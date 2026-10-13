<?php
/**
 * File for an atomic lock based on a single entry in the options table.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Log;

/**
 * Object for an atomic lock, e.g. to prevent the parallel import of the same file by two requests.
 *
 * Hint:
 * We use "INSERT IGNORE" instead of add_option(), as add_option() uses "ON DUPLICATE KEY UPDATE"
 * and would not fail if another process has created the lock in the meantime.
 * See WP_Upgrader::create_lock() for the same approach.
 */
class Lock {
	/**
	 * The name of the option which holds the lock.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The time in seconds after which a lock is treated as stale.
	 *
	 * @var int
	 */
	private int $ttl;

	/**
	 * The token of the lock this instance holds (empty if it holds none).
	 *
	 * @var string
	 */
	private string $token = '';

	/**
	 * Marker whether the release on shutdown is already registered for this instance.
	 *
	 * @var bool
	 */
	private bool $shutdown_registered = false;

	/**
	 * Initialize this lock.
	 *
	 * @param string $name The name of the option which holds the lock.
	 * @param int    $ttl  The time in seconds after which a lock is treated as stale.
	 */
	public function __construct( string $name, int $ttl = 600 ) {
		$this->name = $name;
		$this->ttl  = max( 1, $ttl );
	}

	/**
	 * Return the name of the option which holds the lock.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Try to get the lock.
	 *
	 * @return bool True if this instance holds the lock now.
	 */
	public function acquire(): bool {
		// bail if this instance holds the lock already.
		if ( $this->is_held() ) {
			return true;
		}

		// create a token which identifies this instance.
		$token = wp_generate_password( 20, false ) . '|' . time();

		// the lock is ours.
		if ( $this->insert( $token ) ) {
			return true;
		}

		// bail if the existing lock is not stale, it is held by another process.
		if ( ! $this->remove_stale() ) {
			return false;
		}

		// try it a second time after the stale lock has been removed.
		return $this->insert( $token );
	}

	/**
	 * Return whether this instance holds the lock.
	 *
	 * @return bool
	 */
	public function is_held(): bool {
		return '' !== $this->token;
	}

	/**
	 * Refresh the time of the lock, so a long-running process is not treated as stale.
	 *
	 * @return void
	 */
	public function refresh(): void {
		global $wpdb;

		// bail if this instance does not hold the lock.
		if ( ! $this->is_held() ) {
			return;
		}

		// bail if the lock has been refreshed during the last minute.
		$parts = explode( '|', $this->token );
		if ( ( time() - max( 0, (int) end( $parts ) ) ) < MINUTE_IN_SECONDS ) {
			return;
		}

		// update the lock only if it is still ours.
		$token  = $parts[0] . '|' . time();
		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Atomic lock.
			$wpdb->options,
			array( 'option_value' => $token ),
			array(
				'option_name'  => $this->name,
				'option_value' => $this->token,
			),
			array( '%s' ),
			array( '%s', '%s' )
		);
		if ( 1 === $result ) {
			$this->token = $token;
		}
	}

	/**
	 * Release the lock, but only if this instance holds it.
	 *
	 * @return void
	 */
	public function release(): void {
		global $wpdb;

		// bail if this instance does not hold the lock.
		if ( ! $this->is_held() ) {
			return;
		}

		// remove the lock only if it is still ours.
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Atomic lock.
			$wpdb->options,
			array(
				'option_name'  => $this->name,
				'option_value' => $this->token,
			),
			array( '%s', '%s' )
		);
		wp_cache_delete( $this->name, 'options' );

		// forget the token.
		$this->token = '';
	}

	/**
	 * Release the lock at the end of the request, e.g. after a fatal error or an exit.
	 *
	 * @return void
	 */
	public function release_on_shutdown(): void {
		$this->release();
	}

	/**
	 * Try to create the lock with the given token.
	 *
	 * @param string $token The token which identifies this instance.
	 *
	 * @return bool True if this instance holds the lock now.
	 */
	private function insert( string $token ): bool {
		global $wpdb;

		// try to create the lock.
		$result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES ( %s, %s, 'no' )", $this->name, $token ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Atomic lock, see WP_Upgrader::create_lock().

		// bail if the lock exists already.
		if ( 1 !== $result ) {
			return false;
		}

		// the lock is ours.
		$this->token = $token;
		wp_cache_delete( $this->name, 'options' );

		// release the lock also if the request is aborted by a fatal error or ended by an exit.
		if ( ! $this->shutdown_registered ) {
			register_shutdown_function( array( $this, 'release_on_shutdown' ) );
			$this->shutdown_registered = true;
		}

		return true;
	}

	/**
	 * Remove the existing lock, but only if it is stale.
	 *
	 * @return bool True if a stale lock has been removed.
	 */
	private function remove_stale(): bool {
		global $wpdb;

		// get the existing lock directly from the database.
		$existing = (string) $wpdb->get_var( $wpdb->prepare( "SELECT `option_value` FROM `$wpdb->options` WHERE `option_name` = %s", $this->name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Atomic lock.

		// get the time of the existing lock.
		$parts   = explode( '|', $existing );
		$created = max( 0, (int) end( $parts ) );

		// bail if the existing lock is not stale.
		if ( ( time() - $created ) <= $this->ttl ) {
			return false;
		}

		// remove the stale lock, but only if it has not been replaced in the meantime.
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Atomic lock.
			$wpdb->options,
			array(
				'option_name'  => $this->name,
				'option_value' => $existing,
			),
			array( '%s', '%s' )
		);

		// add a log entry.
		/* translators: %1$s will be replaced by the name of the lock. */
		Log::get_instance()->add( sprintf( __( 'A stale lock (%1$s) has been removed.', 'connector-for-propstack' ), '<code>' . esc_html( $this->name ) . '</code>' ), 'info', 'system' );

		return true;
	}
}

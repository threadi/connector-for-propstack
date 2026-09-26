<?php
/**
 * File to handle basic import functions.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Plugin\ProcessHandler;
use WP_Error;

/**
 * Base object for each import.
 */
class Import_Base {
	/**
	 * List of errors.
	 *
	 * @var array<int,WP_Error>
	 */
	private array $errors = array();

	/**
	 * The process ID.
	 *
	 * @var string
	 */
	private string $process_id = '';

	/**
	 * Marker whether this import needs another run to be completed.
	 *
	 * @var bool
	 */
	protected bool $load_more = false;

	/**
	 * The option which holds the work list of a paginated import.
	 *
	 * @var string
	 */
	protected string $work_list_option = 'cfprop_objects_to_import';

	/**
	 * The option which holds the position of a paginated import.
	 *
	 * @var string
	 */
	protected string $offset_option = 'cfprop_objects_import_offset';

	/**
	 * The option which holds the lock for a single chunk of a paginated import.
	 *
	 * @var string
	 */
	protected string $chunk_lock_option = 'cfprop_import_chunk_lock';

	/**
	 * The token of the chunk lock this instance holds (empty if it holds none).
	 *
	 * @var string
	 */
	private string $chunk_lock_token = '';

	/**
	 * The ID of the chunk lock held by another process (empty if unknown).
	 *
	 * @var string
	 */
	private string $foreign_chunk_lock_id = '';

	/**
	 * Marker whether this run could not get the chunk lock as another process holds it.
	 *
	 * @var bool
	 */
	private bool $locked = false;

	/**
	 * List of object IDs of errors which are already written to the log.
	 *
	 * @var array<int,bool>
	 */
	private array $logged_errors = array();

	/**
	 * Return the header to be used for any API request.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_header(): array {
		return array(
			'X-API-KEY' => get_option( 'propstack_connector_api_key' ),
		);
	}

	/**
	 * Run the import.
	 *
	 * @return void
	 */
	public function run(): void {}

	/**
	 * Add an error to the list of errors during the import.
	 *
	 * @param string $code    The code to use.
	 * @param string $message The message to add as an error.
	 *
	 * @return void
	 */
	public function add_error( string $code, string $message ): void {
		// create the error object.
		$error = new WP_Error();
		$error->add( $code, $message );

		// add the error to the list.
		$this->errors[] = $error;
	}

	/**
	 * Return whether we had errors.
	 *
	 * @return bool
	 */
	public function has_errors(): bool {
		return ! empty( $this->errors );
	}

	/**
	 * Return the list of errors.
	 *
	 * @return array<int,WP_Error>
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Reset the list of errors.
	 *
	 * @return void
	 */
	public function reset_errors(): void {
		$this->errors = array();
	}

	/**
	 * Return a list of all error messages.
	 *
	 * @return string
	 */
	private function get_error_messages(): string {
		// prepare the list of errors.
		$messages = array();

		// add them to the list.
		foreach ( $this->get_errors() as $error ) {
			$messages[] = $error->get_error_message();
		}

		// return the list with linebreak and only unique entries (no doubles).
		return implode( '<br>', array_unique( $messages ) );
	}

	/**
	 * Return an error dialog configuration with the list of collected errors during the import.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_error_dialog_config(): array {
		return array(
			'detail' => array(
				'className' => 'cfprop-dialog',
				'title'     => __( 'Error', 'connector-for-propstack' ),
				'texts'     => array(
					'<p><strong>' . __( 'The following error occurred:', 'connector-for-propstack' ) . '</strong></p>',
					'<p>' . $this->get_error_messages() . '</p>',
				),
				'buttons'   => array(
					array(
						'action'  => 'closeDialog();',
						'variant' => 'primary',
						'text'    => __( 'OK', 'connector-for-propstack' ),
					),
				),
			),
		);
	}

	/**
	 * Save the errors in our own log.
	 *
	 * @return void
	 */
	protected function save_errors_in_log(): void {
		foreach ( $this->get_errors() as $error ) {
			// bail if this error is already in the log.
			if ( isset( $this->logged_errors[ spl_object_id( $error ) ] ) ) {
				continue;
			}

			Log::get_instance()->add( $error->get_error_message(), 'error', 'import' );

			// mark this error as logged.
			$this->logged_errors[ spl_object_id( $error ) ] = true;
		}
	}

	/**
	 * Add the errors of this run to the state of a paginated import.
	 *
	 * Each chunk is a separate request with its own instance, so errors are kept in the
	 * work list to be known in the following chunks. Any error marks the import as incomplete.
	 *
	 * @param array<string,mixed> $import_data The state of the paginated import.
	 *
	 * @return array<string,mixed>
	 */
	protected function persist_errors( array $import_data ): array {
		// get the errors which are already known.
		$persisted = ! empty( $import_data['errors'] ) && is_array( $import_data['errors'] ) ? $import_data['errors'] : array();

		// add each error of this run, but only once.
		foreach ( $this->get_errors() as $error ) {
			$entry = array(
				'code'    => (string) $error->get_error_code(),
				'message' => $error->get_error_message(),
			);

			if ( in_array( $entry, $persisted, true ) ) {
				continue;
			}

			$persisted[] = $entry;
		}

		// save them in the state.
		$import_data['errors'] = $persisted;

		// return the resulting state.
		return $import_data;
	}

	/**
	 * Add the errors of previous chunks from the state of a paginated import to this run.
	 *
	 * These errors have already been written to the log by the chunk which collected them.
	 *
	 * @param array<string,mixed> $import_data The state of the paginated import.
	 *
	 * @return void
	 */
	protected function restore_errors( array $import_data ): void {
		// bail if no error is known.
		if ( empty( $import_data['errors'] ) || ! is_array( $import_data['errors'] ) ) {
			return;
		}

		// collect the errors this run already has.
		$known = array();
		foreach ( $this->get_errors() as $error ) {
			$known[] = $error->get_error_code() . '|' . $error->get_error_message();
		}

		// add each persisted error which is not known yet.
		foreach ( $import_data['errors'] as $entry ) {
			// bail if the entry is not usable.
			if ( ! is_array( $entry ) || ! isset( $entry['code'], $entry['message'] ) ) {
				continue;
			}

			// bail if this error is already known.
			if ( in_array( $entry['code'] . '|' . $entry['message'], $known, true ) ) {
				continue;
			}

			$this->add_error( (string) $entry['code'], (string) $entry['message'] );

			// mark it as logged, as the chunk which collected it did this already.
			$errors = $this->get_errors();
			$error  = end( $errors );
			if ( $error instanceof WP_Error ) {
				$this->logged_errors[ spl_object_id( $error ) ] = true;
			}
		}
	}

	/**
	 * Try to get the lock for a single chunk of a paginated import.
	 *
	 * Hint:
	 * We use "INSERT IGNORE" instead of add_option(), as add_option() uses "ON DUPLICATE KEY UPDATE"
	 * and would not fail if another process has created the lock in the meantime.
	 *
	 * @return bool True if this instance holds the lock now.
	 */
	protected function acquire_chunk_lock(): bool {
		// create a token which identifies this instance.
		$token = wp_generate_password( 20, false ) . '|' . time();

		// the lock is ours.
		if ( $this->insert_chunk_lock( $token ) ) {
			return true;
		}

		// bail if the existing lock is not stale, it is held by another process.
		if ( ! $this->remove_stale_chunk_lock() ) {
			return false;
		}

		// try it a second time after the stale lock has been removed.
		return $this->insert_chunk_lock( $token );
	}

	/**
	 * Try to create the lock for a single chunk with the given token.
	 *
	 * @param string $token The token which identifies this instance.
	 *
	 * @return bool True if this instance holds the lock now.
	 */
	private function insert_chunk_lock( string $token ): bool {
		global $wpdb;

		// try to create the lock.
		$result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES ( %s, %s, 'no' )", $this->chunk_lock_option, $token ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Atomic lock, see WP_Upgrader::create_lock().

		// bail if the lock exists already.
		if ( 1 !== $result ) {
			return false;
		}

		// the lock is ours.
		$this->chunk_lock_token = $token;
		wp_cache_delete( $this->chunk_lock_option, 'options' );

		// release the lock also if the chunk is aborted by a fatal error.
		register_shutdown_function( array( $this, 'release_chunk_lock_on_shutdown' ) );

		return true;
	}

	/**
	 * Remove the existing lock for a single chunk, but only if it is stale.
	 *
	 * @return bool True if a stale lock has been removed.
	 */
	private function remove_stale_chunk_lock(): bool {
		global $wpdb;

		// get the existing lock directly from the database.
		$existing = (string) $wpdb->get_var( $wpdb->prepare( "SELECT `option_value` FROM `$wpdb->options` WHERE `option_name` = %s", $this->chunk_lock_option ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Atomic lock.

		$ttl = 10 * MINUTE_IN_SECONDS;
		/**
		 * Filter the time in seconds after which the lock of a single import chunk is treated as stale.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param int $ttl The time in seconds.
		 */
		$ttl = absint( apply_filters( 'cfprop_import_chunk_lock_ttl', $ttl ) );

		// get the ID and the time of the existing lock.
		$parts                       = explode( '|', $existing );
		$created                     = absint( end( $parts ) );
		$this->foreign_chunk_lock_id = $parts[0];

		// bail if the existing lock is not stale.
		if ( ( time() - $created ) <= $ttl ) {
			return false;
		}

		// remove the stale lock, but only if it has not been replaced in the meantime.
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Atomic lock.
			$wpdb->options,
			array(
				'option_name'  => $this->chunk_lock_option,
				'option_value' => $existing,
			),
			array( '%s', '%s' )
		);

		// add a log entry.
		Log::get_instance()->add( __( 'A stale lock of an import step has been removed.', 'connector-for-propstack' ), 'info', 'import' );

		return true;
	}

	/**
	 * Refresh the time of the lock for a single chunk, so a long-running chunk (e.g. via WP CLI)
	 * is not treated as stale by another process.
	 *
	 * @return void
	 */
	protected function refresh_chunk_lock(): void {
		global $wpdb;

		// bail if this instance does not hold the lock.
		if ( empty( $this->chunk_lock_token ) ) {
			return;
		}

		// bail if the lock has been refreshed during the last minute.
		$parts = explode( '|', $this->chunk_lock_token );
		if ( ( time() - absint( end( $parts ) ) ) < MINUTE_IN_SECONDS ) {
			return;
		}

		// update the lock only if it is still ours, the ID of the lock is kept.
		$token  = $parts[0] . '|' . time();
		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Atomic lock.
			$wpdb->options,
			array( 'option_value' => $token ),
			array(
				'option_name'  => $this->chunk_lock_option,
				'option_value' => $this->chunk_lock_token,
			),
			array( '%s' ),
			array( '%s', '%s' )
		);
		if ( 1 === $result ) {
			$this->chunk_lock_token = $token;
		}
	}

	/**
	 * Release the lock of this instance at the end of the request, e.g. after a fatal error.
	 *
	 * @return void
	 */
	public function release_chunk_lock_on_shutdown(): void {
		$this->release_chunk_lock();
	}

	/**
	 * Return whether this run could not get the lock for its chunk as another process holds it.
	 *
	 * @return bool
	 */
	public function is_locked(): bool {
		return $this->locked;
	}

	/**
	 * Release the lock for a single chunk of a paginated import, but only if this instance holds it.
	 *
	 * @return void
	 */
	protected function release_chunk_lock(): void {
		global $wpdb;

		// bail if this instance does not hold the lock.
		if ( empty( $this->chunk_lock_token ) ) {
			return;
		}

		// remove the lock only if it is still ours.
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Atomic lock.
			$wpdb->options,
			array(
				'option_name'  => $this->chunk_lock_option,
				'option_value' => $this->chunk_lock_token,
			),
			array( '%s', '%s' )
		);
		wp_cache_delete( $this->chunk_lock_option, 'options' );

		// forget the token.
		$this->chunk_lock_token = '';
	}

	/**
	 * Handle a run which could not get the lock for its chunk as another process is working on it.
	 *
	 * Nothing is changed. An AJAX request asks for another run, so the progress dialog polls again.
	 * Any other caller loops by itself and would only spin, so it stops here - the process which
	 * holds the lock continues the import.
	 *
	 * @return void
	 */
	protected function handle_locked_chunk(): void {
		// mark this run as locked.
		$this->locked = true;

		// add the hint.
		$this->add_error( 'propstack_object_import_chunk_locked', __( 'Another import step is still running. Please wait.', 'connector-for-propstack' ) );

		// add a log entry, but only once per lock of the other process (the progress dialog polls every second).
		if ( get_transient( 'cfprop_import_chunk_locked_logged' ) !== $this->foreign_chunk_lock_id ) {
			Log::get_instance()->add( __( 'Another import step is still running. Please wait.', 'connector-for-propstack' ), 'info', 'import' );
			set_transient( 'cfprop_import_chunk_locked_logged', $this->foreign_chunk_lock_id, HOUR_IN_SECONDS );
		}

		// only AJAX requests poll again.
		$this->set_load_more( wp_doing_ajax() );

		// bail if this is not an AJAX request.
		if ( ! wp_doing_ajax() ) {
			return;
		}

		// update the status for the progress dialog.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_id( $this->get_process_id() );
		$process_handler->set_status( __( 'Another import step is still running. Please wait.', 'connector-for-propstack' ) );

		// wait a moment so the progress dialog does not hammer the server.
		sleep( 1 );
	}

	/**
	 * Return the process ID to use during the import.
	 *
	 * @return string
	 */
	public function get_process_id(): string {
		return $this->process_id;
	}

	/**
	 * Set the process ID.
	 *
	 * @param string $process_id The process ID.
	 *
	 * @return void
	 */
	public function set_process_id( string $process_id ): void {
		$this->process_id = $process_id;
	}

	/**
	 * Catch fatal errors that try/catch cannot handle and clean up the running-state.
	 *
	 * @return void
	 */
	public function handle_fatal_shutdown(): void {
		$this->process_shutdown_error( error_get_last() );
	}

	/**
	 * Testable core of the shutdown handling.
	 *
	 * @param array{type:int,message:string,file:string,line:int}|null $error The error.
	 *
	 * @return void
	 */
	public function process_shutdown_error( ?array $error ): void {
		// bail if there was no fatal error.
		if ( null === $error || ! in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ), true ) ) {
			return;
		}

		// release the lock of the chunk which has been aborted.
		$this->release_chunk_lock();

		// bail if import already finished cleanly.
		if ( 0 === absint( get_option( CFPROP_IMPORT_RUNNING, 0 ) ) ) {
			return;
		}

		// log this event.
		Log::get_instance()->add(
			__( 'Import was aborted by a fatal PHP error:', 'connector-for-propstack' ) . '<br><code>' . esc_html( $error['message'] ) . '</code> ' . esc_html( $error['file'] ) . ':' . absint( $error['line'] ),
			'error',
			'import'
		);

		// reset the running-flag so the user is not stuck.
		update_option( CFPROP_IMPORT_RUNNING, 0 );
		$this->clear_work_list();
	}

	/**
	 * Return whether this import needs another run to be completed.
	 *
	 * @return bool
	 */
	public function has_load_more(): bool {
		return $this->load_more;
	}

	/**
	 * Set whether this import needs another run to be completed.
	 *
	 * @param bool $load_more True if another run is needed.
	 *
	 * @return void
	 */
	public function set_load_more( bool $load_more ): void {
		$this->load_more = $load_more;
	}

	/**
	 * Remove the complete state of a paginated import.
	 *
	 * @return void
	 */
	protected function clear_work_list(): void {
		// get the metadata to know how many blocks exist.
		$import_data = get_option( $this->work_list_option, array() );

		// delete every block.
		if ( is_array( $import_data ) && isset( $import_data['blocks'] ) ) {
			$count = absint( $import_data['blocks'] );
			for ( $i = 0; $i < $count; $i++ ) {
				delete_option( $this->work_list_option . '_block_' . $i );
			}
		}

		// reset the metadata and the position.
		update_option( $this->work_list_option, array() );
		update_option( $this->offset_option, 0 );
	}

	/**
	 * Return the name of the option which holds the work list of a paginated import.
	 *
	 * @return string
	 */
	public function get_work_list_option(): string {
		return $this->work_list_option;
	}

	/**
	 * Return the name of the option which holds the position of a paginated import.
	 *
	 * @return string
	 */
	public function get_offset_option(): string {
		return $this->offset_option;
	}

	/**
	 * End a paginated import before all objects have been processed.
	 *
	 * The objects imported so far are kept. There is no cleanup phase, so no object is removed,
	 * and no md5 hash is saved, so the next import processes all objects again.
	 *
	 * @return void
	 */
	public function end_early(): void {
		// remove the work list and the position.
		$this->clear_work_list();
		$this->set_load_more( false );

		$instance = $this;
		/**
		 * Run additional tasks after any import of objects.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param Import_Base $instance The import object.
		 */
		do_action( 'cfprop_import_object_after', $instance );

		// add a log entry.
		Log::get_instance()->add( __( 'Import of objects has been ended early. The other objects will follow with the next import.', 'connector-for-propstack' ), 'info', 'import' );

		// update the running marker.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_id( $this->get_process_id() );
		$process_handler->set_running( 0 );
		update_option( CFPROP_IMPORT_RUNNING, 0 );
	}
}

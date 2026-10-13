<?php
/**
 * File for an object to handle AJAX-driven processes.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle AJAX-driven processes.
 */
class ProcessHandler {

	/**
	 * The process ID for the actual request.
	 *
	 * @var string
	 */
	private string $process_id = '';

	/**
	 * The prefix for the transients which hold the values of each process.
	 *
	 * @var string
	 */
	public const TRANSIENT_PREFIX = 'cfprop_process_';

	/**
	 * Instance of this object.
	 *
	 * @var ?ProcessHandler
	 */
	private static ?ProcessHandler $instance = null;

	/**
	 * Constructor, which sets the active method.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): ProcessHandler {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_ajax_cfprop_process_info', array( $this, 'get_process_info_by_ajax' ) );
	}

	/**
	 * Return a unique ID for the current process.
	 *
	 * @return string
	 */
	public function create_id(): string {
		return uniqid( '', true );
	}

	/**
	 * Return the process info via AJAX.
	 *
	 * @return void
	 */
	public function get_process_info_by_ajax(): void {
		// check nonce.
		check_ajax_referer( 'get-propstack-process-info', 'nonce' );

		// bail if the capability check fails.
		if ( ! current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// get the process ID from request.
		$process_id = filter_input( INPUT_POST, 'process_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// bail if no process ID is set.
		if ( empty( $process_id ) ) {
			wp_send_json_error();
		}

		// set the process ID on the object.
		$this->set_id( $process_id );

		// collect the response.
		$response = array(
			absint( $this->get( 'count' ) ),
			absint( $this->get( 'max_count' ) ),
			absint( $this->get( 'running' ) ),
			wp_kses_post( $this->get( 'status' ) ),
			wp_json_encode( $this->get_message() ),
		);

		// remove this ID from the process' values if the process has been finished.
		if ( 0 === absint( $this->get( 'running' ) ) ) {
			$this->delete();
		}

		// return the actual process info.
		wp_send_json( $response );
	}

	/**
	 * Set a value for the actual process.
	 *
	 * Each process is saved in its own transient, so parallel processes do not overwrite their values.
	 *
	 * @param string $key The key.
	 * @param mixed  $value The value.
	 *
	 * @return void
	 */
	private function set( string $key, mixed $value ): void {
		// bail if no process ID is set.
		if ( empty( $this->get_id() ) ) {
			return;
		}

		// get the actual values for the actual process ID.
		$values = $this->get_values();

		// add or update the given key with its value.
		$values[ $key ] = $value;

		// remember when this process was touched the last time.
		$values['updated'] = time();

		// save it (the transient expires if it has not been touched for a while).
		set_transient( $this->get_transient_name(), $values, $this->get_max_age() );
	}

	/**
	 * Return the value for the given key.
	 *
	 * @param string $key The key.
	 *
	 * @return mixed
	 */
	private function get( string $key ): mixed {
		// bail if no process ID is set.
		if ( empty( $this->get_id() ) ) {
			return '';
		}

		// get the values.
		$values = $this->get_values();

		// bail if the requested key is not set.
		if ( ! isset( $values[ $key ] ) ) {
			return '';
		}

		// return the value.
		return $values[ $key ];
	}

	/**
	 * Return all values of the actual process.
	 *
	 * @return array<string,mixed>
	 */
	private function get_values(): array {
		// get the values.
		$values = get_transient( $this->get_transient_name() );

		// if this is not an array, create it.
		if ( ! is_array( $values ) ) {
			return array();
		}

		// return the values.
		return $values;
	}

	/**
	 * Return the name of the transient which holds the values of the actual process.
	 *
	 * @return string
	 */
	private function get_transient_name(): string {
		return self::TRANSIENT_PREFIX . md5( $this->get_id() );
	}

	/**
	 * Return the max age of process values in seconds.
	 *
	 * @return int
	 */
	private function get_max_age(): int {
		$max_age = 15 * MINUTE_IN_SECONDS;

		/**
		 * Filter the max age of process values in seconds.
		 *
		 * @since 1.1.0 Available since 1.1.0.
		 * @param int $max_age The max age in seconds.
		 */
		return max( 1, absint( apply_filters( 'cfprop_process_values_max_age', $max_age ) ) );
	}

	/**
	 * Return the process ID for the actual request.
	 *
	 * @return string
	 */
	private function get_id(): string {
		return $this->process_id;
	}

	/**
	 * Set the process ID for the actual request.
	 *
	 * @param string $process_id The process ID.
	 *
	 * @return void
	 */
	public function set_id( string $process_id ): void {
		if ( empty( $process_id ) && ! empty( $this->process_id ) ) {
			return;
		}
		$this->process_id = $process_id;
	}

	/**
	 * Return the count of the actual process.
	 *
	 * @return int
	 */
	public function get_count(): int {
		return absint( $this->get( 'count' ) );
	}

	/**
	 * Set the count of the actual process.
	 *
	 * @param int $count The count.
	 *
	 * @return void
	 */
	public function set_count( int $count ): void {
		$this->set( 'count', $count );
	}

	/**
	 * Update the count of the actual process with the given value.
	 *
	 * @param int $count The count.
	 *
	 * @return void
	 */
	public function update_count( int $count ): void {
		// get the actual count.
		$actual_count = absint( $this->get( 'count' ) );
		$this->set( 'count', $actual_count + $count );
	}

	/**
	 * Return the max count of the actual process.
	 *
	 * @return int
	 */
	public function get_max_count(): int {
		return absint( $this->get( 'max_count' ) );
	}

	/**
	 * Set the max count of the actual process.
	 *
	 * @param int $count The max count.
	 *
	 * @return void
	 */
	public function set_max_count( int $count ): void {
		$this->set( 'max_count', $count );
	}

	/**
	 * Set the status of the actual process.
	 *
	 * @param string $status The status.
	 *
	 * @return void
	 */
	public function set_status( string $status ): void {
		$this->set( 'status', $status );
	}

	/**
	 * Set the time then the actual process startet.
	 *
	 * @param int $time The time when it started.
	 *
	 * @return void
	 */
	public function set_running( int $time ): void {
		$this->set( 'running', $time );
	}

	/**
	 * Return the message as an array for the library easy-dialog-for-wordpress.
	 *
	 * @return array<string,mixed>
	 */
	private function get_message(): array {
		// get the value.
		$value = $this->get( 'message' );

		// if the value is not set, return an empty array.
		if ( empty( $value ) ) {
			return array();
		}

		// if the value is not an array, return an empty array.
		if ( ! is_array( $value ) ) {
			return array();
		}

		// return the value.
		return $value;
	}

	/**
	 * Set the message for the actual process.
	 *
	 * @param array<string,mixed> $message The message.
	 *
	 * @return void
	 */
	public function set_message( array $message ): void {
		$this->set( 'message', $message );
	}

	/**
	 * Remove outdated entries from the process values.
	 *
	 * Hint:
	 * The values are not removed when they have been read, as a very fast process could
	 * be finished before the dialog asked for its result the first time. They expire
	 * after a while instead (as transient).
	 *
	 * @return void
	 */
	private function delete(): void {
		// remove the list of all processes used before 2.1.0, if it still exists.
		if ( false !== get_option( 'cfprop_process_values' ) ) {
			delete_option( 'cfprop_process_values' );
		}
	}
}

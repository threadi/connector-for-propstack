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
		add_action( 'wp_ajax_get_propstack_process_info', array( $this, 'get_process_info_by_ajax' ) );
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
		if ( 0 === $this->get( 'running' ) ) {
			$this->delete();
		}

		// return the actual process info.
		wp_send_json( $response );
	}

	/**
	 * Set a value for the actual process.
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
		$values = get_option( 'propstack_connector_process_values' );

		// if this is not an array, create it.
		if ( ! is_array( $values ) ) {
			$values = array();
		}

		// create the entry for the ID if it does not exist.
		if ( ! isset( $values[ $this->get_id() ] ) ) {
			$values[ $this->get_id() ] = array();
		}

		// add or update the given key with its value.
		$values[ $this->get_id() ][ $key ] = $value;

		// save it.
		update_option( 'propstack_connector_process_values', $values );
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
		$values = get_option( 'propstack_connector_process_values' );

		// if this is not an array, create it.
		if ( ! is_array( $values ) ) {
			$values = array();
		}

		// bail if no values are set for the actual process ID.
		if ( empty( $values[ $this->get_id() ] ) ) {
			return '';
		}

		// bail if the requested key is not set.
		if ( ! isset( $values[ $this->get_id() ][ $key ] ) ) {
			return '';
		}

		// return the value.
		return $values[ $this->get_id() ][ $key ];
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
	 * Delete the actual ID from the process values.
	 *
	 * Hint: not if we are in debug mode.
	 *
	 * @return void
	 */
	private function delete(): void {
		// bail if we are in debug mode.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return;
		}

		// get the values.
		$values = get_option( 'propstack_connector_process_values' );

		// if this is not an array, create it.
		if ( ! is_array( $values ) ) {
			$values = array();
		}

		// delete the entry for the ID if it exists.
		if ( isset( $values[ $this->get_id() ] ) ) {
			unset( $values[ $this->get_id() ] );
		}

		// save it.
		update_option( 'propstack_connector_process_values', $values );
	}
}

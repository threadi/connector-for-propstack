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
	 * Return a list of all error messages.
	 *
	 * @return string
	 */
	private function get_error_messages(): string {
		$messages = '';
		foreach ( $this->get_errors() as $error ) {
			$messages .= $error->get_error_message() . '<br>';
		}
		return $messages;
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
			Log::get_instance()->add( $error->get_error_message(), 'error', 'import' );
		}
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
	 * Check for running import processes.
	 *
	 * @param string $option_name The name of the option to check.
	 *
	 * @return bool True, if a process is running, false otherwise.
	 */
	protected function is_process_running( string $option_name ): bool {
		// get the timestamp of the lock.
		$started = absint( get_option( $option_name ) );

		// no lock is set.
		if ( 0 === $started ) {
			return false;
		}

		$hour = HOUR_IN_SECONDS;
		/**
		 * Filter the timeout for the process lock (which is by default 1 hour).
		 *
		 * @since 1.0.3 Available since 1.0.3.
		 *
		 * @param int    $timeout     The timeframe in seconds.
		 * @param string $option_name The name of the option to check.
		 */
		$timeout = absint( apply_filters( 'cfprop_process_lock_timeout', $hour, $option_name ) );

		// lock is too new and still running.
		if ( ( time() - $started ) < $timeout ) {
			// return true, as the process is still running.
			return true;
		}

		// log this event.
		Log::get_instance()->add(
			sprintf(
			/* translators: %1$s will be replaced by the option name, %2$d by the age in seconds. */
				__( 'A previous process (%1$s) did not end properly and blocked further runs for %2$d seconds. The lock has been released automatically. Check the log for a preceding fatal error, e.g. memory limit or timeout.', 'connector-for-propstack' ),
				'<code>' . $option_name . '</code>',
				time() - $started
			),
			'error',
			'import'
		);

		// reset the lock.
		update_option( $option_name, 0 );

		// return false, as no process is running.
		return false;
	}
}

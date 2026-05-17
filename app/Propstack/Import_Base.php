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
}

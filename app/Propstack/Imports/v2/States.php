<?php
/**
 * File to handle the import of object states from Propstack via API v2.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Imports\v2;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Languages;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Plugin\ProcessHandler;
use ConnectorForPropstack\Plugin\Settings;
use ConnectorForPropstack\Propstack\ApiRequest;
use ConnectorForPropstack\Propstack\Import_Base;
use ConnectorForPropstack\Propstack\Taxonomies\Status;

/**
 * Object to import object states from Propstack API.
 */
class States extends Import_Base {
	/**
	 * The URL of the Propstack API to import the object states.
	 *
	 * @var string
	 */
	private string $url = 'https://api.propstack.de/v2/property_statuses';

	/**
	 * Initialize this object.
	 */
	public function __construct() {}

	/**
	 * Process the import of objects from Propstack.
	 *
	 * @return void
	 */
	public function run(): void {
		// bail if an import is still running.
		if ( Helper::is_process_running( CFPROP_STATES_IMPORT_RUNNING ) ) {
			// add the error.
			$this->add_error( 'propstack_states_import_is_running', __( 'Import of states is still running. Please wait.', 'connector-for-propstack' ) );

			// log the errors.
			$this->save_errors_in_log();

			// do nothing more.
			return;
		}

		// bail if the deletion is still running.
		if ( Helper::is_process_running( CFPROP_STATES_DELETE_RUNNING ) ) {
			// add the error.
			$this->add_error( 'propstack_states_deletion_is_running', __( 'Deletion of states is still running. Please wait.', 'connector-for-propstack' ) );

			// log the errors.
			$this->save_errors_in_log();

			// do nothing more.
			return;
		}

		// get the process ID from the request.
		$process_id = filter_input( INPUT_POST, 'process_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( is_null( $process_id ) ) {
			$process_id = '';
		}

		// get the process handler and set the process ID.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_id( $process_id );

		// set initial value.
		$process_handler->set_count( 0 );
		$process_handler->set_max_count( 0 );
		$process_handler->set_status( __( 'Import of states starting', 'connector-for-propstack' ) );
		$process_handler->set_running( time() );
		update_option( CFPROP_STATES_IMPORT_RUNNING, time() );

		// get the enabled languages.
		$languages = array( get_option( 'propstack_connector_languages' ) => 1 );

		// if no language is set, use the fallback language.
		if ( empty( get_option( 'propstack_connector_languages' ) ) ) {
			$languages = array( Languages::get_instance()->get_fallback_language_name() => 1 );
		}

		/**
		 * Filter the languages to import object states from Propstack.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<string,int> $languages The languages to import.
		 */
		$languages = apply_filters( 'cfprop_import_object_states_languages', $languages );

		// if any error occurred during import of objects, collect and log it.
		try {
			// loop through each enabled language and import its objects.
			foreach ( $languages as $language_code => $language_enabled ) {
				// create and send the API request.
				$request_object = new ApiRequest();
				$request_object->set_url( $this->get_url( $language_code ) );
				$request_object->set_post_data( '' );
				$request_object->set_method( 'GET' );
				$request_object->set_md5( md5( $this->get_url( $language_code ) ) );
				$request_object->set_header( $this->get_header() );
				$request_object->send();

				// bail on error.
				if ( 200 !== $request_object->get_http_status() ) {
					// save this error.
					$this->add_error( 'propstack_states_import_http_status', __( 'Propstack API answered with wrong HTTP-status.', 'connector-for-propstack' ) );

					// do nothing more in this language.
					continue;
				}

				// get the response body.
				$body = $request_object->get_response();

				// convert the response to an array.
				$data = json_decode( $body, true );

				// bail if no data is given.
				if ( empty( $data['data'] ) ) {
					// save this error.
					$this->add_error( 'propstack_states_import_no_data', __( 'Propstack API answered with wrong data.', 'connector-for-propstack' ) );

					// do nothing more in this language.
					continue;
				}

				// update the markers.
				$process_handler->set_max_count( count( $data['data'] ) );
				/* translators: %1$s will be replaced by a language name. */
				$process_handler->set_status( sprintf( __( 'Import of object states in language %1$s is running', 'connector-for-propstack' ), '<em>' . $language_code . '</em>' ) );

				// loop over the data and add the states.
				foreach ( $data['data'] as $state ) {
					// update the status.
					/* translators: %1$s will be replaced by the object title. */
					$process_handler->set_status( sprintf( __( 'Import of object state %1$s in language %2$s', 'connector-for-propstack' ), '<em>' . $state['name'] . '</em>', '<em>' . $language_code . '</em>' ) );

					// get the term by given state ID.
					$term_id = \ConnectorForPropstack\Propstack\States::get_instance()->get_term_id_by_id( absint( $state['id'] ), $language_code );

					// if the state does not exist, create it.
					if ( ! is_int( $term_id ) ) {
						// add the term.
						$term_data = wp_insert_term( $state['name'], Status::get_instance()->get_name() );

						// bail on error.
						if ( is_wp_error( $term_data ) ) {
							continue;
						}

						// set the term ID as metadata.
						$term_id = $term_data['term_id'];
					}

					// set the language code.
					update_term_meta( $term_id, 'language_code', $language_code );

					// update the counter.
					$process_handler->set_count( $process_handler->get_count() + 1 );
				}
			}
		} catch ( \Throwable $e ) {
			// log this event.
			Log::get_instance()->add( __( 'Following error occurred during the import of states via API v2:', 'connector-for-propstack' ) . '<br>' . __( 'Message:', 'connector-for-propstack' ) . '<code>' . $e->getMessage() . '</code><br>' . __( 'Code:', 'connector-for-propstack' ) . '<code>' . $e->getCode() . '</code><br>' . __( 'File:', 'connector-for-propstack' ) . '<code>' . $e->getFile() . '</code><br>' . __( 'Line:', 'connector-for-propstack' ) . '<code>' . $e->getLine() . '</code>', 'error', 'import' );

			// show hint.
			/* translators: %1$s will be replaced by a URL. */
			$this->add_error( 'propstack_states_import_error', sprintf( __( 'Error occurred. Check <a href="%1$s">the log</a> for details.', 'connector-for-propstack' ), esc_url( Settings::get_instance()->get_url( 'propstack_connector_logs' ) ) ) );
		} finally {
			// stop process handler.
			$process_handler->set_running( 0 );

			// update the marker.
			update_option( CFPROP_STATES_IMPORT_RUNNING, 0 );

			// show messages.
			if ( $this->has_errors() ) {
				$process_handler->set_message( $this->get_error_dialog_config() );
			} else {
				$process_handler->set_message( $this->get_success_dialog_config() );
			}
		}
	}

	/**
	 * Return the API URL to import object states.
	 *
	 * @param string $language_code The language to use for the URL.
	 *
	 * @return string
	 */
	private function get_url( string $language_code ): string {
		// get the URL.
		$url = add_query_arg(
			array(
				'locale' => $language_code,
			),
			$this->url
		);

		/**
		 * Filter the URL of the API to import object states from Propstack.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param string $url The URL.
		 */
		return apply_filters( 'cfprop_api_object_states_url', $url );
	}

	/**
	 * Return a success dialog configuration.
	 *
	 * @return array<string,mixed>
	 */
	private function get_success_dialog_config(): array {
		return array(
			'detail' => array(
				'className' => 'propstack-connector-dialog',
				'title'     => __( 'Import has been run', 'connector-for-propstack' ),
				'texts'     => array(
					'<p><strong>' . __( 'The import of object states from your Propstack account has been run.', 'connector-for-propstack' ) . '</strong></p>',
					'<p>' . __( 'You will find them in the list in the backend and your frontend.', 'connector-for-propstack' ) . '</p>',
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
}

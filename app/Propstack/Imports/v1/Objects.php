<?php
/**
 * File to object to handle the import of objects from Propstack via API v1.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Imports\v1;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Languages;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Plugin\ProcessHandler;
use ConnectorForPropstack\Plugin\Settings;
use ConnectorForPropstack\Propstack\ApiRequest;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Import_Base;
use ConnectorForPropstack\Propstack\KnowledgeCenter;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use WP_Error;

/**
 * Object to import objects from Propstack API.
 */
class Objects extends Import_Base {
	/**
	 * The URL of the Propstack API to import object.
	 *
	 * @var string
	 */
	private string $url = 'https://api.propstack.de/v1/units';

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
		if ( absint( get_option( CFPROP_IMPORT_RUNNING ) ) > 0 ) {
			// add the error.
			$this->add_error( 'propstack_object_import_is_running', __( 'Import of objects is still running. Please wait.', 'connector-for-propstack' ) );

			// log the errors.
			$this->save_errors_in_log();

			// do nothing more.
			return;
		}

		// bail if the deletion is still running.
		if ( absint( get_option( CFPROP_DELETE_RUNNING ) ) > 0 ) {
			// add the error.
			$this->add_error( 'propstack_object_deletion_is_running', __( 'Deletion of objects is still running. Please wait.', 'connector-for-propstack' ) );

			// log the errors.
			$this->save_errors_in_log();

			// do nothing more.
			return;
		}

		// get the process handler and set the process ID.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_id( $this->get_process_id() );

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
		$languages = apply_filters( 'cfprop_import_object_languages', $languages );

		$instance = $this;
		/**
		 * Run additional tasks before starting the import of objects.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param ProcessHandler $process_handler The process handler.
		 * @param Objects        $instance        The import object.
		 */
		do_action( 'cfprop_import_object_before_start', $process_handler, $instance );

		// bail if any error occurred before.
		if ( $this->has_errors() ) {
			// log the errors.
			$this->save_errors_in_log();

			// do nothing more.
			$process_handler->set_running( 0 );

			// do nothing more.
			return;
		}

		// set initial value.
		$process_handler->set_count( 0 );
		$process_handler->set_max_count( 0 );
		$process_handler->set_status( __( 'Import of objects starting', 'connector-for-propstack' ) );
		$process_handler->set_running( time() );
		update_option( CFPROP_IMPORT_RUNNING, time() );

		// add a log entry if debug is enabled.
		if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
			Log::get_instance()->add( __( 'Import of objects starting', 'connector-for-propstack' ), 'info', 'import' );
		}

		// if any error occurred during import of objects, collect and log it.
		try {
			// loop through each enabled language and import its objects.
			foreach ( $languages as $language_code => $language_enabled ) {
				// add a log entry if debug is enabled.
				if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
					/* translators: a title will replace %1$s. */
					Log::get_instance()->add( sprintf( __( 'Import of objects in language %1$s starting', 'connector-for-propstack' ), ' <em>' . $language_code . '</em>' ), 'info', 'import' );
				}

				// create and send API request.
				$request_object = new ApiRequest();
				$request_object->set_url( $this->get_url( $language_code ) );
				$request_object->set_post_data( '' );
				$request_object->set_method( 'GET' );
				$request_object->set_md5( md5( $this->get_url( $language_code ) ) );
				$request_object->set_header( $this->get_header() );
				$request_object->send();

				// bail on error.
				if ( 200 !== $request_object->get_http_status() ) {
					// save the error.
					$this->add_error( 'propstack_object_import_http_status', __( 'The Propstack API returned an unexpected HTTP status when retrieving objects:', 'connector-for-propstack' ) . ' <code>' . $request_object->get_http_status() . '</code>' );

					// get the details of the error from the response.
					$response = $request_object->get_response();

					// convert the response to an array.
					$data = json_decode( $response, true );

					// add the error.
					if ( is_array( $data ) && isset( $data['errors'] ) ) {
						foreach ( $data['errors'] as $error ) {
							// get help entry by the given error message.
							$knowledge_center_entry = KnowledgeCenter::get_instance()->get_entry_by_text( $error );

							// prepare the error entry.
							$error_entry = __( 'Error from Propstack API for objects:', 'connector-for-propstack' ) . ' <code>' . $error . '</code>';

							// add the help text if it has a match.
							if ( $knowledge_center_entry ) {
								$error_entry .= $knowledge_center_entry->get_text();
							}
							$this->add_error( 'propstack_object_import_http_status_details', $error_entry );
						}
					}

					// do nothing more in this language.
					continue;
				}

				// get the response body.
				$response = $request_object->get_response();

				// convert the response to an array.
				$data = json_decode( $response, true );

				// bail on any error.
				if ( ! is_array( $data ) ) {
					// add a log entry if debug is enabled.
					Log::get_instance()->add( __( 'Error during decoding the API response.', 'connector-for-propstack' ), 'error', 'import' );

					// do nothing more.
					continue;
				}

				/**
				 * Filter the response data from Propstack.
				 *
				 * @since 1.0.0 Available since 1.0.0.
				 * @param array<string,mixed> $data The response data.
				 */
				$data = apply_filters( 'cfprop_object_import_response', $data );

				// get the md5 hash of this content.
				$md5 = md5( Helper::get_json( $data ) );

				// bail if the md5 of this content has not changed and debug mode is not enabled.
				if ( get_option( 'cfprop_md5_' . $language_code ) === $md5 && 1 !== absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
					// add a log entry.
					/* translators: a title will replace %1$s. */
					Log::get_instance()->add( sprintf( __( 'The objects in the %1$s language have not been modified. The import will not be performed.', 'connector-for-propstack' ), ' <em>' . $language_code . '</em>' ), 'info', 'import' );

					/**
					 * Run actions if objects in Propstack did not change.
					 *
					 * @since 1.0.0 Available since 1.0.0.
					 *
					 * @param string $md5 The md5-hash from the content of the response.
					 */
					do_action( 'cfprop_import_content_not_change', $md5 );

					// do nothing more.
					continue;
				}

				// get the name of the post-type to use.
				$post_type_name = ImmoObject::get_instance()->get_name();

				// update the markers.
				$this->set_max_count( $process_handler, count( $data ) );
				$this->set_new_status( $process_handler, __( 'Import of objects is running', 'connector-for-propstack' ) );

				// add a log entry if debug is enabled.
				if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
					Log::get_instance()->add( __( 'Import of objects is running', 'connector-for-propstack' ), 'info', 'import' );
				}

				// show cli hint.
				$progress = Helper::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Import objects in language ' . $language_code, count( $data ) ) : false;

				// loop through the data and import or update each object.
				foreach ( $data as $object ) {
					$prevent_import = false;
					/**
					 * Prevent import of this object under custom conditions.
					 *
					 * @since        1.0.0 Available since 1.0.0.
					 *
					 * @param bool  $prevent_import True to prevent the import.
					 * @param array $object         The object data from API.
					 *
					 * @noinspection PhpConditionAlreadyCheckedInspection
					 */
					if ( apply_filters( 'cfprop_prevent_import_of_object', $prevent_import, $object ) ) {
						// update the counter.
						$this->set_count( $process_handler, $process_handler->get_count() + 1 );

						// update tick.
						$progress ? $progress->tick() : '';

						// do nothing more.
						continue;
					}

					// update the status.
					/* translators: %1$s will be replaced by the object title. */
					$this->set_new_status( $process_handler, sprintf( __( 'Import of object %1$s', 'connector-for-propstack' ), '<em>' . $object['title']['value'] . '</em>' ) );

					// add a log entry if debug is enabled.
					if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
						/* translators: %1$s will be replaced by the object title. */
						Log::get_instance()->add( sprintf( __( 'Import of object %1$s', 'connector-for-propstack' ), '<em>' . $object['title']['value'] . '</em>' ), 'info', 'import' );
					}

					// get the object with the given ID.
					$propstack_immo_object = ImmoObjects::get_instance()->get_object_by_object_id( $object['id'], $language_code );

					// if the object does not exist, create it.
					if ( ! $propstack_immo_object instanceof \ConnectorForPropstack\Propstack\ImmoObject ) {
						// add a log entry if debug is enabled.
						if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
							/* translators: %1$s will be replaced by the object title. */
							Log::get_instance()->add( sprintf( __( 'Creating new entry for the object %1$s', 'connector-for-propstack' ), '<em>' . $object['title']['value'] . '</em>' ), 'info', 'import' );
						}

						// prepare the query to insert a new object.
						$query = array(
							'post_type'    => $post_type_name,
							'post_title'   => $object['title']['value'],
							'post_status'  => 'publish',
							'post_author'  => Helper::get_author_during_object_creation(),
							'post_content' => '',
						);

						/**
						 * Filter the query to add a new object during import.
						 *
						 * @since 1.0.0 Available since 1.0.0.
						 *
						 * @param array<string,mixed> $query  The query.
						 * @param array<string,mixed> $object The object data from API.
						 */
						$query = apply_filters( 'cfprop_new_object_query', $query, $object );

						// add the object.
						$post_id = wp_insert_post( $query );

						// bail if inserting failed.
						if ( $post_id instanceof WP_Error ) { // @phpstan-ignore instanceof.alwaysFalse
							// save the error.
							$this->add_error( 'propstack_object_could_not_be_saved', __( 'New object could not be created. The following error occurred:', 'connector-for-propstack' ) . ' <code>' . wp_json_encode( $post_id ) . '</code>' );

							// update the counter.
							$this->set_count( $process_handler, $process_handler->get_count() + 1 );

							// do nothing more.
							continue;
						}
					} else {
						$post_id = $propstack_immo_object->get_id();
					}

					/**
					 * Run additional tasks for a single language-specific object import.
					 *
					 * @since 1.0.0 Available since 1.0.0.
					 *
					 * @param array<string,mixed> $object        The object data from API.
					 * @param int                 $post_id       The post-ID of the object.
					 * @param string              $language_code The used language.
					 */
					do_action( 'cfprop_import_object', $object, $post_id, $language_code );

					// set the language.
					update_post_meta( $post_id, 'language_code', $language_code );

					// mark the object as changed.
					update_post_meta( $post_id, 'changed', time() );

					// update the counter.
					$this->set_count( $process_handler, $process_handler->get_count() + 1 );

					// update tick.
					$progress ? $progress->tick() : '';
				}

				/**
				 * Run additional tasks for importing objects in a given language.
				 *
				 * @since 1.0.0 Available since 1.0.0.
				 *
				 * @param string $language_code The used language.
				 */
				do_action( 'cfprop_import_language', $language_code );

				// save the md5 hash.
				update_option( 'cfprop_md5_' . $language_code, $md5 );

				// set finished.
				$progress ? $progress->finish() : '';
			}

			// set the result.
			if ( $this->has_errors() ) {
				/**
				 * Run additional tasks if any error occurred during import of objects.
				 *
				 * @since 1.0.0 Available since 1.0.0.
				 *
				 * @param Objects $instance The import object.
				 */
				do_action( 'cfprop_import_object_errors', $instance );

				// report the errors.
				$process_handler->set_message( $this->get_error_dialog_config() );
			} else {
				/**
				 * Run additional tasks after successfully import of objects.
				 *
				 * @since 1.0.0 Available since 1.0.0.
				 *
				 * @param Objects $instance The import object.
				 */
				do_action( 'cfprop_import_object_success', $instance );

				// report the success.
				$process_handler->set_message( $this->get_success_dialog_config() );
			}
		} catch ( \Throwable $e ) {
			// log this event.
			Log::get_instance()->add( __( 'Following error occurred during the import of objects via API v2:', 'connector-for-propstack' ) . '<br>' . __( 'Message:', 'connector-for-propstack' ) . '<code>' . $e->getMessage() . '</code><br>' . __( 'Code:', 'connector-for-propstack' ) . '<code>' . $e->getCode() . '</code><br>' . __( 'File:', 'connector-for-propstack' ) . '<code>' . $e->getFile() . '</code><br>' . __( 'Line:', 'connector-for-propstack' ) . '<code>' . $e->getLine() . '</code>', 'error', 'import' );

			// show hint.
			/* translators: %1$s will be replaced by a URL. */
			$this->add_error( 'propstack_object_import_error', sprintf( __( 'Error occurred. Check <a href="%1$s">the log</a> for details.', 'connector-for-propstack' ), esc_url( Settings::get_instance()->get_url( 'propstack_connector_logs' ) ) ) );
		} finally {

			/**
			 * Run additional tasks after any import of objects.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 *
			 * @param Objects $instance The import object.
			 */
			do_action( 'cfprop_import_object_after', $instance );

			// log the errors.
			$this->save_errors_in_log();

			// add a log entry if debug is enabled.
			if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
				Log::get_instance()->add( __( 'Import of objects has been ended.', 'connector-for-propstack' ), 'info', 'import' );
			}

			// update the running marker.
			$process_handler->set_running( 0 );
			update_option( CFPROP_IMPORT_RUNNING, 0 );
		}
	}

	/**
	 * Return the API URL to import objects.
	 *
	 * @param string $language_code The language to use for the URL.
	 *
	 * @return string
	 */
	private function get_url( string $language_code ): string {
		// get the URL.
		$url = add_query_arg(
			array(
				'locale'   => $language_code,
				'expand'   => 1,
				'archived' => -1,
				'per'      => 500,
			),
			$this->url
		);

		/**
		 * Filter the URL of the API to import objects from Propstack.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param string $url The URL.
		 */
		return apply_filters( 'cfprop_api_object_url', $url );
	}

	/**
	 * Return a success dialog configuration.
	 *
	 * @return array<string,mixed>
	 */
	private function get_success_dialog_config(): array {
		return array(
			'detail' => array(
				'className' => 'cfprop-dialog',
				'title'     => __( 'Import of objects has been run', 'connector-for-propstack' ),
				'texts'     => array(
					'<p><strong>' . __( 'The import of objects from your Propstack account has been run.', 'connector-for-propstack' ) . '</strong></p>',
					'<p>' . __( 'You will find them in the list in the backend and your frontend.', 'connector-for-propstack' ) . '</p>',
					'<p>' . __( 'Please note that any files for the objects are imported later. However, you can also trigger their import directly from the object itself.', 'connector-for-propstack' ) . '</p>',
				),
				'buttons'   => array(
					array(
						'action'  => 'location.reload();',
						'variant' => 'primary',
						'text'    => __( 'OK', 'connector-for-propstack' ),
					),
				),
			),
		);
	}

	/**
	 * Set max count.
	 *
	 * @param ProcessHandler $process_handler The process handler.
	 * @param int            $count The new max count.
	 *
	 * @return void
	 */
	private function set_max_count( ProcessHandler $process_handler, int $count ): void {
		$process_handler->set_max_count( $count );

		/**
		 * Run additional tasks after setting the max count.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param int $count The max count.
		 */
		do_action( 'cfprop_import_object_set_max_count', $count );
	}

	/**
	 * Set count.
	 *
	 * @param ProcessHandler $process_handler The process handler.
	 * @param int            $count The new count.
	 *
	 * @return void
	 */
	private function set_count( ProcessHandler $process_handler, int $count ): void {
		$process_handler->set_count( $count );

		/**
		 * Run additional tasks after setting the max count.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param int $count The max count.
		 */
		do_action( 'cfprop_import_object_set_count', $count );
	}

	/**
	 * Set a new status (text).
	 *
	 * @param ProcessHandler $process_handler The process handler.
	 * @param string         $new_status The new status.
	 *
	 * @return void
	 */
	private function set_new_status( ProcessHandler $process_handler, string $new_status ): void {
		$process_handler->set_status( $new_status );

		/**
		 * Run additional tasks after setting the new status.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param string $new_status The new status.
		 */
		do_action( 'cfprop_import_object_set_status', $new_status );
	}
}

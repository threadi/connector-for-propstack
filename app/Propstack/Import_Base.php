<?php
/**
 * File to handle basic import functions.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Db;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Languages;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Plugin\ProcessHandler;
use ConnectorForPropstack\Plugin\Settings;
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
	 * The option which holds the objects which have not been imported during the last import.
	 *
	 * @var string
	 */
	protected string $prevented_objects_option = 'cfprop_prevented_objects';

	/**
	 * The objects which have not been imported in this run, as they are not saved yet.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $prevented_objects = array();

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
	 * The option which holds the object a paginated import is processing right now.
	 *
	 * @var string
	 */
	protected string $current_object_option = 'cfprop_import_current_object';

	/**
	 * The time (as Unix timestamp with microseconds) after which this import must not start with another object (0 for no limit).
	 *
	 * @var float
	 */
	private float $deadline = 0.0;

	/**
	 * Marker whether a fatal error during this request has already been handled.
	 *
	 * @var bool
	 */
	private static bool $fatal_error_handled = false;

	/**
	 * The amount of fatal errors in a row without progress after which a paginated import is discarded.
	 *
	 * @var int
	 */
	private const MAX_FATAL_ERRORS_WITHOUT_PROGRESS = 3;

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
	 * Return the languages the import of objects requests from Propstack.
	 *
	 * @return array<string,int>
	 */
	public function get_import_languages(): array {
		// get the configured language or the fallback language.
		$languages = array( Languages::get_instance()->get_import_language() => 1 );

		/** This filter is documented in app/Propstack/Imports/v1/Objects.php */
		$languages = apply_filters( 'cfprop_import_object_languages', $languages );

		// bail if the filter did not return a list.
		if ( ! is_array( $languages ) ) { // @phpstan-ignore function.alreadyNarrowedType
			return array();
		}

		// return the languages.
		return $languages;
	}

	/**
	 * Load the objects of a language page by page.
	 *
	 * Must be overridden by each import which requests objects from the API.
	 *
	 * @param string $language_code The language to load.
	 *
	 * @return \Generator<int,array<int,mixed>>
	 */
	protected function get_object_pages( string $language_code ): \Generator { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Used by the imports which override this.
		yield from array();
	}

	/**
	 * Bring an object from the API into the structure the import expects.
	 *
	 * Could be overridden by an import whose API delivers another structure.
	 *
	 * @param array<string,mixed> $immo_object   The object data from API.
	 * @param string              $language_code The used language.
	 *
	 * @return array<string,mixed>
	 */
	protected function prepare_object( array $immo_object, string $language_code ): array {
		return $immo_object;
	}

	/**
	 * Return the API URL to request a single object by its Propstack-ID.
	 *
	 * Must be overridden by each import which requests objects from the API.
	 *
	 * @param string $object_id The Propstack-ID of the object.
	 *
	 * @return string
	 */
	protected function get_single_object_url( string $object_id ): string {
		return '';
	}

	/**
	 * Check whether the API delivers a specific object for the import.
	 *
	 * This uses the same request as the import, including every restriction which is sent to the API.
	 * Nothing is imported or changed. The check looks at the raw answer of the API, so objects which
	 * are removed afterwards via hook are still found.
	 *
	 * If the object is searched by its Propstack-ID, the API is asked for this single object first, which
	 * needs one request only. All pages are requested like the import does it if the API does not
	 * support this, if another field is used or if a full scan is requested. Only a full scan
	 * detects objects which get lost between two pages.
	 *
	 * Errors during the requests are added to this object, the result is not reliable then.
	 *
	 * @param string $value         The value to search for.
	 * @param string $language_code The language to check.
	 * @param string $field         The field of the API which must contain the value ("id", "unit_id" or "exposee_id").
	 * @param bool   $full_scan     True to request all pages in any case.
	 *
	 * @return array{found:bool,object:array<string,mixed>,checked:int,full_scan:bool,url:string}
	 */
	public function find_object( string $value, string $language_code, string $field = 'id', bool $full_scan = false ): array {
		try {
			return $this->find_object_in_pages( $value, $language_code, $field, $full_scan );
		} catch ( \Throwable $e ) {
			// log this event.
			Log::get_instance()->add( __( 'Following error occurred during the check of an object:', 'connector-for-propstack' ) . '<br>' . Helper::get_throwable_as_log_text( $e ), 'error', 'import' );

			// add the error, the result is not reliable.
			$this->add_error( 'propstack_object_find_error', __( 'An error occurred during the check of the object.', 'connector-for-propstack' ) );

			// return an empty result.
			return array(
				'found'     => false,
				'object'    => array(),
				'checked'   => 0,
				'full_scan' => $full_scan,
				'url'       => '',
			);
		}
	}

	/**
	 * Check whether the API delivers a specific object for the import, see find_object().
	 *
	 * @param string $value         The value to search for.
	 * @param string $language_code The language to check.
	 * @param string $field         The field of the API which must contain the value ("id", "unit_id" or "exposee_id").
	 * @param bool   $full_scan     True to request all pages in any case.
	 *
	 * @return array{found:bool,object:array<string,mixed>,checked:int,full_scan:bool,url:string}
	 */
	private function find_object_in_pages( string $value, string $language_code, string $field, bool $full_scan ): array {
		// the API can be asked for a single object only by its Propstack-ID.
		if ( ! $full_scan && 'id' === $field && ctype_digit( $value ) ) {
			// remember the errors so far to be able to drop the ones of this attempt.
			$errors = $this->errors;

			// ask the API for this single object.
			$result = $this->search_object_in_pages( $value, $language_code, $field, true );

			// use the result if the object has been found, or if the API answered without any error and with nothing else.
			if ( $result['found'] || ( ! $result['others'] && count( $this->errors ) === count( $errors ) ) ) {
				unset( $result['others'] );
				return $result;
			}

			// the API did not limit its answer to this object, so check all pages instead.
			$this->errors = $errors;
		}

		// check all pages like the import does it.
		$result = $this->search_object_in_pages( $value, $language_code, $field, false );
		unset( $result['others'] );

		// return the result.
		return $result;
	}

	/**
	 * Search for an object in the pages the API delivers for the import.
	 *
	 * @param string $value          The value to search for.
	 * @param string $language_code  The language to check.
	 * @param string $field          The field of the API which must contain the value.
	 * @param bool   $with_id_filter True to ask the API only for the object with this Propstack-ID.
	 *
	 * @return array{found:bool,object:array<string,mixed>,checked:int,full_scan:bool,url:string,others:bool}
	 */
	private function search_object_in_pages( string $value, string $language_code, string $field, bool $with_id_filter ): array {
		// prepare the result.
		$result = array(
			'found'     => false,
			'object'    => array(),
			'checked'   => 0,
			'full_scan' => ! $with_id_filter,
			'url'       => '',
			'others'    => false,
		);

		// add the ID to each request if requested, and remember the first URL which is requested.
		$first_url  = '';
		$url_filter = static function ( string $url ) use ( $value, $with_id_filter, &$first_url ): string {
			if ( $with_id_filter ) {
				$url = add_query_arg( array( 'property_ids' => $value ), $url );
			}

			if ( '' === $first_url ) {
				$first_url = $url;
			}

			return $url;
		};
		add_filter( 'cfprop_api_object_url', $url_filter, PHP_INT_MAX );

		// search in each page, the requests stop as soon as the object has been found.
		try {
			foreach ( $this->get_object_pages( $language_code ) as $page_objects ) {
				foreach ( $page_objects as $immo_object ) {
					// bail if this is not an object.
					if ( ! is_array( $immo_object ) ) {
						continue;
					}

					++$result['checked'];

					// get the value of the field, the API delivers some of them as a list with the value in "value".
					$field_value = $immo_object[ $field ] ?? '';
					if ( is_array( $field_value ) ) {
						$field_value = $field_value['value'] ?? '';
					}

					// bail if this is another object.
					if ( ! is_scalar( $field_value ) || (string) $field_value !== $value ) {
						$result['others'] = true;
						continue;
					}

					// we found the object.
					$result['found']  = true;
					$result['object'] = $this->prepare_object( $immo_object, $language_code );

					break 2;
				}

				// stop if the API did not limit its answer to the requested object, all pages are checked afterwards.
				if ( $with_id_filter && $result['others'] ) {
					break;
				}
			}
		} finally {
			remove_filter( 'cfprop_api_object_url', $url_filter, PHP_INT_MAX );
		}

		// add the requested URL.
		$result['url'] = $first_url;

		// return the result.
		return $result;
	}

	/**
	 * Request a single object by its Propstack-ID directly from the API, without any restriction.
	 *
	 * This tells whether Propstack knows the object at all if it is not part of the objects for the import.
	 *
	 * @param string $object_id The Propstack-ID of the object.
	 *
	 * @return array{http_status:int,object:array<string,mixed>}
	 */
	public function request_single_object( string $object_id ): array {
		// prepare the result.
		$result = array(
			'http_status' => 0,
			'object'      => array(),
		);

		// bail if this import does not support the request of a single object.
		$url = $this->get_single_object_url( $object_id );
		if ( '' === $url ) {
			return $result;
		}

		// request the object.
		$request_object = new ApiRequest();
		$request_object->set_url( $url );
		$request_object->set_post_data( '' );
		$request_object->set_method( 'GET' );
		$request_object->set_md5( md5( $url ) );
		$request_object->set_header( $this->get_header() );
		$request_object->send();

		// add the HTTP status.
		$result['http_status'] = $request_object->get_http_status();

		// bail if the API did not deliver the object.
		if ( 200 !== $result['http_status'] ) {
			return $result;
		}

		// decode.
		$data = json_decode( $request_object->get_response(), true );
		if ( ! is_array( $data ) ) {
			return $result;
		}

		// add the object, with or without a surrounding "data".
		$result['object'] = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : $data;

		// return the result.
		return $result;
	}

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
	 * Handle an object which is not imported as a restriction prevents it.
	 *
	 * The object is written to the log with the reasons, so it is visible why it is missing.
	 * It is also remembered for the list of objects which have not been imported.
	 *
	 * @param array<string,mixed> $immo_object The object data from API.
	 *
	 * @return void
	 */
	protected function handle_prevented_object( array $immo_object ): void {
		// get the title, the API v1 delivers it as a field with label and value.
		$title = $immo_object['title'] ?? '';
		if ( is_array( $title ) ) {
			$title = $title['value'] ?? '';
		}

		// use the name if no title is given.
		if ( ! is_scalar( $title ) || '' === (string) $title ) {
			$title = $immo_object['name'] ?? '';
		}
		if ( ! is_scalar( $title ) ) {
			$title = '';
		}

		// get the Propstack-ID.
		$object_id = isset( $immo_object['id'] ) && is_scalar( $immo_object['id'] ) ? absint( $immo_object['id'] ) : 0;

		// get the reasons.
		$reasons = ImmoObjects::get_instance()->get_prevent_import_reasons( $immo_object );

		// use a general hint if the import is prevented by an unknown check.
		if ( empty( $reasons ) ) {
			$reasons = array( __( 'A custom restriction prevents the import.', 'connector-for-propstack' ) );
		}

		// remember the object for the list of objects which have not been imported.
		$this->prevented_objects[] = array(
			'id'      => $object_id,
			'title'   => (string) $title,
			'reasons' => $reasons,
		);

		// log this entry in any case, also if debug is disabled or limited to other categories.
		$log_in_any_case = static fn(): bool => true;
		add_filter( 'cfprop_log_without_debug', $log_in_any_case );
		add_filter( 'cfprop_log_with_debug', $log_in_any_case );

		// add the log entry.
		Log::get_instance()->add(
			sprintf(
				/* translators: %1$s will be replaced by the object title, %2$d by its Propstack-ID. */
				__( 'Import of object %1$s (Propstack-ID %2$d) prevented.', 'connector-for-propstack' ),
				'<em>' . esc_html( (string) $title ) . '</em>',
				$object_id
			) . ' ' . esc_html( implode( ' ', $reasons ) ),
			'info',
			'import'
		);

		// the following entries are logged as configured again.
		remove_filter( 'cfprop_log_without_debug', $log_in_any_case );
		remove_filter( 'cfprop_log_with_debug', $log_in_any_case );
	}

	/**
	 * Return the amount of objects which have not been imported in this run and are not saved yet.
	 *
	 * @return int
	 */
	protected function get_prevented_objects_count(): int {
		return count( $this->prevented_objects );
	}

	/**
	 * Save the objects which have not been imported in this run.
	 *
	 * The list belongs to a single import. It replaces the list of the import before, unless
	 * it is extended by a later chunk of the same import.
	 *
	 * @param int  $run_id The ID of the import run.
	 * @param bool $append True to add the objects to the list of this import run.
	 *
	 * @return void
	 */
	protected function save_prevented_objects( int $run_id, bool $append = false ): void {
		// get the objects of this run.
		$objects = $this->prevented_objects;

		// add them to the objects which are already saved for this import run.
		if ( $append ) {
			$saved = get_option( $this->prevented_objects_option, array() );
			if ( is_array( $saved ) && isset( $saved['run_id'], $saved['objects'] ) && absint( $saved['run_id'] ) === $run_id && is_array( $saved['objects'] ) ) {
				$objects = array_merge( $saved['objects'], $objects );
			}
		}

		// save the list, it is not needed on every request.
		update_option(
			$this->prevented_objects_option,
			array(
				'run_id'  => $run_id,
				'objects' => $objects,
			),
			false
		);

		// the objects of this run are saved now.
		$this->prevented_objects = array();
	}

	/**
	 * Return the texts for the dialog which name the result of an import.
	 *
	 * @param int $imported  The amount of imported objects.
	 * @param int $prevented The amount of objects which have not been imported because of a restriction.
	 *
	 * @return array<int,string>
	 */
	protected function get_result_texts( int $imported, int $prevented ): array {
		// prepare the list.
		$texts = array();

		// add the imported objects.
		if ( $imported > 0 ) {
			/* translators: %1$d will be replaced by the amount of objects. */
			$texts[] = '<p>' . sprintf( _n( '%1$d object has been imported.', '%1$d objects have been imported.', $imported, 'connector-for-propstack' ), $imported ) . ' ' . __( 'You will find them in the list in the backend and your frontend.', 'connector-for-propstack' ) . '</p>';
		}

		// bail if every object has been imported.
		if ( $prevented <= 0 ) {
			return $texts;
		}

		/* translators: %1$s will be replaced by a URL. */
		$hint = '<br><span class="cfprop-pro-hint">' . sprintf( __( 'With <a href="%1$s">Connector for Propstack Pro</a>, you will receive a list of the relevant objects.', 'connector-for-propstack' ), esc_url( Helper::get_pro_url() ) ) . '</span> ' . sprintf( __( 'Alternatively, you can <a href="%1$s">enable debug mode for imports</a> and then check the log after the next import.', 'connector-for-propstack' ), esc_url( Settings::get_instance()->get_url( 'propstack_connector_advanced', 'propstack_connector_advanced_plugin_settings' ) ) );
		if ( 1 === absint( get_option( 'propstack_connector_debug' ) ) ) {
			/* translators: %1$s will be replaced by a URL. */
			$hint = '<br>' . sprintf( __( 'You will find the reasons in <a href="%1$s">the log</a>.', 'connector-for-propstack' ), esc_url( Settings::get_instance()->get_url( 'propstack_connector_logs' ) ) );
		}

		/**
		 * Filter the hint where the reasons for objects which have not been imported can be found.
		 *
		 * @since 2.0.1 Available since 2.0.1.
		 *
		 * @param string $hint      The hint, HTML is allowed.
		 * @param int    $prevented The amount of objects which have not been imported.
		 */
		$hint = (string) apply_filters( 'cfprop_import_prevented_hint', $hint, $prevented );

		// add the objects which have not been imported.
		/* translators: %1$d will be replaced by the amount of objects, %2$s with an URL. */
		$texts[] = '<p>' . sprintf( _n( '%1$d object has not been imported because of <a href="%2$s">your restrictions</a>.', '%1$d objects have not been imported because of <a href="%2$s">your restrictions</a>.', $prevented, 'connector-for-propstack' ), $prevented, Settings::get_instance()->get_url( 'propstack_connector_objects', 'propstack_connector_import_restrictions' ) ) . ' ' . $hint . '</p>';

		// return the resulting list.
		return $texts;
	}

	/**
	 * Remove every block of the work list of a paginated import.
	 *
	 * The blocks are searched by their name and not by the amount in the metadata. An import
	 * which is aborted while its list is built has no metadata yet, its blocks would be left
	 * behind. Such a block lets the next import fail, as an unchanged block is not saved again.
	 *
	 * @return void
	 */
	protected function remove_blocks(): void {
		global $wpdb;

		// get the names of all blocks.
		$options = Db::get_instance()->get_results(
			$wpdb->prepare(
				'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE %s',
				$wpdb->esc_like( $this->work_list_option . '_block_' ) . '%'
			)
		);

		// delete them via WordPress, so the object cache is cleaned, too.
		foreach ( $options as $option ) {
			// bail if the name is missing.
			if ( ! is_array( $option ) || ! isset( $option['option_name'] ) ) {
				continue;
			}

			delete_option( (string) $option['option_name'] );
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
		$created                     = max( 0, (int) end( $parts ) );
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
		if ( ( time() - max( 0, (int) end( $parts ) ) ) < MINUTE_IN_SECONDS ) {
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

		// bail if the fatal error has already been handled by another instance in this request.
		if ( self::$fatal_error_handled ) {
			return;
		}

		// bail if import already finished cleanly.
		if ( 0 === absint( get_option( CFPROP_IMPORT_RUNNING, 0 ) ) ) {
			return;
		}

		// mark the fatal error as handled.
		self::$fatal_error_handled = true;

		// log this event.
		Log::get_instance()->add(
			__( 'Import was aborted by a fatal PHP error:', 'connector-for-propstack' ) . '<br><code>' . esc_html( $error['message'] ) . '</code> ' . esc_html( $error['file'] ) . ':' . absint( $error['line'] ),
			'error',
			'import'
		);

		// keep the state of an import whose work list is complete: the next run continues with it
		// and skips the object which caused the error, instead of starting from the beginning again.
		$import_data = get_option( $this->work_list_option, array() );
		if ( is_array( $import_data ) && isset( $import_data['blocks'], $import_data['run_id'] ) ) {
			// get the position the import has reached, to detect whether it progresses since the last fatal error.
			$position = $this->get_fatal_error_position( $import_data );

			// count the fatal errors at this position, the counter starts again if the import has progressed.
			$fatal_count = isset( $import_data['fatal_count'], $import_data['fatal_offset'] ) && $position === $import_data['fatal_offset'] ? absint( $import_data['fatal_count'] ) + 1 : 1;

			// discard the state if the import does not progress, as every continuation would abort again.
			if ( $fatal_count >= self::MAX_FATAL_ERRORS_WITHOUT_PROGRESS ) {
				// log this event.
				Log::get_instance()->add(
					/* translators: %1$d will be replaced by a number. */
					sprintf( __( 'The import has been aborted %1$d times in a row by a fatal PHP error without any progress. It will not be continued. Please check the log and start the import again.', 'connector-for-propstack' ), $fatal_count ),
					'error',
					'import'
				);

				// reset the running-flag and the state so the user is not stuck.
				update_option( CFPROP_IMPORT_RUNNING, 0 );
				$this->clear_work_list();

				// do nothing more.
				return;
			}

			// save the counter with the work list.
			$import_data['fatal_count']  = $fatal_count;
			$import_data['fatal_offset'] = $position;
			update_option( $this->work_list_option, $import_data );

			// refresh the running marker, so the state is not discarded as stale.
			update_option( CFPROP_IMPORT_RUNNING, time() );

			// continue the import via WP-Cron.
			Schedules\Objects::schedule_continuation();

			// log this event.
			Log::get_instance()->add( __( 'The import will be continued automatically in a few moments.', 'connector-for-propstack' ), 'info', 'import' );

			// do nothing more.
			return;
		}

		// reset the running-flag so the user is not stuck.
		update_option( CFPROP_IMPORT_RUNNING, 0 );
		$this->clear_work_list();
	}

	/**
	 * Return the position a paginated import has reached, to detect whether it progresses between fatal errors.
	 *
	 * It consists of the phase, the position in the work list and the object which has been processed as last
	 * (which is also the object deleted as last during the cleanup).
	 *
	 * @param array<string,mixed> $import_data The work list data.
	 *
	 * @return string
	 */
	private function get_fatal_error_position( array $import_data ): string {
		// get the object which has been processed as last.
		$marker   = get_option( $this->current_object_option, array() );
		$position = is_array( $marker ) && isset( $marker['phase'], $marker['position'] ) && is_scalar( $marker['phase'] ) ? $marker['phase'] . '-' . absint( $marker['position'] ) : '';

		// get the phase.
		$phase = isset( $import_data['phase'] ) && is_scalar( $import_data['phase'] ) ? (string) $import_data['phase'] : '';

		// return the combined position.
		return $phase . ':' . absint( get_option( $this->offset_option, 0 ) ) . ':' . $position;
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
		// delete every block.
		$this->remove_blocks();

		// reset the metadata and the position.
		update_option( $this->work_list_option, array() );
		update_option( $this->offset_option, 0 );

		// forget the object which has been processed.
		$this->clear_current_object();
	}

	/**
	 * Set the time after which this import must not start with another object.
	 *
	 * Used by runs with a limited time, e.g. via WP-Cron. The run stops after the object it
	 * processes when the time is reached and asks for another run via has_load_more().
	 *
	 * @param float $deadline The time as Unix timestamp with microseconds (0 for no limit).
	 *
	 * @return void
	 */
	public function set_deadline( float $deadline ): void {
		$this->deadline = max( 0.0, $deadline );
	}

	/**
	 * Return the time after which this import must not start with another object (0 for no limit).
	 *
	 * @return float
	 */
	public function get_deadline(): float {
		return $this->deadline;
	}

	/**
	 * Return whether the time for this run is used up.
	 *
	 * @return bool
	 */
	protected function is_deadline_reached(): bool {
		return $this->deadline > 0 && microtime( true ) >= $this->deadline;
	}

	/**
	 * Remember the object a paginated import is processing right now.
	 *
	 * If the request is aborted (e.g. by the memory or time limit), the next run knows the
	 * object which caused it and skips it.
	 *
	 * @param int    $run_id   The ID of the import run.
	 * @param string $phase    The phase of the import ("import" or "cleanup").
	 * @param int    $position The position in the work list (import) or the post-ID (cleanup).
	 *
	 * @return void
	 */
	protected function mark_current_object( int $run_id, string $phase, int $position ): void {
		update_option(
			$this->current_object_option,
			array(
				'run_id'   => $run_id,
				'phase'    => $phase,
				'position' => $position,
			),
			false
		);
	}

	/**
	 * Return the object a previous run of this import was processing as last.
	 *
	 * @param int    $run_id The ID of the import run.
	 * @param string $phase  The phase of the import ("import" or "cleanup").
	 *
	 * @return int The position in the work list (import) or the post-ID (cleanup), -1 if unknown.
	 */
	protected function get_marked_object( int $run_id, string $phase ): int {
		// get the marker.
		$marker = get_option( $this->current_object_option, array() );

		// bail if it does not belong to this run and phase.
		if ( ! is_array( $marker ) || ! isset( $marker['run_id'], $marker['phase'], $marker['position'] ) || absint( $marker['run_id'] ) !== $run_id || $phase !== $marker['phase'] ) {
			return -1;
		}

		// return the position.
		return absint( $marker['position'] );
	}

	/**
	 * Forget the object a paginated import has processed as last.
	 *
	 * @return void
	 */
	protected function clear_current_object(): void {
		delete_option( $this->current_object_option );
	}

	/**
	 * Return the title of an object from the API, for log entries and status texts.
	 *
	 * The result is not escaped.
	 *
	 * @param array<string,mixed> $immo_object The object data from API.
	 *
	 * @return string
	 */
	protected function get_object_title( array $immo_object ): string {
		// get the title, the API v1 delivers it as a field with label and value.
		$title = $immo_object['title'] ?? '';
		if ( is_array( $title ) ) {
			$title = $title['value'] ?? '';
		}

		// use the name if no title is given.
		if ( ! is_scalar( $title ) || '' === (string) $title ) {
			$title = $immo_object['name'] ?? '';
		}

		// return the title.
		return is_scalar( $title ) ? (string) $title : '';
	}

	/**
	 * Handle an object whose import aborted a previous run, e.g. by the memory or time limit.
	 *
	 * The object is skipped, as it would abort every run again. The import is marked as faulty,
	 * so no object is removed by the cleanup and the next import tries it again.
	 *
	 * @param array<string,mixed> $immo_object The object data from API.
	 *
	 * @return void
	 */
	protected function handle_aborted_object( array $immo_object ): void {
		// log this event.
		Log::get_instance()->add(
			sprintf(
				/* translators: %1$s will be replaced by the object title, %2$d by its Propstack-ID. */
				__( 'The import of object %1$s (Propstack-ID %2$d) aborted the previous run, e.g. by the memory or time limit of PHP. The object has been skipped.', 'connector-for-propstack' ),
				'<em>' . esc_html( $this->get_object_title( $immo_object ) ) . '</em>',
				isset( $immo_object['id'] ) && is_scalar( $immo_object['id'] ) ? absint( $immo_object['id'] ) : 0
			),
			'error',
			'import'
		);

		// mark the import as faulty so the error dialog is shown.
		$this->add_error(
			'propstack_object_import_error',
			/* translators: %1$s will be replaced by a URL. */
			sprintf( __( 'At least one object could not be imported. Check <a href="%1$s">the log</a> for details.', 'connector-for-propstack' ), esc_url( Settings::get_instance()->get_url( 'propstack_connector_logs' ) ) )
		);
	}

	/**
	 * Handle an object whose deletion during the cleanup aborted a previous run.
	 *
	 * The object is excluded from the cleanup of this import, as it would abort every run again.
	 *
	 * @param int $post_id The post-ID of the object.
	 * @param int $run_id  The ID of the import run.
	 *
	 * @return void
	 */
	protected function handle_aborted_cleanup( int $post_id, int $run_id ): void {
		// forget the marker.
		$this->clear_current_object();

		// bail if the object has been removed.
		$post_status = get_post_status( $post_id );
		if ( false === $post_status || 'trash' === $post_status ) {
			return;
		}

		// exclude it from the cleanup of this import.
		update_post_meta( $post_id, 'changed', $run_id );

		// log this event.
		Log::get_instance()->add(
			/* translators: %1$d will be replaced by the post-ID. */
			sprintf( __( 'The deletion of the obsolete object with the post-ID %1$d aborted the previous run, e.g. by the memory or time limit of PHP. The object has been skipped.', 'connector-for-propstack' ), $post_id ),
			'error',
			'import'
		);

		// mark the import as faulty so the error dialog is shown.
		$this->add_error(
			'propstack_object_import_cleanup_error',
			/* translators: %1$s will be replaced by a URL. */
			sprintf( __( 'At least one obsolete object could not be deleted. Check <a href="%1$s">the log</a> for details.', 'connector-for-propstack' ), esc_url( Settings::get_instance()->get_url( 'propstack_connector_logs' ) ) )
		);
	}

	/**
	 * Return the meta query for objects which have been imported, but are not part of the given import run.
	 *
	 * Objects without a Propstack-ID have not been created by the import (e.g. copies of other plugins)
	 * and are never part of the cleanup.
	 *
	 * @param int $run_id The ID of the import run.
	 *
	 * @return array<int|string,mixed>
	 */
	protected function get_obsolete_objects_meta_query( int $run_id ): array {
		return array(
			'relation' => 'AND',
			array(
				'key'     => 'object_id',
				'compare' => 'EXISTS',
			),
			array(
				'relation' => 'OR',
				array(
					'key'     => 'changed',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'changed',
					'value'   => $run_id,
					'compare' => '!=',
				),
			),
		);
	}

	/**
	 * Delete an obsolete object during the cleanup of an import.
	 *
	 * Its images are deleted, too, if it is deleted permanently and the files should not be preserved.
	 * Images which are used by another object are kept. Its entries in the queue are removed.
	 *
	 * @param int  $post_id      The post-ID of the object.
	 * @param bool $force_delete True to bypass the trash.
	 * @param int  $run_id       The ID of the import run.
	 *
	 * @return bool True if the object has been deleted.
	 */
	protected function delete_obsolete_object( int $post_id, bool $force_delete, int $run_id ): bool {
		// get the images before the object is deleted, as its metadata is removed with it.
		$images = array();
		if ( $force_delete && 1 !== absint( get_option( 'propstack_connector_preserve_files' ) ) ) {
			$images = ImmoObjects::get_instance()->get_object( $post_id )->get_images();
		}

		// delete the object.
		$result = wp_delete_post( $post_id, $force_delete );

		// bail if the object could not be deleted.
		if ( ! $result instanceof \WP_Post ) {
			// exclude it from the cleanup of this import, so it is not matched again and again.
			update_post_meta( $post_id, 'changed', $run_id );

			// log this event.
			/* translators: %1$d will be replaced by the post-ID. */
			Log::get_instance()->add( sprintf( __( 'The obsolete object with the post-ID %1$d could not be deleted.', 'connector-for-propstack' ), $post_id ), 'error', 'import' );

			// return false as the object has not been deleted.
			return false;
		}

		// remove its entries from the queue.
		Queue::get_instance()->delete_entries_of_object( $post_id );

		// delete its images.
		foreach ( $images as $attachment_id ) {
			$attachment_id = absint( $attachment_id );

			// bail if this is not an attachment.
			if ( 0 === $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
				continue;
			}

			// bail if this is a broker avatar.
			if ( absint( get_post_meta( $attachment_id, 'cfprop_broker_avatar', true ) ) > 0 ) {
				continue;
			}

			// bail if another object uses this image, e.g. the same object in another language.
			if ( $this->is_image_used_by_any_object( $attachment_id ) ) {
				continue;
			}

			// delete the image including its files.
			wp_delete_attachment( $attachment_id, true );
		}

		// return true as the object has been deleted.
		return true;
	}

	/**
	 * Return whether any object has the given attachment in its list of images.
	 *
	 * @param int $attachment_id The attachment ID.
	 *
	 * @return bool
	 */
	private function is_image_used_by_any_object( int $attachment_id ): bool {
		global $wpdb;

		// search the ID in the serialized lists of images.
		$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `$wpdb->postmeta` WHERE `meta_key` = %s AND ( `meta_value` LIKE %s OR `meta_value` LIKE %s )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'images',
				'%' . $wpdb->esc_like( 'i:' . $attachment_id . ';' ) . '%',
				'%' . $wpdb->esc_like( ':"' . $attachment_id . '";' ) . '%'
			)
		);

		// return whether it has been found.
		return absint( $count ) > 0;
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

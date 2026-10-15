<?php
/**
 * File to handle the custom fields of objects from Propstack.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Fields\FieldTable;
use easySettingsForWordPress\Fields\TextInfo;
use easySettingsForWordPress\Fields\Value;
use easySettingsForWordPress\Settings as Settings_Object;
use easySettingsForWordPress\Tab;
use ConnectorForPropstack\Plugin\Cache;
use ConnectorForPropstack\Plugin\Countries;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Propstack\Fields\Custom\Propstack_Custom_Field;
use ConnectorForPropstack\Propstack\Fields\Custom\Value_Formatter;

/**
 * Object to handle the custom fields of objects from Propstack.
 *
 * In Propstack each customer could add any number of custom fields to objects. Their definitions (name, label,
 * type and options) are detected by their values during the import of objects. They are only loaded from Propstack
 * if this is enabled via the hook "cfprop_custom_fields_load_definitions" (Connector for Propstack Pro does this for
 * API v2). Their values are saved on each object with a prefix, so they could not collide with any other field:
 * - "cfprop_cf_{name}" holds the value prepared for the output (e.g. the name of a chosen option),
 * - "cfprop_cf_{name}_raw" holds the value as delivered by Propstack (e.g. the ID of a chosen option),
 * - "cfprop_custom_fields" holds the list of the custom fields of this object.
 *
 * The output of custom fields in the frontend is only possible with Connector for Propstack Pro.
 */
class Custom_Fields {
	/**
	 * The prefix for the meta keys of custom fields.
	 */
	public const META_PREFIX = 'cfprop_cf_';

	/**
	 * The suffix for the meta keys of the raw values of custom fields.
	 */
	public const RAW_SUFFIX = '_raw';

	/**
	 * The meta key for the list of custom fields of an object.
	 */
	public const LIST_META_KEY = 'cfprop_custom_fields';

	/**
	 * The meta key for the list of custom fields of an object up to version 2.0.x.
	 */
	public const OLD_LIST_META_KEY = 'custom_fields';

	/**
	 * The option, which holds the definitions of all custom fields.
	 */
	public const OPTION = 'cfprop_custom_fields';

	/**
	 * Name of the transient, which marks that Propstack refused the request for the definitions.
	 *
	 * While it exists, the definitions are not requested again during the import of single objects
	 * and the refusal is not logged again.
	 */
	public const NOT_ALLOWED_TRANSIENT = 'cfprop_custom_fields_not_allowed';

	/**
	 * The prefix for the settings of custom fields.
	 */
	public const SETTING_PREFIX = 'propstack_connector_custom_fields_';

	/**
	 * The URL of the API v1 to get the custom fields (with their groups).
	 */
	private const URL_V1 = 'https://api.propstack.de/v1/custom_field_groups';

	/**
	 * The URL of the API v2 to get the custom fields.
	 */
	private const URL_V2 = 'https://api.propstack.de/v2/custom_fields';

	/**
	 * Instance of this object.
	 *
	 * @var ?Custom_Fields
	 */
	private static ?Custom_Fields $instance = null;

	/**
	 * The definitions of the custom fields, loaded in this request.
	 *
	 * @var array<string,array<string,mixed>>|null
	 */
	private ?array $definitions = null;

	/**
	 * The custom fields as objects, created in this request.
	 *
	 * @var array<int,Propstack_Custom_Field>|null
	 */
	private ?array $fields = null;

	/**
	 * Marker whether the definitions have already been requested from Propstack in this request.
	 *
	 * @var bool
	 */
	private bool $definitions_requested = false;

	/**
	 * Constructor, not used as this a Singleton object.
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
	public static function get_instance(): Custom_Fields {
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
		// load the definitions of the custom fields before each import of objects.
		add_action( 'cfprop_import_object_before_start', array( $this, 'update_definitions' ), 5, 0 );

		// remove the custom fields, which are no longer used by any object, after a complete import (if the definitions are not loaded from Propstack).
		add_action( 'cfprop_import_object_success', array( $this, 'remove_unused_definitions' ), 10, 0 );

		// request the definitions again immediately, if the API key or the API version has been changed.
		add_action( 'update_option_propstack_connector_api_key', array( $this, 'reset_not_allowed' ), 10, 0 );
		add_action( 'add_option_propstack_connector_api_key', array( $this, 'reset_not_allowed' ), 10, 0 );
		add_action( 'update_option_propstack_connector_api_version', array( $this, 'reset_not_allowed' ), 10, 0 );

		// format the values of custom fields.
		add_filter( 'cfprop_field_type', array( $this, 'set_field_type' ), 10, 2 );

		// reset the loaded definitions, if they are changed outside of this object (e.g. by another process or in tests).
		add_action( 'add_option_' . self::OPTION, array( $this, 'reset_cache' ), 10, 0 );
		add_action( 'update_option_' . self::OPTION, array( $this, 'reset_cache' ), 10, 0 );
		add_action( 'delete_option_' . self::OPTION, array( $this, 'reset_cache' ), 10, 0 );
	}

	/**
	 * Return the types of custom fields in Propstack with their labels.
	 *
	 * @return array<string,string>
	 */
	public function get_types(): array {
		$types = array(
			'String'         => __( 'Text line', 'connector-for-propstack' ),
			'Text'           => __( 'Textarea', 'connector-for-propstack' ),
			'Number'         => __( 'Number field', 'connector-for-propstack' ),
			'Dropdown'       => __( 'Dropdown', 'connector-for-propstack' ),
			'Multiselect'    => __( 'Multiple choice', 'connector-for-propstack' ),
			'Boolean'        => __( 'Checkbox', 'connector-for-propstack' ),
			'CountrySelect'  => __( 'Country selection', 'connector-for-propstack' ),
			'Date'           => __( 'Date', 'connector-for-propstack' ),
			'DateTime'       => __( 'Date and time', 'connector-for-propstack' ),
			'Broker'         => __( 'User dropdown', 'connector-for-propstack' ),
			'Tags'           => __( 'Features', 'connector-for-propstack' ),
			'TagsByCategory' => __( 'Features by category', 'connector-for-propstack' ),
		);

		/**
		 * Filter the types of custom fields in Propstack with their labels.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,string> $types The types.
		 */
		return apply_filters( 'cfprop_custom_field_types', $types );
	}

	/**
	 * Return the label of the given type of custom fields.
	 *
	 * @param string $type The type.
	 *
	 * @return string
	 */
	public function get_type_label( string $type ): string {
		$types = $this->get_types();
		return $types[ $type ] ?? $type;
	}

	/**
	 * Return the definitions of all known custom fields.
	 *
	 * Format: the name of the field in Propstack => array with
	 * - name: the name in Propstack,
	 * - label: the label in Propstack,
	 * - type: the type in Propstack (see get_types()),
	 * - unit: the unit (e.g. for numbers, only via API v2),
	 * - options: the ID of each option => its name (for dropdowns, multiple choice and features),
	 * - guessed: true, if the definition could not be loaded from Propstack and has been guessed from the value.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_definitions(): array {
		// return the definitions, if they are already loaded.
		if ( is_array( $this->definitions ) ) {
			return $this->definitions;
		}

		// get them from the option.
		$definitions = get_option( self::OPTION, array() );
		if ( ! is_array( $definitions ) ) {
			$definitions = array();
		}

		// secure them for this request.
		$this->definitions = $definitions;

		// return them.
		return $this->definitions;
	}

	/**
	 * Return the definition of the custom field with the given name.
	 *
	 * @param string $name The name of the field in Propstack.
	 *
	 * @return array<string,mixed>|false
	 */
	public function get_definition( string $name ): array|false {
		$definitions = $this->get_definitions();
		return isset( $definitions[ $name ] ) && is_array( $definitions[ $name ] ) ? $definitions[ $name ] : false; // @phpstan-ignore booleanAnd.rightAlwaysTrue
	}

	/**
	 * Save the given definitions.
	 *
	 * @param array<string,array<string,mixed>> $definitions The definitions.
	 *
	 * @return void
	 */
	private function save_definitions( array $definitions ): void {
		// sort them by their labels.
		uasort(
			$definitions,
			static function ( array $a, array $b ): int {
				return strnatcasecmp( (string) ( $a['label'] ?? '' ), (string) ( $b['label'] ?? '' ) );
			}
		);

		// save them (autoloaded, as they are used to show the fields in the frontend).
		update_option( self::OPTION, $definitions, true );

		// secure them for this request and reset the field objects.
		$this->definitions = $definitions;
		$this->fields      = null;

		// hide each new custom field in the frontend.
		$this->add_hidden_settings( array_keys( $definitions ) );
	}

	/**
	 * Save the setting to hide the given custom fields in the frontend, if it does not exist yet.
	 *
	 * Each custom field is hidden until the user enables it. The explicit setting is necessary,
	 * as the settings page shows the saved value and not the default value.
	 *
	 * @param array<int|string,int|string> $names List of names of the fields in Propstack.
	 *
	 * @return void
	 */
	private function add_hidden_settings( array $names ): void {
		foreach ( $names as $name ) {
			// get the name of the setting.
			$setting_name = $this->get_hidden_setting_name( (string) $name );

			// bail if it already exists.
			if ( false !== get_option( $setting_name, false ) ) {
				continue;
			}

			// save it as hidden.
			add_option( $setting_name, 1, '', true );
		}
	}

	/**
	 * Clear the cache if the visibility of a custom field has been changed.
	 *
	 * Used as callback before the setting is saved, so it must return the value.
	 *
	 * @param mixed $value The new value.
	 *
	 * @return mixed
	 */
	public function clear_cache_on_visibility_change( mixed $value ): mixed {
		Cache::get_instance()->clear_cache();
		return $value;
	}

	/**
	 * Reset the definitions and field objects loaded in this request.
	 *
	 * @return void
	 */
	public function reset_cache(): void {
		$this->definitions = null;
		$this->fields      = null;
	}

	/**
	 * Return the meta key for the value of the given custom field.
	 *
	 * This is also the name of the field in the widgets and shortcodes.
	 *
	 * @param string $name The name of the field in Propstack.
	 *
	 * @return string
	 */
	public function get_meta_key( string $name ): string {
		return self::META_PREFIX . sanitize_key( $name );
	}

	/**
	 * Return the name of the setting for the visibility of the given custom field.
	 *
	 * @param string $name The name of the field in Propstack.
	 *
	 * @return string
	 */
	public function get_hidden_setting_name( string $name ): string {
		return self::SETTING_PREFIX . sanitize_key( $name ) . '_hidden';
	}

	/**
	 * Return whether the output of custom fields in the frontend is supported.
	 *
	 * This plugin does not show custom fields in the frontend. Connector for Propstack Pro does.
	 *
	 * @return bool
	 */
	public function is_output_supported(): bool {
		$supported = false;

		/**
		 * Filter whether the output of custom fields in the frontend is supported.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param bool $supported True if the output is supported.
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		return (bool) apply_filters( 'cfprop_custom_fields_output', $supported );
	}

	/**
	 * Return whether the given custom field is hidden in the frontend.
	 *
	 * Each custom field is hidden until it has been enabled in the settings.
	 *
	 * @param string $name The name of the field in Propstack.
	 *
	 * @return bool
	 */
	public function is_hidden( string $name ): bool {
		$hidden = get_option( $this->get_hidden_setting_name( $name ), 1 );
		return 1 === absint( is_scalar( $hidden ) ? $hidden : 1 );
	}

	/**
	 * Return all known custom fields as field objects.
	 *
	 * @return array<int,Propstack_Custom_Field>
	 */
	public function get_fields(): array {
		// return the list, if it is already created.
		if ( is_array( $this->fields ) ) {
			return $this->fields;
		}

		// create the list.
		$this->fields = array();
		foreach ( $this->get_definitions() as $definition ) {
			// bail if the definition has no name.
			if ( empty( $definition['name'] ) || ! is_scalar( $definition['name'] ) || '' === sanitize_key( (string) $definition['name'] ) ) {
				continue;
			}

			// add the field.
			$this->fields[] = new Propstack_Custom_Field( $definition );
		}

		// return the list.
		return $this->fields;
	}

	/**
	 * Return the custom fields for the list of all object fields.
	 *
	 * They are only part of this list if their output is supported (via Connector for Propstack Pro).
	 * Their visibility in the frontend is checked by each field itself.
	 *
	 * @return array<int,Propstack_Custom_Field>
	 */
	public function get_fields_for_output(): array {
		// bail if the output of custom fields is not supported.
		if ( ! $this->is_output_supported() ) {
			return array();
		}

		// return all custom fields.
		return $this->get_fields();
	}

	/**
	 * Return the custom fields which are enabled for the output in the frontend.
	 *
	 * @return array<int,Propstack_Custom_Field>
	 */
	public function get_visible_fields(): array {
		$fields = array();
		foreach ( $this->get_fields_for_output() as $field ) {
			if ( $field->hide_in_frontend() ) {
				continue;
			}
			$fields[] = $field;
		}
		return $fields;
	}

	/**
	 * Return the custom field object by its meta key (e.g. "cfprop_cf_my_field").
	 *
	 * @param string $meta_key The meta key, which is also the field name.
	 *
	 * @return Propstack_Custom_Field|false
	 */
	public function get_field_by_meta_key( string $meta_key ): Propstack_Custom_Field|false {
		foreach ( $this->get_fields() as $field ) {
			if ( $meta_key === $field->get_name() ) {
				return $field;
			}
		}
		return false;
	}

	/**
	 * Return the custom fields of the given object (in the order of the definitions).
	 *
	 * @param int $post_id The post-ID of the object.
	 *
	 * @return array<int,Propstack_Custom_Field>
	 */
	public function get_fields_of_object( int $post_id ): array {
		// get the list of custom fields of this object.
		$names = get_post_meta( $post_id, self::LIST_META_KEY, true );

		// bail if the object does not have any custom fields.
		if ( ! is_array( $names ) || empty( $names ) ) {
			return array();
		}

		// collect the fields.
		$fields = array();
		foreach ( $this->get_fields() as $field ) {
			if ( in_array( $field->get_propstack_name(), $names, true ) ) {
				$fields[] = $field;
			}
		}

		// return the resulting list.
		return $fields;
	}

	/**
	 * Return whether the custom fields of the given object have been saved in the format up to version 2.0.x.
	 *
	 * They will be converted during the next import of this object.
	 *
	 * @param int $post_id The post-ID of the object.
	 *
	 * @return bool
	 */
	public function has_old_format( int $post_id ): bool {
		$old_list = get_post_meta( $post_id, self::OLD_LIST_META_KEY, true );
		return is_array( $old_list ) && ! empty( $old_list ) && ! metadata_exists( 'post', $post_id, self::LIST_META_KEY );
	}

	/**
	 * Load the definitions of the custom fields from Propstack.
	 *
	 * The fields, which are no longer delivered, are removed with their settings.
	 * If Propstack does not deliver the definitions (e.g. because of missing permissions of the API key),
	 * the known definitions are used.
	 *
	 * @param bool $force True to request them, even if Propstack refused the last request (used before each import).
	 *
	 * @return void
	 */
	public function update_definitions( bool $force = true ): void {
		// mark the definitions as requested in this request.
		$this->definitions_requested = true;

		// bail if the definitions should not be loaded from Propstack.
		if ( ! $this->is_loading_of_definitions_enabled() ) {
			return;
		}

		// bail if Propstack refused the last request and this is not forced (e.g. during the import of a single object).
		if ( ! $force && false !== get_transient( self::NOT_ALLOWED_TRANSIENT ) ) {
			return;
		}

		// get the definitions from Propstack.
		$api_definitions = $this->request_definitions();

		// bail if they could not be loaded.
		if ( false === $api_definitions ) {
			return;
		}

		// remove the settings of the fields, which are no longer delivered.
		foreach ( array_keys( $this->get_definitions() ) as $name ) {
			// bail if this field is still delivered.
			if ( isset( $api_definitions[ $name ] ) ) {
				continue;
			}

			// remove its setting.
			delete_option( $this->get_hidden_setting_name( (string) $name ) );

			// log this event.
			/* translators: %1$s will be replaced by the name of the custom field. */
			Log::get_instance()->add( sprintf( __( 'The custom field %1$s is no longer delivered by Propstack and has been removed.', 'connector-for-propstack' ), '<code>' . esc_html( (string) $name ) . '</code>' ), 'info', 'import' );
		}

		// save the new definitions.
		$this->save_definitions( $api_definitions );
	}

	/**
	 * Return whether the definitions of the custom fields should be loaded from Propstack.
	 *
	 * This plugin does not load them, as it does not show custom fields in the frontend and the API key
	 * would need an additional permission for it. Without the definitions, the type of each field is guessed
	 * by its value and its label is created from its internal name. Connector for Propstack Pro loads them
	 * for API v2, which delivers only the IDs of chosen options.
	 *
	 * @return bool
	 */
	public function is_loading_of_definitions_enabled(): bool {
		// get the used API version.
		$api_version = 'v2' === get_option( 'propstack_connector_api_version' ) ? 'v2' : 'v1';

		// do not load them by default.
		$enabled = false;

		/**
		 * Filter whether the definitions of custom fields (labels, types, units and options) are loaded from Propstack before each import.
		 *
		 * The API key needs the permission to read custom fields for this.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param bool   $enabled     True to load them.
		 * @param string $api_version The used API version ("v1" or "v2").
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		return (bool) apply_filters( 'cfprop_custom_fields_load_definitions', $enabled, $api_version );
	}

	/**
	 * Remove the custom fields, which are not used by any object, with their settings.
	 *
	 * Used after a complete import if the definitions are not loaded from Propstack. Otherwise, the
	 * custom fields which are no longer delivered by Propstack are removed before each import.
	 *
	 * @return void
	 */
	public function remove_unused_definitions(): void {
		global $wpdb;

		// bail if the definitions are loaded from Propstack.
		if ( $this->is_loading_of_definitions_enabled() ) {
			return;
		}

		// get the definitions.
		$definitions = $this->get_definitions();

		// bail if no custom fields are known.
		if ( empty( $definitions ) ) {
			return;
		}

		// get the meta keys of custom fields, which are used by any object.
		$used_meta_keys = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT DISTINCT meta_key FROM ' . $wpdb->postmeta . ' WHERE meta_key LIKE %s',
				$wpdb->esc_like( self::META_PREFIX ) . '%'
			)
		);
		$used_meta_keys = array_flip( array_map( 'strval', $used_meta_keys ) );

		// remove each unused custom field.
		$changed = false;
		foreach ( array_keys( $definitions ) as $name ) {
			// bail if this field is used.
			if ( isset( $used_meta_keys[ $this->get_meta_key( (string) $name ) ] ) ) {
				continue;
			}

			// remove it with its setting.
			unset( $definitions[ $name ] );
			delete_option( $this->get_hidden_setting_name( (string) $name ) );
			$changed = true;

			// log this event.
			/* translators: %1$s will be replaced by the name of the custom field. */
			Log::get_instance()->add( sprintf( __( 'The custom field %1$s is no longer used by any object and has been removed.', 'connector-for-propstack' ), '<code>' . esc_html( (string) $name ) . '</code>' ), 'info', 'import' );
		}

		// save the definitions, if they have been changed.
		if ( $changed ) {
			$this->save_definitions( $definitions );
		}
	}

	/**
	 * Log that Propstack refused the request for the definitions, max. once per hour.
	 *
	 * @param ApiRequest $request_object The request.
	 * @param string     $url            The URL of the request.
	 *
	 * @return void
	 */
	private function log_not_allowed( ApiRequest $request_object, string $url ): void {
		// bail if this has already been logged.
		if ( false !== get_transient( self::NOT_ALLOWED_TRANSIENT ) ) {
			return;
		}

		// mark it.
		set_transient( self::NOT_ALLOWED_TRANSIENT, $request_object->get_http_status(), HOUR_IN_SECONDS );

		// collect the text for the log entry.
		/* translators: %1$s will be replaced by the HTTP status, %2$s by the URL of the request. */
		$text = sprintf( __( 'Propstack refused the request for the custom fields of objects (HTTP status %1$s, URL %2$s).', 'connector-for-propstack' ), '<code>' . $request_object->get_http_status() . '</code>', '<code>' . esc_html( $url ) . '</code>' );

		// add the message from Propstack.
		$message = $this->get_error_message( $request_object->get_response() );
		if ( '' !== $message ) {
			/* translators: %1$s will be replaced by the message from Propstack. */
			$text .= ' ' . sprintf( __( 'Answer of Propstack: %1$s', 'connector-for-propstack' ), '<code>' . esc_html( $message ) . '</code>' );
		}

		// add the explanation.
		/* translators: %1$s will be replaced by a URL. */
		$text .= '<br><br>' . sprintf( __( 'The values of the custom fields are imported anyway, but without their labels and options, unless they are already known from an earlier request. Please check whether your API key is allowed to read custom fields in the <a href="%1$s" target="_blank">settings of your API keys in Propstack</a>. This hint is shown max. once per hour.', 'connector-for-propstack' ), esc_url( Helper::get_propstack_api_page_url() ) );

		// add the log entry.
		Log::get_instance()->add( $text, 'error', 'import' );
	}

	/**
	 * Return the error message from the given response of Propstack.
	 *
	 * @param string $response The response.
	 *
	 * @return string
	 */
	private function get_error_message( string $response ): string {
		// get the message from a JSON response.
		$data = json_decode( $response, true );
		if ( is_array( $data ) ) {
			foreach ( array( 'error', 'message', 'errors' ) as $key ) {
				if ( isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) && '' !== (string) $data[ $key ] ) {
					$response = (string) $data[ $key ];
					break;
				}
				if ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
					$response = implode( ', ', array_filter( array_map( static fn( $value ): string => is_scalar( $value ) ? (string) $value : '', $data[ $key ] ) ) );
					break;
				}
			}
		}

		// return it without HTML and limited in length.
		$response = trim( wp_strip_all_tags( $response ) );
		return strlen( $response ) > 300 ? substr( $response, 0, 300 ) . ' …' : $response;
	}

	/**
	 * Remove the marker that Propstack refused the request for the definitions.
	 *
	 * @return void
	 */
	public function reset_not_allowed(): void {
		delete_transient( self::NOT_ALLOWED_TRANSIENT );
	}

	/**
	 * Request the definitions of the custom fields of objects from Propstack.
	 *
	 * @return array<string,array<string,mixed>>|false
	 */
	private function request_definitions(): array|false {
		// get the used API version.
		$api_version = 'v2' === get_option( 'propstack_connector_api_version' ) ? 'v2' : 'v1';

		// get the URL (both API versions deliver the custom fields of objects with the same parameter).
		$url = add_query_arg(
			array(
				'entity' => 'for_properties',
			),
			'v2' === $api_version ? self::URL_V2 : self::URL_V1
		);

		/**
		 * Filter the URL of the API to get the definitions of custom fields from Propstack.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param string $url         The URL.
		 * @param string $api_version The used API version ("v1" or "v2").
		 */
		$url = apply_filters( 'cfprop_api_custom_fields_url', $url, $api_version );

		// create and send the API request.
		$request_object = new ApiRequest();
		$request_object->set_url( $url );
		$request_object->set_post_data( '' );
		$request_object->set_method( 'GET' );
		$request_object->set_md5( md5( $url ) );
		$request_object->set_header(
			array(
				'X-API-KEY'    => (string) get_option( 'propstack_connector_api_key' ),
				'Content-Type' => 'application/json',
			)
		);
		$request_object->send();

		// handle the refusal of the request.
		if ( in_array( $request_object->get_http_status(), array( 401, 403 ), true ) ) {
			$this->log_not_allowed( $request_object, $url );
			return false;
		}

		// bail on error.
		if ( 200 !== $request_object->get_http_status() ) {
			return false;
		}

		// the request is allowed (again).
		$this->reset_not_allowed();

		// convert the response to an array.
		$data = json_decode( $request_object->get_response(), true );

		// bail if the response is not usable.
		if ( ! is_array( $data ) || ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return false;
		}

		// collect the fields (API v1 delivers them in their groups, API v2 as list).
		$fields = array();
		foreach ( $data['data'] as $entry ) {
			// bail if the entry is not an array.
			if ( ! is_array( $entry ) ) {
				continue;
			}

			// use the fields of a group.
			if ( isset( $entry['custom_fields'] ) && is_array( $entry['custom_fields'] ) ) {
				foreach ( $entry['custom_fields'] as $field ) {
					if ( is_array( $field ) ) {
						$fields[] = $field;
					}
				}
				continue;
			}

			// use the field.
			$fields[] = $entry;
		}

		// convert them to our definitions.
		$definitions = array();
		foreach ( $fields as $field ) {
			// get the name.
			$name = isset( $field['name'] ) && is_scalar( $field['name'] ) ? (string) $field['name'] : '';

			// bail if the name is not usable.
			if ( '' === sanitize_key( $name ) ) {
				continue;
			}

			// collect the options.
			$options = array();
			if ( isset( $field['custom_options'] ) && is_array( $field['custom_options'] ) ) {
				foreach ( $field['custom_options'] as $option ) {
					if ( is_array( $option ) && isset( $option['id'], $option['name'] ) && is_scalar( $option['id'] ) && is_scalar( $option['name'] ) ) {
						$options[ (string) $option['id'] ] = (string) $option['name'];
					}
				}
			}

			// add the definition.
			$definitions[ $name ] = array(
				'name'    => $name,
				'label'   => isset( $field['pretty_name'] ) && is_scalar( $field['pretty_name'] ) && '' !== (string) $field['pretty_name'] ? (string) $field['pretty_name'] : $this->get_label_by_name( $name ),
				'type'    => isset( $field['field_type'] ) && is_scalar( $field['field_type'] ) ? (string) $field['field_type'] : 'String',
				'unit'    => isset( $field['unit'] ) && is_scalar( $field['unit'] ) ? (string) $field['unit'] : '',
				'options' => $options,
				'guessed' => ! isset( $field['field_type'] ) || ! is_scalar( $field['field_type'] ) || ! array_key_exists( (string) $field['field_type'], $this->get_types() ),
			);
		}

		/**
		 * Filter the definitions of custom fields loaded from Propstack.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,array<string,mixed>> $definitions The definitions.
		 * @param string                            $api_version The used API version.
		 */
		return apply_filters( 'cfprop_custom_field_definitions', $definitions, $api_version );
	}

	/**
	 * Return a label for a custom field by its name (e.g. "My field" for "my_field").
	 *
	 * @param string $name The name of the field in Propstack.
	 *
	 * @return string
	 */
	private function get_label_by_name( string $name ): string {
		return ucfirst( trim( str_replace( '_', ' ', $name ) ) );
	}

	/**
	 * Return the values of the custom fields of an object for its import.
	 *
	 * The values are saved together with all other field values of this object. For existing objects
	 * the values of custom fields, which are no longer delivered or empty, are removed here. Values in
	 * the format up to version 2.0.x are removed, too.
	 *
	 * @param array<string,mixed> $immo_object   The object data from API.
	 * @param int                 $post_id       The post-ID of the object.
	 * @param bool                $is_new_object True if this is a new object.
	 * @param array<string,mixed> $values        The values of the other fields of this object (they are never removed).
	 *
	 * @return array<string,mixed>
	 */
	public function get_values_for_import( array $immo_object, int $post_id, bool $is_new_object, array $values ): array {
		// bail if the object data does not contain custom fields (e.g. if they are not part of the response).
		if ( ! isset( $immo_object['custom_fields'] ) || ! is_array( $immo_object['custom_fields'] ) ) {
			return array();
		}

		// prepare the resulting list.
		$result = array();

		// prepare the list of names of the custom fields of this object.
		$names = array();

		// prepare the list of meta keys to remove.
		$remove = array();

		foreach ( $immo_object['custom_fields'] as $name => $entry ) {
			// get the name as string.
			$name = (string) $name;

			// bail if the name is not usable.
			if ( '' === sanitize_key( $name ) ) {
				continue;
			}

			// get the definition of this field.
			$definition = $this->get_definition_for_import( $name, $entry );

			// load the definitions again, if the value contains an option which is unknown (e.g. a new option in Propstack).
			if ( ! $this->definitions_requested && $this->has_unknown_options( $definition, $entry ) ) {
				$this->update_definitions( false );
				$definition = $this->get_definition_for_import( $name, $entry );
			}

			// get the meta key.
			$meta_key = $this->get_meta_key( $name );

			// get the values.
			$prepared = $this->prepare_value( $definition, $entry );

			// remove the values, if the field is empty.
			if ( is_null( $prepared ) ) {
				$remove[] = $meta_key;
				$remove[] = $meta_key . self::RAW_SUFFIX;
				continue;
			}

			// add the field to the list.
			$names[] = $name;

			// add its values.
			$result[ $meta_key ]                    = $prepared['value'];
			$result[ $meta_key . self::RAW_SUFFIX ] = $prepared['raw'];
		}

		// add the list of custom fields of this object.
		$result[ self::LIST_META_KEY ] = $names;

		// remove values of existing objects, which are no longer used.
		if ( ! $is_new_object ) {
			// get the custom fields of the last import of this object.
			$previous_names = get_post_meta( $post_id, self::LIST_META_KEY, true );
			if ( is_array( $previous_names ) ) {
				foreach ( $previous_names as $previous_name ) {
					// bail if this field has a value now.
					if ( ! is_scalar( $previous_name ) || in_array( (string) $previous_name, $names, true ) ) {
						continue;
					}

					// remove its values.
					$remove[] = $this->get_meta_key( (string) $previous_name );
					$remove[] = $this->get_meta_key( (string) $previous_name ) . self::RAW_SUFFIX;
				}
			}

			// remove the values.
			foreach ( array_unique( $remove ) as $meta_key ) {
				delete_post_meta( $post_id, $meta_key );
			}

			// remove the values in the old format.
			$this->remove_old_format( $post_id, $values );
		}

		/**
		 * Filter the values of the custom fields of an object during its import.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,mixed> $result      The values: the meta key => the value.
		 * @param array<string,mixed> $immo_object The object data from API.
		 * @param int                 $post_id     The post-ID of the object.
		 */
		return apply_filters( 'cfprop_custom_fields_import_values', $result, $immo_object, $post_id );
	}

	/**
	 * Remove the values of custom fields of an object in the format up to version 2.0.x.
	 *
	 * They have been saved with the name of the field in Propstack as meta key (without prefix).
	 * Meta keys, which are used by any field of this plugin, are not removed.
	 *
	 * @param int                 $post_id The post-ID of the object.
	 * @param array<string,mixed> $values  The values of the other fields of this object.
	 *
	 * @return void
	 */
	private function remove_old_format( int $post_id, array $values ): void {
		// get the old list.
		$old_names = get_post_meta( $post_id, self::OLD_LIST_META_KEY, true );

		// bail if the object does not have this list.
		if ( ! metadata_exists( 'post', $post_id, self::OLD_LIST_META_KEY ) ) {
			return;
		}

		// remove the values, if the list contains entries.
		if ( is_array( $old_names ) ) {
			// get the meta keys, which are used by fields of this plugin.
			$reserved = Fields::get_instance()->get_reserved_meta_keys();

			foreach ( $old_names as $old_name ) {
				// bail if the name is not usable.
				if ( ! is_string( $old_name ) || '' === $old_name || str_starts_with( $old_name, '_' ) || str_starts_with( $old_name, self::META_PREFIX ) ) {
					continue;
				}

				// remove the meta keys of this field, if they are not used by another field.
				foreach ( array( $old_name, $old_name . '_pretty_value' ) as $meta_key ) {
					if ( isset( $reserved[ $meta_key ] ) || array_key_exists( $meta_key, $values ) ) {
						continue;
					}
					delete_post_meta( $post_id, $meta_key );
				}
			}
		}

		// remove the old list.
		delete_post_meta( $post_id, self::OLD_LIST_META_KEY );
	}

	/**
	 * Return the definition of a custom field during the import of an object.
	 *
	 * If the field is unknown, the definitions are loaded from Propstack once per request
	 * (e.g. for the import of a single object). If it is still unknown, the definition is guessed by its value.
	 *
	 * @param string $name  The name of the field in Propstack.
	 * @param mixed  $entry The entry of this field in the response of the API.
	 *
	 * @return array<string,mixed>
	 */
	private function get_definition_for_import( string $name, mixed $entry ): array {
		// get the known definition.
		$definition = $this->get_definition( $name );

		// load the definitions from Propstack, if this field is unknown and they have not been loaded in this request.
		if ( false === $definition && ! $this->definitions_requested ) {
			$this->update_definitions( false );
			$definition = $this->get_definition( $name );
		}

		// return the definition, if it is known (with a corrected type, if necessary).
		if ( false !== $definition ) {
			return $this->correct_type( $name, $definition, $entry );
		}

		// guess the definition.
		$definition = array(
			'name'    => $name,
			'label'   => $this->get_label_by_name( $name ),
			'type'    => $this->guess_type( $entry ),
			'unit'    => '',
			'options' => array(),
			'guessed' => true,
		);

		// save it, so the field is known for the output.
		$definitions          = $this->get_definitions();
		$definitions[ $name ] = $definition;
		$this->save_definitions( $definitions );

		// return the guessed definition.
		return $definition;
	}

	/**
	 * Correct the type of a known custom field by its value, if its type is not reliable.
	 *
	 * The type is not reliable if it is a text line (the type used if it could not be detected) or unknown,
	 * and if it has been guessed or is not loaded from Propstack. A value, which clearly belongs to another
	 * type (e.g. a checkbox, a date or the ID of an option with its name), changes the type to this one.
	 * Saved definitions with a wrong type (e.g. from an earlier import with another API version) are corrected this way.
	 *
	 * @param string              $name       The name of the field in Propstack.
	 * @param array<string,mixed> $definition The definition of the custom field.
	 * @param mixed               $entry      The entry of this field in the response of the API.
	 *
	 * @return array<string,mixed>
	 */
	private function correct_type( string $name, array $definition, mixed $entry ): array {
		// get the actual type.
		$type = isset( $definition['type'] ) && is_string( $definition['type'] ) ? $definition['type'] : '';

		// bail if the type is a specific known type.
		if ( 'String' !== $type && array_key_exists( $type, $this->get_types() ) ) {
			return $definition;
		}

		// bail if the type is loaded from Propstack.
		if ( empty( $definition['guessed'] ) && $this->is_loading_of_definitions_enabled() ) {
			return $definition;
		}

		// guess the type by the value.
		$guessed_type = $this->guess_type( $entry );

		// bail if the type would not change.
		if ( $guessed_type === $type || ( 'String' === $guessed_type && '' !== $type ) ) {
			return $definition;
		}

		// save the corrected type.
		$definition['type']   = $guessed_type;
		$definitions          = $this->get_definitions();
		$definitions[ $name ] = $definition;
		$this->save_definitions( $definitions );

		// return the corrected definition.
		return $definition;
	}

	/**
	 * Return whether the value of a custom field contains an option, which is not part of its definition.
	 *
	 * @param array<string,mixed> $definition The definition of the custom field.
	 * @param mixed               $entry      The entry of the field in the response of the API.
	 *
	 * @return bool
	 */
	private function has_unknown_options( array $definition, mixed $entry ): bool {
		// bail if this type does not use options.
		if ( ! isset( $definition['type'] ) || ! in_array( $definition['type'], array( 'Dropdown', 'Multiselect', 'Tags', 'TagsByCategory' ), true ) ) {
			return false;
		}

		// get the value.
		$value = $this->is_v1_entry( $entry ) ? $entry['value'] : $entry;

		// check each chosen option.
		foreach ( is_array( $value ) ? $value : array( $value ) as $option_id ) {
			if ( is_scalar( $option_id ) && '' !== (string) $option_id && null === $this->get_option_name( $definition, $option_id ) ) {
				return true;
			}
		}

		// return false as all options are known.
		return false;
	}

	/**
	 * Return whether the given entry of a custom field is in the format of the API v1.
	 *
	 * The API v1 delivers each custom field as array with "value" and "pretty_value", the API v2 only the value.
	 *
	 * @param mixed $entry The entry of the field in the response of the API.
	 *
	 * @return bool
	 */
	private function is_v1_entry( mixed $entry ): bool {
		return is_array( $entry ) && array_key_exists( 'value', $entry ) && array_key_exists( 'pretty_value', $entry );
	}

	/**
	 * Guess the type of custom field by its value.
	 *
	 * @param mixed $entry The entry of the field in the response of the API.
	 *
	 * @return string
	 */
	private function guess_type( mixed $entry ): string {
		// get the value and the pretty value.
		$value        = $this->is_v1_entry( $entry ) ? $entry['value'] : $entry;
		$pretty_value = $this->is_v1_entry( $entry ) ? $entry['pretty_value'] : null;

		// a user is delivered with its data by the API v1.
		if ( is_array( $pretty_value ) && isset( $pretty_value['name'] ) ) {
			return 'Broker';
		}

		// a boolean is a checkbox.
		if ( is_bool( $value ) ) {
			return 'Boolean';
		}

		// a list is a multiple choice.
		if ( is_array( $value ) ) {
			return 'Multiselect';
		}

		// an ID with a text is a dropdown.
		if ( is_int( $value ) && is_string( $pretty_value ) && ! is_numeric( str_replace( array( '.', ',' ), '', $pretty_value ) ) ) {
			return 'Dropdown';
		}

		// a float is a number (an integer could be the ID of an option, too).
		if ( is_float( $value ) || ( is_int( $value ) && is_string( $pretty_value ) ) ) {
			return 'Number';
		}

		// a country code without pretty value is a country selection (API v1 delivers a pretty value for each text).
		if ( is_string( $value ) && $this->is_v1_entry( $entry ) && null === $pretty_value && preg_match( '/^[A-Z]{3}$/', $value ) && array_key_exists( $value, Countries::get_countries() ) ) {
			return 'CountrySelect';
		}

		// check strings for dates.
		if ( is_string( $value ) ) {
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value ) ) {
				return 'DateTime';
			}
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
				return 'Date';
			}
		}

		// use text as default.
		return 'String';
	}

	/**
	 * Return the values of a custom field to save them.
	 *
	 * Returns null if the field is empty. Otherwise, an array with:
	 * - "raw": the value as delivered by Propstack.
	 * - "value": the value for the output (e.g. the names of the chosen options instead of their IDs).
	 *
	 * @param array<string,mixed> $definition The definition of the custom field.
	 * @param mixed               $entry      The entry of the field in the response of the API.
	 *
	 * @return array<string,mixed>|null
	 */
	private function prepare_value( array $definition, mixed $entry ): ?array {
		// get the value and the pretty value (only the API v1 delivers it).
		$value        = $this->is_v1_entry( $entry ) ? $entry['value'] : $entry;
		$pretty_value = $this->is_v1_entry( $entry ) ? $entry['pretty_value'] : null;

		// bail if the value is empty (false is a value of a checkbox).
		if ( is_null( $value ) || '' === $value || array() === $value ) {
			return null;
		}

		// get the type.
		$type = isset( $definition['type'] ) && is_string( $definition['type'] ) ? $definition['type'] : 'String';

		// get the value for the output depending on the type.
		switch ( $type ) {
			case 'Boolean':
				$output = filter_var( $value, FILTER_VALIDATE_BOOLEAN ) ? '1' : '0';
				break;
			case 'Number':
				if ( ! is_numeric( $value ) ) {
					return null;
				}
				$output = (string) $value;
				break;
			case 'Dropdown':
				$output = $this->get_option_name( $definition, $value );
				if ( null === $output ) {
					$output = is_string( $pretty_value ) && '' !== $pretty_value ? $pretty_value : $this->get_string( $value );
				}
				break;
			case 'Multiselect':
			case 'Tags':
			case 'TagsByCategory':
				$output = $this->get_option_names( $definition, $value, $pretty_value );
				break;
			case 'CountrySelect':
				$output = strtoupper( $this->get_string( $value ) );
				break;
			case 'Broker':
				$output = $this->get_broker_name( $value, $pretty_value );
				break;
			default:
				$output = is_array( $value ) ? $this->get_option_names( $definition, $value, $pretty_value ) : $this->get_string( $value );
		}

		// bail if the value for the output is empty.
		if ( '' === $output || array() === $output ) {
			return null;
		}

		// return the values.
		return array(
			'raw'   => $value,
			'value' => $output,
		);
	}

	/**
	 * Return the given value as string.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	private function get_string( mixed $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Return the name of the option with the given ID.
	 *
	 * @param array<string,mixed> $definition The definition of the custom field.
	 * @param mixed               $option_id  The ID of the option.
	 *
	 * @return string|null
	 */
	private function get_option_name( array $definition, mixed $option_id ): ?string {
		// bail if the ID is not usable.
		if ( ! is_scalar( $option_id ) ) {
			return null;
		}

		// get the options.
		$options = isset( $definition['options'] ) && is_array( $definition['options'] ) ? $definition['options'] : array();

		// return the name, if it is known.
		return isset( $options[ (string) $option_id ] ) && is_scalar( $options[ (string) $option_id ] ) ? (string) $options[ (string) $option_id ] : null;
	}

	/**
	 * Return the names of the chosen options.
	 *
	 * @param array<string,mixed> $definition   The definition of the custom field.
	 * @param mixed               $value        The value (a list of option IDs).
	 * @param mixed               $pretty_value The pretty value (a comma-separated list of names, only via API v1).
	 *
	 * @return array<int,string>
	 */
	private function get_option_names( array $definition, mixed $value, mixed $pretty_value ): array {
		// get the values as list.
		$values = is_array( $value ) ? array_values( $value ) : array( $value );

		// get the names from the pretty value as fallback.
		$pretty_names = is_string( $pretty_value ) && '' !== $pretty_value ? array_map( 'trim', explode( ', ', $pretty_value ) ) : array();
		if ( count( $pretty_names ) !== count( $values ) ) {
			$pretty_names = array();
		}

		// collect the names.
		$names = array();
		foreach ( $values as $index => $entry ) {
			// use the name of the option.
			$name = $this->get_option_name( $definition, $entry );

			// use the name of the pretty value.
			if ( null === $name && isset( $pretty_names[ $index ] ) ) {
				$name = $pretty_names[ $index ];
			}

			// use the value itself.
			if ( null === $name ) {
				$name = $this->get_string( $entry );
			}

			// add it to the list.
			if ( '' !== $name ) {
				$names[] = $name;
			}
		}

		// return the list.
		return $names;
	}

	/**
	 * Return the name of the user with the given ID in Propstack.
	 *
	 * Returns an empty string if the name could not be found.
	 *
	 * @param mixed $value        The ID of the user in Propstack.
	 * @param mixed $pretty_value The pretty value (the data of the user, only via API v1).
	 *
	 * @return string
	 */
	private function get_broker_name( mixed $value, mixed $pretty_value ): string {
		// use the name from the API v1.
		if ( is_array( $pretty_value ) && isset( $pretty_value['name'] ) && is_string( $pretty_value['name'] ) && '' !== $pretty_value['name'] ) {
			return $pretty_value['name'];
		}

		// use the name of the broker term.
		$term_id = Taxonomies\Broker::get_instance()->get_term_id_by_api_value( $value, \ConnectorForPropstack\Plugin\Languages::get_instance()->get_current_lang() );
		if ( false !== $term_id ) {
			$term = get_term( $term_id );
			if ( $term instanceof \WP_Term ) {
				return $term->name;
			}
		}

		// the ID alone is not usable for the output.
		return '';
	}

	/**
	 * Return the formatted value of a custom field for the output.
	 *
	 * The result is HTML with escaped texts.
	 *
	 * @param array<string,mixed> $definition The definition of the custom field.
	 * @param mixed               $value      The saved value for the output.
	 *
	 * @return string
	 */
	public function format_value( array $definition, mixed $value ): string {
		// bail if the value is empty.
		if ( is_null( $value ) || '' === $value || array() === $value || false === $value ) {
			return '';
		}

		// get the type.
		$type = isset( $definition['type'] ) && is_string( $definition['type'] ) ? $definition['type'] : 'String';

		// format the value depending on the type.
		switch ( $type ) {
			case 'Text':
				$output = $this->get_link( $this->get_string( $value ) );
				if ( '' === $output ) {
					$output = nl2br( esc_html( $this->get_string( $value ) ) );
				}
				break;
			case 'Number':
				$output = $this->format_number( $value, isset( $definition['unit'] ) && is_string( $definition['unit'] ) ? $definition['unit'] : '' );
				break;
			case 'Boolean':
				// use the icons of the yes/no fields of this plugin.
				$boolean_field = new FieldTypes\BooleanField();
				$boolean_field->set_value( absint( $this->get_string( $value ) ) );
				$output = $boolean_field->get_value();
				break;
			case 'Date':
				$output = $this->format_date( $this->get_string( $value ), false );
				break;
			case 'DateTime':
				$output = $this->format_date( $this->get_string( $value ), true );
				break;
			case 'CountrySelect':
				$output = esc_html( Countries::get_name( $this->get_string( $value ) ) );
				break;
			case 'Multiselect':
			case 'Tags':
			case 'TagsByCategory':
				$output = $this->format_list( $value );
				break;
			default:
				if ( is_array( $value ) ) {
					$output = $this->format_list( $value );
					break;
				}
				$output = $this->get_link( $this->get_string( $value ) );
				if ( '' === $output ) {
					$output = esc_html( $this->get_string( $value ) );
				}
		}

		/**
		 * Filter the formatted value of a custom field.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param string              $output     The formatted value (HTML).
		 * @param mixed               $value      The saved value.
		 * @param array<string,mixed> $definition The definition of the custom field.
		 */
		return apply_filters( 'cfprop_custom_field_formatted_value', $output, $value, $definition );
	}

	/**
	 * Return a formatted number with its unit.
	 *
	 * Shows two decimals if the number has any, like Propstack does.
	 *
	 * @param mixed  $value The value.
	 * @param string $unit  The unit.
	 *
	 * @return string
	 */
	private function format_number( mixed $value, string $unit ): string {
		// bail if the value is not a number.
		if ( ! is_numeric( $value ) ) {
			return esc_html( $this->get_string( $value ) );
		}

		// get the number.
		$number = (float) $value;

		// format it.
		$output = number_format_i18n( $number, floor( $number ) === $number ? 0 : 2 );

		// add the unit.
		if ( '' !== $unit ) {
			$output .= ' ' . $unit;
		}

		// return the escaped result.
		return esc_html( $output );
	}

	/**
	 * Return a formatted date in the format of the website.
	 *
	 * Dates with time are delivered in UTC and shown in the timezone of the website.
	 *
	 * @param string $value     The date (e.g. "2026-10-15" or "2026-10-15T07:00:00+00:00").
	 * @param bool   $with_time True to show the time, too.
	 *
	 * @return string
	 */
	private function format_date( string $value, bool $with_time ): string {
		// get the timestamp.
		if ( $with_time ) {
			$timestamp = strtotime( $value );
		} else {
			$date      = date_create_immutable_from_format( '!Y-m-d', substr( $value, 0, 10 ), wp_timezone() );
			$timestamp = $date instanceof \DateTimeImmutable ? $date->getTimestamp() : false;
		}

		// bail if the date is not usable.
		if ( false === $timestamp ) {
			return esc_html( $value );
		}

		// get the format.
		$format = (string) get_option( 'date_format' );
		if ( $with_time ) {
			$format .= ' ' . get_option( 'time_format' );
		}

		// return the formatted date.
		return esc_html( (string) wp_date( $format, $timestamp ) );
	}

	/**
	 * Return a link for the given text, if it is a single URL.
	 *
	 * @param string $text The text.
	 *
	 * @return string The link or an empty string, if the text is not a single URL.
	 */
	private function get_link( string $text ): string {
		// get the text without surrounding spaces.
		$url = trim( $text );

		// bail if the text is not a single URL with http or https.
		if ( ! preg_match( '#^https?://[^\s<>"]+$#i', $url ) || false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		/**
		 * Filter the link for a custom field, which contains a single URL.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param string $link The link.
		 * @param string $url  The URL.
		 */
		return (string) apply_filters( 'cfprop_custom_field_link', '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $url ) . '</a>', $url );
	}

	/**
	 * Return a list of values as HTML list.
	 *
	 * @param mixed $value The list of values.
	 *
	 * @return string
	 */
	private function format_list( mixed $value ): string {
		// get the value as list.
		$list = is_array( $value ) ? $value : array( $value );

		// use the format of this plugin for lists.
		$field_format = new FieldFormats\Listing();
		$field_format->set_value( $list );
		return $field_format->get_value();
	}

	/**
	 * Use our own field type for custom fields, which formats their values depending on their type in Propstack.
	 *
	 * @param FieldType_Base|false $field_type The field type.
	 * @param Field_Base           $field      The field.
	 *
	 * @return FieldType_Base|false
	 */
	public function set_field_type( FieldType_Base|false $field_type, Field_Base $field ): FieldType_Base|false {
		// bail if this is not a custom field.
		if ( ! $field instanceof Propstack_Custom_Field ) {
			return $field_type;
		}

		// return our own field type.
		return new Value_Formatter( $field->get_definition() );
	}

	/**
	 * Add the settings for custom fields as tab in the settings for fields.
	 *
	 * @param Settings_Object $settings_obj The settings object.
	 * @param Tab             $fields_tab   The tab for the fields.
	 *
	 * @return void
	 */
	public function add_settings( Settings_Object $settings_obj, Tab $fields_tab ): void {
		// get the marker whether the output in the frontend is supported.
		$output_supported = $this->is_output_supported();

		// add the tab.
		$custom_fields_tab = $fields_tab->add_tab( 'propstack_connector_fields_custom_fields', 1000 );
		$custom_fields_tab->set_title( __( 'Custom fields', 'connector-for-propstack' ) );
		if ( ! $output_supported ) {
			$custom_fields_tab->set_hide_save( true );
		}

		// add a section.
		$section = $custom_fields_tab->add_section( 'propstack_connector_fields_custom_fields', 10 );
		$section->set_title( __( 'Custom fields from Propstack', 'connector-for-propstack' ) );

		// get the custom fields.
		$fields = $this->get_fields();

		// show a hint if no custom fields are known.
		if ( empty( $fields ) ) {
			$setting = $settings_obj->add_setting( 'propstack_connector_fields_custom_fields_hint' );
			$setting->set_section( $section );
			$setting->prevent_export( true );
			$field = new TextInfo( $settings_obj );
			$field->set_title( __( 'The custom fields', 'connector-for-propstack' ) );
			$field->set_description( __( 'No custom fields are known yet. They are added during the next import of objects.', 'connector-for-propstack' ) );
			$setting->set_field( $field );
			return;
		}

		// collect the description.
		$description = __( 'These are the custom fields of your objects in Propstack. Their values are shown in the edit view of each object.', 'connector-for-propstack' );
		if ( $output_supported ) {
			$description .= ' ' . __( 'Each custom field is hidden on your website until you enable it here. Enabled custom fields could be used with the widgets "Object Field", "Object data" and "Custom field" in each supported page builder.', 'connector-for-propstack' );
		} elseif ( Helper::show_pro_hints() ) {
			/* translators: %1$s: link to the Connector for Propstack Pro page */
			$description .= '<br><br><span class="cfprop-pro-hint">' . sprintf( __( 'Show custom fields on your website with <a href="%1$s" target="_blank">Connector for Propstack Pro</a>.', 'connector-for-propstack' ), Helper::get_pro_url() ) . '</span>';
		}

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_fields_custom_fields' );
		$setting->set_type( 'integer' );
		$setting->set_default( array() );
		$setting->set_section( $section );
		$field_table = new FieldTable( $settings_obj );
		$field_table->set_title( __( 'The custom fields', 'connector-for-propstack' ) );
		$field_table->set_description( $description );
		$columns = array(
			__( 'The field', 'connector-for-propstack' ),
			__( 'Internal name', 'connector-for-propstack' ),
			__( 'Type', 'connector-for-propstack' ),
		);
		if ( $output_supported ) {
			$columns[] = __( 'Hide in frontend', 'connector-for-propstack' );
		}
		$field_table->set_columns( $columns );

		// create the hidden section for all settings in this field table.
		$hidden_section = $custom_fields_tab->add_section( 'propstack_connector_fields_custom_fields_hidden', 20 );
		$hidden_section->set_hidden( true );

		// secure that each custom field is hidden until the user enables it (for definitions without this setting).
		if ( $output_supported ) {
			$this->add_hidden_settings( array_keys( $this->get_definitions() ) );
		}

		// add each custom field as row.
		$row = 0;
		foreach ( $fields as $custom_field ) {
			// get the internal key for the settings.
			$key = sanitize_key( $custom_field->get_propstack_name() );

			// add entry as a new row.
			$field_table->add_row();

			// add the label.
			$name_setting = $settings_obj->add_setting( self::SETTING_PREFIX . $key . '_name' );
			$name_setting->prevent_export( true );
			$name_setting->set_type( 'string' );
			$name_setting->set_section( $hidden_section );
			$name_setting_field = new Value( $settings_obj );
			$name_setting_field->set_value( esc_html( $custom_field->get_label() ) );
			$name_setting->set_field( $name_setting_field );
			$field_table->add_setting( $name_setting, $row, 0 );

			// add the internal name.
			$internal_name_setting = $settings_obj->add_setting( self::SETTING_PREFIX . $key . '_internal_name' );
			$internal_name_setting->prevent_export( true );
			$internal_name_setting->set_type( 'string' );
			$internal_name_setting->set_section( $hidden_section );
			$internal_name_setting_field = new Value( $settings_obj );
			$internal_name_setting_field->set_value( '<code data-copied-label="' . esc_attr__( 'copied', 'connector-for-propstack' ) . '" title="' . esc_attr__( 'Click to copy this code in your clipboard', 'connector-for-propstack' ) . '">' . esc_html( $custom_field->get_name() ) . '</code>' );
			$internal_name_setting->set_field( $internal_name_setting_field );
			$field_table->add_setting( $internal_name_setting, $row, 1 );

			// add the type.
			$type_setting = $settings_obj->add_setting( self::SETTING_PREFIX . $key . '_type' );
			$type_setting->prevent_export( true );
			$type_setting->set_type( 'string' );
			$type_setting->set_section( $hidden_section );
			$type_setting_field = new Value( $settings_obj );
			$type_setting_field->set_value( esc_html( $this->get_type_label( $custom_field->get_propstack_type() ) ) );
			$type_setting->set_field( $type_setting_field );
			$field_table->add_setting( $type_setting, $row, 2 );

			// add the setting to hide the field in the frontend (hidden by default).
			if ( $output_supported ) {
				$hide_setting = $settings_obj->add_setting( $this->get_hidden_setting_name( $custom_field->get_propstack_name() ) );
				$hide_setting->set_type( 'integer' );
				$hide_setting->set_default( 1 );
				$hide_setting->set_section( $hidden_section );
				$hide_setting->set_save_callback( array( $this, 'clear_cache_on_visibility_change' ) );
				$hide_setting_field = new Checkbox( $settings_obj );
				$hide_setting_field->set_title( $custom_field->get_label() );
				$hide_setting_field->set_readonly( false );
				$hide_setting->set_field( $hide_setting_field );
				$field_table->add_setting( $hide_setting, $row, 3 );
			}

			// next row.
			++$row;
		}
		$setting->set_field( $field_table );
	}
}

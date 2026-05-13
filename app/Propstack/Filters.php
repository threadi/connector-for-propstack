<?php
/**
 * File for handling the filter for objects in the frontend.
 *
 * TODO:
 * - Show matches count.
 * -> Configurable where (button, own field ..)
 * -> disablable
 * - AJAX-reload
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Fields\FieldTable;
use easySettingsForWordPress\Fields\Select;
use easySettingsForWordPress\Fields\Value;
use easySettingsForWordPress\Page;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Settings;

/**
 * Object to handle the filter for objects in the frontend.
 */
class Filters {

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Filters
	 */
	private static ?Filters $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Filters {
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
		// use hooks.
		add_action( 'init', array( $this, 'init_filter' ) );
		add_action( 'init', array( $this, 'add_settings' ) );

		// use our own hooks.
		add_filter( 'cfprop_archive_query_params', array( $this, 'add_query_params' ) );
		add_action( 'cfprop_filter_before', array( $this, 'extend_form_for_simple_permalinks' ) );
		add_filter( 'cfprop_filter_is_hidden', array( $this, 'hide_filter_in_settings' ) );
	}

	/**
	 * Initialize the filter.
	 *
	 * @return void
	 */
	public function init_filter(): void {
		foreach ( $this->get_filters_as_objects() as $filter ) {
			$filter->init();
		}
	}

	/**
	 * Add filter settings.
	 *
	 * @return void
	 */
	public function add_settings(): void {
		// get the settings object.
		$settings_obj = Settings::get_instance()->get_settings_obj();

		// get the settings page.
		$settings_page = $settings_obj->get_page( 'connector-for-propstack' );

		// bail if the page could not be found.
		if ( ! $settings_page instanceof Page ) {
			return;
		}

		// add a tab on this page.
		$filter_tab = $settings_page->add_tab( 'propstack_connector_filters', 40 );
		$filter_tab->set_title( __( 'Filters', 'connector-for-propstack' ) );

		// add the section in this tab.
		$filter_section = $filter_tab->add_section( 'propstack_connector_filters', 10 );
		$filter_section->set_title( __( 'Manage filters', 'connector-for-propstack' ) );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_filters' );
		$setting->set_type( 'array' );
		$setting->set_default( array() );
		$setting->set_section( $filter_section );
		$field = new FieldTable( $settings_obj );
		$field->set_title( __( 'The filters', 'connector-for-propstack' ) );
		/* translators: %1$s: Connector for Propstack Pro URL */
		$field->set_description( __( 'These are the filters you could use in the frontend. Manage their settings here.', 'connector-for-propstack' ) . '<br><br><span class="propstack-connector-pro-hint">' . sprintf( __( 'Get more filters like room or spaces with <a href="%1$s" target="_blank">Connector for Propstack Pro</a>.', 'connector-for-propstack' ), Helper::get_pro_url() ) . '</span>' );
		$field->set_columns(
			array(
				__( 'The filter', 'connector-for-propstack' ),
				__( 'Type', 'connector-for-propstack' ),
				__( 'Hide', 'connector-for-propstack' ),
			)
		);

		// create the hidden section for all settings in this field table.
		$hidden_section = $filter_tab->add_section( 'propstack_connector_filters_hidden', 20 );
		$hidden_section->set_hidden( true );

		// list the fields.
		$row = 0;
		foreach ( $this->get_filters_as_objects() as $filter_obj ) {
			foreach ( $filter_obj->get() as $filter ) {
				// get the allowed filter types for this filter.
				$allowed_filter_types = array();
				foreach ( $filter_obj->get_filter_types() as $filter_type ) {
					$allowed_filter_types[ $filter_type->get_name() ] = $filter_type->get_type_label();
				}

				// add entry as a new row.
				$field->add_row();

				// add setting.
				$name_setting = $settings_obj->add_setting( 'propstack_connector_filters_' . $filter_obj->get_name() . '_name' );
				$name_setting->prevent_export( true );
				$name_setting->set_type( 'string' );
				$name_setting->set_section( $hidden_section );
				$name_setting_field = new Value( $settings_obj );
				$name_setting_field->set_value( $filter->get_label() );
				$name_setting->set_field( $name_setting_field );
				$field->add_setting( $name_setting, $row, 0 );

				// add setting.
				$type_setting = $settings_obj->add_setting( 'propstack_connector_filters_' . $filter_obj->get_name() . '_type' );
				$type_setting->set_type( 'string' );
				$type_setting->set_default( $filter_obj->get_filter_type( $filter->get_name() )->get_filter_name() );
				$type_setting->set_section( $hidden_section );
				if ( count( $allowed_filter_types ) > 1 ) {
					$type_setting_field = new Select( $settings_obj );
					$type_setting_field->set_options( $allowed_filter_types );
				} else {
					$type_setting_field = new Value( $settings_obj );
					$type_setting_field->set_value( $allowed_filter_types[ array_key_first( $allowed_filter_types ) ] );
				}
				$type_setting->set_field( $type_setting_field );
				$field->add_setting( $type_setting, $row, 1 );

				// add setting.
				$hide_setting = $settings_obj->add_setting( 'propstack_connector_filters_' . $filter->get_filter_name() . '_hidden' );
				$hide_setting->set_type( 'integer' );
				$hide_setting->set_default( 0 );
				$hide_setting->set_section( $hidden_section );
				$hide_setting_field = new Checkbox( $settings_obj );
				$hide_setting->set_field( $hide_setting_field );
				$field->add_setting( $hide_setting, $row, 2 );

				// next row.
				++$row;
			}
		}
		$setting->set_field( $field );

		// add the section in this tab.
		$filter_settings_section = $filter_tab->add_section( 'propstack_connector_filter_settings', 20 );
		$filter_settings_section->set_title( __( 'Settings', 'connector-for-propstack' ) );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_filter_use_post' );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
		$setting->set_section( $filter_settings_section );
		$field = new Checkbox( $settings_obj );
		$field->set_title( __( 'Use POST to submit the filter', 'connector-for-propstack' ) );
		$field->set_description( __( 'If enabled, the filter will be submitted via POST resulting in a short URL without the visible filter parameters.', 'connector-for-propstack' ) );
		$setting->set_field( $field );
	}

	/**
	 * Add some query params to filter the archive listing.
	 *
	 * @param array<string,mixed> $query_params The query params.
	 *
	 * @return array<string,mixed>
	 */
	public function add_query_params( array $query_params ): array {
		// loop through each supported filter and run its filter rules.
		foreach ( $this->get_filters_as_objects() as $filter ) {
			$query_params = $filter->filter( $query_params );
		}

		// add relation to the meta-query if it is not set and meta-queries are used.
		if ( ! empty( $query_params['meta_query'] ) && ! isset( $query_params['meta_query']['relation'] ) ) {
			$query_params['meta_query']['relation'] = 'AND';
		}

		// return the resulting query params.
		return $query_params;
	}

	/**
	 * Return the list of available filters as objects.
	 *
	 * @return array<int,Filter_Base>
	 */
	public function get_filters_as_objects(): array {
		// prepare the list.
		$list = array();

		// add them to the list.
		foreach ( $this->get_filters() as $filter_class_name ) {
			// create the caller.
			$class_name = $filter_class_name . '::get_instance';

			// bail if it is not callable.
			if ( ! is_callable( $class_name ) ) {
				continue;
			}

			// get the object.
			$obj = $class_name();

			// bail if the object is not an instance of type "Filter_Base".
			if ( ! $obj instanceof Filter_Base ) { // @phpstan-ignore callable.nonCallable
				continue;
			}

			// add it to the list.
			$list[] = $obj;
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Return the list of available filters.
	 *
	 * @return array<int,string>
	 */
	private function get_filters(): array {
		$list = array(
			'\ConnectorForPropstack\Propstack\Filters\Cities',
			'\ConnectorForPropstack\Propstack\Filters\ObjectId',
		);

		/**
		 * Filter the list of available immo object filters.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $list List of filters.
		 */
		return apply_filters( 'cfprop_filters', $list );
	}

	/**
	 * Return the list of filter types as objects.
	 *
	 * @return array<int,Filter_Type_Base>
	 */
	public function get_filter_types_as_object(): array {
		// prepare the list.
		$list = array();

		// add them to the list.
		foreach ( $this->get_filter_types() as $filter_class_name ) {
			// bail if it is not callable.
			if ( ! class_exists( $filter_class_name ) ) {
				continue;
			}

			// get the object.
			$obj = new $filter_class_name( '' );

			// bail if the object is not an instance of type "Filter_Type_Base".
			if ( ! $obj instanceof Filter_Type_Base ) { // @phpstan-ignore callable.nonCallable
				continue;
			}

			// add it to the list.
			$list[] = $obj;
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Return the list of available filter types.
	 *
	 * @return array<int,string>
	 */
	private function get_filter_types(): array {
		$list = array(
			'\ConnectorForPropstack\Propstack\FilterTypes\Input',
			'\ConnectorForPropstack\Propstack\FilterTypes\Select',
		);

		/**
		 * Filter the list of available immo object filter types.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $list List of filter types.
		 */
		return apply_filters( 'cfprop_filter_types', $list );
	}

	/**
	 * Return the used method for filter_input().
	 *
	 * @return mixed
	 */
	public function get_method_for_filter_input(): mixed {
		return 1 === absint( get_option( 'propstack_connector_filter_use_post' ) ) ? INPUT_POST : INPUT_GET;
	}

	/**
	 * Extend the form if the project is using simple permalinks.
	 *
	 * @return void
	 */
	public function extend_form_for_simple_permalinks(): void {
		// bail if simple permalinks are not used.
		if ( ! empty( get_option( 'permalink_structure' ) ) ) {
			return;
		}

		// add some fields.
		?>
		<input type="hidden" name="page_id" value="<?php echo absint( get_queried_object_id() ); ?>">
		<input type="hidden" name="post_type" value="<?php echo esc_attr( \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() ); ?>">
		<?php
	}

	/**
	 * Return true if the filter tab is called.
	 *
	 * @param bool $hidden True if the filter should be is hidden.
	 *
	 * @return bool
	 */
	public function hide_filter_in_settings( bool $hidden ): bool {
		// bail if we are not in the backend.
		if ( ! is_admin() ) {
			return $hidden;
		}

		// show it if the activation is running to load all settings.
		if ( defined( 'CONNECTOR_FOR_PROPSTACK_ACTIVATION_RUNNING' ) ) {
			return false;
		}

		// get the tab from the request.
		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// hide it if the tab is not called.
		if ( is_null( $tab ) ) {
			return true;
		}

		// hide it if the tab is not the filter tab.
		if ( 'propstack_connector_filters' !== $tab ) {
			return true;
		}

		// return the setting.
		return $hidden;
	}
}

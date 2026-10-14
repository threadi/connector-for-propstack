<?php
/**
 * File for handling the filter for objects in the frontend.
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
		add_action( 'pre_get_posts', array( $this, 'filter_main_query' ) );
		add_action( 'pre_get_posts', array( $this, 'set_archive_posts_per_page' ) );
		add_filter( 'query_loop_block_query_vars', array( $this, 'filter_query_loop_block' ) );

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
		$setting->set_show_in_rest( array( 'schema' => array( 'items' => array( 'type' => 'string' ) ) ) );
		$field = new FieldTable( $settings_obj );
		$field->set_title( __( 'The filters', 'connector-for-propstack' ) );
		/* translators: %1$s: Connector for Propstack Pro URL */
		$field->set_description( __( 'These are the filters you could use in the frontend. Manage their settings here.', 'connector-for-propstack' ) . '<br><br><span class="cfprop-pro-hint">' . sprintf( __( 'Get more filters like room or spaces with <a href="%1$s" target="_blank">Connector for Propstack Pro</a>.', 'connector-for-propstack' ), Helper::get_pro_url() ) . '</span>' );
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
	 * Return the filter query params from the request, without params which must not be controlled by the filter.
	 *
	 * @return array<string,mixed>
	 */
	private function get_filter_query_params(): array {
		// get the query params of all filters.
		$query_params = $this->add_query_params( array() );

		// remove params which must never be controlled by filters (e.g., to prevent loading of all objects at once).
		foreach ( array( 'post_type', 'post_status', 'posts_per_page', 'posts_per_archive_page', 'nopaging', 'offset', 'paged', 'page', 'fields', 'perm', 'has_password', 'post_password' ) as $key ) {
			unset( $query_params[ $key ] );
		}

		// return the resulting list.
		return $query_params;
	}

	/**
	 * Merge the given filter query params in the given query vars.
	 *
	 * Meta- and tax-queries are combined with existing ones via AND.
	 *
	 * @param array<string,mixed> $query_vars   The query vars.
	 * @param array<string,mixed> $query_params The filter query params.
	 *
	 * @return array<string,mixed>
	 */
	private function merge_query_params( array $query_vars, array $query_params ): array {
		foreach ( $query_params as $key => $value ) {
			// combine meta- and tax-queries with existing ones.
			if ( in_array( $key, array( 'meta_query', 'tax_query' ), true ) && is_array( $value ) ) {
				if ( ! empty( $query_vars[ $key ] ) && is_array( $query_vars[ $key ] ) ) {
					$value = array(
						'relation' => 'AND',
						$query_vars[ $key ],
						$value,
					);
				}
				$query_vars[ $key ] = $value;
				continue;
			}

			// set any other value.
			$query_vars[ $key ] = $value;
		}

		// return the resulting query vars.
		return $query_vars;
	}

	/**
	 * Apply the requested filters on the main query of the object archive (e.g., used in block themes via filter block and query loop with inherit).
	 *
	 * @param \WP_Query $query The query object.
	 *
	 * @return void
	 */
	public function filter_main_query( \WP_Query $query ): void {
		// bail if this is the backend.
		if ( is_admin() ) {
			return;
		}

		// bail if this is not the main query.
		if ( ! $query->is_main_query() ) {
			return;
		}

		// bail if this is not the archive of our objects.
		if ( ! $query->is_post_type_archive( PostTypes\ImmoObject::get_instance()->get_name() ) ) {
			return;
		}

		// get the filter query params.
		$query_params = $this->get_filter_query_params();

		// bail if no filter is used.
		if ( empty( $query_params ) ) {
			return;
		}

		// merge them in the query.
		$query_vars = $this->merge_query_params(
			array(
				'meta_query' => $query->get( 'meta_query' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'tax_query'  => $query->get( 'tax_query' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			),
			$query_params
		);
		foreach ( $query_vars as $key => $value ) {
			// bail on empty meta- or tax-queries which have not been changed.
			if ( empty( $value ) ) {
				continue;
			}
			$query->set( $key, $value );
		}
	}

	/**
	 * Set the number of objects per page of the main query for the archive of our objects.
	 *
	 * Uses the same value as the archive widget, so that the pages of a paginated archive
	 * (e.g., /page/2/) are resolvable and do not result in a 404.
	 *
	 * @param \WP_Query $query The query object.
	 *
	 * @return void
	 */
	public function set_archive_posts_per_page( \WP_Query $query ): void {
		// bail if this is the backend.
		if ( is_admin() ) {
			return;
		}

		// bail if this is not the main query.
		if ( ! $query->is_main_query() ) {
			return;
		}

		// bail if this is not the archive of our objects.
		if ( ! $query->is_post_type_archive( PostTypes\ImmoObject::get_instance()->get_name() ) ) {
			return;
		}

		// bail if the number of objects per page is already set by someone else.
		if ( '' !== $query->get( 'posts_per_page' ) ) {
			return;
		}

		// set the number of objects per page.
		$query->set( 'posts_per_page', Widgets\Archive::get_instance()->get_posts_per_page() );
	}

	/**
	 * Apply the requested filters on query loop blocks which list our objects and do not inherit the main query.
	 *
	 * @param array<string,mixed> $query_vars The query vars of the query loop block.
	 *
	 * @return array<string,mixed>
	 */
	public function filter_query_loop_block( array $query_vars ): array {
		// bail if this query does not list our objects.
		if ( empty( $query_vars['post_type'] ) || PostTypes\ImmoObject::get_instance()->get_name() !== $query_vars['post_type'] ) {
			return $query_vars;
		}

		// get the filter query params.
		$query_params = $this->get_filter_query_params();

		// bail if no filter is used.
		if ( empty( $query_params ) ) {
			return $query_vars;
		}

		// return the merged query vars.
		return $this->merge_query_params( $query_vars, $query_params );
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
	 * Return the requested filter values, read from the configured request method (GET or POST).
	 *
	 * @return array<string,string>
	 */
	public function get_requested_filters(): array {
		// get the request data depending on the configured method.
		if ( INPUT_POST === $this->get_method_for_filter_input() ) {
			$filters = isset( $_POST['filter'] ) ? wp_unslash( (array) $_POST['filter'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only public filter, no nonce required; sanitized below.
		} else {
			$filters = isset( $_GET['filter'] ) ? wp_unslash( (array) $_GET['filter'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only public filter, no nonce required; sanitized below.
		}

		// return the sanitized list.
		return array_map( 'sanitize_text_field', $filters );
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
		if ( defined( 'CFPROP_ACTIVATION_RUNNING' ) ) {
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

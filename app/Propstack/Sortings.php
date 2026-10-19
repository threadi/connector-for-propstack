<?php
/**
 * File for handling the sorting of the list of objects in the frontend.
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
use ConnectorForPropstack\Plugin\Templates;
use WP_Query;

/**
 * Object to handle the sorting of the list of objects in the frontend.
 *
 * Each sorting is an object based on Sorting_Base. The visitor chooses the sorting via the request
 * parameter "cfprop_sort" (e.g. "price-asc"), which is added to the query of the list. The order
 * itself is added to the SQL statement of this query, so it works with pagination, too.
 */
class Sortings {
	/**
	 * The name of the request parameter for the sorting.
	 */
	public const REQUEST_PARAM = 'cfprop_sort';

	/**
	 * The name of the query var, which contains the sorting for the SQL statement.
	 */
	public const QUERY_VAR = 'cfprop_sorting';

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Sortings
	 */
	private static ?Sortings $instance = null;

	/**
	 * The contexts of lists, which have been loaded during this request.
	 *
	 * @var array<string,Sorting_Context>
	 */
	private array $contexts = array();

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
	public static function get_instance(): Sortings {
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
		add_action( 'init', array( $this, 'init_sortings' ) );
		add_action( 'init', array( $this, 'add_settings' ), 15 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'register_assets' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_main_query' ), 20 );
		add_filter( 'query_loop_block_query_vars', array( $this, 'sort_query_loop_block' ), 20 );
		add_filter( 'posts_clauses', array( $this, 'add_order_to_sql' ), 10, 2 );

		// use our own hooks.
		add_filter( 'cfprop_archive_query_params', array( $this, 'sort_archive_query_params' ), 20 );
		add_action( 'cfprop_filter_before', array( $this, 'add_sorting_to_filter_form' ) );
	}

	/**
	 * Initialize the sortings.
	 *
	 * @return void
	 */
	public function init_sortings(): void {
		foreach ( $this->get_sortings_as_objects() as $sorting ) {
			$sorting->init();
		}
	}

	/**
	 * Return whether the visitors could sort the list of objects.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$enabled = 1 === absint( get_option( 'propstack_connector_sorting_enabled', 1 ) );

		/**
		 * Filter whether the visitors could sort the list of objects.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param bool $enabled True if the sorting is enabled.
		 */
		return (bool) apply_filters( 'cfprop_sorting_enabled', $enabled );
	}

	/**
	 * Return the minimal share of objects in a list, which must have a value for a sorting to offer it.
	 *
	 * @return float Value between 0 and 1.
	 */
	public function get_min_share(): float {
		$min_share = 0.5;

		/**
		 * Filter the minimal share of objects in a list, which must have a value for a sorting to offer it.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param float $min_share The share between 0 and 1 (default 0.5 for the half of the objects).
		 */
		$min_share = (float) apply_filters( 'cfprop_sorting_min_share', $min_share );

		// return the value in its valid range.
		return min( 1.0, max( 0.0, $min_share ) );
	}

	/**
	 * Add the settings for the sortings.
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
		$tab = $settings_page->add_tab( 'propstack_connector_sortings', 42 );
		$tab->set_title( __( 'Sorting', 'connector-for-propstack' ) );

		// add the section for the general settings.
		$settings_section = $tab->add_section( 'propstack_connector_sorting_settings', 20 );
		$settings_section->set_title( __( 'Sorting above the list of objects', 'connector-for-propstack' ) );

		// add setting.
		$enabled_setting = $settings_obj->add_setting( 'propstack_connector_sorting_enabled' );
		$enabled_setting->set_default( 1 );
		$enabled_setting->set_section( $settings_section );
		$field = new Checkbox( $settings_obj );
		$field->set_title( __( 'Enable sorting', 'connector-for-propstack' ) );
		$field->set_description( __( 'If enabled, a selection above the list of objects allows your visitors to sort the list. Only sortings which are useful for the objects of the actual list are offered. If the list contains objects to buy and to rent, sorting by price shows the objects to buy (by purchase price) first, then the objects to rent (by rent).', 'connector-for-propstack' ) );
		$enabled_setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_sorting_default' );
		$setting->set_type( 'string' );
		$setting->set_default( '' );
		$setting->set_section( $settings_section );
		$field = new Select( $settings_obj );
		$field->set_title( __( 'Default sorting', 'connector-for-propstack' ) );
		$field->set_description( $this->get_default_option_description() );
		$field->set_options( $this->get_options_for_settings() );
		$field->set_sanitize_callback( array( $this, 'sanitize_default_option' ) );
		if ( ! $this->is_default_selectable() ) {
			$field->set_readonly( true );
			if ( Helper::show_pro_hints() ) {
				/* translators: %1$s: Connector for Propstack Pro URL */
				$field->set_description( $this->get_default_option_description() . '<br><br><span class="cfprop-pro-hint">' . sprintf( __( 'Choose the default sorting with <a href="%1$s" target="_blank">Connector for Propstack Pro</a>.', 'connector-for-propstack' ), Helper::get_pro_url() ) . '</span>' );
			}
		}
		$setting->set_field( $field );

		// add the section with the list of sortings.
		$section = $tab->add_section( 'propstack_connector_sortings', 10 );
		$section->set_title( __( 'Manage sortings', 'connector-for-propstack' ) );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_sortings' );
		$setting->set_type( 'array' );
		$setting->set_default( array() );
		$setting->set_section( $section );
		$setting->set_show_in_rest( array( 'schema' => array( 'items' => array( 'type' => 'string' ) ) ) );
		$field_table = new FieldTable( $settings_obj );
		$field_table->set_title( __( 'The sortings', 'connector-for-propstack' ) );
		$description = __( 'These are the sortings your visitors could use above the list of objects. Hide the ones you do not want to offer.', 'connector-for-propstack' );
		if ( Helper::show_pro_hints() ) {
			/* translators: %1$s: Connector for Propstack Pro URL */
			$description .= '<br><br><span class="cfprop-pro-hint">' . sprintf( __( 'Get more sortings like living space, rooms or newest objects with <a href="%1$s" target="_blank">Connector for Propstack Pro</a>.', 'connector-for-propstack' ), Helper::get_pro_url() ) . '</span>';
		}
		$field_table->set_description( $description );
		$field_table->set_columns(
			array(
				__( 'The sorting', 'connector-for-propstack' ),
				__( 'Options for visitors', 'connector-for-propstack' ),
				__( 'Hide', 'connector-for-propstack' ),
			)
		);

		// create the hidden section for all settings in this field table.
		$hidden_section = $tab->add_section( 'propstack_connector_sortings_hidden', 30 );
		$hidden_section->set_hidden( true );

		// list the sortings.
		$row = 0;
		foreach ( $this->get_sortings_as_objects() as $sorting ) {
			// add entry as a new row.
			$field_table->add_row();

			// add setting for the name.
			$name_setting = $settings_obj->add_setting( 'propstack_connector_sortings_' . $sorting->get_name() . '_name' );
			$name_setting->prevent_export( true );
			$name_setting->set_type( 'string' );
			$name_setting->set_section( $hidden_section );
			$name_setting_field = new Value( $settings_obj );
			$name_setting_field->set_value( $sorting->get_label() );
			$name_setting->set_field( $name_setting_field );
			$field_table->add_setting( $name_setting, $row, 0 );

			// add setting for the options.
			$options_setting = $settings_obj->add_setting( 'propstack_connector_sortings_' . $sorting->get_name() . '_options' );
			$options_setting->prevent_export( true );
			$options_setting->set_type( 'string' );
			$options_setting->set_section( $hidden_section );
			$options_setting_field = new Value( $settings_obj );
			$options_setting_field->set_value( implode( ', ', $sorting->get_options() ) );
			$options_setting->set_field( $options_setting_field );
			$field_table->add_setting( $options_setting, $row, 1 );

			// add setting to hide it.
			$hide_setting = $settings_obj->add_setting( 'propstack_connector_sortings_' . $sorting->get_name() . '_hidden' );
			$hide_setting->set_default( 0 );
			$hide_setting->set_section( $hidden_section );
			$hide_setting_field = new Checkbox( $settings_obj );
			$hide_setting->set_field( $hide_setting_field );
			$field_table->add_setting( $hide_setting, $row, 2 );

			// next row.
			++$row;
		}
		$setting->set_field( $field_table );

		// save the defaults.
		$this->add_default_options(
			array(
				'propstack_connector_sorting_enabled' => 1,
				'propstack_connector_sorting_default' => '',
			)
		);
	}

	/**
	 * Save the default values of our settings, if they do not exist yet (e.g. after an update).
	 *
	 * The settings page shows the stored values, so the defaults must be stored.
	 *
	 * @param array<string,mixed> $defaults List of settings with their default values.
	 *
	 * @return void
	 */
	private function add_default_options( array $defaults ): void {
		foreach ( $defaults as $name => $value ) {
			if ( false === get_option( $name, false ) ) {
				add_option( $name, $value );
			}
		}
	}

	/**
	 * Return the description of the setting for the default sorting.
	 *
	 * @return string
	 */
	public function get_default_option_description(): string {
		return __( 'This sorting is used as long as the visitor did not choose another one. If it is not useful for the objects of a list, the standard order is used.', 'connector-for-propstack' );
	}

	/**
	 * Return whether the default sorting could be chosen (with Connector for Propstack Pro).
	 *
	 * Without it, the standard order (newest objects first) is used.
	 *
	 * @return bool
	 */
	public function is_default_selectable(): bool {
		$selectable = false;

		/**
		 * Filter whether the default sorting of the list of objects could be chosen.
		 *
		 * Used by Connector for Propstack Pro.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param bool $selectable True if it could be chosen.
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		return (bool) apply_filters( 'cfprop_sorting_default_selectable', $selectable );
	}

	/**
	 * Return the list of options for the setting of the default sorting.
	 *
	 * @return array<string,string>
	 */
	public function get_options_for_settings(): array {
		$options = array(
			'' => __( 'Standard order (newest objects first)', 'connector-for-propstack' ),
		);
		foreach ( $this->get_sortings_as_objects() as $sorting ) {
			$options = array_merge( $options, $sorting->get_options() );
		}
		return $options;
	}

	/**
	 * Sanitize the setting for the default sorting.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return string
	 */
	public function sanitize_default_option( mixed $value ): string {
		// bail if value is not a string.
		if ( ! is_string( $value ) ) {
			return '';
		}

		// return the value only if it is a known option.
		return array_key_exists( $value, $this->get_options_for_settings() ) ? $value : '';
	}

	/**
	 * Return the list of available sortings as objects.
	 *
	 * @return array<int,Sorting_Base>
	 */
	public function get_sortings_as_objects(): array {
		// prepare the list.
		$list = array();

		// add them to the list.
		foreach ( $this->get_sortings() as $sorting_class_name ) {
			// create the caller.
			$class_name = $sorting_class_name . '::get_instance';

			// bail if it is not callable.
			if ( ! is_callable( $class_name ) ) {
				continue;
			}

			// get the object.
			$obj = $class_name();

			// bail if the object is not an instance of type "Sorting_Base".
			if ( ! $obj instanceof Sorting_Base ) {
				continue;
			}

			// add it to the list.
			$list[] = $obj;
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Return the list of available sortings.
	 *
	 * @return array<int,string>
	 */
	private function get_sortings(): array {
		$list = array(
			'\ConnectorForPropstack\Propstack\Sortings\Price',
		);

		/**
		 * Filter the list of available sortings for the list of objects.
		 *
		 * Each entry must be the name of a class based on \ConnectorForPropstack\Propstack\Sorting_Base
		 * with a static get_instance() method.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<int,string> $list List of sortings.
		 */
		return apply_filters( 'cfprop_sortings', $list );
	}

	/**
	 * Return the sorting by its name.
	 *
	 * @param string $name The internal name of the sorting.
	 *
	 * @return Sorting_Base|false
	 */
	public function get_sorting_by_name( string $name ): Sorting_Base|false {
		foreach ( $this->get_sortings_as_objects() as $sorting ) {
			if ( $sorting->get_name() === $name ) {
				return $sorting;
			}
		}
		return false;
	}

	/**
	 * Return the sorting and its direction for the given option (e.g. "price-asc").
	 *
	 * @param string $option The option.
	 *
	 * @return array{sorting:Sorting_Base,direction:string}|false
	 */
	public function get_sorting_by_option( string $option ): array|false {
		// bail if the option has not the format "name-direction".
		if ( 1 !== preg_match( '/^([a-z0-9_]+)-(asc|desc)$/', $option, $matches ) ) {
			return false;
		}

		// get the sorting.
		$sorting = $this->get_sorting_by_name( $matches[1] );

		// bail if the sorting is unknown.
		if ( ! $sorting instanceof Sorting_Base ) {
			return false;
		}

		// bail if the sorting does not support this direction.
		if ( ! array_key_exists( $matches[2], $sorting->get_directions() ) ) {
			return false;
		}

		// return the sorting with its direction.
		return array(
			'sorting'   => $sorting,
			'direction' => $matches[2],
		);
	}

	/**
	 * Return the sorting option requested by the visitor (e.g. "price-asc").
	 *
	 * Uses the same request method (GET or POST) as the filter.
	 *
	 * @return string
	 */
	public function get_requested_option(): string {
		// get the request data depending on the configured method.
		if ( INPUT_POST === Filters::get_instance()->get_method_for_filter_input() ) {
			$option = isset( $_POST[ self::REQUEST_PARAM ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::REQUEST_PARAM ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only public sorting, no nonce required.
		} else {
			$option = isset( $_GET[ self::REQUEST_PARAM ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::REQUEST_PARAM ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only public sorting, no nonce required.
		}

		// return it only if it is a known option.
		return false !== $this->get_sorting_by_option( $option ) ? $option : '';
	}

	/**
	 * Return the configured default sorting option.
	 *
	 * @return string
	 */
	public function get_default_option(): string {
		$option = $this->is_default_selectable() ? get_option( 'propstack_connector_sorting_default', '' ) : '';

		/**
		 * Filter the default sorting of the list of objects (e.g. "price-asc"). An empty string uses the standard order.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param string $option The default sorting.
		 */
		$option = apply_filters( 'cfprop_sorting_default', is_string( $option ) ? $option : '' );

		// return it only if it is a known option.
		return is_string( $option ) && false !== $this->get_sorting_by_option( $option ) ? $option : ''; // @phpstan-ignore function.alreadyNarrowedType
	}

	/**
	 * Return the context (the objects of the list) for the given query params.
	 *
	 * All objects of the list are used, not only the ones of the actual page.
	 *
	 * @param array<string,mixed> $query_params The query params of the list.
	 *
	 * @return Sorting_Context
	 */
	public function get_context( array $query_params ): Sorting_Context {
		// remove the params which are not relevant for the objects of the list.
		foreach ( array( 'paged', 'page', 'offset', 'posts_per_page', 'posts_per_archive_page', 'nopaging', 'orderby', 'order', 'fields', 'no_found_rows', self::QUERY_VAR ) as $key ) {
			unset( $query_params[ $key ] );
		}

		// use the loaded context.
		$hash = md5( (string) wp_json_encode( $query_params ) );
		if ( isset( $this->contexts[ $hash ] ) ) {
			return $this->contexts[ $hash ];
		}

		// get the post-IDs of all objects in this list.
		$query = ImmoObjects::get_instance()->get_objects_query(
			array_merge(
				$query_params,
				array(
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			)
		);

		// create the context, save and return it.
		$this->contexts[ $hash ] = new Sorting_Context( array_map( 'absint', array_filter( (array) $query->posts, 'is_numeric' ) ) );
		return $this->contexts[ $hash ];
	}

	/**
	 * Return the sortings which are available for the given list.
	 *
	 * @param Sorting_Context $context The objects of the list.
	 *
	 * @return array<int,Sorting_Base>
	 */
	public function get_available_sortings( Sorting_Context $context ): array {
		return array_values(
			array_filter(
				$this->get_sortings_as_objects(),
				static function ( Sorting_Base $sorting ) use ( $context ) {
					return $sorting->is_available( $context );
				}
			)
		);
	}

	/**
	 * Return the available options with their labels for the given list.
	 *
	 * @param Sorting_Context $context The objects of the list.
	 *
	 * @return array<string,string>
	 */
	public function get_available_options( Sorting_Context $context ): array {
		$options = array();
		foreach ( $this->get_available_sortings( $context ) as $sorting ) {
			$options = array_merge( $options, $sorting->get_options( $context ) );
		}
		return $options;
	}

	/**
	 * Return the sorting option, which should be used for the given list.
	 *
	 * This is the option requested by the visitor or the default option, if they are available for the list.
	 *
	 * @param Sorting_Context $context The objects of the list.
	 * @param string          $option  The requested option (optional, uses the request of the visitor if empty).
	 *
	 * @return string The option or an empty string for the standard order.
	 */
	public function get_active_option( Sorting_Context $context, string $option = '' ): string {
		// get the requested option.
		if ( '' === $option ) {
			$option = $this->get_requested_option();
		}

		// use the default option if none is requested.
		if ( '' === $option ) {
			$option = $this->get_default_option();
		}

		// bail if no option is set.
		if ( '' === $option ) {
			return '';
		}

		// get the sorting.
		$sorting = $this->get_sorting_by_option( $option );

		// bail if the option is unknown or not available for this list.
		if ( false === $sorting || ! $sorting['sorting']->is_available( $context ) ) {
			return '';
		}

		// return the option.
		return $option;
	}

	/**
	 * Add the given sorting option to the given query params.
	 *
	 * @param array<string,mixed> $query_params The query params.
	 * @param string              $option       The sorting option (e.g. "price-asc").
	 * @param Sorting_Context     $context      The objects of the list.
	 *
	 * @return array<string,mixed>
	 */
	public function add_option_to_query_params( array $query_params, string $option, Sorting_Context $context ): array {
		// get the sorting.
		$sorting = $this->get_sorting_by_option( $option );

		// bail if the option is unknown.
		if ( false === $sorting ) {
			return $query_params;
		}

		// get the meta key for this list.
		$meta_key = $sorting['sorting']->get_meta_key( $context );

		// bail if no meta key could be used.
		if ( '' === $meta_key ) {
			return $query_params;
		}

		// add the sorting for the SQL statement.
		$query_params[ self::QUERY_VAR ] = array(
			'option'        => $option,
			'meta_key'      => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'fallback_key'  => $sorting['sorting']->get_fallback_meta_key( $context ),
			'cast'          => $sorting['sorting']->get_cast(),
			'direction'     => $sorting['direction'],
			'positive_only' => $sorting['sorting']->is_positive_only(),
		);

		// return the resulting query params.
		return $query_params;
	}

	/**
	 * Add the sorting to the query params of the archive widget.
	 *
	 * @param array<string,mixed> $query_params The query params.
	 *
	 * @return array<string,mixed>
	 */
	public function sort_archive_query_params( array $query_params ): array {
		// bail if sorting is not enabled.
		if ( ! $this->is_enabled() ) {
			return $query_params;
		}

		// get the context.
		$context = $this->get_context( $query_params );

		// get the option to use.
		$option = $this->get_active_option( $context );

		// bail if no option should be used.
		if ( '' === $option ) {
			return $query_params;
		}

		// return the query params with the sorting.
		return $this->add_option_to_query_params( $query_params, $option, $context );
	}

	/**
	 * Add the sorting to the main query of the object archive (e.g., used in block themes via query loop with inherit).
	 *
	 * @param WP_Query $query The query object.
	 *
	 * @return void
	 */
	public function sort_main_query( WP_Query $query ): void {
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

		// bail if sorting is not enabled.
		if ( ! $this->is_enabled() ) {
			return;
		}

		// get the context with the query vars of the main query.
		$query_vars = array_filter(
			array(
				'meta_query' => $query->get( 'meta_query' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'tax_query'  => $query->get( 'tax_query' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				's'          => $query->get( 's' ),
			)
		);
		$context    = $this->get_context( $query_vars );

		// get the option to use.
		$option = $this->get_active_option( $context );

		// bail if no option should be used.
		if ( '' === $option ) {
			return;
		}

		// add the sorting.
		$query_params = $this->add_option_to_query_params( array(), $option, $context );
		if ( isset( $query_params[ self::QUERY_VAR ] ) ) {
			$query->set( self::QUERY_VAR, $query_params[ self::QUERY_VAR ] );
		}
	}

	/**
	 * Add the sorting to query loop blocks which list our objects and do not inherit the main query.
	 *
	 * @param array<string,mixed> $query_vars The query vars of the query loop block.
	 *
	 * @return array<string,mixed>
	 */
	public function sort_query_loop_block( array $query_vars ): array {
		// bail if this query does not list our objects.
		if ( empty( $query_vars['post_type'] ) || PostTypes\ImmoObject::get_instance()->get_name() !== $query_vars['post_type'] ) {
			return $query_vars;
		}

		// bail if sorting is not enabled.
		if ( ! $this->is_enabled() ) {
			return $query_vars;
		}

		// get the context.
		$context = $this->get_context( $query_vars );

		// get the option to use.
		$option = $this->get_active_option( $context );

		// bail if no option should be used.
		if ( '' === $option ) {
			return $query_vars;
		}

		// return the query vars with the sorting.
		return $this->add_option_to_query_params( $query_vars, $option, $context );
	}

	/**
	 * Add the order of a sorting to the SQL statement of a query.
	 *
	 * The value is joined via a sub-query with one row per object, so objects without a value are
	 * kept in the list. They are shown at the end in both directions. The post date and the ID are
	 * used as second criteria, so the order is the same on each page of a paginated list.
	 *
	 * @param array<string,string> $clauses The clauses of the SQL statement.
	 * @param WP_Query             $query   The query object.
	 *
	 * @return array<string,string>
	 */
	public function add_order_to_sql( array $clauses, WP_Query $query ): array {
		// get the sorting of this query.
		$sorting = $query->get( self::QUERY_VAR );

		// bail if no sorting is set.
		if ( ! is_array( $sorting ) || empty( $sorting['meta_key'] ) || ! is_string( $sorting['meta_key'] ) ) {
			return $clauses;
		}

		global $wpdb;

		// get the direction.
		$direction = isset( $sorting['direction'] ) && 'desc' === $sorting['direction'] ? 'DESC' : 'ASC';

		// get the cast.
		$cast = isset( $sorting['cast'] ) && is_string( $sorting['cast'] ) ? $sorting['cast'] : Sorting_Base::CAST_NUMBER;

		// add the join for the value.
		$clauses['join'] .= $wpdb->prepare( " LEFT JOIN ( SELECT `post_id`, MAX( `meta_value` ) AS `cfprop_value` FROM `$wpdb->postmeta` WHERE `meta_key` = %s GROUP BY `post_id` ) AS `cfprop_sort` ON ( `cfprop_sort`.`post_id` = `$wpdb->posts`.`ID` )", $sorting['meta_key'] ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$first            = $this->get_sql_expressions( '`cfprop_sort`.`cfprop_value`', $cast, ! empty( $sorting['positive_only'] ) );

		// use only the first value, if no second meta key is set.
		if ( empty( $sorting['fallback_key'] ) || ! is_string( $sorting['fallback_key'] ) ) {
			// set the order: first the objects with value, then by value, then by date and ID.
			$clauses['orderby'] = '( ' . $first['missing'] . ' ) ASC, ' . $first['value'] . ' ' . $direction . ', `' . $wpdb->posts . '`.`post_date` DESC, `' . $wpdb->posts . '`.`ID` DESC';
			return $clauses;
		}

		// add the join for the second value.
		$clauses['join'] .= $wpdb->prepare( " LEFT JOIN ( SELECT `post_id`, MAX( `meta_value` ) AS `cfprop_value` FROM `$wpdb->postmeta` WHERE `meta_key` = %s GROUP BY `post_id` ) AS `cfprop_sort2` ON ( `cfprop_sort2`.`post_id` = `$wpdb->posts`.`ID` )", $sorting['fallback_key'] ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$second           = $this->get_sql_expressions( '`cfprop_sort2`.`cfprop_value`', $cast, ! empty( $sorting['positive_only'] ) );

		// set the order: first the objects with the first value, then the ones with the second value, then the others.
		$group              = 'CASE WHEN NOT ( ' . $first['missing'] . ' ) THEN 0 WHEN NOT ( ' . $second['missing'] . ' ) THEN 1 ELSE 2 END';
		$value              = 'CASE WHEN NOT ( ' . $first['missing'] . ' ) THEN ' . $first['value'] . ' ELSE ' . $second['value'] . ' END';
		$clauses['orderby'] = $group . ' ASC, ' . $value . ' ' . $direction . ', `' . $wpdb->posts . '`.`post_date` DESC, `' . $wpdb->posts . '`.`ID` DESC';

		// return the resulting clauses.
		return $clauses;
	}

	/**
	 * Return the SQL expressions for the value of a column and for the check whether it is missing.
	 *
	 * @param string $column        The column with the value.
	 * @param string $cast          How the values should be compared (see the CAST_ constants of Sorting_Base).
	 * @param bool   $positive_only Whether only values greater than 0 are usable.
	 *
	 * @return array{value:string,missing:string}
	 */
	private function get_sql_expressions( string $column, string $cast, bool $positive_only ): array {
		switch ( $cast ) {
			case Sorting_Base::CAST_DATE:
				return array(
					'value'   => 'CAST( LEFT( ' . $column . ', 10 ) AS DATE )',
					'missing' => $column . ' IS NULL OR ' . $column . " NOT REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}'",
				);
			case Sorting_Base::CAST_TEXT:
				return array(
					'value'   => $column,
					'missing' => $column . ' IS NULL OR TRIM( ' . $column . " ) = ''",
				);
			default:
				$value   = 'CAST( ' . $column . ' AS DECIMAL( 20, 4 ) )';
				$missing = $column . ' IS NULL OR ' . $column . " NOT REGEXP '^-?[0-9]+([.][0-9]+)?$'";
				if ( $positive_only ) {
					$missing .= ' OR ' . $value . ' <= 0';
				}
				return array(
					'value'   => $value,
					'missing' => $missing,
				);
		}
	}

	/**
	 * Register the script and the styles for the sorting.
	 *
	 * @return void
	 */
	public function register_assets(): void {
		wp_register_script(
			'cfprop-sorting',
			Helper::get_plugin_url() . 'js/sorting.js',
			array(),
			Helper::get_file_version( Helper::get_plugin_path() . 'js/sorting.js' ),
			true
		);
		wp_register_style(
			'cfprop-sorting',
			Helper::get_plugin_url() . 'css/sorting.css',
			array(),
			Helper::get_file_version( Helper::get_plugin_path() . 'css/sorting.css' ),
		);
	}

	/**
	 * Return the HTML code of the sorting selection for the list with the given query params.
	 *
	 * @param array<string,mixed> $query_params The query params of the list (incl. the active sorting).
	 * @param array<string,mixed> $attributes   The attributes of the archive widget.
	 *
	 * @return string
	 */
	public function get_output( array $query_params, array $attributes = array() ): string {
		// bail if sorting is not enabled.
		if ( ! $this->is_enabled() ) {
			return '';
		}

		// get the context.
		$context = $this->get_context( $query_params );

		// get the available options.
		$options = $this->get_available_options( $context );

		// bail if no options are available.
		if ( empty( $options ) ) {
			return '';
		}

		// get the active option.
		$active = '';
		if ( isset( $query_params[ self::QUERY_VAR ]['option'] ) && is_string( $query_params[ self::QUERY_VAR ]['option'] ) ) {
			$active = $query_params[ self::QUERY_VAR ]['option'];
		}

		// add the standard order, if no default sorting is used for this list.
		if ( '' === $this->get_active_option( $context, $this->get_default_option() ) ) {
			$options = array_merge( array( '' => __( 'Standard order', 'connector-for-propstack' ) ), $options );
		}

		// collect the settings for the template.
		$attributes['sorting_options'] = $options;
		$attributes['sorting_active']  = $active;
		$attributes['sorting_param']   = self::REQUEST_PARAM;
		$attributes['sorting_method']  = INPUT_POST === Filters::get_instance()->get_method_for_filter_input() ? 'post' : 'get';
		$attributes['sorting_action']  = $this->get_form_action( $attributes['sorting_method'] );
		$attributes['sorting_hidden']  = $this->get_hidden_fields( $attributes['sorting_method'] );

		/**
		 * Filter the attributes for the output of the sorting.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,mixed> $attributes The attributes.
		 * @param Sorting_Context     $context    The objects of the list.
		 */
		$attributes = apply_filters( 'cfprop_sorting_attributes', $attributes, $context );

		// enable the script and the styles.
		$this->register_assets();
		wp_enqueue_script( 'cfprop-sorting' );
		wp_enqueue_style( 'cfprop-sorting' );

		// collect the output.
		ob_start();
		include \ConnectorForPropstack\Plugin\Templates::get_instance()->get_template( 'parts/part-sorting.php' );
		$content = ob_get_clean();

		// return the content.
		if ( ! $content ) {
			return '';
		}
		return $content;
	}

	/**
	 * Return the URL for the form of the sorting: the first page of the actual list.
	 *
	 * @param string $method The method of the form ("get" or "post").
	 *
	 * @return string
	 */
	private function get_form_action( string $method ): string {
		// get the URL of the first page (without the actual page number).
		$url = get_pagenum_link( 1, false );

		// remove the sorting.
		$url = remove_query_arg( self::REQUEST_PARAM, $url );

		// browsers replace the query of the URL with the fields of a GET form, so the query is added as hidden fields.
		if ( 'get' === $method ) {
			$url = (string) strtok( $url, '?' );
		}

		// return the resulting URL.
		return $url;
	}

	/**
	 * Return the hidden fields for the form of the sorting, which keep the actual filter and other params.
	 *
	 * @param string $method The method of the form ("get" or "post").
	 *
	 * @return array<string,string> List of field names with their values.
	 */
	private function get_hidden_fields( string $method ): array {
		$fields = array();

		// for POST: keep the requested filter values (the URL keeps its query).
		if ( 'post' === $method ) {
			foreach ( Filters::get_instance()->get_requested_filters() as $name => $value ) {
				if ( '' === $value ) {
					continue;
				}
				$fields[ 'filter[' . sanitize_key( (string) $name ) . ']' ] = $value;
			}
			return $fields;
		}

		// for GET: keep all params of the actual request, except the page number and the sorting.
		foreach ( wp_unslash( $_GET ) as $name => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only public sorting, values are sanitized below.
			$name = sanitize_text_field( (string) $name );
			if ( in_array( $name, array( self::REQUEST_PARAM, 'paged' ), true ) ) {
				continue;
			}

			// add lists with their keys (e.g. the filter).
			if ( is_array( $value ) ) {
				foreach ( $value as $key => $sub_value ) {
					if ( ! is_scalar( $sub_value ) || '' === (string) $sub_value ) {
						continue;
					}
					$fields[ $name . '[' . sanitize_text_field( (string) $key ) . ']' ] = sanitize_text_field( (string) $sub_value );
				}
				continue;
			}

			// add single values.
			if ( is_scalar( $value ) ) {
				$fields[ $name ] = sanitize_text_field( (string) $value );
			}
		}

		// return the list.
		return $fields;
	}

	/**
	 * Add the actual sorting to the form of the filter, so it is kept if the visitor filters the list.
	 *
	 * @return void
	 */
	public function add_sorting_to_filter_form(): void {
		// bail if sorting is not enabled.
		if ( ! $this->is_enabled() ) {
			return;
		}

		// get the requested sorting.
		$option = $this->get_requested_option();

		// bail if no sorting is requested.
		if ( '' === $option ) {
			return;
		}

		// output the hidden field.
		?>
		<input type="hidden" name="<?php echo esc_attr( self::REQUEST_PARAM ); ?>" value="<?php echo esc_attr( $option ); ?>">
		<?php
	}

	/**
	 * Return the allowed HTML for the output of the sorting (including the form elements).
	 *
	 * @return array<string,mixed>
	 */
	public function get_allowed_html(): array {
		return Templates::get_instance()->get_allowed_filter_html();
	}
}

<?php
/**
 * File for handling the object for the taxonomy filter.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Filters;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Cache;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Fields;
use ConnectorForPropstack\Propstack\Fields\Main\City;
use ConnectorForPropstack\Propstack\Filters;
use ConnectorForPropstack\Propstack\Filter_Base;
use ConnectorForPropstack\Propstack\Filter_Type_Base;
use ConnectorForPropstack\Propstack\FilterTypes\Input;
use ConnectorForPropstack\Propstack\FilterTypes\Select;
use ConnectorForPropstack\Propstack\ImmoObjects;

/**
 * Object to handle the input filter type.
 */
class Cities extends Filter_Base {

	/**
	 * The internal name of the filter.
	 *
	 * @var string
	 */
	protected string $name = 'cities';

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Cities
	 */
	private static ?Cities $instance = null;

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
	public static function get_instance(): Cities {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->get_field()->get_label();
	}

	/**
	 * Return the list of for this filter allowed filter types.
	 *
	 * @return array<int,Filter_Type_Base>
	 */
	public function get_filter_types(): array {
		return array(
			new Input( '' ),
			new Select( '' ),
		);
	}

	/**
	 * Return the filter type.
	 *
	 * @param string $field_name The field name to use.
	 *
	 * @return Filter_Type_Base
	 */
	public function get_filter_type( string $field_name ): Filter_Type_Base {
		// get the setting.
		$field_type_setting = get_option( 'propstack_connector_filters_' . $this->get_name() . '_type' );

		// if no type is selected, use Select.
		if ( empty( $field_type_setting ) ) {
			return new Select( $field_name );
		}

		// get the selected filter type.
		$selected_filter_type = false;
		foreach ( Filters::get_instance()->get_filter_types_as_object() as $filter_type ) {
			if ( $filter_type->get_name() === $field_type_setting ) {
				$selected_filter_type = $filter_type;
			}
		}

		// if no filter type could be found, use Select.
		if ( ! $selected_filter_type ) {
			return new Select( $field_name );
		}

		// use the filter type from setting.
		return new $selected_filter_type( $field_name );
	}

	/**
	 * Return the field object.
	 *
	 * @return Field_Base
	 */
	protected function get_field(): Field_Base {
		return new City();
	}

	/**
	 * Return the filters.
	 *
	 * @param array<string,mixed> $attributes Additional configuration.
	 *
	 * @return array<int,Filter_Type_Base>
	 */
	public function get( array $attributes = array() ): array {
		// bail if this filter is hidden.
		if ( $this->is_hidden() ) {
			return array();
		}

		// prepare the list.
		$filters = array();

		// check our objects for some values.
		// The cache also contains an empty list (with expiration) to prevent loading all objects on every request.
		$cache = Cache::get( 'filter_cities' );
		if ( is_array( $cache ) && isset( $cache['list'] ) && is_array( $cache['list'] ) && ( empty( $cache['expires'] ) || absint( $cache['expires'] ) > time() ) ) {
			$cities = $cache['list'];
		} else {
			$cities = array();
			$query  = ImmoObjects::get_instance()->get_objects_query(
				array(
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			foreach ( $query->posts as $post_id ) {
				// get the city.
				$city = Fields::get_instance()->get_field_value( is_int( $post_id ) ? $post_id : $post_id->ID, $this->get_field() );

				// bail if no city is given.
				if ( empty( $city ) ) {
					continue;
				}

				$md5   = md5( $this->get_field()->get_name() . $city );
				$false = false;
				/**
				 * Prevent the usage of this filter value.
				 *
				 * @since        1.0.0 Available since 1.0.0.
				 *
				 * @param bool   $false Whether the filter value should be hidden.
				 * @param string $md5   The md5 hash of the field name and the value.
				 *
				 * @noinspection PhpConditionAlreadyCheckedInspection
				 */
				if ( apply_filters( 'cfprop_filter_hide_field_by_value', $false, $md5 ) ) {
					continue;
				}

				// add it to the list.
				$cities[ $city ] = $city;
			}

			// save the list in the cache (an empty list expires after an hour, e.g., if the import is still running).
			Cache::set(
				'filter_cities',
				array(
					'list'    => $cities,
					'expires' => empty( $cities ) ? time() + HOUR_IN_SECONDS : 0,
				)
			);
		}

		// add a filter for cities, if more than 0.
		if ( count( $cities ) > 0 ) {
			// sort the list.
			$cities = Helper::get_nat_sorted_array( $cities );

			// create the filter object and configure it.
			$obj = $this->get_filter_type( $this->get_field()->get_name() );
			$obj->set_label( $this->get_field()->get_label() );
			if ( $obj instanceof Select ) {
				$obj->set_options( $cities );
			}
			if ( $obj instanceof Input ) {
				$obj->set_placeholder( __( 'Enter a city', 'connector-for-propstack' ) );
			}

			// add the object to the list.
			$filters[] = $obj;
		}

		// return the resulting list.
		return $filters;
	}

	/**
	 * Check the filter rules for this filter.
	 *
	 * @param array<string,mixed> $query_params The query parameter we will use.
	 *
	 * @return array<string,mixed>
	 */
	public function filter( array $query_params ): array {
		// bail if this filter is hidden.
		if ( 1 === absint( get_option( 'propstack_connector_filters_' . $this->get_field()->get_name() . '_hidden' ) ) ) {
			return $query_params;
		}

		// get the filters.
		$filters = Filters::get_instance()->get_requested_filters();

		// get the param from the request.
		$city = isset( $filters[ $this->get_field()->get_name() ] ) ? sanitize_text_field( $filters[ $this->get_field()->get_name() ] ) : '';

		// bail if no value is set.
		if ( empty( $city ) ) { // @phpstan-ignore empty.variable
			return $query_params;
		}

		$md5   = md5( $this->get_field()->get_name() . $city );
		$false = false;
		/**
		 * Prevent the usage of this filter value.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param bool $false Whether the filter value should be hidden.
		 * @param string $md5 The md5 hash of the field name and the value.
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		if ( apply_filters( 'cfprop_filter_hide_field_by_value', $false, $md5 ) ) {
			return $query_params;
		}

		// add the entry for the meta-query.
		$query_params['meta_query'][] = array(
			'key'     => $this->get_field()->get_name(),
			'value'   => $city,
			'compare' => '=',
		);

		// return the resulting list of query params.
		return $query_params;
	}
}

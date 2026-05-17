<?php
/**
 * File for handling the object for the taxonomy filter.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Filters;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Filters;
use ConnectorForPropstack\Propstack\Filter_Base;
use ConnectorForPropstack\Propstack\Filter_Type_Base;
use ConnectorForPropstack\Propstack\FilterTypes\Input;

/**
 * Object to handle the input filter type.
 */
class ObjectId extends Filter_Base {

	/**
	 * The internal name of the filter.
	 *
	 * @var string
	 */
	protected string $name = 'object_id';

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?ObjectId
	 */
	private static ?ObjectId $instance = null;

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
	public static function get_instance(): ObjectId {
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
	 * Return the field object.
	 *
	 * @return Field_Base
	 */
	protected function get_field(): Field_Base {
		return new \ConnectorForPropstack\Propstack\Fields\Main\ObjectId();
	}

	/**
	 * Return the filter type.
	 *
	 * @param string $field_name The field name to use.
	 *
	 * @return Filter_Type_Base
	 */
	public function get_filter_type( string $field_name ): Filter_Type_Base {
		return new Input( $field_name );
	}

	/**
	 * Return the filter types.
	 *
	 * @return array<int,Filter_Type_Base>
	 */
	public function get_filter_types(): array {
		return array( new Input( '' ) );
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

		// add a filter to search for the object ID.
		$obj = $this->get_filter_type( $this->get_field()->get_name() );
		if ( ! $obj instanceof Input ) {
			return array();
		}
		$obj->set_label( $this->get_field()->get_label() );
		$obj->set_placeholder( __( 'Enter an object ID', 'connector-for-propstack' ) );

		// add the object to the list.
		$filters[] = $obj;

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

		// check for nonce even though this is just a filter and nothing is actually being written here, and the filter is public.
		if ( isset( $_GET['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'cfprop-verify' ) ) {
			exit;
		}

		// get the filters.
		$filters = isset( $_GET['filter'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_GET['filter'] ) ) : array();

		// get the param from the request.
		$object_id = isset( $filters[ $this->get_field()->get_name() ] ) ? sanitize_text_field( $filters[ $this->get_field()->get_name() ] ) : '';

		// bail if no value is set.
		if ( empty( $object_id ) ) { // @phpstan-ignore empty.variable
			return $query_params;
		}

		// add the entry for the meta-query.
		$query_params['meta_query'][] = array(
			'key'     => $this->get_field()->get_name(),
			'value'   => $object_id,
			'compare' => '=',
		);

		// return the resulting list of query params.
		return $query_params;
	}
}

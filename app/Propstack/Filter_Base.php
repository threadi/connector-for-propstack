<?php
/**
 * File to handle basic functions for any filter.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\FilterTypes\Select;

/**
 * Base object for each filter.
 */
class Filter_Base {

	/**
	 * The internal name of the filter.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {}

	/**
	 * Return the category name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return '';
	}

	/**
	 * Return the filter type.
	 *
	 * @param string $field_name The field name to use.
	 *
	 * @return Filter_Type_Base
	 */
	public function get_filter_type( string $field_name ): Filter_Type_Base {
		return new Select( $field_name );
	}

	/**
	 * Return the list of for this filter allowed filter types.
	 *
	 * @return array<int,Filter_Type_Base>
	 */
	public function get_filter_types(): array {
		return array(
			new Select( '' ),
		);
	}

	/**
	 * Return the filters.
	 *
	 * @param array<string,mixed> $attributes Additional configuration.
	 *
	 * @return array<int,Filter_Type_Base>
	 */
	public function get( array $attributes = array() ): array {
		if ( ! empty( $attributes ) ) {
			return array();
		}
		return array();
	}

	/**
	 * Check the filter rules for this filter.
	 *
	 * @param array<string,mixed> $query_params The query parameter we will use.
	 *
	 * @return array<string,mixed>
	 */
	public function filter( array $query_params ): array {
		return $query_params;
	}

	/**
	 * Return the field object.
	 *
	 * @return Field_Base
	 */
	protected function get_field(): Field_Base {
		return new Field_Base();
	}

	/**
	 * Return whether this filter should be hidden.
	 *
	 * @return bool
	 */
	protected function is_hidden(): bool {
		// bail if this filter is hidden.
		if ( ! is_admin() && ! empty( $this->get_field()->get_name() ) && 1 === absint( get_option( 'propstack_connector_filters_' . $this->get_field()->get_name() . '_hidden' ) ) ) {
			return true;
		}

		$hidden   = false;
		$instance = $this;
		/**
		 * Filter whether an object filter should be hidden.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param bool $hidden True if the filter should be hidden.
		 * @param Filter_Base $instance The filter object.
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		return apply_filters( 'cfprop_filter_is_hidden', $hidden, $instance );
	}
}

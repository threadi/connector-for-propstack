<?php
/**
 * File for handling a field type.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldTypes;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Energy_Efficiency_Classes;
use ConnectorForPropstack\Propstack\FieldType_Base;

/**
 * Object to handle this field type
 */
class EnergyEfficiencyClass extends FieldType_Base {
	/**
	 * The internal name of the type.
	 *
	 * @var string
	 */
	protected string $name = 'energy_efficiency_class';

	/**
	 * Return the value.
	 *
	 * @return string
	 */
	public function get_value(): string {
		// return empty string for non-scalar values (e.g. arrays from the API).
		if ( ! is_scalar( $this->value ) ) {
			return '';
		}

		// format the value.
		$value = strtoupper( trim( (string) str_replace( array( '_PLUS', 'PLUS' ), '+', (string) $this->value ) ) );

		// bail if value is not in the list.
		if ( ! in_array( $value, Energy_Efficiency_Classes::get_instance()->get_energy_efficiency_classes(), true ) ) {
			return '';
		}

		// return the value as string.
		return $value;
	}
}

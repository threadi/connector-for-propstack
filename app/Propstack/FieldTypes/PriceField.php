<?php
/**
 * File for handling a field type.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldTypes;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\FieldType_Base;

/**
 * Object to handle this field type
 */
class PriceField extends FieldType_Base {
	/**
	 * The internal name of the type.
	 *
	 * @var string
	 */
	protected string $name = 'price';

	/**
	 * Return the value.
	 *
	 * @return string
	 */
	public function get_value(): string {
		// bail if the value is empty or not numeric.
		if ( empty( $this->value ) || ! is_numeric( $this->value ) ) {
			return '';
		}

		// get the value as float.
		$value = round( (float) $this->value, 2 );

		// format the value: without decimals for integers, with 2 decimals otherwise.
		return number_format( $value, ( (float) (int) $value === $value ) ? 0 : 2, ',', '.' ) . ' &euro;';
	}
}

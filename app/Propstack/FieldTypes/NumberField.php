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
class NumberField extends FieldType_Base {
	/**
	 * The internal name of the type.
	 *
	 * @var string
	 */
	protected string $name = 'number';

	/**
	 * Return the value.
	 *
	 * @return string
	 */
	public function get_value(): string {
		// bail if the value is not set.
		if ( empty( $this->value ) || ! is_numeric( $this->value ) ) {
			return '';
		}

		// get the value as float.
		$value = (float) $this->value;

		// format the value with up to 2 decimals, but only if it has decimals.
		return number_format( $value, self::get_decimals( $value ), ',', '.' );
	}

	/**
	 * Return the amount of decimals (0 to 2) to show for the given value.
	 *
	 * Integers are shown without decimals, other values with up to 2 decimals without trailing zeros.
	 *
	 * @param float $value The value.
	 *
	 * @return int
	 */
	public static function get_decimals( float $value ): int {
		// round the value to 2 decimals.
		$value = round( $value, 2 );

		// return 0 if the value is an integer.
		if ( (float) (int) $value === $value ) {
			return 0;
		}

		// return 1 if the value has only 1 decimal.
		if ( round( $value, 1 ) === $value ) {
			return 1;
		}

		// return 2 for any other value.
		return 2;
	}
}

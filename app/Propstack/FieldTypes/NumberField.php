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

		// format the value.
		return number_format( (float) $this->value, 0, '', '.' );
	}
}

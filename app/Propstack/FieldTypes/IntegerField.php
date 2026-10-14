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
class IntegerField extends FieldType_Base {
	/**
	 * The internal name of the type.
	 *
	 * @var string
	 */
	protected string $name = 'integer';

	/**
	 * Return the value.
	 *
	 * @return mixed
	 */
	public function get_value(): mixed {
		if ( ! is_scalar( $this->value ) ) {
			return '';
		}

		// get the value as integer (negative values are kept).
		$output = intval( $this->value );

		// bail if the value is 0.
		if ( 0 === $output ) {
			return '';
		}

		// return the value.
		return $output;
	}
}

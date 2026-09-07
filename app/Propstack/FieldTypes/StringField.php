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
class StringField extends FieldType_Base {
	/**
	 * The internal name of the type.
	 *
	 * @var string
	 */
	protected string $name = 'string';

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

		// return the value as string.
		return (string) $this->value;
	}
}

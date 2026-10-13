<?php
/**
 * File for handling a field format.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldFormats;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\FieldFormat_Base;

/**
 * Object to handle this field format
 */
class Email extends FieldFormat_Base {
	/**
	 * The internal name of the format.
	 *
	 * @var string
	 */
	protected string $name = 'email';

	/**
	 * Return the value.
	 *
	 * @return string
	 */
	public function get_value(): string {
		// bail if the value is empty or not scalar.
		if ( empty( $this->value ) || ! is_scalar( $this->value ) ) {
			return '';
		}

		// get the value as string.
		$value = trim( (string) $this->value );

		// return the link.
		return '<a href="' . esc_url( 'mailto:' . $value ) . '">' . esc_html( $value ) . '</a>';
	}
}

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
class PhoneNumber extends FieldFormat_Base {
	/**
	 * The internal name of the format.
	 *
	 * @var string
	 */
	protected string $name = 'phone_number';

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

		// use only digits and the plus sign for the link.
		$number = (string) preg_replace( '/[^0-9+]/', '', $value );

		// return the value without link if no number is left.
		if ( empty( $number ) ) {
			return esc_html( $value );
		}

		// return the link.
		return '<a href="' . esc_url( 'tel:' . $number ) . '">' . esc_html( $value ) . '</a>';
	}
}

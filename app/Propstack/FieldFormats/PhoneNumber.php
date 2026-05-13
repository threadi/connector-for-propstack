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
		return '<a href="tel:' . $this->value . '">' . $this->value . '</a>';
	}
}

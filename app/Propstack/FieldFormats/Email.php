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
		if ( empty( $this->value ) ) {
			return '';
		}

		return '<a href="mailto:' . $this->value . '">' . $this->value . '</a>';
	}
}

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
class Country extends FieldFormat_Base {
	/**
	 * The internal name of the format.
	 *
	 * @var string
	 */
	protected string $name = 'country';

	/**
	 * Return the value.
	 *
	 * @return string
	 */
	public function get_value(): string {
		switch ( $this->value ) {
			case 'DEU':
				return __( 'Germany', 'connector-for-propstack' );
			case 'CHL':
				return __( 'Chile', 'connector-for-propstack' );
			default:
				return $this->value;
		}
	}
}

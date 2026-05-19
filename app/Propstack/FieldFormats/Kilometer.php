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
class Kilometer extends FieldFormat_Base {
	/**
	 * The internal name of the format.
	 *
	 * @var string
	 */
	protected string $name = 'kilometers';

	/**
	 * Return the value.
	 *
	 * @return string
	 */
	public function get_value(): string {
		if( empty( $this->value ) ) {
			return '';
		}

		/* translators: %1$s: number of kilometers */
		return sprintf( __( '%1$skm', 'connector-for-propstack' ), $this->value );
	}
}

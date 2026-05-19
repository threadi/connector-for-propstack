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
class Minutes extends FieldFormat_Base {
	/**
	 * The internal name of the format.
	 *
	 * @var string
	 */
	protected string $name = 'minutes';

	/**
	 * Return the value.
	 *
	 * @return string
	 */
	public function get_value(): string {
		if( empty( $this->value ) ) {
			return '';
		}

		/* translators: %1$s: number of minutes */
		return sprintf( __( '%1$smin', 'connector-for-propstack' ), $this->value );
	}
}

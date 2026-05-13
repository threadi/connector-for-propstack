<?php
/**
 * File for handling a field type.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldTypes;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\FieldType_Base;

/**
 * Object to handle this field type
 */
class ArrayField extends FieldType_Base {
	/**
	 * The internal name of the type.
	 *
	 * @var string
	 */
	protected string $name = 'array';

	/**
	 * Marker to use HTML for this field type.
	 *
	 * @var bool
	 */
	protected bool $with_html = true;

	/**
	 * Return the value.
	 *
	 * @return array<int,mixed>
	 */
	public function get_value(): array {
		// bail if the value is not an array.
		if ( ! is_array( $this->value ) ) {
			return array();
		}

		// return the value as an array.
		return $this->value;
	}
}

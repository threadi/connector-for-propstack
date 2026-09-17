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
class GeoField extends FieldType_Base {
	/**
	 * The internal name of the type.
	 *
	 * @var string
	 */
	protected string $name = 'geo';

	/**
	 * Marker to use HTML for this field type.
	 *
	 * @var bool
	 */
	protected bool $with_html = true;

	/**
	 * Return the value.
	 *
	 * Hint:
	 * - 4-digit coordinates are accurate to within 10 meters.
	 * - 5-digit coordinates are accurate to within 1 meter.
	 *
	 * @return string
	 */
	public function get_value(): string {
		// bail if the value is not set.
		if ( empty( $this->value ) || ! is_numeric( $this->value ) ) {
			return '';
		}

		// format the value.
		return number_format( (float) $this->value, 4, '.', '' );
	}
}

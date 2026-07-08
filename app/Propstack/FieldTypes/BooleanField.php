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
class BooleanField extends FieldType_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = 'boolean';

	/**
	 * Marker to use HTML for this field type.
	 *
	 * @var bool
	 */
	protected bool $with_html = true;

	/**
	 * Return the value.
	 *
	 * @return string
	 */
	public function get_value(): string {
		if ( 1 === absint( $this->value ) ) {
			return '<span class="dashicons dashicons-yes"></span>';
		}
		return '<span class="dashicons dashicons-no"></span>';
	}
}

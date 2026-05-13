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
class CodeField extends FieldType_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = 'code';

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
		// bail if the value is not an array.
		if ( ! is_array( $this->value ) ) {
			return '';
		}

		// return the value as formatted JSON.
		return '<code data-copied-label="' . esc_attr__( 'copied', 'connector-for-propstack' ) . '">' . htmlentities( Helper::get_json( $this->value, JSON_PRETTY_PRINT ) ) . '</code>';
	}
}

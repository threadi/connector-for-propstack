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
class HtmlField extends FieldType_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = 'html';

	/**
	 * Return the value.
	 *
	 * @return mixed
	 */
	public function get_value(): mixed {
		return nl2br( $this->value );
	}

	/**
	 * Return the cleaned value.
	 *
	 * @return mixed
	 */
	public function get_cleaned_value(): mixed {
		// bail if the value is empty.
		if ( empty( $this->value ) ) {
			return '';
		}

		// remove the style and id attributes.
		$pre_cleaned_value = preg_replace( '/ style=("|\')(.*?)("|\')/', '', $this->value );
		return preg_replace( '/ id=("|\')(.*?)("|\')/', '', $pre_cleaned_value );
	}
}

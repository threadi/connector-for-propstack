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
class EditorField extends FieldType_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = 'editor';

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
		$pre_cleaned_value = preg_replace( '/ style=("|\')(.*?)("|\')/', '', $this->value );
		return preg_replace( '/ id=("|\')(.*?)("|\')/', '', $pre_cleaned_value );
	}
}

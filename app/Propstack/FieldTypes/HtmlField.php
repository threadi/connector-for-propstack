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
	 * The internal name of the type.
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
		// bail if the value is not scalar (e.g. arrays or objects from the API).
		if ( ! is_scalar( $this->value ) ) {
			return '';
		}

		// return the value.
		return nl2br( (string) $this->value );
	}

	/**
	 * Return the cleaned value.
	 *
	 * @return mixed
	 */
	public function get_cleaned_value(): mixed {
		// bail if the value is empty.
		if ( ! is_scalar( $this->value ) ) {
			return '';
		}

		// remove the style and id attributes.
		$pre_cleaned_value = preg_replace( '/ style=("|\')(.*?)("|\')/', '', (string) $this->value );
		$cleaned_value     = preg_replace( '/ id=("|\')(.*?)("|\')/', '', (string) $pre_cleaned_value );

		// allow only HTML-code which is allowed in posts.
		return wp_kses_post( (string) $cleaned_value );
	}
}

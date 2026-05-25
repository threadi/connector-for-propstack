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
		// remove any <p>-elements.
		$pre_cleaned_value = preg_replace( '/^\s*<p[^>]*>|<\/p>\s*$/i', '', $this->value );

		// add line breaks.
		return nl2br( $pre_cleaned_value );
	}

	/**
	 * Return the cleaned value.
	 *
	 * @return mixed
	 */
	public function get_cleaned_value(): mixed {
		// bail if no value is set.
		if ( empty( $this->value ) ) {
			return '';
		}

		// clean the value.
		return preg_replace(
			array(
				'/ style=("|\')(.*?)("|\')/',   // remove inline styles on HTML-elements.
				'/ id=("|\')(.*?)("|\')/',      // remove the ID attribute on every HTML-element.
				'/^\s*<p[^>]*>|<\/p>\s*$/i',   // remove any <p>-elements.
			),
			'',
			$this->value
		);
	}
}

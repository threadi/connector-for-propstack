<?php
/**
 * File to handle basic functions for field types.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Base object for each field type.
 */
class FieldType_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * The value.
	 *
	 * @var mixed
	 */
	protected mixed $value = '';

	/**
	 * Marker to use HTML for this field type.
	 *
	 * @var bool
	 */
	protected bool $with_html = false;

	/**
	 * Return the category name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the value.
	 *
	 * @return mixed
	 */
	public function get_value(): mixed {
		return $this->value;
	}

	/**
	 * Set the value of the field.
	 *
	 * @param mixed $value The value.
	 *
	 * @return void
	 */
	public function set_value( mixed $value ): void {
		$this->value = $value;
	}

	/**
	 * Return whether the value should be used with HTML.
	 *
	 * @return bool
	 */
	public function is_with_html(): bool {
		return $this->with_html;
	}

	/**
	 * Return the value cleaned for saving it as post meta.
	 *
	 * Hint:
	 * Post meta is always stored as string. Returning anything else lets the strict
	 * comparison in update_metadata() fail on every import, which rewrites the value
	 * and drops the meta cache of the object for every single field.
	 *
	 * @return mixed
	 */
	public function get_cleaned_value(): mixed {
		// return an empty string for values which are not set, as that is how WordPress stores them.
		if ( is_null( $this->value ) ) {
			return '';
		}

		// keep non-scalar values as they are, they are serialized anyway.
		if ( ! is_scalar( $this->value ) ) {
			return $this->value;
		}

		// return booleans the way WordPress stores them.
		if ( is_bool( $this->value ) ) {
			return $this->value ? '1' : '';
		}

		// return everything else as string.
		return (string) $this->value;
	}
}

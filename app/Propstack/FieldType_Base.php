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
	 * Return the cleaned value.
	 *
	 * @return mixed
	 */
	public function get_cleaned_value(): mixed {
		return $this->value;
	}
}

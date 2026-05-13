<?php
/**
 * File to handle basic functions for a single category type for categories of fields.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Base object for a single category type for categories of fields.
 */
class Field_Category_Type_Base {
	/**
	 * The internal name of the category type.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Return the category type.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the category type label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return '';
	}
}

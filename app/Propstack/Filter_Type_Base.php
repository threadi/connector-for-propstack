<?php
/**
 * File to handle basic functions for any filter type.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Base object for each filter type.
 */
class Filter_Type_Base {

	/**
	 * The internal name of the filter type.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * The internal name of the filter, that is using this type.
	 *
	 * @var string
	 */
	protected string $filter_name = '';

	/**
	 * The label for the filter.
	 *
	 * @var string
	 */
	protected string $label = '';

	/**
	 * Return the filter type name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the filter name.
	 *
	 * @return string
	 */
	public function get_filter_name(): string {
		return $this->filter_name;
	}

	/**
	 * Return the filter type label.
	 *
	 * @return string
	 */
	public function get_type_label(): string {
		return '';
	}

	/**
	 * Return the filter label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Set the label.
	 *
	 * @param string $label The label.
	 *
	 * @return void
	 */
	public function set_label( string $label ): void {
		$this->label = $label;
	}

	/**
	 * Render this filter type.
	 *
	 * @return string
	 */
	public function render(): string {
		return '';
	}
}

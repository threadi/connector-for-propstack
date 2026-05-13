<?php
/**
 * File for handling a field format.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldFormats;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\FieldFormat_Base;

/**
 * Object to handle this field format
 */
class Listing extends FieldFormat_Base {
	/**
	 * The internal name of the format.
	 *
	 * @var string
	 */
	protected string $name = 'listing';

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

		// bail if the value is empty.
		if ( empty( $this->value ) ) {
			return '';
		}

		// create the list.
		$list = '';
		foreach ( $this->value as $value ) {
			$list .= '<li>' . $value . '</li>';
		}

		// return the resulting list.
		return '<ul>' . $list . '</ul>';
	}
}

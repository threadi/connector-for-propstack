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
			// get the text for this entry.
			$text = $this->get_entry_text( $value );

			// bail if the text is empty.
			if ( '' === $text ) {
				continue;
			}

			// add it to the list.
			$list .= '<li>' . esc_html( $text ) . '</li>';
		}

		// bail if the list is empty.
		if ( empty( $list ) ) {
			return '';
		}

		// return the resulting list.
		return '</p><ul>' . $list . '</ul><p>';
	}

	/**
	 * Return the text for a single entry of the list.
	 *
	 * Lists are flattened to their scalar values, separated by comma.
	 *
	 * @param mixed $value The entry.
	 *
	 * @return string
	 */
	private function get_entry_text( mixed $value ): string {
		// return scalar values as string.
		if ( is_scalar( $value ) ) {
			return trim( (string) $value );
		}

		// bail if the value is not an array.
		if ( ! is_array( $value ) ) {
			return '';
		}

		// collect the texts of its entries.
		$texts = array();
		foreach ( $value as $entry ) {
			$text = $this->get_entry_text( $entry );
			if ( '' !== $text ) {
				$texts[] = $text;
			}
		}

		// return the resulting text.
		return implode( ', ', $texts );
	}
}

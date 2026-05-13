<?php
/**
 * File for handling a field format.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldFormats;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\FieldFormat_Base;

/**
 * Object to handle this field format
 */
class Thumbnail extends FieldFormat_Base {
	/**
	 * The internal name of the format.
	 *
	 * @var string
	 */
	protected string $name = 'thumbnail';

	/**
	 * Return the value.
	 *
	 * @return string
	 */
	public function get_value(): string {
		// get the value as integer.
		$attachment_id = absint( $this->value );

		// bail if no ID is set.
		if ( 0 === $attachment_id ) {
			return '';
		}

		// return the image.
		return wp_get_attachment_image( $attachment_id );
	}
}

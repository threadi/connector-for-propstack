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
use DateTimeImmutable;
use DateTimeZone;

/**
 * Object to handle this field format
 */
class Date extends FieldFormat_Base {
	/**
	 * The internal name of the format.
	 *
	 * @var string
	 */
	protected string $name = 'date';

	/**
	 * Return the value.
	 *
	 * Only values beginning with a date in the format YYYY-MM-DD are formatted,
	 * any other text (e.g. "by arrangement") is returned as it is.
	 *
	 * @return string
	 */
	public function get_value(): string {
		// bail if the value is not scalar.
		if ( ! is_scalar( $this->value ) ) {
			return '';
		}

		// get the value as string.
		$value = (string) $this->value;

		// bail if the value does not start with a date.
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}/', $value ) ) {
			return $value;
		}

		// get the date without any time zone conversion.
		$timezone = new DateTimeZone( 'UTC' );
		$date     = DateTimeImmutable::createFromFormat( '!Y-m-d', substr( $value, 0, 10 ), $timezone );

		// bail if the date is not valid.
		if ( ! $date instanceof DateTimeImmutable ) {
			return $value;
		}

		// return the formatted date.
		return (string) wp_date( (string) get_option( 'date_format' ), $date->getTimestamp(), $timezone );
	}
}

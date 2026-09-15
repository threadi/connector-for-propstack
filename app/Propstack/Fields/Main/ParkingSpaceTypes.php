<?php
/**
 * File to handle a field.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Fields\Main;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Field_Category_Base;

/**
 * Object to handle a single field.
 */
class ParkingSpaceTypes extends Field_Base {
	/**
	 * The API name of the field.
	 *
	 * @var string
	 */
	protected string $api = 'parking_space_types';

	/**
	 * The internal name of this field.
	 *
	 * @var string
	 */
	protected string $name = 'parking_space_types';

	/**
	 * The WordPress-compatible type for the field (e.g., 'boolean', 'string', 'number', 'array').
	 *
	 * @var string
	 */
	protected string $type = 'array';

	/**
	 * The output format, if different from the type.
	 *
	 * @var string
	 */
	protected string $output_format = 'listing';

	/**
	 * Return the field label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Parking Space Types', 'connector-for-propstack' );
	}

	/**
	 * Return the category of this field.
	 *
	 * @return Field_Category_Base
	 */
	public function get_category(): Field_Category_Base {
		return new \ConnectorForPropstack\Propstack\FieldCategories\Other();
	}

	/**
	 * Return the value from the API response.
	 *
	 * Hint:
	 * Propstack delivers this field inconsistently. Beside a proper list it can contain
	 * a JSON encoded list as a single string, or an array whose entries are such JSON
	 * strings - e.g. array( '["GARAGE"]' ) instead of array( 'GARAGE' ).
	 *
	 * @param int                 $post_id     The post-ID of the object.
	 * @param array<string,mixed> $immo_object The object data from API.
	 *
	 * @return mixed
	 */
	public function get_value_from_api_response( int $post_id, array $immo_object ): mixed {
		// get the value the usual way.
		$value = parent::get_value_from_api_response( $post_id, $immo_object );

		// bail if no value is given.
		if ( empty( $value ) ) {
			return array();
		}

		// use an array in any case.
		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		// collect the resulting entries.
		$list = array();
		foreach ( $value as $entry ) {
			// bail if this entry is not a string.
			if ( ! is_string( $entry ) ) {
				continue;
			}

			// use the entry as it is if it is not a JSON encoded list.
			if ( ! str_starts_with( $entry, '[' ) ) {
				$list[] = $entry;

				continue;
			}

			// decode the JSON encoded list.
			$decoded = json_decode( $entry, true );

			// use the entry as it is if it could not be decoded.
			if ( ! is_array( $decoded ) ) {
				$list[] = $entry;

				continue;
			}

			// add every decoded entry to the list.
			foreach ( $decoded as $decoded_entry ) {
				if ( is_string( $decoded_entry ) ) {
					$list[] = $decoded_entry;
				}
			}
		}

		// return the resulting list without duplicates and gaps in its keys.
		return array_values( array_unique( $list ) );
	}
}

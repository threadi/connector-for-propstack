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
class ParkingSpaceType extends Field_Base {
	/**
	 * The API name of the field.
	 *
	 * @var string
	 */
	protected string $api = 'parking_space_type';

	/**
	 * The internal name of this field.
	 *
	 * @var string
	 */
	protected string $name = 'parking_space_type';

	/**
	 * Return the field label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Parking Space Type', 'connector-for-propstack' );
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
	 * Propstack delivers this field inconsistently: sometimes as a plain string,
	 * sometimes as a JSON encoded list like ["GARAGE"], and sometimes as an array
	 * which itself contains such a JSON string.
	 *
	 * @param int                 $post_id     The post-ID of the object.
	 * @param array<string,mixed> $immo_object The object data from API.
	 *
	 * @return mixed
	 */
	public function get_value_from_api_response( int $post_id, array $immo_object ): mixed {
		// get the value the usual way.
		$value = parent::get_value_from_api_response( $post_id, $immo_object );

		// use the first entry if an array is given.
		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		// bail if the value is not a string.
		if ( ! is_string( $value ) ) {
			return $value;
		}

		// decode the value if it is a JSON encoded list.
		if ( str_starts_with( $value, '[' ) ) {
			$decoded = json_decode( $value, true );

			if ( is_array( $decoded ) ) {
				$value = implode( ', ', $decoded );
			}
		}

		return $value;
	}
}

<?php
/**
 * File for tests against the availability of fields depending on the used API version.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Fields;
use ConnectorForPropstack\Propstack\Fields\Broker\FirstName;
use ConnectorForPropstack\Propstack\Fields\Broker\Name;
use ConnectorForPropstack\Propstack\Fields\Main\ApiResponse;
use ConnectorForPropstack\Propstack\Fields\Main\District;
use ConnectorForPropstack\Propstack\Fields\Main\Floorplans;
use ConnectorForPropstack\Propstack\Fields\Main\HeatingType;
use ConnectorForPropstack\Propstack\Fields\Main\Latitude;
use ConnectorForPropstack\Propstack\Fields\Main\ObjectId;
use ConnectorForPropstack\Propstack\Fields\Main\Price;
use ConnectorForPropstack\Propstack\Fields\Main\Terrace;
use ConnectorForPropstack\Propstack\Fields\Main\UnitId;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against the availability of fields depending on the used API version.
 *
 * The API v2 delivers fewer fields for an object than the API v1. Fields which are not delivered
 * are hidden as long as the API v2 is used.
 */
class FieldsApiVersion extends ConnectorForPropstackTestCase {
	/**
	 * Return fields of objects which the API v2 does not deliver.
	 *
	 * @return array<string,array<int,Field_Base>>
	 */
	public static function get_fields_not_in_api_v2(): array {
		return array(
			'district'   => array( new District() ),
			'terrace'    => array( new Terrace() ),
			'floorplans' => array( new Floorplans() ),
		);
	}

	/**
	 * Return fields which must not be hidden if the API v2 is used.
	 *
	 * @return array<string,array<int,Field_Base>>
	 */
	public static function get_fields_available_with_api_v2(): array {
		return array(
			'price'                  => array( new Price() ),
			'heating type'           => array( new HeatingType() ),
			'object ID'              => array( new ObjectId() ),
			'latitude'               => array( new Latitude() ),
			'API response'           => array( new ApiResponse() ),
			'first name of a broker' => array( new FirstName() ),
			'name of a broker'       => array( new Name() ),
		);
	}

	/**
	 * Test that no field is hidden because of the API version if the API v1 is used.
	 *
	 * @dataProvider get_fields_not_in_api_v2
	 *
	 * @param Field_Base $field The field object.
	 *
	 * @return void
	 */
	public function test_fields_are_not_hidden_with_api_v1( Field_Base $field ): void {
		update_option( 'propstack_connector_api_version', 'v1' );

		$this->assertTrue( Fields::get_instance()->is_field_available_in_api( $field ) );
		$this->assertFalse( $field->hide() );
	}

	/**
	 * Test that fields which the API v2 does not deliver are hidden if the API v2 is used.
	 *
	 * @dataProvider get_fields_not_in_api_v2
	 *
	 * @param Field_Base $field The field object.
	 *
	 * @return void
	 */
	public function test_fields_are_hidden_with_api_v2( Field_Base $field ): void {
		update_option( 'propstack_connector_api_version', 'v2' );

		$this->assertFalse( Fields::get_instance()->is_field_available_in_api( $field ) );
		$this->assertTrue( $field->hide() );
	}

	/**
	 * Test that fields which the API v2 delivers, internal fields and fields of brokers are not hidden if the API v2 is used.
	 *
	 * @dataProvider get_fields_available_with_api_v2
	 *
	 * @param Field_Base $field The field object.
	 *
	 * @return void
	 */
	public function test_available_fields_are_not_hidden_with_api_v2( Field_Base $field ): void {
		update_option( 'propstack_connector_api_version', 'v2' );

		$this->assertTrue( Fields::get_instance()->is_field_available_in_api( $field ) );
		$this->assertFalse( $field->hide() );
	}

	/**
	 * Test that a field which is hidden anyway stays hidden with both API versions.
	 *
	 * @return void
	 */
	public function test_hidden_field_stays_hidden(): void {
		foreach ( array( 'v1', 'v2' ) as $api_version ) {
			update_option( 'propstack_connector_api_version', $api_version );

			$this->assertTrue( ( new UnitId() )->hide(), 'API ' . $api_version );
		}
	}

	/**
	 * Test that the list of fields for the selection in a block does not contain fields the API v2 does not deliver.
	 *
	 * @return void
	 */
	public function test_fields_for_selection_depend_on_api_version(): void {
		// with the API v1 the field is part of the list.
		update_option( 'propstack_connector_api_version', 'v1' );
		$this->assertContains( 'district', array_column( Fields::get_instance()->get_fields_by_request( '', '', true ), 'value' ) );

		// with the API v2 it is not, but the other fields still are.
		update_option( 'propstack_connector_api_version', 'v2' );
		$names = array_column( Fields::get_instance()->get_fields_by_request( '', '', true ), 'value' );
		$this->assertNotContains( 'district', $names );
		$this->assertContains( 'price', $names );
		$this->assertContains( 'heating_type', $names );
	}

	/**
	 * Test that the availability of a field can be changed by a filter.
	 *
	 * @return void
	 */
	public function test_availability_can_be_filtered(): void {
		update_option( 'propstack_connector_api_version', 'v2' );

		$make_available = static fn( bool $available, Field_Base $field ): bool => 'district' === $field->get_name() ? true : $available;
		add_filter( 'cfprop_field_available_in_api', $make_available, 10, 2 );

		$this->assertFalse( ( new District() )->hide() );
		$this->assertTrue( ( new Terrace() )->hide() );

		remove_filter( 'cfprop_field_available_in_api', $make_available );
	}

	/**
	 * Test that every field the API v2 delivers is known only once.
	 *
	 * @return void
	 */
	public function test_list_of_api_v2_fields_is_unique(): void {
		$fields = Fields::get_instance()->get_api_v2_object_fields();

		$this->assertNotEmpty( $fields );
		$this->assertSame( array_values( array_unique( $fields ) ), $fields );
	}

	/**
	 * Test that every field of the complete object from the API v2 is in the list of delivered fields.
	 *
	 * @return void
	 */
	public function test_list_of_api_v2_fields_matches_the_api(): void {
		$data = json_decode( (string) file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'properties_complete.json' ), true );

		foreach ( array_keys( $data['data'][0] ) as $field_name ) {
			$this->assertTrue( Fields::get_instance()->is_api_v2_object_field( (string) $field_name ), 'Missing field ' . $field_name );
		}
	}
}

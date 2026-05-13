<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\Fields.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\Fields.
 */
class Fields extends ConnectorForPropstackTestCase {
	/**
	 * Test if all field classes, which are configured are also available.
	 *
	 * @return void
	 */
	public function test_fields_availability(): void {
		// test it.
		$fields = \ConnectorForPropstack\Propstack\Fields::get_instance()->get_fields();
		$this->assertIsArray( $fields );
		$this->assertNotEmpty( $fields );
		foreach( $fields as $field_class_name ) {
			$this->assertTrue( class_exists( $field_class_name ) );
		}
	}

	/**
	 * Test response for get files if no files are imported.
	 *
	 * @return void
	 */
	public function test_list_of_field_objects(): void {
		// test it.
		$fields = \ConnectorForPropstack\Propstack\Fields::get_instance()->get_fields_as_objects();
		$this->assertIsArray( $fields );
		$this->assertNotEmpty( $fields );
	}

	/**
	 * Return the list of our fields.
	 *
	 * @return iterable
	 */
	public function get_fields(): iterable {
		$fields = \ConnectorForPropstack\Propstack\Fields::get_instance()->get_fields_as_objects();
		foreach( $fields as $field ) {
			yield array( $field );
		}
	}

	/**
	 * Test the name of each field.
	 *
	 * @dataProvider get_fields
	 *
	 * @param Field_Base $obj Object of the taxonomy.
	 *
	 * @return void
	 */
	public function test_field_name( \ConnectorForPropstack\Propstack\Field_Base $obj ): void {
		$this->assertIsString( $obj->get_name() );
		if( $obj->get_api() !== 'custom_fields' ) {
			$this->assertNotEmpty( $obj->get_name() );
		}
	}

	/**
	 * Test the API name of each field.
	 *
	 * @dataProvider get_fields
	 *
	 * @param Field_Base $obj Object of the taxonomy.
	 *
	 * @return void
	 */
	public function test_field_api_name( \ConnectorForPropstack\Propstack\Field_Base $obj ): void {
		$this->assertIsString( $obj->get_api() );
		$this->assertNotEmpty( $obj->get_api() );
	}

	/**
	 * Test the type of each field.
	 *
	 * @dataProvider get_fields
	 *
	 * @param Field_Base $obj Object of the taxonomy.
	 *
	 * @return void
	 */
	public function test_field_type( \ConnectorForPropstack\Propstack\Field_Base $obj ): void {
		$this->assertIsString( $obj->get_type() );
		$this->assertNotEmpty( $obj->get_type() );
	}

	/**
	 * Test the label of each field.
	 *
	 * @dataProvider get_fields
	 *
	 * @param Field_Base $obj Object of the taxonomy.
	 *
	 * @return void
	 */
	public function test_field_label( \ConnectorForPropstack\Propstack\Field_Base $obj ): void {
		$this->assertIsString( $obj->get_label() );
		if( $obj->get_api() !== 'custom_fields' ) {
			$this->assertNotEmpty( $obj->get_label() );
		}
	}
}

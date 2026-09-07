<?php
/**
 * File for tests against every registered field type.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\FieldTypes;

use ConnectorForPropstack\Propstack\FieldType_Base;
use ConnectorForPropstack\Propstack\FieldTypes;
use PHPUnit\Framework\TestCase;

/**
 * Object for tests against every registered field type.
 *
 * The single field type tests pin the concrete output. This one pins the contract
 * itself: no field type may throw or emit a warning, whatever the Propstack API
 * delivered. It walks the registry, so a newly added field type is covered without
 * touching this file - which is exactly the case the TypeError in StringField was.
 */
class FieldTypeContractTest extends TestCase {
	/**
	 * Test that no field type throws for an unexpected value.
	 *
	 * @dataProvider provide_field_type_and_value
	 *
	 * @param FieldType_Base $field_type The field type object.
	 * @param mixed          $value      The value as delivered by the Propstack API.
	 *
	 * @return void
	 */
	public function test_get_value_survives_unexpected_value( FieldType_Base $field_type, mixed $value ): void {
		$field_type->set_value( $value );

		$output = $field_type->get_value();

		// the output must be usable for output or storage, never an object.
		$this->assertTrue(
			is_scalar( $output ) || is_array( $output ),
			sprintf( '%s::get_value() returned %s.', $field_type::class, get_debug_type( $output ) )
		);
	}

	/**
	 * Test that no field type throws while cleaning a value during import.
	 *
	 * @dataProvider provide_field_type_and_value
	 *
	 * @param FieldType_Base $field_type The field type object.
	 * @param mixed          $value      The value as delivered by the Propstack API.
	 *
	 * @return void
	 */
	public function test_get_cleaned_value_survives_unexpected_value( FieldType_Base $field_type, mixed $value ): void {
		$field_type->set_value( $value );

		$field_type->get_cleaned_value();

		// reaching this line without a throwable is the assertion.
		$this->assertTrue( true );
	}

	/**
	 * Provide every registered field type combined with every hostile value.
	 *
	 * @return array<string,array<int,mixed>>
	 */
	public static function provide_field_type_and_value(): array {
		$values = array(
			'null'         => null,
			'empty string' => '',
			'string'       => 'Wohnung in Leipzig',
			'integer'      => 42,
			'float'        => 1.5,
			'true'         => true,
			'false'        => false,
			'list'         => array( 'a', 'b' ),
			'assoc array'  => array( 'de' => 'Text' ),
			'nested array' => array( 'de' => array( 'title' => 'Text' ) ),
			'empty array'  => array(),
			'object'       => new \stdClass(),
		);

		// build one data set per field type and value.
		$data = array();
		foreach ( FieldTypes::get_instance()->get_field_types_as_objects() as $field_type ) {
			foreach ( $values as $value_name => $value ) {
				// use a fresh object per data set so no value leaks between them.
				$class_name = $field_type::class;

				$data[ $field_type->get_name() . ' with ' . $value_name ] = array( new $class_name(), $value );
			}
		}

		return $data;
	}
}

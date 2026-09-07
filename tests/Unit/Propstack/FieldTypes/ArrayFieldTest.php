<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\FieldTypes\ArrayField.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\FieldTypes;

use PHPUnit\Framework\TestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\FieldTypes\ArrayField.
 *
 * This field type already guards its input via is_array(). The tests pin that
 * behaviour so a future refactoring cannot reintroduce a TypeError.
 */
class ArrayFieldTest extends TestCase {
	/**
	 * Test that get_value() always returns an array, whatever the API delivered.
	 *
	 * @dataProvider provide_values
	 *
	 * @param mixed              $value    The value as delivered by the Propstack API.
	 * @param array<int|string,mixed> $expected The expected return value.
	 *
	 * @return void
	 */
	public function test_get_value_always_returns_array( mixed $value, array $expected ): void {
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\ArrayField();
		$field->set_value( $value );

		$this->assertSame( $expected, $field->get_value() );
	}

	/**
	 * Provide the values to test.
	 *
	 * @return array<string,array<int,mixed>>
	 */
	public static function provide_values(): array {
		return array(
			'list'         => array( array( 'a', 'b' ), array( 'a', 'b' ) ),
			'assoc array'  => array( array( 'de' => 'Text' ), array( 'de' => 'Text' ) ),
			'empty array'  => array( array(), array() ),
			'string'       => array( 'Wohnung in Leipzig', array() ),
			'empty string' => array( '', array() ),
			'null'         => array( null, array() ),
			'integer'      => array( 42, array() ),
			'float'        => array( 1.5, array() ),
			'true'         => array( true, array() ),
			'false'        => array( false, array() ),
			'object'       => array( new \stdClass(), array() ),
		);
	}
}

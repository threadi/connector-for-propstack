<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\FieldTypes\NumberField.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\FieldTypes;

use PHPUnit\Framework\TestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\FieldTypes\NumberField.
 *
 * Same situation as FloatField: number_format() requires a float and the empty()
 * check does not catch a non-empty array. The tests require the additional
 * is_numeric() guard in get_value().
 */
class NumberFieldTest extends TestCase {
	/**
	 * Test that get_value() always returns a formatted string or an empty string.
	 *
	 * @dataProvider provide_values
	 *
	 * @param mixed  $value    The value as delivered by the Propstack API.
	 * @param string $expected The expected return value.
	 *
	 * @return void
	 */
	public function test_get_value_always_returns_string( mixed $value, string $expected ): void {
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\NumberField();
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
			'integer'         => array( 1234, '1.234' ),
			'numeric string'  => array( '1234', '1.234' ),
			'float rounds up' => array( 1234.5, '1.235' ),
			'small integer'   => array( 7, '7' ),
			'zero'            => array( 0, '' ),
			'zero as string'  => array( '0', '' ),
			'empty string'    => array( '', '' ),
			'null'            => array( null, '' ),
			'non numeric'     => array( 'keine Zahl', '' ),
			'true'            => array( true, '' ),
			'false'           => array( false, '' ),
			'list'            => array( array( 'a', 'b' ), '' ),
			'assoc array'     => array( array( 'de' => 'Text' ), '' ),
			'empty array'     => array( array(), '' ),
			'object'          => array( new \stdClass(), '' ),
		);
	}
}

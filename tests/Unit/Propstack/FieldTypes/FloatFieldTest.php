<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\FieldTypes\FloatField.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\FieldTypes;

use PHPUnit\Framework\TestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\FieldTypes\FloatField.
 *
 * number_format() requires a float, so any non-numeric value from the API ends in a
 * TypeError. The empty() check alone does not prevent this: a non-empty array passes
 * it. The tests require the additional is_numeric() guard in get_value().
 */
class FloatFieldTest extends TestCase {
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
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\FloatField();
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
			'float'           => array( 1234.5, '1.234,50' ),
			'numeric string'  => array( '1234.5', '1.234,50' ),
			'integer'         => array( 1234, '1.234,00' ),
			'negative float'  => array( -12.34, '-12,34' ),
			'zero'            => array( 0, '' ),
			'zero as float'   => array( 0.0, '' ),
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

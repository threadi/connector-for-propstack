<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\FieldTypes\IntegerField.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\FieldTypes;

use PHPUnit\Framework\TestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\FieldTypes\IntegerField.
 *
 * get_value() returns an int for a usable value and an empty string otherwise, so the
 * assertions use assertSame() to also cover the type. absint() silently turns a
 * non-empty array into 1 and an object into 1 with a PHP warning, which is why the
 * tests require the is_scalar() guard in get_value().
 */
class IntegerFieldTest extends TestCase {
	/**
	 * Test that get_value() returns an int or an empty string.
	 *
	 * @dataProvider provide_values
	 *
	 * @param mixed      $value    The value as delivered by the Propstack API.
	 * @param int|string $expected The expected return value.
	 *
	 * @return void
	 */
	public function test_get_value_returns_integer_or_empty_string( mixed $value, int|string $expected ): void {
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\IntegerField();
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
			'integer'         => array( 42, 42 ),
			'numeric string'  => array( '42', 42 ),
			'float truncates' => array( 3.9, 3 ),
			'negative'        => array( -5, 5 ),
			'zero'            => array( 0, '' ),
			'zero as string'  => array( '0', '' ),
			'empty string'    => array( '', '' ),
			'null'            => array( null, '' ),
			'non numeric'     => array( 'keine Zahl', '' ),
			'true'            => array( true, 1 ),
			'false'           => array( false, '' ),
			'list'            => array( array( 'a', 'b' ), '' ),
			'assoc array'     => array( array( 'de' => 'Text' ), '' ),
			'empty array'     => array( array(), '' ),
			'object'          => array( new \stdClass(), '' ),
		);
	}
}

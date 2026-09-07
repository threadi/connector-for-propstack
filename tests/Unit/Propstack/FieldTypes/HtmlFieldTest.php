<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\FieldTypes\HtmlField.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\FieldTypes;

use PHPUnit\Framework\TestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\FieldTypes\HtmlField.
 *
 * get_value() passes the raw value into nl2br(), which needs a string. An array ends
 * in a TypeError, null in a deprecation notice. The tests require the is_scalar()
 * guard in get_value().
 */
class HtmlFieldTest extends TestCase {
	/**
	 * Test that get_value() never receives a non-scalar value.
	 *
	 * @dataProvider provide_values
	 *
	 * @param mixed  $value    The value as delivered by the Propstack API.
	 * @param string $expected The expected return value.
	 *
	 * @return void
	 */
	public function test_get_value_always_returns_string( mixed $value, string $expected ): void {
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\HtmlField();
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
			'plain text'     => array( 'Wohnung in Leipzig', 'Wohnung in Leipzig' ),
			'single break'   => array( "Zeile 1\nZeile 2", "Zeile 1<br />\nZeile 2" ),
			'windows break'  => array( "Zeile 1\r\nZeile 2", "Zeile 1<br />\r\nZeile 2" ),
			'existing html'  => array( '<strong>fett</strong>', '<strong>fett</strong>' ),
			'empty string'   => array( '', '' ),
			'integer'        => array( 42, '42' ),
			'null'           => array( null, '' ),
			'list'           => array( array( 'a', 'b' ), '' ),
			'assoc array'    => array( array( 'de' => 'Text' ), '' ),
			'empty array'    => array( array(), '' ),
			'object'         => array( new \stdClass(), '' ),
		);
	}
}

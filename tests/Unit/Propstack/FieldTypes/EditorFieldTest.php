<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\FieldTypes\EditorField.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\FieldTypes;

use PHPUnit\Framework\TestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\FieldTypes\EditorField.
 *
 * get_value() strips a surrounding paragraph and then converts line breaks. Both
 * preg_replace() and nl2br() need a string, so a non-scalar value from the API ends in
 * a TypeError. The tests require the is_scalar() guard in get_value().
 */
class EditorFieldTest extends TestCase {
	/**
	 * Test that get_value() strips the wrapping paragraph and handles any input type.
	 *
	 * @dataProvider provide_values
	 *
	 * @param mixed  $value    The value as delivered by the Propstack API.
	 * @param string $expected The expected return value.
	 *
	 * @return void
	 */
	public function test_get_value_always_returns_string( mixed $value, string $expected ): void {
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\EditorField();
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
			'wrapped paragraph'    => array( '<p>Wohnung in Leipzig</p>', 'Wohnung in Leipzig' ),
			'paragraph with attrs' => array( '<p class="lead">Text</p>', 'Text' ),
			'no paragraph'         => array( 'Wohnung in Leipzig', 'Wohnung in Leipzig' ),
			'line break inside'    => array( "<p>Zeile 1\nZeile 2</p>", "Zeile 1<br />\nZeile 2" ),
			'inner markup kept'    => array( '<p>Text mit <strong>fett</strong></p>', 'Text mit <strong>fett</strong>' ),
			'empty string'         => array( '', '' ),
			'integer'              => array( 42, '42' ),
			'null'                 => array( null, '' ),
			'list'                 => array( array( 'a', 'b' ), '' ),
			'assoc array'          => array( array( 'de' => 'Text' ), '' ),
			'empty array'          => array( array(), '' ),
			'object'               => array( new \stdClass(), '' ),
		);
	}
}

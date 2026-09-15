<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\FieldTypes\StringField.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\FieldTypes;

use PHPUnit\Framework\TestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\FieldTypes\StringField.
 */
class StringFieldTest extends TestCase {
	/**
	 * @dataProvider provide_values
	 */
	public function test_get_value_always_returns_string( mixed $value, string $expected ): void {
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\StringField();
		$field->set_value( $value );

		$this->assertSame( $expected, $field->get_value() );
	}

	public static function provide_values(): array {
		return array(
			'string'       => array( 'Wohnung in Leipzig', 'Wohnung in Leipzig' ),
			'empty string' => array( '', '' ),
			'null'         => array( null, '' ),
			'integer'      => array( 42, '42' ),
			'float'        => array( 1.5, '1.5' ),
			'true'         => array( true, '1' ),
			'false'        => array( false, '' ),
			'list'         => array( array( 'a', 'b' ), '' ),
			'assoc array'  => array( array( 'de' => 'Text' ), '' ),
			'empty array'  => array( array(), '' ),
			'object'       => array( new \stdClass(), '' ),
		);
	}
}

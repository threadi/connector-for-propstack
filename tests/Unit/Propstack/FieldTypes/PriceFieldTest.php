<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\FieldTypes\PriceField.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\FieldTypes;

use PHPUnit\Framework\TestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\FieldTypes\PriceField.
 *
 * The most used numeric field type of the plugin. It appends the currency to the
 * formatted number, so an empty value must not result in a lone currency sign.
 * The tests require the additional is_numeric() guard in get_value().
 */
class PriceFieldTest extends TestCase {
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
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\PriceField();
		$field->set_value( $value );

		$this->assertSame( $expected, $field->get_value() );
	}

	/**
	 * Test that no currency sign is rendered without a value.
	 *
	 * @return void
	 */
	public function test_get_value_does_not_render_currency_without_value(): void {
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\PriceField();
		$field->set_value( array( 'de' => 'Text' ) );

		$this->assertStringNotContainsString( '&euro;', $field->get_value() );
	}

	/**
	 * Provide the values to test.
	 *
	 * @return array<string,array<int,mixed>>
	 */
	public static function provide_values(): array {
		return array(
			'integer'        => array( 250000, '250.000 &euro;' ),
			'numeric string' => array( '250000', '250.000 &euro;' ),
			'float rounds'   => array( 1234.5, '1.235 &euro;' ),
			'zero'           => array( 0, '' ),
			'zero as string' => array( '0', '' ),
			'empty string'   => array( '', '' ),
			'null'           => array( null, '' ),
			'non numeric'    => array( 'auf Anfrage', '' ),
			'true'           => array( true, '' ),
			'false'          => array( false, '' ),
			'list'           => array( array( 'a', 'b' ), '' ),
			'assoc array'    => array( array( 'de' => 'Text' ), '' ),
			'empty array'    => array( array(), '' ),
			'object'         => array( new \stdClass(), '' ),
		);
	}
}

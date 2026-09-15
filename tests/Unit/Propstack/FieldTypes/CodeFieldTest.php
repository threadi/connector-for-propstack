<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\FieldTypes\CodeField.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\FieldTypes;

use PHPUnit\Framework\TestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\FieldTypes\CodeField.
 *
 * This field type already guards its input via is_array(). It renders the value as
 * pretty printed JSON inside a <code>-element, so the assertions check the payload
 * instead of the complete markup, which depends on the active translation.
 */
class CodeFieldTest extends TestCase {
	/**
	 * Test that a non-array value results in an empty string.
	 *
	 * @dataProvider provide_non_array_values
	 *
	 * @param mixed $value The value as delivered by the Propstack API.
	 *
	 * @return void
	 */
	public function test_get_value_returns_empty_string_for_non_array( mixed $value ): void {
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\CodeField();
		$field->set_value( $value );

		$this->assertSame( '', $field->get_value() );
	}

	/**
	 * Provide the non-array values to test.
	 *
	 * @return array<string,array<int,mixed>>
	 */
	public static function provide_non_array_values(): array {
		return array(
			'string'       => array( 'Wohnung in Leipzig' ),
			'empty string' => array( '' ),
			'null'         => array( null ),
			'integer'      => array( 42 ),
			'float'        => array( 1.5 ),
			'true'         => array( true ),
			'false'        => array( false ),
			'object'       => array( new \stdClass() ),
		);
	}

	/**
	 * Test that an array is rendered as JSON inside a code element.
	 *
	 * @return void
	 */
	public function test_get_value_renders_array_as_json(): void {
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\CodeField();
		$field->set_value( array( 'city' => 'Leipzig' ) );

		$output = $field->get_value();

		$this->assertStringStartsWith( '<code ', $output );
		$this->assertStringEndsWith( '</code>', $output );
		$this->assertStringContainsString( 'data-copied-label=', $output );
		$this->assertStringContainsString( 'Leipzig', $output );
	}

	/**
	 * Test that an empty array does not produce a broken code element.
	 *
	 * @return void
	 */
	public function test_get_value_handles_empty_array(): void {
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\CodeField();
		$field->set_value( array() );

		$this->assertStringContainsString( '[]', $field->get_value() );
	}
}

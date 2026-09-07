<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\FieldTypes\BooleanField.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\FieldTypes;

use PHPUnit\Framework\TestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\FieldTypes\BooleanField.
 *
 * This field type does not produce a TypeError, but absint() converts a non-empty
 * array to 1 and an object to 1 (with a PHP warning). Both would show the "yes"
 * icon for a value which is not a boolean at all. The tests require the is_scalar()
 * guard in get_value().
 */
class BooleanFieldTest extends TestCase {
	/**
	 * The markup for a true value.
	 *
	 * @var string
	 */
	private const YES = '<span class="dashicons dashicons-yes"></span>';

	/**
	 * The markup for a false value.
	 *
	 * @var string
	 */
	private const NO = '<span class="dashicons dashicons-no"></span>';

	/**
	 * Test that get_value() only returns the "yes" markup for true boolean values.
	 *
	 * @dataProvider provide_values
	 *
	 * @param mixed  $value    The value as delivered by the Propstack API.
	 * @param string $expected The expected return value.
	 *
	 * @return void
	 */
	public function test_get_value_returns_expected_markup( mixed $value, string $expected ): void {
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\BooleanField();
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
			'true'         => array( true, self::YES ),
			'false'        => array( false, self::NO ),
			'integer one'  => array( 1, self::YES ),
			'integer zero' => array( 0, self::NO ),
			'string one'   => array( '1', self::YES ),
			'string zero'  => array( '0', self::NO ),
			'string text'  => array( 'yes', self::NO ),
			'empty string' => array( '', self::NO ),
			'null'         => array( null, self::NO ),
			'list'         => array( array( 'a', 'b' ), self::NO ),
			'empty array'  => array( array(), self::NO ),
			'object'       => array( new \stdClass(), self::NO ),
		);
	}
}

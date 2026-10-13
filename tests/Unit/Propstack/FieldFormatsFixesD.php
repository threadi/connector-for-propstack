<?php
/**
 * File for tests against the field formats and field types changed in 2.1.0.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use PHPUnit\Framework\TestCase;

/**
 * Object for tests against the field formats and field types changed in 2.1.0.
 */
class FieldFormatsFixesD extends TestCase {
	/**
	 * Test that the email format escapes the value.
	 *
	 * @return void
	 */
	public function test_email_is_escaped(): void {
		$format = new \ConnectorForPropstack\Propstack\FieldFormats\Email();
		$format->set_value( 'info@example.com"><script>alert(1)</script>' );

		$this->assertStringNotContainsString( '<script>', $format->get_value() );
	}

	/**
	 * Test that the phone number format uses only digits and plus sign in the link.
	 *
	 * @return void
	 */
	public function test_phone_number_link(): void {
		$format = new \ConnectorForPropstack\Propstack\FieldFormats\PhoneNumber();
		$format->set_value( '+49 341 / 123-45' );

		$this->assertSame( '<a href="tel:+4934112345">+49 341 / 123-45</a>', $format->get_value() );
	}

	/**
	 * Test that the listing format escapes its entries and flattens nested lists.
	 *
	 * @return void
	 */
	public function test_listing_escapes_and_flattens(): void {
		$format = new \ConnectorForPropstack\Propstack\FieldFormats\Listing();
		$format->set_value( array( '<b>Parkett</b>', array( 'Fliesen', 'Laminat' ), '' ) );

		$this->assertSame( '</p><ul><li>&lt;b&gt;Parkett&lt;/b&gt;</li><li>Fliesen, Laminat</li></ul><p>', $format->get_value() );
	}

	/**
	 * Test that the date format keeps text which is not a date.
	 *
	 * @return void
	 */
	public function test_date_keeps_text(): void {
		$format = new \ConnectorForPropstack\Propstack\FieldFormats\Date();
		$format->set_value( 'nach Vereinbarung' );

		$this->assertSame( 'nach Vereinbarung', $format->get_value() );
	}

	/**
	 * Test that the date format is registered with its own name.
	 *
	 * @return void
	 */
	public function test_date_format_is_registered(): void {
		$format = \ConnectorForPropstack\Propstack\FieldFormats::get_instance()->get_field_format_by_name( 'date' );

		$this->assertInstanceOf( \ConnectorForPropstack\Propstack\FieldFormats\Date::class, $format );
	}

	/**
	 * Test the amount of decimals used for numbers.
	 *
	 * @return void
	 */
	public function test_number_decimals(): void {
		$this->assertSame( 0, \ConnectorForPropstack\Propstack\FieldTypes\NumberField::get_decimals( 62.0 ) );
		$this->assertSame( 1, \ConnectorForPropstack\Propstack\FieldTypes\NumberField::get_decimals( 62.5 ) );
		$this->assertSame( 2, \ConnectorForPropstack\Propstack\FieldTypes\NumberField::get_decimals( 62.25 ) );
	}

	/**
	 * Test that HTML fields remove scripts during import.
	 *
	 * @return void
	 */
	public function test_html_field_removes_scripts(): void {
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\HtmlField();
		$field->set_value( '<p>Text</p><script>alert(1)</script>' );

		$this->assertStringNotContainsString( '<script>', (string) $field->get_cleaned_value() );
	}

	/**
	 * Test that the editor fields remove scripts during import.
	 *
	 * @return void
	 */
	public function test_editor_field_removes_scripts(): void {
		$field = new \ConnectorForPropstack\Propstack\FieldTypes\EditorField();
		$field->set_value( 'Text<script>alert(1)</script>' );

		$this->assertStringNotContainsString( '<script>', (string) $field->get_cleaned_value() );
	}

	/**
	 * Test that internal fields are hidden in the frontend.
	 *
	 * @return void
	 */
	public function test_internal_fields_are_hidden_in_frontend(): void {
		$fields = array(
			new \ConnectorForPropstack\Propstack\Fields\Main\Renter(),
			new \ConnectorForPropstack\Propstack\Fields\Main\TotalCommission(),
			new \ConnectorForPropstack\Propstack\Fields\Main\ValuationPrice(),
			new \ConnectorForPropstack\Propstack\Fields\Main\ValuationPriceFrom(),
			new \ConnectorForPropstack\Propstack\Fields\Main\ValuationPriceTo(),
		);
		foreach ( $fields as $field ) {
			$this->assertTrue( $field->hide_in_frontend(), $field->get_name() );
		}

		// the sold price is used by brokers in the frontend.
		$this->assertFalse( ( new \ConnectorForPropstack\Propstack\Fields\Main\SoldPrice() )->hide_in_frontend() );
	}
}

<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\Custom_Fields.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Propstack\Custom_Fields;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\Custom_Fields.
 */
class CustomFields extends ConnectorForPropstackTestCase {
	/**
	 * Prepare the definitions of the custom fields for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		update_option(
			Custom_Fields::OPTION,
			array(
				'my_text'     => array( 'name' => 'my_text', 'label' => 'My text', 'type' => 'String', 'unit' => '', 'options' => array(), 'guessed' => false ),
				'my_number'   => array( 'name' => 'my_number', 'label' => 'My number', 'type' => 'Number', 'unit' => 'm²', 'options' => array(), 'guessed' => false ),
				'my_dropdown' => array( 'name' => 'my_dropdown', 'label' => 'My dropdown', 'type' => 'Dropdown', 'unit' => '', 'options' => array( '1' => 'One', '2' => 'Two' ), 'guessed' => false ),
				'my_multi'    => array( 'name' => 'my_multi', 'label' => 'My multi', 'type' => 'Multiselect', 'unit' => '', 'options' => array( '3' => 'Three', '4' => 'Four' ), 'guessed' => false ),
				'my_checkbox' => array( 'name' => 'my_checkbox', 'label' => 'My checkbox', 'type' => 'Boolean', 'unit' => '', 'options' => array(), 'guessed' => false ),
			)
		);

		// forget the definitions of previous tests.
		Custom_Fields::get_instance()->reset_cache();
	}

	/**
	 * Remove the definitions after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		delete_option( Custom_Fields::OPTION );
		Custom_Fields::get_instance()->reset_cache();
		parent::tear_down();
	}

	/**
	 * Test the meta key of a custom field.
	 *
	 * @return void
	 */
	public function test_meta_key(): void {
		$this->assertEquals( 'cfprop_cf_my_text', Custom_Fields::get_instance()->get_meta_key( 'my_text' ) );
	}

	/**
	 * Test that the values of API v1 and API v2 are saved in the same format.
	 *
	 * @return void
	 */
	public function test_same_values_for_both_api_versions(): void {
		$v1 = array(
			'my_text'     => array( 'value' => 'Text', 'pretty_value' => 'Text' ),
			'my_number'   => array( 'value' => 42.3, 'pretty_value' => '42,3' ),
			'my_dropdown' => array( 'value' => 2, 'pretty_value' => 'Two' ),
			'my_multi'    => array( 'value' => array( 3, 4 ), 'pretty_value' => 'Three, Four' ),
			'my_checkbox' => array( 'value' => false, 'pretty_value' => 'Nein' ),
		);
		$v2 = array(
			'my_text'     => 'Text',
			'my_number'   => 42.3,
			'my_dropdown' => 2,
			'my_multi'    => array( 3, 4 ),
			'my_checkbox' => false,
		);

		// get the values of a new object.
		$values_v1 = Custom_Fields::get_instance()->get_values_for_import( array( 'custom_fields' => $v1 ), 1, true, array() );
		$values_v2 = Custom_Fields::get_instance()->get_values_for_import( array( 'custom_fields' => $v2 ), 2, true, array() );

		// test them.
		$this->assertEquals( 'Two', $values_v1['cfprop_cf_my_dropdown'] );
		$this->assertEquals( array( 'Three', 'Four' ), $values_v1['cfprop_cf_my_multi'] );
		$this->assertEquals( '0', $values_v1['cfprop_cf_my_checkbox'] );
		$this->assertEquals( 2, $values_v1['cfprop_cf_my_dropdown_raw'] );
		foreach ( array( 'cfprop_cf_my_text', 'cfprop_cf_my_number', 'cfprop_cf_my_dropdown', 'cfprop_cf_my_multi', 'cfprop_cf_my_checkbox', Custom_Fields::LIST_META_KEY ) as $meta_key ) {
			$this->assertEquals( $values_v1[ $meta_key ], $values_v2[ $meta_key ] );
		}
	}

	/**
	 * Test that empty values are not saved.
	 *
	 * @return void
	 */
	public function test_empty_values(): void {
		$values = Custom_Fields::get_instance()->get_values_for_import(
			array(
				'custom_fields' => array(
					'my_text'     => array( 'value' => null, 'pretty_value' => null ),
					'my_checkbox' => array( 'value' => null, 'pretty_value' => 'Nein' ),
				),
			),
			1,
			true,
			array()
		);
		$this->assertArrayNotHasKey( 'cfprop_cf_my_text', $values );
		$this->assertArrayNotHasKey( 'cfprop_cf_my_checkbox', $values );
		$this->assertEquals( array(), $values[ Custom_Fields::LIST_META_KEY ] );
	}

	/**
	 * Test the formatting of values.
	 *
	 * @return void
	 */
	public function test_format_value(): void {
		$custom_fields = Custom_Fields::get_instance();
		$this->assertEquals( 'Two', $custom_fields->format_value( $custom_fields->get_definition( 'my_dropdown' ), 'Two' ) );
		$this->assertStringContainsString( 'm²', $custom_fields->format_value( $custom_fields->get_definition( 'my_number' ), '42.3' ) );
		$this->assertStringContainsString( '<li>Three</li>', $custom_fields->format_value( $custom_fields->get_definition( 'my_multi' ), array( 'Three', 'Four' ) ) );
		$this->assertEquals( '&lt;b&gt;', $custom_fields->format_value( $custom_fields->get_definition( 'my_text' ), '<b>' ) );
	}

	/**
	 * Test that custom fields are hidden and not part of the object fields without the Pro plugin.
	 *
	 * @return void
	 */
	public function test_hidden_by_default(): void {
		$custom_fields = Custom_Fields::get_instance();
		$this->assertTrue( $custom_fields->is_hidden( 'my_text' ) );
		$this->assertEmpty( $custom_fields->get_fields_for_output() );
	}

	/**
	 * Test that the values in the format up to version 2.0.x are removed during the import of an object.
	 *
	 * @return void
	 */
	public function test_remove_old_format(): void {
		// create an object with the old format.
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, Custom_Fields::OLD_LIST_META_KEY, array( 'my_text' ) );
		update_post_meta( $post_id, 'my_text_pretty_value', 'Old' );

		// import it.
		Custom_Fields::get_instance()->get_values_for_import( array( 'custom_fields' => array( 'my_text' => 'New' ) ), $post_id, false, array() );

		// test it.
		$this->assertFalse( metadata_exists( 'post', $post_id, Custom_Fields::OLD_LIST_META_KEY ) );
		$this->assertFalse( metadata_exists( 'post', $post_id, 'my_text_pretty_value' ) );
	}
}

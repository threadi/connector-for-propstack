<?php
/**
 * File for tests against the prevent-import-filters in \ConnectorForPropstack\Propstack\ImmoObjects.
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\ImmoObjects;

use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against the prevent-import-filters in \ConnectorForPropstack\Propstack\ImmoObjects.
 *
 * These filters decide for every single object whether it is imported or skipped.
 * They are pure input/output methods, so they can be tested without any HTTP request.
 */
class PreventImport extends ConnectorForPropstackTestCase {
	/**
	 * The object to test.
	 *
	 * @var ImmoObjects
	 */
	private ImmoObjects $immo_objects;

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// get the object to test.
		$this->immo_objects = ImmoObjects::get_instance();

		// reset the filter settings so each test starts without restrictions.
		update_option( 'propstack_connector_import_states', array() );
		update_option( 'propstack_connector_import_broker', array() );
		update_option( 'propstack_connector_import_marketing_type', array() );
		update_option( 'propstack_connector_import_object_type', array() );
		update_option( 'propstack_connector_import_property_type', array() );
	}

	/**
	 * Return a minimal but complete object as the API would deliver it.
	 *
	 * @param array<string,mixed> $overrides Values to overwrite in the returned object.
	 *
	 * @return array<string,mixed>
	 */
	private function get_object( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'    => 42,
				'name'  => 'Musterstraße 1 Hinterhaus',
				'title' => 'Musterhaus',
			),
			$overrides
		);
	}

	/**
	 * Test that a complete object is not prevented from import.
	 *
	 * @return void
	 */
	public function test_complete_object_is_not_prevented(): void {
		$this->assertFalse( $this->immo_objects->prevent_import_by_missing_fields( false, $this->get_object() ) );
	}

	/**
	 * Test that a missing ID prevents the import.
	 *
	 * @return void
	 */
	public function test_missing_id_prevents_import(): void {
		$immo_object = $this->get_object();
		unset( $immo_object['id'] );

		$this->assertTrue( $this->immo_objects->prevent_import_by_missing_fields( false, $immo_object ) );
	}

	/**
	 * Test that a missing name prevents the import.
	 *
	 * @return void
	 */
	public function test_missing_name_prevents_import(): void {
		$immo_object = $this->get_object();
		unset( $immo_object['name'] );

		$this->assertTrue( $this->immo_objects->prevent_import_by_missing_fields( false, $immo_object ) );
	}

	/**
	 * Test that a missing title prevents the import.
	 *
	 * @return void
	 */
	public function test_missing_title_prevents_import(): void {
		$immo_object = $this->get_object();
		unset( $immo_object['title'] );

		$this->assertTrue( $this->immo_objects->prevent_import_by_missing_fields( false, $immo_object ) );
	}

	/**
	 * Test that an already set prevent-marker is passed through untouched.
	 *
	 * @return void
	 */
	public function test_missing_fields_passes_through_existing_marker(): void {
		$this->assertTrue( $this->immo_objects->prevent_import_by_missing_fields( true, $this->get_object() ) );
	}

	/**
	 * Test that an object in state "Vermarktung" is imported (API v1).
	 *
	 * Hint: the free version imports objects in exactly this state only. This is
	 * intended behaviour, so this test protects it against accidental changes.
	 *
	 * @return void
	 */
	public function test_state_v1_allows_active_marketing(): void {
		$immo_object = $this->get_object(
			array(
				'property_status' => array(
					'id'   => 222051,
					'name' => 'Vermarktung',
				),
			)
		);

		$this->assertFalse( $this->immo_objects->prevent_import_by_state( false, $immo_object ) );
	}

	/**
	 * Test that an object in any other state is skipped (API v1).
	 *
	 * @return void
	 */
	public function test_state_v1_prevents_other_states(): void {
		$immo_object = $this->get_object(
			array(
				'property_status' => array(
					'id'   => 222052,
					'name' => 'Archiviert',
				),
			)
		);

		$this->assertTrue( $this->immo_objects->prevent_import_by_state( false, $immo_object ) );
	}

	/**
	 * Test that an object in state "Vermarktung" is imported (API v2).
	 *
	 * @return void
	 */
	public function test_state_v2_allows_active_marketing(): void {
		$immo_object = $this->get_object( array( 'property_status_id' => 'Vermarktung' ) );

		$this->assertFalse( $this->immo_objects->prevent_import_by_state( false, $immo_object ) );
	}

	/**
	 * Test that an object in any other state is skipped (API v2).
	 *
	 * @return void
	 */
	public function test_state_v2_prevents_other_states(): void {
		$immo_object = $this->get_object( array( 'property_status_id' => 'Archiviert' ) );

		$this->assertTrue( $this->immo_objects->prevent_import_by_state( false, $immo_object ) );
	}

	/**
	 * Test that an object without any state is skipped.
	 *
	 * @return void
	 */
	public function test_state_without_any_status_prevents_import(): void {
		$this->assertTrue( $this->immo_objects->prevent_import_by_state( false, $this->get_object() ) );
	}

	/**
	 * Test that an empty setting allows every value.
	 *
	 * @return void
	 */
	public function test_taxonomy_filter_with_empty_setting_allows_all(): void {
		update_option( 'propstack_connector_import_states', array() );

		$this->assertFalse( $this->immo_objects->prevent_import_by_taxonomy( 'propstack_connector_import_states', '123', false ) );
	}

	/**
	 * Test that a setting containing only an empty first entry allows every value.
	 *
	 * @return void
	 */
	public function test_taxonomy_filter_with_empty_first_entry_allows_all(): void {
		update_option( 'propstack_connector_import_states', array( '' ) );

		$this->assertFalse( $this->immo_objects->prevent_import_by_taxonomy( 'propstack_connector_import_states', '123', false ) );
	}

	/**
	 * Test that a value which is part of the setting is allowed.
	 *
	 * @return void
	 */
	public function test_taxonomy_filter_allows_configured_value(): void {
		update_option( 'propstack_connector_import_states', array( '123', '456' ) );

		$this->assertFalse( $this->immo_objects->prevent_import_by_taxonomy( 'propstack_connector_import_states', '123', false ) );
	}

	/**
	 * Test that a value which is not part of the setting is prevented.
	 *
	 * @return void
	 */
	public function test_taxonomy_filter_prevents_unconfigured_value(): void {
		update_option( 'propstack_connector_import_states', array( '123', '456' ) );

		$this->assertTrue( $this->immo_objects->prevent_import_by_taxonomy( 'propstack_connector_import_states', '789', false ) );
	}

	/**
	 * Test the strict comparison used for the configured values.
	 *
	 * Hint: prevent_import_by_taxonomy() compares with in_array( ..., true ). If the
	 * setting ever stores integers instead of strings, no value would match anymore
	 * and every object would be skipped. This test documents that behaviour so a
	 * change of the stored type does not pass unnoticed.
	 *
	 * @return void
	 */
	public function test_taxonomy_filter_uses_strict_comparison(): void {
		update_option( 'propstack_connector_import_states', array( 123, 456 ) );

		$this->assertTrue( $this->immo_objects->prevent_import_by_taxonomy( 'propstack_connector_import_states', '123', false ) );
	}

	/**
	 * Test that the broker filter uses the broker ID of API v1.
	 *
	 * @return void
	 */
	public function test_broker_filter_v1(): void {
		update_option( 'propstack_connector_import_broker', array( '64' ) );

		// the configured broker is allowed.
		$immo_object = $this->get_object( array( 'broker' => array( 'id' => 64 ) ) );
		$this->assertFalse( $this->immo_objects->prevent_import_by_broker( false, $immo_object ) );

		// any other broker is skipped.
		$immo_object = $this->get_object( array( 'broker' => array( 'id' => 65 ) ) );
		$this->assertTrue( $this->immo_objects->prevent_import_by_broker( false, $immo_object ) );
	}

	/**
	 * Test that the broker filter uses the broker ID of API v2.
	 *
	 * @return void
	 */
	public function test_broker_filter_v2(): void {
		update_option( 'propstack_connector_import_broker', array( '64' ) );

		// the configured broker is allowed.
		$immo_object = $this->get_object( array( 'broker_id' => 64 ) );
		$this->assertFalse( $this->immo_objects->prevent_import_by_broker( false, $immo_object ) );

		// any other broker is skipped.
		$immo_object = $this->get_object( array( 'broker_id' => 65 ) );
		$this->assertTrue( $this->immo_objects->prevent_import_by_broker( false, $immo_object ) );
	}

	/**
	 * Test that an object without any broker is not prevented by the broker filter.
	 *
	 * @return void
	 */
	public function test_broker_filter_without_broker(): void {
		update_option( 'propstack_connector_import_broker', array( '64' ) );

		$this->assertFalse( $this->immo_objects->prevent_import_by_broker( false, $this->get_object() ) );
	}
}

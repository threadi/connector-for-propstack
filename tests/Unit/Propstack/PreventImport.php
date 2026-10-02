<?php
/**
 * File for tests against the prevent-import-filters in \ConnectorForPropstack\Propstack\ImmoObjects.
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

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

	/**
	 * Create a state term as the import of states via API v2 does.
	 *
	 * @param int    $state_id The Propstack-ID of the state.
	 * @param string $name     The name of the state.
	 *
	 * @return void
	 */
	private function create_state_term( int $state_id, string $name ): void {
		// the states are only used with API v2.
		update_option( 'propstack_connector_api_version', 'v2' );

		$term = wp_insert_term( $name, \ConnectorForPropstack\Propstack\Taxonomies\Status::get_instance()->get_name() );
		$this->assertIsArray( $term );
		update_term_meta( $term['term_id'], 'id', $state_id );
		update_term_meta( $term['term_id'], 'language_code', \ConnectorForPropstack\Plugin\Languages::get_instance()->get_import_language() );
	}

	/**
	 * Test that without configured states only objects in state "Vermarktung" are imported (API v2).
	 *
	 * Hint: the API v2 delivers the ID of the state, its name comes from the imported state terms.
	 *
	 * @return void
	 */
	public function test_state_v2_allows_only_active_marketing_by_default(): void {
		$this->create_state_term( 222051, 'Vermarktung' );
		$this->create_state_term( 222052, 'Archiviert' );

		$states = \ConnectorForPropstack\Propstack\States::get_instance();

		// the state "Vermarktung" is allowed.
		$this->assertFalse( $states->prevent_import_by_state( false, $this->get_object( array( 'property_status_id' => 222051 ) ) ) );

		// any other state is skipped.
		$this->assertTrue( $states->prevent_import_by_state( false, $this->get_object( array( 'property_status_id' => 222052 ) ) ) );

		// an unknown state is skipped.
		$this->assertTrue( $states->prevent_import_by_state( false, $this->get_object( array( 'property_status_id' => 999999 ) ) ) );

		// an object without a state is skipped.
		$this->assertTrue( $states->prevent_import_by_state( false, $this->get_object() ) );
	}

	/**
	 * Test that configured states replace the default state (API v2).
	 *
	 * @return void
	 */
	public function test_state_v2_uses_configured_states(): void {
		$this->create_state_term( 222051, 'Vermarktung' );
		$this->create_state_term( 222052, 'Archiviert' );

		update_option( 'propstack_connector_import_states', array( '222052' ) );

		$states = \ConnectorForPropstack\Propstack\States::get_instance();

		$this->assertTrue( $states->prevent_import_by_state( false, $this->get_object( array( 'property_status_id' => 222051 ) ) ) );
		$this->assertFalse( $states->prevent_import_by_state( false, $this->get_object( array( 'property_status_id' => 222052 ) ) ) );
	}

	/**
	 * Return a complete object as API v1 delivers it, which passes every check.
	 *
	 * @param array<string,mixed> $overrides Values to overwrite in the returned object.
	 *
	 * @return array<string,mixed>
	 */
	private function get_importable_object( array $overrides = array() ): array {
		return $this->get_object(
			array_merge(
				array(
					'property_status' => array(
						'id'   => 222051,
						'name' => 'Vermarktung',
					),
					'broker'          => array(
						'id'   => 64,
						'name' => 'Erika Musterfrau',
					),
					'marketing_type'  => 'BUY',
					'rs_type'         => 'HOUSE',
					'rs_category'     => 'TWO_FAMILY_HOUSE',
				),
				$overrides
			)
		);
	}

	/**
	 * Prepare the default terms in the language the import uses, as the object type check needs them.
	 *
	 * @return void
	 */
	private function prepare_default_terms(): void {
		update_option( 'propstack_connector_languages', 'de' );
		update_option( 'propstack_connector_api_version', 'v1' );
		add_filter( 'cfprop_current_language', fn() => 'de' );
		\ConnectorForPropstack\Propstack\Propstack::get_instance()->activation();
	}

	/**
	 * Test that an object which passes every check does not deliver any reason.
	 *
	 * @return void
	 */
	public function test_reasons_are_empty_for_importable_object(): void {
		$this->prepare_default_terms();

		$this->assertSame( array(), $this->immo_objects->get_prevent_import_reasons( $this->get_importable_object() ) );
	}

	/**
	 * Test that the state is named as reason if it is not "Vermarktung" (API v1).
	 *
	 * Hint: this is the most common support case of the free version. A state like
	 * "Aktive Vermarktung" looks right, but is not the state the import expects.
	 *
	 * @return void
	 */
	public function test_reasons_name_the_state_v1(): void {
		$this->prepare_default_terms();

		$immo_object = $this->get_importable_object(
			array(
				'property_status' => array(
					'id'   => 262839,
					'name' => 'Aktive Vermarktung',
				),
			)
		);

		$reasons = $this->immo_objects->get_prevent_import_reasons( $immo_object );

		$this->assertCount( 1, $reasons );
		$this->assertStringContainsString( 'Aktive Vermarktung', $reasons[0] );
	}

	/**
	 * Test that a missing state is named as reason (API v1).
	 *
	 * @return void
	 */
	public function test_reasons_name_the_missing_state_v1(): void {
		$this->prepare_default_terms();

		$immo_object = $this->get_importable_object();
		unset( $immo_object['property_status'] );

		$this->assertSame( array( 'The object has no state.' ), $this->immo_objects->get_prevent_import_reasons( $immo_object ) );
	}

	/**
	 * Test that every restriction which prevents the import is named.
	 *
	 * @return void
	 */
	public function test_reasons_name_every_restriction(): void {
		$this->prepare_default_terms();

		update_option( 'propstack_connector_import_broker', array( '65' ) );
		update_option( 'propstack_connector_import_marketing_type', array( 'RENT' ) );
		update_option( 'propstack_connector_import_object_type', array( 'APARTMENT' ) );
		update_option( 'propstack_connector_import_property_type', array( 'MAISONETTE' ) );

		$reasons = implode( ' ', $this->immo_objects->get_prevent_import_reasons( $this->get_importable_object() ) );

		$this->assertStringContainsString( 'The broker "Erika Musterfrau"', $reasons );
		$this->assertStringContainsString( 'The marketing type "BUY"', $reasons );
		$this->assertStringContainsString( 'The object type "HOUSE" of the object is not one of the object types to import.', $reasons );
		$this->assertStringContainsString( 'The property type "TWO_FAMILY_HOUSE"', $reasons );

		// the state is fine, so it must not be named.
		$this->assertStringNotContainsString( 'state', $reasons );
	}

	/**
	 * Test that an object type this plugin does not know is named as not supported.
	 *
	 * @return void
	 */
	public function test_reasons_name_unsupported_object_type(): void {
		$this->prepare_default_terms();

		$reasons = $this->immo_objects->get_prevent_import_reasons( $this->get_importable_object( array( 'rs_type' => 'OFFICE' ) ) );

		$this->assertSame( array( 'The object type "OFFICE" of the object is not supported.' ), $reasons );
	}

	/**
	 * Test that missing main fields are named as reason.
	 *
	 * @return void
	 */
	public function test_reasons_name_missing_fields(): void {
		$this->prepare_default_terms();

		$immo_object = $this->get_importable_object();
		unset( $immo_object['title'] );

		$this->assertSame( array( 'The ID, the name or the title of the object is missing.' ), $this->immo_objects->get_prevent_import_reasons( $immo_object ) );
	}

	/**
	 * Test that a check which is not used does not deliver a reason.
	 *
	 * Hint: another plugin can replace a check with its own one. The reason of the removed
	 * check would be wrong then.
	 *
	 * @return void
	 */
	public function test_reasons_ignore_removed_checks(): void {
		$this->prepare_default_terms();

		remove_filter( 'cfprop_prevent_import_of_object', array( $this->immo_objects, 'prevent_import_by_state' ) );

		$immo_object = $this->get_importable_object(
			array(
				'property_status' => array(
					'id'   => 262839,
					'name' => 'Aktive Vermarktung',
				),
			)
		);

		$this->assertSame( array(), $this->immo_objects->get_prevent_import_reasons( $immo_object ) );
	}

	/**
	 * Test that own reasons can be added via hook.
	 *
	 * @return void
	 */
	public function test_reasons_can_be_extended(): void {
		$this->prepare_default_terms();

		add_filter(
			'cfprop_prevent_import_of_object_reasons',
			function ( array $reasons, array $immo_object ) {
				$reasons[] = 'The object ' . $immo_object['id'] . ' is excluded.';
				return $reasons;
			},
			10,
			2
		);

		$this->assertSame( array( 'The object 42 is excluded.' ), $this->immo_objects->get_prevent_import_reasons( $this->get_importable_object() ) );
	}

	/**
	 * Test the reason for the state with API v2.
	 *
	 * @return void
	 */
	public function test_state_v2_reason(): void {
		$this->create_state_term( 222051, 'Vermarktung' );
		$this->create_state_term( 222052, 'Archiviert' );

		$states = \ConnectorForPropstack\Propstack\States::get_instance();

		// without configured states the state must be "Vermarktung".
		$this->assertSame( 'The state "Archiviert" of the object is not "Vermarktung".', $states->get_prevent_import_reason( $this->get_object( array( 'property_status_id' => 222052 ) ) ) );

		// an unknown state is named by its ID.
		$this->assertSame( 'The state "999999" of the object is not "Vermarktung".', $states->get_prevent_import_reason( $this->get_object( array( 'property_status_id' => 999999 ) ) ) );

		// an object without a state.
		$this->assertSame( 'The object has no state.', $states->get_prevent_import_reason( $this->get_object() ) );

		// with configured states the state must be one of them.
		update_option( 'propstack_connector_import_states', array( '222052' ) );
		$this->assertSame( 'The state "Vermarktung" of the object is not one of the states to import.', $states->get_prevent_import_reason( $this->get_object( array( 'property_status_id' => 222051 ) ) ) );
	}

	/**
	 * Test that the state check of API v2 delivers its reason via the list of reasons.
	 *
	 * @return void
	 */
	public function test_reasons_name_the_state_v2(): void {
		$this->prepare_default_terms();
		$this->create_state_term( 222051, 'Vermarktung' );
		$this->create_state_term( 222052, 'Archiviert' );

		// use the state check of API v2, as States::init() does.
		$states = \ConnectorForPropstack\Propstack\States::get_instance();
		remove_filter( 'cfprop_prevent_import_of_object', array( $this->immo_objects, 'prevent_import_by_state' ) );
		add_filter( 'cfprop_prevent_import_of_object', array( $states, 'prevent_import_by_state' ), 10, 2 );

		$immo_object = $this->get_importable_object(
			array(
				'property_status_id' => 222052,
				'broker_id'          => 64,
			)
		);
		unset( $immo_object['property_status'], $immo_object['broker'] );

		$this->assertSame( array( 'The state "Archiviert" of the object is not "Vermarktung".' ), $this->immo_objects->get_prevent_import_reasons( $immo_object ) );
	}
}

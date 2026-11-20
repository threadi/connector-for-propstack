<?php
/**
 * File for the tests of the connection to the live Propstack API: the API key and the data the
 * import needs besides the objects.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Live;

use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Imports\v2\Brokers;
use ConnectorForPropstack\Propstack\Imports\v2\Options;
use ConnectorForPropstack\Propstack\Imports\v2\States;
use ConnectorForPropstack\Propstack\Propstack;
use ConnectorForPropstack\Propstack\Taxonomies\Status;
use ConnectorForPropstack\Tests\LiveTestCase;

/**
 * Object for the tests of the connection to the live Propstack API.
 */
class Connection extends LiveTestCase {
	/**
	 * Test that the configured API key is accepted, like the setup and the settings check it.
	 *
	 * @dataProvider api_versions
	 *
	 * @param string $api_version The API version.
	 *
	 * @return void
	 */
	public function test_api_key_is_accepted( string $api_version ): void {
		$this->require_api_version( $api_version );
		self::use_api_version( $api_version );

		// the check is only allowed for administrators.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$result = Propstack::rest_validate_key( self::get_config()['api_key'] );

		$this->assertSame( array(), $result, 'The API key has not been accepted for the API ' . $api_version . ': ' . ( $result['text'] ?? '' ) . "\n\nRequests:\n" . self::get_api_requests_as_text() );
	}

	/**
	 * Test that Propstack rejects a wrong API key and the plugin reports this.
	 *
	 * @return void
	 */
	public function test_wrong_api_key_is_rejected(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		self::use_api_version( 'v1' );

		$result = Propstack::rest_validate_key( 'cfproplivetest' . wp_generate_password( 26, false, false ) );

		$this->assertSame( 'invalid', $result['error'] ?? '', 'A wrong API key has not been rejected: ' . wp_json_encode( $result ) . "\n\nRequests:\n" . self::get_api_requests_as_text() );

		// the key must not have been saved.
		$this->assertSame( self::get_config()['api_key'], get_option( 'propstack_connector_api_key' ), 'A wrong API key has been saved.' );
	}

	/**
	 * Test that the API delivers the objects for the import page by page, without importing them.
	 *
	 * This requests all pages like the import does it, including all restrictions which are sent to the API.
	 *
	 * @dataProvider api_versions
	 *
	 * @param string $api_version The API version.
	 *
	 * @return void
	 */
	public function test_api_delivers_objects_for_the_import( string $api_version ): void {
		$this->require_api_version( $api_version );
		self::use_api_version( $api_version );

		// search an object which does not exist, so all pages are requested.
		$import = ImmoObjects::get_instance()->get_import_object();
		$result = $import->find_object( 'cfprop-live-test-not-existing', self::get_config()['language'], 'id', true );

		$this->assertEmpty( self::get_error_texts( $import ), "The request of the objects ended with errors:\n  " . implode( "\n  ", self::get_error_texts( $import ) ) . "\n\nRequests:\n" . self::get_api_requests_as_text() );
		$this->assertFalse( $result['found'], 'An object with an invalid ID has been found.' );
		$this->assertGreaterThan( 0, $result['checked'], 'Propstack did not deliver any object. Use an account with objects for the live tests.' );

		self::report( sprintf( 'API %1$s: %2$d objects on %3$d pages.', $api_version, $result['checked'], count( self::get_api_requests() ) ) );
	}

	/**
	 * Test that the configured object is delivered for the import and not prevented by a restriction.
	 *
	 * @dataProvider api_versions
	 *
	 * @param string $api_version The API version.
	 *
	 * @return void
	 */
	public function test_configured_object_is_delivered( string $api_version ): void {
		$this->require_api_version( $api_version );
		self::use_api_version( $api_version );

		// bail if no object is configured.
		$object_id = self::get_config()['object_id'];
		if ( '' === $object_id ) {
			$this->markTestSkipped( 'No object configured (object_id or CFPROP_LIVE_OBJECT_ID).' );
		}

		// check it like "wp cfprop check_object" does it.
		$check    = ImmoObjects::get_instance()->get_object_check( $object_id );
		$language = self::get_config()['language'];

		$this->assertFalse( $check['has_errors'], 'The check ended with errors: ' . implode( ' ', $check['languages'][ $language ]['errors'] ?? array() ) );
		$this->assertTrue( $check['delivered'], 'Propstack does not deliver object ' . $object_id . ' for the import (direct request: HTTP ' . $check['direct']['http_status'] . ').' );
		$this->assertFalse( $check['languages'][ $language ]['prevented'], 'A restriction prevents the import of object ' . $object_id . ': ' . implode( ' ', $check['languages'][ $language ]['reasons'] ) );
	}

	/**
	 * Test the import of the object states via API v2.
	 *
	 * @return void
	 */
	public function test_states_are_imported(): void {
		$this->require_api_version( 'v2' );
		self::use_api_version( 'v2' );

		$import = new States();
		$import->run();

		$this->assertEmpty( self::get_error_texts( $import ), "The import of the states ended with errors:\n  " . implode( "\n  ", self::get_error_texts( $import ) ) . "\n\nRequests:\n" . self::get_api_requests_as_text() );

		$terms = get_terms(
			array(
				'taxonomy'   => Status::get_instance()->get_name(),
				'hide_empty' => false,
				'meta_key'   => 'language_code', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => self::get_config()['language'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$this->assertIsArray( $terms );
		$this->assertNotEmpty( $terms, 'No state has been imported.' );
	}

	/**
	 * Test that the API v2 delivers the texts for the codes of selection fields.
	 *
	 * Without them, the plugin would show the codes or use the texts delivered with the plugin.
	 *
	 * @return void
	 */
	public function test_options_are_delivered(): void {
		$this->require_api_version( 'v2' );
		self::use_api_version( 'v2' );

		$this->assertTrue( ( new Options() )->is_token_permitted( self::get_config()['api_key'] ), 'The API key is not allowed to read the options of object fields.' );

		self::$api_requests = array();
		$options            = ( new Options() )->get_options( self::get_config()['language'] );
		$requests           = self::get_api_requests( '/v2/properties/options' );

		$this->assertNotEmpty( $requests, 'The options have not been requested.' );
		$this->assertSame( 200, $requests[0]['status'], "The options could not be loaded:\n" . self::get_api_requests_as_text( $requests ) );
		$this->assertNotEmpty( $options, 'Propstack did not deliver any option.' );
	}

	/**
	 * Test that the API v2 delivers the brokers.
	 *
	 * @return void
	 */
	public function test_brokers_are_delivered(): void {
		$this->require_api_version( 'v2' );
		self::use_api_version( 'v2' );

		$brokers  = ( new Brokers() )->get_brokers();
		$requests = self::get_api_requests( '/v2/brokers' );

		$this->assertNotEmpty( $requests, 'The brokers have not been requested.' );
		$failed = array_filter( $requests, static fn( array $request ): bool => 200 !== $request['status'] );
		$this->assertEmpty( $failed, "The brokers could not be loaded:\n" . self::get_api_requests_as_text( $failed ) );
		$this->assertNotEmpty( $brokers, 'Propstack did not deliver any broker.' );

		// each broker needs its ID, as the objects are assigned to their broker by it.
		foreach ( $brokers as $broker_id => $broker ) {
			$this->assertSame( $broker_id, absint( $broker['id'] ?? 0 ), 'A broker is delivered without its ID.' );
		}
	}
}

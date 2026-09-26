<?php
/**
 * Tests for the handling of object states.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object to test the states.
 */
class States extends ConnectorForPropstackTestCase {
	/**
	 * Test that the constants for the states import are defined even if API v1 is used.
	 *
	 * The API version could be changed to v2 during a request, the v2 import would fail
	 * with an undefined constant then.
	 *
	 * @return void
	 */
	public function test_constants_are_defined_with_api_v1(): void {
		// use API v1.
		$api_version = get_option( 'propstack_connector_api_version' );
		update_option( 'propstack_connector_api_version', 'v1' );

		// initialize the states.
		\ConnectorForPropstack\Propstack\States::get_instance()->init();

		// test the result.
		$this->assertTrue( defined( 'CFPROP_STATES_IMPORT_RUNNING' ) );
		$this->assertTrue( defined( 'CFPROP_STATES_DELETE_RUNNING' ) );

		// the filter for v2 is not added with API v1.
		$this->assertFalse( has_filter( 'cfprop_prevent_import_of_object', array( \ConnectorForPropstack\Propstack\States::get_instance(), 'prevent_import_by_state' ) ) );

		// restore the setting.
		update_option( 'propstack_connector_api_version', $api_version );
	}
}

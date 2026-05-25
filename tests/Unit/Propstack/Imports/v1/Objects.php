<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\Imports\v1\Objects.
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\Imports\v1;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\Imports\v1\Objects.
 */
class Objects extends ConnectorForPropstackTestCase {
	/**
	 * The object for immo object imports.
	 *
	 * @var \ConnectorForPropstack\Propstack\Imports\v1\Objects
	 */
	private \ConnectorForPropstack\Propstack\Imports\v1\Objects $import_obj;

	/**
	 * Prepare the test environment for each test class.
	 *
	 * @return void
	 */
	public function set_up(): void {
		// set running import.
		update_option( CFPROP_IMPORT_RUNNING, 0 );

		// set running import.
		update_option( CFPROP_DELETE_RUNNING, 0 );

		// set language to "de".
		update_option( 'propstack_connector_languages', 'de' );

		// get the object to import immo objects.
		$this->import_obj = new \ConnectorForPropstack\Propstack\Imports\v1\Objects();

		// run the activation.
		\ConnectorForPropstack\Propstack\Propstack::get_instance()->activation();
	}

	/**
	 * Test if an import is already running.
	 *
	 * @return void
	 */
	public function test_import_running(): void {
		// set running import.
		update_option( CFPROP_IMPORT_RUNNING, time() );

		// run it.
		$this->import_obj->run();

		// test the results.
		$this->assertIsArray( $this->import_obj->get_errors() );
		$this->assertNotEmpty( $this->import_obj->get_errors() );
		foreach ( $this->import_obj->get_errors() as $error ) {
			$this->assertEquals( 'propstack_object_import_is_running', $error->get_error_code() );
		}
	}

	/**
	 * Test if a deletion is already running.
	 *
	 * @return void
	 */
	public function test_deletion_running(): void {
		// set running import.
		update_option( CFPROP_DELETE_RUNNING, time() );

		// run it.
		$this->import_obj->run();

		// test the results.
		$this->assertIsArray( $this->import_obj->get_errors() );
		$this->assertNotEmpty( $this->import_obj->get_errors() );
		foreach ( $this->import_obj->get_errors() as $error ) {
			$this->assertEquals( 'propstack_object_deletion_is_running', $error->get_error_code() );
		}
	}

	/**
	 * Test if the HTTP status is not 200.
	 *
	 * @return void
	 */
	public function test_import_with_wrong_http_status(): void {
		// add filter to force a wrong HTTP status.
		add_filter( 'cfprop_request_header', array( $this, 'set_wrong_http_status' ) );

		// run it.
		$this->import_obj->run();

		// test the results.
		$this->assertIsArray( $this->import_obj->get_errors() );
		$this->assertNotEmpty( $this->import_obj->get_errors() );
		foreach ( $this->import_obj->get_errors() as $error ) {
			$this->assertEquals( 'propstack_object_import_http_status', $error->get_error_code() );
		}

		// remove the filter.
		remove_filter( 'cfprop_request_header', array( $this, 'set_wrong_http_status' ) );
	}

	/**
	 * Test without an API key.
	 *
	 * @return void
	 */
	public function test_import_without_key(): void {
		// set a pseudo-key.
		update_option( 'propstack_connector_api_key', '' );

		// run it.
		$this->import_obj->run();

		// test the results.
		$this->assertIsArray( $this->import_obj->get_errors() );
		$this->assertNotEmpty( $this->import_obj->get_errors() );
		foreach ( $this->import_obj->get_errors() as $error ) {
			$this->assertEquals( 'propstack_object_import_http_status', $error->get_error_code() );
		}
	}

	/**
	 * Test an import with an API key but a faulty API response.
	 *
	 * @return void
	 */
	public function test_import_with_api_key_but_faulty_api_response(): void {
		// force a faulty API response.
		add_filter( 'cfprop_request_header', array( $this, 'set_to_use_faulty_api_response' ) );

		// set a pseudo-key.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// run it.
		$this->import_obj->run();

		// test the results.
		$this->assertIsArray( $this->import_obj->get_errors() );
		$this->assertEmpty( $this->import_obj->get_errors() );

		// remove the filter.
		remove_filter( 'cfprop_request_header', array( $this, 'set_to_use_faulty_api_response' ) );

		// remove the pseudo-key.
		update_option( 'propstack_connector_api_key', '' );
	}

	/**
	 * Test a successful import of objects.
	 *
	 * @return void
	 */
	public function test_import_with_api_key_and_response(): void {
		// set a pseudo-key.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// run it.
		$this->import_obj->run();

		// test the results.
		$this->assertIsArray( $this->import_obj->get_errors() );
		$this->assertEmpty( $this->import_obj->get_errors() );
		$this->assertIsArray( \ConnectorForPropstack\Propstack\ImmoObjects::get_instance()->get_objects() );
		$this->assertNotEmpty( \ConnectorForPropstack\Propstack\ImmoObjects::get_instance()->get_objects() );
		$this->assertIsBool( \ConnectorForPropstack\Propstack\ImmoObjects::get_instance()->has_objects() );
		$this->assertTrue( \ConnectorForPropstack\Propstack\ImmoObjects::get_instance()->has_objects() );

		// remove the pseudo-key.
		update_option( 'propstack_connector_api_key', '' );
	}

	/**
	 * Set a variable to force a wrong HTTP status.
	 *
	 * @param array<string,mixed> $headers The list of headers for the request.
	 *
	 * @return array<string,mixed>
	 */
	public function set_wrong_http_status( array $headers ): array {
		$headers['response_http_status'] = 401;
		return $headers;
	}

	/**
	 * Set a variable to force a faulty API response.
	 *
	 * @param array<string,mixed> $headers The list of headers for the request.
	 *
	 * @return array<string,mixed>
	 */
	public function set_to_use_faulty_api_response( array $headers ): array {
		$headers['faulty_response'] = 1;
		return $headers;
	}
}

<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\Imports\v2\Objects.
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\Imports\v2;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;
use WP_HTTP_Requests_Response;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\Imports\v2\Objects.
 *
 * Hint: the shared test case only mocks the API v1 URL. This class registers its
 * own filter for the v2 URL, so no real request leaves the test environment.
 */
class Objects extends ConnectorForPropstackTestCase {
	/**
	 * The object for immo object imports.
	 *
	 * @var \ConnectorForPropstack\Propstack\Imports\v2\Objects
	 */
	private \ConnectorForPropstack\Propstack\Imports\v2\Objects $import_obj;

	/**
	 * The API URL used by the v2 import.
	 *
	 * @var string
	 */
	private static string $properties_url = 'https://api.propstack.de/v2/properties';

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// no import is running.
		update_option( CFPROP_IMPORT_RUNNING, 0 );

		// no deletion is running.
		update_option( CFPROP_DELETE_RUNNING, 0 );

		// set language to "de".
		update_option( 'propstack_connector_languages', 'de' );

		// use API v2.
		update_option( 'propstack_connector_api_version', 'v2' );

		// mock the v2 endpoint.
		add_filter( 'pre_http_request', array( $this, 'mock_v2_request' ), 10, 3 );

		// get the object to import immo objects.
		$this->import_obj = new \ConnectorForPropstack\Propstack\Imports\v2\Objects();

		// run the activation.
		\ConnectorForPropstack\Propstack\Propstack::get_instance()->activation();
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_v2_request' ), 10 );

		// remove the pseudo-key.
		update_option( 'propstack_connector_api_key', '' );

		parent::tear_down();
	}

	/**
	 * Deliver a local response for the v2 endpoint.
	 *
	 * @param false|array<string,mixed>|WP_Error $result      The return value of the filter.
	 * @param array<string,mixed>                $parsed_args The used parameters for the request.
	 * @param string                             $url         The requested URL.
	 *
	 * @return false|array<string,mixed>|WP_Error
	 */
	public function mock_v2_request( false|array|WP_Error $result, array $parsed_args, string $url ): false|array|WP_Error {
		// bail if this is not our v2 request.
		if ( 'GET' !== $parsed_args['method'] || ! str_starts_with( $url, self::$properties_url ) ) {
			return $result;
		}

		// answer with 401 if the API key is missing.
		if ( empty( $parsed_args['headers']['X-API-KEY'] ) ) {
			$requests_response              = new \WpOrg\Requests\Response();
			$requests_response->status_code = 401;

			return array(
				'http_response' => new WP_HTTP_Requests_Response( $requests_response, $parsed_args['filename'] ),
			);
		}

		// bail with the given status if a wrong one is forced.
		$requests_response              = new \WpOrg\Requests\Response();
		$requests_response->status_code = isset( $parsed_args['headers']['response_http_status'] ) ? $parsed_args['headers']['response_http_status'] : 200;

		// deliver our fixture.
		$json = Helper::get_wp_filesystem()->get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'properties_full.json' );

		return array(
			'http_response' => new WP_HTTP_Requests_Response( $requests_response, $parsed_args['filename'] ),
			'body'          => $json,
		);
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
		// set running deletion.
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
	 * Test without an API key.
	 *
	 * @return void
	 */
	public function test_import_without_key(): void {
		// set an empty key.
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
	 * Test if the HTTP status is not 200.
	 *
	 * @return void
	 */
	public function test_import_with_wrong_http_status(): void {
		// set a pseudo-key.
		update_option( 'propstack_connector_api_key', self::$api_key );

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
		$this->assertNotEmpty( \ConnectorForPropstack\Propstack\ImmoObjects::get_instance()->get_objects() );
		$this->assertTrue( \ConnectorForPropstack\Propstack\ImmoObjects::get_instance()->has_objects() );
	}

	/**
	 * Test that objects which are not in state "Vermarktung" are skipped.
	 *
	 * The fixture contains three objects, one of them archived.
	 *
	 * @return void
	 */
	public function test_import_skips_archived_objects(): void {
		// set a pseudo-key.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// run it.
		$this->import_obj->run();

		// only the two objects in the marketing state should exist.
		$this->assertCount( 2, \ConnectorForPropstack\Propstack\ImmoObjects::get_instance()->get_objects() );
	}

	/**
	 * Test that the import lock is released after a run.
	 *
	 * @return void
	 */
	public function test_import_releases_the_lock(): void {
		// set a pseudo-key.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// run it.
		$this->import_obj->run();

		// the lock has to be released.
		$this->assertSame( 0, absint( get_option( CFPROP_IMPORT_RUNNING ) ) );
	}

	/**
	 * Test that a second run does not import the same objects twice.
	 *
	 * @return void
	 */
	public function test_import_is_idempotent(): void {
		// set a pseudo-key.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// run it twice.
		$this->import_obj->run();
		$count_after_first_run = count( \ConnectorForPropstack\Propstack\ImmoObjects::get_instance()->get_objects() );

		$import_obj = new \ConnectorForPropstack\Propstack\Imports\v2\Objects();
		$import_obj->run();

		// the number of objects must not change.
		$this->assertCount( $count_after_first_run, \ConnectorForPropstack\Propstack\ImmoObjects::get_instance()->get_objects() );
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
}

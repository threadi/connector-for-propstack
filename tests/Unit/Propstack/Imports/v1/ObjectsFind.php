<?php
/**
 * File for tests against the check whether the API v1 delivers a specific object.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\Imports\v1;

use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Imports\v1\Objects;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;
use WP_HTTP_Requests_Response;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\Import_Base::find_object() with the API v1.
 *
 * This check is used by the WP CLI command "wp cfprop check_object". The class registers its own
 * mock for the v1 endpoint (https://api.propstack.de/v1/units): it reads "page", "per" and
 * "property_ids" from the request URL and returns the matching objects with "meta.total_count".
 */
class ObjectsFind extends ConnectorForPropstackTestCase {
	/**
	 * The v1 endpoint.
	 *
	 * @var string
	 */
	private static string $units_url = 'https://api.propstack.de/v1/units';

	/**
	 * The number of objects the mocked API "holds".
	 *
	 * @var int
	 */
	private int $total_objects = 250;

	/**
	 * If true, the mock ignores the parameter "property_ids" and returns all objects -
	 * simulating an API which does not support the request of a single object.
	 *
	 * @var bool
	 */
	private bool $ignore_id_filter = false;

	/**
	 * The IDs of objects the mock knows, but does not deliver in its list - simulating objects
	 * which are excluded by a parameter of the request, e.g. by their state.
	 *
	 * @var array<int,int>
	 */
	private array $hidden_ids = array();

	/**
	 * The URLs the mock received during a test.
	 *
	 * @var array<int,string>
	 */
	private array $requested_urls = array();

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// no import or deletion is running.
		update_option( CFPROP_IMPORT_RUNNING, 0 );
		update_option( CFPROP_DELETE_RUNNING, 0 );

		// use API v1 and a single language.
		update_option( 'propstack_connector_api_version', 'v1' );
		update_option( 'propstack_connector_languages', 'de' );

		// a valid key so the mock answers.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// no restriction which would be added to the URL.
		update_option( 'propstack_connector_import_states', array() );
		update_option( 'propstack_connector_import_marketing_type', array() );
		update_option( 'propstack_connector_import_object_type', array() );

		// reset the mock state.
		$this->total_objects    = 250;
		$this->ignore_id_filter = false;
		$this->hidden_ids       = array();
		$this->requested_urls   = array();

		// register the mock for the v1 endpoint.
		add_filter( 'pre_http_request', array( $this, 'mock_v1_request' ), 10, 3 );

		// take over the v1 endpoint for this class instead of relying on filter order.
		remove_filter( 'pre_http_request', array( ConnectorForPropstackTestCase::class, 'add_url_filter' ), 10 );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_v1_request' ) );

		update_option( 'propstack_connector_api_key', '' );

		parent::tear_down();
	}

	/**
	 * Mock for the v1 endpoint which supports pagination and the request of single objects.
	 *
	 * @param false|array<string,mixed>|WP_Error $result      The filter return value.
	 * @param array<string,mixed>                $parsed_args The request arguments.
	 * @param string                             $url         The requested URL.
	 *
	 * @return false|array<string,mixed>|WP_Error
	 */
	public function mock_v1_request( false|array|WP_Error $result, array $parsed_args, string $url ): false|array|WP_Error {
		// pass through anything that is not our v1 endpoint.
		if ( 'GET' !== $parsed_args['method'] || ! str_starts_with( $url, self::$units_url ) ) {
			return $result;
		}

		// remember the request.
		$this->requested_urls[] = $url;

		// answer with 401 if the key is missing.
		if ( empty( $parsed_args['headers']['X-API-KEY'] ) || $parsed_args['headers']['X-API-KEY'] !== self::$api_key ) {
			return $this->build_response( 401, '' );
		}

		// allow a test to force a non-200 status.
		if ( isset( $parsed_args['headers']['response_http_status'] ) ) {
			return $this->build_response( absint( $parsed_args['headers']['response_http_status'] ), (string) wp_json_encode( array( 'data' => array() ) ) );
		}

		// answer the request for a single object.
		$matches = array();
		if ( 1 === preg_match( '#/v1/units/(\d+)$#', (string) wp_parse_url( $url, PHP_URL_PATH ), $matches ) ) {
			$id = absint( $matches[1] );
			if ( $id < 1 || $id > $this->total_objects ) {
				return $this->build_response( 404, (string) wp_json_encode( array( 'errors' => array( 'Not found' ) ) ) );
			}

			return $this->build_response( 200, (string) wp_json_encode( $this->build_object( $id ) ) );
		}

		// read the parameters from the URL.
		$query = array();
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$page = isset( $query['page'] ) ? max( 1, absint( $query['page'] ) ) : 1;
		$per  = isset( $query['per'] ) ? max( 1, absint( $query['per'] ) ) : 20;

		// build the full list of objects.
		$all = array();
		for ( $id = 1; $id <= $this->total_objects; $id++ ) {
			// bail if this object is not part of the list.
			if ( in_array( $id, $this->hidden_ids, true ) ) {
				continue;
			}

			$all[] = $this->build_object( $id );
		}

		// limit the list to the requested objects, if the API supports this.
		if ( isset( $query['property_ids'] ) && ! $this->ignore_id_filter ) {
			$ids = array_map( 'absint', explode( ',', (string) $query['property_ids'] ) );
			$all = array_values( array_filter( $all, static fn( array $immo_object ): bool => in_array( $immo_object['id'], $ids, true ) ) );
		}

		// build the paginated response with the total count.
		$body = wp_json_encode(
			array(
				'data' => array_slice( $all, ( $page - 1 ) * $per, $per ),
				'meta' => array( 'total_count' => count( $all ) ),
			)
		);

		return $this->build_response( 200, (string) $body );
	}

	/**
	 * Build a single object as the v1 API would deliver it (title is an array).
	 *
	 * @param int $id The object ID.
	 *
	 * @return array<string,mixed>
	 */
	private function build_object( int $id ): array {
		return array(
			'id'      => $id,
			'unit_id' => 'A' . $id,
			'name'    => 'Object ' . $id,
			'title'   => array( 'value' => 'Title ' . $id ),
		);
	}

	/**
	 * Build a WP HTTP response array for the given status and body.
	 *
	 * @param int    $status The HTTP status code.
	 * @param string $body   The response body.
	 *
	 * @return array<string,mixed>
	 */
	private function build_response( int $status, string $body ): array {
		$requests_response              = new \WpOrg\Requests\Response();
		$requests_response->status_code = $status;

		return array(
			'http_response' => new WP_HTTP_Requests_Response( $requests_response, '' ),
			'body'          => $body,
		);
	}

	/**
	 * Test that a delivered object is found by its Propstack-ID with a single request.
	 *
	 * @return void
	 */
	public function test_delivered_object_is_found_with_a_single_request(): void {
		$import_obj = new Objects();
		$result     = $import_obj->find_object( '240', 'de' );

		$this->assertTrue( $result['found'] );
		$this->assertSame( 240, $result['object']['id'] );
		$this->assertFalse( $result['full_scan'] );
		$this->assertStringContainsString( 'property_ids=240', $result['url'] );
		$this->assertCount( 1, $this->requested_urls );
		$this->assertFalse( $import_obj->has_errors() );
	}

	/**
	 * Test that an object which is not delivered is not found, without requesting all pages.
	 *
	 * @return void
	 */
	public function test_missing_object_is_not_found(): void {
		$import_obj = new Objects();
		$result     = $import_obj->find_object( '9999', 'de' );

		$this->assertFalse( $result['found'] );
		$this->assertSame( array(), $result['object'] );
		$this->assertCount( 1, $this->requested_urls );
		$this->assertFalse( $import_obj->has_errors() );
	}

	/**
	 * Test that all pages are checked if the API does not limit its answer to the requested object.
	 *
	 * With 250 objects and 100 per page the object 240 is on page 3: one request with the ID
	 * and three requests for the pages are expected.
	 *
	 * @return void
	 */
	public function test_all_pages_are_checked_if_the_api_ignores_the_id(): void {
		$this->ignore_id_filter = true;

		$import_obj = new Objects();
		$result     = $import_obj->find_object( '240', 'de' );

		$this->assertTrue( $result['found'] );
		$this->assertTrue( $result['full_scan'] );
		$this->assertStringNotContainsString( 'property_ids', $result['url'] );
		$this->assertCount( 4, $this->requested_urls );
		$this->assertFalse( $import_obj->has_errors() );
	}

	/**
	 * Test that a missing object is reported as missing if the API ignores the ID, after all objects have been checked.
	 *
	 * @return void
	 */
	public function test_missing_object_is_not_found_if_the_api_ignores_the_id(): void {
		$this->ignore_id_filter = true;

		$import_obj = new Objects();
		$result     = $import_obj->find_object( '9999', 'de' );

		$this->assertFalse( $result['found'] );
		$this->assertTrue( $result['full_scan'] );
		$this->assertSame( 250, $result['checked'] );
		$this->assertFalse( $import_obj->has_errors() );
	}

	/**
	 * Test that a full scan uses the requests of the import, without the ID.
	 *
	 * @return void
	 */
	public function test_full_scan_does_not_ask_for_the_id(): void {
		$import_obj = new Objects();
		$result     = $import_obj->find_object( '240', 'de', 'id', true );

		$this->assertTrue( $result['found'] );
		$this->assertTrue( $result['full_scan'] );
		$this->assertCount( 3, $this->requested_urls );
		foreach ( $this->requested_urls as $url ) {
			$this->assertStringNotContainsString( 'property_ids', $url );
		}
	}

	/**
	 * Test that an object is found by its unit ID.
	 *
	 * @return void
	 */
	public function test_object_is_found_by_unit_id(): void {
		$import_obj = new Objects();
		$result     = $import_obj->find_object( 'A240', 'de', 'unit_id' );

		$this->assertTrue( $result['found'] );
		$this->assertSame( 240, $result['object']['id'] );
		$this->assertTrue( $result['full_scan'] );
	}

	/**
	 * Test that an HTTP error is reported, as the result is not reliable then.
	 *
	 * @return void
	 */
	public function test_http_error_is_reported(): void {
		// force a non-200 status on the request.
		$callback = static function ( array $headers ): array {
			$headers['response_http_status'] = 500;
			return $headers;
		};
		add_filter( 'cfprop_request_header', $callback );

		$import_obj = new Objects();
		$result     = $import_obj->find_object( '240', 'de' );

		remove_filter( 'cfprop_request_header', $callback );

		$this->assertFalse( $result['found'] );
		$this->assertTrue( $import_obj->has_errors() );
	}

	/**
	 * Test that the check does not import anything and does not leave its URL parameter behind.
	 *
	 * @return void
	 */
	public function test_check_does_not_change_anything(): void {
		$import_obj = new Objects();
		$import_obj->find_object( '240', 'de' );

		// nothing was imported and no import has been started.
		$this->assertEmpty( ImmoObjects::get_instance()->get_objects() );
		$this->assertEmpty( get_option( $import_obj->get_work_list_option(), array() ) );
		$this->assertSame( 0, absint( get_option( CFPROP_IMPORT_RUNNING, 0 ) ) );

		// the URL of the import is the same as before.
		$this->assertStringNotContainsString( 'property_ids', (string) apply_filters( 'cfprop_api_object_url', self::$units_url ) );
	}

	/**
	 * Test the complete check for a delivered object, which is used by WP CLI and the Pro plugin.
	 *
	 * @return void
	 */
	public function test_object_check_for_a_delivered_object(): void {
		$check = ImmoObjects::get_instance()->get_object_check( '240' );

		$this->assertTrue( $check['delivered'] );
		$this->assertFalse( $check['has_errors'] );
		$this->assertArrayHasKey( 'de', $check['languages'] );

		$result = $check['languages']['de'];
		$this->assertTrue( $result['found'] );
		$this->assertSame( '240', $result['object']['id'] );
		$this->assertSame( 'A240', $result['object']['unit_id'] );
		$this->assertSame( 'Title 240', $result['object']['title'] );
		$this->assertSame( 0, $result['post_id'] );

		// a prevented import always comes with its reasons.
		$this->assertSame( $result['prevented'], ! empty( $result['reasons'] ) );

		// Propstack is not asked directly, as the object is delivered.
		$this->assertSame( 0, $check['direct']['http_status'] );
		$this->assertCount( 1, $this->requested_urls );
	}

	/**
	 * Test the complete check for an object Propstack does not know.
	 *
	 * @return void
	 */
	public function test_object_check_for_an_unknown_object(): void {
		$check = ImmoObjects::get_instance()->get_object_check( '9999' );

		$this->assertFalse( $check['delivered'] );
		$this->assertFalse( $check['has_errors'] );
		$this->assertFalse( $check['languages']['de']['found'] );
		$this->assertSame( 404, $check['direct']['http_status'] );
	}

	/**
	 * Test the complete check for an object Propstack knows, but does not deliver for the import.
	 *
	 * @return void
	 */
	public function test_object_check_for_an_excluded_object(): void {
		$this->hidden_ids = array( 240 );

		$check = ImmoObjects::get_instance()->get_object_check( '240' );

		$this->assertFalse( $check['delivered'] );
		$this->assertFalse( $check['languages']['de']['found'] );
		$this->assertSame( 200, $check['direct']['http_status'] );
		$this->assertSame( 'Title 240', $check['direct']['object']['title'] );
	}

	/**
	 * Test that Propstack is not asked directly if the check is not reliable because of an error.
	 *
	 * @return void
	 */
	public function test_object_check_with_http_error(): void {
		// force a non-200 status on the request.
		$callback = static function ( array $headers ): array {
			$headers['response_http_status'] = 500;
			return $headers;
		};
		add_filter( 'cfprop_request_header', $callback );

		$check = ImmoObjects::get_instance()->get_object_check( '240' );

		remove_filter( 'cfprop_request_header', $callback );

		$this->assertFalse( $check['delivered'] );
		$this->assertTrue( $check['has_errors'] );
		$this->assertNotEmpty( $check['languages']['de']['errors'] );
		$this->assertSame( 0, $check['direct']['http_status'] );
	}
}

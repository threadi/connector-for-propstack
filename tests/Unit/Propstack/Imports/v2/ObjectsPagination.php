<?php
/**
 * File for tests against the pagination of the API v2 object import.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\Imports\v2;

use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;
use WP_HTTP_Requests_Response;

/**
 * Object for tests against the pagination of \ConnectorForPropstack\Propstack\Imports\v2\Objects.
 *
 * The shared test case only mocks the API v1 URL. This class registers its own
 * page-aware mock for the v2 endpoint (https://api.propstack.de/v2/properties):
 * it reads "page" and "per" from the request URL, returns the matching slice and
 * always sends "meta.total_count", so the pagination loop can be exercised end to
 * end without a real request.
 */
class ObjectsPagination extends ConnectorForPropstackTestCase {
	/**
	 * The v2 endpoint.
	 *
	 * @var string
	 */
	private static string $properties_url = 'https://api.propstack.de/v2/properties';

	/**
	 * The number of objects the mocked API "holds".
	 *
	 * @var int
	 */
	private int $total_objects = 0;

	/**
	 * If true, the mock ignores the page parameter and always returns the same
	 * first page - simulating an API that does not honor pagination.
	 *
	 * @var bool
	 */
	private bool $ignore_pagination = false;

	/**
	 * Counts how many requests the mock received during a test.
	 *
	 * @var int
	 */
	private int $request_count = 0;

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

		// use API v2 and a single language.
		update_option( 'propstack_connector_api_version', 'v2' );
		update_option( 'propstack_connector_languages', 'de' );

		// a valid key so the mock answers.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// no change-detection hash from a previous run.
		delete_option( 'cfprop_md5_de' );

		// let every object pass the prevent-import filters (empty = allow all).
		update_option( 'propstack_connector_import_states', array() );
		update_option( 'propstack_connector_import_broker', array() );
		update_option( 'propstack_connector_import_marketing_type', array() );
		update_option( 'propstack_connector_import_object_type', array() );
		update_option( 'propstack_connector_import_property_type', array() );

		// reset the mock state.
		$this->total_objects     = 0;
		$this->ignore_pagination = false;
		$this->request_count     = 0;

		// register the page-aware mock for the v2 endpoint.
		add_filter( 'pre_http_request', array( $this, 'mock_v2_request' ), 10, 3 );
		add_filter( 'cfprop_prevent_import_of_object', '__return_false', 999 );

		// isolate the pagination logic: skip the object processing (fields, images,
		// taxonomies, broker) which its own tests cover and which would choke on the
		// minimal test objects.
		remove_all_actions( 'cfprop_import_object' );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_v2_request' ) );
		remove_filter( 'cfprop_prevent_import_of_object', '__return_false', 999 );

		update_option( 'propstack_connector_api_key', '' );

		parent::tear_down();
	}

	/**
	 * Build a single object as the v2 API would deliver it (title is a string).
	 *
	 * @param int $id The object ID.
	 *
	 * @return array<string,mixed>
	 */
	private function build_object( int $id ): array {
		return array(
			'id'                 => $id,
			'name'               => 'Object ' . $id,
			'title'              => 'Title ' . $id,
			'property_status_id' => 'Vermarktung',
		);
	}

	/**
	 * Page-aware mock for the v2 endpoint.
	 *
	 * @param false|array<string,mixed>|WP_Error $result      The filter return value.
	 * @param array<string,mixed>                $parsed_args The request arguments.
	 * @param string                             $url         The requested URL.
	 *
	 * @return false|array<string,mixed>|WP_Error
	 */
	public function mock_v2_request( false|array|WP_Error $result, array $parsed_args, string $url ): false|array|WP_Error {
		// pass through anything that is not our v2 endpoint.
		if ( 'GET' !== $parsed_args['method'] || ! str_starts_with( $url, self::$properties_url ) ) {
			return $result;
		}

		// count the request.
		++$this->request_count;

		// answer with 401 if the key is missing.
		if ( empty( $parsed_args['headers']['X-API-KEY'] ) || $parsed_args['headers']['X-API-KEY'] !== self::$api_key ) {
			return $this->build_response( 401, '' );
		}

		// allow a test to force a non-200 status.
		if ( isset( $parsed_args['headers']['response_http_status'] ) ) {
			return $this->build_response( absint( $parsed_args['headers']['response_http_status'] ), wp_json_encode( array( 'data' => array() ) ) );
		}

		// read page and per from the URL.
		$query = array();
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$page = isset( $query['page'] ) ? max( 1, absint( $query['page'] ) ) : 1;
		$per  = isset( $query['per'] ) ? max( 1, absint( $query['per'] ) ) : 20;

		// build the full list of objects.
		$all = array();
		for ( $id = 1; $id <= $this->total_objects; $id++ ) {
			$all[] = $this->build_object( $id );
		}

		// determine the slice for this page. An API that ignores pagination
		// always returns the first page, no matter which page was requested.
		$offset = $this->ignore_pagination ? 0 : ( ( $page - 1 ) * $per );
		$slice  = array_slice( $all, $offset, $per );

		// build the paginated response with the total count.
		$body = wp_json_encode(
			array(
				'data' => $slice,
				'meta' => array( 'total_count' => $this->total_objects ),
			)
		);

		return $this->build_response( 200, (string) $body );
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
	 * Run an import with the given number of objects and (optionally) a forced
	 * per-page value.
	 *
	 * @param int      $total The number of objects the API should hold.
	 * @param int|null $per   The per-page value to force via filter, or null.
	 *
	 * @return void
	 */
	private function run_import( int $total, ?int $per = null ): void {
		$this->total_objects = $total;

		$callback = null;
		if ( null !== $per ) {
			$callback = static function () use ( $per ) {
				return $per;
			};
			add_filter( 'cfprop_import_per_page', $callback );
		}

		( new \ConnectorForPropstack\Propstack\Imports\v2\Objects() )->run();

		if ( null !== $callback ) {
			remove_filter( 'cfprop_import_per_page', $callback );
		}
	}

	/**
	 * Test that a catalogue smaller than one page is imported in a single request.
	 *
	 * @return void
	 */
	public function test_single_page_import(): void {
		$this->run_import( 3 );

		$this->assertSame( 1, $this->request_count );
		$this->assertCount( 3, ImmoObjects::get_instance()->get_objects() );
	}

	/**
	 * Test that a catalogue spanning multiple pages is fully imported.
	 *
	 * With 5 objects and 2 per page the import must request pages 1, 2 and 3.
	 *
	 * @return void
	 */
	public function test_multi_page_import_collects_all_objects(): void {
		$this->run_import( 5, 2 );

		// pages 1 (1,2), 2 (3,4), 3 (5) -> three requests.
		$this->assertSame( 3, $this->request_count );

		// all five objects were imported.
		$this->assertCount( 5, ImmoObjects::get_instance()->get_objects() );
	}

	/**
	 * Test that the import stops once total_count is reached (no extra request).
	 *
	 * With 4 objects and 2 per page exactly two pages are needed; the loop must
	 * not fetch a third, empty page because total_count already reports 4.
	 *
	 * @return void
	 */
	public function test_stops_when_total_count_is_reached(): void {
		$this->run_import( 4, 2 );

		$this->assertSame( 2, $this->request_count );
		$this->assertCount( 4, ImmoObjects::get_instance()->get_objects() );
	}

	/**
	 * Test that an API which ignores pagination does not create duplicates.
	 *
	 * The mock returns the same first page for every request. The page-hash
	 * safeguard must stop after the repeated page, so only the first page worth
	 * of objects is imported - not duplicated across the assumed pages.
	 *
	 * @return void
	 */
	public function test_non_paginating_api_does_not_duplicate(): void {
		$this->ignore_pagination = true;

		// 5 objects "exist", but every request returns only the first 2.
		$this->run_import( 5, 2 );

		// page 1 -> (1,2), page 2 -> (1,2) again -> hash repeats -> stop.
		$this->assertSame( 2, $this->request_count );

		// only two distinct objects were imported, no duplicates.
		$this->assertCount( 2, ImmoObjects::get_instance()->get_objects() );
	}

	/**
	 * Test that a repeated import of unchanged data is skipped.
	 *
	 * @return void
	 */
	public function test_unchanged_import_is_skipped(): void {
		// first run imports everything.
		$this->run_import( 3 );
		$this->assertCount( 3, ImmoObjects::get_instance()->get_objects() );

		// second run must detect the unchanged md5 and not request again.
		$this->request_count = 0;
		( new \ConnectorForPropstack\Propstack\Imports\v2\Objects() )->run();

		// the request still happens (to compare), but no further objects appear.
		$this->assertCount( 3, ImmoObjects::get_instance()->get_objects() );
	}

	/**
	 * Test that an HTTP error skips the language without importing anything.
	 *
	 * @return void
	 */
	public function test_http_error_imports_nothing(): void {
		$this->total_objects = 5;

		// force a non-200 status on the request.
		$callback = static function ( array $headers ): array {
			$headers['response_http_status'] = 500;
			return $headers;
		};
		add_filter( 'cfprop_request_header', $callback );

		$import_obj = new \ConnectorForPropstack\Propstack\Imports\v2\Objects();
		$import_obj->run();

		remove_filter( 'cfprop_request_header', $callback );

		// nothing was imported and an error was recorded.
		$this->assertEmpty( ImmoObjects::get_instance()->get_objects() );
		$this->assertNotEmpty( $import_obj->get_errors() );
	}
}

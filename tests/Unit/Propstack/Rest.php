<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\Rest.
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_REST_Request;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\Rest.
 *
 * These endpoints are consumed by the block editor. They are tested through the
 * REST server so the registered routes, the permission callbacks and the
 * parameter handling are covered the same way the editor uses them.
 */
class Rest extends ConnectorForPropstackTestCase {
	/**
	 * The namespace of our REST routes.
	 *
	 * @var string
	 */
	private string $namespace = '/connector-for-propstack/v1';

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// make sure the REST server is initialized with our routes.
		global $wp_rest_server;
		$wp_rest_server = new \WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	/**
	 * Set the current user to someone who is allowed to use our endpoints.
	 *
	 * @return void
	 */
	private function login_as_editor(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
	}

	/**
	 * Test that our routes are registered.
	 *
	 * @return void
	 */
	public function test_routes_are_registered(): void {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( $this->namespace . '/fields', $routes );
		$this->assertArrayHasKey( $this->namespace . '/filters', $routes );
	}

	/**
	 * Test that the fields endpoint is not public.
	 *
	 * @return void
	 */
	public function test_fields_endpoint_requires_capability(): void {
		// make sure nobody is logged in.
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', $this->namespace . '/fields' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * Test that the filters endpoint is not public.
	 *
	 * @return void
	 */
	public function test_filters_endpoint_requires_capability(): void {
		// make sure nobody is logged in.
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', $this->namespace . '/filters' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * Test that an editor gets a list of fields.
	 *
	 * @return void
	 */
	public function test_fields_endpoint_returns_list(): void {
		$this->login_as_editor();

		$request  = new WP_REST_Request( 'GET', $this->namespace . '/fields' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $response->get_data() );
		$this->assertNotEmpty( $response->get_data() );
	}

	/**
	 * Test that every returned field carries the keys the block editor expects.
	 *
	 * The blocks map the response to { label, value } for their select controls,
	 * so a renamed key would break the editor without any PHP error.
	 *
	 * @return void
	 */
	public function test_fields_endpoint_returns_expected_structure(): void {
		$this->login_as_editor();

		$request  = new WP_REST_Request( 'GET', $this->namespace . '/fields' );
		$response = rest_get_server()->dispatch( $request );

		foreach ( $response->get_data() as $field ) {
			$this->assertIsArray( $field );
			$this->assertArrayHasKey( 'label', $field );
			$this->assertArrayHasKey( 'value', $field );
		}
	}

	/**
	 * Test that the fields endpoint respects the requested field category.
	 *
	 * Hint: this covers the case where the description block asks for the
	 * descriptions only. It fails as long as Rest::get_fields() does not accept
	 * the WP_REST_Request and forwards the parameter.
	 *
	 * @return void
	 */
	public function test_fields_endpoint_respects_field_category(): void {
		$this->login_as_editor();

		// get all fields.
		$request = new WP_REST_Request( 'GET', $this->namespace . '/fields' );
		$all     = rest_get_server()->dispatch( $request )->get_data();

		// get the fields of one category only.
		$request = new WP_REST_Request( 'GET', $this->namespace . '/fields' );
		$request->set_param( 'field_category', 'descriptions' );
		$filtered = rest_get_server()->dispatch( $request )->get_data();

		// the filtered list must not be empty.
		$this->assertNotEmpty( $filtered );

		// and it must be smaller than the complete list.
		$this->assertLessThan( count( $all ), count( $filtered ) );
	}

	/**
	 * Test that an unknown field category returns an empty list instead of everything.
	 *
	 * @return void
	 */
	public function test_fields_endpoint_with_unknown_category(): void {
		$this->login_as_editor();

		$request = new WP_REST_Request( 'GET', $this->namespace . '/fields' );
		$request->set_param( 'field_category', 'this-category-does-not-exist' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $response->get_data() );
		$this->assertEmpty( $response->get_data() );
	}

	/**
	 * Test that an editor gets a list of filters.
	 *
	 * @return void
	 */
	public function test_filters_endpoint_returns_list(): void {
		$this->login_as_editor();

		$request  = new WP_REST_Request( 'GET', $this->namespace . '/filters' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $response->get_data() );

		foreach ( $response->get_data() as $filter ) {
			$this->assertArrayHasKey( 'id', $filter );
			$this->assertArrayHasKey( 'label', $filter );
			$this->assertArrayHasKey( 'value', $filter );
		}
	}

	/**
	 * Test that the IDs returned by the filters endpoint are unique.
	 *
	 * Hint: the IDs are built from two nested loop indexes ( $index1 + $index2 + 1 ),
	 * which can produce the same ID for different filters. The block editor uses
	 * them as keys, so duplicates would lead to missing entries.
	 *
	 * @return void
	 */
	public function test_filters_endpoint_returns_unique_ids(): void {
		$this->login_as_editor();

		$request  = new WP_REST_Request( 'GET', $this->namespace . '/filters' );
		$response = rest_get_server()->dispatch( $request );

		$ids = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertSameSize( $ids, array_unique( $ids ) );
	}
}

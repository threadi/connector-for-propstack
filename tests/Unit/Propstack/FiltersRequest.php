<?php
/**
 * File for tests against the request handling of \ConnectorForPropstack\Propstack\Filters.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Plugin\Cache;
use ConnectorForPropstack\Propstack\Filters;
use ConnectorForPropstack\Propstack\Filters\Cities;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against the request handling of \ConnectorForPropstack\Propstack\Filters
 * and the cache of \ConnectorForPropstack\Propstack\Filters\Cities.
 */
class FiltersRequest extends ConnectorForPropstackTestCase {
	/**
	 * Counter for the queries for objects.
	 *
	 * @var int
	 */
	private int $object_queries = 0;

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// start with an empty request.
		$_GET  = array();
		$_POST = array();

		// use GET per default.
		delete_option( 'propstack_connector_filter_use_post' );

		// start with an empty cache.
		Cache::get_instance()->clear_cache();
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$_GET  = array();
		$_POST = array();

		delete_option( 'propstack_connector_filter_use_post' );
		remove_filter( 'posts_request', array( $this, 'count_object_queries' ), 10 );
		Cache::get_instance()->clear_cache();

		parent::tear_down();
	}

	/**
	 * Count the queries for objects.
	 *
	 * @param string    $request The SQL request.
	 * @param \WP_Query $query   The query object.
	 *
	 * @return string
	 */
	public function count_object_queries( string $request, \WP_Query $query ): string {
		if ( \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() === $query->get( 'post_type' ) ) {
			++$this->object_queries;
		}
		return $request;
	}

	/**
	 * Return the name of the cache option for the cities filter.
	 *
	 * @return string
	 */
	private function get_cities_cache_option(): string {
		return 'cfprop_cache_filter_cities_' . \ConnectorForPropstack\Plugin\Languages::get_instance()->get_current_lang();
	}

	/**
	 * Test that GET is used as default method.
	 *
	 * @return void
	 */
	public function test_default_method_is_get(): void {
		$this->assertSame( INPUT_GET, Filters::get_instance()->get_method_for_filter_input() );
	}

	/**
	 * Test that POST is used if configured.
	 *
	 * @return void
	 */
	public function test_method_is_post_if_configured(): void {
		update_option( 'propstack_connector_filter_use_post', 1 );

		$this->assertSame( INPUT_POST, Filters::get_instance()->get_method_for_filter_input() );
	}

	/**
	 * Test that the filters are read from $_GET per default.
	 *
	 * @return void
	 */
	public function test_requested_filters_from_get(): void {
		$_GET['filter']  = array( 'city' => 'Berlin' );
		$_POST['filter'] = array( 'city' => 'Hamburg' );

		$this->assertSame( array( 'city' => 'Berlin' ), Filters::get_instance()->get_requested_filters() );
	}

	/**
	 * Test that the filters are read from $_POST if configured.
	 *
	 * @return void
	 */
	public function test_requested_filters_from_post(): void {
		update_option( 'propstack_connector_filter_use_post', 1 );

		$_GET['filter']  = array( 'city' => 'Berlin' );
		$_POST['filter'] = array( 'city' => 'Hamburg' );

		$this->assertSame( array( 'city' => 'Hamburg' ), Filters::get_instance()->get_requested_filters() );
	}

	/**
	 * Test that no filters are returned if the configured method has no values.
	 *
	 * @return void
	 */
	public function test_requested_filters_without_values(): void {
		// only POST data, but GET is configured.
		$_POST['filter'] = array( 'city' => 'Hamburg' );
		$this->assertSame( array(), Filters::get_instance()->get_requested_filters() );

		// only GET data, but POST is configured.
		update_option( 'propstack_connector_filter_use_post', 1 );
		$_POST = array();

		$_GET['filter'] = array( 'city' => 'Berlin' );
		$this->assertSame( array(), Filters::get_instance()->get_requested_filters() );
	}

	/**
	 * Test that the requested values are sanitized.
	 *
	 * @return void
	 */
	public function test_requested_filters_are_sanitized(): void {
		// WordPress adds slashes to the request data.
		$_GET['filter'] = wp_slash(
			array(
				'city'      => '  <script>alert(1)</script>Ber"lin  ',
				'object_id' => "42\n<b>x</b>",
				'nested'    => array( 'a' => 'b' ),
			)
		);

		$filters = Filters::get_instance()->get_requested_filters();

		$this->assertSame( 'Ber"lin', $filters['city'] );
		$this->assertSame( '42 x', $filters['object_id'] );
		$this->assertSame( '', $filters['nested'] );
	}

	/**
	 * Test that the sanitized POST values are used by the cities filter for the query.
	 *
	 * @return void
	 */
	public function test_cities_filter_uses_requested_filter(): void {
		update_option( 'propstack_connector_filter_use_post', 1 );
		$_POST['filter'] = array( 'city' => '<b>Musterhausen</b>' );

		$query_params = Cities::get_instance()->filter( array() );

		$this->assertSame( 'city', $query_params['meta_query'][0]['key'] );
		$this->assertSame( 'Musterhausen', $query_params['meta_query'][0]['value'] );
	}

	/**
	 * Test that an empty list of cities is cached, so the objects are not queried again.
	 *
	 * @return void
	 */
	public function test_cities_cache_empty_list(): void {
		add_filter( 'posts_request', array( $this, 'count_object_queries' ), 10, 2 );

		// first call: queries the objects.
		$this->assertSame( array(), Cities::get_instance()->get() );
		$this->assertSame( 1, $this->object_queries );

		// the empty list is cached with an expiration.
		$cache = Cache::get( 'filter_cities' );
		$this->assertIsArray( $cache );
		$this->assertSame( array(), $cache['list'] );
		$this->assertGreaterThan( time(), $cache['expires'] );

		// second call: uses the cache.
		$this->assertSame( array(), Cities::get_instance()->get() );
		$this->assertSame( 1, $this->object_queries );
	}

	/**
	 * Test that an expired empty cache results in a new query.
	 *
	 * @return void
	 */
	public function test_cities_cache_expired(): void {
		Cache::set(
			'filter_cities',
			array(
				'list'    => array(),
				'expires' => time() - 10,
			)
		);

		add_filter( 'posts_request', array( $this, 'count_object_queries' ), 10, 2 );

		Cities::get_instance()->get();

		$this->assertSame( 1, $this->object_queries );
	}

	/**
	 * Test that a cached list of cities is used without a query.
	 *
	 * @return void
	 */
	public function test_cities_cache_filled_list(): void {
		Cache::set(
			'filter_cities',
			array(
				'list'    => array( 'Musterhausen' => 'Musterhausen' ),
				'expires' => 0,
			)
		);

		add_filter( 'posts_request', array( $this, 'count_object_queries' ), 10, 2 );

		$filters = Cities::get_instance()->get();

		$this->assertSame( 0, $this->object_queries );
		$this->assertCount( 1, $filters );
		$this->assertSame( 'city', $filters[0]->get_filter_name() );
	}

	/**
	 * Test that the cache is not autoloaded.
	 *
	 * @return void
	 */
	public function test_cache_is_not_autoloaded(): void {
		global $wpdb;

		// fill the cache via the filter.
		Cities::get_instance()->get();

		$option = $this->get_cities_cache_option();

		// check the autoload column in the database.
		$autoload = $wpdb->get_var( $wpdb->prepare( 'SELECT autoload FROM ' . $wpdb->options . ' WHERE option_name = %s', $option ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Test check.
		$this->assertNotNull( $autoload, 'The cache option has not been saved.' );
		$this->assertContains( $autoload, array( 'no', 'off', 'auto-off' ) );

		// and the list of autoloaded options.
		wp_cache_delete( 'alloptions', 'options' );
		$this->assertArrayNotHasKey( $option, wp_load_alloptions() );
	}
}

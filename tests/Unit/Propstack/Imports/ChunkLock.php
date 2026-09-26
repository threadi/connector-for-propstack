<?php
/**
 * File for tests against the chunk lock of \ConnectorForPropstack\Propstack\Import_Base.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\Imports;

use ConnectorForPropstack\Propstack\Import_Base;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use ConnectorForPropstack\Propstack\States;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;
use WP_HTTP_Requests_Response;
use WP_Query;

/**
 * Object for tests against the chunk lock and the running marker of the object imports.
 *
 * Every run of an import (the first one and each continuation) takes a lock in the option
 * "cfprop_import_chunk_lock", so the same chunk is never processed by two requests at the
 * same time. The lock holds "<id>|<timestamp>" and is treated as stale after a TTL.
 *
 * Hint: this class registers its own page-aware mock for the v1 and the v2 endpoint with a
 * later priority than the mock of the shared test case.
 */
class ChunkLock extends ConnectorForPropstackTestCase {
	/**
	 * The v1 endpoint.
	 *
	 * @var string
	 */
	private static string $units_url = 'https://api.propstack.de/v1/units';

	/**
	 * The v2 endpoint.
	 *
	 * @var string
	 */
	private static string $properties_url = 'https://api.propstack.de/v2/properties';

	/**
	 * The option which holds the lock of a single chunk.
	 *
	 * @var string
	 */
	private static string $lock_option = 'cfprop_import_chunk_lock';

	/**
	 * The option which holds the work list of a paginated import.
	 *
	 * @var string
	 */
	private static string $work_list_option = 'cfprop_objects_to_import';

	/**
	 * The option which holds the position of a paginated import.
	 *
	 * @var string
	 */
	private static string $offset_option = 'cfprop_objects_import_offset';

	/**
	 * The number of objects the mocked API holds.
	 *
	 * @var int
	 */
	private int $total_objects = 3;

	/**
	 * The HTTP status the mocked API answers with.
	 *
	 * @var int
	 */
	private int $http_status = 200;

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

		// no import and no deletion are running.
		update_option( CFPROP_IMPORT_RUNNING, 0 );
		update_option( CFPROP_DELETE_RUNNING, 0 );

		// no paginated import is in progress and no lock exists.
		$this->clear_import_state();
		$this->delete_lock();

		// use a single language.
		update_option( 'propstack_connector_languages', 'de' );

		// set a pseudo-key.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// reset the mock state.
		$this->total_objects = 3;
		$this->http_status   = 200;
		$this->request_count = 0;

		// register the page-aware mock after the one of the shared test case.
		add_filter( 'pre_http_request', array( $this, 'mock_request' ), 20, 3 );

		// let every object pass the prevent-import filters.
		add_filter( 'cfprop_prevent_import_of_object', '__return_false', 999 );

		// skip the object processing, which has its own tests.
		remove_all_actions( 'cfprop_import_object' );

		// initialize the states object (it defines its constants only for API v2).
		update_option( 'propstack_connector_api_version', 'v2' );
		States::get_instance()->init();

		// run the activation.
		\ConnectorForPropstack\Propstack\Propstack::get_instance()->activation();
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_request' ), 20 );
		remove_filter( 'cfprop_prevent_import_of_object', '__return_false', 999 );
		remove_filter( 'cfprop_object_import_limit', array( $this, 'set_limit_to_one' ) );
		remove_filter( 'cfprop_import_chunk_lock_ttl', array( $this, 'set_ttl_to_ten_seconds' ) );

		// remove every object this test created.
		foreach ( $this->get_object_post_ids() as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		// remove the state of the import and the lock.
		$this->clear_import_state();
		$this->delete_lock();
		delete_option( 'cfprop_md5_de' );
		delete_transient( 'cfprop_import_chunk_locked_logged' );
		update_option( CFPROP_IMPORT_RUNNING, 0 );

		// remove the pseudo-key.
		update_option( 'propstack_connector_api_key', '' );

		parent::tear_down();
	}

	/**
	 * Return one as the limit of objects per request.
	 *
	 * @return int
	 */
	public function set_limit_to_one(): int {
		return 1;
	}

	/**
	 * Return ten seconds as TTL of the chunk lock.
	 *
	 * @return int
	 */
	public function set_ttl_to_ten_seconds(): int {
		return 10;
	}

	/**
	 * Remove the complete state of a paginated import, including every block.
	 *
	 * @return void
	 */
	private function clear_import_state(): void {
		global $wpdb;

		// get every option of the work list.
		$names = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Test helper.
			$wpdb->prepare(
				'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name = %s OR option_name LIKE %s',
				self::$work_list_option,
				$wpdb->esc_like( self::$work_list_option . '_block_' ) . '%'
			)
		);

		// delete them.
		foreach ( (array) $names as $name ) {
			delete_option( (string) $name );
		}

		// delete the position.
		delete_option( self::$offset_option );
	}

	/**
	 * Insert a lock row as another process would do.
	 *
	 * @param string $token The value of the lock.
	 *
	 * @return void
	 */
	private function insert_lock( string $token ): void {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Test helper.
			$wpdb->options,
			array(
				'option_name'  => self::$lock_option,
				'option_value' => $token,
				'autoload'     => 'no',
			)
		);
		wp_cache_delete( self::$lock_option, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/**
	 * Return the value of the lock row directly from the database (null if it does not exist).
	 *
	 * @return string|null
	 */
	private function get_lock(): ?string {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s", self::$lock_option ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test helper.
	}

	/**
	 * Remove the lock row.
	 *
	 * @return void
	 */
	private function delete_lock(): void {
		global $wpdb;

		$wpdb->delete( $wpdb->options, array( 'option_name' => self::$lock_option ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Test helper.
		wp_cache_delete( self::$lock_option, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/**
	 * Return the post-IDs of all objects, regardless of their status.
	 *
	 * @return array<int,int>
	 */
	private function get_object_post_ids(): array {
		$query = new WP_Query(
			array(
				'post_type'      => ImmoObject::get_instance()->get_name(),
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		return array_map( 'absint', $query->get_posts() );
	}

	/**
	 * Return the error codes of the given import object.
	 *
	 * @param Import_Base $import_obj The import object.
	 *
	 * @return array<int,string>
	 */
	private function get_error_codes( Import_Base $import_obj ): array {
		return array_map(
			fn( $error ) => (string) $error->get_error_code(),
			$import_obj->get_errors()
		);
	}

	/**
	 * Return a new import object for the given API version.
	 *
	 * @param string $version The API version ("v1" or "v2").
	 *
	 * @return Import_Base
	 */
	private function get_import_obj( string $version ): Import_Base {
		update_option( 'propstack_connector_api_version', $version );

		if ( 'v1' === $version ) {
			return new \ConnectorForPropstack\Propstack\Imports\v1\Objects();
		}

		return new \ConnectorForPropstack\Propstack\Imports\v2\Objects();
	}

	/**
	 * Run the import until it reports that it is completed.
	 *
	 * @param string $version The API version ("v1" or "v2").
	 *
	 * @return Import_Base The import object of the last run.
	 */
	private function run_complete_import( string $version ): Import_Base {
		$runs = 0;

		do {
			$import_obj = $this->get_import_obj( $version );
			$import_obj->run();

			++$runs;

			// safeguard so a broken offset cannot hang the test suite.
			$this->assertLessThan( 50, $runs, 'The import did not finish.' );
		} while ( $import_obj->has_load_more() );

		return $import_obj;
	}

	/**
	 * Page-aware mock for the v1 and the v2 endpoint.
	 *
	 * @param false|array<string,mixed>|WP_Error $result      The filter return value.
	 * @param array<string,mixed>                $parsed_args The request arguments.
	 * @param string                             $url         The requested URL.
	 *
	 * @return false|array<string,mixed>|WP_Error
	 */
	public function mock_request( false|array|WP_Error $result, array $parsed_args, string $url ): false|array|WP_Error {
		$is_v1 = str_starts_with( $url, self::$units_url );

		// pass through anything that is not one of our endpoints.
		if ( 'GET' !== $parsed_args['method'] || ( ! $is_v1 && ! str_starts_with( $url, self::$properties_url ) ) ) {
			return $result;
		}

		// count the request.
		++$this->request_count;

		// answer with the forced HTTP status.
		if ( 200 !== $this->http_status ) {
			return $this->build_response( $this->http_status, '' );
		}

		// read page and per from the URL.
		$query = array();
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$page = isset( $query['page'] ) ? max( 1, absint( $query['page'] ) ) : 1;
		$per  = isset( $query['per'] ) ? max( 1, absint( $query['per'] ) ) : 100;

		// build the slice of this page in the format of the used version.
		$slice = array();
		foreach ( array_slice( range( 1, $this->total_objects ), ( $page - 1 ) * $per, $per ) as $id ) {
			$slice[] = array(
				'id'    => $id,
				'title' => $is_v1 ? array( 'value' => 'Title ' . $id ) : 'Title ' . $id,
			);
		}

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
	 * Return the API versions to test.
	 *
	 * @return array<string,array<int,string>>
	 */
	public function provide_versions(): array {
		return array(
			'v1' => array( 'v1' ),
			'v2' => array( 'v2' ),
		);
	}

	/**
	 * Test that a fresh lock of another process blocks the run completely.
	 *
	 * @dataProvider provide_versions
	 *
	 * @param string $version The API version.
	 *
	 * @return void
	 */
	public function test_fresh_lock_blocks_the_run( string $version ): void {
		$token = 'x|' . time();
		$this->insert_lock( $token );

		$import_obj = $this->get_import_obj( $version );
		$import_obj->run();

		// the run reports the lock.
		$this->assertTrue( $import_obj->is_locked() );
		$this->assertSame( array( 'propstack_object_import_chunk_locked' ), $this->get_error_codes( $import_obj ) );

		// nothing has been requested or imported.
		$this->assertSame( 0, $this->request_count );
		$this->assertEmpty( $this->get_object_post_ids() );
		$this->assertSame( 0, absint( get_option( CFPROP_IMPORT_RUNNING ) ) );

		// outside of AJAX no further run is requested.
		$this->assertFalse( $import_obj->has_load_more() );

		// the lock of the other process is untouched.
		$this->assertSame( $token, $this->get_lock() );
	}

	/**
	 * Test that a fresh lock does not change the state of a paginated import in progress.
	 *
	 * @return void
	 */
	public function test_fresh_lock_blocks_a_continuation_chunk(): void {
		add_filter( 'cfprop_object_import_limit', array( $this, 'set_limit_to_one' ) );

		// the first chunk processes one object.
		$first = $this->get_import_obj( 'v2' );
		$first->run();
		$this->assertTrue( $first->has_load_more() );
		$this->assertSame( 1, absint( get_option( self::$offset_option ) ) );

		// another process holds the lock now.
		$this->insert_lock( 'x|' . time() );

		$second = $this->get_import_obj( 'v2' );
		$second->run();

		// the chunk has been skipped, the position is unchanged.
		$this->assertContains( 'propstack_object_import_chunk_locked', $this->get_error_codes( $second ) );
		$this->assertSame( 1, absint( get_option( self::$offset_option ) ) );
		$this->assertCount( 1, $this->get_object_post_ids() );
	}

	/**
	 * Test that a lock older than the default TTL is removed and the import runs.
	 *
	 * @dataProvider provide_versions
	 *
	 * @param string $version The API version.
	 *
	 * @return void
	 */
	public function test_stale_lock_is_removed_and_the_import_runs( string $version ): void {
		$this->insert_lock( 'x|' . ( time() - 11 * MINUTE_IN_SECONDS ) );

		$import_obj = $this->run_complete_import( $version );

		$this->assertFalse( $import_obj->is_locked() );
		$this->assertNotContains( 'propstack_object_import_chunk_locked', $this->get_error_codes( $import_obj ) );
		$this->assertCount( 3, $this->get_object_post_ids() );

		// neither the stale lock nor a new one is left.
		$this->assertNull( $this->get_lock() );
	}

	/**
	 * Test that the TTL of the lock can be changed via filter.
	 *
	 * @return void
	 */
	public function test_lock_ttl_filter_is_used(): void {
		// a lock of 30 seconds is fresh with the default TTL.
		$this->insert_lock( 'x|' . ( time() - 30 ) );

		$blocked = $this->get_import_obj( 'v2' );
		$blocked->run();
		$this->assertTrue( $blocked->is_locked() );

		// with a TTL of ten seconds it is stale.
		add_filter( 'cfprop_import_chunk_lock_ttl', array( $this, 'set_ttl_to_ten_seconds' ) );

		$import_obj = $this->run_complete_import( 'v2' );

		$this->assertFalse( $import_obj->is_locked() );
		$this->assertCount( 3, $this->get_object_post_ids() );
		$this->assertNull( $this->get_lock() );
	}

	/**
	 * Test that the lock is released after every chunk and after the complete import.
	 *
	 * @dataProvider provide_versions
	 *
	 * @param string $version The API version.
	 *
	 * @return void
	 */
	public function test_lock_is_released_after_each_run( string $version ): void {
		add_filter( 'cfprop_object_import_limit', array( $this, 'set_limit_to_one' ) );

		// after a single chunk.
		$first = $this->get_import_obj( $version );
		$first->run();
		$this->assertTrue( $first->has_load_more() );
		$this->assertNull( $this->get_lock() );
		$this->assertFalse( get_option( self::$lock_option ) );

		// after the complete import.
		$last = $this->run_complete_import( $version );
		$this->assertEmpty( $last->get_errors() );
		$this->assertNull( $this->get_lock() );
		$this->assertFalse( get_option( self::$lock_option ) );
	}

	/**
	 * Test that the lock is released also if the import failed.
	 *
	 * @return void
	 */
	public function test_lock_is_released_after_a_failed_run(): void {
		$this->http_status = 500;

		$import_obj = $this->get_import_obj( 'v2' );
		$import_obj->run();

		$this->assertContains( 'propstack_object_import_http_status', $this->get_error_codes( $import_obj ) );
		$this->assertNull( $this->get_lock() );
	}

	/**
	 * Test that the running marker is refreshed on continuation chunks.
	 *
	 * Otherwise a long import would look stale after an hour and a second import could
	 * start in parallel.
	 *
	 * @dataProvider provide_versions
	 *
	 * @param string $version The API version.
	 *
	 * @return void
	 */
	public function test_running_marker_is_refreshed_on_continuation_chunks( string $version ): void {
		add_filter( 'cfprop_object_import_limit', array( $this, 'set_limit_to_one' ) );

		// the first chunk.
		$first = $this->get_import_obj( $version );
		$first->run();
		$this->assertTrue( $first->has_load_more() );

		// pretend the first chunk ran ten minutes ago.
		$old = time() - 10 * MINUTE_IN_SECONDS;
		update_option( CFPROP_IMPORT_RUNNING, $old );

		// the continuation chunk.
		$second = $this->get_import_obj( $version );
		$second->run();

		// the import is still in progress and the marker has been refreshed.
		$this->assertTrue( $second->has_load_more() );
		$this->assertGreaterThan( $old, absint( get_option( CFPROP_IMPORT_RUNNING ) ) );
		$this->assertGreaterThanOrEqual( time() - 5, absint( get_option( CFPROP_IMPORT_RUNNING ) ) );
		$this->assertSame( 2, absint( get_option( self::$offset_option ) ) );
	}
}

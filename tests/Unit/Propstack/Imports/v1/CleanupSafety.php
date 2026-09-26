<?php
/**
 * File for tests against the cleanup safety of \ConnectorForPropstack\Propstack\Imports\v1\Objects.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\Imports\v1;

use ConnectorForPropstack\Propstack\Imports\v1\Objects;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;
use WP_HTTP_Requests_Response;
use WP_Query;

/**
 * Object for tests against the cleanup safety of the object import via API v1.
 *
 * After a complete import every object which has not been delivered by the API is removed.
 * This must never happen if the API delivered incomplete data, as it would delete objects
 * which still exist in Propstack. These tests pin that an incomplete run (HTTP error on a
 * later page, broken JSON, fewer objects than announced) does not remove anything, and that
 * the ratio guard stops a cleanup which would remove too many objects at once.
 *
 * Hint: the shared test case answers every request to the v1 units URL with a fixed file.
 * This class registers its own page-aware mock with a later priority, so it overrides
 * that answer.
 */
class CleanupSafety extends ConnectorForPropstackTestCase {
	/**
	 * The v1 endpoint.
	 *
	 * @var string
	 */
	private static string $units_url = 'https://api.propstack.de/v1/units';

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
	 * The Propstack-IDs of the objects the mocked API delivers.
	 *
	 * @var array<int,int>
	 */
	private array $api_ids = array();

	/**
	 * The total count the mocked API reports (null = the real amount of objects).
	 *
	 * @var int|null
	 */
	private ?int $reported_total = null;

	/**
	 * List of pages with a forced failure (page => "http_500", "invalid_json" or "missing_data").
	 *
	 * @var array<int,string>
	 */
	private array $page_failures = array();

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

		// no paginated import is in progress.
		$this->clear_import_state();

		// use API v1 and a single language.
		update_option( 'propstack_connector_api_version', 'v1' );
		update_option( 'propstack_connector_languages', 'de' );

		// set a pseudo-key.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// no change-detection hash from a previous run.
		delete_option( 'cfprop_md5_de' );

		// reset the mock state.
		$this->api_ids        = array();
		$this->reported_total = null;
		$this->page_failures  = array();
		$this->request_count  = 0;

		// register the page-aware mock after the one of the shared test case.
		add_filter( 'pre_http_request', array( $this, 'mock_v1_request' ), 20, 3 );

		// let every object pass the prevent-import filters.
		add_filter( 'cfprop_prevent_import_of_object', '__return_false', 999 );

		// use two objects per page, so every test has more than one page.
		add_filter( 'cfprop_import_per_page', array( $this, 'set_per_page_to_two' ) );

		// isolate the cleanup logic: skip the object processing (fields, images, taxonomies),
		// only save the Propstack-ID so the objects are found again by the next import.
		remove_all_actions( 'cfprop_import_object' );
		add_action( 'cfprop_import_object', array( $this, 'save_object_id' ), 10, 2 );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_v1_request' ), 20 );
		remove_filter( 'cfprop_prevent_import_of_object', '__return_false', 999 );
		remove_filter( 'cfprop_import_per_page', array( $this, 'set_per_page_to_two' ) );
		remove_filter( 'cfprop_import_max_cleanup_ratio', array( $this, 'allow_full_cleanup' ) );
		remove_filter( 'cfprop_object_import_limit', array( $this, 'set_limit_to_one' ) );
		remove_action( 'cfprop_import_object', array( $this, 'save_object_id' ) );

		// remove every object this test created.
		foreach ( $this->get_object_post_ids() as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		// remove the state of the import.
		$this->clear_import_state();
		delete_option( 'cfprop_md5_de' );
		update_option( CFPROP_IMPORT_RUNNING, 0 );

		// remove the pseudo-key.
		update_option( 'propstack_connector_api_key', '' );

		parent::tear_down();
	}

	/**
	 * Return two as the amount of objects per page.
	 *
	 * @return int
	 */
	public function set_per_page_to_two(): int {
		return 2;
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
	 * Return 1.0 as max. cleanup ratio, so the ratio guard never blocks the cleanup.
	 *
	 * @return float
	 */
	public function allow_full_cleanup(): float {
		return 1.0;
	}

	/**
	 * Save the Propstack-ID of an imported object as the field import would do.
	 *
	 * @param array<string,mixed> $object  The object data from the API.
	 * @param int                 $post_id The post-ID of the object.
	 *
	 * @return void
	 */
	public function save_object_id( array $object, int $post_id ): void {
		update_post_meta( $post_id, 'object_id', (string) $object['id'] );
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
	 * Create objects as a previous import would have left them.
	 *
	 * @param array<int,int> $ids The Propstack-IDs of the objects.
	 *
	 * @return void
	 */
	private function create_existing_objects( array $ids ): void {
		foreach ( $ids as $id ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => ImmoObject::get_instance()->get_name(),
					'post_title'  => 'Existing ' . $id,
					'post_status' => 'publish',
				)
			);
			update_post_meta( $post_id, 'object_id', (string) $id );
			update_post_meta( $post_id, 'language_code', 'de' );

			// mark it with the run ID of an older import.
			update_post_meta( $post_id, 'changed', 1 );
		}
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
	 * Return the sorted Propstack-IDs of all existing objects.
	 *
	 * @return array<int,int>
	 */
	private function get_existing_propstack_ids(): array {
		$ids = array();
		foreach ( $this->get_object_post_ids() as $post_id ) {
			$ids[] = absint( get_post_meta( $post_id, 'object_id', true ) );
		}
		sort( $ids );

		return $ids;
	}

	/**
	 * Return the error codes of the given import object.
	 *
	 * @param Objects $import_obj The import object.
	 *
	 * @return array<int,string>
	 */
	private function get_error_codes( Objects $import_obj ): array {
		return array_map(
			fn( $error ) => (string) $error->get_error_code(),
			$import_obj->get_errors()
		);
	}

	/**
	 * Run the import until it reports that it is completed.
	 *
	 * Every run is a separate import object, as every chunk is a separate request in production.
	 *
	 * @return Objects The import object of the last run.
	 */
	private function run_complete_import(): Objects {
		$runs = 0;

		do {
			$import_obj = new Objects();
			$import_obj->run();

			++$runs;

			// safeguard so a broken offset cannot hang the test suite.
			$this->assertLessThan( 50, $runs, 'The import did not finish.' );
		} while ( $import_obj->has_load_more() );

		return $import_obj;
	}

	/**
	 * Page-aware mock for the v1 endpoint.
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

		// count the request.
		++$this->request_count;

		// read page and per from the URL.
		$query = array();
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$page = isset( $query['page'] ) ? max( 1, absint( $query['page'] ) ) : 1;
		$per  = isset( $query['per'] ) ? max( 1, absint( $query['per'] ) ) : 100;

		// answer with the forced failure for this page.
		if ( isset( $this->page_failures[ $page ] ) ) {
			switch ( $this->page_failures[ $page ] ) {
				case 'http_500':
					return $this->build_response( 500, (string) wp_json_encode( array( 'errors' => array( 'Internal Server Error' ) ) ) );
				case 'invalid_json':
					return $this->build_response( 200, '{"data": [ {"id": 1' );
				case 'missing_data':
					return $this->build_response( 200, (string) wp_json_encode( array( 'meta' => array( 'total_count' => count( $this->api_ids ) ) ) ) );
			}
		}

		// build the slice of this page in the v1 format (the title is an array).
		$slice = array();
		foreach ( array_slice( $this->api_ids, ( $page - 1 ) * $per, $per ) as $id ) {
			$slice[] = array(
				'id'    => $id,
				'title' => array(
					'label' => 'Titel',
					'value' => 'Title ' . $id,
				),
			);
		}

		// build the paginated response with the total count.
		$body = wp_json_encode(
			array(
				'data' => $slice,
				'meta' => array( 'total_count' => null !== $this->reported_total ? $this->reported_total : count( $this->api_ids ) ),
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
	 * Test that an HTTP error on a later page does not remove any existing object.
	 *
	 * @return void
	 */
	public function test_http_error_on_later_page_keeps_existing_objects(): void {
		$this->create_existing_objects( array( 1, 2, 3, 4, 5, 6 ) );

		// the API holds all six objects, but page 2 fails.
		$this->api_ids          = array( 1, 2, 3, 4, 5, 6 );
		$this->page_failures[2] = 'http_500';

		// the ratio guard must not be the reason for keeping the objects.
		add_filter( 'cfprop_import_max_cleanup_ratio', array( $this, 'allow_full_cleanup' ) );

		$import_obj = $this->run_complete_import();

		// the loop stopped at the failing page.
		$this->assertSame( 2, $this->request_count );

		// the error is reported.
		$this->assertContains( 'propstack_object_import_http_status', $this->get_error_codes( $import_obj ) );

		// no object has been removed.
		$this->assertSame( array( 1, 2, 3, 4, 5, 6 ), $this->get_existing_propstack_ids() );

		// the hash is not saved, so the next import processes everything again.
		$this->assertEmpty( get_option( 'cfprop_md5_de' ) );

		// the import is finished.
		$this->assertSame( 0, absint( get_option( CFPROP_IMPORT_RUNNING ) ) );
	}

	/**
	 * Test that an invalid JSON response on a later page does not remove any existing object.
	 *
	 * @return void
	 */
	public function test_invalid_json_on_later_page_keeps_existing_objects(): void {
		$this->create_existing_objects( array( 1, 2, 3, 4, 5, 6 ) );

		$this->api_ids          = array( 1, 2, 3, 4, 5, 6 );
		$this->page_failures[2] = 'invalid_json';

		add_filter( 'cfprop_import_max_cleanup_ratio', array( $this, 'allow_full_cleanup' ) );

		$import_obj = $this->run_complete_import();

		$this->assertContains( 'propstack_object_import_decoding', $this->get_error_codes( $import_obj ) );
		$this->assertSame( array( 1, 2, 3, 4, 5, 6 ), $this->get_existing_propstack_ids() );
	}

	/**
	 * Test that a response without "data" on a later page does not remove any existing object.
	 *
	 * @return void
	 */
	public function test_missing_data_on_later_page_keeps_existing_objects(): void {
		$this->create_existing_objects( array( 1, 2, 3, 4, 5, 6 ) );

		$this->api_ids          = array( 1, 2, 3, 4, 5, 6 );
		$this->page_failures[3] = 'missing_data';

		add_filter( 'cfprop_import_max_cleanup_ratio', array( $this, 'allow_full_cleanup' ) );

		$import_obj = $this->run_complete_import();

		$this->assertContains( 'propstack_object_import_decoding', $this->get_error_codes( $import_obj ) );
		$this->assertSame( array( 1, 2, 3, 4, 5, 6 ), $this->get_existing_propstack_ids() );
	}

	/**
	 * Test that fewer objects than reported by "total_count" do not remove any existing object.
	 *
	 * @return void
	 */
	public function test_fewer_objects_than_total_count_keeps_existing_objects(): void {
		$this->create_existing_objects( array( 1, 2, 3, 4, 5, 6 ) );

		// the API announces six objects but only delivers four.
		$this->api_ids        = array( 1, 2, 3, 4 );
		$this->reported_total = 6;

		add_filter( 'cfprop_import_max_cleanup_ratio', array( $this, 'allow_full_cleanup' ) );

		$import_obj = $this->run_complete_import();

		$this->assertContains( 'propstack_object_import_incomplete', $this->get_error_codes( $import_obj ) );
		$this->assertSame( array( 1, 2, 3, 4, 5, 6 ), $this->get_existing_propstack_ids() );
		$this->assertEmpty( get_option( 'cfprop_md5_de' ) );
	}

	/**
	 * Test that an error of the first chunk still blocks the cleanup in the last chunk.
	 *
	 * Every chunk is a separate request with its own import object, so the error of the
	 * first chunk must survive in the state of the import.
	 *
	 * @return void
	 */
	public function test_error_of_earlier_chunk_blocks_cleanup_in_last_chunk(): void {
		$this->create_existing_objects( array( 1, 2, 3, 4, 5, 6 ) );

		$this->api_ids          = array( 1, 2, 3, 4, 5, 6 );
		$this->page_failures[2] = 'http_500';

		add_filter( 'cfprop_import_max_cleanup_ratio', array( $this, 'allow_full_cleanup' ) );
		add_filter( 'cfprop_object_import_limit', array( $this, 'set_limit_to_one' ) );

		// the first chunk collects the error and needs another run.
		$first = new Objects();
		$first->run();
		$this->assertTrue( $first->has_load_more() );
		$this->assertContains( 'propstack_object_import_http_status', $this->get_error_codes( $first ) );

		// run the remaining chunks.
		$last = $this->run_complete_import();

		// the last chunk reports the error of the first chunk.
		$this->assertContains( 'propstack_object_import_http_status', $this->get_error_codes( $last ) );

		// no object has been removed.
		$this->assertSame( array( 1, 2, 3, 4, 5, 6 ), $this->get_existing_propstack_ids() );
	}

	/**
	 * Test that the cleanup is skipped if it would remove more than half of the objects.
	 *
	 * @return void
	 */
	public function test_cleanup_is_skipped_above_the_ratio(): void {
		$this->create_existing_objects( range( 1, 10 ) );

		// the API delivers only 3 of 10 objects, 7 would be removed.
		$this->api_ids = array( 1, 2, 3 );

		$import_obj = $this->run_complete_import();

		$this->assertContains( 'propstack_object_import_cleanup_skipped', $this->get_error_codes( $import_obj ) );
		$this->assertSame( range( 1, 10 ), $this->get_existing_propstack_ids() );
	}

	/**
	 * Test that the cleanup removes the objects if the ratio filter allows it.
	 *
	 * @return void
	 */
	public function test_cleanup_runs_with_ratio_filter(): void {
		$this->create_existing_objects( range( 1, 10 ) );

		$this->api_ids = array( 1, 2, 3 );

		add_filter( 'cfprop_import_max_cleanup_ratio', array( $this, 'allow_full_cleanup' ) );

		$import_obj = $this->run_complete_import();

		$this->assertEmpty( $this->get_error_codes( $import_obj ) );
		$this->assertSame( array( 1, 2, 3 ), $this->get_existing_propstack_ids() );
	}

	/**
	 * Test that a single object which disappeared from the API is still removed.
	 *
	 * @return void
	 */
	public function test_single_obsolete_object_is_removed(): void {
		$this->create_existing_objects( range( 1, 10 ) );

		// object 7 is no longer delivered.
		$this->api_ids = array( 1, 2, 3, 4, 5, 6, 8, 9, 10 );

		$import_obj = $this->run_complete_import();

		$this->assertEmpty( $this->get_error_codes( $import_obj ) );
		$this->assertSame( array( 1, 2, 3, 4, 5, 6, 8, 9, 10 ), $this->get_existing_propstack_ids() );

		// a complete import saves the hash.
		$this->assertNotEmpty( get_option( 'cfprop_md5_de' ) );
	}
}

<?php
/**
 * File for tests against the chunked runs of \ConnectorForPropstack\Propstack\Imports\v2\Objects.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\Imports\v2;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\States;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;
use WP_HTTP_Requests_Response;

/**
 * Object for tests against the chunked runs of the object import.
 *
 * To prevent timeouts on accounts with many objects the import builds its work list once
 * and processes only a limited amount of objects per request. It then reports "load_more"
 * so the AJAX dialog starts another request. These tests pin that the state which has to
 * survive between the requests is kept, and that everything which must happen exactly once
 * is not repeated per chunk.
 *
 * Hint: the shared test case only mocks the API v1 URL, so this class registers its own
 * filter for the v2 URL.
 */
class ObjectsChunkedImport extends ConnectorForPropstackTestCase {
	/**
	 * The API URL used by the v2 import.
	 *
	 * @var string
	 */
	private static string $properties_url = 'https://api.propstack.de/v2/properties';

	/**
	 * The option which holds the work list of a paginated import.
	 *
	 * @var string
	 */
	private static string $work_list_option = 'propstack_objects_to_import';

	/**
	 * The option which holds the position of a paginated import.
	 *
	 * @var string
	 */
	private static string $offset_option = 'propstack_objects_import_offset';

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// no import and no deletion is running.
		update_option( CFPROP_IMPORT_RUNNING, 0 );
		update_option( CFPROP_DELETE_RUNNING, 0 );

		// no paginated import is in progress.
		delete_option( self::$work_list_option );
		delete_option( self::$offset_option );

		// set language to "de" and use API v2.
		update_option( 'propstack_connector_languages', 'de' );
		update_option( 'propstack_connector_api_version', 'v2' );

		// set a pseudo-key.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// mock the v2 endpoint.
		add_filter( 'pre_http_request', array( $this, 'mock_v2_request' ), 10, 3 );

		// import one object per request.
		add_filter( 'cfprop_object_import_limit', array( $this, 'set_limit_to_one' ) );

		// initialize the states object.
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
		remove_filter( 'pre_http_request', array( $this, 'mock_v2_request' ) );
		remove_filter( 'cfprop_object_import_limit', array( $this, 'set_limit_to_one' ) );

		// remove the pseudo-key.
		update_option( 'propstack_connector_api_key', '' );

		parent::tear_down();
	}

	/**
	 * Set the limit of objects per request to one.
	 *
	 * @return int
	 */
	public function set_limit_to_one(): int {
		return 1;
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

		$requests_response = new \WpOrg\Requests\Response();

		// answer with 401 if the API key is missing.
		if ( empty( $parsed_args['headers']['X-API-KEY'] ) ) {
			$requests_response->status_code = 401;

			return array(
				'http_response' => new WP_HTTP_Requests_Response( $requests_response, $parsed_args['filename'] ),
			);
		}

		// bail with the given status if a wrong one is forced.
		$requests_response->status_code = isset( $parsed_args['headers']['response_http_status'] ) ? $parsed_args['headers']['response_http_status'] : 200;

		// deliver our fixture.
		$json = Helper::get_wp_filesystem()->get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'properties_full.json' );

		return array(
			'http_response' => new WP_HTTP_Requests_Response( $requests_response, $parsed_args['filename'] ),
			'body'          => $json,
		);
	}

	/**
	 * Run one chunk of the import with a fresh import object.
	 *
	 * Every chunk is a separate AJAX request in production, so it must not reuse the
	 * import object of the run before.
	 *
	 * @return \ConnectorForPropstack\Propstack\Imports\v2\Objects
	 */
	private function run_chunk(): \ConnectorForPropstack\Propstack\Imports\v2\Objects {
		$import_obj = new \ConnectorForPropstack\Propstack\Imports\v2\Objects();
		$import_obj->run();

		return $import_obj;
	}

	/**
	 * Test that the first run stops after the limit and keeps its state.
	 *
	 * @return void
	 */
	public function test_first_chunk_stops_and_keeps_its_state(): void {
		$import_obj = $this->run_chunk();

		// another run is needed.
		$this->assertTrue( $import_obj->has_load_more() );

		// the work list and the position survived.
		$this->assertIsArray( get_option( self::$work_list_option ) );
		$this->assertSame( 1, absint( get_option( self::$offset_option ) ) );

		// the import is still marked as running so the progress bar keeps polling.
		$this->assertGreaterThan( 0, absint( get_option( CFPROP_IMPORT_RUNNING ) ) );
	}

	/**
	 * Test that a chunk is not blocked by the running marker of the run before.
	 *
	 * @return void
	 */
	public function test_second_chunk_is_not_blocked_by_the_running_marker(): void {
		$this->run_chunk();

		// the marker of the first chunk is still set.
		$this->assertGreaterThan( 0, absint( get_option( CFPROP_IMPORT_RUNNING ) ) );

		// the second chunk must not report the import as blocked.
		$import_obj = $this->run_chunk();

		$codes = array_map(
			fn( $error ) => $error->get_error_code(),
			$import_obj->get_errors()
		);

		$this->assertNotContains( 'propstack_object_import_is_running', $codes );
	}

	/**
	 * Test that the import is completed over several chunks.
	 *
	 * @return void
	 */
	public function test_import_completes_over_several_chunks(): void {
		$runs = 0;

		do {
			$import_obj = $this->run_chunk();
			++$runs;

			// safeguard so a broken offset cannot hang the test suite.
			$this->assertLessThan( 20, $runs, 'The chunked import did not finish.' );
		} while ( $import_obj->has_load_more() );

		// the fixture holds three objects, so it needs more than one run.
		$this->assertGreaterThan( 1, $runs );

		// the same objects as in a single run must exist.
		$this->assertCount( 2, ImmoObjects::get_instance()->get_objects() );

		// the state of the paginated import is gone.
		$this->assertFalse( get_option( self::$work_list_option, false ) );
		$this->assertFalse( get_option( self::$offset_option, false ) );

		// the lock is released.
		$this->assertSame( 0, absint( get_option( CFPROP_IMPORT_RUNNING ) ) );

		// no error occurred.
		$this->assertEmpty( $import_obj->get_errors() );
	}

	/**
	 * Test that the md5 hash is only saved after the last chunk.
	 *
	 * Saving it earlier would let a later import skip the language although its objects
	 * were not imported completely.
	 *
	 * @return void
	 */
	public function test_md5_is_saved_after_the_last_chunk_only(): void {
		// the first chunk must not save the hash.
		$this->run_chunk();
		$this->assertEmpty( get_option( 'cfprop_md5_de' ) );

		// run the import to its end.
		do {
			$import_obj = $this->run_chunk();
		} while ( $import_obj->has_load_more() );

		// now the hash is stored.
		$this->assertNotEmpty( get_option( 'cfprop_md5_de' ) );
	}

	/**
	 * Test that the preparations run exactly once for the whole import.
	 *
	 * "cfprop_import_object_before_start" clears the cache and triggers the states import,
	 * "cfprop_import_object_set_max_count" feeds the progress bar of the setup. Both would
	 * be wrong if they were repeated per chunk.
	 *
	 * @return void
	 */
	public function test_preparations_run_only_once(): void {
		$before_start = 0;
		$max_count    = 0;

		$count_before_start = function () use ( &$before_start ) {
			++$before_start;
		};
		$count_max_count    = function ( $count ) use ( &$max_count ) {
			++$max_count;

			return $count;
		};

		add_action( 'cfprop_import_object_before_start', $count_before_start );
		add_action( 'cfprop_import_object_set_max_count', $count_max_count );

		// run the import to its end.
		do {
			$import_obj = $this->run_chunk();
		} while ( $import_obj->has_load_more() );

		remove_action( 'cfprop_import_object_before_start', $count_before_start );
		remove_action( 'cfprop_import_object_set_max_count', $count_max_count );

		$this->assertSame( 1, $before_start, 'The preparations were repeated per chunk.' );
		$this->assertSame( 1, $max_count, 'The max count was set more than once.' );
	}

	/**
	 * Test that the closing tasks run exactly once for the whole import.
	 *
	 * @return void
	 */
	public function test_closing_tasks_run_only_once(): void {
		$after   = 0;
		$success = 0;

		$count_after   = function () use ( &$after ) {
			++$after;
		};
		$count_success = function () use ( &$success ) {
			++$success;
		};

		add_action( 'cfprop_import_object_after', $count_after );
		add_action( 'cfprop_import_object_success', $count_success );

		// run the import to its end.
		do {
			$import_obj = $this->run_chunk();
		} while ( $import_obj->has_load_more() );

		remove_action( 'cfprop_import_object_after', $count_after );
		remove_action( 'cfprop_import_object_success', $count_success );

		$this->assertSame( 1, $after, 'The closing tasks were repeated per chunk.' );
		$this->assertSame( 1, $success, 'The success message was set more than once.' );
	}

	/**
	 * Test that a prevented object still moves the position forward.
	 *
	 * If it did not, the next chunk would start at the same object again and the import
	 * would never finish.
	 *
	 * @return void
	 */
	public function test_prevented_object_moves_the_position_forward(): void {
		$prevent_all = fn() => true;

		add_filter( 'cfprop_prevent_import_of_object', $prevent_all );

		$this->run_chunk();

		remove_filter( 'cfprop_prevent_import_of_object', $prevent_all );

		$this->assertSame( 1, absint( get_option( self::$offset_option ) ) );
	}

	/**
	 * Test that the state is removed if the import cannot be continued.
	 *
	 * @return void
	 */
	public function test_state_is_removed_on_a_hard_error(): void {
		$throw = function () {
			throw new \RuntimeException( 'test' );
		};

		add_action( 'cfprop_import_object', $throw );

		// run the import to its end.
		do {
			$import_obj = $this->run_chunk();
		} while ( $import_obj->has_load_more() );

		remove_action( 'cfprop_import_object', $throw );

		// the state of the paginated import is gone and the lock is released.
		$this->assertFalse( get_option( self::$work_list_option, false ) );
		$this->assertFalse( get_option( self::$offset_option, false ) );
		$this->assertSame( 0, absint( get_option( CFPROP_IMPORT_RUNNING ) ) );

		// the hash must not be saved as objects were skipped.
		$this->assertEmpty( get_option( 'cfprop_md5_de' ) );
	}
}

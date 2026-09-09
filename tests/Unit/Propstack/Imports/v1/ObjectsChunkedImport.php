<?php
/**
 * File for tests against the chunked runs of \ConnectorForPropstack\Propstack\Imports\v1\Objects.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\Imports\v1;

use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against the chunked runs of the object import via API v1.
 *
 * To prevent timeouts on accounts with many objects the import builds its work list once
 * and processes only a limited amount of objects per request. It then reports "load_more"
 * so the AJAX dialog starts another request. These tests pin that the state which has to
 * survive between the requests is kept, and that everything which must happen exactly once
 * is not repeated per chunk.
 *
 * Hint: the v1 endpoint is already mocked by the shared test case, so this class only sets
 * the API key and the limit.
 */
class ObjectsChunkedImport extends ConnectorForPropstackTestCase {
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

		// set language to "de" and use API v1.
		update_option( 'propstack_connector_languages', 'de' );
		update_option( 'propstack_connector_api_version', 'v1' );

		// set a pseudo-key.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// import one object per request.
		add_filter( 'cfprop_object_import_limit', array( $this, 'set_limit_to_one' ) );

		// run the activation.
		\ConnectorForPropstack\Propstack\Propstack::get_instance()->activation();
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
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
	 * Run one chunk of the import with a fresh import object.
	 *
	 * Every chunk is a separate AJAX request in production, so it must not reuse the
	 * import object of the run before.
	 *
	 * @return \ConnectorForPropstack\Propstack\Imports\v1\Objects
	 */
	private function run_chunk(): \ConnectorForPropstack\Propstack\Imports\v1\Objects {
		$import_obj = new \ConnectorForPropstack\Propstack\Imports\v1\Objects();
		$import_obj->run();

		return $import_obj;
	}

	/**
	 * Run the complete import in chunks.
	 *
	 * @return \ConnectorForPropstack\Propstack\Imports\v1\Objects The import object of the last run.
	 */
	private function run_complete_import(): \ConnectorForPropstack\Propstack\Imports\v1\Objects {
		$runs = 0;

		do {
			$import_obj = $this->run_chunk();
			++$runs;

			// safeguard so a broken offset cannot hang the test suite.
			$this->assertLessThan( 20, $runs, 'The chunked import did not finish.' );
		} while ( $import_obj->has_load_more() );

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
	 * Test that the work list holds every object of the fixture.
	 *
	 * @return void
	 */
	public function test_work_list_holds_all_objects(): void {
		$this->run_chunk();

		$import_data = get_option( self::$work_list_option );

		$this->assertIsArray( $import_data );
		$this->assertArrayHasKey( 'objects', $import_data );
		$this->assertGreaterThan( 1, count( $import_data['objects'] ), 'The fixture needs more than one object for this test.' );

		// every entry carries its language.
		foreach ( $import_data['objects'] as $entry ) {
			$this->assertSame( 'de', $entry['language_code'] );
			$this->assertIsArray( $entry['object'] );
		}
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
		$import_obj = $this->run_complete_import();

		// the objects of the fixture have been imported.
		$this->assertNotEmpty( ImmoObjects::get_instance()->get_objects() );
		$this->assertTrue( ImmoObjects::get_instance()->has_objects() );

		// the state of the paginated import is gone.
		$this->assertFalse( get_option( self::$work_list_option, false ) );
		$this->assertFalse( get_option( self::$offset_option, false ) );

		// the lock is released.
		$this->assertSame( 0, absint( get_option( CFPROP_IMPORT_RUNNING ) ) );

		// no error occurred.
		$this->assertEmpty( $import_obj->get_errors() );
	}

	/**
	 * Test that a chunked import does not create the same objects twice.
	 *
	 * @return void
	 */
	public function test_chunked_import_is_idempotent(): void {
		$this->run_complete_import();
		$count_after_first_import = count( ImmoObjects::get_instance()->get_objects() );

		// force a second import although nothing changed in Propstack.
		update_option( 'propstack_connector_debug', 1 );
		$this->run_complete_import();
		update_option( 'propstack_connector_debug', 0 );

		$this->assertCount( $count_after_first_import, ImmoObjects::get_instance()->get_objects() );
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
		$this->run_complete_import();

		// now the hash is stored.
		$this->assertNotEmpty( get_option( 'cfprop_md5_de' ) );
	}

	/**
	 * Test that the preparations run exactly once for the whole import.
	 *
	 * "cfprop_import_object_before_start" triggers the states import,
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

		$this->run_complete_import();

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

		$this->run_complete_import();

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
	 * Test that a failing object does not stop the whole import.
	 *
	 * @return void
	 */
	public function test_failing_object_is_skipped_and_the_state_is_removed(): void {
		$throw = function () {
			throw new \RuntimeException( 'test' );
		};

		add_action( 'cfprop_import_object', $throw );

		$import_obj = $this->run_complete_import();

		remove_action( 'cfprop_import_object', $throw );

		// the failure has been reported.
		$codes = array_map(
			fn( $error ) => $error->get_error_code(),
			$import_obj->get_errors()
		);
		$this->assertContains( 'propstack_object_import_error', $codes );

		// the state of the paginated import is gone and the lock is released.
		$this->assertFalse( get_option( self::$work_list_option, false ) );
		$this->assertFalse( get_option( self::$offset_option, false ) );
		$this->assertSame( 0, absint( get_option( CFPROP_IMPORT_RUNNING ) ) );

		// the hash must not be saved as objects were skipped.
		$this->assertEmpty( get_option( 'cfprop_md5_de' ) );
	}
}

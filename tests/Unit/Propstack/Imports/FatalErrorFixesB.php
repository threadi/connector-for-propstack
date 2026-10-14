<?php
/**
 * File for tests against the handling of fatal errors during the import of objects.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\Imports;

use ConnectorForPropstack\Propstack\Import_Base;
use ConnectorForPropstack\Propstack\Schedules\Objects;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use ReflectionProperty;

/**
 * Object for tests against the handling of fatal errors during the import of objects.
 */
class FatalErrorFixesB extends ConnectorForPropstackTestCase {
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
	 * Prepare a running import with a complete work list.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		update_option(
			self::$work_list_option,
			array(
				'blocks'     => 1,
				'block_size' => 10,
				'total'      => 5,
				'phase'      => 'import',
				'run_id'     => 123,
			)
		);
		update_option( self::$work_list_option . '_block_0', array( array( 'language_code' => 'de' ) ), false );
		update_option( self::$offset_option, 2 );
		update_option( CFPROP_IMPORT_RUNNING, time() );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		wp_clear_scheduled_hook( Objects::CONTINUATION_HOOK );
		delete_option( self::$work_list_option );
		delete_option( self::$work_list_option . '_block_0' );
		delete_option( self::$offset_option );
		update_option( CFPROP_IMPORT_RUNNING, 0 );

		parent::tear_down();
	}

	/**
	 * Simulate a fatal error in a new request.
	 *
	 * @return void
	 */
	private function simulate_fatal_error(): void {
		// every request handles its fatal error once.
		$handled = new ReflectionProperty( Import_Base::class, 'fatal_error_handled' );
		$handled->setValue( null, false );

		// the continuation of the last request has been run.
		wp_clear_scheduled_hook( Objects::CONTINUATION_HOOK );

		( new Import_Base() )->process_shutdown_error(
			array(
				'type'    => E_ERROR,
				'message' => 'Allowed memory size exhausted',
				'file'    => __FILE__,
				'line'    => 1,
			)
		);
	}

	/**
	 * Test that the import is continued after a fatal error, but discarded if it repeats without progress.
	 *
	 * @return void
	 */
	public function test_import_is_discarded_after_repeated_fatal_errors_without_progress(): void {
		// the first and the second fatal error lead to a continuation.
		$this->simulate_fatal_error();
		$this->assertIsInt( wp_next_scheduled( Objects::CONTINUATION_HOOK ) );
		$this->simulate_fatal_error();
		$this->assertIsInt( wp_next_scheduled( Objects::CONTINUATION_HOOK ) );
		$this->assertNotEmpty( get_option( self::$work_list_option ) );

		// the third one at the same position discards the import.
		$this->simulate_fatal_error();
		$this->assertFalse( wp_next_scheduled( Objects::CONTINUATION_HOOK ) );
		$this->assertEmpty( get_option( self::$work_list_option ) );
		$this->assertFalse( get_option( self::$work_list_option . '_block_0' ) );
		$this->assertSame( 0, absint( get_option( CFPROP_IMPORT_RUNNING ) ) );
	}

	/**
	 * Test that the counter starts again if the import progresses between the fatal errors.
	 *
	 * @return void
	 */
	public function test_counter_is_reset_on_progress(): void {
		for ( $offset = 2; $offset < 6; $offset++ ) {
			update_option( self::$offset_option, $offset );

			$this->simulate_fatal_error();

			// the import is continued.
			$this->assertIsInt( wp_next_scheduled( Objects::CONTINUATION_HOOK ) );

			$import_data = get_option( self::$work_list_option );
			$this->assertIsArray( $import_data );
			$this->assertSame( 1, $import_data['fatal_count'] );
		}
	}
}

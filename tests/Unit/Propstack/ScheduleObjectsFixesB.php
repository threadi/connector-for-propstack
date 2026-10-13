<?php
/**
 * File for tests against the time limited run of the scheduled import of objects.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Propstack\Schedules\Objects;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against the time limited run of the scheduled import of objects.
 */
class ScheduleObjectsFixesB extends ConnectorForPropstackTestCase {
	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		wp_clear_scheduled_hook( Objects::CONTINUATION_HOOK );
		remove_filter( 'cfprop_schedule_import_time_budget', array( $this, 'get_time_budget' ) );

		parent::tear_down();
	}

	/**
	 * Return 42 as time budget.
	 *
	 * @return int
	 */
	public function get_time_budget(): int {
		return 42;
	}

	/**
	 * Test that the time budget is limited and could be changed via hook.
	 *
	 * @return void
	 */
	public function test_time_budget(): void {
		$time_budget = Objects::get_time_budget();
		$this->assertGreaterThan( 0, $time_budget );
		$this->assertLessThanOrEqual( 240, $time_budget );

		add_filter( 'cfprop_schedule_import_time_budget', array( $this, 'get_time_budget' ) );
		$this->assertSame( 42, Objects::get_time_budget() );
	}

	/**
	 * Test that the continuation is scheduled only once.
	 *
	 * @return void
	 */
	public function test_continuation_is_scheduled_once(): void {
		Objects::schedule_continuation();
		$first = wp_next_scheduled( Objects::CONTINUATION_HOOK );
		$this->assertIsInt( $first );

		Objects::schedule_continuation();
		$this->assertSame( $first, wp_next_scheduled( Objects::CONTINUATION_HOOK ) );

		// the continuation is hooked.
		$this->assertNotFalse( has_action( Objects::CONTINUATION_HOOK, array( Objects::class, 'continue_import' ) ) );
	}

	/**
	 * Test that the continuation does nothing if no import is in progress.
	 *
	 * @return void
	 */
	public function test_continuation_without_running_import(): void {
		delete_option( 'cfprop_objects_to_import' );

		Objects::continue_import();

		$this->assertFalse( wp_next_scheduled( Objects::CONTINUATION_HOOK ) );
		$this->assertEmpty( get_option( 'cfprop_objects_to_import', array() ) );
	}
}

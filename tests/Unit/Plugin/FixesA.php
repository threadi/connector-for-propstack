<?php
/**
 * File for tests against stability fixes in the plugin base.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Plugin;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against intervals, log, locks and helper functions.
 */
class FixesA extends ConnectorForPropstackTestCase {
	/**
	 * Clean up the test environment after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		delete_option( 'cfprop_test_lock' );
		delete_transient( 'cfprop_log_cleanup' );
		update_option( 'propstack_connector_max_age_log_entries', 20 );
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Test that the time of each interval matches its name.
	 *
	 * @return void
	 */
	public function test_interval_times_match_their_names(): void {
		$units = array(
			'minutely' => MINUTE_IN_SECONDS,
			'hourly'   => HOUR_IN_SECONDS,
			'daily'    => DAY_IN_SECONDS,
			'weekly'   => WEEK_IN_SECONDS,
		);

		$intervals = \ConnectorForPropstack\Plugin\Intervals::get_instance()->get_intervals_as_objects();
		$this->assertNotEmpty( $intervals );

		foreach ( $intervals as $interval ) {
			// get the factor and the unit from the name, e.g. "20minutely" or "daily".
			$this->assertSame( 1, preg_match( '/^(\d*)(minutely|hourly|daily|weekly)$/', $interval->get_name(), $matches ), 'Unknown interval name ' . $interval->get_name() );
			$factor = '' === $matches[1] ? 1 : absint( $matches[1] );

			$this->assertSame( $factor * $units[ $matches[2] ], $interval->get_time(), 'Wrong time for interval ' . $interval->get_name() );
		}
	}

	/**
	 * Test that the 20-minutely interval is registered with 20 minutes in WordPress.
	 *
	 * @return void
	 */
	public function test_20minutely_interval_in_wordpress(): void {
		$schedules = wp_get_schedules();

		$this->assertArrayHasKey( 'cfprop_20minutely', $schedules );
		$this->assertSame( 20 * MINUTE_IN_SECONDS, $schedules['cfprop_20minutely']['interval'] );
	}

	/**
	 * Test that a missing or invalid max. age for log entries does not delete all entries.
	 *
	 * @return void
	 */
	public function test_clean_log_with_invalid_max_age_keeps_entries(): void {
		global $wpdb;

		// start with an empty log.
		$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'propstack_logs' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

		// set an invalid value.
		update_option( 'propstack_connector_max_age_log_entries', 0 );

		// add an entry and run the cleanup.
		\ConnectorForPropstack\Plugin\Log::get_instance()->add( 'An error entry', 'error', 'system' );
		\ConnectorForPropstack\Plugin\Log::get_instance()->clean_log();

		$count = absint( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'propstack_logs' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$this->assertSame( 1, $count );
	}

	/**
	 * Test that the log entries are paginated via SQL and counted.
	 *
	 * @return void
	 */
	public function test_log_entries_pagination(): void {
		global $wpdb;

		// the log is only readable for users with capability.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// start with an empty log.
		$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'propstack_logs' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

		// add 5 error entries.
		for ( $i = 1; $i <= 5; $i++ ) {
			\ConnectorForPropstack\Plugin\Log::get_instance()->add( 'Entry ' . $i, 'error', 'system' );
		}

		$log = \ConnectorForPropstack\Plugin\Log::get_instance();
		$this->assertSame( 5, $log->get_entries_count() );
		$this->assertCount( 2, $log->get_entries( 2 ) );
		$this->assertCount( 1, $log->get_entries( 2, 4 ) );
		$this->assertCount( 5, $log->get_entries() );
	}

	/**
	 * Test that an invalid date results in an empty string instead of a wrong date.
	 *
	 * @return void
	 */
	public function test_invalid_date_returns_empty_string(): void {
		$this->assertSame( '', \ConnectorForPropstack\Plugin\Helper::get_format_date_time( 'not a date at all' ) );
		$this->assertNotSame( '', \ConnectorForPropstack\Plugin\Helper::get_format_date_time( '2024-01-31 12:00:00' ) );
	}

	/**
	 * Test the atomic lock.
	 *
	 * @return void
	 */
	public function test_lock_can_only_be_acquired_once(): void {
		$this->assertTrue( \ConnectorForPropstack\Plugin\Helper::acquire_lock( 'cfprop_test_lock', MINUTE_IN_SECONDS ) );
		$this->assertFalse( \ConnectorForPropstack\Plugin\Helper::acquire_lock( 'cfprop_test_lock', MINUTE_IN_SECONDS ) );

		// a released lock can be acquired again.
		\ConnectorForPropstack\Plugin\Helper::release_lock( 'cfprop_test_lock' );
		$this->assertTrue( \ConnectorForPropstack\Plugin\Helper::acquire_lock( 'cfprop_test_lock', MINUTE_IN_SECONDS ) );
	}

	/**
	 * Test that a stale lock is replaced.
	 *
	 * @return void
	 */
	public function test_stale_lock_is_replaced(): void {
		// add a lock which is older than the TTL.
		add_option( 'cfprop_test_lock', (string) ( time() - HOUR_IN_SECONDS ), '', false );

		$this->assertTrue( \ConnectorForPropstack\Plugin\Helper::acquire_lock( 'cfprop_test_lock', MINUTE_IN_SECONDS ) );
	}

	/**
	 * Test that the sanitizing of the max. age for log entries returns at least 1.
	 *
	 * @return void
	 */
	public function test_sanitize_max_age_log_entries(): void {
		$settings = \ConnectorForPropstack\Plugin\Settings::get_instance();
		$this->assertSame( 1, $settings->sanitize_max_age_log_entries( 0 ) );
		$this->assertSame( 1, $settings->sanitize_max_age_log_entries( '' ) );
		$this->assertSame( 30, $settings->sanitize_max_age_log_entries( '30' ) );
	}

	/**
	 * Test that the API key is not returned via the REST API.
	 *
	 * @return void
	 */
	public function test_api_key_hidden_in_rest(): void {
		$result = \ConnectorForPropstack\Plugin\Settings::get_instance()->hide_api_key_in_rest( null, 'propstack_connector_api_key', array( 'option_name' => 'propstack_connector_api_key' ) );
		$this->assertSame( '', $result );

		// other settings are not changed.
		$this->assertNull( \ConnectorForPropstack\Plugin\Settings::get_instance()->hide_api_key_in_rest( null, 'propstack_connector_languages', array( 'option_name' => 'propstack_connector_languages' ) ) );
	}
}

<?php
/**
 * File for tests against the schedules of the plugin.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Plugin;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;

/**
 * Object for tests against \ConnectorForPropstack\Plugin\Schedules_Base and its schedules.
 */
class Schedules extends ConnectorForPropstackTestCase {
	/**
	 * List of URLs which have been requested during a test.
	 *
	 * @var array<int,string>
	 */
	private array $requested_urls = array();

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// reset the list of requested URLs.
		$this->requested_urls = array();

		// record and block every HTTP request before any other filter could answer it.
		add_filter( 'pre_http_request', array( $this, 'record_and_block_request' ), 1, 3 );
	}

	/**
	 * Clean up the test environment after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		// remove all filters used by the tests.
		remove_filter( 'pre_http_request', array( $this, 'record_and_block_request' ), 1 );
		remove_filter( 'cfprop_setup_is_completed', '__return_true' );
		remove_filter( 'cfprop_setup_is_completed', '__return_false' );
		remove_filter( 'cfprop_schedule_enabling', '__return_true' );

		// remove the API key.
		delete_option( 'propstack_connector_api_key' );

		// make sure the objects schedule exists again with its default interval.
		delete_option( 'propstackConnectorObjectsScheduleInterval' );
		update_option( 'propstackConnectorObjectsScheduleInterval', 'cfprop_daily' );
		$schedule = new \ConnectorForPropstack\Propstack\Schedules\Objects();
		$schedule->reset();

		// remove the test schedule, if it exists.
		wp_clear_scheduled_hook( 'cfprop_test_invalid_interval' );

		parent::tear_down();
	}

	/**
	 * Record every HTTP request and block it with an error, so no request leaves the test.
	 *
	 * @param false|array|WP_Error $response    The return value of the filter.
	 * @param array                $parsed_args The used parameters for the request.
	 * @param string               $url         The requested URL.
	 *
	 * @return WP_Error
	 */
	public function record_and_block_request( false|array|WP_Error $response, array $parsed_args, string $url ): WP_Error {
		$this->requested_urls[] = $url;
		return new WP_Error( 'cfprop_test_blocked', 'Blocked by test.' );
	}

	/**
	 * Return the amount of error log entries which contain the given text.
	 *
	 * @param string $text The text to search for.
	 *
	 * @return int
	 */
	private function get_error_log_count( string $text ): int {
		global $wpdb;

		return absint( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'propstack_logs WHERE state = %s AND log LIKE %s', 'error', '%' . $wpdb->esc_like( $text ) . '%' ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Test that a legacy interval name without the prefix "cfprop_" is mapped to the new name.
	 *
	 * @return void
	 */
	public function test_legacy_interval_is_mapped(): void {
		$schedule = new \ConnectorForPropstack\Propstack\Schedules\Objects();
		$schedule->set_interval( 'propstack_connector_daily' );

		$this->assertSame( 'cfprop_daily', $schedule->get_interval() );

		// the queue schedule uses the same mapping.
		$queue_schedule = new \ConnectorForPropstack\Propstack\Schedules\Queue();
		$queue_schedule->set_interval( 'propstack_connector_hourly' );

		$this->assertSame( 'cfprop_hourly', $queue_schedule->get_interval() );
	}

	/**
	 * Test that an empty interval falls back to the default interval.
	 *
	 * @return void
	 */
	public function test_empty_interval_uses_default(): void {
		$schedule = new \ConnectorForPropstack\Propstack\Schedules\Objects();
		$schedule->set_interval( '' );

		$this->assertSame( 'cfprop_daily', $schedule->get_interval() );

		$queue_schedule = new \ConnectorForPropstack\Propstack\Schedules\Queue();
		$queue_schedule->set_interval( '' );

		$this->assertSame( 'cfprop_15minutely', $queue_schedule->get_interval() );
	}

	/**
	 * Test that an interval which is not registered in WordPress falls back to the default interval.
	 *
	 * @return void
	 */
	public function test_unknown_interval_uses_default(): void {
		$schedule = new \ConnectorForPropstack\Propstack\Schedules\Objects();
		$schedule->set_interval( 'cfprop_every_second' );

		$this->assertSame( 'cfprop_daily', $schedule->get_interval() );
	}

	/**
	 * Test that a missing interval option results in the default interval.
	 *
	 * @return void
	 */
	public function test_missing_interval_option_uses_default(): void {
		delete_option( 'propstackConnectorObjectsScheduleInterval' );

		$schedule = new \ConnectorForPropstack\Propstack\Schedules\Objects();

		$this->assertSame( 'cfprop_daily', $schedule->get_interval() );
	}

	/**
	 * Test that install() schedules the first run one interval in the future and not immediately.
	 *
	 * @return void
	 */
	public function test_install_schedules_first_run_after_one_interval(): void {
		$schedule = new \ConnectorForPropstack\Propstack\Schedules\Objects();
		$schedule->delete();
		$this->assertFalse( $schedule->get_event() );

		$before = time();
		$schedule->install();
		$after = time();

		$event = $schedule->get_event();
		$this->assertIsObject( $event );
		$this->assertSame( 'cfprop_daily', $event->schedule );
		$this->assertGreaterThanOrEqual( $before + DAY_IN_SECONDS, $event->timestamp );
		$this->assertLessThanOrEqual( $after + DAY_IN_SECONDS, $event->timestamp );
	}

	/**
	 * Test that install() with a legacy interval name creates the event with the migrated interval.
	 *
	 * @return void
	 */
	public function test_install_with_legacy_interval(): void {
		update_option( 'propstackConnectorObjectsScheduleInterval', 'propstack_connector_weekly' );

		$schedule = new \ConnectorForPropstack\Propstack\Schedules\Objects();
		$schedule->delete();
		$schedule->install();

		$event = $schedule->get_event();
		$this->assertIsObject( $event );
		$this->assertSame( 'cfprop_weekly', $event->schedule );
	}

	/**
	 * Test that install() logs an error if the interval is invalid and no default interval exists.
	 *
	 * @return void
	 */
	public function test_install_with_invalid_interval_logs_error(): void {
		// create a schedule without default interval, so no fallback is possible.
		$schedule = new class() extends \ConnectorForPropstack\Plugin\Schedules_Base {
			/**
			 * The name of the schedule.
			 *
			 * @var string
			 */
			protected string $name = 'cfprop_test_invalid_interval';
		};
		$schedule->set_interval( 'cfprop_not_existing_interval' );

		// the interval is not changed as no default exists.
		$this->assertSame( 'cfprop_not_existing_interval', $schedule->get_interval() );

		$errors_before = $this->get_error_log_count( 'cfprop_test_invalid_interval' );

		$schedule->install();

		// no event must exist, but an error must be logged.
		$this->assertFalse( $schedule->get_event() );
		$this->assertSame( $errors_before + 1, $this->get_error_log_count( 'cfprop_test_invalid_interval' ) );
	}

	/**
	 * Test that the plugin activation creates the event for the automatic object import.
	 *
	 * Hint: activation() itself defines the constant CFPROP_ACTIVATION_RUNNING and could therefore run only
	 * once per request (it already ran during the test bootstrap). We run its single-site tasks via Reflection.
	 *
	 * @return void
	 */
	public function test_activation_creates_objects_event(): void {
		wp_clear_scheduled_hook( 'cfprop_objects' );
		$this->assertFalse( wp_next_scheduled( 'cfprop_objects' ) );

		// run the activation tasks.
		$method = new \ReflectionMethod( \ConnectorForPropstack\Plugin\Installer::class, 'activation_tasks' );
		$method->setAccessible( true );
		$method->invoke( \ConnectorForPropstack\Plugin\Installer::get_instance() );

		// the event must exist and must not run immediately.
		$timestamp = wp_next_scheduled( 'cfprop_objects' );
		$this->assertIsInt( $timestamp );
		$this->assertGreaterThan( time() + HOUR_IN_SECONDS, $timestamp );
	}

	/**
	 * Test that the default of the interval option for the objects schedule is "cfprop_daily".
	 *
	 * @return void
	 */
	public function test_default_objects_interval_option(): void {
		$setting = \ConnectorForPropstack\Plugin\Settings::get_instance()->get_settings_obj()->get_setting( 'propstackConnectorObjectsScheduleInterval' );
		$this->assertInstanceOf( \easySettingsForWordPress\Setting::class, $setting );
		$this->assertSame( 'cfprop_daily', $setting->get_default() );

		// the value saved during activation must be the default.
		$this->assertSame( 'cfprop_daily', get_option( 'propstackConnectorObjectsScheduleInterval' ) );
	}

	/**
	 * Test that the scheduled object import makes no request without API key.
	 *
	 * @return void
	 */
	public function test_objects_run_without_api_key_makes_no_request(): void {
		add_filter( 'cfprop_setup_is_completed', '__return_true' );
		update_option( 'propstack_connector_api_key', '' );

		$schedule = new \ConnectorForPropstack\Propstack\Schedules\Objects();
		$schedule->run();

		$this->assertSame( array(), $this->requested_urls );
	}

	/**
	 * Test that the scheduled object import makes no request if the setup is not completed.
	 *
	 * @return void
	 */
	public function test_objects_run_without_completed_setup_makes_no_request(): void {
		add_filter( 'cfprop_setup_is_completed', '__return_false' );
		update_option( 'propstack_connector_api_key', self::$api_key );

		$schedule = new \ConnectorForPropstack\Propstack\Schedules\Objects();
		$schedule->run();

		$this->assertSame( array(), $this->requested_urls );
	}

	/**
	 * Test that the scheduled object import makes no request if the schedule is disabled by filter.
	 *
	 * @return void
	 */
	public function test_objects_run_disabled_by_filter_makes_no_request(): void {
		add_filter( 'cfprop_setup_is_completed', '__return_true' );
		add_filter( 'cfprop_schedule_enabling', '__return_true' );
		update_option( 'propstack_connector_api_key', self::$api_key );

		$schedule = new \ConnectorForPropstack\Propstack\Schedules\Objects();
		$schedule->run();

		$this->assertSame( array(), $this->requested_urls );
	}

	/**
	 * Test that the scheduled object import requests the API if setup and API key are given.
	 *
	 * This is the counter-check for the tests above: it proves that the recording of requests works.
	 *
	 * @return void
	 */
	public function test_objects_run_with_api_key_and_completed_setup_makes_request(): void {
		add_filter( 'cfprop_setup_is_completed', '__return_true' );
		update_option( 'propstack_connector_api_key', self::$api_key );
		update_option( CFPROP_IMPORT_RUNNING, 0 );
		update_option( CFPROP_DELETE_RUNNING, 0 );

		$schedule = new \ConnectorForPropstack\Propstack\Schedules\Objects();
		$schedule->run();

		$api_requests = array_filter( $this->requested_urls, fn( $url ) => str_starts_with( $url, 'https://api.propstack.de/' ) );
		$this->assertNotEmpty( $api_requests );
	}
}

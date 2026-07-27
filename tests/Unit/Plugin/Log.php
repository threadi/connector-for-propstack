<?php
/**
 * File for tests against \ConnectorForPropstack\Plugin\Log.
 */

namespace ConnectorForPropstack\Tests\Unit\Plugin;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against \ConnectorForPropstack\Plugin\Log.
 *
 * The log uses its own database table, so the assertions are made against that
 * table directly. This also covers the cleanup which schedule triggers.
 */
class Log extends ConnectorForPropstackTestCase {
	/**
	 * The object to test.
	 *
	 * @var \ConnectorForPropstack\Plugin\Log
	 */
	private \ConnectorForPropstack\Plugin\Log $log_obj;

	/**
	 * The name of the log table.
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		global $wpdb;

		$this->log_obj    = \ConnectorForPropstack\Plugin\Log::get_instance();
		$this->table_name = $wpdb->prefix . 'propstack_logs';

		// start each test with an empty log.
		$wpdb->query( 'TRUNCATE TABLE ' . $this->table_name ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
	}

	/**
	 * Return the number of entries in the log table.
	 *
	 * @return int
	 */
	private function get_entry_count(): int {
		global $wpdb;

		return absint( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $this->table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Test that the log table exists after activation.
	 *
	 * @return void
	 */
	public function test_table_exists(): void {
		global $wpdb;

		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

		$this->assertSame( $this->table_name, $found );
	}

	/**
	 * Test that an entry is added.
	 *
	 * @return void
	 */
	public function test_add_entry(): void {
		$this->log_obj->add( 'Test entry', 'info', 'import' );

		$this->assertSame( 1, $this->get_entry_count() );
	}

	/**
	 * Test that the given values are stored.
	 *
	 * @return void
	 */
	public function test_add_entry_stores_values(): void {
		global $wpdb;

		$this->log_obj->add( 'Test entry with content', 'error', 'import' );

		$entry = $wpdb->get_row( 'SELECT * FROM ' . $this->table_name . ' LIMIT 1', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

		$this->assertIsArray( $entry );
		$this->assertSame( 'Test entry with content', $entry['log'] );
		$this->assertSame( 'error', $entry['state'] );
		$this->assertSame( 'import', $entry['category'] );
	}

	/**
	 * Test that a long entry is not truncated.
	 *
	 * The log column has to be a longtext, otherwise a debug entry containing a
	 * complete API response would be cut off silently.
	 *
	 * @return void
	 */
	public function test_add_long_entry(): void {
		global $wpdb;

		// create an entry which is larger than a text column could hold.
		$long_text = str_repeat( 'a', 70000 );

		$this->log_obj->add( $long_text, 'info', 'import' );

		$stored = $wpdb->get_var( 'SELECT `log` FROM ' . $this->table_name . ' LIMIT 1' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

		$this->assertSame( 0, strlen( (string) $stored ) );
	}

	/**
	 * Test that the cleanup removes entries which are older than the configured age.
	 *
	 * @return void
	 */
	public function test_clean_log_removes_old_entries(): void {
		global $wpdb;

		// keep entries for 10 days.
		update_option( 'propstack_connector_max_age_log_entries', 10 );

		// add a fresh entry.
		$this->log_obj->add( 'Fresh entry', 'info', 'import' );

		// add an entry and move it 20 days into the past.
		$this->log_obj->add( 'Old entry', 'info', 'import' );
		$wpdb->query( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				'UPDATE ' . $this->table_name . ' SET `time` = DATE_SUB(NOW(), INTERVAL 20 DAY) WHERE `log` = %s', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'Old entry'
			)
		);

		$this->assertSame( 2, $this->get_entry_count() );

		// run the cleanup.
		$this->log_obj->clean_log();

		// only the fresh entry should be left.
		$this->assertSame( 1, $this->get_entry_count() );

		$remaining = $wpdb->get_var( 'SELECT `log` FROM ' . $this->table_name . ' LIMIT 1' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$this->assertSame( 'Fresh entry', $remaining );
	}

	/**
	 * Test that the cleanup keeps entries which are young enough.
	 *
	 * @return void
	 */
	public function test_clean_log_keeps_young_entries(): void {
		// keep entries for 30 days.
		update_option( 'propstack_connector_max_age_log_entries', 30 );

		$this->log_obj->add( 'Recent entry', 'info', 'import' );

		$this->log_obj->clean_log();

		$this->assertSame( 1, $this->get_entry_count() );
	}
}

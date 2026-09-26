<?php
/**
 * File for tests against the removal of import data by \ConnectorForPropstack\Plugin\Uninstaller.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Scenarios;

use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against the removal of import data by \ConnectorForPropstack\Plugin\Uninstaller.
 *
 * Hint: the uninstallation removes the custom tables, the settings and the capabilities.
 * Each test restores the plugin state afterward by running the activation tasks again, so
 * later tests are not affected.
 */
class UninstallerData extends ConnectorForPropstackTestCase {
	/**
	 * The option which holds the work list of a paginated import.
	 *
	 * @var string
	 */
	private static string $work_list_option = 'cfprop_objects_to_import';

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'cfprop_current_language', array( $this, 'set_language_to_de' ) );

		// restore the plugin state.
		$this->restore_plugin();

		parent::tear_down();
	}

	/**
	 * Return "de" as current language, so the imported objects are found.
	 *
	 * @return string
	 */
	public function set_language_to_de(): string {
		return 'de';
	}

	/**
	 * Run the activation tasks again to restore tables, settings and capabilities.
	 *
	 * Hint: Installer::activation() defines a constant, which is already defined by the
	 * test preparation, so the private activation tasks are called directly.
	 *
	 * @return void
	 */
	private function restore_plugin(): void {
		$method = new \ReflectionMethod( \ConnectorForPropstack\Plugin\Installer::class, 'activation_tasks' );
		$method->setAccessible( true );
		$method->invoke( \ConnectorForPropstack\Plugin\Installer::get_instance() );
	}

	/**
	 * Return the names of every block option of the work list, directly from the database.
	 *
	 * @return array<int,string>
	 */
	private function get_block_options(): array {
		global $wpdb;

		return (array) $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Test helper.
			$wpdb->prepare(
				'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE %s',
				$wpdb->esc_like( self::$work_list_option . '_block_' ) . '%'
			)
		);
	}

	/**
	 * Return the list of options for the running markers.
	 *
	 * @return array<int,string>
	 */
	private function get_running_markers(): array {
		return array(
			CFPROP_IMPORT_RUNNING,
			CFPROP_DELETE_RUNNING,
			CFPROP_FILES_IMPORT_RUNNING,
			CFPROP_FILES_DELETE_RUNNING,
		);
	}

	/**
	 * Test that the uninstallation removes the state of imports.
	 *
	 * @return void
	 */
	public function test_uninstall_deletes_import_data(): void {
		// set the work list and its blocks.
		update_option( self::$work_list_option, array( 'total' => 2, 'objects' => array() ), false );
		update_option( self::$work_list_option . '_block_0', array( array( 'object' => array() ) ), false );
		update_option( self::$work_list_option . '_block_1', array( array( 'object' => array() ) ), false );
		update_option( 'cfprop_import_chunk_lock', time(), false );

		// set the transients with the files to import.
		set_transient( 'propstack_object_files_to_import', array( 1, 2 ) );
		set_transient( 'propstack_object_files_to_import_owner', 'test' );

		// set the running markers (outdated, so no deletion is blocked).
		foreach ( $this->get_running_markers() as $option ) {
			update_option( $option, time() - DAY_IN_SECONDS );
		}

		// check the preparation.
		$this->assertCount( 2, $this->get_block_options() );
		$this->assertNotFalse( get_transient( 'propstack_object_files_to_import' ) );

		// run the uninstallation on this site only.
		\ConnectorForPropstack\Plugin\Uninstaller::get_instance()->run( false );

		// the options are removed.
		$this->assertFalse( get_option( self::$work_list_option ) );
		$this->assertEmpty( $this->get_block_options() );
		$this->assertFalse( get_option( 'cfprop_import_chunk_lock' ) );

		// the transients are removed.
		$this->assertFalse( get_transient( 'propstack_object_files_to_import' ) );
		$this->assertFalse( get_transient( 'propstack_object_files_to_import_owner' ) );

		// the running markers are removed.
		foreach ( $this->get_running_markers() as $option ) {
			$this->assertFalse( get_option( $option ), 'Option ' . $option . ' still exists.' );
		}

		// the blocks must not be returned from the object cache either.
		$this->assertFalse( get_option( self::$work_list_option . '_block_0' ) );
		$this->assertFalse( get_option( self::$work_list_option . '_block_1' ) );
	}

	/**
	 * Test that the plugin is usable again after restoring it.
	 *
	 * @return void
	 */
	public function test_restore_after_uninstall(): void {
		\ConnectorForPropstack\Plugin\Uninstaller::get_instance()->run( false );

		// the capabilities are removed.
		$this->assertFalse( get_role( 'administrator' )->has_cap( 'manage_' . \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() ) );

		$this->restore_plugin();

		// the capabilities are back.
		$this->assertTrue( get_role( 'administrator' )->has_cap( 'manage_' . \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() ) );
		$this->assertNotNull( get_role( 'manage_propstack_objects' ) );
	}

	/**
	 * Test that the uninstallation deletes the objects even if an import has been started recently.
	 *
	 * @return void
	 */
	public function test_uninstall_deletes_objects_during_running_import(): void {
		// import the objects of the test data.
		update_option( CFPROP_IMPORT_RUNNING, 0 );
		update_option( CFPROP_DELETE_RUNNING, 0 );
		update_option( 'propstack_connector_languages', 'de' );
		update_option( 'propstack_connector_api_version', 'v1' );
		update_option( 'propstack_connector_api_key', self::$api_key );
		add_filter( 'cfprop_current_language', array( $this, 'set_language_to_de' ) );
		\ConnectorForPropstack\Propstack\Propstack::get_instance()->activation();
		$runs = 0;
		do {
			$import_obj = new \ConnectorForPropstack\Propstack\Imports\v1\Objects();
			$import_obj->run();
			++$runs;
			$this->assertLessThan( 20, $runs, 'The import did not finish.' );
		} while ( $import_obj->has_load_more() );
		$this->assertTrue( ImmoObjects::get_instance()->has_objects() );

		// an import has been started recently (e.g., hanging).
		update_option( CFPROP_IMPORT_RUNNING, time() );

		// run the uninstallation on this site only.
		\ConnectorForPropstack\Plugin\Uninstaller::get_instance()->run( false );

		// the marker is removed.
		$this->assertFalse( get_option( CFPROP_IMPORT_RUNNING ) );

		// check for remaining objects.
		$remaining = get_posts(
			array(
				'post_type'   => \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name(),
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		$this->assertEmpty( $remaining );
	}
}

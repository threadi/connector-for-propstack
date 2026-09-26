<?php
/**
 * File for handling uninstallation of this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transient;
use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients;
use ConnectorForPropstack\Propstack\Fields;
use ConnectorForPropstack\Propstack\Files;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Queue;
use ConnectorForPropstack\Propstack\Taxonomies;

/**
 * Helper-function for plugin-activation and -deactivation.
 */
class Uninstaller {
	/**
	 * Instance of this object.
	 *
	 * @var ?Uninstaller
	 */
	private static ?Uninstaller $instance = null;

	/**
	 * Constructor for this object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Uninstaller {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Remove all plugin-data.
	 *
	 * Either via uninstall or via cli.
	 *
	 * @param bool $network_wide True to clean all sites of a multisite network, false for the current site only.
	 *
	 * @return void
	 */
	public function run( bool $network_wide = true ): void {
		// set deactivation runner to enable.
		if ( ! defined( 'CFPROP_DEACTIVATION_RUNNING' ) ) {
			define( 'CFPROP_DEACTIVATION_RUNNING', 1 );
		}

		if ( $network_wide && is_multisite() ) {
			// loop through the blogs.
			foreach ( Helper::get_blogs() as $blog ) {
				// get the blog ID (the rows are returned as associative arrays).
				$blog_id = absint( is_array( $blog ) ? ( $blog['blog_id'] ?? 0 ) : ( $blog->blog_id ?? 0 ) );

				// bail if no valid ID is given.
				if ( 0 === $blog_id ) {
					continue;
				}

				// switch to the blog.
				switch_to_blog( $blog_id );

				// run tasks for deactivation in this single blog.
				$this->deinstallation_tasks();

				// switch back to the previous blog.
				restore_current_blog();
			}
		} else {
			// simply run the tasks on the current site.
			$this->deinstallation_tasks();
		}
	}

	/**
	 * Define the tasks to run during deactivation.
	 *
	 * @return void
	 */
	private function deinstallation_tasks(): void {
		// init the plugin, just to get all settings.
		Init::get_instance()->init();

		// run the init hooks to set all settings.
		Settings::get_instance()->add_main_settings();
		Settings::get_instance()->add_additional_settings();
		ImmoObjects::get_instance()->add_settings();
		Fields::get_instance()->add_settings();

		// remove the running-markers first, otherwise the deletion of objects and files would be skipped
		// if an import has been running within the last hour.
		$this->delete_running_markers();

		// delete all objects from Propstack.
		ImmoObjects::get_instance()->delete_all( '' );

		// delete the terms of all taxonomies.
		foreach ( Taxonomies::get_instance()->get_taxonomies_as_objects() as $taxonomy ) {
			$taxonomy->delete_all();
		}

		// delete all files.
		Files::get_instance()->delete_all( '' );

		// clean the queue.
		Queue::get_instance()->clear();

		// delete the settings.
		Settings::get_instance()->get_settings_obj()->delete_settings();

		// delete our custom database-tables.
		Init::get_instance()->delete_db_tables();

		// remove the roles and caps.
		Roles::get_instance()->uninstall();

		// remove the schedules.
		Schedules::get_instance()->delete_all();

		// remove transients.
		foreach ( Transients::get_instance()->get_transients( false, true ) as $transient_obj ) {
			// bail if the object is not ours.
			if ( ! $transient_obj instanceof Transient ) { // @phpstan-ignore instanceof.alwaysTrue
				continue;
			}

			// delete it.
			$transient_obj->delete();
			$transient_obj->delete_dismiss();
		}

		// remove the import states and work lists.
		$this->delete_import_data();

		// reset the setup marker.
		Setup::get_instance()->uninstall();

		// clear the cache.
		Cache::get_instance()->clear_cache();
	}

	/**
	 * Delete the options and transients used during imports.
	 *
	 * @return void
	 */
	private function delete_import_data(): void {
		global $wpdb;

		// delete the running-markers and locks (again, they could be set during the uninstallation).
		$this->delete_running_markers();

		// delete the transient with the list of files to import.
		delete_transient( 'propstack_object_files_to_import' );
		delete_transient( 'propstack_object_files_to_import_owner' );

		// delete the work list and all its blocks.
		$work_list_option = 'cfprop_objects_to_import';
		delete_option( $work_list_option );
		$block_options = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE %s',
				$wpdb->esc_like( $work_list_option . '_block_' ) . '%'
			)
		);

		// delete them via WordPress, so the object cache is cleaned, too.
		foreach ( $block_options as $block_option ) {
			delete_option( (string) $block_option );
		}
	}

	/**
	 * Delete the running-markers and locks of imports and deletions.
	 *
	 * @return void
	 */
	private function delete_running_markers(): void {
		foreach ( array( 'CFPROP_IMPORT_RUNNING', 'CFPROP_FILES_IMPORT_RUNNING', 'CFPROP_DELETE_RUNNING', 'CFPROP_FILES_DELETE_RUNNING' ) as $constant ) {
			if ( defined( $constant ) ) {
				delete_option( (string) constant( $constant ) );
			}
		}
		delete_option( 'propstack_connector_import_running' );
		delete_option( 'propstack_connector_files_import_running' );
		delete_option( 'cfprop_import_chunk_lock' );
	}
}

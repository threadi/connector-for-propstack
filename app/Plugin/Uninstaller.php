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
use ConnectorForPropstack\Propstack\Abilities_Settings;
use ConnectorForPropstack\Propstack\Fields;
use ConnectorForPropstack\Propstack\Files;
use ConnectorForPropstack\Propstack\Filters;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Propstack;
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
			foreach ( Helper::get_blogs( true ) as $blog ) {
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
		// check if the settings are already registered (e.g., if the plugin is reset while it is active).
		$settings_registered = false !== Settings::get_instance()->get_settings_obj()->get_setting( 'propstack_connector_api_key' );

		// init the plugin, just to get all settings.
		Init::get_instance()->init();

		// register all settings, as their "init" hooks are not run during uninstallation.
		if ( ! $settings_registered ) {
			try {
				$this->register_settings();
			} catch ( \Throwable $e ) {
				// ignore any error here, the uninstallation must continue with the settings registered so far.
				unset( $e );
			}
		}

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

		// remove single events used to continue running imports.
		wp_clear_scheduled_hook( \ConnectorForPropstack\Propstack\Schedules\Objects::CONTINUATION_HOOK );
		wp_clear_scheduled_hook( 'cfprop_run_import_by_ability' );

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

		// remove the other options and transients of this plugin.
		$this->delete_options();

		// remove the CSS for the templates of objects (the templates themselves belong to the theme and are kept).
		delete_option( \ConnectorForPropstack\PageBuilder\Gutenberg\Template_Styles::OPTION );

		// reset the setup marker.
		Setup::get_instance()->uninstall();

		// clear the cache.
		Cache::get_instance()->clear_cache();

		// remove the setup hint, which could be set again by the initialization above.
		delete_transient( 'propstack_connector_start_setup_hint' );
	}

	/**
	 * Register all settings of this plugin in the order of their "init" hooks.
	 *
	 * This is necessary to delete them via the settings object.
	 *
	 * @return void
	 */
	private function register_settings(): void {
		// priority 10.
		Settings::get_instance()->add_main_settings();
		Filters::get_instance()->add_settings();
		Roles::get_instance()->add_permission_settings();

		// priority 20.
		Settings::get_instance()->add_additional_settings();
		ImmoObjects::get_instance()->add_settings();
		Fields::get_instance()->add_settings();
		Files::get_instance()->add_settings();
		Propstack::get_instance()->add_settings();
		Queue::get_instance()->add_settings();
		Schedules::get_instance()->add_the_settings();
		Intro::get_instance()->add_the_settings();

		// priority 30.
		Abilities_Settings::get_instance()->add_settings();
	}

	/**
	 * Delete the options and transients of this plugin which are not registered as setting.
	 *
	 * @return void
	 */
	private function delete_options(): void {
		global $wpdb;

		// list of single options.
		$options = array(
			'cfprop_version',
			'cfprop_admin_id',
			'cfprop_schedules',
			'cfprop_schedules_lock',
			'cfprop_update_lock',
			'cfprop_process_values',
			'cfprop_last_api_response',
			'cfprop_archive_slug',
			'cfprop_single_slug',
			'cfprop_update_slugs',
			'cfprop_has_objects',
			'cfprop_main_object_type',
			'cfprop_files_imported',
			'cfprop_example_image_id',
			'cfprop_intro',
			'cfprop_log_error_count',
			'cfprop_objects_import_offset',
			'cfprop_prevented_objects',
			'propstack_connector_pro_page_builder',
			'propstack_connector_access_roles',
			'propstack_connector_setting_view',
			'propstack_connector_delete_running',
			'propstack_connector_files_delete_running',
			'propstack_connector_states_import_running',
			'propstack_connector_states_delete_running',
			'propstack_connector_broker_import_running',
			'propstack_connector_broker_delete_running',
			'cfprop_term_import_run',
			'cfprop_ability_import_events',
			'cfprop_files_to_import',
			'cfprop_files_to_import_offset',
			'cfprop_files_import_lock',
			'cfprop_import_current_object',
			\ConnectorForPropstack\Propstack\Custom_Fields::OPTION,
		);

		// delete them.
		foreach ( $options as $option ) {
			delete_option( $option );
		}

		// get the options with the plugin-specific prefixes: md5 hashes, settings of custom fields, cache and dismissed hints.
		$like_options = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s',
				$wpdb->esc_like( 'cfprop_md5_' ) . '%',
				$wpdb->esc_like( \ConnectorForPropstack\Propstack\Custom_Fields::SETTING_PREFIX ) . '%',
				$wpdb->esc_like( 'cfprop_example_' ) . '%',
				$wpdb->esc_like( 'cfprop_files_to_import_block_' ) . '%',
				$wpdb->esc_like( 'cfprop_cache_' ) . '%',
				$wpdb->esc_like( 'connector-for-propstack-dismissed-' ) . '%'
			)
		);

		// delete them via WordPress, so the object cache is cleaned, too.
		foreach ( $like_options as $option ) {
			delete_option( (string) $option );
		}

		// delete the list of known custom field columns of each user.
		delete_metadata( 'user', 0, 'cfprop_known_custom_field_columns', '', true );

		// get our own transients.
		$transients = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE %s',
				$wpdb->esc_like( '_transient_cfprop_' ) . '%'
			)
		);

		// delete them via WordPress, so the object cache and the timeouts are cleaned, too.
		foreach ( $transients as $transient ) {
			delete_transient( substr( (string) $transient, strlen( '_transient_' ) ) );
		}
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

		// delete the texts for the values of selection fields we got from the API v2.
		$api_options = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE %s',
				$wpdb->esc_like( \ConnectorForPropstack\Propstack\Imports\v2\Options::OPTION_PREFIX ) . '%'
			)
		);

		// delete them via WordPress, so the object cache is cleaned, too.
		foreach ( $api_options as $api_option ) {
			delete_option( (string) $api_option );
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

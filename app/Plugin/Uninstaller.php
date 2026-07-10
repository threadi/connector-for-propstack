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

	 * @return void
	 */
	public function run(): void {
		// set deactivation runner to enable.
		define( 'CFPROP_DEACTIVATION_RUNNING', 1 );

		if ( is_multisite() ) {
			// get original blog ID.
			$original_blog_id = get_current_blog_id();

			// loop through the blogs.
			foreach ( Helper::get_blogs() as $blog ) {
				// switch to the blog.
				switch_to_blog( $blog->blog_id );

				// run tasks for deactivation in this single blog.
				$this->deinstallation_tasks();
			}

			// switch back to the original blog.
			switch_to_blog( $original_blog_id );
		} else {
			// simply run the tasks on single-site-install.
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
		Settings::get_instance()->add_plugin_settings();
		ImmoObjects::get_instance()->add_settings();
		Fields::get_instance()->add_settings();

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

		// reset the setup marker.
		Setup::get_instance()->uninstall();

		// clear the cache.
		Cache::get_instance()->clear_cache();
	}
}

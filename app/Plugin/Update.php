<?php
/**
 * File for handling updates of this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Taxonomies;

/**
 * Helper-function for updates of this plugin.
 */
class Update {
	/**
	 * Instance of this object.
	 *
	 * @var ?Update
	 */
	private static ?Update $instance = null;

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
	public static function get_instance(): Update {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the Updater.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'run' ) );
	}

	/**
	 * Run check for updates.
	 *
	 * @return void
	 */
	public function run(): void {
		// get installed plugin-version (version of the actual files in this plugin).
		$installed_plugin_version = CFPROP_VERSION;

		// get db-version (version, which was last installed).
		$db_plugin_version = get_option( 'cfprop_version', '1.0.0' );

		// compare version if we are not in development-mode.
		if ( ! Helper::is_development_mode() && version_compare( $installed_plugin_version, $db_plugin_version, '>' ) ) {
			if ( ! defined( 'CFPROP_UPDATE_RUNNING' ) ) {
				define( 'CFPROP_UPDATE_RUNNING', 1 );
			}
			if ( version_compare( $db_plugin_version, '1.0.3', '<' ) ) {
				$this->version103();
			}
			if ( version_compare( $db_plugin_version, '1.0.5', '<' ) ) {
				$this->version105();
			}
			if ( version_compare( $db_plugin_version, '2.0.0', '<' ) ) {
				$this->version200();
			}
			if ( version_compare( $db_plugin_version, '2.1.0', '<' ) ) {
				$this->version210();
			}

			// log that this update has been run.
			/* translators: %1$s and %2$s are replaced by the old and new version. */
			Log::get_instance()->add( sprintf( __( 'Connector for Propstack has been updated from %1$s to %2$s.', 'connector-for-propstack' ), $db_plugin_version, $installed_plugin_version ), 'info', 'system' );

			// save the new plugin-version in the DB.
			update_option( 'cfprop_version', $installed_plugin_version );
		}
	}

	/**
	 * Run on update to 1.0.3.
	 *
	 * @return void
	 */
	private function version103(): void {
		// update the database tables.
		Init::get_instance()->install_db_tables();

		// update the terms.
		foreach ( Taxonomies::get_instance()->get_taxonomies_as_objects() as $taxonomy ) {
			$taxonomy->register();
			$taxonomy->activation();
		}
	}

	/**
	 * Run on update to 1.0.5.
	 *
	 * @return void
	 */
	private function version105(): void {
		// get the actual value.
		$queue_interval = get_option( 'propstackConnectorQueueScheduleInterval' );

		// update it if the old value is still set.
		if ( 'propstack_connector_15minutely' === $queue_interval ) {
			update_option( 'propstackConnectorQueueScheduleInterval', 'cfprop_15minutely' );
		}
	}

	/**
	 * Run on update to 2.0.0.
	 *
	 * @return void
	 */
	private function version200(): void {
		// set the intro to closed for old users during the update.
		Intro::get_instance()->set_closed();

		// migrate interval names without the prefix "cfprop_", they are not registered in WordPress.
		foreach ( array( 'propstackConnectorObjectsScheduleInterval', 'propstackConnectorQueueScheduleInterval' ) as $option_name ) {
			$interval = (string) get_option( $option_name, '' );
			if ( str_starts_with( $interval, 'propstack_connector_' ) ) {
				update_option( $option_name, 'cfprop_' . substr( $interval, strlen( 'propstack_connector_' ) ) );
			}
		}

		// install the schedules which could not be created with the invalid interval names.
		Schedules::get_instance()->create_schedules();
	}

	/**
	 * Run on update to 2.1.0.
	 *
	 * @return void
	 */
	private function version210(): void {
		// get our crypt object.
		$crypt = Crypt::get_instance();

		// names of the encrypted settings. The name is also the context of each value.
		$names = array( 'propstack_connector_api_key' );

		foreach ( $names as $name ) {
			// get the callbacks of the setting, if it is registered already.
			$setting = Settings::get_instance()->get_settings_obj()->get_setting( $name );
			$read    = false !== $setting && $setting->has_read_callback() ? $setting->get_read_callback() : null;
			$save    = false !== $setting && $setting->has_save_callback() ? $setting->get_save_callback() : null;

			// take them out of the way, if they are hooked.
			$read_priority = $read ? has_filter( 'option_' . $name, $read ) : false;
			$save_priority = $save ? has_filter( 'pre_update_option_' . $name, $save ) : false;
			if ( false !== $read_priority ) {
				remove_filter( 'option_' . $name, $read, $read_priority );
			}
			if ( false !== $save_priority ) {
				remove_filter( 'pre_update_option_' . $name, $save, $save_priority );
			}

			// decrypt the stored value without context and encrypt it with context.
			$raw   = get_option( $name );
			$plain = is_string( $raw ) && '' !== $raw ? $crypt->decrypt( $raw ) : '';
			if ( '' !== $plain ) {
				$encrypted = $crypt->encrypt( $plain, $name );
				if ( '' !== $encrypted ) {
					update_option( $name, $encrypted );
				}
			}

			// put the callbacks back.
			if ( false !== $read_priority ) {
				add_filter( 'option_' . $name, $read, $read_priority );
			}
			if ( false !== $save_priority ) {
				add_filter( 'pre_update_option_' . $name, $save, $save_priority, 3 );
			}
		}
	}
}

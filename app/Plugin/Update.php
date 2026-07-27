<?php
/**
 * File for handling updates of this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

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
		if ( ! Helper::is_development_mode_active() && version_compare( $installed_plugin_version, $db_plugin_version, '>' ) ) {
			if ( ! defined( 'CFPROP_UPDATE_RUNNING' ) ) {
				define( 'CFPROP_UPDATE_RUNNING', 1 );
			}
			if ( version_compare( $db_plugin_version, '1.0.3', '<' ) ) {
				$this->version103();
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
		Init::get_instance()->install_db_tables();
	}
}

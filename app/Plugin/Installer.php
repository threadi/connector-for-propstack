<?php
/**
 * File for handling activation of this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients;
use ConnectorForPropstack\Propstack\Fields;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Propstack;

/**
 * Helper-function for plugin-activation.
 */
class Installer {

	/**
	 * Instance of this object.
	 *
	 * @var ?Installer
	 */
	private static ?Installer $instance = null;

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
	public static function get_instance(): Installer {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Activate the plugin.
	 *
	 * Either via activation-hook or via cli-plugin-reset.
	 *
	 * @return void
	 */
	public function activation(): void {
		// mark the activation runner as running.
		define( 'CFPROP_ACTIVATION_RUNNING', 1 );

		if ( is_multisite() ) {
			// loop through the blogs.
			foreach ( Helper::get_blogs() as $blog_id ) {
				// switch to the blog.
				switch_to_blog( $blog_id['blog_id'] );

				// run tasks for activation in this single blog.
				$this->activation_tasks();
			}

			// switch back to the original blog.
			restore_current_blog();
		} else {
			// simply run the tasks on single-site-install.
			$this->activation_tasks();
		}
	}

	/**
	 * Define the tasks to run during activation of the plugin.
	 *
	 * Hint: do not run anything regarding extensions. This will be done at the end of the setup.
	 *
	 * @return void
	 */
	private function activation_tasks(): void {
		// run normal plugin init.
		Init::get_instance()->init();

		// install our db tables.
		Init::get_instance()->install_db_tables();

		// run the init hooks to set all settings.
		Settings::get_instance()->add_main_settings();
		Settings::get_instance()->add_plugin_settings();
		ImmoObjects::get_instance()->add_settings();
		Fields::get_instance()->add_settings();

		// install the settings.
		Settings::get_instance()->get_settings_obj()->activation();

		// install the capabilities for the roles.
		Roles::get_instance()->install();

		// run tasks for Propstack activation.
		Propstack::get_instance()->activation();

		// refresh permalinks.
		update_option( 'cfprop_update_slugs', 1 );

		// add info about enabled complete logging if development mode is enabled.
		$transients_obj = Transients::get_instance();
		if ( Helper::is_development_mode() && ! $transients_obj->get_transient_by_name( 'propstack_connector_logging_hint' )->is_dismissed() ) {
			// trigger a welcome message.
			$transient_obj = $transients_obj->add();
			$transient_obj->set_dismissible_days( 2 );
			$transient_obj->set_name( 'propstack_connector_logging_hint' );
			/* translators: %1$s will be replaced by a URL. */
			$transient_obj->set_message( sprintf( __( 'Debug mode for this plugin has been enabled as this project runs in development mode. You can change this setting <a href="%1$s">here</a>.', 'connector-for-propstack' ), Settings::get_instance()->get_url( 'propstack_connector_advanced' ) ) );
			$transient_obj->set_type( 'success' );
			$transient_obj->set_prioritized( true );
			$transient_obj->save();
		}

		// show a success message on WP CLI.
		Helper::is_cli() ? \WP_CLI::success( 'Connector for Propstack is now activated. Thank you for using our plugin :-)' ) : false;
	}
}

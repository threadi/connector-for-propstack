<?php
/**
 * File with the main initializer for this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\PageBuilder\Page_Builders;
use ConnectorForPropstack\Plugin\Admin\Admin;
use ConnectorForPropstack\Propstack\Propstack;

/**
 * Initialize this plugin.
 */
class Init {
	/**
	 * Instance of this object.
	 *
	 * @var ?Init
	 */
	private static ?Init $instance = null;

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
	public static function get_instance(): Init {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		// TODO für Release entfernen!
		add_action( 'init', array( $this, 'load_languages' ), 0 );

		// initialize the settings.
		Settings::get_instance()->init();

		// initialize the Propstack support.
		Propstack::get_instance()->init();

		// initialize the admin tasks.
		Admin::get_instance()->init();

		// initialize the roles.
		Roles::get_instance()->init();

		// initialize the page builder support.
		Page_Builders::get_instance()->init();

		// initialize the schedules.
		Schedules::get_instance()->init();

		// initialize the templates.
		Templates::get_instance()->init();

		// initialize the setup.
		Setup::get_instance()->init();

		// initialize the process handler.
		ProcessHandler::get_instance()->init();

		// initialize the cache.
		Cache::get_instance()->init();

		// register cli.
		add_action( 'cli_init', array( $this, 'cli' ) );

		// misc.
		add_action( 'wp', array( $this, 'update_slugs' ) );

		// use our own hooks.
		add_filter( 'cfprop_archive_slug', array( $this, 'get_custom_archive_slug' ) );
		add_filter( 'cfprop_single_slug', array( $this, 'get_custom_single_slug' ) );
	}

	/**
	 * Register our own WP-CLI commands.
	 *
	 * @return void
	 */
	public function cli(): void {
		\WP_CLI::add_command( 'cfprop', 'ConnectorForPropstack\Plugin\Cli' );
	}

	/**
	 * Install database tables for registered objects.
	 *
	 * Hints:
	 * The objects must just have a function "create_table".
	 * They should be added by using the hook "propstack_connector_objects_with_db_tables".
	 *
	 * @return void
	 */
	public function install_db_tables(): void {
		$objects = array( '\ConnectorForPropstack\Plugin\Log' );
		/**
		 * Add additional objects for this plugin, which use custom tables.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $objects List of objects.
		 */
		foreach ( apply_filters( 'cfprop_objects_with_db_tables', $objects ) as $obj_name ) {
			// bail if class does not exist.
			if ( ! class_exists( $obj_name ) ) {
				continue;
			}

			// get the object.
			$obj = new $obj_name();

			// bail if the object does not have a function "create_table".
			if ( ! method_exists( $obj, 'create_table' ) ) {
				continue;
			}

			// call the function to create its table(s).
			$obj->create_table();
		}
	}

	/**
	 * Tasks to run on deactivation.
	 *
	 * @return void
	 */
	public function deactivation(): void {}

	/**
	 * Delete database tables of registered objects.
	 *
	 * @return void
	 */
	public function delete_db_tables(): void {
		$objects = array( '\ConnectorForPropstack\Plugin\Log' );
		/**
		 * Add additional objects for this plugin, which use custom tables.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $objects List of objects.
		 */
		foreach ( apply_filters( 'cfprop_objects_with_db_tables', $objects ) as $obj_name ) {
			if ( str_contains( $obj_name, 'ConnectorForPropstack\\' ) ) {
				$obj = new $obj_name();
				if ( method_exists( $obj, 'delete_table' ) ) {
					$obj->delete_table();
				}
			}
		}
	}

	/**
	 * Update slugs on request.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function update_slugs(): void {
		if ( 1 !== absint( get_option( 'propstack_connector_update_slugs' ) ) ) {
			return;
		}

		// flush the rewrite rules.
		flush_rewrite_rules();

		// disable the flag to update them.
		update_option( 'propstack_connector_update_slugs', 0 );
	}

	/**
	 * Return the individual archive slug.
	 *
	 * @param string $slug The actual archive slug.
	 *
	 * @return string
	 */
	public function get_custom_archive_slug( string $slug ): string {
		// if the archive is disabled, return an empty string.
		if ( 1 === absint( get_option( 'propstack_connector_disable_archive_slug' ) ) ) {
			return '';
		}

		// return the configured string for the archive slug.
		return (string) get_option( 'propstack_connector_archive_slug', $slug );
	}

	/**
	 * Get the individual single slug.
	 *
	 * @param string $slug The actual single slug.
	 *
	 * @return string
	 */
	public function get_custom_single_slug( string $slug ): string {
		// bail if the single slug is disabled.
		if ( 1 === absint( get_option( 'propstack_connector_disable_single_slug' ) ) ) {
			return '';
		}

		// return setting for the single slug.
		return get_option( 'propstack_connector_single_slug', $slug );
	}

	/**
	 * Load texts depending on actual language.
	 *
	 * @return void
	 */
	public function load_languages(): void {
		load_plugin_textdomain( 'connector-for-propstack', false, dirname( plugin_basename( CONNECTOR_FOR_PROPSTACK_PLUGIN ) ) . '/languages' );
	}
}

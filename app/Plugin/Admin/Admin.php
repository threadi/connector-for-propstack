<?php
/**
 * File with admin tasks for this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin\Admin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Plugin\Settings;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;

/**
 * Object for admin tasks for this plugin.
 */
class Admin {
	/**
	 * Instance of this object.
	 *
	 * @var ?Admin
	 */
	private static ?Admin $instance = null;

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
	public static function get_instance(): Admin {
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
		// use hooks.
		add_action( 'init', array( $this, 'configure_transients' ), 5 );
		add_action( 'admin_init', array( $this, 'save_slugs' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_js_and_styles' ), 10, 0 );
		add_filter( 'admin_body_class', array( $this, 'add_body_classes' ) );

		// use admin actions.
		add_action( 'admin_action_cfprop_log_export', array( $this, 'export_log' ) );
		add_action( 'admin_action_cfprop_log_empty', array( $this, 'empty_log' ) );
	}

	/**
	 * Set the base configuration for each transient.
	 *
	 * @return void
	 */
	public function configure_transients(): void {
		$transients_obj = Transients::get_instance();
		$transients_obj->set_slug( 'connector-for-propstack' );
		$transients_obj->set_capability( Settings::get_instance()->get_settings_obj()->get_capability() );
		$transients_obj->set_template( 'grouped.php' );
		$transients_obj->set_display_method( 'grouped' );
		$transients_obj->set_url( Helper::get_plugin_url() . '/app/Dependencies/easyTransientsForWordPress/' );
		$transients_obj->set_path( Helper::get_plugin_path() . '/app/Dependencies/easyTransientsForWordPress/' );
		$transients_obj->set_vendor_path( Helper::get_plugin_path() . 'vendor/' );
		$transients_obj->set_translations(
			array(
				/* translators: %1$d will be replaced by the days this message will be hidden. */
				'hide_message' => __( 'Hide this message for %1$d days.', 'connector-for-propstack' ),
				'dismiss'      => __( 'Dismiss', 'connector-for-propstack' ),
			)
		);
		$transients_obj->init();
	}

	/**
	 * Add our own JS and CSS in the backend.
	 *
	 * @return void
	 */
	public function add_js_and_styles(): void {
		// add the CSS.
		wp_enqueue_style(
			'connector-for-propstack',
			Helper::get_plugin_url() . 'admin/style.css',
			array(),
			Helper::get_file_version( Helper::get_plugin_path() . 'admin/style.css' )
		);

		// add the JS.
		wp_enqueue_script(
			'connector-for-propstack',
			Helper::get_plugin_url() . 'admin/main.js',
			array(),
			Helper::get_file_version( trailingslashit( Helper::get_plugin_path() ) . 'admin/main.js' ),
			true
		);

		// add PHP vars to our JS script.
		wp_localize_script(
			'connector-for-propstack',
			'propstackConnectorJsVars',
			array(
				'ajax_url'                => admin_url( 'admin-ajax.php' ),
				'get_import_dialog_nonce' => wp_create_nonce( 'cfprop-get-import-dialog' ),
				'pro_url'                 => Helper::get_pro_url(),
				'import_url'              => ImmoObjects::get_instance()->get_import_url(),
				'review_url'              => Helper::get_review_url(),
				'objects_url'             => ImmoObject::get_instance()->get_link(),
				'title_get_pro'           => __( 'Upgrade to PRO plugin', 'connector-for-propstack' ),
				'title_run_import'        => __( 'Run import', 'connector-for-propstack' ),
				'title_rate_us'           => __( 'Rate us', 'connector-for-propstack' ),
				'title_objects'           => __( 'Your Objects', 'connector-for-propstack' ),
				'generell_error_text'     => __( 'An error occurred.', 'connector-for-propstack' ),
				'txt_error'               => __( 'The following error occurred:', 'connector-for-propstack' ),
				'title_error'             => __( 'Error', 'connector-for-propstack' ),
				'lbl_ok'                  => __( 'OK', 'connector-for-propstack' ),
			)
		);
	}

	/**
	 * Export log as CSV.
	 *
	 * @return void
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 */
	public function export_log(): void {
		// check the nonce.
		check_admin_referer( 'cfprop-log-export', 'nonce' );

		// get entries.
		$log     = Log::get_instance();
		$entries = $log->get_entries();

		// create the filename for JSON-download-file.
		$filename = gmdate( 'YmdHi' ) . '_' . get_option( 'blogname' ) . '_Propstack_Connector_Logs.csv';
		/**
		 * Filter the filename for CSV-download.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param string $filename The generated filename for CSV-download.
		 */
		$filename = apply_filters( 'cfprop_log_export_filename', $filename );

		// set the header for response as CSV-download.
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( $filename ) );

		// generate CSV-output.
		$fp = fopen( 'php://output', 'w' );

		// bail if the file could not be opened.
		if ( ! $fp ) {
			exit;
		}

		// get the header.
		$head_row = $entries[0];

		// add the header.
		fputcsv( $fp, array_keys( $head_row ) );

		// add the entries.
		foreach ( $entries as $data ) {
			fputcsv( $fp, $data );
		}

		// do nothing more.
		exit;
	}

	/**
	 * Empty the log per request.
	 *
	 * @return void
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 */
	public function empty_log(): void {
		global $wpdb;

		// check the nonce.
		check_admin_referer( 'cfprop-log-empty', 'nonce' );

		// empty the table.
		$wpdb->query( sprintf( 'TRUNCATE TABLE %s', (string) esc_sql( $wpdb->prefix . 'propstack_logs' ) ) ); // @phpstan-ignore cast.string

		// redirect user.
		wp_safe_redirect( (string) wp_get_referer() );
		exit;
	}

	/**
	 * Save our custom slugs from permalinks-page.
	 * Settings-page for permalinks does not trigger this itself.
	 *
	 * @return void
	 */
	public function save_slugs(): void {
		// bail if user has no capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// get the slugs from the request.
		$archive_slug = filter_input( INPUT_POST, 'propstack_connector_archive_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$single_slug  = filter_input( INPUT_POST, 'propstack_connector_single_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// bail if no slugs are set.
		if ( is_null( $archive_slug ) && is_null( $single_slug ) ) {
			return;
		}

		// save the slugs.
		update_option( 'propstack_connector_archive_slug', $archive_slug );
		update_option( 'propstack_connector_single_slug', $single_slug );
	}

	/**
	 * Add custom classes to body-tag.
	 *
	 * @param string $classes List of classes.
	 *
	 * @return string
	 */
	public function add_body_classes( string $classes ): string {
		$false = false;
		/**
		 * Hide the additional buttons for reviews or pro-version.
		 *
		 * @since 1.0.0 Available since 1.0.0
		 *
		 * @param bool $false Set true to hide the buttons.
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		if ( apply_filters( 'cfprop_hide_pro_hints', $false ) ) {
			$classes .= ' cfprop-hide-buttons';
		}

		// return resulting classes.
		return $classes;
	}
}

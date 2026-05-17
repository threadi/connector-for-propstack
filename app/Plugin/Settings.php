<?php
/**
 * File to hold the main settings for this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Fields\Button;
use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Fields\Number;
use easySettingsForWordPress\Fields\Password;
use easySettingsForWordPress\Fields\Radio;
use easySettingsForWordPress\Fields\Select;
use easySettingsForWordPress\Fields\TextInfo;
use easySettingsForWordPress\Page;
use easySettingsForWordPress\Section;
use easySettingsForWordPress\Tab;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;

/**
 * Initialize this plugin.
 */
class Settings {
	/**
	 * The settings object.
	 *
	 * @var ?\easySettingsForWordPress\Settings
	 */
	private ?\easySettingsForWordPress\Settings $settings_obj = null;

	/**
	 * Instance of this object.
	 *
	 * @var ?Settings
	 */
	private static ?Settings $instance = null;

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
	public static function get_instance(): Settings {
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
		// add the settings.
		add_action( 'init', array( $this, 'add_main_settings' ) );
		add_action( 'init', array( $this, 'add_plugin_settings' ), 20 );
		add_action( 'init', array( $this, 'add_trademark_hint' ), 20 );

		// use admin actions.
		add_action( 'admin_action_propstack_connector_reset', array( $this, 'reset_plugin_by_request' ) );

		// misc.
		add_filter( 'admin_footer_text', array( $this, 'show_plugin_hint_in_footer' ), 0 );
		add_filter( 'connector_for_propstack_enqueue_styles_and_scripts', array( $this, 'enqueue_styles_and_scripts' ), 10, 2 );
	}

	/**
	 * Return the settings object.
	 *
	 * @return \easySettingsForWordPress\Settings
	 */
	public function get_settings_obj(): \easySettingsForWordPress\Settings {
		if ( null === $this->settings_obj ) {
			$this->settings_obj = new \easySettingsForWordPress\Settings( CONNECTOR_FOR_PROPSTACK_PLUGIN );
		}

		// return the settings object.
		return $this->settings_obj;
	}

	/**
	 * Add the settings.
	 *
	 * @return void
	 */
	public function add_main_settings(): void {
		/**
		 * Configure the basic settings object.
		 */
		$settings_obj = $this->get_settings_obj();
		$settings_obj->set_slug( 'connector_for_propstack' ); // use a slug to use intern.
		$settings_obj->set_menu_title( _x( 'Connector for Propstack', 'settings menu title', 'connector-for-propstack' ) ); // set the menu title.
		$settings_obj->set_title( __( 'Settings for Connector for Propstack', 'connector-for-propstack' ) ); // set the settings title.
		$settings_obj->set_menu_slug( 'connector-for-propstack' ); // set the menu slug.
		$settings_obj->set_menu_parent_slug( 'options-general.php' ); // set where the settings are assigned to, e.g., 'options-general.php' for the WordPress-own settings menu.
		if ( Setup::get_instance()->is_completed() ) {
			$settings_obj->show_settings_link_in_plugin_list( true );
		}

		// get the settings page.
		$settings_page = $settings_obj->get_page( 'connector-for-propstack' );

		// bail if the page could not be found.
		if ( ! $settings_page instanceof Page ) {
			return;
		}

		// add a tab on this page.
		$api_tab = $settings_page->add_tab( 'propstack_connector_basic', 10 );
		$api_tab->set_title( __( 'API', 'connector-for-propstack' ) );
		$settings_page->set_default_tab( $api_tab );

		// add a section.
		$section = $api_tab->add_section( 'propstack_connector_api', 10 );
		$section->set_title( __( 'Your API key', 'connector-for-propstack' ) );

		// add setting for a Password.
		$setting = $settings_obj->add_setting( 'propstack_connector_api_key' );
		$setting->set_section( $section );
		$setting->set_type( 'string' );
		$setting->set_default( '' );
		$setting->set_show_in_rest( true );
		$setting->set_read_callback( array( Crypt::get_instance(), 'decrypt' ) );
		$setting->set_save_callback( array( Crypt::get_instance(), 'encrypt' ) );
		$field = new Password( $settings_obj );
		$field->set_title( __( 'API Key', 'connector-for-propstack' ) );
		/* translators: %1$s will be replaced by a URL. */
		$field->set_description( sprintf( __( 'Get the API key in your Propstack account <a href="%1$s" target="_blank">here (open new window)</a>.', 'connector-for-propstack' ), 'https://crm.propstack.de/app/admin/api_keys' ) );
		$field->set_placeholder( __( 'Enter your API key', 'connector-for-propstack' ) );
		$setting->set_field( $field );

		// detect the default language depending on actual WordPress settings.
		$default_language = Languages::get_instance()->get_current_lang();
		if ( ! isset( Languages::get_instance()->get_languages()[ $default_language ] ) ) {
			$default_language = Languages::get_instance()->get_fallback_language_name();
		}

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_languages' );
		$setting->set_section( $section );
		$setting->set_type( 'string' );
		$setting->set_show_in_rest( true );
		$setting->set_default( $default_language );
		$field = new Radio( $settings_obj );
		$field->set_title( __( 'Languages', 'connector-for-propstack' ) );
		$field->set_options( Languages::get_instance()->get_languages() );
		$field->set_sanitize_callback( array( $this, 'validate_language' ) );
		$setting->set_field( $field );

		// set the API versions.
		$api_versions = array(
			'v1' => __( 'v1', 'connector-for-propstack' ),
		);
		if ( Helper::is_development_mode() ) {
			$api_versions['v2'] = __( 'v2 (Beta)', 'connector-for-propstack' );
		}

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_api_version' );
		$setting->set_section( $section );
		$setting->set_type( 'string' );
		$setting->set_default( 'v1' );
		$field = new Select( $settings_obj );
		$field->set_title( __( 'API version', 'connector-for-propstack' ) );
		$field->set_options( $api_versions );
		$setting->set_field( $field );

		// create a hidden section.
		$hidden_section = $api_tab->add_section( 'propstack_connector_hidden', 10 );
		$hidden_section->set_hidden( true );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_main_object_type' );
		$setting->set_section( $hidden_section );
		$setting->set_type( 'string' );
		$setting->set_default( '' );
		$setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_update_slugs' );
		$setting->set_section( $hidden_section );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
		$setting->set_field( $field );

		// the log tab.
		$logs_tab = $settings_page->add_tab( 'propstack_connector_logs', 80 );
		$logs_tab->set_title( __( 'Logs', 'connector-for-propstack' ) );
		$logs_tab->set_hide_save( true );
		$logs_tab->set_callback( array( $this, 'show_logs' ) );

		// add a hidden tab.
		$hidden_tab = $settings_page->add_tab( 'propstack_connector_hidden', 1000 );
		$hidden_tab->set_show_in_menu( true );

		// add a section.
		$hidden_tab->add_section( 'propstack_connector_hidden', 10 );

		// initialize these settings.
		$settings_obj->init();
	}

	/**
	 * Return the hidden section for invisible settings.
	 *
	 * @return Section|false
	 */
	public function get_hidden_section(): Section|false {
		// get the settings page.
		$page = $this->get_settings_obj()->get_page( 'connector-for-propstack' );

		// bail if the page could not be loaded.
		if ( ! $page instanceof Page ) {
			return false;
		}

		// add a hidden tab.
		$hidden_tab = $page->get_tab( 'propstack_connector_hidden' );

		// bail if the tab could not be found.
		if ( ! $hidden_tab instanceof Tab ) {
			return false;
		}

		// return the hidden section.
		return $hidden_tab->get_section( 'propstack_connector_hidden' );
	}

	/**
	 * Add setting for plugin management.
	 *
	 * @return void
	 */
	public function add_plugin_settings(): void {
		// get the settings page.
		$settings_page = $this->get_settings_page();

		// bail if the page could not be found.
		if ( ! $settings_page instanceof Page ) {
			return;
		}

		// add a tab on this page to demonstration import and export of settings.
		$advanced_tab = $settings_page->add_tab( 'propstack_connector_advanced', 70 );
		$advanced_tab->set_title( __( 'Advanced settings', 'connector-for-propstack' ) );

		// add a section.
		$advanced_section = $advanced_tab->add_section( 'propstack_connector_advanced', 10 );
		$advanced_section->set_title( __( 'Advanced settings', 'connector-for-propstack' ) );

		// add setting.
		$setting = $this->get_settings_obj()->add_setting( 'propstack_connector_disable_archive_slug' );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
		$setting->set_section( $advanced_section );
		$setting->set_save_callback( array( $this, 'update_slugs' ) );
		$field = new Checkbox( $this->get_settings_obj() );
		$field->set_title( __( 'Disable archive view', 'connector-for-propstack' ) );
		$setting->set_field( $field );

		// add setting.
		$setting = $this->get_settings_obj()->add_setting( 'propstack_connector_disable_single_slug' );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
		$setting->set_section( $advanced_section );
		$setting->set_save_callback( array( $this, 'update_slugs' ) );
		$field = new Checkbox( $this->get_settings_obj() );
		$field->set_title( __( 'Disable single view', 'connector-for-propstack' ) );
		$setting->set_field( $field );

		// add setting.
		$setting = $this->get_settings_obj()->add_setting( 'propstack_connector_show_help' );
		$setting->set_section( $advanced_section );
		$setting->set_type( 'integer' );
		$setting->set_default( 1 );
		$field = new Checkbox( $this->get_settings_obj() );
		$field->set_title( __( 'Show help', 'connector-for-propstack' ) );
		$field->set_description( __( 'If enabled we will show hints in your page builder.', 'connector-for-propstack' ) );
		$setting->set_field( $field );

		// add setting.
		$setting = $this->get_settings_obj()->add_setting( 'propstack_connector_max_age_log_entries' );
		$setting->set_section( $advanced_section );
		$setting->set_type( 'integer' );
		$setting->set_default( 20 );
		$field = new Number( $this->get_settings_obj() );
		$field->set_title( __( 'max. Age for log entries in days', 'connector-for-propstack' ) );
		$setting->set_field( $field );

		// add setting.
		$setting = $this->get_settings_obj()->add_setting( 'propstack_connector_debug' );
		$setting->set_section( $advanced_section );
		$setting->set_type( 'integer' );
		$setting->set_default( Helper::is_development_mode() ? 1 : 0 );
		$field = new Checkbox( $this->get_settings_obj() );
		$field->set_title( __( 'Enable debug mode', 'connector-for-propstack' ) );
		$field->set_description( __( 'If enabled the plugin will log much more events. Do not use this in a production environment.', 'connector-for-propstack' ) );
		$setting->set_field( $field );

		// add setting.
		$setting = $this->get_settings_obj()->add_setting( 'propstack_connector_timeout' );
		$setting->set_type( 'integer' );
		$setting->set_default( 30 );
		$setting->set_section( $advanced_section );
		$field = new Number( $this->get_settings_obj() );
		$field->set_title( __( 'Default timeout', 'connector-for-propstack' ) );
		$field->set_description( __( 'This timeout will be used for any API connection.', 'connector-for-propstack' ) );
		$setting->set_field( $field );

		// add a section.
		$import_export_section = $advanced_tab->add_section( 'propstack_connector_import_export_section', 20 );
		$import_export_section->set_title( __( 'Secure settings', 'connector-for-propstack' ) );

		// create import dialog.
		$dialog = array(
			'className' => 'cfprop-dialog',
			'title'     => __( 'Import settings', 'connector-for-propstack' ),
			'texts'     => array(
				'<p><strong>' . __( 'Choose the JSON-file with the settings for this plugin.', 'connector-for-propstack' ) . '</strong></p>',
				'<input type="file" accept="application/json" name="import_settings_file" id="import_settings_file">',
			),
			'buttons'   => array(
				array(
					'action'  => 'settings_import_file();',
					'variant' => 'primary',
					'text'    => __( 'Import now', 'connector-for-propstack' ),
				),
				array(
					'action'  => 'closeDialog();',
					'variant' => 'secondary',
					'text'    => __( 'Cancel', 'connector-for-propstack' ),
				),
			),
		);

		// add setting.
		$setting = $this->get_settings_obj()->add_setting( 'import_settings' );
		$setting->set_section( $import_export_section );
		$setting->set_autoload( false );
		$setting->prevent_export( true );
		$field = new Button( $this->get_settings_obj() );
		$field->set_title( __( 'Import', 'connector-for-propstack' ) );
		$field->set_button_title( __( 'Import now', 'connector-for-propstack' ) );
		$field->add_class( 'easy-dialog-for-wordpress' );
		$field->set_custom_attributes( array( 'data-dialog' => (string) wp_json_encode( $dialog ) ) );
		$setting->set_field( $field );

		// create export dialog.
		$dialog = array(
			'className' => 'cfprop-dialog',
			'title'     => __( 'Export settings', 'connector-for-propstack' ),
			'texts'     => array(
				'<p><strong>' . __( 'Click on the following button to download the settings as JSON-file.', 'connector-for-propstack' ) . '</strong></p>',
				'<p>' . __( 'You can import this JSON-file in other projects using this WordPress plugin or theme.', 'connector-for-propstack' ) . '</p>',
			),
			'buttons'   => array(
				array(
					'action'  => 'closeDialog();location.href="' . $this->get_settings_obj()->get_export_obj()->get_download_url() . '";',
					'variant' => 'primary',
					'text'    => __( 'Export now', 'connector-for-propstack' ),
				),
				array(
					'action'  => 'closeDialog();',
					'variant' => 'secondary',
					'text'    => __( 'Cancel', 'connector-for-propstack' ),
				),
			),
		);

		// add setting.
		$setting = $this->get_settings_obj()->add_setting( 'export_settings' );
		$setting->set_section( $import_export_section );
		$setting->set_autoload( false );
		$setting->prevent_export( true );
		$field = new Button( $this->get_settings_obj() );
		$field->set_title( __( 'Export', 'connector-for-propstack' ) );
		$field->set_button_title( __( 'Export now', 'connector-for-propstack' ) );
		$field->set_button_url( $this->get_settings_obj()->get_export_obj()->get_download_url() );
		$field->add_class( 'easy-dialog-for-wordpress' );
		$field->set_custom_attributes( array( 'data-dialog' => (string) wp_json_encode( $dialog ) ) );
		$setting->set_field( $field );

		// add a section.
		$plugin_handling_section = $advanced_tab->add_section( 'propstack_connector_plugin_section', 30 );
		$plugin_handling_section->set_title( __( 'Plugin handling', 'connector-for-propstack' ) );

		// create reset URL.
		$reset_url = add_query_arg(
			array(
				'action' => 'propstack_connector_reset',
				'nonce'  => wp_create_nonce( 'cfprop-reset' ),
			),
			get_admin_url() . 'admin.php'
		);

		// create dialog.
		$reset_dialog = array(
			'className' => 'cfprop-dialog',
			'title'     => __( 'Reset plugin', 'connector-for-propstack' ),
			'texts'     => array(
				'<p><strong>' . __( 'Do you really want to reset any settings and data for the plugin Connector for Propstack?', 'connector-for-propstack' ) . '</strong></p>',
				'<p>' . __( 'This will not only reset all settings, but also remove all objects and associated data.', 'connector-for-propstack' ) . '</p>',
				'<p>' . __( 'You can then setup the plugin again.', 'connector-for-propstack' ) . '</p>',
				'<p><strong>' . __( 'We recommend creating a backup before resetting the plugin.', 'connector-for-propstack' ) . '</strong></p>',
			),
			'buttons'   => array(
				array(
					'action'  => 'location.href="' . $reset_url . '";',
					'variant' => 'primary',
					'text'    => __( 'Yes, reset it', 'connector-for-propstack' ),
				),
				array(
					'action'  => 'closeDialog();',
					'variant' => 'primary',
					'text'    => __( 'Cancel', 'connector-for-propstack' ),
				),
			),
		);

		// add setting.
		$setting = $this->get_settings_obj()->add_setting( 'propstack_connector_reset' );
		$setting->set_section( $plugin_handling_section );
		$setting->prevent_export( true );
		$field = new Button( $this->get_settings_obj() );
		$field->set_title( __( 'Reset plugin', 'connector-for-propstack' ) );
		$field->set_button_title( __( 'Reset plugin', 'connector-for-propstack' ) );
		$field->set_button_url( $reset_url );
		$field->add_data( 'dialog', Helper::get_json( $reset_dialog ) );
		$field->add_class( 'easy-dialog-for-wordpress' );
		$setting->set_field( $field );

		// the help tab.
		$help_tab = $settings_page->add_tab( 'help', 1000 );
		$help_tab->set_title( __( 'Questions? Check our forum!', 'connector-for-propstack' ) );
		$help_tab->set_tab_class( 'nav-tab-help' );
		$help_tab->set_url( Helper::get_plugin_support_url() );
		$help_tab->set_url_target( '_blank' );
	}

	/**
	 * Add a trademark hint in the settings.
	 *
	 * @return void
	 */
	public function add_trademark_hint(): void {
		// get the settings page.
		$settings_page = $this->get_settings_page();

		// bail if the page could not be found.
		if ( ! $settings_page instanceof Page ) {
			return;
		}

		// add a tab on this page to show trademark hints.
		$trademark_tab = $settings_page->add_tab( 'propstack_connector_trademark', 200 );
		$trademark_tab->set_title( '&copy;' );
		$trademark_tab->set_hide_save( true );

		// add a section.
		$trademark_section = $trademark_tab->add_section( 'propstack_connector_trademark', 10 );
		$trademark_section->set_title( __( 'Trademarks', 'connector-for-propstack' ) );

		// add setting for a TextInfo.
		$setting = $this->get_settings_obj()->add_setting( 'propstack_connector_trademark' );
		$setting->do_not_register( true );
		$setting->prevent_export( true );
		$setting->set_section( $trademark_section );
		$field = new TextInfo( $this->get_settings_obj() );
		$field->set_title( __( 'Propstack', 'connector-for-propstack' ) );
		/* translators: %1$s will be replaced by a URL. */
		$field->set_description( sprintf( __( 'The Propstack logo as part of all distributed icons is a trademark of <a href="%1$s" target="_blank">Propstack GmbH (open new window)</a>.', 'connector-for-propstack' ), 'https://www.propstack.de' ) );
		$setting->set_field( $field );
	}

	/**
	 * Return the settings page.
	 *
	 * @return Page|false
	 */
	public function get_settings_page(): Page|false {
		// get the settings object.
		$settings_obj = $this->get_settings_obj();

		// get the settings page.
		$settings_page = $settings_obj->get_page( 'connector-for-propstack' );

		// bail if the page could not be found.
		if ( ! $settings_page instanceof Page ) {
			return false;
		}

		// return the page.
		return $settings_page;
	}

	/**
	 * Return the settings URL for a specific tab.
	 *
	 * @param string $tab The slug of the tab (optional).
	 *
	 * @return string
	 */
	public function get_url( string $tab = '' ): string {
		if ( empty( $tab ) ) {
			return $this->get_settings_obj()->get_settings_link();
		}
		return add_query_arg(
			array(
				'tab' => $tab,
			),
			$this->get_settings_obj()->get_settings_link()
		);
	}

	/**
	 * Show the log table.
	 *
	 * @return void
	 */
	public function show_logs(): void {
		// bail if the user has not the capability for this.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// get the page from request.
		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// if WP_List_Table is not loaded automatically, we need to load it.
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}

		// get the table object.
		$log = new Log_Table();
		$log->prepare_items();

		// output.
		?>
		<div class="wrap">
			<h2><?php echo esc_html__( 'Logs', 'connector-for-propstack' ); ?></h2>
			<form action="<?php echo esc_url( get_admin_url() . 'edit.php' ); ?>" method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>">
				<input type="hidden" name="tab" value="logs">
				<input type="hidden" name="post_type" value="<?php echo esc_attr( ImmoObject::get_instance()->get_name() ); ?>">
				<?php
				$log->views();
				$log->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Reset the plugin by request.
	 *
	 * @return void
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 */
	public function reset_plugin_by_request(): void {
		// check nonce.
		check_admin_referer( 'cfprop-reset', 'nonce' );

		// uninstall all.
		Uninstaller::get_instance()->run();

		// run installer tasks.
		Installer::get_instance()->activation();

		// forward user to the dashboard.
		wp_safe_redirect( Setup::get_instance()->get_setup_link() );
		exit;
	}

	/**
	 * Validate the configured language.
	 *
	 * @param mixed $value The value from the settings field.
	 *
	 * @return string
	 */
	public function validate_language( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			$value = (string) $value;
		}
		return $value;
	}

	/**
	 * Trigger a permalink refresh on change of slugs.
	 *
	 * @param string|null $value The new value.
	 *
	 * @return string|null
	 */
	public function update_slugs( string|null $value ): string|null {
		update_option( 'propstack_connector_update_slugs', 1 );
		return $value;
	}

	/**
	 * Show hint in footer in the backend on listing and single view of objects there.
	 *
	 * @param string $content The actual footer content.
	 *
	 * @return string
	 */
	public function show_plugin_hint_in_footer( string $content ): string {
		// get the requested page.
		$page = (string) filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// bail if no page is requested.
		if ( empty( $page ) ) {
			return $content;
		}

		// bail if this is not the settings page.
		if ( 'connector-for-propstack' !== $page ) {
			return $content;
		}

		// show hint for our plugin.
		/* translators: %1$s will be replaced by the plugin name. */
		return $content . ' ' . sprintf( __( 'This page is provided by the plugin %1$s.', 'connector-for-propstack' ), '<em>' . Helper::get_plugin_name() . '</em>' );
	}

	/**
	 * Return whether the settings styles and scripts should be loaded.
	 *
	 * @param bool   $result The result.
	 * @param string $hook The used hook.
	 *
	 * @return bool
	 */
	public function enqueue_styles_and_scripts( bool $result, string $hook ): bool {
		if ( 'options-permalink.php' !== $hook ) {
			return $result;
		}
		return true;
	}
}

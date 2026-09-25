<?php
/**
 * File to handle intro for this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use easySettingsForWordPress\Fields\Button;
use easySettingsForWordPress\Page;
use easySettingsForWordPress\Section;
use easySettingsForWordPress\Tab;

/**
 * Initialize this object.
 */
class Intro {
	/**
	 * Instance of this object.
	 *
	 * @var ?Intro
	 */
	private static ?Intro $instance = null;

	/**
	 * Constructor for this handler.
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
	public static function get_instance(): Intro {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		// bail if main block editor functions are not available.
		if ( ! has_action( 'enqueue_block_assets' ) ) {
			return;
		}

		// add admin-actions.
		add_action( 'admin_action_cfprop_reset_intro', array( $this, 'reset_intro' ) );

		// add settings.
		add_action( 'init', array( $this, 'add_the_settings' ), 20 );

		// bail if intro has been run.
		if ( $this->is_closed() ) {
			return;
		}

		$false = false;
		/**
		 * Hide intro via hook.
		 *
		 * @since 3.0.0 Available since 3.0.0
		 *
		 * @param bool $false Return true to hide the intro.
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		if ( apply_filters( 'cfprop_hide_intro', $false ) ) {
			return;
		}

		// use hooks.
		add_action( 'admin_enqueue_scripts', array( $this, 'add_js' ) );
		add_filter( 'admin_body_class', array( $this, 'add_body_classes' ) );

		// add AJAX-actions.
		add_action( 'wp_ajax_cfprop_intro_closed', array( $this, 'set_closed_via_ajax' ) );
	}

	/**
	 * Return whether the intro is closed.
	 *
	 * @return bool
	 */
	private function is_closed(): bool {
		return 1 === absint( get_option( 'cfprop_intro' ) );
	}

	/**
	 * Set the intro to be closed.
	 *
	 * @return void
	 */
	public function set_closed(): void {
		update_option( 'cfprop_intro', 1 );
	}

	/**
	 * Save that intro has been closed via AJAX.
	 *
	 * @return void
	 */
	public function set_closed_via_ajax(): void {
		// check nonce.
		check_ajax_referer( 'cfprop-intro-closed', 'nonce' );

		// bail if capability is missing.
		if ( ! current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// save that intro has been closed.
		$this->set_closed();
	}

	/**
	 * Add the intro.js-scripts and -styles.
	 *
	 * @param string $hook The used hook.
	 *
	 * @source https://introjs.com/docs/examples/basic/hello-world
	 *
	 * @return void
	 */
	public function add_js( string $hook ): void {
		// bail on any other page then edit.php.
		if ( 'edit.php' !== $hook ) {
			return;
		}

		// embed the necessary scripts for the dialog.
		$path = Helper::get_plugin_path() . 'admin/driver/';
		$url  = Helper::get_plugin_url() . 'admin/driver/';

		// bail if a path does not exist.
		if ( ! file_exists( $path ) ) {
			return;
		}

		// embed the JS script from "intro.js".
		wp_enqueue_script(
			'cfprop-driver',
			$url . 'driver.js.iife.js',
			array(),
			Helper::get_file_version( trailingslashit( $path ) . 'driver.js.iife.js' ),
			true
		);

		// embed our own JS script.
		wp_enqueue_script(
			'cfprop-intro-custom',
			Helper::get_plugin_url() . 'admin/driver.js',
			array( 'cfprop-driver', 'jquery' ),
			Helper::get_file_version( Helper::get_plugin_path() . '/admin/driver.js' ),
			true
		);

		// embed the CSS file.
		wp_enqueue_style(
			'cfprop-driver',
			$url . 'driver.css',
			array(),
			Helper::get_file_version( trailingslashit( $path ) . 'driver.css' ),
		);

		// embed the CSS file.
		wp_enqueue_style(
			'cfprop-intro-custom',
			Helper::get_plugin_url() . 'admin/driver.css',
			array(),
			Helper::get_file_version( Helper::get_plugin_path() . '/admin/driver.css' ),
		);

		// add php-vars to our js-script.
		wp_localize_script(
			'cfprop-intro-custom',
			'cfPropIntroJsVars',
			array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'intro_closed_nonce' => wp_create_nonce( 'cfprop-intro-closed' ),
				'button_title_next'  => __( 'Next', 'connector-for-propstack' ),
				'button_title_back'  => __( 'Back', 'connector-for-propstack' ),
				'button_title_done'  => __( 'Done', 'connector-for-propstack' ),
				'step_1_title'       => __( 'Intro', 'connector-for-propstack' ),
				'step_1_intro'       => __( 'Thank you for installing <em>Connector for Propstack</em>. We will show you some basics to use this plugin.', 'connector-for-propstack' ),
				'step_2_title'       => __( 'Your objects', 'connector-for-propstack' ),
				'step_2_intro'       => __( 'This is one of your objects from Propstack. They will be updated daily or if you run the import.', 'connector-for-propstack' ),
				'step_3_title'       => __( 'The images', 'connector-for-propstack' ),
				'step_3_intro'       => __( 'The images of your objects will be automatically requested from Propstack. If you want to speed things up, you can also click this button. Alternatively, you will find a button in the settings under Import > Images that lets you download all images at once.', 'connector-for-propstack' ),
				'step_4_title'       => __( 'Change the view', 'connector-for-propstack' ),
				'step_4_intro'       => __( 'Choose the columns you need in your list in the backend. Which columns are filled depends on the data in your Propstack account. These settings are saved on your WordPress user.', 'connector-for-propstack' ),
				'step_5_title'       => __( 'Run the import', 'connector-for-propstack' ),
				'step_5_intro'       => __( 'On this button you could run the import of objects from Propstack any time. They can be displayed immediately afterwards in the frontend to your visitors.', 'connector-for-propstack' ),
				'step_6_title'       => __( 'Frontend view', 'connector-for-propstack' ),
				'step_6_intro'       => __( 'Here you will find the link to the objects in your frontend. You can configure the view in the settings or with the page builder you are using.', 'connector-for-propstack' ),
				'step_7_title'       => __( 'Settings', 'connector-for-propstack' ),
				'step_7_intro'       => __( 'The settings of this plugin help you to individualize the use of your Propstack objects on your website.', 'connector-for-propstack' ),
				'step_8_title'       => __( 'Thank you for using Connector for Propstack', 'connector-for-propstack' ),
				/* translators: %1$s, %2$s and %3$s will be replaced by URLs */
				'step_8_intro'       => sprintf( __( 'If you have any questions, please do not hesitate to ask them <a href="%1$s" target="_blank">in our forum (opens a new window)</a>.<br>You are also welcome to <a href="%2$s" target="_blank">rate the plugin (opens a new window)</a>.<br>If you also want to collect applications on your website, take a look at our <a href="%3$s" target="_blank">Connector for Propstack Pro (opens a new window)</a>.', 'connector-for-propstack' ), esc_url( Helper::get_plugin_support_url() ), esc_url( Helper::get_review_url() ), esc_url( Helper::get_pro_url() ) ),
			)
		);
	}

	/**
	 * Reset intro via request.
	 *
	 * @return void
	 */
	public function reset_intro(): void {
		// check nonce.
		check_ajax_referer( 'cfprop-intro-reset', 'nonce' );

		// bail if capability is missing.
		if ( ! current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// delete the actual setting.
		delete_option( 'cfprop_intro' );

		// redirect user to intro-start.
		wp_safe_redirect( $this->get_start_url() );
		exit;
	}

	/**
	 * Return the URL where set setup starts.
	 *
	 * @return string
	 */
	public function get_start_url(): string {
		return ImmoObject::get_instance()->get_link();
	}

	/**
	 * Add settings for the intro.
	 * *
	 *
	 * @return void
	 */
	public function add_the_settings(): void {
		// get settings object.
		$settings_obj = Settings::get_instance()->get_settings_obj();

		// get the main settings page.
		$main_settings_page = Settings::get_instance()->get_settings_page();

		// bail if the page could not be loaded.
		if ( ! $main_settings_page instanceof Page ) {
			return;
		}

		// get the advanced tab.
		$advanced_tab = $main_settings_page->get_tab( 'propstack_connector_advanced' );

		// bail if the page could not be loaded.
		if ( ! $advanced_tab instanceof Tab ) {
			return;
		}

		// get the advanced tab.
		$advanced_settings_tab = $advanced_tab->get_tab( 'propstack_connector_advanced_plugin_settings' );

		// bail if the page could not be loaded.
		if ( ! $advanced_settings_tab instanceof Tab ) {
			return;
		}

		// get the advanced section.
		$advanced_section = $advanced_settings_tab->get_section( 'propstack_connector_plugin_section' );

		// bail if the section could not be loaded.
		if ( ! $advanced_section instanceof Section ) {
			return;
		}

		// create dialog.
		$dialog = array(
			'title'   => __( 'Reset intro', 'connector-for-propstack' ),
			'texts'   => array(
				'<p><strong>' . __( 'Are your sure you want to reset the intro?', 'connector-for-propstack' ) . '</strong></p>',
			),
			'buttons' => array(
				array(
					'action'  => 'location.href="' . $this->get_reset_url() . '";',
					'variant' => 'primary',
					'text'    => __( 'Yes', 'connector-for-propstack' ),
				),
				array(
					'action'  => 'closeDialog();',
					'variant' => 'secondary',
					'text'    => __( 'No', 'connector-for-propstack' ),
				),
			),
		);

		// add setting.
		$setting = $settings_obj->add_setting( 'cfprop_reset_intro' );
		$setting->set_section( $advanced_section );
		$setting->set_autoload( false );
		$setting->prevent_export( true );
		$field = new Button( $settings_obj );
		$field->set_title( __( 'Reset intro', 'connector-for-propstack' ) );
		$field->set_button_title( __( 'Rerun the intro', 'connector-for-propstack' ) );
		$field->set_button_url( $this->get_reset_url() );
		$field->add_class( 'easy-dialog-for-wordpress' );
		$field->add_data( 'dialog', Helper::get_json( $dialog ) );
		$setting->set_field( $field );

		// get hidden section.
		$hidden_section = Settings::get_instance()->get_hidden_section();

		// bail if the hidden section could not be found.
		if ( ! $hidden_section instanceof Section ) {
			return;
		}

		// add setting.
		$setting = $settings_obj->add_setting( 'cfprop_intro' );
		$setting->set_section( $hidden_section );
		$setting->set_show_in_rest( true );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
	}

	/**
	 * Return the URL to reset the intro.
	 *
	 * @return string
	 */
	private function get_reset_url(): string {
		return add_query_arg(
			array(
				'action' => 'cfprop_reset_intro',
				'nonce'  => wp_create_nonce( 'cfprop-intro-reset' ),
			),
			get_admin_url() . 'admin.php'
		);
	}

	/**
	 * Add custom classes to body-tag.
	 *
	 * @param string $classes List of classes.
	 *
	 * @return string
	 */
	public function add_body_classes( string $classes ): string {
		if ( $this->is_closed() ) {
			return $classes;
		}

		// add our class to mark to show the intro.
		$classes .= ' cfprop-show-intro';

		// return the resulting list of classes.
		return $classes;
	}
}

<?php
/**
 * File to handle setup for this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Fields\MultiSelect;
use easySettingsForWordPress\Fields\Password;
use easySettingsForWordPress\Fields\Radio;
use easySettingsForWordPress\Setting;
use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transient;
use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;

/**
 * Initialize the setup object.
 */
class Setup {
	/**
	 * Instance of this object.
	 *
	 * @var ?Setup
	 */
	private static ?Setup $instance = null;

	/**
	 * Define setup as an array with steps.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $setup = array();

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
	public static function get_instance(): Setup {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the setup-object.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'init_setup' ), 100 );
	}

	/**
	 * Initialize the setup.
	 *
	 * @return void
	 */
	public function init_setup(): void {
		// check to show a hint if setup should be run.
		$this->show_hint();

		// only load setup if it is not completed.
		if ( ! $this->is_completed() ) {
			// initialize the setup object.
			$setup_obj = \easySetupForWordPress\Setup::get_instance();
			$setup_obj->init();

			// get the setup-object.
			$setup_obj->set_url( Helper::get_plugin_url() );
			$setup_obj->set_path( Helper::get_plugin_path() );
			$setup_obj->set_texts(
				array(
					'title_error' => __( 'Error', 'connector-for-propstack' ),
					'txt_error_1' => __( 'The following error occurred:', 'connector-for-propstack' ),
					/* translators: %1$s will be replaced with the URL of the plugin-forum on wp.org */
					'txt_error_2' => sprintf( __( '<strong>If the reason is unclear</strong> please contact our <a href="%1$s" target="_blank">support-forum (opens new window)</a> with as much detail as possible.', 'connector-for-propstack' ), esc_url( Helper::get_plugin_support_url() ) ),
				)
			);
			$setup_obj->set_display_hook( Settings::get_instance()->get_settings_obj()->get_menu_slug() );

			// set configuration for the setup.
			$setup_obj->set_config( $this->get_config() );

			// only load setup if it is not completed.
			add_filter( 'esfw_completed', array( $this, 'check_completed_value' ), 10, 2 );
			add_action( 'esfw_set_completed', array( $this, 'set_completed' ) );
			add_action( 'esfw_process', array( $this, 'run_process' ) );
			add_action( 'esfw_process', array( $this, 'show_process_end' ), PHP_INT_MAX );

			// add hooks to enable the setup of this plugin.
			add_action( 'admin_menu', array( $this, 'add_setup_menu' ) );

			// use own hooks.
			add_action( 'cfprop_import_object_set_max_count', array( $this, 'update_max_step' ) );
			add_action( 'cfprop_import_object_set_count', array( $this, 'update_process_step' ) );
			add_action( 'cfprop_import_object_set_status', array( $this, 'set_process_label' ) );
			add_action( 'cfprop_queue_before_processing', array( $this, 'update_max_step' ) );
			add_action( 'cfprop_queue_processing', array( $this, 'update_process_step' ) );

			// misc.
			add_filter( 'plugin_action_links_' . plugin_basename( CFPROP_PLUGIN ), array( $this, 'add_setting_link' ) );
		}
	}

	/**
	 * Return whether setup is completed.
	 *
	 * @return bool
	 */
	public function is_completed(): bool {
		$completed = \easySetupForWordPress\Setup::get_instance()->is_completed( $this->get_setup_name() );
		/**
		 * Filter the setup complete marker.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param bool $completed True if setup has been completed.
		 */
		return apply_filters( 'cfprop_setup_is_completed', $completed );
	}

	/**
	 * Return the setup-URL.
	 *
	 * @return string
	 */
	public function get_setup_link(): string {
		return add_query_arg(
			array(
				'page' => 'connector-for-propstack-setup',
			),
			get_admin_url() . 'options-general.php'
		);
	}

	/**
	 * Check if setup should be run and show the hint for it.
	 *
	 * @return void
	 */
	public function show_hint(): void {
		// get transients object.
		$transients_obj = Transients::get_instance();

		// check if setup should be run.
		if ( ! $this->is_completed() ) {
			// bail if the hint is already set.
			if ( $transients_obj->get_transient_by_name( 'propstack_connector_start_setup_hint' )->is_set() ) {
				return;
			}

			// delete all other transients.
			foreach ( $transients_obj->get_transients() as $transient_obj ) {
				// bail if the object is not ours.
				if ( ! $transient_obj instanceof Transient ) { // @phpstan-ignore instanceof.alwaysTrue
					continue;
				}

				// delete it.
				$transient_obj->delete();
			}

			// add a hint to run setup.
			$transient_obj = Transients::get_instance()->add();
			$transient_obj->set_name( 'propstack_connector_start_setup_hint' );
			$transient_obj->set_message( __( '<strong>You have installed Connector for Propstack - nice, and thank you!</strong> Now run the setup to expand your website with the possibilities of this plugin to promote your objects from Propstack.', 'connector-for-propstack' ) . '<br><br>' . sprintf( '<a href="%1$s" class="button button-primary">' . __( 'Start setup', 'connector-for-propstack' ) . '</a>', esc_url( $this->get_setup_link() ) ) );
			$transient_obj->set_type( 'error' );
			$transient_obj->set_dismissible_days( 2 );
			$transient_obj->set_hide_on(
				array(
					$this->get_setup_link(),
				)
			);
			$transient_obj->save();
		} elseif ( is_admin() ) {
			$transients_obj->get_transient_by_name( 'propstack_connector_start_setup_hint' )->delete();
		}
	}

	/**
	 * Return the configured setup.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_setup(): array {
		$setup = $this->setup;
		if ( empty( $setup ) ) {
			$this->set_config();
			$setup = $this->setup;
		}

		/**
		 * Filter the configured setup for this plugin.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param array<int,array<string,mixed>> $setup The setup-configuration.
		 */
		return apply_filters( 'cfprop_setup', $setup );
	}

	/**
	 * Show the setup dialog.
	 *
	 * @return void
	 */
	public function display(): void {
		// create help in case of error during loading of the setup.
		$error_help = '<div class="cfprop-transient notice notice-success"><h3>' . wp_kses_post( Helper::get_logo_img() ) . ' ' . esc_html( apply_filters( 'cfprop_transient_title', Helper::get_plugin_name() ) ) . '</h3><p><strong>' . __( 'Setup is loading', 'connector-for-propstack' ) . '</strong><br>' . __( 'Please wait while we load the setup.', 'connector-for-propstack' ) . '<br>' . __( 'However, you can also skip the setup and configure the plugin manually.', 'connector-for-propstack' ) . '</p><p><a href="' . esc_url( \easySetupForWordPress\Setup::get_instance()->get_skip_url( $this->get_setup_name(), Settings::get_instance()->get_url() ) ) . '" class="button button-primary">' . __( 'Skip setup', 'connector-for-propstack' ) . '</a></p></div>';

		// add error text.
		\easySetupForWordPress\Setup::get_instance()->set_error_help( $error_help );

		// output.
		echo wp_kses_post( \easySetupForWordPress\Setup::get_instance()->display( $this->get_setup_name() ) );
	}

	/**
	 * Convert options array to a react-compatible array-list with label and value.
	 *
	 * @param array<int|string,mixed> $options The list of options to convert.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function convert_options_for_react( array $options ): array {
		// define resulting list.
		$resulting_array = array();

		// loop through the options.
		foreach ( $options as $key => $label ) {
			$resulting_array[] = array(
				'label' => $label,
				'value' => $key,
			);
		}

		// return the resulting list.
		return $resulting_array;
	}

	/**
	 * Return configuration for setup.
	 *
	 * Here we define which steps and texts are used by easy-setup-for-wordpress.
	 *
	 * @return array<string,array<int,mixed>|string>
	 */
	private function get_config(): array {
		// get setup.
		$setup = $this->get_setup();

		// collect configuration for the setup.
		$config = array(
			'name'                  => $this->get_setup_name(),
			'title'                 => Helper::get_plugin_name() . ' ' . __( 'Setup', 'connector-for-propstack' ),
			'steps'                 => $setup,
			'back_button_label'     => __( 'Back', 'connector-for-propstack' ) . '<span class="dashicons dashicons-undo"></span>',
			'continue_button_label' => __( 'Continue', 'connector-for-propstack' ) . '<span class="dashicons dashicons-controls-play"></span>',
			'finish_button_label'   => __( 'Completed', 'connector-for-propstack' ) . '<span class="dashicons dashicons-saved"></span>',
			'skip_button_label'     => __( 'Skip', 'connector-for-propstack' ) . '<span class="dashicons dashicons-undo"></span>',
			'skip_url'              => \easySetupForWordPress\Setup::get_instance()->get_skip_url( $this->get_setup_name(), Settings::get_instance()->get_url() ),
			'error_label'           => __( 'An error occurred. Received an incorrect response from the server. Please check your permalink settings.', 'connector-for-propstack' ),
		);

		/**
		 * Filter the setup configuration.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<string,array<int,mixed>|string> $config List of configuration for the setup.
		 */
		return apply_filters( 'cfprop_setup_config', $config );
	}

	/**
	 * Set the process label.
	 *
	 * @param string $label The label to process.
	 *
	 * @return void
	 */
	public function set_process_label( string $label ): void {
		update_option( 'esfw_step_label', $label );
	}

	/**
	 * Updates the process step.
	 *
	 * @param int $step Steps to add.
	 *
	 * @return void
	 */
	public function update_process_step( int $step = 1 ): void {
		update_option( 'esfw_step', absint( get_option( 'esfw_step' ) + $step ) );
	}

	/**
	 * Sets the setup configuration.
	 *
	 * @return void
	 */
	public function set_config(): void {
		// get properties from settings.
		$settings = Settings::get_instance()->get_settings_obj();

		// get api token settings.
		$api_token = $settings->get_setting( 'propstack_connector_api_key' );

		// bail if URL setting could not be loaded.
		if ( ! $api_token instanceof Setting ) {
			return;
		}

		// get the field for URL settings.
		$api_token_field = $api_token->get_field();

		// bail if field is not available.
		if ( ! $api_token_field instanceof Password ) {
			return;
		}

		// get the main language setting.
		$language_setting = $settings->get_setting( 'propstack_connector_languages' );

		// bail if language setting could not be loaded.
		if ( ! $language_setting instanceof Setting ) {
			return;
		}

		// get the field for URL settings.
		$language_field = $language_setting->get_field();

		// bail if field is not available.
		if ( ! $language_field instanceof Radio ) {
			return;
		}

		// get the settings for the object types.
		$object_types_setting = $settings->get_setting( 'propstack_connector_import_object_type' );

		// bail if setting could not be loaded.
		if ( ! $object_types_setting instanceof Setting ) {
			return;
		}

		// get the field for URL settings.
		$object_types_field = $object_types_setting->get_field();

		// bail if field is not available.
		if ( ! $object_types_field instanceof MultiSelect ) {
			return;
		}

		// define setup.
		$this->setup = array(
			1 => array(
				$api_token->get_name()            => array(
					'type'                => 'TextControl',
					'label'               => $api_token_field->get_title(),
					'help'                => $api_token_field->get_description(),
					'placeholder'         => $api_token_field->get_placeholder(),
					'required'            => true,
					'validation_callback' => 'ConnectorForPropstack\Propstack\Propstack::rest_validate_key',
				),
				$object_types_setting->get_name() => array(
					'type'    => 'SelectControl',
					'label'   => $object_types_field->get_title(),
					'help'    => __( 'Only objects with the selected object type will be imported. All other will be ignored. You can change this setting after the setup any time.', 'connector-for-propstack' ),
					'options' => $this->convert_options_for_react( $object_types_field->get_options() ),
				),
				$language_setting->get_name()     => array(
					'type'    => 'RadioControl',
					'label'   => $language_field->get_title(),
					'help'    => $language_field->get_description(),
					'options' => $this->convert_options_for_react( $language_field->get_options() ), // @phpstan-ignore argument.type
				),
				'help'                            => array(
					'type' => 'Text',
					/* translators: %1$s will be replaced by our support-forum-URL. */
					'text' => '<p><span class="dashicons dashicons-editor-help"></span> ' . sprintf( __( '<strong>Need help?</strong> Ask in <a href="%1$s" target="_blank">our forum (opens new window)</a>.', 'connector-for-propstack' ), esc_url( Helper::get_plugin_support_url() ) ) . '</p>',
				),
			),
			2 => array(
				'runSetup' => array(
					'type'  => 'ProgressBar',
					'label' => __( 'Setup preparing your object data', 'connector-for-propstack' ),
				),
			),
		);
	}

	/**
	 * Update max count.
	 *
	 * @param int $add_to_max_count The value to add.
	 *
	 * @return void
	 */
	public function update_max_step( int $add_to_max_count ): void {
		update_option( 'esfw_max_steps', absint( get_option( 'esfw_max_steps' ) ) + $add_to_max_count );
	}

	/**
	 * Run the process to import objects
	 *
	 * @param string $config_name The name of the setup-configuration.
	 *
	 * @return void
	 */
	public function run_process( string $config_name ): void {
		// bail if this is not our setup.
		if ( $config_name !== $this->get_setup_name() ) {
			return;
		}

		// ignore if we have objects already.
		if ( ImmoObjects::get_instance()->has_objects() ) {
			return;
		}

		// update the max steps for this process.
		$this->update_max_step( 2 );

		// step 1: import the object from Propstack.
		$this->set_process_label( __( 'Retrieve data for your properties from Propstack.', 'connector-for-propstack' ) );
		ImmoObjects::get_instance()->import();
	}

	/**
	 * Show process end text.
	 *
	 * @param string $config_name The name of the setup-configuration.
	 *
	 * @return void
	 */
	public function show_process_end( string $config_name ): void {
		// bail if this is not our setup.
		if ( $config_name !== $this->get_setup_name() ) {
			return;
		}

		$completed_text = '<strong>' . __( 'Setup has been run.', 'connector-for-propstack' ) . '</strong> ' . __( 'Your objects from Propstack has been imported. Click on "Completed" to view them.', 'connector-for-propstack' );

		/**
		 * Filter the text for display if the setup has been run.
		 *
		 * @since 1.0.0 Available since 1.0.0
		 * @param string $completed_text The text to show.
		 * @param string $config_name The name of the setup-configuration used.
		 */
		$this->set_process_label( apply_filters( 'cfprop_setup_process_completed_text', $completed_text, $config_name ) );
	}

	/**
	 * Run additional tasks if the setup has been marked as completed.
	 *
	 * @param string $config_name The name of the setup-configuration.
	 *
	 * @return void
	 */
	public function set_completed( string $config_name ): void {
		// bail if this is not our setup.
		if ( $this->get_setup_name() !== $config_name ) {
			return;
		}

		// bail if this is not a request from API.
		if ( ! Helper::is_rest_request() ) {
			return;
		}

		/**
		 * Run additional tasks if the setup is marked as completed.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 */
		do_action( 'cfprop_setup_completed' );

		// return JSON with forward-URL.
		wp_send_json(
			array(
				'forward' => ImmoObject::get_instance()->get_link(),
			)
		);
	}

	/**
	 * If an API token is set, do not run the setup.
	 *
	 * @param bool   $is_completed Whether to run setup (true) or not (false).
	 * @param string $config_name The name of the used setup-configuration.
	 *
	 * @return bool
	 */
	public function check_completed_value( bool $is_completed, string $config_name ): bool {
		// bail if this is not our setup.
		if ( $this->get_setup_name() !== $config_name ) {
			return $is_completed;
		}

		if ( ! empty( get_option( 'propstack_connector_api_key' ) ) ) {
			return true;
		}

		return $is_completed;
	}

	/**
	 * Return the name for the setup configuration.
	 *
	 * @return string
	 */
	public function get_setup_name(): string {
		return 'connector-for-propstack';
	}

	/**
	 * Uninstall the setup.
	 *
	 * @return void
	 */
	public function uninstall(): void {
		\easySetupForWordPress\Setup::get_instance()->uninstall( $this->get_setup_name() );
	}

	/**
	 * Add a setup menu if setup is not completed.
	 *
	 * @return void
	 */
	public function add_setup_menu(): void {
		// add setup entry as sub-menu.
		add_options_page(
			Helper::get_plugin_name() . ' ' . __( 'Setup', 'connector-for-propstack' ),
			Helper::get_plugin_name(),
			'manage_options',
			'connector-for-propstack-setup',
			array( $this, 'display' )
		);

		// remove the settings menu entry.
		remove_submenu_page( 'options-general.php', 'connector-for-propstack' );
	}

	/**
	 * Add a link to the setup in the plugin-list.
	 *
	 * @param array<int,string> $links List of links.
	 * @return array<int,string>
	 */
	public function add_setting_link( array $links ): array {
		// add a link.
		$links[] = '<a href="' . esc_url( $this->get_setup_link() ) . '">' . __( 'Setup', 'connector-for-propstack' ) . '</a>';

		// return the resulting list of links.
		return $links;
	}
}

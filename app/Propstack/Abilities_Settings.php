<?php
/**
 * File for the settings and hints about the abilities of this plugin.
 *
 * @package connector-for-propstack
 */

declare(strict_types=1);

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients;
use ConnectorForPropstack\Plugin\Admin\Callback_TextInfo;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Settings;
use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Page;
use easySettingsForWordPress\Tab;
use Throwable;
use WP_Error;
use WP_Post;

/**
 * Show the abilities of this plugin in the settings and inform about them once.
 *
 * The abilities are made for applications, which the user connects to WordPress himself (e.g. an AI assistant via
 * the MCP Adapter). Nothing is sent anywhere by this plugin, so the texts describe the feature neutrally, and it can
 * be disabled completely.
 */
class Abilities_Settings {
	/**
	 * The option to enable or disable the abilities.
	 */
	public const OPTION = 'propstack_connector_abilities';

	/**
	 * The name of the tab for advanced settings.
	 */
	private const TAB = 'propstack_connector_advanced';

	/**
	 * The name of the sub tab for the abilities.
	 */
	private const SUBTAB = 'propstack_connector_abilities';

	/**
	 * The meta key, which marks templates saved via the template abilities.
	 */
	private const MARKER_META = 'propstackConnectorTemplate';

	/**
	 * Instance of this object.
	 *
	 * @var ?Abilities_Settings
	 */
	private static ?Abilities_Settings $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return instance of this object as singleton.
	 *
	 * @return Abilities_Settings
	 */
	public static function get_instance(): Abilities_Settings {
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
		add_action( 'init', array( $this, 'add_settings' ), 30 );
		add_action( 'admin_action_cfprop_reset_ability_template', array( $this, 'reset_template_by_request' ) );
		add_filter( 'display_post_states', array( $this, 'add_post_state' ), 10, 2 );
	}

	/**
	 * Return whether the abilities of this plugin are enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return 1 === absint( get_option( self::OPTION, 1 ) );
	}

	/**
	 * Return whether WordPress supports abilities (since WordPress 6.9).
	 *
	 * @return bool
	 */
	public function is_api_available(): bool {
		return function_exists( 'wp_register_ability' );
	}

	/**
	 * Return whether the MCP Adapter, which connects applications with the abilities, is active.
	 *
	 * @return bool
	 */
	public function is_mcp_adapter_active(): bool {
		return class_exists( '\WP\MCP\Core\McpAdapter' );
	}

	/**
	 * Return the URL of the settings for the abilities.
	 *
	 * @return string
	 */
	public function get_url(): string {
		return Settings::get_instance()->get_url( self::TAB, self::SUBTAB );
	}

	/**
	 * Add the settings for the abilities as sub tab of the advanced settings.
	 *
	 * @return void
	 */
	public function add_settings(): void {
		// get the settings object and page.
		$settings_obj  = Settings::get_instance()->get_settings_obj();
		$settings_page = Settings::get_instance()->get_settings_page();

		// bail if the page could not be found.
		if ( ! $settings_page instanceof Page ) {
			return;
		}

		// get the tab for advanced settings.
		$advanced_tab = $settings_page->get_tab( self::TAB );

		// bail if the tab could not be found.
		if ( ! $advanced_tab instanceof Tab ) {
			return;
		}

		// add the sub tab.
		$tab = $advanced_tab->add_tab( self::SUBTAB, 35 );
		$tab->set_title( __( 'Abilities', 'connector-for-propstack' ) );

		// add a section.
		$section = $tab->add_section( 'propstack_connector_abilities_section', 10 );
		$section->set_title( __( 'Abilities for connected applications', 'connector-for-propstack' ) );

		// add the setting to enable or disable the abilities.
		$setting = $settings_obj->add_setting( self::OPTION );
		$setting->set_section( $section );
		$setting->set_type( 'integer' );
		$setting->set_default( 1 );
		$field = new Checkbox( $settings_obj );
		$field->set_title( __( 'Provide abilities', 'connector-for-propstack' ) );
		$field->set_description( __( 'Provides the abilities of this plugin via the Abilities API of WordPress. Applications, which you connect to your WordPress yourself (e.g. an AI assistant via the MCP Adapter), can then read your objects and create templates for the detail view, and the list of objects. This plugin sends no data to such applications on its own. Disable this option if you do not want to use it.', 'connector-for-propstack' ) );
		$setting->set_field( $field );

		// add the status.
		$setting = $settings_obj->add_setting( 'propstack_connector_abilities_status' );
		$setting->set_section( $section );
		$setting->prevent_export( true );
		$field = new Callback_TextInfo( $settings_obj );
		$field->set_title( __( 'Status', 'connector-for-propstack' ) );
		$field->set_callback( array( $this, 'get_status_html' ) );
		$setting->set_field( $field );

		// add the templates of the page builders.
		$setting = $settings_obj->add_setting( 'propstack_connector_abilities_templates' );
		$setting->set_section( $section );
		$setting->prevent_export( true );
		$field = new Callback_TextInfo( $settings_obj );
		$field->set_title( __( 'Templates for your page builders', 'connector-for-propstack' ) );
		$field->set_callback( array( $this, 'get_templates_html' ) );
		$setting->set_field( $field );

		// add the guide.
		$setting = $settings_obj->add_setting( 'propstack_connector_abilities_guide' );
		$setting->set_section( $section );
		$setting->prevent_export( true );
		$field = new Callback_TextInfo( $settings_obj );
		$field->set_title( __( 'Getting started', 'connector-for-propstack' ) );
		$field->set_callback( array( $this, 'get_guide_html' ) );
		$setting->set_field( $field );
	}

	/**
	 * Return a line of the status with an icon.
	 *
	 * @param bool   $ok   Whether the requirement is fulfilled.
	 * @param string $text The text (HTML).
	 *
	 * @return string
	 */
	private function get_status_line( bool $ok, string $text ): string {
		return '<li><span class="dashicons dashicons-' . ( $ok ? 'yes' : 'no' ) . '"></span> ' . $text . '</li>';
	}

	/**
	 * Return the status of the abilities as HTML.
	 *
	 * @return string
	 */
	public function get_status_html(): string {
		// bail if the abilities are disabled.
		if ( ! $this->is_enabled() ) {
			return '<p>' . esc_html__( 'The abilities are disabled.', 'connector-for-propstack' ) . '</p>';
		}

		$lines = array();

		// the Abilities API.
		if ( $this->is_api_available() ) {
			$lines[] = $this->get_status_line( true, esc_html__( 'Your WordPress supports abilities.', 'connector-for-propstack' ) );
		} else {
			$lines[] = $this->get_status_line( false, esc_html__( 'Your WordPress does not support abilities yet. They are available since WordPress 6.9.', 'connector-for-propstack' ) );
		}

		// the MCP Adapter (optional, other MCP plugins with support for abilities work as well).
		if ( $this->is_mcp_adapter_active() ) {
			/* translators: %1$s will be replaced by a URL. */
			$lines[] = $this->get_status_line( true, sprintf( __( 'The MCP Adapter is active. Its default endpoint for connected applications is %1$s', 'connector-for-propstack' ), '<code>' . esc_html( rest_url( 'mcp/mcp-adapter-default-server' ) ) . '</code>' ) );
		} else {
			$lines[] = '<li><span class="dashicons dashicons-info"></span> ' . esc_html__( 'To connect applications like AI assistants, you need an MCP plugin with support for the abilities of WordPress.', 'connector-for-propstack' ) . '</li>';
		}

		// return the list.
		return '<ul>' . implode( '', $lines ) . '</ul>';
	}

	/**
	 * Return the templates of the page builders with their state as HTML.
	 *
	 * @return string
	 */
	public function get_templates_html(): string {
		// bail if the abilities can not be used.
		if ( ! $this->is_enabled() || ! $this->is_api_available() ) {
			return '<p>' . esc_html__( 'Available as soon as the abilities are provided.', 'connector-for-propstack' ) . '</p>';
		}

		// get the page builders.
		$adapters = Template_Abilities::get_instance()->get_adapters();

		// bail if no page builder is supported.
		if ( empty( $adapters ) ) {
			return '<p>' . esc_html__( 'No supported page builder found.', 'connector-for-propstack' ) . '</p>';
		}

		// create a table with a row per page builder.
		$html = '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Page builder', 'connector-for-propstack' ) . '</th>';
		foreach ( reset( $adapters )->get_template_types() as $label ) {
			$html .= '<th>' . esc_html( $label ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( $adapters as $adapter ) {
			$html .= '<tr><td>' . esc_html( $adapter->get_label() ) . '</td>';

			// show the reason, if the page builder is not available.
			if ( ! $adapter->is_available() ) {
				$html .= '<td colspan="' . count( $adapter->get_template_types() ) . '">' . esc_html( $adapter->get_unavailable_reason() ) . '</td></tr>';
				continue;
			}

			// show the state of each template.
			foreach ( array_keys( $adapter->get_template_types() ) as $type ) {
				$html .= '<td>' . $this->get_template_state_html( $adapter, $type ) . '</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';

		// return the resulting HTML.
		return $html;
	}

	/**
	 * Return the state of a template as HTML, with a button to reset it, if it was saved via abilities.
	 *
	 * @param Template_Adapter_Base $adapter The adapter of the page builder.
	 * @param string                $type    The template type.
	 *
	 * @return string
	 */
	private function get_template_state_html( Template_Adapter_Base $adapter, string $type ): string {
		// get the template, errors of a page builder must not break the settings.
		try {
			$template = $adapter->get_template( $type );
		} catch ( Throwable $e ) {
			$template = new WP_Error( 'cfprop_template_error', $e->getMessage() );
		}
		if ( $template instanceof WP_Error ) {
			return esc_html( $template->get_error_message() );
		}

		// the template is not saved via abilities.
		if ( empty( $template['is_customized'] ) ) {
			$id     = isset( $template['id'] ) && is_scalar( $template['id'] ) ? (string) $template['id'] : '';
			$source = isset( $template['source'] ) && is_string( $template['source'] ) ? $template['source'] : '';
			if ( '' === $id || 'none' === $source ) {
				return esc_html__( 'Classic template of this plugin', 'connector-for-propstack' );
			}
			if ( 'plugin' === $source ) {
				return esc_html__( 'Template of this plugin', 'connector-for-propstack' );
			}
			if ( 'theme' === $source ) {
				return esc_html__( 'Template of the theme', 'connector-for-propstack' );
			}
			if ( ctype_digit( $id ) ) {
				/* translators: %1$s will be replaced by an ID. */
				return esc_html( sprintf( __( 'Template of the page builder (ID %1$s)', 'connector-for-propstack' ), $id ) );
			}
			return esc_html__( 'Template of the page builder', 'connector-for-propstack' );
		}

		// the template is saved via abilities: offer to reset it.
		$html = esc_html__( 'Created via abilities', 'connector-for-propstack' );
		if ( $adapter->can_save() ) {
			$url    = add_query_arg(
				array(
					'action'  => 'cfprop_reset_ability_template',
					'builder' => $adapter->get_name(),
					'type'    => $type,
					'nonce'   => wp_create_nonce( 'cfprop-reset-ability-template' ),
				),
				get_admin_url() . 'admin.php'
			);
			$dialog = array(
				'className' => 'cfprop-dialog',
				'title'     => __( 'Reset template', 'connector-for-propstack' ),
				'texts'     => array(
					'<p><strong>' . __( 'Do you really want to reset this template?', 'connector-for-propstack' ) . '</strong></p>',
					'<p>' . __( 'The template will be moved to the trash. The template, which was used before, will be used again.', 'connector-for-propstack' ) . '</p>',
				),
				'buttons'   => array(
					array(
						'action'  => 'location.href="' . $url . '";',
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
			$html  .= '<br><a href="' . esc_url( $url ) . '" class="button easy-dialog-for-wordpress" data-dialog="' . esc_attr( Helper::get_json( $dialog ) ) . '">' . esc_html__( 'Reset', 'connector-for-propstack' ) . '</a>';
		}
		return $html;
	}

	/**
	 * Return the guide for connecting an application as HTML.
	 *
	 * @return string
	 */
	public function get_guide_html(): string {
		$html = '<ol>';
		/* translators: %1$s will be replaced by a URL. */
		$html .= '<li>' . sprintf( __( 'Install and activate an MCP plugin of your choice, which supports the abilities of WordPress (e.g. the <a href="%1$s" target="_blank">MCP Adapter</a>).', 'connector-for-propstack' ), esc_url( 'https://github.com/WordPress/mcp-adapter' ) ) . '</li>';
		$html .= '<li>' . esc_html__( 'Make sure the abilities of this plugin are available in your MCP plugin. Depending on the plugin, you may have to enable them in its settings.', 'connector-for-propstack' ) . '</li>';
		/* translators: %1$s will be replaced by a URL. */
		$html .= '<li>' . sprintf( __( 'Set up the access for your application as described by your MCP plugin, e.g. with an <a href="%1$s">application password</a> for your user.', 'connector-for-propstack' ), esc_url( admin_url( 'profile.php#application-passwords-section' ) ) ) . '</li>';
		$html .= '<li>' . esc_html__( 'Connect your application (e.g. an AI assistant with MCP support) with the endpoint of your MCP plugin and the access data.', 'connector-for-propstack' ) . '</li>';
		$html .= '</ol>';
		// … examples for prompts and the final note unchanged …
		return $html;
	}

	/**
	 * Reset a template, which was saved via abilities, by request from the settings.
	 *
	 * @return void
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 */
	public function reset_template_by_request(): void {
		// check nonce.
		check_admin_referer( 'cfprop-reset-ability-template', 'nonce' );

		// get the target URL.
		$referer = wp_get_referer();
		$url     = is_string( $referer ) ? $referer : $this->get_url();

		// bail if the user is not allowed to change templates.
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_safe_redirect( $url );
			exit;
		}

		// get the page builder and the template type and reset the template.
		$builder = isset( $_GET['builder'] ) ? sanitize_key( wp_unslash( $_GET['builder'] ) ) : '';
		$type    = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
		$result  = $this->reset_template( $builder, $type );

		// show the result.
		$transient_obj = Transients::get_instance()->add();
		$transient_obj->set_name( 'cfprop_abilities_reset' );
		if ( $result instanceof WP_Error ) {
			$transient_obj->set_message( __( 'The template could not be reset:', 'connector-for-propstack' ) . ' ' . esc_html( $result->get_error_message() ) );
			$transient_obj->set_type( 'error' );
		} elseif ( 'none' === ( $result['action'] ?? '' ) ) {
			$transient_obj->set_message( __( 'There was no template to reset.', 'connector-for-propstack' ) );
			$transient_obj->set_type( 'success' );
		} else {
			$transient_obj->set_message( __( 'The template has been reset. The template, which was used before, is used again.', 'connector-for-propstack' ) );
			$transient_obj->set_type( 'success' );
		}
		$transient_obj->save();

		// forward the user.
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Reset the template of the given type, which was saved via abilities, for the given page builder.
	 *
	 * @param string $builder The internal name of the page builder.
	 * @param string $type    The template type.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function reset_template( string $builder, string $type ): array|WP_Error {
		// get the page builder.
		$adapter = '' !== $builder ? Template_Abilities::get_instance()->get_adapter( $builder ) : new WP_Error( 'cfprop_missing_builder', __( 'No page builder given.', 'connector-for-propstack' ) );
		if ( $adapter instanceof WP_Error ) {
			return $adapter;
		}

		// check the template type.
		if ( ! array_key_exists( $type, $adapter->get_template_types() ) ) {
			return new WP_Error( 'cfprop_unknown_type', __( 'The template type is unknown.', 'connector-for-propstack' ) );
		}

		// reset the template, errors of a page builder must not break the request.
		try {
			return $adapter->reset_template( $type, false );
		} catch ( Throwable $e ) {
			return new WP_Error( 'cfprop_reset_failed', $e->getMessage() );
		}
	}

	/**
	 * Mark templates saved via abilities in the lists of templates of the page builders.
	 *
	 * @param mixed $states The states of the post.
	 * @param mixed $post   The post.
	 *
	 * @return mixed
	 */
	public function add_post_state( mixed $states, mixed $post ): mixed {
		// bail without usable data.
		if ( ! is_array( $states ) || ! $post instanceof WP_Post ) {
			return $states;
		}

		// mark the templates with our marker.
		$marker = get_post_meta( $post->ID, self::MARKER_META, true );
		if ( is_string( $marker ) && preg_match( '/^ai_[a-z0-9_]*(single|archive)_template$/', $marker ) ) {
			$states['cfprop_abilities'] = __( 'Created via abilities', 'connector-for-propstack' );
		}
		return $states;
	}
}

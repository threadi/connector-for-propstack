<?php
/**
 * Tests for the settings and hints about the abilities.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients;
use ConnectorForPropstack\Plugin\Admin\Callback_TextInfo;
use ConnectorForPropstack\Plugin\Settings;
use ConnectorForPropstack\Propstack\Abilities;
use ConnectorForPropstack\Propstack\Abilities_Settings;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use ConnectorForPropstack\Propstack\Template_Adapter_Base;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;

/**
 * Object to test the settings and hints about the abilities.
 */
class AbilitiesSettings extends ConnectorForPropstackTestCase {
	/**
	 * The theme before the test.
	 *
	 * @var string
	 */
	private string $original_theme = '';

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// register the post type for objects (the test suite resets post types after each test).
		ImmoObject::get_instance()->register();

		// use a block theme for the templates of the block editor.
		$this->original_theme = get_stylesheet();
		switch_theme( 'twentytwentyfive' );

		// use an administrator.
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $user_id );
		}
		wp_set_current_user( $user_id );

		// start with enabled abilities and without hint.
		delete_option( Abilities_Settings::OPTION );
		delete_option( Abilities_Settings::HINT_OPTION );
		Transients::get_instance()->get_transient_by_name( 'cfprop_abilities_hint' )->delete();
	}

	/**
	 * Clean up the test environment after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		if ( ! empty( $this->original_theme ) ) {
			switch_theme( $this->original_theme );
		}
		delete_option( Abilities_Settings::OPTION );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Return an adapter for tests.
	 *
	 * @param array<string,mixed> $template  The template, which get_template() returns.
	 * @param bool                $available Whether the page builder is available.
	 *
	 * @return Template_Adapter_Base
	 */
	private function get_adapter( array $template, bool $available = true ): Template_Adapter_Base {
		return new class( $template, $available ) extends Template_Adapter_Base {
			/**
			 * The reset template types.
			 *
			 * @var array<int,string>
			 */
			public array $resets = array();

			/**
			 * Constructor.
			 *
			 * @param array<string,mixed> $template  The template.
			 * @param bool                $available Whether the page builder is available.
			 */
			public function __construct( private array $template, private bool $available ) {}

			/**
			 * Return the name.
			 *
			 * @return string
			 */
			public function get_name(): string {
				return 'test-builder';
			}

			/**
			 * Return the label.
			 *
			 * @return string
			 */
			public function get_label(): string {
				return 'Test builder';
			}

			/**
			 * Return whether the page builder is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return $this->available;
			}

			/**
			 * Return the reason.
			 *
			 * @return string
			 */
			public function get_unavailable_reason(): string {
				return 'Test builder is not active.';
			}

			/**
			 * Return the format.
			 *
			 * @return string
			 */
			public function get_format(): string {
				return '';
			}

			/**
			 * Return the elements.
			 *
			 * @return array<int,array<string,mixed>>
			 */
			public function get_elements(): array {
				return array();
			}

			/**
			 * Return the template.
			 *
			 * @param string $type The template type.
			 *
			 * @return array<string,mixed>|WP_Error
			 */
			public function get_template( string $type ): array|WP_Error {
				return $this->template;
			}

			/**
			 * Validate the template.
			 *
			 * @param string $type    The template type.
			 * @param string $content The content.
			 *
			 * @return array<string,array<int,string>>
			 */
			public function validate( string $type, string $content ): array {
				return array(
					'errors'   => array(),
					'warnings' => array(),
				);
			}

			/**
			 * Render the template.
			 *
			 * @param string $type    The template type.
			 * @param string $content The content.
			 * @param int    $post_id The post-ID.
			 *
			 * @return string|WP_Error
			 */
			public function render( string $type, string $content, int $post_id ): string|WP_Error {
				return '';
			}

			/**
			 * Return whether templates can be saved.
			 *
			 * @return bool
			 */
			public function can_save(): bool {
				return $this->available;
			}

			/**
			 * Remember the reset.
			 *
			 * @param string $type    The template type.
			 * @param bool   $dry_run True for a dry run.
			 *
			 * @return array<string,mixed>|WP_Error
			 */
			public function reset_template( string $type, bool $dry_run ): array|WP_Error {
				$this->resets[] = $type;
				return array( 'action' => 'reset' );
			}
		};
	}

	/**
	 * Test that the sub tab with its settings exists and the abilities are enabled by default.
	 *
	 * @return void
	 */
	public function test_settings(): void {
		$settings_obj = Settings::get_instance()->get_settings_obj();
		$this->assertNotFalse( $settings_obj->get_setting( Abilities_Settings::OPTION ) );
		$status = $settings_obj->get_setting( 'propstack_connector_abilities_status' );
		$this->assertNotFalse( $status );
		$this->assertInstanceOf( Callback_TextInfo::class, $status->get_field() );
		$this->assertTrue( Abilities_Settings::get_instance()->is_enabled() );
		$this->assertStringContainsString( 'subtab=propstack_connector_abilities', Abilities_Settings::get_instance()->get_url() );
	}

	/**
	 * Test that the abilities are not registered if they are disabled.
	 *
	 * @return void
	 */
	public function test_disable_abilities(): void {
		$abilities = Abilities::get_instance();

		update_option( Abilities_Settings::OPTION, 0 );
		remove_action( 'wp_abilities_api_init', array( $abilities, 'add_abilities' ) );
		$abilities->init();
		$this->assertFalse( has_action( 'wp_abilities_api_init', array( $abilities, 'add_abilities' ) ) );
		$this->assertStringContainsString( 'disabled', Abilities_Settings::get_instance()->get_status_html() );
		$this->assertStringContainsString( 'Available as soon', Abilities_Settings::get_instance()->get_templates_html() );

		update_option( Abilities_Settings::OPTION, 1 );
		$abilities->init();
		$this->assertNotFalse( has_action( 'wp_abilities_api_init', array( $abilities, 'add_abilities' ) ) );
	}

	/**
	 * Test the list of templates with their states and the reset.
	 *
	 * @return void
	 */
	public function test_templates_and_reset(): void {
		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'The abilities API is not available.' );
		}

		// a page builder with a template saved via abilities.
		$adapter  = $this->get_adapter(
			array(
				'id'            => '42',
				'source'        => 'custom',
				'is_customized' => true,
			)
		);
		$callback = static fn( array $adapters ): array => array( $adapter );
		add_filter( 'cfprop_template_ability_adapters', $callback, PHP_INT_MAX );
		$html = Abilities_Settings::get_instance()->get_templates_html();
		$this->assertStringContainsString( 'Test builder', $html );
		$this->assertStringContainsString( 'Created via abilities', $html );
		$this->assertStringContainsString( 'action=cfprop_reset_ability_template', $html );
		$this->assertStringContainsString( 'easy-dialog-for-wordpress', $html );

		// the field shows the button with its dialog, although the output is filtered.
		$setting = Settings::get_instance()->get_settings_obj()->get_setting( 'propstack_connector_abilities_templates' );
		$this->assertNotFalse( $setting );
		$field = $setting->get_field();
		$this->assertInstanceOf( Callback_TextInfo::class, $field );
		ob_start();
		$field->display( array( 'setting' => $setting ) );
		$output = (string) ob_get_clean();
		$this->assertStringContainsString( 'class="button easy-dialog-for-wordpress"', $output );
		$this->assertStringContainsString( 'data-dialog="', $output );
		$this->assertStringContainsString( 'closeDialog', $output );

		// reset it.
		$this->assertSame( array( 'action' => 'reset' ), Abilities_Settings::get_instance()->reset_template( 'test-builder', 'archive' ) );
		$this->assertSame( array( 'archive' ), $adapter->resets );
		$this->assertInstanceOf( WP_Error::class, Abilities_Settings::get_instance()->reset_template( 'test-builder', 'unknown' ) );
		$this->assertInstanceOf( WP_Error::class, Abilities_Settings::get_instance()->reset_template( 'unknown-builder', 'single' ) );
		$this->assertInstanceOf( WP_Error::class, Abilities_Settings::get_instance()->reset_template( '', 'single' ) );
		remove_filter( 'cfprop_template_ability_adapters', $callback, PHP_INT_MAX );

		// a template of the page builder and an unavailable page builder.
		$adapter  = $this->get_adapter(
			array(
				'id'            => '7',
				'source'        => 'builder',
				'is_customized' => false,
			)
		);
		$callback = static fn( array $adapters ): array => array( $adapter );
		add_filter( 'cfprop_template_ability_adapters', $callback, PHP_INT_MAX );
		$html = Abilities_Settings::get_instance()->get_templates_html();
		$this->assertStringContainsString( 'ID 7', $html );
		$this->assertStringNotContainsString( 'cfprop_reset_ability_template', $html );
		remove_filter( 'cfprop_template_ability_adapters', $callback, PHP_INT_MAX );

		$adapter  = $this->get_adapter( array(), false );
		$callback = static fn( array $adapters ): array => array( $adapter );
		add_filter( 'cfprop_template_ability_adapters', $callback, PHP_INT_MAX );
		$this->assertStringContainsString( 'Test builder is not active.', Abilities_Settings::get_instance()->get_templates_html() );
		remove_filter( 'cfprop_template_ability_adapters', $callback, PHP_INT_MAX );
	}

	/**
	 * Test the list and the reset with a template of the block editor saved via abilities.
	 *
	 * @return void
	 */
	public function test_block_editor_template(): void {
		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'The abilities API is not available.' );
		}

		$this->assertStringContainsString( 'Template of this plugin', Abilities_Settings::get_instance()->get_templates_html() );

		$ability = wp_get_ability( 'connector-for-propstack/save-template' );
		$this->assertNotNull( $ability );
		$result = $ability->execute(
			array(
				'builder' => 'gutenberg',
				'content' => '<!-- wp:group --><div class="wp-block-group"><!-- wp:connector-for-propstack/field {"field_name":"price"} /--></div><!-- /wp:group -->',
				'dry_run' => false,
			)
		);
		$this->assertTrue( $result['done'], implode( ' ', $result['errors'] ) );
		$this->assertStringContainsString( 'Created via abilities', Abilities_Settings::get_instance()->get_templates_html() );

		$reset = Abilities_Settings::get_instance()->reset_template( 'gutenberg', 'single' );
		$this->assertIsArray( $reset );
		$this->assertSame( 'reset', $reset['action'] );
		$this->assertStringNotContainsString( 'Created via abilities', Abilities_Settings::get_instance()->get_templates_html() );
	}

	/**
	 * Test the status.
	 *
	 * @return void
	 */
	public function test_status(): void {
		$html = Abilities_Settings::get_instance()->get_status_html();
		$this->assertStringContainsString( 'MCP Adapter', $html );
		$this->assertStringContainsString( function_exists( 'wp_register_ability' ) ? 'supports abilities' : 'WordPress 6.9', $html );
		$this->assertStringContainsString( 'application password', Abilities_Settings::get_instance()->get_guide_html() );
	}

	/**
	 * Test that the hint is created once and only for users, who can use templates.
	 *
	 * @return void
	 */
	public function test_hint(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'The abilities API is not available.' );
		}
		$transients = Transients::get_instance();

		// not for editors.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		Abilities_Settings::get_instance()->add_hint();
		$this->assertFalse( $transients->is_transient_set( 'cfprop_abilities_hint' ) );

		// not if the abilities are disabled.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( Abilities_Settings::OPTION, 0 );
		Abilities_Settings::get_instance()->add_hint();
		$this->assertFalse( $transients->is_transient_set( 'cfprop_abilities_hint' ) );

		// once for administrators.
		update_option( Abilities_Settings::OPTION, 1 );
		Abilities_Settings::get_instance()->add_hint();
		$this->assertTrue( $transients->is_transient_set( 'cfprop_abilities_hint' ) );
		$this->assertStringContainsString( 'optional', $transients->get_transient_by_name( 'cfprop_abilities_hint' )->get_message() );
		$this->assertSame( 1, absint( get_option( Abilities_Settings::HINT_OPTION ) ) );

		// not again after it was removed.
		$transients->get_transient_by_name( 'cfprop_abilities_hint' )->delete();
		Abilities_Settings::get_instance()->add_hint();
		$this->assertFalse( $transients->is_transient_set( 'cfprop_abilities_hint' ) );
	}

	/**
	 * Test the state of templates saved via abilities in the lists of templates.
	 *
	 * @return void
	 */
	public function test_post_state(): void {
		$post_id = self::factory()->post->create();
		$post    = get_post( $post_id );
		$this->assertSame( array(), Abilities_Settings::get_instance()->add_post_state( array(), $post ) );

		update_post_meta( $post_id, 'propstackConnectorTemplate', 'single_basic' );
		$this->assertSame( array(), Abilities_Settings::get_instance()->add_post_state( array(), $post ) );

		foreach ( array( 'ai_single_template', 'ai_bricks_archive_template', 'ai_divi5_single_template' ) as $marker ) {
			update_post_meta( $post_id, 'propstackConnectorTemplate', $marker );
			$this->assertArrayHasKey( 'cfprop_abilities', Abilities_Settings::get_instance()->add_post_state( array(), $post ), $marker );
		}
	}
}

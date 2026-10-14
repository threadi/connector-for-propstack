<?php
/**
 * File for tests against the fixes in widgets, templates, blocks, filters and REST API.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Plugin\Templates as PluginTemplates;
use ConnectorForPropstack\Propstack\Taxonomies\Broker;
use ConnectorForPropstack\Propstack\Templates;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_REST_Request;

/**
 * Object for tests against the fixes in widgets, templates, blocks, filters and REST API.
 */
class FixesC extends ConnectorForPropstackTestCase {
	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Test that only known template names are allowed for the archive and single widgets.
	 *
	 * @return void
	 */
	public function test_only_allowed_template_names(): void {
		$names = Templates::get_instance()->get_allowed_template_names( array( 'title', 'ability_adapters', 'thumbnail', '../x' ) );

		$this->assertSame( array( 'title', 'thumbnail' ), $names );
	}

	/**
	 * Test that the archive shortcode does not run other hooks with our template prefix.
	 *
	 * @return void
	 */
	public function test_archive_shortcode_with_unknown_template_name(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name(),
				'post_status' => 'publish',
			)
		);

		// this must not run the filter "cfprop_template_ability_adapters" with the object as argument.
		$this->assertIsString( do_shortcode( '[cfprop_widget_archive templates="ability_adapters,title"]' ) );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test that the adapter hook tolerates invalid values.
	 *
	 * @return void
	 */
	public function test_add_template_adapter_with_invalid_value(): void {
		$adapters = \ConnectorForPropstack\PageBuilder\Gutenberg::get_instance()->add_template_adapter( 'invalid' );

		$this->assertCount( 1, $adapters );
	}

	/**
	 * Test the sanitizing of CSS declarations from blocks.
	 *
	 * @return void
	 */
	public function test_sanitize_css_declarations(): void {
		$this->assertSame( 'color: red; padding: 1em', PluginTemplates::sanitize_css_declarations( 'color: red; padding: 1em' ) );
		$this->assertSame( '', PluginTemplates::sanitize_css_declarations( 'color:red} body{display:none' ) );
		$this->assertSame( '', PluginTemplates::sanitize_css_declarations( 'color:red;@import url(x)' ) );
		$this->assertSame( '', PluginTemplates::sanitize_css_declarations( 'color:\\7d' ) );
		$this->assertSame( '', PluginTemplates::sanitize_css_declarations( 'color:red/*' ) );
	}

	/**
	 * Test the generated CSS rule for blocks.
	 *
	 * @return void
	 */
	public function test_get_block_css_rule(): void {
		$this->assertSame( '.cfprop-block-abc { color:red }', PluginTemplates::get_block_css_rule( 'cfprop-block-abc', 'color:red' ) );
		$this->assertSame( '', PluginTemplates::get_block_css_rule( '', 'color:red' ) );
		$this->assertSame( '', PluginTemplates::get_block_css_rule( 'cfprop-block-abc', false ) );
		$this->assertSame( '', PluginTemplates::get_block_css_rule( 'cfprop-block-abc', 'color:red}body{x:y' ) );
	}

	/**
	 * Test that form elements are only allowed with the dedicated list for filters and not for posts in general.
	 *
	 * @return void
	 */
	public function test_form_elements_are_not_allowed_in_posts(): void {
		$html = '<form action="https://example.com"><input type="password" name="pw"></form>';

		$this->assertStringNotContainsString( '<form', wp_kses_post( $html ) );
		$this->assertStringNotContainsString( '<input', wp_kses_post( $html ) );
		$this->assertStringContainsString( '<form', wp_kses( $html, PluginTemplates::get_instance()->get_allowed_filter_html() ) );
		$this->assertStringContainsString( '<input', wp_kses( $html, PluginTemplates::get_instance()->get_allowed_filter_html() ) );
	}

	/**
	 * Test that the taxonomy fields endpoint handles an array as query and does not list hidden fields.
	 *
	 * @return void
	 */
	public function test_taxonomy_fields_endpoint(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$route = '/connector-for-propstack/v1/' . Broker::get_instance()->get_name() . '-fields';

		// an array as query must not result in an error 500.
		$request = new WP_REST_Request( 'GET', $route );
		$request->set_query_params( array( 'query' => array( 'x' ) ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertNotEquals( 500, $response->get_status() );

		// hidden fields are not listed.
		$request  = new WP_REST_Request( 'GET', $route );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$names = wp_list_pluck( (array) $response->get_data(), 'value' );
		$this->assertNotContains( 'email', $names );
		$this->assertNotContains( 'cell', $names );
	}

	/**
	 * Test that custom template hooks (e.g., from the Pro plugin) are allowed, but not filters with our prefix.
	 *
	 * @return void
	 */
	public function test_custom_template_names_with_registered_hook(): void {
		$callback = '__return_null';
		add_action( 'cfprop_template_custom_test', $callback );
		add_filter( 'cfprop_template_ability_adapters', $callback );

		$names = Templates::get_instance()->get_allowed_template_names( array( 'title', 'custom_test', 'not_registered', 'ability_adapters', 'names', 'Custom_Test' ) );

		remove_action( 'cfprop_template_custom_test', $callback );
		remove_filter( 'cfprop_template_ability_adapters', $callback );

		$this->assertSame( array( 'title', 'custom_test' ), $names );
	}

	/**
	 * Test that the pagination of the archive could be enabled via filter and the page is used for the query.
	 *
	 * @return void
	 */
	public function test_archive_pagination_could_be_enabled(): void {
		$post_type = \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name();
		$post_ids  = self::factory()->post->create_many(
			12,
			array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
			)
		);

		add_filter( 'cfprop_archive_show_pagination', '__return_true' );

		// the pagination is shown on the first page.
		$output = do_shortcode( '[cfprop_widget_archive]' );
		$this->assertSame( 10, substr_count( $output, 'type-' . $post_type ) );
		$this->assertStringContainsString( 'cfprop-pagination', $output );

		// the second page shows the remaining objects.
		set_query_var( 'paged', 2 );
		$output = do_shortcode( '[cfprop_widget_archive]' );
		set_query_var( 'paged', 0 );
		$this->assertSame( 2, substr_count( $output, 'type-' . $post_type ) );

		remove_filter( 'cfprop_archive_show_pagination', '__return_true' );

		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Test that the main query of the object archive uses the same number of objects per page as the archive widget.
	 *
	 * @return void
	 */
	public function test_archive_main_query_uses_widget_posts_per_page(): void {
		$post_type = \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name();
		$post_ids  = self::factory()->post->create_many(
			12,
			array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
			)
		);

		// with this setting the second page would not exist without our change.
		update_option( 'posts_per_page', 20 );

		$this->go_to( add_query_arg( 'paged', 2, get_post_type_archive_link( $post_type ) ) );

		$this->assertFalse( is_404() );
		$this->assertSame( 10, absint( get_query_var( 'posts_per_page' ) ) );

		delete_option( 'posts_per_page' );

		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Test that the global kses extension is enabled if the Pro plugin seems to be active.
	 *
	 * @return void
	 */
	public function test_form_elements_are_allowed_in_posts_with_pro(): void {
		$html     = '<form action="https://example.com"><input type="text" name="x"></form>';
		$property = new \ReflectionProperty( PluginTemplates::class, 'global_kses_extension_needed' );

		// simulate the Pro plugin.
		add_filter( 'cfprop_hide_pro_hints', '__return_false' );
		$property->setValue( PluginTemplates::get_instance(), null );
		$output = wp_kses_post( $html );
		remove_filter( 'cfprop_hide_pro_hints', '__return_false' );
		$property->setValue( PluginTemplates::get_instance(), null );

		$this->assertStringContainsString( '<form', $output );
		$this->assertStringContainsString( '<input', $output );

		// without the Pro plugin it is disabled.
		$this->assertStringNotContainsString( '<form', wp_kses_post( $html ) );
	}

	/**
	 * Test that generated styles keep child combinators and drop CSS with comments.
	 *
	 * @return void
	 */
	public function test_add_styles(): void {
		$handle = \ConnectorForPropstack\Plugin\Helper::theme_is_fse_theme() ? 'wp-block-library' : 'cfprop-generated-styles';

		PluginTemplates::get_instance()->add_styles( array( 'styles' => '.cfprop-a > .cfprop-b { color: red; }' ) );
		PluginTemplates::get_instance()->add_styles( array( 'styles' => '.cfprop-c { color: blue; } /* .cfprop-d { color: green; }' ) );

		$styles = implode( '', (array) wp_styles()->get_data( $handle, 'after' ) );

		$this->assertStringContainsString( '.cfprop-a > .cfprop-b { color: red; }', $styles );
		$this->assertStringNotContainsString( 'cfprop-c', $styles );
	}
}

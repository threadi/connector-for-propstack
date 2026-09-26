<?php
/**
 * Tests for saving and resetting templates via abilities and for the output helpers for templates.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\PageBuilder\Gutenberg\Hide_Empty_Groups;
use ConnectorForPropstack\PageBuilder\Gutenberg\Template_Styles;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;
use WP_Post;

/**
 * Object to test saving and resetting of templates.
 */
class TemplateSaving extends ConnectorForPropstackTestCase {
	/**
	 * The ID of the test object.
	 *
	 * @var int
	 */
	private int $object_id = 0;

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

		// bail if the abilities API is not available.
		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'The abilities API is not available.' );
		}

		// register the post type for objects (the test suite resets post types after each test).
		ImmoObject::get_instance()->register();

		// use a block theme.
		$this->original_theme = get_stylesheet();
		switch_theme( 'twentytwentyfive' );

		// use an administrator (with the capability to edit CSS, which is only given to super admins in multisite).
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $user_id );
		}
		wp_set_current_user( $user_id );

		// create an object with a price, a balcony and no cellar.
		$this->object_id = self::factory()->post->create(
			array(
				'post_type'   => ImmoObject::get_instance()->get_name(),
				'post_status' => 'publish',
				'post_title'  => 'Test object for saving templates',
			)
		);
		update_post_meta( $this->object_id, 'price', 123456 );
		update_post_meta( $this->object_id, 'balcony', '1' );
		update_post_meta( $this->object_id, 'cellar', '' );

		// assign an object type, fields are only shown for objects with an object type.
		$taxonomy = \ConnectorForPropstack\Propstack\Taxonomies\ObjectType::get_instance()->get_name();
		$terms    = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);
		$term_id  = ( is_array( $terms ) && ! empty( $terms ) ) ? $terms[0]->term_id : self::factory()->term->create( array( 'taxonomy' => $taxonomy ) );
		wp_set_object_terms( $this->object_id, array( $term_id ), $taxonomy );

		// start without CSS.
		delete_option( Template_Styles::OPTION );
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
		delete_option( Template_Styles::OPTION );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Execute the given ability with the given input.
	 *
	 * @param string              $name  The name of the ability, without category.
	 * @param array<string,mixed> $input The input.
	 *
	 * @return mixed
	 */
	private function execute( string $name, array $input = array() ): mixed {
		$ability = wp_get_ability( 'connector-for-propstack/' . $name );
		$this->assertNotNull( $ability, 'The ability ' . $name . ' is not registered.' );
		return $ability->execute( $input );
	}

	/**
	 * Return a valid template for the detail view.
	 *
	 * @param string $marker A text to find the template again.
	 *
	 * @return string
	 */
	private function get_template( string $marker = 'Saved by test' ): string {
		return '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->'
			. '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group">'
			. '<!-- wp:post-title {"level":1} /-->'
			. '<!-- wp:paragraph --><p>' . $marker . '</p><!-- /wp:paragraph -->'
			. '<!-- wp:connector-for-propstack/field {"field_name":"price"} /-->'
			. '</div><!-- /wp:group -->'
			. '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
	}

	/**
	 * Return the customized template posts for the detail view.
	 *
	 * @return array<int,WP_Post>
	 */
	private function get_customized_posts(): array {
		$posts = get_posts(
			array(
				'post_type'   => 'wp_template',
				'post_status' => 'publish',
				'name'        => 'single-' . ImmoObject::get_instance()->get_name(),
				'numberposts' => -1,
			)
		);
		return array_values( array_filter( $posts, fn( $post ) => $post instanceof WP_Post ) );
	}

	/**
	 * Return a template with groups, which should be hidden if the blocks of this plugin inside them are empty.
	 *
	 * @param string $inner The inner blocks.
	 * @param string $class The class of the group.
	 * @param string $label The label to find the group.
	 *
	 * @return string
	 */
	private function get_group( string $inner, string $class, string $label ): string {
		return '<!-- wp:group {"className":"' . $class . '"} --><div class="wp-block-group ' . $class . '">'
			. '<!-- wp:paragraph --><p>' . $label . '</p><!-- /wp:paragraph -->'
			. $inner
			. '</div><!-- /wp:group -->';
	}

	/**
	 * Render the given template for the test object via the preview.
	 *
	 * @param string $content The template content.
	 *
	 * @return string
	 */
	private function render( string $content ): string {
		$result = $this->execute(
			'preview-template',
			array(
				'content' => $content,
				'post_id' => $this->object_id,
			)
		);
		$this->assertIsArray( $result );
		$this->assertEmpty( $result['errors'], implode( ' ', $result['errors'] ) );
		return $result['html'];
	}

	/**
	 * Test the registration and annotations of the new abilities.
	 *
	 * @return void
	 */
	public function test_abilities_are_registered(): void {
		$save  = wp_get_ability( 'connector-for-propstack/save-template' );
		$reset = wp_get_ability( 'connector-for-propstack/reset-template' );
		$this->assertNotNull( $save );
		$this->assertNotNull( $reset );

		// saving keeps a revision, resetting removes the customized template.
		$this->assertFalse( $save->get_meta_item( 'annotations' )['readonly'] );
		$this->assertFalse( $save->get_meta_item( 'annotations' )['destructive'] );
		$this->assertFalse( $reset->get_meta_item( 'annotations' )['readonly'] );
		$this->assertTrue( $reset->get_meta_item( 'annotations' )['destructive'] );

		// both are available via MCP.
		$this->assertTrue( $save->get_meta_item( 'mcp' )['public'] );
		$this->assertTrue( $reset->get_meta_item( 'mcp' )['public'] );

		// the page builder says that it can save.
		$builders = array_column( $this->execute( 'get-builders' )['builders'], null, 'name' );
		$this->assertTrue( $builders['gutenberg']['can_save'] );
	}

	/**
	 * Test that saving is only a dry run by default.
	 *
	 * @return void
	 */
	public function test_save_is_dry_run_by_default(): void {
		$result = $this->execute( 'save-template', array( 'content' => $this->get_template() ) );

		$this->assertTrue( $result['dry_run'] );
		$this->assertFalse( $result['done'] );
		$this->assertSame( 'create', $result['action'] );
		$this->assertEmpty( $result['errors'] );
		$this->assertEmpty( $this->get_customized_posts() );
		$this->assertFalse( $this->execute( 'get-template' )['is_customized'] );
	}

	/**
	 * Test saving a new customized template with CSS.
	 *
	 * @return void
	 */
	public function test_save_creates_template(): void {
		$result = $this->execute(
			'save-template',
			array(
				'content' => $this->get_template(),
				'css'     => '.my-object { color: red; }',
				'dry_run' => false,
			)
		);

		$this->assertTrue( $result['done'], implode( ' ', $result['errors'] ) );
		$this->assertSame( 'create', $result['action'] );
		$this->assertGreaterThan( 0, $result['id'] );

		// the template is used now.
		$template = $this->execute( 'get-template' );
		$this->assertTrue( $template['is_customized'] );
		$this->assertStringContainsString( 'Saved by test', $template['content'] );
		$this->assertSame( '.my-object { color: red; }', $template['css'] );

		// it is found by WordPress as template for the detail view.
		$found = false;
		foreach ( get_block_templates( array( 'slug__in' => array( 'single-' . ImmoObject::get_instance()->get_name() ) ), 'wp_template' ) as $block_template ) {
			if ( str_contains( $block_template->content, 'Saved by test' ) ) {
				$found = true;
			}
		}
		$this->assertTrue( $found );
	}

	/**
	 * Test that saving again updates the customized template and keeps the previous version as revision.
	 *
	 * @return void
	 */
	public function test_save_updates_template(): void {
		$first = $this->execute(
			'save-template',
			array(
				'content' => $this->get_template( 'First version' ),
				'dry_run' => false,
			)
		);

		// the dry run tells that the template would be updated.
		$dry = $this->execute( 'save-template', array( 'content' => $this->get_template( 'Second version' ) ) );
		$this->assertSame( 'update', $dry['action'] );
		$this->assertSame( $first['id'], $dry['id'] );

		// save the second version.
		$second = $this->execute(
			'save-template',
			array(
				'content' => $this->get_template( 'Second version' ),
				'dry_run' => false,
			)
		);
		$this->assertSame( 'update', $second['action'] );
		$this->assertSame( $first['id'], $second['id'] );
		$this->assertCount( 1, $this->get_customized_posts() );
		$this->assertStringContainsString( 'Second version', $this->execute( 'get-template' )['content'] );

		// the first version is kept as revision.
		$revisions = wp_get_post_revisions( $first['id'] );
		$contents  = implode( ' ', wp_list_pluck( $revisions, 'post_content' ) );
		$this->assertStringContainsString( 'First version', $contents );
	}

	/**
	 * Test that the CSS is kept if it is omitted and removed with an empty string.
	 *
	 * @return void
	 */
	public function test_save_keeps_or_removes_css(): void {
		$this->execute(
			'save-template',
			array(
				'content' => $this->get_template(),
				'css'     => '.a { color: red; }',
				'dry_run' => false,
			)
		);

		// omitted: the CSS is kept.
		$this->execute(
			'save-template',
			array(
				'content' => $this->get_template( 'Again' ),
				'dry_run' => false,
			)
		);
		$this->assertSame( '.a { color: red; }', $this->execute( 'get-template' )['css'] );

		// empty: the CSS is removed.
		$this->execute(
			'save-template',
			array(
				'content' => $this->get_template( 'Again' ),
				'css'     => '',
				'dry_run' => false,
			)
		);
		$this->assertSame( '', $this->execute( 'get-template' )['css'] );
		$this->assertFalse( get_option( Template_Styles::OPTION ) );
	}

	/**
	 * Test that templates with errors are not saved.
	 *
	 * @return void
	 */
	public function test_save_rejects_invalid_template(): void {
		$result = $this->execute(
			'save-template',
			array(
				'content' => '<!-- wp:connector-for-propstack/unknown /-->',
				'dry_run' => false,
			)
		);

		$this->assertFalse( $result['done'] );
		$this->assertNotEmpty( $result['errors'] );
		$this->assertEmpty( $this->get_customized_posts() );
	}

	/**
	 * Test that CSS can not be saved without the capability to edit CSS.
	 *
	 * @return void
	 */
	public function test_save_css_requires_capability(): void {
		$deny = function ( array $caps, string $cap ): array {
			if ( 'edit_css' === $cap ) {
				return array( 'do_not_allow' );
			}
			return $caps;
		};
		add_filter( 'map_meta_cap', $deny, 10, 2 );

		$result = $this->execute(
			'save-template',
			array(
				'content' => $this->get_template(),
				'css'     => '.a { color: red; }',
				'dry_run' => false,
			)
		);

		remove_filter( 'map_meta_cap', $deny );

		$this->assertFalse( $result['done'] );
		$this->assertNotEmpty( $result['errors'] );
		$this->assertEmpty( $this->get_customized_posts() );
		$this->assertFalse( get_option( Template_Styles::OPTION ) );
	}

	/**
	 * Test that the CSS can not end the style element.
	 *
	 * @return void
	 */
	public function test_css_is_sanitized(): void {
		$this->execute(
			'save-template',
			array(
				'content' => $this->get_template(),
				'css'     => '.a { color: red; }</style><script>alert(1)</script>',
				'dry_run' => false,
			)
		);

		$css = Template_Styles::get_instance()->get_css( 'single' );
		$this->assertStringNotContainsString( '<', $css );
		$this->assertStringContainsString( '.a { color: red; }', $css );
	}

	/**
	 * Test that the CSS is only loaded on the pages of the template.
	 *
	 * @return void
	 */
	public function test_css_is_loaded_on_detail_view_only(): void {
		Template_Styles::get_instance()->set_css( 'single', '.only-on-single { color: red; }' );

		// not on the home page.
		$this->go_to( home_url( '/' ) );
		Template_Styles::get_instance()->add_styles();
		$this->assertFalse( wp_style_is( 'cfprop-template', 'enqueued' ) );

		// but on the detail view of an object.
		$this->go_to( get_permalink( $this->object_id ) );
		Template_Styles::get_instance()->add_styles();
		$this->assertTrue( wp_style_is( 'cfprop-template', 'enqueued' ) );
		$this->assertStringContainsString( '.only-on-single', implode( ' ', (array) wp_styles()->get_data( 'cfprop-template', 'after' ) ) );
	}

	/**
	 * Test resetting a customized template.
	 *
	 * @return void
	 */
	public function test_reset_template(): void {
		$saved = $this->execute(
			'save-template',
			array(
				'content' => $this->get_template(),
				'css'     => '.a { color: red; }',
				'dry_run' => false,
			)
		);

		// the dry run changes nothing.
		$dry = $this->execute( 'reset-template' );
		$this->assertTrue( $dry['dry_run'] );
		$this->assertSame( 'reset', $dry['action'] );
		$this->assertSame( $saved['id'], $dry['id'] );
		$this->assertTrue( $this->execute( 'get-template' )['is_customized'] );

		// reset.
		$result = $this->execute( 'reset-template', array( 'dry_run' => false ) );
		$this->assertTrue( $result['done'] );
		$this->assertSame( 'trash', get_post_status( $saved['id'] ) );
		$template = $this->execute( 'get-template' );
		$this->assertFalse( $template['is_customized'] );
		$this->assertStringNotContainsString( 'Saved by test', $template['content'] );
		$this->assertSame( '', $template['css'] );

		// nothing left to reset.
		$again = $this->execute( 'reset-template', array( 'dry_run' => false ) );
		$this->assertSame( 'none', $again['action'] );
		$this->assertFalse( $again['done'] );
	}

	/**
	 * Test that saving and resetting require the capability to edit the theme.
	 *
	 * @return void
	 */
	public function test_permissions(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertInstanceOf( WP_Error::class, $this->execute( 'save-template', array( 'content' => $this->get_template() ) ) );
		$this->assertInstanceOf( WP_Error::class, $this->execute( 'reset-template' ) );
		$this->assertEmpty( $this->get_customized_posts() );
	}

	/**
	 * Test that templates can not be saved with a classic theme.
	 *
	 * @return void
	 */
	public function test_save_with_classic_theme(): void {
		switch_theme( 'twentytwentyone' );

		$result = $this->execute(
			'save-template',
			array(
				'builder' => 'gutenberg',
				'content' => $this->get_template(),
				'dry_run' => false,
			)
		);

		// the page builder is not available, so the ability returns an error.
		$this->assertTrue( $result instanceof WP_Error || ( is_array( $result ) && ! empty( $result['errors'] ) ) );
		$this->assertEmpty( $this->get_customized_posts() );
	}

	/**
	 * Test that empty fields are still output as empty element, as the CSS of the field block hides groups with it.
	 *
	 * @return void
	 */
	public function test_empty_field_outputs_empty_element(): void {
		$html = $this->render( '<!-- wp:connector-for-propstack/field {"field_name":"base_rent"} /-->' );
		$this->assertMatchesRegularExpression( '/<p class="cfprop-field cfprop-field-base_rent[^"]*"><\/p>/', $html );

		// a filled field has content.
		$html = $this->render( '<!-- wp:connector-for-propstack/field {"field_name":"price"} /-->' );
		$this->assertMatchesRegularExpression( '/<p class="cfprop-field cfprop-field-price[^"]*">[^<]+<\/p>/', $html );
	}

	/**
	 * Test that the preview shows no placeholders for empty fields, even if placeholders are enabled (e.g. in REST requests).
	 *
	 * @return void
	 */
	public function test_preview_shows_no_placeholders(): void {
		add_filter( 'cfprop_show_empty_placeholders', '__return_true' );
		$html = $this->render( '<!-- wp:connector-for-propstack/field {"field_name":"base_rent"} /-->' );
		remove_filter( 'cfprop_show_empty_placeholders', '__return_true' );

		$this->assertStringNotContainsString( 'Empty field.', $html );
	}

	/**
	 * Test the output of yes/no fields as icons.
	 *
	 * @return void
	 */
	public function test_boolean_output(): void {
		$html = $this->render( '<!-- wp:connector-for-propstack/field {"field_name":"balcony"} /--><!-- wp:connector-for-propstack/field {"field_name":"cellar"} /-->' );

		$this->assertStringContainsString( '<span class="dashicons dashicons-yes"></span>', $html );
		$this->assertStringContainsString( '<span class="dashicons dashicons-no"></span>', $html );
	}

	/**
	 * Test that groups with the class to hide them are hidden if the blocks of this plugin in them are empty.
	 *
	 * @return void
	 */
	public function test_hide_empty_groups(): void {
		$html = $this->render(
			$this->get_group( '<!-- wp:connector-for-propstack/field {"field_name":"price"} /-->', Hide_Empty_Groups::CLASS_EMPTY, 'Label price' )
			. $this->get_group( '<!-- wp:connector-for-propstack/field {"field_name":"base_rent"} /-->', Hide_Empty_Groups::CLASS_EMPTY, 'Label base rent' )
			. $this->get_group( '<!-- wp:connector-for-propstack/field {"field_name":"base_rent"} /-->', 'other-class', 'Label without hiding' )
			. $this->get_group( '<!-- wp:paragraph --><p>Only text</p><!-- /wp:paragraph -->', Hide_Empty_Groups::CLASS_EMPTY, 'Label without plugin blocks' )
		);

		$this->assertStringContainsString( 'Label price', $html );
		$this->assertStringNotContainsString( 'Label base rent', $html );
		$this->assertStringContainsString( 'Label without hiding', $html );
		$this->assertStringContainsString( 'Label without plugin blocks', $html );
	}

	/**
	 * Test that "no" values only hide groups with the class for it.
	 *
	 * @return void
	 */
	public function test_hide_no_groups(): void {
		$html = $this->render(
			$this->get_group( '<!-- wp:connector-for-propstack/field {"field_name":"balcony"} /-->', Hide_Empty_Groups::CLASS_NO, 'Label balcony' )
			. $this->get_group( '<!-- wp:connector-for-propstack/field {"field_name":"cellar"} /-->', Hide_Empty_Groups::CLASS_NO, 'Label cellar hidden' )
			. $this->get_group( '<!-- wp:connector-for-propstack/field {"field_name":"cellar"} /-->', Hide_Empty_Groups::CLASS_EMPTY, 'Label cellar shown' )
		);

		$this->assertStringContainsString( 'Label balcony', $html );
		$this->assertStringNotContainsString( 'Label cellar hidden', $html );
		$this->assertStringContainsString( 'Label cellar shown', $html );
	}

	/**
	 * Test nested groups: a section is hidden if all its rows are hidden.
	 *
	 * @return void
	 */
	public function test_hide_nested_groups(): void {
		$empty_rows = $this->get_group( '<!-- wp:connector-for-propstack/field {"field_name":"base_rent"} /-->', Hide_Empty_Groups::CLASS_EMPTY, 'Row base rent' )
			. $this->get_group( '<!-- wp:connector-for-propstack/field {"field_name":"cellar"} /-->', Hide_Empty_Groups::CLASS_NO, 'Row cellar' );
		$filled_rows = $empty_rows . $this->get_group( '<!-- wp:connector-for-propstack/field {"field_name":"price"} /-->', Hide_Empty_Groups::CLASS_EMPTY, 'Row price' );

		$html = $this->render(
			$this->get_group( $empty_rows, Hide_Empty_Groups::CLASS_EMPTY, 'Section empty' )
			. $this->get_group( $filled_rows, Hide_Empty_Groups::CLASS_EMPTY, 'Section filled' )
		);

		$this->assertStringNotContainsString( 'Section empty', $html );
		$this->assertStringContainsString( 'Section filled', $html );
		$this->assertStringContainsString( 'Row price', $html );
		$this->assertStringNotContainsString( 'Row base rent', $html );
		$this->assertStringNotContainsString( 'Row cellar', $html );
	}

	/**
	 * Test that the catalog explains the new features.
	 *
	 * @return void
	 */
	public function test_catalog_hints(): void {
		$hints = implode( ' ', $this->execute( 'get-template-catalog' )['hints'] );

		$this->assertStringContainsString( Hide_Empty_Groups::CLASS_EMPTY, $hints );
		$this->assertStringContainsString( Hide_Empty_Groups::CLASS_NO, $hints );
		$this->assertStringContainsString( 'dashicons-yes', $hints );
		$this->assertStringContainsString( 'save-template', $hints );
		$this->assertStringContainsString( 'dry_run', $hints );
	}

	/**
	 * Test that the example in the hints is a valid template part.
	 *
	 * @return void
	 */
	public function test_catalog_hint_example_is_valid(): void {
		foreach ( $this->execute( 'get-template-catalog' )['hints'] as $hint ) {
			if ( ! preg_match( '/(<!-- wp:group.*<!-- \/wp:group -->)/s', $hint, $matches ) ) {
				continue;
			}
			$result = $this->execute(
				'preview-template',
				array(
					'content' => $matches[1],
					'render'  => false,
				)
			);
			$this->assertEmpty( $result['errors'], implode( ' ', $result['errors'] ) );
			return;
		}
		$this->fail( 'No example found in the hints.' );
	}
}

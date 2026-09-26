<?php
/**
 * Tests for the abilities to work with templates of page builders.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use ConnectorForPropstack\Propstack\Template_Abilities;
use ConnectorForPropstack\Propstack\Template_Adapter_Base;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;

/**
 * Object to test the abilities for templates.
 */
class TemplateAbilities extends ConnectorForPropstackTestCase {
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

		// use an administrator.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// create an object.
		$this->object_id = self::factory()->post->create(
			array(
				'post_type'   => ImmoObject::get_instance()->get_name(),
				'post_status' => 'publish',
				'post_title'  => 'Test object for templates',
			)
		);
		update_post_meta( $this->object_id, 'object_id', '4711' );
		update_post_meta( $this->object_id, 'price', 123456 );

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
	 * @return string
	 */
	private function get_valid_single_template(): string {
		return '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->'
			. '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group">'
			. '<!-- wp:post-title {"level":1} /-->'
			. '<!-- wp:post-terms {"term":"cfprop_object_type"} /-->'
			. '<!-- wp:connector-for-propstack/field {"field_name":"price"} /-->'
			. '<!-- wp:connector-for-propstack/description {"description_type":"description_note"} /-->'
			. '</div><!-- /wp:group -->'
			. '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
	}

	/**
	 * Test that all abilities are registered.
	 *
	 * @return void
	 */
	public function test_abilities_are_registered(): void {
		foreach ( array( 'get-builders', 'get-template-catalog', 'get-template', 'preview-template' ) as $name ) {
			$ability = wp_get_ability( 'connector-for-propstack/' . $name );
			$this->assertNotNull( $ability, $name );
			$this->assertFalse( $ability->get_meta_item( 'annotations' )['destructive'], $name );
		}

		// the preview is not marked as readonly, so it can be called via POST with long content.
		$this->assertFalse( wp_get_ability( 'connector-for-propstack/preview-template' )->get_meta_item( 'annotations' )['readonly'] );
		$this->assertTrue( wp_get_ability( 'connector-for-propstack/get-template' )->get_meta_item( 'annotations' )['readonly'] );
	}

	/**
	 * Test that the input schemas of all abilities of this plugin are valid for AI APIs.
	 *
	 * The properties must be sent as JSON object, an empty PHP array would be sent as JSON array ("[]"),
	 * which is rejected e.g. by the Anthropic API.
	 *
	 * @return void
	 */
	public function test_input_schemas_are_valid_for_ai_apis(): void {
		$count = 0;
		foreach ( wp_get_abilities() as $ability ) {
			if ( ! str_starts_with( $ability->get_name(), 'connector-for-propstack/' ) ) {
				continue;
			}
			++$count;

			$schema = $ability->get_input_schema();
			if ( empty( $schema ) ) {
				continue;
			}
			$this->assertSame( 'object', $schema['type'] ?? '', $ability->get_name() );
			if ( array_key_exists( 'properties', $schema ) ) {
				$this->assertStringStartsWith( '{', (string) wp_json_encode( $schema['properties'] ), $ability->get_name() );
			}
		}
		$this->assertGreaterThanOrEqual( 10, $count );

		// abilities without input can be executed without input and with an empty input, as sent by AI clients.
		foreach ( array( 'get-builders', 'get-import-status' ) as $name ) {
			$this->assertIsArray( wp_get_ability( 'connector-for-propstack/' . $name )->execute(), $name );
			$this->assertIsArray( wp_get_ability( 'connector-for-propstack/' . $name )->execute( array() ), $name );
		}
	}

	/**
	 * Test that our templates are not listed as template parts.
	 *
	 * @return void
	 */
	public function test_templates_are_not_template_parts(): void {
		$slugs = wp_list_pluck( get_block_templates( array(), 'wp_template_part' ), 'slug' );
		$this->assertNotContains( 'single-' . ImmoObject::get_instance()->get_name(), $slugs );
		$this->assertNotContains( 'archive-' . ImmoObject::get_instance()->get_name(), $slugs );

		// but as templates.
		$slugs = wp_list_pluck( get_block_templates( array(), 'wp_template' ), 'slug' );
		$this->assertContains( 'single-' . ImmoObject::get_instance()->get_name(), $slugs );
	}

	/**
	 * Test that the block editor is listed as available page builder with a block theme.
	 *
	 * @return void
	 */
	public function test_get_builders_with_block_theme(): void {
		$result   = $this->execute( 'get-builders' );
		$builders = array_column( $result['builders'], null, 'name' );

		$this->assertArrayHasKey( 'gutenberg', $builders );
		$this->assertTrue( $builders['gutenberg']['available'] );
		$this->assertArrayHasKey( 'single', $builders['gutenberg']['template_types'] );
		$this->assertArrayHasKey( 'archive', $builders['gutenberg']['template_types'] );
	}

	/**
	 * Test that the block editor is not available with a classic theme.
	 *
	 * @return void
	 */
	public function test_get_builders_with_classic_theme(): void {
		switch_theme( 'twentytwentyone' );

		$result   = $this->execute( 'get-builders' );
		$builders = array_column( $result['builders'], null, 'name' );
		$this->assertFalse( $builders['gutenberg']['available'] );
		$this->assertNotEmpty( $builders['gutenberg']['reason'] );

		// the other abilities return an error.
		$this->assertInstanceOf( WP_Error::class, $this->execute( 'get-template-catalog' ) );
		$this->assertInstanceOf( WP_Error::class, $this->execute( 'get-template', array( 'builder' => 'gutenberg' ) ) );
	}

	/**
	 * Test the catalog for the block editor.
	 *
	 * @return void
	 */
	public function test_catalog(): void {
		$catalog = $this->execute( 'get-template-catalog', array( 'builder' => 'gutenberg' ) );

		$this->assertIsArray( $catalog );
		$this->assertSame( 'gutenberg', $catalog['builder'] );
		$this->assertNotEmpty( $catalog['format'] );

		// our blocks are listed with examples and without internal attributes.
		$elements = array_column( $catalog['elements'], null, 'name' );
		foreach ( array( 'field', 'broker-field', 'description', 'gallery', 'energy-scale', 'archive', 'filter' ) as $block ) {
			$this->assertArrayHasKey( 'connector-for-propstack/' . $block, $elements, $block );
			$this->assertStringContainsString( 'wp:connector-for-propstack/' . $block, $elements[ 'connector-for-propstack/' . $block ]['example'] );
		}
		$this->assertArrayHasKey( 'field_name', $elements['connector-for-propstack/field']['attributes'] );
		$this->assertArrayNotHasKey( 'blockId', $elements['connector-for-propstack/field']['attributes'] );
		$this->assertArrayNotHasKey( 'id', $elements['connector-for-propstack/field']['attributes'] );
		$this->assertArrayHasKey( 'core/post-terms', $elements );

		// the allowed values are listed.
		$this->assertContains( 'price', array_column( $catalog['field_names'], 'name' ) );
		$this->assertNotContains( 'api_response', array_column( $catalog['field_names'], 'name' ) );
		$this->assertContains( 'description_note', array_column( $catalog['description_types'], 'name' ) );
		$this->assertNotEmpty( $catalog['broker_fields'] );
		$this->assertNotContains( 'cell', array_column( $catalog['broker_fields'], 'name' ) );
		$this->assertContains( 'cfprop_object_type', array_column( $catalog['taxonomies'], 'name' ) );

		// the hints contain the template parts of the theme.
		$this->assertStringContainsString( 'header', implode( ' ', $catalog['hints'] ) );
	}

	/**
	 * Test that the examples of the catalog are valid.
	 *
	 * @return void
	 */
	public function test_catalog_examples_are_valid(): void {
		$catalog = $this->execute( 'get-template-catalog' );
		foreach ( $catalog['elements'] as $element ) {
			$result = $this->execute(
				'preview-template',
				array(
					'content' => $element['example'],
					'render'  => false,
				)
			);
			$this->assertSame( array(), $result['errors'], $element['name'] . ': ' . implode( ' ', $result['errors'] ) );
		}
	}

	/**
	 * Test the template of the plugin.
	 *
	 * @return void
	 */
	public function test_get_template_from_plugin(): void {
		$result = $this->execute( 'get-template', array( 'type' => 'single' ) );

		$this->assertIsArray( $result );
		$this->assertSame( 'single', $result['type'] );
		$this->assertFalse( $result['is_customized'] );
		$this->assertStringContainsString( 'wp:template-part', $result['content'] );

		// the archive template is available, too.
		$result = $this->execute( 'get-template', array( 'type' => 'archive' ) );
		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'wp:', $result['content'] );
	}

	/**
	 * Test that the templates of the plugin pass the validation.
	 *
	 * @return void
	 */
	public function test_plugin_templates_are_valid(): void {
		foreach ( array( 'single', 'archive' ) as $type ) {
			$template = $this->execute( 'get-template', array( 'type' => $type ) );
			$result   = $this->execute(
				'preview-template',
				array(
					'type'    => $type,
					'content' => $template['content'],
				)
			);
			$this->assertSame( array(), $result['errors'], $type . ': ' . implode( ' ', $result['errors'] ) );
			$this->assertNotEmpty( $result['html'], $type );
		}
	}

	/**
	 * Test that a customized template is preferred.
	 *
	 * @return void
	 */
	public function test_get_customized_template(): void {
		$slug    = 'single-' . ImmoObject::get_instance()->get_name();
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'wp_template',
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:post-title /--><!-- wp:paragraph --><p>Customized</p><!-- /wp:paragraph -->',
			)
		);
		wp_set_post_terms( $post_id, get_stylesheet(), 'wp_theme' );

		$result = $this->execute( 'get-template', array( 'type' => 'single' ) );
		$this->assertTrue( $result['is_customized'] );
		$this->assertStringContainsString( 'Customized', $result['content'] );
	}

	/**
	 * Test that an unknown template type is rejected.
	 *
	 * @return void
	 */
	public function test_unknown_template_type(): void {
		$result = Template_Abilities::get_instance()->get_template( array( 'type' => 'page' ) );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test the preview of a valid template.
	 *
	 * @return void
	 */
	public function test_preview_valid_template(): void {
		$result = $this->execute(
			'preview-template',
			array(
				'content' => $this->get_valid_single_template(),
				'post_id' => $this->object_id,
			)
		);

		$this->assertTrue( $result['valid'], implode( ' ', $result['errors'] ) );
		$this->assertSame( array(), $result['errors'] );
		$this->assertSame( $this->object_id, $result['post_id'] );
		$this->assertStringContainsString( 'Test object for templates', $result['html'] );
		$this->assertMatchesRegularExpression( '/123[.,]?456/', $result['html'] );
		$this->assertFalse( $result['truncated'] );
	}

	/**
	 * Test that the newest object is used if no object is requested.
	 *
	 * @return void
	 */
	public function test_preview_uses_newest_object(): void {
		$result = $this->execute( 'preview-template', array( 'content' => '<!-- wp:post-title /-->' ) );
		$this->assertGreaterThan( 0, $result['post_id'] );
		$this->assertSame( ImmoObject::get_instance()->get_name(), get_post_type( $result['post_id'] ) );
	}

	/**
	 * Test that the global post is restored after the preview.
	 *
	 * @return void
	 */
	public function test_preview_restores_global_post(): void {
		global $post;
		$other_post = self::factory()->post->create_and_get();
		$post       = $other_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->execute(
			'preview-template',
			array(
				'content' => '<!-- wp:post-title /-->',
				'post_id' => $this->object_id,
			)
		);

		$this->assertSame( $other_post->ID, $post->ID );
	}

	/**
	 * Test that the validation finds errors.
	 *
	 * @return void
	 */
	public function test_preview_finds_errors(): void {
		$cases = array(
			'<!-- wp:connector-for-propstack/unknown /-->' => 'unknown',
			'<!-- wp:connector-for-propstack/field {"field_name":"no_such_field"} /-->' => 'no_such_field',
			'<!-- wp:connector-for-propstack/field /-->'   => 'field_name',
			'<!-- wp:connector-for-propstack/field {"field_name":"api_response"} /-->' => 'api_response',
			'<!-- wp:connector-for-propstack/broker-field {"field_name":"cell"} /-->' => 'cell',
			'<!-- wp:connector-for-propstack/description {"description_type":"price"} /-->' => 'price',
			'<!-- wp:post-terms {"term":"no_such_taxonomy"} /-->' => 'no_such_taxonomy',
			'<!-- wp:connector-for-propstack/field {"field_name":"price" /-->' => 'invalid block markup',
		);
		foreach ( $cases as $content => $expected ) {
			$result = $this->execute(
				'preview-template',
				array(
					'content' => $content,
					'render'  => false,
				)
			);
			$this->assertFalse( $result['valid'], $content );
			$this->assertStringContainsString( $expected, implode( ' ', $result['errors'] ), $content );
		}
	}

	/**
	 * Test that block bindings to fields of objects are detected, as they show nothing.
	 *
	 * This is how an AI built a first draft without knowing the blocks of this plugin.
	 *
	 * @return void
	 */
	public function test_preview_detects_meta_bindings(): void {
		$draft = '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->'
			. '<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} --><main class="wp-block-group">'
			. '<!-- wp:post-title {"level":1} /-->'
			. '<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"price"}}}}} --><p></p><!-- /wp:paragraph -->'
			. '<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"no_such_meta"}}}}} --><p></p><!-- /wp:paragraph -->'
			. '</main><!-- /wp:group -->'
			. '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';

		$result = $this->execute(
			'preview-template',
			array(
				'content' => $draft,
				'render'  => false,
			)
		);

		$this->assertFalse( $result['valid'] );
		$errors = implode( ' ', $result['errors'] );
		$this->assertStringContainsString( '{"field_name":"price"}', $errors );
		$this->assertStringContainsString( 'no_such_meta', $errors );

		// the template uses no block of this plugin.
		$this->assertStringContainsString( 'uses no block of this plugin', implode( ' ', $result['warnings'] ) );
	}

	/**
	 * Test that block bindings to meta fields, which are available for block bindings, are allowed.
	 *
	 * @return void
	 */
	public function test_preview_allows_available_meta_bindings(): void {
		register_post_meta(
			ImmoObject::get_instance()->get_name(),
			'cfprop_test_binding',
			array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
			)
		);

		$result = $this->execute(
			'preview-template',
			array(
				'content' => '<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"cfprop_test_binding"}}}}} --><p></p><!-- /wp:paragraph -->',
				'render'  => false,
			)
		);
		$this->assertTrue( $result['valid'], implode( ' ', $result['errors'] ) );

		unregister_post_meta( ImmoObject::get_instance()->get_name(), 'cfprop_test_binding' );
	}

	/**
	 * Test that the abilities for object data point to the template abilities.
	 *
	 * @return void
	 */
	public function test_object_abilities_point_to_template_abilities(): void {
		foreach ( array( 'get-objects', 'get-object', 'get-fields' ) as $name ) {
			$this->assertStringContainsString( 'get-template-catalog', wp_get_ability( 'connector-for-propstack/' . $name )->get_description(), $name );
		}

		// and the catalog warns about block bindings.
		$catalog = $this->execute( 'get-template-catalog' );
		$this->assertStringContainsString( 'block bindings', implode( ' ', $catalog['hints'] ) );
	}

	/**
	 * Test that errors in inner blocks are found.
	 *
	 * @return void
	 */
	public function test_preview_finds_errors_in_inner_blocks(): void {
		$result = $this->execute(
			'preview-template',
			array(
				'content' => '<!-- wp:group --><div class="wp-block-group"><!-- wp:connector-for-propstack/field {"field_name":"no_such_field"} /--></div><!-- /wp:group -->',
				'render'  => false,
			)
		);
		$this->assertFalse( $result['valid'] );
	}

	/**
	 * Test that the validation returns warnings.
	 *
	 * @return void
	 */
	public function test_preview_warnings(): void {
		// missing template part and content outside of blocks.
		$result = $this->execute(
			'preview-template',
			array(
				'content' => '<p>Hello</p><!-- wp:template-part {"slug":"no-such-part"} /-->',
				'render'  => false,
			)
		);
		$this->assertTrue( $result['valid'] );
		$warnings = implode( ' ', $result['warnings'] );
		$this->assertStringContainsString( 'no-such-part', $warnings );
		$this->assertStringContainsString( 'outside of blocks', $warnings );

		// an archive template without a list of objects.
		$result = $this->execute(
			'preview-template',
			array(
				'type'    => 'archive',
				'content' => '<!-- wp:template-part {"slug":"header"} /-->',
				'render'  => false,
			)
		);
		$this->assertStringContainsString( 'connector-for-propstack/archive', implode( ' ', $result['warnings'] ) );
	}

	/**
	 * Test that nothing is rendered if not requested.
	 *
	 * @return void
	 */
	public function test_preview_without_render(): void {
		$result = $this->execute(
			'preview-template',
			array(
				'content' => $this->get_valid_single_template(),
				'render'  => false,
			)
		);
		$this->assertTrue( $result['valid'] );
		$this->assertSame( '', $result['html'] );
	}

	/**
	 * Test the preview of an archive template.
	 *
	 * @return void
	 */
	public function test_preview_archive_template(): void {
		$result = $this->execute(
			'preview-template',
			array(
				'type'    => 'archive',
				'content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:connector-for-propstack/archive /-->',
			)
		);
		$this->assertTrue( $result['valid'], implode( ' ', $result['errors'] ) );
		$this->assertStringContainsString( 'Test object for templates', $result['html'] );
	}

	/**
	 * Test that the preview only accepts objects.
	 *
	 * @return void
	 */
	public function test_preview_with_other_post(): void {
		$result = $this->execute(
			'preview-template',
			array(
				'content' => '<!-- wp:post-title /-->',
				'post_id' => self::factory()->post->create(),
			)
		);
		$this->assertFalse( $result['valid'] );
		$this->assertSame( '', $result['html'] );
	}

	/**
	 * Test the permissions.
	 *
	 * @return void
	 */
	public function test_permissions(): void {
		// an editor can read the catalog, but not the templates.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertIsArray( $this->execute( 'get-template-catalog' ) );
		$this->assertInstanceOf( WP_Error::class, $this->execute( 'get-template' ) );
		$this->assertInstanceOf( WP_Error::class, $this->execute( 'preview-template', array( 'content' => '<!-- wp:post-title /-->' ) ) );

		// a subscriber can use none of them.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertInstanceOf( WP_Error::class, $this->execute( 'get-builders' ) );
		$this->assertInstanceOf( WP_Error::class, $this->execute( 'get-template-catalog' ) );
	}

	/**
	 * Test adapters added via the filter, e.g. by the Pro plugin.
	 *
	 * @return void
	 */
	public function test_additional_adapter(): void {
		$adapter = new class() extends Template_Adapter_Base {
			/**
			 * Return the name.
			 *
			 * @return string
			 */
			public function get_name(): string {
				return 'testbuilder';
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
			 * Return whether it is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Return the format.
			 *
			 * @return string
			 */
			public function get_format(): string {
				return 'JSON';
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
			 * @param string $type The type.
			 *
			 * @return array<string,mixed>|WP_Error
			 */
			public function get_template( string $type ): array|WP_Error {
				return array(
					'id'            => 'test',
					'source'        => 'test',
					'is_customized' => false,
					'content'       => '{}',
				);
			}

			/**
			 * Validate the content.
			 *
			 * @param string $type    The type.
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
			 * Render the content.
			 *
			 * @param string $type    The type.
			 * @param string $content The content.
			 * @param int    $post_id The post-ID.
			 *
			 * @return string|WP_Error
			 */
			public function render( string $type, string $content, int $post_id ): string|WP_Error {
				return 'rendered ' . $post_id;
			}
		};

		$callback = function ( array $adapters ) use ( $adapter ) {
			$adapters[] = $adapter;
			$adapters[] = 'invalid';
			return $adapters;
		};
		add_filter( 'cfprop_template_ability_adapters', $callback );

		// the adapter is listed, the invalid entry is ignored.
		$result = $this->execute( 'get-builders' );
		$this->assertSame( array( 'gutenberg', 'testbuilder' ), array_column( $result['builders'], 'name' ) );

		// with two available page builders, the builder must be set.
		$this->assertInstanceOf( WP_Error::class, $this->execute( 'get-template-catalog' ) );
		$this->assertSame( 'testbuilder', $this->execute( 'get-template-catalog', array( 'builder' => 'testbuilder' ) )['builder'] );

		// the adapter is used for the preview.
		$result = $this->execute(
			'preview-template',
			array(
				'builder' => 'testbuilder',
				'content' => '{}',
				'post_id' => $this->object_id,
			)
		);
		$this->assertSame( 'rendered ' . $this->object_id, $result['html'] );

		// an unknown page builder is rejected.
		$this->assertInstanceOf( WP_Error::class, $this->execute( 'get-template', array( 'builder' => 'unknown' ) ) );

		remove_filter( 'cfprop_template_ability_adapters', $callback );
	}
}

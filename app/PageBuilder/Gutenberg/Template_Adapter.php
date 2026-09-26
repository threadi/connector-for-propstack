<?php
/**
 * File for the adapter, which makes the block templates accessible for abilities.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\PageBuilder\Gutenberg;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\Fields;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use ConnectorForPropstack\Propstack\Taxonomies;
use ConnectorForPropstack\Propstack\Template_Abilities;
use ConnectorForPropstack\Propstack\Template_Adapter_Base;
use Throwable;
use WP_Block_Template;
use WP_Block_Type_Registry;
use WP_Error;
use WP_Post;
use WP_Query;

/**
 * Adapter for the block templates of block themes (Gutenberg / Site Editor).
 */
class Template_Adapter extends Template_Adapter_Base {
	/**
	 * The prefix of the names of our blocks.
	 */
	private const BLOCK_PREFIX = 'connector-for-propstack/';

	/**
	 * Return the internal name of the page builder.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'gutenberg';
	}

	/**
	 * Return the human-readable name of the page builder.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Block Editor (Gutenberg)', 'connector-for-propstack' );
	}

	/**
	 * Return whether the block templates can be used, which requires a block theme.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return Helper::theme_is_fse_theme();
	}

	/**
	 * Return the reason why the block templates can not be used.
	 *
	 * @return string
	 */
	public function get_unavailable_reason(): string {
		return __( 'The active theme is not a block theme. Templates of block themes are only available with block themes.', 'connector-for-propstack' );
	}

	/**
	 * Return a description of the format of block templates.
	 *
	 * @return string
	 */
	public function get_format(): string {
		return __( 'WordPress block markup: serialized blocks as HTML comments, e.g. <!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group">…</div><!-- /wp:group -->. Blocks without inner content are self-closing, e.g. <!-- wp:connector-for-propstack/gallery /-->. The template is a block template of the active block theme, so it contains the whole page including the template parts for header and footer.', 'connector-for-propstack' );
	}

	/**
	 * Return the slug of the block template for the given type.
	 *
	 * @param string $type The template type.
	 *
	 * @return string
	 */
	private function get_slug( string $type ): string {
		return $type . '-' . ImmoObject::get_instance()->get_name();
	}

	/**
	 * Return the descriptions of attributes of our blocks, which are not in their block.json.
	 *
	 * @return array<string,string>
	 */
	private function get_attribute_descriptions(): array {
		return array(
			'field_name'       => __( 'The name of the field to show, see field_names (or broker_fields for the broker field).', 'connector-for-propstack' ),
			'description_type' => __( 'The description to show, see description_types.', 'connector-for-propstack' ),
			'filters'          => __( 'The filters to show, e.g. ["city"].', 'connector-for-propstack' ),
			'filter_alignment' => __( 'The alignment of the filters: "row" or "column".', 'connector-for-propstack' ),
			'object_id'        => __( 'The post-ID of the object to show. Only needed outside of templates for objects.', 'connector-for-propstack' ),
		);
	}

	/**
	 * Return the template types for which our blocks are meant.
	 *
	 * @return array<string,array<int,string>>
	 */
	private function get_block_template_types(): array {
		return array(
			self::BLOCK_PREFIX . 'field'        => array( 'single' ),
			self::BLOCK_PREFIX . 'broker-field' => array( 'single' ),
			self::BLOCK_PREFIX . 'description'  => array( 'single' ),
			self::BLOCK_PREFIX . 'gallery'      => array( 'single' ),
			self::BLOCK_PREFIX . 'energy-scale' => array( 'single' ),
			self::BLOCK_PREFIX . 'archive'      => array( 'archive' ),
			self::BLOCK_PREFIX . 'filter'       => array( 'archive' ),
			self::BLOCK_PREFIX . 'single'       => array(),
		);
	}

	/**
	 * Return an example for the given block.
	 *
	 * @param string $block_name The block name.
	 *
	 * @return string
	 */
	private function get_example( string $block_name ): string {
		// get the first broker field as example.
		$broker_fields = Template_Abilities::get_instance()->get_broker_fields();
		$broker_field  = ! empty( $broker_fields ) ? $broker_fields[0]['name'] : 'name';

		$examples = array(
			self::BLOCK_PREFIX . 'field'        => '<!-- wp:connector-for-propstack/field {"field_name":"price"} /-->',
			self::BLOCK_PREFIX . 'broker-field' => '<!-- wp:connector-for-propstack/broker-field {"field_name":"' . $broker_field . '"} /-->',
			self::BLOCK_PREFIX . 'description'  => '<!-- wp:connector-for-propstack/description {"description_type":"description_note"} /-->',
			self::BLOCK_PREFIX . 'filter'       => '<!-- wp:connector-for-propstack/filter {"filters":["city"]} /-->',
			self::BLOCK_PREFIX . 'single'       => '<!-- wp:connector-for-propstack/single {"object_id":123} /-->',
		);

		// return the example or a self-closing block without attributes.
		return $examples[ $block_name ] ?? '<!-- wp:' . $block_name . ' /-->';
	}

	/**
	 * Return the elements, which can be used in block templates: our blocks and useful core blocks.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_elements(): array {
		$elements               = array();
		$attribute_descriptions = $this->get_attribute_descriptions();
		$template_types         = $this->get_block_template_types();

		// add our own blocks.
		foreach ( WP_Block_Type_Registry::get_instance()->get_all_registered() as $block_type ) {
			if ( ! str_starts_with( $block_type->name, self::BLOCK_PREFIX ) ) {
				continue;
			}

			// collect the attributes, without the internal ones.
			$attributes = array();
			foreach ( (array) $block_type->attributes as $name => $settings ) {
				if ( in_array( $name, array( 'id', 'blockId', 'lock', 'metadata', 'className', 'style' ), true ) ) {
					continue;
				}
				$attributes[ $name ] = array(
					'type'        => $settings['type'] ?? 'string',
					'description' => $attribute_descriptions[ $name ] ?? '',
					'default'     => $settings['default'] ?? null,
				);
			}

			$elements[] = array(
				'name'           => $block_type->name,
				'title'          => (string) $block_type->title,
				'description'    => (string) $block_type->description,
				'template_types' => $template_types[ $block_type->name ] ?? array(),
				'attributes'     => $attributes,
				'example'        => $this->get_example( $block_type->name ),
			);
		}

		// add useful core blocks.
		$object_type = Taxonomies\ObjectType::get_instance()->get_name();
		$core_blocks = array(
			array(
				'name'           => 'core/template-part',
				'title'          => __( 'Template part', 'connector-for-propstack' ),
				'description'    => __( 'Header or footer of the theme, see the hint about the available template parts.', 'connector-for-propstack' ),
				'template_types' => array( 'single', 'archive' ),
				'example'        => '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->',
			),
			array(
				'name'           => 'core/post-title',
				'title'          => __( 'Title of the object', 'connector-for-propstack' ),
				'description'    => __( 'Shows the title of the object.', 'connector-for-propstack' ),
				'template_types' => array( 'single' ),
				'example'        => '<!-- wp:post-title {"level":1} /-->',
			),
			array(
				'name'           => 'core/post-featured-image',
				'title'          => __( 'Main image of the object', 'connector-for-propstack' ),
				'description'    => __( 'Shows the first image of the object.', 'connector-for-propstack' ),
				'template_types' => array( 'single' ),
				'example'        => '<!-- wp:post-featured-image {"height":"400px"} /-->',
			),
			array(
				'name'           => 'core/post-terms',
				'title'          => __( 'Terms of the object', 'connector-for-propstack' ),
				'description'    => __( 'Shows the terms of a taxonomy of the object, e.g. the object type. Use a taxonomy from the list of taxonomies as "term".', 'connector-for-propstack' ),
				'template_types' => array( 'single' ),
				'example'        => '<!-- wp:post-terms {"term":"' . $object_type . '"} /-->',
			),
			array(
				'name'           => 'core/group',
				'title'          => __( 'Group', 'connector-for-propstack' ),
				'description'    => __( 'Container for the layout of other blocks.', 'connector-for-propstack' ),
				'template_types' => array( 'single', 'archive' ),
				'example'        => '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:post-title /--></div><!-- /wp:group -->',
			),
			array(
				'name'           => 'core/columns',
				'title'          => __( 'Columns', 'connector-for-propstack' ),
				'description'    => __( 'Shows blocks in columns, e.g. key data next to the gallery.', 'connector-for-propstack' ),
				'template_types' => array( 'single', 'archive' ),
				'example'        => '<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:connector-for-propstack/gallery /--></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:connector-for-propstack/field {"field_name":"price"} /--></div><!-- /wp:column --></div><!-- /wp:columns -->',
			),
			array(
				'name'           => 'core/heading',
				'title'          => __( 'Heading', 'connector-for-propstack' ),
				'description'    => __( 'A heading, e.g. above a description.', 'connector-for-propstack' ),
				'template_types' => array( 'single', 'archive' ),
				'example'        => '<!-- wp:heading --><h2 class="wp-block-heading">Location</h2><!-- /wp:heading -->',
			),
		);
		foreach ( $core_blocks as $core_block ) {
			$core_block['attributes'] = array();
			$elements[]               = $core_block;
		}

		// return the resulting list.
		return $elements;
	}

	/**
	 * Return hints for building block templates.
	 *
	 * @return array<int,string>
	 */
	public function get_hints(): array {
		$hints = array(
			__( 'Start with get-template and change the actual template instead of writing a new one from scratch.', 'connector-for-propstack' ),
			__( 'In the template for the detail view, the blocks of this plugin show the object of the actual page. Do not set the attribute "object_id" or "id" there.', 'connector-for-propstack' ),
			__( 'Use only field names, description types, broker fields and taxonomies from this catalog. Hidden or unknown fields are not shown.', 'connector-for-propstack' ),
			__( 'Use only valid block markup. Every opening block comment needs a closing one, unless the block is self-closing. Attributes must be valid JSON.', 'connector-for-propstack' ),
			__( 'Do not use block bindings (core/post-meta) or custom HTML for fields of objects. The values are stored unformatted, only the blocks of this plugin format them (e.g. prices, areas, yes/no values) and hide empty fields.', 'connector-for-propstack' ),
			__( 'Check every template with preview-template and fix all errors before it is used.', 'connector-for-propstack' ),
			/* translators: %1$s and %2$s will be replaced by CSS class names, %3$s by an example. */
			sprintf( __( 'An empty field is output as empty element. A group whose direct children are a paragraph (the label) followed by a field block is hidden automatically if the field is empty or a yes/no field shows "no". For other structures (e.g. several fields in a row or whole sections) add the class "%1$s" to the group (attribute "className"): it is hidden if all blocks of this plugin inside it are empty, also nested. With the class "%2$s" yes/no fields with the value "no" count as empty, too (e.g. for a list of features). Groups without blocks of this plugin are never hidden. Example: %3$s', 'connector-for-propstack' ), Hide_Empty_Groups::CLASS_EMPTY, Hide_Empty_Groups::CLASS_NO, '<!-- wp:group {"className":"' . Hide_Empty_Groups::CLASS_EMPTY . '","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} --><div class="wp-block-group ' . Hide_Empty_Groups::CLASS_EMPTY . '"><!-- wp:paragraph --><p>Price</p><!-- /wp:paragraph --><!-- wp:connector-for-propstack/field {"field_name":"price"} /--></div><!-- /wp:group -->' ),
			__( 'Hiding empty values needs no own CSS: do not write CSS with :has() or :empty for this.', 'connector-for-propstack' ),
			__( 'Yes/no fields are output as icon: <span class="dashicons dashicons-yes"></span> or <span class="dashicons dashicons-no"></span>. The icon font is loaded by this plugin on every page, so do not replace the icons via CSS.', 'connector-for-propstack' ),
			__( 'Use the block connector-for-propstack/description for descriptions (see description_types), not the field block. It keeps the formatting of the texts.', 'connector-for-propstack' ),
			__( 'The blocks of this plugin support the usual block settings for typography, colors and spacing (e.g. "fontSize", "style"). Do not set the attribute "blockId", it is managed by the block editor.', 'connector-for-propstack' ),
			__( 'CSS for the template belongs into the parameter "css" of save-template, not into the template. It is loaded only on the pages which use this template. Use own class names via "className" and prefix them to avoid conflicts with the theme.', 'connector-for-propstack' ),
			__( 'Saving: call save-template first with "dry_run": true, show the result to the user and save with "dry_run": false only after the user agreed. reset-template restores the original template.', 'connector-for-propstack' ),
		);

		// add the available template parts of the theme.
		$template_parts = array();
		foreach ( get_block_templates( array(), 'wp_template_part' ) as $template_part ) {
			$template_parts[] = $template_part->slug;
		}
		if ( ! empty( $template_parts ) ) {
			/* translators: %1$s will be replaced by a list of slugs. */
			$hints[] = sprintf( __( 'Available template parts of the active theme: %1$s.', 'connector-for-propstack' ), implode( ', ', array_unique( $template_parts ) ) );
		}

		return $hints;
	}

	/**
	 * Return the actual block template of the given type.
	 *
	 * @param string $type The template type.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_template( string $type ): array|WP_Error {
		$slug = $this->get_slug( $type );

		// get the template, a customized one is preferred.
		$result = false;
		foreach ( get_block_templates( array( 'slug__in' => array( $slug ) ), 'wp_template' ) as $template ) {
			if ( $slug !== $template->slug ) {
				continue;
			}
			if ( false === $result || $this->is_customized( $template ) ) {
				$result = $template;
			}
		}

		// bail if no template could be found.
		if ( ! $result instanceof WP_Block_Template ) {
			return new WP_Error( 'cfprop_template_not_found', __( 'The template could not be found.', 'connector-for-propstack' ) );
		}

		// return the template.
		return array(
			'id'            => (string) $result->id,
			'source'        => (string) $result->source,
			'is_customized' => $this->is_customized( $result ),
			'content'       => (string) $result->content,
		);
	}

	/**
	 * Return whether block templates can be saved via abilities.
	 *
	 * @return bool
	 */
	public function can_save(): bool {
		return $this->is_available();
	}

	/**
	 * Return the CSS for the template of the given type.
	 *
	 * @param string $type The template type.
	 *
	 * @return string
	 */
	public function get_css( string $type ): string {
		return Template_Styles::get_instance()->get_css( $type );
	}

	/**
	 * Return the post of the customized template of the given type, if one exists.
	 *
	 * @param string $type The template type.
	 *
	 * @return WP_Post|null
	 */
	private function get_customized_post( string $type ): ?WP_Post {
		foreach ( Templates::get_instance()->get_templates_from_db( array( $this->get_slug( $type ) ), 'wp_template' ) as $template ) {
			$post = get_post( $template->get_post_id() );
			if ( $post instanceof WP_Post ) {
				return $post;
			}
		}
		return null;
	}

	/**
	 * Save the given block template as customized template of the given type.
	 *
	 * An existing customized template is updated (the previous version is kept as revision),
	 * otherwise a customized template is created, like the Site Editor does it.
	 *
	 * @param string      $type    The template type.
	 * @param string      $content The block markup.
	 * @param string|null $css     The CSS for this template, null to keep the actual CSS, "" to remove it.
	 * @param bool        $dry_run True to only return what would happen.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function save_template( string $type, string $content, ?string $css, bool $dry_run ): array|WP_Error {
		// bail if block templates are not available.
		if ( ! $this->can_save() ) {
			return new WP_Error( 'cfprop_saving_not_supported', $this->get_unavailable_reason() );
		}

		// get the customized template, if it exists.
		$post   = $this->get_customized_post( $type );
		$slug   = $this->get_slug( $type );
		$result = array(
			'action' => $post instanceof WP_Post ? 'update' : 'create',
			'id'     => $post instanceof WP_Post ? $post->ID : 0,
		);

		// bail on dry run.
		if ( $dry_run ) {
			return $result;
		}

		// update the existing customized template.
		if ( $post instanceof WP_Post ) {
			// keep the actual version as revision, if none exists yet (WordPress only saves the new version as revision).
			if ( empty( wp_get_post_revisions( $post->ID ) ) ) {
				wp_save_post_revision( $post->ID );
			}

			$post_id = wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => wp_slash( $content ),
				),
				true
			);
		} else {
			// get the title of the actual template.
			$title = $slug;
			foreach ( get_block_templates( array( 'slug__in' => array( $slug ) ), 'wp_template' ) as $block_template ) {
				if ( $slug === $block_template->slug && ! empty( $block_template->title ) ) {
					$title = (string) $block_template->title;
					break;
				}
			}

			// create the customized template, assigned to our templates like the Site Editor does it.
			$post_id = wp_insert_post(
				array(
					'post_type'    => 'wp_template',
					'post_status'  => 'publish',
					'post_name'    => $slug,
					'post_title'   => $title,
					'post_content' => wp_slash( $content ),
				),
				true
			);
			if ( ! $post_id instanceof WP_Error ) {
				wp_set_post_terms( $post_id, Templates::get_instance()->get_parent_id(), 'wp_theme' );
			}
		}

		// bail on error.
		if ( $post_id instanceof WP_Error ) {
			return $post_id;
		}

		// save the CSS, if given.
		if ( ! is_null( $css ) ) {
			Template_Styles::get_instance()->set_css( $type, $css );
		}

		// return the result.
		$result['id'] = $post_id;
		return $result;
	}

	/**
	 * Remove the customized template of the given type and its CSS.
	 *
	 * The template is moved to the trash, so it can be restored.
	 *
	 * @param string $type    The template type.
	 * @param bool   $dry_run True to only return what would happen.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function reset_template( string $type, bool $dry_run ): array|WP_Error {
		// bail if block templates are not available.
		if ( ! $this->can_save() ) {
			return new WP_Error( 'cfprop_saving_not_supported', $this->get_unavailable_reason() );
		}

		// get the customized template.
		$post    = $this->get_customized_post( $type );
		$has_css = '' !== $this->get_css( $type );

		// prepare the result.
		$result = array(
			'action' => $post instanceof WP_Post || $has_css ? 'reset' : 'none',
			'id'     => $post instanceof WP_Post ? $post->ID : 0,
		);

		// bail on dry run or if nothing is to do.
		if ( $dry_run || 'none' === $result['action'] ) {
			return $result;
		}

		// move the customized template to the trash.
		if ( $post instanceof WP_Post && ! wp_trash_post( $post->ID ) ) {
			return new WP_Error( 'cfprop_reset_failed', __( 'The customized template could not be removed.', 'connector-for-propstack' ) );
		}

		// remove the CSS.
		Template_Styles::get_instance()->set_css( $type, '' );

		// return the result.
		return $result;
	}

	/**
	 * Return whether the given template has been customized in this WordPress.
	 *
	 * @param WP_Block_Template $template The template.
	 *
	 * @return bool
	 */
	private function is_customized( WP_Block_Template $template ): bool {
		return 'custom' === $template->source || absint( $template->wp_id ) > 0;
	}

	/**
	 * Validate the given block template.
	 *
	 * @param string $type    The template type.
	 * @param string $content The block markup.
	 *
	 * @return array<string,array<int,string>>
	 */
	public function validate( string $type, string $content ): array {
		$errors   = array();
		$warnings = array();

		// bail if the content is empty.
		if ( '' === trim( $content ) ) {
			return array(
				'errors'   => array( __( 'The template is empty.', 'connector-for-propstack' ) ),
				'warnings' => array(),
			);
		}

		// collect the allowed values.
		$abilities         = Template_Abilities::get_instance();
		$description_types = wp_list_pluck( $abilities->get_description_types(), 'name' );
		$broker_fields     = wp_list_pluck( $abilities->get_broker_fields(), 'name' );
		$taxonomies        = array();
		foreach ( Taxonomies::get_instance()->get_taxonomies_as_objects() as $taxonomy ) {
			$taxonomies[] = $taxonomy->get_name();
		}

		// get the available template parts.
		$template_parts = array();
		foreach ( get_block_templates( array(), 'wp_template_part' ) as $template_part ) {
			$template_parts[] = $template_part->slug;
		}

		// check the blocks.
		$used_blocks = array();
		$this->check_blocks( parse_blocks( $content ), $errors, $warnings, $used_blocks, $description_types, $broker_fields, $taxonomies, $template_parts );

		// check the content for the template type.
		if ( 'single' === $type && empty( preg_grep( '/^' . preg_quote( self::BLOCK_PREFIX, '/' ) . '/', $used_blocks ) ) ) {
			$warnings[] = __( 'The template for the detail view uses no block of this plugin. Fields of objects are only shown formatted with the blocks of this plugin, see get-template-catalog.', 'connector-for-propstack' );
		}
		if ( 'archive' === $type && ! in_array( self::BLOCK_PREFIX . 'archive', $used_blocks, true ) && ! in_array( 'core/query', $used_blocks, true ) ) {
			$warnings[] = __( 'The template for the list of objects contains no block which shows objects (connector-for-propstack/archive or core/query).', 'connector-for-propstack' );
		}
		if ( ! in_array( 'core/template-part', $used_blocks, true ) ) {
			$warnings[] = __( 'The template contains no template part. Header and footer of the theme will be missing.', 'connector-for-propstack' );
		}

		// return the result.
		return array(
			'errors'   => array_values( array_unique( $errors ) ),
			'warnings' => array_values( array_unique( $warnings ) ),
		);
	}

	/**
	 * Check the given blocks and their inner blocks.
	 *
	 * @param array<int|string,array<string,mixed>> $blocks     The parsed blocks.
	 * @param array<int,string>                     $errors            The list of errors.
	 * @param array<int,string>                     $warnings          The list of warnings.
	 * @param array<int,string>                     $used_blocks       The list of used block names.
	 * @param array<int,string>                     $description_types The allowed description types.
	 * @param array<int,string>                     $broker_fields     The allowed broker fields.
	 * @param array<int,string>                     $taxonomies        The allowed taxonomies.
	 * @param array<int,string>                     $template_parts    The available template parts.
	 *
	 * @return void
	 */
	private function check_blocks( array $blocks, array &$errors, array &$warnings, array &$used_blocks, array $description_types, array $broker_fields, array $taxonomies, array $template_parts ): void {
		$registry = WP_Block_Type_Registry::get_instance();

		foreach ( $blocks as $block ) {
			$block_name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';

			// check content outside of blocks.
			if ( empty( $block_name ) ) {
				// a block comment, which could not be parsed, e.g. because of invalid JSON in its attributes.
				if ( str_contains( (string) ( $block['innerHTML'] ?? '' ), '<!-- wp:' ) ) {
					$errors[] = __( 'The template contains invalid block markup, e.g. a block comment with invalid JSON attributes.', 'connector-for-propstack' );
				} elseif ( '' !== trim( wp_strip_all_tags( (string) ( $block['innerHTML'] ?? '' ) ) ) ) {
					$warnings[] = __( 'The template contains content outside of blocks. It will be shown as classic content.', 'connector-for-propstack' );
				}
				continue;
			}
			$used_blocks[] = $block_name;

			// check the block type.
			if ( ! $registry->is_registered( $block_name ) ) {
				/* translators: %1$s will be replaced by the block name. */
				$errors[] = sprintf( __( 'The block %1$s is unknown.', 'connector-for-propstack' ), $block_name );
			}

			// check the attributes.
			$attributes = $block['attrs'] ?? array();
			if ( ! is_array( $attributes ) ) {
				/* translators: %1$s will be replaced by the block name. */
				$errors[]   = sprintf( __( 'The attributes of the block %1$s are not valid JSON.', 'connector-for-propstack' ), $block_name );
				$attributes = array();
			}

			// check block bindings to post meta, which do not work for the fields of objects.
			foreach ( $this->get_unusable_meta_bindings( $attributes ) as $meta_key ) {
				$field = Fields::get_instance()->get_field_by_name( $meta_key );
				if ( $field && ! $field->hide() && ! $field->hide_in_frontend() ) {
					/* translators: %1$s will be replaced by the field name. */
					$errors[] = sprintf( __( 'The block binding to the meta field %1$s would show nothing, as fields of objects are not available for block bindings. Use <!-- wp:connector-for-propstack/field {"field_name":"%1$s"} /--> instead.', 'connector-for-propstack' ), $meta_key );
				} else {
					/* translators: %1$s will be replaced by the meta key. */
					$errors[] = sprintf( __( 'The block binding to the meta field %1$s would show nothing, as this field is not available for block bindings. Use the blocks of this plugin for fields of objects, see get-template-catalog.', 'connector-for-propstack' ), $meta_key );
				}
			}

			// check the attributes of our blocks and some core blocks.
			switch ( $block_name ) {
				case self::BLOCK_PREFIX . 'field':
					$field_name = isset( $attributes['field_name'] ) ? (string) $attributes['field_name'] : '';
					$field      = Fields::get_instance()->get_field_by_name( $field_name );
					if ( empty( $field_name ) ) {
						$errors[] = __( 'A field block has no field_name.', 'connector-for-propstack' );
					} elseif ( ! $field || $field->hide() || $field->hide_in_frontend() ) {
						/* translators: %1$s will be replaced by the field name. */
						$errors[] = sprintf( __( 'The field %1$s is unknown or not visible. Use a name from field_names.', 'connector-for-propstack' ), $field_name );
					}
					break;
				case self::BLOCK_PREFIX . 'broker-field':
					$field_name = isset( $attributes['field_name'] ) ? (string) $attributes['field_name'] : '';
					if ( ! in_array( $field_name, $broker_fields, true ) ) {
						/* translators: %1$s will be replaced by the field name. */
						$errors[] = sprintf( __( 'The broker field %1$s is unknown or not visible. Use a name from broker_fields.', 'connector-for-propstack' ), $field_name );
					}
					break;
				case self::BLOCK_PREFIX . 'description':
					$description_type = isset( $attributes['description_type'] ) ? (string) $attributes['description_type'] : 'description_note';
					if ( ! in_array( $description_type, $description_types, true ) ) {
						/* translators: %1$s will be replaced by the description type. */
						$errors[] = sprintf( __( 'The description type %1$s is unknown. Use a name from description_types.', 'connector-for-propstack' ), $description_type );
					}
					break;
				case 'core/post-terms':
					$term = isset( $attributes['term'] ) ? (string) $attributes['term'] : '';
					if ( ! in_array( $term, $taxonomies, true ) && ! taxonomy_exists( $term ) ) {
						/* translators: %1$s will be replaced by the taxonomy name. */
						$errors[] = sprintf( __( 'The taxonomy %1$s is unknown. Use a name from taxonomies.', 'connector-for-propstack' ), $term );
					}
					break;
				case 'core/template-part':
					$slug = isset( $attributes['slug'] ) ? (string) $attributes['slug'] : '';
					if ( ! in_array( $slug, $template_parts, true ) ) {
						/* translators: %1$s will be replaced by the slug. */
						$warnings[] = sprintf( __( 'The template part %1$s does not exist in the active theme.', 'connector-for-propstack' ), $slug );
					}
					break;
			}

			// check the inner blocks.
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$this->check_blocks( $block['innerBlocks'], $errors, $warnings, $used_blocks, $description_types, $broker_fields, $taxonomies, $template_parts );
			}
		}
	}

	/**
	 * Return the meta keys of block bindings to post meta, which can not be shown for objects.
	 *
	 * Block bindings with the source "core/post-meta" only show meta fields, which are registered
	 * with "show_in_rest" for the post type (and are not protected).
	 *
	 * @param array<string,mixed> $attributes The block attributes.
	 *
	 * @return array<int,string>
	 */
	private function get_unusable_meta_bindings( array $attributes ): array {
		// bail if the block has no bindings.
		if ( empty( $attributes['metadata']['bindings'] ) || ! is_array( $attributes['metadata']['bindings'] ) ) {
			return array();
		}

		// get the meta keys which could be used.
		$registered = get_registered_meta_keys( 'post', ImmoObject::get_instance()->get_name() ) + get_registered_meta_keys( 'post' );

		// check each binding.
		$meta_keys = array();
		foreach ( $attributes['metadata']['bindings'] as $binding ) {
			if ( ! is_array( $binding ) || 'core/post-meta' !== ( $binding['source'] ?? '' ) ) {
				continue;
			}
			$meta_key = isset( $binding['args']['key'] ) ? (string) $binding['args']['key'] : '';
			if ( '' === $meta_key ) {
				continue;
			}
			if ( empty( $registered[ $meta_key ]['show_in_rest'] ) || is_protected_meta( $meta_key, 'post' ) ) {
				$meta_keys[] = $meta_key;
			}
		}

		return $meta_keys;
	}

	/**
	 * Render the given block template.
	 *
	 * @param string $type    The template type.
	 * @param string $content The block markup.
	 * @param int    $post_id The post-ID of the object (for single templates).
	 *
	 * @return string|WP_Error
	 */
	public function render( string $type, string $content, int $post_id ): string|WP_Error {
		global $post, $wp_query;

		// secure the global state.
		$original_post  = $post;
		$original_query = $wp_query;

		// prepare the global state for the template type.
		if ( 'single' === $type ) {
			$object = get_post( $post_id );
			if ( ! $object instanceof WP_Post ) {
				return new WP_Error( 'cfprop_unknown_object', __( 'The requested object does not exist.', 'connector-for-propstack' ) );
			}
			$wp_query = new WP_Query( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored below.
				array(
					'p'         => $object->ID,
					'post_type' => $object->post_type,
				)
			);
			$post     = $object; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored below.
			setup_postdata( $post );
		} else {
			$wp_query = new WP_Query( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored below.
				array(
					'post_type'      => ImmoObject::get_instance()->get_name(),
					'post_status'    => 'publish',
					'posts_per_page' => 10,
				)
			);
		}

		// render the blocks like in the frontend: without placeholders for empty values.
		add_filter( 'cfprop_show_empty_placeholders', '__return_false', PHP_INT_MAX );

		// render the blocks, including any direct output.
		$ob_level = ob_get_level();
		try {
			ob_start();
			$html  = do_blocks( $content );
			$html .= (string) ob_get_clean();
		} catch ( Throwable $e ) {
			if ( ob_get_level() > $ob_level ) {
				ob_end_clean();
			}
			$html = new WP_Error( 'cfprop_render_error', __( 'The template could not be rendered:', 'connector-for-propstack' ) . ' ' . $e->getMessage() );
		} finally {
			remove_filter( 'cfprop_show_empty_placeholders', '__return_false', PHP_INT_MAX );

			// restore the global state.
			$wp_query = $original_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring the original value.
			$post     = $original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring the original value.
			if ( $original_post instanceof WP_Post ) {
				setup_postdata( $original_post );
			} else {
				wp_reset_postdata();
			}
		}

		// return the result.
		return $html;
	}
}

<?php
/**
 * File to handle support for pagebuilder Gutenberg aka Block Editor.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\PageBuilder;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\PageBuilder\Gutenberg\Blocks_Basis;
use ConnectorForPropstack\PageBuilder\Gutenberg\Templates;
use ConnectorForPropstack\Propstack\Taxonomies;

/**
 * Object to handle the Gutenberg support.
 */
class Gutenberg extends PageBuilder_Base {
	/**
	 * Variable for an instance of this Singleton object.
	 *
	 * @var ?Gutenberg
	 */
	private static ?Gutenberg $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Gutenberg {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this PageBuilder support.
	 *
	 * @return void
	 */
	public function init(): void {
		// add our custom blocks.
		add_action( 'init', array( $this, 'register_blocks' ) );

		// initialize the templates.
		Templates::get_instance()->init();

		// add our custom block category.
		add_filter( 'block_categories_all', array( $this, 'add_block_category' ) );

		// bail if the theme is not a FSE theme with block support.
		if ( ! $this->theme_support_block_templates() ) {
			return;
		}

		// use hooks.
		add_filter( 'render_block_core/post-terms', array( $this, 'prevent_post_term_links' ), 10, 2 );

		// call parent init.
		parent::init();
	}

	/**
	 * Check if the active theme supports block templates.
	 *
	 * @return bool
	 * @noinspection PhpUndefinedFunctionInspection
	 */
	public function theme_support_block_templates(): bool {
		if (
			! $this->current_theme_is_fse_theme() &&
			( ! function_exists( 'gutenberg_supports_block_templates' ) || ! gutenberg_supports_block_templates() )
		) {
			return false;
		}

		return $this->current_theme_is_fse_theme();
	}

	/**
	 * Check if the current theme is a block theme.
	 *
	 * @return bool
	 * @noinspection PhpUndefinedFunctionInspection
	 */
	private function current_theme_is_fse_theme(): bool {
		$resulting_value = false;
		if ( function_exists( 'wp_is_block_theme' ) ) {
			$resulting_value = (bool) wp_is_block_theme();
		}
		if ( function_exists( 'gutenberg_is_fse_theme' ) ) {
			$resulting_value = (bool) gutenberg_is_fse_theme();
		}

		/**
		 * Filter whether this theme is a block theme (true) or not (false).
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param bool $resulting_value The resulting value.
		 */
		return apply_filters( 'cfprop_is_block_theme', $resulting_value );
	}

	/**
	 * Return the list of available blocks.
	 *
	 * @return array<string>
	 */
	public function get_widgets(): array {
		$list = array(
			'\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Archive',
			'\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Broker_Field',
			'\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Description',
			'\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Field',
			'\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Filter',
			'\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Gallery',
			'\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Single',
		);

		/**
		 * Filter the list of available Gutenberg blocks.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $list List of blocks.
		 */
		return apply_filters( 'cfprop_gutenberg_blocks', $list );
	}

	/**
	 * Add our custom blocks.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		foreach ( $this->get_widgets() as $block_class_name ) {
			// extend the class name to match callable.
			$class_name = $block_class_name . '::get_instance';

			// bail if it is not callable.
			if ( ! is_callable( $class_name ) ) {
				continue;
			}

			// initiate object.
			$obj = $class_name();

			// bail if the object is not a "Blocks_Basis".
			if ( ! $obj instanceof Blocks_Basis ) {
				continue;
			}

			// run registering of this block.
			$obj->register();
		}
	}

	/**
	 * Remove our own templates on uninstallation.
	 *
	 * @return void
	 */
	public function uninstall(): void {
		Templates::get_instance()->remove_db_templates();
	}

	/**
	 * Add our custom block category for all of our own widgets.
	 *
	 * @param array<int,array<string,mixed>> $block_categories List of block categories.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function add_block_category( array $block_categories ): array {
		// add our custom block category.
		$block_categories[] = array(
			'slug'  => 'connector-for-propstack',
			'title' => __( 'Connector for Propstack', 'connector-for-propstack' ),
		);

		// return the resulting list.
		return $block_categories;
	}

	/**
	 * Prevent the linking of our own taxonomies in core post-term block.
	 *
	 * @param string              $content The content.
	 * @param array<string,mixed> $block The block configuration.
	 *
	 * @return string
	 */
	public function prevent_post_term_links( string $content, array $block ): string {
		// bail if no term is set.
		if ( empty( $block['attrs']['term'] ) ) {
			return $content;
		}

		// bail if the used term is none of our taxonomies.
		if ( ! array_key_exists( $block['attrs']['term'], Taxonomies::get_instance()->get_taxonomies() ) ) {
			return $content;
		}

		// remove the links.
		$new_content = preg_replace(
			'/<a[^>]*>(.*?)<\/a>/i',
			'$1',
			$content
		);

		// bail if replacement was not successful.
		if ( ! is_string( $new_content ) ) {
			return '';
		}

		// return the content without link.
		return $new_content;
	}
}

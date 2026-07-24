<?php
/**
 * File to handle main functions for a single block.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\PageBuilder\Gutenberg;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use WP_Block_Type_Registry;

/**
 * Object to handle main functions for a single block.
 */
class Blocks_Basis {
	/**
	 * The internal name for this block.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * The text domain of this block.
	 *
	 * @var string
	 */
	protected string $text_domain = 'connector-for-propstack';

	/**
	 * Path to the directory where block.json resides.
	 *
	 * @var string
	 */
	protected string $path = '';

	/**
	 * Attributes this block is using.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	protected array $attributes = array();

	/**
	 * The instance of this object.
	 *
	 * @var Blocks_Basis|null
	 */
	private static ?Blocks_Basis $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	protected function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Blocks_Basis {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register this block.
	 *
	 * @return void
	 */
	public function register(): void {
		// get the block type registry.
		$block_type_registry = WP_Block_Type_Registry::get_instance();

		// bail if the block type registry could not be loaded.
		if ( ! $block_type_registry instanceof WP_Block_Type_Registry ) { // @phpstan-ignore instanceof.alwaysTrue
			return;
		}

		// bail if the block is already registered.
		if ( $block_type_registry->is_registered( 'connector-for-propstack/' . $this->get_name() ) ) {
			return;
		}

		// register the block.
		register_block_type(
			$this->get_path(),
			array(
				'render_callback' => array( $this, 'render' ),
				'attributes'      => $this->get_attributes(),
			)
		);

		// If this is a classic theme, deregister the blocks CSS. We will use the concatenated blocks.css instead.
		if ( ! Helper::theme_is_fse_theme() ) {
			wp_deregister_style( 'connector-for-propstack-' . $this->get_name() . '-style' );
		}

		// add some JavaScript variables for Block Editor.
		wp_add_inline_script(
			'connector-for-propstack-' . $this->get_name() . '-editor-script',
			'window.propstack_connector_config = ' . wp_json_encode(
				array(
					'enable_help' => 1 === absint( get_option( 'propstack_connector_show_help' ) ),
					'icon_url' => Helper::get_plugin_url() . 'gfx/propstack_menu_logo.png',
					/**
					 * Change the block help URL.
					 *
					 * @since 1.0.0 Available since 1.0.0.
					 * @param string $url The URL where the user could find support for this block.
					 */
					'support_url' => apply_filters( 'cfprop_block_help_url', Helper::get_plugin_support_url() ),
				)
			),
			'before'
		);
	}

	/**
	 * Return the list of attributes for this block.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_attributes(): array {
		$name              = $this->get_name();
		$single_attributes = $this->attributes;
		/**
		 * Filter the attributes for a Block.
		 *
		 * @since 1.0.0 Available since 1.0.0
		 *
		 * @param array<string,mixed> $single_attributes The settings as an array.
		 */
		return apply_filters( 'cfprop_gutenberg_block_' . $name . '_attributes', $single_attributes );
	}

	/**
	 * Return the absolute path to JSON of this block.
	 *
	 * @return string
	 */
	protected function get_path(): string {
		$name = $this->get_name();
		$path = Helper::get_plugin_path() . $this->path;
		/**
		 * Filter the path of a Block.
		 *
		 * @since 1.0.0 Available since 1.0.0
		 *
		 * @param string $path The absolute path to the block.json.
		 */
		return apply_filters( 'cfprop_gutenberg_block_' . $name . '_path', $path );
	}

	/**
	 * Return the internal name of this block.
	 *
	 * @return string
	 */
	protected function get_name(): string {
		return $this->name;
	}

	/**
	 * The render callback.
	 *
	 * @param array<string,mixed> $attributes List of attributes for this object.
	 * @return string
	 */
	public function render( array $attributes ): string {
		if ( empty( $attributes ) ) {
			return '';
		}
		return '';
	}
}

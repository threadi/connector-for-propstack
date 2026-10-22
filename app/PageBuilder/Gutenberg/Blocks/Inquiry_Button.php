<?php
/**
 * File to handle the inquiry button block.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\PageBuilder\Gutenberg\Blocks;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\PageBuilder\Gutenberg\Blocks_Basis;
use ConnectorForPropstack\Plugin\Helper;

/**
 * Object to handle this block.
 */
class Inquiry_Button extends Blocks_Basis {
	/**
	 * The internal name of this block.
	 *
	 * @var string
	 */
	protected string $name = 'inquiry-button';

	/**
	 * Path to the directory where block.json resides.
	 *
	 * @var string
	 */
	protected string $path = 'blocks/inquiry-button/';

	/**
	 * Attributes this block is using.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	protected array $attributes = array(
		'id'      => array(
			'type'    => 'integer',
			'default' => 0,
		),
		'blockId' => array(
			'type' => 'string',
		),
		'url'     => array(
			'type'    => 'string',
			'default' => '',
		),
	);

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Inquiry_Button
	 */
	private static ?Inquiry_Button $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Inquiry_Button {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register this block and add the data for the block editor.
	 *
	 * The hint for Connector for Propstack Pro in the settings of the block is hidden via "cfprop_hide_pro_hints".
	 *
	 * @return void
	 */
	public function register(): void {
		parent::register();

		// add them for the block editor.
		wp_add_inline_script(
			'connector-for-propstack-' . $this->get_name() . '-editor-script',
			'window.cfprop_inquiry_button_block = ' . wp_json_encode(
				array(
					'show_pro_hint' => Helper::show_pro_hints(),
					'pro_url'       => Helper::get_pro_url(),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Get the content for this view.
	 *
	 * @param array<string,mixed> $attributes List of attributes for this object.
	 * @return string
	 */
	public function render( array $attributes ): string {
		// set ID as class.
		$classes = '';
		if ( ! empty( $attributes['blockId'] ) ) {
			$classes = 'cfprop-block-' . sanitize_html_class( (string) $attributes['blockId'] );
		}

		// get block-classes.
		$block_html_attributes = '';
		if ( function_exists( 'get_block_wrapper_attributes' ) ) {
			$block_html_attributes = get_block_wrapper_attributes();
		}

		// get the classes and styles of the design tools for the link.
		$link_design = $this->get_link_design( $attributes );

		// collect the attributes for the widget.
		$widget_attributes = array(
			'url'          => isset( $attributes['url'] ) && is_scalar( $attributes['url'] ) ? (string) $attributes['url'] : '',
			'classes'      => trim( $classes . ' ' . Helper::get_attribute_value_from_html( 'class', $block_html_attributes ) ),
			'style'        => Helper::get_attribute_value_from_html( 'style', $block_html_attributes ),
			'link_classes' => $link_design['classes'],
			'link_style'   => $link_design['style'],
		);

		// get the widget.
		$widget = \ConnectorForPropstack\Propstack\Widgets\Inquiry_Button::get_instance();

		return wp_kses( $widget->render( $widget_attributes ), $widget->get_allowed_html() );
	}

	/**
	 * Return the classes and inline styles of the design tools of this block for the link.
	 *
	 * The design tools (colors, typography, border and padding) are not used on the wrapper of the block
	 * (see "__experimentalSkipSerialization" in block.json), but on the link, like the core button block does.
	 *
	 * @param array<string,mixed> $attributes List of attributes of this block.
	 *
	 * @return array{classes: string, style: string}
	 */
	private function get_link_design( array $attributes ): array {
		$classes = array();

		// get the custom styles (without the margin, which is used on the wrapper).
		$style = isset( $attributes['style'] ) && is_array( $attributes['style'] ) ? $attributes['style'] : array();
		if ( isset( $style['spacing'] ) && is_array( $style['spacing'] ) ) {
			unset( $style['spacing']['margin'] );
		}

		// use fluid typography for a custom font size, if supported.
		if ( isset( $style['typography'] ) && is_array( $style['typography'] ) && ! empty( $style['typography']['fontSize'] ) && is_string( $style['typography']['fontSize'] ) && function_exists( 'wp_get_typography_font_size_value' ) ) {
			$style['typography']['fontSize'] = wp_get_typography_font_size_value( array( 'size' => $style['typography']['fontSize'] ) );
		}

		// add the classes for custom colors (like the core button block).
		if ( ! empty( $style['color']['text'] ) ) {
			$classes[] = 'has-text-color';
		}
		if ( ! empty( $style['color']['background'] ) || ! empty( $style['color']['gradient'] ) ) {
			$classes[] = 'has-background';
		}
		if ( ! empty( $style['border']['color'] ) ) {
			$classes[] = 'has-border-color';
		}

		// add the classes for the presets.
		$presets = array(
			'textColor'       => array( 'has-text-color', 'has-%s-color' ),
			'backgroundColor' => array( 'has-background', 'has-%s-background-color' ),
			'gradient'        => array( 'has-background', 'has-%s-gradient-background' ),
			'borderColor'     => array( 'has-border-color', 'has-%s-border-color' ),
			'fontSize'        => array( '', 'has-%s-font-size' ),
			'fontFamily'      => array( '', 'has-%s-font-family' ),
		);
		foreach ( $presets as $name => $preset_classes ) {
			if ( empty( $attributes[ $name ] ) || ! is_string( $attributes[ $name ] ) ) {
				continue;
			}
			if ( '' !== $preset_classes[0] ) {
				$classes[] = $preset_classes[0];
			}
			$classes[] = sprintf( $preset_classes[1], _wp_to_kebab_case( $attributes[ $name ] ) );
		}

		// get the inline styles and the classes of presets used in the custom styles.
		$css = '';
		if ( ! empty( $style ) && function_exists( 'wp_style_engine_get_styles' ) ) {
			$styles = wp_style_engine_get_styles( $style, array( 'convert_vars_to_classnames' => true ) );
			$css    = $styles['css'];
			if ( ! empty( $styles['classnames'] ) ) {
				$classes = array_merge( $classes, explode( ' ', $styles['classnames'] ) );
			}
		}

		// return the results.
		return array(
			'classes' => implode( ' ', array_unique( array_filter( $classes ) ) ),
			'style'   => $css,
		);
	}
}

<?php
/**
 * File to handle the map block.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\PageBuilder\Gutenberg\Blocks;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\PageBuilder\Gutenberg\Blocks_Basis;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\Maps;

/**
 * Object to handle the map block.
 */
class Map extends Blocks_Basis {
	/**
	 * Internal name of the block.
	 *
	 * @var string
	 */
	protected string $name = 'map';

	/**
	 * Path to the directory where block.json resides.
	 *
	 * @var string
	 */
	protected string $path = 'blocks/map/';

	/**
	 * Attributes this block is using.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	protected array $attributes = array(
		'id'        => array(
			'type'    => 'integer',
			'default' => 0,
		),
		'blockId'   => array(
			'type' => 'string',
		),
		'source'    => array(
			'type'    => 'string',
			'default' => 'object',
		),
		'object_id' => array(
			'type'    => 'string',
			'default' => '',
		),
		'zoom'      => array(
			'type'    => 'string',
			'default' => '',
		),
		'popup'     => array(
			'type'    => 'boolean',
			'default' => true,
		),
		'height'    => array(
			'type'    => 'string',
			'default' => '',
		),
	);

	/**
	 * Instance of this object.
	 *
	 * @var ?Map
	 */
	private static ?Map $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Map {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register this block and add the sources for the locations for the block editor.
	 *
	 * @return void
	 */
	public function register(): void {
		parent::register();

		// collect the sources for the select field in the block editor.
		$sources = array();
		foreach ( \ConnectorForPropstack\Propstack\Widgets\Map::get_instance()->get_sources() as $value => $label ) {
			$sources[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		// add them for the block editor.
		wp_add_inline_script(
			'connector-for-propstack-' . $this->get_name() . '-editor-script',
			'window.cfprop_map_block = ' . wp_json_encode(
				array(
					'sources' => $sources,
					'height'  => Maps::get_instance()->get_height(),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Render the block.
	 *
	 * @param array<string,mixed> $attributes List of attributes for this block.
	 *
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

		// collect the attributes for the widget.
		$widget_attributes = array(
			'source'    => isset( $attributes['source'] ) && is_string( $attributes['source'] ) ? $attributes['source'] : 'object',
			'object_id' => isset( $attributes['object_id'] ) && is_scalar( $attributes['object_id'] ) ? (string) $attributes['object_id'] : '',
			'zoom'      => isset( $attributes['zoom'] ) && is_scalar( $attributes['zoom'] ) ? (string) $attributes['zoom'] : '',
			'popup'     => ! isset( $attributes['popup'] ) || ! empty( $attributes['popup'] ) ? '1' : '0',
			'height'    => isset( $attributes['height'] ) && is_scalar( $attributes['height'] ) ? (string) $attributes['height'] : '',
			'classes'   => trim( $classes . ' ' . Helper::get_attribute_value_from_html( 'class', $block_html_attributes ) ),
		);

		return wp_kses( \ConnectorForPropstack\Propstack\Widgets\Map::get_instance()->render( $widget_attributes ), \ConnectorForPropstack\Propstack\Widgets\Map::get_instance()->get_allowed_html() );
	}
}

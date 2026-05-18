<?php
/**
 * File to handle the archive block.
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
class Archive extends Blocks_Basis {
	/**
	 * The internal name of this block.
	 *
	 * @var string
	 */
	protected string $name = 'archive';

	/**
	 * Path to the directory where block.json resides.
	 *
	 * @var string
	 */
	protected string $path = 'blocks/archive/';

	/**
	 * Attributes this block is using.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	protected array $attributes = array(
		'blockId'             => array(
			'type' => 'string',
		),
	);

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Archive
	 */
	private static ?Archive $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Archive {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return the content of the archive.
	 *
	 * @param array<string,mixed> $attributes List of attributes for this object.
	 * @return string
	 */
	public function render( array $attributes ): string {
		// set ID as class.
		$classes = '';
		if ( ! empty( $attributes['blockId'] ) ) {
			$classes = 'cfprop-block-' . $attributes['blockId'];
		}

		// get block-classes.
		$styles_array          = array();
		$block_html_attributes = '';
		if ( function_exists( 'get_block_wrapper_attributes' ) ) {
			$block_html_attributes = get_block_wrapper_attributes();

			// get styles.
			$styles = Helper::get_attribute_value_from_html( 'style', $block_html_attributes );
			if ( ! empty( $styles ) ) {
				$styles_array[] = '.entry-content.' . $classes . ' { ' . $styles . ' }';
			}
		}

		// add the attributes.
		$attributes['styles'] = implode( PHP_EOL, $styles_array );
		$attributes['classes'] = $classes . ' ' . Helper::get_attribute_value_from_html( 'class', $block_html_attributes );

		return wp_kses_post( \ConnectorForPropstack\Propstack\Widgets\Archive::get_instance()->render( $attributes ) );
	}
}

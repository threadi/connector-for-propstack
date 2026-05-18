<?php
/**
 * File to handle the field block.
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
class Broker_Field extends Blocks_Basis {
	/**
	 * The internal name of this block.
	 *
	 * @var string
	 */
	protected string $name = 'broker-field';

	/**
	 * Path to the directory where block.json resides.
	 *
	 * @var string
	 */
	protected string $path = 'blocks/broker-field/';

	/**
	 * Attributes this block is using.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	protected array $attributes = array(
		'id'                  => array(
			'type'    => 'integer',
			'default' => 0,
		),
		'blockId'             => array(
			'type' => 'string',
		),
		'field_name'             => array(
			'type' => 'string',
			'default' => '',
		),
	);

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Broker_Field
	 */
	private static ?Broker_Field $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Broker_Field {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
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

		return wp_kses_post( \ConnectorForPropstack\Propstack\Widgets\Broker_Field::get_instance()->render( $attributes ) );
	}
}

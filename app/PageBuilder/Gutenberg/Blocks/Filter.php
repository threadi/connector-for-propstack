<?php
/**
 * File to handle the filter block.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\PageBuilder\Gutenberg\Blocks;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\PageBuilder\Gutenberg\Blocks_Basis;

/**
 * Object to handle this block.
 */
class Filter extends Blocks_Basis {
	/**
	 * The internal name of this block.
	 *
	 * @var string
	 */
	protected string $name = 'filter';

	/**
	 * Path to the directory where block.json resides.
	 *
	 * @var string
	 */
	protected string $path = 'blocks/filter/';

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
		'filters'             => array(
			'type' => 'array',
			'default' => array(),
		),
	);

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Filter
	 */
	private static ?Filter $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Filter {
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
		return \ConnectorForPropstack\Propstack\Widgets\Filter::get_instance()->render( $attributes );
	}
}

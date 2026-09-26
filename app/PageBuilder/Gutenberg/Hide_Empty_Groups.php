<?php
/**
 * File to hide blocks (e.g. groups) whose blocks of this plugin show nothing.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\PageBuilder\Gutenberg;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use WeakMap;
use WP_Block;

/**
 * Hide blocks with the class "cfprop-hide-if-empty" or "cfprop-hide-if-no", if the blocks of this plugin inside them show nothing.
 *
 * Example: a group with the label "Price" and the field block for the price is hidden completely if the object has no price.
 * With "cfprop-hide-if-no" a yes/no field with the value "no" is also treated as empty, e.g. for a list of features.
 *
 * Blocks without any block of this plugin inside them are never hidden.
 */
class Hide_Empty_Groups {
	/**
	 * The class to hide a block if the blocks of this plugin inside it are empty.
	 */
	public const CLASS_EMPTY = 'cfprop-hide-if-empty';

	/**
	 * The class to hide a block if the blocks of this plugin inside it are empty or show "no".
	 */
	public const CLASS_NO = 'cfprop-hide-if-no';

	/**
	 * The prefix of the names of our blocks.
	 */
	private const BLOCK_PREFIX = 'connector-for-propstack/';

	/**
	 * The result of each rendered block of this plugin and of each block with our classes.
	 *
	 * Values: "filled", "no" or "empty".
	 *
	 * @var WeakMap<WP_Block,string>|null
	 */
	private ?WeakMap $results = null;

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Hide_Empty_Groups
	 */
	private static ?Hide_Empty_Groups $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Hide_Empty_Groups {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'render_block', array( $this, 'check_block' ), 10, 3 );
	}

	/**
	 * Check each rendered block: remember the result of our blocks and hide blocks with our classes, if necessary.
	 *
	 * Inner blocks are rendered before their parent, so the results of our blocks are known when the parent is checked.
	 *
	 * @param string              $block_content The rendered block.
	 * @param array<string,mixed> $parsed_block  The parsed block.
	 * @param WP_Block|null       $instance      The block instance.
	 *
	 * @return string
	 */
	public function check_block( string $block_content, array $parsed_block, ?WP_Block $instance = null ): string {
		// bail without instance (e.g. called by other plugins).
		if ( ! $instance instanceof WP_Block ) {
			return $block_content;
		}

		// initialize the results.
		if ( is_null( $this->results ) ) {
			$this->results = new WeakMap();
		}

		// remember the result of our blocks.
		$block_name = isset( $parsed_block['blockName'] ) ? (string) $parsed_block['blockName'] : '';
		if ( str_starts_with( $block_name, self::BLOCK_PREFIX ) ) {
			$this->results[ $instance ] = $this->get_result( $block_content );
			return $block_content;
		}

		// bail if the block does not use our classes.
		$mode = $this->get_mode( $parsed_block );
		if ( '' === $mode ) {
			return $block_content;
		}

		// check the results of our blocks inside this block.
		$state = $this->get_state( $instance );

		// bail if no block of this plugin is inside this block.
		if ( '' === $state ) {
			return $block_content;
		}

		// hide the block if nothing is filled.
		$hide = 'empty' === $state || ( 'no' === $state && self::CLASS_NO === $mode );

		// remember the result for surrounding blocks.
		$this->results[ $instance ] = $hide ? 'empty' : 'filled';

		// return the result.
		return $hide ? '' : $block_content;
	}

	/**
	 * Return the result of a rendered block of this plugin.
	 *
	 * @param string $block_content The rendered block.
	 *
	 * @return string
	 */
	private function get_result( string $block_content ): string {
		// yes/no fields are output as icon without text.
		if ( str_contains( $block_content, 'dashicons-yes' ) ) {
			return 'filled';
		}
		if ( str_contains( $block_content, 'dashicons-no' ) ) {
			return 'no';
		}

		// media without text (e.g. the image of the broker or the gallery) counts as filled.
		if ( preg_match( '/<(img|picture|video|audio|iframe|svg|canvas)\b/i', $block_content ) ) {
			return 'filled';
		}

		// empty fields are output as empty element (e.g. <p class="cfprop-field"></p>), so check the text.
		$text = str_replace( array( '&nbsp;', "\xC2\xA0" ), '', wp_strip_all_tags( $block_content ) );
		return '' === trim( $text ) ? 'empty' : 'filled';
	}

	/**
	 * Return which of our classes the block uses: the class name or an empty string.
	 *
	 * @param array<string,mixed> $parsed_block The parsed block.
	 *
	 * @return string
	 */
	private function get_mode( array $parsed_block ): string {
		// get the classes.
		$class_name = '';
		if ( isset( $parsed_block['attrs'] ) && is_array( $parsed_block['attrs'] ) && isset( $parsed_block['attrs']['className'] ) && is_string( $parsed_block['attrs']['className'] ) ) {
			$class_name = $parsed_block['attrs']['className'];
		}
		$classes = preg_split( '/\s+/', $class_name );
		if ( ! is_array( $classes ) ) {
			return '';
		}

		// return the used class.
		if ( in_array( self::CLASS_NO, $classes, true ) ) {
			return self::CLASS_NO;
		}
		if ( in_array( self::CLASS_EMPTY, $classes, true ) ) {
			return self::CLASS_EMPTY;
		}
		return '';
	}

	/**
	 * Return the combined state of our blocks inside the given block.
	 *
	 * Values: "filled" if at least one block shows a value, "no" if all blocks with a value show "no",
	 * "empty" if all blocks are empty or "" if there is no block of this plugin inside.
	 *
	 * @param WP_Block $instance The block.
	 *
	 * @return string
	 */
	private function get_state( WP_Block $instance ): string {
		$state = '';
		foreach ( $instance->inner_blocks as $inner_block ) {
			if ( ! $inner_block instanceof WP_Block ) {
				continue;
			}

			// use the known result of our blocks or of inner blocks with our classes, otherwise check the inner blocks.
			if ( ! is_null( $this->results ) && isset( $this->results[ $inner_block ] ) ) {
				$inner_state = $this->results[ $inner_block ];
			} else {
				$inner_state = $this->get_state( $inner_block );
			}

			// combine the states: filled > no > empty > nothing.
			if ( 'filled' === $inner_state ) {
				return 'filled';
			}
			if ( 'no' === $inner_state || ( 'empty' === $inner_state && '' === $state ) ) {
				$state = $inner_state;
			}
		}
		return $state;
	}
}

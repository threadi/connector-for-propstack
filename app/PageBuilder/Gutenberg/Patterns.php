<?php
/**
 * File to register the block patterns of this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\PageBuilder\Gutenberg;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;

/**
 * Object to register the block patterns of this plugin.
 */
class Patterns {
	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Patterns
	 */
	private static ?Patterns $instance = null;

	/**
	 * The name of the pattern category.
	 *
	 * @var string
	 */
	private string $category = 'connector-for-propstack';

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Patterns {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the patterns.
	 *
	 * @return void
	 */
	public function init(): void {
		// register the patterns after our post type has been registered.
		add_action( 'init', array( $this, 'register' ), 30 );
	}

	/**
	 * Register the pattern category and the patterns.
	 *
	 * @return void
	 */
	public function register(): void {
		// bail if patterns are not supported.
		if ( ! function_exists( 'register_block_pattern' ) || ! function_exists( 'register_block_pattern_category' ) ) {
			return;
		}

		// register the category.
		register_block_pattern_category(
			$this->category,
			array(
				'label'       => __( 'Connector for Propstack', 'connector-for-propstack' ),
				'description' => __( 'Patterns to show your objects from Propstack.', 'connector-for-propstack' ),
			)
		);

		// register the patterns.
		foreach ( $this->get_patterns() as $name => $pattern ) {
			register_block_pattern( 'connector-for-propstack/' . $name, $pattern );
		}
	}

	/**
	 * Return the list of patterns.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_patterns(): array {
		$patterns = array(
			'list-with-filter' => array(
				'title'       => __( 'List of objects with filter', 'connector-for-propstack' ),
				'description' => __( 'A headline, the filter and the list of your objects.', 'connector-for-propstack' ),
				'categories'  => array( $this->category ),
				'keywords'    => array( 'propstack', __( 'objects', 'connector-for-propstack' ), __( 'filter', 'connector-for-propstack' ) ),
				'content'     => $this->get_list_with_filter(),
			),
			'newest-objects'   => array(
				'title'       => __( 'Newest objects', 'connector-for-propstack' ),
				'description' => __( 'The three newest objects with image, title, location and price, e.g. for your home page.', 'connector-for-propstack' ),
				'categories'  => array( $this->category ),
				'keywords'    => array( 'propstack', __( 'objects', 'connector-for-propstack' ), __( 'teaser', 'connector-for-propstack' ) ),
				'content'     => $this->get_newest_objects(),
			),
		);

		// the contact with the broker is only usable in the template for the detail view of block themes.
		if ( Helper::theme_is_fse_theme() ) {
			$patterns['broker-contact'] = array(
				'title'         => __( 'Contact with broker', 'connector-for-propstack' ),
				'description'   => __( 'Image, name, position, email and phone of the broker of the object with a button for inquiries. For the template of the detail view.', 'connector-for-propstack' ),
				'categories'    => array( $this->category ),
				'keywords'      => array( 'propstack', __( 'broker', 'connector-for-propstack' ), __( 'contact', 'connector-for-propstack' ) ),
				'templateTypes' => array( 'single-' . ImmoObject::get_instance()->get_name() ),
				'content'       => $this->get_broker_contact(),
			);
		}

		/**
		 * Filter the block patterns of this plugin.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,array<string,mixed>> $patterns List of patterns (name => settings for register_block_pattern()).
		 */
		return apply_filters( 'cfprop_block_patterns', $patterns );
	}

	/**
	 * Return the markup of a heading.
	 *
	 * @param string $text The text.
	 *
	 * @return string
	 */
	private function get_heading( string $text ): string {
		return '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $text ) . '</h2><!-- /wp:heading -->';
	}

	/**
	 * Return the opening markup of the wide group, which contains a pattern.
	 *
	 * @return string
	 */
	private function get_group_start(): string {
		return '<!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"1200px"}} --><div class="wp-block-group alignwide">';
	}

	/**
	 * Return the markup of the query loop from the block template for the list of objects.
	 *
	 * Uses the query loop of the block template of this plugin, so the pattern looks like the list of objects.
	 * The query does not inherit the main query and shows the given amount of objects.
	 *
	 * @param int $per_page The amount of objects.
	 * @param int $query_id The ID of the query (must be unique on a page).
	 *
	 * @return string
	 */
	private function get_archive_query( int $per_page, int $query_id ): string {
		// bail if the functions to handle blocks are not available.
		if ( ! function_exists( 'parse_blocks' ) || ! function_exists( 'serialize_block' ) ) {
			return '';
		}

		// get the block template for the list of objects.
		$file = Helper::get_plugin_path() . 'templates/gutenberg/archive-' . ImmoObject::get_instance()->get_name() . '.html';
		if ( ! file_exists( $file ) ) {
			return '';
		}
		$content = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		// search the query loop in it.
		$query_block = $this->find_block( parse_blocks( $content ), 'core/query' );
		if ( empty( $query_block ) ) {
			return '';
		}

		// configure the query.
		$query_block['attrs']['queryId'] = $query_id;
		$query                           = isset( $query_block['attrs']['query'] ) && is_array( $query_block['attrs']['query'] ) ? $query_block['attrs']['query'] : array();
		$query['inherit']                = false;
		$query['perPage']                = $per_page;
		$query['postType']               = ImmoObject::get_instance()->get_name();
		$query_block['attrs']['query']   = $query;

		// remove the IDs of our blocks, so the block editor creates new ones for each usage of the pattern.
		$query_block = $this->remove_block_ids( $query_block );

		// return the markup.
		return serialize_block( $query_block );
	}

	/**
	 * Return the first block with the given name from the given list of blocks (including inner blocks).
	 *
	 * @param array<int|string,mixed> $blocks The list of blocks.
	 * @param string                  $name   The name of the searched block.
	 *
	 * @return array<string,mixed>
	 */
	private function find_block( array $blocks, string $name ): array {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( isset( $block['blockName'] ) && $name === $block['blockName'] ) {
				return $block;
			}
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$result = $this->find_block( $block['innerBlocks'], $name );
				if ( ! empty( $result ) ) {
					return $result;
				}
			}
		}
		return array();
	}

	/**
	 * Remove the attribute "blockId" from the given block and its inner blocks.
	 *
	 * @param array<string,mixed> $block The block.
	 *
	 * @return array<string,mixed>
	 */
	private function remove_block_ids( array $block ): array {
		if ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
			unset( $block['attrs']['blockId'] );
		}
		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $index => $inner_block ) {
				if ( is_array( $inner_block ) ) {
					$block['innerBlocks'][ $index ] = $this->remove_block_ids( $inner_block );
				}
			}
		}
		return $block;
	}

	/**
	 * Return the markup for the pattern "list with filter".
	 *
	 * In block themes it uses the query loop of the block template for the list of objects, so it looks the same.
	 *
	 * @return string
	 */
	private function get_list_with_filter(): string {
		// get the list: the query loop in block themes, the block of this plugin in all other themes.
		$list = Helper::theme_is_fse_theme() ? $this->get_archive_query( 10, 10 ) : '';
		if ( empty( $list ) ) {
			$list = '<!-- wp:connector-for-propstack/archive /-->';
		}

		return $this->get_group_start()
			. $this->get_heading( __( 'Our objects', 'connector-for-propstack' ) )
			. '<!-- wp:connector-for-propstack/filter /-->'
			. $list
			. '</div><!-- /wp:group -->';
	}

	/**
	 * Return the markup for the pattern "newest objects".
	 *
	 * In block themes it uses the query loop of the block template for the list of objects, so it looks the same.
	 *
	 * @return string
	 */
	private function get_newest_objects(): string {
		// get the list: the query loop of the block template in block themes.
		$list = Helper::theme_is_fse_theme() ? $this->get_archive_query( 3, 11 ) : '';

		// use a simple query loop in all other themes.
		if ( empty( $list ) ) {
			$query = array(
				'queryId' => 11,
				'query'   => array(
					'perPage'  => 3,
					'pages'    => 0,
					'offset'   => 0,
					'postType' => ImmoObject::get_instance()->get_name(),
					'order'    => 'desc',
					'orderBy'  => 'date',
					'author'   => '',
					'search'   => '',
					'exclude'  => array(),
					'sticky'   => '',
					'inherit'  => false,
				),
			);

			$list  = '<!-- wp:query ' . wp_json_encode( $query ) . ' --><div class="wp-block-query">';
			$list .= '<!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->';
			$list .= '<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3"} /-->';
			$list .= '<!-- wp:post-title {"isLink":true,"level":3} /-->';
			$list .= '<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} --><div class="wp-block-group">';
			$list .= '<!-- wp:connector-for-propstack/field {"field_name":"zip_code"} /-->';
			$list .= '<!-- wp:connector-for-propstack/field {"field_name":"city"} /-->';
			$list .= '</div><!-- /wp:group -->';
			$list .= '<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} --><div class="wp-block-group">';
			$list .= '<!-- wp:connector-for-propstack/field {"field_name":"price"} /-->';
			$list .= '<!-- wp:connector-for-propstack/field {"field_name":"total_rent"} /-->';
			$list .= '</div><!-- /wp:group -->';
			$list .= '<!-- /wp:post-template -->';
			$list .= '</div><!-- /wp:query -->';
		}

		// collect the markup.
		$content  = $this->get_group_start();
		$content .= $this->get_heading( __( 'Our newest objects', 'connector-for-propstack' ) );
		$content .= $list;

		// add a button to the list of all objects, if it is available.
		$archive_url = ImmoObject::get_instance()->get_archive_url();
		if ( ! empty( $archive_url ) && 1 !== absint( get_option( 'propstack_connector_disable_archive_slug' ) ) ) {
			$content .= '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $archive_url ) . '">' . esc_html__( 'Show all objects', 'connector-for-propstack' ) . '</a></div><!-- /wp:button --></div><!-- /wp:buttons -->';
		}

		// return the resulting markup.
		return $content . '</div><!-- /wp:group -->';
	}

	/**
	 * Return the markup for the pattern "contact with broker".
	 *
	 * @return string
	 */
	private function get_broker_contact(): string {
		$content  = '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group">';
		$content .= $this->get_heading( __( 'Your contact', 'connector-for-propstack' ) );
		$content .= '<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"top"}} --><div class="wp-block-group">';
		$content .= '<!-- wp:connector-for-propstack/broker-field {"field_name":"thumbnail_id"} /-->';
		$content .= '<!-- wp:group {"layout":{"type":"flex","orientation":"vertical"}} --><div class="wp-block-group">';
		$content .= '<!-- wp:connector-for-propstack/broker-field {"field_name":"name"} /-->';
		$content .= '<!-- wp:connector-for-propstack/broker-field {"field_name":"position"} /-->';
		$content .= '<!-- wp:connector-for-propstack/broker-field {"field_name":"public_email"} /-->';
		$content .= '<!-- wp:connector-for-propstack/broker-field {"field_name":"public_phone"} /-->';
		$content .= '</div><!-- /wp:group -->';
		$content .= '</div><!-- /wp:group -->';
		$content .= '<!-- wp:connector-for-propstack/inquiry-button /-->';
		return $content . '</div><!-- /wp:group -->';
	}
}

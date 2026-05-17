<?php
/**
 * File to handle the archive widget.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Widgets;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Languages;
use ConnectorForPropstack\Plugin\Templates;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Widget_Base;

/**
 * Object to handle the archive widget.
 */
class Archive extends Widget_Base {
	/**
	 * The internal name for this object.
	 *
	 * @var string
	 */
	protected string $name = 'widget_archive';

	/**
	 * Name if the setting tab where the setting field is visible.
	 *
	 * @var string
	 */
	protected string $setting_tab = '';

	/**
	 * Path to Block object.
	 *
	 * @var string
	 */
	protected string $gutenberg = '\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Archive';

	/**
	 * Instance of this object.
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
	 * Return the rendered widget.
	 *
	 * @param array<string,mixed> $attributes Attributes to configure the rendering.
	 *
	 * @return string
	 */
	public function render( array $attributes ): string {
		$query_params = array();
		/**
		 * Filter the archive query params, e.g., to filter the list.
		 *
		 * @param array<string,mixed> $query_params The additional query parameters for "WP_Query".
		 * @param array<string,mixed> $attributes The attributes.
		 */
		$query_params = apply_filters( 'cfprop_archive_query_params', $query_params, $attributes );

		// add settings to use the templates.
		$attributes['classes']          = 'cfprop-objects default-max-width';
		$attributes['listing_template'] = 'default';
		$attributes['templates']        = array(
			'thumbnail',
			'location_object_type',
			'title',
			'values',
			'detail_link',
		);
		$attributes['query']            = ImmoObjects::get_instance()->get_objects_query( $query_params );
		$attributes['lang']             = Languages::get_instance()->get_current_lang();

		// get pagination.
		$query                    = array(
			'base'    => str_replace( (string) PHP_INT_MAX, '%#%', esc_url( get_pagenum_link( PHP_INT_MAX ) ) ),
			'format'  => '?paged=%#%',
			'current' => max( 1, get_query_var( 'paged' ) ),
			'total'   => $attributes['query']->max_num_pages,
		);
		$attributes['pagination'] = paginate_links( $query );
		if ( is_null( $attributes['pagination'] ) ) {
			$attributes['pagination'] = '';
		}

		/**
		 * Filter the attributes for the archive widget.
		 *
		 * @param array<string,mixed> $attributes The attributes for the archive widget.
		 */
		$attributes = apply_filters( 'cfprop_widget_archive_attributes', $attributes );

		// collect the output.
		ob_start();

		/**
		 * Run custom actions before the output of the archive listing.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array $attributes List of attributes.
		 */
		do_action( 'cfprop_get_template_before', $attributes );

		// use the template to generate the output.
		include Templates::get_instance()->get_template( 'parts/archive.php' );

		// get the content.
		$content = ob_get_clean();

		// return the content.
		if ( ! $content ) {
			return '';
		}
		return $content;
	}
}

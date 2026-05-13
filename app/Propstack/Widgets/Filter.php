<?php
/**
 * File to handle the filter widget.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Widgets;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Templates;
use ConnectorForPropstack\Propstack\Filters;
use ConnectorForPropstack\Propstack\Widget_Base;

/**
 * Object to handle the select filter widget.
 */
class Filter extends Widget_Base {
	/**
	 * The internal name for this object.
	 *
	 * @var string
	 */
	protected string $name = 'widget_filter';

	/**
	 * Name if the setting tab where the setting field is visible.
	 *
	 * @var string
	 */
	protected string $setting_tab = '';

	/**
	 * Path to the Block object.
	 *
	 * @var string
	 */
	protected string $gutenberg = '\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Filter';

	/**
	 * Instance of this object.
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
	 * Initialize this object.
	 */
	public function init(): void {
		// use hooks.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_css' ) );

		// use global init.
		parent::init();
	}

	/**
	 * Return the rendered widget.
	 *
	 * @param array<string,mixed> $attributes Attributes to configure the rendering.
	 *
	 * @return string
	 */
	public function render( array $attributes ): string {
		// prepare the listing of filters to use.
		$attributes['filter_objects'] = array();

		// loop through the possible filters and add them to the list.
		foreach ( Filters::get_instance()->get_filters_as_objects() as $filter_obj ) {
			foreach ( $filter_obj->get( $attributes ) as $filter ) {
				// bail if filters are preset and this is not in the list.
				if ( ! empty( $attributes['filters'] ) && ! in_array( $filter->get_filter_name(), $attributes['filters'], true ) ) {
					continue;
				}

				// add the filter to the list.
				$attributes['filter_objects'][] = $filter;
			}
		}

		// set the method to use.
		$attributes['method'] = 1 === absint( get_option( 'propstack_connector_filter_use_post' ) ) ? 'post' : 'get';

		// set the current URL.
		$attributes['current_url'] = Helper::get_current_url();

		// prepare classes.
		$attributes['classes'] = '';

		// set classes depending on filter alignment.
		if ( empty( $attributes['filter_alignment'] ) ) {
			$attributes['filter_alignment'] = 'column';
		}
		$attributes['classes'] .= ' propstack-connector-filter-' . $attributes['filter_alignment'];

		/**
		 * Filter the attributes for the select filter widget.
		 *
		 * @param array<string,mixed> $attributes The attributes for the archive widget.
		 */
		$attributes = apply_filters( 'cfprop_widget_filter_select_attributes', $attributes );

		// bail if no filters are set.
		if ( empty( $attributes['filter_objects'] ) ) {
			return '';
		}

		// enable the styles.
		wp_enqueue_style( 'propstack-connector-filters' );

		// collect the output.
		ob_start();

		// embed the listing content.
		include Templates::get_instance()->get_template( 'parts/part-filter.php' );

		// get the content.
		$content = ob_get_clean();

		// return the content.
		if ( ! $content ) {
			return '';
		}
		return $content;
	}

	/**
	 * Register the CSS for this widget.
	 *
	 * @return void
	 */
	public function register_css(): void {
		wp_register_style(
			'propstack-connector-filters',
			Helper::get_plugin_url() . 'css/filter.css',
			array(),
			Helper::get_file_version( Helper::get_plugin_path() . 'css/filter.css' ),
		);
	}

	/**
	 * Enqueue the CSS for this widget.
	 *
	 * @return void
	 */
	public function enqueue_css(): void {
		$this->register_css();

		// enable the styles.
		wp_enqueue_style( 'propstack-connector-filters' );
	}
}

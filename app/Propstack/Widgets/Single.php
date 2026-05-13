<?php
/**
 * File to handle the single widget.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Widgets;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Languages;
use ConnectorForPropstack\Plugin\Templates;
use ConnectorForPropstack\Propstack\ImmoObject;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Widget_Base;

/**
 * Object to handle the single widget.
 */
class Single extends Widget_Base {
	/**
	 * The internal name for this object.
	 *
	 * @var string
	 */
	protected string $name = 'widget_single';

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
	protected string $gutenberg = '\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Single';

	/**
	 * Instance of this object.
	 *
	 * @var ?Single
	 */
	private static ?Single $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Single {
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
		// bail if no object ID is set.
		if ( empty( $attributes['object_id'] ) ) {
			return '';
		}

		// get the object for the selected object ID.
		$attributes['object'] = ImmoObjects::get_instance()->get_object_by_object_id( $attributes['object_id'], Languages::get_instance()->get_current_lang() );

		// bail if no object could be found.
		if ( ! $attributes['object'] instanceof ImmoObject ) {
			return '';
		}

		// set some attributes to configure the rendering.
		$attributes['classes']   = 'propstack-connector-object default-max-width';
		$attributes['template']  = 'default';
		$attributes['templates'] = array(
			'object_data',
			'energy',
			'descriptions',
			'location',
			'broker',
		);
		$attributes['lang']      = Languages::get_instance()->get_current_lang();

		/**
		 * Filter the attributes for the single widget.
		 *
		 * @param array<string,mixed> $attributes The attributes for the single widget.
		 */
		$attributes = apply_filters( 'cfprop_widget_single_attributes', $attributes );

		// secure the post-ID for the template.
		$post_id = $attributes['object']->get_id();

		// get the object for the immo objects.
		$immo_object = \ConnectorForPropstack\Propstack\ImmoObjects::get_instance()->get_object( $attributes['object']->get_id(), $attributes['lang'] );

		// collect the output.
		ob_start();

		// embed content.
		include Templates::get_instance()->get_template( 'parts/single/' . $attributes['template'] . '.php' );

		// return the resulting code.
		$content = ob_get_clean();
		if ( ! $content ) {
			return '';
		}
		return $content;
	}
}

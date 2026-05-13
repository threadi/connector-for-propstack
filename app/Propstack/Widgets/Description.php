<?php
/**
 * File to handle the description widget.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Widgets;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Templates;
use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Fields;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use ConnectorForPropstack\Propstack\Widget_Base;

/**
 * Object to handle the description widget.
 */
class Description extends Widget_Base {
	/**
	 * The internal name for this object.
	 *
	 * @var string
	 */
	protected string $name = 'widget_description';

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
	protected string $gutenberg = '\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Description';

	/**
	 * Instance of this object.
	 *
	 * @var ?Description
	 */
	private static ?Description $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Description {
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
		// bail if no field is selected.
		if ( empty( $attributes['description_type'] ) ) {
			return '';
		}

		// get the object for this request.
		$immo_object = $this->get_object_by_request();

		// bail if no object could be found.
		if ( ! $immo_object instanceof \ConnectorForPropstack\Propstack\ImmoObject ) {
			return '';
		}

		// bail if requested post type is not ours.
		if ( get_post_type( $immo_object->get_id() ) !== ImmoObject::get_instance()->get_name() ) {
			return '';
		}

		// get the field object.
		$field = Fields::get_instance()->get_field_by_name( $attributes['description_type'] );

		// bail if field could not be found.
		if ( ! $field instanceof Field_Base ) {
			return '';
		}

		// return the value of the given description type in this object.
		$description_text = Fields::get_instance()->get_field_value( $immo_object->get_id(), $field, false );

		// if we got no text and are in an editor, show placeholder.
		if ( empty( $description_text ) && Helper::is_rest_request() ) {
			$description_text = '<em>' . __( 'No description set.', 'connector-for-propstack' ) . '</em>';
		}

		// return the template with this value.
		ob_start();
		include Templates::get_instance()->get_template( 'parts/part-description.php' );
		$content = ob_get_clean();
		if ( ! $content ) {
			return '';
		}
		return $content;
	}
}

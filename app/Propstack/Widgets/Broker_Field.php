<?php
/**
 * File to handle the broker field widget.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Widgets;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Templates;
use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use ConnectorForPropstack\Propstack\Taxonomies;
use ConnectorForPropstack\Propstack\Taxonomies\Broker;
use ConnectorForPropstack\Propstack\Widget_Base;

/**
 * Object to handle the broker field widget.
 */
class Broker_Field extends Widget_Base {
	/**
	 * The internal name for this object.
	 *
	 * @var string
	 */
	protected string $name = 'widget_broker_field';

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
	protected string $gutenberg = '\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Broker_Field';

	/**
	 * Instance of this object.
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
	 * Return the rendered widget.
	 *
	 * @param array<string,mixed> $attributes Attributes to configure the rendering.
	 *
	 * @return string
	 */
	public function render( array $attributes ): string {
		// bail if no field is selected.
		if ( empty( $attributes['field_name'] ) ) {
			return '';
		}

		// get the object for this request.
		$immo_object = $this->get_object_by_request();

		// bail if no object could be found.
		if ( ! $immo_object instanceof \ConnectorForPropstack\Propstack\ImmoObject ) {
			return '';
		}

		// bail if requested post-type is not ours.
		if ( get_post_type( $immo_object->get_id() ) !== ImmoObject::get_instance()->get_name() ) {
			return '';
		}

		// get the field object.
		$field = Broker::get_instance()->get_field_by_name( $attributes['field_name'] );

		// bail if field could not be found.
		if ( ! $field instanceof Field_Base ) {
			return '';
		}

		// bail if the field should not be shown.
		if ( 1 === absint( get_option( 'propstack_connector_fields_' . Broker::get_instance()->get_name() . '_' . $field->get_name() . '_disabled' ) ) ) {
			return '';
		}

		// get the assigned term on this object for this taxonomy.
		$term = wp_get_object_terms( $immo_object->get_id(), Broker::get_instance()->get_name() );

		// bail if no terms could be found.
		if ( ! is_array( $term ) || empty( $term ) ) {
			return '';
		}

		// return the value of the given description type in this object.
		$field_value = Taxonomies::get_instance()->get_field_value( $term[0]->term_id, $field, false );

		// if we got no text and are in an editor, show a placeholder.
		if ( empty( $field_value ) && Helper::is_rest_request() ) {
			$field_value = '<em>' . __( 'Empty field.', 'connector-for-propstack' ) . '</em>';
		}

		// return the template with this value.
		ob_start();
		include Templates::get_instance()->get_template( 'parts/part-field.php' );
		$content = ob_get_clean();
		if ( ! $content ) {
			return '';
		}
		return $content;
	}
}

<?php
/**
 * File to handle the field widget.
 *
 * This widget is used to display a single immo object field value.
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
use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use ConnectorForPropstack\Propstack\Widget_Base;

/**
 * Object to handle the field widget.
 */
class Field extends Widget_Base {
	/**
	 * The internal name for this object.
	 *
	 * @var string
	 */
	protected string $name = 'widget_field';

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
	protected string $gutenberg = '\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Field';

	/**
	 * Instance of this object.
	 *
	 * @var ?Field
	 */
	private static ?Field $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Field {
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
		$field = Fields::get_instance()->get_field_by_name( $attributes['field_name'] );

		// bail if field could not be found.
		if ( ! $field instanceof Field_Base ) {
			return '';
		}

		// get the assigned object type term on this object.
		$object_type_term = wp_get_object_terms( $immo_object->get_id(), ObjectType::get_instance()->get_name() );

		// bail if no object type term could be found.
		if ( ! is_array( $object_type_term ) || empty( $object_type_term ) ) {
			return '';
		}

		// get the cache for disabled fields.
		$disabled_fields = \ConnectorForPropstack\Plugin\Cache::get( 'disabled_fields' );

		// bail if the field should not be shown.
		if ( is_array( $disabled_fields ) && in_array( $field->get_name(), $disabled_fields, true ) ) {
			return '';
		}

		$show_field = true;
		/**
		 * Filter whether a field should be shown in the frontend.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param bool $show_field Whether the field should be shown in frontend.
		 * @param Field_Base $field The field.
		 * @param \ConnectorForPropstack\Propstack\ImmoObject $immo_object The object.
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		if ( ! apply_filters( 'cfprop_show_field_in_frontend', $show_field, $field, $immo_object ) ) {
			return '';
		}

		// return the value of the given description type in this object.
		$field_value = Fields::get_instance()->get_field_value( $immo_object->get_id(), $field, false );

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

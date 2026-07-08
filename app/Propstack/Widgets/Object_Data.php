<?php
/**
 * File to handle the object data widget.
 *
 * This widget is used to display multiple but selected immo object field values. It uses the same template for each field.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Widgets;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Templates;
use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Fields;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use ConnectorForPropstack\Propstack\Widget_Base;

/**
 * Object to handle the object data widget.
 */
class Object_Data extends Widget_Base {
	/**
	 * The internal name for this object.
	 *
	 * @var string
	 */
	protected string $name = 'widget_object_data';

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
	protected string $gutenberg = '\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Object_Data';

	/**
	 * Instance of this object.
	 *
	 * @var ?Object_Data
	 */
	private static ?Object_Data $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Object_Data {
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
		// bail if no object_data are set.
		if ( empty( $attributes['object_data'] ) ) {
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

		// collect the field labels and values for this object.
		$fields = array();
		foreach ( $attributes['object_data'] as $field_name ) {
			// bail if the value is empty.
			if ( empty( $field_name ) ) {
				continue;
			}

			// get the field object.
			$field_obj = Fields::get_instance()->get_field_by_name( $field_name );

			// bail if the field object could not be found.
			if ( ! $field_obj instanceof Field_Base ) {
				continue;
			}

			// get the value of the field.
			$value = Fields::get_instance()->get_field_value( $immo_object->get_id(), $field_obj );

			// bail if no value could be found.
			if ( empty( $value ) ) {
				continue;
			}

			// add the field to the list.
			$fields[] = array(
				'label' => $field_obj->get_label(),
				'value' => $value,
			);
		}

		/**
		 * Filter the fields for the object data widget.
		 *
		 * @param array<int,array<string,mixed>> $fields The fields for the single widget.
		 */
		$fields = apply_filters( 'cfprop_object_data_widget_attributes', $fields );

		/**
		 * Filter the attributes for the object data widget.
		 *
		 * @param array<string,mixed> $attributes The attributes for the single widget.
		 */
		$attributes = apply_filters( 'cfprop_object_data_widget_attributes', $attributes );

		// return the template with this value.
		ob_start();

		/**
		 * Run custom actions before the output of the archive listing.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array $attributes List of attributes.
		 */
		do_action( 'cfprop_get_template_before', $attributes );

		// use the template to generate the output.
		include Templates::get_instance()->get_template( 'parts/part-fields.php' );
		$content = ob_get_clean();
		if ( ! $content ) {
			return '';
		}
		return $content;
	}
}

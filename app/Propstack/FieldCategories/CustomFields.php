<?php
/**
 * File to handle the category for custom fields.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldCategories;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Settings;
use ConnectorForPropstack\Propstack\Custom_Fields;
use ConnectorForPropstack\Propstack\Field_Category_Base;
use WP_Post;

/**
 * Object to handle the category for custom fields.
 */
class CustomFields extends Field_Category_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = 'custom_fields';

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Custom fields', 'connector-for-propstack' );
	}

	/**
	 * Show the metabox for this field category.
	 *
	 * @param WP_Post $post The post-object.
	 *
	 * @return void
	 */
	public function show_in_metabox( WP_Post $post ): void {
		// get the custom fields object.
		$custom_fields_obj = Custom_Fields::get_instance();

		// show a hint if the custom fields of this object are saved in the format of an older version.
		if ( $custom_fields_obj->has_old_format( $post->ID ) ) {
			echo '<p>' . esc_html__( 'The custom fields of this object will be shown after the next import of objects.', 'connector-for-propstack' ) . '</p>';
			return;
		}

		// get the custom fields of this object.
		$custom_fields = $custom_fields_obj->get_fields_of_object( $post->ID );

		// bail if the object does not have any custom fields.
		if ( empty( $custom_fields ) ) {
			echo esc_html__( 'No custom fields used.', 'connector-for-propstack' );
			return;
		}

		// show each field.
		foreach ( $custom_fields as $custom_field ) {
			$this->show_single_field_in_meta_box( $post, $custom_field );
		}

		// show a hint where the custom fields could be enabled for the frontend.
		if ( $custom_fields_obj->is_output_supported() ) {
			/* translators: %1$s will be replaced by a URL. */
			echo '<p class="cfprop-custom-fields-hint">' . wp_kses_post( sprintf( __( 'Choose the custom fields to show on your website in the <a href="%1$s">settings</a>.', 'connector-for-propstack' ), esc_url( Settings::get_instance()->get_url( 'propstack_connector_fields', 'propstack_connector_fields_custom_fields' ) ) ) ) . '</p>';
			return;
		}

		// show a hint for the Pro plugin.
		if ( Helper::show_pro_hints() ) {
			/* translators: %1$s will be replaced by a URL. */
			echo '<p class="cfprop-pro-hint">' . wp_kses_post( sprintf( __( 'The output of custom fields on your website is only possible with <a href="%1$s" target="_blank">Connector for Propstack Pro</a>.', 'connector-for-propstack' ), esc_url( Helper::get_pro_url() ) ) ) . '</p>';
		}
	}
}

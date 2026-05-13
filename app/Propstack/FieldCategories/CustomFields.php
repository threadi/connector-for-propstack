<?php
/**
 * File to handle the basic category for fields.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldCategories;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Category_Base;
use WP_Post;

/**
 * Object to handle the basic category for fields.
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
		// get the custom fields.
		$custom_fields = get_post_meta( $post->ID, 'custom_fields', true );

		// bail if the list is empty.
		if ( empty( $custom_fields ) ) {
			echo esc_html__( 'No custom fields used.', 'connector-for-propstack' );
			return;
		}

		foreach ( $custom_fields as $custom_field_name ) {
			// get the label.
			$label = get_post_meta( $post->ID, $custom_field_name, true );

			// get the value.
			$value = get_post_meta( $post->ID, $custom_field_name . '_pretty_value', true );

			// show the field.
			?>
			<fieldset>
				<label><?php echo esc_html( $label ); ?></label>
				<div>
					<p>
						<?php
						// show the value.
						echo wp_kses_post( $value );
						?>
					</p>
				</div>
			</fieldset>
			<?php
		}
	}
}

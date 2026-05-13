<?php
/**
 * File to handle basic functions for field categories.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base;
use WP_Post;

/**
 * Base object for each field category.
 */
class Field_Category_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Return the category name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return '';
	}

	/**
	 * Return the metabox callback function.
	 *
	 * @return callable
	 */
	public function get_metabox_callback(): callable {
		return array( $this, 'show_in_metabox' );
	}

	/**
	 * Show the metabox with fields in this category.
	 *
	 * @param WP_Post $post The post-object.
	 *
	 * @return void
	 */
	public function show_in_metabox( WP_Post $post ): void {
		// get the object type for this object.
		$object_type = ObjectType::get_instance()->get_object_type_by_object_post_id( $post->ID );

		// bail if the object type is not set.
		if ( ! $object_type instanceof Object_Type_Base ) {
			return;
		}

		// list the fields in the metabox.
		foreach ( $object_type->get_fields_by_category( $this->get_name() ) as $field ) {
			// bail if this field should be hidden.
			if ( $field->hide_in_backend() || $field->hide() ) {
				continue;
			}

			// show it.
			$this->show_single_field_in_meta_box( $post, $field );
		}
	}

	/**
	 * Show a single field in a meta-box in the backend.
	 *
	 * @param WP_Post    $post  The post-object.
	 * @param Field_Base $field The field object.
	 *
	 * @return void
	 */
	protected function show_single_field_in_meta_box( WP_Post $post, Field_Base $field ): void {
		// get the value.
		$value = Fields::get_instance()->get_field_value( $post->ID, $field, false );

		// collect the classes for the fieldset wrapper.
		$classes = array(
			sanitize_title( $field->get_api() ),
		);

		// set class if the field is hidden in the frontend.
		if ( $field->hide_in_frontend() ) {
			$classes[] = 'hidden-in-frontend';
		}

		// set a title.
		$title = $field->get_label();
		if ( $field->hide_in_frontend() ) {
			$title .= ' (' . __( 'hidden in frontend', 'connector-for-propstack' ) . ')';
		}

		// show the field.
		?>
		<fieldset class="field-<?php echo esc_attr( implode( ' ', $classes ) ); ?>" title="<?php echo esc_attr( $title ); ?>">
			<label><?php echo esc_html( $field->get_label() ); ?></label>
			<div>
				<p>
					<?php
					// show title, if set on the field.
					$custom_label = get_post_meta( absint( get_the_ID() ), $field->get_name() . '_label', true );
					if ( ! empty( $custom_label ) ) {
						echo '<strong>' . esc_html( $custom_label ) . '</strong><br>';
					}

					// show the value.
					echo wp_kses_post( $value );

					/**
					 * Run additional tasks during the view of a single field in a metabox in the backend.
					 *
					 * @since 1.0.0 Available since 1.0.0.
					 * @param WP_Post $post The post.
					 * @param Field_Base $field The field.
					 * @param mixed $value The value.
					 */
					do_action( 'cfprop_object_field_metabox', $post, $field, $value );
					?>
				</p>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Return the category type for this category.
	 *
	 * @return Field_Category_Type_Base
	 */
	public function get_category_type(): Field_Category_Type_Base {
		return new FieldCategoryTypes\ObjectData();
	}

	/**
	 * Return whether the metabox should be hidden (true) or not (false).
	 *
	 * @return bool
	 */
	public function hide_meta_box(): bool {
		return false;
	}
}

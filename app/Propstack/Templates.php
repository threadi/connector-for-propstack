<?php
/**
 * File to handle Propstack-specific template tasks.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Fields\Main\City;
use ConnectorForPropstack\Propstack\Fields\Main\ZipCode;
use ConnectorForPropstack\Propstack\Taxonomies\Broker;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base;
use ConnectorForPropstack\Propstack\Widgets\Object_Data;
use WP_Error;

/**
 * Object to handle Propstack-specific template tasks.
 */
class Templates {

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Templates
	 */
	private static ?Templates $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() { }

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Templates {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		// use hooks.
		add_action( 'cfprop_template_thumbnail', array( $this, 'show_thumbnail' ) );
		add_action( 'cfprop_template_small_location', array( $this, 'show_location' ) );
		add_action( 'cfprop_template_title', array( $this, 'show_title' ) );
		add_action( 'cfprop_template_broker', array( $this, 'show_broker' ), 10, 2 );
	}

	/**
	 * Show the thumbnail for the given object.
	 *
	 * @param ImmoObject $immo_object The immo object.
	 *
	 * @return void
	 */
	public function show_thumbnail( ImmoObject $immo_object ): void {
		// get the thumbnail ID.
		$attachment_id = absint( get_post_thumbnail_id( $immo_object->get_id() ) );

		// bail if no attachment ID is set.
		if ( 0 === $attachment_id ) {
			return;
		}

		// show the thumbnail.
		echo wp_get_attachment_image( $attachment_id, array( 150, 150 ) );
	}

	/**
	 * Show the location of the given object.
	 *
	 * @param ImmoObject $immo_object The immo object.
	 *
	 * @return void
	 */
	public function show_location( ImmoObject $immo_object ): void {
		// get the zip code for this object.
		$zip_code = Fields::get_instance()->get_field_value( $immo_object->get_id(), new ZipCode() );

		// get the city for this object.
		$city = Fields::get_instance()->get_field_value( $immo_object->get_id(), new City() );

		// show the location.
		echo '<p>' . esc_html( $zip_code . ' ' . $city ) . '</p>';
	}

	/**
	 * Show the location of the given object.
	 *
	 * @param ImmoObject $immo_object The immo object.
	 *
	 * @return void
	 */
	public function show_title( ImmoObject $immo_object ): void {
		echo '<h2><a href="' . esc_url( $immo_object->get_link() ) . '">' . esc_html( get_the_title( $immo_object->get_id() ) ) . '</a></h2>';
	}

	/**
	 * Show the broker.
	 *
	 * @param ImmoObject $immo_object The immo object.
	 * @param string     $category_type_name The category type name.
	 *
	 * @return void
	 */
	public function show_broker( ImmoObject $immo_object, string $category_type_name ): void {
		// get the object type term for this object.
		$object_type_terms = wp_get_object_terms( $immo_object->get_id(), Broker::get_instance()->get_name() );

		// bail if no object type term is set.
		if ( empty( $object_type_terms ) || $object_type_terms instanceof WP_Error ) {
			return;
		}

		// get the fields to list in the loop item.
		$fields = array();
		foreach ( Broker::get_instance()->get_fields() as $field ) {
			// bail if the field should not be able to configure or visible in the frontend.
			if ( $field->hide() || $field->hide_in_frontend() || empty( $field->get_name() ) ) {
				continue;
			}

			// bail if the field should not be shown.
			if ( 1 === absint( get_option( 'propstack_connector_fields_' . Broker::get_instance()->get_name() . '_' . $field->get_name() . '_disabled' ) ) ) {
				continue;
			}

			// add the field to the list.
			$fields[] = array(
				'label' => $field->get_label(),
				'value' => Taxonomies::get_instance()->get_field_value( $object_type_terms[0]->term_id, $field, false ),
			);
		}

		// bail if no fields are set.
		if ( empty( $fields ) ) {
			return;
		}

		// return the template with this value.
		ob_start();
		include \ConnectorForPropstack\Plugin\Templates::get_instance()->get_template( 'parts/part-fields.php' );
		$content = ob_get_clean();
		if ( ! $content ) {
			return;
		}

		// get the category type object.
		$category_type = FieldCategories::get_instance()->get_category_type_by_name( $category_type_name );

		// bail if the category type is not set.
		if ( ! $category_type instanceof Field_Category_Type_Base ) {
			return;
		}

		?>
			<div class="properties-category properties-category-<?php echo esc_attr( $category_type_name ); ?>">
				<h3><?php echo esc_html( $category_type->get_label() ); ?></h3>
				<?php echo wp_kses_post( $content ); ?>
			</div>
		<?php
	}
	/**
	 * Show the object data of the given object for a given category type.
	 *
	 * @param \ConnectorForPropstack\Propstack\ImmoObject $immo_object        The immo object.
	 * @param string                                      $category_type_name The category type name.
	 * @param array<string,mixed>                         $attributes         The used attributes.
	 *
	 * @return void
	 */
	public function show_category_type( \ConnectorForPropstack\Propstack\ImmoObject $immo_object, string $category_type_name, array $attributes ): void {
		// get the object type term for this object.
		$object_type_terms = wp_get_object_terms( $immo_object->get_id(), ObjectType::get_instance()->get_name() );

		// bail if no object type term is set.
		if ( empty( $object_type_terms ) || $object_type_terms instanceof WP_Error ) {
			return;
		}

		// get the object type object for this term.
		$object_type = ObjectType::get_instance()->get_object_type_by_slug( $object_type_terms[0]->slug );

		// bail if the object type could not be loaded.
		if ( ! $object_type instanceof Object_Type_Base ) {
			return;
		}

		// get the category type object.
		$category_type = FieldCategories::get_instance()->get_category_type_by_name( $category_type_name );

		// bail if the category type is not set.
		if ( ! $category_type instanceof Field_Category_Type_Base ) {
			return;
		}

		// set the fields.
		$attributes['object_data'] = $this->get_fields_by_category_type( $category_type, $object_type );

		// bail if no fields are set.
		if ( empty( $attributes['object_data'] ) ) {
			return;
		}

		// show them.
		?>
		<div class="properties-category properties-category-<?php echo esc_attr( $category_type_name ); ?>">
			<h3><?php echo esc_html( $category_type->get_label() ); ?></h3>
			<div class="properties"><?php echo wp_kses_post( Object_Data::get_instance()->render( $attributes ) ); ?></div>
		</div>
		<?php
	}

	/**
	 * Return the list of fields for the given category type on the given object type.
	 *
	 * @param Field_Category_Type_Base $category_type_to_check The category type to check.
	 * @param Object_Type_Base         $object_type The object type.
	 *
	 * @return array<int,string>
	 */
	public function get_fields_by_category_type( Field_Category_Type_Base $category_type_to_check, Object_Type_Base $object_type ): array {
		// get the category types for this object type.
		$category_types = array();

		// check the taxonomy-specific fields.
		foreach ( FieldCategories::get_instance()->get_categories_as_objects() as $field_category ) {
			// get the name of the category type for this category.
			$category_type      = $field_category->get_category_type();
			$category_type_name = $category_type->get_name();
			if ( $category_type_name !== $category_type_to_check->get_name() ) {
				continue;
			}

			// check if this taxonomy has fields of this type.
			$fields = $object_type->get_fields_by_category( $field_category->get_name() );

			// bail if no fields are set.
			if ( empty( $fields ) ) {
				continue;
			}

			// add this category with the fields to the list of category types.
			if ( empty( $category_types[ $category_type_name ] ) ) {
				$category_types[ $category_type_name ] = array();
			}
			$category_types[ $category_type_name ] = array_merge( $category_types[ $category_type_name ], $fields );
		}

		$fields = array();

		// check each category type for used fields and show them.
		foreach ( $category_types as $field_objects ) {
			// collect for each field the value from the called immo object.
			foreach ( $field_objects as $field_object ) {
				// skip an empty field name.
				if ( $field_object->hide() || $field_object->hide_in_frontend() || empty( $field_object->get_name() ) ) {
					continue;
				}
				$fields[] = $field_object->get_name();
			}
		}

		// return the resulting fields.
		return $fields;
	}
}

<?php
/**
 * File to handle our own custom taxonomy "cfprop_object_type".
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Taxonomies;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base;
use ConnectorForPropstack\Propstack\Taxonomy;
use ConnectorForPropstack\Propstack\Term_Base;
use WP_Term;

/**
 * Object to handle this custom taxonomy.
 */
class ObjectType extends Taxonomy {
	/**
	 * Define the taxonomy name.
	 *
	 * @var string
	 */
	protected string $name = 'cfprop_object_type';

	/**
	 * The API field name to assign a term of this taxonomy to an object.
	 *
	 * @var string
	 */
	protected string $api_field = 'rs_type';

	/**
	 * Instance of this object.
	 *
	 * @var ?ObjectType
	 */
	private static ?ObjectType $instance = null;

	/**
	 * Constructor for this object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() { }

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): ObjectType {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return the labels for this taxonomy.
	 *
	 * @return array<string,string>
	 */
	protected function get_labels(): array {
		return array(
			'name'          => _x( 'Object type', 'taxonomy general name', 'connector-for-propstack' ),
			'singular_name' => _x( 'Object type', 'taxonomy singular name', 'connector-for-propstack' ),
			'search_items'  => __( 'Search Object type', 'connector-for-propstack' ),
			'edit_item'     => __( 'Edit object type', 'connector-for-propstack' ),
			'update_item'   => __( 'Update object type', 'connector-for-propstack' ),
			'menu_name'     => __( 'Object Types', 'connector-for-propstack' ),
			'back_to_items' => '&larr; ' . __( 'Go to all object types', 'connector-for-propstack' ),
		);
	}

	/**
	 * Return the list of available object types.
	 *
	 * @return array<int,string>
	 */
	private function get_object_types(): array {
		$object_types = array(
			'\ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Apartment',
			'\ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Garage',
			'\ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\House',
		);

		/**
		 * Filter the list of available object types.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $object_types List of object types.
		 */
		return apply_filters( 'cfprop_object_types', $object_types );
	}

	/**
	 * Return the list of available object types as objects.
	 *
	 * @return array<int,Object_Type_Base>
	 */
	public function get_object_types_as_objects(): array {
		// prepare the list.
		$list = array();

		foreach ( $this->get_object_types() as $object_type_name ) {
			// bail if given class does not exist.
			if ( ! class_exists( $object_type_name ) ) {
				continue;
			}

			// create the object.
			$obj = new $object_type_name();

			// bail if the object is not an instance of "Object_Type".
			if ( ! $obj instanceof Object_Type_Base ) {
				continue;
			}

			// add it to the list.
			$list[] = $obj;
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Return an object type object by its slug.
	 *
	 * @param string $slug The slug of the object type.
	 *
	 * @return Object_Type_Base|false
	 */
	public function get_object_type_by_slug( string $slug ): Object_Type_Base|false {
		foreach ( $this->get_object_types_as_objects() as $object_type ) {
			if ( $slug === $object_type->get_slug() ) {
				return $object_type;
			}
		}
		return false;
	}

	/**
	 * Return the object type object for a given post-ID.
	 *
	 * @param int $post_id The post-ID.
	 *
	 * @return Object_Type_Base|false
	 */
	public function get_object_type_by_object_post_id( int $post_id ): Object_Type_Base|false {
		// get the object types.
		$object_types = wp_get_post_terms( $post_id, self::get_instance()->get_name() );

		// get an array.
		if ( ! is_array( $object_types ) ) {
			return false;
		}

		// bail if no object types are set.
		if ( empty( $object_types ) ) {
			return false;
		}

		// get the object type.
		$object_type = $object_types[0];

		// get the object type object by the given slug.
		$object_type_object = $this->get_object_type_by_slug( $object_type->slug );

		// bail if the object type object is not set.
		if ( ! $object_type_object instanceof Object_Type_Base ) {
			return false;
		}

		// return the object type object.
		return $object_type_object;
	}

	/**
	 * Return the list of default terms for object types from Propstack.
	 *
	 * Format per entry:
	 * - api => the value in the Propstack API.
	 * - slug => the WordPress-internal slug.
	 * - label => the label for show in WordPress.
	 *
	 * @return array<int,mixed>
	 */
	protected function get_default_terms(): array {
		// prepare the list.
		$terms = array();

		// add the default terms.
		foreach ( $this->get_object_types_as_objects() as $object_type ) {
			$terms[] = array(
				'api'   => $object_type->get_api(),
				'slug'  => $object_type->get_slug(),
				'label' => $object_type->get_label(),
			);
		}

		/**
		 * Filter the default terms of object types.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,mixed> $terms List of terms.
		 */
		return apply_filters( 'cfprop_object_typ_default_terms', $terms );
	}

	/**
	 * Return the list of terms for this taxonomy.
	 *
	 * @return array<int,Term_Base>
	 */
	public function get_terms(): array {
		// create the query for terms.
		$query = array(
			'taxonomy'   => $this->get_name(),
			'hide_empty' => false,
		);

		$instance = $this;
		/**
		 * Filter the query for terms on a single taxonomy.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<string,mixed> $query The query.
		 * @param Taxonomy $instance The taxonomy object.
		 */
		$query = apply_filters( 'cfprop_taxonomy_terms_query', $query, $instance );

		// run the query.
		$terms = get_terms( $query );

		// bail if terms are not an array.
		if ( ! is_array( $terms ) ) {
			return array();
		}

		// create a "Term_Base" object for every term.
		$list = array();
		foreach ( $terms as $term ) {
			// bail if the term is not "WP_Term".
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			// get the object for this term slug.
			$obj = $this->get_object_type_by_slug( $term->slug );

			// bail if the object could not be found.
			if ( ! $obj instanceof Term_Base ) {
				continue;
			}

			// add settings on the object.
			$obj->set_id( $term->term_id );
			$obj->set_name( $term->name );

			// add the object to the list.
			$list[] = $obj;
		}

		// return the list.
		return $list;
	}

	/**
	 * Return the list of all fields in the object types.
	 *
	 * @return array<int,Field_Base>
	 */
	public function get_fields(): array {
		// prepare a list.
		$list = array();

		// get the fields.
		foreach ( $this->get_object_types_as_objects() as $object_type ) {
			$list = array_merge( $list, $object_type->get_fields() );
		}

		// return the resulting list.
		return $list;
	}
}

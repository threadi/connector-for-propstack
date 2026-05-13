<?php
/**
 * File for handling the field categories.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle the field categories.
 */
class FieldCategories {
	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?FieldCategories
	 */
	private static ?FieldCategories $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): FieldCategories {
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
		foreach ( $this->get_category_types_as_objects() as $category_type ) {
			add_action( 'cfprop_template_' . $category_type->get_name(), array( Templates::get_instance(), 'show_category_type' ), 10, 3 );
		}
	}

	/**
	 * Return the list of field categories with their class names.
	 *
	 * @return array<int,string>
	 */
	private function get_categories(): array {
		$categories = array(
			'\ConnectorForPropstack\Propstack\FieldCategories\Address',
			'\ConnectorForPropstack\Propstack\FieldCategories\Api',
			'\ConnectorForPropstack\Propstack\FieldCategories\Basic',
			'\ConnectorForPropstack\Propstack\FieldCategories\Broker',
			'\ConnectorForPropstack\Propstack\FieldCategories\BuildingConditions',
			'\ConnectorForPropstack\Propstack\FieldCategories\CustomFields',
			'\ConnectorForPropstack\Propstack\FieldCategories\Dates',
			'\ConnectorForPropstack\Propstack\FieldCategories\Descriptions',
			'\ConnectorForPropstack\Propstack\FieldCategories\Energy',
			'\ConnectorForPropstack\Propstack\FieldCategories\Features',
			'\ConnectorForPropstack\Propstack\FieldCategories\Geo',
			'\ConnectorForPropstack\Propstack\FieldCategories\Images',
			'\ConnectorForPropstack\Propstack\FieldCategories\Languages',
			'\ConnectorForPropstack\Propstack\FieldCategories\Other',
			'\ConnectorForPropstack\Propstack\FieldCategories\Prices',
			'\ConnectorForPropstack\Propstack\FieldCategories\Properties',
			'\ConnectorForPropstack\Propstack\FieldCategories\Rent',
		);

		/**
		 * Filter the list of available field categories.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $categories List of field categories.
		 */
		return apply_filters( 'cfprop_field_categories', $categories );
	}

	/**
	 * Return the list of field categories as objects.
	 *
	 * @return array<int,Field_Category_Base>
	 */
	public function get_categories_as_objects(): array {
		// prepare the list.
		$list = array();

		// add them to the list.
		foreach ( $this->get_categories() as $category_class_name ) {
			// bail if class does not exist.
			if ( ! class_exists( $category_class_name ) ) {
				continue;
			}

			// get the object.
			$obj = new $category_class_name();

			// bail if the object is not an instance of type "Field_Category_Base".
			if ( ! $obj instanceof Field_Category_Base ) {
				continue;
			}

			// add it to the list.
			$list[] = $obj;
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Return the list of category types as objects.
	 *
	 * @return array<int,Field_Category_Type_Base>
	 */
	public function get_category_types_as_objects(): array {
		// prepare the list.
		$list = array();

		// add them to the list.
		foreach ( $this->get_category_types() as $category_class_name ) {
			// bail if class does not exist.
			if ( ! class_exists( $category_class_name ) ) {
				continue;
			}

			// get the object.
			$obj = new $category_class_name();

			// bail if the object is not an instance of type "Field_Category_Type_Base".
			if ( ! $obj instanceof Field_Category_Type_Base ) {
				continue;
			}

			// add it to the list.
			$list[] = $obj;
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Return the list of available category types.
	 *
	 * @return array<int,string>
	 */
	private function get_category_types(): array {
		$category_types = array(
			'\ConnectorForPropstack\Propstack\FieldCategoryTypes\Broker',
			'\ConnectorForPropstack\Propstack\FieldCategoryTypes\Descriptions',
			'\ConnectorForPropstack\Propstack\FieldCategoryTypes\Energy',
			'\ConnectorForPropstack\Propstack\FieldCategoryTypes\Locations',
			'\ConnectorForPropstack\Propstack\FieldCategoryTypes\ObjectData',
		);

		/**
		 * Filter the list of available field category types.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $category_types List of field category types.
		 */
		return apply_filters( 'cfprop_field_categories', $category_types );
	}

	/**
	 * Return a category type by its name.
	 *
	 * @param string $category_type_name The category type name.
	 *
	 * @return Field_Category_Type_Base|false
	 */
	public function get_category_type_by_name( string $category_type_name ): Field_Category_Type_Base|false {
		foreach ( $this->get_category_types_as_objects() as $category_type ) {
			if ( $category_type_name === $category_type->get_name() ) {
				return $category_type;
			}
		}
		return false;
	}
}

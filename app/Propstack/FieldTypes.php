<?php
/**
 * File for handling the field types.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle the field types.
 */
class FieldTypes {
	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?FieldTypes
	 */
	private static ?FieldTypes $instance = null;

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
	public static function get_instance(): FieldTypes {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return the list of field categories with their class names.
	 *
	 * @return array<int,string>
	 */
	private function get_field_types(): array {
		$field_types = array(
			'\ConnectorForPropstack\Propstack\FieldTypes\ArrayField',
			'\ConnectorForPropstack\Propstack\FieldTypes\BooleanField',
			'\ConnectorForPropstack\Propstack\FieldTypes\CodeField',
			'\ConnectorForPropstack\Propstack\FieldTypes\EditorField',
			'\ConnectorForPropstack\Propstack\FieldTypes\FloatField',
			'\ConnectorForPropstack\Propstack\FieldTypes\HtmlField',
			'\ConnectorForPropstack\Propstack\FieldTypes\IntegerField',
			'\ConnectorForPropstack\Propstack\FieldTypes\NumberField',
			'\ConnectorForPropstack\Propstack\FieldTypes\PriceField',
			'\ConnectorForPropstack\Propstack\FieldTypes\StringField',
		);

		/**
		 * Filter the list of available field types.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $field_types List of field types.
		 */
		return apply_filters( 'cfprop_field_types', $field_types );
	}

	/**
	 * Return the list of field types as objects.
	 *
	 * @return array<int,FieldType_Base>
	 */
	public function get_field_types_as_objects(): array {
		// prepare the list.
		$list = array();

		// add them to the list.
		foreach ( $this->get_field_types() as $category_class_name ) {
			// bail if class does not exist.
			if ( ! class_exists( $category_class_name ) ) {
				continue;
			}

			// get the object.
			$obj = new $category_class_name();

			// bail if the object is not an instance of type "FieldType_Base".
			if ( ! $obj instanceof FieldType_Base ) {
				continue;
			}

			// add it to the list.
			$list[] = $obj;
		}

		// return the resulting list.
		return $list;
	}


	/**
	 * Return a field type by its name.
	 *
	 * @param string $field_type_name The field type name.
	 *
	 * @return FieldType_Base|false
	 */
	public function get_field_type_by_name( string $field_type_name ): FieldType_Base|false {
		foreach ( $this->get_field_types_as_objects() as $field_type ) {
			if ( $field_type_name === $field_type->get_name() ) {
				return $field_type;
			}
		}
		return false;
	}
}

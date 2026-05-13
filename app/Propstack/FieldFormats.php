<?php
/**
 * File for handling the field formats.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle the field formats.
 */
class FieldFormats {
	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?FieldFormats
	 */
	private static ?FieldFormats $instance = null;

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
	public static function get_instance(): FieldFormats {
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
	private function get_field_formats(): array {
		$field_formats = array(
			'\ConnectorForPropstack\Propstack\FieldFormats\DateTime',
			'\ConnectorForPropstack\Propstack\FieldFormats\Email',
			'\ConnectorForPropstack\Propstack\FieldFormats\Kilometer',
			'\ConnectorForPropstack\Propstack\FieldFormats\Listing',
			'\ConnectorForPropstack\Propstack\FieldFormats\Minutes',
			'\ConnectorForPropstack\Propstack\FieldFormats\PhoneNumber',
			'\ConnectorForPropstack\Propstack\FieldFormats\SquareMeter',
			'\ConnectorForPropstack\Propstack\FieldFormats\Thumbnail',
			'\ConnectorForPropstack\Propstack\FieldFormats\None',
		);

		/**
		 * Filter the list of available field formats.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $field_types List of field formats.
		 */
		return apply_filters( 'cfprop_field_types', $field_formats );
	}

	/**
	 * Return the list of field formats as objects.
	 *
	 * @return array<int,FieldFormat_Base>
	 */
	public function get_field_formats_as_objects(): array {
		// prepare the list.
		$list = array();

		// add them to the list.
		foreach ( $this->get_field_formats() as $category_class_name ) {
			// bail if class does not exist.
			if ( ! class_exists( $category_class_name ) ) {
				continue;
			}

			// get the object.
			$obj = new $category_class_name();

			// bail if the object is not an instance of type "FieldFormat_Base".
			if ( ! $obj instanceof FieldFormat_Base ) {
				continue;
			}

			// add it to the list.
			$list[] = $obj;
		}

		// return the resulting list.
		return $list;
	}


	/**
	 * Return a field format by its name.
	 *
	 * @param string $field_format_name The field format name.
	 *
	 * @return FieldFormat_Base|false
	 */
	public function get_field_format_by_name( string $field_format_name ): FieldFormat_Base|false {
		foreach ( $this->get_field_formats_as_objects() as $field_format ) {
			if ( $field_format_name === $field_format->get_name() ) {
				return $field_format;
			}
		}
		return false;
	}
}

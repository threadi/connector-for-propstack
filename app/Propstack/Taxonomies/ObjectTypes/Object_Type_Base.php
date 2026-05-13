<?php
/**
 * File to handle basic functions for any object type.
 *
 * Every object type needs a list of fields to use. They will be imported and show in backend and frontend.
 * It should also have a list of fields, which must be disabled for the view in the frontend.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Fields\Main\DescriptionNote;
use ConnectorForPropstack\Propstack\Term_Base;

/**
 * Base object for each object type.
 */
class Object_Type_Base extends Term_Base {

	/**
	 * Define the API name.
	 *
	 * @var string
	 */
	protected string $api = '';

	/**
	 * Return the API name.
	 *
	 * @return string
	 */
	public function get_api(): string {
		return $this->api;
	}

	/**
	 * Return the label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return '';
	}

	/**
	 * Return the fields.
	 *
	 * @return array<int,Field_Base>
	 */
	public function get_fields(): array {
		return array();
	}

	/**
	 * Return the default content field for "post_content" for this object type.
	 *
	 * @return Field_Base
	 */
	public function get_default_content_field(): Field_Base {
		return new DescriptionNote();
	}

	/**
	 * Return a description for this object type.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return '';
	}

	/**
	 * Return whether a given field is disabled by default for this object type.
	 *
	 * @param Field_Base $field The field to check.
	 *
	 * @return bool
	 */
	public function is_field_default_disabled( Field_Base $field ): bool {
		// get the name of the default fields.
		$names = array_map(
			static fn( $item ) => $item->get_name(),
			$this->get_default_disabled_fields()
		);

		// return the result.
		return in_array( $field->get_name(), $names, true );
	}

	/**
	 * Return the list of default disabled fields for this object type.
	 *
	 * @return array<int,Field_Base>
	 */
	protected function get_default_disabled_fields(): array {
		return array();
	}

	/**
	 * Use label as name for object types.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->get_label();
	}

	/**
	 * Return the most important fields as objects.
	 *
	 * @return array<int,Field_Base>
	 */
	public function get_important_fields_as_objects(): array {
		return array();
	}

	/**
	 * Return fields of a given category.
	 *
	 * @param string $category The category name.
	 *
	 * @return array<int,Field_Base>
	 */
	public function get_fields_by_category( string $category ): array {
		$list = array();
		foreach ( $this->get_fields() as $field ) {
			if ( $category === $field->get_category()->get_name() ) {
				$list[] = $field;
			}
		}
		return $list;
	}
}

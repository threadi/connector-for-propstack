<?php
/**
 * File to handle a single category type for categories of fields.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldCategoryTypes;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Category_Type_Base;

/**
 * Object to handle the object data category for fields.
 */
class ObjectData extends Field_Category_Type_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = 'object_data';

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Object data', 'connector-for-propstack' );
	}
}

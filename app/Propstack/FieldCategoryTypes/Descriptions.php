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
 * Object to handle the address category for fields.
 */
class Descriptions extends Field_Category_Type_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = 'descriptions';

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Object descriptions', 'connector-for-propstack' );
	}
}

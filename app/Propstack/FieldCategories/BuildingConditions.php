<?php
/**
 * File to handle the building conditions category for fields.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldCategories;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Category_Base;
use ConnectorForPropstack\Propstack\Field_Category_Type_Base;

/**
 * Object to handle the building conditions category for fields.
 */
class BuildingConditions extends Field_Category_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = 'building_conditions';

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Building Conditions', 'connector-for-propstack' );
	}

	/**
	 * Return the category type for this category.
	 *
	 * @return Field_Category_Type_Base
	 */
	public function get_category_type(): Field_Category_Type_Base {
		return new \ConnectorForPropstack\Propstack\FieldCategoryTypes\Features();
	}
}

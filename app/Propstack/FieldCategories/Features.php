<?php
/**
 * File to handle the feature category for fields.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldCategories;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Category_Base;
use ConnectorForPropstack\Propstack\Field_Category_Type_Base;

/**
 * Object to handle the feature category for fields.
 */
class Features extends Field_Category_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = 'features';

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Features', 'connector-for-propstack' );
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

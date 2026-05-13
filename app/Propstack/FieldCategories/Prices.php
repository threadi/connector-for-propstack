<?php
/**
 * File to handle the prices category for fields.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldCategories;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Category_Base;

/**
 * Object to handle the prices category for fields.
 */
class Prices extends Field_Category_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = 'prices';

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Prices', 'connector-for-propstack' );
	}
}

<?php
/**
 * File to handle a field.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Fields\Main;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Field_Category_Base;

/**
 * Object to handle a single field.
 */
class Floorplans extends Field_Base {
	/**
	 * The API name of the field.
	 *
	 * @var string
	 */
	protected string $api = 'floorplans';

	/**
	 * The internal name of this field.
	 *
	 * @var string
	 */
	protected string $name = 'floorplans';

	/**
	 * The WordPress-compatible type for the field (e.g., 'boolean', 'string', 'number', 'array').
	 *
	 * @var string
	 */
	protected string $type = 'array';

	/**
	 * The output format, if different from the type.
	 *
	 * @var string
	 */
	protected string $output_format = 'listing';

	/**
	 * Return the field label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return 'floorplans';
	}

	/**
	 * Return the category of this field.
	 *
	 * @return Field_Category_Base
	 */
	public function get_category(): Field_Category_Base {
		return new \ConnectorForPropstack\Propstack\FieldCategories\Other();
	}
}

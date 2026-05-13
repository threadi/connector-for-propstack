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
class PlotNumber extends Field_Base {
	/**
	 * The API name of the field.
	 *
	 * @var string
	 */
	protected string $api = 'plot_number';

	/**
	 * The internal name of this field.
	 *
	 * @var string
	 */
	protected string $name = 'plot_number';

	/**
	 * Return the field label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Plot number', 'connector-for-propstack' );
	}

	/**
	 * Return the category of this field.
	 *
	 * @return Field_Category_Base
	 */
	public function get_category(): Field_Category_Base {
		return new \ConnectorForPropstack\Propstack\FieldCategories\Properties();
	}
}

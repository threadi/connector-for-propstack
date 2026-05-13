<?php
/**
 * File to handle a field.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Fields\Broker;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Field_Category_Base;

/**
 * Object to handle a single field.
 */
class Position extends Field_Base {
	/**
	 * The API name of the field.
	 *
	 * @var string
	 */
	protected string $api = 'position';

	/**
	 * The internal name of this field.
	 *
	 * @var string
	 */
	protected string $name = 'position';

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Position', 'connector-for-propstack' );
	}

	/**
	 * Return the category of this field.
	 *
	 * @return Field_Category_Base
	 */
	public function get_category(): Field_Category_Base {
		return new \ConnectorForPropstack\Propstack\FieldCategories\Broker();
	}
}

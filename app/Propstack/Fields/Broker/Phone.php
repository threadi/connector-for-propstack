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
class Phone extends Field_Base {
	/**
	 * The API name of the field.
	 *
	 * @var string
	 */
	protected string $api = 'phone';

	/**
	 * The internal name of this field.
	 *
	 * @var string
	 */
	protected string $name = 'phone';

	/**
	 * The output format, if different from type.
	 *
	 * @var string
	 */
	protected string $output_format = 'phone_number';

	/**
	 * Hide this field in the frontend.
	 *
	 * @var bool
	 */
	protected bool $hide_in_frontend = true;

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Phone', 'connector-for-propstack' );
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

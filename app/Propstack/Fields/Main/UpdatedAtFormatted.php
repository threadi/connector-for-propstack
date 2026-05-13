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
class UpdatedAtFormatted extends Field_Base {
	/**
	 * The API name of the field.
	 *
	 * @var string
	 */
	protected string $api = 'updated_at_formatted';

	/**
	 * The internal name of the field.
	 *
	 * @var string
	 */
	protected string $name = 'updated_at_formatted';

	/**
	 * Hide this field in the backend and the frontend. It will only be used for functional tasks.
	 *
	 * @var bool
	 */
	protected bool $hide = true;

	/**
	 * Do not disable this field.
	 *
	 * @var bool
	 */
	protected bool $do_not_configure = true;

	/**
	 * Return the field label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Updated at', 'connector-for-propstack' );
	}

	/**
	 * Return the category of this field.
	 *
	 * @return Field_Category_Base
	 */
	public function get_category(): Field_Category_Base {
		return new \ConnectorForPropstack\Propstack\FieldCategories\Dates();
	}
}

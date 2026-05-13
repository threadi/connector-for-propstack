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
class Id extends Field_Base {
	/**
	 * The API name of the field.
	 *
	 * @var string
	 */
	protected string $api = 'id';

	/**
	 * The internal name of this field.
	 *
	 * @var string
	 */
	protected string $name = 'api';

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'ID', 'connector-for-propstack' );
	}

	/**
	 * Hide this field in the backend and the frontend. It will only be used for functional tasks.
	 *
	 * @var bool
	 */
	protected bool $hide = true;

	/**
	 * Return the category of this field.
	 *
	 * @return Field_Category_Base
	 */
	public function get_category(): Field_Category_Base {
		return new \ConnectorForPropstack\Propstack\FieldCategories\Broker();
	}
}

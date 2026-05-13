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

/**
 * Object to handle a single field.
 */
class ExposeId extends Field_Base {
	/**
	 * The API name of the field.
	 *
	 * @var string
	 */
	protected string $api = 'exposee_id';

	/**
	 * The internal name of this field.
	 *
	 * @var string
	 */
	protected string $name = 'expose_id';

	/**
	 * Do not disable this field.
	 *
	 * @var bool
	 */
	protected bool $do_not_configure = true;

	/**
	 * Hide this field in the backend and the frontend. It will only be used for functional tasks.
	 *
	 * @var bool
	 */
	protected bool $hide = true;

	/**
	 * Return the field label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Expose ID', 'connector-for-propstack' );
	}
}

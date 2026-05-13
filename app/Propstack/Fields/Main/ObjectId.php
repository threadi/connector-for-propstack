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
class ObjectId extends Field_Base {
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
	protected string $name = 'object_id';

	/**
	 * The type for the field (e.g., 'boolean', 'string', 'number', 'array').
	 *
	 * @var string
	 */
	protected string $type = 'integer';

	/**
	 * Do not disable this field.
	 *
	 * @var bool
	 */
	protected bool $do_not_configure = true;

	/**
	 * Show this field in REST API.
	 *
	 * @var bool
	 */
	protected bool $show_in_rest = true;

	/**
	 * Return the field label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Object ID', 'connector-for-propstack' );
	}
}

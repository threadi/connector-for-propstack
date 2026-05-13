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
class Note extends Field_Base {
	/**
	 * The API name of the field.
	 *
	 * @var string
	 */
	protected string $api = 'note';

	/**
	 * The internal name of this field.
	 *
	 * @var string
	 */
	protected string $name = 'note';

	/**
	 * Do not disable this field.
	 *
	 * @var bool
	 */
	protected bool $do_not_configure = true;

	/**
	 * Hide this field in the frontend.
	 *
	 * @var bool
	 */
	protected bool $hide_in_frontend = true;

	/**
	 * The WordPress-compatible type for the field (e.g., 'boolean', 'string', 'number', 'array').
	 *
	 * @var string
	 */
	protected string $type = 'html';

	/**
	 * Return the field label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Note', 'connector-for-propstack' );
	}
}

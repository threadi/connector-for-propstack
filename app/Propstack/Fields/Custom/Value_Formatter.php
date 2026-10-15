<?php
/**
 * File to format the values of custom fields from Propstack.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Fields\Custom;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Custom_Fields;
use ConnectorForPropstack\Propstack\FieldType_Base;

/**
 * Object to format the value of a custom field depending on its type in Propstack.
 *
 * It is used as field type for the custom fields (via the hook "cfprop_field_type"), so the
 * values are formatted the same way in the backend and in each output in the frontend.
 */
class Value_Formatter extends FieldType_Base {
	/**
	 * The internal name of this field type.
	 *
	 * @var string
	 */
	protected string $name = 'custom_field';

	/**
	 * The output contains HTML (e.g. lists), which is already escaped.
	 *
	 * @var bool
	 */
	protected bool $with_html = true;

	/**
	 * The definition of the custom field.
	 *
	 * @var array<string,mixed>
	 */
	private array $definition;

	/**
	 * Constructor for this object.
	 *
	 * @param array<string,mixed> $definition The definition of the custom field.
	 */
	public function __construct( array $definition ) {
		$this->definition = $definition;
	}

	/**
	 * Return the formatted value.
	 *
	 * @return string
	 */
	public function get_value(): string {
		return Custom_Fields::get_instance()->format_value( $this->definition, $this->value );
	}
}

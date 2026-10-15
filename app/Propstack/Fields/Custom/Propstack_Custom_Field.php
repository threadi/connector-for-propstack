<?php
/**
 * File to handle a single custom field from Propstack.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Fields\Custom;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Custom_Fields;
use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Field_Category_Base;

/**
 * Object to handle a single custom field from Propstack.
 *
 * In contrast to all other fields, these fields are not defined by this plugin. Each object of this class
 * represents one custom field, which is configured in Propstack. Its definition (label, type and options) is
 * loaded from Propstack during the import of objects (see Custom_Fields).
 */
class Propstack_Custom_Field extends Field_Base {
	/**
	 * The API name of the field.
	 *
	 * @var string
	 */
	protected string $api = 'custom_fields';

	/**
	 * The type for the field.
	 *
	 * @var string
	 */
	protected string $type = 'custom_field';

	/**
	 * Do not configure this field per object type, it is configured in the list of custom fields.
	 *
	 * @var bool
	 */
	protected bool $do_not_configure = true;

	/**
	 * The definition of this custom field.
	 *
	 * @var array<string,mixed>
	 */
	private array $definition;

	/**
	 * Constructor for this field.
	 *
	 * @param array<string,mixed> $definition The definition of the custom field (see Custom_Fields::get_definitions()).
	 */
	public function __construct( array $definition ) {
		$this->definition = $definition;
		$this->name       = Custom_Fields::get_instance()->get_meta_key( isset( $definition['name'] ) && is_string( $definition['name'] ) ? $definition['name'] : '' );
	}

	/**
	 * Return the label of this field.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return isset( $this->definition['label'] ) && is_string( $this->definition['label'] ) ? $this->definition['label'] : $this->get_name();
	}

	/**
	 * Return the name of this field in Propstack.
	 *
	 * @return string
	 */
	public function get_propstack_name(): string {
		return isset( $this->definition['name'] ) && is_string( $this->definition['name'] ) ? $this->definition['name'] : '';
	}

	/**
	 * Return the definition of this field.
	 *
	 * @return array<string,mixed>
	 */
	public function get_definition(): array {
		return $this->definition;
	}

	/**
	 * Return the type of this field in Propstack (e.g. "Dropdown").
	 *
	 * @return string
	 */
	public function get_propstack_type(): string {
		return isset( $this->definition['type'] ) && is_string( $this->definition['type'] ) ? $this->definition['type'] : '';
	}

	/**
	 * Return the category of this field.
	 *
	 * @return Field_Category_Base
	 */
	public function get_category(): Field_Category_Base {
		return new \ConnectorForPropstack\Propstack\FieldCategories\CustomFields();
	}

	/**
	 * Return whether this field should be hidden in frontend and backend.
	 *
	 * Custom fields are delivered by both API versions.
	 *
	 * @return bool
	 */
	public function hide(): bool {
		return false;
	}

	/**
	 * Return whether this field should be hidden in the frontend.
	 *
	 * Each custom field is hidden until it has been enabled in the settings. Without the Pro plugin
	 * custom fields are never shown in the frontend.
	 *
	 * @return bool
	 */
	public function hide_in_frontend(): bool {
		return ! Custom_Fields::get_instance()->is_output_supported() || Custom_Fields::get_instance()->is_hidden( $this->get_propstack_name() );
	}
}

<?php
/**
 * File to handle basic functions for any fields.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\FieldCategories\Basic;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base;

/**
 * Base object for each field.
 */
class Field_Base {
	/**
	 * The API name of the field.
	 *
	 * @var string
	 */
	protected string $api = '';

	/**
	 * The internal name of the field.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * The WordPress-compatible type for the field (e.g., 'boolean', 'string', 'number', 'array').
	 *
	 * @var string
	 */
	protected string $type = 'string';

	/**
	 * The output format, if different from the type.
	 *
	 * @var string
	 */
	protected string $output_format = '';

	/**
	 * Show in the table in the backend.
	 *
	 * @var bool
	 */
	protected bool $show_in_table = false;

	/**
	 * Do not configure this field.
	 *
	 * @var bool
	 */
	protected bool $do_not_configure = false;

	/**
	 * Do not disable this field.
	 *
	 * @var bool
	 */
	protected bool $do_not_disable = false;

	/**
	 * Hide this field in the backend and the frontend. It will only be used for functional tasks.
	 *
	 * @var bool
	 */
	protected bool $hide = false;

	/**
	 * Hide this field in the frontend.
	 *
	 * @var bool
	 */
	protected bool $hide_in_frontend = false;

	/**
	 * Hide this field in the backend.
	 *
	 * @var bool
	 */
	protected bool $hide_in_backend = false;

	/**
	 * Show this field in REST API.
	 *
	 * @var bool
	 */
	protected bool $show_in_rest = false;

	/**
	 * Return the category name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return '';
	}

	/**
	 * Return the API name.
	 *
	 * @return string
	 */
	public function get_api(): string {
		return $this->api;
	}

	/**
	 * Return the category of this field.
	 *
	 * @return Field_Category_Base
	 */
	public function get_category(): Field_Category_Base {
		return new Basic();
	}

	/**
	 * Return the type for this field.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Return the output format for this field.
	 *
	 * @return string
	 */
	public function get_output_format(): string {
		return $this->output_format;
	}

	/**
	 * Return whether the field should be shown in the table.
	 *
	 * @return bool
	 */
	public function show_in_table(): bool {
		return $this->show_in_table;
	}

	/**
	 * Return whether this field should be hidden in frontend and backend.
	 *
	 * @return bool
	 */
	public function hide(): bool {
		return $this->hide;
	}

	/**
	 * Return whether this field should be hidden in the frontend.
	 *
	 * @return bool
	 */
	public function hide_in_frontend(): bool {
		return $this->hide_in_frontend;
	}

	/**
	 * Return whether this field should be hidden in the backend.
	 *
	 * @return bool
	 */
	public function hide_in_backend(): bool {
		return is_admin() && $this->hide_in_backend;
	}

	/**
	 * Return the value for this field from the API response during import.
	 *
	 * @param int                 $post_id The post-ID of the immo object.
	 * @param array<string,mixed> $immo_object The immo data from Propstack API.
	 *
	 * @return mixed
	 */
	public function get_value_from_api_response( int $post_id, array $immo_object ): mixed {
		// bail if the field does not exist in response.
		if ( ! isset( $immo_object[ $this->get_api() ] ) ) {
			return null;
		}

		// get the value.
		$value = $immo_object[ $this->get_api() ];

		// if the field is using a title and a value, save them both.
		if ( isset( $immo_object[ $this->get_api() ]['label'] ) ) {
			$title = $immo_object[ $this->get_api() ]['label'];

			// save the title.
			update_post_meta( $post_id, $this->get_name() . '_label', $title );

			// get the value.
			$value = $immo_object[ $this->get_api() ]['value'];
		}

		// return the resulting value.
		return $value;
	}

	/**
	 * Return whether this field should not be able to configure.
	 *
	 * @return bool
	 */
	public function do_not_configure(): bool {
		return $this->do_not_configure;
	}

	/**
	 * Return whether this field should not be disabled.
	 *
	 * @return bool
	 */
	public function do_not_disable(): bool {
		return $this->do_not_disable;
	}

	/**
	 * Return whether the user disables this field in the given object type or taxonomy.
	 *
	 * @param Object_Type_Base $object_type The object type object.
	 *
	 * @return bool
	 */
	public function is_disabled( Object_Type_Base $object_type ): bool {
		return 1 === absint( get_option( 'propstack_connector_fields_' . $object_type->get_slug() . '_' . $this->get_name() . '_disabled' ) );
	}

	/**
	 * Show this field in REST API.
	 *
	 * @return bool
	 */
	public function show_in_rest(): bool {
		return $this->show_in_rest;
	}
}

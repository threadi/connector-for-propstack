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
class ApiResponse extends Field_Base {
	/**
	 * The API name of the field.
	 *
	 * @var string
	 */
	protected string $api = 'api_response';

	/**
	 * The internal name of this field.
	 *
	 * @var string
	 */
	protected string $name = 'api_response';

	/**
	 * The type for the field (e.g., 'boolean', 'string', 'number', 'array').
	 *
	 * @var string
	 */
	protected string $type = 'code';

	/**
	 * Hide this field in the frontend.
	 *
	 * @var bool
	 */
	protected bool $hide_in_frontend = true;

	/**
	 * Return the field label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Response from Propstack API', 'connector-for-propstack' );
	}

	/**
	 * Return the category of this field.
	 *
	 * @return Field_Category_Base
	 */
	public function get_category(): Field_Category_Base {
		return new \ConnectorForPropstack\Propstack\FieldCategories\Api();
	}

	/**
	 * Return the value for this field from API response.
	 *
	 * @param int                 $post_id The post-ID of the immo object.
	 * @param array<string,mixed> $immo_object The immo data from Propstack API.
	 *
	 * @return mixed
	 */
	public function get_value_from_api_response( int $post_id, array $immo_object ): mixed {
		return $immo_object;
	}
}

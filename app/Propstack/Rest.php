<?php
/**
 * File for handling the REST API support for Propstack.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_REST_Server;

/**
 * Object to handle the REST API support for Propstack.
 */
class Rest {

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Rest
	 */
	private static ?Rest $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Rest {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	/**
	 * Initialize additional REST API endpoints.
	 *
	 * @return void
	 */
	public function rest_api_init(): void {
		register_rest_route(
			'connector-for-propstack/v1',
			'/fields/',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_fields' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'field_category' => array(
						'type'              => 'string',
						'required'          => false,
						'validate_callback' => 'rest_validate_request_arg',
						'sanitize_callback' => array( $this, 'sanitize_key_arg' ),
					),
					'query'          => array(
						'type'              => 'string',
						'required'          => false,
						'validate_callback' => 'rest_validate_request_arg',
						'sanitize_callback' => array( $this, 'sanitize_text_arg' ),
					),
				),
			)
		);
		foreach ( Taxonomies::get_instance()->get_taxonomies_as_objects() as $taxonomy ) {
			register_rest_route(
				'connector-for-propstack/v1',
				'/' . $taxonomy->get_name() . '-fields/',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $taxonomy, 'get_fields_for_rest_api' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => array(
						'query' => array(
							'type'              => 'string',
							'required'          => false,
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => array( $this, 'sanitize_text_arg' ),
						),
					),
				)
			);
		}
		register_rest_route(
			'connector-for-propstack/v1',
			'/filters/',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_filters' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Sanitize a text argument of a REST API request.
	 *
	 * Returns an empty string for non-scalar values.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	public function sanitize_text_arg( mixed $value ): string {
		// bail if value is not a scalar.
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		// return the sanitized value.
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitize a key argument of a REST API request.
	 *
	 * Returns an empty string for non-scalar values.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	public function sanitize_key_arg( mixed $value ): string {
		// bail if value is not a scalar.
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		// return the sanitized value.
		return sanitize_key( (string) $value );
	}

	/**
	 * Return the list of available fields.
	 *
	 * @param WP_REST_Request $request The object with the data of the request.
	 *
	 * @return array<int,mixed>
	 */
	public function get_fields( WP_REST_Request $request ): array {
		// get the params and make sure they are strings.
		$field_category = $request->get_param( 'field_category' );
		$query          = $request->get_param( 'query' );

		return Fields::get_instance()->get_fields_by_request( is_string( $field_category ) ? $field_category : '', is_string( $query ) ? $query : '', true );
	}

	/**
	 * Return the list of available filters.
	 *
	 * @return array<int,mixed>
	 */
	public function get_filters(): array {
		// prepare the list.
		$list = array();

		// get the fields.
		foreach ( Filters::get_instance()->get_filters_as_objects() as $filter_obj ) {
			foreach ( $filter_obj->get() as $filter ) {
				$list[] = array(
					'id'    => count( $list ) + 1,
					'label' => $filter->get_label(),
					'value' => $filter->get_filter_name(),
				);
			}
		}

		// return the resulting list.
		return $list;
	}
}

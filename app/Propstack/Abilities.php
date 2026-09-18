<?php
/**
 * File for handling the abilities we provide.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Plugin\Settings;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base;
use WP_Error;
use WP_Term;

/**
 * Object to add support for abilities.
 */
class Abilities {
	/**
	 * The name for the ability category.
	 */
	public const ABILITY_CATEGORY = 'connector-for-propstack';

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Abilities
	 */
	private static ?Abilities $instance = null;

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
	public static function get_instance(): Abilities {
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
		// use hooks.
		add_action( 'wp_abilities_api_init', array( $this, 'add_abilities' ) );
		add_action( 'wp_abilities_api_categories_init', array( $this, 'add_ability_category' ) );

		// use our own hooks.
		add_action( 'cfprop_run_import_by_ability', array( $this, 'run_scheduled_import' ) );
	}

	/**
	 * Add our own ability category.
	 *
	 * @return void
	 */
	public function add_ability_category(): void {
		// bail if function does not exist.
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		// add the category.
		wp_register_ability_category(
			self::ABILITY_CATEGORY,
			array(
				'label'       => __( 'Connector for Propstack', 'connector-for-propstack' ),
				'description' => __( 'Abilities for managing objects from Propstack in WordPress.', 'connector-for-propstack' ),
			)
		);
	}

	/**
	 * Add abilities this plugin provides.
	 *
	 * @return void
	 */
	public function add_abilities(): void {
		// bail if support for abilities is missing.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		// add the ability to get the list of objects.
		wp_register_ability(
			self::ABILITY_CATEGORY . '/get-objects',
			array(
				'label'               => __( 'Get list of objects', 'connector-for-propstack' ),
				'description'         => __( 'Returns the list of objects from Propstack in WordPress. Use the returned post_id to request the details of a single object.', 'connector-for-propstack' ),
				'category'            => self::ABILITY_CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'search'      => array(
							'type'        => 'string',
							'description' => __( 'Limit the results to objects matching this text.', 'connector-for-propstack' ),
						),
						'language'    => array(
							'type'        => 'string',
							'description' => __( 'Limit the results to objects in this language, e.g. "de".', 'connector-for-propstack' ),
						),
						'limit'       => array(
							'type'        => 'integer',
							'description' => __( 'The maximum amount of objects to return, 20 by default and 100 at most.', 'connector-for-propstack' ),
							'minimum'     => 1,
							'maximum'     => 100,
						),
						'with_fields' => array(
							'type'        => 'boolean',
							'description' => __( 'Return the fields of every object as well. This makes the response much bigger, so use it only if the fields are needed.', 'connector-for-propstack' ),
							'default'     => false,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'count'   => array(
							'type'        => 'integer',
							'description' => __( 'The amount of returned objects.', 'connector-for-propstack' ),
						),
						'objects' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'post_id'   => array(
										'type'        => 'integer',
										'description' => __( 'The ID of this object in WordPress.', 'connector-for-propstack' ),
									),
									'object_id' => array(
										'type'        => 'string',
										'description' => __( 'The ID of this object in Propstack.', 'connector-for-propstack' ),
									),
									'title'     => array(
										'type'        => 'string',
										'description' => __( 'The title of this object.', 'connector-for-propstack' ),
									),
									'language'  => array(
										'type'        => 'string',
										'description' => __( 'The language of this object.', 'connector-for-propstack' ),
									),
									'url'       => array(
										'type'        => 'string',
										'description' => __( 'The URL of this object in the frontend.', 'connector-for-propstack' ),
									),
									'fields'    => array(
										'type'        => 'object',
										'description' => __( 'The fields of this object, only present if requested via with_fields.', 'connector-for-propstack' ),
										'additionalProperties' => array( 'type' => 'string' ),
									),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'get_objects' ),
				'permission_callback' => array( $this, 'has_read_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);

		// add the ability to get a single object with all of its fields.
		wp_register_ability(
			self::ABILITY_CATEGORY . '/get-object',
			array(
				'label'               => __( 'Get a single object', 'connector-for-propstack' ),
				'description'         => __( 'Returns a single object from Propstack in WordPress with all of its fields.', 'connector-for-propstack' ),
				'category'            => self::ABILITY_CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'     => array(
							'type'        => 'integer',
							'description' => __( 'The ID of the object in WordPress, as returned by get-objects.', 'connector-for-propstack' ),
						),
						'with_fields' => array(
							'type'        => 'boolean',
							'description' => __( 'Return the fields of every object as well. This makes the response much bigger, so use it only if the fields are needed.', 'connector-for-propstack' ),
							'default'     => false,
						),
					),
					'required'   => array( 'post_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'   => array(
							'type'        => 'integer',
							'description' => __( 'The ID of this object in WordPress.', 'connector-for-propstack' ),
						),
						'object_id' => array(
							'type'        => 'string',
							'description' => __( 'The object ID of this object in Propstack.', 'connector-for-propstack' ),
						),
						'title'     => array(
							'type'        => 'string',
							'description' => __( 'The title of this object.', 'connector-for-propstack' ),
						),
						'language'  => array(
							'type'        => 'string',
							'description' => __( 'The language of this object.', 'connector-for-propstack' ),
						),
						'url'       => array(
							'type'        => 'string',
							'description' => __( 'The URL of this object in the frontend.', 'connector-for-propstack' ),
						),
						'fields'    => array(
							'type'                 => 'object',
							'description'          => __( 'The fields of this object, indexed by their internal name.', 'connector-for-propstack' ),
							'additionalProperties' => array( 'type' => 'string' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'get_object' ),
				'permission_callback' => array( $this, 'has_read_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);

		// add the ability to get the list of available fields.
		wp_register_ability(
			self::ABILITY_CATEGORY . '/get-fields',
			array(
				'label'               => __( 'Get list of object fields', 'connector-for-propstack' ),
				'description'         => __( 'Returns the fields which objects from Propstack can have, with their internal name, label and category. Use the internal names to request single fields via get-objects.', 'connector-for-propstack' ),
				'category'            => self::ABILITY_CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'search' => array(
							'type'        => 'string',
							'description' => __( 'Limit the results to fields whose name or label contains this text.', 'connector-for-propstack' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'count'  => array(
							'type'        => 'integer',
							'description' => __( 'The amount of returned fields.', 'connector-for-propstack' ),
						),
						'fields' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'     => array(
										'type'        => 'string',
										'description' => __( 'The internal name of this field.', 'connector-for-propstack' ),
									),
									'label'    => array(
										'type'        => 'string',
										'description' => __( 'The human readable label of this field.', 'connector-for-propstack' ),
									),
									'category' => array(
										'type'        => 'string',
										'description' => __( 'The category this field belongs to.', 'connector-for-propstack' ),
									),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'get_fields' ),
				'permission_callback' => array( $this, 'has_read_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);

		// add the ability to get the taxonomies and their terms.
		wp_register_ability(
			self::ABILITY_CATEGORY . '/get-taxonomies',
			array(
				'label'               => __( 'Get taxonomies and their terms', 'connector-for-propstack' ),
				'description'         => __( 'Returns the taxonomies objects are organized in - like object type, marketing type or state - with the terms which actually exist. Use these values to filter the results of get-objects.', 'connector-for-propstack' ),
				'category'            => self::ABILITY_CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'taxonomy' => array(
							'type'        => 'string',
							'description' => __( 'Limit the result to this taxonomy, by its internal name. All taxonomies are returned if this is not set.', 'connector-for-propstack' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'taxonomies' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'  => array(
										'type'        => 'string',
										'description' => __( 'The internal name of this taxonomy.', 'connector-for-propstack' ),
									),
									'label' => array(
										'type'        => 'string',
										'description' => __( 'The human readable label of this taxonomy.', 'connector-for-propstack' ),
									),
									'terms' => array(
										'type'  => 'array',
										'items' => array(
											'type'       => 'object',
											'properties' => array(
												'slug'  => array(
													'type' => 'string',
													'description' => __( 'The slug of this term, usable as a filter value.', 'connector-for-propstack' ),
												),
												'label' => array(
													'type' => 'string',
													'description' => __( 'The human readable label of this term.', 'connector-for-propstack' ),
												),
												'count' => array(
													'type' => 'integer',
													'description' => __( 'The amount of objects assigned to this term.', 'connector-for-propstack' ),
												),
											),
										),
									),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'get_taxonomies' ),
				'permission_callback' => array( $this, 'has_read_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);

		// add the ability to get the state of the import.
		wp_register_ability(
			self::ABILITY_CATEGORY . '/get-import-status',
			array(
				'label'               => __( 'Get the state of the object import', 'connector-for-propstack' ),
				'description'         => __( 'Returns whether an import is running right now and how many objects exist. Use this to find out why objects are missing.', 'connector-for-propstack' ),
				'category'            => self::ABILITY_CATEGORY,
				'input_schema'        => array(),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'is_running'   => array(
							'type'        => 'boolean',
							'description' => __( 'True if an import is running right now.', 'connector-for-propstack' ),
						),
						'object_count' => array(
							'type'        => 'integer',
							'description' => __( 'The amount of objects which exist in WordPress.', 'connector-for-propstack' ),
						),
						'progress'     => array(
							'type'        => 'integer',
							'description' => __( 'The progress of the import.', 'connector-for-propstack' ),
						),
						'progress_max' => array(
							'type'        => 'integer',
							'description' => __( 'The amount of objects to import.', 'connector-for-propstack' ),
						),
						'has_api_key'  => array(
							'type'        => 'boolean',
							'description' => __( 'True if an API token for Propstack is configured.', 'connector-for-propstack' ),
						),
						'api_version'  => array(
							'type'        => 'string',
							'description' => __( 'The version of the Propstack API which is used.', 'connector-for-propstack' ),
						),
						'scheduled'    => array(
							'type'        => 'boolean',
							'description' => __( 'True if an ability-driven import is scheduled.', 'connector-for-propstack' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'get_import_status' ),
				'permission_callback' => array( $this, 'has_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);

		// add the ability to start the import of objects.
		wp_register_ability(
			self::ABILITY_CATEGORY . '/start-import',
			array(
				'label'               => __( 'Start the import of objects', 'connector-for-propstack' ),
				'description'         => __( 'Starts the import of objects from Propstack. The import runs in several steps, so this only starts it - use get-import-status to follow its progress.', 'connector-for-propstack' ),
				'category'            => self::ABILITY_CATEGORY,
				'input_schema'        => array(),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'started' => array(
							'type'        => 'boolean',
							'description' => __( 'True if the import has been started.', 'connector-for-propstack' ),
						),
						'message' => array(
							'type'        => 'string',
							'description' => __( 'A hint about the result.', 'connector-for-propstack' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'start_import' ),
				'permission_callback' => array( $this, 'has_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Return whether the actual user is allowed to use our abilities.
	 *
	 * @return bool
	 */
	public function has_permission(): bool {
		return current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() );
	}

	/**
	 * Return whether the actual user is allowed to use our abilities.
	 *
	 * @return bool
	 */
	public function has_read_permission(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Return a list of objects from Propstack in WordPress.
	 *
	 * @param array<string,mixed> $input The given input.
	 *
	 * @return array<string,mixed>
	 */
	public function get_objects( array $input = array() ): array {
		// get the limit.
		$limit = isset( $input['limit'] ) ? min( 100, max( 1, absint( $input['limit'] ) ) ) : 20;

		// create the query.
		$query = array(
			'posts_per_page' => $limit,
		);

		// limit the results to a search text, if given.
		if ( ! empty( $input['search'] ) ) {
			$query['s'] = sanitize_text_field( (string) $input['search'] );
		}

		// limit the results to a language, if given.
		if ( ! empty( $input['language'] ) ) {
			$query['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup.
				array(
					'key'     => 'language_code',
					'value'   => sanitize_text_field( (string) $input['language'] ),
					'compare' => '=',
				),
			);
		}

		// check whether the fields should be returned as well.
		$with_fields = ! empty( $input['with_fields'] );

		// collect the objects.
		$objects = array();
		foreach ( ImmoObjects::get_instance()->get_objects( $query ) as $immo_object ) {
			$object_data = $this->get_object_data( $immo_object );

			// add the fields of this object if requested.
			if ( $with_fields ) {
				$object_data['fields'] = $this->get_object_fields( $immo_object );
			}

			$objects[] = $object_data;
		}

		// return the resulting list.
		return array(
			'count'   => count( $objects ),
			'objects' => $objects,
		);
	}

	/**
	 * Return a single object from Propstack in WordPress with all of its fields.
	 *
	 * @param array<string,mixed> $input The given input.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_object( array $input = array() ): array|WP_Error {
		// get the post-ID.
		$post_id = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;

		// bail if no post-ID is given.
		if ( 0 === $post_id ) {
			return new WP_Error( 'cfprop_missing_post_id', __( 'No post ID has been given.', 'connector-for-propstack' ), array( 'status' => 400 ) );
		}

		// get the object.
		$immo_object = ImmoObjects::get_instance()->get_object( $post_id );

		// bail if this is not one of our objects.
		if ( PostTypes\ImmoObject::get_instance()->get_name() !== get_post_type( $post_id ) ) {
			return new WP_Error( 'cfprop_object_not_found', __( 'No object could be found for the given ID.', 'connector-for-propstack' ), array( 'status' => 404 ) );
		}

		// get the base data.
		$data = $this->get_object_data( $immo_object );

		// add the fields of this object.
		$data['fields'] = $this->get_object_fields( $immo_object );

		// return the resulting data.
		return $data;
	}

	/**
	 * Return the base data of a single object.
	 *
	 * @param ImmoObject $immo_object The object.
	 *
	 * @return array<string,mixed>
	 */
	private function get_object_data( ImmoObject $immo_object ): array {
		return array(
			'post_id'   => $immo_object->get_id(),
			'object_id' => $immo_object->get_object_id(),
			'title'     => $immo_object->get_title(),
			'language'  => $immo_object->get_language(),
			'url'       => $immo_object->get_link(),
		);
	}

	/**
	 * Return the fields of a single object, prepared for the output.
	 *
	 * @param ImmoObject $immo_object The object.
	 *
	 * @return array<string,string>
	 */
	/**
	 * Return the fields of a single object which are visible in the frontend.
	 *
	 * Hint:
	 * We do not use ImmoObject::get_fields() here as it also returns internal fields like
	 * the raw API response, which must not leave the site through an ability.
	 *
	 * @param ImmoObject $immo_object The object.
	 *
	 * @return array<string,string>
	 */
	private function get_object_fields( ImmoObject $immo_object ): array {
		// get the object type of this object.
		$object_type_object = ObjectType::get_instance()->get_object_type_by_object_post_id( $immo_object->get_id() );

		// bail if the object type is missing.
		if ( ! $object_type_object instanceof Object_Type_Base ) {
			return array();
		}

		// collect the fields which are visible in the frontend.
		$fields = array();
		foreach ( $object_type_object->get_fields() as $field ) {
			// bail if this field is hidden.
			if ( $field->hide() || $field->hide_in_frontend() || empty( $field->get_name() ) ) {
				continue;
			}

			$show_field = true;

			/**
			 * Filter whether a field is shown in the frontend.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 *
			 * @param bool       $show_field  The result.
			 * @param Field_Base $field       The field.
			 * @param ImmoObject $immo_object The object.
			 *
			 * @noinspection PhpConditionAlreadyCheckedInspection
			 */
			if ( ! apply_filters( 'cfprop_show_field_in_frontend', $show_field, $field, $immo_object ) ) {
				continue;
			}

			// get the value of this field without HTML.
			$value = Fields::get_instance()->get_field_value( $immo_object->get_id(), $field );

			// add it to the list.
			$fields[ $field->get_name() ] = is_scalar( $value ) ? (string) $value : (string) wp_json_encode( $value );
		}

		// return the resulting list.
		return $fields;
	}

	/**
	 * Return the list of fields objects can have.
	 *
	 * @param array<string,mixed> $input The given input.
	 *
	 * @return array<string,mixed>
	 */
	public function get_fields( array $input = array() ): array {
		// get the search text, if given.
		$search = isset( $input['search'] ) ? strtolower( sanitize_text_field( (string) $input['search'] ) ) : '';

		// collect the fields.
		$fields = array();
		foreach ( Fields::get_instance()->get_fields_as_objects() as $field ) {
			// bail if this field is hidden or has no name.
			if ( $field->hide() || $field->hide_in_frontend() || empty( $field->get_name() ) ) {
				continue;
			}

			// bail if the search text does not match.
			if ( ! empty( $search ) && ! str_contains( strtolower( $field->get_name() ), $search ) && ! str_contains( strtolower( $field->get_label() ), $search ) ) {
				continue;
			}

			// add this field to the list.
			$fields[] = array(
				'name'     => $field->get_name(),
				'label'    => $field->get_label(),
				'category' => $field->get_category()->get_label(),
			);
		}

		// return the resulting list.
		return array(
			'count'  => count( $fields ),
			'fields' => $fields,
		);
	}

	/**
	 * Return the taxonomies objects are organized in, with their terms.
	 *
	 * @param array<string,mixed> $input The given input.
	 *
	 * @return array<string,mixed>
	 */
	public function get_taxonomies( array $input = array() ): array {
		// get the requested taxonomy, if given.
		$requested = isset( $input['taxonomy'] ) ? sanitize_text_field( (string) $input['taxonomy'] ) : '';

		// collect the taxonomies.
		$taxonomies = array();
		foreach ( Taxonomies::get_instance()->get_taxonomies_as_objects() as $taxonomy ) {
			// bail if another taxonomy has been requested.
			if ( ! empty( $requested ) && $requested !== $taxonomy->get_name() ) {
				continue;
			}

			// get the terms of this taxonomy.
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy->get_name(),
					'hide_empty' => false,
				)
			);

			// collect the terms.
			$term_list = array();
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					// bail if this is not a term object.
					if ( ! $term instanceof WP_Term ) { // @phpstan-ignore instanceof.alwaysTrue
						continue;
					}

					$term_list[] = array(
						'slug'  => $term->slug,
						'label' => $term->name,
						'count' => absint( $term->count ),
					);
				}
			}

			// add this taxonomy to the list.
			$taxonomies[] = array(
				'name'  => $taxonomy->get_name(),
				'label' => $taxonomy->get_title(),
				'terms' => $term_list,
			);
		}

		// return the resulting list.
		return array(
			'taxonomies' => $taxonomies,
		);
	}

	/**
	 * Return the state of the object import.
	 *
	 * @return array<string,mixed>
	 */
	public function get_import_status(): array {
		// get the state of a running import.
		$import_data = get_option( 'cfprop_objects_to_import', array() );
		$offset      = absint( get_option( 'cfprop_objects_import_offset', 0 ) );

		return array(
			'is_running'   => Helper::is_process_running( CFPROP_IMPORT_RUNNING ),
			'object_count' => absint( wp_count_posts( PostTypes\ImmoObject::get_instance()->get_name() )->publish ),
			'progress'     => isset( $import_data['total'] ) ? $offset : 0,
			'progress_max' => isset( $import_data['total'] ) ? absint( $import_data['total'] ) : 0,
			'has_api_key'  => ! empty( get_option( 'propstack_connector_api_key' ) ),
			'api_version'  => (string) get_option( 'propstack_connector_api_version', 'v1' ),
			'scheduled'    => false !== wp_next_scheduled( 'cfprop_run_import_by_ability' ),
		);
	}

	/**
	 * Start the import of objects.
	 *
	 * Hint:
	 * The import runs in several steps to prevent timeouts, so this only starts it. The
	 * caller has to follow its progress via the get-import-status ability.
	 *
	 * @return array<string,mixed>
	 */
	public function start_import(): array {
		// bail if no API token is set.
		if ( empty( get_option( 'propstack_connector_api_key' ) ) ) {
			return array(
				'started' => false,
				'message' => __( 'No API token for Propstack is configured, so no import can be started.', 'connector-for-propstack' ),
			);
		}

		// bail if an import is already running.
		if ( Helper::is_process_running( CFPROP_IMPORT_RUNNING ) ) {
			return array(
				'started' => false,
				'message' => __( 'An import is already running.', 'connector-for-propstack' ),
			);
		}

		// schedule the import to run in the background, it takes too long to wait for it.
		wp_schedule_single_event( time(), 'cfprop_run_import_by_ability' );

		// return the result.
		return array(
			'started' => true,
			'message' => __( 'The import has been scheduled and will start within the next minutes. Use get-import-status to follow its progress.', 'connector-for-propstack' ),
		);
	}

	/**
	 * Run the import which has been scheduled by an ability.
	 *
	 * Hint:
	 * The import runs in chunks, so it has to be called until it reports that it is done.
	 * There is no request which could continue it here, so we loop ourselves.
	 *
	 * @return void
	 */
	public function run_scheduled_import(): void {
		$runs = 0;

		do {
			$import_obj = ImmoObjects::get_instance()->import( '' );

			++$runs;

			// bail if the import does not finish.
			if ( $runs > 10000 ) {
				Log::get_instance()->add( __( 'The import started by an ability did not finish and has been stopped.', 'connector-for-propstack' ), 'error', 'import' );

				break;
			}
		} while ( $import_obj->has_load_more() );
	}
}

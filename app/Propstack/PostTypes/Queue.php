<?php
/**
 * File to handle our own custom post-type "propstack_queue".
 *
 * This post-type holds the queue of files to import for objects from Propstack.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\PostTypes;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Fields;
use ConnectorForPropstack\Propstack\Post_Type;

/**
 * Object of this custom post type.
 */
class Queue extends Post_Type {
	/**
	 * Set the name of this cpt.
	 *
	 * @var string
	 */
	protected string $name = 'cfprop_queue';

	/**
	 * Instance of this object.
	 *
	 * @var ?Queue
	 */
	private static ?Queue $instance = null;

	/**
	 * Constructor for this object.
	 */
	private function __construct() {
	}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {
	}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Queue {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this post-type.
	 *
	 * @return void
	 */
	public function init(): void {
	}

	/**
	 * Register this custom post-type.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = array(
			'name'          => __( 'Entries', 'connector-for-propstack' ),
			'singular_name' => __( 'Entry', 'connector-for-propstack' ),
			'menu_name'     => __( 'Entries', 'connector-for-propstack' ),
			'not_found'     => __( 'No entries found', 'connector-for-propstack' ),
		);

		// set arguments for our own cpt.
		$args = array(
			'label'               => $labels['name'],
			'description'         => '',
			'labels'              => $labels,
			'supports'            => array(),
			'public'              => true,
			'hierarchical'        => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'has_archive'         => false,
			'can_export'          => false,
			'exclude_from_search' => false,
			'taxonomies'          => array(),
			'publicly_queryable'  => true,
			'show_in_rest'        => true,
			'capability_type'     => 'post',
			'capabilities'        => array(
				'create_posts'       => 'do_not_allow',
				'delete_posts'       => 'do_not_allow',
				'edit_post'          => 'read_' . $this->get_name(),
				'edit_posts'         => 'read_' . $this->get_name(),
				'edit_others_posts'  => 'do_not_allow',
				'read_post'          => 'do_not_allow',
				'read_posts'         => 'do_not_allow',
				'publish_posts'      => 'do_not_allow',
				'read_private_posts' => 'do_not_allow',
			),
		);
		register_post_type( $this->get_name(), $args ); // @phpstan-ignore argument.type

		// register our fields in REST API.
		foreach ( Fields::get_instance()->get_fields_as_objects() as $field ) {
			register_post_meta(
				$this->get_name(),
				$field->get_name(),
				array(
					'type'         => $field->get_type(),
					'single'       => true,
					'show_in_rest' => true,
				)
			);
		}
	}

	/**
	 * Return the list of fields, used as post-meta, for this cpt.
	 *
	 * The list is grouped by categories:
	 * - basic: the basic object data.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_fields(): array {
		$fields = array(
			'basic' => array(
				'label'  => __( 'Basic data', 'connector-for-propstack' ),
				'fields' => array(
					'id'                 => array(
						'api'   => 'id',
						'label' => __( 'ID', 'connector-for-propstack' ),
					),
					'token'              => array(
						'api'   => 'token',
						'label' => __( 'Token', 'connector-for-propstack' ),
					),
					'title'              => array(
						'api'   => 'title',
						'label' => __( 'Title', 'connector-for-propstack' ),
					),
					'name'               => array(
						'api'   => 'name',
						'label' => __( 'Name', 'connector-for-propstack' ),
					),
					'url'                => array(
						'api'   => 'url',
						'label' => __( 'URL', 'connector-for-propstack' ),
					),
					'position'           => array(
						'api'   => 'position',
						'label' => __( 'Position', 'connector-for-propstack' ),
						'type'  => 'integer',
					),
					'is_private'         => array(
						'api'   => 'is_private',
						'label' => __( 'Private', 'connector-for-propstack' ),
						'type'  => 'boolean',
					),
					'on_landing_page'    => array(
						'api'   => 'on_landing_page',
						'label' => __( 'On landing page', 'connector-for-propstack' ),
					),
					'is_exposee'         => array(
						'api'   => 'is_exposee',
						'label' => __( 'Exposee', 'connector-for-propstack' ),
						'type'  => 'boolean',
					),
					'second_document'    => array(
						'api'   => 'second_document',
						'label' => __( 'Second document', 'connector-for-propstack' ),
					),
					'is_floorplan'       => array(
						'api'   => 'is_floorplan',
						'label' => __( 'Floorplan', 'connector-for-propstack' ),
					),
					'tags'               => array(
						'api'   => 'tags',
						'label' => __( 'Tags', 'connector-for-propstack' ),
						'type'  => 'array',
					),
					'created_at'         => array(
						'api'   => 'created_at',
						'label' => __( 'Created at', 'connector-for-propstack' ),
					),
					'updated_at'         => array(
						'api'   => 'updated_at',
						'label' => __( 'Updated at', 'connector-for-propstack' ),
					),
					'client_id'          => array(
						'api'   => 'client_id',
						'label' => __( 'Client ID', 'connector-for-propstack' ),
					),
					'property_id'        => array(
						'api'   => 'property_id',
						'label' => __( 'Property ID', 'connector-for-propstack' ),
					),
					'project_id'         => array(
						'api'   => 'project_id',
						'label' => __( 'Project ID', 'connector-for-propstack' ),
					),
					'client_property_id' => array(
						'api'   => 'client_property_id',
						'label' => __( 'Client Property ID', 'connector-for-propstack' ),
					),
					'folder_id'          => array(
						'api'   => 'folder_id',
						'label' => __( 'Folder ID', 'connector-for-propstack' ),
					),
					'imageable_type'     => array(
						'api'   => 'imageable_type',
						'label' => __( 'Imageable type', 'connector-for-propstack' ),
					),
					'imageable_id'       => array(
						'api'   => 'imageable_id',
						'label' => __( 'Imageable ID', 'connector-for-propstack' ),
						'type'  => 'integer',
					),
					'connection_ids'     => array(
						'api'   => 'connection_ids',
						'label' => __( 'Connection IDs', 'connector-for-propstack' ),
						'type'  => 'array',
					),
					'is_not_for_exposee' => array(
						'api'   => 'is_not_for_exposee',
						'label' => __( 'Is not for exposee', 'connector-for-propstack' ),
						'type'  => 'boolean',
					),
					'big_url'            => array(
						'api'   => 'big_url',
						'label' => __( 'Big URL', 'connector-for-propstack' ),
					),
					'medium_url'         => array(
						'api'   => 'medium_url',
						'label' => __( 'Medium URL', 'connector-for-propstack' ),
					),
					'thumb_url'          => array(
						'api'   => 'thumb_url',
						'label' => __( 'Thumb URL', 'connector-for-propstack' ),
					),
					'small_thumb_url'    => array(
						'api'   => 'small_thumb_url',
						'label' => __( 'Small thumb URL', 'connector-for-propstack' ),
					),
					'square_url'         => array(
						'api'   => 'square_url',
						'label' => __( 'Square URL', 'connector-for-propstack' ),
					),
				),
			),
		);

		/**
		 * Filter the list of fields for the Propstack queue.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param array<string,array<string,mixed>> $fields The list of fields.
		 */
		return apply_filters( 'cfprop_queue_fields', $fields );
	}
}

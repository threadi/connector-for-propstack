<?php
/**
 * File to handle our own custom post-type "cfprop_object".
 *
 * This post-type holds the main entry for each object imported from Propstack.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\PostTypes;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\ProcessHandler;
use ConnectorForPropstack\Plugin\Settings;
use ConnectorForPropstack\Plugin\Setup;
use ConnectorForPropstack\Plugin\Templates;
use ConnectorForPropstack\Propstack\FieldCategories;
use ConnectorForPropstack\Propstack\FieldCategories\Images;
use ConnectorForPropstack\Propstack\Fields;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Post_Type;
use ConnectorForPropstack\Propstack\Taxonomies;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base;
use WP_Admin_Bar;
use WP_Post;
use WP_REST_Response;

/**
 * Object of this custom post type.
 */
class ImmoObject extends Post_Type {
	/**
	 * Set the name of this cpt.
	 *
	 * @var string
	 */
	protected string $name = 'cfprop_object';

	/**
	 * Instance of this object.
	 *
	 * @var ?ImmoObject
	 */
	private static ?ImmoObject $instance = null;

	/**
	 * Constructor for this object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() { }

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): ImmoObject {
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
		// use hooks.
		add_filter( 'rest_prepare_' . $this->get_name(), array( $this, 'rest_prepare' ), 12, 2 );
		add_filter( 'post_row_actions', array( $this, 'update_row_action' ), 10, 2 );
		add_action( 'admin_bar_menu', array( $this, 'add_custom_toolbar' ), 100 );
		add_filter( 'admin_footer_text', array( $this, 'show_plugin_hint_in_footer' ), 0 );
		add_action( 'restrict_manage_posts', array( $this, 'add_button_in_table' ) );

		// edit objects.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'remove_third_party_meta_boxes' ), PHP_INT_MAX );

		// run parent tasks.
		parent::init();
	}

	/**
	 * Register this custom post-type.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = array(
			'name'                  => __( 'Objects', 'connector-for-propstack' ),
			'singular_name'         => __( 'Object', 'connector-for-propstack' ),
			'menu_name'             => __( 'Objects', 'connector-for-propstack' ),
			'all_items'             => __( 'All objects', 'connector-for-propstack' ),
			'view_item'             => __( 'View object in frontend', 'connector-for-propstack' ),
			'view_items'            => __( 'View objects in frontend', 'connector-for-propstack' ),
			'edit_item'             => __( 'View object in backend', 'connector-for-propstack' ),
			'search_items'          => __( 'Search object', 'connector-for-propstack' ),
			'not_found'             => __( 'No objects imported', 'connector-for-propstack' ),
			'item_link'             => _x( 'Object Link', 'navigation link block title', 'connector-for-propstack' ),
			'item_link_description' => _x( 'A link to an object.', 'navigation link block description', 'connector-for-propstack' ),
		);

		// get slugs.
		$archive_slug = Helper::get_archive_slug();
		$single_slug  = Helper::get_single_slug();

		// set arguments for our own cpt.
		$args = array(
			'label'               => $labels['name'],
			'description'         => '',
			'labels'              => $labels,
			'supports'            => array( 'title', 'thumbnail' ),
			'public'              => true,
			'hierarchical'        => false,
			'show_ui'             => true,
			'show_in_menu'        => Setup::get_instance()->is_completed(),
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'has_archive'         => $archive_slug,
			'can_export'          => false,
			'exclude_from_search' => false,
			'taxonomies'          => array_keys( Taxonomies::get_instance()->get_taxonomies() ),
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
			'menu_icon'           => Helper::get_plugin_url() . 'gfx/propstack_menu_logo.png',
			'rewrite'             => array(
				'slug' => $single_slug,
			),
		);
		register_post_type( $this->get_name(), $args ); // @phpstan-ignore argument.type

		// register our fields in REST API.
		foreach ( Fields::get_instance()->get_fields_as_objects() as $field ) {
			register_meta(
				'post',
				$field->get_name(),
				array(
					'type'         => $field->get_type(),
					'single'       => true,
					'show_in_rest' => $field->show_in_rest(),
				)
			);
		}
	}

	/**
	 * Add boxes to the edit-page of our own cpt.
	 *
	 * @param string $post_type The requested post-type.
	 * @param mixed  $post The post-object.
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function add_meta_boxes( string $post_type, mixed $post ): void {
		// Bail if the second parameter is not "WP_Post".
		// This is necessary as backward compatibility because of a bug on WooCommerce: https://github.com/woocommerce/woocommerce/issues/61909.
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		// get the object type for this object.
		$object_type = ObjectType::get_instance()->get_object_type_by_object_post_id( $post->ID );

		// bail if the object type is not set.
		if ( ! $object_type instanceof Object_Type_Base ) {
			return;
		}

		// add a meta-box with a link to Propstack.
		add_meta_box(
			$this->get_name() . '-propstack',
			__( 'Propstack', 'connector-for-propstack' ),
			array( $this, 'show_propstack_link_metabox' ),
			$this->get_name()
		);

		// loop through the categories and add the meta-boxes for each one.
		foreach ( FieldCategories::get_instance()->get_categories_as_objects() as $category ) {
			// do not show this meta-box if the user has not the capability to view the data.
			if ( $category->hide_meta_box() ) {
				continue;
			}

			// do not show this meta-box if no taxonomy of this object contains any fields from it.
			if ( empty( $object_type->get_fields_by_category( $category->get_name() ) ) ) {
				continue;
			}

			// add the metabox.
			add_meta_box(
				$this->get_name() . '-' . $category->get_name() . '-fields',
				$category->get_label(),
				$category->get_metabox_callback(),
				$this->get_name()
			);
		}

		// add a metabox for the images.
		$image_cat = new Images();
		add_meta_box(
			$this->get_name() . '-' . $image_cat->get_name() . '-fields',
			$image_cat->get_label(),
			$image_cat->get_metabox_callback(),
			$this->get_name()
		);

		// add a metabox for the thumbnail (we do not use the default box as this thumbnail should not be edited).
		add_meta_box(
			$this->get_name() . '-' . $image_cat->get_name() . '-thumbnail',
			__( 'Thumbnail', 'connector-for-propstack' ),
			array( $this, 'add_thumbnail_metabox' ),
			$this->get_name(),
			'side'
		);
	}

	/**
	 * Change the REST API response for own cpt.
	 *
	 * In this way we add fields, which are missing through the supports-setting during registering the cpt.
	 * And we format the content, which comes from Propstack, as an array.
	 *
	 * @param WP_REST_Response $data The response object.
	 * @param WP_Post          $post The requested object.
	 *
	 * @return WP_REST_Response
	 * @noinspection PhpUnused
	 */
	public function rest_prepare( WP_REST_Response $data, WP_Post $post ): WP_REST_Response {
		// get immo objects-object.
		$immo_objects = ImmoObjects::get_instance();

		// get the position as an object.
		$immo_object = $immo_objects->get_object( $post->ID );

		// get the content of this immo object.
		$content = Templates::get_instance()->get_direct_content_template( $immo_object, array() );

		// add a result to the response.
		$data->data['excerpt'] = array(
			'rendered'  => '',
			'raw'       => '',
			'protected' => false,
		);
		$data->data['title']   = array(
			'rendered' => $immo_object->get_title(),
			'raw'      => $immo_object->get_title(),
		);
		$data->data['content'] = array( 'rendered' => $content );

		// add our fields as meta-fields.
		$meta = array();
		foreach ( Fields::get_instance()->get_fields_as_objects() as $field ) {
			// get its name.
			$field_name = $field->get_name();

			// add it to the list.
			$meta[ $field_name ] = get_post_meta( $immo_object->get_id(), $field_name, true );
		}

		// add them.
		$data->data['meta'] = $meta;

		// set response.
		return $data;
	}

	/**
	 * Remove all actions except "view" and "edit" for our own cpt.
	 *
	 * @param array<string,string> $actions List of actions.
	 * @param WP_Post              $post Object of the post.
	 * @return array<string,string>
	 */
	public function update_row_action( array $actions, WP_Post $post ): array {
		// bail if this is not our cpt.
		if ( $this->get_name() !== get_post_type() ) {
			return $actions;
		}

		$new_actions = array();
		if ( ! empty( $actions['view'] ) ) {
			$new_actions = array(
				'view' => $actions['view'],
			);
		}

		// get edit-URL.
		$edit_url = get_edit_post_link( $post->ID );

		// add the edit-URL to the action-list if it is set.
		if ( ! is_null( $edit_url ) ) {
			$new_actions['edit'] = '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'connector-for-propstack' ) . '</a>';
		}

		// add a link to import the files of this object.
		$new_actions['files-import'] = '<a href="#" class="easy-dialog-for-wordpress" data-dialog="' . esc_attr( Helper::get_json( $this->get_import_images_dialog( $post->ID ) ) ) . '">' . __( 'Import images', 'connector-for-propstack' ) . '</a>';

		// return the resulting list.
		return $new_actions;
	}

	/**
	 * Add a link in the toolbar to the archive of the objects.
	 *
	 * @param WP_Admin_Bar $admin_bar The object of the Admin-Bar.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 **/
	public function add_custom_toolbar( WP_Admin_Bar $admin_bar ): void {
		// get the archive URL.
		$archive_url = $this->get_archive_url();

		// bail if no URL was given.
		if ( empty( $archive_url ) ) {
			return;
		}

		// add a link in the admin bar dropdown.
		$admin_bar->add_menu(
			array(
				'id'     => $this->get_name() . '-archive',
				'parent' => 'site-name',
				'title'  => __( 'Objects', 'connector-for-propstack' ),
				'href'   => $archive_url,
			)
		);

		// add links in the admin-bar in the backend.
		if ( ! is_admin() ) {
			return;
		}

		// add a link to view the object in the frontend if one is called in the backend.
		$post_id = absint( filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT ) );
		if ( $post_id > 0 && $this->get_name() === get_post_type( $post_id ) ) {
			$immo_obj = ImmoObjects::get_instance()->get_object( $post_id );
			$admin_bar->add_menu(
				array(
					'id'     => 'propstack-connector-single',
					'parent' => null,
					'group'  => null,
					'title'  => __( 'View object in frontend', 'connector-for-propstack' ),
					'href'   => $immo_obj->get_link(),
				)
			);
		}
	}

	/**
	 * Show hint in footer in the backend on listing and single view of objects there.
	 *
	 * @param string $content The actual footer content.
	 *
	 * @return string
	 */
	public function show_plugin_hint_in_footer( string $content ): string {
		// get requested taxonomy.
		$taxonomy = (string) filter_input( INPUT_GET, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// bail if a taxonomy is requested.
		if ( ! empty( $taxonomy ) ) {
			return $content;
		}

		// get requested post-type.
		$post_type = (string) filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// get requested post.
		$post = absint( filter_input( INPUT_GET, 'post', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );

		// bail if this is not the listing or the single view of a position in the backend.
		if ( $post_type !== $this->get_name() && get_post_type( $post ) !== $this->get_name() ) {
			return $content;
		}

		// show hint for our plugin.
		/* translators: %1$s will be replaced by the plugin name. */
		return $content . ' ' . sprintf( __( 'This page is provided by the plugin %1$s.', 'connector-for-propstack' ), '<em>' . Helper::get_plugin_name() . '</em>' );
	}

	/**
	 * Remove all meta-boxes that are not part of this post-type.
	 *
	 * @param string $post_type The used post-type.
	 *
	 * @return void
	 */
	public function remove_third_party_meta_boxes( string $post_type ): void {
		global $wp_meta_boxes;

		// bail if this is not our own cpt.
		if ( $this->get_name() !== $post_type ) {
			return;
		}

		$false = false;
		/**
		 * Prevent removing of all meta-boxes in the edit view of objects.
		 *
		 * Caution: the boxes will not be able to be saved.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param bool $false Set true to prevent removing of each meta-box.
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		if ( apply_filters( 'cfprop_object_prevent_meta_box_remove', $false ) ) {
			return;
		}

		/**
		 * Loop through the boxes for this cpt and remove all which do not belong to our plugin.
		 */
		foreach ( $wp_meta_boxes[ $this->get_name() ] as $context => $priority_boxes ) {
			foreach ( $priority_boxes as $boxes ) {
				foreach ( $boxes as $box ) {
					// bail of box is not an array.
					if ( ! is_array( $box ) ) {
						continue;
					}

					/**
					 * Decide if we should not remove the support for this meta-box.
					 *
					 * @since 1.0.0 Available since 1.0.0.
					 *
					 * @param bool $false Return true to ignore this box.
					 * @param array $box Settings of the meta-box.
					 *
					 * @noinspection PhpConditionAlreadyCheckedInspection
					 */
					if ( apply_filters( 'cfprop_object_do_not_hide_meta_box', $false, $box ) ) {
						continue;
					}

					// check if the box is not from our own plugin.
					if ( false === str_contains( $box['id'], $this->get_name() ) ) {
						remove_meta_box( $box['id'], $this->get_name(), $context );
					}
				}
			}
		}
	}

	/**
	 * Show the thumbnail of the object.
	 *
	 * @param WP_Post $post The post-object.
	 *
	 * @return void
	 */
	public function add_thumbnail_metabox( WP_Post $post ): void {
		// get the thumbnail attachment ID for this object.
		$thumbnail_id = absint( get_post_thumbnail_id( $post->ID ) );

		// bail if no ID is set.
		if ( 0 === $thumbnail_id ) {
			echo '<p>' . esc_html__( 'No thumbnail is set.', 'connector-for-propstack' ) . '</p>';
			return;
		}

		// show the thumbnail.
		echo wp_get_attachment_image( $thumbnail_id, array( 150, 150 ) );
	}

	/**
	 * Add a button to go to the settings in the head of the table.
	 *
	 * @param string $post_type The used post-type.
	 *
	 * @return void
	 */
	public function add_button_in_table( string $post_type ): void {
		// bail if this is not our own cpt.
		if ( $post_type !== $this->get_name() ) {
			return;
		}

		// bail if the user is not allowed to use the settings.
		if ( ! current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// show the button.
		echo '<div class="alignleft actions bulkactions"><a href="' . esc_url( Settings::get_instance()->get_url() ) . '" class="button">' . esc_html__( 'Settings', 'connector-for-propstack' ) . '</a></div>';
	}

	/**
	 * Return the dialog to import images for the object from Propstack.
	 *
	 * @param int $post_id The post-ID of the object.
	 *
	 * @return array<string,mixed>
	 */
	public function get_import_images_dialog( int $post_id ): array {
		return array(
			'className' => 'propstack-connector-dialog',
			'title'     => __( 'Import images from Propstack', 'connector-for-propstack' ),
			'texts'     => array(
				/* translators: %1$s will be replaced by the object title. */
				'<p><strong>' . sprintf( __( 'Click on the button below to start the import of files for %1$s from Propstack.', 'connector-for-propstack' ), '<br><em>' . get_the_title( $post_id ) . '</em><br>' ) . '</strong></p>',
				'<p>' . __( 'The import could take some time. Please be patient.', 'connector-for-propstack' ) . '</p>',
			),
			'buttons'   => array(
				array(
					'action'  => 'propstack_connector_object_files_import( "' . esc_attr( ProcessHandler::get_instance()->create_id() ) . '", ' . $post_id . ' );',
					'variant' => 'primary',
					'text'    => __( 'Import them now', 'connector-for-propstack' ),
				),
				array(
					'action'  => 'closeDialog();',
					'variant' => 'primary',
					'text'    => __( 'Cancel', 'connector-for-propstack' ),
				),
			),
		);
	}

	/**
	 * Show a link to edit a single object in Propstack.
	 *
	 * @param WP_Post $post The post-object of the immo object.
	 *
	 * @return void
	 */
	public function show_propstack_link_metabox( WP_Post $post ): void {
		// get the immo object.
		$immo_object = ImmoObjects::get_instance()->get_object( $post->ID );

		// show the link as a button.
		echo '<a href="https://crm.propstack.de/app/portfolio/properties/' . esc_attr( $immo_object->get_object_id() ) . '" target="_blank" class="button">' . esc_html__( 'Edit in Propstack', 'connector-for-propstack' ) . '</a>';
	}
}

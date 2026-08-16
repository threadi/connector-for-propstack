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
use ConnectorForPropstack\Plugin\Log;
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
use WP_Error;
use WP_Post;
use WP_Query;
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

		// change third party support.
		add_filter( 'brizy_settings_post_types', array( $this, 'brizy_settings_post_types' ) );
		add_filter( 'manage_edit-' . self::get_instance()->get_name() . '_columns', array( $this, 'remove_yoast_columns' ) );
		add_filter( 'manage_edit-' . self::get_instance()->get_name() . '_columns', array( $this, 'remove_rank_math_columns' ) );
		add_filter( 'trp_translating_capability', array( $this, 'translatepress_hide_option' ) );
		add_action( 'admin_init', array( $this, 'scpo_remove_filter' ), 11 );
		add_filter( 'og_array', array( $this, 'og_optimizer' ) );
		add_filter( 'the_seo_framework_meta_render_data', array( $this, 'seoframework' ) );
		add_filter( 'the_seo_framework_schema_graph_data', array( $this, 'seoframework_schema' ) );
		add_filter( 'seopress_social_og_desc', array( $this, 'seopress_og_description' ) );
		add_filter( 'seopress_titles_desc', array( $this, 'seopress_titles' ) );
		add_filter( 'manage_' . self::get_instance()->get_name() . '_posts_columns', array( $this, 'remove_wpml_column' ), 20 );
		add_action( 'wp_before_admin_bar_render', array( $this, 'duplicate_page_prevent_options' ), 20 );
		add_filter( 'wp_consent_api_registered_' . plugin_basename( CFPROP_PLUGIN ), array( $this, 'wp_consent_api_register' ) );

		// use our own hooks.
		add_filter( 'cfprop_help_tabs', array( $this, 'add_help' ) );

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
			'show_in_rest'        => is_user_logged_in(),
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

		// get the object.
		$immo_object = ImmoObjects::get_instance()->get_object( $post->ID );

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

		// add a link to edit this object in Propstack.
		$new_actions['edit-in-propstack'] = '<a href="' . esc_url( $immo_object->get_url_to_propstack() ) . '" target="_blank">' . __( 'Edit in Propstack', 'connector-for-propstack' ) . '</a>';

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
					'id'     => 'cfprop-single',
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
			'className' => 'cfprop-dialog',
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
		echo '<a href="' . esc_url( $immo_object->get_url_to_propstack() ) . '" target="_blank" class="button">' . esc_html__( 'Edit in Propstack', 'connector-for-propstack' ) . '</a>';
	}

	/**
	 * Add a hint in the knowledge base.
	 *
	 * @param array<int,array<string,mixed>> $help_list List of help tabs.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function add_help( array $help_list ): array {
		// collect the content for the help.
		/* translators: %1$s will be replaced by a URL. */
		$content = Helper::get_logo_img() . '<h2>' . __( 'Connector for Propstack', 'connector-for-propstack' ) . '</h2><p>' . __( 'The Connector for Propstack plugin allows you to import your properties from Propstack into WordPress. It lets you display all stored property data on your website.<br><br>Select the section on the left for which you need help.', 'connector-for-propstack' ) . '</p>';

		// add the help entry.
		$help_list[] = array(
			'id'      => self::get_instance()->get_name() . '-' . $this->get_name(),
			'title'   => __( 'Connector for Propstack', 'connector-for-propstack' ),
			'content' => $content,
		);

		// return the resulting list.
		return $help_list;
	}

	/**
	 * Return the ID of our example image for immo objects.
	 *
	 * @return int
	 */
	public function get_example_image_id(): int {
		// use the cached attachment ID if it still points to a valid attachment.
		$attachment_id = absint( get_option( 'cfprop_example_image_id', 0 ) );
		if ( $attachment_id > 0 && 'attachment' === get_post_type( $attachment_id ) ) {
			return $attachment_id;
		}

		// otherwise import the example image.
		require_once ABSPATH . 'wp-admin/includes/image.php'; // @phpstan-ignore requireOnce.fileNotFound
		require_once ABSPATH . 'wp-admin/includes/file.php';  // @phpstan-ignore requireOnce.fileNotFound
		require_once ABSPATH . 'wp-admin/includes/media.php'; // @phpstan-ignore requireOnce.fileNotFound

		// get the WP_Filesystem object.
		$wp_filesystem = Helper::get_wp_filesystem();

		// create a tmp file with a path.
		$tmp_file = wp_tempnam( 'immo-object-default-image.jpg' );

		// copy our example image as media_handle_sideload() will delete it.
		if ( ! $wp_filesystem->copy( Helper::get_plugin_path() . 'gfx/immo-object-default-image.jpg', $tmp_file, true ) ) {
			Log::get_instance()->add( __( 'Could not copy the example image. Please check your file system permissions.', 'connector-for-propstack' ), 'error', 'system' );
			return 0;
		}

		// create the array to add the image.
		$file_array    = array(
			'type'     => 'image/png',
			'name'     => 'immo-object-default-image.jpg',
			'tmp_name' => $tmp_file,
			'error'    => '0',
			'size'     => (string) $wp_filesystem->size( $tmp_file ),
		);
		$attachment_id = media_handle_sideload( $file_array, 0, null, array( 'post_author' => get_current_user_id() ) );

		// delete the tmp file if media_handle_sideload() does it not.
		if ( $wp_filesystem->exists( $tmp_file ) ) {
			$wp_filesystem->delete( $tmp_file );
		}

		// bail if no image could be imported.
		if ( $attachment_id instanceof WP_Error ) {
			Log::get_instance()->add( __( 'Could not save the example image. Following error occurred:', 'connector-for-propstack' ) . ' <code>' . $attachment_id->get_error_message() . '</code>', 'error', 'system' );
			return 0;
		}

		// mark this image as the example image and cache its ID.
		update_post_meta( $attachment_id, 'cfprop_example_image', true );
		update_option( 'cfprop_example_image_id', $attachment_id );

		// return the resulting attachment ID.
		return $attachment_id;
	}

	/**
	 * Remove our post type from the list of supported post types in Brizy.
	 *
	 * @param array<string,mixed> $post_types List of post types.
	 *
	 * @return array<string,mixed>
	 */
	public function brizy_settings_post_types( array $post_types ): array {
		// bail if our cpt is not in list.
		if ( ! isset( $post_types[ self::get_instance()->get_name() ] ) ) {
			return $post_types;
		}

		// remove the entry.
		unset( $post_types[ self::get_instance()->get_name() ] );

		// return the resulting post types.
		return $post_types;
	}

	/**
	 * Remove Yoast's columns from our own cpt.
	 *
	 * @param array<string,mixed> $columns List of columns.
	 *
	 * @return array<string,mixed>
	 */
	public function remove_yoast_columns( array $columns ): array {
		if ( isset( $columns['wpseo-score'] ) ) {
			unset( $columns['wpseo-score'], $columns['wpseo-score-readability'], $columns['wpseo-title'], $columns['wpseo-metadesc'], $columns['wpseo-focuskw'], $columns['wpseo-links'], $columns['wpseo-linked'] );
		}
		return $columns;
	}

	/**
	 * Remove Rank Math's columns from our own cpt.
	 *
	 * @param array<string,mixed> $columns List of columns.
	 *
	 * @return array<string,mixed>
	 */
	public function remove_rank_math_columns( array $columns ): array {
		if ( isset( $columns['rank_math_seo_details'] ) ) {
			unset( $columns['rank_math_seo_details'], $columns['rank_math_title'], $columns['rank_math_description'] );
		}
		return $columns;
	}

	/**
	 * Hide translation-option on our own custom post-type pages.
	 *
	 * @param string $capability The actual capability.
	 *
	 * @return string
	 */
	public function translatepress_hide_option( string $capability ): string {
		// bail if this is admin.
		if ( is_admin() ) {
			return $capability;
		}

		// get actual object.
		$object_id = get_queried_object_id();

		// bail if this is not our cpt.
		if ( get_post_type( $object_id ) !== self::get_instance()->get_name() ) {
			return $capability;
		}

		// return 'god' to disable any translation-options on our cpt.
		return 'god';
	}

	/**
	 * Prevent usage of order our own ctp positions via plugin Simple Custom Order.
	 *
	 * @return void
	 */
	public function scpo_remove_filter(): void {
		global $pagenow;
		if ( 'edit-' . self::get_instance()->get_name() . '.php' === $pagenow ) {
			wp_dequeue_script( 'scporderjs' );
		}
	}

	/**
	 * Optimize output of plugin OG.
	 *
	 * @source https://de.wordpress.org/plugins/og/
	 * @param array<string,array<mixed>> $og_array List of OpenGraph-settings from OG-plugin.
	 * @return array<string,array<mixed>>
	 */
	public function og_optimizer( array $og_array ): array {
		// bail if requested object is not ours.
		if ( ! is_singular( self::get_instance()->get_name() ) ) {
			return $og_array;
		}

		// update settings.
		$immo_object                        = new \ConnectorForPropstack\Propstack\ImmoObject( get_queried_object_id() );
		$og_array['og']['title']            = $immo_object->get_title();
		$og_array['og']['description']      = '';
		$og_array['twitter']['title']       = $immo_object->get_title();
		$og_array['twitter']['description'] = '';
		$og_array['schema']['title']        = $immo_object->get_title();
		$og_array['schema']['description']  = '';

		// return the resulting list.
		return $og_array;
	}

	/**
	 * Optimize output for description with plugin SEOFramework.
	 *
	 * @param array<string,mixed> $fields The SEO-fields the framework has collected.
	 *
	 * @return array<string,mixed>
	 */
	public function seoframework( array $fields ): array {
		// bail if requested object is not ours.
		if ( ! is_singular( self::get_instance()->get_name() ) ) {
			return $fields;
		}

		// update settings.
		$fields['description']['attributes']['content']         = '';
		$fields['og:description']['attributes']['content']      = '';
		$fields['twitter:description']['attributes']['content'] = '';

		// return resulting fields.
		return $fields;
	}

	/**
	 * Optimize output for schema with plugin SEOFramework.
	 *
	 * @param array<string,mixed> $graph A list of fields for SEO-output.
	 *
	 * @return array<string,mixed>
	 */
	public function seoframework_schema( array $graph ): array {
		// bail if requested object is not ours.
		if ( ! is_singular( self::get_instance()->get_name() ) ) {
			return $graph;
		}

		foreach ( $graph as $index => $entry ) {
			if ( ! empty( $entry['description'] ) && 'WebPage' === $entry['@type'] ) {
				$graph[ $index ]['description'] = '';
			}
		}

		// return the resulting graph.
		return $graph;
	}

	/**
	 * Optimize the output for SEO-og:description with plugin SEOPress.
	 *
	 * @param string $meta_og_description The meta-tag the plugin would use as the description.
	 *
	 * @return string
	 */
	public function seopress_og_description( string $meta_og_description ): string {
		// bail if requested object is not ours.
		if ( ! is_singular( self::get_instance()->get_name() ) ) {
			return $meta_og_description;
		}

		// get og:description.
		return '<meta property="og:description" content="" />';
	}

	/**
	 * Optimize the output for SEO-description with plugin SEOPress.
	 *
	 * @param string $description The SEO-description text.
	 *
	 * @return string
	 */
	public function seopress_titles( string $description ): string {
		// bail if requested object is not ours.
		if ( ! is_singular( self::get_instance()->get_name() ) ) {
			return $description;
		}

		// get description.
		return '';
	}

	/**
	 * Remove WPML translation columns.
	 *
	 * @param array<string,mixed> $columns List of columns.
	 *
	 * @return array<string,mixed>
	 */
	public function remove_wpml_column( array $columns ): array {
		if ( isset( $columns['icl_translations'] ) ) {
			unset( $columns['icl_translations'] );
		}

		// return the resulting list.
		return $columns;
	}

	/**
	 * Prevent visibility of duplicate options on position details during usage if plugin Duplicate Page.
	 *
	 * @return void
	 */
	public function duplicate_page_prevent_options(): void {
		// bail if we are not logged in.
		if ( ! is_user_logged_in() ) {
			return;
		}

		// bail if the plugin Duplicate Page is not active.
		if ( ! Helper::is_plugin_active( 'duplicate-page/duplicatepage.php' ) ) {
			return;
		}

		// bail if we are in the backend.
		if ( is_admin() ) {
			return;
		}

		// bail if this is not our cpt.
		if ( ! $this->is_single_page_called() ) {
			return;
		}

		global $wp_admin_bar;
		$wp_admin_bar->remove_node( 'duplicate_this' );
	}

	/**
	 * Return whether a single page of our own custom post type is called in frontend.
	 *
	 * @return bool
	 */
	private function is_single_page_called(): bool {
		// bail if single is not called.
		if ( ! is_single() ) {
			return false;
		}

		// get the queried object.
		$object = get_queried_object();

		// return true if the object is from our own cpt.
		return ( $object instanceof WP_Post && $this->get_name() === $object->post_type );
	}

	/**
	 * We simply return true to register the plugin with WP Consent API, although we do not use it
	 * as this plugin does not set any cookies or collect any personal data.
	 *
	 * @return bool
	 */
	public function wp_consent_api_register(): bool {
		return true;
	}
}

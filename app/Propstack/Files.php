<?php
/**
 * File for handling any files from Propstack.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Fields\Button;
use easySettingsForWordPress\Fields\Number;
use easySettingsForWordPress\Fields\Select;
use easySettingsForWordPress\Fields\TextInfo;
use easySettingsForWordPress\Page;
use easySettingsForWordPress\Section;
use easySettingsForWordPress\Tab;
use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients;
use ConnectorForPropstack\Plugin\Db;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Plugin\ProcessHandler;
use ConnectorForPropstack\Plugin\Settings;
use ConnectorForPropstack\Plugin\Users;
use ConnectorForPropstack\Propstack\Fields\Main\ObjectId;
use Throwable;
use WP_Post;
use WP_Query;

/**
 * Object to handle any files from Propstack.
 */
class Files {

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Files
	 */
	private static ?Files $instance = null;

	/**
	 * The option which holds the metadata of the list of files to import.
	 *
	 * @var string
	 */
	private string $files_work_list_option = 'cfprop_files_to_import';

	/**
	 * The option which holds the position in the list of files to import.
	 *
	 * @var string
	 */
	private string $files_offset_option = 'cfprop_files_to_import_offset';

	/**
	 * Marker whether the last run of the import of files needs another run to be completed.
	 *
	 * @var bool
	 */
	private bool $load_more = false;

	/**
	 * Marker whether the last call of import() could not run as another process imports files right now.
	 *
	 * @var bool
	 */
	private bool $locked_by_other_process = false;

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
	public static function get_instance(): Files {
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
		// define constants.
		if ( ! defined( 'CFPROP_FILES_IMPORT_RUNNING' ) ) {
			define( 'CFPROP_FILES_IMPORT_RUNNING', 'propstack_connector_files_import_running' );
		}
		if ( ! defined( 'CFPROP_FILES_DELETE_RUNNING' ) ) {
			define( 'CFPROP_FILES_DELETE_RUNNING', 'propstack_connector_files_delete_running' );
		}

		// use hooks.
		add_action( 'init', array( $this, 'add_settings' ), 20 );

		// use our own hooks.
		add_action( 'cfprop_files_before_import', array( $this, 'mark_files_as_not_updated' ), 10, 0 );
		add_action( 'cfprop_file_is_assigned', array( $this, 'mark_file_as_updated' ) );
		add_action( 'cfprop_files_for_object_imported', array( $this, 'delete_not_updated_files' ), 10, 0 );
		add_action( 'cfprop_files_for_object_imported_via_ajax', array( $this, 'delete_not_updated_files' ), 10, 0 );
		add_action( 'cfprop_files_for_object_imported', array( $this, 'update_file_counter' ), 100, 0 );
		add_action( 'cfprop_files_for_object_imported_via_ajax', array( $this, 'update_file_counter' ), 100, 0 );
		add_action( 'cfprop_files_deleted', array( $this, 'update_file_counter' ), 100, 0 );
		add_action( 'cfprop_queue_after_processing', array( $this, 'update_file_counter' ), 100, 0 );
		add_filter( 'cfprop_prevent_file_import', array( $this, 'prevent_file_import' ), 10, 2 );
		add_action( 'cfprop_import_object', array( $this, 'delete_unused_files' ), 10, 2 );

		// use actions.
		add_action( 'wp_ajax_cfprop_import_object_files', array( $this, 'import_by_ajax' ) );
		add_action( 'admin_action_cfprop_import_object_files', array( $this, 'import_by_request' ) );
		add_action( 'wp_ajax_cfprop_delete_object_files', array( $this, 'delete_by_ajax' ) );
		add_action( 'admin_action_cfprop_delete_object_files', array( $this, 'delete_by_request' ) );
	}

	/**
	 * Add settings regarding the file handling.
	 *
	 * @return void
	 */
	public function add_settings(): void {
		// get the settings object.
		$settings_obj = Settings::get_instance()->get_settings_obj();

		// get the settings page.
		$settings_page = Settings::get_instance()->get_settings_page();

		// bail if the page could not be found.
		if ( ! $settings_page instanceof Page ) {
			return;
		}

		// get the import tab.
		$import_tab = $settings_page->get_tab( 'propstack_connector_import' );

		// bail if the import tab could not be found.
		if ( ! $import_tab instanceof Tab ) {
			return;
		}

		// add a tab for file imports.
		$files_tab = $import_tab->add_tab( 'propstack_connector_files_import', 20 );
		$files_tab->set_title( __( 'Images', 'connector-for-propstack' ) );

		// add a section.
		$files_import_section = $files_tab->add_section( 'propstack_connector_files_import', 20 );
		$files_import_section->set_title( __( 'Import images', 'connector-for-propstack' ) );

		// create the import URL.
		$import_url = add_query_arg(
			array(
				'action' => 'cfprop_import_object_files',
				'nonce'  => wp_create_nonce( 'import-propstack-object-files' ),
			),
			get_admin_url() . 'admin.php'
		);

		// add setting for Button.
		$setting = $settings_obj->add_setting( 'propstack_connector_files_import' );
		$setting->set_section( $files_import_section );
		$setting->prevent_export( true );
		if ( empty( get_option( 'propstack_connector_api_key' ) ) ) {
			$field = new TextInfo( $settings_obj );
			$field->set_title( __( 'Import images', 'connector-for-propstack' ) );
			/* translators: %1$s  will ber replaced by a URL. */
			$field->set_description( sprintf( __( 'Propstack API key is missing. Add it <a href="%1$s">here</a>.', 'connector-for-propstack' ), Settings::get_instance()->get_url() ) );
		} elseif ( defined( 'CFPROP_FILES_IMPORT_RUNNING' ) && Helper::is_process_running( CFPROP_FILES_IMPORT_RUNNING ) ) {
			$field = new TextInfo( $settings_obj );
			$field->set_title( __( 'Import images', 'connector-for-propstack' ) );
			$field->set_description( __( 'Import of images for objects is still running. Please wait.', 'connector-for-propstack' ) );
		} elseif ( ! ImmoObjects::get_instance()->has_objects() ) {
			$field = new TextInfo( $settings_obj );
			$field->set_title( __( 'Import images', 'connector-for-propstack' ) );
			/* translators: %1$s will be replaced by a URL. */
			$field->set_description( sprintf( __( 'No objects imported. <a href="%1$s">Please import them first</a> to import their images.', 'connector-for-propstack' ), Settings::get_instance()->get_url( 'propstack_connector_import', 'propstack_connector_objects_import' ) ) );
		} else {
			$field = new Button( $settings_obj );
			$field->set_button_title( __( 'Import now', 'connector-for-propstack' ) );
			$field->set_title( __( 'Import images', 'connector-for-propstack' ) );
			$field->set_description( __( 'Import all images for all objects in one go. Existing images will not be changed.', 'connector-for-propstack' ) );
			$field->set_button_url( $import_url );
			$field->add_data(
				'dialog',
				(string) wp_json_encode(
					array(
						'className' => 'cfprop-dialog',
						'title'     => __( 'Import images for your objects from Propstack', 'connector-for-propstack' ),
						'texts'     => array(
							'<p><strong>' . __( 'Click on the button below to start the import of images for your objects from Propstack.', 'connector-for-propstack' ) . '</strong></p>',
							'<p>' . __( 'The import could take some time. Please be patient.', 'connector-for-propstack' ) . '</p>',
						),
						'buttons'   => array(
							array(
								'action'  => 'propstack_connector_object_files_import("' . esc_attr( ProcessHandler::get_instance()->create_id() ) . '");',
								'variant' => 'primary',
								'text'    => __( 'Import them now', 'connector-for-propstack' ),
							),
							array(
								'action'  => 'closeDialog();',
								'variant' => 'primary',
								'text'    => __( 'Cancel', 'connector-for-propstack' ),
							),
						),
					)
				)
			);
			$field->add_class( 'easy-dialog-for-wordpress' );
		}
		$setting->set_field( $field );

		// create the import URL.
		$delete_url = add_query_arg(
			array(
				'action' => 'cfprop_delete_object_files',
				'nonce'  => wp_create_nonce( 'delete-propstack-object-files' ),
			),
			get_admin_url() . 'admin.php'
		);

		// add setting for Button.
		$setting = $settings_obj->add_setting( 'propstack_connector_files_delete' );
		$setting->set_section( $files_import_section );
		$setting->prevent_export( true );
		if ( ! ImmoObjects::get_instance()->has_objects() ) {
			$field = new TextInfo( $settings_obj );
			$field->set_title( __( 'Delete files', 'connector-for-propstack' ) );
			$field->set_description( __( 'No objects imported.', 'connector-for-propstack' ) );
		} elseif ( ! $this->has_files() ) {
			$field = new TextInfo( $settings_obj );
			$field->set_title( __( 'Delete files', 'connector-for-propstack' ) );
			$field->set_description( __( 'No images for objects imported.', 'connector-for-propstack' ) );
		} else {
			$field = new Button( $settings_obj );
			$field->set_button_title( __( 'Delete now', 'connector-for-propstack' ) );
			$field->set_title( __( 'Delete files', 'connector-for-propstack' ) );
			$field->set_description( __( 'Delete all images of your objects in WordPress. You will be asked to confirm before the process begins. Afterward, you can import them again from Propstack at any time.', 'connector-for-propstack' ) );
			$field->set_button_url( $delete_url );
			$field->add_data(
				'dialog',
				(string) wp_json_encode(
					array(
						'className' => 'cfprop-dialog',
						'title'     => __( 'Delete all images of your objects', 'connector-for-propstack' ),
						'texts'     => array(
							'<p><strong>' . __( 'Click on the button below to delete all images of your objects in your WordPress website.', 'connector-for-propstack' ) . '</strong></p>',
							'<p>' . __( 'You will lose any images for your objects. They will be re-imported during the next object import.', 'connector-for-propstack' ) . '</p>',
						),
						'buttons'   => array(
							array(
								'action'  => 'propstack_connector_object_file_delete("' . esc_attr( ProcessHandler::get_instance()->create_id() ) . '");',
								'variant' => 'primary',
								'text'    => __( 'Delete them now', 'connector-for-propstack' ),
							),
							array(
								'action'  => 'closeDialog();',
								'variant' => 'primary',
								'text'    => __( 'Close', 'connector-for-propstack' ),
							),
						),
					)
				)
			);
			$field->add_class( 'easy-dialog-for-wordpress' );
		}
		$setting->set_field( $field );

		// add a section.
		$import_options_section = $files_tab->add_section( 'propstack_connector_files_import_options', 30 );
		$import_options_section->set_title( __( 'Options', 'connector-for-propstack' ) );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_image_size' );
		$setting->set_type( 'string' );
		$setting->set_default( 'big_url' );
		$setting->set_section( $import_options_section );
		$field = new Select( $settings_obj );
		$field->set_title( __( 'Image size', 'connector-for-propstack' ) );
		$field->set_description( __( 'Choose the image size to use for import in WordPress. As bigger the size as longer takes the import of any image. WordPress only needs on image size as basis to optimize the generation of them in your website.', 'connector-for-propstack' ) );
		$field->set_options(
			array(
				'url'             => __( 'Original', 'connector-for-propstack' ),
				'big_url'         => __( 'Big URL', 'connector-for-propstack' ),
				'medium_url'      => __( 'Medium URL', 'connector-for-propstack' ),
				'thumb_url'       => __( 'Thumbnail', 'connector-for-propstack' ),
				'small_thumb_url' => __( 'Small thumbnail', 'connector-for-propstack' ),
				'square_url'      => __( 'Square image URL', 'connector-for-propstack' ),
			)
		);
		$setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_files_author' );
		$setting->set_section( $import_options_section );
		$setting->set_type( 'integer' );
		$setting->set_default( Users::get_instance()->get_first_administrator_user() );
		$field = new Select( $settings_obj );
		$field->set_title( __( 'Assign new images to this user', 'connector-for-propstack' ) );
		$field->set_description( __( 'This is only a fallback if the actual user is not available (e.g., via WP CLI import or synchronisation). New images are normally assigned to the user who adds them.', 'connector-for-propstack' ) );
		$field->set_options( Users::get_instance()->get_users_for_settings() );
		$setting->set_field( $field );

		// get the hidden section to add some hidden settings we could clean up during uninstallation.
		$hidden_section = Settings::get_instance()->get_hidden_section();

		// bail if the hidden section could not be loaded.
		if ( ! $hidden_section instanceof Section ) {
			return;
		}

		// add setting.
		$setting = $settings_obj->add_setting( CFPROP_FILES_IMPORT_RUNNING );
		$setting->set_section( $hidden_section );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
		$setting->prevent_export( true );

		// add setting.
		$setting = $settings_obj->add_setting( CFPROP_FILES_DELETE_RUNNING );
		$setting->set_section( $hidden_section );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
		$setting->prevent_export( true );

		// add setting.
		$setting = $settings_obj->add_setting( 'cfprop_files_imported' );
		$setting->set_section( $hidden_section );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
		$setting->prevent_export( true );

		// get the advanced settings tab.
		$advanced_tab = $settings_page->get_tab( 'propstack_connector_advanced' );
		if ( ! $advanced_tab instanceof Tab ) {
			return;
		}

		// get the advanced object settings tab.
		$advanced_object_settings_tab = $advanced_tab->get_tab( 'propstack_connector_advanced_images' );
		if ( ! $advanced_object_settings_tab instanceof Tab ) {
			return;
		}

		// get the section.
		$section = $advanced_object_settings_tab->get_section( 'propstack_connector_advanced_images' );
		if ( ! $section instanceof Section ) {
			return;
		}

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_ajax_limit' );
		$setting->set_type( 'integer' );
		$setting->set_default( 25 );
		$setting->set_section( $section );
		$field = new Number( $settings_obj );
		$field->set_title( __( 'Limit for import of images', 'connector-for-propstack' ) );
		$field->set_description( __( 'This limits the amount of images during one import run. If the limit is reached, a new import run is startet automatically. There is not hard limit to import images. The higher this number is, the greater the likelihood of a timeout when importing images.', 'connector-for-propstack' ) );
		$setting->set_field( $field );
	}

	/**
	 * Return the attachment ID of a given Propstack file URL.
	 *
	 * @param int $id The URL to check.
	 *
	 * @return int
	 */
	public function is_file_in_media_library( int $id ): int {
		// run the check.
		$query = array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup; admin/sync context.
				array(
					'key'     => 'propstack_file_id',
					'value'   => $id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
				// ignore broker avatars, as they use the broker ID as file ID.
				array(
					'key'     => 'cfprop_broker_avatar',
					'compare' => 'NOT EXISTS',
				),
			),
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'fields'         => 'ids',
		);
		$results = new WP_Query( $query );

		// bail if nothing was found.
		if ( empty( $results->posts ) ) {
			return 0;
		}

		// bail on a wrong entry.
		if ( ! is_int( $results->posts[0] ) ) {
			return 0;
		}

		// return the resulting attachment ID.
		return absint( $results->posts[0] );
	}

	/**
	 * Return the URL of a file from the Propstack API in the configured image size.
	 *
	 * API v1 delivers each image size as its own key (e.g. "big_url"). API v2 delivers them
	 * in the list "urls" with other names (e.g. "large"). If the chosen size is not delivered
	 * for a file, the original URL is used, so the file is imported anyway.
	 *
	 * @param array<string,mixed> $file The file data from Propstack API.
	 *
	 * @return string
	 */
	public function get_file_url( array $file ): string {
		// get the image size setting.
		$image_size = (string) get_option( 'propstack_connector_image_size', 'big_url' );

		// use the chosen size if the API delivers it under this name (API v1).
		if ( ! empty( $file[ $image_size ] ) && is_string( $file[ $image_size ] ) ) {
			return $file[ $image_size ];
		}

		// get the list of sizes API v2 delivers.
		$urls = ( isset( $file['urls'] ) && is_array( $file['urls'] ) ) ? $file['urls'] : array();

		// the names of our image sizes in the list of API v2.
		$mapping = array(
			'url'             => 'original',
			'big_url'         => 'large',
			'medium_url'      => 'medium',
			'thumb_url'       => 'thumb',
			'small_thumb_url' => 'small',
		);

		// use the chosen size from the list of API v2.
		if ( isset( $mapping[ $image_size ] ) && ! empty( $urls[ $mapping[ $image_size ] ] ) && is_string( $urls[ $mapping[ $image_size ] ] ) ) {
			return $urls[ $mapping[ $image_size ] ];
		}

		// fallback: use the original file, if the chosen size is not available (e.g. the square size in API v2).
		if ( ! empty( $file['url'] ) && is_string( $file['url'] ) ) {
			return $file['url'];
		}
		if ( ! empty( $urls['original'] ) && is_string( $urls['original'] ) ) {
			return $urls['original'];
		}

		// no URL could be found.
		return '';
	}

	/**
	 * Import a single file in the media library and assign it to the given object.
	 *
	 * @param int                 $post_id  The post-ID of the object the file should be assigned to.
	 * @param int                 $id       The file ID.
	 * @param string              $url      The URL to use for import.
	 * @param string              $filename The file name.
	 * @param array<string,mixed> $file_data The file data from Propstack.
	 * @param int                 $broker_id The broker ID, if this file is a broker avatar (optional).
	 *
	 * @return int
	 */
	public function import_file( int $post_id, int $id, string $url, string $filename, array $file_data, int $broker_id = 0 ): int {
		// bail if the given URL is already in the media library.
		// broker avatars are looked up by their broker, as their ID could collide with real file IDs.
		$attachment_id = $broker_id > 0 ? $this->get_broker_avatar( $broker_id ) : $this->is_file_in_media_library( $id );
		if ( $attachment_id > 0 ) {
			// add a log entry.
			/* translators: %1$s: the given URL. */
			Log::get_instance()->add( sprintf( __( 'Given URL %1$s is already in the database.', 'connector-for-propstack' ), ' <em>' . esc_html( $url ) . '</em>' ), 'info', 'import' );

			// return the attachment ID.
			return $attachment_id;
		}

		$false = false;
		/**
		 * Filter whether a given file should not be imported.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param bool $false Return true to prevent the import.
		 * @param array<string,mixed> $file_data The file data from Propstack.
		 * @param int  $id    The file ID.
		 * @param string  $url   The URL to use for import.
		 * @param string  $filename The file name.
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		if ( apply_filters( 'cfprop_prevent_file_import', $false, $file_data, $id, $url, $filename ) ) {
			return 0;
		}

		// require necessary files.
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// get the content-type of this file.
		$mime_type = wp_check_filetype( $filename );

		// download the file in a temporary file.
		$tmp_file = $this->download_file( $url, $filename );

		// bail if the file could not be downloaded.
		if ( empty( $tmp_file ) ) {
			return 0;
		}

		// import the file and remove the temporary file in any case.
		try {
			// create the query to add the file.
			$array = array(
				'name'     => $filename,
				'type'     => (string) $mime_type['type'],
				'tmp_name' => $tmp_file,
				'error'    => '0',
				'size'     => (string) filesize( $tmp_file ),
			);
			/**
			 * Filter the query to upload a file in the media library.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 * @param array<string,mixed> $array The parameter to use.
			 */
			$array = apply_filters( 'cfprop_file_import_array', $array );

			$post_array = array(
				'post_author' => Helper::get_author_during_object_creation(),
				'post_name'   => $filename,
			);
			/**
			 * Filter the post-query to upload a file in the media library.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 * @param array<string,mixed> $array The parameter to use.
			 */
			$post_array = apply_filters( 'cfprop_file_import_post_array', $post_array );

			// save the image in the media library.
			$attachment_id = media_handle_sideload( $array, $post_id, null, $post_array );
		} finally {
			// remove the temporary file if it still exists (e.g. if the sideload failed).
			if ( file_exists( $tmp_file ) ) {
				wp_delete_file( $tmp_file );
			}
		}

		// bail on error.
		if ( ! is_int( $attachment_id ) ) {
			// add a log entry.
			/* translators: %1$s: the given URL. */
			Log::get_instance()->add( sprintf( __( 'Import of file %1$s failed:', 'connector-for-propstack' ), '<em>' . esc_html( $url ) . '</em>' ) . ' <code>' . esc_html( Helper::get_json( $attachment_id ) ) . '</code>', 'error', 'import' );

			// return 0 as we could not import the file.
			return 0;
		}

		// add the used Propstack ID for the file to the file.
		update_post_meta( $attachment_id, 'propstack_file_id', $id );

		// mark broker avatars as such.
		if ( $broker_id > 0 ) {
			update_post_meta( $attachment_id, 'cfprop_broker_avatar', $broker_id );
		}

		// use the older attachment if another process has imported the same file in the meantime.
		$older_attachment_id = $this->get_older_duplicate( $attachment_id, $id, $broker_id );
		if ( $older_attachment_id > 0 ) {
			// remove the newer duplicate including its files.
			wp_delete_attachment( $attachment_id, true );

			// add a log entry.
			/* translators: %1$s: the given URL. */
			Log::get_instance()->add( sprintf( __( 'File %1$s has been imported by another process at the same time. The duplicate has been removed.', 'connector-for-propstack' ), '<em>' . esc_html( $url ) . '</em>' ), 'info', 'import' );

			// return the attachment ID of the older file.
			return $older_attachment_id;
		}

		// add the position from Propstack to the file, if given.
		if ( ! empty( $file_data['position'] ) ) {
			update_post_meta( $attachment_id, 'propstack_file_position', $file_data['position'] );
		}

		// add the object ID of the assigned object.
		update_post_meta( $attachment_id, 'propstack_file_object_id', Fields::get_instance()->get_field_value( $post_id, new ObjectId(), false, true ) );

		/**
		 * Run additional tasks after a file has been imported.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param int $attachment_id The attachment ID.
		 * @param int $id            The Propstack file ID.
		 * @param array<string,mixed> $file_data The file data from Propstack.
		 */
		do_action( 'cfprop_file_imported', $attachment_id, $id, $file_data );

		// return the resulting attachment ID.
		return $attachment_id;
	}

	/**
	 * Return the ID of an older attachment for the same Propstack file than the given attachment.
	 *
	 * Used to detect duplicates which result from two processes which import the same file at the same time.
	 *
	 * @param int $attachment_id The ID of the new attachment.
	 * @param int $id            The Propstack file ID.
	 * @param int $broker_id     The broker ID, if this file is a broker avatar.
	 *
	 * @return int The ID of the older attachment or 0 if there is none.
	 */
	private function get_older_duplicate( int $attachment_id, int $id, int $broker_id ): int {
		// search broker avatars by their broker, any other file by its Propstack file ID.
		$meta_query = array(
			array(
				'key'     => 'propstack_file_id',
				'value'   => $id,
				'compare' => '=',
				'type'    => 'NUMERIC',
			),
			array(
				'key'     => 'cfprop_broker_avatar',
				'compare' => 'NOT EXISTS',
			),
		);
		if ( $broker_id > 0 ) {
			$meta_query = array(
				array(
					'key'     => 'cfprop_broker_avatar',
					'value'   => $broker_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			);
		}

		// get the oldest attachment for this file.
		$results = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'any',
				'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup; sync context.
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'fields'         => 'ids',
			)
		);

		// bail if nothing was found.
		if ( empty( $results->posts ) || ! is_int( $results->posts[0] ) ) {
			return 0;
		}

		// bail if the given attachment is the oldest one.
		if ( $results->posts[0] >= $attachment_id ) {
			return 0;
		}

		// return the older attachment.
		return $results->posts[0];
	}

	/**
	 * Return the file name of a URL, without its query string and fragment.
	 *
	 * @param string $url The URL.
	 *
	 * @return string
	 */
	public function get_file_name_from_url( string $url ): string {
		// get the path of the URL.
		$path = wp_parse_url( $url, PHP_URL_PATH );

		// use the complete URL if it has no usable path.
		if ( ! is_string( $path ) || '' === $path ) {
			$path = $url;
		}

		// return the last part of the path.
		return basename( $path );
	}

	/**
	 * Return the lock which prevents the import of files by two processes at the same time.
	 *
	 * It is used for a single run of the queue and for a single request of the import of all files.
	 *
	 * @return Lock
	 */
	public function get_import_lock(): Lock {
		$ttl = 10 * MINUTE_IN_SECONDS;
		/**
		 * Filter the time in seconds after which the lock for the import of files is treated as stale.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param int $ttl The time in seconds.
		 */
		$ttl = absint( apply_filters( 'cfprop_files_import_lock_ttl', $ttl ) );

		// return the lock.
		return new Lock( 'cfprop_files_import_lock', $ttl );
	}

	/**
	 * Download a file from the given URL in a temporary file.
	 *
	 * Only HTTPS-URLs are allowed. The download is limited in time and size.
	 * The caller is responsible for deleting the returned temporary file.
	 *
	 * @param string $url      The URL to download.
	 * @param string $filename The file name (used for the name of the temporary file).
	 *
	 * @return string The path to the temporary file or an empty string on any error.
	 */
	private function download_file( string $url, string $filename ): string {
		// bail if the URL is not allowed.
		if ( ! $this->is_allowed_file_url( $url ) ) {
			// add a log entry.
			/* translators: %1$s: the given URL. */
			Log::get_instance()->add( sprintf( __( 'Given URL %1$s is not allowed for the download of files.', 'connector-for-propstack' ), '<em>' . esc_html( $url ) . '</em>' ), 'error', 'import' );

			// return empty string as we could not download the file.
			return '';
		}

		$timeout = 60;
		/**
		 * Filter the timeout in seconds for the download of a single file.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param int    $timeout The timeout in seconds.
		 * @param string $url     The URL to download.
		 */
		$timeout = absint( apply_filters( 'cfprop_file_download_timeout', $timeout, $url ) );

		$max_size = 50 * MB_IN_BYTES;
		/**
		 * Filter the max size in bytes for a single file to download. Use 0 to disable the limit.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param int    $max_size The max size in bytes.
		 * @param string $url      The URL to download.
		 */
		$max_size = absint( apply_filters( 'cfprop_max_file_size', $max_size, $url ) );

		// get a temporary file.
		$tmp_file = wp_tempnam( $filename );

		// bail if no temporary file could be created.
		if ( empty( $tmp_file ) ) {
			return '';
		}

		// collect the arguments for the request.
		$args = array(
			'timeout'  => $timeout > 0 ? $timeout : 60,
			'stream'   => true,
			'filename' => $tmp_file,
		);

		// limit the response size (one byte more than allowed to detect too large files).
		if ( $max_size > 0 ) {
			$args['limit_response_size'] = $max_size + 1;
		}

		// download the file (which rejects unsafe URLs, e.g. local addresses).
		$response = wp_safe_remote_get( $url, $args );

		// bail on any error.
		if ( is_wp_error( $response ) || 200 !== absint( wp_remote_retrieve_response_code( $response ) ) ) {
			// add a log entry.
			/* translators: %1$s: the given URL. */
			Log::get_instance()->add( sprintf( __( 'Download of file %1$s failed:', 'connector-for-propstack' ), '<em>' . esc_html( $url ) . '</em>' ) . ' <code>' . ( is_wp_error( $response ) ? esc_html( $response->get_error_message() ) : absint( wp_remote_retrieve_response_code( $response ) ) ) . '</code>', 'error', 'import' );

			// remove the temporary file.
			if ( file_exists( $tmp_file ) ) {
				wp_delete_file( $tmp_file );
			}

			// return empty string as we could not download the file.
			return '';
		}

		// get the size of the downloaded file.
		clearstatcache( true, $tmp_file );
		$size = file_exists( $tmp_file ) ? absint( filesize( $tmp_file ) ) : 0;

		// bail if the file is empty or too large.
		if ( 0 === $size || ( $max_size > 0 && $size > $max_size ) ) {
			// add a log entry.
			/* translators: %1$s: the given URL. */
			Log::get_instance()->add( sprintf( __( 'Downloaded file %1$s is empty or larger than the allowed size.', 'connector-for-propstack' ), '<em>' . esc_html( $url ) . '</em>' ), 'error', 'import' );

			// remove the temporary file.
			if ( file_exists( $tmp_file ) ) {
				wp_delete_file( $tmp_file );
			}

			// return empty string as we could not use the file.
			return '';
		}

		// return the path to the temporary file.
		return $tmp_file;
	}

	/**
	 * Return whether the given URL is allowed for the download of files.
	 *
	 * Only valid HTTPS-URLs are allowed. Per default any public host is allowed, as unsafe
	 * URLs (e.g. local addresses) are rejected by wp_safe_remote_get(). Use the filter
	 * "cfprop_allowed_file_hosts" to restrict the hosts.
	 *
	 * @param string $url The URL to check.
	 *
	 * @return bool
	 */
	private function is_allowed_file_url( string $url ): bool {
		// bail if the URL is not valid.
		if ( false === wp_http_validate_url( $url ) ) {
			return false;
		}

		// bail if the URL does not use HTTPS.
		if ( 'https' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
			return false;
		}

		// get the host of the URL.
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

		$allowed_hosts = array();
		/**
		 * Filter the list of hosts from which files may be downloaded.
		 *
		 * An empty list allows any public host. Subdomains of listed hosts are allowed, too.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param array<int,string> $allowed_hosts The list of allowed hosts.
		 * @param string            $url           The URL to check.
		 */
		$allowed_hosts = (array) apply_filters( 'cfprop_allowed_file_hosts', $allowed_hosts, $url );

		// allow any public host if no list is given.
		if ( empty( $allowed_hosts ) ) {
			return true;
		}

		// check the host against the list.
		foreach ( $allowed_hosts as $allowed_host ) {
			$allowed_host = strtolower( (string) $allowed_host );
			if ( $host === $allowed_host || str_ends_with( $host, '.' . $allowed_host ) ) {
				return true;
			}
		}

		// return false as the host is not allowed.
		return false;
	}

	/**
	 * Return the attachment ID of the avatar of the given broker.
	 *
	 * @param int $broker_id The broker ID from Propstack.
	 *
	 * @return int
	 */
	public function get_broker_avatar( int $broker_id ): int {
		// run the check.
		$query   = array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup; admin/sync context.
				array(
					'key'     => 'cfprop_broker_avatar',
					'value'   => $broker_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			),
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'fields'         => 'ids',
		);
		$results = new WP_Query( $query );

		// bail if nothing was found.
		if ( empty( $results->posts ) || ! is_int( $results->posts[0] ) ) {
			return 0;
		}

		// return the resulting attachment ID.
		return absint( $results->posts[0] );
	}

	/**
	 * Delete all files we imported from Propstack.
	 *
	 * @param string $process_id The process ID.
	 *
	 * @return void
	 */
	public function delete_all( string $process_id ): void {
		// bail if file import is running (a stale marker is released after a timeout).
		if ( Helper::is_process_running( CFPROP_FILES_IMPORT_RUNNING ) ) {
			return;
		}

		// get the process handler and set the process ID.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_id( $process_id );

		// set initial value.
		$process_handler->set_count( 0 );
		$process_handler->set_max_count( 0 );
		$process_handler->set_status( __( 'Deletion of images starting', 'connector-for-propstack' ) );
		$process_handler->set_running( time() );
		update_option( CFPROP_FILES_DELETE_RUNNING, time() );

		// get the list of files from Propstack that have been imported.
		$files = $this->get_files();

		// update the max count.
		$process_handler->set_max_count( $files->found_posts );

		// show cli hint.
		$progress = Helper::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Deleting imported files', $files->found_posts ) : null;

		// delete them images.
		foreach ( $files->get_posts() as $post ) {
			// bail on a wrong object.
			if ( ! $post instanceof WP_Post ) { // @phpstan-ignore instanceof.alwaysTrue
				$progress ? $progress->tick() : null;
				$process_handler->update_count( 1 );
				continue;
			}

			// update the status.
			/* translators: %1$s: the file name. */
			$process_handler->set_status( sprintf( __( 'Deletion of file %1$s', 'connector-for-propstack' ), '<em>' . $post->post_title . '</em>' ) );

			// get the assigned post-object of the immo object.
			$object_post_id = $post->post_parent;

			// remove the assignment of this attachment from the object.
			if ( $object_post_id > 0 ) {
				// get the list of images.
				$images = get_post_meta( $object_post_id, 'images', true );

				// create an array.
				if ( ! is_array( $images ) ) {
					$images = array();
				}

				// find the entry of this attachment.
				$key = array_search( $post->ID, $images, true );

				// remove it from the list.
				if ( false !== $key ) {
					unset( $images[ $key ] );
				}

				// save the updated list.
				update_post_meta( $object_post_id, 'images', $images );
			}

			// delete the image.
			wp_delete_attachment( $post->ID, true );

			// show progress.
			$progress ? $progress->tick() : null;
			$process_handler->update_count( 1 );
		}

		// finish progress bar.
		$progress ? $progress->finish() : null;

		// create a dialog for the OK message.
		$dialog = array(
			'detail' => array(
				'className' => 'cfprop-dialog',
				'title'     => __( 'Files has been deleted', 'connector-for-propstack' ),
				'texts'     => array(
					'<p><strong>' . __( 'The images for your objects from Propstack has been deleted.', 'connector-for-propstack' ) . '</strong></p>',
					'<p>' . __( 'They are still in your Propstack account. They will be imported during the next object import.', 'connector-for-propstack' ) . '</p>',
				),
				'buttons'   => array(
					array(
						'action'  => 'location.reload();',
						'variant' => 'primary',
						'text'    => __( 'OK', 'connector-for-propstack' ),
					),
				),
			),
		);

		// return it.
		$process_handler->set_message( $dialog );

		/**
		 * Run additional tasks after all files for objects have been deleted.
		 */
		do_action( 'cfprop_files_deleted' );

		// end the process.
		$process_handler->set_running( 0 );
		update_option( CFPROP_FILES_DELETE_RUNNING, 0 );
	}

	/**
	 * Mark files as not updated.
	 *
	 * This is used at the start of a file import. Files without an update marker are handled
	 * as not updated. If the import runs for a single object, only the files of this object
	 * are marked, otherwise the marker is removed from all files in one query
	 *
	 * @return void
	 */
	public function mark_files_as_not_updated(): void {
		// get the post-ID of the object from the request.
		$post_id = $this->get_post_id_from_request();

		// remove the marker from all files if the import runs for all objects.
		if ( 0 === $post_id ) {
			delete_metadata( 'post', 0, 'propstack_file_updated', '', true );
			return;
		}

		// remove the marker only from the files of this object.
		add_filter( 'cfprop_files_query', array( $this, 'set_post_id_filter' ) );
		foreach ( $this->get_files()->get_posts() as $file ) {
			if ( ! $file instanceof WP_Post ) {
				continue;
			}
			delete_post_meta( $file->ID, 'propstack_file_updated' );
		}
		remove_filter( 'cfprop_files_query', array( $this, 'set_post_id_filter' ) );
	}

	/**
	 * Mark files as updated.
	 *
	 * @param int $attachment_id The attachment ID.
	 *
	 * @return void
	 */
	public function mark_file_as_updated( int $attachment_id ): void {
		update_post_meta( $attachment_id, 'propstack_file_updated', time() );
	}

	/**
	 * Return the list of all files in the media library we imported from Propstack.
	 *
	 * @return WP_Query
	 */
	public function get_files(): WP_Query {
		$query = array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup; admin/sync context.
				array(
					'key'     => 'propstack_file_id',
					'compare' => 'EXIST',
				),
			),
			'posts_per_page' => -1,
		);

		/**
		 * Filter the query to get the list of files we imported from Propstack.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array $query The query array.
		 */
		$query = apply_filters( 'cfprop_files_query', $query );

		// return the results.
		return new WP_Query( $query );
	}

	/**
	 * Delete not updated files.
	 *
	 * @return void
	 */
	public function delete_not_updated_files(): void {
		// add a filter to restrict the query to a single object.
		add_filter( 'cfprop_files_query', array( $this, 'set_post_id_filter' ) );

		// delete the not updated files.
		foreach ( $this->get_files()->get_posts() as $file ) {
			if ( ! $file instanceof WP_Post ) {
				continue;
			}

			// ignore broker avatars, as they are not part of any object.
			if ( absint( get_post_meta( $file->ID, 'cfprop_broker_avatar', true ) ) > 0 ) {
				continue;
			}

			if ( 0 === absint( get_post_meta( $file->ID, 'propstack_file_updated', true ) ) ) {
				// remove the file from the list of images of its object.
				if ( $file->post_parent > 0 ) {
					$images = get_post_meta( $file->post_parent, 'images', true );
					if ( is_array( $images ) && in_array( $file->ID, $images, true ) ) {
						update_post_meta( $file->post_parent, 'images', array_values( array_diff( $images, array( $file->ID ) ) ) );
					}
				}

				// delete the file.
				wp_delete_attachment( $file->ID, true );
			}
		}

		// remove the filter.
		remove_filter( 'cfprop_files_query', array( $this, 'set_post_id_filter' ) );
	}

	/**
	 * Run deletion of object files by request.
	 *
	 * @return void
	 */
	public function delete_by_request(): void {
		// check nonce.
		check_admin_referer( 'delete-propstack-object-files', 'nonce' );

		// bail if capability is missing.
		if ( ! current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// get the process ID from the request.
		$process_id = filter_input( INPUT_POST, 'process_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( is_null( $process_id ) ) {
			$process_id = '';
		}

		// run the deletion.
		$this->delete_all( $process_id );

		// show hint.
		$transient_obj = Transients::get_instance()->add();
		$transient_obj->set_name( 'propstack_object_files_delection_run' );
		$transient_obj->set_message( '<strong>' . __( 'The deletion of objects has been run.', 'connector-for-propstack' ) . '</strong> ' . __( 'They are unchanged in your Propstack account. You can import them at any time again.', 'connector-for-propstack' ) );
		$transient_obj->set_type( 'success' );
		$transient_obj->save();

		// redirect the user.
		wp_safe_redirect( (string) wp_get_referer() );
	}

	/**
	 * Run import of objects by AJAX.
	 *
	 * @return void
	 */
	public function delete_by_ajax(): void {
		// check nonce.
		check_ajax_referer( 'delete-propstack-object-files', 'nonce' );

		// bail if capability is missing.
		if ( ! current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// get the process ID from the request.
		$process_id = filter_input( INPUT_POST, 'process_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( is_null( $process_id ) ) {
			$process_id = '';
		}

		// run the deletion.
		$this->delete_all( $process_id );

		// send ok.
		wp_send_json_success();
	}

	/**
	 * Run import of object files by AJAX.
	 *
	 * Hint:
	 * As this takes some time and to prevent timeouts, we use a paginated AJAX-based dialog.
	 * We import X entries per request and answer with "load_more" to create a new request.
	 *
	 * @return void
	 */
	public function import_by_ajax(): void {
		// check nonce.
		check_ajax_referer( 'import-propstack-object-files', 'nonce' );

		// bail if capability is missing.
		if ( ! current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// get the process ID from the request.
		$process_id = filter_input( INPUT_POST, 'process_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( is_null( $process_id ) ) {
			$process_id = '';
		}

		// run the import.
		$this->import( 0, $process_id );

		// send ok.
		wp_send_json_success();
	}

	/**
	 * Import the files for all objects.
	 *
	 * This process is running in loops with a limited amount of files per run.
	 * It must be reloaded to continue. Via AJAX, this method answers with "load_more" itself.
	 * Any other caller has to check has_load_more() and call it again.
	 *
	 * The list of files to import is stored in blocks in options, the position in a separate
	 * option. Each run takes the lock for the import of files, so no file is imported by two
	 * requests (e.g. the queue and this import) at the same time.
	 *
	 * @param int    $post_id The post-ID of the immo object these files should be assigned to.
	 * @param string $process_id The process ID.
	 *
	 * @return void
	 */
	public function import( int $post_id = 0, string $process_id = '' ): void {
		// this run does not need another run and is not locked until now.
		$this->load_more               = false;
		$this->locked_by_other_process = false;

		// bail if deletion is running (a stale marker is released after a timeout).
		if ( Helper::is_process_running( CFPROP_FILES_DELETE_RUNNING ) ) {
			return;
		}

		// get post-ID from the request.
		$post_id_from_request = $this->get_post_id_from_request();
		if ( $post_id_from_request > 0 ) {
			$post_id = $post_id_from_request;
		}

		// bail if another process imports files right now.
		$lock = $this->get_import_lock();
		if ( ! $lock->acquire() ) {
			$this->handle_locked_import( $process_id );
		} else {
			// run this chunk and release the lock in any case.
			try {
				$this->import_chunk( $post_id, $process_id, $lock );
			} catch ( Throwable $e ) {
				// log this event.
				Log::get_instance()->add( __( 'Following error occurred during the import of files:', 'connector-for-propstack' ) . '<br>' . Helper::get_throwable_as_log_text( $e ), 'error', 'import' );

				// end this import, it cannot be continued.
				$this->end_files_import_after_error();
			} finally {
				$lock->release();
			}
		}

		// request another run via AJAX if the import is not completed yet.
		if ( $this->load_more && wp_doing_ajax() ) {
			// keep the marker set: the next request refreshes it, a stale marker expires via Helper::is_process_running().
			wp_send_json( array( 'load_more' => 1 ) );
		}
	}

	/**
	 * Return whether the last call of import() needs another run to complete the import of files.
	 *
	 * @return bool
	 */
	public function has_load_more(): bool {
		return $this->load_more;
	}

	/**
	 * Return whether the last call of import() did not import anything as another process imports files right now.
	 *
	 * @return bool
	 */
	public function is_locked_by_other_process(): bool {
		return $this->locked_by_other_process;
	}

	/**
	 * Run a single chunk of the import of files while this request holds the lock for the import of files.
	 *
	 * @param int    $post_id    The post-ID of the immo object these files should be assigned to (0 for all objects).
	 * @param string $process_id The process ID.
	 * @param Lock   $lock       The lock for the import of files.
	 *
	 * @return void
	 */
	private function import_chunk( int $post_id, string $process_id, Lock $lock ): void {
		// get the "immo objects" object.
		$immo_objects_obj = ImmoObjects::get_instance();

		// get the process handler and set the process ID.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_id( $process_id );

		// the owner of the list of files: the list must belong to this import process (same process and same object).
		// otherwise, a list left over from an aborted import (e.g. for another object) would be used.
		$list_owner = $process_id . '|' . $post_id;

		// get the list of files to import of an import which is already in progress.
		$work_list = $this->get_files_work_list();

		// handle a list of another import.
		if ( ! empty( $work_list ) && ( $work_list['owner'] ?? '' ) !== $list_owner ) {
			// wait if the other import is still in progress.
			if ( ( time() - absint( $work_list['updated'] ?? 0 ) ) < $this->get_files_import_takeover_time() ) {
				$this->handle_locked_import( $process_id );

				// do nothing more.
				return;
			}

			// remove the list of the aborted import.
			$this->clear_files_work_list();
			$work_list = array();
		}

		// discard a list which is older than a day.
		if ( ! empty( $work_list ) && ( time() - absint( $work_list['created'] ?? 0 ) ) > DAY_IN_SECONDS ) {
			$this->clear_files_work_list();
			$work_list = array();
		}

		// set initial value.
		$process_handler->set_status( __( 'Import of files for your objects starting', 'connector-for-propstack' ) );
		$process_handler->set_running( time() );

		// set global marker.
		update_option( CFPROP_FILES_IMPORT_RUNNING, time() );

		// add a log entry.
		Log::get_instance()->add( __( 'Prepare the import of files for your objects', 'connector-for-propstack' ), 'info', 'import' );

		// if the list is empty, create it.
		if ( empty( $work_list ) ) {
			$work_list = $this->build_files_work_list( $post_id, $list_owner, $process_handler );

			// bail if the list could not be saved.
			if ( empty( $work_list ) ) {
				// add a log entry.
				Log::get_instance()->add( __( 'The list of files to import could not be saved. The import of files has been aborted.', 'connector-for-propstack' ), 'error', 'import' );

				// end this import.
				$this->end_files_import_after_error();

				// do nothing more.
				return;
			}
		}

		// update marker.
		$process_handler->set_status( __( 'Import of files for your objects is starting', 'connector-for-propstack' ) );

		// add a log entry.
		Log::get_instance()->add( __( 'Import of files for your objects is starting', 'connector-for-propstack' ), 'info', 'import' );

		// get the position and the structure of the list.
		$offset     = absint( get_option( $this->files_offset_option, 0 ) );
		$total      = absint( $work_list['total'] ?? 0 );
		$block_size = max( 1, absint( $work_list['block_size'] ?? 1 ) );

		// set the counter for this run and the limit.
		$counter = 0;
		$limit   = absint( get_option( 'propstack_connector_ajax_limit' ) );

		// load the block with the next file.
		$block_index = intdiv( $offset, $block_size );
		$block       = get_option( $this->files_work_list_option . '_block_' . $block_index, array() );

		/**
		 * Filter the limit of files to import during object import.
		 *
		 * Hint: since 2.1.0 the list contains the files of the actual block of the list only.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param int $limit The limit.
		 * @param array<int|string,mixed> $files_to_import The list of files.
		 */
		$limit = (int) apply_filters( 'cfprop_files_import_limit', $limit, is_array( $block ) ? $block : array() );

		// show cli hint.
		$progress = Helper::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Import files', max( 0, $total - $offset ) ) : false;

		// loop through the list of files to import until we reach our limit.
		while ( $offset < $total ) {
			// bail if the limit has been reached: another run is needed.
			if ( $limit > 0 && $counter >= $limit ) {
				$this->load_more = true;
				break;
			}

			// keep the lock alive.
			$lock->refresh();

			// load the block of the next file, if it is not loaded yet.
			if ( intdiv( $offset, $block_size ) !== $block_index ) {
				$block_index = intdiv( $offset, $block_size );
				$block       = get_option( $this->files_work_list_option . '_block_' . $block_index, array() );
			}

			// bail if the block is gone - continuing would loop forever.
			if ( ! is_array( $block ) || empty( $block ) ) {
				/* translators: %1$d will be replaced by the block number. */
				Log::get_instance()->add( sprintf( __( 'Block %1$d of the list of files to import is missing. The import of files has been aborted.', 'connector-for-propstack' ), $block_index ), 'error', 'import' );

				// end this import.
				$this->end_files_import_after_error();

				// do nothing more.
				return;
			}

			// get the file.
			$file = $block[ $offset - ( $block_index * $block_size ) ] ?? null;

			// move the position forward before the file is processed: a file which aborts the request
			// (e.g. by the memory or time limit) is skipped by the next run instead of blocking the import.
			++$offset;
			++$counter;
			update_option( $this->files_offset_option, $offset, false );

			// remove the block as soon as its last file is taken.
			if ( 0 === $offset % $block_size || $offset >= $total ) {
				delete_option( $this->files_work_list_option . '_block_' . $block_index );
			}

			// import this file.
			try {
				$this->import_entry_of_list( $file, $process_handler, $immo_objects_obj );
			} catch ( Throwable $e ) {
				// add a log entry.
				Log::get_instance()->add( __( 'Following error occurred during the import of a file. The file has been skipped:', 'connector-for-propstack' ) . ' <code>' . esc_html( is_array( $file ) ? Helper::get_json( $file ) : (string) wp_json_encode( $file ) ) . '</code><br>' . Helper::get_throwable_as_log_text( $e ), 'error', 'import' );
			}

			// update the global counter.
			$process_handler->set_count( $process_handler->get_count() + 1 );
			$progress ? $progress->tick() : '';
		}

		// stop here if another run is needed.
		if ( $this->load_more ) {
			// remember that this import is still in progress.
			$work_list['updated'] = time();
			update_option( $this->files_work_list_option, $work_list, false );

			// do nothing more.
			return;
		}

		// get dialog.
		$dialog = array(
			'detail' => array(
				'className' => 'cfprop-dialog',
				'title'     => __( 'Import of files has been run', 'connector-for-propstack' ),
				'texts'     => array(
					'<p><strong>' . __( 'The files have been imported.', 'connector-for-propstack' ) . '</strong></p>',
					'<p>' . __( 'You can now see them on the object pages.', 'connector-for-propstack' ) . '</p>',
				),
				'buttons'   => array(
					array(
						'action'  => 'closeDialog();',
						'variant' => 'primary',
						'text'    => __( 'OK', 'connector-for-propstack' ),
					),
				),
			),
		);

		// change the dialog if we run a single import for an object.
		if ( $post_id > 0 ) {
			// get the object.
			$immo_object_obj = $immo_objects_obj->get_object( $post_id );
			/* translators: %1$s will be replaced by the immo object title. */
			$dialog['detail']['texts'][1]             = '<p>' . sprintf( __( 'You can now see them on the page of your object %1$s.', 'connector-for-propstack' ), '<em>' . esc_html( $immo_object_obj->get_title() ) . '</em>' ) . '</p>';
			$dialog['detail']['buttons'][0]['action'] = 'location.reload();';
			$dialog['detail']['buttons'][]            = array(
				'action'  => 'location.href="' . esc_url( $immo_object_obj->get_link() ) . '";',
				'variant' => 'secondary',
				'text'    => __( 'Show them', 'connector-for-propstack' ),
			);
		}

		// clear the list of files to import as we are completed the import.
		$this->clear_files_work_list();

		// add a log entry.
		Log::get_instance()->add( __( 'Import of files has been run', 'connector-for-propstack' ), 'info', 'import' );

		/**
		 * Run additional tasks after the files has been imported during the object import via AJAX.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param int $post_id The post-ID of the object (optional).
		 */
		do_action( 'cfprop_files_for_object_imported_via_ajax', $post_id );

		// update message.
		$process_handler->set_message( $dialog );

		// update the marker.
		$process_handler->set_running( 0 );
		if ( $progress ) {
			$progress->finish(); }
		update_option( CFPROP_FILES_IMPORT_RUNNING, 0 );
	}

	/**
	 * Import a single entry of the list of files to import.
	 *
	 * @param mixed          $file             The entry of the list.
	 * @param ProcessHandler $process_handler  The process handler.
	 * @param ImmoObjects    $immo_objects_obj The handler for immo objects.
	 *
	 * @return void
	 */
	private function import_entry_of_list( mixed $file, ProcessHandler $process_handler, ImmoObjects $immo_objects_obj ): void {
		// bail if the entry is not usable.
		if ( ! is_array( $file ) || empty( $file['wp_post_id'] ) || ! isset( $file['id'] ) ) {
			// add a log entry.
			Log::get_instance()->add( __( 'Unusable entry in the list of files to import. The used file data:', 'connector-for-propstack' ) . ' <code>' . esc_html( is_array( $file ) ? Helper::get_json( $file ) : (string) wp_json_encode( $file ) ) . '</code>', 'error', 'import' );

			// do nothing more.
			return;
		}

		// get the post-ID of the object.
		$object_post_id = absint( $file['wp_post_id'] );

		// bail if the object does not exist anymore.
		if ( get_post_type( $object_post_id ) !== PostTypes\ImmoObject::get_instance()->get_name() ) {
			return;
		}

		// get the immo object.
		$immo_object_obj = $immo_objects_obj->get_object( $object_post_id );

		// get the URL in the chosen image size (API v1 and v2 deliver them in different structures).
		$url = $this->get_file_url( $file );

		// bail if no URL could be found.
		if ( empty( $url ) ) {
			// add a log entry.
			Log::get_instance()->add( __( 'Missing URL for the file during file import. The used file data from API:', 'connector-for-propstack' ) . ' <code>' . esc_html( Helper::get_json( $file ) ) . '</code>', 'error', 'import' );

			// do nothing more.
			return;
		}

		// get the file name.
		$name = isset( $file['name'] ) && is_scalar( $file['name'] ) ? (string) $file['name'] : '';
		if ( empty( $name ) ) {
			$name = $this->get_file_name_from_url( $url );
		}

		// set new state text for process handler and setup.
		/* translators: %1$s will be replaced by the name of the file. */
		$new_state_text = sprintf( __( 'Import file %1$s for %2$s', 'connector-for-propstack' ), '<em>' . esc_html( $name ) . '</em>', '<em>' . esc_html( $immo_object_obj->get_title() ) . '</em>' );

		// update status.
		$process_handler->set_status( $new_state_text );

		/**
		 * Set the new state for the setup.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param string $new_state_text The new status.
		 */
		do_action( 'cfprop_import_object_set_status', $new_state_text );

		// import this single file.
		$attachment_id = $this->import_file( $object_post_id, absint( $file['id'] ), $url, $name, $file );

		// add this file to the list of images or document of the object.
		if ( $attachment_id > 0 ) {
			// update the corresponding list of files.
			$immo_object_obj->add_to_image_list( $attachment_id );
		}

		/**
		 * Run additional tasks after a file has been assigned to an object.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param int $attachment The attachment ID.
		 * @param array<string,mixed> $file The file data from Propstack API.
		 * @param ImmoObject $immo_object_obj The immo object.
		 */
		do_action( 'cfprop_file_is_assigned', $attachment_id, $file, $immo_object_obj );
	}

	/**
	 * Collect the files to import and save them as list in blocks.
	 *
	 * @param int            $post_id         The post-ID of the immo object (0 for all objects).
	 * @param string         $list_owner      The owner of the list.
	 * @param ProcessHandler $process_handler The process handler.
	 *
	 * @return array<string,mixed> The metadata of the list or an empty array if it could not be saved.
	 */
	private function build_files_work_list( int $post_id, string $list_owner, ProcessHandler $process_handler ): array {
		// get the "immo objects" object.
		$immo_objects_obj = ImmoObjects::get_instance();

		// create the empty list as an array.
		$files_to_import = array();

		// get the objects where we want to import the files.
		if ( $post_id > 0 ) {
			// update status.
			$process_handler->set_status( __( 'Collecting all files for your object to import', 'connector-for-propstack' ) );

			// add a log entry.
			Log::get_instance()->add( __( 'Collecting all files for your object to import', 'connector-for-propstack' ), 'info', 'import' );

			// a single object.
			$immo_objects = array( $immo_objects_obj->get_object( $post_id ) );
		} else {
			// update status.
			$process_handler->set_status( __( 'Collecting all files for your objects to import', 'connector-for-propstack' ) );

			// add a log entry.
			Log::get_instance()->add( __( 'Collecting all files for your objects to import', 'connector-for-propstack' ), 'info', 'import' );

			// all objects.
			$immo_objects = $immo_objects_obj->get_objects( array( 'posts_per_page' => -1 ) );
		}

		// loop through the objects to create the list of files to import.
		foreach ( $immo_objects as $object ) {
			// get the API response for this object.
			$api_response = $object->get_api_response();

			// bail if the object has no images.
			if ( empty( $api_response['images'] ) || ! is_array( $api_response['images'] ) ) {
				continue;
			}

			// add their images to the list.
			foreach ( $api_response['images'] as $document ) {
				// bail if this is not a file.
				if ( ! is_array( $document ) ) {
					continue;
				}

				$document['wp_post_id'] = $object->get_id();
				$files_to_import[]      = $document;
			}
		}

		/**
		 * Run additional tasks before the files are imported during object import.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,array<string,mixed>> $files_to_import The list of files.
		 */
		do_action( 'cfprop_files_before_import', $files_to_import );

		// remove any list left over from an aborted import.
		$this->clear_files_work_list();

		$block_size = 100;
		/**
		 * Filter the amount of files stored in a single block of the list of files to import.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param int $block_size The block size.
		 */
		$block_size = max( 1, absint( apply_filters( 'cfprop_files_import_block_size', $block_size ) ) );

		// save the list in blocks, which are not autoloaded.
		$blocks = array_chunk( $files_to_import, $block_size );
		foreach ( $blocks as $block_index => $block ) {
			if ( ! update_option( $this->files_work_list_option . '_block_' . $block_index, $block, false ) ) {
				// remove the blocks which have been saved.
				$this->clear_files_work_list();

				// return an empty list as the list could not be saved.
				return array();
			}
		}

		// save the metadata of the list.
		$work_list = array(
			'owner'      => $list_owner,
			'total'      => count( $files_to_import ),
			'blocks'     => count( $blocks ),
			'block_size' => $block_size,
			'created'    => time(),
			'updated'    => time(),
		);
		update_option( $this->files_work_list_option, $work_list, false );
		update_option( $this->files_offset_option, 0, false );

		// add a log entry.
		/* translators: %1$d will be replaced by the amount of files. */
		Log::get_instance()->add( sprintf( _n( '%1$d file will be imported.', '%1$d files will be imported.', count( $files_to_import ), 'connector-for-propstack' ), count( $files_to_import ) ), 'info', 'import' );

		// reset the counter as we start a new import.
		$process_handler->set_count( 0 );
		$process_handler->set_max_count( count( $files_to_import ) );

		// return the metadata of the list.
		return $work_list;
	}

	/**
	 * Return the metadata of the list of files to import of an import which is in progress.
	 *
	 * @return array<string,mixed>
	 */
	private function get_files_work_list(): array {
		// get the metadata.
		$work_list = get_option( $this->files_work_list_option, array() );

		// bail if it is not usable.
		if ( ! is_array( $work_list ) || ! isset( $work_list['owner'], $work_list['total'], $work_list['block_size'] ) ) {
			return array();
		}

		// return the metadata.
		return $work_list;
	}

	/**
	 * Remove the list of files to import with all its blocks and its position.
	 *
	 * The blocks are searched by their name, so blocks of an aborted import are removed, too.
	 *
	 * @return void
	 */
	public function clear_files_work_list(): void {
		global $wpdb;

		// get the names of all blocks.
		$options = Db::get_instance()->get_results(
			$wpdb->prepare(
				'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE %s',
				$wpdb->esc_like( $this->files_work_list_option . '_block_' ) . '%'
			)
		);

		// delete them via WordPress, so the object cache is cleaned, too.
		foreach ( $options as $option ) {
			// bail if the name is missing.
			if ( ! is_array( $option ) || ! isset( $option['option_name'] ) ) {
				continue;
			}

			delete_option( (string) $option['option_name'] );
		}

		// delete the metadata and the position.
		delete_option( $this->files_work_list_option );
		delete_option( $this->files_offset_option );

		// delete the list of versions before 2.1.0.
		delete_transient( 'propstack_object_files_to_import' );
		delete_transient( 'propstack_object_files_to_import_owner' );
	}

	/**
	 * Return the time in seconds after which the list of files of another, inactive import is replaced.
	 *
	 * @return int
	 */
	private function get_files_import_takeover_time(): int {
		$time = 5 * MINUTE_IN_SECONDS;
		/**
		 * Filter the time in seconds after which the list of files of another import is replaced,
		 * if this import did not continue in the meantime.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param int $time The time in seconds.
		 */
		return absint( apply_filters( 'cfprop_files_import_takeover_time', $time ) );
	}

	/**
	 * Handle a run which could not start as another process imports files right now.
	 *
	 * An AJAX request asks for another run, so the progress dialog polls again.
	 *
	 * @param string $process_id The process ID.
	 *
	 * @return void
	 */
	private function handle_locked_import( string $process_id ): void {
		// mark this run as locked, so callers which cannot poll could react on it.
		$this->locked_by_other_process = true;

		// update the status for the progress dialog.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_id( $process_id );
		$process_handler->set_status( __( 'Another import of files is still running. Please wait.', 'connector-for-propstack' ) );

		// bail if this is not an AJAX request, which cannot poll.
		if ( ! wp_doing_ajax() ) {
			// add a log entry.
			Log::get_instance()->add( __( 'Another import of files is still running. Please wait.', 'connector-for-propstack' ), 'info', 'import' );

			// do nothing more.
			return;
		}

		// request another run.
		$this->load_more = true;

		// wait a moment so the progress dialog does not hammer the server.
		sleep( 1 );
	}

	/**
	 * End the import of files after an error which prevents its continuation.
	 *
	 * @return void
	 */
	private function end_files_import_after_error(): void {
		// remove the list.
		$this->clear_files_work_list();

		// this import cannot be continued.
		$this->load_more = false;

		// update the markers.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_running( 0 );
		$process_handler->set_message(
			array(
				'detail' => array(
					'className' => 'cfprop-dialog',
					'title'     => __( 'Error', 'connector-for-propstack' ),
					'texts'     => array(
						/* translators: %1$s will be replaced by a URL. */
						'<p>' . sprintf( __( 'The import of files has been aborted. Check <a href="%1$s">the log</a> for details.', 'connector-for-propstack' ), esc_url( Settings::get_instance()->get_url( 'propstack_connector_logs' ) ) ) . '</p>',
					),
					'buttons'   => array(
						array(
							'action'  => 'closeDialog();',
							'variant' => 'primary',
							'text'    => __( 'OK', 'connector-for-propstack' ),
						),
					),
				),
			)
		);
		update_option( CFPROP_FILES_IMPORT_RUNNING, 0 );
	}

	/**
	 * Run import of object files by request.
	 *
	 * @return void
	 */
	public function import_by_request(): void {
		// check nonce.
		check_admin_referer( 'import-propstack-object-files', 'nonce' );

		// bail if capability is missing.
		if ( ! current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// get the process ID from the request.
		$process_id = filter_input( INPUT_POST, 'process_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( is_null( $process_id ) ) {
			$process_id = '';
		}

		// count the runs to prevent an endless loop on a broken state.
		$runs = 0;

		// run the import until it is completed, every run processes a limited amount of files.
		do {
			$this->import( 0, $process_id );

			++$runs;

			// bail if the import does not finish.
			if ( $runs > 10000 ) {
				Log::get_instance()->add( __( 'The import of files did not finish and has been stopped.', 'connector-for-propstack' ), 'error', 'import' );

				break;
			}
		} while ( $this->has_load_more() );

		// show hint.
		$transient_obj = Transients::get_instance()->add();
		$transient_obj->set_name( 'propstack_object_import_files_run' );
		if ( $this->is_locked_by_other_process() ) {
			// the files have not been imported as another process imports files right now.
			$transient_obj->set_message( '<strong>' . __( 'Another import of files is running. The import of files has not been completed.', 'connector-for-propstack' ) . '</strong><br>' . __( 'Please try again in a few minutes.', 'connector-for-propstack' ) );
			$transient_obj->set_type( 'error' );
		} else {
			$transient_obj->set_message( '<strong>' . __( 'The import of files from your Propstack account has been run.', 'connector-for-propstack' ) . '</strong>' );
			$transient_obj->set_type( 'success' );
		}
		$transient_obj->save();

		// redirect the user.
		wp_safe_redirect( (string) wp_get_referer() );
	}

	/**
	 * Return whether we have files for the objects imported in the media library.
	 *
	 * @return bool
	 */
	private function has_files(): bool {
		return absint( get_option( 'cfprop_files_imported', 0 ) ) > 0;
	}

	/**
	 * Update the file counter.
	 *
	 * @return void
	 */
	public function update_file_counter(): void {
		update_option( 'cfprop_files_imported', $this->get_files()->found_posts );
	}

	/**
	 * Set post-ID in the filter for objects to get their files.
	 *
	 * @param array<string,mixed> $query The query.
	 *
	 * @return array<string,mixed>
	 */
	public function set_post_id_filter( array $query ): array {
		// get the post-ID from the request.
		$post_id = $this->get_post_id_from_request();

		// bail if no post-ID is given.
		if ( 0 === $post_id ) {
			return $query;
		}

		// restrict to the files which are assigned to this object (the files are attachments of the object).
		$query['post_parent'] = $post_id;

		// return the resulting query.
		return $query;
	}

	/**
	 * Return the post-ID of the object from the request, if the import runs for a single object.
	 *
	 * @return int
	 */
	private function get_post_id_from_request(): int {
		// the nonce is checked by the calling AJAX handler.
		return isset( $_POST['post'] ) ? absint( wp_unslash( $_POST['post'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Prevent the import of a single file by its data.
	 *
	 * @param bool                $result_value The resulting value (true to prevent the import).
	 * @param array<string,mixed> $file_data The file data.
	 *
	 * @return bool
	 */
	public function prevent_file_import( bool $result_value, array $file_data ): bool {
		// prevent import if "is_private" is true.
		if ( isset( $file_data['is_private'] ) && $file_data['is_private'] ) {
			return true;
		}

		// prevent import if "is_not_for_exposee" is true.
		if ( isset( $file_data['is_not_for_exposee'] ) && $file_data['is_not_for_exposee'] ) {
			return true;
		}

		// return the result value.
		return $result_value;
	}

	/**
	 * Delete files from immo object, which does not return from Propstack anymore.
	 *
	 * Hint: we compare them by the given "propstack_file_id".
	 *
	 * @param array<string,mixed> $object_data The object data from API.
	 * @param int                 $post_id The post-ID of the object.
	 *
	 * @return void
	 */
	public function delete_unused_files( array $object_data, int $post_id ): void {
		// bail if the API response does not contain a list of images (this is not the same as "no images").
		if ( ! isset( $object_data['images'] ) || ! is_array( $object_data['images'] ) ) {
			return;
		}

		// get the immo object.
		$immo_object = ImmoObjects::get_instance()->get_object( $post_id );

		// get its images.
		$images = $immo_object->get_images();

		// bail if list of images is empty.
		if ( empty( $images ) ) {
			return;
		}

		// get the "propstack_file_id" for each file.
		$list = array();
		foreach ( $images as $attachment_id ) {
			$list[ $attachment_id ] = (string) get_post_meta( $attachment_id, 'propstack_file_id', true );
		}

		// clean the list of images on this object.
		$immo_object->clear_image_list();

		// loop through the images from the API and remove them from the local list, which are in the API list.
		foreach ( $object_data['images'] as $image ) {
			// get the given "propstack_file_id" from the list.
			$attachment_id = array_search( (string) $image['id'], $list, true );

			// bail if this ID is not in the list.
			if ( ! is_int( $attachment_id ) ) {
				continue;
			}

			// add this file to the new list of images on this object.
			$immo_object->add_to_image_list( $attachment_id );

			// remove this key from the list.
			unset( $list[ $attachment_id ] );
		}

		// bail if the resulting list is empty (aka: nothing has been changed).
		if ( empty( $list ) ) {
			return;
		}

		// delete all files from this list from media library.
		foreach ( $list as $attachment_id => $id ) {
			wp_delete_attachment( $attachment_id, true );
		}
	}
}

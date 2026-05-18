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
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Plugin\ProcessHandler;
use ConnectorForPropstack\Plugin\Settings;
use ConnectorForPropstack\Plugin\Users;
use ConnectorForPropstack\Propstack\Fields\Main\ObjectId;
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
		if ( ! defined( 'CONNECTOR_FOR_PROPSTACK_FILES_IMPORT_RUNNING' ) ) {
			define( 'CONNECTOR_FOR_PROPSTACK_FILES_IMPORT_RUNNING', 'propstack_connector_files_import_running' );
		}
		if ( ! defined( 'CONNECTOR_FOR_PROPSTACK_FILES_DELETE_RUNNING' ) ) {
			define( 'CONNECTOR_FOR_PROPSTACK_FILES_DELETE_RUNNING', 'propstack_connector_files_delete_running' );
		}

		// use hooks.
		add_action( 'init', array( $this, 'add_settings' ), 20 );

		// use our own hooks.
		add_action( 'cfprop_queue_before_processing', array( $this, 'mark_files_as_not_updated' ), 10, 0 );
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
		add_action( 'wp_ajax_import_propstack_object_files', array( $this, 'import_by_ajax' ) );
		add_action( 'admin_action_import_propstack_object_files', array( $this, 'import_by_request' ) );
		add_action( 'wp_ajax_delete_propstack_object_files', array( $this, 'delete_by_ajax' ) );
		add_action( 'admin_action_delete_propstack_object_files', array( $this, 'delete_by_request' ) );
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
		$files_tab->set_title( __( 'Files', 'connector-for-propstack' ) );

		// add a section.
		$files_import_section = $files_tab->add_section( 'propstack_connector_files_import', 20 );
		$files_import_section->set_title( __( 'Import images', 'connector-for-propstack' ) );

		// create the import URL.
		$import_url = add_query_arg(
			array(
				'action' => 'import_propstack_object_files',
				'nonce'  => wp_create_nonce( 'import-propstack-object-files' ),
			),
			get_admin_url() . 'admin.php'
		);

		// add setting for Button.
		$setting = $settings_obj->add_setting( 'propstack_connector_files_import' );
		$setting->set_section( $files_import_section );
		$setting->prevent_export( true );
		if ( defined( 'CONNECTOR_FOR_PROPSTACK_FILES_IMPORT_RUNNING' ) && absint( get_option( CONNECTOR_FOR_PROPSTACK_FILES_IMPORT_RUNNING ) ) > 0 ) {
			$field = new TextInfo( $settings_obj );
			$field->set_title( __( 'Import images', 'connector-for-propstack' ) );
			$field->set_description( __( 'Import of files for objects is still running. Please wait.', 'connector-for-propstack' ) );
		} elseif ( ! ImmoObjects::get_instance()->has_objects() ) {
			$field = new TextInfo( $settings_obj );
			$field->set_title( __( 'Import images', 'connector-for-propstack' ) );
			$field->set_description( __( 'No objects imported. Please import them first to import their files.', 'connector-for-propstack' ) );
		} else {
			$field = new Button( $settings_obj );
			$field->set_button_title( __( 'Import now', 'connector-for-propstack' ) );
			$field->set_title( __( 'Import images', 'connector-for-propstack' ) );
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
				'action' => 'delete_propstack_object_files',
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
			$field->set_description( __( 'No files for objects imported.', 'connector-for-propstack' ) );
		} else {
			$field = new Button( $settings_obj );
			$field->set_button_title( __( 'Delete now', 'connector-for-propstack' ) );
			$field->set_title( __( 'Delete files', 'connector-for-propstack' ) );
			$field->set_button_url( $delete_url );
			$field->add_data(
				'dialog',
				(string) wp_json_encode(
					array(
						'className' => 'cfprop-dialog',
						'title'     => __( 'Delete all files of your objects', 'connector-for-propstack' ),
						'texts'     => array(
							'<p><strong>' . __( 'Click on the button below to delete all files of your objects in your WordPress website.', 'connector-for-propstack' ) . '</strong></p>',
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
		$setting = $settings_obj->add_setting( 'propstack_connector_ajax_limit' );
		$setting->set_type( 'integer' );
		$setting->set_default( 25 );
		$setting->set_section( $import_options_section );
		$field = new Number( $settings_obj );
		$field->set_title( __( 'Limit for import of files', 'connector-for-propstack' ) );
		$field->set_description( __( 'The higher this number is, the greater the likelihood of a timeout when importing files.', 'connector-for-propstack' ) );
		$setting->set_field( $field );

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
		$field->set_title( __( 'Assign new files to this user', 'connector-for-propstack' ) );
		$field->set_description( __( 'This is only a fallback if the actual user is not available (e.g., via WP CLI import or synchronisation). New files are normally assigned to the user who adds them.', 'connector-for-propstack' ) );
		$field->set_options( Users::get_instance()->get_users_for_settings() );
		$setting->set_field( $field );

		// get the hidden section to add some hidden settings we could clean up during uninstallation.
		$hidden_section = Settings::get_instance()->get_hidden_section();

		// bail if the hidden section could not be loaded.
		if ( ! $hidden_section instanceof Section ) {
			return;
		}

		// add setting.
		$setting = $settings_obj->add_setting( CONNECTOR_FOR_PROPSTACK_FILES_IMPORT_RUNNING );
		$setting->set_section( $hidden_section );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
		$setting->prevent_export( true );

		// add setting.
		$setting = $settings_obj->add_setting( CONNECTOR_FOR_PROPSTACK_FILES_DELETE_RUNNING );
		$setting->set_section( $hidden_section );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
		$setting->prevent_export( true );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_files_imported' );
		$setting->set_section( $hidden_section );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
		$setting->prevent_export( true );
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
		$query   = array(
			'post_type'   => 'attachment',
			'post_status' => 'any',
			'meta_query'  => array(
				array(
					'key'     => 'propstack_file_id',
					'value'   => $id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			),
			'fields'      => 'ids',
		);
		$results = new WP_Query( $query );

		// bail if the image is already in the media library.
		if ( 1 === $results->found_posts ) {
			// bail on a wrong object.
			if ( ! is_int( $results->posts[0] ) ) {
				return 0;
			}

			// return the resulting attachment ID.
			return absint( $results->posts[0] );
		}

		// return 0 as we found nothing.
		return 0;
	}

	/**
	 * Import a single file in the media library and assign it to the given object.
	 *
	 * @param int                 $post_id  The post-ID of the object the file should be assigned to.
	 * @param int                 $id       The file ID.
	 * @param string              $url      The URL to use for import.
	 * @param string              $filename The file name.
	 * @param array<string,mixed> $file_data The file data from Propstack.
	 *
	 * @return int
	 */
	public function import_file( int $post_id, int $id, string $url, string $filename, array $file_data ): int {
		// bail if the given URL is already in the media library.
		$attachment_id = $this->is_file_in_media_library( $id );
		if ( $attachment_id > 0 ) {
			// add a log entry if debug is enabled.
			if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
				/* translators: %1$s: the given URL. */
				Log::get_instance()->add( sprintf( __( 'Given URL %1$s is already in the database.', 'connector-for-propstack' ), ' <em>' . $url . '</em>' ), 'info', 'import' );
			}

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

		// get "WP_Filesystem" object.
		$wp_filesystem = Helper::get_wp_filesystem();

		// get tmp file name.
		$tmp_file_name = wp_tempnam();

		// set the file as tmp-file for import.
		$tmp_file = str_replace( '.tmp', '', $tmp_file_name . '.' . $mime_type['ext'] );

		// get the file from the given URL.
		$file_content = $wp_filesystem->get_contents( $url );

		// bail if the content is not a string.
		if ( ! is_string( $file_content ) ) {
			return 0;
		}

		// save the file.
		$wp_filesystem->put_contents( $tmp_file, $file_content );

		// create the query to add the file.
		$array = array(
			'name'     => $filename,
			'type'     => (string) $mime_type['type'],
			'tmp_name' => $tmp_file,
			'error'    => '0',
			'size'     => (string) $wp_filesystem->size( $tmp_file ),
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

		// bail on error.
		if ( ! is_int( $attachment_id ) ) {
			// add a log entry.
			/* translators: %1$s: the given URL. */
			Log::get_instance()->add( sprintf( __( 'Import of file %1$s failed:', 'connector-for-propstack' ), '<em>' . $url . '</em>' ) . ' <code>' . Helper::get_json( $attachment_id ) . '</code>', 'error', 'import' );

			// return 0 as we could not import the file.
			return 0;
		}

		// add the used Propstack ID for the file to the file.
		update_post_meta( $attachment_id, 'propstack_file_id', $id );

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
	 * Delete all files we imported from Propstack.
	 *
	 * @return void
	 */
	public function delete_all(): void {
		// bail if file import is running.
		if ( absint( get_option( CONNECTOR_FOR_PROPSTACK_FILES_IMPORT_RUNNING, 0 ) ) > 0 ) {
			return;
		}

		// get the process ID from the request.
		$process_id = filter_input( INPUT_POST, 'process_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( is_null( $process_id ) ) {
			$process_id = '';
		}

		// get the process handler and set the process ID.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_id( $process_id );

		// set initial value.
		$process_handler->set_count( 0 );
		$process_handler->set_max_count( 0 );
		$process_handler->set_status( __( 'Deletion of files starting', 'connector-for-propstack' ) );
		$process_handler->set_running( time() );
		update_option( CONNECTOR_FOR_PROPSTACK_FILES_DELETE_RUNNING, time() );

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

				// find the entry.
				$key = array_search( $object_post_id, $images, true );

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
					'<p><strong>' . __( 'The files for your objects from Propstack has been deleted.', 'connector-for-propstack' ) . '</strong></p>',
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
		update_option( CONNECTOR_FOR_PROPSTACK_FILES_DELETE_RUNNING, 0 );
	}

	/**
	 * Mark files as not updated.
	 *
	 * @return void
	 */
	public function mark_files_as_not_updated(): void {
		foreach ( $this->get_files()->get_posts() as $file ) {
			if ( ! $file instanceof WP_Post ) {
				continue;
			}
			update_post_meta( $file->ID, 'propstack_file_updated', 0 );
		}
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
			'meta_query'     => array(
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
			if ( 0 === absint( get_post_meta( $file->ID, 'propstack_file_updated', true ) ) ) {
				wp_delete_attachment( $file->ID, true );
			}
		}

		// remove the filter.
		remove_filter( 'propstack_connector_files_query', array( $this, 'set_post_id_filter' ) );
	}

	/**
	 * Run deletion of object files by request.
	 *
	 * @return void
	 */
	public function delete_by_request(): void {
		// check nonce.
		check_admin_referer( 'delete-propstack-object-files', 'nonce' );

		// run the deletion.
		$this->delete_all();

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

		// run the deletion.
		$this->delete_all();

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

		// run the import.
		$this->import();

		// send ok.
		wp_send_json_success();
	}

	/**
	 * Import the files for all objects.
	 *
	 * This process is running in loops with a limited amount of files per run.
	 * It must be reloaded to continue.
	 *
	 * @param int $post_id The post-ID of the immo object these files should be assigned to.
	 *
	 * @return void
	 */
	public function import( int $post_id = 0 ): void {
		// bail if deletion is running.
		if ( absint( get_option( CONNECTOR_FOR_PROPSTACK_FILES_DELETE_RUNNING, 0 ) ) > 0 ) {
			return;
		}

		// get the "immo objects" object.
		$immo_objects_obj = ImmoObjects::get_instance();

		// get the process ID from the request.
		$process_id = filter_input( INPUT_POST, 'process_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( is_null( $process_id ) ) {
			$process_id = '';
		}

		// get the process handler and set the process ID.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_id( $process_id );

		// set initial value.
		$process_handler->set_status( __( 'Import of files for your objects starting', 'connector-for-propstack' ) );
		$process_handler->set_running( time() );

		// set global marker.
		update_option( CONNECTOR_FOR_PROPSTACK_FILES_IMPORT_RUNNING, time() );

		// get the cached list of files to import.
		$files_to_import = get_transient( 'propstack_object_files_to_import ' );

		// get post-ID from the request.
		$post_id_from_request = absint( filter_input( INPUT_POST, 'post', FILTER_SANITIZE_NUMBER_INT ) );
		if ( $post_id_from_request > 0 ) {
			$post_id = $post_id_from_request;
		}

		// add a log entry if debug is enabled.
		if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
			Log::get_instance()->add( __( 'Prepare the import of files for your objects', 'connector-for-propstack' ), 'info', 'import' );
		}

		// if the list is empty, create it.
		if ( empty( $files_to_import ) ) {
			// create the empty list as an array.
			$files_to_import = array();

			// get the objects where we want to import the files.
			if ( $post_id > 0 ) {
				// update status.
				$process_handler->set_status( __( 'Collecting all files for your object to import', 'connector-for-propstack' ) );

				// add a log entry if debug is enabled.
				if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
					Log::get_instance()->add( __( 'Collecting all files for your object to import', 'connector-for-propstack' ), 'info', 'import' );
				}

				// a single object.
				$immo_objects = array( $immo_objects_obj->get_object( $post_id ) );
			} else {
				// update status.
				$process_handler->set_status( __( 'Collecting all files for your objects to import', 'connector-for-propstack' ) );

				// add a log entry if debug is enabled.
				if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
					Log::get_instance()->add( __( 'Collecting all files for your objects to import', 'connector-for-propstack' ), 'info', 'import' );
				}

				// all objects.
				$immo_objects = $immo_objects_obj->get_objects( array( 'posts_per_page' => -1 ) );
			}

			// loop through the objects to create the list of files to import.
			foreach ( $immo_objects as $object ) {
				// get the API response for this object.
				$api_response = $object->get_api_response();

				if ( ! empty( $api_response['images'] ) ) {
					foreach ( $api_response['images'] as $index => $document ) {
						$api_response['images'][ $index ]['wp_post_id'] = $object->get_id();
					}
				}

				// add their images to the list.
				if ( ! empty( $api_response['images'] ) ) {
					$files_to_import = array_merge( $files_to_import, $api_response['images'] );
				}
			}

			/**
			 * Run additional tasks before the files are imported during object import.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 * @param array<string,mixed> $files_to_import The list of files.
			 */
			do_action( 'cfprop_files_before_import', $files_to_import );

			// save the list in the cache.
			set_transient( 'propstack_object_files_to_import', $files_to_import );

			// add a log entry if debug is enabled.
			if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
				Log::get_instance()->add( __( 'Cache stored during import:', 'connector-for-propstack' ) . ' <code>' . Helper::get_json( $files_to_import ) . '</code>', 'info', 'import' );
			}

			// reset the counter if we start a new import.
			$process_handler->set_count( 0 );
			$process_handler->set_max_count( count( $files_to_import ) );
		}

		// update marker.
		$process_handler->set_status( __( 'Import of files for your objects is starting', 'connector-for-propstack' ) );

		// add a log entry if debug is enabled.
		if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
			Log::get_instance()->add( __( 'Import of files for your objects is starting', 'connector-for-propstack' ), 'info', 'import' );
		}

		// set the counter for this run and the limit.
		$counter = 0;
		$limit   = absint( get_option( 'propstack_connector_ajax_limit' ) );

		/**
		 * Filter the limit of files to import during object import.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param int $limit The limit.
		 * @param array<string,mixed> $files_to_import The list of files.
		 */
		$limit = apply_filters( 'cfprop_files_import_limit', $limit, $files_to_import );

		// get the image size setting.
		$image_size = get_option( 'propstack_connector_image_size', 'big_url' );

		// show cli hint.
		$progress = Helper::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Import files', count( $files_to_import ) ) : false;

		// loop through the list of files to import until we reach our limit.
		foreach ( $files_to_import as $file_index => $file ) {
			// bail if the limit has been reached: send a simple AJAX response to start a new request and break the loop.
			if ( $limit > 0 && $counter >= $limit ) {
				wp_send_json( array( 'load_more' => 1 ) );
			}

			// get the immo object.
			$immo_object_obj = $immo_objects_obj->get_object( $file['wp_post_id'] );

			// get the type by checking if the chosen image size is given.
			$url = isset( $file[ $image_size ] ) ? $file[ $image_size ] : '';

			// bail if no URL could be found.
			if ( empty( $url ) ) {
				// add a log entry.
				Log::get_instance()->add( __( 'Missing URL for the file during file import. The used file data from API:', 'connector-for-propstack' ) . ' <code>' . Helper::get_json( $file ) . '</code>', 'error', 'import' );

				// update the counter.
				$process_handler->set_count( $process_handler->get_count() + 1 );
				$progress ? $progress->tick() : '';

				// continue with the next file.
				continue;
			}

			// get the file name.
			$name = isset( $file['name'] ) ? $file['name'] : '';
			if ( empty( $name ) ) {
				$name = basename( $url );
			}

			// set new state text for process handler and setup.
			/* translators: %1$s will be replaced by the name of the file. */
			$new_state_text = sprintf( __( 'Import file %1$s for %2$s', 'connector-for-propstack' ), '<em>' . $name . '</em>', '<em>' . $immo_object_obj->get_title() . '</em>' );

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
			$attachment_id = $this->import_file( $file['wp_post_id'], $file['id'], $url, $name, $file );

			// add this file to the list of images or document of the object.
			if ( $attachment_id > 0 ) {
				// update the corresponding list of files.
				$immo_object_obj->add_to_image_list( $attachment_id );
			}

			// remove this file from the list and update it.
			unset( $files_to_import[ $file_index ] );
			set_transient( 'propstack_object_files_to_import', $files_to_import );

			/**
			 * Run additional tasks after a file has been assigned to an object.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 * @param int $attachment The attachment ID.
			 * @param array<string,mixed> $file The file data from Propstack API.
			 * @param ImmoObject $immo_object_obj The immo object.
			 */
			do_action( 'cfprop_file_is_assigned', $attachment_id, $file, $immo_object_obj );

			// update this counter.
			++$counter;

			// update the global counter.
			$process_handler->set_count( $process_handler->get_count() + 1 );
			$progress ? $progress->tick() : '';
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
			$dialog['detail']['texts'][1]             = '<p>' . sprintf( __( 'You can now see them on the page of your object %1$s.', 'connector-for-propstack' ), '<em>' . $immo_object_obj->get_title() . '</em>' ) . '</p>';
			$dialog['detail']['buttons'][0]['action'] = 'location.reload();';
			$dialog['detail']['buttons'][]            = array(
				'action'  => 'location.href="' . esc_url( $immo_object_obj->get_link() ) . '";',
				'variant' => 'secondary',
				'text'    => __( 'Show them', 'connector-for-propstack' ),
			);
		}

		// clear the cache of files to import as we are completed the import.
		delete_transient( 'propstack_object_files_to_import' );

		// add a log entry if debug is enabled.
		if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
			Log::get_instance()->add( __( 'Import of files has been run', 'connector-for-propstack' ), 'info', 'import' );
		}

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
		$progress ? $progress->finish() : '';
		update_option( CONNECTOR_FOR_PROPSTACK_FILES_IMPORT_RUNNING, 0 );
	}

	/**
	 * Run import of object files by request.
	 *
	 * @return void
	 */
	public function import_by_request(): void {
		// check nonce.
		check_admin_referer( 'import-propstack-object-files', 'nonce' );

		// run the import.
		$this->import();

		// show hint.
		$transient_obj = Transients::get_instance()->add();
		$transient_obj->set_name( 'propstack_object_import_files_run' );
		$transient_obj->set_message( '<strong>' . __( 'The import of files from your Propstack account has been run.', 'connector-for-propstack' ) . '</strong>' );
		$transient_obj->set_type( 'success' );
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
		return absint( get_option( 'propstack_connector_files_imported', 0 ) ) > 0;
	}

	/**
	 * Update the file counter.
	 *
	 * @return void
	 */
	public function update_file_counter(): void {
		update_option( 'propstack_connector_files_imported', $this->get_files()->found_posts );
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
		$post_id = absint( filter_input( INPUT_POST, 'post', FILTER_SANITIZE_NUMBER_INT ) );

		// bail if no post-ID is given.
		if ( 0 === $post_id ) {
			return $query;
		}

		// restrict to the ID.
		$query['p'] = $post_id;

		// return the resulting query.
		return $query;
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

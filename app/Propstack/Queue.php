<?php
/**
 * File for handling the queue to import files from Propstack.
 *
 * Hints:
 * We assume that each file is only assigned to one object.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Fields\Number;
use easySettingsForWordPress\Fields\Select;
use easySettingsForWordPress\Page;
use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Intervals;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Plugin\ProcessHandler;
use ConnectorForPropstack\Plugin\Schedules;
use ConnectorForPropstack\Plugin\Settings;
use WP_Post;
use WP_Query;

/**
 * Object to handle the queue to import files from Propstack.
 */
class Queue {
	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Queue
	 */
	private static ?Queue $instance = null;

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
	public static function get_instance(): Queue {
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
		add_action( 'init', array( $this, 'add_settings' ), 20 );

		// use our own hooks.
		add_action( 'cfprop_import_object', array( $this, 'add_files_during_import' ), 10, 2 );
		add_action( 'cfprop_file_imported', array( $this, 'remove_file' ), 10, 2 );

		// use action hooks.
		add_action( 'admin_action_propstack_connector_queue_clear', array( $this, 'clear_by_request' ) );
		add_action( 'admin_action_propstack_connector_queue_process', array( $this, 'process_by_request' ) );
		add_action( 'wp_ajax_propstack_connector_queue_process', array( $this, 'process_via_ajax' ) );
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

		// add the queue tab.
		$queue_tab = $settings_page->add_tab( 'propstack_connector_queue', 50 );
		$queue_tab->set_title( __( 'Queue', 'connector-for-propstack' ) );

		// add a section.
		$queue_section = $queue_tab->add_section( 'propstack_connector_queue', 10 );
		$queue_section->set_title( __( 'Queue', 'connector-for-propstack' ) );
		$queue_section->set_callback( array( $this, 'show_queue' ) );

		// add a section.
		$queue_settings_section = $queue_tab->add_section( 'propstack_connector_queue_settings', 20 );
		$queue_settings_section->set_title( __( 'Settings', 'connector-for-propstack' ) );

		// add setting.
		$queue_setting = $settings_obj->add_setting( 'propstack_connector_queue' );
		$queue_setting->set_section( $queue_settings_section );
		$queue_setting->set_type( 'integer' );
		$queue_setting->set_default( 1 );
		$field = new Checkbox( $settings_obj );
		$field->set_title( __( 'Enable queue', 'connector-for-propstack' ) );
		$field->set_description( __( 'The queue helps you to import the files for your objects in WordPress.', 'connector-for-propstack' ) );
		$queue_setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstackConnectorQueueScheduleInterval' );
		$setting->set_type( 'string' );
		$setting->set_default( 'propstack_connector_15minutely' );
		$setting->set_section( $queue_settings_section );
		$setting->set_save_callback( array( $this, 'save_interval' ) );
		$field = new Select( $settings_obj );
		$field->set_title( __( 'Set interval for queue', 'connector-for-propstack' ) );
		$field->set_description( __( 'Defines the time interval in which queue entries will be processed.', 'connector-for-propstack' ) );
		$field->set_options( Intervals::get_instance()->get_intervals_for_settings() );
		$field->set_sanitize_callback( array( Schedules::get_instance(), 'validate_interval' ) );
		$field->add_depend( $queue_setting, 1 );
		$setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_queue_limit' );
		$setting->set_type( 'integer' );
		$setting->set_default( 10 );
		$setting->set_section( $queue_settings_section );
		$field = new Number( $settings_obj );
		$field->set_title( __( 'Limit per queue run', 'connector-for-propstack' ) );
		$field->set_description( __( 'Defines the amount of entries each queue run will process. The higher the value, the more files are imported; however, this also increases the likelihood of a timeout, which in turn causes a delay in processing.', 'connector-for-propstack' ) );
		$field->add_depend( $queue_setting, 1 );
		$setting->set_field( $field );
	}

	/**
	 * Add files to the queue during import.
	 *
	 * @param array<string,mixed> $immo_object The object data to import.
	 * @param int                 $post_id The post-ID of the object.
	 *
	 * @return void
	 */
	public function add_files_during_import( array $immo_object, int $post_id ): void {
		// bail if queue is disabled.
		if ( 1 !== absint( get_option( 'propstack_connector_queue' ) ) ) {
			return;
		}

		// get the immo object title.
		$title = '';
		if ( ! empty( $immo_object['title']['value'] ) ) {
			$title = $immo_object['title']['value'];
		} elseif ( ! empty( $immo_object['title'] ) ) {
			$title = $immo_object['title'];
		}

		// add a log entry if debug is enabled.
		if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
			/* translators: %1$s: the given title. */
			Log::get_instance()->add( sprintf( __( 'Adding files for %1$s during import.', 'connector-for-propstack' ), '<em>' . $title . '</em>' ), 'info', 'import' );
		}

		// set the limit for requesting of queue entries to unlimited.
		add_filter(
			'cfprop_queue_query',
			static function ( array $query ): array {
				$query['posts_per_page'] = -1;
				return $query;
			}
		);

		// get all files in the queue.
		$queue = $this->get_queue();

		// get their IDs.
		$ids = array_map( static fn( $post ) => $post->ID, $queue );
		// get their titles.
		$titles = array_map( static fn( $post ) => $post->post_title, $queue );
		// use one array for both.
		$list_of_existing_files_in_queue = array_combine( $titles, $ids );

		// add the images of this object to the queue.
		foreach ( $immo_object['images'] as $file ) {
			// get its post-ID in the queue.
			$queue_post_id = 0;
			$state         = 0;
			if ( isset( $list_of_existing_files_in_queue[ $file['id'] ] ) ) {
				$queue_post_id = $list_of_existing_files_in_queue[ $file['id'] ];
				$state         = 1;
			}

			// bail if the given URL is already in the media library.
			$attachment_id = Files::get_instance()->is_file_in_media_library( absint( $file['id'] ) );
			if ( $attachment_id > 0 ) {
				// add a log entry if debug is enabled.
				if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
					/* translators: %1$s: the given URL. */
					Log::get_instance()->add( sprintf( __( 'Given file ID %1$s is already in the media library and will not be added to the queue.', 'connector-for-propstack' ), ' <em>' . $file['id'] . '</em>' ), 'info', 'import' );
				}

				// remove the queue entry.
				if ( isset( $list_of_existing_files_in_queue[ $file['id'] ] ) ) {
					wp_delete_post( $list_of_existing_files_in_queue[ $file['id'] ], true );
				}

				// do nothing more with this file.
				continue;
			}

			// add this entry to the queue or update it.
			$query         = array(
				'ID'           => $queue_post_id,
				'post_type'    => PostTypes\Queue::get_instance()->get_name(),
				'post_status'  => 'publish',
				'post_title'   => $file['id'],
				'post_author'  => Helper::get_author_during_object_creation(),
				'post_parent'  => $post_id, // the ID we want this file to be assigned to.
				'post_content' => '',
			);
			$queue_post_id = wp_insert_post( $query );

			// bail on any error.
			if ( is_wp_error( $queue_post_id ) ) { // @phpstan-ignore function.impossibleType
				// add a log entry.
				Log::get_instance()->add( __( 'File could not be added to queue. Following error occurred:', 'connector-for-propstack' ) . ' <code>' . Helper::get_json( $queue_post_id ) . '</code>', 'error', 'import' );

				// do nothing more with this file.
				continue;
			}

			// add the fields of this file on the queue entry.
			foreach ( PostTypes\Queue::get_instance()->get_fields() as $category ) {
				foreach ( $category['fields'] as $field_name => $field ) {
					// bail if field is missing.
					if ( ! isset( $file[ $field['api'] ] ) ) {
						continue;
					}

					// add the field.
					update_post_meta( $queue_post_id, $field_name, $file[ $field['api'] ] );
				}
			}

			// save the structure we get from the API.
			update_post_meta( $queue_post_id, 'api_response', $file );

			// mark the object as changed.
			update_post_meta( $queue_post_id, 'changed', time() );

			// add a log entry if debug is enabled.
			if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
				if ( 0 === $state ) {
					/* translators: %1$s: the given URL. */
					Log::get_instance()->add( sprintf( __( 'Given file ID %1$s has been added in the queue.', 'connector-for-propstack' ), ' <em>' . $file['id'] . '</em>' ), 'info', 'import' );
				} else {
					/* translators: %1$s: the given URL. */
					Log::get_instance()->add( sprintf( __( 'Given file ID %1$s has been updated in the queue.', 'connector-for-propstack' ), ' <em>' . $file['id'] . '</em>' ), 'info', 'import' );
				}
			}
		}
	}

	/**
	 * Return the list of entries in the queue as a list.
	 *
	 * @return array<int,WP_Post>
	 */
	public function get_queue(): array {
		// get entries to process.
		$query = array(
			'post_type'      => PostTypes\Queue::get_instance()->get_name(),
			'post_status'    => 'publish',
			'posts_per_page' => absint( get_option( 'propstack_connector_queue_limit' ) ),
			'meta_query'     => array(
				array(
					'key'     => 'failed',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		/**
		 * Filter the query to get the next entries for the processing of the queue.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 */
		$query = apply_filters( 'cfprop_queue_query', $query );

		// request them.
		$result = new WP_Query( $query );

		// return an empty list of nothing could be found.
		if ( 0 === $result->found_posts ) {
			return array();
		}

		// collect the list.
		$list = array();
		foreach ( $result->get_posts() as $post ) {
			if ( ! $post instanceof WP_Post ) { // @phpstan-ignore instanceof.alwaysTrue
				continue;
			}
			$list[] = $post;
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Process the queue.
	 *
	 * Load X entries and import their files.
	 *
	 * The number for X can be changed but is set to 10 per process run.
	 *
	 * @return void
	 */
	public function process(): void {
		// bail if deactivation is in progress.
		if ( defined( 'CONNECTOR_FOR_PROPSTACK_DEACTIVATION_RUNNING' ) ) {
			return;
		}

		// get the actual but limited queue.
		$queue = $this->get_queue();

		/**
		 * Run additional tasks before the queue is processed.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param int $count The max count.
		 */
		do_action( 'cfprop_queue_before_processing', count( $queue ) );

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
		$process_handler->set_max_count( count( $queue ) );
		$process_handler->set_status( __( 'Processing is starting', 'connector-for-propstack' ) );
		$process_handler->set_running( time() );

		// get the object for files.
		$files_obj = Files::get_instance();

		// get the immo objects handler.
		$immo_objects = ImmoObjects::get_instance();

		// show cli hint.
		$progress = Helper::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Process queue to import files', count( $queue ) ) : false;

		// import the given files.
		foreach ( $queue as $post ) {
			// get the type by checking if "big_url" is given.
			$url = get_post_meta( $post->ID, get_option( 'propstack_connector_image_size', 'big_url' ), true );

			// get the file name.
			$name = get_post_meta( $post->ID, 'name', true );
			if ( empty( $name ) ) {
				$name = basename( $url );
			}

			// get the immo object.
			$immo_object = $immo_objects->get_object( $post->post_parent );

			// show status.
			/* translators: %1$s: the file name. */
			$process_handler->set_status( sprintf( __( 'Processing file %1$s for %2$s', 'connector-for-propstack' ), '<em>' . $name . '</em>', '<em>' . $immo_object->get_title() . '</em>' ) );

			// save it.
			$attachment_id = $files_obj->import_file( $post->post_parent, absint( get_post_meta( $post->ID, 'id', true ) ), $url, $name, (array) get_post_meta( $post->ID, 'api_response', true ) );

			// add this file to the list of files for the object.
			if ( $attachment_id > 0 ) {
				// update the corresponding list of files.
				$immo_object->add_to_image_list( $attachment_id );

				// get the API response for this file.
				$file = get_post_meta( $post->ID, 'api_response', true );

				/**
				 * Run additional tasks after a file has been assigned to an object.
				 *
				 * @since 1.0.0 Available since 1.0.0.
				 * @param int $attachment The attachment ID.
				 * @param array<string,mixed> $file The file data from Propstack API.
				 * @param ImmoObject $immo_object_obj The immo object.
				 */
				do_action( 'cfprop_file_is_assigned', $attachment_id, $file, $immo_object );

				// delete it from the queue.
				wp_delete_post( $post->ID, true );
			} else {
				// mark as failure.
				update_post_meta( $post->ID, 'failed', time() );
			}

			/**
			 * Run additional tasks after the queue has processed one entry.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 * @param int $count The max count.
			 * @param int $attachment The attachment ID.
			 */
			do_action( 'cfprop_queue_processing', 1, $attachment_id );

			// show progress.
			$process_handler->set_count( $process_handler->get_count() + 1 );
			$progress ? $progress->tick() : '';
		}

		/**
		 * Run additional tasks after the queue has been processed.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 */
		do_action( 'cfprop_queue_after_processing' );

		// create a dialog for the OK message.
		$dialog = array(
			'detail' => array(
				'className' => 'cfprop-dialog',
				'title'     => __( 'Queue has been processed', 'connector-for-propstack' ),
				'texts'     => array(
					'<p><strong>' . __( 'The images have been imported.', 'connector-for-propstack' ) . '</strong></p>',
					'<p>' . __( 'You can see them now on their objects.', 'connector-for-propstack' ) . '</p>',
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

		// finish progress.
		$process_handler->set_running( 0 );
		$process_handler->set_message( $dialog );
		$progress ? $progress->finish() : '';
	}

	/**
	 * Save the interval.
	 *
	 * @param mixed $value The given interval value.
	 *
	 * @return string
	 */
	public function save_interval( mixed $value ): string {
		// change value to string.
		if ( ! is_string( $value ) ) {
			$value = '';
		}

		// if import is enabled, set or update the schedule.
		if ( ! empty( $value ) && 1 === absint( get_option( 'propstack_connector_queue' ) ) ) {
			// get import-schedule-object.
			$queue_schedule = new \ConnectorForPropstack\Propstack\Schedules\Queue();

			// set the new interval.
			$queue_schedule->set_interval( $value );

			// reset schedule.
			$queue_schedule->reset();
		}

		// return the new value to save it via WP.
		return $value;
	}

	/**
	 * Clear the queue.
	 *
	 * @return void
	 */
	public function clear(): void {
		// return the meta-query to get all entries in the queue.
		add_filter(
			'cfprop_queue_query',
			function ( $query ) {
				unset( $query['meta_query'] );
				$query['posts_per_page'] = -1;
				return $query;
			}
		);

		// get the queue list.
		$queue = $this->get_queue();

		// show cli hint.
		$progress = Helper::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Delete entries in the queue', count( $queue ) ) : false;

		// delete them.
		foreach ( $queue as $post ) {
			// delete the entry.
			wp_delete_post( $post->ID, true );

			// show progress.
			$progress ? $progress->tick() : '';
		}

		// finish the progress.
		$progress ? $progress->finish() : '';
	}

	/**
	 * Show the log table.
	 *
	 * @return void
	 */
	public function show_queue(): void {
		// bail if the user has not the capability for this.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// get the page from request.
		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// if WP_List_Table is not loaded automatically, we need to load it.
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}

		// get the table object.
		$queue = new Tables\Queue();
		$queue->prepare_items();

		// output.
		$queue->views();
		$queue->display();
	}

	/**
	 * Clear the queue by request.
	 *
	 * @return void
	 */
	public function clear_by_request(): void {
		// check nonce.
		check_admin_referer( 'cfprop-queue-clear', 'nonce' );

		// clear the queue.
		$this->clear();

		// show a message.
		$transient_obj = Transients::get_instance()->add();
		$transient_obj->set_name( 'propstack_connector_queue_cleared' );
		$transient_obj->set_message( '<strong>' . __( 'The queue has been cleared.', 'connector-for-propstack' ) . '</strong><br>' . __( 'To import files from Propstack, run the import again.', 'connector-for-propstack' ) );
		$transient_obj->set_type( 'success' );
		$transient_obj->save();

		// redirect back to the referring page.
		wp_safe_redirect( (string) wp_get_referer() );
	}

	/**
	 * Process the queue by request.
	 *
	 * @return void
	 */
	public function process_by_request(): void {
		// check nonce.
		check_admin_referer( 'cfprop-queue-process', 'nonce' );

		// clear the queue.
		$this->process();

		// show a message.
		$transient_obj = Transients::get_instance()->add();
		$transient_obj->set_name( 'propstack_connector_queue_processed' );
		$transient_obj->set_message( '<strong>' . __( 'The queue has been processed.', 'connector-for-propstack' ) . '</strong><br>' . __( 'Some files have been imported. Check your objects.', 'connector-for-propstack' ) );
		$transient_obj->set_type( 'success' );
		$transient_obj->save();

		// redirect back to the referring page.
		wp_safe_redirect( (string) wp_get_referer() );
	}

	/**
	 * Process the queue by request.
	 *
	 * @return void
	 */
	public function process_via_ajax(): void {
		// check nonce.
		check_admin_referer( 'propstack-queue-processing', 'nonce' );

		// bail if capability is missing.
		if( ! current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// clear the queue.
		$this->process();

		// return ok.
		wp_send_json_success();
	}

	/**
	 * Delete the given entry from the queue.
	 *
	 * @param int $attachment_id The used attachment ID.
	 * @param int $propstack_id The Propstack ID.
	 *
	 * @return void
	 */
	public function remove_file( int $attachment_id, int $propstack_id ): void {
		// get the file in the queue.
		$query  = array(
			'post_type'      => PostTypes\Queue::get_instance()->get_name(),
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'   => 'id',
					'value' => $propstack_id,
				),
			),
			'fields'         => 'ids',
		);
		$result = new WP_Query( $query );
		if ( $result->found_posts > 0 && is_int( $result->posts[0] ) ) {
			wp_delete_post( absint( $result->posts[0] ), true );
		}
	}
}

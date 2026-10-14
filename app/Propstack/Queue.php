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

use ConnectorForPropstack\Plugin\Db;
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
use Throwable;
use WP_Post;
use WP_Query;

/**
 * Object to handle the queue to import files from Propstack.
 */
class Queue {
	/**
	 * The map of Propstack file IDs to their queue post IDs.
	 *
	 * @var array<string,int>|null
	 */
	private ?array $queue_map = null;

	/**
	 * The amount of entries the last request of the queue found in total (without the limit).
	 *
	 * @var int
	 */
	private int $last_found_posts = 0;

	/**
	 * Marker whether the last call of process() could not run as another process imports files right now.
	 *
	 * @var bool
	 */
	private bool $locked = false;

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
		add_action( 'admin_action_cfprop_queue_clear', array( $this, 'clear_by_request' ) );
		add_action( 'admin_action_cfprop_queue_process', array( $this, 'process_by_request' ) );
		add_action( 'wp_ajax_cfprop_queue_process', array( $this, 'process_via_ajax' ) );
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
		if ( method_exists( $queue_settings_section, 'set_collapsed' ) ) { // @phpstan-ignore function.alreadyNarrowedType
			$queue_settings_section->set_collapsed( 1 !== absint( get_option( 'propstack_connector_queue' ) ) );
		}

		// add setting.
		$queue_setting = $settings_obj->add_setting( 'propstack_connector_queue' );
		$queue_setting->set_section( $queue_settings_section );
		$queue_setting->set_default( 1 );
		$field = new Checkbox( $settings_obj );
		$field->set_title( __( 'Enable queue', 'connector-for-propstack' ) );
		$field->set_description( __( 'The queue helps you to import the files for your objects in WordPress.', 'connector-for-propstack' ) );
		$queue_setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstackConnectorQueueScheduleInterval' );
		$setting->set_type( 'string' );
		$setting->set_default( 'cfprop_15minutely' );
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

		// get the immo object title, the API v1 delivers it as a field with label and value.
		$title = '';
		if ( isset( $immo_object['title'] ) && is_array( $immo_object['title'] ) ) {
			$title = $immo_object['title']['value'] ?? '';
		} elseif ( ! empty( $immo_object['title'] ) ) {
			$title = $immo_object['title'];
		}
		if ( ! is_scalar( $title ) ) {
			$title = '';
		}

		// add a log entry.
		/* translators: %1$s: the given title. */
		Log::get_instance()->add( sprintf( __( 'Adding files for %1$s during import.', 'connector-for-propstack' ), '<em>' . esc_html( (string) $title ) . '</em>' ), 'info', 'queue' );

		// bail if no images are given.
		if ( empty( $immo_object['images'] ) || ! is_array( $immo_object['images'] ) ) {
			return;
		}

		// get the map of file IDs already in the queue.
		$list_of_existing_files_in_queue = $this->get_queue_map();

		// add the images of this object to the queue.
		foreach ( $immo_object['images'] as $file ) {
			// bail if this is not a usable file entry.
			if ( ! is_array( $file ) || ! isset( $file['id'] ) || ! is_scalar( $file['id'] ) ) {
				continue;
			}

			// get the file ID as string, as it is used as array key.
			$file_id = (string) $file['id'];

			// get its post-ID in the queue.
			$queue_post_id = 0;
			$state         = 0;
			if ( isset( $list_of_existing_files_in_queue[ $file_id ] ) ) {
				$queue_post_id = $list_of_existing_files_in_queue[ $file_id ];
				$state         = 1;
			}

			// do not add files to the queue, which would not be imported anyway (e.g. private files).
			if ( $this->is_file_import_prevented( $file, Files::get_instance()->get_file_url( $file ) ) ) {
				// remove the queue entry.
				if ( $queue_post_id > 0 ) {
					wp_delete_post( $queue_post_id, true );
					unset( $this->queue_map[ $file_id ] );
				}

				// do nothing more with this file.
				continue;
			}

			// bail if the given URL is already in the media library.
			$attachment_id = Files::get_instance()->is_file_in_media_library( max( 0, (int) $file['id'] ) );
			if ( $attachment_id > 0 ) {
				// add a log entry.
				/* translators: %1$s: the given URL. */
				Log::get_instance()->add( sprintf( __( 'Given file ID %1$s is already in the media library and will not be added to the queue.', 'connector-for-propstack' ), ' <em>' . esc_html( $file_id ) . '</em>' ), 'info', 'queue' );

				// remove the queue entry.
				if ( isset( $list_of_existing_files_in_queue[ $file_id ] ) ) {
					wp_delete_post( $list_of_existing_files_in_queue[ $file_id ], true );
					unset( $this->queue_map[ $file_id ] );
				}

				// do nothing more with this file.
				continue;
			}

			// collect the fields of this file.
			$meta = array();
			foreach ( PostTypes\Queue::get_instance()->get_fields() as $category ) {
				foreach ( $category['fields'] as $field_name => $field ) {
					// bail if field is missing.
					if ( ! isset( $file[ $field['api'] ] ) ) {
						continue;
					}

					$meta[ $field_name ] = $file[ $field['api'] ];
				}
			}

			// save the structure we get from the API.
			$meta['api_response'] = $file;

			// mark the object as changed.
			$meta['changed'] = time();

			// add this entry to the queue or update it.
			$query         = array(
				'ID'           => $queue_post_id,
				'post_type'    => PostTypes\Queue::get_instance()->get_name(),
				'post_status'  => 'publish',
				'post_title'   => $file_id,
				'post_author'  => Helper::get_author_during_object_creation(),
				'post_parent'  => $post_id,
				'post_content' => '',
				'menu_order'   => 0,
				'meta_input'   => $meta,
			);
			$queue_post_id = wp_insert_post( $query, true );

			// bail on any error.
			if ( is_wp_error( $queue_post_id ) ) { // @phpstan-ignore function.impossibleType
				// add a log entry.
				Log::get_instance()->add( __( 'File could not be added to queue. Following error occurred:', 'connector-for-propstack' ) . ' <code>' . Helper::get_json( $queue_post_id ) . '</code>', 'error', 'queue' );

				// do nothing more with this file.
				continue;
			}

			// keep the map up to date.
			$this->queue_map[ $file_id ] = absint( $queue_post_id );

			// save the structure we get from the API.
			update_post_meta( $queue_post_id, 'api_response', $file );

			// mark the object as changed.
			update_post_meta( $queue_post_id, 'changed', time() );

			// reset any previous failures, as the entry has been (re-)added to the queue.
			delete_post_meta( $queue_post_id, 'failed' );
			delete_post_meta( $queue_post_id, 'failed_attempts' );

			// add a log entry.
			if ( 0 === $state ) {
				/* translators: %1$s: the given URL. */
				Log::get_instance()->add( sprintf( __( 'Given file ID %1$s has been added in the queue.', 'connector-for-propstack' ), ' <em>' . esc_html( $file_id ) . '</em>' ), 'info', 'queue' );
			} else {
				/* translators: %1$s: the given URL. */
				Log::get_instance()->add( sprintf( __( 'Given file ID %1$s has been updated in the queue.', 'connector-for-propstack' ), ' <em>' . esc_html( $file_id ) . '</em>' ), 'info', 'queue' );
			}
		}
	}

	/**
	 * Return the list of entries in the queue as a list.
	 *
	 * @return array<int,WP_Post>
	 */
	public function get_queue(): array {
		// get the limit for the queue.
		$limit = absint( get_option( 'propstack_connector_queue_limit' ) );

		// if the limit is 0, set it to 10.
		if ( 0 === $limit ) {
			$limit = 10;
		}

		// get entries to process: fresh entries first, failed entries (with fewer than max attempts) after them.
		// the "menu_order" contains the count of failed attempts for each entry.
		$query = array(
			'post_type'      => PostTypes\Queue::get_instance()->get_name(),
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup; admin/sync context.
				'relation' => 'OR',
				array(
					'key'     => 'failed_attempts',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'failed_attempts',
					'value'   => $this->get_max_attempts(),
					'compare' => '<',
					'type'    => 'NUMERIC',
				),
			),
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'ASC',
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

		// remember the total amount of entries.
		$this->last_found_posts = absint( $result->found_posts );

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
	 * Return the amount of entries the last call of get_queue() found in total (without its limit).
	 *
	 * @return int
	 */
	public function get_last_found_posts(): int {
		return $this->last_found_posts;
	}

	/**
	 * Process the queue.
	 *
	 * Load X entries and import their files.
	 *
	 * The number for X can be changed but is set to 10 per process run.
	 *
	 * @param string $process_id The process ID.
	 *
	 * @return void
	 */
	public function process( string $process_id ): void {
		// bail if deactivation is in progress.
		if ( defined( 'CFPROP_DEACTIVATION_RUNNING' ) ) {
			return;
		}

		// this run is not locked until now.
		$this->locked = false;

		// get the lock for the import of files, so no file is imported by two requests at the same time.
		$lock = Files::get_instance()->get_import_lock();
		if ( ! $lock->acquire() ) {
			// mark this run as locked.
			$this->locked = true;

			// add a log entry.
			Log::get_instance()->add( __( 'Another import of files is running. The queue will be processed during its next run.', 'connector-for-propstack' ), 'info', 'queue' );

			// update the process handler.
			$process_handler = ProcessHandler::get_instance();
			$process_handler->set_id( $process_id );
			$process_handler->set_running( 0 );
			$process_handler->set_message( $this->get_locked_dialog_config() );

			// do nothing more.
			return;
		}

		// process the entries and release the lock in any case.
		try {
			$this->process_entries( $process_id, $lock );
		} finally {
			$lock->release();
		}
	}

	/**
	 * Return whether the last call of process() did not process the queue as another process imports files right now.
	 *
	 * @return bool
	 */
	public function was_locked(): bool {
		return $this->locked;
	}

	/**
	 * Process the entries of the queue while this request holds the lock for the import of files.
	 *
	 * @param string $process_id The process ID.
	 * @param Lock   $lock       The lock for the import of files.
	 *
	 * @return void
	 */
	private function process_entries( string $process_id, Lock $lock ): void {
		// get the actual but limited queue.
		$queue = $this->get_queue();

		/**
		 * Run additional tasks before the queue is processed.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param int $count The max count.
		 */
		do_action( 'cfprop_queue_before_processing', count( $queue ) );

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

		// get the name of the post type for objects.
		$object_post_type = PostTypes\ImmoObject::get_instance()->get_name();

		// show cli hint.
		$progress = Helper::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Process queue to import files', count( $queue ) ) : false;

		// import the given files.
		foreach ( $queue as $post ) {
			// keep the lock alive.
			$lock->refresh();

			// no file has been imported for this entry yet.
			$attachment_id = 0;

			try {
				// get the URL in the chosen image size.
				$url = $this->get_file_url_of_entry( $post->ID );

				// get the file name.
				$name = get_post_meta( $post->ID, 'name', true );
				if ( empty( $name ) || ! is_string( $name ) ) {
					$name = Files::get_instance()->get_file_name_from_url( $url );
				}

				// get the API response for this file.
				$file = get_post_meta( $post->ID, 'api_response', true );
				if ( ! is_array( $file ) ) {
					$file = array();
				}

				// check whether the object of this entry does not exist anymore, e.g. as it has been removed by the cleanup of an import.
				$object_missing = $post->post_parent <= 0 || get_post_type( $post->post_parent ) !== $object_post_type;

				// remove entries whose object is missing or which would not be imported anyway (e.g. private files added by older versions).
				if ( $object_missing || $this->is_file_import_prevented( $file, $url ) ) {
					wp_delete_post( $post->ID, true );

					// add a log entry.
					if ( $object_missing ) {
						/* translators: %1$s: the file ID. */
						Log::get_instance()->add( sprintf( __( 'File ID %1$s has been removed from the queue as its object does not exist anymore.', 'connector-for-propstack' ), '<em>' . esc_html( $post->post_title ) . '</em>' ), 'info', 'queue' );
					}

					// show progress.
					$process_handler->set_count( $process_handler->get_count() + 1 );
					$progress ? $progress->tick() : '';

					// continue with the next entry.
					continue;
				}

				// get the immo object.
				$immo_object = $immo_objects->get_object( $post->post_parent );

				// count this attempt before the import: an entry which aborts the request (e.g. by the memory or time limit)
				// is moved behind the other entries and stops being processed after the max attempts.
				$failed_attempts = absint( get_post_meta( $post->ID, 'failed_attempts', true ) ) + 1;
				wp_update_post(
					array(
						'ID'         => $post->ID,
						'menu_order' => $failed_attempts,
						'meta_input' => array(
							'failed'          => time(),
							'failed_attempts' => $failed_attempts,
						),
					)
				);

				// show status.
				/* translators: %1$s: the file name. */
				$process_handler->set_status( sprintf( __( 'Processing file %1$s for %2$s', 'connector-for-propstack' ), '<em>' . esc_html( $name ) . '</em>', '<em>' . esc_html( $immo_object->get_title() ) . '</em>' ) );

				// save it.
				$attachment_id = $files_obj->import_file( $post->post_parent, absint( get_post_meta( $post->ID, 'id', true ) ), $url, $name, $file );

				// add this file to the list of files for the object.
				if ( $attachment_id > 0 ) {
					// update the corresponding list of files.
					$immo_object->add_to_image_list( $attachment_id );

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
					// add a log entry, the attempt has already been counted.
					/* translators: %1$s: the file ID, %2$d: the count of failed attempts, %3$d: the max attempts. */
					Log::get_instance()->add( sprintf( __( 'Import of file ID %1$s from the queue failed (attempt %2$d of %3$d).', 'connector-for-propstack' ), '<em>' . esc_html( $post->post_title ) . '</em>', $failed_attempts, $this->get_max_attempts() ) . ' ' . \ConnectorForPropstack\Propstack\KnowledgeCenter::get_instance()->get_link( 'missing_images' ), 'error', 'queue' );
				}
			} catch ( Throwable $e ) {
				// add a log entry, the attempt has already been counted.
				/* translators: %1$s: the file ID. */
				Log::get_instance()->add( sprintf( __( 'Import of file ID %1$s from the queue failed with an error:', 'connector-for-propstack' ), '<em>' . esc_html( $post->post_title ) . '</em>' ) . '<br>' . Helper::get_throwable_as_log_text( $e ), 'error', 'queue' );
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
	 * Return the dialog configuration if the queue could not be processed as another import of files is running.
	 *
	 * @return array<string,mixed>
	 */
	private function get_locked_dialog_config(): array {
		return array(
			'detail' => array(
				'className' => 'cfprop-dialog',
				'title'     => __( 'Queue has not been processed', 'connector-for-propstack' ),
				'texts'     => array(
					'<p><strong>' . __( 'Another import of files is running.', 'connector-for-propstack' ) . '</strong></p>',
					'<p>' . __( 'Please try again in a few minutes.', 'connector-for-propstack' ) . '</p>',
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
	}

	/**
	 * Delete all entries of the given object from the queue, e.g. if the object has been deleted.
	 *
	 * @param int $post_id The post-ID of the object.
	 *
	 * @return void
	 */
	public function delete_entries_of_object( int $post_id ): void {
		// bail if no object is given.
		if ( $post_id <= 0 ) {
			return;
		}

		// get the entries of this object.
		$entries = get_posts(
			array(
				'post_type'      => PostTypes\Queue::get_instance()->get_name(),
				'post_status'    => 'any',
				'post_parent'    => $post_id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		// delete them.
		foreach ( $entries as $entry_id ) {
			wp_delete_post( absint( $entry_id ), true );
		}

		// the map of entries is not up to date anymore.
		$this->queue_map = null;
	}

	/**
	 * Delete entries which reached the max attempts and failed for the last time a long time ago.
	 *
	 * These entries are not processed anymore. They are added again if their object delivers them
	 * during a later import, so they would only fill the database.
	 *
	 * @return int The amount of deleted entries.
	 */
	public function purge_failed_entries(): int {
		$days = 30;
		/**
		 * Filter the amount of days after which an entry of the queue which reached the max attempts is deleted.
		 *
		 * Use 0 to keep these entries.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param int $days The amount of days.
		 */
		$days = absint( apply_filters( 'cfprop_queue_failed_entries_lifetime', $days ) );

		// bail if these entries should be kept.
		if ( 0 === $days ) {
			return 0;
		}

		// get the entries.
		$entries = get_posts(
			array(
				'post_type'      => PostTypes\Queue::get_instance()->get_name(),
				'post_status'    => 'any',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup; sync context.
					'relation' => 'AND',
					array(
						'key'     => 'failed_attempts',
						'value'   => $this->get_max_attempts(),
						'compare' => '>=',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => 'failed',
						'value'   => time() - $days * DAY_IN_SECONDS,
						'compare' => '<',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		// delete them.
		foreach ( $entries as $entry_id ) {
			wp_delete_post( absint( $entry_id ), true );
		}

		// add a log entry.
		if ( ! empty( $entries ) ) {
			/* translators: %1$d will be replaced by the amount of entries, %2$d by the amount of days. */
			Log::get_instance()->add( sprintf( __( '%1$d entries of the queue which failed for more than %2$d days have been deleted.', 'connector-for-propstack' ), count( $entries ), $days ), 'info', 'queue' );

			// the map of entries is not up to date anymore.
			$this->queue_map = null;
		}

		// return the amount of deleted entries.
		return count( $entries );
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

		// bail if capability is missing.
		if ( ! current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() ) ) {
			// redirect back to the referring page.
			wp_safe_redirect( (string) wp_get_referer() );
			exit;
		}

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

		// bail if capability is missing.
		if ( ! current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// get the process ID from the request.
		$process_id = filter_input( INPUT_POST, 'process_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( is_null( $process_id ) ) {
			$process_id = '';
		}

		// clear the queue.
		$this->process( $process_id );

		// show a message.
		$transient_obj = Transients::get_instance()->add();
		$transient_obj->set_name( 'propstack_connector_queue_processed' );
		if ( $this->was_locked() ) {
			// the queue has not been processed as another process imports files right now.
			$transient_obj->set_message( '<strong>' . __( 'Another import of files is running. The queue has not been processed.', 'connector-for-propstack' ) . '</strong><br>' . __( 'Please try again in a few minutes.', 'connector-for-propstack' ) );
			$transient_obj->set_type( 'error' );
		} else {
			$transient_obj->set_message( '<strong>' . __( 'The queue has been processed.', 'connector-for-propstack' ) . '</strong><br>' . __( 'Some files have been imported. Check your objects.', 'connector-for-propstack' ) );
			$transient_obj->set_type( 'success' );
		}
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
		if ( ! current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// get the process ID from the request.
		$process_id = filter_input( INPUT_POST, 'process_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( is_null( $process_id ) ) {
			$process_id = '';
		}

		// clear the queue.
		$this->process( $process_id );

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
		// bail if this is a broker avatar, as its ID is the broker ID and not a file ID.
		if ( absint( get_post_meta( $attachment_id, 'cfprop_broker_avatar', true ) ) > 0 ) {
			return;
		}

		// get the file in the queue.
		$query  = array(
			'post_type'      => PostTypes\Queue::get_instance()->get_name(),
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup; admin/sync context.
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

	/**
	 * Return the max count of attempts to import a single queue entry.
	 *
	 * @return int
	 */
	public function get_max_attempts(): int {
		$max_attempts = 3;
		/**
		 * Filter the max count of attempts to import a single queue entry before it is skipped.
		 * Failed entries are reset if they are added to the queue again during an object import.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param int $max_attempts The max count of attempts.
		 */
		return max( 1, absint( apply_filters( 'cfprop_queue_max_attempts', $max_attempts ) ) );
	}

	/**
	 * Return the URL to import for the given entry of the queue.
	 *
	 * API v1 delivers the chosen image size as its own field, which is saved on the entry.
	 * API v2 delivers the sizes as a list, so we get the URL from the saved API response.
	 *
	 * @param int $queue_post_id The post-ID of the entry in the queue.
	 *
	 * @return string
	 */
	public function get_file_url_of_entry( int $queue_post_id ): string {
		// get the URL from the field of the chosen image size.
		$url = get_post_meta( $queue_post_id, (string) get_option( 'propstack_connector_image_size', 'big_url' ), true );
		if ( ! empty( $url ) && is_string( $url ) ) {
			return $url;
		}

		// get the URL from the saved API response of this file.
		$file = get_post_meta( $queue_post_id, 'api_response', true );

		// bail if no API response is saved.
		if ( ! is_array( $file ) ) {
			return '';
		}

		// return the URL in the chosen image size.
		return Files::get_instance()->get_file_url( $file );
	}

	/**
	 * Return whether the import of the given file would be prevented.
	 *
	 * Uses the same filter as the file import itself (e.g. for private files).
	 *
	 * @param array<string,mixed> $file The file data from Propstack API.
	 * @param string              $url  The URL to use for import.
	 *
	 * @return bool
	 */
	private function is_file_import_prevented( array $file, string $url ): bool {
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
		return (bool) apply_filters( 'cfprop_prevent_file_import', $false, $file, absint( $file['id'] ?? 0 ), $url, ! empty( $file['name'] ) && is_scalar( $file['name'] ) ? (string) $file['name'] : Files::get_instance()->get_file_name_from_url( $url ) );
	}

	/**
	 * Return the map of Propstack file IDs to their queue post IDs.
	 *
	 * Loaded once per request and kept up to date by this object itself, as reloading it
	 * for every object would grow with the amount of entries already in the queue.
	 *
	 * @return array<string,int>
	 */
	private function get_queue_map(): array {
		// return the map if it has already been built.
		if ( is_array( $this->queue_map ) ) {
			return $this->queue_map;
		}

		global $wpdb;

		// load only the two columns we need.
		$results = Db::get_instance()->get_results(
			$wpdb->prepare(
				'SELECT ID, post_title FROM ' . $wpdb->posts . ' WHERE post_type = %s AND post_status = %s',
				PostTypes\Queue::get_instance()->get_name(),
				'publish'
			)
		);

		// build the map.
		$this->queue_map = array();
		foreach ( $results as $result ) {
			$this->queue_map[ (string) $result['post_title'] ] = absint( $result['ID'] );
		}

		return $this->queue_map;
	}
}

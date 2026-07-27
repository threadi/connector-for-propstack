<?php
/**
 * File for handling Propstack immo objects.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Setup;
use easySettingsForWordPress\Fields\Button;
use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Fields\MultiSelect;
use easySettingsForWordPress\Fields\Select;
use easySettingsForWordPress\Fields\TextInfo;
use easySettingsForWordPress\Fields\Value;
use easySettingsForWordPress\Page;
use easySettingsForWordPress\Section;
use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients;
use ConnectorForPropstack\Plugin\Cache;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Languages;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Plugin\ProcessHandler;
use ConnectorForPropstack\Plugin\Settings;
use ConnectorForPropstack\Plugin\Users;
use ConnectorForPropstack\Propstack\Fields\Main\ApiResponse;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use WP_Post;
use WP_Query;
use WP_Screen;
use WP_Term;

/**
 * Object to handle objects from Propstack.
 */
class ImmoObjects {

	/**
	 * Variable to hold the list of initialized objects.
	 *
	 * @var array<ImmoObject>
	 */
	private array $objects = array();

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?ImmoObjects
	 */
	private static ?ImmoObjects $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() { }

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): ImmoObjects {
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
		if ( ! defined( 'CFPROP_IMPORT_RUNNING' ) ) {
			define( 'CFPROP_IMPORT_RUNNING', 'propstack_connector_import_running' );
		}
		if ( ! defined( 'CFPROP_DELETE_RUNNING' ) ) {
			define( 'CFPROP_DELETE_RUNNING', 'propstack_connector_delete_running' );
		}

		// use hooks.
		add_action( 'init', array( $this, 'add_settings' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_js' ) );
		add_action( 'deleted_post_' . \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name(), array( $this, 'after_deletion_of_object' ) );

		// use table hooks.
		add_filter( 'manage_edit-' . \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() . '_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_' . \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() . '_posts_custom_column', array( $this, 'add_custom_column_contents' ), 10, 2 );
		add_filter( 'hidden_columns', array( $this, 'hide_columns' ), 10, 3 );
		add_action( 'pre_get_posts', array( $this, 'extend_search' ) );

		// use actions.
		add_action( 'wp_ajax_cfprop_import_objects', array( $this, 'import_by_ajax' ) );
		add_action( 'admin_action_cfprop_import_objects', array( $this, 'import_by_request' ) );
		add_action( 'wp_ajax_cfprop_delete_objects', array( $this, 'delete_by_ajax' ) );
		add_action( 'admin_action_cfprop_delete_objects', array( $this, 'delete_by_request' ) );
		add_action( 'wp_ajax_cfprop_get_import_dialog', array( $this, 'get_import_dialog_by_ajax' ) );

		// use our own hooks.
		add_filter( 'cfprop_prevent_import_of_object', array( $this, 'prevent_import_by_state' ), 10, 2 );
		add_filter( 'cfprop_prevent_import_of_object', array( $this, 'prevent_import_by_broker' ), 10, 2 );
		add_filter( 'cfprop_prevent_import_of_object', array( $this, 'prevent_import_by_marketing_type' ), 10, 2 );
		add_filter( 'cfprop_prevent_import_of_object', array( $this, 'prevent_import_by_object_type' ), 10, 2 );
		add_filter( 'cfprop_prevent_import_of_object', array( $this, 'prevent_import_by_property_type' ), 10, 2 );
		add_filter( 'cfprop_prevent_import_of_object', array( $this, 'prevent_import_by_missing_fields' ), 10, 2 );
		add_action( 'cfprop_import_object_after', array( $this, 'set_has_objects' ), 10, 0 );
		add_action( 'cfprop_files_for_object_imported_via_ajax', array( $this, 'assign_feature_image' ) );
		add_action( 'cfprop_queue_after_processing', array( $this, 'assign_feature_image_via_queue' ), 10, 0 );
		add_action( 'cfprop_import_object', array( $this, 'assign_feature_image_during_import' ), 10, 2 );
		add_action( 'cfprop_import_object_after', array( $this, 'cleanup_after_import' ) );
		add_action( 'cfprop_import_object_after', array( $this, 'set_main_object_type' ) );
		add_filter( 'cfprop_object_type_fields', array( $this, 'hide_object_type_fields' ) );
		add_filter( 'cfprop_object_import_response', array( $this, 'remove_document_from_response' ) );
		add_filter( 'cfprop_object_import_response', array( $this, 'save_response' ) );
		add_filter( 'cfprop_api_object_url', array( $this, 'add_marketing_type_to_import_url' ) );
		add_filter( 'cfprop_api_object_url', array( $this, 'add_object_type_to_import_url' ) );
		add_action( 'cfprop_import_content_not_change', array( $this, 'mark_as_updated' ), 10, 0 );
		add_action( 'cfprop_restriction_value_changed', array( $this, 'remove_changed_flag' ), 10, 0 );
		add_action( 'cfprop_object_field_metabox', array( $this, 'show_pro_hint_on_field' ), 10, 2 );
	}

	/**
	 * Return Position object of the given ID.
	 *
	 * @param int    $post_id The ID of the post-object.
	 * @param string $language_code The language-code to use for contents of the requested object (optional).
	 *
	 * @return ImmoObject
	 */
	public function get_object( int $post_id, string $language_code = '' ): ImmoObject {
		if ( empty( $this->objects[ $post_id . $language_code ] ) ) {
			$immo_object = new ImmoObject( $post_id );
			/**
			 * Filter the requested position object.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 *
			 * @param ImmoObject $immo_object The object of the object.
			 * @param string $language_code The requested language.
			 */
			$this->objects[ $post_id . $language_code ] = apply_filters( 'cfprop_get_immo_obj', $immo_object, $language_code );
			if ( ! empty( $language_code ) ) {
				$this->objects[ $post_id . $language_code ]->set_lang( $language_code );
			}
		}
		return $this->objects[ $post_id . $language_code ];
	}

	/**
	 * Return the "WP_Query" object for immo objects.
	 *
	 * @param array<string,mixed> $query_params The query parameters (optional).
	 *
	 * @return WP_Query
	 */
	public function get_objects_query( array $query_params = array() ): WP_Query {
		$default_query = array(
			'post_type'      => \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name(),
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'fields'         => 'ids',
		);
		$query         = wp_parse_args( $query_params, $default_query );
		return new WP_Query( $query );
	}

	/**
	 * Return all immo objects as objects.
	 *
	 * @param array<string,mixed> $query_params The query parameters (optional).
	 *
	 * @return array<ImmoObject>
	 */
	public function get_objects( array $query_params = array() ): array {
		// get the results.
		$results = $this->get_objects_query( $query_params );

		// bail on no results.
		if ( 0 === $results->found_posts ) {
			return array();
		}

		// prepare the list.
		$list = array();

		// convert the results to an array with PropstackImmoObject.
		foreach ( $results->posts as $post_id ) {
			// bail on a wrong object.
			if ( ! is_int( $post_id ) ) {
				continue;
			}
			$list[] = $this->get_object( absint( $post_id ) );
		}

		// return the results.
		return $list;
	}

	/**
	 * Return a single position by its object ID.
	 *
	 * @param string $object_id The object ID.
	 * @param string $language_code The language of the object.
	 *
	 * @return ImmoObject|false
	 */
	public function get_object_by_object_id( string $object_id, string $language_code ): false|ImmoObject {
		// create the query.
		$query = array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'object_id',
					'value'   => $object_id,
					'compare' => '=',
				),
				array(
					'key'     => 'language_code',
					'value'   => $language_code,
					'compare' => '=',
				),
			),
		);

		// get the objects.
		$objects = $this->get_objects( $query );
		if ( ! empty( $objects ) ) {
			return $objects[0];
		}
		return false;
	}

	/**
	 * Delete all objects.
	 *
	 * @param string $process_id The process ID.
	 *
	 * @return void
	 */
	public function delete_all( string $process_id ): void {
		// bail if import or deletion are still running.
		if ( Helper::is_process_running( CFPROP_IMPORT_RUNNING ) || Helper::is_process_running( CFPROP_DELETE_RUNNING ) ) {
			return;
		}

		// get the process handler and set the process ID.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_id( $process_id );

		// set initial value.
		$process_handler->set_count( 0 );
		$process_handler->set_max_count( 0 );
		$process_handler->set_status( __( 'Deletion of your objects is starting', 'connector-for-propstack' ) );
		$process_handler->set_running( time() );
		update_option( CFPROP_DELETE_RUNNING, time() );

		// add a log entry if debug is enabled.
		if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
			Log::get_instance()->add( __( 'Delete of all Propstack objects has been started.', 'connector-for-propstack' ), 'info', 'system' );
		}

		// get all objects.
		$objects = $this->get_objects( array( 'posts_per_page' => -1 ) );

		// get the object count.
		$object_count = count( $objects );

		// update process values.
		$process_handler->set_max_count( $object_count );
		$process_handler->set_status( __( 'Deletion of your objects is running', 'connector-for-propstack' ) );

		// get marker if images should be preserved.
		$preserve_images = absint( get_option( 'propstack_connector_preserve_files' ) );

		// show cli hint.
		$progress = Helper::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Deleting objects', $object_count ) : false;

		// loop through all objects and delete them.
		foreach ( $objects as $object ) {
			// update marker.
			/* translators: a title will replace %1$s. */
			$process_handler->set_status( sprintf( __( 'Deleting object %1$s', 'connector-for-propstack' ), '<em>' . $object->get_title() . '</em>' ) );

			// get the assigned images and delete them, if not disabled.
			if ( 0 === $preserve_images ) {
				foreach ( $object->get_images() as $attachment_id ) {
					$delete_result = wp_delete_post( $attachment_id, true );

					// add a log entry if debug is enabled.
					if ( ! $delete_result instanceof WP_Post ) {
						/* translators: a title will replace %1$s. */
						Log::get_instance()->add( sprintf( __( 'Object %1$s could not be deleted.', 'connector-for-propstack' ), ' <em>' . $object->get_title() . '</em>' ), 'error', 'system' );
					}
				}
			}

			// delete the object.
			wp_delete_post( $object->get_id(), true );

			// update the counter.
			$process_handler->set_count( $process_handler->get_count() + 1 );

			// show progress.
			$progress ? $progress->tick() : '';
		}

		// finish progress.
		$progress ? $progress->finish() : '';

		// update marker.
		$process_handler->set_status( __( 'Cleanup the database', 'connector-for-propstack' ) );

		// clean up the database.
		$this->remove_changed_flag();

		// add a log entry if debug is enabled.
		if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
			Log::get_instance()->add( __( 'Delete of all Propstack objects has been ended.', 'connector-for-propstack' ), 'info', 'system' );
		}

		// update the marker.
		$process_handler->set_message( $this->get_success_dialog_config() );
		$process_handler->set_running( 0 );
		update_option( CFPROP_DELETE_RUNNING, 0 );
	}

	/**
	 * Add the object settings.
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

		// add a tab on this page.
		$import_tab = $settings_page->add_tab( 'propstack_connector_import', 20 );
		$import_tab->set_title( __( 'Imports', 'connector-for-propstack' ) );
		$import_tab->set_hide_save( true );

		// add a tab for the objects.
		$import_objects_tab = $import_tab->add_tab( 'propstack_connector_objects_import', 10 );
		$import_objects_tab->set_title( __( 'Objects', 'connector-for-propstack' ) );
		$import_tab->set_default_tab( $import_objects_tab );

		// add a section.
		$import_section = $import_objects_tab->add_section( 'propstack_connector_objects_import', 10 );
		$import_section->set_title( __( 'Import objects', 'connector-for-propstack' ) );

		// add setting for Button.
		$setting = $settings_obj->add_setting( 'propstack_connector_import' );
		$setting->set_section( $import_section );
		$setting->prevent_export( true );
		if ( empty( get_option( 'propstack_connector_api_key' ) ) ) {
			$field = new TextInfo( $settings_obj );
			$field->set_title( __( 'Import objects', 'connector-for-propstack' ) );
			$field->set_description( __( 'Propstack API key is missing.', 'connector-for-propstack' ) );
		} elseif ( defined( 'CFPROP_IMPORT_RUNNING' ) && absint( get_option( CFPROP_IMPORT_RUNNING ) ) > 0 ) {
			$field = new TextInfo( $settings_obj );
			$field->set_title( __( 'Import objects', 'connector-for-propstack' ) );
			$field->set_description( __( 'Import of objects is still running. Please wait.', 'connector-for-propstack' ) );
		} else {
			$field = new Button( $settings_obj );
			$field->set_button_title( __( 'Import now', 'connector-for-propstack' ) );
			$field->set_title( __( 'Import objects', 'connector-for-propstack' ) );
			$field->set_button_url( $this->get_import_url() );
			$field->add_data( 'dialog', (string) wp_json_encode( $this->get_import_dialog() ) );
			$field->add_class( 'easy-dialog-for-wordpress' );
		}
		$setting->set_field( $field );

		// create the import URL.
		$delete_url = add_query_arg(
			array(
				'action' => 'cfprop_delete_objects',
				'nonce'  => wp_create_nonce( 'delete-propstack-objects' ),
			),
			get_admin_url() . 'admin.php'
		);

		// add setting for Button.
		$setting = $settings_obj->add_setting( 'propstack_connector_delete' );
		$setting->set_section( $import_section );
		$setting->prevent_export( true );
		if ( defined( 'CFPROP_DELETE_RUNNING' ) && absint( get_option( CFPROP_DELETE_RUNNING ) ) > 0 ) {
			$field = new TextInfo( $settings_obj );
			$field->set_title( __( 'Delete objects', 'connector-for-propstack' ) );
			$field->set_description( __( 'Deletion of objects is still running. Please wait.', 'connector-for-propstack' ) );
		} elseif ( ! $this->has_objects() ) {
			$field = new TextInfo( $settings_obj );
			$field->set_title( __( 'Delete objects', 'connector-for-propstack' ) );
			$field->set_description( __( 'No objects imported.', 'connector-for-propstack' ) );
		} else {
			$field = new Button( $settings_obj );
			$field->set_button_title( __( 'Delete now', 'connector-for-propstack' ) );
			$field->set_title( __( 'Delete objects', 'connector-for-propstack' ) );
			$field->set_button_url( $delete_url );
			$field->add_data(
				'dialog',
				(string) wp_json_encode(
					array(
						'className' => 'cfprop-dialog',
						'title'     => __( 'Delete your objects', 'connector-for-propstack' ),
						'texts'     => array(
							'<p><strong>' . __( 'Click on the button below to delete all objects in your WordPress website.', 'connector-for-propstack' ) . '</strong></p>',
							'<p>' . __( 'The objects will stay in your Propstack account.', 'connector-for-propstack' ) . '</p>',
						),
						'buttons'   => array(
							array(
								'action'  => 'propstack_connector_object_delete("' . esc_attr( ProcessHandler::get_instance()->create_id() ) . '");',
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
		$import_options_section = $import_objects_tab->add_section( 'propstack_connector_import_options', 30 );
		$import_options_section->set_title( __( 'Options', 'connector-for-propstack' ) );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_object_author' );
		$setting->set_section( $import_options_section );
		$setting->set_type( 'integer' );
		$setting->set_default( Users::get_instance()->get_first_administrator_user() );
		$field = new Select( $settings_obj );
		$field->set_title( __( 'Assign new objects to this user', 'connector-for-propstack' ) );
		$field->set_description( __( 'This is only a fallback if the actual user is not available (e.g., via WP CLI import or synchronisation). New files are normally assigned to the user who adds them.', 'connector-for-propstack' ) );
		$field->set_options( Users::get_instance()->get_users_for_settings() );
		$setting->set_field( $field );

		// add setting.
		$import_schedule_setting = $settings_obj->add_setting( 'propstack_connector_import_schedule' );
		$import_schedule_setting->set_section( $import_options_section );
		$import_schedule_setting->set_type( 'integer' );
		$import_schedule_setting->set_default( 1 );
		$field = new Checkbox( $settings_obj );
		$field->set_title( __( 'Enable automatic import', 'connector-for-propstack' ) );
		/* translators: %1$s: Connector for Propstack Pro URL */
		$field->set_description( '<span class="cfprop-pro-hint">' . sprintf( __( 'Use more options for automatic imports with <a href="%1$s" target="_blank">Connector for Propstack Pro</a>.', 'connector-for-propstack' ), Helper::get_pro_url() ) . '</span>' );
		$field->set_readonly( true );
		$import_schedule_setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstackConnectorObjectsScheduleInterval' );
		$setting->set_type( 'string' );
		$setting->set_default( 'propstack_connector_daily' );
		$setting->set_section( $import_options_section );
		$field = new Value( $settings_obj );
		$field->set_title( __( 'Interval for automatic import', 'connector-for-propstack' ) );
		$field->set_value( __( 'Daily', 'connector-for-propstack' ) );
		$setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_preserve_files' );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
		$setting->set_section( $import_options_section );
		$field = new Checkbox( $settings_obj );
		$field->set_title( __( 'Preserve files', 'connector-for-propstack' ) );
		$field->set_description( __( 'If objects are deleted, do not delete their files. They will stay in your media library, and you have to clean them manually.', 'connector-for-propstack' ) );
		$setting->set_field( $field );

		// add hidden setting for each language.
		foreach ( Languages::get_instance()->get_languages() as $language_code => $name ) {
			$setting = $settings_obj->add_setting( 'cfprop_md5_' . $language_code );
			$setting->prevent_export( true );
			$setting->set_type( 'string' );
			$setting->set_default( '' );
		}

		// add a tab on this page.
		$objects_tab = $settings_page->add_tab( 'propstack_connector_objects', 45 );
		$objects_tab->set_title( __( 'Objects', 'connector-for-propstack' ) );
		$objects_tab->set_hide_save( true );

		// add a sub tab for restrictions.
		$restrictions_tab = $objects_tab->add_tab( 'propstack_connector_import_restrictions', 10 );
		$restrictions_tab->set_title( __( 'Restrictions', 'connector-for-propstack' ) );
		$objects_tab->set_default_tab( $restrictions_tab );

		// add a section.
		$import_restrictions_section = $restrictions_tab->add_section( 'propstack_connector_import_restrictions', 30 );
		$import_restrictions_section->set_title( __( 'Restrictions', 'connector-for-propstack' ) );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_import_states' );
		$setting->set_type( 'array' );
		$setting->set_default( array() );
		$setting->set_section( $import_restrictions_section );
		$field = new Value( $settings_obj );
		$field->set_title( __( 'States to import', 'connector-for-propstack' ) );
		/* translators: %1$s: Connector for Propstack Pro URL */
		$field->set_description( '<br><span class="cfprop-pro-hint">' . sprintf( __( 'Use more states with <a href="%1$s" target="_blank">Connector for Propstack Pro</a>.', 'connector-for-propstack' ), Helper::get_pro_url() ) . '</span>' );
		$field->set_value( __( 'Vermarktung', 'connector-for-propstack' ) );
		$setting->set_field( $field );

		// get the available brokers.
		$broker = Cache::get( Taxonomies\Broker::get_instance()->get_name() . '_non_empty_terms_for_settings' );
		if ( empty( $broker ) ) {
			$broker_terms = get_terms(
				array(
					'taxonomy'   => Taxonomies\Broker::get_instance()->get_name(),
					'hide_empty' => false,
				)
			);
			$broker       = array();
			if ( is_array( $broker_terms ) ) {
				foreach ( $broker_terms as $term ) {
					$broker[ get_term_meta( $term->term_id, 'api', true ) ] = $term->name;
				}
				Cache::set( Taxonomies\Broker::get_instance()->get_name() . '_non_empty_terms_for_settings', $broker );
			}
		}

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_import_broker' );
		$setting->set_type( 'array' );
		$setting->set_default( array() );
		$setting->set_section( $import_restrictions_section );
		$setting->set_save_callback( array( $this, 'check_for_changed_restriction_value' ) );
		$field = new MultiSelect( $settings_obj );
		$field->set_title( __( 'Broker to import', 'connector-for-propstack' ) );
		$field->set_description( __( 'Only objects with the selected broker will be imported. All other will be ignored. Selecting none will import objects for each broker.', 'connector-for-propstack' ) );
		$field->set_options( $broker );
		$setting->set_field( $field );

		// get the available marketing types.
		$marketing_type = Cache::get( Taxonomies\MarketingType::get_instance()->get_name() . '_non_empty_terms_for_settings' );
		if ( empty( $marketing_type ) ) {
			$state_terms    = get_terms(
				array(
					'taxonomy'   => Taxonomies\MarketingType::get_instance()->get_name(),
					'hide_empty' => false,
				)
			);
			$marketing_type = array();
			if ( is_array( $state_terms ) ) {
				foreach ( $state_terms as $term ) {
					$marketing_type[ get_term_meta( $term->term_id, 'api', true ) ] = $term->name;
				}
				Cache::set( Taxonomies\MarketingType::get_instance()->get_name() . '_non_empty_terms_for_settings', $marketing_type );
			}
		}

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_import_marketing_type' );
		$setting->set_type( 'array' );
		$setting->set_default( array() );
		$setting->set_section( $import_restrictions_section );
		$setting->set_save_callback( array( $this, 'check_for_changed_restriction_value' ) );
		$field = new MultiSelect( $settings_obj );
		$field->set_title( __( 'Marketing types to import', 'connector-for-propstack' ) );
		$field->set_description( __( 'Only objects with the selected marketing types will be imported. All other will be ignored. Selecting none will import objects for each marketing type.', 'connector-for-propstack' ) );
		$field->set_options( $marketing_type );
		$setting->set_field( $field );

		// get the available object types.
		$object_types = Cache::get( Taxonomies\ObjectType::get_instance()->get_name() . '_non_empty_terms_for_settings' );
		if ( empty( $object_types ) || ! Setup::get_instance()->is_completed() ) {
			$state_terms  = get_terms(
				array(
					'taxonomy'   => Taxonomies\ObjectType::get_instance()->get_name(),
					'hide_empty' => false,
				)
			);
			$object_types = array();
			if ( is_array( $state_terms ) ) {
				foreach ( $state_terms as $term ) {
					$object_types[ get_term_meta( $term->term_id, 'api', true ) ] = $term->name;
				}
				Cache::set( Taxonomies\ObjectType::get_instance()->get_name() . '_non_empty_terms_for_settings', $object_types );
			}
		}

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_import_object_type' );
		$setting->set_type( 'array' );
		$setting->set_default( array( 'APARTMENT' ) );
		$setting->set_section( $import_restrictions_section );
		$setting->set_show_in_rest(
			array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
			)
		);
		$setting->set_save_callback( array( $this, 'check_for_changed_restriction_value' ) );
		$field = new MultiSelect( $settings_obj );
		$field->set_title( __( 'Object types to import', 'connector-for-propstack' ) );
		/* translators: %1$s: Connector for Propstack Pro URL */
		$field->set_description( __( 'Only objects with the selected object types will be imported. All other will be ignored. Selecting none will import objects for each object type.', 'connector-for-propstack' ) . '<br><br><span class="cfprop-pro-hint">' . __( 'Use more object types with <a href="%1$s" target="_blank">Connector for Propstack Pro</a>.', 'connector-for-propstack' ) . '</span>' );
		$field->set_options( $object_types );
		$setting->set_field( $field );

		// get the available property types.
		$property_types = Cache::get( Taxonomies\PropertyType::get_instance()->get_name() . '_non_empty_terms_for_settings' );
		if ( empty( $property_types ) ) {
			$property_terms = get_terms(
				array(
					'taxonomy'   => Taxonomies\PropertyType::get_instance()->get_name(),
					'hide_empty' => false,
				)
			);
			$property_types = array();
			if ( is_array( $property_terms ) ) {
				foreach ( $property_terms as $term ) {
					$property_types[ get_term_meta( $term->term_id, 'api', true ) ] = $term->name;
				}
			}
			Cache::set( Taxonomies\PropertyType::get_instance()->get_name() . '_non_empty_terms_for_settings', $property_types );
		}

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_import_property_type' );
		$setting->set_type( 'array' );
		$setting->set_default( array() );
		$setting->set_section( $import_restrictions_section );
		$setting->set_save_callback( array( $this, 'check_for_changed_restriction_value' ) );
		$field = new MultiSelect( $settings_obj );
		$field->set_title( __( 'Property types to import', 'connector-for-propstack' ) );
		$field->set_description( __( 'Only objects with the property types will be imported. All other will be ignored. Selecting none will import objects for each property type.', 'connector-for-propstack' ) );
		$field->set_options( $property_types );
		$setting->set_field( $field );

		// get the hidden section to add some hidden settings we could clean up during uninstallation.
		$hidden_section = Settings::get_instance()->get_hidden_section();

		// bail if the hidden section could not be loaded.
		if ( ! $hidden_section instanceof Section ) {
			return;
		}

		// add setting.
		$setting = $settings_obj->add_setting( CFPROP_DELETE_RUNNING );
		$setting->set_section( $hidden_section );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
		$setting->prevent_export( true );

		// add setting.
		$setting = $settings_obj->add_setting( 'cfprop_has_objects' );
		$setting->set_section( $hidden_section );
		$setting->set_type( 'integer' );
		$setting->set_default( 0 );
		$setting->prevent_export( true );

		// add setting.
		$setting = $settings_obj->add_setting( 'cfprop_last_api_response' );
		$setting->set_section( $hidden_section );
		$setting->set_type( 'array' );
		$setting->set_default( array() );
		$setting->prevent_export( true );
	}

	/**
	 * Run import of objects by request.
	 *
	 * @return void
	 */
	public function import_by_request(): void {
		// check nonce.
		check_admin_referer( 'import-propstack-objects', 'nonce' );

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
		$this->import( $process_id );

		// show hint.
		$transient_obj = Transients::get_instance()->add();
		$transient_obj->set_name( 'propstack_object_import_run' );
		$transient_obj->set_message( '<strong>' . __( 'The import of objects from your Propstack account has been run.', 'connector-for-propstack' ) . '</strong>' );
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
	public function import_by_ajax(): void {
		// check nonce.
		check_ajax_referer( 'import-propstack-objects', 'nonce' );

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
		$this->import( $process_id );

		// send ok.
		wp_send_json_success();
	}

	/**
	 * Run deletion of objects by request.
	 *
	 * @return void
	 */
	public function delete_by_request(): void {
		// check nonce.
		check_admin_referer( 'delete-propstack-objects', 'nonce' );

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
		$transient_obj->set_name( 'propstack_object_deletion_run' );
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
		check_ajax_referer( 'delete-propstack-objects', 'nonce' );

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
	 * Add the import scripts.
	 *
	 * @return void
	 */
	public function add_js(): void {
		wp_enqueue_script(
			'cfprop-imports',
			Helper::get_plugin_url() . 'admin/objects.js',
			array(),
			Helper::get_file_version( trailingslashit( Helper::get_plugin_path() ) . 'admin/objects.js' ),
			true
		);

		// add php-vars to our js-script.
		wp_localize_script(
			'cfprop-imports',
			'propstackConnectorImportJsVars',
			array(
				'ajax_url'                     => admin_url( 'admin-ajax.php' ),
				'start_nonce'                  => wp_create_nonce( 'import-propstack-objects' ),
				'info_nonce'                   => wp_create_nonce( 'import-propstack-objects-info' ),
				'start_files_nonce'            => wp_create_nonce( 'import-propstack-object-files' ),
				'delete_start_nonce'           => wp_create_nonce( 'delete-propstack-objects' ),
				'delete_files_start_nonce'     => wp_create_nonce( 'delete-propstack-object-files' ),
				'process_info_nonce'           => wp_create_nonce( 'get-propstack-process-info' ),
				'start_queue_processing_nonce' => wp_create_nonce( 'propstack-queue-processing' ),
				'title'                        => __( 'Your objects are being imported', 'connector-for-propstack' ),
				'delete_title'                 => __( 'Deletion of objects is running', 'connector-for-propstack' ),
				'delete_files_title'           => __( 'Deletion of files is running', 'connector-for-propstack' ),
				'files_title'                  => __( 'Import of files is running', 'connector-for-propstack' ),
				'queue_title'                  => __( 'Processing the queue to import images', 'connector-for-propstack' ),
			)
		);

		// add php-vars to our js-script for possible import-errors.
		wp_localize_script(
			'cfprop-imports',
			'propstackConnectorJsErrors',
			array(
				'Request Timeout'  => __( '<u>Request Timeout</u> - The import apparently took too long to be completed.', 'connector-for-propstack' ),
				'Gateway Time-out' => __( '<u>Gateway Timeout</u> - The import apparently took too long to be completed.', 'connector-for-propstack' ),
			)
		);
	}

	/**
	 * Return a success dialog configuration.
	 *
	 * @return array<string,mixed>
	 */
	private function get_success_dialog_config(): array {
		return array(
			'detail' => array(
				'className' => 'cfprop-dialog',
				'title'     => __( 'Deletion has been run', 'connector-for-propstack' ),
				'texts'     => array(
					'<p><strong>' . __( 'The objects have been deleted.', 'connector-for-propstack' ) . '</strong></p>',
					'<p>' . __( 'They are still in your Propstack account. You can import them any time.', 'connector-for-propstack' ) . '</p>',
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
	 * Extend the columns in the table with all field data of immo objects.
	 *
	 * @param array<string,string> $columns The list of columns.
	 *
	 * @return array<string|int,string>
	 */
	public function add_columns( array $columns ): array {
		// add a column for the thumbnail after the cb-column.
		$cb_position = array_search( 'cb', array_keys( $columns ), true );
		if ( false !== $cb_position ) {
			$columns = Helper::add_array_in_array_on_position( $columns, ( $cb_position + 1 ), array( 'cfprop-thumbnail' => __( 'Thumbnail', 'connector-for-propstack' ) ) );
		}

		// add a column for the object state after the broker column.
		$broker_position = array_search( 'taxonomy-propstack_object_broker', array_keys( $columns ), true );
		if ( false !== $broker_position ) {
			$columns = Helper::add_array_in_array_on_position( $columns, ( $broker_position + 1 ), array( 'propstack_object_status' => __( 'Object state', 'connector-for-propstack' ) ) );
		}

		// add all fields.
		foreach ( Fields::get_instance()->get_fields_as_objects() as $field ) {
			$columns[ $field->get_name() ] = $field->get_label();
		}

		// return the resulting list of columns.
		return $columns;
	}

	/**
	 * Show content for the requested field column.
	 *
	 * @param string $column_name The column name.
	 * @param int    $post_id The requested post-ID.
	 *
	 * @return void
	 */
	public function add_custom_column_contents( string $column_name, int $post_id ): void {
		// show the object thumbnail.
		if ( 'cfprop-thumbnail' === $column_name ) {
			$attachment_id = absint( get_post_thumbnail_id( $post_id ) );
			if ( $attachment_id > 0 ) {
				echo wp_get_attachment_image( $attachment_id, array( 80, 80 ) );
			} else {
				// get the API response for this object.
				$api_response = Fields::get_instance()->get_field_value( $post_id, new ApiResponse(), true, true );

				// if no images are in the response, show a hint.
				if ( empty( $api_response['images'] ) ) {
					// show hint to import images with a link to start it.
					echo '<span class="button">' . esc_html__( 'Got no images', 'connector-for-propstack' ) . '</span>';
				} else {
					// show hint to import images with a link to start it.
					echo '<a href="#" class="easy-dialog-for-wordpress button" data-dialog="' . esc_attr( Helper::get_json( \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_import_images_dialog( $post_id ) ) ) . '">' . esc_html__( 'Import images', 'connector-for-propstack' ) . '</a>';
				}
			}
			return;
		}

		// show the object status.
		if ( 'propstack_object_status' === $column_name ) {
			echo esc_html__( 'Vermarktung', 'connector-for-propstack' );
			return;
		}

		// check all files.
		foreach ( Fields::get_instance()->get_fields_as_objects() as $field ) {
			// bail if field does not match.
			if ( $column_name !== $field->get_name() ) {
				continue;
			}

			// get the value.
			$value = get_post_meta( $post_id, $field->get_name(), true );

			// bail if the value is not a string.
			if ( ! is_string( $value ) ) {
				continue;
			}

			// get the content.
			echo wp_kses_post( $value );
		}
	}

	/**
	 * Hide fields in our own cpt-table.
	 *
	 * @param array<int,string> $hidden List of columns to hide.
	 * @param WP_Screen         $screen Actual screen-object.
	 * @param bool              $use_defaults If defaults should be used.
	 *
	 * @return array<int,string>
	 */
	public function hide_columns( array $hidden, WP_Screen $screen, bool $use_defaults ): array {
		// bail if we do not want to use the defaults.
		if ( ! $use_defaults ) {
			return $hidden;
		}

		// bail if this is not out own cpt.
		if ( \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() !== $screen->post_type ) {
			return $hidden;
		}

		// add the configured taxonomies to the list to hide.
		foreach ( Fields::get_instance()->get_fields_as_objects() as $field ) {
			// bail if this field should be visible.
			if ( $field->show_in_table() ) {
				continue;
			}

			// hide this field in the table.
			$hidden[] = $field->get_name();
		}

		// return the resulting list.
		return $hidden;
	}

	/**
	 * Extend the search in the backend for post-meta fields of our immo objects.
	 *
	 * @param WP_Query $query The "WP_Query" object.
	 *
	 * @return void
	 */
	public function extend_search( WP_Query $query ): void {
		// bail if we are not in the backend.
		if ( ! is_admin() ) {
			return;
		}

		// bail if this is not the main query.
		if ( ! $query->is_main_query() ) {
			return;
		}

		// bail if this is not a search.
		if ( ! $query->is_search() ) {
			return;
		}

		// bail if this is not our post type.
		if ( \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() !== $query->query['post_type'] ) {
			return;
		}

		// get the search term.
		$search_term = $query->get( 's' );

		// get the "meta_query".
		$meta_query = $query->get( 'meta_query' );

		// if it is empty, create an array.
		if ( empty( $meta_query ) ) {
			$meta_query = array();
		}

		// update the relational.
		$meta_query['relation'] = 'OR';

		// add the fields we want to use for search.
		foreach ( Fields::get_instance()->get_fields_as_objects() as $field ) {
			$meta_query[] = array(
				'key'     => $field->get_name(),
				'value'   => $search_term,
				'compare' => 'LIKE',
			);
		}

		// set the meta-query.
		$query->set( 'meta_query', $meta_query );

		// remove default search.
		$query->set( 's', '' );
	}

	/**
	 * Prevent import of incomplete object data if main fields are missing.
	 *
	 * @param bool                $prevent_import The marker to prevent import.
	 * @param array<string,mixed> $immo_object The object data.
	 *
	 * @return bool
	 */
	public function prevent_import_by_missing_fields( bool $prevent_import, array $immo_object ): bool {
		// bail if ID, name or title are missing.
		if ( empty( $immo_object['id'] ) || empty( $immo_object['name'] ) || empty( $immo_object['title'] ) ) {
			return true;
		}

		// return the value.
		return $prevent_import;
	}

	/**
	 * Prevent import of incomplete object data by its given state.
	 *
	 * @param bool                $prevent_import The marker to prevent import.
	 * @param array<string,mixed> $immo_object The object data.
	 *
	 * @return bool
	 */
	public function prevent_import_by_state( bool $prevent_import, array $immo_object ): bool {
		// check if "property_status" (API v1) is set.
		if ( ! empty( $immo_object['property_status']['id'] ) ) {
			return 'Vermarktung' !== $immo_object['property_status']['name'];
		}

		// bail if "property_status_id" (API v2) is set.
		if ( ! empty( $immo_object['property_status_id'] ) ) {
			return 'Vermarktung' !== $immo_object['property_status_id'];
		}

		// prevent the import if no state is set.
		return true;
	}

	/**
	 * Prevent import of incomplete object data by its given property state.
	 *
	 * @param bool                $prevent_import The marker to prevent import.
	 * @param array<string,mixed> $immo_object The object data.
	 *
	 * @return bool
	 */
	public function prevent_import_by_broker( bool $prevent_import, array $immo_object ): bool {
		// check if "broker" (API v1) is set.
		if ( ! empty( $immo_object['broker']['id'] ) ) {
			return $this->prevent_import_by_taxonomy( 'propstack_connector_import_broker', (string) $immo_object['broker']['id'], $prevent_import );
		}

		// check if "broker_id" (API v2) is set.
		if ( ! empty( $immo_object['broker_id'] ) ) {
			return $this->prevent_import_by_taxonomy( 'propstack_connector_import_broker', (string) $immo_object['broker_id'], $prevent_import );
		}

		// return the value.
		return $prevent_import;
	}

	/**
	 * Prevent import of incomplete object data by its given marketing type.
	 *
	 * @param bool                $prevent_import The marker to prevent import.
	 * @param array<string,mixed> $immo_object The object data.
	 *
	 * @return bool
	 */
	public function prevent_import_by_marketing_type( bool $prevent_import, array $immo_object ): bool {
		// check if "marketing_type" (API v1) is set.
		if ( ! empty( $immo_object['marketing_type']['id'] ) ) {
			return $this->prevent_import_by_taxonomy( 'propstack_connector_import_marketing_type', (string) $immo_object['marketing_type']['id'], $prevent_import );
		}

		// check if "marketing_type" (API v2) is set.
		if ( ! empty( $immo_object['marketing_type'] ) ) {
			return $this->prevent_import_by_taxonomy( 'propstack_connector_import_marketing_type', (string) $immo_object['marketing_type'], $prevent_import );
		}

		// return the value.
		return $prevent_import;
	}

	/**
	 * Prevent import of incomplete object data by its given object type.
	 *
	 * @param bool                $prevent_import The marker to prevent import.
	 * @param array<string,mixed> $immo_object The object data.
	 *
	 * @return bool
	 */
	public function prevent_import_by_object_type( bool $prevent_import, array $immo_object ): bool {
		// prepare the object type name variable.
		$object_type_name = '';

		// check if "rs_type" (API v1) is set.
		if ( ! empty( $immo_object['rs_type']['id'] ) ) {
			$object_type_name = $immo_object['rs_type']['id'];
		} elseif ( ! empty( $immo_object['rs_type'] ) ) {
			// check if "rs_type" (API v2) is set.
			$object_type_name = $immo_object['rs_type'];
		}

		// bail if no object type name could be found.
		if ( empty( $object_type_name ) ) {
			return true;
		}

		// check if the given object type name does exist.
		$term = ObjectType::get_instance()->get_term_id_by_api_value( $object_type_name, Languages::get_instance()->get_current_lang() );

		// prevent import if the term does not exist.
		if ( ! $term ) {
			return true;
		}

		// check for configured prevention.
		return $this->prevent_import_by_taxonomy( 'propstack_connector_import_object_type', (string) $immo_object['rs_type'], $prevent_import );
	}

	/**
	 * Prevent import of incomplete object data by its given object type.
	 *
	 * @param bool                $prevent_import The marker to prevent import.
	 * @param array<string,mixed> $immo_object The object data.
	 *
	 * @return bool
	 */
	public function prevent_import_by_property_type( bool $prevent_import, array $immo_object ): bool {
		// check if "rs_type" (API v1) is set.
		if ( ! empty( $immo_object['rs_category']['id'] ) ) {
			return $this->prevent_import_by_taxonomy( 'propstack_connector_import_property_type', (string) $immo_object['rs_category']['id'], $prevent_import );
		}

		// check if "rs_type" (API v2) is set.
		if ( ! empty( $immo_object['rs_category'] ) ) {
			return $this->prevent_import_by_taxonomy( 'propstack_connector_import_property_type', (string) $immo_object['rs_category'], $prevent_import );
		}

		// return the value.
		return $prevent_import;
	}

	/**
	 * Prevent import of incomplete object data by its given property state.
	 *
	 * @param string $setting_name   The settings name we want to use.
	 * @param string $value          The value from API to check.
	 * @param bool   $prevent_import The marker to prevent import.
	 *
	 * @return bool
	 */
	public function prevent_import_by_taxonomy( string $setting_name, string $value, bool $prevent_import ): bool {
		// get the list of allowed states.
		$allowed_states = get_option( $setting_name );

		// bail if the list is empty, we allow all in this case.
		if ( empty( $allowed_states ) ) {
			return $prevent_import;
		}

		// bail if the first entry is empty.
		if ( isset( $allowed_states[0] ) && empty( $allowed_states[0] ) ) {
			return $prevent_import;
		}

		// prevent the import if the given value is not in the list of allowed terms for import.
		if ( ! in_array( $value, $allowed_states, true ) ) {
			return true;
		}

		// return the value.
		return $prevent_import;
	}

	/**
	 * Assign the feature image to the objects if the queue has been run.
	 *
	 * @return void
	 */
	public function assign_feature_image_via_queue(): void {
		$this->assign_feature_image( 0 );
	}

	/**
	 * Assign the feature image during the import.
	 *
	 * @param array<string,mixed> $object_data  The object data from API.
	 * @param int                 $post_id The post-ID of the object.
	 *
	 * @return void
	 */
	public function assign_feature_image_during_import( array $object_data, int $post_id ): void {
		// update the position for each image from what we got from the Propstack API.
		if ( isset( $object_data['images'] ) ) {
			foreach ( $object_data['images'] as $file_data ) {
				// get the file by its ID in the media library.
				$attachment_id = Files::get_instance()->is_file_in_media_library( $file_data['id'] );

				// bail if attachment ID is not known.
				if ( 0 === $attachment_id ) {
					continue;
				}

				// update the position from Propstack to the file, if given.
				if ( ! empty( $file_data['position'] ) ) {
					update_post_meta( $attachment_id, 'propstack_file_position', $file_data['position'] );
				}
			}
		}

		// re-calc the feature image for this object.
		$this->assign_feature_image( $post_id );
	}

	/**
	 * Assign the feature image to the immo object.
	 *
	 * @param int $post_id The ID of the immo object.
	 *
	 * @return void
	 */
	public function assign_feature_image( int $post_id ): void {
		// if no post-ID is set, get all objects and assign the thumbnail for each one.
		$list = array();
		if ( 0 === $post_id ) {
			foreach ( $this->get_objects( array( 'posts_per_page' => -1 ) ) as $object ) {
				$list[] = $object->get_id();
			}
		} else {
			$list = array( $post_id );
		}

		// loop through the list of objects and assign the thumbnail for each one.
		foreach ( $list as $object_post_id ) {
			// get the images of the given object.
			$query   = array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_parent'    => $object_post_id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);
			$results = new WP_Query( $query );

			// bail on no results.
			if ( 0 === $results->found_posts ) {
				continue;
			}

			// get the image with the lowest position value on this immo object.
			$position                    = 100000;
			$attachment_id_for_thumbnail = false;
			foreach ( $results->get_posts() as $attachment_id ) {
				if ( ! is_int( $attachment_id ) ) {
					continue;
				}

				// get the position value.
				$attachment_position = absint( get_post_meta( $attachment_id, 'propstack_file_position', true ) );

				// bail if no position is set.
				if ( 0 === $attachment_position ) {
					continue;
				}

				// find the lowest value.
				if ( $position > $attachment_position ) {
					$position                    = $attachment_position;
					$attachment_id_for_thumbnail = $attachment_id;
				}
			}

			// bail if no image was found.
			if ( ! $attachment_id_for_thumbnail ) {
				return;
			}

			// add a log entry if debug is enabled.
			if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
				/* translators: %1$s will be replaced by the attachment ID. */
				Log::get_instance()->add( sprintf( __( 'Assign image %1$s as thumbnail to object %2$s.', 'connector-for-propstack' ), '<em>' . $attachment_id_for_thumbnail . '</em>', '<em>' . $object_post_id . '</em>' ), 'info', 'import' );
			}

			// assign this image as the featured image to the position.
			set_post_thumbnail( $object_post_id, $attachment_id_for_thumbnail );
		}
	}

	/**
	 * Return whether we have objects imported from Propstack.
	 *
	 * @return bool
	 */
	public function has_objects(): bool {
		return 1 === absint( get_option( 'cfprop_has_objects' ) );
	}

	/**
	 * Clean up after import of objects.
	 *
	 * @param Import_Base $import_obj The import object.
	 *
	 * @return void
	 */
	public function cleanup_after_import( Import_Base $import_obj ): void {
		// get the process handler with this ID.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_id( $import_obj->get_process_id() );

		// update status.
		$process_handler->set_status( __( 'Get the objects in tip-top shape', 'connector-for-propstack' ) );

		// get the not updated objects.
		$query   = array(
			'post_type'      => PostTypes\ImmoObject::get_instance()->get_name(),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => 'changed',
					'compare' => 'NOT EXISTS',
				),
			),
			'fields'         => 'ids',
		);
		$results = new WP_Query( $query );

		// loop through the posts of objects and delete them.
		foreach ( $results->get_posts() as $post_id ) {
			if ( $post_id instanceof WP_Post ) {
				continue;
			}
			wp_delete_post( $post_id, true );
		}

		// remove the changed marker on each other object.
		foreach ( $this->get_objects( array( 'posts_per_page' => -1 ) ) as $object ) {
			delete_post_meta( $object->get_id(), 'changed' );
		}
	}

	/**
	 * Check for the main object type used by the objects of this Propstack account.
	 *
	 * We check how often the objects use an object type.
	 * The object type with the most assignments is treated as the primary object type.
	 *
	 * @return void
	 */
	public function set_main_object_type(): void {
		// get all objects.
		$objects = $this->get_objects( array( 'posts_per_page' => -1 ) );

		// get their IDs.
		$object_ids = array();
		foreach ( $objects as $object ) {
			$object_ids[] = $object->get_id();
		}

		// query for their object types.
		$terms = wp_get_object_terms( $object_ids, ObjectType::get_instance()->get_name() );

		// bail if no terms could be found.
		if ( ! is_array( $terms ) ) {
			return;
		}

		// get the term with the highest count.
		$max_count             = 0;
		$main_object_type_term = false;
		foreach ( $terms as $term ) {
			if ( $term->count > $max_count ) {
				$max_count             = $term->count;
				$main_object_type_term = $term;
			}
		}

		// if a main object term could be found, save its slug in settings.
		if ( ! $main_object_type_term instanceof WP_Term ) {
			return;
		}
		update_option( 'cfprop_main_object_type', $main_object_type_term->slug );
	}

	/**
	 * Hide fields in the frontend, if configured.
	 *
	 * @param array<int,Field_Base> $fields List of fields.
	 *
	 * @return array<int,Field_Base>
	 */
	public function hide_object_type_fields( array $fields ): array {
		// bail if we are in the backend.
		if ( is_admin() ) {
			return $fields;
		}

		// check the fields.
		foreach ( $fields as $index => $field ) {
			if ( $field->hide() ) {
				unset( $fields[ $index ] );
			}
		}

		// return the resulting list of fields.
		return $fields;
	}

	/**
	 * Save whether we have objects imported (1) oder not (0).
	 *
	 * @return void
	 */
	public function set_has_objects(): void {
		update_option( 'cfprop_has_objects', count( $this->get_objects() ) > 0 ? 1 : 0 );
	}

	/**
	 * Run tasks after deletion of a single object.
	 *
	 * @return void
	 */
	public function after_deletion_of_object(): void {
		$this->set_has_objects();
		Cache::get_instance()->clear_cache();
	}

	/**
	 * Run the import depending on the API version setting.
	 *
	 * @param string $process_id The process ID to use.
	 *
	 * @return Import_Base
	 */
	public function import( string $process_id ): Import_Base {
		$import_obj = new Imports\v1\Objects();
		if ( 'v2' === get_option( 'propstack_connector_api_version' ) ) {
			$import_obj = new Imports\v2\Objects();
		}

		// run the import.
		$import_obj->set_process_id( $process_id );
		$import_obj->run();

		// return the import object.
		return $import_obj;
	}

	/**
	 * Return the import URL.
	 *
	 * @return string
	 */
	public function get_import_url(): string {
		return add_query_arg(
			array(
				'action' => 'cfprop_import_objects',
				'nonce'  => wp_create_nonce( 'import-propstack-objects' ),
			),
			get_admin_url() . 'admin.php'
		);
	}

	/**
	 * Return the import dialog.
	 *
	 * @return array<string,mixed>
	 */
	private function get_import_dialog(): array {
		return array(
			'className' => 'cfprop-dialog',
			'title'     => __( 'Import your objects from Propstack', 'connector-for-propstack' ),
			'texts'     => array(
				'<p><strong>' . __( 'Click on the button below to start the import of your objects from Propstack.', 'connector-for-propstack' ) . '</strong></p>',
				'<p>' . __( 'The import could take some time. Please be patient.', 'connector-for-propstack' ) . '</p>',
			),
			'buttons'   => array(
				array(
					'action'  => 'propstack_connector_object_import("' . esc_attr( ProcessHandler::get_instance()->create_id() ) . '");',
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
	 * Return the import dialog via AJAX.
	 *
	 * @return void
	 */
	public function get_import_dialog_by_ajax(): void {
		// check nonce.
		check_ajax_referer( 'cfprop-get-import-dialog', 'nonce' );

		// bail if the capability check fails.
		if ( ! current_user_can( Settings::get_instance()->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// return the dialog.
		wp_send_json( array( 'detail' => $this->get_import_dialog() ) );
	}

	/**
	 * Remove documents from the Propstack API response as we do not use them in the websites.
	 *
	 * @param array<string,mixed> $response_data The API response.
	 *
	 * @return array<string,mixed>
	 */
	public function remove_document_from_response( array $response_data ): array {
		foreach ( $response_data as $key => $value ) {
			if ( isset( $value['documents'] ) ) {
				unset( $response_data[ $key ]['documents'] );
			}
		}
		return $response_data;
	}

	/**
	 * Save the API response
	 *
	 * @param array<string,mixed> $data The API response.
	 *
	 * @return array<string,mixed>
	 */
	public function save_response( array $data ): array {
		update_option( 'cfprop_last_api_response', $data );
		return $data;
	}

	/**
	 * Add the marketing type as a parameter to the import-URL if only 1 marketing type is selected.
	 *
	 * @param string $url The URL.
	 *
	 * @return string
	 */
	public function add_marketing_type_to_import_url( string $url ): string {
		// get the configured marketing types.
		$marketing_types = get_option( 'propstack_connector_import_marketing_type' );

		// bail if no marketing types are configured.
		if ( ! is_array( $marketing_types ) || empty( $marketing_types ) ) {
			return $url;
		}

		// bail if 2 marketing types are configured.
		if ( 2 === count( $marketing_types ) ) {
			return $url;
		}

		// add the marketing type to the URL.
		return add_query_arg( array( 'marketing_type' => $marketing_types[0] ), $url );
	}

	/**
	 * Add the object type as a parameter to the import-URL if only 1 object type is selected.
	 *
	 * @param string $url The URL.
	 *
	 * @return string
	 */
	public function add_object_type_to_import_url( string $url ): string {
		// get the configured marketing types.
		$object_types = get_option( 'propstack_connector_import_object_type' );

		// bail if no marketing types are configured.
		if ( ! is_array( $object_types ) || empty( $object_types ) ) {
			return $url;
		}

		// bail if more than 1 object types are configured.
		if ( count( $object_types ) > 1 ) {
			return $url;
		}

		// add the marketing type to the URL.
		return add_query_arg( array( 'rs_type' => $object_types[0] ), $url );
	}

	/**
	 * Mark all objects as updated.
	 *
	 * @return void
	 */
	public function mark_as_updated(): void {
		foreach ( $this->get_objects( array( 'posts_per_page' => -1 ) ) as $object ) {
			// mark the object as changed.
			update_post_meta( $object->get_id(), 'changed', time() );
		}
	}

	/**
	 * Remove the changed marker, used during the import, on each other language.
	 *
	 * @return void
	 */
	public function remove_changed_flag(): void {
		foreach ( Languages::get_instance()->get_languages() as $language_code => $name ) {
			delete_option( 'cfprop_md5_' . $language_code );
		}
	}

	/**
	 * Check if a restriction value has been changed.
	 *
	 * @param mixed $new_value The old value.
	 * @param mixed $old_value The new value.
	 *
	 * @return mixed
	 */
	public function check_for_changed_restriction_value( mixed $new_value, mixed $old_value ): mixed {
		// bail if values did not change.
		if ( $new_value === $old_value ) {
			return $new_value;
		}

		/**
		 * Run tasks if one restriction setting has been changed.
		 *
		 * @since 1.0.0 Availability 1.0.0.
		 */
		do_action( 'cfprop_restriction_value_changed' );

		// return the new value.
		return $new_value;
	}

	/**
	 * Show a hint for Pro plugin on single fields in the backend.
	 *
	 * @param WP_Post    $post The post object of the immo object.
	 * @param Field_Base $field The field object.
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function show_pro_hint_on_field( WP_Post $post, Field_Base $field ): void {
		$false = false;
		/**
		 * Hide the additional buttons for reviews or pro-version.
		 *
		 * @since 1.0.0 Available since 1.0.0
		 * @param bool $false Set true to hide the buttons.
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		if ( apply_filters( 'cfprop_hide_pro_hints', $false ) ) {
			return;
		}

		// bail if this is not a Pro field.
		if ( ! $field->only_pro() ) {
			return;
		}

		// show hint.
		echo '<div class="cfprop-pro-hint">' . esc_html__( 'Use this in Pro', 'connector-for-propstack' ) . '</div>';
	}
}

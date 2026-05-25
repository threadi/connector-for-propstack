<?php
/**
 * File for handling any Propstack specific support.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Fields\PermalinkSlug;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Settings;
use stdClass;
use WP_Post;

/**
 * Object to handle any Propstack specific support.
 */
class Propstack {

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Propstack
	 */
	private static ?Propstack $instance = null;

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
	public static function get_instance(): Propstack {
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
		// use our own hooks.
		add_filter( 'cfprop_register_taxonomies', array( $this, 'register_taxonomies' ) );

		// initialize the post-types.
		Post_Types::get_instance()->init();

		// initialize the taxonomies.
		Taxonomies::get_instance()->init();

		// initialize the fields.
		Fields::get_instance()->init();

		// initialize the broker.
		Broker::get_instance()->init();

		// initialize the immo objects.
		ImmoObjects::get_instance()->init();

		// initialize the file support.
		Files::get_instance()->init();

		// initialize the queue.
		Queue::get_instance()->init();

		// initialize the REST API support.
		Rest::get_instance()->init();

		// initialize the templates.
		Templates::get_instance()->init();

		// initialize the widgets.
		Widgets::get_instance()->init();

		// initialize the filter.
		Filters::get_instance()->init();

		// initialize the knowledge center.
		KnowledgeCenter::get_instance()->init();

		// use hooks.
		add_action( 'init', array( $this, 'add_settings' ), 20 );
		add_filter( 'cfprop_log_categories', array( $this, 'add_categories' ) );
		add_filter( 'cfprop_schedules', array( $this, 'add_schedules' ) );

		// add additional url support.
		add_filter( 'post_link', array( $this, 'set_custom_permalink' ), 10, 2 );
		add_filter( 'post_type_link', array( $this, 'set_custom_permalink' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'hide_single_view' ) );
	}

	/**
	 * Add our Propstack specific taxonomies to the immo post type.
	 *
	 * @param array<int,string> $taxonomies List of taxonomies.
	 *
	 * @return array<int,string>
	 */
	public function register_taxonomies( array $taxonomies ): array {
		// add the taxonomies we use for Propstack.
		$taxonomies[] = 'ConnectorForPropstack\Propstack\Taxonomies\Broker';
		$taxonomies[] = 'ConnectorForPropstack\Propstack\Taxonomies\Category';
		$taxonomies[] = 'ConnectorForPropstack\Propstack\Taxonomies\MarketingType';
		$taxonomies[] = 'ConnectorForPropstack\Propstack\Taxonomies\ObjectType';
		$taxonomies[] = 'ConnectorForPropstack\Propstack\Taxonomies\PropertyType';

		// return the list of taxonomie.
		return $taxonomies;
	}

	/**
	 * Run tasks during activation of the plugin.
	 *
	 * @return void
	 */
	public function activation(): void {
		foreach ( Taxonomies::get_instance()->get_taxonomies_as_objects() as $taxonomy ) {
			$taxonomy->register();
			$taxonomy->activation();
		}
	}

	/**
	 * Add our own categories for logging.
	 *
	 * @param array<string,string> $categories The log categories.
	 *
	 * @return array<string,string>
	 */
	public function add_categories( array $categories ): array {
		$categories['import'] = __( 'Import', 'connector-for-propstack' );
		return $categories;
	}

	/**
	 * Validate a given string from REST API as a potential API token.
	 *
	 * Returns an array with the list of errors.
	 * Returns an empty array if all is ok.
	 *
	 * @param string $value The API token.
	 *
	 * @return array<string,string>
	 * @noinspection PhpUnused
	 */
	public static function rest_validate_key( string $value ): array {
		$value = self::cleanup_url_string( $value );

		// check if the value has size.
		if ( empty( $value ) ) {
			// return empty string as we do not mark this as a failure.
			return array();
		}

		// bail if the given value has not exact 40 characters.
		if ( strlen( $value ) !== 40 ) {
			return array(
				'error' => 'short',
				'text'  => __( 'The specified API token is too short. Please double-check the API token in your Propstack-account.', 'connector-for-propstack' ),
			);
		}

		// bail if the given value is not an alphanumeric string.
		if ( 0 === preg_match( '/^[a-zA-Z0-9-_]+$/', $value ) ) {
			return array(
				'error' => 'character_error',
				'text'  => __( 'The specified API token contains not allowed characters. Please double-check the API token in your Propstack-account.', 'connector-for-propstack' ),
			);
		}

		// return an empty value if no error occurred.
		return array();
	}

	/**
	 * Clean up a string.
	 *
	 * @param string $value The string.
	 *
	 * @return string
	 */
	public static function cleanup_url_string( string $value ): string {
		return trim( $value );
	}

	/**
	 * Add custom settings for Propstack.
	 *
	 * @return void
	 */
	public function add_settings(): void {
		// get the settings object.
		$settings_obj = Settings::get_instance()->get_settings_obj();

		// get the page.
		$permalink_page = $settings_obj->add_page( 'permalink' );

		// add a tab on this page.
		$permalinks_tab = $permalink_page->add_tab( 'permalink', 10 );

		// add the section in this tab.
		$permalink_section = $permalinks_tab->add_section( 'propstack_connector_slugs', 10 );
		$permalink_section->set_title( __( 'Connector for Propstack', 'connector-for-propstack' ) );

		// collect the options for permalink settings.
		$options = array();
		foreach ( Taxonomies::get_instance()->get_taxonomies_as_objects() as $taxonomy ) {
			$options[ $taxonomy->get_name() ] = $taxonomy->get_title();
		}

		// add setting.
		$setting = $settings_obj->add_setting( 'cfprop_archive_slug' );
		$setting->set_type( 'string' );
		$setting->set_default( Helper::get_archive_slug() );
		$setting->set_section( $permalink_section );
		$field = new PermalinkSlug( $settings_obj );
		$field->set_title( __( 'URL-slug for archive view', 'connector-for-propstack' ) );
		$field->set_options( $options );
		$field->set_list_title( __( 'Available tags:', 'connector-for-propstack' ) );
		$setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'cfprop_single_slug' );
		$setting->set_type( 'string' );
		$setting->set_default( Helper::get_single_slug() );
		$setting->set_section( $permalink_section );
		$field = new PermalinkSlug( $settings_obj );
		$field->set_title( __( 'URL-slug for single view', 'connector-for-propstack' ) );
		$field->set_options( $options );
		$field->set_list_title( __( 'Available tags:', 'connector-for-propstack' ) );
		$setting->set_field( $field );
	}

	/**
	 * Hide a single view of positions if the option is enabled for this.
	 *
	 * @return void
	 */
	public function hide_single_view(): void {
		// bail if this is not our cpt.
		if ( ! is_singular( \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() ) ) {
			return;
		}

		// bail if a single view is not disabled.
		if ( 1 !== absint( get_option( 'propstack_connector_disable_single_slug' ) ) ) {
			return;
		}

		// bail if this is not a single view.
		if ( ! is_single() ) {
			return;
		}

		// show 404 page.
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
	}

	/**
	 * Support for custom post-type slugs with dynamic parts for our own Position cpt.
	 *
	 * @param string           $permalink The permalink.
	 * @param stdClass|WP_Post $post The post-object of the post.
	 *
	 * @return string
	 */
	public function set_custom_permalink( string $permalink, stdClass|WP_Post $post ): string {
		// bail if $post is not given.
		if ( ! $post instanceof WP_Post ) {
			return $permalink;
		}

		// bail if this is not a post from our cpt.
		if ( \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() !== $post->post_type ) {
			return $permalink;
		}

		// loop through the possible taxonomies.
		foreach ( Taxonomies::get_instance()->get_taxonomies_as_objects() as $taxonomy ) {
			// bail if the permalink does not contain any dynamic part.
			if ( ! str_contains( $permalink, '%' . $taxonomy->get_name() . '%' ) ) {
				continue;
			}

			// get the terms of the post.
			$terms = wp_get_object_terms( $post->ID, $taxonomy->get_name() );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$taxonomy_slug = $terms[0]->slug;
			} else {
				$taxonomy_slug = 'other';
			}

			return str_replace( '%' . $taxonomy->get_name() . '%', $taxonomy_slug, $permalink );
		}

		// return permalink in all other cases.
		return $permalink;
	}

	/**
	 * Add our Propstack specific schedules.
	 *
	 * @param array<int,string> $schedules The list of schedules.
	 *
	 * @return array<int,string>
	 */
	public function add_schedules( array $schedules ): array {
		// add our custom schedules for Propstack.
		$schedules[] = '\ConnectorForPropstack\Propstack\Schedules\Objects';
		$schedules[] = '\ConnectorForPropstack\Propstack\Schedules\Queue';

		// return the resulting list.
		return $schedules;
	}
}

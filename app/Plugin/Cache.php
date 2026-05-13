<?php
/**
 * File to handle caching tasks.
 *
 * This object caches value in option, not in transient, to be more persistent and not user-dependent.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle caching tasks.
 */
class Cache {

	/**
	 * Instance of this object.
	 *
	 * @var ?Cache
	 */
	private static ?Cache $instance = null;

	/**
	 * Constructor, which sets the active method.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Cache {
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
		add_action( 'cfprop_import_object_before_start', array( $this, 'clear_cache' ) );

		// clear the cache also on any user change, as we have a cache, which saves the first admin user as fallback for authors of object posts.
		add_action( 'wp_update_user', array( $this, 'clear_cache' ), 10, 0 );
		add_action( 'user_register', array( $this, 'clear_cache' ), 10, 0 );
	}

	/**
	 * Return the cached value of the given key.
	 *
	 * @param string $key The given key.
	 *
	 * @return mixed
	 */
	public static function get( string $key ): mixed {
		return get_option( 'propstack_connector_cache_' . $key . '_' . Languages::get_instance()->get_current_lang() );
	}

	/**
	 * Set the cached value of a given key.
	 *
	 * @param string $key The cache key.
	 * @param mixed  $cache The value to cache.
	 *
	 * @return void
	 */
	public static function set( string $key, mixed $cache ): void {
		update_option( 'propstack_connector_cache_' . $key . '_' . Languages::get_instance()->get_current_lang(), $cache );
	}

	/**
	 * Delete a cache entry by the given key.
	 *
	 * @param string $key The cache key.
	 *
	 * @return void
	 */
	public static function delete( string $key ): void {
		delete_option( 'propstack_connector_cache_' . $key . '_' . Languages::get_instance()->get_current_lang() );
	}

	/**
	 * Clear the complete cache.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		global $wpdb;

		// get the entries.
		$entries = Db::get_instance()->get_results( $wpdb->prepare( 'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name like %s', 'propstack_connector_cache_%%' ) );

		// delete the entries.
		foreach ( $entries as $entry ) {
			delete_option( $entry['option_name'] );
		}
	}
}

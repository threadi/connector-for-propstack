<?php
/**
 * File for handle user tasks.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use WP_User;
use WP_User_Query;

/**
 * Object to handle user tasks.
 */
class Users {
	/**
	 * Instance of this object.
	 *
	 * @var ?Users
	 */
	private static ?Users $instance = null;

	/**
	 * Constructor for Init-Handler.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return instance of this object as singleton.
	 *
	 * @return Users
	 */
	public static function get_instance(): Users {
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
		add_action( 'set_user_role', array( $this, 'reset_cache' ), 10, 0 );
	}

	/**
	 * Return the ID of the first administrator user.
	 *
	 * @return int
	 */
	public function get_first_administrator_user(): int {
		// get value from the cache.
		$user_id = absint( get_option( 'cfprop_admin_id', 0 ) );

		// return the ID from cache, if given.
		if ( $user_id > 0 ) {
			return $user_id;
		}

		// get the first user with administrator role.
		$query   = array(
			'role'   => 'administrator',
			'number' => 1,
		);
		$results = new WP_User_Query( $query );

		// bail on no results.
		if ( 0 === $results->get_total() ) {
			return 0;
		}

		// get the results.
		$roles = $results->get_results();

		// bail if no results returned.
		if ( empty( $roles ) ) {
			return 0;
		}

		// bail if first entry does not exist.
		if ( empty( $roles[0] ) ) {
			return 0;
		}

		// get the ID.
		$user_id = absint( $roles[0]->ID );

		// save this ID in the cache.
		update_option( 'cfprop_admin_id', $user_id );

		// return it.
		return $user_id;
	}

	/**
	 * Reset the cache.
	 *
	 * @return void
	 */
	public function reset_cache(): void {
		delete_option( 'cfprop_admin_id' );
	}

	/**
	 * Return list of users for settings.
	 *
	 * @param bool $ignore_actual_user True if the actual user should not be included in list.
	 *
	 * @return array<string|int,string>
	 */
	public function get_users_for_settings( bool $ignore_actual_user = false ): array {
		// load user-list only on specific requests.
		if ( 'connector-for-propstack' !== filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) {
			return array();
		}

		// get it from the cache.
		$users = Cache::get( 'users_for_settings' );

		// return it from the cache.
		if ( ! empty( $users ) ) {
			return $users;
		}

		// get the users and create the list.
		$users = array();
		foreach ( get_users() as $user ) {
			// bail if user is not a "WP_User" object.
			if ( ! $user instanceof WP_User ) {
				continue;
			}

			// bail if this is the actual user, it this is enabled.
			if ( $ignore_actual_user && get_current_user_id() === $user->ID ) {
				continue;
			}

			// add to the list.
			$users[ (string) $user->ID ] = $user->display_name;
		}

		// save in the cache.
		Cache::set( 'users_for_settings', $users );

		// return list of users.
		return $users;
	}
}

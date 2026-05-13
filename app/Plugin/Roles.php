<?php
/**
 * File to handle roles used from this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Fields\MultiSelect;
use easySettingsForWordPress\Page;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use WP_Role;
use WP_Roles;

/**
 * Object to handle roles.
 */
class Roles {
	/**
	 * Instance of this object.
	 *
	 * @var ?Roles
	 */
	private static ?Roles $instance = null;

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
	public static function get_instance(): Roles {
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
		add_action( 'init', array( $this, 'add_permission_settings' ) );
	}

	/**
	 * Install the roles we use.
	 *
	 * @return void
	 */
	public function install(): void {
		// add a user role to manage immo objects if it does not exist.
		$manager_role = get_role( 'manage_propstack_objects' );
		if ( is_null( $manager_role ) ) {
			$manager_role = add_role( 'manage_propstack_objects', __( 'Manage Propstack-based Objects', 'connector-for-propstack' ) );
		}
		if ( ! is_null( $manager_role ) ) {
			$manager_role->add_cap( 'read' ); // to enter wp-admin.
			$manager_role->add_cap( 'read_' . ImmoObject::get_instance()->get_name() );
			$manager_role->add_cap( 'manage_' . ImmoObject::get_instance()->get_name() );
		}

		// get admin-role.
		$admin_role = get_role( 'administrator' );
		if ( ! is_null( $admin_role ) ) {
			$admin_role->add_cap( 'read_' . ImmoObject::get_instance()->get_name() );
			$admin_role->add_cap( 'manage_' . ImmoObject::get_instance()->get_name() );
		}
	}

	/**
	 * Remove the role we use.
	 *
	 * @return void
	 */
	public function uninstall(): void {
		/**
		 * Remove our own role.
		 */
		remove_role( 'manage_propstack_objects' );

		/**
		 * Remove our capabilities from other roles.
		 */
		global $wp_roles;
		foreach ( $wp_roles->roles as $role_name => $settings ) {
			// get the role.
			$role = get_role( $role_name );

			// bail if the object could not be loaded.
			if ( ! $role instanceof WP_Role ) {
				continue;
			}

			// remove the capabilities.
			$role->remove_cap( 'manage_' . ImmoObject::get_instance()->get_name() );
			$role->remove_cap( 'read_' . ImmoObject::get_instance()->get_name() );
		}
	}

	/**
	 * Add setting for permissions.
	 *
	 * @return void
	 */
	public function add_permission_settings(): void {
		// bail if user has not the permission to manage permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// get the settings object.
		$settings_obj = Settings::get_instance()->get_settings_obj();

		// get the settings page.
		$main_settings_page = Settings::get_instance()->get_settings_page();

		// bail if page could not be loaded.
		if ( ! $main_settings_page instanceof Page ) {
			return;
		}

		// add permissions tab.
		$permissions_tab = $main_settings_page->add_tab( 'propstack_connector_permissions', 60 );
		$permissions_tab->set_title( __( 'Permissions', 'connector-for-propstack' ) );

		// add a section.
		$permission_section = $permissions_tab->add_section( 'section_permissions_objects', 10 );
		$permission_section->set_title( __( 'Access to objects', 'connector-for-propstack' ) );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_access_roles' );
		$setting->set_type( 'array' );
		$setting->set_default( array() );
		$setting->set_section( $permission_section );
		$setting->set_save_callback( array( $this, 'save' ) );
		$field = new MultiSelect( $settings_obj );
		$field->set_title( __( 'Choose roles', 'connector-for-propstack' ) );
		$field->set_description( __( 'Mark roles, which will have access to objects in the backend. These roles could also control each object-regarding setting except the permissions.', 'connector-for-propstack' ) );
		$field->set_options( $this->get_roles() );
		$setting->set_field( $field );
	}

	/**
	 * Return the roles of this project except the administrator role.
	 *
	 * @return array<string,string>
	 */
	private function get_roles(): array {
		global $wp_roles;

		// bail if "WP_Roles" could not be loaded.
		if ( ! $wp_roles instanceof WP_Roles ) {
			return array();
		}

		// collect the roles.
		$roles = array();

		// add each role to the list except the admin role.
		foreach ( $wp_roles->roles as $role => $role_settings ) {
			// bail if this is the admin role.
			if ( 'administrator' === $role ) {
				continue;
			}

			// add the role.
			$roles[ $role ] = $role_settings['name'];
		}

		// @phpstan-ignore return.type
		return $roles;
	}

	/**
	 * Save the permissions.
	 *
	 * @param array<string>|null|string $value The value to save.
	 *
	 * @return array<string>
	 */
	public function save( array|null|string $value ): array {
		global $wp_roles;

		// change value to array.
		if ( ! is_array( $value ) ) {
			$value = array();
		}

		// get our cpt-name.
		$cpt_name = ImmoObject::get_instance()->get_name();

		// remove the cap from all roles.
		foreach ( $wp_roles->roles as $role => $role_settings ) {
			if ( 'administrator' === $role ) {
				continue;
			}

			// get the role as object.
			$role_obj = get_role( $role );

			// bail if role could not be loaded.
			if ( ! $role_obj instanceof WP_Role ) {
				continue;
			}

			// remove the capabilities.
			$role_obj->remove_cap( 'manage_' . $cpt_name );
			$role_obj->remove_cap( 'read_' . $cpt_name );
			$role_obj->remove_cap( 'manage_categories' );
		}

		// add the cap to the configured roles.
		foreach ( $value as $role ) {
			if ( 'administrator' === $role ) {
				continue;
			}

			// remove caps from this role.
			$role_obj = get_role( $role );

			// bail if role could not be loaded.
			if ( ! $role_obj instanceof WP_Role ) {
				continue;
			}

			// add the capabilities.
			$role_obj->add_cap( 'manage_' . $cpt_name );
			$role_obj->add_cap( 'read_' . $cpt_name );
			$role_obj->add_cap( 'manage_categories' );
		}

		// return the value.
		return $value;
	}
}

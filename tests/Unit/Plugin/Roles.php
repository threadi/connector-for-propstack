<?php
/**
 * File for tests against \ConnectorForPropstack\Plugin\Roles.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Plugin;

use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against \ConnectorForPropstack\Plugin\Roles.
 */
class Roles extends ConnectorForPropstackTestCase {
	/**
	 * The capabilities of each role before the test.
	 *
	 * @var array<string,array<string,bool>>
	 */
	private array $caps_backup = array();

	/**
	 * The roles to check.
	 *
	 * @var array<int,string>
	 */
	private array $roles = array( 'editor', 'author', 'subscriber' );

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// save the capabilities of every role.
		foreach ( wp_roles()->role_objects as $role_name => $role ) {
			$this->caps_backup[ $role_name ] = $role->capabilities;
		}
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		// restore the capabilities of every role.
		foreach ( $this->caps_backup as $role_name => $caps ) {
			$role = get_role( $role_name );
			if ( ! $role instanceof \WP_Role ) {
				continue;
			}

			// remove the caps, which have been added.
			foreach ( array_diff_key( $role->capabilities, $caps ) as $cap => $grant ) {
				$role->remove_cap( $cap );
			}

			// add the caps, which have been changed or removed.
			foreach ( $caps as $cap => $grant ) {
				if ( ! isset( $role->capabilities[ $cap ] ) || $role->capabilities[ $cap ] !== $grant ) {
					$role->add_cap( $cap, $grant );
				}
			}
		}

		parent::tear_down();
	}

	/**
	 * Return the capability to manage objects.
	 *
	 * @return string
	 */
	private function get_manage_cap(): string {
		return 'manage_' . ImmoObject::get_instance()->get_name();
	}

	/**
	 * Return the capability to read objects.
	 *
	 * @return string
	 */
	private function get_read_cap(): string {
		return 'read_' . ImmoObject::get_instance()->get_name();
	}

	/**
	 * Return the state of "manage_categories" for the checked roles.
	 *
	 * @return array<string,bool>
	 */
	private function get_manage_categories_state(): array {
		$state = array();
		foreach ( $this->roles as $role_name ) {
			$state[ $role_name ] = get_role( $role_name )->has_cap( 'manage_categories' );
		}
		return $state;
	}

	/**
	 * Test that saving the permissions does not change "manage_categories".
	 *
	 * @return void
	 */
	public function test_save_does_not_change_manage_categories(): void {
		$before = $this->get_manage_categories_state();

		// the editor has this capability per default, the others not.
		$this->assertTrue( $before['editor'] );
		$this->assertFalse( $before['author'] );
		$this->assertFalse( $before['subscriber'] );

		// grant access to all checked roles.
		\ConnectorForPropstack\Plugin\Roles::get_instance()->save( $this->roles );
		$this->assertSame( $before, $this->get_manage_categories_state() );

		// revoke the access from all roles.
		\ConnectorForPropstack\Plugin\Roles::get_instance()->save( array() );
		$this->assertSame( $before, $this->get_manage_categories_state() );
	}

	/**
	 * Test that saving the permissions adds our capabilities to the selected roles only.
	 *
	 * @return void
	 */
	public function test_save_adds_caps_to_selected_roles(): void {
		$result = \ConnectorForPropstack\Plugin\Roles::get_instance()->save( array( 'author' ) );

		$this->assertSame( array( 'author' ), $result );

		// the author has the capabilities.
		$this->assertTrue( get_role( 'author' )->has_cap( $this->get_manage_cap() ) );
		$this->assertTrue( get_role( 'author' )->has_cap( $this->get_read_cap() ) );

		// the others not.
		$this->assertFalse( get_role( 'editor' )->has_cap( $this->get_manage_cap() ) );
		$this->assertFalse( get_role( 'subscriber' )->has_cap( $this->get_read_cap() ) );
	}

	/**
	 * Test that saving the permissions removes our capabilities from unselected roles.
	 *
	 * @return void
	 */
	public function test_save_removes_caps_from_unselected_roles(): void {
		\ConnectorForPropstack\Plugin\Roles::get_instance()->save( array( 'editor' ) );
		$this->assertTrue( get_role( 'editor' )->has_cap( $this->get_manage_cap() ) );

		\ConnectorForPropstack\Plugin\Roles::get_instance()->save( array( 'author' ) );
		$this->assertFalse( get_role( 'editor' )->has_cap( $this->get_manage_cap() ) );
		$this->assertFalse( get_role( 'editor' )->has_cap( $this->get_read_cap() ) );
	}

	/**
	 * Test that the administrator keeps its capabilities.
	 *
	 * @return void
	 */
	public function test_save_does_not_change_administrator(): void {
		// the administrator got the capabilities during activation.
		$this->assertTrue( get_role( 'administrator' )->has_cap( $this->get_manage_cap() ) );

		\ConnectorForPropstack\Plugin\Roles::get_instance()->save( array() );

		$this->assertTrue( get_role( 'administrator' )->has_cap( $this->get_manage_cap() ) );
		$this->assertTrue( get_role( 'administrator' )->has_cap( $this->get_read_cap() ) );
	}

	/**
	 * Test that invalid values are handled as an empty list.
	 *
	 * @return void
	 */
	public function test_save_with_invalid_values(): void {
		$this->assertSame( array(), \ConnectorForPropstack\Plugin\Roles::get_instance()->save( null ) );
		$this->assertSame( array(), \ConnectorForPropstack\Plugin\Roles::get_instance()->save( 'editor' ) );

		// unknown roles are ignored.
		$this->assertSame( array( 'does_not_exist' ), \ConnectorForPropstack\Plugin\Roles::get_instance()->save( array( 'does_not_exist' ) ) );
		$this->assertNull( get_role( 'does_not_exist' ) );
	}
}

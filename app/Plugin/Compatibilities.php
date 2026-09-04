<?php
/**
 * File to handle every compatibility-check for this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * The object which handles schedules.
 */
class Compatibilities {
	/**
	 * Instance of this object.
	 *
	 * @var ?Compatibilities
	 */
	private static ?Compatibilities $instance = null;

	/**
	 * Constructor for Schedules-Handler.
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
	public static function get_instance(): Compatibilities {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize all compatibility checks for this plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		// add our own hook to prevent checks in wp-admin.
		add_filter( 'cfprop_run_compatibility_checks', array( $this, 'prevent_checks_outside_of_admin' ) );

		// check each compatibility.
		add_action( 'init', array( $this, 'check' ) );
	}

	/**
	 * Check the compatibility of all supported third party products.
	 *
	 * @return void
	 */
	public function check(): void {
		$false = false;
		/**
		 * Filter whether the compatibility-checks should be run (false) or not (true)
		 *
		 * @since 1.0.5 Available since 1.0.5.
		 *
		 * @param bool $false True to prevent compatibility-checks.
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		if ( apply_filters( 'cfprop_run_compatibility_checks', $false ) ) {
			return;
		}

		// loop through our compatibility-checks.
		foreach ( $this->get_compatibility_checks_as_object() as $compatibility_check_obj ) {
			$compatibility_check_obj->check();
		}
	}

	/**
	 * Return the list of compatibilities as objects.
	 *
	 * @return array<Compatibilities_Base>
	 */
	public function get_compatibility_checks_as_object(): array {
		// define the list for the objects.
		$list = array();

		// loop through our compatibility-checks.
		foreach ( $this->get_compatibility_checks() as $compatibility_check ) {
			// get the class name.
			$class_name = $compatibility_check . '::get_instance';

			// bail if it is not callable.
			if ( ! is_callable( $class_name ) ) {
				continue;
			}

			// get the object.
			$obj = $class_name();

			// bail if this object is not a Compatibilities_Base.
			if ( ! $obj instanceof Compatibilities_Base ) {
				continue;
			}

			// add to the list.
			$list[] = $obj;
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Return array of compatibility-objects.
	 *
	 * @return array<string>
	 */
	public function get_compatibility_checks(): array {
		$list = array(
			'ConnectorForPropstack\Plugin\Compatibilities\Avada',
			'ConnectorForPropstack\Plugin\Compatibilities\Breakdance',
			'ConnectorForPropstack\Plugin\Compatibilities\Bricks',
			'ConnectorForPropstack\Plugin\Compatibilities\Brizy',
			'ConnectorForPropstack\Plugin\Compatibilities\Contact_Form_7',
			'ConnectorForPropstack\Plugin\Compatibilities\Elementor',
			'ConnectorForPropstack\Plugin\Compatibilities\Salient_WpBakery',
			'ConnectorForPropstack\Plugin\Compatibilities\WpBakery',
			'ConnectorForPropstack\Plugin\Compatibilities\Wpforms',
			'ConnectorForPropstack\Plugin\Compatibilities\WpformsLite',
		);

		/**
		 * Filter the list of compatibilities.
		 *
		 * @since 1.0.5 Available since 1.0.5.
		 *
		 * @param array<string> $list List of compatibility-checks.
		 */
		return apply_filters( 'cfprop_compatibility_checks', $list );
	}

	/**
	 * Prevent checks outside of admin.
	 *
	 * @param bool $prevent_checks Must be true to prevent the checks.
	 *
	 * @return bool
	 */
	public function prevent_checks_outside_of_admin( bool $prevent_checks ): bool {
		if ( ! is_admin() ) {
			return true;
		}

		// return initial value.
		return $prevent_checks;
	}
}

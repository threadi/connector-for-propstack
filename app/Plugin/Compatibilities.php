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
	 * Name of the transient which marks the last run of the checks.
	 *
	 * @var string
	 */
	private const CHECK_TRANSIENT = 'cfprop_compatibility_checks_run';

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

		// force a new check after changes on plugins or themes.
		add_action( 'activated_plugin', array( $this, 'reset_check_interval' ), 10, 0 );
		add_action( 'deactivated_plugin', array( $this, 'reset_check_interval' ), 10, 0 );
		add_action( 'switch_theme', array( $this, 'reset_check_interval' ), 10, 0 );
		add_action( 'upgrader_process_complete', array( $this, 'reset_check_interval' ), 10, 0 );
	}

	/**
	 * Reset the interval for the compatibility checks, so they will be run on the next request in backend.
	 *
	 * @return void
	 */
	public function reset_check_interval(): void {
		delete_transient( self::CHECK_TRANSIENT );
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

		// bail on AJAX- and cron-requests (e.g., the heartbeat).
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// bail if the checks have been run recently (the resulting hints are kept until the next check).
		if ( get_transient( self::CHECK_TRANSIENT ) ) {
			return;
		}

		// mark the checks as run.
		$interval = 12 * HOUR_IN_SECONDS;
		/**
		 * Filter the interval in seconds in which the compatibility checks are run.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param int $interval The interval in seconds.
		 */
		set_transient( self::CHECK_TRANSIENT, 1, max( 1, absint( apply_filters( 'cfprop_compatibility_checks_interval', $interval ) ) ) );

		// loop through our compatibility-checks.
		foreach ( $this->get_compatibility_checks_as_object() as $compatibility_check_obj ) {
			$compatibility_check_obj->check();
		}
	}

	/**
	 * Return the labels of active page builders (or themes with own page builder), which are not natively supported
	 * by this plugin.
	 *
	 * @return array<int,string>
	 */
	public function get_active_page_builder_labels(): array {
		$list = array();
		foreach ( $this->get_compatibility_checks_as_object() as $compatibility ) {
			if ( ! $compatibility->is_page_builder() || ! $compatibility->is_active() ) {
				continue;
			}
			$list[] = $compatibility->get_label();
		}
		return $list;
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
			'ConnectorForPropstack\Plugin\Compatibilities\Avia',
			'ConnectorForPropstack\Plugin\Compatibilities\Beaver',
			'ConnectorForPropstack\Plugin\Compatibilities\Breakdance',
			'ConnectorForPropstack\Plugin\Compatibilities\Bricks',
			'ConnectorForPropstack\Plugin\Compatibilities\Brizy',
			'ConnectorForPropstack\Plugin\Compatibilities\Contact_Form_7',
			'ConnectorForPropstack\Plugin\Compatibilities\Divi4',
			'ConnectorForPropstack\Plugin\Compatibilities\Divi5',
			'ConnectorForPropstack\Plugin\Compatibilities\Elementor',
			'ConnectorForPropstack\Plugin\Compatibilities\Salient_WpBakery',
			'ConnectorForPropstack\Plugin\Compatibilities\SiteOrigin',
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

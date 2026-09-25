<?php
/**
 * File for handling the energy efficiency classes.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to add support for abilities.
 */
class Energy_Efficiency_Classes {
	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Energy_Efficiency_Classes
	 */
	private static ?Energy_Efficiency_Classes $instance = null;

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
	public static function get_instance(): Energy_Efficiency_Classes {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return the energy efficiency classes.
	 *
	 * @return array<int,string>
	 */
	public function get_energy_efficiency_classes(): array {
		// bail if the value is not in the allowed list.
		$allowed_classes = array(
			'A+',
			'A',
			'B',
			'C',
			'D',
			'E',
			'F',
			'G',
			'H',
		);

		/**
		 * Filter the allowed energy efficiency classes.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param array<int,string> $allowed_classes List of allowed classes.
		 */
		return apply_filters( 'cfprop_energy_efficiency_clases', $allowed_classes );
	}
}

<?php
/**
 * File to handle the 15minutely interval.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin\Intervals;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Interval_Base;

/**
 * Object to handle the 15minutely interval.
 */
class Minutely15 extends Interval_Base {

	/**
	 * Name of the method.
	 *
	 * @var string
	 */
	protected string $name = '15minutely';

	/**
	 * Time of the interval.
	 *
	 * @var int
	 */
	protected int $time = 15 * MINUTE_IN_SECONDS;

	/**
	 * Instance of this object.
	 *
	 * @var ?Minutely15
	 */
	private static ?Minutely15 $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Minutely15 {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return the title of this interval.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Every 15 minutes', 'connector-for-propstack' );
	}
}

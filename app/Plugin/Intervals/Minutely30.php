<?php
/**
 * File to handle the 30minutely interval.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin\Intervals;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Interval_Base;

/**
 * Object to handle the 30minutely interval.
 */
class Minutely30 extends Interval_Base {

	/**
	 * Name of the method.
	 *
	 * @var string
	 */
	protected string $name = '30minutely';

	/**
	 * Time of the interval.
	 *
	 * @var int
	 */
	protected int $time = 30 * MINUTE_IN_SECONDS;

	/**
	 * Instance of this object.
	 *
	 * @var ?Minutely30
	 */
	private static ?Minutely30 $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Minutely30 {
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
		return __( 'Every 30 minutes', 'connector-for-propstack' );
	}
}

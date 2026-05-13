<?php
/**
 * File to handle the queue schedule.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Schedules;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Schedules_Base;

/**
 * Object for this schedule.
 */
class Queue extends Schedules_Base {

	/**
	 * Name of this event.
	 *
	 * @var string
	 */
	protected string $name = 'cfprop_queue';

	/**
	 * Name of the option used to enable this event.
	 *
	 * @var string
	 */
	protected string $interval_option_name = 'propstackConnectorQueueScheduleInterval';

	/**
	 * Define the default interval.
	 *
	 * @var string
	 */
	protected string $default_interval = 'propstack_connector_hourly';

	/**
	 * Initialize this schedule.
	 */
	public function __construct() {
		// get interval from settings.
		$this->interval = get_option( $this->get_interval_option_name() );
	}

	/**
	 * Run this schedule.
	 *
	 * @return void
	 */
	public function run(): void {
		// bail if import is not enabled.
		if ( ! $this->is_enabled() ) {
			// do nothing more.
			return;
		}

		// get the queue object.
		$queue_object = \ConnectorForPropstack\Propstack\Queue::get_instance();

		// process the queue.
		$queue_object->process();
	}
}

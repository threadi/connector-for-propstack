<?php
/**
 * File to handle the schedule for immo objects.
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
class Objects extends Schedules_Base {

	/**
	 * Name of this event.
	 *
	 * @var string
	 */
	protected string $name = 'cfprop_objects';

	/**
	 * Name of the option used to enable this event.
	 *
	 * @var string
	 */
	protected string $interval_option_name = 'propstackConnectorObjectsScheduleInterval';

	/**
	 * Define the default interval.
	 *
	 * @var string
	 */
	protected string $default_interval = 'propstack_connector_15minutely';

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

		// get the import objects for immo objects.
		$import_obj = new \ConnectorForPropstack\Propstack\Imports\v1\Objects();
		if ( 'v2' === get_option( 'propstack_connector_api_version' ) ) {
			$import_obj = new \ConnectorForPropstack\Propstack\Imports\v2\Objects();
		}

		// run the import.
		$import_obj->run();
	}
}

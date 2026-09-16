<?php
/**
 * File to handle the schedule for immo objects.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Schedules;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Log;
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
	protected string $default_interval = 'cfprop_15minutely';

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

		// count the runs to prevent an endless loop on a broken state.
		$runs = 0;

		// run the import until it is completed, every run processes one chunk.
		do {
			// get the import object for immo objects.
			$import_obj = new \ConnectorForPropstack\Propstack\Imports\v1\Objects();
			if ( 'v2' === get_option( 'propstack_connector_api_version' ) ) {
				$import_obj = new \ConnectorForPropstack\Propstack\Imports\v2\Objects();
			}

			// run one chunk.
			$import_obj->run();

			++$runs;

			// bail if the import does not finish.
			if ( $runs > 10000 ) {
				Log::get_instance()->add( __( 'The scheduled import did not finish and has been stopped.', 'connector-for-propstack' ), 'error', 'import' );

				break;
			}
		} while ( $import_obj->has_load_more() );
	}
}

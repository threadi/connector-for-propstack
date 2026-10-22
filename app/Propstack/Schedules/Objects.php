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
use ConnectorForPropstack\Plugin\Setup;
use ConnectorForPropstack\Propstack\ImmoObjects;

/**
 * Object for this schedule.
 */
class Objects extends Schedules_Base {
	/**
	 * Name of the single event which continues a running import.
	 *
	 * @var string
	 */
	public const CONTINUATION_HOOK = 'cfprop_objects_continue';

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
	protected string $default_interval = 'cfprop_daily';

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

		// bail if the setup is not completed, the setup runs the first import itself.
		if ( ! Setup::get_instance()->is_completed() ) {
			return;
		}

		// bail if no API key is configured, a request would only end in an authentication error.
		if ( empty( get_option( 'propstack_connector_api_key' ) ) ) {
			return;
		}

		// run the import within the time of this request, a continuation follows if needed.
		$this->run_import();
	}

	/**
	 * Run the import of objects within a limited time.
	 *
	 * Every run of the import processes one chunk. If the import is not completed when the time
	 * is used up, a single WP-Cron event continues it in a following request. This prevents
	 * timeouts of the request, which would abort the import again and again.
	 *
	 * Could also be used by other processes which run the import without a browser, e.g. abilities.
	 *
	 * @param int $time_budget The time in seconds this request could use (0 to use the default).
	 *
	 * @return bool True if nothing is left for a continuation, false if a continuation has been scheduled.
	 */
	public function run_import( int $time_budget = 0 ): bool {
		// get the time this request could use.
		if ( $time_budget <= 0 ) {
			$time_budget = self::get_time_budget();
		}
		$deadline = microtime( true ) + $time_budget;

		// count the runs to prevent an endless loop on a broken state.
		$runs = 0;

		// run the import until it is completed or the time is used up, every run processes one chunk.
		while ( true ) {
			// get the import object for immo objects.
			$import_obj = ImmoObjects::get_instance()->get_import_object();

			// stop the run if the time is used up.
			$import_obj->set_deadline( $deadline );

			// run one chunk.
			$import_obj->run();

			++$runs;

			// bail if the import is completed or another process is working on it.
			if ( ! $import_obj->has_load_more() ) {
				return true;
			}

			// continue in a new request if the time is used up.
			if ( microtime( true ) >= $deadline ) {
				self::schedule_continuation();
				return false;
			}

			// bail if the import does not finish.
			if ( $runs > 10000 ) {
				Log::get_instance()->add( __( 'The scheduled import did not finish and has been stopped.', 'connector-for-propstack' ), 'error', 'import' );

				return true;
			}
		}
	}

	/**
	 * Return the time in seconds a single request of the scheduled import could use.
	 *
	 * @return int
	 */
	public static function get_time_budget(): int {
		// use the limit of PHP with a buffer for the end of the request, but not more than 4 minutes.
		$max_execution_time = (int) ini_get( 'max_execution_time' );
		$time_budget        = $max_execution_time > 0 ? max( 5, min( $max_execution_time - 10, 240 ) ) : 240;

		/**
		 * Filter the time in seconds a single request of the scheduled import of objects could use.
		 *
		 * If the import is not completed in this time, it is continued in a following request.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param int $time_budget The time in seconds.
		 */
		return max( 1, absint( apply_filters( 'cfprop_schedule_import_time_budget', $time_budget ) ) );
	}

	/**
	 * Schedule a single WP-Cron event which continues a running import of objects.
	 *
	 * @return void
	 */
	public static function schedule_continuation(): void {
		// bail if the continuation is already scheduled.
		if ( wp_next_scheduled( self::CONTINUATION_HOOK ) ) {
			return;
		}

		// schedule it.
		wp_schedule_single_event( time() + 10, self::CONTINUATION_HOOK );
	}

	/**
	 * Continue a running import of objects, used by the single WP-Cron event.
	 *
	 * @return void
	 */
	public static function continue_import(): void {
		// bail if no import is in progress.
		$import_data = get_option( ImmoObjects::get_instance()->get_import_object()->get_work_list_option(), array() );
		if ( empty( $import_data ) ) {
			return;
		}

		// continue the import.
		( new self() )->run_import();
	}
}

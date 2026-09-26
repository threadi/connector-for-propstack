<?php
/**
 * File for an object as a base object for each schedule.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Define the base object for schedules.
 */
class Schedules_Base {
	/**
	 * Name of this event.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Name of the option used to enable this event.
	 *
	 * @var string
	 */
	protected string $option_name = '';

	/**
	 * Name of the option used to define the interval for this event.
	 *
	 * @var string
	 */
	protected string $interval_option_name = '';

	/**
	 * Name of the log category.
	 *
	 * @var string
	 */
	protected string $log_category = 'schedule';

	/**
	 * Interval of this event.
	 *
	 * @var string
	 */
	protected string $interval;

	/**
	 * Default interval of this event.
	 *
	 * @var string
	 */
	protected string $default_interval;

	/**
	 * Arguments for the schedule-event.
	 *
	 * @var list<mixed>
	 */
	protected array $args = array();

	/**
	 * Return the name of this schedule.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the interval of this schedule.
	 *
	 * @return string
	 */
	public function get_interval(): string {
		$interval = $this->interval ?? '';
		$instance = $this;

		// migrate interval names from before the prefix "cfprop_" was introduced.
		if ( str_starts_with( $interval, 'propstack_connector_' ) ) {
			$interval = 'cfprop_' . substr( $interval, strlen( 'propstack_connector_' ) );
		}

		// use the default interval if the configured one is empty or not registered in WordPress.
		$schedules = wp_get_schedules();
		if ( ( empty( $interval ) || ! isset( $schedules[ $interval ] ) ) && ! empty( $this->get_default_interval() ) ) {
			$interval = $this->get_default_interval();
		}
		/**
		 * Filter the interval to a single schedule.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param string $interval The interval.
		 * @param Schedules_Base $instance The schedule-object.
		 */
		return apply_filters( 'cfprop_schedule_interval', $interval, $instance );
	}

	/**
	 * Set the interval for this schedule.
	 *
	 * @param string $interval The interval to set (e.g. "daily").
	 *
	 * @return void
	 */
	public function set_interval( string $interval ): void {
		$this->interval = $interval;
	}

	/**
	 * Run a single schedule.
	 *
	 * @return void
	 */
	public function run(): void {}

	/**
	 * Install this schedule if it does not exist atm.
	 *
	 * @return void
	 */
	public function install(): void {
		// bail if the schedule already exists.
		if ( wp_next_scheduled( $this->get_name(), $this->get_args() ) ) {
			return;
		}

		// start the first run after one interval, not immediately (e.g., directly after activation or during the setup).
		$interval  = $this->get_interval();
		$schedules = wp_get_schedules();
		$first_run = time() + ( isset( $schedules[ $interval ]['interval'] ) ? absint( $schedules[ $interval ]['interval'] ) : 0 );

		// create the schedule.
		$result = wp_schedule_event( $first_run, $interval, $this->get_name(), $this->get_args(), true );

		// log if the schedule could not be created, e.g., because of an unknown interval.
		if ( is_wp_error( $result ) ) {
			Log::get_instance()->add(
				sprintf(
				/* translators: %1$s will be replaced by the schedule name, %2$s by the interval, %3$s by the error message. */
					__( 'Schedule %1$s could not be created with interval %2$s: %3$s', 'connector-for-propstack' ),
					'<code>' . esc_html( $this->get_name() ) . '</code>',
					'<code>' . esc_html( $this->get_interval() ) . '</code>',
					esc_html( $result->get_error_message() )
				),
				'error',
				$this->log_category
			);
		}
	}

	/**
	 * Delete a single schedule.
	 *
	 * @return void
	 */
	public function delete(): void {
		// delete the schedule and get the result.
		wp_clear_scheduled_hook( $this->get_name(), $this->get_args() );
	}

	/**
	 * Return the event attributes.
	 *
	 * @return false|object
	 */
	public function get_event(): false|object {
		return wp_get_scheduled_event( $this->get_name(), $this->get_args() );
	}

	/**
	 * Reset this schedule.
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->delete();
		$this->install();
	}

	/**
	 * Return the arguments for the schedule-event.
	 *
	 * @return list<mixed>
	 */
	public function get_args(): array {
		return $this->args;
	}

	/**
	 * Set the arguments for the schedule-event.
	 *
	 * @param list<mixed> $args The args to set for the hook-event of this schedule.
	 *
	 * @return void
	 */
	public function set_args( array $args ): void {
		$this->args = $args;
	}

	/**
	 * Return the option name which enabled this schedule.
	 *
	 * @return string
	 */
	protected function get_option_name(): string {
		return $this->option_name;
	}

	/**
	 * Return whether the schedule has an option name configured.
	 *
	 * @return bool
	 */
	private function has_option_name(): bool {
		return ! empty( $this->get_option_name() );
	}

	/**
	 * Return whether this schedule should be enabled and active according to configuration.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$false    = false;
		$instance = $this;
		/**
		 * Filter whether to activate this schedule.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param bool $false True if this object should NOT be enabled.
		 * @param Schedules_Base $instance Actual object.
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		if ( apply_filters( 'cfprop_schedule_enabling', $false, $instance ) ) {
			return false;
		}

		// bail with true if no setting is configured.
		if ( ! $this->has_option_name() ) {
			return true;
		}

		// return the state of this schedule according to configuration.
		return 1 === absint( get_option( $this->get_option_name() ) );
	}

	/**
	 * Return the interval option name.
	 *
	 * @return string
	 */
	public function get_interval_option_name(): string {
		return $this->interval_option_name;
	}

	/**
	 * Return the interval option name.
	 *
	 * @return string
	 */
	public function get_default_interval(): string {
		return $this->default_interval ?? '';
	}

	/**
	 * Return the log category for this schedule.
	 *
	 * @return string
	 */
	public function get_log_category(): string {
		return $this->log_category;
	}
}

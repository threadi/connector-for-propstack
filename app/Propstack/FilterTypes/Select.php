<?php
/**
 * File for handling the object for the select filter type.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FilterTypes;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Templates;
use ConnectorForPropstack\Propstack\Filter_Type_Base;

/**
 * Object to handle the select filter type.
 */
class Select extends Filter_Type_Base {
	/**
	 * The internal name of the filter type.
	 *
	 * @var string
	 */
	protected string $name = 'select';

	/**
	 * The list of options for this filter.
	 *
	 * @var array<string,string>
	 */
	private array $options;

	/**
	 * Use an empty entry at first.
	 *
	 * @var bool
	 */
	private bool $use_empty_first_entry = true;

	/**
	 * Constructor, not used as this a Singleton object.
	 *
	 * @param string $name The filter name.
	 */
	public function __construct( string $name ) {
		$this->filter_name = $name;
	}

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_type_label(): string {
		return __( 'Select field', 'connector-for-propstack' );
	}

	/**
	 * Return the list of options.
	 *
	 * @return array<string,string>
	 */
	public function get_options(): array {
		// get the options.
		$options = $this->options;

		// add an empty entry at the first position if it should be used.
		if ( $this->use_empty_first_entry() ) {
			return array( '' => __( 'Choose', 'connector-for-propstack' ) ) + $options;
		}

		// return the resulting list of options.
		return $options;
	}

	/**
	 * Set the list of options.
	 *
	 * @param array<string,string> $options The options.
	 *
	 * @return void
	 */
	public function set_options( array $options ): void {
		$this->options = $options;
	}

	/**
	 * Return to use an empty first entry.
	 *
	 * @return bool
	 */
	private function use_empty_first_entry(): bool {
		return $this->use_empty_first_entry;
	}

	/**
	 * Set to use an empty first entry.
	 *
	 * @param bool $use_empty_first_entry The setting.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function set_to_use_empty_first_entry( bool $use_empty_first_entry ): void {
		$this->use_empty_first_entry = $use_empty_first_entry;
	}

	/**
	 * Return whether a given value is selected.
	 *
	 * @param string $key The key to check.
	 *
	 * @return bool
	 */
	public function is_selected( string $key ): bool {
		// get the filters.
		$filters = isset( $_GET['filter'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_GET['filter'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended - Read-only public endpoint, no nonce required.

		// if no value is set, return the min value.
		if ( ! isset( $filters[ $this->get_filter_name() ] ) ) {
			return false;
		}

		// return the comparison.
		return $filters[ $this->get_filter_name() ] === $key; // @phpstan-ignore deadCode.unreachable
	}

	/**
	 * Render this filter type.
	 *
	 * @return string
	 */
	public function render(): string {
		// use this object in the template.
		$interface = $this;

		// collect the output.
		ob_start();

		// embed the listing content.
		include Templates::get_instance()->get_template( 'parts/filter-types/select.php' );

		// get the content.
		$content = ob_get_clean();

		// return the content.
		if ( ! $content ) {
			return '';
		}
		return $content;
	}
}

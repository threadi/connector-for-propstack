<?php
/**
 * File for handling the object for the input filter type.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FilterTypes;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Templates;
use ConnectorForPropstack\Propstack\Filters;
use ConnectorForPropstack\Propstack\Filter_Type_Base;

/**
 * Object to handle the input filter type.
 */
class Input extends Filter_Type_Base {
	/**
	 * The internal name of the filter type.
	 *
	 * @var string
	 */
	protected string $name = 'input';

	/**
	 * The placeholder.
	 *
	 * @var string
	 */
	private string $placeholder = '';

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
		return __( 'Input field', 'connector-for-propstack' );
	}

	/**
	 * Return the placeholder.
	 *
	 * @return string
	 */
	public function get_placeholder(): string {
		return $this->placeholder;
	}

	/**
	 * Set the placeholder.
	 *
	 * @param string $placeholder The placeholder.
	 *
	 * @return void
	 */
	public function set_placeholder( string $placeholder ): void {
		$this->placeholder = $placeholder;
	}

	/**
	 * Return the searched string.
	 *
	 * @return string
	 */
	public function get_value(): string {
		// check for nonce even though this is just a filter and nothing is actually being written here, and the filter is public.
		if ( isset( $_GET['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'cfprop-verify' ) ) {
			exit;
		}

		// get the filters.
		$filters = isset( $_GET['filter'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_GET['filter'] ) ) : array();

		// get the value from the request.
		return isset( $filters[ $this->get_filter_name() ] ) ? $filters[ $this->get_filter_name() ] : '';
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
		include Templates::get_instance()->get_template( 'parts/filter-types/input.php' );

		// get the content.
		$content = ob_get_clean();

		// return the content.
		if ( ! $content ) {
			return '';
		}
		return $content;
	}
}

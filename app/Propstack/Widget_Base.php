<?php
/**
 * File to handle widget extensions for immo objects.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;

/**
 * Object, which handles the base functions for widget extensions.
 */
class Widget_Base {
	/**
	 * The widget name.
	 *
	 * @var string
	 */
	protected string $name = '';
	/**
	 * Path to the Block object.
	 *
	 * @var string
	 */
	protected string $gutenberg = '';

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Widget_Base
	 */
	private static ?Widget_Base $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Widget_Base {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		// add a shortcode for this widget.
		add_shortcode( 'cfprop_' . $this->get_name(), array( $this, 'get_shortcode' ) );
	}

	/**
	 * Return the name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the field of this widget.
	 *
	 * @param string        $filter The requested filter.
	 * @param array<string> $attributes The settings for this field.
	 *
	 * @return void
	 */
	public function get_field( string $filter, array $attributes ): void {}

	/**
	 * Return the rendered widget.
	 *
	 * @param array<string,mixed> $attributes Attributes to configure the rendering.
	 *
	 * @return string
	 */
	public function render( array $attributes ): string {
		if ( empty( $attributes ) ) {
			return '';
		}
		return '';
	}

	/**
	 * Return the shortcode for the widget content.
	 *
	 * @param array<string,mixed> $attributes List of attributes.
	 *
	 * @return string
	 */
	public function get_shortcode( array $attributes ): string {
		return wp_kses_post( $this->render( $attributes ) );
	}

	/**
	 * Return the list of params this widget requires.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_params(): array {
		return array();
	}

	/**
	 * Return the object as a PHP object by request.
	 *
	 * Hints:
	 * - Bug https://github.com/WordPress/gutenberg/issues/40714 prevents clean usage in Query Loop (backend bad, frontend ok)
	 *
	 * @return ImmoObject|false
	 */
	public function get_object_by_request(): ImmoObject|false {
		// get the immo-objects object.
		$immo_objects = ImmoObjects::get_instance();

		// the return value.
		$immo_object = false;

		// return the immo object as an object if the called ID is valid.
		$post_id = get_the_ID();
		if ( $post_id > 0 ) {
			$immo_object = $immo_objects->get_object( $post_id );
		}

		// Fallback: get a random immo object, only during the AJAX request (e.g., in Gutenberg).
		if ( Helper::is_rest_request() ) {
			$immo_objects_array = $immo_objects->get_objects( array( 'posts_per_page' => 1 ) );
			if ( ! empty( $immo_objects_array ) ) {
				$immo_object = $immo_objects_array[0];
			}
		}

		// return the object.
		return $immo_object;
	}

	/**
	 * Return a shortcode description.
	 *
	 * @return string
	 */
	public function get_shortcode_description(): string {
		// concat the returning text.
		$text = '<code data-copied-label="' . esc_attr__( 'copied', 'connector-for-propstack' ) . '" title="' . esc_attr__( 'Click to copy this code in your clipboard', 'connector-for-propstack' ) . '">[propstack_connector_' . $this->get_name() . ']</code><br>';

		// get the params.
		$params = $this->get_params();

		// add them if they are filled.
		if ( ! empty( $params ) ) {
			$text .= '<i>' . __( 'Attributes:', 'connector-for-propstack' ) . '</i><ul>';
			foreach ( $params as $name => $param ) {
				$text .= '<li><code data-copied-label="' . esc_attr__( 'copied', 'connector-for-propstack' ) . '" title="' . esc_attr__( 'Click to copy this code in your clipboard', 'connector-for-propstack' ) . '">' . $name . '</code> ' . $param['label'] . ( $param['required'] ? ' <em>' . __( 'required', 'connector-for-propstack' ) . '</em>' : '' ) . '</li>';
			}
			$text .= '</ul>';
			$text .= '<i>' . __( 'Example:', 'connector-for-propstack' ) . '</i><br>' . $this->get_shortcode_example();
		} else {
			$text .= '<i>' . __( 'Does not have any attributes.', 'connector-for-propstack' ) . '</i>';
		}

		// return the resulting text.
		return '<div>' . $text . '</div>';
	}

	/**
	 * Return a shortcode example.
	 *
	 * @return string
	 */
	private function get_shortcode_example(): string {
		// collect the params here.
		$params = '';

		// get all required params.
		foreach ( $this->get_params() as $name => $param ) {
			// bail if it is not required.
			if ( empty( $param['required'] ) ) {
				continue;
			}

			// bail if no example value is set.
			if ( empty( $param['example_value'] ) ) {
				continue;
			}

			// add this to the list with the configured example value.
			$params .= ' ' . $name . '="' . $param['example_value'] . '"';
		}

		// return the resulting example.
		return '<code data-copied-label="' . esc_attr__( 'copied', 'connector-for-propstack' ) . '" title="' . esc_attr__( 'Click to copy this code in your clipboard', 'connector-for-propstack' ) . '">[propstack_connector_' . $this->get_name() . $params . ']</code>';
	}

	/**
	 * Enqueue the CSS for this widget.
	 *
	 * @return void
	 */
	public function enqueue_css(): void {}
}

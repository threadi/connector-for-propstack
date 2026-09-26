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
use ConnectorForPropstack\Plugin\Languages;

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
	 * WordPress passes an empty string if the shortcode is used without any attributes.
	 *
	 * @param array<string,mixed>|string $attributes List of attributes.
	 *
	 * @return string
	 */
	public function get_shortcode( array|string $attributes ): string {
		// WordPress uses an empty string for shortcodes without attributes.
		$attributes = is_array( $attributes ) ? $attributes : array();

		// remove attributes which are only allowed internally (e.g., via blocks) and not via shortcodes.
		unset( $attributes['styles'], $attributes['object'] );

		return wp_kses_post( $this->render( $attributes ) );
	}

	/**
	 * Return the immo object to use for rendering.
	 *
	 * Uses the given 'object' attribute if it is an ImmoObject, the 'object_id' attribute if it is set
	 * (and $use_object_id is true) or the object of the actual request.
	 *
	 * @param array<string,mixed> $attributes    The attributes.
	 * @param bool                $use_object_id Whether to use the 'object_id' attribute.
	 *
	 * @return ImmoObject|false
	 */
	protected function get_immo_object( array $attributes, bool $use_object_id = false ): ImmoObject|false {
		// use the given object, if it is valid.
		if ( isset( $attributes['object'] ) && $attributes['object'] instanceof ImmoObject ) {
			return $attributes['object'];
		}

		// if 'object_id' is given, get the object for it.
		if ( $use_object_id && ! empty( $attributes['object_id'] ) && is_scalar( $attributes['object_id'] ) ) {
			$immo_object = ImmoObjects::get_instance()->get_object_by_object_id( (string) $attributes['object_id'], Languages::get_instance()->get_current_lang() );
			return $immo_object instanceof ImmoObject ? $immo_object : false;
		}

		// get the object for this request.
		$immo_object = $this->get_object_by_request();

		// bail if no object could be found.
		if ( ! $immo_object instanceof ImmoObject ) {
			return false;
		}

		// bail if requested post-type is not ours.
		if ( get_post_type( $immo_object->get_id() ) !== \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() ) {
			return false;
		}

		// return the object.
		return $immo_object;
	}

	/**
	 * Return a list attribute as array.
	 *
	 * Shortcodes provide lists as comma-separated strings, blocks as arrays.
	 *
	 * @param mixed $value The value.
	 *
	 * @return array<int,string>
	 */
	protected function get_list_attribute( mixed $value ): array {
		// convert a comma-separated string to an array.
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}

		// bail if value is not an array.
		if ( ! is_array( $value ) ) {
			return array();
		}

		// return the cleaned list.
		return array_values( array_filter( array_map( 'trim', array_map( 'strval', array_filter( $value, 'is_scalar' ) ) ) ) );
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

		// Fallback: get the newest immo object, only during the REST request of editors (e.g., preview in Gutenberg).
		if ( Helper::is_rest_request() && current_user_can( 'edit_posts' ) ) {
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
		$text = '<code data-copied-label="' . esc_attr__( 'copied', 'connector-for-propstack' ) . '" title="' . esc_attr__( 'Click to copy this code in your clipboard', 'connector-for-propstack' ) . '">[cfprop_' . $this->get_name() . ']</code><br>';

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
		return '<code data-copied-label="' . esc_attr__( 'copied', 'connector-for-propstack' ) . '" title="' . esc_attr__( 'Click to copy this code in your clipboard', 'connector-for-propstack' ) . '">[cfprop_' . $this->get_name() . $params . ']</code>';
	}

	/**
	 * Enqueue the CSS for this widget.
	 *
	 * @return void
	 */
	public function enqueue_css(): void {}
}

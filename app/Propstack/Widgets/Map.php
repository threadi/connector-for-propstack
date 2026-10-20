<?php
/**
 * File to handle the map widget.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Widgets;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Languages;
use ConnectorForPropstack\Plugin\Templates;
use ConnectorForPropstack\Propstack\ImmoObject;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Maps;
use ConnectorForPropstack\Propstack\Sortings;
use ConnectorForPropstack\Propstack\Widget_Base;

/**
 * Object to handle the map widget.
 *
 * Shows the location of the actual object (or of all objects of the list, if supported) on a map
 * of the map service chosen in the settings. The settings of this widget are the same in each
 * page builder and are defined here.
 */
class Map extends Widget_Base {
	/**
	 * The internal name for this object.
	 *
	 * @var string
	 */
	protected string $name = 'widget_map';

	/**
	 * Name if the setting tab where the setting field is visible.
	 *
	 * @var string
	 */
	protected string $setting_tab = '';

	/**
	 * Path to the Block object.
	 *
	 * @var string
	 */
	protected string $gutenberg = '\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Map';

	/**
	 * Instance of this object.
	 *
	 * @var ?Map
	 */
	private static ?Map $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Map {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return the sources of the locations on the map.
	 *
	 * @return array<string,string>
	 */
	public function get_sources(): array {
		$sources = array(
			'object' => __( 'Actual object', 'connector-for-propstack' ),
		);

		/**
		 * Filter the sources of the locations, which could be shown on the map widget.
		 *
		 * Connector for Propstack Pro adds "list" for all objects of the list (with the active filters).
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,string> $sources List of sources (internal name => label).
		 */
		return apply_filters( 'cfprop_widget_map_sources', $sources );
	}

	/**
	 * Return the settings of this widget for its content.
	 *
	 * Each page builder uses this list to provide the same settings. Possible types are:
	 * text, number, toggle (value "1" or "0") and select.
	 * A setting with "condition" is only relevant, if the other setting has the given value.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_content_settings(): array {
		$settings = array(
			'source'    => array(
				'label'       => __( 'Show', 'connector-for-propstack' ),
				'description' => __( 'Which objects are shown on the map.', 'connector-for-propstack' ),
				'type'        => 'select',
				'options'     => $this->get_sources(),
				'default'     => 'object',
			),
			'object_id' => array(
				'label'       => __( 'Object ID', 'connector-for-propstack' ),
				'description' => __( 'Optional: the Propstack ID of the object to show. Only needed outside of the template for the detail view of objects.', 'connector-for-propstack' ),
				'type'        => 'text',
				'default'     => '',
				'condition'   => array( 'source' => 'object' ),
			),
			'zoom'      => array(
				'label'       => __( 'Zoom level', 'connector-for-propstack' ),
				/* translators: %1$d will be replaced by a number. */
				'description' => sprintf( __( 'From 1 (world) to 19 (street). Leave empty to use the zoom level from the settings (actual: %1$d). Maps with several objects show all of them.', 'connector-for-propstack' ), Maps::get_instance()->get_zoom() ),
				'type'        => 'number',
				'default'     => '',
				'min'         => 1,
				'max'         => 19,
			),
			'popup'     => array(
				'label'       => __( 'Show popup with link', 'connector-for-propstack' ),
				'description' => __( 'Shows the title and a link to the object if the visitor clicks on its marker.', 'connector-for-propstack' ),
				'type'        => 'toggle',
				'default'     => '1',
				'condition'   => array( 'source' => 'list' ),
			),
		);

		// remove the popup, if no list is supported.
		if ( ! array_key_exists( 'list', $this->get_sources() ) ) {
			unset( $settings['popup'] );
		}

		/**
		 * Filter the content settings of the map widget, which are used in each page builder.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,array<string,mixed>> $settings List of settings.
		 */
		return apply_filters( 'cfprop_widget_map_content_settings', $settings );
	}

	/**
	 * Return the settings of this widget for its styles.
	 *
	 * Each page builder uses this list to provide the same settings. Empty values are not used.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_style_settings(): array {
		return array(
			'height' => array(
				'label'       => __( 'Height', 'connector-for-propstack' ),
				/* translators: %1$s will be replaced by a height, e.g. "400px". */
				'description' => sprintf( __( 'E.g. 400px or 50vh. Leave empty to use the height from the settings (actual: %1$s).', 'connector-for-propstack' ), Maps::get_instance()->get_height() ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => Maps::get_instance()->get_height(),
			),
		);
	}

	/**
	 * Return the default values of all settings of this widget.
	 *
	 * @return array<string,string>
	 */
	public function get_default_values(): array {
		$defaults = array();
		foreach ( array_merge( $this->get_content_settings(), $this->get_style_settings() ) as $name => $setting ) {
			$defaults[ $name ] = isset( $setting['default'] ) && is_scalar( $setting['default'] ) ? (string) $setting['default'] : '';
		}
		return $defaults;
	}

	/**
	 * Return the list of params this widget supports (for the shortcode description).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_params(): array {
		$params = array();
		foreach ( array_merge( $this->get_content_settings(), $this->get_style_settings() ) as $name => $setting ) {
			$params[ $name ] = array(
				'label'    => $setting['label'],
				'required' => false,
			);
		}
		return $params;
	}

	/**
	 * Return the value of an attribute as string.
	 *
	 * @param array<string,mixed> $attributes The attributes.
	 * @param string              $name       The name of the attribute.
	 *
	 * @return string
	 */
	private function get_attribute( array $attributes, string $name ): string {
		if ( isset( $attributes[ $name ] ) && is_scalar( $attributes[ $name ] ) ) {
			return trim( (string) $attributes[ $name ] );
		}
		$defaults = $this->get_default_values();
		return $defaults[ $name ] ?? '';
	}

	/**
	 * Return whether the given value of a toggle is enabled.
	 *
	 * @param mixed $value The value.
	 *
	 * @return bool
	 */
	public function is_enabled( mixed $value ): bool {
		return ! in_array( $value, array( false, 0, '0', '', 'no', 'false', 'off' ), true );
	}

	/**
	 * Return the allowed HTML for the output of this widget.
	 *
	 * @return array<string,mixed>
	 */
	public function get_allowed_html(): array {
		$allowed_html = wp_kses_allowed_html( 'post' );

		// allow the hint for visitors without JavaScript.
		$allowed_html['noscript'] = array();

		// allow the configuration of the map on its container.
		if ( isset( $allowed_html['div'] ) && is_array( $allowed_html['div'] ) ) {
			$allowed_html['div']['data-cfprop-map'] = true;
			$allowed_html['div']['style']           = true;
		}

		return $allowed_html;
	}

	/**
	 * Return the source of the locations for the given attributes.
	 *
	 * @param array<string,mixed> $attributes The attributes.
	 *
	 * @return string
	 */
	public function get_source( array $attributes ): string {
		$source = $this->get_attribute( $attributes, 'source' );
		return array_key_exists( $source, $this->get_sources() ) ? $source : 'object';
	}

	/**
	 * Return the rendered widget.
	 *
	 * @param array<string,mixed> $attributes Attributes to configure the rendering.
	 *
	 * @return string
	 */
	public function render( array $attributes ): string {
		/**
		 * Filter the output of the map widget (also if it is empty).
		 *
		 * Used by Connector for Propstack Pro, e.g. to update a map with the objects of the list via AJAX.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param string              $content    The output.
		 * @param array<string,mixed> $attributes The attributes of the map widget.
		 * @param string              $source     The source of the locations ("object" or "list").
		 */
		return (string) apply_filters( 'cfprop_widget_map_output', $this->get_output( $attributes ), $attributes, $this->get_source( $attributes ) );
	}

	/**
	 * Return the output of the widget.
	 *
	 * @param array<string,mixed> $attributes Attributes to configure the rendering.
	 *
	 * @return string
	 */
	private function get_output( array $attributes ): string {
		// get the maps object.
		$maps = Maps::get_instance();

		// get the source.
		$source = $this->get_source( $attributes );

		// get the points.
		$points = 'list' === $source ? $this->get_points_of_list( $attributes ) : $this->get_points_of_object( $attributes );

		/**
		 * Filter the points (locations of objects), which are shown on the map widget.
		 *
		 * Never add the exact location of objects with hidden address.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<int,array<string,mixed>> $points     List of points with lat, lng, approximate, title, url and image.
		 * @param array<string,mixed>            $attributes The attributes of the widget.
		 */
		$points = apply_filters( 'cfprop_widget_map_points', $points, $attributes );

		// bail if no points are available.
		if ( empty( $points ) ) {
			if ( $this->show_placeholders() ) {
				return '<p class="cfprop-map-empty"><em>' . esc_html__( 'No location is available for the map.', 'connector-for-propstack' ) . '</em></p>';
			}
			return 'list' !== $source ? $this->get_admin_hint( $attributes ) : '';
		}

		// bail if the map service could not be used.
		if ( ! $maps->is_usable() ) {
			if ( $this->show_placeholders() || current_user_can( 'manage_options' ) ) {
				return '<p class="cfprop-map-empty"><em>' . esc_html__( 'Enter the API key for Google Maps in the settings of Connector for Propstack to show the map.', 'connector-for-propstack' ) . '</em></p>';
			}
			return '';
		}

		// get the zoom level.
		$zoom = $this->get_attribute( $attributes, 'zoom' );
		$zoom = '' !== $zoom ? $maps->sanitize_zoom( $zoom ) : $maps->get_zoom();

		// get the height.
		$height = $maps->sanitize_height( $this->get_attribute( $attributes, 'height' ) );
		if ( '' === $height ) {
			$height = $maps->get_height();
		}

		// collect the settings for the template.
		$attributes['map_points']   = $points;
		$attributes['map_height']   = $height;
		$attributes['map_provider'] = $maps->get_provider();
		$attributes['map_preview']  = $this->show_placeholders();
		$attributes['map_config']   = array(
			'points' => array_values( $points ),
			'zoom'   => $zoom,
			'popup'  => 'list' === $source && $this->is_enabled( $this->get_attribute( $attributes, 'popup' ) ),
			'radius' => $maps->get_area_radius(),
		);
		$attributes['classes']      = trim( 'cfprop-map-wrapper ' . ( isset( $attributes['classes'] ) && is_string( $attributes['classes'] ) ? $attributes['classes'] : '' ) );

		/**
		 * Filter the attributes for the map widget.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,mixed> $attributes The attributes for the map widget.
		 */
		$attributes = apply_filters( 'cfprop_widget_map_attributes', $attributes );

		// enqueue the scripts and styles (not needed for the preview in editors).
		if ( ! $attributes['map_preview'] ) {
			$maps->enqueue_assets( count( $points ) > 1 );
		}

		/** This action is documented in app/Propstack/Widgets/Archive.php */
		do_action( 'cfprop_get_template_before', $attributes );

		// collect the output.
		ob_start();
		include Templates::get_instance()->get_template( 'parts/part-map.php' );
		$content = ob_get_clean();

		// return the content.
		if ( ! $content ) {
			return '';
		}
		return $content;
	}

	/**
	 * Return the hint for administrators in frontend, why the map of an object is not shown.
	 *
	 * @param array<string,mixed> $attributes The attributes.
	 *
	 * @return string
	 */
	private function get_admin_hint( array $attributes ): string {
		// bail if the user is not allowed to manage the settings.
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		/**
		 * Filter whether administrators see a hint in frontend, why the map of an object is not shown.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param bool                $show       True to show the hint.
		 * @param array<string,mixed> $attributes The attributes of the widget.
		 */
		if ( ! apply_filters( 'cfprop_widget_map_show_admin_hint', true, $attributes ) ) {
			return '';
		}

		// get the object.
		$immo_object = $this->get_immo_object( $attributes, true );

		// bail if no object could be found.
		if ( ! $immo_object instanceof ImmoObject ) {
			return '';
		}

		// get the reason.
		$reason = Maps::get_instance()->get_missing_location_reason( $immo_object );

		// bail if no reason is known.
		if ( '' === $reason ) {
			return '';
		}

		// return the hint.
		return '<p class="cfprop-map-empty cfprop-map-admin-hint"><em>' . esc_html( $reason ) . ' ' . esc_html__( 'This hint is only visible to administrators.', 'connector-for-propstack' ) . '</em></p>';
	}

	/**
	 * Return the point of the actual object (or the object with the given object ID).
	 *
	 * @param array<string,mixed> $attributes The attributes.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_points_of_object( array $attributes ): array {
		// get the object.
		$immo_object = $this->get_immo_object( $attributes, true );

		// bail if no object could be found.
		if ( ! $immo_object instanceof ImmoObject ) {
			return array();
		}

		// get its point.
		$point = $this->get_point( $immo_object );

		// return the list.
		return false !== $point ? array( $point ) : array();
	}

	/**
	 * Return the points of all objects of the list, with the active filters.
	 *
	 * @param array<string,mixed> $attributes The attributes.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_points_of_list( array $attributes ): array {
		$max = 500;
		/**
		 * Filter the maximum number of objects on the map widget with the objects of the list.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param int $max The maximum number of objects.
		 */
		$max = max( 1, absint( apply_filters( 'cfprop_widget_map_max_objects', $max ) ) );

		// get the query params of the list with the active filters (like the archive widget).
		$query_params = array(
			'posts_per_page' => $max,
		);

		/** This filter is documented in app/Propstack/Widgets/Archive.php */
		$query_params = apply_filters( 'cfprop_archive_query_params', $query_params, $attributes );

		// the order is not relevant for the map.
		unset( $query_params[ Sortings::QUERY_VAR ], $query_params['paged'] );
		$query_params['posts_per_page'] = $max;
		$query_params['no_found_rows']  = true;

		// get the objects.
		$points = array();
		foreach ( (array) ImmoObjects::get_instance()->get_objects_query( $query_params )->posts as $post_id ) {
			if ( ! is_numeric( $post_id ) ) {
				continue;
			}

			// get the object.
			$immo_object = ImmoObjects::get_instance()->get_object( absint( $post_id ) );
			$immo_object->set_lang( Languages::get_instance()->get_current_lang() );

			// get its point.
			$point = $this->get_point( $immo_object );
			if ( false === $point ) {
				continue;
			}

			// add it to the list.
			$points[] = $point;
		}

		// return the list.
		return $points;
	}

	/**
	 * Return the point of the given object for the map.
	 *
	 * @param ImmoObject $immo_object The object.
	 *
	 * @return array<string,mixed>|false
	 */
	private function get_point( ImmoObject $immo_object ): array|false {
		// get the location.
		$location = Maps::get_instance()->get_location( $immo_object );

		// bail if no location is available.
		if ( false === $location ) {
			return false;
		}

		// get the image.
		$image = get_the_post_thumbnail_url( $immo_object->get_id(), 'medium' );

		// return the point.
		return array(
			'lat'         => $location['lat'],
			'lng'         => $location['lng'],
			'approximate' => $location['approximate'],
			'title'       => wp_strip_all_tags( $immo_object->get_title() ),
			'url'         => $immo_object->get_link(),
			'image'       => is_string( $image ) ? $image : '',
		);
	}
}

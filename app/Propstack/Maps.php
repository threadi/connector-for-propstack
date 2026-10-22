<?php
/**
 * File for handling the maps, which show the location of objects.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Fields\Number;
use easySettingsForWordPress\Fields\Select;
use easySettingsForWordPress\Fields\Text;
use easySettingsForWordPress\Page;
use easySettingsForWordPress\Setting;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Settings;

/**
 * Object to handle the maps, which show the location of objects.
 *
 * The map service (OpenStreetMap or Google Maps) is chosen in the settings. Maps are loaded
 * only after the consent of the visitor by default, as the map service receives the IP address
 * of the visitor. The exact location of objects with hidden address is never sent to the browser.
 */
class Maps {
	/**
	 * The name of the provider OpenStreetMap.
	 */
	public const PROVIDER_OSM = 'openstreetmap';

	/**
	 * The name of the provider Google Maps.
	 */
	public const PROVIDER_GOOGLE = 'google';

	/**
	 * The default URL for the tiles of OpenStreetMap.
	 */
	public const DEFAULT_TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Maps
	 */
	private static ?Maps $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Maps {
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
		add_action( 'init', array( $this, 'add_settings' ), 16 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'register_assets' ) );
	}

	/**
	 * Add the settings for the maps.
	 *
	 * @return void
	 */
	public function add_settings(): void {
		// get the settings object.
		$settings_obj = Settings::get_instance()->get_settings_obj();

		// get the settings page.
		$settings_page = $settings_obj->get_page( 'connector-for-propstack' );

		// bail if the page could not be found.
		if ( ! $settings_page instanceof Page ) {
			return;
		}

		// add a tab on this page.
		$tab = $settings_page->add_tab( 'propstack_connector_maps', 44 );
		$tab->set_title( __( 'Maps', 'connector-for-propstack' ) );

		// add the section for the map service.
		$service_section = $tab->add_section( 'propstack_connector_maps_service', 10 );
		$service_section->set_title( __( 'Map service', 'connector-for-propstack' ) );
		$service_section->set_callback( array( $this, 'show_service_hint' ) );

		// add setting.
		$provider_setting = $settings_obj->add_setting( 'propstack_connector_map_provider' );
		$provider_setting->set_type( 'string' );
		$provider_setting->set_default( self::PROVIDER_OSM );
		$provider_setting->set_section( $service_section );
		$field = new Select( $settings_obj );
		$field->set_title( __( 'Map service', 'connector-for-propstack' ) );
		$field->set_description( __( 'Choose the service which provides the maps on your website.', 'connector-for-propstack' ) );
		$field->set_options( $this->get_providers() );
		$field->set_sanitize_callback( array( $this, 'sanitize_provider' ) );
		$provider_setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_map_tile_url' );
		$setting->set_type( 'string' );
		$setting->set_default( self::DEFAULT_TILE_URL );
		$setting->set_section( $service_section );
		$field = new Text( $settings_obj );
		$field->set_title( __( 'URL of the map tiles', 'connector-for-propstack' ) );
		$field->set_description( __( 'The URL of the tile server with the placeholders {z}, {x} and {y}. The tile servers of OpenStreetMap are not meant for websites with many visitors. Use a commercial provider or your own tile server in this case.', 'connector-for-propstack' ) );
		$field->set_placeholder( self::DEFAULT_TILE_URL );
		$field->set_sanitize_callback( array( $this, 'sanitize_tile_url' ) );
		$field->add_depend( $provider_setting, self::PROVIDER_OSM );
		$setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_map_attribution' );
		$setting->set_type( 'string' );
		$setting->set_default( $this->get_default_attribution() );
		$setting->set_section( $service_section );
		$field = new Text( $settings_obj );
		$field->set_title( __( 'Copyright note of the map', 'connector-for-propstack' ) );
		$field->set_description( __( 'Shown in the corner of the map. The provider of the tiles defines which note is required. Links are allowed.', 'connector-for-propstack' ) );
		$field->set_sanitize_callback( array( $this, 'sanitize_attribution' ) );
		$field->add_depend( $provider_setting, self::PROVIDER_OSM );
		$setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_map_google_api_key' );
		$setting->set_type( 'string' );
		$setting->set_default( '' );
		$setting->set_section( $service_section );
		$setting->prevent_export( true );
		$field = new Text( $settings_obj );
		$field->set_title( __( 'API key for Google Maps', 'connector-for-propstack' ) );
		/* translators: %1$s will be replaced by the URL of the Google Cloud Console. */
		$field->set_description( sprintf( __( 'Create a key in the <a href="%1$s" target="_blank">Google Cloud Console (opens new window)</a> with the "Maps JavaScript API" enabled and restrict it to the domain of your website. The key is visible in the source code of your website, which is normal for Google Maps.', 'connector-for-propstack' ), 'https://console.cloud.google.com/google/maps-apis/credentials' ) );
		$field->set_sanitize_callback( array( $this, 'sanitize_key' ) );
		$field->add_depend( $provider_setting, self::PROVIDER_GOOGLE );
		$setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_map_google_map_id' );
		$setting->set_type( 'string' );
		$setting->set_default( '' );
		$setting->set_section( $service_section );
		$field = new Text( $settings_obj );
		$field->set_title( __( 'Map ID for Google Maps', 'connector-for-propstack' ) );
		$field->set_description( __( 'Optional: the ID of a map style from the Google Cloud Console. With a map ID, the current markers of Google Maps are used.', 'connector-for-propstack' ) );
		$field->set_sanitize_callback( array( $this, 'sanitize_key' ) );
		$field->add_depend( $provider_setting, self::PROVIDER_GOOGLE );
		$setting->set_field( $field );

		// add the section for the privacy.
		$privacy_section = $tab->add_section( 'propstack_connector_maps_privacy', 20 );
		$privacy_section->set_title( __( 'Privacy', 'connector-for-propstack' ) );

		// add setting.
		$consent_setting = $settings_obj->add_setting( 'propstack_connector_map_consent' );
		$consent_setting->set_default( 1 );
		$consent_setting->set_section( $privacy_section );
		$field = new Checkbox( $settings_obj );
		$field->set_title( __( 'Load maps only after consent', 'connector-for-propstack' ) );
		$field->set_description( __( 'If enabled, a placeholder is shown instead of the map. The map is loaded only after the visitor clicked on it, as the map service receives the IP address of the visitor. Visitors can also allow all maps at once, this is saved in their browser. Disable it only if your consent tool blocks the map service until the visitor agreed.', 'connector-for-propstack' ) );
		$consent_setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_map_consent_text' );
		$setting->set_type( 'string' );
		$setting->set_default( '' );
		$setting->set_section( $privacy_section );
		$field = new Text( $settings_obj );
		$field->set_title( __( 'Text of the placeholder', 'connector-for-propstack' ) );
		$field->set_description( __( 'Leave empty to use the default text, which names the chosen map service.', 'connector-for-propstack' ) );
		$field->set_placeholder( $this->get_default_consent_text() );
		$field->set_sanitize_callback( array( $this, 'sanitize_text' ) );
		$field->add_depend( $consent_setting, 1 );
		$setting->set_field( $field );

		// add the section for the display.
		$display_section = $tab->add_section( 'propstack_connector_maps_display', 30 );
		$display_section->set_title( __( 'Display', 'connector-for-propstack' ) );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_map_zoom' );
		$setting->set_type( 'integer' );
		$setting->set_default( 15 );
		$setting->set_section( $display_section );
		$field = new Number( $settings_obj );
		$field->set_title( __( 'Zoom level', 'connector-for-propstack' ) );
		$field->set_description( __( 'The zoom level of maps with a single object, from 1 (world) to 19 (street). Maps with several objects show all of them. Each map widget could use its own zoom level.', 'connector-for-propstack' ) );
		$field->set_min( 1 );
		$field->set_max( 19 );
		$field->set_sanitize_callback( array( $this, 'sanitize_zoom' ) );
		$setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_map_height' );
		$setting->set_type( 'string' );
		$setting->set_default( '400px' );
		$setting->set_section( $display_section );
		$field = new Text( $settings_obj );
		$field->set_title( __( 'Height of the map', 'connector-for-propstack' ) );
		$field->set_description( __( 'E.g. 400px or 50vh. Each map widget could use its own height.', 'connector-for-propstack' ) );
		$field->set_placeholder( '400px' );
		$field->set_sanitize_callback( array( $this, 'sanitize_height' ) );
		$setting->set_field( $field );

		// add setting.
		$hidden_setting = $settings_obj->add_setting( 'propstack_connector_map_hidden_address' );
		$hidden_setting->set_type( 'string' );
		$hidden_setting->set_default( 'area' );
		$hidden_setting->set_section( $display_section );
		$field = new Select( $settings_obj );
		$field->set_title( __( 'Objects with hidden address', 'connector-for-propstack' ) );
		$field->set_description( __( 'Objects for which the address should be hidden in Propstack. Their exact location is never sent to the browser of the visitor. For the area, the location is rounded to about 1 km.', 'connector-for-propstack' ) );
		$field->set_options( $this->get_hidden_address_modes() );
		$field->set_sanitize_callback( array( $this, 'sanitize_hidden_address_mode' ) );
		$hidden_setting->set_field( $field );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_map_area_radius' );
		$setting->set_type( 'integer' );
		$setting->set_default( 1000 );
		$setting->set_section( $display_section );
		$field = new Number( $settings_obj );
		$field->set_title( __( 'Radius of the area in meters', 'connector-for-propstack' ) );
		$field->set_description( __( 'The radius of the circle, which shows the area of an object with hidden address. Use at least 500 meters, as the location is rounded.', 'connector-for-propstack' ) );
		$field->set_min( 500 );
		$field->set_max( 20000 );
		$field->set_sanitize_callback( array( $this, 'sanitize_radius' ) );
		$field->add_depend( $hidden_setting, 'area' );
		$setting->set_field( $field );

		// save the defaults.
		$this->add_default_options(
			array(
				'propstack_connector_map_provider'       => self::PROVIDER_OSM,
				'propstack_connector_map_tile_url'       => self::DEFAULT_TILE_URL,
				'propstack_connector_map_attribution'    => $this->get_default_attribution(),
				'propstack_connector_map_consent'        => 1,
				'propstack_connector_map_zoom'           => 15,
				'propstack_connector_map_height'         => '400px',
				'propstack_connector_map_hidden_address' => 'area',
				'propstack_connector_map_area_radius'    => 1000,
			)
		);
	}

	/**
	 * Save the default values of our settings, if they do not exist yet (e.g. after an update).
	 *
	 * The settings page shows the stored values, so the defaults must be stored.
	 *
	 * @param array<string,mixed> $defaults List of settings with their default values.
	 *
	 * @return void
	 */
	private function add_default_options( array $defaults ): void {
		foreach ( $defaults as $name => $value ) {
			if ( false === get_option( $name, false ) ) {
				add_option( $name, $value );
			}
		}
	}

	/**
	 * Show a hint above the settings of the map service.
	 *
	 * @return void
	 */
	public function show_service_hint(): void {
		echo '<p>' . esc_html__( 'Use the widget or the block "Map" to show the location of an object on its detail page. Coordinates of the objects are delivered by Propstack, an additional service to find the location of an address is not used.', 'connector-for-propstack' ) . '</p>';
	}

	/**
	 * Return the list of supported map services.
	 *
	 * @return array<string,string>
	 */
	public function get_providers(): array {
		$providers = array(
			self::PROVIDER_OSM    => __( 'OpenStreetMap (via Leaflet)', 'connector-for-propstack' ),
			self::PROVIDER_GOOGLE => __( 'Google Maps', 'connector-for-propstack' ),
		);

		/**
		 * Filter the list of supported map services.
		 *
		 * A new map service needs its own handling in the JavaScript of the maps (see js/map.js).
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,string> $providers List of map services (internal name => label).
		 */
		return apply_filters( 'cfprop_map_providers', $providers );
	}

	/**
	 * Return the modes for objects with hidden address.
	 *
	 * @return array<string,string>
	 */
	public function get_hidden_address_modes(): array {
		return array(
			'area' => __( 'Show the approximate area as circle', 'connector-for-propstack' ),
			'hide' => __( 'Do not show them on maps', 'connector-for-propstack' ),
		);
	}

	/**
	 * Return the chosen map service.
	 *
	 * @return string
	 */
	public function get_provider(): string {
		$provider = get_option( 'propstack_connector_map_provider', self::PROVIDER_OSM );
		return is_string( $provider ) && array_key_exists( $provider, $this->get_providers() ) ? $provider : self::PROVIDER_OSM;
	}

	/**
	 * Return the label of the chosen map service.
	 *
	 * @return string
	 */
	public function get_provider_label(): string {
		$providers = $this->get_providers();
		return self::PROVIDER_GOOGLE === $this->get_provider() ? __( 'Google Maps', 'connector-for-propstack' ) : ( self::PROVIDER_OSM === $this->get_provider() ? 'OpenStreetMap' : (string) ( $providers[ $this->get_provider() ] ?? '' ) );
	}

	/**
	 * Return whether maps could be shown with the actual settings.
	 *
	 * Google Maps needs an API key.
	 *
	 * @return bool
	 */
	public function is_usable(): bool {
		if ( self::PROVIDER_GOOGLE === $this->get_provider() ) {
			return '' !== $this->get_google_api_key();
		}
		return true;
	}

	/**
	 * Return the API key for Google Maps.
	 *
	 * @return string
	 */
	public function get_google_api_key(): string {
		$key = get_option( 'propstack_connector_map_google_api_key', '' );
		return is_string( $key ) ? $key : '';
	}

	/**
	 * Return the URL of the map tiles for OpenStreetMap.
	 *
	 * @return string
	 */
	public function get_tile_url(): string {
		$url = get_option( 'propstack_connector_map_tile_url', self::DEFAULT_TILE_URL );
		return is_string( $url ) && '' !== $url ? $url : self::DEFAULT_TILE_URL;
	}

	/**
	 * Return the default copyright note for OpenStreetMap.
	 *
	 * @return string
	 */
	public function get_default_attribution(): string {
		return '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>';
	}

	/**
	 * Return the default text of the placeholder for the consent.
	 *
	 * @return string
	 */
	public function get_default_consent_text(): string {
		/* translators: %1$s will be replaced by the name of the map service, e.g. "OpenStreetMap". */
		return sprintf( __( 'This map is provided by %1$s. When the map is loaded, your IP address is sent to this service.', 'connector-for-propstack' ), $this->get_provider_label() );
	}

	/**
	 * Return whether maps should be loaded only after the consent of the visitor.
	 *
	 * @return bool
	 */
	public function requires_consent(): bool {
		$requires_consent = 1 === absint( get_option( 'propstack_connector_map_consent', 1 ) );

		/**
		 * Filter whether maps are loaded only after the consent of the visitor.
		 *
		 * Return false if a consent tool blocks the map service on its own.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param bool $requires_consent True if the visitor has to agree first.
		 */
		return (bool) apply_filters( 'cfprop_map_requires_consent', $requires_consent );
	}

	/**
	 * Return the default zoom level.
	 *
	 * @return int
	 */
	public function get_zoom(): int {
		return $this->sanitize_zoom( get_option( 'propstack_connector_map_zoom', 15 ) );
	}

	/**
	 * Return the default height of maps.
	 *
	 * @return string
	 */
	public function get_height(): string {
		$height = $this->sanitize_height( get_option( 'propstack_connector_map_height', '400px' ) );
		return '' !== $height ? $height : '400px';
	}

	/**
	 * Return the mode for objects with hidden address ("area" or "hide").
	 *
	 * @return string
	 */
	public function get_hidden_address_mode(): string {
		return $this->sanitize_hidden_address_mode( get_option( 'propstack_connector_map_hidden_address', 'area' ) );
	}

	/**
	 * Return the radius of the area for objects with hidden address in meters.
	 *
	 * @return int
	 */
	public function get_area_radius(): int {
		return $this->sanitize_radius( get_option( 'propstack_connector_map_area_radius', 1000 ) );
	}

	/**
	 * Return the reason, why the given object has no location for maps.
	 *
	 * Used for the hint for administrators in frontend. Returns an empty string if the object has a location
	 * or the reason is not known (e.g. the location is hidden via hook).
	 *
	 * @param ImmoObject $immo_object The object.
	 *
	 * @return string
	 */
	public function get_missing_location_reason( ImmoObject $immo_object ): string {
		// bail if the object has a location.
		if ( false !== $this->get_location( $immo_object ) ) {
			return '';
		}

		// check the coordinates.
		$lat = get_post_meta( $immo_object->get_id(), 'latitude', true );
		$lng = get_post_meta( $immo_object->get_id(), 'longitude', true );
		if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) || ( 0.0 === (float) $lat && 0.0 === (float) $lng ) ) {
			return __( 'The map is not shown, because no geo coordinates are stored for this object in Propstack.', 'connector-for-propstack' );
		}

		// check the hidden address.
		if ( Fields::get_instance()->is_address_hidden( $immo_object ) && 'hide' === $this->get_hidden_address_mode() ) {
			return __( 'The map is not shown, because the address of this object is hidden and objects with hidden address are not shown on maps (see the settings for maps).', 'connector-for-propstack' );
		}

		// check the field.
		$latitude = Fields::get_instance()->get_field_by_name( 'latitude' );
		if ( $latitude instanceof Field_Base && ! Fields::get_instance()->is_field_visible_in_frontend( $latitude, $immo_object, false ) ) {
			return __( 'The map is not shown, because the geo coordinates of this object are hidden in the settings for fields.', 'connector-for-propstack' );
		}

		// check for invalid coordinates.
		if ( abs( (float) $lat ) > 90 || abs( (float) $lng ) > 180 ) {
			return __( 'The map is not shown, because the geo coordinates of this object are not valid.', 'connector-for-propstack' );
		}

		// the location is hidden for other reasons (e.g. via hook).
		return '';
	}

	/**
	 * Return the location of the given object for a map.
	 *
	 * For objects with hidden address the location is rounded to 2 decimals (about 1 km) and marked as approximate,
	 * or false is returned, depending on the settings. The exact location of such objects is never returned.
	 *
	 * @param ImmoObject $immo_object The object.
	 *
	 * @return array{lat:float,lng:float,approximate:bool}|false
	 */
	public function get_location( ImmoObject $immo_object ): array|false {
		// get the coordinates.
		$lat = get_post_meta( $immo_object->get_id(), 'latitude', true );
		$lng = get_post_meta( $immo_object->get_id(), 'longitude', true );

		// bail if the coordinates are not valid.
		if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
			return false;
		}
		$lat = (float) $lat;
		$lng = (float) $lng;
		if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ( 0.0 === $lat && 0.0 === $lng ) ) {
			return false;
		}

		// get the fields.
		$fields       = Fields::get_instance();
		$latitude     = $fields->get_field_by_name( 'latitude' );
		$address_hide = $fields->is_address_hidden( $immo_object );

		// bail if the field is hidden for the frontend for other reasons than the hidden address.
		if ( ! $address_hide && $latitude instanceof Field_Base && ! $fields->is_field_visible_in_frontend( $latitude, $immo_object, false ) ) {
			return false;
		}

		// use the exact location if the address is not hidden.
		if ( ! $address_hide ) {
			$location = array(
				'lat'         => $lat,
				'lng'         => $lng,
				'approximate' => false,
			);
		} elseif ( 'area' === $this->get_hidden_address_mode() ) {
			// use only the approximate location.
			$location = array(
				'lat'         => round( $lat, 2 ),
				'lng'         => round( $lng, 2 ),
				'approximate' => true,
			);
		} else {
			return false;
		}

		/**
		 * Filter the location of an object for maps.
		 *
		 * Return false to hide the object on maps. Never return the exact location of objects with hidden address.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array{lat:float,lng:float,approximate:bool}|false $location    The location.
		 * @param ImmoObject                                        $immo_object The object.
		 */
		return $this->validate_location( apply_filters( 'cfprop_map_location', $location, $immo_object ) );
	}

	/**
	 * Return the given location, if it is valid (e.g. after a filter).
	 *
	 * @param mixed $location The location.
	 *
	 * @return array{lat:float,lng:float,approximate:bool}|false
	 */
	private function validate_location( mixed $location ): array|false {
		// bail if the location is not valid.
		if ( ! is_array( $location ) || ! isset( $location['lat'], $location['lng'] ) || ! is_numeric( $location['lat'] ) || ! is_numeric( $location['lng'] ) ) {
			return false;
		}

		// return the location.
		return array(
			'lat'         => (float) $location['lat'],
			'lng'         => (float) $location['lng'],
			'approximate' => ! empty( $location['approximate'] ),
		);
	}

	/**
	 * Return the configuration of the map service for the JavaScript of the maps.
	 *
	 * @return array<string,mixed>
	 */
	public function get_frontend_config(): array {
		// get the text of the placeholder.
		$consent_text = get_option( 'propstack_connector_map_consent_text', '' );
		if ( ! is_string( $consent_text ) || '' === trim( $consent_text ) ) {
			$consent_text = $this->get_default_consent_text();
		}

		$config = array(
			'provider'       => $this->get_provider(),
			'consent'        => $this->requires_consent(),
			'consent_text'   => $consent_text,
			'tile_url'       => $this->get_tile_url(),
			'attribution'    => $this->sanitize_attribution( get_option( 'propstack_connector_map_attribution', $this->get_default_attribution() ) ),
			'google_api_key' => self::PROVIDER_GOOGLE === $this->get_provider() ? $this->get_google_api_key() : '',
			'google_map_id'  => self::PROVIDER_GOOGLE === $this->get_provider() ? $this->sanitize_key( get_option( 'propstack_connector_map_google_map_id', '' ) ) : '',
			'cluster_url'    => '', // the clusterer for Google Maps is added by Connector for Propstack Pro, which shows several objects on maps.
			'area_radius'    => $this->get_area_radius(),
			'texts'          => array(
				'load'          => __( 'Load map', 'connector-for-propstack' ),
				'load_always'   => __( 'Always load maps', 'connector-for-propstack' ),
				'approximate'   => __( 'Approximate location', 'connector-for-propstack' ),
				'show_object'   => __( 'Show object', 'connector-for-propstack' ),
				'error'         => __( 'The map could not be loaded.', 'connector-for-propstack' ),
				'map_label'     => __( 'Map', 'connector-for-propstack' ),
				'consent_label' => __( 'Consent for the map', 'connector-for-propstack' ),
			),
		);

		/**
		 * Filter the configuration of the map service for the JavaScript of the maps.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,mixed> $config The configuration.
		 */
		return apply_filters( 'cfprop_map_config', $config );
	}

	/**
	 * Register the scripts and styles for the maps.
	 *
	 * @return void
	 */
	public function register_assets(): void {
		$url  = Helper::get_plugin_url();
		$path = Helper::get_plugin_path();

		// register Leaflet for OpenStreetMap.
		wp_register_style( 'cfprop-leaflet', $url . 'js/leaflet/leaflet.css', array(), '1.9.4' );
		wp_register_script( 'cfprop-leaflet', $url . 'js/leaflet/leaflet.js', array(), '1.9.4', true );

		// register our own script and styles.
		wp_register_script( 'cfprop-map', $url . 'js/map.js', array(), Helper::get_file_version( $path . 'js/map.js' ), true );
		wp_register_style( 'cfprop-map', $url . 'css/map.css', array(), Helper::get_file_version( $path . 'css/map.css' ) );
	}

	/**
	 * Enqueue the scripts and styles for a map.
	 *
	 * @param bool $with_cluster True if the map shows several objects, which could be grouped.
	 *
	 * @return void
	 */
	public function enqueue_assets( bool $with_cluster = false ): void {
		// register them, if not done yet.
		if ( ! wp_script_is( 'cfprop-map', 'registered' ) ) {
			$this->register_assets();
		}

		// enqueue Leaflet for OpenStreetMap (Google Maps is loaded by our script after the consent).
		if ( self::PROVIDER_OSM === $this->get_provider() ) {
			wp_enqueue_style( 'cfprop-leaflet' );
			wp_enqueue_script( 'cfprop-leaflet' );
		}

		// let others add the scripts to group several markers (the map works without them).
		if ( $with_cluster ) {
			/**
			 * Enqueue the scripts and styles to group several markers on a map.
			 *
			 * Used by Connector for Propstack Pro, which shows several objects on maps. Without them, all markers
			 * are shown on their own.
			 *
			 * @since 2.1.0 Available since 2.1.0.
			 * @param string $provider The map service ("openstreetmap" or "google").
			 */
			do_action( 'cfprop_map_enqueue_cluster_assets', $this->get_provider() );
		}

		// enqueue our script with the configuration (only once).
		if ( ! wp_script_is( 'cfprop-map', 'enqueued' ) ) {
			wp_add_inline_script( 'cfprop-map', 'window.cfpropMapConfig = ' . wp_json_encode( $this->get_frontend_config() ) . ';', 'before' );
		}
		wp_enqueue_script( 'cfprop-map' );
		wp_enqueue_style( 'cfprop-map' );
	}

	/**
	 * Sanitize the setting for the map service.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	public function sanitize_provider( mixed $value ): string {
		return is_string( $value ) && array_key_exists( $value, $this->get_providers() ) ? $value : self::PROVIDER_OSM;
	}

	/**
	 * Sanitize the URL of the map tiles: only HTTPS-URLs with the placeholders are allowed.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	public function sanitize_tile_url( mixed $value ): string {
		// bail if the value is not a string.
		if ( ! is_string( $value ) ) {
			return self::DEFAULT_TILE_URL;
		}

		// get the value.
		$value = trim( $value );

		// bail if the URL is not valid.
		if ( ! str_starts_with( $value, 'https://' ) || ! str_contains( $value, '{z}' ) || ! str_contains( $value, '{x}' ) || ! str_contains( $value, '{y}' ) || 1 === preg_match( '/[\s"\'<>]/', $value ) ) {
			return self::DEFAULT_TILE_URL;
		}

		// return the URL.
		return $value;
	}

	/**
	 * Sanitize the copyright note of the map: only links are allowed.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	public function sanitize_attribution( mixed $value ): string {
		// bail if the value is not a string.
		if ( ! is_string( $value ) ) {
			return '';
		}

		// return the value with links only.
		return trim(
			wp_kses(
				$value,
				array(
					'a' => array(
						'href'   => true,
						'target' => true,
						'rel'    => true,
					),
				)
			)
		);
	}

	/**
	 * Sanitize a key (e.g. the API key or the map ID for Google Maps).
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	public function sanitize_key( mixed $value ): string {
		// bail if the value is not a string.
		if ( ! is_string( $value ) ) {
			return '';
		}

		// return only the allowed characters.
		return (string) preg_replace( '/[^A-Za-z0-9_\-]/', '', $value );
	}

	/**
	 * Sanitize a text.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	public function sanitize_text( mixed $value ): string {
		return is_string( $value ) ? sanitize_text_field( $value ) : '';
	}

	/**
	 * Sanitize the zoom level.
	 *
	 * @param mixed $value The value.
	 *
	 * @return int
	 */
	public function sanitize_zoom( mixed $value ): int {
		$value = is_numeric( $value ) ? absint( $value ) : 15;
		return (int) min( 19, max( 1, $value ) );
	}

	/**
	 * Sanitize the height of a map: a number with a CSS unit.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string The height or an empty string if it is not valid.
	 */
	public function sanitize_height( mixed $value ): string {
		// bail if the value is not a string.
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		// get the value.
		$value = strtolower( trim( (string) $value ) );

		// add "px" to numbers.
		if ( 1 === preg_match( '/^\d+(\.\d+)?$/', $value ) ) {
			$value .= 'px';
		}

		// return the value only if it is a number with a unit.
		return 1 === preg_match( '/^\d+(\.\d+)?(px|vh|vw|em|rem|%)$/', $value ) ? $value : '';
	}

	/**
	 * Sanitize the mode for objects with hidden address.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	public function sanitize_hidden_address_mode( mixed $value ): string {
		return is_string( $value ) && array_key_exists( $value, $this->get_hidden_address_modes() ) ? $value : 'area';
	}

	/**
	 * Sanitize the radius of the area for objects with hidden address.
	 *
	 * @param mixed $value The value.
	 *
	 * @return int
	 */
	public function sanitize_radius( mixed $value ): int {
		$value = is_numeric( $value ) ? absint( $value ) : 1000;
		return (int) min( 20000, max( 500, $value ) );
	}

	/**
	 * Return the settings of the maps for the abilities.
	 *
	 * The API key for Google Maps is not returned, only whether it is set.
	 *
	 * @return array<string,mixed>
	 */
	public function get_settings_for_abilities(): array {
		return array(
			'provider'             => $this->get_provider(),
			'providers'            => $this->get_providers(),
			'usable'               => $this->is_usable(),
			'google_api_key_set'   => '' !== $this->get_google_api_key(),
			'google_map_id'        => $this->sanitize_key( get_option( 'propstack_connector_map_google_map_id', '' ) ),
			'tile_url'             => $this->get_tile_url(),
			'attribution'          => $this->sanitize_attribution( get_option( 'propstack_connector_map_attribution', $this->get_default_attribution() ) ),
			'consent'              => 1 === absint( get_option( 'propstack_connector_map_consent', 1 ) ),
			'consent_text'         => $this->sanitize_text( get_option( 'propstack_connector_map_consent_text', '' ) ),
			'zoom'                 => $this->get_zoom(),
			'height'               => $this->get_height(),
			'hidden_address'       => $this->get_hidden_address_mode(),
			'hidden_address_modes' => $this->get_hidden_address_modes(),
			'area_radius'          => $this->get_area_radius(),
		);
	}

	/**
	 * Return the setting object of the given setting, e.g., to use its sanitize callback.
	 *
	 * @param string $name The name of the setting.
	 *
	 * @return Setting|false
	 */
	public function get_setting( string $name ): Setting|false {
		$setting = Settings::get_instance()->get_settings_obj()->get_setting( $name );
		return $setting instanceof Setting ? $setting : false;
	}
}

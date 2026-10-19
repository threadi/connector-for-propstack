<?php
/**
 * File to handle the abilities for the settings of the sorting and the maps.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Object to handle the abilities for the settings of the sorting and the maps.
 *
 * These abilities allow AI to read and change how the list of objects could be sorted and how maps are shown.
 * Changes are made only with "dry_run": false, so the AI could show them to the user first.
 */
class Display_Abilities {
	/**
	 * Instance of this object.
	 *
	 * @var ?Display_Abilities
	 */
	private static ?Display_Abilities $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Display_Abilities {
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
		add_action( 'wp_abilities_api_init', array( $this, 'add_abilities' ) );
	}

	/**
	 * Return the annotations for abilities which only read data.
	 *
	 * @return array<string,mixed>
	 */
	private function get_read_meta(): array {
		return array(
			'show_in_rest' => true,
			'mcp'          => array( 'public' => true ), // expose via the MCP Adapter of WordPress.
			'annotations'  => array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			),
		);
	}

	/**
	 * Return the annotations for abilities which change settings.
	 *
	 * @return array<string,mixed>
	 */
	private function get_change_meta(): array {
		return array(
			'show_in_rest' => true,
			'mcp'          => array( 'public' => true ), // expose via the MCP Adapter of WordPress.
			'annotations'  => array(
				'readonly'    => false,
				'destructive' => false,
				'idempotent'  => true,
			),
		);
	}

	/**
	 * Return the output schema for abilities which change settings.
	 *
	 * @return array<string,mixed>
	 */
	private function get_change_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'dry_run'  => array( 'type' => 'boolean' ),
				'done'     => array(
					'type'        => 'boolean',
					'description' => __( 'True if the changes have been saved.', 'connector-for-propstack' ),
				),
				'changes'  => array(
					'type'                 => 'object',
					'description'          => __( 'The settings which are or would be changed, with their new values.', 'connector-for-propstack' ),
					'additionalProperties' => true,
				),
				'settings' => array(
					'type'                 => 'object',
					'description'          => __( 'The settings after the changes.', 'connector-for-propstack' ),
					'additionalProperties' => true,
				),
				'message'  => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Return the schema for the parameter "dry_run".
	 *
	 * @return array<string,mixed>
	 */
	private function get_dry_run_schema(): array {
		return array(
			'type'        => 'boolean',
			'description' => __( 'Only return what would be changed, without saving. Set to false to save.', 'connector-for-propstack' ),
			'default'     => true,
		);
	}

	/**
	 * Add the abilities.
	 *
	 * @return void
	 */
	public function add_abilities(): void {
		// bail if support for abilities is missing.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		// add the ability to get the settings of the sorting.
		wp_register_ability(
			Abilities::ABILITY_CATEGORY . '/get-sorting-settings',
			array(
				'label'               => __( 'Get settings of the sorting', 'connector-for-propstack' ),
				'description'         => __( 'Returns whether visitors could sort the list of objects on the website, the default sorting and all sortings with their options (e.g. "price-asc") and whether they are hidden. The selection above the list offers only sortings which are useful for the objects of the actual list (in lists with objects to buy and to rent, the price sorting shows the objects to buy first, then the objects to rent) and which at least half of these objects have a value for.', 'connector-for-propstack' ),
				'category'            => Abilities::ABILITY_CATEGORY,
				'input_schema'        => array(
					'type'    => 'object',
					'default' => array(),
				),
				'output_schema'       => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
				'execute_callback'    => array( $this, 'get_sorting_settings' ),
				'permission_callback' => array( Abilities::get_instance(), 'has_permission' ),
				'meta'                => $this->get_read_meta(),
			)
		);

		// add the ability to change the settings of the sorting.
		wp_register_ability(
			Abilities::ABILITY_CATEGORY . '/update-sorting-settings',
			array(
				'label'               => __( 'Change settings of the sorting', 'connector-for-propstack' ),
				'description'         => __( 'Changes whether visitors could sort the list of objects, the default sorting and which sortings are hidden. Only the given parameters are changed. Call it first with "dry_run": true, show the result to the user and save with "dry_run": false only after the user agreed.', 'connector-for-propstack' ),
				'category'            => Abilities::ABILITY_CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'enabled' => array(
							'type'        => 'boolean',
							'description' => __( 'True to show the selection for the sorting above the list of objects.', 'connector-for-propstack' ),
						),
						'default' => array(
							'type'        => 'string',
							'description' => __( 'The default sorting, e.g. "price-asc", or an empty string for the standard order (newest objects first). See get-sorting-settings. Only possible with Connector for Propstack Pro (see default_selectable).', 'connector-for-propstack' ),
						),
						'hidden'  => array(
							'type'        => 'array',
							'description' => __( 'The names of the sortings which should be hidden, e.g. ["city"]. All other sortings are shown. See get-sorting-settings.', 'connector-for-propstack' ),
							'items'       => array( 'type' => 'string' ),
						),
						'dry_run' => $this->get_dry_run_schema(),
					),
					'default'    => array(),
				),
				'output_schema'       => $this->get_change_output_schema(),
				'execute_callback'    => array( $this, 'update_sorting_settings' ),
				'permission_callback' => array( Abilities::get_instance(), 'has_permission' ),
				'meta'                => $this->get_change_meta(),
			)
		);

		// add the ability to get the settings of the maps.
		wp_register_ability(
			Abilities::ABILITY_CATEGORY . '/get-map-settings',
			array(
				'label'               => __( 'Get settings of the maps', 'connector-for-propstack' ),
				'description'         => __( 'Returns the settings of the maps which show the location of objects: the map service (OpenStreetMap or Google Maps), whether an API key for Google Maps is set (the key itself is never returned), the consent, the zoom level, the height and how objects with hidden address are shown. Maps are placed with the widget or block "Map", see get-template-catalog.', 'connector-for-propstack' ),
				'category'            => Abilities::ABILITY_CATEGORY,
				'input_schema'        => array(
					'type'    => 'object',
					'default' => array(),
				),
				'output_schema'       => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
				'execute_callback'    => array( $this, 'get_map_settings' ),
				'permission_callback' => array( Abilities::get_instance(), 'has_permission' ),
				'meta'                => $this->get_read_meta(),
			)
		);

		// add the ability to change the settings of the maps.
		wp_register_ability(
			Abilities::ABILITY_CATEGORY . '/update-map-settings',
			array(
				'label'               => __( 'Change settings of the maps', 'connector-for-propstack' ),
				'description'         => __( 'Changes the settings of the maps. Only the given parameters are changed. Ask the user before the consent is disabled: without it, the map service receives the IP address of each visitor before he agreed, which is only allowed if a consent tool blocks the map service. Call it first with "dry_run": true, show the result to the user and save with "dry_run": false only after the user agreed.', 'connector-for-propstack' ),
				'category'            => Abilities::ABILITY_CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'provider'       => array(
							'type'        => 'string',
							'description' => __( 'The map service, see get-map-settings.', 'connector-for-propstack' ),
							'enum'        => array_keys( Maps::get_instance()->get_providers() ),
						),
						'google_api_key' => array(
							'type'        => 'string',
							'description' => __( 'The API key for Google Maps. Only set it if the user gave it to you.', 'connector-for-propstack' ),
						),
						'google_map_id'  => array(
							'type'        => 'string',
							'description' => __( 'Optional map ID for Google Maps.', 'connector-for-propstack' ),
						),
						'tile_url'       => array(
							'type'        => 'string',
							'description' => __( 'The HTTPS-URL of the tile server for OpenStreetMap with the placeholders {z}, {x} and {y}.', 'connector-for-propstack' ),
						),
						'attribution'    => array(
							'type'        => 'string',
							'description' => __( 'The copyright note of the tile server, links are allowed.', 'connector-for-propstack' ),
						),
						'consent'        => array(
							'type'        => 'boolean',
							'description' => __( 'True to load maps only after the consent of the visitor.', 'connector-for-propstack' ),
						),
						'consent_text'   => array(
							'type'        => 'string',
							'description' => __( 'The text of the placeholder for the consent, empty for the default text.', 'connector-for-propstack' ),
						),
						'zoom'           => array(
							'type'        => 'integer',
							'description' => __( 'The default zoom level from 1 (world) to 19 (street).', 'connector-for-propstack' ),
							'minimum'     => 1,
							'maximum'     => 19,
						),
						'height'         => array(
							'type'        => 'string',
							'description' => __( 'The default height of maps with a CSS unit, e.g. "400px".', 'connector-for-propstack' ),
						),
						'hidden_address' => array(
							'type'        => 'string',
							'description' => __( 'How objects with hidden address are shown: "area" for the approximate area or "hide" to not show them.', 'connector-for-propstack' ),
							'enum'        => array_keys( Maps::get_instance()->get_hidden_address_modes() ),
						),
						'area_radius'    => array(
							'type'        => 'integer',
							'description' => __( 'The radius of the area for objects with hidden address in meters (500 to 20000).', 'connector-for-propstack' ),
							'minimum'     => 500,
							'maximum'     => 20000,
						),
						'dry_run'        => $this->get_dry_run_schema(),
					),
					'default'    => array(),
				),
				'output_schema'       => $this->get_change_output_schema(),
				'execute_callback'    => array( $this, 'update_map_settings' ),
				'permission_callback' => array( Abilities::get_instance(), 'has_permission' ),
				'meta'                => $this->get_change_meta(),
			)
		);
	}

	/**
	 * Return the settings of the sorting.
	 *
	 * @return array<string,mixed>
	 */
	public function get_sorting_settings(): array {
		$sortings = array();
		foreach ( Sortings::get_instance()->get_sortings_as_objects() as $sorting ) {
			$sortings[] = array(
				'name'    => $sorting->get_name(),
				'label'   => $sorting->get_label(),
				'options' => $sorting->get_options(),
				'hidden'  => $sorting->is_hidden(),
			);
		}

		return array(
			'enabled'            => Sortings::get_instance()->is_enabled(),
			'default'            => Sortings::get_instance()->get_default_option(),
			'default_selectable' => Sortings::get_instance()->is_default_selectable(),
			'sortings'           => $sortings,
			'min_share'          => Sortings::get_instance()->get_min_share(),
		);
	}

	/**
	 * Change the settings of the sorting.
	 *
	 * @param array<string,mixed> $input The given input.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function update_sorting_settings( array $input = array() ): array|WP_Error {
		$changes = array();
		$options = array();

		// check whether the sorting should be enabled.
		if ( isset( $input['enabled'] ) ) {
			$changes['enabled']                             = (bool) $input['enabled'];
			$options['propstack_connector_sorting_enabled'] = $changes['enabled'] ? 1 : 0;
		}

		// check the default sorting.
		if ( isset( $input['default'] ) ) {
			if ( ! Sortings::get_instance()->is_default_selectable() ) {
				return new WP_Error( 'cfprop_default_sorting_not_available', __( 'The default sorting could only be chosen with Connector for Propstack Pro.', 'connector-for-propstack' ), array( 'status' => 400 ) );
			}
			$default = is_string( $input['default'] ) ? sanitize_text_field( $input['default'] ) : '';
			if ( '' !== $default && false === Sortings::get_instance()->get_sorting_by_option( $default ) ) {
				return new WP_Error( 'cfprop_unknown_sorting', __( 'The given default sorting is not known. Use get-sorting-settings for the available options.', 'connector-for-propstack' ), array( 'status' => 400 ) );
			}
			$changes['default']                             = $default;
			$options['propstack_connector_sorting_default'] = $default;
		}

		// check the hidden sortings.
		if ( isset( $input['hidden'] ) && is_array( $input['hidden'] ) ) {
			$hidden = array_values( array_unique( array_map( 'sanitize_key', array_filter( $input['hidden'], 'is_string' ) ) ) );
			foreach ( $hidden as $name ) {
				if ( false === Sortings::get_instance()->get_sorting_by_name( $name ) ) {
					/* translators: %1$s will be replaced by the name of a sorting. */
					return new WP_Error( 'cfprop_unknown_sorting', sprintf( __( 'The sorting "%1$s" is not known. Use get-sorting-settings for the available sortings.', 'connector-for-propstack' ), $name ), array( 'status' => 400 ) );
				}
			}
			$changes['hidden'] = $hidden;
			foreach ( Sortings::get_instance()->get_sortings_as_objects() as $sorting ) {
				$options[ 'propstack_connector_sortings_' . $sorting->get_name() . '_hidden' ] = in_array( $sorting->get_name(), $hidden, true ) ? 1 : 0;
			}
		}

		// save the changes and return the result.
		return $this->save_options( $options, $changes, ! isset( $input['dry_run'] ) || ! empty( $input['dry_run'] ), array( $this, 'get_sorting_settings' ) );
	}

	/**
	 * Return the settings of the maps.
	 *
	 * @return array<string,mixed>
	 */
	public function get_map_settings(): array {
		return Maps::get_instance()->get_settings_for_abilities();
	}

	/**
	 * Change the settings of the maps.
	 *
	 * @param array<string,mixed> $input The given input.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function update_map_settings( array $input = array() ): array|WP_Error {
		$maps    = Maps::get_instance();
		$changes = array();
		$options = array();

		// check the map service.
		if ( isset( $input['provider'] ) ) {
			if ( ! is_string( $input['provider'] ) || ! array_key_exists( $input['provider'], $maps->get_providers() ) ) {
				return new WP_Error( 'cfprop_unknown_map_provider', __( 'The given map service is not supported. Use get-map-settings for the available map services.', 'connector-for-propstack' ), array( 'status' => 400 ) );
			}
			$changes['provider']                         = $input['provider'];
			$options['propstack_connector_map_provider'] = $input['provider'];
		}

		// check the API key for Google Maps (never returned, only marked as changed).
		if ( isset( $input['google_api_key'] ) ) {
			$options['propstack_connector_map_google_api_key'] = $maps->sanitize_key( $input['google_api_key'] );
			$changes['google_api_key_set']                     = '' !== $options['propstack_connector_map_google_api_key'];
		}

		// check the map ID for Google Maps.
		if ( isset( $input['google_map_id'] ) ) {
			$changes['google_map_id']                         = $maps->sanitize_key( $input['google_map_id'] );
			$options['propstack_connector_map_google_map_id'] = $changes['google_map_id'];
		}

		// check the URL of the tiles.
		if ( isset( $input['tile_url'] ) ) {
			$tile_url = $maps->sanitize_tile_url( $input['tile_url'] );
			if ( is_string( $input['tile_url'] ) && trim( $input['tile_url'] ) !== $tile_url ) {
				return new WP_Error( 'cfprop_invalid_tile_url', __( 'The URL of the tiles must start with "https://" and contain the placeholders {z}, {x} and {y}.', 'connector-for-propstack' ), array( 'status' => 400 ) );
			}
			$changes['tile_url']                         = $tile_url;
			$options['propstack_connector_map_tile_url'] = $tile_url;
		}

		// check the copyright note.
		if ( isset( $input['attribution'] ) ) {
			$changes['attribution']                         = $maps->sanitize_attribution( $input['attribution'] );
			$options['propstack_connector_map_attribution'] = $changes['attribution'];
		}

		// check the consent.
		if ( isset( $input['consent'] ) ) {
			$changes['consent']                         = (bool) $input['consent'];
			$options['propstack_connector_map_consent'] = $changes['consent'] ? 1 : 0;
		}

		// check the text of the placeholder.
		if ( isset( $input['consent_text'] ) ) {
			$changes['consent_text']                         = $maps->sanitize_text( $input['consent_text'] );
			$options['propstack_connector_map_consent_text'] = $changes['consent_text'];
		}

		// check the zoom level.
		if ( isset( $input['zoom'] ) ) {
			$changes['zoom']                         = $maps->sanitize_zoom( $input['zoom'] );
			$options['propstack_connector_map_zoom'] = $changes['zoom'];
		}

		// check the height.
		if ( isset( $input['height'] ) ) {
			$height = $maps->sanitize_height( $input['height'] );
			if ( '' === $height ) {
				return new WP_Error( 'cfprop_invalid_map_height', __( 'The height must be a number with a CSS unit, e.g. "400px" or "50vh".', 'connector-for-propstack' ), array( 'status' => 400 ) );
			}
			$changes['height']                         = $height;
			$options['propstack_connector_map_height'] = $height;
		}

		// check the mode for objects with hidden address.
		if ( isset( $input['hidden_address'] ) ) {
			$changes['hidden_address']                         = $maps->sanitize_hidden_address_mode( $input['hidden_address'] );
			$options['propstack_connector_map_hidden_address'] = $changes['hidden_address'];
		}

		// check the radius.
		if ( isset( $input['area_radius'] ) ) {
			$changes['area_radius']                         = $maps->sanitize_radius( $input['area_radius'] );
			$options['propstack_connector_map_area_radius'] = $changes['area_radius'];
		}

		// save the changes and return the result.
		return $this->save_options( $options, $changes, ! isset( $input['dry_run'] ) || ! empty( $input['dry_run'] ), array( $this, 'get_map_settings' ) );
	}

	/**
	 * Save the given options, if this is not a dry run, and return the result.
	 *
	 * @param array<string,mixed> $options  The options to save.
	 * @param array<string,mixed> $changes  The changes for the result.
	 * @param bool                $dry_run  True if nothing should be saved.
	 * @param callable            $settings Callback which returns the actual settings.
	 *
	 * @return array<string,mixed>
	 */
	private function save_options( array $options, array $changes, bool $dry_run, callable $settings ): array {
		// bail if nothing should be changed.
		if ( empty( $options ) ) {
			return array(
				'dry_run'  => $dry_run,
				'done'     => false,
				'changes'  => (object) array(),
				'settings' => $settings(),
				'message'  => __( 'No setting to change has been given.', 'connector-for-propstack' ),
			);
		}

		// bail if this is a dry run.
		if ( $dry_run ) {
			return array(
				'dry_run'  => true,
				'done'     => false,
				'changes'  => $changes,
				'settings' => $settings(),
				'message'  => __( 'Nothing has been saved. Show the changes to the user and call this ability with "dry_run": false to save them.', 'connector-for-propstack' ),
			);
		}

		// save the options.
		foreach ( $options as $name => $value ) {
			update_option( $name, $value );
		}

		// return the result.
		return array(
			'dry_run'  => false,
			'done'     => true,
			'changes'  => $changes,
			'settings' => $settings(),
			'message'  => __( 'The changes have been saved.', 'connector-for-propstack' ),
		);
	}
}

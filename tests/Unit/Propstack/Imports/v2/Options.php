<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\Imports\v2\Options.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\Imports\v2;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\Imports\v2\Options.
 *
 * The shared test case answers the request for the options with the fixture "property_options_full.json"
 * if an API key is set, and with the HTTP status 401 if not.
 */
class Options extends ConnectorForPropstackTestCase {
	/**
	 * The object to test.
	 *
	 * @var \ConnectorForPropstack\Propstack\Imports\v2\Options
	 */
	private \ConnectorForPropstack\Propstack\Imports\v2\Options $options_obj;

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// set a pseudo-key, so the API answers with the options.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// get the object to test.
		$this->options_obj = new \ConnectorForPropstack\Propstack\Imports\v2\Options();
	}

	/**
	 * Return the codes with the texts which are expected for them.
	 *
	 * @return array<string,array<int,mixed>>
	 */
	public static function get_codes(): array {
		return array(
			'heating type'                => array( 'heating_type', 'SELF_CONTAINED_CENTRAL_HEATING', 'Etagenheizung' ),
			'condition'                   => array( 'condition', 'MINT_CONDITION', 'neuwertig' ),
			'interior quality'            => array( 'interior_quality', 'SOPHISTICATED', 'gehoben' ),
			'firing type'                 => array( 'firing_types', 'GEOTHERMAL', 'Erdwärme' ),
			'energy rating type'          => array( 'building_energy_rating_type', 'ENERGY_CONSUMPTION', 'Verbrauchsausweis' ),
			'energy certificate'          => array( 'energy_certificate_availability', 'AVAILABLE', 'liegt vor' ),
			'energy certificate date'     => array( 'energy_certificate_creation_date', 'FROM_01_MAY_2014', 'ab 1. Mai 2014' ),
			'energy efficiency class'     => array( 'energy_efficiency_class', 'A_PLUS', 'A+' ),
			'multiple codes'              => array( 'firing_types', 'GAS, OIL', 'Gas, Öl' ),
			'list of codes'               => array( 'firing_types', array( 'GAS', 'OIL' ), array( 'Gas', 'Öl' ) ),
			'unknown code'                => array( 'heating_type', 'NOT_KNOWN', 'NOT_KNOWN' ),
			'multiple codes with unknown' => array( 'firing_types', 'GAS, NOT_KNOWN', 'GAS, NOT_KNOWN' ),
			'no value'                    => array( 'heating_type', null, null ),
			'marketing type keeps code'   => array( 'marketing_type', 'RENT', 'RENT' ),
			'object type keeps code'      => array( 'rs_type', 'APARTMENT', 'APARTMENT' ),
			'category keeps code'         => array( 'object_type', 'LIVING', 'LIVING' ),
			'property type keeps code'    => array( 'rs_category', 'ROOF_STOREY', 'ROOF_STOREY' ),
			'commission is a free text'   => array( 'courtage', 'BUY', 'BUY' ),
			'field without options'       => array( 'title', 'AVAILABLE', 'AVAILABLE' ),
		);
	}

	/**
	 * Test that the codes of selection fields are replaced by their texts.
	 *
	 * @dataProvider get_codes
	 *
	 * @param string $field_name The name of the field in the API.
	 * @param mixed  $value      The value from the API.
	 * @param mixed  $expected   The expected value.
	 *
	 * @return void
	 */
	public function test_codes_are_replaced_by_their_texts( string $field_name, mixed $value, mixed $expected ): void {
		$immo_object = $this->options_obj->translate_object( array( $field_name => $value ), 'de' );

		$this->assertSame( $expected, $immo_object[ $field_name ] );
	}

	/**
	 * Test that other fields of the object are not changed.
	 *
	 * @return void
	 */
	public function test_other_fields_are_not_changed(): void {
		$immo_object = array(
			'id'           => 42,
			'title'        => 'Musterhaus',
			'heating_type' => 'GAS_HEATING',
			'balcony'      => true,
			'price'        => 349000,
			'images'       => array( array( 'id' => 1 ) ),
		);

		$expected                 = $immo_object;
		$expected['heating_type'] = 'Gas-Heizung';

		$this->assertSame( $expected, $this->options_obj->translate_object( $immo_object, 'de' ) );
	}

	/**
	 * Test that the options from the API are saved as reserve.
	 *
	 * @return void
	 */
	public function test_options_from_api_are_saved(): void {
		$this->assertFalse( get_option( \ConnectorForPropstack\Propstack\Imports\v2\Options::OPTION_PREFIX . 'de' ) );

		$options = $this->options_obj->get_options( 'de' );

		$this->assertArrayHasKey( 'heating_type', $options );
		$this->assertSame( $options, get_option( \ConnectorForPropstack\Propstack\Imports\v2\Options::OPTION_PREFIX . 'de' ) );
	}

	/**
	 * Test that the saved options are used if the API does not deliver them.
	 *
	 * @return void
	 */
	public function test_saved_options_are_used_if_the_api_fails(): void {
		// save options from an earlier import.
		update_option( \ConnectorForPropstack\Propstack\Imports\v2\Options::OPTION_PREFIX . 'de', array( 'heating_type' => array( 'GAS_HEATING' => 'Saved text' ) ), false );

		// without a key the API answers with an error.
		update_option( 'propstack_connector_api_key', '' );

		$immo_object = $this->options_obj->translate_object( array( 'heating_type' => 'GAS_HEATING' ), 'de' );

		$this->assertSame( 'Saved text', $immo_object['heating_type'] );
	}

	/**
	 * Test that the options delivered with the plugin are used if the API does not deliver them and nothing is saved.
	 *
	 * @return void
	 */
	public function test_delivered_options_are_used_if_the_api_fails(): void {
		// without a key the API answers with an error.
		update_option( 'propstack_connector_api_key', '' );

		$immo_object = $this->options_obj->translate_object( array( 'heating_type' => 'GAS_HEATING' ), 'de' );

		$this->assertSame( 'Gas-Heizung', $immo_object['heating_type'] );

		// they are not saved as if they came from the API.
		$this->assertFalse( get_option( \ConnectorForPropstack\Propstack\Imports\v2\Options::OPTION_PREFIX . 'de' ) );
	}

	/**
	 * Test that the options delivered with the plugin are also used for the English language.
	 *
	 * @return void
	 */
	public function test_delivered_options_are_used_for_english(): void {
		// without a key the API answers with an error.
		update_option( 'propstack_connector_api_key', '' );

		$immo_object = $this->options_obj->translate_object(
			array(
				'heating_type'            => 'GAS_HEATING',
				'energy_efficiency_class' => 'A_PLUS',
				'marketing_type'          => 'RENT',
			),
			'en'
		);

		$this->assertSame( 'Gas heating', $immo_object['heating_type'] );
		$this->assertSame( 'A+', $immo_object['energy_efficiency_class'] );
		$this->assertSame( 'RENT', $immo_object['marketing_type'] );
	}

	/**
	 * Test that a hint about the missing permission is logged if the API rejects the API key.
	 *
	 * @return void
	 */
	public function test_missing_permission_is_logged(): void {
		global $wpdb;

		// without a key the API answers with HTTP status 401.
		update_option( 'propstack_connector_api_key', '' );

		$this->options_obj->get_options( 'de' );

		$this->assertSame( 1, absint( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'propstack_logs WHERE state = %s AND log LIKE %s', 'error', '%' . $wpdb->esc_like( 'does not allow your API key to read the options of object fields' ) . '%' ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Test whether a token is permitted to read the options.
	 *
	 * @return void
	 */
	public function test_token_permission(): void {
		// the mocked API accepts any key and rejects a missing one.
		$this->assertTrue( $this->options_obj->is_token_permitted( self::$api_key ) );
		$this->assertFalse( $this->options_obj->is_token_permitted( '' ) );
	}

	/**
	 * Test that the codes are kept if no options are available for the language.
	 *
	 * @return void
	 */
	public function test_codes_are_kept_without_any_options(): void {
		// without a key the API answers with an error, and we do not deliver options for this language.
		update_option( 'propstack_connector_api_key', '' );

		$immo_object = $this->options_obj->translate_object( array( 'heating_type' => 'GAS_HEATING' ), 'xx' );

		$this->assertSame( 'GAS_HEATING', $immo_object['heating_type'] );
	}

	/**
	 * Test that options are delivered with the plugin for every language we support, and that they
	 * are valid and contain the texts of the fields we need.
	 *
	 * @return void
	 */
	public function test_delivered_options_are_valid(): void {
		foreach ( array_keys( \ConnectorForPropstack\Plugin\Languages::get_instance()->get_languages() ) as $language_code ) {
			$path = TESTS_PLUGIN_DIR . '/lib/options-' . $language_code . '.json';
			$this->assertFileExists( $path );

			$data = json_decode( (string) file_get_contents( $path ), true );

			$this->assertIsArray( $data, $language_code );
			$this->assertIsArray( $data['data'], $language_code );
			foreach ( array( 'heating_type', 'condition', 'interior_quality', 'firing_types', 'building_energy_rating_type', 'energy_certificate_availability', 'energy_certificate_creation_date', 'energy_efficiency_class' ) as $field_name ) {
				$this->assertNotEmpty( $data['data'][ $field_name ], 'Missing texts for ' . $field_name . ' in ' . $language_code );
			}
		}
	}
}

<?php
/**
 * File for tests against the preparation of objects in \ConnectorForPropstack\Propstack\Imports\v2\Objects.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\Imports\v2;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\ImmoObject;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\States;
use ConnectorForPropstack\Propstack\Taxonomies\Broker;
use ConnectorForPropstack\Propstack\Taxonomies\MarketingType;
use ConnectorForPropstack\Propstack\Taxonomies\PropertyType;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;
use WP_HTTP_Requests_Response;
use WP_Term;

/**
 * Object for tests against the preparation of objects in \ConnectorForPropstack\Propstack\Imports\v2\Objects.
 *
 * The API v2 delivers the codes of selection fields and only the ID of the broker. These tests pin that an
 * object is saved as if the API v1 had delivered it: with the texts of the selection fields and with its broker.
 *
 * The fixture "properties_complete.json" contains one object with every field the API v2 delivers.
 */
class ObjectsPrepared extends ConnectorForPropstackTestCase {
	/**
	 * The API URL used by the v2 import.
	 *
	 * @var string
	 */
	private static string $properties_url = 'https://api.propstack.de/v2/properties?';

	/**
	 * The requested URLs for objects during a test.
	 *
	 * @var array<int,string>
	 */
	private array $requested_urls = array();

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// reset the marker.
		$this->requested_urls = array();

		// no import and no deletion is running.
		update_option( CFPROP_IMPORT_RUNNING, 0 );
		update_option( CFPROP_DELETE_RUNNING, 0 );

		// set language to "de".
		update_option( 'propstack_connector_languages', 'de' );

		// use API v2.
		update_option( 'propstack_connector_api_version', 'v2' );

		// set a pseudo-key.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// mock the v2 endpoint.
		add_filter( 'pre_http_request', array( $this, 'mock_v2_request' ), 10, 3 );

		// initialize the states object.
		States::get_instance()->init();

		// run the activation.
		\ConnectorForPropstack\Propstack\Propstack::get_instance()->activation();
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_v2_request' ) );

		parent::tear_down();
	}

	/**
	 * Deliver a local response for the v2 endpoint.
	 *
	 * @param false|array<string,mixed>|WP_Error $result      The return value of the filter.
	 * @param array<string,mixed>                $parsed_args The used parameters for the request.
	 * @param string                             $url         The requested URL.
	 *
	 * @return false|array<string,mixed>|WP_Error
	 */
	public function mock_v2_request( false|array|WP_Error $result, array $parsed_args, string $url ): false|array|WP_Error {
		// bail if this is not our v2 request.
		if ( 'GET' !== $parsed_args['method'] || ! str_starts_with( $url, self::$properties_url ) ) {
			return $result;
		}

		// remember the URL.
		$this->requested_urls[] = $url;

		// deliver our fixture.
		$requests_response              = new \WpOrg\Requests\Response();
		$requests_response->status_code = 200;

		return array(
			'http_response' => new WP_HTTP_Requests_Response( $requests_response, $parsed_args['filename'] ),
			'body'          => Helper::get_wp_filesystem()->get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'properties_complete.json' ),
		);
	}

	/**
	 * Run the import until it reports that it is completed and return the imported object.
	 *
	 * @return ImmoObject
	 */
	private function import_object(): ImmoObject {
		$runs = 0;

		do {
			$import_obj = new \ConnectorForPropstack\Propstack\Imports\v2\Objects();
			$import_obj->run();

			++$runs;

			$this->assertLessThan( 50, $runs, 'The import did not finish.' );
		} while ( $import_obj->has_load_more() );

		// the import must not report any error.
		$this->assertEmpty( $import_obj->get_errors() );

		// get the object.
		$immo_object = ImmoObjects::get_instance()->get_object_by_object_id( '4711', 'de' );
		$this->assertInstanceOf( ImmoObject::class, $immo_object );

		return $immo_object;
	}

	/**
	 * Return the fields with selection values and the texts which are expected for them.
	 *
	 * @return array<string,array<int,string>>
	 */
	public static function get_selection_fields(): array {
		return array(
			'heating type'            => array( 'heating_type', 'Etagenheizung' ),
			'condition'               => array( 'condition', 'neuwertig' ),
			'interior quality'        => array( 'interior_quality', 'gehoben' ),
			'firing type'             => array( 'firing_types', 'Erdwärme' ),
			'energy rating type'      => array( 'building_energy_rating_type', 'Verbrauchsausweis' ),
			'energy certificate'      => array( 'energy_certificate_availability', 'liegt vor' ),
			'energy efficiency class' => array( 'energy_efficiency_class', 'C' ),
		);
	}

	/**
	 * Test that the texts of selection fields are saved instead of their codes.
	 *
	 * @dataProvider get_selection_fields
	 *
	 * @param string $field_name The name of the field.
	 * @param string $expected   The expected value.
	 *
	 * @return void
	 */
	public function test_texts_of_selection_fields_are_saved( string $field_name, string $expected ): void {
		$immo_object = $this->import_object();

		$this->assertSame( $expected, get_post_meta( $immo_object->get_id(), $field_name, true ) );
	}

	/**
	 * Test that the object is still assigned to its terms by the codes.
	 *
	 * @return void
	 */
	public function test_terms_are_assigned_by_their_codes(): void {
		$immo_object = $this->import_object();

		// the marketing type.
		$terms = wp_get_object_terms( $immo_object->get_id(), MarketingType::get_instance()->get_name() );
		$this->assertIsArray( $terms );
		$this->assertCount( 1, $terms );
		$this->assertSame( 'RENT', get_term_meta( $terms[0]->term_id, 'api', true ) );

		// the property type.
		$terms = wp_get_object_terms( $immo_object->get_id(), PropertyType::get_instance()->get_name() );
		$this->assertIsArray( $terms );
		$this->assertCount( 1, $terms );
		$this->assertSame( 'ROOF_STOREY', get_term_meta( $terms[0]->term_id, 'api', true ) );
	}

	/**
	 * Test that the broker is assigned to the object with its data.
	 *
	 * @return void
	 */
	public function test_broker_is_assigned_with_its_data(): void {
		$immo_object = $this->import_object();

		// get the assigned broker.
		$terms = wp_get_object_terms( $immo_object->get_id(), Broker::get_instance()->get_name() );
		$this->assertIsArray( $terms );
		$this->assertCount( 1, $terms );
		$this->assertInstanceOf( WP_Term::class, $terms[0] );

		// check its data.
		$this->assertSame( 'Max Mustermann', $terms[0]->name );
		$this->assertSame( '64', (string) get_term_meta( $terms[0]->term_id, 'api', true ) );
		$this->assertSame( 'Max', get_term_meta( $terms[0]->term_id, 'first_name', true ) );
		$this->assertSame( 'Mustermann', get_term_meta( $terms[0]->term_id, 'last_name', true ) );
		$this->assertSame( 'Max Mustermann', get_term_meta( $terms[0]->term_id, 'name', true ) );
		$this->assertSame( 'max.mustermann@musterdomain.tld', get_term_meta( $terms[0]->term_id, 'public_email', true ) );
		$this->assertSame( '0123 4567890', get_term_meta( $terms[0]->term_id, 'public_cell', true ) );
		$this->assertSame( 'Immobilienberater und Inhaber', get_term_meta( $terms[0]->term_id, 'position', true ) );
	}

	/**
	 * Test that the saved API response contains the broker and the images of the object.
	 *
	 * @return void
	 */
	public function test_api_response_contains_broker_and_images(): void {
		$api_response = $this->import_object()->get_api_response();

		$this->assertSame( 'Max Mustermann', $api_response['broker']['name'] );
		$this->assertCount( 2, $api_response['images'] );
		$this->assertSame( 'https://images.propstack.de/photos/big_musterbild-1.png', $api_response['images'][0]['urls']['large'] );
	}

	/**
	 * Test that the objects are requested with the total count.
	 *
	 * @return void
	 */
	public function test_objects_are_requested_with_total_count(): void {
		$this->import_object();

		$this->assertCount( 1, $this->requested_urls );
		$this->assertStringContainsString( 'with_total=true', $this->requested_urls[0] );
		$this->assertStringNotContainsString( 'with_meta', $this->requested_urls[0] );
	}

	/**
	 * Test that the object is imported without its broker, if the API does not deliver the brokers.
	 *
	 * @return void
	 */
	public function test_object_is_imported_without_brokers(): void {
		// the API does not deliver any broker.
		$no_brokers = static fn() => array();
		add_filter( 'cfprop_api_v2_brokers', $no_brokers );

		$immo_object = $this->import_object();

		remove_filter( 'cfprop_api_v2_brokers', $no_brokers );

		// the object exists, but no broker is assigned.
		$this->assertEmpty( wp_get_object_terms( $immo_object->get_id(), Broker::get_instance()->get_name() ) );
		$this->assertSame( 'Etagenheizung', get_post_meta( $immo_object->get_id(), 'heating_type', true ) );
	}
}

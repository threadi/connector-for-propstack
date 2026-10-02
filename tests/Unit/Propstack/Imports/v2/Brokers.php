<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\Imports\v2\Brokers.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\Imports\v2;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;
use WP_HTTP_Requests_Response;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\Imports\v2\Brokers.
 *
 * The shared test case answers the request for the brokers with the fixture "brokers_full.json"
 * if an API key is set, and with the HTTP status 401 if not.
 */
class Brokers extends ConnectorForPropstackTestCase {
	/**
	 * The API URL for brokers.
	 *
	 * @var string
	 */
	private static string $brokers_url = 'https://api.propstack.de/v2/brokers';

	/**
	 * The object to test.
	 *
	 * @var \ConnectorForPropstack\Propstack\Imports\v2\Brokers
	 */
	private \ConnectorForPropstack\Propstack\Imports\v2\Brokers $brokers_obj;

	/**
	 * Counts how many requests for brokers have been sent during a test.
	 *
	 * @var int
	 */
	private int $request_count = 0;

	/**
	 * The amount of brokers the mocked API delivers per page (0 = use the fixture of the shared test case).
	 *
	 * @var int
	 */
	private int $per_page = 0;

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// reset the markers.
		$this->request_count = 0;
		$this->per_page      = 0;

		// set a pseudo-key, so the API answers with the brokers.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// count the requests (and answer them with pages, if a test needs this).
		add_filter( 'pre_http_request', array( $this, 'mock_broker_request' ), 20, 3 );

		// get the object to test.
		$this->brokers_obj = new \ConnectorForPropstack\Propstack\Imports\v2\Brokers();
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_broker_request' ), 20 );

		parent::tear_down();
	}

	/**
	 * Count the requests for brokers and answer them page by page, if a test needs this.
	 *
	 * @param false|array<string,mixed>|WP_Error $result      The filter return value.
	 * @param array<string,mixed>                $parsed_args The request arguments.
	 * @param string                             $url         The requested URL.
	 *
	 * @return false|array<string,mixed>|WP_Error
	 */
	public function mock_broker_request( false|array|WP_Error $result, array $parsed_args, string $url ): false|array|WP_Error {
		// pass through anything that is not a request for brokers.
		if ( 'GET' !== $parsed_args['method'] || ! str_starts_with( $url, self::$brokers_url ) ) {
			return $result;
		}

		// count the request.
		++$this->request_count;

		// use the answer of the shared test case, if no pages are requested by the test.
		if ( 0 === $this->per_page ) {
			return $result;
		}

		// read the page from the URL.
		$query = array();
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$page = isset( $query['page'] ) ? max( 1, absint( $query['page'] ) ) : 1;

		// get all brokers from the fixture.
		$data = json_decode( (string) file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'brokers_full.json' ), true );

		// build the response with the slice of this page.
		$requests_response              = new \WpOrg\Requests\Response();
		$requests_response->status_code = 200;

		return array(
			'http_response' => new WP_HTTP_Requests_Response( $requests_response, '' ),
			'body'          => (string) wp_json_encode(
				array(
					'data'  => array_slice( $data['data'], ( $page - 1 ) * $this->per_page, $this->per_page ),
					'total' => count( $data['data'] ),
				)
			),
		);
	}

	/**
	 * Test that all brokers are delivered, indexed by their ID.
	 *
	 * @return void
	 */
	public function test_brokers_are_indexed_by_their_id(): void {
		$brokers = $this->brokers_obj->get_brokers();

		$this->assertSame( array( 64, 65, 66 ), array_keys( $brokers ) );
		$this->assertSame( 'Mustermann', $brokers[64]['last_name'] );
	}

	/**
	 * Test that the name of a broker is built from its first and last name.
	 *
	 * @return void
	 */
	public function test_name_is_built_from_first_and_last_name(): void {
		$this->assertSame( 'Max Mustermann', $this->brokers_obj->get_broker( 64 )['name'] );
		$this->assertSame( 'Erika Musterfrau', $this->brokers_obj->get_broker( 65 )['name'] );
	}

	/**
	 * Test that a broker without any name gets its email as name.
	 *
	 * @return void
	 */
	public function test_broker_without_name_uses_its_email(): void {
		$this->assertSame( 'kontakt@musterdomain.tld', $this->brokers_obj->get_broker( 66 )['name'] );
	}

	/**
	 * Test that the email is used as public email.
	 *
	 * @return void
	 */
	public function test_email_is_used_as_public_email(): void {
		$this->assertSame( 'max.mustermann@musterdomain.tld', $this->brokers_obj->get_broker( 64 )['public_email'] );
	}

	/**
	 * Test that an unknown broker is delivered as an empty list.
	 *
	 * @return void
	 */
	public function test_unknown_broker_is_empty(): void {
		$this->assertSame( array(), $this->brokers_obj->get_broker( 999 ) );
	}

	/**
	 * Test that the complete broker is added to an object.
	 *
	 * @return void
	 */
	public function test_broker_is_added_to_object(): void {
		$immo_object = $this->brokers_obj->add_broker_to_object(
			array(
				'id'        => 42,
				'broker_id' => 65,
			)
		);

		$this->assertSame( 65, $immo_object['broker_id'] );
		$this->assertSame( 65, $immo_object['broker']['id'] );
		$this->assertSame( 'Erika Musterfrau', $immo_object['broker']['name'] );
		$this->assertSame( 'https://images.propstack.de/photos/musterfoto.png', $immo_object['broker']['avatar_url'] );
	}

	/**
	 * Test that an object with an unknown broker is not changed.
	 *
	 * @return void
	 */
	public function test_object_with_unknown_broker_is_not_changed(): void {
		$immo_object = array(
			'id'        => 42,
			'broker_id' => 999,
		);

		$this->assertSame( $immo_object, $this->brokers_obj->add_broker_to_object( $immo_object ) );
	}

	/**
	 * Test that an object which already contains its broker (as delivered by the API v1) is not changed.
	 *
	 * @return void
	 */
	public function test_object_with_broker_is_not_changed(): void {
		$immo_object = array(
			'id'        => 42,
			'broker_id' => 64,
			'broker'    => array(
				'id'   => 64,
				'name' => 'Given name',
			),
		);

		$this->assertSame( $immo_object, $this->brokers_obj->add_broker_to_object( $immo_object ) );
		$this->assertSame( 0, $this->request_count );
	}

	/**
	 * Test that the API is not requested for an object without a broker.
	 *
	 * @return void
	 */
	public function test_object_without_broker_does_not_request_the_api(): void {
		$immo_object = array( 'id' => 42 );

		$this->assertSame( $immo_object, $this->brokers_obj->add_broker_to_object( $immo_object ) );
		$this->assertSame( 0, $this->request_count );
	}

	/**
	 * Test that the API is requested only once for several objects.
	 *
	 * @return void
	 */
	public function test_api_is_requested_only_once(): void {
		foreach ( array( 64, 65, 64, 999 ) as $broker_id ) {
			$this->brokers_obj->add_broker_to_object( array( 'broker_id' => $broker_id ) );
		}

		$this->assertSame( 1, $this->request_count );
	}

	/**
	 * Test that all pages of brokers are requested.
	 *
	 * @return void
	 */
	public function test_all_pages_are_requested(): void {
		// the API delivers two brokers per page, so two pages are needed for the three brokers.
		$this->per_page = 2;

		$this->assertSame( array( 64, 65, 66 ), array_keys( $this->brokers_obj->get_brokers() ) );
		$this->assertSame( 2, $this->request_count );
	}

	/**
	 * Test that an error of the API does not change the object.
	 *
	 * @return void
	 */
	public function test_api_error_does_not_change_the_object(): void {
		// without a key the API answers with an error.
		update_option( 'propstack_connector_api_key', '' );

		$immo_object = array(
			'id'        => 42,
			'broker_id' => 64,
		);

		$this->assertSame( array(), $this->brokers_obj->get_brokers() );
		$this->assertSame( $immo_object, $this->brokers_obj->add_broker_to_object( $immo_object ) );

		// the API is not requested again for each object.
		$this->assertSame( 1, $this->request_count );
	}
}

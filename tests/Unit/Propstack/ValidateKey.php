<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\Propstack::rest_validate_key().
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Propstack\Propstack;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\Propstack::rest_validate_key().
 */
class ValidateKey extends ConnectorForPropstackTestCase {
	/**
	 * A key with a valid format, which is accepted by the mocked API.
	 *
	 * @var string
	 */
	private static string $valid_key = 'abcdefghijABCDEFGHIJ0123456789abcdefghij';

	/**
	 * A key with a valid format, which is rejected by the mocked API.
	 *
	 * @var string
	 */
	private static string $rejected_key = 'zzzzzzzzzzZZZZZZZZZZ0123456789zzzzzzzzzz';

	/**
	 * The key which is saved before each test.
	 *
	 * @var string
	 */
	private static string $saved_key = 'the-previously-saved-key';

	/**
	 * Number of requests against the API.
	 *
	 * @var int
	 */
	private int $requests = 0;

	/**
	 * Number of requests for the options of object fields (API v2).
	 *
	 * @var int
	 */
	private int $option_requests = 0;

	/**
	 * The HTTP status the mocked API answers with for the options of object fields (API v2).
	 *
	 * @var int
	 */
	private int $options_status = 200;

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// save a known key.
		update_option( 'propstack_connector_api_key', self::$saved_key );

		// mock the API endpoint used for the validation before any other filter could answer it.
		$this->requests        = 0;
		$this->option_requests = 0;
		$this->options_status  = 200;
		add_filter( 'pre_http_request', array( $this, 'mock_validation_request' ), 1, 3 );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_validation_request' ), 1 );
		update_option( 'propstack_connector_api_key', '' );
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Mock the request to validate the API key.
	 *
	 * @param false|array|WP_Error $response    The return value of the filter.
	 * @param array                $parsed_args The used parameters for the request.
	 * @param string               $url         The requested URL.
	 *
	 * @return false|array|WP_Error
	 */
	public function mock_validation_request( false|array|WP_Error $response, array $parsed_args, string $url ): false|array|WP_Error {
		// answer the request for the options of object fields, which is used for the API v2.
		if ( str_starts_with( $url, 'https://api.propstack.de/v2/properties/options' ) ) {
			++$this->option_requests;

			return array(
				'headers'  => array(),
				'body'     => 200 === $this->options_status ? '{"data":{"heating_type":{"GAS_HEATING":"Gas-Heizung"}}}' : '{"status":403,"message":"authorization_error"}',
				'response' => array(
					'code'    => $this->options_status,
					'message' => 200 === $this->options_status ? 'OK' : 'Forbidden',
				),
				'cookies'  => array(),
			);
		}

		// bail if this is not the validation request.
		if ( ! str_starts_with( $url, 'https://api.propstack.de/v1/property_statuses' ) ) {
			return $response;
		}

		++$this->requests;

		// accept only the valid key.
		$code = self::$valid_key === ( $parsed_args['headers']['X-API-KEY'] ?? '' ) ? 200 : 401;

		return array(
			'headers'  => array(),
			'body'     => '[]',
			'response' => array(
				'code'    => $code,
				'message' => 200 === $code ? 'OK' : 'Unauthorized',
			),
			'cookies'  => array(),
		);
	}

	/**
	 * Log in a user with the given role.
	 *
	 * @param string $role The role.
	 *
	 * @return void
	 */
	private function login_as( string $role ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
	}

	/**
	 * Test that an empty value is not handled as an error.
	 *
	 * @return void
	 */
	public function test_empty_value(): void {
		$this->assertSame( array(), Propstack::rest_validate_key( '   ' ) );
		$this->assertSame( self::$saved_key, get_option( 'propstack_connector_api_key' ) );
	}

	/**
	 * Test that a subscriber cannot save a key.
	 *
	 * @return void
	 */
	public function test_subscriber_is_not_allowed(): void {
		$this->login_as( 'subscriber' );

		$result = Propstack::rest_validate_key( self::$valid_key );

		$this->assertSame( 'no_permission', $result['error'] );
		$this->assertNotEmpty( $result['text'] );
		$this->assertSame( self::$saved_key, get_option( 'propstack_connector_api_key' ) );

		// the API must not be requested.
		$this->assertSame( 0, $this->requests );
	}

	/**
	 * Test that an editor cannot save a key.
	 *
	 * @return void
	 */
	public function test_editor_is_not_allowed(): void {
		$this->login_as( 'editor' );

		$result = Propstack::rest_validate_key( self::$valid_key );

		$this->assertSame( 'no_permission', $result['error'] );
		$this->assertSame( self::$saved_key, get_option( 'propstack_connector_api_key' ) );
	}

	/**
	 * Test that a visitor cannot save a key.
	 *
	 * @return void
	 */
	public function test_visitor_is_not_allowed(): void {
		wp_set_current_user( 0 );

		$result = Propstack::rest_validate_key( self::$valid_key );

		$this->assertSame( 'no_permission', $result['error'] );
		$this->assertSame( self::$saved_key, get_option( 'propstack_connector_api_key' ) );
	}

	/**
	 * Test that an administrator can save a valid key.
	 *
	 * @return void
	 */
	public function test_administrator_with_valid_key(): void {
		$this->login_as( 'administrator' );

		$this->assertSame( array(), Propstack::rest_validate_key( ' ' . self::$valid_key . ' ' ) );
		$this->assertSame( self::$valid_key, get_option( 'propstack_connector_api_key' ) );
		$this->assertSame( 1, $this->requests );
	}

	/**
	 * Test that the permission for the options of object fields is not checked with the API v1.
	 *
	 * @return void
	 */
	public function test_options_are_not_checked_with_api_v1(): void {
		$this->login_as( 'administrator' );
		update_option( 'propstack_connector_api_version', 'v1' );
		$this->options_status = 403;

		$this->assertSame( array(), Propstack::rest_validate_key( self::$valid_key ) );
		$this->assertSame( self::$valid_key, get_option( 'propstack_connector_api_key' ) );
		$this->assertSame( 0, $this->option_requests );
	}

	/**
	 * Test that a key with the permission for the options of object fields is saved with the API v2.
	 *
	 * @return void
	 */
	public function test_valid_key_with_api_v2(): void {
		$this->login_as( 'administrator' );
		update_option( 'propstack_connector_api_version', 'v2' );

		$this->assertSame( array(), Propstack::rest_validate_key( self::$valid_key ) );
		$this->assertSame( self::$valid_key, get_option( 'propstack_connector_api_key' ) );
		$this->assertSame( 1, $this->option_requests );
	}

	/**
	 * Test that a key without the permission for the options of object fields is not saved with the API v2.
	 *
	 * @return void
	 */
	public function test_key_without_permission_for_options_with_api_v2(): void {
		$this->login_as( 'administrator' );
		update_option( 'propstack_connector_api_version', 'v2' );
		$this->options_status = 403;

		$result = Propstack::rest_validate_key( self::$valid_key );

		$this->assertSame( 'missing_options_permission', $result['error'] );
		$this->assertSame( self::$saved_key, get_option( 'propstack_connector_api_key' ) );
		$this->assertSame( 1, $this->option_requests );
	}

	/**
	 * Test that a key is saved with the API v2 if the options are not reachable, as this is no missing permission.
	 *
	 * @return void
	 */
	public function test_key_is_saved_with_api_v2_if_options_are_not_reachable(): void {
		$this->login_as( 'administrator' );
		update_option( 'propstack_connector_api_version', 'v2' );
		$this->options_status = 500;

		$this->assertSame( array(), Propstack::rest_validate_key( self::$valid_key ) );
		$this->assertSame( self::$valid_key, get_option( 'propstack_connector_api_key' ) );
	}

	/**
	 * Test that a key rejected by the API is not saved.
	 *
	 * @return void
	 */
	public function test_administrator_with_rejected_key(): void {
		$this->login_as( 'administrator' );

		$result = Propstack::rest_validate_key( self::$rejected_key );

		$this->assertSame( 'invalid', $result['error'] );
		$this->assertSame( self::$saved_key, get_option( 'propstack_connector_api_key' ) );
	}

	/**
	 * Test that keys with a wrong format are not saved and not sent to the API.
	 *
	 * @return void
	 */
	public function test_administrator_with_wrong_format(): void {
		$this->login_as( 'administrator' );

		// too short.
		$this->assertSame( 'short', Propstack::rest_validate_key( 'abc' )['error'] );

		// not allowed characters.
		$this->assertSame( 'character_error', Propstack::rest_validate_key( str_repeat( 'a', 39 ) . '"' )['error'] );

		$this->assertSame( self::$saved_key, get_option( 'propstack_connector_api_key' ) );
		$this->assertSame( 0, $this->requests );
	}
}

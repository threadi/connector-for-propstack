<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\ApiRequest.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Error;
use WP_HTTP_Requests_Response;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\ApiRequest.
 *
 * The mock of this class answers a URL which is not used by any import. It returns the
 * responses of the list $responses one after another and captures the arguments of every
 * request.
 */
class ApiRequest extends ConnectorForPropstackTestCase {
	/**
	 * The URL used for the requests of these tests.
	 *
	 * @var string
	 */
	private static string $test_url = 'https://api.propstack.de/v2/imp_api_request_test';

	/**
	 * The responses the mock returns one after another (status and headers).
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $responses = array();

	/**
	 * The arguments of each request the mock received.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $captured_args = array();

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// reset the mock state.
		$this->responses     = array();
		$this->captured_args = array();

		// register the mock.
		add_filter( 'pre_http_request', array( $this, 'mock_request' ), 5, 3 );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_request' ), 5 );

		parent::tear_down();
	}

	/**
	 * Mock for the test URL.
	 *
	 * @param false|array<string,mixed>|WP_Error $result      The filter return value.
	 * @param array<string,mixed>                $parsed_args The request arguments.
	 * @param string                             $url         The requested URL.
	 *
	 * @return false|array<string,mixed>|WP_Error
	 */
	public function mock_request( false|array|WP_Error $result, array $parsed_args, string $url ): false|array|WP_Error {
		// pass through anything that is not our test URL.
		if ( ! str_starts_with( $url, self::$test_url ) ) {
			return $result;
		}

		// capture the arguments.
		$this->captured_args[] = $parsed_args;

		// get the next response, the last one is repeated.
		$index    = min( count( $this->captured_args ), count( $this->responses ) ) - 1;
		$response = $this->responses[ max( 0, $index ) ] ?? array( 'status' => 200 );

		// create the response object.
		$requests_response              = new \WpOrg\Requests\Response();
		$requests_response->status_code = absint( $response['status'] );

		return array(
			'http_response' => new WP_HTTP_Requests_Response( $requests_response, '' ),
			'headers'       => $response['headers'] ?? array(),
			'body'          => (string) wp_json_encode( array( 'data' => array() ) ),
			'response'      => array(
				'code'    => absint( $response['status'] ),
				'message' => '',
			),
		);
	}

	/**
	 * Create and send a GET request to the test URL.
	 *
	 * @return \ConnectorForPropstack\Propstack\ApiRequest
	 */
	private function send_request(): \ConnectorForPropstack\Propstack\ApiRequest {
		$request_object = new \ConnectorForPropstack\Propstack\ApiRequest();
		$request_object->set_url( self::$test_url );
		$request_object->set_post_data( '' );
		$request_object->set_method( 'GET' );
		$request_object->set_header( array( 'X-API-KEY' => self::$api_key ) );
		$request_object->send();

		return $request_object;
	}

	/**
	 * Return the texts of the errors of the given request.
	 *
	 * Hint: ApiRequest saves its texts as error data without an error code.
	 *
	 * @param \ConnectorForPropstack\Propstack\ApiRequest $request_object The request object.
	 *
	 * @return string
	 */
	private function get_error_texts( \ConnectorForPropstack\Propstack\ApiRequest $request_object ): string {
		$texts = array();
		foreach ( $request_object->get_errors() as $error ) {
			$texts[] = $error->get_error_message();
		}

		return implode( "\n", $texts );
	}

	/**
	 * Test that redirects are not followed, as they would forward the API key.
	 *
	 * @return void
	 */
	public function test_redirects_are_not_followed(): void {
		$this->responses = array( array( 'status' => 200 ) );

		$request_object = $this->send_request();

		$this->assertCount( 1, $this->captured_args );
		$this->assertArrayHasKey( 'redirection', $this->captured_args[0] );
		$this->assertSame( 0, $this->captured_args[0]['redirection'] );

		// a successful request has no errors.
		$this->assertSame( 200, $request_object->get_http_status() );
		$this->assertEmpty( $request_object->get_errors() );
	}

	/**
	 * Test that a redirect response results in an error.
	 *
	 * @return void
	 */
	public function test_redirect_response_results_in_an_error(): void {
		$this->responses = array(
			array(
				'status'  => 301,
				'headers' => array( 'location' => 'https://example.com/' ),
			),
		);

		$request_object = $this->send_request();

		// the redirect is not followed and not retried.
		$this->assertCount( 1, $this->captured_args );
		$this->assertSame( 301, $request_object->get_http_status() );
		$this->assertCount( 1, $request_object->get_errors() );
		$this->assertStringContainsString( 'redirect', $this->get_error_texts( $request_object ) );
	}

	/**
	 * Test that a 429 with a short "retry-after" is retried exactly once.
	 *
	 * @return void
	 */
	public function test_too_many_requests_with_retry_after_is_retried_once(): void {
		$this->responses = array(
			array(
				'status'  => 429,
				'headers' => array( 'retry-after' => '0' ),
			),
			array( 'status' => 200 ),
		);

		$request_object = $this->send_request();

		$this->assertCount( 2, $this->captured_args );
		$this->assertSame( 200, $request_object->get_http_status() );
		$this->assertEmpty( $request_object->get_errors() );
	}

	/**
	 * Test that a repeated 429 is not retried more than once.
	 *
	 * @return void
	 */
	public function test_repeated_too_many_requests_is_retried_only_once(): void {
		$this->responses = array(
			array(
				'status'  => 429,
				'headers' => array( 'retry-after' => '0' ),
			),
		);

		$request_object = $this->send_request();

		$this->assertCount( 2, $this->captured_args );
		$this->assertSame( 429, $request_object->get_http_status() );
	}

	/**
	 * Test that a 429 without "retry-after" is not retried.
	 *
	 * @return void
	 */
	public function test_too_many_requests_without_retry_after_is_not_retried(): void {
		$this->responses = array(
			array( 'status' => 429 ),
			array( 'status' => 200 ),
		);

		$request_object = $this->send_request();

		$this->assertCount( 1, $this->captured_args );
		$this->assertSame( 429, $request_object->get_http_status() );
	}

	/**
	 * Test that a 429 with a long "retry-after" is not retried (the request would block too long).
	 *
	 * @return void
	 */
	public function test_too_many_requests_with_long_retry_after_is_not_retried(): void {
		$this->responses = array(
			array(
				'status'  => 429,
				'headers' => array( 'retry-after' => '60' ),
			),
			array( 'status' => 200 ),
		);

		$request_object = $this->send_request();

		$this->assertCount( 1, $this->captured_args );
		$this->assertSame( 429, $request_object->get_http_status() );
	}

	/**
	 * Test that a failing request results in an error and no HTTP status.
	 *
	 * @return void
	 */
	public function test_wp_error_response_results_in_an_error(): void {
		$return_error = static fn() => new WP_Error( 'http_request_failed', 'cURL error 28: timeout' );
		add_filter( 'pre_http_request', $return_error, 1 );
		remove_filter( 'pre_http_request', array( $this, 'mock_request' ), 5 );

		$request_object = new \ConnectorForPropstack\Propstack\ApiRequest();
		$request_object->set_url( self::$test_url );
		$request_object->set_post_data( '' );
		$request_object->set_method( 'GET' );

		$result = $request_object->send();

		remove_filter( 'pre_http_request', $return_error, 1 );

		$this->assertFalse( $result );
		$this->assertSame( -1, $request_object->get_http_status() );
		$this->assertCount( 1, $request_object->get_errors() );
		$this->assertStringContainsString( 'timeout', $this->get_error_texts( $request_object ) );
	}

	/**
	 * Test that the errors of a request are complete WP_Error objects with code and message.
	 *
	 * @return void
	 */
	public function test_errors_have_code_and_message(): void {
		$this->responses = array(
			array(
				'status'  => 301,
				'headers' => array( 'location' => 'https://example.com/' ),
			),
		);

		$request_object = $this->send_request();

		$errors = $request_object->get_errors();
		$this->assertCount( 1, $errors );
		$this->assertTrue( $errors[0]->has_errors() );
		$this->assertSame( 'propstack_api_request_error', $errors[0]->get_error_code() );
		$this->assertNotEmpty( $errors[0]->get_error_message() );
	}
}

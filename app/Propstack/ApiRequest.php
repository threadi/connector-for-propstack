<?php
/**
 * File for handling any requests to the Propstack API.
 *
 * @source https://docs.propstack.de
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Log;
use WP_Error;

/**
 * Send a single request with given data.
 * Used for each request to Propstack API.
 */
class ApiRequest {
	/**
	 * The list of errors.
	 *
	 * @var array<int,WP_Error>
	 */
	private array $errors = array();

	/**
	 * The URL for the request.
	 *
	 * @var string
	 */
	private string $url;

	/**
	 * The method to use for the request.
	 *
	 * @var string
	 */
	private string $method = 'POST';

	/**
	 * Set default http header.
	 *
	 * @var array<string,string>
	 */
	private array $header = array();

	/**
	 * The HTTP-Post-data as JSON- or boundary-string.
	 *
	 * @var string|array<string,mixed>
	 */
	private string|array $post_data;

	/**
	 * The response.
	 *
	 * @var string
	 */
	private string $response = '';

	/**
	 * The http-status.
	 *
	 * @var int
	 */
	private int $http_status = -1;

	/**
	 * The md5 hash of the transferred object.
	 *
	 * @var string
	 */
	private string $md5 = '';

	/**
	 * Constructor to build this object.
	 */
	public function __construct() {}

	/**
	 * Set URL.
	 *
	 * @param string $url The url to request.
	 * @return void
	 */
	public function set_url( string $url ): void {
		$this->url = $url;
	}

	/**
	 * Set header for request additional to authentication-header that is set by this object.
	 *
	 * @param array<string,string> $header List of headers.
	 * @return void
	 */
	public function set_header( array $header ): void {
		$this->header = $header;
	}

	/**
	 * Set post-data for the request.
	 *
	 * @param string|array<string,mixed> $post_data The post-data as JSON- or boundary-string OR as an array.
	 * @return void
	 */
	public function set_post_data( string|array $post_data ): void {
		$this->post_data = $post_data;
	}

	/**
	 * Send the request and collect the result in this object.
	 * Do not interpret anything of the response.
	 *
	 * @return bool
	 */
	public function send(): bool {
		// get the header-array.
		$headers = $this->header;

		$instance = $this;
		/**
		 * Filter the headers for the request.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param array<string,string> $headers List of headers.
		 * @param ApiRequest $instance The ApiRequest-object.
		 */
		$headers = apply_filters( 'cfprop_request_header', $headers, $instance );

		// collect arguments for request.
		$args = array(
			'method'      => $this->get_method(),
			'headers'     => $headers,
			'httpversion' => '1.1',
			'timeout'     => get_option( 'propstack_connector_timeout', 30 ),
			'redirection' => 0, // do not follow redirects, as they would forward the API key to the target.
			'body'        => $this->get_post_data(),
		);

		// send the request.
		$response = $this->send_request( $args );

		// send it a second time if the API asks for a short break ("too many requests").
		$retry_after = $this->get_retry_after( $response );
		if ( $retry_after >= 0 ) {
			sleep( $retry_after );
			$response = $this->send_request( $args );
		}

		// bail if the response is false.
		if ( false === $response ) {
			// add the event in the log.
			$this->add_error( __( 'Got no response from Propstack API.', 'connector-for-propstack' ) );

			// return false as request resulted in unspecific http error.
			return false;
		}

		// bail on error.
		if ( is_wp_error( $response ) ) {
			// add an event in the log.
			$this->add_error( __( 'The request to the Propstack API ended in an error: ', 'connector-for-propstack' ) . Helper::get_json( $response ) );

			// return false as request resulted in unspecific http error.
			return false;
		}

		// secure response.
		$this->set_response( wp_remote_retrieve_body( $response ) );

		// secure the HTTP status from the response.
		$this->set_http_status( $this->get_status_code( $response ) );

		// note a redirect, it is not followed and must be handled as error by the caller.
		if ( $this->get_http_status() >= 300 && $this->get_http_status() < 400 ) {
			$this->add_error( __( 'The Propstack API answered with a redirect, which is not followed for security reasons.', 'connector-for-propstack' ) );
		}

		// clean arguments from sensitive data.
		$args['headers']['X-API-KEY'] = 'anonymized';

		$log_text  = __( 'URL:', 'connector-for-propstack' ) . ' <code>' . esc_url( $this->get_url() ) . '</code>';
		$log_text .= '<br><br>' . __( 'Request:', 'connector-for-propstack' ) . ' <code>' . wp_json_encode( $args ) . '</code>';
		$log_text .= '<br><br>' . __( 'HTTP-Status:', 'connector-for-propstack' ) . ' <code>' . wp_json_encode( $this->get_http_status() ) . '</code>';

		// get the raw response and limit its length for the log entry.
		$response   = $this->get_response();
		$max_length = absint( apply_filters( 'cfprop_log_max_response_length', 100000 ) );
		if ( strlen( $response ) > $max_length ) {
			/* translators: %1$d will be replaced by the total length in bytes. */
			$response = substr( $response, 0, $max_length ) . ' … ' . sprintf( __( '[truncated, %1$d bytes in total]', 'connector-for-propstack' ), strlen( $response ) );
		}
		$log_text .= '<br><br>' . __( 'Response:', 'connector-for-propstack' ) . ' <code>' . esc_html( $response ) . '</code>';
		Log::get_instance()->add( $log_text, 'info', 'import', $this->get_md5() );

		// return true as the request itself was successful.
		return true;
	}

	/**
	 * Return the response we got from the request.
	 *
	 * @return string
	 */
	public function get_response(): string {
		return $this->response;
	}

	/**
	 * Set the response of the request.
	 *
	 * @param string $response The response.
	 *
	 * @return void
	 */
	private function set_response( string $response ): void {
		$this->response = $response;
	}

	/**
	 * Return the http-status of this request.
	 *
	 * @return int
	 */
	public function get_http_status(): int {
		return $this->http_status;
	}

	/**
	 * Set the HTTP status.
	 *
	 * @param int $http_status The HTTP status.
	 *
	 * @return void
	 */
	private function set_http_status( int $http_status ): void {
		$this->http_status = $http_status;
	}

	/**
	 * Return the URL for the request.
	 *
	 * @return string
	 */
	private function get_url(): string {
		return $this->url;
	}

	/**
	 * Return the POST-data.
	 *
	 * @return string|array<string,mixed>
	 */
	private function get_post_data(): string|array {
		return $this->post_data;
	}

	/**
	 * Return the method to use for this request.
	 * *
	 *
	 * @return string
	 */
	public function get_method(): string {
		return $this->method;
	}

	/**
	 * Set the method to use for this request.
	 *
	 * @param string $method The method (must be GET or POST).
	 *
	 * @return void
	 */
	public function set_method( string $method ): void {
		if ( ! in_array( $method, array( 'POST', 'GET' ), true ) ) {
			return;
		}
		$this->method = $method;
	}

	/**
	 * Set md5 hash for the transferred object.
	 *
	 * @param string $md5 The md5 hash.
	 *
	 * @return void
	 */
	public function set_md5( string $md5 ): void {
		$this->md5 = $md5;
	}

	/**
	 * Return the md5 hash.
	 *
	 * @return string
	 */
	private function get_md5(): string {
		return $this->md5;
	}

	/**
	 * Add an error with the given text to the list of errors.
	 *
	 * @param string $text The error text.
	 *
	 * @return void
	 */
	private function add_error( string $text ): void {
		$error = new WP_Error();
		$error->add_data( $text );
		$this->errors[] = $error;
	}

	/**
	 * Return the list of errors.
	 *
	 * @return array<int,WP_Error>
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Send the request with the given arguments.
	 *
	 * @param array<string,mixed> $args The arguments for the request.
	 *
	 * @return array<string,mixed>|WP_Error|false
	 */
	private function send_request( array $args ): array|WP_Error|false {
		switch ( $this->get_method() ) {
			case 'GET':
				return wp_remote_get( $this->get_url(), $args );
			case 'POST':
				return wp_remote_post( $this->get_url(), $args );
		}

		return false;
	}

	/**
	 * Return the seconds to wait before the request could be retried.
	 *
	 * Returns -1 if the request should not be retried: if it is not a "too many requests" answer
	 * or if the API does not tell us a short time to wait (max. 10 seconds).
	 *
	 * @param array<string,mixed>|WP_Error|false $response The response.
	 *
	 * @return int
	 */
	private function get_retry_after( array|WP_Error|false $response ): int {
		// bail if this is not a valid response.
		if ( ! is_array( $response ) ) {
			return -1;
		}

		// bail if this is not a "too many requests" answer.
		if ( 429 !== $this->get_status_code( $response ) ) {
			return -1;
		}

		// bail if the API does not tell us a short time to wait (in seconds).
		$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
		if ( ! is_numeric( $retry_after ) || absint( $retry_after ) > 10 ) {
			return -1;
		}

		// return the seconds to wait.
		return absint( $retry_after );
	}

	/**
	 * Return the HTTP status code of the given response.
	 *
	 * @param array<string,mixed> $response The response.
	 *
	 * @return int
	 */
	private function get_status_code( array $response ): int {
		// use the response object, if given.
		$http_response = $response['http_response'] ?? null;
		if ( $http_response instanceof \WP_HTTP_Requests_Response ) {
			return absint( $http_response->get_status() );
		}

		// otherwise, use the code from the response array.
		return absint( wp_remote_retrieve_response_code( $response ) );
	}
}

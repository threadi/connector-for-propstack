<?php
/**
 * File to handle the main object for each test class.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests;

use WP_Error;
use WP_HTTP_Requests_Response;
use WP_UnitTestCase;

/**
 * Object to handle the preparations for each test class.
 */
abstract class ConnectorForPropstackTestCase extends WP_UnitTestCase {
	/**
	 * The API status URL.
	 *
	 * @var string
	 */
	private static string $units_url = 'https://api.propstack.de/v1/units';

	/**
	 * The test API key.
	 *
	 * @var string
	 */
	protected static string $api_key = 'test_api_key';

	/**
	 * Prepare the test environment for each test class.
	 *
	 * @return void
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		// prepare to load just one time.
		if ( ! did_action('propstack_connector_test_preparation_loaded') ) {
			// Plugin initialisieren
			\ConnectorForPropstack\Plugin\Installer::get_instance()->activation();

			// run initialization.
			do_action( 'init' );

			// prevent external requests from Propstack APIs.
			add_filter( 'pre_http_request', array( self::class, 'add_url_filter' ), 10, 3 );

			// mark as loaded.
			do_action('propstack_connector_test_preparation_loaded');
		}
	}

	/**
	 * Define the URL filter for external requests to prevent any external requests for selected URLs.
	 *
	 * @param false|array|WP_Error $false The return value of the filter.
	 * @param array $parsed_args The used parameters for the request.
	 * @param string $url The requested URL.
	 *
	 * @return false|array|WP_Error
	 */
	public static function add_url_filter( false|array|WP_Error $false, array $parsed_args, string $url ): false|array|WP_Error {
		// create a local response for the GET request for immo objects.
		if ( 'GET' === $parsed_args['method'] && str_starts_with( $url, self::$units_url ) ) {
			// if the API key is missing.
			if ( empty( $parsed_args['headers']['X-API-KEY'] ) ) {
				// create the response object.
				$requests_response              = new \WpOrg\Requests\Response();
				$requests_response->status_code = 401;

				// create the header response.
				return array(
					'http_response' => new WP_HTTP_Requests_Response( $requests_response, $parsed_args['filename'] ),
				);
			}
			// if the API key is correct and no faulty response is set.
			if ( ! isset( $parsed_args['headers']['faulty_response'] ) && $parsed_args['headers']['X-API-KEY'] === self::$api_key ) {
				// get our XML file and return its content.
				$xml = \ConnectorForPropstack\Plugin\Helper::get_wp_filesystem()->get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'units_full.json' );

				// create the response object.
				$requests_response              = new \WpOrg\Requests\Response();
				$requests_response->status_code = isset( $parsed_args['headers']['response_http_status'] ) ? $parsed_args['headers']['response_http_status'] : 200;

				// create the header response.
				return array(
					'http_response' => new WP_HTTP_Requests_Response( $requests_response, $parsed_args['filename'] ),
					'body'          => $xml
				);
			}

			// if the API key is correct and a faulty response is set.
			if ( isset( $parsed_args['headers']['faulty_response'] ) && $parsed_args['headers']['X-API-KEY'] === self::$api_key ) {
				// get our XML file and return its content.
				$xml = \ConnectorForPropstack\Plugin\Helper::get_wp_filesystem()->get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'units_faulty.json' );

				// create the response object.
				$requests_response              = new \WpOrg\Requests\Response();
				$requests_response->status_code = isset( $parsed_args['headers']['response_http_status'] ) ? $parsed_args['headers']['response_http_status'] : 200;

				// create the header response.
				return array(
					'http_response' => new WP_HTTP_Requests_Response( $requests_response, $parsed_args['filename'] ),
					'body'          => $xml
				);
			}
		}

		// return the given value.
		return $false;
	}
}

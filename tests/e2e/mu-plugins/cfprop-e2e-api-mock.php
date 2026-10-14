<?php
/**
 * Plugin Name: Connector for Propstack - E2E API mock
 * Description: Answers all requests to the Propstack API with the test data of the plugin. Only for the E2E tests, never use it on a real website.
 *
 * @package connector-for-propstack
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Answer requests to the Propstack API with the JSON files in tests/Data of the plugin.
 *
 * Every other external request is blocked, so the tests never depend on the network.
 *
 * @param false|array<string,mixed>|WP_Error $response The response (false to run the request).
 * @param array<string,mixed>                $args     The request arguments.
 * @param string                             $url      The requested URL.
 *
 * @return false|array<string,mixed>|WP_Error
 */
function cfprop_e2e_api_mock( false|array|WP_Error $response, array $args, string $url ): false|array|WP_Error {
	// bail if another filter already answered.
	if ( false !== $response ) {
		return $response;
	}

	// the test data of the plugin.
	$data_dir = WP_PLUGIN_DIR . '/connector-for-propstack/tests/Data/';

	// the API endpoints and their test data.
	$endpoints = array(
		'https://api.propstack.de/v1/units'              => 'units_full.json',
		'https://api.propstack.de/v2/property_statuses'  => 'states_full.json',
		'https://api.propstack.de/v2/brokers'            => 'brokers_full.json',
		'https://api.propstack.de/v2/properties/options' => 'property_options_full.json',
		'https://api.propstack.de/v2/properties'         => 'properties_full.json',
	);

	foreach ( $endpoints as $endpoint => $file ) {
		// bail if this is not the requested endpoint.
		if ( ! str_starts_with( $url, $endpoint ) ) {
			continue;
		}

		// the API key is required like in the real API.
		if ( empty( $args['headers']['X-API-KEY'] ) ) {
			return array(
				'headers'  => array(),
				'body'     => '',
				'response' => array(
					'code'    => 401,
					'message' => 'Unauthorized',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		}

		// return the test data.
		return array(
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => (string) file_get_contents( $data_dir . $file ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	// block every other request to external hosts.
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	if ( '' !== $host && ! in_array( $host, array( 'localhost', '127.0.0.1', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ), true ) ) {
		return new WP_Error( 'cfprop_e2e_blocked', 'External requests are blocked during the E2E tests: ' . $url );
	}

	// let all other requests pass.
	return false;
}
add_filter( 'pre_http_request', 'cfprop_e2e_api_mock', 10, 3 );

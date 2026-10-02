<?php
/**
 * File to handle the brokers from Propstack via API v2.
 *
 * The API v1 delivers the complete broker as part of each object.
 * The API v2 delivers only the ID of the broker. We get the brokers with an own request,
 * so we are able to add the complete broker to each object as the API v1 does.
 *
 * @source https://api.propstack.de/docs/index.html
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Imports\v2;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Propstack\ApiRequest;
use ConnectorForPropstack\Propstack\Import_Base;

/**
 * Object to get the brokers from Propstack API and to add them to objects.
 */
class Brokers extends Import_Base {
	/**
	 * The URL of the Propstack API to get the brokers.
	 *
	 * @var string
	 */
	private string $url = 'https://api.propstack.de/v2/brokers';

	/**
	 * The brokers we got from the API, indexed by their ID (null if they are not requested until now).
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	private ?array $brokers = null;

	/**
	 * Initialize this object.
	 */
	public function __construct() {}

	/**
	 * Return all brokers from the API, indexed by their ID.
	 *
	 * They are requested once per object of this class. Each broker is delivered in the structure
	 * the API v1 uses for the broker of an object.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_brokers(): array {
		// request the brokers, if this has not been done until now.
		if ( null === $this->brokers ) {
			$this->brokers = $this->request_brokers();
		}

		// return the brokers.
		return $this->brokers;
	}

	/**
	 * Return a single broker by its ID.
	 *
	 * @param int $broker_id The ID of the broker.
	 *
	 * @return array<string,mixed> The broker or an empty array if the API did not deliver it.
	 */
	public function get_broker( int $broker_id ): array {
		// get all brokers.
		$brokers = $this->get_brokers();

		// return this broker, if it is known.
		return $brokers[ $broker_id ] ?? array();
	}

	/**
	 * Add the complete broker to the given object, as the API v1 delivers it.
	 *
	 * @param array<string,mixed> $immo_object The object data from API.
	 *
	 * @return array<string,mixed>
	 */
	public function add_broker_to_object( array $immo_object ): array {
		// bail if the object already contains its broker.
		if ( ! empty( $immo_object['broker'] ) ) {
			return $immo_object;
		}

		// bail if the object has no broker.
		if ( empty( $immo_object['broker_id'] ) || ! is_scalar( $immo_object['broker_id'] ) ) {
			return $immo_object;
		}

		// get the broker.
		$broker = $this->get_broker( absint( $immo_object['broker_id'] ) );

		// bail if the API did not deliver this broker.
		if ( empty( $broker ) ) {
			return $immo_object;
		}

		// add the broker to the object.
		$immo_object['broker'] = $broker;

		// return the resulting object.
		return $immo_object;
	}

	/**
	 * Request all brokers from the API, page by page.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function request_brokers(): array {
		// prepare the list of brokers.
		$brokers = array();

		// the API delivers max. 1000 brokers per page.
		$per       = 1000;
		$page      = 1;
		$max_pages = 100;
		$total     = null;
		$collected = 0;

		do {
			// create and send the API request.
			$request_object = new ApiRequest();
			$request_object->set_url( $this->get_url( $page, $per ) );
			$request_object->set_post_data( '' );
			$request_object->set_method( 'GET' );
			$request_object->set_md5( md5( $this->get_url( $page, $per ) ) );
			$request_object->set_header( $this->get_header() );
			$request_object->send();

			// bail on error.
			if ( 200 !== $request_object->get_http_status() ) {
				// add a log entry.
				/* translators: %1$s will be replaced by the HTTP status, %2$s by a URL. */
				Log::get_instance()->add( sprintf( __( 'The brokers could not be loaded from Propstack (HTTP status %1$s). The objects are imported without the data of their brokers. Please check whether <a href="%2$s" target="_blank">your API key</a> has the permission to read brokers.', 'connector-for-propstack' ), '<code>' . $request_object->get_http_status() . '</code>', esc_url( Helper::get_propstack_api_page_url() ) ), 'error', 'import' );

				// use the brokers we got until now.
				break;
			}

			// convert the response to an array.
			$data = json_decode( $request_object->get_response(), true );

			// bail if no list of brokers is given.
			if ( ! is_array( $data ) || ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
				// add a log entry.
				Log::get_instance()->add( __( 'Propstack answered with unexpected data for the brokers. The objects are imported without the data of their brokers.', 'connector-for-propstack' ), 'error', 'import' );

				// use the brokers we got until now.
				break;
			}

			// read the total count once (from the first page).
			if ( null === $total && isset( $data['total'] ) ) {
				$total = absint( $data['total'] );
			}

			// add each broker of this page to the list.
			$data_count = count( $data['data'] );
			foreach ( $data['data'] as $broker ) {
				// bail if this is not a broker.
				if ( ! is_array( $broker ) || empty( $broker['id'] ) ) {
					continue;
				}

				$brokers[ absint( $broker['id'] ) ] = $this->prepare_broker( $broker );
			}
			$collected += $data_count;

			++$page;
		} while (
			$page <= $max_pages
			&& $data_count > 0
			&& (
				( null !== $total && $collected < $total )
				|| ( null === $total && $data_count >= $per )
			)
		);

		/**
		 * Filter the brokers from Propstack API v2.
		 *
		 * @since 2.0.1 Available since 2.0.1.
		 * @param array<int,array<string,mixed>> $brokers The brokers, indexed by their ID.
		 */
		return apply_filters( 'cfprop_api_v2_brokers', $brokers );
	}

	/**
	 * Bring a broker from the API v2 into the structure the API v1 uses for the broker of an object.
	 *
	 * The API v2 does not deliver the full name and the public email of a broker:
	 * - the name is built from the first and the last name.
	 * - the email is used as public email.
	 *
	 * @param array<string,mixed> $broker The broker from API.
	 *
	 * @return array<string,mixed>
	 */
	private function prepare_broker( array $broker ): array {
		// build the name, if it is not given.
		if ( empty( $broker['name'] ) ) {
			// collect the parts of the name.
			$parts = array();
			foreach ( array( 'first_name', 'last_name' ) as $part ) {
				if ( ! empty( $broker[ $part ] ) && is_string( $broker[ $part ] ) ) {
					$parts[] = trim( $broker[ $part ] );
				}
			}
			$name = trim( implode( ' ', $parts ) );

			// use the email or the ID, if the broker has no name (a broker without a name could not be saved).
			if ( '' === $name ) {
				$name = ( ! empty( $broker['email'] ) && is_string( $broker['email'] ) ) ? $broker['email'] : (string) absint( $broker['id'] );
			}

			$broker['name'] = $name;
		}

		// use the email as public email, if no public email is given.
		if ( empty( $broker['public_email'] ) && ! empty( $broker['email'] ) ) {
			$broker['public_email'] = $broker['email'];
		}

		// return the resulting broker.
		return $broker;
	}

	/**
	 * Return the API URL to get the brokers.
	 *
	 * @param int $page The page to request.
	 * @param int $per The amount of brokers per page.
	 *
	 * @return string
	 */
	private function get_url( int $page, int $per ): string {
		// get the URL.
		$url = add_query_arg(
			array(
				'with_total' => 'true',
				'page'       => $page,
				'per'        => $per,
			),
			$this->url
		);

		/**
		 * Filter the URL of the API to get the brokers from Propstack via API v2.
		 *
		 * @since 2.0.1 Available since 2.0.1.
		 * @param string $url The URL.
		 */
		return apply_filters( 'cfprop_api_v2_broker_url', $url );
	}
}

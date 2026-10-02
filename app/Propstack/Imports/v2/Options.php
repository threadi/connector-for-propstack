<?php
/**
 * File to handle the options of object fields from Propstack via API v2.
 *
 * The API v1 delivers the value of a selection field as text (e.g. "Gas-Heizung").
 * The API v2 delivers its code (e.g. "GAS_HEATING"). The options contain the text for each code,
 * so we are able to save the same values as with the API v1.
 *
 * @source https://api.propstack.de/docs/index.html
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Imports\v2;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Languages;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Propstack\ApiRequest;
use ConnectorForPropstack\Propstack\Import_Base;
use ConnectorForPropstack\Propstack\Taxonomies;

/**
 * Object to get the options of object fields from Propstack API and to use them on objects.
 */
class Options extends Import_Base {
	/**
	 * The URL of the Propstack API to get the options.
	 *
	 * @var string
	 */
	private string $url = 'https://api.propstack.de/v2/properties/options';

	/**
	 * The prefix of the option, which holds the last options we got from the API for a language.
	 *
	 * @var string
	 */
	public const OPTION_PREFIX = 'cfprop_api_v2_options_';

	/**
	 * The options, which are already loaded in this request, per language.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private array $options = array();

	/**
	 * Initialize this object.
	 */
	public function __construct() {}

	/**
	 * Return the options for the given language.
	 *
	 * They are requested from the API once per object of this class. If the API does not deliver them,
	 * the options of the last successful request are used. If there are none, the options delivered
	 * with this plugin are used (if available for the language).
	 *
	 * Format: the name of the field in the API => list of codes with their texts.
	 *
	 * @param string $language_code The language to use.
	 *
	 * @return array<string,mixed>
	 */
	public function get_options( string $language_code ): array {
		// return the options if they are already loaded.
		if ( isset( $this->options[ $language_code ] ) ) {
			return $this->options[ $language_code ];
		}

		// get the actual options from the API.
		$options = $this->request_options( $language_code );

		if ( ! empty( $options ) ) {
			// save them as reserve for the next import (not autoloaded, they are only needed during an import).
			update_option( self::OPTION_PREFIX . $language_code, $options, false );
		} else {
			// use the options of the last successful request.
			$options = get_option( self::OPTION_PREFIX . $language_code, array() );
			if ( ! is_array( $options ) ) {
				$options = array();
			}

			// use the options delivered with this plugin.
			if ( empty( $options ) ) {
				$options = $this->get_delivered_options( $language_code );
			}

			// add a log entry.
			if ( empty( $options ) ) {
				/* translators: %1$s will be replaced by the language. */
				Log::get_instance()->add( sprintf( __( 'The texts for the values of selection fields could not be loaded from Propstack for the language %1$s. These fields will show the codes Propstack delivers (e.g. for the heating type).', 'connector-for-propstack' ), '<em>' . esc_html( $language_code ) . '</em>' ), 'error', 'import' );
			} else {
				/* translators: %1$s will be replaced by the language. */
				Log::get_instance()->add( sprintf( __( 'The texts for the values of selection fields could not be loaded from Propstack for the language %1$s. The last known texts are used instead.', 'connector-for-propstack' ), '<em>' . esc_html( $language_code ) . '</em>' ), 'info', 'import' );
			}
		}

		/**
		 * Filter the options of object fields from Propstack API v2.
		 *
		 * @since 2.0.1 Available since 2.0.1.
		 * @param array<string,mixed> $options       The options: the name of the field in the API => list of codes with their texts.
		 * @param string              $language_code The used language.
		 */
		$options = apply_filters( 'cfprop_api_v2_options', $options, $language_code );

		// remember them for this request.
		$this->options[ $language_code ] = is_array( $options ) ? $options : array(); // @phpstan-ignore function.alreadyNarrowedType

		// return the options.
		return $this->options[ $language_code ];
	}

	/**
	 * Replace the codes of selection fields in the given object by their texts.
	 *
	 * Fields, which are used to assign an object to a term (e.g. the marketing type), keep their code.
	 *
	 * @param array<string,mixed> $immo_object   The object data from API.
	 * @param string              $language_code The language to use.
	 *
	 * @return array<string,mixed>
	 */
	public function translate_object( array $immo_object, string $language_code ): array {
		// get the options.
		$options = $this->get_options( $language_code );

		// bail if no options are available.
		if ( empty( $options ) ) {
			return $immo_object;
		}

		// get the fields which must keep their codes.
		$untranslated_fields = $this->get_untranslated_fields();

		// replace the code in each field we have texts for.
		foreach ( $options as $field_name => $texts ) {
			// bail if this field has no texts, is not part of the object or must keep its code.
			if ( ! is_array( $texts ) || ! isset( $immo_object[ $field_name ] ) || in_array( $field_name, $untranslated_fields, true ) ) {
				continue;
			}

			$immo_object[ $field_name ] = $this->translate_value( $immo_object[ $field_name ], $texts );
		}

		// return the resulting object.
		return $immo_object;
	}

	/**
	 * Return the names of the fields in the API whose codes must not be replaced by their texts.
	 *
	 * @return array<int,string>
	 */
	private function get_untranslated_fields(): array {
		// the commission is a free text, its options only contain labels depending on the marketing type.
		$fields = array( 'courtage' );

		// the terms of our taxonomies are assigned by the code.
		foreach ( Taxonomies::get_instance()->get_taxonomies_as_objects() as $taxonomy ) {
			if ( ! empty( $taxonomy->get_api_field() ) ) {
				$fields[] = $taxonomy->get_api_field();
			}
		}

		/**
		 * Filter the names of the fields in the API v2 whose codes must not be replaced by their texts.
		 *
		 * @since 2.0.1 Available since 2.0.1.
		 * @param array<int,string> $fields The names of the fields in the API.
		 */
		return apply_filters( 'cfprop_api_v2_untranslated_fields', $fields );
	}

	/**
	 * Replace the given code by its text.
	 *
	 * Supports a single code, a list of codes and multiple codes separated by a comma.
	 * A value we have no text for is returned unchanged.
	 *
	 * @param mixed               $value The value from API.
	 * @param array<string,mixed> $texts The codes with their texts.
	 *
	 * @return mixed
	 */
	private function translate_value( mixed $value, array $texts ): mixed {
		// translate each entry of a list.
		if ( is_array( $value ) ) {
			foreach ( $value as $index => $entry ) {
				if ( is_string( $entry ) ) {
					$value[ $index ] = $this->translate_value( $entry, $texts );
				}
			}
			return $value;
		}

		// bail if the value is not a code.
		if ( ! is_string( $value ) || '' === $value ) {
			return $value;
		}

		// use the text of a single code.
		if ( isset( $texts[ $value ] ) && is_string( $texts[ $value ] ) ) {
			return $texts[ $value ];
		}

		// bail if the value does not contain multiple codes.
		if ( ! str_contains( $value, ',' ) ) {
			return $value;
		}

		// use the texts of multiple codes, but only if we know each of them.
		$translated = array();
		foreach ( explode( ',', $value ) as $code ) {
			$code = trim( $code );
			if ( ! isset( $texts[ $code ] ) || ! is_string( $texts[ $code ] ) ) {
				return $value;
			}
			$translated[] = $texts[ $code ];
		}

		// return the resulting list of texts.
		return implode( ', ', $translated );
	}

	/**
	 * Request the options from the API.
	 *
	 * @param string $language_code The language to use.
	 *
	 * @return array<string,mixed>
	 */
	private function request_options( string $language_code ): array {
		// create and send the API request.
		$request_object = new ApiRequest();
		$request_object->set_url( $this->get_url( $language_code ) );
		$request_object->set_post_data( '' );
		$request_object->set_method( 'GET' );
		$request_object->set_md5( md5( $this->get_url( $language_code ) ) );
		$request_object->set_header( $this->get_header() );
		$request_object->send();

		// add a log entry if the API key is not allowed to read the options, as this must be changed in Propstack.
		if ( in_array( $request_object->get_http_status(), array( 401, 403 ), true ) ) {
			/* translators: %1$s will be replaced by the HTTP status, %2$s by a URL. */
			Log::get_instance()->add( sprintf( __( 'Propstack does not allow your API key to read the options of object fields (HTTP status %1$s). They contain the texts for the values of selection fields (e.g. for the heating type) and are necessary to use the API v2. Please enable the permission to read these options for <a href="%2$s" target="_blank">your API key</a>.', 'connector-for-propstack' ), '<code>' . $request_object->get_http_status() . '</code>', esc_url( Helper::get_propstack_api_page_url() ) ), 'error', 'import' );
		}

		// bail on error.
		if ( 200 !== $request_object->get_http_status() ) {
			return array();
		}

		// convert the response to an array.
		$data = json_decode( $request_object->get_response(), true );

		// bail if no options are given.
		if ( ! is_array( $data ) || empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return array();
		}

		// return the options.
		return $data['data'];
	}

	/**
	 * Return whether the given API token is allowed to read the options.
	 *
	 * Only a rejection by the API is handled as a missing permission. Any other problem
	 * (e.g. the API is not reachable) does not say anything about the permissions of the token.
	 *
	 * @param string $api_key The API token to check.
	 *
	 * @return bool
	 */
	public function is_token_permitted( string $api_key ): bool {
		// create and send the API request with the given token.
		$request_object = new ApiRequest();
		$request_object->set_url( $this->get_url( Languages::get_instance()->get_import_language() ) );
		$request_object->set_post_data( '' );
		$request_object->set_method( 'GET' );
		$request_object->set_md5( md5( 'validate_options_' . $api_key ) );
		$request_object->set_header(
			array(
				'X-API-KEY'    => $api_key,
				'Content-Type' => 'application/json',
			)
		);
		$request_object->send();

		// return whether the API did not reject the token.
		return ! in_array( $request_object->get_http_status(), array( 401, 403 ), true );
	}

	/**
	 * Return the options which are delivered with this plugin for the given language.
	 *
	 * @param string $language_code The language to use.
	 *
	 * @return array<string,mixed>
	 */
	private function get_delivered_options( string $language_code ): array {
		// get the path of the file for this language.
		$path = Helper::get_plugin_path() . 'lib/options-' . sanitize_key( $language_code ) . '.json';

		// bail if we do not deliver options for this language.
		if ( ! file_exists( $path ) ) {
			return array();
		}

		// get the content of the file.
		$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file of this plugin.

		// bail if the file could not be read.
		if ( ! is_string( $content ) ) {
			return array();
		}

		// convert the content to an array.
		$data = json_decode( $content, true );

		// bail if no options are given.
		if ( ! is_array( $data ) || empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return array();
		}

		// return the options.
		return $data['data'];
	}

	/**
	 * Return the API URL to get the options.
	 *
	 * @param string $language_code The language to use for the URL.
	 *
	 * @return string
	 */
	private function get_url( string $language_code ): string {
		// get the URL.
		$url = add_query_arg(
			array(
				'locale' => $language_code,
			),
			$this->url
		);

		/**
		 * Filter the URL of the API to get the options of object fields from Propstack.
		 *
		 * @since 2.0.1 Available since 2.0.1.
		 * @param string $url The URL.
		 */
		return apply_filters( 'cfprop_api_object_options_url', $url );
	}
}

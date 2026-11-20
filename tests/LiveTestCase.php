<?php
/**
 * File with the base object for the tests against the live Propstack API.
 *
 * The live tests are run with "composer test-live" (see phpunit-live.xml.dist). They use a real
 * API key and request real data from Propstack. They only read from Propstack: no data in
 * Propstack is changed. Everything is imported into the database of the WordPress tests, which
 * is reset by the test framework.
 *
 * The configuration is read in this order (later wins):
 * 1. Defaults, see get_config().
 * 2. The file tests/live-config.php (not in the repository, see tests/live-config.example.php)
 *    or the file set via the environment variable CFPROP_LIVE_CONFIG.
 * 3. Environment variables:
 *    CFPROP_LIVE_API_KEY       the API key (required, otherwise all live tests are skipped)
 *    CFPROP_LIVE_API_VERSIONS  the API versions to test, e.g. "v1,v2"
 *    CFPROP_LIVE_LANGUAGE      the language of the import, e.g. "de"
 *    CFPROP_LIVE_OBJECT_ID     the Propstack-ID of an object which must be delivered and imported (optional)
 *    CFPROP_LIVE_MAX_OBJECTS   the max. amount of objects to import, 0 for all
 *    CFPROP_LIVE_REQUIRE       set to 1 to fail instead of skip if no API key is configured (e.g. in CI)
 *
 * Details: doc/live-tests.md
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests;

use ConnectorForPropstack\Plugin\Installer;
use ConnectorForPropstack\Propstack\ImmoObject;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Import_Base;
use ConnectorForPropstack\Propstack\Propstack;
use ConnectorForPropstack\Propstack\States;
use WP_Error;
use WP_UnitTestCase;

/**
 * Base object for each test against the live Propstack API.
 */
abstract class LiveTestCase extends WP_UnitTestCase {
	/**
	 * The host of the Propstack API.
	 *
	 * @var string
	 */
	protected const API_HOST = 'api.propstack.de';

	/**
	 * The resulting configuration.
	 *
	 * @var array{api_key:string,api_versions:array<int,string>,language:string,object_id:string,max_objects:int,require:bool}|null
	 */
	private static ?array $config = null;

	/**
	 * List of requests to the Propstack API since the last reset.
	 *
	 * @var array<int,array{method:string,url:string,status:int,error:string}>
	 */
	protected static array $api_requests = array();

	/**
	 * List of attachment-IDs which have been created during the actual test.
	 *
	 * @var array<int,int>
	 */
	private static array $created_attachments = array();

	/**
	 * Return the configuration for the live tests.
	 *
	 * @return array{api_key:string,api_versions:array<int,string>,language:string,object_id:string,max_objects:int,require:bool}
	 */
	public static function get_config(): array {
		// return the configuration if it is already loaded.
		if ( null !== self::$config ) {
			return self::$config;
		}

		// set the defaults.
		$config = array(
			'api_key'      => '',
			'api_versions' => 'v1,v2',
			'language'     => 'de',
			'object_id'    => '',
			'max_objects'  => 0,
			'require'      => false,
		);

		// add the values from the configuration file.
		$file = getenv( 'CFPROP_LIVE_CONFIG' );
		if ( ! is_string( $file ) || '' === $file ) {
			$file = dirname( __DIR__ ) . '/tests/live-config.php';
		}
		if ( is_readable( $file ) ) {
			$from_file = require $file;
			if ( is_array( $from_file ) ) {
				$config = array_merge( $config, array_intersect_key( $from_file, $config ) );
			}
		}

		// add the values from the environment.
		$environment = array(
			'api_key'      => 'CFPROP_LIVE_API_KEY',
			'api_versions' => 'CFPROP_LIVE_API_VERSIONS',
			'language'     => 'CFPROP_LIVE_LANGUAGE',
			'object_id'    => 'CFPROP_LIVE_OBJECT_ID',
			'max_objects'  => 'CFPROP_LIVE_MAX_OBJECTS',
			'require'      => 'CFPROP_LIVE_REQUIRE',
		);
		foreach ( $environment as $name => $variable ) {
			$value = getenv( $variable );
			if ( is_string( $value ) && '' !== $value ) {
				$config[ $name ] = $value;
			}
		}

		// get the API versions as list.
		$api_versions = is_array( $config['api_versions'] ) ? $config['api_versions'] : explode( ',', (string) $config['api_versions'] );
		$api_versions = array_values( array_intersect( array( 'v1', 'v2' ), array_map( 'trim', array_map( 'strval', $api_versions ) ) ) );

		// secure the resulting configuration.
		self::$config = array(
			'api_key'      => trim( (string) $config['api_key'] ),
			'api_versions' => $api_versions,
			'language'     => '' !== trim( (string) $config['language'] ) ? trim( (string) $config['language'] ) : 'de',
			'object_id'    => trim( (string) $config['object_id'] ),
			'max_objects'  => max( 0, (int) $config['max_objects'] ),
			'require'      => filter_var( $config['require'], FILTER_VALIDATE_BOOLEAN ),
		);

		return self::$config;
	}

	/**
	 * Return whether the live tests are configured (an API key is given).
	 *
	 * @return bool
	 */
	protected static function is_configured(): bool {
		return '' !== self::get_config()['api_key'];
	}

	/**
	 * Return whether the given API version should be tested.
	 *
	 * @param string $api_version The API version ("v1" or "v2").
	 *
	 * @return bool
	 */
	protected static function is_api_version_enabled( string $api_version ): bool {
		return in_array( $api_version, self::get_config()['api_versions'], true );
	}

	/**
	 * Return the API versions as data provider for the tests.
	 *
	 * Always returns both versions, so the names of the tests do not depend on the configuration.
	 * Versions which are not configured are skipped by the tests.
	 *
	 * @return array<string,array<int,string>>
	 */
	public static function api_versions(): array {
		return array(
			'API v1' => array( 'v1' ),
			'API v2' => array( 'v2' ),
		);
	}

	/**
	 * Prepare the test environment for each test class.
	 *
	 * Runs inside the preparation of the test framework (after its reconnect to the database), so
	 * the data is committed and available for all tests of the class. Test classes, which need
	 * data for all of their tests (e.g. imported objects), create it in set_up_live_class().
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass(): void {
		// prepare the plugin once for all test classes.
		if ( ! did_action( 'cfprop_live_test_preparation_loaded' ) ) {
			// enable error reporting.
			error_reporting( E_ALL ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting

			// initialize the plugin.
			Installer::get_instance()->activation();

			// run initialization.
			do_action( 'init' );

			// record each request to the Propstack API.
			add_action( 'http_api_debug', array( self::class, 'record_api_request' ), 10, 5 );

			// record each attachment, so files of tests could be removed from disk.
			add_action( 'add_attachment', array( self::class, 'record_attachment' ) );

			// use the language of the import also as language of the frontend.
			add_filter( 'cfprop_current_language', static fn(): string => self::get_config()['language'] );

			// mark as loaded.
			do_action( 'cfprop_live_test_preparation_loaded' );
		}

		// set the basic settings for each test class, as the data of the last class has been removed.
		self::set_basic_options();

		// create the terms of our taxonomies (they are removed after each test class).
		Propstack::get_instance()->activation();

		// let the test class add its data.
		static::set_up_live_class();
	}

	/**
	 * Create data for all tests of a test class, e.g. import the objects once.
	 *
	 * @return void
	 */
	protected static function set_up_live_class(): void {}

	/**
	 * Clean up after each test class.
	 *
	 * Removes the files of all attachments, as the test framework only removes them from the database.
	 *
	 * @return void
	 */
	public static function tear_down_after_class(): void {
		self::delete_all_attachment_files();

		parent::tear_down_after_class();
	}

	/**
	 * Prepare each test.
	 *
	 * Skips the test if no API key is configured.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// bail if the live tests are not configured.
		if ( ! self::is_configured() ) {
			$reason = 'No API key for the live tests. Copy tests/live-config.example.php to tests/live-config.php and enter your API key, or set CFPROP_LIVE_API_KEY.';
			if ( self::get_config()['require'] ) {
				$this->fail( $reason );
			}
			$this->markTestSkipped( $reason );
		}

		// start with an empty list of requests and attachments.
		self::$api_requests        = array();
		self::$created_attachments = array();
	}

	/**
	 * Clean up after each test.
	 *
	 * Removes the files of attachments, which have been created during this test. Their database
	 * entries are removed by the test framework, the files would remain on disk.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		foreach ( self::$created_attachments as $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}
		self::$created_attachments = array();

		parent::tear_down();
	}

	/**
	 * Skip the test if the given API version should not be tested.
	 *
	 * @param string $api_version The API version.
	 *
	 * @return void
	 */
	protected function require_api_version( string $api_version ): void {
		if ( ! self::is_api_version_enabled( $api_version ) ) {
			$this->markTestSkipped( 'The API ' . $api_version . ' is not part of the configured API versions (' . implode( ', ', self::get_config()['api_versions'] ) . ').' );
		}
	}

	/**
	 * Set the basic options for the live tests.
	 *
	 * @return void
	 */
	protected static function set_basic_options(): void {
		update_option( 'propstack_connector_api_key', self::get_config()['api_key'] );
		update_option( 'propstack_connector_languages', self::get_config()['language'] );
		update_option( 'propstack_connector_debug', 0 );
		update_option( CFPROP_IMPORT_RUNNING, 0 );
		update_option( CFPROP_DELETE_RUNNING, 0 );
	}

	/**
	 * Use the given API version for the following requests.
	 *
	 * @param string $api_version The API version ("v1" or "v2").
	 *
	 * @return void
	 */
	protected static function use_api_version( string $api_version ): void {
		update_option( 'propstack_connector_api_version', $api_version );

		// register the hooks of the states, which are only used with the API v2.
		States::get_instance()->init();
	}

	/**
	 * Run the import of objects until it is completed, like the WP CLI command does it.
	 *
	 * Format of the result:
	 * - import => the import object of the last run (it holds the errors of the import).
	 * - runs => the amount of runs.
	 * - delivered => the amount of objects the API delivered for the import (after the limit of max_objects).
	 * - seconds => the duration of the import.
	 *
	 * @return array{import:Import_Base,runs:int,delivered:int,seconds:float}
	 */
	protected static function run_complete_import(): array {
		// count the delivered objects and limit them to the configured max. amount (the configured object is always kept).
		$max_objects = self::get_config()['max_objects'];
		$object_id   = self::get_config()['object_id'];
		$delivered   = 0;
		$others      = 0;
		$limit       = static function ( array $page_objects ) use ( $max_objects, $object_id, &$delivered, &$others ): array {
			if ( $max_objects > 0 ) {
				$page_objects = array_values(
					array_filter(
						$page_objects,
						static function ( mixed $immo_object ) use ( $max_objects, $object_id, &$others ): bool {
							if ( '' !== $object_id && is_array( $immo_object ) && isset( $immo_object['id'] ) && is_scalar( $immo_object['id'] ) && (string) $immo_object['id'] === $object_id ) {
								return true;
							}
							return $others++ < $max_objects;
						}
					)
				);
			}
			$delivered += count( $page_objects );
			return $page_objects;
		};
		add_filter( 'cfprop_object_import_response', $limit, 1 );

		// run the import until it is completed.
		$start = microtime( true );
		$runs  = 0;
		try {
			do {
				$import = ImmoObjects::get_instance()->import( '' );
				++$runs;
			} while ( $import->has_load_more() && $runs < 10000 );
		} finally {
			remove_filter( 'cfprop_object_import_response', $limit, 1 );
		}

		return array(
			'import'    => $import,
			'runs'      => $runs,
			'delivered' => $delivered,
			'seconds'   => microtime( true ) - $start,
		);
	}

	/**
	 * Return all imported objects in any post status, indexed by their post-ID.
	 *
	 * @return array<int,ImmoObject>
	 */
	protected static function get_all_objects(): array {
		$objects = array();
		foreach ( ImmoObjects::get_instance()->get_objects(
			array(
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		) as $immo_object ) {
			$objects[ $immo_object->get_id() ] = $immo_object;
		}
		return $objects;
	}

	/**
	 * Return the errors of the given import object as plain texts.
	 *
	 * @param Import_Base $import The import object.
	 *
	 * @return array<int,string>
	 */
	protected static function get_error_texts( Import_Base $import ): array {
		$texts = array();
		foreach ( $import->get_errors() as $error ) {
			$texts[] = $error->get_error_code() . ': ' . html_entity_decode( wp_strip_all_tags( $error->get_error_message() ), ENT_QUOTES | ENT_HTML5 );
		}
		return $texts;
	}

	/**
	 * Record a request to the Propstack API.
	 *
	 * @param array<string,mixed>|WP_Error $response    The response.
	 * @param string                       $context     The context ("response").
	 * @param string                       $class_name  The used class.
	 * @param array<string,mixed>          $parsed_args The arguments of the request.
	 * @param string                       $url         The URL.
	 *
	 * @return void
	 */
	public static function record_api_request( array|WP_Error $response, string $context, string $class_name, array $parsed_args, string $url ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// bail if this is not a request to the Propstack API.
		if ( 'response' !== $context || self::API_HOST !== wp_parse_url( $url, PHP_URL_HOST ) ) {
			return;
		}

		self::$api_requests[] = array(
			'method' => isset( $parsed_args['method'] ) && is_string( $parsed_args['method'] ) ? $parsed_args['method'] : '',
			'url'    => $url,
			'status' => $response instanceof WP_Error ? 0 : absint( wp_remote_retrieve_response_code( $response ) ),
			'error'  => $response instanceof WP_Error ? $response->get_error_message() : '',
		);
	}

	/**
	 * Return the recorded requests to the Propstack API, whose path starts with the given path.
	 *
	 * @param string $path The path, e.g. "/v2/brokers".
	 *
	 * @return array<int,array{method:string,url:string,status:int,error:string}>
	 */
	protected static function get_api_requests( string $path = '' ): array {
		return array_values(
			array_filter(
				self::$api_requests,
				static fn( array $request ): bool => str_starts_with( (string) wp_parse_url( $request['url'], PHP_URL_PATH ), $path )
			)
		);
	}

	/**
	 * Return the recorded requests to the Propstack API as text for messages of failed assertions.
	 *
	 * @param array<int,array{method:string,url:string,status:int,error:string}>|null $requests The requests (null for all recorded requests).
	 *
	 * @return string
	 */
	protected static function get_api_requests_as_text( ?array $requests = null ): string {
		$lines = array();
		foreach ( $requests ?? self::$api_requests as $request ) {
			$lines[] = '  ' . $request['method'] . ' ' . $request['url'] . ' => ' . ( '' !== $request['error'] ? $request['error'] : 'HTTP ' . $request['status'] );
		}
		return empty( $lines ) ? '  (no requests to the Propstack API)' : implode( "\n", $lines );
	}

	/**
	 * Record an attachment, which has been created.
	 *
	 * @param int $attachment_id The attachment-ID.
	 *
	 * @return void
	 */
	public static function record_attachment( int $attachment_id ): void {
		self::$created_attachments[] = $attachment_id;
	}

	/**
	 * Delete the files of all attachments, e.g. images imported for a whole test class.
	 *
	 * @return void
	 */
	protected static function delete_all_attachment_files(): void {
		foreach ( get_posts(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		) as $attachment_id ) {
			wp_delete_attachment( absint( $attachment_id ), true );
		}
	}

	/**
	 * Write a line with information about the live test to the console.
	 *
	 * @param string $text The text.
	 *
	 * @return void
	 */
	protected static function report( string $text ): void {
		fwrite( STDERR, "\n[live] " . $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	}
}

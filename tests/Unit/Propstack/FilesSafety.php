<?php
/**
 * File for safety tests against \ConnectorForPropstack\Propstack\Files.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use ReflectionMethod;
use WP_Error;

/**
 * Object for safety tests against \ConnectorForPropstack\Propstack\Files.
 */
class FilesSafety extends ConnectorForPropstackTestCase {
	/**
	 * A public IP-based URL for downloads, which does not need a DNS lookup.
	 *
	 * @var string
	 */
	private const DOWNLOAD_URL = 'https://3.168.40.35/photos/test.png';

	/**
	 * The HTTP status the download mock should return.
	 *
	 * @var int
	 */
	private int $mock_status = 200;

	/**
	 * List of the arguments of every mocked download.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $mocked_downloads = array();

	/**
	 * List of file IDs for which an import has been requested.
	 *
	 * @var array<int,int>
	 */
	private array $requested_file_ids = array();

	/**
	 * List of files which have been collected by Files::import().
	 *
	 * @var array<int,array<int,array<string,mixed>>>
	 */
	private array $collected_lists = array();

	/**
	 * List of attachment IDs which must be deleted after the test.
	 *
	 * @var array<int,int>
	 */
	private array $attachment_ids = array();

	/**
	 * The host which should be used as home host during the test.
	 *
	 * @var string
	 */
	private string $home_host = '';

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// register the post type for objects (the test suite resets post types after each test).
		\ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->register();

		// reset the states.
		$this->mock_status        = 200;
		$this->mocked_downloads   = array();
		$this->requested_file_ids = array();
		$this->collected_lists    = array();
		$this->attachment_ids     = array();
		$this->home_host          = '';

		// mock the download of files.
		add_filter( 'pre_http_request', array( $this, 'mock_download' ), 5, 3 );

		// make sure no process is marked as running.
		update_option( CFPROP_FILES_IMPORT_RUNNING, 0 );
		update_option( CFPROP_FILES_DELETE_RUNNING, 0 );
	}

	/**
	 * Clean up the test environment after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		// remove the filters.
		remove_filter( 'pre_http_request', array( $this, 'mock_download' ), 5 );
		remove_filter( 'pre_option_home', array( $this, 'get_home_url' ) );
		remove_filter( 'cfprop_allowed_file_hosts', array( $this, 'get_allowed_hosts' ) );
		remove_filter( 'cfprop_max_file_size', array( $this, 'get_tiny_max_file_size' ) );
		remove_filter( 'cfprop_files_import_limit', '__return_zero' );
		remove_filter( 'cfprop_prevent_file_import', array( $this, 'record_and_prevent_file_import' ), 1 );
		remove_filter( 'cfprop_files_before_import', array( $this, 'collect_files_list' ), 1 );

		// remove the request data.
		unset( $_POST['post'] );

		// remove the transients.
		delete_transient( 'propstack_object_files_to_import' );
		delete_transient( 'propstack_object_files_to_import_owner' );

		// reset the process markers.
		update_option( CFPROP_FILES_IMPORT_RUNNING, 0 );
		update_option( CFPROP_FILES_DELETE_RUNNING, 0 );

		// delete the attachments including their files.
		foreach ( $this->attachment_ids as $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}

		parent::tear_down();
	}

	/**
	 * Mock the download of files: write a tiny PNG in the target file and return the configured status.
	 *
	 * @param false|array|WP_Error $response    The return value of the filter.
	 * @param array                $parsed_args The used parameters for the request.
	 * @param string               $url         The requested URL.
	 *
	 * @return false|array|WP_Error
	 */
	public function mock_download( false|array|WP_Error $response, array $parsed_args, string $url ): false|array|WP_Error {
		// ignore requests to the Propstack API.
		if ( str_starts_with( $url, 'https://api.propstack.de/' ) ) {
			return $response;
		}

		// record this download.
		$this->mocked_downloads[] = array_merge( $parsed_args, array( 'url' => $url ) );

		// write the file content only on success.
		if ( 200 === $this->mock_status && ! empty( $parsed_args['stream'] ) && ! empty( $parsed_args['filename'] ) ) {
			file_put_contents( $parsed_args['filename'], $this->get_png() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		// return the response.
		return array(
			'headers'  => array(),
			'body'     => '',
			'response' => array(
				'code'    => $this->mock_status,
				'message' => 200 === $this->mock_status ? 'OK' : 'Not Found',
			),
			'cookies'  => array(),
			'filename' => $parsed_args['filename'] ?? '',
		);
	}

	/**
	 * Return the content of a valid 1x1 PNG image.
	 *
	 * @return string
	 */
	private function get_png(): string {
		return (string) base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	}

	/**
	 * Return the home URL with the configured host.
	 *
	 * Hint: wp_http_validate_url() resolves foreign hosts via DNS. Using the tested host as home host skips this
	 * lookup, so the tests only check the rules of the plugin and do not depend on the network.
	 *
	 * @return string
	 */
	public function get_home_url(): string {
		return 'https://' . $this->home_host;
	}

	/**
	 * Return the list of allowed hosts.
	 *
	 * @return array<int,string>
	 */
	public function get_allowed_hosts(): array {
		return array( 'propstack.de' );
	}

	/**
	 * Return a tiny max file size.
	 *
	 * @return int
	 */
	public function get_tiny_max_file_size(): int {
		return 10;
	}

	/**
	 * Record the requested file ID and prevent the import of the file.
	 *
	 * @param bool                $prevent   The actual value.
	 * @param array<string,mixed> $file_data The file data.
	 * @param int                 $id        The file ID.
	 *
	 * @return bool
	 */
	public function record_and_prevent_file_import( bool $prevent, array $file_data, int $id ): bool {
		$this->requested_file_ids[] = $id;
		return true;
	}

	/**
	 * Collect the list of files which Files::import() has built.
	 *
	 * @param array<int,array<string,mixed>> $files_to_import The list of files.
	 *
	 * @return void
	 */
	public function collect_files_list( array $files_to_import ): void {
		$this->collected_lists[] = $files_to_import;
	}

	/**
	 * Return a callable private method of the Files object.
	 *
	 * @param string $name The method name.
	 *
	 * @return ReflectionMethod
	 */
	private function get_private_method( string $name ): ReflectionMethod {
		$method = new ReflectionMethod( \ConnectorForPropstack\Propstack\Files::class, $name );
		$method->setAccessible( true );
		return $method;
	}

	/**
	 * Return whether the given URL is allowed, with the host of the URL used as home host (to skip DNS lookups).
	 *
	 * @param string $url The URL to check.
	 *
	 * @return bool
	 */
	private function is_allowed_file_url( string $url ): bool {
		$this->home_host = (string) wp_parse_url( $url, PHP_URL_HOST );
		add_filter( 'pre_option_home', array( $this, 'get_home_url' ) );
		$result = $this->get_private_method( 'is_allowed_file_url' )->invoke( \ConnectorForPropstack\Propstack\Files::get_instance(), $url );
		remove_filter( 'pre_option_home', array( $this, 'get_home_url' ) );
		return $result;
	}

	/**
	 * Create an object post.
	 *
	 * @param array<string,mixed> $api_response The API response to save on the object (optional).
	 *
	 * @return int
	 */
	private function create_object( array $api_response = array() ): int {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name(),
				'post_status' => 'publish',
			)
		);
		if ( ! empty( $api_response ) ) {
			update_post_meta( $post_id, 'api_response', $api_response );
		}
		return $post_id;
	}

	/**
	 * Create an attachment which has been imported from Propstack.
	 *
	 * @param int  $parent_id The object the attachment is assigned to.
	 * @param int  $file_id   The Propstack file ID.
	 * @param bool $updated   Whether the attachment is marked as updated.
	 *
	 * @return int
	 */
	private function create_file( int $parent_id, int $file_id, bool $updated ): int {
		$attachment_id = self::factory()->attachment->create( array( 'post_parent' => $parent_id ) );
		update_post_meta( $attachment_id, 'propstack_file_id', $file_id );
		if ( $updated ) {
			update_post_meta( $attachment_id, 'propstack_file_updated', time() );
		}
		$this->attachment_ids[] = $attachment_id;
		return $attachment_id;
	}

	/**
	 * Test that URLs without HTTPS are rejected.
	 *
	 * @return void
	 */
	public function test_http_url_is_rejected(): void {
		$this->assertFalse( $this->is_allowed_file_url( 'http://images.propstack.de/photo.png' ) );
	}

	/**
	 * Test that invalid URLs are rejected.
	 *
	 * @return void
	 */
	public function test_invalid_urls_are_rejected(): void {
		$files = \ConnectorForPropstack\Propstack\Files::get_instance();
		$check = $this->get_private_method( 'is_allowed_file_url' );

		$this->assertFalse( $check->invoke( $files, '' ) );
		$this->assertFalse( $check->invoke( $files, 'not a url' ) );
		$this->assertFalse( $check->invoke( $files, 'ftp://images.propstack.de/photo.png' ) );
		$this->assertFalse( $check->invoke( $files, 'https://user:pass@images.propstack.de/photo.png' ) );
		$this->assertFalse( $check->invoke( $files, 'https://127.0.0.1/photo.png' ) );
	}

	/**
	 * Test that a public HTTPS URL is accepted if no host list is configured.
	 *
	 * @return void
	 */
	public function test_public_https_url_is_accepted(): void {
		$this->assertTrue( $this->is_allowed_file_url( 'https://images.propstack.de/photo.png' ) );
		$this->assertTrue( $this->is_allowed_file_url( 'https://cdn.example.com/photo.png' ) );
	}

	/**
	 * Test the list of allowed hosts: subdomains are allowed, foreign hosts and look-alike hosts not.
	 *
	 * @return void
	 */
	public function test_allowed_hosts_filter(): void {
		add_filter( 'cfprop_allowed_file_hosts', array( $this, 'get_allowed_hosts' ) );

		$this->assertTrue( $this->is_allowed_file_url( 'https://propstack.de/photo.png' ) );
		$this->assertTrue( $this->is_allowed_file_url( 'https://images.propstack.de/photo.png' ) );
		$this->assertTrue( $this->is_allowed_file_url( 'https://IMAGES.Propstack.DE/photo.png' ) );
		$this->assertFalse( $this->is_allowed_file_url( 'https://evil.com/photo.png' ) );
		$this->assertFalse( $this->is_allowed_file_url( 'https://propstack.de.evil.com/photo.png' ) );
		$this->assertFalse( $this->is_allowed_file_url( 'https://evilpropstack.de/photo.png' ) );
	}

	/**
	 * Test that a successful download creates an attachment with the Propstack file ID.
	 *
	 * @return void
	 */
	public function test_successful_download_creates_attachment(): void {
		$post_id = $this->create_object();

		$attachment_id = \ConnectorForPropstack\Propstack\Files::get_instance()->import_file( $post_id, 424242, self::DOWNLOAD_URL, 'test.png', array( 'position' => 3 ) );
		if ( $attachment_id > 0 ) {
			$this->attachment_ids[] = $attachment_id;
		}

		$this->assertGreaterThan( 0, $attachment_id );
		$this->assertSame( 'attachment', get_post_type( $attachment_id ) );
		$this->assertSame( $post_id, wp_get_post_parent_id( $attachment_id ) );
		$this->assertSame( 424242, absint( get_post_meta( $attachment_id, 'propstack_file_id', true ) ) );
		$this->assertSame( 3, absint( get_post_meta( $attachment_id, 'propstack_file_position', true ) ) );
		$this->assertFileExists( (string) get_attached_file( $attachment_id ) );

		// the download must be streamed into a temporary file which is removed afterward.
		$this->assertCount( 1, $this->mocked_downloads );
		$this->assertTrue( $this->mocked_downloads[0]['stream'] );
		$this->assertFileDoesNotExist( $this->mocked_downloads[0]['filename'] );

		// a second import of the same file must return the existing attachment without a new download.
		$this->assertSame( $attachment_id, \ConnectorForPropstack\Propstack\Files::get_instance()->import_file( $post_id, 424242, self::DOWNLOAD_URL, 'test.png', array() ) );
		$this->assertCount( 1, $this->mocked_downloads );
	}

	/**
	 * Test that a failed download (HTTP 404) returns 0 and leaves no temporary file.
	 *
	 * @return void
	 */
	public function test_failed_download_leaves_no_temp_file(): void {
		$this->mock_status = 404;
		$post_id           = $this->create_object();

		$attachment_id = \ConnectorForPropstack\Propstack\Files::get_instance()->import_file( $post_id, 424243, self::DOWNLOAD_URL, 'test.png', array() );

		$this->assertSame( 0, $attachment_id );
		$this->assertSame( 0, \ConnectorForPropstack\Propstack\Files::get_instance()->is_file_in_media_library( 424243 ) );
		$this->assertCount( 1, $this->mocked_downloads );
		$this->assertNotEmpty( $this->mocked_downloads[0]['filename'] );
		$this->assertFileDoesNotExist( $this->mocked_downloads[0]['filename'] );
	}

	/**
	 * Test that download_file() returns an empty string on HTTP 404.
	 *
	 * @return void
	 */
	public function test_download_file_returns_empty_string_on_404(): void {
		$this->mock_status = 404;

		$result = $this->get_private_method( 'download_file' )->invoke( \ConnectorForPropstack\Propstack\Files::get_instance(), self::DOWNLOAD_URL, 'test.png' );

		$this->assertSame( '', $result );
		$this->assertFileDoesNotExist( $this->mocked_downloads[0]['filename'] );
	}

	/**
	 * Test that a file larger than the allowed size is rejected and removed.
	 *
	 * @return void
	 */
	public function test_too_large_download_is_rejected(): void {
		add_filter( 'cfprop_max_file_size', array( $this, 'get_tiny_max_file_size' ) );
		$post_id = $this->create_object();

		$attachment_id = \ConnectorForPropstack\Propstack\Files::get_instance()->import_file( $post_id, 424244, self::DOWNLOAD_URL, 'test.png', array() );

		$this->assertSame( 0, $attachment_id );
		$this->assertCount( 1, $this->mocked_downloads );

		// the request must be limited to one byte more than allowed.
		$this->assertSame( 11, $this->mocked_downloads[0]['limit_response_size'] );
		$this->assertFileDoesNotExist( $this->mocked_downloads[0]['filename'] );
	}

	/**
	 * Test that a not allowed URL is not downloaded at all.
	 *
	 * @return void
	 */
	public function test_not_allowed_url_is_not_downloaded(): void {
		$post_id = $this->create_object();

		$attachment_id = \ConnectorForPropstack\Propstack\Files::get_instance()->import_file( $post_id, 424245, 'http://3.168.40.35/photos/test.png', 'test.png', array() );

		$this->assertSame( 0, $attachment_id );
		$this->assertCount( 0, $this->mocked_downloads );
	}

	/**
	 * Test that a cached list of files owned by another process and object is ignored by import().
	 *
	 * @return void
	 */
	public function test_import_ignores_cached_list_of_other_owner(): void {
		$other_post_id = $this->create_object();
		$post_id       = $this->create_object(
			array(
				'images' => array(
					array(
						'id'      => 9001,
						'name'    => 'own.png',
						'big_url' => self::DOWNLOAD_URL,
					),
				),
			)
		);

		// simulate a list left over from an aborted import of another object in another process.
		set_transient(
			'propstack_object_files_to_import',
			array(
				array(
					'id'         => 7777,
					'name'       => 'foreign.png',
					'big_url'    => self::DOWNLOAD_URL,
					'wp_post_id' => $other_post_id,
				),
			),
			DAY_IN_SECONDS
		);
		set_transient( 'propstack_object_files_to_import_owner', 'other-process|' . $other_post_id, DAY_IN_SECONDS );

		add_filter( 'cfprop_files_import_limit', '__return_zero' );
		add_filter( 'cfprop_prevent_file_import', array( $this, 'record_and_prevent_file_import' ), 1, 3 );
		add_action( 'cfprop_files_before_import', array( $this, 'collect_files_list' ), 1 );

		\ConnectorForPropstack\Propstack\Files::get_instance()->import( $post_id, 'this-process' );

		// the list must have been rebuilt for the requested object.
		$this->assertCount( 1, $this->collected_lists );
		$this->assertSame( array( 9001 ), array_map( 'absint', array_column( $this->collected_lists[0], 'id' ) ) );
		$this->assertSame( $post_id, $this->collected_lists[0][0]['wp_post_id'] );
		$this->assertSame( array( 9001 ), $this->requested_file_ids );

		// the cache must be cleaned up after the import.
		$this->assertFalse( get_transient( 'propstack_object_files_to_import' ) );
		$this->assertFalse( get_transient( 'propstack_object_files_to_import_owner' ) );
		$this->assertSame( 0, absint( get_option( CFPROP_FILES_IMPORT_RUNNING ) ) );
	}

	/**
	 * Test that a cached list with the same owner (same process and object) is continued by import().
	 *
	 * @return void
	 */
	public function test_import_continues_cached_list_of_same_owner(): void {
		$post_id = $this->create_object(
			array(
				'images' => array(
					array(
						'id'      => 9001,
						'name'    => 'own.png',
						'big_url' => self::DOWNLOAD_URL,
					),
				),
			)
		);

		// simulate the remaining list of the same import process.
		set_transient(
			'propstack_object_files_to_import',
			array(
				array(
					'id'         => 9002,
					'name'       => 'remaining.png',
					'big_url'    => self::DOWNLOAD_URL,
					'wp_post_id' => $post_id,
				),
			),
			DAY_IN_SECONDS
		);
		set_transient( 'propstack_object_files_to_import_owner', 'this-process|' . $post_id, DAY_IN_SECONDS );

		add_filter( 'cfprop_files_import_limit', '__return_zero' );
		add_filter( 'cfprop_prevent_file_import', array( $this, 'record_and_prevent_file_import' ), 1, 3 );
		add_action( 'cfprop_files_before_import', array( $this, 'collect_files_list' ), 1 );

		\ConnectorForPropstack\Propstack\Files::get_instance()->import( $post_id, 'this-process' );

		// the list must not have been rebuilt, the remaining entry must have been processed.
		$this->assertCount( 0, $this->collected_lists );
		$this->assertSame( array( 9002 ), $this->requested_file_ids );
	}

	/**
	 * Test that set_post_id_filter() does not change the query without a requested object.
	 *
	 * @return void
	 */
	public function test_set_post_id_filter_without_request(): void {
		$query = array( 'post_type' => 'attachment' );

		$this->assertSame( $query, \ConnectorForPropstack\Propstack\Files::get_instance()->set_post_id_filter( $query ) );
	}

	/**
	 * Test that set_post_id_filter() restricts the query via "post_parent" (not "p") to the requested object.
	 *
	 * @return void
	 */
	public function test_set_post_id_filter_uses_post_parent(): void {
		$_POST['post'] = '123';

		$query = \ConnectorForPropstack\Propstack\Files::get_instance()->set_post_id_filter( array( 'post_type' => 'attachment' ) );

		$this->assertSame( 123, $query['post_parent'] );
		$this->assertArrayNotHasKey( 'p', $query );
	}

	/**
	 * Test that mark_files_as_not_updated() without a requested object removes the marker from all files.
	 *
	 * @return void
	 */
	public function test_mark_files_as_not_updated_for_all_objects(): void {
		$first_file  = $this->create_file( $this->create_object(), 1001, true );
		$second_file = $this->create_file( $this->create_object(), 1002, true );

		\ConnectorForPropstack\Propstack\Files::get_instance()->mark_files_as_not_updated();

		$this->assertFalse( metadata_exists( 'post', $first_file, 'propstack_file_updated' ) );
		$this->assertFalse( metadata_exists( 'post', $second_file, 'propstack_file_updated' ) );
	}

	/**
	 * Test that mark_files_as_not_updated() with a requested object only changes the files of this object.
	 *
	 * @return void
	 */
	public function test_mark_files_as_not_updated_for_single_object(): void {
		$object_id     = $this->create_object();
		$own_file      = $this->create_file( $object_id, 1001, true );
		$foreign_file  = $this->create_file( $this->create_object(), 1002, true );
		$_POST['post'] = (string) $object_id;

		\ConnectorForPropstack\Propstack\Files::get_instance()->mark_files_as_not_updated();

		$this->assertFalse( metadata_exists( 'post', $own_file, 'propstack_file_updated' ) );
		$this->assertTrue( metadata_exists( 'post', $foreign_file, 'propstack_file_updated' ) );
	}

	/**
	 * Test that delete_not_updated_files() deletes not updated files and removes them from the image list.
	 *
	 * @return void
	 */
	public function test_delete_not_updated_files_cleans_image_list(): void {
		$object_id     = $this->create_object();
		$outdated_file = $this->create_file( $object_id, 1001, false );
		$updated_file  = $this->create_file( $object_id, 1002, true );
		update_post_meta( $object_id, 'images', array( $outdated_file, $updated_file ) );

		\ConnectorForPropstack\Propstack\Files::get_instance()->delete_not_updated_files();

		$this->assertNull( get_post( $outdated_file ) );
		$this->assertInstanceOf( \WP_Post::class, get_post( $updated_file ) );
		$this->assertSame( array( $updated_file ), get_post_meta( $object_id, 'images', true ) );
	}

	/**
	 * Test that delete_not_updated_files() with a requested object keeps the files of other objects.
	 *
	 * @return void
	 */
	public function test_delete_not_updated_files_keeps_files_of_other_objects(): void {
		$object_id       = $this->create_object();
		$other_object_id = $this->create_object();
		$own_file        = $this->create_file( $object_id, 1001, false );
		$foreign_file    = $this->create_file( $other_object_id, 1002, false );
		update_post_meta( $object_id, 'images', array( $own_file ) );
		update_post_meta( $other_object_id, 'images', array( $foreign_file ) );
		$_POST['post'] = (string) $object_id;

		\ConnectorForPropstack\Propstack\Files::get_instance()->delete_not_updated_files();

		$this->assertNull( get_post( $own_file ) );
		$this->assertSame( array(), get_post_meta( $object_id, 'images', true ) );
		$this->assertInstanceOf( \WP_Post::class, get_post( $foreign_file ) );
		$this->assertSame( array( $foreign_file ), get_post_meta( $other_object_id, 'images', true ) );
	}

	/**
	 * Test that delete_all() deletes the files and removes them from the image list of their object.
	 *
	 * @return void
	 */
	public function test_delete_all_cleans_image_list(): void {
		$object_id = $this->create_object();
		$file_id   = $this->create_file( $object_id, 1001, true );
		update_post_meta( $object_id, 'images', array( $file_id ) );

		\ConnectorForPropstack\Propstack\Files::get_instance()->delete_all( '' );

		$this->assertNull( get_post( $file_id ) );
		$this->assertSame( 0, absint( get_option( CFPROP_FILES_DELETE_RUNNING ) ) );

		// the deleted file must not remain in the image list of the object.
		$this->assertSame( array(), array_values( (array) get_post_meta( $object_id, 'images', true ) ) );
	}

	/**
	 * Test that delete_unused_files() deletes nothing if the API response does not contain the key "images".
	 *
	 * @return void
	 */
	public function test_delete_unused_files_without_images_key(): void {
		$object_id = $this->create_object();
		$file_id   = $this->create_file( $object_id, 1001, true );
		update_post_meta( $object_id, 'images', array( $file_id ) );

		\ConnectorForPropstack\Propstack\Files::get_instance()->delete_unused_files( array( 'id' => 1 ), $object_id );

		$this->assertInstanceOf( \WP_Post::class, get_post( $file_id ) );
		$this->assertSame( array( $file_id ), get_post_meta( $object_id, 'images', true ) );
	}

	/**
	 * Test that delete_unused_files() deletes files which are not in the API response anymore.
	 *
	 * This is the counter-check for the test above: an existing but empty list means "no images".
	 *
	 * @return void
	 */
	public function test_delete_unused_files_with_images_key(): void {
		$object_id    = $this->create_object();
		$kept_file    = $this->create_file( $object_id, 1001, true );
		$removed_file = $this->create_file( $object_id, 1002, true );
		update_post_meta( $object_id, 'images', array( $kept_file, $removed_file ) );

		\ConnectorForPropstack\Propstack\Files::get_instance()->delete_unused_files( array( 'images' => array( array( 'id' => 1001 ) ) ), $object_id );

		$this->assertInstanceOf( \WP_Post::class, get_post( $kept_file ) );
		$this->assertNull( get_post( $removed_file ) );
		$this->assertSame( array( $kept_file ), get_post_meta( $object_id, 'images', true ) );
	}

	/**
	 * Test that broker avatars are not found as object files and are not deleted as not updated files.
	 *
	 * @return void
	 */
	public function test_broker_avatars_are_ignored(): void {
		// the broker avatar uses the broker ID as file ID.
		$avatar_id = $this->create_file( 0, 555, false );
		update_post_meta( $avatar_id, 'cfprop_broker_avatar', 555 );

		$files = \ConnectorForPropstack\Propstack\Files::get_instance();

		// it must not be found as an object file, but as a broker avatar.
		$this->assertSame( 0, $files->is_file_in_media_library( 555 ) );
		$this->assertSame( $avatar_id, $files->get_broker_avatar( 555 ) );

		// it must not be deleted during the cleanup of not updated files.
		$files->delete_not_updated_files();
		$this->assertInstanceOf( \WP_Post::class, get_post( $avatar_id ) );
	}

	/**
	 * Test that an object file with the same ID as a broker is still found.
	 *
	 * @return void
	 */
	public function test_object_file_with_same_id_as_broker_is_found(): void {
		$avatar_id = $this->create_file( 0, 556, true );
		update_post_meta( $avatar_id, 'cfprop_broker_avatar', 556 );
		$file_id = $this->create_file( $this->create_object(), 556, true );

		$this->assertSame( $file_id, \ConnectorForPropstack\Propstack\Files::get_instance()->is_file_in_media_library( 556 ) );
	}
}

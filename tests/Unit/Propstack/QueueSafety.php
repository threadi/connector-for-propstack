<?php
/**
 * File for safety tests against \ConnectorForPropstack\Propstack\Queue.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use ReflectionProperty;
use WP_Error;
use WP_Post;

/**
 * Object for safety tests against \ConnectorForPropstack\Propstack\Queue.
 */
class QueueSafety extends ConnectorForPropstackTestCase {
	/**
	 * A public IP-based URL for downloads, which does not need a DNS lookup.
	 *
	 * @var string
	 */
	private const DOWNLOAD_URL = 'https://3.168.40.35/photos/queue.png';

	/**
	 * The HTTP status the download mock should return.
	 *
	 * @var int
	 */
	private int $mock_status = 404;

	/**
	 * Count of mocked downloads.
	 *
	 * @var int
	 */
	private int $download_count = 0;

	/**
	 * The post-ID of the object used in the tests.
	 *
	 * @var int
	 */
	private int $object_id = 0;

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// register the post types (the test suite resets post types after each test).
		\ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->register();
		\ConnectorForPropstack\Propstack\PostTypes\Queue::get_instance()->register();

		// reset the cached map of queue entries in the singleton.
		$this->reset_queue_map();

		// enable the queue and set the used image size.
		update_option( 'propstack_connector_queue', 1 );
		update_option( 'propstack_connector_image_size', 'big_url' );
		update_option( 'propstack_connector_queue_limit', 10 );

		// mock the downloads.
		$this->mock_status    = 404;
		$this->download_count = 0;
		add_filter( 'pre_http_request', array( $this, 'mock_download' ), 5, 3 );

		// create the object.
		$this->object_id = self::factory()->post->create(
			array(
				'post_type'   => \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name(),
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Clean up the test environment after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		// remove the filters.
		remove_filter( 'pre_http_request', array( $this, 'mock_download' ), 5 );
		remove_filter( 'cfprop_queue_max_attempts', array( $this, 'get_two_max_attempts' ) );
		remove_filter( 'cfprop_queue_max_attempts', '__return_zero' );

		// delete all queue entries.
		foreach ( $this->get_queue_entries() as $entry ) {
			wp_delete_post( $entry->ID, true );
		}

		// delete all attachments of the object including their files.
		foreach ( get_children( array( 'post_parent' => $this->object_id, 'post_type' => 'attachment' ) ) as $attachment ) {
			wp_delete_attachment( $attachment->ID, true );
		}

		// reset the options.
		delete_option( 'propstack_connector_queue_limit' );

		// reset the cached map of queue entries in the singleton.
		$this->reset_queue_map();

		parent::tear_down();
	}

	/**
	 * Reset the cached map of queue entries in the Queue singleton.
	 *
	 * @return void
	 */
	private function reset_queue_map(): void {
		$property = new ReflectionProperty( \ConnectorForPropstack\Propstack\Queue::class, 'queue_map' );
		$property->setAccessible( true );
		$property->setValue( \ConnectorForPropstack\Propstack\Queue::get_instance(), null );
	}

	/**
	 * Mock the download of files.
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

		++$this->download_count;

		// write a tiny PNG on success.
		if ( 200 === $this->mock_status && ! empty( $parsed_args['stream'] ) && ! empty( $parsed_args['filename'] ) ) {
			file_put_contents( $parsed_args['filename'], base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		}

		return array(
			'headers'  => array(),
			'body'     => '',
			'response' => array(
				'code'    => $this->mock_status,
				'message' => '',
			),
			'cookies'  => array(),
			'filename' => $parsed_args['filename'] ?? '',
		);
	}

	/**
	 * Return 2 as max attempts.
	 *
	 * @return int
	 */
	public function get_two_max_attempts(): int {
		return 2;
	}

	/**
	 * Return all queue entries.
	 *
	 * @return array<int,WP_Post>
	 */
	private function get_queue_entries(): array {
		return get_posts(
			array(
				'post_type'      => \ConnectorForPropstack\Propstack\PostTypes\Queue::get_instance()->get_name(),
				'post_status'    => 'any',
				'posts_per_page' => -1,
			)
		);
	}

	/**
	 * Return the queue entry for the given Propstack file ID.
	 *
	 * @param int $file_id The Propstack file ID.
	 *
	 * @return WP_Post|null
	 */
	private function get_queue_entry( int $file_id ): ?WP_Post {
		foreach ( $this->get_queue_entries() as $entry ) {
			if ( (string) $file_id === $entry->post_title ) {
				return $entry;
			}
		}
		return null;
	}

	/**
	 * Return the Propstack file IDs of the entries which get_queue() returns.
	 *
	 * @return array<int,int>
	 */
	private function get_queued_file_ids(): array {
		return array_map( fn( $post ) => absint( $post->post_title ), \ConnectorForPropstack\Propstack\Queue::get_instance()->get_queue() );
	}

	/**
	 * Return file data like the Propstack API.
	 *
	 * @param int                 $file_id The file ID.
	 * @param array<string,mixed> $data    Additional data (optional).
	 *
	 * @return array<string,mixed>
	 */
	private function get_file_data( int $file_id, array $data = array() ): array {
		return array_merge(
			array(
				'id'      => $file_id,
				'name'    => 'queue-' . $file_id . '.png',
				'big_url' => self::DOWNLOAD_URL,
			),
			$data
		);
	}

	/**
	 * Add the given files to the queue like during an object import.
	 *
	 * @param array<int,array<string,mixed>> $files The list of files.
	 *
	 * @return void
	 */
	private function add_files( array $files ): void {
		\ConnectorForPropstack\Propstack\Queue::get_instance()->add_files_during_import(
			array(
				'title'  => 'Test object',
				'images' => $files,
			),
			$this->object_id
		);
	}

	/**
	 * Test that the post type for the queue is not public.
	 *
	 * @return void
	 */
	public function test_post_type_is_not_public(): void {
		$post_type = get_post_type_object( 'cfprop_queue' );

		$this->assertInstanceOf( \WP_Post_Type::class, $post_type );
		$this->assertFalse( $post_type->public );
		$this->assertFalse( $post_type->publicly_queryable );
		$this->assertFalse( $post_type->show_in_rest );
		$this->assertTrue( $post_type->exclude_from_search );
		$this->assertFalse( $post_type->show_ui );
		$this->assertFalse( $post_type->query_var );
		$this->assertFalse( $post_type->rewrite );
	}

	/**
	 * Test that private and not-for-exposé images are not added to the queue.
	 *
	 * @return void
	 */
	public function test_private_and_not_for_exposee_images_are_not_queued(): void {
		$this->add_files(
			array(
				$this->get_file_data( 8001, array( 'is_private' => true ) ),
				$this->get_file_data( 8002, array( 'is_not_for_exposee' => true ) ),
				$this->get_file_data( 8003 ),
			)
		);

		$this->assertNull( $this->get_queue_entry( 8001 ) );
		$this->assertNull( $this->get_queue_entry( 8002 ) );
		$this->assertInstanceOf( WP_Post::class, $this->get_queue_entry( 8003 ) );
		$this->assertSame( array( 8003 ), $this->get_queued_file_ids() );
	}

	/**
	 * Test that an existing queue entry is removed if the image has been changed to private.
	 *
	 * @return void
	 */
	public function test_queued_image_is_removed_if_it_becomes_private(): void {
		$this->add_files( array( $this->get_file_data( 8011 ) ) );
		$this->assertInstanceOf( WP_Post::class, $this->get_queue_entry( 8011 ) );

		$this->add_files( array( $this->get_file_data( 8011, array( 'is_private' => true ) ) ) );
		$this->assertNull( $this->get_queue_entry( 8011 ) );
	}

	/**
	 * Test that nothing is queued if the queue is disabled.
	 *
	 * @return void
	 */
	public function test_nothing_is_queued_if_queue_is_disabled(): void {
		update_option( 'propstack_connector_queue', 0 );

		$this->add_files( array( $this->get_file_data( 8021 ) ) );

		$this->assertNull( $this->get_queue_entry( 8021 ) );
	}

	/**
	 * Test that failed entries are retried until the max attempts are reached and then excluded from the queue.
	 *
	 * @return void
	 */
	public function test_failed_entry_is_retried_until_max_attempts(): void {
		add_filter( 'cfprop_queue_max_attempts', array( $this, 'get_two_max_attempts' ) );
		$this->add_files( array( $this->get_file_data( 8101 ) ) );
		$entry_id = $this->get_queue_entry( 8101 )->ID;

		// first run fails.
		\ConnectorForPropstack\Propstack\Queue::get_instance()->process( '' );
		$this->assertSame( 1, $this->download_count );
		$this->assertSame( 1, absint( get_post_meta( $entry_id, 'failed_attempts', true ) ) );
		$this->assertGreaterThan( 0, absint( get_post_meta( $entry_id, 'failed', true ) ) );
		$this->assertSame( array( 8101 ), $this->get_queued_file_ids() );

		// second run fails, too, so the max attempts are reached.
		\ConnectorForPropstack\Propstack\Queue::get_instance()->process( '' );
		$this->assertSame( 2, $this->download_count );
		$this->assertSame( 2, absint( get_post_meta( $entry_id, 'failed_attempts', true ) ) );
		$this->assertSame( array(), $this->get_queued_file_ids() );

		// the entry still exists, but is not processed anymore.
		$this->assertInstanceOf( WP_Post::class, get_post( $entry_id ) );
		\ConnectorForPropstack\Propstack\Queue::get_instance()->process( '' );
		$this->assertSame( 2, $this->download_count );
	}

	/**
	 * Test that failed entries are sorted behind new entries.
	 *
	 * @return void
	 */
	public function test_failed_entry_is_sorted_behind_new_entries(): void {
		$this->add_files( array( $this->get_file_data( 8111 ) ) );
		\ConnectorForPropstack\Propstack\Queue::get_instance()->process( '' );

		$this->add_files( array( $this->get_file_data( 8112 ) ) );

		$this->assertSame( array( 8112, 8111 ), $this->get_queued_file_ids() );
	}

	/**
	 * Test that adding a failed file again clears its failure state.
	 *
	 * @return void
	 */
	public function test_re_adding_clears_failure_meta(): void {
		add_filter( 'cfprop_queue_max_attempts', array( $this, 'get_two_max_attempts' ) );
		$this->add_files( array( $this->get_file_data( 8121 ) ) );
		$entry_id = $this->get_queue_entry( 8121 )->ID;
		\ConnectorForPropstack\Propstack\Queue::get_instance()->process( '' );
		\ConnectorForPropstack\Propstack\Queue::get_instance()->process( '' );
		$this->assertSame( array(), $this->get_queued_file_ids() );

		// add the file again, e.g. during the next import.
		$this->reset_queue_map();
		$this->add_files( array( $this->get_file_data( 8121 ) ) );

		// the same entry must be used and its failure state must be cleared.
		$this->assertSame( $entry_id, $this->get_queue_entry( 8121 )->ID );
		$this->assertCount( 1, $this->get_queue_entries() );
		$this->assertFalse( metadata_exists( 'post', $entry_id, 'failed' ) );
		$this->assertFalse( metadata_exists( 'post', $entry_id, 'failed_attempts' ) );
		$this->assertSame( array( 8121 ), $this->get_queued_file_ids() );
	}

	/**
	 * Test that a successful processing imports the file, assigns it to the object and removes the entry.
	 *
	 * @return void
	 */
	public function test_successful_entry_is_imported_and_removed(): void {
		$this->mock_status = 200;
		$this->add_files( array( $this->get_file_data( 8131 ) ) );
		$entry_id = $this->get_queue_entry( 8131 )->ID;

		\ConnectorForPropstack\Propstack\Queue::get_instance()->process( '' );

		$attachment_id = \ConnectorForPropstack\Propstack\Files::get_instance()->is_file_in_media_library( 8131 );
		$this->assertGreaterThan( 0, $attachment_id );
		$this->assertNull( get_post( $entry_id ) );
		$this->assertContains( $attachment_id, (array) get_post_meta( $this->object_id, 'images', true ) );

		// an already imported file is not added to the queue again.
		$this->reset_queue_map();
		$this->add_files( array( $this->get_file_data( 8131 ) ) );
		$this->assertNull( $this->get_queue_entry( 8131 ) );
	}

	/**
	 * Test that the max attempts could not be lower than 1.
	 *
	 * @return void
	 */
	public function test_max_attempts_is_at_least_one(): void {
		$this->assertSame( 3, \ConnectorForPropstack\Propstack\Queue::get_instance()->get_max_attempts() );

		add_filter( 'cfprop_queue_max_attempts', '__return_zero' );
		$this->assertSame( 1, \ConnectorForPropstack\Propstack\Queue::get_instance()->get_max_attempts() );
	}
}

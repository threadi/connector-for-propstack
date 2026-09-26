<?php
/**
 * Tests for the extensions of the media library.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Propstack\MediaLibrary as MediaLibraryObject;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;
use WP_Query;

/**
 * Object to test the extensions of the media library.
 */
class MediaLibrary extends ConnectorForPropstackTestCase {
	/**
	 * The ID of the test object.
	 *
	 * @var int
	 */
	private int $object_id = 0;

	/**
	 * The ID of an image of the test object.
	 *
	 * @var int
	 */
	private int $object_image_id = 0;

	/**
	 * The ID of a broker avatar.
	 *
	 * @var int
	 */
	private int $avatar_id = 0;

	/**
	 * The ID of a normal media file.
	 *
	 * @var int
	 */
	private int $media_id = 0;

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// register the post type for objects (the test suite resets post types after each test).
		ImmoObject::get_instance()->register();

		// create an object with an image.
		$this->object_id = self::factory()->post->create(
			array(
				'post_type'   => ImmoObject::get_instance()->get_name(),
				'post_status' => 'publish',
				'post_title'  => 'Test object for the media library',
			)
		);
		update_post_meta( $this->object_id, 'object_id', '4711' );
		update_post_meta( $this->object_id, 'short_address', 'Teststreet 1, 04107 Leipzig' );
		$this->object_image_id = self::factory()->attachment->create( array( 'post_parent' => $this->object_id ) );
		update_post_meta( $this->object_image_id, 'propstack_file_id', 1001 );

		// create a broker avatar.
		$this->avatar_id = self::factory()->attachment->create();
		update_post_meta( $this->avatar_id, 'propstack_file_id', 1002 );
		update_post_meta( $this->avatar_id, 'cfprop_broker_avatar', 1002 );

		// create a normal media file.
		$this->media_id = self::factory()->attachment->create();
	}

	/**
	 * Clean up the test environment after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		unset( $_GET['cfprop_media'], $_REQUEST['query'] );
		parent::tear_down();
	}

	/**
	 * Return the IDs of all attachments found with the given query arguments.
	 *
	 * @param array<string,mixed> $query_args The query arguments.
	 *
	 * @return array<int,int>
	 */
	private function get_attachment_ids( array $query_args ): array {
		$query = new WP_Query(
			array_merge(
				array(
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				),
				$query_args
			)
		);
		return array_map( 'absint', $query->posts );
	}

	/**
	 * Test that the filter in the grid view returns only object images.
	 *
	 * @return void
	 */
	public function test_grid_view_filter_returns_only_object_images(): void {
		$_REQUEST['query'] = array( 'cfprop_media' => 'object_images' );

		$query_args = apply_filters( 'ajax_query_attachments_args', array( 'post_type' => 'attachment' ) );
		$ids        = $this->get_attachment_ids( $query_args );

		$this->assertContains( $this->object_image_id, $ids );
		$this->assertNotContains( $this->avatar_id, $ids );
		$this->assertNotContains( $this->media_id, $ids );
	}

	/**
	 * Test that the grid view is not changed without our filter.
	 *
	 * @return void
	 */
	public function test_grid_view_without_filter(): void {
		$query_args = MediaLibraryObject::get_instance()->filter_grid_view( array( 'post_type' => 'attachment' ) );
		$this->assertArrayNotHasKey( 'meta_query', $query_args );

		// an empty or unknown value does not filter either.
		foreach ( array( '', 'unknown', array( 'object_images' ) ) as $value ) {
			$_REQUEST['query'] = array( 'cfprop_media' => $value );
			$query_args        = MediaLibraryObject::get_instance()->filter_grid_view( array( 'post_type' => 'attachment' ) );
			$this->assertArrayNotHasKey( 'meta_query', $query_args );
		}
	}

	/**
	 * Test that an existing meta query is kept and combined with ours.
	 *
	 * @return void
	 */
	public function test_grid_view_filter_keeps_existing_meta_query(): void {
		// mark only the object image with another meta.
		update_post_meta( $this->object_image_id, 'cfprop_test_marker', 1 );
		$second_image_id = self::factory()->attachment->create( array( 'post_parent' => $this->object_id ) );
		update_post_meta( $second_image_id, 'propstack_file_id', 1003 );

		$_REQUEST['query'] = array( 'cfprop_media' => 'object_images' );
		$query_args        = MediaLibraryObject::get_instance()->filter_grid_view(
			array(
				'post_type'  => 'attachment',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'cfprop_test_marker',
						'compare' => 'EXISTS',
					),
				),
			)
		);
		$ids               = $this->get_attachment_ids( $query_args );

		$this->assertSame( array( $this->object_image_id ), $ids );
	}

	/**
	 * Test that the filter in the list view returns only object images.
	 *
	 * @return void
	 */
	public function test_list_view_filter_returns_only_object_images(): void {
		set_current_screen( 'upload' );
		$_GET['cfprop_media'] = 'object_images';

		// simulate the main query of the list view.
		global $wp_the_query;
		$main_query   = $wp_the_query;
		$query        = new WP_Query();
		$wp_the_query = $query;
		$query->query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		$wp_the_query = $main_query;
		$ids          = array_map( 'absint', $query->posts );

		$this->assertContains( $this->object_image_id, $ids );
		$this->assertNotContains( $this->avatar_id, $ids );
		$this->assertNotContains( $this->media_id, $ids );

		set_current_screen( 'front' );
	}

	/**
	 * Test that the filter select is shown in the list view of the media library only.
	 *
	 * @return void
	 */
	public function test_filter_select(): void {
		// shown for the media library, with the actual value selected.
		$_GET['cfprop_media'] = 'object_images';
		ob_start();
		MediaLibraryObject::get_instance()->add_filter_select( 'attachment' );
		$output = (string) ob_get_clean();
		$this->assertStringContainsString( 'name="cfprop_media"', $output );
		$this->assertMatchesRegularExpression( '/value="object_images"\s+selected/', $output );

		// not shown for other post types.
		ob_start();
		MediaLibraryObject::get_instance()->add_filter_select( 'post' );
		$this->assertSame( '', (string) ob_get_clean() );
	}

	/**
	 * Test that the object of an image is detected.
	 *
	 * @return void
	 */
	public function test_get_object_of_attachment(): void {
		$media_library = MediaLibraryObject::get_instance();

		$immo_object = $media_library->get_object_of_attachment( get_post( $this->object_image_id ) );
		$this->assertInstanceOf( \ConnectorForPropstack\Propstack\ImmoObject::class, $immo_object );
		$this->assertSame( $this->object_id, $immo_object->get_id() );

		$this->assertFalse( $media_library->get_object_of_attachment( get_post( $this->avatar_id ) ) );
		$this->assertFalse( $media_library->get_object_of_attachment( get_post( $this->media_id ) ) );

		// an image assigned to a normal post is not an object image.
		$post_id  = self::factory()->post->create();
		$image_id = self::factory()->attachment->create( array( 'post_parent' => $post_id ) );
		update_post_meta( $image_id, 'propstack_file_id', 1004 );
		$this->assertFalse( $media_library->get_object_of_attachment( get_post( $image_id ) ) );
	}

	/**
	 * Test the information about the object of an image.
	 *
	 * @return void
	 */
	public function test_object_info(): void {
		$info = MediaLibraryObject::get_instance()->get_object_info( get_post( $this->object_image_id ) );

		$this->assertStringContainsString( 'Test object for the media library', $info );
		$this->assertStringContainsString( '4711', $info );
		$this->assertStringContainsString( 'Teststreet 1, 04107 Leipzig', $info );
		$this->assertStringContainsString( esc_url( get_permalink( $this->object_id ) ), $info );
		$this->assertStringContainsString( 'https://crm.propstack.de/app/portfolio/properties/4711', $info );

		// no information for other files.
		$this->assertSame( '', MediaLibraryObject::get_instance()->get_object_info( get_post( $this->avatar_id ) ) );
		$this->assertSame( '', MediaLibraryObject::get_instance()->get_object_info( get_post( $this->media_id ) ) );
	}

	/**
	 * Test that the object title is escaped in the information.
	 *
	 * @return void
	 */
	public function test_object_info_is_escaped(): void {
		wp_update_post(
			array(
				'ID'         => $this->object_id,
				'post_title' => 'Title <script>alert(1)</script>',
			)
		);

		$info = MediaLibraryObject::get_instance()->get_object_info( get_post( $this->object_image_id ) );
		$this->assertStringNotContainsString( '<script>', $info );
	}

	/**
	 * Test that the link to the object is only shown for published objects.
	 *
	 * @return void
	 */
	public function test_object_info_without_link_for_draft(): void {
		wp_update_post(
			array(
				'ID'          => $this->object_id,
				'post_status' => 'draft',
			)
		);

		$info = MediaLibraryObject::get_instance()->get_object_info( get_post( $this->object_image_id ) );
		$this->assertStringContainsString( 'Test object for the media library', $info );
		$this->assertStringNotContainsString( 'target="_blank">' . esc_html__( 'Show object', 'connector-for-propstack' ), $info );
	}

	/**
	 * Test that the object is added to the details of an image in the media modal.
	 *
	 * @return void
	 */
	public function test_object_field_in_media_modal(): void {
		$fields = apply_filters( 'attachment_fields_to_edit', array(), get_post( $this->object_image_id ) );
		$this->assertArrayHasKey( 'cfprop_object', $fields );
		$this->assertSame( 'html', $fields['cfprop_object']['input'] );
		$this->assertFalse( $fields['cfprop_object']['show_in_edit'] );
		$this->assertStringContainsString( 'Test object for the media library', $fields['cfprop_object']['html'] );

		// not added for other files.
		$fields = apply_filters( 'attachment_fields_to_edit', array(), get_post( $this->media_id ) );
		$this->assertArrayNotHasKey( 'cfprop_object', $fields );
	}

	/**
	 * Test that the meta box is added on the edit page of object images only.
	 *
	 * @return void
	 */
	public function test_meta_box(): void {
		global $wp_meta_boxes;

		set_current_screen( 'attachment' );

		// not added for a normal media file.
		MediaLibraryObject::get_instance()->add_meta_box( get_post( $this->media_id ) );
		$this->assertEmpty( $wp_meta_boxes['attachment']['side']['default']['cfprop-media-object'] ?? array() );

		// added for an object image.
		MediaLibraryObject::get_instance()->add_meta_box( get_post( $this->object_image_id ) );
		$this->assertNotEmpty( $wp_meta_boxes['attachment']['side']['default']['cfprop-media-object'] ?? array() );

		// the meta box shows the object.
		ob_start();
		MediaLibraryObject::get_instance()->show_meta_box( get_post( $this->object_image_id ) );
		$this->assertStringContainsString( 'Test object for the media library', (string) ob_get_clean() );

		// clean up.
		remove_meta_box( 'cfprop-media-object', 'attachment', 'side' );
		set_current_screen( 'front' );
	}

	/**
	 * Test that the script is only loaded in the media library.
	 *
	 * @return void
	 */
	public function test_script_only_in_media_library(): void {
		MediaLibraryObject::get_instance()->add_js( 'edit.php' );
		$this->assertFalse( wp_script_is( 'cfprop-media-library', 'enqueued' ) );

		MediaLibraryObject::get_instance()->add_js( 'upload.php' );
		$this->assertTrue( wp_script_is( 'cfprop-media-library', 'enqueued' ) );

		wp_dequeue_script( 'cfprop-media-library' );
	}
}

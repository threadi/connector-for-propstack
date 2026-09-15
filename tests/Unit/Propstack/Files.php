<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\Files.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\Files.
 */
class Files extends ConnectorForPropstackTestCase {
	/**
	 * Test response for get files if no files are imported.
	 *
	 * @return void
	 */
	public function test_empty_files_list(): void {
		// test it.
		$files = \ConnectorForPropstack\Propstack\Files::get_instance()->get_files();
		$this->assertIsObject( $files );
		$this->assertInstanceOf( 'WP_Query', $files );
		$this->assertEquals( 0, $files->found_posts );
	}

	/**
	 * Test that an already imported file is found again.
	 *
	 * @return void
	 */
	public function test_imported_file_is_found_again(): void {
		$attachment_id = $this->factory()->attachment->create();
		update_post_meta( $attachment_id, 'propstack_file_id', 409594 );

		$this->assertSame( $attachment_id, \ConnectorForPropstack\Propstack\Files::get_instance()->is_file_in_media_library( 409594 ) );
	}

	/**
	 * Test that an unknown file ID is not found.
	 *
	 * @return void
	 */
	public function test_unknown_file_is_not_found(): void {
		$this->assertSame( 0, \ConnectorForPropstack\Propstack\Files::get_instance()->is_file_in_media_library( 123456 ) );
	}
}

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

	/**
	 * Return file data like the Propstack API v1 delivers them.
	 *
	 * @return array<string,mixed>
	 */
	private function get_file_data_v1(): array {
		return array(
			'id'              => 143,
			'url'             => 'https://images.propstack.de/photos/example.jpg',
			'big_url'         => 'https://images.propstack.de/photos/big_example.jpg',
			'medium_url'      => 'https://images.propstack.de/photos/medium_example.jpg',
			'thumb_url'       => 'https://images.propstack.de/photos/thumb_example.jpg',
			'small_thumb_url' => 'https://images.propstack.de/photos/small_thumb_example.jpg',
			'square_url'      => 'https://images.propstack.de/photos/square_example.jpg',
		);
	}

	/**
	 * Return file data like the Propstack API v2 delivers them.
	 *
	 * @return array<string,mixed>
	 */
	private function get_file_data_v2(): array {
		return array(
			'id'   => 44794719,
			'url'  => 'https://images.propstack.de/photos/example.jpg',
			'urls' => array(
				'original' => 'https://images.propstack.de/photos/example.jpg',
				'thumb'    => 'https://images.propstack.de/photos/thumb_example.jpg',
				'small'    => 'https://images.propstack.de/photos/small_thumb_example.jpg',
				'medium'   => 'https://images.propstack.de/photos/medium_example.jpg',
				'large'    => 'https://images.propstack.de/photos/big_example.jpg',
			),
		);
	}

	/**
	 * Return the image sizes with the URL which is expected for them.
	 *
	 * @return array<string,array<int,string>>
	 */
	public static function get_image_sizes(): array {
		return array(
			'original'        => array( 'url', 'https://images.propstack.de/photos/example.jpg' ),
			'big'             => array( 'big_url', 'https://images.propstack.de/photos/big_example.jpg' ),
			'medium'          => array( 'medium_url', 'https://images.propstack.de/photos/medium_example.jpg' ),
			'thumbnail'       => array( 'thumb_url', 'https://images.propstack.de/photos/thumb_example.jpg' ),
			'small thumbnail' => array( 'small_thumb_url', 'https://images.propstack.de/photos/small_thumb_example.jpg' ),
		);
	}

	/**
	 * Test that the URL in the chosen image size is found in file data from API v1.
	 *
	 * @dataProvider get_image_sizes
	 *
	 * @param string $image_size The chosen image size.
	 * @param string $expected   The expected URL.
	 *
	 * @return void
	 */
	public function test_file_url_from_api_v1( string $image_size, string $expected ): void {
		update_option( 'propstack_connector_image_size', $image_size );

		$this->assertSame( $expected, \ConnectorForPropstack\Propstack\Files::get_instance()->get_file_url( $this->get_file_data_v1() ) );
	}

	/**
	 * Test that the URL in the chosen image size is found in file data from API v2.
	 *
	 * @dataProvider get_image_sizes
	 *
	 * @param string $image_size The chosen image size.
	 * @param string $expected   The expected URL.
	 *
	 * @return void
	 */
	public function test_file_url_from_api_v2( string $image_size, string $expected ): void {
		update_option( 'propstack_connector_image_size', $image_size );

		$this->assertSame( $expected, \ConnectorForPropstack\Propstack\Files::get_instance()->get_file_url( $this->get_file_data_v2() ) );
	}

	/**
	 * Test that the original file is used if API v2 does not deliver the chosen image size.
	 *
	 * @return void
	 */
	public function test_file_url_from_api_v2_uses_original_as_fallback(): void {
		update_option( 'propstack_connector_image_size', 'square_url' );

		$this->assertSame( 'https://images.propstack.de/photos/example.jpg', \ConnectorForPropstack\Propstack\Files::get_instance()->get_file_url( $this->get_file_data_v2() ) );
	}

	/**
	 * Test that no URL is returned if the file data does not contain any.
	 *
	 * @return void
	 */
	public function test_file_url_is_empty_without_any_url(): void {
		$this->assertSame( '', \ConnectorForPropstack\Propstack\Files::get_instance()->get_file_url( array( 'id' => 1 ) ) );
	}
}

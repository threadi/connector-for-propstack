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
}

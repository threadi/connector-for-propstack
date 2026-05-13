<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\FieldCategories.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\FieldCategories.
 */
class FieldCategories extends ConnectorForPropstackTestCase {
	/**
	 * Test the list of categories.
	 *
	 * @return void
	 */
	public function test_categories_as_objects(): void {
		$categories = \ConnectorForPropstack\Propstack\FieldCategories::get_instance()->get_categories_as_objects();
		$this->assertIsArray( $categories );
		$this->assertNotEmpty( $categories );
	}

	/**
	 * Test the list of category types.
	 *
	 * @return void
	 */
	public function test_category_types_as_objects(): void {
		$categories = \ConnectorForPropstack\Propstack\FieldCategories::get_instance()->get_category_types_as_objects();
		$this->assertIsArray( $categories );
		$this->assertNotEmpty( $categories );
	}
}

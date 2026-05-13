<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\Taxonomies.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Propstack\Taxonomy;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\Taxonomies.
 */
class Taxonomies extends ConnectorForPropstackTestCase {
	/**
	 * Test response for get files if no files are imported.
	 *
	 * @return void
	 */
	public function test_list_of_taxonomy_objects(): void {
		// test it.
		$taxonomies = \ConnectorForPropstack\Propstack\Taxonomies::get_instance()->get_taxonomies_as_objects();
		$this->assertIsArray( $taxonomies );
		$this->assertNotEmpty( $taxonomies );
	}

	/**
	 * Test response for get files if no files are imported.
	 *
	 * @return void
	 */
	public function test_list_of_taxonomy_array(): void {
		// test it.
		$taxonomies = \ConnectorForPropstack\Propstack\Taxonomies::get_instance()->get_taxonomies();
		$this->assertIsArray( $taxonomies );
		$this->assertNotEmpty( $taxonomies );
	}

	/**
	 * Return the list of our taxonomies.
	 *
	 * @return iterable
	 */
	public function get_taxonomies(): iterable {
		$taxonomies = \ConnectorForPropstack\Propstack\Taxonomies::get_instance()->get_taxonomies_as_objects();
		foreach( $taxonomies as $taxonomy ) {
			yield array( $taxonomy );
		}
	}

	/**
	 * Test the name of each taxonomy.
	 *
	 * @dataProvider get_taxonomies
	 *
	 * @param Taxonomy $obj Object of the taxonomy.
	 *
	 * @return void
	 */
	public function test_taxonomy_name( \ConnectorForPropstack\Propstack\Taxonomy $obj ): void {
		$this->assertIsString( $obj->get_name() );
		$this->assertNotEmpty( $obj->get_name() );
	}

	/**
	 * Test the title of each taxonomy.
	 *
	 * @dataProvider get_taxonomies
	 *
	 * @param Taxonomy $obj Object of the taxonomy.
	 *
	 * @return void
	 */
	public function test_taxonomy_title( \ConnectorForPropstack\Propstack\Taxonomy $obj ): void {
		$this->assertIsString( $obj->get_title() );
		$this->assertNotEmpty( $obj->get_title() );
	}

	/**
	 * Test the API field name of each taxonomy.
	 *
	 * @dataProvider get_taxonomies
	 *
	 * @param Taxonomy $obj Object of the taxonomy.
	 *
	 * @return void
	 */
	public function test_taxonomy_api_field_name( \ConnectorForPropstack\Propstack\Taxonomy $obj ): void {
		$this->assertIsString( $obj->get_api_field() );
		$this->assertNotEmpty( $obj->get_api_field() );
	}

	/**
	 * Test the fields of each taxonomy.
	 *
	 * @dataProvider get_taxonomies
	 *
	 * @param Taxonomy $obj Object of the taxonomy.
	 *
	 * @return void
	 */
	public function test_taxonomy_fields( \ConnectorForPropstack\Propstack\Taxonomy $obj ): void {
		$this->assertIsArray( $obj->get_fields() );
		if( ! in_array( $obj->get_name(), array( 'cfprop_object_category', 'cfprop_object_marketing_type', 'cfprop_object_property_type' ), true) ) {
			$this->assertNotEmpty( $obj->get_fields() );
		}
	}

	/**
	 * Test the fields of each taxonomy.
	 *
	 * @dataProvider get_taxonomies
	 *
	 * @param Taxonomy $obj Object of the taxonomy.
	 *
	 * @return void
	 */
	public function test_taxonomy_terms( \ConnectorForPropstack\Propstack\Taxonomy $obj ): void {
		$terms = $obj->get_terms();
		$this->assertIsArray( $terms );
	}
}

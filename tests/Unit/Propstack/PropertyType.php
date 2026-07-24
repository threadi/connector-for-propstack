<?php
/**
 * File for tests against \ConnectorForPropstack\Propstack\Taxonomies\PropertyType.
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack\Taxonomies;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\Taxonomies\PropertyType.
 *
 * The taxonomy holds a large mapping table between the API values of Propstack and
 * the local terms. A duplicated slug or a duplicated API value would silently map
 * two different property types onto the same term, so the consistency of that
 * table is checked here.
 */
class PropertyType extends ConnectorForPropstackTestCase {
	/**
	 * The taxonomy object to test.
	 *
	 * @var \ConnectorForPropstack\Propstack\Taxonomies\PropertyType
	 */
	private \ConnectorForPropstack\Propstack\Taxonomies\PropertyType $taxonomy_obj;

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->taxonomy_obj = \ConnectorForPropstack\Propstack\Taxonomies\PropertyType::get_instance();
	}

	/**
	 * Return the installed terms of this taxonomy.
	 *
	 * @return array<int,\WP_Term>
	 */
	private function get_installed_terms(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $this->taxonomy_obj->get_name(),
				'hide_empty' => false,
			)
		);

		return is_array( $terms ) ? $terms : array();
	}

	/**
	 * Test that the taxonomy is registered.
	 *
	 * @return void
	 */
	public function test_taxonomy_is_registered(): void {
		$this->assertTrue( taxonomy_exists( $this->taxonomy_obj->get_name() ) );
	}

	/**
	 * Test that the default terms have been installed.
	 *
	 * @return void
	 */
	public function test_default_terms_are_installed(): void {
		$this->assertNotEmpty( $this->get_installed_terms() );
	}

	/**
	 * Test that no slug is used twice.
	 *
	 * Hint: WordPress enforces unique slugs by adding a numeric suffix, so this is
	 * a sanity check only. A duplicate in the mapping table is caught by
	 * test_api_values_are_unique_per_language() instead.
	 *
	 * @return void
	 */
	public function test_slugs_are_unique(): void {
		$slugs = wp_list_pluck( $this->get_installed_terms(), 'slug' );

		$this->assertNotEmpty( $slugs );
		$this->assertSameSize( $slugs, array_unique( $slugs ) );
	}

	/**
	 * Test that no term name is used twice.
	 *
	 * @return void
	 */
	public function test_names_are_unique(): void {
		$names = wp_list_pluck( $this->get_installed_terms(), 'name' );

		$this->assertNotEmpty( $names );
		$this->assertSameSize( $names, array_unique( $names ) );
	}

	/**
	 * Test that a known API value can be resolved to a term.
	 *
	 * @return void
	 */
	public function test_known_api_value_resolves_to_term(): void {
		$term_id = $this->taxonomy_obj->get_term_id_by_api_value( 'MAISONETTE', 'en' );

		$this->assertIsInt( $term_id );
		$this->assertGreaterThan( 0, $term_id );
	}

	/**
	 * Test that an unknown API value does not resolve to a term.
	 *
	 * @return void
	 */
	public function test_unknown_api_value_does_not_resolve(): void {
		$this->assertFalse( $this->taxonomy_obj->get_term_id_by_api_value( 'THIS_VALUE_DOES_NOT_EXIST', 'de' ) );
	}

	/**
	 * Test that an empty API value does not resolve to a term.
	 *
	 * @return void
	 */
	public function test_empty_api_value_does_not_resolve(): void {
		$this->assertFalse( $this->taxonomy_obj->get_term_id_by_api_value( '', 'en' ) );
	}

	/**
	 * Test that no API value is used by more than one term of the same language.
	 *
	 * Hint: get_term_id_by_api_value() matches on the term meta "api" together with
	 * "language_code" and returns the first hit. A duplicated API value would
	 * therefore map two property types onto the same term without any error.
	 *
	 * @return void
	 */
	public function test_api_values_are_unique_per_language(): void {
		$resolved = array();

		foreach ( $this->get_installed_terms() as $term ) {
			$api_value     = get_term_meta( $term->term_id, 'api', true );
			$language_code = get_term_meta( $term->term_id, 'language_code', true );

			// skip terms without an API value.
			if ( empty( $api_value ) ) {
				continue;
			}

			$key = $language_code . '|' . $api_value;

			$this->assertArrayNotHasKey( $key, $resolved, 'The API value ' . $api_value . ' is used by more than one term in language ' . $language_code . '.' );

			$resolved[ $key ] = $term->term_id;
		}

		$this->assertNotEmpty( $resolved );
	}
}

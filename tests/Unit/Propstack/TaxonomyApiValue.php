<?php
/**
 * File for tests against the API value resolution of taxonomies during object import.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\Taxonomies\Broker;
use ConnectorForPropstack\Propstack\Taxonomies\Status;
use ConnectorForPropstack\Propstack\Taxonomy;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against the API value resolution of taxonomies during object import.
 *
 * The Propstack API delivers some taxonomy fields as nested objects (e.g. "broker" and
 * "property_status"). Handing such a structure to get_term_id_by_api_value() lets
 * WP_Meta_Query call wpdb::prepare() with an array for a single placeholder. WordPress
 * then aborts the preparation, the "api" condition silently drops out of the query and
 * an arbitrary term of the taxonomy is returned as a match.
 *
 * These tests pin that every taxonomy resolves its API value to a scalar and that a
 * non-scalar value can never produce a false positive.
 */
class TaxonomyApiValue extends ConnectorForPropstackTestCase {
	/**
	 * Return a single object from the API fixture.
	 *
	 * @return array<string,mixed>
	 */
	private function get_immo_object(): array {
		$content = Helper::get_wp_filesystem()->get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'units_full.json' );

		$this->assertIsString( $content, 'The fixture units_full.json could not be read.' );

		$data = json_decode( $content, true );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertNotEmpty( $data['data'] );

		return $data['data'][0];
	}

	/**
	 * Return the list of our taxonomies.
	 *
	 * @return iterable<array<int,Taxonomy>>
	 */
	public function get_taxonomies(): iterable {
		foreach ( \ConnectorForPropstack\Propstack\Taxonomies::get_instance()->get_taxonomies_as_objects() as $taxonomy ) {
			yield $taxonomy->get_name() => array( $taxonomy );
		}
	}

	/**
	 * Test that every taxonomy resolves its API value to a scalar.
	 *
	 * This is the resolution Taxonomies::assign_term_during_import() performs before it
	 * hands the value to get_term_id_by_api_value(). A taxonomy whose API field holds a
	 * nested object must declare an "api_sub_field" to reduce it to a scalar.
	 *
	 * @dataProvider get_taxonomies
	 *
	 * @param Taxonomy $taxonomy Object of the taxonomy.
	 *
	 * @return void
	 */
	public function test_api_value_is_scalar( Taxonomy $taxonomy ): void {
		$immo_object = $this->get_immo_object();

		// skip taxonomies whose API field is not part of this fixture.
		if ( empty( $taxonomy->get_api_field() ) || empty( $immo_object[ $taxonomy->get_api_field() ] ) ) {
			$this->markTestSkipped( 'The API field of this taxonomy is not part of the fixture.' );
		}

		// resolve the value the same way the import does.
		$value = $immo_object[ $taxonomy->get_api_field() ];
		if ( ! empty( $taxonomy->get_api_subfield() ) ) {
			$value = $immo_object[ $taxonomy->get_api_field() ][ $taxonomy->get_api_subfield() ];
		}

		$this->assertIsScalar(
			$value,
			sprintf(
				'The taxonomy "%1$s" resolves the API field "%2$s" to %3$s. Declare an "api_sub_field" on this taxonomy.',
				$taxonomy->get_name(),
				$taxonomy->get_api_field(),
				get_debug_type( $value )
			)
		);
	}

	/**
	 * Test that the two taxonomies with a nested API field declare their subfield.
	 *
	 * Kept separate from the data driven test above so the expectation stays readable
	 * even if the fixture changes.
	 *
	 * @return void
	 */
	public function test_nested_taxonomies_declare_their_subfield(): void {
		$this->assertSame( 'id', Broker::get_instance()->get_api_subfield() );
		$this->assertSame( 'id', Status::get_instance()->get_api_subfield() );
	}

	/**
	 * Test that a non-scalar value never returns a term ID.
	 *
	 * Without the guard in get_term_id_by_api_value() the "api" condition drops out of
	 * the meta query and the first term of the taxonomy is returned as a match.
	 *
	 * @dataProvider provide_non_scalar_values
	 *
	 * @param mixed $value The value as delivered by the Propstack API.
	 *
	 * @return void
	 */
	public function test_non_scalar_api_value_returns_false( mixed $value ): void {
		// create two terms so a dropped condition would produce a false positive.
		$taxonomy = Status::get_instance();
		$this->factory()->term->create_many( 2, array( 'taxonomy' => $taxonomy->get_name() ) );

		$this->assertFalse( $taxonomy->get_term_id_by_api_value( $value, 'de' ) );
	}

	/**
	 * Provide the non-scalar values to test.
	 *
	 * @return array<string,array<int,mixed>>
	 */
	public static function provide_non_scalar_values(): array {
		return array(
			'propstack status object' => array( array( 'id' => 222051, 'name' => 'Vermarktung' ) ),
			'list'                    => array( array( 'a', 'b' ) ),
			'empty array'             => array( array() ),
			'object'                  => array( new \stdClass() ),
		);
	}

	/**
	 * Test that the import stores the API value as term meta.
	 *
	 * Terms of this taxonomy are created by the object import, not on activation.
	 * Without the "api" meta the next import cannot find them again and creates a
	 * duplicate term on every run.
	 *
	 * @return void
	 */
	public function test_import_stores_api_meta_on_created_terms(): void {
		$immo_object = $this->get_immo_object();
		$post_id     = $this->factory()->post->create();

		// run the assignment as the import does.
		\ConnectorForPropstack\Propstack\Taxonomies::get_instance()->assign_term_during_import( $immo_object, $post_id, 'de' );

		// only Broker creates its terms during the object import, Status is filled by the states import.
		$taxonomy_name = Broker::get_instance()->get_name();
		$api_value     = (string) $immo_object['broker']['id'];

		$terms = wp_get_post_terms( $post_id, $taxonomy_name );

		$this->assertIsArray( $terms );
		$this->assertCount( 1, $terms, sprintf( 'No term assigned for taxonomy "%1$s".', $taxonomy_name ) );

		$this->assertSame(
			$api_value,
			(string) get_term_meta( $terms[0]->term_id, 'api', true ),
			sprintf( 'The term of taxonomy "%1$s" does not carry the API value as meta.', $taxonomy_name )
		);
	}

	/**
	 * Test that a second import does not create a duplicate term.
	 *
	 * This is the effect the missing "api" meta had: the lookup found nothing, so every
	 * import added another term for the same broker and the same status.
	 *
	 * @return void
	 */
	public function test_import_reuses_existing_terms(): void {
		$immo_object = $this->get_immo_object();
		$taxonomies  = \ConnectorForPropstack\Propstack\Taxonomies::get_instance();

		// import the same object into two different posts.
		$first_post_id  = $this->factory()->post->create();
		$second_post_id = $this->factory()->post->create();

		$taxonomies->assign_term_during_import( $immo_object, $first_post_id, 'de' );
		$taxonomies->assign_term_during_import( $immo_object, $second_post_id, 'de' );

		// check that only one term exists per taxonomy.
		$taxonomy = Broker::get_instance();
		$terms    = get_terms(
			array(
				'taxonomy'   => $taxonomy->get_name(),
				'hide_empty' => false,
			)
		);

		$this->assertIsArray( $terms );
		$this->assertCount(
			1,
			$terms,
			sprintf( 'The second import created an additional term in taxonomy "%1$s".', $taxonomy->get_name() )
		);
	}
}

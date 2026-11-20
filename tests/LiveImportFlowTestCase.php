<?php
/**
 * File with the base object for the tests of the complete import flow against the live Propstack API.
 *
 * The objects are imported once per test class (in "set_up_live_class()") and checked by the tests of
 * the class. Tests, which change the data (e.g. the deletion), run in their own transaction, which
 * is rolled back after the test. So each test sees the result of the first import.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests;

use ConnectorForPropstack\Propstack\Files;
use ConnectorForPropstack\Propstack\ImmoObject;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use ConnectorForPropstack\Propstack\Widgets\Archive;
use ConnectorForPropstack\Propstack\Widgets\Single;
use Throwable;

/**
 * Base object for the tests of the complete import flow, the concrete classes set the API version.
 */
abstract class LiveImportFlowTestCase extends LiveTestCase {
	/**
	 * The API version to test, set by each concrete class.
	 *
	 * @var string
	 */
	protected static string $api_version = '';

	/**
	 * The result of the first import, see run_complete_import().
	 *
	 * @var array{import:\ConnectorForPropstack\Propstack\Import_Base,runs:int,delivered:int,seconds:float}|null
	 */
	protected static ?array $first_import = null;

	/**
	 * The errors of the first import as plain texts.
	 *
	 * @var array<int,string>
	 */
	protected static array $first_import_errors = array();

	/**
	 * The requests to the Propstack API during the first import.
	 *
	 * @var array<int,array{method:string,url:string,status:int,error:string}>
	 */
	protected static array $first_import_requests = array();

	/**
	 * The post-IDs of the objects after the first import, indexed by their Propstack-ID.
	 *
	 * @var array<string,int>
	 */
	protected static array $post_ids = array();

	/**
	 * Import the objects once for all tests of this class.
	 *
	 * @return void
	 */
	protected static function set_up_live_class(): void {
		// reset the results of the last class.
		self::$first_import          = null;
		self::$first_import_errors   = array();
		self::$first_import_requests = array();
		self::$post_ids              = array();

		// bail if the live tests are not configured or this API version should not be tested.
		if ( ! self::is_configured() || ! self::is_api_version_enabled( static::$api_version ) ) {
			return;
		}

		// use the API version of this class.
		self::use_api_version( static::$api_version );

		// run the import.
		self::$api_requests          = array();
		self::$first_import          = self::run_complete_import();
		self::$first_import_errors   = self::get_error_texts( self::$first_import['import'] );
		self::$first_import_requests = self::$api_requests;

		// remember the post-IDs of the objects.
		foreach ( self::get_all_objects() as $post_id => $immo_object ) {
			self::$post_ids[ $immo_object->get_object_id() ] = $post_id;
		}

		self::report(
			sprintf(
				'API %1$s: %2$d objects delivered, %3$d imported, %4$d prevented by restrictions, %5$d requests, %6$d runs, %7$.1f s.',
				static::$api_version,
				self::$first_import['delivered'],
				count( self::$post_ids ),
				count( ImmoObjects::get_instance()->get_prevented_objects()['objects'] ),
				count( self::$first_import_requests ),
				self::$first_import['runs'],
				self::$first_import['seconds']
			)
		);
	}

	/**
	 * Prepare each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// skip if this API version should not be tested.
		$this->require_api_version( static::$api_version );

		// use the API version of this class.
		self::use_api_version( static::$api_version );
	}

	/**
	 * Test that the import runs without any error.
	 *
	 * @return void
	 */
	public function test_import_runs_without_errors(): void {
		$this->assertNotNull( self::$first_import, 'The import has not been run.' );
		$this->assertEmpty(
			self::$first_import_errors,
			"The import ended with errors:\n  " . implode( "\n  ", self::$first_import_errors ) . "\n\nRequests:\n" . self::get_api_requests_as_text( self::$first_import_requests )
		);
		$this->assertLessThan( 10000, self::$first_import['runs'], 'The import did not finish.' );
		$this->assertSame( 0, absint( get_option( CFPROP_IMPORT_RUNNING ) ), 'The marker of the running import has not been released.' );
	}

	/**
	 * Test that each request to the Propstack API during the import has been answered successfully.
	 *
	 * @return void
	 */
	public function test_import_requests_are_successful(): void {
		$this->assertNotEmpty( self::$first_import_requests, 'The import did not send any request to the Propstack API.' );

		$failed = array_filter( self::$first_import_requests, static fn( array $request ): bool => 200 !== $request['status'] );
		$this->assertEmpty( $failed, "The following requests to the Propstack API failed:\n" . self::get_api_requests_as_text( $failed ) );
	}

	/**
	 * Test that each delivered object has been imported or has been prevented by a restriction.
	 *
	 * Detects objects, which got lost during the import (e.g. by a changed pagination of the API).
	 *
	 * @return void
	 */
	public function test_all_delivered_objects_are_imported(): void {
		$this->assertNotNull( self::$first_import, 'The import has not been run.' );

		$prevented = ImmoObjects::get_instance()->get_prevented_objects()['objects'];

		$this->assertGreaterThan( 0, self::$first_import['delivered'], 'Propstack did not deliver any object for the import. Use an account with objects for the live tests.' );
		$this->assertNotEmpty( self::$post_ids, 'No object has been imported, ' . count( $prevented ) . ' objects have been prevented by restrictions.' );
		$this->assertSame(
			self::$first_import['delivered'],
			count( self::$post_ids ) + count( $prevented ),
			sprintf( 'Propstack delivered %1$d objects, but %2$d have been imported and %3$d have been prevented.', self::$first_import['delivered'], count( self::$post_ids ), count( $prevented ) )
		);
		$this->assertTrue( ImmoObjects::get_instance()->has_objects(), 'The plugin does not know that objects have been imported.' );
	}

	/**
	 * Test that each imported object has its identifying data and an object type.
	 *
	 * @return void
	 */
	public function test_imported_objects_are_complete(): void {
		$problems = array();
		$language = self::get_config()['language'];

		foreach ( self::get_all_objects() as $post_id => $immo_object ) {
			$label = self::get_object_label( $immo_object );

			if ( '' === $immo_object->get_object_id() ) {
				$problems[] = $label . ': the Propstack-ID is missing.';
			}
			if ( get_post_meta( $post_id, 'language_code', true ) !== $language ) {
				$problems[] = $label . ': the language is "' . get_post_meta( $post_id, 'language_code', true ) . '" instead of "' . $language . '".';
			}
			if ( false === ObjectType::get_instance()->get_object_type_by_object_post_id( $post_id ) ) {
				$problems[] = $label . ': no known object type is assigned.';
			}
			if ( empty( $immo_object->get_api_response() ) ) {
				$problems[] = $label . ': the API response is not saved, the images could not be imported.';
			}
		}

		$this->assertEmpty( $problems, "Problems with imported objects:\n  " . implode( "\n  ", $problems ) );
	}

	/**
	 * Test that no object has been imported twice.
	 *
	 * @return void
	 */
	public function test_no_object_is_imported_twice(): void {
		$propstack_ids = array();
		foreach ( self::get_all_objects() as $immo_object ) {
			$propstack_ids[] = $immo_object->get_object_id();
		}

		$duplicates = array_keys( array_filter( array_count_values( $propstack_ids ), static fn( int $count ): bool => $count > 1 ) );
		$this->assertEmpty( $duplicates, 'The following Propstack-IDs have been imported more than once: ' . implode( ', ', $duplicates ) );
	}

	/**
	 * Test that the fields of each object could be read with the real data, with and without HTML.
	 *
	 * Each PHP warning, notice or deprecation during the formatting of a value is reported for its object.
	 *
	 * @return void
	 */
	public function test_fields_of_all_objects_could_be_read(): void {
		$problems = array();
		$values   = 0;

		foreach ( self::get_all_objects() as $immo_object ) {
			foreach ( array( false, true ) as $without_html ) {
				try {
					$fields  = $immo_object->get_fields( $without_html );
					$values += count( array_filter( $fields, static fn( mixed $value ): bool => ! empty( $value ) ) );
				} catch ( Throwable $e ) {
					$problems[] = self::get_object_label( $immo_object ) . ( $without_html ? ' (without HTML)' : ' (with HTML)' ) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
				}
			}
		}

		$this->assertEmpty( $problems, "Errors during reading the fields:\n  " . implode( "\n  ", $problems ) );
		$this->assertGreaterThan( 0, $values, 'No object has a field with a value.' );
	}

	/**
	 * Test that the single view of each object could be rendered with the real data.
	 *
	 * @return void
	 */
	public function test_single_view_of_all_objects_could_be_rendered(): void {
		$problems = array();

		foreach ( self::get_all_objects() as $immo_object ) {
			try {
				$output = Single::get_instance()->render( array( 'object_id' => $immo_object->get_object_id() ) );
				if ( '' === trim( wp_strip_all_tags( $output ) ) ) {
					$problems[] = self::get_object_label( $immo_object ) . ': the single view is empty.';
				}
			} catch ( Throwable $e ) {
				$problems[] = self::get_object_label( $immo_object ) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
			}
		}

		$this->assertEmpty( $problems, "Errors during rendering the single view:\n  " . implode( "\n  ", $problems ) );
	}

	/**
	 * Test that the list of objects could be rendered with the real data.
	 *
	 * @return void
	 */
	public function test_archive_could_be_rendered(): void {
		$output = Archive::get_instance()->render( array() );

		$this->assertNotEmpty( trim( wp_strip_all_tags( $output ) ), 'The list of objects is empty.' );
	}

	/**
	 * Test that a second import updates the existing objects: no object is added twice and none is removed.
	 *
	 * @return void
	 */
	public function test_second_import_keeps_the_objects(): void {
		$result = self::run_complete_import();

		$this->assertEmpty( self::get_error_texts( $result['import'] ), "The second import ended with errors:\n  " . implode( "\n  ", self::get_error_texts( $result['import'] ) ) );

		// get the post-IDs after the second import.
		$post_ids = array();
		foreach ( self::get_all_objects() as $post_id => $immo_object ) {
			$post_ids[ $immo_object->get_object_id() ] = $post_id;
		}
		ksort( $post_ids );
		$expected = self::$post_ids;
		ksort( $expected );

		$this->assertSame( $expected, $post_ids, 'The second import has changed the list of objects (format: Propstack-ID => post-ID).' );
	}

	/**
	 * Test that the check of an object finds an imported object: the configured one or the first one.
	 *
	 * @return void
	 */
	public function test_check_finds_an_imported_object(): void {
		// get the object to check.
		$object_id = self::get_config()['object_id'];
		if ( '' === $object_id ) {
			$this->assertNotEmpty( self::$post_ids, 'No object has been imported.' );
			$object_id = (string) array_key_first( self::$post_ids );
		}
		$this->assertArrayHasKey( $object_id, self::$post_ids, 'The configured object ' . $object_id . ' has not been imported.' );

		// check it like "wp cfprop check_object" does it.
		$check = ImmoObjects::get_instance()->get_object_check( $object_id );

		$language = self::get_config()['language'];
		$this->assertFalse( $check['has_errors'], 'The check of object ' . $object_id . ' ended with errors: ' . implode( ' ', $check['languages'][ $language ]['errors'] ?? array() ) );
		$this->assertTrue( $check['delivered'], 'Propstack does not deliver object ' . $object_id . ' for the import.' );
		$this->assertFalse( $check['languages'][ $language ]['prevented'], 'A restriction prevents the import of object ' . $object_id . ': ' . implode( ' ', $check['languages'][ $language ]['reasons'] ) );
		$this->assertSame( self::$post_ids[ $object_id ], $check['languages'][ $language ]['post_id'], 'The check did not find the imported object ' . $object_id . '.' );
	}

	/**
	 * Test the import of the images of one object: the configured one or the one with the fewest images.
	 *
	 * @return void
	 */
	public function test_images_of_an_object_are_imported(): void {
		// get the object and the images we expect.
		list( $immo_object, $expected_images ) = $this->get_object_for_images();

		// import its images.
		$this->import_images( $immo_object );

		// check the images.
		$problems = array();
		$images   = ImmoObjects::get_instance()->get_object( $immo_object->get_id() )->get_images();
		foreach ( $images as $attachment_id ) {
			$file = get_attached_file( $attachment_id );
			if ( 'attachment' !== get_post_type( $attachment_id ) ) {
				$problems[] = 'Attachment ' . $attachment_id . ' does not exist.';
			} elseif ( ! is_string( $file ) || ! file_exists( $file ) ) {
				$problems[] = 'The file of attachment ' . $attachment_id . ' does not exist.';
			} elseif ( ! wp_attachment_is_image( $attachment_id ) ) {
				$problems[] = 'Attachment ' . $attachment_id . ' is not an image.';
			}
		}

		$this->assertEmpty( $problems, self::get_object_label( $immo_object ) . ":\n  " . implode( "\n  ", $problems ) );
		$this->assertCount( $expected_images, $images, self::get_object_label( $immo_object ) . ': not all images have been imported.' );
	}

	/**
	 * Test the deletion of all objects, including the images of an object.
	 *
	 * @return void
	 */
	public function test_delete_all_objects_with_their_images(): void {
		// import the images of one object to check that they are removed too.
		list( $immo_object ) = $this->get_object_for_images();
		$this->import_images( $immo_object );
		$images = ImmoObjects::get_instance()->get_object( $immo_object->get_id() )->get_images();
		$this->assertNotEmpty( $images, 'No image has been imported.' );
		$files = array_filter( array_map( 'get_attached_file', $images ) );

		// delete all objects.
		ImmoObjects::get_instance()->delete_all( '' );

		$this->assertEmpty( self::get_all_objects(), 'Not all objects have been deleted.' );
		$this->assertSame( 0, absint( get_option( CFPROP_DELETE_RUNNING ) ), 'The marker of the running deletion has not been released.' );
		foreach ( $images as $attachment_id ) {
			$this->assertNull( get_post( $attachment_id ), 'Image ' . $attachment_id . ' has not been deleted with its object.' );
		}
		foreach ( $files as $file ) {
			$this->assertFileDoesNotExist( (string) $file, 'The file of an image has not been deleted with its object.' );
		}
	}

	/**
	 * Return the object to test the import of images and the amount of images we expect.
	 *
	 * Uses the configured object or the object with the fewest images, so the test downloads as
	 * few files as possible.
	 *
	 * @return array{0:ImmoObject,1:int}
	 */
	private function get_object_for_images(): array {
		$candidates = array();

		foreach ( self::get_all_objects() as $immo_object ) {
			// bail if another object is configured.
			if ( '' !== self::get_config()['object_id'] && self::get_config()['object_id'] !== $immo_object->get_object_id() ) {
				continue;
			}

			// count the images, which would be imported.
			$api_response = $immo_object->get_api_response();
			$count        = 0;
			foreach ( isset( $api_response['images'] ) && is_array( $api_response['images'] ) ? $api_response['images'] : array() as $file ) {
				if ( is_array( $file ) && ! Files::get_instance()->is_file_import_prevented( $file ) ) {
					++$count;
				}
			}

			if ( $count > 0 ) {
				$candidates[] = array( $immo_object, $count );
			}
		}

		// bail if no object has images.
		if ( empty( $candidates ) ) {
			$this->markTestSkipped( '' !== self::get_config()['object_id'] ? 'The configured object has no images.' : 'No imported object has images.' );
		}

		// use the object with the fewest images.
		usort( $candidates, static fn( array $a, array $b ): int => $a[1] <=> $b[1] );
		return $candidates[0];
	}

	/**
	 * Import the images of the given object, like the button on the object does it.
	 *
	 * @param ImmoObject $immo_object The object.
	 *
	 * @return void
	 */
	private function import_images( ImmoObject $immo_object ): void {
		$files_obj = Files::get_instance();
		$runs      = 0;
		do {
			$files_obj->import( $immo_object->get_id(), 'live-test' );
			++$runs;
			$this->assertFalse( $files_obj->is_locked_by_other_process(), 'The import of images is locked by another process.' );
		} while ( $files_obj->has_load_more() && $runs < 1000 );

		$this->assertLessThan( 1000, $runs, 'The import of images did not finish.' );
	}

	/**
	 * Return a label for the given object for messages of failed assertions.
	 *
	 * @param ImmoObject $immo_object The object.
	 *
	 * @return string
	 */
	protected static function get_object_label( ImmoObject $immo_object ): string {
		return 'Object "' . $immo_object->get_title() . '" (Propstack-ID ' . $immo_object->get_object_id() . ', post-ID ' . $immo_object->get_id() . ')';
	}
}

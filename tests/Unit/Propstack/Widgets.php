<?php
/**
 * File for tests against the widgets and their shortcodes.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Propstack;

use ConnectorForPropstack\Propstack\ImmoObject;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Widgets as WidgetsHandler;
use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against the widgets of \ConnectorForPropstack\Propstack\Widgets.
 *
 * The widgets are tested through their shortcodes, as this is the public entry point
 * where every visitor-controlled attribute arrives.
 */
class Widgets extends ConnectorForPropstackTestCase {
	/**
	 * The Propstack ID of the first object in the test data.
	 *
	 * @var string
	 */
	private static string $object_id = '42';

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// no import and no deletion are running.
		update_option( CFPROP_IMPORT_RUNNING, 0 );
		update_option( CFPROP_DELETE_RUNNING, 0 );

		// set language to "de" and use API v1.
		update_option( 'propstack_connector_languages', 'de' );
		update_option( 'propstack_connector_api_version', 'v1' );

		// make sure the objects are searched with the same language the import uses.
		add_filter( 'cfprop_current_language', array( $this, 'set_language_to_de' ) );

		// run the activation.
		\ConnectorForPropstack\Propstack\Propstack::get_instance()->activation();
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'cfprop_current_language', array( $this, 'set_language_to_de' ) );

		// remove the pseudo-key.
		update_option( 'propstack_connector_api_key', '' );

		// reset the global post.
		unset( $GLOBALS['post'] );
		wp_reset_postdata();

		// reset the inline styles.
		foreach ( array( 'cfprop-generated-styles', 'wp-block-library' ) as $handle ) {
			if ( isset( wp_styles()->registered[ $handle ] ) ) {
				unset( wp_styles()->registered[ $handle ]->extra['after'] );
			}
		}
		wp_dequeue_style( 'cfprop-generated-styles' );

		parent::tear_down();
	}

	/**
	 * Return "de" as current language, so the imported objects are found.
	 *
	 * @return string
	 */
	public function set_language_to_de(): string {
		return 'de';
	}

	/**
	 * Import the objects of the test data via the mocked API v1.
	 *
	 * @return ImmoObject The first imported object.
	 */
	private function import_objects(): ImmoObject {
		// set the pseudo-key.
		update_option( 'propstack_connector_api_key', self::$api_key );

		// run the import until it is completed.
		$runs = 0;
		do {
			$import_obj = new \ConnectorForPropstack\Propstack\Imports\v1\Objects();
			$import_obj->run();
			++$runs;

			// safeguard so a broken import cannot hang the test suite.
			$this->assertLessThan( 20, $runs, 'The import did not finish.' );
		} while ( $import_obj->has_load_more() );

		// get the object with the known ID.
		$immo_object = ImmoObjects::get_instance()->get_object_by_object_id( self::$object_id, 'de' );
		$this->assertInstanceOf( ImmoObject::class, $immo_object, 'The test object could not be imported.' );

		return $immo_object;
	}

	/**
	 * Set the given object as the object of the actual request.
	 *
	 * @param ImmoObject $immo_object The object.
	 *
	 * @return void
	 */
	private function set_request_object( ImmoObject $immo_object ): void {
		$GLOBALS['post'] = get_post( $immo_object->get_id() );
		setup_postdata( $GLOBALS['post'] );
	}

	/**
	 * Return the inline styles of the handles the plugin uses for custom widget styles.
	 *
	 * @return string
	 */
	private function get_inline_styles(): string {
		$css = '';
		foreach ( array( 'cfprop-generated-styles', 'wp-block-library' ) as $handle ) {
			$data = wp_styles()->get_data( $handle, 'after' );
			if ( is_array( $data ) ) {
				$css .= implode( '', $data );
			}
		}
		return $css;
	}

	/**
	 * Remove the running counter from the gallery HTML, so two outputs can be compared.
	 *
	 * @param string $html The gallery HTML.
	 *
	 * @return string
	 */
	private function remove_gallery_counter( string $html ): string {
		return (string) preg_replace( '/gallery-\d+/', 'gallery-X', $html );
	}

	/**
	 * Test that the archive shortcode without attributes returns a string.
	 *
	 * Hint: WordPress passes an empty string instead of an array for shortcodes without attributes.
	 *
	 * @return void
	 */
	public function test_archive_shortcode_without_attributes(): void {
		$this->assertIsString( do_shortcode( '[cfprop_widget_archive]' ) );
	}

	/**
	 * Test that the filter shortcode without attributes returns a string.
	 *
	 * @return void
	 */
	public function test_filter_shortcode_without_attributes(): void {
		$this->assertIsString( do_shortcode( '[cfprop_widget_filter]' ) );
	}

	/**
	 * Test that the gallery shortcode without attributes returns a string.
	 *
	 * @return void
	 */
	public function test_gallery_shortcode_without_attributes(): void {
		$this->assertIsString( do_shortcode( '[cfprop_widget_gallery]' ) );
	}

	/**
	 * Test that every registered shortcode of our widgets works without attributes.
	 *
	 * @return void
	 */
	public function test_all_shortcodes_without_attributes(): void {
		global $shortcode_tags;

		$widgets = WidgetsHandler::get_instance()->get_widgets_as_objects();
		$this->assertNotEmpty( $widgets );

		foreach ( $widgets as $widget ) {
			$tag = 'cfprop_' . $widget->get_name();

			// the shortcode must be registered.
			$this->assertArrayHasKey( $tag, $shortcode_tags, 'Shortcode ' . $tag . ' is not registered.' );

			// and must return a string without any error.
			$this->assertIsString( do_shortcode( '[' . $tag . ']' ), 'Shortcode ' . $tag . ' failed.' );
		}
	}

	/**
	 * Test that the field shortcode with an unknown object ID returns an empty string.
	 *
	 * @return void
	 */
	public function test_field_shortcode_with_unknown_object_id(): void {
		$this->assertSame( '', do_shortcode( '[cfprop_widget_field field_name="price" object_id="999999"]' ) );
	}

	/**
	 * Test that the filter shortcode accepts the list of filters as a string.
	 *
	 * @return void
	 */
	public function test_filter_shortcode_with_filters_as_string(): void {
		$this->assertIsString( do_shortcode( '[cfprop_widget_filter filters="cities"]' ) );
		$this->assertIsString( do_shortcode( '[cfprop_widget_filter filters="city, cities"]' ) );
	}

	/**
	 * Test that the filter shortcode shows the filter given by its name as a string.
	 *
	 * Hint: the filter is selected by its filter name, which is the name of the field ("city").
	 *
	 * @return void
	 */
	public function test_filter_shortcode_shows_selected_filter(): void {
		$this->import_objects();

		// clear the cache so the imported cities are used.
		\ConnectorForPropstack\Plugin\Cache::get_instance()->clear_cache();

		$output = do_shortcode( '[cfprop_widget_filter filters="city"]' );

		$this->assertStringContainsString( 'Musterhausen', $output );
		$this->assertStringContainsString( '<form', $output );

		// clean up the cache.
		\ConnectorForPropstack\Plugin\Cache::get_instance()->clear_cache();
	}

	/**
	 * Test that a template attribute with a path traversal does not include any other file.
	 *
	 * @return void
	 */
	public function test_archive_shortcode_with_path_traversal_in_template(): void {
		$default = do_shortcode( '[cfprop_widget_archive]' );

		// try it via both attributes, which could be used to select a template.
		$template         = do_shortcode( '[cfprop_widget_archive template="../../../../wp-config"]' );
		$listing_template = do_shortcode( '[cfprop_widget_archive listing_template="../../../../wp-config"]' );

		$this->assertSame( $default, $template );
		$this->assertSame( $default, $listing_template );
		$this->assertStringNotContainsString( 'DB_NAME', $listing_template );
	}

	/**
	 * Test that the archive with a path traversal in the listing template uses the default listing.
	 *
	 * @return void
	 */
	public function test_archive_shortcode_with_path_traversal_and_objects(): void {
		$this->import_objects();

		$default = do_shortcode( '[cfprop_widget_archive]' );
		$output  = do_shortcode( '[cfprop_widget_archive listing_template="../../../../wp-config"]' );

		$this->assertNotEmpty( $default );
		$this->assertSame( $default, $output );
	}

	/**
	 * Test that a visible field is shown for an existing object.
	 *
	 * @return void
	 */
	public function test_field_shortcode_with_visible_field(): void {
		$this->import_objects();

		$output = do_shortcode( '[cfprop_widget_field field_name="price" object_id="' . self::$object_id . '"]' );

		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'cfprop-field-price', $output );
		$this->assertStringContainsString( '180.000', $output );
	}

	/**
	 * Test that the raw API response is never shown via the field shortcode.
	 *
	 * @return void
	 */
	public function test_field_shortcode_hides_api_response(): void {
		$this->import_objects();

		$this->assertSame( '', do_shortcode( '[cfprop_widget_field field_name="api_response" object_id="' . self::$object_id . '"]' ) );
	}

	/**
	 * Test that hidden broker fields are not shown via the broker field shortcode.
	 *
	 * @return void
	 */
	public function test_broker_field_shortcode_hides_hidden_fields(): void {
		$this->set_request_object( $this->import_objects() );

		// a visible field is shown.
		$this->assertStringContainsString( 'Mustermann', do_shortcode( '[cfprop_widget_broker_field field_name="name"]' ) );

		// the hidden fields are not shown.
		$this->assertSame( '', do_shortcode( '[cfprop_widget_broker_field field_name="cell"]' ) );
		$this->assertSame( '', do_shortcode( '[cfprop_widget_broker_field field_name="email"]' ) );
	}

	/**
	 * Test that custom styles are not accepted via shortcode attributes.
	 *
	 * @return void
	 */
	public function test_shortcode_does_not_add_styles(): void {
		$this->import_objects();

		$output  = do_shortcode( '[cfprop_widget_archive styles="body{display:none}"]' );
		$output .= do_shortcode( '[cfprop_widget_field field_name="price" object_id="' . self::$object_id . '" styles="body{display:none}"]' );

		$this->assertStringNotContainsString( 'display:none', $output );
		$this->assertStringNotContainsString( 'display:none', $this->get_inline_styles() );
	}

	/**
	 * Test that the check for inline styles works, when the styles are given internally (e.g., via blocks).
	 *
	 * @return void
	 */
	public function test_internal_styles_are_added(): void {
		\ConnectorForPropstack\Plugin\Templates::get_instance()->add_styles( array( 'styles' => '.cfprop-test{color:red}' ) );

		$this->assertStringContainsString( '.cfprop-test{color:red}', $this->get_inline_styles() );
	}

	/**
	 * Test that an invalid size attribute of the gallery falls back to the default size.
	 *
	 * @return void
	 */
	public function test_gallery_shortcode_with_invalid_size(): void {
		$immo_object = $this->import_objects();

		// attach an image to the object.
		self::factory()->attachment->create_object(
			'cfprop-test-image.jpg',
			$immo_object->get_id(),
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		// use this object for the request.
		$this->set_request_object( $immo_object );

		// get the default output (without the counter WordPress uses for the gallery ID).
		$default = $this->remove_gallery_counter( do_shortcode( '[cfprop_widget_gallery]' ) );
		$this->assertStringContainsString( 'cfprop-gallery', $default );

		// get the output with an invalid size.
		$output = $this->remove_gallery_counter( do_shortcode( '[cfprop_widget_gallery size=\'x" ids="1\']' ) );

		$this->assertSame( $default, $output );
		$this->assertStringContainsString( 'gallery-size-thumbnail', $output );
		$this->assertStringNotContainsString( 'xids1', $output );
		$this->assertStringNotContainsString( 'ids="1"', $output );
	}
}

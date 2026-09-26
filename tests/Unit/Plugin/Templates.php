<?php
/**
 * File for tests against \ConnectorForPropstack\Plugin\Templates.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Plugin;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against \ConnectorForPropstack\Plugin\Templates.
 */
class Templates extends ConnectorForPropstackTestCase {
	/**
	 * Return the path to the empty fallback template.
	 *
	 * @return string
	 */
	private function get_empty_template(): string {
		return plugin_dir_path( CFPROP_PLUGIN ) . 'templates/parts/empty.php';
	}

	/**
	 * Return a list of template names with a path traversal or an absolute path.
	 *
	 * @return array<string,array<int,string>>
	 */
	public function provide_invalid_template_names(): array {
		return array(
			'parent directory'     => array( '../../../../wp-config.php' ),
			'parent in the middle' => array( 'parts/../../connector-for-propstack.php' ),
			'absolute path'        => array( '/etc/passwd' ),
			'null byte'            => array( "parts/archive.php\0.txt" ),
			'empty name'           => array( '' ),
		);
	}

	/**
	 * Test that invalid template names resolve to the empty template.
	 *
	 * @dataProvider provide_invalid_template_names
	 *
	 * @param string $template The template name.
	 *
	 * @return void
	 */
	public function test_get_template_with_invalid_name( string $template ): void {
		$this->assertSame( $this->get_empty_template(), \ConnectorForPropstack\Plugin\Templates::get_instance()->get_template( $template ) );
	}

	/**
	 * Test that invalid template names are reported as not existing.
	 *
	 * @dataProvider provide_invalid_template_names
	 *
	 * @param string $template The template name.
	 *
	 * @return void
	 */
	public function test_has_template_with_invalid_name( string $template ): void {
		$this->assertFalse( \ConnectorForPropstack\Plugin\Templates::get_instance()->has_template( $template ) );
	}

	/**
	 * Test that the private validation of template names detects traversals.
	 *
	 * @return void
	 */
	public function test_is_valid_template_name(): void {
		$method = new \ReflectionMethod( \ConnectorForPropstack\Plugin\Templates::class, 'is_valid_template_name' );
		$method->setAccessible( true );
		$templates_obj = \ConnectorForPropstack\Plugin\Templates::get_instance();

		$this->assertTrue( $method->invoke( $templates_obj, 'parts/archive.php' ) );
		$this->assertFalse( $method->invoke( $templates_obj, '../parts/archive.php' ) );
		$this->assertFalse( $method->invoke( $templates_obj, '/parts/archive.php' ) );
		$this->assertFalse( $method->invoke( $templates_obj, "parts/archive.php\0" ) );
	}

	/**
	 * Test that template names starting with "./" resolve to the empty template.
	 *
	 * Hint: is_valid_template_name() relies on validate_file(), which only rejects "../" but
	 * not "./". So "./parts/archive.php" is accepted as a valid name.
	 *
	 * @return void
	 */
	public function test_get_template_with_current_directory_name(): void {
		$templates_obj = \ConnectorForPropstack\Plugin\Templates::get_instance();

		$path         = $templates_obj->get_template( './parts/archive.php' );
		$has_template = $templates_obj->has_template( './parts/archive.php' );


		$this->assertSame( $this->get_empty_template(), $path );
		$this->assertFalse( $has_template );
	}

	/**
	 * Test that a valid template name resolves to the real template.
	 *
	 * @return void
	 */
	public function test_get_template_with_valid_name(): void {
		$path = \ConnectorForPropstack\Plugin\Templates::get_instance()->get_template( 'parts/archive.php' );

		$this->assertSame( plugin_dir_path( CFPROP_PLUGIN ) . 'templates/parts/archive.php', $path );
		$this->assertFileExists( $path );
	}

	/**
	 * Test that an existing template is detected and a missing one is not.
	 *
	 * @return void
	 */
	public function test_has_template_with_valid_name(): void {
		$this->assertTrue( \ConnectorForPropstack\Plugin\Templates::get_instance()->has_template( 'parts/archive.php' ) );
		$this->assertFalse( \ConnectorForPropstack\Plugin\Templates::get_instance()->has_template( 'parts/does-not-exist.php' ) );
	}

	/**
	 * Test that the empty template produces no output.
	 *
	 * @return void
	 */
	public function test_empty_template_has_no_output(): void {
		ob_start();
		include \ConnectorForPropstack\Plugin\Templates::get_instance()->get_template( '../../../../wp-config.php' );
		$this->assertSame( '', ob_get_clean() );
	}

	/**
	 * Test that the archive template uses only known listing templates.
	 *
	 * @return void
	 */
	public function test_archive_template_with_unknown_listing_template(): void {
		// prepare the attributes like the archive widget, but with an empty query.
		$attributes = array(
			'listing_template' => '../../../../wp-config',
			'templates'        => array( 'title' ),
			'query'            => new \WP_Query( array( 'post__in' => array( 0 ) ) ),
			'pagination'       => '',
		);

		ob_start();
		include \ConnectorForPropstack\Plugin\Templates::get_instance()->get_template( 'parts/archive.php' );
		$output = ob_get_clean();

		// the output must be the hint for an empty list, nothing else.
		$this->assertStringNotContainsString( 'DB_NAME', $output );
		$this->assertStringContainsString( '<article', $output );
	}

	/**
	 * Test that add_styles() strips HTML tags from the given CSS.
	 *
	 * @return void
	 */
	public function test_add_styles_strips_tags(): void {
		\ConnectorForPropstack\Plugin\Templates::get_instance()->add_styles( array( 'styles' => '.cfprop-a{color:red}</style><script>alert(1)</script>' ) );

		$css = implode( '', (array) wp_styles()->get_data( 'cfprop-generated-styles', 'after' ) );

		$this->assertStringContainsString( '.cfprop-a{color:red}', $css );
		$this->assertStringNotContainsString( '</style>', $css );
		$this->assertStringNotContainsString( '<script>', $css );

		// clean up.
		unset( wp_styles()->registered['cfprop-generated-styles']->extra['after'] );
		wp_dequeue_style( 'cfprop-generated-styles' );
	}

	/**
	 * Test that add_styles() does nothing without styles.
	 *
	 * @return void
	 */
	public function test_add_styles_without_styles(): void {
		\ConnectorForPropstack\Plugin\Templates::get_instance()->add_styles( array() );

		$this->assertFalse( wp_style_is( 'cfprop-generated-styles', 'enqueued' ) );
	}
}

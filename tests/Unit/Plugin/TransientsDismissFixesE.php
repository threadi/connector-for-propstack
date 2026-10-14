<?php
/**
 * File for tests against the AJAX dismiss handler of the bundled easy-transients-for-wordpress library.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Plugin;

use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

/**
 * Object for tests against the AJAX dismiss handler of the transients.
 */
class TransientsDismissFixesE extends \WP_Ajax_UnitTestCase {
	/**
	 * The AJAX action to test.
	 *
	 * @var string
	 */
	private string $action = 'efw_dismiss_admin_notice';

	/**
	 * Prepare the test environment for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// make sure the AJAX handler is registered.
		if ( ! has_action( 'wp_ajax_' . $this->action ) ) {
			add_action( 'wp_ajax_' . $this->action, array( Transients::get_instance(), 'dismiss_transient_via_ajax' ) );
		}
	}

	/**
	 * Return the number of dismiss-markers in the options table.
	 *
	 * @return int
	 */
	private function get_dismiss_marker_count(): int {
		global $wpdb;
		return absint( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->options . ' WHERE option_name LIKE %s', '%' . $wpdb->esc_like( '-dismissed-' ) . '%' ) ) );
	}

	/**
	 * Run the AJAX action and return the decoded response.
	 *
	 * @return array<string,mixed>
	 */
	private function run_dismiss_request(): array {
		$_POST['nonce']              = wp_create_nonce( 'etfw-dismiss-nonce' );
		$_POST['option_name']        = 'cfprop_test_transient';
		$_POST['dismissible_length'] = 'forever';

		try {
			$this->_handleAjax( $this->action );
		} catch ( WPAjaxDieContinueException $e ) {
			// expected as wp_send_json_*() ends the request.
			unset( $e );
		} catch ( WPAjaxDieStopException $e ) {
			// also an end of the request.
			unset( $e );
		}

		$response = json_decode( $this->_last_response, true );
		return is_array( $response ) ? $response : array();
	}

	/**
	 * Test if a user without the capability cannot dismiss transients and no option is created.
	 *
	 * @return void
	 */
	public function test_dismiss_is_rejected_for_subscriber(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$count_before = $this->get_dismiss_marker_count();

		$response = $this->run_dismiss_request();

		$this->assertArrayHasKey( 'success', $response );
		$this->assertFalse( $response['success'] );
		$this->assertSame( $count_before, $this->get_dismiss_marker_count() );
	}

	/**
	 * Test if a request for an unknown transient does not create any dismiss-marker.
	 *
	 * @return void
	 */
	public function test_dismiss_of_unknown_transient_creates_no_option(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$count_before = $this->get_dismiss_marker_count();

		$response = $this->run_dismiss_request();

		$this->assertArrayHasKey( 'success', $response );
		$this->assertFalse( $response['success'] );
		$this->assertSame( $count_before, $this->get_dismiss_marker_count() );
	}

	/**
	 * Test if the script with the dismiss nonce is not enqueued for users without the capability.
	 *
	 * @return void
	 */
	public function test_scripts_are_not_enqueued_for_subscriber(): void {
		// skip this test if the bundled library does not check the capability before enqueuing its script (fixed upstream after 1.4.9).
		$method = new \ReflectionMethod( Transients::class, 'add_scripts' );
		$source = implode( '', array_slice( (array) file( (string) $method->getFileName() ), $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1 ) );
		if ( ! str_contains( $source, 'current_user_can' ) ) {
			$this->markTestSkipped( 'The bundled easy-transients-for-wordpress does not check the capability before enqueuing its script yet.' );
		}

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		wp_dequeue_script( 'easy-transients-for-wordpress' );

		Transients::get_instance()->add_scripts();

		$this->assertFalse( wp_script_is( 'easy-transients-for-wordpress', 'enqueued' ) );
	}
}

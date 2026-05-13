<?php
/**
 * File to some test scenarios for one topic.
 *
 * @package propstack-connector
 */

namespace ConnectorForPropstack\Tests\Unit\Scenarios;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against \ConnectorForPropstack\Propstack\FieldCategories.
 */
class Uninstall extends ConnectorForPropstackTestCase {

	/**
	 * Run uninstallation and test the database.
	 *
	 * @return void
	 */
	public function test_uninstall(): void {
		// get the list of settings.
		$settings = array();
		foreach( \ConnectorForPropstack\Plugin\Settings::get_instance()->get_settings_obj()->get_settings() as $setting ) {
			$settings[ $setting->get_name() ] = $setting->get_value();
		}

		// set a transient.
		$transient = \ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients::get_instance()->add();
		$transient->set_name( 'my_test' );
		$transient->save();
		$this->assertIsObject( \ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients::get_instance()->get_transient_by_name( 'my_test' ) );

		// run the uninstallation.
		\ConnectorForPropstack\Plugin\Uninstaller::get_instance()->run();

		// test if the transient has been deleted.
		$test_transient = \ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients::get_instance()->get_transient_by_name( 'my_test' );
		$this->assertFalse( $test_transient->is_set() );

		// test if the settings have been deleted.
		foreach( $settings as $name => $value ) {
			$value = get_option( $name );
			$this->assertIsBool( $value );
			$this->assertFalse( $value );
		}
	}
}

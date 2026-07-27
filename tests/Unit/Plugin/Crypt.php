<?php
/**
 * File for tests against \ConnectorForPropstack\Plugin\Crypt.
 */

namespace ConnectorForPropstack\Tests\Unit\Plugin;

use ConnectorForPropstack\Tests\ConnectorForPropstackTestCase;

/**
 * Object for tests against \ConnectorForPropstack\Plugin\Crypt.
 */
class Crypt extends ConnectorForPropstackTestCase {
	/**
	 * Test the encryption.
	 *
	 * @return void
	 */
	public function test_encrypt(): void {
		// the test text.
		$text = 'Hallo World';

		// test it.
		$encrypted = \ConnectorForPropstack\Plugin\Crypt::get_instance()->encrypt( $text );
		$this->assertIsString( $encrypted );
		$this->assertNotEquals( $text, $encrypted );
	}

	/**
	 * Test the encryption and decryption.
	 *
	 * @return void
	 */
	public function test_encrypt_and_decrypt(): void {
		// the test text.
		$text = 'Hallo World';

		// test it to encrypt.
		$encrypted = \ConnectorForPropstack\Plugin\Crypt::get_instance()->encrypt( $text );
		$this->assertIsString( $encrypted );
		$this->assertNotEquals( $text, $encrypted );

		// test it to decrypt.
		$decrypted = \ConnectorForPropstack\Plugin\Crypt::get_instance()->decrypt( $encrypted );
		$this->assertIsString( $decrypted );
		$this->assertEquals( $text, $decrypted );
	}
}

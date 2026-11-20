<?php
/**
 * File for the tests of the complete import flow with the live Propstack API v1.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Tests\Live;

use ConnectorForPropstack\Tests\LiveImportFlowTestCase;

/**
 * Object for the tests of the complete import flow with the live Propstack API v1.
 */
class ImportFlowV1 extends LiveImportFlowTestCase {
	/**
	 * The API version to test.
	 *
	 * @var string
	 */
	protected static string $api_version = 'v1';
}

<?php
/**
 * Example configuration for the tests against the live Propstack API ("composer test-live").
 *
 * Copy this file to tests/live-config.php and enter your values. tests/live-config.php is ignored
 * by git, so your API key does not end up in the repository. Each value could also be set via an
 * environment variable, which wins over this file (see tests/LiveTestCase.php).
 *
 * The live tests only read from Propstack. Everything is imported into the database of the
 * WordPress tests, which is reset by the test framework.
 *
 * Details: doc/live-tests.md
 *
 * @package connector-for-propstack
 */

return array(
	// The API key of the Propstack account to test with (CFPROP_LIVE_API_KEY).
	// It needs the permissions for the API v1 and/or v2, depending on "api_versions".
	'api_key'      => '',

	// The API versions to test, comma-separated (CFPROP_LIVE_API_VERSIONS).
	'api_versions' => 'v1,v2',

	// The language of the import (CFPROP_LIVE_LANGUAGE).
	'language'     => 'de',

	// Optional: the Propstack-ID of an object, which must be delivered and imported (CFPROP_LIVE_OBJECT_ID).
	// It is also used for the test of the image import. Without it, the object with the fewest images is used.
	'object_id'    => '',

	// Optional: import only the first X objects to save time, 0 for all (CFPROP_LIVE_MAX_OBJECTS).
	// All pages are requested anyway, the configured object_id is always imported in addition.
	'max_objects'  => 0,
);

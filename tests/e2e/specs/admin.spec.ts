/**
 * Tests for the backend of the plugin: settings, object list, manual import and log.
 */
import { test, expect } from '@playwright/test';
import { expectNoPhpErrors, openPage, settingsUrl } from './helpers';

test.describe( 'Settings', () => {
	test( 'all tabs of the settings load without errors', async ( { page } ) => {
		await openPage( page, settingsUrl() );

		// collect the tabs of the settings page.
		const tabs = await page.locator( 'a.nav-tab[href*="tab=propstack_connector_"]' ).evaluateAll( ( links ) =>
			[ ...new Set( links.map( ( link ) => new URL( ( link as HTMLAnchorElement ).href ).searchParams.get( 'tab' ) ) ) ]
		);
		expect( tabs.length ).toBeGreaterThan( 5 );

		for ( const tab of tabs ) {
			await openPage( page, settingsUrl( tab ?? '' ) );
		}
	} );

	test( 'the API key is never shown in the settings', async ( { page } ) => {
		await openPage( page, settingsUrl( 'propstack_connector_basic' ) );

		const field = page.locator( 'input#propstack_connector_api_key' );
		await expect( field ).toHaveAttribute( 'type', 'password' );
		await expect( field ).toHaveValue( '' );
		expect( await page.content() ).not.toContain( 'test_api_key' );
	} );

	test( 'the field table shows examples from the imported objects', async ( { page } ) => {
		await openPage( page, settingsUrl( 'propstack_connector_fields' ) );

		// the city of the test objects is used as example.
		await expect( page.locator( 'table' ).getByText( 'Musterhausen' ).first() ).toBeVisible();
	} );

	test( 'the log shows the import of the test data', async ( { page } ) => {
		await openPage( page, settingsUrl( 'propstack_connector_logs' ) );

		await expect( page.locator( 'table.wp-list-table' ) ).toBeVisible();
		await expect( page.locator( 'table.wp-list-table tbody tr' ).first() ).toBeVisible();

		// the encrypted API key could be read during the import.
		await expect( page.locator( 'table.wp-list-table' ) ).not.toContainText( 'Error in crypt support' );
	} );
} );

test.describe( 'Objects', () => {
	test( 'the imported objects are listed in the backend', async ( { page } ) => {
		await openPage( page, '/wp-admin/edit.php?post_type=cfprop_object' );

		await expect( page.locator( '#the-list tr.type-cfprop_object' ) ).toHaveCount( 2 );
		await expect( page.locator( '#the-list' ) ).toContainText( 'Musterhaus' );
	} );

	test( 'the import can be started manually again', async ( { page } ) => {
		await openPage( page, '/wp-admin/edit.php?post_type=cfprop_object' );

		// the button is added by JavaScript next to the headline.
		const button = page.locator( 'a.cfprop-import-hint' );
		await expect( button ).toBeVisible();
		await button.click();
		await page.waitForLoadState( 'load' );
		await expectNoPhpErrors( page );

		// the import must not duplicate the objects.
		await openPage( page, '/wp-admin/edit.php?post_type=cfprop_object' );
		await expect( page.locator( '#the-list tr.type-cfprop_object' ) ).toHaveCount( 2 );
	} );
} );

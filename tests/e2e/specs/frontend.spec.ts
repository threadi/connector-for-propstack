/**
 * Tests for the output of the objects in the frontend for visitors.
 */
import { test, expect } from '@playwright/test';
import { openPage } from './helpers';

// visitors are not logged in.
test.use( { storageState: { cookies: [], origins: [] } } );

test.describe( 'Archive', () => {
	test( 'the archive lists the imported objects with their key data', async ( { page } ) => {
		await openPage( page, '/objects/' );

		await expect( page.locator( '.type-cfprop_object' ) ).toHaveCount( 2 );
		await expect( page.locator( 'body' ) ).toContainText( 'Musterhausen' );
		await expect( page.locator( 'body' ) ).toContainText( '180.000 €' );
	} );

	test( 'the filter reduces the list to the chosen object', async ( { page } ) => {
		await openPage( page, '/objects/' );

		const form = page.locator( 'form.cfprop-filter' ).first();
		await expect( form.locator( 'select[name="filter[city]"]' ) ).toBeVisible();
		await form.locator( 'input[name="filter[object_id]"]' ).fill( '42' );
		await form.locator( '[type="submit"]' ).click();
		await page.waitForURL( /filter/ );

		await expect( page.locator( '.type-cfprop_object' ) ).toHaveCount( 1 );
	} );

	test( 'the filter keeps the entered value and escapes it', async ( { page } ) => {
		await openPage( page, '/objects/?filter[object_id]=%22%3E%3Cscript%3Ewindow.cfpropXss%3D1%3C%2Fscript%3E' );

		// the script must not run and must not be part of the field value (the value is sanitized).
		expect( await page.evaluate( () => ( window as unknown as { cfpropXss?: number } ).cfpropXss ) ).toBeUndefined();
		const value = await page.locator( 'input[name="filter[object_id]"]' ).first().inputValue();
		expect( value ).not.toContain( '<script' );
	} );
} );

test.describe( 'Single object', () => {
	test( 'the detail page shows the data of the object', async ( { page } ) => {
		await openPage( page, '/object/musterhaus/' );

		const main = page.locator( 'body' );
		await expect( main ).toContainText( 'Musterhaus' );
		await expect( main ).toContainText( 'Lorem ipsum description note.' );
		await expect( main ).toContainText( '62m²' );
	} );

	test( 'the detail page shows no internal data and respects "hide address"', async ( { page } ) => {
		await openPage( page, '/object/musterhaus/' );
		const html = await page.content();

		// the internal note of the object in Propstack.
		expect( html ).not.toContain( 'MUSTER-0001' );
		// the exact address (the test object has "hide_address" set in Propstack).
		expect( html ).not.toContain( 'Musterstraße 1' );
		// the raw API response.
		expect( html ).not.toContain( 'api_response' );
	} );
} );

test.describe( 'Shortcodes', () => {
	test( 'the shortcodes render objects and ignore unknown template names', async ( { page } ) => {
		// the page is created by tests/e2e/blueprint.json, one shortcode uses an internal hook name as template.
		await openPage( page, '/e2e-shortcodes/' );

		await expect( page.locator( '.type-cfprop_object' ).first() ).toBeVisible();
		await expect( page.locator( 'form.cfprop-filter' ) ).toBeVisible();
	} );
} );

/**
 * Helpers for the E2E tests.
 */
import { expect, type Page, type Response } from '@playwright/test';

/**
 * The URL of the settings page of the plugin.
 *
 * @param tab Optional the tab to open.
 */
export function settingsUrl( tab = '' ): string {
	return '/wp-admin/options-general.php?page=connector-for-propstack' + ( tab ? `&tab=${ tab }` : '' );
}

/**
 * Check that the given page does not show any PHP error.
 *
 * @param page The page.
 */
export async function expectNoPhpErrors( page: Page ): Promise< void > {
	const html = await page.content();
	for ( const marker of [ 'Fatal error', 'Parse error', 'Uncaught', '<b>Warning</b>:', '<b>Notice</b>:', '<b>Deprecated</b>:', 'There has been a critical error' ] ) {
		expect( html, `page contains "${ marker }"` ).not.toContain( marker );
	}
}

/**
 * Open a URL, check the status code and that no PHP error is shown.
 *
 * @param page The page.
 * @param url  The URL.
 */
export async function openPage( page: Page, url: string ): Promise< Response > {
	const response = await page.goto( url );
	expect( response, `no response for ${ url }` ).not.toBeNull();
	expect( response!.status(), `status of ${ url }` ).toBeLessThan( 400 );
	await expectNoPhpErrors( page );
	return response!;
}

/**
 * Return a nonce for the REST API of the logged-in user of the page.
 *
 * @param page The page with a logged-in user.
 */
export async function getRestNonce( page: Page ): Promise< string > {
	const response = await page.request.get( '/wp-admin/admin-ajax.php?action=rest-nonce' );
	expect( response.ok() ).toBeTruthy();
	return ( await response.text() ).trim();
}

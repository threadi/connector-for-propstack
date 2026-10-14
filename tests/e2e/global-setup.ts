/**
 * Log in once as administrator and as subscriber and save the sessions for the tests.
 */
import { chromium, type FullConfig } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

/**
 * The users which are used by the tests (the subscriber is created by tests/e2e/blueprint.json).
 */
export const users = {
	admin: { login: 'admin', password: 'password' },
	subscriber: { login: 'subscriber', password: 'subscriber' },
};

/**
 * Return the path of the saved session of a user.
 *
 * @param user The user.
 */
export function authFile( user: keyof typeof users ): string {
	return path.join( __dirname, '.auth', `${ user }.json` );
}

export default async function globalSetup( config: FullConfig ): Promise< void > {
	const { baseURL, launchOptions } = config.projects[ 0 ].use;
	fs.mkdirSync( path.join( __dirname, '.auth' ), { recursive: true } );

	const browser = await chromium.launch( launchOptions );
	for ( const user of Object.keys( users ) as Array< keyof typeof users > ) {
		const page = await browser.newPage( { baseURL } );
		await page.goto( '/wp-login.php' );
		await page.locator( '#user_login' ).fill( users[ user ].login );
		await page.locator( '#user_pass' ).fill( users[ user ].password );
		await page.locator( '#wp-submit' ).click();
		await page.waitForURL( /wp-admin/ );
		await page.context().storageState( { path: authFile( user ) } );
		await page.close();
	}
	await browser.close();
}

/**
 * Tests that no internal data of the objects is available via the REST API.
 */
import { test, expect } from '@playwright/test';
import { authFile } from '../global-setup';
import { getRestNonce } from './helpers';

// fields which must never be visible for visitors or subscribers.
const internalFields = [ 'api_response', 'note', 'renter', 'total_commission', 'valuation_price', '' ];

// fields with the exact address, which must be hidden as the test objects have "hide address" set in Propstack.
const addressFields = [ 'street', 'house_number', 'address', 'latitude', 'longitude' ];

test.describe( 'REST API', () => {
	test.use( { storageState: authFile( 'subscriber' ) } );

	test( 'a subscriber gets no internal fields of objects', async ( { page } ) => {
		await page.goto( '/wp-admin/profile.php' );
		const nonce = await getRestNonce( page );

		const response = await page.request.get( '/wp-json/wp/v2/cfprop_object', { headers: { 'X-WP-Nonce': nonce } } );
		expect( response.status() ).toBe( 200 );

		const objects = ( await response.json() ) as Array< { meta?: Record< string, unknown > } >;
		expect( objects.length ).toBe( 2 );

		for ( const object of objects ) {
			const meta = object.meta ?? {};
			for ( const field of [ ...internalFields, ...addressFields ] ) {
				expect( Object.keys( meta ), `field "${ field }" is exposed` ).not.toContain( field );
			}
			// public data is still available.
			expect( Object.keys( meta ) ).toContain( 'city' );
		}
	} );

	test( 'a subscriber cannot delete objects', async ( { page } ) => {
		await page.goto( '/wp-admin/profile.php' );
		const nonce = await getRestNonce( page );

		const list = await page.request.get( '/wp-json/wp/v2/cfprop_object', { headers: { 'X-WP-Nonce': nonce } } );
		const [ object ] = ( await list.json() ) as Array< { id: number } >;

		const response = await page.request.delete( `/wp-json/wp/v2/cfprop_object/${ object.id }?force=true`, { headers: { 'X-WP-Nonce': nonce } } );
		expect( response.status() ).toBeGreaterThanOrEqual( 400 );
	} );
} );

test.describe( 'REST API for visitors', () => {
	test.use( { storageState: { cookies: [], origins: [] } } );

	test( 'visitors get no fields of objects', async ( { request } ) => {
		const response = await request.get( '/wp-json/wp/v2/cfprop_object' );

		// the route is only available for logged-in users.
		if ( 200 === response.status() ) {
			const objects = ( await response.json() ) as Array< { meta?: Record< string, unknown > } >;
			for ( const object of objects ) {
				for ( const field of internalFields ) {
					expect( Object.keys( object.meta ?? {} ) ).not.toContain( field );
				}
			}
		} else {
			expect( response.status() ).toBe( 404 );
		}
	} );
} );

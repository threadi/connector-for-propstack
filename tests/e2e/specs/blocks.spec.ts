/**
 * Tests for the blocks of the plugin in the block editor.
 */
import { test, expect, type Page } from '@playwright/test';

/**
 * Open the block editor for a new page and wait until it is ready.
 *
 * @param page The page.
 */
async function openEditor( page: Page ): Promise< void > {
	await page.goto( '/wp-admin/post-new.php?post_type=page' );
	await page.waitForFunction( () => ( window as any ).wp?.blocks?.getBlockTypes?.().length > 0 );

	// close the welcome guide, if it is shown.
	await page.evaluate( () => {
		const wp = ( window as any ).wp;
		wp.data.dispatch( 'core/preferences' )?.set( 'core/edit-post', 'welcomeGuide', false );
	} );
}

/**
 * Insert a block into the editor.
 *
 * @param page       The page.
 * @param name       The name of the block.
 * @param attributes The attributes of the block.
 */
async function insertBlock( page: Page, name: string, attributes: Record< string, unknown > = {} ): Promise< void > {
	await page.evaluate(
		( { name: blockName, attributes: blockAttributes } ) => {
			const wp = ( window as any ).wp;
			wp.data.dispatch( 'core/block-editor' ).insertBlocks( wp.blocks.createBlock( blockName, blockAttributes ) );
		},
		{ name, attributes }
	);
}

/**
 * Insert a block and wait until its preview has been rendered by the server.
 *
 * The block editor loads many things via the REST API at the same time. As Playground answers the requests
 * one after another, the preview of the block could take a while on slower systems.
 *
 * @param page       The page.
 * @param name       The name of the block.
 * @param attributes The attributes of the block.
 */
async function insertBlockAndWaitForPreview( page: Page, name: string, attributes: Record< string, unknown > = {} ): Promise< void > {
	const [ blockNamespace, blockName ] = name.split( '/' );
	const rendered = page.waitForResponse(
		( response ) => new RegExp( `block-renderer(/|%2F)${ blockNamespace }(/|%2F)${ blockName }` ).test( response.url() ),
		{ timeout: 120_000 }
	);
	await insertBlock( page, name, attributes );
	const response = await rendered;
	expect( response.status(), `preview request of ${ name } failed: ${ await response.text() }` ).toBe( 200 );
}

/**
 * Return the editor canvas (the editor uses an iframe since WordPress 6.3).
 *
 * @param page The page.
 */
function canvas( page: Page ) {
	return page.frameLocator( 'iframe[name="editor-canvas"]' );
}

test.describe( 'Blocks', () => {
	test( 'all blocks of the plugin are registered', async ( { page } ) => {
		await openEditor( page );

		const names = await page.evaluate( () =>
			( window as any ).wp.blocks.getBlockTypes().map( ( type: { name: string } ) => type.name ).filter( ( name: string ) => name.startsWith( 'connector-for-propstack/' ) )
		);

		for ( const block of [ 'archive', 'single', 'field', 'broker-field', 'description', 'energy-scale', 'filter', 'gallery' ] ) {
			expect( names ).toContain( `connector-for-propstack/${ block }` );
		}
	} );

	test( 'the field block shows the value of the newest object', async ( { page } ) => {
		test.setTimeout( 240_000 );
		await openEditor( page );
		await insertBlockAndWaitForPreview( page, 'connector-for-propstack/field', { field_name: 'city' } );

		await expect( canvas( page ).locator( '.wp-block-connector-for-propstack-field' ).first() ).toContainText( 'Musterhausen', { timeout: 60_000 } );
	} );

	test( 'the archive block lists the objects', async ( { page } ) => {
		test.setTimeout( 240_000 );
		await openEditor( page );
		await insertBlockAndWaitForPreview( page, 'connector-for-propstack/archive' );

		await expect( canvas( page ).getByText( '180.000 €' ).first() ).toBeVisible( { timeout: 60_000 } );
	} );

	test( 'the previews in the block inserter are not empty', async ( { page } ) => {
		await openEditor( page );

		// render each block like the preview in the inserter does, with the example attributes from block.json.
		const results = await page.evaluate( async () => {
			const wp = ( window as any ).wp;
			const output: Record< string, string > = {};
			for ( const block of [ 'archive', 'single', 'field', 'broker-field', 'description', 'energy-scale', 'filter' ] ) {
				const name = `connector-for-propstack/${ block }`;
				const example = wp.blocks.getBlockType( name )?.example?.attributes ?? {};
				const response = await wp.apiFetch( {
					path: `/wp/v2/block-renderer/${ name }?context=edit`,
					method: 'POST',
					data: { attributes: example },
				} );
				output[ block ] = String( response.rendered ?? '' );
			}
			return output;
		} );

		for ( const [ block, html ] of Object.entries( results ) ) {
			expect( html.trim(), `preview of "${ block }" is empty` ).not.toBe( '' );
		}
	} );
} );

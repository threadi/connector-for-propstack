/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * Add individual dependencies.
 */
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
const { useEffect } = wp.element;
import { onChange } from '../../components';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * The settings are the same as for this widget in any other page builder.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @param  object
 * @return {WPElement} Element to render.
 */
export default function Edit( object ) {
	// secure ID of this block, but only if it is not set yet.
	useEffect( () => {
		if ( ! object.attributes.blockId ) {
			object.setAttributes( { blockId: object.clientId } );
		}
	}, [ object.attributes.blockId, object.clientId ] );

	// get the sources of the locations (Connector for Propstack Pro adds the list of objects).
	const sources = window.cfprop_map_block?.sources ?? [
		{
			label: __( 'Actual object', 'connector-for-propstack' ),
			value: 'object',
		},
	];
	const source = object.attributes.source
		? object.attributes.source
		: 'object';

	/**
	 * Collect return for the edit-function
	 * @param value
	 */
	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'connector-for-propstack' ) }
				>
					{ sources.length > 1 && (
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Show', 'connector-for-propstack' ) }
							help={ __(
								'Which objects are shown on the map.',
								'connector-for-propstack'
							) }
							value={ source }
							options={ sources }
							onChange={ ( value ) =>
								onChange( 'source', value, object )
							}
						/>
					) }
					{ 'object' === source && (
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __(
								'Object ID',
								'connector-for-propstack'
							) }
							help={ __(
								'Optional: the Propstack ID of the object to show. Only needed outside of the template for the detail view of objects.',
								'connector-for-propstack'
							) }
							value={ object.attributes.object_id }
							onChange={ ( value ) =>
								onChange( 'object_id', value, object )
							}
						/>
					) }
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						type="number"
						min={ 1 }
						max={ 19 }
						label={ __( 'Zoom level', 'connector-for-propstack' ) }
						help={ __(
							'From 1 (world) to 19 (street). Leave empty to use the zoom level from the settings. Maps with several objects show all of them.',
							'connector-for-propstack'
						) }
						value={ object.attributes.zoom }
						onChange={ ( value ) =>
							onChange( 'zoom', value, object )
						}
					/>
					{ 'list' === source && (
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Show popup with link',
								'connector-for-propstack'
							) }
							help={ __(
								'Shows the title and a link to the object if the visitor clicks on its marker.',
								'connector-for-propstack'
							) }
							checked={ !! object.attributes.popup }
							onChange={ ( value ) =>
								onChange( 'popup', !! value, object )
							}
						/>
					) }
				</PanelBody>
				<PanelBody
					title={ __( 'Style', 'connector-for-propstack' ) }
					initialOpen={ false }
				>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Height', 'connector-for-propstack' ) }
						placeholder={
							window.cfprop_map_block?.height ?? '400px'
						}
						help={ __(
							'E.g. 400px or 50vh. Leave empty to use the height from the settings.',
							'connector-for-propstack'
						) }
						value={ object.attributes.height }
						onChange={ ( value ) =>
							onChange( 'height', value, object )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="connector-for-propstack/map"
				attributes={ object.attributes }
				httpMethod="POST"
			/>
		</div>
	);
}

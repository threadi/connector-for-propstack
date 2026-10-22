/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * Add individual dependencies.
 */
import { ExternalLink, Notice, PanelBody, TextControl } from '@wordpress/components';
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

	// get the data for the hint (Connector for Propstack Pro disables it).
	const showProHint = !! window.cfprop_inquiry_button_block?.show_pro_hint;
	const proUrl = window.cfprop_inquiry_button_block?.pro_url ?? '';

	/**
	 * Collect return for the edit-function
	 */
	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'connector-for-propstack' ) }
				>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Link target', 'connector-for-propstack' ) }
						placeholder="#contact"
						help={ __(
							'Optional: e.g. #contact for a form with this ID on the same page or the URL of your contact page. If empty, the button uses the email of the broker, otherwise the phone number of the broker, otherwise the email of this website.',
							'connector-for-propstack'
						) }
						value={ object.attributes.url }
						onChange={ ( value ) =>
							onChange( 'url', value, object )
						}
					/>
					{ showProHint && (
						<Notice status="info" isDismissible={ false }>
							<p>
								{ __(
									'With Connector for Propstack Pro, you can link this button to a contact form on the page, which takes over the object and the broker automatically and sends the inquiry to Propstack.',
									'connector-for-propstack'
								) }
							</p>
							{ proUrl && (
								<ExternalLink href={ proUrl }>
									{ __(
										'Learn more about Pro',
										'connector-for-propstack'
									) }
								</ExternalLink>
							) }
						</Notice>
					) }
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="connector-for-propstack/inquiry-button"
				attributes={ object.attributes }
				httpMethod="POST"
			/>
		</div>
	);
}

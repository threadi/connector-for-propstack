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
	SelectControl,
	PanelBody
} from '@wordpress/components';
import {
	InspectorControls,
	useBlockProps
} from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
const { dispatch, useSelect } = wp.data;
const { useEffect, useState } = wp.element;
import {
  onChange,
} from '../../components';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @param object
 * @return {WPElement} Element to render.
 */
export default function Edit( object ) {

  const [ query, setQuery ] = useState( '' );

	// secure ID of this block
  useEffect( () => {
    if ( ! object.attributes.blockId ) {
      object.setAttributes( { blockId: object.clientId } );
    }
  }, [ object.attributes.blockId, object.clientId ] );

  const isPreview = !! object.attributes.preview;

  useEffect( () => {
    if ( isPreview ) {
      return;
    }
    dispatch( 'core' ).addEntities( [
      {
        name: 'fields',
        kind: 'connector-for-propstack/v1',
        baseURL: '/connector-for-propstack/v1/fields'
      }
    ] );
  }, [ isPreview ] );

  const description_types = useSelect(
    ( select ) => {
      if ( isPreview ) {
        return [];
      }
      return select( 'core' ).getEntityRecords( 'connector-for-propstack/v1', 'fields', { field_category: 'descriptions', per_page: 20 } ) || [];
    },
    [ isPreview ]
  );

	/**
	 * Collect return for the edit-function
	 */
	return (
		<div { ...useBlockProps() }>
      <InspectorControls>
        <PanelBody title={ __( 'Settings', 'connector-for-propstack' ) }>
          <SelectControl
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            label={__('Select description', 'connector-for-propstack')}
            options={ description_types }
            value={object.attributes.description_type}
            onChange={(value) => onChange( 'description_type', value, object )}
          />
        </PanelBody>
      </InspectorControls>
			<ServerSideRender
				block="connector-for-propstack/description"
				attributes={ object.attributes }
				httpMethod="POST"
			/>
		</div>
	);
}

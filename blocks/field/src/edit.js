/**
 * Add individual dependencies.
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { ComboboxControl, PanelBody } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import apiFetch from '@wordpress/api-fetch';
import { useEffect } from '@wordpress/element';
import { onChange } from '../../components';

let isFetching = false;

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

  const { setFields } = useDispatch( 'connector-for-propstack/fields' );
  const fields  = useSelect( (select) => select( 'connector-for-propstack/fields' ).getFields() );
  const isLoaded = useSelect( (select) => select( 'connector-for-propstack/fields' ).isLoaded() );

  useEffect( () => {
    if ( ! object.attributes.blockId ) {
      object.setAttributes( { blockId: object.clientId } );
    }
  }, [ object.attributes.blockId, object.clientId ] );

  useEffect( () => {
    // only load once.
    if ( isLoaded || isFetching ) return;

    isFetching = true;

    apiFetch( {
      path: `/connector-for-propstack/v1/fields?per_page=300`,
    } )
      .then( ( results ) => {

        const mapped = results.map( ( field ) => ( {
          label: field.label,
          value: field.value,
        } ) );
        setFields( mapped );
      } )
      .catch( ( err ) => {
        console.error( err );
        isFetching = false;
      } );

  }, [ isLoaded ] );

  return (
    <div { ...useBlockProps() }>
      <InspectorControls>
        <PanelBody title={ __( 'Settings', 'connector-for-propstack' ) }>
          <ComboboxControl
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            label={ __( 'Select field', 'connector-for-propstack' ) }
            options={ fields }
            value={ object.attributes.field_name }
            multiple={ false }
            isLoading={ ! isLoaded }
            onChange={ ( value ) => onChange( 'field_name', value, object ) }
          />
        </PanelBody>
      </InspectorControls>
      <ServerSideRender
        block="connector-for-propstack/field"
        attributes={ object.attributes }
        httpMethod="POST"
      />
    </div>
  );
}

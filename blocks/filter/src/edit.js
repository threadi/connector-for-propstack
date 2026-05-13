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
	useEffect(() => {
		object.setAttributes({blockId: object.clientId});
	});

  // get the possible filters.
  let filters = [];
  if( !object.attributes.preview ) {
    useEffect( () => {
      dispatch( 'core' ).addEntities( [
        {
          name: 'filters',
          kind: 'connector-for-propstack/v1',
          baseURL: '/connector-for-propstack/v1/filters'
        }
      ] );
    }, [ query ] );
    filters = useSelect( (select) => {
        return select( 'core' ).getEntityRecords( 'connector-for-propstack/v1', 'filters', { per_page: 10, query: query } ) || [];
      }
    );
  }

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
            label={__('Select filters', 'connector-for-propstack')}
            options={ filters }
            multiple={true}
            value={object.attributes.filters}
            onChange={(value) => onChange( 'filters', value, object )}
          />
        </PanelBody>
      </InspectorControls>
			<ServerSideRender
				block="connector-for-propstack/filter"
				attributes={ object.attributes }
				httpMethod="POST"
			/>
		</div>
	);
}

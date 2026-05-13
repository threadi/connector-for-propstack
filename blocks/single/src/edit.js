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
import {
  onChange,
} from '../../components';
const { useSelect } = wp.data;
const { useEffect } = wp.element;

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

	// secure ID of this block
	useEffect(() => {
		object.setAttributes({blockId: object.clientId});
	});

  // useSelect to retrieve all entries on our own cpt
  const immo_objects = useSelect(
    (select) => select('core').getEntityRecords('postType', 'cfprop_object', { per_page: -1 }), []
  );

  // Options expects [{label: ..., value: ...}]
  // noinspection JSUnresolvedVariable
  let immoObjectsOptions = !Array.isArray(immo_objects) ? immo_objects : immo_objects
    .map(
      // Format the options for display in the <SelectControl/>
      (immo_object) => ({
        label: immo_object.title.raw,
        value: immo_object.meta.object_id, // the value saved as postType in attributes
      })
    );

  // create an array if it is empty until now
  if( !Array.isArray(immoObjectsOptions) ) {
    immoObjectsOptions = [];
  }

  // add entry on first index of list of objects
  immoObjectsOptions.unshift({
    label: __( 'Please choose', 'connector-for-propstack' ),
    value: 0
  });

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
						label={__('Select object', 'connector-for-propstack')}
						options={ immoObjectsOptions }
						value={object.attributes.id}
						onChange={(value) => onChange( 'id', parseInt(value), object )}
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="connector-for-propstack/single"
				attributes={ object.attributes }
				httpMethod="POST"
			/>
		</div>
	);
}

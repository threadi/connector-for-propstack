/**
 * Update the value of an attribute.
 *
 * @param field
 * @param newValue
 * @param object
 */
export const onChange = ( field, newValue, object ) => {
  object.setAttributes( { [field]: newValue } );
}

/**
 * Set the Propstack icon used by each block.
 */
const el = wp.element.createElement;
export const propstackIcon = el( 'img', {
  src: window.propstack_connector_config?.icon_url,
  width: 20,
  height: 20,
} );

<?php
/**
 * Template to show a select filter.
 *
 * @param Select $interface The filter type object with the data to use.
 *
 * @package connector-for-propstack
 * @version: 1.0.0
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\FilterTypes\Select;

?>
<fieldset>
	<label for="propstack-connector-filter-<?php echo esc_attr( $interface->get_filter_name() ); ?>"><?php echo esc_html( $interface->get_label() ); ?></label>
	<select id="propstack-connector-filter-<?php echo esc_attr( $interface->get_filter_name() ); ?>" name="filter[<?php echo esc_attr( $interface->get_filter_name() ); ?>]">
		<?php
		foreach ( $interface->get_options() as $propstack_connector_key => $propstack_connector_label ) {
			?>
				<option value="<?php echo esc_attr( $propstack_connector_key ); ?>"<?php echo $interface->is_selected( $propstack_connector_key ) ? ' selected' : ''; ?>><?php echo esc_html( $propstack_connector_label ); ?></option>
				<?php
		}
		?>
	</select>
</fieldset>

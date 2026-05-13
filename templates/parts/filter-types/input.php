<?php
/**
 * Template to show an input filter.
 *
 * @param Input $interface The filter type object with the data to use.
 *
 * @package connector-for-propstack
 * @version: 1.0.0
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\FilterTypes\Input;

?>
<fieldset>
	<label for="propstack-connector-filter-<?php echo esc_attr( $interface->get_filter_name() ); ?>"><?php echo esc_html( $interface->get_label() ); ?></label>
	<input type="text" value="<?php echo esc_attr( $interface->get_value() ); ?>" id="propstack-connector-filter-<?php echo esc_attr( $interface->get_filter_name() ); ?>" name="filter[<?php echo esc_attr( $interface->get_filter_name() ); ?>]" placeholder="<?php echo esc_attr( $interface->get_placeholder() ); ?>">
</fieldset>

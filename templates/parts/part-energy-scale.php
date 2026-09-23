<?php
/**
 * Template to show the energy scale.
 *
 * @param string $energy_efficiency_class The energy efficiency class of this object.
 *
 * @package connector-for-propstack
 * @version: 1.0.0
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

// start the wrapper.
?><div class="cfprop-energy-scale">
<?php

// loop through the available energy efficiency classes.
foreach ( \ConnectorForPropstack\Propstack\Energy_Efficiency_Classes::get_instance()->get_energy_efficiency_classes() as $cfprop_energy_class_name ) {
	// check if this is the class of the object.
	$cfprop_active = ( $cfprop_energy_class_name === $energy_efficiency_class );

	// show the field with color, name and optional active marker.
	echo wp_kses_post(
		sprintf(
			'<span class="cfprop-energy-scale-color-%1$s%2$s">%3$s</span>',
			esc_attr( strtolower( str_replace( '+', '-plus', $cfprop_energy_class_name ) ) ),
			$cfprop_active ? ' cfprop-energy-scale-active' : '',
			esc_attr( $cfprop_energy_class_name ),
		)
	);
}

// end the wrapper.
?>
</div>

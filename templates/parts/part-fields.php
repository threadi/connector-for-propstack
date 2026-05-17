<?php
/**
 * Template to show multiple fields with their values for a single object.
 *
 * @param array<string,array<string,string>> $fields The fields with their labels and values.
 *
 * @package connector-for-propstack
 * @version: 1.0.0
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

foreach ( $fields as $cfprop_field ) {
	echo '<p><strong>' . esc_html( $cfprop_field['label'] ) . ':</strong> ' . wp_kses_post( $cfprop_field['value'] ) . '</p>';
}

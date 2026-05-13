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

foreach ( $fields as $propstack_connector_field ) {
	echo '<p><strong>' . esc_html( $propstack_connector_field['label'] ) . ':</strong> ' . wp_kses_post( $propstack_connector_field['value'] ) . '</p>';
}

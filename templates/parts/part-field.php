<?php
/**
 * Template to show the value of a single field on a single object.
 *
 * @param \ConnectorForPropstack\Propstack\Field_Base $field The field object.
 * @param string $field_value The text of the description.
 *
 * @package connector-for-propstack
 * @version: 1.0.0
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

echo '<p class="propstack-connector-field propstack-connector-field-' . esc_attr( $field->get_name() ) . '">' . wp_kses_post( $field_value ) . '</p>';

<?php
/**
 * Template to show the value of a single field on a single object.
 *
 * @param array<string,mixed> $attributes List of attributes.
 * @param Field_Base $field The field object.
 * @param string $field_value The text of the description.
 *
 * @package connector-for-propstack
 * @version: 1.0.0
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Base;

echo '<p class="cfprop-field cfprop-field-' . esc_attr( $field->get_name() ) . ' ' . esc_attr( $attributes['classes'] ) . '">' . wp_kses_post( $field_value ) . '</p>';

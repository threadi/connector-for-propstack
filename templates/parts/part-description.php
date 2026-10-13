<?php
/**
 * Template to show the object description.
 *
 * @param string $description_text The text of the description.
 *
 * @package connector-for-propstack
 * @version: 1.0.0
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

echo '<p' . ( ! empty( $attributes['classes'] ) && is_string( $attributes['classes'] ) ? ' class="' . esc_attr( trim( $attributes['classes'] ) ) . '"' : '' ) . '>' . wp_kses_post( $description_text ) . '</p>';

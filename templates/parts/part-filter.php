<?php
/**
 * Template to show the filter.
 *
 * @param array<string,mixed> $attributes List of attributes for displaying templates.
 *
 * @package connector-for-propstack
 * @version: 1.0.0
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

// output.
?>
<form action="<?php echo esc_url( $attributes['current_url'] ); ?>" method="<?php echo esc_attr( $attributes['method'] ); ?>" class="cfprop-filter default-max-width<?php echo esc_attr( $attributes['classes'] ); ?>">
	<?php

	/**
	 * Run action before the filter output.
	 *
	 * @since 1.0.0 Available since 1.0.0.
	 * @param array<string,mixed> $attributes List of attributes for displaying templates.
	 */
	do_action( 'cfprop_filter_before', $attributes );

	foreach ( $attributes['filter_objects'] as $cfprop_filter ) {
		echo wp_kses_post( $cfprop_filter->render() );
	}

	/**
	 * Run action after the filter output.
	 *
	 * @since 1.0.0 Available since 1.0.0.
	 * @param array<string,mixed> $attributes List of attributes for displaying templates.
	 */
	do_action( 'cfprop_filter_after', $attributes );

	?>
	<button type="submit" class="cfprop-filter-button"><?php echo esc_html__( 'Filter', 'connector-for-propstack' ); ?></button>
</form>

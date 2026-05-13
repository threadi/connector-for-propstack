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
<form action="<?php echo esc_url( $attributes['current_url'] ); ?>" method="<?php echo esc_attr( $attributes['method'] ); ?>" class="propstack-connector-filter default-max-width<?php echo esc_attr( $attributes['classes'] ); ?>">
	<?php

	/**
	 * Run action before the filter output.
	 *
	 * @since 1.0.0 Available since 1.0.0.
	 * @param array<string,mixed> $attributes List of attributes for displaying templates.
	 */
	do_action( 'propstack_connector_filter_before', $attributes );

	foreach ( $attributes['filter_objects'] as $propstack_connector_filter ) {
		echo wp_kses_post( $propstack_connector_filter->render() );
	}

	/**
	 * Run action after the filter output.
	 *
	 * @since 1.0.0 Available since 1.0.0.
	 * @param array<string,mixed> $attributes List of attributes for displaying templates.
	 */
	do_action( 'propstack_connector_filter_after', $attributes );

	?>
	<button type="submit" class="propstack-connector-filter-button"><?php echo esc_html__( 'Filter', 'connector-for-propstack' ); ?></button>
</form>

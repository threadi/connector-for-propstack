<?php
/**
 * Template to show a map with the location of one or more objects.
 *
 * The map itself is created by the script js/map.js (after the consent of the visitor, if enabled).
 *
 * @param array<string,mixed> $attributes List of attributes for displaying templates.
 *
 * @package connector-for-propstack
 * @version: 3.0.0
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

// bail if no points are given.
if ( empty( $attributes['map_points'] ) || ! is_array( $attributes['map_points'] ) ) {
	return;
}

// output.
?>
<div class="<?php echo esc_attr( (string) $attributes['classes'] ); ?>">
	<?php
	// show a preview in editors.
	if ( ! empty( $attributes['map_preview'] ) ) {
		?>
		<div class="cfprop-map cfprop-map-preview" style="height: <?php echo esc_attr( (string) $attributes['map_height'] ); ?>">
			<p>
				<?php
				/* translators: %1$s will be replaced by the name of the map service, %2$d by the number of locations. */
				echo esc_html( sprintf( _n( 'Map of %1$s with %2$d location', 'Map of %1$s with %2$d locations', count( $attributes['map_points'] ), 'connector-for-propstack' ), \ConnectorForPropstack\Propstack\Maps::get_instance()->get_provider_label(), count( $attributes['map_points'] ) ) );
				?>
			</p>
		</div>
		<?php
	} else {
		?>
		<div class="cfprop-map cfprop-map-<?php echo esc_attr( (string) $attributes['map_provider'] ); ?>" style="height: <?php echo esc_attr( (string) $attributes['map_height'] ); ?>" data-cfprop-map="<?php echo esc_attr( (string) wp_json_encode( $attributes['map_config'] ) ); ?>">
			<noscript><p><?php echo esc_html__( 'Please enable JavaScript to show the map.', 'connector-for-propstack' ); ?></p></noscript>
		</div>
		<?php
	}
	?>
</div>

<?php
/**
 * Template for show fields on a taxonomy term of a single object.
 *
 * @param array<string,string> $fields The fields with their labels and values.
 *
 * @package connector-for-propstack
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

foreach ( $fields as $propstack_connector_label => $propstack_connector_value ) {
	?>
		<div class="field">
			<strong><?php echo esc_html( $propstack_connector_label ); ?></strong><br>
			<?php echo wp_kses_post( $propstack_connector_value ); ?>
		</div>
	<?php
}

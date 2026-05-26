<?php
/**
 * Template for output a single immo objects.
 *
 * @param array<string,mixed> $attributes List of settings.
 * @param int $post_id The post-ID of the immo object.
 * @param ImmoObject $immo_object The immo object.
 *
 * @version: 1.0.0
 * @package connector-for-propstack
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\ImmoObject;

// output.
?><div class="<?php echo esc_attr( $attributes['classes'] ); ?>">
	<article id="post-<?php echo absint( $post_id ); ?>" class="post-<?php echo absint( $post_id ); ?> post type-<?php echo esc_attr( \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() ); ?> status-<?php echo esc_attr( get_post_status( $post_id ) ); ?> entry <?php echo esc_attr( apply_filters( 'cfprop_object_classes', '', $immo_object ) ); ?>" role="region" aria-label="<?php echo esc_attr__( 'Objects', 'connector-for-propstack' ); ?>">
		<?php
		foreach ( $attributes['templates'] as $cfprop_template ) {
			/**
			 * Use the template hook to render the object.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 * @param ImmoObject    $immo_object      The immo object as an object.
			 * @param string       $cfprop_template         The template name.
			 * @param array<string,mixed>  $attributes   List of attributes.
			 */
			do_action( 'cfprop_template_' . $cfprop_template, $immo_object, $cfprop_template, $attributes );
		}
		?>
	</article>
</div>

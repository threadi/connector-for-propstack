<?php
/**
 * Default-template for archive-listing.
 *
 * @param array<string,mixed> $attributes List of settings.
 *
 * @package connector-for-propstack
 * @version: 1.0.0
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\ImmoObject;

?><div class="<?php echo esc_attr( $attributes['classes'] ); ?>">
	<?php
	while ( $attributes['query']->have_posts() ) :
		$attributes['query']->the_post();

		// secure the post-ID.
		$post_id = absint( get_the_id() );

		// get the immo object as an object with the requested language.
		$propstack_connector_immo_object = \ConnectorForPropstack\Propstack\ImmoObjects::get_instance()->get_object( $post_id, $attributes['lang'] );

		?>
		<article id="post-<?php echo absint( $post_id ); ?>" class="post-<?php echo absint( $post_id ); ?> post type-<?php echo esc_attr( \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() ); ?> status-<?php echo esc_attr( get_post_status( $post_id ) ); ?> entry <?php echo esc_attr( apply_filters( 'propstack_connector_object_classes', '', $propstack_connector_immo_object ) ); ?>" role="region" aria-label="<?php echo esc_attr__( 'Objects', 'connector-for-propstack' ); ?>">
			<?php
			foreach ( $attributes['templates'] as $cfprop_template ) {
				/**
				 * Use the template hook to render the object.
				 *
				 * @since 1.0.0 Available since 1.0.0.
				 * @param ImmoObject    $propstack_connector_immo_object      The immo object as an object.
				 * @param array<string,mixed>  $attributes   List of attributes.
				 */
				do_action( 'cfprop_template_' . $cfprop_template, $propstack_connector_immo_object, $attributes );
			}
			?>
		</article>
		<?php
	endwhile;
	?>
</div>

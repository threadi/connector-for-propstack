<?php
/**
 * Template for output a list of immo objects.
 *
 * @param array<string,mixed> $attributes List of settings.
 *
 * @version: 1.0.0
 * @package connector-for-propstack
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Templates;

if ( ! empty( $attributes['listing_template'] ) && ! empty( $attributes['templates'] ) ) {

	// loop through the list by using set the listing template.
	if ( $attributes['query']->have_posts() ) :
		include Templates::get_instance()->get_template( 'parts/archive/' . $attributes['listing_template'] . '.php' );
	else :
		?><article class="site-main entry inside-article container site-content site-container content-bg content-area ht-container"><div class="entry-content"><p><?php echo esc_html__( 'There are currently no objects available.', 'connector-for-propstack' ); ?></p></div></article>
		<?php
	endif;
}
wp_reset_postdata();

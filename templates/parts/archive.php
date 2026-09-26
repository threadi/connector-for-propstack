<?php
/**
 * Template for output a list of immo objects.
 *
 * @param array<string,mixed> $attributes List of settings.
 *
 * @version: 2.0.0
 * @package connector-for-propstack
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Templates;

if ( ! empty( $attributes['listing_template'] ) && is_string( $attributes['listing_template'] ) && ! empty( $attributes['templates'] ) && is_array( $attributes['templates'] ) ) {
	// use only known listing templates (prevents path traversal).
	$cfprop_listing_template = array_key_exists( $attributes['listing_template'], Templates::get_instance()->get_archive_templates() ) ? $attributes['listing_template'] : 'default';

	// loop through the list by using set the listing template.
	if ( $attributes['query']->have_posts() ) :
		include Templates::get_instance()->get_template( 'parts/archive/' . $cfprop_listing_template . '.php' );

		// show the pagination, if set.
		if ( ! empty( $attributes['pagination'] ) && is_string( $attributes['pagination'] ) ) :
			?><nav class="navigation pagination cfprop-pagination" aria-label="<?php echo esc_attr__( 'Objects pagination', 'connector-for-propstack' ); ?>"><div class="nav-links"><?php echo wp_kses_post( $attributes['pagination'] ); ?></div></nav>
			<?php
		endif;
	else :
		?><article class="site-main entry inside-article container site-content site-container content-bg content-area ht-container"><div class="entry-content"><p><?php echo esc_html__( 'There are currently no objects available.', 'connector-for-propstack' ); ?></p></div></article>
		<?php
	endif;
}
wp_reset_postdata();

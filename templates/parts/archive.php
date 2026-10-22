<?php
/**
 * Template for output a list of immo objects.
 *
 * @param array<string,mixed> $attributes List of settings.
 *
 * @version: 3.0.0
 * @package connector-for-propstack
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Templates;

if ( ! empty( $attributes['listing_template'] ) && is_string( $attributes['listing_template'] ) && ! empty( $attributes['templates'] ) && is_array( $attributes['templates'] ) ) {
	// use only known listing templates (prevents path traversal).
	$cfprop_listing_template = array_key_exists( $attributes['listing_template'], Templates::get_instance()->get_archive_templates() ) ? $attributes['listing_template'] : 'default';

	// use the default listing template if the requested one does not exist.
	if ( ! Templates::get_instance()->has_template( 'parts/archive/' . $cfprop_listing_template . '.php' ) ) {
		$cfprop_listing_template = 'default';
	}

	// loop through the list by using set the listing template.
	if ( $attributes['query']->have_posts() ) {
		include Templates::get_instance()->get_template( 'parts/archive/' . $cfprop_listing_template . '.php' );

		// show the pagination only if enabled (disabled by default, the pagination is a feature of the Pro plugin or custom templates).
		$cfprop_show_pagination = false;
		/**
		 * Filter whether the pagination should be shown below the archive listing.
		 *
		 * Disabled by default.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param bool                $cfprop_show_pagination True to show the pagination.
		 * @param array<string,mixed> $attributes             List of attributes.
		 */
		if ( apply_filters( 'cfprop_archive_show_pagination', $cfprop_show_pagination, $attributes ) && ! empty( $attributes['pagination'] ) && is_string( $attributes['pagination'] ) ) {
			?>
			<nav class="cfprop-pagination navigation pagination" aria-label="<?php echo esc_attr__( 'Objects pagination', 'connector-for-propstack' ); ?>"><div class="nav-links"><?php echo wp_kses_post( $attributes['pagination'] ); ?></div></nav>
			<?php
		}
	} else {
		?>
		<article class="site-main entry inside-article container site-content site-container content-bg content-area ht-container"><div class="entry-content"><p><?php echo esc_html__( 'There are currently no objects available.', 'connector-for-propstack' ); ?></p></div></article>
		<?php
	}
}
wp_reset_postdata();

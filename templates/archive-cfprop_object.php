<?php
/**
 * Template for output a list of objects as an archive of our custom post type.
 *
 * @version: 3.0.0
 * @package connector-for-propstack
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

// get the description.
$cfprop_description = get_the_archive_description();

get_header();

?>
	<div class="cfprop-page cfprop-page-archive">
		<header class="site-main page-header alignwide">
			<?php the_archive_title( '<h1 class="page-title site-container">', '</h1>' ); ?>
			<?php if ( $cfprop_description ) : ?>
				<div class="archive-description"><?php echo wp_kses_post( wpautop( $cfprop_description ) ); ?></div>
			<?php endif; ?>
		</header>
		<?php
		echo wp_kses( \ConnectorForPropstack\Propstack\Widgets\Filter::get_instance()->render( array() ), \ConnectorForPropstack\Plugin\Templates::get_instance()->get_allowed_filter_html() );
		echo wp_kses( \ConnectorForPropstack\Propstack\Widgets\Archive::get_instance()->render( array() ), \ConnectorForPropstack\Propstack\Widgets\Archive::get_instance()->get_allowed_html() );
		?>
	</div>
<?php

get_footer();

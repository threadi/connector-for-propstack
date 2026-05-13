<?php
/**
 * Template for output a list of objects as an archive of our custom post type.
 *
 * @version: 1.0.0
 * @package connector-for-propstack
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

// get the description.
$propstack_connector_description = get_the_archive_description();

get_header();

?>
	<div>
		<header class="site-main page-header alignwide">
			<?php the_archive_title( '<h1 class="page-title site-container">', '</h1>' ); ?>
			<?php if ( $propstack_connector_description ) : ?>
				<div class="archive-description"><?php echo wp_kses_post( wpautop( $propstack_connector_description ) ); ?></div>
			<?php endif; ?>
		</header>
		<?php
		echo wp_kses_post( \ConnectorForPropstack\Propstack\Widgets\Filter::get_instance()->render( array() ) );
		echo wp_kses_post( \ConnectorForPropstack\Propstack\Widgets\Archive::get_instance()->render( array() ) );
		?>
	</div>
<?php

get_footer();

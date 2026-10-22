<?php
/**
 * Template for the output of a single immo object.
 *
 * @version: 2.2.0
 * @package connector-for-propstack
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Fields\Main\ObjectId;

// get its object ID.
$cfprop_object_id = get_post_meta( absint( get_the_ID() ), ( new ObjectId() )->get_name(), true );

get_header();

?>
	<div class="cfprop-page cfprop-page-single">
		<?php
		echo wp_kses_post( \ConnectorForPropstack\Propstack\Widgets\Single::get_instance()->render( array( 'object_id' => $cfprop_object_id ) ) );
		?>
	</div>
<?php

get_footer();

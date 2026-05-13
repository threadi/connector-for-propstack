<?php
/**
 * File as base to generate a dynamic pattern for object type specific fields.
 *
 * @param array<int,Field_Base> $fields List of fields.
 *
 * @package connector-for-propstack
 * @version : 1.0.0
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Base;

?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_html__( 'Object data', 'connector-for-propstack' ); ?>"},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:heading -->
	<h2 class="wp-block-heading"><?php echo esc_html__( 'Object data', 'connector-for-propstack' ); ?></h2>
	<!-- /wp:heading -->

	<?php
	foreach ( $fields as $propstack_connector_field ) {
		?>
		<!-- wp:columns {"verticalAlignment":"top"} -->
		<div class="wp-block-columns are-vertically-aligned-top"><!-- wp:column {"verticalAlignment":"top","width":"33.33%"} -->
			<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:33.33%"><!-- wp:paragraph -->
				<p><strong><?php echo esc_html( $propstack_connector_field->get_label() ); ?></strong></p>
				<!-- /wp:paragraph --></div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"top","width":"66.66%","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":"0"}}} -->
			<div class="wp-block-column is-vertically-aligned-top" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;flex-basis:66.66%"><!-- wp:propstack-connector/field {"field_name":"<?php echo esc_attr( $propstack_connector_field->get_name() ); ?>","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}}} /--></div>
			<!-- /wp:column --></div>
		<!-- /wp:columns -->
		<?php
	}
	?>
	</div>
<!-- /wp:group -->

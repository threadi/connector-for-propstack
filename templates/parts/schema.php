<?php
/**
 * Template for output any schema.org type.
 *
 * @param array<string,mixed> $schema The schema.org listing.
 * @param Schema_Base $schema_type The schema type.
 *
 * @version: 1.0.0
 * @package connector-for-propstack
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\Schema_Base;

?>
<script type="application/ld+json" class="propstack-connector-schema-<?php echo esc_attr( $schema_type->get_name() ); ?>">
	<?php echo wp_kses_post( Helper::get_json( $schema ) ); ?>
</script>

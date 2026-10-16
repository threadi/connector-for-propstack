<?php
/**
 * Template to show the selection for the sorting of the list of objects.
 *
 * @param array<string,mixed> $attributes List of attributes for displaying templates.
 *
 * @package connector-for-propstack
 * @version: 2.1.0
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

// bail if no options are given.
if ( empty( $attributes['sorting_options'] ) || ! is_array( $attributes['sorting_options'] ) ) {
	return;
}

// get the ID for the label.
$cfprop_sorting_id = wp_unique_id( 'cfprop-sorting-' );

// output.
?>
<form action="<?php echo esc_url( (string) $attributes['sorting_action'] ); ?>" method="<?php echo esc_attr( (string) $attributes['sorting_method'] ); ?>" class="cfprop-sorting default-max-width">
	<?php
	// add the hidden fields, which keep the actual filter.
	foreach ( (array) $attributes['sorting_hidden'] as $cfprop_name => $cfprop_value ) {
		?>
		<input type="hidden" name="<?php echo esc_attr( (string) $cfprop_name ); ?>" value="<?php echo esc_attr( (string) $cfprop_value ); ?>">
		<?php
	}

	/**
	 * Run action before the output of the sorting selection.
	 *
	 * @since 2.1.0 Available since 2.1.0.
	 * @param array<string,mixed> $attributes List of attributes for displaying templates.
	 */
	do_action( 'cfprop_sorting_before', $attributes );
	?>
	<label for="<?php echo esc_attr( $cfprop_sorting_id ); ?>"><?php echo esc_html__( 'Sort by', 'connector-for-propstack' ); ?></label>
	<select id="<?php echo esc_attr( $cfprop_sorting_id ); ?>" name="<?php echo esc_attr( (string) $attributes['sorting_param'] ); ?>">
		<?php
		foreach ( $attributes['sorting_options'] as $cfprop_key => $cfprop_label ) {
			?>
			<option value="<?php echo esc_attr( (string) $cfprop_key ); ?>"<?php echo (string) $cfprop_key === (string) $attributes['sorting_active'] ? ' selected' : ''; ?>><?php echo esc_html( (string) $cfprop_label ); ?></option>
			<?php
		}
		?>
	</select>
	<?php
	/**
	 * Run action after the output of the sorting selection.
	 *
	 * @since 2.1.0 Available since 2.1.0.
	 * @param array<string,mixed> $attributes List of attributes for displaying templates.
	 */
	do_action( 'cfprop_sorting_after', $attributes );
	?>
	<button type="submit" class="cfprop-sorting-button"><?php echo esc_html__( 'Sort', 'connector-for-propstack' ); ?></button>
</form>

<?php
/**
 * Template to show the inquiry button.
 *
 * @param array{url: string, label: string, type: string} $target                      The target of the button with URL, label and type.
 * @param string                                          $inquiry_button_classes      The classes of the wrapper.
 * @param string                                          $inquiry_button_style        The inline styles of the wrapper (already sanitized).
 * @param string                                          $inquiry_button_link_classes The classes of the link.
 * @param string                                          $inquiry_button_link_style   The inline styles of the link (already sanitized).
 * @param string                                          $inquiry_button_styles       The CSS for the configured styles (already sanitized).
 * @param array<string,mixed>                             $attributes                  List of attributes.
 *
 * @package connector-for-propstack
 * @version: 1.0.0
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

?><div class="<?php echo esc_attr( $inquiry_button_classes ); ?>"<?php echo '' !== $inquiry_button_style ? ' style="' . esc_attr( $inquiry_button_style ) . '"' : ''; ?>>
	<?php
	if ( ! empty( $inquiry_button_styles ) ) {
		echo '<style>' . wp_strip_all_tags( $inquiry_button_styles ) . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The CSS is generated from sanitized values.
	}
	?>
	<a href="<?php echo esc_url( $target['url'], array( 'http', 'https', 'mailto', 'tel' ) ); ?>" class="cfprop-inquiry-button-link <?php echo esc_attr( $inquiry_button_link_classes ); ?>"<?php echo '' !== $inquiry_button_link_style ? ' style="' . esc_attr( $inquiry_button_link_style ) . '"' : ''; ?>><?php echo esc_html( $target['label'] ); ?></a>
</div>

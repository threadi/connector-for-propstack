<?php
/**
 * File to handle the inquiry button widget.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Widgets;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Templates;
use ConnectorForPropstack\Propstack\Fields\Main\ObjectId;
use ConnectorForPropstack\Propstack\ImmoObject;
use ConnectorForPropstack\Propstack\Widget_Base;

/**
 * Object to handle the inquiry button widget.
 *
 * Shows a button for an inquiry about an object. The target of the button is determined in this order:
 * 1. the link target set in the widget (e.g. "#contact" for a form on the same page or the URL of a contact page),
 * 2. the public email of the broker (with subject and link to the object),
 * 3. the public phone number of the broker,
 * 4. the email of the administrator of the website.
 */
class Inquiry_Button extends Widget_Base {
	/**
	 * The internal name for this object.
	 *
	 * @var string
	 */
	protected string $name = 'widget_inquiry_button';

	/**
	 * Path to the Block object.
	 *
	 * @var string
	 */
	protected string $gutenberg = '\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Inquiry_Button';

	/**
	 * Instance of this object.
	 *
	 * @var ?Inquiry_Button
	 */
	private static ?Inquiry_Button $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Inquiry_Button {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return the settings of this widget for its content.
	 *
	 * Each page builder uses this list to provide the same settings.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_content_settings(): array {
		$settings = array(
			'url' => array(
				'label'       => __( 'Link target', 'connector-for-propstack' ),
				'description' => __( 'Optional: e.g. #contact for a form with this ID on the same page or the URL of your contact page. If empty, the button uses the email of the broker, otherwise the phone number of the broker, otherwise the email of this website.', 'connector-for-propstack' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => '#contact',
			),
		);

		/**
		 * Filter the content settings of the inquiry button widget, which are used in each page builder.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,array<string,mixed>> $settings List of settings.
		 */
		return apply_filters( 'cfprop_widget_inquiry_button_content_settings', $settings );
	}

	/**
	 * Return the settings of this widget for its styles.
	 *
	 * Each page builder uses this list to provide the same settings. Empty values are not used, then the button
	 * uses the styles of the theme or page builder. The block editor uses its own design tools instead.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_style_settings(): array {
		$settings = array(
			'text_color'             => array(
				'label'   => __( 'Text color', 'connector-for-propstack' ),
				'type'    => 'color',
				'default' => '',
			),
			'background_color'       => array(
				'label'   => __( 'Background color', 'connector-for-propstack' ),
				'type'    => 'color',
				'default' => '',
			),
			'text_color_hover'       => array(
				'label'   => __( 'Text color on hover', 'connector-for-propstack' ),
				'type'    => 'color',
				'default' => '',
			),
			'background_color_hover' => array(
				'label'   => __( 'Background color on hover', 'connector-for-propstack' ),
				'type'    => 'color',
				'default' => '',
			),
			'font_family'            => array(
				'label'       => __( 'Font family', 'connector-for-propstack' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => 'Arial, sans-serif',
			),
			'font_size'              => array(
				'label'       => __( 'Font size', 'connector-for-propstack' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => '16px',
			),
			'font_weight'            => array(
				'label'   => __( 'Font weight', 'connector-for-propstack' ),
				'type'    => 'select',
				'options' => array(
					''    => __( 'Default', 'connector-for-propstack' ),
					'100' => '100',
					'200' => '200',
					'300' => '300',
					'400' => __( '400 (normal)', 'connector-for-propstack' ),
					'500' => '500',
					'600' => '600',
					'700' => __( '700 (bold)', 'connector-for-propstack' ),
					'800' => '800',
					'900' => '900',
				),
				'default' => '',
			),
			'font_style'             => array(
				'label'   => __( 'Font style', 'connector-for-propstack' ),
				'type'    => 'select',
				'options' => array(
					''       => __( 'Default', 'connector-for-propstack' ),
					'normal' => __( 'Normal', 'connector-for-propstack' ),
					'italic' => __( 'Italic', 'connector-for-propstack' ),
				),
				'default' => '',
			),
			'text_transform'         => array(
				'label'   => __( 'Letter case', 'connector-for-propstack' ),
				'type'    => 'select',
				'options' => array(
					''           => __( 'Default', 'connector-for-propstack' ),
					'none'       => __( 'None', 'connector-for-propstack' ),
					'uppercase'  => __( 'Uppercase', 'connector-for-propstack' ),
					'lowercase'  => __( 'Lowercase', 'connector-for-propstack' ),
					'capitalize' => __( 'Capitalize', 'connector-for-propstack' ),
				),
				'default' => '',
			),
			'text_decoration'        => array(
				'label'   => __( 'Decoration', 'connector-for-propstack' ),
				'type'    => 'select',
				'options' => array(
					''             => __( 'Default', 'connector-for-propstack' ),
					'none'         => __( 'None', 'connector-for-propstack' ),
					'underline'    => __( 'Underline', 'connector-for-propstack' ),
					'line-through' => __( 'Strikethrough', 'connector-for-propstack' ),
				),
				'default' => '',
			),
			'line_height'            => array(
				'label'       => __( 'Line height', 'connector-for-propstack' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => '1.4',
			),
			'letter_spacing'         => array(
				'label'       => __( 'Letter spacing', 'connector-for-propstack' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => '1px',
			),
			'padding'                => array(
				'label'       => __( 'Padding', 'connector-for-propstack' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => '10px 20px',
			),
			'border_width'           => array(
				'label'       => __( 'Border width', 'connector-for-propstack' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => '1px',
			),
			'border_style'           => array(
				'label'   => __( 'Border style', 'connector-for-propstack' ),
				'type'    => 'select',
				'options' => array(
					''       => __( 'Default', 'connector-for-propstack' ),
					'none'   => __( 'None', 'connector-for-propstack' ),
					'solid'  => __( 'Solid', 'connector-for-propstack' ),
					'dashed' => __( 'Dashed', 'connector-for-propstack' ),
					'dotted' => __( 'Dotted', 'connector-for-propstack' ),
					'double' => __( 'Double', 'connector-for-propstack' ),
				),
				'default' => '',
			),
			'border_color'           => array(
				'label'   => __( 'Border color', 'connector-for-propstack' ),
				'type'    => 'color',
				'default' => '',
			),
			'border_radius'          => array(
				'label'       => __( 'Border radius', 'connector-for-propstack' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => '4px',
			),
		);

		/**
		 * Filter the style settings of the inquiry button widget, which are used in each page builder.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,array<string,mixed>> $settings List of settings.
		 */
		return apply_filters( 'cfprop_widget_inquiry_button_style_settings', $settings );
	}

	/**
	 * Return the default values of all settings of this widget.
	 *
	 * @return array<string,string>
	 */
	public function get_default_values(): array {
		$defaults = array();
		foreach ( array_merge( $this->get_content_settings(), $this->get_style_settings() ) as $name => $setting ) {
			$defaults[ $name ] = isset( $setting['default'] ) && is_scalar( $setting['default'] ) ? (string) $setting['default'] : '';
		}
		return $defaults;
	}

	/**
	 * Return the list of params this widget supports (for the shortcode description).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_params(): array {
		$params = array();
		foreach ( array_merge( $this->get_content_settings(), $this->get_style_settings() ) as $name => $setting ) {
			$params[ $name ] = array(
				'label'    => $setting['label'],
				'required' => false,
			);
		}
		return $params;
	}

	/**
	 * Return the rendered widget.
	 *
	 * @param array<string,mixed> $attributes Attributes to configure the rendering.
	 *
	 * @return string
	 */
	public function render( array $attributes ): string {
		// get the object to use (given as attribute or by request).
		$immo_object = $this->get_immo_object( $attributes );

		// bail if no object could be found.
		if ( ! $immo_object instanceof ImmoObject ) {
			return '';
		}

		// get the target of the button.
		$target = $this->get_target( $immo_object, $attributes );

		// bail if no target could be found.
		if ( empty( $target['url'] ) ) {
			return '';
		}

		// use the styles of the core button block, as the button uses its classes (block themes load them only for used blocks).
		if ( wp_style_is( 'wp-block-button', 'registered' ) ) {
			wp_enqueue_style( 'wp-block-button' );
		}

		// get the configured styles as CSS with their own class.
		$css = $this->get_css( $attributes );

		// collect the classes for the wrapper.
		$classes = array( 'cfprop-inquiry-button', 'cfprop-inquiry-button-' . $target['type'] );
		if ( '' !== $css['class'] ) {
			$classes[] = $css['class'];
		}
		if ( ! empty( $attributes['classes'] ) && is_string( $attributes['classes'] ) ) {
			$classes[] = trim( $attributes['classes'] );
		}

		// collect the classes and the inline styles for the link (used by the block editor for its design tools).
		$link_classes = array( 'button', 'wp-block-button__link', 'wp-element-button' );
		if ( ! empty( $attributes['link_classes'] ) && is_string( $attributes['link_classes'] ) ) {
			foreach ( explode( ' ', $attributes['link_classes'] ) as $link_class ) {
				$link_class = sanitize_html_class( $link_class );
				if ( '' !== $link_class ) {
					$link_classes[] = $link_class;
				}
			}
		}
		$link_style = ! empty( $attributes['link_style'] ) && is_string( $attributes['link_style'] ) ? safecss_filter_attr( $attributes['link_style'] ) : '';

		// get the inline styles for the wrapper (e.g. the margin of the block editor).
		$wrapper_style = ! empty( $attributes['style'] ) && is_string( $attributes['style'] ) ? safecss_filter_attr( $attributes['style'] ) : '';

		// collect the variables for the template.
		$inquiry_button_classes      = implode( ' ', $classes );
		$inquiry_button_link_classes = implode( ' ', array_unique( $link_classes ) );
		$inquiry_button_link_style   = $link_style;
		$inquiry_button_style        = $wrapper_style;
		$inquiry_button_styles       = $css['css'];

		// return the template with this target.
		ob_start();

		/**
		 * Run custom actions before the output of the widget.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param array $attributes List of attributes.
		 */
		do_action( 'cfprop_get_template_before', $attributes );

		// use the template to generate the output.
		include Templates::get_instance()->get_template( 'parts/part-inquiry-button.php' );
		$content = ob_get_clean();
		if ( ! $content ) {
			return '';
		}
		return $content;
	}

	/**
	 * Return the output of the shortcode of this widget.
	 *
	 * The classes and inline styles of the design tools of the block editor are not allowed via shortcode.
	 *
	 * @param array<string,mixed>|string $attributes The attributes of the shortcode.
	 *
	 * @return string
	 */
	public function get_shortcode( array|string $attributes ): string {
		if ( is_array( $attributes ) ) {
			unset( $attributes['style'], $attributes['link_style'], $attributes['link_classes'] );
		}
		return parent::get_shortcode( $attributes );
	}

	/**
	 * Return the allowed HTML for the output of this widget.
	 *
	 * Public, as the page builders use it for the output.
	 *
	 * @return array<string,mixed>
	 */
	public function get_allowed_html(): array {
		$allowed_html = wp_kses_allowed_html( 'post' );

		// allow the styles of the button.
		if ( empty( $allowed_html['style'] ) ) {
			$allowed_html['style'] = array();
		}

		return $allowed_html;
	}

	/**
	 * Return the CSS for the configured styles of the button and the class it uses.
	 *
	 * Returns an empty class and CSS if no style is configured.
	 *
	 * @param array<string,mixed> $attributes The attributes of the widget.
	 *
	 * @return array{class: string, css: string}
	 */
	public function get_css( array $attributes ): array {
		// collect the declarations for the link and the link on hover.
		$link  = array();
		$hover = array();

		// colors.
		foreach ( array(
			'text_color'             => array( 'link', 'color' ),
			'background_color'       => array( 'link', 'background' ),
			'border_color'           => array( 'link', 'border-color' ),
			'text_color_hover'       => array( 'hover', 'color' ),
			'background_color_hover' => array( 'hover', 'background' ),
		) as $name => $target ) {
			$color = $this->sanitize_color( $this->get_attribute( $attributes, $name ) );
			if ( '' === $color ) {
				continue;
			}
			if ( 'link' === $target[0] ) {
				$link[] = $target[1] . ': ' . $color;
			} else {
				$hover[] = $target[1] . ': ' . $color;
			}
		}

		// sizes (one up to four values).
		foreach ( array(
			'font_size'      => 'font-size',
			'letter_spacing' => 'letter-spacing',
			'padding'        => 'padding',
			'border_width'   => 'border-width',
			'border_radius'  => 'border-radius',
		) as $name => $property ) {
			$size = $this->sanitize_size( $this->get_attribute( $attributes, $name ), 'letter_spacing' === $name );
			if ( '' !== $size ) {
				$link[] = $property . ': ' . $size;
			}
		}

		// values from a list (only the values of the options are allowed).
		$settings = $this->get_style_settings();
		foreach ( array(
			'font_weight'     => 'font-weight',
			'font_style'      => 'font-style',
			'text_transform'  => 'text-transform',
			'text_decoration' => 'text-decoration',
			'border_style'    => 'border-style',
		) as $name => $property ) {
			$value = $this->get_attribute( $attributes, $name );
			if ( '' !== $value && isset( $settings[ $name ]['options'] ) && is_array( $settings[ $name ]['options'] ) && array_key_exists( $value, $settings[ $name ]['options'] ) ) {
				$link[] = $property . ': ' . $value;
			}
		}

		// line height (also without unit).
		$line_height = $this->get_attribute( $attributes, 'line_height' );
		if ( 1 === preg_match( '/^(\d+(\.\d+)?|\.\d+)(px|em|rem|%)?$/', $line_height ) ) {
			$link[] = 'line-height: ' . $line_height;
		}

		// font family.
		$font_family = $this->sanitize_font_family( $this->get_attribute( $attributes, 'font_family' ) );
		if ( '' !== $font_family ) {
			$link[] = 'font-family: ' . $font_family;
		}

		// bail if no style is configured.
		if ( empty( $link ) && empty( $hover ) ) {
			return array(
				'class' => '',
				'css'   => '',
			);
		}

		// create the class.
		$class = 'cfprop-inquiry-button-' . substr( md5( implode( ';', $link ) . '|' . implode( ';', $hover ) ), 0, 10 );

		// create the rules (with the element in the selector, so they are stronger than the button styles of themes).
		$rules = array();
		if ( ! empty( $link ) ) {
			$rules[] = '.' . $class . ' a.cfprop-inquiry-button-link, .' . $class . ' a.cfprop-inquiry-button-link:visited { ' . implode( '; ', $link ) . ' }';
		}
		if ( ! empty( $hover ) ) {
			$rules[] = '.' . $class . ' a.cfprop-inquiry-button-link:hover, .' . $class . ' a.cfprop-inquiry-button-link:focus { ' . implode( '; ', $hover ) . ' }';
		}

		// return the results.
		return array(
			'class' => $class,
			'css'   => implode( ' ', $rules ),
		);
	}

	/**
	 * Return the value of an attribute as string.
	 *
	 * @param array<string,mixed> $attributes The attributes.
	 * @param string              $name       The name of the attribute.
	 *
	 * @return string
	 */
	private function get_attribute( array $attributes, string $name ): string {
		return isset( $attributes[ $name ] ) && is_scalar( $attributes[ $name ] ) ? trim( (string) $attributes[ $name ] ) : '';
	}

	/**
	 * Return the sanitized color value or an empty string if it is not valid.
	 *
	 * Allowed are hex colors, rgb(a)/hsl(a) colors, CSS variables and color names.
	 *
	 * @param string $color The color value.
	 *
	 * @return string
	 */
	public function sanitize_color( string $color ): string {
		$color = trim( $color );

		// add the hash to hex colors without it (e.g., Beaver Builder saves them this way).
		if ( 1 === preg_match( '/^([0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color ) ) {
			$color = '#' . $color;
		}

		if ( 1 === preg_match( '/^(#[0-9a-fA-F]{3,8}|(rgb|rgba|hsl|hsla)\([0-9.,%\s\/]+\)|var\(--[a-zA-Z0-9_-]+\)|[a-zA-Z]{3,30})$/', $color ) ) {
			return $color;
		}
		return '';
	}

	/**
	 * Return the sanitized size value (one up to four values) or an empty string if it is not valid.
	 *
	 * Numbers without unit are used as pixel.
	 *
	 * @param string $size           The size value.
	 * @param bool   $allow_negative Whether negative values are allowed (e.g. for letter spacing).
	 *
	 * @return string
	 */
	public function sanitize_size( string $size, bool $allow_negative = false ): string {
		// split the values.
		$values = preg_split( '/\s+/', trim( $size ) );

		// bail if no or more than four values are given.
		if ( ! is_array( $values ) || empty( $values ) || count( $values ) > 4 ) {
			return '';
		}

		// check each value.
		$result = array();
		foreach ( $values as $value ) {
			// bail if the value is not valid.
			if ( 1 !== preg_match( '/^(' . ( $allow_negative ? '-?' : '' ) . '(\d+(\.\d+)?|\.\d+))(px|em|rem|%|vw|vh)?$/', $value, $matches ) ) {
				return '';
			}

			// add "px" to numbers without unit (except 0).
			if ( empty( $matches[4] ) && 0.0 !== (float) $matches[1] ) {
				$value .= 'px';
			}

			// add the value.
			$result[] = $value;
		}

		// return the resulting value.
		return implode( ' ', $result );
	}

	/**
	 * Return the sanitized font family or an empty string if it is not valid.
	 *
	 * Allowed are a list of font names (with or without quotes) or a CSS variable.
	 *
	 * @param string $font_family The font family.
	 *
	 * @return string
	 */
	public function sanitize_font_family( string $font_family ): string {
		$font_family = trim( $font_family );

		if ( 1 === preg_match( '/^(var\(--[a-zA-Z0-9_-]+\)|[a-zA-Z0-9 ,\'"_-]{1,200})$/', $font_family ) ) {
			return $font_family;
		}
		return '';
	}

	/**
	 * Return the target of the inquiry button for the given object.
	 *
	 * @param ImmoObject          $immo_object The immo object.
	 * @param array<string,mixed> $attributes  The attributes of the widget.
	 *
	 * @return array{url: string, label: string, type: string}
	 */
	public function get_target( ImmoObject $immo_object, array $attributes = array() ): array {
		// the default label.
		$label = __( 'Send inquiry', 'connector-for-propstack' );

		// get the link target from the settings of this widget.
		$url = $this->sanitize_url( isset( $attributes['url'] ) && is_scalar( $attributes['url'] ) ? (string) $attributes['url'] : '' );

		// use the link target, if set.
		if ( '' !== $url ) {
			$target = array(
				'url'   => $url,
				'label' => $label,
				'type'  => 'custom',
			);
		} else {
			// get the public data of the broker.
			$broker_email = \ConnectorForPropstack\Propstack\Templates::get_instance()->get_broker_public_email( $immo_object );
			$broker_phone = \ConnectorForPropstack\Propstack\Templates::get_instance()->get_broker_public_phone( $immo_object );

			// get the email of the website.
			$admin_email = get_option( 'admin_email' );
			$admin_email = is_string( $admin_email ) && is_email( $admin_email ) ? $admin_email : '';

			if ( '' !== $broker_email ) {
				$target = array(
					'url'   => $this->get_mailto_url( $broker_email, $immo_object ),
					'label' => $label,
					'type'  => 'broker_email',
				);
			} elseif ( '' !== $this->get_phone_number( $broker_phone ) ) {
				$target = array(
					'url'   => 'tel:' . $this->get_phone_number( $broker_phone ),
					'label' => _x( 'Call', 'inquiry button', 'connector-for-propstack' ),
					'type'  => 'broker_phone',
				);
			} elseif ( '' !== $admin_email ) {
				$target = array(
					'url'   => $this->get_mailto_url( $admin_email, $immo_object ),
					'label' => $label,
					'type'  => 'admin_email',
				);
			} else {
				$target = array(
					'url'   => '',
					'label' => $label,
					'type'  => 'none',
				);
			}
		}

		/**
		 * Filter the target of the inquiry button.
		 *
		 * The type is one of "custom", "broker_email", "broker_phone", "admin_email" or "none".
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array{url: string, label: string, type: string} $target      The target with URL, label and type.
		 * @param ImmoObject                                      $immo_object The immo object.
		 * @param array<string,mixed>                             $attributes  The attributes of the widget.
		 */
		return apply_filters( 'cfprop_inquiry_button_target', $target, $immo_object, $attributes );
	}

	/**
	 * Return the mailto-URL with subject and body for an inquiry about the given object.
	 *
	 * @param string     $email       The email address.
	 * @param ImmoObject $immo_object The immo object.
	 *
	 * @return string
	 */
	private function get_mailto_url( string $email, ImmoObject $immo_object ): string {
		// get the title and the Propstack ID of the object.
		$title     = wp_strip_all_tags( get_the_title( $immo_object->get_id() ) );
		$object_id = get_post_meta( $immo_object->get_id(), ( new ObjectId() )->get_name(), true );
		$object_id = is_scalar( $object_id ) ? (string) $object_id : '';

		// create the subject.
		/* translators: %1$s will be replaced by the title of the object. */
		$subject = sprintf( __( 'Inquiry about %1$s', 'connector-for-propstack' ), $title );
		if ( '' !== $object_id ) {
			/* translators: %1$s will be replaced by the title of the object, %2$s by its ID. */
			$subject = sprintf( __( 'Inquiry about %1$s (object ID %2$s)', 'connector-for-propstack' ), $title, $object_id );
		}

		// create the body.
		/* translators: %1$s will be replaced by the URL of the object. */
		$body = sprintf( __( "Hello,\n\nI am interested in this object: %1\$s\n\n", 'connector-for-propstack' ), get_permalink( $immo_object->get_id() ) );

		// return the URL (the email address is encoded against spambots).
		return 'mailto:' . antispambot( $email ) . '?subject=' . rawurlencode( $subject ) . '&body=' . rawurlencode( str_replace( "\n", "\r\n", $body ) );
	}

	/**
	 * Return the phone number cleaned for a tel-URL.
	 *
	 * @param string $phone The phone number.
	 *
	 * @return string
	 */
	private function get_phone_number( string $phone ): string {
		$phone = (string) preg_replace( '/[^0-9+]/', '', $phone );

		// bail if no digits are left.
		if ( ! preg_match( '/[0-9]/', $phone ) ) {
			return '';
		}

		return $phone;
	}

	/**
	 * Return the sanitized link target.
	 *
	 * Allows anchors on the same page (e.g. "#contact"), relative URLs and absolute URLs.
	 *
	 * @param string $url The link target.
	 *
	 * @return string
	 */
	private function sanitize_url( string $url ): string {
		$url = trim( $url );

		// bail if empty.
		if ( '' === $url ) {
			return '';
		}

		// allow anchors on the same page.
		if ( str_starts_with( $url, '#' ) ) {
			$anchor = sanitize_html_class( substr( $url, 1 ) );
			return '' !== $anchor ? '#' . $anchor : '';
		}

		// return the sanitized URL (only with allowed protocols).
		return esc_url_raw( $url );
	}
}

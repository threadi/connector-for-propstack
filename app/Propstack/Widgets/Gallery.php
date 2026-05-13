<?php
/**
 * File to handle the gallery widget.
 *
 * This widget is used to display a gallery of images for a single immo object.
 *
 * @source https://github.com/lokesh/lightbox3
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Widgets;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\Widget_Base;

/**
 * Object to handle the gallery widget.
 */
class Gallery extends Widget_Base {
	/**
	 * The internal name for this object.
	 *
	 * @var string
	 */
	protected string $name = 'widget_gallery';

	/**
	 * Name if the setting tab where the setting field is visible.
	 *
	 * @var string
	 */
	protected string $setting_tab = '';

	/**
	 * Path to the Block object.
	 *
	 * @var string
	 */
	protected string $gutenberg = '\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Field';

	/**
	 * Instance of this object.
	 *
	 * @var ?Gallery
	 */
	private static ?Gallery $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Gallery {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this object.
	 */
	public function init(): void {
		// use hooks.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_css_and_js' ) );

		// use global init.
		parent::init();
	}

	/**
	 * Return the rendered widget.
	 *
	 * @param array<string,mixed> $attributes Attributes to configure the rendering.
	 *
	 * @return string
	 */
	public function render( array $attributes ): string {
		// get the object for this request.
		$immo_object = $this->get_object_by_request();

		// bail if this is not our cpt.
		if ( ! $immo_object instanceof \ConnectorForPropstack\Propstack\ImmoObject ) {
			return '';
		}

		// check the columns.
		$attributes['columns'] = ! empty( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3;

		// check the size.
		$attributes['size'] = ! empty( $attributes['size'] ) ? sanitize_text_field( $attributes['size'] ) : 'thumbnail';

		// get the assigned images.
		$images = get_attached_media( array( 'image/jpeg', 'image/gif', 'image/png' ), $immo_object->get_id() ); // @phpstan-ignore argument.type

		// bail if the list of images is empty.
		if ( empty( $images ) ) {
			return '';
		}

		// get the attachment IDs.
		$ids = implode( ',', wp_list_pluck( $images, 'ID' ) );

		// enable the styles.
		wp_enqueue_style( 'propstack-connector-gallery' );
		wp_enqueue_style( 'propstack-connector-lightbox3' );
		wp_enqueue_script( 'propstack-connector-lightbox3' );

		// add the lightbox marker to the image link.
		add_filter( 'wp_get_attachment_link', array( $this, 'add_lightbox_marker_to_image_link' ) );

		// get the gallery HTML code.
		$gallery = do_shortcode( '[gallery ids="' . $ids . '" columns="' . $attributes['columns'] . '" size="' . $attributes['size'] . '" link="file"]' );

		// remove the filter.
		remove_filter( 'wp_get_attachment_link', array( $this, 'add_lightbox_marker_to_image_link' ) );

		// bail if this is empty.
		if ( empty( $gallery ) ) {
			return '';
		}

		// return the gallery.
		return '<div class="propstack-connector-gallery">' . wp_kses_post( $gallery ) . '</div>';
	}

	/**
	 * Register the CSS and JS for this widget.
	 *
	 * @return void
	 */
	public function register_css_and_js(): void {
		wp_register_style(
			'propstack-connector-gallery',
			Helper::get_plugin_url() . 'css/gallery.css',
			array(),
			Helper::get_file_version( Helper::get_plugin_path() . 'css/gallery.css' ),
		);
		wp_register_style(
			'propstack-connector-lightbox3',
			Helper::get_plugin_url() . 'js/generated/lightbox3.css',
			array(),
			Helper::get_file_version( Helper::get_plugin_path() . 'js/generated/lightbox3.css' ),
		);
		wp_register_script(
			'propstack-connector-lightbox3',
			Helper::get_plugin_url() . 'js/generated/lightbox3.js',
			array(),
			Helper::get_file_version( trailingslashit( Helper::get_plugin_path() ) . 'js/generated/lightbox3.js' ),
			true
		);
	}

	/**
	 * Add the lightbox marker to the image link.
	 *
	 * @param string $link The HTML-Code of the link.
	 *
	 * @return string
	 */
	public function add_lightbox_marker_to_image_link( string $link ): string {
		return str_replace( 'href', 'data-lightbox="gallery" href', $link );
	}

	/**
	 * Enqueue the CSS for this widget.
	 *
	 * @return void
	 */
	public function enqueue_css(): void {
		$this->register_css_and_js();

		// enable the styles.
		wp_enqueue_style( 'propstack-connector-gallery' );
		wp_enqueue_style( 'propstack-connector-lightbox3' );
		wp_enqueue_script( 'propstack-connector-lightbox3' );
	}
}

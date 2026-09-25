<?php
/**
 * File to handle the energy scale widget.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Widgets;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Templates;
use ConnectorForPropstack\Propstack\Fields;
use ConnectorForPropstack\Propstack\Fields\Main\EnergyEfficiencyClass;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use ConnectorForPropstack\Propstack\Widget_Base;

/**
 * Object to handle the energy scale widget.
 */
class Energy_Scale extends Widget_Base {
	/**
	 * The internal name for this object.
	 *
	 * @var string
	 */
	protected string $name = 'widget_energy_scale';

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
	protected string $gutenberg = '\ConnectorForPropstack\PageBuilder\Gutenberg\Blocks\Energy_Scale';

	/**
	 * Instance of this object.
	 *
	 * @var ?Energy_Scale
	 */
	private static ?Energy_Scale $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Energy_Scale {
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
		add_action( 'wp_enqueue_scripts', array( $this, 'register_css' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_css' ) );

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
		// get the object for this request, if no object is given as attribute.
		if ( ! isset( $attributes['object'] ) ) {
			$immo_object = $this->get_object_by_request();

			// bail if no object could be found.
			if ( ! $immo_object instanceof \ConnectorForPropstack\Propstack\ImmoObject ) {
				return '';
			}

			// bail if requested post-type is not ours.
			if ( get_post_type( $immo_object->get_id() ) !== ImmoObject::get_instance()->get_name() ) {
				return '';
			}
		} else {
			$immo_object = $attributes['object'];
		}

		// get the energy efficiency class of this object.
		$energy_efficiency_class = Fields::get_instance()->get_field_value( $immo_object->get_id(), new EnergyEfficiencyClass() );

		// enable the styles.
		wp_enqueue_style( 'cfprop-energy-scale' );

		// return the template with this value.
		ob_start();

		/**
		 * Run custom actions before the output of the archive listing.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param array $attributes List of attributes.
		 */
		do_action( 'cfprop_get_template_before', $attributes );

		// use the template to generate the output.
		include Templates::get_instance()->get_template( 'parts/part-energy-scale.php' );
		$content = ob_get_clean();
		if ( ! $content ) {
			return '';
		}
		return $content;
	}

	/**
	 * Register the CSS for this widget.
	 *
	 * @return void
	 */
	public function register_css(): void {
		wp_register_style(
			'cfprop-energy-scale',
			Helper::get_plugin_url() . 'css/energy-scale.css',
			array(),
			Helper::get_file_version( Helper::get_plugin_path() . 'css/energy-scale.css' ),
		);
	}

	/**
	 * Enqueue the CSS for this widget.
	 *
	 * @return void
	 */
	public function enqueue_css(): void {
		$this->register_css();

		// enable the styles.
		wp_enqueue_style( 'cfprop-energy-scale' );
	}
}

<?php
/**
 * File to handle the compatibility-check for Avada.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin\Compatibilities;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients;
use ConnectorForPropstack\Plugin\Compatibilities_Base;
use ConnectorForPropstack\Plugin\Helper;

/**
 * Object for this check.
 */
class Avada extends Compatibilities_Base {

	/**
	 * Name of this object.
	 *
	 * @var string
	 */
	protected string $name = 'cfprop_compatibility_avada';

	/**
	 * Instance of this object.
	 *
	 * @var ?Avada
	 */
	private static ?Avada $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Avada {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Run the check.
	 *
	 * @return void
	 */
	public function check(): void {
		$transients_obj = Transients::get_instance();
		if ( $this->is_active() ) {
			$transient_obj = $transients_obj->add();
			$transient_obj->set_name( $this->get_name() );
			/* translators: %1$s will be replaced by the URL to the Pro-version-info-page. */
			$transient_obj->set_message( sprintf( __( '<strong>We realized that you are using Avada - very nice!</strong> <a href="%1$s" target="_blank"><i>Connector for Propstack Pro</i> (opens a new window)</a> allows you to design the output of your objects in Avada.', 'connector-for-propstack' ), esc_url( Helper::get_pro_url() ) ) );
			$transient_obj->set_type( 'success' );
			$transient_obj->set_dismissible_days( 30 );
			$transient_obj->save();
		} else {
			$transients_obj->get_transient_by_name( $this->get_name() )->delete();
		}
	}

	/**
	 * Check if Avada and its necessary plugins are active.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		// bail if fusion-builder-plugin is not available.
		if ( ! Helper::is_plugin_active( 'fusion-builder/fusion-builder.php' ) ) {
			return false;
		}

		// otherwise check for the theme.
		$is_avada = false;
		$theme    = wp_get_theme();
		if ( 'Avada' === $theme->get( 'Name' ) ) {
			$is_avada = true;
		}
		if ( $theme->parent() && 'Avada' === $theme->parent()->get( 'Name' ) ) {
			$is_avada = true;
		}

		// return resulting value.
		return $is_avada;
	}
}

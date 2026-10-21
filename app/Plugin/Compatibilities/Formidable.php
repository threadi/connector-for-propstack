<?php
/**
 * File to handle the compatibility-check for Formidable Forms.
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
class Formidable extends Compatibilities_Base {

	/**
	 * Name of this object.
	 *
	 * @var string
	 */
	protected string $name = 'cfprop_compatibility_formidable';

	/**
	 * Human-readable name of the checked product.
	 *
	 * @var string
	 */
	protected string $label = 'Formidable Forms';

	/**
	 * Instance of this object.
	 *
	 * @var ?Formidable
	 */
	private static ?Formidable $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Formidable {
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
			$transient_obj->set_message( sprintf( __( '<strong>We realized that you are using Formidable Forms - very nice!</strong> <a href="%1$s" target="_blank"><i>Connector for Propstack Pro</i> (opens a new window)</a> allows you to submit requests for your objects direct to Propstack with Formidable Forms.', 'connector-for-propstack' ), esc_url( Helper::get_pro_url() ) ) );
			$transient_obj->set_type( 'success' );
			$transient_obj->set_dismissible_days( 30 );
			$transient_obj->save();
		} else {
			$transients_obj->get_transient_by_name( $this->get_name() )->delete();
		}
	}

	/**
	 * Check if the plugin is active.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return Helper::is_plugin_active( 'formidable/formidable.php' );
	}
}

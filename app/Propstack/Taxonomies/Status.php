<?php
/**
 * File to handle our own custom taxonomy "cfprop_object_status".
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Taxonomies;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Taxonomy;

/**
 * Object to handle this custom taxonomy.
 */
class Status extends Taxonomy {
	/**
	 * Define the taxonomy name.
	 *
	 * @var string
	 */
	protected string $name = 'cfprop_object_status';

	/**
	 * The API field name to assign a term of this taxonomy to an object.
	 *
	 * @var string
	 */
	protected string $api_field = 'property_status';

	/**
	 * Instance of this object.
	 *
	 * @var ?Status
	 */
	private static ?Status $instance = null;

	/**
	 * Constructor for this object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() { }

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Status {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hide this taxonomy in any menu.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'cfprop_taxonomy_cfprop_object_status', array( $this, 'hide' ) );
		parent::register();
	}

	/**
	 * Change the setting for this taxonomy to hide it.
	 *
	 * @param array<string,mixed> $taxonomy_array The settings.
	 *
	 * @return array<string,mixed>
	 */
	public function hide( array $taxonomy_array ): array {
		$taxonomy_array['show_ui']           = false;
		$taxonomy_array['show_in_menu']      = false;
		$taxonomy_array['show_admin_column'] = false;
		$taxonomy_array['show_tagcloud']     = false;
		return $taxonomy_array;
	}
}

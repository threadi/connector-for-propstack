<?php
/**
 * File to handle our own custom taxonomy "cfprop_object_marketing_type".
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
class MarketingType extends Taxonomy {
	/**
	 * Define the taxonomy name.
	 *
	 * @var string
	 */
	protected string $name = 'cfprop_object_marketing_type';

	/**
	 * The API field name to assign a term of this taxonomy to an object.
	 *
	 * @var string
	 */
	protected string $api_field = 'marketing_type';

	/**
	 * Instance of this object.
	 *
	 * @var ?MarketingType
	 */
	private static ?MarketingType $instance = null;

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
	public static function get_instance(): MarketingType {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return the labels for this taxonomy.
	 *
	 * @return array<string,string>
	 */
	protected function get_labels(): array {
		return array(
			'name'          => _x( 'Marketing types', 'taxonomy general name', 'connector-for-propstack' ),
			'singular_name' => _x( 'Marketing type', 'taxonomy singular name', 'connector-for-propstack' ),
			'search_items'  => __( 'Search marketing types', 'connector-for-propstack' ),
			'edit_item'     => __( 'Edit marketing type', 'connector-for-propstack' ),
			'update_item'   => __( 'Update marketing type', 'connector-for-propstack' ),
			'menu_name'     => __( 'Marketing types', 'connector-for-propstack' ),
			'back_to_items' => '&larr; ' . __( 'Go to all marketing types', 'connector-for-propstack' ),
		);
	}

	/**
	 * Return the list of default terms for object types from Propstack.
	 *
	 * Format per entry:
	 * - api => the value in the Propstack API.
	 * - slug => the WordPress-internal slug.
	 * - label => the label for show in WordPress.
	 *
	 * @return array<int,mixed>
	 */
	protected function get_default_terms(): array {
		$terms = array(
			array(
				'api'   => 'BUY',
				'slug'  => 'buy',
				'label' => __( 'Buy', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'RENT',
				'slug'  => 'rent',
				'label' => __( 'Rent', 'connector-for-propstack' ),
			),
		);

		/**
		 * Filter the default terms of marketing types.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,mixed> $terms List of terms.
		 */
		return apply_filters( 'cfprop_marketing_type_default_terms', $terms );
	}
}

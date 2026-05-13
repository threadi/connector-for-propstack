<?php
/**
 * File to handle our own custom taxonomy "cfprop_object_category".
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
class Category extends Taxonomy {
	/**
	 * Define the taxonomy name.
	 *
	 * @var string
	 */
	protected string $name = 'cfprop_object_category';

	/**
	 * The API field name to assign a term of this taxonomy to an object.
	 *
	 * @var string
	 */
	protected string $api_field = 'object_type';

	/**
	 * Instance of this object.
	 *
	 * @var ?Category
	 */
	private static ?Category $instance = null;

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
	public static function get_instance(): Category {
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
			'name'          => _x( 'Category', 'taxonomy general name', 'connector-for-propstack' ),
			'singular_name' => _x( 'Category', 'taxonomy singular name', 'connector-for-propstack' ),
			'search_items'  => __( 'Search categories', 'connector-for-propstack' ),
			'edit_item'     => __( 'Edit category', 'connector-for-propstack' ),
			'update_item'   => __( 'Update category', 'connector-for-propstack' ),
			'menu_name'     => __( 'Categories', 'connector-for-propstack' ),
			'back_to_items' => '&larr; ' . __( 'Go to all categories', 'connector-for-propstack' ),
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
				'api'   => 'LIVING',
				'slug'  => 'living',
				'label' => __( 'Living', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'COMMERCIAL',
				'slug'  => 'commercial',
				'label' => __( 'Commercial', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVESTMENT',
				'slug'  => 'investment',
				'label' => __( 'Investment', 'connector-for-propstack' ),
			),
		);

		/**
		 * Filter the default terms of categories.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,mixed> $terms List of terms.
		 */
		return apply_filters( 'cfprop_category_default_terms', $terms );
	}
}

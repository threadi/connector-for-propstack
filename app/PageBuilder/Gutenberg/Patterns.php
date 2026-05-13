<?php
/**
 * File to handle multiple Gutenberg patterns.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\PageBuilder\Gutenberg;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base;

/**
 * Object to handle all Gutenberg patterns of this plugin.
 */
class Patterns {
	/**
	 * The instance of this object.
	 *
	 * @var Patterns|null
	 */
	private static ?Patterns $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
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
	public static function get_instance(): Patterns {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		// register pattern and categories.
		$this->register_category();
		$this->register_patterns();
	}

	/**
	 * Return the in this plugin available pattern.
	 *
	 * Hint:
	 * We deliver patterns as a file but load them here as content and not as template to allow multilingual texts in them.
	 *
	 * @return array<string,mixed>
	 */
	private function get_patterns(): array {
		// prepare the list of patters we support.
		$patterns = array();

		// add a pattern for each object type.
		foreach ( ObjectType::get_instance()->get_object_types_as_objects() as $object_type ) {
			// get the slug.
			$slug = $object_type->get_slug();

			// add it to the list.
			$patterns[ 'connector-for-propstack/' . $slug ] = array(
				'title'       => $object_type->get_label(),
				'description' => $object_type->get_description(),
				'template'    => 'gutenberg/patterns/object-type-fields.php',
				'object-type' => $slug,
			);
		}

		// add a pattern for energy.
		$patterns['connector-for-propstack/energy'] = array(
			'title'       => __( 'Energy Performance Certificate', 'connector-for-propstack' ),
			'description' => __( 'Display information about the energy performance certificate.', 'connector-for-propstack' ),
			'template'    => 'gutenberg/patterns/object-type-energy.html',
		);

		// add a pattern for description.
		$patterns['connector-for-propstack/description'] = array(
			'title'       => __( 'Description', 'connector-for-propstack' ),
			'description' => __( 'Display the description for a single object.', 'connector-for-propstack' ),
			'template'    => 'gutenberg/patterns/object-type-description.html',
		);

		// add a pattern for description.
		$patterns['connector-for-propstack/contact'] = array(
			'title'       => __( 'Contact', 'connector-for-propstack' ),
			'description' => __( 'Display the contact for a single object.', 'connector-for-propstack' ),
			'template'    => 'gutenberg/patterns/object-type-contact.html',
		);

		/**
		 * Filter the list of patterns we provide for the Block Editor.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param array<string,mixed> $patterns List of patterns.
		 */
		return apply_filters( 'cfprop_gutenberg_pattern', $patterns );
	}

	/**
	 * Register our patterns.
	 *
	 * @return void
	 */
	private function register_patterns(): void {
		// bail if a required function is not available.
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		// get WP Filesystem-handler.
		$wp_filesystem = Helper::get_wp_filesystem();

		// loop through the patterns and add them.
		foreach ( $this->get_patterns() as $pattern_name => $pattern ) {
			// bail if no template is given.
			if ( empty( $pattern['template'] ) ) {
				continue;
			}

			// get the path of the template.
			$template_path = \ConnectorForPropstack\Plugin\Templates::get_instance()->get_template( $pattern['template'] );

			// bail if the path does not exist.
			if ( ! $wp_filesystem->exists( $template_path ) ) {
				continue;
			}

			// get the fields of the object-type if one is set.
			$fields = array();
			if ( ! empty( $pattern['object-type'] ) ) {
				// get the object of this object type.
				$object_type = ObjectType::get_instance()->get_object_type_by_slug( $pattern['object-type'] );

				// get the public available fields if the object could be loaded.
				if ( $object_type instanceof Object_Type_Base ) {
					foreach ( $object_type->get_fields() as $field ) {
						// bail if the field is hidden in the frontend.
						if ( $field->hide() || $field->hide_in_frontend() ) {
							continue;
						}

						// add the field to the list.
						$fields[] = $field;
					}
				}
			}

			// get content for this pattern from the template.
			ob_start();
			include $template_path;
			$content = ob_get_clean();
			if ( ! $content ) {
				continue;
			}

			// create arguments.
			$args = array(
				'title'       => $pattern['title'],
				'description' => $pattern['description'],
				'categories'  => array( 'connector-for-propstack' ),
				'keywords'    => array( 'propstack' ),
				'content'     => $content,
				'source'      => 'plugin',
			);

			// add custom args, if set.
			if ( isset( $pattern['args'] ) ) {
				$args = array_merge( $args, $pattern['args'] );
			}

			// register pattern.
			register_block_pattern( $pattern_name, $args );
		}
	}

	/**
	 * Register our own category for patterns.
	 *
	 * @return void
	 */
	private function register_category(): void {
		register_block_pattern_category(
			'connector-for-propstack',
			array( 'label' => __( 'Connector for Propstack', 'connector-for-propstack' ) )
		);
	}
}

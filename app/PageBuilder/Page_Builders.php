<?php
/**
 * File to handle our page builder support.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\PageBuilder;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle page builder support.
 */
class Page_Builders {

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Page_Builders
	 */
	private static ?Page_Builders $instance = null;

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Page_Builders {
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
		// register the known page builder.
		foreach ( $this->get_page_builders_as_objects() as $page_builder_obj ) {
			$page_builder_obj->init();
		}

		// use hooks.
		add_filter( 'cfprop_log_categories', array( $this, 'add_log_category' ) );
	}

	/**
	 * Return the list of page builders.
	 *
	 * @return array<string>
	 */
	public function get_page_builders(): array {
		$list = array(
			'\ConnectorForPropstack\PageBuilder\Gutenberg',
		);

		/**
		 * Filter the possible page builders.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param array<string> $list List of the handler.
		 */
		return apply_filters( 'cfprop_pagebuilder', $list );
	}

	/**
	 * Return the list of page builders as their objects.
	 *
	 * @return array<int,PageBuilder_Base>
	 */
	public function get_page_builders_as_objects(): array {
		// create the list.
		$list = array();

		// register the known pagebuilder.
		foreach ( $this->get_page_builders() as $page_builder ) {
			// get the classname.
			$classname = $page_builder . '::get_instance';

			// bail if it is not callable.
			if ( ! is_callable( $classname ) ) {
				continue;
			}

			// get the object.
			$obj = $classname();

			// bail if an object is not PageBuilder_Base.
			if ( ! $obj instanceof PageBuilder_Base ) {
				continue;
			}

			// add an object to the list.
			$list[] = $obj;
		}

		// return the list.
		return $list;
	}

	/**
	 * Add our own log category.
	 *
	 * @param array<string,string> $categories The log categories.
	 *
	 * @return array<string,string>
	 */
	public function add_log_category( array $categories ): array {
		$categories['pagebuilder'] = __( 'Page Builder', 'connector-for-propstack' );
		return $categories;
	}
}

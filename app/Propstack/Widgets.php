<?php
/**
 * File to handle widgets in Pro-plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle widgets we add with this plugin.
 */
class Widgets {
	/**
	 * Instance of this object.
	 *
	 * @var ?Widgets
	 */
	private static ?Widgets $instance = null;

	/**
	 * Constructor for Init-Handler.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Widgets {
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
		foreach ( $this->get_widgets_as_objects() as $widget ) {
			$widget->init();
		}
	}

	/**
	 * Return the list of supported widgets.
	 *
	 * @param array<int,string> $extensions List of extensions.
	 *
	 * @return array<int,string>
	 */
	public function add_widgets( array $extensions ): array {
		return array_merge( $this->get_widgets(), $extensions );
	}

	/**
	 * Return the list of light plugin widgets.
	 *
	 * @return array<int,string>
	 */
	public function get_widgets(): array {
		$widgets = array(
			'\ConnectorForPropstack\Propstack\Widgets\Archive',
			'\ConnectorForPropstack\Propstack\Widgets\Broker_Field',
			'\ConnectorForPropstack\Propstack\Widgets\Description',
			'\ConnectorForPropstack\Propstack\Widgets\Field',
			'\ConnectorForPropstack\Propstack\Widgets\Filter',
			'\ConnectorForPropstack\Propstack\Widgets\Gallery',
			'\ConnectorForPropstack\Propstack\Widgets\Object_Data',
			'\ConnectorForPropstack\Propstack\Widgets\Single',
		);

		/**
		 * Filter the list of available widgets.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $widgets List of widgets.
		 */
		return apply_filters( 'cfprop_widgets', $widgets );
	}

	/**
	 * Return the list of widgets as an object.
	 *
	 * @return array<int,Widget_Base>
	 */
	public function get_widgets_as_objects(): array {
		// create the list.
		$list = array();

		// add the widgets.
		foreach ( $this->add_widgets( array() ) as $widget_class_name ) {
			// create the classname.
			$classname = $widget_class_name . '::get_instance';

			// bail if the classname is not callable.
			if ( ! is_callable( $classname ) ) {
				continue;
			}

			// get the object.
			$obj = $classname();

			// bail if an object is not the handler base.
			if ( ! $obj instanceof Widget_Base ) {
				continue;
			}

			// add an object to the list.
			$list[] = $obj;
		}

		// return the list.
		return $list;
	}
}

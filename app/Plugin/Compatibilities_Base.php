<?php
/**
 * File as base for each compatibility-check
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Define the base object for compatibilities.
 */
class Compatibilities_Base {
	/**
	 * Name of this plugin.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * URL of this plugin.
	 *
	 * @var string
	 */
	protected string $plugin_url = '';

	/**
	 * Human-readable name of the checked product.
	 *
	 * @var string
	 */
	protected string $label = '';

	/**
	 * Whether the checked product is a page builder or theme with own page builder, which is not natively supported
	 * by this plugin.
	 *
	 * @var bool
	 */
	protected bool $is_page_builder = false;

	/**
	 * Instance of this object.
	 *
	 * @var ?Compatibilities_Base
	 */
	private static ?Compatibilities_Base $instance = null;

	/**
	 * Constructor for this object.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Compatibilities_Base {
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
	public function check(): void {}

	/**
	 * Return the name of the object.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return whether this component is active (true) or not (false).
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return false;
	}

	/**
	 * Return the plugin URL, if set.
	 *
	 * @return string
	 */
	public function get_plugin_url(): string {
		return $this->plugin_url;
	}

	/**
	 * Return the human-readable name of the checked product.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Return whether the checked product is a page builder (or theme with own page builder),
	 * which is not natively supported by this plugin.
	 *
	 * @return bool
	 */
	public function is_page_builder(): bool {
		return $this->is_page_builder;
	}
}

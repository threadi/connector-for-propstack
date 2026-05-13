<?php
/**
 * File for the base object for each page builder support.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\PageBuilder;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object as the base for each page builder.
 */
class PageBuilder_Base {
	/**
	 * True if Page Builder has templates.
	 *
	 * @var bool
	 */
	protected bool $has_templates = false;

	/**
	 * User can enable this extension.
	 *
	 * @var bool
	 */
	protected bool $can_be_enabled_by_user = false;

	/**
	 * Initialize the Page Builder support.
	 *
	 * @return void
	 */
	public function init(): void {}

	/**
	 * Return widgets this page builder supports.
	 *
	 * This means any widgets, block, component ... name it. The returning strings should contain their
	 * class names incl. namespace.
	 *
	 * @return array<string>
	 */
	public function get_widgets(): array {
		return array();
	}

	/**
	 * Return whether this page builder supports templates.
	 *
	 * @return bool
	 */
	public function has_templates(): bool {
		return $this->has_templates;
	}

	/**
	 * Installer for templates this page builder is using.
	 *
	 * @return bool Returns true if the import of the templates has been run successfully.
	 */
	public function install_templates(): bool {
		return false;
	}
}

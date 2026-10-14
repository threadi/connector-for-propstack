<?php
/**
 * File to handle a knowledge center entry.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\KnowledgeCenter;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\KnowledgeCenter_Base;

/**
 * Object for the knowledge center entry "A shortcode is shown as text".
 */
class ShortcodeAsText extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'shortcode_as_text';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 50;

	/**
	 * The group of this entry.
	 *
	 * @var string
	 */
	protected string $group = 'display';

	/**
	 * Return the screens on which this entry is shown in the help.
	 *
	 * Only shown in the settings and the setup.
	 *
	 * @return array<int,string>
	 */
	public function get_screens(): array {
		return array();
	}

	/**
	 * Return the title of the knowledge center entry.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'A shortcode is shown as text', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'Instead of your objects the shortcode itself, e.g. [cfprop_widget_archive], is shown on your website.', 'connector-for-propstack' );
	}

	/**
	 * Return whether this entry should be visible.
	 *
	 * Not visible if a page builder is used, which is only supported by the Pro plugin.
	 *
	 * @return bool
	 */
	public function is_visible(): bool {
		return 'page_builder' !== $this->get_environment();
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text = $this->get_list(
			array(
				__( '<strong>Check the name:</strong> all shortcodes of this plugin start with <code>cfprop_</code>, e.g. <code>[cfprop_widget_archive]</code>.', 'connector-for-propstack' ),
				__( '<strong>Check the quotation marks:</strong> values of parameters must be in straight quotation marks <code>"</code>. Typographic quotation marks like „ or “ are often inserted automatically when copying the shortcode from a document.', 'connector-for-propstack' ),
				__( '<strong>Check where you inserted it:</strong> in the block editor use the block <em>Shortcode</em>. In the blocks <em>Code</em> or <em>Preformatted</em> shortcodes are shown as text. Even better: use the blocks of this plugin instead of the shortcodes.', 'connector-for-propstack' ),
			)
		);
		/* translators: %1$s will be replaced by a link to the documentation. */
		$text .= '<p>' . sprintf( __( 'All shortcodes and their parameters are described in %1$s.', 'connector-for-propstack' ), $this->get_documentation_link( 'shortcodes', __( 'our documentation (opens a new window)', 'connector-for-propstack' ) ) ) . '</p>';

		// return the resulting text.
		return $text;
	}
}

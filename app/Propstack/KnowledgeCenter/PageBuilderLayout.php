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
 * Object for the knowledge center entry "The views of objects do not use my page builder".
 */
class PageBuilderLayout extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'page_builder_layout';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 30;

	/**
	 * The group of this entry.
	 *
	 * @var string
	 */
	protected string $group = 'display';

	/**
	 * Return the title of the knowledge center entry.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'The views of objects do not use my page builder', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'You design your website with a page builder, but the detail view and the list of objects look different and cannot be edited with it.', 'connector-for-propstack' );
	}

	/**
	 * Return whether this entry should be visible.
	 *
	 * Only visible as long as the Pro plugin is not used.
	 *
	 * @return bool
	 */
	public function is_visible(): bool {
		return $this->show_pro_hints();
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text = '<p>' . __( 'This plugin outputs the views of objects with its own templates or with the block templates of block themes. Page builders like Elementor, Divi, Bricks, Avada or WPBakery are not supported by this plugin.', 'connector-for-propstack' ) . '</p>';

		// name the detected page builders.
		$page_builders = $this->get_active_unsupported_page_builders();
		if ( ! empty( $page_builders ) ) {
			/* translators: %1$s will be replaced by the names of page builders. */
			$text .= '<p>' . sprintf( __( 'We detected on your website: %1$s.', 'connector-for-propstack' ), '<strong>' . esc_html( implode( ', ', $page_builders ) ) . '</strong>' ) . '</p>';
		}

		/* translators: %1$s will be replaced by a link to the Pro plugin. */
		$text .= '<p>' . sprintf( __( 'With %1$s you design the detail view and the list of objects directly in your page builder. It provides widgets for all data of your objects and ready-made templates as starting point. You also get personal support from us for the setup.', 'connector-for-propstack' ), $this->get_pro_link() ) . '</p>';

		// return the resulting text.
		return $text;
	}
}

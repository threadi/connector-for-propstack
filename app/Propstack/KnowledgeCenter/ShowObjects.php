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
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;

/**
 * Object for the knowledge center entry "How do I show my objects on my website?".
 */
class ShowObjects extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'show_objects';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 10;

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
		return __( 'How do I show my objects on my website?', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'You have imported your objects and want to show them to the visitors of your website.', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text = '';

		// show the URL of the list of objects.
		$archive_url = ImmoObject::get_instance()->get_archive_url();
		if ( ! empty( $archive_url ) && 1 !== absint( get_option( 'propstack_connector_disable_archive_slug' ) ) ) {
			/* translators: %1$s will be replaced by a link. */
			$text .= '<p>' . sprintf( __( 'Your objects are available on your website right after the import: the list of all objects at %1$s and each object on its own detail page.', 'connector-for-propstack' ), '<a href="' . esc_url( $archive_url ) . '" target="_blank">' . esc_html( $archive_url ) . '</a>' ) . '</p>';
		}

		// show the hint depending on the environment.
		switch ( $this->get_environment() ) {
			case 'page_builder':
				/* translators: %1$s will be replaced by the names of page builders, %2$s by a link to the Pro plugin. */
				$text .= '<p>' . sprintf( __( 'You are using %1$s. To place and design your objects with it, you need %2$s. It provides widgets and ready-made templates for your page builder, so you can design the list and the detail view of your objects like the rest of your website. You also get personal support from us.', 'connector-for-propstack' ), '<strong>' . esc_html( implode( ', ', $this->get_active_unsupported_page_builders() ) ) . '</strong>', $this->get_pro_link() ) . '</p>';
				break;

			case 'block_theme':
				$text .= '<p>' . __( 'Your theme is a <strong>block theme</strong>. You can use the blocks of this plugin on every page:', 'connector-for-propstack' ) . '</p>';
				$text .= $this->get_list(
					array(
						__( 'Add the block <em>List of objects</em> (optionally with the block <em>Filter</em>) to show your objects on any page.', 'connector-for-propstack' ),
						__( 'Add the block <em>Single Object</em> to show one specific object.', 'connector-for-propstack' ),
						__( 'Use the block patterns in the category <em>Connector for Propstack</em> as starting point, e.g. a list with filter or the newest objects.', 'connector-for-propstack' ),
					)
				);
				/* translators: %1$s will be replaced by a link to another help entry. */
				$text .= '<p>' . sprintf( __( 'The design of the list and the detail view is controlled by templates in the Site Editor, see %1$s.', 'connector-for-propstack' ), $this->get_entry_link( 'edit_view', __( 'Where do I edit the view of my objects?', 'connector-for-propstack' ) ) ) . '</p>';
				break;

			default:
				$text .= '<p>' . __( 'Your theme is a <strong>classic theme</strong>. You can show your objects on any page:', 'connector-for-propstack' ) . '</p>';
				$text .= $this->get_list(
					array(
						__( 'In the block editor: add the block <em>List of objects</em> (optionally with the block <em>Filter</em>) or the block <em>Single Object</em>. Block patterns are available in the category <em>Connector for Propstack</em>.', 'connector-for-propstack' ),
						/* translators: %1$s will be replaced by a shortcode, %2$s by a link to the documentation. */
						sprintf( __( 'In the classic editor: use the shortcode %1$s for the list of objects. All shortcodes are described in %2$s.', 'connector-for-propstack' ), '<code>[cfprop_widget_archive]</code>', $this->get_documentation_link( 'shortcodes', __( 'our documentation (opens a new window)', 'connector-for-propstack' ) ) ),
					)
				);
				/* translators: %1$s will be replaced by a link to another help entry. */
				$text .= '<p>' . sprintf( __( 'The list and the detail view of objects are output by the templates of this plugin, see %1$s.', 'connector-for-propstack' ), $this->get_entry_link( 'edit_view', __( 'Where do I edit the view of my objects?', 'connector-for-propstack' ) ) ) . '</p>';
				break;
		}

		// return the resulting text.
		return $text;
	}
}

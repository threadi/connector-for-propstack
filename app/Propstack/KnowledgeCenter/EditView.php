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
 * Object for the knowledge center entry "Where do I edit the view of my objects?".
 */
class EditView extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'edit_view';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 20;

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
		return __( 'Where do I edit the view of my objects?', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'You want to change how the detail view of an object or the list of objects looks on your website.', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text = '<p>' . __( 'The detail view of an object and the list of objects are output by templates. Which templates are used depends on your theme.', 'connector-for-propstack' ) . '</p>';

		// show the hint depending on the environment.
		switch ( $this->get_environment() ) {
			case 'page_builder':
				/* translators: %1$s will be replaced by the names of page builders, %2$s by a link to the Pro plugin. */
				$text .= '<p>' . sprintf( __( 'You are using %1$s. Designing the views of objects with your page builder is possible with %2$s. Until then the templates of this plugin are used for the detail view and the list of objects.', 'connector-for-propstack' ), '<strong>' . esc_html( implode( ', ', $this->get_active_unsupported_page_builders() ) ) . '</strong>', $this->get_pro_link() ) . '</p>';
				break;

			case 'block_theme':
				$text .= '<p>' . __( 'Your theme is a <strong>block theme</strong>. The views are block templates, which you can edit in the Site Editor:', 'connector-for-propstack' ) . '</p>';
				$text .= $this->get_list(
					array(
						/* translators: %1$s will be replaced by a URL. */
						sprintf( __( 'Open <a href="%1$s">Appearance > Editor > Templates</a> and choose <em>Single object</em> or <em>List of objects</em>.', 'connector-for-propstack' ), esc_url( admin_url( 'site-editor.php?postType=wp_template' ) ) ),
						__( 'The blocks of this plugin are in the category <em>Connector for Propstack</em>. The block <em>Field</em> shows any field of an object.', 'connector-for-propstack' ),
						__( 'Your changes are saved as customized template. You can reset it anytime in the Site Editor via <em>Reset</em> in the actions of the template, then the original template of this plugin is used again.', 'connector-for-propstack' ),
					)
				);
				break;

			default:
				$text .= '<p>' . __( 'Your theme is a <strong>classic theme</strong>. The views are output by the PHP templates of this plugin:', 'connector-for-propstack' ) . '</p>';
				$text .= $this->get_list(
					array(
						/* translators: %1$s will be replaced by a link to the settings. */
						sprintf( __( 'Choose which fields are shown for each object type under %1$s.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Settings > Fields', 'connector-for-propstack' ), 'propstack_connector_fields' ) ),
						/* translators: %1$s will be replaced by a link to the settings. */
						sprintf( __( 'Choose the filters above the list of objects under %1$s.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Settings > Filters', 'connector-for-propstack' ), 'propstack_connector_filters' ) ),
						/* translators: %1$s and %2$s will be replaced by folder names. */
						sprintf( __( 'Developers can copy the templates from the folder %1$s of this plugin to the folder %2$s in the child theme and change them there.', 'connector-for-propstack' ), '<code>templates</code>', '<code>connector-for-propstack</code>' ),
					)
				);
				if ( $this->show_pro_hints() ) {
					/* translators: %1$s will be replaced by a link to the Pro plugin. */
					$text .= '<p>' . sprintf( __( 'If you want to design the views with a page builder like Elementor, Divi or Bricks, you need %1$s.', 'connector-for-propstack' ), $this->get_pro_link() ) . '</p>';
				}
				break;
		}

		// add the hint for the admin bar.
		if ( 1 === absint( get_option( 'propstack_connector_show_frontend_hints', 1 ) ) ) {
			$text .= '<p>' . __( '<strong>Tip:</strong> Open an object or the list of objects on your website while you are logged in. The entry <em>Edit object view</em> in the admin bar shows which template is used and leads you to it.', 'connector-for-propstack' ) . '</p>';
		} else {
			/* translators: %1$s will be replaced by a link to the settings. */
			$text .= '<p>' . sprintf( __( '<strong>Tip:</strong> Enable <em>Show hints in frontend</em> under %1$s. Then the admin bar on your website shows which template is used for objects and leads you to it.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Advanced settings > Miscellaneous', 'connector-for-propstack' ), 'propstack_connector_advanced', 'propstack_connector_advanced_settings' ) ) . '</p>';
		}

		// return the resulting text.
		return $text;
	}
}

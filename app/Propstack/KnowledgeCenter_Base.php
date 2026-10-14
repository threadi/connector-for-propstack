<?php
/**
 * File to handle basic functions for each knowledge center entry.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Compatibilities;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Settings;

/**
 * Base object for each knowledge center entry.
 */
class KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * The position of this entry in the list of help tabs (lower values first).
	 *
	 * @var int
	 */
	protected int $priority = 100;

	/**
	 * The group of this entry, see KnowledgeCenter::get_groups().
	 *
	 * Each group is a tab in the help, which contains its entries.
	 *
	 * @var string
	 */
	protected string $group = 'support';

	/**
	 * Return the internal name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the position of this entry in the list of help tabs.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return $this->priority;
	}

	/**
	 * Return the group of this entry.
	 *
	 * @return string
	 */
	public function get_group(): string {
		return $this->group;
	}

	/**
	 * Return the ID of the help tab, which contains this entry (the tab of its group).
	 *
	 * @return string
	 */
	public function get_help_tab_id(): string {
		return \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() . '-group-' . $this->get_group();
	}

	/**
	 * Return the HTML-ID of this entry in the help.
	 *
	 * @return string
	 */
	public function get_html_id(): string {
		return 'cfprop-help-' . $this->get_name();
	}

	/**
	 * Return the anchor of this entry in the help.
	 *
	 * Links with this anchor and the class "cfprop-open-help" open the WordPress help on the actual page
	 * and show this entry.
	 *
	 * @return string
	 */
	public function get_help_tab_anchor(): string {
		return '#' . $this->get_html_id();
	}

	/**
	 * Return the title of the knowledge center entry.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return '';
	}

	/**
	 * Return the list of keywords for this knowledge center entry.
	 *
	 * The keywords are compared with error messages from the Propstack API. If one matches, the text of this entry
	 * is shown with the error. Entries without keywords are only available in the help.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords(): array {
		return array();
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * Defaults to the keywords.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return implode( '<br>', $this->get_keywords() );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		return '';
	}

	/**
	 * Return the text of this entry, which could be changed by other plugins (e.g. the Pro plugin).
	 *
	 * @return string
	 */
	public function get_content(): string {
		$text = $this->get_text();

		/**
		 * Filter the text of a knowledge center entry.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param string               $text  The text.
		 * @param KnowledgeCenter_Base $entry The knowledge center entry.
		 */
		return (string) apply_filters( 'cfprop_knowledge_center_entry_text', $text, $this );
	}

	/**
	 * Return the screens on which this entry is shown in the help, additionally to the settings and the setup of this plugin.
	 *
	 * Possible values:
	 * - "objects" => the list and edit pages of objects in the backend.
	 *
	 * The settings and the setup of this plugin always show all entries, so links between entries always work there.
	 *
	 * @return array<int,string>
	 */
	public function get_screens(): array {
		return array( 'objects' );
	}

	/**
	 * Return whether this entry should be visible.
	 *
	 * @return bool
	 */
	public function is_visible(): bool {
		return true;
	}

	/**
	 * Return a link which opens this entry.
	 *
	 * On pages with the help of this plugin the entry is opened directly, on all other pages the link
	 * leads to the settings of this plugin and opens the entry there.
	 *
	 * @param string $label The label of the link (optional, defaults to the title of this entry).
	 *
	 * @return string
	 */
	public function get_link( string $label = '' ): string {
		if ( empty( $label ) ) {
			$label = $this->get_title();
		}
		return '<a href="' . esc_url( $this->get_url() ) . '" class="cfprop-open-help" data-help-entry="' . esc_attr( $this->get_name() ) . '">' . esc_html( $label ) . '</a>';
	}

	/**
	 * Return the URL which opens this entry in the help of the settings of this plugin.
	 *
	 * @return string
	 */
	public function get_url(): string {
		// use the setup page, if the setup has not been completed (the settings are not available then).
		$url = \ConnectorForPropstack\Plugin\Setup::get_instance()->is_completed() ? Settings::get_instance()->get_url() : admin_url( 'options-general.php?page=connector-for-propstack-setup' );

		// add the entry to open.
		return add_query_arg( array( 'cfprop_help' => $this->get_name() ), $url );
	}

	/**
	 * Return a link to a tab in the settings of this plugin.
	 *
	 * @param string $label  The label of the link.
	 * @param string $tab    The tab.
	 * @param string $subtab The sub-tab (optional).
	 *
	 * @return string
	 */
	protected function get_settings_link( string $label, string $tab, string $subtab = '' ): string {
		return '<a href="' . esc_url( Settings::get_instance()->get_url( $tab, $subtab ) ) . '">' . esc_html( $label ) . '</a>';
	}

	/**
	 * Return a link to another knowledge center entry.
	 *
	 * @param string $name  The internal name of the entry.
	 * @param string $label The label (optional).
	 *
	 * @return string
	 */
	protected function get_entry_link( string $name, string $label = '' ): string {
		$entry = KnowledgeCenter::get_instance()->get_entry_by_name( $name );
		if ( ! $entry ) {
			return esc_html( $label );
		}
		return $entry->get_link( $label );
	}

	/**
	 * Return whether hints for the Pro plugin should be shown.
	 *
	 * @return bool
	 */
	protected function show_pro_hints(): bool {
		return Helper::show_pro_hints();
	}

	/**
	 * Return a link to the Pro plugin.
	 *
	 * @return string
	 */
	protected function get_pro_link(): string {
		return '<a href="' . esc_url( Helper::get_pro_url() ) . '" target="_blank">' . esc_html__( 'Connector for Propstack Pro (opens a new window)', 'connector-for-propstack' ) . '</a>';
	}

	/**
	 * Return the labels of active page builders and themes, which are not natively supported by this plugin.
	 *
	 * @return array<int,string>
	 */
	protected function get_active_unsupported_page_builders(): array {
		return Compatibilities::get_instance()->get_active_page_builder_labels();
	}

	/**
	 * Return the environment for the output of objects on the website.
	 *
	 * Possible values:
	 * - "page_builder" => a page builder (or theme with own page builder) is active, which is not natively supported.
	 * - "block_theme" => a block theme is used.
	 * - "classic_theme" => a classic theme is used.
	 *
	 * Page builders are only recognized if hints for the Pro plugin are shown, as the Pro plugin supports them.
	 *
	 * @return string
	 */
	protected function get_environment(): string {
		if ( $this->show_pro_hints() && ! empty( $this->get_active_unsupported_page_builders() ) ) {
			return 'page_builder';
		}
		if ( Helper::theme_is_fse_theme() ) {
			return 'block_theme';
		}
		return 'classic_theme';
	}

	/**
	 * Return the link to a documentation file of this plugin on GitHub.
	 *
	 * Uses the German version of the file for German users.
	 *
	 * @param string $file  The file name without extension, e.g. "shortcodes".
	 * @param string $label The label of the link.
	 *
	 * @return string
	 */
	protected function get_documentation_link( string $file, string $label ): string {
		$suffix = str_starts_with( get_user_locale(), 'de' ) ? '_de' : '';
		return '<a href="' . esc_url( Helper::get_github_documentation_link() . '/' . $file . $suffix . '.md' ) . '" target="_blank">' . esc_html( $label ) . '</a>';
	}

	/**
	 * Return a list as HTML.
	 *
	 * @param array<int,string> $items The entries of the list (could contain HTML).
	 *
	 * @return string
	 */
	protected function get_list( array $items ): string {
		return '<ul><li>' . implode( '</li><li>', $items ) . '</li></ul>';
	}
}

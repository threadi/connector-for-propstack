<?php
/**
 * File for handling the knowledge center with help texts for different situations the user could have with this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use WP_Screen;

/**
 * Object to handle the knowledge center.
 */
class KnowledgeCenter {
	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?KnowledgeCenter
	 */
	private static ?KnowledgeCenter $instance = null;

	/**
	 * The cached list of entries as objects.
	 *
	 * @var array<int,KnowledgeCenter_Base>|null
	 */
	private ?array $entries = null;

	/**
	 * The name of the actual screen for the help ("settings", "setup" or "objects").
	 *
	 * @var string
	 */
	private string $screen_name = 'settings';

	/**
	 * Constructor, not used as this a Singleton object.
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
	public static function get_instance(): KnowledgeCenter {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Extend the help tab with our knowledge center entries.
	 *
	 * @return void
	 */
	public function init(): void {
		// use hooks.
		add_action( 'current_screen', array( $this, 'add_help' ) );

		// add tabs in help.
		add_filter( 'cfprop_help_tabs', array( $this, 'add_entries' ) );
		add_filter( 'cfprop_help_tabs', array( $this, 'add_documentation_help' ), 20 );
	}

	/**
	 * Return the list of entries in the knowledge center.
	 *
	 * @return array<int,string>
	 */
	public function get_entries(): array {
		$fields = array(
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\ShowObjects',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\EditView',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\PageBuilderLayout',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\NotFound',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\ShortcodeAsText',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\InsufficientPermissions',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\ObjectNotImported',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\ImportTimeout',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\MissingImages',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\ApiV2Data',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\AutomaticImport',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\LimitedObjects',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\HiddenAddress',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\SoldObjects',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\Languages',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\CheckObject',
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\Support',
		);

		/**
		 * Filter the list of available knowledge center entries.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $fields List of field categories.
		 */
		return apply_filters( 'cfprop_knowledge_center_entries', $fields );
	}

	/**
	 * Return the list of entries as objects.
	 *
	 * @return array<int,KnowledgeCenter_Base>
	 */
	public function get_entries_as_objects(): array {
		// return the cached list.
		if ( ! is_null( $this->entries ) ) {
			return $this->entries;
		}

		// prepare the list.
		$list = array();

		// add them to the list.
		foreach ( $this->get_entries() as $field_class_name ) {
			// bail if class does not exist.
			if ( ! class_exists( $field_class_name ) ) {
				continue;
			}

			// get the object.
			$obj = new $field_class_name();

			// bail if the object is not an instance of type "KnowledgeCenter_Base".
			if ( ! $obj instanceof KnowledgeCenter_Base ) {
				continue;
			}

			// add it to the list.
			$list[] = $obj;
		}

		// sort the list by the priority of the entries.
		usort(
			$list,
			function ( KnowledgeCenter_Base $a, KnowledgeCenter_Base $b ) {
				return $a->get_priority() <=> $b->get_priority();
			}
		);

		// cache and return the resulting list.
		$this->entries = $list;
		return $list;
	}

	/**
	 * Return the visible entries for the given screen.
	 *
	 * @param string $screen_name The name of the screen ("settings", "setup" or "objects").
	 *
	 * @return array<int,KnowledgeCenter_Base>
	 */
	private function get_visible_entries( string $screen_name ): array {
		$list = array();
		foreach ( $this->get_entries_as_objects() as $entry ) {
			// the settings and the setup show all entries, other screens only the entries which are configured for them.
			if ( ! in_array( $screen_name, array( 'settings', 'setup' ), true ) && ! in_array( $screen_name, $entry->get_screens(), true ) ) {
				continue;
			}

			$visible = $entry->is_visible();
			/**
			 * Filter whether a knowledge center entry is visible in the help.
			 *
			 * @since 2.1.0 Available since 2.1.0.
			 * @param bool                 $visible True if the entry is visible.
			 * @param KnowledgeCenter_Base $entry   The entry.
			 * @param string               $screen_name The name of the screen ("settings", "setup" or "objects").
			 */
			if ( ! apply_filters( 'cfprop_knowledge_center_entry_visible', $visible, $entry, $screen_name ) ) {
				continue;
			}

			$list[] = $entry;
		}
		return $list;
	}

	/**
	 * Return the entry with the given internal name.
	 *
	 * @param string $name The internal name.
	 *
	 * @return false|KnowledgeCenter_Base
	 */
	public function get_entry_by_name( string $name ): false|KnowledgeCenter_Base {
		foreach ( $this->get_entries_as_objects() as $entry ) {
			if ( $name === $entry->get_name() ) {
				return $entry;
			}
		}
		return false;
	}

	/**
	 * Return a link which opens the entry with the given internal name in the help.
	 *
	 * Could be used in log entries, hints and descriptions of settings.
	 *
	 * @param string $name  The internal name of the entry.
	 * @param string $label The label of the link (optional, defaults to the title of the entry).
	 *
	 * @return string
	 */
	public function get_link( string $name, string $label = '' ): string {
		$entry = $this->get_entry_by_name( $name );
		if ( ! $entry ) {
			return esc_html( $label );
		}
		return $entry->get_link( $label );
	}

	/**
	 * Return the entry for a given text.
	 *
	 * @param string $text The text.
	 *
	 * @return false|KnowledgeCenter_Base
	 */
	public function get_entry_by_text( string $text ): false|KnowledgeCenter_Base {
		foreach ( $this->get_entries_as_objects() as $entry ) {
			// return the entry if the text matches.
			if ( in_array( $text, $entry->get_keywords(), true ) ) {
				return $entry;
			}
		}

		// return false if no entry was found.
		return false;
	}

	/**
	 * Add the help box to our own pages with the configured contents.
	 *
	 * @param WP_Screen $screen The screen object.
	 *
	 * @return void
	 */
	public function add_help( WP_Screen $screen ): void {
		// get the name of the actual screen.
		$this->screen_name = '';
		if ( \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() === $screen->post_type ) {
			$this->screen_name = 'objects';
		}
		if ( 'settings_page_connector-for-propstack' === $screen->base ) {
			$this->screen_name = 'settings';
		}
		if ( 'settings_page_connector-for-propstack-setup' === $screen->base ) {
			$this->screen_name = 'setup';
		}

		$allowed = ! empty( $this->screen_name );
		/**
		 * Prevent adding the WordPress-internal help for our plugin.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param bool $allowed True if the help should be visible.
		 * @param WP_Screen $screen The actual visible screen.
		 */
		if ( ! apply_filters( 'cfprop_show_help', $allowed, $screen ) ) {
			return;
		}

		// get the help tabs.
		$help_tabs = $this->get_help_tabs();

		// bail if the list is empty.
		if ( empty( $help_tabs ) ) {
			return;
		}

		// add our own help tabs.
		foreach ( $help_tabs as $help_tab ) {
			$screen->add_help_tab( $help_tab );
		}

		// add the sidebar.
		$this->add_sidebar( $screen );
	}

	/**
	 * Return the list of help tabs.
	 *
	 * @return array<string,mixed>
	 */
	private function get_help_tabs(): array {
		$list = array();

		/**
		 * Filter the list of help tabs with its contents.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<string,mixed> $list List of help tabs.
		 */
		return apply_filters( 'cfprop_help_tabs', $list );
	}

	/**
	 * Add the sidebar with its content.
	 *
	 * @param WP_Screen $screen The screen object.
	 *
	 * @return void
	 */
	private function add_sidebar( WP_Screen $screen ): void {
		// get content for sidebar.
		$sidebar_content = '<p><strong>' . __( 'Question not answered?', 'connector-for-propstack' ) . '</strong></p><p><a href="' . esc_url( Helper::get_plugin_support_url() ) . '" target="_blank">' . esc_html__( 'Ask in our forum', 'connector-for-propstack' ) . '</a></p>';

		/**
		 * Filter the sidebar content.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param string $sidebar_content The content.
		 */
		$sidebar_content = apply_filters( 'cfprop_help_sidebar_content', $sidebar_content );

		// add the sidebar with the given content.
		$screen->set_help_sidebar( $sidebar_content );
	}

	/**
	 * Add a hint for our documentation..
	 *
	 * @param array<int,array<string,mixed>> $help_list List of help tabs.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function add_documentation_help( array $help_list ): array {
		// collect the content for the help.
		/* translators: %1$s will be replaced by a URL. */
		$content = Helper::get_logo_img() . '<h2>' . __( 'Documentation', 'connector-for-propstack' ) . '</h2><p>' . sprintf( __( 'We provide some documentation for the WordPress plugin <i>Connector for Propstack</i> at <a href="%1$s" target="_blank">GitHub (opens a new window)</a>.', 'connector-for-propstack' ), esc_url( Helper::get_github_documentation_link() ) ) . '</p>';

		// add the help entry.
		$help_list[] = array(
			'id'      => \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() . '-documentation',
			'title'   => __( 'Documentations', 'connector-for-propstack' ),
			'content' => $content,
		);

		// return the resulting list.
		return $help_list;
	}

	/**
	 * Return the groups of entries. Each group is shown as one tab in the help.
	 *
	 * @return array<string,string>
	 */
	public function get_groups(): array {
		$groups = array(
			'display' => __( 'Show on your website', 'connector-for-propstack' ),
			'import'  => __( 'Import', 'connector-for-propstack' ),
			'data'    => __( 'Data and contents', 'connector-for-propstack' ),
			'support' => __( 'Support', 'connector-for-propstack' ),
		);

		/**
		 * Filter the groups of the knowledge center entries. Each group is shown as one tab in the help.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,string> $groups List of groups (internal name => title).
		 */
		return apply_filters( 'cfprop_knowledge_center_groups', $groups );
	}

	/**
	 * Add the knowledge base entries to the help tabs.
	 *
	 * Each group of entries is one tab, which shows its entries as expandable sections.
	 *
	 * @param array<int,array<string,mixed>> $help_list List of help tabs.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function add_entries( array $help_list ): array {
		// get the groups.
		$groups = $this->get_groups();

		// bail if no groups are available.
		if ( empty( $groups ) ) {
			return $help_list;
		}

		// sort the visible entries by their groups (entries with an unknown group are added to the last group).
		$entries_by_group = array();
		$last_group       = (string) array_key_last( $groups );
		foreach ( $this->get_visible_entries( $this->screen_name ) as $entry ) {
			$group_name                        = isset( $groups[ $entry->get_group() ] ) ? $entry->get_group() : $last_group;
			$entries_by_group[ $group_name ][] = $entry;
		}

		// add a tab for each group with entries.
		foreach ( $groups as $group_name => $group_title ) {
			// bail if this group has no entries.
			if ( empty( $entries_by_group[ $group_name ] ) ) {
				continue;
			}

			// create the content.
			$content = Helper::get_logo_img() . '<h2>' . esc_html( $group_title ) . '</h2>';
			foreach ( $entries_by_group[ $group_name ] as $entry ) {
				$content .= $this->get_entry_html( $entry );
			}

			// add the tab.
			$help_list[] = array(
				'id'      => \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() . '-group-' . $group_name,
				'title'   => $group_title,
				'content' => $content,
			);
		}

		// return the resulting list.
		return $help_list;
	}

	/**
	 * Return the HTML of a single entry as expandable section.
	 *
	 * @param KnowledgeCenter_Base $entry The entry.
	 *
	 * @return string
	 */
	private function get_entry_html( KnowledgeCenter_Base $entry ): string {
		$html = '<details class="cfprop-knowledge-center-entry" id="' . esc_attr( $entry->get_html_id() ) . '"><summary>' . esc_html( $entry->get_title() ) . '</summary><div class="cfprop-knowledge-center-entry-content">';

		// add the problem, if one is described.
		$problem = $entry->get_problem();
		if ( ! empty( $problem ) ) {
			$html .= '<p><strong>' . esc_html__( 'The problem:', 'connector-for-propstack' ) . '</strong><br>' . wp_kses_post( $problem ) . '</p><p><strong>' . esc_html__( 'The solution:', 'connector-for-propstack' ) . '</strong></p>';
		}

		// add the text.
		$html .= wp_kses_post( $entry->get_content() );

		// return the resulting HTML.
		return $html . '</div></details>';
	}
}

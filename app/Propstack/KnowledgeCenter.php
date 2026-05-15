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
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\InsufficientPermissions',
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
	private function get_entries_as_objects(): array {
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

		// return the resulting list.
		return $list;
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
		$allowed = \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() === $screen->post_type || 'settings_page_connector-for-propstack' === $screen->base;
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
	 * Add the knowledge base entries to the help tabs.
	 *
	 * @param array<int,array<string,mixed>> $help_list List of help tabs.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function add_entries( array $help_list ): array {
		foreach ( $this->get_entries_as_objects() as $entry ) {
			// create the content.
			$content = Helper::get_logo_img() . '<h2>' . esc_html( $entry->get_title() ) . '</h2><p><strong>' . esc_html__( 'The problem:', 'connector-for-propstack' ) . '</strong><br>' . wp_kses_post( implode( '<br>', $entry->get_keywords() ) ) . '</p><p><strong>' . esc_html__( 'The solution:', 'connector-for-propstack' ) . '</strong> ' . wp_kses_post( $entry->get_text() ) . '</p>';

			// add help for the positions in general.
			$help_list[] = array(
				'id'      => \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() . '-' . $entry->get_name(),
				'title'   => $entry->get_title(),
				'content' => $content,
			);
		}

		// return the resulting list.
		return $help_list;
	}
}

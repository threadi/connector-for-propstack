<?php
/**
 * File to handle a knowledge center entry.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\KnowledgeCenter;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\KnowledgeCenter_Base;

/**
 * Object for the knowledge center entry "How do I get help from the support?".
 */
class Support extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'support';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 200;

	/**
	 * The group of this entry.
	 *
	 * @var string
	 */
	protected string $group = 'support';

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
		return __( 'How do I get help from the support?', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text  = '<p>' . __( 'We can help you faster if you send us the details of the problem:', 'connector-for-propstack' ) . '</p>';
		$text .= $this->get_list(
			array(
				/* translators: %1$s will be replaced by a link to the settings. */
				sprintf( __( '<strong>Enable the debug mode</strong> under %1$s and choose the affected categories. Then repeat the step which causes the problem.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Advanced settings > Plugin', 'connector-for-propstack' ), 'propstack_connector_advanced', 'propstack_connector_advanced_plugin_settings' ) ),
				/* translators: %1$s will be replaced by a link to the log. */
				sprintf( __( '<strong>Export the log</strong> via the button <em>Export as CSV</em> in %1$s.', 'connector-for-propstack' ), $this->get_settings_link( __( 'the log', 'connector-for-propstack' ), 'propstack_connector_logs' ) ),
				__( '<strong>Describe the problem</strong> with the steps to reproduce it and, if possible, the Propstack ID of an affected object. Do not send your API key.', 'connector-for-propstack' ),
			)
		);
		/* translators: %1$s will be replaced by a URL. */
		$text .= '<p>' . sprintf( __( 'Ask your question in <a href="%1$s" target="_blank">our forum (opens a new window)</a>.', 'connector-for-propstack' ), esc_url( Helper::get_plugin_support_url() ) );
		if ( $this->show_pro_hints() ) {
			/* translators: %1$s will be replaced by a link to the Pro plugin. */
			$text .= ' ' . sprintf( __( 'With %1$s you get personal support directly from the developers.', 'connector-for-propstack' ), $this->get_pro_link() );
		}
		$text .= '</p>';
		$text .= '<p>' . __( 'Disable the debug mode again after solving the problem.', 'connector-for-propstack' ) . '</p>';

		// return the resulting text.
		return $text;
	}
}

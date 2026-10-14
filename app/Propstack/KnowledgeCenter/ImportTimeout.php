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
 * Object for the knowledge center entry "The import stops or ends with a timeout".
 */
class ImportTimeout extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'import_timeout';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 70;

	/**
	 * The group of this entry.
	 *
	 * @var string
	 */
	protected string $group = 'import';

	/**
	 * Return the title of the knowledge center entry.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'The import stops or ends with a timeout', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'The import does not finish, reports a timeout (e.g. "Gateway Timeout") or the log reports that a previous run did not end properly.', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text  = '<p>' . __( 'The import runs in several steps. If a step takes too long or needs too much memory, the server stops it. The plugin then continues the import automatically with the next run and skips an object which aborted the previous run.', 'connector-for-propstack' ) . '</p>';
		$text .= '<p>' . __( 'If this happens often:', 'connector-for-propstack' ) . '</p>';
		$text .= $this->get_list(
			array(
				/* translators: %1$s will be replaced by a link to the settings. */
				sprintf( __( 'Reduce the <em>Limit for objects</em> under %1$s and the <em>Limit for import of images</em> under %2$s. Smaller steps take more runs, but each run ends in time.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Advanced settings > For objects', 'connector-for-propstack' ), 'propstack_connector_advanced', 'propstack_connector_advanced_objects' ), $this->get_settings_link( __( 'Advanced settings > For images', 'connector-for-propstack' ), 'propstack_connector_advanced', 'propstack_connector_advanced_images' ) ),
				/* translators: %1$s will be replaced by a link to the settings. */
				sprintf( __( 'Increase the <em>Default timeout</em> for the connection to Propstack under %1$s, if the log reports that Propstack did not answer in time.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Advanced settings > Miscellaneous', 'connector-for-propstack' ), 'propstack_connector_advanced', 'propstack_connector_advanced_settings' ) ),
				/* translators: %1$s will be replaced by a link to the log. */
				sprintf( __( 'Check %1$s for a fatal error before the abort, e.g. an exhausted memory limit. Your hosting provider can increase the PHP settings <code>memory_limit</code> and <code>max_execution_time</code>.', 'connector-for-propstack' ), $this->get_settings_link( __( 'the log', 'connector-for-propstack' ), 'propstack_connector_logs' ) ),
				/* translators: %1$s will be replaced by a command. */
				sprintf( __( 'If you have access to WP-CLI: the command %1$s runs the import without the time limits of the web server.', 'connector-for-propstack' ), '<code>wp cfprop import_objects</code>' ),
			)
		);

		// return the resulting text.
		return $text;
	}
}

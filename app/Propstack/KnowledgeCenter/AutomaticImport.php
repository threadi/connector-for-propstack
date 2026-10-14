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
 * Object for the knowledge center entry "The automatic import does not run".
 */
class AutomaticImport extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'automatic_import';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 100;

	/**
	 * The group of this entry.
	 *
	 * @var string
	 */
	protected string $group = 'import';

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
		return __( 'The automatic import does not run', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'Changes in Propstack do not appear on your website, although the automatic import is enabled.', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		/* translators: %1$s will be replaced by a link to the settings. */
		$text  = '<p>' . sprintf( __( 'The automatic import is enabled under %1$s, where you also choose its interval. It uses the scheduler of WordPress (WP-Cron).', 'connector-for-propstack' ), $this->get_settings_link( __( 'Imports > Objects', 'connector-for-propstack' ), 'propstack_connector_import', 'propstack_connector_objects_import' ) ) . '</p>';
		$text .= $this->get_list(
			array(
				__( '<strong>WP-Cron needs visitors:</strong> WordPress runs scheduled tasks only when someone visits your website. On websites with few visitors the import starts later than planned.', 'connector-for-propstack' ),
				__( '<strong>WP-Cron could be disabled:</strong> if the constant <code>DISABLE_WP_CRON</code> is set in the <code>wp-config.php</code>, a cron job on the server must call WordPress regularly. Ask your hosting provider for it.', 'connector-for-propstack' ),
				/* translators: %1$s will be replaced by a link to the settings. */
				sprintf( __( '<strong>Use the check in the frontend:</strong> as a fallback the plugin can check its schedules on every request, see <em>Check for schedules in frontend</em> under %1$s. This could slow down your website.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Advanced settings > Miscellaneous', 'connector-for-propstack' ), 'propstack_connector_advanced', 'propstack_connector_advanced_settings' ) ),
				/* translators: %1$s will be replaced by a command. */
				sprintf( __( '<strong>Use WP-CLI:</strong> a cron job on the server can also run the import with the command %1$s.', 'connector-for-propstack' ), '<code>wp cfprop import_objects</code>' ),
			)
		);

		// return the resulting text.
		return $text;
	}
}

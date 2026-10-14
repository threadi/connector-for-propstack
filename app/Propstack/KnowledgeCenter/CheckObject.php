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
 * Object for the knowledge center entry "Is a specific object delivered by Propstack?".
 */
class CheckObject extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'check_object';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 150;

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
		return __( 'Is a specific object delivered by Propstack?', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text  = '<p>' . __( 'If an object is missing and the log does not name a reason, Propstack probably does not deliver it via the API. You can check this without running an import:', 'connector-for-propstack' ) . '</p>';
		$text .= $this->get_list(
			array(
				/* translators: %1$s will be replaced by a command. */
				sprintf( __( 'With WP-CLI run %1$s with the Propstack ID of the object. The command shows whether Propstack delivers the object and whether one of your restrictions would prevent its import.', 'connector-for-propstack' ), '<code>wp cfprop check_object &lt;ID&gt;</code>' ),
				/* translators: %1$s and %2$s will be replaced by parameters. */
				sprintf( __( 'With %1$s or %2$s you can search by another ID of the object.', 'connector-for-propstack' ), '<code>--field=unit_id</code>', '<code>--field=exposee_id</code>' ),
			)
		);
		/* translators: %1$s will be replaced by a link to the documentation. */
		$text .= '<p>' . sprintf( __( 'All commands are described in %1$s. If Propstack does not deliver the object, check its settings in Propstack or contact the Propstack support.', 'connector-for-propstack' ), $this->get_documentation_link( 'cli', __( 'our documentation (opens a new window)', 'connector-for-propstack' ) ) ) . '</p>';

		// return the resulting text.
		return $text;
	}
}

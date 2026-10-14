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
 * Object for the knowledge center entry "Object pages show a 404 error".
 */
class NotFound extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'not_found';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 40;

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
		return __( 'Object pages show a 404 error', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'The detail view of an object or the list of objects shows "Page not found" (error 404).', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		return $this->get_list(
			array(
				/* translators: %1$s will be replaced by a URL. */
				sprintf( __( '<strong>Refresh the permalinks:</strong> open <a href="%1$s">Settings > Permalinks</a> and click on <em>Save Changes</em> without changing anything. This is often necessary after the first import, after changing the slugs or after moving the website.', 'connector-for-propstack' ), esc_url( admin_url( 'options-permalink.php' ) ) ),
				/* translators: %1$s will be replaced by a link to the settings. */
				sprintf( __( '<strong>Check the views:</strong> the detail view or the list could be disabled under %1$s.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Advanced settings > For objects', 'connector-for-propstack' ), 'propstack_connector_advanced', 'propstack_connector_advanced_objects' ) ),
				/* translators: %1$s will be replaced by a link to another help entry. */
				sprintf( __( '<strong>Check whether the object still exists:</strong> objects which are no longer marketed in Propstack are removed with the next import, see %1$s.', 'connector-for-propstack' ), $this->get_entry_link( 'sold_objects', __( 'What happens to sold or archived objects?', 'connector-for-propstack' ) ) ),
			)
		);
	}
}

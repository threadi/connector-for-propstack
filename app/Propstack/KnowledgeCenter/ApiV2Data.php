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
 * Object for the knowledge center entry "With the API v2 data is missing or codes are shown instead of texts".
 */
class ApiV2Data extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'api_v2_data';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 90;

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
		return __( 'With the API v2 data is missing or codes are shown instead of texts', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'Since you use the API v2, some fields are missing, the data of brokers is empty or selection fields show codes (e.g. "central_heating") instead of texts.', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text = $this->get_list(
			array(
				/* translators: %1$s will be replaced by a link to another help entry. */
				sprintf( __( '<strong>Check the permissions of your API key:</strong> the API v2 needs additional permissions for <em>Properties_options</em> (texts of selection fields) and <em>Brokers</em> (data of brokers), see %1$s.', 'connector-for-propstack' ), $this->get_entry_link( 'insufficient_permissions', __( 'Insufficient Permissions for Propstack API', 'connector-for-propstack' ) ) ),
				__( '<strong>Some fields are not available:</strong> Propstack delivers fewer fields via the API v2 than via the API v1. Fields which are not delivered are hidden as long as you use the API v2. If you miss data, please contact the Propstack support.', 'connector-for-propstack' ),
				/* translators: %1$s will be replaced by a link to the settings. */
				sprintf( __( '<strong>Run the import again:</strong> after changing the API version or the permissions, run the import of objects again under %1$s.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Imports > Objects', 'connector-for-propstack' ), 'propstack_connector_import', 'propstack_connector_objects_import' ) ),
			)
		);

		/* translators: %1$s will be replaced by the name of the API version, %2$s by a link to the settings. */
		$text .= '<p><em>' . sprintf( __( 'You are currently using the API %1$s. You can change it under %2$s.', 'connector-for-propstack' ), '<code>' . esc_html( (string) get_option( 'propstack_connector_api_version', 'v1' ) ) . '</code>', $this->get_settings_link( __( 'Settings > API', 'connector-for-propstack' ), 'propstack_connector_basic' ) ) . '</em></p>';

		// return the resulting text.
		return $text;
	}
}

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
 * Object for a single knowledge center entry.
 */
class InsufficientPermissions extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'insufficient_permissions';

	/**
	 * Return the title of the knowledge center entry.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Insufficient Permissions for Propstack API', 'connector-for-propstack' );
	}

	/**
	 * Return the list of keywords for this knowledge center entry.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords(): array {
		return array(
			'API-Key besitzt nicht genügend Rechte',
			'The API key does not have sufficient permissions',
		);
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text  = '<p class="propstack-connector-knowledge-entry"><strong>' . __( 'To resolve this, please access the list of your API keys in Propstack:', 'connector-for-propstack' ) . '</strong>';
		$text .= ' <a href="https://crm.propstack.de/app/admin/api_keys" target="_blank">https://crm.propstack.de/app/admin/api_keys</a>';
		$text .= '<br>' . __( 'Edit the key you are using and grant it at least the following permissions:<br><strong>Objects:</strong> Read<br><strong>Object states:</strong> Read', 'connector-for-propstack' );
		$text .= '<br>' . __( 'Save these settings and then try retrieving the data again.', 'connector-for-propstack' ) . '</p>';
		return $text;
	}
}

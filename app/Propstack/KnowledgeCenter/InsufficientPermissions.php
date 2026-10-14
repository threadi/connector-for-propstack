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
		// get the actual used API version.
		$api_version = (string) get_option( 'propstack_connector_api_version', 'v1' );
		if ( empty( $api_version ) ) {
			$api_version = 'v1';
		}

		$text  = '<p class="cfprop-knowledge-entry"><strong>' . __( 'To resolve this, please access the list of your API keys in Propstack:', 'connector-for-propstack' ) . '</strong>';
		$text .= ' <a href="' . esc_url( Helper::get_propstack_api_page_url() ) . '" target="_blank">' . esc_html( Helper::get_propstack_api_page_url() ) . '</a>';
		$text .= '<br>' . __( 'Edit the key you are using and grant it at least the following permissions. Which permissions are necessary depends on the API version you use in this plugin:', 'connector-for-propstack' );
		$text .= '<br><strong>' . __( 'API v1 and API v2:', 'connector-for-propstack' ) . '</strong> ' . __( 'Objects (Read), Object states (Read)', 'connector-for-propstack' );
		$text .= '<br><strong>' . __( 'Additionally for API v2:', 'connector-for-propstack' ) . '</strong> ' . __( 'Properties_options (Read), Properties_statuses (Read), Brokers (Read)', 'connector-for-propstack' );
		/* translators: %1$s will be replaced by the name of the API version (e.g. "v1"). */
		$text .= '<br><em>' . sprintf( __( 'You are currently using the API %1$s.', 'connector-for-propstack' ), '<code>' . esc_html( $api_version ) . '</code>' ) . '</em>';
		$text .= '<br>' . __( 'Save these settings and then try retrieving the data again.', 'connector-for-propstack' ) . '</p>';
		return $text;
	}
}

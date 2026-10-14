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
 * Object for the knowledge center entry "The address of an object is not shown".
 */
class HiddenAddress extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'hidden_address';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 120;

	/**
	 * The group of this entry.
	 *
	 * @var string
	 */
	protected string $group = 'data';

	/**
	 * Return the title of the knowledge center entry.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'The address of an object is not shown', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'Street, house number or the map position of an object are not shown on your website.', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text  = '<p>' . __( 'There are two reasons for this, both protect the exact location of your objects:', 'connector-for-propstack' ) . '</p>';
		$text .= $this->get_list(
			array(
				__( '<strong>The address is hidden in Propstack:</strong> if the address of an object is marked as hidden in Propstack, this plugin respects it. Street, house number, address and geo coordinates are then not shown for this object. Change the setting for the object in Propstack and run the import again.', 'connector-for-propstack' ),
				/* translators: %1$s will be replaced by a link to the settings. */
				sprintf( __( '<strong>The fields are disabled:</strong> for apartments, houses and garages the field <em>Address</em> is disabled by default. You can enable it and all other fields for each object type under %1$s.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Settings > Fields', 'connector-for-propstack' ), 'propstack_connector_fields' ) ),
			)
		);
		$text .= '<p>' . __( 'Postal code and city are shown in both cases, if they are enabled.', 'connector-for-propstack' ) . '</p>';

		// return the resulting text.
		return $text;
	}
}

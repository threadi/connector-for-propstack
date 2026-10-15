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
 * Object for the knowledge center entry "Custom fields from Propstack".
 */
class CustomFields extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'custom_fields';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 125;

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
		return __( 'Custom fields from Propstack', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'You use custom fields for your objects in Propstack and want to know what happens with them.', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text  = '<p>' . __( 'In Propstack you can add any number of custom fields to your objects. Their values are imported together with the objects.', 'connector-for-propstack' ) . '</p>';
		$text .= $this->get_list(
			array(
				__( '<strong>Check:</strong> the box "Custom fields" on the edit page of an object shows its custom fields with their labels and their values.', 'connector-for-propstack' ),
				/* translators: %1$s will be replaced by a link to the settings. */
				sprintf( __( '<strong>Overview:</strong> all known custom fields with their type and internal name are listed under %1$s.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Settings > Fields > Custom fields', 'connector-for-propstack' ), 'propstack_connector_fields', 'propstack_connector_fields_custom_fields' ) ),
				__( '<strong>Labels:</strong> the labels of the fields are created from their internal names in Propstack. Your API key needs no additional permission for custom fields.', 'connector-for-propstack' ),
				__( '<strong>Changes in Propstack:</strong> if you rename or delete a custom field in Propstack, it is updated or removed during the next import.', 'connector-for-propstack' ),
			)
		);

		// add the hint for the Pro plugin.
		if ( $this->show_pro_hints() ) {
			/* translators: %1$s will be replaced by a link to the Pro plugin. */
			$text .= '<p>' . sprintf( __( 'This plugin does not show custom fields on your website. With %1$s you can choose which custom fields should be shown and use them in the widgets of each supported page builder, formatted according to their type (e.g. dates, numbers with unit, lists or the name of a country).', 'connector-for-propstack' ), $this->get_pro_link() ) . '</p>';
		}

		// return the resulting text.
		return $text;
	}
}

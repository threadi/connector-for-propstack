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
 * Object for the knowledge center entry "In which language are the texts of objects imported?".
 */
class Languages extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'languages';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 140;

	/**
	 * The group of this entry.
	 *
	 * @var string
	 */
	protected string $group = 'data';

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
		return __( 'In which language are the texts of objects imported?', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		/* translators: %1$s will be replaced by a link to the settings. */
		$text  = '<p>' . sprintf( __( 'Propstack provides the texts of objects (e.g. titles, descriptions and the texts of selection fields) in several languages. Choose the language to import under %1$s.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Settings > API', 'connector-for-propstack' ), 'propstack_connector_basic' ) ) . '</p>';
		$text .= $this->get_list(
			array(
				__( 'Only texts which are maintained in Propstack in this language can be imported.', 'connector-for-propstack' ),
				/* translators: %1$s will be replaced by a link to the settings. */
				sprintf( __( 'After changing the language, run the import of objects again under %1$s.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Imports > Objects', 'connector-for-propstack' ), 'propstack_connector_import', 'propstack_connector_objects_import' ) ),
				__( 'If Propstack does not deliver the texts of selection fields in this language, the last known texts are used or the fields show the codes from Propstack. The log reports this.', 'connector-for-propstack' ),
			)
		);

		// return the resulting text.
		return $text;
	}
}

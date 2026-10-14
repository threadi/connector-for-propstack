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
 * Object for the knowledge center entry "Why has an object not been imported?".
 */
class ObjectNotImported extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'object_not_imported';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 60;

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
		return __( 'Why has an object not been imported?', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'An object from Propstack is missing in WordPress after the import.', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		/* translators: %1$s will be replaced by a link to the log. */
		$text  = '<p>' . sprintf( __( 'For each object which has not been imported, the reason is noted in %1$s. Possible reasons are:', 'connector-for-propstack' ), $this->get_settings_link( __( 'the log', 'connector-for-propstack' ), 'propstack_connector_logs' ) ) . '</p>';
		$items = array(
			/* translators: %1$s will be replaced by a link to the settings. */
			sprintf( __( '<strong>A restriction of the import:</strong> under %1$s you can restrict the import to certain states, brokers, marketing types, object types and property types. If nothing is selected, all objects are imported.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Objects > Restrictions', 'connector-for-propstack' ), 'propstack_connector_objects', 'propstack_connector_import_restrictions' ) ),
			__( '<strong>The state of the object:</strong> without a restriction for the states only objects in the state <em>Vermarktung</em> are imported.', 'connector-for-propstack' ),
		);
		if ( $this->show_pro_hints() ) {
			/* translators: %1$s will be replaced by a link to the Pro plugin. */
			$items[] = sprintf( __( '<strong>The object type:</strong> this plugin imports apartments, houses and garages. All other object types like plots, offices or stores are imported by %1$s.', 'connector-for-propstack' ), $this->get_pro_link() );
		}
		/* translators: %1$s will be replaced by a link to another help entry. */
		$items[] = sprintf( __( '<strong>Propstack does not deliver the object:</strong> you can check this, see %1$s.', 'connector-for-propstack' ), $this->get_entry_link( 'check_object', __( 'Is a specific object delivered by Propstack?', 'connector-for-propstack' ) ) );
		$text   .= $this->get_list( $items );

		/* translators: %1$s will be replaced by a link to the settings. */
		$text .= '<p>' . sprintf( __( 'After changing the restrictions run the import again under %1$s.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Imports > Objects', 'connector-for-propstack' ), 'propstack_connector_import', 'propstack_connector_objects_import' ) ) . '</p>';

		// return the resulting text.
		return $text;
	}
}

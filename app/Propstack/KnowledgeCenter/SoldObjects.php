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
 * Object for the knowledge center entry "What happens to sold or archived objects?".
 */
class SoldObjects extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'sold_objects';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 130;

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
		return __( 'What happens to sold or archived objects?', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		/* translators: %1$s will be replaced by a link to the settings. */
		$text  = '<p>' . sprintf( __( 'Objects which are no longer marketed in Propstack, e.g. because they are sold, rented or archived, are removed from your website with the next import. Their images are removed from the media library as well, unless you enable <em>Preserve files</em> under %1$s.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Advanced settings > For objects', 'connector-for-propstack' ), 'propstack_connector_advanced', 'propstack_connector_advanced_objects' ) ) . '</p>';
		$text .= '<p>' . __( 'Links to these objects then end in a "Page not found" (error 404). Consider setting up redirects for their URLs, e.g. to the list of objects, so that existing links and search engine results still lead your visitors to your offers.', 'connector-for-propstack' ) . '</p>';

		// return the resulting text.
		return $text;
	}
}

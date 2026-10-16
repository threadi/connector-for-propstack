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
 * Object for the knowledge center entry "Show objects on a map".
 */
class Maps extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'maps';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 24;

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
		return __( 'Show objects on a map', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'You want to show the location of your objects on a map.', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text  = '<p>' . __( 'Use the widget or the block "Map" to show the location of an object, e.g. on its detail page. The coordinates are delivered by Propstack with each object.', 'connector-for-propstack' ) . '</p>';
		$text .= $this->get_list(
			array(
				/* translators: %1$s will be replaced by a link to the settings. */
				sprintf( __( '<strong>Map service:</strong> choose OpenStreetMap or Google Maps under %1$s. Google Maps needs your own API key from the Google Cloud Console with the "Maps JavaScript API".', 'connector-for-propstack' ), $this->get_settings_link( __( 'Settings > Maps', 'connector-for-propstack' ), 'propstack_connector_maps' ) ),
				__( '<strong>Privacy:</strong> by default the map is loaded only after the visitor clicked on it, as the map service receives the IP address of the visitor. Visitors can allow all maps at once. Mention the map service in your privacy policy.', 'connector-for-propstack' ),
				__( '<strong>Consent tools:</strong> if your consent tool blocks the map service on its own, disable the consent of this plugin. Consent tools can load all maps via the JavaScript event <code>cfprop-maps-consent</code> on the document or via <code>window.cfpropMaps.loadAll()</code>.', 'connector-for-propstack' ),
				__( '<strong>Hidden address:</strong> objects whose address is hidden in Propstack are shown as approximate area (about 1 km) or not at all, as set in the settings. Their exact location is never sent to the browser.', 'connector-for-propstack' ),
				__( '<strong>No map is shown:</strong> the object has no coordinates in Propstack, the field for the coordinates is hidden, or the API key for Google Maps is missing.', 'connector-for-propstack' ),
				__( '<strong>Many visitors:</strong> the tile servers of OpenStreetMap are not meant for websites with many visitors. Enter the URL of a commercial provider or your own tile server in the settings.', 'connector-for-propstack' ),
			)
		);
		if ( $this->show_pro_hints() ) {
			/* translators: %1$s will be replaced by a link. */
			$text .= '<p>' . sprintf( __( 'A map with all objects of the list (with the active filters) and widgets for many page builders are part of %1$s.', 'connector-for-propstack' ), $this->get_pro_link() ) . '</p>';
		}

		// return the resulting text.
		return $text;
	}
}

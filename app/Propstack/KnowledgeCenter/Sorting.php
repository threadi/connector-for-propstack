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
 * Object for the knowledge center entry "Sort the list of objects".
 */
class Sorting extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'sorting';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 22;

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
		return __( 'Sort the list of objects', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'You want your visitors to sort the list of objects, e.g. by price.', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text  = '<p>' . __( 'Above the list of objects your visitors can choose how the list is sorted. The list keeps the active filter and starts again on its first page.', 'connector-for-propstack' ) . '</p>';
		$text .= $this->get_list(
			array(
				/* translators: %1$s will be replaced by a link to the settings. */
				sprintf( __( '<strong>Settings:</strong> enable or disable the sorting and hide sortings under %1$s. The default sorting could be chosen there with Connector for Propstack Pro.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Settings > Sorting', 'connector-for-propstack' ), 'propstack_connector_sortings' ) ),
				__( '<strong>Only useful sortings:</strong> a sorting is offered only if at least half of the objects in the actual list have a value for it. Purchase prices and rents are not comparable: if the list contains objects to buy and to rent, sorting by price shows the objects to buy first (by purchase price), then the objects to rent (by rent).', 'connector-for-propstack' ),
				__( '<strong>Objects without value</strong> are always shown at the end of the list.', 'connector-for-propstack' ),
				__( '<strong>Single lists without sorting:</strong> add the attribute <code>show_sorting="0"</code> to the shortcode <code>[cfprop_widget_archive]</code> to hide the sorting for this list.', 'connector-for-propstack' ),
			)
		);
		if ( $this->show_pro_hints() ) {
			/* translators: %1$s will be replaced by a link. */
			$text .= '<p>' . sprintf( __( 'More sortings like newest objects, living space, rooms, plot area or availability are part of %1$s.', 'connector-for-propstack' ), $this->get_pro_link() ) . '</p>';
		}

		// return the resulting text.
		return $text;
	}
}

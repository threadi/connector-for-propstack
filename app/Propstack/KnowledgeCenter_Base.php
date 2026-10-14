<?php
/**
 * File to handle basic functions for each knowledge center entry.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Base object for each knowledge center entry.
 */
class KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Return the internal name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the ID of the help tab for this entry.
	 *
	 * @return string
	 */
	public function get_help_tab_id(): string {
		return \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() . '-' . $this->get_name();
	}

	/**
	 * Return the anchor which opens the help tab for this entry.
	 *
	 * Links with this anchor and the class "cfprop-open-help" open the WordPress help on the actual page
	 * and show this entry.
	 *
	 * @return string
	 */
	public function get_help_tab_anchor(): string {
		return '#tab-panel-' . $this->get_help_tab_id();
	}

	/**
	 * Return the title of the knowledge center entry.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return '';
	}

	/**
	 * Return the list of keywords for this knowledge center entry.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords(): array {
		return array();
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		return '';
	}
}

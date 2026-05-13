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

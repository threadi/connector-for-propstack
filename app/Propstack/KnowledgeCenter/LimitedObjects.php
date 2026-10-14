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
 * Object for the knowledge center entry "Why are only 10 objects shown or some objects missing?".
 */
class LimitedObjects extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'limited_objects';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 110;

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
		return __( 'Why are only 10 objects shown or some objects missing?', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'The list of objects on your website shows only 10 objects or objects of certain types like plots or offices are missing.', 'connector-for-propstack' );
	}

	/**
	 * Return whether this entry should be visible.
	 *
	 * Only visible as long as the Pro plugin is not used.
	 *
	 * @return bool
	 */
	public function is_visible(): bool {
		return $this->show_pro_hints();
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		$text  = '<p>' . __( 'This plugin is free to use with the following limits:', 'connector-for-propstack' ) . '</p>';
		$text .= $this->get_list(
			array(
				__( 'The list of objects shows up to 10 objects, without pagination.', 'connector-for-propstack' ),
				__( 'Only the object types apartment, house and garage are imported.', 'connector-for-propstack' ),
			)
		);
		/* translators: %1$s will be replaced by a link to the Pro plugin. */
		$text .= '<p>' . sprintf( __( '%1$s shows all of your objects with pagination, imports all object types and adds many more fields, widgets for page builders and personal support.', 'connector-for-propstack' ), $this->get_pro_link() ) . '</p>';

		// return the resulting text.
		return $text;
	}
}

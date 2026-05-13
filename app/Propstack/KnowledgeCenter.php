<?php
/**
 * File for handling the knowledge center with help texts for different situations the user could have with this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle the knowledge center.
 */
class KnowledgeCenter {
	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?KnowledgeCenter
	 */
	private static ?KnowledgeCenter $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): KnowledgeCenter {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return the list of entries in the knowledge center.
	 *
	 * @return array<int,string>
	 */
	public function get_entries(): array {
		$fields = array(
			'\ConnectorForPropstack\Propstack\KnowledgeCenter\InsufficientPermissions',
		);

		/**
		 * Filter the list of available knowledge center entries.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $fields List of field categories.
		 */
		return apply_filters( 'cfprop_knowledge_center_entries', $fields );
	}

	/**
	 * Return the list of entries as objects.
	 *
	 * @return array<int,KnowledgeCenter_Base>
	 */
	private function get_entries_as_objects(): array {
		// prepare the list.
		$list = array();

		// add them to the list.
		foreach ( $this->get_entries() as $field_class_name ) {
			// bail if class does not exist.
			if ( ! class_exists( $field_class_name ) ) {
				continue;
			}

			// get the object.
			$obj = new $field_class_name();

			// bail if the object is not an instance of type "KnowledgeCenter_Base".
			if ( ! $obj instanceof KnowledgeCenter_Base ) {
				continue;
			}

			// add it to the list.
			$list[] = $obj;
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Return the entry for a given text.
	 *
	 * @param string $text The text.
	 *
	 * @return false|KnowledgeCenter_Base
	 */
	public function get_entry_by_text( string $text ): false|KnowledgeCenter_Base {
		foreach ( $this->get_entries_as_objects() as $entry ) {
			// return the entry if the text matches.
			if ( in_array( $text, $entry->get_keywords(), true ) ) {
				return $entry;
			}
		}

		// return false if no entry was found.
		return false;
	}
}

<?php
/**
 * File to handle the base object for each team from our own taxonomies.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Base object for each team from our own taxonomies.
 */
class Term_Base {

	/**
	 * The term ID.
	 *
	 * @var int
	 */
	private int $term_id = 0;

	/**
	 * The slug.
	 *
	 * @var string
	 */
	protected string $slug = '';

	/**
	 * The name.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Return the term ID.
	 *
	 * @return int
	 */
	public function get_term_id(): int {
		return $this->term_id;
	}

	/**
	 * Set the term ID.
	 *
	 * @param int $term_id The term ID.
	 *
	 * @return void
	 */
	public function set_id( int $term_id ): void {
		$this->term_id = $term_id;
	}

	/**
	 * Return the slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Set the slug.
	 *
	 * @param string $slug The slug.
	 *
	 * @return void
	 */
	public function set_slug( string $slug ): void {
		$this->slug = $slug;
	}

	/**
	 * Return the name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Set the name.
	 *
	 * @param string $name The name.
	 *
	 * @return void
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
	}

	/**
	 * Return the term source (a plugin).
	 *
	 * @return string
	 */
	public function get_source(): string {
		return CFPROP_PLUGIN;
	}
}

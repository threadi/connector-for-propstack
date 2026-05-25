<?php
/**
 * File to handle basic cpt-function.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Base Object for each post-type.
 */
class Post_Type {

	/**
	 * Define the post-type name.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		// register this cpt.
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Return the post-type name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the link to manage items of this cpt in the backend.
	 *
	 * @param bool $without_admin_url True if the URL should contain get_admin_url().
	 *
	 * @return string
	 */
	public function get_link( bool $without_admin_url = false ): string {
		return add_query_arg(
			array(
				'post_type' => $this->get_name(),
			),
			( $without_admin_url ? '' : get_admin_url() ) . 'edit.php'
		);
	}

	/**
	 * Return whether this cpt is assigned to a given plugin.
	 *
	 * @param string $plugin_path The plugin path (like __FILE__).
	 *
	 * @return bool
	 */
	public function is_from_plugin( string $plugin_path ): bool {
		return CFPROP_PLUGIN === $plugin_path;
	}

	/**
	 * Return the archive URL of this post-type.
	 *
	 * @return string
	 */
	public function get_archive_url(): string {
		// get the archive URL.
		$url = get_post_type_archive_link( $this->get_name() );
		if ( ! $url ) {
			$url = '';
		}
		return $url;
	}

	/**
	 * Register this custom post-type.
	 *
	 * @return void
	 */
	public function register(): void {}

	/**
	 * Return the list of fields, used as post-meta, for this cpt.
	 *
	 * The list is grouped by categories:
	 * - basic: the basic object data.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_fields(): array {
		return array();
	}
}

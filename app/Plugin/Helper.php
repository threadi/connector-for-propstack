<?php
/**
 * File with general helper tasks for the plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use DateTime;
use WP_Error;
use WP_Filesystem_Base;
use WP_Filesystem_Direct;
use WP_Post;
use WP_Post_Type;
use WP_Rewrite;

/**
 * The helper class itself.
 */
class Helper {
	/**
	 * Create JSON from the given array.
	 *
	 * @param array<string|int,mixed>|WP_Error $source The source array.
	 * @param int                              $flag Flags to use for this JSON, see: https://www.php.net/manual/en/function.json-encode.php.
	 *
	 * @return string
	 */
	public static function get_json( array|WP_Error $source, int $flag = 0 ): string {
		// create JSON.
		$json = wp_json_encode( $source, $flag );

		// bail if creating the JSON failed.
		if ( ! $json ) {
			return '';
		}

		// return the resulting JSON string.
		return $json;
	}

	/**
	 * Return the list of blogs in a multisite-installation.
	 *
	 * @return array<int,mixed>
	 */
	public static function get_blogs(): array {
		// bail if this is not a multisite installation.
		if ( false === is_multisite() ) {
			return array();
		}

		// get the WordPress-own database object.
		global $wpdb;

		// get blogs in this site-network.
		return Db::get_instance()->get_results(
			$wpdb->prepare(
				'
	            SELECT blog_id
	            FROM ' . $wpdb->blogs . "
	            WHERE site_id = %s
	            AND spam = '0'
	            AND deleted = '0'
	            AND archived = '0'
	            ",
				$wpdb->siteid
			)
		);
	}

	/**
	 * Check if WP CLI has been called.
	 *
	 * @return bool
	 */
	public static function is_cli(): bool {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Return the absolute URL to the plugin (already trailed with slash).
	 *
	 * @return string
	 */
	public static function get_plugin_url(): string {
		return trailingslashit( plugin_dir_url( CONNECTOR_FOR_PROPSTACK_PLUGIN ) );
	}

	/**
	 * Return the absolute local filesystem-path (already trailed with slash) to the plugin.
	 *
	 * @return string
	 */
	public static function get_plugin_path(): string {
		return trailingslashit( plugin_dir_path( CONNECTOR_FOR_PROPSTACK_PLUGIN ) );
	}

	/**
	 * Return the language-depending list-slug.
	 *
	 * @return string
	 */
	public static function get_archive_slug(): string {
		$slug = 'objects';
		if ( Languages::get_instance()->is_german_language() ) {
			$slug = 'objekte';
		}

		/**
		 * Change the archive slug.
		 *
		 * @since 1.0.0 Available since first release.
		 *
		 * @param string $slug The archive slug.
		 */
		return apply_filters( 'cfprop_archive_slug', $slug );
	}

	/**
	 * Return the language-depending single-slug.
	 *
	 * @return string
	 */
	public static function get_single_slug(): string {
		$slug = 'object';
		if ( Languages::get_instance()->is_german_language() ) {
			$slug = 'objekt';
		}

		/**
		 * Change the single slug.
		 *
		 * @since 1.0.0 Available since first release.
		 *
		 * @param string $single_slug The archive slug.
		 */
		return apply_filters( 'cfprop_single_slug', $slug );
	}

	/**
	 * Return the logo as img
	 *
	 * @param bool $big_logo True to output the big logo.
	 *
	 * @return string
	 */
	public static function get_logo_img( bool $big_logo = false ): string {
		if ( $big_logo ) {
			return '<img src="' . self::get_plugin_url() . 'gfx/propstack_logo_big.png" alt="Propstack Logo" class="logo">';
		}
		return '<img src="' . self::get_plugin_url() . 'gfx/propstack_logo.png" alt="Propstack Logo" class="logo">';
	}

	/**
	 * Return the name of this plugin.
	 *
	 * @return string
	 */
	public static function get_plugin_name(): string {
		// get the plugin data.
		$plugin_data = get_plugin_data( CONNECTOR_FOR_PROPSTACK_PLUGIN );

		// bail if no 'Name' is in the result.
		if ( empty( $plugin_data['Name'] ) ) {
			return '';
		}

		// return the plugin name.
		return $plugin_data['Name'];
	}

	/**
	 * Return the version of the given file.
	 *
	 * With WP_DEBUG or plugin-debug enabled its @filemtime().
	 * Without this it is the plugin-version.
	 *
	 * @param string $filepath The absolute path to the requested file.
	 *
	 * @return string
	 */
	public static function get_file_version( string $filepath ): string {
		// check for WP_DEBUG.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return (string) filemtime( $filepath );
		}

		// check for own debug.
		if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
			return (string) filemtime( $filepath );
		}

		$plugin_version = CONNECTOR_FOR_PROPSTACK_VERSION;

		/**
		 * Filter the used file version (for JS- and CSS-files, which get enqueued).
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param string $plugin_version The plugin-version.
		 * @param string $filepath The absolute path to the requested file.
		 */
		return apply_filters( 'cfprop_file_version', $plugin_version, $filepath );
	}

	/**
	 * Return whether the current theme is a block-theme.
	 *
	 * @return bool
	 */
	public static function theme_is_fse_theme(): bool {
		if ( function_exists( 'wp_is_block_theme' ) ) {
			return wp_is_block_theme();
		}
		return false;
	}

	/**
	 * Return the plugin support url: the forum on WordPress.org.
	 *
	 * @return string
	 */
	public static function get_plugin_support_url(): string {
		return 'https://wordpress.org/support/plugin/connector-for-propstack/';
	}

	/**
	 * Return the WP Filesystem object.
	 *
	 * @param bool $local Mark with "true" to get the local filesystem object.
	 *
	 * @return WP_Filesystem_Base
	 */
	public static function get_wp_filesystem( bool $local = false ): WP_Filesystem_Base {
		// get WP Filesystem-handler for local files if requested.
		if ( $local ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

			return new WP_Filesystem_Direct( false );
		}

		// get global WP Filesystem handler.
		require_once ABSPATH . '/wp-admin/includes/file.php';
		\WP_Filesystem();
		global $wp_filesystem;

		// bail if "wp_filesystem" is not of "WP_Filesystem_Base".
		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			return new WP_Filesystem_Direct( false );
		}

		// return the local object on any error.
		if ( $wp_filesystem->errors->has_errors() ) {
			// log this event.
			/* translators: a name will replace %1$s. */
			Log::get_instance()->add( sprintf( __( '<strong>Error during loading the required WordPress-own filesystem object!</strong><br>We will now use the local filesystem object and hope it will work.<br><br>Tipps to solve this:<ul><li>Check the following error and speak to your WordPress administrator about it.</li><li>Check your <em>wp-config.php</em> if you have the constant "FS_METHOD" set there. If yes, remove it and check if your WordPress can save media files.</li><li>Ask the support of your hoster for help.</li></ul>Used filesystem mode: <em>%1$s</em><br>The following errors occurred:', 'connector-for-propstack' ), get_filesystem_method() ) . ' <code>' . wp_json_encode( $wp_filesystem->errors ) . '</code>', 'error', 'system' );

			// embed the local directory object.
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

			return new WP_Filesystem_Direct( false );
		}

		// return the requested filesystem object.
		return $wp_filesystem;
	}

	/**
	 * Regex to get html tag attribute value.
	 *
	 * @param string $attribute The attribute.
	 * @param string $tag The tag.
	 * @return string|false
	 */
	public static function get_attribute_value_from_html( string $attribute, string $tag ): string|false {
		// get attribute from html tag.
		$re = '/' . preg_quote( $attribute, null ) . '=([\'"])?((?(1).+?|[^\s>]+))(?(1)\1)/is';
		if ( preg_match( $re, $tag, $match ) ) {
			return urldecode( $match[2] );
		}
		return false;
	}

	/**
	 * Checks if the current request is a WP REST API request.
	 *
	 * Case #1: After WP_REST_Request initialization
	 * Case #2: Support "plain" permalink settings and check if `rest_route` starts with `/`
	 * Case #3: It can happen that "WP_Rewrite" is not yet initialized,
	 *          so do this (wp-settings.php)
	 * Case #4: URL Path begins with wp-json/ (your REST prefix)
	 *          Also supports WP installations in the subfolders
	 *
	 * @returns boolean
	 * @author matzeeable
	 */
	public static function is_rest_request(): bool {
		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) // Case #1.
			|| ( isset( $GLOBALS['wp']->query_vars['rest_route'] ) // (#2)
					&& str_starts_with( $GLOBALS['wp']->query_vars['rest_route'], '/' ) ) ) {
			return true;
		}

		// Case #3.
		global $wp_rewrite;
		if ( is_null( $wp_rewrite ) ) {
			$wp_rewrite = new WP_Rewrite();
		}

		// Case #4.
		$rest_url    = wp_parse_url( trailingslashit( rest_url() ) );
		$current_url = wp_parse_url( add_query_arg( array() ) );
		if ( is_array( $current_url ) && is_array( $rest_url ) && isset( $current_url['path'], $rest_url['path'] ) ) {
			return str_starts_with( $current_url['path'], $rest_url['path'] );
		}
		return false;
	}

	/**
	 * Return all files of the directory recursively.
	 *
	 * @param string $path The path.
	 *
	 * @return array<string>
	 */
	public static function get_files_from_directory( string $path = '.' ): array {
		// get WP_Filesystem as object.
		$wp_filesystem = self::get_wp_filesystem();

		// get the file list.
		$files = $wp_filesystem->dirlist( $path, true, true );

		// bail if no files could be loaded.
		if ( ! $files ) {
			return array();
		}

		// load files recursive in an array and return the resulting list.
		return self::get_files( $files, $path );
	}

	/**
	 * Recursively load files from the given array.
	 *
	 * @param array<string,array<string,mixed>> $files Array of the file we iterate through.
	 * @param string                            $path Absolute path where the files are located.
	 * @param array<string>                     $file_list List of files.
	 *
	 * @return array<string>
	 */
	private static function get_files( array $files, string $path, array $file_list = array() ): array {
		foreach ( $files as $filename => $settings ) {
			if ( 'f' === $settings['type'] ) {
				$file_list[ $filename ] = $path . $filename;
			}
			if ( 'd' === $settings['type'] ) {
				$file_list = self::get_files( $settings['files'], $path . trailingslashit( $filename ), $file_list );
			}
		}

		return $file_list;
	}

	/**
	 * Return the user ID of the author during an object creation depending on the actual login state.
	 *
	 * @return int
	 */
	public static function get_author_during_object_creation(): int {
		// if the user is logged in, use its user ID.
		if ( is_user_logged_in() ) {
			return wp_get_current_user()->ID;
		}

		// otherwise return the fallback.
		return absint( get_option( 'propstack_connector_object_author' ) );
	}

	/**
	 * Format a given date with WP-settings and functions.
	 *
	 * @param string $date The date as YYYY-MM-DD.
	 * @return string
	 */
	public static function get_format_date( string $date ): string {
		$dt = get_date_from_gmt( $date );
		return date_i18n( get_option( 'date_format' ), strtotime( $dt ) );
	}

	/**
	 * Format a given datetime with WP-settings and functions.
	 *
	 * @param string $date The date as YYYY-MM-DD HH:MM:SS.
	 * @return string
	 */
	public static function get_format_date_time( string $date ): string {
		// bail if the given value is empty.
		if ( empty( $date ) ) {
			return '';
		}

		try {
			$dt = new DateTime( $date );
			// return the formatted date.
			return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $dt->getTimestamp() );
		} catch ( \Exception $e ) {
			Log::get_instance()->add( __( 'Error during date formatting:', 'connector-for-propstack' ) . ' <code>' . $e->getMessage() . '</code>', 'info', 'system' );

			// fallback on any error with DateTime.
			$dt = get_date_from_gmt( $date );

			// return the formatted date.
			return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $dt ) );
		}
	}

	/**
	 * Return the current URL in frontend and backend.
	 *
	 * @return string
	 */
	public static function get_current_url(): string {
		if ( ! empty( $_SERVER['REQUEST_URI'] ) && is_admin() ) {
			return admin_url( basename( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
		}

		// set the return value for the page url.
		$page_url = '';

		// get actual object.
		$object = get_queried_object();
		if ( $object instanceof WP_Post_Type ) {
			$page_url = get_post_type_archive_link( $object->name );
		}
		if ( $object instanceof WP_Post ) {
			$page_url = get_permalink( $object->ID );
		}

		// return an empty string if no URL could be loaded.
		if ( ! $page_url ) {
			return '';
		}

		/**
		 * Filter the resulting current URL.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param string $page_url The resulting current URL.
		 */
		return apply_filters( 'cfprop_current_url', $page_url );
	}

	/**
	 * Add a new entry with its key on the specific position in the array.
	 *
	 * @param array<int|string,mixed>|null $fields The array we want to change.
	 * @param int                          $position The position where the new array should be added.
	 * @param array<int|string,mixed>      $array_to_add The new array, which should be added.
	 *
	 * @return array<int|string,mixed>
	 */
	public static function add_array_in_array_on_position( array|null $fields, int $position, array $array_to_add ): array {
		if ( is_null( $fields ) ) {
			return array();
		}
		return array_slice( $fields, 0, $position, true ) + $array_to_add + array_slice( $fields, $position, null, true );
	}

	/**
	 * Return whether this WordPress runs in development mode (available since WordPress 6.3).
	 *
	 * @return bool
	 */
	public static function is_development_mode(): bool {
		return (
					function_exists( 'wp_is_development_mode' ) && false !== wp_is_development_mode( 'plugin' )
				)
				|| ! function_exists( 'wp_is_development_mode' );
	}

	/**
	 * Return a shortened URL with the domain and filename on the base of the given URL.
	 *
	 * @param string $url The given URL.
	 *
	 * @return string
	 */
	public static function shorten_url( string $url ): string {
		// get the parse URL.
		$parsed_url = wp_parse_url( $url );

		// bail if URL could not be parsed.
		if ( ! is_array( $parsed_url ) ) {
			return $url;
		}

		// collect the resulting URL.
		$shortened_url = '';

		// add protocol.
		if ( ! empty( $parsed_url['scheme'] ) ) {
			$shortened_url .= $parsed_url['scheme'] . '://';
		}

		// add the host.
		if ( ! empty( $parsed_url['host'] ) ) {
			$shortened_url .= $parsed_url['host'];
		}

		// add the filename.
		if ( ! empty( $parsed_url['path'] ) ) {
			// get the potential filename.
			$filename = '/' . basename( $parsed_url['path'] );

			// if filename is not exact the path add the filename to the URL.
			if ( $filename !== $parsed_url['path'] ) {
				$shortened_url .= '/../' . basename( $parsed_url['path'] );
			} else {
				$shortened_url .= $filename;
			}
		}

		// return the shortened URL.
		return $shortened_url;
	}

	/**
	 * Return the language-specific URL where the user can find information about the Pro-version of this plugin.
	 *
	 * @return string
	 */
	public static function get_pro_url(): string {
		if ( Languages::get_instance()->is_german_language() ) {
			return 'https://laolaweb.com/plugins/propstack-wordpress-plugin/';
		}
		return 'https://laolaweb.com/en/plugins/propstack-wordpress-plugin/';
	}

	/**
	 * Return the review URL of this plugin on WordPress.org.
	 *
	 * @return string
	 */
	public static function get_review_url(): string {
		return 'https://wordpress.org/plugins/connector-for-propstack/#reviews';
	}

	/**
	 * Return the link to the documentation of this plugin on GitHub.
	 *
	 * @return string
	 */
	public static function get_github_documentation_link(): string {
		return 'https://github.com/threadi/connector-for-propstack/tree/master/doc';
	}
}

<?php
/**
 * File to handle template-tasks for this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Dependencies\easyTransientsForWordPress\Transients;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;

/**
 * Handler for templates.
 */
class Templates {
	/**
	 * Instance of this object.
	 *
	 * @var ?Templates
	 */
	private static ?Templates $instance = null;

	/**
	 * Cached result whether the global extension of the kses "post" context is needed.
	 *
	 * @var bool|null
	 */
	private ?bool $global_kses_extension_needed = null;

	/**
	 * Constructor for this object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() { }

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Templates {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the templates.
	 *
	 * @return void
	 */
	public function init(): void {
		// check for changed templates.
		add_action( 'admin_init', array( $this, 'check_child_theme_templates' ) );
		add_action( 'switch_theme', array( $this, 'reset_child_theme_templates_check' ) );
		add_action( 'upgrader_process_complete', array( $this, 'reset_child_theme_templates_check' ) );

		// add the template hooks.
		add_filter( 'single_template', array( $this, 'get_single_template' ) );
		add_filter( 'archive_template', array( $this, 'get_archive_template' ) );

		// expand kses-filter (only if explicitly enabled, see add_kses_html()).
		add_filter( 'wp_kses_allowed_html', array( $this, 'add_kses_html' ), 10, 2 );

		// use our own hooks.
		add_action( 'cfprop_get_template_before', array( $this, 'add_styles' ) );
	}

	/**
	 * Return possible archive-templates.
	 *
	 * @return array<string,string>
	 */
	public function get_archive_templates(): array {
		$templates = array(
			'default' => __( 'Default', 'connector-for-propstack' ),
			'listing' => __( 'Listings', 'connector-for-propstack' ),
		);

		/**
		 * Filter the list of available templates for archive listings.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param array<string,string> $templates List of templates (filename => label).
		 */
		return apply_filters( 'cfprop_templates_archive', $templates );
	}

	/**
	 * Return the path to a requested template if it exists.
	 *
	 * Also load the requested file if it is located in the /wp-content/themes/xy/connector-for-propstack/ directory.
	 *
	 * @param string $template The template to use.
	 * @return string
	 */
	public function get_template( string $template ): string {
		// bail with an empty template if the given template contains a path traversal or is an absolute path.
		if ( ! $this->is_valid_template_name( $template ) ) {
			return plugin_dir_path( CFPROP_PLUGIN ) . 'templates/parts/empty.php';
		}

		// check if the requested template exists in the theme (not for embeds).
		if ( ! is_embed() ) {
			$theme_template = locate_template( trailingslashit( basename( dirname( CFPROP_PLUGIN ) ) ) . $template );
			if ( $theme_template ) {
				return $theme_template;
			}
		}

		// set the directory for the template to use.
		$directory = CFPROP_PLUGIN;

		/**
		 * Set the template directory.
		 *
		 * Defaults to our own plugin-directory.
		 *
		 * @since 1.0.0 Available since first release.
		 *
		 * @param string $directory The directory to use.
		 */
		$plugin_template = plugin_dir_path( apply_filters( 'cfprop_set_template_directory', $directory ) ) . 'templates/' . $template;
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		// return template from light-plugin.
		return plugin_dir_path( CFPROP_PLUGIN ) . 'templates/' . $template;
	}

	/**
	 * Return whether the given template name is a valid relative path without path traversal.
	 *
	 * @param string $template The template with a relative path.
	 *
	 * @return bool
	 */
	private function is_valid_template_name( string $template ): bool {
		return '' !== $template && 0 === validate_file( $template ) && ! str_starts_with( $template, '/' ) && ! str_starts_with( $template, './' ) && ! str_contains( $template, '/./' ) && ! str_contains( $template, "\0" );
	}

	/**
	 * Check if the given template exists.
	 *
	 * @param string $template The searched template with a relative path.
	 * @return bool
	 */
	public function has_template( string $template ): bool {
		// bail if the given template contains a path traversal or is an absolute path.
		if ( ! $this->is_valid_template_name( $template ) ) {
			return false;
		}

		// check if the requested template exists in the theme.
		$theme_template = locate_template( trailingslashit( basename( dirname( CFPROP_PLUGIN ) ) ) . $template );
		if ( $theme_template ) {
			return true;
		}

		// set the directory for the template to use.
		$directory = CFPROP_PLUGIN;

		/**
		 * Set the template directory.
		 *
		 * Defaults to our own plugin-directory.
		 *
		 * @since 1.0.0 Available since first release.
		 *
		 * @param string $directory The directory to use.
		 */
		$plugin_template = plugin_dir_path( apply_filters( 'cfprop_set_template_directory', $directory ) ) . 'templates/' . $template;
		if ( file_exists( $plugin_template ) ) {
			return true;
		}

		// return template from light-plugin.
		return file_exists( plugin_dir_path( CFPROP_PLUGIN ) . 'templates/' . $template );
	}

	/**
	 * Check for changed templates of our own plugin in the child-theme if one is used.
	 *
	 * @return void
	 */
	public function check_child_theme_templates(): void {
		// bail on AJAX-, cron- and REST-requests, the check is only necessary for visible pages in backend.
		if ( wp_doing_ajax() || wp_doing_cron() || Helper::is_rest_request() ) {
			return;
		}

		// get the signature of the actual state to check (theme, its template directory and plugin version).
		$path      = trailingslashit( get_stylesheet_directory() ) . 'connector-for-propstack/';
		$signature = md5( get_stylesheet() . '|' . ( file_exists( $path ) ? (string) filemtime( $path ) : '' ) . '|' . CFPROP_VERSION );

		// bail if this state has already been checked.
		if ( get_transient( 'cfprop_child_theme_templates_checked' ) === $signature ) {
			return;
		}

		// mark this state as checked (re-check it at least once per day).
		set_transient( 'cfprop_child_theme_templates_checked', $signature, DAY_IN_SECONDS );

		// bail if it is not a child-theme.
		if ( ! is_child_theme() ) {
			Transients::get_instance()->get_transient_by_name( 'propstack_connector_old_templates' )->delete();
			return;
		}

		// check the existence of the child-theme-templates-directory.
		if ( ! file_exists( $path ) ) {
			Transients::get_instance()->get_transient_by_name( 'propstack_connector_old_templates' )->delete();
			return;
		}

		// get all files from the child-theme-templates directory (local, without any remote filesystem connection).
		$files = $this->get_local_files_from_directory( $path );
		if ( empty( $files ) ) {
			Transients::get_instance()->get_transient_by_name( 'propstack_connector_old_templates' )->delete();
			return;
		}

		// get the list of all templates in this plugin.
		$plugin_files = $this->get_local_files_from_directory( Helper::get_plugin_path() . 'templates/' );

		// collect warnings.
		$warnings = array();

		// set headers to check.
		$headers = array(
			'version' => 'Version',
		);

		// check the files from child-theme and compare them with our own.
		foreach ( $files as $file ) {
			// bail if the file does not exist in our plugin.
			if ( ! isset( $plugin_files[ basename( $file ) ] ) ) {
				continue;
			}

			// get the file-version-data of the child-template-file.
			$file_data = get_file_data( $file, $headers );

			// bail if the version does not exist.
			if ( ! isset( $file_data['version'] ) ) {
				continue;
			}

			// if the version is empty, show a warning (aka: no setting found).
			if ( empty( $file_data['version'] ) ) {
				$warnings[] = $file;
			} elseif ( ! empty( $plugin_files[ basename( $file ) ] ) ) {
				// get data of the original template.
				$plugin_file_data = get_file_data( $plugin_files[ basename( $file ) ], $headers );

				// bail if no version is set in original.
				if ( ! isset( $plugin_file_data['version'] ) ) {
					continue;
				}

				// trigger warning for this file.
				if ( version_compare( $plugin_file_data['version'], $file_data['version'], '>' ) ) {
					$warnings[] = $file;
				}
			}
		}

		// get transients-object.
		$transients_obj = Transients::get_instance();

		if ( ! empty( $warnings ) ) {
			// generate html-list of the files.
			$html_list = '<ul>';
			foreach ( $warnings as $file ) {
				$html_list .= '<li>' . esc_html( basename( $file ) ) . '</li>';
			}
			$html_list .= '</ul>';

			// show a transient.
			$transient_obj = $transients_obj->add();
			$transient_obj->set_name( 'propstack_connector_old_templates' );
			$transient_obj->set_message( __( '<strong>You are using a child theme that contains outdated Connector for Propstack template files.</strong> Please compare the following files in your child-theme with the one this plugin provides:', 'connector-for-propstack' ) . $html_list . '<strong>' . __( 'Hints:', 'connector-for-propstack' ) . '</strong><br>' . __( 'The version-number in the header of the files must match.', 'connector-for-propstack' ) . '<br>' . __( 'If you have any questions about this, talk to the technical administrator of your website.', 'connector-for-propstack' ) );
			$transient_obj->set_type( 'error' );
			$transient_obj->set_dismissible_days( 10 );
			$transient_obj->save();
		} else {
			$transients_obj->get_transient_by_name( 'propstack_connector_old_templates' )->delete();
		}
	}

	/**
	 * Reset the check for changed templates in the child theme, so it runs on the next request in backend.
	 *
	 * @return void
	 */
	public function reset_child_theme_templates_check(): void {
		delete_transient( 'cfprop_child_theme_templates_checked' );
	}

	/**
	 * Return the list of files in the given local directory, recursively.
	 *
	 * Uses the direct filesystem access as these are local files (theme and plugin), so no
	 * connection to a remote filesystem (e.g., FTP) is necessary.
	 *
	 * @param string $path The absolute path to the directory.
	 *
	 * @return array<string,string> List of files with the filename as index and the absolute path as value.
	 */
	private function get_local_files_from_directory( string $path ): array {
		// get the list of files.
		$files = Helper::get_wp_filesystem( true )->dirlist( $path, true, true );

		// bail if no files could be loaded.
		if ( ! is_array( $files ) || empty( $files ) ) {
			return array();
		}

		// return the resulting list.
		return $this->get_local_files( $files, trailingslashit( $path ) );
	}

	/**
	 * Recursively collect the files from the given list of a directory.
	 *
	 * @param array<string,mixed>  $files     The list of files from dirlist().
	 * @param string               $path      The absolute path of this list.
	 * @param array<string,string> $file_list The list of files collected so far.
	 *
	 * @return array<string,string>
	 */
	private function get_local_files( array $files, string $path, array $file_list = array() ): array {
		foreach ( $files as $filename => $settings ) {
			// bail if settings are not an array.
			if ( ! is_array( $settings ) || empty( $settings['type'] ) ) {
				continue;
			}

			// add a file.
			if ( 'f' === $settings['type'] ) {
				$file_list[ (string) $filename ] = $path . $filename;
			}

			// collect the files of a directory.
			if ( 'd' === $settings['type'] && ! empty( $settings['files'] ) && is_array( $settings['files'] ) ) {
				$file_list = $this->get_local_files( $settings['files'], $path . trailingslashit( (string) $filename ), $file_list );
			}
		}

		// return the resulting list.
		return $file_list;
	}

	/**
	 * Return the path to the single template.
	 *
	 * @param string $single_template The path to the single template.
	 * @return string
	 */
	public function get_single_template( string $single_template ): string {
		// bail if this is a FSE theme.
		if ( Helper::theme_is_fse_theme() ) {
			return $single_template;
		}

		// get the actual post-ID.
		$post_id = get_the_ID();

		// bail if post-ID could not be loaded.
		if ( ! $post_id ) {
			return $single_template;
		}

		// get the post-type of the actual object.
		$post_type = get_post_type( $post_id );

		// bail if post-type could not be loaded.
		if ( ! is_string( $post_type ) ) {
			return $single_template;
		}

		// bail if this is not our cpt.
		if ( ImmoObject::get_instance()->get_name() !== $post_type ) {
			return $single_template;
		}

		$false = false;
		/**
		 * Decide whether to use our own template (false) or not (true).
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param bool $false Return true if our own single template should not be used.
		 * @param string $single_template The single template, which will be used instead.
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		if ( apply_filters( 'cfprop_load_single_template', $false, $single_template ) ) {
			return $single_template;
		}

		// return the single template of our own plugin.
		return $this->get_template( 'single-' . ImmoObject::get_instance()->get_name() . '.php' );
	}

	/**
	 * Return the path to the archive template.
	 *
	 * @param string $archive_template The path to the archive template.
	 * @return string
	 */
	public function get_archive_template( string $archive_template ): string {
		// bail if this is a FSE theme.
		if ( Helper::theme_is_fse_theme() ) {
			return $archive_template;
		}

		// get the actual post-ID.
		$post_id = get_the_ID();

		// bail if post-ID could not be loaded.
		if ( ! $post_id ) {
			return $archive_template;
		}

		// get post-type of an actual object.
		$post_type = get_post_type( $post_id );

		// bail if post-type could not be loaded.
		if ( ! is_string( $post_type ) ) {
			return $archive_template;
		}

		// bail if this is not our cpt.
		if ( ImmoObject::get_instance()->get_name() !== $post_type ) {
			return $archive_template;
		}

		$false = false;
		/**
		 * Decide whether to use our own archive template (false) or not (true).
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param bool $false Return true if our own archive template should not be used.
		 * @param string $archive_template The archive template, which will be used instead.
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		if ( apply_filters( 'cfprop_load_archive_template', $false, $archive_template ) ) {
			return $archive_template;
		}

		// return our own archive template.
		return $this->get_template( 'archive-' . ImmoObject::get_instance()->get_name() . '.php' );
	}

	/**
	 * Extend the kses "post" context globally with the form elements of the filter, if enabled.
	 *
	 * Hint: until version 2.0.x this was always enabled. Now it is only for backwards compatibility,
	 * use get_allowed_filter_html() with wp_kses() for the output of the filter instead.
	 *
	 * The extension is enabled by default only where it is needed for backwards compatibility:
	 * - if the Pro plugin seems to be active (its page builder widgets could output the filter via wp_kses_post()).
	 * - if the active theme contains an override of the archive template or the filter template part in its
	 *   "connector-for-propstack" directory (older versions of these templates output the filter via wp_kses_post()).
	 * In all other cases it is disabled by default. The filter "cfprop_extend_post_kses_globally" overrides the default.
	 *
	 * @param array<string,mixed> $allowed_tags List of allowed tags and attributes.
	 * @param string              $context The context where this is called.
	 *
	 * @return array<string,mixed>
	 */
	public function add_kses_html( array $allowed_tags, string $context ): array {
		// bail if context is not "post" (checked first, as this filter is called very often).
		if ( 'post' !== $context ) {
			return $allowed_tags;
		}

		// get the default: only enabled if necessary for backwards compatibility.
		$extend_post_kses = $this->is_global_kses_extension_needed();

		/**
		 * Filter whether the allowed HTML of the "post" context of kses should be extended globally
		 * with form elements for the filter.
		 *
		 * This was the default until version 2.0.x. It allows all users who could save posts without
		 * "unfiltered_html" to use form elements. Use \ConnectorForPropstack\Plugin\Templates::get_instance()->get_allowed_filter_html()
		 * with wp_kses() for the output of the filter instead.
		 *
		 * Defaults to true if the Pro plugin seems to be active or if the theme overrides the archive
		 * template or the filter template part, otherwise false.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param bool $extend_post_kses True to extend the "post" context globally.
		 */
		if ( ! apply_filters( 'cfprop_extend_post_kses_globally', $extend_post_kses ) ) {
			return $allowed_tags;
		}

		$false = false;
		/**
		 * Prevent filtering the HTML code via kses.
		 * We need this only for the filter-form.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param bool $false False if the filter should be run.
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		if ( apply_filters( 'cfprop_add_kses_filter', $false ) ) {
			return $allowed_tags;
		}

		// add the form elements and return the resulting list.
		return $this->add_form_elements_to_allowed_html( $allowed_tags );
	}

	/**
	 * Return whether the global extension of the kses "post" context is needed for backwards compatibility.
	 *
	 * This is the case if:
	 * - the Pro plugin seems to be active (it hooks into "cfprop_hide_pro_hints" or an active plugin
	 *   has a directory starting with "connector-for-propstack-pro").
	 * - the active theme (or its parent theme) contains an override of the archive template or the
	 *   filter template part in its "connector-for-propstack" directory.
	 *
	 * @return bool
	 */
	private function is_global_kses_extension_needed(): bool {
		// use the cached result.
		if ( is_bool( $this->global_kses_extension_needed ) ) {
			return $this->global_kses_extension_needed;
		}

		// get the result.
		$needed = $this->is_pro_plugin_active() || $this->has_theme_filter_template_override();

		// cache it only after all plugins have been loaded (before that, the Pro plugin might not have added its hooks).
		if ( did_action( 'plugins_loaded' ) ) {
			$this->global_kses_extension_needed = $needed;
		}

		// return the result.
		return $needed;
	}

	/**
	 * Return whether the Pro plugin seems to be active.
	 *
	 * @return bool
	 */
	private function is_pro_plugin_active(): bool {
		// the Pro plugin uses this hook.
		if ( has_filter( 'cfprop_hide_pro_hints' ) ) {
			return true;
		}

		// get the list of active plugins.
		$active_plugins = (array) get_option( 'active_plugins', array() );

		// add the network-wide active plugins on multisite.
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		// check each plugin for the directory of the Pro plugin.
		foreach ( $active_plugins as $plugin ) {
			if ( is_string( $plugin ) && str_starts_with( $plugin, 'connector-for-propstack-pro' ) ) {
				return true;
			}
		}

		// Pro plugin is not active.
		return false;
	}

	/**
	 * Return whether the active theme overrides the archive template or the filter template part.
	 *
	 * @return bool
	 */
	private function has_theme_filter_template_override(): bool {
		// check the directories of the theme and its parent theme.
		foreach ( array_unique( array( get_stylesheet_directory(), get_template_directory() ) ) as $directory ) {
			$path = trailingslashit( $directory ) . trailingslashit( basename( dirname( CFPROP_PLUGIN ) ) );
			if ( file_exists( $path . 'archive-' . ImmoObject::get_instance()->get_name() . '.php' ) || file_exists( $path . 'parts/part-filter.php' ) ) {
				return true;
			}
		}

		// no override found.
		return false;
	}

	/**
	 * Return the allowed HTML for outputs which contain the filter form.
	 *
	 * This is the allowed HTML of the "post" context of kses extended by the form elements of the filter.
	 * Use it with wp_kses() for outputs of the filter (and only for them).
	 *
	 * @return array<string,mixed>
	 */
	public function get_allowed_filter_html(): array {
		// get the allowed HTML for posts.
		$allowed_tags = wp_kses_allowed_html( 'post' );

		// add the form elements.
		$allowed_tags = $this->add_form_elements_to_allowed_html( $allowed_tags );

		/**
		 * Filter the allowed HTML for the output of the filter form.
		 *
		 * @since 2.1.0 Available since 2.1.0.
		 * @param array<string,mixed> $allowed_tags List of allowed tags and their attributes.
		 */
		return apply_filters( 'cfprop_allowed_filter_html', $allowed_tags );
	}

	/**
	 * Add the form elements for our filter to the given list of allowed HTML elements.
	 *
	 * @param array<string,mixed> $allowed_tags List of allowed tags and attributes.
	 *
	 * @return array<string,mixed>
	 */
	private function add_form_elements_to_allowed_html( array $allowed_tags ): array {
		// add the necessary fields for the filter, if not already set.
		if ( empty( $allowed_tags['form'] ) ) {
			$allowed_tags['form'] = array(
				'action' => true,
				'method' => true,
				'class'  => true,
				'id'     => true,
			);
		}
		if ( empty( $allowed_tags['select'] ) ) {
			$allowed_tags['select'] = array(
				'class' => true,
				'id'    => true,
				'name'  => true,
			);
		}
		if ( empty( $allowed_tags['option'] ) ) {
			$allowed_tags['option'] = array(
				'class'    => true,
				'id'       => true,
				'selected' => true,
				'value'    => true,
			);
		}
		if ( empty( $allowed_tags['input'] ) ) {
			$allowed_tags['input'] = array(
				'class'       => true,
				'id'          => true,
				'name'        => true,
				'type'        => true,
				'value'       => true,
				'placeholder' => true,
				'min'         => true,
				'max'         => true,
				'step'        => true,
				'data-*'      => true,
			);
		}

		// return the list of allowed tags.
		return $allowed_tags;
	}

	/**
	 * Return the given CSS declarations (e.g., from the style attribute of a block) sanitized.
	 *
	 * Returns an empty string if the declarations contain characters, which could be used to
	 * break out of the declaration block or to load external resources.
	 *
	 * @param string $declarations The CSS declarations (e.g., "color: red; padding: 1em").
	 *
	 * @return string
	 */
	public static function sanitize_css_declarations( string $declarations ): string {
		// remove any tags.
		$declarations = trim( wp_strip_all_tags( $declarations ) );

		// bail if empty.
		if ( empty( $declarations ) ) {
			return '';
		}

		// bail if declarations contain forbidden characters.
		foreach ( array( '{', '}', '<', '>', '@', '\\', '/*', '*/', 'expression(', 'javascript:' ) as $forbidden ) {
			if ( str_contains( strtolower( $declarations ), $forbidden ) ) {
				return '';
			}
		}

		// return the sanitized declarations.
		return $declarations;
	}

	/**
	 * Return the CSS rule for the given block class and the given style declarations.
	 *
	 * Returns an empty string if no valid class or declarations are given.
	 *
	 * @param string       $class_name   The class of the block (e.g., "cfprop-block-xy").
	 * @param string|false $declarations The CSS declarations.
	 *
	 * @return string
	 */
	public static function get_block_css_rule( string $class_name, string|false $declarations ): string {
		// bail if no declarations are given.
		if ( ! is_string( $declarations ) ) {
			return '';
		}

		// sanitize the class.
		$class_name = sanitize_html_class( $class_name );

		// bail if no class is given.
		if ( empty( $class_name ) ) {
			return '';
		}

		// sanitize the declarations.
		$declarations = self::sanitize_css_declarations( $declarations );

		// bail if no declarations are left.
		if ( empty( $declarations ) ) {
			return '';
		}

		// return the rule (the class is set on the element of the block itself).
		return '.' . $class_name . ' { ' . $declarations . ' }';
	}

	/**
	 * Add custom widget styles depending on the theme type:
	 * - add via wp block-library-handle for block themes
	 * - add via the custom handle for all other themes
	 *
	 * @param array<string,mixed> $attributes List of attributes.
	 *
	 * @return void
	 */
	public function add_styles( array $attributes ): void {
		// bail if styles are not set.
		if ( empty( $attributes['styles'] ) ) {
			return;
		}

		// bail if styles are not a string.
		if ( ! is_string( $attributes['styles'] ) ) {
			return;
		}

		// get the styles.
		$css = $attributes['styles'];

		// clean the CSS (">" is kept as it is used for child combinators).
		$prepared_css = wp_strip_all_tags( $css );
		$prepared_css = str_replace( array( '<', '@import' ), '', $prepared_css );

		// bail if the CSS contains comments (they could hide or break the following rules).
		if ( str_contains( $prepared_css, '/*' ) || str_contains( $prepared_css, '*/' ) ) {
			return;
		}

		// bail if the curly brackets are not balanced (each rule must be complete).
		if ( substr_count( $prepared_css, '{' ) !== substr_count( $prepared_css, '}' ) ) {
			return;
		}

		// if this is a block theme, add styles the modern way.
		if ( Helper::theme_is_fse_theme() && ! Helper::is_rest_request() ) {
			// show these styles the modern way.
			wp_add_inline_style( 'wp-block-library', $prepared_css );

			// and do nothing more.
			return;
		}

		// show these styles the classic way.
		wp_register_style( 'cfprop-generated-styles', false, array(), CFPROP_VERSION, 'all' );
		wp_enqueue_style( 'cfprop-generated-styles' );
		wp_add_inline_style( 'cfprop-generated-styles', $prepared_css );
	}

	/**
	 * Return the content with the configured template.
	 *
	 * @param \ConnectorForPropstack\Propstack\ImmoObject $immo_object   The immo object as an object.
	 * @param array<string,mixed>                         $attributes The attributes used for output the template.
	 *
	 * @return string
	 */
	public function get_direct_content_template( \ConnectorForPropstack\Propstack\ImmoObject $immo_object, array $attributes ): string {
		if ( ! $immo_object instanceof ImmoObject ) { // @phpstan-ignore instanceof.alwaysFalse
			return '';
		}
		if ( ! empty( $attributes ) ) { // @phpstan-ignore deadCode.unreachable
			return '';
		}

		// get the template and return it.
		ob_start();
		include $this->get_template( 'parts/part-description.php' );
		$content = ob_get_clean();
		if ( ! $content ) {
			return '';
		}
		return $content;
	}
}

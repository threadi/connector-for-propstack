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

		// add the template hooks.
		add_filter( 'single_template', array( $this, 'get_single_template' ) );
		add_filter( 'archive_template', array( $this, 'get_archive_template' ) );

		// expand kses-filter.
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
		if ( is_embed() ) {
			return $template;
		}

		// check if the requested template exists in the theme.
		$theme_template = locate_template( trailingslashit( basename( dirname( CFPROP_PLUGIN ) ) ) . $template );
		if ( $theme_template ) {
			return $theme_template;
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
	 * Check if the given template exists.
	 *
	 * @param string $template The searched template with a relative path.
	 * @return bool
	 */
	public function has_template( string $template ): bool {
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
		// bail if it is not a child-theme.
		if ( ! is_child_theme() ) {
			Transients::get_instance()->get_transient_by_name( 'propstack_connector_old_templates' )->delete();
			return;
		}

		// get path for child-theme-templates-directory and check its existence.
		$path = trailingslashit( get_stylesheet_directory() ) . 'connector-for-propstack/';
		if ( ! file_exists( $path ) ) {
			Transients::get_instance()->get_transient_by_name( 'propstack_connector_old_templates' )->delete();
			return;
		}

		// get all files from the child-theme-templates directory.
		$files = Helper::get_files_from_directory( $path );
		if ( empty( $files ) ) {
			Transients::get_instance()->get_transient_by_name( 'propstack_connector_old_templates' )->delete();
			return;
		}

		// get the list of all templates in this plugin.
		$plugin_files = Helper::get_files_from_directory( Helper::get_plugin_path() . 'templates/' );

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
	 * Extend kses-filter if our own cpt is called.
	 *
	 * @param array<string,mixed> $allowed_tags List of allowed tags and attributes.
	 * @param string              $context The context where this is called.
	 *
	 * @return array<string,mixed>
	 */
	public function add_kses_html( array $allowed_tags, string $context ): array {
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

		// bail if context is not "post".
		if ( 'post' !== $context ) {
			return $allowed_tags;
		}

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

		// get the styles.
		$css = $attributes['styles'];

		// clean the CSS.
		$prepared_css = wp_strip_all_tags( $css );
		$prepared_css = str_replace( array( '</style>', '<style' ), '', $prepared_css );

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

<?php
/**
 * File to handle basic taxonomy-function.
 *
 * Hint:
 * We do not use "WP_Taxonomy" as we add some custom methods here.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Languages;
use ConnectorForPropstack\Plugin\Setup;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_Taxonomy;
use WP_Term;
use WP_Term_Query;

/**
 * Base object for each taxonomy.
 */
class Taxonomy {

	/**
	 * Define the taxonomy name.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * The API field name to assign a term of this taxonomy to an object.
	 *
	 * @var string
	 */
	protected string $api_field = '';

	/**
	 * The API subfield name to assign a term of this taxonomy to an object.
	 *
	 * @var string
	 */
	protected string $api_sub_field = '';

	/**
	 * Import these terms by object import.
	 *
	 * @var bool
	 */
	protected bool $add_by_object_import = false;

	/**
	 * Mark if this taxonomy can be used as a filter in the frontend.
	 *
	 * @var bool
	 */
	protected bool $can_be_used_as_filter = true;

	/**
	 * Mark if this taxonomy can be used as a template.
	 *
	 * @var bool
	 */
	protected bool $do_not_use_as_template = false;

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		// register this cpt.
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'admin_footer_text', array( $this, 'show_plugin_hint_in_footer' ), 0 );
	}

	/**
	 * Return the taxonomy name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the taxonomy single name.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->get_labels()['name'];
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
	 * Register this taxonomy.
	 *
	 * @return void
	 */
	public function register(): void {
		$taxonomy_array = $this->get_default_settings();
		$taxonomy_slug  = $this->get_name();
		/**
		 * Filter the settings for this taxonomy.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<string,mixed> $taxonomy_array The taxonomy settings.
		 * @param string $taxonomy_slug The slug of the taxonomy.
		 */
		$taxonomy_array = apply_filters( 'cfprop_taxonomy_' . $taxonomy_slug, $taxonomy_array, $taxonomy_slug );

		// register this taxonomy.
		register_taxonomy( $this->get_name(), array( ImmoObject::get_instance()->get_name() ), $taxonomy_array );
	}

	/**
	 * Return the default settings for each taxonomy.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_default_settings(): array {
		return array(
			'hierarchical'       => false,
			'labels'             => $this->get_labels(),
			'public'             => true,
			'show_ui'            => true,
			'show_in_menu'       => Setup::get_instance()->is_completed(),
			'show_in_nav_menus'  => false,
			'show_admin_column'  => true,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => false,
			'show_in_rest'       => is_user_logged_in(),
			'query_var'          => true,
			'meta_box_cb'        => array( $this, 'show_meta_box' ),
			'capabilities'       => array(
				'manage_terms' => 'read_' . ImmoObject::get_instance()->get_name(),
				'edit_terms'   => 'read_' . ImmoObject::get_instance()->get_name(),
				'delete_terms' => 'do_not_allow',
				'assign_terms' => 'read_' . ImmoObject::get_instance()->get_name(),
			),
		);
	}

	/**
	 * Return the labels for this taxonomy.
	 *
	 * @return array<string,string>
	 */
	protected function get_labels(): array {
		return array();
	}

	/**
	 * Return the API field name with the value to assign a term of this taxonomy to an object.
	 *
	 * @return string
	 */
	public function get_api_field(): string {
		return $this->api_field;
	}

	/**
	 * Return the API subfield name with the value to assign a term of this taxonomy to an object.
	 *
	 * @return string
	 */
	public function get_api_subfield(): string {
		return $this->api_sub_field;
	}

	/**
	 * Return the term ID by the given value from API.
	 *
	 * @param mixed  $value The value from API.
	 * @param string $language_code The language code of the term.
	 *
	 * @return int|false
	 */
	public function get_term_id_by_api_value( mixed $value, string $language_code ): int|false {
		// check if the given value exists.
		$query   = array(
			'taxonomy'   => $this->get_name(),
			'hide_empty' => false,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'api',
					'value'   => $value,
					'compare' => '=',
				),
				array(
					'key'     => 'language_code',
					'value'   => $language_code,
					'compare' => '=',
				),
			),
			'fields'     => 'ids',
		);
		$results = new WP_Term_Query( $query );

		// bail on no results.
		if ( empty( $results->terms ) ) {
			return false;
		}

		// return the term ID.
		return absint( $results->terms[0] );
	}

	/**
	 * Return whether the object import should add terms of this taxonomy.
	 *
	 * @return bool
	 */
	public function should_add_by_object_import(): bool {
		return $this->add_by_object_import;
	}

	/**
	 * Return the list of supported meta-fields to terms of this taxonomy.
	 *
	 * @return array<int,Field_Base>
	 */
	public function get_fields(): array {
		return array();
	}

	/**
	 * Return the list of available fields for REST API.
	 *
	 * @param WP_REST_Request $request The REST API request.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_fields_for_rest_api( WP_REST_Request $request ): array {
		// get the query.
		$query = $request->get_param( 'query' );

		// prepare the list of fields.
		$fields = array();

		// add them to the list.
		foreach ( $this->get_fields() as $index => $field ) {
			// bail on fields without name.
			if ( empty( $field->get_name() ) ) {
				continue;
			}

			// bail if the query does not match, if set.
			if ( ! empty( $query ) && ! str_contains( $field->get_name(), $query ) ) {
				continue;
			}

			// add the field to the list.
			$fields[] = array(
				'id'    => ( $index + 1 ),
				'label' => $field->get_label(),
				'value' => $field->get_name(),
			);
		}

		$instance = $this;

		/**
		 * Filter the available details-templates for REST API.
		 *
		 * @since 1.0.0 Available since 1.0.0
		 *
		 * @param array<int,array<string,mixed>> $fields The fields.
		 * @param Taxonomy $instance The taxonomy object.
		 * @param WP_REST_Request $request The REST API request object.
		 */
		return apply_filters( 'cfprop_rest_taxonomy_fields', $fields, $instance, $request );
	}

	/**
	 * Show the terms assigned to an immo object in a metabox.
	 *
	 * @param WP_Post             $post The post-object of the immo object.
	 * @param array<string,mixed> $box The box settings (contains the taxonomy slug).
	 *
	 * @return void
	 */
	public function show_meta_box( WP_Post $post, array $box ): void {
		// get the taxonomy slug.
		$taxonomy_slug = str_replace( 'tagsdiv-', '', $box['id'] );

		// get the taxonomy object.
		$taxonomy = get_taxonomy( $taxonomy_slug );

		// bail if taxonomy could not be loaded.
		if ( ! $taxonomy instanceof WP_Taxonomy ) {
			return;
		}

		// get the terms assigned to the post-object for this taxonomy.
		$terms = wp_get_object_terms( $post->ID, $taxonomy_slug );

		// we need an array.
		if ( ! is_array( $terms ) ) {
			return;
		}

		// show hint if no terms are assigned.
		if ( empty( $terms ) ) {
			echo '<em>' . esc_html__( 'No terms assigned.', 'connector-for-propstack' ) . '</em>';
			return;
		}

		// collect them.
		$list = array();
		foreach ( $terms as $term ) {
			// create a URL to filter an object for this term.
			$url    = add_query_arg(
				array(
					'post_type'    => $post->post_type,
					$taxonomy_slug => $term->slug,
				),
				get_admin_url() . 'edit.php'
			);
			$list[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a>';
		}

		// show them.
		echo wp_kses_post( implode( ', ', $list ) );
	}

	/**
	 * Return the object of a field by its given name.
	 *
	 * @param string $field_name The field name.
	 *
	 * @return Field_Base|false
	 */
	public function get_field_by_name( string $field_name ): Field_Base|false {
		foreach ( $this->get_fields() as $field ) {
			if ( $field_name !== $field->get_name() ) {
				continue;
			}

			// return this field.
			return $field;
		}

		// return false if no field could be found.
		return false;
	}

	/**
	 * Return the list of default terms for object types from Propstack.
	 *
	 * Format per entry:
	 * - api => the value in the Propstack API.
	 * - slug => the WordPress-internal slug.
	 * - label => the label for show in WordPress.
	 *
	 * @return array<int,mixed>
	 */
	protected function get_default_terms(): array {
		return array();
	}

	/**
	 * Tasks to run during plugin activation.
	 *
	 * @return void
	 */
	public function activation(): void {
		// add default entries.
		foreach ( $this->get_default_terms() as $default_term ) {
			// add the term and get the result.
			$term_data = wp_insert_term( $default_term['label'], $this->get_name(), array( 'slug' => $default_term['slug'] ) );

			// if an error occurred, get the term ID from it.
			if ( $term_data instanceof WP_Error ) {
				$term_id = $term_data->get_error_data();
			} else {
				$term_id = $term_data['term_id'];
			}

			// get the term object.
			$term = get_term( $term_id, $this->get_name() );

			// bail if it is not a "WP_Term" object.
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			// add the API name as a meta-field.
			update_term_meta( $term->term_id, 'api', $default_term['api'] );

			// add the language as a meta-field.
			update_term_meta( $term->term_id, 'language_code', Languages::get_instance()->get_current_lang() );
		}
	}

	/**
	 * Return the list of terms for this taxonomy.
	 *
	 * @return array<int,Term_Base>
	 */
	public function get_terms(): array {
		// create the query for terms.
		$query = array(
			'taxonomy'   => $this->get_name(),
			'hide_empty' => false,
		);

		$instance = $this;
		/**
		 * Filter the query for terms on a single taxonomy.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<string,mixed> $query The query.
		 * @param Taxonomy $instance The taxonomy object.
		 */
		$query = apply_filters( 'cfprop_taxonomy_terms_query', $query, $instance );

		// run the query.
		$terms = get_terms( $query );

		// bail if terms are not an array.
		if ( ! is_array( $terms ) ) {
			return array();
		}

		// create a "Term_Base" object for every term.
		$list = array();
		foreach ( $terms as $term ) {
			// bail if the term is not "WP_Term".
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			// create the object.
			$obj = new Term_Base();
			$obj->set_id( $term->term_id );
			$obj->set_slug( $term->slug );
			$obj->set_name( $term->name );

			// add the object to the list.
			$list[] = $obj;
		}

		// return the list.
		return $list;
	}

	/**
	 * Return the list of fields in a specific category.
	 *
	 * @param string $category_name The name of the category.
	 * @param bool   $check_for_public_view Whether to check for public view.
	 *
	 * @return array<int,Field_Base>
	 */
	public function get_fields_by_category( string $category_name, bool $check_for_public_view = false ): array {
		// prepare the list.
		$list = array();

		// loop through all fields in this taxonomy.
		foreach ( $this->get_fields() as $field ) {
			// bail if this field is not in the requested category.
			if ( $category_name !== $field->get_category()->get_name() ) {
				continue;
			}

			// bail if a public view check is run and the field should be hidden.
			if ( $check_for_public_view && ( $field->hide() || $field->hide_in_frontend() ) ) {
				continue;
			}

			// add this field to the list.
			$list[] = $field;
		}

		// return the resulting list of fields in this category .
		return $list;
	}

	/**
	 * Return a category title preset for this taxonomy to use in template generation.
	 *
	 * @param string $category_title The category title.
	 * @param string $term_title The term title.
	 *
	 * @return string
	 */
	public function get_category_title_preset( string $category_title, string $term_title ): string {
		/* translators: %1$s: category title, %2$s: term title */
		return sprintf( __( '%1$s for %2$s', 'connector-for-propstack' ), $category_title, $term_title );
	}

	/**
	 * Return whether a given field is disabled by default for this object type.
	 *
	 * @param Field_Base $field The field to check.
	 *
	 * @return bool
	 */
	public function is_field_default_disabled( Field_Base $field ): bool {
		// get the name of the default fields.
		$names = array_map(
			static fn( $item ) => $item->get_name(),
			$this->get_default_disabled_fields()
		);

		// return the result.
		return in_array( $field->get_name(), $names, true );
	}

	/**
	 * Return the list of default disabled fields for this object type.
	 *
	 * @return array<int,Field_Base>
	 */
	protected function get_default_disabled_fields(): array {
		return array();
	}

	/**
	 * Delete all terms of this taxonomy.
	 *
	 * @return void
	 */
	public function delete_all(): void {
		// prepare the query to delete terms.
		$query = array(
			'taxonomy'   => $this->get_name(),
			'hide_empty' => false,
			'fields'     => 'ids',
		);

		/**
		 * Filter the query to delete terms of one taxonomy.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<string,mixed> $query The query parameter.
		 */
		$query = apply_filters( 'cfprop_taxonomy_terms_query', $query );

		// get the terms of our taxonomy.
		$terms = get_terms( $query );

		// bail on any error.
		if ( $terms instanceof WP_Error ) {
			return;
		}

		// bail if it is not an array.
		if ( ! is_array( $terms ) ) {
			return;
		}

		// delete them.
		foreach ( $terms as $term_id ) {
			if ( ! is_int( $term_id ) ) {
				continue;
			}
			wp_delete_term( $term_id, $this->get_name() );
		}
	}

	/**
	 * Return whether this taxonomy can be used as a filter.
	 *
	 * @return bool
	 */
	public function can_be_used_as_filter(): bool {
		return $this->can_be_used_as_filter;
	}

	/**
	 * Show hint in the footer in the backend on listing and single view of our own taxonomies there.
	 *
	 * @param string $content The actual footer content.
	 *
	 * @return string
	 */
	public function show_plugin_hint_in_footer( string $content ): string {
		// get requested taxonomy.
		$taxonomy = (string) filter_input( INPUT_GET, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// bail if this is not the listing or the single view of a position in the backend.
		if ( $taxonomy !== $this->get_name() ) {
			return $content;
		}

		// show hint for our plugin.
		/* translators: %1$s will be replaced by the plugin name. */
		return $content . ' ' . sprintf( __( 'This page is provided by the plugin %1$s.', 'connector-for-propstack' ), '<em>' . Helper::get_plugin_name() . '</em>' );
	}

	/**
	 * Return whether this taxonomy should NOT be used as a template.
	 *
	 * @return bool
	 */
	public function do_not_use_as_template(): bool {
		return $this->do_not_use_as_template;
	}
}

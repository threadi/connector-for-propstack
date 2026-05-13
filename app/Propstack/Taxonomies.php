<?php
/**
 * File for handling any taxonomies we add.
 *
 * Types of taxonomies:
 * - Terms from Propstack API (like "states").
 * - Default terms (like "Marketing Type").
 *
 * Any term is not editable for the user as Propstack is the leading platform for this data.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\ProcessHandler;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use WP_Screen;
use WP_Term;
use WP_Term_Query;

/**
 * Object to handle any taxonomies we add.
 */
class Taxonomies {
	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Taxonomies
	 */
	protected static ?Taxonomies $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Taxonomies {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		// register our post-types.
		$this->register_taxonomies();

		// use hooks.
		add_action( 'init', array( $this, 'add_taxonomy_fields' ) );
		add_filter( 'hidden_columns', array( $this, 'hide_columns' ), 10, 3 );

		// use our own hooks.
		add_action( 'cfprop_import_object', array( $this, 'assign_term_during_import' ), 10, 3 );
		add_action( 'cfprop_import_object_after', array( $this, 'cleanup_after_import' ) );
	}

	/**
	 * Register the taxonomies from the list.
	 *
	 * @return void
	 */
	public function register_taxonomies(): void {
		foreach ( $this->get_taxonomies_as_objects() as $obj ) {
			$obj->init();
		}
	}

	/**
	 * Return the list of our taxonomies as objects.
	 *
	 * @return array<int,Taxonomy>
	 */
	public function get_taxonomies_as_objects(): array {
		// prepare the list.
		$list = array();

		// add them to the list.
		foreach ( $this->get_taxonomy_names() as $post_type ) {
			// get the class name.
			$class_name = $post_type . '::get_instance';

			// check if it is callable.
			if ( ! is_callable( $class_name ) ) {
				continue;
			}

			// get the object.
			$obj = $class_name();

			// bail if the instance is not our "Taxonomy".
			if ( ! $obj instanceof Taxonomy ) {
				continue;
			}

			// add it to the list.
			$list[] = $obj;
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Return the list of taxonomies as an array with the format "slug => label".
	 *
	 * @return array<string,string>
	 */
	public function get_taxonomies(): array {
		// prepare the list.
		$list = array();

		// add them to the list.
		foreach ( $this->get_taxonomies_as_objects() as $obj ) {
			$list[ $obj->get_name() ] = $obj->get_title();
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Return a taxonomy object by its name.
	 *
	 * @param string $name The taxonomy name.
	 *
	 * @return Taxonomy|false
	 */
	private function get_taxonomy_by_name( string $name ): Taxonomy|false {
		foreach ( $this->get_taxonomies_as_objects() as $taxonomy ) {
			if ( $name !== $taxonomy->get_name() ) {
				continue;
			}

			return $taxonomy;
		}

		// return false if not found.
		return false;
	}

	/**
	 * Return the list of class names for our own taxonomies.
	 *
	 * @return array<int,string>
	 */
	private function get_taxonomy_names(): array {
		$taxonomies = array();

		/**
		 * Filter the taxonomies.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param array<int,string> $taxonomies List of taxonomies.
		 */
		return apply_filters( 'cfprop_register_taxonomies', $taxonomies );
	}

	/**
	 * Add fields on each taxonomy and change the table-actions.
	 *
	 * @return void
	 */
	public function add_taxonomy_fields(): void {
		foreach ( $this->get_taxonomies_as_objects() as $taxonomy ) {
			add_filter( $taxonomy->get_name() . '_row_actions', array( $this, 'change_taxonomy_actions' ) );
			add_filter( 'manage_edit-' . $taxonomy->get_name() . '_columns', array( $this, 'change_taxonomy_columns' ) );
			add_filter( 'manage_' . $taxonomy->get_name() . '_custom_column', array( $this, 'add_thumbnail_in_column' ), 10, 3 );
			add_filter( 'manage_' . $taxonomy->get_name() . '_custom_column', array( $this, 'add_field_contents' ), 10, 3 );
			add_action( $taxonomy->get_name() . '_edit_form_fields', array( $this, 'add_custom_fields' ) );
			add_filter( 'bulk_actions-edit-' . $taxonomy->get_name(), array( $this, 'remove_bulk_actions' ), PHP_INT_MAX, 0 );
		}
	}

	/**
	 * Remove QuickEdit from taxonomy-table-actions.
	 *
	 * @param array<string,string> $actions List of actions.
	 *
	 * @return array<string,string>
	 */
	public function change_taxonomy_actions( array $actions ): array {
		unset( $actions['inline hide-if-no-js'] );
		return $actions;
	}

	/**
	 * Remove unnecessary columns from the taxonomy-table.
	 *
	 * @param array<string,string> $columns List of columns.
	 *
	 * @return array<string|int,string>
	 */
	public function change_taxonomy_columns( array $columns ): array {
		// get the called taxonomy.
		$taxonomy = filter_input( INPUT_GET, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// get the taxonomy object.
		$taxonomy_obj = $this->get_taxonomy_by_name( $taxonomy );

		// bail if taxonomy could not be loaded.
		if ( ! $taxonomy_obj instanceof Taxonomy ) {
			return $columns;
		}

		// remove cb and description.
		unset( $columns['cb'], $columns['description'] );

		// add the thumbnail column for Broker.
		if ( \ConnectorForPropstack\Propstack\Taxonomies\Broker::get_instance()->get_name() === $taxonomy ) {
			$columns = Helper::add_array_in_array_on_position( $columns, 0, array( 'propstack-connector-thumbnail' => __( 'Thumbnail', 'connector-for-propstack' ) ) );
		}

		// bail if this is an object type.
		if ( ObjectType::get_instance()->get_name() === $taxonomy_obj->get_name() ) {
			return $columns;
		}

		// add all fields from this taxonomy.
		foreach ( $taxonomy_obj->get_fields() as $field ) {
			// bail if the field should be hidden.
			if ( $field->hide() ) {
				continue;
			}

			// add the field.
			$columns[ 'propstack-connector-' . $field->get_name() ] = $field->get_label();
		}

		// return resulting columns.
		return $columns;
	}

	/**
	 * Add the fields to each term of our taxonomies.
	 *
	 * @param WP_Term $term The term as an object.
	 *
	 * @return void
	 */
	public function add_custom_fields( WP_Term $term ): void {
		// get the taxonomy object.
		$taxonomy = $this->get_taxonomy_by_name( $term->taxonomy );

		// bail if taxonomy is not found.
		if ( ! $taxonomy instanceof Taxonomy ) {
			return;
		}

		// bail if this taxonomy should not be editable.
		if ( ! $taxonomy->should_add_by_object_import() ) {
			return;
		}

		// show the data of all fields for this term.
		foreach ( $taxonomy->get_fields() as $field ) {
			// bail if the field should be hidden.
			if ( $field->hide() || $field->hide_in_frontend() ) {
				continue;
			}

			// show the field.
			?>
			<tr class="propstack-connector-<?php echo esc_attr( $field->get_name() ); ?> form-field">
				<th><label><?php echo esc_html( $field->get_label() ); ?></label></th>
				<td>
					<?php echo wp_kses_post( $this->get_field_value( $term->term_id, $field, false ) ); ?>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Remove any bulk action for terms.
	 *
	 * @return array<string,mixed>
	 */
	public function remove_bulk_actions(): array {
		return array();
	}

	/**
	 * Add and assign terms to objects during import.
	 *
	 * @param array<string,mixed> $immo_object The object data from API.
	 * @param int                 $post_id The post-ID.
	 * @param string              $language_code The used language.
	 *
	 * @return void
	 */
	public function assign_term_during_import( array $immo_object, int $post_id, string $language_code ): void {
		// assign the object to terms of some taxonomies.
		foreach ( $this->get_taxonomies_as_objects() as $taxonomy ) {
			// bail if required field is missing.
			if ( empty( $taxonomy->get_api_field() ) || empty( $immo_object[ $taxonomy->get_api_field() ] ) ) {
				continue;
			}

			// get the value.
			$value = $immo_object[ $taxonomy->get_api_field() ];
			if ( ! empty( $taxonomy->get_api_subfield() ) ) {
				$value = $immo_object[ $taxonomy->get_api_field() ][ $taxonomy->get_api_subfield() ];
			}

			// get the term by the given value.
			$term_id = $taxonomy->get_term_id_by_api_value( $value, $language_code );

			// bail if no term could be found.
			if ( ! is_int( $term_id ) ) {
				// bail if the taxonomy prevents the adding of terms by object import.
				if ( ! $taxonomy->should_add_by_object_import() ) {
					continue;
				}

				// add the term.
				$term_data = wp_insert_term( $immo_object[ $taxonomy->get_api_field() ]['name'], $taxonomy->get_name() );

				// bail on error.
				if ( ! is_array( $term_data ) ) {
					continue;
				}

				// get the term ID.
				$term_id = $term_data['term_id'];

				// set the language code.
				update_term_meta( $term_id, 'language_code', $language_code );
			}

			// update the term meta.
			foreach ( $taxonomy->get_fields() as $field ) {
				// bail if the API field is not set.
				if ( ! isset( $immo_object[ $taxonomy->get_api_field() ][ $field->get_api() ] ) ) {
					continue;
				}

				// save the value.
				update_term_meta( $term_id, $field->get_name(), $immo_object[ $taxonomy->get_api_field() ][ $field->get_api() ] );
			}

			// assign the term to the object.
			wp_set_object_terms( $post_id, $term_id, $taxonomy->get_name() );

			// mark this term as changed.
			update_term_meta( $term_id, 'changed', 1 );
		}
	}

	/**
	 * Return the value of a single field on a term of a single immo object.
	 *
	 * This function also checks the datatype of the value.
	 *
	 * @param int        $term_id      The term-ID.
	 * @param Field_Base $field        The field object.
	 * @param bool       $without_html Return the values with HTML or not.
	 * @param bool       $plain Return the values as plain text or not.
	 *
	 * @return mixed
	 */
	public function get_field_value( int $term_id, Field_Base $field, bool $without_html = true, bool $plain = false ): mixed {
		// get the formatted output for this field.
		return Fields::get_instance()->format_field( $field, get_term_meta( $term_id, $field->get_name(), true ), $without_html, $plain );
	}

	/**
	 * Return a taxonomy object by its name.
	 *
	 * @param string $name The given taxonomy name.
	 *
	 * @return Taxonomy|false
	 */
	public function get_taxonomy_as_object( string $name ): Taxonomy|false {
		foreach ( $this->get_taxonomies_as_objects() as $taxonomy ) {
			if ( $taxonomy->get_name() !== $name ) {
				continue;
			}
			return $taxonomy;
		}
		return false;
	}

	/**
	 * Add a thumbnail in the Broker table in a column.
	 *
	 * @param string $output Custom output.
	 * @param string $column_name The column name.
	 * @param int    $term_id The term ID.
	 *
	 * @return string
	 */
	public function add_thumbnail_in_column( string $output, string $column_name, int $term_id ): string {
		// bail if this is not our column.
		if ( 'propstack-connector-thumbnail' !== $column_name ) {
			return $output;
		}

		// get the thumbnail ID.
		$attachment_id = get_term_meta( $term_id, 'thumbnail_id', true );

		// bail if no attachment ID is set.
		if ( 0 === $attachment_id ) {
			return '';
		}

		// get the image.
		$image = wp_get_attachment_image( $attachment_id );

		// get the taxonomy object.
		$taxonomy_obj = $this->get_taxonomy_by_name( \ConnectorForPropstack\Propstack\Taxonomies\Broker::get_instance()->get_name() );

		// bail if the taxonomy could not be read.
		if ( ! $taxonomy_obj instanceof Taxonomy ) {
			return '';
		}

		// get the URL for the term.
		$url = get_edit_term_link( $term_id, $taxonomy_obj->get_name() );

		// bail if URL could not be read.
		if ( ! is_string( $url ) ) {
			return $image;
		}

		// show the image.
		return '<a href="' . $url . '">' . $image . '</a>';
	}

	/**
	 * Show column contents.
	 *
	 * @param string $output Custom output.
	 * @param string $column_name The column name.
	 * @param int    $term_id The term ID.
	 *
	 * @return string
	 */
	public function add_field_contents( string $output, string $column_name, int $term_id ): string {
		// get the called taxonomy.
		$taxonomy = filter_input( INPUT_GET, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// bail if this is an object type.
		if ( ObjectType::get_instance()->get_name() === $taxonomy ) {
			return $output;
		}

		// get the taxonomy object.
		$taxonomy_obj = $this->get_taxonomy_by_name( $taxonomy );

		// bail if taxonomy could not be loaded.
		if ( ! $taxonomy_obj instanceof Taxonomy ) {
			return $output;
		}

		// add the fields.
		foreach ( $taxonomy_obj->get_fields() as $field ) {
			if ( $column_name === $field->get_name() ) {
				return wp_kses_post( $this->get_field_value( $term_id, $field ) );
			}
		}

		// return the output.
		return $output;
	}

	/**
	 * Hide fields in our own cpt-table.
	 *
	 * @param array<int,string> $hidden List of columns to hide.
	 * @param WP_Screen         $screen Actual screen-object.
	 * @param bool              $use_defaults If defaults should be used.
	 *
	 * @return array<int,string>
	 */
	public function hide_columns( array $hidden, WP_Screen $screen, bool $use_defaults ): array {
		// bail if we do not want to use the defaults.
		if ( ! $use_defaults ) {
			return $hidden;
		}

		// bail if this is not our post type.
		if ( \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_name() !== $screen->post_type ) {
			return $hidden;
		}

		// check each taxonomy.
		foreach ( $this->get_taxonomies_as_objects() as $taxonomy ) {
			if ( $screen->taxonomy === $taxonomy->get_name() ) {
				foreach ( $taxonomy->get_fields() as $field ) {
					// bail if this field should be visible.
					if ( $field->show_in_table() ) {
						continue;
					}

					// hide this field in the table.
					$hidden[] = 'propstack-connector-' . $field->get_name();
				}
			}
		}

		// return the resulting list of hidden columns.
		return $hidden;
	}

	/**
	 * Clean up the terms after import of objects.
	 *
	 * @return void
	 */
	public function cleanup_after_import(): void {
		// get process ID from request.
		$process_id = filter_input( INPUT_POST, 'process_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( is_null( $process_id ) ) {
			$process_id = '';
		}

		// get the process handler with this ID.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_id( $process_id );

		// update status.
		$process_handler->set_status( __( 'Get the terms in tip-top shape', 'connector-for-propstack' ) );

		// assign the object to terms of some taxonomies.
		foreach ( $this->get_taxonomies_as_objects() as $taxonomy ) {
			// bail if this taxonomy should not be added through import.
			if ( ! $taxonomy->should_add_by_object_import() ) {
				continue;
			}

			// bail if this is not the broker taxonomy.
			if ( \ConnectorForPropstack\Propstack\Taxonomies\Broker::get_instance()->get_name() !== $taxonomy->get_name() ) {
				continue;
			}

			// get the not updated terms of this taxonomy.
			$query   = array(
				'taxonomy'   => $taxonomy->get_name(),
				'hide_empty' => false,
				'meta_query' => array(
					array(
						'key'     => 'changed',
						'compare' => 'NOT EXISTS',
					),
				),
			);
			$results = new WP_Term_Query( $query );

			// bail on no results.
			if ( empty( $results->terms ) ) {
				continue;
			}

			// bail if it is not an array.
			if ( ! is_array( $results->get_terms() ) ) {
				continue;
			}

			// delete these not updated terms.
			foreach ( $results->get_terms() as $term ) {
				if ( ! $term instanceof WP_Term ) {
					continue;
				}
				wp_delete_term( $term->term_id, $taxonomy->get_name() );
			}
		}
	}
}

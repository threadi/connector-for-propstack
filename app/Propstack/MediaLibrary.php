<?php
/**
 * File to extend the media library with object-specific functions.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\Fields\Main\ShortAddress;
use ConnectorForPropstack\Propstack\Taxonomies\MarketingType;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use WP_Post;
use WP_Query;

/**
 * Object to extend the media library.
 *
 * - Adds a filter to show only the images of objects.
 * - Shows the assigned object in the details of an image.
 */
class MediaLibrary {

	/**
	 * The name of the request parameter for the filter.
	 *
	 * @var string
	 */
	private string $filter_name = 'cfprop_media';

	/**
	 * The value of the filter to show only object images.
	 *
	 * @var string
	 */
	private string $filter_value = 'object_images';

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?MediaLibrary
	 */
	private static ?MediaLibrary $instance = null;

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
	public static function get_instance(): MediaLibrary {
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
		// filter in the list view of the media library.
		add_action( 'restrict_manage_posts', array( $this, 'add_filter_select' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_list_view' ) );

		// filter in the grid view of the media library.
		add_action( 'admin_enqueue_scripts', array( $this, 'add_js' ) );
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_grid_view' ) );

		// show the assigned object in the details of an image.
		add_action( 'add_meta_boxes_attachment', array( $this, 'add_meta_box' ) );
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_object_field' ), 10, 2 );

		// misc.
		add_filter( 'admin_footer_text', array( $this, 'show_plugin_hint_in_footer' ), 0 );
	}

	/**
	 * Return the meta query to get only the images of objects.
	 *
	 * Broker avatars are imported from Propstack, too, but they are not object images.
	 *
	 * @return array<string|int,mixed>
	 */
	public function get_meta_query(): array {
		return array(
			'relation' => 'AND',
			array(
				'key'     => 'propstack_file_id',
				'compare' => 'EXISTS',
			),
			array(
				'key'     => 'cfprop_broker_avatar',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	/**
	 * Add the meta query to the given query arguments.
	 *
	 * An existing meta query is kept and combined with ours.
	 *
	 * @param array<string,mixed> $query_args The query arguments.
	 *
	 * @return array<string,mixed>
	 */
	private function add_meta_query( array $query_args ): array {
		// use only our meta query if no other is set.
		if ( empty( $query_args['meta_query'] ) || ! is_array( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = $this->get_meta_query(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup; admin context.
			return $query_args;
		}

		// combine both.
		$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup; admin context.
			'relation' => 'AND',
			$query_args['meta_query'],
			$this->get_meta_query(),
		);

		return $query_args;
	}

	/**
	 * Return whether the filter for object images is requested.
	 *
	 * @param mixed $value The requested value.
	 *
	 * @return bool
	 */
	private function is_filter_requested( mixed $value ): bool {
		return is_string( $value ) && sanitize_key( wp_unslash( $value ) ) === $this->filter_value;
	}

	/**
	 * Add the filter select to the list view of the media library.
	 *
	 * @param string $post_type The post type of the list.
	 *
	 * @return void
	 */
	public function add_filter_select( string $post_type ): void {
		// bail if this is not the media library.
		if ( 'attachment' !== $post_type ) {
			return;
		}

		// get the actual value.
		$value = isset( $_GET[ $this->filter_name ] ) ? sanitize_key( wp_unslash( $_GET[ $this->filter_name ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter of a list.

		?>
		<label for="cfprop-media-filter" class="screen-reader-text"><?php echo esc_html__( 'Filter by object images', 'connector-for-propstack' ); ?></label>
		<select name="<?php echo esc_attr( $this->filter_name ); ?>" id="cfprop-media-filter">
			<option value=""><?php echo esc_html__( 'All media files', 'connector-for-propstack' ); ?></option>
			<option value="<?php echo esc_attr( $this->filter_value ); ?>"<?php selected( $this->filter_value, $value ); ?>><?php echo esc_html__( 'Show object images', 'connector-for-propstack' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Filter the list view of the media library.
	 *
	 * @param WP_Query $query The query.
	 *
	 * @return void
	 */
	public function filter_list_view( WP_Query $query ): void {
		// bail if this is not the main query in the backend.
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// bail if this is not the media library.
		if ( 'attachment' !== $query->get( 'post_type' ) ) {
			return;
		}

		// bail if our filter is not requested.
		if ( ! isset( $_GET[ $this->filter_name ] ) || ! $this->is_filter_requested( sanitize_text_field( wp_unslash( $_GET[ $this->filter_name ] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only filter of a list, sanitized in is_filter_requested().
			return;
		}

		// set the meta query.
		$query_args = $this->add_meta_query( array( 'meta_query' => $query->get( 'meta_query' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup; admin context.
		$query->set( 'meta_query', $query_args['meta_query'] );
	}

	/**
	 * Filter the grid view of the media library (and the media modal).
	 *
	 * @param array<string,mixed> $query_args The query arguments.
	 *
	 * @return array<string,mixed>
	 */
	public function filter_grid_view( array $query_args ): array {
		// bail if our filter is not requested.
		if ( ! isset( $_REQUEST['query'][ $this->filter_name ] ) || ! $this->is_filter_requested( sanitize_text_field( wp_unslash( $_REQUEST['query'][ $this->filter_name ] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The nonce is checked by WordPress, sanitized in is_filter_requested().
			return $query_args;
		}

		// return the query with our meta query.
		return $this->add_meta_query( $query_args );
	}

	/**
	 * Add the script for the filter in the grid view of the media library.
	 *
	 * @param string $hook The actual admin page.
	 *
	 * @return void
	 */
	public function add_js( string $hook ): void {
		// bail if this is not the media library.
		if ( 'upload.php' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'cfprop-media-library',
			Helper::get_plugin_url() . 'admin/media.js',
			array( 'media-views' ),
			Helper::get_file_version( trailingslashit( Helper::get_plugin_path() ) . 'admin/media.js' ),
			true
		);

		// add php-vars to our js-script.
		wp_localize_script(
			'cfprop-media-library',
			'cfpropMediaLibraryJsVars',
			array(
				'filter_name'  => $this->filter_name,
				'filter_value' => $this->filter_value,
				'label'        => __( 'Filter by object images', 'connector-for-propstack' ),
				'all'          => __( 'All media files', 'connector-for-propstack' ),
				'objects'      => __( 'Show object images', 'connector-for-propstack' ),
			)
		);
	}

	/**
	 * Return the object to which the given attachment is assigned.
	 *
	 * @param WP_Post $attachment The attachment.
	 *
	 * @return ImmoObject|false
	 */
	public function get_object_of_attachment( WP_Post $attachment ): ImmoObject|false {
		// bail if this is not an image of an object.
		if ( empty( get_post_meta( $attachment->ID, 'propstack_file_id', true ) ) || ! empty( get_post_meta( $attachment->ID, 'cfprop_broker_avatar', true ) ) ) {
			return false;
		}

		// bail if the attachment is not assigned to an object.
		if ( $attachment->post_parent <= 0 || PostTypes\ImmoObject::get_instance()->get_name() !== get_post_type( $attachment->post_parent ) ) {
			return false;
		}

		// return the object.
		return ImmoObjects::get_instance()->get_object( $attachment->post_parent );
	}

	/**
	 * Add the meta box with the object on the edit page of an image.
	 *
	 * @param WP_Post $attachment The attachment.
	 *
	 * @return void
	 */
	public function add_meta_box( WP_Post $attachment ): void {
		// bail if the attachment is not assigned to an object.
		if ( ! $this->get_object_of_attachment( $attachment ) ) {
			return;
		}

		// add the meta box.
		add_meta_box(
			'cfprop-media-object',
			__( 'Propstack object', 'connector-for-propstack' ),
			array( $this, 'show_meta_box' ),
			'attachment',
			'side'
		);
	}

	/**
	 * Show the content of the meta box.
	 *
	 * @param WP_Post $attachment The attachment.
	 *
	 * @return void
	 */
	public function show_meta_box( WP_Post $attachment ): void {
		echo wp_kses_post( $this->get_object_info( $attachment ) );
	}

	/**
	 * Add the object to the details of an image in the media modal (e.g. the grid view of the media library).
	 *
	 * The field is not shown on the edit page of an image, as the meta box is shown there.
	 *
	 * @param array<string,array<string,mixed>> $form_fields The form fields.
	 * @param WP_Post                           $attachment  The attachment.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function add_object_field( array $form_fields, WP_Post $attachment ): array {
		// get the info about the object.
		$info = $this->get_object_info( $attachment );

		// bail if the attachment is not assigned to an object.
		if ( empty( $info ) ) {
			return $form_fields;
		}

		// add the field.
		$form_fields['cfprop_object'] = array(
			'label'        => __( 'Propstack object', 'connector-for-propstack' ),
			'input'        => 'html',
			'html'         => wp_kses_post( $info ),
			'show_in_edit' => false,
		);

		// return the resulting list.
		return $form_fields;
	}

	/**
	 * Return the HTML with the base information about the object of the given attachment.
	 *
	 * @param WP_Post $attachment The attachment.
	 *
	 * @return string
	 */
	public function get_object_info( WP_Post $attachment ): string {
		// get the object.
		$immo_object = $this->get_object_of_attachment( $attachment );

		// bail if the attachment is not assigned to an object.
		if ( ! $immo_object ) {
			return '';
		}

		// collect the base information.
		$entries = array();

		// add the Propstack ID.
		$object_id = $immo_object->get_object_id();
		if ( ! empty( $object_id ) ) {
			$entries[] = array(
				'label' => __( 'Propstack ID', 'connector-for-propstack' ),
				'value' => $object_id,
			);
		}

		// add the address.
		$address = Fields::get_instance()->get_field_value( $immo_object->get_id(), new ShortAddress() );
		if ( is_string( $address ) && ! empty( $address ) ) {
			$entries[] = array(
				'label' => __( 'Address', 'connector-for-propstack' ),
				'value' => $address,
			);
		}

		// add the object type and the marketing type.
		foreach ( array( ObjectType::get_instance(), MarketingType::get_instance() ) as $taxonomy ) {
			$terms = wp_get_object_terms( $immo_object->get_id(), $taxonomy->get_name() );
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				$entries[] = array(
					'label' => $taxonomy->get_title(),
					'value' => $terms[0]->name,
				);
			}
		}

		// add the status of the post.
		$post_status = get_post_status_object( (string) get_post_status( $immo_object->get_id() ) );
		if ( $post_status ) {
			$entries[] = array(
				'label' => __( 'Status', 'connector-for-propstack' ),
				'value' => (string) $post_status->label,
			);
		}

		// create the output.
		$html = '<div class="cfprop-media-object">';

		// add the title.
		$html .= '<p><strong>' . esc_html( $immo_object->get_title() ) . '</strong></p>';

		// add the base information.
		if ( ! empty( $entries ) ) {
			$html .= '<ul>';
			foreach ( $entries as $entry ) {
				$html .= '<li>' . esc_html( $entry['label'] ) . ': ' . esc_html( $entry['value'] ) . '</li>';
			}
			$html .= '</ul>';
		}

		// add the links.
		$links = array();
		if ( 'publish' === get_post_status( $immo_object->get_id() ) ) {
			$links[] = '<a href="' . esc_url( $immo_object->get_link() ) . '" target="_blank">' . esc_html__( 'Show object', 'connector-for-propstack' ) . '</a>';
		}
		$edit_link = get_edit_post_link( $immo_object->get_id(), 'url' );
		if ( ! empty( $edit_link ) ) {
			$links[] = '<a href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit object', 'connector-for-propstack' ) . '</a>';
		}
		if ( ! empty( $object_id ) ) {
			$links[] = '<a href="' . esc_url( $immo_object->get_url_to_propstack() ) . '" target="_blank">' . esc_html__( 'Open in Propstack', 'connector-for-propstack' ) . '</a>';
		}
		if ( ! empty( $links ) ) {
			$html .= '<p>' . implode( ' | ', $links ) . '</p>';
		}

		$html .= '</div>';

		/**
		 * Filter the information about the object of an image in the media library.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param string     $html        The HTML output.
		 * @param ImmoObject $immo_object The object.
		 * @param WP_Post    $attachment  The attachment.
		 */
		return apply_filters( 'cfprop_media_object_info', $html, $immo_object, $attachment );
	}

	/**
	 * Show hint in the footer in the backend on listing and single view of our own taxonomies there.
	 *
	 * @param string $content The actual footer content.
	 *
	 * @return string
	 */
	public function show_plugin_hint_in_footer( string $content ): string {
		global $pagenow;

		// bail if media library is not loaded.
		if ( ! in_array( $pagenow, array( 'upload.php', 'post.php' ), true ) ) {
			return $content;
		}

		// get requested page.
		$post_id = absint( filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT ) );

		// get the post object.
		$post = get_post( $post_id );

		// bail if post could not be loaded.
		if ( ! $post instanceof WP_Post ) {
			return $content;
		}

		// get the object.
		$immo_object = $this->get_object_of_attachment( $post );

		// bail if the attachment is not assigned to an object.
		if ( ! $immo_object ) {
			return $content;
		}

		// show hint for our plugin.
		/* translators: %1$s will be replaced by the plugin name. */
		return $content . ' ' . sprintf( __( 'This page is extended by the plugin %1$s.', 'connector-for-propstack' ), '<em>' . Helper::get_plugin_name() . '</em>' );
	}
}

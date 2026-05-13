<?php
/**
 * File for handling a single Propstack object as an object.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base;

/**
 * Object, which represents a single Propstack object.
 */
class ImmoObject {
	/**
	 * The post_id of this object.
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * The object language.
	 *
	 * @var string
	 */
	private string $lang = 'de';

	/**
	 * Constructor for this position.
	 *
	 * @param int $post_id The post_id of this position.
	 */
	public function __construct( int $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * Return the post-ID of this object.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->post_id;
	}

	/**
	 * Return the language of this object.
	 *
	 * @return string
	 */
	public function get_lang(): string {
		return $this->lang;
	}

	/**
	 * Set the language of this object.
	 *
	 * @param string $lang The language.
	 *
	 * @return void
	 */
	public function set_lang( string $lang ): void {
		$this->lang = $lang;
	}

	/**
	 * Return the list of images that are assigned to this object.
	 *
	 * @return array<int,int>
	 */
	public function get_images(): array {
		// get the images.
		$images = get_post_meta( $this->get_id(), 'images', true );

		// bail if it is not an array.
		if ( ! is_array( $images ) ) {
			return array();
		}

		// return the list of images.
		return $images;
	}

	/**
	 * Return the language of this object.
	 *
	 * @return string
	 */
	public function get_language(): string {
		// get the language.
		$language = get_post_meta( $this->get_id(), 'language_code', true );

		// bail if it is not a string.
		if ( ! is_string( $language ) ) {
			return '';
		}

		// return the language.
		return $language;
	}

	/**
	 * Return the object ID.
	 *
	 * @return string
	 */
	public function get_object_id(): string {
		return get_post_meta( $this->get_id(), 'object_id', true );
	}

	/**
	 * Return the title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return get_the_title( $this->get_id() );
	}

	/**
	 * Return the list of fields for this object.
	 *
	 * @param bool $without_html Return the fields with or without HTML.
	 *
	 * @return array<string,mixed>
	 */
	public function get_fields( bool $without_html = false ): array {
		// get the object type.
		$object_type_object = ObjectType::get_instance()->get_object_type_by_object_post_id( $this->get_id() );

		// bail if the object type is missing.
		if ( ! $object_type_object instanceof Object_Type_Base ) {
			return array();
		}

		// prepare the list of fields.
		$fields = array();

		// loop through all configured fields.
		foreach ( $object_type_object->get_fields() as $field ) {
			// get the name.
			$name = $field->get_name();

			// add the value of this field on this object to the list.
			$fields[ $name ] = Fields::get_instance()->get_field_value( $this->get_id(), $field, $without_html );
		}

		// return the resulting list of fields.
		return $fields;
	}

	/**
	 * Return the secured API response.
	 *
	 * @return array<string,mixed>
	 */
	public function get_api_response(): array {
		// get the secured response.
		$response = get_post_meta( $this->get_id(), 'api_response', true );

		// bail if the response is not an array.
		if ( ! is_array( $response ) ) {
			return array();
		}

		// return the API response.
		return $response;
	}

	/**
	 * Add an attachment ID to the list of images for this object.
	 *
	 * @param int $attachment_id The attachment ID.
	 *
	 * @return void
	 */
	public function add_to_image_list( int $attachment_id ): void {
		// get the actual list of files.
		$list = $this->get_images();

		// bail if the ID exists already.
		if ( in_array( $attachment_id, $list, true ) ) {
			return;
		}

		// add the ID.
		$list[] = $attachment_id;

		// add a log entry if debug is enabled.
		if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
			/* translators: %1$s will be replaced by the attachment ID. */
			Log::get_instance()->add( sprintf( __( 'Assign image %1$s to object %2$s.', 'connector-for-propstack' ), '<em>' . $attachment_id . '</em>', '<em>' . $this->get_title() . '</em>' ), 'info', 'import' );
		}

		// save it.
		update_post_meta( $this->get_id(), 'images', $list );
	}

	/**
	 * Return the single view URL for this object.
	 *
	 * @return string
	 */
	public function get_link(): string {
		return (string) get_permalink( $this->get_id() );
	}
}

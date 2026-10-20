<?php
/**
 * File to handle the image category for fields.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldCategories;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\Field_Category_Base;
use ConnectorForPropstack\Propstack\Fields;
use ConnectorForPropstack\Propstack\Files;
use ConnectorForPropstack\Propstack\Fields\Main\ApiResponse;
use ConnectorForPropstack\Propstack\ImmoObjects;
use WP_Post;

/**
 * Object to handle the image category for fields.
 */
class Images extends Field_Category_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = 'images';

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Images', 'connector-for-propstack' );
	}

	/**
	 * Show the metabox for this field category.
	 *
	 * @param WP_Post $post The post-object.
	 *
	 * @return void
	 */
	public function show_in_metabox( WP_Post $post ): void {
		// get the post-ID.
		$post_id = $post->ID;

		// get the immo object.
		$immo_object = ImmoObjects::get_instance()->get_object( $post_id );

		// get its images.
		$images = $immo_object->get_images();

		// bail if no images are set.
		if ( empty( $images ) ) {
			// get the API response for this object.
			$api_response = Fields::get_instance()->get_field_value( $post_id, new ApiResponse(), true, true );

			// show hint that images are not available.
			echo '<p><em>' . esc_html__( 'This object does not have any images.', 'connector-for-propstack' ) . '</em></p>';

			// show a button to import images if they are in the API response.
			if ( empty( $api_response['images'] ) ) {
				// show hint to import images with a link to start it.
				echo '<span class="button">' . esc_html__( 'Got no images', 'connector-for-propstack' ) . '</span>';
			} else {
				echo '<a href="#" class="easy-dialog-for-wordpress button" data-dialog="' . esc_attr( Helper::get_json( \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_import_images_dialog( $post_id ) ) ) . '">' . esc_html__( 'Import images', 'connector-for-propstack' ) . '</a>';
			}

			// do nothing more.
			return;
		}

		// show them as a simple list.
		?>
		<ul class="gallery">
			<?php
			foreach ( $images as $image_id ) {
				// get the image as HTML with the thumbnail site.
				$thumbnail = wp_get_attachment_image( $image_id );

				// get the original size in HTML.
				$image = wp_get_attachment_image( $image_id, 'full' );

				// get the original image URL.
				$image_url = wp_get_attachment_image_url( $image_id, 'full' );

				// create the dialog for a simple lightbox.
				$lightbox_dialog = array(
					'className'                 => 'propstack-lightbox',
					'isDismissible'             => true,
					'shouldCloseOnEsc'          => true,
					'hide_title'                => true,
					'shouldCloseOnClickOutside' => true,
					'texts'                     => array(
						$image,
					),
				);

				// output.
				?>
				<li><a href="<?php echo esc_url( (string) $image_url ); ?>" class="easy-dialog-for-wordpress" data-dialog="<?php echo esc_attr( Helper::get_json( $lightbox_dialog ) ); ?>">
						<?php
						echo wp_kses_post( $thumbnail );
						?>
					</a>
					<?php
					/**
					 * Run additional tasks after an image of the object in its edit view (e.g. to show its tags).
					 *
					 * @since 2.1.0 Available since 2.1.0.
					 * @param int     $image_id The attachment ID of the image.
					 * @param WP_Post $post     The object.
					 */
					do_action( 'cfprop_object_image_in_metabox', $image_id, $post );
					?>
				</li>
				<?php
			}
			?>
		</ul>
		<?php

		// get the API response for this object.
		$api_response = Fields::get_instance()->get_field_value( $post_id, new ApiResponse(), true, true );

		// bail if no images are in the API response.
		if ( ! is_array( $api_response ) || empty( $api_response['images'] ) || ! is_array( $api_response['images'] ) ) {
			return;
		}

		// count the images from Propstack which are not in the media library yet (e.g. new images or images which have been excluded before).
		$missing = 0;
		foreach ( $api_response['images'] as $file ) {
			// bail if this is not a usable file entry.
			if ( ! is_array( $file ) || ! isset( $file['id'] ) || ! is_scalar( $file['id'] ) ) {
				continue;
			}

			// bail if the import of this image is prevented.
			if ( Files::get_instance()->is_file_import_prevented( $file ) ) {
				continue;
			}

			// bail if the image is already in the media library.
			if ( Files::get_instance()->is_file_in_media_library( absint( $file['id'] ) ) > 0 ) {
				continue;
			}

			++$missing;
		}

		// show hint for missing images.
		if ( $missing > 0 ) {
			/* translators: %1$d will be replaced by the amount of images. */
			echo '<p><em>' . esc_html( sprintf( _n( '%1$d image from Propstack is not imported yet. It will be imported by the queue or you can import it now.', '%1$d images from Propstack are not imported yet. They will be imported by the queue or you can import them now.', $missing, 'connector-for-propstack' ), $missing ) ) . '</em></p>';
		}

		// show the button to import the images of this object now.
		echo '<a href="#" class="easy-dialog-for-wordpress button" data-dialog="' . esc_attr( Helper::get_json( \ConnectorForPropstack\Propstack\PostTypes\ImmoObject::get_instance()->get_import_images_dialog( $post_id ) ) ) . '">' . esc_html__( 'Import images', 'connector-for-propstack' ) . '</a>';
	}
}

<?php
/**
 * File to handle the language category for fields.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\FieldCategories;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Category_Base;
use ConnectorForPropstack\Propstack\ImmoObjects;
use WP_Post;

/**
 * Object to handle the image category for fields.
 */
class Languages extends Field_Category_Base {
	/**
	 * The internal name of the category.
	 *
	 * @var string
	 */
	protected string $name = 'languages';

	/**
	 * Return the category label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Languages', 'connector-for-propstack' );
	}

	/**
	 * Show the metabox for this field category.
	 *
	 * @param WP_Post $post The post-object.
	 *
	 * @return void
	 */
	public function show_in_metabox( WP_Post $post ): void {
		// get the immo object.
		$immo_object = ImmoObjects::get_instance()->get_object( $post->ID );

		// get the language of the object.
		$language = $immo_object->get_language();

		// bail if no language is given.
		if ( empty( $language ) ) {
			echo '<p>' . esc_html__( 'This object does not have a language set.', 'connector-for-propstack' ) . '</p>';
			return;
		}

		// get list of languages.
		$languages = \ConnectorForPropstack\Plugin\Languages::get_instance()->get_languages();

		// bail if language is not supported.
		if ( ! isset( $languages[ $language ] ) ) {
			echo '<p>' . esc_html__( 'This object does use a language, that is actually not supported.', 'connector-for-propstack' ) . '</p>';
			return;
		}

		// show the language.
		echo wp_kses_post( $languages[ $language ] );
	}
}

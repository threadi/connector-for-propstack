<?php
/**
 * File to handle a knowledge center entry.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\KnowledgeCenter;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\KnowledgeCenter_Base;

/**
 * Object for the knowledge center entry "Images are missing or not imported".
 */
class MissingImages extends KnowledgeCenter_Base {
	/**
	 * The internal name of the knowledge center entry.
	 *
	 * @var string
	 */
	protected string $name = 'missing_images';

	/**
	 * The position of this entry in the list of help tabs.
	 *
	 * @var int
	 */
	protected int $priority = 80;

	/**
	 * The group of this entry.
	 *
	 * @var string
	 */
	protected string $group = 'import';

	/**
	 * Return the title of the knowledge center entry.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Images are missing or not imported', 'connector-for-propstack' );
	}

	/**
	 * Return the description of the problem this entry solves.
	 *
	 * @return string
	 */
	public function get_problem(): string {
		return __( 'Objects are shown without images or with only some of their images, or the log reports errors for the import of files.', 'connector-for-propstack' );
	}

	/**
	 * Return the text for this knowledge center entry.
	 *
	 * @return string
	 */
	public function get_text(): string {
		/* translators: %1$s will be replaced by a link to the settings. */
		$text  = '<p>' . sprintf( __( 'The images are not imported together with the objects, but afterwards via a queue. This keeps the import of objects fast. You can see the actual state of the queue under %1$s.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Settings > Queue', 'connector-for-propstack' ), 'propstack_connector_queue' ) ) . '</p>';
		$text .= $this->get_list(
			array(
				__( '<strong>Wait a moment:</strong> after an import the queue needs some runs until all images are in your media library.', 'connector-for-propstack' ),
				/* translators: %1$s will be replaced by a link to the settings. */
				sprintf( __( '<strong>Start the import manually:</strong> under %1$s you can import the images immediately.', 'connector-for-propstack' ), $this->get_settings_link( __( 'Imports > Images', 'connector-for-propstack' ), 'propstack_connector_import', 'propstack_connector_files_import' ) ),
				__( '<strong>Check the queue:</strong> it must be enabled. Each file is tried up to 3 times. Files which failed every time stay in the queue for 30 days, so you can see them there.', 'connector-for-propstack' ),
				/* translators: %1$s will be replaced by a link to the log. */
				sprintf( __( '<strong>Check %1$s:</strong> it names the reason for each file, e.g. a failed download, a file which is too large or missing write permissions in the upload directory of your WordPress.', 'connector-for-propstack' ), $this->get_settings_link( __( 'the log', 'connector-for-propstack' ), 'propstack_connector_logs' ) ),
				/* translators: %1$s will be replaced by a link to the settings. */
				sprintf( __( '<strong>Private images:</strong> images which are marked as private or as not intended for the exposee in Propstack are never imported. If you <a href="%1$s">enable debug mode for imports</a>, the log names each of these images with the reason after the next import.', 'connector-for-propstack' ), esc_url( \ConnectorForPropstack\Plugin\Settings::get_instance()->get_url( 'propstack_connector_advanced', 'propstack_connector_advanced_plugin_settings' ) ) ),
			)
		);

		// return the resulting text.
		return $text;
	}
}

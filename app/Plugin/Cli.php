<?php
/**
 * File for WP CLI commands of this plugin.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Files;
use ConnectorForPropstack\Propstack\ImmoObjects;

/**
 * Handler for commands to interact with Propstack.
 */
class Cli {
	/**
	 * Import all objects from Propstack to WordPress.
	 *
	 * Hint: run with "--user=xy".
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function import_objects(): void {
		// import the objects.
		$import_obj = ImmoObjects::get_instance()->import();

		// show errors if any occurred.
		if ( $import_obj->has_errors() ) {
			$errors = '';
			foreach ( $import_obj->get_errors() as $error ) {
				$text = (string) preg_replace(
					array(
						'/<br\s*\/?>/i',
						'/<\/p>/i',
						'/<\/li>/i',
						'/<\/ul>/i',
					),
					"\n",
					$error->get_error_message()
				);
				$text = wp_strip_all_tags( $text ) . "\n";

				// decode html entities.
				$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5 );

				// clean multiple newlines.
				$text = preg_replace( "/\n{2,}/", "\n\n", $text );

				// add to the list of errors.
				$errors .= $text;
			}

			// output success-message.
			\WP_CLI::error( 'Following errors occurred: ' . trim( $errors ) );
		}

		// output success-message.
		\WP_CLI::success( 'Objects has been imported.' );
	}

	/**
	 * Delete all objects from Propstack in WordPress.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function delete_objects(): void {
		// get the "immo objects" object.
		$immo_objects_object = ImmoObjects::get_instance();

		// delete them.
		$immo_objects_object->delete_all();

		// show error if objects still exist.
		if ( $immo_objects_object->has_objects() ) {
			\WP_CLI::error( 'Deletion was not successfully!' );
		}

		// output success-message.
		\WP_CLI::success( 'Objects has been deleted.' );
	}

	/**
	 * Delete all imported files for objects from Propstack.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function delete_imported_files(): void {
		Files::get_instance()->delete_all();

		// output success-message.
		\WP_CLI::success( 'Imported files has been deleted.' );
	}

	/**
	 * Process the queue to import files for objects from Propstack.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function process_queue(): void {
		// set the limit for requesting of queue entries to unlimited.
		add_filter(
			'cfprop_queue_query',
			static function ( array $query ): array {
				$query['posts_per_page'] = -1;
				return $query;
			}
		);

		// process the queue.
		\ConnectorForPropstack\Propstack\Queue::get_instance()->process();

		// output success-message.
		\WP_CLI::success( 'Queue has been processed.' );
	}

	/**
	 * Clear the queue.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function clear_queue(): void {
		\ConnectorForPropstack\Propstack\Queue::get_instance()->clear();

		// output success-message.
		\WP_CLI::success( 'Queue has been cleared.' );
	}

	/**
	 * Resets all settings of this plugin.
	 *
	 * @since        1.0.0
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function reset_plugin(): void {
		Uninstaller::get_instance()->run();

		// run installer tasks.
		Installer::get_instance()->activation();

		// output success-message.
		\WP_CLI::success( 'Plugin has been reset.' );
	}

	/**
	 * Import all files from Propstack to WordPress.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function import_files(): void {
		// remove the limit.
		add_filter(
			'cfprop_files_import_limit',
			static function () {
				return -1;
			}
		);

		// import the files.
		Files::get_instance()->import();

		// output success-message.
		\WP_CLI::success( 'Files have been imported.' );
	}
}

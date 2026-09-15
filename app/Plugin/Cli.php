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
		// run the import until it is completed.
		$runs = 0;
		do {
			$import_obj = ImmoObjects::get_instance()->import( '' );
			++$runs;
			if ( $runs > 10000 ) {
				\WP_CLI::error( 'The import did not finish after 10000 runs and has been stopped.' );
			}
		} while ( $import_obj->has_load_more() );

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
		$immo_objects_object->delete_all( '' );

		// show error if objects still exist.
		if ( $immo_objects_object->has_objects() ) {
			\WP_CLI::error( 'Deletion was not successful!' );
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
		Files::get_instance()->delete_all( '' );

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
		\ConnectorForPropstack\Propstack\Queue::get_instance()->process( '' );

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
	 * Reset the state of a running or aborted object import.
	 *
	 * Removes the work list including all of its blocks, the position and the running
	 * marker, so the next import starts from scratch. The already imported objects are
	 * kept. Use "--with-hashes" to also drop the md5 hashes, otherwise unchanged
	 * languages are skipped on the next run.
	 *
	 * ## OPTIONS
	 *
	 * [--with-hashes]
	 * : Also remove the md5 hashes so the next import is performed in any case.
	 *
	 * @since 1.1.0
	 *
	 * @param array<int,string>    $args       The arguments.
	 * @param array<string,string> $assoc_args The associative arguments.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function reset_import( array $args = array(), array $assoc_args = array() ): void {
		global $wpdb;

		// get the option names from the import object.
		$import_obj       = new \ConnectorForPropstack\Propstack\Imports\v1\Objects();
		$work_list_option = $import_obj->get_work_list_option();
		$offset_option    = $import_obj->get_offset_option();

		// remove the work list including every block, also if the amount of blocks is unknown.
		$options = Db::get_instance()->get_results(
			$wpdb->prepare(
				'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name = %s OR option_name LIKE %s',
				$work_list_option,
				$wpdb->esc_like( $work_list_option . '_block_' ) . '%'
			)
		);

		// delete them.
		$removed = 0;
		foreach ( $options as $option ) {
			if ( ! isset( $option['option_name'] ) ) {
				continue;
			}

			delete_option( (string) $option['option_name'] );

			++$removed;
		}

		// remove the position.
		delete_option( $offset_option );

		// remove the running marker.
		update_option( CFPROP_IMPORT_RUNNING, 0 );

		// remove the md5 hashes if requested.
		if ( isset( $assoc_args['with-hashes'] ) ) {
			$hashes = Db::get_instance()->get_results(
				$wpdb->prepare(
					'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE %s',
					$wpdb->esc_like( 'cfprop_md5_' ) . '%'
				)
			);

			foreach ( $hashes as $hash ) {
				if ( ! isset( $hash['option_name'] ) ) {
					continue;
				}

				delete_option( (string) $hash['option_name'] );

				++$removed;
			}
		}

		// output success-message.
		\WP_CLI::success( sprintf( 'Import state has been reset, %1$d options removed.', $removed ) );
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

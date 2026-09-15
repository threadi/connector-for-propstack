<?php
/**
 * File to handle the import of objects from Propstack via API v2.
 *
 * @source https://api.propstack.de/docs/index.html
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Imports\v2;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Languages;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Plugin\ProcessHandler;
use ConnectorForPropstack\Plugin\Settings;
use ConnectorForPropstack\Propstack\ApiRequest;
use ConnectorForPropstack\Propstack\ImmoObjects;
use ConnectorForPropstack\Propstack\Import_Base;
use ConnectorForPropstack\Propstack\PostTypes\ImmoObject;
use Throwable;
use WP_Error;
use WP_Query;

/**
 * Object to import objects from Propstack API.
 */
class Objects extends Import_Base {
	/**
	 * The URL of the Propstack API to import object.
	 *
	 * @var string
	 */
	private string $url = 'https://api.propstack.de/v2/properties';

	/**
	 * Initialize this object.
	 */
	public function __construct() {}

	/**
	 * Process the import of objects from Propstack.
	 *
	 * Hint:
	 * As this takes some time and to prevent timeouts, we use a paginated AJAX-based dialog.
	 * We import X objects per request and mark the import with "load_more" to create a new
	 * request. The work list is built once and stored in blocks, the position of the
	 * running import is kept in a second option.
	 *
	 * @return void
	 */
	public function run(): void {
		// get the work list of a paginated import which is already in progress.
		$import_data  = get_option( $this->work_list_option, array() );
		$is_first_run = empty( $import_data );

		// discard the state of an import which was aborted more than an hour ago.
		if ( ! $is_first_run && ( time() - absint( get_option( CFPROP_IMPORT_RUNNING, 0 ) ) ) > HOUR_IN_SECONDS ) {
			$this->clear_work_list();

			$import_data  = array();
			$is_first_run = true;
		}

		// bail if an import is still running - but not if this is the continuation of a paginated run.
		if ( $is_first_run && Helper::is_process_running( CFPROP_IMPORT_RUNNING ) ) {
			// add the error.
			$this->add_error( 'propstack_object_import_is_running', __( 'Import of objects is still running. Please wait.', 'connector-for-propstack' ) );

			// log the errors.
			$this->save_errors_in_log();

			// do nothing more.
			return;
		}

		// bail if the deletion is still running.
		if ( Helper::is_process_running( CFPROP_DELETE_RUNNING ) ) {
			// add the error.
			$this->add_error( 'propstack_object_deletion_is_running', __( 'Deletion of objects is still running. Please wait.', 'connector-for-propstack' ) );

			// log the errors.
			$this->save_errors_in_log();

			// do nothing more.
			return;
		}

		// get the process handler and set the process ID.
		$process_handler = ProcessHandler::get_instance();
		$process_handler->set_id( $this->get_process_id() );

		// get the enabled languages.
		$languages = array( get_option( 'propstack_connector_languages' ) => 1 );

		// if no language is set, use the fallback language.
		if ( empty( get_option( 'propstack_connector_languages' ) ) ) {
			$languages = array( Languages::get_instance()->get_fallback_language_name() => 1 );
		}

		$instance = $this;

		// run the preparations only on the first run of a paginated import.
		if ( $is_first_run ) {
			/**
			 * Run additional tasks before starting the import of objects.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 * @param ProcessHandler                                   $process_handler The process handler.
			 * @param Import_Base $instance        The import object.
			 */
			do_action( 'cfprop_import_object_before_start', $process_handler, $instance );

			// bail if any error occurred before.
			if ( $this->has_errors() ) {
				// log the errors.
				$this->save_errors_in_log();

				// do nothing more.
				$process_handler->set_running( 0 );

				// do nothing more.
				return;
			}

			// set these values on every run.
			$process_handler->set_status( __( 'Import of objects starting', 'connector-for-propstack' ) );
			$process_handler->set_running( time() );

			// add a log entry.
			Log::get_instance()->add( __( 'Import of objects has started.', 'connector-for-propstack' ), 'success', 'import' );

			// set initial value.
			$process_handler->set_count( 0 );
			$process_handler->set_max_count( 0 );
			update_option( CFPROP_IMPORT_RUNNING, time() );
		}

		// add a log entry.
		Log::get_instance()->add( __( 'Import of objects has running.', 'connector-for-propstack' ), 'info', 'import' );

		// if any error occurred during import of objects, collect and log it.
		try {
			// no limit on the command line, there is no timeout to avoid there.
			$limit = Helper::is_cli() ? 0 : absint( get_option( 'propstack_connector_ajax_object_limit' ) );

			/**
			 * Filter the limit of objects to import per request.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 * @param int $limit The limit.
			 */
			$limit = apply_filters( 'cfprop_object_import_limit', $limit );

			// build the work list on the first run of a paginated import.
			if ( $is_first_run ) {
				$import_data = array(
					'objects' => array(),
					'md5'     => array(),
					'skipped' => array(),
					'phase'   => 'import',
					'run_id'  => time(),
				);

				// the block size is a storage decision and must not depend on the request limit.
				$block_size = $limit > 0 ? $limit : 100;

				/**
				 * Filter the amount of objects stored in a single block of the work list.
				 *
				 * @since 1.0.0 Available since 1.0.0.
				 * @param int $block_size The block size.
				 */
				$block_size = max( 1, absint( apply_filters( 'cfprop_object_import_block_size', $block_size ) ) );

				// count across all languages, the blocks are numbered globally.
				$block_index   = 0;
				$total_objects = 0;
				$total_blocks  = 0;
				$buffer        = array();

				// loop through each enabled language and collect its objects.
				foreach ( $languages as $language_code => $language_enabled ) {
					$page_hashes      = array();
					$language_objects = 0;

					foreach ( $this->get_object_pages( $language_code ) as $page_hash => $page_objects ) {
						// remember the hash of this page for the overall hash.
						$page_hashes[] = $page_hash;

						/**
						 * Filter the response data from Propstack.
						 *
						 * Hint: this filter now receives a single page instead of all objects.
						 *
						 * @since 1.0.0 Available since 1.0.0.
						 * @param array<int,mixed> $page_objects The objects of this page.
						 */
						$page_objects = apply_filters( 'cfprop_object_import_response', $page_objects );

						foreach ( $page_objects as $object ) {
							// skip objects which would be prevented anyway.
							if ( apply_filters( 'cfprop_prevent_import_of_object', false, $object ) ) {
								continue;
							}

							$buffer[] = array(
								'language_code' => $language_code,
								'object'        => $object,
							);

							// count it for the overall progress and for this language.
							++$total_objects;
							++$language_objects;

							// write the buffer as soon as it holds a full block.
							if ( count( $buffer ) >= $block_size ) {
								if ( ! update_option( $this->work_list_option . '_block_' . $block_index, $buffer, false ) ) {
									$this->add_error(
										'propstack_object_import_block_not_saved',
										/* translators: %1$d will be replaced by the block number. */
										sprintf( __( 'Block %1$d of the import list could not be saved. The import has been aborted.', 'connector-for-propstack' ), $block_index )
									);

									return;
								}

								++$block_index;
								$buffer = array();
							}
						}

						// free this page.
						unset( $page_objects );
					}

					// bail if this language delivered nothing.
					if ( 0 === $language_objects ) {
						continue;
					}

					// build the overall hash from the page hashes.
					$md5 = md5( implode( '', $page_hashes ) );

					// remember the hash and the skip counter to handle them after this language is completed.
					$import_data['md5'][ $language_code ]     = $md5;
					$import_data['skipped'][ $language_code ] = 0;
				}

				// write the remaining objects of the last block.
				if ( ! empty( $buffer ) ) {
					if ( ! update_option( $this->work_list_option . '_block_' . $block_index, $buffer, false ) ) {
						$this->add_error(
							'propstack_object_import_block_not_saved',
							/* translators: %1$d will be replaced by the block number. */
							sprintf( __( 'Block %1$d of the import list could not be saved. The import has been aborted.', 'connector-for-propstack' ), $block_index )
						);

						return;
					}

					++$block_index;
				}

				// remember the amount of blocks.
				$total_blocks = $block_index;

				// free the buffer.
				unset( $buffer );

				// keep only the small metadata in the main option.
				$import_data['total']      = $total_objects;
				$import_data['blocks']     = $total_blocks;
				$import_data['block_size'] = $block_size;
				$import_data['objects']    = array();

				// save the metadata and reset the position.
				update_option( $this->work_list_option, $import_data );
				update_option( $this->offset_option, 0 );

				// update the markers for the complete run.
				$this->set_max_count( $process_handler, absint( $import_data['total'] ) );
				$this->set_new_status( $process_handler, __( 'Import of objects is running', 'connector-for-propstack' ) );

				// add a log entry.
				Log::get_instance()->add( __( 'Import of objects is running', 'connector-for-propstack' ), 'info', 'import' );
			}

			// run the cleanup in chunks, it can affect thousands of objects.
			if ( 'cleanup' === ( ! empty( $import_data['phase'] ) ? $import_data['phase'] : '' ) ) {
				// update the status.
				$this->set_new_status( $process_handler, __( 'Get the objects in tip-top shape', 'connector-for-propstack' ) );

				// get the objects which are not part of this import run.
				$query = array(
					'post_type'      => ImmoObject::get_instance()->get_name(),
					'post_status'    => 'any',
					'posts_per_page' => $limit > 0 ? $limit : -1,
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup; sync context.
						'relation' => 'OR',
						array(
							'key'     => 'changed',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'changed',
							'value'   => absint( $import_data['run_id'] ),
							'compare' => '!=',
						),
					),
					'fields'         => 'ids',
					'no_found_rows'  => true,
				);

				$results  = new WP_Query( $query );
				$post_ids = $results->get_posts();

				// delete them.
				foreach ( $post_ids as $post_id ) {
					// bail if this is not an ID.
					if ( ! is_int( $post_id ) ) {
						continue;
					}

					/**
					 * Filter whether an obsolete object is deleted permanently.
					 *
					 * @since 1.0.0 Available since 1.0.0.
					 * @param bool $force_delete True to bypass the trash.
					 * @param int  $post_id      The post-ID of the object.
					 */
					wp_delete_post( $post_id, apply_filters( 'cfprop_force_delete_obsolete_object', true, $post_id ) );

					// update the counter for the progress bar.
					$process_handler->set_count( $process_handler->get_count() + 1 );
				}

				// another run is needed if this chunk was full.
				if ( $limit > 0 && count( $post_ids ) >= $limit ) {
					$this->set_load_more( true );

					// save the state and stop here.
					update_option( $this->work_list_option, $import_data );

					// do nothing more.
					return;
				}
			} else {
				// get the name of the post-type to use.
				$post_type_name = ImmoObject::get_instance()->get_name();

				// get the position to continue from.
				$offset = absint( get_option( $this->offset_option, 0 ) );

				// determine which block this run has to process.
				$block_size  = max( 1, absint( $import_data['block_size'] ) );
				$block_index = intdiv( $offset, $block_size );

				// load only this block of the work list.
				$block = get_option( $this->work_list_option . '_block_' . $block_index, array() );

				// bail if the block is gone - continuing would loop forever.
				if ( empty( $block ) && absint( $import_data['total'] ) > $offset ) {
					$this->add_error(
						'propstack_object_import_block_missing',
						/* translators: %1$d will be replaced by the block number. */
						sprintf( __( 'Block %1$d of the import list is missing. The import has been aborted.', 'connector-for-propstack' ), $block_index )
					);

					$this->set_load_more( false );

					$this->clear_work_list();
				}

				// count the objects processed in this run.
				$counter = 0;

				// no time budget on the command line or in cron, there is no proxy which gives up.
				$time_budget = ( Helper::is_cli() || wp_doing_cron() ) ? 0 : 20;

				/**
				 * Filter the time budget in seconds for a single import request.
				 *
				 * @since 1.1.0 Available since 1.1.0.
				 * @param int $time_budget The budget in seconds.
				 */
				$time_budget = absint( apply_filters( 'cfprop_object_import_time_budget', $time_budget ) );

				// remember the state at the start of this chunk.
				$chunk_queries = get_num_queries();
				$chunk_start   = microtime( true );

				// show cli hint.
				$progress = Helper::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Import objects', absint( $import_data['total'] ) ) : false;

				// loop through this block, starting where the last run stopped.
				foreach ( $block as $index_in_block => $entry ) {
					// skip what a previous run already processed inside this block.
					if ( ( $block_index * $block_size + $index_in_block ) < $offset ) {
						continue;
					}

					// stop this run if the time budget is used up, the request will be restarted.
					if ( $time_budget > 0 && $counter > 0 && ! Helper::is_cli() && ! wp_doing_cron() && ( microtime( true ) - $chunk_start ) > $time_budget ) {
						$this->set_load_more( true );
						break;
					}

					// get the data of this entry.
					$language_code = $entry['language_code'];
					$object        = $entry['object'];

					try {
						$prevent_import = false;
						/**
						 * Prevent import of this object under custom conditions.
						 *
						 * @since        1.0.0 Available since 1.0.0.
						 *
						 * @param bool  $prevent_import True to prevent the import.
						 * @param array $object         The object data from API.
						 *
						 * @noinspection PhpConditionAlreadyCheckedInspection
						 */
						if ( apply_filters( 'cfprop_prevent_import_of_object', $prevent_import, $object ) ) {
							// update the counter.
							$this->set_count( $process_handler, $process_handler->get_count() + 1 );

							// add a log entry.
							/* translators: %1$s will be replaced by the object title. */
							Log::get_instance()->add( sprintf( __( 'Import of object %1$s prevented.', 'connector-for-propstack' ), '<em>' . $object['title'] . '</em>' ), 'info', 'import' );

							// update tick.
							if ( $progress ) {
								$progress->tick(); }

							// do nothing more.
							continue;
						}

						// update the status.
						/* translators: %1$s will be replaced by the object title. */
						$this->set_new_status( $process_handler, sprintf( __( 'Import of object %1$s', 'connector-for-propstack' ), '<em>' . $object['title'] . '</em>' ) );

						// add a log entry.
						/* translators: %1$s will be replaced by the object title. */
						Log::get_instance()->add( sprintf( __( 'Import of object %1$s', 'connector-for-propstack' ), '<em>' . $object['title'] . '</em>' ), 'info', 'import' );

						// get the object with the given ID.
						$propstack_immo_object = ImmoObjects::get_instance()->get_object_by_object_id( $object['id'], $language_code );

						// if the object does not exist, create it.
						if ( ! $propstack_immo_object instanceof \ConnectorForPropstack\Propstack\ImmoObject ) {
							// add a log entry.
							/* translators: %1$s will be replaced by the object title. */
							Log::get_instance()->add( sprintf( __( 'Creating new entry for the object %1$s', 'connector-for-propstack' ), '<em>' . $object['title'] . '</em>' ), 'info', 'import' );

							// prepare the query to insert a new object.
							$query = array(
								'post_type'    => $post_type_name,
								'post_title'   => (string) $object['title'],
								'post_status'  => 'publish',
								'post_author'  => Helper::get_author_during_object_creation(),
								'post_content' => '',
							);

							/**
							 * Filter the query to add a new object during import.
							 *
							 * @since 1.0.0 Available since 1.0.0.
							 *
							 * @param array<string,mixed> $query  The query.
							 * @param array<string,mixed> $object The object data from API.
							 */
							$query = apply_filters( 'cfprop_new_object_query', $query, $object );

							// add the object.
							$post_id = wp_insert_post( $query, true );

							// bail if inserting failed.
							if ( $post_id instanceof WP_Error ) { // @phpstan-ignore instanceof.alwaysFalse
								// save the error.
								$this->add_error( 'propstack_object_could_not_be_saved', __( 'New object could not be created. The following error occurred:', 'connector-for-propstack' ) . ' <code>' . wp_json_encode( $post_id ) . '</code>' );

								// update the counter.
								$this->set_count( $process_handler, $process_handler->get_count() + 1 );

								// do nothing more.
								continue;
							}
							$is_new_object = true;
						} else {
							$post_id       = $propstack_immo_object->get_id();
							$is_new_object = false;
						}

						/**
						 * Run additional tasks for a single language-specific object import.
						 *
						 * @since 1.0.0 Available since 1.0.0.
						 *
						 * @param array<string,mixed> $object        The object data from API.
						 * @param int                 $post_id       The post-ID of the object.
						 * @param string              $language_code The used language.
						 * @param bool                $is_new_object Marker for new objects.
						 */
						do_action( 'cfprop_import_object', $object, $post_id, $language_code, $is_new_object );

						// set the language.
						update_post_meta( $post_id, 'language_code', $language_code );

						// mark the object as changed.
						update_post_meta( $post_id, 'changed', absint( $import_data['run_id'] ) );

						// update the counter.
						$this->set_count( $process_handler, $process_handler->get_count() + 1 );

						// free the object cache of this object.
						clean_post_cache( $post_id );

						// update tick.
						if ( $progress ) {
							$progress->tick(); }
					} catch ( Throwable $e ) {
						// count this object as skipped for its language.
						if ( isset( $import_data['skipped'][ $language_code ] ) ) {
							++$import_data['skipped'][ $language_code ];
						}

						// log this event with the object which caused it.
						Log::get_instance()->add(
							sprintf(
							/* translators: %1$s will be replaced by the object title, %2$d by its Propstack-ID. */
								__( 'Following error occurred during the import of object %1$s (Propstack-ID %2$d). The object has been skipped.', 'connector-for-propstack' ),
								'<em>' . esc_html( $object['title'] ?? '' ) . '</em>',
								absint( $object['id'] ?? 0 )
							) . '<br>' . Helper::get_throwable_as_log_text( $e ),
							'error',
							'import'
						);

						// mark the import as faulty so the error dialog is shown.
						$this->add_error(
							'propstack_object_import_error',
							/* translators: %1$s will be replaced by a URL. */
							sprintf( __( 'At least one object could not be imported. Check <a href="%1$s">the log</a> for details.', 'connector-for-propstack' ), esc_url( Settings::get_instance()->get_url( 'propstack_connector_logs' ) ) )
						);

						// update the counter and the progress for the skipped object.
						$this->set_count( $process_handler, $process_handler->get_count() + 1 );
						if ( $progress ) {
							$progress->tick(); }
					} finally {
						// remember the position for the next run, also if the object above used "continue".
						++$counter;
						update_option( $this->offset_option, $offset + $counter, false );
					}
				}

				// set finished.
				if ( $progress ) {
					$progress->finish();
				}

				// add a log entry.
				if ( Helper::is_development_mode() ) {
					Log::get_instance()->add(
						sprintf(
							'Chunk done: %1$d objects, %2$d queries, %3$s memory of %4$s, %5$.1f seconds',
							$counter,
							get_num_queries() - $chunk_queries,
							size_format( memory_get_peak_usage( true ) ),
							ini_get( 'memory_limit' ),
							microtime( true ) - $chunk_start
						),
						'info',
						'system'
					);
				}

				// free the memory of this block.
				unset( $block );

				// another run is needed as long as objects are left.
				if ( ( $offset + $counter ) < absint( $import_data['total'] ) ) {
					$this->set_load_more( true );
				}

				// remove this block only if every entry of it has been processed.
				if ( ( $offset + $counter ) >= ( ( $block_index + 1 ) * $block_size ) || ( $offset + $counter ) >= absint( $import_data['total'] ) ) {
					delete_option( $this->work_list_option . '_block_' . $block_index );
				}

				// stop here if another run is needed to complete this import.
				if ( $this->has_load_more() ) {
					// save the current state of the meta data.
					update_option( $this->work_list_option, $import_data );

					// do nothing more.
					return;
				}

				// switch to the cleanup phase if this import delivered objects and ran without errors.
				if ( absint( $import_data['total'] ) > 0 && ! $this->has_errors() ) {
					$import_data['phase'] = 'cleanup';

					// count the objects which have to be removed, to show a progress for this phase.
					$obsolete = new WP_Query(
						array(
							'post_type'      => ImmoObject::get_instance()->get_name(),
							'post_status'    => 'any',
							'posts_per_page' => 1,
							'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup; sync context.
								'relation' => 'OR',
								array(
									'key'     => 'changed',
									'compare' => 'NOT EXISTS',
								),
								array(
									'key'     => 'changed',
									'value'   => absint( $import_data['run_id'] ),
									'compare' => '!=',
								),
							),
							'fields'         => 'ids',
						)
					);

					// use the amount as the new maximum for the progress bar.
					$process_handler->set_max_count( absint( $obsolete->found_posts ) );
					$process_handler->set_count( 0 );

					$this->set_load_more( true );

					// save the state and stop here.
					update_option( $this->work_list_option, $import_data );

					// do nothing more.
					return;
				}
			}

			// the import is completed - run the tasks for each imported language now.
			foreach ( array_keys( $import_data['md5'] ) as $language_code ) {
				/**
				 * Run additional tasks for importing objects in a given language.
				 *
				 * @since 1.0.0 Available since 1.0.0.
				 *
				 * @param string $language_code The used language.
				 */
				do_action( 'cfprop_import_language', (string) $language_code );

				// save the md5 hash only if all objects of this language could be imported.
				if ( 0 === $import_data['skipped'][ $language_code ] ) {
					update_option( 'cfprop_md5_' . $language_code, $import_data['md5'][ $language_code ] );
				}
			}

			// clear the work list as the import is completed.
			$this->clear_work_list();

			// set the result.
			if ( $this->has_errors() ) {
				/**
				 * Run additional tasks if any error occurred during import of objects.
				 * …
				 */
				do_action( 'cfprop_import_object_errors', $instance );

				// report the errors.
				$process_handler->set_message( $this->get_error_dialog_config() );
			} elseif ( 0 === absint( $import_data['total'] ) ) {
				/**
				 * Run additional tasks if the import did not deliver any object.
				 *
				 * @since 1.1.0 Available since 1.1.0.
				 *
				 * @param Objects $instance The import object.
				 */
				do_action( 'cfprop_import_object_empty', $instance );

				// report that nothing has been imported.
				$process_handler->set_message( $this->get_empty_dialog_config() );
			} else {
				/**
				 * Run additional tasks after successful import of objects.
				 *
				 * @since 1.0.0 Available since 1.0.0.
				 *
				 * @param Objects $instance The import object.
				 */
				do_action( 'cfprop_import_object_success', $instance );

				// report the success.
				$process_handler->set_message( $this->get_success_dialog_config() );
			}
		} catch ( Throwable $e ) {
			// log this event.
			Log::get_instance()->add(
				__( 'Following error occurred during the import of objects via API v2:', 'connector-for-propstack' )
				. '<br>' . Helper::get_throwable_as_log_text( $e ),
				'error',
				'import'
			);

			// show hint.
			/* translators: %1$s will be replaced by a URL. */
			$this->add_error( 'propstack_object_import_error', sprintf( __( 'Error occurred. Check <a href="%1$s">the log</a> for details.', 'connector-for-propstack' ), esc_url( Settings::get_instance()->get_url( 'propstack_connector_logs' ) ) ) );

			// clear the work list as this import cannot be continued.
			$this->clear_work_list();

			// make sure the import is not marked as continuable.
			$this->set_load_more( false );
		} finally {
			// keep the markers as they are if another run is needed to complete this import.
			if ( ! $this->has_load_more() ) {
				/**
				 * Run additional tasks after any import of objects.
				 *
				 * @since 1.0.0 Available since 1.0.0.
				 *
				 * @param Objects $instance The import object.
				 */
				do_action( 'cfprop_import_object_after', $instance );

				// log the errors.
				$this->save_errors_in_log();

				// add a log entry.
				Log::get_instance()->add( __( 'Import of objects has been ended.', 'connector-for-propstack' ), 'success', 'import' );

				// update the running marker.
				$process_handler->set_running( 0 );
				update_option( CFPROP_IMPORT_RUNNING, 0 );
			}
		}
	}

	/**
	 * Return the API URL to import objects.
	 *
	 * @param string $language_code The language to use for the URL.
	 * @param int    $page The page to request.
	 * @param int    $per The amount of objects per page.
	 *
	 * @return string
	 */
	private function get_url( string $language_code, int $page = 1, int $per = 100 ): string {
		// get the URL.
		$url = add_query_arg(
			array(
				'locale'    => $language_code,
				'expand'    => 1,
				'archived'  => -1,
				'with_meta' => 1,
				'page'      => $page,
				'per'       => $per,
			),
			$this->url
		);

		/**
		 * Filter the URL of the API to import objects from Propstack.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param string $url The URL.
		 */
		return apply_filters( 'cfprop_api_object_url', $url );
	}

	/**
	 * Return a success dialog configuration.
	 *
	 * @return array<string,mixed>
	 */
	private function get_success_dialog_config(): array {
		return array(
			'detail' => array(
				'className' => 'cfprop-dialog',
				'title'     => __( 'Import of objects has been run', 'connector-for-propstack' ),
				'texts'     => array(
					'<p><strong>' . __( 'The import of objects from your Propstack account has been run.', 'connector-for-propstack' ) . '</strong></p>',
					'<p>' . __( 'You will find them in the list in the backend and your frontend.', 'connector-for-propstack' ) . '</p>',
					'<p>' . __( 'Please note that any files for the objects are imported later. However, you can also trigger their import directly from the object itself.', 'connector-for-propstack' ) . '</p>',
				),
				'buttons'   => array(
					array(
						'action'  => 'location.reload();',
						'variant' => 'primary',
						'text'    => __( 'OK', 'connector-for-propstack' ),
					),
				),
			),
		);
	}

	/**
	 * Set max count.
	 *
	 * @param ProcessHandler $process_handler The process handler.
	 * @param int            $count The new max count.
	 *
	 * @return void
	 */
	private function set_max_count( ProcessHandler $process_handler, int $count ): void {
		$process_handler->set_max_count( $count );

		/**
		 * Run additional tasks after setting the max count.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param int $count The max count.
		 */
		do_action( 'cfprop_import_object_set_max_count', $count );
	}

	/**
	 * Set count.
	 *
	 * @param ProcessHandler $process_handler The process handler.
	 * @param int            $count The new count.
	 *
	 * @return void
	 */
	private function set_count( ProcessHandler $process_handler, int $count ): void {
		$process_handler->set_count( $count );

		/**
		 * Run additional tasks after setting the max count.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param int $count The max count.
		 */
		do_action( 'cfprop_import_object_set_count', $count );
	}

	/**
	 * Set a new status (text).
	 *
	 * @param ProcessHandler $process_handler The process handler.
	 * @param string         $new_status The new status.
	 *
	 * @return void
	 */
	private function set_new_status( ProcessHandler $process_handler, string $new_status ): void {
		$process_handler->set_status( $new_status );

		/**
		 * Run additional tasks after setting the new status.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param string $new_status The new status.
		 */
		do_action( 'cfprop_import_object_set_status', $new_status );
	}

	/**
	 * Load the objects of a language page by page.
	 *
	 * Yields one page at a time so the caller never has to hold the complete result set
	 * in memory.
	 *
	 * @param string $language_code The language to load.
	 *
	 * @return \Generator<int,array<int,mixed>>
	 */
	private function get_object_pages( string $language_code ): \Generator {
		$max_per_value = 100;
		/**
		 * Filter the max. per page objects for every import from Propstack.
		 *
		 * @since 1.0.3 Available since 1.0.3.
		 * @param int $max_per_value The max per value.
		 */
		$per = min( 500, absint( apply_filters( 'cfprop_import_per_page', $max_per_value ) ) );

		$max_pages = 1000;
		/**
		 * Filter the max pages for every import from Propstack.
		 *
		 * @since 1.0.3 Available since 1.0.3.
		 * @param int $max_pages The max pages value.
		 */
		$max_pages = absint( apply_filters( 'cfprop_import_max_pages', $max_pages ) );

		$page               = 1;
		$total              = null;
		$collected          = 0;
		$previous_page_hash = '';

		do {
			// request one page.
			$request_object = new ApiRequest();
			$request_object->set_url( $this->get_url( $language_code, $page, $per ) );
			$request_object->set_post_data( '' );
			$request_object->set_method( 'GET' );
			$request_object->set_md5( md5( $this->get_url( $language_code, $page, $per ) ) );
			$request_object->set_header( $this->get_header() );
			$request_object->send();

			// bail on HTTP error.
			if ( 200 !== $request_object->get_http_status() ) {
				// save the error.
				$this->add_error( 'propstack_object_import_http_status', __( 'Propstack API answered with wrong HTTP-status:', 'connector-for-propstack' ) . ' <code>' . $request_object->get_http_status() . '</code>' );

				// do nothing more in this language.
				return;
			}

			// decode.
			$data = json_decode( $request_object->get_response(), true );
			if ( ! is_array( $data ) || ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
				Log::get_instance()->add( __( 'Error during decoding the API response.', 'connector-for-propstack' ), 'error', 'import' );
				return;
			}

			// read the total count once (from the first page).
			if ( null === $total && isset( $data['meta']['total_count'] ) ) {
				$total = absint( $data['meta']['total_count'] );
			}

			// stop on an empty page.
			if ( empty( $data['data'] ) ) {
				break;
			}

			// safeguard: stop if the API ignores pagination and repeats a page.
			$page_hash = md5( Helper::get_json( $data['data'] ) );
			if ( $page_hash === $previous_page_hash ) {
				break;
			}
			$previous_page_hash = $page_hash;

			// hand this page to the caller.
			$collected += count( $data['data'] );
			$data_count = count( $data['data'] );

			yield $page_hash => $data['data']; // @phpstan-ignore generator.keyType

			// free the page before requesting the next one.
			unset( $data, $request_object );

			++$page;
		} while (
			$page <= $max_pages
			&& (
				( null !== $total && $collected < $total )
				|| ( null === $total && $data_count >= $per )
			)
		);

		// safety check: warn if we ended up with fewer than reported.
		if ( null !== $total && $collected < $total && $page <= $max_pages ) {
			Log::get_instance()->add(
				sprintf(
				/* translators: %1$d received, %2$d total. */
					__( 'Only %1$d of %2$d objects were imported. The rest could not be loaded.', 'connector-for-propstack' ),
					$collected,
					$total
				),
				'error',
				'import'
			);
		}
	}

	/**
	 * Return a dialog configuration for an import which did not deliver any object.
	 *
	 * @return array<string,mixed>
	 */
	private function get_empty_dialog_config(): array {
		return array(
			'detail' => array(
				'className' => 'cfprop-dialog',
				'title'     => __( 'No objects have been imported', 'connector-for-propstack' ),
				'texts'     => array(
					'<p><strong>' . __( 'Your Propstack account did not deliver any object for the import.', 'connector-for-propstack' ) . '</strong></p>',
					/* translators: %1$s will be replaced by a URL. */
					'<p>' . sprintf( __( 'If you expected objects here, check <a href="%1$s">your import settings</a> - a restriction on marketing type or status can exclude all of them.', 'connector-for-propstack' ), esc_url( Settings::get_instance()->get_url( 'propstack_connector_import' ) ) ) . '</p>',
				),
				'buttons'   => array(
					array(
						'action'  => 'location.reload();',
						'variant' => 'primary',
						'text'    => __( 'OK', 'connector-for-propstack' ),
					),
				),
			),
		);
	}
}

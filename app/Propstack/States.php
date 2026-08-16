<?php
/**
 * File for handling object states from Propstack.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Taxonomies\Status;
use WP_Error;
use WP_Term_Query;

/**
 * Object to handle object states from Propstack.
 */
class States {

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?States
	 */
	private static ?States $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): States {
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
		// bail if not the v2 API is used.
		if ( 'v2' !== get_option( 'propstack_connector_api_version' ) ) {
			return;
		}

		// define constants.
		if ( ! defined( 'CFPROP_STATES_IMPORT_RUNNING' ) ) {
			define( 'CFPROP_STATES_IMPORT_RUNNING', 'propstack_connector_states_import_running' );
		}

		if ( ! defined( 'CFPROP_STATES_DELETE_RUNNING' ) ) {
			define( 'CFPROP_STATES_DELETE_RUNNING', 'propstack_connector_states_delete_running' );
		}

		// use hooks.
		add_action(
			'init',
			function () {
				remove_filter( 'cfprop_prevent_import_of_object', array( ImmoObjects::get_instance(), 'prevent_import_by_state' ) );
			}
		);
		add_filter( 'cfprop_prevent_import_of_object', array( $this, 'prevent_import_by_state' ), 10, 2 );
	}

	/**
	 * Return the term ID of the given state ID.
	 *
	 * @param int    $state_id The ID of the state.
	 * @param string $language_code The language code of the term.
	 *
	 * @return int|false
	 */
	public function get_term_id_by_id( int $state_id, string $language_code ): int|false {
		// bail if not the v2 API is used.
		if ( 'v2' !== get_option( 'propstack_connector_api_version' ) ) {
			return false;
		}

		// check if the given state ID exists.
		$query   = array(
			'taxonomy'   => Status::get_instance()->get_name(),
			'hide_empty' => false,
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary meta lookup; admin/sync context.
				'relation' => 'AND',
				array(
					'key'     => 'id',
					'value'   => $state_id,
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
	 * Delete all object state terms.
	 *
	 * @return void
	 */
	public function delete_all(): void {
		// get the terms of our taxonomy.
		$terms = get_terms(
			array(
				'taxonomy'   => Status::get_instance()->get_name(),
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		// bail on any error.
		if ( $terms instanceof WP_Error ) {
			return;
		}

		// delete them.
		foreach ( $terms as $term_id ) {
			wp_delete_term( $term_id, Status::get_instance()->get_name() );
		}
	}

	/**
	 * Prevent import of incomplete object data by its given state.
	 *
	 * @param bool                $prevent_import The marker to prevent import.
	 * @param array<string,mixed> $immo_object The object data.
	 *
	 * @return bool
	 */
	public function prevent_import_by_state( bool $prevent_import, array $immo_object ): bool {
		// bail if "property_status_id" (API v2) is set.
		if ( ! empty( $immo_object['property_status_id'] ) ) {
			return ImmoObjects::get_instance()->prevent_import_by_taxonomy( 'propstack_connector_import_states', (string) $immo_object['property_status_id'], $prevent_import );
		}

		// prevent the import if no state is set.
		return true;
	}
}

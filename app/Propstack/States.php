<?php
/**
 * File for handling object states from Propstack.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Languages;
use ConnectorForPropstack\Propstack\Taxonomies\Status;
use WP_Error;
use WP_Term;
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
		// prevent the import if no state is set.
		if ( empty( $immo_object['property_status_id'] ) ) {
			return true;
		}

		// get the state of this object.
		$property_status_id = (string) $immo_object['property_status_id'];

		// use the configured states, if any are configured.
		$import_states = get_option( 'propstack_connector_import_states' );
		if ( is_array( $import_states ) && ! empty( $import_states ) && ! ( isset( $import_states[0] ) && empty( $import_states[0] ) ) ) {
			return ImmoObjects::get_instance()->prevent_import_by_taxonomy( 'propstack_connector_import_states', $property_status_id, $prevent_import );
		}

		// without configured states, only objects in state "Vermarktung" are imported (same as with API v1).
		if ( 'Vermarktung' !== $this->get_state_name( $property_status_id ) ) {
			return true;
		}

		// return the value.
		return $prevent_import;
	}

	/**
	 * Return the name of the given state.
	 *
	 * The API v2 delivers the ID of the state. Its name is taken from the imported state terms.
	 *
	 * @param string $property_status_id The state as delivered by the API.
	 *
	 * @return string
	 */
	private function get_state_name( string $property_status_id ): string {
		// return the value as it is, if it is not an ID.
		if ( ! is_numeric( $property_status_id ) ) {
			return $property_status_id;
		}

		// get the term of this state.
		$term_id = $this->get_term_id_by_id( absint( $property_status_id ), Languages::get_instance()->get_import_language() );

		// bail if the state is unknown.
		if ( ! is_int( $term_id ) ) {
			return '';
		}

		// get the term object.
		$term = get_term( $term_id, Status::get_instance()->get_name() );

		// bail if this is not a term.
		if ( ! $term instanceof WP_Term ) {
			return '';
		}

		// return the name of the state.
		return $term->name;
	}
}

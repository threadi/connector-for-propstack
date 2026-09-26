<?php
/**
 * File for handling brokers from Propstack.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Log;
use WP_Error;

/**
 * Object to handle brokers from Propstack.
 */
class Broker {
	/**
	 * The already imported broker avatars of this run, indexed by broker ID.
	 *
	 * @var array<int,int>
	 */
	private array $broker_avatars = array();

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Broker
	 */
	private static ?Broker $instance = null;

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
	public static function get_instance(): Broker {
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
		// define constants.
		if ( ! defined( 'CFPROP_BROKER_IMPORT_RUNNING' ) ) {
			define( 'CFPROP_BROKER_IMPORT_RUNNING', 'propstack_connector_broker_import_running' );
		}
		if ( ! defined( 'CFPROP_BROKER_DELETE_RUNNING' ) ) {
			define( 'CFPROP_BROKER_DELETE_RUNNING', 'propstack_connector_broker_delete_running' );
		}

		// use hooks.
		add_action( 'cfprop_import_object', array( $this, 'import_broker_image' ), 20, 2 );
	}

	/**
	 * Delete all broker terms.
	 *
	 * @return void
	 */
	public function delete_all(): void {
		// get the terms of our taxonomy.
		$terms = get_terms(
			array(
				'taxonomy'   => Taxonomies\Broker::get_instance()->get_name(),
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
			wp_delete_term( $term_id, Taxonomies\Broker::get_instance()->get_name() );
		}
	}

	/**
	 * Import the broker image during import of objects.
	 *
	 * @param array<string,mixed> $immo_object The object data.
	 * @param int                 $post_id The post-ID of the object.
	 *
	 * @return void
	 */
	public function import_broker_image( array $immo_object, int $post_id ): void {
		// bail if no broker or image is set.
		if ( empty( $immo_object['broker'] ) || empty( $immo_object['broker']['avatar_url'] ) ) {
			return;
		}

		// get the assigned broker terms.
		$broker_terms = wp_get_object_terms( $post_id, Taxonomies\Broker::get_instance()->get_name() );

		// bail if no broker terms are assigned.
		if ( empty( $broker_terms ) || $broker_terms instanceof WP_Error ) {
			return;
		}

		// get the ID of the broker.
		$broker_id = absint( ! empty( $immo_object['broker']['id'] ) ? $immo_object['broker']['id'] : 0 );

		// bail if no broker ID is given.
		if ( 0 === $broker_id ) {
			return;
		}

		// bail if the term already has an existing thumbnail, the avatar does not have to be imported again.
		$thumbnail_id = absint( get_term_meta( $broker_terms[0]->term_id, 'thumbnail_id', true ) );
		if ( $thumbnail_id > 0 && 'attachment' === get_post_type( $thumbnail_id ) ) {
			// mark avatars imported by older versions as broker avatar to protect them from the cleanup of object files.
			if ( ! metadata_exists( 'post', $thumbnail_id, 'cfprop_broker_avatar' ) && absint( get_post_meta( $thumbnail_id, 'propstack_file_id', true ) ) === $broker_id ) {
				update_post_meta( $thumbnail_id, 'cfprop_broker_avatar', $broker_id );
			}
			return;
		}

		// import the avatar only once per broker and run.
		if ( isset( $this->broker_avatars[ $broker_id ] ) ) {
			$attachment_id = $this->broker_avatars[ $broker_id ];
		} else {
			$attachment_id                      = Files::get_instance()->import_file( 0, $broker_id, $immo_object['broker']['avatar_url'], basename( $immo_object['broker']['avatar_url'] ), array(), $broker_id );
			$this->broker_avatars[ $broker_id ] = $attachment_id;
		}

		// bail if no image has been imported.
		if ( 0 === $attachment_id ) {
			return;
		}

		// set the image like a thumbnail on the term.
		update_term_meta( $broker_terms[0]->term_id, 'thumbnail_id', $attachment_id );
	}
}

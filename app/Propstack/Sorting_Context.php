<?php
/**
 * File for the context of a sorting: the objects of the actual list.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Taxonomies\MarketingType;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base;

/**
 * Object which describes the objects of a list, e.g., to decide which sortings are usable for them.
 *
 * It is based on all objects of the list (with the active filters), not only on the objects of the actual page.
 */
class Sorting_Context {
	/**
	 * The post-IDs of the objects in the list.
	 *
	 * @var array<int,int>
	 */
	private array $post_ids;

	/**
	 * The loaded values per meta key.
	 *
	 * @var array<string,array<int,string>>
	 */
	private array $values = array();

	/**
	 * The marketing types (their API values, e.g. "BUY" or "RENT") of the objects in the list.
	 *
	 * @var array<int,string>|null
	 */
	private ?array $marketing_types = null;

	/**
	 * The object types of the objects in the list.
	 *
	 * @var array<string,Object_Type_Base>|null
	 */
	private ?array $object_types = null;

	/**
	 * Constructor for this object.
	 *
	 * @param array<int,int> $post_ids The post-IDs of the objects in the list.
	 */
	public function __construct( array $post_ids ) {
		$this->post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	}

	/**
	 * Return the post-IDs of the objects in the list.
	 *
	 * @return array<int,int>
	 */
	public function get_post_ids(): array {
		return $this->post_ids;
	}

	/**
	 * Return the number of objects in the list.
	 *
	 * @return int
	 */
	public function get_count(): int {
		return count( $this->post_ids );
	}

	/**
	 * Return the values of the given meta key for all objects in the list, which have a value for it.
	 *
	 * Uses a single database request per meta key.
	 *
	 * @param string $meta_key The meta key.
	 *
	 * @return array<int,string> List of values with the post-ID as key.
	 */
	public function get_values( string $meta_key ): array {
		// use the loaded values.
		if ( isset( $this->values[ $meta_key ] ) ) {
			return $this->values[ $meta_key ];
		}

		// bail if no objects are in the list.
		if ( empty( $this->post_ids ) || '' === $meta_key ) {
			return array();
		}

		global $wpdb;

		// get the values of all objects in a single request.
		$placeholders = implode( ',', array_fill( 0, count( $this->post_ids ), '%d' ) );
		$results      = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT `post_id`, `meta_value` FROM `$wpdb->postmeta` WHERE `meta_key` = %s AND `meta_value` <> '' AND `post_id` IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				array_merge( array( $meta_key ), $this->post_ids )
			),
			ARRAY_A
		);

		// collect them.
		$values = array();
		foreach ( (array) $results as $result ) {
			if ( ! is_array( $result ) || ! isset( $result['post_id'], $result['meta_value'] ) ) {
				continue;
			}
			$values[ absint( $result['post_id'] ) ] = (string) $result['meta_value'];
		}

		// save and return them.
		$this->values[ $meta_key ] = $values;
		return $values;
	}

	/**
	 * Return the marketing types (their API values, e.g. "BUY" or "RENT") of the objects in the list.
	 *
	 * @return array<int,string>
	 */
	public function get_marketing_types(): array {
		// use the loaded list.
		if ( is_array( $this->marketing_types ) ) {
			return $this->marketing_types;
		}

		// collect the API values of the used terms.
		$this->marketing_types = array();
		foreach ( $this->get_terms( MarketingType::get_instance()->get_name() ) as $term ) {
			$api = get_term_meta( $term->term_id, 'api', true );
			if ( is_string( $api ) && '' !== $api ) {
				$this->marketing_types[] = strtoupper( $api );
			}
		}
		$this->marketing_types = array_values( array_unique( $this->marketing_types ) );

		// return the list.
		return $this->marketing_types;
	}

	/**
	 * Return the only marketing type of the objects in the list ("BUY" or "RENT").
	 *
	 * Returns an empty string, if the list contains objects of more than one marketing type or none.
	 *
	 * @return string
	 */
	public function get_single_marketing_type(): string {
		$marketing_types = $this->get_marketing_types();
		return 1 === count( $marketing_types ) ? $marketing_types[0] : '';
	}

	/**
	 * Return the object types of the objects in the list.
	 *
	 * @return array<string,Object_Type_Base>
	 */
	public function get_object_types(): array {
		// use the loaded list.
		if ( is_array( $this->object_types ) ) {
			return $this->object_types;
		}

		// collect the object types of the used terms.
		$this->object_types = array();
		foreach ( $this->get_terms( ObjectType::get_instance()->get_name() ) as $term ) {
			$object_type = ObjectType::get_instance()->get_object_type_by_term( $term );
			if ( $object_type instanceof Object_Type_Base ) {
				$this->object_types[ $object_type->get_slug() ] = $object_type;
			}
		}

		// return the list.
		return $this->object_types;
	}

	/**
	 * Return the terms of the given taxonomy, which are used by the objects in the list.
	 *
	 * @param string $taxonomy The taxonomy.
	 *
	 * @return array<int,\WP_Term>
	 */
	private function get_terms( string $taxonomy ): array {
		// bail if no objects are in the list or the taxonomy does not exist.
		if ( empty( $this->post_ids ) || ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		// get the terms of all objects in a single request.
		$terms = wp_get_object_terms( $this->post_ids, $taxonomy );

		// bail on error.
		if ( ! is_array( $terms ) ) {
			return array();
		}

		// return the terms.
		return array_values( $terms );
	}
}

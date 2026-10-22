<?php
/**
 * File for the sorting by price.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Sortings;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Fields\Main\BaseRent;
use ConnectorForPropstack\Propstack\Fields\Main\Price as PriceField;
use ConnectorForPropstack\Propstack\Sorting_Base;
use ConnectorForPropstack\Propstack\Sorting_Context;

/**
 * Object for the sorting by price.
 *
 * Uses the purchase price for objects to buy and the base rent for objects to rent. In a list
 * with objects to buy and to rent, the objects to buy are shown first, then the objects to rent,
 * as purchase prices and rents are not comparable.
 */
class Price extends Sorting_Base {
	/**
	 * The internal name of the sorting.
	 *
	 * @var string
	 */
	protected string $name = 'price';

	/**
	 * Whether only values greater than 0 should be used (e.g., a price of 0 is not a real price).
	 *
	 * @var bool
	 */
	protected bool $positive_only = true;

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Price
	 */
	private static ?Price $instance = null;

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
	public static function get_instance(): Price {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return the label of this sorting, e.g., for the settings.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Price', 'connector-for-propstack' );
	}

	/**
	 * Return the field, whose values are used for this sorting.
	 *
	 * @return Field_Base
	 */
	public function get_field(): Field_Base {
		return new PriceField();
	}

	/**
	 * Return the meta key to sort by for the given list of objects.
	 *
	 * @param Sorting_Context $context The objects of the list.
	 *
	 * @return string
	 */
	public function get_meta_key( Sorting_Context $context ): string {
		// get the marketing types of the objects in this list.
		$marketing_types = $context->get_marketing_types();

		// use the purchase price if no marketing type is known.
		if ( empty( $marketing_types ) ) {
			return 'price';
		}

		// use the purchase price first, if objects to buy and to rent are mixed (the base rent is used as second value).
		if ( count( $marketing_types ) > 1 ) {
			return 'price';
		}

		// use the base rent for objects to rent.
		if ( 'RENT' === $marketing_types[0] ) {
			return ( new BaseRent() )->get_name();
		}

		// use the purchase price otherwise.
		return 'price';
	}

	/**
	 * Return the base rent as second meta key for lists with objects to buy and to rent.
	 *
	 * The objects to buy are shown first (sorted by purchase price), then the objects to rent (sorted by rent).
	 *
	 * @param Sorting_Context $context The objects of the list.
	 *
	 * @return string
	 */
	public function get_fallback_meta_key( Sorting_Context $context ): string {
		return count( $context->get_marketing_types() ) > 1 ? ( new BaseRent() )->get_name() : '';
	}

	/**
	 * Return the directions this sorting supports with their labels for the visitor.
	 *
	 * @param Sorting_Context|null $context The objects of the list, if known.
	 *
	 * @return array<string,string>
	 */
	public function get_directions( ?Sorting_Context $context = null ): array {
		// use other labels for objects to rent.
		if ( $context instanceof Sorting_Context && 'RENT' === $context->get_single_marketing_type() ) {
			return array(
				'asc'  => __( 'Rent: low to high', 'connector-for-propstack' ),
				'desc' => __( 'Rent: high to low', 'connector-for-propstack' ),
			);
		}

		return array(
			'asc'  => __( 'Price: low to high', 'connector-for-propstack' ),
			'desc' => __( 'Price: high to low', 'connector-for-propstack' ),
		);
	}
}

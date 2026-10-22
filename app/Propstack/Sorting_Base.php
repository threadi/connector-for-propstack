<?php
/**
 * File to handle basic functions for any sorting of the list of objects.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Base object for each sorting.
 *
 * A sorting orders the list of objects by the value of a single field. It offers one or two
 * directions (e.g. "price-asc" and "price-desc"). Objects without a value are always shown at the end.
 */
class Sorting_Base {
	/**
	 * Sort the values as numbers.
	 */
	public const CAST_NUMBER = 'number';

	/**
	 * Sort the values as dates (format YYYY-MM-DD at the beginning of the value).
	 */
	public const CAST_DATE = 'date';

	/**
	 * Sort the values as texts.
	 */
	public const CAST_TEXT = 'text';

	/**
	 * The internal name of the sorting.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * How the values should be compared.
	 *
	 * @var string
	 */
	protected string $cast = self::CAST_NUMBER;

	/**
	 * Whether only values greater than 0 should be used (e.g., a price of 0 is not a real price).
	 *
	 * @var bool
	 */
	protected bool $positive_only = false;

	/**
	 * Whether the sorting is not offered, if its field is disabled for all object types of the list.
	 *
	 * Sortings, whose values are not shown to the visitor (e.g. the creation date), could disable this.
	 *
	 * @var bool
	 */
	protected bool $respect_disabled_fields = true;

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {}

	/**
	 * Return the internal name of this sorting.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the label of this sorting, e.g., for the settings.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->get_field()->get_label();
	}

	/**
	 * Return the field, whose values are used for this sorting.
	 *
	 * @return Field_Base
	 */
	public function get_field(): Field_Base {
		return new Field_Base();
	}

	/**
	 * Return the meta key to sort by for the given list of objects.
	 *
	 * Returns an empty string if this sorting could not be used for this list.
	 *
	 * @param Sorting_Context $context The objects of the list.
	 *
	 * @return string
	 */
	public function get_meta_key( Sorting_Context $context ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Used by the sortings which override this.
		return $this->get_field()->get_name();
	}

	/**
	 * Return a second meta key, whose value is used for objects without a usable value in the first one.
	 *
	 * Objects with a value in the first meta key are shown first, then the objects with a value in
	 * this meta key (each group sorted by its value), then the objects without any value.
	 *
	 * @param Sorting_Context $context The objects of the list.
	 *
	 * @return string The meta key or an empty string if no second meta key is used.
	 */
	public function get_fallback_meta_key( Sorting_Context $context ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Used by the sortings which override this.
		return '';
	}

	/**
	 * Return how the values should be compared (see the CAST_ constants).
	 *
	 * @return string
	 */
	public function get_cast(): string {
		return $this->cast;
	}

	/**
	 * Return whether only values greater than 0 are used for this sorting.
	 *
	 * @return bool
	 */
	public function is_positive_only(): bool {
		return $this->positive_only;
	}

	/**
	 * Return the directions this sorting supports with their labels for the visitor.
	 *
	 * @param Sorting_Context|null $context The objects of the list, if known.
	 *
	 * @return array<string,string> List of directions ("asc" or "desc") with their labels.
	 */
	public function get_directions( ?Sorting_Context $context = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Used by the sortings which override this.
		return array(
			/* translators: %1$s will be replaced by the name of the field. */
			'asc'  => sprintf( __( '%1$s: ascending', 'connector-for-propstack' ), $this->get_label() ),
			/* translators: %1$s will be replaced by the name of the field. */
			'desc' => sprintf( __( '%1$s: descending', 'connector-for-propstack' ), $this->get_label() ),
		);
	}

	/**
	 * Return the options of this sorting (name and direction) with their labels.
	 *
	 * @param Sorting_Context|null $context The objects of the list, if known.
	 *
	 * @return array<string,string> List of options (e.g. "price-asc") with their labels.
	 */
	public function get_options( ?Sorting_Context $context = null ): array {
		$options = array();
		foreach ( $this->get_directions( $context ) as $direction => $label ) {
			$options[ $this->get_name() . '-' . $direction ] = $label;
		}
		return $options;
	}

	/**
	 * Return whether this sorting is hidden via the settings.
	 *
	 * @return bool
	 */
	public function is_hidden(): bool {
		$hidden = 1 === absint( get_option( 'propstack_connector_sortings_' . $this->get_name() . '_hidden' ) );

		$instance = $this;
		/**
		 * Filter whether a sorting should be hidden.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param bool         $hidden   True if the sorting should be hidden.
		 * @param Sorting_Base $instance The sorting object.
		 */
		return (bool) apply_filters( 'cfprop_sorting_is_hidden', $hidden, $instance );
	}

	/**
	 * Return whether the given value could be used for this sorting.
	 *
	 * @param string $value The value.
	 *
	 * @return bool
	 */
	public function is_usable_value( string $value ): bool {
		// check dates.
		if ( self::CAST_DATE === $this->get_cast() ) {
			return 1 === preg_match( '/^\d{4}-\d{2}-\d{2}/', $value );
		}

		// check numbers.
		if ( self::CAST_NUMBER === $this->get_cast() ) {
			if ( ! is_numeric( $value ) ) {
				return false;
			}
			return ! $this->is_positive_only() || (float) $value > 0;
		}

		// check texts.
		return '' !== trim( $value );
	}

	/**
	 * Return whether this sorting could be used for the given list of objects.
	 *
	 * This is the case if:
	 * - it is not hidden via settings,
	 * - its field is shown in the frontend (and not disabled for all object types of the list),
	 * - enough objects in the list have a usable value for it (by default at least half of them and at least 2).
	 *
	 * @param Sorting_Context $context The objects of the list.
	 *
	 * @return bool
	 */
	public function is_available( Sorting_Context $context ): bool {
		$available = $this->check_availability( $context );

		$instance = $this;
		/**
		 * Filter whether a sorting could be used for the given list of objects.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param bool            $available True if the sorting is available.
		 * @param Sorting_Base    $instance  The sorting object.
		 * @param Sorting_Context $context   The objects of the list.
		 */
		return (bool) apply_filters( 'cfprop_sorting_is_available', $available, $instance, $context );
	}

	/**
	 * Check whether this sorting could be used for the given list of objects.
	 *
	 * @param Sorting_Context $context The objects of the list.
	 *
	 * @return bool
	 */
	protected function check_availability( Sorting_Context $context ): bool {
		// bail if this sorting is hidden.
		if ( $this->is_hidden() ) {
			return false;
		}

		// bail if the list does not contain at least 2 objects.
		if ( $context->get_count() < 2 ) {
			return false;
		}

		// bail if the field is not usable in frontend.
		$field = $this->get_field();
		if ( $field->hide() || $field->hide_in_frontend() ) {
			return false;
		}

		// bail if the field is disabled for all object types in this list.
		$object_types = $this->respect_disabled_fields ? $context->get_object_types() : array();
		if ( ! empty( $object_types ) ) {
			$disabled = 0;
			foreach ( $object_types as $object_type ) {
				if ( Fields::get_instance()->is_field_disabled_for_object_type( $field, $object_type ) ) {
					++$disabled;
				}
			}
			if ( count( $object_types ) === $disabled ) {
				return false;
			}
		}

		// bail if no meta key could be used for this list.
		$meta_key = $this->get_meta_key( $context );
		if ( '' === $meta_key ) {
			return false;
		}

		// count the objects with a usable value (in the first or in the second meta key).
		$usable = array();
		foreach ( array_filter( array( $meta_key, $this->get_fallback_meta_key( $context ) ) ) as $key ) {
			foreach ( $context->get_values( $key ) as $post_id => $value ) {
				if ( $this->is_usable_value( $value ) ) {
					$usable[ $post_id ] = true;
				}
			}
		}
		$count = count( $usable );

		// bail if less than 2 objects have a value.
		if ( $count < 2 ) {
			return false;
		}

		// return whether enough objects in this list have a value.
		return ( $count / $context->get_count() ) >= Sortings::get_instance()->get_min_share();
	}
}

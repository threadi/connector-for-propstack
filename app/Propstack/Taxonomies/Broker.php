<?php
/**
 * File to handle our own custom taxonomy "cfprop_object_broker".
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Taxonomies;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Fields\Broker\AcademicTitle;
use ConnectorForPropstack\Propstack\Fields\Broker\Cell;
use ConnectorForPropstack\Propstack\Fields\Broker\Email;
use ConnectorForPropstack\Propstack\Fields\Broker\FirstName;
use ConnectorForPropstack\Propstack\Fields\Broker\Id;
use ConnectorForPropstack\Propstack\Fields\Broker\LastName;
use ConnectorForPropstack\Propstack\Fields\Broker\Locale;
use ConnectorForPropstack\Propstack\Fields\Broker\Name;
use ConnectorForPropstack\Propstack\Fields\Broker\Phone;
use ConnectorForPropstack\Propstack\Fields\Broker\Position;
use ConnectorForPropstack\Propstack\Fields\Broker\PublicCell;
use ConnectorForPropstack\Propstack\Fields\Broker\PublicEmail;
use ConnectorForPropstack\Propstack\Fields\Broker\PublicPhone;
use ConnectorForPropstack\Propstack\Fields\Broker\Salutation;
use ConnectorForPropstack\Propstack\Fields\Broker\Thumbnail;
use ConnectorForPropstack\Propstack\Taxonomy;

/**
 * Object to handle this custom taxonomy.
 */
class Broker extends Taxonomy {
	/**
	 * Define the taxonomy name.
	 *
	 * @var string
	 */
	protected string $name = 'cfprop_object_broker';

	/**
	 * The API field name to assign a term of this taxonomy to an object.
	 *
	 * @var string
	 */
	protected string $api_field = 'broker';

	/**
	 * The API field name to assign a term of this taxonomy to an object.
	 *
	 * @var string
	 */
	protected string $api_sub_field = 'id';

	/**
	 * Import these terms by object import.
	 *
	 * @var bool
	 */
	protected bool $add_by_object_import = true;

	/**
	 * Instance of this object.
	 *
	 * @var ?Broker
	 */
	private static ?Broker $instance = null;

	/**
	 * Constructor for this object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() { }

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
	 * Return the labels for this taxonomy.
	 *
	 * @return array<string,string>
	 */
	protected function get_labels(): array {
		return array(
			'name'          => _x( 'Broker', 'taxonomy general name', 'connector-for-propstack' ),
			'singular_name' => _x( 'Broker', 'taxonomy singular name', 'connector-for-propstack' ),
			'search_items'  => __( 'Search broker', 'connector-for-propstack' ),
			'edit_item'     => __( 'Edit broker', 'connector-for-propstack' ),
			'update_item'   => __( 'Update broker', 'connector-for-propstack' ),
			'menu_name'     => __( 'Broker', 'connector-for-propstack' ),
			'back_to_items' => '&larr; ' . __( 'Go to all broker', 'connector-for-propstack' ),
		);
	}

	/**
	 * Return the list of supported meta-fields to terms of this taxonomy.
	 *
	 * @return array<int,Field_Base>
	 */
	public function get_fields(): array {
		$fields = array(
			new AcademicTitle(),
			new Cell(),
			new Email(),
			new FirstName(),
			new Id(),
			new LastName(),
			new Locale(),
			new Name(),
			new Phone(),
			new Position(),
			new PublicCell(),
			new PublicEmail(),
			new PublicPhone(),
			new Salutation(),
			new Thumbnail(),
		);

		/**
		 * Filter the list of available broker fields.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,Field_Base> $fields List of field categories.
		 */
		return apply_filters( 'cfprop_taxonomy_broker_fields', $fields );
	}

	/**
	 * Return the list of default disabled fields for this object type.
	 *
	 * @return array<int,Field_Base>
	 */
	protected function get_default_disabled_fields(): array {
		return array(
			new Id(),
			new Email(),
			new Phone(),
			new Cell(),
		);
	}

	/**
	 * Return a category title preset for this taxonomy to use in template generation.
	 *
	 * @param string $category_title The category title.
	 * @param string $term_title The term title.
	 *
	 * @return string
	 */
	public function get_category_title_preset( string $category_title, string $term_title ): string {
		/* translators: %1$s: Category title, %2$s: Term title. */
		return sprintf( _x( '%1$s %2$s', 'Broker category title', 'connector-for-propstack' ), $category_title, $term_title );
	}
}

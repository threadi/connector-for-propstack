<?php
/**
 * File to handle Propstack-specific template tasks.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Propstack\Fields\Broker\Email;
use ConnectorForPropstack\Propstack\Fields\Broker\Name;
use ConnectorForPropstack\Propstack\Fields\Broker\Position;
use ConnectorForPropstack\Propstack\Fields\Broker\PublicEmail;
use ConnectorForPropstack\Propstack\Fields\Broker\PublicPhone;
use ConnectorForPropstack\Propstack\Fields\Broker\Thumbnail;
use ConnectorForPropstack\Propstack\Fields\Main\AirConditioning;
use ConnectorForPropstack\Propstack\Fields\Main\AlarmSystem;
use ConnectorForPropstack\Propstack\Fields\Main\BarrierFree;
use ConnectorForPropstack\Propstack\Fields\Main\BuildingEnergyRatingType;
use ConnectorForPropstack\Propstack\Fields\Main\Cellar;
use ConnectorForPropstack\Propstack\Fields\Main\Chimney;
use ConnectorForPropstack\Propstack\Fields\Main\City;
use ConnectorForPropstack\Propstack\Fields\Main\Co2Emission;
use ConnectorForPropstack\Propstack\Fields\Main\Condition;
use ConnectorForPropstack\Propstack\Fields\Main\ConstructionYear;
use ConnectorForPropstack\Propstack\Fields\Main\Corridor;
use ConnectorForPropstack\Propstack\Fields\Main\Deposit;
use ConnectorForPropstack\Propstack\Fields\Main\DescriptionNote;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToAirport;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToAirportInKm;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToAirportLength;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToFm;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToFmInKm;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToFmLength;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToMrs;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToMrsInKm;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToMrsLength;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToPt;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToPtInKm;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToPtLength;
use ConnectorForPropstack\Propstack\Fields\Main\EnergyCertificateAvailability;
use ConnectorForPropstack\Propstack\Fields\Main\EnergyCertificateCreationDate;
use ConnectorForPropstack\Propstack\Fields\Main\EnergyCertificateEndDate;
use ConnectorForPropstack\Propstack\Fields\Main\EnergyEfficiencyClass;
use ConnectorForPropstack\Propstack\Fields\Main\FiringTypes;
use ConnectorForPropstack\Propstack\Fields\Main\Floor;
use ConnectorForPropstack\Propstack\Fields\Main\FreeFrom;
use ConnectorForPropstack\Propstack\Fields\Main\FurnishingNote;
use ConnectorForPropstack\Propstack\Fields\Main\Garden;
use ConnectorForPropstack\Propstack\Fields\Main\GardenSpace;
use ConnectorForPropstack\Propstack\Fields\Main\HeatingCosts;
use ConnectorForPropstack\Propstack\Fields\Main\HeatingType;
use ConnectorForPropstack\Propstack\Fields\Main\LastRefurbishment;
use ConnectorForPropstack\Propstack\Fields\Main\Lift;
use ConnectorForPropstack\Propstack\Fields\Main\LivingSpace;
use ConnectorForPropstack\Propstack\Fields\Main\LocationNote;
use ConnectorForPropstack\Propstack\Fields\Main\Loggia;
use ConnectorForPropstack\Propstack\Fields\Main\LongDescriptionNote;
use ConnectorForPropstack\Propstack\Fields\Main\LongFurnishingNote;
use ConnectorForPropstack\Propstack\Fields\Main\LongLocationNote;
use ConnectorForPropstack\Propstack\Fields\Main\MaintenanceReserve;
use ConnectorForPropstack\Propstack\Fields\Main\Monument;
use ConnectorForPropstack\Propstack\Fields\Main\NetFloorSpace;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfApartments;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfBalconies;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfBathRooms;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfBedRooms;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfFloors;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfRooms;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfTerraces;
use ConnectorForPropstack\Propstack\Fields\Main\OtherCosts;
use ConnectorForPropstack\Propstack\Fields\Main\OtherNote;
use ConnectorForPropstack\Propstack\Fields\Main\ParkingSpacePrice;
use ConnectorForPropstack\Propstack\Fields\Main\PlotArea;
use ConnectorForPropstack\Propstack\Fields\Main\Price;
use ConnectorForPropstack\Propstack\Fields\Main\PricePerSqm;
use ConnectorForPropstack\Propstack\Fields\Main\Rented;
use ConnectorForPropstack\Propstack\Fields\Main\RentSubsidy;
use ConnectorForPropstack\Propstack\Fields\Main\Sauna;
use ConnectorForPropstack\Propstack\Fields\Main\ServiceCharge;
use ConnectorForPropstack\Propstack\Fields\Main\ShortAddress;
use ConnectorForPropstack\Propstack\Fields\Main\SummerResidencePractical;
use ConnectorForPropstack\Propstack\Fields\Main\SwimmingPool;
use ConnectorForPropstack\Propstack\Fields\Main\TotalRent;
use ConnectorForPropstack\Propstack\Fields\Main\WinterGarden;
use ConnectorForPropstack\Propstack\Fields\Main\ZipCode;
use ConnectorForPropstack\Propstack\Taxonomies\Broker;
use ConnectorForPropstack\Propstack\Taxonomies\MarketingType;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base;
use ConnectorForPropstack\Propstack\Widgets\Broker_Field;
use ConnectorForPropstack\Propstack\Widgets\Field;
use ConnectorForPropstack\Propstack\Widgets\Gallery;
use ConnectorForPropstack\Propstack\Widgets\Object_Data;
use WP_Error;

/**
 * Object to handle Propstack-specific template tasks.
 */
class Templates {

	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Templates
	 */
	private static ?Templates $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() { }

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Templates {
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
		// use hooks.
		add_action( 'wp_enqueue_scripts', array( $this, 'add_styles' ) );
		add_action( 'enqueue_block_assets', array( $this, 'add_styles' ) );

		// use our own hooks.
		add_action( 'cfprop_template_thumbnail', array( $this, 'show_thumbnail' ) );
		add_action( 'cfprop_template_location_object_type', array( $this, 'show_location_and_object_type' ) );
		add_action( 'cfprop_template_title', array( $this, 'show_title' ) );
		add_action( 'cfprop_template_broker', array( $this, 'show_broker' ), 10, 2 );
		add_action( 'cfprop_template_values', array( $this, 'show_values' ) );
		add_action( 'cfprop_template_marketing_type', array( $this, 'show_marketing_type' ) );
		add_action( 'cfprop_template_key_facts', array( $this, 'show_key_facts' ), 10, 0 );
		add_action( 'cfprop_template_detail_link', array( $this, 'show_detail_link' ) );
		add_action( 'cfprop_template_property_details', array( $this, 'show_property_details' ), 10, 0 );
		add_action( 'cfprop_template_2column_content', array( $this, 'show_2column_content' ) );
		add_action( 'cfprop_template_gallery', array( $this, 'show_gallery' ), 10, 0 );
	}

	/**
	 * Show the thumbnail for the given object.
	 *
	 * @param ImmoObject $immo_object The immo object.
	 *
	 * @return void
	 */
	public function show_thumbnail( ImmoObject $immo_object ): void {
		// get the thumbnail ID.
		$attachment_id = absint( get_post_thumbnail_id( $immo_object->get_id() ) );

		// bail if no attachment ID is set.
		if ( 0 === $attachment_id ) {
			return;
		}

		// show another thumbnail output in the single page.
		if ( is_singular() ) {
			// get the object type for this object.
			$object_type_term = wp_get_object_terms( $immo_object->get_id(), ObjectType::get_instance()->get_name() );
			$object_type      = '';
			if ( is_array( $object_type_term ) && ! empty( $object_type_term ) ) {
				$object_type = $object_type_term[0]->name;
			}

			?>
				<div class="cfprop-hero">
					<figure class="cfprop-thumbnail">
						<?php echo wp_kses_post( wp_get_attachment_image( $attachment_id, 'large' ) ); ?>
					</figure>
					<div class="wp-block-group">
						<p><?php echo esc_html( $object_type ); ?></p>
						<h1><?php echo esc_html( $immo_object->get_title() ); ?></h1>
					</div>
				</div>
			<?php

			// do nothing more.
			return;
		}

		// show the simple thumbnail in the archive listing.
		echo '<figure class="cfprop-thumbnail">';
		echo wp_get_attachment_image( $attachment_id, 'large' );
		echo '</figure>';
	}

	/**
	 * Show the location of the given object.
	 *
	 * @param ImmoObject $immo_object The immo object.
	 *
	 * @return void
	 */
	public function show_location_and_object_type( ImmoObject $immo_object ): void {
		// get the zip code for this object.
		$zip_code = Fields::get_instance()->get_field_value( $immo_object->get_id(), new ZipCode() );

		// get the city for this object.
		$city = Fields::get_instance()->get_field_value( $immo_object->get_id(), new City() );

		// get the object type.
		$object_type_term = wp_get_object_terms( $immo_object->get_id(), ObjectType::get_instance()->get_name() );
		$object_type      = '';
		if ( is_array( $object_type_term ) && ! empty( $object_type_term ) ) {
			$object_type = $object_type_term[0]->name;
		}

		// output.
		echo '<div class="cfprop-location-object-type">';
		echo '<p>' . esc_html( $zip_code . ' ' . $city ) . '</p>';
		echo '<p>' . esc_html( $object_type ) . '</p>';
		echo '</div>';
	}

	/**
	 * Show the location of the given object.
	 *
	 * @param ImmoObject $immo_object The immo object.
	 *
	 * @return void
	 */
	public function show_title( ImmoObject $immo_object ): void {
		echo '<h3 class="cfprop-title"><a href="' . esc_url( $immo_object->get_link() ) . '">' . esc_html( get_the_title( $immo_object->get_id() ) ) . '</a></h3>';
	}

	/**
	 * Show some main values.
	 *
	 * @param ImmoObject $immo_object The immo object.
	 *
	 * @return void
	 */
	public function show_values( ImmoObject $immo_object ): void {
		// get the total rent.
		$total_rent = Fields::get_instance()->get_field_value( $immo_object->get_id(), new TotalRent() );

		// get the price.
		$price = Fields::get_instance()->get_field_value( $immo_object->get_id(), new Price() );

		// get the price.
		$living_space = Fields::get_instance()->get_field_value( $immo_object->get_id(), new LivingSpace() );

		// get the price.
		$net_floor_space = Fields::get_instance()->get_field_value( $immo_object->get_id(), new NetFloorSpace() );

		// output.
		echo '<div class="cfprop-values"><div class="cfprop-costs">';
		echo '<p>' . esc_html( $total_rent ) . '</p>';
		echo '<p>' . esc_html( $price ) . '</p>';
		echo '</div><div class="cfprop-spaces">';
		echo '<p>' . esc_html( $living_space ) . '</p>';
		echo '<p>' . esc_html( $net_floor_space ) . '</p>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Show the detail link.
	 *
	 * @param ImmoObject $immo_object The immo object.
	 *
	 * @return void
	 */
	public function show_detail_link( ImmoObject $immo_object ): void {
		echo '<div class="cfprop-detail-link">';
		echo '<p><a href="' . esc_url( $immo_object->get_link() ) . '">' . esc_html__( 'Details', 'connector-for-propstack' ) . '</a></p>';
		echo '</div>';
	}

	/**
	 * Show the broker.
	 *
	 * @param ImmoObject $immo_object The immo object.
	 * @param string     $category_type_name The category type name.
	 *
	 * @return void
	 */
	public function show_broker( ImmoObject $immo_object, string $category_type_name ): void {
		// get the object type term for this object.
		$object_type_terms = wp_get_object_terms( $immo_object->get_id(), Broker::get_instance()->get_name() );

		// bail if no object type term is set.
		if ( empty( $object_type_terms ) || $object_type_terms instanceof WP_Error ) {
			return;
		}

		// get the fields to list in the loop item.
		$fields = array();
		foreach ( Broker::get_instance()->get_fields() as $field ) {
			// bail if the field should not be able to configure or visible in the frontend.
			if ( $field->hide() || $field->hide_in_frontend() || empty( $field->get_name() ) ) {
				continue;
			}

			// bail if the field should not be shown.
			if ( 1 === absint( get_option( 'propstack_connector_fields_' . Broker::get_instance()->get_name() . '_' . $field->get_name() . '_disabled' ) ) ) {
				continue;
			}

			// add the field to the list.
			$fields[] = array(
				'label' => $field->get_label(),
				'value' => Taxonomies::get_instance()->get_field_value( $object_type_terms[0]->term_id, $field, false ),
			);
		}

		// bail if no fields are set.
		if ( empty( $fields ) ) {
			return;
		}

		// return the template with this value.
		ob_start();
		include \ConnectorForPropstack\Plugin\Templates::get_instance()->get_template( 'parts/part-fields.php' );
		$content = ob_get_clean();
		if ( ! $content ) {
			return;
		}

		// get the category type object.
		$category_type = FieldCategories::get_instance()->get_category_type_by_name( $category_type_name );

		// bail if the category type is not set.
		if ( ! $category_type instanceof Field_Category_Type_Base ) {
			return;
		}

		?>
			<div class="properties-category properties-category-<?php echo esc_attr( $category_type_name ); ?>">
				<h3><?php echo esc_html( $category_type->get_label() ); ?></h3>
				<?php echo wp_kses_post( $content ); ?>
			</div>
		<?php
	}
	/**
	 * Show the object data of the given object for a given category type.
	 *
	 * @param \ConnectorForPropstack\Propstack\ImmoObject $immo_object        The immo object.
	 * @param string                                      $category_type_name The category type name.
	 * @param array<string,mixed>                         $attributes         The used attributes.
	 *
	 * @return void
	 */
	public function show_category_type( \ConnectorForPropstack\Propstack\ImmoObject $immo_object, string $category_type_name, array $attributes ): void {
		// get the object type term for this object.
		$object_type_terms = wp_get_object_terms( $immo_object->get_id(), ObjectType::get_instance()->get_name() );

		// bail if no object type term is set.
		if ( empty( $object_type_terms ) || $object_type_terms instanceof WP_Error ) {
			return;
		}

		// get the object type object for this term.
		$object_type = ObjectType::get_instance()->get_object_type_by_slug( $object_type_terms[0]->slug );

		// bail if the object type could not be loaded.
		if ( ! $object_type instanceof Object_Type_Base ) {
			return;
		}

		// get the category type object.
		$category_type = FieldCategories::get_instance()->get_category_type_by_name( $category_type_name );

		// bail if the category type is not set.
		if ( ! $category_type instanceof Field_Category_Type_Base ) {
			return;
		}

		// set the fields.
		$attributes['object_data'] = $this->get_fields_by_category_type( $category_type, $object_type );

		// bail if no fields are set.
		if ( empty( $attributes['object_data'] ) ) {
			return;
		}

		// show them.
		?>
		<div class="properties-category properties-category-<?php echo esc_attr( $category_type_name ); ?>">
			<h3><?php echo esc_html( $category_type->get_label() ); ?></h3>
			<div class="properties"><?php echo wp_kses_post( Object_Data::get_instance()->render( $attributes ) ); ?></div>
		</div>
		<?php
	}

	/**
	 * Return the list of fields for the given category type on the given object type.
	 *
	 * @param Field_Category_Type_Base $category_type_to_check The category type to check.
	 * @param Object_Type_Base         $object_type The object type.
	 *
	 * @return array<int,string>
	 */
	public function get_fields_by_category_type( Field_Category_Type_Base $category_type_to_check, Object_Type_Base $object_type ): array {
		// get the category types for this object type.
		$category_types = array();

		// check the taxonomy-specific fields.
		foreach ( FieldCategories::get_instance()->get_categories_as_objects() as $field_category ) {
			// get the name of the category type for this category.
			$category_type      = $field_category->get_category_type();
			$category_type_name = $category_type->get_name();
			if ( $category_type_name !== $category_type_to_check->get_name() ) {
				continue;
			}

			// check if this taxonomy has fields of this type.
			$fields = $object_type->get_fields_by_category( $field_category->get_name() );

			// bail if no fields are set.
			if ( empty( $fields ) ) {
				continue;
			}

			// add this category with the fields to the list of category types.
			if ( empty( $category_types[ $category_type_name ] ) ) {
				$category_types[ $category_type_name ] = array();
			}
			$category_types[ $category_type_name ] = array_merge( $category_types[ $category_type_name ], $fields );
		}

		// check each category type for used fields and show them.
		$fields = array();
		foreach ( $category_types as $field_objects ) {
			// collect for each field the value from the called immo object.
			foreach ( $field_objects as $field_object ) {
				// skip an empty field name.
				if ( $field_object->hide() || $field_object->hide_in_frontend() || empty( $field_object->get_name() ) ) {
					continue;
				}
				$fields[] = $field_object->get_name();
			}
		}

		// return the resulting fields.
		return $fields;
	}

	/**
	 * Add our own styling for classic templates.
	 *
	 * @return void
	 */
	public function add_styles(): void {
		// add dashicons in the frontend to show checkboxes on objects.
		wp_enqueue_style( 'dashicons' );

		// add our custom CSS for styling in our custom classic templates.
		wp_enqueue_style(
			'cfprop',
			Helper::get_plugin_url() . 'css/style.css',
			array(),
			Helper::get_file_version( Helper::get_plugin_path() . 'css/style.css' ),
		);
	}

	/**
	 * Show the marketing type.
	 *
	 * @param ImmoObject $immo_object The immo object.
	 *
	 * @return void
	 */
	public function show_marketing_type( ImmoObject $immo_object ): void {
		// get the marketing type.
		$marketing_type_term = wp_get_object_terms( $immo_object->get_id(), MarketingType::get_instance()->get_name() );
		$marketing_type      = '';
		if ( is_array( $marketing_type_term ) && ! empty( $marketing_type_term ) ) {
			$marketing_type = $marketing_type_term[0]->name;
		}

		// show it.
		echo '<p class="cfprop-marketing-type"><span>' . esc_html( $marketing_type ) . '</span></p>';
	}

	/**
	 * Show the key facts.
	 *
	 * @return void
	 */
	public function show_key_facts(): void {
		// get the price.
		$price = Field::get_instance()->render( array( 'field_name' => ( new Price() )->get_name() ) );

		// get the price per sqm.
		$price_per_sqm = Field::get_instance()->render( array( 'field_name' => ( new PricePerSqm() )->get_name() ) );

		// get the city.
		$city = Field::get_instance()->render( array( 'field_name' => ( new City() )->get_name() ) );

		// get the short address.
		$address = Field::get_instance()->render( array( 'field_name' => ( new ShortAddress() )->get_name() ) );

		// show them.
		?>
			<div class="cfprop-key-facts">
				<div class="cfprop-key-facts-prices">
					<?php
					echo wp_kses_post( $price );
					echo wp_kses_post( $price_per_sqm );
					?>
				</div>
				<div class="cfprop-key-facts-address">
					<?php
					echo wp_kses_post( $city );
					echo wp_kses_post( $address );
					?>
				</div>
			</div>
		<?php
	}

	/**
	 * Show the property details.
	 *
	 * @return void
	 */
	public function show_property_details(): void {
		// list of fields.
		$fields = array(
			new LivingSpace(),
			new NetFloorSpace(),
			new Corridor(),
			new Floor(),
			new GardenSpace(),
			new FreeFrom(),
			new NumberOfBathRooms(),
			new NumberOfRooms(),
			new NumberOfApartments(),
			new NumberOfBalconies(),
			new NumberOfBedRooms(),
			new NumberOfFloors(),
			new NumberOfTerraces(),
			new PlotArea(),
		);

		// show them.
		?>
		<div class="cfprop-property-details">
			<?php
			foreach ( $fields as $field ) {
				echo '<div class="wp-block-group"><p><span>' . wp_kses_post( $field->get_label() ) . ':</span></p>' . wp_kses_post( Field::get_instance()->render( array( 'field_name' => $field->get_name() ) ) ) . '</div>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Show the content of an immo object in a 2 column layout.
	 *
	 * Left: immo data.
	 * Right: a sticky contact.
	 *
	 * @param ImmoObject $immo_object The immo object.
	 *
	 * @return void
	 */
	public function show_2column_content( ImmoObject $immo_object ): void {
		// list of costs-fields.
		$costs_fields = array(
			new TotalRent(),
			new OtherCosts(),
			new RentSubsidy(),
			new MaintenanceReserve(),
			new Deposit(),
			new HeatingCosts(),
			new ServiceCharge(),
			new ParkingSpacePrice(),
		);

		// list of feature-fields.
		$feature_fields = array(
			new Monument(),
			new Rented(),
			new BarrierFree(),
			new Cellar(),
			new Lift(),
			new Garden(),
			new Loggia(),
			new AirConditioning(),
			new Sauna(),
			new WinterGarden(),
			new SwimmingPool(),
			new AlarmSystem(),
			new Chimney(),
			new SummerResidencePractical(),
		);

		// list of fields for building condition.
		$buildings_conditions_fields = array(
			new ConstructionYear(),
			new FiringTypes(),
			new HeatingType(),
			new LastRefurbishment(),
			new Condition(),
		);

		// list of fields for energy.
		$energy_fields = array(
			new EnergyCertificateAvailability(),
			new EnergyCertificateCreationDate(),
			new EnergyCertificateEndDate(),
			new EnergyEfficiencyClass(),
			new BuildingEnergyRatingType(),
			new Co2Emission(),
		);

		// list of distance fields.
		$distance_fields = array(
			new DistanceToAirport(),
			new DistanceToAirportInKm(),
			new DistanceToAirportLength(),
			new DistanceToFm(),
			new DistanceToFmInKm(),
			new DistanceToFmLength(),
			new DistanceToMrs(),
			new DistanceToMrsInKm(),
			new DistanceToMrsLength(),
			new DistanceToPt(),
			new DistanceToPtInKm(),
			new DistanceToPtLength(),
		);

		// list of broker fields.
		$broker_fields = array(
			new Name(),
			new Position(),
			new PublicEmail(),
			new PublicPhone(),
		);

		// get the broker to get the email.
		$broker_terms = wp_get_object_terms( $immo_object->get_id(), Broker::get_instance()->get_name() );
		$email        = '';

		// get the email for the broker.
		if ( is_array( $broker_terms ) && ! empty( $broker_terms ) ) {
			$email = (string) Taxonomies::get_instance()->get_field_value( $broker_terms[0]->term_id, new PublicEmail(), true, true );
			if ( empty( $email ) ) {
				$email = (string) Taxonomies::get_instance()->get_field_value( $broker_terms[0]->term_id, new Email(), true, true );
			}
		}
		if ( ! empty( $email ) ) {
			$email = 'mailto:' . $email;
		}

		?>
			<div class="cfprop-2column-content">
				<div class="cfprop-2column-content-left">
					<div>
						<h2><?php echo esc_html__( 'Description', 'connector-for-propstack' ); ?></h2>
						<?php
							echo wp_kses_post( Field::get_instance()->render( array( 'field_name' => ( new DescriptionNote() )->get_name() ) ) );
							echo wp_kses_post( Field::get_instance()->render( array( 'field_name' => ( new FurnishingNote() )->get_name() ) ) );
							echo wp_kses_post( Field::get_instance()->render( array( 'field_name' => ( new OtherNote() )->get_name() ) ) );
						?>
					</div>
					<div class="cfprop-costs">
						<?php
						foreach ( $costs_fields as $field ) {
							echo '<div class="wp-block-group"><p><span>' . wp_kses_post( $field->get_label() ) . ':</span></p>' . wp_kses_post( Field::get_instance()->render( array( 'field_name' => $field->get_name() ) ) ) . '</div>';
						}
						?>
					</div>
					<div>
						<h2><?php echo esc_html__( 'Location', 'connector-for-propstack' ); ?></h2>
						<?php
						echo wp_kses_post( Field::get_instance()->render( array( 'field_name' => ( new LocationNote() )->get_name() ) ) );
						?>
					</div>
					<div>
						<h2><?php echo esc_html__( 'Features', 'connector-for-propstack' ); ?></h2>
						<div class="cfprop-features">
							<?php
							foreach ( $feature_fields as $field ) {
								echo '<div class="wp-block-group"><p><span>' . wp_kses_post( $field->get_label() ) . '</span></p>' . wp_kses_post( Field::get_instance()->render( array( 'field_name' => $field->get_name() ) ) ) . '</div>';
							}
							?>
						</div>
					</div>
					<div>
						<h2><?php echo esc_html__( 'Building Condition and Energy Performance Certificate', 'connector-for-propstack' ); ?></h2>
						<div class="cfprop-building-conditions-energy">
							<div class="cfprop-building-conditions">
								<?php
								foreach ( $buildings_conditions_fields as $field ) {
									echo '<div class="wp-block-group"><p><span>' . wp_kses_post( $field->get_label() ) . ':</span></p>' . wp_kses_post( Field::get_instance()->render( array( 'field_name' => $field->get_name() ) ) ) . '</div>';
								}
								?>
							</div>
							<div class="cfprop-energy">
								<?php
								foreach ( $energy_fields as $field ) {
									echo '<div class="wp-block-group"><p><span>' . wp_kses_post( $field->get_label() ) . ':</span></p>' . wp_kses_post( Field::get_instance()->render( array( 'field_name' => $field->get_name() ) ) ) . '</div>';
								}
								?>
							</div>
						</div>
					</div>
					<div>
						<h2><?php echo esc_html__( 'Detailed description', 'connector-for-propstack' ); ?></h2>
						<?php
						echo wp_kses_post( Field::get_instance()->render( array( 'field_name' => ( new LongFurnishingNote() )->get_name() ) ) );
						echo wp_kses_post( Field::get_instance()->render( array( 'field_name' => ( new LongDescriptionNote() )->get_name() ) ) );
						echo wp_kses_post( Field::get_instance()->render( array( 'field_name' => ( new LongLocationNote() )->get_name() ) ) );
						?>
					</div>
					<div>
						<div class="cfprop-distances">
							<?php
							foreach ( $distance_fields as $field ) {
								echo '<div class="wp-block-group"><p><span>' . wp_kses_post( $field->get_label() ) . ':</span></p>' . wp_kses_post( Field::get_instance()->render( array( 'field_name' => $field->get_name() ) ) ) . '</div>';
							}
							?>
						</div>
					</div>
				</div>
				<div class="cfprop-2column-content-right">
					<div class="cfprop-contact-box">
						<h2><?php echo esc_html__( 'Contact', 'connector-for-propstack' ); ?></h2>
						<div class="cfprop-contact">
							<div class="cfprop-broker-image">
								<?php
									echo wp_kses_post( Broker_Field::get_instance()->render( array( 'field_name' => ( new Thumbnail() )->get_name() ) ) );
								?>
							</div>
							<div class="cfprop-broker">
								<?php
								foreach ( $broker_fields as $field ) {
									echo '<div class="wp-block-group">' . wp_kses_post( Broker_Field::get_instance()->render( array( 'field_name' => $field->get_name() ) ) ) . '</div>';
								}
								?>
							</div>
						</div>
						<a href="<?php echo esc_attr( $email ); ?>" class="button"><?php echo esc_html__( 'Send inquiry', 'connector-for-propstack' ); ?></a>
					</div>
				</div>
			</div>
		<?php
	}

	/**
	 * Show the gallery.
	 *
	 * @return void
	 */
	public function show_gallery(): void {
		echo wp_kses_post( Gallery::get_instance()->render( array() ) );
	}
}

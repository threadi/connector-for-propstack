<?php
/**
 * File to handle our own custom taxonomy "cfprop_object_property_type".
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Taxonomies;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Taxonomy;

/**
 * Object to handle this custom taxonomy.
 */
class PropertyType extends Taxonomy {
	/**
	 * Define the taxonomy name.
	 *
	 * @var string
	 */
	protected string $name = 'cfprop_object_property_type';

	/**
	 * The API field name to assign a term of this taxonomy to an object.
	 *
	 * @var string
	 */
	protected string $api_field = 'rs_category';

	/**
	 * Instance of this object.
	 *
	 * @var ?PropertyType
	 */
	private static ?PropertyType $instance = null;

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
	public static function get_instance(): PropertyType {
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
			'name'          => _x( 'Property Type', 'taxonomy general name', 'connector-for-propstack' ),
			'singular_name' => _x( 'Property Type', 'taxonomy singular name', 'connector-for-propstack' ),
			'search_items'  => __( 'Search Property Types', 'connector-for-propstack' ),
			'edit_item'     => __( 'Edit Property Type', 'connector-for-propstack' ),
			'update_item'   => __( 'Update Property Type', 'connector-for-propstack' ),
			'menu_name'     => __( 'Property Types', 'connector-for-propstack' ),
			'back_to_items' => '&larr; ' . __( 'Go to all Property Types', 'connector-for-propstack' ),
		);
	}

	/**
	 * Return the list of default terms for object types from Propstack.
	 *
	 * Format per entry:
	 * - api => the value in the Propstack API.
	 * - slug => the WordPress-internal slug.
	 * - label => the label for show in WordPress.
	 *
	 * @return array<int,mixed>
	 */
	protected function get_default_terms(): array {
		$terms = array(
			array(
				'api'   => 'APARTMENT',
				'slug'  => 'apartment',
				'label' => __( 'Apartment', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'ASSISTED_LIVING',
				'slug'  => 'assisted_living',
				'label' => __( 'Assisted Living', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'ATTIKA',
				'slug'  => 'attika',
				'label' => __( 'Attic Apartment', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'BAR_LOUNGE',
				'slug'  => 'bar_lounge',
				'label' => __( 'Bar / Lounge', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'BUNGALOW',
				'slug'  => 'bungalow',
				'label' => __( 'Bungalow', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'CAFE',
				'slug'  => 'cafe',
				'label' => __( 'Cafe', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'CARPORT',
				'slug'  => 'carport',
				'label' => __( 'Carport', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'CAR_PARK',
				'slug'  => 'car_park',
				'label' => __( 'Car Park', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'CASTLE_MANOR_HOUSE',
				'slug'  => 'castle_manor_house',
				'label' => __( 'Castle / Manor House', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'CLUB_DISCO',
				'slug'  => 'club_disco',
				'label' => __( 'Club / Disco', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'COLD_STORAGE',
				'slug'  => 'cold_storage',
				'label' => __( 'Cold Storage', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'COMMERCIAL_CENTRE',
				'slug'  => 'commercial_centre',
				'label' => __( 'Commercial Centre', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'COMMERCIAL_UNIT',
				'slug'  => 'commercial_unit',
				'label' => __( 'Commercial Unit', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'DEPARTMENT_STORE',
				'slug'  => 'department_store',
				'label' => __( 'Department Store', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'DOUBLE_GARAGE',
				'slug'  => 'double_garage',
				'label' => __( 'Double Garage', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'DUPLEX',
				'slug'  => 'duplex',
				'label' => __( 'Duplex', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'END_TERRACE_HOUSE',
				'slug'  => 'end_terrace_house',
				'label' => __( 'End Terrace House', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'FACTORY_OUTLET',
				'slug'  => 'factory_outlet',
				'label' => __( 'Factory Outlet', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'FARM',
				'slug'  => 'farm',
				'label' => __( 'Farm', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'FARMHOUSE',
				'slug'  => 'farmhouse',
				'label' => __( 'Farmhouse', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'FINCA',
				'slug'  => 'finca',
				'label' => __( 'Finca', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'GARAGE',
				'slug'  => 'garage',
				'label' => __( 'Garage', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'GROUND_FLOOR',
				'slug'  => 'ground_floor',
				'label' => __( 'Ground Floor', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'GUESTS_HOUSE',
				'slug'  => 'guests_house',
				'label' => __( 'Guest House', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'HALF_BASEMENT',
				'slug'  => 'half_basement',
				'label' => __( 'Half Basement', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'HALL',
				'slug'  => 'hall',
				'label' => __( 'Hall', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'HIGH_LACK_STORAGE',
				'slug'  => 'high_lack_storage',
				'label' => __( 'High Rack Storage', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'HORSE_FARM',
				'slug'  => 'horse_farm',
				'label' => __( 'Horse Farm', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'HOTEL',
				'slug'  => 'hotel',
				'label' => __( 'Hotel', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'HOTEL_GARNI',
				'slug'  => 'hotel_garni',
				'label' => __( 'Hotel Garni', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'HOTEL_RESIDENCE',
				'slug'  => 'hotel_residence',
				'label' => __( 'Hotel Residence', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INDUSTRIAL_AREA',
				'slug'  => 'industrial_area',
				'label' => __( 'Industrial Area', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INDUSTRY_HALL',
				'slug'  => 'industry_hall',
				'label' => __( 'Industry Hall', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INDUSTRY_HALL_WITH_OPEN_AREA',
				'slug'  => 'industry_hall_with_open_area',
				'label' => __( 'Industry Hall with Open Area', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_ASSISTED_LIVING',
				'slug'  => 'invest_assisted_living',
				'label' => __( 'Investment Assisted Living', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_BOARDING_HOUSE',
				'slug'  => 'invest_boarding_house',
				'label' => __( 'Investment Boarding House', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_CAR_PARK',
				'slug'  => 'invest_car_park',
				'label' => __( 'Investment Car Park', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_CLINIC',
				'slug'  => 'invest_clinic',
				'label' => __( 'Investment Clinic', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_COMMERCIAL_BUILDING',
				'slug'  => 'invest_commercial_building',
				'label' => __( 'Investment Commercial Building', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_COMMERCIAL_CENTRE',
				'slug'  => 'invest_commercial_centre',
				'label' => __( 'Investment Commercial Centre', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_COMMERCIAL_UNIT',
				'slug'  => 'invest_commercial_unit',
				'label' => __( 'Investment Commercial Unit', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_DAY_CARE',
				'slug'  => 'invest_day_care',
				'label' => __( 'Investment Day Care', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_DAY_NURSERY',
				'slug'  => 'invest_day_nursery',
				'label' => __( 'Investment Day Nursery', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_HALL_STORAGE',
				'slug'  => 'invest_hall_storage',
				'label' => __( 'Investment Hall / Storage', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_HOTEL',
				'slug'  => 'invest_hotel',
				'label' => __( 'Investment Hotel', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_HOUSING_ESTATE',
				'slug'  => 'invest_housing_estate',
				'label' => __( 'Investment Housing Estate', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_INDUSTRIAL_PROPERTY',
				'slug'  => 'invest_industrial_property',
				'label' => __( 'Investment Industrial Property', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_INTEGRATION_ASSISTANCE',
				'slug'  => 'invest_integration_assistance',
				'label' => __( 'Investment Integration Assistance', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_LIVING_BUSINESS_HOUSE',
				'slug'  => 'invest_living_business_house',
				'label' => __( 'Investment Living / Business House', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_MEDICAL_SERVICE_CENTER',
				'slug'  => 'invest_medical_service_center',
				'label' => __( 'Investment Medical Service Center', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_MICRO_APARTMENTS',
				'slug'  => 'invest_micro_apartments',
				'label' => __( 'Investment Micro Apartments', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_NURSING_HOME',
				'slug'  => 'invest_nursing_home',
				'label' => __( 'Investment Nursing Home', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_OFFICE_AND_COMMERCIAL_BUILDING',
				'slug'  => 'invest_office_and_commercial_building',
				'label' => __( 'Investment Office & Commercial Building', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_OFFICE_BUILDING',
				'slug'  => 'invest_office_building',
				'label' => __( 'Investment Office Building', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_OTHER',
				'slug'  => 'invest_other',
				'label' => __( 'Investment Other', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_PLOT',
				'slug'  => 'invest_plot',
				'label' => __( 'Investment Plot', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_REHAB_CLINIC',
				'slug'  => 'invest_rehab_clinic',
				'label' => __( 'Investment Rehab Clinic', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_RETAIL_PARK',
				'slug'  => 'invest_retail_park',
				'label' => __( 'Investment Retail Park', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_SHOPPING_CENTRE',
				'slug'  => 'invest_shopping_centre',
				'label' => __( 'Investment Shopping Centre', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_SHOP_SALES_FLOOR',
				'slug'  => 'invest_shop_sales_floor',
				'label' => __( 'Investment Shop / Sales Floor', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_SUPERMARKET',
				'slug'  => 'invest_supermarket',
				'label' => __( 'Investment Supermarket', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'INVEST_SURGERY_BUILDING',
				'slug'  => 'invest_surgery_building',
				'label' => __( 'Investment Surgery Building', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'KIOSK',
				'slug'  => 'kiosk',
				'label' => __( 'Kiosk', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'LEISURE_FACILITY',
				'slug'  => 'leisure_facility',
				'label' => __( 'Leisure Facility', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'LIVING_AND_COMMERCIAL_BUILDING',
				'slug'  => 'living_and_commercial_building',
				'label' => __( 'Living & Commercial Building', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'LOFT',
				'slug'  => 'loft',
				'label' => __( 'Loft', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'MAISONETTE',
				'slug'  => 'maisonette',
				'label' => __( 'Maisonette', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'MID_TERRACE_HOUSE',
				'slug'  => 'mid_terrace_house',
				'label' => __( 'Mid Terrace House', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'MULTI_FAMILY_HOUSE',
				'slug'  => 'multi_family_house',
				'label' => __( 'Multi Family House', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'MULTIDECK_CABINET_STORAGE',
				'slug'  => 'multideck_cabinet_storage',
				'label' => __( 'Multideck Cabinet Storage', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'NO_INFORMATION',
				'slug'  => 'no_information',
				'label' => __( 'No Information', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'NURSING_HOME',
				'slug'  => 'nursing_home',
				'label' => __( 'Nursing Home', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'OFFICE',
				'slug'  => 'office',
				'label' => __( 'Office', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'OFFICE_AND_COMMERCIAL_BUILDING',
				'slug'  => 'office_and_commercial_building',
				'label' => __( 'Office & Commercial Building', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'OFFICE_BUILDING',
				'slug'  => 'office_building',
				'label' => __( 'Office Building', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'OFFICE_CENTRE',
				'slug'  => 'office_centre',
				'label' => __( 'Office Centre', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'OFFICE_FLOOR',
				'slug'  => 'office_floor',
				'label' => __( 'Office Floor', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'OFFICE_LOFT',
				'slug'  => 'office_loft',
				'label' => __( 'Office Loft', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'OFFICE_STORAGE_BUILDING',
				'slug'  => 'office_storage_building',
				'label' => __( 'Office / Storage Building', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'OTHER',
				'slug'  => 'other',
				'label' => __( 'Other', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'PENSION',
				'slug'  => 'pension',
				'label' => __( 'Pension', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'PENTHOUSE',
				'slug'  => 'penthouse',
				'label' => __( 'Penthouse', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'RAISED_GROUND_FLOOR',
				'slug'  => 'raised_ground_floor',
				'label' => __( 'Raised Ground Floor', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'REPAIR_SHOP',
				'slug'  => 'repair_shop',
				'label' => __( 'Repair Shop', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'RESTAURANT',
				'slug'  => 'restaurant',
				'label' => __( 'Restaurant', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'RESIDENCE',
				'slug'  => 'residence',
				'label' => __( 'Residence', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'ROOF_STOREY',
				'slug'  => 'roof_storey',
				'label' => __( 'Roof Storey', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'SALES_AREA',
				'slug'  => 'sales_area',
				'label' => __( 'Sales Area', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'SALES_HALL',
				'slug'  => 'sales_hall',
				'label' => __( 'Sales Hall', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'SEMIDETACHED_HOUSE',
				'slug'  => 'semidetached_house',
				'label' => __( 'Semi-detached House', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'SERVICE_AREA',
				'slug'  => 'service_area',
				'label' => __( 'Service Area', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'SHOWROOM_SPACE',
				'slug'  => 'showroom_space',
				'label' => __( 'Showroom Space', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'SINGLE_FAMILY_HOUSE',
				'slug'  => 'single_family_house',
				'label' => __( 'Single Family House', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'SPECIAL_ESTATE',
				'slug'  => 'special_estate',
				'label' => __( 'Special Estate', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'SPECIAL_REAL_ESTATE',
				'slug'  => 'special_real_estate',
				'label' => __( 'Special Real Estate', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'STORAGE_AREA',
				'slug'  => 'storage_area',
				'label' => __( 'Storage Area', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'STORAGE_HALL',
				'slug'  => 'storage_hall',
				'label' => __( 'Storage Hall', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'STORAGE_WITH_OPEN_AREA',
				'slug'  => 'storage_with_open_area',
				'label' => __( 'Storage with Open Area', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'STREET_PARKING',
				'slug'  => 'street_parking',
				'label' => __( 'Street Parking', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'STUDIO',
				'slug'  => 'studio',
				'label' => __( 'Studio', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'SUMMER_RESIDENCE',
				'slug'  => 'summer_residence',
				'label' => __( 'Summer Residence', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'SURGERY',
				'slug'  => 'surgery',
				'label' => __( 'Surgery', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'SURGERY_BUILDING',
				'slug'  => 'surgery_building',
				'label' => __( 'Surgery Building', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'SURGERY_FLOOR',
				'slug'  => 'surgery_floor',
				'label' => __( 'Surgery Floor', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'TAVERN',
				'slug'  => 'tavern',
				'label' => __( 'Tavern', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'TERRACE_END_HOUSE',
				'slug'  => 'terrace_end_house',
				'label' => __( 'Terrace End House', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'TERRACE_HOUSE',
				'slug'  => 'terrace_house',
				'label' => __( 'Terrace House', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'TERRACED_FLAT',
				'slug'  => 'terraced_flat',
				'label' => __( 'Terraced Flat', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'TOWNHOUSE',
				'slug'  => 'townhouse',
				'label' => __( 'Townhouse', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'TWO_FAMILY_HOUSE',
				'slug'  => 'two_family_house',
				'label' => __( 'Two Family House', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'UNDERGROUND_GARAGE',
				'slug'  => 'underground_garage',
				'label' => __( 'Underground Garage', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'VILLA',
				'slug'  => 'villa',
				'label' => __( 'Villa', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'VINEYARD',
				'slug'  => 'vineyard',
				'label' => __( 'Vineyard', 'connector-for-propstack' ),
			),
			array(
				'api'   => 'WORKSHOP',
				'slug'  => 'workshop',
				'label' => __( 'Workshop', 'connector-for-propstack' ),
			),
		);

		/**
		 * Filter the default terms of categories.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,mixed> $terms List of terms.
		 */
		return apply_filters( 'cfprop_property_type_default_terms', $terms );
	}
}

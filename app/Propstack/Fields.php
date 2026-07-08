<?php
/**
 * File for handling the fields.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Fields\FieldTable;
use easySettingsForWordPress\Fields\Value;
use easySettingsForWordPress\Page;
use ConnectorForPropstack\Plugin\Cache;
use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\Log;
use ConnectorForPropstack\Plugin\Settings;
use ConnectorForPropstack\Propstack\Fields\Main\DescriptionNote;
use ConnectorForPropstack\Propstack\Fields\Main\ObjectId;
use ConnectorForPropstack\Propstack\Fields\Main\ShortAddress;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectType;
use ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes\Object_Type_Base;

/**
 * Object to handle the fields.
 */
class Fields {
	/**
	 * Variable for the instance of this Singleton object.
	 *
	 * @var ?Fields
	 */
	private static ?Fields $instance = null;

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
	public static function get_instance(): Fields {
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
		// initialize the field categories.
		FieldCategories::get_instance()->init();

		// use hooks.
		add_action( 'init', array( $this, 'add_settings' ), 20 );

		// use our own hooks.
		add_action( 'cfprop_import_object', array( $this, 'import_fields' ), 10, 2 );
		add_action( 'cfprop_import_object_field', array( $this, 'import_example' ), 10, 2 );
		add_action( 'cfprop_import_object_field', array( $this, 'set_post_content' ), 10, 4 );
		add_filter( 'cfprop_import_object_field_value', array( $this, 'clean_field_value_during_import' ), 10, 2 );
		add_filter( 'cfprop_rest_fields', array( $this, 'sort_rest_fields' ) );
	}

	/**
	 * Add the object settings.
	 *
	 * @return void
	 */
	public function add_settings(): void {
		// get the settings object.
		$settings_obj = Settings::get_instance()->get_settings_obj();

		// get the settings page.
		$settings_page = Settings::get_instance()->get_settings_page();

		// bail if the page could not be found.
		if ( ! $settings_page instanceof Page ) {
			return;
		}

		// add a tab on this page.
		$fields_tab = $settings_page->add_tab( 'propstack_connector_fields', 30 );
		$fields_tab->set_title( __( 'Fields', 'connector-for-propstack' ) );
		$fields_tab->set_hide_save( true );

		// check if this tab is called.
		$fields_tab_called = $fields_tab->get_name() === filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// add a sub tab to show all fields from Propstack we support.
		$all_fields_tab = $fields_tab->add_tab( 'propstack_connector_fields_all', 10 );
		$all_fields_tab->set_title( __( 'All fields', 'connector-for-propstack' ) );
		$all_fields_tab->set_hide_save( true );
		$fields_tab->set_default_tab( $all_fields_tab );

		// add a section.
		$all_fields_section = $all_fields_tab->add_section( 'propstack_connector_fields_all', 10 );
		$all_fields_section->set_title( __( 'List of Propstack objects fields', 'connector-for-propstack' ) );

		// add setting.
		$setting = $settings_obj->add_setting( 'propstack_connector_fields_all' );
		$setting->set_type( 'array' );
		$setting->set_default( array() );
		$setting->set_section( $all_fields_section );
		$field = new FieldTable( $settings_obj );
		$field->set_title( __( 'The fields', 'connector-for-propstack' ) );
		/* translators: %1$s: link to the Connector for Propstack Pro page */
		$field->set_description( __( 'These are the fields we get from Propstack for your objects. They are assigned to specific object types for use on the website.', 'connector-for-propstack' ) . '<br><br><span class="cfprop-pro-hint">' . sprintf( __( 'Get more fields with support for other object types like "Stores" with <a href="%1$s" target="_blank">Connector for Propstack Pro</a>.', 'connector-for-propstack' ), Helper::get_pro_url() ) . '</span>' );
		$field->set_columns(
			array(
				__( 'The field', 'connector-for-propstack' ),
				__( 'Example from your objects', 'connector-for-propstack' ),
			)
		);

		// create the hidden section for all settings in this field table.
		$hidden_section = $all_fields_tab->add_section( 'propstack_connector_fields_all_hidden', 20 );
		$hidden_section->set_hidden( true );

		// list the fields.
		$row = 0;
		foreach ( self::get_instance()->get_fields_as_objects() as $immo_field ) {
			// bail if the field should not be able to configure or visible in the frontend.
			if ( $immo_field->hide() || $immo_field->hide_in_frontend() || empty( $immo_field->get_name() ) ) {
				continue;
			}

			// add entry as a new row.
			$field->add_row();

			// add setting.
			$name_setting = $settings_obj->add_setting( 'propstack_connector_fields_all_' . $immo_field->get_name() . '_name' );
			$name_setting->prevent_export( true );
			$name_setting->set_type( 'string' );
			$name_setting->set_section( $hidden_section );
			$name_setting_field = new Value( $settings_obj );
			$name_setting_field->set_value( $immo_field->get_label() );
			$name_setting->set_field( $name_setting_field );
			$field->add_setting( $name_setting, $row, 0 );

			// add setting.
			$name_setting = $settings_obj->add_setting( 'propstack_connector_fields_all_' . $immo_field->get_name() . '_example' );
			$name_setting->prevent_export( true );
			$name_setting->set_type( 'string' );
			$name_setting->set_section( $hidden_section );
			$name_setting_field = new Value( $settings_obj );
			$name_setting_field->set_value( $fields_tab_called ? Cache::get( $immo_field->get_name() . '_example' ) : '' );
			$name_setting->set_field( $name_setting_field );
			$field->add_setting( $name_setting, $row, 1 );

			// next row.
			++$row;
		}
		$setting->set_field( $field );

		// get the list of object types.
		$object_types = ObjectType::get_instance()->get_object_types_as_objects();

		// add one sub tab for each object type to configure their fields.
		foreach ( $object_types as $index => $object_type ) {
			$object_type_fields_tab = $fields_tab->add_tab( 'propstack_connector_fields_' . $object_type->get_slug(), ( $index * 10 + 10 ) );
			$object_type_fields_tab->set_title( $object_type->get_label() );

			// add a section.
			$section = $object_type_fields_tab->add_section( 'propstack_connector_fields_' . $object_type->get_slug(), 10 );
			$section->set_title( $object_type->get_label() );

			// add setting.
			$setting = $settings_obj->add_setting( 'propstack_connector_fields_' . $object_type->get_slug() );
			$setting->set_type( 'integer' );
			$setting->set_default( array() );
			$setting->set_section( $section );
			$setting->set_save_callback( array( $this, 'save_disabled_fields_cache' ) );
			$field = new FieldTable( $settings_obj );
			$field->set_title( __( 'Configure the fields', 'connector-for-propstack' ) );
			$field->set_description( __( 'Set configuration for fields for your objects of this object type.', 'connector-for-propstack' ) );
			$field->set_columns(
				array(
					__( 'The field', 'connector-for-propstack' ),
					__( 'Hide in frontend', 'connector-for-propstack' ),
					__( 'Use as object content', 'connector-for-propstack' ),
					__( 'Example from your objects', 'connector-for-propstack' ),
				)
			);

			// create the hidden section for all settings in this field table.
			$hidden_section = $object_type_fields_tab->add_section( 'propstack_connector_fields_' . $object_type->get_slug() . '_hidden', ( 10 * $index ) );
			$hidden_section->set_hidden( true );

			$row = 0;
			foreach ( $object_type->get_fields() as $immo_field ) {
				// bail if the field should not be able to configure or visible in the frontend.
				if ( $immo_field->hide() || $immo_field->hide_in_frontend() || $immo_field->do_not_configure() ) {
					continue;
				}

				// add entry as a new row.
				$field->add_row();

				// add setting.
				$name_setting = $settings_obj->add_setting( 'propstack_connector_fields_' . $object_type->get_slug() . '_' . $immo_field->get_name() . '_name' );
				$name_setting->prevent_export( true );
				$name_setting->set_type( 'string' );
				$name_setting->set_section( $hidden_section );
				$name_setting_field = new Value( $settings_obj );
				$name_setting_field->set_value( $immo_field->get_label() );
				$name_setting->set_field( $name_setting_field );
				$field->add_setting( $name_setting, $row, 0 );

				// add setting.
				$disable_field_setting = $settings_obj->add_setting( 'propstack_connector_fields_' . $object_type->get_slug() . '_' . $immo_field->get_name() . '_disabled' );
				$disable_field_setting->set_type( 'integer' );
				$disable_field_setting->set_default( $object_type->is_field_default_disabled( $immo_field ) ? 1 : 0 );
				$disable_field_setting->set_section( $hidden_section );
				$disable_field_setting_field = new Checkbox( $settings_obj );
				$disable_field_setting_field->set_title( $immo_field->get_label() );
				$disable_field_setting_field->set_readonly( true );
				$disable_field_setting->set_field( $disable_field_setting_field );
				$field->add_setting( $disable_field_setting, $row, 1 );

				// add setting.
				$content_field_setting = $settings_obj->add_setting( 'propstack_connector_fields_' . $object_type->get_slug() . '_' . $immo_field->get_name() . '_content' );
				$content_field_setting->set_type( 'integer' );
				$content_field_setting->set_default( $object_type->get_default_content_field()->get_name() === $immo_field->get_name() ? 1 : 0 );
				$content_field_setting->set_section( $hidden_section );
				$content_field_setting_field = new Checkbox( $settings_obj );
				$content_field_setting_field->set_title( $immo_field->get_label() );
				$content_field_setting_field->set_readonly( true );
				$content_field_setting->set_field( $content_field_setting_field );
				$field->add_setting( $content_field_setting, $row, 2 );

				// add setting.
				$name_setting = $settings_obj->add_setting( 'propstack_connector_fields_all_' . $immo_field->get_name() . '_example' );
				$name_setting->prevent_export( true );
				$name_setting->set_type( 'string' );
				$name_setting->set_section( $hidden_section );
				$name_setting_field = new Value( $settings_obj );
				$name_setting_field->set_value( $fields_tab_called ? Cache::get( $immo_field->get_name() . '_example' ) : '' );
				$name_setting->set_field( $name_setting_field );
				$field->add_setting( $name_setting, $row, 3 );

				// next row.
				++$row;
			}
			$setting->set_field( $field );
		}

		// add additional tabs for each taxonomie with support for fields.
		foreach ( Taxonomies::get_instance()->get_taxonomies_as_objects() as $index => $taxonomy ) {
			// bail if this taxonomy does not support fields, or it is the object type taxonomy.
			if ( empty( $taxonomy->get_fields() ) || $taxonomy->get_name() === ObjectType::get_instance()->get_name() ) {
				continue;
			}

			// add a tab.
			$object_type_fields_tab = $fields_tab->add_tab( 'propstack_connector_fields_' . $taxonomy->get_name(), ( $index * 10 + count( $object_types ) * 10 ) );
			$object_type_fields_tab->set_title( $taxonomy->get_title() );

			// add a section.
			$section = $object_type_fields_tab->add_section( 'propstack_connector_fields_' . $taxonomy->get_name(), 10 );
			$section->set_title( $taxonomy->get_title() );

			// add setting.
			$setting = $settings_obj->add_setting( 'propstack_connector_fields_' . $taxonomy->get_name() );
			$setting->set_type( 'integer' );
			$setting->set_default( array() );
			$setting->set_section( $section );
			$field = new FieldTable( $settings_obj );
			$field->set_title( __( 'Configure the fields', 'connector-for-propstack' ) );
			$field->set_description( __( 'Set configuration for fields for this taxonomy.', 'connector-for-propstack' ) );
			$field->set_columns(
				array(
					__( 'The field', 'connector-for-propstack' ),
					__( 'Hide in frontend', 'connector-for-propstack' ),
				)
			);

			// create the hidden section for all settings in this field table.
			$hidden_section = $object_type_fields_tab->add_section( 'propstack_connector_fields_' . $taxonomy->get_name() . '_hidden', ( 10 * $index ) );
			$hidden_section->set_hidden( true );

			$row = 0;
			foreach ( $taxonomy->get_fields() as $immo_field ) {
				// bail if the field should not be able to configure or visible in the frontend.
				if ( $immo_field->hide() || $immo_field->hide_in_frontend() || $immo_field->do_not_configure() ) {
					continue;
				}

				// add entry as a new row.
				$field->add_row();

				// add setting.
				$name_setting = $settings_obj->add_setting( 'propstack_connector_fields_' . $taxonomy->get_name() . '_' . $immo_field->get_name() . '_name' );
				$name_setting->prevent_export( true );
				$name_setting->set_type( 'string' );
				$name_setting->set_section( $hidden_section );
				$name_setting_field = new Value( $settings_obj );
				$name_setting_field->set_value( $immo_field->get_label() );
				$name_setting->set_field( $name_setting_field );
				$field->add_setting( $name_setting, $row, 0 );

				// add setting.
				$disable_field_setting = $settings_obj->add_setting( 'propstack_connector_fields_' . $taxonomy->get_name() . '_' . $immo_field->get_name() . '_disabled' );
				$disable_field_setting->set_type( 'integer' );
				$disable_field_setting->set_default( $taxonomy->is_field_default_disabled( $immo_field ) ? 1 : 0 );
				$disable_field_setting->set_section( $hidden_section );
				$disable_field_setting_field = new Checkbox( $settings_obj );
				$disable_field_setting_field->set_title( $immo_field->get_label() );
				$disable_field_setting_field->set_readonly( true );
				$disable_field_setting->set_field( $disable_field_setting_field );
				$field->add_setting( $disable_field_setting, $row, 1 );

				// next row.
				++$row;
			}
			$setting->set_field( $field );
		}

		// add a tab.
		$fields_pro_tab = $fields_tab->add_tab( 'propstack_connector_fields_pro', 2000 );
		$fields_pro_tab->set_title( __( 'Use more object types with Connector for Propstack Pro', 'connector-for-propstack' ) );
		$fields_pro_tab->set_url( Helper::get_pro_url() );
		$fields_pro_tab->set_url_target( '_blank' );
		$fields_pro_tab->set_tab_class( 'cfprop-pro-hint' );
	}

	/**
	 * Return the list of object fields with their class names.
	 *
	 * @return array<int,string>
	 */
	public function get_fields(): array {
		$fields = array(
			'\ConnectorForPropstack\Propstack\Fields\Main\AdditionalArea',
			'\ConnectorForPropstack\Propstack\Fields\Main\ApartmentNumber',
			'\ConnectorForPropstack\Propstack\Fields\Main\ApartmentType',
			'\ConnectorForPropstack\Propstack\Fields\Main\Address',
			'\ConnectorForPropstack\Propstack\Fields\Main\AirConditioning',
			'\ConnectorForPropstack\Propstack\Fields\Main\AlarmSystem',
			'\ConnectorForPropstack\Propstack\Fields\Main\ApiResponse',
			'\ConnectorForPropstack\Propstack\Fields\Main\AutoLift',
			'\ConnectorForPropstack\Propstack\Fields\Main\Balcony',
			'\ConnectorForPropstack\Propstack\Fields\Main\BarrierFree',
			'\ConnectorForPropstack\Propstack\Fields\Main\BalconySpace',
			'\ConnectorForPropstack\Propstack\Fields\Main\BaseRent',
			'\ConnectorForPropstack\Propstack\Fields\Main\BaseRentNet',
			'\ConnectorForPropstack\Propstack\Fields\Main\BaseRentVat',
			'\ConnectorForPropstack\Propstack\Fields\Main\Bathroom',
			'\ConnectorForPropstack\Propstack\Fields\Main\BuildingEnergyRatingType',
			'\ConnectorForPropstack\Propstack\Fields\Main\BuildingType',
			'\ConnectorForPropstack\Propstack\Fields\Main\CellarSpace',
			'\ConnectorForPropstack\Propstack\Fields\Main\Co2Emission',
			'\ConnectorForPropstack\Propstack\Fields\Main\CommercializationType',
			'\ConnectorForPropstack\Propstack\Fields\Main\BuiltInKitchen',
			'\ConnectorForPropstack\Propstack\Fields\Main\Cellar',
			'\ConnectorForPropstack\Propstack\Fields\Main\CertificateOfEligibilityNeeded',
			'\ConnectorForPropstack\Propstack\Fields\Main\Chimney',
			'\ConnectorForPropstack\Propstack\Fields\Main\City',
			'\ConnectorForPropstack\Propstack\Fields\Main\Condition',
			'\ConnectorForPropstack\Propstack\Fields\Main\ConnectedLoad',
			'\ConnectorForPropstack\Propstack\Fields\Main\ConstructionPhase',
			'\ConnectorForPropstack\Propstack\Fields\Main\ConstructionType',
			'\ConnectorForPropstack\Propstack\Fields\Main\ContractType',
			'\ConnectorForPropstack\Propstack\Fields\Main\CoOwnershipShare',
			'\ConnectorForPropstack\Propstack\Fields\Main\Corridor',
			'\ConnectorForPropstack\Propstack\Fields\Main\CostBalcony',
			'\ConnectorForPropstack\Propstack\Fields\Main\CostLift',
			'\ConnectorForPropstack\Propstack\Fields\Main\CostOther',
			'\ConnectorForPropstack\Propstack\Fields\Main\ConstructionYear',
			'\ConnectorForPropstack\Propstack\Fields\Main\ConstructionYearUnknown',
			'\ConnectorForPropstack\Propstack\Fields\Main\Country',
			'\ConnectorForPropstack\Propstack\Fields\Main\Courtage',
			'\ConnectorForPropstack\Propstack\Fields\Main\CourtageNote',
			'\ConnectorForPropstack\Propstack\Fields\Main\CreatedAt',
			'\ConnectorForPropstack\Propstack\Fields\Main\CreatedAtFormatted',
			'\ConnectorForPropstack\Propstack\Fields\Main\Currency',
			'\ConnectorForPropstack\Propstack\Fields\Main\CustomField',
			'\ConnectorForPropstack\Propstack\Fields\Main\Demolition',
			'\ConnectorForPropstack\Propstack\Fields\Main\Deposit',
			'\ConnectorForPropstack\Propstack\Fields\Main\DistanceToAirport',
			'\ConnectorForPropstack\Propstack\Fields\Main\DistanceToAirportInKm',
			'\ConnectorForPropstack\Propstack\Fields\Main\DistanceToAirportLength',
			'\ConnectorForPropstack\Propstack\Fields\Main\DistanceToFm',
			'\ConnectorForPropstack\Propstack\Fields\Main\DistanceToFmInKm',
			'\ConnectorForPropstack\Propstack\Fields\Main\DistanceToFmLength',
			'\ConnectorForPropstack\Propstack\Fields\Main\DistanceToMrs',
			'\ConnectorForPropstack\Propstack\Fields\Main\DistanceToMrsInKm',
			'\ConnectorForPropstack\Propstack\Fields\Main\DistanceToMrsLength',
			'\ConnectorForPropstack\Propstack\Fields\Main\DistanceToPt',
			'\ConnectorForPropstack\Propstack\Fields\Main\DistanceToPtInKm',
			'\ConnectorForPropstack\Propstack\Fields\Main\DistanceToPtLength',
			'\ConnectorForPropstack\Propstack\Fields\Main\District',
			'\ConnectorForPropstack\Propstack\Fields\Main\Duration',
			'\ConnectorForPropstack\Propstack\Fields\Main\DurationFrom',
			'\ConnectorForPropstack\Propstack\Fields\Main\DurationUntil',
			'\ConnectorForPropstack\Propstack\Fields\Main\EndRentalDate',
			'\ConnectorForPropstack\Propstack\Fields\Main\EnergyEfficiencyValue',
			'\ConnectorForPropstack\Propstack\Fields\Main\EquipmentTechnologyConstructionYear',
			'\ConnectorForPropstack\Propstack\Fields\Main\DescriptionNote',
			'\ConnectorForPropstack\Propstack\Fields\Main\EnergyCertificateAvailability',
			'\ConnectorForPropstack\Propstack\Fields\Main\EnergyCertificateCreationDate',
			'\ConnectorForPropstack\Propstack\Fields\Main\EnergyCertificateEndDate',
			'\ConnectorForPropstack\Propstack\Fields\Main\EnergyCertificateStartDate',
			'\ConnectorForPropstack\Propstack\Fields\Main\EnergyEfficiencyClass',
			'\ConnectorForPropstack\Propstack\Fields\Main\EnergyConsumptionContainsWarmWater',
			'\ConnectorForPropstack\Propstack\Fields\Main\ExposeId',
			'\ConnectorForPropstack\Propstack\Fields\Main\FinancialContribution',
			'\ConnectorForPropstack\Propstack\Fields\Main\FlooringType',
			'\ConnectorForPropstack\Propstack\Fields\Main\FloorLoad',
			'\ConnectorForPropstack\Propstack\Fields\Main\Floorplans',
			'\ConnectorForPropstack\Propstack\Fields\Main\FloorPosition',
			'\ConnectorForPropstack\Propstack\Fields\Main\ForBidding',
			'\ConnectorForPropstack\Propstack\Fields\Main\FreeFrom',
			'\ConnectorForPropstack\Propstack\Fields\Main\FreeUntil',
			'\ConnectorForPropstack\Propstack\Fields\Main\GardenSpace',
			'\ConnectorForPropstack\Propstack\Fields\Main\Gender',
			'\ConnectorForPropstack\Propstack\Fields\Main\FiringTypes',
			'\ConnectorForPropstack\Propstack\Fields\Main\FlatShareSuitable',
			'\ConnectorForPropstack\Propstack\Fields\Main\Floor',
			'\ConnectorForPropstack\Propstack\Fields\Main\FurnishingNote',
			'\ConnectorForPropstack\Propstack\Fields\Main\GarageType',
			'\ConnectorForPropstack\Propstack\Fields\Main\Garden',
			'\ConnectorForPropstack\Propstack\Fields\Main\GuestToilet',
			'\ConnectorForPropstack\Propstack\Fields\Main\HallHeight',
			'\ConnectorForPropstack\Propstack\Fields\Main\HeatingCosts',
			'\ConnectorForPropstack\Propstack\Fields\Main\HeatingCostsNet',
			'\ConnectorForPropstack\Propstack\Fields\Main\HeatingCostsVat',
			'\ConnectorForPropstack\Propstack\Fields\Main\HideAddress',
			'\ConnectorForPropstack\Propstack\Fields\Main\HasCanteen',
			'\ConnectorForPropstack\Propstack\Fields\Main\HasFurniture',
			'\ConnectorForPropstack\Propstack\Fields\Main\HeatingCostsInServiceCharge',
			'\ConnectorForPropstack\Propstack\Fields\Main\HeatingType',
			'\ConnectorForPropstack\Propstack\Fields\Main\HeightGarage',
			'\ConnectorForPropstack\Propstack\Fields\Main\HouseNumber',
			'\ConnectorForPropstack\Propstack\Fields\Main\IndustrialArea',
			'\ConnectorForPropstack\Propstack\Fields\Main\InteriorQuality',
			'\ConnectorForPropstack\Propstack\Fields\Main\InvestmentType',
			'\ConnectorForPropstack\Propstack\Fields\Main\KitchenComplete',
			'\ConnectorForPropstack\Propstack\Fields\Main\LanCables',
			'\ConnectorForPropstack\Propstack\Fields\Main\LastRefurbishment',
			'\ConnectorForPropstack\Propstack\Fields\Main\Latitude',
			'\ConnectorForPropstack\Propstack\Fields\Main\LengthGarage',
			'\ConnectorForPropstack\Propstack\Fields\Main\Lift',
			'\ConnectorForPropstack\Propstack\Fields\Main\LivingSpace',
			'\ConnectorForPropstack\Propstack\Fields\Main\LocationClassificationType',
			'\ConnectorForPropstack\Propstack\Fields\Main\LocationName',
			'\ConnectorForPropstack\Propstack\Fields\Main\LocationNote',
			'\ConnectorForPropstack\Propstack\Fields\Main\LodgerFlat',
			'\ConnectorForPropstack\Propstack\Fields\Main\Loggia',
			'\ConnectorForPropstack\Propstack\Fields\Main\Longitude',
			'\ConnectorForPropstack\Propstack\Fields\Main\LongDescriptionNote',
			'\ConnectorForPropstack\Propstack\Fields\Main\LongFurnishingNote',
			'\ConnectorForPropstack\Propstack\Fields\Main\LongLocationNote',
			'\ConnectorForPropstack\Propstack\Fields\Main\LongOtherNote',
			'\ConnectorForPropstack\Propstack\Fields\Main\MaintenanceReserve',
			'\ConnectorForPropstack\Propstack\Fields\Main\MarketingStartDate',
			'\ConnectorForPropstack\Propstack\Fields\Main\MaxNumberOfPersons',
			'\ConnectorForPropstack\Propstack\Fields\Main\MaxRentalTime',
			'\ConnectorForPropstack\Propstack\Fields\Main\MinDivisible',
			'\ConnectorForPropstack\Propstack\Fields\Main\MinRentalTime',
			'\ConnectorForPropstack\Propstack\Fields\Main\NumberBeds',
			'\ConnectorForPropstack\Propstack\Fields\Main\NumberOfCommercials',
			'\ConnectorForPropstack\Propstack\Fields\Main\NumberSeats',
			'\ConnectorForPropstack\Propstack\Fields\Main\Monument',
			'\ConnectorForPropstack\Propstack\Fields\Main\NetFloorSpace',
			'\ConnectorForPropstack\Propstack\Fields\Main\NonSmoker',
			'\ConnectorForPropstack\Propstack\Fields\Main\Note',
			'\ConnectorForPropstack\Propstack\Fields\Main\NumberOfApartments',
			'\ConnectorForPropstack\Propstack\Fields\Main\NumberOfBalconies',
			'\ConnectorForPropstack\Propstack\Fields\Main\NumberOfBathRooms',
			'\ConnectorForPropstack\Propstack\Fields\Main\NumberOfBedRooms',
			'\ConnectorForPropstack\Propstack\Fields\Main\NumberOfFloors',
			'\ConnectorForPropstack\Propstack\Fields\Main\NumberOfRooms',
			'\ConnectorForPropstack\Propstack\Fields\Main\NumberOfParkingSpaces',
			'\ConnectorForPropstack\Propstack\Fields\Main\NumberOfTerraces',
			'\ConnectorForPropstack\Propstack\Fields\Main\NumberOfUnits',
			'\ConnectorForPropstack\Propstack\Fields\Main\ObjectId',
			'\ConnectorForPropstack\Propstack\Fields\Main\OtherNote',
			'\ConnectorForPropstack\Propstack\Fields\Main\OtherCosts',
			'\ConnectorForPropstack\Propstack\Fields\Main\OtherCostsNet',
			'\ConnectorForPropstack\Propstack\Fields\Main\OtherCostsVat',
			'\ConnectorForPropstack\Propstack\Fields\Main\OtherRent',
			'\ConnectorForPropstack\Propstack\Fields\Main\OtherRentNet',
			'\ConnectorForPropstack\Propstack\Fields\Main\OtherRentVat',
			'\ConnectorForPropstack\Propstack\Fields\Main\ParkingSpaceNumber',
			'\ConnectorForPropstack\Propstack\Fields\Main\ParkingSpacePrice',
			'\ConnectorForPropstack\Propstack\Fields\Main\ParkingSpaceType',
			'\ConnectorForPropstack\Propstack\Fields\Main\ParkingSpaceTypes',
			'\ConnectorForPropstack\Propstack\Fields\Main\PlotNumber',
			'\ConnectorForPropstack\Propstack\Fields\Main\PlusVat',
			'\ConnectorForPropstack\Propstack\Fields\Main\PriceNet',
			'\ConnectorForPropstack\Propstack\Fields\Main\PriceType',
			'\ConnectorForPropstack\Propstack\Fields\Main\PriceVat',
			'\ConnectorForPropstack\Propstack\Fields\Main\PetsAllowed',
			'\ConnectorForPropstack\Propstack\Fields\Main\PlotArea',
			'\ConnectorForPropstack\Propstack\Fields\Main\Price',
			'\ConnectorForPropstack\Propstack\Fields\Main\PriceMultiplier',
			'\ConnectorForPropstack\Propstack\Fields\Main\PriceMultiplierTarget',
			'\ConnectorForPropstack\Propstack\Fields\Main\PriceOnInquiry',
			'\ConnectorForPropstack\Propstack\Fields\Main\PricePerSqm',
			'\ConnectorForPropstack\Propstack\Fields\Main\ProjectId',
			'\ConnectorForPropstack\Propstack\Fields\Main\PropertySpaceValue',
			'\ConnectorForPropstack\Propstack\Fields\Main\RecurringCosts',
			'\ConnectorForPropstack\Propstack\Fields\Main\RecurringCostsNet',
			'\ConnectorForPropstack\Propstack\Fields\Main\RecurringCostsVat',
			'\ConnectorForPropstack\Propstack\Fields\Main\RecommendedUseTypes',
			'\ConnectorForPropstack\Propstack\Fields\Main\Region',
			'\ConnectorForPropstack\Propstack\Fields\Main\Rented',
			'\ConnectorForPropstack\Propstack\Fields\Main\RentDuration',
			'\ConnectorForPropstack\Propstack\Fields\Main\RentDurations',
			'\ConnectorForPropstack\Propstack\Fields\Main\Renter',
			'\ConnectorForPropstack\Propstack\Fields\Main\RentalIncome',
			'\ConnectorForPropstack\Propstack\Fields\Main\RentalIncomeActual',
			'\ConnectorForPropstack\Propstack\Fields\Main\RentalIncomeTarget',
			'\ConnectorForPropstack\Propstack\Fields\Main\RentSubsidy',
			'\ConnectorForPropstack\Propstack\Fields\Main\Sauna',
			'\ConnectorForPropstack\Propstack\Fields\Main\ServiceCharge',
			'\ConnectorForPropstack\Propstack\Fields\Main\ServiceChargeNet',
			'\ConnectorForPropstack\Propstack\Fields\Main\ServiceChargeVat',
			'\ConnectorForPropstack\Propstack\Fields\Main\ShortAddress',
			'\ConnectorForPropstack\Propstack\Fields\Main\SiteConstructibleType',
			'\ConnectorForPropstack\Propstack\Fields\Main\SiteDevelopmentType',
			'\ConnectorForPropstack\Propstack\Fields\Main\SoldPrice',
			'\ConnectorForPropstack\Propstack\Fields\Main\StartRentalDate',
			'\ConnectorForPropstack\Propstack\Fields\Main\StatusUpdatedAt',
			'\ConnectorForPropstack\Propstack\Fields\Main\StoreType',
			'\ConnectorForPropstack\Propstack\Fields\Main\Street',
			'\ConnectorForPropstack\Propstack\Fields\Main\SummerResidencePractical',
			'\ConnectorForPropstack\Propstack\Fields\Main\SwimmingPool',
			'\ConnectorForPropstack\Propstack\Fields\Main\Tenancy',
			'\ConnectorForPropstack\Propstack\Fields\Main\ThermalCharacteristic',
			'\ConnectorForPropstack\Propstack\Fields\Main\ThermalCharacteristicElectricity',
			'\ConnectorForPropstack\Propstack\Fields\Main\ThermalCharacteristicHeating',
			'\ConnectorForPropstack\Propstack\Fields\Main\Terrace',
			'\ConnectorForPropstack\Propstack\Fields\Main\TotalCommission',
			'\ConnectorForPropstack\Propstack\Fields\Main\TotalFloorSpace',
			'\ConnectorForPropstack\Propstack\Fields\Main\TotalRent',
			'\ConnectorForPropstack\Propstack\Fields\Main\TotalRentNet',
			'\ConnectorForPropstack\Propstack\Fields\Main\TotalRentVat',
			'\ConnectorForPropstack\Propstack\Fields\Main\UnitId',
			'\ConnectorForPropstack\Propstack\Fields\Main\UpdatedAt',
			'\ConnectorForPropstack\Propstack\Fields\Main\UpdatedAtFormatted',
			'\ConnectorForPropstack\Propstack\Fields\Main\UsableFloorSpace',
			'\ConnectorForPropstack\Propstack\Fields\Main\ValuationPrice',
			'\ConnectorForPropstack\Propstack\Fields\Main\ValuationPriceFrom',
			'\ConnectorForPropstack\Propstack\Fields\Main\ValuationPriceTo',
			'\ConnectorForPropstack\Propstack\Fields\Main\Vat',
			'\ConnectorForPropstack\Propstack\Fields\Main\WellnessArea',
			'\ConnectorForPropstack\Propstack\Fields\Main\WinterGarden',
			'\ConnectorForPropstack\Propstack\Fields\Main\WidthGarage',
			'\ConnectorForPropstack\Propstack\Fields\Main\YieldActual',
			'\ConnectorForPropstack\Propstack\Fields\Main\YieldTarget',
			'\ConnectorForPropstack\Propstack\Fields\Main\ZipCode',
		);

		/**
		 * Filter the list of available fields.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,string> $fields List of field categories.
		 */
		return apply_filters( 'cfprop_fields', $fields );
	}

	/**
	 * Return the list of fields as objects.
	 *
	 * @return array<int,Field_Base>
	 */
	public function get_fields_as_objects(): array {
		// prepare the list.
		$list = array();

		// add them to the list.
		foreach ( $this->get_fields() as $field_class_name ) {
			// bail if class does not exist.
			if ( ! class_exists( $field_class_name ) ) {
				continue;
			}

			// get the object.
			$obj = new $field_class_name();

			// bail if the object is not an instance of type "Field_Base".
			if ( ! $obj instanceof Field_Base ) {
				continue;
			}

			// add it to the list.
			$list[] = $obj;
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Return the value of a single field on an immo object.
	 *
	 * This function also checks the datatype of the value.
	 *
	 * @param int        $post_id      The post-ID.
	 * @param Field_Base $field        The field object.
	 * @param bool       $without_html Return the values with HTML or not.
	 * @param bool       $plain Return the plain value.
	 *
	 * @return mixed
	 */
	public function get_field_value( int $post_id, Field_Base $field, bool $without_html = true, bool $plain = false ): mixed {
		// get the formatted output for this field.
		return $this->format_field( $field, get_post_meta( $post_id, $field->get_name(), true ), $without_html, $plain );
	}

	/**
	 * Import the fields depending on the used object type during the import of a single immo object.
	 *
	 * @param array<string,mixed> $immo_object The object data from API.
	 * @param int                 $post_id The post-ID.
	 *
	 * @return void
	 */
	public function import_fields( array $immo_object, int $post_id ): void {
		// get the immo object title.
		$title = '';
		if ( ! empty( $immo_object['title']['value'] ) ) {
			$title = $immo_object['title']['value'];
		} elseif ( ! empty( $immo_object['title'] ) ) {
			$title = $immo_object['title'];
		}

		// get the object type.
		$object_type_object = ObjectType::get_instance()->get_object_type_by_object_post_id( $post_id );

		// bail if the object type is missing.
		if ( ! $object_type_object instanceof Object_Type_Base ) {
			// add a log entry if debug is enabled.
			/* translators: %1$s will be replaced by the object title. */
			Log::get_instance()->add( sprintf( __( 'Could not load object type for the object %1$s', 'connector-for-propstack' ), '<em>' . $title . '</em>' ), 'error', 'import' );
			return;
		}

		// add a log entry if debug is enabled.
		if ( 1 === absint( get_option( 'propstack_connector_debug', 0 ) ) ) {
			/* translators: %1$s will be replaced by the object title. */
			Log::get_instance()->add( sprintf( __( 'Import the fields for the object %1$s', 'connector-for-propstack' ), '<em>' . $title . '</em>' ), 'info', 'import' );
		}

		// get the list of fields.
		$fields = $object_type_object->get_fields();

		// update the object data in its fields.
		foreach ( $fields as $field ) {
			// get the field value from API.
			$value = $field->get_value_from_api_response( $post_id, $immo_object );

			/**
			 * Filter the value of a single field on an immo object during import.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 * @param mixed $value The value.
			 * @param Field_Base $field The field.
			 * @param int $post_id The post-ID of the object.
			 */
			$value = apply_filters( 'cfprop_import_object_field_value', $value, $field, $post_id );

			// save the field value from API.
			update_post_meta( $post_id, $field->get_name(), $value );

			/**
			 * Run additional tasks for a single field on an object during the import of them.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 * @param Field_Base $field The field.
			 * @param mixed $value The value.
			 * @param int $post_id The post-ID of the object.
			 * @param Object_Type_Base $object_type_object The object type.
			 * @param array<string,mixed> $immo_object The data from API.
			 */
			do_action( 'cfprop_import_object_field', $field, $value, $post_id, $object_type_object, $immo_object );
		}

		// get the custom fields.
		$custom_fields_list = array();
		if ( is_array( $immo_object['custom_fields'] ) ) {
			foreach ( $immo_object['custom_fields'] as $field_name => $field ) {
				// add the field to the list.
				$custom_fields_list[] = $field_name;

				// save the pretty value.
				if ( is_array( $field ) ) {
					update_post_meta( $post_id, $field_name . '_pretty_value', $field['pretty_value'] );
				} else {
					update_post_meta( $post_id, $field_name . '_pretty_value', $field );
				}

				// save the value.
				if ( is_array( $field ) ) {
					update_post_meta( $post_id, $field_name, $field_name );
				}
			}
		}

		/**
		 * Run additional tasks for fields on an object during the import of them.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,Field_Base> $fields The list of fields.
		 * @param int $post_id The post-ID of the object.
		 * @param Object_Type_Base $object_type_object The object type.
		 * @param array<string,mixed> $immo_object The data from API.
		 */
		do_action( 'cfprop_import_object_fields', $fields, $post_id, $object_type_object, $immo_object );

		// save the list of custom fields.
		update_post_meta( $post_id, 'custom_fields', $custom_fields_list );
	}

	/**
	 * Return the object of a field by its given name.
	 *
	 * @param string $field_name The field name.
	 *
	 * @return Field_Base|false
	 */
	public function get_field_by_name( string $field_name ): Field_Base|false {
		// get the field by its name.
		foreach ( $this->get_fields_as_objects() as $field ) {
			// bail if the name does not match.
			if ( $field_name !== $field->get_name() ) {
				continue;
			}

			// return this field object.
			return $field;
		}

		// return false if no field could be found.
		return false;
	}

	/**
	 * Return the most important fields as objects.
	 *
	 * @return array<int,Field_Base>
	 */
	public function get_important_fields_as_objects(): array {
		return array(
			new ObjectId(),
			new DescriptionNote(),
			new ShortAddress(),
		);
	}

	/**
	 * Format the value of a field before output depending on its format.
	 *
	 * @param Field_Base $field The field.
	 * @param mixed      $value The value.
	 * @param bool       $without_html True if we do not use HTML code in output of this value (will be stripped).
	 * @param bool       $plain Return the plain value.
	 *
	 * @return mixed
	 */
	public function format_field( Field_Base $field, mixed $value, bool $without_html = true, bool $plain = false ): mixed {
		// return the plain value, if requested.
		if ( $plain ) {
			return $value;
		}

		// format the value depending on its type.
		$field_type = FieldTypes::get_instance()->get_field_type_by_name( $field->get_type() );

		/**
		 * Filter the detected field-type of a single field.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param FieldType_Base|false $field_type The field type.
		 * @param Field_Base $field The field.
		 */
		$field_type = apply_filters( 'cfprop_field_type', $field_type, $field );

		// bail if no field type could be found.
		if ( ! $field_type instanceof FieldType_Base ) {
			return $value;
		}

		// set the value on the type object.
		$field_type->set_value( $value );

		// get the output for this value.
		$output = $field_type->get_value();

		// check if the value should be used with HTML code.
		if ( $field_type->is_with_html() ) {
			$without_html = false;
		}

		// get the field format.
		$field_format = FieldFormats::get_instance()->get_field_format_by_name( $field->get_output_format() );

		// bail if no field type could be found.
		if ( ! $field_format ) {
			return $value;
		}

		// set the output on the format object.
		$field_format->set_value( $output );

		// get the output for this value.
		$output = $field_format->get_value();

		// strip all tags if parameter is set.
		if ( $without_html ) {
			if ( empty( $output ) && is_admin() ) {
				return __( 'not set', 'connector-for-propstack' );
			}
			return wp_strip_all_tags( $output );
		}

		// use hint if the value is not set.
		if ( empty( $output ) && is_admin() ) {
			$output = '<em>' . __( 'not set', 'connector-for-propstack' ) . '</em>';
		}

		// return the resulting output.
		return $output;
	}

	/**
	 * Return the list of fields by request.
	 *
	 * @param string $field_category The field category.
	 * @param string $query The query for a name.
	 * @param bool   $load_all True if all fields should be loaded.
	 *
	 * @return array<int,mixed>
	 */
	public function get_fields_by_request( mixed $field_category, mixed $query, bool $load_all = false ): array {
		// prepare the list of fields.
		$fields = array();

		// if no query and "field_category" is set, return the most important fields.
		if ( empty( $query ) && empty( $field_category ) && ! $load_all ) {
			foreach ( self::get_instance()->get_important_fields_as_objects() as $index => $field ) {
				// add the field to the list.
				$fields[] = array(
					'id'    => ( $index + 1 ),
					'label' => $field->get_label(),
					'value' => $field->get_name(),
				);
			}

			// return this list.
			return $fields;
		}

		// add them to the list.
		foreach ( self::get_instance()->get_fields_as_objects() as $field ) {
			// bail if the field is hidden.
			if ( $field->hide() || $field->hide_in_frontend() ) {
				continue;
			}

			// bail if the category does not match, if set.
			if ( ! empty( $field_category ) && $field_category !== $field->get_category()->get_name() ) {
				continue;
			}

			// bail if no field name is set.
			if ( empty( $field->get_name() ) ) {
				continue;
			}

			// bail if the query does not match, if set.
			if ( ! empty( $query ) && ! str_contains( strtolower( $field->get_label() ), strtolower( $query ) ) && ! str_contains( strtolower( $field->get_name() ), strtolower( $query ) ) ) {
				continue;
			}

			// add the field to the list.
			$fields[] = array(
				'id'    => count( $fields ) + 1,
				'label' => $field->get_label(),
				'value' => $field->get_name(),
			);
		}

		/**
		 * Filter the available details-templates for REST API.
		 *
		 * @since 1.0.0 Available since 1.0.0
		 *
		 * @param array<int,mixed> $fields The fields.
		 */
		return apply_filters( 'cfprop_rest_fields', $fields );
	}

	/**
	 * Save an example value for any field during the import of objects.
	 *
	 * @param Field_Base $field The field object.
	 * @param mixed      $value The value.
	 *
	 * @return void
	 */
	public function import_example( Field_Base $field, mixed $value ): void {
		// bail if no value is given.
		if ( empty( $value ) ) {
			return;
		}

		// bail if fields name is empty.
		if ( empty( $field->get_name() ) ) {
			return;
		}

		// save in the cache.
		Cache::set( $field->get_name() . '_example', $this->format_field( $field, $value, false ) );
	}

	/**
	 * Set the post-content depending on settings.
	 *
	 * @param Field_Base       $field The field object.
	 * @param mixed            $value The value.
	 * @param int              $post_id The post ID.
	 * @param Object_Type_Base $object_type_object The object type for the object.
	 *
	 * @return void
	 */
	public function set_post_content( Field_Base $field, mixed $value, int $post_id, Object_Type_Base $object_type_object ): void {
		// bail if setting has not been enabled for this object type on this field.
		if ( 1 !== absint( get_option( 'propstack_connector_fields_' . $object_type_object->get_slug() . '_' . $field->get_name() . '_content' ) ) ) {
			return;
		}

		// bail if value is empty.
		if ( empty( $value ) ) {
			return;
		}

		// save it.
		$query = array(
			'ID'           => $post_id,
			'post_content' => $value,
		);
		wp_update_post( $query );
	}

	/**
	 * Clean the value of a field before saving it during import.
	 *
	 * @param mixed      $value The value.
	 * @param Field_Base $field The field.
	 *
	 * @return mixed
	 */
	public function clean_field_value_during_import( mixed $value, Field_Base $field ): mixed {
		// get the field type.
		$field_type = FieldTypes::get_instance()->get_field_type_by_name( $field->get_type() );

		// bail if no field type could be found.
		if ( ! $field_type ) {
			return $value;
		}

		// set the value on the type.
		$field_type->set_value( $value );

		// return the cleaned value.
		return $field_type->get_cleaned_value();
	}

	/**
	 * Save all disabled fields in the cache.
	 *
	 * @return void
	 */
	public function save_disabled_fields_cache(): void {
		$cache = array();
		foreach ( ObjectType::get_instance()->get_object_types_as_objects() as $object_type ) {
			foreach ( $object_type->get_fields() as $immo_field ) {
				// bail if the field should not be able to configure or visible in the frontend.
				if ( $immo_field->hide() || $immo_field->hide_in_frontend() || $immo_field->do_not_configure() ) {
					continue;
				}

				// bail if this field is not disabled.
				if ( 1 !== absint( get_option( 'propstack_connector_fields_' . $object_type->get_slug() . '_' . $immo_field->get_name() . '_disabled' ) ) ) {
					continue;
				}
				$cache[] = $immo_field->get_name();
			}
		}
		Cache::set( 'disabled_fields', $cache );
	}

	/**
	 * Sort the fields returned via REST API.
	 *
	 * @param array<string,mixed> $fields List of fields.
	 *
	 * @return array<string,mixed>
	 */
	public function sort_rest_fields( array $fields ): array {
		// order the list by their labels.
		usort(
			$fields,
			static function ( $a, $b ) {
				return strcmp( $a['label'], $b['label'] );
			}
		);

		// return the resulting list.
		return $fields; // @phpstan-ignore return.type
	}
}

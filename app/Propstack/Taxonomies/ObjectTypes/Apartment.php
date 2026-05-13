<?php
/**
 * File to handle the object type Apartment.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Taxonomies\ObjectTypes;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Propstack\Field_Base;
use ConnectorForPropstack\Propstack\Fields\Main\AdditionalArea;
use ConnectorForPropstack\Propstack\Fields\Main\Address;
use ConnectorForPropstack\Propstack\Fields\Main\AirConditioning;
use ConnectorForPropstack\Propstack\Fields\Main\AlarmSystem;
use ConnectorForPropstack\Propstack\Fields\Main\ApartmentNumber;
use ConnectorForPropstack\Propstack\Fields\Main\ApartmentType;
use ConnectorForPropstack\Propstack\Fields\Main\ApiResponse;
use ConnectorForPropstack\Propstack\Fields\Main\AutoLift;
use ConnectorForPropstack\Propstack\Fields\Main\Balcony;
use ConnectorForPropstack\Propstack\Fields\Main\BalconySpace;
use ConnectorForPropstack\Propstack\Fields\Main\BarrierFree;
use ConnectorForPropstack\Propstack\Fields\Main\BaseRent;
use ConnectorForPropstack\Propstack\Fields\Main\BaseRentNet;
use ConnectorForPropstack\Propstack\Fields\Main\BaseRentVat;
use ConnectorForPropstack\Propstack\Fields\Main\Bathroom;
use ConnectorForPropstack\Propstack\Fields\Main\BuildingEnergyRatingType;
use ConnectorForPropstack\Propstack\Fields\Main\BuiltInKitchen;
use ConnectorForPropstack\Propstack\Fields\Main\Cellar;
use ConnectorForPropstack\Propstack\Fields\Main\CellarSpace;
use ConnectorForPropstack\Propstack\Fields\Main\CertificateOfEligibilityNeeded;
use ConnectorForPropstack\Propstack\Fields\Main\Chimney;
use ConnectorForPropstack\Propstack\Fields\Main\City;
use ConnectorForPropstack\Propstack\Fields\Main\Co2Emission;
use ConnectorForPropstack\Propstack\Fields\Main\CommercializationType;
use ConnectorForPropstack\Propstack\Fields\Main\Condition;
use ConnectorForPropstack\Propstack\Fields\Main\ConnectedLoad;
use ConnectorForPropstack\Propstack\Fields\Main\ConstructionPhase;
use ConnectorForPropstack\Propstack\Fields\Main\ConstructionType;
use ConnectorForPropstack\Propstack\Fields\Main\ConstructionYear;
use ConnectorForPropstack\Propstack\Fields\Main\ConstructionYearUnknown;
use ConnectorForPropstack\Propstack\Fields\Main\ContractType;
use ConnectorForPropstack\Propstack\Fields\Main\CoOwnershipShare;
use ConnectorForPropstack\Propstack\Fields\Main\Corridor;
use ConnectorForPropstack\Propstack\Fields\Main\CostBalcony;
use ConnectorForPropstack\Propstack\Fields\Main\CostLift;
use ConnectorForPropstack\Propstack\Fields\Main\CostOther;
use ConnectorForPropstack\Propstack\Fields\Main\Courtage;
use ConnectorForPropstack\Propstack\Fields\Main\CourtageNote;
use ConnectorForPropstack\Propstack\Fields\Main\CreatedAt;
use ConnectorForPropstack\Propstack\Fields\Main\CreatedAtFormatted;
use ConnectorForPropstack\Propstack\Fields\Main\Currency;
use ConnectorForPropstack\Propstack\Fields\Main\CustomField;
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
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToPt;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToPtInKm;
use ConnectorForPropstack\Propstack\Fields\Main\DistanceToPtLength;
use ConnectorForPropstack\Propstack\Fields\Main\District;
use ConnectorForPropstack\Propstack\Fields\Main\Duration;
use ConnectorForPropstack\Propstack\Fields\Main\DurationFrom;
use ConnectorForPropstack\Propstack\Fields\Main\DurationUntil;
use ConnectorForPropstack\Propstack\Fields\Main\EndRentalDate;
use ConnectorForPropstack\Propstack\Fields\Main\EnergyCertificateAvailability;
use ConnectorForPropstack\Propstack\Fields\Main\EnergyCertificateCreationDate;
use ConnectorForPropstack\Propstack\Fields\Main\EnergyCertificateEndDate;
use ConnectorForPropstack\Propstack\Fields\Main\EnergyCertificateStartDate;
use ConnectorForPropstack\Propstack\Fields\Main\EnergyConsumptionContainsWarmWater;
use ConnectorForPropstack\Propstack\Fields\Main\EnergyEfficiencyClass;
use ConnectorForPropstack\Propstack\Fields\Main\ExposeId;
use ConnectorForPropstack\Propstack\Fields\Main\FinancialContribution;
use ConnectorForPropstack\Propstack\Fields\Main\FiringTypes;
use ConnectorForPropstack\Propstack\Fields\Main\FlatShareSuitable;
use ConnectorForPropstack\Propstack\Fields\Main\Floor;
use ConnectorForPropstack\Propstack\Fields\Main\FlooringType;
use ConnectorForPropstack\Propstack\Fields\Main\FloorLoad;
use ConnectorForPropstack\Propstack\Fields\Main\Floorplans;
use ConnectorForPropstack\Propstack\Fields\Main\FloorPosition;
use ConnectorForPropstack\Propstack\Fields\Main\ForBidding;
use ConnectorForPropstack\Propstack\Fields\Main\FreeFrom;
use ConnectorForPropstack\Propstack\Fields\Main\FreeUntil;
use ConnectorForPropstack\Propstack\Fields\Main\FurnishingNote;
use ConnectorForPropstack\Propstack\Fields\Main\Garden;
use ConnectorForPropstack\Propstack\Fields\Main\GardenSpace;
use ConnectorForPropstack\Propstack\Fields\Main\Gender;
use ConnectorForPropstack\Propstack\Fields\Main\GuestToilet;
use ConnectorForPropstack\Propstack\Fields\Main\HallHeight;
use ConnectorForPropstack\Propstack\Fields\Main\HasCanteen;
use ConnectorForPropstack\Propstack\Fields\Main\HasFurniture;
use ConnectorForPropstack\Propstack\Fields\Main\HeatingCosts;
use ConnectorForPropstack\Propstack\Fields\Main\HeatingCostsInServiceCharge;
use ConnectorForPropstack\Propstack\Fields\Main\HeatingCostsNet;
use ConnectorForPropstack\Propstack\Fields\Main\HeatingCostsVat;
use ConnectorForPropstack\Propstack\Fields\Main\HeatingType;
use ConnectorForPropstack\Propstack\Fields\Main\HideAddress;
use ConnectorForPropstack\Propstack\Fields\Main\HouseNumber;
use ConnectorForPropstack\Propstack\Fields\Main\IndustrialArea;
use ConnectorForPropstack\Propstack\Fields\Main\InteriorQuality;
use ConnectorForPropstack\Propstack\Fields\Main\InvestmentType;
use ConnectorForPropstack\Propstack\Fields\Main\KitchenComplete;
use ConnectorForPropstack\Propstack\Fields\Main\LanCables;
use ConnectorForPropstack\Propstack\Fields\Main\LastRefurbishment;
use ConnectorForPropstack\Propstack\Fields\Main\Latitude;
use ConnectorForPropstack\Propstack\Fields\Main\Lift;
use ConnectorForPropstack\Propstack\Fields\Main\LivingSpace;
use ConnectorForPropstack\Propstack\Fields\Main\LocationClassificationType;
use ConnectorForPropstack\Propstack\Fields\Main\LocationName;
use ConnectorForPropstack\Propstack\Fields\Main\LocationNote;
use ConnectorForPropstack\Propstack\Fields\Main\LodgerFlat;
use ConnectorForPropstack\Propstack\Fields\Main\Loggia;
use ConnectorForPropstack\Propstack\Fields\Main\LongDescriptionNote;
use ConnectorForPropstack\Propstack\Fields\Main\LongFurnishingNote;
use ConnectorForPropstack\Propstack\Fields\Main\Longitude;
use ConnectorForPropstack\Propstack\Fields\Main\LongLocationNote;
use ConnectorForPropstack\Propstack\Fields\Main\LongOtherNote;
use ConnectorForPropstack\Propstack\Fields\Main\MaintenanceReserve;
use ConnectorForPropstack\Propstack\Fields\Main\MaxNumberOfPersons;
use ConnectorForPropstack\Propstack\Fields\Main\MaxRentalTime;
use ConnectorForPropstack\Propstack\Fields\Main\MinDivisible;
use ConnectorForPropstack\Propstack\Fields\Main\MinRentalTime;
use ConnectorForPropstack\Propstack\Fields\Main\Monument;
use ConnectorForPropstack\Propstack\Fields\Main\NetFloorSpace;
use ConnectorForPropstack\Propstack\Fields\Main\NonSmoker;
use ConnectorForPropstack\Propstack\Fields\Main\Note;
use ConnectorForPropstack\Propstack\Fields\Main\NumberBeds;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfApartments;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfBalconies;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfBathRooms;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfBedRooms;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfCommercials;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfParkingSpaces;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfRooms;
use ConnectorForPropstack\Propstack\Fields\Main\NumberOfTerraces;
use ConnectorForPropstack\Propstack\Fields\Main\NumberSeats;
use ConnectorForPropstack\Propstack\Fields\Main\ObjectId;
use ConnectorForPropstack\Propstack\Fields\Main\OtherCosts;
use ConnectorForPropstack\Propstack\Fields\Main\OtherCostsNet;
use ConnectorForPropstack\Propstack\Fields\Main\OtherCostsVat;
use ConnectorForPropstack\Propstack\Fields\Main\OtherNote;
use ConnectorForPropstack\Propstack\Fields\Main\OtherRent;
use ConnectorForPropstack\Propstack\Fields\Main\OtherRentNet;
use ConnectorForPropstack\Propstack\Fields\Main\OtherRentVat;
use ConnectorForPropstack\Propstack\Fields\Main\ParkingSpaceNumber;
use ConnectorForPropstack\Propstack\Fields\Main\ParkingSpacePrice;
use ConnectorForPropstack\Propstack\Fields\Main\ParkingSpaceType;
use ConnectorForPropstack\Propstack\Fields\Main\ParkingSpaceTypes;
use ConnectorForPropstack\Propstack\Fields\Main\PetsAllowed;
use ConnectorForPropstack\Propstack\Fields\Main\PlotArea;
use ConnectorForPropstack\Propstack\Fields\Main\Price;
use ConnectorForPropstack\Propstack\Fields\Main\PriceMultiplier;
use ConnectorForPropstack\Propstack\Fields\Main\PriceMultiplierTarget;
use ConnectorForPropstack\Propstack\Fields\Main\PriceNet;
use ConnectorForPropstack\Propstack\Fields\Main\PriceOnInquiry;
use ConnectorForPropstack\Propstack\Fields\Main\PriceType;
use ConnectorForPropstack\Propstack\Fields\Main\PriceVat;
use ConnectorForPropstack\Propstack\Fields\Main\ProjectId;
use ConnectorForPropstack\Propstack\Fields\Main\RecommendedUseTypes;
use ConnectorForPropstack\Propstack\Fields\Main\RecurringCosts;
use ConnectorForPropstack\Propstack\Fields\Main\RecurringCostsNet;
use ConnectorForPropstack\Propstack\Fields\Main\RecurringCostsVat;
use ConnectorForPropstack\Propstack\Fields\Main\Region;
use ConnectorForPropstack\Propstack\Fields\Main\RentalIncome;
use ConnectorForPropstack\Propstack\Fields\Main\RentalIncomeActual;
use ConnectorForPropstack\Propstack\Fields\Main\RentalIncomeTarget;
use ConnectorForPropstack\Propstack\Fields\Main\RentDuration;
use ConnectorForPropstack\Propstack\Fields\Main\RentDurations;
use ConnectorForPropstack\Propstack\Fields\Main\Rented;
use ConnectorForPropstack\Propstack\Fields\Main\Renter;
use ConnectorForPropstack\Propstack\Fields\Main\RentSubsidy;
use ConnectorForPropstack\Propstack\Fields\Main\Sauna;
use ConnectorForPropstack\Propstack\Fields\Main\ServiceCharge;
use ConnectorForPropstack\Propstack\Fields\Main\ServiceChargeNet;
use ConnectorForPropstack\Propstack\Fields\Main\ServiceChargeVat;
use ConnectorForPropstack\Propstack\Fields\Main\ShortAddress;
use ConnectorForPropstack\Propstack\Fields\Main\SiteConstructibleType;
use ConnectorForPropstack\Propstack\Fields\Main\SiteDevelopmentType;
use ConnectorForPropstack\Propstack\Fields\Main\SoldPrice;
use ConnectorForPropstack\Propstack\Fields\Main\StartRentalDate;
use ConnectorForPropstack\Propstack\Fields\Main\StatusUpdatedAt;
use ConnectorForPropstack\Propstack\Fields\Main\StoreType;
use ConnectorForPropstack\Propstack\Fields\Main\Street;
use ConnectorForPropstack\Propstack\Fields\Main\SummerResidencePractical;
use ConnectorForPropstack\Propstack\Fields\Main\SwimmingPool;
use ConnectorForPropstack\Propstack\Fields\Main\Tenancy;
use ConnectorForPropstack\Propstack\Fields\Main\Terrace;
use ConnectorForPropstack\Propstack\Fields\Main\ThermalCharacteristic;
use ConnectorForPropstack\Propstack\Fields\Main\ThermalCharacteristicElectricity;
use ConnectorForPropstack\Propstack\Fields\Main\ThermalCharacteristicHeating;
use ConnectorForPropstack\Propstack\Fields\Main\TotalCommission;
use ConnectorForPropstack\Propstack\Fields\Main\TotalFloorSpace;
use ConnectorForPropstack\Propstack\Fields\Main\TotalRent;
use ConnectorForPropstack\Propstack\Fields\Main\TotalRentNet;
use ConnectorForPropstack\Propstack\Fields\Main\TotalRentVat;
use ConnectorForPropstack\Propstack\Fields\Main\UnitId;
use ConnectorForPropstack\Propstack\Fields\Main\UpdatedAt;
use ConnectorForPropstack\Propstack\Fields\Main\UpdatedAtFormatted;
use ConnectorForPropstack\Propstack\Fields\Main\UsableFloorSpace;
use ConnectorForPropstack\Propstack\Fields\Main\ValuationPrice;
use ConnectorForPropstack\Propstack\Fields\Main\ValuationPriceFrom;
use ConnectorForPropstack\Propstack\Fields\Main\ValuationPriceTo;
use ConnectorForPropstack\Propstack\Fields\Main\Vat;
use ConnectorForPropstack\Propstack\Fields\Main\WellnessArea;
use ConnectorForPropstack\Propstack\Fields\Main\WinterGarden;
use ConnectorForPropstack\Propstack\Fields\Main\YieldActual;
use ConnectorForPropstack\Propstack\Fields\Main\YieldTarget;
use ConnectorForPropstack\Propstack\Fields\Main\ZipCode;
use ConnectorForPropstack\Propstack\Fields\Main\PricePerSqm;
use ConnectorForPropstackPro\Propstack\Taxonomies\ObjectTypes\Industry;
use WPML\TM\TranslationProxy\Services\Project\SiteDetails;

/**
 * Object to handle the object type Apartment.
 */
class Apartment extends Object_Type_Base {

	/**
	 * Define the API name.
	 *
	 * @var string
	 */
	protected string $api = 'APARTMENT';

	/**
	 * Define the slug.
	 *
	 * @var string
	 */
	protected string $slug = 'apartment';

	/**
	 * Constructor for this object.
	 */
	public function __construct() {}

	/**
	 * Return the label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Apartment', 'connector-for-propstack' );
	}

	/**
	 * Return a description for this object type.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Show data of your apartment', 'connector-for-propstack' );
	}

	/**
	 * Return the fields.
	 *
	 * @return array<int,Field_Base>
	 */
	public function get_fields(): array {
		$fields = array(
			// basics.
			new ObjectId(),
			new UnitId(),
			new ExposeId(),
			new ProjectId(),
			new Note(),
			// descriptions.
			new DescriptionNote(),
			new LocationNote(),
			new FurnishingNote(),
			new OtherNote(),
			new LongDescriptionNote(),
			new LongLocationNote(),
			new LongFurnishingNote(),
			new LongOtherNote(),
			// API.
			new ApiResponse(),
			// address.
			new Address(),
			new ShortAddress(),
			new Street(),
			new District(),
			new ZipCode(),
			new City(),
			// dates.
			new CreatedAt(),
			new CreatedAtFormatted(),
			new UpdatedAt(),
			new UpdatedAtFormatted(),
			new ConstructionYear(),
			new ConstructionYearUnknown(),
			new LastRefurbishment(),
			new StartRentalDate(),
			// custom fields.
			new CustomField(),
			// geo.
			new Latitude(),
			new Longitude(),
			// properties.
			new Floor(),
			new LivingSpace(),
			new NumberOfRooms(),
			new NumberOfApartments(),
			new NumberOfBathrooms(),
			new NumberOfParkingSpaces(),
			new NumberOfBalconies(),
			new NumberOfBedRooms(),
			new NumberOfTerraces(),
			new NetFloorSpace(),
			new BuiltInKitchen(),
			new GuestToilet(),
			new Balcony(),
			new Garden(),
			new HeatingType(),
			new FiringTypes(),
			new Cellar(),
			new PlotArea(),
			new Monument(),
			new CertificateOfEligibilityNeeded(),
			new FlatShareSuitable(),
			new HasCanteen(),
			new KitchenComplete(),
			new Terrace(),
			new AutoLift(),
			new NonSmoker(),
			new Loggia(),
			new WinterGarden(),
			new WellnessArea(),
			new Chimney(),
			new SwimmingPool(),
			new Sauna(),
			new AlarmSystem(),
			new AirConditioning(),
			new HasFurniture(),
			new Rented(),
			new LodgerFlat(),
			new Lift(),
			new BarrierFree(),
			new PetsAllowed(),
			new SummerResidencePractical(),
			new AdditionalArea(),
			new ApartmentNumber(),
			new ApartmentType(),
			new BalconySpace(),
			new Bathroom(),
			new BuildingEnergyRatingType(),
			new CellarSpace(),
			new Co2Emission(),
			new CommercializationType(),
			new Condition(),
			new ConnectedLoad(),
			new ConstructionPhase(),
			new ConstructionType(),
			new ContractType(),
			new CoOwnershipShare(),
			new Corridor(),
			new CostBalcony(),
			new CostLift(),
			new CostOther(),
			new Deposit(),
			new DistanceToAirport(),
			new DistanceToAirportInKm(),
			new DistanceToAirportLength(),
			new DistanceToFm(),
			new DistanceToFmInKm(),
			new DistanceToFmLength(),
			new DistanceToMrs(),
			new DistanceToMrsInKm(),
			new DistanceToPt(),
			new DistanceToPtInKm(),
			new DistanceToPtLength(),
			new Duration(),
			new DurationFrom(),
			new DurationUntil(),
			new EndRentalDate(),
			new FinancialContribution(),
			new FlooringType(),
			new FloorLoad(),
			new Floorplans(),
			new FloorPosition(),
			new ForBidding(),
			new FreeFrom(),
			new FreeUntil(),
			new GardenSpace(),
			new Gender(),
			new HallHeight(),
			new HeatingCosts(),
			new HeatingCostsNet(),
			new HeatingCostsVat(),
			new HideAddress(),
			new HouseNumber(),
			new IndustrialArea(),
			new InteriorQuality(),
			new InvestmentType(),
			new LanCables(),
			new LocationClassificationType(),
			new LocationName(),
			new MaintenanceReserve(),
			new MaxNumberOfPersons(),
			new MaxRentalTime(),
			new MinDivisible(),
			new MinRentalTime(),
			new NumberBeds(),
			new NumberOfCommercials(),
			new NumberSeats(),
			new ParkingSpaceNumber(),
			new ParkingSpacePrice(),
			new ParkingSpaceType(),
			new ParkingSpaceTypes(),
			new RecommendedUseTypes(),
			new RecurringCosts(),
			new RecurringCostsNet(),
			new RecurringCostsVat(),
			new Region(),
			new RentalIncome(),
			new RentalIncomeActual(),
			new RentalIncomeTarget(),
			new RentDuration(),
			new RentDurations(),
			new Renter(),
			new RentSubsidy(),
			new ServiceCharge(),
			new ServiceChargeNet(),
			new ServiceChargeVat(),
			new SiteConstructibleType(),
			new SiteDevelopmentType(),
			new StatusUpdatedAt(),
			new StoreType(),
			new Tenancy(),
			new ThermalCharacteristic(),
			new ThermalCharacteristicElectricity(),
			new ThermalCharacteristicHeating(),
			new TotalCommission(),
			new TotalFloorSpace(),
			new UsableFloorSpace(),
			new ValuationPrice(),
			new ValuationPriceFrom(),
			new ValuationPriceTo(),
			new Vat(),
			// prices.
			new Price(),
			new Courtage(),
			new CourtageNote(),
			new Currency(),
			new PricePerSqm(),
			new PriceMultiplier(),
			new PriceMultiplierTarget(),
			new PriceOnInquiry(),
			new HeatingCostsInServiceCharge(),
			new EnergyConsumptionContainsWarmWater(),
			new TotalRent(),
			new TotalRentNet(),
			new TotalRentVat(),
			new SoldPrice(),
			new YieldActual(),
			new YieldTarget(),
			new BaseRent(),
			new BaseRentNet(),
			new BaseRentVat(),
			new OtherCosts(),
			new OtherCostsNet(),
			new OtherCostsVat(),
			new OtherRent(),
			new OtherRentNet(),
			new OtherRentVat(),
			new PriceNet(),
			new PriceVat(),
			new PriceType(),
			// energy.
			new EnergyCertificateAvailability(),
			new EnergyCertificateCreationDate(),
			new EnergyCertificateStartDate(),
			new EnergyCertificateEndDate(),
			new EnergyEfficiencyClass(),
		);

		$instance = $this;
		/**
		 * Filter the list of files in this object type.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,Field_Base> $fields List of fields.
		 * @param Object_Type_Base $instance The object type object.
		 */
		return apply_filters( 'cfprop_object_type_fields', $fields, $instance );
	}

	/**
	 * Return the list of default disabled fields for this object type.
	 *
	 * @return array<int,Field_Base>
	 */
	protected function get_default_disabled_fields(): array {
		$fields = array(
			new DescriptionNote(),
			new ApiResponse(),
			new Address(),
		);

		$instance = $this;
		/**
		 * Filter the list of files in this object type.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,Field_Base> $fields List of fields.
		 * @param Object_Type_Base $instance The object type object.
		 */
		return apply_filters( 'cfprop_object_type_default_disabled_fields', $fields, $instance );
	}

	/**
	 * Return the most important fields as objects.
	 *
	 * @return array<int,Field_Base>
	 */
	public function get_important_fields_as_objects(): array {
		return array(
			new ObjectId(),
			new NumberOfRooms(),
			new LivingSpace(),
		);
	}
}

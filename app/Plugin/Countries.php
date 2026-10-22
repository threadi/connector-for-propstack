<?php
/**
 * File to handle the names of countries.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to return the name of a country by its ISO 3166 code.
 *
 * Propstack delivers countries with their 3-letter code (ISO 3166-1 alpha-3, e.g. "DEU").
 * The name is shown in the language of the website, if the PHP extension "intl" is available.
 * Otherwise, the English name is used.
 */
class Countries {
	/**
	 * Return the list of countries: the 3-letter code => array with the 2-letter code and the English name.
	 *
	 * @return array<string,array<int,string>>
	 */
	public static function get_countries(): array {
		$countries = array(
			'ABW' => array( 'AW', 'Aruba' ),
			'AFG' => array( 'AF', 'Afghanistan' ),
			'AGO' => array( 'AO', 'Angola' ),
			'AIA' => array( 'AI', 'Anguilla' ),
			'ALA' => array( 'AX', 'Åland Islands' ),
			'ALB' => array( 'AL', 'Albania' ),
			'AND' => array( 'AD', 'Andorra' ),
			'ARE' => array( 'AE', 'United Arab Emirates' ),
			'ARG' => array( 'AR', 'Argentina' ),
			'ARM' => array( 'AM', 'Armenia' ),
			'ASM' => array( 'AS', 'American Samoa' ),
			'ATA' => array( 'AQ', 'Antarctica' ),
			'ATF' => array( 'TF', 'French Southern Territories' ),
			'ATG' => array( 'AG', 'Antigua and Barbuda' ),
			'AUS' => array( 'AU', 'Australia' ),
			'AUT' => array( 'AT', 'Austria' ),
			'AZE' => array( 'AZ', 'Azerbaijan' ),
			'BDI' => array( 'BI', 'Burundi' ),
			'BEL' => array( 'BE', 'Belgium' ),
			'BEN' => array( 'BJ', 'Benin' ),
			'BES' => array( 'BQ', 'Bonaire, Sint Eustatius and Saba' ),
			'BFA' => array( 'BF', 'Burkina Faso' ),
			'BGD' => array( 'BD', 'Bangladesh' ),
			'BGR' => array( 'BG', 'Bulgaria' ),
			'BHR' => array( 'BH', 'Bahrain' ),
			'BHS' => array( 'BS', 'Bahamas' ),
			'BIH' => array( 'BA', 'Bosnia and Herzegovina' ),
			'BLM' => array( 'BL', 'Saint Barthélemy' ),
			'BLR' => array( 'BY', 'Belarus' ),
			'BLZ' => array( 'BZ', 'Belize' ),
			'BMU' => array( 'BM', 'Bermuda' ),
			'BOL' => array( 'BO', 'Bolivia' ),
			'BRA' => array( 'BR', 'Brazil' ),
			'BRB' => array( 'BB', 'Barbados' ),
			'BRN' => array( 'BN', 'Brunei Darussalam' ),
			'BTN' => array( 'BT', 'Bhutan' ),
			'BVT' => array( 'BV', 'Bouvet Island' ),
			'BWA' => array( 'BW', 'Botswana' ),
			'CAF' => array( 'CF', 'Central African Republic' ),
			'CAN' => array( 'CA', 'Canada' ),
			'CCK' => array( 'CC', 'Cocos (Keeling) Islands' ),
			'CHE' => array( 'CH', 'Switzerland' ),
			'CHL' => array( 'CL', 'Chile' ),
			'CHN' => array( 'CN', 'China' ),
			'CIV' => array( 'CI', 'Côte d\'Ivoire' ),
			'CMR' => array( 'CM', 'Cameroon' ),
			'COD' => array( 'CD', 'Congo, The Democratic Republic of the' ),
			'COG' => array( 'CG', 'Congo' ),
			'COK' => array( 'CK', 'Cook Islands' ),
			'COL' => array( 'CO', 'Colombia' ),
			'COM' => array( 'KM', 'Comoros' ),
			'CPV' => array( 'CV', 'Cabo Verde' ),
			'CRI' => array( 'CR', 'Costa Rica' ),
			'CUB' => array( 'CU', 'Cuba' ),
			'CUW' => array( 'CW', 'Curaçao' ),
			'CXR' => array( 'CX', 'Christmas Island' ),
			'CYM' => array( 'KY', 'Cayman Islands' ),
			'CYP' => array( 'CY', 'Cyprus' ),
			'CZE' => array( 'CZ', 'Czechia' ),
			'DEU' => array( 'DE', 'Germany' ),
			'DJI' => array( 'DJ', 'Djibouti' ),
			'DMA' => array( 'DM', 'Dominica' ),
			'DNK' => array( 'DK', 'Denmark' ),
			'DOM' => array( 'DO', 'Dominican Republic' ),
			'DZA' => array( 'DZ', 'Algeria' ),
			'ECU' => array( 'EC', 'Ecuador' ),
			'EGY' => array( 'EG', 'Egypt' ),
			'ERI' => array( 'ER', 'Eritrea' ),
			'ESH' => array( 'EH', 'Western Sahara' ),
			'ESP' => array( 'ES', 'Spain' ),
			'EST' => array( 'EE', 'Estonia' ),
			'ETH' => array( 'ET', 'Ethiopia' ),
			'FIN' => array( 'FI', 'Finland' ),
			'FJI' => array( 'FJ', 'Fiji' ),
			'FLK' => array( 'FK', 'Falkland Islands (Malvinas)' ),
			'FRA' => array( 'FR', 'France' ),
			'FRO' => array( 'FO', 'Faroe Islands' ),
			'FSM' => array( 'FM', 'Micronesia, Federated States of' ),
			'GAB' => array( 'GA', 'Gabon' ),
			'GBR' => array( 'GB', 'United Kingdom' ),
			'GEO' => array( 'GE', 'Georgia' ),
			'GGY' => array( 'GG', 'Guernsey' ),
			'GHA' => array( 'GH', 'Ghana' ),
			'GIB' => array( 'GI', 'Gibraltar' ),
			'GIN' => array( 'GN', 'Guinea' ),
			'GLP' => array( 'GP', 'Guadeloupe' ),
			'GMB' => array( 'GM', 'Gambia' ),
			'GNB' => array( 'GW', 'Guinea-Bissau' ),
			'GNQ' => array( 'GQ', 'Equatorial Guinea' ),
			'GRC' => array( 'GR', 'Greece' ),
			'GRD' => array( 'GD', 'Grenada' ),
			'GRL' => array( 'GL', 'Greenland' ),
			'GTM' => array( 'GT', 'Guatemala' ),
			'GUF' => array( 'GF', 'French Guiana' ),
			'GUM' => array( 'GU', 'Guam' ),
			'GUY' => array( 'GY', 'Guyana' ),
			'HKG' => array( 'HK', 'Hong Kong' ),
			'HMD' => array( 'HM', 'Heard Island and McDonald Islands' ),
			'HND' => array( 'HN', 'Honduras' ),
			'HRV' => array( 'HR', 'Croatia' ),
			'HTI' => array( 'HT', 'Haiti' ),
			'HUN' => array( 'HU', 'Hungary' ),
			'IDN' => array( 'ID', 'Indonesia' ),
			'IMN' => array( 'IM', 'Isle of Man' ),
			'IND' => array( 'IN', 'India' ),
			'IOT' => array( 'IO', 'British Indian Ocean Territory' ),
			'IRL' => array( 'IE', 'Ireland' ),
			'IRN' => array( 'IR', 'Iran' ),
			'IRQ' => array( 'IQ', 'Iraq' ),
			'ISL' => array( 'IS', 'Iceland' ),
			'ISR' => array( 'IL', 'Israel' ),
			'ITA' => array( 'IT', 'Italy' ),
			'JAM' => array( 'JM', 'Jamaica' ),
			'JEY' => array( 'JE', 'Jersey' ),
			'JOR' => array( 'JO', 'Jordan' ),
			'JPN' => array( 'JP', 'Japan' ),
			'KAZ' => array( 'KZ', 'Kazakhstan' ),
			'KEN' => array( 'KE', 'Kenya' ),
			'KGZ' => array( 'KG', 'Kyrgyzstan' ),
			'KHM' => array( 'KH', 'Cambodia' ),
			'KIR' => array( 'KI', 'Kiribati' ),
			'KNA' => array( 'KN', 'Saint Kitts and Nevis' ),
			'KOR' => array( 'KR', 'South Korea' ),
			'KWT' => array( 'KW', 'Kuwait' ),
			'LAO' => array( 'LA', 'Laos' ),
			'LBN' => array( 'LB', 'Lebanon' ),
			'LBR' => array( 'LR', 'Liberia' ),
			'LBY' => array( 'LY', 'Libya' ),
			'LCA' => array( 'LC', 'Saint Lucia' ),
			'LIE' => array( 'LI', 'Liechtenstein' ),
			'LKA' => array( 'LK', 'Sri Lanka' ),
			'LSO' => array( 'LS', 'Lesotho' ),
			'LTU' => array( 'LT', 'Lithuania' ),
			'LUX' => array( 'LU', 'Luxembourg' ),
			'LVA' => array( 'LV', 'Latvia' ),
			'MAC' => array( 'MO', 'Macao' ),
			'MAF' => array( 'MF', 'Saint Martin (French part)' ),
			'MAR' => array( 'MA', 'Morocco' ),
			'MCO' => array( 'MC', 'Monaco' ),
			'MDA' => array( 'MD', 'Moldova' ),
			'MDG' => array( 'MG', 'Madagascar' ),
			'MDV' => array( 'MV', 'Maldives' ),
			'MEX' => array( 'MX', 'Mexico' ),
			'MHL' => array( 'MH', 'Marshall Islands' ),
			'MKD' => array( 'MK', 'North Macedonia' ),
			'MLI' => array( 'ML', 'Mali' ),
			'MLT' => array( 'MT', 'Malta' ),
			'MMR' => array( 'MM', 'Myanmar' ),
			'MNE' => array( 'ME', 'Montenegro' ),
			'MNG' => array( 'MN', 'Mongolia' ),
			'MNP' => array( 'MP', 'Northern Mariana Islands' ),
			'MOZ' => array( 'MZ', 'Mozambique' ),
			'MRT' => array( 'MR', 'Mauritania' ),
			'MSR' => array( 'MS', 'Montserrat' ),
			'MTQ' => array( 'MQ', 'Martinique' ),
			'MUS' => array( 'MU', 'Mauritius' ),
			'MWI' => array( 'MW', 'Malawi' ),
			'MYS' => array( 'MY', 'Malaysia' ),
			'MYT' => array( 'YT', 'Mayotte' ),
			'NAM' => array( 'NA', 'Namibia' ),
			'NCL' => array( 'NC', 'New Caledonia' ),
			'NER' => array( 'NE', 'Niger' ),
			'NFK' => array( 'NF', 'Norfolk Island' ),
			'NGA' => array( 'NG', 'Nigeria' ),
			'NIC' => array( 'NI', 'Nicaragua' ),
			'NIU' => array( 'NU', 'Niue' ),
			'NLD' => array( 'NL', 'Netherlands' ),
			'NOR' => array( 'NO', 'Norway' ),
			'NPL' => array( 'NP', 'Nepal' ),
			'NRU' => array( 'NR', 'Nauru' ),
			'NZL' => array( 'NZ', 'New Zealand' ),
			'OMN' => array( 'OM', 'Oman' ),
			'PAK' => array( 'PK', 'Pakistan' ),
			'PAN' => array( 'PA', 'Panama' ),
			'PCN' => array( 'PN', 'Pitcairn' ),
			'PER' => array( 'PE', 'Peru' ),
			'PHL' => array( 'PH', 'Philippines' ),
			'PLW' => array( 'PW', 'Palau' ),
			'PNG' => array( 'PG', 'Papua New Guinea' ),
			'POL' => array( 'PL', 'Poland' ),
			'PRI' => array( 'PR', 'Puerto Rico' ),
			'PRK' => array( 'KP', 'North Korea' ),
			'PRT' => array( 'PT', 'Portugal' ),
			'PRY' => array( 'PY', 'Paraguay' ),
			'PSE' => array( 'PS', 'Palestine, State of' ),
			'PYF' => array( 'PF', 'French Polynesia' ),
			'QAT' => array( 'QA', 'Qatar' ),
			'REU' => array( 'RE', 'Réunion' ),
			'ROU' => array( 'RO', 'Romania' ),
			'RUS' => array( 'RU', 'Russian Federation' ),
			'RWA' => array( 'RW', 'Rwanda' ),
			'SAU' => array( 'SA', 'Saudi Arabia' ),
			'SDN' => array( 'SD', 'Sudan' ),
			'SEN' => array( 'SN', 'Senegal' ),
			'SGP' => array( 'SG', 'Singapore' ),
			'SGS' => array( 'GS', 'South Georgia and the South Sandwich Islands' ),
			'SHN' => array( 'SH', 'Saint Helena, Ascension and Tristan da Cunha' ),
			'SJM' => array( 'SJ', 'Svalbard and Jan Mayen' ),
			'SLB' => array( 'SB', 'Solomon Islands' ),
			'SLE' => array( 'SL', 'Sierra Leone' ),
			'SLV' => array( 'SV', 'El Salvador' ),
			'SMR' => array( 'SM', 'San Marino' ),
			'SOM' => array( 'SO', 'Somalia' ),
			'SPM' => array( 'PM', 'Saint Pierre and Miquelon' ),
			'SRB' => array( 'RS', 'Serbia' ),
			'SSD' => array( 'SS', 'South Sudan' ),
			'STP' => array( 'ST', 'Sao Tome and Principe' ),
			'SUR' => array( 'SR', 'Suriname' ),
			'SVK' => array( 'SK', 'Slovakia' ),
			'SVN' => array( 'SI', 'Slovenia' ),
			'SWE' => array( 'SE', 'Sweden' ),
			'SWZ' => array( 'SZ', 'Eswatini' ),
			'SXM' => array( 'SX', 'Sint Maarten (Dutch part)' ),
			'SYC' => array( 'SC', 'Seychelles' ),
			'SYR' => array( 'SY', 'Syria' ),
			'TCA' => array( 'TC', 'Turks and Caicos Islands' ),
			'TCD' => array( 'TD', 'Chad' ),
			'TGO' => array( 'TG', 'Togo' ),
			'THA' => array( 'TH', 'Thailand' ),
			'TJK' => array( 'TJ', 'Tajikistan' ),
			'TKL' => array( 'TK', 'Tokelau' ),
			'TKM' => array( 'TM', 'Turkmenistan' ),
			'TLS' => array( 'TL', 'Timor-Leste' ),
			'TON' => array( 'TO', 'Tonga' ),
			'TTO' => array( 'TT', 'Trinidad and Tobago' ),
			'TUN' => array( 'TN', 'Tunisia' ),
			'TUR' => array( 'TR', 'Türkiye' ),
			'TUV' => array( 'TV', 'Tuvalu' ),
			'TWN' => array( 'TW', 'Taiwan' ),
			'TZA' => array( 'TZ', 'Tanzania' ),
			'UGA' => array( 'UG', 'Uganda' ),
			'UKR' => array( 'UA', 'Ukraine' ),
			'UMI' => array( 'UM', 'United States Minor Outlying Islands' ),
			'URY' => array( 'UY', 'Uruguay' ),
			'USA' => array( 'US', 'United States' ),
			'UZB' => array( 'UZ', 'Uzbekistan' ),
			'VAT' => array( 'VA', 'Holy See (Vatican City State)' ),
			'VCT' => array( 'VC', 'Saint Vincent and the Grenadines' ),
			'VEN' => array( 'VE', 'Venezuela' ),
			'VGB' => array( 'VG', 'Virgin Islands, British' ),
			'VIR' => array( 'VI', 'Virgin Islands, U.S.' ),
			'VNM' => array( 'VN', 'Vietnam' ),
			'VUT' => array( 'VU', 'Vanuatu' ),
			'WLF' => array( 'WF', 'Wallis and Futuna' ),
			'WSM' => array( 'WS', 'Samoa' ),
			'YEM' => array( 'YE', 'Yemen' ),
			'ZAF' => array( 'ZA', 'South Africa' ),
			'ZMB' => array( 'ZM', 'Zambia' ),
			'ZWE' => array( 'ZW', 'Zimbabwe' ),
		);

		/**
		 * Filter the list of countries with their 3-letter code, their 2-letter code and their English name.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param array<string,array<int,string>> $countries The list of countries.
		 */
		return apply_filters( 'cfprop_countries', $countries );
	}

	/**
	 * Return the name of the country with the given 3-letter code (or 2-letter code).
	 *
	 * Returns the given code, if it is not a known country.
	 *
	 * @param string $code The ISO 3166 code of the country.
	 *
	 * @return string
	 */
	public static function get_name( string $code ): string {
		// get the code in upper case.
		$code = strtoupper( trim( $code ) );

		// bail if no code is given.
		if ( '' === $code ) {
			return '';
		}

		// get the list of countries.
		$countries = self::get_countries();

		// get the entry of this country (by its 3-letter code or by its 2-letter code).
		$entry = $countries[ $code ] ?? false;
		if ( false === $entry ) {
			foreach ( $countries as $country ) {
				if ( isset( $country[0] ) && $code === $country[0] ) {
					$entry = $country;
					break;
				}
			}
		}

		// bail if the country is unknown.
		if ( ! is_array( $entry ) || empty( $entry[0] ) ) {
			return $code;
		}

		// use the name in the language of the website, if possible.
		if ( class_exists( '\Locale' ) ) {
			$name = \Locale::getDisplayRegion( '-' . $entry[0], determine_locale() );
			if ( is_string( $name ) && '' !== $name && $name !== $entry[0] ) {
				return $name;
			}
		}

		// return the English name.
		return $entry[1] ?? $code;
	}
}

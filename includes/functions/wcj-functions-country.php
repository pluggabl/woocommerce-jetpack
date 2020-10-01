<?php
/**
 * Booster for WooCommerce - Functions - Country
 *
 * @version 3.9.0
 * @author  Pluggabl LLC.
 */

if ( ! function_exists( 'wcj_maybe_add_european_union_countries' ) ) {
	/**
	 * wcj_maybe_add_european_union_countries.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    use where needed
	 */
	function wcj_maybe_add_european_union_countries( $countries ) {
		if ( ! empty( $countries ) && in_array( 'EU', $countries ) ) {
			$countries = array_merge( $countries, wcj_get_european_union_countries() );
		}
		return $countries;
	}
}

if ( ! function_exists( 'wcj_get_country_by_ip' ) ) {
	/**
	 * wcj_get_country_by_ip.
	 *
	 * @version 3.9.0
	 * @since   3.1.0
	 */
	function wcj_get_country_by_ip() {
		// Get the country by IP
		$location = ( class_exists( 'WC_Geolocation' ) ? WC_Geolocation::geolocate_ip() : array( 'country' => '' ) );
		// Base fallback
		if ( empty( $location['country'] ) ) {
			$location = apply_filters( 'woocommerce_customer_default_location', wcj_get_option( 'woocommerce_default_country' ) );
			if ( function_exists( 'wc_format_country_state_string' ) ) {
				$location = wc_format_country_state_string( $location );
			}
		}
		return ( isset( $location['country'] ) ? $location['country'] : '' );
	}
}

if ( ! function_exists( 'wcj_get_country_flag_by_code' ) ) {
	/**
	 * wcj_get_country_flag_by_code.
	 *
	 @version  2.9.1
	 */
	function wcj_get_country_flag_by_code( $country_code ) {
		$img      = '/assets/images/flag-icons/' . strtolower( $country_code ) . '.png';
		$img_path = wcj_plugin_path() . $img;
		$img_src  = wcj_plugin_url() . ( file_exists( $img_path ) ? $img : '/assets/images/flag-icons/no-flag.png' );
		return '<img src="' . $img_src . '" title="' . wcj_get_country_name_by_code( $country_code ) . '">';
	}
}

if ( ! function_exists( 'wcj_get_customer_country' ) ) {
	/**
	 * wcj_get_customer_country.
	 *
	 * @version 2.9.0
	 * @return  string
	 * @todo    re-check: there is also `wcj_customer_get_country()`
	 */
	function wcj_get_customer_country( $user_id ) {
		$user_meta = get_user_meta( $user_id );
		$billing_country  = isset( $user_meta['billing_country'][0] )  ? $user_meta['billing_country'][0]  : '';
		$shipping_country = isset( $user_meta['shipping_country'][0] ) ? $user_meta['shipping_country'][0] : '';
		return ( '' == $billing_country ) ? $shipping_country : $billing_country;
	}
}

if ( ! function_exists( 'wcj_get_european_union_countries_with_vat' ) ) {
	/**
	 * wcj_get_european_union_countries_with_vat.
	 *
	 * @version 3.2.0
	 * @return  array
	 * @todo    check `MC`, `IM`
	 */
	function wcj_get_european_union_countries_with_vat() {
		return array(
			'AT' => 20,
			'BE' => 21,
			'BG' => 20,
			'CY' => 19,
			'CZ' => 21,
			'DE' => 19,
			'DK' => 25,
			'EE' => 20,
			'ES' => 21,
			'FI' => 24,
			'FR' => 20,
			'GB' => 20,
			'GR' => 24, // 23
			'HU' => 27,
			'HR' => 25,
			'IE' => 23,
			'IT' => 22,
			'LT' => 21,
			'LU' => 17,
			'LV' => 21,
			'MT' => 18,
			'NL' => 21,
			'PL' => 23,
			'PT' => 23,
			'RO' => 19, // 20 // 24
			'SE' => 25,
			'SI' => 22,
			'SK' => 20,
		);
	}
}

if ( ! function_exists( 'wcj_get_european_union_countries' ) ) {
	/**
	 * wcj_get_european_union_countries.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function wcj_get_european_union_countries() {
		return array_keys( wcj_get_european_union_countries_with_vat() );
	}
}

if ( ! function_exists( 'wcj_get_country_name_by_code' ) ) {
	/**
	 * Get country name by country code.
	 *
	 * @version 2.9.0
	 * @return  string on success, boolean false otherwise
	 */
	function wcj_get_country_name_by_code( $country_code ) {
		$countries = wcj_get_countries();
		return ( isset( $countries[ $country_code ] ) ) ? $countries[ $country_code ] : false;
	}
}

if ( ! function_exists( 'wcj_get_states' ) ) {
	/**
	 * Get all states array.
	 *
	 * @version 2.4.4
	 * @since   2.4.4
	 * @return  array
	 */
	function wcj_get_states() {
		$base_country = WC()->countries->get_base_country();
		$states = WC()->countries->get_states( $base_country );
		return ( isset( $states ) && ! empty( $states ) ) ? $states : array();
	}
}

if ( ! function_exists( 'wcj_get_countries' ) ) {
	/**
	 * Get all countries array.
	 *
	 * @version 2.9.0
	 * @return  array
	 */
	function wcj_get_countries() {
		return array(
			'AF' => __( 'Afghanistan', 'woocommerce' ),
			'AX' => __( '&#197;land Islands', 'woocommerce' ),
			'AL' => __( 'Albania', 'woocommerce' ),
			'DZ' => __( 'Algeria', 'woocommerce' ),
			'AD' => __( 'Andorra', 'woocommerce' ),
			'AO' => __( 'Angola', 'woocommerce' ),
			'AI' => __( 'Anguilla', 'woocommerce' ),
			'AQ' => __( 'Antarctica', 'woocommerce' ),
			'AG' => __( 'Antigua and Barbuda', 'woocommerce' ),
			'AR' => __( 'Argentina', 'woocommerce' ),
			'AM' => __( 'Armenia', 'woocommerce' ),
			'AW' => __( 'Aruba', 'woocommerce' ),
			'AU' => __( 'Australia', 'woocommerce' ),
			'AT' => __( 'Austria', 'woocommerce' ),
			'AZ' => __( 'Azerbaijan', 'woocommerce' ),
			'BS' => __( 'Bahamas', 'woocommerce' ),
			'BH' => __( 'Bahrain', 'woocommerce' ),
			'BD' => __( 'Bangladesh', 'woocommerce' ),
			'BB' => __( 'Barbados', 'woocommerce' ),
			'BY' => __( 'Belarus', 'woocommerce' ),
			'BE' => __( 'Belgium', 'woocommerce' ),
			'PW' => __( 'Belau', 'woocommerce' ),
			'BZ' => __( 'Belize', 'woocommerce' ),
			'BJ' => __( 'Benin', 'woocommerce' ),
			'BM' => __( 'Bermuda', 'woocommerce' ),
			'BT' => __( 'Bhutan', 'woocommerce' ),
			'BO' => __( 'Bolivia', 'woocommerce' ),
			'BQ' => __( 'Bonaire, Saint Eustatius and Saba', 'woocommerce' ),
			'BA' => __( 'Bosnia and Herzegovina', 'woocommerce' ),
			'BW' => __( 'Botswana', 'woocommerce' ),
			'BV' => __( 'Bouvet Island', 'woocommerce' ),
			'BR' => __( 'Brazil', 'woocommerce' ),
			'IO' => __( 'British Indian Ocean Territory', 'woocommerce' ),
			'VG' => __( 'British Virgin Islands', 'woocommerce' ),
			'BN' => __( 'Brunei', 'woocommerce' ),
			'BG' => __( 'Bulgaria', 'woocommerce' ),
			'BF' => __( 'Burkina Faso', 'woocommerce' ),
			'BI' => __( 'Burundi', 'woocommerce' ),
			'KH' => __( 'Cambodia', 'woocommerce' ),
			'CM' => __( 'Cameroon', 'woocommerce' ),
			'CA' => __( 'Canada', 'woocommerce' ),
			'CV' => __( 'Cape Verde', 'woocommerce' ),
			'KY' => __( 'Cayman Islands', 'woocommerce' ),
			'CF' => __( 'Central African Republic', 'woocommerce' ),
			'TD' => __( 'Chad', 'woocommerce' ),
			'CL' => __( 'Chile', 'woocommerce' ),
			'CN' => __( 'China', 'woocommerce' ),
			'CX' => __( 'Christmas Island', 'woocommerce' ),
			'CC' => __( 'Cocos (Keeling) Islands', 'woocommerce' ),
			'CO' => __( 'Colombia', 'woocommerce' ),
			'KM' => __( 'Comoros', 'woocommerce' ),
			'CG' => __( 'Congo (Brazzaville)', 'woocommerce' ),
			'CD' => __( 'Congo (Kinshasa)', 'woocommerce' ),
			'CK' => __( 'Cook Islands', 'woocommerce' ),
			'CR' => __( 'Costa Rica', 'woocommerce' ),
			'HR' => __( 'Croatia', 'woocommerce' ),
			'CU' => __( 'Cuba', 'woocommerce' ),
			'CW' => __( 'Cura&Ccedil;ao', 'woocommerce' ),
			'CY' => __( 'Cyprus', 'woocommerce' ),
			'CZ' => __( 'Czech Republic', 'woocommerce' ),
			'DK' => __( 'Denmark', 'woocommerce' ),
			'DJ' => __( 'Djibouti', 'woocommerce' ),
			'DM' => __( 'Dominica', 'woocommerce' ),
			'DO' => __( 'Dominican Republic', 'woocommerce' ),
			'EC' => __( 'Ecuador', 'woocommerce' ),
			'EG' => __( 'Egypt', 'woocommerce' ),
			'SV' => __( 'El Salvador', 'woocommerce' ),
			'GQ' => __( 'Equatorial Guinea', 'woocommerce' ),
			'ER' => __( 'Eritrea', 'woocommerce' ),
			'EE' => __( 'Estonia', 'woocommerce' ),
			'ET' => __( 'Ethiopia', 'woocommerce' ),
			'FK' => __( 'Falkland Islands', 'woocommerce' ),
			'FO' => __( 'Faroe Islands', 'woocommerce' ),
			'FJ' => __( 'Fiji', 'woocommerce' ),
			'FI' => __( 'Finland', 'woocommerce' ),
			'FR' => __( 'France', 'woocommerce' ),
			'GF' => __( 'French Guiana', 'woocommerce' ),
			'PF' => __( 'French Polynesia', 'woocommerce' ),
			'TF' => __( 'French Southern Territories', 'woocommerce' ),
			'GA' => __( 'Gabon', 'woocommerce' ),
			'GM' => __( 'Gambia', 'woocommerce' ),
			'GE' => __( 'Georgia', 'woocommerce' ),
			'DE' => __( 'Germany', 'woocommerce' ),
			'GH' => __( 'Ghana', 'woocommerce' ),
			'GI' => __( 'Gibraltar', 'woocommerce' ),
			'GR' => __( 'Greece', 'woocommerce' ),
			'GL' => __( 'Greenland', 'woocommerce' ),
			'GD' => __( 'Grenada', 'woocommerce' ),
			'GP' => __( 'Guadeloupe', 'woocommerce' ),
			'GT' => __( 'Guatemala', 'woocommerce' ),
			'GG' => __( 'Guernsey', 'woocommerce' ),
			'GN' => __( 'Guinea', 'woocommerce' ),
			'GW' => __( 'Guinea-Bissau', 'woocommerce' ),
			'GY' => __( 'Guyana', 'woocommerce' ),
			'HT' => __( 'Haiti', 'woocommerce' ),
			'HM' => __( 'Heard Island and McDonald Islands', 'woocommerce' ),
			'HN' => __( 'Honduras', 'woocommerce' ),
			'HK' => __( 'Hong Kong', 'woocommerce' ),
			'HU' => __( 'Hungary', 'woocommerce' ),
			'IS' => __( 'Iceland', 'woocommerce' ),
			'IN' => __( 'India', 'woocommerce' ),
			'ID' => __( 'Indonesia', 'woocommerce' ),
			'IR' => __( 'Iran', 'woocommerce' ),
			'IQ' => __( 'Iraq', 'woocommerce' ),
			'IE' => __( 'Republic of Ireland', 'woocommerce' ),
			'IM' => __( 'Isle of Man', 'woocommerce' ),
			'IL' => __( 'Israel', 'woocommerce' ),
			'IT' => __( 'Italy', 'woocommerce' ),
			'CI' => __( 'Ivory Coast', 'woocommerce' ),
			'JM' => __( 'Jamaica', 'woocommerce' ),
			'JP' => __( 'Japan', 'woocommerce' ),
			'JE' => __( 'Jersey', 'woocommerce' ),
			'JO' => __( 'Jordan', 'woocommerce' ),
			'KZ' => __( 'Kazakhstan', 'woocommerce' ),
			'KE' => __( 'Kenya', 'woocommerce' ),
			'KI' => __( 'Kiribati', 'woocommerce' ),
			'KW' => __( 'Kuwait', 'woocommerce' ),
			'KG' => __( 'Kyrgyzstan', 'woocommerce' ),
			'LA' => __( 'Laos', 'woocommerce' ),
			'LV' => __( 'Latvia', 'woocommerce' ),
			'LB' => __( 'Lebanon', 'woocommerce' ),
			'LS' => __( 'Lesotho', 'woocommerce' ),
			'LR' => __( 'Liberia', 'woocommerce' ),
			'LY' => __( 'Libya', 'woocommerce' ),
			'LI' => __( 'Liechtenstein', 'woocommerce' ),
			'LT' => __( 'Lithuania', 'woocommerce' ),
			'LU' => __( 'Luxembourg', 'woocommerce' ),
			'MO' => __( 'Macao S.A.R., China', 'woocommerce' ),
			'MK' => __( 'Macedonia', 'woocommerce' ),
			'MG' => __( 'Madagascar', 'woocommerce' ),
			'MW' => __( 'Malawi', 'woocommerce' ),
			'MY' => __( 'Malaysia', 'woocommerce' ),
			'MV' => __( 'Maldives', 'woocommerce' ),
			'ML' => __( 'Mali', 'woocommerce' ),
			'MT' => __( 'Malta', 'woocommerce' ),
			'MH' => __( 'Marshall Islands', 'woocommerce' ),
			'MQ' => __( 'Martinique', 'woocommerce' ),
			'MR' => __( 'Mauritania', 'woocommerce' ),
			'MU' => __( 'Mauritius', 'woocommerce' ),
			'YT' => __( 'Mayotte', 'woocommerce' ),
			'MX' => __( 'Mexico', 'woocommerce' ),
			'FM' => __( 'Micronesia', 'woocommerce' ),
			'MD' => __( 'Moldova', 'woocommerce' ),
			'MC' => __( 'Monaco', 'woocommerce' ),
			'MN' => __( 'Mongolia', 'woocommerce' ),
			'ME' => __( 'Montenegro', 'woocommerce' ),
			'MS' => __( 'Montserrat', 'woocommerce' ),
			'MA' => __( 'Morocco', 'woocommerce' ),
			'MZ' => __( 'Mozambique', 'woocommerce' ),
			'MM' => __( 'Myanmar', 'woocommerce' ),
			'NA' => __( 'Namibia', 'woocommerce' ),
			'NR' => __( 'Nauru', 'woocommerce' ),
			'NP' => __( 'Nepal', 'woocommerce' ),
			'NL' => __( 'Netherlands', 'woocommerce' ),
			'AN' => __( 'Netherlands Antilles', 'woocommerce' ),
			'NC' => __( 'New Caledonia', 'woocommerce' ),
			'NZ' => __( 'New Zealand', 'woocommerce' ),
			'NI' => __( 'Nicaragua', 'woocommerce' ),
			'NE' => __( 'Niger', 'woocommerce' ),
			'NG' => __( 'Nigeria', 'woocommerce' ),
			'NU' => __( 'Niue', 'woocommerce' ),
			'NF' => __( 'Norfolk Island', 'woocommerce' ),
			'KP' => __( 'North Korea', 'woocommerce' ),
			'NO' => __( 'Norway', 'woocommerce' ),
			'OM' => __( 'Oman', 'woocommerce' ),
			'PK' => __( 'Pakistan', 'woocommerce' ),
			'PS' => __( 'Palestinian Territory', 'woocommerce' ),
			'PA' => __( 'Panama', 'woocommerce' ),
			'PG' => __( 'Papua New Guinea', 'woocommerce' ),
			'PY' => __( 'Paraguay', 'woocommerce' ),
			'PE' => __( 'Peru', 'woocommerce' ),
			'PH' => __( 'Philippines', 'woocommerce' ),
			'PN' => __( 'Pitcairn', 'woocommerce' ),
			'PL' => __( 'Poland', 'woocommerce' ),
			'PT' => __( 'Portugal', 'woocommerce' ),
			'QA' => __( 'Qatar', 'woocommerce' ),
			'RE' => __( 'Reunion', 'woocommerce' ),
			'RO' => __( 'Romania', 'woocommerce' ),
			'RU' => __( 'Russia', 'woocommerce' ),
			'RW' => __( 'Rwanda', 'woocommerce' ),
			'BL' => __( 'Saint Barth&eacute;lemy', 'woocommerce' ),
			'SH' => __( 'Saint Helena', 'woocommerce' ),
			'KN' => __( 'Saint Kitts and Nevis', 'woocommerce' ),
			'LC' => __( 'Saint Lucia', 'woocommerce' ),
			'MF' => __( 'Saint Martin (French part)', 'woocommerce' ),
			'SX' => __( 'Saint Martin (Dutch part)', 'woocommerce' ),
			'PM' => __( 'Saint Pierre and Miquelon', 'woocommerce' ),
			'VC' => __( 'Saint Vincent and the Grenadines', 'woocommerce' ),
			'SM' => __( 'San Marino', 'woocommerce' ),
			'ST' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'woocommerce' ),
			'SA' => __( 'Saudi Arabia', 'woocommerce' ),
			'SN' => __( 'Senegal', 'woocommerce' ),
			'RS' => __( 'Serbia', 'woocommerce' ),
			'SC' => __( 'Seychelles', 'woocommerce' ),
			'SL' => __( 'Sierra Leone', 'woocommerce' ),
			'SG' => __( 'Singapore', 'woocommerce' ),
			'SK' => __( 'Slovakia', 'woocommerce' ),
			'SI' => __( 'Slovenia', 'woocommerce' ),
			'SB' => __( 'Solomon Islands', 'woocommerce' ),
			'SO' => __( 'Somalia', 'woocommerce' ),
			'ZA' => __( 'South Africa', 'woocommerce' ),
			'GS' => __( 'South Georgia/Sandwich Islands', 'woocommerce' ),
			'KR' => __( 'South Korea', 'woocommerce' ),
			'SS' => __( 'South Sudan', 'woocommerce' ),
			'ES' => __( 'Spain', 'woocommerce' ),
			'LK' => __( 'Sri Lanka', 'woocommerce' ),
			'SD' => __( 'Sudan', 'woocommerce' ),
			'SR' => __( 'Suriname', 'woocommerce' ),
			'SJ' => __( 'Svalbard and Jan Mayen', 'woocommerce' ),
			'SZ' => __( 'Swaziland', 'woocommerce' ),
			'SE' => __( 'Sweden', 'woocommerce' ),
			'CH' => __( 'Switzerland', 'woocommerce' ),
			'SY' => __( 'Syria', 'woocommerce' ),
			'TW' => __( 'Taiwan', 'woocommerce' ),
			'TJ' => __( 'Tajikistan', 'woocommerce' ),
			'TZ' => __( 'Tanzania', 'woocommerce' ),
			'TH' => __( 'Thailand', 'woocommerce' ),
			'TL' => __( 'Timor-Leste', 'woocommerce' ),
			'TG' => __( 'Togo', 'woocommerce' ),
			'TK' => __( 'Tokelau', 'woocommerce' ),
			'TO' => __( 'Tonga', 'woocommerce' ),
			'TT' => __( 'Trinidad and Tobago', 'woocommerce' ),
			'TN' => __( 'Tunisia', 'woocommerce' ),
			'TR' => __( 'Turkey', 'woocommerce' ),
			'TM' => __( 'Turkmenistan', 'woocommerce' ),
			'TC' => __( 'Turks and Caicos Islands', 'woocommerce' ),
			'TV' => __( 'Tuvalu', 'woocommerce' ),
			'UG' => __( 'Uganda', 'woocommerce' ),
			'UA' => __( 'Ukraine', 'woocommerce' ),
			'AE' => __( 'United Arab Emirates', 'woocommerce' ),
			'GB' => __( 'United Kingdom (UK)', 'woocommerce' ),
			'US' => __( 'United States (US)', 'woocommerce' ),
			'UY' => __( 'Uruguay', 'woocommerce' ),
			'UZ' => __( 'Uzbekistan', 'woocommerce' ),
			'VU' => __( 'Vanuatu', 'woocommerce' ),
			'VA' => __( 'Vatican', 'woocommerce' ),
			'VE' => __( 'Venezuela', 'woocommerce' ),
			'VN' => __( 'Vietnam', 'woocommerce' ),
			'WF' => __( 'Wallis and Futuna', 'woocommerce' ),
			'EH' => __( 'Western Sahara', 'woocommerce' ),
			'WS' => __( 'Western Samoa', 'woocommerce' ),
			'YE' => __( 'Yemen', 'woocommerce' ),
			'ZM' => __( 'Zambia', 'woocommerce' ),
			'ZW' => __( 'Zimbabwe', 'woocommerce' ),
			'EU' => __( 'European Union', 'woocommerce' ),
		);
	}
}

if ( ! function_exists( 'wcj_get_currency_countries' ) ) {
	/**
	 * wcj_get_currency_countries.
	 *
	 * 158 currencies.
	 * Three-letter currency code (ISO 4217) => Two-letter countries codes (ISO 3166-1 alpha-2).
	 *
	 * @version 3.3.0
	 * @since   2.9.0
	 */
	function wcj_get_currency_countries() {
		return array(
			'AFN' => array( 'AF' ),
			'ALL' => array( 'AL' ),
			'DZD' => array( 'DZ' ),
			'USD' => array( 'US', 'AS', 'IO', 'GU', 'MH', 'FM', 'MP', 'PW', 'PR', 'TC', 'UM', 'VI' ),
			'EUR' => array( 'AD', 'AT', 'BE', 'CY', 'EE', 'FI', 'FR', 'GF', 'TF', 'DE', 'GR', 'GP', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'MQ', 'YT', 'MC', 'ME', 'NL', 'PT', 'RE', 'PM', 'SM', 'SK', 'SI', 'ES' ),
			'AOA' => array( 'AO' ),
			'XCD' => array( 'KN', 'AI', 'AQ', 'AG', 'DM', 'GD', 'MS', 'LC', 'VC' ),
			'ARS' => array( 'AR' ),
			'AMD' => array( 'AM' ),
			'AWG' => array( 'AW' ),
			'AUD' => array( 'AU', 'CX', 'CC', 'HM', 'KI', 'NR', 'NF', 'TV' ),
			'AZN' => array( 'AZ' ),
			'BSD' => array( 'BS' ),
			'BHD' => array( 'BH' ),
			'BDT' => array( 'BD' ),
			'BBD' => array( 'BB' ),
			'BYN' => array( 'BY' ),
			'BZD' => array( 'BZ' ),
			'XOF' => array( 'SN', 'BJ', 'BF', 'ML', 'NE', 'TG' ),
			'BMD' => array( 'BM' ),
			'BTN' => array( 'BT' ),
			'BOB' => array( 'BO' ),
			'BAM' => array( 'BA' ),
			'BWP' => array( 'BW' ),
			'NOK' => array( 'NO', 'BV', 'SJ' ),
			'BRL' => array( 'BR' ),
			'BND' => array( 'BN' ),
			'BGN' => array( 'BG' ),
			'BIF' => array( 'BI' ),
			'KHR' => array( 'KH' ),
			'XAF' => array( 'CF', 'CM', 'TD', 'CG', 'GQ', 'GA' ),
			'CAD' => array( 'CA' ),
			'CVE' => array( 'CV' ),
			'KYD' => array( 'KY' ),
			'CLP' => array( 'CL' ),
			'CNY' => array( 'CN' ),
			'RMB' => array( 'CN' ),
			'HKD' => array( 'HK' ),
			'COP' => array( 'CO' ),
			'KMF' => array( 'KM' ),
			'CDF' => array( 'CD' ),
			'NZD' => array( 'NZ', 'CK', 'NU', 'PN', 'TK' ),
			'CRC' => array( 'CR' ),
			'HRK' => array( 'HR' ),
			'CUP' => array( 'CU' ),
			'CUC' => array( 'CU' ),
			'CZK' => array( 'CZ' ),
			'DKK' => array( 'DK', 'FO', 'GL' ),
			'DJF' => array( 'DJ' ),
			'DOP' => array( 'DO' ),
			'ECS' => array( 'EC' ),
			'EGP' => array( 'EG' ),
			'SVC' => array( 'SV' ),
			'ERN' => array( 'ER' ),
			'ETB' => array( 'ET' ),
			'FKP' => array( 'FK' ),
			'FJD' => array( 'FJ' ),
			'GMD' => array( 'GM' ),
			'GEL' => array( 'GE' ),
			'GHS' => array( 'GH' ),
			'GIP' => array( 'GI' ),
			'QTQ' => array( 'GT' ),
			'GTQ' => array( 'GT' ),
			'GGP' => array( 'GG' ),
			'GNF' => array( 'GN' ),
			'GWP' => array( 'GW' ),
			'GYD' => array( 'GY' ),
			'HTG' => array( 'HT' ),
			'HNL' => array( 'HN' ),
			'HUF' => array( 'HU' ),
			'ISK' => array( 'IS' ),
			'INR' => array( 'IN' ),
			'IDR' => array( 'ID' ),
			'IRR' => array( 'IR' ),
			'IQD' => array( 'IQ' ),
			'GBP' => array( 'GB', 'IM', 'JE', 'GS' ),
			'ILS' => array( 'IL' ),
			'JMD' => array( 'JM' ),
			'JPY' => array( 'JP' ),
			'JOD' => array( 'JO' ),
			'KZT' => array( 'KZ' ),
			'KES' => array( 'KE' ),
			'KPW' => array( 'KP' ),
			'KRW' => array( 'KR' ),
			'KWD' => array( 'KW' ),
			'KGS' => array( 'KG' ),
			'LAK' => array( 'LA' ),
			'KIP' => array( 'LA' ),
			'LBP' => array( 'LB' ),
			'LSL' => array( 'LS' ),
			'LRD' => array( 'LR' ),
			'LYD' => array( 'LY' ),
			'CHF' => array( 'CH', 'LI' ),
			'MKD' => array( 'MK' ),
			'MGF' => array( 'MG' ),
			'MGA' => array( 'MG' ),
			'MWK' => array( 'MW' ),
			'MYR' => array( 'MY' ),
			'MVR' => array( 'MV' ),
			'MRO' => array( 'MR' ),
			'MUR' => array( 'MU' ),
			'MXN' => array( 'MX' ),
			'MDL' => array( 'MD' ),
			'MNT' => array( 'MN' ),
			'MAD' => array( 'MA', 'EH' ),
			'MZN' => array( 'MZ' ),
			'MZM' => array( 'MZ' ),
			'MMK' => array( 'MM' ),
			'NAD' => array( 'NA' ),
			'NPR' => array( 'NP' ),
			'ANG' => array( 'AN' ),
			'XPF' => array( 'NC', 'WF' ),
			'NIO' => array( 'NI' ),
			'NGN' => array( 'NG' ),
			'OMR' => array( 'OM' ),
			'PKR' => array( 'PK' ),
			'PAB' => array( 'PA' ),
			'PGK' => array( 'PG' ),
			'PYG' => array( 'PY' ),
			'PEN' => array( 'PE' ),
			'PHP' => array( 'PH' ),
			'PLN' => array( 'PL' ),
			'QAR' => array( 'QA' ),
			'RON' => array( 'RO' ),
			'RUB' => array( 'RU' ),
			'RWF' => array( 'RW' ),
			'SHP' => array( 'SH' ),
			'WST' => array( 'WS' ),
			'STD' => array( 'ST' ),
			'SAR' => array( 'SA' ),
			'RSD' => array( 'RS' ),
			'SCR' => array( 'SC' ),
			'SLL' => array( 'SL' ),
			'SGD' => array( 'SG' ),
			'SBD' => array( 'SB' ),
			'SOS' => array( 'SO' ),
			'ZAR' => array( 'ZA' ),
			'SSP' => array( 'SS' ),
			'LKR' => array( 'LK' ),
			'SDG' => array( 'SD' ),
			'SRD' => array( 'SR' ),
			'SZL' => array( 'SZ' ),
			'SEK' => array( 'SE' ),
			'SYP' => array( 'SY' ),
			'TWD' => array( 'TW' ),
			'TJS' => array( 'TJ' ),
			'TZS' => array( 'TZ' ),
			'THB' => array( 'TH' ),
			'TOP' => array( 'TO' ),
			'TTD' => array( 'TT' ),
			'TND' => array( 'TN' ),
			'TRY' => array( 'TR' ),
			'TMT' => array( 'TM' ),
			'TMM' => array( 'TM' ),
			'UGX' => array( 'UG' ),
			'UAH' => array( 'UA' ),
			'AED' => array( 'AE' ),
			'UYU' => array( 'UY' ),
			'UZS' => array( 'UZ' ),
			'VUV' => array( 'VU' ),
			'VEF' => array( 'VE' ),
			'VND' => array( 'VN' ),
			'YER' => array( 'YE' ),
			'ZMW' => array( 'ZM' ),
			'ZMK' => array( 'ZM' ),
			'ZWD' => array( 'ZW' ),
			'GQE' => array( 'CF' ),
			'MOP' => array( 'MO' ),
			// Former currencies
			'LTL' => array( 'LT' ),
			'LVL' => array( 'LV' ),
			'EEK' => array( 'EE' ),
			'SKK' => array( 'SK' ),
		);
	}
}

<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Utils class
 * 
 * @since 1.0.0
 */
class Utils {

	/**
	 * Check if pro version installed
	 * 
	 * @since 1.1.2
	 * @return boolean
	 */
	public static function is_pro_installed() {
		return file_exists(dirname(UTM_EVENT_TRACKER_PATH) . DIRECTORY_SEPARATOR . 'utm-event-tracker-and-analytics-pro/utm-event-tracker-and-analytics-pro.php');
	}

	/**
	 * Check if pro plugin activated
	 * 
	 * @since 1.1.2
	 * @return boolean
	 */
	public static function is_pro_activated() {
		return class_exists('\UTM_Event_Tracker_Pro\Main');
	}

	/**
	 * Check if pro plugin activated the license
	 * 
	 * @since 1.1.2
	 * @return boolean
	 */
	public static function license_activated() {
		return function_exists('utm_event_tracker_fs') && utm_event_tracker_fs()->can_use_premium_code();
	}

	/**
	 * Get country list
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_countries() {
		return array(
			'AF' => __('Afghanistan', 'utm-event-tracker-and-analytics'),
			'AL' => __('Albania', 'utm-event-tracker-and-analytics'),
			'DZ' => __('Algeria', 'utm-event-tracker-and-analytics'),
			'AS' => __('American Samoa', 'utm-event-tracker-and-analytics'),
			'AD' => __('Andorra', 'utm-event-tracker-and-analytics'),
			'AO' => __('Angola', 'utm-event-tracker-and-analytics'),
			'AI' => __('Anguilla', 'utm-event-tracker-and-analytics'),
			'AQ' => __('Antarctica', 'utm-event-tracker-and-analytics'),
			'AG' => __('Antigua and Barbuda', 'utm-event-tracker-and-analytics'),
			'AR' => __('Argentina', 'utm-event-tracker-and-analytics'),
			'AM' => __('Armenia', 'utm-event-tracker-and-analytics'),
			'AW' => __('Aruba', 'utm-event-tracker-and-analytics'),
			'AU' => __('Australia', 'utm-event-tracker-and-analytics'),
			'AT' => __('Austria', 'utm-event-tracker-and-analytics'),
			'AZ' => __('Azerbaijan', 'utm-event-tracker-and-analytics'),
			'BS' => __('Bahamas', 'utm-event-tracker-and-analytics'),
			'BH' => __('Bahrain', 'utm-event-tracker-and-analytics'),
			'BD' => __('Bangladesh', 'utm-event-tracker-and-analytics'),
			'BB' => __('Barbados', 'utm-event-tracker-and-analytics'),
			'BY' => __('Belarus', 'utm-event-tracker-and-analytics'),
			'BE' => __('Belgium', 'utm-event-tracker-and-analytics'),
			'BZ' => __('Belize', 'utm-event-tracker-and-analytics'),
			'BJ' => __('Benin', 'utm-event-tracker-and-analytics'),
			'BM' => __('Bermuda', 'utm-event-tracker-and-analytics'),
			'BT' => __('Bhutan', 'utm-event-tracker-and-analytics'),
			'BO' => __('Bolivia', 'utm-event-tracker-and-analytics'),
			'BA' => __('Bosnia and Herzegovina', 'utm-event-tracker-and-analytics'),
			'BW' => __('Botswana', 'utm-event-tracker-and-analytics'),
			'BV' => __('Bouvet Island', 'utm-event-tracker-and-analytics'),
			'BR' => __('Brazil', 'utm-event-tracker-and-analytics'),
			'BQ' => __('British Antarctic Territory', 'utm-event-tracker-and-analytics'),
			'IO' => __('British Indian Ocean Territory', 'utm-event-tracker-and-analytics'),
			'VG' => __('British Virgin Islands', 'utm-event-tracker-and-analytics'),
			'BN' => __('Brunei', 'utm-event-tracker-and-analytics'),
			'BG' => __('Bulgaria', 'utm-event-tracker-and-analytics'),
			'BF' => __('Burkina Faso', 'utm-event-tracker-and-analytics'),
			'BI' => __('Burundi', 'utm-event-tracker-and-analytics'),
			'KH' => __('Cambodia', 'utm-event-tracker-and-analytics'),
			'CM' => __('Cameroon', 'utm-event-tracker-and-analytics'),
			'CA' => __('Canada', 'utm-event-tracker-and-analytics'),
			'CT' => __('Canton and Enderbury Islands', 'utm-event-tracker-and-analytics'),
			'CV' => __('Cape Verde', 'utm-event-tracker-and-analytics'),
			'KY' => __('Cayman Islands', 'utm-event-tracker-and-analytics'),
			'CF' => __('Central African Republic', 'utm-event-tracker-and-analytics'),
			'TD' => __('Chad', 'utm-event-tracker-and-analytics'),
			'CL' => __('Chile', 'utm-event-tracker-and-analytics'),
			'CN' => __('China', 'utm-event-tracker-and-analytics'),
			'CX' => __('Christmas Island', 'utm-event-tracker-and-analytics'),
			'CC' => __('Cocos [Keeling] Islands', 'utm-event-tracker-and-analytics'),
			'CO' => __('Colombia', 'utm-event-tracker-and-analytics'),
			'KM' => __('Comoros', 'utm-event-tracker-and-analytics'),
			'CG' => __('Congo - Brazzaville', 'utm-event-tracker-and-analytics'),
			'CD' => __('Congo - Kinshasa', 'utm-event-tracker-and-analytics'),
			'CK' => __('Cook Islands', 'utm-event-tracker-and-analytics'),
			'CR' => __('Costa Rica', 'utm-event-tracker-and-analytics'),
			'HR' => __('Croatia', 'utm-event-tracker-and-analytics'),
			'CU' => __('Cuba', 'utm-event-tracker-and-analytics'),
			'CY' => __('Cyprus', 'utm-event-tracker-and-analytics'),
			'CZ' => __('Czech Republic', 'utm-event-tracker-and-analytics'),
			'CI' => __('Côte d\'Ivoire', 'utm-event-tracker-and-analytics'),
			'DK' => __('Denmark', 'utm-event-tracker-and-analytics'),
			'DJ' => __('Djibouti', 'utm-event-tracker-and-analytics'),
			'DM' => __('Dominica', 'utm-event-tracker-and-analytics'),
			'DO' => __('Dominican Republic', 'utm-event-tracker-and-analytics'),
			'NQ' => __('Dronning Maud Land', 'utm-event-tracker-and-analytics'),
			'DD' => __('East Germany', 'utm-event-tracker-and-analytics'),
			'EC' => __('Ecuador', 'utm-event-tracker-and-analytics'),
			'EG' => __('Egypt', 'utm-event-tracker-and-analytics'),
			'SV' => __('El Salvador', 'utm-event-tracker-and-analytics'),
			'GQ' => __('Equatorial Guinea', 'utm-event-tracker-and-analytics'),
			'ER' => __('Eritrea', 'utm-event-tracker-and-analytics'),
			'EE' => __('Estonia', 'utm-event-tracker-and-analytics'),
			'ET' => __('Ethiopia', 'utm-event-tracker-and-analytics'),
			'FK' => __('Falkland Islands', 'utm-event-tracker-and-analytics'),
			'FO' => __('Faroe Islands', 'utm-event-tracker-and-analytics'),
			'FJ' => __('Fiji', 'utm-event-tracker-and-analytics'),
			'FI' => __('Finland', 'utm-event-tracker-and-analytics'),
			'FR' => __('France', 'utm-event-tracker-and-analytics'),
			'GF' => __('French Guiana', 'utm-event-tracker-and-analytics'),
			'PF' => __('French Polynesia', 'utm-event-tracker-and-analytics'),
			'TF' => __('French Southern Territories', 'utm-event-tracker-and-analytics'),
			'FQ' => __('French Southern and Antarctic Territories', 'utm-event-tracker-and-analytics'),
			'GA' => __('Gabon', 'utm-event-tracker-and-analytics'),
			'GM' => __('Gambia', 'utm-event-tracker-and-analytics'),
			'GE' => __('Georgia', 'utm-event-tracker-and-analytics'),
			'DE' => __('Germany', 'utm-event-tracker-and-analytics'),
			'GH' => __('Ghana', 'utm-event-tracker-and-analytics'),
			'GI' => __('Gibraltar', 'utm-event-tracker-and-analytics'),
			'GR' => __('Greece', 'utm-event-tracker-and-analytics'),
			'GL' => __('Greenland', 'utm-event-tracker-and-analytics'),
			'GD' => __('Grenada', 'utm-event-tracker-and-analytics'),
			'GP' => __('Guadeloupe', 'utm-event-tracker-and-analytics'),
			'GU' => __('Guam', 'utm-event-tracker-and-analytics'),
			'GT' => __('Guatemala', 'utm-event-tracker-and-analytics'),
			'GG' => __('Guernsey', 'utm-event-tracker-and-analytics'),
			'GN' => __('Guinea', 'utm-event-tracker-and-analytics'),
			'GW' => __('Guinea-Bissau', 'utm-event-tracker-and-analytics'),
			'GY' => __('Guyana', 'utm-event-tracker-and-analytics'),
			'HT' => __('Haiti', 'utm-event-tracker-and-analytics'),
			'HM' => __('Heard Island and McDonald Islands', 'utm-event-tracker-and-analytics'),
			'HN' => __('Honduras', 'utm-event-tracker-and-analytics'),
			'HK' => __('Hong Kong SAR China', 'utm-event-tracker-and-analytics'),
			'HU' => __('Hungary', 'utm-event-tracker-and-analytics'),
			'IS' => __('Iceland', 'utm-event-tracker-and-analytics'),
			'IN' => __('India', 'utm-event-tracker-and-analytics'),
			'ID' => __('Indonesia', 'utm-event-tracker-and-analytics'),
			'IR' => __('Iran', 'utm-event-tracker-and-analytics'),
			'IQ' => __('Iraq', 'utm-event-tracker-and-analytics'),
			'IE' => __('Ireland', 'utm-event-tracker-and-analytics'),
			'IM' => __('Isle of Man', 'utm-event-tracker-and-analytics'),
			'IL' => __('Israel', 'utm-event-tracker-and-analytics'),
			'IT' => __('Italy', 'utm-event-tracker-and-analytics'),
			'JM' => __('Jamaica', 'utm-event-tracker-and-analytics'),
			'JP' => __('Japan', 'utm-event-tracker-and-analytics'),
			'JE' => __('Jersey', 'utm-event-tracker-and-analytics'),
			'JT' => __('Johnston Island', 'utm-event-tracker-and-analytics'),
			'JO' => __('Jordan', 'utm-event-tracker-and-analytics'),
			'KZ' => __('Kazakhstan', 'utm-event-tracker-and-analytics'),
			'KE' => __('Kenya', 'utm-event-tracker-and-analytics'),
			'KI' => __('Kiribati', 'utm-event-tracker-and-analytics'),
			'KW' => __('Kuwait', 'utm-event-tracker-and-analytics'),
			'KG' => __('Kyrgyzstan', 'utm-event-tracker-and-analytics'),
			'LA' => __('Laos', 'utm-event-tracker-and-analytics'),
			'LV' => __('Latvia', 'utm-event-tracker-and-analytics'),
			'LB' => __('Lebanon', 'utm-event-tracker-and-analytics'),
			'LS' => __('Lesotho', 'utm-event-tracker-and-analytics'),
			'LR' => __('Liberia', 'utm-event-tracker-and-analytics'),
			'LY' => __('Libya', 'utm-event-tracker-and-analytics'),
			'LI' => __('Liechtenstein', 'utm-event-tracker-and-analytics'),
			'LT' => __('Lithuania', 'utm-event-tracker-and-analytics'),
			'LU' => __('Luxembourg', 'utm-event-tracker-and-analytics'),
			'MO' => __('Macau SAR China', 'utm-event-tracker-and-analytics'),
			'MK' => __('Macedonia', 'utm-event-tracker-and-analytics'),
			'MG' => __('Madagascar', 'utm-event-tracker-and-analytics'),
			'MW' => __('Malawi', 'utm-event-tracker-and-analytics'),
			'MY' => __('Malaysia', 'utm-event-tracker-and-analytics'),
			'MV' => __('Maldives', 'utm-event-tracker-and-analytics'),
			'ML' => __('Mali', 'utm-event-tracker-and-analytics'),
			'MT' => __('Malta', 'utm-event-tracker-and-analytics'),
			'MH' => __('Marshall Islands', 'utm-event-tracker-and-analytics'),
			'MQ' => __('Martinique', 'utm-event-tracker-and-analytics'),
			'MR' => __('Mauritania', 'utm-event-tracker-and-analytics'),
			'MU' => __('Mauritius', 'utm-event-tracker-and-analytics'),
			'YT' => __('Mayotte', 'utm-event-tracker-and-analytics'),
			'FX' => __('Metropolitan France', 'utm-event-tracker-and-analytics'),
			'MX' => __('Mexico', 'utm-event-tracker-and-analytics'),
			'FM' => __('Micronesia', 'utm-event-tracker-and-analytics'),
			'MI' => __('Midway Islands', 'utm-event-tracker-and-analytics'),
			'MD' => __('Moldova', 'utm-event-tracker-and-analytics'),
			'MC' => __('Monaco', 'utm-event-tracker-and-analytics'),
			'MN' => __('Mongolia', 'utm-event-tracker-and-analytics'),
			'ME' => __('Montenegro', 'utm-event-tracker-and-analytics'),
			'MS' => __('Montserrat', 'utm-event-tracker-and-analytics'),
			'MA' => __('Morocco', 'utm-event-tracker-and-analytics'),
			'MZ' => __('Mozambique', 'utm-event-tracker-and-analytics'),
			'MM' => __('Myanmar [Burma]', 'utm-event-tracker-and-analytics'),
			'NA' => __('Namibia', 'utm-event-tracker-and-analytics'),
			'NR' => __('Nauru', 'utm-event-tracker-and-analytics'),
			'NP' => __('Nepal', 'utm-event-tracker-and-analytics'),
			'NL' => __('Netherlands', 'utm-event-tracker-and-analytics'),
			'AN' => __('Netherlands Antilles', 'utm-event-tracker-and-analytics'),
			'NT' => __('Neutral Zone', 'utm-event-tracker-and-analytics'),
			'NC' => __('New Caledonia', 'utm-event-tracker-and-analytics'),
			'NZ' => __('New Zealand', 'utm-event-tracker-and-analytics'),
			'NI' => __('Nicaragua', 'utm-event-tracker-and-analytics'),
			'NE' => __('Niger', 'utm-event-tracker-and-analytics'),
			'NG' => __('Nigeria', 'utm-event-tracker-and-analytics'),
			'NU' => __('Niue', 'utm-event-tracker-and-analytics'),
			'NF' => __('Norfolk Island', 'utm-event-tracker-and-analytics'),
			'KP' => __('North Korea', 'utm-event-tracker-and-analytics'),
			'VD' => __('North Vietnam', 'utm-event-tracker-and-analytics'),
			'MP' => __('Northern Mariana Islands', 'utm-event-tracker-and-analytics'),
			'NO' => __('Norway', 'utm-event-tracker-and-analytics'),
			'OM' => __('Oman', 'utm-event-tracker-and-analytics'),
			'PC' => __('Pacific Islands Trust Territory', 'utm-event-tracker-and-analytics'),
			'PK' => __('Pakistan', 'utm-event-tracker-and-analytics'),
			'PW' => __('Palau', 'utm-event-tracker-and-analytics'),
			'PS' => __('Palestinian Territories', 'utm-event-tracker-and-analytics'),
			'PA' => __('Panama', 'utm-event-tracker-and-analytics'),
			'PZ' => __('Panama Canal Zone', 'utm-event-tracker-and-analytics'),
			'PG' => __('Papua New Guinea', 'utm-event-tracker-and-analytics'),
			'PY' => __('Paraguay', 'utm-event-tracker-and-analytics'),
			'YD' => __('People\'s Democratic Republic of Yemen', 'utm-event-tracker-and-analytics'),
			'PE' => __('Peru', 'utm-event-tracker-and-analytics'),
			'PH' => __('Philippines', 'utm-event-tracker-and-analytics'),
			'PN' => __('Pitcairn Islands', 'utm-event-tracker-and-analytics'),
			'PL' => __('Poland', 'utm-event-tracker-and-analytics'),
			'PT' => __('Portugal', 'utm-event-tracker-and-analytics'),
			'PR' => __('Puerto Rico', 'utm-event-tracker-and-analytics'),
			'QA' => __('Qatar', 'utm-event-tracker-and-analytics'),
			'RO' => __('Romania', 'utm-event-tracker-and-analytics'),
			'RU' => __('Russia', 'utm-event-tracker-and-analytics'),
			'RW' => __('Rwanda', 'utm-event-tracker-and-analytics'),
			'BL' => __('Saint Barthélemy', 'utm-event-tracker-and-analytics'),
			'SH' => __('Saint Helena', 'utm-event-tracker-and-analytics'),
			'KN' => __('Saint Kitts and Nevis', 'utm-event-tracker-and-analytics'),
			'LC' => __('Saint Lucia', 'utm-event-tracker-and-analytics'),
			'MF' => __('Saint Martin', 'utm-event-tracker-and-analytics'),
			'PM' => __('Saint Pierre and Miquelon', 'utm-event-tracker-and-analytics'),
			'VC' => __('Saint Vincent and the Grenadines', 'utm-event-tracker-and-analytics'),
			'WS' => __('Samoa', 'utm-event-tracker-and-analytics'),
			'SM' => __('San Marino', 'utm-event-tracker-and-analytics'),
			'SA' => __('Saudi Arabia', 'utm-event-tracker-and-analytics'),
			'SN' => __('Senegal', 'utm-event-tracker-and-analytics'),
			'RS' => __('Serbia', 'utm-event-tracker-and-analytics'),
			'CS' => __('Serbia and Montenegro', 'utm-event-tracker-and-analytics'),
			'SC' => __('Seychelles', 'utm-event-tracker-and-analytics'),
			'SL' => __('Sierra Leone', 'utm-event-tracker-and-analytics'),
			'SG' => __('Singapore', 'utm-event-tracker-and-analytics'),
			'SK' => __('Slovakia', 'utm-event-tracker-and-analytics'),
			'SI' => __('Slovenia', 'utm-event-tracker-and-analytics'),
			'SB' => __('Solomon Islands', 'utm-event-tracker-and-analytics'),
			'SO' => __('Somalia', 'utm-event-tracker-and-analytics'),
			'ZA' => __('South Africa', 'utm-event-tracker-and-analytics'),
			'GS' => __('South Georgia and the South Sandwich Islands', 'utm-event-tracker-and-analytics'),
			'KR' => __('South Korea', 'utm-event-tracker-and-analytics'),
			'ES' => __('Spain', 'utm-event-tracker-and-analytics'),
			'LK' => __('Sri Lanka', 'utm-event-tracker-and-analytics'),
			'SD' => __('Sudan', 'utm-event-tracker-and-analytics'),
			'SR' => __('Suriname', 'utm-event-tracker-and-analytics'),
			'SJ' => __('Svalbard and Jan Mayen', 'utm-event-tracker-and-analytics'),
			'SZ' => __('Swaziland', 'utm-event-tracker-and-analytics'),
			'SE' => __('Sweden', 'utm-event-tracker-and-analytics'),
			'CH' => __('Switzerland', 'utm-event-tracker-and-analytics'),
			'SY' => __('Syria', 'utm-event-tracker-and-analytics'),
			'ST' => __('São Tomé and Príncipe', 'utm-event-tracker-and-analytics'),
			'TW' => __('Taiwan', 'utm-event-tracker-and-analytics'),
			'TJ' => __('Tajikistan', 'utm-event-tracker-and-analytics'),
			'TZ' => __('Tanzania', 'utm-event-tracker-and-analytics'),
			'TH' => __('Thailand', 'utm-event-tracker-and-analytics'),
			'TL' => __('Timor-Leste', 'utm-event-tracker-and-analytics'),
			'TG' => __('Togo', 'utm-event-tracker-and-analytics'),
			'TK' => __('Tokelau', 'utm-event-tracker-and-analytics'),
			'TO' => __('Tonga', 'utm-event-tracker-and-analytics'),
			'TT' => __('Trinidad and Tobago', 'utm-event-tracker-and-analytics'),
			'TN' => __('Tunisia', 'utm-event-tracker-and-analytics'),
			'TR' => __('Turkey', 'utm-event-tracker-and-analytics'),
			'TM' => __('Turkmenistan', 'utm-event-tracker-and-analytics'),
			'TC' => __('Turks and Caicos Islands', 'utm-event-tracker-and-analytics'),
			'TV' => __('Tuvalu', 'utm-event-tracker-and-analytics'),
			'UM' => __('U.S. Minor Outlying Islands', 'utm-event-tracker-and-analytics'),
			'PU' => __('U.S. Miscellaneous Pacific Islands', 'utm-event-tracker-and-analytics'),
			'VI' => __('U.S. Virgin Islands', 'utm-event-tracker-and-analytics'),
			'UG' => __('Uganda', 'utm-event-tracker-and-analytics'),
			'UA' => __('Ukraine', 'utm-event-tracker-and-analytics'),
			'SU' => __('Union of Soviet Socialist Republics', 'utm-event-tracker-and-analytics'),
			'AE' => __('United Arab Emirates', 'utm-event-tracker-and-analytics'),
			'GB' => __('United Kingdom', 'utm-event-tracker-and-analytics'),
			'US' => __('United States', 'utm-event-tracker-and-analytics'),
			'ZZ' => __('Unknown or Invalid Region', 'utm-event-tracker-and-analytics'),
			'UY' => __('Uruguay', 'utm-event-tracker-and-analytics'),
			'UZ' => __('Uzbekistan', 'utm-event-tracker-and-analytics'),
			'VU' => __('Vanuatu', 'utm-event-tracker-and-analytics'),
			'VA' => __('Vatican City', 'utm-event-tracker-and-analytics'),
			'VE' => __('Venezuela', 'utm-event-tracker-and-analytics'),
			'VN' => __('Vietnam', 'utm-event-tracker-and-analytics'),
			'WK' => __('Wake Island', 'utm-event-tracker-and-analytics'),
			'WF' => __('Wallis and Futuna', 'utm-event-tracker-and-analytics'),
			'EH' => __('Western Sahara', 'utm-event-tracker-and-analytics'),
			'YE' => __('Yemen', 'utm-event-tracker-and-analytics'),
			'ZM' => __('Zambia', 'utm-event-tracker-and-analytics'),
			'ZW' => __('Zimbabwe', 'utm-event-tracker-and-analytics'),
			'AX' => __('Åland Islands', 'utm-event-tracker-and-analytics'),
		);
	}

	/**
	 * Get country name from country code
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_country_name($country_code) {
		$countries = self::get_countries();
		return isset($countries[$country_code]) ? $countries[$country_code] : '';
	}

	/**
	 * Get Client IP address
	 * 
	 * @since 1.1.3
	 * @return string
	 */
	public static function get_client_ip() {
		$ipaddress = null;
		if (getenv('HTTP_CLIENT_IP')) {
			$ipaddress = getenv('HTTP_CLIENT_IP');
		} else if (getenv('HTTP_X_FORWARDED_FOR')) {
			$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
		} else if (getenv('HTTP_X_FORWARDED')) {
			$ipaddress = getenv('HTTP_X_FORWARDED');
		} else if (getenv('HTTP_FORWARDED_FOR')) {
			$ipaddress = getenv('HTTP_FORWARDED_FOR');
		} else if (getenv('HTTP_FORWARDED')) {
			$ipaddress = getenv('HTTP_FORWARDED');
		} else if (getenv('REMOTE_ADDR')) {
			$ipaddress = getenv('REMOTE_ADDR');
		}

		if (defined('UTM_EVENT_TRACKER_DEV_MODE')) {
			$ipaddress = sprintf('%s.%s.%s.%s', wp_rand(0, 255), wp_rand(0, 255), wp_rand(0, 255), wp_rand(0, 255));
			$ipaddress = '202.5.180.121';
		}

		return $ipaddress;
	}

	/**
	 * Get client unique key
	 * 
	 * @since 1.1.3
	 * @return string
	 */
	public static function get_client_key($key) {
		return sprintf('utm_event_tracker_%s_%s', sanitize_key($key), md5(self::get_client_ip()));
	}

	/**
	 * Get all supported parameters
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_utm_parameters() {
		return apply_filters('utm_event_tracker/utm_parameters', array(
			'utm_campaign' => __('UTM Campaign', 'utm-event-tracker-and-analytics'),
			'utm_medium' => __('UTM Medium', 'utm-event-tracker-and-analytics'),
			'utm_source'  => __('UTM Source', 'utm-event-tracker-and-analytics'),
			'utm_term' => __('UTM Terms', 'utm-event-tracker-and-analytics'),
			'utm_content' => __('UTM Content', 'utm-event-tracker-and-analytics'),
			'fbclid' => __('Facebook ads Click ID', 'utm-event-tracker-and-analytics'),
			'gclid' => __('Google ads Click ID', 'utm-event-tracker-and-analytics'),
		));
	}

	/**
	 * Get all available parameters
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_all_parameters() {
		return array_merge(self::get_utm_parameters(), array(
			'ip_address' => __('IP Address', 'utm-event-tracker-and-analytics'),
			'landing_page' => __('Landing Page', 'utm-event-tracker-and-analytics'),
			'tracking_time' => __('Tracking Time', 'utm-event-tracker-and-analytics'),
		));
	}

	/**
	 * Get date of selected timezone of settings
	 * 
	 * @since 1.0.0
	 * @param string mysql date
	 * @param boolean timestamp
	 * @param string date format
	 * @return string|integer
	 */
	public static function get_date($date, $timestamp = false, $format = 'Y-m-d H:i:s') {
		$date = wp_date($format, strtotime($date));
		if ($timestamp) {
			return strtotime($date);
		}

		return $date;
	}

	/**
	 * Add field note
	 * 
	 * @since 1.1.2
	 * @return void
	 */
	public static function get_field_note($prepend = '', $append = '', $utm_source = 'settings', $utm_medium = 'get+pro') {
		echo '<div class="field-note">';
		if (!empty($prepend)) {
			echo wp_kses_post($prepend) . ' ';
		}

		if (!self::is_pro_installed()) {
			printf(
				/* translators: %1$s for link open, %2$s for link close */
				esc_html__('Get the %1$s pro version%2$s to unlock this option.', 'utm-event-tracker-and-analytics'),
				'<a href="https://codiepress.com/plugins/utm-event-tracker-and-analytics-pro/?utm_campaign=utm+event+tracker&utm_source=' . esc_attr($utm_source) . '&utm_medium=' . esc_attr($utm_medium) . '" target="_blank">',
				'</a>',
			);
		}

		if (self::is_pro_installed() && !self::is_pro_activated()) {
			esc_html_e('Activate the "UTM Event Tracker and Analytics Pro" plugin to unlock this option.', 'utm-event-tracker-and-analytics');
		}

		if (self::is_pro_activated() && !self::license_activated()) {
			esc_html_e('Activate the license of "UTM Event Tracker and Analytics Pro" plugin to unlock this option.', 'utm-event-tracker-and-analytics');
		}

		if (!empty($prepend)) {
			echo ' ' . wp_kses_post($append);
		}

		echo '</div>';
	}

	/**
	 * Sanitize event key
	 * 
	 * @since 1.1.3
	 * @return string
	 */
	public static function sanitize_event_key($value) {
		return sanitize_key(preg_replace('/[\s-]+/', '_', trim($value)));
	}

	/**
	 * Get parameters data
	 * 
	 * @since 1.1.3
	 * @return array
	 */
	public static function get_parameters_data() {
		return Session::get_current_session()->get_utm_values();
	}

	/**
	 * JSON string to array
	 * 
	 * @since 1.1.9
	 * @return array
	 */
	public static function json_string_to_array($json_string) {
		if (!is_scalar($json_string)) {
			return (array) $json_string;
		}

		$data = json_decode($json_string, true);
		if (!is_array($data)) {
			$data = array();
		}

		return $data;
	}

}

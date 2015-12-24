<?php
/**
 * WooCommerce Jetpack Functions
 *
 * The WooCommerce Jetpack Functions.
 *
 * @version 2.3.10
 * @author  Algoritmika Ltd.
 */

if ( ! function_exists( 'wcj_get_rates_for_tax_class' ) ) {
	/* Used by admin settings page.
	 *
	 * @param string $tax_class
	 *
	 * @return array|null|object
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	function wcj_get_rates_for_tax_class( $tax_class ) {
		global $wpdb;

		// Get all the rates and locations. Snagging all at once should significantly cut down on the number of queries.
		$rates     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}woocommerce_tax_rates` WHERE `tax_rate_class` = %s ORDER BY `tax_rate_order`;", sanitize_title( $tax_class ) ) );
		$locations = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}woocommerce_tax_rate_locations`" );

		// Set the rates keys equal to their ids.
		$rates = array_combine( wp_list_pluck( $rates, 'tax_rate_id' ), $rates );

		// Drop the locations into the rates array.
		foreach ( $locations as $location ) {
			// Don't set them for unexistent rates.
			if ( ! isset( $rates[ $location->tax_rate_id ] ) ) {
				continue;
			}
			// If the rate exists, initialize the array before appending to it.
			if ( ! isset( $rates[ $location->tax_rate_id ]->{$location->location_type} ) ) {
				$rates[ $location->tax_rate_id ]->{$location->location_type} = array();
			}
			$rates[ $location->tax_rate_id ]->{$location->location_type}[] = $location->location_code;
		}

		return $rates;
	}
}

/*
 * wcj_get_select_options()
 *
 * @version  2.3.0
 * @since    2.3.0
 * @return   array
 */
if ( ! function_exists( 'wcj_get_select_options' ) ) {
	function wcj_get_select_options( $select_options_raw ) {
		$select_options_raw = explode( PHP_EOL, $select_options_raw );
		$select_options = array();
		foreach ( $select_options_raw as $select_options_title ) {
			$select_options_key = sanitize_title( $select_options_title );
			$select_options[ $select_options_key ] = $select_options_title;
		}
		return $select_options;
	}
}

/*
 * is_frontend()
 *
 * @since  2.2.6
 * @return boolean
 */
if ( ! function_exists( 'wcj_is_frontend' ) ) {
	function wcj_is_frontend() {
		return ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) ? true : false;
	}
}

/**
 * wcj_get_wcj_uploads_dir.
 */
if ( ! function_exists( 'wcj_get_wcj_uploads_dir' ) ) {
	function wcj_get_wcj_uploads_dir( $subdir = '' ) {
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];
		$upload_dir = $upload_dir . '/woocommerce_uploads/wcj_uploads';
		if ( '' != $subdir ) $upload_dir = $upload_dir . '/' . $subdir;
		return $upload_dir;
	}
}

/**
 * wcj_is_product_wholesale_enabled.
 */
if ( ! function_exists( 'wcj_is_product_wholesale_enabled' ) ) {
	function wcj_is_product_wholesale_enabled( $product_id ) {
		$products_to_include = get_option( 'wcj_wholesale_price_products_to_include', array() );
		if ( empty ( $products_to_include ) ) return true;
		foreach ( $products_to_include as $id ) {
			if ( $product_id == $id ) return true;
		}
		return false;
	}
 }

/**
 * wcj_get_products.
 */
add_action( 'init', 'add_wcj_get_products_filter' );
if ( ! function_exists( 'add_wcj_get_products_filter' ) ) {
	function add_wcj_get_products_filter() {
		add_filter( 'wcj_get_products_filter', 'wcj_get_products' );
	}
}
if ( ! function_exists( 'wcj_get_products' ) ) {
	function wcj_get_products( $products ) {
		//if (is_admin()) require_once(ABSPATH . 'wp-includes/pluggable.php');
		//$products = array();
		$args = array(
			'post_type'			=> 'product',
			'post_status' 		=> 'any',
			'posts_per_page' 	=> -1,
		);
		$loop = new WP_Query( $args );
		while ( $loop->have_posts() ) : $loop->the_post();
			$products[ strval( $loop->post->ID ) ] = get_the_title( $loop->post->ID );
		endwhile;
		//TODO:
		// reset_postdata? reset_query?
		//print_r( $products );
		return $products;
	}
}

/*
 * wcj_get_product.
 */
if ( ! function_exists( 'wcj_get_product' ) ) {
	function wcj_get_product( $product_id = 0 ) {
		if ( 0 == $product_id ) $product_id = get_the_ID();
		$the_product = new WCJ_Product( $product_id );
		return $the_product;
	}
}

/**
 * wc_get_product_purchase_price.
 */
if ( ! function_exists( 'wc_get_product_purchase_price' ) ) {
	function wc_get_product_purchase_price( $product_id = 0 ) {
		$the_product = wcj_get_product( $product_id );
		return $the_product->get_purchase_price();
	}
}

/**
 * is_shop_manager.
 *
 * @return bool
 */
if ( ! function_exists( 'is_shop_manager' ) ) {
	function is_shop_manager( $user_id = 0 ) {
		$the_user = ( 0 == $user_id ) ? wp_get_current_user() : get_user_by( 'id', $user_id );
		//if ( isset( $the_user['roles'][0] ) && 'shop_manager' === $the_user['roles'][0] ) {
		return ( isset( $the_user->roles[0] ) && 'shop_manager' === $the_user->roles[0] ) ? true : false;
	}
}

/**
 * validate_VAT.
 *
 * @return mixed: bool on successful checking (can be true or false), null otherwise
 */
if ( ! function_exists( 'validate_VAT' ) ) {
	function validate_VAT( $country_code, $vat_number ) {
		try {
			$client = new SoapClient(
				'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl',
				array( 'exceptions' => true )
			);

			$result = $client->checkVat( array(
				'countryCode' => $country_code,
				'vatNumber'   => $vat_number,
			) );

			return ( isset( $result->valid ) ) ? $result->valid : null;

		} catch( Exception $exception ) {
			return null;
		}
	}
}


/**
 * convert_number_to_words.
 *
 * @return string
 */

if ( ! function_exists( 'convert_number_to_words' ) ) {
	function convert_number_to_words( $number ) {
		$hyphen      = '-';
		$conjunction = ' and ';
		$separator   = ', ';
		$negative    = 'negative ';
		$decimal     = ' point ';
		$dictionary  = array(
			0                   => 'zero',
			1                   => 'one',
			2                   => 'two',
			3                   => 'three',
			4                   => 'four',
			5                   => 'five',
			6                   => 'six',
			7                   => 'seven',
			8                   => 'eight',
			9                   => 'nine',
			10                  => 'ten',
			11                  => 'eleven',
			12                  => 'twelve',
			13                  => 'thirteen',
			14                  => 'fourteen',
			15                  => 'fifteen',
			16                  => 'sixteen',
			17                  => 'seventeen',
			18                  => 'eighteen',
			19                  => 'nineteen',
			20                  => 'twenty',
			30                  => 'thirty',
			40                  => 'fourty',
			50                  => 'fifty',
			60                  => 'sixty',
			70                  => 'seventy',
			80                  => 'eighty',
			90                  => 'ninety',
			100                 => 'hundred',
			1000                => 'thousand',
			1000000             => 'million',
			1000000000          => 'billion',
			1000000000000       => 'trillion',
			1000000000000000    => 'quadrillion',
			1000000000000000000 => 'quintillion'
		);

		if (!is_numeric($number)) {
			return false;
		}

		if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
			// overflow
			trigger_error(
				'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
				E_USER_WARNING
			);
			return false;
		}

		if ($number < 0) {
			return $negative . convert_number_to_words(abs($number));
		}

		$string = $fraction = null;

		if (strpos($number, '.') !== false) {
			list($number, $fraction) = explode('.', $number);
		}

		switch (true) {
			case $number < 21:
				$string = $dictionary[$number];
				break;
			case $number < 100:
				$tens   = ((int) ($number / 10)) * 10;
				$units  = $number % 10;
				$string = $dictionary[$tens];
				if ($units) {
					$string .= $hyphen . $dictionary[$units];
				}
				break;
			case $number < 1000:
				$hundreds  = $number / 100;
				$remainder = $number % 100;
				$string = $dictionary[$hundreds] . ' ' . $dictionary[100];
				if ($remainder) {
					$string .= $conjunction . convert_number_to_words($remainder);
				}
				break;
			default:
				$baseUnit = pow(1000, floor(log($number, 1000)));
				$numBaseUnits = (int) ($number / $baseUnit);
				$remainder = $number % $baseUnit;
				$string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
				if ($remainder) {
					$string .= $remainder < 100 ? $conjunction : $separator;
					$string .= convert_number_to_words($remainder);
				}
				break;
		}

		if (null !== $fraction && is_numeric($fraction)) {
			$string .= $decimal;
			$words = array();
			foreach (str_split((string) $fraction) as $number) {
				$words[] = $dictionary[$number];
			}
			$string .= implode(' ', $words);
		}

		return $string;
	}
}

/**
 * wcj_plugin_url.
 *
 * @version 2.3.0
 */
if ( ! function_exists( 'wcj_plugin_url' ) ) {
	function wcj_plugin_url() {
		return untrailingslashit( plugin_dir_url( realpath( dirname( __FILE__ ) . '/..' ) ) );
		//return untrailingslashit( realpath( dirname( __FILE__ ) . '/..' ) );
	}
}

/**
 * Get the plugin path.
 *
 * @return string
 */
if ( ! function_exists( 'wcj_plugin_path' ) ) {
	function wcj_plugin_path() {
		//return untrailingslashit( plugin_dir_path( __FILE__ ) );
		return untrailingslashit( realpath( plugin_dir_path( __FILE__ ) . '/../..' ) );
	}
}

/**
 * Convert the php date format string to a js date format.
 * https://gist.github.com/clubduece/4053820
 */
if ( ! function_exists( 'wcj_date_format_php_to_js' ) ) {
	function wcj_date_format_php_to_js( $php_date_format ) {
		$date_formats_php_to_js = array(
			'F j, Y' => 'MM dd, yy',
			'Y/m/d'  => 'yy/mm/dd',
			'm/d/Y'  => 'mm/dd/yy',
			'd/m/Y'  => 'dd/mm/yy',
		);
		return isset( $date_formats_php_to_js[ $php_date_format ] ) ? $date_formats_php_to_js[ $php_date_format ] : 'MM dd, yy';
	}
}

/**
 * wcj_hex2rgb.
 */
if ( ! function_exists( 'wcj_hex2rgb' ) ) {
	function wcj_hex2rgb( $hex ) {
		return sscanf( $hex, "#%2x%2x%2x" );
	}
}

/**
 * wcj_get_the_ip.
 * http://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php
 */
if ( ! function_exists( 'wcj_get_the_ip' ) ) {
	function wcj_get_the_ip( ) {
		$ip = null;
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
}

/**
 * wcj_get_shortcodes_atts_list.
 *
if ( ! function_exists( 'wcj_get_shortcodes_atts_list' ) ) {
	function wcj_get_shortcodes_atts_list() {
		return apply_filters( 'wcj_shortcodes_atts', array(
			'before'        => '',
			'after'         => '',
			'visibility'    => '',
		) );
	}
}

/**
 * wcj_get_shortcodes_list.
 */
if ( ! function_exists( 'wcj_get_shortcodes_list' ) ) {
	function wcj_get_shortcodes_list() {
		$the_array = apply_filters( 'wcj_shortcodes_list', array() );
		return implode( ', ', $the_array )/*  . ' (' . count( $the_array ) . ')' */;
	}
}

/**
 * wcj_get_order_statuses.
 */
if ( ! function_exists( 'wcj_get_order_statuses' ) ) {
	function wcj_get_order_statuses( $cut_the_prefix ) {
		$order_statuses = array(
			'wc-pending'    => _x( 'Pending Payment', 'Order status', 'woocommerce' ),
			'wc-processing' => _x( 'Processing', 'Order status', 'woocommerce' ),
			'wc-on-hold'    => _x( 'On Hold', 'Order status', 'woocommerce' ),
			'wc-completed'  => _x( 'Completed', 'Order status', 'woocommerce' ),
			'wc-cancelled'  => _x( 'Cancelled', 'Order status', 'woocommerce' ),
			'wc-refunded'   => _x( 'Refunded', 'Order status', 'woocommerce' ),
			'wc-failed'     => _x( 'Failed', 'Order status', 'woocommerce' ),
		);
		$order_statuses = apply_filters( 'wc_order_statuses', $order_statuses );
		if ( $cut_the_prefix ) {
			$order_statuses_no_prefix = array();
			foreach ( $order_statuses as $status => $desc ) {
				$order_statuses_no_prefix[ substr( $status, 3 ) ] = $desc;
			}
			return $order_statuses_no_prefix;
		}
		return $order_statuses;
	}
}

/**
 * wcj_get_currencies_names_and_symbols.
 */
if ( ! function_exists( 'wcj_get_currencies_names_and_symbols' ) ) {
	function wcj_get_currencies_names_and_symbols() {
		$currencies = include( wcj_plugin_path() . '/includes/currencies/wcj-currencies.php' );
		foreach( $currencies as $data ) {
			$currency_names_and_symbols[ $data['code'] ] = $data['name'] . ' (' . $data['symbol'] . ')';
		}
		return $currency_names_and_symbols;
	}
}

/**
 * wcj_get_currency_symbol.
 *
 * @version 2.2.7
 */
if ( ! function_exists( 'wcj_get_currency_symbol' ) ) {
	function wcj_get_currency_symbol( $currency_code ) {
		$return = '';
		$currencies = include( wcj_plugin_path() . '/includes/currencies/wcj-currencies.php' );
		foreach( $currencies as $data ) {
			if ( $currency_code == $data['code'] ) {
				$return = $data['symbol'];
				break;
			}
		}
		$return = apply_filters( 'wcj_get_option_filter', $return, get_option( 'wcj_currency_' . $currency_code, $return ) );
		return ( '' != $return ) ? $return : false;
	}
}

/**
 * wcj_price.
 */
if ( ! function_exists( 'wcj_price' ) ) {
	function wcj_price( $price, $currency, $hide_currency ) {
		return ( 'yes' === $hide_currency ) ? wc_price( $price, array( 'currency' => 'DISABLED' ) ) : wc_price( $price, array( 'currency' => $currency ) );
	}
}

<?php
/**
 * WooCommerce Jetpack Functions
 *
 * The WooCommerce Jetpack Functions.
 *
 * @version 2.4.4
 * @author  Algoritmika Ltd.
 */

/*
 * wcj_get_product_input_fields.
 *
 * @version 2.4.4
 * @since   2.4.4
 * @return  string
 */
if ( ! function_exists( 'wcj_get_product_input_fields' ) ) {
	function wcj_get_product_input_fields( $item ) {
		$product_input_fields = array();
		foreach ( $item as $key => $value ) {
			if ( false !== strpos( $key, 'wcj_product_input_fields_' ) ) {
				$product_input_fields[] = /* $key . ': ' . */ $value;
			}
		}
		return ( ! empty( $product_input_fields ) ) ? /* ' (' . */ implode( ', ', $product_input_fields ) /* . ')' */ : '';
	}
}

/*
 * wcj_get_left_to_free_shipping.
 *
 * @version 2.4.4
 * @since   2.4.4
 * @return  string
 */
if ( ! function_exists( 'wcj_get_left_to_free_shipping' ) ) {
	function wcj_get_left_to_free_shipping( $content ) {
		if ( '' == $content ) {
			$content = __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' );
		}
		$free_shipping = new WC_Shipping_Free_Shipping();
		if ( in_array( $free_shipping->requires, array( 'min_amount', 'either', 'both' ) ) && isset( WC()->cart->cart_contents_total ) ) {
			if ( WC()->cart->prices_include_tax ) {
				$total = WC()->cart->cart_contents_total + array_sum( WC()->cart->taxes );
			} else {
				$total = WC()->cart->cart_contents_total;
			}
			if ( $total >= $free_shipping->min_amount ) {
				return '';
			} else {
				$content = str_replace( '%left_to_free%',             wc_price( $free_shipping->min_amount - $total ), $content );
				$content = str_replace( '%free_shipping_min_amount%', wc_price( $free_shipping->min_amount ),          $content );
				return $content;
			}
		}
	}
}

/*
 * wcj_get_cart_filters()
 *
 * @version 2.4.4
 * @since   2.4.4
 * @return  array
 */
if ( ! function_exists( 'wcj_get_cart_filters' ) ) {
	function wcj_get_cart_filters() {
		return array(
			'woocommerce_before_cart'                    => __( 'Before cart', 'woocommerce-jetpack' ),
			'woocommerce_before_cart_table'              => __( 'Before cart table', 'woocommerce-jetpack' ),
			'woocommerce_before_cart_contents'           => __( 'Before cart contents', 'woocommerce-jetpack' ),
			'woocommerce_cart_contents'                  => __( 'Cart contents', 'woocommerce-jetpack' ),
			'woocommerce_cart_coupon'                    => __( 'Cart coupon', 'woocommerce-jetpack' ),
			'woocommerce_cart_actions'                   => __( 'Cart actions', 'woocommerce-jetpack' ),
			'woocommerce_after_cart_contents'            => __( 'After cart contents', 'woocommerce-jetpack' ),
			'woocommerce_after_cart_table'               => __( 'After cart table', 'woocommerce-jetpack' ),
			'woocommerce_cart_collaterals'               => __( 'Cart collaterals', 'woocommerce-jetpack' ),
			'woocommerce_after_cart'                     => __( 'After cart', 'woocommerce-jetpack' ),

			'woocommerce_before_cart_totals'             => __( 'Before cart totals', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_before_shipping'    => __( 'Cart totals: Before shipping', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_after_shipping'     => __( 'Cart totals: After shipping', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_before_order_total' => __( 'Cart totals: Before order total', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_after_order_total'  => __( 'Cart totals: After order total', 'woocommerce-jetpack' ),
			'woocommerce_proceed_to_checkout'            => __( 'Proceed to checkout', 'woocommerce-jetpack' ),
			'woocommerce_after_cart_totals'              => __( 'After cart totals', 'woocommerce-jetpack' ),

			'woocommerce_before_shipping_calculator'     => __( 'Before shipping calculator', 'woocommerce-jetpack' ),
			'woocommerce_after_shipping_calculator'      => __( 'After shipping calculator', 'woocommerce-jetpack' ),

			'woocommerce_cart_is_empty'                  => __( 'If cart is empty', 'woocommerce-jetpack' ),
		);
	}
}

/*
 * wcj_is_module_enabled()
 *
 * @version 2.4.0
 * @since   2.4.0
 * @return  boolean
 */
if ( ! function_exists( 'wcj_is_module_enabled' ) ) {
	function wcj_is_module_enabled( $module_id ) {
		return ( 'yes' === get_option( 'wcj_' . $module_id . '_enabled', 'no' ) ) ? true : false;
	}
}

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
 * add_wcj_get_products_filter.
 */
add_action( 'init', 'add_wcj_get_products_filter' );
if ( ! function_exists( 'add_wcj_get_products_filter' ) ) {
	function add_wcj_get_products_filter() {
		add_filter( 'wcj_get_products_filter', 'wcj_get_products' );
	}
}

/**
 * wcj_get_products.
 *
 * @version 2.4.4
 */
if ( ! function_exists( 'wcj_get_products' ) ) {
	function wcj_get_products( $products = array() ) {
		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'title',
				'order'          => 'ASC',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			while ( $loop->have_posts() ) : $loop->the_post();
				$products[ strval( $loop->post->ID ) ] = get_the_title( $loop->post->ID );
			endwhile;
			$offset += $block_size;
		}
		wp_reset_postdata();
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
/* if ( ! function_exists( 'wcj_date_format_php_to_js' ) ) {
	function wcj_date_format_php_to_js( $php_date_format ) {
		$date_formats_php_to_js = array(
			'F j, Y' => 'MM dd, yy',
			'Y/m/d'  => 'yy/mm/dd',
			'm/d/Y'  => 'mm/dd/yy',
			'd/m/Y'  => 'dd/mm/yy',
		);
		return isset( $date_formats_php_to_js[ $php_date_format ] ) ? $date_formats_php_to_js[ $php_date_format ] : 'MM dd, yy';
	}
} */

/*
 * Matches each symbol of PHP date format standard
 * with jQuery equivalent codeword
 * http://stackoverflow.com/questions/16702398/convert-a-php-date-format-to-a-jqueryui-datepicker-date-format
 *
 * @author  Tristan Jahier
 * @version 2.4.0
 * @since   2.4.0
 */
if ( ! function_exists( 'wcj_date_format_php_to_js_v2' ) ) {
	function wcj_date_format_php_to_js_v2( $php_format ) {
		$SYMBOLS_MATCHING = array(
			// Day
			'd' => 'dd',
			'D' => 'D',
			'j' => 'd',
			'l' => 'DD',
			'N' => '',
			'S' => '',
			'w' => '',
			'z' => 'o',
			// Week
			'W' => '',
			// Month
			'F' => 'MM',
			'm' => 'mm',
			'M' => 'M',
			'n' => 'm',
			't' => '',
			// Year
			'L' => '',
			'o' => '',
			'Y' => 'yy',
			'y' => 'y',
			// Time
			'a' => '',
			'A' => '',
			'B' => '',
			'g' => '',
			'G' => '',
			'h' => '',
			'H' => '',
			'i' => '',
			's' => '',
			'u' => ''
		);
		$jqueryui_format = "";
		$escaping = false;
		for ( $i = 0; $i < strlen( $php_format ); $i++ ) {
			$char = $php_format[ $i ];
			if ( $char === '\\' ) { // PHP date format escaping character
				$i++;
				$jqueryui_format .= ( $escaping ) ? $php_format[ $i ] : '\'' . $php_format[ $i ];
				$escaping = true;
			} else {
				if ( $escaping ) {
					$jqueryui_format .= "'";
					$escaping = false;
				}
				$jqueryui_format .= ( isset( $SYMBOLS_MATCHING[ $char ] ) ) ? $SYMBOLS_MATCHING[ $char ] : $char;
			}
		}
		return $jqueryui_format;
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
 *
 * @version 2.4.4
 */
if ( ! function_exists( 'wcj_get_currencies_names_and_symbols' ) ) {
	function wcj_get_currencies_names_and_symbols( $result = 'names_and_symbols', $scope = 'all' ) {
		$currency_names_and_symbols = array();
		/* if ( ! wcj_is_module_enabled( 'currency' ) ) {
			return $currency_names_and_symbols;
		} */
		if ( 'all' === $scope || 'no_custom' === $scope ) {
//			$currencies = include( wcj_plugin_path() . '/includes/currencies/wcj-currencies.php' );
//			include( wcj_plugin_path() . '/includes/currencies/wcj-currencies.php' );
			$currencies = wcj_get_currencies_array();
			foreach( $currencies as $data ) {
				switch ( $result ) {
					case 'names_and_symbols':
						$currency_names_and_symbols[ $data['code'] ] = $data['name'] . ' (' . $data['symbol'] . ')';
						break;
					case 'names':
						$currency_names_and_symbols[ $data['code'] ] = $data['name'];
						break;
					case 'symbols':
						$currency_names_and_symbols[ $data['code'] ] = $data['symbol'];
						break;
				}
			}
		}
		if ( wcj_is_module_enabled( 'currency' ) && ( 'all' === $scope || 'custom_only' === $scope ) ) {
			// Custom currencies
			$custom_currency_total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_currency_custom_currency_total_number', 1 ) );
			for ( $i = 1; $i <= $custom_currency_total_number; $i++) {
				$custom_currency_code   = get_option( 'wcj_currency_custom_currency_code_'   . $i );
				$custom_currency_name   = get_option( 'wcj_currency_custom_currency_name_'   . $i );
				$custom_currency_symbol = get_option( 'wcj_currency_custom_currency_symbol_' . $i );
				if ( '' != $custom_currency_code && '' != $custom_currency_name /* && '' != $custom_currency_symbol */ ) {
					switch ( $result ) {
						case 'names_and_symbols':
							$currency_names_and_symbols[ $custom_currency_code ] = $custom_currency_name . ' (' . $custom_currency_symbol . ')';
							break;
						case 'names':
							$currency_names_and_symbols[ $custom_currency_code ] = $custom_currency_name;
							break;
						case 'symbols':
							$currency_names_and_symbols[ $custom_currency_code ] = $custom_currency_symbol;
							break;
					}
				}
			}
		}
		return $currency_names_and_symbols;
	}
}

/**
 * wcj_get_currency_symbol.
 *
 * @version 2.4.4
 */
if ( ! function_exists( 'wcj_get_currency_symbol' ) ) {
	function wcj_get_currency_symbol( $currency_code ) {
		$return = '';
		$currencies = wcj_get_currencies_names_and_symbols( 'symbols', 'no_custom' );
		if ( isset( $currencies[ $currency_code ] ) ) {
			if ( wcj_is_module_enabled( 'currency' ) ) {
				$return = apply_filters( 'wcj_get_option_filter', $currencies[ $currency_code ], get_option( 'wcj_currency_' . $currency_code, $currencies[ $currency_code ] ) );
			} else {
				$return = $currencies[ $currency_code ];
			}
		} else {
			$currencies = wcj_get_currencies_names_and_symbols( 'symbols', 'custom_only' );
			$return = isset( $currencies[ $currency_code ] ) ? $currencies[ $currency_code ] : '';
		}
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

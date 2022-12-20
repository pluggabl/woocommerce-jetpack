<?php
/**
 * Booster for WooCommerce - Module - Bookings
 *
 * @version 6.0.1
 * @since   2.5.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Product_Bookings' ) ) :
	/**
	 * WCJ_Product_Bookings.
	 */
	class WCJ_Product_Bookings extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 3.4.0
		 * @since   2.5.0
		 */
		public function __construct() {

			$this->id         = 'product_bookings';
			$this->short_desc = __( 'Bookings', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add bookings products to WooCommerce.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-bookings';
			$this->extra_desc = __( 'When enabled, module will add new "Booster: Bookings" meta box to each product\'s edit page.', 'woocommerce-jetpack' );
			parent::__construct();

			if ( $this->is_enabled() ) {

				add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

				if ( wcj_is_frontend() ) {
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
					add_action( 'wp_ajax_price_change', array( $this, 'price_change_ajax' ) );
					add_action( 'wp_ajax_nopriv_price_change', array( $this, 'price_change_ajax' ) );
					// Prices.
					add_filter( WCJ_PRODUCT_GET_PRICE_FILTER, array( $this, 'change_price' ), PHP_INT_MAX - 100, 2 );
					add_filter( 'woocommerce_product_variation_get_price', array( $this, 'change_price' ), PHP_INT_MAX - 100, 2 );
					// Single Page.
					add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_input_fields_to_frontend' ), PHP_INT_MAX );
					// Add to cart.
					add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_bookings_on_add_to_cart' ), PHP_INT_MAX, 2 );
					add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_bookings_price_to_cart_item_data' ), PHP_INT_MAX, 3 );
					add_filter( 'woocommerce_add_cart_item', array( $this, 'add_bookings_price_to_cart_item' ), PHP_INT_MAX, 2 );
					add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_bookings_price_from_session' ), PHP_INT_MAX, 3 );
					// Price html.
					add_filter( 'woocommerce_get_price_html', array( $this, 'add_per_day_label' ), PHP_INT_MAX, 2 );
					add_filter( 'woocommerce_get_variation_price_html', array( $this, 'add_per_day_label' ), PHP_INT_MAX, 2 );
					// Add to Cart button on archives.
					add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'add_to_cart_url' ), PHP_INT_MAX, 2 );
					add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'add_to_cart_text' ), PHP_INT_MAX, 2 );
					// Show details at cart, order details, emails.
					add_filter( 'woocommerce_cart_item_name', array( $this, 'add_info_to_cart_item_name' ), PHP_INT_MAX, 3 );
					add_filter( 'woocommerce_order_item_name', array( $this, 'add_info_to_order_item_name' ), PHP_INT_MAX, 2 );
					if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
						add_action( 'woocommerce_add_order_item_meta', array( $this, 'add_info_to_order_item_meta' ), PHP_INT_MAX, 3 );
					} else {
						add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_info_to_order_item_meta_wc3' ), PHP_INT_MAX, 4 );
					}
					// Hide quantity.
					if ( 'yes' === wcj_get_option( 'wcj_product_bookings_hide_quantity', 'yes' ) ) {
						add_filter( 'woocommerce_is_sold_individually', array( $this, 'sold_individually' ), PHP_INT_MAX, 2 );
					}
					// Disable AJAX add to cart.
					add_filter( 'woocommerce_product_supports', array( $this, 'disable_add_to_cart_ajax' ), PHP_INT_MAX, 3 );
				}

				add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_value' ), PHP_INT_MAX, 3 );
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			}
		}

		/**
		 * Disable_add_to_cart_ajax.
		 *
		 * @version 2.5.0
		 * @since   2.5.0
		 * @param string | bool  $supports defines the supports.
		 * @param string         $feature defines the feature.
		 * @param string | array $_product defines the _product.
		 */
		public function disable_add_to_cart_ajax( $supports, $feature, $_product ) {
			if ( $this->is_bookings_product( $_product ) && 'ajax_add_to_cart' === $feature ) {
				$supports = false;
			}
			return $supports;
		}

		/**
		 * Sold_individually.
		 *
		 * @version 2.5.0
		 * @since   2.5.0
		 * @param string | array $return defines the return.
		 * @param string | array $_product defines the _product.
		 */
		public function sold_individually( $return, $_product ) {
			return ( $this->is_bookings_product( $_product ) ) ? true : $return;
		}

		/**
		 * Price_change_ajax.
		 *
		 * @version 5.6.8
		 * @since   2.5.0
		 * @param string | array $param defines the param.
		 */
		public function price_change_ajax( $param ) {
			$wpnonce = isset( $_REQUEST['_wpnonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'wcj_product_bookings' ) : false;
			if ( $wpnonce && isset( $_POST['date_from'] ) && '' !== $_POST['date_from'] && isset( $_POST['date_to'] ) && '' !== $_POST['date_to'] && isset( $_POST['product_id'] ) && '' !== $_POST['product_id'] ) {
				$date_to       = strtotime( sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) );
				$date_from     = strtotime( sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) );
				$seconds_diff  = $date_to - $date_from;
				$days_diff     = ( $seconds_diff / 60 / 60 / 24 );
				$the_product   = wc_get_product( sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) );
				$price_per_day = wcj_get_product_display_price( $the_product );
				$the_price     = $days_diff * $price_per_day;
				echo wp_kses_post( wc_price( $the_price ) );
			} else {
				echo esc_html__( 'Sorry, but something went wrong...', 'woocommerce-jetpack' );
			}
			die();
		}

		/**
		 * Enqueue_scripts.
		 *
		 * @version 4.3.0
		 * @since   2.5.0
		 * @todo    add "calculating price" progress message
		 */
		public function enqueue_scripts() {
			$the_product = wc_get_product();
			if (
			! is_product() ||

			! $this->is_bookings_product( $the_product ) ||
			( 'no' === wcj_get_option( 'wcj_product_bookings_price_per_day_variable_products', 'yes' ) && ( $the_product->is_type( 'variable' ) || $the_product->is_type( 'variation' ) ) )
			) {
				return;
			}

			wp_enqueue_script( 'wcj-bookings', wcj_plugin_url() . '/includes/js/wcj-bookings.js', array(), w_c_j()->version, true );
			wp_localize_script(
				'wcj-bookings',
				'ajax_object',
				array(
					'ajax_url'            => admin_url( 'admin-ajax.php' ),
					'product_id'          => get_the_ID(),
					'wrong_dates_message' => wcj_get_option( 'wcj_product_bookings_message_date_to_before_date_from', __( '"Date to" must be after "Date from"', 'woocommerce-jetpack' ) ),
				)
			);
		}

		/**
		 * Add_to_cart_text.
		 *
		 * @version 2.5.0
		 * @since   2.5.0
		 * @param string | array $text defines the text.
		 * @param string | array $_product defines the _product.
		 */
		public function add_to_cart_text( $text, $_product ) {
			return ( $this->is_bookings_product( $_product ) ) ? __( 'Read more', 'woocommerce' ) : $text;
		}

		/**
		 * Add_to_cart_url.
		 *
		 * @version 2.7.0
		 * @since   2.5.0
		 * @param string         $url defines the url.
		 * @param string | array $_product defines the _product.
		 */
		public function add_to_cart_url( $url, $_product ) {
			return ( $this->is_bookings_product( $_product ) ) ? get_permalink( wcj_get_product_id_or_variation_parent_id( $_product ) ) : $url;
		}

		/**
		 * Add_info_to_order_item_meta_wc3.
		 *
		 * @version 3.4.0
		 * @since   3.4.0
		 * @param  array  $item defines the item.
		 * @param string $cart_item_key defines the cart_item_key.
		 * @param array  $values defines the values.
		 * @param array  $order defines the order.
		 */
		public function add_info_to_order_item_meta_wc3( $item, $cart_item_key, $values, $order ) {
			$meta_keys = array( 'wcj_bookings_price', 'wcj_bookings_date_from', 'wcj_bookings_date_to' );
			foreach ( $meta_keys as $meta_key ) {
				if ( isset( $values[ $meta_key ] ) ) {
					$item[ '_' . $meta_key ] = $values[ $meta_key ];
				}
			}
		}

		/**
		 * Add_info_to_order_item_meta.
		 *
		 * @version 2.5.0
		 * @since   2.5.0
		 * @param int    $item_id defines the item_id.
		 * @param array  $values defines the values.
		 * @param string $cart_item_key defines the cart_item_key.
		 */
		public function add_info_to_order_item_meta( $item_id, $values, $cart_item_key ) {
			if ( isset( $values['wcj_bookings_price'] ) ) {
				wc_add_order_item_meta( $item_id, '_wcj_bookings_price', $values['wcj_bookings_price'] );
				wc_add_order_item_meta( $item_id, '_wcj_bookings_date_from', $values['wcj_bookings_date_from'] );
				wc_add_order_item_meta( $item_id, '_wcj_bookings_date_to', $values['wcj_bookings_date_to'] );
			}
		}

		/**
		 * Adds info to order details (and emails).
		 *
		 * @version 2.5.2
		 * @since   2.5.0
		 * @param string        $name defines the name.
		 * @param array         $item defines the item.
		 * @param string | bool $is_cart defines the is_cart.
		 */
		public function add_info_to_order_item_name( $name, $item, $is_cart = false ) {
			if ( $is_cart ) {
				$name .= '<dl class="variation">';
			}
			if ( isset( $item['wcj_bookings_price'] ) ) {
				if ( $is_cart ) {
					$name .= '<dt>' . wcj_get_option( 'wcj_product_bookings_label_period', __( 'Period', 'woocommerce-jetpack' ) ) . ': </dt>';
					$name .= '<dd>' . $item['wcj_bookings_date_from'] . ' - ' . $item['wcj_bookings_date_to'] . '</dd>';
				} else {
					$name .= ' | ' . $item['wcj_bookings_date_from'] . ' - ' . $item['wcj_bookings_date_to'];
				}
			}
			if ( $is_cart ) {
				$name .= '</dl>';
			}
			return $name;
		}

		/**
		 * Adds info to cart item details.
		 *
		 * @version 2.5.0
		 * @since   2.5.0
		 * @param string         $name defines the name.
		 * @param string | array $cart_item defines the cart_item.
		 * @param string         $cart_item_key defines the cart_item_key.
		 */
		public function add_info_to_cart_item_name( $name, $cart_item, $cart_item_key ) {
			return $this->add_info_to_order_item_name( $name, $cart_item, true );
		}

		/**
		 * Validate_bookings_on_add_to_cart.
		 *
		 * @version 5.6.8
		 * @since   2.5.0
		 * @param string | array $passed defines the passed.
		 * @param int            $product_id defines the product_id.
		 */
		public function validate_bookings_on_add_to_cart( $passed, $product_id ) {
			$the_product = wc_get_product( $product_id );
			if ( $this->is_bookings_product( $the_product ) ) {
				$wpnonce = isset( $_REQUEST['wcj_product_bookings-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_product_bookings-nonce'] ), 'wcj_product_bookings' ) : false;
				if ( $wpnonce && ( ! isset( $_POST['wcj_product_bookings_date_from'] ) || '' === $_POST['wcj_product_bookings_date_from'] ) ) {
					wc_add_notice( wcj_get_option( 'wcj_product_bookings_message_no_date_from', __( '"Date from" must be set', 'woocommerce-jetpack' ) ), 'error' );
					return false;
				}
				if ( $wpnonce && ( ! isset( $_POST['wcj_product_bookings_date_to'] ) || '' === $_POST['wcj_product_bookings_date_to'] ) ) {
					wc_add_notice( wcj_get_option( 'wcj_product_bookings_message_no_date_to', __( '"Date to" must be set', 'woocommerce-jetpack' ) ), 'error' );
					return false;
				}
				$date_to   = strtotime( sanitize_text_field( wp_unslash( $_POST['wcj_product_bookings_date_to'] ) ) );
				$date_from = strtotime( sanitize_text_field( wp_unslash( $_POST['wcj_product_bookings_date_from'] ) ) );
				if ( $date_from >= $date_to ) {
					wc_add_notice( wcj_get_option( 'wcj_product_bookings_message_date_to_before_date_from', __( '"Date to" must be after "Date from"', 'woocommerce-jetpack' ) ), 'error' );
					return false;
				}
			}
			return $passed;
		}

		/**
		 * Get_cart_item_bookings_price_from_session.
		 *
		 * @version 2.5.0
		 * @since   2.5.0
		 * @param string | array $item defines the item.
		 * @param array          $values defines the values.
		 * @param string         $key defines the key.
		 */
		public function get_cart_item_bookings_price_from_session( $item, $values, $key ) {
			if ( array_key_exists( 'wcj_bookings_price', $values ) ) {
				$item['data']->wcj_bookings_price = $values['wcj_bookings_price'];
			}
			return $item;
		}

		/**
		 * Add_bookings_price_to_cart_item_data.
		 *
		 * @version 5.6.2
		 * @since   2.5.0
		 * @param array $cart_item_data defines the cart_item_data.
		 * @param int   $product_id defines the product_id.
		 * @param int   $variation_id defines the variation_id.
		 */
		public function add_bookings_price_to_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
			$wpnonce = isset( $_REQUEST['wcj_product_bookings-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_product_bookings-nonce'] ), 'wcj_product_bookings' ) : false;
			if ( $wpnonce && isset( $_POST['wcj_product_bookings_date_to'] ) && isset( $_POST['wcj_product_bookings_date_from'] ) ) {
				$the_product = wc_get_product( $product_id );
				if ( $this->is_bookings_product( $the_product ) ) {
					$date_to      = strtotime( sanitize_text_field( wp_unslash( $_POST['wcj_product_bookings_date_to'] ) ) );
					$date_from    = strtotime( sanitize_text_field( wp_unslash( $_POST['wcj_product_bookings_date_from'] ) ) );
					$seconds_diff = $date_to - $date_from;
					$days_diff    = ( $seconds_diff / 60 / 60 / 24 );
					if ( 0 > $variation_id ) {
						$the_product = wc_get_product( $variation_id );
					}
					$price_per_day                            = $the_product->get_price();
					$the_price                                = $days_diff * $price_per_day;
					$cart_item_data['wcj_bookings_price']     = $the_price;
					$cart_item_data['wcj_bookings_date_from'] = sanitize_text_field( wp_unslash( $_POST['wcj_product_bookings_date_from'] ) );
					$cart_item_data['wcj_bookings_date_to']   = sanitize_text_field( wp_unslash( $_POST['wcj_product_bookings_date_to'] ) );

				}
			}
			return $cart_item_data;
		}

		/**
		 * Add_bookings_price_to_cart_item.
		 *
		 * @version 2.5.0
		 * @since   2.5.0
		 * @param array  $cart_item_data defines the cart_item_data.
		 * @param string $cart_item_key defines the cart_item_key.
		 */
		public function add_bookings_price_to_cart_item( $cart_item_data, $cart_item_key ) {
			if ( isset( $cart_item_data['wcj_bookings_price'] ) ) {
				$cart_item_data['data']->wcj_bookings_price = $cart_item_data['wcj_bookings_price'];
			}
			return $cart_item_data;
		}

		/**
		 * Create custom style for bookings product page
		 *
		 * @version 4.6.0
		 * @since   2.5.0
		 */
		public function create_custom_style() {
			?>
		<style>
			.wcj-loader {
				display: none;
				border: 4px solid #f3f3f3;
				border-radius: 50%;
				border-top: 4px solid #999999;
				width: 25px;
				height: 25px;
				-webkit-animation: spin 1s linear infinite;
				animation: spin 1s linear infinite;
			}

			/* Safari */
			@-webkit-keyframes spin {
				0% { -webkit-transform: rotate(0deg); }
				100% { -webkit-transform: rotate(360deg); }
			}

			@keyframes spin {
				0% { transform: rotate(0deg); }
				100% { transform: rotate(360deg); }
			}

			.wcj-bookings-price-wrapper { margin-bottom: 25px; }
			.wcj-bookings-price-wrapper.loading .wcj-loader { display: block; }
		</style>
			<?php
		}

		/**
		 * Add_input_fields_to_frontend.
		 *
		 * @version 6.0.1
		 * @since   2.5.0
		 * @todo    more options: exclude days (1-31), exact availability dates, mindate, maxdate, firstday, dateformat etc.
		 */
		public function add_input_fields_to_frontend() {
			if ( isset( $this->are_bookings_input_fields_displayed ) && 'yes' === wcj_get_option( 'wcj_product_bookings_check_for_outputted_data', 'yes' ) ) {
				return;
			}
			if ( $this->is_bookings_product( wc_get_product() ) ) {
				$this->create_custom_style();
				$wpnonce                  = isset( $_REQUEST['wcj_product_bookings-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_product_bookings-nonce'] ), 'wcj_product_bookings' ) : false;
				$data_table               = array();
				$date_from_value          = ( $wpnonce && isset( $_POST['wcj_product_bookings_date_from'] ) ) ? sanitize_text_field( wp_unslash( $_POST['wcj_product_bookings_date_from'] ) ) : '';
				$date_to_value            = ( $wpnonce && isset( $_POST['wcj_product_bookings_date_to'] ) ) ? sanitize_text_field( wp_unslash( $_POST['wcj_product_bookings_date_to'] ) ) : '';
				$date_from_exclude_days   = wcj_get_option( 'wcj_product_bookings_datepicker_date_from_exclude_days', '' );
				$date_from_exclude_days   = ( '' !== $date_from_exclude_days ? ' excludedays="[' . str_replace( 'S', '0', implode( ',', $date_from_exclude_days ) ) . ']"' : '' );
				$date_to_exclude_days     = wcj_get_option( 'wcj_product_bookings_datepicker_date_to_exclude_days', '' );
				$date_to_exclude_days     = ( '' !== $date_to_exclude_days ? ' excludedays="[' . str_replace( 'S', '0', implode( ',', $date_to_exclude_days ) ) . ']"' : '' );
				$date_from_exclude_months = wcj_get_option( 'wcj_product_bookings_datepicker_date_from_exclude_months', '' );
				$date_from_exclude_months = ( '' !== $date_from_exclude_months ? ' excludemonths="[' . implode( ',', $date_from_exclude_months ) . ']"' : '' );
				$date_to_exclude_months   = wcj_get_option( 'wcj_product_bookings_datepicker_date_to_exclude_months', '' );
				$date_to_exclude_months   = ( '' !== $date_to_exclude_months ? ' excludemonths="[' . implode( ',', $date_to_exclude_months ) . ']"' : '' );
				$data_table[]             = array(
					'<label for="wcj_product_bookings_date_from">' . wcj_get_option( 'wcj_product_bookings_label_date_from', __( 'Date from' ) ) . '</label>',
					'<input firstday="0"' . $date_from_exclude_days . $date_from_exclude_months . ' dateformat="mm/dd/yy" mindate="0" type="datepicker" display="date" id="wcj_product_bookings_date_from" name="wcj_product_bookings_date_from" placeholder="" value="' . $date_from_value . '">',
				);
				$data_table[]             = array(
					'<label for="wcj_product_bookings_date_to">' . wcj_get_option( 'wcj_product_bookings_label_date_to', __( 'Date to' ) ) . '</label>',
					'<input firstday="0"' . $date_to_exclude_days . $date_to_exclude_months . ' dateformat="mm/dd/yy" mindate="0" type="datepicker" display="date" id="wcj_product_bookings_date_to" name="wcj_product_bookings_date_to" placeholder="" value="' . $date_to_value . '">',
				);
				echo wp_kses_post( wcj_get_table_html( $data_table, array( 'table_heading_type' => 'none' ) ) );
				echo '<div class="wcj-bookings-price-wrapper"><div class="wcj-value"></div><div class="wcj-loader"></div></div>';
				echo '<div style="display:none !important;" name="wcj_bookings_message"><p style="color:red;"></p></div>';
				wp_nonce_field( 'wcj_product_bookings', 'wcj_product_bookings-nonce' );
				$this->are_bookings_input_fields_displayed = true;
			}
		}

		/**
		 * Add_per_day_label.
		 *
		 * @version 4.3.0
		 * @since   2.5.0
		 * @param int | string $price_html defines the price_html.
		 * @param array        $_product defines the _product.
		 */
		public function add_per_day_label( $price_html, $_product ) {
			if (
			'no' === wcj_get_option( 'wcj_product_bookings_price_per_day_variable_products', 'yes' ) &&
			( $_product->is_type( 'variable' ) || $_product->is_type( 'variation' ) )
			) {
				return $price_html;
			}
			return ( $this->is_bookings_product( $_product ) ) ? $price_html . ' ' . wcj_get_option( 'wcj_product_bookings_label_per_day', __( '/ day', 'woocommerce-jetpack' ) ) : $price_html;
		}

		/**
		 * Change_price.
		 *
		 * @version 4.3.0
		 * @since   2.5.0
		 * @param int   $price defines the price.
		 * @param array $_product defines the _product.
		 */
		public function change_price( $price, $_product ) {
			if (
			'no' === wcj_get_option( 'wcj_product_bookings_price_per_day_variable_products', 'yes' ) &&
			( $_product->is_type( 'variable' ) || $_product->is_type( 'variation' ) )
			) {
				return $price;
			}
			return ( $this->is_bookings_product( $_product ) && isset( $_product->wcj_bookings_price ) ) ? $_product->wcj_bookings_price : $price;
		}

		/**
		 * Save_meta_box_value.
		 *
		 * @version 5.6.8
		 * @since   2.5.0
		 * @param string $option_value defines the option_value.
		 * @param string $option_name defines the option_name.
		 * @param int    $module_id defines the module_id.
		 */
		public function save_meta_box_value( $option_value, $option_name, $module_id ) {
			if ( true === apply_filters( 'booster_option', false, true ) ) {
				return $option_value;
			}
			if ( 'no' === $option_value ) {
				return $option_value;
			}
			if ( $this->id === $module_id && 'wcj_product_bookings_enabled' === $option_name ) {
				$args = array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'meta_key'       => '_wcj_product_bookings_enabled', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'post__not_in'   => array( get_the_ID() ),
				);
				$loop = new WP_Query( $args );
				$c    = $loop->found_posts + 1;
				if ( $c >= 2 ) {
					add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
					return 'no';
				}
			}
			return $option_value;
		}

		/**
		 * Add_notice_query_var.
		 *
		 * @version 5.6.8
		 * @since   2.5.0
		 * @param string $location defines the location.
		 */
		public function add_notice_query_var( $location ) {
			remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
			return esc_url_raw(
				add_query_arg(
					array(
						'wcj_product_bookings_admin_notice' => true,
						'wcjnonce' => wp_create_nonce( 'wcj_product_bookings_admin_notice' ),
					),
					$location
				)
			);
		}

		/**
		 * Admin_notices.
		 *
		 * @version 5.6.8
		 * @since   2.5.0
		 */
		public function admin_notices() {
			$wpnonce = isset( $_GET['wcjnonce'] ) ? wp_verify_nonce( sanitize_key( $_GET['wcjnonce'] ), 'wcj_product_bookings_admin_notice' ) : false;
			if ( ! $wpnonce || ! isset( $_GET['wcj_product_bookings_admin_notice'] ) ) {
				return;
			}
			?>
		<div class="error"><p>
			<?php
			echo '<div class="message">'
				. wp_kses_post( __( 'Booster: Free plugin\'s version is limited to only one bookings product enabled at a time. You will need to get <a href="https://booster.io/buy-booster/" target="_blank">Booster Plus</a> to add unlimited number of bookings products.', 'woocommerce-jetpack' ) )
				. '</div>';
			?>
		</p></div>
			<?php
		}

		/**
		 * Is_bookings_product.
		 *
		 * @version 3.9.0
		 * @since   2.5.0
		 * @param array $_product defines the _product.
		 */
		public function is_bookings_product( $_product ) {
			return ( 'yes' === get_post_meta( wcj_get_product_id_or_variation_parent_id( $_product ), '_wcj_product_bookings_enabled', true ) );
		}

	}

endif;

return new WCJ_Product_Bookings();

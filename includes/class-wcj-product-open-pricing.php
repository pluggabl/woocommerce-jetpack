<?php
/**
 * Booster for WooCommerce - Module - Product Open Pricing
 *
 * @version 6.0.1
 * @since   2.4.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Product_Open_Pricing' ) ) :
	/**
	 * WCJ_Currencies.
	 */
	class WCJ_Product_Open_Pricing extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 4.6.0
		 * @since   2.4.8
		 */
		public function __construct() {

			$this->id         = 'product_open_pricing';
			$this->short_desc = __( 'Product Open Pricing (Name Your Price)', 'woocommerce-jetpack' );
			$this->desc       = __( 'Let your store customers enter price for the product manually.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-product-open-pricing-name-your-price';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				add_filter( WCJ_PRODUCT_GET_PRICE_FILTER, array( $this, 'get_open_price' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_product_variation_get_price', array( $this, 'get_open_price' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_get_price_html', array( $this, 'hide_original_price' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_get_variation_price_html', array( $this, 'hide_original_price' ), PHP_INT_MAX, 2 );
				if ( 'yes' === wcj_get_option( 'wcj_product_open_price_disable_quantity', 'yes' ) ) {
					add_filter( 'woocommerce_is_sold_individually', array( $this, 'hide_quantity_input_field' ), PHP_INT_MAX, 2 );
				}
				add_filter( 'woocommerce_is_purchasable', array( $this, 'is_purchasable' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_product_supports', array( $this, 'disable_add_to_cart_ajax' ), PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'add_to_cart_url' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'add_to_cart_text' ), PHP_INT_MAX, 2 );
				$position = wcj_get_option( 'wcj_product_open_price_position', 'woocommerce_before_add_to_cart_button' );
				add_action( $position, array( $this, 'add_open_price_input_field_to_frontend' ), PHP_INT_MAX );
				add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_open_price_on_add_to_cart' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_open_price_to_cart_item_data' ), PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_add_cart_item', array( $this, 'add_open_price_to_cart_item' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_open_price_from_session' ), PHP_INT_MAX, 3 );
				add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_value' ), PHP_INT_MAX, 3 );
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
				if ( 'yes' === wcj_get_option( 'wcj_product_open_price_enable_loop_price_info', 'no' ) ) {
					$this->loop_price_info_template = wcj_get_option( 'wcj_product_open_price_loop_price_info_template', '<span class="price">%default_price%</span>' );
					add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'add_price_info_to_loop' ), 10 );
				}
				if ( is_admin() && 'yes' === wcj_get_option( 'wcj_product_open_price_enable_admin_product_list_column', 'no' ) ) {
					add_filter( 'manage_edit-product_columns', array( $this, 'add_product_column_open_pricing' ), PHP_INT_MAX );
					add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column_open_pricing' ), PHP_INT_MAX );
				}
				$this->shop_currency = wcj_get_option( 'woocommerce_currency' );

				// WPC Product Bundles plugin.
				add_action(
					'woocommerce_init',
					function () {
						if ( 'yes' === wcj_get_option( 'wcj_product_open_price_woosb_product_bundles_remove_atc', 'no' ) ) {
							wcj_remove_class_filter( 'woocommerce_add_to_cart', 'WPcleverWoosb', 'woosb_add_to_cart' );
						}
					},
					99
				);
			}
		}

		/**
		 * Add_product_column_open_pricing.
		 *
		 * @version 2.8.0
		 * @since   2.8.0
		 * @param array $columns defines the columns.
		 */
		public function add_product_column_open_pricing( $columns ) {
			$columns['wcj_open_pricing'] = __( 'Open Pricing', 'woocommerce-jetpack' );
			return $columns;
		}

		/**
		 * Render_product_column_open_pricing.
		 *
		 * @version 2.8.0
		 * @since   2.8.0
		 * @param string $column defines the column.
		 */
		public function render_product_column_open_pricing( $column ) {
			if ( 'wcj_open_pricing' !== $column ) {
				return;
			}
			if ( 'yes' === get_post_meta( get_the_ID(), '_wcj_product_open_price_enabled', true ) ) {
				echo '<span id="wcj_open_pricing_admin_column">&#10004;</span>';
			}
		}

		/**
		 * Maybe_convert_price_currency.
		 *
		 * @version 6.0.1
		 * @since   4.2.0
		 * @param int            $price defines the price.
		 * @param string | array $product defines the product.
		 */
		public function maybe_convert_price_currency( $price, $product = null ) {
			if ( 'switched_currency' === wcj_get_option( 'wcj_product_open_price_currency_switcher', 'shop_currency' ) ) {
				// Multicurrency (Currency Switcher) module.
				if ( w_c_j()->all_modules['multicurrency']->is_enabled() ) {
					$price = w_c_j()->all_modules['multicurrency']->change_price( $price, $product );
				}
			}
			return $price;
		}

		/**
		 * Wc_price_shop_currency.
		 *
		 * @version 4.2.0
		 * @since   4.1.0
		 * @param int   $price defines the price.
		 * @param array $args defines the args.
		 */
		public function wc_price_shop_currency( $price, $args = array() ) {
			$args['currency'] = ( 'shop_currency' === wcj_get_option( 'wcj_product_open_price_currency_switcher', 'shop_currency' ) ? $this->shop_currency : get_woocommerce_currency() );
			return wc_price( $price, $args );
		}

		/**
		 * Add_price_info_to_loop.
		 *
		 * @version 4.6.0
		 * @since   2.8.0
		 */
		public function add_price_info_to_loop() {
			$_product_id = get_the_ID();
			$product     = wc_get_product( $_product_id );
			if ( $this->is_open_price_product( $_product_id ) ) {
				$replaceable_values = array(
					'%default_price%' => $this->wc_price_shop_currency( $this->maybe_convert_price_currency( get_post_meta( $_product_id, '_wcj_product_open_price_default_price', true ), $product ) ),
					'%min_price%'     => $this->wc_price_shop_currency( $this->maybe_convert_price_currency( get_post_meta( $_product_id, '_wcj_product_open_price_min_price', true ), $product ) ),
					'%max_price%'     => $this->wc_price_shop_currency( $this->maybe_convert_price_currency( get_post_meta( $_product_id, '_wcj_product_open_price_max_price', true ), $product ) ),
				);
				echo wp_kses_post( wcj_handle_replacements( $replaceable_values, $this->loop_price_info_template ) );
			}
		}

		/**
		 * Is_open_price_product.
		 *
		 * @version 2.8.2
		 * @since   2.4.8
		 * @param array $_product defines the _product.
		 */
		public function is_open_price_product( $_product ) {
			$_product_id = ( is_numeric( $_product ) ? $_product : wcj_get_product_id_or_variation_parent_id( $_product ) );
			return ( 'yes' === get_post_meta( $_product_id, '_wcj_product_open_price_enabled', true ) );
		}

		/**
		 * Disable_add_to_cart_ajax.
		 *
		 * @version 2.5.0
		 * @since   2.5.0
		 * @param bool   $supports defines the supports.
		 * @param string $feature defines the feature.
		 * @param array  $_product defines the _product.
		 */
		public function disable_add_to_cart_ajax( $supports, $feature, $_product ) {
			if ( $this->is_open_price_product( $_product ) && 'ajax_add_to_cart' === $feature ) {
				$supports = false;
			}
			return $supports;
		}

		/**
		 * Save_meta_box_value.
		 *
		 * @version 5.6.8
		 * @since   2.4.8
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
			if ( $this->id === $module_id && 'wcj_product_open_price_enabled' === $option_name ) {
				$args = array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'meta_key'       => '_wcj_product_open_price_enabled', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
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
		 * @since   2.4.8
		 * @param string $location defines the location.
		 */
		public function add_notice_query_var( $location ) {
			remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
			return esc_url_raw(
				add_query_arg(
					array(
						'wcj_product_open_price_admin_notice' => true,
						'wcj_product_open_price_admin_notice-nonce' => wp_create_nonce( 'wcj_product_open_price_admin_notice' ),
					),
					$location
				)
			);
		}

		/**
		 * Admin_notices.
		 *
		 * @version 5.6.2
		 * @since   2.4.8
		 */
		public function admin_notices() {
			$wpnonce = isset( $_REQUEST['wcj_product_open_price_admin_notice-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_product_open_price_admin_notice-nonce'] ), 'wcj_product_open_price_admin_notice' ) : false;
			if ( ! $wpnonce || ! isset( $_GET['wcj_product_open_price_admin_notice'] ) ) {
				return;
			}
			?><div class="error"><p>
			<?php
			echo '<div class="message">'
				. wp_kses_post( 'Booster: Free plugin\'s version is limited to only one open pricing product enabled at a time. You will need to get <a href="https://booster.io/buy-booster/" target="_blank">Booster Plus</a> to add unlimited number of open pricing products.', 'woocommerce-jetpack' )
				. '</div>';
			?>
		</p></div>
			<?php
		}

		/**
		 * As_purchasable.
		 *
		 * @version 2.7.0
		 * @since   2.4.8
		 * @todo    maybe `wcj_get_product_id()` instead of `wcj_get_product_id_or_variation_parent_id()` - check `is_purchasable()` in `WC_Product` class.
		 * @param string | bool $purchasable defines the purchasable.
		 * @param array         $_product defines the _product.
		 */
		public function is_purchasable( $purchasable, $_product ) {
			if ( $this->is_open_price_product( $_product ) ) {
				$purchasable = true;

				// Products must exist of course.
				if ( ! $_product->exists() ) {
					$purchasable = false;

					// Other products types need a price to be set.

					// Check the product is published.
				} elseif ( wcj_get_product_status( $_product ) !== 'publish' && ! current_user_can( 'edit_post', wcj_get_product_id_or_variation_parent_id( $_product ) ) ) {
					$purchasable = false;
				}
			}
			return $purchasable;
		}

		/**
		 * Add_to_cart_text.
		 *
		 * @version 2.4.8
		 * @since   2.4.8
		 * @param string $text defines the text.
		 * @param array  $_product defines the _product.
		 */
		public function add_to_cart_text( $text, $_product ) {
			return ( $this->is_open_price_product( $_product ) ) ? __( 'Read more', 'woocommerce' ) : $text;
		}

		/**
		 * Add_to_cart_url.
		 *
		 * @version 2.7.0
		 * @since   2.4.8
		 * @param string $url defines the url.
		 * @param array  $_product defines the _product.
		 */
		public function add_to_cart_url( $url, $_product ) {
			return ( $this->is_open_price_product( $_product ) ) ? get_permalink( wcj_get_product_id_or_variation_parent_id( $_product ) ) : $url;
		}

		/**
		 * Hide_quantity_input_field.
		 *
		 * @version 2.4.8
		 * @since   2.4.8
		 * @param string | bool $return defines the return.
		 * @param array         $_product defines the _product.
		 */
		public function hide_quantity_input_field( $return, $_product ) {
			return ( $this->is_open_price_product( $_product ) ) ? true : $return;
		}

		/**
		 * Hide_original_price.
		 *
		 * @version 2.4.8
		 * @since   2.4.8
		 * @param  int   $price defines the price.
		 * @param array $_product defines the _product.
		 */
		public function hide_original_price( $price, $_product ) {
			return ( $this->is_open_price_product( $_product ) ) ? '' : $price;
		}

		/**
		 * Get_open_price.
		 *
		 * @version 6.0.1
		 * @since   2.4.8
		 * @param int   $price defines the price.
		 * @param array $_product defines the _product.
		 */
		public function get_open_price( $price, $_product ) {
			if ( $this->is_open_price_product( $_product ) && isset( $_product->wcj_open_price ) ) {
				if ( 'no' === wcj_get_option( 'wcj_product_open_price_woosb_product_bundles_replace_prices', 'no' ) && 'WC_Product_Woosb' === get_class( $_product ) ) {
					// "WPC Product Bundles for WooCommerce" plugin.
					return $price;
				}
				if ( 'yes' === wcj_get_option( 'wcj_product_open_price_check_for_product_changes_price', 'no' ) ) {
					$product_changes = $_product->get_changes();
					if ( ! empty( $product_changes ) && isset( $product_changes['price'] ) ) {
						return $price;
					}
				}
				$price = $_product->wcj_open_price;
				// Multicurrency (Currency Switcher) module.
				if ( w_c_j()->all_modules['multicurrency']->is_enabled() ) {
					$price = w_c_j()->all_modules['multicurrency']->change_price( $price, null );
				}
				return $price;
			} else {
				return $price;
			}
		}

		/**
		 * Validate_open_price_on_add_to_cart.
		 *
		 * @version 5.6.8
		 * @since   2.4.8
		 * @param string $passed defines the passed.
		 * @param int    $product_id defines the product_id.
		 */
		public function validate_open_price_on_add_to_cart( $passed, $product_id ) {
			$wpnonce     = isset( $_REQUEST['wcj_open_price-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_open_price-nonce'] ), 'wcj_open_price' ) : false;
			$the_product = wc_get_product( $product_id );
			if ( $this->is_open_price_product( $the_product ) ) {
				// Empty price.
				if ( ! $wpnonce || ! isset( $_POST['wcj_open_price'] ) || '' === $_POST['wcj_open_price'] ) {
					wc_add_notice( wcj_get_option( 'wcj_product_open_price_messages_required', __( 'Price is required!', 'woocommerce-jetpack' ) ), 'error' );
					return false;
				}
				// Min & Max.
				$min_price = $this->maybe_convert_price_currency( get_post_meta( $product_id, '_wcj_product_open_price_min_price', true ) );
				if ( ( $min_price ) > 0 && $_POST['wcj_open_price'] < $min_price ) {
					wc_add_notice(
						wcj_handle_replacements(
							array(
								'%price%'     => $this->wc_price_shop_currency( sanitize_text_field( wp_unslash( $_POST['wcj_open_price'] ) ) ),
								'%min_price%' => $this->wc_price_shop_currency( $min_price ),
							),
							get_option( 'wcj_product_open_price_messages_to_small', __( 'Entered price is too small!', 'woocommerce-jetpack' ) )
						),
						'error'
					);
					return false;
				}
				$max_price = $this->maybe_convert_price_currency( get_post_meta( $product_id, '_wcj_product_open_price_max_price', true ) );
				if ( ( $max_price ) > 0 && $_POST['wcj_open_price'] > $max_price ) {

					wc_add_notice(
						wcj_handle_replacements(
							array(
								'%price%'     => $this->wc_price_shop_currency( sanitize_text_field( wp_unslash( $_POST['wcj_open_price'] ) ) ),
								'%max_price%' => $this->wc_price_shop_currency( $max_price ),
							),
							get_option( 'wcj_product_open_price_messages_to_big', __( 'Entered price is too big!', 'woocommerce-jetpack' ) )
						),
						'error'
					);
					return false;
				}
			}
			return $passed;
		}

		/**
		 * Get_cart_item_open_price_from_session.
		 *
		 * @version 2.4.8
		 * @since   2.4.8
		 * @param array  $item defines the item.
		 * @param array  $values defines the values.
		 * @param string $key defines the key.
		 */
		public function get_cart_item_open_price_from_session( $item, $values, $key ) {
			if ( array_key_exists( 'wcj_open_price', $values ) ) {
				$item['data']->wcj_open_price = $values['wcj_open_price'];
			}
			return $item;
		}

		/**
		 * Add_open_price_to_cart_item_data.
		 *
		 * @version 6.0.1
		 * @since   2.4.8
		 * @todo    [dev] (maybe) better conversion for Currency Switcher module (i.e. include rounding)
		 * @param array $cart_item_data defines the cart_item_data.
		 * @param int   $product_id defines the product_id.
		 * @param int   $variation_id defines the variation_id.
		 */
		public function add_open_price_to_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
			$wpnonce = isset( $_REQUEST['wcj_open_price-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_open_price-nonce'] ), 'wcj_open_price' ) : false;
			if ( $wpnonce && isset( $_POST['wcj_open_price'] ) ) {
				$cart_item_data['wcj_open_price'] = sanitize_text_field( wp_unslash( $_POST['wcj_open_price'] ) );
				$product_bundles_divide           = wcj_get_option( 'wcj_product_open_price_woosb_product_bundles_divide', 'no' );
				if ( 'no' !== ( $product_bundles_divide ) ) {
					// "WPC Product Bundles for WooCommerce" plugin.
					if ( ! empty( $cart_item_data['woosb_parent_id'] ) ) {
						$parent_product = wc_get_product( $cart_item_data['woosb_parent_id'] );
						if ( 'WC_Product_Woosb' === get_class( $parent_product ) ) {
							if ( 'yes' === $product_bundles_divide ) {
								$total_products_in_bundle = count( $parent_product->get_items() );
								if ( $total_products_in_bundle ) {
									$qty                              = ( ! empty( $cart_item_data['woosb_qty'] ) ? $cart_item_data['woosb_qty'] : 1 );
									$cart_item_data['wcj_open_price'] = $cart_item_data['wcj_open_price'] / $total_products_in_bundle / $qty;
								}
							} else { // 'proportionally'.
								$total_original_price = 0;
								foreach ( $parent_product->get_items() as $child ) {
									$total_original_price += get_post_meta( $child['id'], '_price', true );
								}
								if ( 0 !== $total_original_price ) {
									$qty                              = ( ! empty( $cart_item_data['woosb_qty'] ) ? $cart_item_data['woosb_qty'] : 1 );
									$cart_item_data['wcj_open_price'] = $cart_item_data['wcj_open_price'] * $cart_item_data['woosb_price'] / $total_original_price / $qty;
								}
							}
						}
					}
				}
				if ( 'switched_currency' === wcj_get_option( 'wcj_product_open_price_currency_switcher', 'shop_currency' ) ) {
					// Multicurrency (Currency Switcher) module.
					if ( w_c_j()->all_modules['multicurrency']->is_enabled() ) {
						$current_currency = w_c_j()->all_modules['multicurrency']->get_current_currency_code();
						if ( ( $current_currency ) !== $this->shop_currency ) {
							$rate = w_c_j()->all_modules['multicurrency']->get_currency_exchange_rate( $current_currency );
							if ( 0 !== ( $rate ) ) {
								$cart_item_data['wcj_open_price'] = $cart_item_data['wcj_open_price'] / $rate;
							}
						}
					}
				}
			}
			return $cart_item_data;
		}

		/**
		 * Add_open_price_to_cart_item.
		 *
		 * @version 2.4.8
		 * @since   2.4.8
		 * @param array  $cart_item_data defines the cart_item_data.
		 * @param string $cart_item_key defines the cart_item_key.
		 */
		public function add_open_price_to_cart_item( $cart_item_data, $cart_item_key ) {
			if ( isset( $cart_item_data['wcj_open_price'] ) ) {
				$cart_item_data['data']->wcj_open_price = $cart_item_data['wcj_open_price'];
			}
			return $cart_item_data;
		}

		/**
		 * Add_open_price_input_field_to_frontend.
		 *
		 * @version 5.6.8
		 * @since   2.4.8
		 */
		public function add_open_price_input_field_to_frontend() {
			if ( isset( $this->is_open_price_input_field_displayed ) && 'yes' === wcj_get_option( 'wcj_product_open_price_check_for_outputted_data', 'yes' ) ) {
				return;
			}
			$the_product = wc_get_product();
			if ( $this->is_open_price_product( $the_product ) ) {
				$wpnonce = isset( $_REQUEST['wcj_open_price-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_open_price-nonce'] ), 'wcj_open_price' ) : false;
				// Title.
				$title = wcj_get_option( 'wcj_product_open_price_label_frontend', __( 'Name Your Price', 'woocommerce-jetpack' ) );
				// Prices.
				$_product_id   = wcj_get_product_id_or_variation_parent_id( $the_product );
				$min_price     = $this->maybe_convert_price_currency( get_post_meta( $_product_id, '_wcj_product_open_price_min_price', true ) );
				$max_price     = $this->maybe_convert_price_currency( get_post_meta( $_product_id, '_wcj_product_open_price_max_price', true ) );
				$default_price = $this->maybe_convert_price_currency( get_post_meta( $_product_id, '_wcj_product_open_price_default_price', true ) );
				// Input field.
				$value              = ( $wpnonce && isset( $_POST['wcj_open_price'] ) ) ? sanitize_text_field( wp_unslash( $_POST['wcj_open_price'] ) ) : $default_price;
				$default_price_step = 1 / pow( 10, absint( wcj_get_option( 'woocommerce_price_num_decimals', 2 ) ) );
				$custom_attributes  = '';
				$custom_attributes .= 'step="' . wcj_get_option( 'wcj_product_open_price_price_step', $default_price_step ) . '" ';
				$custom_attributes .= ( '' === $min_price || 'no' === wcj_get_option( 'wcj_product_open_price_enable_js_validation', 'no' ) ) ? 'min="0" ' : 'min="' . $min_price . '" ';
				$custom_attributes .= ( '' === $max_price || 'no' === wcj_get_option( 'wcj_product_open_price_enable_js_validation', 'no' ) ) ? '' : 'max="' . $max_price . '" ';
				$input_field        = '<input '
				. 'type="number" '
				. 'class="text" '
				. 'style="' . wcj_get_option( 'wcj_product_open_price_input_style', 'width:75px;text-align:center;' ) . '" '
				. 'name="wcj_open_price" '
				. 'id="wcj_open_price" '
				. 'placeholder="' . wcj_get_option( 'wcj_product_open_price_input_placeholder', '' ) . '" '
				. 'value="' . $value . '" '
				. $custom_attributes . '>';
				// Currency symbol.
				$currency_symbol = get_woocommerce_currency_symbol(
					( 'shop_currency' === wcj_get_option( 'wcj_product_open_price_currency_switcher', 'shop_currency' ) ? $this->shop_currency : '' )
				);
				// Replacing final values.
				$replacement_values = array(
					'%frontend_label%'       => $title,
					'%open_price_input%'     => $input_field,
					'%currency_symbol%'      => $currency_symbol,
					'%min_price_simple%'     => $min_price,
					'%max_price_simple%'     => $max_price,
					'%default_price_simple%' => $default_price,
					'%min_price%'            => $this->wc_price_shop_currency( $min_price ),
					'%max_price%'            => $this->wc_price_shop_currency( $max_price ),
					'%default_price%'        => $this->wc_price_shop_currency( $default_price ),
				);
				echo wp_kses_post(
					str_replace(
						array_keys( $replacement_values ),
						array_values( $replacement_values ),
						get_option( 'wcj_product_open_price_frontend_template', '<label for="wcj_open_price">%frontend_label%</label> %open_price_input% %currency_symbol%' )
					)
				);
				wp_nonce_field( 'wcj_open_price', 'wcj_open_price-nonce' );
				$this->is_open_price_input_field_displayed = true;
			}
		}

	}

endif;

return new WCJ_Product_Open_Pricing();

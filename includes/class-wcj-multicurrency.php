<?php
/**
 * Booster for WooCommerce - Module - Multicurrency (Currency Switcher)
 *
 * @version 4.4.0
 * @since   2.4.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Multicurrency' ) ) :

class WCJ_Multicurrency extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 4.3.0
	 * @todo    check if we can just always execute `init()` on `init` hook
	 */
	function __construct() {

		$this->id         = 'multicurrency';
		$this->short_desc = __( 'Multicurrency (Currency Switcher)', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add multiple currencies (currency switcher) to WooCommerce.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-multicurrency-currency-switcher';
		$this->extra_desc = sprintf( __( 'After setting currencies in the Currencies Options section below, you can add switcher to the frontend with: %s', 'woocommerce-jetpack' ),
			'<ol>' .
				'<li>' . sprintf( __( '<strong>Widget:</strong> "%s"', 'woocommerce-jetpack' ),
					__( 'Booster - Multicurrency Switcher', 'woocommerce-jetpack' ) ) .
				'</li>' .
				'<li>' . sprintf( __( '<strong>Shortcodes:</strong> %s', 'woocommerce-jetpack' ),
					'<code>[wcj_currency_select_drop_down_list]</code>, <code>[wcj_currency_select_radio_list]</code>, <code>[wcj_currency_select_link_list]</code>' ) .
				'</li>' .
				'<li>' . sprintf( __( '<strong>PHP code:</strong> by using %s function, e.g.: %s', 'woocommerce-jetpack' ),
					'<code>do_shortcode()</code>',
					'<code>echo&nbsp;do_shortcode(&nbsp;\'[wcj_currency_select_drop_down_list]\'&nbsp;);</code>' ) .
				'</li>' .
			'</ol>' );
		parent::__construct();

		if ( $this->is_enabled() ) {
			$this->price_hooks_priority = wcj_get_module_price_hooks_priority( 'multicurrency' );

			// Session
			if ( 'wc' === WCJ_SESSION_TYPE ) {
				// `init()` executed on `init` hook because we need to use `WC()->session`
				add_action( 'init', array( $this, 'init' ) );
			} else {
				$this->init();
			}

			$this->add_hooks();

			if ( 'yes' === get_option( 'wcj_multicurrency_per_product_enabled' , 'yes' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}

			if ( is_admin() ) {
				include_once( 'reports/class-wcj-currency-reports.php' );
			}
		}
	}

	/**
	 * Handles third party compatibility
	 *
	 * @version 4.4.0
	 * @since   4.3.0
	 */
	function handle_third_party_compatibility(){
		// "WooCommerce Smart Coupons" Compatibility
		if ( 'yes' === get_option( 'wcj_multicurrency_compatibility_wc_smart_coupons' , 'yes' ) ) {
			add_filter( 'woocommerce_coupon_get_amount', array( $this, 'smart_coupons_get_amount' ), 10, 2 );
		}

		// WooCommerce Price Filter Widget
		if ( 'yes' === get_option( 'wcj_multicurrency_compatibility_wc_price_filter' , 'yes' ) ) {
			add_action( 'wp_footer', array( $this, 'add_compatibility_with_price_filter_widget' ) );
			add_action( 'wp_footer', array( $this, 'fix_price_filter_widget_currency_format' ) );
		}

		// Fix WooCommerce Import
		if ( 'yes' === get_option( 'wcj_multicurrency_compatibility_wc_import' , 'no' ) ) {
			add_filter( 'woocommerce_product_importer_parsed_data', array( $this, 'fix_wc_product_import' ), 10, 2 );
		}
	}

	/**
	 * Fixes WooCommerce import.
	 *
	 * For now it converts '_wcj_%price%_{currency}' meta with lowercase currency to uppercase.
	 *
	 * @version 4.4.0
	 * @since   4.4.0
	 *
	 * @param $parsed_data
	 * @param $raw_data
	 *
	 * @return mixed
	 */
	function fix_wc_product_import( $parsed_data, $raw_data ) {
		// Gets values
		$multicurrency_values = array_filter( $parsed_data['meta_data'], function ( $value ) {
			return preg_match( '/^_wcj_.+price.*_[a-z]{2,3}$/', $value['key'] );
		} );

		// Changes lowercase currency to uppercase
		$modified_values = array_map( function ( $value ) {
			$str             = $value['key'];
			$last_underscore = strrpos( $str, '_' );
			$before          = substr( $str, 0, $last_underscore );
			$currency        = substr( $str, $last_underscore + 1 );
			$value['key']    = $before . '_' . strtoupper( $currency );
			return $value;
		}, $multicurrency_values );

		// Applies changes to the main data
		foreach ( $modified_values as $i => $value ) {
			$parsed_data['meta_data'][ $i ]['key'] = $value['key'];
		}

		return $parsed_data;
	}

	/**
	 * Adds compatibility with WooCommerce Price Filter widget
	 * @version 4.3.0
	 * @since   4.3.0
	 */
	function add_compatibility_with_price_filter_widget() {
		if ( ! is_active_widget( false, false, 'woocommerce_price_filter' ) ) {
			return;
		}
		?>
		<?php
		$exchange_rate = $this->get_currency_exchange_rate( $this->get_current_currency_code() );
		?>
		<input type="hidden" id="wcj_mc_exchange_rate" value="<?php echo esc_html( $exchange_rate ) ?>"/>
		<script>
			var wcj_mc_pf_slider = {
				slider: null,
				convert_rate: 1,
				original_min: 1,
				original_max: 1,
				original_values: [],
				current_min: 1,
				current_max: 1,
				current_values: [],

				init(slider, convert_rate) {
					this.slider = slider;
					this.convert_rate = convert_rate;
					this.original_min = jQuery(this.slider).slider("option", "min");
					this.original_max = jQuery(this.slider).slider("option", "max");
					this.original_values = jQuery(this.slider).slider("option", "values");
					this.current_min = this.original_min * this.convert_rate;
					this.current_max = this.original_max * this.convert_rate;
					this.current_values = this.original_values.map(function (elem) {
						return elem * wcj_mc_pf_slider.convert_rate;
					});
					this.update_slider();
				},

				/**
				 * @see price-slider.js, init_price_filter()
				 */
				update_slider() {
					jQuery(this.slider).slider("destroy");
					var current_min_price = Math.floor(this.current_min);
					var current_max_price = Math.ceil(this.current_max);

					jQuery(this.slider).slider({
						range: true,
						animate: true,
						min: current_min_price,
						max: current_max_price,
						values: wcj_mc_pf_slider.current_values,
						create: function () {
							jQuery(wcj_mc_pf_slider.slider).parent().find('.price_slider_amount #min_price').val(wcj_mc_pf_slider.current_values[0] / wcj_mc_pf_slider.convert_rate);
							jQuery(wcj_mc_pf_slider.slider).parent().find('.price_slider_amount #max_price').val(wcj_mc_pf_slider.current_values[1] / wcj_mc_pf_slider.convert_rate);
							jQuery(document.body).trigger('price_slider_create', [Math.floor(wcj_mc_pf_slider.current_values[0]), Math.ceil(wcj_mc_pf_slider.current_values[1])]);
						},
						slide: function (event, ui) {
							jQuery(wcj_mc_pf_slider.slider).parent().find('.price_slider_amount #min_price').val(Math.floor(ui.values[0] / wcj_mc_pf_slider.convert_rate));
							jQuery(wcj_mc_pf_slider.slider).parent().find('.price_slider_amount #max_price').val(Math.ceil(ui.values[1] / wcj_mc_pf_slider.convert_rate));
							jQuery(document.body).trigger('price_slider_slide', [Math.floor(ui.values[0]), Math.ceil(ui.values[1])]);
						},
						change: function (event, ui) {
							jQuery(document.body).trigger('price_slider_change', [Math.floor(ui.values[0]), Math.ceil(ui.values[1])]);
						}
					});
				}
			};
			var wcj_mc_pf = {
				price_filters: null,
				rate: 1,
				init: function (price_filters) {
					this.price_filters = price_filters;
					this.rate = document.getElementById('wcj_mc_exchange_rate').value;
					this.update_slider();
				},
				update_slider: function () {
					[].forEach.call(wcj_mc_pf.price_filters, function (el) {
						wcj_mc_pf_slider.init(el, wcj_mc_pf.rate);
					});
				}
			}
			document.addEventListener("DOMContentLoaded", function () {
				var price_filters = document.querySelectorAll('.price_slider.ui-slider');
				if (price_filters.length) {
					wcj_mc_pf.init(price_filters);
				}
			});
		</script>
		<?php
	}

	/**
	 * Fixes price filter widget currency format
	 *
	 * @version 4.3.0
	 * @since   4.3.0
	 */
	function fix_price_filter_widget_currency_format() {
		$price_args = apply_filters( 'wc_price_args', array(
			'ex_tax_label'       => false,
			'currency'           => '',
			'decimal_separator'  => wc_get_price_decimal_separator(),
			'thousand_separator' => wc_get_price_thousand_separator(),
			'decimals'           => wc_get_price_decimals(),
			'price_format'       => get_woocommerce_price_format(),
		) );
		$symbol     = apply_filters( 'woocommerce_currency_symbol', get_woocommerce_currency_symbol(), get_woocommerce_currency() );
		wp_localize_script(
			'wc-price-slider', 'woocommerce_price_slider_params', array(
				'currency_format_num_decimals' => $price_args['decimals'],
				'currency_format_symbol'       => $symbol,
				'currency_format_decimal_sep'  => esc_attr( $price_args['decimal_separator'] ),
				'currency_format_thousand_sep' => esc_attr( $price_args['thousand_separator'] ),
				'currency_format'              => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), $price_args['price_format'] ) ),
			)
		);
	}

	/**
	 * add_hooks.
	 *
	 * @version 4.3.0
	 */
	function add_hooks() {
		if ( wcj_is_frontend() ) {

			// Prices - Compatibility - "WooCommerce TM Extra Product Options" plugin
			add_filter( 'woocommerce_tm_epo_price_on_cart', array( $this, 'change_price_by_currency_tm_extra_product_options_plugin_cart' ), $this->price_hooks_priority, 1 );
			add_filter( 'wc_epo_price',                     array( $this, 'change_price_by_currency_tm_extra_product_options_plugin' ),      $this->price_hooks_priority, 3 );

			// Currency hooks
			add_filter( 'woocommerce_currency', array( $this, 'change_currency_code' ), $this->price_hooks_priority, 1 );

			// Add "Change Price" hooks
			wcj_add_change_price_hooks( $this, $this->price_hooks_priority );

			// "WooCommerce Product Add-ons" plugin
			add_filter( 'woocommerce_get_item_data', array( $this, 'get_addons_item_data' ), 20, 2 );
			add_filter( 'woocommerce_product_addons_option_price_raw', array( $this, 'product_addons_option_price_raw' ), 10, 2 );

			// Third Party Compatibility
			$this->handle_third_party_compatibility();

			// Additional Price Filters
			$this->additional_price_filters = get_option( 'wcj_multicurrency_switcher_additional_price_filters', '' );
			if ( ! empty( $this->additional_price_filters ) ) {
				$this->additional_price_filters = array_map( 'trim', explode( PHP_EOL, $this->additional_price_filters ) );
				foreach ( $this->additional_price_filters as $additional_price_filter ) {
					add_filter( $additional_price_filter, array( $this, 'change_price' ), $this->price_hooks_priority, 2 );
				}
			} else {
				$this->additional_price_filters = array();
			}

		}
	}

	/**
	 * Converts Smart Coupon currency.
	 *
	 * @version 4.3.0
	 * @since   4.3.0
	 *
	 * @param $value
	 * @param $coupon
	 *
	 * @return float|int
	 */
	function smart_coupons_get_amount( $value, $coupon ) {
		if (
			! is_a( $coupon, 'WC_Coupon' ) ||
			'smart_coupon' !== $coupon->get_discount_type()
		) {
			return $value;
		}
		$value = $this->change_price( $value, null );
		return $value;
	}

	/**
	 * init.
	 *
	 * @version 3.4.5
	 * @since   3.4.5
	 */
	function init() {
		wcj_session_maybe_start();
		if ( isset( $_REQUEST['wcj-currency'] ) ) {
			wcj_session_set( 'wcj-currency', $_REQUEST['wcj-currency'] );
		}
	}

	/**
	 * Converts add-ons plugin prices.
	 *
	 * @version 4.3.0
	 * @since   4.3.0
	 *
	 * @param $price
	 * @param $option
	 *
	 * @return float|int
	 */
	function product_addons_option_price_raw( $price, $option ) {
		$price = $this->change_price( $price, null );
		return $price;
	}

	/**
	 * Finds old add-ons fields on cart and replace by correct price.
	 *
	 * @version 4.3.0
	 * @since   4.3.0
	 *
	 * @param $other_data
	 * @param $cart_item
	 *
	 * @return mixed
	 */
	function get_addons_item_data( $other_data, $cart_item ) {
		if ( ! empty( $cart_item['addons'] ) ) {
			foreach ( $cart_item['addons'] as $addon ) {
				$price    = isset( $cart_item['addons_price_before_calc'] ) ? $cart_item['addons_price_before_calc'] : $addon['price'];
				$name_old = $addon['name'];

				// Get old field name (with wrong currency price)
				if ( 0 == $addon['price'] ) {
					$name_old .= '';
				} elseif ( 'percentage_based' === $addon['price_type'] && 0 == $price ) {
					$name_old .= '';
				} elseif ( 'percentage_based' !== $addon['price_type'] && $addon['price'] && apply_filters( 'woocommerce_addons_add_price_to_name', '__return_true' ) ) {
					$name_old .= ' (' . wc_price( \WC_Product_Addons_Helper::get_product_addon_price_for_display( $addon['price'], $cart_item['data'], true ) ) . ')';
				} else {
					$_product = new WC_Product( $cart_item['product_id'] );
					$_product->set_price( $price * ( $addon['price'] / 100 ) );
					$name_old .= ' (' . WC()->cart->get_product_price( $_product ) . ')';
				}

				// Get new field name (with correct currency price)
				$name_new       = $addon['name'];
				$addon['price'] = $this->change_price( $addon['price'], null );
				if ( 0 == $addon['price'] ) {
					$name_new .= '';
				} elseif ( 'percentage_based' === $addon['price_type'] && 0 == $price ) {
					$name_new .= '';
				} elseif ( 'percentage_based' !== $addon['price_type'] && $addon['price'] && apply_filters( 'woocommerce_addons_add_price_to_name', '__return_true' ) ) {
					$name_new .= ' (' . wc_price( \WC_Product_Addons_Helper::get_product_addon_price_for_display( $addon['price'], $cart_item['data'], true ) ) . ')';
				} else {
					$_product = new WC_Product( $cart_item['product_id'] );
					$_product->set_price( $price * ( $addon['price'] / 100 ) );
					$name_new .= ' (' . WC()->cart->get_product_price( $_product ) . ')';
				}

				// Find old field on cart and replace by correct price
				foreach ( $other_data as $key => $data ) {
					if ( $data['name'] == $name_old ) {
						$other_data[ $key ]['name'] = $name_new;
					}
				}
			}
		}
		return $other_data;
	}

	/**
	 * change_price_by_currency_tm_extra_product_options_plugin_cart.
	 *
	 * @version 2.7.0
	 * @since   2.5.7
	 */
	function change_price_by_currency_tm_extra_product_options_plugin_cart( $price ) {
		return $this->change_price( $price, null );
	}

	/**
	 * change_price_by_currency_tm_extra_product_options_plugin.
	 *
	 * @version 2.7.0
	 * @since   2.5.7
	 */
	function change_price_by_currency_tm_extra_product_options_plugin( $price, $type, $post_id ) {
		return $this->change_price( $price, null );
	}

	/**
	 * change_price_grouped.
	 *
	 * @version 2.7.0
	 * @since   2.5.0
	 */
	function change_price_grouped( $price, $qty, $_product ) {
		if ( $_product->is_type( 'grouped' ) ) {
			if ( 'yes' === get_option( 'wcj_multicurrency_per_product_enabled' , 'yes' ) ) {
				foreach ( $_product->get_children() as $child_id ) {
					$the_price = get_post_meta( $child_id, '_price', true );
					$the_product = wc_get_product( $child_id );
					$the_price = wcj_get_product_display_price( $the_product, $the_price, 1 );
					if ( $the_price == $price ) {
						return $this->change_price( $price, $the_product );
					}
				}
			} else {
				return $this->change_price( $price, null );
			}
		}
		return $price;
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 3.8.0
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$currency_code = $this->get_current_currency_code();
		$price_hash['wcj_multicurrency'] = array(
			'currency'                => $currency_code,
			'exchange_rate'           => $this->get_currency_exchange_rate( $currency_code ),
			'per_product'             => get_option( 'wcj_multicurrency_per_product_enabled', 'yes' ),
			'per_product_make_empty'  => get_option( 'wcj_multicurrency_per_product_make_empty', 'no' ),
			'rounding'                => get_option( 'wcj_multicurrency_rounding', 'no_round' ),
			'rounding_precision'      => get_option( 'wcj_multicurrency_rounding_precision', absint( get_option( 'woocommerce_price_num_decimals', 2 ) ) ),
		);
		return $price_hash;
	}

	/**
	 * get_currency_exchange_rate.
	 *
	 * @version 2.4.3
	 */
	function get_currency_exchange_rate( $currency_code ) {
		$currency_exchange_rate = 1;
		$total_number = apply_filters( 'booster_option', 2, get_option( 'wcj_multicurrency_total_number', 2 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( $currency_code === get_option( 'wcj_multicurrency_currency_' . $i ) ) {
				$currency_exchange_rate = get_option( 'wcj_multicurrency_exchange_rate_' . $i );
				break;
			}
		}
		return $currency_exchange_rate;
	}

	/**
	 * do_revert.
	 *
	 * @version 3.9.0
	 * @since   2.5.0
	 */
	function do_revert() {
		switch ( get_option( 'wcj_multicurrency_revert', 'no' ) ) {
			case 'cart_only':
				return is_cart();
			case 'yes': // checkout only
				return is_checkout();
			case 'cart_and_checkout':
				return ( is_cart() || is_checkout() );
			default: // 'no'
				return false;
		}
	}

	/**
	 * change_price.
	 *
	 * @version 3.8.0
	 */
	function change_price( $price, $_product ) {
		if ( '' === $price ) {
			return $price;
		}

		if ( $this->do_revert() ) {
			return $price;
		}

		// Per product
		if ( 'yes' === get_option( 'wcj_multicurrency_per_product_enabled' , 'yes' ) && null != $_product ) {
			$_product_id = wcj_get_product_id( $_product );
			if (
				'yes' === get_option( 'wcj_multicurrency_per_product_make_empty', 'no' ) &&
				'yes' === get_post_meta( $_product_id, '_' . 'wcj_multicurrency_per_product_make_empty_' . $this->get_current_currency_code(), true )
			) {
				return '';
			} elseif ( '' != ( $regular_price_per_product = get_post_meta( $_product_id, '_' . 'wcj_multicurrency_per_product_regular_price_' . $this->get_current_currency_code(), true ) ) ) {
				$_current_filter = current_filter();
				if ( 'woocommerce_get_price_including_tax' == $_current_filter || 'woocommerce_get_price_excluding_tax' == $_current_filter ) {
					return wcj_get_product_display_price( $_product );

				} elseif ( WCJ_PRODUCT_GET_PRICE_FILTER == $_current_filter || 'woocommerce_variation_prices_price' == $_current_filter || 'woocommerce_product_variation_get_price' == $_current_filter || in_array( $_current_filter, $this->additional_price_filters ) ) {
					$sale_price_per_product = get_post_meta( $_product_id, '_' . 'wcj_multicurrency_per_product_sale_price_' . $this->get_current_currency_code(), true );
					return ( '' != $sale_price_per_product && $sale_price_per_product < $regular_price_per_product ) ? $sale_price_per_product : $regular_price_per_product;

				} elseif ( WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER == $_current_filter || 'woocommerce_variation_prices_regular_price' == $_current_filter || 'woocommerce_product_variation_get_regular_price' == $_current_filter ) {
					return $regular_price_per_product;

				} elseif ( WCJ_PRODUCT_GET_SALE_PRICE_FILTER == $_current_filter || 'woocommerce_variation_prices_sale_price' == $_current_filter || 'woocommerce_product_variation_get_sale_price' == $_current_filter ) {
					$sale_price_per_product = get_post_meta( $_product_id, '_' . 'wcj_multicurrency_per_product_sale_price_' . $this->get_current_currency_code(), true );
					return ( '' != $sale_price_per_product ) ? $sale_price_per_product : $price;
				}
			}
		}

		// Global
		if ( 1 != ( $currency_exchange_rate = $this->get_currency_exchange_rate( $this->get_current_currency_code() ) ) ) {
			$price = $price * $currency_exchange_rate;
			switch ( get_option( 'wcj_multicurrency_rounding', 'no_round' ) ) {
				case 'round':
					$price = round( $price, get_option( 'wcj_multicurrency_rounding_precision', absint( get_option( 'woocommerce_price_num_decimals', 2 ) ) ) );
					break;
				case 'round_up':
					$price = ceil( $price );
					break;
				case 'round_down':
					$price = floor( $price );
					break;
			}
			return $price;
		}

		// No changes
		return $price;
	}

	/**
	 * get_current_currency_code.
	 *
	 * @version 3.4.0
	 */
	function get_current_currency_code( $default_currency = '' ) {
		if ( null !== ( $session_value = wcj_session_get( 'wcj-currency' ) ) ) {
			return $session_value;
		} else {
			$module_roles = get_option( 'wcj_multicurrency_role_defaults_roles', '' );
			if ( ! empty( $module_roles ) ) {
				$current_user_role = wcj_get_current_user_first_role();
				if ( in_array( $current_user_role, $module_roles ) ) {
					$roles_default_currency = get_option( 'wcj_multicurrency_role_defaults_' . $current_user_role, '' );
					if ( '' != $roles_default_currency ) {
						return $roles_default_currency;
					}
				}
			}
		}
		return $default_currency;
	}

	/**
	 * change_currency_code.
	 *
	 * @version 2.5.0
	 */
	function change_currency_code( $currency ) {
		if ( $this->do_revert() ) {
			return $currency;
		}
		return $this->get_current_currency_code( $currency );
	}

	/**
	 * change_price_shipping.
	 *
	 * @version 3.2.0
	 */
	function change_price_shipping( $package_rates, $package ) {
		if ( $this->do_revert() ) {
			return $package_rates;
		}
		$currency_exchange_rate = $this->get_currency_exchange_rate( $this->get_current_currency_code() );
		return wcj_change_price_shipping_package_rates( $package_rates, $currency_exchange_rate );
	}

}

endif;

return new WCJ_Multicurrency();

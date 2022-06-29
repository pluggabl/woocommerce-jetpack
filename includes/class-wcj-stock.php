<?php
/**
 * Booster for WooCommerce - Module - Stock
 *
 * @version 5.3.8
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Stock' ) ) :
		/**
		 * WCJ_Stock.
		 *
		 * @version 5.3.8
		 * @since   2.8.0
		 */
	class WCJ_Stock extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.3.8
		 * @since   2.8.0
		 * @todo    (maybe) change `link_slug` to "woocommerce-products-stock" or "woocommerce-product-stock"
		 * @todo    customize "Available on backorder" message in cart
		 */
		public function __construct() {

			$this->id         = 'stock';
			$this->short_desc = __( 'Stock', 'woocommerce-jetpack' );
			$this->desc       = __( 'Products stock display management. Custom Stock HTML (Plus). Remove Stock Display (Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Products stock display management.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-stock';
			parent::__construct();

			if ( $this->is_enabled() ) {
				$this->is_custom_in_stock           = ( 'yes' === wcj_get_option( 'wcj_stock_custom_in_stock_section_enabled', 'no' ) );
				$this->is_custom_in_stock_text      = ( 'yes' === wcj_get_option( 'wcj_stock_custom_in_stock_enabled', 'no' ) );
				$this->is_custom_in_stock_class     = ( 'yes' === wcj_get_option( 'wcj_stock_custom_in_stock_class_enabled', 'no' ) );
				$this->is_custom_out_of_stock       = ( 'yes' === wcj_get_option( 'wcj_stock_custom_out_of_stock_section_enabled', 'no' ) );
				$this->is_custom_out_of_stock_text  = ( 'yes' === wcj_get_option( 'wcj_stock_custom_out_of_stock_enabled', 'no' ) );
				$this->is_custom_out_of_stock_class = ( 'yes' === wcj_get_option( 'wcj_stock_custom_out_of_stock_class_enabled', 'no' ) );
				$this->is_custom_backorder          = ( 'yes' === wcj_get_option( 'wcj_stock_custom_backorder_section_enabled', 'no' ) );
				$this->is_custom_backorder_text     = ( 'yes' === wcj_get_option( 'wcj_stock_custom_backorder_enabled', 'no' ) );
				$this->is_custom_backorder_class    = ( 'yes' === wcj_get_option( 'wcj_stock_custom_backorder_class_enabled', 'no' ) );
				if ( $this->is_custom_in_stock || $this->is_custom_out_of_stock || $this->is_custom_backorder ) {
					if ( $this->is_custom_in_stock_text || $this->is_custom_out_of_stock_text || $this->is_custom_backorder_text ) {
						add_filter( 'woocommerce_get_availability_text', array( $this, 'custom_availability_text' ), PHP_INT_MAX, 2 );
						add_filter( 'woocommerce_before_variations_form', array( $this, 'custom_availability_for_variable_product' ), PHP_INT_MAX, 2 );
					}
					if ( $this->is_custom_in_stock_class || $this->is_custom_out_of_stock_class || $this->is_custom_backorder_class ) {
						add_filter( 'woocommerce_get_availability_class', array( $this, 'custom_availability_class' ), PHP_INT_MAX, 2 );
					}
				}
				// Custom stock HTML.
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_stock_custom_stock_html_section_enabled', 'no' ) ) ) {
					if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
						add_filter( 'woocommerce_stock_html', array( $this, 'custom_stock_html_below_wc_3' ), PHP_INT_MAX, 3 );
					} else {
						add_filter( 'woocommerce_get_stock_html', array( $this, 'custom_stock_html' ), PHP_INT_MAX, 2 );
					}
				}
				// Remove stock display.
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_stock_remove_frontend_display_enabled', 'no' ) ) ) {
					add_filter( ( WCJ_IS_WC_VERSION_BELOW_3 ? 'woocommerce_stock_html' : 'woocommerce_get_stock_html' ), '__return_empty_string', PHP_INT_MAX );
				}
			}

		}
		/**
		 * Custom_availability_for_variable_product.
		 *
		 * @version 5.3.8
		 * @since   3.6.0
		 * @see     `wc_format_stock_for_display()`
		 * */
		public function custom_availability_for_variable_product() {
			global $product;
			$_product = $product;
			if ( $_product->is_type( 'variable' ) ) {
				$stock = get_post_meta( $_product->get_id(), '_manage_stock', true );
				if ( 'yes' === $stock ) {
					if ( ! $_product->is_in_stock() ) {
						if ( $this->is_custom_out_of_stock && $this->is_custom_out_of_stock_text ) {
							$variable = "<div class='stock'>" . do_shortcode( wcj_get_option( 'wcj_stock_custom_out_of_stock', '' ) ) . '</div>';
							echo wp_kses_post( $variable );
						}
					} elseif ( $_product->managing_stock() && $_product->is_on_backorder( 1 ) ) {
						if ( $this->is_custom_backorder && $this->is_custom_backorder_text ) {
							// Available on backorder.
							echo $_product->backorders_require_notification() ? do_shortcode( wcj_get_option( 'wcj_stock_custom_backorder', '' ) ) : '';
						}
					} elseif ( $_product->managing_stock() ) {
						if ( $this->is_custom_in_stock && $this->is_custom_in_stock_text ) {
							// In stock.
							$low_amount_text = wcj_get_option( 'wcj_stock_custom_in_stock_low_amount', '' );
							if (
								'' !== ( $low_amount_text ) &&
								'low_amount' === wcj_get_option( 'woocommerce_stock_format' ) && $_product->get_stock_quantity() <= wcj_get_option( 'woocommerce_notify_low_stock_amount' )
							) {
								// Only %s left in stock.
								$return = sprintf(
									do_shortcode( $low_amount_text ),
									wc_format_stock_quantity_for_display( $_product->get_stock_quantity(), $_product )
								);
							} else {
								// %s in stock && In stock.
								$return = sprintf(
									do_shortcode( wcj_get_option( 'wcj_stock_custom_in_stock', '' ) ),
									wc_format_stock_quantity_for_display( $_product->get_stock_quantity(), $_product )
								);
							}
							$can_be_backordered_text = wcj_get_option( 'wcj_stock_custom_in_stock_can_be_backordered', '' );
							if ( '' !== ( $can_be_backordered_text ) &&
								$_product->backorders_allowed() && $_product->backorders_require_notification()
							) {
								// (can be backordered).
								$return .= $can_be_backordered_text;
							}
							echo wp_kses_post( $return );
						}
					}
				}
			}

		}
		/**
		 * Custom_availability_text.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 * @see     `wc_format_stock_for_display()`
		 * @todo    `$this->is_custom_out_of_stock_text` - html tags in < WC3
		 * @todo    last `else` (i.e. `( ! $_product->managing_stock() )`
		 * @todo    (maybe) use `wc_format_stock_quantity_for_display( $stock_amount, $_product )` in `[wcj_product_stock_quantity]`
		 * @param string | bool  $availability defines the availability.
		 * @param string | array $_product defines the _product.
		 */
		public function custom_availability_text( $availability, $_product ) {
			if ( ! $_product->is_in_stock() ) {
				if ( $this->is_custom_out_of_stock && $this->is_custom_out_of_stock_text ) {
					// Out of stock.
					return do_shortcode( wcj_get_option( 'wcj_stock_custom_out_of_stock', '' ) );
				}
			} elseif ( $_product->managing_stock() && $_product->is_on_backorder( 1 ) ) {

				if ( $this->is_custom_backorder && $this->is_custom_backorder_text ) {
					// Available on backorder.

					return $_product->backorders_require_notification() ? do_shortcode( wcj_get_option( 'wcj_stock_custom_backorder', '' ) ) : '';
				}
			} elseif ( $_product->managing_stock() ) {
				if ( $this->is_custom_in_stock && $this->is_custom_in_stock_text ) {
					// In stock.
					$low_amount_text = wcj_get_option( 'wcj_stock_custom_in_stock_low_amount', '' );
					if (
					'' !== ( $low_amount_text ) &&
					'low_amount' === wcj_get_option( 'woocommerce_stock_format' ) && $_product->get_stock_quantity() <= wcj_get_option( 'woocommerce_notify_low_stock_amount' )
					) {
						// Only %s left in stock.
						$return = sprintf(
							do_shortcode( $low_amount_text ),
							wc_format_stock_quantity_for_display( $_product->get_stock_quantity(), $_product )
						);
					} else {
						// %s in stock && In stock.
						$return = sprintf(
							do_shortcode( wcj_get_option( 'wcj_stock_custom_in_stock', '' ) ),
							wc_format_stock_quantity_for_display( $_product->get_stock_quantity(), $_product )
						);
					}
					$can_be_backordered_text = wcj_get_option( 'wcj_stock_custom_in_stock_can_be_backordered', '' );
					if ( '' !== ( $can_be_backordered_text ) &&
					$_product->backorders_allowed() && $_product->backorders_require_notification()
					) {
						// (can be backordered).
						$return .= $can_be_backordered_text;
					}
					return $return;
				}
			} else {
				$availability = '';
			}
			return $availability;
		}

		/**
		 * Custom_availability_class.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 * @param string | array $class defines the class.
		 * @param array          $_product defines the _product.
		 */
		public function custom_availability_class( $class, $_product ) {
			if ( ! $_product->is_in_stock() ) {
				if ( $this->is_custom_out_of_stock && $this->is_custom_out_of_stock_class ) {
					// 'out-of-stock'.
					return wcj_get_option( 'wcj_stock_custom_out_of_stock_class', '' );
				}
			} elseif ( $_product->managing_stock() && $_product->is_on_backorder( 1 ) ) {
				if ( $this->is_custom_backorder && $this->is_custom_backorder_class ) {
					// 'available-on-backorder'.
					return wcj_get_option( 'wcj_stock_custom_backorder_class', '' );
				}
			} else {
				if ( $this->is_custom_in_stock && $this->is_custom_in_stock_class ) {
					// 'in-stock'.
					return wcj_get_option( 'wcj_stock_custom_in_stock_class', '' );
				}
			}
			return $class;
		}

		/**
		 * Custom_stock_html_below_wc_3.
		 *
		 * @version 3.5.0
		 * @since   3.4.0
		 * @param array | string $html defines the html.
		 * @param string | bool  $availability_availability defines the availability_availability.
		 * @param array          $product defines the product.
		 */
		public function custom_stock_html_below_wc_3( $html, $availability_availability, $product ) {
			$availability = $product->get_availability();
			$replacements = array(
				'%class%'        => ( ! empty( $availability['class'] ) ? $availability['class'] : '' ),
				'%availability%' => $availability_availability,
			);
			return do_shortcode(
				str_replace(
					array_keys( $replacements ),
					$replacements,
					apply_filters(
						'booster_option',
						'<p class="stock %class%">%availability%</p>',
						get_option( 'wcj_stock_custom_stock_html', '<p class="stock %class%">%availability%</p>' )
					)
				)
			);
		}

		/**
		 * Custom_stock_html.
		 *
		 * @version 3.5.0
		 * @since   3.4.0
		 * @param array $html defines the html.
		 * @param array $product defines the product.
		 */
		public function custom_stock_html( $html, $product ) {
			$availability = $product->get_availability();
			$replacements = array(
				'%class%'        => ( ! empty( $availability['class'] ) ? $availability['class'] : '' ),
				'%availability%' => ( ! empty( $availability['availability'] ) ? $availability['availability'] : '' ),
			);
			return do_shortcode(
				str_replace(
					array_keys( $replacements ),
					$replacements,
					apply_filters(
						'booster_option',
						'<p class="stock %class%">%availability%</p>',
						get_option( 'wcj_stock_custom_stock_html', '<p class="stock %class%">%availability%</p>' )
					)
				)
			);
		}

	}

endif;

return new WCJ_Stock();

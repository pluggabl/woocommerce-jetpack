<?php
/**
 * Booster for WooCommerce - Module - Product Add To Cart
 *
 * @version 5.6.8
 * @since   2.2.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Product_Add_To_Cart' ) ) :
	/**
	 * WCJ_Product_Add_To_Cart.
	 */
	class WCJ_Product_Add_To_Cart extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @todo    (maybe) move "Display radio buttons instead of drop box for variable products" to new module
		 * @todo    (maybe) rename to "Add to Cart Button (Options)"
		 */
		public function __construct() {

			$this->id         = 'product_add_to_cart';
			$this->short_desc = __( 'Add to Cart', 'woocommerce-jetpack' );
			// Desc.
			$this->desc = __( 'Set any local URL to redirect to on Add to Cart (Per product not allowed in free version).', 'woocommerce-jetpack' )
					. ' ' . __( 'Automatically add to cart on product visit.', 'woocommerce-jetpack' )
					. ' ' . __( 'Display radio buttons instead of drop box for variable products (Plus).', 'woocommerce-jetpack' )
					. ' ' . __( 'Disable quantity input.', 'woocommerce-jetpack' )
					. ' ' . __( 'Open external products on the add to cart in the new window.', 'woocommerce-jetpack' )
					. ' ' . __( 'Replace Add to Cart button on archives with button from single product pages.', 'woocommerce-jetpack' )
					. ' ' . __( 'Customize Add to Cart messages (Plus).', 'woocommerce-jetpack' );
			// Desc Pro.
			$this->desc_pro  = __( 'Set any local URL to redirect to on Add to Cart.', 'woocommerce-jetpack' )
						. ' ' . __( 'Automatically add to cart on product visit.', 'woocommerce-jetpack' )
						. ' ' . __( 'Display radio buttons instead of drop box for variable products.', 'woocommerce-jetpack' )
						. ' ' . __( 'Disable quantity input.', 'woocommerce-jetpack' )
						. ' ' . __( 'Open external products on the add to cart in the new window.', 'woocommerce-jetpack' )
						. ' ' . __( 'Replace Add to Cart button on archives with button from single product pages.', 'woocommerce-jetpack' )
						. ' ' . __( 'Customize Add to Cart messages.', 'woocommerce-jetpack' );
			$this->link_slug = 'woocommerce-product-add-to-cart';
			parent::__construct();

			if ( $this->is_enabled() ) {

				// Metaboxes.
				if (
				'yes' === wcj_get_option( 'wcj_add_to_cart_button_custom_loop_url_per_product_enabled', 'no' ) ||
				'yes' === wcj_get_option( 'wcj_add_to_cart_button_ajax_per_product_enabled', 'no' ) ||
				'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_add_to_cart_redirect_per_product_enabled', 'no' ) ) ||
				'per_product' === wcj_get_option( 'wcj_add_to_cart_on_visit_enabled', 'no' )
				) {
					add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
					add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				}

				// Customize "Continue shopping" or "View cart" messages.
				if (
				'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_product_add_to_cart_message_continue_shopping_enabled', 'no' ) ) ||
				'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_product_add_to_cart_message_view_cart_enabled', 'no' ) )
				) {
					add_filter( 'wc_add_to_cart_message_html', array( $this, 'change_add_to_cart_message_html' ), PHP_INT_MAX, 2 );
				}

				// Local Redirect.
				if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_redirect_enabled', 'no' ) || 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_add_to_cart_redirect_per_product_enabled', 'no' ) ) ) {
					add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'maybe_redirect_to_url' ), PHP_INT_MAX );
				}

				// Add to Cart on Visit.
				if ( 'no' !== wcj_get_option( 'wcj_add_to_cart_on_visit_enabled', 'no' ) ) {
					add_action( 'wp', array( $this, 'add_to_cart_on_visit' ), 98 );
				}

				// Variable Add to Cart Template.
				if ( 'yes' === apply_filters( 'booster_option', 'wcj', wcj_get_option( 'wcj_add_to_cart_variable_as_radio_enabled', 'no' ) ) ) {
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_variable_add_to_cart_scripts' ) );
					add_action( 'woocommerce_before_variations_form', array( $this, 'add_variations_radio_buttons_template' ) );
				}

				// Replace Add to Cart Loop with Single.
				if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_replace_loop_w_single_enabled', 'no' ) ) {
					add_action( 'init', array( $this, 'add_to_cart_replace_loop_w_single' ), PHP_INT_MAX );
				} elseif ( 'variable_only' === wcj_get_option( 'wcj_add_to_cart_replace_loop_w_single_enabled', 'no' ) ) {
					add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'add_to_cart_variable_replace_loop_w_single' ), PHP_INT_MAX );
				}

				// Quantity.
				if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_quantity_disable', 'no' ) || 'yes' === wcj_get_option( 'wcj_add_to_cart_quantity_disable_cart', 'no' ) ) {
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_disable_quantity_add_to_cart_script' ) );
				}
				// Quantity - Sold individually.
				if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_quantity_sold_individually_all', 'no' ) ) {
					add_filter( 'woocommerce_is_sold_individually', '__return_true', PHP_INT_MAX );
				}

				// Button per product Custom URL.
				if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_button_custom_loop_url_per_product_enabled', 'no' ) ) {
					add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'custom_add_to_cart_loop_url' ), PHP_INT_MAX, 2 );
				}
				// Button per product AJAX.
				if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_button_ajax_per_product_enabled', 'no' ) ) {
					add_filter( 'woocommerce_product_supports', array( $this, 'manage_add_to_cart_ajax' ), PHP_INT_MAX, 3 );
				}

				// External Products.
				if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_button_external_open_new_window_single', 'no' ) ) {
					$start_filter = ( WCJ_IS_WC_VERSION_BELOW_3_4_0 ? 'woocommerce_before_add_to_cart_button' : 'woocommerce_before_add_to_cart_form' );
					$end_filter   = ( WCJ_IS_WC_VERSION_BELOW_3_4_0 ? 'woocommerce_after_add_to_cart_button' : 'woocommerce_after_add_to_cart_form' );
					add_action( $start_filter, array( $this, 'replace_external_with_custom_add_to_cart_on_single_start' ), PHP_INT_MAX );
					add_action( $end_filter, array( $this, 'replace_external_with_custom_add_to_cart_on_single_end' ), PHP_INT_MAX );
				}
				if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_button_external_open_new_window_loop', 'no' ) ) {
					add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'replace_external_with_custom_add_to_cart_in_loop' ), PHP_INT_MAX );
				}

				// Reposition Add to Cart button.
				if ( 'yes' === wcj_get_option( 'wcj_product_add_to_cart_button_position_enabled', 'no' ) ) {
					// Single product pages.
					if ( 'yes' === wcj_get_option( 'wcj_product_add_to_cart_button_position_single_enabled', 'no' ) ) {
						add_action( 'init', array( $this, 'reposition_add_to_cart_button_single' ), PHP_INT_MAX );
					}
					// Archives.
					if ( 'yes' === wcj_get_option( 'wcj_product_add_to_cart_button_position_loop_enabled', 'no' ) ) {
						add_action( 'init', array( $this, 'reposition_add_to_cart_button_loop' ), PHP_INT_MAX );
					}
				}
			}
		}

		/**
		 * Reposition_add_to_cart_button_loop.
		 *
		 * @version 3.3.0
		 * @since   3.3.0
		 * @todo    (maybe) add option to duplicate the button (i.e. not replace)
		 */
		public function reposition_add_to_cart_button_loop() {
			remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
			add_action(
				get_option( 'wcj_product_add_to_cart_button_position_hook_loop', 'woocommerce_after_shop_loop_item' ),
				'woocommerce_template_loop_add_to_cart',
				get_option( 'wcj_product_add_to_cart_button_position_loop', 10 )
			);
		}

		/**
		 * Reposition_add_to_cart_button_single.
		 *
		 * @version 3.3.0
		 * @since   3.3.0
		 * @todo    (maybe) add option to duplicate the button (i.e. not replace)
		 */
		public function reposition_add_to_cart_button_single() {
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
			add_action(
				get_option( 'wcj_product_add_to_cart_button_position_hook_single', 'woocommerce_single_product_summary' ),
				'woocommerce_template_single_add_to_cart',
				get_option( 'wcj_product_add_to_cart_button_position_single', 30 )
			);
		}

		/**
		 * Change_add_to_cart_message_html.
		 *
		 * @version 2.8.0
		 * @since   2.8.0
		 * @see     `wc_add_to_cart_message()` in `wc-cart-functions.php`
		 * @todo    (maybe) product specific messages: foreach ( $products as $product_id => $qty ) ...
		 * @todo    (maybe) if ( WCJ_IS_WC_VERSION_BELOW_3 ) apply_filters( 'wc_add_to_cart_message' ) with 2 params: $message, $product_id
		 * @param string $message defines the message.
		 * @param array  $products defines the products.
		 */
		public function change_add_to_cart_message_html( $message, $products ) {

			if ( 'yes' === wcj_get_option( 'woocommerce_cart_redirect_after_add' ) && 'no' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_product_add_to_cart_message_continue_shopping_enabled', 'no' ) ) ) {
				return $message;
			}
			if ( 'yes' !== wcj_get_option( 'woocommerce_cart_redirect_after_add' ) && 'no' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_product_add_to_cart_message_view_cart_enabled', 'no' ) ) ) {
				return $message;
			}

			$titles = array();
			$count  = 0;

			foreach ( $products as $product_id => $qty ) {
				/* translators: %s: translation added */
				$titles[] = ( $qty > 1 ? absint( $qty ) . ' &times; ' : '' ) . sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'woocommerce' ), wp_strip_all_tags( get_the_title( $product_id ) ) );
				$count   += $qty;
			}

			$titles = array_filter( $titles );
			/* translators: %s: translation added */
			$added_text = sprintf( _n( '%s has been added to your cart.', '%s have been added to your cart.', $count, 'woocommerce' ), wc_format_list_of_items( $titles ) );

			if ( 'yes' === wcj_get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				$return_to = apply_filters( 'woocommerce_continue_shopping_redirect', wc_get_raw_referer() ? wp_validate_redirect( wc_get_raw_referer(), false ) : wc_get_page_permalink( 'shop' ) );
				$message   = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', esc_url( $return_to ), esc_html( wcj_get_option( 'wcj_product_add_to_cart_message_continue_shopping_text', __( 'Continue shopping', 'woocommerce' ) ) ), esc_html( $added_text ) );
			} else {
				$message = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', esc_url( wc_get_page_permalink( 'cart' ) ), esc_html( wcj_get_option( 'wcj_product_add_to_cart_message_view_cart_text', __( 'View cart', 'woocommerce' ) ) ), esc_html( $added_text ) );
			}

			return $message;
		}

		/**
		 * Add_to_cart_variable_replace_loop_w_single.
		 *
		 * @version 2.6.0
		 * @since   2.6.0
		 * @param string $link defines the link.
		 */
		public function add_to_cart_variable_replace_loop_w_single( $link ) {
			global $product;
			if ( $product->is_type( 'variable' ) ) {
				do_action( 'woocommerce_variable_add_to_cart' );
				return '';
			}
			return $link;
		}

		/**
		 * Add_to_cart_replace_loop_w_single.
		 *
		 * @version 2.6.0
		 * @since   2.6.0
		 */
		public function add_to_cart_replace_loop_w_single() {
			remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
			add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_single_add_to_cart', 30 );
		}

		/**
		 * Replace_external_with_custom_add_to_cart_on_single_start.
		 *
		 * @version 2.5.3
		 * @since   2.5.3
		 */
		public function replace_external_with_custom_add_to_cart_on_single_start() {
			global $product;
			if ( $product->is_type( 'external' ) ) {
				ob_start();
			}
		}

		/**
		 * Replace_external_with_custom_add_to_cart_on_single_end.
		 *
		 * @version 5.6.2
		 * @since   2.5.3
		 */
		public function replace_external_with_custom_add_to_cart_on_single_end() {
			global $product;
			if ( $product->is_type( 'external' ) ) {
				$button_html = ob_get_contents();
				ob_end_clean();
				echo wp_kses_post(
					WCJ_IS_WC_VERSION_BELOW_3_4_0 ?
					( str_replace( '<a href=', '<a target="_blank" href=', $button_html ) ) : ( str_replace( '<form ', '<form target="_blank" ', $button_html ) )
				);
			}
		}

		/**
		 * Replace_external_with_custom_add_to_cart_in_loop.
		 *
		 * @version 3.2.4
		 * @since   2.5.3
		 * @param string $link_html defines the link_html.
		 */
		public function replace_external_with_custom_add_to_cart_in_loop( $link_html ) {
			global $product;
			if ( $product->is_type( 'external' ) ) {
				if ( false !== strpos( $link_html, '<a rel=' ) ) {
					$link_html = str_replace( '<a rel=', '<a target="_blank" rel=', $link_html );
				} else {
					$link_html = str_replace( '<a href=', '<a target="_blank" href=', $link_html );
				}
			}
			return $link_html;
		}

		/**
		 * Manage_add_to_cart_ajax.
		 *
		 * @version 2.5.6
		 * @since   2.5.6
		 * @param string $supports defines the supports.
		 * @param string $feature defines the feature.
		 * @param array  $_product defines the _product.
		 */
		public function manage_add_to_cart_ajax( $supports, $feature, $_product ) {
			$value = get_post_meta( get_the_ID(), '_wcj_add_to_cart_button_ajax_disable', true );
			if ( 'ajax_add_to_cart' === $feature && 0 !== get_the_ID() && 'as_shop_default' !== ( $value ) ) {
				return ( 'yes' === $value ) ? false : true;
			}
			return $supports;
		}

		/**
		 * Custom_add_to_cart_loop_url.
		 *
		 * @version 5.6.2
		 * @since   2.5.6
		 * @param string $url defines the url.
		 * @param array  $_product defines the _product.
		 */
		public function custom_add_to_cart_loop_url( $url, $_product ) {

			$custom_url = get_post_meta( get_the_ID(), '_wcj_add_to_cart_button_loop_custom_url', true );
			if ( 0 !== get_the_ID() && '' !== ( $custom_url ) ) {
				return $custom_url;
			}
			return $url;
		}

		/**
		 * Enqueue_disable_quantity_add_to_cart_script.
		 *
		 * @version 5.6.2
		 * @since   2.5.2
		 * @todo    add "hide" (not just disable) option
		 */
		public function enqueue_disable_quantity_add_to_cart_script() {
			if (
			( 'yes' === wcj_get_option( 'wcj_add_to_cart_quantity_disable', 'no' ) && is_product() ) ||
			( 'yes' === wcj_get_option( 'wcj_add_to_cart_quantity_disable_cart', 'no' ) && is_cart() )
			) {
				wp_enqueue_script( 'wcj-disable-quantity', wcj_plugin_url() . '/includes/js/wcj-disable-quantity.js', array( 'jquery' ), w_c_j()->version, true );
			}
		}

		/**
		 * Enqueue_variable_add_to_cart_scripts.
		 *
		 * @version 2.9.0
		 * @since   2.4.8
		 */
		public function enqueue_variable_add_to_cart_scripts() {
			wp_enqueue_script( 'wcj-variations', wcj_plugin_url() . '/includes/js/wcj-variations-frontend.js', array( 'jquery' ), w_c_j()->version, true );
		}

		/**
		 * Adds radio buttons template for variations
		 *
		 * @version 5.6.1
		 * @since   4.3.0
		 * @see woocommerce_variable_add_to_cart()
		 */
		public function add_variations_radio_buttons_template() {
			global $product;
			$get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
			wc_get_template(
				'wcj-radio-for-variations.php',
				array(
					'available_variations' => $get_variations ? $product->get_available_variations() : false,
					'attributes'           => $product->get_variation_attributes(),
					'selected_attributes'  => $product->get_default_attributes(),
				),
				'',
				WCJ_FREE_PLUGIN_PATH . '/includes/templates/'
			);
			?>
		<style>
			table.variations{display:none}
		</style>
			<?php
		}

		/**
		 * Maybe_redirect_to_url.
		 *
		 * @version 5.6.8
		 * @param string     $url defines the url.
		 * @param bool | int $product_id defines the product_id.
		 */
		public function maybe_redirect_to_url( $url, $product_id = false ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_add_to_cart_redirect_per_product_enabled', 'no' ) ) && ( $product_id || isset( $_REQUEST['add-to-cart'] ) ) ) {
				if ( ! $product_id ) {
					$product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_REQUEST['add-to-cart'] ) );
				}
				if ( 'yes' === get_post_meta( $product_id, '_wcj_add_to_cart_redirect_enabled', true ) ) {
					$redirect_url = get_post_meta( $product_id, '_wcj_add_to_cart_redirect_url', true );
					if ( '' === $redirect_url ) {
						$redirect_url = wc_get_checkout_url();
					}
					return $redirect_url;
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification
			if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_redirect_enabled', 'no' ) ) {
				$redirect_url = wcj_get_option( 'wcj_add_to_cart_redirect_url', '' );
				if ( '' === $redirect_url ) {
					$redirect_url = wc_get_checkout_url();
				}
				return $redirect_url;
			}
			return $url;
		}

		/**
		 * Add item to cart on visit.
		 *
		 * @version 5.6.2
		 * @todo    (maybe) optionally add product every time page is visited (instead of only once)
		 */
		public function add_to_cart_on_visit() {

			global $product;
			$product_id = get_the_ID();
			if ( ! is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) && is_product() && ( $product_id ) ) {

				$_productid = wc_get_product( get_the_ID() );

				if ( $_productid->is_type( 'variable' ) ) {

					$default_attributes = $_productid->get_default_attributes();

					foreach ( $_productid->get_available_variations() as $variation_values ) {

						foreach ( $variation_values['attributes'] as $key => $attribute_value ) {

							$attribute_name = str_replace( 'attribute_', '', $key );
							$default_value  = $_productid->get_variation_default_attribute( $attribute_name );
							if ( $default_value === $attribute_value ) {

								$product_id = $variation_values['variation_id'];

							} else {

								$product_id = $product_id;

							}
						}
					}
				}

				if ( 'per_product' === wcj_get_option( 'wcj_add_to_cart_on_visit_enabled', 'no' ) ) {
					if ( 'yes' !== get_post_meta( $product_id, '_wcj_add_to_cart_on_visit_enabled', true ) ) {
						return;
					}
				}
				if ( isset( WC()->cart ) ) {
						// Check if product already in cart.

					if ( count( WC()->cart->get_cart() ) > 0 ) {
						foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
							$_product = $values['data'];

							// OLD CONDITION - if ( wcj_get_product_id_or_variation_parent_id( $_product ) == $product_id ).
							if ( wcj_get_product_id_or_variation_id( $_product ) === $product_id ) {

								return;
							}
						}

						$was_added_to_cart = WC()->cart->add_to_cart( $product_id );

					} else {

						$was_added_to_cart = WC()->cart->add_to_cart( $product_id );
					}
						// Maybe perform add to cart redirect.
					if ( false !== $was_added_to_cart && (
					'yes' === wcj_get_option( 'wcj_add_to_cart_redirect_enabled', 'no' ) ||
					'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_add_to_cart_redirect_per_product_enabled', 'no' ) )
					) ) {
						$redirect_url = $this->maybe_redirect_to_url( false, $product_id );
						if ( $redirect_url ) {
							if ( wp_safe_redirect( $redirect_url ) ) {
								exit;
							}
						}
					}
				}
			}
		}
	}

endif;

return new WCJ_Product_Add_To_Cart();

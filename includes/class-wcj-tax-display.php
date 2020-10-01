<?php
/**
 * Booster for WooCommerce - Module - Tax Display
 *
 * @version 4.6.0
 * @since   3.2.4
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Tax_Display' ) ) :

class WCJ_Tax_Display extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 4.1.0
	 * @since   3.2.4
	 */
	function __construct() {

		$this->id         = 'tax_display';
		$this->short_desc = __( 'Tax Display', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce tax display.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-tax-display';
		parent::__construct();

		if ( $this->is_enabled() ) {

			// Tax Incl./Excl. by product/category
			if ( 'yes' === wcj_get_option( 'wcj_product_listings_display_taxes_by_products_enabled', 'no' ) ) {
				add_filter( 'option_woocommerce_tax_display_shop', array( $this, 'tax_display_by_product' ), PHP_INT_MAX );
			}

			// Tax Incl./Excl. by user role
			if ( 'yes' === wcj_get_option( 'wcj_product_listings_display_taxes_by_user_role_enabled', 'no' ) ) {
				add_filter( 'option_woocommerce_tax_display_shop', array( $this, 'tax_display_by_user_role' ), PHP_INT_MAX );
				add_filter( 'option_woocommerce_tax_display_cart', array( $this, 'tax_display_by_user_role' ), PHP_INT_MAX );
			}

			// Tax toggle
			if ( 'yes' === wcj_get_option( 'wcj_tax_display_toggle_enabled', 'no' ) ) {
				add_action( 'init',                                array( $this, 'tax_display_toggle_param' ), PHP_INT_MAX );
				add_filter( 'option_woocommerce_tax_display_shop', array( $this, 'tax_display_toggle' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * tax_display_toggle_param.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function tax_display_toggle_param() {
		wcj_session_maybe_start();
		if ( isset( $_REQUEST['wcj_button_toggle_tax_display'] ) ) {
			$current_value = ( '' == ( $session_value = wcj_session_get( 'wcj_toggle_tax_display' ) ) ? wcj_get_option( 'woocommerce_tax_display_shop', 'excl' ) : $session_value );
			wcj_session_set( 'wcj_toggle_tax_display', ( 'incl' === $current_value ? 'excl' : 'incl' ) );
		}
	}

	/**
	 * tax_display_toggle.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @todo    [dev] widget
	 * @todo    [dev] (maybe) floating button or at least give CSS instructions ($)
	 * @todo    [dev] (maybe) position near the price or at least give "Product Info" instructions
	 */
	function tax_display_toggle( $value ) {
		if ( ! wcj_is_frontend() ) {
			return $value;
		}
		if ( '' != ( $session_value = wcj_session_get( 'wcj_toggle_tax_display' ) ) ) {
			return $session_value;
		}
		return $value;
	}

	/**
	 * tax_display_by_user_role.
	 *
	 * @version 4.6.0
	 * @since   3.2.0
	 */
	function tax_display_by_user_role( $value ) {
		if ( ! wcj_is_frontend() ) {
			return $value;
		}
		if ( '' != ( $display_taxes_by_user_role_roles = wcj_get_option( 'wcj_product_listings_display_taxes_by_user_role_roles', '' ) ) ) {
			$current_user_roles = wcj_get_current_user_all_roles();
			foreach ( $current_user_roles as $current_user_first_role ) {
				if ( in_array( $current_user_first_role, $display_taxes_by_user_role_roles ) ) {
					$option_name = 'option_woocommerce_tax_display_shop' === current_filter() ? 'wcj_product_listings_display_taxes_by_user_role_' . $current_user_first_role : 'wcj_product_listings_display_taxes_on_cart_by_user_role_' . $current_user_first_role;
					if ( 'no_changes' != ( $tax_display = wcj_get_option( $option_name, 'no_changes' ) ) ) {
						return $tax_display;
					}
				}
			}
		}
		return $value;
	}

	/**
	 * tax_display_by_product.
	 *
	 * @version 3.2.4
	 * @since   2.5.5
	 */
	function tax_display_by_product( $value ) {
		if ( ! wcj_is_frontend() ) {
			return $value;
		}
		$product_id = get_the_ID();
		if ( 'product' === get_post_type( $product_id ) ) {
			$products_incl_tax     = wcj_get_option( 'wcj_product_listings_display_taxes_products_incl_tax', '' );
			$products_excl_tax     = wcj_get_option( 'wcj_product_listings_display_taxes_products_excl_tax', '' );
			$product_cats_incl_tax = wcj_get_option( 'wcj_product_listings_display_taxes_product_cats_incl_tax', '' );
			$product_cats_excl_tax = wcj_get_option( 'wcj_product_listings_display_taxes_product_cats_excl_tax', '' );
			if ( '' != $products_incl_tax || '' != $products_incl_tax || '' != $products_incl_tax || '' != $products_incl_tax ) {
				// Products
				if ( ! empty( $products_incl_tax ) ) {
					if ( in_array( $product_id, $products_incl_tax ) ) {
						return 'incl';
					}
				}
				if ( ! empty( $products_excl_tax ) ) {
					if ( in_array( $product_id, $products_excl_tax ) ) {
						return 'excl';
					}
				}
				// Categories
				$product_categories = get_the_terms( $product_id, 'product_cat' );
				if ( ! empty( $product_cats_incl_tax ) ) {
					if ( ! empty( $product_categories ) ) {
						foreach ( $product_categories as $product_category ) {
							if ( in_array( $product_category->term_id, $product_cats_incl_tax ) ) {
								return 'incl';
							}
						}
					}
				}
				if ( ! empty( $product_cats_excl_tax ) ) {
					if ( ! empty( $product_categories ) ) {
						foreach ( $product_categories as $product_category ) {
							if ( in_array( $product_category->term_id, $product_cats_excl_tax ) ) {
								return 'excl';
							}
						}
					}
				}
			}
		}
		return $value;
	}

}

endif;

return new WCJ_Tax_Display();

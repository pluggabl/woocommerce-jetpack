<?php
/**
 * Booster for WooCommerce - Module - Shipping
 *
 * @version 2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping' ) ) :

class WCJ_Shipping extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @todo    "custom shipping" to new module
	 */
	function __construct() {

		$this->id         = 'shipping';
		$this->short_desc = __( 'Shipping', 'woocommerce-jetpack' );
		$this->desc       =
			__( 'Add multiple custom shipping methods to WooCommerce.', 'woocommerce-jetpack' ) . ' ' .
			__( 'Add descriptions and icons to shipping methods on frontend.', 'woocommerce-jetpack') . ' ' .
			__( 'Hide WooCommerce shipping when free is available.', 'woocommerce-jetpack');
		$this->link_slug  = 'woocommerce-shipping';
		parent::__construct();

		if ( $this->is_enabled() ) {

			// Custom Shipping
			include_once( 'shipping/class-wc-shipping-wcj-custom.php' );
			if ( 'yes' === get_option( 'wcj_shipping_custom_shipping_w_zones_enabled', 'no' ) ) {
				include_once( 'shipping/class-wc-shipping-wcj-custom-with-shipping-zones.php' );
			}

			// Hide if free is available
			if ( 'yes' === get_option( 'wcj_shipping_hide_if_free_available_all', 'no' ) ) {
				add_filter( 'woocommerce_package_rates', array( $this, 'hide_shipping_when_free_is_available' ), 10, 2 );
			}
			add_filter( 'woocommerce_shipping_settings', array( $this, 'add_hide_shipping_if_free_available_fields' ), 100 );

			// Shipping Descriptions
			if ( 'yes' === get_option( 'wcj_shipping_description_enabled', 'no' ) ) {
				add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'shipping_description' ), PHP_INT_MAX, 2 );
			}

			// Shipping Icons
			if ( 'yes' === get_option( 'wcj_shipping_icons_enabled', 'no' ) ) {
				add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'shipping_icon' ), PHP_INT_MAX, 2 );
			}

			// Free shipping by product
			if ( 'yes' === get_option( 'wcj_shipping_free_shipping_by_product_enabled', 'no' ) ) {
				add_filter( 'woocommerce_shipping_free_shipping_is_available', array( $this, 'free_shipping_by_product' ), PHP_INT_MAX, 2 );
			}
		}
	}

	/**
	 * free_shipping_by_product.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 * @return  bool
	 */
	function free_shipping_by_product( $is_available, $package ) {
		$free_shipping_granting_products = get_option( 'wcj_shipping_free_shipping_by_product_products', '' );
		if ( empty( $free_shipping_granting_products ) ) {
			return $is_available;
		}
		$free_shipping_granting_products_type = apply_filters( 'booster_get_option', 'all', get_option( 'wcj_shipping_free_shipping_by_product_type', 'all' ) );
		$package_grants_free_shipping = false;
		foreach( $package['contents'] as $item ) {
			if ( in_array( $item['product_id'], $free_shipping_granting_products ) ) {
				if ( 'at_least_one' === $free_shipping_granting_products_type ) {
					return true;
				} elseif ( ! $package_grants_free_shipping ) {
					$package_grants_free_shipping = true;
				}
			} else {
				if ( 'all' === $free_shipping_granting_products_type ) {
					return $is_available;
				}
			}
		}
		return ( $package_grants_free_shipping ) ? true : $is_available;
	}

	/**
	 * shipping_icon.
	 *
	 * @version 2.6.0
	 * @since   2.5.6
	 */
	function shipping_icon( $label, $method ) {
		$shipping_icons_visibility = apply_filters( 'booster_get_option', 'both', get_option( 'wcj_shipping_icons_visibility', 'both' ) );
		if ( 'checkout_only' === $shipping_icons_visibility && is_cart() ) {
			return $label;
		}
		if ( 'cart_only' === $shipping_icons_visibility && is_checkout() ) {
			return $label;
		}
		if ( '' != ( $icon_url = get_option( 'wcj_shipping_icon_' . $method->method_id, '' ) ) ) {
			$style_html = ( '' != ( $style = get_option( 'wcj_shipping_icons_style', 'display:inline;' ) ) ) ?  'style="' . $style . '" ' : '';
			$img = '<img ' . $style_html . 'class="wcj_shipping_icon" id="wcj_shipping_icon_' . $method->method_id . '" src="' . $icon_url . '">';
			$label = ( 'before' === get_option( 'wcj_shipping_icons_position', 'before' ) ) ? $img . ' ' . $label : $label . ' ' . $img;
		}
		return $label;
	}

	/**
	 * shipping_description.
	 *
	 * @version 2.6.0
	 * @since   2.5.6
	 */
	function shipping_description( $label, $method ) {
		$shipping_descriptions_visibility = apply_filters( 'booster_get_option', 'both', get_option( 'wcj_shipping_descriptions_visibility', 'both' ) );
		if ( 'checkout_only' === $shipping_descriptions_visibility && is_cart() ) {
			return $label;
		}
		if ( 'cart_only' === $shipping_descriptions_visibility && is_checkout() ) {
			return $label;
		}
		if ( '' != ( $desc = get_option( 'wcj_shipping_description_' . $method->method_id, '' ) ) ) {
			$label .= $desc;
		}
		return $label;
	}

	/**
	 * hide_shipping_when_free_is_available.
	 *
	 * @version 2.5.3
	 * @todo    if ( 'yes' === get_option( 'wcj_shipping_hide_if_free_available_local_delivery' ) ) { unset( $rates['local_delivery'] ); }
	 */
	function hide_shipping_when_free_is_available( $rates, $package ) {
		$free_shipping_rates = array();
		$is_free_shipping_available = false;
		foreach ( $rates as $rate_key => $rate ) {
			if ( false !== strpos( $rate_key, 'free_shipping' ) ) {
				$is_free_shipping_available = true;
				$free_shipping_rates[ $rate_key ] = $rate;
			}
		}
		return ( $is_free_shipping_available ) ? $free_shipping_rates : $rates;
	}

	/**
	 * add_hide_shipping_if_free_available_fields.
	 *
	 * @version 2.5.3
	 */
	function add_hide_shipping_if_free_available_fields( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			$updated_settings[] = $section;
			if ( isset( $section['id'] ) && 'woocommerce_ship_to_destination' === $section['id'] ) {
				/* $updated_settings[] = array(
					'title'    => __( 'Booster: Hide shipping', 'woocommerce-jetpack' ),
					'desc'     => __( 'Hide local delivery when free is available', 'woocommerce-jetpack' ),
					'desc_tip' => __( '', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_hide_if_free_available_local_delivery',
					'default'  => 'no',
					'type'     => 'checkbox',
					'checkboxgroup' => 'start',
				); */
				$updated_settings[] = array(
					'title'    => __( 'Booster: Hide shipping', 'woocommerce-jetpack' ),
					'desc'     => __( 'Hide all when free is available', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_hide_if_free_available_all',
					'default'  => 'no',
					'type'     => 'checkbox',
					/* 'checkboxgroup' => 'end', */
				);
			}
		}
		return $updated_settings;
	}

}

endif;

return new WCJ_Shipping();

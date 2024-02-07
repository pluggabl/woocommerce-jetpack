<?php
/**
 * Booster for WooCommerce - Module - Shipping Descriptions
 *
 * @version 7.1.6
 * @since   3.4.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Shipping_Descriptions' ) ) :
		/**
		 * WCJ_Shipping_Descriptions.
		 *
		 * @version 7.1.6
		 * @since   3.4.0
		 */
	class WCJ_Shipping_Descriptions extends WCJ_Module {

		/**
		 * The module shipping_descriptions_position
		 *
		 * @var varchar $shipping_descriptions_position Module shipping_descriptions_position.
		 */
		public $shipping_descriptions_position;

		/**
		 * The module shipping_descriptions_visibility
		 *
		 * @var varchar $shipping_descriptions_visibility Module shipping_descriptions_visibility.
		 */
		public $shipping_descriptions_visibility;

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   3.4.0
		 */
		public function __construct() {

			$this->id         = 'shipping_description';
			$this->short_desc = __( 'Shipping Descriptions', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add descriptions to shipping methods on frontend. Description visibility (Plus). Description position (Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add descriptions to shipping methods on frontend.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-shipping-descriptions';
			parent::__construct();

			if ( $this->is_enabled() ) {
				$this->shipping_descriptions_visibility = apply_filters( 'booster_option', 'both', wcj_get_option( 'wcj_shipping_descriptions_visibility', 'both' ) );
				$this->shipping_descriptions_position   = apply_filters( 'booster_option', 'after', wcj_get_option( 'wcj_shipping_descriptions_position', 'after' ) );
				add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'shipping_description' ), PHP_INT_MAX, 2 );
			}
		}

		/**
		 * Shipping_description.
		 *
		 * @version 3.6.0
		 * @since   5.6.2
		 * @todo    `shipping_descriptions_position` on per method basis
		 * @param String $label Get shipping label.
		 * @param String $method Get shipping method.
		 */
		public function shipping_description( $label, $method ) {
			if ( 'checkout_only' === $this->shipping_descriptions_visibility && is_cart() ) {
				return $label;
			}
			if ( 'cart_only' === $this->shipping_descriptions_visibility && is_checkout() ) {
				return $label;
			}
			$use_shipping_instances = ( 'yes' === wcj_get_option( 'wcj_shipping_descriptions_use_shipping_instance', 'no' ) );
			$option_id              = 'wcj_shipping_description_' . ( $use_shipping_instances ? 'instance_' . $method->instance_id : $method->method_id );
			$desc                   = wcj_get_option( $option_id, '' );
			if ( '' !== ( $desc ) ) {
				switch ( $this->shipping_descriptions_position ) {
					case 'before':
						return $desc . $label;
					case 'instead':
						return $desc;
					default: // after.
						return $label . $desc;
				}
			} else {
				return $label;
			}
		}

	}

endif;

return new WCJ_Shipping_Descriptions();

<?php
/**
 * Booster for WooCommerce - Module - Shipping Methods by Products
 *
 * @version 7.0.0
 * @since   3.2.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Shipping_By_Products' ) ) :
	/**
	 * WCJ_Shipping_By_Products.
	 *
	 * @version 5.2.0
	 * @since   3.2.0
	 */
	class WCJ_Shipping_By_Products extends WCJ_Module_Shipping_By_Condition {
		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   3.2.0
		 * @todo    (maybe) add customer messages on cart and checkout pages (if some shipping method is not available)
		 */
		public function __construct() {

			$this->id         = 'shipping_by_products';
			$this->short_desc = __( 'Shipping Methods by Products', 'woocommerce-jetpack' );
			$this->desc       = __( 'Set products, product categories, tags or shipping classes to include/exclude for shipping methods to show up (Free shipping available in Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Set products, product categories, tags or shipping classes to include/exclude for shipping methods to show up.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-shipping-methods-by-products';

			$this->condition_options = array(
				'products'     => array(
					'title' => __( 'Products', 'woocommerce-jetpack' ),
					'desc'  => __( 'Shipping methods by <strong>products</strong>.', 'woocommerce-jetpack' ),
				),
				'product_cats' => array(
					'title' => __( 'Product Categories', 'woocommerce-jetpack' ),
					'desc'  => __( 'Shipping methods by <strong>products categories</strong>.', 'woocommerce-jetpack' ),
				),
				'product_tags' => array(
					'title' => __( 'Product Tags', 'woocommerce-jetpack' ),
					'desc'  => __( 'Shipping methods by <strong>products tags</strong>.', 'woocommerce-jetpack' ),
				),
				'classes'      => array(
					'title' => __( 'Product Shipping Classes', 'woocommerce-jetpack' ),
					'desc'  => '',
				),
			);

			parent::__construct();
		}

		/**
		 * Check_for_data.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 * @param string $cart_instead_of_package defines the cart_instead_of_package.
		 * @param array  $package defines the package.
		 */
		public function check_for_data( $cart_instead_of_package, $package ) {
			if ( $cart_instead_of_package ) {
				if ( ! isset( WC()->cart ) || WC()->cart->is_empty() ) {
					return false;
				}
			} else {
				if ( ! isset( $package['contents'] ) ) {
					return false;
				}
			}
			return true;
		}

		/**
		 * Get_items.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 * @param string $cart_instead_of_package defines the cart_instead_of_package.
		 * @param array  $package defines the package.
		 */
		public function get_items( $cart_instead_of_package, $package ) {
			return ( $cart_instead_of_package ? WC()->cart->get_cart() : $package['contents'] );
		}

		/**
		 * Check.
		 *
		 * @version 7.0.0
		 * @since   3.2.0
		 * @todo    variations in `classes`
		 * @todo    check for `if ( is_object( $product ) && is_callable( array( $product, 'get_shipping_class_id' ) ) ) { ... }`
		 * @todo    check for `isset( $item['variation_id'] )`, `isset( $item['product_id'] )` and `isset( $item['data'] )` before using it
		 * @todo    (maybe) if needed, prepare `$products_variations` earlier (and only once)
		 * @param string $options_id defines the options_id.
		 * @param array  $values defines the values.
		 * @param string $include_or_exclude defines the include_or_exclude.
		 * @param array  $package defines the package.
		 */
		public function check( $options_id, $values, $include_or_exclude, $package ) {
			$cart_instead_of_package = ( 'yes' === wcj_get_option( 'wcj_shipping_by_' . $options_id . '_cart_not_package', 'yes' ) );
			if ( ! $this->check_for_data( $cart_instead_of_package, $package ) ) {
				return true;
			}
			$do_add_variations = ( 'yes' === wcj_get_option( 'wcj_shipping_by_' . $options_id . '_add_variations_enabled', 'no' ) );
			if ( 'products' === $options_id && ( $do_add_variations ) ) {
				$products_variations = array();
				foreach ( $values as $_product_id ) {
					$_product = wc_get_product( $_product_id );
					if ( $_product->is_type( 'variable' ) ) {
						$products_variations = array_merge( $products_variations, $_product->get_children() );
					} else {
						$products_variations[] = $_product_id;
					}
				}
				$values = array_unique( $products_variations );
			}
			$validate_all_for_include = ( 'include' === $include_or_exclude && 'yes' === wcj_get_option( 'wcj_shipping_by_' . $options_id . '_validate_all_enabled', 'no' ) );
			foreach ( $this->get_items( $cart_instead_of_package, $package ) as $item ) {
				switch ( $options_id ) {
					case 'products':
						$_product_id = ( $do_add_variations && 0 !== $item['variation_id'] && '0' !== $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
						if ( $validate_all_for_include && ! in_array( (string) $_product_id, $values, true ) ) {
							return false;
						} elseif ( ! $validate_all_for_include && in_array( (string) $_product_id, $values, true ) ) {
							return true;
						}
						break;
					case 'product_cats':
					case 'product_tags':
						$product_terms = get_the_terms( $item['product_id'], ( 'product_cats' === $options_id ? 'product_cat' : 'product_tag' ) );
						if ( empty( $product_terms ) ) {
							if ( $validate_all_for_include ) {
								return false;
							} else {
								break;
							}
						}
						foreach ( $product_terms as $product_term ) {
							if ( $validate_all_for_include && ! in_array( (string) $product_term->term_id, $values, true ) ) {
								return false;
							} elseif ( ! $validate_all_for_include && in_array( (string) $product_term->term_id, $values, true ) ) {
								return true;
							}
						}
						break;
					case 'classes':
						$product                = $item['data'];
						$product_shipping_class = $product->get_shipping_class_id();
						if ( $validate_all_for_include && ! in_array( (string) $product_shipping_class, $values, true ) ) {
							return false;
						} elseif ( ! $validate_all_for_include && in_array( (string) $product_shipping_class, $values, true ) ) {
							return true;
						}
						break;
				}
			}
			return $validate_all_for_include;
		}

		/**
		 * Get_condition_options.
		 *
		 * @version 3.9.0
		 * @since   3.2.0
		 * @param string $options_id defines the options_id.
		 */
		public function get_condition_options( $options_id ) {
			switch ( $options_id ) {
				case 'products':
					return wcj_get_products( array(), 'any', 1024, ( 'yes' === wcj_get_option( 'wcj_shipping_by_' . $options_id . '_add_variations_enabled', 'no' ) ) );
				case 'product_cats':
					return wcj_get_terms( 'product_cat' );
				case 'product_tags':
					return wcj_get_terms( 'product_tag' );
				case 'classes':
					$wc_shipping              = WC_Shipping::instance();
					$shipping_classes_terms   = $wc_shipping->get_shipping_classes();
					$shipping_classes_options = array( 0 => __( 'No shipping class', 'woocommerce' ) );
					foreach ( $shipping_classes_terms as $shipping_classes_term ) {
						$shipping_classes_options[ $shipping_classes_term->term_id ] = $shipping_classes_term->name;
					}
					return $shipping_classes_options;
			}
		}

		/**
		 * Get_additional_section_settings.
		 *
		 * @version 3.6.0
		 * @since   3.2.1
		 * @param string $options_id defines the options_id.
		 */
		public function get_additional_section_settings( $options_id ) {
			$return = array(
				array(
					'title'    => __( '"Include" Options', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Enable this checkbox if you want all products in cart to be valid (instead of at least one).', 'woocommerce-jetpack' ),
					'desc'     => __( 'Validate all', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_by_' . $options_id . '_validate_all_enabled',
					'type'     => 'checkbox',
					'default'  => 'no',
				),
				array(
					'title'    => __( 'Cart instead of Package', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Enable this checkbox if you want to check all cart products instead of package.', 'woocommerce-jetpack' ),
					'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_by_' . $options_id . '_cart_not_package',
					'type'     => 'checkbox',
					'default'  => 'yes',
				),
			);
			if ( 'products' === $options_id ) {
				$return[] = array(
					'title'    => __( 'Add Products Variations', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Enable this checkbox if you want to add products variations to the products list.', 'woocommerce-jetpack' ) . ' ' .
						__( 'Save changes after enabling this option.', 'woocommerce-jetpack' ),
					'desc'     => __( 'Add', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_by_' . $options_id . '_add_variations_enabled',
					'type'     => 'checkbox',
					'default'  => 'no',
				);
			}
			return $return;
		}

	}

endif;

return new WCJ_Shipping_By_Products();

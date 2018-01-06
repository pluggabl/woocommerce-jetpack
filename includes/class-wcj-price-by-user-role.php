<?php
/**
 * Booster for WooCommerce - Module - Price by User Role
 *
 * @version 3.2.2
 * @since   2.5.0
 * @author  Algoritmika Ltd.
 * @todo    Fix "Make Empty Price" option for variable products
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Price_By_User_Role' ) ) :

class WCJ_Price_By_User_Role extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.2.2
	 * @since   2.5.0
	 */
	function __construct() {

		$this->id         = 'price_by_user_role';
		$this->short_desc = __( 'Price by User Role', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display WooCommerce products prices by user roles.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-price-by-user-role';
		parent::__construct();

		if ( $this->is_enabled() ) {
			$this->price_hooks_priority = wcj_get_module_price_hooks_priority( 'price_by_user_role' );
			if ( 'yes' === get_option( 'wcj_price_by_user_role_per_product_enabled', 'yes' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				if ( 'no' === get_option( 'wcj_price_by_user_role_for_bots_disabled', 'no' ) || ! wcj_is_bot() ) {
					wcj_add_change_price_hooks( $this, $this->price_hooks_priority );
					if ( ( $this->disable_for_regular_price = ( 'yes' === get_option( 'wcj_price_by_user_role_disable_for_regular_price', 'no' ) ) ) ) {
						add_filter( 'woocommerce_product_is_on_sale', array( $this, 'maybe_make_on_sale' ), PHP_INT_MAX, 2 );
					}
				}
			}
			add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_value' ), PHP_INT_MAX, 3 );
			add_action( 'admin_notices',           array( $this, 'admin_notices' ) );
		}
	}

	/**
	 * maybe_make_on_sale.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 */
	function maybe_make_on_sale( $on_sale, $product ) {
		return ( $product->get_price() < $product->get_regular_price() ? true : $on_sale );
	}

	/**
	 * save_meta_box_value.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function save_meta_box_value( $option_value, $option_name, $module_id ) {
		if ( true === apply_filters( 'booster_option', false, true ) ) {
			return $option_value;
		}
		if ( 'no' === $option_value ) {
			return $option_value;
		}
		if ( $this->id === $module_id && 'wcj_price_by_user_role_per_product_settings_enabled' === $option_name ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_key'       => '_' . 'wcj_price_by_user_role_per_product_settings_enabled',
				'meta_value'     => 'yes',
				'post__not_in'   => array( get_the_ID() ),
			);
			$loop = new WP_Query( $args );
			$c = $loop->found_posts + 1;
			if ( $c >= 2 ) {
				add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
				return 'no';
			}
		}
		return $option_value;
	}

	/**
	 * add_notice_query_var.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function add_notice_query_var( $location ) {
		remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
		return add_query_arg( array( 'wcj_product_price_by_user_role_admin_notice' => true ), $location );
	}

	/**
	 * admin_notices.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function admin_notices() {
		if ( ! isset( $_GET['wcj_product_price_by_user_role_admin_notice'] ) ) {
			return;
		}
		?><div class="error"><p><?php
			echo '<div class="message">'
				. __( 'Booster: Free plugin\'s version is limited to only one price by user role per products settings product enabled at a time. You will need to get <a href="http://booster.io/plus/" target="_blank">Booster Plus</a> to add unlimited number of price by user role per product settings products.', 'woocommerce-jetpack' )
				. '</div>';
		?></p></div><?php
	}

	/**
	 * change_price_shipping.
	 *
	 * @version 3.2.0
	 * @since   2.5.0
	 */
	function change_price_shipping( $package_rates, $package ) {
		if ( 'yes' === get_option( 'wcj_price_by_user_role_shipping_enabled', 'no' ) ) {
			$current_user_role = wcj_get_current_user_first_role();
			$koef = get_option( 'wcj_price_by_user_role_' . $current_user_role, 1 );
			return wcj_change_price_shipping_package_rates( $package_rates, $koef );
		}
		return $package_rates;
	}

	/**
	 * change_price_grouped.
	 *
	 * @version 2.7.0
	 * @since   2.5.0
	 */
	function change_price_grouped( $price, $qty, $_product ) {
		if ( $_product->is_type( 'grouped' ) ) {
			if ( 'yes' === get_option( 'wcj_price_by_user_role_per_product_enabled', 'yes' ) ) {
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
	 * change_price.
	 *
	 * @version 3.2.2
	 * @since   2.5.0
	 */
	function change_price( $price, $_product ) {

		$current_user_role = wcj_get_current_user_first_role();
		$_current_filter   = current_filter();

		// Per product
		if ( 'yes' === get_option( 'wcj_price_by_user_role_per_product_enabled', 'yes' ) ) {
			if ( 'yes' === get_post_meta( wcj_get_product_id_or_variation_parent_id( $_product ), '_' . 'wcj_price_by_user_role_per_product_settings_enabled', true ) ) {
				$_product_id = wcj_get_product_id( $_product );
				if ( 'yes' === get_post_meta( $_product_id, '_' . 'wcj_price_by_user_role_empty_price_' . $current_user_role, true ) ) {
					return '';
				}
				if ( 'multiplier' === get_option( 'wcj_price_by_user_role_per_product_type', 'fixed' ) ) {
					if ( '' !== ( $multiplier_per_product = get_post_meta( $_product_id, '_' . 'wcj_price_by_user_role_multiplier_' . $current_user_role, true ) ) ) {
						// Maybe disable for regular price hooks
						if ( $this->disable_for_regular_price && in_array( $_current_filter, array(
							WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER,
							'woocommerce_variation_prices_regular_price',
							'woocommerce_product_variation_get_regular_price'
						) ) ) {
							return $price;
						}
						return ( '' === $price ) ? $price : $price * $multiplier_per_product;
					}
				} elseif ( '' != ( $regular_price_per_product = get_post_meta( $_product_id, '_' . 'wcj_price_by_user_role_regular_price_' . $current_user_role, true ) ) ) {
					if ( in_array( $_current_filter, array(
						'woocommerce_get_price_including_tax',
						'woocommerce_get_price_excluding_tax'
					) ) ) {
						return wcj_get_product_display_price( $_product );
					} elseif ( in_array( $_current_filter, array(
						WCJ_PRODUCT_GET_PRICE_FILTER,
						'woocommerce_variation_prices_price',
						'woocommerce_product_variation_get_price'
					) ) ) {
						$sale_price_per_product = get_post_meta( $_product_id, '_' . 'wcj_price_by_user_role_sale_price_' . $current_user_role, true );
						$return = ( '' != $sale_price_per_product && $sale_price_per_product < $regular_price_per_product ) ? $sale_price_per_product : $regular_price_per_product;
						return apply_filters( 'wcj_price_by_user_role_get_price', $return, $_product );
					} elseif ( in_array( $_current_filter, array(
						WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER,
						'woocommerce_variation_prices_regular_price',
						'woocommerce_product_variation_get_regular_price'
					) ) ) {
						return $regular_price_per_product;
					} elseif ( in_array( $_current_filter, array(
						WCJ_PRODUCT_GET_SALE_PRICE_FILTER,
						'woocommerce_variation_prices_sale_price',
						'woocommerce_product_variation_get_sale_price'
					) ) ) {
						$sale_price_per_product = get_post_meta( $_product_id, '_' . 'wcj_price_by_user_role_sale_price_' . $current_user_role, true );
						return ( '' != $sale_price_per_product ) ? $sale_price_per_product : $price;
					}
				}
			}
		}

		// Maybe disable for products on sale
		if ( 'yes' === get_option( 'wcj_price_by_user_role_disable_for_products_on_sale', 'no' ) ) {
			wcj_remove_change_price_hooks( $this, $this->price_hooks_priority );
			if ( $_product && $_product->is_on_sale() ) {
				wcj_add_change_price_hooks( $this, $this->price_hooks_priority );
				return $price;
			} else {
				wcj_add_change_price_hooks( $this, $this->price_hooks_priority );
			}
		}

		// Maybe disable for regular price hooks
		if ( $this->disable_for_regular_price && in_array( $_current_filter, array(
			WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER,
			'woocommerce_variation_prices_regular_price',
			'woocommerce_product_variation_get_regular_price'
		) ) ) {
			return $price;
		}

		// By category
		$categories = apply_filters( 'booster_option', '', get_option( 'wcj_price_by_user_role_categories', '' ) );
		if ( ! empty( $categories ) ) {
			$product_categories = get_the_terms( wcj_get_product_id_or_variation_parent_id( $_product ), 'product_cat' );
			if ( ! empty( $product_categories ) ) {
				foreach ( $product_categories as $product_category ) {
					foreach ( $categories as $category ) {
						if ( $product_category->term_id == $category ) {
							if ( 'yes' === get_option( 'wcj_price_by_user_role_cat_empty_price_' . $category . '_' . $current_user_role, 'no' ) ) {
								return '';
							}
							$koef_category = get_option( 'wcj_price_by_user_role_cat_' . $category . '_' . $current_user_role, 1 );
							return ( '' === $price ) ? $price : $price * $koef_category;
						}
					}
				}
			}
		}

		// Global
		if ( 'yes' === get_option( 'wcj_price_by_user_role_empty_price_' . $current_user_role, 'no' ) ) {
			return '';
		}
		if ( 1 != ( $koef = get_option( 'wcj_price_by_user_role_' . $current_user_role, 1 ) ) ) {
			return ( '' === $price ) ? $price : $price * $koef;
		}

		// No changes
		return $price;
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 3.2.0
	 * @since   2.5.0
	 * @todo    only hash categories that is relevant to the product
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$user_role = wcj_get_current_user_first_role();
		$categories = apply_filters( 'booster_option', '', get_option( 'wcj_price_by_user_role_categories', '' ) );
		$price_hash['wcj_user_role'] = array(
			$user_role,
			get_option( 'wcj_price_by_user_role_' . $user_role, 1 ),
			get_option( 'wcj_price_by_user_role_empty_price_' . $user_role, 'no' ),
			get_option( 'wcj_price_by_user_role_per_product_enabled', 'yes' ),
			get_option( 'wcj_price_by_user_role_per_product_type', 'fixed' ),
			get_option( 'wcj_price_by_user_role_disable_for_products_on_sale', 'no' ),
			$this->disable_for_regular_price,
			$categories,
		);
		if ( ! empty( $categories ) ) {
			foreach ( $categories as $category ) {
				$price_hash['wcj_user_role'][] = get_option( 'wcj_price_by_user_role_cat_empty_price_' . $category . '_' . $user_role, 'no' );
				$price_hash['wcj_user_role'][] = get_option( 'wcj_price_by_user_role_cat_' . $category . '_' . $user_role, 1 );
			}
		}
		return $price_hash;
	}

}

endif;

return new WCJ_Price_By_User_Role();

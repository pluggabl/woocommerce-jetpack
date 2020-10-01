<?php
/**
 * Booster for WooCommerce - Module - Price based on User Role
 *
 * @version 5.2.0
 * @since   2.5.0
 * @author  Pluggabl LLC.
 * @todo    Fix "Make Empty Price" option for variable products
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Price_By_User_Role' ) ) :

class WCJ_Price_By_User_Role extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.3.0
	 * @since   2.5.0
	 */
	function __construct() {

		$this->id         = 'price_by_user_role';
		$this->short_desc = __( 'Price based on User Role', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display products prices by user roles. Price based on User Role by Products Categories or Tags (Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Display products prices by user roles. Price based on User Role by Products Categories or Tags.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-price-by-user-role';
		parent::__construct();

		if ( $this->is_enabled() ) {
			$this->price_hooks_priority = wcj_get_module_price_hooks_priority( 'price_by_user_role' );
			if ( 'yes' === wcj_get_option( 'wcj_price_by_user_role_per_product_enabled', 'yes' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}
			if ( wcj_is_frontend() ) {
				if ( 'no' === wcj_get_option( 'wcj_price_by_user_role_for_bots_disabled', 'no' ) || ! wcj_is_bot() ) {
					wcj_add_change_price_hooks( $this, $this->price_hooks_priority );
					if ( ( $this->disable_for_regular_price = ( 'yes' === wcj_get_option( 'wcj_price_by_user_role_disable_for_regular_price', 'no' ) ) ) ) {
						add_filter( 'woocommerce_product_is_on_sale', array( $this, 'maybe_make_on_sale' ), PHP_INT_MAX, 2 );
					}
				}
			}
			add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_value' ), PHP_INT_MAX, 3 );
			add_action( 'admin_notices',           array( $this, 'admin_notices' ) );
			// Admin settings - "copy price" buttons
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script' ) );
			add_filter( 'woocommerce_hide_invisible_variations', array( $this, 'show_empty_price_variations' ) );
			add_action( 'woocommerce_before_single_variation', array( $this, 'remove_single_variation_hooks' ) );

			// WooCommerce Product Bundles compatibility
			add_filter( 'wcj_price_by_user_role_do_change_price', array( $this, 'change_bundle_product_price' ), 10, 3 );
		}
	}

	/**
	 * change_bundle_product_price.
	 *
	 * @version 5.3.0
	 * @since   5.3.0
	 *
	 * @param $change
	 * @param $price
	 * @param $_product
	 *
	 * @return bool
	 */
	function change_bundle_product_price( $change, $price, $_product ) {
		if (
			'yes' !== wcj_get_option( 'wcj_price_by_user_role_compatibility_wc_product_bundles', 'no' )
			|| ! function_exists( 'wc_pb_get_bundled_product_map' )
			|| ! $_product
			|| $price > 0
			|| empty( wc_pb_get_bundled_product_map( $_product ) )
		) {
			return $change;
		}
		$change = false;
		return $change;
	}

	/**
	 * remove_single_variation_hooks.
	 *
	 * @version 4.6.0
	 * @since   4.6.0
	 */
	function remove_single_variation_hooks() {
		global $product;
		if ( ! empty( $product->get_price() ) ) {
			return;
		}
		if ( 'yes' === wcj_get_option( 'wcj_price_by_user_role_remove_single_variation', 'no' ) ) {
			remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation', apply_filters( 'wcj_remove_single_variation_priority', 10 ) );
		}
		if ( 'yes' === wcj_get_option( 'wcj_price_by_user_role_remove_add_to_cart_btn', 'no' ) ) {
			remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', apply_filters( 'wcj_remove_single_variation_add_to_cart_button_priority', 20 ) );
		}
	}

	/**
	 * show_empty_price_variations.
	 *
	 * @version 4.6.0
	 * @since   4.6.0
	 *
	 * @param $hide
	 *
	 * @return bool
	 */
	function show_empty_price_variations( $hide ) {
		if (
			'no' === wcj_get_option( 'wcj_price_by_user_role_show_empty_price_variations', 'no' )
		) {
			return $hide;
		}
		$hide = false;
		return $hide;
	}

	/**
	 * get_admin_settings_copy_link.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function get_admin_settings_copy_link( $action, $regular_or_sale, $source_product, $source_role, $dest_roles, $dest_products ) {
		switch ( $action ) {
			case 'copy_to_roles_and_variations':
				$dashicon = 'links';
				$title    = __( 'user roles & variations', 'woocommerce-jetpack' );
				break;
			case 'copy_to_variations':
				$dashicon = 'page';
				$title    = __( 'variations', 'woocommerce-jetpack' );
				break;
			default: // 'copy_to_roles'
				$dashicon = 'users';
				$title    = __( 'user roles', 'woocommerce-jetpack' );
				break;
		}
		$data_array = array(
			'action'         => $action,
			'price'          => $regular_or_sale,
			'source_product' => $source_product,
			'source_role'    => $source_role,
			'dest_roles'     => $dest_roles,
			'dest_products'  => $dest_products,
		);
		return '<a href="#" class="wcj-copy-price" wcj-copy-data=\'' . json_encode( $data_array ) . '\'>' .
			'<span class="dashicons dashicons-admin-' . $dashicon . '" style="font-size:small;float:right;" title="' .
				sprintf( __( 'Copy price to all %s', 'woocommerce-jetpack' ), $title ) . '">' .
			'</span>' .
		'</a>';
}

	/**
	 * enqueue_admin_script.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function enqueue_admin_script() {
		wp_enqueue_script( 'wcj-price-by-user-role-admin', wcj_plugin_url() . '/includes/js/wcj-price-by-user-role-admin.js', array( 'jquery' ), WCJ()->version, true );
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
				. __( 'Booster: Free plugin\'s version is limited to only one price by user role per products settings product enabled at a time. You will need to get <a href="https://booster.io/plus/" target="_blank">Booster Plus</a> to add unlimited number of price by user role per product settings products.', 'woocommerce-jetpack' )
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
		if ( 'yes' === wcj_get_option( 'wcj_price_by_user_role_shipping_enabled', 'no' ) ) {
			$current_user_role = wcj_get_current_user_first_role();
			$koef = wcj_get_option( 'wcj_price_by_user_role_' . $current_user_role, 1 );
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
			if ( 'yes' === wcj_get_option( 'wcj_price_by_user_role_per_product_enabled', 'yes' ) ) {
				foreach ( $_product->get_children() as $child_id ) {
					$the_price   = get_post_meta( $child_id, '_price', true );
					$the_product = wc_get_product( $child_id );
					$the_price   = wcj_get_product_display_price( $the_product, $the_price, 1 );
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
	 * @version 4.7.1
	 * @since   2.5.0
	 * @todo    (maybe) add "enable compound multipliers" option
	 * @todo    (maybe) check for `( '' === $price )` only once, at the beginning of the function (instead of comparing before each `return`)
	 * @todo    (maybe) code refactoring (cats/tags)
	 */
	function change_price( $price, $_product ) {

		$current_user_role = wcj_get_current_user_first_role();
		$_current_filter   = current_filter();

		if ( ! apply_filters( 'wcj_price_by_user_role_do_change_price', true, $price, $_product, $current_user_role, $_current_filter ) ) {
			return $price;
		}

		if ( 'yes' === wcj_get_option( 'wcj_price_by_user_role_check_for_product_changes_price', 'no' ) && $_product ) {
			$product_changes = $_product->get_changes();
			if ( ! empty( $product_changes ) && isset( $product_changes['price'] ) ) {
				return $price;
			}
		}

		// Per product
		if ( 'yes' === wcj_get_option( 'wcj_price_by_user_role_per_product_enabled', 'yes' ) ) {
			if ( 'yes' === get_post_meta( wcj_maybe_get_product_id_wpml( wcj_get_product_id_or_variation_parent_id( $_product ) ), '_' . 'wcj_price_by_user_role_per_product_settings_enabled', true ) ) {
				$_product_id = wcj_maybe_get_product_id_wpml( wcj_get_product_id( $_product ) );
				if ( 'yes' === get_post_meta( $_product_id, '_' . 'wcj_price_by_user_role_empty_price_' . $current_user_role, true ) ) {
					return '';
				}
				if ( 'multiplier' === wcj_get_option( 'wcj_price_by_user_role_per_product_type', 'fixed' ) ) {
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
		if ( 'yes' === wcj_get_option( 'wcj_price_by_user_role_disable_for_products_on_sale', 'no' ) ) {
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
		$categories = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_price_by_user_role_categories', '' ) );
		if ( ! empty( $categories ) ) {
			$product_categories = get_the_terms( wcj_maybe_get_product_id_wpml( wcj_get_product_id_or_variation_parent_id( $_product ) ), 'product_cat' );
			if ( ! empty( $product_categories ) ) {
				foreach ( $product_categories as $product_category ) {
					foreach ( $categories as $category ) {
						if (
							$product_category->term_id == $category ||
							(
								'yes' === wcj_get_option( 'wcj_price_by_user_role_check_child_categories', 'no' ) &&
								term_is_ancestor_of( $category, $product_category->term_id, 'product_cat' )
							)
						) {
							if ( 'yes' === wcj_get_option( 'wcj_price_by_user_role_cat_empty_price_' . $category . '_' . $current_user_role, 'no' ) ) {
								return '';
							}
							if ( ( $koef_category = wcj_get_option( 'wcj_price_by_user_role_cat_' . $category . '_' . $current_user_role, -1 ) ) >= 0 ) {
								return ( '' === $price ) ? $price : $price * (float) $koef_category;
							}
						}
					}
				}
			}
		}

		// By tag
		$tags = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_price_by_user_role_tags', '' ) );
		if ( ! empty( $tags ) ) {
			$product_tags = get_the_terms( wcj_maybe_get_product_id_wpml( wcj_get_product_id_or_variation_parent_id( $_product ) ), 'product_tag' );
			if ( ! empty( $product_tags ) ) {
				foreach ( $product_tags as $product_tag ) {
					foreach ( $tags as $tag ) {
						if ( $product_tag->term_id == $tag ) {
							if ( 'yes' === wcj_get_option( 'wcj_price_by_user_role_tag_empty_price_' . $tag . '_' . $current_user_role, 'no' ) ) {
								return '';
							}
							if ( ( $koef_tag = wcj_get_option( 'wcj_price_by_user_role_tag_' . $tag . '_' . $current_user_role, -1 ) ) >= 0 ) {
								return ( '' === $price ) ? $price : $price * (float) $koef_tag;
							}
						}
					}
				}
			}
		}

		// Global
		if ( 'yes' === wcj_get_option( 'wcj_price_by_user_role_empty_price_' . $current_user_role, 'no' ) ) {
			return '';
		}
		if ( 1 != ( $koef = wcj_get_option( 'wcj_price_by_user_role_' . $current_user_role, 1 ) ) ) {
			return ( '' === $price ) ? $price : $price * (float) $koef;
		}

		// No changes
		return $price;
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 3.6.0
	 * @since   2.5.0
	 * @todo    only hash categories that is relevant to the product
	 * @todo    (maybe) code refactoring (cats/tags)
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$user_role  = wcj_get_current_user_first_role();
		$categories = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_price_by_user_role_categories', '' ) );
		$tags       = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_price_by_user_role_tags', '' ) );
		$price_hash['wcj_user_role'] = array(
			$user_role,
			get_option( 'wcj_price_by_user_role_' . $user_role, 1 ),
			get_option( 'wcj_price_by_user_role_empty_price_' . $user_role, 'no' ),
			get_option( 'wcj_price_by_user_role_per_product_enabled', 'yes' ),
			get_option( 'wcj_price_by_user_role_per_product_type', 'fixed' ),
			get_option( 'wcj_price_by_user_role_disable_for_products_on_sale', 'no' ),
			$this->disable_for_regular_price,
			$categories,
			$tags,
		);
		if ( ! empty( $categories ) ) {
			foreach ( $categories as $category ) {
				$price_hash['wcj_user_role'][] = wcj_get_option( 'wcj_price_by_user_role_cat_empty_price_' . $category . '_' . $user_role, 'no' );
				$price_hash['wcj_user_role'][] = wcj_get_option( 'wcj_price_by_user_role_cat_' . $category . '_' . $user_role, -1 );
			}
		}
		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				$price_hash['wcj_user_role'][] = wcj_get_option( 'wcj_price_by_user_role_tag_empty_price_' . $tag . '_' . $user_role, 'no' );
				$price_hash['wcj_user_role'][] = wcj_get_option( 'wcj_price_by_user_role_tag_' . $tag . '_' . $user_role, -1 );
			}
		}
		return $price_hash;
	}

}

endif;

return new WCJ_Price_By_User_Role();

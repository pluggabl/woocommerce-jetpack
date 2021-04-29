<?php
/**
 * Booster for WooCommerce - Module - Product Addons
 *
 * @version 5.4.0
 * @since   2.5.3
 * @author  Pluggabl LLC.
 * @todo    admin order view (names)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Addons' ) ) :

class WCJ_Product_Addons extends WCJ_Module {


	/**
	 * Constructor.
	 *
	 * @version 5.4.0
	 * @since   2.5.3
	 * @todo    (maybe) add "in progress" ajax message
	 * @todo    (maybe) for variable products - show addons only if variation is selected (e.g. move to addons from `woocommerce_before_add_to_cart_button` to variation description)
	 * @todo    (maybe) add `product_addons` to `wcj_get_module_price_hooks_priority()`
	 */
	function __construct() {

		$this->id         = 'product_addons';
		$this->short_desc = __( 'Product Addons', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add (paid/free/discount) addons to products (1 addon allowed in free version).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Add (paid/free/discount) addons to products.<br>
							You can add <code>item_product_addons</code> into columns parameter of <code>[wcj_order_items_table]</code> to show selected product addon in pdf.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-addons';

		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( 'yes' === wcj_get_option( 'wcj_product_addons_per_product_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes',          array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product',       array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_value' ), PHP_INT_MAX, 3 );
				add_action( 'admin_notices',           array( $this, 'admin_notices' ) );
				$this->co = 'wcj_product_addons_per_product_settings_enabled';
			}
			if ( wcj_is_frontend() ) {
				if ( 'yes' === wcj_get_option( 'wcj_product_addons_ajax_enabled', 'no' ) ) {
					// Scripts
					add_action( 'wp_enqueue_scripts',                         array( $this, 'enqueue_scripts' ) );
					add_action( 'wp_ajax_product_addons_price_change',        array( $this, 'price_change_ajax' ) );
					add_action( 'wp_ajax_nopriv_product_addons_price_change', array( $this, 'price_change_ajax' ) );
				}
				// Single Page
				$position = wcj_get_option( 'wcj_product_addons_position', 'woocommerce_before_add_to_cart_button' );
				$priority = wcj_get_option( 'wcj_product_addons_position_priority', 0 );
				add_action( $position,                                    array( $this, 'add_addons_to_frontend' ), ( 0 == $priority ? PHP_INT_MAX : $priority ) );
				// Add to cart
				add_filter( 'woocommerce_add_cart_item_data',             array( $this, 'add_addons_price_to_cart_item_data' ), PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_add_cart_item',                  array( $this, 'add_addons_price_to_cart_item' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_get_cart_item_from_session',     array( $this, 'get_cart_item_addons_price_from_session' ), PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_add_to_cart_validation',         array( $this, 'validate_on_add_to_cart' ), PHP_INT_MAX, 4 );
				// Prices
				add_filter( WCJ_PRODUCT_GET_PRICE_FILTER,                 array( $this, 'change_price' ), wcj_get_module_price_hooks_priority( 'product_addons' ), 2 );
				add_filter( 'woocommerce_product_variation_get_price',    array( $this, 'change_price' ), wcj_get_module_price_hooks_priority( 'product_addons' ), 2 );
				// Show details at cart, order details, emails
				add_filter( 'woocommerce_cart_item_name',                 array( $this, 'add_info_to_cart_item_name' ), PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_order_item_name',                array( $this, 'add_info_to_order_item_name' ), PHP_INT_MAX, 2 );
				if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
					add_action( 'woocommerce_add_order_item_meta',        array( $this, 'add_info_to_order_item_meta' ), PHP_INT_MAX, 3 );
				} else {
					add_action( 'woocommerce_new_order_item',             array( $this, 'add_info_to_order_item_meta_wc3' ), PHP_INT_MAX, 3 );
				}
			}
			if ( is_admin() ) {
				if ( 'yes' === wcj_get_option( 'wcj_product_addons_hide_on_admin_order_page', 'no' ) ) {
					add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_addons_in_admin_order' ), PHP_INT_MAX );
				}
				// Format meta on admin order
				add_filter( 'woocommerce_order_item_get_formatted_meta_data', array( $this, 'format_meta_data' ), 20, 2 );
			}
			// Addons quantity
			$qty_triggers = wcj_get_option( 'wcj_product_addons_qty_decrease_triggers', '' );
			if ( ! empty( $qty_triggers ) ) {
				if ( in_array( 'woocommerce_new_order', $qty_triggers ) ) {
					$qty_triggers = array_merge( $qty_triggers, array(
						'woocommerce_api_create_order',
						'woocommerce_cli_create_order',
						'kco_before_confirm_order',
						'woocommerce_checkout_order_processed',
					) );
				}
				foreach ( $qty_triggers as $qty_trigger ) {
					add_action( $qty_trigger, array( $this, 'maybe_reduce_addons_qty' ) );
				}
			}

			// Export and import '_wcj_product_addons_per_product_enable_by_variation_%' meta
			add_filter( 'woocommerce_product_export_meta_value', array( $this, 'export_enable_by_variation_meta' ), 10, 4 );
			add_filter( 'woocommerce_product_importer_parsed_data', array( $this, 'import_enable_by_variation_meta' ), 10, 2 );
		}
	}

	/**
	 * import_enable_by_variation_meta.
	 *
	 * @version 4.8.0
	 * @since   4.7.0
	 *
	 * @param $data
	 * @param $raw_data
	 *
	 * @return mixed
	 */
	function import_enable_by_variation_meta( $data, $raw_data ) {
		if (
			'no' === wcj_get_option( 'wcj_product_addons_enable_by_variation_export_import', 'no' ) ||
			! isset( $data['meta_data'] ) ||
			empty( $data['meta_data'] )
		) {
			return $data;
		}
		foreach ( $data['meta_data'] as $key => $meta_data ) {
			if ( false !== strpos( $meta_data['key'], '_wcj_product_addons_per_product_enable_by_variation_' ) ) {
				$new_value = array_filter( preg_split( "/\n|\r\n?/", $data['meta_data'][ $key ]['value'] ) );
				if ( ! empty( $new_value ) ) {
					$data['meta_data'][ $key ]['value'] = $new_value;
				}
			}
		}
		return $data;
	}

	/**
	 * export_enable_by_variation_meta.
	 *
	 * @version 4.8.0
	 * @since   4.7.0
	 *
	 * @param $value
	 * @param $meta
	 * @param $product
	 * @param $row
	 *
	 * @return string
	 */
	function export_enable_by_variation_meta( $value, $meta, $product, $row ) {
		if (
			'no' === wcj_get_option( 'wcj_product_addons_enable_by_variation_export_import', 'no' ) ||
			false === strpos( $meta->get_data()['key'], '_wcj_product_addons_per_product_enable_by_variation_' )
		) {
			return $value;
		}
		$value = implode( "\n", (array) $value );
		return $value;
	}

	/**
	 * format_meta_data.
	 *
	 * @version 4.5.0
	 * @since   4.5.0
	 *
	 * @param $meta_data
	 * @param $item
	 *
	 * @return mixed
	 */
	function format_meta_data( $meta_data, $item ) {
		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $meta_data;
		}
		$addons  = $this->get_product_addons( $item->get_product_id() );
		$product = $item->get_product_id();
		foreach ( $addons as $addon ) {
			$label_key_search = wp_list_filter( $meta_data, array( 'key' => '_' . $addon['label_key'] ) );
			$price_key_search = wp_list_filter( $meta_data, array( 'key' => '_' . $addon['price_key'] ) );
			if ( count( $label_key_search ) > 0 ) {
				reset( $label_key_search );
				reset( $price_key_search );
				$label_key   = key( $label_key_search );
				$price_key   = key( $price_key_search );
				$addon_price = $this->maybe_convert_currency( $meta_data[ $price_key ]->value, $product );
				$final_price = wc_price( $addon_price );
				// Change metadata values
				$meta_data[ $label_key ]->display_key   = $meta_data[ $label_key ]->value;
				$meta_data[ $label_key ]->display_value = $final_price;
				unset( $meta_data[ $price_key ] );
			}
		}
		return $meta_data;
	}

	/**
	 * maybe_reduce_addons_qty.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 * @todo    (maybe) $order->add_order_note
	 */
	function maybe_reduce_addons_qty( $order_id ) {
		if ( $order = wc_get_order( $order_id ) ) {
			if ( 'yes' !== get_post_meta( $order_id, '_' . 'wcj_product_addons_qty_reduced', true ) ) {
				if ( sizeof( $order->get_items() ) > 0 ) {
					foreach ( $order->get_items() as $item ) {
						if ( $item->is_type( 'line_item' ) && ( $product = $item->get_product() ) ) {
							$product_id              = wcj_get_product_id_or_variation_parent_id( $product );
							$product_qty             = $item->get_quantity();
							$global_addon_key        = '_wcj_product_all_products_addons_label_';
							$global_addon_key_length = strlen( $global_addon_key );
							$local_addon_key         = '_wcj_product_per_product_addons_label_';
							$local_addon_key_length  = strlen( $local_addon_key );
							foreach ( $item->get_meta_data() as $meta_data ) {
								$meta = $meta_data->get_data();
								if ( $global_addon_key === substr( $meta['key'], 0, $global_addon_key_length ) ) {
									$i       = substr( $meta['key'], $global_addon_key_length );
									$qty_key = 'wcj_product_addons_all_products_qty_' . $i;
									$old_qty = wcj_get_option( $qty_key, '' );
									if ( '' !== $old_qty ) {
										$new_qty = $old_qty - $product_qty;
										update_option( $qty_key, $new_qty );
									}
								} elseif ( $local_addon_key === substr( $meta['key'], 0, $local_addon_key_length ) ) {
									$i       = substr( $meta['key'], $local_addon_key_length );
									$qty_key = '_' . 'wcj_product_addons_per_product_qty_' . $i;
									$old_qty = get_post_meta( $product_id, $qty_key, true );
									if ( '' !== $old_qty ) {
										$new_qty = $old_qty - $product_qty;
										update_post_meta( $product_id, $qty_key, $new_qty );
									}
								}
							}
						}
					}
					update_post_meta( $order_id, '_' . 'wcj_product_addons_qty_reduced', 'yes' );
				}
			}
		}
	}

	/**
	 * hide_addons_in_admin_order.
	 *
	 * @version 2.5.6
	 * @since   2.5.6
	 * @todo    get real number of addons (instead of max_addons = 100)
	 */
	function hide_addons_in_admin_order( $hidden_metas ) {
		$max_addons = 100;
		for ( $i = 1; $i <= $max_addons; $i++ ) {
			$hidden_metas[] = '_' . 'wcj_product_all_products_addons_price_' . $i;
			$hidden_metas[] = '_' . 'wcj_product_all_products_addons_label_' . $i;
			$hidden_metas[] = '_' . 'wcj_product_per_product_addons_price_' . $i;
			$hidden_metas[] = '_' . 'wcj_product_per_product_addons_label_' . $i;
		}
		return $hidden_metas;
	}

	/**
	 * validate_on_add_to_cart.
	 *
	 * @version 4.9.0
	 * @since   2.5.5
	 */
	function validate_on_add_to_cart( $passed, $product_id, $quantity ) {
		if ( 4 === count( $args = func_get_args() ) ) {
			$variation_id = $args[3];
		}
		$addons = $this->get_product_addons( $product_id );
		foreach ( $addons as $addon ) {
			// Prevents validation on addons if not in "enable by variation" option
			if (
				! empty( $variation_id ) &&
				! empty( $addon['enable_by_variation'] ) &&
				! in_array( $variation_id, $addon['enable_by_variation'] )
			) {
				continue;
			}
			if ( 'yes' === $addon['is_required'] ) {
				if ( ! isset( $_POST[ $addon['checkbox_key'] ] ) && empty( $addon['default'] ) ) {
					wc_add_notice( __( 'Some of the required addons are not selected!', 'woocommerce-jetpack' ), 'error' );
					return false;
				}
			}
		}
		return $passed;
	}

	/**
	 * get_the_notice.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function get_the_notice() {
		return __( 'Booster: Free plugin\'s version is limited to only three products with per product addons enabled at a time. You will need to get <a href="https://booster.io/plus/" target="_blank">Booster Plus</a> to add unlimited number of products with per product addons.', 'woocommerce-jetpack' );
	}

	/**
	 * maybe_convert_currency.
	 *
	 * @version 5.3.3
	 * @since   2.8.0
	 */
	function maybe_convert_currency( $price, $product = null ) {
		$apply_price_filters = wcj_get_option( 'wcj_product_addons_apply_price_filters', 'by_module' );
		if ( 'by_module' === $apply_price_filters ) {
			$modules_to_apply = wcj_get_option( 'wcj_product_addons_apply_price_filters_by_module', array() );
			// Multicurrency Product Base Price module
			if ( ( empty( $modules_to_apply ) || in_array( 'multicurrency_base_price', $modules_to_apply ) ) && WCJ()->modules['multicurrency_base_price']->is_enabled() ) {
				$price = WCJ()->modules['multicurrency_base_price']->change_price( $price, $product );
			}
			// Multicurrency (Currency Switcher) module
			if ( ( empty( $modules_to_apply ) || in_array( 'multicurrency',            $modules_to_apply ) ) && WCJ()->modules['multicurrency']->is_enabled() ) {
				$price = WCJ()->modules['multicurrency']->change_price( $price, $product, array( 'do_save' => false ) );
			}
			// Global Discount module
			if ( ( empty( $modules_to_apply ) || in_array( 'global_discount',          $modules_to_apply ) ) && WCJ()->modules['global_discount']->is_enabled() ) {
				$price = WCJ()->modules['global_discount']->add_global_discount( $price, $product, 'price' );
			}
		} elseif ( 'yes' === $apply_price_filters ) {
			$price = apply_filters( WCJ_PRODUCT_GET_PRICE_FILTER, $price, $product );
		}
		return $price;
	}

	/**
	 * clean_and_explode.
	 *
	 * @version 3.7.0
	 * @since   3.7.0
	 * @todo    move this to global functions (`wcj_clean_and_explode()`)
	 */
	function clean_and_explode( $delimiter, $string ) {
		return array_map( 'trim', explode( $delimiter, trim( $string ) ) );
	}

	/**
	 * price_change_ajax.
	 *
	 * @version 4.6.0
	 * @since   2.5.3
	 */
	function price_change_ajax( $param ) {
		if ( ! isset( $_POST['product_id'] ) || 0 == $_POST['product_id'] ) {
			die();
		}
		$the_product = wc_get_product( $_POST['product_id'] );
		$parent_product_id = ( $the_product->is_type( 'variation' ) ) ? wp_get_post_parent_id( $_POST['product_id'] ) : $_POST['product_id'];
		$addons = $this->get_product_addons( $parent_product_id );
		$the_addons_price = 0;
		foreach ( $addons as $addon ) {
			$price_value = $this->replace_price_template_vars( $addon['price_value'], $_POST['product_id'] );
			if ( isset( $_POST[ $addon['checkbox_key'] ] ) ) {
				if ( ( 'checkbox' === $addon['type'] || '' == $addon['type'] ) || ( 'text' == $addon['type'] && '' != $_POST[ $addon['checkbox_key'] ] ) ) {
					$the_addons_price += (float) $price_value;
				} elseif ( 'radio' === $addon['type'] || 'select' === $addon['type'] ) {
					$labels = $this->clean_and_explode( PHP_EOL, $addon['label_value'] );
					$prices = $this->clean_and_explode( PHP_EOL, $price_value );
					if ( count( $labels ) === count( $prices ) ) {
						foreach ( $labels as $i => $label ) {
							if ( $_POST[ $addon['checkbox_key'] ] == sanitize_title( $label ) ) {
								$the_addons_price += (float) $prices[ $i ];
								break;
							}
						}
					}
				}
			}
		}
		if ( 0 != $the_addons_price ) {
			$the_price = $the_product->get_price();
			$the_display_price = wcj_get_product_display_price( $the_product, ( $the_price + $this->maybe_convert_currency( $the_addons_price, $the_product ) ) );
			echo wc_price( $the_display_price );
		} else {
			echo wc_price( $the_product->get_price() );
		}
		die();
	}

	/**
	 * enqueue_scripts.
	 *
	 * @version 4.6.1
	 * @since   2.5.3
	 */
	function enqueue_scripts() {
		if ( is_product() ) {
			$the_product         = wc_get_product();
			$addons              = $this->get_product_addons( wcj_get_product_id_or_variation_parent_id( $the_product ) );
			$enable_by_variation = wp_list_pluck( $addons, 'enable_by_variation' );
			if ( ! empty( $addons ) ) {
				$is_variable_with_single_price = ( $the_product->is_type( 'variable' ) && ( $the_product->get_variation_price( 'min' ) == $the_product->get_variation_price( 'max' ) ) );
				wp_enqueue_script( 'wcj-product-addons', wcj_plugin_url() . '/includes/js/wcj-product-addons.js', array(), WCJ()->version, true );
				wp_localize_script( 'wcj-product-addons', 'ajax_object', array(
					'enable_by_variation'           => $enable_by_variation,
					'ajax_url'                      => admin_url( 'admin-ajax.php' ),
					'product_id'                    => get_the_ID(),
					'ignore_strikethrough_price'    => wcj_get_option( 'wcj_product_addons_ajax_ignore_st_price', 'no' ),
					'is_variable_with_single_price' => $is_variable_with_single_price,
				) );
			}
		}
	}

	/**
	 * is_global_addon_visible.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 * @todo    add "include only products"
	 * @todo    add "include/exclude categories/tags"
	 */
	function is_global_addon_visible( $i, $product_id ) {
		$exclude_products = wcj_get_option( 'wcj_product_addons_all_products_exclude_products_' . $i, '' );
		if ( ! empty( $exclude_products ) && in_array( $product_id, $exclude_products ) ) {
			return false;
		}
		return true;
	}

	/**
	 * get_product_addons.
	 *
	 * @version 4.6.1
	 * @since   2.5.3
	 * @todo    (maybe) `checkbox_key` is mislabelled, should be `key` (or maybe `value_key`)
	 */
	function get_product_addons( $product_id ) {
		$addons = array();
		// All Products
		if ( 'yes' === wcj_get_option( 'wcj_product_addons_all_products_enabled', 'no' ) ) {
			$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_product_addons_all_products_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( 'yes' === wcj_get_option( 'wcj_product_addons_all_products_enabled_' . $i, 'yes' ) ) {
					if ( ! $this->is_global_addon_visible( $i, $product_id ) ) {
						continue;
					}
					if ( '0' === ( $qty = wcj_get_option( 'wcj_product_addons_all_products_qty_' . $i, '' ) ) || $qty < 0 ) {
						continue;
					}
					$addons[] = array(
//						'scope'        => 'all_products',
//						'index'        => $i,
						'enable_by_variation' => '',
						'checkbox_key' => 'wcj_product_all_products_addons_' . $i,
						'price_key'    => 'wcj_product_all_products_addons_price_' . $i,
						'label_key'    => 'wcj_product_all_products_addons_label_' . $i,
						'price_value'  => wcj_get_option( 'wcj_product_addons_all_products_price_' . $i ),
						'label_value'  => do_shortcode( wcj_get_option( 'wcj_product_addons_all_products_label_' . $i ) ),
						'title'        => do_shortcode( wcj_get_option( 'wcj_product_addons_all_products_title_' . $i, '' ) ),
						'placeholder'  => do_shortcode( wcj_get_option( 'wcj_product_addons_all_products_placeholder_' . $i, '' ) ),
						'class'        => wcj_get_option( 'wcj_product_addons_all_products_class_' . $i, '' ),
						'tooltip'      => do_shortcode( wcj_get_option( 'wcj_product_addons_all_products_tooltip_' . $i, '' ) ),
						'type'         => wcj_get_option( 'wcj_product_addons_all_products_type_' . $i, 'checkbox' ),
						'default'      => wcj_get_option( 'wcj_product_addons_all_products_default_' . $i, '' ),
						'is_required'  => wcj_get_option( 'wcj_product_addons_all_products_required_' . $i, 'no' ),
						'qty'          => $qty,
					);
				}
			}
		}
		// Per product
		if ( 'yes' === wcj_get_option( 'wcj_product_addons_per_product_enabled', 'no' ) ) {
			if ( 'yes' === get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_settings_enabled', true ) ) {
				$total_number = get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_total_number', true );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					if ( 'yes' === get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_enabled_' . $i, true ) ) {
						if ( '0' === ( $qty = get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_qty_' . $i, true ) ) || $qty < 0  ) {
							continue;
						}
						$addons[] = array(
//							'scope'        => 'per_product',
//							'index'        => $i,
							'enable_by_variation' => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_enable_by_variation_' . $i, true ),
							'checkbox_key'        => 'wcj_product_per_product_addons_' . $i,
							'price_key'           => 'wcj_product_per_product_addons_price_' . $i,
							'label_key'           => 'wcj_product_per_product_addons_label_' . $i,
							'price_value'         => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_price_' . $i, true ),
							'label_value'         => do_shortcode( get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_label_' . $i, true ) ),
							'title'               => do_shortcode( get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_title_' . $i, true ) ),
							'placeholder'         => do_shortcode( get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_placeholder_' . $i, true ) ),
							'class'               => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_class_' . $i, true ),
							'tooltip'             => do_shortcode( get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_tooltip_' . $i, true ) ),
							'type'                => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_type_' . $i, true ),
							'default'             => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_default_' . $i, true ),
							'is_required'         => get_post_meta( $product_id, '_' . 'wcj_product_addons_per_product_required_' . $i, true ),
							'qty'                 => $qty,
						);
					}
				}
			}
		}
		return $addons;
	}

	/**
	 * Replaces template vars on price, like % or any other possibility.
	 *
	 * If it doesn't have any template var, just return the price.
	 *
	 * @version 4.6.0
	 * @since   4.6.0
	 *
	 * @param $price_value
	 * @param $product_id
	 *
	 * @return string
	 */
	function replace_price_template_vars( $price_value, $product_id ) {
		if ( preg_match_all( '/\d+%/m', $price_value, $matches ) > 0 ) {
			$product_price     = get_post_meta( $product_id, '_price', true );
			$new_prices        = array();
			$preg_replace_find = array();
			foreach ( $matches[0] as $match ) {
				$preg_replace_find[] = '/' . $match . '/';
				$percent_value       = preg_replace( '/\%/', '', $match );
				$new_prices[]        = $product_price * ( $percent_value / 100 );
			}
			$price_value = preg_replace( $preg_replace_find, $new_prices, $price_value );
		}
		return $price_value;
	}

	/**
	 * add_info_to_order_item_meta_wc3.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @todo    this is only a temporary solution: must replace `$item->legacy_values` (check "Bookings" module - `woocommerce_checkout_create_order_line_item` hook)
	 */
	function add_info_to_order_item_meta_wc3( $item_id, $item, $order_id  ) {
		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
			$this->add_info_to_order_item_meta( $item_id, $item->legacy_values, null );
		}
	}

	/**
	 * add_info_to_order_item_meta.
	 *
	 * @version 3.7.0
	 * @since   2.5.3
	 */
	function add_info_to_order_item_meta( $item_id, $values, $cart_item_key  ) {
		$addons  = $this->get_product_addons( $values['product_id'] );
		$product = wc_get_product( $values['product_id'] );
		foreach ( $addons as $addon ) {
			if ( isset( $values[ $addon['price_key'] ] ) ) {
				wc_add_order_item_meta( $item_id, '_' . $addon['price_key'], $this->maybe_convert_currency( $values[ $addon['price_key'] ], $product ) );
				wc_add_order_item_meta( $item_id, '_' . $addon['label_key'], $values[ $addon['label_key'] ] );
			}
		}
	}

	/**
	 * Adds info to order details (and emails).
	 *
	 * @version 4.8.0
	 * @since   2.5.3
	 */
	function add_info_to_order_item_name( $name, $item, $is_cart = false ) {
		if ( $is_cart ) {
			$start_format = wcj_get_option( 'wcj_product_addons_cart_format_start', '<dl class="variation">' );
			$item_format  = wcj_get_option( 'wcj_product_addons_cart_format_each_addon', '<dt>%addon_label%:</dt><dd>%addon_price%</dd>' );
			$end_format   = wcj_get_option( 'wcj_product_addons_cart_format_end', '</dl>' );
		} else {
			$start_format = wcj_get_option( 'wcj_product_addons_order_details_format_start', '' );
			$item_format  = wcj_get_option( 'wcj_product_addons_order_details_format_each_addon', '&nbsp;| %addon_label%: %addon_price%' );
			$end_format   = wcj_get_option( 'wcj_product_addons_order_details_format_end', '' );
		}
		$addons_info = '';
		$addons      = $this->get_product_addons( $item['product_id'] );
		$_product    = wc_get_product( $item['product_id'] );
		foreach ( $addons as $addon ) {
			if ( isset( $item[ $addon['price_key'] ] ) ) {
				$addon_price = ( $is_cart ) ? $this->maybe_convert_currency( $item[ $addon['price_key'] ], $_product ) : $item[ $addon['price_key'] ];
				$addons_info .= str_replace(
					array( '%addon_label%', '%addon_price%', '%addon_title%' ),
					array( $item[ $addon['label_key'] ], wc_price( wcj_get_product_display_price( $_product, $addon_price ) ), $addon['title'] ),
					$item_format
				);
			}
		}
		if ( '' != $addons_info ) {
			$name .= $start_format . $addons_info . $end_format;
		}
		return $name;
	}

	/**
	 * Adds info to cart item details.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_info_to_cart_item_name( $name, $cart_item, $cart_item_key  ) {
		return $this->add_info_to_order_item_name( $name, $cart_item, true );
	}

	/**
	 * change_price.
	 *
	 * @version 4.4.0
	 * @since   2.5.3
	 */
	function change_price( $price, $_product ) {
		$addons = $this->get_product_addons( wcj_get_product_id_or_variation_parent_id( $_product ) );
		foreach ( $addons as $addon ) {
			if ( isset( $_product->{$addon['price_key']} ) ) {
				$price += $this->maybe_convert_currency( $_product->{$addon['price_key']}, $_product );
			}
		}
		return $price;
	}

	/**
	 * add_addons_price_to_cart_item.
	 *
	 * @version 4.5.0
	 * @since   2.5.3
	 */
	function add_addons_price_to_cart_item( $cart_item_data, $cart_item_key ) {
		$addons = $this->get_product_addons( ( WCJ_IS_WC_VERSION_BELOW_3 ? $cart_item_data['data']->product_id : wcj_get_product_id_or_variation_parent_id( $cart_item_data['data'] ) ) );
		foreach ( $addons as $addon ) {
			if ( isset( $cart_item_data[ $addon['price_key'] ] ) ) {
				if ( ! isset( $cart_item_data['wcj_pa_extra_price'] ) ) {
					$cart_item_data['wcj_pa_extra_price'] = 0;
				}
				$cart_item_data['wcj_pa_extra_price'] += (float) $cart_item_data[ $addon['price_key'] ];
			}
		}
		if ( isset( $cart_item_data['wcj_pa_extra_price'] ) ) {
			$cart_item_data['wcj_pa_total_extra_price'] = $cart_item_data['data']->get_data()['price'] + $cart_item_data['wcj_pa_extra_price'];
			$cart_item_data['data']->set_price( $cart_item_data['wcj_pa_total_extra_price'] );
		}
		return $cart_item_data;
	}

	/**
	 * get_cart_item_addons_price_from_session.
	 *
	 * @version 4.5.0
	 * @since   2.5.3
	 */
	function get_cart_item_addons_price_from_session( $item, $values, $addon ) {
		if ( array_key_exists( 'wcj_pa_total_extra_price', $item ) ) {
			$item['data']->set_price( $item['wcj_pa_total_extra_price'] );
		}
		return $item;
	}

	/**
	 * add_addons_price_to_cart_item_data.
	 *
	 * @version 4.9.0
	 * @since   2.5.3
	 */
	function add_addons_price_to_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		$addons = $this->get_product_addons( $product_id );
		foreach ( $addons as $addon ) {
			// Prevents adding addons to cart if not in "enable by variation" option
			if (
				! empty( $variation_id ) &&
				! empty( $addon['enable_by_variation'] ) &&
				! in_array( $variation_id, $addon['enable_by_variation'] )
			) {
				continue;
			}
			$price_value = $this->replace_price_template_vars( $addon['price_value'], $variation_id ? $variation_id : $product_id );
			if (
				isset( $_POST[ $addon['checkbox_key'] ] ) ||
				! empty( $addon['default'] )
			) {
				$checkbox_key = isset( $_POST[ $addon['checkbox_key'] ] ) ? $_POST[ $addon['checkbox_key'] ] : ( ! empty( $addon['default'] ) ? $addon['default'] : null );
				if ( ( 'checkbox' === $addon['type'] || '' == $addon['type'] ) || ( 'text' == $addon['type'] && '' != $checkbox_key ) ) {
					$cart_item_data[ $addon['price_key'] ] = $price_value;
					$cart_item_data[ $addon['label_key'] ] = $addon['label_value'];
					if ( 'text' == $addon['type'] ) {
						$cart_item_data[ $addon['label_key'] ] .= ' (' . $checkbox_key . ')';
					}
				} elseif ( 'radio' === $addon['type'] || 'select' === $addon['type'] ) {
					$prices = $this->clean_and_explode( PHP_EOL, $price_value );
					$labels = $this->clean_and_explode( PHP_EOL, $addon['label_value'] );
					if ( count( $labels ) === count( $prices ) ) {
						foreach ( $labels as $i => $label ) {
							if ( $checkbox_key == sanitize_title( $label ) ) {
								$cart_item_data[ $addon['price_key'] ] = $prices[ $i ];
								$cart_item_data[ $addon['label_key'] ] = $labels[ $i ];
								break;
							}
						}
					}
				}
			}
		}
		return $cart_item_data;
	}

	/**
	 * add_addons_to_frontend.
	 *
	 * @version 4.6.0
	 * @since   2.5.3
	 */
	function add_addons_to_frontend() {
		if ( isset( $this->are_addons_displayed ) && 'yes' === wcj_get_option( 'wcj_product_addons_check_for_outputted_data', 'yes' ) ) {
			return;
		}
		$html     = '';
		$addons   = $this->get_product_addons( get_the_ID() );
		$_product = wc_get_product( get_the_ID() );
		foreach ( $addons as $addon ) {
			if ( '' != $addon['title'] ) {
				$html .= wcj_handle_replacements( array(
						'%addon_id%'    => $addon['checkbox_key'],
						'%addon_title%' => $addon['title'],
					), wcj_get_option( 'wcj_product_addons_template_title', '<p><label for="%addon_id%">%addon_title%</label></p>' ) );
			}
			$is_required = ( 'yes' === $addon['is_required'] ) ? ' required' : '';
			if ( 'checkbox' === $addon['type'] || '' == $addon['type'] ) {
				$is_checked = '';
				if ( isset( $_POST[ $addon['checkbox_key'] ] ) ) {
					$is_checked = ' checked';
				} elseif ( 'checked' === $addon['default'] ) {
					$is_checked = ' checked';
				}
				$maybe_tooltip = ( '' != $addon['tooltip'] ) ?
					' <img style="display:inline;" class="wcj-question-icon" src="' . wcj_plugin_url() . '/assets/images/question-icon.png' . '" title="' . $addon['tooltip'] . '">' :
					'';
				$html .= wcj_handle_replacements( array(
						'%addon_input%'   => '<input type="checkbox" id="' . $addon['checkbox_key'] . '" class="' . $addon['class'] . '" name="' . $addon['checkbox_key'] . '"' . $is_checked . $is_required . '>',
						'%addon_id%'      => $addon['checkbox_key'],
						'%addon_label%'   => $addon['label_value'],
						'%addon_price%'   => $this->format_addon_price( $addon['price_value'], $_product ),
						'%addon_tooltip%' => $maybe_tooltip,
					), wcj_get_option( 'wcj_product_addons_template_type_checkbox',
						'<p>%addon_input% <label for="%addon_id%">%addon_label% (%addon_price%)</label>%addon_tooltip%</p>' ) );
			} elseif ( 'text' == $addon['type'] ) {
				$default_value = ( isset( $_POST[ $addon['checkbox_key'] ] ) ? $_POST[ $addon['checkbox_key'] ] : $addon['default'] );
				$maybe_tooltip = ( '' != $addon['tooltip'] ) ?
					' <img style="display:inline;" class="wcj-question-icon" src="' . wcj_plugin_url() . '/assets/images/question-icon.png' . '" title="' . $addon['tooltip'] . '">' :
					'';
				$html .= wcj_handle_replacements( array(
						'%addon_input%'   => '<input type="text" id="' . $addon['checkbox_key'] . '" class="' . $addon['class'] . '" name="' . $addon['checkbox_key'] . '" placeholder="' . $addon['placeholder'] . '" value="' . $default_value . '"' . $is_required . '>',
						'%addon_id%'      => $addon['checkbox_key'],
						'%addon_label%'   => $addon['label_value'],
						'%addon_price%'   => $this->format_addon_price( $addon['price_value'], $_product ),
						'%addon_tooltip%' => $maybe_tooltip,
					), wcj_get_option( 'wcj_product_addons_template_type_text',
						'<p><label for="%addon_id%">%addon_label% (%addon_price%)</label> %addon_input%%addon_tooltip%</p>' ) );
			} elseif ( 'radio' === $addon['type'] || 'select' === $addon['type'] ) {
				$prices   = $this->clean_and_explode( PHP_EOL, $addon['price_value'] );
				$labels   = $this->clean_and_explode( PHP_EOL, $addon['label_value'] );
				if ( 'radio' === $addon['type'] ) {
					$tooltips = $this->clean_and_explode( PHP_EOL, $addon['tooltip'] );
				}
				if ( count( $labels ) === count( $prices ) ) {
					if ( 'select' === $addon['type'] ) {
						$select_options = '';
					}
					foreach ( $labels as $i => $label ) {
						$label = sanitize_title( $label );
						$is_checked = '';
						$checked_or_selected = ( 'radio' === $addon['type'] ? ' checked' : ' selected' );
						if ( isset( $_POST[ $addon['checkbox_key'] ] ) ) {
							$is_checked = ( $label === $_POST[ $addon['checkbox_key'] ] ) ? $checked_or_selected : '';
						} elseif ( '' != $addon['default'] ) {
							$is_checked = ( $label === sanitize_title( $addon['default'] ) ) ? $checked_or_selected : '';
						}
						if ( 'radio' === $addon['type'] ) {
							$maybe_tooltip = ( isset( $tooltips[ $i ] ) && '' != $tooltips[ $i ] ) ?
								' <img style="display:inline;" class="wcj-question-icon" src="' . wcj_plugin_url() . '/assets/images/question-icon.png' . '" title="' . $tooltips[ $i ] . '">' :
								'';
							$html .= wcj_handle_replacements( array(
									'%addon_input%'   => '<input type="radio" id="' . $addon['checkbox_key'] . '-' . $label . '" class="' . $addon['class'] . '" name="' . $addon['checkbox_key'] . '" value="' . $label . '"' . $is_checked . $is_required . '>',
									'%addon_id%'      => $addon['checkbox_key'] . '-' . $label,
									'%addon_label%'   => $labels[ $i ],
									'%addon_price%'   => $this->format_addon_price( $prices[ $i ], $_product ),
									'%addon_tooltip%' => $maybe_tooltip,
								), wcj_get_option( 'wcj_product_addons_template_type_radio',
									'<p>%addon_input% <label for="%addon_id%">%addon_label% (%addon_price%)</label>%addon_tooltip%</p>' ) );
						} else {
							$select_option = wcj_handle_replacements( array(
									'%addon_label%'   => $labels[ $i ],
									'%addon_price%'   => $this->format_addon_price( $prices[ $i ], $_product ),
								), wcj_get_option( 'wcj_product_addons_template_type_select_option', '%addon_label% (%addon_price%)' ) );
							$select_options .= '<option value="' . $label . '"' . $is_checked . '>' . $select_option . '</option>';
						}
					}
					if ( 'select' === $addon['type'] ) {
						$maybe_tooltip = ( '' != $addon['tooltip'] ) ?
							' <img style="display:inline;" class="wcj-question-icon" src="' . wcj_plugin_url() . '/assets/images/question-icon.png' . '" title="' . $addon['tooltip'] . '">' :
							'';
						if ( '' != $addon['placeholder'] ) {
							$select_options = '<option value="">' . $addon['placeholder'] . '</option>' . $select_options;
						}
						$html .= wcj_handle_replacements( array(
								'%addon_input%'   => '<select' . $is_required . ' id="' . $addon['checkbox_key'] . '" class="' . $addon['class'] . '" name="' . $addon['checkbox_key'] . '">' . $select_options . '</select>',
								'%addon_tooltip%' => $maybe_tooltip,
							), wcj_get_option( 'wcj_product_addons_template_type_select', '<p>%addon_input%%addon_tooltip%</p>' ) );
					}
				}
			}
		}
		// Output
		if ( ! empty( $html ) ) {
			$html = wcj_handle_replacements( array(
					'%addons_html%' => $html,
				), wcj_get_option( 'wcj_product_addons_template_final', '<div id="wcj_product_addons">%addons_html%</div>' ) );
			echo $this->remove_empty_parenthesis($html);
			$this->are_addons_displayed = true;
		}
	}

	/**
	 * remove_empty_parenthesis.
	 *
	 * @version 4.6.0
	 * @since   4.6.0
	 *
	 * @param $html
	 *
	 * @return string
	 */
	function remove_empty_parenthesis( $html ) {
		return preg_replace( '/\(\)/', '', $html );
	}

	/**
	 * Formats the addon price.
	 *
	 * @version 4.6.0
	 * @since   4.6.0
	 *
	 * @param $_product
	 * @param $price
	 *
	 * @return string
	 */
	function format_addon_price( $price, $_product ) {
		$show_raw_price = false;
		if ( preg_match_all( '/\d+%/m', $price ) > 0 ) {
			if ( is_a( $_product, 'WC_Product_Simple' ) ) {
				$price = $this->replace_price_template_vars( $price, $_product->get_id() );
			} else {
				$show_raw_price = true;
			}
		}
		if ( $show_raw_price ) {
			if ( 'yes' === wcj_get_option( 'wcj_product_addons_template_hide_percentage_price', 'yes' ) ) {
				return '';
			} else {
				return $price;
			}
		} else {
			return wc_price( wcj_get_product_display_price( $_product, $this->maybe_convert_currency( $price, $_product ) ) );
		}
	}

}

endif;

return new WCJ_Product_Addons();

<?php
/**
 * Booster for WooCommerce - Functions - Products
 *
 * @version 5.6.2
 * @since   2.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_maybe_get_product_id_wpml' ) ) {
	/**
	 * Wcj_maybe_get_product_id_wpml.
	 *
	 * @version 3.8.0
	 * @since   3.6.0
	 * @param int $product_id Get product id.
	 */
	function wcj_maybe_get_product_id_wpml( $product_id ) {
		if ( function_exists( 'icl_object_id' ) && ( 'yes' === wcj_get_option( 'wcj_wpml_use_translation_product_id', 'yes' ) || ! wcj_is_module_enabled( 'wpml' ) ) ) {
			$product_id = icl_object_id( $product_id, 'product', true, wcj_get_wpml_default_language() );
		}
		return $product_id;
	}
}

if ( ! function_exists( 'wcj_is_enabled_for_product' ) ) {
	/**
	 * Wcj_is_enabled_for_product.
	 *
	 * @version 5.6.2
	 * @since   3.1.0
	 * @param int   $product_id Get product id.
	 * @param Array $args Get product args.
	 */
	function wcj_is_enabled_for_product( $product_id, $args ) {
		if ( isset( $args['include_products'] ) && ! empty( $args['include_products'] ) ) {
			if ( ! is_array( $args['include_products'] ) ) {
				$args['include_products'] = array_map( 'trim', explode( ',', $args['include_products'] ) );
			}
			if ( ! in_array( (string) $product_id, $args['include_products'], true ) ) {
				return false;
			}
		}
		if ( isset( $args['exclude_products'] ) && ! empty( $args['exclude_products'] ) ) {
			if ( ! is_array( $args['exclude_products'] ) ) {
				$args['exclude_products'] = array_map( 'trim', explode( ',', $args['exclude_products'] ) );
			}
			if ( in_array( (string) $product_id, $args['exclude_products'], true ) ) {
				return false;
			}
		}
		if ( isset( $args['include_categories'] ) && ! empty( $args['include_categories'] ) ) {
			if ( ! wcj_is_product_term( $product_id, $args['include_categories'], 'product_cat' ) ) {
				return false;
			}
		}
		if ( isset( $args['exclude_categories'] ) && ! empty( $args['exclude_categories'] ) ) {
			if ( wcj_is_product_term( $product_id, $args['exclude_categories'], 'product_cat' ) ) {
				return false;
			}
		}
		if ( isset( $args['include_tags'] ) && ! empty( $args['include_tags'] ) ) {
			if ( ! wcj_is_product_term( $product_id, $args['include_tags'], 'product_tag' ) ) {
				return false;
			}
		}
		if ( isset( $args['exclude_tags'] ) && ! empty( $args['exclude_tags'] ) ) {
			if ( wcj_is_product_term( $product_id, $args['exclude_tags'], 'product_tag' ) ) {
				return false;
			}
		}
		return true;
	}
}

if ( ! function_exists( 'wcj_get_product_id_or_variation_id' ) ) {
	/**
	 * Wcj_get_product_id_or_variation_id.
	 *
	 * @version 5.5.8
	 * @since  1.0.0
	 * @param Array $_product Get products.
	 */
	function wcj_get_product_id_or_variation_id( $_product ) {
		if ( ! $_product || ! is_object( $_product ) ) {
			return 0;
		}
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			return $_product->id;

		} else {

			return $_product->get_id();
		}
	}
}

if ( ! function_exists( 'wcj_get_product_id' ) ) {
	/**
	 * Wcj_get_product_id.
	 *
	 * @version 2.9.0
	 * @since   2.7.0
	 * @param Array $_product Get products.
	 */
	function wcj_get_product_id( $_product ) {
		if ( ! $_product || ! is_object( $_product ) ) {
			return 0;
		}
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			return ( isset( $_product->variation_id ) ) ? $_product->variation_id : $_product->id;
		} else {
			return $_product->get_id();
		}
	}
}

if ( ! function_exists( 'wcj_get_product_id_or_variation_parent_id' ) ) {
	/**
	 * Wcj_get_product_id_or_variation_parent_id.
	 *
	 * @version 2.9.0
	 * @since   2.7.0
	 * @param Array $_product Get products.
	 */
	function wcj_get_product_id_or_variation_parent_id( $_product ) {
		if ( ! $_product || ! is_object( $_product ) ) {
			return 0;
		}
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			return $_product->id;
		} else {
			return ( $_product->is_type( 'variation' ) ) ? $_product->get_parent_id() : $_product->get_id();
		}
	}
}

if ( ! function_exists( 'wcj_get_product_status' ) ) {
	/**
	 * Wcj_get_product_status.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 * @param Array $_product Get products.
	 */
	function wcj_get_product_status( $_product ) {
		return ( WCJ_IS_WC_VERSION_BELOW_3 ) ? $_product->post->post_status : $_product->get_status();
	}
}

if ( ! function_exists( 'wcj_get_product_total_stock' ) ) {
	/**
	 * Wcj_get_product_total_stock.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 * @param Array $_product Get products.
	 */
	function wcj_get_product_total_stock( $_product ) {
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			return $_product->get_total_stock();
		} else {
			if ( $_product->is_type( array( 'variable', 'grouped' ) ) ) {
				$total_stock = 0;
				foreach ( $_product->get_children() as $child_id ) {
					$child        = wc_get_product( $child_id );
					$total_stock += $child->get_stock_quantity();
				}
				return $total_stock;
			} else {
				return $_product->get_stock_quantity();
			}
		}
	}
}

if ( ! function_exists( 'wcj_get_product_display_price' ) ) {
	/**
	 * Wcj_get_product_display_price.
	 *
	 * @version 5.3.8
	 * @since   2.7.0
	 * @todo    `$scope` in `WCJ_IS_WC_VERSION_BELOW_3` (i.e. `cart`)
	 * @param Array      $_product Get products.
	 * @param null | int $price Get price.
	 * @param int        $qty Get product qty.
	 * @param string     $scope Get product scope.
	 */
	function wcj_get_product_display_price( $_product, $price = '', $qty = 1, $scope = 'shop' ) {
		if ( ! $_product || ! is_object( $_product ) ) {
			return false;
		}
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			return $_product->get_display_price( $price, $qty );
		} else {
			$minus_sign = '';
			if ( $price < 0 ) {
				if ( is_numeric( $price ) ) {
					$minus_sign = '-';
					$price     *= -1;
				}
			}
			if ( 'cart' === $scope ) {
				$display_price = ( 'incl' === wcj_get_option( 'woocommerce_tax_display_cart' ) ?
					wc_get_price_including_tax(
						$_product,
						array(
							'price' => $price,
							'qty'   => $qty,
						)
					) :
					wc_get_price_excluding_tax(
						$_product,
						array(
							'price' => $price,
							'qty'   => $qty,
						)
					) );
			} else { // shop.
				$display_price = wc_get_price_to_display(
					$_product,
					array(
						'price' => $price,
						'qty'   => $qty,
					)
				);
			}
			return $minus_sign . $display_price;
		}
	}
}

if ( ! function_exists( 'wcj_get_product_formatted_variation' ) ) {
	/**
	 * Wcj_get_product_formatted_variation.
	 *
	 * @version 5.4.0
	 * @since   2.7.0
	 * @param Array $variation Get product variations.
	 * @param  bool  $flat formatted variation attributes.
	 * @param bool  $include_names Get include names.
	 */
	function wcj_get_product_formatted_variation( $variation, $flat = false, $include_names = true ) {
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			return $variation->get_formatted_variation_attributes( $flat );
		} else {
			return wc_get_formatted_variation( $variation, $flat, $include_names );
		}
	}
}

if ( ! function_exists( 'wcj_get_product_image_url' ) ) {
	/**
	 * Wcj_get_product_image_url.
	 *
	 * @version 5.6.2
	 * @since   2.5.7
	 * @todo    placeholder
	 * @param int    $product_id Get product id.
	 * @param string $image_size Get images size.
	 */
	function wcj_get_product_image_url( $product_id, $image_size = 'shop_thumbnail' ) {
		$parent_id = wp_get_post_parent_id( $product_id );
		if ( has_post_thumbnail( $product_id ) ) {
			$image_url = get_the_post_thumbnail_url( $product_id, $image_size );
		} elseif ( $parent_id && has_post_thumbnail( $parent_id ) ) {
			$image_url = get_the_post_thumbnail_url( $parent_id, $image_size );
		} else {
			$image_url = '';
		}
		return $image_url;
	}
}

if ( ! function_exists( 'wcj_get_product_input_field_value' ) ) {
	/**
	 * Wcj_get_product_input_field_value.
	 *
	 * @version 3.5.1
	 * @since   3.5.1
	 * @param Array  $item  get items.
	 * @param string $key get item key.
	 * @param string $field Get field.
	 */
	function wcj_get_product_input_field_value( $item, $key, $field ) {
		$key         = explode( '_', str_replace( '_wcj_product_input_fields_', '', $key ) );
		$scope       = $key[0];
		$option_name = 'wcj_product_input_fields_' . $field . '_' . $scope . '_' . $key[1];
		$product_id  = $item['product_id'];
		$default     = '';
		if ( 'global' === $scope ) {
			return wcj_get_option( $option_name, $default );
		} else { // local.
			$options = get_post_meta( $product_id, '_wcj_product_input_fields', true );
			if ( '' !== ( $options ) ) {
				$option_name = str_replace( 'wcj_product_input_fields_', '', $option_name );
				return ( isset( $options[ $option_name ] ) ? $options[ $option_name ] : $default );
			} else { // Booster version  < 3.5.0.
				return get_post_meta( $product_id, '_' . $option_name, true );
			}
		}
	}
}

if ( ! function_exists( 'wcj_get_product_input_fields' ) ) {
	/**
	 * Wcj_get_product_input_fields.
	 *
	 * @version 3.5.1
	 * @since   2.4.4
	 * @return  string
	 * @todo    (maybe) better handle "file" type
	 * @param Array  $item  get items.
	 * @param bool   $do_add_titles add titles.
	 * @param string $sep  get sep.
	 */
	function wcj_get_product_input_fields( $item, $do_add_titles = false, $sep = ', ' ) {
		$product_input_fields = array();
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			foreach ( $item as $key => $value ) {
				if ( false !== strpos( $key, 'wcj_product_input_fields_' ) ) {
					$title                  = ( $do_add_titles ? wcj_get_product_input_field_value( $item, $key, 'title' ) . ': ' : '' );
					$product_input_fields[] = $title . wcj_maybe_unserialize_and_implode( $value );
				}
			}
		} else {
			foreach ( $item->get_meta_data() as $value ) {
				if ( isset( $value->key ) && isset( $value->value ) && false !== strpos( $value->key, 'wcj_product_input_fields_' ) ) {
					$title                  = ( $do_add_titles ? wcj_get_product_input_field_value( $item, $value->key, 'title' ) . ': ' : '' );
					$product_input_fields[] = $title . wcj_maybe_unserialize_and_implode( $value->value );
				}
			}
		}
		return ( ! empty( $product_input_fields ) ) ? implode( $sep, array_map( 'wcj_maybe_implode', $product_input_fields ) ) : '';
	}
}

if ( ! function_exists( 'wcj_get_index_from_key' ) ) {
	/**
	 * Wcj_get_index_from_key.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 * @return  string
	 * @param String $key Get key.
	 */
	function wcj_get_index_from_key( $key ) {
		$index = explode( '_', $key );
		$index = array_reverse( $index );
		return $index[0];
	}
}

if ( ! function_exists( 'wcj_get_product_addons' ) ) {
	/**
	 * Wcj_get_product_addons.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 * @return  string
	 * @param Array  $item Get items.
	 * @param string $order_currency Get order currency.
	 */
	function wcj_get_product_addons( $item, $order_currency ) {

		// Prepare item values.
		$values = array();
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			$values = $item;
		} else {
			foreach ( $item->get_meta_data() as $value ) {
				if ( isset( $value->key ) && isset( $value->value ) ) {
					$values[ $value->key ] = $value->value;
				}
			}
		}

		// Prepare addons (if any).
		$addons = array();
		foreach ( $values as $key => $value ) {
			if ( false !== strpos( $key, 'wcj_product_all_products_addons_label_' ) ) {
				$addons['all_products'][ wcj_get_index_from_key( $key ) ]['label'] = $value;
			}
			if ( false !== strpos( $key, 'wcj_product_per_product_addons_label_' ) ) {
				$addons['per_product'][ wcj_get_index_from_key( $key ) ]['label'] = $value;
			}
			if ( false !== strpos( $key, 'wcj_product_all_products_addons_price_' ) ) {
				$addons['all_products'][ wcj_get_index_from_key( $key ) ]['price'] = $value;
			}
			if ( false !== strpos( $key, 'wcj_product_per_product_addons_price_' ) ) {
				$addons['per_product'][ wcj_get_index_from_key( $key ) ]['price'] = $value;
			}
		}

		// Final result array.
		$return = array();
		foreach ( $addons as $scope => $scope_addons ) {
			foreach ( $scope_addons as $index => $addons_data ) {
				$return[] = $addons_data['label'] . ': ' . wc_price( $addons_data['price'], array( 'currency' => $order_currency ) );
			}
		}
		return ( ! empty( $return ) ) ? implode( ', ', $return ) : '';
	}
}

if ( ! function_exists( 'wcj_get_products' ) ) {
	/**
	 * Wcj_get_products.
	 *
	 * @version 4.1.0
	 * @todo    [dev] optimize `if ( $variations_only ) { ... }`
	 * @todo    [dev] maybe use `wc_get_products()` instead of `WP_Query`
	 * @param Array  $products Get products.
	 * @param string $post_status get post status.
	 * @param int    $block_size  Get block size.
	 * @param bool   $add_variations Add variations.
	 * @param bool   $variations_only get variation only.
	 */
	function wcj_get_products( $products = array(), $post_status = 'any', $block_size = 256, $add_variations = false, $variations_only = false ) {
		$offset = 0;
		while ( true ) {
			$args = array(
				'post_type'      => ( $add_variations ? array( 'product', 'product_variation' ) : 'product' ),
				'post_status'    => $post_status,
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $post_id ) {
				if ( $variations_only ) {
					$_product = wc_get_product( $post_id );
					if ( $_product->is_type( 'variable' ) ) {
						continue;
					}
				}
				$products[ $post_id ] = get_the_title( $post_id ) . ' (ID:' . $post_id . ')';
			}
			$offset += $block_size;
		}
		return $products;
	}
}

if ( ! function_exists( 'wcj_product_has_terms' ) ) {
	/**
	 * Wcj_product_has_terms.
	 *
	 * @version 3.5.0
	 * @since   2.8.2
	 * @param Array  $_product Get product.
	 * @param string $_values Get values.
	 * @param String $_term get term.
	 */
	function wcj_product_has_terms( $_product, $_values, $_term ) {
		if ( is_string( $_values ) ) {
			$_values = explode( ',', $_values );
		}
		if ( empty( $_values ) ) {
			return false;
		}
		$product_id         = ( is_numeric( $_product ) && $_product > 0 ? $_product : wcj_get_product_id_or_variation_parent_id( $_product ) );
		$product_categories = get_the_terms( $product_id, $_term );
		if ( empty( $product_categories ) ) {
			return false;
		}
		foreach ( $product_categories as $product_category ) {
			foreach ( $_values as $_value ) {
				if ( $product_category->slug === $_value ) {
					return true;
				}
			}
		}
		return false;
	}
}

if ( ! function_exists( 'wcj_is_product_wholesale_enabled_per_product' ) ) {
	/**
	 * Wcj_is_product_wholesale_enabled_per_product.
	 *
	 * @version 3.3.0
	 * @since   2.5.0
	 * @param int $product_id Get product id.
	 */
	function wcj_is_product_wholesale_enabled_per_product( $product_id ) {
		return (
			'yes' === wcj_get_option( 'wcj_wholesale_price_per_product_enable', 'yes' ) &&
			'yes' === get_post_meta( $product_id, '_wcj_wholesale_price_per_product_enabled', true )
		);
	}
}

if ( ! function_exists( 'wcj_is_product_wholesale_enabled' ) ) {
	/**
	 * Wcj_is_product_wholesale_enabled.
	 *
	 * @version 5.6.2
	 * @param int $product_id Get product id.
	 */
	function wcj_is_product_wholesale_enabled( $product_id ) {
		if ( wcj_is_module_enabled( 'wholesale_price' ) ) {
			if ( wcj_is_product_wholesale_enabled_per_product( $product_id ) ) {
				return true;
			} else {

				$product_cats_to_include = wcj_get_option( 'wcj_wholesale_price_product_cats_to_include', array() );
				if ( ! empty( $product_cats_to_include ) && wcj_is_product_term( $product_id, $product_cats_to_include, 'product_cat' ) ) {
					$is_product_eligible = true;
				}

				if ( 'no' === wcj_get_option( 'wcj_wholesale_price_per_product_enable', 'yes' ) ) {
					$product_cats_to_exclude = wcj_get_option( 'wcj_wholesale_price_product_cats_to_exclude', array() );
					if ( ! empty( $product_cats_to_exclude ) && wcj_is_product_term( $product_id, $product_cats_to_exclude, 'product_cat' ) ) {
						if ( ! empty( $product_cats_to_exclude ) && in_array( (string) $product_id, $product_cats_to_exclude, true ) ) {
							$is_product_eligible_new = false;
						}
						return $is_product_eligible_new ?? false;
					}
				}

				$products_to_include = wcj_get_option( 'wcj_wholesale_price_products_to_include', array() );
				if ( ! empty( $products_to_include ) && in_array( (string) $product_id, $products_to_include, true ) ) {
					$is_product_eligible = true;
				} elseif ( empty( $products_to_include ) && empty( $product_cats_to_include ) ) {
					$is_product_eligible = true;
				}

				if ( 'no' === wcj_get_option( 'wcj_wholesale_price_per_product_enable', 'yes' ) ) {
					$products_to_exclude = wcj_get_option( 'wcj_wholesale_price_products_to_exclude', array() );
					if ( ! empty( $products_to_exclude ) && in_array( (string) $product_id, $products_to_exclude, true ) ) {
						$is_product_eligible = false;
					}
				}

				return $is_product_eligible ?? false;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'wcj_get_the_terms' ) ) {
	/**
	 * Wcj_get_the_terms.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @param int    $product_id Get product id.
	 * @param string $taxonomy  Get taxonomy.
	 */
	function wcj_get_the_terms( $product_id, $taxonomy ) {
		$result = array();
		$_terms = get_the_terms( $product_id, $taxonomy );
		if ( ! empty( $_terms ) ) {
			foreach ( $_terms as $_term ) {
				$result[] = $_term->term_id;
			}
		}
		return $result;
	}
}

if ( ! function_exists( 'wcj_is_product_term' ) ) {
	/**
	 * Wcj_is_product_term.
	 *
	 * @version 3.7.0
	 * @since   2.9.0
	 * @param int    $product_id Get product id.
	 * @param int    $term_ids Get term id.
	 * @param string $taxonomy  Get taxonomy.
	 */
	function wcj_is_product_term( $product_id, $term_ids, $taxonomy ) {
		if ( empty( $term_ids ) ) {
			return false;
		}
		$product_terms = get_the_terms( $product_id, $taxonomy );
		if ( empty( $product_terms ) ) {
			return false;
		}
		foreach ( $product_terms as $product_term ) {
			if ( in_array( (string) $product_term->term_id, $term_ids, true ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'wcj_get_terms' ) ) {
	/**
	 * Wcj_get_terms.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 * @param Array $args Get terms args.
	 */
	function wcj_get_terms( $args ) {
		if ( ! is_array( $args ) ) {
			$_taxonomy = $args;
			$args      = array(
				'taxonomy'   => $_taxonomy,
				'orderby'    => 'name',
				'hide_empty' => false,
			);
		}
		global $wp_version;
		if ( version_compare( $wp_version, '4.5.0', '>=' ) ) {
			$_terms = get_terms( $args );
		} else {
			$_taxonomy = $args['taxonomy'];
			unset( $args['taxonomy'] );
			$_terms = get_terms( $_taxonomy, $args );
		}
		$_terms_options = array();
		if ( ! empty( $_terms ) && ! is_wp_error( $_terms ) ) {
			foreach ( $_terms as $_term ) {
				$_terms_options[ $_term->term_id ] = $_term->name;
			}
		}
		return $_terms_options;
	}
}

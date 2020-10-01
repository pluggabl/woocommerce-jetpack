<?php
/**
 * Booster for WooCommerce - Module - Custom Price Labels
 *
 * @version 5.2.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Price_Labels' ) ) :

class WCJ_Price_Labels extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 */
	function __construct() {

		$this->id         = 'price_labels';
		$this->short_desc = __( 'Custom Price Labels', 'woocommerce-jetpack' );
		$this->desc       = __( 'Create any custom price label for any product (Just a few positions allowed in free version).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Create any custom price label for any product.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-custom-price-labels';
		parent::__construct();

		// Custom Price Labels - fields array
		$this->custom_tab_group_name                  = 'wcj_price_labels';
		$this->custom_tab_sections                    = array( '_instead', '_before', '_between', '_after', );
		$this->custom_tab_sections_titles             = array(
			'_instead' => __( 'Instead of the price', 'woocommerce-jetpack' ),
			'_before'  => __( 'Before the price', 'woocommerce-jetpack' ),
			'_between' => __( 'Between regular and sale prices', 'woocommerce-jetpack' ),
			'_after'   => __( 'After the price', 'woocommerce-jetpack' ),
		);
		$this->custom_tab_section_variations          = array( '_text', '_enabled', '_home', '_products', '_single', '_page', '_cart', '_variable', '_variation' );
		$this->custom_tab_section_variations_titles   = array(
			'_text'      => '', // 'The label',
			'_enabled'   => __( 'Enable', 'woocommerce-jetpack' ),
			'_home'      => __( 'Hide on home page', 'woocommerce-jetpack' ),
			'_products'  => __( 'Hide on products page', 'woocommerce-jetpack' ),
			'_single'    => __( 'Hide on single', 'woocommerce-jetpack' ),
			'_page'      => __( 'Hide on all pages', 'woocommerce-jetpack' ),
			'_cart'      => __( 'Hide on cart page only', 'woocommerce-jetpack' ),
			'_variable'  => __( 'Hide for main price', 'woocommerce-jetpack' ),
			'_variation' => __( 'Hide for all variations', 'woocommerce-jetpack' ),
		);

		if ( $this->is_enabled() ) {

			if ( 'yes' === wcj_get_option( 'wcj_local_price_labels_enabled', 'yes' ) ) {
				// Meta box (admin)
				add_action( 'add_meta_boxes',    array( $this, 'add_price_label_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_custom_price_labels' ), 999, 2 );
			}

			// Prices Hooks
			$this->prices_filters = array(
				// Cart
				'woocommerce_cart_item_price',
				// Composite Products
				'woocommerce_composite_sale_price_html',
				'woocommerce_composite_price_html',
				'woocommerce_composite_empty_price_html',
				'woocommerce_composite_free_sale_price_html',
				'woocommerce_composite_free_price_html',
				// Booking Products
				'woocommerce_get_price_html',
				// Simple Products
				'woocommerce_empty_price_html',
				'woocommerce_free_price_html',
				'woocommerce_free_sale_price_html',
				'woocommerce_price_html',
				'woocommerce_sale_price_html',
				// Grouped Products
				'woocommerce_grouped_price_html',
				// Variable Products
				'woocommerce_variable_empty_price_html',
				'woocommerce_variable_free_price_html',
				'woocommerce_variable_free_sale_price_html',
				'woocommerce_variable_price_html',
				'woocommerce_variable_sale_price_html',
				// Variable Products - Variations
				'woocommerce_variation_empty_price_html',
				'woocommerce_variation_free_price_html',
				'woocommerce_variation_price_html',
				'woocommerce_variation_sale_price_html',
				// WooCommerce Subscription
				'woocommerce_subscriptions_product_price_string',
				'woocommerce_variable_subscription_price_html',
				// Entrada theme
				'entrada_price',
			);
			foreach ( $this->prices_filters as $the_filter ) {
				add_filter( $the_filter, array( $this, 'custom_price' ), 100, 2 );
			}
		}
	}

	/**
	 * save_custom_price_labels.
	 */
	function save_custom_price_labels( $post_id, $post ) {
		if ( ! isset( $_POST['woojetpack_price_labels_save_post'] ) ) {
			return;
		}
		foreach ( $this->custom_tab_sections as $custom_tab_section ) {
			foreach ( $this->custom_tab_section_variations as $custom_tab_section_variation ) {
				$option_name = $this->custom_tab_group_name . $custom_tab_section . $custom_tab_section_variation;
				if ( $custom_tab_section_variation == '_text' ) {
					if ( isset( $_POST[ $option_name ] ) ) {
						update_post_meta( $post_id, '_' . $option_name, $_POST[ $option_name ] );
					}
				} else {
					if ( isset( $_POST[ $option_name ] ) ) {
						update_post_meta( $post_id, '_' . $option_name, $_POST[ $option_name ] );
					} else {
						update_post_meta( $post_id, '_' . $option_name, 'off' );
					}
				}
			}
		}
	}

	/**
	 * add_price_label_meta_box.
	 *
	 * @version 2.4.8
	 */
	function add_price_label_meta_box() {
		add_meta_box(
			'wc-jetpack-price-labels',
			__( 'Booster: Custom Price Labels', 'woocommerce-jetpack' ),
			array( $this, 'create_price_label_meta_box' ),
			'product',
			'normal',
			'high'
		);
	}

	/*
	 * create_price_label_meta_box - back end.
	 *
	 * @version 2.4.8
	 */
	function create_price_label_meta_box() {
		$current_post_id = get_the_ID();
		echo '<table style="width:100%;">';
		echo '<tr>';
		foreach ( $this->custom_tab_sections as $custom_tab_section ) {
			echo '<td style="width:25%;"><h4>' . $this->custom_tab_sections_titles[ $custom_tab_section ] . '</h4></td>';
		}
		echo '</tr>';
		echo '<tr>';
		foreach ( $this->custom_tab_sections as $custom_tab_section ) {
			echo '<td style="width:25%;">';
			echo '<ul>';
			foreach ( $this->custom_tab_section_variations as $custom_tab_section_variation ) {
				$option_name = $this->custom_tab_group_name . $custom_tab_section . $custom_tab_section_variation;
				if ( $custom_tab_section_variation == '_text' ) {
					if ( $custom_tab_section != '_instead' ) {
						$disabled_if_no_plus = apply_filters( 'booster_message', '', 'readonly_string' );
					} else {
						$disabled_if_no_plus = '';
					}
					$label_text = get_post_meta( $current_post_id, '_' . $option_name, true );
					$label_text = str_replace ( '"', '&quot;', $label_text );
					echo '<li>' . $this->custom_tab_section_variations_titles[ $custom_tab_section_variation ] . '<textarea style="width:95%;min-width:100px;height:100px;" ' . $disabled_if_no_plus . ' name="' . $option_name . '">' . $label_text . '</textarea></li>';
				} else {
					if ( '_home' === $custom_tab_section_variation ) {
						echo '<li><h5>Hide by page type</h5></li>';
					}
					if ( '_variable' === $custom_tab_section_variation ) {
						echo '<li><h5>Variable products</h5></li>';
					}
					if ( '_instead' != $custom_tab_section ) {
						$disabled_if_no_plus = apply_filters( 'booster_message', '', 'disabled_string' );
					} else {
						$disabled_if_no_plus = '';
					}
					echo '<li><input class="checkbox" type="checkbox" ' . $disabled_if_no_plus . ' name="' . $option_name . '" id="' . $option_name . '" ' .
						checked( get_post_meta( $current_post_id, '_' . $option_name, true ), 'on', false ) . ' /> ' . $this->custom_tab_section_variations_titles[ $custom_tab_section_variation ] . '</li>';
				}
			}
			echo '</ul>';
			echo '</td>';
		}
		echo '</tr>';
		echo '<tr>';
		foreach ( $this->custom_tab_sections as $custom_tab_section ) {
			if ( '_instead' != $custom_tab_section )
				$disabled_if_no_plus = apply_filters( 'booster_message', '', 'desc_above' );
			else
				$disabled_if_no_plus = '';
			echo '<td style="width:25%;">' . $disabled_if_no_plus . '</td>';
		}
		echo '</tr>';
		echo '</table>';
		echo '<input type="hidden" name="woojetpack_price_labels_save_post" value="woojetpack_price_labels_save_post">';
	}

	/*
	 * customize_price.
	 *
	 * @todo    recheck if `str_replace( 'From: ', '', $price );` is necessary? Also this works only for English WP installs.
	 */
	function customize_price( $price, $custom_tab_section, $custom_label ) {
		switch ( $custom_tab_section ) {
			case '_instead':
				$price = $custom_label;
				break;
			case '_before':
				$price = apply_filters( 'booster_option', $price, $custom_label . $price );
				break;
			case '_between':
				$price = apply_filters( 'booster_option', $price, str_replace( '</del> <ins>', '</del>' . $custom_label . '<ins>', $price ) );
				break;
			case '_after':
				$price = apply_filters( 'booster_option', $price, $price . $custom_label );
				break;
		}
		return str_replace( 'From: ', '', $price );
	}

	/*
	 * custom_price - front end.
	 *
	 * @version 3.9.0
	 * @todo    rewrite this with less filters (e.g. `woocommerce_get_price_html` only) - at least for `! WCJ_IS_WC_VERSION_BELOW_3`
	 */
	function custom_price( $price, $product ) {

		if ( ! wcj_is_frontend() ) {
			return $price;
		}
		$current_filter_name = current_filter();
		if ( 'woocommerce_cart_item_price' === $current_filter_name ) {
			$product = $product['data'];
		} elseif ( 'entrada_price' === $current_filter_name ) {
			$product = wc_get_product();
		}
		$_product_id   = wcj_get_product_id_or_variation_parent_id( $product );
		$_product_type = ( WCJ_IS_WC_VERSION_BELOW_3 ? $product->product_type : $product->get_type() );
		if ( WCJ_IS_WC_VERSION_BELOW_3 && 'woocommerce_get_price_html' === $current_filter_name && ! in_array( $_product_type, apply_filters( 'wcj_price_labels_woocommerce_get_price_html_allowed_post_types', array( 'booking' ), $_product_type ) ) ) {
			return $price;
		}
		if ( ! WCJ_IS_WC_VERSION_BELOW_3 && 'woocommerce_variable_price_html' === $current_filter_name ) {
			return $price;
		}
		if ( ! WCJ_IS_WC_VERSION_BELOW_3 && 'woocommerce_get_price_html' === $current_filter_name && $product->is_type( 'variation' ) ) {
			$current_filter_name = 'woocommerce_variation_price_html';
		}
		if ( ! WCJ_IS_WC_VERSION_BELOW_3 && 'woocommerce_get_price_html' === $current_filter_name && $product->is_type( 'variable' ) ) {
			$current_filter_name = 'woocommerce_variable_price_html';
		}
		if ( 'subscription' === $_product_type && 'woocommerce_subscriptions_product_price_string' !== $current_filter_name ) {
			return $price;
		}
		if ( 'variable-subscription' === $_product_type && 'woocommerce_variable_subscription_price_html' !== $current_filter_name ) {
			return $price;
		}
		if ( 'subscription_variation' === $_product_type && 'woocommerce_subscriptions_product_price_string' !== $current_filter_name ) {
			return $price;
		}
		if ( 'subscription_variation' === $_product_type && 'woocommerce_subscriptions_product_price_string' === $current_filter_name ) {
			$current_filter_name = 'woocommerce_variation_subscription_price_html';
		}

		// Global
		$do_apply_global = true;
		$products_incl = wcj_get_option( 'wcj_global_price_labels_products_incl', array() );
		if ( ! empty( $products_incl ) ) {
			$do_apply_global = (   in_array( $_product_id, $products_incl ) );
		}
		$products_excl = wcj_get_option( 'wcj_global_price_labels_products_excl', array() );
		if ( ! empty( $products_excl ) ) {
			$do_apply_global = ( ! in_array( $_product_id, $products_excl ) );
		}
		$product_categories = get_the_terms( $_product_id, 'product_cat' );
		$product_categories_incl = wcj_get_option( 'wcj_global_price_labels_product_cats_incl', array() );
		if ( ! empty( $product_categories_incl ) ) {
			$do_apply_global = false;
			if ( ! empty( $product_categories ) ) {
				foreach ( $product_categories as $product_category ) {
					if ( in_array( $product_category->term_id, $product_categories_incl ) ) {
						$do_apply_global = true;
						break;
					}
				}
			}
		}
		$product_categories_excl = wcj_get_option( 'wcj_global_price_labels_product_cats_excl', array() );
		if ( ! empty( $product_categories_excl ) ) {
			$do_apply_global = true;
			if ( ! empty( $product_categories ) ) {
				foreach ( $product_categories as $product_category ) {
					if ( in_array( $product_category->term_id, $product_categories_excl ) ) {
						$do_apply_global = false;
						break;
					}
				}
			}
		}
		if ( $do_apply_global ) {
			// Check product type
			$product_types_incl = wcj_get_option( 'wcj_global_price_labels_product_types_incl', '' );
			if ( ! empty( $product_types_incl ) ) {
				$do_apply_global = false;
				foreach ( $product_types_incl as $product_type_incl ) {
					if ( $product->is_type( $product_type_incl ) ) {
						$do_apply_global = true;
						break;
					}
				}
			}
		}
		if ( $do_apply_global ) {
			// Global price labels - Add text before price
			$text_to_add_before = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_global_price_labels_add_before_text' ) );
			if ( '' != $text_to_add_before ) {
				if ( apply_filters( 'wcj_price_labels_check_on_applying_label', true, $price, $text_to_add_before ) ) {
					$price = $text_to_add_before . $price;
				}
			}
			// Global price labels - Add text after price
			$text_to_add_after = wcj_get_option( 'wcj_global_price_labels_add_after_text' );
			if ( '' != $text_to_add_after ) {
				if ( apply_filters( 'wcj_price_labels_check_on_applying_label', true, $price, $text_to_add_after ) ) {
					$price = $price . $text_to_add_after;
				}
			}
			// Global price labels - Between regular and sale prices
			$text_to_add_between_regular_and_sale = wcj_get_option( 'wcj_global_price_labels_between_regular_and_sale_text' );
			if ( '' != $text_to_add_between_regular_and_sale ) {
				$price = apply_filters( 'booster_option', $price, str_replace( '</del> <ins>', '</del>' . $text_to_add_between_regular_and_sale . '<ins>', $price ) );
			}
			// Global price labels - Remove text from price
			$text_to_remove = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_global_price_labels_remove_text' ) );
			if ( '' != $text_to_remove ) {
				$price = str_replace( $text_to_remove, '', $price );
			}
			// Global price labels - Replace in price
			$text_to_replace = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_global_price_labels_replace_text' ) );
			$text_to_replace_with = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_global_price_labels_replace_with_text' ) );
			if ( '' != $text_to_replace && '' != $text_to_replace_with ) {
				$price = str_replace( $text_to_replace, $text_to_replace_with, $price );
			}
			// Global price labels - Instead of the price
			if ( '' != ( $text_instead = wcj_get_option( 'wcj_global_price_labels_instead_text', '' ) ) ) {
				$price = $text_instead;
			}
		}

		// Per product
		if ( 'yes' === wcj_get_option( 'wcj_local_price_labels_enabled', 'yes' ) ) {
			foreach ( $this->custom_tab_sections as $custom_tab_section ) {
				$labels_array = array();
				foreach ( $this->custom_tab_section_variations as $custom_tab_section_variation ) {
					$option_name = $this->custom_tab_group_name . $custom_tab_section . $custom_tab_section_variation;
					$labels_array[ 'variation' . $custom_tab_section_variation ] = get_post_meta( $_product_id, '_' . $option_name, true );
				}
				if ( 'on' === $labels_array[ 'variation_enabled' ] ) {
					if (
						( 'on' === $labels_array['variation_home']      && is_front_page() ) ||
						( 'on' === $labels_array['variation_products']  && is_archive() ) ||
						( 'on' === $labels_array['variation_single']    && is_single() ) ||
						( 'on' === $labels_array['variation_page']      && is_page() && ! is_front_page() ) ||
						( 'on' === $labels_array['variation_cart']      && 'woocommerce_cart_item_price' === $current_filter_name ) ||
						( 'on' === $labels_array['variation_variable']  && in_array( $current_filter_name, array(
							'woocommerce_variable_empty_price_html',
							'woocommerce_variable_free_price_html',
							'woocommerce_variable_free_sale_price_html',
							'woocommerce_variable_price_html',
							'woocommerce_variable_sale_price_html',
							'woocommerce_variable_subscription_price_html',
						) ) ) ||
						( 'on' === $labels_array['variation_variation'] && in_array( $current_filter_name, array(
							'woocommerce_variation_empty_price_html',
							'woocommerce_variation_free_price_html',
							'woocommerce_variation_price_html',
							'woocommerce_variation_sale_price_html',
							'woocommerce_variation_subscription_price_html', // pseudo filter!
						) ) )
					) {
						continue;
					}
					$price = $this->customize_price( $price, $custom_tab_section, $labels_array['variation_text'] );
				}
			}
		}

		// Apply shortcodes
		WCJ()->shortcodes['products']->passed_product = $product;
		WCJ()->shortcodes['products_crowdfunding']->passed_product = $product;
		$result = do_shortcode( $price );
		unset( WCJ()->shortcodes['products']->passed_product );
		unset( WCJ()->shortcodes['products_crowdfunding']->passed_product );

		return $result;
	}

}

endif;

return new WCJ_Price_Labels();

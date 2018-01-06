<?php
/**
 * Booster for WooCommerce - Module - Product Open Pricing
 *
 * @version 2.9.0
 * @since   2.4.8
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Open_Pricing' ) ) :

class WCJ_Product_Open_Pricing extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.0
	 * @since   2.4.8
	 */
	function __construct() {

		$this->id         = 'product_open_pricing';
		$this->short_desc = __( 'Product Open Pricing (Name Your Price)', 'woocommerce-jetpack' );
		$this->desc       = __( 'Let your WooCommerce store customers enter price for the product manually.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-open-pricing-name-your-price';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'add_meta_boxes',                         array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product',                      array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			add_filter( WCJ_PRODUCT_GET_PRICE_FILTER,             array( $this, 'get_open_price' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_product_variation_get_price',array( $this, 'get_open_price' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_price_html',             array( $this, 'hide_original_price' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_variation_price_html',   array( $this, 'hide_original_price' ), PHP_INT_MAX, 2 );
			if ( 'yes' === get_option( 'wcj_product_open_price_disable_quantity', 'yes' ) ) {
				add_filter( 'woocommerce_is_sold_individually',   array( $this, 'hide_quantity_input_field' ), PHP_INT_MAX, 2 );
			}
			add_filter( 'woocommerce_is_purchasable',             array( $this, 'is_purchasable' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_product_supports',           array( $this, 'disable_add_to_cart_ajax' ), PHP_INT_MAX, 3 );
			add_filter( 'woocommerce_product_add_to_cart_url',    array( $this, 'add_to_cart_url' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_product_add_to_cart_text',   array( $this, 'add_to_cart_text' ), PHP_INT_MAX, 2 );
			add_action( 'woocommerce_before_add_to_cart_button',  array( $this, 'add_open_price_input_field_to_frontend' ), PHP_INT_MAX );
			add_filter( 'woocommerce_add_to_cart_validation',     array( $this, 'validate_open_price_on_add_to_cart' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_add_cart_item_data',         array( $this, 'add_open_price_to_cart_item_data' ), PHP_INT_MAX, 3 );
			add_filter( 'woocommerce_add_cart_item',              array( $this, 'add_open_price_to_cart_item' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_open_price_from_session' ), PHP_INT_MAX, 3 );
			add_filter( 'wcj_save_meta_box_value',                array( $this, 'save_meta_box_value' ), PHP_INT_MAX, 3 );
			add_action( 'admin_notices',                          array( $this, 'admin_notices' ) );
			if ( 'yes' === get_option( 'wcj_product_open_price_enable_loop_price_info', 'no' ) ) {
				$this->loop_price_info_template = get_option( 'wcj_product_open_price_loop_price_info_template', '<span class="price">%default_price%</span>' );
				add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'add_price_info_to_loop' ), 10 );
			}
			if ( is_admin() && 'yes' === get_option( 'wcj_product_open_price_enable_admin_product_list_column', 'no' ) ) {
				add_filter( 'manage_edit-product_columns',        array( $this, 'add_product_column_open_pricing' ),    PHP_INT_MAX );
				add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column_open_pricing' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * add_product_column_open_pricing.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function add_product_column_open_pricing( $columns ) {
		$columns['wcj_open_pricing'] = __( 'Open Pricing', 'woocommerce-jetpack' );
		return $columns;
	}

	/**
	 * render_product_column_open_pricing.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	*/
	function render_product_column_open_pricing( $column ) {
		if ( 'wcj_open_pricing' != $column ) {
			return;
		}
		if ( 'yes' === get_post_meta( get_the_ID(), '_' . 'wcj_product_open_price_enabled', true ) ) {
			echo '<span id="wcj_open_pricing_admin_column">&#10004;</span>';
		}
	}

	/**
	 * add_price_info_to_loop.
	 *
	 * @version 2.8.2
	 * @since   2.8.0
	 */
	function add_price_info_to_loop() {
		$_product_id = get_the_ID();
		if ( $this->is_open_price_product( $_product_id ) ) {
			$replaceable_values = array(
				'%default_price%' => wc_price( get_post_meta( $_product_id, '_' . 'wcj_product_open_price_default_price', true ) ),
				'%min_price%'     => wc_price( get_post_meta( $_product_id, '_' . 'wcj_product_open_price_min_price', true ) ),
				'%max_price%'     => wc_price( get_post_meta( $_product_id, '_' . 'wcj_product_open_price_max_price', true ) ),
			);
			echo str_replace( array_keys( $replaceable_values ), array_values( $replaceable_values ), $this->loop_price_info_template );
		}
	}

	/**
	 * is_open_price_product.
	 *
	 * @version 2.8.2
	 * @since   2.4.8
	 */
	function is_open_price_product( $_product ) {
		$_product_id = ( is_numeric( $_product ) ? $_product : wcj_get_product_id_or_variation_parent_id( $_product ) );
		return ( 'yes' === get_post_meta( $_product_id, '_' . 'wcj_product_open_price_enabled', true ) );
	}

	/**
	 * disable_add_to_cart_ajax.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function disable_add_to_cart_ajax( $supports, $feature, $_product ) {
		if ( $this->is_open_price_product( $_product ) && 'ajax_add_to_cart' === $feature ) {
			$supports = false;
		}
		return $supports;
	}

	/**
	 * save_meta_box_value.
	 *
	 * @version 2.5.0
	 * @since   2.4.8
	 */
	function save_meta_box_value( $option_value, $option_name, $module_id ) {
		if ( true === apply_filters( 'booster_option', false, true ) ) {
			return $option_value;
		}
		if ( 'no' === $option_value ) {
			return $option_value;
		}
		if ( $this->id === $module_id && 'wcj_product_open_price_enabled' === $option_name ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_key'       => '_' . 'wcj_product_open_price_enabled',
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
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function add_notice_query_var( $location ) {
		remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
		return add_query_arg( array( 'wcj_product_open_price_admin_notice' => true ), $location );
	}

	/**
	 * admin_notices.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function admin_notices() {
		if ( ! isset( $_GET['wcj_product_open_price_admin_notice'] ) ) {
			return;
		}
		?><div class="error"><p><?php
			echo '<div class="message">'
				. __( 'Booster: Free plugin\'s version is limited to only one open pricing product enabled at a time. You will need to get <a href="http://booster.io/plus/" target="_blank">Booster Plus</a> to add unlimited number of open pricing products.', 'woocommerce-jetpack' )
				. '</div>';
		?></p></div><?php
	}

	/**
	 * is_purchasable.
	 *
	 * @version 2.7.0
	 * @since   2.4.8
	 * @todo    maybe `wcj_get_product_id()` instead of `wcj_get_product_id_or_variation_parent_id()` - check `is_purchasable()` in `WC_Product` class.
	 */
	function is_purchasable( $purchasable, $_product ) {
		if ( $this->is_open_price_product( $_product ) ) {
			$purchasable = true;

			// Products must exist of course
			if ( ! $_product->exists() ) {
				$purchasable = false;

			// Other products types need a price to be set
			/* } elseif ( $_product->get_price() === '' ) {
				$purchasable = false; */

			// Check the product is published
			} elseif ( wcj_get_product_status( $_product ) !== 'publish' && ! current_user_can( 'edit_post', wcj_get_product_id_or_variation_parent_id( $_product ) ) ) {
				$purchasable = false;
			}
		}
		return $purchasable;
	}

	/**
	 * add_to_cart_text.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function add_to_cart_text( $text, $_product ) {
		return ( $this->is_open_price_product( $_product ) ) ? __( 'Read more', 'woocommerce' ) : $text;
	}

	/**
	 * add_to_cart_url.
	 *
	 * @version 2.7.0
	 * @since   2.4.8
	 */
	function add_to_cart_url( $url, $_product ) {
		return ( $this->is_open_price_product( $_product ) ) ? get_permalink( wcj_get_product_id_or_variation_parent_id( $_product ) ) : $url;
	}

	/**
	 * hide_quantity_input_field.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function hide_quantity_input_field( $return, $_product ) {
		return ( $this->is_open_price_product( $_product ) ) ? true : $return;
	}

	/**
	 * hide_original_price.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function hide_original_price( $price, $_product ) {
		return ( $this->is_open_price_product( $_product ) ) ? '' : $price;
	}

	/**
	 * get_open_price.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function get_open_price( $price, $_product ) {
		return ( $this->is_open_price_product( $_product ) && isset( $_product->wcj_open_price ) ) ? $_product->wcj_open_price : $price;
	}

	/**
	 * validate_open_price_on_add_to_cart.
	 *
	 * @version 2.6.0
	 * @since   2.4.8
	 */
	function validate_open_price_on_add_to_cart( $passed, $product_id ) {
		$the_product = wc_get_product( $product_id );
		if ( $this->is_open_price_product( $the_product ) ) {
			$min_price = get_post_meta( $product_id, '_' . 'wcj_product_open_price_min_price', true );
			$max_price = get_post_meta( $product_id, '_' . 'wcj_product_open_price_max_price', true );
			if ( $min_price > 0 ) {
				if ( ! isset( $_POST['wcj_open_price'] ) || '' === $_POST['wcj_open_price'] ) {
					wc_add_notice( get_option( 'wcj_product_open_price_messages_required', __( 'Price is required!', 'woocommerce-jetpack' ) ), 'error' );
					return false;
				}
				if ( $_POST['wcj_open_price'] < $min_price ) {
					wc_add_notice( get_option( 'wcj_product_open_price_messages_to_small', __( 'Entered price is too small!', 'woocommerce-jetpack' ) ), 'error' );
					return false;
				}
			}
			if ( $max_price > 0 ) {
				if ( isset( $_POST['wcj_open_price'] ) && $_POST['wcj_open_price'] > $max_price ) {
					wc_add_notice( get_option( 'wcj_product_open_price_messages_to_big', __( 'Entered price is too big!', 'woocommerce-jetpack' ) ), 'error' );
					return false;
				}
			}
		}
		return $passed;
	}

	/**
	 * get_cart_item_open_price_from_session.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function get_cart_item_open_price_from_session( $item, $values, $key ) {
		if ( array_key_exists( 'wcj_open_price', $values ) ) {
			$item['data']->wcj_open_price = $values['wcj_open_price'];
		}
		return $item;
	}

	/**
	 * add_open_price_to_cart_item_data.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function add_open_price_to_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		if ( isset( $_POST['wcj_open_price'] ) ) {
			$cart_item_data['wcj_open_price'] = $_POST['wcj_open_price'];
		}
		return $cart_item_data;
	}

	/**
	 * add_open_price_to_cart_item.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function add_open_price_to_cart_item( $cart_item_data, $cart_item_key ) {
		if ( isset( $cart_item_data['wcj_open_price'] ) ) {
			$cart_item_data['data']->wcj_open_price = $cart_item_data['wcj_open_price'];
		}
		return $cart_item_data;
	}

	/**
	 * add_open_price_input_field_to_frontend.
	 *
	 * @version 2.7.0
	 * @since   2.4.8
	 */
	function add_open_price_input_field_to_frontend() {
		$the_product = wc_get_product();
		if ( $this->is_open_price_product( $the_product ) ) {
			// Title
			$title = get_option( 'wcj_product_open_price_label_frontend', __( 'Name Your Price', 'woocommerce-jetpack' ) );
			// Prices
			$_product_id = wcj_get_product_id_or_variation_parent_id( $the_product );
			$min_price     = get_post_meta( $_product_id, '_' . 'wcj_product_open_price_min_price', true );
			$max_price     = get_post_meta( $_product_id, '_' . 'wcj_product_open_price_max_price', true );
			$default_price = get_post_meta( $_product_id, '_' . 'wcj_product_open_price_default_price', true );
			// Input field
			$value = ( isset( $_POST['wcj_open_price'] ) ) ? $_POST['wcj_open_price'] : $default_price;
			$default_price_step = 1 / pow( 10, absint( get_option( 'woocommerce_price_num_decimals', 2 ) ) );
			$custom_attributes = '';
			$custom_attributes .= 'step="' . get_option( 'wcj_product_open_price_price_step', $default_price_step ) . '" ';
			$custom_attributes .= ( '' == $min_price || 'no' === get_option( 'wcj_product_open_price_enable_js_validation', 'no' ) ) ? 'min="0" ' : 'min="' . $min_price . '" ';
			$custom_attributes .= ( '' == $max_price || 'no' === get_option( 'wcj_product_open_price_enable_js_validation', 'no' ) ) ? ''         : 'max="' . $max_price . '" ';
			$input_field = '<input '
				. 'type="number" '
				. 'class="text" '
				. 'style="' . get_option( 'wcj_product_open_price_input_style', 'width:75px;text-align:center;' ). '" '
				. 'name="wcj_open_price" '
				. 'id="wcj_open_price" '
				. 'placeholder="' . get_option( 'wcj_product_open_price_input_placeholder', '' ) . '" '
				. 'value="' . $value . '" '
				. $custom_attributes . '>';
			// Currency symbol
			$currency_symbol = get_woocommerce_currency_symbol();
			// Replacing final values
			$replacement_values = array(
				'%frontend_label%'       => $title,
				'%open_price_input%'     => $input_field,
				'%currency_symbol%'      => $currency_symbol,
				'%min_price_simple%'     => $min_price,
				'%max_price_simple%'     => $max_price,
				'%default_price_simple%' => $default_price,
				'%min_price%'            => wc_price( $min_price ),
				'%max_price%'            => wc_price( $max_price ),
				'%default_price%'        => wc_price( $default_price ),
			);
			echo str_replace(
				array_keys( $replacement_values ),
				array_values( $replacement_values ),
				get_option( 'wcj_product_open_price_frontend_template', '<label for="wcj_open_price">%frontend_label%</label> %open_price_input% %currency_symbol%' )
			);
		}
	}

}

endif;

return new WCJ_Product_Open_Pricing();

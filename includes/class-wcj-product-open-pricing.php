<?php
/**
 * WooCommerce Jetpack Product Open Pricing
 *
 * The WooCommerce Jetpack Product Open Pricing class.
 *
 * @version 2.5.1
 * @since   2.4.8
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Open_Pricing' ) ) :

class WCJ_Product_Open_Pricing extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 * @since   2.4.8
	 */
	function __construct() {

		$this->id         = 'product_open_pricing';
		$this->short_desc = __( 'Product Open Pricing (Name Your Price)', 'woocommerce-jetpack' );
		$this->desc       = __( 'Let your WooCommerce store customers enter price for the product manually.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-open-pricing-name-your-price/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'add_meta_boxes',                         array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product',                      array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_price',                  array( $this, 'get_open_price' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_price_html',             array( $this, 'hide_original_price' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_variation_price_html',   array( $this, 'hide_original_price' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_is_sold_individually',       array( $this, 'hide_quantity_input_field' ), PHP_INT_MAX, 2 );
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
		}
	}

	/**
	 * is_open_price_product.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function is_open_price_product( $_product ) {
		return ( 'yes' === get_post_meta( $_product->id, '_' . 'wcj_product_open_price_enabled', true ) ) ? true : false;
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
		if ( true === apply_filters( 'wcj_get_option_filter', false, true ) ) {
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
	 * @version 2.4.8
	 * @since   2.4.8
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
			} elseif ( $_product->post->post_status !== 'publish' && ! current_user_can( 'edit_post', $_product->id ) ) {
				$purchasable = false;
			}
		}
		return $purchasable;
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function get_meta_box_options() {
		$options = array(
			array(
				'name'       => 'wcj_product_open_price_enabled',
				'default'    => 'no',
				'type'       => 'select',
				'options'    => array(
					'yes' => __( 'Yes', 'woocommerce-jetpack' ),
					'no'  => __( 'No', 'woocommerce-jetpack' ),
				),
				'title'      => __( 'Enabled', 'woocommerce-jetpack' ),
			),
			array(
				'name'       => 'wcj_product_open_price_default_price',
				'default'    => '',
				'type'       => 'number',
				'title'      => __( 'Default Price', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			),
			array(
				'name'       => 'wcj_product_open_price_min_price',
				'default'    => 1,
				'type'       => 'number',
				'title'      => __( 'Min Price', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			),
			array(
				'name'       => 'wcj_product_open_price_max_price',
				'default'    => '',
				'type'       => 'number',
				'title'      => __( 'Max Price', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			),
		);
		return $options;
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
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function add_to_cart_url( $url, $_product ) {
		return ( $this->is_open_price_product( $_product ) ) ? get_permalink( $_product->id ) : $url;
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
	 * @version 2.4.8
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
					wc_add_notice( get_option( 'wcj_product_open_price_messages_to_small', __( 'Entered price is to small!', 'woocommerce-jetpack' ) ), 'error' );
					return false;
				}
			}
			if ( $max_price > 0 ) {
				if ( isset( $_POST['wcj_open_price'] ) && $_POST['wcj_open_price'] > $max_price ) {
					wc_add_notice( get_option( 'wcj_product_open_price_messages_to_big', __( 'Entered price is to big!', 'woocommerce-jetpack' ) ), 'error' );
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
	 * @version 2.5.1
	 * @since   2.4.8
	 */
	function add_open_price_input_field_to_frontend() {
		$the_product = wc_get_product();
		if ( $this->is_open_price_product( $the_product ) ) {
			// Title
			$title = get_option( 'wcj_product_open_price_label_frontend', __( 'Name Your Price', 'woocommerce-jetpack' ) );
			// Input field
			$value = ( isset( $_POST['wcj_open_price'] ) ) ? $_POST['wcj_open_price'] : get_post_meta( $the_product->id, '_' . 'wcj_product_open_price_default_price', true );
//			$placeholder = $the_product->get_price();
			$custom_attributes = '';
			$wc_price_decimals = wc_get_price_decimals();
			if ( $wc_price_decimals > 0 ) {
				$custom_attributes .= sprintf( 'step="0.%0' . ( $wc_price_decimals ) . 'd" ', 1 );
			}
			$input_field = '<input '
				. 'type="number" '
				. 'class="text" '
				. 'style="width:75px;text-align:center;" '
				. 'name="wcj_open_price" '
				. 'id="wcj_open_price" '
//				. 'placeholder="' . $placeholder . '" '
				. 'value="' . $value . '" '
				. $custom_attributes . '>';
			// Currency symbol
			$currency_symbol = get_woocommerce_currency_symbol();
			echo str_replace(
				array( '%frontend_label%', '%open_price_input%', '%currency_symbol%' ),
				array( $title,             $input_field,         $currency_symbol ),
				get_option( 'wcj_product_open_price_frontend_template', '<label for="wcj_open_price">%frontend_label%</label> %open_price_input% %currency_symbol%' )
			);
		}
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.1
	 * @since   2.4.8
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Labels and Messages', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_open_price_messages_options',
			),
			array(
				'title'    => __( 'Frontend Label', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_open_price_label_frontend',
				'default'  => __( 'Name Your Price', 'woocommerce-jetpack' ),
				'type'     => 'text',
				'css'      => 'width:250px;',
			),
			array(
				'title'    => __( 'Frontend Template', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Here you can use' ) . ': ' . '%frontend_label%, %open_price_input%, %currency_symbol%',
				'id'       => 'wcj_product_open_price_frontend_template',
				'default'  => '<label for="wcj_open_price">%frontend_label%</label> %open_price_input% %currency_symbol%',
				'type'     => 'textarea',
				'css'      => 'min-width:300px;width:50%;',
			),
			array(
				'title'    => __( 'Message on Empty Price', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_open_price_messages_required',
				'default'  => __( 'Price is required!', 'woocommerce-jetpack' ),
				'type'     => 'text',
				'css'      => 'width:250px;',
			),
			array(
				'title'    => __( 'Message on Price to Small', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_open_price_messages_to_small',
				'default'  => __( 'Entered price is to small!', 'woocommerce-jetpack' ),
				'type'     => 'text',
				'css'      => 'width:250px;',
			),
			array(
				'title'    => __( 'Message on Price to Big', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_open_price_messages_to_big',
				'default'  => __( 'Entered price is to big!', 'woocommerce-jetpack' ),
				'type'     => 'text',
				'css'      => 'width:250px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_open_price_messages_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Product_Open_Pricing();

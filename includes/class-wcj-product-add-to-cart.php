<?php
/**
 * WooCommerce Jetpack Product Add To Cart
 *
 * The WooCommerce Jetpack Product Add To Cart class.
 *
 * @version 2.5.3
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Add_To_Cart' ) ) :

class WCJ_Product_Add_To_Cart extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.3
	 */
	public function __construct() {

		$this->id         = 'product_add_to_cart';
		$this->short_desc = __( 'Product Add to Cart', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set any local url to redirect to on WooCommerce Add to Cart.', 'woocommerce-jetpack' )
			. ' ' . __( 'Automatically add to cart on product visit.', 'woocommerce-jetpack' )
			. ' ' . __( 'Display radio buttons instead of drop box for variable products.', 'woocommerce-jetpack' )
			. ' ' . __( 'Disable quantity input.', 'woocommerce-jetpack' )
			. ' ' . __( 'Disable add to cart button on per product basis.', 'woocommerce-jetpack' )
			. ' ' . __( 'Open external products on add to cart in new window.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-add-to-cart/';
		parent::__construct();

		if ( $this->is_enabled() ) {

			if ( 'yes' === get_option( 'wcj_add_to_cart_redirect_enabled' ) ) {
				add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'redirect_to_url' ), 100 );
			}

			if ( 'yes' === get_option( 'wcj_add_to_cart_on_visit_enabled' ) ) {
				add_action( 'woocommerce_before_single_product', array( $this, 'add_to_cart_on_visit' ), 100 );
			}

			// Variable Add to Cart Template
			if ( 'yes' === apply_filters( 'wcj_get_option_filter', 'wcj', get_option( 'wcj_add_to_cart_variable_as_radio_enabled', 'no' ) ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_variable_add_to_cart_scripts' ) );
				add_filter( 'wc_get_template', array( $this, 'change_variable_add_to_cart_template' ), PHP_INT_MAX, 5 );
			}

			// Quantity
			if ( 'yes' === get_option( 'wcj_add_to_cart_quantity_disable', 'no' ) || 'yes' === get_option( 'wcj_add_to_cart_quantity_disable_cart', 'no' ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_disable_quantity_add_to_cart_script' ) );
			}

			// Button per product
			if ( 'yes' === get_option( 'wcj_add_to_cart_button_per_product_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

				add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_to_cart_button_disable_start' ), PHP_INT_MAX, 0 );
				add_action( 'woocommerce_after_add_to_cart_button',  array( $this, 'add_to_cart_button_disable_end' ), PHP_INT_MAX, 0 );
				add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'add_to_cart_button_loop_disable' ), PHP_INT_MAX, 2 );
			}

			// External Products
			if ( 'yes' === get_option( 'wcj_add_to_cart_button_external_open_new_window_single', 'no' ) ) {
				add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'replace_external_with_custom_add_to_cart_on_single_start' ), PHP_INT_MAX );
				add_action( 'woocommerce_after_add_to_cart_button',  array( $this, 'replace_external_with_custom_add_to_cart_on_single_end' ), PHP_INT_MAX );
			}
			if ( 'yes' === get_option( 'wcj_add_to_cart_button_external_open_new_window_loop', 'no' ) ) {
				add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'replace_external_with_custom_add_to_cart_in_loop' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * replace_external_with_custom_add_to_cart_on_single_start.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function replace_external_with_custom_add_to_cart_on_single_start() {
		global $product;
		if ( $product->is_type( 'external' ) ) {
			ob_start();
		}
	}

	/**
	 * replace_external_with_custom_add_to_cart_on_single_end.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function replace_external_with_custom_add_to_cart_on_single_end() {
		global $product;
		if ( $product->is_type( 'external' ) ) {
			$button_html = ob_get_contents();
			ob_end_clean();
			echo str_replace( '<a href=', '<a target="_blank" href=', $button_html );
		}
	}

	/**
	 * replace_external_with_custom_add_to_cart_in_loop.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function replace_external_with_custom_add_to_cart_in_loop( $link_html ) {
		global $product;
		if ( $product->is_type( 'external' ) ) {
			$link_html = str_replace( '<a rel=', '<a target="_blank" rel=', $link_html );
		}
		return $link_html;
	}

	/**
	 * add_to_cart_button_loop_disable.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_to_cart_button_loop_disable( $link, $_product ) {
		if ( 0 != get_the_ID() && 'yes' === get_post_meta( get_the_ID(), '_' . 'wcj_add_to_cart_button_loop_disable', true ) ) {
			return '';
		}
		return $link;
	}

	/**
	 * add_to_cart_button_disable_end.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_to_cart_button_disable_end() {
		if ( 0 != get_the_ID() && 'yes' === get_post_meta( get_the_ID(), '_' . 'wcj_add_to_cart_button_disable', true ) ) {
			ob_end_clean();
		}
	}

	/**
	 * add_to_cart_button_disable_start.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_to_cart_button_disable_start() {
		if ( 0 != get_the_ID() && 'yes' === get_post_meta( get_the_ID(), '_' . 'wcj_add_to_cart_button_disable', true ) ) {
			ob_start();
		}
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function get_meta_box_options() {
		$options = array(
			array(
				'name'       => 'wcj_add_to_cart_button_disable',
				'default'    => 'no',
				'type'       => 'select',
				'options'    => array(
					'yes' => __( 'Yes', 'woocommerce-jetpack' ),
					'no'  => __( 'No', 'woocommerce-jetpack' ),
				),
				'title'      => __( 'Disable Add to Cart Button (Single Product Page)', 'woocommerce-jetpack' ),
			),
			array(
				'name'       => 'wcj_add_to_cart_button_loop_disable',
				'default'    => 'no',
				'type'       => 'select',
				'options'    => array(
					'yes' => __( 'Yes', 'woocommerce-jetpack' ),
					'no'  => __( 'No', 'woocommerce-jetpack' ),
				),
				'title'      => __( 'Disable Add to Cart Button (Category/Archives)', 'woocommerce-jetpack' ),
			),
		);
		return $options;
	}

	/**
	 * enqueue_disable_quantity_add_to_cart_script.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function enqueue_disable_quantity_add_to_cart_script() {
		if (
			( 'yes' === get_option( 'wcj_add_to_cart_quantity_disable', 'no' ) && is_product() ) ||
			( 'yes' === get_option( 'wcj_add_to_cart_quantity_disable_cart', 'no' ) && is_cart() )
		) {
			wp_enqueue_script( 'wcj-disable-quantity', wcj_plugin_url() . '/includes/js/wcj-disable-quantity.js', array( 'jquery' ) );
		}
	}

	/**
	 * enqueue_variable_add_to_cart_scripts.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function enqueue_variable_add_to_cart_scripts() {
		wp_enqueue_script( 'wcj-variations', wcj_plugin_url() . '/includes/js/wcj-variations-frontend.js', array( 'jquery' ) );
	}

	/**
	 * change_variable_add_to_cart_template.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function change_variable_add_to_cart_template( $located, $template_name, $args, $template_path, $default_path ) {
		if ( 'single-product/add-to-cart/variable.php' == $template_name ) {
			$located = untrailingslashit( realpath( plugin_dir_path( __FILE__ ) . '/..' ) ) . '/includes/templates/wcj-add-to-cart-variable.php';
		}
		return $located;
	}
	/*
	 * redirect_to_url.
	 */
	function redirect_to_url( $url ) {
		global $woocommerce;
		$checkout_url = get_option( 'wcj_add_to_cart_redirect_url' );
		if ( '' === $checkout_url ) {
			$checkout_url = $woocommerce->cart->get_checkout_url();
		}
		return $checkout_url;
	}

	/*
	 * Add item to cart on visit.
	 */
	function add_to_cart_on_visit() {
		if ( is_product() ) {
			global $woocommerce;
			$product_id = get_the_ID();
			$found = false;
			//check if product already in cart
			if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
				foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
					$_product = $values['data'];
					if ( $_product->id == $product_id ) {
						$found = true;
					}
				}
				// if product not found, add it
				if ( ! $found ) {
					$woocommerce->cart->add_to_cart( $product_id );
				}
			} else {
				// if no products in cart, add it
				$woocommerce->cart->add_to_cart( $product_id );
			}
		}
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.3
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Add to Cart Local Redirect Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you set any local URL to redirect to after successfully adding product to cart. Leave empty to redirect to checkout page (skipping the cart page).', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_redirect_options',
			),
			array(
				'title'    => __( 'Local Redirect', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_redirect_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Local Redirect URL', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Performs a safe (local) redirect, using wp_redirect().', 'woocommerce-jetpack' ),
				'desc'     => __( 'Local redirect URL. Leave empty to redirect to checkout.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_redirect_url',
				'default'  => '',
				'type'     => 'text',
				'css'      => 'width:50%;min-width:300px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_add_to_cart_redirect_options',
			),
			array(
				'title'    => __( 'Add to Cart on Visit', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you enable automatically adding product to cart on visiting the product page. Product is only added once, so if it is already in cart - duplicate product is not added. ', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_on_visit_options',
			),
			array(
				'title'    => __( 'Add to Cart on Visit', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_on_visit_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_add_to_cart_on_visit_options',
			),
			array(
				'title'    => __( 'Add to Cart Variable Product', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_add_to_cart_variable_options',
			),
			array(
				'title'    => __( 'Display Radio Buttons Instead of Drop Box', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_variable_as_radio_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
				'desc_tip' => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_add_to_cart_variable_options',
			),
			array(
				'title'    => __( 'Add to Cart Quantity', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_add_to_cart_quantity_options',
			),
			array(
				'title'    => __( 'Disable Quantity Field for All Products', 'woocommerce-jetpack' ),
				'desc'     => __( 'Disable on Single Product Page', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_quantity_disable',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'start',
			),
			array(
				'desc'     => __( 'Disable on Cart Page', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_quantity_disable_cart',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'end',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_add_to_cart_quantity_options',
			),
			array(
				'title'    => __( 'Add to Cart Button', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_add_to_cart_button_options',
			),
			array(
				'title'    => __( 'Enable/Disable Add to Cart Buttons on per Product Basis', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'This will add meta box to each product\'s edit page', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_button_per_product_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_add_to_cart_button_options',
			),
			array(
				'title'    => __( 'External Products', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_add_to_cart_button_external_product_options',
			),
			array(
				'title'    => __( 'Open External Products on Add to Cart in New Window', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable on Single Product Pages', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_button_external_open_new_window_single',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'start',
			),
			array(
				'desc'     => __( 'Enable on Category/Archive Pages', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_button_external_open_new_window_loop',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'end',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_add_to_cart_button_external_product_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Product_Add_To_Cart();

<?php
/**
 * WooCommerce Jetpack Product Add To Cart
 *
 * The WooCommerce Jetpack Product Add To Cart class.
 *
 * @version 2.5.2
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Add_To_Cart' ) ) :

class WCJ_Product_Add_To_Cart extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.2
	 */
	public function __construct() {

		$this->id         = 'product_add_to_cart';
		$this->short_desc = __( 'Product Add to Cart', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set any local url to redirect to on WooCommerce Add to Cart. Automatically add to cart on product visit. Display radio buttons instead of drop box for variable products.', 'woocommerce-jetpack' );
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
		}
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
	 * @version 2.5.2
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
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Product_Add_To_Cart();

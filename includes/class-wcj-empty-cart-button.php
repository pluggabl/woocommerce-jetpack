<?php
/**
 * WooCommerce Jetpack Empty Cart Button
 *
 * The WooCommerce Jetpack Empty Cart Button class.
 *
 * @version 2.2.1
 * @since   2.2.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Empty_Cart_Button' ) ) :

class WCJ_Empty_Cart_Button extends WCJ_Module {

	/**
	 * Constructor.
	 */
	function __construct() {

		$this->id         = 'empty_cart';
		$this->short_desc = __( 'Empty Cart Button', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add and customize "Empty Cart" button to cart page.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {

			add_action( 'init', array( $this, 'empty_cart' ) );
			//add_action( get_option( 'wcj_empty_cart_position', 'woocommerce_after_cart' ), array( $this, 'add_empty_cart_link' ) );
			add_action( apply_filters( 'wcj_get_option_filter', 'woocommerce_after_cart', get_option( 'wcj_empty_cart_position', 'woocommerce_after_cart' ) ),
						array( $this, 'add_empty_cart_link' ) );

			//add_action( 'wp_head', array( $this, 'add_confirmation_script' ) );

			//add_filter( 'wcj_empty_cart_button_filter', array( $this, 'empty_cart_button_filter_function' ), 100, 2 );

		}
	}

	/**
	 * add_confirmation_script.
	 *
	function add_confirmation_script() {
		?><script type="text/javascript">
		function clicked() {
		   if (confirm('Do you wanna to submit?')) {
			   yourformelement.submit();
		   } else {
			   return false;
		   }
		}
		</script><?
	}

	/*
	 * empty_cart_button_filter_function.
	 *
	public function empty_cart_button_filter_function ( $value, $type ) {

		if ( 'text' == $type ) return 'Empty Cart';
		if ( 'div-style' == $type ) return 'float: right';
	}

	/**
	 * add_empty_cart_link.
	 */
	public function add_empty_cart_link() {
		$confirmation_html = ( 'confirm_with_pop_up_box' == get_option( 'wcj_empty_cart_confirmation', 'no_confirmation' ) ) ? ' onclick="return confirm(\'' . get_option( 'wcj_empty_cart_confirmation_text' ) . '\')"' : '';
		echo '<div style="' . apply_filters( 'wcj_get_option_filter', 'float: right;', get_option( 'wcj_empty_cart_div_style' ) ) . '"><form action="" method="post"><input type="submit" class="button" name="empty_cart" value="' . apply_filters( 'wcj_get_option_filter', 'Empty Cart', get_option( 'wcj_empty_cart_text' ) ) . '"' . $confirmation_html . '></form></div>';
		//echo '<input type="submit" class="button" name="empty_cart" value="' . apply_filters( 'wcj_get_option_filter', 'Empty Cart', get_option( 'wcj_empty_cart_text' ) ) . '">';
	}

	/**
	 * empty_cart.
	 */
	public function empty_cart() {

		if ( isset( $_POST['empty_cart'] ) ) {

			global $woocommerce;
			$woocommerce->cart->empty_cart();
		}
	}

	/**
	 * get_settings.
	 */
	function get_settings() {

		$settings = array();

		$settings = array_merge( $settings, array(

			array( 'title' => __( 'Empty Cart Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'This section lets you add and customize "Empty Cart" button to cart page.', 'woocommerce-jetpack' ), 'id' => 'wcj_empty_cart_customization_options' ),

			/* array(
				'title'    => __( 'Empty Cart', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_empty_cart_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
			), */

			array(
				'title'    => __( 'Empty Cart Button Text', 'woocommerce-jetpack' ),
				'id'       => 'wcj_empty_cart_text',
				'default'  => 'Empty Cart',
				'type'     => 'text',
				'desc' 	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'
						   => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),

			array(
				'title'    => __( 'Wrapping DIV style', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Style for the button\'s div. Default is "float: right;"', 'woocommerce-jetpack' ),
				'id'       => 'wcj_empty_cart_div_style',
				'default'  => 'float: right;',
				'type'     => 'text',
				/*'desc' 	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'
						   => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),*/
			),

			array(
				'title'    => __( 'Button position on the Cart page', 'woocommerce-jetpack' ),
				'id'       => 'wcj_empty_cart_position',
				'default'  => 'woocommerce_after_cart',
				'type'     => 'select',
				'options'  => array(
					'woocommerce_after_cart'          => __( 'After Cart', 'woocommerce-jetpack' ),
					'woocommerce_before_cart'         => __( 'Before Cart', 'woocommerce-jetpack' ),
					'woocommerce_proceed_to_checkout' => __( 'After Proceed to Checkout button', 'woocommerce-jetpack' ),
					'woocommerce_after_cart_totals'   => __( 'After Cart Totals', 'woocommerce-jetpack' ),
				),
				'desc' 	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'
						   => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
			),

			array(
				'title'    => __( 'Confirmation', 'woocommerce-jetpack' ),
				'id'       => 'wcj_empty_cart_confirmation',
				'default'  => 'no_confirmation',
				'type'     => 'select',
				'options'  => array(
					'no_confirmation'          => __( 'No confirmation', 'woocommerce-jetpack' ),
					'confirm_with_pop_up_box'  => __( 'Confirm by pop up box', 'woocommerce-jetpack' ),
				),
				/* 'desc' 	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'
						   => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ), */
			),

			array(
				'title'    => __( 'Confirmation Text (if enabled)', 'woocommerce-jetpack' ),
				'id'       => 'wcj_empty_cart_confirmation_text',
				'default'  => __( 'Are you sure?', 'woocommerce-jetpack' ),
				'type'     => 'text',
				/* 'desc' 	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'
						   => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ), */
			),

			array( 'type'  => 'sectionend', 'id' => 'wcj_empty_cart_customization_options' ),

		) );

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_Empty_Cart_Button();

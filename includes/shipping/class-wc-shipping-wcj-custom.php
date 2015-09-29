<?php
/**
 * class WC_Shipping_WCJ_Custom
 */
add_action( 'woocommerce_shipping_init', 'init_wc_shipping_wcj_custom_class' );

function init_wc_shipping_wcj_custom_class() {
	if ( ! class_exists( 'WC_Shipping_WCJ_Custom' ) ) {
		class WC_Shipping_WCJ_Custom extends WC_Shipping_Method {
			/**
			 * Constructor for your shipping class
			 *
			 * @access public
			 * @return void
			 */
			public function __construct() {
				$this->id                 	= 'jetpack_custom_shipping';
				//$this->title       		= __( 'Custom', 'woocommerce-jetpack' );
				$this->method_title 	  	= __( 'Custom Shipping', 'woocommerce-jetpack' );
				$this->method_description 	= __( 'WooCommerce Jetpack: Custom Shipping Method', 'woocommerce-jetpack' );
				//$this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
				$this->init();
				
				$this->enabled 				= $this->settings['enabled'];//$this->get_option( 'enabled' );
				$this->title        		= $this->settings['title'];//$this->get_option( 'title' );
		
				// Define user set variables
				//$this->title        		= $this->get_option( 'title' );
				////$this->description  		= $this->get_option( 'description' );
				////$this->instructions 		= $this->get_option( 'instructions', $this->description );
				////$this->icon					= $this->get_option( 'icon', '' );//apply_filters( 'woocommerce_wcj_custom_icon', $this->get_option( 'icon', '' ) );
			}

			/**
			 * Init your settings
			 *
			 * @access public
			 * @return void
			 */
			function init() {
				// Load the settings API
				$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
				$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

				// Save settings in admin if you have any defined
				add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
			}
			
			/**
			 * Initialise Settings Form Fields
			 */
			public function init_form_fields() {		
				
				$this->form_fields = array(
					'enabled' => array(
						'title'   => __( 'Enable/Disable', 'woocommerce' ),
						'type'    => 'checkbox',
						'label'   => __( 'Enable Custom Shipping', 'woocommerce-jetpack' ),
						'default' => 'no'
					),
					'title' => array(
						'title'       => __( 'Title', 'woocommerce' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
						'default'     => __( 'Custom Shipping', 'woocommerce-jetpack' ),
						'desc_tip'    => true,
					),					
				);
			}					

			/**
			 * calculate_shipping function.
			 *
			 * @access public
			 * @param mixed $package
			 * @return void
			 */
			public function calculate_shipping( $package ) {
				$rate = array(
					'id'       => $this->id,
					'label'    => $this->title,
					'cost'     => '4.99',
					'calc_tax' => 'per_order'
				);

				// Register the rate
				$this->add_rate( $rate );
			}
		}
	}
}

function add_wc_shipping_wcj_custom_class( $methods ) {
	$methods[] = 'WC_Shipping_WCJ_Custom'; 
	return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_wc_shipping_wcj_custom_class' );
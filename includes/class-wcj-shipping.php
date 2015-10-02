<?php
/**
 * WooCommerce Jetpack Shipping
 *
 * The WooCommerce Jetpack Shipping class.
 *
 * @class		WCJ_Shipping
 * @version		1.0.0
 * @category	Class
 * @author 		Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
if ( ! class_exists( 'WCJ_Shipping' ) ) :
 
class WCJ_Shipping {
	
	/**
	 * Constructor.
	 */
	public function __construct() {	
	    if ( get_option( 'wcj_shipping_enabled' ) == 'yes' ) {
			// Include custom shipping method
			//include_once( 'shipping/class-wc-shipping-wcj-custom.php' );			
			// Main hooks
			//add_filter( 'woocommerce_available_shipping_methods', array( $this, 'hide_all_shipping_when_free_is_available' ), 10, 1 );
			add_filter( 'woocommerce_package_rates', array( $this, 'hide_shipping_when_free_is_available' ), 10, 2 );			
			// Settings
			add_filter( 'woocommerce_shipping_settings', array( $this, 'add_hide_shipping_if_free_available_fields' ), 100 );				
	    }    
	    // Settings hooks
	    add_filter( 'wcj_settings_sections', array( $this, 'settings_section' ) );
	    add_filter( 'wcj_settings_shipping', array( $this, 'get_settings' ), 100 );
	    add_filter( 'wcj_features_status', array( $this, 'add_enabled_option' ), 100 );
	}
	
	/**
	 * add_enabled_option.
	 */
	public function add_enabled_option( $settings ) {
	
	    $all_settings = $this->get_settings();
	    $settings[] = $all_settings[1];
	    
	    return $settings;
	}
	
	function add_hide_shipping_if_free_available_fields( $settings ) {

		$updated_settings = array();

		foreach ( $settings as $section ) {
		  
			if ( isset( $section['id'] ) && 'woocommerce_shipping_method_format' == $section['id'] ) { 
				 //( isset( $section['type'] ) && 'title' == $section['type'] ) ) {
				 
				// $updated_settings[] = 
				//	array( 'title' => __( 'Hide if free is available', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( '', 'woocommerce-jetpack' ), 'id' => 'wcj_shipping_hide_if_free_available_options' );
					
				$updated_settings[] = 
					array(
						'title'    => __( 'WooCommerce Jetpack: Hide shipping', 'woocommerce-jetpack' ),
						'desc'     => __( 'Hide local delivery when free is available', 'woocommerce-jetpack' ),
						'desc_tip' => __( '', 'woocommerce-jetpack' ),
						'id'       => 'wcj_shipping_hide_if_free_available_local_delivery',
						'default'  => 'no',
						'type'     => 'checkbox',
						'checkboxgroup'   => 'start',
					);

				$updated_settings[] = 
					array(
						//'title'    => __( 'Hide all', 'woocommerce-jetpack' ),
						'desc'     => __( 'Hide all when free is available', 'woocommerce-jetpack' ),
						'desc_tip' => __( '', 'woocommerce-jetpack' ),
						'id'       => 'wcj_shipping_hide_if_free_available_all',
						'default'  => 'no',
						'type'     => 'checkbox',
						'checkboxgroup'   => 'end',
					);
					
				//$updated_settings[] = 
				//	array( 'type'  => 'sectionend', 'id' => 'wcj_shipping_hide_if_free_available_options' );
			
			}
			
			$updated_settings[] = $section;
		}
	  
		return $updated_settings;
	}	
	
	function hide_shipping_when_free_is_available( $rates, $package ) {
	
		//print_r( $rates );
		
		// Only modify rates if free_shipping is present
		if ( isset( $rates['free_shipping'] ) ) {
		
			// To unset a single rate/method, do the following. This example unsets flat_rate shipping
			if ( get_option( 'wcj_shipping_hide_if_free_available_local_delivery' ) == 'yes' )
				unset( $rates['local_delivery'] );
			
			if ( get_option( 'wcj_shipping_hide_if_free_available_all' ) == 'yes' ) {

				// To unset all methods except for free_shipping, do the following
				$free_shipping          = $rates['free_shipping'];
				//unset( $rates );
				$rates                  = array();
				$rates['free_shipping'] = $free_shipping;
			}
			
			//echo 'free-shipping';
		}
		
		return $rates;
	}	
	
	/**
	* Hide ALL Shipping option when free shipping is available
	*
	* @param array $available_methods
	*
	function hide_all_shipping_when_free_is_available( $available_methods ) {
	 
		if( isset( $available_methods['free_shipping'] ) ) {
			
			// Get Free Shipping array into a new array
			$freeshipping = array();
			$freeshipping = $available_methods['free_shipping'];
	 
			// Empty the $available_methods array
			unset( $available_methods );
	 
			// Add Free Shipping back into $avaialble_methods
			$available_methods = array();
			$available_methods[] = $freeshipping;
	 
		}
	 
		return $available_methods;
	}	
	
	/**
	 * get_settings.
	 */    
	function get_settings() {
 
	    $settings = array(
 
	        array( 'title' => __( 'Shipping Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( '', 'woocommerce-jetpack' ), 'id' => 'wcj_shipping_options' ),
	        
	        array(
	            'title'    => __( 'Shipping', 'woocommerce-jetpack' ),
	            'desc'     => '<strong>' . __( 'Enable Module', 'woocommerce-jetpack' ) . '</strong>',
	            'desc_tip' => __( 'Hide WooCommerce shipping when free is available.', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_shipping_enabled',
	            'default'  => 'no',
	            'type'     => 'checkbox',
	        ),
	    
	        array( 'type'  => 'sectionend', 'id' => 'wcj_shipping_options' ),
			
			array( 'title' => __( 'Hide if free is available', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'This section lets you hide other shipping options when free shipping is available on shop frontend.', 'woocommerce-jetpack' ), 'id' => 'wcj_shipping_hide_if_free_available_options' ),
			
	        array(
	            //'title'    => __( 'Hide local delivery', 'woocommerce-jetpack' ),
				'title'    => __( 'Hide shipping', 'woocommerce-jetpack' ),
	            'desc'     => __( 'Hide local delivery when free is available', 'woocommerce-jetpack' ),
	            'desc_tip' => __( '', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_shipping_hide_if_free_available_local_delivery',
	            'default'  => 'no',
	            'type'     => 'checkbox',
				'checkboxgroup'   => 'start',
	        ),

			array(
	            //'title'    => __( 'Hide all', 'woocommerce-jetpack' ),
	            'desc'     => __( 'Hide all when free is available', 'woocommerce-jetpack' ),
	            'desc_tip' => __( '', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_shipping_hide_if_free_available_all',
	            'default'  => 'no',
	            'type'     => 'checkbox',
				'checkboxgroup'   => 'end',
	        ),
			
			array( 'type'  => 'sectionend', 'id' => 'wcj_shipping_hide_if_free_available_options' ),
		
	    );
	    
	    return $settings;
	}
 
	/**
	 * settings_section.
	 */
	function settings_section( $sections ) {
	
	    $sections['shipping'] = __( 'Shipping', 'woocommerce-jetpack' );
	    
	    return $sections;
	}    
}
 
endif;
 
return new WCJ_Shipping();

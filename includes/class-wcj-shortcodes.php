<?php
/**
 * WooCommerce Jetpack Shortcodes
 *
 * The WooCommerce Jetpack Shortcodes class.
 *
 * @class    WCJ_Shortcodes
 * @version  1.0.0
 * @category Class
 * @author   Algoritmika Ltd.
 */
 
if ( ! defined( 'ABSPATH' ) ) exit;
 
if ( ! class_exists( 'WCJ_Shortcodes' ) ) :
 
class WCJ_Shortcodes {
	
	/**
	 * Constructor.
	 */
	public function __construct() {
 
	    // Main hooks
	    //if ( 'yes' === get_option( 'wcj_shortcodes_enabled' ) ) {
			//add_shortcode( 'wcj_sku', 									array( 'WCJ_Product_Info', 'shortcode_product_info_sku' ) );	
			//add_shortcode( 'wcj_sku', 									array( &WCJ_Product_Info, 'shortcode_product_info_sku' ) );	
	    //}        
	
	    // Settings hooks
	    //add_filter( 'wcj_settings_sections', array( $this, 'settings_section' ) );
	    //add_filter( 'wcj_settings_shortcodes', array( $this, 'get_settings' ), 100 );
	    //add_filter( 'wcj_features_status', array( $this, 'add_enabled_option' ), 100 );
	}
	
	
	
	/**
	 * add_enabled_option.
	 *
	public function shortcode_product_info_sku( $atts ) {    
		return 'ttt';
	}
	
	/**
	 * add_enabled_option.
	 */
	public function add_enabled_option( $settings ) {    
	    $all_settings = $this->get_settings();
	    $settings[] = $all_settings[1];        
	    return $settings;
	}
	
	/**
	 * get_settings.
	 */    
	function get_settings() {
 
	    $settings = array(
 
	        array( 'title' => __( 'Shortcodes Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'Shortcodes.', 'woocommerce-jetpack' ), 'id' => 'wcj_shortcodes_options' ),
	        
	        array(
	            'title'    => __( 'Shortcodes', 'woocommerce-jetpack' ),
	            'desc'     => __( 'Enable the Shortcodes feature', 'woocommerce-jetpack' ),
	            'desc_tip' => __( 'Shortcodes.', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_shortcodes_enabled',
	            'default'  => 'yes',
	            'type'     => 'checkbox',
	        ),
	    
	        array( 'type'  => 'sectionend', 'id' => 'wcj_shortcodes_options' ),
	    );
	    
	    return $settings;
	}
 
	/**
	 * settings_section.
	 */
	function settings_section( $sections ) {    
	    $sections['shortcodes'] = __( 'Shortcodes', 'woocommerce-jetpack' );        
	    return $sections;
	}    
}
 
endif;
 
return new WCJ_Shortcodes();

<?php
/**
 * WooCommerce Jetpack PDF Invoicing Page
 *
 * The WooCommerce Jetpack PDF Invoicing Page class.
 *
 * @class    WCJ_PDF_Invoicing_Page
 * @version  2.1.2
 * @category Class
 * @author   Algoritmika Ltd.
 */
 
if ( ! defined( 'ABSPATH' ) ) exit;
 
if ( ! class_exists( 'WCJ_PDF_Invoicing_Page' ) ) :
 
class WCJ_PDF_Invoicing_Page {
    
    /**
     * Constructor.
     */
    function __construct() {		
		/*if ( 'yes' === get_option( 'wcj_pdf_invoicing_enabled' ) ) {

		}*/		
        // Settings hooks
        add_filter( 'wcj_settings_sections',           array( $this, 'settings_section' ) );
        add_filter( 'wcj_settings_pdf_invoicing_page', array( $this, 'get_settings' ), 100 );
    }
		    
    /**
     * get_settings.
     */    
    function get_settings() {
	
		$settings = array();		
		$invoice_types = wcj_get_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {	

			$settings[] = array( 'title' => strtoupper( $invoice_type['desc'] ), 'type' => 'title', 'desc' => '', 'id' => 'wcj_invoicing_' . $invoice_type['id'] . '_page_options' );			
			
			$settings[] = array(
				'title'    => __( 'Page Orientation', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_page_orientation',
				'default'  => 'P',
				'type'     => 'select',
				'options'  => array(
					'P' => __( 'Portrait', 'woocommerce-jetpack' ),
					'L' => __( 'Landscape', 'woocommerce-jetpack' ),
				),
			);				
					
			$page_formats = array();
			for ( $i = 1; $i < 8; $i++ ) $page_formats[ 'A' . $i ] = 'A' . $i;
			$settings[] = array(
				'title'    => __( 'Page Format', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_page_format',
				'default'  => 'A4',
				'type'     => 'select',
				'options'  => $page_formats,
			);				
		
			$settings[] = array(
				'title'    => __( 'Margin Left', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_left',
				'default'  => 15,//PDF_MARGIN_LEFT,
				'type'     => 'number',
			);		

			$settings[] = array(
				'title'    => __( 'Margin Right', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_right',
				'default'  => 15,//PDF_MARGIN_RIGHT,
				'type'     => 'number',
			);

			$settings[] = array(
				'title'    => __( 'Margin Top', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_top',
				'default'  => 27,//PDF_MARGIN_TOP,
				'type'     => 'number',
			);

			$settings[] = array(
				'title'    => __( 'Margin Bottom', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_bottom',
				'default'  => 0,//PDF_MARGIN_BOTTOM,
				'type'     => 'number',
			);	
		
			$settings[] = array( 'type'  => 'sectionend', 'id' => 'wcj_invoicing_' . $invoice_type['id'] . '_page_options' );			
		}	
        
        return $settings;
    }
 
    /**
     * settings_section.
     */
    function settings_section( $sections ) {    
        $sections['pdf_invoicing_page'] = __( 'Page Settings', 'woocommerce-jetpack' );        
        return $sections;
    }  
}
 
endif;
 
return new WCJ_PDF_Invoicing_Page();
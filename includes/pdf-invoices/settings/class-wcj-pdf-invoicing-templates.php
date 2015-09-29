<?php
/**
 * WooCommerce Jetpack PDF Invoices Templates
 *
 * The WooCommerce Jetpack PDF Invoices Templates class.
 *
 * @class    WCJ_PDF_Invoicing_Templates
 * @version  1.0.0
 * @category Class
 * @author   Algoritmika Ltd.
 */
 
if ( ! defined( 'ABSPATH' ) ) exit;
 
if ( ! class_exists( 'WCJ_PDF_Invoicing_Templates' ) ) :
 
class WCJ_PDF_Invoicing_Templates {
    
    /**
     * Constructor.
     */
    public function __construct() {
        // Settings hooks
        add_filter( 'wcj_settings_sections',                array( $this, 'settings_section' ) );
        add_filter( 'wcj_settings_pdf_invoicing_templates', array( $this, 'get_settings' ), 100 );
    }
	    
    /**
     * get_settings.
     */    
    function get_settings() {	
	
		$settings = array();				
		$invoice_types = wcj_get_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
		
			ob_start();
			include( 'defaults/wcj-content-template-' . $invoice_type['id'] . '.php' );
			$default_template = ob_get_clean();		
				
			$settings[] = array( 'title' => strtoupper( $invoice_type['desc'] ), 'type' => 'title', 'desc' => '', 'id' => 'wcj_invoicing_' . $invoice_type['id'] . '_templates_options' );			
			$settings[] = array(
                'title'    => __( 'HTML Template', 'woocommerce-jetpack' ),
                //'title'    => $invoice_type['title'],
                'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_template',
                'default'  => $default_template,
                'type'     => 'textarea',
				'css'	   => 'width:66%;min-width:300px;height:500px;',
            );				
			$settings[] = array( 'type'  => 'sectionend', 'id' => 'wcj_invoicing_' . $invoice_type['id'] . '_templates_options' );			
		}
		
		$settings[] = array( 'title' => __( 'Available Shortcodes', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => wcj_get_shortcodes_list(), 'id' => 'wcj_invoicing_templates_desc' );			
		$settings[] = array( 'type'  => 'sectionend', 'id' => 'wcj_invoicing_templates_desc' );					

		;
        
        return $settings;
    }
 
    /**
     * settings_section.
     */
    function settings_section( $sections ) {    
        $sections['pdf_invoicing_templates'] = __( 'Templates', 'woocommerce-jetpack' );        
        return $sections;
    }  
}
 
endif;
 
return new WCJ_PDF_Invoicing_Templates();
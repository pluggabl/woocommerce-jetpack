<?php
/**
 * WooCommerce Jetpack Emails
 *
 * The WooCommerce Jetpack Emails class.
 *
 * @class       WCJ_Emails
 * @version		1.0.3
 * @category	Class
 * @author 		Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Emails' ) ) :

class WCJ_Emails {

    /**
     * Constructor.
     */
    public function __construct() {
        // Main hooks
        if ( 'yes' === get_option( 'wcj_emails_enabled' ) ) {
			if ( '' != get_option( 'wcj_emails_bcc_email' ) )
				add_filter( 'woocommerce_email_headers', array( $this, 'add_bcc_email' ) );
			if ( '' != get_option( 'wcj_emails_cc_email' ) )
				add_filter( 'woocommerce_email_headers', array( $this, 'add_cc_email' ) );

			//add_action( 'woocommerce_email_after_order_table', array( $this, 'add_payment_method_to_new_order_email' ), 15, 2 );

			// Settings
			add_filter( 'woocommerce_email_settings', array( $this, 'add_email_forwarding_fields' ), 100 );
        }
        // Settings hooks
        add_filter( 'wcj_settings_sections', array( $this, 'settings_section' ) );
        add_filter( 'wcj_settings_emails', array( $this, 'get_settings' ), 100 );
        add_filter( 'wcj_features_status', array( $this, 'add_enabled_option' ), 100 );
    }

    /**
     * add_payment_method_to_new_order_email.
     *
    public function add_payment_method_to_new_order_email( $order, $is_admin_email ) {
        if ( 'yes' === get_option( 'wcj_emails_add_payment_method_to_new_order_enabled' ) ) {
			echo '<p><strong>' . __( 'Payment Method:', 'woocommerce-jetpack' ) . '</strong> ' . $order->payment_method_title . '</p>';
		}
    }

    /**
     * add_enabled_option.
     */
    public function add_enabled_option( $settings ) {
        $all_settings = $this->get_settings();
        $settings[] = $all_settings[1];
        return $settings;
    }

	function add_email_forwarding_fields( $settings ) {

		$updated_settings = array();

		foreach ( $settings as $section ) {

			if ( ( isset( $section['id'] ) && 'email_template_options' == $section['id'] ) &&
				 ( isset( $section['type'] ) && 'title' == $section['type'] ) ) {

				$updated_settings[] =
					array( 'title' => __( 'WooCommerce Jetpack: Email Forwarding Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'This section lets you add another email recipient(s) to all WooCommerce emails. Leave blank to disable.', 'woocommerce-jetpack' ), 'id' => 'wcj_emails_forwarding_options' );

				$updated_settings[] =
					array(
						'title'    => __( 'Cc Email', 'woocommerce-jetpack' ),
						'desc' 	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
						'desc_tip' => __( 'Cc to email, e.g. youremail@yourdomain.com. Leave blank to disable.', 'woocommerce-jetpack' ),
						'id'       => 'wcj_emails_cc_email',
						'default'  => '',
						'type'     => 'text',
						'custom_attributes'
								   => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
					);

				$updated_settings[] =
					array(
						'title'    => __( 'Bcc Email', 'woocommerce-jetpack' ),
						'desc' 	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
						'desc_tip' => __( 'Bcc to email, e.g. youremail@yourdomain.com. Leave blank to disable.', 'woocommerce-jetpack' ),
						'id'       => 'wcj_emails_bcc_email',
						'default'  => '',
						'type'     => 'text',
						'custom_attributes'
								   => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
					);

				$updated_settings[] =
					array( 'type'  => 'sectionend', 'id' => 'wcj_emails_forwarding_options' );

			}

			$updated_settings[] = $section;
		}

		return $updated_settings;
	}

	 /**
	 * Add another email recipient to all WooCommerce emails
	 */
	function add_bcc_email( $email_headers ) {
		return $email_headers . "Bcc: " . get_option( 'wcj_emails_bcc_email' ) . "\r\n";
	}

	 /**
	 * Add another email recipient to all WooCommerce emails
	 */
	function add_cc_email( $email_headers ) {
		return $email_headers . "Cc: " . get_option( 'wcj_emails_cc_email' ) . "\r\n";
	}

    /**
     * get_settings.
     */
    function get_settings() {

        $settings = array(

            array( 'title' => __( 'Emails Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( '', 'woocommerce-jetpack' ), 'id' => 'wcj_emails_options' ),

            array(
                'title'    => __( 'Emails', 'woocommerce-jetpack' ),
                'desc'     => '<strong>' . __( 'Enable Module', 'woocommerce-jetpack' ) . '</strong>',
                'desc_tip' => __( 'Add another email recipient(s) to all WooCommerce emails.', 'woocommerce-jetpack' ),
                'id'       => 'wcj_emails_enabled',
                'default'  => 'no',
                'type'     => 'checkbox',
            ),

			/*array(
                'title'    => __( 'Add Payment Method to New Order Email', 'woocommerce-jetpack' ),
                'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
                'id'       => 'wcj_emails_add_payment_method_to_new_order_enabled',
                'default'  => 'no',
                'type'     => 'checkbox',
            ),*/

            array( 'type'  => 'sectionend', 'id' => 'wcj_emails_options' ),

			array( 'title' => __( 'Email Forwarding Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'This section lets you add another email recipient(s) to all WooCommerce emails. Leave blank to disable.', 'woocommerce-jetpack' ), 'id' => 'wcj_emails_forwarding_options' ),

			array(
				'title'    => __( 'Cc Email', 'woocommerce-jetpack' ),
				'desc' 	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'desc_tip' => __( 'Cc to email, e.g. youremail@yourdomain.com. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_emails_cc_email',
				'default'  => '',
				'type'     => 'text',
				'custom_attributes'
						   => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),

			array(
				'title'    => __( 'Bcc Email', 'woocommerce-jetpack' ),
				'desc' 	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'desc_tip' => __( 'Bcc to email, e.g. youremail@yourdomain.com. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_emails_bcc_email',
				'default'  => '',
				'type'     => 'text',
				'custom_attributes'
						   => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),

			array( 'type'  => 'sectionend', 'id' => 'wcj_emails_forwarding_options' ),
        );

        return $settings;
    }

    /**
     * settings_section.
     */
    function settings_section( $sections ) {
        $sections['emails'] = __( 'Emails', 'woocommerce-jetpack' );
        return $sections;
    }
}

endif;

return new WCJ_Emails();

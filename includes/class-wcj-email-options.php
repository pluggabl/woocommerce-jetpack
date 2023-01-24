<?php
/**
 * Booster for WooCommerce - Module - Email Options
 *
 * @version 
 * @since   2.9.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Email_Options' ) ) :
	/**
	 * WCJ_Debug_Tools.
	 */
	class WCJ_Email_Options extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 3.5.0
		 * @since   2.9.1
		 * @todo    meta customizer - check "gravity-forms-emails-woocommerce" plugin
		 */
		public function __construct() {

			$this->id         = 'email_options';
			$this->short_desc = __( 'Email Options', 'woocommerce-jetpack' );
			$this->desc       = __( 'WooCommerce email options. E.g.: add another email recipient(s) to all WooCommerce emails.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-email-options';
			parent::__construct();

			if ( $this->is_enabled() ) {
				// Email Forwarding.
				if ( '' !== wcj_get_option( 'wcj_emails_bcc_email', '' ) ) {
					add_filter( 'woocommerce_email_headers', array( $this, 'add_bcc_email' ), PHP_INT_MAX, 3 );
				}
				if ( '' !== wcj_get_option( 'wcj_emails_cc_email', '' ) ) {
					add_filter( 'woocommerce_email_headers', array( $this, 'add_cc_email' ), PHP_INT_MAX, 3 );
				}
				// Product Info.
				if ( 'yes' === wcj_get_option( 'wcj_product_info_in_email_order_item_name_enabled', 'no' ) ) {
					add_filter( 'woocommerce_order_item_name', array( $this, 'add_product_info_to_email_order_item_name' ), PHP_INT_MAX, 2 );
				}
				// Settings.
				add_filter( 'woocommerce_email_settings', array( $this, 'add_email_forwarding_fields_to_wc_standard_settings' ), PHP_INT_MAX );
			}
		}

		/**
		 * Add_product_info_to_email_order_item_name.
		 *
		 * @version 
		 * @since   2.7.0
		 * @param string $item_name defines the item_name.
		 * @param  array  $item defines the item.
		 */
		public function add_product_info_to_email_order_item_name( $item_name, $item ) {
			if ( $item['product_id'] ) {
				global $post;
				$product_id = ( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'] );
				$post       = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				setup_postdata( $post );
				$item_name .= do_shortcode( wcj_get_option( 'wcj_product_info_in_email_order_item_name', '[wcj_product_categories strip_tags="yes" before="<hr><em>" after="</em>"]' ) );
				wp_reset_postdata();
			}
			return $item_name;
		}

		/**
		 * Maybe_check_order_status.
		 *
		 * @version 3.5.0
		 * @since   3.5.0
		 * @param string $_object | array  $_object defines the _object.
		 */
		public function maybe_check_order_status( $_object ) {
			$enable_order_statuses = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_emails_forwarding_enable_order_status', '' ) );
			if ( ! empty( $enable_order_statuses ) && isset( $_object ) && is_object( $_object ) && 'WC_Order' === get_class( $_object ) ) {
				if ( ! in_array( $_object->get_status(), $enable_order_statuses, true ) ) {
					return false;
				}
			}
			return true;
		}

		/**
		 * Add another email recipient to all WooCommerce emails.
		 *
		 * @version 3.5.0
		 * @param string         $email_headers defines the email_headers.
		 * @param int            $id defines the id.
		 * @param string | array $_object defines the _object.
		 */
		public function add_bcc_email( $email_headers, $id, $_object ) {
			return ( $this->maybe_check_order_status( $_object ) ? $email_headers . 'Bcc: ' . wcj_get_option( 'wcj_emails_bcc_email', '' ) . "\r\n" : $email_headers );
		}

		/**
		 * Add another email recipient to all WooCommerce emails.
		 *
		 * @version 3.5.0
		 * @param string         $email_headers defines the email_headers.
		 * @param int            $id defines the id.
		 * @param string | array $_object defines the _object.
		 */
		public function add_cc_email( $email_headers, $id, $_object ) {
			return ( $this->maybe_check_order_status( $_object ) ? $email_headers . 'Cc: ' . wcj_get_option( 'wcj_emails_cc_email', '' ) . "\r\n" : $email_headers );
		}

		/**
		 * Get_emails_forwarding_settings.
		 *
		 * @version 3.5.0
		 * @since   2.3.9
		 * @param string | bool $extended_title defines the extended_title.
		 */
		public function get_emails_forwarding_settings( $extended_title = false ) {
			return array(
				array(
					'title' => ( $extended_title ) ?
						__( 'Booster: Email Forwarding Options', 'woocommerce-jetpack' ) :
						__( 'Email Forwarding Options', 'woocommerce-jetpack' ),
					'type'  => 'title',
					'desc'  => __( 'This section lets you add another email recipient(s) to all WooCommerce emails. Leave blank to disable.', 'woocommerce-jetpack' ),
					'id'    => 'wcj_emails_forwarding_options',
				),
				array(
					'title'    => __( 'Cc Email', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Cc to email, e.g. youremail@yourdomain.com. Leave blank to disable.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_emails_cc_email',
					'default'  => '',
					'type'     => 'text',
				),
				array(
					'title'    => __( 'Bcc Email', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Bcc to email, e.g. youremail@yourdomain.com. Leave blank to disable.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_emails_bcc_email',
					'default'  => '',
					'type'     => 'text',
				),
				array(
					'title'             => __( 'Orders Emails: Enable for Statuses', 'woocommerce-jetpack' ),
					'desc'              => apply_filters( 'booster_message', '', 'desc' ),
					'desc_tip'          => __( 'If you want to forward emails for certain orders only, set order statuses here. Leave blank to send for all orders statuses.', 'woocommerce-jetpack' ),
					'id'                => 'wcj_emails_forwarding_enable_order_status',
					'default'           => '',
					'type'              => 'multiselect',
					'class'             => 'chosen_select',
					'options'           => wcj_get_order_statuses(),
					'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'wcj_emails_forwarding_options',
				),
			);
		}

		/**
		 * Add_email_forwarding_fields_to_wc_standard_settings.
		 *
		 * @version 2.3.9
		 * @todo    (maybe) remove this completely (and then move `get_emails_forwarding_settings()` to settings file)
		 * @param   array $settings defines the settings.
		 */
		public function add_email_forwarding_fields_to_wc_standard_settings( $settings ) {
			$updated_settings = array();
			foreach ( $settings as $section ) {
				if ( isset( $section['id'] ) && 'email_template_options' === $section['id'] && isset( $section['type'] ) && 'title' === $section['type'] ) {
					$updated_settings = array_merge( $updated_settings, $this->get_emails_forwarding_settings( true ) );
				}
				$updated_settings[] = $section;
			}
			return $updated_settings;
		}

	}

endif;

return new WCJ_Email_Options();

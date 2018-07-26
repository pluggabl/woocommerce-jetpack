<?php
/**
 * Booster for WooCommerce - Module - Checkout Customization
 *
 * @version 3.8.0
 * @since   2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Checkout_Customization' ) ) :

class WCJ_Checkout_Customization extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.4.0
	 * @since   2.7.0
	 */
	function __construct() {

		$this->id         = 'checkout_customization';
		$this->short_desc = __( 'Checkout Customization', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce checkout - restrict countries by customer\'s IP; hide "Order Again" button; disable selected fields on checkout for logged users and more.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-checkout-customization';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// "Create an account?" Checkbox
			if ( 'default' != ( $create_account_default = get_option( 'wcj_checkout_create_account_default_checked', 'default' ) ) ) {
				if ( 'checked' === $create_account_default ) {
					add_filter( 'woocommerce_create_account_default_checked', '__return_true' );
				} elseif ( 'not_checked' === $create_account_default ) {
					add_filter( 'woocommerce_create_account_default_checked', '__return_false' );
				}
			}
			// Hide "Order Again" button
			if ( 'yes' === get_option( 'wcj_checkout_hide_order_again', 'no' ) ) {
				add_action( 'init', array( $this, 'checkout_hide_order_again' ), PHP_INT_MAX );
			}
			// Disable Fields on Checkout for Logged Users
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'maybe_disable_fields' ), PHP_INT_MAX );
			$checkout_fields_types = array(
				'country',
				'state',
				'textarea',
				'checkbox',
				'password',
				'text',
				'email',
				'tel',
				'number',
				'select',
				'radio',
			);
			foreach ( $checkout_fields_types as $checkout_fields_type ) {
				add_filter( 'woocommerce_form_field_' . $checkout_fields_type, array( $this, 'maybe_add_description' ), PHP_INT_MAX, 4 );
			}
			// Custom "order received" message text
			if ( 'yes' === get_option( 'wcj_checkout_customization_order_received_message_enabled', 'no' ) ) {
				add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'customize_order_received_message' ), PHP_INT_MAX, 2 );
			}
			// Custom checkout login message
			if ( 'yes' === get_option( 'wcj_checkout_customization_checkout_login_message_enabled', 'no' ) ) {
				add_filter( 'woocommerce_checkout_login_message', array( $this, 'checkout_login_message' ), PHP_INT_MAX );
			}
			// Restrict countries by customer's IP
			if ( 'yes' === get_option( 'wcj_checkout_restrict_countries_by_customer_ip_billing', 'no' ) ) {
				add_filter( 'woocommerce_countries_allowed_countries', array( $this, 'restrict_countries_by_customer_ip' ), PHP_INT_MAX );
			}
			if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_checkout_restrict_countries_by_customer_ip_shipping', 'no' ) ) ) {
				add_filter( 'woocommerce_countries_shipping_countries', array( $this, 'restrict_countries_by_customer_ip' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * restrict_countries_by_customer_ip.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @todo    (maybe) handle case when `wcj_get_country_by_ip()` returns empty string
	 * @todo    (maybe) for shipping countries - filter `woocommerce_ship_to_countries` option
	 */
	function restrict_countries_by_customer_ip( $countries ) {
		$user_country = wcj_get_country_by_ip();
		return array( $user_country => wcj_get_country_name_by_code( $user_country ) );
	}

	/**
	 * checkout_login_message.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function checkout_login_message( $message ) {
		return get_option( 'wcj_checkout_customization_checkout_login_message', __( 'Returning customer?', 'woocommerce' ) );
	}

	/**
	 * customize_order_received_message.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function customize_order_received_message( $message, $_order ) {
		if ( null != $_order ) {
			global $post;
			$post = get_post( wcj_get_order_id( $_order ) );
			setup_postdata( $post );
		}
		$message = do_shortcode( get_option( 'wcj_checkout_customization_order_received_message', __( 'Thank you. Your order has been received.', 'woocommerce' ) ) );
		if ( null != $_order ) {
			wp_reset_postdata();
		}
		return $message;
	}

	/**
	 * maybe_add_description.
	 *
	 * @version 3.8.0
	 * @since   2.9.0
	 */
	function maybe_add_description( $field, $key, $args, $value ) {
		if ( is_user_logged_in() ) {
			$fields_to_disable          = get_option( 'wcj_checkout_customization_disable_fields_for_logged', array() );
			if ( empty( $fields_to_disable ) ) {
				$fields_to_disable = array();
			}
			$fields_to_disable_custom_r = array_map( 'trim', explode( ',', apply_filters( 'booster_option', '', get_option( 'wcj_checkout_customization_disable_fields_for_logged_custom_r', '' ) ) ) );
			$fields_to_disable_custom_d = array_map( 'trim', explode( ',', apply_filters( 'booster_option', '', get_option( 'wcj_checkout_customization_disable_fields_for_logged_custom_d', '' ) ) ) );
			$fields_to_disable          = array_merge( $fields_to_disable, $fields_to_disable_custom_r, $fields_to_disable_custom_d );
			if ( ! empty( $fields_to_disable ) ) {
				if ( in_array( $key, $fields_to_disable ) ) {
					$desc = get_option( 'wcj_checkout_customization_disable_fields_for_logged_message',
						'<em>' . __( 'This field can not be changed', 'woocommerce-jetpack' ) . '</em>' );
					if ( '' != $desc ) {
						$field = str_replace( '__WCJ_TEMPORARY_VALUE_TO_REPLACE__', $desc, $field );
					}
				}
			}
		}
		return $field;
	}

	/**
	 * maybe_disable_fields.
	 *
	 * @version 3.8.0
	 * @since   2.9.0
	 * @see     woocommerce_form_field
	 * @todo    (maybe) add single option (probably checkbox) to disable all fields
	 * @todo    (maybe) on `'billing_country', 'shipping_country'` change to simple `select` (i.e. probably remove `wc-enhanced-select` class)
	 */
	function maybe_disable_fields( $checkout_fields ) {
		if ( is_user_logged_in() ) {
			$fields_to_disable          = get_option( 'wcj_checkout_customization_disable_fields_for_logged', array() );
			if ( empty( $fields_to_disable ) ) {
				$fields_to_disable = array();
			}
			$fields_to_disable_custom_r = array_map( 'trim', explode( ',', apply_filters( 'booster_option', '', get_option( 'wcj_checkout_customization_disable_fields_for_logged_custom_r', '' ) ) ) );
			$fields_to_disable_custom_d = array_map( 'trim', explode( ',', apply_filters( 'booster_option', '', get_option( 'wcj_checkout_customization_disable_fields_for_logged_custom_d', '' ) ) ) );
			$fields_to_disable          = array_merge( $fields_to_disable, $fields_to_disable_custom_r, $fields_to_disable_custom_d );
			$disable_type_fields        = array_merge( array( 'billing_country', 'shipping_country' ), $fields_to_disable_custom_d );
			$do_add_desc_placeholder    = ( '' != get_option( 'wcj_checkout_customization_disable_fields_for_logged_message',
				'<em>' . __( 'This field can not be changed', 'woocommerce-jetpack' ) . '</em>' ) );
			if ( ! empty( $fields_to_disable ) ) {
				foreach ( $fields_to_disable as $field_to_disable ) {
					$section = explode( '_', $field_to_disable );
					$section = $section[0];
					if ( isset( $checkout_fields[ $section ][ $field_to_disable ] ) ) {
						if ( ! isset( $checkout_fields[ $section ][ $field_to_disable ]['custom_attributes'] ) ) {
							$checkout_fields[ $section ][ $field_to_disable ]['custom_attributes'] = array();
						}
						$custom_attributes = ( in_array( $field_to_disable, $disable_type_fields ) ? array( 'disabled' => 'disabled' ) : array( 'readonly' => 'readonly' ) );
						$checkout_fields[ $section ][ $field_to_disable ]['custom_attributes'] = array_merge(
							$checkout_fields[ $section ][ $field_to_disable ]['custom_attributes'],
							$custom_attributes
						);
						if ( $do_add_desc_placeholder ) {
							$checkout_fields[ $section ][ $field_to_disable ]['description'] = '__WCJ_TEMPORARY_VALUE_TO_REPLACE__';
						}
					}
				}
			}
		}
		return $checkout_fields;
	}

	/**
	 * checkout_hide_order_again.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function checkout_hide_order_again() {
		remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );
	}

}

endif;

return new WCJ_Checkout_Customization();

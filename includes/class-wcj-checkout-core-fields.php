<?php
/**
 * WooCommerce Jetpack Checkout Core Fields
 *
 * The WooCommerce Jetpack Checkout Core Fields class.
 *
 * @version 2.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Checkout_Core_Fields' ) ) :

class WCJ_Checkout_Core_Fields extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 */
	public function __construct() {

		$this->id         = 'checkout_core_fields';
		$this->short_desc = __( 'Checkout Core Fields', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce core checkout fields. Disable/enable fields, set required, change labels and/or placeholders.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-checkout-core-fields/';
		parent::__construct();

		$this->woocommerce_core_checkout_fields = array(
			'billing_country',
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_postcode',
			'billing_email',
			'billing_phone',
			'shipping_country',
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_state',
			'shipping_postcode',
			'account_password',
			'order_comments',
		);

		if ( $this->is_enabled() ) {
//			$this->convert_old_settings();
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'custom_override_checkout_fields' ) );
			add_filter( 'woocommerce_default_address_fields' , array( $this, 'custom_override_default_address_fields' ) );
		}
	}

	/**
	 * custom_override_default_address_fields.
	 *
	 * @version 2.3.8
	 * @since   2.3.8
	 */
	function custom_override_default_address_fields( $fields ) {

		foreach ( $fields as $field_key => $field_values ) {

			foreach ( $this->woocommerce_core_checkout_fields as $field ) {

				$field_parts = explode( '_', $field, 2 );
				if ( is_array( $field_parts ) && 2 === count( $field_parts ) ) {

					$section     = $field_parts[0]; // billing or shipping
					$field_name  = $field_parts[1];

					if ( $field_key === $field_name ) {

						/* $is_required = get_option( 'wcj_checkout_fields_' . $field . '_' . 'is_required', 'default' );
						if ( 'default' != $is_required ) {
							$fields[ $field_name ]['required'] = ( 'yes' === $is_required ) ? true : false;
						} */

						$label = get_option( 'wcj_checkout_fields_' . $field . '_' . 'label', '' );
						if ( '' != $label ) {
							$fields[ $field_name ]['label'] = $label;
						}

						$placeholder = get_option( 'wcj_checkout_fields_' . $field . '_' . 'placeholder', '' );
						if ( '' != $placeholder ) {
							$fields[ $field_name ]['placeholder'] = $placeholder;
						}

						/* $class = get_option( 'wcj_checkout_fields_' . $field . '_' . 'class', 'default' );
						if ( 'default' != $class ) {
							$fields[ $field_name ]['class'] = $class;
						} */
					}
				}
			}
		}
		return $fields;
	}

	/**
	 * convert_old_settings.
	 *
	 * @version 2.3.8
	 * @since   2.3.8
	 */
	/* function convert_old_settings() {
		$woocommerce_core_checkout_fields_old = array(
			'billing_country'     => array( 'default_required' => 'yes' ),
			'billing_first_name'  => array( 'default_required' => 'yes' ),
			'billing_last_name'   => array( 'default_required' => 'yes' ),
			'billing_company'     => array( 'default_required' => 'no' ),
			'billing_address_1'   => array( 'default_required' => 'yes' ),
			'billing_address_2'   => array( 'default_required' => 'no' ),
			'billing_city'        => array( 'default_required' => 'yes' ),
			'billing_state'       => array( 'default_required' => 'yes' ),
			'billing_postcode'    => array( 'default_required' => 'yes' ),
			'billing_email'       => array( 'default_required' => 'yes' ),
			'billing_phone'       => array( 'default_required' => 'yes' ),
			'shipping_country'    => array( 'default_required' => 'yes' ),
			'shipping_first_name' => array( 'default_required' => 'yes' ),
			'shipping_last_name'  => array( 'default_required' => 'yes' ),
			'shipping_company'    => array( 'default_required' => 'no' ),
			'shipping_address_1'  => array( 'default_required' => 'yes' ),
			'shipping_address_2'  => array( 'default_required' => 'no' ),
			'shipping_city'       => array( 'default_required' => 'yes' ),
			'shipping_state'      => array( 'default_required' => 'yes' ),
			'shipping_postcode'   => array( 'default_required' => 'yes' ),
			'account_password'    => array( 'default_required' => 'yes' ),
			'order_comments'      => array( 'default_required' => 'no' ),
		);

		foreach ( $woocommerce_core_checkout_fields_old as $field => $options ) {
			$is_enabled_old  = get_option( 'wcj_checkout_fields_' . $field . '_' . 'enabled', '' );
			$is_required_old = get_option( 'wcj_checkout_fields_' . $field . '_' . 'required', '' );

			if ( '' != $is_enabled_old && 'yes' != $is_enabled_old ) {
				update_option( 'wcj_checkout_fields_' . $field . '_' . 'is_enabled', $is_enabled_old );
			}
			if ( '' != $is_required_old && $options['default_required'] != $is_required_old ) {
				update_option( 'wcj_checkout_fields_' . $field . '_' . 'is_required', $is_required_old );
			}

			delete_option( 'wcj_checkout_fields_' . $field . '_' . 'enabled', '' );
			delete_option( 'wcj_checkout_fields_' . $field . '_' . 'required', '' );
		}
	} */

	/**
	 * custom_override_checkout_fields.
	 *
	 * @version 2.4.0
	 */
	function custom_override_checkout_fields( $checkout_fields ) {

		foreach ( $this->woocommerce_core_checkout_fields as $field ) {

			$field_parts = explode( '_', $field, 2 );
			$section = ( ! empty( $field_parts ) && is_array( $field_parts ) ) ? $field_parts[0] : ''; // billing or shipping

			$is_enabled = get_option( 'wcj_checkout_fields_' . $field . '_' . 'is_enabled', 'default' );
			if ( 'default' != $is_enabled ) {
				if ( 'no' === $is_enabled ) {
					if ( isset( $checkout_fields[ $section ][ $field ] ) ) {
						unset( $checkout_fields[ $section ][ $field ] ); // e.g. unset( $checkout_fields['billing']['billing_country'] );
					}
				}
			}

			if ( isset( $checkout_fields[ $section ][ $field ] ) ) {

				$is_required = get_option( 'wcj_checkout_fields_' . $field . '_' . 'is_required', 'default' );
				if ( 'default' != $is_required ) {
					$checkout_fields[ $section ][ $field ]['required'] = ( 'yes' === $is_required ) ? true : false;
				}

				$label = get_option( 'wcj_checkout_fields_' . $field . '_' . 'label', '' );
				if ( '' != $label ) {
					$checkout_fields[ $section ][ $field ]['label'] = $label;
				}

				$placeholder = get_option( 'wcj_checkout_fields_' . $field . '_' . 'placeholder', '' );
				if ( '' != $placeholder ) {
					$checkout_fields[ $section ][ $field ]['placeholder'] = $placeholder;
				}

				$class = get_option( 'wcj_checkout_fields_' . $field . '_' . 'class', 'default' );
				if ( 'default' != $class ) {
					$checkout_fields[ $section ][ $field ]['class'] = array( $class );
				}
			}
		}

		return $checkout_fields;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.0
	 */
	function get_settings() {

		$settings = array(
			array(
				'title' => __( 'Checkout Core Fields Options', 'woocommerce-jetpack' ),
				'type'  => 'title',
				'id'    => 'wcj_checkout_core_fields_options',
			),
		);

		foreach ( $this->woocommerce_core_checkout_fields as $field ) {

			$settings = array_merge( $settings, array (

				array(
					'title'     => ucwords( str_replace( '_', ' ', $field ) ),
					'desc'      => __( 'enabled', 'woocommerce-jetpack' ),
					'id'        => 'wcj_checkout_fields_' . $field . '_' . 'is_enabled',
					'default'   => 'default',
					'type'      => 'select',
					'options'   => array(
						'default' => __( 'Default', 'woocommerce-jetpack' ),
						'yes'     => __( 'Enabled', 'woocommerce-jetpack' ),
						'no'      => __( 'Disabled', 'woocommerce-jetpack' ),
					),
					'css'       => 'min-width:300px;width:50%;',
				),

				array(
					'title'     => '',
					'desc'      => __( 'required', 'woocommerce-jetpack' ),
					'id'        => 'wcj_checkout_fields_' . $field . '_' . 'is_required',
					'default'   => 'default',
					'type'      => 'select',
					'options'   => array(
						'default' => __( 'Default', 'woocommerce-jetpack' ),
						'yes'     => __( 'Required', 'woocommerce-jetpack' ),
						'no'      => __( 'Not Required', 'woocommerce-jetpack' ),
					),
					'css'       => 'min-width:300px;width:50%;',
				),

				array(
					'title'     => '',
					'desc'      => __( 'label', 'woocommerce-jetpack' ),
					'desc_tip'  => __( 'Leave blank for WooCommerce defaults.', 'woocommerce-jetpack' ),
					'id'        => 'wcj_checkout_fields_' . $field . '_' . 'label',
					'default'   => '',
					'type'      => 'text',
					'css'       => 'min-width:300px;width:50%;',
				),

				array(
					'title'     => '',
					'desc'      => __( 'placeholder', 'woocommerce-jetpack' ),
					'desc_tip'  => __( 'Leave blank for WooCommerce defaults.', 'woocommerce-jetpack' ),
					'id'        => 'wcj_checkout_fields_' . $field . '_' . 'placeholder',
					'default'   => '',
					'type'      => 'text',
					'css'       => 'min-width:300px;width:50%;',
				),

				array(
					'title'     => '',
					'desc'      => __( 'class', 'woocommerce-jetpack' ),
					'id'        => 'wcj_checkout_fields_' . $field . '_' . 'class',
					'default'   => 'default',
					'type'      => 'select',
					'options'   => array(
						'default'        => __( 'Default', 'woocommerce-jetpack' ),
						'form-row-first' => __( 'Align Left', 'woocommerce-jetpack' ),
						'form-row-last'  => __( 'Align Right', 'woocommerce-jetpack' ),
						'form-row-full'  => __( 'Full Row', 'woocommerce-jetpack' ),
					),
					'css'       => 'min-width:300px;width:50%;',
				),

			) );
		}

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'wcj_checkout_core_fields_options',
		);

		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Checkout_Core_Fields();

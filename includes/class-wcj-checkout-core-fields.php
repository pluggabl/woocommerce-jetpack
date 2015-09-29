<?php
/**
 * WooCommerce Jetpack Checkout Core Fields
 *
 * The WooCommerce Jetpack Checkout Core Fields class.
 *
 * @version 2.2.7
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Checkout_Core_Fields' ) ) :

class WCJ_Checkout_Core_Fields extends WCJ_Module {

	/**
	 * @var array $sub_items
	 */
	public $sub_items = array(
		'enabled'     => 'checkbox',
		'required'    => 'checkbox',
		'label'       => 'text',
		'placeholder' => 'text',
		'class'       => 'select',
	);

	/**
	 * @var array $items
	 */
	public $items = array(
		'billing_country' 		=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'billing_first_name' 	=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'billing_last_name' 	=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'billing_company' 		=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'no' ),
		'billing_address_1' 	=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'billing_address_2' 	=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'no' ),
		'billing_city' 			=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'billing_state' 		=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'billing_postcode' 		=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'billing_email' 		=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'billing_phone' 		=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'shipping_country' 		=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'shipping_first_name' 	=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'shipping_last_name' 	=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'shipping_company' 		=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'no' ),
		'shipping_address_1' 	=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'shipping_address_2' 	=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'no' ),
		'shipping_city' 		=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'shipping_state' 		=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'shipping_postcode' 	=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'account_password' 		=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'yes' ),
		'order_comments' 		=> array( 'enabled' => 'yes', 'label' => '', 'placeholder' => '', 'required' => 'no' ),
	);

	/**
	 * Constructor.
	 *
	 * @version 2.2.7
	 */
	public function __construct() {

		$this->id         = 'checkout_core_fields';
		$this->short_desc = __( 'Checkout Core Fields', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce core checkout fields. Disable/enable fields, set required, change labels and/or placeholders.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'custom_override_checkout_fields' ) );
			add_filter( 'woocommerce_default_address_fields', array( $this, 'fix_required_by_default' ) );
		}
	}

	/**
	 * fix_required_by_default.
	 *
	 * @since 2.2.4
	 * @todo  There must be a better way!
	 */
	function fix_required_by_default( $address_fields ) {
		$fields_required_by_default = array(
			'country',
			'first_name',
			'last_name',
			'address_1',
			'city',
			'state',
			'postcode',
		);
		foreach ( $fields_required_by_default as $field ) {
			$billing_value  = get_option( 'wcj_checkout_fields_' . 'billing_'  . $field . '_required' );
			$shipping_value = get_option( 'wcj_checkout_fields_' . 'shipping_' . $field . '_required' );
			if ( 'no' === $billing_value && 'no' === $shipping_value ) {
				$address_fields[ $field ]['required'] = false;
			}
		}
		return $address_fields;
	}

	/**
	 * custom_override_checkout_fields.
	 *
	 * @version 2.2.7
	 */
	function custom_override_checkout_fields( $checkout_fields ) {

		foreach ( $this->items as $item_key => $default_values ) {

			foreach ( $this->sub_items as $sub_item_key => $sub_item_type ) {

				$item_id = 'wcj_checkout_fields_' . $item_key . '_' . $sub_item_key;
				$the_option = get_option( $item_id );

				$field_parts = explode( "_", $item_key, 2 );

				if ( $sub_item_key == 'enabled' ) {

					if ( $the_option == 'no' )
						unset( $checkout_fields[$field_parts[0]][$item_key] ); // e.g. unset( $checkout_fields['billing']['billing_country'] );
				}
				else if ( isset( $checkout_fields[$field_parts[0]][$item_key] ) ) {

					if ( $the_option != '' ) {

						if ( $sub_item_key == 'required' ) {

							if ( $the_option == 'yes' ) $the_option = true;
							else {
								$the_option = false;
								/* $checkout_fields[$field_parts[0]][$item_key]['validate'] = array();
								$checkout_fields[$field_parts[0]][$item_key]['class'] = array( 'woocommerce-validated' );
								$checkout_fields[$field_parts[0]][$item_key]['custom_attributes'] = array(); */
							}
						}

						if ( 'class' === $sub_item_key ) {
							if ( 'default' === $the_option ) continue;
							else $the_option = array( $the_option );
						}

						$checkout_fields[$field_parts[0]][$item_key][$sub_item_key] = $the_option;
					}
				}
			}
		}

		return $checkout_fields;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.2.7
	 */
	function get_settings() {

		//global $woocommerce;

		$settings = array(

			array( 'title' => __( 'Checkout Core Fields Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( '', 'woocommerce-jetpack' ), 'id' => 'wcj_checkout_core_fields_options' ),

			//array( 'type'  => 'sectionend', 'id' => 'wcj_checkout_core_fields_options' ),
		);

		// Checkout fields
//		$settings[] = array( 'title' => __( 'Checkout Fields Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'This section lets you customize the checkout fields: change label, placeholder, set required, or remove any field.', 'woocommerce-jetpack' ), 'id' => 'wcj_checkout_fields_options' );

		/*$items = array(
			'enabled'		=> 'checkbox',
			////'type',
			'label' 		=> 'text',
			'placeholder'	=> 'text',
			'required'		=> 'checkbox',
			//'clear',
		);

		$fields = array(
			'billing_country',// => array( 'yes', '', '', 'yes' ),
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
		);*/

		//global $woocommerce;
		//$checkout_fields = WC()->WC_Checkout->$checkout_fields;//apply_filters( 'woocommerce_checkout_fields' , array() );
		/*if ( is_super_admin() ) {

			global $woocommerce;
			echo '<pre>[';
			print_r( WC_Checkout::instance() );//->checkout()->checkout_fields;
			echo ']</pre>';
		}*/

		//global $woocommerce;
		//echo '<pre>'; print_r( $woocommerce->checkout()->checkout_fields ); echo '</pre>';

		foreach ( $this->items as $field => $default_values) {

			foreach ( $this->sub_items as $item_key => $item_type ) {

				$item_id = 'wcj_checkout_fields_' . $field . '_' . $item_key;

				$default_value = isset( $default_values[ $item_key ] ) ?  $default_values[ $item_key ] : '';

				$item_title = $field;// . ' ' . $item_key;
				$item_title = str_replace( "_", " ", $item_title );
				$item_title = ucwords( $item_title );

				$item_desc_tip = '';
				if ( 'text' == $item_type ) $item_desc_tip = __( 'Leave blank for WooCommerce defaults.', 'woocommerce-jetpack' );

				$settings_to_add = array(
//					'title'    => $item_title,
//					'desc'     => $item_id,//__( 'Enable the Checkout feature', 'woocommerce-jetpack' ),
					'desc'     => $item_key,
					'desc_tip' => $item_desc_tip,// . __( 'Default: ', 'woocommerce-jetpack' ) . $default_value,
					'id'       => $item_id,
					'default'  => $default_value,
					'type'     => $item_type,
					'css'      => 'min-width:300px;width:50%;',
				);

				if ( 'class' === $item_key ) {
					$settings_to_add['options'] = array(
						'default'        => __( 'Default', 'woocommerce-jetpack' ),
						'form-row-first' => __( 'Align Left', 'woocommerce-jetpack' ),
						'form-row-last'  => __( 'Align Right', 'woocommerce-jetpack' ),
						'form-row-full'  => __( 'Full Row', 'woocommerce-jetpack' ),
					);
					$settings_to_add['default'] = 'default';
				}

				if ( 'enabled' == $item_key ) {

					$settings_to_add['title'] = $item_title;
					$settings_to_add['checkboxgroup'] = 'start';
				}
				else if ( 'required' == $item_key ) $settings_to_add['checkboxgroup'] = 'end';

				$settings[] = $settings_to_add;
			}
		}

//		$settings[] = array( 'type'  => 'sectionend', 'id' => 'wcj_checkout_fields_options' );
		$settings[] = array( 'type'  => 'sectionend', 'id' => 'wcj_checkout_core_fields_options' );

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_Checkout_Core_Fields();

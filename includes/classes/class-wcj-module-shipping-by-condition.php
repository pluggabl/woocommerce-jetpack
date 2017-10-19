<?php
/**
 * Booster for WooCommerce - Module - Shipping by Condition
 *
 * @version 3.1.4
 * @since   3.1.4
 * @author  Algoritmika Ltd.
 * @todo    (maybe) `abstract class WCJ_Module_Shipping_By_Condition`
 * @todo    (maybe) add "Shipping Methods by Date/Time" module(s)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Module_Shipping_By_Condition' ) ) :

class WCJ_Module_Shipping_By_Condition extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 */
	function __construct( $type = 'module' ) {
		parent::__construct( $type );
		add_filter( 'wcj_' . $this->id . '_settings', array( $this, 'generate_settings' ) );
		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_package_rates', array( $this, 'available_shipping_methods' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * available_shipping_methods.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 * @todo    apply_filters( 'booster_get_option' )
	 */
	function available_shipping_methods( $rates, $package ) {
		foreach ( $rates as $rate_key => $rate ) {
			foreach ( $this->condition_options as $options_id => $options_data ) {
				$include = get_option( 'wcj_shipping_' . $options_id . '_include_' . $rate->method_id, '' );
				if ( ! empty( $include ) && ! $this->check( $options_id, $include ) ) {
					unset( $rates[ $rate_key ] );
					continue;
				}
				$exclude = get_option( 'wcj_shipping_' . $options_id . '_exclude_' . $rate->method_id, '' );
				if ( ! empty( $exclude ) && $this->check( $options_id, $exclude ) ) {
					unset( $rates[ $rate_key ] );
					continue;
				}
			}
		}
		return $rates;
	}

	/**
	 * generate_settings.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 */
	function generate_settings() {
		$settings = array();
		if ( ! function_exists( 'WC' ) ) {
			return array();
		}
		foreach ( $this->condition_options as $options_id => $options_data ) {
			$settings = array_merge( $settings, array(
				array(
					'title' => __( 'Shipping Methods', 'woocommerce-jetpack' ),
					'type'  => 'title',
					'desc'  => __( 'Leave empty to disable.', 'woocommerce-jetpack' )  . ' ' . $options_data['desc'],
					'id'    => 'wcj_shipping_by_' . $options_id . '_methods_options',
				),
			) );
			foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
				if ( ! in_array( $method->id, array( 'flat_rate', 'local_pickup' ) ) ) {
					$custom_attributes = apply_filters( 'booster_get_message', '', 'disabled' );
					if ( '' == $custom_attributes ) {
						$custom_attributes = array();
					}
					$desc_tip = apply_filters( 'booster_get_message', '', 'desc_no_link' );
				} else {
					$custom_attributes = array();
					$desc_tip = '';
				}
				$settings = array_merge( $settings, array(
					array(
						'title'     => $method->get_method_title(),
						'desc_tip'  => $desc_tip,
						'desc'      => sprintf( __( 'Include %s', 'woocommerce-jetpack' ), $options_data['title'] ),
						'id'        => 'wcj_shipping_' . $options_id . '_include_' . $method->id,
						'default'   => '',
						'type'      => 'multiselect',
						'class'     => 'chosen_select',
						'css'       => 'width: 450px;',
						'options'   => $this->get_condition_options( $options_id ),
						'custom_attributes' => $custom_attributes,
					),
					array(
						'desc_tip'  => $desc_tip,
						'desc'      => sprintf( __( 'Exclude %s', 'woocommerce-jetpack' ), $options_data['title'] ),
						'id'        => 'wcj_shipping_' . $options_id . '_exclude_' . $method->id,
						'default'   => '',
						'type'      => 'multiselect',
						'class'     => 'chosen_select',
						'css'       => 'width: 450px;',
						'options'   => $this->get_condition_options( $options_id ),
						'custom_attributes' => $custom_attributes,
					),
				) );
			}
			$settings = array_merge( $settings, array(
				array(
					'type'  => 'sectionend',
					'id'    => 'wcj_shipping_by_' . $options_id . '_methods_options',
				),
			) );
		}
		return $settings;
	}

	/**
	 * check.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 */
	function check( $options_id, $args ) {
		return true;
	}

	/**
	 * get_condition_options.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 */
	function get_condition_options( $options_id ) {
		return array();
	}

}

endif;

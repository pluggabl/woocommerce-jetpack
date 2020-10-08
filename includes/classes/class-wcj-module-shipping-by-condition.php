<?php
/**
 * Booster for WooCommerce - Module - Shipping by Condition
 *
 * @version 5.3.5
 * @since   3.2.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Module_Shipping_By_Condition' ) ) :

abstract class WCJ_Module_Shipping_By_Condition extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.5.0
	 * @since   3.2.0
	 */
	function __construct( $type = 'module' ) {
		parent::__construct( $type );
		if ( $this->is_enabled() ) {
			$this->use_shipping_instances = ( 'yes' === wcj_get_option( 'wcj_' . $this->id . '_use_shipping_instance', 'no' ) );
			add_filter( 'woocommerce_package_rates', array( $this, 'available_shipping_methods' ), wcj_get_woocommerce_package_rates_module_filter_priority( $this->id ) , 2 );
		}
	}

	/**
	 * check_multiple_roles.
	 *
	 * @version 4.8.0
	 * @since   4.8.0
	 *
	 * @return bool
	 */
	function add_multiple_roles_option(){
		return false;
	}

	/**
	 * available_shipping_methods.
	 *
	 * @version 5.3.5
	 * @since   3.2.0
	 * @todo    apply_filters( 'booster_option' )
	 */
	function available_shipping_methods( $rates, $package ) {
		$include_arr = array();
		$exclude_arr = array();
		foreach ( $rates as $rate_key => $rate ) {
			foreach ( $this->condition_options as $options_id => $options_data ) {
				if ( 'no' === wcj_get_option( 'wcj_shipping_by_' . $options_id . '_section_enabled', 'yes' ) ) {
					continue;
				}
				$include = ( $this->use_shipping_instances ?
					get_option( 'wcj_shipping_' . $options_id . '_include_' . 'instance_' . $rate->instance_id, '' ) :
					get_option( 'wcj_shipping_' . $options_id . '_include_' . $rate->method_id, '' )
				);
				if ( ! empty( $include ) ) {
					if ( $this->check( $options_id, $include, 'include', $package ) ) {
						//$include_arr[] = $rate_key;
					} else {
						unset( $rates[ $rate_key ] );
					}
				}
				$exclude = ( $this->use_shipping_instances ?
					get_option( 'wcj_shipping_' . $options_id . '_exclude_' . 'instance_' . $rate->instance_id, '' ) :
					get_option( 'wcj_shipping_' . $options_id . '_exclude_' . $rate->method_id, '' )
				);
				if ( ! empty( $exclude ) && $this->check( $options_id, $exclude, 'exclude', $package ) ) {
					$exclude_arr[] = $rate_key;
				}
			}
		}
		foreach ( $rates as $rate_key => $rate ) {
			if (
				//( ! empty( $include_arr ) && ! in_array( $rate_key, $include_arr ) ) ||
				( ! empty( $exclude_arr ) && in_array( $rate_key, $exclude_arr ) )
			) {
				unset( $rates[ $rate_key ] );
			}
		}
		return $rates;
	}

	/**
	 * add_settings_from_file.
	 *
	 * @version 3.2.1
	 * @since   3.2.1
	 */
	function add_settings_from_file( $settings ) {
		return $this->maybe_fix_settings( require( wcj_plugin_path() . '/includes/settings/wcj-settings-shipping-by-condition.php' ) );
	}

	/**
	 * check.
	 *
	 * @version 3.6.0
	 * @since   3.2.0
	 */
	abstract function check( $options_id, $args, $include_or_exclude, $package );

	/**
	 * get_condition_options.
	 *
	 * @version 3.6.0
	 * @since   3.2.0
	 */
	abstract function get_condition_options( $options_id );

	/**
	 * get_additional_section_settings.
	 *
	 * @version 3.2.1
	 * @since   3.2.1
	 */
	function get_additional_section_settings( $options_id ) {
		return array();
	}

	/**
	 * get_extra_option_desc.
	 *
	 * @version 4.0.0
	 * @since   4.0.0
	 */
	function get_extra_option_desc( $option_id ) {
		return '';
	}

}

endif;

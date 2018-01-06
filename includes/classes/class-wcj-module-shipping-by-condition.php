<?php
/**
 * Booster for WooCommerce - Module - Shipping by Condition
 *
 * @version 3.2.4
 * @since   3.2.0
 * @author  Algoritmika Ltd.
 * @todo    (maybe) `abstract class WCJ_Module_Shipping_By_Condition`
 * @todo    (maybe) add "Shipping Methods by Date/Time" module
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Module_Shipping_By_Condition' ) ) :

class WCJ_Module_Shipping_By_Condition extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.2.4
	 * @since   3.2.0
	 */
	function __construct( $type = 'module' ) {
		parent::__construct( $type );
		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_package_rates', array( $this, 'available_shipping_methods' ), wcj_get_woocommerce_package_rates_module_filter_priority( $this->id ) , 2 );
		}
	}

	/**
	 * available_shipping_methods.
	 *
	 * @version 3.2.1
	 * @since   3.2.0
	 * @todo    apply_filters( 'booster_option' )
	 */
	function available_shipping_methods( $rates, $package ) {
		foreach ( $rates as $rate_key => $rate ) {
			foreach ( $this->condition_options as $options_id => $options_data ) {
				if ( 'no' === get_option( 'wcj_shipping_by_' . $options_id . '_section_enabled', 'yes' ) ) {
					continue;
				}
				$include = get_option( 'wcj_shipping_' . $options_id . '_include_' . $rate->method_id, '' );
				if ( ! empty( $include ) && ! $this->check( $options_id, $include, 'include' ) ) {
					unset( $rates[ $rate_key ] );
					break;
				}
				$exclude = get_option( 'wcj_shipping_' . $options_id . '_exclude_' . $rate->method_id, '' );
				if ( ! empty( $exclude ) && $this->check( $options_id, $exclude , 'exclude' ) ) {
					unset( $rates[ $rate_key ] );
					break;
				}
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
	 * @version 3.2.1
	 * @since   3.2.0
	 */
	function check( $options_id, $args, $include_or_exclude ) {
		return true;
	}

	/**
	 * get_condition_options.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 */
	function get_condition_options( $options_id ) {
		return array();
	}

	/**
	 * get_additional_section_settings.
	 *
	 * @version 3.2.1
	 * @since   3.2.1
	 */
	function get_additional_section_settings( $options_id ) {
		return array();
	}

}

endif;

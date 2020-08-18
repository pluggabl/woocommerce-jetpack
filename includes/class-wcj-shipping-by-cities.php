<?php
/**
 * Booster for WooCommerce - Module - Shipping by Cities
 *
 * @version 5.2.0
 * @since   3.6.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_By_Cities' ) ) :

class WCJ_Shipping_By_Cities extends WCJ_Module_Shipping_By_Condition {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   3.6.0
	 */
	function __construct() {

		$this->id         = 'shipping_by_cities';
		$this->short_desc = __( 'Shipping Methods by City or Postcode', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set shipping cities or postcodes to include/exclude for shipping methods to show up. (Free shipping available in Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Set shipping cities or postcodes to include/exclude for shipping methods to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-methods-by-cities';

		$this->condition_options = array(
			'cities' => array(
				'title' => __( 'Cities', 'woocommerce-jetpack' ),
				'desc'  => __( 'Otherwise enter cities one per line.', 'woocommerce-jetpack' ),
				'type'  => 'textarea',
				'class' => '',
				'css'   => 'height:200px;',
			),
			'postcodes' => array(
				'title' => __( 'Postcodes', 'woocommerce-jetpack' ),
				'desc'  => __( 'Otherwise enter postcodes one per line.', 'woocommerce-jetpack' ) . '<br>' .
					'<em>' . __( 'Postcodes containing wildcards (e.g. CB23*) and fully numeric ranges (e.g. <code>90210...99000</code>) are also supported.', 'woocommerce' ) . '</em>',
				'type'  => 'textarea',
				'class' => '',
				'css'   => 'height:200px;',
			),
		);

		parent::__construct();

	}

	/**
	 * check.
	 *
	 * @version 5.1.0
	 * @since   3.6.0
	 * @todo    `$_REQUEST['city']` (i.e. billing city)
	 * @todo    `get_base_city()` - do we really need this?
	 */
	function check( $options_id, $values, $include_or_exclude, $package ) {
		switch ( $options_id ) {
			case 'cities':
				$customer_city = strtoupper( isset( $_REQUEST['s_city'] ) ? $_REQUEST['s_city'] : ( isset ( $_REQUEST['calc_shipping_city'] ) ? $_REQUEST['calc_shipping_city'] : ( ! empty( $user_city = WC()->customer->get_shipping_city() ) ? $user_city : WC()->countries->get_base_city() ) ) );
				$values        = array_map( 'strtoupper', array_map( 'trim', explode( PHP_EOL, $values ) ) );
				return in_array( $customer_city, $values );
			case 'postcodes':
				$customer_postcode = strtoupper( isset( $_REQUEST['s_postcode'] ) ? $_REQUEST['s_postcode'] : ( ! empty( $customer_shipping_postcode = WC()->customer->get_shipping_postcode() ) ? $customer_shipping_postcode : WC()->countries->get_base_postcode() ) );
				$postcodes         = array_map( 'strtoupper', array_map( 'trim', explode( PHP_EOL, $values ) ) );
				return wcj_check_postcode( $customer_postcode, $postcodes );
		}
	}

	/**
	 * get_condition_options.
	 *
	 * @version 4.0.0
	 * @since   3.6.0
	 */
	function get_condition_options( $options_id ) {
		switch( $options_id ) {
			case 'cities':
				return '';
			case 'postcodes':
				return '';
		}
	}

}

endif;

return new WCJ_Shipping_By_Cities();

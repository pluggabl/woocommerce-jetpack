<?php
/**
 * Booster for WooCommerce - Module - Shipping by Cities
 *
 * @version 3.6.0
 * @since   3.6.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_By_Cities' ) ) :

class WCJ_Shipping_By_Cities extends WCJ_Module_Shipping_By_Condition {

	/**
	 * Constructor.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function __construct() {

		$this->id         = 'shipping_by_cities';
		$this->short_desc = __( 'Shipping Methods by Cities', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set cities to include/exclude for shipping methods to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-methods-by-cities';

		$this->condition_options = array(
			'cities' => array(
				'title' => __( 'Cities', 'woocommerce-jetpack' ),
				'desc'  => __( 'Otherwise enter cities one per line.', 'woocommerce-jetpack' ),
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
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    `$_REQUEST['city']` (i.e. billing city)
	 * @todo    `get_base_city()` - do we really need this?
	 */
	function check( $options_id, $values, $include_or_exclude, $package ) {
		switch( $options_id ) {
			case 'cities':
				$customer_city = strtoupper( isset( $_REQUEST['s_city'] ) ? $_REQUEST['s_city'] : WC()->countries->get_base_city() );
				$values        = array_map( 'strtoupper', array_map( 'trim', explode( PHP_EOL, $values ) ) );
				return in_array( $customer_city, $values );
		}
	}

	/**
	 * get_condition_options.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function get_condition_options( $options_id ) {
		switch( $options_id ) {
			case 'cities':
				return '';
		}
	}

}

endif;

return new WCJ_Shipping_By_Cities();

<?php
/**
 * WooCommerce Jetpack Crowdfunding
 *
 * The WooCommerce Jetpack Crowdfunding class.
 *
 * @version 2.5.0
 * @since   2.2.6
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Crowdfunding' ) ) :

class WCJ_Crowdfunding extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.8
	 */
	function __construct() {

		$this->id         = 'crowdfunding';
		$this->short_desc = __( 'Crowdfunding', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add crowdfunding products to WooCommerce.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-crowdfunding/';
		parent::__construct();

		if ( $this->is_enabled() ) {

			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

			add_action( 'woocommerce_is_purchasable', array( $this, 'check_dates' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * check_dates.
	 *
	 * @version 2.3.0
	 */
	function check_dates( $purchasable, $_product ) {
		$goal = get_post_meta( $_product->id, '_' . 'wcj_crowdfunding_goal_sum', true );
		if ( 0 != $goal ) {
			$start_date_str = get_post_meta( $_product->id, '_' . 'wcj_crowdfunding_startdate', true );
			$end_date_str   = get_post_meta( $_product->id, '_' . 'wcj_crowdfunding_deadline',  true );
			$start_date = ( '' != $start_date_str ) ? strtotime( $start_date_str ) : 0;
			$end_date   = ( '' != $end_date_str )   ? strtotime( $end_date_str )   : 0;
			if ( $start_date > 0 && ( $start_date - current_time( 'timestamp' ) ) > 0 ) $purchasable = false;
			if ( $end_date   > 0 && ( $end_date   - current_time( 'timestamp' ) ) < 0 ) $purchasable = false;
		}
		return $purchasable;
	}

	/**
	 * get_meta_box_options.
	 */
	function get_meta_box_options() {
		return array(
			array(
				'name'    => 'wcj_crowdfunding_goal_sum',
				'default' => 0,
				'type'    => 'price',
				'title'   => __( 'Goal', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			),
			array(
				'name'    => 'wcj_crowdfunding_startdate',
				'default' => '',
				'type'    => 'date',
				'title'   => __( 'Start Date', 'woocommerce-jetpack' )
			),
			array(
				'name'    => 'wcj_crowdfunding_deadline',
				'default' => '',
				'type'    => 'date',
				'title'   => __( 'Deadline', 'woocommerce-jetpack' )
			),
		);
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.0
 	 */
	function get_settings() {
		$module_desc = __( 'When enabled, module will add Crowdfunding metabox to product edit.', 'woocommerce-jetpack' )
					 . '<br>'
					 . __( 'To add crowdfunding info to the product, use these shortcodes:', 'woocommerce-jetpack' )
					 . '<br>'
					 . '[wcj_product_total_orders], [wcj_product_total_orders_sum],'
					 . '<br>'
					 . '[wcj_product_crowdfunding_goal], [wcj_product_crowdfunding_goal_remaining],'
					 . '<br>'
					 . '[wcj_product_crowdfunding_startdate], [wcj_product_crowdfunding_deadline], [wcj_product_crowdfunding_time_remaining].'
					 . '<br>'
					 . __( 'Shortcodes could be used in "Product Info" module.', 'woocommerce-jetpack' )
					 . '<br>'
					 . __( 'To change add to cart button labels use "Add to Cart Labels" module.', 'woocommerce-jetpack' );
		return $this->add_standard_settings( array(), $module_desc );
	}
}

endif;

return new WCJ_Crowdfunding();

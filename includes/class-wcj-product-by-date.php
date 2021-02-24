<?php
/**
 * Booster for WooCommerce - Module - Product Availability by Date
 *
 * @version 5.2.0
 * @since   2.9.1
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_By_Date' ) ) :

class WCJ_Product_By_Date extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   2.9.1
	 * @todo    per category
	 * @todo    per tag
	 * @todo    (maybe) per products sets
	 * @todo    redirect to custom URL when product is not available
	 */
	function __construct() {

		$this->id         = 'product_by_date';
		$this->short_desc = __( 'Product Availability by Date', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set product availability by date (Custom frontend messages available in Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Set product availability by date.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-availability-by-date';
		parent::__construct();

		$this->time_now = current_time( 'timestamp' );

		if ( $this->is_enabled() ) {
			// Per product meta box
			if ( 'yes' === wcj_get_option( 'wcj_product_by_date_per_product_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes',          array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product',       array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_validate_value' ), PHP_INT_MAX, 3 );
				add_action( 'admin_notices',           array( $this, 'validate_value_admin_notices' ) );
				$this->meta_box_validate_value = 'wcj_product_by_date_enabled';
			}
			if ( 'yes' === wcj_get_option( 'wcj_product_by_date_per_product_enabled', 'no' ) || 'yes' === wcj_get_option( 'wcj_product_by_date_section_enabled', 'no' ) ) {
				// Time now
				$this->day_now   = intval( date( 'j', $this->time_now ) ); // Day of the month without leading zeros: 1 to 31
				$this->month_now = intval( date( 'n', $this->time_now ) ); // Numeric representation of a month, without leading zeros: 1 through 12
				// Filters
				if ( 'non_purchasable' === wcj_get_option( 'wcj_product_by_date_action', 'non_purchasable' ) ) {
					add_filter( 'woocommerce_is_purchasable', array( $this, 'check_is_purchasable_by_date' ), PHP_INT_MAX, 2 );
				}
				add_action( 'woocommerce_single_product_summary', array( $this, 'maybe_add_unavailable_by_date_message' ), 30 );
				if('yes' === wcj_get_option( 'wcj_product_by_date_show_message_on_shop_enabled', 'no' )){
					add_action( 'woocommerce_shop_loop_item_title', array( $this, 'maybe_add_unavailable_by_date_message' ), 30 );
				}	
			}
		}
	}

	/**
	 * get_default_date.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function get_default_date( $i ) {
		$date_defaults = array(
			'1-31',
			'1-29',
			'1-31',
			'1-30',
			'1-31',
			'-',
			'-',
			'-',
			'10-30',
			'1-31',
			'1-30',
			'1-20,26-31',
		);
		return $date_defaults[ $i - 1 ];
	}

	/**
	 * maybe_get_direct_date.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 */
	function maybe_get_direct_date( $product_id ) {
		return (
			'yes' === wcj_get_option( 'wcj_product_by_date_per_product_enabled', 'no' ) &&
			'yes' === get_post_meta( $product_id, '_' . 'wcj_product_by_date_enabled', true ) &&
			'' != ( $direct_date = get_post_meta( $product_id, '_' . 'wcj_product_by_date_direct_date', true ) )
		) ? $direct_date : false;
	}

	/**
	 * maybe_add_unavailable_by_date_message.
	 *
	 * @version 3.4.0
	 * @since   2.9.1
	 */
	function maybe_add_unavailable_by_date_message() {
		$_product = wc_get_product();
		if ( ! $this->check_is_purchasable_by_date( true, $_product ) ) {
			if ( false !== ( $direct_date = $this->maybe_get_direct_date( wcj_get_product_id_or_variation_parent_id( $_product ) ) ) ) {
				$replaceable_values = array(
					'%direct_date%' => $direct_date,
				);
				$message = wcj_get_option( 'wcj_product_by_date_unavailable_message_direct_date',
					'<p style="color:red;">' . __( '%product_title% is not available until %direct_date%.', 'woocommerce-jetpack' ) . '</p>' );
			} else {
				$_date = $this->get_product_availability_this_month( $_product );
				$replaceable_values = array(
					'%date_this_month%' => $_date,
				);
				$message = ( ( '-' === $_date ) ?
					apply_filters( 'booster_option', __( '<p style="color:red;">%product_title% is not available this month.</p>', 'woocommerce-jetpack' ),
						get_option( 'wcj_product_by_date_unavailable_message_month_off',
							__( '<p style="color:red;">%product_title% is not available this month.</p>', 'woocommerce-jetpack' ) ) ) :
					apply_filters( 'booster_option', __( '<p style="color:red;">%product_title% is available only on %date_this_month% this month.</p>', 'woocommerce-jetpack' ),
						get_option( 'wcj_product_by_date_unavailable_message',
							__( '<p style="color:red;">%product_title% is available only on %date_this_month% this month.</p>', 'woocommerce-jetpack' ) ) )
				);
			}
			$replaceable_values['%product_title%'] = $_product->get_title();
			echo str_replace( array_keys( $replaceable_values ), $replaceable_values, do_shortcode( $message ) );
		}
	}

	/**
	 * get_product_availability_this_month.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function get_product_availability_this_month( $_product ) {
		$product_id = wcj_get_product_id_or_variation_parent_id( $_product );
		if ( 'yes' === wcj_get_option( 'wcj_product_by_date_per_product_enabled', 'no' ) && 'yes' === get_post_meta( $product_id, '_' . 'wcj_product_by_date_enabled', true ) ) {
			return get_post_meta( $product_id, '_' . 'wcj_product_by_date_' . $this->month_now, true );
		} elseif ( 'yes' === wcj_get_option( 'wcj_product_by_date_section_enabled', 'no' ) ) {
			return wcj_get_option( 'wcj_product_by_date_' . $this->month_now, $this->get_default_date( $this->month_now ) );
		} else {
			return '';
		}
	}

	/**
	 * check_is_purchasable_by_date.
	 *
	 * @version 4.8.0
	 * @since   2.9.1
	 * @todo    validate `wcj_product_by_date_` option before checking (or even better earlier, when option is saved by admin)
	 */
	function check_is_purchasable_by_date( $purchasable, $_product ) {
		if ( $purchasable ) {
			if ( false !== ( $direct_date = $this->maybe_get_direct_date( wcj_get_product_id_or_variation_parent_id( $_product ) ) ) ) {
				$date = DateTime::createFromFormat( wcj_get_option( 'wcj_product_by_date_direct_date_format', 'm/d/Y' ), $direct_date, wcj_timezone() );
				if ( false === $date ) {
					return false;
				}
				$timestamp = $date->getTimestamp();
				return ( $this->time_now >= $timestamp );
			} else {
				$_date = $this->get_product_availability_this_month( $_product );
				if ( '-' === $_date ) {
					return false;
				} elseif ( '' == $_date ) {
					return true;
				} else {
					return wcj_check_date( $_date, array( 'day_now' => $this->day_now ) );
				}
			}
		}
		return $purchasable;
	}

}

endif;

return new WCJ_Product_By_Date();

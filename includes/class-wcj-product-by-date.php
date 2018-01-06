<?php
/**
 * Booster for WooCommerce - Module - Product Availability by Date
 *
 * @version 2.9.1
 * @since   2.9.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_By_Date' ) ) :

class WCJ_Product_By_Date extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 * @todo    per category
	 * @todo    per tag
	 * @todo    (maybe) per products sets
	 * @todo    redirect to custom URL when product is not available
	 */
	function __construct() {

		$this->id         = 'product_by_date';
		$this->short_desc = __( 'Product Availability by Date', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce product availability by date.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-availability-by-date';
		parent::__construct();

		$this->time_now = current_time( 'timestamp' );

		if ( $this->is_enabled() ) {
			// Per product meta box
			if ( 'yes' === get_option( 'wcj_product_by_date_per_product_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes',          array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product',       array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_validate_value' ), PHP_INT_MAX, 3 );
				add_action( 'admin_notices',           array( $this, 'validate_value_admin_notices' ) );
				$this->meta_box_validate_value = 'wcj_product_by_date_enabled';
			}
			if ( 'yes' === get_option( 'wcj_product_by_date_per_product_enabled', 'no' ) || 'yes' === get_option( 'wcj_product_by_date_section_enabled', 'no' ) ) {
				// Time now
				$this->day_now   = intval( date( 'j', $this->time_now ) ); // Day of the month without leading zeros: 1 to 31
				$this->month_now = intval( date( 'n', $this->time_now ) ); // Numeric representation of a month, without leading zeros: 1 through 12
				// Filters
				add_filter( 'woocommerce_is_purchasable',         array( $this, 'check_is_purchasable_by_date' ),          PHP_INT_MAX, 2 );
				add_action( 'woocommerce_single_product_summary', array( $this, 'maybe_add_unavailable_by_date_message' ), 30 );
			}
		}
	}

	/**
	 * get_default_date.
	 *
	 * @version 2.9.1
	 * @version 2.9.1
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
	 * maybe_add_unavailable_by_date_message.
	 *
	 * @version 2.9.1
	 * @version 2.9.1
	 */
	function maybe_add_unavailable_by_date_message() {
		$_product = wc_get_product();
		if ( ! $this->check_is_purchasable_by_date( true, $_product ) ) {
			$_date = $this->get_product_availability_this_month( $_product );
			$replaceable_values = array(
				'%date_this_month%' => $_date,
				'%product_title%'   => $_product->get_title(),
			);
			$message = ( ( '-' === $_date ) ?
				apply_filters( 'booster_option', __( '<p style="color:red;">%product_title% is not available this month.</p>', 'woocommerce-jetpack' ),
					get_option( 'wcj_product_by_date_unavailable_message_month_off',
						__( '<p style="color:red;">%product_title% is not available this month.</p>', 'woocommerce-jetpack' ) ) ) :
				apply_filters( 'booster_option', __( '<p style="color:red;">%product_title% is available only on %date_this_month% this month.</p>', 'woocommerce-jetpack' ),
					get_option( 'wcj_product_by_date_unavailable_message',
						__( '<p style="color:red;">%product_title% is available only on %date_this_month% this month.</p>', 'woocommerce-jetpack' ) ) )
			);
			echo str_replace(
				array_keys( $replaceable_values ),
				array_values( $replaceable_values ),
				do_shortcode( $message )
			);
		}
	}

	/**
	 * get_product_availability_this_month.
	 *
	 * @version 2.9.1
	 * @version 2.9.1
	 */
	function get_product_availability_this_month( $_product ) {
		$product_id = wcj_get_product_id_or_variation_parent_id( $_product );
		if ( 'yes' === get_option( 'wcj_product_by_date_per_product_enabled', 'no' ) && 'yes' === get_post_meta( $product_id, '_' . 'wcj_product_by_date_enabled', true ) ) {
			return get_post_meta( $product_id, '_' . 'wcj_product_by_date_' . $this->month_now, true );
		} elseif ( 'yes' === get_option( 'wcj_product_by_date_section_enabled', 'no' ) ) {
			return get_option( 'wcj_product_by_date_' . $this->month_now, $this->get_default_date( $this->month_now ) );
		} else {
			return '';
		}
	}

	/**
	 * check_is_purchasable_by_date.
	 *
	 * @version 2.9.1
	 * @version 2.9.1
	 * @todo    validate `wcj_product_by_date_` option before checking (or even better earlier, when option is saved by admin)
	 */
	function check_is_purchasable_by_date( $purchasable, $_product ) {
		if ( $purchasable ) {
			$_date = $this->get_product_availability_this_month( $_product );
			if ( '-' === $_date ) {
				return false;
			} elseif ( '' == $_date ) {
				return true;
			} else {
				return wcj_check_date( $_date, array( 'day_now' => $this->day_now ) );
			}
		}
		return $purchasable;
	}

}

endif;

return new WCJ_Product_By_Date();

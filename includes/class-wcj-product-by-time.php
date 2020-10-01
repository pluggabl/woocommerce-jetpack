<?php
/**
 * Booster for WooCommerce - Module - Product Availability by Time
 *
 * @version 5.2.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_By_Time' ) ) :

class WCJ_Product_By_Time extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   2.8.0
	 * @todo    per category
	 * @todo    per tag
	 * @todo    (maybe) per products sets
	 * @todo    redirect to custom URL when product is not available
	 */
	function __construct() {

		$this->id         = 'product_by_time';
		$this->short_desc = __( 'Product Availability by Time', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set product availability by time (Custom frontend messages available in Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Set product availability by time.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-availability-by-time';
		parent::__construct();

		$this->time_now = current_time( 'timestamp' );

		if ( $this->is_enabled() ) {
			// Per product meta box
			if ( 'yes' === wcj_get_option( 'wcj_product_by_time_per_product_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes',          array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product',       array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_validate_value' ), PHP_INT_MAX, 3 );
				add_action( 'admin_notices',           array( $this, 'validate_value_admin_notices' ) );
				$this->meta_box_validate_value = 'wcj_product_by_time_enabled';
			}
			if ( 'yes' === wcj_get_option( 'wcj_product_by_time_per_product_enabled', 'no' ) || 'yes' === wcj_get_option( 'wcj_product_by_time_section_enabled', 'no' ) ) {
				// Time now
				$this->day_of_week_now = intval( date( 'w', $this->time_now ) );
				$this->hours_now       = intval( date( 'H', $this->time_now ) );
				$this->minutes_now     = intval( date( 'i', $this->time_now ) );
				// Filters
				add_filter( 'woocommerce_is_purchasable',         array( $this, 'check_is_purchasable_by_time' ),          PHP_INT_MAX, 2 );
				add_action( 'woocommerce_single_product_summary', array( $this, 'maybe_add_unavailable_by_time_message' ), 30 );
			}
		}
	}

	/**
	 * get_default_time.
	 *
	 * @version 2.8.0
	 * @version 2.8.0
	 */
	function get_default_time( $i ) {
		$time_defaults = array(
			'-',
			'8:00-19:59',
			'8:00-19:59',
			'8:00-19:59',
			'8:00-19:59',
			'8:00-9:59,12:00-17:59',
			'-',
		);
		return $time_defaults[ $i ];
	}

	/**
	 * maybe_add_unavailable_by_time_message.
	 *
	 * @version 2.9.1
	 * @version 2.8.0
	 */
	function maybe_add_unavailable_by_time_message() {
		$_product = wc_get_product();
		if ( ! $this->check_is_purchasable_by_time( true, $_product ) ) {
			$_time = $this->get_product_availability_this_day( $_product );
			$replaceable_values = array(
				'%time_today%'    => $_time,
				'%product_title%' => $_product->get_title(),
			);
			$message = ( ( '-' === $_time ) ?
				apply_filters( 'booster_option', __( '<p style="color:red;">%product_title% is not available today.</p>', 'woocommerce-jetpack' ),
					get_option( 'wcj_product_by_time_unavailable_message_day_off',
						__( '<p style="color:red;">%product_title% is not available today.</p>', 'woocommerce-jetpack' ) ) ) :
				apply_filters( 'booster_option', __( '<p style="color:red;">%product_title% is available only at %time_today% today.</p>', 'woocommerce-jetpack' ),
					get_option( 'wcj_product_by_time_unavailable_message',
						__( '<p style="color:red;">%product_title% is available only at %time_today% today.</p>', 'woocommerce-jetpack' ) ) )
			);
			echo str_replace(
				array_keys( $replaceable_values ),
				array_values( $replaceable_values ),
				do_shortcode( $message )
			);
		}
	}

	/**
	 * get_product_availability_this_day.
	 *
	 * @version 2.9.1
	 * @version 2.9.1
	 */
	function get_product_availability_this_day( $_product ) {
		$product_id = wcj_get_product_id_or_variation_parent_id( $_product );
		if ( 'yes' === wcj_get_option( 'wcj_product_by_time_per_product_enabled', 'no' ) && 'yes' === get_post_meta( $product_id, '_' . 'wcj_product_by_time_enabled', true ) ) {
			return get_post_meta( $product_id, '_' . 'wcj_product_by_time_' . $this->day_of_week_now, true );
		} elseif ( 'yes' === wcj_get_option( 'wcj_product_by_time_section_enabled', 'no' ) ) {
			return wcj_get_option( 'wcj_product_by_time_' . $this->day_of_week_now, $this->get_default_time( $this->day_of_week_now ) );
		} else {
			return '';
		}
	}

	/**
	 * check_is_purchasable_by_time.
	 *
	 * @version 2.9.1
	 * @version 2.8.0
	 * @todo    validate `wcj_product_by_time_` option before checking (or even better earlier, when option is saved by admin)
	 */
	function check_is_purchasable_by_time( $purchasable, $_product ) {
		if ( $purchasable ) {
			$_time = $this->get_product_availability_this_day( $_product );
			if ( '-' === $_time ) {
				return false;
			} elseif ( '' == $_time ) {
				return true;
			} else {
				return wcj_check_time( $_time, array( 'hours_now' => $this->hours_now, 'minutes_now' => $this->minutes_now ) );
			}
		}
		return $purchasable;
	}

}

endif;

return new WCJ_Product_By_Time();

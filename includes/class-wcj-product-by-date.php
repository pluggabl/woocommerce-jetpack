<?php
/**
 * Booster for WooCommerce - Module - Product Availability by Date
 *
 * @version 7.1.6
 * @since   2.9.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Product_By_Date' ) ) :
	/**
	 * WCJ_Product_By_Date.
	 */
	class WCJ_Product_By_Date extends WCJ_Module {

		/**
		 * The module day_now
		 *
		 * @var varchar $day_now Module day_now.
		 */
		public $day_now;

		/**
		 * The module month_now
		 *
		 * @var varchar $month_now Module month_now.
		 */
		public $month_now;

		/**
		 * The module meta_box_validate_value
		 *
		 * @var varchar $meta_box_validate_value Module meta_box_validate_value.
		 */
		public $meta_box_validate_value;

		/**
		 * Constructor.
		 *
		 * @version 5.6.8
		 * @since   2.9.1
		 * @todo    per category
		 * @todo    per tag
		 * @todo    (maybe) per products sets
		 * @todo    redirect to custom URL when product is not available
		 */
		public function __construct() {

			$this->id         = 'product_by_date';
			$this->short_desc = __( 'Product Availability by Date', 'woocommerce-jetpack' );
			$this->desc       = __( 'Set product availability by date (Custom frontend messages available in Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Set product availability by date.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-product-availability-by-date';
			parent::__construct();
			$this->time_now = wcj_get_timestamp_date_from_gmt();

			if ( $this->is_enabled() ) {
				// Per product meta box.
				if ( 'yes' === wcj_get_option( 'wcj_product_by_date_per_product_enabled', 'no' ) ) {
					add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
					add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
					add_filter( 'wcj_save_meta_box_value', array( $this, 'save_meta_box_validate_value' ), PHP_INT_MAX, 3 );
					add_action( 'admin_notices', array( $this, 'validate_value_admin_notices' ) );
					$this->meta_box_validate_value = 'wcj_product_by_date_enabled';
				}
				if ( 'yes' === wcj_get_option( 'wcj_product_by_date_per_product_enabled', 'no' ) || 'yes' === wcj_get_option( 'wcj_product_by_date_section_enabled', 'no' ) ) {
					// Time now.
					$this->day_now   = intval( gmdate( 'j', $this->time_now ) ); // Day of the month without leading zeros: 1 to 31.
					$this->month_now = intval( gmdate( 'n', $this->time_now ) ); // Numeric representation of a month, without leading zeros: 1 through 12.
					// Filters.
					if ( 'non_purchasable' === wcj_get_option( 'wcj_product_by_date_action', 'non_purchasable' ) ) {
						add_filter( 'woocommerce_is_purchasable', array( $this, 'check_is_purchasable_by_date' ), PHP_INT_MAX, 2 );
					}
					add_action( 'woocommerce_single_product_summary', array( $this, 'maybe_add_unavailable_by_date_message' ), 30 );
					if ( 'yes' === wcj_get_option( 'wcj_product_by_date_show_message_on_shop_enabled', 'no' ) ) {
						add_action( 'woocommerce_shop_loop_item_title', array( $this, 'maybe_add_unavailable_by_date_message' ), 30 );
					}
				}
			}
		}

		/**
		 * Get_default_date.
		 *
		 * @version 2.9.1
		 * @since   2.9.1
		 * @param int $i defines the i.
		 */
		public function get_default_date( $i ) {
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
		 * Maybe_get_direct_date.
		 *
		 * @version 3.4.0
		 * @since   3.4.0
		 * @param int $product_id defines the product_id.
		 */
		public function maybe_get_direct_date( $product_id ) {
			$direct_date = get_post_meta( $product_id, '_wcj_product_by_date_direct_date', true );
			return (
			'yes' === wcj_get_option( 'wcj_product_by_date_per_product_enabled', 'no' ) &&
			'yes' === get_post_meta( $product_id, '_wcj_product_by_date_enabled', true ) &&
			'' !== ( $direct_date )
			) ? $direct_date : false;
		}

		/**
		 * Maybe_add_unavailable_by_date_message.
		 *
		 * @version 3.4.0
		 * @since   2.9.1
		 */
		public function maybe_add_unavailable_by_date_message() {
			$_product = wc_get_product();
			if ( ! $this->check_is_purchasable_by_date( true, $_product ) ) {
				$direct_date = $this->maybe_get_direct_date( wcj_get_product_id_or_variation_parent_id( $_product ) );
				if ( false !== ( $direct_date ) ) {
					$replaceable_values = array(
						'%direct_date%' => $direct_date,
					);
					$message            = wcj_get_option(
						'wcj_product_by_date_unavailable_message_direct_date',
						/* translators: %s: translation added */
						'<p style="color:red;">' . __( '%product_title% is not available until %direct_date%.', 'woocommerce-jetpack' ) . '</p>'
					);
				} else {
					$_date              = $this->get_product_availability_this_month( $_product );
					$replaceable_values = array(
						'%date_this_month%' => $_date,
					);
					$message            = ( ( '-' === $_date ) ?
					apply_filters(
						'booster_option',
						/* translators: %s: translation added */
						'<p style="color:red;">' . __( '%product_title% is not available this month.', 'woocommerce-jetpack' ) . '</p>',
						get_option(
							'wcj_product_by_date_unavailable_message_month_off',
							'<p style="color:red;">' . __( '%product_title% is not available this month.', 'woocommerce-jetpack' ) . '</p>'
						)
					) :
					apply_filters(
						'booster_option',
						/* translators: %s: translation added */
						'<p style="color:red;">' . __( '%product_title% is available only on %date_this_month% this month.', 'woocommerce-jetpack' ) . '</p>',
						get_option(
							'wcj_product_by_date_unavailable_message',
							/* translators: %s: translation added */
							'<p style="color:red;">' . __( '%product_title% is available only on %date_this_month% this month.', 'woocommerce-jetpack' ) . '</p>'
						)
					)
					);
				}
				$replaceable_values['%product_title%'] = $_product->get_title();
				echo wp_kses_post( str_replace( array_keys( $replaceable_values ), $replaceable_values, do_shortcode( $message ) ) );
			}
		}

		/**
		 * Get_product_availability_this_month.
		 *
		 * @version 2.9.1
		 * @since   2.9.1
		 * @param array $_product defines the _product.
		 */
		public function get_product_availability_this_month( $_product ) {
			$product_id = wcj_get_product_id_or_variation_parent_id( $_product );
			if ( 'yes' === wcj_get_option( 'wcj_product_by_date_per_product_enabled', 'no' ) && 'yes' === get_post_meta( $product_id, '_wcj_product_by_date_enabled', true ) ) {
				return get_post_meta( $product_id, '_wcj_product_by_date_' . $this->month_now, true );
			} elseif ( 'yes' === wcj_get_option( 'wcj_product_by_date_section_enabled', 'no' ) ) {
				return wcj_get_option( 'wcj_product_by_date_' . $this->month_now, $this->get_default_date( $this->month_now ) );
			} else {
				return '';
			}
		}

		/**
		 * Check_is_purchasable_by_date.
		 *
		 * @version 4.8.0
		 * @since   2.9.1
		 * @todo    validate `wcj_product_by_date_` option before checking (or even better earlier, when option is saved by admin)
		 * @param string | array $purchasable defines the purchasable.
		 * @param array          $_product defines the _product.
		 */
		public function check_is_purchasable_by_date( $purchasable, $_product ) {
			if ( $purchasable ) {
				$direct_date = $this->maybe_get_direct_date( wcj_get_product_id_or_variation_parent_id( $_product ) );
				if ( false !== ( $direct_date ) ) {
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
					} elseif ( '' === $_date ) {
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

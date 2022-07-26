<?php
/**
 * Booster for WooCommerce - Module - Upsells
 *
 * @version 5.6.2
 * @since   3.5.3
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Upsells' ) ) :
		/**
		 * WCJ_Upsells.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 */
	class WCJ_Upsells extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   3.5.3
		 * @todo    (maybe) use `apply_filters( 'woocommerce_upsell_display_args', array( 'posts_per_page' => $limit, 'orderby' => $orderby, 'columns' => $columns ) );`
		 * @todo    (maybe) Global Upsells - on per category/tag basis
		 * @todo    (maybe) Global Upsells - ids instead of list
		 * @todo    (maybe) add `wcj_upsells_replace_with_upsells` option (similar to `wcj_cross_sells_replace_with_cross_sells`)
		 */
		public function __construct() {

			$this->id         = 'upsells';
			$this->short_desc = __( 'Upsells', 'woocommerce-jetpack' );
			$this->extra_desc = __( 'Upsells are products which you recommend instead of the currently viewed product, for example, products that are more profitable or better quality or more expensive.', 'woocommerce' );
			$this->desc       = __( 'Customize upsells products display. Global upsells (Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Customize upsells products display.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-upsells';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_filter( 'woocommerce_upsells_total', array( $this, 'upsells_total' ), PHP_INT_MAX );
				add_filter( 'woocommerce_upsells_columns', array( $this, 'upsells_columns' ), PHP_INT_MAX );
				add_filter( 'woocommerce_upsells_orderby', array( $this, 'upsells_orderby' ), PHP_INT_MAX );
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_upsells_global_enabled', 'no' ) ) ) {
					$upsell_ids_filter = ( WCJ_IS_WC_VERSION_BELOW_3 ? 'woocommerce_product_upsell_ids' : 'woocommerce_product_get_upsell_ids' );
					add_filter( $upsell_ids_filter, array( $this, 'upsell_ids' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === wcj_get_option( 'wcj_upsells_hide', 'no' ) ) {
					add_action( 'init', array( $this, 'hide_upsells' ), PHP_INT_MAX );
				}
				if ( 'no_changes' !== wcj_get_option( 'wcj_upsells_position', 'no_changes' ) ) {
					add_action( 'init', array( $this, 'reposition_upsells' ), PHP_INT_MAX );
				}
			}

		}

		/**
		 * Reposition_upsells.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 */
		public function reposition_upsells() {
			$this->hide_upsells();
			if ( function_exists( 'storefront_upsell_display' ) ) {
				add_action( wcj_get_option( 'wcj_upsells_position', 'no_changes' ), 'storefront_upsell_display', wcj_get_option( 'wcj_upsells_position_priority', 15 ) );
			} else {
				add_action( wcj_get_option( 'wcj_upsells_position', 'no_changes' ), 'woocommerce_upsell_display', wcj_get_option( 'wcj_upsells_position_priority', 15 ) );
			}
		}

		/**
		 * Hide_upsells.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 */
		public function hide_upsells() {
			remove_action( 'woocommerce_after_single_product_summary', 'storefront_upsell_display', 15 );
			remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		}

		/**
		 * Upsell_ids.
		 *
		 * @version 5.6.2
		 * @since   3.6.0
		 * @param int   $ids Get ids.
		 * @param Array $_product Get products.
		 */
		public function upsell_ids( $ids, $_product ) {
			$global_upsells = wcj_get_option( 'wcj_upsells_global_ids', '' );
			if ( ! empty( $global_upsells ) ) {
				$global_upsells = array_unique( $global_upsells );
				$product_id     = wcj_get_product_id_or_variation_parent_id( $_product );
				$key            = array_search( $product_id, $global_upsells, true );
				if ( false !== ( $key ) ) {
					unset( $global_upsells[ $key ] );
				}
			}
			return ( empty( $global_upsells ) ? $ids : array_unique( array_merge( $ids, $global_upsells ) ) );
		}

		/**
		 * Upsells_orderby.
		 *
		 * @version 3.5.3
		 * @since   3.5.3
		 * @todo    (maybe) check for `isset( $args['orderby'] )`
		 * @param string $orderby defines the orderby.
		 */
		public function upsells_orderby( $orderby ) {
			$_orderby = wcj_get_option( 'wcj_upsells_orderby', 'no_changes' );
			return ( 'no_changes' !== ( $_orderby ) ? $_orderby : $orderby );
		}

		/**
		 * Upsells_columns.
		 *
		 * @version 5.6.2
		 * @since   3.5.3
		 * @todo    (maybe) check for `isset( $args['columns'] )`
		 * @param array $columns defines the columns.
		 */
		public function upsells_columns( $columns ) {
			$_columns = wcj_get_option( 'wcj_upsells_columns', 0 );
			return ( 0 !== ( $_columns ) ? $_columns : $columns );
		}

		/**
		 * Upsells_total.
		 *
		 * @version 5.6.2
		 * @since   3.5.3
		 * @todo    (maybe) check for `isset( $args['posts_per_page'] )`
		 * @param int $limit defines the limit.
		 */
		public function upsells_total( $limit ) {
			$_limit = wcj_get_option( 'wcj_upsells_total', 0 );
			return ( 0 !== ( $_limit ) ? $_limit : $limit );
		}

	}

endif;

return new WCJ_Upsells();

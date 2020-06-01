<?php
/**
 * Booster for WooCommerce - Background Process - Multicurrency Price Updater
 *
 * Updates min and max prices
 *
 * @version 4.5.0
 * @since   4.5.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Multicurrency_Price_Updater' ) ) :

	class WCJ_Multicurrency_Price_Updater extends WP_Background_Process {

		/**
		 * @var string
		 */
		protected $action = 'wcj_bkg_process_price_updater';

		protected function task( $item ) {
			$module = 'multicurrency';
			if ( wcj_is_module_enabled( $module ) ) {
				WCJ()->modules[ $module ]->save_min_max_prices_per_product( $item['id'], $item['currency'] );
			}
			return false;
		}

	}
endif;
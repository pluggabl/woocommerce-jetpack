<?php
/**
 * Booster for WooCommerce - Background Process - Price by Country Updater
 *
 * Updates price metas related to the Price By Country module
 *
 * @version 5.1.0
 * @since   5.1.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Price_By_Country_Updater' ) ) :
		/**
		 * WCJ_Price_By_Country_Updater
		 *
		 * @version 5.1.0
		 */
	class WCJ_Price_By_Country_Updater extends WP_Background_Process {

		/**
		 * Wcj_bkg_process_price_by_country_updater
		 *
		 * @var string
		 */
		protected $action = 'wcj_bkg_process_price_by_country_updater';
		/**
		 * Task
		 *
		 * @version 5.1.0
		 * @param Array $item Get Items.
		 */
		protected function task( $item ) {
			$module = 'price_by_country';
			if ( wcj_is_module_enabled( $module ) ) {
				wcj_update_products_price_by_country_for_single_product( $item['id'] );
			}
			return false;
		}

	}
endif;

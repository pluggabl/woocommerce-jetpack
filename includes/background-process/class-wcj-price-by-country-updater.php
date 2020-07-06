<?php
/**
 * Booster for WooCommerce - Background Process - Price by Country Updater
 *
 * Updates price metas related to the Price By Country module
 *
 * @version 5.1.0
 * @since   5.1.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Price_By_Country_Updater' ) ) :

	class WCJ_Price_By_Country_Updater extends WP_Background_Process {

		/**
		 * @var string
		 */
		protected $action = 'wcj_bkg_process_price_by_country_updater';

		protected function task( $item ) {
			$module = 'price_by_country';
			if ( wcj_is_module_enabled( $module ) ) {
				wcj_update_products_price_by_country_for_single_product( $item['id'] );
			}
			return false;
		}

	}
endif;
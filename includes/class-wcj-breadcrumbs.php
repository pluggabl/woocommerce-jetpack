<?php
/**
 * Booster for WooCommerce - Module - Breadcrumbs
 *
 * @version 2.8.3
 * @since   2.8.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Breadcrumbs' ) ) :

class WCJ_Breadcrumbs extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @todo    recheck filter: `woocommerce_get_breadcrumb`
	 * @todo    recheck action: `woocommerce_breadcrumb`; filter: `woocommerce_structured_data_breadcrumblist`
	 * @todo    recheck filters: `woocommerce_breadcrumb_defaults`, `woocommerce_breadcrumb_home_url`; action: `woocommerce_breadcrumb`
	 */
	function __construct() {

		$this->id         = 'breadcrumbs';
		$this->short_desc = __( 'Breadcrumbs', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce breadcrumbs.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-breadcrumbs';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Hide Breadcrumbs
			if ( 'yes' === get_option( 'wcj_breadcrumbs_hide', 'no' ) ) {
				add_filter( 'woocommerce_get_breadcrumb', '__return_false', PHP_INT_MAX );
				add_action( 'wp_head',                    array( $this, 'hide_breadcrumbs_with_css' ) );
				add_action( 'wp_loaded',                  array( $this, 'hide_breadcrumbs_by_removing_action' ) );
			}
		}
	}

	/**
	 * hide_breadcrumbs_with_css.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 */
	function hide_breadcrumbs_with_css() {
		$identifiers = array(
			'.woocommerce-breadcrumb',
			'.woo-breadcrumbs',
			'.breadcrumbs',
			'.breadcrumb',
			'#breadcrumbs',
		);
		echo '<style>' . implode( ', ', $identifiers ) . ' { display: none !important; }' . '</style>';
	}

	/**
	 * hide_breadcrumbs_by_removing_action.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @see     `add_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );`
	 */
	function hide_breadcrumbs_by_removing_action() {
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	}

}

endif;

return new WCJ_Breadcrumbs();

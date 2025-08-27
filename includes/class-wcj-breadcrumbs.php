<?php
/**
 * Booster for WooCommerce - Module - Breadcrumbs
 *
 * @version 7.3.0
 * @since   2.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Breadcrumbs' ) ) :

	/**
	 * WCJ_Breadcrumbs.
	 *
	 * @version 2.7.0
	 */
	class WCJ_Breadcrumbs extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 7.3.0
		 * @since   2.9.0
		 * @todo    recheck filter: `woocommerce_get_breadcrumb`
		 * @todo    recheck filter: `woocommerce_structured_data_breadcrumblist`; action: `woocommerce_breadcrumb`;
		 * @todo    recheck filter: `woocommerce_breadcrumb_defaults`; action: `woocommerce_breadcrumb`
		 */
		public function __construct() {

			$this->id         = 'breadcrumbs';
			$this->short_desc = __( 'Breadcrumbs', 'woocommerce-jetpack' );
			$this->desc       = __( 'Customize WooCommerce breadcrumbs. Hide breadcrumbs (Elite).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Customize WooCommerce breadcrumbs.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-breadcrumbs';
			parent::__construct();

			if ( $this->is_enabled() ) {
				// Hide Breadcrumbs.
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_breadcrumbs_hide', 'no' ) ) ) {
					add_filter( 'woocommerce_get_breadcrumb', '__return_false', PHP_INT_MAX );
					add_action( 'wp_head', array( $this, 'hide_breadcrumbs_with_css' ) );
					add_action( 'wp_loaded', array( $this, 'hide_breadcrumbs_by_removing_action' ), PHP_INT_MAX );
				}
				// Home URL.
				if ( 'yes' === wcj_get_option( 'wcj_breadcrumbs_change_home_url_enabled', 'no' ) ) {
					add_filter( 'woocommerce_breadcrumb_home_url', array( $this, 'change_home_url' ), PHP_INT_MAX );
				}
			}
		}

		/**
		 * Change_home_url.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 * @param string $_url defines the url.
		 */
		public function change_home_url( $_url ) {
			return wcj_get_option( 'wcj_breadcrumbs_home_url', home_url() );
		}

		/**
		 * Hide_breadcrumbs_with_css.
		 *
		 * @version 3.2.2
		 * @since   2.9.0
		 * @todo    (maybe) option to add custom identifiers
		 * @todo    (maybe) add more identifiers
		 */
		public function hide_breadcrumbs_with_css() {
			$identifiers = array(
				'.woocommerce-breadcrumb',
				'.woo-breadcrumbs',
				'.breadcrumbs',
				'.breadcrumb',
				'#breadcrumbs',
				'.breadcrumbs-wrapper',
			);
			echo '<style>' . esc_html( implode( ', ', $identifiers ) ) . ' { display: none !important; }</style>';
		}

		/**
		 * Hide_breadcrumbs_by_removing_action.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 * @see     `add_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );`
		 */
		public function hide_breadcrumbs_by_removing_action() {
			remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		}

	}

endif;

return new WCJ_Breadcrumbs();

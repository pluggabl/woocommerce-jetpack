<?php
/**
 * Booster for WooCommerce - Module - Product Visibility by Country
 *
 * @version 5.6.8
 * @since   2.5.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Product_By_Country' ) ) :
	/**
	 * WCJ_Product_By_Country.
	 */
	class WCJ_Product_By_Country extends WCJ_Module_Product_By_Condition {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   2.5.0
		 */
		public function __construct() {

			$this->id         = 'product_by_country';
			$this->short_desc = __( 'Product Visibility by Country', 'woocommerce-jetpack' );
			$this->desc       = __( 'Display products by customer\'s country. User Country Selection Method (Plus); Admin country list options (Plus); Visibility method options (Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Display products by customer\'s country.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-product-visibility-by-country';
			$this->extra_desc = __( 'When enabled, module will add new "Booster: Product Visibility by Country" meta box to each product\'s edit page.', 'woocommerce-jetpack' );

			$this->title = __( 'Countries', 'woocommerce-jetpack' );

			parent::__construct();

			if ( $this->is_enabled() ) {
				add_filter( 'woocommerce_checkout_update_order_review_expired', array( $this, 'update_order_review_expired' ) );
			}

		}

		/**
		 * Update_order_review_expired.
		 *
		 * @version 4.6.1
		 * @since   4.6.1
		 *
		 * @param string | bool $update defines the update.
		 *
		 * @return bool
		 */
		public function update_order_review_expired( $update ) {
			if ( 'yes' !== wcj_get_option( 'wcj_product_by_country_selection_billing_country_overwrite', 'no' ) ) {
				return $update;
			}
			$update = false;
			return $update;
		}

		/**
		 * Get_options_list.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 */
		public function get_options_list() {
			return ( 'wc' === apply_filters( 'booster_option', 'all', wcj_get_option( 'wcj_product_by_country_country_list', 'all' ) ) ?
			WC()->countries->get_allowed_countries() : wcj_get_countries() );
		}

		/**
		 * Get_check_option.
		 *
		 * @version 5.6.2
		 * @since   3.6.0
		 */
		public function get_check_option() {
			if ( 'manual' === apply_filters( 'booster_option', 'by_ip', wcj_get_option( 'wcj_product_by_country_selection_method', 'by_ip' ) ) ) {
				if ( '' === wcj_session_get( 'wcj_selected_country' ) || null === wcj_session_get( 'wcj_selected_country' ) ) {
					$country = wcj_get_country_by_ip();
					wcj_session_set( 'wcj_selected_country', $country );
					$check_option = $country;
				} else {
					$check_option = wcj_session_get( 'wcj_selected_country' );
				}
			} else {
				$check_option = wcj_get_country_by_ip();
			}

			if ( 'yes' === wcj_get_option( 'wcj_product_by_country_selection_billing_country_overwrite', 'no' ) ) {
				$billing_country = isset( $_REQUEST['country'] ) && ! empty( $_REQUEST['country'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['country'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				if ( ! empty( $billing_country ) ) {
					$check_option = $billing_country;
				}
			}
			return $check_option;
		}

		/**
		 * Maybe_add_extra_frontend_filters.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 */
		public function maybe_add_extra_frontend_filters() {
			if ( 'manual' === apply_filters( 'booster_option', 'by_ip', wcj_get_option( 'wcj_product_by_country_selection_method', 'by_ip' ) ) ) {
				add_action( 'init', array( $this, 'save_country_in_session' ), PHP_INT_MAX );
			}
		}

		/**
		 * Save_country_in_session.
		 *
		 * @version 5.6.8
		 * @since   3.1.0
		 */
		public function save_country_in_session() {
			wcj_session_maybe_start();
			$country_selector_wpnonce = isset( $_REQUEST['wcj_country_selector-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_country_selector-nonce'] ), 'wcj_country_selector' ) : false;
			$country_wpnonce          = isset( $_REQUEST['wcj-country-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-country-nonce'] ), 'wcj-country' ) : false;
			if ( $country_selector_wpnonce && isset( $_REQUEST['wcj_country_selector'] ) ) {
				wcj_session_set( 'wcj_selected_country', sanitize_text_field( wp_unslash( $_REQUEST['wcj_country_selector'] ) ) );
			}
			if ( $country_wpnonce && isset( $_REQUEST['wcj-country'] ) ) {
				wcj_session_set( 'wcj_selected_country', sanitize_text_field( wp_unslash( $_REQUEST['wcj-country'] ) ) );
			}
		}

		/**
		 * Maybe_extra_options_process.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 * @param array $options defines the options.
		 */
		public function maybe_extra_options_process( $options ) {
			if ( in_array( 'EU', $options, true ) ) {
				$options = array_merge( $options, wcj_get_european_union_countries() );
			}
			return $options;
		}

		/**
		 * Maybe_add_extra_settings.
		 *
		 * @version 5.6.2
		 * @since   3.6.0
		 * @todo    (maybe) move "Country List" inside the "Admin Options" section
		 */
		public function maybe_add_extra_settings() {
			return array(
				array(
					'title' => __( 'User Country Selection Options', 'woocommerce-jetpack' ),
					'type'  => 'title',
					'id'    => 'wcj_product_by_country_selection_options',
				),
				array(
					'title'             => __( 'User Country Selection Method', 'woocommerce-jetpack' ),
					'desc_tip'          => __( 'Possible values: "Automatically by IP" or "Manually".', 'woocommerce-jetpack' ),
					'desc'              => sprintf(
						/* translators: %s: translation added */
						'<p>' . __( 'If "Manually" option is selected, you can add country selection drop box to frontend with "%1$s" widget or %2$s shortcode.', 'woocommerce-jetpack' ),
						__( 'Booster - Selector', 'woocommerce-jetpack' ),
						'<code>[wcj_selector selector_type="country"]</code>'
					) .
						'<br>' . apply_filters( 'booster_message', '', 'desc' ) . '</p>',
					'id'                => 'wcj_product_by_country_selection_method',
					'default'           => 'by_ip',
					'type'              => 'select',
					'options'           => array(
						'by_ip'  => __( 'Automatically by IP', 'woocommerce-jetpack' ),
						'manual' => __( 'Manually', 'woocommerce-jetpack' ),
					),
					'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
					'css'               => 'min-width:250px;',
				),
				array(
					'title'             => __( 'Country List', 'woocommerce-jetpack' ),
					'desc_tip'          => __( 'This option sets which countries will be added to the list in [wcj_selector selector_type="country"] shortcode. Possible values: "All countries" or "WooCommerce selling locations".', 'woocommerce-jetpack' ),
					'desc'              => sprintf(
						/* translators: %s: translation added */
						'<p>' . __( 'If "WooCommerce selling locations" option is selected, country list will be set by <a href="%s">WooCommerce > Settings > General > Selling location(s)</a>.', 'woocommerce-jetpack' ),
						admin_url( 'admin.php?page=wc-settings' )
					) .
						'<br>' . apply_filters( 'booster_message', '', 'desc' ) . '</p>',
					'id'                => 'wcj_product_by_country_country_list_shortcode',
					'default'           => 'all',
					'type'              => 'select',
					'options'           => array(
						'all' => __( 'All countries', 'woocommerce-jetpack' ),
						'wc'  => __( 'WooCommerce selling locations', 'woocommerce-jetpack' ),
					),
					'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
					'css'               => 'min-width:250px;',
				),
				array(
					'title'    => __( 'Overwrite by Billing Country', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Tries to overwrite Country by User Billing Country on Checkout Page.', 'woocommerce-jetpack' ),
					'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_by_country_selection_billing_country_overwrite',
					'default'  => 'no',
					'type'     => 'checkbox',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'wcj_product_by_country_selection_options',
				),
				array(
					'title' => __( 'Admin Country List Options', 'woocommerce-jetpack' ),
					'type'  => 'title',
					'id'    => 'wcj_product_by_country_admin_country_list_options',
				),
				array(
					'title'             => __( 'Country List', 'woocommerce-jetpack' ),
					'desc_tip'          => __( 'This option sets which countries will be added to list in product\'s edit page. Possible values: "All countries" or "WooCommerce selling locations".', 'woocommerce-jetpack' ),
					'desc'              => sprintf(
						/* translators: %s: translation added */
						'<p>' . __( 'If "WooCommerce selling locations" option is selected, country list will be set by <a href="%s">WooCommerce > Settings > General > Selling location(s)</a>.', 'woocommerce-jetpack' ),
						admin_url( 'admin.php?page=wc-settings' )
					) .
						'<br>' . apply_filters( 'booster_message', '', 'desc' ) . '</p>',
					'id'                => 'wcj_product_by_country_country_list',
					'default'           => 'all',
					'type'              => 'select',
					'options'           => array(
						'all' => __( 'All countries', 'woocommerce-jetpack' ),
						'wc'  => __( 'WooCommerce selling locations', 'woocommerce-jetpack' ),
					),
					'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
					'css'               => 'min-width:250px;',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'wcj_product_by_country_admin_country_list_options',
				),
			);
		}

	}

endif;

return new WCJ_Product_By_Country();

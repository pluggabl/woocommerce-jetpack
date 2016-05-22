<?php
/**
 * WooCommerce Jetpack Product by Country
 *
 * The WooCommerce Jetpack Product by Country class.
 *
 * @version 2.5.0
 * @since   2.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_By_Country' ) ) :

class WCJ_Product_By_Country extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function __construct() {

		$this->id         = 'product_by_country';
		$this->short_desc = __( 'Product by Country', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display WooCommerce products by customers country.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-by-country/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				add_filter( 'woocommerce_product_is_visible', array( $this, 'product_by_country' ), PHP_INT_MAX, 2 );
			}
		}
	}

	/**
	 * product_by_country.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function product_by_country( $visible, $product_id ) {
		// Get the country by IP
		$location = WC_Geolocation::geolocate_ip();
		// Base fallback
		if ( empty( $location['country'] ) ) {
			$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) ) );
		}
		$country = ( isset( $location['country'] ) ) ? $location['country'] : '';
		$visible_countries = get_post_meta( $product_id, '_' . 'wcj_product_by_country_visible', true );
		if ( is_array( $visible_countries ) && ! in_array( $country, $visible_countries ) ) {
			return false;
		}
		return $visible;
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function get_meta_box_options() {
		$options = array(
			array(
				'name'       => 'wcj_product_by_country_visible',
				'default'    => '',
				'type'       => 'select',
				'options'    => wcj_get_countries(),
				'multiple'   => true,
				'title'      => __( 'Visible in Countries', 'woocommerce-jetpack' ),
			),
		);
		return $options;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function get_settings() {
		$settings = array();
		return $this->add_standard_settings( $settings, __( 'When enabled, module will add new "Booster: Product by Country" meta box to each product\'s edit page.', 'woocommerce-jetpack' ) );
	}
}

endif;

return new WCJ_Product_By_Country();

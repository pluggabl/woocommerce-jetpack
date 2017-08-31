<?php
/**
 * Booster for WooCommerce - Module - Product Visibility by Country
 *
 * @version 3.1.0
 * @since   2.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_By_Country' ) ) :

class WCJ_Product_By_Country extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.1.0
	 * @since   2.5.0
	 */
	function __construct() {

		$this->id         = 'product_by_country';
		$this->short_desc = __( 'Product Visibility by Country', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display WooCommerce products by customer\'s country.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-visibility-by-country';
		$this->extra_desc = __( 'When enabled, module will add new "Booster: Product Visibility by Country" meta box to each product\'s edit page.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Product meta box
			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			// Core
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				if ( 'yes' === get_option( 'wcj_product_by_country_visibility', 'yes' ) ) {
					add_filter( 'woocommerce_product_is_visible', array( $this, 'product_by_country' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_product_by_country_purchasable', 'no' ) ) {
					add_filter( 'woocommerce_is_purchasable',     array( $this, 'product_by_country_purchasable' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_product_by_country_query', 'no' ) ) {
					add_action( 'pre_get_posts',                  array( $this, 'product_by_country_pre_get_posts' ) );
				}
				if ( 'manual' === get_option( 'wcj_product_by_country_selection_method', 'by_ip' ) ) {
					add_action( 'init',                           array( $this, 'save_country_in_session' ), PHP_INT_MAX ) ;
				}
			}
			// Admin products list
			if ( 'yes' === get_option( 'wcj_product_by_country_add_column_visible_countries', 'no' ) ) {
				add_filter( 'manage_edit-product_columns',        array( $this, 'add_product_columns' ),   PHP_INT_MAX );
				add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * add_product_columns.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function add_product_columns( $columns ) {
		$columns[ 'wcj_product_by_country_visible_countries' ] = __( 'Countries', 'woocommerce-jetpack' );
		return $columns;
	}

	/**
	 * render_product_column.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function render_product_column( $column ) {
		if ( 'wcj_product_by_country_visible_countries' === $column ) {
			if ( $countries = get_post_meta( get_the_ID(), '_' . 'wcj_product_by_country_visible', true ) ) {
				if ( is_array( $countries ) ) {
					echo implode( ', ', $countries );
				}
			}
		}
	}

	/**
	 * product_by_country_pre_get_posts.
	 *
	 * @version 3.1.0
	 * @since   2.9.0
	 */
	function product_by_country_pre_get_posts( $query ) {
		if ( is_admin() ) {
			return;
		}
		remove_action( 'pre_get_posts', array( $this, 'product_by_country_pre_get_posts' ) );
		$country = $this->get_country();
		// Calculate `post__not_in`
		$post__not_in = $query->get( 'post__not_in' );
		$args = $query->query;
		$args['fields'] = 'ids';
		$loop = new WP_Query( $args );
		foreach ( $loop->posts as $product_id ) {
			$visible_countries = get_post_meta( $product_id, '_' . 'wcj_product_by_country_visible', true );
			if ( is_array( $visible_countries ) && ! in_array( $country, $visible_countries ) ) {
				$post__not_in[] = $product_id;
			}
		}
		$query->set( 'post__not_in', $post__not_in );
		add_action( 'pre_get_posts', array( $this, 'product_by_country_pre_get_posts' ) );
	}

	/**
	 * product_by_country_purchasable.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function product_by_country_purchasable( $purchasable, $_product ) {
		return $this->product_by_country( $purchasable, wcj_get_product_id_or_variation_parent_id( $_product ) );
	}

	/**
	 * product_by_country.
	 *
	 * @version 3.1.0
	 * @since   2.5.0
	 */
	function product_by_country( $visible, $product_id ) {
		$country = $this->get_country();
		$visible_countries = get_post_meta( $product_id, '_' . 'wcj_product_by_country_visible', true );
		if ( is_array( $visible_countries ) && ! in_array( $country, $visible_countries ) ) {
			return false;
		}
		return $visible;
	}

	/**
	 * save_country_in_session.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 * @todo    (maybe) option to use standard sessions instead of WC sessions
	 */
	function save_country_in_session() {
		if ( isset( $_REQUEST['wcj_country_selector'] ) ) {
			if ( ! WC()->session->has_session() ) {
				WC()->session->set_customer_session_cookie( true );
			}
			WC()->session->set( 'wcj_selected_country', $_REQUEST['wcj_country_selector'] );
		}
	}

	/**
	 * get_country.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function get_country() {
		if ( 'manual' === get_option( 'wcj_product_by_country_selection_method', 'by_ip' ) ) {
			if ( '' == WC()->session->get( 'wcj_selected_country' ) ) {
				if ( ! WC()->session->has_session() ) {
					WC()->session->set_customer_session_cookie( true );
				}
				$country = $this->get_country_by_ip();
				WC()->session->set( 'wcj_selected_country', $country );
				return $country;
			} else {
				return WC()->session->get( 'wcj_selected_country' );
			}
		} else {
			return $this->get_country_by_ip();
		}
	}

	/**
	 * get_country_by_ip.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 * @todo    move to global functions
	 */
	function get_country_by_ip() {
		// Get the country by IP
		$location = ( class_exists( 'WC_Geolocation' ) ? WC_Geolocation::geolocate_ip() : array( 'country' => '' ) );
		// Base fallback
		if ( empty( $location['country'] ) ) {
			$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) ) );
		}
		return ( isset( $location['country'] ) ) ? $location['country'] : '';
	}

}

endif;

return new WCJ_Product_By_Country();

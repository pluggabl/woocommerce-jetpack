<?php
/**
 * Booster for WooCommerce - Module - Products per Page
 *
 * @version 5.2.0
 * @since   2.6.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Products_Per_Page' ) ) :

class WCJ_Products_Per_Page extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   2.6.0
	 * @todo    (dev) position priority for every hook
	 * @todo    (dev) post or get
	 * @todo    (dev) (maybe) make `session` the default `$this->products_per_page_saving_method`
	 */
	function __construct() {

		$this->id         = 'products_per_page';
		$this->short_desc = __( 'Products per Page', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add "products per page" selector to WooCommerce (Select options available in Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Add "products per page" selector to WooCommerce.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-products-per-page';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( 'session' === ( $this->products_per_page_saving_method = wcj_get_option( 'wcj_products_per_page_saving_method', 'cookie' ) ) ) {
				add_action( 'init', 'wcj_session_maybe_start' );
			}
			add_filter( 'loop_shop_per_page', array( $this, 'set_products_per_page_number' ), PHP_INT_MAX );
			add_filter( 'option_et_divi', array( $this, 'set_products_per_page_number_on_divi' ), PHP_INT_MAX );
			$position_hooks = wcj_get_option( 'wcj_products_per_page_position', array( 'woocommerce_before_shop_loop' ) );
			foreach ( $position_hooks as $position_hook ) {
				add_action( $position_hook, array( $this, 'add_products_per_page_form' ), wcj_get_option( 'wcj_products_per_page_position_priority', 40 ) );
			}
		}
	}

	/**
	 * add_products_per_page_form.
	 *
	 * @version 4.7.0
	 * @since   2.5.3
	 */
	function add_products_per_page_form() {

		global $wp_query;

		$products_per_page = $this->get_current_products_per_page_number( false );

		$paged = get_query_var( 'paged' );
		if ( 0 == $paged ) {
			$paged = 1;
		}

		$products_from  = ( $paged - 1 ) * $products_per_page + 1;
		$products_to    = ( $paged - 1 ) * $products_per_page + $wp_query->post_count;
		$products_total = $wp_query->found_posts;

		$select_form = '<select name="wcj_products_per_page" id="wcj_products_per_page" class="sortby rounded_corners_class" onchange="this.form.submit()">';
		$products_per_page_select_options = apply_filters( 'booster_option', implode( PHP_EOL, array( '10|10', '25|25', '50|50', '100|100', 'All|-1' ) ),
			get_option( 'wcj_products_per_page_select_options', implode( PHP_EOL, array( '10|10', '25|25', '50|50', '100|100', 'All|-1' ) ) ) );
		$products_per_page_select_options = array_map( 'trim', explode( PHP_EOL, $products_per_page_select_options ) );
		foreach ( $products_per_page_select_options as $products_per_page_select_option ) {
			$_option = array_map( 'trim', explode( '|', $products_per_page_select_option, 2 ) );
			if ( 2 === count( $_option ) ) {
				$sort_id   = $_option[1];
				$sort_name = $_option[0];
				$select_form .= '<option value="' . $sort_id . '" ' . selected( $products_per_page, $sort_id, false ) . ' >' . $sort_name . '</option>';
			}
		}
		$select_form .= '</select>';
		$form_method = wcj_get_option( 'wcj_products_per_page_form_method', 'post' );
		$html = '';
		$html .= wcj_get_option( 'wcj_products_per_page_text_before', '<div class="clearfix"></div><div>' );
		$html .= '<form action="' . esc_url( remove_query_arg( 'paged' ) ) . '" method="' . $form_method . '">';
		$_text = wcj_get_option( 'wcj_products_per_page_text',
			__( 'Products <strong>%from% - %to%</strong> from <strong>%total%</strong>. Products on page %select_form%', 'woocommerce-jetpack' ) );
		$html .= str_replace( array( '%from%', '%to%', '%total%', '%select_form%' ), array( $products_from, $products_to, $products_total, $select_form ), $_text );
		$html .= '</form>';
		$html .= wcj_get_option( 'wcj_products_per_page_text_after', '</div>' );

		echo $html;
	}

	/**
	 * set_products_per_page_number.
	 *
	 * @version 3.8.0
	 * @since   2.5.3
	 */
	function set_products_per_page_number( $products_per_page ) {
		return $this->get_current_products_per_page_number( true );
	}

	/**
	 * Sets products per page on DIVI theme.
	 *
	 * @version 4.1.0
	 * @since   4.1.0
	 *
	 * @param $option
	 *
	 * @return mixed
	 */
	function set_products_per_page_number_on_divi( $option ) {
		$option['divi_woocommerce_archive_num_posts'] = $this->get_current_products_per_page_number( true );
		return $option;
	}

	/**
	 * get_current_products_per_page_number.
	 *
	 * @version 4.7.0
	 * @since   3.8.0
	 */
	function get_current_products_per_page_number( $do_save ) {
		if ( isset( $_REQUEST['wcj_products_per_page'] ) && ! empty( $_REQUEST['wcj_products_per_page'] ) ) {
			if ( $do_save ) {
				$this->save_products_per_page_number( intval( sanitize_text_field( $_REQUEST['wcj_products_per_page'] ) ) );
			}
			return intval( sanitize_text_field( $_REQUEST['wcj_products_per_page'] ) );
		} elseif ( $products_per_page = $this->get_saved_products_per_page_number() ) {
			return $products_per_page;
		} else {
			return wcj_get_option( 'wcj_products_per_page_default', 10 ); // default
		}
	}

	/**
	 * save_products_per_page_number.
	 *
	 * @version 3.8.0
	 * @since   3.8.0
	 */
	function save_products_per_page_number( $products_per_page ) {
		switch ( $this->products_per_page_saving_method ) {
			case 'session':
				wcj_session_set( 'wcj_products_per_page', $products_per_page );
				break;
			default: // 'cookie'
				setcookie( 'wcj_products_per_page', $products_per_page, ( time() + 1209600 ), '/', $_SERVER['SERVER_NAME'], false );
		}
	}

	/**
	 * get_saved_products_per_page_number.
	 *
	 * @version 3.8.0
	 * @since   3.8.0
	 */
	function get_saved_products_per_page_number() {
		switch ( $this->products_per_page_saving_method ) {
			case 'session':
				return wcj_session_get( 'wcj_products_per_page', false );
			default: // 'cookie'
				return ( isset( $_COOKIE['wcj_products_per_page'] ) ?  $_COOKIE['wcj_products_per_page'] : false );
		}
	}

}

endif;

return new WCJ_Products_Per_Page();

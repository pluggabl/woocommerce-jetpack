<?php
/**
 * Booster for WooCommerce - Module - Products per Page
 *
 * @version 2.9.0
 * @since   2.6.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Products_Per_Page' ) ) :

class WCJ_Products_Per_Page extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @since   2.6.0
	 * @todo    position priority for every hook
	 * @todo    post or get
	 * @todo    cookie - optional (or at least add description in admin settings)
	 */
	function __construct() {

		$this->id         = 'products_per_page';
		$this->short_desc = __( 'Products per Page', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add "products per page" selector to WooCommerce.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-products-per-page';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'loop_shop_per_page', array( $this, 'set_products_per_page_number' ), PHP_INT_MAX );
			$position_hooks = get_option( 'wcj_products_per_page_position', array( 'woocommerce_before_shop_loop' ) );
			foreach ( $position_hooks as $position_hook ) {
				add_action( $position_hook, array( $this, 'add_products_per_page_form' ), get_option( 'wcj_products_per_page_position_priority', 40 ) );
			}
		}
	}

	/**
	 * add_products_per_page_form.
	 *
	 * @version 2.9.0
	 * @since   2.5.3
	 */
	function add_products_per_page_form() {

		global $wp_query;

		if ( isset( $_REQUEST['wcj_products_per_page'] ) ) {
			$products_per_page = $_REQUEST['wcj_products_per_page'];
		} elseif ( isset( $_COOKIE['wcj_products_per_page'] ) ) {
			$products_per_page = $_COOKIE['wcj_products_per_page'];
		} else {
			$products_per_page = get_option( 'wcj_products_per_page_default', 10 ); // default
		}

		$paged = get_query_var( 'paged' );
		if ( 0 == $paged ) {
			$paged = 1;
		}

		$products_from  = ( $paged - 1 ) * $products_per_page + 1;
		$products_to    = ( $paged - 1 ) * $products_per_page + $wp_query->post_count;
		$products_total = $wp_query->found_posts;

		$select_form = '<select name="wcj_products_per_page" id="wcj_products_per_page" class="sortby rounded_corners_class" onchange="this.form.submit()">';
		$products_per_page_select_options = apply_filters( 'booster_option',
			'10|10' . PHP_EOL . '25|25' . PHP_EOL . '50|50' . PHP_EOL . '100|100' . PHP_EOL . 'All|-1',
			get_option( 'wcj_products_per_page_select_options', '10|10' . PHP_EOL . '25|25' . PHP_EOL . '50|50' . PHP_EOL . '100|100' . PHP_EOL . 'All|-1' ) );
		$products_per_page_select_options = explode( PHP_EOL, $products_per_page_select_options );
		foreach ( $products_per_page_select_options as $products_per_page_select_option ) {
			$the_option = explode( '|', $products_per_page_select_option );
			if ( 2 === count( $the_option ) ) {
				$sort_id   = $the_option[1];
				$sort_name = $the_option[0];
				$sort_id   = str_replace( "\n", '', $sort_id );
				$sort_id   = str_replace( "\r", '', $sort_id );
				$sort_name = str_replace( "\n", '', $sort_name );
				$sort_name = str_replace( "\r", '', $sort_name );
				$select_form .= '<option value="' . $sort_id . '" ' . selected( $products_per_page, $sort_id, false ) . ' >' . $sort_name . '</option>';
			}
		}
		$select_form .= '</select>';

		$html = '';
		$html .= get_option( 'wcj_products_per_page_text_before', '<div class="clearfix"></div><div>' );
		$html .= '<form action="' . remove_query_arg( 'paged' ) . '" method="POST">';
		$the_text = get_option( 'wcj_products_per_page_text',
			__( 'Products <strong>%from% - %to%</strong> from <strong>%total%</strong>. Products on page %select_form%', 'woocommerce-jetpack' ) );
		$html .= str_replace( array( '%from%', '%to%', '%total%', '%select_form%' ), array( $products_from, $products_to, $products_total, $select_form ), $the_text );
		$html .= '</form>';
		$html .= get_option( 'wcj_products_per_page_text_after', '</div>' );

		echo $html;
	}

	/**
	 * set_products_per_page_number.
	 *
	 * @version 2.9.0
	 * @since   2.5.3
	 */
	function set_products_per_page_number( $products_per_page ) {
		if ( isset( $_REQUEST['wcj_products_per_page'] ) ) {
			$products_per_page = $_REQUEST['wcj_products_per_page'];
			setcookie( 'wcj_products_per_page', $products_per_page, ( time() + 1209600 ), '/', $_SERVER['SERVER_NAME'], false );
		} elseif ( isset( $_COOKIE['wcj_products_per_page'] ) ) {
			$products_per_page = $_COOKIE['wcj_products_per_page'];
		} else {
			$products_per_page = get_option( 'wcj_products_per_page_default', 10 );
		}
		return $products_per_page;
	}

}

endif;

return new WCJ_Products_Per_Page();

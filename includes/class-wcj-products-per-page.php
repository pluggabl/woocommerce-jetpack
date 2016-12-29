<?php
/**
 * WooCommerce Jetpack Products per Page
 *
 * The WooCommerce Jetpack Products per Page class.
 *
 * @version 2.6.0
 * @since   2.6.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Products_Per_Page' ) ) :

class WCJ_Products_Per_Page extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 * @todo    position priority for every hook
	 * @todo    post or get
	 */
	function __construct() {

		$this->id         = 'products_per_page';
		$this->short_desc = __( 'Products per Page', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce products per page.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-products-per-page/';
		parent::__construct();

		add_action( 'init', array( $this, 'add_settings_hook' ) );

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
	 * @version 2.6.0
	 * @since   2.5.3
	 */
	function add_products_per_page_form() {

		global $wp_query;

		if ( isset( $_POST['wcj_products_per_page'] ) ) {
			$products_per_page = $_POST['wcj_products_per_page'];
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

		$html = '';
		$html .= '<div class="clearfix"></div>';
		$html .= '<div>';
		$html .= '<form action="' . remove_query_arg( 'paged' ) . '" method="POST">';
		$the_text = get_option( 'wcj_products_per_page_text', __( 'Products <strong>%from% - %to%</strong> from <strong>%total%</strong>. Products on page %select_form%', 'woocommerce-jetpack' ) );
		$select_form = '<select name="wcj_products_per_page" id="wcj_products_per_page" class="sortby rounded_corners_class" onchange="this.form.submit()">';
		$html .= str_replace( array( '%from%', '%to%', '%total%', '%select_form%' ), array( $products_from, $products_to, $products_total, $select_form ), $the_text );
		$products_per_page_select_options = apply_filters( 'booster_get_option', '10|10' . PHP_EOL . '25|25' . PHP_EOL . '50|50' . PHP_EOL . '100|100' . PHP_EOL . 'All|-1', get_option( 'wcj_products_per_page_select_options', '10|10' . PHP_EOL . '25|25' . PHP_EOL . '50|50' . PHP_EOL . '100|100' . PHP_EOL . 'All|-1' ) );
		$products_per_page_select_options = explode( PHP_EOL, $products_per_page_select_options );
		foreach ( $products_per_page_select_options as $products_per_page_select_option ) {
			$the_option = explode( '|', $products_per_page_select_option );
			if ( 2 === count( $the_option ) ) {
				$sort_id   = $the_option[1];
				$sort_name = $the_option[0];
				$sort_id = str_replace( "\n", '', $sort_id );
				$sort_id = str_replace( "\r", '', $sort_id );
				$sort_name = str_replace( "\n", '', $sort_name );
				$sort_name = str_replace( "\r", '', $sort_name );
				$html .= '<option value="' . $sort_id . '" ' . selected( $products_per_page, $sort_id, false ) . ' >' . $sort_name . '</option>';
			}
		}
		$html .= '</select>';
		$html .= '</form>';
		$html .= '</div>';

		echo $html;
	}

	/**
	 * set_products_per_page_number.
	 *
	 * @version 2.6.0
	 * @since   2.5.3
	 */
	function set_products_per_page_number( $the_number ) {
		if ( isset( $_POST['wcj_products_per_page'] ) ) {
			$the_number = $_POST['wcj_products_per_page'];
			setcookie( 'wcj_products_per_page', $the_number, ( time() + 1209600 ), '/', $_SERVER['SERVER_NAME'], false );
		} elseif ( isset( $_COOKIE['wcj_products_per_page'] ) ) {
			$the_number = $_COOKIE['wcj_products_per_page'];
		} else {
			$the_number = get_option( 'wcj_products_per_page_default', 10 );
		}
		return $the_number;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function get_settings() {
		return $this->add_standard_settings( apply_filters( 'wcj_products_per_page_settings', array() ) );
	}

	/*
	 * add_settings_hook.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function add_settings_hook() {
		add_filter( 'wcj_products_per_page_settings', array( $this, 'add_settings' ) );
	}

	/*
	 * add_settings.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function add_settings() {
		return array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_products_per_page_options',
			),
			array(
				'title'    => __( 'Select Options', 'woocommerce-jetpack' ),
				'desc'     => __( 'Name|Number; one per line; -1 for all products', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_per_page_select_options',
				'default'  => '10|10' . PHP_EOL . '25|25' . PHP_EOL . '50|50' . PHP_EOL . '100|100' . PHP_EOL . 'All|-1',
				'type'     => 'textarea',
				'css'      => 'height:200px;',
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
				'desc_tip' => apply_filters( 'booster_get_message', '', 'desc_no_link' ),
			),
			array(
				'title'    => __( 'Default', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_per_page_default',
				'default'  => 10,
				'type'     => 'number',
				'custom_attributes' => array( 'min' => -1 ),
			),
			array(
				'title'    => __( 'Position', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_per_page_position',
				'default'  => array( 'woocommerce_before_shop_loop' ),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => array(
					'woocommerce_before_shop_loop' => __( 'Before shop loop', 'woocommerce-jetpack' ),
					'woocommerce_after_shop_loop'  => __( 'After shop loop', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Position Priority', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_per_page_position_priority',
				'default'  => 40,
				'type'     => 'number',
				'custom_attributes' => array( 'min' => 0 ),
			),
			array(
				'title'    => __( 'Text', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_per_page_text',
				'default'  => __( 'Products <strong>%from% - %to%</strong> from <strong>%total%</strong>. Products on page %select_form%', 'woocommerce-jetpack' ),
				'type'     => 'textarea',
				'css'      => 'width:66%;min-width:300px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_products_per_page_options',
			),
		);
	}

}

endif;

return new WCJ_Products_Per_Page();

<?php
/**
 * Booster for WooCommerce - Module - Sales Notifications
 *
 * @version 7.3.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Sales_Notifications' ) ) :

	/**
	 * WCJ_sales_notifications.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	class WCJ_Sales_Notifications extends WCJ_Module {
		/**
		 * Settings.
		 *
		 * @version 1.0.0
		 * @var settings
		 */
		protected $settings;

		/**
		 * Lang.
		 *
		 * @version 1.0.0
		 * @var lang
		 */
		protected $lang;

		/**
		 * Constructor.
		 *
		 * @version 1.0.0
		 */
		public function __construct() {

			$this->id         = 'sales_notifications';
			$this->short_desc = __( 'Sales Notifications', 'woocommerce-jetpack' );
			$this->desc       = __( 'Sales Notifications.', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Sales Notifications.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-sales-notifications';

			parent::__construct();

			if ( $this->is_enabled() ) {

				if ( wcj_is_frontend() ) {
					add_action( 'wp_enqueue_scripts', array( $this, 'wcj_sn_enqueue_scripts' ) );
					add_action( 'wp_head', array( $this, 'wcj_sales_notifications_pop_styling' ), PHP_INT_MAX );
					add_action( 'wp_footer', array( $this, 'wcj_sales_notifications_pop' ) );
					if ( true === wcj_is_hpos_enabled() ) {
						add_action( 'wp_ajax_wcj_sale_not_product_html_hpos', array( $this, 'wcj_sale_not_product_html_hpos' ) );
						add_action( 'wp_ajax_nopriv_wcj_sale_not_product_html_hpos', array( $this, 'wcj_sale_not_product_html_hpos' ) );
					} else {
						add_action( 'wp_ajax_wcj_sale_not_product_html', array( $this, 'wcj_sale_not_product_html' ) );
						add_action( 'wp_ajax_nopriv_wcj_sale_not_product_html', array( $this, 'wcj_sale_not_product_html' ) );
					}
				}
			}
		}

		/**
		 * Wcj_sales_notifications_pop_styling.
		 *
		 * @version 1.0.0
		 */
		public function wcj_sales_notifications_pop_styling() {
			$styling_default = array(
				'width'   => '30%',
				'bgcolor' => '#ffffff',
				'color'   => '#000000',
			);

			$styling_options = wcj_get_option( 'wcj_sale_msg_styling', array() );
			foreach ( $styling_default as $option => $default ) {
				if ( ! isset( $styling_options[ $option ] ) ) {
					$styling_options[ $option ] = $default;
				}
			}

			echo wp_kses_post(
				"<style type=\"text/css\">
				.wcj_sale_notification {
					width: {$styling_options['width']};
					background-color: {$styling_options['bgcolor']};
					color: {$styling_options['color']};
				}
				.wcj_sale_notification a{
					width: {$styling_options['width']};
					background-color: {$styling_options['bgcolor']};
					color: {$styling_options['color']};
				}
			</style>"
			);
		}

		/**
		 * Wcj_sale_not_product_html.
		 *
		 * @version 1.0.0
		 */
		public function wcj_sale_not_product_html() {
			$wpnonce                    = isset( $_REQUEST['wcj-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-nonce'] ), 'wcj-nonce' ) : false;
			$pageid                     = isset( $_REQUEST['pageid'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['pageid'] ) ) : '';
			$no_exists_value            = wcj_get_option( 'no_exists_value' );
			$wcj_sale_msg_ajax          = wcj_get_option( 'wcj_sale_msg_ajax', 'yes' );
			$wcj_sale_msg_msg           = wcj_get_option( 'wcj_sale_msg_msg', '' );
			$wcj_sale_msg_img           = wcj_get_option( 'wcj_sale_msg_img', '1' );
			$wcj_sale_msg_screen        = wcj_get_option( 'wcj_sale_msg_screen', 'wcj_desktop' );
			$wcj_sale_msg_position      = wcj_get_option( 'wcj_sale_msg_position', 'wcj_bottom_left' );
			$styling_options            = wcj_get_option( 'wcj_sale_msg_styling', array() );
			$styling_options            = wcj_get_option( 'wcj_sale_msg_styling', array() );
			$wcj_sale_msg_repeat        = wcj_get_option( 'wcj_sale_msg_repeat', 'no' );
			$wcj_sale_msg_duration      = wcj_get_option( 'wcj_sale_msg_duration', '2' );
			$wcj_sale_msg_next          = wcj_get_option( 'wcj_sale_msg_next', '5' );
			$wcj_orders_editable_status = array( 'processing', 'completed' );
			$wcj_sn_product_include     = wcj_get_option( 'wcj_sn_product_include', array() );
			$wcj_sn_product_exclude     = wcj_get_option( 'wcj_sn_product_exclude', array() );
			$select2_search__field      = wcj_get_option( 'select2-search__field', '' );
			$wcj_sn_product_cat_include = wcj_get_option( 'wcj_sn_product_cat_include', array() );
			$wcj_sn_product_cat_exclude = wcj_get_option( 'wcj_sn_product_cat_exclude', array() );
			$wcj_sound_enable           = wcj_get_option( 'wcj_sound_enable', 'no' );
			$wcj_sale_msg_sound         = wcj_get_option( 'wcj_sale_msg_sound', 'cool.mp3' );
			$wcj_sn_page_assign         = wcj_get_option( 'wcj_sn_page_assign', array() );
			$wcj_sale_msg_hide_all      = wcj_get_option( 'wcj_sale_msg_hide_all', '' );

			$wcj_order_data_str     = ( isset( $_COOKIE['wcj_order_data'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['wcj_order_data'] ) ) : false );
			$wcj_order_data_str_arr = explode( ',', $wcj_order_data_str );
			$args                   = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => '100',
				'post__not_in'   => $wcj_order_data_str_arr,
				'orderby'        => 'date',
				'order'          => 'ASC',
			);
			$current_time           = '';
			$order_threshold_num    = '60';
			$order_threshold_time   = '2';
			if ( $order_threshold_num ) {
				switch ( $order_threshold_time ) {
					case 1:
						$time_type = 'days';
						break;
					case 2:
						$time_type = 'minutes';
						break;
					default:
						$time_type = 'hours';
				}
				$current_time = strtotime( '-' . $order_threshold_num . ' ' . $time_type );
			}
			if ( $current_time ) {
				$args['date_query'] = array(
					array(
						'after'     => array(
							'year'   => gmdate( 'Y', $current_time ),
							'month'  => gmdate( 'm', $current_time ),
							'day'    => gmdate( 'd', $current_time ),
							'hour'   => gmdate( 'H', $current_time ),
							'minute' => gmdate( 'i', $current_time ),
							'second' => gmdate( 's', $current_time ),
						),
						'inclusive' => true,
						'compare'   => '<=',
						'column'    => 'post_date',
						'relation'  => 'AND',
					),
				);
			}

			$my_query = new WP_Query( $args );

			$products   = array();
			$thumb      = '';
			$first_name = '';
			$last_name  = '';
			$city       = '';
			$state      = '';
			$country    = '';
			$link       = '';
			$title      = '';
			$time       = '';
			if ( $my_query->have_posts() ) {
				$new_order = 1;
				$i         = 0;
				while ( $my_query->have_posts() ) {
					$my_query->the_post();
					$order_id = get_the_ID();

					$order                            = wc_get_order( get_the_ID() );
					$wcj_orders_editable_status_check = array_map(
						function ( $arr_status ) {
							return (string) $arr_status;
						},
						$wcj_orders_editable_status
					);

					$wcj_sn_page_assign_check        = array_map(
						function ( $arrpage ) {
							return (string) $arrpage;
						},
						$wcj_sn_page_assign
					);
					$wcj_sn_page_assign_check_result = array_intersect( (array) $pageid, $wcj_sn_page_assign_check );
					if ( in_array( $order->get_status(), $wcj_orders_editable_status_check )  ) // phpcs:ignore 
					{
						$items = $order->get_items();
						foreach ( $items as $item ) {
							$p_data            = wc_get_product( $item['product_id'] );
							$category_ids      = array();
							$product_cat_terms = get_the_terms( $item['product_id'], 'product_cat' );
							foreach ( $product_cat_terms as $term ) {
								$category_ids[] = $term->id;
							}

							$wcj_sn_product_cat_exclude_result = array_intersect( $category_ids, $wcj_sn_product_cat_exclude );

							if ( 0 === count( $wcj_sn_product_cat_exclude_result ) ) {

								if ( 0 !== count( $wcj_sn_page_assign_check_result ) ) {
									$new_order = 1;
								} elseif ( 0 === count( $wcj_sn_page_assign ) ) {
									$new_order_id = 0;
								} else {
									$new_order = 0;
								}
								$wcj_sn_product_include_check = array_map(
									function ( $arrinc ) {
										return (string) $arrinc;
									},
									$wcj_sn_product_include
								);
								if ( in_array( $p_data->get_id(), $wcj_sn_product_include_check ) ) // phpcs:ignore
								{

									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$sale_price = $p_data->get_sale_price();
									if ( '' === $sale_price ) {
										$price = $p_data->get_price_html();
									} else {
										$price = $p_data->get_price_html();
									}
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( get_post_meta( get_the_ID(), '_billing_first_name', true ) );
									$last_name    = ucfirst( get_post_meta( get_the_ID(), '_billing_last_name', true ) );
									$city         = ucfirst( get_post_meta( get_the_ID(), '_billing_city', true ) );
									$state        = ucfirst( get_post_meta( get_the_ID(), '_billing_state', true ) );
									$country      = ucfirst( WC()->countries->countries[ get_post_meta( get_the_ID(), '_billing_country', true ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								} elseif ( 0 === count( $wcj_sn_product_include_check ) ) {
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$sale_price = $p_data->get_sale_price();
									if ( '' === $sale_price ) {
										$price = $p_data->get_price_html();
									} else {
										$price = $p_data->get_price_html();
									}
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( get_post_meta( get_the_ID(), '_billing_first_name', true ) );
									$last_name    = ucfirst( get_post_meta( get_the_ID(), '_billing_last_name', true ) );
									$city         = ucfirst( get_post_meta( get_the_ID(), '_billing_city', true ) );
									$state        = ucfirst( get_post_meta( get_the_ID(), '_billing_state', true ) );
									$country      = ucfirst( WC()->countries->countries[ get_post_meta( get_the_ID(), '_billing_country', true ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								} else {
									$new_order = 'hide';
								}

								$wcj_sn_product_exclude_check = array_map(
									function ( $arrexc ) {
										return (string) $arrexc;
									},
									$wcj_sn_product_exclude
								);

								if ( in_array( $p_data->get_id(), $wcj_sn_product_exclude_check ) ) // phpcs:ignore
								{
									$new_order = 'hide';
								} elseif ( 0 === count( $wcj_sn_product_exclude_check ) ) {
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$price        = wc_price( $p_data->get_price() );
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( get_post_meta( get_the_ID(), '_billing_first_name', true ) );
									$last_name    = ucfirst( get_post_meta( get_the_ID(), '_billing_last_name', true ) );
									$city         = ucfirst( get_post_meta( get_the_ID(), '_billing_city', true ) );
									$state        = ucfirst( get_post_meta( get_the_ID(), '_billing_state', true ) );
									$country      = ucfirst( WC()->countries->countries[ get_post_meta( get_the_ID(), '_billing_country', true ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								} else {
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$price        = wc_price( $p_data->get_price() );
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( get_post_meta( get_the_ID(), '_billing_first_name', true ) );
									$last_name    = ucfirst( get_post_meta( get_the_ID(), '_billing_last_name', true ) );
									$city         = ucfirst( get_post_meta( get_the_ID(), '_billing_city', true ) );
									$state        = ucfirst( get_post_meta( get_the_ID(), '_billing_state', true ) );
									$country      = ucfirst( WC()->countries->countries[ get_post_meta( get_the_ID(), '_billing_country', true ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								}
							} else {
								if ( 'no' === $wcj_sale_msg_repeat ) {
									break;
								}
								$new_order = 'hide';
							}

							$wcj_sn_product_cat_include_result = array_intersect( $category_ids, $wcj_sn_product_cat_include );
							if ( 0 !== count( $wcj_sn_product_cat_include_result ) ) {
								global $wp_query;
								$post_id                  = $wp_query->get_queried_object_id();
								$wcj_sn_page_assign_check = array_map(
									function ( $arrpage ) {
										return (string) $arrpage;
									},
									$wcj_sn_page_assign
								);
								if ( 0 !== count( $wcj_sn_page_assign_check_result ) ) {
									$new_order = 1;
								} else {
									$new_order = 0;
								}

								$wcj_sn_product_include_check = array_map(
									function ( $arrinc ) {
										return (string) $arrinc;
									},
									$wcj_sn_product_include
								);
								if ( in_array( $p_data->get_id(), $wcj_sn_product_include_check ) ) // phpcs:ignore
								{
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$sale_price = $p_data->get_sale_price();
									if ( '' === $sale_price ) {
										$price = $p_data->get_price_html();
									} else {
										$price = $p_data->get_price_html();
									}
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( get_post_meta( get_the_ID(), '_billing_first_name', true ) );
									$last_name    = ucfirst( get_post_meta( get_the_ID(), '_billing_last_name', true ) );
									$city         = ucfirst( get_post_meta( get_the_ID(), '_billing_city', true ) );
									$state        = ucfirst( get_post_meta( get_the_ID(), '_billing_state', true ) );
									$country      = ucfirst( WC()->countries->countries[ get_post_meta( get_the_ID(), '_billing_country', true ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								} elseif ( 0 === count( $wcj_sn_product_include_check ) ) {
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$sale_price = $p_data->get_sale_price();
									if ( '' === $sale_price ) {
										$price = $p_data->get_price_html();
									} else {
										$price = $p_data->get_price_html();
									}
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( get_post_meta( get_the_ID(), '_billing_first_name', true ) );
									$last_name    = ucfirst( get_post_meta( get_the_ID(), '_billing_last_name', true ) );
									$city         = ucfirst( get_post_meta( get_the_ID(), '_billing_city', true ) );
									$state        = ucfirst( get_post_meta( get_the_ID(), '_billing_state', true ) );
									$country      = ucfirst( WC()->countries->countries[ get_post_meta( get_the_ID(), '_billing_country', true ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								} else {
									$new_order = 'hide';
								}

								$wcj_sn_product_exclude_check = array_map(
									function ( $arrexc ) {
										return (string) $arrexc;
									},
									$wcj_sn_product_exclude
								);
								if ( in_array( $p_data->get_id(), $wcj_sn_product_exclude_check ) ) // phpcs:ignore
								{
									$new_order = 'hide';
								} elseif ( 0 === count( $wcj_sn_product_exclude_check ) ) {
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$price        = wc_price( $p_data->get_price() );
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( get_post_meta( get_the_ID(), '_billing_first_name', true ) );
									$last_name    = ucfirst( get_post_meta( get_the_ID(), '_billing_last_name', true ) );
									$city         = ucfirst( get_post_meta( get_the_ID(), '_billing_city', true ) );
									$state        = ucfirst( get_post_meta( get_the_ID(), '_billing_state', true ) );
									$country      = ucfirst( WC()->countries->countries[ get_post_meta( get_the_ID(), '_billing_country', true ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								} else {
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$price        = wc_price( $p_data->get_price() );
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( get_post_meta( get_the_ID(), '_billing_first_name', true ) );
									$last_name    = ucfirst( get_post_meta( get_the_ID(), '_billing_last_name', true ) );
									$city         = ucfirst( get_post_meta( get_the_ID(), '_billing_city', true ) );
									$state        = ucfirst( get_post_meta( get_the_ID(), '_billing_state', true ) );
									$country      = ucfirst( WC()->countries->countries[ get_post_meta( get_the_ID(), '_billing_country', true ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								}
							}
						}
					} else {
						$new_order = 'hide';
					}
					$i++;
					$order_data[ $i ] = array(
						'order_id'              => $order_id,
						'msg_screen'            => $wcj_sale_msg_screen,
						'wcj_sale_msg_hide_all' => $wcj_sale_msg_hide_all,
						'wcj_sound_enable'      => $wcj_sound_enable,
						'wcj_sale_msg_sound'    => $wcj_sale_msg_sound,
						'wcj_site_url'          => get_site_url(),
						'animation'             => $styling_options['animation'],
						'hidden_animation'      => $styling_options['hidden_animation'],
						'image_enable'          => $wcj_sale_msg_img,
						'loop'                  => $wcj_sale_msg_repeat,
						'msg_position'          => $wcj_sale_msg_position,
						'link'                  => $link,
						'order_status'          => ( isset( $order_status ) ? $order_status : '' ),
						'time'                  => $time,
						'duration'              => $wcj_sale_msg_duration,
						'next_time_display'     => $wcj_sale_msg_next,
						'thumb'                 => $thumb,
						'title'                 => $title,
						'msg'                   => ( isset( $newmsg ) ? $newmsg : '' ),
						'new_order'             => $new_order,
						'site_url'              => site_url(),
					);
				}
				wp_reset_postdata();
			} else {
				$new_order = 0;
			}

			$url = site_url() . '/wp-content/plugins/booster-elite-for-woocommerce/assets/images/close-salse.png';

			if ( 0 === $new_order ) {
				$response_data['new_order'] = $new_order;
			} else {
				$response_data['order_id']              = $order_id;
				$response_data['order_data']            = $order_data;
				$response_data['wcj_sale_msg_hide_all'] = $wcj_sale_msg_hide_all;
				$response_data['wcj_sound_enable']      = $wcj_sound_enable;
				$response_data['wcj_sale_msg_sound']    = $wcj_sale_msg_sound;
				$response_data['wcj_site_url']          = get_site_url();
				$response_data['msg_screen']            = $wcj_sale_msg_screen;
				$response_data['msg_ajax']              = $wcj_sale_msg_ajax;
				$response_data['animation']             = $styling_options['animation'];
				$response_data['hidden_animation']      = $styling_options['hidden_animation'];
				$response_data['image_enable']          = $wcj_sale_msg_img;
				$response_data['loop']                  = $wcj_sale_msg_repeat;
				$response_data['msg_position']          = $wcj_sale_msg_position;
				$response_data['link']                  = $link;
				$response_data['order_status']          = ( isset( $order_status ) ? $order_status : '' );
				$response_data['time']                  = $time;
				$response_data['duration']              = $wcj_sale_msg_duration;
				$response_data['next_time_display']     = $wcj_sale_msg_next;
				$response_data['thumb']                 = $thumb;
				$response_data['title']                 = $title;
				$response_data['msg']                   = ( isset( $newmsg ) ? $newmsg : '' );
				$response_data['new_order']             = $new_order;
				$response_data['close_img']             = $url;
				$response_data['site_url']              = site_url();
			}

			header( 'Content-type: application/json' );
			$response_data['success'] = 1;
			echo wp_json_encode( $response_data );
			wp_die();
		}

		/**
		 * Wcj_sale_not_product_html_hpos.
		 *
		 * @version 1.0.0
		 */
		public function wcj_sale_not_product_html_hpos() {
			$wpnonce                    = isset( $_REQUEST['wcj-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-nonce'] ), 'wcj-nonce' ) : false;
			$pageid                     = isset( $_REQUEST['pageid'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['pageid'] ) ) : '';
			$no_exists_value            = wcj_get_option( 'no_exists_value' );
			$wcj_sale_msg_ajax          = wcj_get_option( 'wcj_sale_msg_ajax', 'yes' );
			$wcj_sale_msg_msg           = wcj_get_option( 'wcj_sale_msg_msg', '' );
			$wcj_sale_msg_img           = wcj_get_option( 'wcj_sale_msg_img', '1' );
			$wcj_sale_msg_screen        = wcj_get_option( 'wcj_sale_msg_screen', 'wcj_desktop' );
			$wcj_sale_msg_position      = wcj_get_option( 'wcj_sale_msg_position', 'wcj_bottom_left' );
			$styling_options            = wcj_get_option( 'wcj_sale_msg_styling', array() );
			$styling_options            = wcj_get_option( 'wcj_sale_msg_styling', array() );
			$wcj_sale_msg_repeat        = wcj_get_option( 'wcj_sale_msg_repeat', 'no' );
			$wcj_sale_msg_duration      = wcj_get_option( 'wcj_sale_msg_duration', '2' );
			$wcj_sale_msg_next          = wcj_get_option( 'wcj_sale_msg_next', '5' );
			$wcj_orders_editable_status = array( 'processing', 'completed' );
			$wcj_sn_product_include     = wcj_get_option( 'wcj_sn_product_include', array() );
			$wcj_sn_product_exclude     = wcj_get_option( 'wcj_sn_product_exclude', array() );
			$select2_search__field      = wcj_get_option( 'select2-search__field', '' );
			$wcj_sn_product_cat_include = wcj_get_option( 'wcj_sn_product_cat_include', array() );
			$wcj_sn_product_cat_exclude = wcj_get_option( 'wcj_sn_product_cat_exclude', array() );
			$wcj_sound_enable           = wcj_get_option( 'wcj_sound_enable', 'no' );
			$wcj_sale_msg_sound         = wcj_get_option( 'wcj_sale_msg_sound', 'cool.mp3' );
			$wcj_sn_page_assign         = wcj_get_option( 'wcj_sn_page_assign', array() );
			$wcj_sale_msg_hide_all      = wcj_get_option( 'wcj_sale_msg_hide_all', '' );

			$wcj_order_data_str     = ( isset( $_COOKIE['wcj_order_data'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['wcj_order_data'] ) ) : false );
			$wcj_order_data_str_arr = explode( ',', $wcj_order_data_str );
			$args                   = array(
				'type'           => 'shop_order',
				'status'         => 'any',
				'posts_per_page' => '100',
				'post__not_in'   => $wcj_order_data_str_arr,
				'orderby'        => 'date',
				'order'          => 'ASC',
			);
			$current_time           = '';
			$order_threshold_num    = '60';
			$order_threshold_time   = '2';
			if ( $order_threshold_num ) {
				switch ( $order_threshold_time ) {
					case 1:
						$time_type = 'days';
						break;
					case 2:
						$time_type = 'minutes';
						break;
					default:
						$time_type = 'hours';
				}
				$current_time = strtotime( '-' . $order_threshold_num . ' ' . $time_type );
			}
			if ( $current_time ) {
				$args['date_query'] = array(
					array(
						'after'     => array(
							'year'   => gmdate( 'Y', $current_time ),
							'month'  => gmdate( 'm', $current_time ),
							'day'    => gmdate( 'd', $current_time ),
							'hour'   => gmdate( 'H', $current_time ),
							'minute' => gmdate( 'i', $current_time ),
							'second' => gmdate( 's', $current_time ),
						),
						'inclusive' => true,
						'compare'   => '<=',
						'column'    => 'date_created_gmt',
						'relation'  => 'AND',
					),
				);
			}

			$products   = array();
			$thumb      = '';
			$first_name = '';
			$last_name  = '';
			$city       = '';
			$state      = '';
			$country    = '';
			$link       = '';
			$title      = '';
			$time       = '';

			$orders = wc_get_orders( $args );

			if ( $orders ) {

				$new_order = 1;
				$i         = 0;

				foreach ( $orders as $order ) {

					$order_id = $order->get_id();

					$order = wc_get_order( $order_id );

					$wcj_orders_editable_status_check = array_map(
						function ( $arr_status ) {
							return (string) $arr_status;
						},
						$wcj_orders_editable_status
					);

					$wcj_sn_page_assign_check        = array_map(
						function ( $arrpage ) {
							return (string) $arrpage;
						},
						$wcj_sn_page_assign
					);
					$wcj_sn_page_assign_check_result = array_intersect( (array) $pageid, $wcj_sn_page_assign_check );
					if ( in_array( $order->get_status(), $wcj_orders_editable_status_check )  ) // phpcs:ignore 
					{
						$items = $order->get_items();
						foreach ( $items as $item ) {
							$p_data            = wc_get_product( $item['product_id'] );
							$category_ids      = array();
							$product_cat_terms = get_the_terms( $item['product_id'], 'product_cat' );
							foreach ( $product_cat_terms as $term ) {
								$category_ids[] = $term->id;
							}

							$wcj_sn_product_cat_exclude_result = array_intersect( $category_ids, $wcj_sn_product_cat_exclude );

							if ( 0 === count( $wcj_sn_product_cat_exclude_result ) ) {

								if ( 0 !== count( $wcj_sn_page_assign_check_result ) ) {
									$new_order = 1;
								} elseif ( 0 === count( $wcj_sn_page_assign ) ) {
									$new_order_id = 0;
								} else {
									$new_order = 0;
								}
								$wcj_sn_product_include_check = array_map(
									function ( $arrinc ) {
										return (string) $arrinc;
									},
									$wcj_sn_product_include
								);
								if ( in_array( $p_data->get_id(), $wcj_sn_product_include_check ) ) // phpcs:ignore
								{

									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$sale_price = $p_data->get_sale_price();
									if ( '' === $sale_price ) {
										$price = $p_data->get_price_html();
									} else {
										$price = $p_data->get_price_html();
									}
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( $order->get_meta( '_billing_first_name' ) );
									$last_name    = ucfirst( $order->get_meta( '_billing_last_name' ) );
									$city         = ucfirst( $order->get_meta( '_billing_city' ) );
									$state        = ucfirst( $order->get_meta( '_billing_state' ) );
									$country      = ucfirst( WC()->countries->countries[ $order->get_meta( '_billing_country' ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								} elseif ( 0 === count( $wcj_sn_product_include_check ) ) {
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$sale_price = $p_data->get_sale_price();
									if ( '' === $sale_price ) {
										$price = $p_data->get_price_html();
									} else {
										$price = $p_data->get_price_html();
									}
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( $order->get_meta( '_billing_first_name' ) );
									$last_name    = ucfirst( $order->get_meta( '_billing_last_name' ) );
									$city         = ucfirst( $order->get_meta( '_billing_city' ) );
									$state        = ucfirst( $order->get_meta( '_billing_state' ) );
									$country      = ucfirst( WC()->countries->countries[ $order->get_meta( '_billing_country' ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								} else {
									$new_order = 'hide';
								}

								$wcj_sn_product_exclude_check = array_map(
									function ( $arrexc ) {
										return (string) $arrexc;
									},
									$wcj_sn_product_exclude
								);

								if ( in_array( $p_data->get_id(), $wcj_sn_product_exclude_check ) ) // phpcs:ignore
								{
									$new_order = 'hide';
								} elseif ( 0 === count( $wcj_sn_product_exclude_check ) ) {
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$price        = wc_price( $p_data->get_price() );
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( $order->get_meta( '_billing_first_name' ) );
									$last_name    = ucfirst( $order->get_meta( '_billing_last_name' ) );
									$city         = ucfirst( $order->get_meta( '_billing_city' ) );
									$state        = ucfirst( $order->get_meta( '_billing_state' ) );
									$country      = ucfirst( WC()->countries->countries[ $order->get_meta( '_billing_country' ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								} else {
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$price        = wc_price( $p_data->get_price() );
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( $order->get_meta( '_billing_first_name' ) );
									$last_name    = ucfirst( $order->get_meta( '_billing_last_name' ) );
									$city         = ucfirst( $order->get_meta( '_billing_city' ) );
									$state        = ucfirst( $order->get_meta( '_billing_state' ) );
									$country      = ucfirst( WC()->countries->countries[ $order->get_meta( '_billing_country' ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								}
							} else {
								if ( 'no' === $wcj_sale_msg_repeat ) {
									break;
								}
								$new_order = 'hide';
							}

							$wcj_sn_product_cat_include_result = array_intersect( $category_ids, $wcj_sn_product_cat_include );
							if ( 0 !== count( $wcj_sn_product_cat_include_result ) ) {
								global $wp_query;
								$post_id                  = $wp_query->get_queried_object_id();
								$wcj_sn_page_assign_check = array_map(
									function ( $arrpage ) {
										return (string) $arrpage;
									},
									$wcj_sn_page_assign
								);
								if ( 0 !== count( $wcj_sn_page_assign_check_result ) ) {
									$new_order = 1;
								} else {
									$new_order = 0;
								}

								$wcj_sn_product_include_check = array_map(
									function ( $arrinc ) {
										return (string) $arrinc;
									},
									$wcj_sn_product_include
								);
								if ( in_array( $p_data->get_id(), $wcj_sn_product_include_check ) ) // phpcs:ignore
								{
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$sale_price = $p_data->get_sale_price();
									if ( '' === $sale_price ) {
										$price = $p_data->get_price_html();
									} else {
										$price = $p_data->get_price_html();
									}
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( $order->get_meta( '_billing_first_name' ) );
									$last_name    = ucfirst( $order->get_meta( '_billing_last_name' ) );
									$city         = ucfirst( $order->get_meta( '_billing_city' ) );
									$state        = ucfirst( $order->get_meta( '_billing_state' ) );
									$country      = ucfirst( WC()->countries->countries[ $order->get_meta( '_billing_country' ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								} elseif ( 0 === count( $wcj_sn_product_include_check ) ) {
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$sale_price = $p_data->get_sale_price();
									if ( '' === $sale_price ) {
										$price = $p_data->get_price_html();
									} else {
										$price = $p_data->get_price_html();
									}
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( $order->get_meta( '_billing_first_name' ) );
									$last_name    = ucfirst( $order->get_meta( '_billing_last_name' ) );
									$city         = ucfirst( $order->get_meta( '_billing_city' ) );
									$state        = ucfirst( $order->get_meta( '_billing_state' ) );
									$country      = ucfirst( WC()->countries->countries[ $order->get_meta( '_billing_country' ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								} else {
									$new_order = 'hide';
								}

								$wcj_sn_product_exclude_check = array_map(
									function ( $arrexc ) {
										return (string) $arrexc;
									},
									$wcj_sn_product_exclude
								);
								if ( in_array( $p_data->get_id(), $wcj_sn_product_exclude_check ) ) // phpcs:ignore
								{
									$new_order = 'hide';
								} elseif ( 0 === count( $wcj_sn_product_exclude_check ) ) {
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$price        = wc_price( $p_data->get_price() );
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( $order->get_meta( '_billing_first_name' ) );
									$last_name    = ucfirst( $order->get_meta( '_billing_last_name' ) );
									$city         = ucfirst( $order->get_meta( '_billing_city' ) );
									$state        = ucfirst( $order->get_meta( '_billing_state' ) );
									$country      = ucfirst( WC()->countries->countries[ $order->get_meta( '_billing_country' ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								} else {
									if ( 'publish' !== $p_data->get_status() ) {
										continue;
									}
									if ( 'hidden' === $p_data->get_catalog_visibility() ) {
										continue;
									}

									$price        = wc_price( $p_data->get_price() );
									$order_status = $order->get_status();
									$link         = $p_data->get_permalink();
									$title        = get_the_title( $p_data->get_id() );
									$time         = $this->wcj_time_substract( $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) );
									$thumb        = get_the_post_thumbnail_url( $p_data->get_id() );
									$first_name   = ucfirst( $order->get_meta( '_billing_first_name' ) );
									$last_name    = ucfirst( $order->get_meta( '_billing_last_name' ) );
									$city         = ucfirst( $order->get_meta( '_billing_city' ) );
									$state        = ucfirst( $order->get_meta( '_billing_state' ) );
									$country      = ucfirst( WC()->countries->countries[ $order->get_meta( '_billing_country' ) ] );

									$msg     = array( '%product_title%', '%product_link%', '%product_price%', '%customer_name%', '%customer_city%', '%customer_country%', '%purchased_time%' );
									$msg_val = array( $title, $link, $price, $first_name . ' ' . $last_name, $city, $country, $time );

									$newmsg = str_replace( $msg, $msg_val, $wcj_sale_msg_msg );
								}
							}
						}
					} else {
						$new_order = 'hide';
					}
					$i++;
					$order_data[ $i ] = array(
						'order_id'              => $order_id,
						'msg_screen'            => $wcj_sale_msg_screen,
						'wcj_sale_msg_hide_all' => $wcj_sale_msg_hide_all,
						'wcj_sound_enable'      => $wcj_sound_enable,
						'wcj_sale_msg_sound'    => $wcj_sale_msg_sound,
						'wcj_site_url'          => get_site_url(),
						'animation'             => $styling_options['animation'],
						'hidden_animation'      => $styling_options['hidden_animation'],
						'image_enable'          => $wcj_sale_msg_img,
						'loop'                  => $wcj_sale_msg_repeat,
						'msg_position'          => $wcj_sale_msg_position,
						'link'                  => $link,
						'order_status'          => ( isset( $order_status ) ? $order_status : '' ),
						'time'                  => $time,
						'duration'              => $wcj_sale_msg_duration,
						'next_time_display'     => $wcj_sale_msg_next,
						'thumb'                 => $thumb,
						'title'                 => $title,
						'msg'                   => ( isset( $newmsg ) ? $newmsg : '' ),
						'new_order'             => $new_order,
						'site_url'              => site_url(),
					);
				}
				wp_reset_postdata();
			} else {
				$new_order = 0;
			}

			$url = site_url() . '/wp-content/plugins/booster-elite-for-woocommerce/assets/images/close-salse.png';

			if ( 0 === $new_order ) {
				$response_data['new_order'] = $new_order;
			} else {
				$response_data['order_id']              = $order_id;
				$response_data['order_data']            = $order_data;
				$response_data['wcj_sale_msg_hide_all'] = $wcj_sale_msg_hide_all;
				$response_data['wcj_sound_enable']      = $wcj_sound_enable;
				$response_data['wcj_sale_msg_sound']    = $wcj_sale_msg_sound;
				$response_data['wcj_site_url']          = get_site_url();
				$response_data['msg_screen']            = $wcj_sale_msg_screen;
				$response_data['msg_ajax']              = $wcj_sale_msg_ajax;
				$response_data['animation']             = $styling_options['animation'];
				$response_data['hidden_animation']      = $styling_options['hidden_animation'];
				$response_data['image_enable']          = $wcj_sale_msg_img;
				$response_data['loop']                  = $wcj_sale_msg_repeat;
				$response_data['msg_position']          = $wcj_sale_msg_position;
				$response_data['link']                  = $link;
				$response_data['order_status']          = ( isset( $order_status ) ? $order_status : '' );
				$response_data['time']                  = $time;
				$response_data['duration']              = $wcj_sale_msg_duration;
				$response_data['next_time_display']     = $wcj_sale_msg_next;
				$response_data['thumb']                 = $thumb;
				$response_data['title']                 = $title;
				$response_data['msg']                   = ( isset( $newmsg ) ? $newmsg : '' );
				$response_data['new_order']             = $new_order;
				$response_data['close_img']             = $url;
				$response_data['site_url']              = site_url();
			}

			header( 'Content-type: application/json' );
			$response_data['success'] = 1;
			echo wp_json_encode( $response_data );
			wp_die();
		}

		/**
		 * Show HTML on front end
		 */
		public function product_html() {
			$enable = $this->settings->enable();
			if ( $enable ) {
				$products = $this->get_product();
				if ( is_array( $products ) && count( $products ) ) {
					echo wp_json_encode( $products );
				}
			}

			die;
		}

		/**
		 * Wcj_time_substract.
		 *
		 * @version 1.0.0
		 *
		 * @param string $ordertime defines the order time.
		 * @param int    $ordernumber defines the order id.
		 * @param int    $amountcalculate defines the order amount.
		 */
		public function wcj_time_substract( $ordertime, $ordernumber = false, $amountcalculate = false ) {
			if ( ! $ordernumber ) {
				if ( $ordertime ) {
					$ordertime = strtotime( $ordertime );
				} else {
					return false;
				}
			}

			if ( ! $amountcalculate ) {
				$current_time   = current_time( 'timestamp' ); // phpcs:ignore 
				$time_substract = $current_time - $ordertime;
			} else {
				$time_substract = $ordertime;
			}
			if ( $time_substract > 0 ) {

				/*Check day*/
				$day = $time_substract / ( 24 * 3600 );
				$day = intval( $day );
				if ( $day > 1 ) {
					return $day . ' ' . esc_html__( 'days', 'woocommerce-jetpack' );
				} elseif ( $day > 0 ) {
					return $day . ' ' . esc_html__( 'day', 'woocommerce-jetpack' );
				}

				/*Check hour*/
				$hour = $time_substract / ( 3600 );
				$hour = intval( $hour );
				if ( $hour > 1 ) {
					return $hour . ' ' . esc_html__( 'hours', 'woocommerce-jetpack' );
				} elseif ( $hour > 0 ) {
					return $hour . ' ' . esc_html__( 'hour', 'woocommerce-jetpack' );
				}

				/*Check min*/
				$min = $time_substract / ( 60 );
				$min = intval( $min );
				if ( $min > 1 ) {
					return $min . ' ' . esc_html__( 'minutes', 'woocommerce-jetpack' );
				} elseif ( $min > 0 ) {
					return $min . ' ' . esc_html__( 'minute', 'woocommerce-jetpack' );
				}

				return intval( $time_substract ) . ' ' . esc_html__( 'seconds', 'woocommerce-jetpack' );

			} else {
				return esc_html__( 'a few seconds', 'woocommerce-jetpack' );
			}
		}

		/**
		 * Wcj_sales_notifications_pop.
		 *
		 * @version 1.0.0
		 */
		public function wcj_sales_notifications_pop() {

			$html  = '<div class="wcj_sale_notification" id="wcj_sale_notification">';
			$html .= '<img class="wcj_sale_notification_img" src="">';
			$html .= '<p><span class="wcj_sale_notification_title"></span></br>';
			$html .= '<i class="wcj_sale_notification_close_div"><input type="checkbox" id="wcj_sale_notification_hide" class="wcj_sale_notification_hide" value="1"><small> Do you want to hide this popup?</small></i>';
			$html .= '</p>';
			$html .= '<span id="notify-close" class="wcj_sn_close"> <img class="wcj_sale_notification_close" src=""></span>';
			$html .= '</div>';
			echo $html; // phpcs:ignore
		}

		/**
		 * Wcj_sn_enqueue_scripts.
		 *
		 * @version 1.0.0
		 */
		public function wcj_sn_enqueue_scripts() {
			wp_reset_postdata();
			global $wp;
			$current_url   = $wp->request;
			$post_id       = url_to_postid( $current_url );
			$slug          = get_post_field( 'page', $post_id );
			$front_page_id = get_option( 'page_on_front' );

			$pageid = get_the_ID();
			wp_enqueue_style( 'wcj-sales-notifications-style', wcj_plugin_url() . '/includes/css/wcj-sales-notifications.css', array(), w_c_j()->version );
			if ( true === wcj_is_hpos_enabled() ) {
				wp_enqueue_script( 'wcj-sales-notifications-script-hpos', wcj_plugin_url() . '/includes/js/wcj-sales-notifications-hpos.js', array(), w_c_j()->version . '' . wp_rand(), true );
				$wcj_sales_notifications = 'wcj-sales-notifications-script-hpos';
			} else {
				wp_enqueue_script( 'wcj-sales-notifications-script', wcj_plugin_url() . '/includes/js/wcj-sales-notifications.js', array(), w_c_j()->version . '' . wp_rand(), true );
				$wcj_sales_notifications = 'wcj-sales-notifications-script';
			}
			$styling_options   = wcj_get_option( 'wcj_sale_msg_styling', array() );
			$wcj_sale_msg_ajax = wcj_get_option( 'wcj_sale_msg_ajax', 'yes' );
			$wcj_sale_msg_next = wcj_get_option( 'wcj_sale_msg_next', '5' );

			wp_localize_script(
				$wcj_sales_notifications,
				'wcj_sn_ajax_object',
				array(
					'ajax_url'          => admin_url( 'admin-ajax.php' ),
					'animation'         => $styling_options['animation'],
					'hidden_animation'  => $styling_options['hidden_animation'],
					'pageid'            => $pageid,
					'wcj_is_sn_ajax'    => $wcj_sale_msg_ajax,
					'wcj_sale_msg_next' => $wcj_sale_msg_next,
				)
			);
		}
	}
endif;

return new WCJ_Sales_Notifications();

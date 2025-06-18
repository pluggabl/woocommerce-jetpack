<?php
/**
 * Booster Elite for WooCommerce - Module - Cart Abandonment
 *
 * @version 7.2.7
 * @author  Pluggabl LLC.
 * @package Booster_Elite_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Cart_Abandonment' ) ) :

	/**
	 * WCJ_Cart_Abandonment.
	 *
	 * @version 7.2.1
	 */
	class WCJ_Cart_Abandonment extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 7.2.1
		 */
		public function __construct() {

			$this->id         = 'cart_abandonment';
			$this->short_desc = __( 'Cart Abandonment', 'woocommerce-jetpack' );
			$this->desc       = __( 'Stop Cart Abandonments and Recover Your Lost Amount!', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Stop Cart Abandonments and Recover Your Lost Amount!', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-cart-abandonment';
			parent::__construct();
			$this->add_tools(
				array(
					'cart_abandonment' => array(
						'title' => __( 'Cart Abandonment Report', 'woocommerce-jetpack' ),
						'desc'  => __( 'Cart Abandonment Report.', 'woocommerce-jetpack' ),
					),
				)
			);

			// Create required tables.
			register_activation_hook( WCJ_FREE_PLUGIN_FILE, array( $this, 'wcj_cart_abandonment_required_tables' ) );
			add_action( 'plugins_loaded', array( $this, 'myplugin_update_db_check' ), PHP_INT_MAX );
			add_action( 'upgrader_process_complete', array( $this, 'wcj_ca_upgrader_process_completed' ), 10, 2 );

			if ( $this->is_enabled() ) {

				add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_cart_abandonment_script' ), 20 );

				// delete abandonment data.
				add_action( 'woocommerce_new_order', array( $this, 'wcj_change_abandonment_data_status' ) );
				add_action( 'woocommerce_thankyou', array( $this, 'wcj_change_abandonment_data_status' ) );

				// Restore cart data.
				add_filter( 'wp', array( $this, 'wcj_restore_cart_data' ), 10 );

				// schedules cron.
				add_filter( 'cron_schedules', array( $this, 'wcj_cart_abandonment_update_order_status_action' ) );

				if ( ! wp_next_scheduled( 'wcj_cart_abandonment_update_order_status_action' ) ) {
					wp_schedule_event( time(), 'every_fifteen_minutes', 'wcj_cart_abandonment_update_order_status_action' );
				}

				add_action( 'wcj_cart_abandonment_update_order_status_action', array( $this, 'wcj_cart_abandonment_email_schedules' ) );

				if ( wcj_is_frontend() ) {

					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_wcj_cart_abandonment_scripts' ) );

					// save abandonment data.
					add_action( 'wp_ajax_wcj_save_cart_abandonment_data', array( $this, 'wcj_save_cart_abandonment_data' ) );
					add_action( 'wp_ajax_nopriv_wcj_save_cart_abandonment_data', array( $this, 'wcj_save_cart_abandonment_data' ) );
				}
			}
		}

		/**
		 * Wcj_cart_abandonment_mail_schedule_data.
		 *
		 * @version 7.2.1
		 * @param string $email_trigger_time | optional email trigger time.
		 * @param int    $template_id | optional template id for email.
		 */
		public function wcj_cart_abandonment_mail_schedule_data( $email_trigger_time = '+30 minutes', $template_id = 1 ) {

			global $wpdb;
			$wcj_cart_abandonment_data = $wpdb->prefix . 'wcj_cart_abandonment_data';
			$wcj_cart_email_tbl        = $wpdb->prefix . 'wcj_abandonment_email_history';

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$data          = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$wcj_cart_abandonment_data} WHERE `order_status` != %s AND DATE(`time`) = %s", 'recovered', gmdate( 'Y-m-d' ) ),
				ARRAY_A
			);
			$schedule_data = array();
			if ( $data ) {
				foreach ( $data as $value ) {
					$data2 = $wpdb->get_results(
						$wpdb->prepare( "SELECT * FROM {$wcj_cart_email_tbl} WHERE `session_id` = %s AND `template_id` = %d", $value['session_id'], $template_id ),
						ARRAY_A
					);
					if ( empty( $data2 ) ) {

						$cuurent       = date_i18n( 'Y-m-d H:i:s' );
						$email_trigger = date_i18n( 'Y-m-d H:i:s', strtotime( $email_trigger_time, strtotime( $value['time'] ) ) );

						if ( $cuurent > $email_trigger ) {
							$schedule_data[] = $value;
						}
					}
				}
			}
			// phpcs:enable

			return $schedule_data;
		}

		/**
		 * Load_admin_cart_abandonment_script.
		 *
		 * @version 6.0.0
		 */
		public function load_admin_cart_abandonment_script() {
			// Styles.
			wp_enqueue_style( 'wcj-cart-abandonment-css', wcj_plugin_url() . '/includes/css/wcj-cart-abandonment.css', array(), w_c_j()->version );
		}

		/**
		 * Create_cart_abandonment_tool.
		 *
		 * @version 7.2.1
		 */
		public function create_cart_abandonment_tool() {
			$view_detail = filter_input( INPUT_GET, 'view_detail', FILTER_UNSAFE_RAW );
			if ( $view_detail ) {
				require_once 'cart-abandonment/wcj-cart-abandonment-orders-detail.php';
			} else {
				require_once 'cart-abandonment/wcj-cart-abandonment-orders-report.php';
			}
		}

		/**
		 * Enqueue_wcj_cart_abandonment_scripts.
		 *
		 * @version 7.2.1
		 */
		public function enqueue_wcj_cart_abandonment_scripts() {

			if ( is_checkout() ) {
				$user_roles = wcj_get_option( 'wcj_cart_abandonment_disable_user_role', array() );
				if ( empty( $user_roles ) || ( ! empty( $user_roles ) && ! in_array( wcj_get_current_user_first_role(), $user_roles, true ) ) ) {
					wp_enqueue_script( 'wcj-cart-abandonment-js', wcj_plugin_url() . '/includes/js/wcj-cart-abandonment.js', array( 'jquery' ), w_c_j()->version, true );
					wp_localize_script(
						'wcj-cart-abandonment-js',
						'ajax_object',
						array(
							'ajax_url' => admin_url( 'admin-ajax.php' ),
							'post_id'  => get_the_ID(),
						)
					);
				}
			}
		}

		/**
		 * Wcj_cart_get_report_by_type.
		 *
		 * @version 6.0.1
		 * @param string $from_date optional | from date for filter data by start date.
		 * @param string $to_date optional | to date for filter data by end date.
		 * @param string $type optional | type of the cart.
		 */
		public function wcj_cart_get_report_by_type( $from_date, $to_date, $type = 'normal' ) {
			global $wpdb;
			$wcj_cart_abandonment_data = $wpdb->prefix . 'wcj_cart_abandonment_data';

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$data = $wpdb->get_row(
				$wpdb->prepare( "SELECT  SUM(`cart_total`) as amount, count('*') as no_of_orders  FROM {$wcj_cart_abandonment_data} WHERE `order_status` = %s AND DATE(`time`) >= %s AND DATE(`time`) <= %s  ", $type, $from_date, $to_date ),
				ARRAY_A
			);
			// phpcs:enable
			return $data;
		}

		/**
		 * Wcj_percentage_of_recovered.
		 *
		 * @version 6.0.1
		 * @param string $from_date contains from date for filter record.
		 * @param string $to_date contains to date for filter record.
		 */
		public function wcj_percentage_of_recovered( $from_date, $to_date ) {
			$normal          = $this->wcj_cart_get_report_by_type( $from_date, $to_date, 'normal' );
			$recovered       = $this->wcj_cart_get_report_by_type( $from_date, $to_date, 'recovered' );
			$lost            = $this->wcj_cart_get_report_by_type( $from_date, $to_date, 'lost' );
			$conversion_rate = 0;
			$total           = ( $normal['no_of_orders'] + $recovered['no_of_orders'] + $lost['no_of_orders'] );
			if ( $total ) {
				$conversion_rate = ( $recovered['no_of_orders'] / $total ) * 100;
			}
			$conversion_rate = number_format_i18n( $conversion_rate, 2 );
			return $conversion_rate;
		}

		/**
		 * Wcj_save_cart_abandonment_data.
		 *
		 * @version 6.0.2
		 */
		public function wcj_save_cart_abandonment_data() {
			$wpnonce = isset( $_POST['wpnonce'] ) ? wp_verify_nonce( sanitize_key( $_POST['wpnonce'] ), 'woocommerce-process_checkout' ) : false;
			if ( $wpnonce && isset( $_POST['billing_email'] ) ) {
				global $wpdb;
				$cart_abandonment_table = $wpdb->prefix . 'wcj_cart_abandonment_data';

				$billing_email        = sanitize_email( wp_unslash( $_POST['billing_email'] ) );
				$session_id           = WC()->session->get( 'wcj_ca_session_id' );
				$session_chekout_data = null;

				if ( isset( $session_id ) && ( '' !== $session_id || null !== $session_id ) ) {
					$session_chekout_data = $this->get_checkout_deta( $session_id );
				} else {
					$session_chekout_data = $this->get_checkout_deta_by_email( $billing_email );
					if ( $session_chekout_data ) {
						$session_id = $session_chekout_data->session_id;
						WC()->session->set( 'wcj_ca_session_id', $session_id );
					} else {
						$session_id = md5( uniqid( wp_rand(), true ) );
					}
				}

				$checkout_data = $this->get_checkout_data_for_cart_abandonment( $_POST );

				if ( isset( $session_chekout_data ) && 'completed' === $session_chekout_data->order_status ) {
					WC()->session->__unset( 'wcj_ca_session_id' );
					$session_id = md5( uniqid( wp_rand(), true ) );
				}

				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( isset( $checkout_data['cart_total'] ) && $checkout_data['cart_total'] > 0 ) {

					if ( ( ! is_null( $session_id ) ) && ! is_null( $session_chekout_data ) ) {

						$wpdb->update(
							$cart_abandonment_table,
							$checkout_data,
							array( 'session_id' => $session_id )
						);

					} else {

						$checkout_data['session_id'] = sanitize_text_field( $session_id );
						// Inserting row into Database.
						$wpdb->insert(
							$cart_abandonment_table,
							$checkout_data
						);

						// Storing session_id in WooCommerce session.
						WC()->session->set( 'wcj_ca_session_id', $session_id );

					}
				} else {
					$wpdb->delete( $cart_abandonment_table, array( 'session_id' => sanitize_key( $session_id ) ) );
				}
				// phpcs:enable

				wp_send_json_success();
			}
		}
		/**
		 * Wcj_decode_token.
		 *
		 * @version 6.0.1
		 * @param string $token contains encoded token.
		 */
		public function wcj_decode_token( $token ) {
			$token = sanitize_text_field( $token );
			parse_str( base64_decode( urldecode( $token ) ), $token ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			return $token;
		}
		/**
		 * Is_valid_token.
		 *
		 * @version 6.0.1
		 * @param string $token contains encoded token.
		 */
		public function is_valid_token( $token ) {
			$is_valid   = false;
			$token_data = $this->wcj_decode_token( $token );
			if ( is_array( $token_data ) && array_key_exists( 'wcj_ca_session_id', $token_data ) ) {
				$result = $this->get_checkout_deta( $token_data['wcj_ca_session_id'] );
				if ( isset( $result ) ) {
					$is_valid = true;
				}
			}
			return $is_valid;
		}

		/**
		 * Get_checkout_deta.
		 *
		 * @version 6.0.1
		 * @param string $wcj_ca_session_id contains session id.
		 */
		public function get_checkout_deta( $wcj_ca_session_id ) {
			global $wpdb;
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}wcj_cart_abandonment_data` WHERE session_id = %s", $wcj_ca_session_id )
			);
			// phpcs:enable
			return $result;
		}

		/**
		 * Get_scheduled_email.
		 *
		 * @version 6.0.1
		 * @param string $wcj_ca_session_id contains session id.
		 */
		public function get_scheduled_email( $wcj_ca_session_id ) {
			global $wpdb;
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}wcj_abandonment_email_history` WHERE session_id = %s", $wcj_ca_session_id )
			);
			// phpcs:enable
			return $result;
		}

		/**
		 * Get_checkout_data_for_cart_abandonment.
		 *
		 * @version 6.0.1
		 * @param array $post optional | contains the post data.
		 */
		public function get_checkout_data_for_cart_abandonment( $post = array() ) {

			if ( function_exists( 'WC' ) ) {
				$cart_total    = WC()->cart->total;
				$products      = WC()->cart->get_cart();
				$current_time  = current_time( 'Y-m-d H:i' );
				$checkout_data = array(
					'billing_first_name'  => isset( $post['billing_first_name'] ) ? $post['billing_first_name'] : '',
					'billing_last_name'   => isset( $post['billing_last_name'] ) ? $post['billing_last_name'] : '',
					'billing_phone'       => isset( $post['billing_phone'] ) ? $post['billing_phone'] : '',
					'billing_company'     => isset( $post['billing_company'] ) ? $post['billing_company'] : '',
					'billing_address_1'   => isset( $post['billing_address_1'] ) ? $post['billing_address_1'] : '',
					'billing_address_2'   => isset( $post['billing_address_1'] ) ? $post['billing_address_1'] : '',
					'billing_state'       => isset( $post['billing_state'] ) ? $post['billing_state'] : '',
					'billing_postcode'    => isset( $post['billing_postcode'] ) ? $post['billing_postcode'] : '',
					'billing_country'     => isset( $post['billing_country'] ) ? $post['billing_country'] : '',
					'billing_city'        => isset( $post['billing_city'] ) ? $post['billing_city'] : '',
					'shipping_first_name' => isset( $post['shipping_first_name'] ) ? $post['shipping_first_name'] : '',
					'shipping_last_name'  => isset( $post['shipping_last_name'] ) ? $post['shipping_last_name'] : '',
					'shipping_company'    => isset( $post['shipping_company'] ) ? $post['shipping_company'] : '',
					'shipping_country'    => isset( $post['shipping_country'] ) ? $post['shipping_country'] : '',
					'shipping_address_1'  => isset( $post['shipping_address_1'] ) ? $post['shipping_address_1'] : '',
					'shipping_address_2'  => isset( $post['shipping_address_2'] ) ? $post['shipping_address_2'] : '',
					'shipping_city'       => isset( $post['shipping_city'] ) ? $post['shipping_city'] : '',
					'shipping_state'      => isset( $post['shipping_state'] ) ? $post['shipping_state'] : '',
					'shipping_postcode'   => isset( $post['shipping_postcode'] ) ? $post['shipping_postcode'] : '',
					'order_comments'      => isset( $post['order_comments'] ) ? $post['order_comments'] : '',
				);

				$checkout_details = array(
					'checkout_id'   => $post['post_id'],
					'email'         => $post['billing_email'],
					'cart_data'     => serialize( $products ), //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
					'cart_total'    => sanitize_text_field( $cart_total ),
					'time'          => sanitize_text_field( $current_time ),
					'coupon_code'   => $post['coupon_code'],
					'checkout_data' => serialize( $checkout_data ), //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
				);
			}
			return $checkout_details;
		}

		/**
		 * Get_checkout_deta_by_email.
		 *
		 * @version 6.0.1
		 * @param string $email email for get checkout data.
		 */
		public function get_checkout_deta_by_email( $email ) {
			global $wpdb;
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}wcj_cart_abandonment_data` WHERE email = %s AND `order_status` IN ( %s, %s )", $email, 'abandoned', 'normal' )
			);
			// phpcs:enable
			return $result;
		}


		/**
		 * Wcj_change_abandonment_data_status.
		 *
		 * @version 6.0.1
		 * @param string $order_id optional | order id to change status.
		 */
		public function wcj_change_abandonment_data_status( $order_id = null ) {
			global $wpdb;
			$order = new WC_Order( $order_id );

			if ( isset( WC()->session ) ) {

				$session_id = WC()->session->get( 'wcj_ca_session_id' );

				$cart_abandonment_table = $wpdb->prefix . 'wcj_cart_abandonment_data';
				$scheduled_email        = $this->get_scheduled_email( $session_id );
				$checkout_deta          = $this->get_checkout_deta( $session_id );

				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				if ( empty( $scheduled_email ) ) {
					$wpdb->delete( $cart_abandonment_table, array( 'session_id' => sanitize_key( $session_id ) ) );
				} else {
					if ( $checkout_deta && ( 'normal' === $checkout_deta->order_status || 'lost' === $checkout_deta->order_status ) ) {
						$wpdb->query( "UPDATE $cart_abandonment_table SET order_status='recovered' WHERE session_id='$session_id'" );
					}
				}
				// phpcs:enable
			}
		}

		/**
		 * Wcj_cart_abandonment_update_order_status_action.
		 *
		 * @version 6.0.1
		 * @param array $schedules contains cron schedules.
		 */
		public function wcj_cart_abandonment_update_order_status_action( $schedules ) {

			$cron_time = apply_filters( 'wcj_cart_abandonment_update_order_cron_interval', 10 );

			$schedules['every_fifteen_minutes'] = array(
				'interval' => 15 * 60,
				'display'  => __( 'Every Fifteen Minutes', 'woocommerce-jetpack' ),
			);

			return $schedules;
		}

		/**
		 * Wcj_generate_new_coupon_code.
		 *
		 * @version 6.0.1
		 * @param string $discount_type coupon discont.
		 * @param string $amount | optional coupon amount.
		 * @param string $expiry | optional coupon expiry.
		 * @param string $free_shipping | optional indicates flag for free shipping.
		 * @param string $individual_use | optional indicates the coupon is for individual use or not.
		 */
		public function wcj_generate_new_coupon_code( $discount_type, $amount, $expiry = '', $free_shipping = 'no', $individual_use = 'no' ) {

			$coupon_code = '';

			$coupon_code = 'ca-' . wp_generate_password( 8, false, false );

			$new_coupon_id = wp_insert_post(
				array(
					'post_title'   => $coupon_code,
					'post_content' => '',
					'post_status'  => 'publish',
					'post_author'  => 1,
					'post_type'    => 'shop_coupon',
				)
			);

				$coupon_post_data = array(
					'discount_type'       => $discount_type,
					'description'         => 'This coupon is for cart abandonment module booster',
					'coupon_amount'       => $amount,
					'individual_use'      => $individual_use,
					'product_ids'         => '',
					'exclude_product_ids' => '',
					'usage_limit'         => '1',
					'usage_count'         => '0',
					'date_expires'        => $expiry,
					'apply_before_tax'    => 'yes',
					'free_shipping'       => $free_shipping,
					'coupon_generated_by' => 'woocommerce-jetpack',
				);

				$coupon_post_data = apply_filters( 'woo_ca_generate_coupon', $coupon_post_data );

				foreach ( $coupon_post_data as $key => $value ) {
					update_post_meta( $new_coupon_id, $key, $value );
				}

				return $coupon_code;
		}

		/**
		 * Wcj_ca_upgrader_process_completed.
		 *
		 * @version 6.0.1
		 * @param string $upgrader_object indicates the coupon is for individual use or not.
		 * @param string $options indicates the coupon is for individual use or not.
		 */
		public function wcj_ca_upgrader_process_completed( $upgrader_object, $options ) {
			$our_plugin = plugin_basename( WCJ_FREE_PLUGIN_FILE );
			if ( 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
				foreach ( $options['plugins'] as $plugin ) {
					if ( $plugin === $our_plugin ) {
						$this->wcj_cart_abandonment_required_tables();
					}
				}
			}
		}

		/**
		 * Wcj_cart_abandonment_email_schedules.
		 *
		 * @version 6.0.1
		 */
		public function wcj_cart_abandonment_email_schedules() {
			require_once 'cart-abandonment/wcj_cart_abandonment_email_schedules.php';
		}

		/**
		 * Wcj_restore_cart_data.
		 *
		 * @version 7.2.1
		 * @param string $fields | optional indicates cart fields.
		 */
		public function wcj_restore_cart_data( $fields = array() ) {
			global $woocommerce;
			$result = array();

			$wcj_restore_ac_token = filter_input( INPUT_GET, 'wcj_restore_ac_token', FILTER_UNSAFE_RAW );
			if ( $this->is_valid_token( $wcj_restore_ac_token ) ) {

				$token_data = $this->wcj_decode_token( $wcj_restore_ac_token );
				if ( is_array( $token_data ) && isset( $token_data['wcj_ca_session_id'] ) ) {
					$result = $this->get_checkout_deta( $token_data['wcj_ca_session_id'] );
					if ( isset( $result ) && 'normal' === $result->order_status || 'lost' === $result->order_status ) {
						WC()->session->set( 'wcj_ca_session_id', $token_data['wcj_ca_session_id'] );
					}
				}

				if ( $result ) {
					$cart_content = unserialize( $result->cart_data ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

					if ( $cart_content ) {
						$woocommerce->cart->empty_cart();
						wc_clear_notices();
						foreach ( $cart_content as $cart_item ) {

							$cart_item_data = array();
							$variation_data = array();
							$id             = $cart_item['product_id'];
							$qty            = $cart_item['quantity'];

							// Skip bundled products when added main product.
							if ( isset( $cart_item['bundled_by'] ) ) {
								continue;
							}

							if ( isset( $cart_item['variation'] ) ) {
								foreach ( $cart_item['variation']  as $key => $value ) {
									$variation_data[ $key ] = $value;
								}
							}

							$cart_item_data = $cart_item;

							$woocommerce->cart->add_to_cart( $id, $qty, $cart_item['variation_id'], $variation_data, $cart_item_data );
						}

						if ( ! empty( $result->coupon_code ) && ! $woocommerce->cart->applied_coupons ) {
							$woocommerce->cart->add_discount( $result->coupon_code );
						}
					}
					$user_details                = unserialize( $result->checkout_data ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
					$_POST['billing_first_name'] = sanitize_text_field( $user_details['billing_first_name'] );
					$_POST['billing_last_name']  = sanitize_text_field( $user_details['billing_last_name'] );
					$_POST['billing_phone']      = sanitize_text_field( $user_details['billing_phone'] );
					$_POST['billing_email']      = sanitize_email( $result->email );
					$_POST['billing_city']       = sanitize_text_field( $user_details['billing_city'] );
					$_POST['billing_state']      = sanitize_text_field( $user_details['billing_state'] );
					$_POST['billing_country']    = sanitize_text_field( $user_details['billing_country'] );
					$_POST['billing_address_1']  = sanitize_text_field( $user_details['billing_address_1'] );
					$_POST['billing_address_2']  = sanitize_text_field( $user_details['billing_address_2'] );
					$_POST['billing_postcode']   = sanitize_text_field( $user_details['billing_postcode'] );
				}
			}
			return $fields;
		}

		/**
		 * Wcj_cart_abandonment_required_tables.
		 *
		 * @version 6.0.1
		 */
		public function wcj_cart_abandonment_required_tables() {

			global $wpdb;
			$table_wcj_cart_abandonment_data = $wpdb->prefix . 'wcj_cart_abandonment_data';
			$table_wcj_email_history         = $wpdb->prefix . 'wcj_abandonment_email_history';

			$charset_collate = $wpdb->get_charset_collate();
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
			if ( $wpdb->get_var( "show tables like '$table_wcj_cart_abandonment_data'" ) !== $table_wcj_cart_abandonment_data ) {
				$sql = "CREATE TABLE $table_wcj_cart_abandonment_data (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				checkout_id int(11) NOT NULL, 
				session_id VARCHAR(60) NOT NULL,
				email VARCHAR(100) DEFAULT NULL,
				cart_data TEXT DEFAULT NULL,
				cart_total DECIMAL(10,2) DEFAULT NULL,
				checkout_data TEXT DEFAULT NULL,
				order_status VARCHAR(20) NOT NULL DEFAULT 'normal',
				is_subscribe  tinyint(1) DEFAULT 1,
				coupon_code VARCHAR(50) DEFAULT NULL,
	   			time DATETIME DEFAULT NULL,
				PRIMARY KEY  (`id`)
			) $charset_collate;";

				dbDelta( $sql );
				$wcj_db_version = '1.0.0';
				add_option( 'test_db_version', $wcj_db_version );
			}

			if ( $wpdb->get_var( "show tables like '$table_wcj_email_history'" ) !== $table_wcj_email_history ) {
				$sql = "CREATE TABLE $table_wcj_email_history (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				session_id VARCHAR(60) NOT NULL,
				email VARCHAR(100) DEFAULT NULL,
				template_id BIGINT(20) DEFAULT NULL,
				coupon_code VARCHAR(100) DEFAULT NULL,				
				status  tinyint(1) DEFAULT 1,
	   			time DATETIME DEFAULT NULL,
				PRIMARY KEY  (`id`)
			) $charset_collate;";

				dbDelta( $sql );
				$wcj_db_version = '1.0.0';
				add_option( 'test_db_version', $wcj_db_version );
			}
			// phpcs:enable
		}


		/**
		 * Myplugin_update_db_check.
		 *
		 * @version 6.0.2
		 */
		public function myplugin_update_db_check() {
			global $wcj_db_version;
			if ( get_site_option( 'test_db_version' ) !== $wcj_db_version ) {
				$this->wcj_cart_abandonment_required_tables();
			}
		}
	}

endif;

return new WCJ_Cart_Abandonment();

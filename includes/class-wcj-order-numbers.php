<?php
/**
 * Booster for WooCommerce - Module - Order Numbers
 *
 * @version 7.1.9
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! class_exists( 'WCJ_Order_Numbers' ) ) :
	/**
	 * WCJ_Order_Numbers.
	 */
	class WCJ_Order_Numbers extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 7.1.9
		 * @todo    (maybe) rename "Orders Renumerate" to "Renumerate orders"
		 * @todo    (maybe) use `woocommerce_new_order` hook instead of `wp_insert_post`
		 */
		public function __construct() {

			$this->id         = 'order_numbers';
			$this->short_desc = __( 'Order Numbers', 'woocommerce-jetpack' );
			$this->desc       = __( 'Sequential order numbering, custom order number prefix, suffix and number width. Prefix Options (Order Number Custom Prefix available in free version). Suffix options (Plus). ', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Sequential order numbering, custom order number prefix, suffix and number width.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-custom-order-numbers';
			parent::__construct();

			$this->add_tools(
				array(
					'renumerate_orders' => array(
						'title' => __( 'Orders Renumerate', 'woocommerce-jetpack' ),
						'desc'  => __( 'Tool renumerates all orders.', 'woocommerce-jetpack' ),
					),
				)
			);

			if ( $this->is_enabled() ) {
				// Add & display custom order number.
				if ( true === wcj_is_hpos_enabled() ) {
					add_action( 'woocommerce_new_order', array( $this, 'add_new_order_number' ), PHP_INT_MAX );
				} else {
					add_action( 'wp_insert_post', array( $this, 'add_new_order_number' ), PHP_INT_MAX );
				}
				add_filter( 'woocommerce_order_number', array( $this, 'display_order_number' ), PHP_INT_MAX, 2 );
				// Order tracking.
				if ( 'yes' === wcj_get_option( 'wcj_order_number_order_tracking_enabled', 'yes' ) ) {
					add_filter( 'woocommerce_shortcode_order_tracking_order_id', array( $this, 'add_order_number_to_tracking' ), PHP_INT_MAX );
					add_action( 'init', array( $this, 'remove_order_tracking_sanitize_order_id_filter' ) );
				}
				// Search by custom number.
				if ( 'yes' === wcj_get_option( 'wcj_order_number_search_by_custom_number_enabled', 'yes' ) ) {
					if ( true === wcj_is_hpos_enabled() ) {
						add_action( 'woocommerce_shop_order_search_fields', array( $this, 'search_by_custom_number_hpos' ) );
						add_filter( 'woocommerce_order_table_search_query_meta_keys', array( $this, 'search_by_custom_number_hpos' ) );
					} else {
						add_action( 'pre_get_posts', array( $this, 'search_by_custom_number' ) );
					}
				}
				// "WooCommerce Subscriptions" plugin.
				$woocommerce_subscriptions_types = array( 'subscription', 'renewal_order', 'resubscribe_order', 'copy_order' );
				foreach ( $woocommerce_subscriptions_types as $woocommerce_subscriptions_type ) {
					add_filter( 'wcs_' . $woocommerce_subscriptions_type . '_meta', array( $this, 'woocommerce_subscriptions_remove_meta_copy' ), PHP_INT_MAX, 3 );
				}
				// Editable order number.
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_order_number_editable_order_number_meta_box_enabled', 'no' ) ) ) {
					if ( true === wcj_is_hpos_enabled() ) {
						$this->meta_box_screen   = 'woocommerce_page_wc-orders';
						$this->meta_box_context  = 'side';
						$this->meta_box_priority = 'high';
						add_action( 'add_meta_boxes', array( $this, 'maybe_add_meta_box' ), PHP_INT_MAX, 2 );
						add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_meta_box_hpos' ), PHP_INT_MAX, 2 );
					} else {
						$this->meta_box_screen   = 'shop_order';
						$this->meta_box_context  = 'side';
						$this->meta_box_priority = 'high';
						add_action( 'add_meta_boxes', array( $this, 'maybe_add_meta_box' ), PHP_INT_MAX, 2 );
						add_action( 'save_post_shop_order', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
					}
				}

				// Compatibility with WPNotif plugin.
				add_action(
					'admin_init',
					function () {
						if ( 'yes' === wcj_get_option( 'wcj_order_numbers_compatibility_wpnotif', 'no' ) ) {
							remove_filter( 'wpnotif_filter_message', 'wpnotif_add_tracking_details', 10, 2 );
							add_filter( 'wpnotif_filter_message', array( $this, 'wpnotif_add_tracking_details' ), 10, 2 );
						}
					}
				);
			}
		}

		/**
		 * Wpnotif_add_tracking_details.
		 *
		 * @see wpnotif_add_tracking_details() from WPNotif/inclues/filters.php
		 *
		 * @version 5.1.0
		 * @since   5.1.0
		 *
		 * @param string         $msg defines the msg.
		 * @param string | array $order defines the order.
		 *
		 * @return mixed
		 */
		public function wpnotif_add_tracking_details( $msg, $order ) {
			$tracking_link_string = '';
			if ( class_exists( 'YITH_Tracking_Data' ) ) {
				$values            = array();
				$yith_placeholders = apply_filters( 'ywsn_sms_placeholders', array(), $order );
				if ( isset( $yith_placeholders['{tracking_url}'] ) ) {
					$tracking_link_string = $yith_placeholders['{tracking_url}'];
				}
			} else {
				$tracking_links = $this->wpnotif_get_wc_tracking_links( $order );
				if ( ! empty( $tracking_links ) ) {
					$tracking_link_string = implode( ',', $tracking_links );
				}
			}
			$msg = str_replace( '{{wc-tracking-link}}', $tracking_link_string, $msg );
			return $msg;
		}

		/**
		 * Wpnotif_get_wc_tracking_links.
		 *
		 * @see wpnotif_get_wc_tracking_links() from WPNotif/inclues/filters.php
		 *
		 * @version 5.1.0
		 * @since   5.1.0
		 *
		 * @param string | array $order defines the order.
		 *
		 * @return array
		 */
		public function wpnotif_get_wc_tracking_links( $order ) {
			if ( class_exists( 'WC_Shipment_Tracking_Actions' ) ) {
				$st = WC_Shipment_Tracking_Actions::get_instance();
			} elseif ( is_plugin_active( 'woo-advanced-shipment-tracking/woocommerce-advanced-shipment-tracking.php' ) ) {
				$st = WC_Advanced_Shipment_Tracking_Actions::get_instance();
			} else {
				return array();
			}
			$order_id       = $order->get_id();
			$tracking_items = $st->get_tracking_items( $order_id );
			if ( ! empty( $tracking_items ) ) {
				$tracking_links = array();
				foreach ( $tracking_items as $tracking_item ) {
					$formated_item    = $st->get_formatted_tracking_item( $order_id, $tracking_item );
					$tracking_links[] = htmlspecialchars_decode( $formated_item['formatted_tracking_link'] );
					break;
				}
			}
			return $tracking_links;
		}

		/**
		 * Maybe_add_meta_box.
		 *
		 * @version 7.1.4
		 * @since   3.5.0
		 * @todo    re-think if setting number for yet not-numbered order should be allowed (i.e. do not check for `( '' !== get_post_meta( $post->ID, '_wcj_order_number', true ) )`)
		 * @param string         $post_type defines the post_type.
		 * @param string | array $post defines the post.
		 */
		public function maybe_add_meta_box( $post_type, $post ) {
			$order            = wcj_get_order( $post->ID );
			$wcj_order_number = ( isset( $order ) && false !== $order ? $order->get_meta( '_wcj_order_number' ) : '' );
			if ( true === wcj_is_hpos_enabled() && $wcj_order_number ) {
				parent::add_meta_box();
			} else {
				if ( '' !== get_post_meta( $post->ID, '_wcj_order_number', true ) ) {
					parent::add_meta_box();
				}
			}
		}

		/**
		 * Woocommerce_subscriptions_remove_meta_copy.
		 *
		 * @version 3.2.3
		 * @since   3.2.3
		 * @param array  $meta defines the meta.
		 * @param string $to_order defines the to_order.
		 * @param string $from_order defines the from_order.
		 */
		public function woocommerce_subscriptions_remove_meta_copy( $meta, $to_order, $from_order ) {
			foreach ( $meta as $meta_id => $meta_item ) {
				if ( '_wcj_order_number' === $meta_item['meta_key'] ) {
					unset( $meta[ $meta_id ] );
				}
			}
			return $meta;
		}

		/**
		 * Remove_order_tracking_sanitize_order_id_filter.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 */
		public function remove_order_tracking_sanitize_order_id_filter() {
			remove_filter( 'woocommerce_shortcode_order_tracking_order_id', 'wc_sanitize_order_id' );
		}

		/**
		 * Search_by_custom_number.
		 *
		 * @version 7.1.6
		 * @since   2.6.0
		 * @todo    `_wcj_order_number` is used for `sequential` and `hash` only
		 * @param array | string $query defines the query.
		 */
		public function search_by_custom_number( $query ) {
			if (
			! is_admin() ||
			! property_exists( $query, 'query' ) ||
			! isset( $query->query['s'] ) ||
			empty( trim( $query->query['s'] ) ) ||
			'shop_order' !== $query->query['post_type']
			) {
				return;
			}
			$search = trim( $query->query['s'] );
			remove_action( 'pre_get_posts', array( $this, 'search_by_custom_number' ) );

			// Get prefix and suffix options.
			$prefix  = do_shortcode( wcj_get_option( 'wcj_order_number_prefix', '' ) );
			$prefix .= date_i18n( wcj_get_option( 'wcj_order_number_date_prefix', '' ) );
			$suffix  = do_shortcode( wcj_get_option( 'wcj_order_number_suffix', '' ) );
			$suffix .= date_i18n( wcj_get_option( 'wcj_order_number_date_suffix', '' ) );

			// Ignore suffix and prefix from search input.
			$search_no_suffix            = preg_replace( "/\A{$prefix}/i", '', $search );
			$search_no_suffix_and_prefix = preg_replace( "/{$suffix}\z/i", '', $search_no_suffix );
			$final_search                = empty( $search_no_suffix_and_prefix ) ? $search : $search_no_suffix_and_prefix;

			if ( $search === $final_search ) {
				$final_search = ltrim( $final_search, 0 );
			}
			// Post Status.
			$get_data = array();
			parse_str( isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '', $get_data );
			$post_status = isset( $get_data['post_status'] ) ? sanitize_text_field( wp_unslash( $get_data['post_status'] ) ) : 'any';

			// Try to search post by '_wcj_order_number' meta key.
			$meta_query_args = array(
				array(
					'key'     => '_wcj_order_number',
					'value'   => $final_search,
					'compare' => '=',
				),
			);
			$search_query    = new WP_Query(
				array(
					'fields'                 => 'ids',
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'post_status'            => $post_status,
					'post_type'              => 'shop_order',
					'meta_query'             => $meta_query_args, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				)
			);

			// If found, create the query by meta_key.
			if ( 0 !== $search_query->found_posts ) {
				$query->set( 'post_status', $post_status );
				$query->set( 's', '' );
				$query->set( 'post__in', array() );
				$current_meta_query = empty( $query->get( 'meta_query' ) ) ? array() : $query->get( 'meta_query' );
				$query->set( 'meta_query', array_merge( $current_meta_query, $meta_query_args ) );
				// If not found search by post_id.
			} else {
				$query->set( 'post_status', $post_status );
				$query->set( 's', '' );
				$current_post_in = empty( $query->get( 'post__in' ) ) ? array() : $query->get( 'post__in' );
				$query->set( 'post__in', array_merge( $current_post_in, array( $final_search ) ) );
			}
		}


		/**
		 * Function search_by_custom_number.
		 *
		 * @param array $metakeys Array of the metakeys to search order numbers on shop order page.
		 * @version 7.1.9
		 * @since   7.1.9
		 */
		public function search_by_custom_number_hpos( $metakeys ) {
			$metakeys[] = '_wcj_order_number';

			return $metakeys;
		}

		/**
		 * Add_order_number_to_tracking.
		 *
		 * @version 3.1.0
		 * @since   2.5.2
		 * @param int $order_number defines the order_number.
		 */
		public function add_order_number_to_tracking( $order_number ) {
			$offset     = 0;
			$block_size = 512;
			while ( true ) {
				$args = array(
					'post_type'      => 'shop_order',
					'post_status'    => 'any',
					'posts_per_page' => $block_size,
					'orderby'        => 'ID',
					'order'          => 'DESC',
					'offset'         => $offset,
					'fields'         => 'ids',
				);
				$loop = new WP_Query( $args );
				if ( ! $loop->have_posts() ) {
					break;
				}
				foreach ( $loop->posts as $_order_id ) {
					$_order        = wc_get_order( $_order_id );
					$_order_number = $this->display_order_number( $_order_id, $_order );
					if ( $_order_number === $order_number ) {
						return $_order_id;
					}
				}
				$offset += $block_size;
			}
			return $order_number;
		}

		/**
		 * Display order number.
		 *
		 * @version 7.1.4
		 * @param int            $order_number defines the order_number.
		 * @param string | array $order defines the order.
		 */
		public function display_order_number( $order_number, $order ) {
			$order_id = wcj_get_order_id( $order );
			if ( true === wcj_is_hpos_enabled() ) {
				$order_number_meta = $order->get_meta( '_wcj_order_number' );
			} else {
				$order_number_meta = get_post_meta( $order_id, '_wcj_order_number', true );
			}
			if ( '' === $order_number_meta || 'no' === wcj_get_option( 'wcj_order_number_sequential_enabled', 'yes' ) ) {
				$order_number_meta = $order_id;
			}
			$order_timestamp = strtotime( ( WCJ_IS_WC_VERSION_BELOW_3 ? $order->post->post_date : $order->get_date_created() ) );
			$order_number    = apply_filters(
				'booster_option',
				sprintf( '%s%s', do_shortcode( wcj_get_option( 'wcj_order_number_prefix', '' ) ), $order_number_meta ),
				sprintf(
					'%s%s%0' . wcj_get_option( 'wcj_order_number_min_width', 0 ) . 's%s%s',
					do_shortcode( wcj_get_option( 'wcj_order_number_prefix', '' ) ),
					date_i18n( wcj_get_option( 'wcj_order_number_date_prefix', '' ), $order_timestamp ),
					$order_number_meta,
					do_shortcode( wcj_get_option( 'wcj_order_number_suffix', '' ) ),
					date_i18n( wcj_get_option( 'wcj_order_number_date_suffix', '' ), $order_timestamp )
				)
			);
			if ( false !== strpos( $order_number, '%order_items_skus%' ) ) {
				$order_number = str_replace( '%order_items_skus%', do_shortcode( '[wcj_order_items order_id="' . $order_id . '" field="_sku" sep="-"]' ), $order_number );
			}
			return $order_number;
		}

		/**
		 * Add Renumerate Orders tool to WooCommerce menu (the content).
		 *
		 * @version 5.6.8
		 * @todo    restyle
		 * @todo    add more result info (e.g. number of regenerated orders etc.)
		 */
		public function create_renumerate_orders_tool() {
			$result_message = '';
			$wpnonce        = isset( $_REQUEST['renumerate-orders-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['renumerate-orders-nonce'] ), 'renumerate_orders' ) : false;
			if ( $wpnonce && isset( $_POST['renumerate_orders'] ) ) {
				$this->renumerate_orders();
				$result_message = '<p><div class="updated"><p><strong>' . __( 'Orders successfully renumerated!', 'woocommerce-jetpack' ) . '</strong></p></div></p>';
			} else {
				if ( 'yes' === wcj_get_option( 'wcj_order_number_sequential_enabled', 'yes' ) ) {
					$result_message .= '<p>' . sprintf(
						/* translators: %s: translation added */
						__( 'Sequential number generation is enabled. Next order number will be %s.', 'woocommerce-jetpack' ),
						'<code>' . wcj_get_option( 'wcj_order_number_counter', 1 ) . '</code>'
					) . '</p>';
				}
			}
			$html  = '';
			$html .= '<div class="wcj-setting-jetpack-body wcj_tools_cnt_main">';
			$html .= '<div class="wrap">';
			$html .= $this->get_tool_header_html( 'renumerate_orders' );
			$html .= '<p>';
			$html .= sprintf(
				/* translators: %s: translation added */
				__(
					'Press the button below to renumerate all existing orders starting from order counter settings in <a href="%s">Order Numbers</a> module.',
					'woocommerce-jetpack'
				),
				admin_url( wcj_admin_tab_url() . '&wcj-cat=shipping_and_orders&section=order_numbers' )
			);
			$html .= '</p>';
			$html .= $result_message;
			$html .= '<form method="post" action="">';
			$html .= '<input class="button-primary" type="submit" name="renumerate_orders" value="' . __( 'Renumerate orders', 'woocommerce-jetpack' ) . '">';
			$html .= wp_nonce_field( 'renumerate_orders', 'renumerate-orders-nonce' );
			$html .= '</form>';
			$html .= '</div>';
			$html .= '</div>';
			echo wp_kses_post( $html );
		}

		/**
		 * Add_new_order_number.
		 *
		 * @param int $order_id defines the order_id.
		 */
		public function add_new_order_number( $order_id ) {
			if ( true === wcj_is_hpos_enabled() ) {
				$this->add_order_number_meta_hpos( $order_id, false );
			} else {
				$this->add_order_number_meta( $order_id, false );
			}
		}

		/**
		 * Maybe_reset_sequential_counter.
		 *
		 * @version 3.3.0
		 * @since   3.3.0
		 * @todo    use transactions on `wcj_order_number_use_mysql_transaction_enabled`
		 * @param int $current_order_number defines the current_order_number.
		 * @param int $order_id defines the order_id.
		 */
		public function maybe_reset_sequential_counter( $current_order_number, $order_id ) {
			$reset_period = wcj_get_option( 'wcj_order_number_counter_reset_enabled', 'no' );
			if ( 'no' !== ( $reset_period ) ) {
				$previous_order_date = wcj_get_option( 'wcj_order_number_counter_previous_order_date', 0 );
				$current_order_date  = strtotime( wcj_get_order_date( wc_get_order( $order_id ) ) );
				update_option( 'wcj_order_number_counter_previous_order_date', $current_order_date );
				if ( 0 !== $previous_order_date ) {
					$do_reset = false;
					switch ( $reset_period ) {
						case 'daily':
							$do_reset = (
							gmdate( 'Y', $current_order_date ) !== gmdate( 'Y', $previous_order_date ) ||
							gmdate( 'm', $current_order_date ) !== gmdate( 'm', $previous_order_date ) ||
							gmdate( 'd', $current_order_date ) !== gmdate( 'd', $previous_order_date )
							);
							break;
						case 'monthly':
							$do_reset = (
							gmdate( 'Y', $current_order_date ) !== gmdate( 'Y', $previous_order_date ) ||
							gmdate( 'm', $current_order_date ) !== gmdate( 'm', $previous_order_date )
							);
							break;
						case 'yearly':
							$do_reset = (
							gmdate( 'Y', $current_order_date ) !== gmdate( 'Y', $previous_order_date )
							);
							break;
					}
					if ( $do_reset ) {
						return 1;
					}
				}
			}
			return $current_order_number;
		}

		/**
		 * Add/update order_number meta to order.
		 *
		 * @version 5.6.2
		 * @todo    (maybe) save order ID instead of `$current_order_number = ''` (if `'no' === wcj_get_option( 'wcj_order_number_sequential_enabled', 'yes' )`)
		 * @param int  $order_id defines the order_id.
		 * @param bool $do_overwrite defines the do_overwrite.
		 */
		public function add_order_number_meta( $order_id, $do_overwrite ) {
			if ( 'shop_order' !== get_post_type( $order_id ) || 'auto-draft' === get_post_status( $order_id ) ) {
				return;
			}

			if ( true === $do_overwrite || 0 === get_post_meta( $order_id, '_wcj_order_number', true ) || '' === get_post_meta( $order_id, '_wcj_order_number', true ) ) {
				if ( $order_id < wcj_get_option( 'wcj_order_numbers_min_order_id', 0 ) ) {
					return;
				}
				if ( 'yes' === wcj_get_option( 'wcj_order_number_sequential_enabled', 'yes' ) && 'yes' === wcj_get_option( 'wcj_order_number_use_mysql_transaction_enabled', 'yes' ) ) {
					global $wpdb;
					// phpcs:disable
					$wpdb->query( 'START TRANSACTION' );
					$wp_options_table = $wpdb->prefix . 'options';
					$result_select    = $wpdb->get_row( "SELECT * FROM $wp_options_table WHERE option_name = 'wcj_order_number_counter'" );
					if ( null !== $result_select ) {
						$current_order_number = $this->maybe_reset_sequential_counter( $result_select->option_value, $order_id );
						$result_update        = $wpdb->update( $wp_options_table, array( 'option_value' => ( $current_order_number + 1 ) ), array( 'option_name' => 'wcj_order_number_counter' ) );
						if ( null !== $result_update || ( $current_order_number + 1 ) === $result_select->option_value ) {
							$wpdb->query( 'COMMIT' ); // all ok.
							update_post_meta( $order_id, '_wcj_order_number', apply_filters( 'wcj_order_number_meta', $current_order_number, $order_id ) );
						} else {
							$wpdb->query( 'ROLLBACK' ); // something went wrong, Rollback.
						}
					} else {
						$wpdb->query( 'ROLLBACK' ); // something went wrong, Rollback.
					}
					// phpcs:enable
				} else {
					if ( 'hash_crc32' === wcj_get_option( 'wcj_order_number_sequential_enabled', 'yes' ) ) {
						$current_order_number = sprintf( '%u', crc32( $order_id ) );
					} elseif ( 'yes' === wcj_get_option( 'wcj_order_number_sequential_enabled', 'yes' ) ) {
						$current_order_number = $this->maybe_reset_sequential_counter( wcj_get_option( 'wcj_order_number_counter', 1 ), $order_id );
						update_option( 'wcj_order_number_counter', ( $current_order_number + 1 ) );
					} else { // 'no' === wcj_get_option( 'wcj_order_number_sequential_enabled', 'yes' ) // order ID.
						$current_order_number = '';
					}
					update_post_meta( $order_id, '_wcj_order_number', apply_filters( 'wcj_order_number_meta', $current_order_number, $order_id ) );
				}
			}
		}


		/**
		 * Add/update order_number meta to order HPOS.
		 *
		 * @version 7.1.9
		 * @todo    (maybe) save order ID instead of `$current_order_number = ''` (if `'no' === wcj_get_option( 'wcj_order_number_sequential_enabled', 'yes' )`)
		 * @param int  $order_id defines the order_id.
		 * @param bool $do_overwrite defines the do_overwrite.
		 */
		public function add_order_number_meta_hpos( $order_id, $do_overwrite ) {
			$order = wcj_get_order( $order_id );

			if ( $order && false !== $order ) {

				if ( 'shop_order' !== OrderUtil::get_order_type( $order_id ) || 'auto-draft' === $order->get_status() ) {
					return;
				}

				if ( true === $do_overwrite || 0 === $order->get_meta( '_wcj_order_number' ) || '' === $order->get_meta( '_wcj_order_number' ) ) {

					if ( $order_id < wcj_get_option( 'wcj_order_numbers_min_order_id', 0 ) ) {
						return;
					}
					if ( 'yes' === wcj_get_option( 'wcj_order_number_sequential_enabled', 'yes' ) && 'yes' === wcj_get_option( 'wcj_order_number_use_mysql_transaction_enabled', 'yes' ) ) {
						global $wpdb;
						// phpcs:disable
						$wpdb->query( 'START TRANSACTION' );
						$wp_options_table = $wpdb->prefix . 'options';
						$result_select    = $wpdb->get_row( "SELECT * FROM $wp_options_table WHERE option_name = 'wcj_order_number_counter'" );
						if ( null !== $result_select ) {
							$current_order_number = $this->maybe_reset_sequential_counter( $result_select->option_value, $order_id );
							$result_update        = $wpdb->update( $wp_options_table, array( 'option_value' => ( $current_order_number + 1 ) ), array( 'option_name' => 'wcj_order_number_counter' ) );
							if ( null !== $result_update || ( $current_order_number + 1 ) === $result_select->option_value ) {
								$wpdb->query( 'COMMIT' ); // all ok.
								$order->update_meta_data('_wcj_order_number', apply_filters( 'wcj_order_number_meta', $current_order_number, $order_id ) );
							} else {
								$wpdb->query( 'ROLLBACK' ); // something went wrong, Rollback.
							}
						} else {
							$wpdb->query( 'ROLLBACK' ); // something went wrong, Rollback.
						}
						// phpcs:enable
					} else {
						if ( 'hash_crc32' === wcj_get_option( 'wcj_order_number_sequential_enabled', 'yes' ) ) {
							$current_order_number = sprintf( '%u', crc32( $order_id ) );
						} elseif ( 'yes' === wcj_get_option( 'wcj_order_number_sequential_enabled', 'yes' ) ) {
							$current_order_number = $this->maybe_reset_sequential_counter( wcj_get_option( 'wcj_order_number_counter', 1 ), $order_id );
							update_option( 'wcj_order_number_counter', ( $current_order_number + 1 ) );
						} else { // 'no' === wcj_get_option( 'wcj_order_number_sequential_enabled', 'yes' ) // order ID.
							$current_order_number = '';
						}
						$order->update_meta_data( '_wcj_order_number', apply_filters( 'wcj_order_number_meta', $current_order_number, $order_id ) );
					}
					$order->save();
				}
			}
		}


		/**
		 * Renumerate orders function.
		 *
		 * @version 7.1.4
		 * @todo    renumerate in date range only
		 * @todo    (maybe) selectable `post_status`
		 * @todo    (maybe) set default value for `wcj_order_numbers_renumerate_tool_orderby` to `ID` (instead of `date`)
		 */
		public function renumerate_orders() {
			if ( 'yes' === wcj_get_option( 'wcj_order_number_sequential_enabled', 'yes' ) && 'no' !== wcj_get_option( 'wcj_order_number_counter_reset_enabled', 'no' ) ) {
				update_option( 'wcj_order_number_counter_previous_order_date', 0 );
			}
			$offset     = 0;
			$block_size = 512;
			while ( true ) {

				if ( true === wcj_is_hpos_enabled() ) {

					$args  = array(
						'type'           => 'shop_order',
						'status'         => 'any',
						'posts_per_page' => $block_size,
						'orderby'        => wcj_get_option( 'wcj_order_numbers_renumerate_tool_orderby', 'date' ),
						'order'          => wcj_get_option( 'wcj_order_numbers_renumerate_tool_order', 'ASC' ),
						'offset'         => $offset,
						'fields'         => 'ids',
					);
					$order = wc_get_orders( $args );
					if ( ! $order ) {
						break;
					}
					$i = 0;
					foreach ( $order as $order_id ) {

						$this->add_order_number_meta_hpos( $order[ $i ]->id, true );
						$i++;
					}
				} else {

					$args = array(
						'post_type'      => 'shop_order',
						'post_status'    => 'any',
						'posts_per_page' => $block_size,
						'orderby'        => wcj_get_option( 'wcj_order_numbers_renumerate_tool_orderby', 'date' ),
						'order'          => wcj_get_option( 'wcj_order_numbers_renumerate_tool_order', 'ASC' ),
						'offset'         => $offset,
						'fields'         => 'ids',
					);
					$loop = new WP_Query( $args );
					if ( ! $loop->have_posts() ) {
						break;
					}
					foreach ( $loop->posts as $order_id ) {
						$this->add_order_number_meta( $order_id, true );
					}
				}

				$offset += $block_size;
			}
		}

	}

endif;

return new WCJ_Order_Numbers();

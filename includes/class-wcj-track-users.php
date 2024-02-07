<?php
/**
 * Booster for WooCommerce - Module - User Tracking
 *
 * @version 7.1.6
 * @since   3.1.3
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Track_Users' ) ) :
		/**
		 * WCJ_Track_Users.
		 *
		 * @version 7.1.6
		 * @since   3.6.0
		 */
	class WCJ_Track_Users extends WCJ_Module {

		/**
		 * The module track_users_scopes
		 *
		 * @var array
		 */
		public $track_users_scopes = array();
		/**
		 * Constructor.
		 *
		 * @version 7.1.4
		 * @since   3.1.3
		 * @todo    (maybe) if `wcj_track_users_enabled` set to `yes`, check if "General" module is also enabled (when upgrading from version 3.1.2)
		 */
		public function __construct() {

			$this->id         = 'track_users';
			$this->short_desc = __( 'User Tracking', 'woocommerce-jetpack' );
			$this->desc       = __( 'Track your users in WooCommerce. Track Orders (Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Track your users in WooCommerce.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-user-tracking';
			parent::__construct();

			// By country scopes.
			$this->track_users_scopes = array(
				'1'        => __( 'Last 24 hours', 'woocommerce-jetpack' ),
				'7'        => __( 'Last 7 days', 'woocommerce-jetpack' ),
				'28'       => __( 'Last 28 days', 'woocommerce-jetpack' ),
				'all_time' => __( 'All time', 'woocommerce-jetpack' ),
			);

			if ( $this->is_enabled() ) {
				// User tracking.
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_track_users_script' ) );
				add_action( 'wp_ajax_wcj_track_users', array( $this, 'track_users' ) );
				add_action( 'wp_ajax_nopriv_wcj_track_users', array( $this, 'track_users' ) );
				// Stats in dashboard widgets.
				if ( 'yes' === wcj_get_option( 'wcj_track_users_by_country_widget_enabled', 'yes' ) ) {
					add_action( 'wp_dashboard_setup', array( $this, 'add_track_users_dashboard_widgets' ) );
					add_action( 'admin_init', array( $this, 'maybe_delete_track_users_stats' ) );
					add_action( 'admin_init', array( $this, 'track_users_update_county_stats' ) );
				}
				// Order tracking.
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_track_users_save_order_http_referer_enabled', 'no' ) ) ) {
					add_action( 'woocommerce_new_order', array( $this, 'add_http_referer_to_order' ) );
					add_action( 'add_meta_boxes', array( $this, 'add_http_referer_order_meta_box' ) );
				}
				// Cron.
				add_action( 'init', array( $this, 'track_users_schedule_the_event' ) );
				add_action( 'admin_init', array( $this, 'track_users_schedule_the_event' ) );
				add_action( 'wcj_track_users_generate_stats', array( $this, 'track_users_generate_stats_cron' ) );
				// Orders columns.
				if (
				'yes' === wcj_get_option( 'wcj_track_users_shop_order_columns_referer', 'no' ) ||
				'yes' === wcj_get_option( 'wcj_track_users_shop_order_columns_referer_type', 'no' )
				) {
					if ( true === wcj_is_hpos_enabled() ) {
						add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'add_order_columns' ), PHP_INT_MAX - 1 );
						add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'render_order_columns_hpos' ), PHP_INT_MAX, 2 );
					} else {
						add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_columns' ), PHP_INT_MAX - 2 );
						add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_columns' ), PHP_INT_MAX );
					}
				}
			}
		}

		/**
		 * Add_order_columns.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 * @param array $columns defines the columns.
		 */
		public function add_order_columns( $columns ) {
			if ( 'yes' === wcj_get_option( 'wcj_track_users_shop_order_columns_referer', 'no' ) ) {
				$columns['wcj_track_users_referer'] = __( 'Referer', 'woocommerce-jetpack' );
			}
			if ( 'yes' === wcj_get_option( 'wcj_track_users_shop_order_columns_referer_type', 'no' ) ) {
				$columns['wcj_track_users_referer_type'] = __( 'Referer Type', 'woocommerce-jetpack' );
			}
			return $columns;
		}

		/**
		 * Render_order_columns.
		 *
		 * @version 5.6.2
		 * @since   3.6.0
		 * @param string $column defines the column.
		 */
		public function render_order_columns( $column ) {
			if ( 'wcj_track_users_referer' === $column || 'wcj_track_users_referer_type' === $column ) {
				$order_id = get_the_ID();
				$referer  = get_post_meta( $order_id, '_wcj_track_users_http_referer', true );
				switch ( $column ) {
					case 'wcj_track_users_referer':
						echo esc_html( $referer );
						break;
					case 'wcj_track_users_referer_type':
						echo esc_html( $this->get_referer_type( $referer ) );
						break;
				}
			}
		}

		/**
		 * Render_order_columns_hpos.
		 *
		 * @version 7.1.4
		 * @since   1.0.0
		 * @param string $column defines the column.
		 * @param string $order defines the order.
		 */
		public function render_order_columns_hpos( $column, $order ) {
			if ( 'wcj_track_users_referer' === $column || 'wcj_track_users_referer_type' === $column ) {
				$referer = $order->get_meta( '_wcj_track_users_http_referer' );
				switch ( $column ) {
					case 'wcj_track_users_referer':
						echo esc_html( $referer );
						break;
					case 'wcj_track_users_referer_type':
						echo esc_html( $this->get_referer_type( $referer ) );
						break;
				}
			}
		}

		/**
		 * Track_users_update_county_stats.
		 *
		 * @version 5.6.8
		 * @since   2.9.1
		 * @todo    (maybe) `wp_nonce`
		 */
		public function track_users_update_county_stats() {
			$wpnonce = isset( $_REQUEST['_wpnonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'wcj_track_users_update_county_stats' ) : false;
			if ( $wpnonce && isset( $_GET['wcj_track_users_update_county_stats'] ) ) {
				$this->track_users_generate_stats_cron( 'manual' );
				wp_safe_redirect( esc_url( remove_query_arg( array( 'wcj_track_users_update_county_stats', '_wpnonce' ) ) ) );
				exit;
			}
		}

		/**
		 * Track_users_schedule_the_event.
		 *
		 * @version 2.9.1
		 * @since   2.9.1
		 * @todo    (maybe) customizable interval
		 * @todo    (maybe) separate events for all time, last 28 days, last 7 days, last 24 hours
		 */
		public function track_users_schedule_the_event() {
			$event_timestamp = wp_next_scheduled( 'wcj_track_users_generate_stats', array( 'hourly' ) );
			update_option( 'wcj_track_users_cron_time_schedule', $event_timestamp );
			if ( ! $event_timestamp ) {
				wp_schedule_event( time(), 'hourly', 'wcj_track_users_generate_stats', array( 'hourly' ) );
			}
		}

		/**
		 * Track_users_generate_stats_cron.
		 *
		 * @version 2.9.1
		 * @since   2.9.
		 * @param string $interval defines the interval.
		 */
		public function track_users_generate_stats_cron( $interval ) {
			update_option( 'wcj_track_users_cron_time_last_run', time() );
			$stats = wcj_get_option( 'wcj_track_users_stats_by_country', array() );
			foreach ( $this->track_users_scopes as $scope => $scope_title ) {
				$stats[ $scope ] = $this->generate_track_users_stats_by_country( $scope );
			}
			update_option( 'wcj_track_users_stats_by_country', $stats );
		}

		/**
		 * Add_http_referer_order_meta_box.
		 *
		 * @version 7.1.4
		 * @since   2.9.1
		 */
		public function add_http_referer_order_meta_box() {

			if ( true === wcj_is_hpos_enabled() ) {

				add_meta_box(
					'wc-jetpack-' . $this->id,
					__( 'Booster', 'woocommerce-jetpack' ) . ': ' . __( 'Acquisition Source', 'woocommerce-jetpack' ),
					array( $this, 'create_http_referer_order_meta_box' ),
					'woocommerce_page_wc-orders',
					'side',
					'low'
				);

			} else {

				add_meta_box(
					'wc-jetpack-' . $this->id,
					__( 'Booster', 'woocommerce-jetpack' ) . ': ' . __( 'Acquisition Source', 'woocommerce-jetpack' ),
					array( $this, 'create_http_referer_order_meta_box' ),
					'shop_order',
					'side',
					'low'
				);
			}
		}

		/**
		 * Get_referer_type.
		 *
		 * @version 3.6.0
		 * @since   2.9.1
		 * @todo    group hosts by type (i.e. "Search Engines", "Social" etc.)
		 * @param string $http_referer defines the http_referer.
		 */
		public function get_referer_type( $http_referer ) {
			if ( '' !== $http_referer && 'N/A' !== $http_referer ) {
				$http_referer_info = wp_parse_url( $http_referer );
				if ( ( $http_referer_info ) && isset( $http_referer_info['host'] ) ) {
					return $http_referer_info['host'];
				}
			}
			return 'N/A';
		}

		/**
		 * Create_http_referer_order_meta_box.
		 *
		 * @version 7.1.4
		 * @since   2.9.1
		 */
		public function create_http_referer_order_meta_box() {
			if ( true === wcj_is_hpos_enabled() ) {
				$order_id = isset( $_REQUEST['id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification
				$_order   = wcj_get_order( $order_id );
				if ( $_order && false !== $_order ) {
					$http_referer = $_order->get_meta( '_wcj_track_users_http_referer' );
				}
			} else {
				$http_referer = get_post_meta( get_the_ID(), '_wcj_track_users_http_referer', true );
			}

			if ( '' === ( $http_referer ) ) {
				$http_referer = 'N/A';
			}
			echo '<p>' . esc_html__( 'URL:', 'woocommerce-jetpack' ) . ' ' . wp_kses_post( $http_referer ) . '</p>';
			echo '<p>' . esc_html__( 'Type:', 'woocommerce-jetpack' ) . ' ' . wp_kses_post( $this->get_referer_type( $http_referer ) ) . '</p>';
		}

		/**
		 * Add_http_referer_to_order.
		 *
		 * @version 7.1.4
		 * @since   2.9.1
		 * @todo    add "all orders by referer type" stats
		 * @param int $order_id defines the order_id.
		 */
		public function add_http_referer_to_order( $order_id = null ) {
			global $wpdb;
			$table_name   = $wpdb->prefix . 'wcj_track_users';
			$http_referer = 'N/A';
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
				$user_ip = ( class_exists( 'WC_Geolocation' ) ? WC_Geolocation::get_ip_address() : wcj_get_the_ip() );
				$result  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wcj_track_users WHERE ip = %s ORDER BY time DESC", $user_ip ) );
				if ( $result ) {
					$http_referer = $result->referer;
				}
			}
			if ( true === wcj_is_hpos_enabled() ) {

				$order = wcj_get_order( $order_id );
				if ( $order && false !== $order ) {
					$order->update_meta_data( '_wcj_track_users_http_referer', $http_referer );
					$order->save();
				}
			} else {
				// phpcs:enable
				update_post_meta( $order_id, '_wcj_track_users_http_referer', $http_referer );

			}
		}

		/**
		 * Maybe_delete_track_users_stats.
		 *
		 * @version 5.6.8
		 * @since   2.9.1
		 * @todo    (maybe) wp_nonce
		 */
		public function maybe_delete_track_users_stats() {
			$wpnonce = isset( $_REQUEST['_wpnonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'wcj_delete_track_users_stats' ) : false;
			if ( $wpnonce && isset( $_GET['wcj_delete_track_users_stats'] ) ) {
				global $wpdb;
				$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wcj_track_users" );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				delete_option( 'wcj_track_users_stats_by_country' );
				delete_option( 'wcj_track_users_cron_time_last_run' );
				wp_safe_redirect( remove_query_arg( array( 'wcj_delete_track_users_stats', '_wpnonce' ) ) );
				exit;
			}
		}

		/**
		 * Add a widgets to the dashboard.
		 *
		 * @version 2.9.1
		 * @since   2.9.1
		 */
		public function add_track_users_dashboard_widgets() {
			wp_add_dashboard_widget(
				'wcj_track_users_dashboard_widget',
				__( 'Booster', 'woocommerce-jetpack' ) . ': ' . sprintf(
					/* translators: %d: translators Added */
					__( 'Top %d countries by visits', 'woocommerce-jetpack' ),
					get_option( 'wcj_track_users_by_country_widget_top_count', 10 )
				),
				array( $this, 'track_users_by_country_dashboard_widget' )
			);
		}

		/**
		 * Get_saved_track_users_stats_by_country.
		 *
		 * @version 2.9.1
		 * @since   2.9.1
		 * @param string $scope defines the scope.
		 */
		public function get_saved_track_users_stats_by_country( $scope ) {
			$stats = wcj_get_option( 'wcj_track_users_stats_by_country', array() );
			return ( isset( $stats[ $scope ] ) ? $stats[ $scope ] : array() );
		}

		/**
		 * Generate_track_users_stats_by_country.
		 *
		 * @version 5.6.8
		 * @since   2.9.1
		 * @param string | int $scope defines the scope.
		 */
		public function generate_track_users_stats_by_country( $scope ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'wcj_track_users';
			switch ( $scope ) {
				case 'all_time':
					$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wcj_track_users" );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					break;
				default:
					$time_expired = gmdate( 'Y-m-d H:i:s', ( wcj_get_timestamp_date_from_gmt() - $scope * 24 * 60 * 60 ) );
					$results      = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wcj_track_users WHERE time > %s", $time_expired ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					break;
			}
			$totals = array();
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name && ( $results ) ) {// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				foreach ( $results as $result ) {
					if ( ! isset( $totals[ $result->country ] ) ) {
						$totals[ $result->country ] = 1;
					} else {
						$totals[ $result->country ]++;
					}
				}
				arsort( $totals );
			}
			return $totals;
		}

		/**
		 * Track_users_by_country_dashboard_widget.
		 *
		 * @version 6.0.3
		 * @since   2.9.1
		 * @todo    (maybe) display all info (IP, referer etc.) on country click
		 * @todo    (maybe) display stats by day and/or month
		 * @todo    (maybe) display stats by state
		 * @param string | array $post defines the post.
		 * @param string | array $args defines the args.
		 */
		public function track_users_by_country_dashboard_widget( $post, $args ) {
			$top_count = wcj_get_option( 'wcj_track_users_by_country_widget_top_count', 10 );
			foreach ( $this->track_users_scopes as $scope => $scope_title ) {
				if ( ! in_array( (string) $scope, wcj_get_option( 'wcj_track_users_by_country_widget_scopes', array( '1', '28' ) ), true ) ) {
					continue;
				}
				$totals = $this->get_saved_track_users_stats_by_country( $scope );
				if ( ! empty( $totals ) ) {
					$totals       = array_slice( $totals, 0, $top_count );
					$table_data   = array();
					$table_data[] = array( '', __( 'Country', 'woocommerce-jetpack' ), __( 'Visits', 'woocommerce-jetpack' ) );
					$i            = 0;
					foreach ( $totals as $country_code => $visits ) {
						$i++;
						$country_info = ( '' !== $country_code ? wcj_get_country_flag_by_code( $country_code ) . ' ' . wcj_get_country_name_by_code( $country_code ) : 'N/A' );
						$table_data[] = array( $i, $country_info, $visits );
					}
					echo '<strong>' . wp_kses_post( $scope_title ) . '</strong>';
					echo wp_kses_post(
						wcj_get_table_html(
							$table_data,
							array(
								'table_class'        => 'widefat striped',
								'table_heading_type' => 'horizontal',
							)
						)
					);
				} else {
					echo '<p> <em>' . wp_kses_post( 'No stats yet.', 'woocommerce-jetpack' ) . '</em> </p>';
				}
			}
			echo '<p>' .
			'<a class="button-primary" href="' . esc_url(
				add_query_arg(
					array(
						'wcj_delete_track_users_stats' => '1',
						'_wpnonce'                     => wp_create_nonce( 'wcj_delete_track_users_stats' ),
					)
				)
			) . '" ' .
				'onclick="return confirm(\'' . wp_kses_post( 'Are you sure?', 'woocommerce-jetpack' ) . '\')"' .
			'>' . wp_kses_post( 'Delete all tracking data', 'woocommerce-jetpack' ) . '</a>' .
			'</p>';
			$_time              = wcj_get_option( 'wcj_track_users_cron_time_last_run', '' );
			$cron_last_run      = ( '' !== ( $_time ) ? gmdate( 'Y-m-d H:i:s', $_time ) : '-' );
			$_time              = wcj_get_option( 'wcj_track_users_cron_time_schedule', '' );
			$cron_next_schedule = ( '' !== ( $_time ) ? gmdate( 'Y-m-d H:i:s', $_time ) : '-' );
			echo '<p>' .
			/* translators: %1$s, %2$s translators Added */
			sprintf( esc_html__( 'Stats generated at %1$s. Next update is scheduled at %2$s.', 'woocommerce-jetpack' ), esc_html( $cron_last_run ), esc_html( $cron_next_schedule ) ) . ' ' .
			'<a href="' . esc_url(
				add_query_arg(
					array(
						'wcj_track_users_update_county_stats' => '1',
						'_wpnonce' => wp_create_nonce( 'wcj_track_users_update_county_stats' ),
					)
				)
			) . '">' . esc_html__( 'Update now', 'woocommerce-jetpack' ) . '</a>.' .
			'</p>';
		}

		/**
		 * Enqueue_track_users_script.
		 *
		 * @version 5.6.8
		 * @since   2.9.1
		 */
		public function enqueue_track_users_script() {
			wp_enqueue_script( 'wcj-track-users', trailingslashit( wcj_plugin_url() ) . 'includes/js/wcj-track-users.js', array( 'jquery' ), w_c_j()->version, true );
			wp_localize_script(
				'wcj-track-users',
				'track_users_ajax_object',
				array(
					'ajax_url'     => admin_url( 'admin-ajax.php' ),
					'http_referer' => ( isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : 'N/A' ),
					'wpnonce'      => wp_create_nonce( 'wcj-track-users' ),
					'user_ip'      => ( class_exists( 'WC_Geolocation' ) ? WC_Geolocation::get_ip_address() : wcj_get_the_ip() ),
				)
			);
		}

		/**
		 * Track_users.
		 *
		 * @version 6.0.0
		 * @since   2.9.1
		 * @todo    (maybe) customizable `$time_expired`
		 * @todo    (maybe) optionally do not track selected user roles (e.g. admin)
		 */
		public function track_users() {
			$wpnonce = isset( $_REQUEST['wpnonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wpnonce'] ), 'wcj-track-users' ) : false;
			if ( ! $wpnonce || ! isset( $_POST['wcj_user_ip'] ) ) {
				die();
			}
			$user_ip = sanitize_text_field( wp_unslash( $_POST['wcj_user_ip'] ) );
			global $wpdb;
			$table_name = $wpdb->prefix . 'wcj_track_users';
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				// Create DB table.
				$charset_collate = $wpdb->get_charset_collate();
				$sql             = "CREATE TABLE $table_name (
				id int NOT NULL AUTO_INCREMENT,
				time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				country tinytext NOT NULL,
				state tinytext NOT NULL,
				ip text NOT NULL,
				referer text NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta( $sql );
			} else {
				// Check if already tracked recently.
				$time_expired = gmdate( 'Y-m-d H:i:s', strtotime( '-1 day', wcj_get_timestamp_date_from_gmt() ) );
				$result       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wcj_track_users WHERE ip = %s AND time > %s", $user_ip, $time_expired ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $result ) {
					return;
				}
			}
			// Country by IP.
			$location = ( class_exists( 'WC_Geolocation' ) ? WC_Geolocation::geolocate_ip( $user_ip ) : array(
				'country' => '',
				'state'   => '',
			) );
			// HTTP referrer.
			$http_referer = ( isset( $_POST['wcj_http_referer'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_http_referer'] ) ) : 'N/A' );
			// Add row to DB table.
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$table_name,
				array(
					'time'    => current_time( 'mysql' ),
					'country' => $location['country'],
					'state'   => $location['state'],
					'ip'      => $user_ip,
					'referer' => $http_referer,
				)
			);
			die();
		}

	}

endif;

return new WCJ_Track_Users();

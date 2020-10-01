<?php
/**
 * Booster for WooCommerce - Module - User Tracking
 *
 * @version 5.2.0
 * @since   3.1.3
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_User_Tracking' ) ) :

class WCJ_User_Tracking extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   3.1.3
	 * @todo    (maybe) if `wcj_track_users_enabled` set to `yes`, check if "General" module is also enabled (when upgrading from version 3.1.2)
	 */
	function __construct() {

		$this->id         = 'track_users';
		$this->short_desc = __( 'User Tracking', 'woocommerce-jetpack' );
		$this->desc       = __( 'Track your users in WooCommerce. Track Orders (Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Track your users in WooCommerce.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-user-tracking';
		parent::__construct();

		// By country scopes
		$this->track_users_scopes = array(
			'1'        => __( 'Last 24 hours', 'woocommerce-jetpack' ),
			'7'        => __( 'Last 7 days', 'woocommerce-jetpack' ),
			'28'       => __( 'Last 28 days', 'woocommerce-jetpack' ),
			'all_time' => __( 'All time', 'woocommerce-jetpack' ),
		);

		if ( $this->is_enabled() ) {
			// User tracking
			add_action( 'wp_enqueue_scripts',                  array( $this, 'enqueue_track_users_script' ) );
			add_action( 'wp_ajax_'        . 'wcj_track_users', array( $this, 'track_users' ) );
			add_action( 'wp_ajax_nopriv_' . 'wcj_track_users', array( $this, 'track_users' ) );
			// Stats in dashboard widgets
			if ( 'yes' === wcj_get_option( 'wcj_track_users_by_country_widget_enabled', 'yes' ) ) {
				add_action( 'wp_dashboard_setup', array( $this, 'add_track_users_dashboard_widgets' ) );
				add_action( 'admin_init',         array( $this, 'maybe_delete_track_users_stats' ) );
				add_action( 'admin_init',         array( $this, 'track_users_update_county_stats' ) );
			}
			// Order tracking
			if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_track_users_save_order_http_referer_enabled', 'no' ) ) ) {
				add_action( 'woocommerce_new_order', array( $this, 'add_http_referer_to_order' ) );
				add_action( 'add_meta_boxes',        array( $this, 'add_http_referer_order_meta_box' ) );
			}
			// Cron
			add_action( 'init',                           array( $this, 'track_users_schedule_the_event' ) );
			add_action( 'admin_init',                     array( $this, 'track_users_schedule_the_event' ) );
			add_action( 'wcj_track_users_generate_stats', array( $this, 'track_users_generate_stats_cron' ) );
			// Orders columns
			if (
				'yes' === wcj_get_option( 'wcj_track_users_shop_order_columns_referer', 'no' ) ||
				'yes' === wcj_get_option( 'wcj_track_users_shop_order_columns_referer_type', 'no' )
			) {
				add_filter( 'manage_edit-shop_order_columns',        array( $this, 'add_order_columns' ),    PHP_INT_MAX - 2 );
				add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_columns' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * add_order_columns.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function add_order_columns( $columns ) {
		if ( 'yes' === wcj_get_option( 'wcj_track_users_shop_order_columns_referer', 'no' ) ) {
			$columns['wcj_track_users_referer'] = __( 'Referer', 'woocommerce-jetpack' );
		}
		if ( 'yes' === wcj_get_option( 'wcj_track_users_shop_order_columns_referer_type', 'no' ) ) {
			$columns['wcj_track_users_referer_type'] = __( 'Referer Type', 'woocommerce-jetpack' );
		}
		return $columns;
	}

	/**
	 * render_order_columns.
	 *
	 * @param   string $column
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function render_order_columns( $column ) {
		if ( 'wcj_track_users_referer' === $column || 'wcj_track_users_referer_type' === $column ) {
			$order_id = get_the_ID();
			$referer  = get_post_meta( $order_id, '_wcj_track_users_http_referer', true );
			switch ( $column ) {
				case 'wcj_track_users_referer':
					echo $referer;
					break;
				case 'wcj_track_users_referer_type':
					echo $this->get_referer_type( $referer );
					break;
			}
		}
	}

	/**
	 * track_users_update_county_stats.
	 *
	 * @version 3.9.0
	 * @since   2.9.1
	 * @todo    (maybe) `wp_nonce`
	 */
	function track_users_update_county_stats() {
		if ( isset( $_GET['wcj_track_users_update_county_stats'] ) ) {
			$this->track_users_generate_stats_cron( 'manual' );
			wp_safe_redirect( remove_query_arg( 'wcj_track_users_update_county_stats' ) );
			exit;
		}
	}

	/**
	 * track_users_schedule_the_event.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 * @todo    (maybe) customizable interval
	 * @todo    (maybe) separate events for all time, last 28 days, last 7 days, last 24 hours
	 */
	function track_users_schedule_the_event() {
		$event_timestamp = wp_next_scheduled( 'wcj_track_users_generate_stats', array( 'hourly' ) );
		update_option( 'wcj_track_users_cron_time_schedule', $event_timestamp );
		if ( ! $event_timestamp ) {
			wp_schedule_event( time(), 'hourly', 'wcj_track_users_generate_stats', array( 'hourly' ) );
		}
	}

	/**
	 * track_users_generate_stats_cron.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function track_users_generate_stats_cron( $interval ) {
		update_option( 'wcj_track_users_cron_time_last_run', time() );
		$stats = wcj_get_option( 'wcj_track_users_stats_by_country', array() );
		foreach ( $this->track_users_scopes as $scope => $scope_title ) {
			$stats[ $scope ] = $this->generate_track_users_stats_by_country( $scope );
		}
		update_option( 'wcj_track_users_stats_by_country', $stats );
	}

	/**
	 * add_http_referer_order_meta_box.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function add_http_referer_order_meta_box() {
		add_meta_box(
			'wc-jetpack-' . $this->id,
			__( 'Booster', 'woocommerce-jetpack' ) . ': ' . __( 'Acquisition Source', 'woocommerce-jetpack' ),
			array( $this, 'create_http_referer_order_meta_box' ),
			'shop_order',
			'side',
			'low'
		);
	}

	/**
	 * get_referer_type.
	 *
	 * @version 3.6.0
	 * @since   2.9.1
	 * @todo    group hosts by type (i.e. "Search Engines", "Social" etc.)
	 */
	function get_referer_type( $http_referer ) {
		if ( '' != $http_referer && 'N/A' != $http_referer ) {
			if ( ( $http_referer_info = parse_url( $http_referer ) ) && isset( $http_referer_info['host'] ) ) {
				return $http_referer_info['host'];
			}
		}
		return 'N/A';
	}

	/**
	 * create_http_referer_order_meta_box.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function create_http_referer_order_meta_box() {
		if ( '' == ( $http_referer = get_post_meta( get_the_ID(), '_wcj_track_users_http_referer', true ) ) ) {
			$http_referer = 'N/A';
		}
		echo '<p>' . __( 'URL:',  'woocommerce-jetpack' ) . ' ' . $http_referer . '</p>';
		echo '<p>' . __( 'Type:', 'woocommerce-jetpack' ) . ' ' . $this->get_referer_type( $http_referer ) . '</p>';
	}

	/**
	 * add_http_referer_to_order.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 * @todo    add "all orders by referer type" stats
	 */
	function add_http_referer_to_order( $order_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wcj_track_users';
		$http_referer = 'N/A';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
			$user_ip = ( class_exists( 'WC_Geolocation' ) ? WC_Geolocation::get_ip_address() : wcj_get_the_ip() );
			$result = $wpdb->get_row( "SELECT * FROM $table_name WHERE ip = '$user_ip' ORDER BY time DESC" );
			if ( $result ) {
				$http_referer = $result->referer;
			}
		}
		update_post_meta( $order_id, '_wcj_track_users_http_referer', $http_referer );
	}

	/**
	 * maybe_delete_track_users_stats.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 * @todo    (maybe) wp_nonce
	 */
	function maybe_delete_track_users_stats() {
		if ( isset( $_GET['wcj_delete_track_users_stats'] ) /* && is_super_admin() */ ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'wcj_track_users';
			$sql = "DROP TABLE IF EXISTS $table_name";
			$wpdb->query( $sql );
			delete_option( 'wcj_track_users_stats_by_country' );
			delete_option( 'wcj_track_users_cron_time_last_run' );
			wp_safe_redirect( remove_query_arg( 'wcj_delete_track_users_stats' ) );
			exit;
		}
	}

	/**
	 * Add a widgets to the dashboard.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function add_track_users_dashboard_widgets() {
		wp_add_dashboard_widget(
			'wcj_track_users_dashboard_widget',
			__( 'Booster', 'woocommerce-jetpack' ) . ': ' . sprintf( __( 'Top %d countries by visits', 'woocommerce-jetpack' ),
				get_option( 'wcj_track_users_by_country_widget_top_count', 10 ) ),
			array( $this, 'track_users_by_country_dashboard_widget' )
		);
	}

	/**
	 * get_saved_track_users_stats_by_country.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function get_saved_track_users_stats_by_country( $scope ) {
		$stats = wcj_get_option( 'wcj_track_users_stats_by_country', array() );
		return ( isset( $stats[ $scope ] ) ? $stats[ $scope ] : array() );
	}

	/**
	 * generate_track_users_stats_by_country.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function generate_track_users_stats_by_country( $scope ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wcj_track_users';
		switch ( $scope ) {
			case 'all_time':
				$select_query = "SELECT * FROM $table_name";
				break;
			default: // '28', '7', '1'
				$time_expired = date( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) - $scope * 24 * 60 * 60 ) );
				$select_query = "SELECT * FROM $table_name WHERE time > '" . $time_expired . "'";
				break;
		}
		$totals = array();
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name && ( $results = $wpdb->get_results( $select_query ) ) ) {
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
	 * track_users_by_country_dashboard_widget.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 * @todo    (maybe) display all info (IP, referer etc.) on country click
	 * @todo    (maybe) display stats by day and/or month
	 * @todo    (maybe) display stats by state
	 */
	function track_users_by_country_dashboard_widget( $post, $args ) {
		$top_count = wcj_get_option( 'wcj_track_users_by_country_widget_top_count', 10 );
		foreach ( $this->track_users_scopes as $scope => $scope_title ) {
			if ( ! in_array( $scope, wcj_get_option( 'wcj_track_users_by_country_widget_scopes', array( '1', '28' ) ) ) ) {
				continue;
			}
			$totals = $this->get_saved_track_users_stats_by_country( $scope );
			if ( ! empty( $totals ) ) {
				$totals = array_slice( $totals, 0, $top_count );
				$table_data = array();
				$table_data[] = array( '', __( 'Country', 'woocommerce-jetpack' ), __( 'Visits', 'woocommerce-jetpack' ) );
				$i = 0;
				foreach ( $totals as $country_code => $visits ) {
					$i++;
					$country_info = ( '' != $country_code ? wcj_get_country_flag_by_code( $country_code ) . ' ' . wcj_get_country_name_by_code( $country_code ) : 'N/A' );
					$table_data[] = array( $i, $country_info, $visits );
				}
				echo '<strong>' . $scope_title . '</strong>';
				echo wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped', 'table_heading_type' => 'horizontal' ) );
			} else {
				echo '<p>' . '<em>' . __( 'No stats yet.', 'woocommerce-jetpack' ) . '</em>' . '</p>';
			}
		}
		echo '<p>' .
			'<a class="button-primary" href="' . add_query_arg( 'wcj_delete_track_users_stats', '1' ) . '" ' .
				'onclick="return confirm(\'' . __( 'Are you sure?', 'woocommerce-jetpack' ) . '\')"' .
			'>' . __( 'Delete all tracking data', 'woocommerce-jetpack' ) . '</a>' .
		'</p>';
		$cron_last_run      = ( '' != ( $_time = wcj_get_option( 'wcj_track_users_cron_time_last_run', '' ) ) ? date( 'Y-m-d H:i:s', $_time ) : '-' );
		$cron_next_schedule = ( '' != ( $_time = wcj_get_option( 'wcj_track_users_cron_time_schedule', '' ) ) ? date( 'Y-m-d H:i:s', $_time ) : '-' );
		echo '<p>' .
			sprintf( __( 'Stats generated at %s. Next update is scheduled at %s.', 'woocommerce-jetpack' ), $cron_last_run, $cron_next_schedule ) . ' ' .
			'<a href="' . add_query_arg( 'wcj_track_users_update_county_stats', '1' ) . '">' . __( 'Update now', 'woocommerce-jetpack' ) . '</a>.' .
		'</p>';
	}

	/**
	 * enqueue_track_users_script.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function enqueue_track_users_script() {
		wp_enqueue_script(  'wcj-track-users',  trailingslashit( wcj_plugin_url() ) . 'includes/js/wcj-track-users.js', array( 'jquery' ), WCJ()->version, true );
		wp_localize_script( 'wcj-track-users', 'track_users_ajax_object', array(
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'http_referer' => ( isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : 'N/A' ),
			'user_ip'      => ( class_exists( 'WC_Geolocation' ) ? WC_Geolocation::get_ip_address() : wcj_get_the_ip() ),
		) );
	}

	/**
	 * track_users.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 * @todo    (maybe) customizable `$time_expired`
	 * @todo    (maybe) optionally do not track selected user roles (e.g. admin)
	 */
	function track_users() {
		if ( ! isset( $_POST['wcj_user_ip'] ) ) {
			die();
		}
		$user_ip = $_POST['wcj_user_ip'];
		global $wpdb;
		$table_name = $wpdb->prefix . 'wcj_track_users';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
			// Create DB table
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE $table_name (
				id int NOT NULL AUTO_INCREMENT,
				time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				country tinytext NOT NULL,
				state tinytext NOT NULL,
				ip text NOT NULL,
				referer text NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		} else {
			// Check if already tracked recently
			$time_expired = date( 'Y-m-d H:i:s', strtotime( '-1 day', current_time( 'timestamp' ) ) );
			$result = $wpdb->get_row( "SELECT * FROM $table_name WHERE ip = '$user_ip' AND time > '$time_expired'" );
			if ( $result ) {
				return;
			}
		}
		// Country by IP
		$location = ( class_exists( 'WC_Geolocation' ) ? WC_Geolocation::geolocate_ip( $user_ip ) : array( 'country' => '', 'state' => '' ) );
		// HTTP referrer
		$http_referer = ( isset( $_POST['wcj_http_referer'] ) ? $_POST['wcj_http_referer'] : 'N/A' );
		// Add row to DB table
		$wpdb->insert(
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

return new WCJ_User_Tracking();

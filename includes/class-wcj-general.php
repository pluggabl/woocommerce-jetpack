<?php
/**
 * Booster for WooCommerce - Module - General
 *
 * @version 2.9.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_General' ) ) :

class WCJ_General extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.1
	 */
	function __construct() {

		$this->id         = 'general';
		$this->short_desc = __( 'General', 'woocommerce-jetpack' );
		$this->desc       = __( 'Custom roles tool. Shortcodes in WordPress text widgets.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-booster-general-tools';
		parent::__construct();

		$this->add_tools( array(
			'products_atts'    => array(
				'title'     => __( 'Products Attributes', 'woocommerce-jetpack' ),
				'desc'      => __( 'All Products and All Attributes.', 'woocommerce-jetpack' ),
			),
			'custom_roles' => array(
				'title'     => __( 'Add/Manage Custom Roles', 'woocommerce-jetpack' ),
				'tab_title' => __( 'Custom Roles', 'woocommerce-jetpack' ),
				'desc'      => __( 'Manage Custom Roles.', 'woocommerce-jetpack' ),
			),
		) );

		// By country scopes
		$this->track_users_scopes = array(
			'1'        => __( 'Last 24 hours', 'woocommerce-jetpack' ),
			'7'        => __( 'Last 7 days', 'woocommerce-jetpack' ),
			'28'       => __( 'Last 28 days', 'woocommerce-jetpack' ),
			'all_time' => __( 'All time', 'woocommerce-jetpack' ),
		);

		if ( $this->is_enabled() ) {

			// Recalculate cart totals
			if ( 'yes' === get_option( 'wcj_general_advanced_recalculate_cart_totals', 'no' ) ) {
				add_action( 'wp_loaded', array( $this, 'fix_mini_cart' ), PHP_INT_MAX );
			}

			// Product revisions
			if ( 'yes' === get_option( 'wcj_product_revisions_enabled', 'no' ) ) {
				add_filter( 'woocommerce_register_post_type_product', array( $this, 'enable_product_revisions' ) );
			}

			// Shortcodes in text widgets
			if ( 'yes' === get_option( 'wcj_general_shortcodes_in_text_widgets_enabled' ) ) {
				add_filter( 'widget_text', 'do_shortcode' );
			}

			// PayPal email per product
			if ( 'yes' === get_option( 'wcj_paypal_email_per_product_enabled', 'no' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_payment_gateways', array( $this, 'maybe_change_paypal_email' ) );
			}

			// Session expiration
			if ( 'yes' === get_option( 'wcj_session_expiration_section_enabled', 'no' ) ) {
				add_filter( 'wc_session_expiring',   array( $this, 'change_session_expiring' ),   PHP_INT_MAX );
				add_filter( 'wc_session_expiration', array( $this, 'change_session_expiration' ), PHP_INT_MAX );
			}

			// Booster role user changer
			if ( wcj_is_booster_role_changer_enabled() ) {
				add_action( 'admin_bar_menu', array( $this, 'add_user_role_changer' ), PHP_INT_MAX );
				add_action( 'init',           array( $this, 'change_user_role_meta' ) );
			}

			// Track users
			if ( 'yes' === get_option( 'wcj_track_users_enabled', 'no' ) ) {
				// User tracking
				add_action( 'wp_enqueue_scripts',                  array( $this, 'enqueue_track_users_script' ) );
				add_action( 'wp_ajax_'        . 'wcj_track_users', array( $this, 'track_users' ) );
				add_action( 'wp_ajax_nopriv_' . 'wcj_track_users', array( $this, 'track_users' ) );
				// Stats in dashboard widgets
				if ( 'yes' === get_option( 'wcj_track_users_by_country_widget_enabled', 'yes' ) ) {
					add_action( 'wp_dashboard_setup', array( $this, 'add_track_users_dashboard_widgets' ) );
					add_action( 'admin_init',         array( $this, 'maybe_delete_track_users_stats' ) );
					add_action( 'admin_init',         array( $this, 'track_users_update_county_stats' ) );
				}
				// Order tracking
				if ( 'yes' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_track_users_save_order_http_referer_enabled', 'no' ) ) ) {
					add_action( 'woocommerce_new_order', array( $this, 'add_http_referer_to_order' ) );
					add_action( 'add_meta_boxes',        array( $this, 'add_http_referer_order_meta_box' ) );
				}
				// Cron
				add_action( 'init',                           array( $this, 'track_users_schedule_the_event' ) );
				add_action( 'admin_init',                     array( $this, 'track_users_schedule_the_event' ) );
				add_action( 'wcj_track_users_generate_stats', array( $this, 'track_users_generate_stats_cron' ) );
			}
		}
	}

	/**
	 * track_users_update_county_stats.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 * @todo    (maybe) `wp_nonce`
	 */
	function track_users_update_county_stats() {
		if ( isset( $_GET['wcj_track_users_update_county_stats'] ) ) {
			$this->track_users_generate_stats_cron();
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
		$stats = get_option( 'wcj_track_users_stats_by_country', array() );
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
	 * @version 2.9.1
	 * @since   2.9.1
	 * @todo    this is not finished!
	 */
	function get_referer_type( $http_referer ) {
		if ( '' != $http_referer && 'N/A' != $http_referer ) {
			if ( ( $http_referer_info = parse_url( $http_referer ) ) && isset( $http_referer_info['host'] ) ) {
				if ( false !== stripos( $http_referer_info['host'], 'google.' ) ) {
					return 'Google';
				} elseif ( false !== stripos( $http_referer_info['host'], 'wordpress.' ) ) {
					return 'WordPress';
				} elseif ( false !== stripos( $http_referer_info['host'], 'facebook.' ) ) {
					return 'Facebook';
				} else {
					return __( 'Other', 'woocommerce-jetpack' );
				}
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
		$stats = get_option( 'wcj_track_users_stats_by_country', array() );
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
		$top_count = get_option( 'wcj_track_users_by_country_widget_top_count', 10 );
		foreach ( $this->track_users_scopes as $scope => $scope_title ) {
			if ( ! in_array( $scope, get_option( 'wcj_track_users_by_country_widget_scopes', array( '1', '28' ) ) ) ) {
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
		$cron_last_run      = ( '' != ( $_time = get_option( 'wcj_track_users_cron_time_last_run', '' ) ) ? date( 'Y-m-d H:i:s', $_time ) : '-' );
		$cron_next_schedule = ( '' != ( $_time = get_option( 'wcj_track_users_cron_time_schedule', '' ) ) ? date( 'Y-m-d H:i:s', $_time ) : '-' );
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

	/**
	 * change_user_role_meta.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) optionally via cookies
	 */
	function change_user_role_meta() {
		if ( isset( $_GET['wcj_booster_user_role'] ) ) {
			$current_user_id = get_current_user_id();
			update_user_meta( $current_user_id, '_' . 'wcj_booster_user_role', $_GET['wcj_booster_user_role'] );
		}
	}

	/**
	 * add_user_role_changer.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function add_user_role_changer( $wp_admin_bar ) {
		$current_user_id  = get_current_user_id();
		$user_roles       = wcj_get_user_roles_options();
		if ( '' != ( $current_booster_user_role = get_user_meta( $current_user_id, '_' . 'wcj_booster_user_role', true ) ) ) {
			$current_booster_user_role = ( isset( $user_roles[ $current_booster_user_role ] ) ) ? $user_roles[ $current_booster_user_role ] : $current_booster_user_role;
			$current_booster_user_role = ' [' . $current_booster_user_role . ']';
		}
		$args = array(
			'parent' => false,
			'id'     => 'booster-user-role-changer',
			'title'  => __( 'Booster User Role', 'woocommerce-jetpack' ) . $current_booster_user_role,
			'href'   => false,
		);
		$wp_admin_bar->add_node( $args );
		foreach ( $user_roles as $user_role_key => $user_role_name ) {
			$args = array(
				'parent' => 'booster-user-role-changer',
				'id'     => 'booster-user-role-changer-role-' . $user_role_key,
				'title'  => $user_role_name,
				'href'   => add_query_arg( 'wcj_booster_user_role', $user_role_key ),
			);
			$wp_admin_bar->add_node( $args );
		}
	}

	/**
	 * fix_mini_cart.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 * @todo    this is only temporary solution!
	 */
	function fix_mini_cart() {
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			if ( null !== ( $wc = WC() ) ) {
				if ( isset( $wc->cart ) ) {
					$wc->cart->calculate_totals();
				}
			}
		}
	}

	/**
	 * change_session_expiring.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function change_session_expiring( $the_time ) {
		return get_option( 'wcj_session_expiring', 47 * 60 * 60 );
	}

	/**
	 * change_session_expiration.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function change_session_expiration( $the_time ) {
		return get_option( 'wcj_session_expiration', 48 * 60 * 60 );
	}

	/**
	 * create_custom_roles_tool.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function create_custom_roles_tool() {
		if ( isset( $_POST['wcj_add_new_role'] ) ) {
			if (
				! isset( $_POST['wcj_custom_role_id'] )   || '' == $_POST['wcj_custom_role_id'] ||
				! isset( $_POST['wcj_custom_role_name'] ) || '' == $_POST['wcj_custom_role_name']
			) {
				echo '<p style="color:red;font-weight:bold;">' . __( 'Both fields are required!', 'woocommerce-jetpack') . '</p>';
			} else {
				if ( is_numeric( $_POST['wcj_custom_role_id'] ) ) {
					echo '<p style="color:red;font-weight:bold;">' . __( 'Role ID must not be numbers only!', 'woocommerce-jetpack') . '</p>';
				} else {
					$result = add_role( $_POST['wcj_custom_role_id'], $_POST['wcj_custom_role_name'] );
					if ( null !== $result ) {
						echo '<p style="color:green;font-weight:bold;">' . __( 'Role successfully added!', 'woocommerce-jetpack') . '</p>';
					} else {
						echo '<p style="color:red;font-weight:bold;">' . __( 'Role already exists!', 'woocommerce-jetpack') . '</p>';
					}
				}
			}
		}

		if ( isset( $_GET['wcj_delete_role'] ) && '' != $_GET['wcj_delete_role'] ) {
			remove_role( $_GET['wcj_delete_role'] );
			echo '<p style="color:green;font-weight:bold;">' . sprintf( __( 'Role %s successfully deleted!', 'woocommerce-jetpack'), $_GET['wcj_delete_role'] ) . '</p>';
		}

		echo $this->get_tool_header_html( 'custom_roles' );

		$table_data = array();
		$table_data[] = array( __( 'ID', 'woocommerce-jetpack'), __( 'Name', 'woocommerce-jetpack'), __( 'Actions', 'woocommerce-jetpack'), );
		$existing_roles = wcj_get_user_roles();
		$default_wp_wc_roles = array( 'guest', 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager', );
		foreach ( $existing_roles as $role_key => $role_data ) {
			$delete_html = ( in_array( $role_key, $default_wp_wc_roles ) )
				? ''
				: '<a href="' . add_query_arg( 'wcj_delete_role', $role_key ). '">' . __( 'Delete', 'woocommerce-jetpack') . '</a>';
			$table_data[] = array( $role_key, $role_data['name'], $delete_html );
		}
		echo '<h3>' . __( 'Existing Roles', 'woocommerce-jetpack') . '</h3>';
		echo wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) );

		$table_data = array();
		$table_data[] = array( __( 'ID', 'woocommerce-jetpack'),   '<input type="text" name="wcj_custom_role_id">' );
		$table_data[] = array( __( 'Name', 'woocommerce-jetpack'), '<input type="text" name="wcj_custom_role_name">' );
		echo '<h3>' . __( 'Add New Role', 'woocommerce-jetpack') . '</h3>';
		echo '<form method="post" action="' . remove_query_arg( 'wcj_delete_role' ) . '">' .
			wcj_get_table_html( $table_data, array( 'table_class' => 'widefat', 'table_heading_type' => 'vertical', 'table_style' => 'width:20%;min-width:300px;', ) )
			. '<p>' . '<input type="submit" name="wcj_add_new_role" class="button-primary" value="' . __( 'Add', 'woocommerce-jetpack' ) . '">' . '</p>'
			. '</form>';
	}

	/**
	 * maybe_change_paypal_email.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function maybe_change_paypal_email( $load_gateways ) {
		if ( isset( WC()->cart ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				if ( '' != ( $email = get_post_meta( $values['product_id'], '_' . 'wcj_paypal_per_product_email', true ) ) ) {
					foreach ( $load_gateways as $key => $gateway ) {
						if ( is_string( $gateway ) && 'WC_Gateway_Paypal' === $gateway ) {
							$load_gateway = new $gateway();
							$load_gateway->receiver_email = $load_gateway->email = $load_gateway->settings['receiver_email'] = $load_gateway->settings['email'] = $email;
							$load_gateways[ $key ] = $load_gateway;
						}
					}
					break;
				}
			}
		}
		return $load_gateways;
	}

	/**
	 * enable_product_revisions.
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function enable_product_revisions( $args ) {
		$args['supports'][] = 'revisions';
		return $args;
	}

	/**
	 * create_products_atts_tool.
	 *
	 * @version 2.3.9
	 * @since   2.3.9
	 */
	function create_products_atts_tool() {
		$html = '';
		$html .= $this->get_products_atts();
		echo $html;
	}

	/*
	 * get_products_atts.
	 *
	 * @version 2.7.0
	 * @since   2.3.9
	 */
	function get_products_atts() {

		$total_products = 0;

		$products_attributes = array();
		$attributes_names = array();
		$attributes_names['wcj_title']    = __( 'Product', 'woocommerce-jetpack' );
		$attributes_names['wcj_category'] = __( 'Category', 'woocommerce-jetpack' );

		$offset = 0;
		$block_size = 96;
		while( true ) {

			$args_products = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $block_size,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'offset'         => $offset,
			);
			$loop_products = new WP_Query( $args_products );
			if ( ! $loop_products->have_posts() ) break;
			while ( $loop_products->have_posts() ) : $loop_products->the_post();

				$total_products++;
				$product_id = $loop_products->post->ID;
				$the_product = wc_get_product( $product_id );

				$products_attributes[ $product_id ]['wcj_title']    = '<a href="' . get_permalink( $product_id ) . '">' . $the_product->get_title() . '</a>';
				$products_attributes[ $product_id ]['wcj_category'] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $the_product->get_categories() : wc_get_product_category_list( $product_id ) );

				foreach ( $the_product->get_attributes() as $attribute ) {
					$products_attributes[ $product_id ][ $attribute['name'] ] = $the_product->get_attribute( $attribute['name'] );
					if ( ! isset( $attributes_names[ $attribute['name'] ] ) ) {
						$attributes_names[ $attribute['name'] ] = wc_attribute_label( $attribute['name'] );
					}
				}

			endwhile;

			$offset += $block_size;

		}

		$table_data = array();
		if ( isset( $_GET['wcj_attribute'] ) && '' != $_GET['wcj_attribute'] ) {
			$table_data[] = array(
				__( 'Product', 'woocommerce-jetpack' ),
				__( 'Category', 'woocommerce-jetpack' ),
				$_GET['wcj_attribute'],
			);
		} else {
//			$table_data[] = array_values( $attributes_names );
			$table_data[] = array_keys( $attributes_names );
		}
		foreach ( $attributes_names as $attributes_name => $attribute_title ) {

			if ( isset( $_GET['wcj_attribute'] ) && '' != $_GET['wcj_attribute'] ) {
				if ( 'wcj_title' != $attributes_name && 'wcj_category' != $attributes_name && $_GET['wcj_attribute'] != $attributes_name ) {
					continue;
				}
			}

			foreach ( $products_attributes as $product_id => $product_attributes ) {
				$table_data[ $product_id ][ $attributes_name ] = isset( $product_attributes[ $attributes_name ] ) ? $product_attributes[ $attributes_name ] : '';
			}
		}

		return '<p>' . __( 'Total Products:', 'woocommerce-jetpack' ) . ' ' . $total_products . '</p>' . wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) );
	}

}

endif;

return new WCJ_General();

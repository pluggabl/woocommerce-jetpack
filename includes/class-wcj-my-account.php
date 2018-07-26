<?php
/**
 * Booster for WooCommerce - Module - My Account
 *
 * @version 3.8.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_My_Account' ) ) :

class WCJ_My_Account extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.8.0
	 * @since   2.9.0
	 */
	function __construct() {

		$this->id         = 'my_account';
		$this->short_desc = __( 'My Account', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce "My Account" page customization.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-my-account';
		parent::__construct();

		$this->account_menu_items = array(
			'dashboard'       => __( 'Dashboard', 'woocommerce' ),
			'orders'          => __( 'Orders', 'woocommerce' ),
			'downloads'       => __( 'Downloads', 'woocommerce' ),
			'edit-address'    => __( 'Addresses', 'woocommerce' ),
			'payment-methods' => __( 'Payment methods', 'woocommerce' ),
			'edit-account'    => __( 'Account details', 'woocommerce' ),
			'customer-logout' => __( 'Logout', 'woocommerce' ),
		);
		$this->account_menu_endpoints = array(
			'orders'          => __( 'Orders', 'woocommerce' ),
			'view-order'      => __( 'View order', 'woocommerce' ),
			'downloads'       => __( 'Downloads', 'woocommerce' ),
			'edit-account'    => __( 'Edit account', 'woocommerce' ) . ' (' . __( 'Account details', 'woocommerce' ) . ')',
			'edit-address'    => __( 'Addresses', 'woocommerce' ),
			'payment-methods' => __( 'Payment methods', 'woocommerce' ),
			'lost-password'   => __( 'Lost password', 'woocommerce' ),
			'customer-logout' => __( 'Logout', 'woocommerce' ),
		);
		$this->menu_order_default = implode( PHP_EOL, array_keys( $this->account_menu_items ) );

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'maybe_add_my_account_order_actions' ), 10, 2 );
			add_action( 'wp_footer',                                array( $this, 'maybe_add_js_conformation' ) );
			add_action( 'init',                                     array( $this, 'process_woocommerce_mark_order_status' ) );
			// Custom info
			if ( 'yes' === get_option( 'wcj_my_account_custom_info_enabled', 'no' ) ) {
				$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_my_account_custom_info_total_number', 1 ) );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					add_action(
						get_option( 'wcj_my_account_custom_info_hook_' . $i, 'woocommerce_account_dashboard' ),
						array( $this, 'add_my_account_custom_info' ),
						get_option( 'wcj_my_account_custom_info_priority_' . $i, 10 )
					);
				}
			}
			// Registration extra fields
			if ( 'yes' === get_option( 'wcj_my_account_registration_extra_fields_user_role_enabled', 'no' ) ) {
				add_action( 'woocommerce_register_form',    array( $this, 'add_registration_extra_fields' ), PHP_INT_MAX );
				add_action( 'woocommerce_created_customer', array( $this, 'process_registration_extra_fields' ), PHP_INT_MAX, 3 );
			}
			// Menu & Endpoints
			if ( 'yes' === get_option( 'wcj_my_account_menu_customize_enabled', 'no' ) ) {
				foreach ( $this->account_menu_endpoints as $account_menu_endpoint_id => $account_menu_endpoint_title ) {
					add_filter( 'woocommerce_endpoint_' . $account_menu_endpoint_id . '_title', array( $this, 'customize_endpoint_title' ), PHP_INT_MAX, 2 );
				}
				add_filter( 'woocommerce_account_menu_items', array( $this, 'customize_menu' ), PHP_INT_MAX );
				if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_my_account_menu_order_custom_items_enabled', 'no' ) ) ) {
					add_filter( 'woocommerce_get_endpoint_url', array( $this, 'customize_menu_custom_endpoints' ), PHP_INT_MAX, 4 );
				}
			}
			// Dashboard customization
			if ( 'yes' === get_option( 'wcj_my_account_custom_dashboard_enabled', 'no' ) ) {
				add_action( 'woocommerce_account_' . 'page' . '_endpoint', array( $this, 'customize_dashboard' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * customize_menu_custom_endpoints.
	 *
	 * @version 3.8.0
	 * @since   3.8.0
	 */
	function customize_menu_custom_endpoints( $url, $endpoint, $value, $permalink ) {
		$custom_items = array_map( 'trim', explode( PHP_EOL, get_option( 'wcj_my_account_menu_order_custom_items', '' ) ) );
		foreach ( $custom_items as $custom_item ) {
			$parts = array_map( 'trim', explode( '|', $custom_item, 3 ) );
			if ( 3 === count( $parts ) && $parts[0] === $endpoint ) {
				return $parts[2];
			}
		}
		return $url;
	}

	/**
	 * customize_dashboard.
	 *
	 * @version 3.8.0
	 * @since   3.8.0
	 * @see     woocommerce/templates/myaccount/dashboard.php
	 */
	function customize_dashboard( $value ) {

		$current_user = get_user_by( 'id', get_current_user_id() );

		if ( '' != ( $custom_content = get_option( 'wcj_my_account_custom_dashboard_content', '' ) ) ) {
			echo do_shortcode( $custom_content );
		}

		if ( 'no' === get_option( 'wcj_my_account_custom_dashboard_hide_hello', 'no' ) ) {
			echo '<p>';
			/* translators: 1: user display name 2: logout url */
			printf(
				__( 'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'woocommerce' ),
				'<strong>' . esc_html( $current_user->display_name ) . '</strong>',
				esc_url( wc_logout_url( wc_get_page_permalink( 'myaccount' ) ) )
			);
			echo '</p>';
		}

		if ( 'no' === get_option( 'wcj_my_account_custom_dashboard_hide_info', 'no' ) ) {
			echo '<p>';
			printf(
				__( 'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">shipping and billing addresses</a>, and <a href="%3$s">edit your password and account details</a>.', 'woocommerce' ),
				esc_url( wc_get_endpoint_url( 'orders' ) ),
				esc_url( wc_get_endpoint_url( 'edit-address' ) ),
				esc_url( wc_get_endpoint_url( 'edit-account' ) )
			);
			echo '</p>';
		}

		/**
		 * My Account dashboard.
		 *
		 * @since 2.6.0
		 */
		do_action( 'woocommerce_account_dashboard' );

		/**
		 * Deprecated woocommerce_before_my_account action.
		 *
		 * @deprecated 2.6.0
		 */
		do_action( 'woocommerce_before_my_account' );

		/**
		 * Deprecated woocommerce_after_my_account action.
		 *
		 * @deprecated 2.6.0
		 */
		do_action( 'woocommerce_after_my_account' );

	}

	/**
	 * customize_endpoint_title.
	 *
	 * @version 3.8.0
	 * @since   3.8.0
	 * @todo    (maybe) 'orders': `if ( ! empty( $wp->query_vars['orders'] ) ) { $title = sprintf( __( 'Orders (page %d)', 'woocommerce' ), intval( $wp->query_vars['orders'] ) ); }`
	 * @todo    (maybe) 'view-order': `$title = ( $order = wc_get_order( $wp->query_vars['view-order'] ) ) ? sprintf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ) : '';`
	 * @todo    (maybe) 'order-pay'      => __( 'Pay for order', 'woocommerce' )
	 * @todo    (maybe) 'order-received' => __( 'Order received', 'woocommerce' )
	 */
	function customize_endpoint_title( $title, $endpoint ) {
		$menu_titles = get_option( 'wcj_my_account_menu_title', array() );
		if ( ! empty( $menu_titles[ $endpoint ] ) ) {
			return $menu_titles[ $endpoint ];
		}
		return $title;
	}

	/**
	 * customize_menu.
	 *
	 * @version 3.8.0
	 * @since   3.8.0
	 * @todo    (maybe) option to disable menu
	 */
	function customize_menu( $items ) {
		$menu_titles = get_option( 'wcj_my_account_menu_title', array() );
		foreach ( $items as $item_id => $item_title ) {
			if ( ! empty( $menu_titles[ $item_id ] ) ) {
				$items[ $item_id ] = $menu_titles[ $item_id ];
			}
		}
		if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_my_account_menu_order_customize_enabled', 'no' ) ) ) {
			$menu_order    = array_map( 'trim', explode( PHP_EOL, get_option( 'wcj_my_account_menu_order', $this->menu_order_default ) ) );
			$modified_menu = array();
			foreach ( $menu_order as $item_id ) {
				if ( isset( $items[ $item_id ] ) ) {
					$modified_menu[ $item_id ] = $items[ $item_id ];
					unset( $items[ $item_id ] );
				}
			}
			$items = array_merge( $modified_menu, $items );
		}
		if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_my_account_menu_order_custom_items_enabled', 'no' ) ) ) {
			$custom_items = array_map( 'trim', explode( PHP_EOL, get_option( 'wcj_my_account_menu_order_custom_items', '' ) ) );
			foreach ( $custom_items as $custom_item ) {
				$parts = array_map( 'trim', explode( '|', $custom_item, 3 ) );
				if ( 3 === count( $parts ) ) {
					$items[ $parts[0] ] = $parts[1];
				}
			}
		}
		return $items;
	}

	/**
	 * add_registration_extra_fields.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    (maybe) more fields to choose from (i.e. not only "user role" field)
	 * @todo    (maybe) customizable position (check for other hooks or at least customizable priority on `woocommerce_register_form`)
	 * @todo    (maybe) move to new module (e.g. "Registration Form")
	 */
	function add_registration_extra_fields() {
		$user_roles_options_html = '';
		$current_user_role_input = ! empty( $_POST['wcj_user_role'] ) ? $_POST['wcj_user_role'] :
			get_option( 'wcj_my_account_registration_extra_fields_user_role_default', 'customer' );
		$user_roles_options      = get_option( 'wcj_my_account_registration_extra_fields_user_role_options', array( 'customer' ) );
		$all_user_roles          = wcj_get_user_roles_options();
		foreach ( $user_roles_options as $user_role_id ) {
			$user_roles_options_html .= '<option value="' . $user_role_id . '" ' . selected( $user_role_id, $current_user_role_input, false ) . '>' .
				( isset( $all_user_roles[ $user_role_id ] ) ? $all_user_roles[ $user_role_id ] : $user_role_id ) . '</option>';
		}
		?><p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="reg_wcj_user_role"><?php esc_html_e( 'User role', 'woocommerce-jetpack' ); ?></label>
			<select name="wcj_user_role" id="reg_wcj_user_role"><?php echo $user_roles_options_html; ?></select>
		</p><?php
	}

	/**
	 * process_registration_extra_fields.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    (maybe) optional admin confirmation for some user roles (probably will need to create additional `...-pending` user roles)
	 */
	function process_registration_extra_fields( $customer_id, $new_customer_data, $password_generated ) {
		if ( isset( $_POST['wcj_user_role'] ) && '' != $_POST['wcj_user_role'] ) {
			$user_roles_options = get_option( 'wcj_my_account_registration_extra_fields_user_role_options', array( 'customer' ) );
			if ( ! empty( $user_roles_options ) && in_array( $_POST['wcj_user_role'], $user_roles_options ) ) {
				wp_update_user( array( 'ID' => $customer_id, 'role' => $_POST['wcj_user_role'] ) );
			}
		}
	}

	/**
	 * add_my_account_custom_info.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 */
	function add_my_account_custom_info() {
		$current_filter          = current_filter();
		$current_filter_priority = wcj_current_filter_priority();
		$total_number            = apply_filters( 'booster_option', 1, get_option( 'wcj_my_account_custom_info_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if (
				''                       != get_option( 'wcj_my_account_custom_info_content_'  . $i ) &&
				$current_filter         === get_option( 'wcj_my_account_custom_info_hook_'     . $i, 'woocommerce_account_dashboard' ) &&
				$current_filter_priority == get_option( 'wcj_my_account_custom_info_priority_' . $i, 10 )
			) {
				echo do_shortcode( get_option( 'wcj_my_account_custom_info_content_' . $i ) );
			}
		}
	}

	/*
	 * maybe_add_my_account_order_actions.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @see     http://snippet.fm/snippets/add-order-complete-action-to-woocommerce-my-orders-customer-table/
	 */
	function maybe_add_my_account_order_actions( $actions, $order ) {
		$statuses_to_add = get_option( 'wcj_my_account_add_order_status_actions', '' );
		if ( ! empty( $statuses_to_add ) ) {
			$all_statuses = wcj_get_order_statuses();
			foreach ( $statuses_to_add as $status_to_add ) {
				if ( $status_to_add != $order->get_status() ) {
					$actions[ 'wcj_mark_' . $status_to_add . '_by_customer' ] = array(
						'url'  => wp_nonce_url( add_query_arg( array(
							'wcj_action' => 'wcj_woocommerce_mark_order_status',
							'status'     => $status_to_add,
							'order_id'   => $order->get_id() ) ), 'wcj-woocommerce-mark-order-status' ),
						'name' => $all_statuses[ $status_to_add ],
					);
				}
			}
		}
		return $actions;
	}

	/*
	 * maybe_add_js_conformation.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function maybe_add_js_conformation() {
		$statuses_to_add = get_option( 'wcj_my_account_add_order_status_actions', '' );
		if ( ! empty( $statuses_to_add ) ) {
			echo '<script>';
			foreach ( $statuses_to_add as $status_to_add ) {
				echo 'jQuery("a.wcj_mark_' . $status_to_add . '_by_customer").each( function() { jQuery(this).attr("onclick", "return confirm(\'' .
					__( 'Are you sure?', 'woocommerce-jetpack' ) . '\')") } );';
			}
			echo '</script>';
		}
	}

	/*
	 * process_woocommerce_mark_order_status.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function process_woocommerce_mark_order_status() {
		if (
			isset( $_GET['wcj_action'] ) && 'wcj_woocommerce_mark_order_status' === $_GET['wcj_action'] &&
			isset( $_GET['status'] ) &&
			isset( $_GET['order_id'] ) &&
			isset( $_GET['_wpnonce'] )
		) {
			if ( wp_verify_nonce( $_GET['_wpnonce'], 'wcj-woocommerce-mark-order-status' ) ) {
				$_order = wc_get_order( $_GET['order_id'] );
				if ( $_order->get_customer_id() === get_current_user_id() ) {
					$_order->update_status( $_GET['status'] );
					wp_safe_redirect( remove_query_arg( array( 'wcj_action', 'status', 'order_id', '_wpnonce' ) ) );
					exit;
				}
			}
		}
	}

}

endif;

return new WCJ_My_Account();

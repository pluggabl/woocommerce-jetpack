<?php
/**
 * Booster for WooCommerce - Module - My Account
 *
 * @version 3.6.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_My_Account' ) ) :

class WCJ_My_Account extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.6.0
	 * @since   2.9.0
	 */
	function __construct() {

		$this->id         = 'my_account';
		$this->short_desc = __( 'My Account', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce "My Account" page customization.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-my-account';
		parent::__construct();

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
		}
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

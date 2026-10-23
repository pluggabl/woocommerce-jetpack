<?php
/**
 * Booster for WooCommerce - Module - Gateways by User Role
 *
 * @version 4.7.1
 * @since   2.5.3
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Payment_Gateways_By_User_Role' ) ) :
	/**
	 * WCJ_Payment_Gateways_By_User_Role.
	 */
	class WCJ_Payment_Gateways_By_User_Role extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 3.2.2
		 * @since   2.5.3
		 */
		public function __construct() {
			$this->id         = 'payment_gateways_by_user_role';
			$this->short_desc = 'Gateways by User Role';
			wcj_init_presentation(
				function () {
					$this->short_desc = __( 'Gateways by User Role', 'woocommerce-jetpack' );
				}
			);
			$this->desc = 'Set user roles to include/exclude for payment gateways to show up.';
			wcj_init_presentation(
				function () {
					$this->desc = __( 'Set user roles to include/exclude for payment gateways to show up.', 'woocommerce-jetpack' );
				}
			);
			$this->link_slug = 'woocommerce-payment-gateways-by-user-role';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX - 100, 1 );
			}
		}

		/**
		 * Available_payment_gateways.
		 *
		 * @version 4.7.1
		 * @since   2.5.3
		 * @param array $_available_gateways defines the _available_gateways.
		 */
		public function available_payment_gateways( $_available_gateways ) {
			$ticket = WCJ_Rule_Explanations::payment_start( $this, $_available_gateways );
			$row = null;
			$whole = false;
			$result = $this->payment_filter_body( $_available_gateways, $ticket, $row, $whole );
			if ( null !== $ticket ) { WCJ_Rule_Explanations::payment_finish( $this, $ticket, $row, $whole ); }
			return $result;
		}

		/** Original lazy collector, shared with bounded per-gateway decisions. */
		private function payment_filter_body( $_available_gateways, $ticket, &$row, &$whole ) {
			$multi_role_check = wcj_get_option( 'wcj_payment_gateways_by_user_role_multi_role_check', 'no' );
			$customer_roles   = 'no' === ( $multi_role_check ) ? array( wcj_get_current_user_first_role() ) : wcj_get_current_user_all_roles();
			foreach ( $_available_gateways as $key => $gateway ) {
				$decision = $this->payment_decision( $key, $customer_roles );
				if ( $ticket === (string) $key ) { $row = $decision; }
				if ( 'removed' === $decision[0] ) { unset( $_available_gateways[ $key ] ); }
			} 
			return $_available_gateways;
		
		}

		/** Shared original include/exclude short-circuit decision. */
		private function payment_decision( $key, $customer_roles ) {
				$include_roles = wcj_get_option( 'wcj_gateways_user_roles_include_' . $key, '' );
				if ( ! empty( $include_roles ) && ! array_intersect( $customer_roles, $include_roles ) ) {
					return array( 'removed', 'include_miss', 'role' );
				}
				$exclude_roles = wcj_get_option( 'wcj_gateways_user_roles_exclude_' . $key, '' );
				if ( ! empty( $exclude_roles ) && array_intersect( $customer_roles, $exclude_roles ) ) {
					return array( 'removed', 'exclude_match', 'role' );
				}
			
			return array( empty( $include_roles ) && empty( $exclude_roles ) ? 'not_configured' : 'retained', empty( $include_roles ) && empty( $exclude_roles ) ? 'no_rules' : 'rules_pass', 'role' );
		}

		public function explain_payment_gateway( $key, $facts ) {
			if ( wcj_is_module_enabled( 'general' ) && 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_general_user_role_changer_enabled', 'no' ) ) ) { return array( 'unsupported', 'dynamic_role_policy', 'role' ); }
			$multi_role_check = wcj_get_option( 'wcj_payment_gateways_by_user_role_multi_role_check', 'no' );
			if ( 'unknown' === $facts['roles']['state'] ) { return array( 'not_checked', 'missing_fact', 'role' ); }
			$roles = $facts['roles']['value'];
			return $this->payment_decision( $key, 'no' === $multi_role_check ? array( $roles[0] ) : $roles );
		}

	}

endif;

return new WCJ_Payment_Gateways_By_User_Role();

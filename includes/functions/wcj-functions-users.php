<?php
/**
 * Booster for WooCommerce - Functions - Users
 *
 * @version 3.2.2
 * @since   2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_get_users_as_options' ) ) {
	/**
	 * wcj_get_users_as_options.
	 *
	 * @version 3.2.1
	 * @since   2.9.0
	 */
	function wcj_get_users_as_options() {
		$users = array();
		foreach ( get_users( 'orderby=display_name' ) as $user ) {
			$users[ $user->ID ] = $user->display_name . ' ' . '[ID:' . $user->ID . ']';
		}
		return $users;
	}
}

if ( ! function_exists( 'is_shop_manager' ) ) {
	/**
	 * is_shop_manager.
	 *
	 * @version 2.9.0
	 * @return  bool
	 */
	function is_shop_manager( $user_id = 0 ) {
		$the_user = ( 0 == $user_id ) ? wp_get_current_user() : get_user_by( 'id', $user_id );
		return ( isset( $the_user->roles[0] ) && 'shop_manager' === $the_user->roles[0] );
	}
}

if ( ! function_exists( 'wcj_get_current_user_all_roles' ) ) {
	/**
	 * wcj_get_current_user_all_roles.
	 *
	 * @version 2.5.6
	 * @since   2.5.6
	 */
	function wcj_get_current_user_all_roles() {
		$current_user = wp_get_current_user();
		return ( ! empty( $current_user->roles ) ) ? $current_user->roles : array( 'guest' );
	}
}

if ( ! function_exists( 'wcj_is_user_logged_in' ) ) {
	/**
	 * wcj_is_user_logged_in.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function wcj_is_user_logged_in() {
		if ( ! function_exists( 'is_user_logged_in' ) ) {
			require_once( ABSPATH . 'wp-includes/pluggable.php' );
		}
		return is_user_logged_in();
	}
}

if ( ! function_exists( 'wcj_is_booster_role_changer_enabled' ) ) {
	/**
	 * wcj_is_booster_role_changer_enabled.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function wcj_is_booster_role_changer_enabled() {
		return (
			'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_general_user_role_changer_enabled', 'no' ) ) &&
			wcj_is_user_logged_in() &&
			wcj_is_user_role( get_option( 'wcj_general_user_role_changer_enabled_for', array( 'administrator', 'shop_manager' ) ) )
		);
	}
}

if ( ! function_exists( 'wcj_get_current_user_first_role' ) ) {
	/**
	 * wcj_get_current_user_first_role.
	 *
	 * @version 3.2.2
	 * @since   2.5.3
	 */
	function wcj_get_current_user_first_role() {
		if ( wcj_is_module_enabled( 'general' ) && wcj_is_booster_role_changer_enabled() ) {
			$current_user_id = get_current_user_id();
			if ( '' != ( $role_by_meta = get_user_meta( $current_user_id, '_' . 'wcj_booster_user_role', true ) ) ) {
				return $role_by_meta;
			}
		}
		$current_user = wp_get_current_user();
		$first_role   = ( isset( $current_user->roles ) && is_array( $current_user->roles ) && ! empty( $current_user->roles ) ? reset( $current_user->roles ) : 'guest' );
		return ( '' != $first_role ? $first_role : 'guest' );
	}
}

if ( ! function_exists( 'wcj_get_user_roles' ) ) {
	/**
	 * wcj_get_user_roles.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function wcj_get_user_roles() {
		global $wp_roles;
		$all_roles = ( isset( $wp_roles ) && is_object( $wp_roles ) ) ? $wp_roles->roles : array();
		$all_roles = apply_filters( 'editable_roles', $all_roles );
		$all_roles = array_merge( array(
			'guest' => array(
				'name'         => __( 'Guest', 'woocommerce-jetpack' ),
				'capabilities' => array(),
			) ), $all_roles );
		return $all_roles;
	}
}

if ( ! function_exists( 'wcj_get_user_roles_options' ) ) {
	/**
	 * wcj_get_user_roles_options.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function wcj_get_user_roles_options() {
		global $wp_roles;
		$all_roles = ( isset( $wp_roles ) && is_object( $wp_roles ) ) ? $wp_roles->roles : array();
		$all_roles = apply_filters( 'editable_roles', $all_roles );
		$all_roles = array_merge( array(
			'guest' => array(
				'name'         => __( 'Guest', 'woocommerce-jetpack' ),
				'capabilities' => array(),
			) ), $all_roles );
		$all_roles_options = array();
		foreach ( $all_roles as $_role_key => $_role ) {
			$all_roles_options[ $_role_key ] = $_role['name'];
		}
		return $all_roles_options;
	}
}

if ( ! function_exists( 'wcj_is_user_role' ) ) {
	/**
	 * wcj_is_user_role.
	 *
	 * @version 3.1.3
	 * @since   2.5.0
	 * @return  bool
	 */
	function wcj_is_user_role( $user_role, $user_id = 0 ) {
		$_user = ( 0 == $user_id ? wp_get_current_user() : get_user_by( 'id', $user_id ) );
		if ( ! isset( $_user->roles ) || empty( $_user->roles ) ) {
			$_user->roles = array( 'guest' );
		}
		if ( ! is_array( $_user->roles ) ) {
			return false;
		}
		if ( is_array( $user_role ) ) {
			if ( in_array( 'administrator', $user_role ) ) {
				$user_role[] = 'super_admin';
			}
			$_intersect = array_intersect( $user_role, $_user->roles );
			return ( ! empty( $_intersect ) );
		} else {
			if ( 'administrator' == $user_role ) {
				return ( in_array( 'administrator', $_user->roles ) || in_array( 'super_admin', $_user->roles ) );
			} else {
				return ( in_array( $user_role, $_user->roles ) );
			}
		}
		/* if ( ! is_array( $user_role ) ) {
			$user_role = array( $user_role );
		}
		if ( in_array( 'administrator', $user_role ) ) {
			$user_role[] = 'super_admin';
		}
		$_intersect = array_intersect( $user_role, $_user->roles );
		return ( ! empty( $_intersect ) ); */
	}
}

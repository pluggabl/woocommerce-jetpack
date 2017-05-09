<?php
/**
 * Booster for WooCommerce - Functions - User Roles
 *
 * @version 2.7.0
 * @since   2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'is_shop_manager' ) ) {
	/**
	 * is_shop_manager.
	 *
	 * @return bool
	 */
	function is_shop_manager( $user_id = 0 ) {
		$the_user = ( 0 == $user_id ) ? wp_get_current_user() : get_user_by( 'id', $user_id );
//		if ( isset( $the_user['roles'][0] ) && 'shop_manager' === $the_user['roles'][0] ) {
		return ( isset( $the_user->roles[0] ) && 'shop_manager' === $the_user->roles[0] ) ? true : false;
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

if ( ! function_exists( 'wcj_get_current_user_first_role' ) ) {
	/**
	 * wcj_get_current_user_first_role.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function wcj_get_current_user_first_role() {
		$current_user = wp_get_current_user();
		return ( isset( $current_user->roles[0] ) && '' != $current_user->roles[0] ) ? $current_user->roles[0] : 'guest';
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
	 * @version 2.5.2
	 * @since   2.5.0
	 * @return  bool
	 */
	function wcj_is_user_role( $user_role, $user_id = 0 ) {
		$the_user = ( 0 == $user_id ) ? wp_get_current_user() : get_user_by( 'id', $user_id );
		if ( ! isset( $the_user->roles ) || empty( $the_user->roles ) ) {
			$the_user->roles = array( 'guest' );
		}
		return ( isset( $the_user->roles ) && is_array( $the_user->roles ) && in_array( $user_role, $the_user->roles ) ) ? true : false;
	}
}
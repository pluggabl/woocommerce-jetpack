<?php
/**
 * Booster for WooCommerce - Functions - Users
 *
 * @version 7.2.4
 * @since   2.7.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_current_user_can' ) ) {
	/**
	 * Wcj_current_user_can.
	 *
	 * @version 3.9.0
	 * @since   3.9.0
	 * @param Array $capability user capability.
	 */
	function wcj_current_user_can( $capability ) {
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			require_once ABSPATH . 'wp-includes/pluggable.php';
		}
		return current_user_can( $capability );
	}
}

if ( ! function_exists( 'wcj_get_current_user_id' ) ) {
	/**
	 * Wcj_get_current_user_id.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function wcj_get_current_user_id() {
		if ( ! function_exists( 'get_current_user_id' ) ) {
			require_once ABSPATH . 'wp-includes/pluggable.php';
		}
		return get_current_user_id();
	}
}

if ( ! function_exists( 'wcj_get_users_as_options' ) ) {
	/**
	 * Wcj_get_users_as_options.
	 *
	 * @version 3.2.1
	 * @since   2.9.0
	 */
	function wcj_get_users_as_options() {
		$users = array();
		foreach ( get_users( 'orderby=display_name' ) as $user ) {
			$users[ $user->ID ] = $user->display_name . '[ID:' . $user->ID . ']';
		}
		return $users;
	}
}

if ( ! function_exists( 'is_shop_manager' ) ) {
	/**
	 * Is_shop_manager.
	 *
	 * @version 2.9.0
	 * @return  bool
	 * @param int $user_id Get user id.
	 */
	function is_shop_manager( $user_id = 0 ) {
		$the_user = ( 0 === $user_id ) ? wp_get_current_user() : get_user_by( 'id', $user_id );
		return ( isset( $the_user->roles[0] ) && 'shop_manager' === $the_user->roles[0] );
	}
}

if ( ! function_exists( 'wcj_get_current_user_all_roles' ) ) {
	/**
	 * Wcj_get_current_user_all_roles.
	 *
	 * @version 3.4.0
	 * @since   2.5.6
	 */
	function wcj_get_current_user_all_roles() {
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			require_once ABSPATH . 'wp-includes/pluggable.php';
		}
		$current_user = wp_get_current_user();
		return ( ! empty( $current_user->roles ) ) ? $current_user->roles : array( 'guest' );
	}
}

if ( ! function_exists( 'wcj_is_user_logged_in' ) ) {
	/**
	 * Wcj_is_user_logged_in.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function wcj_is_user_logged_in() {
		if ( ! function_exists( 'is_user_logged_in' ) ) {
			require_once ABSPATH . 'wp-includes/pluggable.php';
		}
		return is_user_logged_in();
	}
}

if ( ! function_exists( 'wcj_is_booster_role_changer_enabled' ) ) {
	/**
	 * Wcj_is_booster_role_changer_enabled.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function wcj_is_booster_role_changer_enabled() {
		return (
			'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_general_user_role_changer_enabled', 'no' ) ) &&
			wcj_is_user_logged_in() &&
			wcj_is_user_role( wcj_get_option( 'wcj_general_user_role_changer_enabled_for', array( 'administrator', 'shop_manager' ) ) )
		);
	}
}

if ( ! function_exists( 'wcj_get_current_user_first_role' ) ) {
	/**
	 * Wcj_get_current_user_first_role.
	 *
	 * @version 7.2.4
	 * @since  1.0.0
	 */
	function wcj_get_current_user_first_role() {

		if ( is_admin() && 'yes' === wcj_get_option( 'wcj_price_by_user_role_admin_order', 'no' ) ) {

			if ( isset( WC()->session ) ) {
				$current_user_id = WC()->session->get( 'wcj_order_user_id' );
				$user_info       = get_userdata( $current_user_id );
				$role_by_meta    = $user_info->roles;

				return ( '' !== $role_by_meta ? $role_by_meta[0] : 'guest' );
			}
		}

		if ( wcj_is_module_enabled( 'general' ) && wcj_is_booster_role_changer_enabled() ) {
			$current_user_id = get_current_user_id();
			$role_by_meta    = get_user_meta( $current_user_id, '_wcj_booster_user_role', true );
			if ( '' !== ( $role_by_meta ) ) {
				return $role_by_meta;
			}
		}
		$current_user = wp_get_current_user();
		$first_role   = ( isset( $current_user->roles ) && is_array( $current_user->roles ) && ! empty( $current_user->roles ) ? reset( $current_user->roles ) : 'guest' );
		return ( '' !== $first_role ? $first_role : 'guest' );
	}
}

if ( ! function_exists( 'wcj_get_user_roles' ) ) {
	/**
	 * Wcj_get_user_roles.
	 *
	 * @version 4.9.0
	 * @since   2.5.3
	 * @param null $args define args.
	 */
	function wcj_get_user_roles( $args = null ) {
		global $wp_roles;
		$args               = wp_parse_args(
			$args,
			array(
				'skip_editable_roles_filter' => false,
			)
		);
		$all_roles          = ( isset( $wp_roles ) && is_object( $wp_roles ) ) ? $wp_roles->roles : array();
		$current_user_roles = array();
		if ( is_user_logged_in() ) {
			$user               = wp_get_current_user();
			$roles              = (array) $user->roles;
			$current_user_roles = array_filter(
				$all_roles,
				function ( $k ) use ( $roles ) {
					return in_array( $k, $roles, true );
				},
				ARRAY_FILTER_USE_KEY
			);
		}
		if ( ! $args['skip_editable_roles_filter'] ) {
			$all_roles = apply_filters( 'editable_roles', $all_roles );
		}
		$all_roles = array_merge(
			array(
				'guest' => array(
					'name'         => __( 'Guest', 'woocommerce-jetpack' ),
					'capabilities' => array(),
				),
			),
			$all_roles
		);
		if ( ! empty( $current_user_roles ) ) {
			$all_roles = array_merge( $current_user_roles, $all_roles );
		}
		return $all_roles;
	}
}

if ( ! function_exists( 'wcj_get_user_roles_options' ) ) {
	/**
	 * Wcj_get_user_roles_options.
	 *
	 * @version 4.9.0
	 * @since   2.5.3
	 * @param null $args define args.
	 */
	function wcj_get_user_roles_options( $args = null ) {
		global $wp_roles;
		$args      = wp_parse_args(
			$args,
			array(
				'skip_editable_roles_filter' => false,
			)
		);
		$all_roles = ( isset( $wp_roles ) && is_object( $wp_roles ) ) ? $wp_roles->roles : array();
		if ( ! $args['skip_editable_roles_filter'] ) {
			$all_roles = apply_filters( 'editable_roles', $all_roles );
		}
		$all_roles         = array_merge(
			array(
				'guest' => array(
					'name'         => __( 'Guest', 'woocommerce-jetpack' ),
					'capabilities' => array(),
				),
			),
			$all_roles
		);
		$all_roles_options = array();
		foreach ( $all_roles as $_role_key => $_role ) {
			$all_roles_options[ $_role_key ] = $_role['name'];
		}
		return $all_roles_options;
	}
}

if ( ! function_exists( 'wcj_is_user_role' ) ) {
	/**
	 * Wcj_is_user_role.
	 *
	 * @version 3.7.0
	 * @since   2.5.0
	 * @return  bool
	 * @param string $user_role define userrole.
	 * @param int    $user_id Get user id.
	 */
	function wcj_is_user_role( $user_role, $user_id = 0 ) {
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			include ABSPATH . 'wp-includes/pluggable.php';
		}
		$_user = ( 0 === $user_id ? wp_get_current_user() : get_user_by( 'id', $user_id ) );
		if ( ! isset( $_user->roles ) || empty( $_user->roles ) ) {
			$_user->roles = array( 'guest' );
		}
		if ( ! is_array( $_user->roles ) ) {
			return false;
		}
		if ( is_array( $user_role ) ) {
			if ( in_array( 'administrator', $user_role, true ) ) {
				$user_role[] = 'super_admin';
			}
			$_intersect = array_intersect( $user_role, $_user->roles );
			return ( ! empty( $_intersect ) );
		} else {
			if ( 'administrator' === $user_role ) {
				return ( in_array( 'administrator', $_user->roles, true ) || in_array( 'super_admin', $_user->roles, true ) );
			} else {
				return ( in_array( $user_role, $_user->roles, true ) );
			}
		}
	}
}

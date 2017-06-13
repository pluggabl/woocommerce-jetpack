<?php
/**
 * Booster for WooCommerce Tools
 *
 * @version 2.5.7
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Tools' ) ) :

class WCJ_Tools {

	/**
	 * Constructor.
	 */
	function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_wcj_tools' ), 100 );
		}
	}

	/**
	 * add_wcj_tools.
	 *
	 * @version 2.5.7
	 */
	function add_wcj_tools() {
		add_submenu_page(
			'woocommerce',
			__( 'Booster for WooCommerce Tools', 'woocommerce-jetpack' ),
			__( 'Booster Tools', 'woocommerce-jetpack' ),
			'manage_woocommerce',
			'wcj-tools',
			array( $this, 'create_tools_page' )
		);
	}

	/**
	 * create_tools_page.
	 *
	 * @version 2.3.10
	 */
	function create_tools_page() {

		// Tabs
		$tabs = apply_filters( 'wcj_tools_tabs', array(
			array(
				'id'    => 'dashboard',
				'title' => __( 'Tools Dashboard', 'woocommerce-jetpack' ),
			),
		) );
		$html = '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';
		$active_tab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : 'dashboard';
		foreach ( $tabs as $tab ) {
			$is_active = ( $active_tab === $tab['id'] ) ? 'nav-tab-active' : '';
			$html .= '<a href="' . add_query_arg( array( 'page' => 'wcj-tools', 'tab' => $tab['id'] ), get_admin_url() . 'admin.php' ) . '" class="nav-tab ' . $is_active . '">' . $tab['title'] . '</a>';
		}
		$html .= '</h2>';
		echo $html;

		// Content
		if ( 'dashboard' === $active_tab ) {
			$title = __( 'Booster for WooCommerce Tools - Dashboard', 'woocommerce-jetpack' );
			$desc  = __( 'This dashboard lets you check statuses and short descriptions of all available Booster for WooCommerce tools. Tools can be enabled through WooCommerce > Settings > Booster. Enabled tools will appear in the tabs menu above.', 'woocommerce-jetpack' );
			echo '<h3>' . $title . '</h3>';
			echo '<p>' .  $desc . '</p>';
			echo '<table class="widefat" style="width:90%;">';
			echo '<tr>';
			echo '<th style="width:20%;">' . __( 'Tool', 'woocommerce-jetpack' ) . '</th>';
			echo '<th style="width:20%;">' . __( 'Module', 'woocommerce-jetpack' ) . '</th>';
			echo '<th style="width:50%;">' . __( 'Description', 'woocommerce-jetpack' ) . '</th>';
			echo '<th style="width:10%;">' . __( 'Status', 'woocommerce-jetpack' ) . '</th>';
			echo '</tr>';
			do_action( 'wcj_tools_' . 'dashboard' );
			echo '</table>';
		} else {
			do_action( 'wcj_tools_' . $active_tab );
		}
	}
}

endif;

return new WCJ_Tools();

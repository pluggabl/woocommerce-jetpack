<?php
/**
 * WooCommerce Jetpack Tools
 *
 * The WooCommerce Jetpack Tools class.
 *
 * @version 2.3.10
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Tools' ) ) :

class WCJ_Tools {

	/**
	 * __construct.
	 */
	function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array($this, 'add_wcj_tools'), 100 );
		}
	}

	/**
	 * add_wcj_tools.
	 *
	 * @version 2.2.4
	 */
	function add_wcj_tools() {
		add_submenu_page( 'woocommerce', __( 'Booster for WooCommerce Tools', 'woocommerce-jetpack' ), __( 'Booster Tools', 'woocommerce-jetpack' ), 'manage_options', 'wcj-tools', array( $this, 'create_tools_page' ) );
	}

	/**
	 * create_tools_page.
	 *
	 * @version 2.3.10
	 */
	function create_tools_page() {

		$tabs = array(
			array(
				'id'		=> 'dashboard',
				'title'		=> __( 'Tools Dashboard', 'woocommerce-jetpack' ),
				//'desc'		=> __( 'WooCommerce Jetpack Tools Dashboard', 'woocommerce-jetpack' ),
			),
		);

		$tabs = apply_filters( 'wcj_tools_tabs', $tabs );

		$html = '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';

		$active_tab = 'dashboard';
		if ( isset( $_GET['tab'] ) )
			$active_tab = $_GET['tab'];

		foreach ( $tabs as $tab ) {

			$is_active = '';
			if ( $active_tab === $tab['id'] )
				$is_active = 'nav-tab-active';

			//$html .= '<a href="' . get_admin_url() . 'admin.php?page=wcj-tools&tab=' . $tab['id'] . '" class="nav-tab ' . $is_active . '">' . $tab['title'] . '</a>';
			//$html .= '<a href="' . add_query_arg( 'tab', $tab['id'] ) . '" class="nav-tab ' . $is_active . '">' . $tab['title'] . '</a>';
			$html .= '<a href="admin.php?page=wcj-tools&tab=' . $tab['id'] . '" class="nav-tab ' . $is_active . '">' . $tab['title'] . '</a>';

		}
		$html .= '</h2>';

		echo $html;

		if ( 'dashboard' === $active_tab ) {
			echo '<h3>' . __( 'Booster for WooCommerce Tools - Dashboard', 'woocommerce-jetpack' ) . '</h3>';
			echo '<p>' . __( 'This dashboard lets you check statuses and short descriptions of all available Booster for WooCommerce tools. Tools can be enabled through WooCommerce > Settings > Booster. Enabled tools will appear in the tabs menu above.', 'woocommerce-jetpack' ) . '</p>';
			echo '<table class="widefat" style="width:90%;">';
			echo '<tr>';
			echo '<th style="width:20%;">' . __( 'Tool', 'woocommerce-jetpack' ) . '</th>';
			echo '<th style="width:20%;">' . __( 'Module', 'woocommerce-jetpack' ) . '</th>';
			echo '<th style="width:50%;">' . __( 'Description', 'woocommerce-jetpack' ) . '</th>';
			echo '<th style="width:10%;">' . __( 'Status', 'woocommerce-jetpack' ) . '</th>';
			echo '</tr>';
			do_action( 'wcj_tools_' . $active_tab );
			echo '</table>';
		}
		else
			do_action( 'wcj_tools_' . $active_tab );
	}
}

endif;

return new WCJ_Tools();

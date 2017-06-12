<?php
/**
 * Booster for WooCommerce - Module - Admin Bar
 *
 * @version 2.8.3
 * @since   2.8.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Admin_Bar' ) ) :

class WCJ_Admin_Bar extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @todo    reload page after enabling the module
	 */
	function __construct() {

		$this->id         = 'admin_bar';
		$this->short_desc = __( 'Admin Bar', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce admin bar.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-admin-bar';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'admin_bar_menu', array( $this, 'add_woocommerce_admin_bar' ), PHP_INT_MAX );
			add_action( 'admin_head',     array( $this, 'add_woocommerce_admin_bar_icon_style' ) );
		}
	}

	/**
	 * add_woocommerce_admin_bar_icon_style.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 */
	function add_woocommerce_admin_bar_icon_style() {
		echo '<style type="text/css"> #wpadminbar #wp-admin-bar-wcj-wc .ab-icon:before { font-family: WooCommerce !important; content: \'\e03d\'; top: 1px; } </style>';
	}

	/**
	 * add_woocommerce_admin_bar_nodes.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 */
	function add_woocommerce_admin_bar_nodes( $wp_admin_bar, $nodes, $parent_id ) {
		foreach ( $nodes as $node_id => $node ) {
			$id = ( false !== $parent_id ? $parent_id . '-' . $node_id : $node_id );
			$args = array(
				'parent' => $parent_id,
				'id'     => $id,
				'title'  => $node['title'],
				'href'   => $node['href'],
				'meta'   => array( 'title' => ( isset( $node['meta']['title'] ) ? $node['meta']['title'] : $node['title'] ) ),
			);
			if ( isset( $node['meta']['class'] ) ) {
				$args['meta']['class'] = $node['meta']['class'];
			}
			$wp_admin_bar->add_node( $args );
			if ( isset( $node['nodes'] ) ) {
				// Recursion
				$this->add_woocommerce_admin_bar_nodes( $wp_admin_bar, $node['nodes'], $id );
			}
		}
	}

	/**
	 * get_nodes_booster_modules.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @todo    dashboard
	 * @todo    (maybe) module->desc
	 * @todo    (maybe) module->link
	 */
	function get_nodes_booster_modules() {
		$nodes = array();
		$cats  = include( wcj_plugin_path() . '/includes/admin/' . 'wcj-modules-cats.php' );
		foreach ( $cats as $id => $label_info ) {
			$nodes[ $id ] = array(
				'title'  => $label_info['label'],
				'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=' . $id ),
			);
			if ( 'dashboard' === $id ) {
				continue;
			}
			foreach ( $label_info['all_cat_ids'] as $link_id ) {
				$nodes[ $id ]['nodes'][] = array(
					'title'  => WCJ()->modules[ $link_id ]->short_desc,
					'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=' . $id . '&section=' . $link_id ),
				);
			}
		}
		return $nodes;
	}

	/**
	 * add_woocommerce_admin_bar.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @todo    finish standard WC nodes
	 * @todo    custom user nodes
	 * @todo    list all Booster tools
	 */
	function add_woocommerce_admin_bar( $wp_admin_bar ) {
		$nodes = array(
			'wcj-wc' => array(
				'title'  => '<span class="ab-icon"></span>' . __( 'WooCommerce', 'woocommerce-jetpack' ),
				'href'   => admin_url( 'admin.php?page=wc-settings' ),
				'meta'   => array(
					'title'  => __( 'WooCommerce - Settings', 'woocommerce-jetpack' ),
				),
				'nodes'  => array(
					'orders' => array(
						'title'  => __( 'Orders', 'woocommerce-jetpack' ),
						'href'   => admin_url( 'edit.php?post_type=shop_order' ),
						'nodes'  => array(
							'add-new-order' => array(
								'title'  => __( 'Add new order', 'woocommerce-jetpack' ),
								'href'   => admin_url( 'post-new.php?post_type=shop_order' ),
							),
							'customers' => array(
								'title'  => __( 'Customers', 'woocommerce-jetpack' ),
								'href'   => admin_url( 'users.php?role=customer' ),
							),
						),
					),
					'reports' => array(
						'title'  => __( 'Reports', 'woocommerce-jetpack' ),
						'href'   => admin_url( 'admin.php?page=wc-reports' ),
						'nodes'  => array(
							'orders' => array(
								'title'  => __( 'Orders', 'woocommerce-jetpack' ),
								'href'   => admin_url( 'admin.php?page=wc-reports&tab=orders' ),
								'nodes'  => array(
									'7day' => array(
										'title'  => __( 'Last 7 days', 'woocommerce-jetpack' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=orders&range=7day' ),
									),
								),
							),
						),
					),
					'booster' => array(
						'title'  => __( 'Booster', 'woocommerce-jetpack' ),
						'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack' ),
						'meta'   => array(
							'title'  => __( 'Booster - Settings', 'woocommerce-jetpack' ),
						),
						'nodes'  => array(
							'settings' => array(
								'title'  => __( 'Settings', 'woocommerce-jetpack' ),
								'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack' ),
								'nodes'  => $this->get_nodes_booster_modules(),
							),
							'tools' => array(
								'title'  => __( 'Tools', 'woocommerce-jetpack' ),
								'href'   => admin_url( 'admin.php?page=wcj-tools' ),
							),
						),
					),
				),
			),
		);
		$this->add_woocommerce_admin_bar_nodes( $wp_admin_bar, $nodes, false );
	}

}

endif;

return new WCJ_Admin_Bar();

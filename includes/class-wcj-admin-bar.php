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
	 * @todo    enable/disable WC/Booster menus
	 */
	function __construct() {

		$this->id         = 'admin_bar';
		$this->short_desc = __( 'Admin Bar', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce admin bar.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-admin-bar';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'admin_bar_menu', array( $this, 'add_woocommerce_admin_bar' ), PHP_INT_MAX );
			add_action( 'wp_head',        array( $this, 'add_woocommerce_admin_bar_icon_style' ) );
			add_action( 'admin_head',     array( $this, 'add_woocommerce_admin_bar_icon_style' ) );
			add_action( 'admin_bar_menu', array( $this, 'add_booster_admin_bar' ), PHP_INT_MAX );
			add_action( 'wp_head',        array( $this, 'add_booster_admin_bar_icon_style' ) );
			add_action( 'admin_head',     array( $this, 'add_booster_admin_bar_icon_style' ) );
		}
	}

	/**
	 * add_booster_admin_bar_icon_style.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 */
	function add_booster_admin_bar_icon_style() {
		echo '<style type="text/css"> #wpadminbar #wp-admin-bar-booster .ab-icon:before { content: "\f339"; top: 3px; } </style>';
	}

	/**
	 * add_woocommerce_admin_bar_icon_style.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 */
	function add_woocommerce_admin_bar_icon_style() {
		echo '<style type="text/css"> #wpadminbar #wp-admin-bar-wcj-wc .ab-icon:before { content: "\f174"; top: 3px; } </style>';
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
				'meta'   => array( 'title' => $node['title'] ),
			);
			if ( isset( $node['meta'] ) ) {
				$args['meta'] = array_merge( $args['meta'], $node['meta'] );
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
	 * @todo    (maybe) dashboard > alphabetically - list all modules
	 * @todo    (maybe) dashboard > by_category - list all modules
	 */
	function get_nodes_booster_modules() {
		$nodes = array();
		$cats  = include( wcj_plugin_path() . '/includes/admin/' . 'wcj-modules-cats.php' );
		$active_modules = array();
		foreach ( $cats as $id => $label_info ) {
			$nodes[ $id ] = array(
				'title'  => $label_info['label'],
				'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=' . $id ),
				'meta'   => array( 'title' => strip_tags( $label_info['desc'] ) ),
			);
			if ( 'dashboard' === $id ) {
				$nodes[ $id ]['nodes'] = array(
					'alphabetically' => array(
						'title'  => __( 'Alphabetically', 'woocommerce-jetpack' ),
						'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=dashboard&section=alphabetically' ),
					),
					'by_category' => array(
						'title'  => __( 'By Category', 'woocommerce-jetpack' ),
						'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=dashboard&section=by_category' ),
					),
					'active' => array(
						'title'  => __( 'Active', 'woocommerce-jetpack' ),
						'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=dashboard&section=active' ),
					),
					'manager' => array(
						'title'  => __( 'Manage Settings', 'woocommerce-jetpack' ),
						'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=dashboard&section=manager' ),
					),
				);
			} else {
				foreach ( $label_info['all_cat_ids'] as $link_id ) {
					$nodes[ $id ]['nodes'][ $link_id ] = array(
						'title'  => WCJ()->modules[ $link_id ]->short_desc,
						'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=' . $id . '&section=' . $link_id ),
						'meta'   => array( 'title' => WCJ()->modules[ $link_id ]->desc ),
						'nodes'  => array(
							'settings' => array(
								'title'  => __( 'Settings', 'woocommerce-jetpack' ),
								'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=' . $id . '&section=' . $link_id ),
							),
							'docs' => array(
								'title'  => __( 'Documentation', 'woocommerce-jetpack' ),
								'href'   => WCJ()->modules[ $link_id ]->link . '?utm_source=module_documentation&utm_medium=admin_bar_link&utm_campaign=booster_documentation',
								'meta'   => array( 'target' => '_blank' ),
							),
						),
					);
					if ( WCJ()->modules[ $link_id ]->is_enabled() ) {
						$active_modules[ $link_id ] = $nodes[ $id ]['nodes'][ $link_id ];
					}
				}
			}
		}
		if ( ! empty( $active_modules ) ) {
			usort( $active_modules, array( $this, 'usort_compare_by_title' ) );
			$nodes['dashboard']['nodes']['active']['nodes'] = $active_modules;
		}
		return $nodes;
	}

	/**
	 * usort_compare_by_title.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 */
	function usort_compare_by_title( $a, $b ) {
		if ( $a['title'] == $b['title'] ) {
			return 0;
		}
		return ( $a['title'] < $b['title'] ) ? -1 : 1;
	}

	/**
	 * get_nodes_booster_tools.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 */
	function get_nodes_booster_tools() {
		$nodes = array();
		$tools = apply_filters( 'wcj_tools_tabs', array(
			array(
				'id'    => 'dashboard',
				'title' => __( 'Dashboard', 'woocommerce-jetpack' ),
				'desc'  => __( 'This dashboard lets you check statuses and short descriptions of all available Booster for WooCommerce tools. Tools can be enabled through WooCommerce > Settings > Booster.', 'woocommerce-jetpack' ),
			),
		) );
		foreach ( $tools as $tool ) {
			$nodes[ $tool['id'] ] = array(
				'title'  => $tool['title'],
				'href'   => admin_url( 'admin.php?page=wcj-tools&tab=' . $tool['id'] ),
			);
			if ( isset( $tool['desc'] ) ) {
				$nodes[ $tool['id'] ]['meta']['title'] = $tool['desc'];
			}
		}
		return $nodes;
	}

	/**
	 * add_booster_admin_bar.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @todo    (maybe) separate "Booster Active Modules" admin bar menu
	 * @todo    (maybe) separate "Booster Modules" admin bar menu
	 * @todo    (maybe) separate "Booster Tools" admin bar menu
	 */
	function add_booster_admin_bar( $wp_admin_bar ) {
		$nodes = array(
			'booster' => array(
				'title'  => '<span class="ab-icon"></span>' . __( 'Booster', 'woocommerce-jetpack' ),
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
						'nodes'  => $this->get_nodes_booster_tools(),
					),
				),
			),
		);
		$this->add_woocommerce_admin_bar_nodes( $wp_admin_bar, $nodes, false );
	}

	/**
	 * get_nodes_orders_reports.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 */
	function get_nodes_orders_reports() {
		$nodes = array();
		$reports = array(
			'sales_by_date'     => __( 'Sales by date', 'woocommerce-jetpack' ),
			'sales_by_product'  => __( 'Sales by product', 'woocommerce-jetpack' ),
			'sales_by_category' => __( 'Sales by category', 'woocommerce-jetpack' ),
			'coupon_usage'      => __( 'Coupons by date', 'woocommerce-jetpack' ),
		);
		foreach ( $reports as $report_id => $report_title ) {
			$nodes[ $report_id ] = array(
				'title'  => $report_title,
				'href'   => admin_url( 'admin.php?page=wc-reports&tab=orders&report=' . $report_id ),
				'nodes'  => array(
					'7day' => array(
						'title'  => __( 'Last 7 days', 'woocommerce-jetpack' ),
						'href'   => admin_url( 'admin.php?page=wc-reports&tab=orders&report=' . $report_id . '&range=7day' ),
					),
					'month' => array(
						'title'  => __( 'Month', 'woocommerce-jetpack' ),
						'href'   => admin_url( 'admin.php?page=wc-reports&tab=orders&report=' . $report_id . '&range=month' ),
					),
					'last_month' => array(
						'title'  => __( 'Last month', 'woocommerce-jetpack' ),
						'href'   => admin_url( 'admin.php?page=wc-reports&tab=orders&report=' . $report_id . '&range=last_month' ),
					),
					'year' => array(
						'title'  => __( 'Year', 'woocommerce-jetpack' ),
						'href'   => admin_url( 'admin.php?page=wc-reports&tab=orders&report=' . $report_id . '&range=year' ),
					),
				),
			);
		}
		return $nodes;
	}

	/**
	 * add_woocommerce_admin_bar.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @todo    finish standard WC nodes
	 * @todo    reports > customers > customers > add dates
	 * @todo    reports > taxes > taxes_by_code > add dates
	 * @todo    reports > taxes > taxes_by_date > add dates
	 * @todo    (maybe) custom user nodes
	 * @todo    (maybe) optional selections
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
								'nodes'  => $this->get_nodes_orders_reports(),
							),
							'customers' => array(
								'title'  => __( 'Customers', 'woocommerce-jetpack' ),
								'href'   => admin_url( 'admin.php?page=wc-reports&tab=customers' ),
								'nodes'  => array(
									'customers' => array(
										'title'  => __( 'Customers vs. guests', 'woocommerce-jetpack' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=customers&report=customers' ),
									),
									'customer_list' => array(
										'title'  => __( 'Customer list', 'woocommerce-jetpack' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=customers&report=customer_list' ),
									),
								),
							),
							'stock' => array(
								'title'  => __( 'Stock', 'woocommerce-jetpack' ),
								'href'   => admin_url( 'admin.php?page=wc-reports&tab=stock' ),
								'nodes'  => array(
									'low_in_stock' => array(
										'title'  => __( 'Low in stock', 'woocommerce-jetpack' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=stock&report=low_in_stock' ),
									),
									'out_of_stock' => array(
										'title'  => __( 'Out of stock', 'woocommerce-jetpack' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=stock&report=out_of_stock' ),
									),
									'most_stocked' => array(
										'title'  => __( 'Most stocked', 'woocommerce-jetpack' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=stock&report=most_stocked' ),
									),
								),
							),
							'taxes' => array(
								'title'  => __( 'Taxes', 'woocommerce-jetpack' ),
								'href'   => admin_url( 'admin.php?page=wc-reports&tab=taxes' ),
								'nodes'  => array(
									'taxes_by_code' => array(
										'title'  => __( 'Taxes by code', 'woocommerce-jetpack' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=taxes&report=taxes_by_code' ),
									),
									'taxes_by_date' => array(
										'title'  => __( 'Taxes by date', 'woocommerce-jetpack' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=taxes&report=taxes_by_date' ),
									),
								),
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

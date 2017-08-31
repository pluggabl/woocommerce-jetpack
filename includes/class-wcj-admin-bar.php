<?php
/**
 * Booster for WooCommerce - Module - Admin Bar
 *
 * @version 3.1.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Admin_Bar' ) ) :

class WCJ_Admin_Bar extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.1.0
	 * @since   2.9.0
	 * @todo    (maybe) custom user nodes
	 * @todo    (maybe) optional nodes selection
	 * @todo    (maybe) add WooCommerce versions (from / to) to nodes
	 * @todo    (maybe) customizable icons
	 * @todo    (maybe) separate admin bar menu: "Booster Modules", "Booster Tools", "WooCommerce Reports", "WooCommerce Products", "WooCommerce Settings"
	 */
	function __construct() {

		$this->id         = 'admin_bar';
		$this->short_desc = __( 'Admin Bar', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce admin bar.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-admin-bar';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( 'yes' === get_option( 'wcj_admin_bar_wc_enabled', 'yes' ) ) {
				add_action( 'admin_bar_menu', array( $this, 'add_woocommerce_admin_bar' ), PHP_INT_MAX );
				add_action( 'wp_head',        array( $this, 'add_woocommerce_admin_bar_icon_style' ) );
				add_action( 'admin_head',     array( $this, 'add_woocommerce_admin_bar_icon_style' ) );
			}
			if ( 'yes' === get_option( 'wcj_admin_bar_booster_enabled', 'yes' ) ) {
				add_action( 'admin_bar_menu', array( $this, 'add_booster_admin_bar' ), PHP_INT_MAX );
				if ( 'yes' === get_option( 'wcj_admin_bar_booster_active_enabled', 'yes' ) ) {
					add_action( 'admin_bar_menu', array( $this, 'add_booster_active_admin_bar' ), PHP_INT_MAX );
				}
				add_action( 'wp_head',        array( $this, 'add_booster_admin_bar_icon_style' ) );
				add_action( 'admin_head',     array( $this, 'add_booster_admin_bar_icon_style' ) );
			}
			add_action( 'woojetpack_after_settings_save',  array( $this, 'reload_page_after_settings_save' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * reload_page_after_settings_save.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) somehow add "Your settings have been saved." admin notice
	 */
	function reload_page_after_settings_save( $sections, $current_section ) {
		// This function is needed so admin bar menus would appear immediately after module settings are saved (i.e. without additional page refresh)
		if ( $this->id === $current_section ) {
			wp_safe_redirect( add_query_arg( '', '' ) );
			exit;
		}
	}

	/**
	 * add_booster_admin_bar_icon_style.
	 *
	 * @version 3.1.0
	 * @since   2.9.0
	 */
	function add_booster_admin_bar_icon_style() {
		echo '<style type="text/css"> #wpadminbar #wp-admin-bar-booster .ab-icon:before { content: "\f185"; top: 3px; } </style>';
		echo '<style type="text/css"> #wpadminbar #wp-admin-bar-booster-active .ab-icon:before { content: "\f155"; top: 3px; } </style>';
	}

	/**
	 * add_woocommerce_admin_bar_icon_style.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function add_woocommerce_admin_bar_icon_style() {
		echo '<style type="text/css"> #wpadminbar #wp-admin-bar-wcj-wc .ab-icon:before { content: "\f174"; top: 3px; } </style>';
	}

	/**
	 * add_woocommerce_admin_bar_nodes.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
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
	 * @version 3.1.0
	 * @since   2.9.0
	 * @todo    (maybe) dashes instead of underscores
	 * @todo    (maybe) dashboard > alphabetically - list all modules
	 * @todo    (maybe) dashboard > by_category - list all modules
	 */
	function get_nodes_booster_modules() {
		$nodes = array();
		$cats  = include( wcj_plugin_path() . '/includes/admin/' . 'wcj-modules-cats.php' );
		$this->active_modules = array();
		foreach ( $cats as $id => $label_info ) {
			$nodes[ $id ] = array(
				'title'  => $label_info['label'],
				'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=' . $id ),
				'meta'   => array( 'title' => strip_tags( $label_info['desc'] ) ),
			);
			if ( 'dashboard' === $id ) {
				$nodes[ $id ]['nodes'] = apply_filters( 'wcj_admin_bar_dashboard_nodes', array(
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
				) );
			} else {
				$cat_nodes = array();
				foreach ( $label_info['all_cat_ids'] as $link_id ) {
					if ( wcj_is_module_deprecated( $link_id, false, true ) ) {
						continue;
					}
					$cat_nodes[ $link_id ] = array(
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
					if ( WCJ()->modules[ $link_id ]->is_enabled() && 'module' === WCJ()->modules[ $link_id ]->type ) {
						$this->active_modules[ $link_id ] = $cat_nodes[ $link_id ];
					}
				}
				usort( $cat_nodes, array( $this, 'usort_compare_by_title' ) );
				$nodes[ $id ]['nodes'] = $cat_nodes;
			}
		}
		if ( ! empty( $this->active_modules ) ) {
			usort( $this->active_modules, array( $this, 'usort_compare_by_title' ) );
			$nodes['dashboard']['nodes']['active']['nodes'] = $this->active_modules;
		}
		return $nodes;
	}

	/**
	 * usort_compare_by_title.
	 *
	 * @version 2.9.1
	 * @since   2.9.0
	 */
	function usort_compare_by_title( $a, $b ) {
		return strcasecmp( $a['title'], $b['title'] );
	}

	/**
	 * get_nodes_booster_tools.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
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
	 * add_booster_active_admin_bar.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function add_booster_active_admin_bar( $wp_admin_bar ) {
		$tools = array(
			'tools' => array(
				'title'  => __( 'Tools', 'woocommerce-jetpack' ),
				'href'   => admin_url( 'admin.php?page=wcj-tools' ),
				'nodes'  => $this->get_nodes_booster_tools(),
			),
		);
		unset( $tools['tools']['nodes']['dashboard'] );
		$nodes = array(
			'booster-active' => array(
				'title'  => '<span class="ab-icon"></span>' . __( 'Booster: Active', 'woocommerce-jetpack' ),
				'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=dashboard&section=active' ),
				'nodes'  => array_merge( $this->active_modules, $tools ),
			),
		);
		$this->add_woocommerce_admin_bar_nodes( $wp_admin_bar, $nodes, false );
	}

	/**
	 * add_booster_admin_bar.
	 *
	 * @version 2.9.1
	 * @since   2.9.0
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
					'modules' => array(
						'title'  => __( 'Modules', 'woocommerce-jetpack' ),
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
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function get_nodes_orders_reports() {
		$nodes = array();
		$reports = array(
			'sales_by_date'     => __( 'Sales by date', 'woocommerce' ),
			'sales_by_product'  => __( 'Sales by product', 'woocommerce' ),
			'sales_by_category' => __( 'Sales by category', 'woocommerce' ),
			'coupon_usage'      => __( 'Coupons by date', 'woocommerce' ),
		);
		foreach ( $reports as $report_id => $report_title ) {
			$nodes[ $report_id ] = array(
				'title'  => $report_title,
				'href'   => admin_url( 'admin.php?page=wc-reports&tab=orders&report=' . $report_id ),
				'nodes'  => array(
					'7day' => array(
						'title'  => __( 'Last 7 days', 'woocommerce' ),
						'href'   => admin_url( 'admin.php?page=wc-reports&tab=orders&report=' . $report_id . '&range=7day' ),
					),
					'month' => array(
						'title'  => __( 'This month', 'woocommerce' ),
						'href'   => admin_url( 'admin.php?page=wc-reports&tab=orders&report=' . $report_id . '&range=month' ),
					),
					'last-month' => array(
						'title'  => __( 'Last month', 'woocommerce' ),
						'href'   => admin_url( 'admin.php?page=wc-reports&tab=orders&report=' . $report_id . '&range=last_month' ),
					),
					'year' => array(
						'title'  => __( 'Year', 'woocommerce' ),
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
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) reports > customers > customers > add dates
	 * @todo    (maybe) reports > taxes > taxes_by_code > add dates
	 * @todo    (maybe) reports > taxes > taxes_by_date > add dates
	 * @todo    (maybe) settings > add custom sections (i.e. Booster and other plugins)
	 * @todo    (maybe) extensions > add sections
	 */
	function add_woocommerce_admin_bar( $wp_admin_bar ) {
		$nodes = array(
			'wcj-wc' => array(
				'title'  => '<span class="ab-icon"></span>' . __( 'WooCommerce', 'woocommerce' ),
				'href'   => admin_url( 'admin.php?page=wc-settings' ),
				'meta'   => array(
					'title'  => __( 'WooCommerce settings', 'woocommerce' ),
				),
				'nodes'  => array(
					'orders' => array(
						'title'  => __( 'Orders', 'woocommerce' ),
						'href'   => admin_url( 'edit.php?post_type=shop_order' ),
						'nodes'  => array(
							'orders' => array(
								'title'  => __( 'Orders', 'woocommerce' ),
								'href'   => admin_url( 'edit.php?post_type=shop_order' ),
							),
							'add-order' => array(
								'title'  => __( 'Add order', 'woocommerce' ),
								'href'   => admin_url( 'post-new.php?post_type=shop_order' ),
							),
							'customers' => array(
								'title'  => __( 'Customers', 'woocommerce' ),
								'href'   => admin_url( 'users.php?role=customer' ),
							),
						),
					),
					'reports' => array(
						'title'  => __( 'Reports', 'woocommerce' ),
						'href'   => admin_url( 'admin.php?page=wc-reports' ),
						'nodes'  => array(
							'orders' => array(
								'title'  => __( 'Orders', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-reports&tab=orders' ),
								'nodes'  => $this->get_nodes_orders_reports(),
							),
							'customers' => array(
								'title'  => __( 'Customers', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-reports&tab=customers' ),
								'nodes'  => array(
									'customers' => array(
										'title'  => __( 'Customers vs. guests', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=customers&report=customers' ),
									),
									'customer-list' => array(
										'title'  => __( 'Customer list', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=customers&report=customer_list' ),
									),
								),
							),
							'stock' => array(
								'title'  => __( 'Stock', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-reports&tab=stock' ),
								'nodes'  => array(
									'low-in-stock' => array(
										'title'  => __( 'Low in stock', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=stock&report=low_in_stock' ),
									),
									'out-of-stock' => array(
										'title'  => __( 'Out of stock', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=stock&report=out_of_stock' ),
									),
									'most-stocked' => array(
										'title'  => __( 'Most Stocked', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=stock&report=most_stocked' ),
									),
								),
							),
							'taxes' => array(
								'title'  => __( 'Taxes', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-reports&tab=taxes' ),
								'nodes'  => array(
									'taxes-by-code' => array(
										'title'  => __( 'Taxes by code', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=taxes&report=taxes_by_code' ),
									),
									'taxes-by-date' => array(
										'title'  => __( 'Taxes by date', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-reports&tab=taxes&report=taxes_by_date' ),
									),
								),
							),
						),
					),
					'products' => array(
						'title'  => __( 'Products', 'woocommerce' ),
						'href'   => admin_url( 'edit.php?post_type=product' ),
						'nodes'  => array(
							'products' => array(
								'title'  => __( 'Products', 'woocommerce' ),
								'href'   => admin_url( 'edit.php?post_type=product' ),
							),
							'add-product' => array(
								'title'  => __( 'Add product', 'woocommerce' ),
								'href'   => admin_url( 'post-new.php?post_type=product' ),
							),
							'categories' => array(
								'title'  => __( 'Categories', 'woocommerce' ),
								'href'   => admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ),
							),
							'tags' => array(
								'title'  => __( 'Tags', 'woocommerce' ),
								'href'   => admin_url( 'edit-tags.php?taxonomy=product_tag&post_type=product' ),
							),
							'attributes' => array(
								'title'  => __( 'Attributes', 'woocommerce' ),
								'href'   => admin_url( 'edit.php?post_type=product&page=product_attributes' ),
							),
						),
					),
					'coupons' => array(
						'title'  => __( 'Coupons', 'woocommerce' ),
						'href'   => admin_url( 'edit.php?post_type=shop_coupon' ),
						'nodes'  => array(
							'coupons' => array(
								'title'  => __( 'Coupons', 'woocommerce' ),
								'href'   => admin_url( 'edit.php?post_type=shop_coupon' ),
							),
							'add-coupon' => array(
								'title'  => __( 'Add coupon', 'woocommerce' ),
								'href'   => admin_url( 'post-new.php?post_type=shop_coupon' ),
							),
						),
					),
					'settings' => array(
						'title'  => __( 'Settings', 'woocommerce' ),
						'href'   => admin_url( 'admin.php?page=wc-settings' ),
						'nodes'  => array(
							'general' => array(
								'title'  => __( 'General', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-settings&tab=general' ),
							),
							'products' => array(
								'title'  => __( 'Products', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-settings&tab=products' ),
								'nodes'  => array(
									'general' => array(
										'title'  => __( 'General', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=products&section' ),
									),
									'display' => array(
										'title'  => __( 'Display', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=products&section=display' ),
									),
									'inventory' => array(
										'title'  => __( 'Inventory', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=products&section=inventory' ),
									),
									'downloadable' => array(
										'title'  => __( 'Downloadable products', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=products&section=downloadable' ),
									),
								),
							),
							'tax' => array(
								'title'  => __( 'Tax', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-settings&tab=tax' ),
								'nodes'  => array(
									'tax-options' => array(
										'title'  => __( 'Tax options', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=tax&section' ),
									),
									'standard-rates' => array(
										'title'  => __( 'Standard rates', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=tax&section=standard' ),
									),
								),
							),
							'shipping' => array(
								'title'  => __( 'Shipping', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
								'nodes'  => array(
									'shipping-zones' => array(
										'title'  => __( 'Shipping zones', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=shipping&section' ),
									),
									'shipping-options' => array(
										'title'  => __( 'Shipping options', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=shipping&section=options' ),
									),
									'shipping-classes' => array(
										'title'  => __( 'Shipping classes', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=shipping&section=classes' ),
									),
								),
							),
							'checkout' => array(
								'title'  => __( 'Checkout', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-settings&tab=checkout' ),
								'nodes'  => array(
									'checkout-options' => array(
										'title'  => __( 'Checkout options', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=checkout&section' ),
									),
									'bacs' => array(
										'title'  => __( 'BACS', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=checkout&section=bacs' ),
									),
									'cheque' => array(
										'title'  => __( 'Check payments', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=checkout&section=cheque' ),
									),
									'cod' => array(
										'title'  => __( 'Cash on delivery', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=checkout&section=cod' ),
									),
									'paypal' => array(
										'title'  => __( 'PayPal', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paypal' ),
									),
								),
							),
							'account' => array(
								'title'  => __( 'Account', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-settings&tab=account' ),
							),
							'email' => array(
								'title'  => __( 'Emails', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-settings&tab=email' ),
							),
							'api' => array(
								'title'  => __( 'API', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-settings&tab=api' ),
								'nodes'  => array(
									'settings' => array(
										'title'  => __( 'Settings', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=api&section' ),
									),
									'keys' => array(
										'title'  => __( 'Keys/Apps', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=api&section=keys' ),
									),
									'webhooks' => array(
										'title'  => __( 'Webhooks', 'woocommerce' ),
										'href'   => admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks' ),
									),
								),
							),
						),
					),
					'system-status' => array(
						'title'  => __( 'System status', 'woocommerce' ),
						'href'   => admin_url( 'admin.php?page=wc-status' ),
						'nodes'  => array(
							'system-status' => array(
								'title'  => __( 'System status', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-status&tab=status' ),
							),
							'tools' => array(
								'title'  => __( 'Tools', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-status&tab=tools' ),
							),
							'logs' => array(
								'title'  => __( 'Logs', 'woocommerce' ),
								'href'   => admin_url( 'admin.php?page=wc-status&tab=logs' ),
							),
						),
					),
					'extensions' => array(
						'title'  => __( 'Extensions', 'woocommerce' ),
						'href'   => admin_url( 'admin.php?page=wc-addons' ),
					),
				),
			),
		);
		$this->add_woocommerce_admin_bar_nodes( $wp_admin_bar, $nodes, false );
	}

}

endif;

return new WCJ_Admin_Bar();

<?php
/**
 * Booster for WooCommerce - Module - Product by User
 *
 * @version 5.2.0
 * @since   2.5.2
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_By_User' ) ) :

class WCJ_Product_By_User extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   2.5.2
	 * @todo    run `add_my_products_endpoint` only if module is enabled
	 */
	function __construct() {

		$this->id         = 'product_by_user';
		$this->short_desc = __( 'User Products', 'woocommerce-jetpack' );
		$this->desc       = __( 'Let users add new products from the frontend. Image additional field (Plus). Custom Taxonomies (1 allowed in free version).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Let users add new products from the frontend.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-by-user';
		$this->extra_desc = __( 'Use <strong>[wcj_product_add_new]</strong> shortcode to add product upload form to frontend.', 'woocommerce-jetpack' );
		parent::__construct();

		// My Products endpoint
		register_activation_hook(   __FILE__, array( $this, 'add_my_products_endpoint_flush_rewrite_rules' ) );
		register_deactivation_hook( __FILE__, array( $this, 'add_my_products_endpoint_flush_rewrite_rules' ) );
		add_filter( 'query_vars',             array( $this, 'add_my_products_endpoint_query_var' ), 0 );
		add_action( 'init',                   array( $this, 'add_my_products_endpoint' ) );

		if ( $this->is_enabled() ) {
			if ( 'yes' === wcj_get_option( 'wcj_product_by_user_add_to_my_account', 'yes' ) ) {
				add_filter( 'woocommerce_account_menu_items',               array( $this, 'add_my_products_tab_my_account_page' ) );
				add_action( 'woocommerce_account_wcj-my-products_endpoint', array( $this, 'add_my_products_content_my_account_page' ) );
				add_filter( 'the_title',                                    array( $this, 'change_my_products_endpoint_title' ) );
			}
		}
	}

	/**
	 * Flush rewrite rules on plugin activation.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function add_my_products_endpoint_flush_rewrite_rules() {
		add_rewrite_endpoint( 'wcj-my-products', EP_ROOT | EP_PAGES );
		flush_rewrite_rules();
	}

	/**
	 * Add new query var.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 * @param   array $vars
	 * @return  array
	 */
	function add_my_products_endpoint_query_var( $vars ) {
		$vars[] = 'wcj-my-products';
		return $vars;
	}

	/**
	 * Register new endpoint to use inside My Account page.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 * @see     https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
	 */
	function add_my_products_endpoint() {
		add_rewrite_endpoint( 'wcj-my-products', EP_ROOT | EP_PAGES );
	}

	/*
	 * Change endpoint title.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 * @param   string $title
	 * @return  string
	 * @see     https://github.com/woocommerce/woocommerce/wiki/2.6-Tabbed-My-Account-page
	 */
	function change_my_products_endpoint_title( $title ) {
		global $wp_query;
		$is_endpoint = isset( $wp_query->query_vars['wcj-my-products'] );
		if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
			// New page title.
			$title = __( 'Products', 'woocommerce-jetpack' );
			remove_filter( 'the_title', array( $this, 'change_my_products_endpoint_title' ) );
		}
		return $title;
	}

	/**
	 * Custom help to add new items into an array after a selected item.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 * @param   array $items
	 * @param   array $new_items
	 * @param   string $after
	 * @return  array
	 * @see     https://github.com/woocommerce/woocommerce/wiki/2.6-Tabbed-My-Account-page
	 */
	function insert_after_helper( $items, $new_items, $after ) {
		// Search for the item position and +1 since is after the selected item key.
		$position = array_search( $after, array_keys( $items ) ) + 1;
		// Insert the new item.
		$array = array_slice( $items, 0, $position, true );
		$array += $new_items;
		$array += array_slice( $items, $position, count( $items ) - $position, true );
		return $array;
	}

	/**
	 * add_my_products_tab_my_account_page.
	 *
	 * @version 2.5.7
	 * @since   2.5.2
	 * @todo    check if any user's products exist
	 */
	function add_my_products_tab_my_account_page( $items ) {
//		$items['wcj-my-products'] = __( 'My Products', 'woocommerce-jetpack' );
		$new_items = array( 'wcj-my-products' => __( 'Products', 'woocommerce-jetpack' ) );
		return $this->insert_after_helper( $items, $new_items, 'orders' );
	}

	/**
	 * add_my_products_content_my_account_page.
	 *
	 * @version 2.8.0
	 * @since   2.5.2
	 */
	function add_my_products_content_my_account_page() {
		/* if ( ! isset( $_GET['wcj-my-products'] ) ) {
			return;
		} */
		$user_ID = get_current_user_id();
		if ( 0 == $user_ID ) {
			return;
		}
		if ( isset( $_GET['wcj_delete_product'] ) ) {
			$product_id = $_GET['wcj_delete_product'];
			$post_author_id = get_post_field( 'post_author', $product_id );
			if ( $user_ID != $post_author_id ) {
				echo '<p>' . __( 'Wrong user ID!', 'woocommerce-jetpack' ) . '</p>';
			} else {
				wp_delete_post( $product_id, true );
			}
		}
		if ( isset( $_GET['wcj_edit_product'] ) ) {
			$product_id = $_GET['wcj_edit_product'];
			$post_author_id = get_post_field( 'post_author', $product_id );
			if ( $user_ID != $post_author_id ) {
				echo '<p>' . __( 'Wrong user ID!', 'woocommerce-jetpack' ) . '</p>';
			} else {
				echo do_shortcode( '[wcj_product_add_new product_id="' . $product_id . '"]' );
			}
		}
		$offset = 0;
		$block_size = 256;
		$products = array();
		while( true ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'author'         => $user_ID,
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $post_id ) {
				$products[ $post_id ] = array(
					'title'  => get_the_title( $post_id ),
					'status' => get_post_status( $post_id ),
				);
			}
			$offset += $block_size;
		}
		if ( 0 != count( $products ) ) {
//			echo '<h2>' . __( 'My Products', 'woocommerce-jetpack' ) . '</h2>';
			$table_data = array();
			$table_data[] = array( '', __( 'Status', 'woocommerce-jetpack' ), __( 'Title', 'woocommerce-jetpack' ), __( 'Actions', 'woocommerce-jetpack' ) );
			$i = 0;
			foreach ( $products as $_product_id => $_product_data ) {
				$i++;
				$table_data[] = array(
					/* $i . ' [' . $_product_id . ']' . */ get_the_post_thumbnail( $_product_id, array( 25, 25 ) ),
					'<code>'. $_product_data['status'] . '</code>',
					$_product_data['title'],
					'<a class="button" href="' . add_query_arg( 'wcj_edit_product',   $_product_id, remove_query_arg( array( 'wcj_edit_product_image_delete', 'wcj_delete_product' ) ) ) . '">' . __( 'Edit', 'woocommerce-jetpack' ) . '</a>' . ' ' .
					'<a class="button" href="' . add_query_arg( 'wcj_delete_product', $_product_id, remove_query_arg( array( 'wcj_edit_product_image_delete', 'wcj_edit_product' ) ) ) . '" onclick="return confirm(\'' . __( 'Are you sure?', 'woocommerce-jetpack' ) . '\')">' . __( 'Delete', 'woocommerce-jetpack' ) . '</a>',
				);
			}
			echo wcj_get_table_html( $table_data, array( 'table_class' => 'shop_table shop_table_responsive my_account_orders' ) );
		}
	}

}

endif;

return new WCJ_Product_By_User();

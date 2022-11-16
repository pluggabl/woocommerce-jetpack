<?php
/**
 * Booster for WooCommerce - Module - Product by User
 *
 * @version 5.6.8
 * @since   2.5.2
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Product_By_User' ) ) :
	/**
	 * WCJ_Product_By_User.
	 */
	class WCJ_Product_By_User extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.6.2
		 * @since   2.5.2
		 * @todo    run `add_my_products_endpoint` only if module is enabled
		 */
		public function __construct() {

			$this->id         = 'product_by_user';
			$this->short_desc = __( 'User Products', 'woocommerce-jetpack' );
			$this->desc       = __( 'Let users add new products from the frontend. Image additional field (Plus). Custom Taxonomies (1 allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Let users add new products from the frontend.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-product-by-user';
			$this->extra_desc = __( 'Use <strong>[wcj_product_add_new]</strong> shortcode to add product upload form to frontend.', 'woocommerce-jetpack' );
			parent::__construct();

			// My Products endpoint.
			register_activation_hook( __FILE__, array( $this, 'add_my_products_endpoint_flush_rewrite_rules' ) );
			register_deactivation_hook( __FILE__, array( $this, 'add_my_products_endpoint_flush_rewrite_rules' ) );
			add_filter( 'query_vars', array( $this, 'add_my_products_endpoint_query_var' ), 0 );
			add_action( 'init', array( $this, 'add_my_products_endpoint' ) );
			$send_email_to_user_product = get_option( 'wcj_user_product_email_send', 'no' );
			if ( 'yes' === $send_email_to_user_product ) {
				add_action( 'woocommerce_thankyou', array( $this, 'getProductOwnerEmail' ), 10, 1 );
				add_filter( 'woocommerce_email_headers', array( $this, 'sendemail_to_productowner_order_place_successfully' ), 10, 3 );
				add_filter( 'woocommerce_email_recipient_no_stock', array( $this, 'change_stock_email_recipient' ), 10, 2 );
			}

			if ( $this->is_enabled() ) {
				if ( 'yes' === wcj_get_option( 'wcj_product_by_user_add_to_my_account', 'yes' ) ) {
					add_filter( 'woocommerce_account_menu_items', array( $this, 'add_my_products_tab_my_account_page' ) );
					add_action( 'woocommerce_account_wcj-my-products_endpoint', array( $this, 'add_my_products_content_my_account_page' ) );
					add_filter( 'the_title', array( $this, 'change_my_products_endpoint_title' ) );
				}
			}
		}

		/**
		 * Get Product User Email at success page.
		 *
		 * @version 5.5.8
		 * @since 1.0.0
		 * @param int $order_id defines the order_id.
		 */
		public function getProductOwnerEmail( $order_id ) {
			if ( ! $order_id ) {
				return;
			}
			$order = wc_get_order( $order_id );

			foreach ( $order->get_items() as $item_id => $item ) {
				$productid = $item['product_id'];
				$authorid  = get_post_field( 'post_author', $productid );
				$user      = get_user_by( 'ID', $authorid );
				$useremail = $user->user_email;
			}
			return $useremail;
		}

		/**
		 * Send Email To Product User at success page when email send setting enable
		 *
		 * @version 5.6.2
		 * @since 1.0.0
		 * @param string $headers defines the headers.
		 * @param string $email_id defines the email_id.
		 * @param array  $object defines the object.
		 */
		public function sendemail_to_productowner_order_place_successfully( $headers, $email_id, $object ) {
			if ( 'new_order' === $email_id && is_a( $object, 'WC_Order' ) ) {
				$useremail = $this->getProductOwnerEmail( wcj_get_order_id( $object ) );
				$headers  .= 'Cc: Name <' . $useremail . '>' . "\r\n";
			}
			return $headers;
		}

		/**
		 * Send Email To Product User at success page when Product is Out Of Stock
		 *
		 * @version 5.6.0
		 * @since 1.0.0
		 * @param string $recipient defines the recipient.
		 * @param array  $product defines the product.
		 */
		public function change_stock_email_recipient( $recipient, $product ) {
			$recipient = $this->get_out_of_stock_email_to_userproduct_recipient( $recipient, $product );
			return $recipient;
		}

		/**
		 * Get Product User Email at success page for Out Of Stock Product
		 *
		 * @version 5.5.8
		 * @since 1.0.0
		 * @param string $recipient defines the recipient.
		 * @param array  $product defines the product.
		 */
		public function get_out_of_stock_email_to_userproduct_recipient( $recipient, $product ) {
			$productid = $product->id;
			$authorid  = get_post_field( 'post_author', $productid );
			$user      = get_user_by( 'ID', $authorid );
			$recipient = $user->user_email;
			return $recipient;
		}

		/**
		 * Flush rewrite rules on plugin activation.
		 *
		 * @version 2.5.7
		 * @since   2.5.7
		 */
		public function add_my_products_endpoint_flush_rewrite_rules() {
			add_rewrite_endpoint( 'wcj-my-products', EP_ROOT | EP_PAGES );
			flush_rewrite_rules();
		}

		/**
		 * Add new query var.
		 *
		 * @version 2.5.7
		 * @since   2.5.7
		 * @param array $vars defines the vars.
		 * @return  array
		 */
		public function add_my_products_endpoint_query_var( $vars ) {
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
		public function add_my_products_endpoint() {
			add_rewrite_endpoint( 'wcj-my-products', EP_ROOT | EP_PAGES );
		}

		/**
		 * Change endpoint title.
		 *
		 * @version 2.5.7
		 * @since   2.5.7
		 * @param string $title defines the title.
		 * @return  string
		 * @see     https://github.com/woocommerce/woocommerce/wiki/2.6-Tabbed-My-Account-page
		 */
		public function change_my_products_endpoint_title( $title ) {
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
		 * @version 5.6.2
		 * @since   2.5.7
		 * @param array  $items defines the items.
		 * @param string $new_items defines the new_items.
		 * @param string $after defines the after.
		 * @return  array
		 * @see     https://github.com/woocommerce/woocommerce/wiki/2.6-Tabbed-My-Account-page
		 */
		public function insert_after_helper( $items, $new_items, $after ) {
			// Search for the item position and +1 since is after the selected item key.
			$position = array_search( $after, array_keys( $items ), true ) + 1;
			// Insert the new item.
			$array  = array_slice( $items, 0, $position, true );
			$array += $new_items;
			$array += array_slice( $items, $position, count( $items ) - $position, true );
			return $array;
		}

		/**
		 * Add_my_products_tab_my_account_page.
		 *
		 * @version 2.5.7
		 * @since   2.5.2
		 * @todo    check if any user's products exist
		 * @param array $items defines the items.
		 */
		public function add_my_products_tab_my_account_page( $items ) {

			$new_items = array( 'wcj-my-products' => __( 'Products', 'woocommerce-jetpack' ) );
			return $this->insert_after_helper( $items, $new_items, 'orders' );
		}

		/**
		 * Add_my_products_content_my_account_page.
		 *
		 * @version 5.6.8
		 * @since   2.5.2
		 */
		public function add_my_products_content_my_account_page() {
			$user_ID = get_current_user_id();
			if ( 0 === $user_ID ) {
				return;
			}
			$edit_wpnonce   = isset( $_REQUEST['wcj_edit_product-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_edit_product-nonce'] ), 'wcj_edit_product' ) : false;
			$delete_wpnonce = isset( $_REQUEST['wcj_delete_product-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_delete_product-nonce'] ), 'wcj_delete_product' ) : false;
			if ( $delete_wpnonce && isset( $_GET['wcj_delete_product'] ) ) {
				$product_id     = sanitize_text_field( wp_unslash( $_GET['wcj_delete_product'] ) );
				$post_author_id = get_post_field( 'post_author', $product_id );
				if ( (string) $user_ID !== $post_author_id ) {
					echo '<p>' . esc_html__( 'Wrong user ID!', 'woocommerce-jetpack' ) . '</p>';
				} else {
					wp_delete_post( $product_id, true );
				}
			}
			if ( $edit_wpnonce && isset( $_GET['wcj_edit_product'] ) ) {
				$product_id     = sanitize_text_field( wp_unslash( $_GET['wcj_edit_product'] ) );
				$post_author_id = get_post_field( 'post_author', $product_id );
				if ( (string) $user_ID !== $post_author_id ) {
					echo '<p>' . esc_html__( 'Wrong user ID!', 'woocommerce-jetpack' ) . '</p>';
				} else {
					echo do_shortcode( '[wcj_product_add_new product_id="' . $product_id . '"]' );
				}
			}
			$offset     = 0;
			$block_size = 256;
			$products   = array();
			while ( true ) {
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
			if ( 0 !== count( $products ) ) {
				$table_data   = array();
				$table_data[] = array( '', __( 'Status', 'woocommerce-jetpack' ), __( 'Title', 'woocommerce-jetpack' ), __( 'Actions', 'woocommerce-jetpack' ) );
				$i            = 0;
				foreach ( $products as $_product_id => $_product_data ) {
					$i++;
					$table_data[] = array(
						/* $i . ' [' . $_product_id . ']' . */ get_the_post_thumbnail( $_product_id, array( 25, 25 ) ),
						'<code>' . $_product_data['status'] . '</code>',
						$_product_data['title'],
						'<a class="button" href="' . esc_url(
							add_query_arg(
								array(
									'wcj_edit_product' => $_product_id,
									'wcj_edit_product-nonce' => wp_create_nonce( 'wcj_edit_product' ),
								),
								remove_query_arg( array( 'wcj_edit_product_image_delete', 'wcj_delete_product', 'wcj_delete_product-nonce' ) )
							)
						) . '">' . __( 'Edit', 'woocommerce-jetpack' ) . '</a>
						<a class="button" href="' . esc_url(
							add_query_arg(
								array(
									'wcj_delete_product' => $_product_id,
									'wcj_delete_product-nonce' => wp_create_nonce( 'wcj_delete_product' ),
								),
								remove_query_arg( array( 'wcj_edit_product_image_delete', 'wcj_edit_product', 'wcj_edit_product-nonce' ) )
							)
						) . '" onclick="return confirm(\'' . __( 'Are you sure?', 'woocommerce-jetpack' ) . '\')">' . __( 'Delete', 'woocommerce-jetpack' ) . '</a>',
					);
				}
				echo wp_kses_post( wcj_get_table_html( $table_data, array( 'table_class' => 'shop_table shop_table_responsive my_account_orders' ) ) );
			}
		}

	}

endif;

return new WCJ_Product_By_User();

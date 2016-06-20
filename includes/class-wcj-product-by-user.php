<?php
/**
 * WooCommerce Jetpack Product by User
 *
 * The WooCommerce Jetpack Product by User class.
 *
 * @version 2.5.2
 * @since   2.5.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_By_User' ) ) :

class WCJ_Product_By_User extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	public function __construct() {

		$this->id         = 'product_by_user';
		$this->short_desc = __( 'Product by User', 'woocommerce-jetpack' );
		$this->desc       = __( 'Let users to add new WooCommerce products from frontend.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-by-user/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( 'yes' === get_option( 'wcj_product_by_user_add_to_my_account', 'yes' ) ) {
				add_filter( 'woocommerce_account_menu_items', array( $this, 'add_my_products_tab_my_account_page' ) );
				add_action( 'woocommerce_account_content',    array( $this, 'add_my_products_content_my_account_page' ) );
			}
		}
	}

	/**
	 * add_my_products_tab_my_account_page.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 * @todo    check if user's products exist
	 */
	function add_my_products_tab_my_account_page( $items ) {
		$items['wcj-my-products'] = __( 'My Products', 'woocommerce-jetpack' );
		return $items;
	}

	/**
	 * add_my_products_content_my_account_page.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_my_products_content_my_account_page() {
		if ( ! isset( $_GET['wcj-my-products'] ) ) {
			return;
		}
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
		$block_size = 96;
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
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			while ( $loop->have_posts() ) : $loop->the_post();
				$products[ strval( $loop->post->ID ) ] = array(
					'title'  => get_the_title( $loop->post->ID ),
					'status' => get_post_status( $loop->post->ID ),
				);
			endwhile;
			$offset += $block_size;
		}
		wp_reset_postdata();
		if ( 0 != count( $products ) ) {
			echo '<h2>' . __( 'My Products', 'woocommerce-jetpack' ) . '</h2>';
			$table_data = array();
			$table_data[] = array( '', __( 'Status', 'woocommerce-jetpack' ), __( 'Title', 'woocommerce-jetpack' ), __( 'Actions', 'woocommerce-jetpack' ) );
			$i = 0;
			foreach ( $products as $_product_id => $_product_data ) {
				$i++;
				$table_data[] = array(
					/* $i . ' [' . $_product_id . ']' . */ get_the_post_thumbnail( $_product_id, array( 25, 25 ) ),
					'<code>'. $_product_data['status'] . '</code>',
					$_product_data['title'],
					'<a class="button" href="' . add_query_arg( 'wcj_edit_product', $_product_id, remove_query_arg( array( 'wcj_edit_product_image_delete', 'wcj_delete_product' ) ) ) . '">' . __( 'Edit', 'woocommerce-jetpack' ) . '</a>' . ' ' .
					'<a class="button" href="' . add_query_arg( 'wcj_delete_product', $_product_id, remove_query_arg( array( 'wcj_edit_product_image_delete', 'wcj_edit_product' ) ) ) . '" onclick="return confirm(\'' . __( 'Are you sure?', 'woocommerce-jetpack' ) . '\')">' . __( 'Delete', 'woocommerce-jetpack' ) . '</a>',
				);
			}
			echo wcj_get_table_html( $table_data, array( 'table_class' => 'shop_table shop_table_responsive my_account_orders' ) );
		}
	}

	/**
	 * get_user_roles.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function get_user_roles() {
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

	/**
	 * get_settings.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function get_settings() {

		$fields = array(
			'desc'          => __( 'Description', 'woocommerce-jetpack' ),
			'short_desc'    => __( 'Short Description', 'woocommerce-jetpack' ),
			'image'         => __( 'Image', 'woocommerce-jetpack' ),
			'regular_price' => __( 'Regular Price', 'woocommerce-jetpack' ),
			'sale_price'    => __( 'Sale Price', 'woocommerce-jetpack' ),
			'cats'          => __( 'Categories', 'woocommerce-jetpack' ),
			'tags'          => __( 'Tags', 'woocommerce-jetpack' ),
		);
		$fields_enabled_options  = array();
		$fields_required_options = array();
		$i = 0;
		$total_fields = count( $fields );
		foreach ( $fields as $field_id => $field_desc ) {
			$i++;
			$checkboxgroup = '';
			if ( 1 === $i ) {
				$checkboxgroup = 'start';
			} elseif ( $total_fields === $i ) {
				$checkboxgroup = 'end';
			}
			$fields_enabled_options[] = array(
				'title'    => ( ( 1 === $i ) ? __( 'Additional Fields', 'woocommerce-jetpack' ) : '' ),
				'desc'     => $field_desc,
				'id'       => 'wcj_product_by_user_' . $field_id . '_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => $checkboxgroup,
				'custom_attributes' => ( ( 'image' === $field_id ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ) : '' ),
				'desc_tip' => ( ( 'image' === $field_id ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ) : '' ),
			);
			$fields_required_options[] = array(
				'title'    => ( ( 1 === $i ) ? __( 'Is Required', 'woocommerce-jetpack' ) : '' ),
				'desc'     => $field_desc,
				'id'       => 'wcj_product_by_user_' . $field_id . '_required',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => $checkboxgroup,
				'custom_attributes' => ( ( 'image' === $field_id ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ) : '' ),
				'desc_tip' => ( ( 'image' === $field_id ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ) : '' ),
			);
		}

		$settings = array_merge(
			array(
				array(
					'title'    => __( 'Options', 'woocommerce-jetpack' ),
					'type'     => 'title',
					'desc'     => __( '<em>Title</em> field is always enabled and required.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_by_user_options',
				),
			),
			$fields_enabled_options,
			$fields_required_options,
			array(
				array(
					'title'    => __( 'User Visibility', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_by_user_user_visibility',
					'default'  => array(),
					'type'     => 'multiselect',
					'class'    => 'chosen_select',
					'options'  => $this->get_user_roles(),
				),
				array(
					'title'    => __( 'Product Status', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_by_user_status',
					'default'  => 'draft',
					'type'     => 'select',
					'options'  => get_post_statuses(),
				),
				array(
					'title'    => __( 'Add "My Products" Tab to User\'s My Account Page', 'woocommerce-jetpack' ),
					'desc '    => __( 'Add', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_by_user_add_to_my_account',
					'default'  => 'yes',
					'type'     => 'checkbox',
				),
				array(
					'type'     => 'sectionend',
					'id'       => 'wcj_product_by_user_options',
				),
			)
		);
		return $this->add_standard_settings( $settings, __( 'Use [wcj_product_add_new] shortcode.', 'woocommerce-jetpack' ) );
	}
}

endif;

return new WCJ_Product_By_User();

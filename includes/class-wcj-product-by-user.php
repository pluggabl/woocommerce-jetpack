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
		$this->short_desc = __( 'Product by User [BETA]', 'woocommerce-jetpack' );
		$this->desc       = __( 'Let users to add new WooCommerce products from frontend.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-by-user/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'woocommerce_before_my_account', array( $this, 'add_my_products_to_my_account_page' ) );
		}
	}

	/**
	 * add_my_products_to_my_account_page.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_my_products_to_my_account_page() {
		$user_ID = get_current_user_id();
		if ( 0 == $user_ID ) {
			return;
		}
		if ( isset( $_GET['wcj_edit_product'] ) ) {
			$product_id = $_GET['wcj_edit_product'];
			$post_author_id = get_post_field( 'post_author', $product_id );
			if ( $user_ID != $post_author_id ) {
				echo '<p>' . __( 'Wrong user ID!', 'woocommerce-jetpack' ) . '</p>';
			}
			echo do_shortcode( '[wcj_product_add_new product_id="' . $product_id . '"]' );
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
			$table_data[] = array( '#', __( 'Status', 'woocommerce-jetpack' ), __( 'Title', 'woocommerce-jetpack' ), __( 'Actions', 'woocommerce-jetpack' ) );
			$i = 0;
			foreach ( $products as $_product_id => $_product_data ) {
				$i++;
				$table_data[] = array(
					$i . ' [' . $_product_id . ']',
					'<code>'. $_product_data['status'] . '</code>',
					$_product_data['title'],
					'<a href="' . add_query_arg( 'wcj_edit_product', $_product_id ) . '">' . __( 'Edit', 'woocommerce-jetpack' ) . '</a>',
				);
			}
			echo wcj_get_table_html( $table_data, array( 'table_class' => 'shop_table shop_table_responsive' ) );
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
		$all_roles = $wp_roles->roles;
		$all_roles = apply_filters( 'editable_roles', $all_roles );
		if ( '' == $all_roles ) {
			$all_roles = array();
		}
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
		$settings = array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'Title field is always enabled and required.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_options',
			),
			/* array(
				'title'    => __( 'Fields', 'woocommerce-jetpack' ),
				'desc'     => __( 'Title', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_title_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
				'custom_attributes' => array( 'disabled' => 'disabled' ),
				'checkboxgroup' => 'start',
			), */
			array(
				'title'    => __( 'Additional Fields', 'woocommerce-jetpack' ),
				'desc'     => __( 'Description', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_desc_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
				'checkboxgroup' => 'start',
			),
			array(
				'desc'     => __( 'Short Description', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_short_desc_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'desc'     => __( 'Regular Price', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_regular_price_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'desc'     => __( 'Sale Price', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_sale_price_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'desc'     => __( 'Categories', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_cats_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'desc'     => __( 'Tags', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_tags_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'end',
			),
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
				'type'     => 'sectionend',
				'id'       => 'wcj_product_by_user_options',
			),
		);
		return $this->add_standard_settings( $settings, __( 'Use [wcj_product_add_new] shortcode.', 'woocommerce-jetpack' ) );
	}
}

endif;

return new WCJ_Product_By_User();

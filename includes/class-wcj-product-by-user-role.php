<?php
/**
 * Booster for WooCommerce - Module - Product Visibility by User Role
 *
 * @version 3.5.4
 * @since   2.5.5
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_By_User_Role' ) ) :

class WCJ_Product_By_User_Role extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.5.4
	 * @since   2.5.5
	 * @todo    add "Quick and bulk edit" options to all product visibility modules
	 */
	function __construct() {

		$this->id         = 'product_by_user_role';
		$this->short_desc = __( 'Product Visibility by User Role', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display WooCommerce products by customer\'s user role.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-visibility-by-user-role';
		$this->extra_desc = __( 'When enabled, module will add new "Booster: Product Visibility by User Role" meta box to each product\'s edit page.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			if ( wcj_is_frontend() ) {
				if ( 'yes' === get_option( 'wcj_product_by_user_role_visibility', 'yes' ) ) {
					add_filter( 'woocommerce_product_is_visible', array( $this, 'product_by_user_role_visibility' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_product_by_user_role_purchasable', 'no' ) ) {
					add_filter( 'woocommerce_is_purchasable',     array( $this, 'product_by_user_role_purchasable' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_product_by_user_role_query', 'no' ) ) {
					add_action( 'pre_get_posts',                  array( $this, 'product_by_user_role_pre_get_posts' ) );
				}
			}
			// Quick and bulk edit
			if (
				'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_product_by_user_role_admin_bulk_edit', 'no' ) ) ||
				'yes' === get_option( 'wcj_product_by_user_role_admin_quick_edit', 'no' )
			) {
				if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_product_by_user_role_admin_bulk_edit', 'no' ) ) ) {
					add_action( 'woocommerce_product_bulk_edit_end', array( $this, 'add_bulk_and_quick_edit_fields' ), PHP_INT_MAX );
				}
				if ( 'yes' === get_option( 'wcj_product_by_user_role_admin_quick_edit', 'no' ) ) {
					add_action( 'woocommerce_product_quick_edit_end', array( $this, 'add_bulk_and_quick_edit_fields' ), PHP_INT_MAX );
				}
				add_action( 'woocommerce_product_bulk_and_quick_edit', array( $this, 'save_bulk_and_quick_edit_fields' ), PHP_INT_MAX, 2 );
			}
			// Admin products list
			if ( 'yes' === get_option( 'wcj_product_by_user_role_admin_add_column', 'no' ) ) {
				add_filter( 'manage_edit-product_columns',        array( $this, 'add_product_columns' ),   PHP_INT_MAX );
				add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * add_product_columns.
	 *
	 * @version 3.5.4
	 * @since   3.5.4
	 */
	function add_product_columns( $columns ) {
		$columns[ 'wcj_product_by_user_role' ] = __( 'User Roles', 'woocommerce-jetpack' );
		return $columns;
	}

	/**
	 * render_product_column.
	 *
	 * @version 3.5.4
	 * @since   3.5.4
	 */
	function render_product_column( $column ) {
		if ( 'wcj_product_by_user_role' === $column ) {
			$result = '';
			if ( $user_roles = get_post_meta( get_the_ID(), '_' . 'wcj_product_by_user_role_visible', true ) ) {
				if ( is_array( $user_roles ) ) {
					$result .= '<span style="color:green;">' . implode( ', ', $user_roles ) . '</span>';
				}
			}
			echo $result;
		}
	}

	/**
	 * add_bulk_and_quick_edit_fields.
	 *
	 * @version 3.5.4
	 * @since   3.5.4
	 */
	function add_bulk_and_quick_edit_fields() {
		$all_roles_options = '';
		$all_roles_options .= '<option value="wcj_no_change" selected>' . __( '— No change —', 'woocommerce' ) . '</option>';
		foreach ( wcj_get_user_roles_options() as $role_id => $role_desc ) {
			$all_roles_options .= '<option value="' . $role_id . '">' . $role_desc . '</option>';
		}
		?><br class="clear" />
		<label>
			<span class="title"><?php esc_html_e( 'User roles: Visible', 'woocommerce-jetpack' ); ?></span>
			<select multiple id="wcj_product_by_user_role_visible" name="wcj_product_by_user_role_visible[]">
				<?php echo $all_roles_options; ?>
			</select>
		</label><?php
	}

	/**
	 * save_bulk_and_quick_edit_fields.
	 *
	 * @version 3.5.4
	 * @since   3.5.4
	 */
	function save_bulk_and_quick_edit_fields( $post_id, $post ) {
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		// Don't save revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'product' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
		// Check nonce.
		if ( ! isset( $_REQUEST['woocommerce_quick_edit_nonce'] ) || ! wp_verify_nonce( $_REQUEST['woocommerce_quick_edit_nonce'], 'woocommerce_quick_edit_nonce' ) ) { // WPCS: input var ok, sanitization ok.
			return $post_id;
		}
		// Check bulk or quick edit.
		if ( ! empty( $_REQUEST['woocommerce_quick_edit'] ) ) { // WPCS: input var ok.
			if ( 'no' === get_option( 'wcj_product_by_user_role_admin_quick_edit', 'no' ) ) {
				return $post_id;
			}
		} else {
			if ( 'no' === apply_filters( 'booster_option', 'no', get_option( 'wcj_product_by_user_role_admin_bulk_edit', 'no' ) ) ) {
				return $post_id;
			}
		}
		// Save.
		if ( ! isset( $_REQUEST['wcj_product_by_user_role_visible'] ) ) {
			update_post_meta( $post_id, '_' . 'wcj_product_by_user_role_visible', array() );
		} elseif ( is_array( $_REQUEST['wcj_product_by_user_role_visible'] ) && ! in_array( 'wcj_no_change', $_REQUEST['wcj_product_by_user_role_visible'] ) ) {
			update_post_meta( $post_id, '_' . 'wcj_product_by_user_role_visible', $_REQUEST['wcj_product_by_user_role_visible'] );
		}
		return $post_id;
	}

	/**
	 * product_by_user_role_pre_get_posts.
	 *
	 * @version 3.5.4
	 * @since   2.6.0
	 */
	function product_by_user_role_pre_get_posts( $query ) {
		if ( is_admin() ) {
			return;
		}
		remove_action( 'pre_get_posts', array( $this, 'product_by_user_role_pre_get_posts' ) );
		$current_user_roles = wcj_get_current_user_all_roles();
		$post__not_in       = $query->get( 'post__not_in' );
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_' . 'wcj_product_by_user_role_visible',
					'value'   => '',
					'compare' => '!=',
				),
			),
		);
		$loop = new WP_Query( $args );
		foreach ( $loop->posts as $product_id ) {
			if ( ! $this->is_product_visible( $product_id, $current_user_roles ) ) {
				$post__not_in[] = $product_id;
			}
		}
		$query->set( 'post__not_in', $post__not_in );
		add_action( 'pre_get_posts', array( $this, 'product_by_user_role_pre_get_posts' ) );
	}

	/**
	 * product_by_user_role_purchasable.
	 *
	 * @version 3.5.4
	 * @since   2.6.0
	 */
	function product_by_user_role_purchasable( $purchasable, $_product ) {
		return ( ! $this->is_product_visible( wcj_get_product_id_or_variation_parent_id( $_product ), wcj_get_current_user_all_roles() ) ? false : $purchasable );
	}

	/**
	 * product_by_user_role_visibility.
	 *
	 * @version 3.5.4
	 * @since   2.5.5
	 */
	function product_by_user_role_visibility( $visible, $product_id ) {
		return ( ! $this->is_product_visible( $product_id, wcj_get_current_user_all_roles() ) ? false : $visible );
	}

	/**
	 * is_product_visible.
	 *
	 * @version 3.5.4
	 * @since   3.5.4
	 */
	function is_product_visible( $product_id, $current_user_roles ) {
		$visible_user_roles = get_post_meta( wcj_maybe_get_product_id_wpml( $product_id ), '_' . 'wcj_product_by_user_role_visible', true );
		if ( is_array( $visible_user_roles ) && ! empty( $visible_user_roles ) ) {
			$the_intersect = array_intersect( $visible_user_roles, $current_user_roles );
			if ( empty( $the_intersect ) ) {
				return false;
			}
		}
		return true;
	}

}

endif;

return new WCJ_Product_By_User_Role();

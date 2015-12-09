<?php
/**
 * WooCommerce Jetpack Old Slugs
 *
 * The WooCommerce Jetpack Old Slugs class.
 *
 * @version 2.3.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Old_Slugs' ) ) :

class WCJ_Old_Slugs extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.3.9
	 */
	public function __construct() {

		$this->id         = 'old_slugs';
		$this->short_desc = __( 'Old Slugs', 'woocommerce-jetpack' );
		$this->desc       = __( 'Remove old WooCommerce products slugs.', 'woocommerce-jetpack' );
		parent::__construct();

		$this->add_tools( array( 'old_slugs' => __( 'Remove Old Slugs', 'woocommerce-jetpack' ), ) );

		if ( $this->is_enabled() ) {
			if ( is_admin() ) {
				add_filter( 'wcj_tools_tabs',      array( $this, 'add_old_slugs_tool_tab' ), 100 );
				add_action( 'wcj_tools_old_slugs', array( $this, 'create_old_slugs_tool' ), 100 );
			}
		}
		add_action( 'wcj_tools_dashboard', array( $this, 'add_old_slugs_tool_info_to_tools_dashboard' ), 100 );
	}

	/**
	 * add_old_slugs_tool_info_to_tools_dashboard.
	 */
	public function add_old_slugs_tool_info_to_tools_dashboard() {
		echo '<tr>';
		if ( 'yes' === get_option( 'wcj_old_slugs_enabled') )
			$is_enabled = '<span style="color:green;font-style:italic;">' . __( 'enabled', 'woocommerce-jetpack' ) . '</span>';
		else
			$is_enabled = '<span style="color:gray;font-style:italic;">' . __( 'disabled', 'woocommerce-jetpack' ) . '</span>';
		echo '<td>' . __( 'Remove Old Slugs', 'woocommerce-jetpack' ) . '</td>';
		echo '<td>' . $is_enabled . '</td>';
		echo '<td>' . __( 'Tool removes old slugs/permalinks from database.', 'woocommerce-jetpack' ) . '</td>';
		echo '</tr>';
	}

	/**
	 * add_old_slugs_tool_tab.
	 */
	public function add_old_slugs_tool_tab( $tabs ) {
		$tabs[] = array(
			'id'    => 'old_slugs',
			'title' => __( 'Remove Old Slugs', 'woocommerce-jetpack' ),
		);
		return $tabs;
	}

	/*
	 * Add the settings.
	 *
	 * @version 2.3.9
	 */
	function get_settings() {
		$settings = array();
		$settings = $this->add_tools_list( $settings );
		return $this->add_enable_module_setting( $settings );
	}

	/**
	 * add_old_slugs_tool_tab.
	 *
	 * @version 2.3.9
	 */
	public function create_old_slugs_tool() {

		global $wpdb;
		$all_old_slugs = $wpdb->get_results( "SELECT * FROM wp_postmeta WHERE meta_key = '_wp_old_slug' ORDER BY post_id" );
		$num_old_slugs = count( $all_old_slugs );
		$remove_result_html = '';
		if ( $num_old_slugs > 0 ) {

			$posts_ids = array(
				'products'      => array(),
				'none_products' => array(),
			);
			$old_slugs_list = '<ol>';
			$old_slugs_list_products = '<ol>';
			foreach ( $all_old_slugs as $old_slug_object ) {
				$slug_post_type = get_post_type( $old_slug_object->post_id );
				$current_slug = get_post( $old_slug_object->post_id )->post_name;
				if ( $slug_post_type == 'product' ) {
					$old_slugs_list_products .= '<li><strong>' . $old_slug_object->meta_value . '</strong> (<em>post title:</em> ' . get_the_title( $old_slug_object->post_id ) . ', <em>post id:</em> '. $old_slug_object->post_id . ', <em>current slug:</em> ' . $current_slug . ')</li>';
					$posts_ids['products'][] = $old_slug_object->post_id;
				}
				else {
					$old_slugs_list .= '<li><strong>' . $old_slug_object->meta_value . '</strong> (<em>post title:</em> ' . get_the_title( $old_slug_object->post_id ) . ', <em>post id:</em> '.$old_slug_object->post_id . ', <em>post type:</em> ' . $slug_post_type . ', <em>current slug:</em> ' . $current_slug . ' )</li>';
					$posts_ids['none_products'][] = $old_slug_object->post_id;
				}
			}
			$old_slugs_list .= '</ol>';
			$old_slugs_list_products .= '</ol>';

			if ( ( isset( $_POST['remove_old_products_slugs'] ) ) || ( isset( $_POST['remove_old_none_products_slugs'] ) ) ) {

				if ( isset( $_POST['remove_old_products_slugs'] ) ) $post_ids_to_delete = join( ',', $posts_ids['products'] );
				else if ( isset( $_POST['remove_old_none_products_slugs'] ) ) $post_ids_to_delete = join( ',', $posts_ids['none_products'] );

				$the_delete_query = "DELETE FROM wp_postmeta WHERE meta_key = '_wp_old_slug' AND post_id IN ($post_ids_to_delete)";

				$delete_result = $wpdb->get_results( $the_delete_query );

				$recheck_result = $wpdb->get_results("SELECT * FROM wp_postmeta WHERE meta_key = '_wp_old_slug'");
				$recheck_result_count = count( $recheck_result );
				$remove_result_html = '<div class="updated"><p><strong>Removing old slugs from database finished! ' . ($num_old_slugs-$recheck_result_count) . ' old slug(s) deleted. Please <a href="">refresh</a> the page.</strong></p></div>';
			}
		}
		?><div>
			<h2><?php _e( 'WooCommerce Jetpack - Remove Old Product Slugs', 'woocommerce-jetpack' ); ?></h2>
			<p><?php _e( 'Tool removes old slugs/permalinks from database.', 'woocommerce-jetpack' ); ?></p>
			<?php echo $remove_result_html; ?>
			<?php
			$num_old_slugs_products = isset( $posts_ids['products'] ) ? count( $posts_ids['products'] ) : 0;
			if ( $num_old_slugs_products > 0 ) { ?>
				<h3><?php _e( 'Old products slugs found:', 'woocommerce-jetpack' ); ?> <?php echo $num_old_slugs_products; ?></h3>
				<p><?php echo $old_slugs_list_products; ?></p>
				<form method="post" action="">
					<input class="button-primary" type="submit" name="remove_old_products_slugs" value="Remove All Old Product Slugs"/>
				</form>
			<?php }
			$num_old_slugs_none_products = isset( $posts_ids['none_products'] ) ? count( $posts_ids['none_products'] ) : 0;
			if ( $num_old_slugs_none_products > 0 ) { ?>
				<h3><?php _e( 'None-products slugs found:', 'woocommerce-jetpack' ); ?> <?php echo $num_old_slugs_none_products; ?></h3>
				<p><?php echo $old_slugs_list; ?></p>
				<form method="post" action="">
					<input class="button-primary" type="submit" name="remove_old_none_products_slugs" value="Remove All Old None-Product Slugs"/>
				</form>
			<?php }
			if ( $num_old_slugs == 0 ) { ?>
				<div class="updated"><p><strong><?php _e( 'No old slugs found.', 'woocommerce-jetpack' ); ?></strong></p></div>
			<?php } ?>
		</div><?php
	}
}

endif;

return new WCJ_Old_Slugs();

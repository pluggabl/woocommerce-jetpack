<?php
/**
 * Booster for WooCommerce - Module - Old Slugs
 *
 * @version 2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Old_Slugs' ) ) :

class WCJ_Old_Slugs extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'old_slugs';
		$this->short_desc = __( 'Old Slugs', 'woocommerce-jetpack' );
		$this->desc       = __( 'Remove old WooCommerce products slugs.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-remove-old-products-slugs';
		parent::__construct();

		$this->add_tools( array(
			'old_slugs' => array(
				'title' => __( 'Remove Old Slugs', 'woocommerce-jetpack' ),
				'desc'  => __( 'Tool removes old slugs/permalinks from database.', 'woocommerce-jetpack' ),
			),
		) );
	}

	/**
	 * create_old_slugs_tool.
	 *
	 * @version 2.8.0
	 */
	function create_old_slugs_tool() {
		global $wpdb;
		$wp_postmeta_table  = $wpdb->prefix . 'postmeta';
		$all_old_slugs      = $wpdb->get_results( "SELECT * FROM $wp_postmeta_table WHERE meta_key = '_wp_old_slug' ORDER BY post_id" );
		$num_old_slugs      = count( $all_old_slugs );
		$remove_result_html = '';
		$headings = array(
			__( 'Old slug', 'woocommerce-jetpack' ),
			__( 'Post title', 'woocommerce-jetpack' ),
			__( 'Post id', 'woocommerce-jetpack' ),
			__( 'Post type', 'woocommerce-jetpack' ),
			__( 'Current slug', 'woocommerce-jetpack' ),
		);
		$multi_table_data   = array(
			'products'     => array( $headings ),
			'non_products' => array( $headings ),
		);
		$posts_ids          = array(
			'products'     => array(),
			'non_products' => array(),
		);
		if ( $num_old_slugs > 0 ) {
			// Fill `multi_table_data` and `posts_ids`
			foreach ( $all_old_slugs as $old_slug_object ) {
				$slug_post_type = get_post_type( $old_slug_object->post_id );
				$current_slug   = get_post( $old_slug_object->post_id )->post_name;
				$type           = ( in_array( $slug_post_type, array( 'product', 'product_variation' ) ) ) ? 'products' : 'non_products';
				$multi_table_data[ $type ][] = array(
					'<strong>' . $old_slug_object->meta_value . '</strong>',
					get_the_title( $old_slug_object->post_id ),
					$old_slug_object->post_id,
					$slug_post_type,
					$current_slug,
				);
				$posts_ids[ $type ][] = $old_slug_object->post_id;
			}
			// Actions
			if ( isset( $_POST['remove_old_products_slugs'] ) || isset( $_POST['remove_old_non_products_slugs'] ) ) {
				$post_ids_to_delete   = join( ',', ( isset( $_POST['remove_old_products_slugs'] ) ? $posts_ids['products'] : $posts_ids['non_products'] ) );
				$delete_result        = $wpdb->get_results( "DELETE FROM $wp_postmeta_table WHERE meta_key = '_wp_old_slug' AND post_id IN ($post_ids_to_delete)" );
				$recheck_result       = $wpdb->get_results( "SELECT * FROM $wp_postmeta_table WHERE meta_key = '_wp_old_slug'" );
				$recheck_result_count = count( $recheck_result );
				$remove_result_html   = '<div class="updated"><p>' .
					sprintf(
						__( 'Removing old slugs from database finished! <strong>%d</strong> old slug(s) deleted.', 'woocommerce-jetpack' ),
						( $num_old_slugs - $recheck_result_count )
					) . ' ' . __( 'Please <a href="">refresh</a> the page.', 'woocommerce-jetpack' ) .
				'</p></div>';
			}
		}
		$this->output_old_slugs_tool( $remove_result_html, $multi_table_data, $num_old_slugs, $posts_ids );
	}

	/**
	 * output_old_slugs_tool.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function output_old_slugs_tool( $remove_result_html, $multi_table_data, $num_old_slugs, $posts_ids ) {
		$html = '';
		$html .= '<div class="wrap">';
		$html .= $this->get_tool_header_html( 'old_slugs' );
		$html .= $remove_result_html;
		$types = array(
			'products' => array(
				'table_heading' => __( 'Old products slugs found:', 'woocommerce-jetpack' ),
				'table_content' => wcj_get_table_html( $multi_table_data['products'], array( 'table_class' => 'widefat striped' ) ),
				'button_name'   => 'remove_old_products_slugs',
				'button_label'  => __( 'Remove all old product slugs', 'woocommerce-jetpack' ),
			),
			'non_products' => array(
				'table_heading' => __( 'Old non-products slugs found:', 'woocommerce-jetpack' ),
				'table_content' => wcj_get_table_html( $multi_table_data['non_products'], array( 'table_class' => 'widefat striped' ) ),
				'button_name'   => 'remove_old_non_products_slugs',
				'button_label'  => __( 'Remove all old non-product slugs', 'woocommerce-jetpack' ),
			),
		);
		foreach ( $types as $type_id => $type ) {
			$_num_old_slugs = isset( $posts_ids[ $type_id ] ) ? count( $posts_ids[ $type_id ] ) : 0;
			if ( $_num_old_slugs > 0 ) {
				$html .= '<h4>' . $type['table_heading'] . ' ' . $_num_old_slugs . '</h4>';
				$html .= '<p>' . $type['table_content'] . '</p>';
				$html .= '<form method="post" action="">';
				$html .= '<input class="button-primary" type="submit" name="' . $type['button_name'] . '" value="' . $type['button_label'] . '"/>';
				$html .= '</form>';
			}
		}
		if ( $num_old_slugs == 0 ) {
			$html .= '<div class="updated"><p><strong>' . __( 'No old slugs found.', 'woocommerce-jetpack' ) . '</strong></p></div>';
		}
		$html .= '</div>';
		echo $html;
	}
}

endif;

return new WCJ_Old_Slugs();

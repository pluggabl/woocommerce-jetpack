<?php
/**
 * WooCommerce Jetpack General
 *
 * The WooCommerce Jetpack General class.
 *
 * @version 2.3.10
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_General' ) ) :

class WCJ_General extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.3.10
	 */
	public function __construct() {

		$this->id         = 'general';
		$this->short_desc = __( 'General', 'woocommerce-jetpack' );
		$this->desc       = __( 'Separate custom CSS for front and back end. Shortcodes in Wordpress text widgets.', 'woocommerce-jetpack' );
		parent::__construct();

		$this->add_tools( array(
			'products_atts'    => array(
				'title' => __( 'Products Atts', 'woocommerce-jetpack' ),
				'desc'  => __( 'All Products and All Attributes.', 'woocommerce-jetpack' ),
			),
			'export_customers' => array(
				'title' => __( 'Export Customers', 'woocommerce-jetpack' ),
				'desc'  => __( 'Export Customers (extracted from orders).', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {

			if ( 'yes' === get_option( 'wcj_general_shortcodes_in_text_widgets_enabled' ) ) {
				add_filter( 'widget_text', 'do_shortcode' );
			}

			if ( '' != get_option( 'wcj_general_custom_css' ) ) {
				add_action( 'wp_head', array( $this, 'hook_custom_css' ) );
			}
			if ( '' != get_option( 'wcj_general_custom_admin_css' ) ) {
				add_action( 'admin_head', array( $this, 'hook_custom_admin_css' ) );
			}
		}
	}

	/**
	 * create_export_customers_tool.
	 *
	 * @version 2.3.9
	 * @since   2.3.9
	 */
	function create_export_customers_tool() {
		$html = '';
		$html .= '<pre>';
		$html .=
			__( 'Nr.', 'woocommerce-jetpack' ) . ',' .
			__( 'Email', 'woocommerce-jetpack' ) . ',' .
			__( 'First Name', 'woocommerce-jetpack' ) . ',' .
			__( 'Last Name', 'woocommerce-jetpack' ) . ',' .
			__( 'Order Date', 'woocommerce-jetpack' ) . PHP_EOL;
		$total_customers = 0;
		$orders = array();
		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args_orders = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
			);
			$loop_orders = new WP_Query( $args_orders );
			if ( ! $loop_orders->have_posts() ) break;
			while ( $loop_orders->have_posts() ) : $loop_orders->the_post();
				$order_id = $loop_orders->post->ID;
				$order = wc_get_order( $order_id );
				if ( isset( $order->billing_email ) && '' != $order->billing_email && ! in_array( $order->billing_email, $orders ) ) {
					$emails_to_skip = array();
					if ( ! in_array( $order->billing_email, $emails_to_skip ) ) {
						$total_customers++;
						$html .= $total_customers . ',' . $order->billing_email . ',' . $order->billing_first_name . ','. $order->billing_last_name . ','. get_the_date( 'Y/m/d' ) . PHP_EOL;
						$orders[] = $order->billing_email;
					}
				}
			endwhile;
			$offset += $block_size;
		}
		$html .= '</pre>';
		echo $html;
	}

	/**
	 * create_products_atts_tool.
	 *
	 * @version 2.3.9
	 * @since   2.3.9
	 */
	function create_products_atts_tool() {
		$html = '';
		$html .= $this->get_products_atts();
		echo $html;
	}

	/*
	 * get_products_atts.
	 *
	 * @version 2.3.9
	 * @since   2.3.9
	 */
	function get_products_atts() {

		$total_products = 0;

		$products_attributes = array();
		$attributes_names = array();
		$attributes_names['wcj_title']    = __( 'Product', 'woocommerce-jetpack' );
		$attributes_names['wcj_category'] = __( 'Category', 'woocommerce-jetpack' );

		$offset = 0;
		$block_size = 96;
		while( true ) {

			$args_products = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $block_size,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'offset'         => $offset,
			);
			$loop_products = new WP_Query( $args_products );
			if ( ! $loop_products->have_posts() ) break;
			while ( $loop_products->have_posts() ) : $loop_products->the_post();

				$total_products++;
				$product_id = $loop_products->post->ID;
				$the_product = wc_get_product( $product_id );

				$products_attributes[ $product_id ]['wcj_title']    = '<a href="' . get_permalink( $product_id ) . '">' . $the_product->get_title() . '</a>';
				$products_attributes[ $product_id ]['wcj_category'] = $the_product->get_categories();

				foreach ( $the_product->get_attributes() as $attribute ) {
					$products_attributes[ $product_id ][ $attribute['name'] ] = $the_product->get_attribute( $attribute['name'] );
					if ( ! isset( $attributes_names[ $attribute['name'] ] ) ) {
						$attributes_names[ $attribute['name'] ] = wc_attribute_label( $attribute['name'] );
					}
				}

			endwhile;

			$offset += $block_size;

		}

		$table_data = array();
		if ( isset( $_GET['wcj_attribute'] ) && '' != $_GET['wcj_attribute'] ) {
			$table_data[] = array(
				__( 'Product', 'woocommerce-jetpack' ),
				__( 'Category', 'woocommerce-jetpack' ),
				$_GET['wcj_attribute'],
			);
		} else {
//			$table_data[] = array_values( $attributes_names );
			$table_data[] = array_keys( $attributes_names );
		}
		foreach ( $attributes_names as $attributes_name => $attribute_title ) {

			if ( isset( $_GET['wcj_attribute'] ) && '' != $_GET['wcj_attribute'] ) {
				if ( 'wcj_title' != $attributes_name && 'wcj_category' != $attributes_name && $_GET['wcj_attribute'] != $attributes_name ) {
					continue;
				}
			}

			foreach ( $products_attributes as $product_id => $product_attributes ) {
				$table_data[ $product_id ][ $attributes_name ] = isset( $product_attributes[ $attributes_name ] ) ? $product_attributes[ $attributes_name ] : '';
			}
		}

		return '<p>' . __( 'Total Products:', 'woocommerce-jetpack' ) . ' ' . $total_products . '</p>' . wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) );
	}

	/**
	 * hook_custom_css.
	 */
	public function hook_custom_css() {
		$output = '<style>' . get_option( 'wcj_general_custom_css' ) . '</style>';
		echo $output;
	}

	/**
	 * hook_custom_admin_css.
	 */
	public function hook_custom_admin_css() {
		$output = '<style>' . get_option( 'wcj_general_custom_admin_css' ) . '</style>';
		echo $output;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.3.9
	 */
	function get_settings() {

		/* $links_html = '';
		if ( class_exists( 'RecursiveIteratorIterator' ) && class_exists( 'RecursiveDirectoryIterator' ) ) {
			$dir = untrailingslashit( realpath( plugin_dir_path( __FILE__ ) . '/../../woocommerce/templates' ) );
			$rii = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) );
			foreach ( $rii as $file ) {
				$the_name = str_replace( $dir, '', $file->getPathname() );
				$the_name_link = str_replace( DIRECTORY_SEPARATOR, '%2F', $the_name );
				if ( $file->isDir() ) {
					/* $links_html .= '<strong>' . $the_name . '</strong>' . PHP_EOL; *//*
				} else {
					$links_html .= '<a href="' . get_admin_url( null, 'plugin-editor.php?file=woocommerce' . '%2F' . 'templates' . $the_name_link . '&plugin=woocommerce' ) . '">' .
							'templates' . $the_name . '</a>' . PHP_EOL;
				}
			}
		} else {
			$links_html = __( 'PHP 5 is required.', 'woocommerce-jetpack' );
		} */

		$settings = array(

			array(
				'title'   => __( 'Shortcodes Options', 'woocommerce-jetpack' ),
				'type'    => 'title',
				'desc'    => '',
				'id'      => 'wcj_general_shortcodes_options',
			),

			array(
				'title'   => __( 'Enable Shortcodes in WordPress Text Widgets', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => 'wcj_general_shortcodes_in_text_widgets_enabled',
				'default' => 'no',
				'type'    => 'checkbox',
			),

			array(
				'type'    => 'sectionend',
				'id'      => 'wcj_general_shortcodes_options',
			),

			array(
				'title'   => __( 'Custom CSS Options', 'woocommerce-jetpack' ),
				'type'    => 'title',
				'desc'    => __( 'Another custom CSS, if you need one.', 'woocommerce-jetpack' ),
				'id'      => 'wcj_general_custom_css_options',
			),

			array(
				'title'   => __( 'Custom CSS - Front end (Customers)', 'woocommerce-jetpack' ),
				'id'      => 'wcj_general_custom_css',
				'default' => '',
				'type'    => 'custom_textarea',
				'css'     => 'width:66%;min-width:300px;min-height:300px;',
			),

			array(
				'title'   => __( 'Custom CSS - Back end (Admin)', 'woocommerce-jetpack' ),
				'id'      => 'wcj_general_custom_admin_css',
				'default' => '',
				'type'    => 'custom_textarea',
				'css'     => 'width:66%;min-width:300px;min-height:300px;',
			),

			array(
				'type'    => 'sectionend',
				'id'      => 'wcj_general_custom_css_options',
			),

			/* array(
				'title'   => __( 'WooCommerce Templates Editor Links', 'woocommerce-jetpack' ),
				'type'    => 'title',
				'id'      => 'wcj_general_wc_templates_editor_links_options',
			),

			array(
				'title'   => __( 'Templates', 'woocommerce-jetpack' ),
				'id'      => 'wcj_general_wc_templates_editor_links',
				'type'    => 'custom_link',
				'link'    => '<pre>' . $links_html . '</pre>',
			),

			array(
				'type'    => 'sectionend',
				'id'      => 'wcj_general_wc_templates_editor_links_options',
			), */
		);

		$settings = $this->add_tools_list( $settings );

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_General();

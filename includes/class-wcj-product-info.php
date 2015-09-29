<?php
/**
 * WooCommerce Jetpack Product Info
 *
 * The WooCommerce Jetpack Product Info class.
 *
 * @version 2.2.6
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_Info' ) ) :

class WCJ_Product_Info {

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Product archives filters array
		$this->product_info_on_archive_filters_array = array(
			'woocommerce_before_shop_loop_item'				=> __( 'Before product', 'woocommerce-jetpack' ),
			'woocommerce_before_shop_loop_item_title'		=> __( 'Before product title', 'woocommerce-jetpack' ),
			'woocommerce_after_shop_loop_item'				=> __( 'After product', 'woocommerce-jetpack' ),
			'woocommerce_after_shop_loop_item_title'		=> __( 'After product title', 'woocommerce-jetpack' ),
		);

		// Single product filters array
		$this->product_info_on_single_filters_array = array(
			'woocommerce_single_product_summary'			=> __( 'Inside single product summary', 'woocommerce-jetpack' ),
			'woocommerce_before_single_product_summary'		=> __( 'Before single product summary', 'woocommerce-jetpack' ),
			'woocommerce_after_single_product_summary'		=> __( 'After single product summary', 'woocommerce-jetpack' ),
		);

		// List of product info short codes
		$this->product_info_shortcodes_array = array(
			'%sku%',
			'%title%',
			'%weight%',
			'%sale_price%',
			'%regular_price_if_on_sale%',
			'%regular_price%',
			'%price%',
			'%price_including_tax%',
			'%price_excluding_tax%',
			'%tax_class%',
			'%average_rating%',
			'%categories%',
			'%shipping_class%',
			'%dimensions%',
			'%formatted_name%',
			'%stock_availability%',
			'%total_sales%',
			'%you_save%',
			'%you_save_percent%',
			'%sale_price_formatted%',
			'%regular_price_if_on_sale_formatted%',
			'%regular_price_formatted%',
			'%price_formatted%',
			'%price_including_tax_formatted%',
			'%price_excluding_tax_formatted%',
			'%you_save_formatted%',
			'%time_since_last_sale%',
			//'%available_variations%',
			'%list_attributes%',
			'%stock_quantity%',
			'%list_attribute%',
		);

		// Main hooks
		if ( 'yes' === get_option( 'wcj_product_info_enabled' ) ) {

			// Product Info
			$this->add_product_info_filters( 'archive' );
			$this->add_product_info_filters( 'single' );



			// Shortcodes
			add_shortcode( 'wcj_sku', 									array( $this, 'shortcode_product_info_sku' ) );
			add_shortcode( 'wcj_title', 								array( $this, 'shortcode_product_info_title' ) );
			add_shortcode( 'wcj_weight', 								array( $this, 'shortcode_product_info_weight' ) );
			add_shortcode( 'wcj_sale_price', 							array( $this, 'shortcode_product_info_sale_price' ) );
			add_shortcode( 'wcj_regular_price_if_on_sale', 				array( $this, 'shortcode_product_info_regular_price_if_on_sale' ) );
			add_shortcode( 'wcj_regular_price', 						array( $this, 'shortcode_product_info_regular_price' ) );
			add_shortcode( 'wcj_price', 								array( $this, 'shortcode_product_info_price' ) );
			add_shortcode( 'wcj_price_including_tax', 					array( $this, 'shortcode_product_info_price_including_tax' ) );
			add_shortcode( 'wcj_price_excluding_tax', 					array( $this, 'shortcode_product_info_price_excluding_tax' ) );
			add_shortcode( 'wcj_tax_class', 							array( $this, 'shortcode_product_info_tax_class' ) );
			add_shortcode( 'wcj_average_rating', 						array( $this, 'shortcode_product_info_average_rating' ) );
			add_shortcode( 'wcj_categories', 							array( $this, 'shortcode_product_info_categories' ) );
			add_shortcode( 'wcj_shipping_class', 						array( $this, 'shortcode_product_info_shipping_class' ) );
			add_shortcode( 'wcj_dimensions', 							array( $this, 'shortcode_product_info_dimensions' ) );
			add_shortcode( 'wcj_formatted_name', 						array( $this, 'shortcode_product_info_formatted_name' ) );
			add_shortcode( 'wcj_stock_availability', 					array( $this, 'shortcode_product_info_stock_availability' ) );
			add_shortcode( 'wcj_total_sales', 							array( $this, 'shortcode_product_info_total_sales' ) );
			add_shortcode( 'wcj_you_save', 								array( $this, 'shortcode_product_info_you_save' ) );
			add_shortcode( 'wcj_you_save_percent', 						array( $this, 'shortcode_product_info_you_save_percent' ) );
			add_shortcode( 'wcj_sale_price_formatted', 					array( $this, 'shortcode_product_info_sale_price_formatted' ) );
			add_shortcode( 'wcj_regular_price_if_on_sale_formatted', 	array( $this, 'shortcode_product_info_regular_price_if_on_sale_formatted' ) );
			add_shortcode( 'wcj_regular_price_formatted', 				array( $this, 'shortcode_product_info_regular_price_formatted' ) );
			add_shortcode( 'wcj_price_formatted', 						array( $this, 'shortcode_product_info_price_formatted' ) );
			add_shortcode( 'wcj_price_including_tax_formatted', 		array( $this, 'shortcode_product_info_price_including_tax_formatted' ) );
			add_shortcode( 'wcj_price_excluding_tax_formatted', 		array( $this, 'shortcode_product_info_price_excluding_tax_formatted' ) );
			add_shortcode( 'wcj_you_save_formatted', 					array( $this, 'shortcode_product_info_you_save_formatted' ) );
			add_shortcode( 'wcj_time_since_last_sale', 					array( $this, 'shortcode_product_info_time_since_last_sale' ) );
			add_shortcode( 'wcj_available_variations', 					array( $this, 'shortcode_product_info_available_variations' ) );
			add_shortcode( 'wcj_list_attributes', 						array( $this, 'shortcode_product_info_list_attributes' ) );
			add_shortcode( 'wcj_stock_quantity', 						array( $this, 'shortcode_product_info_stock_quantity' ) );
			add_shortcode( 'wcj_list_attribute', 						array( $this, 'shortcode_product_info_list_attribute' ) );

			// Depreciated
			add_shortcode( 'wcjp_list_attribute', 						array( $this, 'shortcode_wcjp_list_attribute' ) );


		}

		// Settings hooks
		add_filter( 'wcj_settings_sections',     array( $this, 'settings_section' ) );
		add_filter( 'wcj_settings_product_info', array( $this, 'get_settings' ), 100 );
		add_filter( 'wcj_features_status',       array( $this, 'add_enabled_option' ), 100 );
	}


	/**
	 * shortcode_wcjp_list_attribute.
	 */
	public function shortcode_wcjp_list_attribute( $atts ) {
		$atts_array = shortcode_atts( array(
			'attribute_name' 	=> '',
			'before' 			=> '',
			'after' 			=> '',
			'visibility' 		=> '',
		), $atts );
		global $product;
		if ( '' != $atts_array['attribute_name'] && $product && '' != $product->get_attribute( $atts_array['attribute_name'] ) ) {
			if ( 'admin' === $atts_array['visibility'] && ! is_super_admin() )
				return '';
			return $atts_array['before'] . $product->get_attribute( $atts_array['attribute_name'] ) . $atts_array['after'];
		}
		return '';
	}

	/**
	 * Shortcodes.
	 */
	public function get_shortcode( $shortcode, $atts ) {
		$atts = shortcode_atts( array(
			'before' 			=> '',
			'after' 			=> '',
			'visibility' 		=> '',
			'id' 		        => 0,
			'options' 			=> '',
		), $atts, $shortcode );
		if ( 'admin' === $atts['visibility'] && ! is_super_admin() )
			return '';
		if ( '' != ( $result = $this->get_product_info_short_code( $shortcode, $atts['id'], $atts['options'] ) ) )
			return $atts['before'] . $result . $atts['after'];
		return '';
	}

	public function shortcode_product_info_sku( $atts ) {
		return $this->get_shortcode( '%sku%', $atts );
	}

	public function shortcode_product_info_title( $atts ) {
		return $this->get_shortcode( '%title%', $atts );
	}

	public function shortcode_product_info_weight( $atts ) {
		return $this->get_shortcode( '%weight%', $atts );
	}

	public function shortcode_product_info_sale_price( $atts ) {
		return $this->get_shortcode( '%sale_price%', $atts );
	}

	public function shortcode_product_info_regular_price_if_on_sale( $atts ) {
		return $this->get_shortcode( '%regular_price_if_on_sale%', $atts );
	}

	public function shortcode_product_info_regular_price( $atts ) {
		return $this->get_shortcode( '%regular_price%', $atts );
	}

	public function shortcode_product_info_price( $atts ) {
		return $this->get_shortcode( '%price%', $atts );
	}

	public function shortcode_product_info_price_including_tax( $atts ) {
		return $this->get_shortcode( '%price_including_tax%', $atts );
	}

	public function shortcode_product_info_price_excluding_tax( $atts ) {
		return $this->get_shortcode( '%price_excluding_tax%', $atts );
	}

	public function shortcode_product_info_tax_class( $atts ) {
		return $this->get_shortcode( '%tax_class%', $atts );
	}

	public function shortcode_product_info_average_rating( $atts ) {
		return $this->get_shortcode( '%average_rating%', $atts );
	}

	public function shortcode_product_info_categories( $atts ) {
		return $this->get_shortcode( '%categories%', $atts );
	}

	public function shortcode_product_info_shipping_class( $atts ) {
		return $this->get_shortcode( '%shipping_class%', $atts );
	}

	public function shortcode_product_info_dimensions( $atts ) {
		return $this->get_shortcode( '%dimensions%', $atts );
	}

	public function shortcode_product_info_formatted_name( $atts ) {
		return $this->get_shortcode( '%formatted_name%', $atts );
	}

	public function shortcode_product_info_stock_availability( $atts ) {
		return $this->get_shortcode( '%stock_availability%', $atts );
	}

	public function shortcode_product_info_total_sales( $atts ) {
		return $this->get_shortcode( '%total_sales%', $atts );
	}

	public function shortcode_product_info_you_save( $atts ) {
		return $this->get_shortcode( '%you_save%', $atts );
	}

	public function shortcode_product_info_you_save_percent( $atts ) {
		return $this->get_shortcode( '%you_save_percent%', $atts );
	}

	public function shortcode_product_info_sale_price_formatted( $atts ) {
		return $this->get_shortcode( '%sale_price_formatted%', $atts );
	}

	public function shortcode_product_info_regular_price_if_on_sale_formatted( $atts ) {
		return $this->get_shortcode( '%regular_price_if_on_sale_formatted%', $atts );
	}

	public function shortcode_product_info_regular_price_formatted( $atts ) {
		return $this->get_shortcode( '%regular_price_formatted%', $atts );
	}

	public function shortcode_product_info_price_formatted( $atts ) {
		return $this->get_shortcode( '%price_formatted%', $atts );
	}

	public function shortcode_product_info_price_including_tax_formatted( $atts ) {
		return $this->get_shortcode( '%price_including_tax_formatted%', $atts );
	}

	public function shortcode_product_info_price_excluding_tax_formatted( $atts ) {
		return $this->get_shortcode( '%price_excluding_tax_formatted%', $atts );
	}

	public function shortcode_product_info_you_save_formatted( $atts ) {
		return $this->get_shortcode( '%you_save_formatted%', $atts );
	}

	public function shortcode_product_info_time_since_last_sale( $atts ) {
		return $this->get_shortcode( '%time_since_last_sale%', $atts );
	}

	/*public function shortcode_product_info_available_variations( $atts ) {
		return $this->get_shortcode( '%available_variations%', $atts );
	}*/

	public function shortcode_product_info_list_attributes( $atts ) {
		return $this->get_shortcode( '%list_attributes%', $atts );
	}

	public function shortcode_product_info_stock_quantity( $atts ) {
		return $this->get_shortcode( '%stock_quantity%', $atts );
	}

	public function shortcode_product_info_list_attribute( $atts ) {
		return $this->get_shortcode( '%list_attribute%', $atts );
	}

	/**
	 * list_short_codes.
	 */
	public function list_short_codes() {
		//return __( 'Available shortcodes are:', 'woocommerce-jetpack' ) . ' ' . implode( ", ", $this->product_info_shortcodes_array );
		return __( 'Available shortcodes are:', 'woocommerce-jetpack' ) . '<ul><li>' . implode( '</li><li>', $this->product_info_shortcodes_array ) . '</li></ul>';
	}

	/**
	 * add_product_info_filters.
	 */
	public function add_product_info_filters( $single_or_archive ) {
		// Product Info
		if ( ( 'yes' === get_option( 'wcj_product_info_on_' . $single_or_archive . '_enabled' ) ) &&
			 ( '' != get_option( 'wcj_product_info_on_' . $single_or_archive ) ) &&
			 ( '' != get_option( 'wcj_product_info_on_' . $single_or_archive . '_filter' ) ) &&
			 ( '' != get_option( 'wcj_product_info_on_' . $single_or_archive . '_filter_priority' ) ) )
				add_action( get_option( 'wcj_product_info_on_' . $single_or_archive . '_filter' ), array( $this, 'product_info' ), get_option( 'wcj_product_info_on_' . $single_or_archive . '_filter_priority' ) );
		// More product Info
		if ( 'yes' === get_option( 'wcj_more_product_info_on_' . $single_or_archive . '_enabled' ) ) {
				add_action( get_option( 'wcj_more_product_info_on_' . $single_or_archive . '_filter' ), array( $this, 'more_product_info' ), get_option( 'wcj_more_product_info_on_' . $single_or_archive . '_filter_priority' ) );
		}
	}

	/**
	 * product_info.
	 */
	public function product_info() {
		$the_action_name = current_filter();
		if ( array_key_exists( $the_action_name, $this->product_info_on_archive_filters_array ) ) {
			$the_product_info = get_option( 'wcj_product_info_on_archive' );
			$this->apply_product_info_short_codes( $the_product_info, false );
		}
		else if ( array_key_exists( $the_action_name, $this->product_info_on_single_filters_array ) ) {
			$the_product_info = get_option( 'wcj_product_info_on_single' );
			$this->apply_product_info_short_codes( $the_product_info, false );
		}
	}

	/**
	 * more_product_info.
	 */
	public function more_product_info() {
		$the_action_name = current_filter();
		if ( array_key_exists( $the_action_name, $this->product_info_on_archive_filters_array ) )
			$this->add_more_product_info( 'archive' );
		else if ( array_key_exists( $the_action_name, $this->product_info_on_single_filters_array ) )
			$this->add_more_product_info( 'single' );
	}

	/**
	 * add_more_product_info.
	 */
	public function add_more_product_info( $single_or_archive ) {
		//$single_or_archive = 'archive';
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 4, get_option( 'wcj_more_product_info_on_' . $single_or_archive . '_fields_total', 4 ) ); $i++ ) {
			$field_id = 'wcj_more_product_info_on_' . $single_or_archive . '_' . $i ;
			$the_product_info = get_option( $field_id );
			$this->apply_product_info_short_codes( $the_product_info, true );
		}
	}

	/**
	 * apply_product_info_short_codes.
	 */
	public function apply_product_info_short_codes( $the_product_info, $remove_on_empty ) {

		$product_ids_to_exclude = get_option( 'wcj_product_info_products_to_exclude', '' );
		if ( '' != $product_ids_to_exclude ) {
			$product_ids_to_exclude = str_replace( ' ', '', $product_ids_to_exclude );
			$product_ids_to_exclude = explode( ',', $product_ids_to_exclude );
			$product_id = get_the_ID();
			if ( ! empty( $product_ids_to_exclude ) && is_array( $product_ids_to_exclude ) && in_array( $product_id, $product_ids_to_exclude ) )
				return;
		}

		if ( '' == $the_product_info )
			return;

		foreach ( $this->product_info_shortcodes_array as $product_info_short_code ) {
			if ( false !== strpos( $the_product_info, $product_info_short_code ) ) {
				// We found short code in the text
				$replace_with_phrase = $this->get_product_info_short_code( $product_info_short_code );
				if ( false === $replace_with_phrase && true === $remove_on_empty ) {
					// No phrase to replace exists, then empty the text and continue with next field
					$the_product_info = '';
					return;
				}
				else {
					if ( false === $replace_with_phrase ) $replace_with_phrase = '';
					// Replacing the short code
					$the_product_info = str_replace( $product_info_short_code, $replace_with_phrase, $the_product_info );
				}
			}
		}

		echo apply_filters( 'the_content', $the_product_info );
	}

	/**
	 * get_product_info.
	 */
	public function get_product_info_short_code( $short_code, $id = 0, $options = null ) {


		if ( 0 != $id ) {
			$product = wc_get_product( $id );
		}
		else {
			//$product = wc_get_product();
			if ( ! array_key_exists( 'product', $GLOBALS ) )
				return '';
			global $product;
		}

		if ( ! $product )
			return '';

		if ( '%list_attribute%' == $short_code && empty( $options ) )
			return '';

		switch( $short_code ) {

			case '%list_attribute%':
				return $product->get_attribute( $options );

			case '%sku%':
				return $product->get_sku();

			case '%sku%':
				return $product->get_sku();

			case '%title%':
				return $product->get_title();

			case '%weight%':
				if ( $product->has_weight() )
					return $product->get_weight();
				else
					return false;

			case '%sale_price%':
				if ( $product->is_on_sale() )
					return $product->get_sale_price();
				else
					return false;

			case '%regular_price_if_on_sale%':
				if ( $product->is_on_sale() )
					return $product->get_regular_price();
				else
					return false;

			case '%regular_price%':
				return $product->get_regular_price();

			case '%price%':
				return $product->get_price();

			case '%price_including_tax%':
				return $product->get_price_including_tax();

			case '%price_excluding_tax%':
				return $product->get_price_excluding_tax();

			case '%tax_class%':
				return $product->get_tax_class();

			case '%average_rating%':
				return $product->get_average_rating();

			case '%categories%':
				return $product->get_categories();

			case '%shipping_class%':
				return $product->get_shipping_class();

			case '%dimensions%':
				if ( $product->has_dimensions() )
					return $product->get_dimensions();
				else
					return false;

			case '%formatted_name%':
				return $product->get_formatted_name();

			case '%stock_availability%':
				$stock_availability_array = $product->get_availability();
				if ( isset( $stock_availability_array['availability'] ) )
					return $stock_availability_array['availability'];
				else
					return false;

			case '%total_sales%':
				$product_custom_fields = get_post_custom( $product->id );
				if ( isset( $product_custom_fields['total_sales'][0] ) )
					return $product_custom_fields['total_sales'][0];
				else
					return false;

			case '%you_save%':
				if ( $product->is_on_sale() )
					return ( $product->get_regular_price() - $product->get_sale_price() );
				else
					return false;

			case '%you_save_percent%':
				if ( $product->is_on_sale() ) {
					if ( 0 != $product->get_regular_price() ) {
						$you_save = ( $product->get_regular_price() - $product->get_sale_price() );
						return intval( $you_save / $product->get_regular_price() * 100 );
					}
					else
						return false;
				}
				else
					return false;

			case '%sale_price_formatted%':
				if ( $product->is_on_sale() )
					return wc_price( $product->get_sale_price() );
				else
					return false;

			case '%regular_price_if_on_sale_formatted%':
				if ( $product->is_on_sale() )
					return wc_price( $product->get_regular_price() );
				else
					return false;

			case '%regular_price_formatted%':
				return wc_price( $product->get_regular_price() );

			case '%price_formatted%':
				return wc_price( $product->get_price() );

			case '%price_including_tax_formatted%':
				return wc_price( $product->get_price_including_tax() );

			case '%price_excluding_tax_formatted%':
				return wc_price( $product->get_price_excluding_tax() );

			case '%you_save_formatted%':
				if ( $product->is_on_sale() )
					return wc_price( $product->get_regular_price() - $product->get_sale_price() );
				else
					return false;

			case '%time_since_last_sale%':
				return $this->get_time_since_last_sale();

			case '%list_attributes%':
				if ( $product->has_attributes() )
					return $product->list_attributes();
				else
					return false;

			case '%stock_quantity%':
				$stock_quantity = $product->get_stock_quantity();
				if ( '' != $stock_quantity )
					return $stock_quantity;
				else
					return false;

			// Not finished!
			case '%available_variations%':
				if ( $product->is_type( 'variable' ) )
					return print_r( $product->get_available_variations(), true );
				else
					return false;

			default:
				return false;
		}

		return false;
	}

	/**
	 * get_time_since_last_sale.
	 *
	 * @version 2.2.6
	 */
	public function get_time_since_last_sale() {
		// Constants
		$days_to_cover = 90;
		$do_use_only_completed_orders = true;
		// Get the ID before new query
		$the_ID = get_the_ID();
		// Create args for new query
		$args = array(
			'post_type'			=> 'shop_order',
			'post_status' 		=> ( true === $do_use_only_completed_orders ? 'wc-completed' : 'any' ),
			'posts_per_page' 	=> -1,
			'orderby'			=> 'date',
			'order'				=> 'DESC',
			'date_query' 		=> array( array( 'after'   => strtotime( '-' . $days_to_cover . ' days' ) ) ),
		);
		// Run new query
		$loop = new WP_Query( $args );
		// Analyze the results, i.e. orders
		while ( $loop->have_posts() ) : $loop->the_post();
			$order = new WC_Order( $loop->post->ID );
			$items = $order->get_items();
			foreach ( $items as $item ) {
				// Run through all order's items
				if ( $item['product_id'] == $the_ID ) {
					// Found sale!
					$result = sprintf( __( '%s ago', 'woocommerce-jetpack' ), human_time_diff( get_the_time('U'), current_time('timestamp') ) );
					//wp_reset_query();
					wp_reset_postdata();
					return $result;
				}
			}
		endwhile;
		//wp_reset_query();
		wp_reset_postdata();
		// No sales found
		return false;
	}



	// ADMIN //

	/**
	 * admin_add_product_info_fields_with_header.
	 */
	function admin_add_product_info_fields_with_header( &$settings, $single_or_archive, $title, $filters_array ) {
		$settings = array_merge( $settings, array(
			array(
				'title' 	=> $title,//__( 'Product Info on Archive Pages', 'woocommerce-jetpack' ),
				'desc' 		=> __( 'Enable', 'woocommerce-jetpack' ),
				'id' 		=> 'wcj_more_product_info_on_' . $single_or_archive . '_enabled',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
			),

			array(
				'title'    => '',
				'desc'     => __( 'Position', 'woocommerce-jetpack' ),
				'id'       => 'wcj_more_product_info_on_' . $single_or_archive . '_filter',
				'css'      => 'min-width:350px;',
				'class'    => 'chosen_select',
				'default'  => 'woocommerce_after_shop_loop_item_title',
				'type'     => 'select',
				'options'  => $filters_array,// $this->product_info_on_archive_filters_array,
				'desc_tip' => true,
			),

			array(
				'title'    => '',
				'desc_tip'    => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ),
				'id'       => 'wcj_more_product_info_on_' . $single_or_archive . '_filter_priority',
				'default'  => 10,
				'type'     => 'number',
			),

			array(
				'title' 	=> '',
				'desc_tip' 	=> __( 'Number of product info fields. Click "Save changes" after you change this number.', 'woocommerce-jetpack' ),
				'id' 		=> 'wcj_more_product_info_on_' . $single_or_archive . '_fields_total',
				'default'	=> 4,
				'type' 		=> 'number',
				'desc' 	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'
						   => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
		) );

		$this->admin_add_product_info_fields( $settings, $single_or_archive );
	}

	/**
	 * admin_add_product_info_fields.
	 */
	function admin_add_product_info_fields( &$settings, $single_or_archive ) {
		//$single_or_archive = 'archive';
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 4, get_option( 'wcj_more_product_info_on_' . $single_or_archive . '_fields_total', 4 ) ); $i++ ) {
			$field_id = 'wcj_more_product_info_on_' . $single_or_archive . '_' . $i ;
			$default_value = '';
			switch ( $i ) {
				case 1: $default_value = '<ul>'; break;
				case 2: $default_value = '<li>' . __( 'You save: <strong>%you_save_formatted%</strong> (%you_save_percent%%)', 'woocommerce-jetpack' ) . '</li>'; break;
				case 3: $default_value = '<li>' . __( 'Total sales: %total_sales%', 'woocommerce-jetpack' ) . '</li>'; break;
				case 4: $default_value = '</ul>'; break;
			}
			$desc = ( '' != $default_value ) ? __( 'Default', 'woocommerce-jetpack' ) . ': ' . esc_html( $default_value ) : '';
			$short_codes_list = '%you_save%, %total_sales%';
			$desc_tip = __( 'Field Nr. ', 'woocommerce-jetpack' ) . $i . '<br>' . __( 'Available short codes: ', 'woocommerce-jetpack' ) . $short_codes_list;
			$settings[] = array(
					'title' 	=> '',
					//'desc_tip'	=> $desc_tip,
					//'desc'		=> $desc,
					'id' 		=> $field_id,
					'default'	=> $default_value,
					'type' 		=> 'textarea',
					'css'	    => 'width:50%;min-width:300px;',
			);
		}
	}

	/**
	 * Return feature's enable/disable option.
	 */
	public function add_enabled_option( $settings ) {
		$all_settings = $this->get_settings();
		$settings[] = $all_settings[1];
		return $settings;
	}

	/**
	 * Get settings.
	 */
	function get_settings() {

		$settings = array(

			// Product Info Options - Global
			array( 'title' 	=> __( 'Product Info Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => '', 'id' => 'wcj_product_info_options' ),

			array(
				'title' 	=> __( 'Product Info', 'woocommerce-jetpack' ),
				'desc' 		=> '<strong>' . __( 'Enable Module', 'woocommerce-jetpack' ) . '</strong>',
				'desc_tip'	=> __( 'Add additional info to WooCommerce category and single product pages.', 'woocommerce-jetpack' ),
				'id' 		=> 'wcj_product_info_enabled',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
			),

			array( 'type' 	=> 'sectionend', 'id' => 'wcj_product_info_options' ),

			// More Product Info
			array( 'title' 	=> __( 'More Products Info', 'woocommerce-jetpack' ), 'type' => 'title',
				   'desc' 	=> __( 'For full list of short codes, please visit <a target="_blank" href="http://woojetpack.com/features/product-info/">http://woojetpack.com/features/product-info/</a>', 'woocommerce-jetpack' ),
				   //'desc' 	=> $this->list_short_codes(),
				   'id' 	=> 'wcj_more_product_info_options' ),
		);

		$this->admin_add_product_info_fields_with_header( $settings, 'archive', __( 'Product Info on Archive Pages', 'woocommerce-jetpack' ), $this->product_info_on_archive_filters_array );
		$this->admin_add_product_info_fields_with_header( $settings, 'single', __( 'Product Info on Single Pages', 'woocommerce-jetpack' ), $this->product_info_on_single_filters_array );

		$settings[] = array( 'type' 	=> 'sectionend', 'id' => 'wcj_more_product_info_options' );

		$settings = array_merge( $settings, array(

			// More Product Info - "Constant" modification
			array( 'title' 	=> __( 'Even More Products Info', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => '', 'id' => 'wcj_product_info_additional_options' ),

			array(
				'title' 	=> __( 'Product Info on Archive Pages', 'woocommerce-jetpack' ),
				'desc' 		=> __( 'Enable', 'woocommerce-jetpack' ),
				'id' 		=> 'wcj_product_info_on_archive_enabled',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> '',
				'desc_tip'	=> __( 'HTML info.', 'woocommerce-jetpack' ),
				'id' 		=> 'wcj_product_info_on_archive',
				'default'	=> __( 'SKU: %sku%', 'woocommerce-jetpack' ),
				'type' 		=> 'textarea',
				'css'	   => 'width:50%;min-width:300px;height:100px;',
			),

			array(
				'title'    => '',
				'desc'     => __( 'Position', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_on_archive_filter',
				'css'      => 'min-width:350px;',
				'class'    => 'chosen_select',
				'default'  => 'woocommerce_after_shop_loop_item_title',
				'type'     => 'select',
				'options'  => $this->product_info_on_archive_filters_array,
				'desc_tip' => true,
			),

			array(
				'title'    => '',
				'desc_tip'    => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_on_archive_filter_priority',
				'default'  => 10,
				'type'     => 'number',
			),

			array(
				'title' 	=> __( 'Product Info on Single Product Pages', 'woocommerce-jetpack' ),
				'desc' 		=> __( 'Enable', 'woocommerce-jetpack' ),
				'id' 		=> 'wcj_product_info_on_single_enabled',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> '',
				'desc_tip'	=> __( 'HTML info.', 'woocommerce-jetpack' ),// . ' ' . $this->list_short_codes(),
				'id' 		=> 'wcj_product_info_on_single',
				'default'	=> __( 'Total sales: %total_sales%', 'woocommerce-jetpack' ),
				'type' 		=> 'textarea',
				/*'desc' 	    => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'
						    => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),*/
				'css'	    => 'width:50%;min-width:300px;height:100px;',
			),

			array(
				'title'    => '',
				'desc'     => __( 'Position', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_on_single_filter',
				'css'      => 'min-width:350px;',
				'class'    => 'chosen_select',
				'default'  => 'woocommerce_after_single_product_summary',
				'type'     => 'select',
				'options'  => $this->product_info_on_single_filters_array,
				'desc_tip' =>  true,
			),

			array(
				'title'    => '',
				'desc_tip'    => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_on_single_filter_priority',
				'default'  => 10,
				'type'     => 'number',
			),

			array(
				'title'    => __( 'Product IDs to exclude', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Comma separated list of product IDs to exclude from product info.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_products_to_exclude',
				'default'  => '',
				'type'     => 'text',
				'css'      => 'min-width:300px;',
			),

			array( 'type' 	=> 'sectionend', 'id' => 'wcj_product_info_additional_options' ),

		) );

		return $settings;
	}

	/**
	 * Add settings section.
	 */
	function settings_section( $sections ) {
		$sections['product_info'] = __( 'Product Info', 'woocommerce-jetpack' );
		return $sections;
	}
}

endif;

return new WCJ_Product_Info();

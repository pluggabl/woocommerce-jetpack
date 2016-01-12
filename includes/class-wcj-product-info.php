<?php
/**
 * WooCommerce Jetpack Product Info
 *
 * The WooCommerce Jetpack Product Info class.
 *
 * @version 2.3.12
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_Info' ) ) :

class WCJ_Product_Info extends WCJ_Module {

	/**
	 * search_and_replace_depreciated_shortcodes.
	 *
	 * @version 2.3.12
	 * @since   2.3.12
	 */
	private function search_and_replace_depreciated_shortcodes( $data ) {
		$search_and_replace_depreciated_shortcodes_array = array(
			'%sku%'                                    => '[wcj_product_sku]',
			'[wcj_sku]'                                => '[wcj_product_sku]', // TODO: for all [wcj_x] shortcodes with args, e.g.: [wcj_sku before="" after=""]
			'%title%'                                  => '[wcj_product_title]',
			'[wcj_title]'                              => '[wcj_product_title]',
			'%weight%'                                 => '[wcj_product_weight]', // TODO: if ( $product->has_weight() )
			'[wcj_weight]'                             => '[wcj_product_weight]',
			'%total_sales%'                            => '[wcj_product_total_sales]',
			'[wcj_total_sales]'                        => '[wcj_product_total_sales]',
			'%shipping_class%'                         => '[wcj_product_shipping_class]',
			'[wcj_shipping_class]'                     => '[wcj_product_shipping_class]',
			'%dimensions%'                             => '[wcj_product_dimensions]',
			'[wcj_dimensions]'                         => '[wcj_product_dimensions]',
			'%formatted_name%'                         => '[wcj_product_formatted_name]',
			'[wcj_formatted_name]'                     => '[wcj_product_formatted_name]',
			'%stock_availability%'                     => '[wcj_product_stock_availability]',
			'[wcj_stock_availability]'                 => '[wcj_product_stock_availability]',
			'%tax_class%'                              => '[wcj_product_tax_class]',
			'[wcj_tax_class]'                          => '[wcj_product_tax_class]',
			'%average_rating%'                         => '[wcj_product_average_rating]',
			'[wcj_average_rating]'                     => '[wcj_product_average_rating]',
			'%categories%'                             => '[wcj_product_categories]',
			'[wcj_categories]'                         => '[wcj_product_categories]',
			'%list_attributes%'                        => '[wcj_product_list_attributes]',
			'[wcj_list_attributes]'                    => '[wcj_product_list_attributes]',
			'%stock_quantity%'                         => '[wcj_product_stock_quantity]',
			'[wcj_stock_quantity]'                     => '[wcj_product_stock_quantity]',
			'%sale_price%'                             => '[wcj_product_sale_price hide_currency="yes"]',
			'[wcj_sale_price]'                         => '[wcj_product_sale_price hide_currency="yes"]',
			'%sale_price_formatted%'                   => '[wcj_product_sale_price]',
			'[wcj_sale_price_formatted]'               => '[wcj_product_sale_price]',
			'%regular_price%'                          => '[wcj_product_regular_price hide_currency="yes"]',
			'[wcj_regular_price]'                      => '[wcj_product_regular_price hide_currency="yes"]',
			'%regular_price_formatted%'                => '[wcj_product_regular_price]',
			'[wcj_regular_price_formatted]'            => '[wcj_product_regular_price]',
			'%regular_price_if_on_sale%'               => '[wcj_product_regular_price hide_currency="yes" show_always="no"]',
			'[wcj_regular_price_if_on_sale]'           => '[wcj_product_regular_price hide_currency="yes" show_always="no"]',
			'%regular_price_if_on_sale_formatted%'     => '[wcj_product_regular_price show_always="no"]',
			'[wcj_regular_price_if_on_sale_formatted]' => '[wcj_product_regular_price show_always="no"]',
		);
		return str_replace(
			array_keys(   $search_and_replace_depreciated_shortcodes_array ),
			array_values( $search_and_replace_depreciated_shortcodes_array ),
			$data
		);
	}

	/**
	 * Constructor.
	 *
	 * @version 2.3.12
	 */
	function __construct() {

		$this->id         = 'product_info';
		$this->short_desc = __( 'Product Info', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add additional info to WooCommerce category and single product pages.', 'woocommerce-jetpack' );
		parent::__construct();

		$this->product_info_on_archive_filters_array = $this->get_product_info_on_archive_filters_array();
		$this->product_info_on_single_filters_array  = $this->get_product_info_on_single_filters_array();

		// List of product info short codes
		$this->product_info_shortcodes_array = array(

			'%price%',
			'%price_including_tax%',
			'%price_excluding_tax%',

			'%you_save%',
			'%you_save_percent%',

			'%price_formatted%',
			'%price_including_tax_formatted%',
			'%price_excluding_tax_formatted%',
			'%you_save_formatted%',

			'%time_since_last_sale%',

			'%list_attribute%',
		);

		if ( $this->is_enabled() ) {

			$this->add_product_info_filters( 'archive' );
			$this->add_product_info_filters( 'single' );

			// Shortcodes
			add_shortcode( 'wcj_price', 								array( $this, 'shortcode_product_info_price' ) );
			add_shortcode( 'wcj_price_including_tax', 					array( $this, 'shortcode_product_info_price_including_tax' ) );
			add_shortcode( 'wcj_price_excluding_tax', 					array( $this, 'shortcode_product_info_price_excluding_tax' ) );
			add_shortcode( 'wcj_you_save', 								array( $this, 'shortcode_product_info_you_save' ) );
			add_shortcode( 'wcj_you_save_percent', 						array( $this, 'shortcode_product_info_you_save_percent' ) );

			add_shortcode( 'wcj_price_formatted', 						array( $this, 'shortcode_product_info_price_formatted' ) );
			add_shortcode( 'wcj_price_including_tax_formatted', 		array( $this, 'shortcode_product_info_price_including_tax_formatted' ) );
			add_shortcode( 'wcj_price_excluding_tax_formatted', 		array( $this, 'shortcode_product_info_price_excluding_tax_formatted' ) );
			add_shortcode( 'wcj_you_save_formatted', 					array( $this, 'shortcode_product_info_you_save_formatted' ) );

			add_shortcode( 'wcj_time_since_last_sale', 					array( $this, 'shortcode_product_info_time_since_last_sale' ) );
			add_shortcode( 'wcj_available_variations', 					array( $this, 'shortcode_product_info_available_variations' ) );

			add_shortcode( 'wcj_list_attribute', 						array( $this, 'shortcode_product_info_list_attribute' ) );

			// Depreciated
			add_shortcode( 'wcjp_list_attribute', 						array( $this, 'shortcode_wcjp_list_attribute' ) );
		}
	}

	/**
	 * get_product_info_on_archive_filters_array.
	 *
	 * @version 2.3.12
	 * @since   2.3.12
	 */
	private function get_product_info_on_archive_filters_array() {
		return array(
			'woocommerce_before_shop_loop_item'       => __( 'Before product', 'woocommerce-jetpack' ),
			'woocommerce_before_shop_loop_item_title' => __( 'Before product title', 'woocommerce-jetpack' ),
			'woocommerce_after_shop_loop_item'        => __( 'After product', 'woocommerce-jetpack' ),
			'woocommerce_after_shop_loop_item_title'  => __( 'After product title', 'woocommerce-jetpack' ),
		);
	}

	/**
	 * get_product_info_on_single_filters_array.
	 *
	 * @version 2.3.12
	 * @since   2.3.12
	 */
	private function get_product_info_on_single_filters_array() {
		return array(
			'woocommerce_single_product_summary'        => __( 'Inside single product summary', 'woocommerce-jetpack' ),
			'woocommerce_before_single_product_summary' => __( 'Before single product summary', 'woocommerce-jetpack' ),
			'woocommerce_after_single_product_summary'  => __( 'After single product summary', 'woocommerce-jetpack' ),
		);
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

	public function shortcode_product_info_price( $atts ) {
		return $this->get_shortcode( '%price%', $atts );
	}

	public function shortcode_product_info_price_including_tax( $atts ) {
		return $this->get_shortcode( '%price_including_tax%', $atts );
	}

	public function shortcode_product_info_price_excluding_tax( $atts ) {
		return $this->get_shortcode( '%price_excluding_tax%', $atts );
	}

	public function shortcode_product_info_you_save( $atts ) {
		return $this->get_shortcode( '%you_save%', $atts );
	}

	public function shortcode_product_info_you_save_percent( $atts ) {
		return $this->get_shortcode( '%you_save_percent%', $atts );
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

	public function shortcode_product_info_list_attribute( $atts ) {
		return $this->get_shortcode( '%list_attribute%', $atts );
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

			case '%price%':
				return $product->get_price();

			case '%price_including_tax%':
				return $product->get_price_including_tax();

			case '%price_excluding_tax%':
				return $product->get_price_excluding_tax();

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
	 *
	 * @version 2.3.12
	 */
	public function product_info() {
		$the_action_name = current_filter();
		if ( array_key_exists( $the_action_name, $this->product_info_on_archive_filters_array ) ) {
			$the_product_info = get_option( 'wcj_product_info_on_archive' );
			$the_product_info = $this->search_and_replace_depreciated_shortcodes( $the_product_info );
			$this->apply_product_info_short_codes( $the_product_info, false );
		}
		else if ( array_key_exists( $the_action_name, $this->product_info_on_single_filters_array ) ) {
			$the_product_info = get_option( 'wcj_product_info_on_single' );
			$the_product_info = $this->search_and_replace_depreciated_shortcodes( $the_product_info );
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
	 *
	 * @version 2.3.12
	 */
	public function add_more_product_info( $single_or_archive ) {
		//$single_or_archive = 'archive';
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 4, get_option( 'wcj_more_product_info_on_' . $single_or_archive . '_fields_total', 4 ) ); $i++ ) {
			$field_id = 'wcj_more_product_info_on_' . $single_or_archive . '_' . $i ;
			$the_product_info = get_option( $field_id );
			$the_product_info = $this->search_and_replace_depreciated_shortcodes( $the_product_info );
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
	 * admin_add_product_info_fields_with_header.
	 */
	function admin_add_product_info_fields_with_header( &$settings, $single_or_archive, $title, $filters_array ) {
		$settings = array_merge( $settings, array(
			array(
				'title'    => $title,
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_more_product_info_on_' . $single_or_archive . '_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => '',
				'desc'     => __( 'Position', 'woocommerce-jetpack' ),
				'id'       => 'wcj_more_product_info_on_' . $single_or_archive . '_filter',
				'css'      => 'min-width:350px;',
				'class'    => 'chosen_select',
				'default'  => 'woocommerce_after_shop_loop_item_title',
				'type'     => 'select',
				'options'  => $filters_array, //$this->product_info_on_archive_filters_array,
				'desc_tip' => true,
			),
			array(
				'title'    => '',
				'desc_tip' => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ),
				'id'       => 'wcj_more_product_info_on_' . $single_or_archive . '_filter_priority',
				'default'  => 10,
				'type'     => 'number',
			),
			array(
				'title'    => '',
				'desc_tip' => __( 'Number of product info fields. Click "Save changes" after you change this number.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_more_product_info_on_' . $single_or_archive . '_fields_total',
				'default'  => 4,
				'type'     => 'number',
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
		) );
		$this->admin_add_product_info_fields( $settings, $single_or_archive );
	}

	/**
	 * admin_add_product_info_fields.
	 *
	 * @version 2.3.12
	 */
	function admin_add_product_info_fields( &$settings, $single_or_archive ) {
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 4, get_option( 'wcj_more_product_info_on_' . $single_or_archive . '_fields_total', 4 ) ); $i++ ) {
			$field_id = 'wcj_more_product_info_on_' . $single_or_archive . '_' . $i ;
			$default_value = '';
			switch ( $i ) {
				case 1: $default_value = '<ul>'; break;
				case 2: $default_value = '<li>' . __( 'You save: <strong>%you_save_formatted%</strong> (%you_save_percent%%)', 'woocommerce-jetpack' ) . '</li>'; break;
				case 3: $default_value = '<li>' . __( 'Total sales: [wcj_product_total_sales]', 'woocommerce-jetpack' ) . '</li>'; break;
				case 4: $default_value = '</ul>'; break;
			}
//			$desc = ( '' != $default_value ) ? __( 'Default', 'woocommerce-jetpack' ) . ': ' . esc_html( $default_value ) : '';
//			$short_codes_list = '%you_save%, %total_sales%';
//			$desc_tip = __( 'Field Nr. ', 'woocommerce-jetpack' ) . $i . '<br>' . __( 'Available short codes: ', 'woocommerce-jetpack' ) . $short_codes_list;
			$settings[] = array(
				'title'    => '',
//				'desc_tip' => $desc_tip,
//				'desc'     => $desc,
				'id'       => $field_id,
				'default'  => $default_value,
				'type'     => 'textarea',
				'css'      => 'width:50%;min-width:300px;',
			);
		}
	}

	/**
	 * Get settings.
	 *
	 * @version 2.3.12
	 */
	function get_settings() {

		$settings = array(
			array(
				'title'    => __( 'More Products Info', 'woocommerce-jetpack' ), 'type' => 'title',
				'desc'     => __( 'For full list of short codes, please visit <a target="_blank" href="http://booster.io/features/product-info/">http://booster.io/features/product-info/</a>', 'woocommerce-jetpack' ),
//				'desc'     => $this->list_short_codes(),
				'id'       => 'wcj_more_product_info_options',
			),
		);
		$this->admin_add_product_info_fields_with_header( $settings, 'archive', __( 'Product Info on Archive Pages', 'woocommerce-jetpack' ), $this->product_info_on_archive_filters_array );
		$this->admin_add_product_info_fields_with_header( $settings, 'single',  __( 'Product Info on Single Pages', 'woocommerce-jetpack' ),  $this->product_info_on_single_filters_array );
		$settings[] = array(
				'type'     => 'sectionend',
				'id'       => 'wcj_more_product_info_options',
		);

		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Even More Products Info', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_product_info_additional_options',
			),
			array(
				'title'    => __( 'Product Info on Archive Pages', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_on_archive_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => '',
				'desc_tip' => __( 'HTML info.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_on_archive',
				'default'  => __( '[wcj_product_sku before="SKU: "]', 'woocommerce-jetpack' ), // TODO - TEST
				'type'     => 'textarea',
				'css'      => 'width:50%;min-width:300px;height:100px;',
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
				'desc_tip' => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_on_archive_filter_priority',
				'default'  => 10,
				'type'     => 'number',
			),
			array(
				'title'    => __( 'Product Info on Single Product Pages', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_info_on_single_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => '',
				'desc_tip' => __( 'HTML info.', 'woocommerce-jetpack' ),// . ' ' . $this->list_short_codes(),
				'id'       => 'wcj_product_info_on_single',
				'default'  => __( 'Total sales: [wcj_product_total_sales]', 'woocommerce-jetpack' ),
				'type'     => 'textarea',
				'css'      => 'width:50%;min-width:300px;height:100px;',
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
				'desc_tip' => true,
			),
			array(
				'title'    => '',
				'desc_tip' => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ),
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
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_info_additional_options',
			),
		) );

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_Product_Info();

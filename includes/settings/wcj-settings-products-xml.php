<?php
/**
 * Booster for WooCommerce - Settings - Products XML
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @todo    recheck "URL" in `'wcj_products_xml_file_path_' . $i`
 * @todo    (maybe) add more options to `wcj_products_xml_orderby_` (see https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters)
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$product_cats_options    = wcj_get_terms( 'product_cat' );
$product_tags_options    = wcj_get_terms( 'product_tag' );
$products_options        = wcj_get_products();
$is_multiselect_products = ( 'yes' === wcj_get_option( 'wcj_list_for_products', 'yes' ) );
$settings                = array(
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_products_xml_options',
	),
	array(
		'title'             => __( 'Total Files', 'woocommerce-jetpack' ),
		'id'                => 'wcj_products_xml_total_files',
		'default'           => 1,
		'type'              => 'custom_number',
		'desc_tip'          => __( 'Press Save changes after you change this number.', 'woocommerce-jetpack' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ?
			apply_filters( 'booster_message', '', 'readonly' ) : array(
				'step' => '1',
				'min'  => '1',
			),
	),
	array(
		'title'             => __( 'Advanced: Block Size', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'If you have large number of products you may want to modify block size for WP_Query call. Leave default value if not sure.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_products_xml_block_size',
		'default'           => 256,
		'type'              => 'number',
		'custom_attributes' => array(
			'step' => '1',
			'min'  => '1',
		),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_products_xml_options',
	),
);
$xml_total_files         = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_products_xml_total_files', 1 ) );
for ( $i = 1; $i <= $xml_total_files; $i++ ) {
	wcj_maybe_convert_and_update_option_value(
		array(
			array(
				'id'      => 'wcj_products_xml_products_incl_' . $i,
				'default' => '',
			),
			array(
				'id'      => 'wcj_products_xml_products_excl_' . $i,
				'default' => '',
			),
		),
		$is_multiselect_products
	);
	$products_xml_cron_desc = '';
	if ( $this->is_enabled() ) {
		$products_xml_cron_desc = '<a class="button" title="' .
			__( 'If you\'ve made any changes in module\'s settings - don\'t forget to save changes before clicking this button.', 'woocommerce-jetpack' ) . '"' .
			' href="' . esc_url( add_query_arg( 'wcj_create_products_xml', $i, remove_query_arg( 'wcj_create_products_xml_result' ) ) ) . '">' .
			__( 'Create Now', 'woocommerce-jetpack' ) . '</a>' .
		wcj_crons_get_next_event_time_message( 'wcj_create_products_xml_cron_time_' . $i );
	}
	$products_time_file_created_desc = '';
	if ( '' !== wcj_get_option( 'wcj_products_time_file_created_' . $i, '' ) ) {
		$products_time_file_created_desc = sprintf(
			/* translators: %s: translators Added */
			__( 'Recent file was created on %s', 'woocommerce-jetpack' ),
			'<code>' . date_i18n( wcj_get_option( 'date_format' ) . ' ' . wcj_get_option( 'time_format' ), wcj_get_option( 'wcj_products_time_file_created_' . $i, '' ) ) . '</code>'
		);
	}
	$default_file_name = ( ( 1 === $i ) ? 'products.xml' : 'products_' . $i . '.xml' );
	$settings          = array_merge(
		$settings,
		array(
			array(
				'title' => __( 'XML File', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'  => 'title',
				'desc'  => $products_time_file_created_desc,
				'id'    => 'wcj_products_xml_options_' . $i,
			),
			array(
				'title'   => __( 'Enabled', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => 'wcj_products_xml_enabled_' . $i,
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'XML Header', 'woocommerce-jetpack' ),
				/* translators: %s: translators Added */
				'desc'    => sprintf( __( 'You can use shortcodes here. For example %s.', 'woocommerce-jetpack' ), '<code>[wcj_current_datetime]</code>' ),
				'id'      => 'wcj_products_xml_header_' . $i,
				'default' => '<?xml version = "1.0" encoding = "utf-8" ?>' . PHP_EOL . '<root>' . PHP_EOL,
				'type'    => 'custom_textarea',
				'css'     => 'width:66%;min-width:300px;min-height:150px;',
			),
			array(
				'title'   => __( 'XML Item', 'woocommerce-jetpack' ),
				'desc'    => sprintf(
					/* translators: %s: translators Added */
					__( 'You can use shortcodes here. Please take a look at <a target="_blank" href="%s">Booster\'s products shortcodes</a>.', 'woocommerce-jetpack' ),
					'https://booster.io/category/shortcodes/products-shortcodes/'
				),
				'id'      => 'wcj_products_xml_item_' . $i,
				'default' =>
					'<item>' . PHP_EOL .
						"\t" . '<name>[wcj_product_title strip_tags="yes"]</name>' . PHP_EOL .
						"\t" . '<link>[wcj_product_url strip_tags="yes"]</link>' . PHP_EOL .
						"\t" . '<price>[wcj_product_price hide_currency="yes" strip_tags="yes"]</price>' . PHP_EOL .
						"\t" . '<image>[wcj_product_image_url image_size="full" strip_tags="yes"]</image>' . PHP_EOL .
						"\t" . '<category_full>[wcj_product_categories_names strip_tags="yes"]</category_full>' . PHP_EOL .
						"\t" . '<category_link>[wcj_product_categories_urls strip_tags="yes"]</category_link>' . PHP_EOL .
					'</item>' . PHP_EOL,
				'type'    => 'custom_textarea',
				'css'     => 'width:66%;min-width:300px;min-height:300px;',
			),
			array(
				'title'   => __( 'XML Footer', 'woocommerce-jetpack' ),
				'desc'    => __( 'You can use shortcodes here.', 'woocommerce-jetpack' ),
				'id'      => 'wcj_products_xml_footer_' . $i,
				'default' => '</root>',
				'type'    => 'custom_textarea',
				'css'     => 'width:66%;min-width:300px;min-height:150px;',
			),
			array(
				'title'    => __( 'XML File Path and Name', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Path on server:', 'woocommerce-jetpack' ) . ' ' . ABSPATH . wcj_get_option( 'wcj_products_xml_file_path_' . $i, $default_file_name ),
				'desc'     => __( 'URL:', 'woocommerce-jetpack' ) . ' ' .
					'<a target="_blank" href="' . site_url() . '/' . wcj_get_option( 'wcj_products_xml_file_path_' . $i, $default_file_name ) . '">' .
						site_url() . '/' . wcj_get_option( 'wcj_products_xml_file_path_' . $i, $default_file_name ) . '</a>',
				'id'       => 'wcj_products_xml_file_path_' . $i,
				'default'  => $default_file_name,
				'type'     => 'text',
				'css'      => 'width:66%;min-width:300px;',
			),
			array(
				'title'             => __( 'Update Period', 'woocommerce-jetpack' ),
				'desc'              => $products_xml_cron_desc,
				'id'                => 'wcj_create_products_xml_period_' . $i,
				'default'           => 'weekly',
				'type'              => 'select',
				'options'           => array(
					'minutely'   => __( 'Update Every Minute', 'woocommerce-jetpack' ),
					'hourly'     => __( 'Update Hourly', 'woocommerce-jetpack' ),
					'twicedaily' => __( 'Update Twice Daily', 'woocommerce-jetpack' ),
					'daily'      => __( 'Update Daily', 'woocommerce-jetpack' ),
					'weekly'     => __( 'Update Weekly', 'woocommerce-jetpack' ),
				),
				'desc_tip'          => __( 'Possible update periods are: every minute, hourly, twice daily, daily and weekly.', 'woocommerce-jetpack' ) . ' ' .
					apply_filters( 'booster_message', '', 'desc_no_link' ),
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			),
			wcj_get_settings_as_multiselect_or_text(
				array(
					'title'    => __( 'Products to Include', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'To include selected products only, enter products here. Leave blank to include all products.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_products_xml_products_incl_' . $i,
					'default'  => '',
				),
				$products_options,
				$is_multiselect_products
			),
			wcj_get_settings_as_multiselect_or_text(
				array(
					'title'    => __( 'Products to Exclude', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'To exclude selected products, enter products here. Leave blank to include all products.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_products_xml_products_excl_' . $i,
					'default'  => '',
				),
				$products_options,
				$is_multiselect_products
			),
			array(
				'title'    => __( 'Categories to Include', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To include products from selected categories only, enter categories here. Leave blank to include all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_xml_cats_incl_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_cats_options,
			),
			array(
				'title'    => __( 'Categories to Exclude', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To exclude products from selected categories, enter categories here. Leave blank to include all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_xml_cats_excl_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_cats_options,
			),
			array(
				'title'    => __( 'Tags to Include', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To include products from selected tags only, enter tags here. Leave blank to include all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_xml_tags_incl_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_tags_options,
			),
			array(
				'title'    => __( 'Tags to Exclude', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To exclude products from selected tags, enter tags here. Leave blank to include all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_xml_tags_excl_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_tags_options,
			),
			array(
				'title'   => __( 'Products Scope', 'woocommerce-jetpack' ),
				'id'      => 'wcj_products_xml_scope_' . $i,
				'default' => 'all',
				'type'    => 'select',
				'options' => array(
					'all'               => __( 'All products', 'woocommerce-jetpack' ),
					'sale_only'         => __( 'Only products that are on sale', 'woocommerce-jetpack' ),
					'not_sale_only'     => __( 'Only products that are not on sale', 'woocommerce-jetpack' ),
					'featured_only'     => __( 'Only products that are featured', 'woocommerce-jetpack' ),
					'not_featured_only' => __( 'Only products that are not featured', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'   => __( 'Sort Products by', 'woocommerce-jetpack' ),
				'id'      => 'wcj_products_xml_orderby_' . $i,
				'default' => 'date',
				'type'    => 'select',
				'options' => array(
					'date'     => __( 'Date', 'woocommerce-jetpack' ),
					'ID'       => __( 'ID', 'woocommerce-jetpack' ),
					'author'   => __( 'Author', 'woocommerce-jetpack' ),
					'title'    => __( 'Title', 'woocommerce-jetpack' ),
					'name'     => __( 'Slug', 'woocommerce-jetpack' ),
					'modified' => __( 'Modified', 'woocommerce-jetpack' ),
					'rand'     => __( 'Rand', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'   => __( 'Sorting Order', 'woocommerce-jetpack' ),
				'id'      => 'wcj_products_xml_order_' . $i,
				'default' => 'DESC',
				'type'    => 'select',
				'options' => array(
					'DESC' => __( 'Descending', 'woocommerce-jetpack' ),
					'ASC'  => __( 'Ascending', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'             => __( 'Max Products', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'Set to -1 to include all products.', 'woocommerce-jetpack' ),
				'id'                => 'wcj_products_xml_max_' . $i,
				'default'           => -1,
				'type'              => 'number',
				'custom_attributes' => array( 'min' => -1 ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wcj_products_xml_options_' . $i,
			),
		)
	);
}
return $settings;

<?php
/**
 * Booster for WooCommerce - Settings - Product by User
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$fields                  = array(
	'desc'          => __( 'Description', 'woocommerce-jetpack' ),
	'short_desc'    => __( 'Short Description', 'woocommerce-jetpack' ),
	'image'         => __( 'Image', 'woocommerce-jetpack' ),
	'regular_price' => __( 'Regular Price', 'woocommerce-jetpack' ),
	'sale_price'    => __( 'Sale Price', 'woocommerce-jetpack' ),
	'external_url'  => __( 'Product URL (for "External/Affiliate" product type only)', 'woocommerce-jetpack' ),
	'cats'          => __( 'Categories', 'woocommerce-jetpack' ),
	'tags'          => __( 'Tags', 'woocommerce-jetpack' ),
);
$fields_enabled_options  = array();
$fields_required_options = array();
$i                       = 0;
$total_fields            = count( $fields );
foreach ( $fields as $field_id => $field_desc ) {
	$i++;
	$checkboxgroup = '';
	if ( 1 === $i ) {
		$checkboxgroup = 'start';
	} elseif ( $total_fields === $i ) {
		$checkboxgroup = 'end';
	}
	$fields_enabled_options[]  = array(
		'title'             => ( ( 1 === $i ) ? __( 'Additional Fields', 'woocommerce-jetpack' ) : '' ),
		'desc'              => $field_desc,
		'id'                => 'wcj_product_by_user_' . $field_id . '_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'checkboxgroup'     => $checkboxgroup,
		'custom_attributes' => ( ( 'image' === $field_id ) ? apply_filters( 'booster_message', '', 'disabled' ) : '' ),
		'desc_tip'          => ( ( 'image' === $field_id ) ? apply_filters( 'booster_message', '', 'desc' ) : '' ),
	);
	$fields_required_options[] = array(
		'title'             => ( ( 1 === $i ) ? __( 'Is Required', 'woocommerce-jetpack' ) : '' ),
		'desc'              => $field_desc,
		'id'                => 'wcj_product_by_user_' . $field_id . '_required',
		'default'           => 'no',
		'type'              => 'checkbox',
		'checkboxgroup'     => $checkboxgroup,
		'custom_attributes' => ( ( 'image' === $field_id ) ? apply_filters( 'booster_message', '', 'disabled' ) : '' ),
		'desc_tip'          => ( ( 'image' === $field_id ) ? apply_filters( 'booster_message', '', 'desc' ) : '' ),
	);
}

$settings                = array_merge(
	array(
		array(
			'id'   => 'wcj_product_by_user_options',
			'type' => 'sectionend',
		),
		array(
			'id'      => 'wcj_product_by_user_options',
			'type'    => 'tab_ids',
			'tab_ids' => array(
				'wcj_product_by_user_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
				'wcj_product_by_user_taxonomies_options_tab' => __( 'Taxonomies Options', 'woocommerce-jetpack' ),
			),
		),
		array(
			'id'   => 'wcj_product_by_user_general_options_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => __( 'Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( '<em>Title</em> field is always enabled and required.', 'woocommerce-jetpack' ),
			'id'    => 'wcj_product_by_user_options',
		),
	),
	$fields_enabled_options,
	$fields_required_options,
	array(
		array(
			'title'             => __( 'Price Step', 'woocommerce-jetpack' ),
			'desc'              => __( 'Number of decimals', 'woocommerce' ),
			'desc_tip'          => __( 'Used for price fields only.', 'woocommerce-jetpack' ),
			'id'                => 'wcj_product_by_user_price_step',
			'default'           => wcj_get_option( 'woocommerce_price_num_decimals', 2 ),
			'type'              => 'number',
			'custom_attributes' => array(
				'step' => '1',
				'min'  => '0',
			),
		),
		array(
			'title'   => __( 'User Visibility', 'woocommerce-jetpack' ),
			'desc'    => sprintf(
								/* translators: %s: translators Added */
				__( 'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module', 'woocommerce-jetpack' ),
				admin_url( wcj_admin_tab_url() . '&wcj-cat=emails_and_misc&section=general' )
			),
			'id'      => 'wcj_product_by_user_user_visibility',
			'default' => array(),
			'type'    => 'multiselect',
			'class'   => 'chosen_select',
			'options' => wcj_get_user_roles_options(),
		),
		array(
			'title'   => __( 'Product Type', 'woocommerce-jetpack' ),
			'id'      => 'wcj_product_by_user_product_type',
			'default' => 'simple',
			'type'    => 'select',
			'options' => array(
				'simple'   => __( 'Simple product', 'woocommerce-jetpack' ),
				'external' => __( 'External/Affiliate product', 'woocommerce-jetpack' ),
			),
		),
		array(
			'title'   => __( 'Product Status', 'woocommerce-jetpack' ),
			'id'      => 'wcj_product_by_user_status',
			'default' => 'draft',
			'type'    => 'select',
			'options' => get_post_statuses(),
		),
		array(
			'title'   => __( 'Require Unique Title', 'woocommerce-jetpack' ),
			'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
			'id'      => 'wcj_product_by_user_require_unique_title',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Add "My Products" Tab to User\'s My Account Page', 'woocommerce-jetpack' ),
			'desc'    => __( 'Add', 'woocommerce-jetpack' ),
			'id'      => 'wcj_product_by_user_add_to_my_account',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Message: Product Successfully Added', 'woocommerce-jetpack' ),
			'id'      => 'wcj_product_by_user_message_product_successfully_added',
			'default' => __( '"%product_title%" successfully added!', 'woocommerce-jetpack' ),
			'type'    => 'text',
			'css'     => 'width:300px;',
		),
		array(
			'title'   => __( 'Message: Product Successfully Edited', 'woocommerce-jetpack' ),
			'id'      => 'wcj_product_by_user_message_product_successfully_edited',
			'default' => __( '"%product_title%" successfully edited!', 'woocommerce-jetpack' ),
			'type'    => 'text',
			'css'     => 'width:300px;',
		),
		array(
			'id'   => 'wcj_product_by_user_general_options_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_product_by_user_taxonomies_options_tab',
			'type' => 'tab_start',
		),
		array(
			'title'             => __( 'Total Custom Taxonomies', 'woocommerce-jetpack' ),
			'id'                => 'wcj_product_by_user_custom_taxonomies_total',
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
	)
);
$custom_taxonomies_total = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_product_by_user_custom_taxonomies_total', 1 ) );
for ( $i = 1; $i <= $custom_taxonomies_total; $i++ ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					'title'   => __( 'Custom Taxonomy', 'woocommerce-jetpack' ) . ' #' . $i,
					'desc'    => __( 'Enabled', 'woocommerce-jetpack' ),
					'id'      => 'wcj_product_by_user_custom_taxonomy_' . $i . '_enabled',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'desc'    => __( 'Required', 'woocommerce-jetpack' ),
					'id'      => 'wcj_product_by_user_custom_taxonomy_' . $i . '_required',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'desc'    => __( 'ID', 'woocommerce-jetpack' ),
					'id'      => 'wcj_product_by_user_custom_taxonomy_' . $i . '_id',
					'default' => '',
					'type'    => 'text',
				),
				array(
					'desc'    => __( 'Title', 'woocommerce-jetpack' ),
					'id'      => 'wcj_product_by_user_custom_taxonomy_' . $i . '_title',
					'default' => '',
					'type'    => 'text',
				),
			)
		);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'title'   => __( 'Send Email to Product User', 'woocommerce-jetpack' ),
			'desc'    => __( 'Check to send email to product user when customer place order successfully', 'woocommerce-jetpack' ),
			'id'      => 'wcj_user_product_email_send',
			'default' => 'no',
			'type'    => 'checkbox',
		),
	)
);
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_product_by_user_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_product_by_user_taxonomies_options_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;

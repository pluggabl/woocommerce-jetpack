<?php
/**
 * Booster for WooCommerce - Settings - Checkout Files Upload
 *
 * @version 3.2.3
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$product_tags_options = wcj_get_terms( 'product_tag' );
$product_cats_options = wcj_get_terms( 'product_cat' );
$products_options     = wcj_get_products();
$settings = array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_files_upload_options',
	),
	array(
		'title'    => __( 'Total Files', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_files_upload_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '1', )
		),
	),
);
$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'File', 'woocommerce-jetpack' ) . ' #' . $i,
			'id'       => 'wcj_checkout_files_upload_enabled_' . $i,
			'desc'     => __( 'Enabled', 'woocommerce-jetpack' ),
			'type'     => 'checkbox',
			'default'  => 'yes',
		),
		array(
			'id'       => 'wcj_checkout_files_upload_required_' . $i,
			'desc'     => __( 'Required', 'woocommerce-jetpack' ),
			'type'     => 'checkbox',
			'default'  => 'no',
		),
		array(
			'id'       => 'wcj_checkout_files_upload_hook_' . $i,
			'desc'     => __( 'Position', 'woocommerce-jetpack' ),
			'default'  => 'woocommerce_before_checkout_form',
			'type'     => 'select',
			'options'  => array(
				'woocommerce_before_checkout_form'              => __( 'Before checkout form', 'woocommerce-jetpack' ),
				'woocommerce_after_checkout_form'               => __( 'After checkout form', 'woocommerce-jetpack' ),
				'disable'                                       => __( 'Do not add on checkout', 'woocommerce-jetpack' ),
			),
			'css'      => 'width:250px;',
		),
		array(
			'desc'     => __( 'Position order', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_hook_priority_' . $i,
			'default'  => 20,
			'type'     => 'number',
			'custom_attributes' => array( 'min' => '0' ),
			'css'      => 'width:250px;',
		),
		array(
			'id'       => 'wcj_checkout_files_upload_add_to_thankyou_' . $i,
			'desc'     => __( 'Add to Thank You page', 'woocommerce-jetpack' ),
			'type'     => 'checkbox',
			'default'  => 'no',
		),
		array(
			'id'       => 'wcj_checkout_files_upload_add_to_myaccount_' . $i,
			'desc'     => __( 'Add to My Account page', 'woocommerce-jetpack' ),
			'type'     => 'checkbox',
			'default'  => 'no',
		),
		array(
			'desc'     => __( 'Label', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Leave blank to disable label', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_label_' . $i,
			'default'  => __( 'Please select file to upload', 'woocommerce-jetpack' ),
			'type'     => 'textarea',
			'css'      => 'width:250px;',
		),
		array(
			'desc'     => __( 'Accepted file types', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Accepted file types. E.g.: ".jpg,.jpeg,.png". Leave blank to accept all files', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_file_accept_' . $i,
			'default'  => '.jpg,.jpeg,.png',
			'type'     => 'text',
			'css'      => 'width:250px;',
		),
		array(
			'desc'     => __( 'Label: Upload button', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_label_upload_button_' . $i,
			'default'  =>  __( 'Upload', 'woocommerce-jetpack' ),
			'type'     => 'text',
			'css'      => 'width:250px;',
		),
		array(
			'desc'     => __( 'Label: Remove button', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_label_remove_button_' . $i,
			'default'  =>  __( 'Remove', 'woocommerce-jetpack' ),
			'type'     => 'text',
			'css'      => 'width:250px;',
		),
		array(
			'desc'     => __( 'Notice: Wrong file type', 'woocommerce-jetpack' ),
			'desc_tip' => __( '%s will be replaced with file name', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_notice_wrong_file_type_' . $i,
			'default'  =>  __( 'Wrong file type: "%s"!', 'woocommerce-jetpack' ),
			'type'     => 'textarea',
			'css'      => 'width:250px;',
		),
		array(
			'desc'     => __( 'Notice: File is required', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_notice_required_' . $i,
			'default'  =>  __( 'File is required!', 'woocommerce-jetpack' ),
			'type'     => 'textarea',
			'css'      => 'width:250px;',
		),
		array(
			'desc'     => __( 'Notice: File was successfully uploaded', 'woocommerce-jetpack' ),
			'desc_tip' => __( '%s will be replaced with file name', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_notice_success_upload_' . $i,
			'default'  =>  __( 'File "%s" was successfully uploaded.', 'woocommerce-jetpack' ),
			'type'     => 'textarea',
			'css'      => 'width:250px;',
		),
		array(
			'desc'     => __( 'Notice: No file selected', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_notice_upload_no_file_' . $i,
			'default'  =>  __( 'Please select file to upload!', 'woocommerce-jetpack' ),
			'type'     => 'textarea',
			'css'      => 'width:250px;',
		),
		array(
			'desc'     => __( 'Notice: File was successfully removed', 'woocommerce-jetpack' ),
			'desc_tip' => __( '%s will be replaced with file name', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_notice_success_remove_' . $i,
			'default'  =>  __( 'File "%s" was successfully removed.', 'woocommerce-jetpack' ),
			'type'     => 'textarea',
			'css'      => 'width:250px;',
		),
		array(
			'title'    => '',
			'desc'     => __( 'PRODUCTS to show this field', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'To show this field only if at least one selected product is in cart, enter products here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_show_products_in_' . $i,
			'default'  => '',
			'class'    => 'chosen_select',
			'type'     => 'multiselect',
			'options'  => $products_options,
		),
		array(
			'title'    => '',
			'desc'     => __( 'CATEGORIES to show this field', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'To show this field only if at least one product of selected category is in cart, enter categories here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_show_cats_in_' . $i,
			'default'  => '',
			'class'    => 'chosen_select',
			'type'     => 'multiselect',
			'options'  => $product_cats_options,
		),
		array(
			'title'    => '',
			'desc'     => __( 'TAGS to show this field', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'To show this field only if at least one product of selected tag is in cart, enter tags here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_show_tags_in_' . $i,
			'default'  => '',
			'class'    => 'chosen_select',
			'type'     => 'multiselect',
			'options'  => $product_tags_options,
		),
		array(
			'title'    => '',
			'desc'     => __( 'PRODUCTS to hide this field', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'To hide this field if at least one selected product is in cart, enter products here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_hide_products_in_' . $i,
			'default'  => '',
			'class'    => 'chosen_select',
			'type'     => 'multiselect',
			'options'  => $products_options,
		),
		array(
			'title'    => '',
			'desc'     => __( 'CATEGORIES to hide this field', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'To hide this field if at least one product of selected category is in cart, enter categories here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_hide_cats_in_' . $i,
			'default'  => '',
			'class'    => 'chosen_select',
			'type'     => 'multiselect',
			'options'  => $product_cats_options,
		),
		array(
			'title'    => '',
			'desc'     => __( 'TAGS to hide this field', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'To hide this field if at least one product of selected tag is in cart, enter tags here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_files_upload_hide_tags_in_' . $i,
			'default'  => '',
			'class'    => 'chosen_select',
			'type'     => 'multiselect',
			'options'  => $product_tags_options,
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_files_upload_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Emails Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_files_upload_emails_options',
	),
	array(
		'title'    => __( 'Attach Files to Admin\'s New Order Emails', 'woocommerce-jetpack' ),
		'desc'     => __( 'Attach', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_files_upload_attach_to_admin_new_order',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Attach Files to Customer\'s Processing Order Emails', 'woocommerce-jetpack' ),
		'desc'     => __( 'Attach', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_files_upload_attach_to_customer_processing_order',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_files_upload_emails_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Form Template Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_files_upload_form_template_options',
	),
	array(
		'title'    => __( 'Before', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_files_upload_form_template_before',
		'default'  => '<table>',
		'type'     => 'textarea',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Label', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Replaced values: %field_id%, %field_label%, %required_html%.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_files_upload_form_template_label',
		'default'  => '<tr><td colspan="2"><label for="%field_id%">%field_label%</label>%required_html%</td></tr>',
		'type'     => 'textarea',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Field', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Replaced values: %field_html%, %button_html%.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_files_upload_form_template_field',
		'default'  => '<tr><td style="width:50%;">%field_html%</td><td style="width:50%;">%button_html%</td></tr>',
		'type'     => 'textarea',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'After', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_files_upload_form_template_after',
		'default'  => '</table>',
		'type'     => 'textarea',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_files_upload_form_template_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Advanced Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_files_upload_advanced_options',
	),
	array(
		'title'    => __( 'Block Potentially Harmful Files', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_files_upload_block_files_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Potentially Harmful File Extensions', 'woocommerce-jetpack' ),
		'desc'     => sprintf( __( 'List of file extensions separated by vertical bar %s.', 'woocommerce-jetpack' ), '<code>|</code>' ),
		'id'       => 'wcj_checkout_files_upload_block_files_exts',
		'default'  => 'bat|exe|cmd|sh|php|php0|php1|php2|php3|php4|php5|php6|php7|php8|php9|ph|ph0|ph1|ph2|ph3|ph4|ph5|ph6|ph7|ph8|ph9|pl|cgi|386|dll|com|torrent|js|app|jar|pif|vb|vbscript|wsf|asp|cer|csr|jsp|drv|sys|ade|adp|bas|chm|cpl|crt|csh|fxp|hlp|hta|inf|ins|isp|jse|htaccess|htpasswd|ksh|lnk|mdb|mde|mdt|mdw|msc|msi|msp|mst|ops|pcd|prg|reg|scr|sct|shb|shs|url|vbe|vbs|wsc|wsf|wsh|html|htm',
		'type'     => 'text',
		'css'      => 'width:100%;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_files_upload_advanced_options',
	),
) );
return $settings;

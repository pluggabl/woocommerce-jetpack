<?php
/**
 * Booster for WooCommerce - Settings - Checkout Files Upload
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$product_tags_options = wcj_get_terms( 'product_tag' );
$product_cats_options = wcj_get_terms( 'product_cat' );
$products_options     = wcj_get_products();
$user_roles_options   = wcj_get_user_roles_options();
$settings             = array(
	array(
		'id'   => 'wcj_checkout_files_upload_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_checkout_files_upload_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_checkout_files_upload_options_tab'        => __( 'Options', 'woocommerce-jetpack' ),
			'wcj_checkout_files_upload_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
			'wcj_checkout_files_upload_emails_options_tab' => __( 'Emails Options', 'woocommerce-jetpack' ),
			'wcj_checkout_files_upload_form_template_options_tab' => __( 'Form Template Options', 'woocommerce-jetpack' ),
			'wcj_checkout_files_upload_order_template_options_tab' => __( 'Order Template Options', 'woocommerce-jetpack' ),
			'wcj_checkout_files_upload_Email_template_options_tab' => __( 'Email Template Options', 'woocommerce-jetpack' ),
			'wcj_checkout_files_upload_advanced_options_tab' => __( 'Advanced Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_checkout_files_upload_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_checkout_files_upload_options',
	),
	array(
		'title'             => __( 'Total Files', 'woocommerce-jetpack' ),
		'id'                => 'wcj_checkout_files_upload_total_number',
		'default'           => 1,
		'type'              => 'custom_number',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array(
				'step' => '1',
				'min'  => '1',
			)
		),
	),
);
$total_number         = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_checkout_files_upload_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'   => __( 'File', 'woocommerce-jetpack' ) . ' #' . $i,
				'id'      => 'wcj_checkout_files_upload_enabled_' . $i,
				'desc'    => __( 'Enabled', 'woocommerce-jetpack' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'id'      => 'wcj_checkout_files_upload_required_' . $i,
				'desc'    => __( 'Required', 'woocommerce-jetpack' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'id'      => 'wcj_checkout_files_upload_hook_' . $i,
				'desc'    => __( 'Position', 'woocommerce-jetpack' ),
				'default' => 'woocommerce_before_checkout_form',
				'type'    => 'select',
				'options' => array(
					'woocommerce_before_checkout_form' => __( 'Before checkout form', 'woocommerce-jetpack' ),
					'woocommerce_after_checkout_form'  => __( 'After checkout form', 'woocommerce-jetpack' ),
					'disable'                          => __( 'Do not add on checkout', 'woocommerce-jetpack' ),
				),
			),
			array(
				'desc'              => __( 'Position order', 'woocommerce-jetpack' ),
				'id'                => 'wcj_checkout_files_upload_hook_priority_' . $i,
				'default'           => 20,
				'type'              => 'number',
				'custom_attributes' => array( 'min' => '0' ),
			),
			array(
				'id'      => 'wcj_checkout_files_upload_add_to_thankyou_' . $i,
				'desc'    => __( 'Add to Thank You page', 'woocommerce-jetpack' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'id'      => 'wcj_checkout_files_upload_add_to_myaccount_' . $i,
				'desc'    => __( 'Add to My Account page', 'woocommerce-jetpack' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'desc'     => __( 'Label', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable label', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_label_' . $i,
				'default'  => __( 'Please select file to upload', 'woocommerce-jetpack' ),
				'type'     => 'textarea',
			),
			array(
				'desc'     => __( 'Accepted file types', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Accepted file types. E.g.: ".jpg,.jpeg,.png". Leave blank to accept all files', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_file_accept_' . $i,
				'default'  => '.jpg,.jpeg,.png',
				'type'     => 'text',
			),
			array(
				'desc'    => __( 'Label: Upload button', 'woocommerce-jetpack' ),
				'id'      => 'wcj_checkout_files_upload_label_upload_button_' . $i,
				'default' => __( 'Upload', 'woocommerce-jetpack' ),
				'type'    => 'text',
			),
			array(
				'desc'    => __( 'Label: Remove button', 'woocommerce-jetpack' ),
				'id'      => 'wcj_checkout_files_upload_label_remove_button_' . $i,
				'default' => __( 'Remove', 'woocommerce-jetpack' ),
				'type'    => 'text',
			),
			array(
				'desc'     => __( 'Notice: Wrong file type', 'woocommerce-jetpack' ),
				/* translators: %s: replaced with file name */
				'desc_tip' => __( '%s will be replaced with file name', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_notice_wrong_file_type_' . $i,
				/* translators: %s: translators Added */
				'default'  => __( 'Wrong file type: "%s"!', 'woocommerce-jetpack' ),
				'type'     => 'textarea',
			),
			array(
				'desc'    => __( 'Notice: File is required', 'woocommerce-jetpack' ),
				'id'      => 'wcj_checkout_files_upload_notice_required_' . $i,
				'default' => __( 'File is required!', 'woocommerce-jetpack' ),
				'type'    => 'textarea',
			),
			array(
				'desc'     => __( 'Notice: File was successfully uploaded', 'woocommerce-jetpack' ),
				/* translators: %s: translators Added */
				'desc_tip' => __( '%s will be replaced with file name', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_notice_success_upload_' . $i,
				/* translators: %s: translators Added */
				'default'  => __( 'File "%s" was successfully uploaded.', 'woocommerce-jetpack' ),
				'type'     => 'textarea',
			),
			array(
				'desc'    => __( 'Notice: No file selected', 'woocommerce-jetpack' ),
				'id'      => 'wcj_checkout_files_upload_notice_upload_no_file_' . $i,
				'default' => __( 'Please select file to upload!', 'woocommerce-jetpack' ),
				'type'    => 'textarea',
			),
			array(
				'desc'     => __( 'Notice: File was successfully removed', 'woocommerce-jetpack' ),
				/* translators: %s: translators Added */
				'desc_tip' => __( '%s will be replaced with file name', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_notice_success_remove_' . $i,
				/* translators: %s: translators Added */
				'default'  => __( 'File "%s" was successfully removed.', 'woocommerce-jetpack' ),
				'type'     => 'textarea',
			),
			array(
				'desc'     => __( 'PRODUCTS to show this field', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To show this field only if at least one selected product is in cart, enter products here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_show_products_in_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $products_options,
			),
			array(
				'desc'     => __( 'CATEGORIES to show this field', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To show this field only if at least one product of selected category is in cart, enter categories here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_show_cats_in_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_cats_options,
			),
			array(
				'desc'     => __( 'TAGS to show this field', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To show this field only if at least one product of selected tag is in cart, enter tags here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_show_tags_in_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_tags_options,
			),
			array(
				'desc'     => __( 'USER ROLES to show this field', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to show for all user roles.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_show_user_roles_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $user_roles_options,
			),
			array(
				'desc'     => __( 'PRODUCTS to hide this field', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To hide this field if at least one selected product is in cart, enter products here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_hide_products_in_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $products_options,
			),
			array(
				'desc'     => __( 'CATEGORIES to hide this field', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To hide this field if at least one product of selected category is in cart, enter categories here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_hide_cats_in_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_cats_options,
			),
			array(
				'desc'     => __( 'TAGS to hide this field', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To hide this field if at least one product of selected tag is in cart, enter tags here. Leave blank to show for all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_hide_tags_in_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_tags_options,
			),
			array(
				'desc'     => __( 'USER ROLES to hide this field', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to show for all user roles.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_files_upload_hide_user_roles_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $user_roles_options,
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_checkout_files_upload_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_options_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_general_options_tab',
			'type' => 'tab_start',
		),
	)
);
$settings = array_merge(
	$settings,
	array(
		array(
			'title' => __( 'General Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_checkout_files_upload_general_options',
		),
		array(
			'title'   => __( 'Remove All Uploaded Files on Empty Cart', 'woocommerce-jetpack' ),
			'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_remove_on_empty_cart',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'desc'    => __( 'Add notice', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_remove_on_empty_cart_add_notice',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'id'      => 'wcj_checkout_files_upload_notice_remove_on_empty_cart',
			'default' => __( 'Files were successfully removed.', 'woocommerce-jetpack' ),
			'type'    => 'textarea',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_general_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_general_options_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_emails_options_tab',
			'type' => 'tab_start',
		),
	)
);
$settings = array_merge(
	$settings,
	array(
		array(
			'title' => __( 'Emails Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_checkout_files_upload_emails_options',
		),
		array(
			'title'   => __( 'Attach Files to Admin\'s New Order Emails', 'woocommerce-jetpack' ),
			'desc'    => __( 'Attach', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_attach_to_admin_new_order',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Attach Files to Customer\'s Processing Order Emails', 'woocommerce-jetpack' ),
			'desc'    => __( 'Attach', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_attach_to_customer_processing_order',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'    => __( 'Send Additional Email to Admin on User Actions', 'woocommerce-jetpack' ),
			/* translators: %s: translators Added */
			'desc_tip' => sprintf( __( 'Admin email: <em>%s</em>.', 'woocommerce-jetpack' ), wcj_get_option( 'admin_email' ) ),
			'id'       => 'wcj_checkout_files_upload_additional_admin_emails[actions]',
			'default'  => array(),
			'type'     => 'multiselect',
			'class'    => 'chosen_select',
			'options'  => array(
				'remove_file' => __( 'File removed on "Thank You" or "My Account" page', 'woocommerce-jetpack' ),
				'upload_file' => __( 'File uploaded on "Thank You" or "My Account" page', 'woocommerce-jetpack' ),
			),
		),
		array(
			'desc'    => __( 'Attach file on upload action', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_additional_admin_emails[do_attach]',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_emails_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_emails_options_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_form_template_options_tab',
			'type' => 'tab_start',
		),
	)
);
$settings = array_merge(
	$settings,
	array(
		array(
			'title' => __( 'Form Template Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_checkout_files_upload_form_template_options',
		),
		array(
			'title'   => __( 'Before', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_form_template_before',
			'default' => '<table>',
			'type'    => 'textarea',
			'css'     => 'width:100%;',
		),
		array(
			'title'    => __( 'Label', 'woocommerce-jetpack' ),
			/* translators: %s: translators Added */
			'desc_tip' => sprintf( __( 'Replaced values: %1$s, %2$s, %3$s.', 'woocommerce-jetpack' ), '%field_id%', '%field_label%', '%required_html%' ),
			'id'       => 'wcj_checkout_files_upload_form_template_label',
			/* translators: %s: translators Added */
			'default'  => '<tr><td colspan="2"><label for="%field_id%">%field_label%</label>%required_html%</td></tr>',
			'type'     => 'textarea',
			'css'      => 'width:100%;',
		),
		array(
			'title'    => __( 'Field', 'woocommerce-jetpack' ),
			/* translators: %s: translators Added */
			'desc_tip' => sprintf( __( 'Replaced values: %1$s, %2$s.', 'woocommerce-jetpack' ), '%field_html%', '%button_html%' ),
			'id'       => 'wcj_checkout_files_upload_form_template_field',
			/* translators: %s: translators Added */
			'default'  => '<tr><td style="width:50%;max-width:50vw;">%field_html%</td><td style="width:50%;">%button_html%</td></tr>',
			'type'     => 'textarea',
			'css'      => 'width:100%;',
		),
		array(
			'title'   => __( 'After', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_form_template_after',
			'default' => '</table>',
			'type'    => 'textarea',
			'css'     => 'width:100%;',
		),
		array(
			'title'   => __( 'Show images in field', 'woocommerce-jetpack' ),
			'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_form_template_field_show_images',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'desc'    => __( 'Image style', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_form_template_field_image_style',
			'default' => 'width:64px;',
			'type'    => 'text',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_form_template_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_form_template_options_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_order_template_options_tab',
			'type' => 'tab_start',
		),
	)
);
$settings = array_merge(
	$settings,
	array(
		array(
			'title' => __( 'Order Template Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_checkout_files_upload_templates[order_options]',
		),
		array(
			'title'   => __( 'Before', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_templates[order_before]',
			'default' => '',
			'type'    => 'textarea',
			'css'     => 'width:100%;',
		),
		array(
			'title'   => __( 'Item', 'woocommerce-jetpack' ),
			'desc'    => wcj_message_replaced_values( array( '%file_name%', '%image%' ) ),
			'id'      => 'wcj_checkout_files_upload_templates[order_item]',
			/* translators: %s: translators Added */
			'default' => sprintf( __( 'File: %s', 'woocommerce-jetpack' ), '%file_name%' ) . '<br>',
			'type'    => 'textarea',
			'css'     => 'width:100%;',
		),
		array(
			'title'   => __( 'After', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_templates[order_after]',
			'default' => '',
			'type'    => 'textarea',
			'css'     => 'width:100%;',
		),
		array(
			'desc'     => __( 'Image style', 'woocommerce-jetpack' ),
			'desc_tip' => sprintf(
							/* translators: %s: translators Added */
				__( 'Ignored, if %1$s is not included in %2$s option above.', 'woocommerce-jetpack' ),
				'<em>%image%</em>',
				'<em>' . __( 'Item', 'woocommerce-jetpack' ) . '</em>'
			),
			'id'       => 'wcj_checkout_files_upload_templates[order_image_style]',
			'default'  => 'width:64px;',
			'type'     => 'text',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_templates[order_options]',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_order_template_options_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_Email_template_options_tab',
			'type' => 'tab_start',
		),
	)
);
$settings = array_merge(
	$settings,
	array(
		array(
			'title' => __( 'Email Template Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_checkout_files_upload_templates[email_options]',
		),
		array(
			'title'   => __( 'Before', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_templates[email_before]',
			'default' => '',
			'type'    => 'textarea',
			'css'     => 'width:100%;',
		),
		array(
			'title'   => __( 'Item', 'woocommerce-jetpack' ),
			'desc'    => wcj_message_replaced_values( array( '%file_name%' ) ),
			'id'      => 'wcj_checkout_files_upload_templates[email_item]',
			/* translators: %s: translators Added */
			'default' => sprintf( __( 'File: %s', 'woocommerce-jetpack' ), '%file_name%' ) . '<br>',
			'type'    => 'textarea',
			'css'     => 'width:100%;',
		),
		array(
			'title'   => __( 'After', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_templates[email_after]',
			'default' => '',
			'type'    => 'textarea',
			'css'     => 'width:100%;',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_templates[email_options]',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_Email_template_options_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_advanced_options_tab',
			'type' => 'tab_start',
		),
	)
);
$settings = array_merge(
	$settings,
	array(
		array(
			'title' => __( 'Advanced Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_checkout_files_upload_advanced_options',
		),
		array(
			'title'   => __( 'Notice Type', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_notice_type',
			'default' => 'wc_add_notice',
			'type'    => 'select',
			'options' => array(
				'wc_add_notice'   => __( 'Add notice', 'woocommerce-jetpack' ),
				'wc_print_notice' => __( 'Print notice', 'woocommerce-jetpack' ),
				'disable'         => __( 'Disable notice', 'woocommerce-jetpack' ),
			),
		),
		array(
			'title'   => __( 'Block Potentially Harmful Files', 'woocommerce-jetpack' ),
			'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
			'id'      => 'wcj_checkout_files_upload_block_files_enabled',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Potentially Harmful File Extensions', 'woocommerce-jetpack' ),
			/* translators: %s: translators Added */
			'desc'    => sprintf( __( 'List of file extensions separated by vertical bar %s.', 'woocommerce-jetpack' ), '<code>|</code>' ),
			'id'      => 'wcj_checkout_files_upload_block_files_exts',
			'default' => 'bat|exe|cmd|sh|php|php0|php1|php2|php3|php4|php5|php6|php7|php8|php9|ph|ph0|ph1|ph2|ph3|ph4|ph5|ph6|ph7|ph8|ph9|pl|cgi|386|dll|com|torrent|js|app|jar|pif|vb|vbscript|wsf|asp|cer|csr|jsp|drv|sys|ade|adp|bas|chm|cpl|crt|csh|fxp|hlp|hta|inf|ins|isp|jse|htaccess|htpasswd|ksh|lnk|mdb|mde|mdt|mdw|msc|msi|msp|mst|ops|pcd|prg|reg|scr|sct|shb|shs|url|vbe|vbs|wsc|wsf|wsh|html|htm',
			'type'    => 'text',
			'css'     => 'width:100%;',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_advanced_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_checkout_files_upload_advanced_options_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;

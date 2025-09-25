<?php
/**
 * Booster for WooCommerce - Settings - Sales Notifications
 *
 * @version 7.3.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$products     = wcj_get_products();
$get_pages    = wcj_get_pages();
$product_cats = wcj_get_terms( 'product_cat' );
$settings     = array(
	array(
		'id'      => 'wcj_sales_general_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_sales_template_tab' => __( 'Template Options', 'woocommerce-jetpack' ),
			'wcj_sales_styling_tab'  => __( 'Styling Options', 'woocommerce-jetpack' ),
			'wcj_sales_time_tab'     => __( 'Time Options', 'woocommerce-jetpack' ),
			'wcj_sales_general_tab'  => __( 'General Options', 'woocommerce-jetpack' ),
			'wcj_sales_sound_tab'    => __( 'Sound Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_sales_template_tab',
		'type' => 'tab_start',
	),
	array(
		'title'             => __( 'Notifications Message', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sale_msg_msg',
		'type'              => 'text',
		'default'           =>
			sprintf(
				/* translators: %s: customer name */
				__( '<p> %s', 'woocommerce-jetpack' ),
				'Someone'
			) .
			sprintf(
				/* translators: %s: customer city */
				__( ' in %s', 'woocommerce-jetpack' ),
				'%customer_city%'
			) .
			sprintf(
				/* translators: %s: purchased time */
				__( ' just Purchased a %1$s', 'woocommerce-jetpack' ),
				'%product_title% </br>'
			) .
			sprintf(
				/* translators: %s: product link */
				'<a href="%s">%2$s',
				'%product_link%',
				'%product_title%</a></p>'
			),
		'css'               => 'width:100%;height:200px;',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => __( 'Customize notification text, add buyer names, country, product prices, images, and time ago etc details. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock.', 'woocommerce-jetpack' ),
	),
	array(
		'title'             => __( 'Image', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sale_msg_img',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => __( 'Enable or disable product image display in notifications. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock this option.', 'woocommerce-jetpack' ),
	),
	array(
		'title'             => __( 'Display Screen', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sale_msg_screen',
		'type'              => 'select',
		'default'           => 'wcj_desktop',
		'options'           => array(
			'wcj_desktop' => __( 'Desktop', 'woocommerce-jetpack' ),
			'wcj_mobile'  => __( 'Mobile', 'woocommerce-jetpack' ),
			'wcj_both'    => __( 'Both', 'woocommerce-jetpack' ),
		),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => __( 'Choose whether notifications appear on desktop, mobile, or both. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock screen selection.', 'woocommerce-jetpack' ),
	),
	array(
		'title'             => __( 'Display Position', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sale_msg_position',
		'type'              => 'select',
		'default'           => 'wcj_bottom_right',
		'options'           => array(
			'wcj_bottom_right' => __( 'Bottom Right', 'woocommerce-jetpack' ),
			'wcj_bottom_left'  => __( 'Bottom Left', 'woocommerce-jetpack' ),
		),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => __( 'Choose where notifications appear on screen. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock Top Right and Top Left positions.', 'woocommerce-jetpack' ),
	),
	array(
		'id'   => 'wcj_sales_template_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_sales_styling_tab',
		'type' => 'tab_start',
	),
	array(
		'title'             => __( 'Notifications Width', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sale_msg_styling[width]',
		'type'              => 'text',
		'default'           => '35%',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'title'             => __( 'Background Color', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sale_msg_styling[bgcolor]',
		'type'              => 'text',
		'default'           => '#ffffff',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => __( 'Choose a background color for notifications. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock background color customization.', 'woocommerce-jetpack' ),
	),
	array(
		'title'             => __( 'Text Color', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sale_msg_styling[color]',
		'type'              => 'text',
		'default'           => '#000000',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => __( 'Customize the text color of your notifications. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock text color customization.', 'woocommerce-jetpack' ),
	),
	array(
		'title'             => __( 'Display Effect', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sale_msg_styling[animation]',
		'type'              => 'select',
		'default'           => 'wcj_fadein',
		'options'           => array(
			'wcj_fadein'       => __( 'FadeIn', 'woocommerce-jetpack' ),
			'wcj_slideinleft'  => __( 'SlideInLeft', 'woocommerce-jetpack' ),
			'wcj_slideinright' => __( 'SlideInRight', 'woocommerce-jetpack' ),
			'wcj_slideinup'    => __( 'SlideInUp', 'woocommerce-jetpack' ),
			'wcj_slideindown'  => __( 'SlideInDown', 'woocommerce-jetpack' ),
		),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => __( 'Select how notifications should appear (fade, slide, etc.). Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock animation effects.', 'woocommerce-jetpack' ),
	),
	array(
		'title'             => __( 'Hidden Effect', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sale_msg_styling[hidden_animation]',
		'type'              => 'select',
		'default'           => 'wcj_fadein',
		'options'           => array(
			'wcj_fadeout'       => __( 'FadeOut', 'woocommerce-jetpack' ),
			'wcj_slideoutleft'  => __( 'SlideOutLeft', 'woocommerce-jetpack' ),
			'wcj_slideoutright' => __( 'SlideOutRight', 'woocommerce-jetpack' ),
			'wcj_slideoutup'    => __( 'SlideOutUp', 'woocommerce-jetpack' ),
			'wcj_slideoutdown'  => __( 'SlideOutDown', 'woocommerce-jetpack' ),
		),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => __( 'Select how notifications should disappear (fade, slide, etc.). Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock hiding effects.', 'woocommerce-jetpack' ),
	),
	array(
		'id'   => 'wcj_sales_styling_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_sales_time_tab',
		'type' => 'tab_start',
	),
	array(
		'title'             => __( 'Duration (seconds)', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sale_msg_duration',
		'type'              => 'number',
		'default'           => '4',
		'step'              => '1',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => __( 'Set how long each notification stays visible (in seconds). Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock duration control.', 'woocommerce-jetpack' ),
	),
	array(
		'title'             => __( 'Next time display (seconds)', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sale_msg_next',
		'type'              => 'number',
		'default'           => '8',
		'step'              => '1',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => __( 'Set delay before the next notification appears (in seconds). Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock timing options.', 'woocommerce-jetpack' ),
	),
	array(
		'id'   => 'wcj_sales_time_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_sales_general_tab',
		'type' => 'tab_start',
	),
	array(
		'title'             => __( 'Orders Status Include', 'woocommerce-jetpack' ),
		'id'                => 'wcj_orders_editable_status',
		'default'           => 'processing, completed',
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => __( 'Want to show notifications for various order statuses like "Shipped" or "Refunded" to build trust and keep customers informed? Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock all order statuses.', 'woocommerce-jetpack' ),
	),
	array(
		'id'   => 'wcj_sales_general_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_sales_sound_tab',
		'type' => 'tab_start',
	),
	array(
		'title'             => __( 'Enable', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sound_enable',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => __( 'Play a sound when a sales notification appears like Beep, Doublebeep etc. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock sound effects.', 'woocommerce-jetpack' ),
	),
	array(
		'title'             => __( 'Sound', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sale_msg_sound',
		'desc'              => __( 'screen notifications should be displayed with sound', 'woocommerce-jetpack' ),
		'type'              => 'select',
		'default'           => '',
		'options'           => array(
			'beep.mp3'       => __( 'beep.mp3', 'woocommerce-jetpack' ),
			'doublebeep.mp3' => __( 'doublebeep.mp3', 'woocommerce-jetpack' ),
			'game.mp3'       => __( 'game.mp3', 'woocommerce-jetpack' ),
		),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'id'   => 'wcj_sales_sound_tab',
		'type' => 'tab_end',
	),
);

return $settings;

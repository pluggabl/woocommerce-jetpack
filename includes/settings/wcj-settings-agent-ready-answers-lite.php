<?php
/**
 * Booster for WooCommerce Settings - Agent Ready Product Answers (Lite)
 *
 * @version 7.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = array(
	array(
		'title' => __( 'Agent Ready Product Answers (Lite)', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'Add one FAQ per product with FAQPage JSON-LD schema. Make your product pages answer-engine friendly.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_agent_ready_answers_lite_options',
	),
	array(
		'title'   => __( 'Enable Module', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable Agent Ready Product Answers (Lite)', 'woocommerce-jetpack' ),
		'id'      => 'wcj_agent_ready_answers_lite_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'FAQ Section Title', 'woocommerce-jetpack' ),
		'desc'    => __( 'Title displayed above FAQ section on product pages', 'woocommerce-jetpack' ),
		'id'      => 'wcj_agent_ready_answers_lite_section_title',
		'default' => __( 'Frequently Asked Questions', 'woocommerce-jetpack' ),
		'type'    => 'text',
		'css'     => 'width:300px;',
	),
	array(
		'title'   => __( 'FAQ Section Position', 'woocommerce-jetpack' ),
		'desc'    => __( 'Choose where to display the FAQ section on product pages', 'woocommerce-jetpack' ),
		'id'      => 'wcj_agent_ready_answers_lite_position',
		'default' => 'woocommerce_after_single_product_summary',
		'type'    => 'select',
		'options' => array(
			'woocommerce_after_single_product_summary'  => __( 'After single product summary', 'woocommerce-jetpack' ),
			'woocommerce_single_product_summary'        => __( 'Inside single product summary', 'woocommerce-jetpack' ),
			'woocommerce_before_single_product_summary' => __( 'Before single product summary', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'FAQ Section Priority', 'woocommerce-jetpack' ),
		'desc'    => __( 'Priority for FAQ section hook (lower numbers = earlier execution)', 'woocommerce-jetpack' ),
		'id'      => 'wcj_agent_ready_answers_lite_priority',
		'default' => 25,
		'type'    => 'number',
		'custom_attributes' => array(
			'min' => 1,
			'max' => 100,
		),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_agent_ready_answers_lite_options',
	),
	array(
		'title' => __( 'Upgrade to Elite', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'Unlock unlimited FAQs, Facts Table, Answer Chips, agent REST feed, Compare Matrix, and Bundles Assist with Booster Elite.', 'woocommerce-jetpack' ) . 
				   '<br><br><a href="https://booster.io/buy-booster/" target="_blank" class="button-primary">' . __( 'Upgrade to Elite', 'woocommerce-jetpack' ) . '</a>',
		'id'    => 'wcj_agent_ready_answers_lite_upgrade',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_agent_ready_answers_lite_upgrade',
	),
);

return $settings;

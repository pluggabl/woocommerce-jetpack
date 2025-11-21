<?php
/**
 * Booster Elite for WooCommerce - Settings - Cart Abandonment
 *
 * @version 7.2.7
 * @author  Pluggabl LLC.
 * @package Booster_Elite_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$user_roles       = wcj_get_user_roles_options();
$email_from_email = ( get_option( 'admin_email', true ) ) ? get_option( 'admin_email', true ) : '';
$email_from_name  = '';
if ( $email_from_email ) {
	$email_from_name_arr = explode( '@', $email_from_email );
	$email_from_name     = ( $email_from_name_arr[0] ) ? $email_from_name_arr[0] : '';
}

$settings = array(
	array(
		'id'   => 'wcj_cart_abandonment_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_cart_abandonment_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_cart_abandonment_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
			'wcj_cart_abandonment_email_options_tab'   => __( 'Email Options', 'woocommerce-jetpack' ),
			'wcj_cart_abandonment_email_templates_tab' => __( 'Email Templates', 'woocommerce-jetpack' ),
			'wcj_cart_abandonment_tools_tab'           => __( 'Tools', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_cart_abandonment_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title'             => __( 'Exclude - User Roles', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Leave blank to disable the option.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_cart_abandonment_disable_user_role',
		'default'           => '',
		'type'              => 'multiselect',
		'class'             => 'chosen_select',
		'options'           => $user_roles,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => __( 'Need to restrict access to abandoned cart settings and data based on user roles? <br> Upgrade <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to set user roles.', 'woocommerce-jetpack' ),
		'help_text'         => __( 'Select user roles that should be excluded from abandoned cart tracking. For example, exclude administrators or shop managers to avoid tracking internal test orders.', 'woocommerce-jetpack' ),
	),
	array(
		'id'   => 'wcj_cart_abandonment_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_cart_abandonment_general_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_cart_abandonment_email_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Email options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_cart_abandonment_email_options',
	),
	array(
		'title'             => __( 'From Name', 'woocommerce-jetpack' ),
		'type'              => 'text',
		'id'                => 'wcj_cart_abandonment_email_from_name',
		'default'           => $email_from_name,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'help_text'         => __( 'The name that appears in the "From" field of abandoned cart reminder emails. Use your store name or a friendly sender name to build trust.', 'woocommerce-jetpack' ),
		'friendly_label'    => __( 'Email Sender Name', 'woocommerce-jetpack' ),
	),
	array(
		'title'             => __( 'From Email', 'woocommerce-jetpack' ),
		'type'              => 'text',
		'id'                => 'wcj_cart_abandonment_email_from_email',
		'default'           => $email_from_email,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'help_text'         => __( 'The email address that abandoned cart reminders are sent from. Use a monitored email address in case customers reply with questions.', 'woocommerce-jetpack' ),
		'friendly_label'    => __( 'Email Sender Address', 'woocommerce-jetpack' ),
	),
	array(
		'id'   => 'wcj_cart_abandonment_email_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_cart_abandonment_email_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_cart_abandonment_email_templates_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Email Templates', 'woocommerce-jetpack' ),
		'id'    => 'wcj_cart_abandonment_general_options',
		'type'  => 'title',
	),
	array(
		'title'             => __( 'Total Email Template', 'woocommerce-jetpack' ),
		'id'                => 'wcj_ca_email_template_total_number',
		'default'           => 1,
		'type'              => 'custom_number',
		'desc'              => __( 'Need to send a sequence of emails at custom intervals? Want to customize sender details or use advanced email templates? Upgrade to <a href="https://booster.io/buy-booster/" target="_blank"> Booster </a> for full automation control!', 'woocommerce-jetpack' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		'help_text'         => __( 'Set the number of reminder emails to send for each abandoned cart. Most stores use 2-3 emails: one after 1 hour, another after 24 hours, and optionally a final reminder after 3 days.', 'woocommerce-jetpack' ),
		'friendly_label'    => __( 'Number of Reminder Emails', 'woocommerce-jetpack' ),
	),
	array(
		'id'   => 'wcj_cart_abandonment_general_options',
		'type' => 'sectionend',
	),
);

$total_number    = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_ca_email_template_total_number', 1 ) );
$template_titles = wcj_get_option( 'wcj_ca_email_template_titles', array() );
$templates       = array();

for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title' => __( 'Email Template', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'  => 'title',
				'id'    => 'wcj_ca_email_template_options_' . $i,
			),
			array(
				'title'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
				'id'        => 'wcj_ca_email_template_enabled_' . $i,
				'default'   => 'yes',
				'type'      => 'checkbox',
				'help_text' => __( 'Enable or disable this reminder email. You can turn off specific reminders while keeping others active.', 'woocommerce-jetpack' ),
			),
			array(
				'title'     => __( 'Email Subject', 'woocommerce-jetpack' ),
				'id'        => 'wcj_ca_email_template_subject_' . $i,
				'default'   => __( 'Is there anything we can help you?', 'woocommerce-jetpack' ),
				'type'      => 'text',
				'help_text' => __( 'The subject line for this reminder email. Make it friendly and helpful to encourage customers to complete their purchase.', 'woocommerce-jetpack' ),
			),
			array(
				'desc'      => wcj_message_replaced_values( array( '%coupon_code%', '%checkout_link%', '%customer_name%', '%admin_email%' ) ),
				'title'     => __( 'Email Content', 'woocommerce-jetpack' ),
				'type'      => 'textarea',
				'id'        => 'wcj_ca_email_template_body_' . $i,
				/* translators: %s: search term */
				'default'   => sprintf( __( "Hi %1\$s <p> we just noticed that you tried to make a order, but unfortunately, you haven't complete. Is there anything we can help you? </p><p> Here is a link to continue where you left off : <br> %s </p>", 'woocommerce-jetpack' ), '%customer_name%', '%checkout_link%' ),
				'css'       => 'width:100%;height:150px',
				'help_text' => __( 'The email message sent to customers. Use placeholders like %customer_name% and %checkout_link% to personalize the message. Keep it friendly and include a clear call-to-action.', 'woocommerce-jetpack' ),
			),
			array(
				'title'          => __( 'Email Trigger Time', 'woocommerce-jetpack' ),
				'id'             => 'wcj_ca_email_trigger_time_' . $i,
				'default'        => '1',
				'type'           => 'text',
				'help_text'      => __( 'How long to wait after cart abandonment before sending this reminder. First emails typically go out after 1 hour, follow-ups after 24 hours or more.', 'woocommerce-jetpack' ),
				'friendly_label' => __( 'Send After', 'woocommerce-jetpack' ),
			),
			array(
				'desc'      => __( 'Trigger this email after cart is abandoned', 'woocommerce-jetpack' ),
				'id'        => 'wcj_ca_email_trigger_time_type_' . $i,
				'default'   => 'day',
				'type'      => 'select',
				'options'   => array(
					'day'    => __( 'Day', 'woocommerce-jetpack' ),
					'minute' => __( 'Minutes', 'woocommerce-jetpack' ),
					'hour'   => __( 'Hours', 'woocommerce-jetpack' ),
				),
				'help_text' => __( 'Choose the time unit for the trigger delay. Use minutes for quick follow-ups, hours for same-day reminders, or days for longer-term recovery campaigns.', 'woocommerce-jetpack' ),
			),
			array(
				'title'             => __( 'Discount Type', 'woocommerce-jetpack' ),
				'desc'              => __( 'Want to supercharge recovery by offering a discount coupon in your reminder emails? This powerful feature is available in <a href="https://booster.io/buy-booster/" target="_blank"> Booster Elite! </a> ', 'woocommerce-jetpack' ),
				'id'                => 'wcj_ca_email_discount_type_' . $i,
				'default'           => 'No Discount',
				'type'              => 'select',
				'options'           => array(
					'no'         => __( 'No Discount', 'woocommerce-jetpack' ),
					'percent'    => __( 'Percentage discount', 'woocommerce-jetpack' ),
					'fixed_cart' => __( 'Fixed cart discount', 'woocommerce-jetpack' ),
				),
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
				'help_text'         => __( 'Offer a discount coupon to encourage customers to complete their purchase. Percentage discounts work well for higher-value carts, while fixed amounts work better for lower-value carts.', 'woocommerce-jetpack' ),
			),
			array(
				'title'             => __( 'Coupon Amount', 'woocommerce-jetpack' ),
				'id'                => 'wcj_ca_email_discount_amount_' . $i,
				'default'           => '',
				'type'              => 'number',
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
				'desc'              => apply_filters( 'booster_message', '', 'desc' ),
				'help_text'         => __( 'The discount value for the coupon. For percentage discounts, enter a number like 10 for 10% off. For fixed discounts, enter the amount in your store currency.', 'woocommerce-jetpack' ),
			),
			array(
				'title'             => __( 'Auto Apply Coupon', 'woocommerce-jetpack' ),
				'desc'              => __( 'Automatically add the coupon to the cart at the checkout', 'woocommerce-jetpack' ),
				'id'                => 'wcj_ca_auto_apply_coupon_' . $i,
				'default'           => 'no',
				'type'              => 'checkbox',
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
				'help_text'         => __( 'When enabled, the coupon is automatically applied when customers click the recovery link. This removes friction and increases conversion rates.', 'woocommerce-jetpack' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wcj_ca_email_template_options_' . $i,
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_cart_abandonment_email_templates_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_cart_abandonment_tools_tab',
			'type' => 'tab_start',
		),
		array(
			'title'    => __( 'Module Tools', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'To use tools, module must be enabled.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_' . $this->id . '_module_tools',
			'type'     => 'custom_link',
			'link'     => ( $this->is_enabled() ) ?
			'<code> <a href=" ' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=cart_abandonment&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' .
			__( 'Cart Abandonment Report', 'woocommerce-jetpack' ) . '</a> </code>' :
				'<code>' . __( 'Cart Abandonment Report', 'woocommerce-jetpack' ) . '</code>',
		),
		array(
			'id'   => 'wcj_cart_abandonment_tools_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;

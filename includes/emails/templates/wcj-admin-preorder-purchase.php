<?php
/**
 * Admin Pre-order Purchase email template.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/wcj-admin-preorder-purchase.php.
 *
 * @version 7.3.1
 * @package Booster_Elite_For_WooCommerce/includes/emails/templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! isset( $email_heading, $email, $order ) || ! is_object( $order ) ) {
	return;
}

// Header.
do_action( 'woocommerce_email_header', $email_heading, $email );

$customer_name = esc_html( $order->get_formatted_billing_full_name() );
/* translators: %s: Customer billing full name */
$customer_text = sprintf( __( 'Customer Name: %s', 'woocommerce-jetpack' ), $customer_name );
?>

<p><?php echo wp_kses_post( $customer_text ); ?></p>

<p>
	<?php
	$customer_name = $order && is_callable( array( $order, 'get_formatted_billing_full_name' ) )
		? esc_html( $order->get_formatted_billing_full_name() )
		: esc_html__( 'a customer', 'woocommerce-jetpack' );

	/* translators: %s: Customer name */
	printf( // phpcs:ignore
		esc_html__( 'You have received a pre-order from %s. Their order is as follows:', 'woocommerce-jetpack' ), // phpcs:ignore
		$customer_name // phpcs:ignore
	);
	?>
</p>

<?php
// Order details.
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

// Additional order meta.
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

// Customer details.
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

// Additional content.
if ( ! empty( $additional_content ) ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

// Footer.
do_action( 'woocommerce_email_footer', $email );

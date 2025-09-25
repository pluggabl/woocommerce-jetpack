<?php
/**
 * Customer Pre-order Confirmation email template.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/wcj-customer-preorder-confirmation.php.
 *
 * @version 7.3.1
 * @package Booster_Elite_For_WooCommerce/includes/emails/templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Action hook to display email header.
 *
 * @param string $email_heading Email heading text.
 * @param object $email Email object.
 */
do_action( 'woocommerce_email_header', $email_heading, $email );

if ( isset( $order ) && $order instanceof WC_Order ) : ?>
	<p>
	<?php
	/* translators: %s: Customer first name */
	$greeting = sprintf( __( 'Hi %s,', 'woocommerce-jetpack' ), esc_html( $order->get_billing_first_name() ) );
	echo wp_kses_post( $greeting );
	?>
	</p>

	<p><?php esc_html_e( 'Thank you for your pre-order. Your order details are shown below for your reference:', 'woocommerce-jetpack' ); ?></p>

	<?php
	$preorder_items = array();
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( $product && 'yes' === get_post_meta( $product->get_id(), '_wcj_product_preorder_enabled', true ) ) {
			$release_date     = get_post_meta( $product->get_id(), '_wcj_product_preorder_release_date', true );
			$preorder_items[] = array(
				'name'         => $product->get_name(),
				'release_date' => $release_date ? date_i18n( get_option( 'date_format' ), strtotime( $release_date ) ) : '',
			);
		}
	}

	if ( ! empty( $preorder_items ) ) :
		?>
		<h2><?php esc_html_e( 'Pre-ordered Items', 'woocommerce-jetpack' ); ?></h2>
		<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 20px;">
			<thead>
				<tr>
					<th class="td" scope="col"><?php esc_html_e( 'Product', 'woocommerce-jetpack' ); ?></th>
					<th class="td" scope="col"><?php esc_html_e( 'Release Date', 'woocommerce-jetpack' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $preorder_items as $item ) : ?>
					<tr>
						<td class="td"><?php echo esc_html( $item['name'] ); ?></td>
						<td class="td"><?php echo esc_html( $item['release_date'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<?php
	/**
	 * Action hook to display order details.
	 *
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Whether the email is being sent to admin.
	 * @param bool     $plain_text    Whether the email is plain text.
	 * @param WC_Email $email         Email object.
	 */
	do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

	/**
	 * Action hook to display order meta data.
	 *
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Whether the email is being sent to admin.
	 * @param bool     $plain_text    Whether the email is plain text.
	 * @param WC_Email $email         Email object.
	 */
	do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

	/**
	 * Action hook to display customer details.
	 *
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Whether the email is being sent to admin.
	 * @param bool     $plain_text    Whether the email is plain text.
	 * @param WC_Email $email         Email object.
	 */
	do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
endif;

/** // phpcs:ignore
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
?>

<?php
/**
 * Booster Elite for WooCommerce - Module - Cart Abandonment Orders
 *
 * @version 7.2.7
 * @author  Pluggabl LLC.
 * @package Booster_Elite_For_WooCommerce/includes
 */

$session_id       = filter_input( INPUT_GET, 'session_id', FILTER_UNSAFE_RAW );
$checkout_details = $this->get_checkout_deta( $session_id );

$user_details = unserialize( $checkout_details->checkout_data ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
if ( wp_get_referer() ) {
	$back_link = wp_get_referer();
} else {
	$back_link = add_query_arg(
		array(
			'page' => 'wcj-tools',
			'tab'  => 'cart_abandonment',
		),
		admin_url( '/admin.php' )
	);
}
$token_data = array( 'wcj_ca_session_id' => $checkout_details->session_id );

$token = rawurlencode( base64_encode( http_build_query( (array) $token_data ) ) ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

$checkout_url = get_permalink( $checkout_details->checkout_id ) . '?wcj_restore_ac_token=' . $token;
$checkout_url = esc_url( $checkout_url );

$scheduled_email = $this->get_scheduled_email( $checkout_details->session_id );

?>
<div class="wcj-setting-jetpack-body cart_abandonment_main">
	<div class="wcj-col-12">
		<a href="<?php echo esc_attr( $back_link ); ?>" class="button"> <?php esc_html_e( 'Back to Reports', 'woocommerce-jetpack' ); ?>
		</a>
	</div>
	<div class="wcj-col-12 wcj-bg-white">
		<div class="wcj-padding-10">
			<div class="wcj-col-8">
				<h2><?php esc_html_e( 'Order Details:', 'woocommerce-jetpack' ); ?></h2>
				<?php
					$cart_items = unserialize( $checkout_details->cart_data ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
					$cart_total = $checkout_details->cart_total;

				if ( ! is_array( $cart_items ) || ! count( $cart_items ) ) {
					return;
				}

					$currency_symbol = get_woocommerce_currency_symbol();
					$tr              = '';
					$total           = 0;
					$discount        = 0;
					$order_tax       = 0;

				foreach ( $cart_items as $cart_item ) {

					if ( isset( $cart_item['product_id'] ) && isset( $cart_item['quantity'] ) && isset( $cart_item['line_total'] ) && isset( $cart_item['line_subtotal'] ) ) {
						$product_id = 0 !== $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
						$discount   = number_format_i18n( $discount + ( $cart_item['line_subtotal'] - $cart_item['line_total'] ), 2 );
						$total      = number_format_i18n( $total + $cart_item['line_subtotal'], 2 );
						$order_tax  = number_format_i18n( $order_tax + $cart_item['line_tax'], 2 );
						$image_url  = get_the_post_thumbnail_url( $product_id );
						$image_url  = ! empty( $image_url ) ? $image_url : get_the_post_thumbnail_url( $cart_item['product_id'] );

						$product      = wc_get_product( $product_id );
						$product_name = $product ? $product->get_formatted_name() : '';

						if ( empty( $image_url ) ) {
							$image_url = '';
						}

						$tr = $tr . '<tr >
			                           <td ><img class="demo_img" width="42" height="42" src=" ' . esc_url( $image_url ) . ' "/></td>
									   <td >' . $product_name . '</td>
			                           <td > ' . $cart_item['quantity'] . ' </td>
			                           <td >' . $currency_symbol . number_format_i18n( $product->get_price(), 2 ) . '</td>
			                           <td  >' . $currency_symbol . number_format_i18n( $cart_item['line_total'], 2 ) . '</td>
			                        </tr> ';
					}
				}

				echo wp_kses_post(
					'<table align="left" cellspacing="0" class="widefat fixed striped posts">
						<thead>
							<tr>
							<th  >' . __( 'Item', 'woocommerce-jetpack' ) . '</th>
							<th  >' . __( 'Product Name', 'woocommerce-jetpack' ) . '</th>
							<th  >' . __( 'Quantity', 'woocommerce-jetpack' ) . '</th>
							<th  >' . __( 'Price', 'woocommerce-jetpack' ) . '</th>
							<th  >' . __( 'Subtotal', 'woocommerce-jetpack' ) . '</th>
							</tr>
						</thead>
						<tbody>
						' . $tr . ' 
							<tr  id="wcj-discount">
								<td  colspan="4" align="right">' . __( 'Discount', 'woocommerce-jetpack' ) . '</td>
								<td>' . $currency_symbol . ( $discount ) . '</td>
							</tr>
							<tr id="wcj-other">
								<td colspan="4" align="right">' . __( 'Tax & Other', 'woocommerce-jetpack' ) . '</td>
								<td>' . $currency_symbol . ( $order_tax ) . '</td>
							</tr>

							<tr  id="wcj-shipping">
								<td colspan="4" align="right">' . __( 'Shipping', 'woocommerce-jetpack' ) . '</td>
								<td>' . $currency_symbol . number_format_i18n( $discount + ( $cart_total - $total ) - $order_tax, 2 ) . '</td>
							</tr>
							<tr  id="wcj-cart-total">
								<td colspan="4" align="right">' . __( 'Cart Total', 'woocommerce-jetpack' ) . '</td>
								<td>' . $currency_symbol . $cart_total . '</td>
							</tr>
						</tbody>
					</table>'
				);
				?>
			</div>
			<div class="wcj-col-5">
				<div class="wcj-col-12">
					<h3><?php esc_html_e( 'Billing Address', 'woocommerce-jetpack' ); ?> </h3>
					<?php
						echo wp_kses_post(
							'<p>
								<strong>' . __( 'Name', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['billing_first_name'] . ' ' . $user_details['billing_last_name'] . ' &nbsp;&nbsp;
								<strong>' . __( 'Email', 'woocommerce-jetpack' ) . '</strong> : ' . $checkout_details->email . '</br> 
								<strong>' . __( 'Phone', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['billing_phone'] . ' &nbsp;&nbsp;
								<strong>' . __( 'Country', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['billing_country'] . ' &nbsp;&nbsp;
								<strong>' . __( 'State', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['billing_state'] . '&nbsp;&nbsp;
								<strong>' . __( 'City', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['billing_city'] . ' &nbsp;&nbsp;
								<strong>' . __( 'Postcode', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['billing_postcode'] . ' &nbsp;&nbsp;<br>
								<strong>' . __( 'Address 1', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['billing_address_1'] . '<br>
								<strong>' . __( 'Address 2', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['billing_address_2'] . '
							</p></br>'
						);
						?>
					<h3><?php esc_html_e( 'Shipping Address', 'woocommerce-jetpack' ); ?> </h3>
					<?php
					if ( '' !== $user_details['shipping_country'] || '' !== $user_details['shipping_state'] || '' !== $user_details['shipping_city'] || '' !== $user_details['shipping_postcode'] || '' !== $user_details['shipping_address_1'] || '' !== $user_details['shipping_address_2'] ) {
						echo wp_kses_post(
							'<p>
								<strong>' . __( 'Country', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['shipping_country'] . ' &nbsp;&nbsp;
								<strong>' . __( 'State', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['shipping_state'] . '&nbsp;&nbsp;
								<strong>' . __( 'City', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['shipping_city'] . ' &nbsp;&nbsp;
								<strong>' . __( 'Postcode', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['shipping_postcode'] . ' &nbsp;&nbsp;<br>
								<strong>' . __( 'Address 1', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['shipping_address_1'] . ' <br>
								<strong>' . __( 'Address 2', 'woocommerce-jetpack' ) . '</strong> : ' . $user_details['shipping_address_2'] . '
							</p>'
						);
					} else {
						echo '<p><strong>' . esc_html__( 'Same as billing address', 'woocommerce-jetpack' ) . '</strong></p></br>';
					}
					echo '<p><strong>' . esc_html__( 'Restore Cart Data Link', 'woocommerce-jetpack' ) . '</strong> : <a href="' . esc_url( $checkout_url ) . '"> click here </a></p>';
					?>
				</div>
			</div>
		</div>
	</div>
	<div class="wcj-col-8 wcj-bg-white">
		<div class="wcj-padding-10">
			<h2><?php esc_html_e( 'Email histrory:', 'woocommerce-jetpack' ); ?></h2>
			<table class="widefat fixed striped posts">
				<tr>
					<th><?php esc_html_e( 'Subject', 'woocommerce-jetpack' ); ?></th>
					<th><?php esc_html_e( 'Coupon', 'woocommerce-jetpack' ); ?></th>
					<th><?php esc_html_e( 'Status', 'woocommerce-jetpack' ); ?></th>
					<th><?php esc_html_e( 'Time', 'woocommerce-jetpack' ); ?></th>
				</tr>
				<?php
				if ( $scheduled_email ) {
					foreach ( $scheduled_email as $schedule ) {
						$subject = wcj_get_option( 'wcj_ca_email_template_subject_' . $schedule->template_id );
						?>
						<tr>
							<td><?php echo esc_attr( $subject ); ?></td>
							<td><?php echo esc_attr( $schedule->coupon_code ); ?></td>
							<td>
								<?php
								if ( '1' === $schedule->status ) {
									echo 'Sent';
								} else {
									echo '';
								}
								?>
							</td>
							<td><?php echo esc_html( gmdate( 'd-m-Y h:i A', strtotime( $schedule->time ) ) ); ?></td>
						</tr>
						<?php
					}
				} else {
					echo '<tr><td colspan="4">No data found.</td></tr>';
				}
				?>
			</table>
		</div>
	</div>
</div>

<?php
/**
 * Booster for WooCommerce - Welcome Screen Content
 *
 * @version 5.6.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

?>
<div class="wcj-welcome-page">
	<div class="wcj-welcome-container">
		<div class="wcj-welcome-content-main">
			<div class="wcj-welcome-content-logo-main">
				<div class="wcj-welcome-content-logo">
				</div>
			</div>
			<div class="wcj-welcome-content-inner">
				<h3> <?php esc_html_e( 'Welcome to booster.', 'woocommerce-jetpack' ); ?> </h3>
				<p> <?php esc_html_e( 'Thank you for choosing Booster - Supercharge your WooCommerce site with these awesome powerful features. More than 100 modules. All in one WooCommerce plugin.', 'woocommerce-jetpack' ); ?> </p>
				<a href="<?php echo wp_kses_post( admin_url( wcj_admin_tab_url() ) ); ?>" class="wcj-buy-puls-btn"> <?php esc_html_e( 'Launch Booster Settings', 'woocommerce-jetpack' ); ?> </a>
			</div>
		</div>
		<div class="wcj-welcome-content-main wcj-welcome-padding-top-0">
			<div class="wcj-welcome-content-inner">
				<div class="wcj-buy-puls-btn-main">
					<a target="_blank" href="https://booster.io/buy-booster/" class="wcj-buy-puls-btn"> <?php esc_html_e( 'Upgrade Booster to unlock this feature.', 'woocommerce-jetpack' ); ?> </a>
				</div>
				<div class="wcj-welcome-content-inner wcj-buy-puls-content-row">
					<div class="wcj-buy-puls-content-col-4">
						<div class="wcj-badge">
							<img src="<?php echo wp_kses_post( wcj_plugin_url() ); ?>/assets/images/30day-guarantee.png">
							<span class="wcj-badge-sp-cn"> <?php esc_html_e( '30-Day Risk Free', 'woocommerce-jetpack' ); ?> <br> <?php esc_html_e( 'Money Back Guarantee', 'woocommerce-jetpack' ); ?> </span>
						</div>
					</div>
					<div class="wcj-buy-puls-content-col-4">
						<div class="wcj-badge">
							<img src="<?php echo wp_kses_post( wcj_plugin_url() ); ?>/assets/images/wp-logo.svg">
							<span class="wcj-badge-sp-cn"><?php esc_html_e( '400+ 5-Star', 'woocommerce-jetpack' ); ?> <br> <?php esc_html_e( 'Reviews', 'woocommerce-jetpack' ); ?></span>
						</div>
					</div>
					<div class="wcj-buy-puls-content-col-4">
						<div class="wcj-badge">
							<img src="<?php echo wp_kses_post( wcj_plugin_url() ); ?>/assets/images/trust-icon.png">
							<span class="wcj-badge-sp-cn"><?php esc_html_e( 'Trusted by', 'woocommerce-jetpack' ); ?> <br> <?php esc_html_e( '100,000+', 'woocommerce-jetpack' ); ?> <br> <?php esc_html_e( 'Websites', 'woocommerce-jetpack' ); ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="wcj-welcome-content-inner wcj-welcome-padding-top-0">
				<div class="wcj-buy-puls-head">
					<h3> <?php esc_html_e( 'Tons of Customizations and Zero Coding.', 'woocommerce-jetpack' ); ?> </h3>
					<p>
						<?php esc_html_e( 'Access more than one hundred easy-to-use modules to quickly add customized functionality to your WooCommerce business', 'woocommerce-jetpack' ); ?>
						<strong><?php esc_html_e( '- Without writing a line of code.', 'woocommerce-jetpack' ); ?> </strong>
					</p>
				</div>
				<div class="wcj-welcome-content-inner wcj-buy-puls-content-row">
					<div class="wcj-buy-puls-content-col-6 wcj-feature">
						<div class="wcj-feature-img">
							<img src="<?php echo wp_kses_post( wcj_plugin_url() ); ?>/assets/images/feature-pdf.png">
						</div>
						<div class="wcj-feature-text">
							<h4> <?php esc_html_e( 'PDF Invoicing and Packing Slips', 'woocommerce-jetpack' ); ?> </h4>
							<p> <?php esc_html_e( 'Streamline your WooCommerce orders and paperwork, and deliver a seamless customer experience with the PDF Invoicing and Packing Slips module.', 'woocommerce-jetpack' ); ?> </p>
						</div>
					</div>
					<div class="wcj-buy-puls-content-col-6 wcj-feature">
						<div class="wcj-feature-img">
							<img src="<?php echo wp_kses_post( wcj_plugin_url() ); ?>/assets/images/feature-add-on.png">
						</div>
						<div class="wcj-feature-text">
							<h4> <?php esc_html_e( 'Product Addons', 'woocommerce-jetpack' ); ?> </h4>
							<p> <?php esc_html_e( 'Create addons for your WooCommerce products like support service or special offers with the Product Addons Module.', 'woocommerce-jetpack' ); ?> </p>
						</div>
					</div>
					<div class="wcj-buy-puls-content-col-6 wcj-feature">
						<div class="wcj-feature-img">
							<img src="<?php echo wp_kses_post( wcj_plugin_url() ); ?>/assets/images/feature-input-field.png">
						</div>
						<div class="wcj-feature-text">
							<h4> <?php esc_html_e( 'Product Input Fields', 'woocommerce-jetpack' ); ?> </h4>
							<p> <?php esc_html_e( 'Allow your customers to provide more details about their order with the Product Input Fields module. Super handy when selling customized products.', 'woocommerce-jetpack' ); ?> </p>
						</div>
					</div>
					<div class="wcj-buy-puls-content-col-6 wcj-feature">
						<div class="wcj-feature-img">
							<img src="<?php echo wp_kses_post( wcj_plugin_url() ); ?>/assets/images/feature-button-prices.png">
						</div>
						<div class="wcj-feature-text">
							<h4> <?php esc_html_e( 'Button and Price Labels', 'woocommerce-jetpack' ); ?> </h4>
							<p> <?php esc_html_e( 'Add custom buttons and price labels to your products with this popular module. Set automatic price for products with an empty price field.', 'woocommerce-jetpack' ); ?> </p>
						</div>
					</div>
					<div class="wcj-buy-puls-content-col-6 wcj-feature">
						<div class="wcj-feature-img">
							<img src="<?php echo wp_kses_post( wcj_plugin_url() ); ?>/assets/images/feature-prices-currency.png">
						</div>
						<div class="wcj-feature-text">
							<h4> <?php esc_html_e( 'Prices and Currencies', 'woocommerce-jetpack' ); ?> </h4>
							<p> <?php esc_html_e( 'Make it easy for customers around the globe to make purchases on your site by displaying their currency with the Prices and Currencies by Country module.', 'woocommerce-jetpack' ); ?> </p>
						</div>
					</div>
					<div class="wcj-buy-puls-content-col-6 wcj-feature">
						<div class="wcj-feature-img">
							<img src="<?php echo wp_kses_post( wcj_plugin_url() ); ?>/assets/images/feature-payment-getway.png">
						</div>
						<div class="wcj-feature-text">
							<h4> <?php esc_html_e( 'Payment Gateways', 'woocommerce-jetpack' ); ?> </h4>
							<p> <?php esc_html_e( 'Set up multiple payment gateways based on currency, shipping method, country, or state.', 'woocommerce-jetpack' ); ?> </p>
						</div>
					</div>
					<div class="wcj-buy-puls-content-col-6 wcj-feature">
						<div class="wcj-feature-img">
							<img src="<?php echo wp_kses_post( wcj_plugin_url() ); ?>/assets/images/feature-cart-checkout.png">
						</div>
						<div class="wcj-feature-text">
							<h4> <?php esc_html_e( 'Cart and Checkout', 'woocommerce-jetpack' ); ?> </h4>
							<p> <?php esc_html_e( 'Customize the shopping cart and checkout experience. Add coupons, additional fees, custom fields, and buttons with the Cart and Checkout modules.', 'woocommerce-jetpack' ); ?> </p>
						</div>
					</div>
					<div class="wcj-buy-puls-content-col-6 wcj-feature">
						<div class="wcj-feature-img">
							<img src="<?php echo wp_kses_post( wcj_plugin_url() ); ?>/assets/images/feature-emails-addtool.png">
						</div>
						<div class="wcj-feature-text">
							<h4> <?php esc_html_e( 'Emails & Additional Tools', 'woocommerce-jetpack' ); ?> </h4>
							<p> <?php esc_html_e( 'Add custom emails, additional recipients, and verification for increased security. Explore miscellaneous reporting and customization tools for increased functionality.', 'woocommerce-jetpack' ); ?> </p>
						</div>
					</div>
					<div class="wcj-buy-puls-btn-main">
						<a target="_blank" href="https://booster.io/category/features/" class="wcj-buy-puls-btn"> <?php esc_html_e( 'See All Features', 'woocommerce-jetpack' ); ?> </a>
					</div>
				</div>
				<div id="subscribe-email" class="wcj-welcome-content-inner wcj-welcome-subscribe-email">
					<h3> <?php esc_html_e( "Don't miss updates from us!", 'woocommerce-jetpack' ); ?> </h3>
					<form method="post" name="subscribe-email-form">
						<input class="form-control user_email" type="email" required="true" name="user_email" placeholder="Enter your email">
						<input class="subscribe-email-btn" type="button" name="submit_email_to_klaviyo" value="Submit">
						<?php wp_nonce_field( 'subscribe-email-nonce', 'subscribe-email-nonce' ); ?>
					</form>
					<?php
					$wpnonce = isset( $_REQUEST['subscribe-email-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['subscribe-email-nonce'] ), 'subscribe-email-nonce' ) : false;
					if ( $wpnonce && isset( $_REQUEST['msg'] ) ) {
						$subscribe_message    = '';
						$subscribe_message_id = sanitize_text_field( wp_unslash( $_REQUEST['msg'] ) );
						if ( '1' === $subscribe_message_id ) {
							$subscribe_message = sprintf( __( 'Thank you for subscribing your email', 'woocommerce-jetpack' ) );
						} elseif ( '2' === $subscribe_message_id ) {
							$subscribe_message = sprintf( __( 'You have already subscribed your email', 'woocommerce-jetpack' ) );
						} elseif ( '3' === $subscribe_message_id ) {
							$subscribe_message = sprintf( __( 'Something went wrong with your subscription. Please after some time !', 'woocommerce-jetpack' ) );
						}
						/* translators: %s: translation added */
						echo '<p style="color: #f46c5e;">' . wp_kses_post( $subscribe_message ) . '</p>';
					}
					?>
				</div>
				<div class="wcj-welcome-content-inner wcj-welcome-subscribe-email">
					<h3> <?php esc_html_e( 'Contact Us', 'woocommerce-jetpack' ); ?> </h3>
					<div class="wcj-support">
						<p><?php esc_html_e( 'Booster Plus customers get access to Premium Support and we respond within 24 business hours.', 'woocommerce-jetpack' ); ?></p>
						<a target="_blank" href="https://booster.io/my-account/booster-contact/"><?php esc_html_e( 'Booster Plus Premium Support', 'woocommerce-jetpack' ); ?></a>
					</div>
					<div class="wcj-support">
						<p><?php esc_html_e( 'Free users post on WordPress Plugin Support forum here. We check these threads twice every week Mon and Frid to respond.', 'woocommerce-jetpack' ); ?></p>
						<a target="_blank" href="https://wordpress.org/support/plugin/woocommerce-jetpack/"><?php esc_html_e( 'Booster Free Plugin Support', 'woocommerce-jetpack' ); ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

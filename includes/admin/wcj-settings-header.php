<?php
/**
 * Booster for WooCommerce - HTML of booster setting header
 *
 * @version 7.3.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wcj-new-header">
	<div class="wcj-container">
		<div class="wcj-row">
			<div class="wcj-new-header-main">
				<div class="wcj-logo">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-dashboard' ) ); ?>">
						<?php
						echo file_get_contents( wcj_plugin_url() . '/assets/images/booster-header-logo.svg' ); // phpcs:ignore
						?>
						<div class="wcj-tagline"><?php esc_html_e( 'Commerce, Super-Powered', 'woocommerce-jetpack' ); ?></div>
					</a>
				</div>
				<div class="wcj-menubar" data-ga="sidebar-main" data-gtm="booster_click_sidebar" data-gtm-container="sidebar" data-page="<?php echo esc_attr( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '' ); ?>" data-section="<?php echo esc_attr( isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '' ); ?>">
					<nav aria-label="Booster admin primary menu">
						<ul>
							<?php
								$wpnonce     = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
								$active_page = ( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '' );
								$section     = ( isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '' );
							?>
							<li>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-dashboard' ) ); ?>" class="
													<?php
													if ( 'wcj-dashboard' === $active_page ) {
														echo 'active'; }
													?>
								">
									<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/menu-icn1.png'; ?>">
									<span><?php esc_html_e( 'Dashboard', 'woocommerce-jetpack' ); ?></span>
								</a>
							</li>
							<li>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-plugins&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) ) ); ?>" class="
													<?php
													if ( 'wcj-plugins' === $active_page ) {
														echo 'active';}
													?>
								">
									<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/new-menu-icn2.png'; ?>">
									<span><?php esc_html_e( 'Plugins', 'woocommerce-jetpack' ); ?></span>
								</a>
							</li>
							<li>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-general-settings' ) ); ?>" class="
													<?php
													if ( 'wcj-general-settings' === $active_page && 'site_key' !== $section ) {
														echo 'active'; }
													?>
								">
									<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/new-menu-icn3.png'; ?>">
									<span><?php esc_html_e( 'General Settings', 'woocommerce-jetpack' ); ?></span>
								</a>
							</li>
							<li>
								<a target="_blank" href="https://booster.io/contact-support/" class="
								<?php
								if ( 'wcj-support' === $active_page ) {
									echo 'active'; }
								?>
								">
									<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/new-menu-icn5.png'; ?>">
									<span><?php esc_html_e( 'Support', 'woocommerce-jetpack' ); ?></span>
								</a>
							</li>
						</ul>
					</nav>
				</div>
			</div>
		</div>
	</div>
</div>

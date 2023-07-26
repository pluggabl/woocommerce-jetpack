<?php
/**
 * Booster for WooCommerce - HTML of booster setting general page
 *
 * @version 7.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require WCJ_FREE_PLUGIN_PATH . '/includes/admin/wcj-settings-header.php'; ?>

<div class="wcj-plugins" style="margin-top: 20px;">
	<div class="wcj-container wcj_backend_settings_container">
		<div class="wcj-row">
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
			<input type="hidden" name="action" value="wcj_save_general_settings">
			<input type="hidden" name="wcj-verify-manage-settings" value="<?php echo esc_html( wp_create_nonce( 'wcj-verify-manage-settings' ) ); ?>">
			<?php
				$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
				$section = ( isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'manager' );
			?>
			<input type="hidden" name="section" value="<?php echo esc_html( $section ); ?>">
			<input type="hidden" name="return_url" value="<?php echo esc_url( admin_url( 'admin.php?page=wcj-general-settings&section=' ) . $section ); ?>">
			<div class="wcj-plugins-sec-part">
				<div class="wcj-plugins-right-main  wcj-settings-general">
					<div class="wcj-plugins-right-listing">
						<?php
						if ( isset( $_REQUEST['msg'] ) && '' !== $_REQUEST['msg'] ) {
							echo wp_kses_post( '<div id="message" class="updated inline wcj_setting_updated" bis_skin_checked="1"><p><strong>' . sanitize_text_field( wp_unslash( $_REQUEST['msg'] ) ) . '</strong></p></div>' );
						}

						if ( 'site_key' === $section ) {
							$site_key_status = wcj_get_option( 'wcj_site_key_status', false );
							if ( isset( $site_key_status['server_response']->status ) && false !== $site_key_status['server_response']->status ) {
								echo wp_kses_post( '<div id="message" class="updated inline wcj_setting_updated" bis_skin_checked="1"><p><strong>' . __( 'License is valid.', 'woocommerce-jetpack' ) . '</strong></p></div>' );
							} else {
								echo wp_kses_post( '<div style="color:red;border-left-color:red;" id="message" class="updated inline wcj_setting_updated" bis_skin_checked="1"><p><strong>' . __( 'Error: Wrong key. Please enter correct site key and save changes to validate it.', 'woocommerce-jetpack' ) . '</strong></p></div>' );
							}
						}
						?>
						<div class="wcj-plugins-sing-acc-box">
							<?php
								$this->output_general_settings( $section );
							?>
						</div>
					</div>
				</div>
			</div>
		</form>
		</div>
	</div>
</div>

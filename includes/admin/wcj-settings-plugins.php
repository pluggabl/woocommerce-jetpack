<?php
/**
 * Booster for WooCommerce - HTML of booster all plugin page
 *
 * @version 7.1.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require WCJ_FREE_PLUGIN_PATH . '/includes/admin/wcj-settings-header.php'; ?>

<div class="wcj-plugins">
	<div class="wcj-container wcj_backend_settings_container">
		<div class="wcj-row">
			<div class="wcj-plugins-top-part-left plug_tab">
				<ul>
					<?php
						$wpnonce             = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
						$active_page         = ( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '' );
						$section             = ( isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '' );
						$wcj_search          = ( isset( $_REQUEST['wcj_search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wcj_search'] ) ) : '' );
						$wcj_search_replaced = str_replace( ' ', '+', $wcj_search );
					?>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-plugins&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) ) ); ?>" class="<?php echo 'wcj-plugins' === $active_page && 'active' !== $section ? 'active' : ''; ?>">
							<?php esc_html_e( 'All Plugins', 'woocommerce-jetpack' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-plugins&section=active&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) ) ); ?>" class="<?php echo 'wcj-plugins' === $active_page && 'active' === $section ? 'active' : ''; ?>">
							<?php esc_html_e( 'Active', 'woocommerce-jetpack' ); ?>
						</a>
					</li>
				</ul>
			</div>
			<div class="wcj-plugins-tp-search src_plug">
				<span class="wcj_admin_span"><img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/search-icn.png'; ?>"></span>
				<input type="text" id="wcj_search_modules" name="wcj_search_modules" class="wcj-search-inp" placeholder="<?php esc_html_e( 'Search plugins and settings...', 'woocommerce-jetpack' ); ?>" value="<?php echo esc_html( $wcj_search ); ?>" >
				<input return_url="<?php echo esc_url( admin_url( 'admin.php?page=wcj-plugins&wcj_search=' ) ); ?>" type="button" class="wcj-btn-sm wcj_search_modules" name="wcj_search_modules_btn" id="wcj_search_modules_btn" value="<?php esc_html_e( 'Search', 'woocommerce-jetpack' ); ?>">
			</div>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="wcj_save_module_settings">
			<input type="hidden" name="wcj-verify-save-module-settings" value="<?php echo esc_html( wp_create_nonce( 'wcj-verify-save-module-settings' ) ); ?>">
			<?php
			$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
			if ( isset( $_REQUEST['section'] ) && '' !== sanitize_text_field( wp_unslash( $_REQUEST['section'] ) ) ) {
				$wcj_cat = ( isset( $_REQUEST['wcj-cat'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wcj-cat'] ) ) : '' );
				echo '<input type="hidden" name="return_url" value="' . esc_url( admin_url( 'admin.php?page=wcj-plugins&wcj-cat=' . $wcj_cat ) . '&section=' . sanitize_text_field( wp_unslash( $_REQUEST['section'] ) ) ) . '">';
				echo '<input type="hidden" name="section" value="' . esc_html( sanitize_text_field( wp_unslash( $_REQUEST['section'] ) ) ) . '">';
			} else {
				$wcj_cat = ( isset( $_REQUEST['wcj-cat'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wcj-cat'] ) ) : '' );
				echo '<input type="hidden" name="return_url" value="' . esc_url( admin_url( 'admin.php?page=wcj-plugins&wcj-cat=' . $wcj_cat . '&wcj_search=' . $wcj_search_replaced ) ) . '">';
			}
			$phpwpnonce = isset( $_REQUEST['wcj_disable_custom_php_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_disable_custom_php_nonce'] ), 'wcj-disable-custom-php' ) : false;
			if ( isset( $_REQUEST['wcj_disable_custom_php'] ) && $phpwpnonce ) {
				echo '<input type="hidden" name="wcj_disable_custom_php" value="1">';
				echo '<input type="hidden" name="wcj_disable_custom_php_nonce" value="' . esc_html( wp_create_nonce( 'wcj-disable-custom-php' ) ) . '">';
			}
			?>
			<div class="wcj-plugins-top-part plug_sv_btn">
				<div class="wcj-plugins-top-part-right">
					<div class="wcj-btn-main">
						<input type="submit" class="wcj-btn-sm" name="wcj_save_module_settings" value="<?php esc_html_e( 'Save Changes', 'woocommerce-jetpack' ); ?>">
					</div>
				</div>
			</div>
			<div class="wcj-plugins-sec-part">
				<div class="wcj-plugins-sidebar">
					<nav>
						<?php $this->output_cats_submenu(); ?>
					</nav>
				</div>
				<div class="wcj-plugins-right-main">
					<div class="wcj-plugins-right-listing">
						<?php
						if ( isset( $_REQUEST['success'] ) && '1' === sanitize_text_field( wp_unslash( $_REQUEST['success'] ) ) ) {
							echo wp_kses_post( '<div id="message" class="updated inline wcj_setting_updated" bis_skin_checked="1"><p><strong>' . __( 'Your settings have been saved.', 'woocommerce-jetpack' ) . '</strong></p></div>' );
						}

						$section = ( isset( $_REQUEST['section'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['section'] ) ) : '' );

						$pdf_header = '<div class="wcj-plugins-sing-acc-box-head">
							<div class="wcj-plugins-sing-head-lf">
								<span class="wcj_admin_span">
									<img src="' . esc_url( wcj_plugin_url() ) . '/assets/images/pr-sm-icn.png">
								</span>
								<div class="wcj-plugins-sing-head-rh">
									<a href="' . admin_url( 'admin.php?page=wcj-plugins&wcj-cat=' . sanitize_title( 'pdf_invoicing' ) . '&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) . '&section=pdf_invoicing' ) . '"><h5>' . __( 'PDF Invoicing', 'woocommerce-jetpack' ) . '</h5></a>
									<p>' . __( 'Invoices, Proforma Invoices, Credit Notes and Packing Slips.', 'woocommerce-jetpack' ) . '</p>
								</div>
							</div>
							<div class="wcj-plugins-sing-head-right">
								<div class="wcj-plugins-border-sm-btn">
									<a target="_blank" href="' . esc_url( 'https://booster.io/docs/woocommerce-pdf-invoicing-and-packing-slips/?utm_source=module_documentation&utm_medium=dashboard_link&utm_campaign=booster_documentation' ) . '"><img src="' . esc_url( wcj_plugin_url() ) . '/assets/images/pdw-download.png"></a>
								</div>
								<div class="wcj-plugins-button-tp">
									<button id="disable_wcj_pdf_invoicing_enabled" data-type="disable" data-id="wcj_pdf_invoicing_enabled" type="button" class="wcj_enable_plugin wcj-enble-btn ' . ( 'yes' === ( wcj_get_option( 'wcj_pdf_invoicing_enabled' ) ) ? 'wcj-disable' : '' ) . '">' . __( 'Disable', 'woocommerce-jetpack' ) . '</button>
									<button id="enable_wcj_pdf_invoicing_enabled" data-type="enable" data-id="wcj_pdf_invoicing_enabled" type="button" class="wcj_enable_plugin wcj-enble-btn ' . ( 'yes' === ( wcj_get_option( 'wcj_pdf_invoicing_enabled' ) ) ? '' : 'wcj-disable' ) . '">' . __( 'Enable', 'woocommerce-jetpack' ) . '</button>
									<input id="wcj_pdf_invoicing_enabled" type="hidden" name="wcj_pdf_invoicing_enabled" value="' . ( 'yes' === ( wcj_get_option( 'wcj_pdf_invoicing_enabled' ) ) ? 'yes' : 'no' ) . '">
								</div>
								<div class="wcj-plugins-acc-arw">
									<a href="' . admin_url( 'admin.php?page=wcj-plugins&wcj-cat=' . $this->get_cat_by_section( 'pdf_invoicing' ) . '&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) . '&section=pdf_invoicing' ) . '"><img src="' . esc_url( wcj_plugin_url() ) . '/assets/images/down-arw2.png"></a>
								</div>
							</div>
						</div>';

						if ( '' !== $section && 'active' !== $section ) {
							if ( 'pdf_invoicing' === $wcj_cat && 'pdf_invoicing' !== $section ) {
								if ( wcj_is_module_enabled( 'modules_by_user_roles' ) ) {
									if ( wcj_check_modules_by_user_roles( 'pdf_invoicing' ) ) {
										echo $pdf_header; // phpcs:ignore WordPress.Security.EscapeOutput
									}
								} else {
									echo $pdf_header; // phpcs:ignore WordPress.Security.EscapeOutput
								}
							}
							echo wp_kses_post( '<div class="wcj-plugins-sing-acc-box">' );
							$this->output_settings( $section );
							echo wp_kses_post( '</div>' );
							if ( 'subscription_customization' !== $section && 'sales_notifications' !== $section ) {
								echo wp_kses_post( '<div class="wcj-plugins-sing-acc-box">' );
								$this->output_modules( $section );
								echo wp_kses_post( '</div>' );
							}
						} else {
							echo wp_kses_post( '<div class="wcj-plugins-sing-acc-box">' );
							if ( 'pdf_invoicing' === $wcj_cat ) {
								if ( wcj_is_module_enabled( 'modules_by_user_roles' ) ) {
									if ( wcj_check_modules_by_user_roles( 'pdf_invoicing' ) ) {
										echo $pdf_header; // phpcs:ignore WordPress.Security.EscapeOutput
									}
								} else {
									echo $pdf_header; // phpcs:ignore WordPress.Security.EscapeOutput
								}
							}
							$this->output_modules( $section );
							echo wp_kses_post( '</div>' );
						}
						?>
					</div>
				</div>
			</div>
		</form>
		</div>
	</div>
</div>

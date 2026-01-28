<?php
/**
 * Booster for WooCommerce - HTML of booster deshboard page
 *
 * @version 7.3.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require WCJ_FREE_PLUGIN_PATH . '/includes/admin/wcj-settings-header.php';

// Handle preset application from hub cards.
if ( isset( $_GET['apply_preset'] ) && ! empty( $_GET['apply_preset'] ) ) {
	// Verify nonce.
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wcj_apply_preset' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'woocommerce-jetpack' ) );
	}
	// Verify capability.
	// phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'woocommerce-jetpack' ) );
	}
	$preset_id = sanitize_key( wp_unslash( $_GET['apply_preset'] ) );
	if ( class_exists( 'WCJ_Presets' ) ) {
		WCJ_Presets::apply_preset( $preset_id );
	}
	// Redirect to remove query params.
	wp_safe_redirect( remove_query_arg( array( 'apply_preset', '_wpnonce' ) ) );
	exit;
}

// Render Getting Started Hub (P6) at top of dashboard.
if ( isset( $GLOBALS['wcj_getting_started_hub'] ) ) {
	$GLOBALS['wcj_getting_started_hub']->render();
}
?>

<div class="wcj-welcome-booster">
	<div class="wcj-container">
		<div class="wcj-row">
			<div class="wcj-welcome-booster-bg wcj_desh_welcome_modal">
				<div class="wcj-close-icn wcj_close_deshboard_modal" data-targetclass="wcj_desh_welcome_modal">
					<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/close.png'; ?>">
				</div>
				<div class="wcj-welcome-booster-left">
					<h2><?php esc_html_e( 'Welcome to Booster!', 'woocommerce-jetpack' ); ?></h2>
					<p><?php esc_html_e( 'The all-in-one Toolkit to Supercharge your WooCommerce Site with 120+ modules! Booster helps 100,000+ website owners increase sales, engage visitors and more. Save time and money with ready-to-use solutions!' ); ?></p>
					<div class="wcj-welcome-banner-btn">
						<div class="wcj-btn-lg-main">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-plugins' ) ); ?>" class="wcj-btn-lg wcj-btn-lg-deshboard"><?php esc_html_e( 'Launch Getting Started Wizard ', 'woocommerce-jetpack' ); ?><img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/wcj-rh-arw.png'; ?>"></a>
						</div>
						<div class="wcj-btn-link">
							<a href="javascript:;" data-targetclass="wcj_desh_welcome_modal" class="wcj_close_deshboard_modal"><?php esc_html_e( 'Dismiss', 'woocommerce-jetpack' ); ?></a>
						</div>
					</div>
				</div>
				<div class="wcj-welcome-booster-right">
					<div class="wcj-welcome-booster-right-bg">
						<div class="wcj-welcome-booster-icon">
							<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/wcj-booster.png'; ?>">
						</div>
						<h4><?php esc_html_e( '120+ Plugins. Limitless functionality.', 'woocommerce-jetpack' ); ?></h4>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Quick Setup Presets Section -->
<div class="wcj-presets-section">
	<div class="wcj-container">
		<div class="wcj-row">
			<div class="wcj-presets-header">
				<h2><?php esc_html_e( 'Quick Setup', 'woocommerce-jetpack' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Get started quickly by applying a preset configuration for common use cases:', 'woocommerce-jetpack' ); ?></p>
			</div>
			<?php
			if ( class_exists( 'WCJ_Presets' ) ) {
				WCJ_Presets::render_preset_cards();
			}
			?>
		</div>
	</div>
</div>

<div class="wcj-dashboard-box">
	<div class="wcj-container">
		<div class="wcj-row wcj-dashboard-box-listing wcj-row-flex">
			<div class="wcj-col-lg-6">
				<div class="wcj-dash-sing-box">
					<h3><?php esc_html_e( 'Getting Started', 'woocommerce-jetpack' ); ?></h3>
					<h6><?php esc_html_e( 'Let\'s get you set up with Booster', 'woocommerce-jetpack' ); ?></h6>
					<div class="wcj-dash-sing-icn-list">
						<div class="wcj-dash-sing-part">
							<div class="wcj-dash-sing-icon">
								<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/menu-icn4.png'; ?>">
							</div>
							<div class="wcj-dash-sing-icon-dtl">
								<h4><?php esc_html_e( 'Generate Site Key', 'woocommerce-jetpack' ); ?></h4>
								<p><?php esc_html_e( 'Add Booster to your website - ', 'woocommerce-jetpack' ); ?><a target="_blank" href="https://booster.io/my-account/downloads/"><?php esc_html_e( 'Generate Key', 'woocommerce-jetpack' ); ?></a></p>
							</div>
						</div>
						<div class="wcj-dash-sing-part">
							<div class="wcj-dash-sing-icon">
								<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/privacy-settings.png'; ?>">
							</div>
							<div class="wcj-dash-sing-icon-dtl">
								<h4><?php esc_html_e( 'License Booster', 'woocommerce-jetpack' ); ?></h4>
								<p><?php esc_html_e( 'Enter Booster license key', 'woocommerce-jetpack' ); ?></p>
							</div>
						</div>
					</div>
					<p><a target="_blank" href="https://booster.io/docs/"><?php esc_html_e( 'Need help? View our Documentation', 'woocommerce-jetpack' ); ?></a></p>
				</div>
			</div>
			<div class="wcj-col-lg-6">
				<div class="wcj-dash-sing-box">
					<h3><?php esc_html_e( 'Documentation', 'woocommerce-jetpack' ); ?></h3>
					<h6><?php esc_html_e( 'One stop shop for all things Booster!', 'woocommerce-jetpack' ); ?></h6>
					<div class="wcj-dash-sing-icn-list">
						<div class="wcj-dash-sing-part">
							<div class="wcj-dash-sing-icon">
								<a target="_blank" href="https://booster.io/docs/how-to-get-started-with-booster/"><img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/documentation-sm.png'; ?>"></a>
							</div>
							<div class="wcj-dash-sing-icon-dtl">
								<a target="_blank" href="https://booster.io/docs/how-to-get-started-with-booster/"><h4><?php esc_html_e( 'How to get started with Booster', 'woocommerce-jetpack' ); ?></h4>
							</div>
						</div>
						<div class="wcj-dash-sing-part">
							<div class="wcj-dash-sing-icon">
								<a target="_blank" href="https://booster.io/docs/how-to-get-started-with-booster/"><img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/documentation-sm.png'; ?>"></a>
							</div>
							<div class="wcj-dash-sing-icon-dtl">
								<a target="_blank" href="https://booster.io/docs/how-to-get-started-with-booster/"><h4><?php esc_html_e( 'Adding a license to your website', 'woocommerce-jetpack' ); ?></h4>
							</div>
						</div>
					</div>
					<p><a target="_blank" href="https://booster.io/docs/"><?php esc_html_e( 'View All Documentation', 'woocommerce-jetpack' ); ?></a></p>
				</div>
			</div>
			<div class="wcj-col-lg-6">
				<div class="wcj-dash-sing-box">
					<h3><?php esc_html_e( 'Frequently Asked Questions', 'woocommerce-jetpack' ); ?></h3>
					<div class="wcj-dash-sing-icn-list">
						<div class="wcj-dash-sing-part">
							<div class="wcj-dash-sing-icon">
								<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/c-question-2.png'; ?>">
							</div>
							<div class="wcj-dash-sing-icon-dtl">
								<h4><?php esc_html_e( 'Do I need to have coding skills to use Booster ?', 'woocommerce-jetpack' ); ?></h4>
								<p><?php esc_html_e( 'Absolutely not. You can configure pretty much everything Booster has to offer without any coding knowledge.', 'woocommerce-jetpack' ); ?></a></p>
							</div>
						</div>
						<div class="wcj-dash-sing-part">
							<div class="wcj-dash-sing-icon">
								<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/c-question-2.png'; ?>">
							</div>
							<div class="wcj-dash-sing-icon-dtl">
								<h4><?php esc_html_e( 'Will Booster slow down my website?', 'woocommerce-jetpack' ); ?></h4>
								<p><?php esc_html_e( 'Absolutely not. Booster is carefully built with performance in mind.', 'woocommerce-jetpack' ); ?></p>
							</div>
						</div>
					</div>
					<p><a target="_blannk" href="https://booster.io/faqs/"><?php esc_html_e( 'View All FAQs', 'woocommerce-jetpack' ); ?></a></p>
				</div>
			</div>
			<div class="wcj-col-lg-6">
				<div class="wcj-dash-sing-box" id="wcj_quick_action">
					<h3><?php esc_html_e( 'Quick Actions', 'woocommerce-jetpack' ); ?></h3>
					<div class="wcj-dash-sing-icn-list">
						<?php
							$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
						if ( isset( $_REQUEST['msg'] ) && '' !== $_REQUEST['msg'] ) {
							echo wp_kses_post( '<div id="message" class="updated inline wcj_setting_updated" bis_skin_checked="1"><p><strong>' . sanitize_text_field( wp_unslash( $_REQUEST['msg'] ) ) . '</strong></p></div>' );
						}
						?>
						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
							<input type="hidden" name="action" value="wcj_save_general_settings">
							<input type="hidden" name="wcj-verify-manage-settings" value="<?php echo esc_html( wp_create_nonce( 'wcj-verify-manage-settings' ) ); ?>">
							<input type="hidden" name="return_url" value="<?php echo esc_url( admin_url( 'admin.php?page=wcj-dashboard' ) ); ?>">
							<input type="hidden" name="wcj_quick_action" value="wcj_quick_action">
							<div class="wcj-dash-sing-part">
								<div class="wcj-dash-sing-icon">
									<button style="width:100px;border: 0;cursor: pointer;" class="" type="submit" name="booster_import_settings"><img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/data-upload.png'; ?>"></button>
								</div>
								<div class="wcj-dash-sing-icon-dtl">
									<input style="display: block;margin-bottom: 10px;" type="file" name="booster_import_settings_file">
									<h4><?php esc_html_e( 'Import Booster options', 'woocommerce-jetpack' ); ?></h4>
								</div>
							</div>
							<div class="wcj-dash-sing-part">
								<div class="wcj-dash-sing-icon">
									<button style="border: 0;cursor: pointer;" type="submit" name="booster_export_settings"><img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/data-download.png'; ?>"></button>
								</div>
								<div class="wcj-dash-sing-icon-dtl">
									<h4><?php esc_html_e( 'Export Booster options', 'woocommerce-jetpack' ); ?></h4>
								</div>
							</div>
						</form>
					</div>
					<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wcj-general-settings' ) ); ?>"><?php esc_html_e( 'More Actions', 'woocommerce-jetpack' ); ?></a></p>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="wcj-upgrade-booster">
	<div class="wcj-container">
		<div class="wcj-row">
			<div class="wcj-upgrade-booster-box">
				<div class="wcj-upgrade-booster-head">
					<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/wcj-booster.png'; ?>" alt="Booster Logo">
					<div class="wcj-upgrade-booster-head-rh">
						<h3><?php esc_html_e( 'Welcome to Booster', 'woocommerce-jetpack' ); ?></h3>
					</div>
					<!-- Promo Chips -->
					<div id="wcj-promo-chips" class="wcj-promo-chips" role="navigation" aria-label="Primary Booster actions" data-gtm-container="promo_chips">
						<a href="https://booster.io/buy-booster/" class="wcj-btn-chip" data-gtm="upgrade_click_elite" data-placement="dashboard_header" aria-label="Upgrade to Elite">
							<?php esc_html_e( '⚡ Upgrade to Elite', 'woocommerce-jetpack' ); ?>
						</a>
						<a href="https://booster.io/changelog/" class="wcj-btn-chip" data-gtm="see_whats_new" data-placement="dashboard_header" aria-label="See What’s New">
							<?php esc_html_e( '📜 See What’s New', 'woocommerce-jetpack' ); ?>
						</a>
					</div>
				</div>
				<div class="wcj-upgrade-booster-sub-cnt">
					<ul>
						<?php
						$features_file = WCJ_FREE_PLUGIN_PATH . '/includes/data/featuresMatrix.json';
						$features_json = file_exists( $features_file ) ? file_get_contents( $features_file ) : ''; // phpcs:ignore
						$features_data = ! empty( $features_json ) ? json_decode( $features_json, true ) : array();
						if ( is_array( $features_data ) ) {
							$count = 0;
							foreach ( $features_data as $feature ) {
								if ( $count >= 8 ) {
									break;
								}
								++$count;

								echo '<li>';
									echo '<div class="wcj-feature-top">';
									echo esc_html( $feature['label'] );

								if ( in_array( $feature['id'], array( 'cart_abandonment', 'wishlist' ), true ) ) {
									$tooltip_text = __( 'Want more? Elite unlocks automation & unlimited items.', 'woocommerce-jetpack' );
									$slug         = ! empty( $feature['slug'] ) ? esc_url( 'https://booster.io/' . $feature['slug'] ) : '#';
									echo '<span class="wcj-lite-ribbon" data-tooltip="' . esc_attr( $tooltip_text ) . '" data-link="' . $slug . '">' . __( 'Lite', 'woocommerce-jetpack' ) . '</span>'; // phpcs:ignore
								}

									echo '</div>';

									$second_line     = '';
									$tooltip_content = ! empty( $feature['freeLong'] ) ? esc_attr( $feature['freeLong'] ) : '';

								if ( ! empty( $feature['freeShort'] ) ) {
									$second_line .= esc_html( $feature['freeShort'] );
								}

								if ( ! empty( $feature['eliteShort'] ) ) {
									if ( ! empty( $second_line ) ) {
										$second_line .= ' ';
									}
									$second_line .= esc_html( $feature['eliteShort'] );
								}

								if ( $second_line ) {
									if ( $tooltip_content ) {
										echo '<div class="wcj-free-note" data-tooltip="' . $tooltip_content . '">' . $second_line . '</div>'; // phpcs:ignore
									} else {
										echo '<div class="wcj-free-note">' . $second_line . '</div>'; // phpcs:ignore
									}
								}
								echo '</li>';
							}
						}
						?>
					</ul>
					<div class="wcj-upgrade-btn-part">
						<div class="wcj-btn-main">
							<a target="_blank" href="https://booster.io/free-vs-elite/" class="wcj-btn-sm wcj-btn-gray">
								<?php esc_html_e( 'Free vs Elite (What’s included?)', 'woocommerce-jetpack' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div id="wpfooter"><?php esc_html_e( '© 2025 Booster', 'woocommerce-jetpack' ); ?></div>

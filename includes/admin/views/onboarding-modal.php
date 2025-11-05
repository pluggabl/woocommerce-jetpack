<?php
/**
 * Booster for WooCommerce - Onboarding Modal View
 *
 * @version 7.5.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$onboarding_map = include WCJ_FREE_PLUGIN_PATH . '/includes/admin/onboarding-map.php';
?>

<div id="booster-onboarding-modal" class="wrap booster-admin booster-modal" style="display: none;">
	<div class="booster-modal-overlay"></div>
	<div class="booster-modal-content">
		<div class="booster-modal-header">
			<h2><?php esc_html_e( 'Get set up fast.', 'woocommerce-jetpack' ); ?></h2>
			<p><?php esc_html_e( 'Pick a goal. We\'ll apply safe defaults you can undo anytime.', 'woocommerce-jetpack' ); ?></p>
			<div class="booster-segmented-control" role="tablist">
				<button type="button" class="segment-button active" data-mode="goals" role="tab" aria-selected="true" aria-controls="goals-panel">
					<?php esc_html_e( 'Quick Setup', 'woocommerce-jetpack' ); ?>
				</button>
				<button type="button" class="segment-button" data-mode="blueprints" role="tab" aria-selected="false" aria-controls="blueprints-panel">
					<?php esc_html_e( 'Blueprints', 'woocommerce-jetpack' ); ?>
				</button>
			</div>

			<div class="booster-progress-indicator" aria-label="<?php esc_attr_e( 'Progress', 'woocommerce-jetpack' ); ?>">
				<div class="progress-step active" data-step="choose">
					<span class="step-number">1</span>
					<span class="step-label"><?php esc_html_e( 'Choose', 'woocommerce-jetpack' ); ?></span>
				</div>
				<div class="progress-connector"></div>
				<div class="progress-step" data-step="review">
					<span class="step-number">2</span>
					<span class="step-label"><?php esc_html_e( 'Review', 'woocommerce-jetpack' ); ?></span>
				</div>
				<div class="progress-connector"></div>
				<div class="progress-step" data-step="complete">
					<span class="step-number">3</span>
					<span class="step-label"><?php esc_html_e( 'Complete', 'woocommerce-jetpack' ); ?></span>
				</div>
			</div>

			<button type="button" class="booster-modal-close" aria-label="<?php esc_attr_e( 'Close', 'woocommerce-jetpack' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>

		<div class="booster-modal-body">
			<div class="booster-goals-screen active" id="goals-panel" role="tabpanel">
				<div class="booster-goals-grid">
					<?php foreach ( $onboarding_map as $goal_id => $goal ) : ?>
						<div class="booster-goal-tile booster-tile" data-goal="<?php echo esc_attr( $goal_id ); ?>">
							<div class="goal-icon tile-icon">
								<?php echo wp_kses_post( $goal['svg_icon'] ); ?>
							</div>
							<h3><?php echo esc_html( $goal['title'] ); ?></h3>
							<p><?php echo esc_html( $goal['subtitle'] ); ?></p>
							<div class="goal-modules">
								<?php
								$module_names = array(
									'sales_notifications'  => 'Sales Notifications',
									'frequently_bought_together' => 'Frequently Bought Together',
									'one_page_checkout'    => 'One-Page Checkout',
									'reviews'              => 'Reviews',
									'order_numbers'        => 'Order Numbers',
									'admin_orders_list'    => 'Admin Orders List',
									'currency'             => 'Currency',
									'pdf_invoicing'        => 'PDF Invoicing',
									'product_addons'       => 'Product Add-ons',
									'related_products'     => 'Related Products',
									'checkout_core_fields' => 'Checkout Fields',
									'more_button_labels'   => 'Button Labels',
									'product_tabs'         => 'Product Tabs',
								);
								foreach ( $goal['modules'] as $module ) :
									$module_name = isset( $module_names[ $module['id'] ] ) ? $module_names[ $module['id'] ] : ucwords( str_replace( '_', ' ', $module['id'] ) );
									?>
									<span class="module-tag"><?php echo esc_html( $module_name ); ?></span>
								<?php endforeach; ?>
							</div>
							<span class="applied-badge" style="display: none;"><?php esc_html_e( 'Applied', 'woocommerce-jetpack' ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="booster-blueprints-screen" id="blueprints-panel" role="tabpanel">
				<div class="booster-blueprints-grid">
					<?php
					$blueprints = file_exists( WCJ_FREE_PLUGIN_PATH . '/includes/admin/onboarding-blueprints.php' )
						? require WCJ_FREE_PLUGIN_PATH . '/includes/admin/onboarding-blueprints.php'
						: array();

					foreach ( $blueprints as $blueprint_id => $blueprint ) :
						?>
						<div class="booster-blueprint-tile booster-tile" data-blueprint="<?php echo esc_attr( $blueprint_id ); ?>">
							<div class="blueprint-icon tile-icon">
								<?php echo wp_kses_post( $blueprint['svg_icon'] ); ?>
							</div>
							<h3><?php echo esc_html( $blueprint['title'] ); ?></h3>
							<p><?php echo esc_html( $blueprint['description'] ); ?></p>
							<div class="blueprint-includes">
								<strong><?php esc_html_e( 'Includes:', 'woocommerce-jetpack' ); ?></strong>
								<ul>
									<?php foreach ( $blueprint['goal_keys'] as $goal_key ) : ?>
										<?php if ( isset( $onboarding_map[ $goal_key ] ) ) : ?>
											<li><?php echo esc_html( $onboarding_map[ $goal_key ]['title'] ); ?></li>
										<?php endif; ?>
									<?php endforeach; ?>
								</ul>
							</div>
							<span class="applied-badge" style="display: none;"><?php esc_html_e( 'Applied', 'woocommerce-jetpack' ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="booster-review-screen">
				<div class="review-header">
					<button type="button" class="back-button">
						<span class="dashicons dashicons-arrow-left-alt2"></span>
						<?php esc_html_e( 'Back', 'woocommerce-jetpack' ); ?>
					</button>
					<h3 id="review-goal-title"></h3>
				</div>

				<div class="review-content">
					<div class="modules-to-enable">
						<h4><?php esc_html_e( 'We will turn on:', 'woocommerce-jetpack' ); ?></h4>
						<ul id="modules-list"></ul>
					</div>

					<div class="settings-to-apply">
						<h4><?php esc_html_e( 'We will set:', 'woocommerce-jetpack' ); ?></h4>
						<ul id="settings-list"></ul>
					</div>

					<div class="snapshot-option">
						<label>
							<input type="checkbox" id="create-snapshot" checked>
							<?php esc_html_e( 'Save an undo snapshot', 'woocommerce-jetpack' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Lets you undo these changes later.', 'woocommerce-jetpack' ); ?>
						</p>
					</div>
				</div>

				<div class="review-actions">
					<button type="button" class="button booster-btn-secondary cancel-button">
						<?php esc_html_e( 'Back', 'woocommerce-jetpack' ); ?>
					</button>
					<button type="button" class="button booster-btn-primary apply-button">
						<?php esc_html_e( 'Apply changes', 'woocommerce-jetpack' ); ?>
					</button>
				</div>
			</div>

			<div class="booster-success-screen">
				<div class="success-icon">
					<span class="dashicons dashicons-yes-alt"></span>
				</div>
				<h3><?php esc_html_e( 'All set.', 'woocommerce-jetpack' ); ?></h3>
				<p id="success-message"><?php esc_html_e( 'Changes applied.', 'woocommerce-jetpack' ); ?></p>
				<div id="next-steps-container" style="display: none;">
					<h4><?php esc_html_e( 'Next steps:', 'woocommerce-jetpack' ); ?></h4>
					<ul id="next-steps-list"></ul>
				</div>

				<div id="pro-note-container" style="display: none;">
					<p class="pro-note">
						<span class="dashicons dashicons-star-filled"></span>
						<a href="#" id="pro-note-link" target="_blank"></a>
					</p>
				</div>

				<div class="success-actions">
					<button type="button" class="button booster-btn-secondary pick-another-button">
						<?php esc_html_e( 'Pick Another Goal', 'woocommerce-jetpack' ); ?>
					</button>
					<a href="#" class="button booster-btn-primary booster-link primary-cta-button" style="display: none;">
						<span id="primary-cta-text"></span>
					</a>
				</div>
			</div>

			<div class="booster-loading-screen">
				<div class="loading-spinner">
					<span class="dashicons dashicons-update"></span>
				</div>
				<p id="loading-message"><?php esc_html_e( 'Applying…', 'woocommerce-jetpack' ); ?></p>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	$('#launch-onboarding-modal').on('click', function() {
		$('#booster-onboarding-modal').show();
		$('.booster-goals-screen').addClass('active');
		$('.booster-review-screen, .booster-success-screen, .booster-loading-screen').removeClass('active');
		$('#booster-onboarding-modal').focus();
	});
});
</script>

<?php
/**
 * Booster for WooCommerce - Onboarding Modal View
 *
 * @version 7.4.0
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
	<div class="booster-modal-content" role="dialog" aria-labelledby="onboarding-modal-title" aria-describedby="onboarding-modal-description" aria-modal="true">
		<div class="booster-modal-header">
			<h2 id="onboarding-modal-title"><?php esc_html_e( 'Get set up fast.', 'woocommerce-jetpack' ); ?></h2>
			<p id="onboarding-modal-description"><?php esc_html_e( 'Pick a goal. We\'ll apply safe defaults you can undo anytime.', 'woocommerce-jetpack' ); ?></p>
			<button type="button" class="booster-modal-close" aria-label="<?php esc_attr_e( 'Close', 'woocommerce-jetpack' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>

		<div class="booster-modal-progress">
			<div class="progress-steps">
				<div class="step active" data-step="goals">
					<span class="step-number">1</span>
					<span class="step-label"><?php esc_html_e( 'Choose Goal', 'woocommerce-jetpack' ); ?></span>
				</div>
				<div class="step" data-step="review">
					<span class="step-number">2</span>
					<span class="step-label"><?php esc_html_e( 'Review', 'woocommerce-jetpack' ); ?></span>
				</div>
				<div class="step" data-step="complete">
					<span class="step-number">3</span>
					<span class="step-label"><?php esc_html_e( 'Complete', 'woocommerce-jetpack' ); ?></span>
				</div>
			</div>
			<div class="progress-bar">
				<div class="progress-fill" style="width: 33%;"></div>
			</div>
		</div>

		<div class="booster-modal-body">
			<div class="booster-goals-screen active">
				<div class="booster-goals-grid" role="radiogroup" aria-labelledby="onboarding-modal-title">
					<?php foreach ( $onboarding_map as $goal_id => $goal ) : ?>
						<div class="booster-goal-tile booster-tile" 
							data-goal="<?php echo esc_attr( $goal_id ); ?>"
							role="radio"
							aria-checked="false"
							abindex="0"
							aria-labelledby="goal-title-<?php echo esc_attr( $goal_id ); ?>"
							aria-describedby="goal-desc-<?php echo esc_attr( $goal_id ); ?>">
							<div class="goal-icon tile-icon" aria-hidden="true">
								<?php echo wp_kses_post( $goal['svg_icon'] ); ?>
							</div>
							<h3 id="goal-title-<?php echo esc_attr( $goal_id ); ?>"><?php echo esc_html( $goal['title'] ); ?></h3>
							<p id="goal-desc-<?php echo esc_attr( $goal_id ); ?>"><?php echo esc_html( $goal['subtitle'] ); ?></p>
							<div class="goal-modules">
								<?php
								$module_names = array(
									'sales_notifications' => 'Sales Notifications',
									'frequently_bought_together' => 'Frequently Bought Together',
									'one_page_checkout'   => 'One-Page Checkout',
									'reviews'             => 'Reviews',
									'order_numbers'       => 'Order Numbers',
									'admin_orders_list'   => 'Admin Orders List',
									'currency'            => 'Currency',
								);
								foreach ( $goal['modules'] as $module ) :
									$module_name = isset( $module_names[ $module['id'] ] ) ? $module_names[ $module['id'] ] : ucwords( str_replace( '_', ' ', $module['id'] ) );
									?>
									<span class="module-tag"><?php echo esc_html( $module_name ); ?></span>
								<?php endforeach; ?>
							</div>
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
				<h3><?php esc_html_e( 'Goal Applied Successfully!', 'woocommerce-jetpack' ); ?></h3>
				<p id="success-message"><?php esc_html_e( 'Your selected modules have been enabled and configured.', 'woocommerce-jetpack' ); ?></p>
				<div class="success-quick-wins">
					<h4><?php esc_html_e( 'What happens next?', 'woocommerce-jetpack' ); ?></h4>
					<ul id="next-steps-list"></ul>
				</div>
				<div class="success-actions">
					<a href="#" id="next-step-link" class="button button-primary next-step-button" style="display:none;">
						<span id="next-step-text"></span>
					</a>
					<button type="button" class="button close-button">
						<?php esc_html_e( 'Close', 'woocommerce-jetpack' ); ?>
					</button>
					<button type="button" class="button button-secondary" id="pick-another-goal">
						<?php esc_html_e( 'Pick Another Goal', 'woocommerce-jetpack' ); ?>
					</button>
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

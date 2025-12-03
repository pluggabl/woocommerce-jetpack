<?php
/**
 * Booster for WooCommerce - Quick Start Admin UI
 *
 * This file contains the admin UI functions for rendering and handling Quick Start presets.
 * It provides the visual interface that allows users to apply preset configurations to modules.
 *
 * @version 7.8.0
 * @since   7.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Render the Quick Start box for a given module.
 *
 * This function checks if the module has any Quick Start presets defined.
 * If presets exist, it renders a compact UI box at the top of the module settings page
 * with buttons to apply each preset.
 *
 * If no presets are defined for the module, this function returns early and renders nothing.
 *
 * @version 7.8.0
 * @since   7.8.0
 * @param   string $module_id The module's canonical ID (e.g., 'cart_abandonment').
 * @return  void
 */
function wcj_quick_start_render_box( $module_id ) {
	// Validate input.
	if ( empty( $module_id ) || ! is_string( $module_id ) ) {
		return;
	}

	// Get presets for this module.
	$presets = wcj_quick_start_get_presets_for_module( $module_id );

	// If no presets, render nothing.
	if ( empty( $presets ) ) {
		return;
	}

	// Get module metadata.
	$module_meta = wcj_quick_start_get_module_meta( $module_id );
	$module_name = isset( $module_meta['module_name'] ) ? $module_meta['module_name'] : '';
	$headline    = isset( $module_meta['headline'] ) ? $module_meta['headline'] : '';

	// Start rendering the Quick Start box.
	?>
	<div class="wcj-quick-start-box notice notice-info" style="position: relative; padding: 15px; margin: 20px; border-radius: 5px;">
		<h3 style="margin-top: 0;">
			<span class="dashicons dashicons-star-filled" style="color: #f0b849;"></span>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: Module name */
					__( 'Quick Start: %s', 'woocommerce-jetpack' ),
					$module_name
				)
			);
			?>
		</h3>

		<?php if ( ! empty( $headline ) ) : ?>
			<p style="margin: 10px 0 15px 0; font-size: 14px;">
				<?php echo esc_html( $headline ); ?>
			</p>
		<?php endif; ?>

		<div class="wcj-quick-start-buttons" style="margin: 15px 0;">
			<?php foreach ( $presets as $preset_id => $preset ) : ?>
				<?php
				$label    = isset( $preset['label'] ) ? $preset['label'] : $preset_id;
				$settings = isset( $preset['settings'] ) ? $preset['settings'] : array();
				?>
				<button
					type="button"
					class="wcj-quick-start-apply"
					data-module-id="<?php echo esc_attr( $module_id ); ?>"
					data-preset-id="<?php echo esc_attr( $preset_id ); ?>"
					data-settings="<?php echo esc_attr( wp_json_encode( $settings ) ); ?>"
					style="margin-right: 10px; margin-bottom: 5px;"
				>
					<?php echo esc_html( $label ); ?>
				</button>
			<?php endforeach; ?>
		</div>

		<?php
		// Show steps/checklist if any preset has steps.
		$has_steps     = false;
		$steps_to_show = array();
		$first_preset  = reset( $presets );
		if ( isset( $first_preset['steps'] ) && is_array( $first_preset['steps'] ) && ! empty( $first_preset['steps'] ) ) {
			$has_steps     = true;
			$steps_to_show = $first_preset['steps'];
		}

		if ( $has_steps ) :
			?>
			<div class="wcj-quick-start-steps" style="margin: 15px 0; padding: 10px; background: #f9f9f9; border-left: 3px solid #f0b849;">
				<ul style="margin: 5px 0; padding-left: 20px;">
					<?php foreach ( $steps_to_show as $step ) : ?>
						<li style="margin: 5px 0;">
							<span class="dashicons dashicons-yes" style="color: #46b450;"></span>
							<?php echo esc_html( $step ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div class="wcj-quick-start-message" aria-live="polite" style="margin: 10px 0; padding: 10px; display: none; background: #d4edda; border-left: 4px solid #46b450; color: #155724; border-radius: 3px;">
		</div>

		<p style="margin: 10px 0 0 0;">
			<a href="" class="wcj-quick-start-advanced" style="text-decoration: none;">
				<?php esc_html_e( 'See advanced options →', 'woocommerce-jetpack' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * Enqueue Quick Start admin scripts and styles.
 *
 * This function enqueues the JavaScript file that handles applying presets
 * when users click the preset buttons.
 *
 * @version 7.8.0
 * @since   7.8.0
 * @return  void
 */
function wcj_quick_start_enqueue_admin_scripts() {
	// Load ONLY on Booster plugin settings pages.
	if ( isset( $_GET['page'] ) && 'wcj-plugins' === $_GET['page'] ) {//phpcs:ignore WordPress.Security.NonceVerification

		// Enqueue the Quick Start JavaScript.
		wp_enqueue_script(
			'wcj-quick-start',
			wcj_plugin_url() . '/includes/js/wcj-quick-start.js',
			array( 'jquery' ),
			w_c_j()->version,
			true
		);

		// Optional: Localize script with confirm message.
		wp_localize_script(
			'wcj-quick-start',
			'wcjQuickStart',
			array(
				'confirmMessage' => __( 'Preset applied! Review the settings below and click "Save changes".', 'woocommerce-jetpack' ),
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'wcj_quick_start_enqueue_admin_scripts' );

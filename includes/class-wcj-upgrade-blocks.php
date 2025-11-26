<?php
/**
 * Booster for WooCommerce - Upgrade Blocks
 *
 * Central configuration and rendering for Lite → Elite upgrade blocks.
 *
 * @version 7.6.0
 * @since   7.6.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'wcj_get_upgrade_blocks_config' ) ) {
	/**
	 * Get all Lite → Elite upgrade block configs.
	 *
	 * Returns an array of upgrade block configurations for modules that have
	 * Lite versions in the free plugin and enhanced features in Elite.
	 *
	 * @version 7.6.0
	 * @since   7.6.0
	 * @return  array Upgrade block configurations keyed by module_id.
	 */
	function wcj_get_upgrade_blocks_config() {
		$config = array(
			// Example stub entry for Abandoned Cart Lite.
			'abandoned_cart_lite' => array(
				'enabled'        => true,
				'module_id'      => 'abandoned_cart_lite',
				'lite_label'     => __( 'Abandoned Cart Lite', 'woocommerce-jetpack' ),
				'headline'       => __( 'Unlock the full power in Booster Elite', 'woocommerce-jetpack' ),
				'benefits'       => array(
					__( 'Send up to 3 recovery emails (Lite: 1)', 'woocommerce-jetpack' ),
					__( 'Add automatic discount coupons', 'woocommerce-jetpack' ),
					__( 'Exclude certain user roles (e.g., wholesalers)', 'woocommerce-jetpack' ),
					__( 'See more detailed recovery stats', 'woocommerce-jetpack' ),
				),
				'comparison_url' => 'https://booster.io/docs/',
				'upgrade_url'    => 'https://booster.io/buy-booster/',
			),
			// Additional Lite modules will be added in Unit 3.2.
		);

		/**
		 * Filter upgrade block configurations.
		 *
		 * Allows external code to modify or extend upgrade block config.
		 *
		 * @since 7.6.0
		 * @param array $config Upgrade block configurations.
		 */
		return apply_filters( 'wcj_upgrade_blocks_config', $config );
	}
}

if ( ! function_exists( 'wcj_get_upgrade_block_config' ) ) {
	/**
	 * Get upgrade block config for a single module.
	 *
	 * Returns the configuration for a specific module's upgrade block,
	 * or null if the module doesn't have an enabled upgrade block.
	 *
	 * @version 7.6.0
	 * @since   7.6.0
	 * @param   string $module_id The module identifier.
	 * @return  array|null Module config array or null if not found/disabled.
	 */
	function wcj_get_upgrade_block_config( $module_id ) {
		$config    = wcj_get_upgrade_blocks_config();
		$module_id = (string) $module_id;

		if ( isset( $config[ $module_id ] ) && ! empty( $config[ $module_id ]['enabled'] ) ) {
			return $config[ $module_id ];
		}

		return null;
	}
}

if ( ! function_exists( 'wcj_has_upgrade_block' ) ) {
	/**
	 * Check if a given module has an enabled upgrade block.
	 *
	 * @version 7.6.0
	 * @since   7.6.0
	 * @param   string $module_id The module identifier.
	 * @return  bool True if module has an enabled upgrade block, false otherwise.
	 */
	function wcj_has_upgrade_block( $module_id ) {
		return ( null !== wcj_get_upgrade_block_config( $module_id ) );
	}
}

if ( ! function_exists( 'wcj_render_upgrade_block' ) ) {
	/**
	 * Render the Lite → Elite upgrade block for a given module.
	 *
	 * Outputs an informational upgrade block with benefits, comparison link,
	 * and upgrade CTA. Does nothing if the module has no enabled config.
	 *
	 * @version 7.6.0
	 * @since   7.6.0
	 * @param   string $module_id The module identifier.
	 * @return  void
	 */
	function wcj_render_upgrade_block( $module_id ) {
		$config = wcj_get_upgrade_block_config( $module_id );

		if ( ! $config ) {
			return; // Graceful no-op if no config.
		}

		// Safely extract and escape values.
		$lite_label     = isset( $config['lite_label'] ) ? $config['lite_label'] : '';
		$headline       = isset( $config['headline'] ) ? $config['headline'] : '';
		$benefits       = ! empty( $config['benefits'] ) && is_array( $config['benefits'] ) ? $config['benefits'] : array();
		$comparison_url = isset( $config['comparison_url'] ) ? $config['comparison_url'] : '';
		$upgrade_url    = isset( $config['upgrade_url'] ) ? $config['upgrade_url'] : '';

		// Output markup using WordPress admin styles.
		?>
		<div class="wcj-upgrade-block notice notice-info">
			<p class="wcj-upgrade-block__intro">
				<?php
				printf(
					/* translators: %s: Lite module label. */
					esc_html__( "You're using: %s", 'woocommerce-jetpack' ),
					'<strong>' . esc_html( $lite_label ) . '</strong>'
				);
				?>
			</p>

			<?php if ( $headline ) : ?>
				<p class="wcj-upgrade-block__headline">
					<?php echo esc_html( $headline ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $benefits ) ) : ?>
				<ul class="wcj-upgrade-block__benefits">
					<?php foreach ( $benefits as $benefit ) : ?>
						<li><?php echo esc_html( $benefit ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<p class="wcj-upgrade-block__actions">
				<?php if ( $comparison_url ) : ?>
					<a
						href="<?php echo esc_url( $comparison_url ); ?>"
						class="button button-secondary"
						target="_blank"
						rel="noopener noreferrer"
					>
						<?php esc_html_e( 'See full comparison →', 'woocommerce-jetpack' ); ?>
					</a>
				<?php endif; ?>

				<?php if ( $upgrade_url ) : ?>
					<a
						href="<?php echo esc_url( $upgrade_url ); ?>"
						class="button button-primary"
						target="_blank"
						rel="noopener noreferrer"
					>
						<?php esc_html_e( 'Upgrade to Booster Elite →', 'woocommerce-jetpack' ); ?>
					</a>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}
}

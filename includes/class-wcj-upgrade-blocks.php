<?php
/**
 * Booster for WooCommerce - Upgrade Blocks
 *
 * Central configuration and rendering for Lite → Elite upgrade blocks.
 *
 * @version 7.9.0
 * @since   7.9.0
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
	 * @version 7.9.0
	 * @since   7.9.0
	 * @return  array Upgrade block configurations keyed by module_id.
	 */
	function wcj_get_upgrade_blocks_config() {
		$config = array(
			// Cart Abandoned Lite.
			'cart_abandonment'           => array(
				'enabled'        => true,
				'module_id'      => 'cart_abandonment',
				'lite_label'     => __( 'Cart Abandoned Lite', 'woocommerce-jetpack' ),
				'headline'       => __( 'Unlock the full power in Booster Elite', 'woocommerce-jetpack' ),
				'benefits'       => array(
					__( 'Send up to 3 recovery emails (Lite: 1)', 'woocommerce-jetpack' ),
					__( 'Add automatic discount coupons', 'woocommerce-jetpack' ),
					__( 'Exclude certain user roles (e.g., wholesalers)', 'woocommerce-jetpack' ),
					__( 'See more detailed recovery stats', 'woocommerce-jetpack' ),
				),
				'comparison_url' => 'https://booster.io/docs/woocommerce-cart-abandonment/',
				'upgrade_url'    => 'https://booster.io/buy-booster/',
			),
			// Wishlist Lite.
			'wishlist'                   => array(
				'enabled'        => true,
				'module_id'      => 'wishlist',
				'lite_label'     => __( 'Wishlist Lite', 'woocommerce-jetpack' ),
				'headline'       => __( 'Unlock the full power in Booster Elite', 'woocommerce-jetpack' ),
				'benefits'       => array(
					__( 'Multiple wishlists per customer', 'woocommerce-jetpack' ),
					__( 'Email reminders for saved items', 'woocommerce-jetpack' ),
					__( 'More styling and placement options', 'woocommerce-jetpack' ),
				),
				'comparison_url' => 'https://booster.io/docs/woocommerce-wishlist/',
				'upgrade_url'    => 'https://booster.io/buy-booster/',
			),
			// Variation Swatches Lite.
			'product_variation_swatches' => array(
				'enabled'        => true,
				'module_id'      => 'product_variation_swatches',
				'lite_label'     => __( 'Variation Swatches Lite', 'woocommerce-jetpack' ),
				'headline'       => __( 'Unlock the full power in Booster Elite', 'woocommerce-jetpack' ),
				'benefits'       => array(
					__( 'More swatch types (images, labels, advanced styles)', 'woocommerce-jetpack' ),
					__( 'Per-product customizations', 'woocommerce-jetpack' ),
					__( 'Extra display and tooltip options', 'woocommerce-jetpack' ),
				),
				'comparison_url' => 'https://booster.io/docs/woocommerce-product-variation-swatches/',
				'upgrade_url'    => 'https://booster.io/buy-booster/',
			),
		);

		/**
		 * Filter upgrade block configurations.
		 *
		 * Allows external code to modify or extend upgrade block config.
		 *
		 * @since 7.9.0
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
	 * @version 7.9.0
	 * @since   7.9.0
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
	 * @version 7.9.0
	 * @since   7.9.0
	 * @param   string $module_id The module identifier.
	 * @return  bool True if module has an enabled upgrade block, false otherwise.
	 */
	function wcj_has_upgrade_block( $module_id ) {
		return ( null !== wcj_get_upgrade_block_config( $module_id ) );
	}
}

if ( ! function_exists( 'wcj_log_upgrade_block_click' ) ) {
	/**
	 * Log a click on a Lite → Elite upgrade block.
	 *
	 * Stores click events locally in WordPress options for analytics.
	 * Events are capped at 500 to prevent unbounded growth.
	 *
	 * @version 7.9.0
	 * @since   7.9.0
	 * @param   string $module_id   The module identifier.
	 * @param   string $button_type Either 'comparison' or 'upgrade'.
	 * @return  void
	 */
	function wcj_log_upgrade_block_click( $module_id, $button_type ) {
		$module_id   = (string) $module_id;
		$button_type = (string) $button_type;

		// Basic validation of button type.
		if ( ! in_array( $button_type, array( 'comparison', 'upgrade' ), true ) ) {
			return;
		}

		$events = get_option( 'wcj_upgrade_block_clicks', array() );
		if ( ! is_array( $events ) ) {
			$events = array();
		}

		$events[] = array(
			'time'      => current_time( 'mysql' ),
			'module_id' => $module_id,
			'button'    => $button_type,
			'user_id'   => get_current_user_id(),
		);

		// Cap array size so it doesn't grow forever (keep last 500).
		$max_events = 500;
		if ( count( $events ) > $max_events ) {
			$events = array_slice( $events, -1 * $max_events );
		}

		update_option( 'wcj_upgrade_block_clicks', $events, false );
	}
}

if ( ! function_exists( 'wcj_handle_upgrade_block_click' ) ) {
	/**
	 * Handle Lite → Elite upgrade block click, then redirect to target URL.
	 *
	 * This is the admin-post handler that logs the click and redirects
	 * the user to the appropriate destination (comparison or upgrade page).
	 *
	 * Expected GET params:
	 * - module_id: The module identifier
	 * - button: Either 'comparison' or 'upgrade'
	 *
	 * @version 7.9.0
	 * @since   7.9.0
	 * @return  void
	 */
	function wcj_handle_upgrade_block_click() {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'woocommerce-jetpack' ) );
		}

		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		$module_id = isset( $_GET['module_id'] ) ? sanitize_text_field( wp_unslash( $_GET['module_id'] ) ) : '';
		$button    = isset( $_GET['button'] ) ? sanitize_text_field( wp_unslash( $_GET['button'] ) ) : '';
		//phpcs:enable

		if ( $module_id && $button && function_exists( 'wcj_log_upgrade_block_click' ) ) {
			wcj_log_upgrade_block_click( $module_id, $button );
		}

		// Read config to know where to send them.
		$config = function_exists( 'wcj_get_upgrade_block_config' ) ? wcj_get_upgrade_block_config( $module_id ) : null;
		$target = '';

		if ( $config ) {
			if ( 'comparison' === $button && ! empty( $config['comparison_url'] ) ) {
				$target = $config['comparison_url'];
			} elseif ( 'upgrade' === $button && ! empty( $config['upgrade_url'] ) ) {
				$target = $config['upgrade_url'];
			}
		}

		if ( ! $target ) {
			$target = admin_url();
		}

		wp_safe_redirect( esc_url_raw( $target ) );
		exit;
	}
}

/**
 * Allow booster.io as a safe redirect host.
 *
 * @since 7.9.0
 * @param array $hosts Existing allowed redirect hosts.
 * @return array Modified allowed redirect hosts.
 */
function allow_booster_site_redirect_host( $hosts ) {
 $hosts[] = 'booster.io';
 return $hosts;
}
add_filter( 'allowed_redirect_hosts', 'allow_booster_site_redirect_host' );

// Register admin-post handler for click tracking.
add_action( 'admin_post_wcj_upgrade_block_click', 'wcj_handle_upgrade_block_click' );

if ( ! function_exists( 'wcj_register_upgrade_clicks_log_page' ) ) {
	/**
	 * Register the Upgrade Clicks Log admin page.
	 *
	 * Adds a submenu page under the Booster admin menu to view
	 * recent upgrade block click events.
	 *
	 * @version 7.9.0
	 * @since   7.9.0
	 * @return  void
	 */
	function wcj_register_upgrade_clicks_log_page() {
		global $submenu;
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $submenu['wcj-dashboard'] ) ) {
			add_submenu_page(
				'wcj-dashboard',
				__( 'Upgrade Clicks Log', 'woocommerce-jetpack' ),
				__( 'Upgrade Clicks Log', 'woocommerce-jetpack' ),
				'manage_woocommerce',
				'wcj-upgrade-clicks-log',
				'wcj_render_upgrade_clicks_log_page'
			);
		} else {
			// Retry a bit later if parent menu not found.
			add_action( 'admin_menu', 'wcj_register_upgrade_clicks_log_page', 130 );
		}
	}
}

// Register admin menu for click log page.
add_action( 'admin_menu', 'wcj_register_upgrade_clicks_log_page', 99 );

if ( ! function_exists( 'wcj_render_upgrade_clicks_log_page' ) ) {
	/**
	 * Render the Upgrade Clicks Log admin page.
	 *
	 * Displays a simple table of recent upgrade block click events
	 * with time, module ID, button type, and user ID.
	 *
	 * @version 7.9.0
	 * @since   7.9.0
	 * @return  void
	 */
	function wcj_render_upgrade_clicks_log_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'woocommerce-jetpack' ) );
		}

		$events = get_option( 'wcj_upgrade_block_clicks', array() );
		if ( ! is_array( $events ) ) {
			$events = array();
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Upgrade Clicks Log', 'woocommerce-jetpack' ); ?></h1>

			<p><?php esc_html_e( 'This table shows recent clicks on Lite → Elite upgrade blocks inside Booster free.', 'woocommerce-jetpack' ); ?></p>

			<?php if ( empty( $events ) ) : ?>
				<p><?php esc_html_e( 'No clicks logged yet.', 'woocommerce-jetpack' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Time', 'woocommerce-jetpack' ); ?></th>
							<th><?php esc_html_e( 'Module ID', 'woocommerce-jetpack' ); ?></th>
							<th><?php esc_html_e( 'Button', 'woocommerce-jetpack' ); ?></th>
							<th><?php esc_html_e( 'User ID', 'woocommerce-jetpack' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( array_reverse( $events ) as $event ) : ?>
							<tr>
								<td><?php echo isset( $event['time'] ) ? esc_html( $event['time'] ) : ''; ?></td>
								<td><?php echo isset( $event['module_id'] ) ? esc_html( $event['module_id'] ) : ''; ?></td>
								<td><?php echo isset( $event['button'] ) ? esc_html( $event['button'] ) : ''; ?></td>
								<td><?php echo isset( $event['user_id'] ) ? esc_html( $event['user_id'] ) : ''; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'wcj_render_upgrade_block' ) ) {
	/**
	 * Render the Lite → Elite upgrade block for a given module.
	 *
	 * Outputs an informational upgrade block with benefits, comparison link,
	 * and upgrade CTA. Does nothing if the module has no enabled config.
	 *
	 * @version 7.9.0
	 * @since   7.9.0
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

		// Build tracking URLs that pass through admin-post.php.
		$tracking_base = admin_url( 'admin-post.php' );

		$comparison_tracking_url = '';
		if ( $comparison_url ) {
			$comparison_tracking_url = add_query_arg(
				array(
					'action'    => 'wcj_upgrade_block_click',
					'module_id' => $module_id,
					'button'    => 'comparison',
				),
				$tracking_base
			);
		}

		$upgrade_tracking_url = '';
		if ( $upgrade_url ) {
			$upgrade_tracking_url = add_query_arg(
				array(
					'action'    => 'wcj_upgrade_block_click',
					'module_id' => $module_id,
					'button'    => 'upgrade',
				),
				$tracking_base
			);
		}

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
				<?php if ( $comparison_tracking_url ) : ?>
					<a
						href="<?php echo esc_url( $comparison_tracking_url ); ?>"
						class="btn-view-comparison"
						target="_blank"
						rel="noopener noreferrer"
					>
						<?php esc_html_e( 'See full comparison →', 'woocommerce-jetpack' ); ?>
					</a>
				<?php endif; ?>

				<?php if ( $upgrade_tracking_url ) : ?>
					<a
						href="<?php echo esc_url( $upgrade_tracking_url ); ?>"
						class="btn-upgrade-to-booster-elite"
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
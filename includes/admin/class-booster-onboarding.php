<?php
/**
 * Booster for WooCommerce - Onboarding Controller
 *
 * @version 7.3.2
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Booster_Onboarding' ) ) :

	/**
	 * Booster_Onboarding.
	 */
	class Booster_Onboarding {

		/**
		 * Onboarding map data
		 *
		 * @var array
		 */
		private $onboarding_map;

		/**
		 * Option key for storing onboarding state
		 *
		 * @var string
		 */
		private $option_key = 'booster_free_onboarding';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->onboarding_map = include WCJ_FREE_PLUGIN_PATH . '/includes/admin/onboarding-map.php';

			add_action( 'admin_menu', array( $this, 'add_getting_started_menu' ), 100 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_ajax_booster_apply_goal', array( $this, 'ajax_apply_goal' ) );
			add_action( 'wp_ajax_booster_undo_goal', array( $this, 'ajax_undo_goal' ) );
			add_action( 'wp_ajax_booster_apply_blueprint', array( $this, 'ajax_apply_blueprint' ) );
			add_action( 'wp_ajax_booster_undo_blueprint', array( $this, 'ajax_undo_blueprint' ) );
			add_action( 'wp_ajax_booster_log_onboarding_event', array( $this, 'ajax_log_onboarding_event' ) );

			add_action( 'admin_notices', array( $this, 'maybe_show_onboarding_modal' ) );
		}

		/**
		 * Add Getting Started menu item
		 */
		public function add_getting_started_menu() {
			global $submenu;

			// Only add submenu if parent menu exists.
			if ( isset( $submenu['wcj-dashboard'] ) ) {
				add_submenu_page(
					'wcj-dashboard',
					__( 'Getting Started', 'woocommerce-jetpack' ),
					__( 'Getting Started', 'woocommerce-jetpack' ),
					'manage_options',
					'wcj-getting-started',
					array( $this, 'getting_started_page' )
				);
			} else {
				// Retry a bit later if parent menu not found.
				add_action( 'admin_menu', array( $this, 'add_getting_started_menu' ), 110 );
			}
		}

		/**
		 * Enqueue scripts and styles for the Getting Started page or onboarding modal.
		 *
		 * @param string $hook The current admin page hook suffix.
		 *
		 * @return void
		 */
		public function enqueue_scripts( $hook ) {
			if ( strpos( $hook, 'wcj-getting-started' ) !== false || $this->should_show_modal() ) {
				wp_enqueue_style(
					'booster-onboarding',
					wcj_plugin_url() . '/assets/css/admin/booster-onboarding.css',
					array(),
					w_c_j()->version
				);

				wp_enqueue_script(
					'booster-onboarding',
					wcj_plugin_url() . '/assets/js/admin/booster-onboarding.js',
					array( 'jquery' ),
					w_c_j()->version,
					true
				);

				$applied_goals = array();
				$onboarding_state = get_option( $this->option_key, array() );
				if ( isset( $onboarding_state['completed_goals'] ) ) {
					$applied_goals = $onboarding_state['completed_goals'];
				}

				$blueprints = file_exists( WCJ_FREE_PLUGIN_PATH . '/includes/admin/onboarding-blueprints.php' ) 
					? include WCJ_FREE_PLUGIN_PATH . '/includes/admin/onboarding-blueprints.php' 
					: array();

				wp_localize_script(
					'booster-onboarding',
					'boosterOnboarding',
					array(
						'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
						'nonce'        => wp_create_nonce( 'booster_onboarding_nonce' ),
						'goals'        => $this->onboarding_map,
						'blueprints'   => $blueprints,
						'appliedGoals' => $applied_goals,
						'strings'      => array(
							'applying'          => __( 'Applying...', 'woocommerce-jetpack' ),
							'undoing'           => __( 'Undoing...', 'woocommerce-jetpack' ),
							'success'           => __( 'Success!', 'woocommerce-jetpack' ),
							'error'             => __( 'Error occurred. Please try again.', 'woocommerce-jetpack' ),
							'close'             => __( 'Close', 'woocommerce-jetpack' ),
							'create_demo_draft' => __( 'Create a demo draft page', 'woocommerce-jetpack' ),
							'add_one_extra'     => __( 'Add one extra currency', 'woocommerce-jetpack' ),
							'confirmUndo'       => __( 'Are you sure you want to undo this goal?', 'woocommerce-jetpack' ),
						),
					)
				);
			}
		}

		/**
		 * Check if modal should be shown
		 */
		private function should_show_modal() {
			$onboarding_state = get_option( $this->option_key, array() );
			return empty( $onboarding_state['last_prompted_at'] );
		}

		/**
		 * Maybe show onboarding modal on first run
		 */
		public function maybe_show_onboarding_modal() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			if ( $this->should_show_modal() ) {
				include WCJ_FREE_PLUGIN_PATH . '/includes/admin/views/onboarding-modal.php';
			}
		}

		/**
		 * Getting Started page content
		 */
		public function getting_started_page() {
			$onboarding_state = get_option( $this->option_key, array() );
			$completed_goals  = isset( $onboarding_state['completed_goals'] ) ? $onboarding_state['completed_goals'] : array();
			$snapshots        = isset( $onboarding_state['snapshots'] ) ? $onboarding_state['snapshots'] : array();

			echo '<div class="wrap">';
			echo '<h1>' . esc_html__( 'Getting Started with Booster', 'woocommerce-jetpack' ) . '</h1>';

			echo '<div class="booster-getting-started">';
			echo '<p>' . esc_html__( 'Welcome to Booster! Get started quickly by choosing one of these goals:', 'woocommerce-jetpack' ) . '</p>';

			echo '<button type="button" class="button button-primary" id="launch-onboarding-modal">';
			echo esc_html__( 'Open onboarding', 'woocommerce-jetpack' );
			echo '</button>';

			if ( ! empty( $completed_goals ) ) {
				echo '<h2>' . esc_html__( 'Completed Goals', 'woocommerce-jetpack' ) . '</h2>';
				echo '<div class="completed-goals">';

				foreach ( $completed_goals as $goal_id ) {
					if ( isset( $this->onboarding_map[ $goal_id ] ) ) {
						$goal = $this->onboarding_map[ $goal_id ];
						echo '<div class="goal-item completed">';
						echo '<div class="goal-item-content">';
						echo '<span class="dashicons ' . esc_attr( $goal['icon'] ) . '"></span>';
						echo '<strong>' . esc_html( $goal['title'] ) . '</strong>';
						echo '<p>' . esc_html( $goal['subtitle'] ) . '</p>';
						echo '</div>';

						if ( isset( $snapshots[ $goal_id ] ) ) {
							echo '<div class="goal-item-actions">';
							echo '<span class="applied-chip">' . esc_html__( 'Applied', 'woocommerce-jetpack' ) . '</span>';
							echo '<button type="button" class="button undo-goal" data-goal="' . esc_attr( $goal_id ) . '">';
							echo esc_html__( 'Undo', 'woocommerce-jetpack' );
							echo '</button>';
							echo '</div>';
						}
						echo '</div>';
					}
				}
				echo '</div>';
			}

			echo '</div>';
			echo '</div>';

			include WCJ_FREE_PLUGIN_PATH . '/includes/admin/views/onboarding-modal.php';
			if ( isset( $_GET['modal'] ) && 'onboarding' === $_GET['modal'] ) : ?>
		<script>
		window.addEventListener("load", function() {
			const btn = document.getElementById("launch-onboarding-modal");
			if (btn) {
				console.log("Auto-opening onboarding modal");
				btn.click();

				// Clean URL
				if (window.history.replaceState) {
					const url = new URL(window.location);
					url.searchParams.delete('modal');
					window.history.replaceState({}, document.title, url);
				}
			}
		});
		</script>
				<?php
		endif;
		}

		/**
		 * AJAX handler for applying a goal
		 */
		public function ajax_apply_goal() {
			check_ajax_referer( 'booster_onboarding_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Insufficient permissions.', 'woocommerce-jetpack' ) );
			}

			$goal_id         = sanitize_text_field( wp_unslash( $_POST['goal_id'] ) );
			$create_snapshot = isset( $_POST['create_snapshot'] ) && 'true' === $_POST['create_snapshot'];

			if ( ! isset( $this->onboarding_map[ $goal_id ] ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid goal ID.', 'woocommerce-jetpack' ) ) );
			}

			$result = $this->apply_goal( $goal_id, $create_snapshot );

			if ( $result['success'] ) {
				do_action( 'booster_onboarding_goal_applied', $goal_id );
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result );
			}
		}

		/**
		 * AJAX handler for undoing a goal.
		 *
		 * @return void
		 */
		public function ajax_undo_goal() {
			check_ajax_referer( 'booster_onboarding_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Insufficient permissions.', 'woocommerce-jetpack' ) );
			}

			$goal_id = sanitize_text_field( wp_unslash( $_POST['goal_id'] ) );

			if ( ! isset( $this->onboarding_map[ $goal_id ] ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid goal ID.', 'woocommerce-jetpack' ) ) );
			}

			$result = $this->undo_goal( $goal_id );

			if ( $result['success'] ) {
				do_action( 'booster_onboarding_undo', $goal_id );
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result );
			}
		}

		/**
		 * Apply a goal.
		 *
		 * @param array|string $goal_id The goal ID to apply.
		 * @param bool         $create_snapshot Whether to create a snapshot before applying changes.
		 *
		 * @return array
		 */
		public function apply_goal( $goal_id, $create_snapshot = true ) {
			if ( ! isset( $this->onboarding_map[ $goal_id ] ) ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid goal ID.', 'woocommerce-jetpack' ),
				);
			}

			$goal            = $this->onboarding_map[ $goal_id ];
			$snapshot_before = array();

			if ( $create_snapshot ) {
				$snapshot_before = $this->create_snapshot( $goal );
			}

			$snapshot_after = array();

			foreach ( $goal['modules'] as $module ) {
				$apply_result = $this->apply_module_settings( $module );
				if ( ! $apply_result['success'] ) {
					return $apply_result;
				}
				$snapshot_after = array_merge( $snapshot_after, $apply_result['settings'] );
			}

			$onboarding_state = get_option( $this->option_key, array() );

			if ( ! isset( $onboarding_state['completed_goals'] ) ) {
				$onboarding_state['completed_goals'] = array();
			}

			if ( ! in_array( $goal_id, $onboarding_state['completed_goals'], true ) ) {
				$onboarding_state['completed_goals'][] = $goal_id;
			}

			if ( $create_snapshot ) {
				$onboarding_state['snapshots'][ $goal_id ] = array(
					'before'     => $snapshot_before,
					'after'      => $snapshot_after,
					'created_at' => current_time( 'c' ),
				);
			}

			$onboarding_state['last_prompted_at'] = current_time( 'c' );

			update_option( $this->option_key, $onboarding_state );

			$next_step_link = '';
			if ( isset( $goal['next_step_link'] ) ) {
				$next_step_link = admin_url( $goal['next_step_link'] . wp_create_nonce( 'wcj-cat-nonce' ) );
			}

			return array(
				'success'        => true,
				'message'        => __( 'Goal applied successfully!', 'woocommerce-jetpack' ),
				'next_step_text' => isset( $goal['next_step_text'] ) ? $goal['next_step_text'] : '',
				'next_step_link' => $next_step_link,
			);
		}

		/**
		 * Create a snapshot of current settings before applying changes.
		 *
		 * @param string $goal The goal ID for which to create the snapshot.
		 *
		 * @return array The snapshot of current settings.
		 */
		private function create_snapshot( $goal ) {
			$snapshot = array();

			foreach ( $goal['modules'] as $module ) {
				foreach ( $module['settings'] as $option_key => $value ) {
					if ( 'create_demo_draft' === $option_key || 'add_one_extra' === $option_key ) {
						continue;
					}
					$snapshot[ $option_key ] = get_option( $option_key, null );
				}
			}

			return $snapshot;
		}

		/**
		 * Apply module settings
		 *
		 * @param array $module The module configuration.
		 *
		 * @return array Result of applying the module settings.
		 */
		private function apply_module_settings( $module ) {
			$module_id        = $module['id'];
			$settings         = $module['settings'];
			$applied_settings = array();

			switch ( $module_id ) {
				case 'sales_notifications':
					return $this->apply_sales_notifications( $settings );

				case 'one_page_checkout':
					return $this->apply_one_page_checkout( $settings );

				case 'reviews':
					return $this->apply_reviews( $settings );

				case 'order_numbers':
					return $this->apply_order_numbers( $settings );

				case 'currency':
					return $this->apply_currency( $settings );

				case 'frequently_bought_together':
					return $this->apply_frequently_bought_together( $settings );

				case 'admin_orders_list':
					return $this->apply_admin_orders_list( $settings );

				default:
					foreach ( $settings as $option_key => $value ) {
						if ( in_array( $option_key, array( 'create_demo_draft', 'add_one_extra' ), true ) ) {
							continue;
						}
						update_option( $option_key, $value );
						$applied_settings[ $option_key ] = $value;
					}
					break;
			}

			return array(
				'success'  => true,
				'settings' => $applied_settings,
			);
		}

		/**
		 * Apply sales notifications settings
		 *
		 * @param array $settings The settings to apply.
		 *
		 * @return array Result of applying the settings.
		 */
		private function apply_sales_notifications( $settings ) {
			$applied_settings = array();

			foreach ( $settings as $option_key => $value ) {
				if ( 'wcj_sale_msg_styling' === $option_key ) {
					update_option( $option_key, $value );
				} else {
					update_option( $option_key, $value );
				}
				$applied_settings[ $option_key ] = $value;
			}

			return array(
				'success'  => true,
				'settings' => $applied_settings,
			);
		}

		/**
		 * Apply one page checkout settings
		 *
		 * @param array $settings The settings to apply.
		 *
		 * @return array Result of applying the settings.
		 */
		private function apply_one_page_checkout( $settings ) {
			$applied_settings = array();

			foreach ( $settings as $option_key => $value ) {
				if ( 'create_demo_draft' === $option_key && $value ) {
					$this->create_one_page_checkout_draft();
				} else {
					update_option( $option_key, $value );
					$applied_settings[ $option_key ] = $value;
				}
			}

			return array(
				'success'  => true,
				'settings' => $applied_settings,
			);
		}

		/**
		 * Create draft page for one page checkout
		 */
		private function create_one_page_checkout_draft() {
			$existing_page = get_page_by_title( 'Quick Checkout (Draft)', OBJECT, 'page' );
			if ( $existing_page ) {
				return;
			}

			$page_data = array(
				'post_title'   => 'Quick Checkout (Draft)',
				'post_content' => '[wcj_one_page_checkout]',
				'post_status'  => 'draft',
				'post_type'    => 'page',
				'post_author'  => get_current_user_id(),
			);

			wp_insert_post( $page_data );
		}

		/**
		 * Apply reviews settings
		 *
		 * @param array $settings The settings to apply.
		 *
		 * @return array Result of applying the settings.
		 */
		private function apply_reviews( $settings ) {
			$applied_settings = array();

			foreach ( $settings as $option_key => $value ) {
				update_option( $option_key, $value );
				$applied_settings[ $option_key ] = $value;
			}

			return array(
				'success'  => true,
				'settings' => $applied_settings,
			);
		}

		/**
		 * Apply order numbers settings
		 *
		 * @param array $settings The settings to apply.
		 *
		 * @return array Result of applying the settings.
		 */
		private function apply_order_numbers( $settings ) {
			$applied_settings = array();

			foreach ( $settings as $option_key => $value ) {
				update_option( $option_key, $value );
				$applied_settings[ $option_key ] = $value;
			}

			return array(
				'success'  => true,
				'settings' => $applied_settings,
			);
		}

		/**
		 * Apply currency settings
		 *
		 * @param array $settings The settings to apply.
		 *
		 * @return array Result of applying the settings.
		 */
		private function apply_currency( $settings ) {
			$applied_settings = array();

			foreach ( $settings as $option_key => $value ) {
				if ( 'add_one_extra' === $option_key && $value ) {
					$this->add_extra_currency();
				} else {
					update_option( $option_key, $value );
					$applied_settings[ $option_key ] = $value;
				}
			}

			return array(
				'success'  => true,
				'settings' => $applied_settings,
			);
		}

		/**
		 * Add one extra currency (EUR as default)
		 */
		private function add_extra_currency() {
			$current_currency = get_woocommerce_currency();
			if ( 'EUR' !== $current_currency ) {
				update_option( 'wcj_currency_EUR', '€' );
			} elseif ( 'USD' !== $current_currency ) {
				update_option( 'wcj_currency_USD', '$' );
			}
		}

		/**
		 * Apply frequently bought together settings
		 *
		 * @param array $settings The settings to apply.
		 *
		 * @return array Result of applying the settings.
		 */
		private function apply_frequently_bought_together( $settings ) {
			$applied_settings = array();

			foreach ( $settings as $option_key => $value ) {
				update_option( $option_key, $value );
				$applied_settings[ $option_key ] = $value;
			}

			return array(
				'success'  => true,
				'settings' => $applied_settings,
			);
		}

		/**
		 * Apply admin orders list settings
		 *
		 * @param array $settings The settings to apply.
		 *
		 * @return array Result of applying the settings.
		 */
		private function apply_admin_orders_list( $settings ) {
			$applied_settings = array();

			foreach ( $settings as $option_key => $value ) {
				update_option( $option_key, $value );
				$applied_settings[ $option_key ] = $value;
			}

			return array(
				'success'  => true,
				'settings' => $applied_settings,
			);
		}

		/**
		 * Undo a goal
		 *
		 * @param string $goal_id The goal ID to undo.
		 *
		 * @return array Result of undoing the goal.
		 */
		public function undo_goal( $goal_id ) {
			$onboarding_state = get_option( $this->option_key, array() );

			if ( ! isset( $onboarding_state['snapshots'][ $goal_id ] ) ) {
				return array(
					'success' => false,
					'message' => __( 'No snapshot found for this goal.', 'woocommerce-jetpack' ),
				);
			}

			$snapshot        = $onboarding_state['snapshots'][ $goal_id ];
			$before_settings = $snapshot['before'];

			foreach ( $before_settings as $option_key => $value ) {
				if ( null === $value ) {
					delete_option( $option_key );
				} else {
					update_option( $option_key, $value );
				}
			}

			if ( 'faster_checkout' === $goal_id ) {
				$this->remove_one_page_checkout_draft();
			}

			$completed_goals                     = isset( $onboarding_state['completed_goals'] ) ? $onboarding_state['completed_goals'] : array();
			$completed_goals                     = array_diff( $completed_goals, array( $goal_id ) );
			$onboarding_state['completed_goals'] = $completed_goals;

			unset( $onboarding_state['snapshots'][ $goal_id ] );

			update_option( $this->option_key, $onboarding_state );

			return array(
				'success' => true,
				'message' => __( 'Goal undone successfully!', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Remove one page checkout draft page
		 */
		private function remove_one_page_checkout_draft() {
			$page = get_page_by_title( 'Quick Checkout (Draft)', OBJECT, 'page' );
			if ( $page && 'draft' === $page->post_status ) {
				wp_delete_post( $page->ID, true );
			}
		}

		/**
		 * Check if first win condition is met
		 *
		 * @param string $goal_id The goal ID to check.
		 *
		 * @return bool True if the first win condition is met, false otherwise.
		 */
		public function check_first_win( $goal_id ) {
			if ( ! isset( $this->onboarding_map[ $goal_id ] ) ) {
				return false;
			}

			$goal  = $this->onboarding_map[ $goal_id ];
			$check = $goal['first_win_check'];

			switch ( $check ) {
				case 'sales_notifications_enabled':
					return 'yes' === get_option( 'wcj_sales_notifications_enabled', 'no' );

				case 'one_page_checkout_draft_page_exists':
					$page = get_page_by_title( 'Quick Checkout (Draft)', OBJECT, 'page' );
					return $page && 'draft' === $page->post_status;

				case 'reviews_enabled':
					return 'yes' === get_option( 'wcj_reviews_enabled', 'no' );

				case 'order_numbers_enabled':
					return 'yes' === get_option( 'wcj_order_numbers_enabled', 'no' );

				case 'extra_currency_added':
					$current_currency = get_woocommerce_currency();
					return ( 'EUR' !== $current_currency && get_option( 'wcj_currency_EUR' ) ) ||
							( 'USD' !== $current_currency && get_option( 'wcj_currency_USD' ) );

				case 'wcj_invoicing_invoice_enabled':
					return 'yes' === get_option( 'wcj_invoicing_invoice_enabled', 'no' );

				case 'wcj_product_addons_enabled':
					return 'yes' === get_option( 'wcj_product_addons_enabled', 'no' );

				case 'wcj_checkout_core_fields_enabled':
					return 'yes' === get_option( 'wcj_checkout_core_fields_enabled', 'no' );

				default:
					return false;
			}
		}

		/**
		 * AJAX handler: Apply blueprint
		 */
		public function ajax_apply_blueprint() {
			check_ajax_referer( 'booster_onboarding_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woocommerce-jetpack' ) ) );
			}

			$blueprint_id = isset( $_POST['blueprint_id'] ) ? sanitize_key( $_POST['blueprint_id'] ) : '';
			$create_snapshot = isset( $_POST['create_snapshot'] ) && 'true' === $_POST['create_snapshot'];

			if ( ! file_exists( WCJ_FREE_PLUGIN_PATH . '/includes/admin/onboarding-blueprints.php' ) ) {
				wp_send_json_error( array( 'message' => __( 'Blueprints not available.', 'woocommerce-jetpack' ) ) );
			}

			$blueprints = include WCJ_FREE_PLUGIN_PATH . '/includes/admin/onboarding-blueprints.php';

			if ( ! isset( $blueprints[ $blueprint_id ] ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid blueprint.', 'woocommerce-jetpack' ) ) );
			}

			$blueprint = $blueprints[ $blueprint_id ];

			if ( $create_snapshot ) {
				$this->create_blueprint_snapshot( $blueprint_id, $blueprint );
			}

			foreach ( $blueprint['goal_keys'] as $goal_key ) {
				if ( isset( $this->onboarding_map[ $goal_key ] ) ) {
					$this->apply_goal_silently( $goal_key, $this->onboarding_map[ $goal_key ] );
				}
			}

			set_transient( "wcj_onboarding_blueprint_{$blueprint_id}_applied", true, YEAR_IN_SECONDS );

			$this->log_onboarding_event( 'blueprint_apply', array(
				'blueprint_id' => $blueprint_id,
				'goal_keys'    => $blueprint['goal_keys'],
			) );

			wp_send_json_success( array(
				'message'        => isset( $blueprint['success_message'] ) ? $blueprint['success_message'] : sprintf( __( '%s blueprint applied successfully!', 'woocommerce-jetpack' ), $blueprint['title'] ),
				'next_steps'     => isset( $blueprint['next_steps'] ) ? $blueprint['next_steps'] : array(),
				'primary_cta'    => isset( $blueprint['primary_cta'] ) ? $blueprint['primary_cta'] : null,
				'pro_note'       => isset( $blueprint['pro_note'] ) ? $blueprint['pro_note'] : null,
			) );
		}

		/**
		 * Apply a goal silently (no AJAX response)
		 *
		 * @param string $goal_id Goal ID
		 * @param array  $goal    Goal config
		 */
		private function apply_goal_silently( $goal_id, $goal ) {
			foreach ( $goal['modules'] as $module_config ) {
				$module_id = $module_config['id'];

				foreach ( $module_config['settings'] as $setting_key => $setting_value ) {
					if ( in_array( $setting_key, array( 'create_demo_draft', 'add_one_extra' ), true ) ) {
						continue;
					}
					update_option( $setting_key, $setting_value );
				}
			}

			$onboarding_state = get_option( $this->option_key, array() );
			if ( ! isset( $onboarding_state['completed_goals'] ) ) {
				$onboarding_state['completed_goals'] = array();
			}
			if ( ! in_array( $goal_id, $onboarding_state['completed_goals'], true ) ) {
				$onboarding_state['completed_goals'][] = $goal_id;
			}
			update_option( $this->option_key, $onboarding_state );
		}

		/**
		 * Create a snapshot for blueprint
		 *
		 * @param string $blueprint_id Blueprint ID
		 * @param array  $blueprint    Blueprint config
		 */
		private function create_blueprint_snapshot( $blueprint_id, $blueprint ) {
			$snapshot = array();

			foreach ( $blueprint['goal_keys'] as $goal_key ) {
				if ( isset( $this->onboarding_map[ $goal_key ] ) ) {
					$goal = $this->onboarding_map[ $goal_key ];
					foreach ( $goal['modules'] as $module ) {
						foreach ( $module['settings'] as $option_key => $value ) {
							if ( 'create_demo_draft' === $option_key || 'add_one_extra' === $option_key ) {
								continue;
							}
							$snapshot[ $option_key ] = get_option( $option_key, null );
						}
					}
				}
			}

			$onboarding_state = get_option( $this->option_key, array() );
			$onboarding_state['blueprint_snapshots'][ $blueprint_id ] = array(
				'before'     => $snapshot,
				'created_at' => current_time( 'c' ),
			);
			update_option( $this->option_key, $onboarding_state );
		}

		/**
		 * AJAX handler: Undo blueprint
		 */
		public function ajax_undo_blueprint() {
			check_ajax_referer( 'booster_onboarding_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woocommerce-jetpack' ) ) );
			}

			$blueprint_id = isset( $_POST['blueprint_id'] ) ? sanitize_key( $_POST['blueprint_id'] ) : '';

			$onboarding_state = get_option( $this->option_key, array() );

			if ( ! isset( $onboarding_state['blueprint_snapshots'][ $blueprint_id ] ) ) {
				wp_send_json_error( array(
					'message' => __( 'No snapshot found for this blueprint.', 'woocommerce-jetpack' ),
				) );
			}

			$snapshot        = $onboarding_state['blueprint_snapshots'][ $blueprint_id ];
			$before_settings = $snapshot['before'];

			foreach ( $before_settings as $option_key => $value ) {
				if ( null === $value ) {
					delete_option( $option_key );
				} else {
					update_option( $option_key, $value );
				}
			}

			delete_transient( "wcj_onboarding_blueprint_{$blueprint_id}_applied" );
			unset( $onboarding_state['blueprint_snapshots'][ $blueprint_id ] );
			update_option( $this->option_key, $onboarding_state );

			$this->log_onboarding_event( 'blueprint_undo', array(
				'blueprint_id' => $blueprint_id,
			) );

			wp_send_json_success( array(
				'message' => __( 'Blueprint reverted successfully.', 'woocommerce-jetpack' ),
			) );
		}

		/**
		 * Log onboarding event
		 *
		 * @param string $event_type Event type
		 * @param array  $event_data Event data
		 */
		private function log_onboarding_event( $event_type, $event_data ) {
			$events = get_option( 'wcj_onboarding_analytics', array() );

			$event = array(
				'type'      => $event_type,
				'data'      => $event_data,
				'timestamp' => current_time( 'mysql' ),
			);

			$events[] = $event;

			if ( count( $events ) > 500 ) {
				$events = array_slice( $events, -500 );
			}

			update_option( 'wcj_onboarding_analytics', $events );
		}

		/**
		 * AJAX handler: Log onboarding event
		 */
		public function ajax_log_onboarding_event() {
			check_ajax_referer( 'booster_onboarding_nonce', 'nonce' );

			$event_type = isset( $_POST['event_type'] ) ? sanitize_text_field( wp_unslash( $_POST['event_type'] ) ) : '';
			$event_data = isset( $_POST['event_data'] ) ? $_POST['event_data'] : array();

			$this->log_onboarding_event( $event_type, $event_data );

			wp_send_json_success();
		}
	}

endif;

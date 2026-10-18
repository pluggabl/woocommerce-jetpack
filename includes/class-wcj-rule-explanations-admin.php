<?php
/**
 * Booster for WooCommerce - read-only merchant rule explanations.
 *
 * @version 9.0.0
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

if ( ! class_exists( 'WCJ_Rule_Explanations_Admin' ) ) :
	/** A connected merchant workflow without shopper/session persistence. */
	class WCJ_Rule_Explanations_Admin {
		/** Registers the scoped merchant menu hook. */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_page' ), 98 );
		}

		/** Registers a capability-protected WooCommerce submenu. */
		public function register_page() {
			add_submenu_page( 'woocommerce', __( 'Rule explanations', 'woocommerce-jetpack' ), __( 'Rule explanations', 'woocommerce-jetpack' ), 'manage_woocommerce', 'wcj-rule-explanations', array( $this, 'render_page' ) );
		}

		/**
		 * Collect two local declarations after current private-admin admission.
		 * Core registry initialization may run normal registration hooks, never Ability callbacks.
		 *
		 * @return array|null Fixed observations, or null outside the admitted context.
		 */
		private function readiness_facts() {
			if ( ! function_exists( 'is_admin' ) || ! function_exists( 'is_user_logged_in' ) || ! function_exists( 'wcj_is_rest' ) || ! is_admin() || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
				return null;
			}
			try {
				if ( wcj_is_rest() ) {
					return null;
				}
			} catch ( Throwable $error ) {
				return null;
			}
			$facts = array(
				'api'                   => function_exists( 'wp_get_ability' ) ? 'unknown' : 'missing',
				'abilities'             => array(),
				'https'                 => null,
				'application_passwords' => null,
			);
			try {
				$initialized = function_exists( 'did_action' ) ? did_action( 'init' ) : 0;
				if ( function_exists( 'wp_has_ability' ) && function_exists( 'wp_get_ability' ) && class_exists( 'WP_Ability', false ) && is_int( $initialized ) && $initialized > 0 ) {
					$facts['api'] = 'available';
				}
			} catch ( Throwable $error ) {
				$facts['api'] = 'unknown';
			}
			foreach ( array( 'booster/explain-gateway-fee', 'booster/returns-summary' ) as $name ) {
				$row = array(
					'registration'    => 'unknown',
					'name'            => null,
					'show_in_rest'    => null,
					'readonly'        => null,
					'non_destructive' => null,
				);
				try {
					if ( 'available' === $facts['api'] ) {
						$registered = wp_has_ability( $name );
						if ( false === $registered ) {
							$row['registration'] = 'missing';
						} elseif ( true === $registered ) {
							$ability = wp_get_ability( $name );
							if ( $ability instanceof WP_Ability && method_exists( $ability, 'get_name' ) && method_exists( $ability, 'get_meta' ) && $name === $ability->get_name() ) {
								$meta = $ability->get_meta();
								if ( is_array( $meta ) ) {
									$row['registration']    = 'available';
									$row['name']            = $name;
									$row['show_in_rest']    = isset( $meta['show_in_rest'] ) && is_bool( $meta['show_in_rest'] ) ? $meta['show_in_rest'] : null;
									$annotations            = isset( $meta['annotations'] ) && is_array( $meta['annotations'] ) ? $meta['annotations'] : array();
									$row['readonly']        = isset( $annotations['readonly'] ) && is_bool( $annotations['readonly'] ) ? $annotations['readonly'] : null;
									$row['non_destructive'] = isset( $annotations['destructive'] ) && is_bool( $annotations['destructive'] ) ? ! $annotations['destructive'] : null;
								}
							}
						}
					}
				} catch ( Throwable $error ) {
					// Preserve fixed unknown facts; no exception or untrusted metadata is rendered.
					$row['registration'] = 'unknown';
				}
				$facts['abilities'][ $name ] = $row;
			}
			try {
				$ssl            = function_exists( 'is_ssl' ) ? is_ssl() : null;
				$facts['https'] = is_bool( $ssl ) ? $ssl : null;
			} catch ( Throwable $error ) {
				// Unknown local request state, not remote reachability.
				$facts['https'] = null;
			}
			try {
				if ( function_exists( 'wp_is_application_passwords_available_for_user' ) && function_exists( 'wp_get_current_user' ) ) {
					$available                      = wp_is_application_passwords_available_for_user( wp_get_current_user() );
					$facts['application_passwords'] = is_bool( $available ) ? $available : null;
				}
			} catch ( Throwable $error ) {
				// Availability is only a declaration; no credential is inspected.
				$facts['application_passwords'] = null;
			}
			return $facts;
		}

		/**
		 * Render a strictly typed declaration with fixed escaped labels.
		 *
		 * @param bool|null $value Observed boolean, never a truthy metadata string.
		 * @return string Translated label.
		 */
		private function readiness_boolean( $value ) {
			return true === $value ? __( 'True', 'woocommerce-jetpack' ) : ( false === $value ? __( 'False', 'woocommerce-jetpack' ) : __( 'Unknown', 'woocommerce-jetpack' ) );
		}

		/** Render useful local prerequisites and revocation guidance, not a connector grant. */
		private function render_readiness() {
			$facts = $this->readiness_facts();
			if ( null === $facts ) {
				return;
			}
			$states = array(
				'available' => __( 'Available', 'woocommerce-jetpack' ),
				'missing'   => __( 'Missing', 'woocommerce-jetpack' ),
				'unknown'   => __( 'Unknown', 'woocommerce-jetpack' ),
			);
			echo '<section class="wcj-ability-readiness"><h2>' . esc_html__( 'Local Ability connection prerequisites', 'woocommerce-jetpack' ) . '</h2>';
			echo '<p>' . esc_html__( 'These are local declarations only, not execution safety or permission certification. A qualified connector/version and real authentication remain untested. This page does not connect a client or execute an Ability.', 'woocommerce-jetpack' ) . '</p>';
			echo '<p>' . esc_html__( 'Core Ability API:', 'woocommerce-jetpack' ) . ' <strong>' . esc_html( $states[ $facts['api'] ] ) . '</strong></p>';
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Fixed Ability', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Registration', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Registered object name', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Declared show_in_rest', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Declared readonly', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Declared non-destructive', 'woocommerce-jetpack' ) . '</th></tr></thead><tbody>';
			foreach ( $facts['abilities'] as $name => $row ) {
				echo '<tr><td><code>' . esc_html( $name ) . '</code></td><td>' . esc_html( $states[ $row['registration'] ] ) . '</td><td>' . esc_html( null === $row['name'] ? __( 'Unknown', 'woocommerce-jetpack' ) : $row['name'] ) . '</td><td>' . esc_html( $this->readiness_boolean( $row['show_in_rest'] ) ) . '</td><td>' . esc_html( $this->readiness_boolean( $row['readonly'] ) ) . '</td><td>' . esc_html( $this->readiness_boolean( $row['non_destructive'] ) ) . '</td></tr>';
			}
			echo '</tbody></table><p>' . esc_html__( 'HTTPS detected for this admin request:', 'woocommerce-jetpack' ) . ' <strong>' . esc_html( $this->readiness_boolean( $facts['https'] ) ) . '</strong> ' . esc_html__( 'This does not prove remote HTTPS reachability.', 'woocommerce-jetpack' ) . '</p>';
			echo '<p>' . esc_html__( 'Application Password availability for the current authorized user:', 'woocommerce-jetpack' ) . ' <strong>' . esc_html( $this->readiness_boolean( $facts['application_passwords'] ) ) . '</strong> ' . esc_html__( 'Availability is not an existing credential, an authentication test or a connector grant. Application Passwords are not Ability-scoped or intrinsically read-only.', 'woocommerce-jetpack' ) . '</p>';
			echo '<p>' . esc_html__( 'Use the approved existing connection procedure and the minimum necessary authorized account. Do not paste credentials into AI prompts.', 'woocommerce-jetpack' ) . '</p>';
			echo '<p>' . esc_html__( 'To revoke access, use WordPress\'s existing credential controls as well as disconnecting the external client. This page makes no account changes.', 'woocommerce-jetpack' ) . '</p>';
			echo '<p class="description">' . esc_html__( 'Registry inspection may initialize normal WordPress registration hooks. Local hooks and filters remain platform trust, not verified transport. Only the two named Abilities are inspected; no whole-registry or credential inventory is read.', 'woocommerce-jetpack' ) . '</p></section>';
			// Static help within the same private readiness admission; no Ability is executed.
			echo '<section class="wcj-ability-examples"><h2>' . esc_html__( 'Example questions for an authorized client', 'woocommerce-jetpack' ) . '</h2>';
			echo '<p>' . esc_html__( 'These are static usage examples, not executed requests or results. A compatible client must map the question to the structured Ability inputs. Natural-language support and a working connection are not established here. Actual use remains subject to current permissions, module and role eligibility.', 'woocommerce-jetpack' ) . '</p>';
			echo '<h3>' . esc_html__( 'Explain a configured gateway fee', 'woocommerce-jetpack' ) . '</h3><p><code>' . esc_html( 'booster/explain-gateway-fee' ) . '</code></p>';
			echo '<blockquote><p>' . esc_html__( 'For the configured gateway identifier I supply, explain the fee or discount with merchandise total 100 after discounts excluding tax, shipping total 5 excluding tax, merchandise subtotal tax 10 before discounts, and shipping tax 0.5.', 'woocommerce-jetpack' ) . '</p></blockquote>';
			echo '<p>' . esc_html__( 'Use booster/explain-gateway-fee with mode=scenario, your configured gateway identifier in gateway, subtotal=100, shipping=5, tax=10 and shipping_tax=0.5. These illustrative amounts are merchant-supplied store-currency facts, not a shopper or order lookup. Supply every amount explicitly, including zero when applicable.', 'woocommerce-jetpack' ) . '</p>';
			echo '<p class="description">' . esc_html__( 'The basic example assumes guest with no supplied product IDs. Free and Plus do not accept role or product_ids inputs; product-dependent rules are unsupported in basic scenarios. For the same known guest/empty example on Elite, also supply role=guest and product_ids=[]; other role/product conditions require explicit applicable facts. Dynamic shortcode titles and active multicurrency are unsupported. The evaluator amount is before WooCommerce cart tax/rounding, not the final payable total or proof that a gateway is available.', 'woocommerce-jetpack' ) . '</p>';
			echo '<h3>' . esc_html__( 'Summarize the bounded Returns sample', 'woocommerce-jetpack' ) . '</h3><p><code>' . esc_html( 'booster/returns-summary' ) . '</code></p>';
			echo '<blockquote><p>' . esc_html__( 'Show status counts for the latest up to 50 return request IDs and how many sampled requests currently need review or have unavailable facts. Tell me whether the sample may be truncated.', 'woocommerce-jetpack' ) . '</p></blockquote>';
			echo '<p>' . esc_html__( 'Use booster/returns-summary with exactly scope=summary and no additional inputs. Request status counts and the current needs_review and facts_unavailable counts, not request IDs, customers, reasons or amounts.', 'woocommerce-jetpack' ) . '</p>';
			echo '<p class="description">' . esc_html__( 'The sample follows latest request IDs, not recent update time or whole-store totals. At 50 rows, possibly_truncated=true means there may be more rows, not that more rows definitely exist. Current flags are sequential reads, not an atomic snapshot. Unavailable counts are null, not zero. The summary never changes returns, orders, stock, refunds or notifications.', 'woocommerce-jetpack' ) . '</p></section>';
		}

		/** Current authorization precedes reads, nonce verification and rendering. */
		public function render_page() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You do not have permission to explain Booster rules.', 'woocommerce-jetpack' ) );
			}
			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && array_key_exists( 'wcj_shipping_action', $_POST ) ) {
				$this->render_shipping_admin();
				return; }
			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['wcj_payment_action'] ) ) {
				$this->render_payment_admin();
				return; }
			$service = new WCJ_Rule_Explanations();
			$choices = $service->get_scenario_choices();
			$result  = null;
			$input   = array(
				'mode'         => 'scenario',
				'gateway'      => '',
				'subtotal'     => 0,
				'shipping'     => 0,
				'tax'          => 0,
				'shipping_tax' => 0,
			);
			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
				$nonce = isset( $_POST['_wpnonce'] ) && is_string( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
				if ( ! wp_verify_nonce( $nonce, 'wcj_explain_gateway_fee' ) ) {
					$result = new WP_Error( 'booster_expired_rule_form', __( 'This form has expired. Reload this page and try again.', 'woocommerce-jetpack' ) );
				} else {
					$input  = $this->read_form( $service->is_full_experience(), $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict scalar/regex validation in read_form(), then service bounds.
					$result = is_wp_error( $input ) ? $input : $service->explain( $input );
				}
			}
			echo '<div class="wrap"><h1>' . esc_html__( 'Rule explanations', 'woocommerce-jetpack' ) . '</h1>';
			$this->render_readiness();
			$this->render_payment_admin();
			$this->render_shipping_admin();
			echo '<p>' . esc_html__( 'Evaluate your configured gateway fee or discount using one merchant-supplied scenario. This is not an actual shopper, order or gateway-availability diagnosis. No cart, session, order or setting is changed.', 'woocommerce-jetpack' ) . '</p>';
			echo '<p>' . esc_html__( 'Enter merchandise total after discounts, excluding tax, and shipping total excluding tax. Enter merchandise subtotal tax BEFORE discounts and shipping tax separately. The existing fee rule chooses which amounts to include. The result is before any tax on the fee, not a final payable total.', 'woocommerce-jetpack' ) . '</p>';
			if ( null !== $result ) {
				$this->render_result( $result ); }
			if ( is_wp_error( $choices ) || empty( $choices['gateways'] ) ) {
				echo '<p>' . esc_html__( 'No configured gateway identifiers are available. Configure Gateways Fees and Discounts first.', 'woocommerce-jetpack' ) . '</p></div>';
				return;
			}
			if ( is_wp_error( $input ) ) {
				$input = array(); }
			echo '<form method="post" action="' . esc_url( admin_url( 'admin.php?page=wcj-rule-explanations' ) ) . '">';
			wp_nonce_field( 'wcj_explain_gateway_fee' );
			echo '<table class="form-table"><tr><th><label for="wcj-rule-gateway">' . esc_html__( 'Configured gateway', 'woocommerce-jetpack' ) . '</label></th><td><select id="wcj-rule-gateway" name="gateway">';
			foreach ( $choices['gateways'] as $gateway ) {
				echo '<option value="' . esc_attr( $gateway ) . '" ' . selected( isset( $input['gateway'] ) ? $input['gateway'] : '', $gateway, false ) . '>' . esc_html( $gateway ) . '</option>';
			}
			echo '</select></td></tr>';
			$labels = array(
				'subtotal'     => __( 'Merchandise total after discounts, excluding tax', 'woocommerce-jetpack' ),
				'shipping'     => __( 'Shipping total excluding tax', 'woocommerce-jetpack' ),
				'tax'          => __( 'Merchandise subtotal tax before discounts', 'woocommerce-jetpack' ),
				'shipping_tax' => __( 'Shipping tax', 'woocommerce-jetpack' ),
			);
			foreach ( $labels as $key => $label ) {
				echo '<tr><th><label for="wcj-rule-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><input required type="number" min="0" max="1000000000" step="any" id="wcj-rule-' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( isset( $input[ $key ] ) ? $input[ $key ] : 0 ) . '"></td></tr>';
			}
			if ( $service->is_full_experience() ) {
				echo '<tr><th><label for="wcj-rule-products">' . esc_html__( 'Product and variation IDs', 'woocommerce-jetpack' ) . '</label></th><td><input id="wcj-rule-products" name="product_ids" maxlength="1100" value="' . esc_attr( isset( $input['product_ids'] ) ? implode( ',', $input['product_ids'] ) : '' ) . '"><p class="description">' . esc_html__( 'Comma-separated IDs, at most 100. Include both parent and variation IDs when relevant. Blank means no product IDs in this scenario, not unknown shopper contents.', 'woocommerce-jetpack' ) . '</p></td></tr>';
				echo '<tr><th><label for="wcj-rule-role">' . esc_html__( 'Scenario role', 'woocommerce-jetpack' ) . '</label></th><td><select id="wcj-rule-role" name="role">';
				foreach ( $choices['roles'] as $role ) {
					echo '<option value="' . esc_attr( $role ) . '" ' . selected( isset( $input['role'] ) ? $input['role'] : 'guest', $role, false ) . '>' . esc_html( $role ) . '</option>';
				}
				echo '</select><p class="description">' . esc_html__( 'This does not impersonate a user. Live fees use the shopper\'s first role, or guest when none is present.', 'woocommerce-jetpack' ) . '</p></td></tr>';
			} else {
				echo '<tr><td colspan="2">' . esc_html__( 'Basic scenarios use guest with no supplied product IDs. Elite adds role/product scenarios and condition detail. Existing checkout fee rights are unchanged.', 'woocommerce-jetpack' ) . '</td></tr>';
			}
			echo '</table><p><button class="button button-primary" type="submit">' . esc_html__( 'Explain this scenario', 'woocommerce-jetpack' ) . '</button></p></form>';
			echo '<p class="description">' . esc_html__( 'The form lists at most 50 configured gateway IDs and 100 roles. Dynamic shortcode titles and active multicurrency cannot be safely evaluated here. Results are not saved. Other plugins and gateway availability are outside this rule evaluation.', 'woocommerce-jetpack' ) . '</p>';
			echo '<h2>' . esc_html__( 'Live decision in this request', 'woocommerce-jetpack' ) . '</h2><p>' . esc_html__( 'This is only the last fee callback snapshot, not the current cart. An admin or Ability request normally has no shopper fee evaluation. A previous request is never retrieved.', 'woocommerce-jetpack' ) . '</p>';
			$this->render_result( $service->explain( array( 'mode' => 'request_local' ) ) );
			echo '</div>';
		}

		/** Strict scalar conversion after the caller verified the nonce.
		 *
		 * @param bool  $full Whether Elite inputs are available.
		 * @param array $form Submitted scalar fields.
		 * @return array|WP_Error
		 */
		private function read_form( $full, $form ) {
			$error = new WP_Error( 'booster_invalid_rule_form', __( 'Enter valid numeric amounts and bounded identifiers.', 'woocommerce-jetpack' ) );
			$input = array( 'mode' => 'scenario' );
			foreach ( array( 'gateway', 'subtotal', 'shipping', 'tax', 'shipping_tax' ) as $key ) {
				if ( ! isset( $form[ $key ] ) || ! is_string( $form[ $key ] ) ) {
					return $error; }
				$value = wp_unslash( $form[ $key ] );
				if ( 'gateway' === $key ) {
					$input[ $key ] = $value;
					continue; }
				if ( strlen( $value ) > 25 || ! preg_match( '/^\d+(?:\.\d+)?$/D', $value ) ) {
					return $error; }
				$input[ $key ] = (float) $value;
			}
			if ( $full ) {
				if ( isset( $form['role'] ) && ! is_string( $form['role'] ) ) {
					return $error; }
				$input['role'] = isset( $form['role'] ) ? wp_unslash( $form['role'] ) : 'guest';
				if ( isset( $form['product_ids'] ) && ! is_string( $form['product_ids'] ) ) {
					return $error; }
				$ids = isset( $form['product_ids'] ) ? trim( wp_unslash( $form['product_ids'] ) ) : '';
				if ( strlen( $ids ) > 1100 || ( '' !== $ids && ! preg_match( '/^\d+(?:\s*,\s*\d+)*$/D', $ids ) ) ) {
					return $error; }
				$input['product_ids'] = '' === $ids ? array() : array_map( 'intval', explode( ',', $ids ) );
			} elseif ( isset( $form['role'] ) || isset( $form['product_ids'] ) ) {
				return $error; }
			return $input;
		}

		/** Renders fixed labels and escaped reason codes.
		 *
		 * @param array|WP_Error $result Safe service response.
		 * @return void
		 */
		private function render_result( $result ) {
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error inline"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
				return;
			}
			$messages = array(
				'eligible'                  => __( 'The fee rule matched. This tool did not apply a fee.', 'woocommerce-jetpack' ),
				'applied'                   => __( 'The last live callback added the fee.', 'woocommerce-jetpack' ),
				'duplicate_suppressed'      => __( 'The live callback did not add another fee because its fee ID was already present.', 'woocommerce-jetpack' ),
				'outside_cart_amount'       => __( 'The supplied amount did not meet the configured minimum/maximum.', 'woocommerce-jetpack' ),
				'product_condition_not_met' => __( 'The supplied product IDs did not meet the configured product conditions.', 'woocommerce-jetpack' ),
				'not_configured'            => __( 'The evaluated gateway had no fee title, so its fee was disabled.', 'woocommerce-jetpack' ),
				'zero_or_invalid_amount'    => __( 'The rule produced no nonzero fee amount.', 'woocommerce-jetpack' ),
				'dynamic_title'             => __( 'A dynamic or malformed fee title cannot be safely evaluated in a merchant scenario.', 'woocommerce-jetpack' ),
				'multicurrency_context'     => __( 'Active multicurrency requires live currency context that this scenario does not reproduce.', 'woocommerce-jetpack' ),
				'advanced_product_context'  => __( 'This rule needs product context. Elite scenarios support explicit product IDs; existing live fee rights are unchanged.', 'woocommerce-jetpack' ),
				'evaluation_failed'         => __( 'This scenario could not be evaluated safely. No error details or settings are exposed.', 'woocommerce-jetpack' ),
				'role_fact_required'        => __( 'Supply the scenario role explicitly for this role-dependent rule.', 'woocommerce-jetpack' ),
				'product_fact_required'     => __( 'Supply product IDs explicitly for this product-dependent rule. An explicit empty list is different from missing facts.', 'woocommerce-jetpack' ),
				'invalid_configuration'     => __( 'Stored fee settings are not supported by this bounded scenario. Review the fee settings.', 'woocommerce-jetpack' ),
				'module_disabled'           => __( 'Gateways Fees and Discounts is not enabled for this request.', 'woocommerce-jetpack' ),
				'unknown_gateway'           => __( 'No stored fee configuration was found for this gateway identifier.', 'woocommerce-jetpack' ),
				'no_evaluation'             => __( 'No live fee callback has evaluated a rule in this request.', 'woocommerce-jetpack' ),
			);
			echo '<section><p><strong>' . esc_html( $result['state'] ) . ': ' . esc_html( $result['reason'] ) . '</strong></p>';
			if ( isset( $messages[ $result['reason'] ] ) ) {
				echo '<p>' . esc_html( $messages[ $result['reason'] ] ) . '</p>'; }
			if ( null !== $result['amount'] ) {
				echo '<p>' . esc_html( 'merchant_supplied_scenario' === $result['context'] ? __( 'Evaluator amount before WooCommerce cart tax/rounding (store currency):', 'woocommerce-jetpack' ) : __( 'Evaluator amount before WooCommerce cart tax/rounding (live evaluation currency):', 'woocommerce-jetpack' ) ) . ' <strong>' . esc_html( $result['amount'] ) . '</strong></p>'; }
			if ( $result['conditions'] ) {
				echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Rule', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Evaluation', 'woocommerce-jetpack' ) . '</th></tr></thead><tbody>';
				foreach ( $result['conditions'] as $condition ) {
					echo '<tr><td>' . esc_html( $condition['rule'] ) . '</td><td>' . esc_html( $condition['result'] ) . '</td></tr>'; }
				echo '</tbody></table>';
			}
			echo '</section>';
		}

		/** New diagnostics share the existing private merchant surface, not checkout controls. */
		private function render_payment_admin() {
			if ( ! is_admin() || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) || wcj_is_rest() ) {
				return; }
			$service = new WCJ_Rule_Explanations();
			echo '<section><h2>' . esc_html__( 'Payment option explanations', 'woocommerce-jetpack' ) . '</h2>';
			$result = null;
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Exact nonce admitted before execution below.
			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['wcj_payment_action'] ) ) {
				$nonce = isset( $_POST['wcj_payment_form_nonce'] ) && is_string( $_POST['wcj_payment_form_nonce'] ) ? filter_var(
					wp_unslash( $_POST['wcj_payment_form_nonce'] ),
					FILTER_CALLBACK,
					array(
						'options' =>
						/**
						 * Verify the exact token without repairing its bytes.
						 *
						 * @param string $value Unslashed token.
						 * @return int|false Existing WordPress nonce verdict.
						 */
						static function ( $value ) {
							return wp_verify_nonce( $value, 'wcj_payment_diagnostic' );
						},
					)
				) : wp_verify_nonce( '', 'wcj_payment_diagnostic' );
				if ( ! $nonce ) {
					$result = new WP_Error( 'booster_expired_rule_form', __( 'This form has expired. Reload this page.', 'woocommerce-jetpack' ) );
				} elseif ( 'observe' === $_POST['wcj_payment_action'] ) {
					$result = isset( $_POST['wcj_payment_gateway_key'] ) && is_string( $_POST['wcj_payment_gateway_key'] ) ? filter_var(
						wp_unslash( $_POST['wcj_payment_gateway_key'] ),
						FILTER_CALLBACK,
						array(
							'options' =>
							/**
							 * Delegate exact input to the existing strict producer.
							 *
							 * @param string $value Unslashed gateway identifier.
							 * @return string|WP_Error Existing link or fixed error.
							 */
							static function ( $value ) use ( $service ) {
								return $service->payment_observation_link( $value );
							},
						)
					) : $service->payment_observation_link( null );
				} elseif ( ! $service->is_full_experience() ) {
					$result = new WP_Error( 'booster_elite_required', __( 'Payment scenarios are available in Booster Elite.', 'woocommerce-jetpack' ) );
				} elseif ( isset( $_POST['wcj_payment_json'] ) && is_string( $_POST['wcj_payment_json'] ) && strlen( $_POST['wcj_payment_json'] ) <= 20000 ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw byte-length gate only; wp_unslash(), bounded JSON decoding and strict service validation follow.
					$input  = filter_var(
						wp_unslash( $_POST['wcj_payment_json'] ),
						FILTER_CALLBACK,
						array(
							'options' =>
							/**
							 * Decode bounded JSON without text sanitization.
							 *
							 * @param string $value Exact unslashed JSON.
							 * @return mixed Existing native decoded value.
							 */
							static function ( $value ) {
								return json_decode( $value, true, 12 );
							},
						)
					);
					$result = 'scenario' === $_POST['wcj_payment_action'] ? $service->explain_payment_options( $input ) : ( 'compare' === $_POST['wcj_payment_action'] ? $service->compare_payment_options( $input ) : new WP_Error( 'booster_invalid_payment_context', __( 'Invalid payment diagnostic input.', 'woocommerce-jetpack' ) ) );
				} else {
					$result = new WP_Error( 'booster_invalid_payment_context', __( 'Invalid payment diagnostic input.', 'woocommerce-jetpack' ) );
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
			if ( is_wp_error( $result ) ) {
				echo '<p>' . esc_html( $result->get_error_message() ) . '</p>'; } elseif ( is_string( $result ) ) {
				echo '<p><a href="' . esc_url( $result ) . '">' . esc_html__( 'Open private initial order-review observation', 'woocommerce-jetpack' ) . '</a></p>'; } elseif ( is_array( $result ) ) {
								echo '<p>' . esc_html( isset( $result['results'] ) ? 'Two sequential merchant-supplied scenarios; not an atomic comparison or a configuration snapshot.' : 'Merchant-supplied scenario; not a configuration snapshot.' ) . '</p>';
					foreach ( isset( $result['results'] ) ? $result['results'] : array( $result ) as $single ) {
						$service->render_payment_rows( $single ); }
				}
				echo '<section class="wcj-payment-observation-link"><h3>' . esc_html__( 'Initial order-review observation link', 'woocommerce-jetpack' ) . '</h3>';
				foreach ( WCJ_Rule_Explanations::payment_observation_labels() as $label ) {
					echo '<p>' . esc_html( $label ) . '</p>'; }
				echo '<form method="post" action="' . esc_url( admin_url( 'admin.php?page=wcj-rule-explanations' ) ) . '">';
				wp_nonce_field( 'wcj_payment_diagnostic', 'wcj_payment_form_nonce' );
				echo '<label>' . esc_html__( 'Diagnostic gateway key', 'woocommerce-jetpack' ) . ' <input name="wcj_payment_gateway_key" maxlength="64" pattern="[a-zA-Z0-9_-]+" required></label><button name="wcj_payment_action" value="observe">' . esc_html__( 'Create private observation link', 'woocommerce-jetpack' ) . '</button></form>';
				echo '</section>';
				if ( $service->is_full_experience() ) {
					echo '<p>' . esc_html__( 'Supply strict tagged facts as JSON. Unknown and known empty are distinct. No customer, order or session lookup is performed. Catalogue scenarios and live dynamic policies remain unsupported.', 'woocommerce-jetpack' ) . '</p>';
					echo '<form method="post" action="' . esc_url( admin_url( 'admin.php?page=wcj-rule-explanations' ) ) . '">';
					wp_nonce_field( 'wcj_payment_diagnostic', 'wcj_payment_form_nonce' );
					echo '<label>' . esc_html__( 'Explicit payment scenario or comparison JSON', 'woocommerce-jetpack' ) . '<textarea name="wcj_payment_json" maxlength="20000" required></textarea></label><button name="wcj_payment_action" value="scenario">' . esc_html__( 'Explain scenario', 'woocommerce-jetpack' ) . '</button><button name="wcj_payment_action" value="compare">' . esc_html__( 'Compare two scenarios', 'woocommerce-jetpack' ) . '</button></form>';
				}
				echo '</section>';
		}

		/**
		 * Renders private supplied-fact shipping diagnostics and their forms.
		 *
		 * @return void
		 */
		public function render_shipping_admin() {
			if ( ! is_admin() || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) || wcj_is_rest() ) {
				return; }
			$service = new WCJ_Rule_Explanations();
			$result  = null;
			$v2      = array_key_exists( 'wcj_shipping_contract_version', $_POST );
			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && array_key_exists( 'wcj_shipping_action', $_POST ) ) {
				$collision = false;
				foreach ( array( 'wcj_payment_action', 'wcj_payment_form_nonce', 'wcj_payment_gateway_key', 'wcj_payment_json', 'wcj_rule_action', 'wcj_rule_comparison_nonce', '_wpnonce', 'gateway', 'subtotal', 'shipping', 'tax', 'shipping_tax', 'product_ids', 'role', 'scenarios' ) as $key ) {
					if ( array_key_exists( $key, $_POST ) ) {
						$collision = true;
						break;
					}
				}
				if ( $collision || ( $v2 && ( ! is_string( $_POST['wcj_shipping_contract_version'] ) || '2' !== $_POST['wcj_shipping_contract_version'] ) ) || ( ! $v2 && array_key_exists( 'wcj_shipping_free_shipping_instance', $_POST ) ) ) {
					$result = new WP_Error( 'booster_shipping_invalid_input', __( 'Shipping diagnostic unavailable for this context.', 'woocommerce-jetpack' ) ); } else {
					$nonce = isset( $_POST['wcj_shipping_form_nonce'] ) && is_string( $_POST['wcj_shipping_form_nonce'] ) ? filter_var(
						wp_unslash( $_POST['wcj_shipping_form_nonce'] ),
						FILTER_CALLBACK,
						array(
							'options' =>
							/**
							 * Verify the exact token for the admitted contract.
							 *
							 * @param string $value Unslashed token.
							 * @return int|false Existing WordPress nonce verdict.
							 */
							static function ( $value ) use ( $v2 ) {
								return wp_verify_nonce( $value, $v2 ? 'wcj_shipping_diagnostic_v2' : 'wcj_shipping_diagnostic' );
							},
						)
					) : wp_verify_nonce( '', $v2 ? 'wcj_shipping_diagnostic_v2' : 'wcj_shipping_diagnostic' );
					if ( ! $nonce ) {
						$result = new WP_Error( 'booster_expired_rule_form', __( 'This form has expired. Reload this page and try again.', 'woocommerce-jetpack' ) ); } elseif ( ! is_string( $_POST['wcj_shipping_action'] ) || ! in_array( $_POST['wcj_shipping_action'], array( 'observe', 'scenario', 'compare' ), true ) ) {
						$result = new WP_Error( 'booster_shipping_invalid_input', __( 'Shipping diagnostic unavailable for this context.', 'woocommerce-jetpack' ) ); } elseif ( 'observe' !== $_POST['wcj_shipping_action'] && ! $service->is_full_experience() ) {
							$result = new WP_Error( 'booster_shipping_elite_required', __( 'Shipping scenarios require Elite.', 'woocommerce-jetpack' ) ); } elseif ( 'observe' === $_POST['wcj_shipping_action'] ) {
							$package_kind = isset( $_POST['wcj_shipping_package_kind'] ) ? wp_unslash( $_POST['wcj_shipping_package_kind'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslash here; strict package-kind string/regex and typed-key validation follow before use.
							$package_key  = isset( $_POST['wcj_shipping_package_key'] ) ? wp_unslash( $_POST['wcj_shipping_package_key'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslash here; strict package-key string/regex and typed-key validation follow before use.
							$package      = $service->shipping_transport_selector( is_string( $package_kind ) ? filter_var( $package_kind, FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/\A(?:int|string)\z/' ) ) ) : null, is_string( $package_key ) ? filter_var( $package_key, FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/\A(?=.{1,96}\z)[A-Za-z0-9_-]+(?::[A-Za-z0-9_-]+)*\z/' ) ) ) : null );
							$rate_kind    = isset( $_POST['wcj_shipping_rate_kind'] ) ? wp_unslash( $_POST['wcj_shipping_rate_kind'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslash here; strict rate-kind string/regex and typed-key validation follow before use.
							$rate_key     = isset( $_POST['wcj_shipping_rate_key'] ) ? wp_unslash( $_POST['wcj_shipping_rate_key'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslash here; strict rate-key string/regex and typed-key validation follow before use.
							$rate         = $service->shipping_transport_selector( is_string( $rate_kind ) ? filter_var( $rate_kind, FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/\A(?:int|string)\z/' ) ) ) : null, is_string( $rate_key ) ? filter_var( $rate_key, FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/\A(?=.{1,96}\z)[A-Za-z0-9_-]+(?::[A-Za-z0-9_-]+)*\z/' ) ) ) : null );
							if ( false === $package || false === $rate ) {
								$result = new WP_Error( 'booster_shipping_invalid_input', __( 'Shipping diagnostic unavailable for this context.', 'woocommerce-jetpack' ) );
							} elseif ( $v2 ) {
								$instance = isset( $_POST['wcj_shipping_free_shipping_instance'] ) ? wp_unslash( $_POST['wcj_shipping_free_shipping_instance'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslash here; strict instance string/regex and integer-selector validation follow before use.
								$result   = $service->shipping_v2_observation_link( $package, $rate, is_string( $instance ) ? filter_var( $instance, FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/\A(?:0|[1-9][0-9]{0,9})\z/' ) ) ) : null );
							} else {
								$result = $service->shipping_observation_link( $package, $rate );
							}
							} else {
								$in = isset( $_POST['wcj_shipping_json'] ) && is_string( $_POST['wcj_shipping_json'] ) ? filter_var(
									wp_unslash( $_POST['wcj_shipping_json'] ),
									FILTER_CALLBACK,
									array(
										'options' =>
										/**
										 * Parse exact bytes with the existing contract parser.
										 *
										 * @param string $value Unslashed JSON.
										 * @return mixed Original parsed value or fixed error.
										 */
										static function ( $value ) use ( $service, $v2 ) {
											return $v2 ? $service->parse_shipping_v2_json( $value ) : ( strlen( $value ) <= 8192 ? json_decode( $value, true, 12 ) : null );
										},
									)
								) : ( $v2 ? $service->parse_shipping_v2_json( null ) : null );
								if ( ! is_array( $in ) || ( ! $v2 && JSON_ERROR_NONE !== json_last_error() ) || ( $v2 && ( ! isset( $in['contract_version'] ) || 2 !== $in['contract_version'] ) ) || ( ! $v2 && isset( $in['contract_version'] ) ) ) {
													$result = new WP_Error( 'booster_shipping_invalid_input', __( 'Shipping diagnostic unavailable for this context.', 'woocommerce-jetpack' ) ); } else {
													$result = 'compare' === $_POST['wcj_shipping_action'] ? $service->compare_shipping_options( $in ) : $service->explain_shipping_options( $in ); }
							}
					}
			}
			echo '<section class="wcj-shipping-admin"><h2>' . esc_html__( 'Independent Booster shipping rule explanations', 'woocommerce-jetpack' ) . '</h2>';
			if ( is_wp_error( $result ) ) {
				echo '<p>' . esc_html( $result->get_error_code() ) . ': ' . esc_html( $result->get_error_message() ) . '</p>'; } elseif ( is_string( $result ) ) {
				echo '<a href="' . esc_url( $result ) . '">' . esc_html__( 'Own-actor shipping predicate observation, not final availability', 'woocommerce-jetpack' ) . '</a>'; } elseif ( is_array( $result ) ) {
					if ( isset( $result['results'] ) && $service->validate_shipping_output( $result, true ) ) {
						echo '<p>' . esc_html( $result['label'] ) . '</p>';
						foreach ( $result['results'] as $single ) {
							$service->render_shipping_rows( $single );
						}
					} else {
						$service->render_shipping_rows( $result );
					}
				}
				echo '<form method="post" action="' . esc_url( admin_url( 'admin.php?page=wcj-rule-explanations' ) ) . '">';
				wp_nonce_field( 'wcj_shipping_diagnostic_v2', 'wcj_shipping_form_nonce' );
				echo '<input type="hidden" name="wcj_shipping_contract_version" value="2">';
				echo '<input type="hidden" name="wcj_shipping_action" value="observe">';
				foreach ( array( 'package', 'rate' ) as $part ) {
					echo '<label>' . esc_html( $part ) . '<select name="wcj_shipping_' . esc_attr( $part ) . '_kind"><option value="int">int</option><option value="string">string</option></select><input name="wcj_shipping_' . esc_attr( $part ) . '_key" maxlength="96" value=""></label>'; }
				echo '<label>' . esc_html__( 'Free shipping instance', 'woocommerce-jetpack' ) . '<input name="wcj_shipping_free_shipping_instance" maxlength="10" value="0"></label><button type="submit">' . esc_html__( 'Create own-actor observation link', 'woocommerce-jetpack' ) . '</button></form>';
				if ( $service->is_full_experience() ) {
					foreach ( array( 'scenario', 'compare' ) as $action ) {
								echo '<form method="post" action="' . esc_url( admin_url( 'admin.php?page=wcj-rule-explanations' ) ) . '">';
								wp_nonce_field( 'wcj_shipping_diagnostic_v2', 'wcj_shipping_form_nonce' );
								echo '<input type="hidden" name="wcj_shipping_contract_version" value="2">';
								echo '<input type="hidden" name="wcj_shipping_action" value="' . esc_attr( $action ) . '"><textarea name="wcj_shipping_json" maxlength="8192"></textarea><button type="submit">' . esc_html( $action ) . '</button></form>';
					}
				}
				echo '<p>' . esc_html__( 'Independent supplied-fact predicates. Not an atomic shipping calculation or final availability.', 'woocommerce-jetpack' ) . '</p></section>';
		}
	}
endif;

new WCJ_Rule_Explanations_Admin();

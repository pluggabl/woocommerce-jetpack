<?php
/**
 * Booster True V10 - Guided Editor: EU VAT Number.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Editor_EU_VAT' ) ) :

	/**
	 * EU VAT Number guided editor with VIES method safety, base-country honesty, and Blocks warnings.
	 */
	class WCJ_V10_Editor_EU_VAT extends WCJ_V10_Editor_Base {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return 'eu_vat_number';
		}

		/**
		 * Relative JS module path.
		 *
		 * @return string
		 */
		public static function script() {
			return '/assets/js/admin/v10-editors/eu-vat-number.js';
		}

		/**
		 * Context keys accepted by this editor.
		 *
		 * @return array<int, string>
		 */
		public static function context_keys() {
			return array();
		}

		/**
		 * Primary Classic module section slug.
		 *
		 * @return string
		 */
		public static function primary_classic_section() {
			return 'eu_vat_number';
		}

		/**
		 * Required Booster module IDs for authorization.
		 *
		 * @return array<int, string>
		 */
		public static function required_module_ids() {
			return array( 'eu_vat_number' );
		}

		/**
		 * Summary card metadata for Command Center.
		 *
		 * @return array<string, string>
		 */
		public static function summary() {
			return array(
				'slug'        => self::slug(),
				'title'       => __( 'EU VAT Number', 'woocommerce-jetpack' ),
				'description' => __( 'Collect & validate B2B EU VAT numbers via VIES and exempt cross-border tax on Classic Checkout.', 'woocommerce-jetpack' ),
				'engine'      => __( 'B2B Tax Exemption Preview', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Classic section IDs handled by this guided editor.
		 *
		 * @return array<string, string>
		 */
		public static function section_map() {
			return array(
				'eu_vat_number' => self::slug(),
			);
		}

		/**
		 * Authoritative list of wp_options keys read/written by this editor.
		 *
		 * @param array $context Editor context.
		 * @return array<int, string>
		 */
		public static function owned_options( $context = array() ) {
			unset( $context );
			$keys = array(
				'wcj_eu_vat_number_enabled',
				'wcj_eu_vat_number_field_label',
				'wcj_eu_vat_number_field_placeholder',
				'wcj_eu_vat_number_field_description',
				'wcj_eu_vat_number_field_required',
				'wcj_eu_vat_number_validate',
				'wcj_eu_vat_number_not_valid_message',
				'wcj_eu_vat_number_first_method',
				'wcj_eu_vat_number_disable_for_valid',
				'wcj_eu_vat_number_disable_for_valid_on_cart',
				'wcj_eu_vat_number_display_position',
				'wcj_eu_vat_number_add_progress_text',
				'wcj_eu_vat_number_apply_country_code',
				'wcj_eu_vat_number_add_order_edit_metabox',
				'wcj_eu_vat_number_advanced_skip_countries',
			);
			if ( self::is_pro() ) {
				$keys[] = 'wcj_eu_vat_number_preserve_in_base_country';
				$keys[] = 'wcj_eu_vat_number_preserve_in_base_country_by_shipping_country';
				$keys[] = 'wcj_eu_vat_number_check_ip_location_country';
				$keys[] = 'wcj_eu_vat_number_show_vat_field_for_eu_only';
			}
			return $keys;
		}

		/**
		 * Plain-english label map for Review & Publish diffs.
		 *
		 * @return array<string, string>
		 */
		public static function diff_labels() {
			return array(
				'enabled'             => __( 'EU VAT Number Module', 'woocommerce-jetpack' ),
				'field_label'         => __( 'Checkout VAT Field Label', 'woocommerce-jetpack' ),
				'field_placeholder'   => __( 'Field Placeholder', 'woocommerce-jetpack' ),
				'field_required'      => __( 'Make EU VAT Field Required (EU countries)', 'woocommerce-jetpack' ),
				'validate'            => __( 'Validate VAT Number with VIES', 'woocommerce-jetpack' ),
				'not_valid_message'   => __( 'Invalid VAT Checkout Notice', 'woocommerce-jetpack' ),
				'disable_for_valid'   => __( 'Exempt VAT (0% Tax) for Valid Numbers', 'woocommerce-jetpack' ),
				'preserve_base'       => __( 'Keep Charging VAT in Base Country', 'woocommerce-jetpack' ),
				'eu_only'             => __( 'Show VAT Field Only for EU Billing Countries', 'woocommerce-jetpack' ),
				'check_ip_country'    => __( 'Match Customer IP Country Against VAT Number', 'woocommerce-jetpack' ),
				'first_method'        => __( 'Preferred VIES Lookup Method', 'woocommerce-jetpack' ),
				'disable_on_cart'     => __( 'Also Exempt VAT on Cart Page Totals', 'woocommerce-jetpack' ),
				'add_progress_text'   => __( 'Show Live VIES Progress Status Text', 'woocommerce-jetpack' ),
				'apply_country_code'  => __( 'Auto-Prefix Missing Billing Country Code', 'woocommerce-jetpack' ),
				'display_position'    => __( 'Order & Email Display Position', 'woocommerce-jetpack' ),
				'skip_countries'      => __( 'Countries Exempted From VIES Check', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Impact grouping metadata for Review & Publish modal.
		 *
		 * @return array<string, array{goes_live?: bool, stored_on_orders?: bool, impact: string}>
		 */
		public static function impact_groups() {
			return array(
				'enabled'           => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Adds EU VAT field to Classic Checkout and saves entered VAT numbers onto WooCommerce orders.', 'woocommerce-jetpack' ),
				),
				'field_label'       => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Displayed above checkout field and on order confirmation emails.', 'woocommerce-jetpack' ),
				),
				'field_required'    => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Requires EU shoppers to enter a VAT number before placing orders.', 'woocommerce-jetpack' ),
				),
				'validate'          => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Validates entered numbers live via EU VIES (blocks checkout if VIES fails/invalid when active).', 'woocommerce-jetpack' ),
				),
				'not_valid_message' => array(
					'goes_live' => true,
					'impact'    => __( 'Updates the checkout error notice shown when VIES rejects a VAT number.', 'woocommerce-jetpack' ),
				),
				'disable_for_valid' => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Zero-rates taxes on orders for validated EU VAT numbers (cannot be reversed automatically on existing orders).', 'woocommerce-jetpack' ),
				),
				'preserve_base'     => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Ensures domestic B2B buyers in your store base country still pay standard domestic VAT.', 'woocommerce-jetpack' ),
				),
				'eu_only'           => array(
					'goes_live' => true,
					'impact'    => __( 'Hides the VAT input unless the selected billing country is in the EU.', 'woocommerce-jetpack' ),
				),
				'check_ip_country'  => array(
					'goes_live' => true,
					'impact'    => __( 'Requires shopper IP country to match the VAT prefix country.', 'woocommerce-jetpack' ),
				),
				'first_method'      => array(
					'goes_live' => false,
					'impact'    => __( 'Selects primary VIES transport (SOAP is recommended).', 'woocommerce-jetpack' ),
				),
				'disable_on_cart'   => array(
					'goes_live' => true,
					'impact'    => __( 'Removes tax rows from the Cart page totals for validated VAT sessions.', 'woocommerce-jetpack' ),
				),
				'add_progress_text' => array(
					'goes_live' => true,
					'impact'    => __( 'Displays inline validation progress/result messages beside the VAT field.', 'woocommerce-jetpack' ),
				),
				'apply_country_code' => array(
					'goes_live' => true,
					'impact'    => __( 'Automatically prepends billing country ISO code before checking VIES.', 'woocommerce-jetpack' ),
				),
				'display_position'  => array(
					'goes_live' => false,
					'impact'    => __( 'Chooses where saved VAT IDs appear on order details and emails.', 'woocommerce-jetpack' ),
				),
				'skip_countries'    => array(
					'goes_live' => true,
					'impact'    => __( 'Comma-separated country codes exempted without contacting VIES.', 'woocommerce-jetpack' ),
				),
			);
		}

		/**
		 * Count EU tax rates currently configured in WooCommerce.
		 *
		 * @return int
		 */
		private static function count_eu_tax_rates() {
			global $wpdb;
			if ( ! function_exists( 'wcj_get_european_union_countries' ) ) {
				return 0;
			}
			$eu = array_values( array_filter( array_map( 'strtoupper', (array) wcj_get_european_union_countries() ) ) );
			if ( empty( $eu ) ) {
				return 0;
			}
			$placeholders = implode( ',', array_fill( 0, count( $eu ), '%s' ) );
			$sql          = "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country IN ({$placeholders})";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $eu ) );
		}

		/**
		 * Load live state, metadata, and safety diagnostics.
		 *
		 * @param array $context   Editor context.
		 * @param array $store_ctx Store context.
		 * @return array<string, mixed>
		 */
		public static function load_state( $context, $store_ctx ) {
			unset( $context );
			$nonce        = wp_create_nonce( 'wcj-cat-nonce' );
			$preview_mode = ! empty( $store_ctx['preview_mode'] );
			$is_pro       = self::is_pro();
			$snap         = WCJ_V10_Store::snapshot_options( self::owned_options() );
			$base_country = strtoupper( (string) ( $store_ctx['base_country'] ?? 'DE' ) );
			$eu_rates     = $preview_mode ? 0 : self::count_eu_tax_rates();
			$taxes_on     = ! empty( $store_ctx['taxes_enabled'] );
			$is_blocks    = ! empty( $store_ctx['is_blocks_checkout'] );
			$method       = (string) self::snap_val( $snap, 'wcj_eu_vat_number_first_method', 'soap' );
			if ( ! in_array( $method, array( 'soap', 'curl', 'file_get_contents' ), true ) ) {
				$method = 'soap';
			}

			$skip_absent = empty( $snap['wcj_eu_vat_number_advanced_skip_countries']['exists'] );
			$skip_val    = $skip_absent ? '' : (string) self::snap_val( $snap, 'wcj_eu_vat_number_advanced_skip_countries', '' );
			$enabled_live = 'yes' === (string) self::snap_val( $snap, 'wcj_eu_vat_number_enabled', 'no' );

			$preserve_raw = (string) get_option( 'wcj_eu_vat_number_preserve_in_base_country', 'no' );
			$eu_only_raw  = (string) get_option( 'wcj_eu_vat_number_show_vat_field_for_eu_only', 'no' );
			$check_ip_raw = (string) get_option( 'wcj_eu_vat_number_check_ip_location_country', 'no' );
			$role_gate    = self::is_elite() ? get_option( 'wcj_eu_vat_number_user_role', '' ) : '';

			$diagnostics = array();
			if ( ! $preview_mode && $is_blocks ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Block Checkout detected: Booster EU VAT Number only supports Classic Checkout ([woocommerce_checkout] shortcode). Shoppers on Checkout Block will NOT see the VAT field or receive tax exemption.', 'woocommerce-jetpack' ),
				);
			}
			if ( ! $taxes_on || 0 === $eu_rates ) {
				$diagnostics[] = array(
					'level'        => 'warning',
					'text'         => __( 'No EU WooCommerce tax rates found (or taxes are disabled). Zero-rating cannot remove VAT unless WooCommerce Tax Rates exist for EU countries.', 'woocommerce-jetpack' ),
					'action_label' => __( 'Open Tax Settings', 'woocommerce-jetpack' ),
					'action_url'   => $store_ctx['tax_settings_url'] ?? admin_url( 'admin.php?page=wc-settings&tab=tax' ),
				);
			}
			if ( ! $is_pro ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => sprintf(
						/* translators: %s: Store base country ISO code */
						__( 'Domestic VAT safeguard: In Booster Free, a valid VAT number in your base country (%s) also receives 0%% VAT. Verify domestic tax rules with your accountant or upgrade to Plus/Elite to keep domestic VAT charged.', 'woocommerce-jetpack' ),
						'' !== $base_country ? $base_country : 'Base Country'
					),
				);
			}
			if ( in_array( $method, array( 'curl', 'file_get_contents' ), true ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Preferred VIES lookup method is set to REST (cURL/file_get_contents). If the EU VIES server times out, REST lookup can fail harshly — switch to SOAP for safer fallback.', 'woocommerce-jetpack' ),
				);
			}
			if ( $enabled_live && $skip_absent ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Skip Countries option has never been saved: Booster native runtime defaults it to "no", which exempts Norway (NO) numbers automatically. Publish any update here to lock Skip Countries safely to empty.', 'woocommerce-jetpack' ),
				);
			}
			if ( ! empty( $store_ctx['has_page_cache'] ) ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => __( 'EU VAT uses PHP sessions to remember validated numbers. Ensure Cart & Checkout pages bypass full-page caching.', 'woocommerce-jetpack' ),
				);
			}
			if ( ! $is_pro && 'yes' === $eu_only_raw ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => __( 'Saved EU-Only billing visibility option is still active at runtime in Free and preserved untouched unless reset in Classic View.', 'woocommerce-jetpack' ),
				);
			}
			if ( ( ! $is_pro && ( 'yes' === $preserve_raw || 'yes' === $check_ip_raw ) ) || ( self::is_elite() && ! empty( $role_gate ) ) ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => __( 'Additional Classic/Pro rules (for example saved IP checks, base-country preservation, or user-role restrictions) exist in your database and are preserved untouched.', 'woocommerce-jetpack' ),
				);
			}

			return array(
				'slug'        => self::slug(),
				'edition'     => WCJ_V10_Store::edition(),
				'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=prices_and_currencies&section=eu_vat_number&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				'live'        => array(
					'enabled'            => $enabled_live,
					'field_label'        => (string) self::snap_val( $snap, 'wcj_eu_vat_number_field_label', 'EU VAT Number' ),
					'field_placeholder'  => (string) self::snap_val( $snap, 'wcj_eu_vat_number_field_placeholder', '' ),
					'field_description'  => (string) self::snap_val( $snap, 'wcj_eu_vat_number_field_description', '' ),
					'field_required'     => 'yes' === (string) self::snap_val( $snap, 'wcj_eu_vat_number_field_required', 'no' ),
					'validate'           => 'yes' === (string) self::snap_val( $snap, 'wcj_eu_vat_number_validate', 'yes' ),
					'not_valid_message'  => (string) self::snap_val( $snap, 'wcj_eu_vat_number_not_valid_message', 'EU VAT Number is not valid.' ),
					'first_method'       => $method,
					'disable_for_valid'  => 'yes' === (string) self::snap_val( $snap, 'wcj_eu_vat_number_disable_for_valid', 'yes' ),
					'disable_on_cart'    => 'yes' === (string) self::snap_val( $snap, 'wcj_eu_vat_number_disable_for_valid_on_cart', 'no' ),
					'display_position'   => ( 'in_billing_address' === (string) self::snap_val( $snap, 'wcj_eu_vat_number_display_position', 'after_order_table' ) ) ? 'in_billing_address' : 'after_order_table',
					'add_progress_text'  => 'yes' === (string) self::snap_val( $snap, 'wcj_eu_vat_number_add_progress_text', 'no' ),
					'apply_country_code' => 'yes' === (string) self::snap_val( $snap, 'wcj_eu_vat_number_apply_country_code', 'no' ),
					'skip_countries'     => $skip_val,
					'preserve_base'      => $is_pro && 'yes' === (string) self::snap_val( $snap, 'wcj_eu_vat_number_preserve_in_base_country', 'no' ),
					'preserve_shipping'  => $is_pro && 'yes' === (string) self::snap_val( $snap, 'wcj_eu_vat_number_preserve_in_base_country_by_shipping_country', 'no' ),
					'check_ip_country'   => $is_pro && 'yes' === (string) self::snap_val( $snap, 'wcj_eu_vat_number_check_ip_location_country', 'no' ),
					'eu_only'            => $is_pro && 'yes' === (string) self::snap_val( $snap, 'wcj_eu_vat_number_show_vat_field_for_eu_only', 'no' ),
				),
				'meta'        => array(
					'is_pro'             => $is_pro,
					'base_country'       => $base_country,
					'currency_symbol'    => $store_ctx['currency_symbol'] ?? '$',
					'decimals'           => (int) ( $store_ctx['decimals'] ?? 2 ),
					'is_blocks_checkout' => $is_blocks,
					'taxes_enabled'      => $taxes_on,
					'eu_tax_rate_count'  => $eu_rates,
					'verify_url'         => $store_ctx['checkout_url'] ?? home_url( '/checkout/' ),
					'verify_label'       => __( 'Verify on Live Checkout', 'woocommerce-jetpack' ),
					'diagnostics'        => $diagnostics,
				),
			);
		}

		/**
		 * Sanitize draft values while preserving untouched live values verbatim.
		 *
		 * @param array $draft Raw draft input.
		 * @param array $live  Current live state.
		 * @return array<string, mixed>
		 */
		public static function sanitize_draft( $draft, $live ) {
			$is_pro = self::is_pro();
			$str    = static function ( $k ) use ( $draft, $live ) {
				if ( ! array_key_exists( $k, $draft ) || (string) $draft[ $k ] === (string) $live[ $k ] ) {
					return $live[ $k ];
				}
				return sanitize_text_field( (string) $draft[ $k ] );
			};

			$method = $live['first_method'];
			if ( array_key_exists( 'first_method', $draft ) ) {
				$cand   = sanitize_key( (string) $draft['first_method'] );
				$method = in_array( $cand, array( 'soap', 'curl', 'file_get_contents' ), true ) ? $cand : $live['first_method'];
			}

			$pos = $live['display_position'];
			if ( array_key_exists( 'display_position', $draft ) ) {
				$pos = 'in_billing_address' === $draft['display_position'] ? 'in_billing_address' : 'after_order_table';
			}

			return array(
				'enabled'            => array_key_exists( 'enabled', $draft ) ? ! empty( $draft['enabled'] ) : ! empty( $live['enabled'] ),
				'field_label'        => $str( 'field_label' ),
				'field_placeholder'  => $str( 'field_placeholder' ),
				'field_description'  => $str( 'field_description' ),
				'field_required'     => array_key_exists( 'field_required', $draft ) ? ! empty( $draft['field_required'] ) : ! empty( $live['field_required'] ),
				'validate'           => array_key_exists( 'validate', $draft ) ? ! empty( $draft['validate'] ) : ! empty( $live['validate'] ),
				'not_valid_message'  => $str( 'not_valid_message' ),
				'first_method'       => $method,
				'disable_for_valid'  => array_key_exists( 'disable_for_valid', $draft ) ? ! empty( $draft['disable_for_valid'] ) : ! empty( $live['disable_for_valid'] ),
				'disable_on_cart'    => array_key_exists( 'disable_on_cart', $draft ) ? ! empty( $draft['disable_on_cart'] ) : ! empty( $live['disable_on_cart'] ),
				'display_position'   => $pos,
				'add_progress_text'  => array_key_exists( 'add_progress_text', $draft ) ? ! empty( $draft['add_progress_text'] ) : ! empty( $live['add_progress_text'] ),
				'apply_country_code' => array_key_exists( 'apply_country_code', $draft ) ? ! empty( $draft['apply_country_code'] ) : ! empty( $live['apply_country_code'] ),
				'skip_countries'     => $str( 'skip_countries' ),
				'preserve_base'      => $is_pro ? ( array_key_exists( 'preserve_base', $draft ) ? ! empty( $draft['preserve_base'] ) : ! empty( $live['preserve_base'] ) ) : false,
				'preserve_shipping'  => $is_pro ? ( array_key_exists( 'preserve_shipping', $draft ) ? ! empty( $draft['preserve_shipping'] ) : ! empty( $live['preserve_shipping'] ) ) : false,
				'check_ip_country'   => $is_pro ? ( array_key_exists( 'check_ip_country', $draft ) ? ! empty( $draft['check_ip_country'] ) : ! empty( $live['check_ip_country'] ) ) : false,
				'eu_only'            => $is_pro ? ( array_key_exists( 'eu_only', $draft ) ? ! empty( $draft['eu_only'] ) : ! empty( $live['eu_only'] ) ) : false,
			);
		}

		/**
		 * Require non-empty label when EU VAT module is active.
		 *
		 * @param array $draft        Sanitized draft.
		 * @param array $live         Live state.
		 * @param array $changed_keys Changed diff keys.
		 * @return true|WP_Error
		 */
		public static function validate_publish( $draft, $live = array(), $changed_keys = array() ) {
			unset( $live, $changed_keys );
			if ( empty( $draft['enabled'] ) ) {
				return true;
			}
			if ( '' === trim( (string) $draft['field_label'] ) ) {
				return new WP_Error(
					'wcj_v10_missing_vat_label',
					__( 'Enter a Checkout VAT Field Label (for example "EU VAT Number") before publishing.', 'woocommerce-jetpack' ),
					array( 'status' => 400 )
				);
			}
			return true;
		}

		/**
		 * Deterministic offline B2B tax-exemption preview (never hits live VIES).
		 *
		 * @param array $live    Live state.
		 * @param array $draft   Draft state.
		 * @param array $context Preview context.
		 * @param array $meta    Metadata.
		 * @return array{before: array, after: array}
		 */
		public static function compute_preview( $live, $draft, $context, $meta ) {
			$is_pro       = ! empty( $meta['is_pro'] );
			$base_country = strtoupper( (string) ( $meta['base_country'] ?? 'DE' ) );
			$scenario     = isset( $context['vat_scenario'] ) ? sanitize_key( (string) $context['vat_scenario'] ) : 'eu_valid';
			$subtotal     = 100.0;
			$vat_rate     = 0.20;

			$project = static function ( $cfg, $force_preserve_sample = false ) use ( $is_pro, $base_country, $scenario, $subtotal, $vat_rate ) {
				$active    = ! empty( $cfg['enabled'] );
				$eu_only   = $is_pro && ! empty( $cfg['eu_only'] );
				$show_fld  = $active && ( ! $eu_only || 'non_eu' !== $scenario );
				$preserve  = ( $is_pro && ! empty( $cfg['preserve_base'] ) ) || $force_preserve_sample;
				$validate  = ! empty( $cfg['validate'] );
				$exempt_on = ! empty( $cfg['disable_for_valid'] );

				$vies_status  = 'not_checked';
				$tax_exempt   = false;
				$notice       = '';
				$checkout_ok  = true;
				$sample_eu    = ( 'DE' === $base_country ) ? 'AT' : 'DE';
				$base_lbl     = '' !== $base_country ? $base_country : 'DE';

				if ( $active && 'non_eu' !== $scenario ) {
					if ( 'eu_valid' === $scenario ) {
						$vies_status = $validate ? 'valid' : 'unvalidated';
						if ( $validate && $exempt_on ) {
							$tax_exempt = ! ( $preserve && $base_country === $sample_eu );
						}
						$notice = $tax_exempt
							? __( 'VAT is valid. Reverse charge 0% VAT applied.', 'woocommerce-jetpack' )
							: ( ! $validate ? __( 'Saved on order without live VIES tax exemption.', 'woocommerce-jetpack' ) : __( 'Valid VAT number; base country VAT kept.', 'woocommerce-jetpack' ) );
					} elseif ( 'domestic' === $scenario ) {
						$vies_status = $validate ? 'valid' : 'unvalidated';
						if ( $validate && $exempt_on ) {
							$tax_exempt = ! $preserve;
						}
						$notice = $tax_exempt
							? sprintf( __( 'Warning: Domestic (%s) buyer exempted to 0%% VAT (base-country preservation is Off).', 'woocommerce-jetpack' ), '' !== $base_country ? $base_country : 'Base' )
							: sprintf( __( 'Valid VAT recorded; domestic %s VAT kept charged.', 'woocommerce-jetpack' ), '' !== $base_country ? $base_country : 'Base' );
					} elseif ( 'invalid' === $scenario ) {
						$vies_status = 'invalid';
						$tax_exempt  = false;
						if ( $validate ) {
							$checkout_ok = false;
							$notice      = (string) ( $cfg['not_valid_message'] ?: 'EU VAT Number is not valid.' );
						} else {
							$notice = __( 'Validation Off: checkout proceeds with standard VAT.', 'woocommerce-jetpack' );
						}
					} elseif ( 'vies_down' === $scenario ) {
						$vies_status = 'unavailable';
						$tax_exempt  = false;
						if ( $validate ) {
							$checkout_ok = false;
							$notice      = __( 'VIES unreachable: Checkout blocks EU order unless Validate is turned off or buyer clears VAT.', 'woocommerce-jetpack' );
						} else {
							$notice = __( 'Validation Off: standard VAT charged despite VIES outage.', 'woocommerce-jetpack' );
						}
					}
				} elseif ( $active && 'non_eu' === $scenario ) {
					$notice = $eu_only
						? __( 'VAT field hidden cleanly for non-EU shoppers.', 'woocommerce-jetpack' )
						: __( 'VAT field stays optional for non-EU billing addresses.', 'woocommerce-jetpack' );
				}

				$vat_amt = $tax_exempt ? 0.0 : round( $subtotal * $vat_rate, 2 );
				$total   = round( $subtotal + $vat_amt, 2 );

				return array(
					'active'       => $active,
					'show_field'   => $show_fld,
					'label'        => '' !== trim( (string) $cfg['field_label'] ) ? (string) $cfg['field_label'] : 'EU VAT Number',
					'required'     => ! empty( $cfg['field_required'] ) && 'non_eu' !== $scenario,
					'vies_status'  => $vies_status,
					'tax_exempt'   => $tax_exempt,
					'checkout_ok'  => $checkout_ok,
					'sample_code'  => 'domestic' === $scenario
						? $base_lbl . '123456789'
						: ( 'invalid' === $scenario ? $sample_eu . '000000000' : ( 'non_eu' === $scenario ? '' : $sample_eu . '123456789' ) ),
					'notice'       => $notice,
					'subtotal'     => $subtotal,
					'vat_amount'   => $vat_amt,
					'total'        => $total,
				);
			};

			return array(
				'before' => $project( $live, false ),
				'after'  => $project( $draft, false ),
			);
		}

		/**
		 * Map changed draft keys to authoritative wp_options writes.
		 *
		 * @param array $draft        Sanitized draft.
		 * @param array $changed_keys Changed diff keys.
		 * @param array $snapshot     Pre-write DB snapshot.
		 * @return array<string, mixed>
		 */
		public static function build_option_writes( $draft, $changed_keys, $snapshot = array() ) {
			$map    = array_flip( (array) $changed_keys );
			$is_pro = self::is_pro();
			$writes = array();

			$bools = array(
				'enabled'            => 'wcj_eu_vat_number_enabled',
				'field_required'     => 'wcj_eu_vat_number_field_required',
				'validate'           => 'wcj_eu_vat_number_validate',
				'disable_for_valid'  => 'wcj_eu_vat_number_disable_for_valid',
				'disable_on_cart'    => 'wcj_eu_vat_number_disable_for_valid_on_cart',
				'add_progress_text'  => 'wcj_eu_vat_number_add_progress_text',
				'apply_country_code' => 'wcj_eu_vat_number_apply_country_code',
			);
			foreach ( $bools as $dk => $ok ) {
				if ( isset( $map[ $dk ] ) ) {
					$writes[ $ok ] = ! empty( $draft[ $dk ] ) ? 'yes' : 'no';
				}
			}

			$strs = array(
				'field_label'       => 'wcj_eu_vat_number_field_label',
				'field_placeholder' => 'wcj_eu_vat_number_field_placeholder',
				'field_description' => 'wcj_eu_vat_number_field_description',
				'not_valid_message' => 'wcj_eu_vat_number_not_valid_message',
				'first_method'      => 'wcj_eu_vat_number_first_method',
				'display_position'  => 'wcj_eu_vat_number_display_position',
				'skip_countries'    => 'wcj_eu_vat_number_advanced_skip_countries',
			);
			foreach ( $strs as $dk => $ok ) {
				if ( isset( $map[ $dk ] ) ) {
					$writes[ $ok ] = $draft[ $dk ];
				}
			}

			// Guard P0 runtime trap: when enabled on an unsaved site, initialize skip_countries='' (prevents Norway 'NO' exemption), validate, and disable_for_valid.
			if ( ! empty( $draft['enabled'] ) ) {
				if ( empty( $snapshot['wcj_eu_vat_number_field_label']['exists'] ) && ! isset( $writes['wcj_eu_vat_number_field_label'] ) ) {
					$writes['wcj_eu_vat_number_field_label'] = $draft['field_label'];
				}
				if ( empty( $snapshot['wcj_eu_vat_number_advanced_skip_countries']['exists'] ) && ! isset( $writes['wcj_eu_vat_number_advanced_skip_countries'] ) ) {
					$writes['wcj_eu_vat_number_advanced_skip_countries'] = '';
				}
				if ( empty( $snapshot['wcj_eu_vat_number_first_method']['exists'] ) && ! isset( $writes['wcj_eu_vat_number_first_method'] ) ) {
					$writes['wcj_eu_vat_number_first_method'] = $draft['first_method'];
				}
				if ( empty( $snapshot['wcj_eu_vat_number_field_required']['exists'] ) && ! isset( $writes['wcj_eu_vat_number_field_required'] ) ) {
					$writes['wcj_eu_vat_number_field_required'] = ! empty( $draft['field_required'] ) ? 'yes' : 'no';
				}
			}

			if ( $is_pro ) {
				$pro_bools = array(
					'preserve_base'     => 'wcj_eu_vat_number_preserve_in_base_country',
					'preserve_shipping' => 'wcj_eu_vat_number_preserve_in_base_country_by_shipping_country',
					'check_ip_country'  => 'wcj_eu_vat_number_check_ip_location_country',
					'eu_only'           => 'wcj_eu_vat_number_show_vat_field_for_eu_only',
				);
				foreach ( $pro_bools as $dk => $ok ) {
					if ( isset( $map[ $dk ] ) ) {
						$writes[ $ok ] = ! empty( $draft[ $dk ] ) ? 'yes' : 'no';
					}
				}
			}

			return $writes;
		}

		/**
		 * Emergency shutoff.
		 *
		 * @param array<string, array> $snapshot Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function emergency_off_writes( $snapshot = array() ) {
			if ( 'yes' !== (string) self::snap_val( $snapshot, 'wcj_eu_vat_number_enabled', 'no' ) ) {
				return array();
			}
			return array(
				'wcj_eu_vat_number_enabled' => 'no',
			);
		}
	}

endif;

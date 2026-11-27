<?php
/**
 * Booster True V10 - Guided Editor: Cart Abandonment.
 *
 * Strictly enforces Free & Plus single-template caps while unlocking multi-step sequences & coupons in Elite.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Editor_Cart_Abandonment' ) ) :

	/**
	 * Cart Abandonment guided editor with WP-Cron, midnight-UTC, and real-email safeguards.
	 */
	class WCJ_V10_Editor_Cart_Abandonment extends WCJ_V10_Editor_Base {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return 'cart_abandonment';
		}

		/**
		 * Relative JS module path.
		 *
		 * @return string
		 */
		public static function script() {
			return '/assets/js/admin/v10-editors/cart-abandonment.js';
		}

		/**
		 * Context keys accepted by this editor.
		 *
		 * @return array<int, string>
		 */
		public static function context_keys() {
			return array( 'template_index' );
		}

		/**
		 * Primary Classic module section slug.
		 *
		 * @return string
		 */
		public static function primary_classic_section() {
			return 'cart_abandonment';
		}

		/**
		 * Required Booster module IDs for authorization.
		 *
		 * @return array<int, string>
		 */
		public static function required_module_ids() {
			return array( 'cart_abandonment' );
		}

		/**
		 * Summary card metadata for Command Center.
		 *
		 * @return array<string, string>
		 */
		public static function summary() {
			return array(
				'slug'        => self::slug(),
				'title'       => __( 'Cart Abandonment', 'woocommerce-jetpack' ),
				'description' => __( 'Send automated abandoned-cart recovery reminders with live email & timeline previews.', 'woocommerce-jetpack' ),
				'engine'      => __( 'Recovery Sequence Preview', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Classic section IDs handled by this guided editor.
		 *
		 * @return array<string, string>
		 */
		public static function section_map() {
			return array(
				'cart_abandonment' => self::slug(),
			);
		}

		/**
		 * Resolve active email template index (1 in Free & Plus; 1..total_number in Elite).
		 *
		 * @param array $context Editor context.
		 * @return int
		 */
		private static function resolve_index( $context = array() ) {
			if ( ! self::is_elite() ) {
				return 1;
			}
			$total = max( 1, min( 5, (int) get_option( 'wcj_ca_email_template_total_number', 1 ) ) );
			$raw   = isset( $context['template_index'] ) ? absint( $context['template_index'] ) : 1;
			return max( 1, min( $total, $raw ) );
		}

		/**
		 * Authoritative list of wp_options keys read/written by this editor.
		 *
		 * @param array $context Editor context (template_index).
		 * @return array<int, string>
		 */
		public static function owned_options( $context = array() ) {
			$i    = self::resolve_index( $context );
			$keys = array(
				'wcj_cart_abandonment_enabled',
				'wcj_cart_abandonment_email_from_name',
				'wcj_cart_abandonment_email_from_email',
				'wcj_ca_email_template_enabled_' . $i,
				'wcj_ca_email_template_subject_' . $i,
				'wcj_ca_email_template_body_' . $i,
				'wcj_ca_email_trigger_time_' . $i,
				'wcj_ca_email_trigger_time_type_' . $i,
				'wcj_ca_email_discount_type_' . $i,
			);
			if ( self::is_elite() ) {
				$keys[] = 'wcj_ca_email_template_total_number';
				$keys[] = 'wcj_cart_abandonment_disable_user_role';
				$keys[] = 'wcj_ca_email_discount_amount_' . $i;
				$keys[] = 'wcj_ca_auto_apply_coupon_' . $i;
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
				'enabled'           => __( 'Cart Abandonment Module', 'woocommerce-jetpack' ),
				'template_enabled'  => __( 'Reminder Email Active', 'woocommerce-jetpack' ),
				'trigger_time'      => __( 'Send Delay Amount', 'woocommerce-jetpack' ),
				'trigger_time_type' => __( 'Send Delay Unit', 'woocommerce-jetpack' ),
				'subject'           => __( 'Email Subject Line', 'woocommerce-jetpack' ),
				'body'              => __( 'Email Body Content', 'woocommerce-jetpack' ),
				'from_name'         => __( 'Sender Name (Elite)', 'woocommerce-jetpack' ),
				'from_email'        => __( 'Sender Email Address (Elite)', 'woocommerce-jetpack' ),
				'discount_type'     => __( 'Recovery Coupon Type (Elite)', 'woocommerce-jetpack' ),
				'discount_amount'   => __( 'Recovery Coupon Amount (Elite)', 'woocommerce-jetpack' ),
				'auto_apply'        => __( 'Auto-Apply Coupon on Link Click (Elite)', 'woocommerce-jetpack' ),
				'total_templates'   => __( 'Number of Sequence Emails (Elite)', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Impact grouping metadata including 4th "sends_emails" safety group.
		 *
		 * @return array<string, array{goes_live?: bool, sends_emails?: bool, impact: string}>
		 */
		public static function impact_groups() {
			return array(
				'enabled'           => array(
					'sends_emails' => true,
					'impact'       => __( 'Starts or stops abandoned cart tracking & scheduled reminder emails for real shoppers.', 'woocommerce-jetpack' ),
				),
				'template_enabled'  => array(
					'sends_emails' => true,
					'impact'       => __( 'Enables or pauses sending this automated reminder email via WP-Cron every 15 minutes.', 'woocommerce-jetpack' ),
				),
				'trigger_time'      => array(
					'sends_emails' => true,
					'impact'       => __( 'Changes how soon captured carts become eligible for sending on the next cron run.', 'woocommerce-jetpack' ),
				),
				'trigger_time_type' => array(
					'sends_emails' => true,
					'impact'       => __( 'Changes the delay unit (Minutes or Hours) evaluated against queued carts.', 'woocommerce-jetpack' ),
				),
				'subject'           => array(
					'sends_emails' => true,
					'impact'       => __( 'Shown directly in customers\' inboxes as the email subject.', 'woocommerce-jetpack' ),
				),
				'body'              => array(
					'sends_emails' => true,
					'impact'       => __( 'Sent as HTML email body to customers who abandoned checkout today.', 'woocommerce-jetpack' ),
				),
				'discount_type'     => array(
					'sends_emails' => true,
					'impact'       => __( 'Creates single-use WooCommerce recovery coupon codes per sent reminder email.', 'woocommerce-jetpack' ),
				),
				'discount_amount'   => array(
					'sends_emails' => true,
					'impact'       => __( 'Sets the discount amount on newly generated recovery coupons.', 'woocommerce-jetpack' ),
				),
				'from_name'         => array(
					'goes_live' => false,
					'impact'    => __( 'Updates the sender display name on future reminder emails.', 'woocommerce-jetpack' ),
				),
				'from_email'        => array(
					'goes_live' => false,
					'impact'    => __( 'Updates the From email header on future reminder emails.', 'woocommerce-jetpack' ),
				),
				'auto_apply'        => array(
					'goes_live' => true,
					'impact'    => __( 'Automatically applies generated coupon when customer clicks the checkout restore link.', 'woocommerce-jetpack' ),
				),
				'total_templates'   => array(
					'goes_live' => false,
					'impact'    => __( 'Controls how many sequential email steps exist in your Elite recovery flow.', 'woocommerce-jetpack' ),
				),
			);
		}

		/**
		 * Normalize discount type enum safely away from Classic settings string bug 'No Discount'.
		 *
		 * @param mixed $raw Raw option value.
		 * @return string
		 */
		private static function normalize_discount_type( $raw ) {
			$val = (string) $raw;
			if ( in_array( $val, array( 'percent', 'fixed_cart' ), true ) ) {
				return $val;
			}
			return 'no';
		}

		/**
		 * Inspect queued carts & cron readiness without touching custom tables directly beyond read counts.
		 *
		 * @return array<string, mixed>
		 */
		private static function inspect_queue() {
			global $wpdb;
			$table         = $wpdb->prefix . 'wcj_cart_abandonment_data';
			$pending_today = 0;
			$total_rows    = 0;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
			if ( $exists ) {
				$today = gmdate( 'Y-m-d' );
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$pending_today = (int) $wpdb->get_var(
					$wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE order_status != %s AND DATE(`time`) = %s", 'recovered', $today )
				);
				$total_rows = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
				// phpcs:enable
			}

			$next_cron = function_exists( 'wp_next_scheduled' ) ? (int) wp_next_scheduled( 'wcj_cart_abandonment_update_order_status_action' ) : 0;

			return array(
				'table_ready'   => $exists,
				'pending_today' => $pending_today,
				'total_rows'    => $total_rows,
				'next_cron_ts'  => $next_cron,
			);
		}

		/**
		 * Load live state, metadata, and queue/deliverability diagnostics.
		 *
		 * @param array $context   Editor context (template_index).
		 * @param array $store_ctx Store context.
		 * @return array<string, mixed>
		 */
		public static function load_state( $context, $store_ctx ) {
			$nonce        = wp_create_nonce( 'wcj-cat-nonce' );
			$preview_mode = ! empty( $store_ctx['preview_mode'] );
			$is_elite     = self::is_elite();
			$i            = self::resolve_index( $context );
			$total        = $is_elite ? max( 1, min( 5, (int) get_option( 'wcj_ca_email_template_total_number', 1 ) ) ) : 1;
			$snap         = WCJ_V10_Store::snapshot_options( self::owned_options( array( 'template_index' => $i ) ) );
			$queue        = $preview_mode
				? array( 'table_ready' => true, 'pending_today' => 0, 'total_rows' => 0, 'next_cron_ts' => 0 )
				: self::inspect_queue();

			$default_from_email = (string) get_option( 'admin_email', 'store@example.com' );
			$default_from_name  = (string) get_bloginfo( 'name' );
			if ( '' === $default_from_name && false !== strpos( $default_from_email, '@' ) ) {
				$parts             = explode( '@', $default_from_email );
				$default_from_name = (string) $parts[0];
			}

			$enabled_live = 'yes' === (string) self::snap_val( $snap, 'wcj_cart_abandonment_enabled', 'no' );
			$tpl_enabled  = ! empty( $snap[ 'wcj_ca_email_template_enabled_' . $i ]['exists'] )
				&& 'yes' === (string) self::snap_val( $snap, 'wcj_ca_email_template_enabled_' . $i, 'no' );

			$raw_disc = self::snap_val( $snap, 'wcj_ca_email_discount_type_' . $i, 'no' );
			$disc     = self::normalize_discount_type( $raw_disc );

			$from_name_raw  = self::snap_val( $snap, 'wcj_cart_abandonment_email_from_name', $default_from_name );
			$from_email_raw = self::snap_val( $snap, 'wcj_cart_abandonment_email_from_email', $default_from_email );

			$raw_unit  = (string) self::snap_val( $snap, 'wcj_ca_email_trigger_time_type_' . $i, 'hour' );
			$unit      = in_array( $raw_unit, array( 'minute', 'hour', 'day' ), true ) ? $raw_unit : 'hour';
			$raw_delay = (string) self::snap_val( $snap, 'wcj_ca_email_trigger_time_' . $i, '1' );

			$templates_nav = array();
			if ( ! $preview_mode ) {
				for ( $t = 1; $t <= $total; $t++ ) {
					$sub             = trim( (string) get_option( 'wcj_ca_email_template_subject_' . $t, '' ) );
					$templates_nav[] = array(
						'index' => $t,
						'label' => '' !== $sub ? $sub : sprintf( __( 'Email #%d', 'woocommerce-jetpack' ), $t ),
					);
				}
			}

			$diagnostics = array();
			if ( ! $preview_mode && ! empty( $store_ctx['cron_disabled'] ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'DISABLE_WP_CRON is true: Reminder emails will NOT send automatically unless a system cron calls wp-cron.php every 15 minutes.', 'woocommerce-jetpack' ),
				);
			}
			if ( ! empty( $store_ctx['is_blocks_checkout'] ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Checkout Block detected: Cart Abandonment captures billing emails via Classic Checkout JS hooks and will not capture typed emails on the WooCommerce Checkout Block.', 'woocommerce-jetpack' ),
				);
			}
			if ( empty( $store_ctx['has_smtp_plugin'] ) ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => __( 'No dedicated SMTP plugin detected (such as WP Mail SMTP or FluentSMTP). Server PHP mail() often lands in spam; configure SMTP for reliable recovery delivery.', 'woocommerce-jetpack' ),
				);
			}
			if ( 'day' === $unit && $tpl_enabled ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Current delay unit is "Day": Booster\'s cron only checks carts captured today (UTC), so a 1-day delay crosses midnight and never sends. Switch to Minutes or Hours.', 'woocommerce-jetpack' ),
				);
			}
			if ( $queue['pending_today'] > 0 ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => sprintf(
						/* translators: %d: Number of carts captured today */
						__( '%d unrecovered checkout cart(s) captured today (UTC) could receive reminders within 15 minutes of publishing.', 'woocommerce-jetpack' ),
						$queue['pending_today']
					),
				);
			}
			if ( 'No Discount' === (string) $raw_disc ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Legacy setting value "No Discount" detected; saving from Guided View automatically repairs this to "no" so bogus coupons are not generated.', 'woocommerce-jetpack' ),
				);
			} elseif ( ! $is_elite && in_array( $disc, array( 'percent', 'fixed_cart' ), true ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => sprintf(
						/* translators: %s: saved coupon discount type */
						__( 'Saved Elite recovery coupon (%s) still exists in your database for this step and can apply in Classic runtime unless cleared in Classic Tools or Booster Elite.', 'woocommerce-jetpack' ),
						$disc
					),
				);
			}

			$def_body = "Hi %customer_name%,<p>We noticed you started checking out and left a few items behind.</p><p><a href=\"%checkout_link%\">Resume your checkout</a> or reply to %admin_email% if you need a hand.</p>";

			return array(
				'slug'        => self::slug(),
				'edition'     => WCJ_V10_Store::edition(),
				'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=emails_and_misc&section=cart_abandonment&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				'live'        => array(
					'template_index'    => $i,
					'enabled'           => $enabled_live,
					'template_enabled'  => $tpl_enabled,
					'trigger_time'      => $raw_delay,
					'trigger_time_type' => $unit,
					'subject'           => (string) self::snap_val( $snap, 'wcj_ca_email_template_subject_' . $i, 'Is there anything we can help you?' ),
					'body'              => (string) self::snap_val( $snap, 'wcj_ca_email_template_body_' . $i, $def_body ),
					'from_name'         => '' !== (string) $from_name_raw ? (string) $from_name_raw : $default_from_name,
					'from_email'        => '' !== (string) $from_email_raw ? (string) $from_email_raw : $default_from_email,
					'discount_type'     => $is_elite ? $disc : 'no',
					'discount_amount'   => $is_elite ? (string) self::snap_val( $snap, 'wcj_ca_email_discount_amount_' . $i, '' ) : '',
					'auto_apply'        => $is_elite && 'yes' === (string) self::snap_val( $snap, 'wcj_ca_auto_apply_coupon_' . $i, 'no' ),
					'total_templates'   => (string) $total,
				),
				'meta'        => array(
					'is_elite'       => $is_elite,
					'is_pro'         => self::is_pro(),
					'templates_nav'  => $templates_nav,
					'pending_today'  => $queue['pending_today'],
					'total_rows'     => $queue['total_rows'],
					'admin_email'    => $default_from_email,
					'store_name'     => $store_ctx['store_defaults']['name'] ?? 'Booster Store',
					'product_name'   => $store_ctx['product']['name'] ?? 'Sample Product',
					'checkout_url'   => $store_ctx['checkout_url'] ?? home_url( '/checkout/' ),
					'verify_url'     => admin_url( 'admin.php?page=wcj-tools&tab=cart_abandonment&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) ),
					'verify_label'   => __( 'Open Cart Abandonment Report', 'woocommerce-jetpack' ),
					'diagnostics'    => $diagnostics,
				),
			);
		}

		/**
		 * Sanitize draft values with strict wp_kses_post on email HTML and Elite-only gate enforcement.
		 *
		 * @param array $draft Raw draft input.
		 * @param array $live  Current live state.
		 * @return array<string, mixed>
		 */
		public static function sanitize_draft( $draft, $live ) {
			$is_elite = self::is_elite();
			$i        = self::resolve_index( $draft );

			$unit = $live['trigger_time_type'];
			if ( array_key_exists( 'trigger_time_type', $draft ) ) {
				$cand = sanitize_key( (string) $draft['trigger_time_type'] );
				$unit = in_array( $cand, array( 'minute', 'hour', 'day' ), true ) ? $cand : $live['trigger_time_type'];
			}

			$disc_type = $live['discount_type'];
			if ( $is_elite && array_key_exists( 'discount_type', $draft ) ) {
				$cand_d    = sanitize_key( (string) $draft['discount_type'] );
				$disc_type = in_array( $cand_d, array( 'no', 'percent', 'fixed_cart' ), true ) ? $cand_d : 'no';
			}

			$str = static function ( $k, $html = false ) use ( $draft, $live ) {
				if ( ! array_key_exists( $k, $draft ) || (string) $draft[ $k ] === (string) $live[ $k ] ) {
					return $live[ $k ];
				}
				return $html ? wp_kses_post( (string) $draft[ $k ] ) : sanitize_text_field( (string) $draft[ $k ] );
			};

			return array(
				'template_index'    => $i,
				'enabled'           => array_key_exists( 'enabled', $draft ) ? ! empty( $draft['enabled'] ) : ! empty( $live['enabled'] ),
				'template_enabled'  => array_key_exists( 'template_enabled', $draft ) ? ! empty( $draft['template_enabled'] ) : ! empty( $live['template_enabled'] ),
				'trigger_time'      => ( ! array_key_exists( 'trigger_time', $draft ) || (string) $draft['trigger_time'] === (string) $live['trigger_time'] )
					? $live['trigger_time']
					: (string) max( 0, absint( $draft['trigger_time'] ) ),
				'trigger_time_type' => $unit,
				'subject'           => $str( 'subject' ),
				'body'              => $str( 'body', true ),
				'from_name'         => $is_elite ? $str( 'from_name' ) : $live['from_name'],
				'from_email'        => $is_elite
					? ( ( ! array_key_exists( 'from_email', $draft ) || (string) $draft['from_email'] === (string) $live['from_email'] ) ? $live['from_email'] : sanitize_email( (string) $draft['from_email'] ) )
					: $live['from_email'],
				'discount_type'     => $is_elite ? $disc_type : 'no',
				'discount_amount'   => $is_elite
					? ( ( ! array_key_exists( 'discount_amount', $draft ) || (string) $draft['discount_amount'] === (string) $live['discount_amount'] )
						? $live['discount_amount']
						: ( '' === trim( (string) $draft['discount_amount'] ) ? '' : (string) max( 0.0, (float) $draft['discount_amount'] ) ) )
					: '',
				'auto_apply'        => $is_elite && ( array_key_exists( 'auto_apply', $draft ) ? ! empty( $draft['auto_apply'] ) : ! empty( $live['auto_apply'] ) ),
				'total_templates'   => $is_elite && array_key_exists( 'total_templates', $draft )
					? (string) max( 1, min( 5, absint( $draft['total_templates'] ) ) )
					: (string) $live['total_templates'],
			);
		}

		/**
		 * Enforce positive non-day timing, valid subject/body, and valid Elite sender email & coupon math.
		 *
		 * @param array $draft        Sanitized draft.
		 * @param array $live         Live state.
		 * @param array $changed_keys Changed diff keys.
		 * @return true|WP_Error
		 */
		public static function validate_publish( $draft, $live = array(), $changed_keys = array() ) {
			$is_elite    = self::is_elite();
			$changed_map = array_flip( (array) $changed_keys );

			if ( $is_elite && isset( $changed_map['from_email'] ) && ! is_email( (string) $draft['from_email'] ) ) {
				return new WP_Error(
					'wcj_v10_ca_bad_email',
					__( 'Enter a valid Sender Email Address before publishing.', 'woocommerce-jetpack' ),
					array( 'status' => 400 )
				);
			}

			if ( empty( $draft['enabled'] ) || empty( $draft['template_enabled'] ) ) {
				return true;
			}

			if ( '' === trim( (string) $draft['subject'] ) || '' === trim( wp_strip_all_tags( (string) $draft['body'] ) ) ) {
				return new WP_Error(
					'wcj_v10_ca_empty_content',
					__( 'Reminder email needs both a Subject Line and Email Body before going live.', 'woocommerce-jetpack' ),
					array( 'status' => 400 )
				);
			}

			$delay = (int) $draft['trigger_time'];
			if ( $delay < 1 ) {
				return new WP_Error(
					'wcj_v10_ca_bad_delay',
					__( 'Send delay must be at least 1 minute.', 'woocommerce-jetpack' ),
					array( 'status' => 400 )
				);
			}

			if ( 'day' === $draft['trigger_time_type'] && ( isset( $changed_map['trigger_time_type'] ) || isset( $changed_map['template_enabled'] ) || empty( $live['template_enabled'] ) ) ) {
				return new WP_Error(
					'wcj_v10_ca_day_trap',
					__( '"Day" delays never send because Booster scans carts captured on the current UTC date only. Use Minutes or Hours (for example 60 minutes or 4 hours).', 'woocommerce-jetpack' ),
					array( 'status' => 400 )
				);
			}

			if ( $is_elite && in_array( $draft['discount_type'], array( 'percent', 'fixed_cart' ), true ) ) {
				$amt = (float) $draft['discount_amount'];
				if ( $amt <= 0 ) {
					return new WP_Error(
						'wcj_v10_ca_bad_coupon',
						__( 'Enter a Coupon Amount greater than 0 for the selected discount type.', 'woocommerce-jetpack' ),
						array( 'status' => 400 )
					);
				}
				if ( 'percent' === $draft['discount_type'] && $amt > 100 ) {
					return new WP_Error(
						'wcj_v10_ca_bad_coupon_pct',
						__( 'Percentage coupon discount cannot exceed 100%.', 'woocommerce-jetpack' ),
						array( 'status' => 400 )
					);
				}
			}

			return true;
		}

		/**
		 * Project deterministic timeline + rendered email card preview.
		 *
		 * @param array $live    Live state.
		 * @param array $draft   Draft state.
		 * @param array $context Preview context.
		 * @param array $meta    Metadata.
		 * @return array{before: array, after: array}
		 */
		public static function compute_preview( $live, $draft, $context, $meta ) {
			$is_elite = ! empty( $meta['is_elite'] );
			$show_raw = ! empty( $context['ca_show_raw'] );
			$chk_url  = (string) ( $meta['checkout_url'] ?? home_url( '/checkout/' ) );
			$adm_mail = (string) ( $meta['admin_email'] ?? 'admin@example.com' );

			$project = static function ( $cfg ) use ( $is_elite, $show_raw, $chk_url, $adm_mail ) {
				$active     = ! empty( $cfg['enabled'] ) && ! empty( $cfg['template_enabled'] );
				$delay_num  = max( 1, (int) $cfg['trigger_time'] );
				$unit       = (string) $cfg['trigger_time_type'];
				$unit_label = 'minute' === $unit ? 'min' : ( 'hour' === $unit ? 'hr' : 'day (never fires)' );
				$delay_txt  = 'T + ' . $delay_num . ' ' . $unit_label;

				$coupon_str = '';
				if ( $is_elite && 'no' !== $cfg['discount_type'] && (float) $cfg['discount_amount'] > 0 ) {
					$coupon_str = 'CA-SAMPLE10';
				}

				$raw_body = (string) $cfg['body'];
				if ( $show_raw ) {
					$rendered = $raw_body;
				} else {
					$restore_link = add_query_arg( 'wcj_restore_ac_token', 'SAMPLE_TOKEN', $chk_url );
					$rendered     = str_replace(
						array( '%customer_name%', '%checkout_link%', '%coupon_code%', '%admin_email%' ),
						array( 'Alex Morgan', $restore_link, $coupon_str, $adm_mail ),
						$raw_body
					);
				}

				$disc_badge_amt = is_numeric( $cfg['discount_amount'] )
					? (string) (float) $cfg['discount_amount']
					: trim( (string) $cfg['discount_amount'] );

				return array(
					'active'       => $active,
					'delay_text'   => $delay_txt,
					'from_line'    => trim( (string) $cfg['from_name'] ) . ' <' . trim( (string) $cfg['from_email'] ) . '>',
					'subject'      => (string) $cfg['subject'],
					'body_html'    => wp_kses_post( $rendered ),
					'has_coupon'   => '' !== $coupon_str,
					'coupon_badge' => 'percent' === $cfg['discount_type']
						? $disc_badge_amt . '% OFF (ca-xxxxxxxx)'
						: ( 'fixed_cart' === $cfg['discount_type'] ? $disc_badge_amt . ' OFF (ca-xxxxxxxx)' : '' ),
				);
			};

			return array(
				'before' => $project( $live ),
				'after'  => $project( $draft ),
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
			$map      = array_flip( (array) $changed_keys );
			$is_elite = self::is_elite();
			$i        = (int) $draft['template_index'];
			$writes   = array();

			if ( isset( $map['enabled'] ) ) {
				$writes['wcj_cart_abandonment_enabled'] = ! empty( $draft['enabled'] ) ? 'yes' : 'no';
			}
			if ( isset( $map['template_enabled'] ) ) {
				$writes[ 'wcj_ca_email_template_enabled_' . $i ] = ! empty( $draft['template_enabled'] ) ? 'yes' : 'no';
			}
			if ( isset( $map['subject'] ) ) {
				$writes[ 'wcj_ca_email_template_subject_' . $i ] = $draft['subject'];
			}
			if ( isset( $map['body'] ) ) {
				$writes[ 'wcj_ca_email_template_body_' . $i ] = $draft['body'];
			}
			if ( isset( $map['trigger_time'] ) ) {
				$writes[ 'wcj_ca_email_trigger_time_' . $i ] = $draft['trigger_time'];
			}
			if ( isset( $map['trigger_time_type'] ) ) {
				$writes[ 'wcj_ca_email_trigger_time_type_' . $i ] = $draft['trigger_time_type'];
			}

			// Materialize required cron fields whenever enabling an unsaved template so WP-Cron never reads null headers or 'No Discount' junk coupons!
			if ( ! empty( $draft['enabled'] ) && ! empty( $draft['template_enabled'] ) ) {
				$ensure = array(
					'wcj_cart_abandonment_email_from_name'   => $draft['from_name'],
					'wcj_cart_abandonment_email_from_email'  => $draft['from_email'],
					'wcj_ca_email_template_subject_' . $i    => $draft['subject'],
					'wcj_ca_email_template_body_' . $i       => $draft['body'],
					'wcj_ca_email_trigger_time_' . $i        => $draft['trigger_time'],
					'wcj_ca_email_trigger_time_type_' . $i   => $draft['trigger_time_type'],
				);
				foreach ( $ensure as $ok => $val ) {
					if ( empty( $snapshot[ $ok ]['exists'] ) && ! isset( $writes[ $ok ] ) ) {
						$writes[ $ok ] = $val;
					}
				}
				// Always normalize legacy 'No Discount' or absent discount_type cleanly on publish!
				$disc_key = 'wcj_ca_email_discount_type_' . $i;
				$cur_disc = isset( $snapshot[ $disc_key ] ) && ! empty( $snapshot[ $disc_key ]['exists'] ) ? WCJ_V10_Store::state_value( $snapshot[ $disc_key ] ) : null;
				if ( ! isset( $writes[ $disc_key ] ) && ( null === $cur_disc || ! in_array( (string) $cur_disc, array( 'no', 'percent', 'fixed_cart' ), true ) ) ) {
					$writes[ $disc_key ] = $is_elite ? $draft['discount_type'] : 'no';
				}
			}

			if ( $is_elite ) {
				if ( isset( $map['from_name'] ) ) {
					$writes['wcj_cart_abandonment_email_from_name'] = $draft['from_name'];
				}
				if ( isset( $map['from_email'] ) ) {
					$writes['wcj_cart_abandonment_email_from_email'] = $draft['from_email'];
				}
				if ( isset( $map['discount_type'] ) || ( ! empty( $draft['enabled'] ) && ! empty( $draft['template_enabled'] ) && empty( $snapshot[ 'wcj_ca_email_discount_type_' . $i ]['exists'] ) ) ) {
					$writes[ 'wcj_ca_email_discount_type_' . $i ] = $draft['discount_type'];
				}
				if ( isset( $map['discount_amount'] ) ) {
					$writes[ 'wcj_ca_email_discount_amount_' . $i ] = $draft['discount_amount'];
				}
				if ( isset( $map['auto_apply'] ) ) {
					$writes[ 'wcj_ca_auto_apply_coupon_' . $i ] = ! empty( $draft['auto_apply'] ) ? 'yes' : 'no';
				}
				if ( isset( $map['total_templates'] ) ) {
					$writes['wcj_ca_email_template_total_number'] = (int) $draft['total_templates'];
				}
			}

			return $writes;
		}

		/**
		 * Emergency shutoff immediately halts automated recovery emails.
		 *
		 * @param array<string, array> $snapshot Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function emergency_off_writes( $snapshot = array() ) {
			if ( 'yes' !== (string) self::snap_val( $snapshot, 'wcj_cart_abandonment_enabled', 'no' ) ) {
				return array();
			}
			return array(
				'wcj_cart_abandonment_enabled' => 'no',
			);
		}
	}

endif;

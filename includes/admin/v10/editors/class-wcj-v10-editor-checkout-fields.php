<?php
/**
 * Booster True V10 - Guided Editor: Checkout Custom Fields.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Editor_Checkout_Fields' ) ) :

	/**
	 * Checkout Custom Fields guided editor with Checkout Blocks compatibility awareness.
	 */
	class WCJ_V10_Editor_Checkout_Fields extends WCJ_V10_Editor_Base {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return 'checkout_custom_fields';
		}

		/**
		 * Relative JS module path.
		 *
		 * @return string
		 */
		public static function script() {
			return '/assets/js/admin/v10-editors/checkout-fields.js';
		}

		/**
		 * Context keys accepted by this editor.
		 *
		 * @return array<int, string>
		 */
		public static function context_keys() {
			return array( 'field_index' );
		}

		/**
		 * Primary Classic module section slug.
		 *
		 * @return string
		 */
		public static function primary_classic_section() {
			return 'checkout_custom_fields';
		}

		/**
		 * Required Booster module IDs for authorization.
		 *
		 * @return array<int, string>
		 */
		public static function required_module_ids() {
			return array( 'checkout_custom_fields' );
		}

		/**
		 * Summary card metadata for Command Center.
		 *
		 * @return array<string, string>
		 */
		public static function summary() {
			return array(
				'slug'        => self::slug(),
				'title'       => __( 'Checkout Custom Fields', 'woocommerce-jetpack' ),
				'description' => __( 'Add custom delivery, gift, or order inputs at checkout with Blocks/Classic checks.', 'woocommerce-jetpack' ),
				'engine'      => __( 'Checkout Form Preview', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Classic section IDs handled by this guided editor.
		 *
		 * @return array<string, string>
		 */
		public static function section_map() {
			return array(
				'checkout_custom_fields' => self::slug(),
			);
		}

		/**
		 * Resolve safe field index (always 1 in Free; 1..total_number in Paid).
		 *
		 * @param array $context Editor context.
		 * @return int
		 */
		private static function resolve_index( $context = array() ) {
			if ( ! self::is_pro() ) {
				return 1;
			}
			$total = max( 1, (int) get_option( 'wcj_checkout_custom_fields_total_number', 1 ) );
			$raw   = isset( $context['field_index'] ) ? absint( $context['field_index'] ) : 1;
			return max( 1, min( $total, $raw ) );
		}

		/**
		 * Authoritative list of wp_options keys read/written by this editor.
		 *
		 * @param array $context Editor context (field_index).
		 * @return array<int, string>
		 */
		public static function owned_options( $context = array() ) {
			$i = self::resolve_index( $context );
			return array(
				'wcj_checkout_custom_fields_enabled',
				'wcj_checkout_custom_fields_email_all_to_admin',
				'wcj_checkout_custom_fields_email_all_to_customer',
				'wcj_checkout_custom_fields_add_to_order_received',
				'wcj_checkout_custom_field_enabled_' . $i,
				'wcj_checkout_custom_field_type_' . $i,
				'wcj_checkout_custom_field_required_' . $i,
				'wcj_checkout_custom_field_label_' . $i,
				'wcj_checkout_custom_field_placeholder_' . $i,
				'wcj_checkout_custom_field_section_' . $i,
				'wcj_checkout_custom_field_select_options_' . $i,
				'wcj_checkout_custom_field_description_' . $i,
				'wcj_checkout_custom_field_priority_' . $i,
				'wcj_checkout_custom_field_class_' . $i,
				'wcj_checkout_custom_field_clear_' . $i,
				'wcj_checkout_custom_field_checkbox_default_' . $i,
				'wcj_checkout_custom_field_customer_meta_fields_' . $i,
			);
		}

		/**
		 * Plain-english label map for Review & Publish diffs.
		 *
		 * @return array<string, string>
		 */
		public static function diff_labels() {
			return array(
				'enabled'            => __( 'Checkout Custom Fields Module', 'woocommerce-jetpack' ),
				'field_enabled'      => __( 'Checkout Field Active', 'woocommerce-jetpack' ),
				'field_label'        => __( 'Field Label', 'woocommerce-jetpack' ),
				'field_type'         => __( 'Field Type', 'woocommerce-jetpack' ),
				'field_section'      => __( 'Checkout Section', 'woocommerce-jetpack' ),
				'field_required'     => __( 'Required Field', 'woocommerce-jetpack' ),
				'field_placeholder'  => __( 'Field Placeholder', 'woocommerce-jetpack' ),
				'select_options'     => __( 'Dropdown / Radio Choices', 'woocommerce-jetpack' ),
				'field_description'  => __( 'Helper Description', 'woocommerce-jetpack' ),
				'email_to_admin'     => __( 'Show in Admin Order Emails', 'woocommerce-jetpack' ),
				'email_to_customer'  => __( 'Show in Customer Order Emails', 'woocommerce-jetpack' ),
				'show_thankyou'      => __( 'Show on Thank You Page', 'woocommerce-jetpack' ),
				'field_priority'     => __( 'Field Position Priority', 'woocommerce-jetpack' ),
				'field_clear'        => __( 'Full-Width Clear Row', 'woocommerce-jetpack' ),
				'customer_meta'      => __( 'Save to Customer Profile Meta', 'woocommerce-jetpack' ),
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
					'impact'           => __( 'Renders custom fields at checkout and saves answers permanently onto orders.', 'woocommerce-jetpack' ),
				),
				'field_enabled'     => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Shows or hides this custom field on your checkout page.', 'woocommerce-jetpack' ),
				),
				'field_label'       => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Displayed above the field at checkout and saved as the label on orders.', 'woocommerce-jetpack' ),
				),
				'field_required'    => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Shoppers cannot place an order without filling this field (avoid requiring inside optional Shipping sections).', 'woocommerce-jetpack' ),
				),
				'field_type'        => array(
					'goes_live' => true,
					'impact'    => __( 'Changes the checkout input control type shown to shoppers.', 'woocommerce-jetpack' ),
				),
				'field_section'     => array(
					'goes_live' => true,
					'impact'    => __( 'Changes which checkout section holds the field (Blocks renders in Additional information).', 'woocommerce-jetpack' ),
				),
				'select_options'    => array(
					'goes_live' => true,
					'impact'    => __( 'Updates selectable choices shown to shoppers at checkout.', 'woocommerce-jetpack' ),
				),
				'field_placeholder' => array(
					'goes_live' => true,
					'impact'    => __( 'Shows hint text inside empty text inputs on Classic Checkout.', 'woocommerce-jetpack' ),
				),
				'field_description' => array(
					'goes_live' => false,
					'impact'    => __( 'Shows small helper copy beneath the field on Classic Checkout.', 'woocommerce-jetpack' ),
				),
				'email_to_admin'    => array(
					'goes_live' => false,
					'impact'    => __( 'Includes shopper answers in Admin New Order emails.', 'woocommerce-jetpack' ),
				),
				'email_to_customer' => array(
					'goes_live' => false,
					'impact'    => __( 'Includes shopper answers in Customer Order emails.', 'woocommerce-jetpack' ),
				),
				'show_thankyou'     => array(
					'goes_live' => false,
					'impact'    => __( 'Displays submitted answers on Order Received & My Account views.', 'woocommerce-jetpack' ),
				),
				'field_priority'    => array(
					'goes_live' => false,
					'impact'    => __( 'Controls field sort order on Classic Checkout.', 'woocommerce-jetpack' ),
				),
				'field_clear'       => array(
					'goes_live' => false,
					'impact'    => __( 'Adds a CSS clear break after the field row on Classic Checkout.', 'woocommerce-jetpack' ),
				),
				'customer_meta'     => array(
					'goes_live' => false,
					'impact'    => __( 'Pre-fills shopper answers onto their WordPress user profile.', 'woocommerce-jetpack' ),
				),
			);
		}

		/**
		 * Supported Classic and Blocks field types.
		 *
		 * @return array<int, string>
		 */
		private static function allowed_types() {
			return array( 'text', 'textarea', 'select', 'checkbox', 'radio', 'datepicker', 'number', 'email', 'tel' );
		}

		/**
		 * Count Classic-only rules or extra saved fields #2..#N.
		 *
		 * @param int $active_idx Active field index.
		 * @return array{classic_rules: int, extra_fields: int}
		 */
		private static function detect_hidden( $active_idx ) {
			$total  = max( 1, (int) get_option( 'wcj_checkout_custom_fields_total_number', 1 ) );
			$extras = 0;
			$limit  = max( $total, 5 );
			for ( $n = 2; $n <= $limit; $n++ ) {
				if ( $n === $active_idx ) {
					continue;
				}
				if ( 'yes' === get_option( 'wcj_checkout_custom_field_enabled_' . $n, 'no' ) || '' !== trim( (string) get_option( 'wcj_checkout_custom_field_label_' . $n, '' ) ) ) {
					++$extras;
				}
			}

			$rules = 0;
			foreach ( array( 'categories_ex_', 'categories_in_', 'products_ex_', 'products_in_' ) as $cond ) {
				if ( ! empty( get_option( 'wcj_checkout_custom_field_' . $cond . $active_idx, array() ) ) ) {
					++$rules;
				}
			}
			if ( (float) get_option( 'wcj_checkout_custom_field_min_cart_amount_' . $active_idx, 0 ) > 0 || (float) get_option( 'wcj_checkout_custom_field_max_cart_amount_' . $active_idx, 0 ) > 0 ) {
				++$rules;
			}
			if ( 'yes' === get_option( 'wcj_checkout_custom_field_select_select2_' . $active_idx, 'no' ) ) {
				++$rules;
			}

			return array(
				'classic_rules' => $rules,
				'extra_fields'  => $extras,
			);
		}

		/**
		 * Load live state, metadata, and compatibility diagnostics.
		 *
		 * @param array $context   Request params (field_index).
		 * @param array $store_ctx Store context.
		 * @return array<string, mixed>
		 */
		public static function load_state( $context, $store_ctx ) {
			$nonce      = wp_create_nonce( 'wcj-cat-nonce' );
			$is_pro     = self::is_pro();
			$i          = self::resolve_index( $context );
			$total_num  = max( 1, (int) get_option( 'wcj_checkout_custom_fields_total_number', 1 ) );
			$is_blocks  = ! empty( $store_ctx['is_blocks_checkout'] );
			$hidden     = self::detect_hidden( $i );
			$cur_type   = (string) get_option( 'wcj_checkout_custom_field_type_' . $i, 'text' );
			$cur_sec    = (string) get_option( 'wcj_checkout_custom_field_section_' . $i, 'billing' );

			$fields_nav = array();
			$max_nav    = $is_pro ? $total_num : 1;
			for ( $n = 1; $n <= $max_nav; $n++ ) {
				$lbl          = trim( (string) get_option( 'wcj_checkout_custom_field_label_' . $n, '' ) );
				$fields_nav[] = array(
					'index' => $n,
					'label' => '' !== $lbl ? $lbl : sprintf( __( 'Field #%d', 'woocommerce-jetpack' ), $n ),
				);
			}

			$diagnostics = array();
			if ( ! empty( $store_ctx['third_party_checkout'] ) && is_array( $store_ctx['third_party_checkout'] ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => sprintf(
						/* translators: %s: Third-party checkout plugin name(s) */
						__( 'Third-party checkout builder detected (%s). After publishing, click "Check My Live Checkout" to verify field placement on the live form.', 'woocommerce-jetpack' ),
						implode( ', ', array_map( 'strval', $store_ctx['third_party_checkout'] ) )
					),
				);
			}
			if ( $is_blocks ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => __( 'Your checkout page uses the WooCommerce Checkout Block. Supported types (Text, Select, Checkbox, Radio) appear under Additional information.', 'woocommerce-jetpack' ),
				);
				if ( ! in_array( $cur_type, array( 'text', 'select', 'checkbox', 'radio' ), true ) ) {
					$diagnostics[] = array(
						'level' => 'warning',
						'text'  => sprintf(
							/* translators: %s: Checkout field type */
							__( 'Field type "%s" is Classic Checkout only and will not appear on your Block Checkout page.', 'woocommerce-jetpack' ),
							$cur_type
						),
					);
				}
			}
			if ( 'shipping' === $cur_sec && ! $is_blocks ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Shipping fields are hidden when the cart only contains virtual products or "Ship to a different address" is unchecked. Avoid making Shipping fields required unless needed.', 'woocommerce-jetpack' ),
				);
			}
			if ( $hidden['extra_fields'] > 0 && ! $is_pro ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => sprintf(
						/* translators: %d: Number of extra fields saved in DB */
						__( '%d extra field(s) from Elite/Classic are saved in your database and preserved untouched (Booster Free activates Field #1).', 'woocommerce-jetpack' ),
						$hidden['extra_fields']
					),
				);
			}
			if ( $hidden['classic_rules'] > 0 ) {
				$diagnostics[] = array(
					'level'        => 'warning',
					'text'         => sprintf(
						/* translators: %d: Count of Classic-only conditions */
						__( '%d advanced Classic rule(s) (cart amount limits, product/category conditions, or Select2) are active and preserved untouched.', 'woocommerce-jetpack' ),
						$hidden['classic_rules']
					),
					'action_label' => __( 'View in Classic', 'woocommerce-jetpack' ),
					'action_url'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=cart_and_checkout&section=checkout_custom_fields&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				);
			}

			return array(
				'slug'        => self::slug(),
				'edition'     => WCJ_V10_Store::edition(),
				'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=cart_and_checkout&section=checkout_custom_fields&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				'live'        => array(
					'field_index'       => $i,
					'enabled'           => 'yes' === get_option( 'wcj_checkout_custom_fields_enabled', 'no' ),
					'field_enabled'     => 'yes' === get_option( 'wcj_checkout_custom_field_enabled_' . $i, 'no' ),
					'field_label'       => (string) get_option( 'wcj_checkout_custom_field_label_' . $i, '' ),
					'field_type'        => '' !== $cur_type ? $cur_type : 'text',
					'field_section'     => '' !== $cur_sec ? $cur_sec : 'billing',
					'field_required'    => 'yes' === get_option( 'wcj_checkout_custom_field_required_' . $i, 'no' ),
					'field_placeholder' => (string) get_option( 'wcj_checkout_custom_field_placeholder_' . $i, '' ),
					'select_options'    => (string) get_option( 'wcj_checkout_custom_field_select_options_' . $i, '' ),
					'field_description' => (string) get_option( 'wcj_checkout_custom_field_description_' . $i, '' ),
					'email_to_admin'    => 'yes' === get_option( 'wcj_checkout_custom_fields_email_all_to_admin', 'yes' ),
					'email_to_customer' => 'yes' === get_option( 'wcj_checkout_custom_fields_email_all_to_customer', 'yes' ),
					'show_thankyou'     => 'yes' === get_option( 'wcj_checkout_custom_fields_add_to_order_received', 'yes' ),
					'field_priority'    => (string) get_option( 'wcj_checkout_custom_field_priority_' . $i, '' ),
					'field_clear'       => 'yes' === get_option( 'wcj_checkout_custom_field_clear_' . $i, 'yes' ),
					'customer_meta'     => 'yes' === get_option( 'wcj_checkout_custom_field_customer_meta_fields_' . $i, 'yes' ),
				),
				'meta'        => array(
					'is_pro'             => $is_pro,
					'is_blocks_checkout' => $is_blocks,
					'fields_nav'         => $fields_nav,
					'verify_url'         => $store_ctx['checkout_url'],
					'verify_label'       => __( 'Check My Live Checkout', 'woocommerce-jetpack' ),
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
			$idx  = self::resolve_index( $draft );
			$type = $live['field_type'];
			if ( array_key_exists( 'field_type', $draft ) && (string) $draft['field_type'] !== (string) $live['field_type'] ) {
				$cand = sanitize_key( (string) $draft['field_type'] );
				$type = in_array( $cand, self::allowed_types(), true ) ? $cand : $live['field_type'];
			}
			$sec = $live['field_section'];
			if ( array_key_exists( 'field_section', $draft ) && (string) $draft['field_section'] !== (string) $live['field_section'] ) {
				$cand_sec = sanitize_key( (string) $draft['field_section'] );
				$sec      = in_array( $cand_sec, array( 'billing', 'shipping', 'order', 'account' ), true ) ? $cand_sec : $live['field_section'];
			}

			$str = static function ( $k, $textarea = false ) use ( $draft, $live ) {
				if ( ! array_key_exists( $k, $draft ) || (string) $draft[ $k ] === (string) $live[ $k ] ) {
					return $live[ $k ];
				}
				return $textarea ? sanitize_textarea_field( (string) $draft[ $k ] ) : sanitize_text_field( (string) $draft[ $k ] );
			};

			return array(
				'field_index'       => $idx,
				'enabled'           => array_key_exists( 'enabled', $draft ) ? ! empty( $draft['enabled'] ) : ! empty( $live['enabled'] ),
				'field_enabled'     => array_key_exists( 'field_enabled', $draft ) ? ! empty( $draft['field_enabled'] ) : ! empty( $live['field_enabled'] ),
				'field_label'       => $str( 'field_label' ),
				'field_type'        => $type,
				'field_section'     => $sec,
				'field_required'    => array_key_exists( 'field_required', $draft ) ? ! empty( $draft['field_required'] ) : ! empty( $live['field_required'] ),
				'field_placeholder' => $str( 'field_placeholder' ),
				'select_options'    => $str( 'select_options', true ),
				'field_description' => $str( 'field_description' ),
				'email_to_admin'    => array_key_exists( 'email_to_admin', $draft ) ? ! empty( $draft['email_to_admin'] ) : ! empty( $live['email_to_admin'] ),
				'email_to_customer' => array_key_exists( 'email_to_customer', $draft ) ? ! empty( $draft['email_to_customer'] ) : ! empty( $live['email_to_customer'] ),
				'show_thankyou'     => array_key_exists( 'show_thankyou', $draft ) ? ! empty( $draft['show_thankyou'] ) : ! empty( $live['show_thankyou'] ),
				'field_priority'    => $str( 'field_priority' ),
				'field_clear'       => array_key_exists( 'field_clear', $draft ) ? ! empty( $draft['field_clear'] ) : ! empty( $live['field_clear'] ),
				'customer_meta'     => array_key_exists( 'customer_meta', $draft ) ? ! empty( $draft['customer_meta'] ) : ! empty( $live['customer_meta'] ),
			);
		}

		/**
		 * Validate label, select/radio choices, and Checkout Blocks compatibility before publish.
		 *
		 * @param array $draft        Sanitized draft.
		 * @param array $live         Live state before publish.
		 * @param array $changed_keys Changed diff keys.
		 * @return true|WP_Error
		 */
		public static function validate_publish( $draft, $live = array(), $changed_keys = array() ) {
			unset( $live );
			if ( empty( $draft['enabled'] ) || empty( $draft['field_enabled'] ) ) {
				return true;
			}
			if ( '' === trim( (string) $draft['field_label'] ) ) {
				return new WP_Error(
					'wcj_v10_missing_cf_label',
					__( 'Enter a Field Label before publishing — shoppers need a clear label on checkout and order details.', 'woocommerce-jetpack' ),
					array( 'status' => 400 )
				);
			}
			if ( in_array( $draft['field_type'], array( 'select', 'radio' ), true ) ) {
				$opts = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $draft['select_options'] ) ) );
				if ( empty( $opts ) ) {
					return new WP_Error(
						'wcj_v10_missing_cf_options',
						__( 'Add at least one choice (one per line) for Select or Radio checkout fields.', 'woocommerce-jetpack' ),
						array( 'status' => 400 )
					);
				}
			}
			$changed_map         = array_flip( (array) $changed_keys );
			$blocks_rule_applies = empty( $changed_keys ) || isset( $changed_map['field_type'] ) || isset( $changed_map['field_enabled'] ) || isset( $changed_map['enabled'] );
			$checkout_id         = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : 0;
			$is_blocks           = ( $checkout_id > 0 && function_exists( 'has_block' ) ) ? has_block( 'woocommerce/checkout', $checkout_id ) : false;
			if ( $blocks_rule_applies && $is_blocks && ! in_array( $draft['field_type'], array( 'text', 'select', 'checkbox', 'radio' ), true ) ) {
				return new WP_Error(
					'wcj_v10_blocks_unsupported_field_type',
					__( 'WooCommerce Block Checkout only supports Text, Select, Checkbox, and Radio custom fields. Switch the field type to one of those so shoppers see it.', 'woocommerce-jetpack' ),
					array( 'status' => 400 )
				);
			}
			return true;
		}

		/**
		 * Project Before & After checkout section preview.
		 *
		 * @param array $live    Live state.
		 * @param array $draft   Draft state.
		 * @param array $context Context.
		 * @param array $meta    Metadata.
		 * @return array{before: array, after: array}
		 */
		public static function compute_preview( $live, $draft, $context, $meta ) {
			unset( $context );
			$is_blocks = ! empty( $meta['is_blocks_checkout'] );
			$project   = static function ( $cfg ) use ( $is_blocks ) {
				$active  = ! empty( $cfg['enabled'] ) && ! empty( $cfg['field_enabled'] );
				$choices = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $cfg['select_options'] ) ) ) );
				$sec     = $is_blocks ? 'order' : $cfg['field_section'];
				return array(
					'active'            => $active,
					'label'             => '' !== trim( (string) $cfg['field_label'] ) ? $cfg['field_label'] : __( 'Custom Checkout Field', 'woocommerce-jetpack' ),
					'type'              => $cfg['field_type'],
					'section'            => $sec,
					'configured_section' => '' !== (string) $cfg['field_section'] ? $cfg['field_section'] : 'billing',
					'required'           => ! empty( $cfg['field_required'] ),
					'placeholder'       => $cfg['field_placeholder'],
					'choices'           => $choices,
					'description'       => $cfg['field_description'],
					'email_to_admin'    => ! empty( $cfg['email_to_admin'] ),
					'email_to_customer' => ! empty( $cfg['email_to_customer'] ),
					'show_thankyou'     => ! empty( $cfg['show_thankyou'] ),
				);
			};

			return array(
				'before' => $project( $live ),
				'after'  => $project( $draft ),
			);
		}

		/**
		 * Map ONLY changed draft fields AND initialize missing core runtime keys on first field enable.
		 *
		 * @param array                $draft        Sanitized draft.
		 * @param array<int, string>   $changed_keys Changed diff keys.
		 * @param array<string, array> $snapshot     Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function build_option_writes( $draft, $changed_keys = array(), $snapshot = array() ) {
			if ( empty( $changed_keys ) ) {
				return array();
			}

			$changed = array_flip( $changed_keys );
			$i       = self::resolve_index( $draft );
			$writes  = array();
			$map     = array(
				'enabled'           => array( 'wcj_checkout_custom_fields_enabled', ! empty( $draft['enabled'] ) ? 'yes' : 'no' ),
				'email_to_admin'    => array( 'wcj_checkout_custom_fields_email_all_to_admin', ! empty( $draft['email_to_admin'] ) ? 'yes' : 'no' ),
				'email_to_customer' => array( 'wcj_checkout_custom_fields_email_all_to_customer', ! empty( $draft['email_to_customer'] ) ? 'yes' : 'no' ),
				'show_thankyou'     => array( 'wcj_checkout_custom_fields_add_to_order_received', ! empty( $draft['show_thankyou'] ) ? 'yes' : 'no' ),
				'field_enabled'     => array( 'wcj_checkout_custom_field_enabled_' . $i, ! empty( $draft['field_enabled'] ) ? 'yes' : 'no' ),
				'field_label'       => array( 'wcj_checkout_custom_field_label_' . $i, $draft['field_label'] ),
				'field_type'        => array( 'wcj_checkout_custom_field_type_' . $i, $draft['field_type'] ),
				'field_section'     => array( 'wcj_checkout_custom_field_section_' . $i, $draft['field_section'] ),
				'field_required'    => array( 'wcj_checkout_custom_field_required_' . $i, ! empty( $draft['field_required'] ) ? 'yes' : 'no' ),
				'field_placeholder' => array( 'wcj_checkout_custom_field_placeholder_' . $i, $draft['field_placeholder'] ),
				'select_options'    => array( 'wcj_checkout_custom_field_select_options_' . $i, $draft['select_options'] ),
				'field_description' => array( 'wcj_checkout_custom_field_description_' . $i, $draft['field_description'] ),
				'field_priority'    => array( 'wcj_checkout_custom_field_priority_' . $i, $draft['field_priority'] ),
				'field_clear'       => array( 'wcj_checkout_custom_field_clear_' . $i, ! empty( $draft['field_clear'] ) ? 'yes' : 'no' ),
				'customer_meta'     => array( 'wcj_checkout_custom_field_customer_meta_fields_' . $i, ! empty( $draft['customer_meta'] ) ? 'yes' : 'no' ),
			);

			foreach ( $map as $field => $pair ) {
				if ( isset( $changed[ $field ] ) ) {
					$writes[ $pair[0] ] = $pair[1];
				}
			}

			// Booster runtime reads several field_{i} keys with no fallback default; ensure core keys exist on activation.
			if ( ! empty( $draft['enabled'] ) && ! empty( $draft['field_enabled'] ) ) {
				$mandatory_defaults = array(
					'wcj_checkout_custom_field_enabled_' . $i  => 'yes',
					'wcj_checkout_custom_field_section_' . $i  => $draft['field_section'],
					'wcj_checkout_custom_field_type_' . $i     => $draft['field_type'],
					'wcj_checkout_custom_field_label_' . $i    => $draft['field_label'],
					'wcj_checkout_custom_field_required_' . $i => ! empty( $draft['field_required'] ) ? 'yes' : 'no',
					'wcj_checkout_custom_field_clear_' . $i    => ! empty( $draft['field_clear'] ) ? 'yes' : 'no',
					'wcj_checkout_custom_field_class_' . $i    => 'form-row-wide',
				);
				foreach ( $mandatory_defaults as $opt_key => $val ) {
					if ( ! array_key_exists( $opt_key, $writes ) && empty( $snapshot[ $opt_key ]['exists'] ) ) {
						$writes[ $opt_key ] = $val;
					}
				}
			}

			return $writes;
		}

		/**
		 * Emergency shutoff option writes.
		 *
		 * @param array<string, array> $snapshot Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function emergency_off_writes( $snapshot = array() ) {
			if ( 'yes' !== self::snap_val( $snapshot, 'wcj_checkout_custom_fields_enabled', 'no' ) ) {
				return array();
			}
			return array(
				'wcj_checkout_custom_fields_enabled' => 'no',
			);
		}
	}

endif;

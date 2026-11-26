<?php
/**
 * Booster True V10 - Guided Editor: PDF Invoicing & Packing Slips.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Editor_PDF_Invoicing' ) ) :

	/**
	 * PDF Invoicing guided editor supporting multiple document types and Tier-2 rules.
	 */
	class WCJ_V10_Editor_PDF_Invoicing extends WCJ_V10_Editor_Base {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return 'pdf_invoicing';
		}

		/**
		 * Relative JS module path.
		 *
		 * @return string
		 */
		public static function script() {
			return '/assets/js/admin/v10-editors/pdf-invoicing.js';
		}

		/**
		 * Context keys accepted by this editor.
		 *
		 * @return array<int, string>
		 */
		public static function context_keys() {
			return array( 'doc_type' );
		}

		/**
		 * Primary Classic module section slug.
		 *
		 * @return string
		 */
		public static function primary_classic_section() {
			return 'pdf_invoicing';
		}

		/**
		 * Required Booster module IDs for authorization.
		 *
		 * @return array<int, string>
		 */
		public static function required_module_ids() {
			return array( 'pdf_invoicing' );
		}

		/**
		 * Summary card metadata for Command Center.
		 *
		 * @return array<string, string>
		 */
		public static function summary() {
			return array(
				'slug'        => self::slug(),
				'title'       => __( 'PDF Invoicing & Packing Slips', 'woocommerce-jetpack' ),
				'description' => __( 'Brand your invoices with logo, colors, business details, and live PDF preview.', 'woocommerce-jetpack' ),
				'engine'      => __( 'Document Preview', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Classic section IDs handled by this guided editor.
		 *
		 * @return array<string, string>
		 */
		public static function section_map() {
			return array(
				'pdf_invoicing'           => self::slug(),
				'pdf_invoicing_numbering' => self::slug(),
				'pdf_invoicing_header'    => self::slug(),
				'pdf_invoicing_footer'    => self::slug(),
				'pdf_invoicing_templates' => self::slug(),
				'pdf_invoicing_styling'   => self::slug(),
				'pdf_invoicing_page'      => self::slug(),
				'pdf_invoicing_emails'    => self::slug(),
				'pdf_invoicing_display'   => self::slug(),
				'pdf_invoicing_advanced'  => self::slug(),
			);
		}

		/**
		 * Supported document types map.
		 *
		 * @return array<string, string>
		 */
		public static function doc_types() {
			return array(
				'invoice'          => __( 'Invoice', 'woocommerce-jetpack' ),
				'proforma_invoice' => __( 'Proforma Invoice', 'woocommerce-jetpack' ),
				'packing_slip'     => __( 'Packing Slip', 'woocommerce-jetpack' ),
				'credit_note'      => __( 'Credit Note', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Normalize requested document type slug.
		 *
		 * @param mixed $raw Raw doc_type.
		 * @return string
		 */
		private static function normalize_doc_type( $raw ) {
			$doc   = sanitize_key( (string) $raw );
			$valid = self::doc_types();
			return isset( $valid[ $doc ] ) ? $doc : 'invoice';
		}

		/**
		 * Authoritative list of wp_options keys read/written by this editor for a doc_type.
		 *
		 * @param array $context Editor context (doc_type).
		 * @return array<int, string>
		 */
		public static function owned_options( $context = array() ) {
			$doc_type = self::normalize_doc_type( isset( $context['doc_type'] ) ? $context['doc_type'] : 'invoice' );
			$prefix   = 'wcj_invoicing_' . $doc_type . '_';
			return array(
				'wcj_pdf_invoicing_enabled',
				$prefix . 'header_enabled',
				$prefix . 'header_image',
				$prefix . 'header_title_text',
				$prefix . 'header_text',
				$prefix . 'header_text_color',
				$prefix . 'header_line_color',
				$prefix . 'numbering_prefix',
				$prefix . 'numbering_suffix',
				$prefix . 'footer_enabled',
				$prefix . 'footer_text',
				$prefix . 'create_on',
				$prefix . 'attach_to_emails',
				$prefix . 'enabled_for_customers',
				$prefix . 'enabled_on_thankyou_page',
				$prefix . 'skip_zero_total',
			);
		}

		/**
		 * Plain-english label map for Review & Publish diffs.
		 *
		 * @return array<string, string>
		 */
		public static function diff_labels() {
			return array(
				'enabled'              => __( 'PDF Invoicing Module (All Documents)', 'woocommerce-jetpack' ),
				'header_enabled'       => __( 'Invoice Header', 'woocommerce-jetpack' ),
				'header_image'         => __( 'Header Logo URL', 'woocommerce-jetpack' ),
				'header_title'         => __( 'Document Title', 'woocommerce-jetpack' ),
				'header_text'          => __( 'Business Header Text', 'woocommerce-jetpack' ),
				'header_text_color'    => __( 'Header Text Color', 'woocommerce-jetpack' ),
				'header_line_color'    => __( 'Header Line Color', 'woocommerce-jetpack' ),
				'numbering_prefix'     => __( 'Document Prefix', 'woocommerce-jetpack' ),
				'numbering_suffix'     => __( 'Document Suffix', 'woocommerce-jetpack' ),
				'footer_text'          => __( 'Document Footer Text', 'woocommerce-jetpack' ),
				'create_on_completed'  => __( 'Auto-Create on Completed Order', 'woocommerce-jetpack' ),
				'create_on_processing' => __( 'Auto-Create on Processing Order', 'woocommerce-jetpack' ),
				'attach_completed'     => __( 'Attach to Completed Email', 'woocommerce-jetpack' ),
				'customer_my_account'  => __( 'Show Download in My Account', 'woocommerce-jetpack' ),
				'thankyou_page'        => __( 'Show Download on Thank You Page', 'woocommerce-jetpack' ),
				'skip_zero_total'      => __( 'Skip $0 / Free Orders', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Customer-facing impact grouping metadata for Review & Publish modal.
		 *
		 * @return array<string, array{goes_live: bool, impact: string}>
		 */
		public static function impact_groups() {
			return array(
				'enabled'              => array(
					'goes_live' => true,
					'impact'    => __( 'Enables or pauses PDF invoice generation across all WooCommerce orders.', 'woocommerce-jetpack' ),
				),
				'attach_completed'     => array(
					'goes_live' => true,
					'impact'    => __( 'Attaches generated PDF documents to customer Completed Order emails.', 'woocommerce-jetpack' ),
				),
				'customer_my_account'  => array(
					'goes_live' => true,
					'impact'    => __( 'Adds a PDF download button to customer My Account > Orders.', 'woocommerce-jetpack' ),
				),
				'thankyou_page'        => array(
					'goes_live' => true,
					'impact'    => __( 'Shows a PDF download link directly on the Order Received confirmation page.', 'woocommerce-jetpack' ),
				),
				'header_title'         => array(
					'goes_live' => true,
					'impact'    => __( 'Updates the customer-facing document title printed on PDFs.', 'woocommerce-jetpack' ),
				),
				'header_text'          => array(
					'goes_live' => true,
					'impact'    => __( 'Updates business details printed at the top of customer PDFs.', 'woocommerce-jetpack' ),
				),
				'header_image'         => array(
					'goes_live' => true,
					'impact'    => __( 'Updates the logo displayed in the header of customer PDFs.', 'woocommerce-jetpack' ),
				),
				'footer_text'          => array(
					'goes_live' => true,
					'impact'    => __( 'Updates the footer text printed at the bottom of customer PDFs.', 'woocommerce-jetpack' ),
				),
				'header_enabled'       => array(
					'goes_live' => false,
					'impact'    => __( 'Shows or hides the document header section.', 'woocommerce-jetpack' ),
				),
				'header_text_color'    => array(
					'goes_live' => false,
					'impact'    => __( 'Changes the accent color used on document headings and tables.', 'woocommerce-jetpack' ),
				),
				'header_line_color'    => array(
					'goes_live' => false,
					'impact'    => __( 'Changes the divider line accent color on the document.', 'woocommerce-jetpack' ),
				),
				'numbering_prefix'     => array(
					'goes_live' => false,
					'impact'    => __( 'Prepends a custom prefix to generated document numbers.', 'woocommerce-jetpack' ),
				),
				'numbering_suffix'     => array(
					'goes_live' => false,
					'impact'    => __( 'Appends a custom suffix to generated document numbers.', 'woocommerce-jetpack' ),
				),
				'create_on_completed'  => array(
					'goes_live' => false,
					'impact'    => __( 'Automatically generates this PDF when an order moves to Completed.', 'woocommerce-jetpack' ),
				),
				'create_on_processing' => array(
					'goes_live' => false,
					'impact'    => __( 'Automatically generates this PDF when an order moves to Processing.', 'woocommerce-jetpack' ),
				),
				'skip_zero_total'      => array(
					'goes_live' => false,
					'impact'    => __( 'Skips PDF generation for $0 or free orders.', 'woocommerce-jetpack' ),
				),
			);
		}

		/**
		 * Sanitize header image accepting either a valid HTTP(S) URL or Booster's relative document-root path.
		 *
		 * @param string $raw Raw input path or URL.
		 * @return string
		 */
		private static function sanitize_header_image( $raw ) {
			$trimmed = trim( (string) $raw );
			if ( '' === $trimmed ) {
				return '';
			}
			if ( wp_http_validate_url( $trimmed ) ) {
				return esc_url_raw( $trimmed );
			}
			// Booster resolves relative image paths against DOCUMENT_ROOT; preserve safe relative paths intact.
			if ( false === strpos( $trimmed, '..' ) && 1 === preg_match( '#^/?[a-zA-Z0-9/_\.\-\s]+$#', $trimmed ) ) {
				return sanitize_text_field( $trimmed );
			}
			return esc_url_raw( $trimmed );
		}

		/**
		 * Count Classic-only rules/templates active for this doc_type that aren't shown in Simple Setup.
		 *
		 * @param string $prefix    Option prefix.
		 * @param array  $create_on Stored create_on hooks.
		 * @param array  $emails    Stored attach_to_emails slugs.
		 * @return int
		 */
		private static function count_classic_only_rules( $prefix, $create_on, $emails ) {
			$count         = 0;
			$custom_tpl    = trim( (string) get_option( $prefix . 'template', '' ) );
			$known_hooks   = array( 'woocommerce_order_status_completed', 'woocommerce_order_status_processing' );
			$extra_hooks   = array_diff( $create_on, $known_hooks );
			$extra_emails  = array_diff( $emails, array( 'customer_completed_order' ) );
			$sequential_on = 'yes' === get_option( $prefix . 'numbering_sequential_enabled', 'no' );

			if ( '' !== $custom_tpl ) {
				++$count;
			}
			if ( ! empty( $extra_hooks ) || ! empty( $extra_emails ) ) {
				++$count;
			}
			if ( $sequential_on ) {
				++$count;
			}
			return $count;
		}

		/**
		 * Load live state, metadata, and compatibility diagnostics.
		 *
		 * @param array $context   Request params (doc_type).
		 * @param array $store_ctx Store context from WCJ_V10_REST::get_store_sample_data().
		 * @return array<string, mixed>
		 */
		public static function load_state( $context, $store_ctx ) {
			$edition          = WCJ_V10_Store::edition();
			$nonce            = wp_create_nonce( 'wcj-cat-nonce' );
			$doc_types        = self::doc_types();
			$doc_type         = self::normalize_doc_type( isset( $context['doc_type'] ) ? $context['doc_type'] : 'invoice' );
			$prefix           = 'wcj_invoicing_' . $doc_type . '_';
			$doc_auto_locked  = ( ! self::is_pro() && 'invoice' !== $doc_type );
			$emails           = array_values( array_filter( (array) get_option( $prefix . 'attach_to_emails', array() ), 'is_string' ) );
			$create_on        = array_values( array_filter( (array) get_option( $prefix . 'create_on', array() ), 'is_string' ) );
			$default_title    = isset( $doc_types[ $doc_type ] ) ? $doc_types[ $doc_type ] : __( 'Invoice', 'woocommerce-jetpack' );
			$default_footer   = __( 'Page %page_number% / %total_pages%', 'woocommerce-jetpack' );
			$footer_enabled   = 'yes' === get_option( $prefix . 'footer_enabled', 'yes' );
			$raw_footer_text  = get_option( $prefix . 'footer_text', $default_footer );
			$classic_rules    = self::count_classic_only_rules( $prefix, $create_on, $emails );

			$diagnostics = array();
			if ( $doc_auto_locked ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => sprintf(
						/* translators: %s: Document type name */
						__( 'Booster Free automates standard Invoices. %s design settings are previewable here, while Elite unlocks automated order creation & email attachment for extra document types.', 'woocommerce-jetpack' ),
						$default_title
					),
				);
			}
			if ( $classic_rules > 0 ) {
				$diagnostics[] = array(
					'level'        => 'warning',
					'text'         => sprintf(
						/* translators: %d: Number of active Classic-only PDF rules */
						__( '%d advanced Classic setting(s) (custom HTML body template, sequential numbering, or extra status/email triggers) are active and preserved untouched.', 'woocommerce-jetpack' ),
						$classic_rules
					),
					'action_label' => __( 'View in Classic', 'woocommerce-jetpack' ),
					'action_url'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_templates&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				);
			}
			$diagnostics[] = array(
				'level' => 'info',
				'text'  => __( 'Preview is a clean schematic mockup of your header, colors, numbering, and footer. Open an order to download the rendered PDF file.', 'woocommerce-jetpack' ),
			);

			$doc_list = array();
			foreach ( $doc_types as $id => $title ) {
				$doc_list[] = array(
					'id'    => $id,
					'title' => $title,
				);
			}

			return array(
				'slug'        => self::slug(),
				'edition'     => $edition,
				'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				'live'        => array(
					'enabled'              => 'yes' === get_option( 'wcj_pdf_invoicing_enabled', 'no' ),
					'doc_type'             => $doc_type,
					'header_enabled'       => 'yes' === get_option( $prefix . 'header_enabled', 'yes' ),
					'header_image'         => (string) get_option( $prefix . 'header_image', '' ),
					'header_title'         => (string) get_option( $prefix . 'header_title_text', $default_title ),
					'header_text'          => (string) get_option( $prefix . 'header_text', __( 'Company Name', 'woocommerce-jetpack' ) ),
					'header_text_color'    => (string) get_option( $prefix . 'header_text_color', '#cccccc' ),
					'header_line_color'    => (string) get_option( $prefix . 'header_line_color', '#cccccc' ),
					'numbering_prefix'     => (string) get_option( $prefix . 'numbering_prefix', '' ),
					'numbering_suffix'     => (string) get_option( $prefix . 'numbering_suffix', '' ),
					'footer_text'          => $footer_enabled ? (string) $raw_footer_text : '',
					'create_on_completed'  => in_array( 'woocommerce_order_status_completed', $create_on, true ),
					'create_on_processing' => in_array( 'woocommerce_order_status_processing', $create_on, true ),
					'attach_completed'     => in_array( 'customer_completed_order', $emails, true ),
					'customer_my_account'  => 'yes' === get_option( $prefix . 'enabled_for_customers', 'no' ),
					'thankyou_page'        => 'yes' === get_option( $prefix . 'enabled_on_thankyou_page', 'no' ),
					'skip_zero_total'      => 'yes' === get_option( $prefix . 'skip_zero_total', 'no' ),
				),
				'meta'        => array(
					'currency_symbol'      => $store_ctx['currency_symbol'],
					'site_name'            => $store_ctx['store_defaults']['name'],
					'doc_type_auto_locked' => $doc_auto_locked,
					'verify_url'           => $store_ctx['orders_url'],
					'verify_label'         => __( 'Open Orders to Generate Sample', 'woocommerce-jetpack' ),
					'store_defaults'       => $store_ctx['store_defaults'],
					'doc_types'            => $doc_list,
					'diagnostics'          => $diagnostics,
					'classic_sections'     => array(
						'pdf_invoicing'           => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_numbering' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_numbering&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_header'    => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_header&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_footer'    => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_footer&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_templates' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_templates&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_styling'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_styling&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_emails'    => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_emails&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'pdf_invoicing_display'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing_display&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
					),
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
			$doc_type        = self::normalize_doc_type( isset( $draft['doc_type'] ) ? $draft['doc_type'] : $live['doc_type'] );
			$doc_auto_locked = ( ! self::is_pro() && 'invoice' !== $doc_type );

			$header_image = ( ! array_key_exists( 'header_image', $draft ) || (string) $draft['header_image'] === (string) $live['header_image'] )
				? $live['header_image']
				: self::sanitize_header_image( (string) $draft['header_image'] );

			$header_title = ( ! array_key_exists( 'header_title', $draft ) || (string) $draft['header_title'] === (string) $live['header_title'] )
				? $live['header_title']
				: sanitize_text_field( (string) $draft['header_title'] );

			$header_text = ( ! array_key_exists( 'header_text', $draft ) || (string) $draft['header_text'] === (string) $live['header_text'] )
				? $live['header_text']
				: wp_kses_post( (string) $draft['header_text'] );

			$footer_text = ( ! array_key_exists( 'footer_text', $draft ) || (string) $draft['footer_text'] === (string) $live['footer_text'] )
				? $live['footer_text']
				: wp_kses_post( (string) $draft['footer_text'] );

			$text_color = ( ! array_key_exists( 'header_text_color', $draft ) || (string) $draft['header_text_color'] === (string) $live['header_text_color'] )
				? $live['header_text_color']
				: ( sanitize_hex_color( (string) $draft['header_text_color'] ) ?: '#cccccc' );

			$line_color = ( ! array_key_exists( 'header_line_color', $draft ) || (string) $draft['header_line_color'] === (string) $live['header_line_color'] )
				? $live['header_line_color']
				: ( sanitize_hex_color( (string) $draft['header_line_color'] ) ?: $text_color );

			$header_edited = (
				$header_image !== $live['header_image']
				|| $header_title !== $live['header_title']
				|| $header_text !== $live['header_text']
				|| $text_color !== $live['header_text_color']
				|| $line_color !== $live['header_line_color']
			);
			$header_on = array_key_exists( 'header_enabled', $draft )
				? ! empty( $draft['header_enabled'] )
				: ( ! empty( $live['header_enabled'] ) || $header_edited );

			return array(
				'enabled'              => array_key_exists( 'enabled', $draft ) ? ! empty( $draft['enabled'] ) : ! empty( $live['enabled'] ),
				'doc_type'             => $doc_type,
				'header_enabled'       => $header_on,
				'header_image'         => $header_image,
				'header_title'         => $header_title,
				'header_text'          => $header_text,
				'header_text_color'    => $text_color,
				'header_line_color'    => $line_color,
				'numbering_prefix'     => ( ! array_key_exists( 'numbering_prefix', $draft ) || (string) $draft['numbering_prefix'] === (string) $live['numbering_prefix'] )
					? $live['numbering_prefix']
					: sanitize_text_field( (string) $draft['numbering_prefix'] ),
				'numbering_suffix'     => ( ! array_key_exists( 'numbering_suffix', $draft ) || (string) $draft['numbering_suffix'] === (string) $live['numbering_suffix'] )
					? $live['numbering_suffix']
					: sanitize_text_field( (string) $draft['numbering_suffix'] ),
				'footer_text'          => $footer_text,
				'create_on_completed'  => $doc_auto_locked ? ! empty( $live['create_on_completed'] ) : ( array_key_exists( 'create_on_completed', $draft ) ? ! empty( $draft['create_on_completed'] ) : ! empty( $live['create_on_completed'] ) ),
				'create_on_processing' => $doc_auto_locked ? ! empty( $live['create_on_processing'] ) : ( array_key_exists( 'create_on_processing', $draft ) ? ! empty( $draft['create_on_processing'] ) : ! empty( $live['create_on_processing'] ) ),
				'attach_completed'     => $doc_auto_locked ? ! empty( $live['attach_completed'] ) : ( array_key_exists( 'attach_completed', $draft ) ? ! empty( $draft['attach_completed'] ) : ! empty( $live['attach_completed'] ) ),
				'customer_my_account'  => array_key_exists( 'customer_my_account', $draft ) ? ! empty( $draft['customer_my_account'] ) : ! empty( $live['customer_my_account'] ),
				'thankyou_page'        => array_key_exists( 'thankyou_page', $draft ) ? ! empty( $draft['thankyou_page'] ) : ! empty( $live['thankyou_page'] ),
				'skip_zero_total'      => array_key_exists( 'skip_zero_total', $draft ) ? ! empty( $draft['skip_zero_total'] ) : ! empty( $live['skip_zero_total'] ),
			);
		}

		/**
		 * Validate draft before publishing.
		 *
		 * @param array $draft Sanitized draft.
		 * @return true|WP_Error
		 */
		public static function validate_publish( $draft ) {
			unset( $draft );
			return true;
		}

		/**
		 * Project Before & After document preview sheets.
		 *
		 * @param array $live    Live state.
		 * @param array $draft   Draft state.
		 * @param array $context Preview parameters.
		 * @param array $meta    Editor metadata.
		 * @return array{before: array, after: array}
		 */
		public static function compute_preview( $live, $draft, $context, $meta ) {
			unset( $context, $meta );
			$doc_labels = self::doc_types();

			$project = static function ( $cfg ) use ( $doc_labels ) {
				$fallback_title = isset( $doc_labels[ $cfg['doc_type'] ] ) ? $doc_labels[ $cfg['doc_type'] ] : __( 'Invoice', 'woocommerce-jetpack' );
				$triggers       = array();
				if ( ! empty( $cfg['create_on_processing'] ) ) {
					$triggers[] = __( 'Processing', 'woocommerce-jetpack' );
				}
				if ( ! empty( $cfg['create_on_completed'] ) ) {
					$triggers[] = __( 'Completed', 'woocommerce-jetpack' );
				}
				$trigger_str = ! empty( $triggers )
					? sprintf(
						/* translators: %s: WooCommerce order status triggers */
						__( 'Auto-creates on %s', 'woocommerce-jetpack' ),
						implode( ' / ', $triggers )
					)
					: __( 'Manual creation', 'woocommerce-jetpack' );

				$email_str = ! empty( $cfg['attach_completed'] )
					? __( 'Attaches to Completed Email', 'woocommerce-jetpack' )
					: __( 'No email attachment', 'woocommerce-jetpack' );

				return array(
					'enabled'             => ! empty( $cfg['enabled'] ),
					'doc_type'            => $cfg['doc_type'],
					'header_enabled'      => ! empty( $cfg['header_enabled'] ),
					'header_image'        => $cfg['header_image'],
					'header_title'        => '' !== trim( wp_strip_all_tags( (string) $cfg['header_title'] ) ) ? wp_strip_all_tags( (string) $cfg['header_title'] ) : $fallback_title,
					'header_text'         => '' !== trim( wp_strip_all_tags( (string) $cfg['header_text'] ) ) ? wp_strip_all_tags( (string) $cfg['header_text'] ) : __( 'Company Name', 'woocommerce-jetpack' ),
					'header_text_color'   => $cfg['header_text_color'],
					'header_line_color'   => $cfg['header_line_color'],
					'invoice_number'      => $cfg['numbering_prefix'] . '1042' . $cfg['numbering_suffix'],
					'footer_text'         => trim( wp_strip_all_tags( (string) $cfg['footer_text'] ) ),
					'create_on_completed' => ! empty( $cfg['create_on_completed'] ),
					'customer_my_account' => ! empty( $cfg['customer_my_account'] ),
					'thankyou_page'       => ! empty( $cfg['thankyou_page'] ),
					'skip_zero_total'     => ! empty( $cfg['skip_zero_total'] ),
					'email_status'        => $trigger_str . ' • ' . $email_str,
				);
			};

			return array(
				'before' => $project( $live ),
				'after'  => $project( $draft ),
			);
		}

		/**
		 * Map ONLY changed draft fields onto native wp_options keys using fresh snapshot merges.
		 *
		 * @param array                $draft        Sanitized draft.
		 * @param array<int, string>   $changed_keys Changed diff field keys.
		 * @param array<string, array> $snapshot     Fresh option snapshot from WCJ_V10_Store::snapshot_options().
		 * @return array<string, mixed>
		 */
		public static function build_option_writes( $draft, $changed_keys = array(), $snapshot = array() ) {
			if ( empty( $changed_keys ) ) {
				return array();
			}

			$changed  = array_flip( $changed_keys );
			$doc_type = self::normalize_doc_type( isset( $draft['doc_type'] ) ? $draft['doc_type'] : 'invoice' );
			$prefix   = 'wcj_invoicing_' . $doc_type . '_';
			$writes   = array();

			$snap_arr = static function ( $key ) use ( $snapshot ) {
				$val = isset( $snapshot[ $key ] ) ? WCJ_V10_Store::state_value( $snapshot[ $key ] ) : get_option( $key, array() );
				return array_values( array_filter( (array) $val, 'is_string' ) );
			};

			if ( isset( $changed['enabled'] ) ) {
				$writes['wcj_pdf_invoicing_enabled'] = ! empty( $draft['enabled'] ) ? 'yes' : 'no';
			}

			$scalar_field_map = array(
				'header_enabled'      => array( $prefix . 'header_enabled', ! empty( $draft['header_enabled'] ) ? 'yes' : 'no' ),
				'header_image'        => array( $prefix . 'header_image', $draft['header_image'] ),
				'header_title'        => array( $prefix . 'header_title_text', $draft['header_title'] ),
				'header_text'         => array( $prefix . 'header_text', $draft['header_text'] ),
				'header_text_color'   => array( $prefix . 'header_text_color', $draft['header_text_color'] ),
				'header_line_color'   => array( $prefix . 'header_line_color', $draft['header_line_color'] ),
				'numbering_prefix'    => array( $prefix . 'numbering_prefix', $draft['numbering_prefix'] ),
				'numbering_suffix'    => array( $prefix . 'numbering_suffix', $draft['numbering_suffix'] ),
				'customer_my_account' => array( $prefix . 'enabled_for_customers', ! empty( $draft['customer_my_account'] ) ? 'yes' : 'no' ),
				'thankyou_page'       => array( $prefix . 'enabled_on_thankyou_page', ! empty( $draft['thankyou_page'] ) ? 'yes' : 'no' ),
				'skip_zero_total'     => array( $prefix . 'skip_zero_total', ! empty( $draft['skip_zero_total'] ) ? 'yes' : 'no' ),
			);

			foreach ( $scalar_field_map as $field_key => $pair ) {
				if ( isset( $changed[ $field_key ] ) ) {
					$writes[ $pair[0] ] = $pair[1];
				}
			}

			if ( isset( $changed['footer_text'] ) ) {
				$trimmed_footer                   = trim( (string) $draft['footer_text'] );
				$writes[ $prefix . 'footer_text' ] = $draft['footer_text'];
				$writes[ $prefix . 'footer_enabled' ] = '' !== $trimmed_footer ? 'yes' : 'no';
			}

			if ( isset( $changed['attach_completed'] ) ) {
				$emails = $snap_arr( $prefix . 'attach_to_emails' );
				if ( ! empty( $draft['attach_completed'] ) ) {
					if ( ! in_array( 'customer_completed_order', $emails, true ) ) {
						$emails[] = 'customer_completed_order';
					}
				} else {
					$emails = array_values( array_diff( $emails, array( 'customer_completed_order' ) ) );
				}
				$writes[ $prefix . 'attach_to_emails' ] = $emails;
			}

			if ( isset( $changed['create_on_completed'] ) || isset( $changed['create_on_processing'] ) ) {
				$create_on = $snap_arr( $prefix . 'create_on' );
				foreach ( array(
					'create_on_completed'  => 'woocommerce_order_status_completed',
					'create_on_processing' => 'woocommerce_order_status_processing',
				) as $field_key => $hook_name ) {
					if ( isset( $changed[ $field_key ] ) ) {
						if ( ! empty( $draft[ $field_key ] ) ) {
							if ( ! in_array( $hook_name, $create_on, true ) ) {
								$create_on[] = $hook_name;
							}
						} else {
							$create_on = array_values( array_diff( $create_on, array( $hook_name ) ) );
						}
					}
				}
				$writes[ $prefix . 'create_on' ] = $create_on;
			}

			return $writes;
		}

		/**
		 * Emergency shutoff option writes (disables module immediately without touching doc templates).
		 *
		 * @param array<string, array> $snapshot Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function emergency_off_writes( $snapshot = array() ) {
			$cur = isset( $snapshot['wcj_pdf_invoicing_enabled'] )
				? WCJ_V10_Store::state_value( $snapshot['wcj_pdf_invoicing_enabled'] )
				: get_option( 'wcj_pdf_invoicing_enabled', 'no' );
			if ( 'yes' !== $cur ) {
				return array();
			}
			return array(
				'wcj_pdf_invoicing_enabled' => 'no',
			);
		}
	}

endif;

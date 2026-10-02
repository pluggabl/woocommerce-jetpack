<?php
/**
 * Bounded starter document setup, within Booster's existing onboarding.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * No orders, mail, numbering, arbitrary templates or general workflow rules.
 */
class WCJ_Invoice_Setup {
	/**
	 * Dedicated edition invoice journal, isolated from legacy onboarding writers.
	 *
	 * @var string
	 */
	private $state_key;

	/**
	 * Construct.
	 *
	 * @param string $state_key Existing onboarding option.
	 * @throws RuntimeException When a setup safeguard fails.
	 */
	public function __construct( $state_key ) {
		if ( ! in_array( $state_key, array( 'booster_free_onboarding', 'booster_plus_onboarding', 'booster_elite_onboarding' ), true ) ) {
			throw new RuntimeException( 'Unknown onboarding state.' );
		}
		$this->state_key = $state_key . '_invoice_setup';
		foreach ( array( 'sample', 'rehearse', 'review', 'activate', 'undo' ) as $operation ) {
			add_action( 'wp_ajax_wcj_invoice_setup_' . $operation, array( $this, 'handle_request' ) );
		}
	}

	/**
	 * Url.
	 *
	 * @return string Non-mutating entrypoint.
	 */
	public static function url() {
		return admin_url( 'admin.php?page=wcj-getting-started&wcj-invoice-setup=1' );
	}

	/**
	 * Services check capability as well as the request adapter.
	 *
	 * @throws RuntimeException When a setup safeguard fails.
	 */
	public static function authorize() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by WooCommerce.
			throw new RuntimeException( esc_html__( 'You need permission to manage WooCommerce.', 'woocommerce-jetpack' ) );
		}
	}

	/**
	 * Full guide.
	 *
	 * @return bool New guidance only; never changes existing Plus entitlement filters.
	 */
	private static function full_guide() {
		$file = defined( 'WCJ_PLUGIN_FILE' ) ? WCJ_PLUGIN_FILE : '';
		return 'booster-elite-for-woocommerce.php' === basename( $file );
	}

	/**
	 * Documents.
	 *
	 * @return array Bounded guide document types.
	 */
	public static function documents() {
		self::authorize();
		$types = array( 'invoice' => __( 'Invoice', 'woocommerce-jetpack' ) );
		if ( self::full_guide() ) {
			$types['proforma_invoice'] = __( 'Proforma Invoice', 'woocommerce-jetpack' );
			$types['packing_slip']     = __( 'Packing Slip', 'woocommerce-jetpack' );
			$types['credit_note']      = __( 'Credit Note', 'woocommerce-jetpack' );
		}
		return $types;
	}

	/**
	 * Operations.
	 *
	 * @return array Fixed existing hooks, not arbitrary submitted hook names.
	 */
	public static function operations() {
		$items = array(
			'manual'    => __( 'Manually', 'woocommerce-jetpack' ),
			'completed' => __( 'When an order is completed', 'woocommerce-jetpack' ),
		);
		if ( self::full_guide() ) {
			$items['processing'] = __( 'When an order is processing', 'woocommerce-jetpack' );
			$items['refunded']   = __( 'When an order is refunded', 'woocommerce-jetpack' );
		}
		return $items;
	}

	/**
	 * Emails.
	 *
	 * @return array Fixed existing email IDs, never an email recipient.
	 */
	public static function emails() {
		$items = array(
			''                         => __( 'Do not attach to email', 'woocommerce-jetpack' ),
			'customer_completed_order' => __( 'Customer completed order email', 'woocommerce-jetpack' ),
		);
		if ( self::full_guide() ) {
			$items['customer_processing_order'] = __( 'Customer processing order email', 'woocommerce-jetpack' );
			$items['customer_refunded_order']   = __( 'Customer refunded order email', 'woocommerce-jetpack' );
		}
		return $items;
	}

	/**
	 * Validate.
	 *
	 * @param array $input Plain bounded form values.
	 * @return array Canonical draft.
	 * @throws RuntimeException When a setup safeguard fails.
	 */
	public static function validate( $input ) {
		self::authorize();
		$defaults = array(
			'business_name'      => '',
			'business_address'   => '',
			'business_reference' => '',
			'logo_attachment_id' => '0',
			'accent_color'       => '#0073aa',
			'footer_note'        => '',
			'document_type'      => 'invoice',
			'operation'          => 'manual',
			'email'              => '',
		);
		if ( ! is_array( $input ) || array_diff( array_keys( $input ), array_keys( $defaults ) ) ) {
			throw new RuntimeException( esc_html__( 'Unknown setup field.', 'woocommerce-jetpack' ) );
		}
		foreach ( $input as $value ) {
			if ( ! is_string( $value ) || strlen( $value ) > 1200 ) {
				throw new RuntimeException( esc_html__( 'Setup fields must be short plain text.', 'woocommerce-jetpack' ) );
			}
		}
		$draft = array_merge( $defaults, $input );
		foreach ( array(
			'business_name'      => 120,
			'business_address'   => 500,
			'business_reference' => 120,
			'footer_note'        => 240,
		) as $key => $limit ) {
			$value = trim( $draft[ $key ] );
			if ( strlen( $value ) > $limit || preg_match( '/[<>\[\]\x00-\x08\x0B\x0C\x0E-\x1F]/u', $value ) || false === preg_match( '//u', $value ) || substr_count( $value, "\n" ) > 6 ) {
				throw new RuntimeException( esc_html__( 'Use plain text without HTML or shortcodes, within the displayed limits.', 'woocommerce-jetpack' ) );
			}
			$draft[ $key ] = $value;
		}
		if ( '' === $draft['business_name'] || '' === $draft['business_address'] || ! preg_match( '/^#[a-fA-F0-9]{6}$/D', $draft['accent_color'] ) || ! preg_match( '/^[0-9]{1,10}$/D', $draft['logo_attachment_id'] ) ) {
			throw new RuntimeException( esc_html__( 'Enter a business name, address, valid color and local image attachment ID.', 'woocommerce-jetpack' ) );
		}
		if ( ! isset( self::documents()[ $draft['document_type'] ] ) || ! isset( self::operations()[ $draft['operation'] ] ) || ! array_key_exists( $draft['email'], self::emails() ) ) {
			throw new RuntimeException( esc_html__( 'This choice is not available in this setup guide. Existing advanced settings remain available.', 'woocommerce-jetpack' ) );
		}
		$matches = array(
			'completed'  => 'customer_completed_order',
			'processing' => 'customer_processing_order',
			'refunded'   => 'customer_refunded_order',
		);
		if ( ( 'credit_note' === $draft['document_type'] && ! in_array( $draft['operation'], array( 'manual', 'refunded' ), true ) ) || ( 'refunded' === $draft['operation'] && 'credit_note' !== $draft['document_type'] ) ) {
			throw new RuntimeException( esc_html__( 'Credit notes support manual or refunded-order generation in this guide. Other documents must not use the refunded-order recipe. No tax or refund policy is determined by this guide.', 'woocommerce-jetpack' ) );
		}
		if ( '' !== $draft['email'] && ( ! isset( $matches[ $draft['operation'] ] ) || $matches[ $draft['operation'] ] !== $draft['email'] ) ) {
			throw new RuntimeException( esc_html__( 'Choose an email that matches the selected generation event, or no attachment.', 'woocommerce-jetpack' ) );
		}
		$draft['accent_color'] = strtolower( $draft['accent_color'] );
		self::logo( $draft['logo_attachment_id'] );
		return $draft;
	}

	/**
	 * Logo.
	 *
	 * @param string $id Existing attachment ID.
	 * @return array Validated local image.
	 * @throws RuntimeException When a setup safeguard fails.
	 */
	private static function logo( $id ) {
		if ( '0' === $id || '' === $id ) {
			return array(
				'path'      => '',
				'url'       => '',
				'width_mm'  => '35',
				'height_mm' => 0,
			);
		}
		$uploads = wp_upload_dir( null, false );
		$root    = realpath( $uploads['basedir'] );
		$path    = realpath( get_attached_file( (int) $id, true ) );
		if ( ! $root || ! $path || 0 !== strpos( $path, $root . DIRECTORY_SEPARATOR ) || ! is_file( $path ) || ! is_readable( $path ) || filesize( $path ) > 2097152 ) {
			throw new RuntimeException( esc_html__( 'Choose a local PNG or JPEG in this site\'s Media Library, no larger than 2 MB.', 'woocommerce-jetpack' ) );
		}
		$size = getimagesize( $path );
		if ( ! $size || ! in_array( $size[2], array( IMAGETYPE_JPEG, IMAGETYPE_PNG ), true ) || $size[0] < 1 || $size[1] < 1 || $size[0] > 4096 || $size[1] > 4096 || $size[0] * $size[1] > 4000000 || $size[0] > 8 * $size[1] || $size[1] > 8 * $size[0] ) {
			throw new RuntimeException( esc_html__( 'Use a PNG or JPEG of at most four million pixels, at most 4096 pixels per side, with an aspect ratio between 1:8 and 8:1.', 'woocommerce-jetpack' ) );
		}
		$url = wp_get_attachment_url( (int) $id );
		if ( ! $url || wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( $uploads['baseurl'], PHP_URL_HOST ) ) {
			throw new RuntimeException( esc_html__( 'Remote and offloaded logos are not supported by this starter guide.', 'woocommerce-jetpack' ) );
		}
		$width = min( 35, floor( 1500 * $size[0] / $size[1] ) / 100 );
		return array(
			'path'      => $path,
			'url'       => $url,
			'width_mm'  => (string) $width,
			'height_mm' => $width * $size[1] / $size[0],
		);
	}

	/**
	 * Stock template.
	 *
	 * @param string $type Allowed type.
	 * @return string Shipped template, never merchant HTML.
	 * @throws RuntimeException When a setup safeguard fails.
	 */
	private static function stock_template( $type ) {
		if ( ! isset( self::documents()[ $type ] ) ) {
			throw new RuntimeException( esc_html__( 'Unsupported document.', 'woocommerce-jetpack' ) );
		}
		$path = dirname( __DIR__ ) . '/settings/pdf-invoicing/wcj-content-template-' . $type . '.html';
		$text = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Fixed shipped file.
		if ( false === $text ) {
			throw new RuntimeException( esc_html__( 'The starter template is unavailable.', 'woocommerce-jetpack' ) );
		}
		return $text;
	}

	/**
	 * Starter template.
	 *
	 * @param array $draft Validated plain draft.
	 * @return string Trusted stock-based production template.
	 */
	private static function starter_template( $draft ) {
		$text   = self::stock_template( $draft['document_type'] );
		$seller = esc_html( $draft['business_name'] ) . '<br>' . nl2br( esc_html( $draft['business_address'] ), false ) . '<br>' . esc_html( $draft['business_reference'] );
		if ( 'packing_slip' === $draft['document_type'] ) {
			$text = '<p>' . $seller . '</p>' . $text;
		} else {
			$text = str_replace( 'COMPANY NAME<br>COMPANY ADDRESS 1<br>COMPANY ADDRESS 2<br>', $seller, $text );
		}
		return $text;
	}

	/**
	 * Sample content.
	 *
	 * @param array $input Plain draft.
	 * @return array Safe sample body and local image.
	 * @throws RuntimeException When a setup safeguard fails.
	 */
	public static function sample_content( $input ) {
		$draft = self::validate( $input );
		$html  = self::starter_template( $draft );
		// These are fixed template tokens, not a shortcode interpreter or an order lookup.
		$items  = '<table border="1" cellpadding="5"><tr><th>' . esc_html__( 'Item', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Qty', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Total', 'woocommerce-jetpack' ) . '</th></tr><tr><td>' . esc_html__( 'Sample product', 'woocommerce-jetpack' ) . '</td><td>1</td><td>100.00</td></tr></table>';
		$html   = preg_replace_callback(
			'/\[wcj_order_items_table[^\]]*\]/',
			static function () use ( $items ) {
				return $items;
			},
			$html
		);
		$buyer  = esc_html__( 'Sample Customer', 'woocommerce-jetpack' ) . '<br>' . esc_html__( '123 Example Street', 'woocommerce-jetpack' ) . '<br>' . esc_html__( 'Example City', 'woocommerce-jetpack' );
		$tokens = array(
			'wcj_order_billing_address'             => $buyer,
			'wcj_order_shipping_address'            => $buyer,
			'wcj_order_number'                      => 'SAMPLE-ORDER',
			'wcj_order_date'                        => '2026-01-01',
			'wcj_order_shipping_method'             => esc_html__( 'Sample shipping', 'woocommerce-jetpack' ),
			'wcj_order_payment_method'              => esc_html__( 'Sample payment', 'woocommerce-jetpack' ),
			'wcj_order_total_excl_tax'              => '100.00',
			'wcj_order_total_tax hide_if_zero="no"' => '0.00',
			'wcj_order_total'                       => '100.00',
		);
		foreach ( array( 'invoice', 'proforma_invoice', 'credit_note', 'packing_slip' ) as $type ) {
			$tokens[ 'wcj_' . $type . '_number' ] = 'SAMPLE-001';
			$tokens[ 'wcj_' . $type . '_date' ]   = '2026-01-01';
		}
		foreach ( $tokens as $token => $value ) {
			$html = str_replace( '[' . $token . ']', $value, $html );
		}
		if ( false !== strpos( $html, '[' ) ) {
			throw new RuntimeException( esc_html__( 'This starter template contains an unsupported sample token.', 'woocommerce-jetpack' ) );
		}
		$logo = self::logo( $draft['logo_attachment_id'] );
		return array(
			'html'           => '<h2 style="color:#b42318">' . esc_html__( 'SAMPLE - NOT A VALID INVOICE', 'woocommerce-jetpack' ) . '</h2>' . $html . '<p>' . esc_html( $draft['footer_note'] ) . '</p>',
			'logo_path'      => $logo['path'],
			'logo_width_mm'  => $logo['width_mm'],
			'logo_height_mm' => $logo['height_mm'],
			'accent'         => $draft['accent_color'],
		);
	}

	/**
	 * Read option.
	 *
	 * @param string $key Internal allowlisted option name.
	 * @return array Exact persisted identity.
	 */
	private function read_option( $key ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $row ? array(
			'exists' => true,
			'raw'    => $row['option_value'],
		) : array(
			'exists' => false,
			'raw'    => '',
		);
	}

	/**
	 * Value state.
	 *
	 * @param mixed $value Option value.
	 * @return array Serialized identity.
	 */
	private function value_state( $value ) {
		return array(
			'exists' => true,
			'raw'    => (string) maybe_serialize( $value ),
		);
	}

	/**
	 * Value.
	 *
	 * @param array $state Exact state.
	 * @return mixed Persisted value.
	 */
	private function value( $state ) {
		return $state['exists'] ? maybe_unserialize( $state['raw'] ) : null;
	}

	/**
	 * Conditional writes protect intervening edits without rewriting other options.
	 *
	 * @param string $key Internal option name.
	 * @param array  $before Expected exact persisted state.
	 * @param array  $after Desired exact persisted state.
	 */
	private function compare_exchange( $key, $before, $after ) {
		global $wpdb;
		if ( $before === $after ) {
			return $this->read_option( $key ) === $before;
		}
		if ( ! $before['exists'] ) {
			$result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", $key, $after['raw'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} elseif ( ! $after['exists'] ) {
			$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND BINARY option_value = %s", $key, $before['raw'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$result = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND BINARY option_value = %s", $after['raw'], $key, $before['raw'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
		wp_cache_delete( $key, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		return 1 === $result;
	}

	/**
	 * Plan.
	 *
	 * @return array Exact relevant settings, including guards which are never modified.
	 * @param array $draft Validated plain draft.
	 * @throws RuntimeException When a setup safeguard fails.
	 */
	private function plan( $draft ) {
		self::authorize();
		$prefix = 'wcj_invoicing_' . $draft['document_type'] . '_';
		$stock  = $this->read_option( $prefix . 'template' );
		if ( $stock['exists'] && '' !== $stock['raw'] && trim( $stock['raw'] ) !== trim( self::stock_template( $draft['document_type'] ) ) ) {
			throw new RuntimeException( esc_html__( 'A custom template is already saved. It has not been changed or previewed. Use advanced settings for this document.', 'woocommerce-jetpack' ) );
		}
		$logo = self::logo( $draft['logo_attachment_id'] );
		if ( $logo['path'] ) {
			$root = wcj_get_invoicing_default_images_directory();
			if ( false === $root || realpath( $root . wp_parse_url( $logo['url'], PHP_URL_PATH ) ) !== $logo['path'] ) {
				throw new RuntimeException( esc_html__( 'The current PDF image directory cannot resolve this logo. Review advanced image settings, or continue without a logo.', 'woocommerce-jetpack' ) );
			}
		}
		$desired = array(
			$prefix . 'template'              => self::starter_template( $draft ),
			$prefix . 'header_enabled'        => 'yes',
			$prefix . 'header_image'          => $logo['url'],
			$prefix . 'header_image_width_mm' => $logo['width_mm'],
			$prefix . 'header_title_text'     => self::documents()[ $draft['document_type'] ],
			$prefix . 'header_text'           => $draft['business_name'],
			$prefix . 'header_text_color'     => $draft['accent_color'],
			$prefix . 'header_line_color'     => $draft['accent_color'],
			$prefix . 'footer_enabled'        => 'yes',
			$prefix . 'footer_text'           => esc_html( $draft['footer_note'] ),
			$prefix . 'attach_to_emails'      => '' === $draft['email'] ? array() : array( $draft['email'] ),
			$prefix . 'create_on'             => array( 'manual' === $draft['operation'] ? 'manual' : 'woocommerce_order_status_' . $draft['operation'] ), // Enable generation only after the selected document is configured.
			'wcj_pdf_invoicing_enabled'       => 'yes', // Parent activation is deliberately last.
		);
		$before  = array();
		foreach ( $desired as $key => $value ) {
			$before[ $key ] = $this->read_option( $key );
		}
		$existing_rules = $this->value( $before[ $prefix . 'create_on' ] );
		if ( 'yes' === $this->value( $before['wcj_pdf_invoicing_enabled'] ) && ! empty( $existing_rules ) && 'disabled' !== $existing_rules ) {
			throw new RuntimeException( esc_html__( 'This document already has active generation rules. The guide will not change a live recipe. Use advanced settings; synthetic samples remain available here.', 'woocommerce-jetpack' ) );
		}
		$guards = array();
		foreach ( array( 'wcj_general_advanced_disable_save_sys_temp_dir', 'wcj_invoicing_general_tmp_dir', 'wcj_invoicing_general_header_images_path', $prefix . 'payment_gateways' ) as $key ) {
			$guards[ $key ] = $this->read_option( $key );
		}
		if ( '' !== $draft['email'] && 'yes' === $this->value( $guards['wcj_general_advanced_disable_save_sys_temp_dir'] ) ) {
			throw new RuntimeException( esc_html__( 'PDF email attachments are disabled by the existing temporary-file setting. Choose no attachment or review advanced settings.', 'woocommerce-jetpack' ) );
		}
		$others = $this->other_documents( $draft['document_type'] );
		if ( 'yes' !== $this->value( $before['wcj_pdf_invoicing_enabled'] ) && $others ) {
			throw new RuntimeException( esc_html__( 'Other documents have saved generation rules while PDF Invoicing is off. Enabling the parent module would activate them too. Review advanced settings first.', 'woocommerce-jetpack' ) );
		}
		return array(
			'before'          => $before,
			'desired'         => $desired,
			'guards'          => $guards,
			'other_documents' => $others,
		);
	}

	/**
	 * Other documents.
	 *
	 * @param string $selected Selected document.
	 * @return array Other persisted configured documents.
	 */
	private function other_documents( $selected ) {
		global $wpdb;
		$rows  = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'wcj_invoicing_%_create_on'", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$other = array();
		foreach ( $rows as $row ) {
			if ( preg_match( '/^wcj_invoicing_([a-z0-9_]+)_create_on$/D', $row['option_name'] ) && 'wcj_invoicing_' . $selected . '_create_on' !== $row['option_name'] ) {
				$value = maybe_unserialize( $row['option_value'] );
				if ( ! empty( $value ) && 'disabled' !== $value ) {
					$other[ $row['option_name'] ] = $row['option_value'];
				}
			}
		}
		ksort( $other );
		return $other;
	}

	/**
	 * Review.
	 *
	 * @param array $input Plain draft.
	 * @return array Server-authored review, no writes.
	 */
	public function review( $input ) {
		$draft = self::validate( $input );
		$plan  = $this->plan( $draft );
		$rows  = array();
		foreach ( $plan['desired'] as $key => $value ) {
			if ( $plan['before'][ $key ] !== $this->value_state( $value ) ) {
				$rows[] = array(
					'option'         => $key,
					'before'         => $this->value( $plan['before'][ $key ] ),
					'after'          => $value,
					'label'          => $this->setting_label( $key, $draft['document_type'] ),
					'before_display' => $this->setting_display( $key, $this->value( $plan['before'][ $key ] ), false ),
					'after_display'  => $this->setting_display( $key, $value, true ),
				);
			}
		}
		return array(
			'fingerprint'          => hash( 'sha256', wp_json_encode( array( $draft, $plan ) ) ),
			'changes'              => $rows,
			'gateway_restrictions' => $this->value( $plan['guards'][ 'wcj_invoicing_' . $draft['document_type'] . '_payment_gateways' ] ),
			'message'              => __( 'Only the listed settings will change. Existing numbering, tax, advanced styling and gateway restrictions remain unchanged. Email delivery has not been tested.', 'woocommerce-jetpack' ),
		);
	}

	/**
	 * Human-readable labels accompany, but do not replace, the exact technical plan.
	 *
	 * @param string $key Allowlisted setting.
	 * @param string $type Document type.
	 * @return string Setting label.
	 */
	private function setting_label( $key, $type ) {
		if ( 'wcj_pdf_invoicing_enabled' === $key ) {
			return __( 'PDF Invoicing module', 'woocommerce-jetpack' );
		}
		$labels = array(
			'template'              => __( 'Document design and seller details', 'woocommerce-jetpack' ),
			'header_enabled'        => __( 'Document header', 'woocommerce-jetpack' ),
			'header_image'          => __( 'Business logo', 'woocommerce-jetpack' ),
			'header_image_width_mm' => __( 'Logo width (mm)', 'woocommerce-jetpack' ),
			'header_title_text'     => __( 'Header title', 'woocommerce-jetpack' ),
			'header_text'           => __( 'Business name in header', 'woocommerce-jetpack' ),
			'header_text_color'     => __( 'Header text color', 'woocommerce-jetpack' ),
			'header_line_color'     => __( 'Header line color', 'woocommerce-jetpack' ),
			'footer_enabled'        => __( 'Document footer', 'woocommerce-jetpack' ),
			'footer_text'           => __( 'Footer note', 'woocommerce-jetpack' ),
			'create_on'             => __( 'Replace generation rules with', 'woocommerce-jetpack' ),
			'attach_to_emails'      => __( 'Replace email attachment selections with', 'woocommerce-jetpack' ),
		);
		$suffix = substr( $key, strlen( 'wcj_invoicing_' . $type . '_' ) );
		return isset( $labels[ $suffix ] ) ? $labels[ $suffix ] : __( 'Document setting', 'woocommerce-jetpack' );
	}

	/**
	 * Plain display values keep HTML/option identifiers out of the primary confirmation.
	 *
	 * @param string $key Allowlisted setting.
	 * @param mixed  $value Existing or proposed value.
	 * @param bool   $proposed Whether this is the proposed setting.
	 * @return string Plain review value.
	 */
	private function setting_display( $key, $value, $proposed ) {
		if ( 'wcj_pdf_invoicing_enabled' === $key ) {
			return 'yes' === $value ? __( 'Enabled', 'woocommerce-jetpack' ) : __( 'Disabled', 'woocommerce-jetpack' );
		}
		if ( substr( $key, -9 ) === '_template' ) {
			return $proposed ? __( 'Stock starter design with your business details', 'woocommerce-jetpack' ) : __( 'Existing stock/default design', 'woocommerce-jetpack' );
		}
		if ( substr( $key, -10 ) === '_create_on' || substr( $key, -17 ) === '_attach_to_emails' ) {
			$labels = array(
				'manual'                              => __( 'Manual generation only', 'woocommerce-jetpack' ),
				'woocommerce_order_status_completed'  => __( 'When an order is completed', 'woocommerce-jetpack' ),
				'woocommerce_order_status_processing' => __( 'When an order is processing', 'woocommerce-jetpack' ),
				'woocommerce_order_status_refunded'   => __( 'When an order is refunded', 'woocommerce-jetpack' ),
				'woocommerce_new_order'               => __( 'When a new order is created', 'woocommerce-jetpack' ),
				'customer_completed_order'            => __( 'Customer completed order email', 'woocommerce-jetpack' ),
				'customer_processing_order'           => __( 'Customer processing order email', 'woocommerce-jetpack' ),
				'customer_refunded_order'             => __( 'Customer refunded order email', 'woocommerce-jetpack' ),
			);
			$parts  = array();
			foreach ( (array) $value as $id ) {
				$parts[] = is_string( $id ) && isset( $labels[ $id ] ) ? $labels[ $id ] : __( 'Existing advanced selection (see technical details)', 'woocommerce-jetpack' );
			}
			return $parts ? implode( '; ', $parts ) : __( 'None', 'woocommerce-jetpack' );
		}
		if ( substr( $key, -13 ) === '_header_image' ) {
			return empty( $value ) ? __( 'No logo', 'woocommerce-jetpack' ) : ( $proposed ? __( 'Selected local Media Library logo', 'woocommerce-jetpack' ) : __( 'Existing logo', 'woocommerce-jetpack' ) );
		}
		if ( null === $value ) {
			return __( 'Not stored (uses the existing default)', 'woocommerce-jetpack' );
		}
		if ( in_array( $value, array( 'yes', 'no' ), true ) ) {
			return 'yes' === $value ? __( 'Enabled', 'woocommerce-jetpack' ) : __( 'Disabled', 'woocommerce-jetpack' );
		}
		return is_scalar( $value ) ? (string) $value : __( 'Existing advanced value (see technical details)', 'woocommerce-jetpack' );
	}

	/**
	 * Rehearse.
	 *
	 * @param array $input Plain draft.
	 * @return array Actual local attachment construction, not mail delivery.
	 * @throws RuntimeException When a setup safeguard fails.
	 */
	public function rehearse( $input ) {
		$draft = self::validate( $input );
		$bytes = WCJ_PDF_Invoice::setup_sample_pdf( $draft );
		$tmp   = $this->value( $this->read_option( 'wcj_invoicing_general_tmp_dir' ) );
		$tmp   = empty( $tmp ) ? sys_get_temp_dir() : $tmp;
		if ( ! is_string( $tmp ) || strpos( $tmp, '://' ) !== false || ! realpath( $tmp ) || ! is_dir( $tmp ) || ! is_writable( $tmp ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Check local path before any filesystem adapter is initialized.
			throw new RuntimeException( esc_html__( 'The configured local PDF temporary directory is not usable.', 'woocommerce-jetpack' ) );
		}
		$dir  = realpath( $tmp ) . DIRECTORY_SEPARATOR . 'booster-setup-' . bin2hex( random_bytes( 16 ) );
		$path = $dir . DIRECTORY_SEPARATOR . 'sample.pdf';
		if ( ! mkdir( $dir, 0700 ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			throw new RuntimeException( esc_html__( 'The synthetic attachment directory could not be created.', 'woocommerce-jetpack' ) );
		}
		try {
			// Do not let the filesystem abstraction select FTP/SSH or initiate remote access.
			global $wp_filesystem;
			if ( ( defined( 'FS_METHOD' ) && 'direct' !== FS_METHOD ) || ( $wp_filesystem && 'WP_Filesystem_Direct' !== get_class( $wp_filesystem ) ) ) {
				throw new RuntimeException( esc_html__( 'The rehearsal requires a local direct filesystem. No remote connection was attempted.', 'woocommerce-jetpack' ) );
			}
			if ( ! $wp_filesystem ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
				$wp_filesystem = new WP_Filesystem_Direct( null ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Initialize the documented filesystem global locally, never FTP or SSH.
			}
			if ( ! WCJ_PDF_Invoice::write_pdf_bytes( $path, $bytes ) || ! is_readable( $path ) || hash_file( 'sha256', $path ) !== hash( 'sha256', $bytes ) ) {
				throw new RuntimeException( esc_html__( 'The synthetic PDF attachment could not be written and read back.', 'woocommerce-jetpack' ) );
			}
			$attachments = array( $path );
			if ( 1 !== count( $attachments ) || filesize( $attachments[0] ) !== strlen( $bytes ) ) {
				throw new RuntimeException( esc_html__( 'The synthetic attachment check failed.', 'woocommerce-jetpack' ) );
			}
		} finally {
			$removed = ! file_exists( $path ) || unlink( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			$removed = rmdir( $dir ) && $removed; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}
		if ( ! $removed ) {
			throw new RuntimeException( esc_html__( 'The synthetic attachment was built, but temporary cleanup failed. Setup has not been activated.', 'woocommerce-jetpack' ) );
		}
		return array(
			'constructed' => true,
			'removed'     => true,
			'bytes'       => strlen( $bytes ),
			'message'     => __( 'Synthetic attachment constructed, read back and removed. No email was sent. Customer email delivery has not been tested.', 'woocommerce-jetpack' ),
		);
	}

	/**
	 * Save snapshot.
	 *
	 * @param array|null  $snapshot This workflow only.
	 * @param string|null $expected Expected prior workflow ID.
	 * @throws RuntimeException When a setup safeguard fails.
	 */
	private function save_snapshot( $snapshot, $expected = null ) {
		for ( $attempt = 0; $attempt < 4; $attempt++ ) {
			$before = $this->read_option( $this->state_key );
			$state  = $this->value( $before );
			if ( $before['exists'] && ! is_array( $state ) ) {
				throw new RuntimeException( esc_html__( 'Setup history is not in the expected format. It was preserved without restoring any settings.', 'woocommerce-jetpack' ) );
			}
			$state = is_array( $state ) ? $state : array();
			$prior = isset( $state['invoice_setup_v1'] ) ? $state['invoice_setup_v1'] : null;
			if ( null !== $prior ) {
				$this->validate_snapshot( $prior );
			}
			if ( null !== $expected && ( ! is_array( $prior ) || $expected !== $prior['id'] ) ) {
				throw new RuntimeException( esc_html__( 'Setup history changed. Refresh before continuing.', 'woocommerce-jetpack' ) );
			}
			if ( null === $expected && ! empty( $prior['entries'] ) ) {
				throw new RuntimeException( esc_html__( 'A previous setup still has an undo snapshot. Undo or resolve its conflicts before starting another setup.', 'woocommerce-jetpack' ) );
			}
			$state['invoice_setup_v1'] = $snapshot;
			if ( $this->compare_exchange( $this->state_key, $before, $this->value_state( $state ) ) ) {
				return;
			}
		}
		throw new RuntimeException( esc_html__( 'Setup history changed. Refresh before continuing.', 'woocommerce-jetpack' ) );
	}

	/**
	 * Fail closed for incomplete, foreign or corrupted setup history.
	 *
	 * @param array $snapshot Persisted setup snapshot.
	 * @throws RuntimeException When a setup safeguard fails.
	 */
	private function validate_snapshot( $snapshot ) {
		if ( ! is_array( $snapshot ) || ! isset( $snapshot['id'], $snapshot['status'], $snapshot['document_type'], $snapshot['entries'], $snapshot['settings_hash'] ) || ! is_string( $snapshot['id'] ) || ! preg_match( '/^[a-f0-9]{32}$/D', $snapshot['id'] ) || ! is_string( $snapshot['settings_hash'] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $snapshot['settings_hash'] ) || ! isset( self::documents()[ $snapshot['document_type'] ] ) || ! is_array( $snapshot['entries'] ) || count( $snapshot['entries'] ) > 13 ) {
			throw new RuntimeException( esc_html__( 'Setup history is not in the expected format. It was preserved without restoring any settings.', 'woocommerce-jetpack' ) );
		}
		$allowed = array( 'wcj_pdf_invoicing_enabled' );
		foreach ( array( 'template', 'header_enabled', 'header_image', 'header_image_width_mm', 'header_title_text', 'header_text', 'header_text_color', 'header_line_color', 'footer_enabled', 'footer_text', 'create_on', 'attach_to_emails' ) as $suffix ) {
			$allowed[] = 'wcj_invoicing_' . $snapshot['document_type'] . '_' . $suffix;
		}
		foreach ( $snapshot['entries'] as $key => $entry ) {
			if ( ! in_array( $key, $allowed, true ) || ! is_array( $entry ) ) {
				throw new RuntimeException( esc_html__( 'Setup history contains an unsupported setting. Nothing was restored.', 'woocommerce-jetpack' ) );
			}
			foreach ( array( 'before', 'after' ) as $side ) {
				if ( ! isset( $entry[ $side ]['exists'], $entry[ $side ]['raw'] ) || ! is_bool( $entry[ $side ]['exists'] ) || ! is_string( $entry[ $side ]['raw'] ) || strlen( $entry[ $side ]['raw'] ) > 131072 ) {
					throw new RuntimeException( esc_html__( 'Setup history contains an invalid setting value. Nothing was restored.', 'woocommerce-jetpack' ) );
				}
			}
		}
	}

	/**
	 * Fingerprint every planned setting, including values setup did not need to write.
	 *
	 * @param string     $type Validated document type.
	 * @param array|null $desired Planned values, or null to read current persisted values.
	 * @return string Canonical raw-state fingerprint.
	 */
	private function settings_hash( $type, $desired = null ) {
		$keys = array( 'wcj_pdf_invoicing_enabled' );
		foreach ( array( 'template', 'header_enabled', 'header_image', 'header_image_width_mm', 'header_title_text', 'header_text', 'header_text_color', 'header_line_color', 'footer_enabled', 'footer_text', 'create_on', 'attach_to_emails' ) as $suffix ) {
			$keys[] = 'wcj_invoicing_' . $type . '_' . $suffix;
		}
		$values = array();
		foreach ( $keys as $key ) {
			$values[ $key ] = null === $desired ? $this->read_option( $key ) : $this->value_state( $desired[ $key ] );
		}
		ksort( $values );
		return hash( 'sha256', wp_json_encode( $values ) );
	}

	/**
	 * Acquire an atomic request lock. Never steal a possibly active operation.
	 *
	 * @return array Exact lock identity.
	 * @throws RuntimeException When a setup safeguard fails.
	 */
	private function acquire_lock() {
		self::authorize();
		$lock = $this->value_state( bin2hex( random_bytes( 16 ) ) );
		if ( ! $this->compare_exchange(
			'wcj_invoice_setup_lock',
			array(
				'exists' => false,
				'raw'    => '',
			),
			$lock
		) ) {
			throw new RuntimeException( esc_html__( 'Another setup operation is running. Retry after it finishes. An interrupted lock needs administrator review.', 'woocommerce-jetpack' ) );
		}
		return $lock;
	}

	/**
	 * Activate with the same lock for request and direct service callers.
	 *
	 * @param array  $input Plain draft.
	 * @param string $fingerprint Reviewed exact plan.
	 * @return array Result.
	 */
	public function activate( $input, $fingerprint ) {
		self::authorize();
		$lock = $this->acquire_lock();
		try {
			return $this->activate_locked( $input, $fingerprint );
		} finally {
			$this->compare_exchange(
				'wcj_invoice_setup_lock',
				$lock,
				array(
					'exists' => false,
					'raw'    => '',
				)
			);
		}
	}

	/**
	 * Activate locked.
	 *
	 * @param array  $input Plain draft.
	 * @param string $fingerprint Reviewed exact plan.
	 * @return array Result.
	 * @throws RuntimeException When a setup safeguard fails.
	 * @throws Throwable When a write fails after the compare-and-restore recovery attempt.
	 */
	private function activate_locked( $input, $fingerprint ) {
		$draft = self::validate( $input );
		$state = $this->value( $this->read_option( $this->state_key ) );
		$prior = isset( $state['invoice_setup_v1'] ) ? $state['invoice_setup_v1'] : array();
		if ( $prior ) {
			$this->validate_snapshot( $prior );
		}
		if ( isset( $prior['draft_hash'], $prior['status'] ) && 'active' === $prior['status'] && hash_equals( $prior['draft_hash'], hash( 'sha256', wp_json_encode( $draft ) ) ) ) {
			if ( ! hash_equals( $prior['settings_hash'], $this->settings_hash( $prior['document_type'] ) ) ) {
				throw new RuntimeException( esc_html__( 'Settings changed after setup. Existing changes were preserved; review advanced settings or undo conflicts.', 'woocommerce-jetpack' ) );
			}
			foreach ( $prior['entries'] as $key => $entry ) {
				if ( $this->read_option( $key ) !== $entry['after'] ) {
					throw new RuntimeException( esc_html__( 'Settings changed after setup. Existing changes were preserved; review advanced settings or undo conflicts.', 'woocommerce-jetpack' ) );
				}
			}
			return array(
				'activated' => true,
				'message'   => __( 'These starter settings are already active. No settings or undo baseline were changed. Email delivery remains unverified.', 'woocommerce-jetpack' ),
			);
		}
		$review = $this->review( $draft );
		if ( ! is_string( $fingerprint ) || ! hash_equals( $review['fingerprint'], $fingerprint ) ) {
			throw new RuntimeException( esc_html__( 'The draft or existing settings changed. Review again before activating.', 'woocommerce-jetpack' ) );
		}
		$this->rehearse( $draft ); // Re-prove actual sample and attachment construction; never trust a browser flag.
		$plan = $this->plan( $draft );
		if ( ! hash_equals( $fingerprint, hash( 'sha256', wp_json_encode( array( $draft, $plan ) ) ) ) ) {
			throw new RuntimeException( esc_html__( 'Settings changed during the check. Review again.', 'woocommerce-jetpack' ) );
		}
		$snapshot = array(
			'id'            => bin2hex( random_bytes( 16 ) ),
			'status'        => 'applying',
			'draft_hash'    => hash( 'sha256', wp_json_encode( $draft ) ),
			'settings_hash' => $this->settings_hash( $draft['document_type'], $plan['desired'] ),
			'document_type' => $draft['document_type'],
			'entries'       => array(),
			'created_at'    => gmdate( 'c' ),
		);
		foreach ( $plan['desired'] as $key => $value ) {
			$after = $this->value_state( $value );
			if ( $plan['before'][ $key ] !== $after ) {
				$snapshot['entries'][ $key ] = array(
					'before' => $plan['before'][ $key ],
					'after'  => $after,
				);
			}
		}
		$this->validate_snapshot( $snapshot );
		$this->save_snapshot( $snapshot ); // Journal precedes production writes, allowing safe recovery after interruption.
		$applied = array();
		try {
			foreach ( $snapshot['entries'] as $key => $entry ) {
				if ( 'wcj_pdf_invoicing_enabled' === $key && $this->other_documents( $draft['document_type'] ) !== $plan['other_documents'] ) {
					throw new RuntimeException( esc_html__( 'Other document rules changed. Activation stopped.', 'woocommerce-jetpack' ) );
				}
				foreach ( $plan['guards'] as $guard => $state ) {
					if ( $this->read_option( $guard ) !== $state ) {
						throw new RuntimeException( esc_html__( 'An operation prerequisite changed. Activation stopped.', 'woocommerce-jetpack' ) );
					}
				}
				if ( ! $this->compare_exchange( $key, $entry['before'], $entry['after'] ) ) {
					throw new RuntimeException( esc_html__( 'A setting changed concurrently. Activation stopped without overwriting it.', 'woocommerce-jetpack' ) );
				}
				$applied[ $key ] = $entry;
			}
			if ( ! hash_equals( $snapshot['settings_hash'], $this->settings_hash( $draft['document_type'] ) ) ) {
				throw new RuntimeException( esc_html__( 'A setting changed concurrently. Activation stopped without overwriting it.', 'woocommerce-jetpack' ) );
			}
			$snapshot['status'] = 'active';
			$this->save_snapshot( $snapshot, $snapshot['id'] );
		} catch ( Throwable $error ) {
			$conflicts = array();
			foreach ( array_reverse( $applied, true ) as $key => $entry ) {
				$other_document_dependency = 'wcj_pdf_invoicing_enabled' === $key && 'yes' !== $this->value( $entry['before'] ) && $this->parent_needed_by_later_recipe( $draft['document_type'], $snapshot['entries'] );
				if ( $other_document_dependency || ! $this->compare_exchange( $key, $entry['after'], $entry['before'] ) ) {
					$conflicts[ $key ] = $entry;
				}
			}
			$snapshot['status']  = 'incomplete';
			$snapshot['entries'] = $conflicts;
			$this->save_snapshot( $snapshot, $snapshot['id'] );
			throw $error;
		}
		return array(
			'activated' => true,
			'message'   => __( 'The reviewed starter settings are active. A synthetic PDF and attachment were tested; customer email delivery remains unverified. Undo is available below.', 'woocommerce-jetpack' ),
		);
	}

	/**
	 * Undo.
	 *
	 * @return array Restore only options still equal to this setup's writes.
	 */
	public function undo() {
		self::authorize();
		$lock = $this->acquire_lock();
		try {
			return $this->undo_locked();
		} finally {
			$this->compare_exchange(
				'wcj_invoice_setup_lock',
				$lock,
				array(
					'exists' => false,
					'raw'    => '',
				)
			);
		}
	}

	/**
	 * Undo locked.
	 *
	 * @return array Conditional restoration while holding the operation lock.
	 * @throws RuntimeException When a setup safeguard fails.
	 */
	private function undo_locked() {
		$state    = $this->value( $this->read_option( $this->state_key ) );
		$snapshot = isset( $state['invoice_setup_v1'] ) ? $state['invoice_setup_v1'] : null;
		if ( ! is_array( $snapshot ) || empty( $snapshot['entries'] ) ) {
			throw new RuntimeException( esc_html__( 'No starter setup changes remain to undo.', 'woocommerce-jetpack' ) );
		}
		$this->validate_snapshot( $snapshot );
		$remaining = array();
		foreach ( array_reverse( $snapshot['entries'], true ) as $key => $entry ) {
			$current = $this->read_option( $key );
			if ( $current === $entry['before'] ) {
				continue; // Already restored or a write never occurred after an interrupted activation.
			}
			// Preserve the parent for later recipes, including an edited selected document.
			$other_document_dependency = 'wcj_pdf_invoicing_enabled' === $key && 'yes' !== $this->value( $entry['before'] ) && $this->parent_needed_by_later_recipe( $snapshot['document_type'], $snapshot['entries'] );
			if ( $other_document_dependency || ! $this->compare_exchange( $key, $entry['after'], $entry['before'] ) ) {
				$remaining[ $key ] = $entry;
			}
		}
		$snapshot['entries'] = $remaining;
		$snapshot['status']  = $remaining ? 'conflicts' : 'undone';
		$this->save_snapshot( $snapshot, $snapshot['id'] );
		return array(
			'restored'  => empty( $remaining ),
			'conflicts' => array_keys( $remaining ),
			'message'   => $remaining ? __( 'Later edits were preserved. The listed conflicts remain in the undo snapshot; review them in advanced settings.', 'woocommerce-jetpack' ) : __( 'Starter settings restored. Orders, issued invoices and invoice numbers were not changed.', 'woocommerce-jetpack' ),
		);
	}

	/**
	 * Whether disabling the parent would also disable a later configured recipe.
	 *
	 * @param string $selected Selected document type.
	 * @param array  $entries This operation's exact write journal.
	 * @return bool True when a current recipe is not owned by this journal.
	 */
	private function parent_needed_by_later_recipe( $selected, $entries ) {
		if ( $this->other_documents( $selected ) ) {
			return true;
		}
		$key     = 'wcj_invoicing_' . $selected . '_create_on';
		$current = $this->read_option( $key );
		return (bool) $this->value( $current ) && ( ! isset( $entries[ $key ] ) || $current !== $entries[ $key ]['after'] );
	}

	/**
	 * Is active.
	 *
	 * @return bool True only for a proved, still-current starter activation.
	 */
	public function is_active() {
		self::authorize();
		$state = $this->value( $this->read_option( $this->state_key ) );
		$saved = isset( $state['invoice_setup_v1'] ) ? $state['invoice_setup_v1'] : array();
		if ( ! isset( $saved['status'] ) || 'active' !== $saved['status'] ) {
			return false;
		}
		$this->validate_snapshot( $saved );
		if ( ! hash_equals( $saved['settings_hash'], $this->settings_hash( $saved['document_type'] ) ) ) {
			return false;
		}
		foreach ( $saved['entries'] as $key => $entry ) {
			if ( $this->read_option( $key ) !== $entry['after'] ) {
				return false;
			}
		}
		return 'yes' === $this->value( $this->read_option( 'wcj_pdf_invoicing_enabled' ) );
	}

	/**
	 * Authenticated POST adapter. Public service methods also enforce capability.
	 *
	 * @throws RuntimeException When a setup safeguard fails.
	 */
	public function handle_request() {
		try {
			self::authorize();
			$action = isset( $_POST['action'] ) && is_string( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$op     = str_replace( 'wcj_invoice_setup_', '', $action );
			if ( ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) || ! in_array( $op, array( 'sample', 'rehearse', 'review', 'activate', 'undo' ), true ) ) {
				throw new RuntimeException( esc_html__( 'Invalid setup request.', 'woocommerce-jetpack' ) );
			}
			if ( ! isset( $_POST['nonce'] ) || ! is_string( $_POST['nonce'] ) || strlen( $_POST['nonce'] ) > 32 ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput -- Bound raw type and byte length before the immediately following core nonce verification.
				throw new RuntimeException( esc_html__( 'Invalid setup nonce.', 'woocommerce-jetpack' ) );
			}
			check_ajax_referer( 'wcj_invoice_setup_' . $op, 'nonce' );
			if ( array_diff( array_keys( $_POST ), array( 'action', 'nonce', 'draft', 'fingerprint' ) ) ) {
				throw new RuntimeException( esc_html__( 'Unknown setup request field.', 'woocommerce-jetpack' ) );
			}
			$input = isset( $_POST['draft'] ) ? wp_unslash( $_POST['draft'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Structured plain fields are strictly validated by the service.
			if ( 'sample' === $op ) {
				$bytes  = WCJ_PDF_Invoice::setup_sample_pdf( $input );
				$result = array(
					'pdf'     => base64_encode( $bytes ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Binary PDF transport, never executable content.
					'message' => __( 'Starter sample generated. It does not preview a custom template, real order, final number, advanced styling or legal/tax correctness.', 'woocommerce-jetpack' ),
				);
			} elseif ( 'activate' === $op ) {
				$result = $this->activate( $input, isset( $_POST['fingerprint'] ) ? wp_unslash( $_POST['fingerprint'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Service requires an exact SHA-256 fingerprint.
			} elseif ( 'undo' === $op ) {
				$result = $this->undo();
			} else {
				$result = $this->$op( $input );
			}
			nocache_headers();
			wp_send_json_success( $result );
		} catch ( Throwable $error ) {
			nocache_headers();
			$message = $error instanceof RuntimeException ? $error->getMessage() : __( 'Setup could not complete. No success is being claimed; refresh and review the current settings.', 'woocommerce-jetpack' );
			wp_send_json_error( array( 'message' => $message ), 400 );
		}
	}

	/**
	 * Existing onboarding page calls this view.
	 */
	public function render() {
		self::authorize();
		wp_enqueue_media();
		wp_enqueue_script( 'wcj-invoice-setup', wcj_plugin_url() . '/assets/js/admin/wcj-invoice-setup.js', array(), '1.0', true );
		$nonces = array();
		foreach ( array( 'sample', 'rehearse', 'review', 'activate', 'undo' ) as $op ) {
			$nonces[ $op ] = wp_create_nonce( 'wcj_invoice_setup_' . $op );
		}
		wp_localize_script(
			'wcj-invoice-setup',
			'wcjInvoiceSetup',
			array(
				'url'              => admin_url( 'admin-ajax.php' ),
				'nonces'           => $nonces,
				'working'          => __( 'Working...', 'woocommerce-jetpack' ),
				'failed'           => __( 'The operation failed. No success is being claimed; refresh and review the setup state.', 'woocommerce-jetpack' ),
				'changed'          => __( 'Draft changed. Generate a new sample and review before activation.', 'woocommerce-jetpack' ),
				'chooseLogo'       => __( 'Choose a local PNG or JPEG logo', 'woocommerce-jetpack' ),
				'useLogo'          => __( 'Use this logo', 'woocommerce-jetpack' ),
				'noLogo'           => __( 'No logo selected.', 'woocommerce-jetpack' ),
				'logoSelected'     => __( 'Logo selected. Generate a sample to validate it.', 'woocommerce-jetpack' ),
				'mediaUnavailable' => __( 'The Media Library could not open. You can use an existing attachment ID in the advanced field.', 'woocommerce-jetpack' ),
				'noChanges'        => __( 'These settings already match the reviewed starter design.', 'woocommerce-jetpack' ),
			)
		);
		include __DIR__ . '/views/invoice-setup.php';
	}
}

<?php
/**
 * Booster for WooCommerce - Module - Offer Price
 *
 * @version 2.9.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Offer_Price' ) ) :

class WCJ_Offer_Price extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    more "Offer price" button position options
	 * @todo    ~ per product
	 * @todo    for all products with empty price
	 * @todo    (maybe) variations and grouped products
	 */
	function __construct() {

		$this->id         = 'offer_price';
		$this->short_desc = __( 'Offer Your Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Let your customers to suggest their price for products in WooCommerce.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-offer-your-product-price';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( 'yes' === get_option( 'wcj_offer_price_enabled_for_all_products', 'no' ) || 'yes' === get_option( 'wcj_offer_price_enabled_per_product', 'no' ) ) {
				add_action( 'woocommerce_single_product_summary', array( $this, 'add_offer_price_button' ), 31 );
				add_action( 'wp_enqueue_scripts',                 array( $this, 'enqueue_scripts' ) );
				add_action( 'init',                               array( $this, 'offer_price' ) );
				if ( 'yes' === get_option( 'wcj_offer_price_enabled_per_product', 'no' ) ) {
					add_action( 'add_meta_boxes',                 array( $this, 'add_meta_box' ) );
					add_action( 'save_post_product',              array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				}
			}
			add_action( 'add_meta_boxes',                         array( $this, 'add_offer_price_history_meta_box' ) );
			add_action( 'save_post_product',                      array( $this, 'delete_offer_price_product_history' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * delete_offer_price_product_history.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    validate wcj meta box
	 * @todo    (maybe) add successful deletion notice
	 */
	function delete_offer_price_product_history( $post_id, $post ) {
		if ( isset( $_POST['wcj_offer_price_delete_history'] ) ) {
			delete_post_meta( $post_id, '_' . 'wcj_price_offers' );
			add_action( 'admin_notices', array( $this, 'notice_delete_offer_price_product_history' ) );
		}
	}

	/**
	 * add_offer_price_history_meta_box.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function add_offer_price_history_meta_box() {
		add_meta_box(
			'wc-booster-offer-price-history',
			__( 'Booster: Offer Price History', 'woocommerce-jetpack' ),
			array( $this, 'create_offer_price_history_meta_box' ),
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * create_offer_price_history_meta_box.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) more info (e.g. average offer price)
	 */
	function create_offer_price_history_meta_box() {
		if ( '' == ( $price_offers = get_post_meta( get_the_ID(), '_' . 'wcj_price_offers', true ) ) ) {
			echo '<em>' . __( 'No price offers yet.', 'woocommerce-jetpack' ) . '</em>';
		} else {
			$table_data = array();
			$table_data[] = array(
				__( 'Date', 'woocommerce-jetpack' ),
				__( 'Price', 'woocommerce-jetpack' ),
				__( 'Message', 'woocommerce-jetpack' ),
				__( 'Name', 'woocommerce-jetpack' ),
				__( 'Email', 'woocommerce-jetpack' ),
			);
			$date_ant_time_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			$price_offers = array_reverse( $price_offers );
			foreach ( $price_offers as $price_offer ) {
				$table_data[] = array(
					date_i18n( $date_ant_time_format, $price_offer['offer_timestamp'] ),
					wc_price( $price_offer['offered_price'], array( 'currency' => $price_offer['currency_code'] ) ),
					$price_offer['customer_message'],
					$price_offer['customer_name'],
					$price_offer['customer_email'],
				);
			}
			echo wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) );
			echo '<p>' .
				'<input type="checkbox" id="wcj_offer_price_delete_history" name="wcj_offer_price_delete_history">' .
				'<label for="wcj_offer_price_delete_history">' . __( 'Delete history', 'woocommerce-jetpack' ) . '</label>' .
				wc_help_tip( __( 'Update product after checking the box.', 'woocommerce-jetpack' ) ) .
			'</p>';
		}
	}

	/**
	 * enqueue_scripts.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @see     https://www.w3schools.com/howto/howto_css_modals.asp
	 */
	function enqueue_scripts() {
		wp_enqueue_style(   'wcj-offer-price',    wcj_plugin_url() . '/includes/css/wcj-offer-price.css', array(),           WCJ()->version );
		wp_enqueue_script(  'wcj-offer-price-js', wcj_plugin_url() . '/includes/js/wcj-offer-price.js',   array( 'jquery' ), WCJ()->version, true );
	}

	/**
	 * is_offer_price_enabled_for_product.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function is_offer_price_enabled_for_product( $product_id ) {
		if ( 'yes' === get_option( 'wcj_offer_price_enabled_per_product', 'no' ) ) {
			return ( 'yes' === get_post_meta( $product_id, '_' . 'wcj_offer_price_enabled', true ) );
		}
		return ( 'yes' === get_option( 'wcj_offer_price_enabled_for_all_products', 'no' ) );
	}

	/**
	 * add_offer_price_button.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) optional, additional and custom form fields
	 * @todo    archives
	 * @todo    customizable fields labels
	 * @todo    form template
	 * @todo    ~ empty footer / header
	 * @todo    wcj-close etc.
	 * @todo    do_shortcode
	 * @todo    more info if logged user (e.g. user id)
	 * @todo    logged user - check `nickname` and `billing_email`
	 * @todo    ~ required asterix
	 * @todo    style options for input fields
	 */
	function add_offer_price_button() {
		$product_id = get_the_ID();
		// Check if enabled for current product
		if ( ! $this->is_offer_price_enabled_for_product( $product_id ) ) {
			return;
		}
		// Prepare logged user data
		$customer_name  = '';
		$customer_email = '';
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			if ( '' != ( $meta = get_user_meta( $current_user->ID, 'nickname', true ) ) ) {
				$customer_name = $meta;
			}
			if ( '' != ( $meta = get_user_meta( $current_user->ID, 'billing_email', true ) ) ) {
				$customer_email = $meta;
			}
		}
		// Initial button
		$make_offer_button = '<p>' .
			'<button type="submit" name="wcj-offer-price-button" id="wcj-offer-price-button" value="' . $product_id . '" class="button alt" style="">' .
				get_option( 'wcj_offer_price_button_label', __( 'Make an offer', 'woocommerce-jetpack' ) ) .
			'</button>' .
		'</p>';
		// Offer form - header
		$offer_form_header = str_replace(
			'%product_title%',
			get_the_title(),
			get_option( 'wcj_offer_price_form_header_template', '<h3>' . sprintf( __( 'Suggest your price for %s', 'woocommerce-jetpack' ), '%product_title%' ) . '</h3>' )
		);
		$offer_form_header = '<div class="modal-header">' .
			'<span class="close">&times;</span>' .
			$offer_form_header .
		'</div>';
		// Offer form - footer
		$offer_form_footer = ( '' != ( $footer_template = get_option( 'wcj_offer_price_form_footer_template', '' ) ) ?
			'<div class="modal-footer">' . do_shortcode( $footer_template ) . '</div>' : '' );
		// Offer form - content - price
		$price_step = ( '' === ( $price_step_per_product = get_post_meta( $product_id, '_' . 'wcj_offer_price_price_step', true ) ) ?
			get_option( 'wcj_offer_price_price_step', get_option( 'woocommerce_price_num_decimals' ) ) :
			$price_step_per_product
		);
		$price_step = sprintf( "%f", ( 1 / pow( 10, absint( $price_step ) ) ) );
		$max_price = ( '' === ( $max_price_per_product = get_post_meta( $product_id, '_' . 'wcj_offer_price_max_price', true ) ) ?
			get_option( 'wcj_offer_price_max_price', 0 ) :
			$max_price_per_product
		);
		$max_price_html = ( 0 != $max_price ? ' max="' . $max_price . '"' : '' );
		$min_price = ( '' === ( $min_price_per_product = get_post_meta( $product_id, '_' . 'wcj_offer_price_min_price', true ) ) ?
			get_option( 'wcj_offer_price_min_price', 0 ) :
			$min_price_per_product
		);
		$default_price = ( '' === ( $default_price_per_product = get_post_meta( $product_id, '_' . 'wcj_offer_price_default_price', true ) ) ?
			get_option( 'wcj_offer_price_default_price', 0 ) :
			$default_price_per_product
		);
		$default_price_html = ( 0 != $default_price ? ' value="' . $default_price . '"' : '' );
		$offer_form_content_price = '<label for="wcj-offer-price-price">' .
			str_replace(
				'%currency_symbol%',
				get_woocommerce_currency_symbol(),
				sprintf( __( 'Your price (%s)', 'woocommerce-jetpack' ), '%currency_symbol%' )
			) . ' ' . '<abbr class="required" title="required">*</abbr>' . '</label>' . '<br>' .
			'<input type="number" required id="wcj-offer-price-price" name="wcj-offer-price-price" ' . $default_price_html . 'step="' . $price_step . '"' .
				' min="' . $min_price . '"' . $max_price_html . '>';
		// Offer form - content - email
		$offer_form_content_email = '<label for="wcj-offer-price-customer-email">' . __( 'Your email', 'woocommerce-jetpack' ) .
			' ' . '<abbr class="required" title="required">*</abbr>' . '</label>' . '<br>' .
			'<input type="email" required id="wcj-offer-price-customer-email" name="wcj-offer-price-customer-email" value="' . $customer_email . '">';
		// Offer form - content - name
		$offer_form_content_name = '<label for="wcj-offer-price-customer-name">' . __( 'Your name', 'woocommerce-jetpack' ) .
			'</label>' . '<br>' .
			'<input type="text" id="wcj-offer-price-customer-name" name="wcj-offer-price-customer-name" value="' . $customer_name . '">';
		// Offer form - content - message
		$offer_form_content_message = '<label for="wcj-offer-price-message">' . __( 'Your message', 'woocommerce-jetpack' ) . '</label>' . '<br>' .
			'<textarea id="wcj-offer-price-message" name="wcj-offer-price-message"></textarea>';
		// Offer form - content - button
		$offer_form_content_button = '<input type="submit" id="wcj-offer-price-submit" name="wcj-offer-price-submit" value="' .
			get_option( 'wcj_offer_price_form_button_label', __( 'Send', 'woocommerce-jetpack' ) ) . '">';
		// Offer form - content
		$offer_form_content = '<div class="modal-body">' .
			'<form method="post">' .
				'<p>' . $offer_form_content_price . '</p>' .
				'<p>' . $offer_form_content_email . '</p>' .
				'<p>' . $offer_form_content_name . '</p>' .
				'<p>' . $offer_form_content_message . '</p>' .
				'<p>' . $offer_form_content_button . '</p>' .
				'<input type="hidden" name="wcj-offer-price-product-id" value="' . $product_id . '">' .
			'</form>' .
		'</div>';
		// Offer form
		$offer_form = '<div id="wcj-offer-price-modal" class="modal">' .
			'<div class="modal-content">' .
				$offer_form_header .
				$offer_form_content .
				$offer_form_footer .
			'</div>' .
		'</div>';
		// Final output
		echo $make_offer_button . $offer_form;
	}

	/**
	 * offer_price.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    check if mail has really been sent
	 * @todo    "send a copy to me (i.e. customer)" checkbox
	 * @todo    (maybe) sanitize $_POST
	 * @todo    check multicurrency
	 * @todo    ! "From" header
	 */
	function offer_price() {
		if ( isset( $_POST['wcj-offer-price-submit'] ) ) {
			$_product_id = $_POST['wcj-offer-price-product-id'];
			$_product    = wc_get_product( $_product_id );
			$price_offer =  array(
				'offer_timestamp'  => current_time( 'timestamp' ),
				'product_title'    => $_product->get_title(),
				'offered_price'    => $_POST['wcj-offer-price-price'],
				'currency_code'    => get_woocommerce_currency(),
				'customer_message' => $_POST['wcj-offer-price-message'],
				'customer_name'    => $_POST['wcj-offer-price-customer-name'],
				'customer_email'   => $_POST['wcj-offer-price-customer-email'],
			);
			// Email
			$email_template = get_option( 'wcj_offer_price_email_template',
				sprintf( __( 'Product: %s', 'woocommerce-jetpack' ),       '%product_title%' ) . '<br>' . PHP_EOL .
				sprintf( __( 'Offered price: %s', 'woocommerce-jetpack' ), '%offered_price%' ) . '<br>' . PHP_EOL .
				sprintf( __( 'From: %s %s', 'woocommerce-jetpack' ),       '%customer_name%', '%customer_email%' ) . '<br>' . PHP_EOL .
				sprintf( __( 'Message: %s', 'woocommerce-jetpack' ),       '%customer_message%' )
			);
			$replaced_values = array(
				'%product_title%'    => $price_offer['product_title'],
				'%offered_price%'    => wc_price( $price_offer['offered_price'] ),
				'%customer_message%' => $price_offer['customer_message'],
				'%customer_name%'    => $price_offer['customer_name'],
				'%customer_email%'   => $price_offer['customer_email'],
			);
			$email_content = str_replace( array_keys( $replaced_values ), array_values( $replaced_values ), $email_template );
			if ( '' == ( $email_address = get_option( 'wcj_offer_price_email_address', '' ) ) ) {
				$email_address = get_option( 'admin_email' );
			}
			$email_subject = get_option( 'wcj_offer_price_email_subject', __( 'Price Offer', 'woocommerce-jetpack' ) );
			$email_headers = 'Content-Type: text/html' . "\r\n" .
				'From: ' . $price_offer['customer_name'] . ' <' . $price_offer['customer_email'] . '>' . "\r\n" .
				'Reply-To: ' . $price_offer['customer_email'] . "\r\n";
			wc_mail( $email_address, $email_subject, $email_content, $email_headers );
			// Notice
			wc_add_notice( get_option( 'wcj_offer_price_customer_notice', __( 'Your price offer has been sent.', 'woocommerce-jetpack' ) ), 'notice' );
			// Product meta (Offer Price History)
			if ( '' == ( $price_offers = get_post_meta( $_product_id, '_' . 'wcj_price_offers', true ) ) ) {
				$price_offers = array();
			}
			$price_offers[] = $price_offer;
			update_post_meta( $_product_id, '_' . 'wcj_price_offers', $price_offers );
		}
	}

}

endif;

return new WCJ_Offer_Price();

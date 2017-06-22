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
	 * @todo    !!! dev
	 * @todo    ! per product (rethink 'Enable for All Products' and 'Enable per Product' compatibility)
	 * @todo    ! are you sure about wp_footer (didn't work with PHP_INT_MAX)? maybe wp_head???
	 * @todo    (maybe) add 'enable for all products with empty price' option
	 * @todo    (maybe) recheck multicurrency
	 * @todo    (maybe) more "Offer price" button position options (on both single and archives)
	 * @todo    (maybe) variations and grouped products
	 * @todo    (maybe) add shortcode
	 * @todo    (maybe) option for disabling offers history
	 * @todo    (maybe) global (i.e. for all products) offers history
	 */
	function __construct() {

		$this->id         = 'offer_price';
		$this->short_desc = __( 'Offer Your Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Let your customers to suggest their price for products in WooCommerce.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-offer-your-product-price';
		parent::__construct();

		$this->dev = true;

		if ( $this->is_enabled() ) {
			if ( 'yes' === get_option( 'wcj_offer_price_enabled_for_all_products', 'no' ) || 'yes' === get_option( 'wcj_offer_price_enabled_per_product', 'no' ) ) {
				if ( 'disable' != ( $_hook = get_option( 'wcj_offer_price_button_position', 'woocommerce_single_product_summary' ) ) ) {
					add_action(
						$_hook,
						array( $this, 'add_offer_price_button' ),
						get_option( 'wcj_offer_price_button_position_priority', 31 )
					);
				}
				if ( 'disable' != ( $_hook = get_option( 'wcj_offer_price_button_position_archives', 'woocommerce_after_shop_loop_item' ) ) ) {
					add_action(
						$_hook,
						array( $this, 'add_offer_price_button' ),
						get_option( 'wcj_offer_price_button_position_priority_archives', 10 )
					);
				}
				add_action( 'wp_footer',                          array( $this, 'add_offer_price_form' ) );
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
	 * @todo    (maybe) validate wcj meta box
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
	 */
	function create_offer_price_history_meta_box() {
		if ( '' == ( $price_offers = get_post_meta( get_the_ID(), '_' . 'wcj_price_offers', true ) ) ) {
			echo '<em>' . __( 'No price offers yet.', 'woocommerce-jetpack' ) . '</em>';
		} else {
			$average_offers = array();
			$table_data = array();
			$table_data[] = array(
				__( 'Date', 'woocommerce-jetpack' ),
				__( 'Price', 'woocommerce-jetpack' ),
				__( 'Message', 'woocommerce-jetpack' ),
				__( 'Name', 'woocommerce-jetpack' ),
				__( 'Email', 'woocommerce-jetpack' ),
				__( 'Customer ID', 'woocommerce-jetpack' ),
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
					$price_offer['customer_id'],
				);
				if ( ! isset( $average_offers[ $price_offer['currency_code'] ] ) ) {
					$average_offers[ $price_offer['currency_code'] ] = array( 'total_offers' => 0, 'offers_sum' => 0 );
				}
				$average_offers[ $price_offer['currency_code'] ]['total_offers']++;
				$average_offers[ $price_offer['currency_code'] ]['offers_sum'] += $price_offer['offered_price'];
			}
			echo wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) );
			foreach ( $average_offers as $average_offer_currency_code => $average_offer_data ) {
				echo '<p>' . sprintf( __( 'Average offer: %s (from %s offer(s))', 'woocommerce-jetpack' ),
					wc_price( ( $average_offer_data['offers_sum'] / $average_offer_data['total_offers'] ), array( 'currency' => $average_offer_currency_code ) ),
					$average_offer_data['total_offers']
				) . '</p>';
			}
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
	 * @todo    (maybe) enqueue only if really needed
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
	 * get_wcj_data_array.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    + check if per product is enabled
	 * @todo    (maybe) recheck `str_replace( '\'', '"', ... )`
	 */
	function get_wcj_data_array( $product_id ) {
		$is_per_product_enabled = ( 'yes' === get_option( 'wcj_offer_price_enabled_per_product', 'no' ) );
		// Price input - price step
		$price_step = ( ! $is_per_product_enabled || '' === ( $price_step_per_product = get_post_meta( $product_id, '_' . 'wcj_offer_price_price_step', true ) ) ?
			get_option( 'wcj_offer_price_price_step', get_option( 'woocommerce_price_num_decimals' ) ) :
			$price_step_per_product
		);
		$price_step = sprintf( "%f", ( 1 / pow( 10, absint( $price_step ) ) ) );
		// Price input - min price
		$min_price = ( ! $is_per_product_enabled || '' === ( $min_price_per_product = get_post_meta( $product_id, '_' . 'wcj_offer_price_min_price', true ) ) ?
			get_option( 'wcj_offer_price_min_price', 0 ) :
			$min_price_per_product
		);
		// Price input - max price
		$max_price = ( ! $is_per_product_enabled || '' === ( $max_price_per_product = get_post_meta( $product_id, '_' . 'wcj_offer_price_max_price', true ) ) ?
			get_option( 'wcj_offer_price_max_price', 0 ) :
			$max_price_per_product
		);
		// Price input - default price
		$default_price = ( ! $is_per_product_enabled || '' === ( $default_price_per_product = get_post_meta( $product_id, '_' . 'wcj_offer_price_default_price', true ) ) ?
			get_option( 'wcj_offer_price_default_price', 0 ) :
			$default_price_per_product
		);
		// Price input - label
		$price_label = str_replace(
			'%currency_symbol%',
			get_woocommerce_currency_symbol(),
			sprintf( __( 'Your price (%s)', 'woocommerce-jetpack' ), '%currency_symbol%' )
		);
		// Offer form - header
		$form_header = str_replace(
			'%product_title%',
			get_the_title(),
			get_option( 'wcj_offer_price_form_header_template', '<h3>' . sprintf( __( 'Suggest your price for %s', 'woocommerce-jetpack' ), '%product_title%' ) . '</h3>' )
		);
		return array(
			'price_step'    => $price_step,
			'min_price'     => $min_price,
			'max_price'     => $max_price,
			'default_price' => $default_price,
			'price_label'   => str_replace( '\'', '"', $price_label ),
			'form_header'   => str_replace( '\'', '"', $form_header ),
			'product_id'    => $product_id,
		);
	}

	/**
	 * add_offer_price_form.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    customizable fields labels
	 * @todo    form template
	 * @todo    ~ empty footer / header
	 * @todo    ~ wcj-close etc.
	 * @todo    (maybe) do_shortcode
	 * @todo    + more info if logged user (e.g. user id)
	 * @todo    (maybe) logged user - check `nickname` and `billing_email`
	 * @todo    (maybe) better required asterix default
	 * @todo    style options for input fields
	 * @todo    (maybe) optional, additional and custom form fields
	 * @todo    (maybe) "send a copy to me (i.e. customer)" checkbox
	 */
	function add_offer_price_form() {
		// Prepare logged user data
		$customer_name  = '';
		$customer_email = '';
		$customer_id    = 0;
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$customer_id  = $current_user->ID;
			if ( '' != ( $meta = get_user_meta( $current_user->ID, 'nickname', true ) ) ) {
				$customer_name = $meta;
			}
			if ( '' != ( $meta = get_user_meta( $current_user->ID, 'billing_email', true ) ) ) {
				$customer_email = $meta;
			}
		}
		// Header
		$offer_form_header = '<div class="wcj-offer-modal-header">' .
			'<span class="wcj-offer-price-form-close">&times;</span>' . '<div id="wcj-offer-form-header"></div>' .
		'</div>';
		// Footer
		$offer_form_footer = ( '' != ( $footer_template = get_option( 'wcj_offer_price_form_footer_template', '' ) ) ?
			'<div class="wcj-offer-price-modal-footer">' . /* do_shortcode */( $footer_template ) . '</div>' : '' );
		// Required HTML
		$required_html = get_option( 'wcj_offer_price_form_required_html', ' <abbr class="required" title="required">*</abbr>' );
		// Content - price
		$offer_form_content_price = '<label for="wcj-offer-price-price">' .
			'<span id="wcj-offer-price-price-label"></span>' . $required_html . '</label>' . '<br>' .
			'<input type="number" required id="wcj-offer-price-price" name="wcj-offer-price-price">';
		// Content - email
		$offer_form_content_email = '<label for="wcj-offer-price-customer-email">' . __( 'Your email', 'woocommerce-jetpack' ) .
			$required_html . '</label>' . '<br>' .
			'<input type="email" required id="wcj-offer-price-customer-email" name="wcj-offer-price-customer-email" value="' . $customer_email . '">';
		// Content - name
		$offer_form_content_name = '<label for="wcj-offer-price-customer-name">' . __( 'Your name', 'woocommerce-jetpack' ) .
			'</label>' . '<br>' .
			'<input type="text" id="wcj-offer-price-customer-name" name="wcj-offer-price-customer-name" value="' . $customer_name . '">';
		// Content - message
		$offer_form_content_message = '<label for="wcj-offer-price-message">' . __( 'Your message', 'woocommerce-jetpack' ) . '</label>' . '<br>' .
			'<textarea id="wcj-offer-price-message" name="wcj-offer-price-message"></textarea>';
		// Content - button
		$offer_form_content_button = '<input type="submit" id="wcj-offer-price-submit" name="wcj-offer-price-submit" value="' .
			get_option( 'wcj_offer_price_form_button_label', __( 'Send', 'woocommerce-jetpack' ) ) . '">';
		// Content
		$offer_form_content = '<div class="wcj-offer-price-modal-body">' .
			'<form method="post">' .
				'<p>' . $offer_form_content_price . '</p>' .
				'<p>' . $offer_form_content_email . '</p>' .
				'<p>' . $offer_form_content_name . '</p>' .
				'<p>' . $offer_form_content_message . '</p>' .
				'<p>' . $offer_form_content_button . '</p>' .
				'<input type="hidden" id="wcj-offer-price-product-id" name="wcj-offer-price-product-id">' .
				'<input type="hidden" name="wcj-offer-price-customer-id" value="' . $customer_id . '">' .
			'</form>' .
		'</div>';
		// Final form
		echo '<div id="wcj-offer-price-modal" class="wcj-offer-price-modal">' .
			'<div class="wcj-offer-price-modal-content">' .
				$offer_form_header .
				$offer_form_content .
				$offer_form_footer .
			'</div>' .
		'</div>';
	}

	/**
	 * add_offer_price_button.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function add_offer_price_button() {
		$product_id = get_the_ID();
		// Check if enabled for current product
		if ( ! $this->is_offer_price_enabled_for_product( $product_id ) ) {
			return;
		}
		// The button
		echo '<p>' .
			'<button type="submit"' .
				' name="wcj-offer-price-button"' .
				' class="wcj-offer-price-button"' .
				' value="' . $product_id . '"' .
				' class="' . get_option( 'wcj_offer_price_button_class', 'button' ) . '"' .
				' style="' . get_option( 'wcj_offer_price_button_style', '' ) . '"' .
				' wcj_data=\'' . json_encode( $this->get_wcj_data_array( $product_id ) ) . '\'' .
			'>' .
				get_option( 'wcj_offer_price_button_label', __( 'Make an offer', 'woocommerce-jetpack' ) ) .
			'</button>' .
		'</p>';
	}

	/**
	 * offer_price.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) redirect (no notice though)
	 * @todo    (maybe) `%product_title%` etc. in notice
	 * @todo    (maybe) fix "From" header
	 * @todo    (maybe) check if mail has really been sent
	 * @todo    (maybe) sanitize $_POST
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
				'customer_id'      => $_POST['wcj-offer-price-customer-id'],
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

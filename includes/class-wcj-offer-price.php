<?php
/**
 * Booster for WooCommerce - Module - Offer Price
 *
 * @version 2.8.3
 * @since   2.8.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Offer_Price' ) ) :

class WCJ_Offer_Price extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @todo    more "Offer price" button position options
	 */
	function __construct() {

		$this->id         = 'offer_price';
		$this->short_desc = __( 'Offer Your Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Let your customers to suggest their price for products in WooCommerce.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-offer-your-product-price';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'woocommerce_single_product_summary', array( $this, 'add_offer_price_button' ), 31 );
			add_action( 'wp_enqueue_scripts',                 array( $this, 'enqueue_scripts' ) );
			add_action( 'init',                               array( $this, 'offer_price' ) );
			add_action( 'add_meta_boxes',                     array( $this, 'add_offer_price_history_meta_box' ) );
		}
	}

	/**
	 * add_offer_price_history_meta_box.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
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
	 * @version 2.8.3
	 * @since   2.8.3
	 * @todo    add "delete all price offers" button (with confirmation)
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
					wc_price( $price_offer['offered_price'] ),
					$price_offer['customer_message'],
					$price_offer['customer_name'],
					$price_offer['customer_email'],
				);
			}
			echo wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) );
		}
	}

	/**
	 * enqueue_scripts.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @see     https://www.w3schools.com/howto/howto_css_modals.asp
	 */
	function enqueue_scripts() {
		wp_enqueue_style(   'wcj-offer-price',     wcj_plugin_url() . '/includes/css/wcj-offer-price.css', array(),           WCJ()->version );
		wp_enqueue_script(  'wcj-offer-price-js',  wcj_plugin_url() . '/includes/js/wcj-offer-price.js',   array( 'jquery' ), WCJ()->version, true );
	}

	/**
	 * add_offer_price_button.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @todo    "Submit" button label
	 * @todo    variations and grouped products
	 * @todo    price - default, step, min and max
	 * @todo    optional, additional and custom form fields
	 * @todo    archives
	 * @todo    ~ fields labels
	 * @todo    form template
	 */
	function add_offer_price_button() {
		echo '<p>' .
			'<button type="submit" name="wcj-offer-price-button" id="wcj-offer-price-button" value="' . get_the_ID() . '" class="button alt" style="">' .
				get_option( 'wcj_offer_price_button_label', __( 'Make an offer', 'woocommerce-jetpack' ) ) .
			'</button>' .
		'</p>' .
		'<div id="wcj-offer-price-modal" class="modal">' .
			'<div class="modal-content">' .
				'<div class="modal-header">' .
					'<span class="close">&times;</span>' . // todo - wcj-close
					str_replace(
						'%product_title%',
						get_the_title(),
						get_option( 'wcj_offer_price_form_header_template',
							'<h3>' . sprintf( __( 'Suggest your price for %s', 'woocommerce-jetpack' ), '%product_title%' ) . '</h3>' )
					) .
				'</div>' .
				'<div class="modal-body">' .
					'<form method="post">' .
						'<p>' . '<label for="wcj-offer-price-price">' . __( 'Your price', 'woocommerce-jetpack' ) . '</label>' . '<br>' .
							'<input type="number" required id="wcj-offer-price-price" name="wcj-offer-price-price">' . '</p>' .
						'<p>' . '<label for="wcj-offer-price-customer-name">' . __( 'Your name', 'woocommerce-jetpack' ) . '</label>' . '<br>' .
							'<input type="text" required id="wcj-offer-price-customer-name" name="wcj-offer-price-customer-name">' . '</p>' .
						'<p>' . '<label for="wcj-offer-price-customer-email">' . __( 'Your email', 'woocommerce-jetpack' ) . '</label>' . '<br>' .
							'<input type="email" required id="wcj-offer-price-customer-email" name="wcj-offer-price-customer-email">' . '</p>' .
						'<p>' . '<label for="wcj-offer-price-message">' . __( 'Your message', 'woocommerce-jetpack' ) . '</label>' . '<br>' .
							'<textarea id="wcj-offer-price-message" name="wcj-offer-price-message"></textarea>' . '</p>' .
						'<p>' . '<input type="submit" id="wcj-offer-price-submit" name="wcj-offer-price-submit">' . '</p>' .
						'<input type="hidden" name="wcj-offer-price-product-id" value="' . get_the_ID() . '">' .
					'</form>' .
				'</div>' .
				'<div class="modal-footer">' .
					get_option( 'wcj_offer_price_form_footer_template', '' ) .
				'</div>' .
			'</div>' .
		'</div>';
	}

	/**
	 * offer_price.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @todo    check if mail has really been sent
	 * @todo    "send a copy to me (i.e. customer)" checkbox
	 * @todo    sanitize $_POST
	 * @todo    multicurrency
	 * @todo    ! "From" header
	 * @todo    email_template - customer name and email
	 */
	function offer_price() {
		if ( isset( $_POST['wcj-offer-price-submit'] ) ) {
			$_product = wc_get_product( $_POST['wcj-offer-price-product-id'] );
			$email_template = get_option( 'wcj_offer_price_email_template',
				sprintf( __( 'Product: %s', 'woocommerce-jetpack' ),       '%product_title%' ) . '<br>' . PHP_EOL .
				sprintf( __( 'Offered price: %s', 'woocommerce-jetpack' ), '%offered_price%' ) . '<br>' . PHP_EOL .
				sprintf( __( 'Message: %s', 'woocommerce-jetpack' ),       '%customer_message%' )
			);
			$replaced_values = array(
				'%product_title%'    => $_product->get_title(),
				'%offered_price%'    => $_POST['wcj-offer-price-price'],
				'%customer_message%' => $_POST['wcj-offer-price-message'],
			);
			$email_content = str_replace( array_keys( $replaced_values ), array_values( $replaced_values ), $email_template );
			if ( '' == ( $email_address = get_option( 'wcj_offer_price_email_address', '' ) ) ) {
				$email_address = get_option( 'admin_email' );
			}
			$email_subject = get_option( 'wcj_offer_price_email_subject', __( 'Price Offer', 'woocommerce-jetpack' ) );
			$email_headers = 'Content-Type: text/html' . "\r\n" .
				'From: ' . $_POST['wcj-offer-price-customer-name'] . ' <' . $_POST['wcj-offer-price-customer-email'] . '>' . "\r\n" .
				'Reply-To: ' . $_POST['wcj-offer-price-customer-email'] . "\r\n";
			wc_mail( $email_address, $email_subject, $email_content, $email_headers );
			wc_add_notice( get_option( 'wcj_offer_price_customer_notice', __( 'Your price offer has been sent.', 'woocommerce-jetpack' ) ), 'notice' );
			if ( '' == ( $price_offers = get_post_meta( $_POST['wcj-offer-price-product-id'], '_' . 'wcj_price_offers', true ) ) ) {
				$price_offers = array();
			}
			$price_offers[] = array(
				'offer_timestamp'  => current_time( 'timestamp' ),
				'product_title'    => $_product->get_title(),
				'offered_price'    => $_POST['wcj-offer-price-price'],
				'customer_message' => $_POST['wcj-offer-price-message'],
				'customer_name'    => $_POST['wcj-offer-price-customer-name'],
				'customer_email'   => $_POST['wcj-offer-price-customer-email'],
			);
			update_post_meta( $_POST['wcj-offer-price-product-id'], '_' . 'wcj_price_offers', $price_offers );
		}
	}

}

endif;

return new WCJ_Offer_Price();

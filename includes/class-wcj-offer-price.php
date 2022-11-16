<?php
/**
 * Booster for WooCommerce - Module - Offer Price
 *
 * @version 5.6.8
 * @since   2.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Offer_Price' ) ) :
	/**
	 * WCJ_Offer_Price.
	 */
	class WCJ_Offer_Price extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   2.9.0
		 * @todo    settings - more info about position priorities, e.g.: __( 'Standard priorities for "Inside single product summary": title - 5, rating - 10, price - 10, excerpt - 20, add to cart - 30, meta - 40, sharing - 50', 'woocommerce-jetpack' )
		 * @todo    (maybe) css - customizable fonts etc.
		 * @todo    (maybe) css - better default colors
		 * @todo    (maybe) better solution for the form hook (instead of `woocommerce_before_main_content`)
		 * @todo    (maybe) per product settings - add "use global values/use values below" for price step etc. (instead of placeholders etc.)
		 * @todo    (maybe) recheck multicurrency
		 * @todo    (maybe) more "Make an offer" button position options (on both single and archives)
		 * @todo    (maybe) variations and grouped products
		 * @todo    (maybe) add shortcode
		 * @todo    (maybe) offers history - option for disabling
		 * @todo    (maybe) offers history - global (i.e. for all products)
		 */
		public function __construct() {

			$this->id         = 'offer_price';
			$this->short_desc = __( 'Offer Your Price', 'woocommerce-jetpack' );
			$this->desc       = __( 'Let your customers suggest their price for products (Available for all the products in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Let your customers suggest their price for products.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-offer-your-product-price';
			parent::__construct();

			if ( $this->is_enabled() ) {
				$_hook = wcj_get_option( 'wcj_offer_price_button_position', 'woocommerce_single_product_summary' );
				if ( 'disable' !== ( $_hook ) ) {
					add_action(
						$_hook,
						array( $this, 'add_offer_price_button' ),
						get_option( 'wcj_offer_price_button_position_priority', 31 )
					);
				}
				$_hook = apply_filters( 'booster_option', 'disable', wcj_get_option( 'wcj_offer_price_button_position_archives', 'disable' ) );
				if ( 'disable' !== ( $_hook ) ) {
					add_action(
						$_hook,
						array( $this, 'add_offer_price_button' ),
						get_option( 'wcj_offer_price_button_position_priority_archives', 10 )
					);
				}
				$custom_hooks = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_offer_price_button_position_custom', '' ) );
				if ( ! empty( $custom_hooks ) ) {
					$custom_hooks           = array_filter( array_map( 'trim', explode( '|', $custom_hooks ) ) );
					$custom_hook_priorities = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_offer_price_button_position_priority_custom', '' ) );
					$custom_hook_priorities = array_map( 'trim', explode( '|', $custom_hook_priorities ) );
					foreach ( $custom_hooks as $i => $custom_hook ) {
						add_action(
							$custom_hook,
							array( $this, 'add_offer_price_button' ),
							( isset( $custom_hook_priorities[ $i ] ) ? $custom_hook_priorities[ $i ] : 10 )
						);
					}
				}
				add_action( 'wp_footer', array( $this, 'add_offer_price_form' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'init', array( $this, 'offer_price' ) );
				if ( in_array( apply_filters( 'booster_option', 'all_products', wcj_get_option( 'wcj_offer_price_enabled_type', 'all_products' ) ), array( 'per_product', 'per_product_and_per_category' ), true ) ) {
					add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
					add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				}
				// Offer history.
				add_action( 'add_meta_boxes', array( $this, 'add_offer_price_history_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'delete_offer_price_product_history' ), PHP_INT_MAX, 2 );
				// CSS.
				add_action( 'wp_head', array( $this, 'add_styling' ), PHP_INT_MAX );
			}
		}

		/**
		 * Add_styling.
		 *
		 * @version 5.6.8
		 * @since   3.7.0
		 */
		public function add_styling() {
			$styling_default = array(
				'form_content_width'     => '80%',
				'form_header_back_color' => '#5cb85c',
				'form_header_text_color' => '#ffffff',
				'form_footer_back_color' => '#5cb85c',
				'form_footer_text_color' => '#ffffff',
			);
			$styling_options = wcj_get_option( 'wcj_offer_price_styling', array() );
			foreach ( $styling_default as $option => $default ) {
				if ( ! isset( $styling_options[ $option ] ) ) {
					$styling_options[ $option ] = $default;
				}
			}
			$css = "<style type=\"text/css\">
			.wcj-offer-price-modal-content {width: {$styling_options['form_content_width']};}
			.wcj-offer-modal-header {background-color: {$styling_options['form_header_back_color']};color: {$styling_options['form_header_text_color']};}
			.wcj-offer-modal-header h1, .wcj-offer-modal-header h2, .wcj-offer-modal-header h3, .wcj-offer-modal-header h4, .wcj-offer-modal-header h5, .wcj-offer-modal-header h6 {color: {$styling_options['form_header_text_color']};}
			.wcj-offer-price-modal-footer {background-color: {$styling_options['form_footer_back_color']};color: {$styling_options['form_footer_text_color']};}
			.wcj-offer-price-modal-footer h1, .wcj-offer-price-modal-footer h2, .wcj-offer-price-modal-footer h3, .wcj-offer-price-modal-footer h4, .wcj-offer-price-modal-footer h5, .wcj-offer-price-modal-footer h6 {color: {$styling_options['form_footer_text_color']};}
			</style>";
			echo wp_kses_post( $css );
		}

		/**
		 * Delete_offer_price_product_history.
		 *
		 * @version 5.6.7
		 * @since   2.9.0
		 * @todo    (maybe) add successful deletion notice
		 * @param int            $post_id defines the post_id.
		 * @param string | array $post defines the post.
		 */
		public function delete_offer_price_product_history( $post_id, $post ) {
			$wpnonce = isset( $_POST['woocommerce_meta_nonce'] ) ? wp_verify_nonce( wp_unslash( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ) ), 'woocommerce_save_data' ) : false;
			if ( $wpnonce && isset( $_POST['wcj_offer_price_delete_history'] ) ) {
				delete_post_meta( $post_id, '_wcj_price_offers' );
				add_action( 'admin_notices', array( $this, 'notice_delete_offer_price_product_history' ) );
			}
		}

		/**
		 * Add_offer_price_history_meta_box.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 */
		public function add_offer_price_history_meta_box() {
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
		 * Get_admin_meta_box_columns.
		 *
		 * @version 4.4.0
		 * @since   4.4.0
		 */
		public function get_admin_meta_box_columns() {
			return array(
				'date'             => __( 'Date', 'woocommerce-jetpack' ),
				'offered_price'    => __( 'Price', 'woocommerce-jetpack' ),
				'customer_message' => __( 'Message', 'woocommerce-jetpack' ),
				'customer_name'    => __( 'Name', 'woocommerce-jetpack' ),
				'customer_email'   => __( 'Email', 'woocommerce-jetpack' ),
				'customer_id'      => __( 'Customer ID', 'woocommerce-jetpack' ),
				'user_ip'          => __( 'User IP', 'woocommerce-jetpack' ),
				'user_agent'       => __( 'User Agent', 'woocommerce-jetpack' ),
				'sent_to'          => __( 'Sent to', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Create_offer_price_history_meta_box.
		 *
		 * @version 5.6.8
		 * @since   2.9.0
		 */
		public function create_offer_price_history_meta_box() {
			$price_offers = get_post_meta( get_the_ID(), '_wcj_price_offers', true );
			if ( '' === ( $price_offers ) ) {
				echo '<em>' . wp_kses_post( _e( 'No price offers yet.', 'woocommerce-jetpack' ) ) . '</em>';
			} else {
				$average_offers   = array();
				$all_columns      = $this->get_admin_meta_box_columns();
				$selected_columns = wcj_get_option(
					'wcj_offer_price_admin_meta_box_columns',
					array( 'date', 'offered_price', 'customer_message', 'customer_name', 'customer_email', 'customer_id', 'user_ip', 'sent_to' )
				);
				$table_data       = array();
				$header           = array();
				foreach ( $selected_columns as $selected_column ) {
					$header[] = $all_columns[ $selected_column ];
				}
				$table_data[]         = $header;
				$date_ant_time_format = wcj_get_option( 'date_format' ) . ' ' . wcj_get_option( 'time_format' );
				$price_offers         = array_reverse( $price_offers );
				foreach ( $price_offers as $price_offer ) {
					$row = array();
					foreach ( $selected_columns as $selected_column ) {
						switch ( $selected_column ) {
							case 'date':
								$row[] = date_i18n( $date_ant_time_format, $price_offer['offer_timestamp'] );
								break;
							case 'offered_price':
								$row[] = wc_price( $price_offer['offered_price'], array( 'currency' => $price_offer['currency_code'] ) );
								break;
							case 'customer_message':
								$row[] = $price_offer['customer_message'];
								break;
							case 'customer_name':
								$row[] = $price_offer['customer_name'];
								break;
							case 'customer_email':
								$row[] = $price_offer['customer_email'];
								break;
							case 'customer_id':
								$row[] = $price_offer['customer_id'];
								break;
							case 'user_ip':
								$row[] = ( isset( $price_offer['user_ip'] ) ? $price_offer['user_ip'] : '' );
								break;
							case 'user_agent':
								$row[] = ( isset( $price_offer['user_agent'] ) ? $price_offer['user_agent'] : '' );
								break;
							case 'sent_to':
								$row[] = $price_offer['sent_to'] . ( 'yes' === $price_offer['copy_to_customer'] ? '<br>' . $price_offer['customer_email'] : '' );
								break;
						}
					}
					$table_data[] = $row;
					if ( ! isset( $average_offers[ $price_offer['currency_code'] ] ) ) {
						$average_offers[ $price_offer['currency_code'] ] = array(
							'total_offers' => 0,
							'offers_sum'   => 0,
						);
					}
					$average_offers[ $price_offer['currency_code'] ]['total_offers']++;
					$average_offers[ $price_offer['currency_code'] ]['offers_sum'] += $price_offer['offered_price'];
				}
				echo wp_kses_post( wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) ) );
				foreach ( $average_offers as $average_offer_currency_code => $average_offer_data ) {
					echo wp_kses_post(
						'<p>' . sprintf(
							/* translators: %s: search term */
							esc_html__( 'Average offer: %1$s (from %2$s offer(s))', 'woocommerce-jetpack' ),
							wc_price( ( $average_offer_data['offers_sum'] / $average_offer_data['total_offers'] ), array( 'currency' => $average_offer_currency_code ) ),
							$average_offer_data['total_offers']
						) . '</p>'
					);
				}
				echo '<p>' .
				'<input type="checkbox" id="wcj_offer_price_delete_history" name="wcj_offer_price_delete_history">' .
				'<label for="wcj_offer_price_delete_history">' . wp_kses_post( 'Delete history', 'woocommerce-jetpack' ) . '</label>' .
				wp_kses_post( wc_help_tip( __( 'Update product after checking the box.', 'woocommerce-jetpack' ) ) ) .
				'</p>';
			}
		}

		/**
		 * Enqueue_scripts.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 * @see     https://www.w3schools.com/howto/howto_css_modals.asp
		 * @todo    (maybe) enqueue only if really needed
		 */
		public function enqueue_scripts() {
			wp_enqueue_style( 'wcj-offer-price', wcj_plugin_url() . '/includes/css/wcj-offer-price.css', array(), w_c_j()->version );
			wp_enqueue_script( 'wcj-offer-price-js', wcj_plugin_url() . '/includes/js/wcj-offer-price.js', array( 'jquery' ), w_c_j()->version, true );
		}

		/**
		 * Is_offer_price_enabled_for_product.
		 *
		 * @version 4.2.0
		 * @since   2.9.0
		 * @param int $product_id defines the product_id.
		 */
		public function is_offer_price_enabled_for_product( $product_id ) {
			switch ( apply_filters( 'booster_option', 'all_products', wcj_get_option( 'wcj_offer_price_enabled_type', 'all_products' ) ) ) {
				case 'all_products':
					return true;
				case 'empty_prices':
					$_product = wc_get_product( $product_id );
					return ( '' === $_product->get_price() );
				case 'per_product':
					return ( 'yes' === get_post_meta( $product_id, '_wcj_offer_price_enabled', true ) );
				case 'per_category':
					return wcj_is_product_term( $product_id, wcj_get_option( 'wcj_offer_price_enabled_cats', array() ), 'product_cat' );
				case 'per_product_and_per_category':
					return ( 'yes' === get_post_meta( $product_id, '_wcj_offer_price_enabled', true ) || wcj_is_product_term( $product_id, wcj_get_option( 'wcj_offer_price_enabled_cats', array() ), 'product_cat' ) );
			}
		}

		/**
		 * Get_wcj_data_array.
		 *
		 * @version 4.2.0
		 * @since   2.9.0
		 * @todo    (maybe) rethink `str_replace( '\'', '"', ... )`
		 * @param int $product_id defines the product_id.
		 */
		public function get_wcj_data_array( $product_id ) {
			$is_per_product_enabled = ( in_array( apply_filters( 'booster_option', 'all_products', wcj_get_option( 'wcj_offer_price_enabled_type', 'all_products' ) ), array( 'per_product', 'per_product_and_per_category' ), true ) );
			// Price input - price step.
			$price_step_per_product = get_post_meta( $product_id, '_wcj_offer_price_price_step', true );
			$price_step             = ( ! $is_per_product_enabled || '' === ( $price_step_per_product ) ?
			get_option( 'wcj_offer_price_price_step', wcj_get_option( 'woocommerce_price_num_decimals' ) ) :
			$price_step_per_product
			);
			$price_step             = sprintf( '%f', ( 1 / pow( 10, absint( $price_step ) ) ) );
			// Price input - min price.
			$min_price_per_product = get_post_meta( $product_id, '_wcj_offer_price_min_price', true );
			$min_price             = ( ! $is_per_product_enabled || '' === ( $min_price_per_product ) ?
				get_option( 'wcj_offer_price_min_price', 0 ) :
				$min_price_per_product
			);
			// Price input - max price.
			$max_price_per_product = get_post_meta( $product_id, '_wcj_offer_price_max_price', true );
			$max_price             = ( ! $is_per_product_enabled || '' === ( $max_price_per_product ) ?
				get_option( 'wcj_offer_price_max_price', 0 ) :
				$max_price_per_product
			);
			// Price input - default price.
			$default_price_per_product = get_post_meta( $product_id, '_wcj_offer_price_default_price', true );
			$default_price             = ( ! $is_per_product_enabled || '' === ( $default_price_per_product ) ?
				get_option( 'wcj_offer_price_default_price', 0 ) :
				$default_price_per_product
			);
			// Price input - label.
			$price_label = str_replace(
				'%currency_symbol%',
				get_woocommerce_currency_symbol(),
				/* translators: %s: translation added */
				get_option( 'wcj_offer_price_price_label', sprintf( __( 'Your price (%s)', 'woocommerce-jetpack' ), '%currency_symbol%' ) )
			);
			// Offer form - header.
			$form_header = str_replace(
				'%product_title%',
				get_the_title(),
				/* translators: %s: translation added */
				get_option( 'wcj_offer_price_form_header_template', '<h3>' . sprintf( __( 'Suggest your price for %s', 'woocommerce-jetpack' ), '%product_title%' ) . '</h3>' )
			);
			return array(
				'price_step'    => $price_step,
				'min_price'     => $min_price,
				'max_price'     => $max_price,
				'default_price' => $default_price,
				'price_label'   => str_replace( '\'', '"', $price_label ),
				'form_header'   => esc_html( $form_header ),
				'product_id'    => $product_id,
			);
		}

		/**
		 * Add_offer_price_form.
		 *
		 * @version 5.6.2
		 * @since   2.9.0
		 * @todo    (maybe) fix when empty header
		 * @todo    (maybe) style options for input fields (class, style)
		 * @todo    (maybe) form template
		 * @todo    (maybe) do_shortcode in form header and footer
		 * @todo    (maybe) logged user - check `nickname` and `billing_email`
		 * @todo    (maybe) better required asterix default
		 * @todo    (maybe) optional, additional and custom form fields
		 */
		public function add_offer_price_form() {
			// Prepare logged user data.
			$customer_name  = '';
			$customer_email = '';
			$customer_id    = 0;
			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$customer_id  = $current_user->ID;
				$meta         = get_user_meta( $current_user->ID, 'nickname', true );
				if ( '' !== ( $meta ) ) {
					$customer_name = $meta;
				}
				$meta = get_user_meta( $current_user->ID, 'billing_email', true );
				if ( '' !== ( $meta ) ) {
					$customer_email = $meta;
				}
			}
			// Header.
			$offer_form_header = '<div class="wcj-offer-modal-header">' .
			'<span class="wcj-offer-price-form-close">&times;</span><div id="wcj-offer-form-header"></div>' .
			'</div>';
			// Footer.
			$footer_template   = wcj_get_option( 'wcj_offer_price_form_footer_template', '' );
			$offer_form_footer = ( '' !== ( $footer_template ) ?
			'<div class="wcj-offer-price-modal-footer">' . /* do_shortcode */( $footer_template ) . '</div>' : '' );
			// Required HTML.
			$required_html = wcj_get_option( 'wcj_offer_price_form_required_html', ' <abbr class="required" title="required">*</abbr>' );
			// Content - price.
			$offer_form_content_price = '<label for="wcj-offer-price-price">' .
				'<span id="wcj-offer-price-price-label"></span>' . $required_html . '</label><br>' .
				'<input type="number" required id="wcj-offer-price-price" name="wcj-offer-price-price">';
			// Content - email.
			$offer_form_content_email = '<label for="wcj-offer-price-customer-email">' . wcj_get_option( 'wcj_offer_price_customer_email', __( 'Your email', 'woocommerce-jetpack' ) ) .
				$required_html . '</label><br>' .
				'<input type="email" required id="wcj-offer-price-customer-email" name="wcj-offer-price-customer-email" value="' . $customer_email . '">';
			// Content - name.
			$offer_form_content_name = '<label for="wcj-offer-price-customer-name">' . wcj_get_option( 'wcj_offer_price_customer_name', __( 'Your name', 'woocommerce-jetpack' ) ) .
				'</label><br>' .
				'<input type="text" id="wcj-offer-price-customer-name" name="wcj-offer-price-customer-name" value="' . $customer_name . '">';
			// Content - message.
			$offer_form_content_message = '<label for="wcj-offer-price-message">' . wcj_get_option( 'wcj_offer_price_customer_message', __( 'Your message', 'woocommerce-jetpack' ) ) . '</label><br>' .
				'<textarea id="wcj-offer-price-message" name="wcj-offer-price-message"></textarea>';
			// Content - button.
			$offer_form_content_button = '<input type="submit" id="wcj-offer-price-submit" name="wcj-offer-price-submit" value="' .
				get_option( 'wcj_offer_price_form_button_label', __( 'Send', 'woocommerce-jetpack' ) ) . '">';
			// Content - copy.
			$offer_form_content_copy = '<label for="wcj-offer-price-customer-copy">' . wcj_get_option( 'wcj_offer_price_customer_copy', __( 'Send a copy to your email', 'woocommerce-jetpack' ) ) .
				'</label><input type="checkbox" id="wcj-offer-price-customer-copy" name="wcj-offer-price-customer-copy" value="yes">';
			// Content.
			$offer_form_content = '<div class="wcj-offer-price-modal-body">' .
				'<form method="post" id="wcj-offer-price-form">' .
				'<p>' . $offer_form_content_price . '</p>' .
				'<p>' . $offer_form_content_email . '</p>' .
				'<p>' . $offer_form_content_name . '</p>' .
				'<p>' . $offer_form_content_message . '</p>' .
				'<p>' . $offer_form_content_button . '</p>' .
				'<p>' . $offer_form_content_copy . '</p>' .
				'<input type="hidden" id="wcj-offer-price-product-id" name="wcj-offer-price-product-id">' .
				'<input type="hidden" name="wcj-offer-price-customer-id" value="' . $customer_id . '">' .
				'<input type="hidden" name="wcj_offer_price_nonce" value="' . wp_create_nonce( 'wcj-offer-price-modal' ) . '">' .
				'</form>' .
			'</div>';
			// Final form.
			echo '<div id="wcj-offer-price-modal" class="wcj-offer-price-modal">' .
				'<div class="wcj-offer-price-modal-content">' .
				wp_kses_post( $offer_form_header ) .
				wp_kses_post( $offer_form_content ) .
				wp_kses_post( $offer_form_footer ) .
				'</div>' .
			'</div>';
		}

		/**
		 * Is_offer_price_excluded_for_product.
		 *
		 * @version 4.4.0
		 * @since   4.4.0
		 * @todo    [dev] add more conditions to exclude (i.e. not only "out of stock")
		 * @param int $product_id defines the product_id.
		 */
		public function is_offer_price_excluded_for_product( $product_id ) {
			if ( 'yes' === wcj_get_option( 'wcj_offer_price_exclude_out_of_stock', 'no' ) ) {
				$product = wc_get_product( $product_id );
				if ( ! $product->is_in_stock() ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Add_offer_price_button.
		 *
		 * @version 5.6.8
		 * @since   2.9.0
		 */
		public function add_offer_price_button() {
			$product_id = get_the_ID();
			// Check if enabled for current product.
			if ( ! $this->is_offer_price_enabled_for_product( $product_id ) || $this->is_offer_price_excluded_for_product( $product_id ) ) {
				return;
			}
			// The button.
			$additional_class = wcj_get_option( 'wcj_offer_price_button_class', 'button' );
			if ( '' !== $additional_class ) {
				$additional_class = ' ' . $additional_class;
			}
			echo wp_kses_post(
				'<p>' .
				'<button type="submit"' .
					' name="wcj-offer-price-button"' .
					' class="wcj-offer-price-button' . $additional_class . '"' .
					' value="' . esc_html( $product_id ) . '"' .
					' style="' . wcj_get_option( 'wcj_offer_price_button_style', '' ) . '"' .
					' wcj_data=\'' . wp_json_encode( $this->get_wcj_data_array( $product_id ) ) . '\'' .
				'>' .
					get_option( 'wcj_offer_price_button_label', __( 'Make an offer', 'woocommerce-jetpack' ) ) .
				'</button>' .
				'</p>'
			);
		}

		/**
		 * Offer_price.
		 *
		 * @version 5.6.7
		 * @since   2.9.0
		 * @todo    (maybe) separate customer copy email template and subject
		 * @todo    (maybe) redirect (no notice though)
		 * @todo    (maybe) `%product_title%` etc. in notice
		 * @todo    (maybe) fix "From" header
		 * @todo    (maybe) check if mail has really been sent
		 * @todo    (maybe) sanitize $_POST
		 */
		public function offer_price() {
			if ( isset( $_POST['wcj-offer-price-submit'] ) ) {
				$wpnonce    = isset( $_REQUEST['wcj_offer_price_nonce'] ) ? wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['wcj_offer_price_nonce'] ) ), 'wcj-offer-price-modal' ) : false;
				$product_id = $wpnonce && isset( $_POST['wcj-offer-price-product-id'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj-offer-price-product-id'] ) ) : '';
				$_product   = wc_get_product( $product_id );
				if ( ! is_a( $_product, 'WC_Product' ) ) {
					return;
				}
				// Email address.
				$email_address = wcj_get_option( 'wcj_offer_price_email_address', '%admin_email%' );
				if ( '' === $email_address ) {
					$email_address = wcj_get_option( 'admin_email' );
				} else {
					$admin_email       = wcj_get_option( 'admin_email' );
					$product_author_id = get_post_field( 'post_author', $product_id );
					$product_user_info = get_userdata( $product_author_id );
					if (
					( $product_author_id ) &&
					( $product_user_info ) &&
					isset( $product_user_info->user_email )
					) {
						$product_author_email = $product_user_info->user_email;
					} else {
						$product_author_email = $admin_email;
					}
					$email_address = str_replace( array( '%admin_email%', '%product_author_email%' ), array( $admin_email, $product_author_email ), $email_address );
				}
				// Price offer array.
				$price_offer = array(
					'offer_timestamp'   => wcj_get_timestamp_date_from_gmt(),
					'product_title'     => $_product->get_title(),
					'product_edit_link' => get_edit_post_link( $_product->get_id() ),
					'offered_price'     => isset( $_POST['wcj-offer-price-price'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj-offer-price-price'] ) ) : '',
					'currency_code'     => get_woocommerce_currency(),
					'customer_message'  => isset( $_POST['wcj-offer-price-message'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj-offer-price-message'] ) ) : '',
					'customer_name'     => isset( $_POST['wcj-offer-price-customer-name'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj-offer-price-customer-name'] ) ) : '',
					'customer_email'    => isset( $_POST['wcj-offer-price-customer-email'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj-offer-price-customer-email'] ) ) : '',
					'customer_id'       => isset( $_POST['wcj-offer-price-customer-id'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj-offer-price-customer-id'] ) ) : '',
					'user_ip'           => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
					'user_agent'        => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
					'copy_to_customer'  => ( isset( $_POST['wcj-offer-price-customer-copy'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj-offer-price-customer-copy'] ) ) : 'no' ),
					'sent_to'           => $email_address,
				);
				// Email content.
				$email_template = wcj_get_option(
					'wcj_offer_price_email_template',
					/* translators: %s: translation added */
					sprintf( __( 'Product: %s', 'woocommerce-jetpack' ), '<a href="%product_edit_link%">%product_title%</a>' ) . '<br>' . PHP_EOL .
					/* translators: %s: translation added */
					sprintf( __( 'Offered price: %s', 'woocommerce-jetpack' ), '%offered_price%' ) . '<br>' . PHP_EOL .
					/* translators: %s: translation added */
					sprintf( __( 'From: %1$s %2$s', 'woocommerce-jetpack' ), '%customer_name%', '%customer_email%' ) . '<br>' . PHP_EOL .
					/* translators: %s: translation added */
					sprintf( __( 'Message: %s', 'woocommerce-jetpack' ), '%customer_message%' )
				);
				$replaced_values = array(
					'%product_title%'     => $price_offer['product_title'],
					'%product_edit_link%' => $price_offer['product_edit_link'],
					'%offered_price%'     => wc_price( $price_offer['offered_price'] ),
					'%customer_message%'  => $price_offer['customer_message'],
					'%customer_name%'     => $price_offer['customer_name'],
					'%customer_email%'    => $price_offer['customer_email'],
					'%user_ip%'           => $price_offer['user_ip'],
					'%user_agent%'        => $price_offer['user_agent'],
				);
				$email_content   = str_replace( array_keys( $replaced_values ), array_values( $replaced_values ), $email_template );
				// Email subject and headers.
				$email_subject = wcj_get_option( 'wcj_offer_price_email_subject', __( 'Price Offer', 'woocommerce-jetpack' ) );
				$email_headers = 'Content-Type: text/html' . "\r\n" .
				'From: ' . $price_offer['customer_name'] . ' <' . $price_offer['customer_email'] . '>' . "\r\n" .
				'Reply-To: ' . $price_offer['customer_email'] . "\r\n";
				// Send email.
				wc_mail( $email_address, $email_subject, $email_content, $email_headers );
				if ( 'yes' === $price_offer['copy_to_customer'] ) {
					wc_mail( $price_offer['customer_email'], $email_subject, $email_content, $email_headers );
				}
				// Notice.
				wc_add_notice( wcj_get_option( 'wcj_offer_price_customer_notice', __( 'Your price offer has been sent.', 'woocommerce-jetpack' ) ), 'notice' );
				// Product meta (Offer Price History).
				$price_offers = get_post_meta( $product_id, '_wcj_price_offers', true );
				if ( '' === ( $price_offers ) ) {
					$price_offers = array();
				}
				$price_offers[] = $price_offer;
				update_post_meta( $product_id, '_wcj_price_offers', $price_offers );
			}
		}

	}

endif;

return new WCJ_Offer_Price();

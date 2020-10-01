<?php
/**
 * Booster for WooCommerce - Module - Coupon Code Generator
 *
 * @version 5.2.0
 * @since   3.2.3
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Coupon_Code_Generator' ) ) :

class WCJ_Coupon_Code_Generator extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   3.2.3
	 * @todo    user ID in coupon code
	 * @todo    add option to generate code only on button (in meta box) pressed
	 * @todo    `wp_ajax_nopriv_wcj_generate_coupon_code` ?
	 */
	function __construct() {

		$this->id         = 'coupon_code_generator';
		$this->short_desc = __( 'Coupon Code Generator', 'woocommerce-jetpack' );
		$this->desc       = __( 'Coupon code generator (Multiple generation algorithms available in Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Coupon code generator.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-coupon-code-generator';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( 'yes' === wcj_get_option( 'wcj_coupons_code_generator_enabled', 'no' ) ) {
				add_action( 'wp_ajax_wcj_generate_coupon_code', array( $this, 'ajax_generate_coupon_code' ) );
				add_action( 'admin_enqueue_scripts',            array( $this, 'enqueue_generate_coupon_code_script' ) );
			}
		}
	}

	/**
	 * enqueue_generate_coupon_code_script.
	 *
	 * @version 3.1.3
	 * @since   3.1.3
	 */
	function enqueue_generate_coupon_code_script() {
		global $pagenow;
		if ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && 'shop_coupon' === $_GET['post_type'] ) {
			wp_enqueue_script(  'wcj-coupons-code-generator', wcj_plugin_url() . '/includes/js/wcj-coupons-code-generator.js', array( 'jquery' ), WCJ()->version, true );
			wp_localize_script( 'wcj-coupons-code-generator', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		}
	}

	/**
	 * random_string.
	 *
	 * @version 3.2.3
	 * @since   3.2.3
	 * @todo    (maybe) $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
	 */
	function random_string( $length = 32, $characters = 'abcdefghijklmnopqrstuvwxyz' ) {
		$characters_length = strlen( $characters );
		$random_string     = '';
		for ( $i = 0; $i < $length; $i++ ) {
			$random_string .= $characters[ rand( 0, $characters_length - 1 ) ];
		}
		return $random_string;
	}

	/**
	 * generate_coupon_code.
	 *
	 * @version 3.2.3
	 * @since   3.1.3
	 * @todo    (maybe) more algorithms
	 */
	function generate_coupon_code( $str = '', $algorithm = '', $length = '' ) {
		if ( '' === $str ) {
			$str = time();
		}
		if ( '' === $algorithm ) {
			$algorithm = apply_filters( 'booster_option', 'crc32', wcj_get_option( 'wcj_coupons_code_generator_algorithm', 'crc32' ) );
		}
		switch ( $algorithm ) {
			case 'random_letters_and_numbers':
				$code = $this->random_string( 32, '0123456789abcdefghijklmnopqrstuvwxyz' );
				break;
			case 'random_letters':
				$code = $this->random_string( 32, 'abcdefghijklmnopqrstuvwxyz' );
				break;
			case 'random_numbers':
				$code = $this->random_string( 32, '0123456789' );
				break;
			case 'md5':
				$code = md5( $str );
				break;
			case 'sha1':
				$code = sha1( $str );
				break;
			default: // 'crc32'
				$code = sprintf( '%08x', crc32( $str ) );
				break;
		}
		if ( '' === $length ) {
			$length = apply_filters( 'booster_option', 0, wcj_get_option( 'wcj_coupons_code_generator_length', 0 ) );
		}
		if ( $length > 0 && strlen( $code ) > $length ) {
			$code = substr( $code, 0, $length );
		}
		return $code;
	}

	/**
	 * ajax_generate_coupon_code.
	 *
	 * @version 3.1.3
	 * @since   3.1.3
	 * @todo    (maybe) optionally generate some description for coupon (e.g. "Automatically generated coupon [YYYY-MM-DD]")
	 */
	function ajax_generate_coupon_code() {
		$attempts = 0;
		while ( true ) {
			$coupon_code = $this->generate_coupon_code();
			$coupon      = new WC_Coupon( $coupon_code );
			if ( ! $coupon->get_id() ) {
				echo $coupon_code;
				die();
			}
			$attempts++;
			if ( $attempts > 100 ) { // shouldn't happen, but just in case...
				echo '';
				die();
			}
		}
	}

}

endif;

return new WCJ_Coupon_Code_Generator();

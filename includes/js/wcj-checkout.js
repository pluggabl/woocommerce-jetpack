/**
 * Wcj-checkout.js
 *
 * @version 5.6.2
 * @package Booster_For_WooCommerce/includes/JS
 */

jQuery(
	function( $ ) {
		// Define that JavaScript code should be executed in "strict mode".
		"use strict";
		// Trigger WooCommerce's `update_checkout` function, when customer changes payment method.
		$( 'body' ).on(
			'change',
			'input[name="payment_method"]',
			function() {
				$( 'body' ).trigger( 'update_checkout' );
			}
		);
	}
);

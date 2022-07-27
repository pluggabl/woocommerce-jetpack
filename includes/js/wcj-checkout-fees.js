/**
 * Wcj-checkout-fees.
 *
 * @version 5.6.2
 * @since   3.8.0
 *
 * @package Booster_For_WooCommerce/includes/JS
 */

jQuery( 'body' ).on(
	'change',
	wcj_checkout_fees.checkout_fields,
	function(){
		jQuery( 'body' ).trigger( 'update_checkout' );
	}
);

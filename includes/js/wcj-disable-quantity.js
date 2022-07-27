/**
 * Wcj-disable-quantity.
 *
 * @version 5.6.2
 * @since   2.5.2
 * @package Booster_For_WooCommerce/includes/JS
 */

jQuery( document ).ready(
	function() {
		jQuery( "div.quantity input.qty" ).each(
			function () {
				jQuery( this ).attr( "disabled", "disabled" );
			}
		);
	}
);

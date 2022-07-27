/**
 * Wcj-pdf-invoicing.
 *
 * @version 5.6.2
 * @since   2.5.2
 * @package Booster_For_WooCommerce/includes/JS
 */

jQuery( document ).ready(
	function() {
		jQuery( 'a.wcj_need_confirmation' ).click(
			function() {
				return confirm( "Are you sure?" );
			}
		);
	}
);

/**
 * Booster for WooCommerce - Input Fields
 *
 * @author  Pluggabl LLC.
 * @version 5.6.2
 *
 * @package Booster_For_WooCommerce/includes/JS
 */

jQuery( document ).ready(
	function() {
		jQuery( 'input,textarea' ).focus(
			function(){
				jQuery( this ).data( 'placeholder',jQuery( this ).attr( 'placeholder' ) )
				jQuery( this ).attr( 'placeholder','' );
			}
		);
		jQuery( 'input,textarea' ).blur(
			function(){
				jQuery( this ).attr( 'placeholder',jQuery( this ).data( 'placeholder' ) );
			}
		);
	}
);

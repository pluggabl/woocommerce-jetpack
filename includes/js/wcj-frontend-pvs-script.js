/**
 * Wcj-frontend-pvs-script.
 *
 * @version 7.2.8
 * @since   1.0.2
 * @package Booster_For_WooCommerce/includes
 */

jQuery( document ).ready(
	function ($) {

		$( "body" ).on(
			"click",
			".variable-items-wrapper .variable-item",
			function(){
				var type  = $( this ).data( 'type' );
				var value = $( this ).data( 'value' );

				$( this ).siblings( '.variable-item' ).removeClass( 'selected' );
				$( this ).addClass( 'selected' );
				var attribute_name = $( this ).parent().attr( 'data-attribute_name' );

				if (attribute_name != "") {
					$( '.wcj_pvs_select[name=' + attribute_name + ']' ).val( value ).trigger( 'change' );
				}
			}
		);

		$( "body" ).on(
			"click",
			".reset_variations",
			function(){
				$( ".wcj_variable_items_wrapper li" ).removeClass( "selected" );
			}
		);

	}
);

/**
 * Confirm selected order-pay fees before allowing a payment submission.
 * @version 9.0.0
 */
jQuery( function( $ ) {
	'use strict';
	var pending = false;
	var failed = false;
	var changedWhilePending = false;
	var $confirmation = $( 'input[name="wcj_order_pay_fee_confirmation"]' );
	var confirmedMethod = $confirmation.attr( 'data-payment-method' ) || '';
	var isOrderPay = typeof wcj_order_pay_fees_params !== 'undefined' && 'yes' === wcj_order_pay_fees_params.is_order_pay;
	function selected() {
		return $( 'input[name="payment_method"]:checked' ).val() || '';
	}
	function canPay() {
		return ! pending && ! failed && ! changedWhilePending && !! $confirmation.val() && confirmedMethod === selected();
	}
	function showError() {
		var $error = $( '#wcj-order-pay-fee-error' );
		if ( ! $error.length ) {
			$error = $( '<p id="wcj-order-pay-fee-error" role="alert"></p>' ).prependTo( '#order_review' );
		}
		$error.text( wcj_order_pay_fees_params.error_message );
	}
	if ( isOrderPay ) {
		$( '#order_review' ).on( 'submit', function( event ) {
			if ( ! canPay() ) {
				event.preventDefault();
				showError();
				return false;
			}
		} );
	}
	$( 'body' ).on( 'change', 'input[name="payment_method"]', function() {
		if ( ! isOrderPay ) {
			$( 'body' ).trigger( 'update_checkout' );
			return;
		}
		if ( pending ) {
			changedWhilePending = true;
			showError();
			return;
		}
		if ( failed || ! $confirmation.val() || ! wcj_order_pay_fees_params.ajax_url || ! wcj_order_pay_fees_params.order_id ) {
			failed = true;
			showError();
			return;
		}
		var method = selected();
		var $form = $( '#order_review' );
		pending = true;
		changedWhilePending = false;
		if ( $form.length && $.fn.block ) {
			$form.block( { message: null, overlayCSS: { background: '#fff', opacity: 0.6 } } );
		}
		$.ajax( {
			type: 'POST',
			url: wcj_order_pay_fees_params.ajax_url,
			dataType: 'json',
			data: {
				order_id: wcj_order_pay_fees_params.order_id,
				order_key: wcj_order_pay_fees_params.order_key,
				payment_method: method,
				nonce: wcj_order_pay_fees_params.nonce,
				wcj_order_pay_fee_confirmation: $confirmation.val()
			},
			success: function( response ) {
				if ( response && true === response.success && response.data && response.data.totals_html && response.data.confirmation && response.data.payment_method === method && selected() === method && ! changedWhilePending ) {
					$( '#order_review table.shop_table tfoot' ).html( response.data.totals_html );
					$confirmation.val( response.data.confirmation );
					$confirmation.attr( 'data-payment-method', method );
					confirmedMethod = method;
				} else {
					failed = true;
					showError();
				}
			},
			error: function() {
				failed = true;
				showError();
			},
			complete: function() {
				pending = false;
				if ( $form.length && $.fn.unblock ) {
					$form.unblock();
				}
			}
		} );
	} );
} );

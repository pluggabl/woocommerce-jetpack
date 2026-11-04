/**
 * Native Checkout block Place order label filter.
 *
 * @since 9.0.0
 */
( function ( window ) {
	'use strict';

	var wc = window.wc;
	if ( ! wc || ! wc.blocksCheckout || typeof wc.blocksCheckout.registerCheckoutFilters !== 'function' ||
		! wc.wcSettings || typeof wc.wcSettings.getSetting !== 'function' ) {
		return;
	}

	var settings = wc.wcSettings.getSetting( 'wcj_more_button_labels_data', {} ) || {};
	var subscriptionReady = false;
	var previousDecision = false;

	function canUseBoosterLabel() {
		var wp = window.wp;
		if ( ! wp || ! wp.data || typeof wp.data.select !== 'function' || typeof wp.data.subscribe !== 'function' ||
			! wc.wcBlocksData || ! wc.wcBlocksData.paymentStore ||
			! wc.wcBlocksRegistry || typeof wc.wcBlocksRegistry.getPaymentMethods !== 'function' ) {
			return false;
		}

		try {
			// Both callers read current public state, never a cached gateway snapshot.
			var payment = wp.data.select( wc.wcBlocksData.paymentStore );
			if ( ! payment || typeof payment.paymentMethodsInitialized !== 'function' ||
				typeof payment.getActivePaymentMethod !== 'function' ||
				typeof payment.getAvailablePaymentMethods !== 'function' ||
				payment.paymentMethodsInitialized() !== true ) {
				return false;
			}

			var active = payment.getActivePaymentMethod();
			var available = payment.getAvailablePaymentMethods();
			if ( typeof active !== 'string' || active === '' || ! available ||
				typeof available !== 'object' || Array.isArray( available ) ) {
				return false;
			}
			// Match Woo's availability values by name, not arbitrary object keys.
			var isAvailable = Object.keys( available ).some( function ( key ) {
				return available[ key ] && available[ key ].name === active;
			} );
			var methods = wc.wcBlocksRegistry.getPaymentMethods();
			var method = methods && methods[ active ];
			if ( ! methods || typeof methods !== 'object' || Array.isArray( methods ) ||
				! isAvailable || ! method || typeof method !== 'object' || Array.isArray( method ) ||
				( typeof method.placeOrderButtonLabel !== 'undefined' && typeof method.placeOrderButtonLabel !== 'string' ) ) {
				return false;
			}

			return ! method.placeOrderButtonLabel;
		} catch ( error ) {
			return false;
		}
	}

	var filters = {
		placeOrderButtonLabel: function ( incomingLabel ) {
			if ( typeof settings.text !== 'string' || settings.text === '' ) {
				// Blank text never blanks the native button or mutates gateway configs.
				return incomingLabel;
			}
			if ( settings.override === true ) {
				return settings.text;
			}
			// Gateway labels win; custom button components remain gateway-owned.
			return subscriptionReady && canUseBoosterLabel() ? settings.text : incomingLabel;
		},
	};

	var wp = window.wp;
	if ( typeof settings.text === 'string' && settings.text !== '' && settings.override !== true &&
		wp && wp.data && typeof wp.data.select === 'function' && typeof wp.data.subscribe === 'function' &&
		wc.wcBlocksData && wc.wcBlocksData.paymentStore &&
		wc.wcBlocksRegistry && typeof wc.wcBlocksRegistry.getPaymentMethods === 'function' ) {
		try {
			// Woo caches filtered labels without payment state in its cache key.
			// Install before native checkout mounts; public registration clears it.
			subscriptionReady = typeof wp.data.subscribe( function () {
				if ( ! subscriptionReady ) {
					return;
				}
				var decision = canUseBoosterLabel();
				if ( decision !== previousDecision ) {
					previousDecision = decision;
					wc.blocksCheckout.registerCheckoutFilters( 'wcj-more-button-labels', filters );
				}
			}, wc.wcBlocksData.paymentStore ) === 'function';
			if ( subscriptionReady ) {
				previousDecision = canUseBoosterLabel();
			}
		} catch ( error ) {
			subscriptionReady = false;
		}
	}

	wc.blocksCheckout.registerCheckoutFilters( 'wcj-more-button-labels', filters );
} )( window );

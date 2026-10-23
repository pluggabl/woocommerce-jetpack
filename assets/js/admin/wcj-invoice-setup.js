/** Bounded starter setup; the server owns validation, review and activation. */
( function () {
	'use strict';
	document.addEventListener( 'DOMContentLoaded', function () {
		var root = document.getElementById( 'wcj-invoice-setup' );
		if ( ! root || typeof wcjInvoiceSetup === 'undefined' ) { return; }
		var form = document.getElementById( 'wcj-invoice-setup-form' );
		var status = document.getElementById( 'wcj-setup-status' );
		var panel = document.getElementById( 'wcj-setup-review' );
		var confirm = document.getElementById( 'wcj-setup-confirm' );
		var activate = root.querySelector( '[data-setup-action="activate"]' );
		var download = document.getElementById( 'wcj-setup-download' );
		var open = document.getElementById( 'wcj-setup-open' );
		var logo = document.getElementById( 'wcj-setup-logo' );
		var logoName = document.getElementById( 'wcj-setup-logo-name' );
		var removeLogo = document.getElementById( 'wcj-setup-remove-logo' );
		var fingerprint = '', sample = false, rehearsal = false, busy = false, objectUrl = '';
		function showStatus( message, isError, focus ) {
			status.textContent = message;
			status.classList.toggle( 'is-error', Boolean( isError ) );
			if ( focus ) { status.focus(); }
		}
		function refresh() { activate.disabled = busy || ! fingerprint || ! confirm.checked || ! sample || ! rehearsal; }
		function invalidate() {
			fingerprint = ''; sample = false; rehearsal = false; confirm.checked = false; panel.hidden = true;
			download.hidden = true; open.hidden = true;
			download.removeAttribute( 'href' ); open.removeAttribute( 'href' );
			if ( objectUrl ) { URL.revokeObjectURL( objectUrl ); objectUrl = ''; }
			showStatus( wcjInvoiceSetup.changed, false, false ); refresh();
		}
		form.addEventListener( 'invalid', function ( event ) {
			var details = event.target.closest( 'details' );
			while ( details ) {
				details.open = true;
				details = details.parentElement.closest( 'details' );
			}
		}, true );
		form.addEventListener( 'input', function ( event ) {
			if ( event.target !== confirm ) {
				if ( event.target === logo ) {
					logoName.textContent = Number( logo.value ) ? wcjInvoiceSetup.logoSelected : wcjInvoiceSetup.noLogo;
					removeLogo.hidden = ! Number( logo.value );
				}
				invalidate();
			} else { refresh(); }
		} );
		form.addEventListener( 'submit', function ( event ) { event.preventDefault(); } );
		document.getElementById( 'wcj-setup-choose-logo' ).addEventListener( 'click', function () {
			if ( busy ) { return; }
			if ( ! window.wp || ! wp.media ) { showStatus( wcjInvoiceSetup.mediaUnavailable, true, true ); return; }
			var picker = wp.media( { title: wcjInvoiceSetup.chooseLogo, button: { text: wcjInvoiceSetup.useLogo }, library: { type: [ 'image/jpeg', 'image/png' ] }, multiple: false } );
			picker.on( 'select', function () {
				var selected = picker.state().get( 'selection' ).first().toJSON();
				logo.value = String( selected.id );
				logoName.textContent = selected.filename || wcjInvoiceSetup.logoSelected;
				removeLogo.hidden = false;
				invalidate();
			} );
			picker.open();
		} );
		removeLogo.addEventListener( 'click', function () {
			if ( busy ) { return; }
			logo.value = '0'; logoName.textContent = wcjInvoiceSetup.noLogo; removeLogo.hidden = true; invalidate();
		} );
		root.addEventListener( 'click', async function ( event ) {
			var button = event.target.closest( '[data-setup-action]' );
			if ( ! button || busy ) { return; }
			var op = button.getAttribute( 'data-setup-action' );
			if ( op !== 'undo' && ! form.reportValidity() ) { return; }
			if ( op === 'activate' && ( ! fingerprint || ! confirm.checked || ! sample || ! rehearsal ) ) { return; }
			var body = new FormData( form );
			body.append( 'action', 'wcj_invoice_setup_' + op );
			body.append( 'nonce', wcjInvoiceSetup.nonces[ op ] );
			body.append( 'fingerprint', fingerprint );
			busy = true; root.querySelectorAll( 'button' ).forEach( function ( item ) { item.disabled = true; } );
			form.querySelectorAll( 'input,select,textarea' ).forEach( function ( item ) { item.disabled = true; } );
			root.setAttribute( 'aria-busy', 'true' );
			showStatus( wcjInvoiceSetup.working, false, false );
			try {
				var response = await fetch( wcjInvoiceSetup.url, { method: 'POST', credentials: 'same-origin', body: body, cache: 'no-store' } );
				var result = await response.json();
				if ( ! result.success ) { throw new Error( result.data && result.data.message ? result.data.message : wcjInvoiceSetup.failed ); }
				var data = result.data;
				showStatus( data.message || '', false, false );
				if ( op === 'sample' ) {
					if ( objectUrl ) { URL.revokeObjectURL( objectUrl ); }
					var binary = atob( data.pdf );
					var bytes = Uint8Array.from( binary, function ( character ) { return character.charCodeAt( 0 ); } );
					objectUrl = URL.createObjectURL( new Blob( [ bytes ], { type: 'application/pdf' } ) );
					download.href = objectUrl; open.href = objectUrl; download.hidden = false; open.hidden = false; sample = true;
				} else if ( op === 'rehearse' ) {
					rehearsal = data.constructed && data.removed;
				} else if ( op === 'review' ) {
					fingerprint = data.fingerprint; confirm.checked = false; panel.hidden = false;
					var table = document.querySelector( '#wcj-setup-summary tbody' );
					table.replaceChildren();
					data.changes.forEach( function ( change ) {
						var row = document.createElement( 'tr' );
						[ change.label, change.before_display, change.after_display ].forEach( function ( value ) {
							var cell = document.createElement( 'td' ); cell.textContent = value; row.appendChild( cell );
						} );
						table.appendChild( row );
					} );
					if ( ! data.changes.length ) {
						var row = document.createElement( 'tr' ), cell = document.createElement( 'td' );
						cell.colSpan = 3; cell.textContent = wcjInvoiceSetup.noChanges; row.appendChild( cell ); table.appendChild( row );
					}
					document.getElementById( 'wcj-setup-changes' ).textContent = JSON.stringify( { changes: data.changes, existing_gateway_restrictions: data.gateway_restrictions }, null, 2 );
					document.getElementById( 'wcj-setup-review-heading' ).focus();
				} else if ( op === 'undo' ) {
					fingerprint = ''; panel.hidden = true;
					if ( data.conflicts.length ) { status.textContent += '\n' + data.conflicts.join( '\n' ); }
				} else if ( op === 'activate' ) { fingerprint = ''; panel.hidden = true; }
				if ( op === 'sample' ) { open.focus(); } else if ( op !== 'review' ) { status.focus(); }
			} catch ( error ) { showStatus( error.message || wcjInvoiceSetup.failed, true, true ); }
			finally {
				busy = false;
				root.setAttribute( 'aria-busy', 'false' );
				root.querySelectorAll( 'button,input,select,textarea' ).forEach( function ( item ) { item.disabled = false; } );
				refresh();
			}
		} );
	} );
}() );

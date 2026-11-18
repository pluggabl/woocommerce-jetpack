/** One country-rule draft; native results and server text are inserted only with textContent. */
( function () {
	'use strict';
	var cfg = window.wcjGatewayCountryJob;
	if ( ! cfg ) { return; }
	var $ = function ( id ) { return document.getElementById( 'wcj-gcj-' + id ); };
	var state, countryInputs = {}, resolving = false;
	function draftChanged() { message( '' ); }
	function message( text, bad ) { $( 'message' ).textContent = text || ''; $( 'message' ).className = bad ? 'is-error' : ''; if ( text && state && state.receipt && text === state.receipt.message && ! $( 'receipt' ).hidden ) { $( 'message' ).classList.add( 'screen-reader-text' ); } }
	function mode() { return document.querySelector( 'input[name="wcj-gcj-mode"]:checked' ).value; }
	function codes() { return Object.keys( countryInputs ).filter( function ( c ) { return countryInputs[ c ].checked; } ); }
	function selected() { var list = codes(); $( 'selected' ).textContent = list.length ? 'Selected: ' + list.map( function ( c ) { return cfg.countries[ c ]; } ).join( ', ' ) : 'No countries selected'; }
	function filterCountries() {
		var q = $( 'search' ).value.toLowerCase().trim();
		Object.keys( countryInputs ).forEach( function ( c ) { countryInputs[ c ].parentElement.hidden = ( 'EU' === c && 'exclude' === mode() ) || ( q && ( cfg.countries[ c ] + ' ' + c ).toLowerCase().indexOf( q ) < 0 ); } );
	}
	function setMode() { if ( 'exclude' === mode() && countryInputs.EU ) { countryInputs.EU.checked = false; } filterCountries(); selected(); }
	function post( op, data ) {
		var body = new URLSearchParams( { action: 'wcj_gateway_country_job_' + op, nonce: cfg.nonces[ op ], gateway: $( 'gateway' ).value } );
		Object.keys( data || {} ).forEach( function ( k ) { body.set( k, typeof data[ k ] === 'object' ? JSON.stringify( data[ k ] ) : data[ k ] ); } );
		return fetch( cfg.url, { method: 'POST', credentials: 'same-origin', body: body } ).then( function ( r ) { return r.json(); } );
	}
	function draft() { return { mode: mode(), countries: codes(), activate: $( 'activate' ).checked }; }
	function syncResolve() { $( 'resolve' ).disabled = resolving || ! state || ! state.receipt || ! state.receipt.pending || ! $( 'close-confirm' ).checked; $( 'close-confirm' ).disabled = resolving; }
	function receipt( r ) {
		$( 'close-confirm' ).checked = false; syncResolve();
		$( 'receipt' ).hidden = ! r;
		if ( ! r ) { return; }
		var ordinary = r.status === 'applied' && ! r.pending && ! r.restricted && r.rows.length > 0 && r.rows.every( function ( row ) { return row.current_status === 'applied'; } );
  $( 'receipt-details' ).open = ! ordinary; $( 'receipt-technical' ).textContent = ordinary ? r.message : ''; $( 'receipt-technical' ).hidden = ! ordinary;
  $( 'receipt-status' ).textContent = ordinary ? 'Saved: ' + r.rows.length + ( r.rows.length === 1 ? ' change' : ' changes' ) : ( 'recovered' === r.status ? 'Undone. ' : ( r.pending ? 'Pending: ' : '' ) ) + r.message;
		var ul = $( 'receipt-rows' ); ul.textContent = '';
		r.rows.forEach( function ( row ) { var li = document.createElement( 'li' ); var current = { applied: 'applied', unchanged: 'unchanged', changed_elsewhere: 'changed elsewhere' }[ row.current_status ] || 'not checked'; li.setAttribute( 'data-current-status', row.current_status || 'unknown' ); li.textContent = 'recovered' === r.status ? row.label + ': now saved ' + row.current : row.label + ': ' + row.before + ' → ' + row.after + '; current: ' + current; ul.appendChild( li ); } );
		$( 'recover' ).hidden = ! r.recover; $( 'recover' ).textContent = r.pending ? 'Recover previous settings' : 'Undo this change';
		$( 'close' ).hidden = ! r.pending;
	}
	function fill( data ) {
		state = data; var picker = $( 'gateway' ); picker.textContent = '';
		Object.keys( data.gateways ).forEach( function ( id ) { var o = document.createElement( 'option' ); o.value = id; o.textContent = data.gateways[ id ].title; picker.appendChild( o ); } ); picker.value = data.gateway;
		var methodOn = data.gateways[ data.gateway ].enabled;
		$( 'context' ).textContent = 'Country rules: ' + ( data.saved.module ? 'On' : 'Off' ) + ' · Method: ' + ( methodOn ? 'On' : 'Off in WooCommerce' ) + '. Uses the ' + data.source + ' country; this source is unchanged here.' + ( data.saved.module ? '' : ' Saved country rules do not apply while the module is off.' ) + ( methodOn ? '' : ' A country-rule pass will not make this disabled method appear at checkout.' );
		$( 'context' ).classList.toggle( 'wcj-gcj-warning', ! methodOn || ! data.saved.module );
		$( 'lite' ).hidden = ! data.lite; $( 'disabled' ).hidden = true; // Its blocker is stated in the consolidated current status above.
		$( 'next-check' ).textContent = ( ! data.saved.module || ! methodOn ? 'Before checking checkout, ' + ( ! data.saved.module ? 'turn on country rules if intended' : '' ) + ( ! data.saved.module && ! methodOn ? ' and ' : '' ) + ( ! methodOn ? 'enable this method in WooCommerce if intended' : '' ) + '. ' : '' ) + 'Use a known ' + data.source + ' country and check the method at checkout. Other restrictions still apply. Do not place an order.';
		$( 'lock' ).hidden = ! data.lock; $( 'lock' ).textContent = data.lock;
		$( 'form' ).hidden = !! data.lock; $( 'preview' ).hidden = true;
		document.querySelectorAll( 'input[name="wcj-gcj-mode"]' ).forEach( function ( input ) { input.checked = input.value === data.saved.mode; } );
		Object.keys( countryInputs ).forEach( function ( c ) { countryInputs[ c ].checked = data.saved.countries.indexOf( c ) >= 0; } );
		$( 'search' ).value = ''; $( 'activate' ).checked = false; $( 'activation-row' ).hidden = data.saved.module;
		$( 'activate' ).disabled = !! data.activation_rows;
		$( 'activation-note' ).textContent = data.activation_rows ? data.activation_rows + ' other saved location row(s) prevent shared activation here. Use All country settings.' : 'Country rules are shared across payment methods. This only turns on the module, not the WooCommerce method.';
		$( 'review' ).disabled = data.pending_elsewhere || !! ( data.receipt && data.receipt.pending ); setMode(); receipt( data.receipt );
		if ( data.pending_elsewhere ) { message( 'A pending change belongs to another payment method. Open that method in the edition that supports it to recover or close its record. No prior record will be discarded.', true ); }
	}
	function load() { return post( 'load' ).then( function ( r ) { if ( r.success ) { fill( r.data ); } else { message( r.data.error, true ); } } ).catch( function () { message( 'The saved state could not be read. Reload before applying.', true ); } ); }
	function cell( tr, text, header ) { var td = document.createElement( header ? 'th' : 'td' ); td.textContent = text; tr.appendChild( td ); return td; }
	function result( tuple, code ) { if ( ! code || 'not_checked' === tuple[ 0 ] || 'unsupported' === tuple[ 0 ] ) { return 'Not checked: country lists do not decide an unknown or empty country'; } return 'removed' === tuple[ 0 ] ? 'Country rule hides it' : 'Country rule keeps it'; }
	function preview( p ) {
		var body = $( 'changes' ).tBodies[ 0 ]; body.textContent = '';
		p.changes.forEach( function ( c ) { var tr = document.createElement( 'tr' ); cell( tr, c.label, true ); cell( tr, c.before ); cell( tr, c.after ).className = 'wcj-gcj-diff'; body.appendChild( tr ); } );
		body = $( 'samples' ).tBodies[ 0 ]; body.textContent = '';
		p.samples.forEach( function ( e ) { var tr = document.createElement( 'tr' ); cell( tr, e.country, true ); var before = result( e.now, e.code ), after = result( e.after, e.code ); cell( tr, before ); var td = cell( tr, after ); if ( before !== after ) { td.className = 'wcj-gcj-diff'; } body.appendChild( tr ); } );
		$( 'enforcement' ).textContent = ( p.on_now ? 'Saved country rule is enforced. ' : 'Saved country rule is not enforced: module off. ' ) + ( p.on_after ? 'After rule will be enforced.' : 'After rule is not enforced until the module is turned on.' ) + ( p.enabled ? '' : ' This WooCommerce method is disabled.' );
		$( 'form' ).hidden = true; $( 'preview' ).hidden = false; $( 'preview-title' ).focus();
	}
	Object.keys( cfg.countries ).forEach( function ( c ) { var label = document.createElement( 'label' ), input = document.createElement( 'input' ); input.type = 'checkbox'; input.value = c; input.setAttribute( 'data-country', c ); input.addEventListener( 'change', function () { selected(); draftChanged(); } ); label.appendChild( input ); label.appendChild( document.createTextNode( ' ' + ( 'EU' === c ? 'European Union (expands to member countries)' : cfg.countries[ c ] ) ) ); $( 'countries' ).appendChild( label ); countryInputs[ c ] = input; } );
	document.querySelectorAll( 'input[name="wcj-gcj-mode"]' ).forEach( function ( input ) { input.addEventListener( 'change', function () { setMode(); draftChanged(); } ); } );
	$( 'activate' ).addEventListener( 'change', draftChanged );
	$( 'search' ).addEventListener( 'input', filterCountries ); $( 'gateway' ).addEventListener( 'change', load );
	$( 'form' ).addEventListener( 'submit', function ( e ) { e.preventDefault(); message( '' ); post( 'preview', { draft: draft() } ).then( function ( r ) { if ( r.success && r.data.changes.length ) { preview( r.data ); } else { message( r.success ? 'These values are already saved.' : r.data.error, ! r.success ); } } ).catch( function () { message( 'Preview could not be read. Nothing was applied.', true ); } ); } );
	$( 'back' ).addEventListener( 'click', function () { $( 'preview' ).hidden = true; $( 'form' ).hidden = false; $( 'review' ).focus(); } );
	$( 'apply' ).addEventListener( 'click', function () { var btn = this; btn.disabled = true; post( 'apply', { draft: draft(), base: state.base, confirm: '1' } ).then( function ( r ) { if ( r.success ) { fill( r.data.fresh ); message( r.data.message, !! ( r.data.fresh.receipt && r.data.fresh.receipt.pending ) ); if ( r.data.fresh.receipt && ! $( 'receipt-details' ).open ) { $( 'message' ).textContent = $( 'receipt-status' ).textContent; $( 'message' ).classList.add( 'screen-reader-text' ); } } else { message( r.data.error, true ); } } ).catch( function () { message( 'Apply response unavailable. Reload to inspect saved state before trying again.', true ); } ).finally( function () { btn.disabled = false; } ); } );
	$( 'recover' ).addEventListener( 'click', function () { var btn = this; btn.disabled = true; post( 'recover', { receipt: state.receipt.token } ).then( function ( r ) { if ( r.success ) { fill( r.data.fresh ); message( r.data.message, !! ( r.data.fresh.receipt && r.data.fresh.receipt.pending ) ); } else { message( r.data.error, true ); } } ).catch( function () { message( 'Recovery response unavailable. Reload the saved state.', true ); } ).finally( function () { btn.disabled = false; } ); } );
	$( 'close-confirm' ).addEventListener( 'change', syncResolve );
	syncResolve();
	$( 'resolve' ).addEventListener( 'click', function () { if ( resolving || ! state || ! state.receipt || ! state.receipt.pending ) { return; } if ( ! $( 'close-confirm' ).checked ) { message( 'Check the current country settings and confirm before closing this record.', true ); return; } resolving = true; syncResolve(); post( 'resolve', { receipt: state.receipt.token, close_base: state.close_base, confirm: '1' } ).then( function ( r ) { if ( r.success ) { fill( r.data.fresh ); message( r.data.message ); } else { message( r.data.error, true ); } } ).catch( function () { message( 'Close response unavailable. Reload the current record.', true ); } ).finally( function () { resolving = false; $( 'close-confirm' ).checked = false; syncResolve(); } ); } );
	load();
}() );

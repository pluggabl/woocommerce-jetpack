/**
 * Returns job page: one draft, review with an illustrative elapsed-day example, apply, receipt and undo.
 * All text from the server is inserted with textContent.
 */
( function () {
	'use strict';
	var cfg = window.wcjReturnsJob;
	if ( ! cfg ) {
		return;
	}
	var $ = function ( id ) {
		return document.getElementById( id );
	};
	var state = null;
	var label = {
		window: 'Return window (days)',
		notify: 'Queue status notices',
		module: 'Return requests',
		rule: 'Item rule'
	};
	var stateText = {
		inside: 'Within the window',
		edge: 'At the limit (native rule decides)',
		outside: 'Outside the window',
		off: 'Returns off',
		blocked: 'Blocked: saved window invalid',
		excluded: 'No returns for this item'
	};
	// Only definite native results are shown; an unavailable check is never presented as readiness.
	var diagText = {
		basic: 'Booster\'s Returns check passed on the saved settings. No item rules are saved.',
		valid: 'Booster\'s Returns check passed on the saved settings, including item rules.',
		policy_invalid: 'Booster\'s Returns check found the saved settings invalid, so new requests are blocked. Undo, or fix them in the full settings.',
		policy_facts_unavailable: 'Saved item rules could not be checked against current products, so new requests are unavailable. See the full settings.'
	};

	function ruleValue( v ) {
		if ( null === v || undefined === v || '' === v ) {
			return 'storewide window';
		}
		return 'exclude' === v ? 'no returns' : v + ' days';
	}

	function msg( text, bad ) {
		var el = $( 'wcj-rj-msg' );
		el.textContent = text || '';
		el.className = 'wcj-rj-msg' + ( bad ? ' is-error' : '' );
	}

	function post( op, data ) {
		var body = new URLSearchParams();
		body.append( 'action', 'wcj_returns_job_' + op );
		body.append( 'nonce', cfg.nonces[ op ] );
		Object.keys( data || {} ).forEach( function ( k ) {
			body.append( k, data[ k ] );
		} );
		return fetch( cfg.url, { method: 'POST', credentials: 'same-origin', body: body } ).then( function ( r ) {
			return r.json().catch( function () {
				return { success: false, data: { error: 'The response could not be read. Reload to see the saved values.' } };
			} );
		} );
	}

	function draft() {
		var d = {};
		var s = state.saved;
		var w = $( 'wcj-rj-window' ).value.trim();
		if ( w !== s.window ) {
			d.window = w;
		}
		var n = $( 'wcj-rj-notify' ).checked ? 'yes' : 'no';
		if ( n !== s.notify ) {
			d.notify = n;
		}
		if ( 'yes' !== s.module && ! state.env.lock && $( 'wcj-rj-module' ).checked ) {
			d.module = 'yes';
		}
		var idInput = $( 'wcj-rj-rule-id' );
		var daysInput = $( 'wcj-rj-rule-days' );
		var id = idInput.value.trim();
		var days = daysInput.value.trim();
		var kind = $( 'wcj-rj-rule-kind' ).value;
		var exclude = 'exclude' === $( 'wcj-rj-rule-mode' ).value;
		// Opening an empty disclosure alone is not a rule draft. Edited incomplete values still need validation.
		var ruleIntent = id || days || exclude || 'product' !== kind || idInput.validity.badInput || daysInput.validity.badInput;
		if ( state.env.rules_edit && $( 'wcj-rj-rule' ).open && ruleIntent ) {
			d.rule = kind + ':' + id + '=' + ( exclude ? 'exclude' : days );
		}
		return d;
	}

	function issues( list ) {
		document.querySelectorAll( '.wcj-rj-issue' ).forEach( function ( el ) {
			el.textContent = list && list[ el.getAttribute( 'data-for' ) ] ? list[ el.getAttribute( 'data-for' ) ] : '';
		} );
	}

	function rulesNote( env ) {
		var el = $( 'wcj-rj-rules' );
		el.hidden = 0 === env.rules;
		if ( 0 === env.rules ) {
			return;
		}
		var text;
		if ( env.rules < 0 ) {
			text = 'Item rules are stored in a format this page does not read. They are kept as saved.';
		} else if ( env.enforced ) {
			text = env.rules + ' item rule line(s) are saved and can change the window for matching products. They are kept as saved and not shown in the storewide example; see all of them in the full settings.';
		} else {
			text = env.rules + ' item rule line(s) are saved from Booster Elite. They are kept untouched, but this edition does not apply them.';
		}
		el.textContent = text;
	}

	function fill( data ) {
		state = data;
		var s = data.saved;
		$( 'wcj-rj-form' ).hidden = false;
		$( 'wcj-rj-preview' ).hidden = true;
		$( 'wcj-rj-window' ).value = s.window_valid ? s.window : '';
		if ( ! s.window_valid ) {
			msg( 'The saved return window is not a valid number of days, so new requests are blocked. Enter a window to fix it.', true );
		}
		$( 'wcj-rj-notify' ).checked = 'yes' === s.notify;
		$( 'wcj-rj-module' ).checked = false;
		$( 'wcj-rj-module-row' ).hidden = 'yes' === s.module || data.env.lock;
		$( 'wcj-rj-module-lock' ).hidden = 'yes' === s.module || ! data.env.lock;
		$( 'wcj-rj-module-on' ).hidden = false;
		$( 'wcj-rj-module-on' ).textContent = 'yes' === s.module ? 'Return requests: On.' : 'Return requests: Off. Customers cannot ask yet.';
		$( 'wcj-rj-next-check' ).textContent = 'yes' !== s.module ? 'Turn Returns on first if intended; saved window and queue settings alone do not enable requests. Then use an authorized eligible test account and order to check the form. Do not refund, restock or send a notice.' : ( ! s.window_valid ? 'Fix the invalid saved window before checking the request form. New requests are blocked.' : 'Check Returns setup, then use an authorized eligible test account and order to check the request form. Do not refund, restock or send a notice as a visual check.' );
		$( 'wcj-rj-rule' ).hidden = ! data.env.rules_edit;
		$( 'wcj-rj-rule-id' ).value = '';
		$( 'wcj-rj-rule-days' ).value = '';
		$( 'wcj-rj-rule-kind' ).value = 'product';
		$( 'wcj-rj-rule-mode' ).value = 'days';
		$( 'wcj-rj-rule-days-wrap' ).hidden = false;
		rulesNote( data.env );
		issues( null );
		receipt( data.receipt );
		$( 'wcj-rj-review' ).disabled = !! ( data.receipt && data.receipt.pending );
	}

	function humanValue( field, value ) {
		if ( ( field === 'notify' || field === 'module' ) && ( value === 'yes' || value === 'no' ) ) { return value === 'yes' ? 'On' : 'Off'; }
		return null === value ? '(not set)' : value;
	}

	function rowText( row ) {
		if ( 'rule' === row.field ) {
			return label.rule + ', ' + row.target + ': ' + ruleValue( row.before ) + ' → ' + ruleValue( row.after );
		}
		return ( label[ row.field ] || row.field ) + ': ' + humanValue( row.field, row.before ) + ' → ' + humanValue( row.field, row.after );
	}

	function receipt( r ) {
		var box = $( 'wcj-rj-receipt' );
		box.hidden = ! r || ( ! r.rows.length && ! r.unfinished && ! r.pending );
		if ( box.hidden ) {
			return;
		}
		var ordinary = r.status === 'applied' && ! r.pending && ! r.unfinished && ! r.earlier.length && r.rows.length > 0 && r.rows.every( function ( row ) { return row.still_ours; } );
		$( 'wcj-rj-receipt-title' ).textContent = ordinary ? 'Saved: ' + r.rows.length + ( r.rows.length === 1 ? ' change' : ' changes' ) : 'Last change: check affected rows';
		$( 'wcj-rj-receipt-details' ).open = ! ordinary;
		var raw = $( 'wcj-rj-receipt-raw' ); raw.textContent = '';
		var ul = $( 'wcj-rj-receipt-rows' );
		ul.textContent = '';
		r.rows.concat( r.earlier.map( function ( row ) {
			return Object.assign( { earlier: true }, row );
		} ) ).forEach( function ( row ) {
			var li = document.createElement( 'li' );
			li.textContent = ( row.earlier ? 'Earlier: ' : '' ) + rowText( row ) + ( row.still_ours ? '' : ' (changed since)' );
			ul.appendChild( li );
			var technical = document.createElement( 'li' );
			technical.textContent = ( row.earlier ? 'Earlier: ' : '' ) + row.field + ': ' + row.before + ' -> ' + row.after; raw.appendChild( technical );
		} );
		var note = r.time ? 'Applied ' + r.time + '. ' : '';
		if ( ! r.undo && r.rows.length && ! r.pending ) {
			note += r.guard_ok ? 'Undo is not available because this change is no longer exactly as applied.' : 'Undo is not available because item rules, guest or role settings changed since.';
		}
		$( 'wcj-rj-receipt-note' ).textContent = note;
		$( 'wcj-rj-pending' ).hidden = ! r.pending;
		$( 'wcj-rj-undo' ).hidden = ! r.undo;
	}

	function cell( tr, text, tag ) {
		var td = document.createElement( tag || 'td' );
		td.textContent = text;
		tr.appendChild( td );
		return td;
	}

	function showPreview( p ) {
		var body = $( 'wcj-rj-changes' ).tBodies[ 0 ];
		body.textContent = '';
		var windowWarning = p.now.every( function ( e ) {
			return 'off' === e.state;
		} ) ? ' (shorter window: fewer elapsed days allowed)' : ' (shorter: some orders lose the chance to start a request)';
		p.changes.forEach( function ( c ) {
			var tr = document.createElement( 'tr' );
			var rule = 'rule' === c.field;
			cell( tr, rule ? label.rule + ': ' + c.target : label[ c.field ] || c.field, 'th' );
			if ( rule ) {
				cell( tr, ruleValue( c.before ) );
			} else {
				cell( tr, c.before === '' ? '(invalid)' : humanValue( c.field, c.before ) );
			}
			var after = cell( tr, ( rule ? ruleValue( c.after ) : humanValue( c.field, c.after ) ) + ( c.shorter ? ( rule ? ' (fewer days or no returns for this item)' : windowWarning ) : '' ) );
			if ( c.shorter ) {
				after.className = 'wcj-rj-warn';
			}
			body.appendChild( tr );
		} );
		var ex = $( 'wcj-rj-example' );
		var head = ex.tHead.rows[ 0 ];
		while ( head.cells.length > 1 ) {
			head.deleteCell( 1 );
		}
		p.now.forEach( function ( e ) {
			cell( head, 'Completed ' + e.age + ' days ago', 'th' );
		} );
		var eb = ex.tBodies[ 0 ];
		eb.textContent = '';
		var sides = [ [ 'Now', p.now, null ], [ 'After', p.after, p.now ] ];
		if ( p.item ) {
			sides.push( [ 'This item now', p.item.now, null ], [ 'This item after', p.item.after, p.item.now ] );
		}
		sides.forEach( function ( side ) {
			var tr = document.createElement( 'tr' );
			cell( tr, side[ 0 ], 'th' );
			side[ 1 ].forEach( function ( e, i ) {
				var td = cell( tr, stateText[ e.state ] || e.state );
				if ( side[ 2 ] && e.state !== side[ 2 ][ i ].state ) {
					td.className = 'wcj-rj-diff';
				}
			} );
			eb.appendChild( tr );
		} );
		$( 'wcj-rj-cav-rule' ).hidden = ! p.item;
		$( 'wcj-rj-cav-roles' ).hidden = ! p.env.roles;
		$( 'wcj-rj-cav-module' ).hidden = ! p.changes.some( function ( c ) {
			return 'module' === c.field;
		} );
		$( 'wcj-rj-form' ).hidden = true;
		$( 'wcj-rj-preview' ).hidden = false;
	}

	function load() {
		post( 'load' ).then( function ( r ) {
			if ( r.success ) {
				fill( r.data );
			} else {
				msg( r.data && r.data.error, true );
			}
		} );
	}

	document.querySelectorAll( '.wcj-rj-presets button' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			$( 'wcj-rj-window' ).value = b.getAttribute( 'data-days' );
		} );
	} );

	$( 'wcj-rj-review' ).addEventListener( 'click', function () {
		var d = draft();
		if ( ! Object.keys( d ).length ) {
			msg( 'Nothing to change yet.' );
			return;
		}
		msg( '' );
		post( 'preview', { draft: JSON.stringify( d ) } ).then( function ( r ) {
			if ( r.success ) {
				issues( null );
				if ( ! r.data.changes.length ) {
					msg( 'These values are already saved.' );
					return;
				}
				showPreview( r.data );
			} else {
				issues( r.data && r.data.issues );
				msg( r.data && r.data.error, true );
			}
		} );
	} );

	$( 'wcj-rj-back' ).addEventListener( 'click', function () {
		$( 'wcj-rj-preview' ).hidden = true;
		$( 'wcj-rj-form' ).hidden = false;
	} );

	$( 'wcj-rj-apply' ).addEventListener( 'click', function () {
		var btn = this;
		btn.disabled = true;
		post( 'apply', { draft: JSON.stringify( draft() ), base: JSON.stringify( state.base ), confirm: '1' } ).then( function ( r ) {
			btn.disabled = false;
			var d = r.data || {};
			if ( d.fresh ) {
				fill( d.fresh );
			} else if ( d.issues ) {
				$( 'wcj-rj-preview' ).hidden = true;
				$( 'wcj-rj-form' ).hidden = false;
				issues( d.issues );
			}
			msg( r.success ? d.message : d.error, ! r.success );
			if ( r.success && d.fresh && d.fresh.receipt && ! $( 'wcj-rj-receipt-details' ).open ) { $( 'wcj-rj-msg' ).textContent = $( 'wcj-rj-receipt-title' ).textContent; $( 'wcj-rj-msg' ).classList.add( 'screen-reader-text' ); }
			var diag = $( 'wcj-rj-diag' );
			diag.hidden = ! ( r.success && diagText[ d.diagnostic ] );
			diag.textContent = r.success && diagText[ d.diagnostic ] ? diagText[ d.diagnostic ] : '';
			diag.className = 'wcj-rj-note' + ( 'policy_invalid' === d.diagnostic || 'policy_facts_unavailable' === d.diagnostic ? ' is-error' : '' );
			if ( r.success && 'policy_invalid' === d.diagnostic ) {
				$( 'wcj-rj-undo' ).focus();
			}
		} );
	} );

	$( 'wcj-rj-undo' ).addEventListener( 'click', function () {
		var btn = this;
		btn.disabled = true;
		post( 'recover' ).then( function ( r ) {
			btn.disabled = false;
			var d = r.data || {};
			if ( d.fresh ) {
				fill( d.fresh );
			}
			$( 'wcj-rj-diag' ).hidden = true;
			msg( d.message || d.error, ! r.success || 'restored' !== d.status );
		} );
	} );

	$( 'wcj-rj-resolve' ).addEventListener( 'click', function () {
		var btn = this;
		btn.disabled = true;
		post( 'resolve', { confirm: '1' } ).then( function ( r ) {
			btn.disabled = false;
			var d = r.data || {};
			if ( d.fresh ) {
				fill( d.fresh );
			}
			msg( d.message || d.error, ! r.success );
		} );
	} );

	$( 'wcj-rj-rule-mode' ).addEventListener( 'change', function () {
		$( 'wcj-rj-rule-days-wrap' ).hidden = 'exclude' === this.value;
	} );

	load();
}() );

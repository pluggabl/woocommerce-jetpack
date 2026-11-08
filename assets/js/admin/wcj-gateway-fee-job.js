/* Payment method fee job. One method, one draft, a Now/After fee estimate on one example cart, one apply, undo. */
( function () {
	'use strict';
	var cfg = window.wcjGatewayFeeJob;
	var root = document.getElementById( 'wcj-gf' );
	if ( ! cfg || ! root ) {
		return;
	}
	var GW = String( cfg.gateway || '' );
	var FIELDS = [ 'text', 'kind', 'amount', 'type', 'min', 'max' ];
	var LABELS = { text: 'Name', type: 'Amount type', value: 'Amount', min: 'At least', max: 'At most', module: 'Fees & Discounts' };
	var REASONS = {
		not_configured: 'the name is blank, so this fee is off',
		outside_cart_amount: 'the example cart is outside the cart limits',
		zero_or_invalid_amount: 'the amount is 0'
	};
	// Incomplete records are never labelled as applied.
	var STATUS = { applied: 'Applied', undo_reversed: 'Undo reversed; changes remain', restored: 'Undone', apply_incomplete: 'Not fully applied', undo_incomplete: 'Not fully undone' };
	var STATUS_NOTE = {
		apply_incomplete: ' – this apply failed and could not be fully put back; check all fee settings.',
		undo_incomplete: ' – not fully undone; check all fee settings.'
	};

	var saved = null; // Server load(): gateway, rule, locked, base, env, methods, receipt.
	var draft = {};
	var pv = null; // Last preview result.
	var pvFor = null; // Request (JSON) that preview was made for.
	var pvSeq = 0;
	var timer = null;
	var busy = false;
	var reviewing = false;
	var stale = null; // { rebase, fresh } after a refused apply.
	var notice = ''; // Note kept in the status line until the draft changes.

	function $( id ) {
		return document.getElementById( id );
	}
	function el( tag, attrs, text ) {
		var n = document.createElement( tag );
		Object.keys( attrs || {} ).forEach( function ( k ) {
			n.setAttribute( k, attrs[ k ] );
		} );
		if ( text !== undefined && text !== null ) {
			n.textContent = text;
		}
		return n;
	}
	function has( o, k ) {
		return Object.prototype.hasOwnProperty.call( o, k );
	}
	function say( msg, kind ) {
		var s = $( 'wcj-gf-status' );
		s.className = 'wcj-ij-status' + ( kind ? ' is-' + kind : '' );
		s.textContent = msg || '';
	}
	function post( op, data ) {
		var body = new FormData();
		body.append( 'action', 'wcj_gateway_fee_job_' + op );
		body.append( 'nonce', cfg.nonces[ op ] );
		body.append( 'gateway', GW );
		Object.keys( data || {} ).forEach( function ( k ) {
			body.append( k, data[ k ] );
		} );
		return fetch( cfg.url, { method: 'POST', credentials: 'same-origin', body: body } ).then( function ( r ) {
			return r.text().then( function ( t ) {
				try {
					return JSON.parse( t );
				} catch ( e ) {
					throw new Error( 'Something went wrong and nothing is confirmed. Reload to see what is saved.' );
				}
			} );
		} );
	}

	/* ---------- Money and wording ---------- */
	function money( n ) {
		var env = saved.env;
		var d = parseInt( env.decimals, 10 );
		return ( env.currency ? env.currency + ' ' : '' ) + Math.abs( n ).toFixed( isNaN( d ) ? 2 : d );
	}
	function outcome( r ) {
		if ( r.state === 'fee' ) {
			return { big: 'Fee +' + money( r.amount ), cls: 'is-fee' };
		}
		if ( r.state === 'discount' ) {
			return { big: 'Discount −' + money( r.amount ), cls: 'is-discount' };
		}
		if ( r.state === 'none' ) {
			return { big: 'No fee', small: REASONS[ r.reason ] ? 'Because ' + REASONS[ r.reason ] + '.' : '', cls: 'is-none' };
		}
		if ( r.state === 'off' ) {
			return { big: 'No fee', small: 'Fees & Discounts is off.', cls: 'is-none' };
		}
		return { big: 'Can’t estimate here', small: 'Check this method in all fee settings.', cls: 'is-none' };
	}
	/* An amount in the unit its own side had: 'percent', 'fixed' (currency of cur) or null (not recorded). */
	function amount( v, unit, cur ) {
		if ( unit === 'percent' ) {
			return String( v ).replace( /^-/, '' ) + '%';
		}
		if ( unit === 'fixed' ) {
			var d = parseInt( cur.decimals, 10 );
			return ( cur.currency ? cur.currency + ' ' : '' ) + Math.abs( parseFloat( v ) ).toFixed( isNaN( d ) ? 2 : d );
		}
		return String( v ).replace( /^-/, '' ) + ' (unit not recorded)';
	}
	function shown( field, v, unit, cur ) {
		switch ( field ) {
			case 'text':
				return v === '' ? '(blank – fee off)' : '“' + v + '”';
			case 'type':
				return v === 'percent' ? '% of cart' : 'Fixed';
			case 'value':
				var n = parseFloat( v );
				if ( ! n ) {
					return '0 (no fee)';
				}
				return ( n < 0 ? 'Discount ' : 'Fee ' ) + amount( v, unit, cur );
			case 'min':
			case 'max':
				return parseFloat( v ) ? amount( v, unit, cur ) : 'No limit';
			case 'module':
				return v === 'yes' ? 'On' : 'Off';
		}
		return String( v );
	}
	function changeItem( c, cur ) {
		var li = el( 'li' );
		li.appendChild( el( 'span', { class: 'wcj-ij-rl' }, LABELS[ c.field ] || c.field ) );
		li.appendChild( el( 'span', {}, c.before === null ? '(not set)' : shown( c.field, c.before, c.unit_before, cur ) ) );
		li.appendChild( el( 'span', { class: 'wcj-ij-arrow' }, '→' ) );
		li.appendChild( el( 'span', {}, shown( c.field, c.after, c.unit_after, cur ) ) );
		return li;
	}

	/* ---------- Draft state ---------- */
	function current( k ) {
		return has( draft, k ) ? draft[ k ] : ( saved.rule ? saved.rule[ k ] : '' );
	}
	/* A drafted switch-on that no longer applies after fresh settings: dropped, and said so. */
	function reconcile() {
		if ( draft.module !== 'yes' || ! ( saved.env.module_on || saved.env.toggle_blocked ) ) {
			return '';
		}
		delete draft.module;
		return saved.env.module_on ? 'Fees & Discounts is already on now, so your draft no longer turns it on; your fee changes apply as soon as you apply them.' : 'Fees & Discounts can’t be turned on here now: ' + saved.env.toggle_blocked + ' Your other changes are kept, but no fee is charged while it is off.';
	}
	function count() {
		return pv && pvFor === request() && pv.changes ? pv.changes.length : Object.keys( draft ).length;
	}
	function request() {
		return JSON.stringify( { d: draft, c: context() } );
	}
	function context() {
		return { subtotal: $( 'wcj-gf-subtotal' ).value.trim(), shipping: $( 'wcj-gf-shipping' ).value.trim() };
	}
	function setDraft( k, v ) {
		if ( ! saved || ! saved.rule ) {
			return;
		}
		notice = '';
		var same = k === 'module' ? v !== 'yes' : saved.rule && String( saved.rule[ k ] ) === String( v );
		if ( k === 'text' ) {
			same = saved.rule && saved.rule.text === String( v ).trim();
		}
		if ( same ) {
			delete draft[ k ];
		} else {
			draft[ k ] = v;
		}
		// The amount and its sign are one native value: send both together.
		if ( ( k === 'kind' || k === 'amount' ) && ( has( draft, 'kind' ) || has( draft, 'amount' ) ) ) {
			if ( ! has( draft, 'kind' ) ) {
				draft.kind = saved.rule.kind;
			}
			if ( ! has( draft, 'amount' ) ) {
				draft.amount = saved.rule.amount;
			}
			if ( draft.kind === saved.rule.kind && String( draft.amount ) === String( saved.rule.amount ) ) {
				delete draft.kind;
				delete draft.amount;
			}
		}
		stale = null;
		closeReview();
		refresh();
		schedule();
	}
	function dropChange( field ) {
		( field === 'value' ? [ 'kind', 'amount' ] : [ field ] ).forEach( function ( k ) {
			delete draft[ k ];
		} );
		fill();
		refresh();
		schedule();
	}

	/* ---------- Form ---------- */
	function fill() {
		if ( ! saved.rule ) {
			return;
		}
		$( 'wcj-gf-text' ).value = current( 'text' );
		$( 'wcj-gf-amount' ).value = current( 'amount' );
		$( 'wcj-gf-min' ).value = current( 'min' );
		$( 'wcj-gf-max' ).value = current( 'max' );
		$( 'wcj-gf-module' ).checked = draft.module === 'yes';
		root.querySelectorAll( '[data-set]' ).forEach( function ( b ) {
			var p = b.getAttribute( 'data-set' ).split( ':' );
			b.setAttribute( 'aria-pressed', current( p[ 0 ] ) === p[ 1 ] ? 'true' : 'false' );
		} );
		$( 'wcj-gf-fixed' ).textContent = 'Fixed' + ( saved.env.currency ? ' ' + saved.env.currency : '' );
	}
	function renderEnv() {
		var env = saved.env;
		var box = $( 'wcj-gf-env' );
		box.textContent = '';
		var notes = [];
		if ( ! env.woo_enabled ) {
			notes.push( [ env.title + ' is off in WooCommerce payments, so shoppers can’t choose it. This page never turns it on.', 'warn' ] );
		}
		if ( env.roles_policy && env.module_on ) {
			notes.push( [ 'Modules by user roles is on: some shopper roles may not get this fee.', 'warn' ] );
		}
		notes.forEach( function ( n ) {
			box.appendChild( el( 'p', { class: 'wcj-ij-note is-' + n[ 1 ] }, n[ 0 ] ) );
		} );
		var lock = $( 'wcj-gf-locked' );
		lock.textContent = '';
		lock.hidden = ! saved.locked;
		$( 'wcj-gf-fields' ).hidden = !! saved.locked;
		if ( saved.locked ) {
			lock.appendChild( el( 'p', { class: 'wcj-gf-lock-h' }, 'This method’s fee is kept as saved here' ) );
			lock.appendChild( el( 'p', {}, saved.locked ) );
			var a = el( 'a', { href: cfg.native, class: 'button' }, 'Open all fee settings' );
			lock.appendChild( a );
		}
		var mbox = $( 'wcj-gf-module-box' );
		mbox.hidden = !! saved.locked || env.module_on;
		$( 'wcj-gf-module' ).disabled = !! env.toggle_blocked;
		$( 'wcj-gf-module-hint' ).textContent = env.toggle_blocked ? env.toggle_blocked + ' Change it in Booster settings.' : 'Off now, so no payment method fee is charged. Turning it on also lets other methods’ saved fees apply.';
		var kept = $( 'wcj-gf-kept' );
		kept.textContent = '';
		( env.kept || [] ).forEach( function ( k ) {
			kept.appendChild( el( 'li', {}, k ) );
		} );
		$( 'wcj-gf-kept-box' ).hidden = ! ( env.kept && env.kept.length );
		var sel = $( 'wcj-gf-method' );
		sel.textContent = '';
		saved.methods.forEach( function ( m ) {
			var o = el( 'option', { value: m.id }, m.title + ( m.rule ? ' – has a fee' : '' ) + ( m.enabled ? '' : ' (off in WooCommerce)' ) );
			if ( m.id === GW ) {
				o.selected = true;
			}
			sel.appendChild( o );
		} );
	}

	/* ---------- Preview ---------- */
	function schedule() {
		clearTimeout( timer );
		$( 'wcj-gf-fresh' ).textContent = 'Updating…';
		$( 'wcj-gf-fresh' ).className = 'wcj-ij-fresh is-stale';
		timer = setTimeout( preview, 250 );
	}
	function preview() {
		if ( ! saved || saved.locked ) {
			pv = null;
			pvFor = null;
			$( 'wcj-gf-fresh' ).textContent = '';
			renderCards();
			return;
		}
		var want = request();
		var seq = ++pvSeq;
		$( 'wcj-gf-fresh' ).textContent = 'Updating…';
		$( 'wcj-gf-fresh' ).className = 'wcj-ij-fresh is-stale';
		post( 'preview', { draft: JSON.stringify( draft ), context: JSON.stringify( context() ) } ).then( function ( res ) {
			if ( seq !== pvSeq ) {
				return;
			}
			showIssues( res.success ? {} : ( res.data && res.data.issues ) || {} );
			if ( ! res.success ) {
				pv = null;
				pvFor = null;
				$( 'wcj-gf-fresh' ).textContent = '';
				say( ( res.data && res.data.error ) || 'The preview could not be made.', 'error' );
			} else {
				pv = res.data;
				pvFor = want;
				$( 'wcj-gf-fresh' ).textContent = 'Estimate up to date';
				$( 'wcj-gf-fresh' ).className = 'wcj-ij-fresh';
				if ( ! stale ) {
					say( notice );
				}
			}
			renderCards();
			refresh();
		} ).catch( function ( e ) {
			if ( seq === pvSeq ) {
				pv = null;
				say( e.message, 'error' );
				refresh();
			}
		} );
	}
	function showIssues( issues ) {
		[ 'text', 'kind', 'amount', 'type', 'min', 'max', 'module' ].forEach( function ( k ) {
			$( 'wcj-gf-err-' + k ).textContent = issues[ k ] || '';
		} );
	}
	function card( id, r, after ) {
		var body = $( id ).querySelector( '.wcj-gf-card-b' );
		body.textContent = '';
		if ( ! r ) {
			body.appendChild( el( 'p', { class: 'wcj-gf-big is-none' }, '—' ) );
			return;
		}
		var o = outcome( r );
		if ( r.state === 'off' && after ) {
			o.small = saved.env.toggle_blocked ? 'Fees & Discounts is off.' : 'Fees & Discounts stays off. Tick “Turn on Fees & Discounts” to charge it.';
		}
		body.appendChild( el( 'p', { class: 'wcj-gf-big ' + o.cls }, o.big ) );
		if ( o.small ) {
			body.appendChild( el( 'p', { class: 'wcj-gf-small' }, o.small ) );
		}
		if ( r.state === 'off' && r.inactive && ( r.inactive.state === 'fee' || r.inactive.state === 'discount' ) ) {
			body.appendChild( el( 'p', { class: 'wcj-gf-inactive' }, 'If it were on: ' + outcome( r.inactive ).big + ' – not charged' ) );
		}
		var name = after ? current( 'text' ) : saved.rule && saved.rule.text;
		if ( ( r.state === 'fee' || r.state === 'discount' ) && name ) {
			body.appendChild( el( 'p', { class: 'wcj-gf-small' }, 'Shown to shoppers as “' + name + '”' ) );
		}
		if ( after && r.state === 'discount' && pv && Math.abs( r.amount ) >= pv.facts.subtotal + pv.facts.shipping ) {
			body.appendChild( el( 'p', { class: 'wcj-gf-small is-warn' }, 'This discount is as large as the cart; WooCommerce may limit it.' ) );
		}
	}
	function renderCards() {
		var hasDraft = Object.keys( draft ).length > 0;
		$( 'wcj-gf-after' ).hidden = ! hasDraft;
		$( 'wcj-gf-now' ).querySelector( '.wcj-gf-card-h' ).textContent = hasDraft ? 'Now' : 'Current';
		$( 'wcj-gf-now' ).parentElement.classList.toggle( 'is-current', ! hasDraft );
		if ( saved && saved.locked ) {
			// A kept rule is not estimated here; say so instead of leaving the cards blank.
			card( 'wcj-gf-now', { state: 'unsupported' }, false );
			card( 'wcj-gf-after', { state: 'unsupported' }, true );
			$( 'wcj-gf-after' ).classList.remove( 'is-stale', 'is-changed' );
			return;
		}
		var fresh = pv && pvFor === request();
		card( 'wcj-gf-now', pv ? pv.now : null, false );
		card( 'wcj-gf-after', pv ? pv.after : null, true );
		$( 'wcj-gf-after' ).classList.toggle( 'is-stale', ! fresh );
		var diff = pv && JSON.stringify( pv.now ) !== JSON.stringify( pv.after );
		$( 'wcj-gf-after' ).classList.toggle( 'is-changed', !! diff );
	}

	/* ---------- Draft bar, rows, review ---------- */
	function rowsOf() {
		return pv && pvFor === request() && pv.changes ? pv.changes : [];
	}
	function refresh() {
		var n = Object.keys( draft ).length;
		var ready = !! ( pv && pvFor === request() && pv.class !== 'none' );
		$( 'wcj-ij-draftbar' ).hidden = ! n && ! stale;
		$( 'wcj-gf-count' ).textContent = n ? count() + ( count() === 1 ? ' change' : ' changes' ) : '';
		$( 'wcj-gf-primary' ).disabled = busy || ! ready || !! saved.locked || !! stale;
		$( 'wcj-gf-primary' ).textContent = pv && pv.class === 'label' ? 'Apply' : 'Review';
		$( 'wcj-gf-discard' ).hidden = ! n;
		var r = saved.receipt;
		var showSaved = ! n && ! stale && r && r.rows && r.rows.length;
		$( 'wcj-ij-savedbar' ).hidden = ! showSaved;
		if ( showSaved ) {
			$( 'wcj-gf-saved' ).textContent = ( STATUS[ r.status ] || 'Last change' ) + ( r.title && r.gateway !== GW ? ' (' + r.title + ')' : '' ) + ( r.time ? ' ' + r.time : '' );
			if ( r.status === 'applied' && ! r.unfinished && ! r.unreadable && ! r.context_changed && r.rows.every( function ( row ) { return row.still_ours; } ) ) { $( 'wcj-gf-saved' ).textContent = 'Saved: ' + r.rows.length + ( r.rows.length === 1 ? ' change' : ' changes' ) + ( r.title && r.gateway !== GW ? ' (' + r.title + ')' : '' ); }
			$( 'wcj-gf-undo' ).hidden = ! r.undo;
		}
		var list = $( 'wcj-gf-rows' );
		list.textContent = '';
		rowsOf().forEach( function ( c ) {
			var li = changeItem( c, saved.env );
			var undo = el( 'button', { type: 'button', class: 'button-link' }, 'Undo' );
			undo.addEventListener( 'click', function () {
				dropChange( c.field );
			} );
			li.appendChild( undo );
			list.appendChild( li );
		} );
		if ( ! n ) {
			$( 'wcj-gf-rows-box' ).hidden = true;
			$( 'wcj-gf-count' ).setAttribute( 'aria-expanded', 'false' );
		}
		renderReceipt();
	}
	function closeReview() {
		reviewing = false;
		$( 'wcj-gf-review' ).hidden = true;
		$( 'wcj-ij-draftbar' ).classList.remove( 'is-review' );
	}
	function otherLine( o ) {
		var r = o.result;
		var s = o.title + ': ';
		if ( r.state === 'fee' || r.state === 'discount' ) {
			s += outcome( r ).big + ' on this example cart. Configured; may apply when shoppers choose it and its conditions match.';
		} else if ( r.state === 'none' ) {
			s += 'has a saved fee, but none on this example cart.';
		} else {
			s += 'has a saved fee that depends on roles, products or other settings. It can’t be estimated here.';
		}
		if ( ! o.enabled ) {
			s += ' Off in WooCommerce payments now.';
		}
		return s;
	}
	function openReview() {
		var box = $( 'wcj-gf-review' );
		box.textContent = '';
		reviewing = true;
		// The review lists the same rows; don't show them twice.
		$( 'wcj-gf-rows-box' ).hidden = true;
		$( 'wcj-gf-count' ).setAttribute( 'aria-expanded', 'false' );
		box.appendChild( el( 'h3', {}, 'Review before applying' ) );
		var ul = el( 'ul', { class: 'wcj-ij-rows wcj-gf-review-rows' } );
		pv.changes.forEach( function ( c ) {
			ul.appendChild( changeItem( c, saved.env ) );
		} );
		box.appendChild( ul );
		var cq = el( 'ul', { class: 'wcj-ij-cqs' } );
		cq.appendChild( el( 'li', {}, 'On this example cart: ' + outcome( pv.now ).big + ' now, ' + outcome( pv.after ).big + ' after.' ) );
		cq.appendChild( el( 'li', {}, 'Unpaid orders that customers pay later through Pay for order may be recalculated with the new fee. Apply changes settings only; paid orders and order history are not changed.' ) );
		var confirm = null;
		if ( pv.class === 'global' ) {
			( pv.others || [] ).forEach( function ( o ) {
				cq.appendChild( el( 'li', {}, otherLine( o ) ) );
			} );
			if ( saved.env.force_default ) {
				cq.appendChild( el( 'li', {}, 'Force default payment method is on: the first available method is preselected at cart and checkout, so its fee may apply before the shopper chooses.' ) );
			}
			if ( saved.env.klarna_alias ) {
				cq.appendChild( el( 'li', {}, 'Klarna alias is on: Klarna methods use the Klarna fee settings.' ) );
			}
		}
		box.appendChild( cq );
		if ( pv.class === 'global' ) {
			var lab = el( 'label', { class: 'wcj-ij-ck' } );
			confirm = el( 'input', { type: 'checkbox', id: 'wcj-gf-confirm' } );
			lab.appendChild( confirm );
			lab.appendChild( document.createTextNode( ' Turn on Fees & Discounts for the whole shop' ) );
			box.appendChild( lab );
		}
		var actions = el( 'p', { class: 'wcj-ij-ra' } );
		var go = el( 'button', { type: 'button', class: 'button button-primary' }, 'Apply changes' );
		var back = el( 'button', { type: 'button', class: 'button-link' }, 'Back to editing' );
		go.disabled = !! confirm;
		if ( confirm ) {
			confirm.addEventListener( 'change', function () {
				go.disabled = ! confirm.checked;
			} );
		}
		go.addEventListener( 'click', function () {
			apply( true );
		} );
		back.addEventListener( 'click', closeReview );
		actions.appendChild( go );
		actions.appendChild( back );
		box.appendChild( actions );
		box.hidden = false;
		$( 'wcj-ij-draftbar' ).classList.add( 'is-review' );
		box.focus();
	}

	/* ---------- Apply, stale, undo ---------- */
	function setSaved( data ) {
		saved = data;
		GW = data.gateway;
		var note = reconcile();
		renderEnv();
		fill();
		refresh();
		if ( note ) {
			notice = note;
		}
		return note;
	}
	function apply( confirmed ) {
		if ( busy ) {
			return;
		}
		busy = true;
		refresh();
		say( 'Applying…' );
		var data = { draft: JSON.stringify( draft ), base: JSON.stringify( saved.base ) };
		if ( confirmed ) {
			data.confirm = '1';
		}
		post( 'apply', data ).then( function ( res ) {
			busy = false;
			var d = res.data || {};
			closeReview();
			if ( res.success && ( d.status === 'applied' || d.status === 'unchanged' ) ) {
				draft = {};
				stale = null;
				notice = '';
				setSaved( d.fresh );
				say( d.status === 'applied' && saved.receipt && saved.receipt.undo ? $( 'wcj-gf-saved' ).textContent + '. Undo is available.' : d.message, d.status === 'applied' && saved.receipt && saved.receipt.undo ? 'receipt' : 'ok' );
				preview();
				return;
			}
			if ( d.status === 'stale' ) {
				stale = { rebase: !! d.rebase, fresh: d.fresh };
				showStale( d.error );
				refresh();
				return;
			}
			if ( d.status === 'invalid' ) {
				showIssues( d.issues || {} );
			}
			var note = '';
			if ( d.fresh ) {
				note = setSaved( d.fresh );
				fill();
				preview();
			}
			say( ( d.error || 'Nothing was changed.' ) + ( d.kept && d.kept.length ? ' Still saved: ' + d.kept.map( function ( f ) {
				return LABELS[ f ] || f;
			} ).join( ', ' ) + '.' : '' ) + ( note ? ' ' + note : '' ), 'error' );
			refresh();
		} ).catch( function ( e ) {
			busy = false;
			say( e.message, 'error' );
			refresh();
		} );
	}
	function showStale( msg ) {
		say( msg, 'error' );
		var s = $( 'wcj-gf-status' );
		var b = el( 'button', { type: 'button', class: 'button-link wcj-gf-inline' }, stale.rebase ? 'Re-check and keep my draft' : 'Discard my draft and load what is saved' );
		b.addEventListener( 'click', function () {
			var fresh = stale.fresh;
			if ( ! stale.rebase ) {
				draft = {};
			}
			stale = null;
			notice = '';
			say( setSaved( fresh ) );
			fill();
			preview();
		} );
		s.appendChild( document.createTextNode( ' ' ) );
		s.appendChild( b );
	}
	function undo() {
		if ( busy ) {
			return;
		}
		busy = true;
		say( 'Undoing…' );
		post( 'recover', {} ).then( function ( res ) {
			busy = false;
			var d = res.data || {};
			if ( d.fresh && d.fresh.gateway !== GW ) {
				window.location.href = cfg.page + '&gateway=' + encodeURIComponent( d.fresh.gateway );
				return;
			}
			if ( d.fresh ) {
				setSaved( d.fresh );
				preview();
			}
			say( d.message || d.error || 'Nothing is confirmed. Reload to see what is saved.', res.success && d.status === 'restored' ? 'ok' : 'error' );
			refresh();
		} ).catch( function ( e ) {
			busy = false;
			say( e.message, 'error' );
		} );
	}
	function renderReceipt() {
		var r = saved.receipt;
		var box = $( 'wcj-gf-receipt' );
		var body = $( 'wcj-gf-receipt-body' );
		body.textContent = '';
		var rows = r && r.rows ? r.rows : [];
		box.hidden = ! ( r && ( rows.length || r.unfinished || r.unreadable ) );
		if ( box.hidden ) {
			return;
		}
		if ( r.status !== 'applied' || r.unfinished || r.unreadable || r.context_changed || rows.some( function ( row ) { return ! row.still_ours; } ) ) { box.open = true; }
		var notes = [];
		if ( r.legacy && rows.length ) {
			notes.push( 'Saved by an earlier version of this page: amounts are shown without units, and it can’t be undone here.' );
		}
		if ( r.context_changed ) {
			notes.push( 'Undo is unavailable because related fee settings changed. The newer settings have been kept. Open all fee settings to review them.' );
		}
		if ( r.unreadable ) {
			notes.push( 'Part of this record can’t be read, so it can’t be undone here. Check all fee settings.' );
		}
		if ( r.unfinished ) {
			notes.push( r.unfinished + ( r.unfinished === 1 ? ' earlier change here' : ' earlier changes here' ) + ' did not finish fully. Check all fee settings.' );
		}
		if ( rows.length ) {
			// Units come from the record itself, never from the rule as it is now.
			var cur = { currency: r.currency, decimals: r.decimals };
			body.appendChild( el( 'p', {}, ( r.title || r.gateway ) + ( r.time ? ', ' + r.time : '' ) + ( STATUS_NOTE[ r.status ] || '' ) ) );
			var ul = el( 'ul', { class: 'wcj-ij-rows' } );
			rows.forEach( function ( row ) {
				var li = changeItem( row, cur );
				li.appendChild( el( 'span', { class: 'wcj-ij-state' }, row.still_ours ? 'still saved' : 'changed since' ) );
				ul.appendChild( li );
			} );
			body.appendChild( ul );
		}
		notes.forEach( function ( n ) {
			body.appendChild( el( 'p', { class: 'wcj-ij-note is-warn' }, n ) );
		} );
		if ( rows.length ) {
			var tech = el( 'details', { class: 'wcj-ij-tech' } );
			tech.appendChild( el( 'summary', {}, 'Technical details' ) );
			rows.forEach( function ( row ) {
				tech.appendChild( el( 'div', {}, row.option + ( row.field === 'module' ? '' : '[' + r.gateway + ']' ) + ( row.created ? ' (row created by this apply)' : '' ) ) );
			} );
			body.appendChild( tech );
		}
	}

	/* ---------- Events ---------- */
	root.querySelectorAll( 'input[data-k]' ).forEach( function ( input ) {
		var k = input.getAttribute( 'data-k' );
		input.addEventListener( input.type === 'checkbox' ? 'change' : 'input', function () {
			setDraft( k, input.type === 'checkbox' ? ( input.checked ? 'yes' : '' ) : input.value );
		} );
	} );
	root.querySelectorAll( '[data-set]' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			var p = b.getAttribute( 'data-set' ).split( ':' );
			setDraft( p[ 0 ], p[ 1 ] );
			fill();
		} );
	} );
	[ 'wcj-gf-subtotal', 'wcj-gf-shipping' ].forEach( function ( id ) {
		$( id ).addEventListener( 'input', function () {
			closeReview();
			refresh();
			schedule();
		} );
	} );
	$( 'wcj-gf-count' ).addEventListener( 'click', function () {
		var box = $( 'wcj-gf-rows-box' );
		box.hidden = ! box.hidden;
		this.setAttribute( 'aria-expanded', box.hidden ? 'false' : 'true' );
	} );
	$( 'wcj-gf-primary' ).addEventListener( 'click', function () {
		if ( ! pv || pvFor !== request() ) {
			return;
		}
		if ( pv.class === 'label' ) {
			apply( false );
		} else if ( ! reviewing ) {
			openReview();
		}
	} );
	$( 'wcj-gf-discard' ).addEventListener( 'click', function () {
		draft = {};
		stale = null;
		notice = '';
		closeReview();
		showIssues( {} );
		fill();
		say( '' );
		refresh();
		schedule();
	} );
	$( 'wcj-gf-undo' ).addEventListener( 'click', undo );
	$( 'wcj-gf-method' ).addEventListener( 'change', function () {
		if ( Object.keys( draft ).length && ! window.confirm( 'Leave this method? Your unsaved draft will be discarded.' ) ) {
			this.value = GW;
			return;
		}
		window.location.href = cfg.page + '&gateway=' + encodeURIComponent( this.value );
	} );

	if ( ! GW ) {
		say( 'No payment methods are registered in WooCommerce.', 'error' );
		return;
	}
	post( 'load', {} ).then( function ( res ) {
		if ( ! res.success ) {
			say( ( res.data && res.data.error ) || 'Could not load the fee settings.', 'error' );
			return;
		}
		setSaved( res.data );
		preview();
	} ).catch( function ( e ) {
		say( e.message, 'error' );
	} );
}() );

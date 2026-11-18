/**
 * Optional extra job: one draft, native preview, review, apply and matching-only undo.
 * Server responses are the only source of saved state; the draft lives in localStorage until applied or discarded.
 *
 * @package Booster_For_WooCommerce/admin
 */
( function () {
	'use strict';
	var cfg = window.wcjProductExtraJob;
	if ( ! cfg ) {
		return;
	}
	var $ = function ( id ) {
		return document.getElementById( 'wcj-pe-' + id );
	};
	var KEY = 'wcjPeJob:' + cfg.site + ':' + cfg.user;
	var TEXT = [ 'label', 'title', 'tooltip' ];
	var S = {
		slot: cfg.slot,
		fresh: null,
		base: null,
		changes: {},
		unit: 'fixed',
		side: 'after',
		sample: 0,
		review: null,
		confirming: false,
		busy: false,
		// A saved draft that cannot be shown (the extra became settings-only) is held, never overwritten.
		held: false,
		seq: 0,
		cache: {}
	};

	function el( tag, cls, text ) {
		var n = document.createElement( tag );
		if ( cls ) {
			n.className = cls;
		}
		if ( undefined !== text && null !== text ) {
			n.textContent = text;
		}
		return n;
	}

	function clear( n ) {
		while ( n.firstChild ) {
			n.removeChild( n.firstChild );
		}
	}

	function post( op, data ) {
		var body = new URLSearchParams();
		body.append( 'action', 'wcj_product_extra_job_' + op );
		body.append( 'nonce', cfg.nonces[ op ] );
		body.append( 'slot', String( S.slot ) );
		Object.keys( data || {} ).forEach( function ( k ) {
			body.append( k, 'object' === typeof data[ k ] ? JSON.stringify( data[ k ] ) : String( data[ k ] ) );
		} );
		return fetch( cfg.url, { method: 'POST', credentials: 'same-origin', body: body } ).then( function ( r ) {
			return r.json().catch( function () {
				return { success: false, data: { error: 'The server answer could not be read. Reload to see the saved values.' } };
			} );
		} );
	}

	function status( text, kind ) {
		var s = $( 'status' );
		s.textContent = text || '';
		s.className = 'wcj-ij-status' + ( kind ? ' is-' + kind : '' );
	}

	// ----- Draft storage -------------------------------------------------

	// One draft per extra, so a draft parked on another extra is never overwritten.
	function stored( slot ) {
		try {
			var d = JSON.parse( window.localStorage.getItem( KEY + ':' + slot ) || 'null' );
			return d && 'object' === typeof d && d.changes && d.slot === slot ? d : null;
		} catch ( e ) {
			return null;
		}
	}

	function parkedElsewhere() {
		try {
			for ( var n = 0; n < window.localStorage.length; n++ ) {
				var k = window.localStorage.key( n );
				if ( 0 === k.indexOf( KEY + ':' ) ) {
					var slot = parseInt( k.slice( KEY.length + 1 ), 10 );
					if ( slot !== S.slot && stored( slot ) ) {
						return stored( slot );
					}
				}
			}
		} catch ( e ) {}
		return null;
	}

	function drop( slot ) {
		try {
			window.localStorage.removeItem( KEY + ':' + slot );
		} catch ( e ) {}
	}

	function persist() {
		if ( S.held ) {
			return;
		}
		try {
			if ( Object.keys( S.changes ).length ) {
				window.localStorage.setItem( KEY + ':' + S.slot, JSON.stringify( { slot: S.slot, changes: S.changes, base: S.base, sample: S.sample, time: Date.now() } ) );
			} else {
				drop( S.slot );
			}
		} catch ( e ) {}
	}

	function same( a, b ) {
		return JSON.stringify( a ) === JSON.stringify( b );
	}

	// ----- Values ----------------------------------------------------------

	function value( k ) {
		if ( Object.prototype.hasOwnProperty.call( S.changes, k ) ) {
			return S.changes[ k ];
		}
		if ( 'live' === k ) {
			return S.fresh.live ? 'yes' : 'no';
		}
		return S.fresh.values[ k ];
	}

	function saved( k ) {
		return 'live' === k ? ( S.fresh.live ? 'yes' : 'no' ) : S.fresh.values[ k ];
	}

	// Same normalisation as the server: surrounding and repeated spaces do not make a change.
	function norm( k, v ) {
		return -1 !== TEXT.indexOf( k ) && 'string' === typeof v ? v.replace( /\s+/g, ' ' ).trim() : v;
	}

	function set( k, v ) {
		if ( 'others' !== k && same( norm( k, v ), norm( k, saved( k ) ) ) && ! S.fresh.append ) {
			delete S.changes[ k ];
		} else {
			S.changes[ k ] = v;
		}
		if ( 'others' === k && ! activating() ) {
			delete S.changes.others;
		}
		S.confirming = false;
		persist();
		paint();
		schedule();
	}

	function activating() {
		var env = S.fresh.env;
		return 'yes' === value( 'live' ) && ( ! env.master || ! env.module ) && S.fresh.dormant.length > 0;
	}

	// ----- Painting --------------------------------------------------------

	function paintEnv() {
		var env = S.fresh.env;
		var box = $( 'env' );
		clear( box );
		var msgs = [];
		if ( ! env.loaded ) {
			msgs.push( 'Product Addons is not loaded in this request, so the preview cannot use its markup.' );
		}
		if ( ! env.module ) {
			msgs.push( 'Product Addons is off. No extras show to customers now.' );
		} else if ( ! env.master ) {
			msgs.push( 'Extras on all products are off. Only extras saved on individual products show now.' );
		}
		if ( ! env.can_add && S.fresh.slots.every( function ( s ) {
			return 'free' !== s.state;
		} ) ) {
			msgs.push( 'Every extra your Booster edition includes is in use. Edit one of them, or add more in a higher edition.' );
		}
		msgs.forEach( function ( m ) {
			box.appendChild( el( 'p', 'wcj-ij-envline', m ) );
		} );
	}

	function paintSlots() {
		var sel = $( 'slot' );
		clear( sel );
		var names = { live: 'shown', off: 'off', dormant: 'hidden', free: 'empty' };
		S.fresh.slots.forEach( function ( s ) {
			var o = el( 'option', '', s.name + ' · ' + names[ s.state ] + ( s.locked ? ' · settings only' : '' ) );
			o.value = String( s.slot );
			sel.appendChild( o );
		} );
		if ( S.fresh.env.can_add ) {
			var add = el( 'option', '', '+ Add another extra' );
			add.value = String( S.fresh.env.total + 1 );
			sel.appendChild( add );
		}
		sel.value = String( S.slot );
		$( 'slot-wrap' ).hidden = sel.options.length < 2;
	}

	function priceParts( p ) {
		p = 'string' === typeof p ? p : '';
		return '%' === p.slice( -1 ) ? { unit: 'percent', num: p.slice( 0, -1 ) } : { unit: 'fixed', num: p };
	}

	function fill() {
		var locked = S.fresh.locked.length > 0;
		$( 'live' ).checked = 'yes' === value( 'live' );
		TEXT.forEach( function ( k ) {
			$( k ).value = value( k ) || '';
		} );
		$( 'required' ).checked = 'yes' === value( 'required' );
		var pp = priceParts( value( 'price' ) );
		S.unit = pp.unit;
		$( 'price' ).value = pp.num;
		Array.prototype.forEach.call( $( 'form' ).querySelectorAll( 'input, button' ), function ( n ) {
			n.disabled = locked;
		} );
		var lb = $( 'locked' );
		clear( lb );
		lb.hidden = ! locked;
		if ( locked ) {
			lb.appendChild( el( 'p', 'wcj-pe-lockhead', 'This extra is managed in the Product Addons settings' ) );
			var ul = el( 'ul' );
			S.fresh.locked.forEach( function ( r ) {
				ul.appendChild( el( 'li', '', r ) );
			} );
			lb.appendChild( ul );
			var a = el( 'a', 'button', 'Open Product Addons settings' );
			a.href = cfg.native;
			lb.appendChild( a );
		}
		var kept = $( 'kept' );
		clear( kept );
		S.fresh.kept.forEach( function ( k ) {
			kept.appendChild( el( 'li', '', k.label + ': ' + k.value ) );
		} );
		$( 'kept-box' ).hidden = ! S.fresh.kept.length;
	}

	function paint() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-unit]' ), function ( b ) {
			b.setAttribute( 'aria-pressed', b.getAttribute( 'data-unit' ) === S.unit ? 'true' : 'false' );
		} );
		$( 'unit' ).textContent = 'percent' === S.unit ? '%' : '';
		Array.prototype.forEach.call( document.querySelectorAll( '[data-side]' ), function ( b ) {
			b.setAttribute( 'aria-pressed', b.getAttribute( 'data-side' ) === S.side ? 'true' : 'false' );
		} );
		paintChips();
		paintOthers();
		var n = S.review ? S.review.rows.length : 0;
		var dirty = Object.keys( S.changes ).length > 0;
		$( 'count' ).textContent = dirty ? ( n + ( 1 === n ? ' change' : ' changes' ) ) : 'No changes';
		$( 'primary' ).disabled = S.busy || ! dirty || ! S.review || ! n;
		$( 'primary' ).textContent = S.confirming ? 'Confirm and apply' : ( S.review && S.review.confirm ? 'Review and apply' : 'Apply' );
		$( 'discard' ).hidden = ! dirty;
		$( 'draftbar' ).hidden = ! dirty;
		// Undo belongs to the last saved change of this extra; it is not offered while a draft is open.
		var r = S.fresh.receipt;
		$( 'savedbar' ).hidden = dirty || ! r || ! r.undo || r.slot !== S.slot;
	}

	function paintChips() {
		var ids = value( 'exclude' ) || [];
		var names = S.fresh.excluded || {};
		var box = $( 'chips' );
		clear( box );
		ids.forEach( function ( id ) {
			var li = el( 'li', 'wcj-pe-chip', names[ id ] || ( '#' + id ) );
			var x = el( 'button', 'button-link', '×' );
			x.type = 'button';
			x.setAttribute( 'aria-label', 'Show it on ' + ( names[ id ] || ( '#' + id ) ) + ' too' );
			x.disabled = S.fresh.locked.length > 0;
			x.addEventListener( 'click', function () {
				set( 'exclude', ids.filter( function ( v ) {
					return v !== id;
				} ) );
			} );
			li.appendChild( x );
			box.appendChild( li );
		} );
		// The exact reach (published products only) is in the review; here only what was chosen.
		$( 'reach' ).textContent = ids.length ? 'All products except the ones below' : 'All ' + S.fresh.env.products + ' published products';
	}

	function paintOthers() {
		var show = activating();
		$( 'others' ).hidden = ! show;
		var list = $( 'others-list' );
		var focused = document.activeElement && list.contains( document.activeElement ) ? document.activeElement.name + '=' + document.activeElement.value : '';
		clear( list );
		if ( ! show ) {
			return;
		}
		var choices = S.changes.others || {};
		S.fresh.dormant.forEach( function ( d ) {
			var row = el( 'div', 'wcj-pe-other' );
			row.appendChild( el( 'span', 'wcj-pe-oname', d.name ) );
			[ [ 'pause', 'Keep hidden' ], [ 'show', 'Show it too' ] ].forEach( function ( c ) {
				var lab = el( 'label', 'wcj-ij-ck' );
				var r = el( 'input' );
				r.type = 'radio';
				r.name = 'wcj-pe-other-' + d.slot;
				r.value = c[ 0 ];
				r.checked = ( choices[ d.slot ] || '' ) === c[ 0 ];
				r.addEventListener( 'change', function () {
					var next = Object.assign( {}, S.changes.others || {} );
					next[ d.slot ] = c[ 0 ];
					set( 'others', next );
				} );
				lab.appendChild( r );
				lab.appendChild( document.createTextNode( ' ' + c[ 1 ] ) );
				row.appendChild( lab );
				if ( focused === r.name + '=' + r.value ) {
					window.setTimeout( function () {
						r.focus();
					}, 0 );
				}
			} );
			list.appendChild( row );
		} );
	}

	function paintErrors( issues ) {
		[ 'live', 'label', 'price', 'required', 'tooltip', 'title', 'exclude', 'others', 'general' ].forEach( function ( k ) {
			var n = $( 'err-' + k );
			if ( n ) {
				n.textContent = issues && issues[ k ] ? issues[ k ] : '';
			}
		} );
	}

	function paintRows() {
		var ul = $( 'rows' );
		clear( ul );
		( S.review ? S.review.rows : [] ).forEach( function ( r ) {
			var li = el( 'li', 'is-' + r.weight );
			li.appendChild( el( 'span', 'wcj-ij-rl', r.label ) );
			li.appendChild( el( 'del', '', r.before ) );
			li.appendChild( el( 'ins', '', r.after ) );
			ul.appendChild( li );
		} );
	}

	function paintReview() {
		var box = $( 'review' );
		clear( box );
		box.hidden = ! S.confirming;
		if ( ! S.confirming || ! S.review ) {
			return;
		}
		box.appendChild( el( 'p', 'wcj-ij-rhead', 'Check what customers will see change' ) );
		var ul = el( 'ul', 'wcj-ij-rows' );
		S.review.rows.forEach( function ( r ) {
			var li = el( 'li', 'is-' + r.weight );
			li.appendChild( el( 'span', 'wcj-ij-rl', r.label ) );
			li.appendChild( el( 'del', '', r.before ) );
			li.appendChild( el( 'ins', '', r.after ) );
			ul.appendChild( li );
		} );
		box.appendChild( ul );
		S.review.notes.forEach( function ( n ) {
			box.appendChild( el( 'p', 'wcj-pe-note', n ) );
		} );
		var cancel = el( 'button', 'button-link', 'Back to editing' );
		cancel.type = 'button';
		cancel.addEventListener( 'click', function () {
			S.confirming = false;
			paint();
			paintReview();
			$( 'primary' ).focus();
		} );
		box.appendChild( cancel );
		box.focus();
	}

	function frameDoc( html ) {
		return '<!doctype html><html><head><meta charset="utf-8"><style>' +
			'body{font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;margin:0;padding:4px 0;color:#1d2327}' +
			'p{margin:0 0 8px}label{cursor:default}input[type=text],select{padding:4px 6px;border:1px solid #8c8f94;border-radius:3px}' +
			'.wcj-question-icon{display:none!important}' +
			'</style></head><body>' + ( html || '' ) + '</body></html>';
	}

	function paintPreview( d ) {
		$( 'pname' ).textContent = d.product ? d.product.name : '';
		$( 'pprice' ).textContent = d.product ? d.product.price : '';
		var frame = $( 'frame' );
		var sum = $( 'sum' );
		var messages = {
			no_product: 'Add a published product to preview the product page.',
			module_off: 'Product Addons is off, so no extras show on this product.',
			unavailable: 'Booster’s product-page markup could not be shown here, so there is no picture.'
		};
		// No product card when there is nothing real to put in it.
		$( 'product' ).hidden = 'unavailable' === d.state || 'no_product' === d.state;
		if ( 'rendered' === d.state ) {
			frame.hidden = false;
			frame.srcdoc = frameDoc( d.html );
			var empty = ! /<input|<select/i.test( d.html || '' );
			sum.textContent = d.extra ? ( d.product.name + ' ' + d.product.price + ' + ' + d.extra.name + ( d.extra.price ? ' ' + d.extra.price : '' ) + ' when chosen' ) : ( empty ? 'No extras show on this product.' : '' );
		} else {
			frame.hidden = true;
			frame.srcdoc = frameDoc( '' );
			sum.textContent = ( messages[ d.state ] || '' ) + ( d.readback ? ' ' + d.readback : '' );
		}
		var notes = $( 'notes' );
		clear( notes );
		( d.notes || [] ).forEach( function ( n ) {
			notes.appendChild( el( 'li', '', n ) );
		} );
		var lim = $( 'limits' );
		clear( lim );
		( d.limits || [] ).forEach( function ( n ) {
			lim.appendChild( el( 'li', '', n ) );
		} );
	}

	function paintReceipt() {
		var r = S.fresh.receipt;
		var box = $( 'receipt' );
		var body = $( 'receipt-body' );
		clear( body );
		box.hidden = ! r || ! r.rows.length || r.slot !== S.slot;
		if ( ! r ) {
			return;
		}
		$( 'saved' ).textContent = ( r.status === 'undo_reversed' ? 'Undo reversed; changes still saved. ' : ( r.status === 'undo_incomplete' ? 'Undo incomplete; check the rows below. ' : ( r.status === 'restored' ? 'Undone. Original save: ' : 'Saved ' ) ) ) + r.time + ( 'readback_mismatch' === r.status ? ' · a value read back differently' : '' );
		var ordinary = r.status === 'applied' && r.rows.length && r.rows.every( function ( row ) { return row.readback_ok && row.still_ours; } );
		if ( ordinary ) { $( 'saved' ).textContent = 'Saved: ' + r.rows.length + ( r.rows.length === 1 ? ' change' : ' changes' ); } else { box.open = true; }
		var recoveryNote = {
			undo_reversed: 'Undo was reversed. Review the saved values below.',
			undo_incomplete: 'Undo is incomplete. Review the row statuses below before changing settings.',
			restored: 'Undo finished. Review the restored rows below.'
		};
		if ( recoveryNote[ r.status ] ) {
			body.appendChild( el( 'p', 'wcj-ij-note', recoveryNote[ r.status ] ) );
		}
		var ul = el( 'ul', 'wcj-ij-rows' );
		r.rows.forEach( function ( row ) {
			var li = el( 'li', row.still_ours ? 'is-ours' : ( row.restored ? 'is-restored' : 'is-changed' ) );
			li.appendChild( el( 'span', 'wcj-ij-rl', row.label ) );
			li.appendChild( el( 'del', '', row.before ) );
			li.appendChild( el( 'ins', '', row.after ) );
			li.appendChild( el( 'span', 'wcj-ij-opt', row.still_ours ? ( row.readback_ok ? 'saved' : 'read back differently' ) : ( row.restored ? 'undone' : 'changed since' ) ) );
			ul.appendChild( li );
		} );
		body.appendChild( ul );
	}

	function paintSamples() {
		var sel = $( 'sample' );
		clear( sel );
		S.fresh.samples.forEach( function ( p ) {
			var o = el( 'option', '', p.name + ' · ' + p.price + ( 'simple' !== p.type ? ' · ' + p.type : '' ) );
			o.value = String( p.id );
			sel.appendChild( o );
		} );
		if ( ! S.sample || ! S.fresh.samples.some( function ( p ) {
			return p.id === S.sample;
		} ) ) {
			S.sample = S.fresh.samples.length ? S.fresh.samples[ 0 ].id : 0;
		}
		sel.value = String( S.sample );
	}

	// ----- Preview ---------------------------------------------------------

	var timer = null;
	function schedule() {
		window.clearTimeout( timer );
		timer = window.setTimeout( refresh, 350 );
	}

	function refresh() {
		var seq = ++S.seq;
		var dirty = Object.keys( S.changes ).length > 0;
		// Without a draft, After equals Now: show what is saved and guide, rather than flag empty fields.
		var side = dirty ? S.side : 'now';
		var want = { changes: S.changes, side: side, sample: String( S.sample ) };
		var after = 'after' === side || ! dirty ? null : post( 'preview', { changes: S.changes, side: 'after', sample: String( S.sample ) } );
		if ( ! dirty ) {
			S.review = null;
			paintErrors( null );
			if ( S.fresh.append ) {
				status( 'Name the new extra and set its price to see it here.' );
			}
		}
		post( 'preview', want ).then( function ( r ) {
			if ( seq !== S.seq ) {
				return;
			}
			var d = r.data || {};
			if ( dirty ) {
				paintErrors( d.issues );
			}
			if ( ! r.success ) {
				S.review = null;
				status( d.error || '', d.issues ? '' : 'error' );
				paint();
				return;
			}
			if ( dirty ) {
				status( '' );
			}
			paintPreview( d );
			if ( d.review ) {
				S.review = d.review;
				paintRows();
				paint();
			}
		} );
		if ( after ) {
			after.then( function ( r ) {
				if ( seq !== S.seq ) {
					return;
				}
				var d = r.data || {};
				S.review = r.success ? d.review : null;
				if ( ! r.success ) {
					paintErrors( d.issues );
				}
				paintRows();
				paint();
			} );
		}
	}

	// ----- Load, apply, undo -----------------------------------------------

	function take( fresh ) {
		S.fresh = fresh;
		S.slot = fresh.slot;
		S.base = fresh.base;
		paintEnv();
		paintSlots();
		paintSamples();
		fill();
		paint();
		paintReceipt();
	}

	function button( cls, text, onClick ) {
		var b = el( 'button', cls, text );
		b.type = 'button';
		b.addEventListener( 'click', onClick );
		return b;
	}

	// A draft parked on another extra: never touched from here except by the owner.
	function parkedBanner( d ) {
		var box = $( 'parked' );
		clear( box );
		box.hidden = false;
		box.appendChild( el( 'p', '', 'You have an unsaved draft for another extra.' ) );
		var open = el( 'a', 'button', 'Open that draft' );
		open.href = cfg.page + '&slot=' + d.slot;
		box.appendChild( open );
		box.appendChild( button( 'button-link', 'Discard that draft', function () {
			drop( d.slot );
			box.hidden = true;
		} ) );
	}

	function dropOwnDraft() {
		S.held = false;
		drop( S.slot );
		S.changes = {};
		S.base = S.fresh.base;
		$( 'stale' ).hidden = true;
		fill();
		paint();
		schedule();
	}

	// This extra's own draft when it no longer matches what is saved. It keeps its original base,
	// so Apply refuses until the owner chooses to continue from what is saved now.
	function staleBanner( kind ) {
		var box = $( 'stale' );
		clear( box );
		box.hidden = false;
		if ( 'locked' === kind ) {
			box.appendChild( el( 'p', '', 'Your unsaved draft for this extra is kept, but the extra now uses settings this page cannot edit. Change it in the Product Addons settings, or discard the draft.' ) );
			box.appendChild( button( 'button-link', 'Discard my draft', dropOwnDraft ) );
			return;
		}
		box.appendChild( el( 'p', '', 'These settings changed after your draft was made. The preview shows your draft on top of what is saved now; check it before applying.' ) );
		box.appendChild( button( 'button', 'Continue from what is saved now', function () {
			S.base = S.fresh.base;
			box.hidden = true;
			persist();
			schedule();
		} ) );
		box.appendChild( button( 'button-link', 'Discard my draft', dropOwnDraft ) );
	}

	function restore() {
		var other = parkedElsewhere();
		if ( other ) {
			parkedBanner( other );
		}
		var d = stored( S.slot );
		if ( ! d ) {
			return;
		}
		if ( S.fresh.locked.length ) {
			S.held = true;
			staleBanner( 'locked' );
			return;
		}
		S.changes = d.changes || {};
		S.sample = d.sample || S.sample;
		paintSamples();
		if ( d.base && ! same( d.base, S.fresh.base ) ) {
			S.base = d.base;
			staleBanner( 'stale' );
		} else {
			status( 'Your draft was restored.' );
		}
		fill();
		paint();
	}

	function load() {
		status( 'Loading…' );
		return post( 'load', {} ).then( function ( r ) {
			if ( ! r.success ) {
				status( ( r.data && r.data.error ) || 'Could not load.', 'error' );
				return;
			}
			take( r.data );
			status( '' );
			restore();
			refresh();
		} );
	}

	function apply() {
		if ( S.busy || ! S.review ) {
			return;
		}
		if ( S.review.confirm && ! S.confirming ) {
			S.confirming = true;
			paint();
			paintReview();
			return;
		}
		S.busy = true;
		paint();
		status( 'Saving…' );
		post( 'apply', { changes: S.changes, base: S.base, confirm: S.confirming ? '1' : '0' } ).then( function ( r ) {
			S.busy = false;
			S.confirming = false;
			paintReview();
			var d = r.data || {};
			if ( d.fresh ) {
				var keep = S.changes;
				take( d.fresh );
				S.changes = keep;
			}
			var done = r.success && ( 'applied' === d.status || 'readback_mismatch' === d.status || 'unchanged' === d.status );
			if ( done ) {
				S.changes = {};
				drop( S.slot );
				$( 'stale' ).hidden = true;
				fill();
				paintReceipt();
				status( d.status === 'applied' && S.fresh.receipt && S.fresh.receipt.undo ? $( 'saved' ).textContent + '. Undo is available.' : d.message, 'readback_mismatch' === d.status ? 'error' : ( d.status === 'applied' && S.fresh.receipt && S.fresh.receipt.undo ? 'receipt' : 'ok' ) );
			} else {
				paintErrors( d.issues );
				var text = d.error || 'Nothing was changed.';
				if ( d.stale && d.stale.length ) {
					// The owner has now seen what changed; the draft continues from the saved values and
					// needs a fresh look at the preview and review before Apply is offered again.
					text += ' Changed: ' + d.stale.join( ', ' ) + '.';
					$( 'stale' ).hidden = true;
					persist();
				}
				if ( d.kept && d.kept.length ) {
					text += ' Still saved: ' + d.kept.join( ', ' ) + '.';
				}
				status( text, 'error' );
				fill();
			}
			paint();
			if ( done && ! $( 'savedbar' ).hidden ) {
				$( 'undo' ).focus();
			}
			refresh();
		} );
	}

	function undo() {
		if ( S.busy ) {
			return;
		}
		S.busy = true;
		status( 'Undoing…' );
		post( 'recover', {} ).then( function ( r ) {
			S.busy = false;
			var d = r.data || {};
			if ( d.fresh ) {
				take( d.fresh );
			}
			var text = d.message || d.error || '';
			if ( d.conflicts && d.conflicts.length ) {
				text += ' Settings not overwritten: ' + d.conflicts.join( ', ' ) + '.';
			}
			status( text, r.success && 'restored' === d.status ? 'ok' : 'error' );
			paint();
			refresh();
		} );
	}

	// ----- Product search for exclusions -----------------------------------

	var searchTimer = null;
	function search() {
		var term = $( 'search' ).value.trim();
		var list = $( 'results' );
		window.clearTimeout( searchTimer );
		if ( term.length < 2 ) {
			list.hidden = true;
			return;
		}
		searchTimer = window.setTimeout( function () {
			var url = cfg.url + '?action=woocommerce_json_search_products&security=' + encodeURIComponent( cfg.search ) + '&term=' + encodeURIComponent( term );
			fetch( url, { credentials: 'same-origin' } ).then( function ( r ) {
				return r.json();
			} ).then( function ( found ) {
				clear( list );
				var ids = value( 'exclude' ) || [];
				Object.keys( found || {} ).forEach( function ( id ) {
					if ( -1 !== ids.indexOf( id ) ) {
						return;
					}
					var li = el( 'li' );
					var b = el( 'button', 'button-link', String( found[ id ] ).replace( /<[^>]*>/g, '' ) );
					b.type = 'button';
					b.setAttribute( 'role', 'option' );
					b.addEventListener( 'click', function () {
						S.fresh.excluded[ id ] = b.textContent;
						set( 'exclude', ids.concat( [ id ] ) );
						$( 'search' ).value = '';
						list.hidden = true;
						$( 'search' ).focus();
					} );
					li.appendChild( b );
					list.appendChild( li );
				} );
				list.hidden = ! list.children.length;
			} ).catch( function () {
				list.hidden = true;
			} );
		}, 250 );
	}

	// ----- Events ----------------------------------------------------------

	function readPrice() {
		var raw = $( 'price' ).value.trim().replace( /%$/, '' );
		set( 'price', '' === raw ? '' : raw + ( 'percent' === S.unit ? '%' : '' ) );
	}

	$( 'live' ).addEventListener( 'change', function () {
		// Hidden extras are not decided for the owner: each needs a choice before Apply is offered.
		set( 'live', this.checked ? 'yes' : 'no' );
	} );
	TEXT.forEach( function ( k ) {
		$( k ).addEventListener( 'input', function () {
			set( k, this.value );
		} );
	} );
	$( 'required' ).addEventListener( 'change', function () {
		set( 'required', this.checked ? 'yes' : 'no' );
	} );
	$( 'price' ).addEventListener( 'input', readPrice );
	Array.prototype.forEach.call( document.querySelectorAll( '[data-unit]' ), function ( b ) {
		b.addEventListener( 'click', function () {
			S.unit = b.getAttribute( 'data-unit' );
			readPrice();
		} );
	} );
	Array.prototype.forEach.call( document.querySelectorAll( '[data-side]' ), function ( b ) {
		b.addEventListener( 'click', function () {
			S.side = b.getAttribute( 'data-side' );
			paint();
			refresh();
		} );
	} );
	$( 'sample' ).addEventListener( 'change', function () {
		S.sample = parseInt( this.value, 10 ) || 0;
		persist();
		refresh();
	} );
	$( 'slot' ).addEventListener( 'change', function () {
		window.location.href = cfg.page + '&slot=' + encodeURIComponent( this.value );
	} );
	$( 'search' ).addEventListener( 'input', search );
	$( 'search' ).addEventListener( 'keydown', function ( e ) {
		if ( 'ArrowDown' === e.key && ! $( 'results' ).hidden ) {
			e.preventDefault();
			$( 'results' ).querySelector( 'button' ).focus();
		} else if ( 'Escape' === e.key ) {
			$( 'results' ).hidden = true;
		}
	} );
	$( 'results' ).addEventListener( 'keydown', function ( e ) {
		var items = Array.prototype.slice.call( this.querySelectorAll( 'button' ) );
		var at = items.indexOf( document.activeElement );
		if ( 'ArrowDown' === e.key || 'ArrowUp' === e.key ) {
			e.preventDefault();
			var next = items[ at + ( 'ArrowDown' === e.key ? 1 : -1 ) ];
			( next || $( 'search' ) ).focus();
		} else if ( 'Escape' === e.key ) {
			this.hidden = true;
			$( 'search' ).focus();
		}
	} );
	$( 'count' ).addEventListener( 'click', function () {
		var box = $( 'rows-box' );
		box.hidden = ! box.hidden;
		this.setAttribute( 'aria-expanded', box.hidden ? 'false' : 'true' );
	} );
	$( 'primary' ).addEventListener( 'click', apply );
	$( 'discard' ).addEventListener( 'click', function () {
		S.changes = {};
		S.confirming = false;
		persist();
		$( 'parked' ).hidden = true;
		fill();
		paint();
		paintReview();
		paintErrors( null );
		refresh();
	} );
	$( 'undo' ).addEventListener( 'click', undo );
	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key && S.confirming ) {
			S.confirming = false;
			paint();
			paintReview();
			$( 'primary' ).focus();
		}
	} );

	// Informational admin notices are grouped under one disclosure, as on the invoice page; errors and warnings stay visible.
	window.addEventListener( 'load', function () {
		var root = document.getElementById( 'wcj-pe' );
		var box = $( 'info' );
		var info = Array.prototype.filter.call( root.querySelectorAll( ':scope > .notice.notice-info' ), function ( n ) {
			return ! n.matches( '.notice-error, .notice-warning, .error, .wcj-guided-compact-notice' );
		} );
		if ( ! box || ! info.length ) {
			return;
		}
		info.forEach( function ( n ) {
			box.appendChild( n );
		} );
		box.querySelector( 'summary' ).textContent = 'Store information (' + info.length + ')';
		box.hidden = false;
	} );

	load();
}() );

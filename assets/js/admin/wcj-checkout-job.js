/* Checkout question job. One question, one draft, real Classic field markup or a Blocks illustration, one apply, undo. */
( function () {
	'use strict';
	var cfg = window.wcjCheckoutJob;
	var root = document.getElementById( 'wcj-cq' );
	if ( ! cfg || ! root ) {
		return;
	}
	var SLOT = parseInt( cfg.slot, 10 ); // wp_localize_script passes top-level numbers as strings.
	// Draft identity: this site's job page URL (host and path, so subdirectory sites differ), user and question.
	function storeKey( slot ) {
		return 'wcjCheckoutJobDraft:' + cfg.page + ':' + cfg.user + ':' + slot;
	}
	var STORE = storeKey( SLOT );
	var LEGACY = 'wcjCheckoutJobDraft:' + cfg.user + ':' + SLOT; // Candidate09 key: read once, kept aside, then removed.
	var KEYS = [ 'enabled', 'label', 'type', 'options', 'required', 'section', 'placeholder', 'email_admin', 'email_customer', 'thanks' ];
	var SHARED = [ 'email_admin', 'email_customer', 'thanks' ];
	var TYPES = { text: 'Short answer', textarea: 'Long answer', select: 'Choice', checkbox: 'Checkbox' };
	var SECTIONS = { billing: 'Billing', shipping: 'Shipping', order: 'Order notes', account: 'Account' };
	var HEADINGS = { billing: 'Billing details', shipping: 'Ship to a different address?', order: 'Additional information', account: 'Account' };
	var BLOCKS_TYPES = [ 'text', 'select', 'checkbox', 'radio' ];
	var DOC_CSS = 'body{font:15px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:#1e1e1e;margin:20px 24px}' +
		'h3{font-size:18px;margin:0 0 14px}.form-row{margin:0 0 14px;padding:0}label{display:block;margin-bottom:4px}' +
		'.required{color:#b32d2e;text-decoration:none;border:0}.optional{color:#757575}' +
		'input.input-text,textarea,select{width:100%;box-sizing:border-box;padding:8px 10px;border:1px solid #8c8f94;border-radius:3px;font:inherit;background:#fff}' +
		'textarea{min-height:90px}label.checkbox{display:inline}input[type=checkbox]{margin-right:6px}.description{font-size:13px;color:#50575e;margin-top:4px}' +
		'.ghost{border:1px dashed #c3c4c7;border-radius:3px;padding:8px 10px;color:#a7aaad;margin:0 0 14px;font-size:13px}';

	var saved = null; // Server load(): slot, fields, env, receipt.
	var draft = {};
	var draftBase = {};
	var parked = {}; // Unsaved values whose setting is now kept as saved: { key: { value, base } }. Never submitted.
	var side = 'now';
	var busy = false;
	var timer = null;
	var pv = null; // Last preview: { now, after, limits }.
	var pvFor = null; // Draft (JSON) that preview was rendered for.
	var pvSeq = 0;
	var pvError = '';
	var conflict = null;

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
	function esc( s ) {
		return String( s ).replace( /[&<>"]/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ];
		} );
	}
	function say( msg, kind ) {
		var s = $( 'wcj-cq-status' );
		s.className = 'wcj-ij-status' + ( kind ? ' is-' + kind : '' );
		s.textContent = msg || '';
	}
	function plural( n, word ) {
		return n + ' ' + word + ( n === 1 ? '' : 's' );
	}
	function list( a ) {
		return a.length > 1 ? a.slice( 0, -1 ).join( ', ' ) + ' and ' + a[ a.length - 1 ] : a.join( '' );
	}
	function lines( t ) {
		return String( t || '' ).split( /\r\n|\r|\n/ ).map( function ( s ) {
			return s.trim();
		} ).filter( Boolean );
	}
	function post( op, data ) {
		var body = new FormData();
		body.append( 'action', 'wcj_checkout_job_' + op );
		body.append( 'nonce', cfg.nonces[ op ] );
		body.append( 'slot', String( SLOT ) );
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

	/* ---------- Draft state ---------- */
	function label( k ) {
		return k === 'module' ? 'Checkout Custom Fields' : cfg.fields[ k ].label;
	}
	function norm( k, v ) {
		if ( k === 'label' || k === 'placeholder' ) {
			return String( v ).trim();
		}
		return k === 'options' ? lines( v ).join( '\n' ) : v;
	}
	function current( k ) {
		return has( draft, k ) ? norm( k, draft[ k ] ) : saved.fields[ k ].value;
	}
	function keys() {
		return KEYS.filter( function ( k ) {
			return has( draft, k );
		} );
	}
	function values( which ) {
		var v = {};
		KEYS.forEach( function ( k ) {
			v[ k ] = which === 'now' ? saved.fields[ k ].value : current( k );
		} );
		return v;
	}
	function arch() {
		return saved.env.architecture;
	}
	// Who sees the answers is one setting for every Booster question, including ones switched off now.
	// The saved enabled count is quoted only above one, and never as a total or an after-Apply count.
	function scope() {
		var n = saved.env.enabled_count;
		return n > 1 ? 'all Booster questions (' + n + ( saved.env.module_enabled ? ' switched on)' : ' switched on in settings; module is off)' ) : 'all Booster questions';
	}
	function show( k, v ) {
		switch ( k ) {
			case 'enabled':
			case 'required':
			case 'email_admin':
			case 'email_customer':
			case 'thanks':
			case 'module':
				return v === 'yes' ? 'On' : 'Off';
			case 'type':
				return TYPES[ v ] || 'Other (' + v + ')';
			case 'section':
				return SECTIONS[ v ] || v;
			case 'options':
				var n = lines( v ).length;
				return n ? plural( n, 'choice' ) + ': ' + lines( v ).join( ', ' ) : 'None';
			default:
				return v ? '“' + v + '”' : 'None';
		}
	}
	function persist() {
		try {
			if ( keys().length || Object.keys( parked ).length ) {
				sessionStorage.setItem( STORE, JSON.stringify( { draft: draft, base: draftBase, parked: parked } ) );
			} else {
				sessionStorage.removeItem( STORE );
			}
		} catch ( e ) {}
	}
	function hasStored( slot ) {
		try {
			return !! sessionStorage.getItem( storeKey( slot ) );
		} catch ( e ) {
			return false;
		}
	}
	// Drafted values whose setting became "kept as saved" move aside instead of being dropped or submitted.
	function reconcile() {
		var moved = [];
		keys().forEach( function ( k ) {
			if ( saved.fields[ k ].locked ) {
				parked[ k ] = { value: draft[ k ], base: draftBase[ k ] || '' };
				delete draft[ k ];
				delete draftBase[ k ];
				moved.push( label( k ) );
			}
		} );
		if ( moved.length ) {
			persist();
		}
		return moved;
	}
	function renderParked() {
		var box = $( 'wcj-cq-parked' );
		var ks = Object.keys( parked );
		box.innerHTML = '';
		box.hidden = ! ks.length;
		ks.forEach( function ( k ) {
			var p = parked[ k ];
			var row = el( 'div', { class: 'wcj-cq-park' } );
			var locked = saved.fields[ k ].locked;
			row.appendChild( el( 'p', { class: 'wcj-cq-park-h' }, 'Your unsaved ' + label( k ).toLowerCase() + ' (not applied)' ) );
			var field = el( k === 'options' ? 'textarea' : 'input', { readonly: 'readonly', 'aria-label': 'Your unsaved ' + label( k ) } );
			if ( k === 'options' ) {
				field.setAttribute( 'rows', '3' );
			} else {
				field.setAttribute( 'type', 'text' );
			}
			field.value = p.value;
			row.appendChild( field );
			var acts = el( 'p', { class: 'wcj-cq-park-a' } );
			if ( locked ) {
				acts.appendChild( el( 'span', { class: 'wcj-ij-cl' }, 'Kept here because this setting is now managed in full settings.' ) );
			}
			var copy = el( 'button', { type: 'button', class: 'button-link' }, 'Copy' );
			copy.addEventListener( 'click', function () {
				field.focus();
				field.select();
				var done = function () {
					say( 'Copied.' );
				};
				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( p.value ).then( done, function () {
						say( 'Select the text and copy it.' );
					} );
				} else {
					try {
						document.execCommand( 'copy' );
						done();
					} catch ( e ) {
						say( 'Select the text and copy it.' );
					}
				}
			} );
			acts.appendChild( copy );
			if ( ! locked ) {
				var use = el( 'button', { type: 'button', class: 'button-link' }, 'Use it' );
				use.addEventListener( 'click', function () {
					delete parked[ k ];
					if ( norm( k, p.value ) !== saved.fields[ k ].value ) {
						draft[ k ] = p.value;
						// The original base: if the setting changed since, Apply shows the comparison first.
						draftBase[ k ] = p.base || saved.fields[ k ].base;
					}
					persist();
					say( '' );
					side = keys().length ? 'after' : 'now';
					fillInputs();
					refresh();
					renderParked();
					renderStage();
					schedule( 0 );
				} );
				acts.appendChild( use );
			}
			var drop = el( 'button', { type: 'button', class: 'button-link' }, 'Remove' );
			drop.addEventListener( 'click', function () {
				delete parked[ k ];
				persist();
				refresh();
				renderParked();
			} );
			acts.appendChild( drop );
			row.appendChild( acts );
			box.appendChild( row );
		} );
	}
	function setChange( k, v ) {
		if ( saved.fields[ k ].locked || busy ) {
			return;
		}
		say( '' ); // A new edit starts a new action; earlier save/undo messages no longer describe the draft.
		if ( norm( k, v ) === saved.fields[ k ].value ) {
			delete draft[ k ];
			delete draftBase[ k ];
		} else {
			if ( ! has( draftBase, k ) ) {
				draftBase[ k ] = saved.fields[ k ].base;
			}
			draft[ k ] = v;
		}
		clearError( k );
		if ( conflict ) {
			// Editing while the conflict panel is open closes it without rebasing; the next Apply re-reports.
			conflict = null;
			closeReview();
		}
		persist();
		side = keys().length ? 'after' : 'now';
		fillInputs();
		refresh();
		renderStage();
		schedule( 450 );
	}
	function clearError( k ) {
		var e = $( 'wcj-cq-err-' + k );
		if ( e && ! e.classList.contains( 'is-note' ) ) {
			e.textContent = '';
		}
		var i = $( 'wcj-cq-' + k );
		if ( i ) {
			i.removeAttribute( 'aria-invalid' );
		}
	}
	function showIssues( issues ) {
		Object.keys( issues || {} ).forEach( function ( k ) {
			var e = $( 'wcj-cq-err-' + k );
			if ( e ) {
				e.classList.remove( 'is-note' );
				e.textContent = issues[ k ];
			}
			var i = $( 'wcj-cq-' + k );
			if ( i ) {
				i.setAttribute( 'aria-invalid', 'true' );
			}
		} );
	}

	/* ---------- Controls ---------- */
	function note( k, text ) {
		var e = $( 'wcj-cq-err-' + k );
		if ( e && ( e.classList.contains( 'is-note' ) || ! e.textContent ) ) {
			e.classList.toggle( 'is-note', !! text );
			e.textContent = text || '';
		}
	}
	function fillInputs() {
		var f = saved.fields;
		var active = document.activeElement;
		[ 'enabled', 'required' ].concat( SHARED ).forEach( function ( k ) {
			var c = $( 'wcj-cq-' + k );
			c.checked = current( k ) === 'yes';
			c.disabled = busy || !! f[ k ].locked;
		} );
		[ 'label', 'placeholder', 'options' ].forEach( function ( k ) {
			var i = $( 'wcj-cq-' + k );
			if ( i !== active ) {
				i.value = has( draft, k ) ? draft[ k ] : f[ k ].value;
			}
			i.disabled = busy || !! f[ k ].locked;
			note( k, f[ k ].locked );
		} );
		var type = current( 'type' );
		var blocks = arch() === 'blocks';
		root.querySelectorAll( '[data-set]' ).forEach( function ( b ) {
			var kv = b.getAttribute( 'data-set' ).split( ':' );
			b.setAttribute( 'aria-pressed', current( kv[ 0 ] ) === kv[ 1 ] ? 'true' : 'false' );
			b.disabled = busy || !! f[ kv[ 0 ] ].locked;
			if ( kv[ 0 ] === 'type' ) {
				b.classList.toggle( 'is-limited', arch() !== 'classic' && BLOCKS_TYPES.indexOf( kv[ 1 ] ) < 0 );
			}
		} );
		var typeNote = f.type.locked ? 'Saved type: ' + show( 'type', f.type.value ) + '. ' + f.type.locked : '';
		if ( ! typeNote && BLOCKS_TYPES.indexOf( type ) < 0 ) {
			if ( blocks ) {
				typeNote = 'Long answers are not shown on your Blocks checkout. Pick another type, or use Classic checkout.';
			} else if ( arch() === 'unknown' ) {
				typeNote = 'Long answers appear only on Classic checkout.';
			}
		}
		note( 'type', typeNote );
		var sectionNote = f.section.locked ? 'Saved place: ' + show( 'section', f.section.value ) + '. ' + f.section.locked : ( blocks ? 'On Blocks checkout the question always appears with the order information.' : '' );
		note( 'section', sectionNote );
		note( 'placeholder', f.placeholder.locked || ( blocks ? 'Blocks checkout does not show hints.' : '' ) );
		$( 'wcj-cq-options-box' ).hidden = type !== 'select' && type !== 'radio';
		$( 'wcj-cq-shared-note' ).textContent = '· for ' + scope();
		var nativeNote = $( 'wcj-cq-native-note' );
		nativeNote.textContent = blocks ? 'These switches control only Booster’s copy. WooCommerce can also show Blocks answers in its own emails and order pages.' : ( arch() === 'unknown' ? 'If your checkout uses Blocks, WooCommerce may also show answers in its own emails and order pages. These switches control only Booster’s copy.' : '' ); // Sits under the switches, inside their fieldset.
		nativeNote.hidden = ! nativeNote.textContent;
		// Drafts are kept per question, so switching never loses work.
		$( 'wcj-cq-slot' ).disabled = busy;
	}
	// One readable row per drafted change: saved now → yours. Shared by the change list and the review.
	function changeRow( k, undo ) {
		var li = el( 'li' );
		var rl = el( 'span', { class: 'wcj-ij-rl' }, label( k ) );
		if ( SHARED.indexOf( k ) >= 0 ) {
			rl.appendChild( el( 'span', { class: 'wcj-cq-scope' }, 'for ' + scope() ) );
		}
		li.appendChild( rl );
		li.appendChild( el( 'span', {}, show( k, saved.fields[ k ].value ) ) );
		li.appendChild( el( 'span', { class: 'wcj-ij-arrow', 'aria-label': 'to' }, '→' ) );
		li.appendChild( el( 'span', {}, show( k, current( k ) ) ) );
		if ( draftBase[ k ] && draftBase[ k ] !== saved.fields[ k ].base ) {
			li.appendChild( el( 'span', { class: 'wcj-ij-flag' }, 'changed elsewhere' ) );
		}
		if ( undo ) {
			var u = el( 'button', { type: 'button', class: 'button-link', 'aria-label': 'Undo ' + label( k ) }, 'Undo' );
			u.addEventListener( 'click', function () {
				setChange( k, saved.fields[ k ].value );
				if ( ! keys().length ) {
					$( 'wcj-cq-rows-box' ).hidden = true;
					$( 'wcj-cq-count' ).setAttribute( 'aria-expanded', 'false' );
				}
			} );
			li.appendChild( u );
		}
		return li;
	}
	function refresh() {
		var ks = keys();
		var count = $( 'wcj-cq-count' );
		count.textContent = ks.length ? plural( ks.length, 'change' ) : 'No changes';
		count.disabled = ! ks.length;
		var primary = $( 'wcj-cq-primary' );
		primary.disabled = busy || ! ks.length || !! conflict;
		primary.textContent = facts().review ? 'Review and apply' : 'Apply';
		$( 'wcj-cq-discard' ).hidden = ! ks.length && ! Object.keys( parked ).length;
		var rc = saved.receipt;
		var showSaved = ! ks.length && rc && rc.undo && rc.slot === SLOT;
		$( 'wcj-ij-savedbar' ).hidden = ! showSaved;
		$( 'wcj-ij-draftbar' ).hidden = !! showSaved;
		if ( showSaved ) {
			$( 'wcj-cq-saved' ).textContent = (rc.rows.every( function ( row ) { return row.readback_ok && row.still_ours; } ) ? 'Saved: ' + plural( rc.rows.length, 'change' ) : 'Last change: check affected rows');
			$( 'wcj-cq-undo' ).disabled = busy;
		}
		var ul = $( 'wcj-cq-rows' );
		ul.innerHTML = '';
		ks.forEach( function ( k ) {
			ul.appendChild( changeRow( k, true ) );
		} );
		if ( ! ks.length ) {
			$( 'wcj-cq-rows-box' ).hidden = true;
		}
		root.querySelectorAll( '[data-side]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-pressed', b.getAttribute( 'data-side' ) === side ? 'true' : 'false' );
		} );
		var fresh = $( 'wcj-cq-fresh' );
		if ( pvError ) {
			fresh.textContent = pvError;
			fresh.className = 'wcj-ij-fresh is-stale';
		} else if ( side === 'after' && arch() !== 'blocks' && pvFor !== JSON.stringify( draft ) ) {
			fresh.textContent = 'Updating…';
			fresh.className = 'wcj-ij-fresh is-stale';
		} else {
			fresh.textContent = side === 'now' ? 'As saved' : ( ks.length ? 'With your changes, not saved' : 'Same as saved' );
			fresh.className = 'wcj-ij-fresh';
		}
	}

	/* ---------- Preview ---------- */
	function surface( tier, title ) {
		var s = el( 'div', { class: 'wcj-cq-surface' } );
		var h = el( 'div', { class: 'wcj-cq-shead' } );
		h.appendChild( el( 'span', { class: 'wcj-cq-tier ' + ( tier === 'real' ? 'is-real' : 'is-ill' ) }, tier === 'real' ? 'Real WooCommerce field' : 'Illustration' ) );
		h.appendChild( el( 'span', {}, title ) );
		s.appendChild( h );
		return s;
	}
	function changed( k ) {
		return side === 'after' && has( draft, k );
	}
	function illustration( v, blocks ) {
		var box = el( 'div', { class: 'wcj-cq-ill' } );
		box.appendChild( el( 'h4', {}, blocks ? 'Additional order information' : ( HEADINGS[ v.section ] || '' ) ) );
		box.appendChild( el( 'div', { class: 'ghost' }, 'Other checkout fields' ) );
		if ( v.enabled !== 'yes' ) {
			box.appendChild( el( 'p', { class: 'wcj-cq-empty' }, 'Customers don’t see this question.' ) );
			return box;
		}
		if ( blocks && BLOCKS_TYPES.indexOf( v.type ) < 0 ) {
			box.appendChild( el( 'div', { class: 'off' }, 'This answer type is not shown on Blocks checkout.' ) );
			return box;
		}
		if ( blocks && saved.env.conditioned && ! saved.env.conditional_ok ) {
			box.appendChild( el( 'div', { class: 'off' }, 'Not shown on Blocks: this question has cart conditions, which need WooCommerce 9.9 or later.' ) );
			return box;
		}
		var isNew = [ 'enabled', 'label', 'type', 'options', 'required', 'section', 'placeholder' ].some( changed );
		var text = v.label + ( v.required === 'yes' ? ' *' : ' (optional)' );
		if ( v.type === 'checkbox' ) {
			var cb = el( 'div', { class: 'cbx' + ( isNew ? ' is-new' : '' ) } );
			cb.appendChild( el( 'i' ) );
			cb.appendChild( el( 'span', {}, text ) );
			box.appendChild( cb );
		} else if ( v.type === 'select' || v.type === 'radio' ) {
			box.appendChild( el( 'div', { class: 'box' + ( isNew ? ' is-new' : '' ) }, text + '  ▾  ' + ( lines( v.options ).join( ' · ' ) || 'No choices yet' ) ) );
		} else {
			var hint = ! blocks && v.placeholder ? ' — ' + v.placeholder : '';
			box.appendChild( el( 'div', { class: 'box' + ( isNew ? ' is-new' : '' ) }, text + hint ) );
		}
		if ( saved.env.conditioned ) {
			box.appendChild( el( 'p', { class: 'wcj-ij-cl' }, 'Shown only for some carts, as saved.' ) );
		}
		return box;
	}
	function frame( html, section ) {
		var f = el( 'iframe', { sandbox: '', title: 'Classic checkout field preview' } );
		f.srcdoc = '<!doctype html><html><head><meta charset="utf-8"><style>' + DOC_CSS + '</style></head><body><h3>' + esc( HEADINGS[ section ] || '' ) + '</h3><div class="ghost">Other checkout fields</div>' + html + '<div class="ghost">…</div></body></html>';
		return f;
	}
	function renderStage() {
		var stage = $( 'wcj-cq-stage' );
		stage.innerHTML = '';
		var v = values( side );
		var a = arch();
		var when = side === 'now' ? 'now' : 'after your changes';
		if ( a !== 'blocks' ) {
			var r = pv ? pv[ side ] : null;
			if ( saved.env.render_ok && ( ! pv || ( r && r.html !== null ) ) ) {
				var s = surface( 'real', 'Classic checkout, ' + when + ' · your theme styling is not shown' );
				if ( ! r ) {
					s.appendChild( el( 'p', { class: 'wcj-cq-empty' }, 'Rendering the field…' ) );
				} else if ( r.html === '' ) {
					s.appendChild( el( 'p', { class: 'wcj-cq-empty' }, r.note || 'Nothing to show.' ) );
				} else {
					s.appendChild( frame( r.html, v.section ) );
				}
				s.classList.toggle( 'is-stale', side === 'after' && pvFor !== JSON.stringify( draft ) );
				stage.appendChild( s );
			} else {
				var si = surface( 'ill', 'Classic checkout, ' + when + ' · the real field could not be rendered here' );
				si.appendChild( illustration( v, false ) );
				stage.appendChild( si );
			}
		}
		if ( a !== 'classic' ) {
			var sb = surface( 'ill', 'Blocks checkout, ' + when + ' · a sketch, not your checkout page' );
			sb.appendChild( illustration( v, true ) );
			stage.appendChild( sb );
		}
	}
	function schedule( delay ) {
		clearTimeout( timer );
		if ( arch() === 'blocks' || ! saved.env.render_ok ) {
			pvFor = JSON.stringify( draft );
			refresh();
			return;
		}
		timer = setTimeout( fetchPreview, delay || 0 );
	}
	function fetchPreview() {
		var seq = ++pvSeq;
		var sent = JSON.stringify( draft );
		post( 'preview', { changes: sent } ).then( function ( r ) {
			if ( seq !== pvSeq ) {
				return;
			}
			var d = r.data || {};
			if ( r.success ) {
				pv = d;
				pvFor = sent;
				pvError = '';
				var ul = $( 'wcj-cq-limits' );
				ul.innerHTML = '';
				( d.limits || [] ).forEach( function ( t ) {
					ul.appendChild( el( 'li', {}, t ) );
				} );
			} else {
				pvError = d.issues ? 'Fix the highlighted field to update the preview' : ( d.error || 'Preview unavailable' );
				if ( d.issues ) {
					showIssues( d.issues );
				}
			}
			refresh();
			renderStage();
		} ).catch( function ( e ) {
			if ( seq === pvSeq ) {
				pvError = e.message;
				refresh();
			}
		} );
	}

	/* ---------- Consequences and review ---------- */
	// Levels: 0 = information only, 1 = shown in review, 2 = needs the explicit confirmation (mirrors the server rule).
	function facts() {
		var out = { items: [], tech: [], review: false, confirm: false };
		if ( ! saved ) {
			return out;
		}
		var f = saved.fields;
		var env = saved.env;
		var on = current( 'enabled' ) === 'yes';
		var wasOn = f.enabled.value === 'yes';
		var a = arch();
		function add( level, text ) {
			out.items.push( { level: level, text: text } );
			out.review = out.review || level > 0;
			out.confirm = out.confirm || level > 1;
		}
		if ( has( draft, 'enabled' ) ) {
			if ( on ) {
				add( 2, 'Customers will see this question on new checkouts.' );
			} else {
				add( 1, 'Customers stop seeing this question. Answers already given stay with their orders.' );
			}
		}
		if ( on && ! env.module_enabled ) {
			add( 2, 'Checkout Custom Fields will be turned on.' );
			out.tech.push( 'wcj_checkout_custom_fields_enabled → yes' );
		}
		if ( on && has( draft, 'required' ) ) {
			if ( current( 'required' ) === 'yes' ) {
				add( 2, 'Customers can’t place an order without answering.' );
			} else {
				add( 1, 'Answering becomes optional.' );
			}
		}
		// Turning the question off or moving its answers already says answers given stay with their orders.
		var keptSaid = has( draft, 'enabled' ) && ! on;
		if ( has( draft, 'section' ) && ( wasOn || f.label.value !== '' ) ) {
			keptSaid = true;
			add( 2, 'New answers will be saved under a new name. Anything that shows this answer by its old name, such as a shortcode in an invoice or email template, won’t show new answers. Answers already given stay with their orders.' );
			if ( a === 'blocks' ) {
				add( 1, 'On Blocks checkout the question stays where it is; only the place answers are saved changes.' );
			}
			out.tech.push( 'Answer name (shortcode field_id): ' + env.answer_key + ' → ' + current( 'section' ) + '_wcj_checkout_field_' + SLOT );
		}
		// One history note naming only what changed. Wording alone is information; type or choices need review.
		var kept = [ [ 'label', 'question wording' ], [ 'type', 'answer type' ], [ 'options', 'choices' ] ].filter( function ( p ) {
			return has( draft, p[ 0 ] );
		} );
		if ( wasOn && ! keptSaid && kept.length ) {
			add( kept.length === 1 && kept[ 0 ][ 0 ] === 'label' ? 0 : 1, 'Orders already placed keep the ' + list( kept.map( function ( p ) {
				return p[ 1 ];
			} ) ) + ' they had.' );
		}
		if ( on && has( draft, 'type' ) && a !== 'classic' && BLOCKS_TYPES.indexOf( current( 'type' ) ) < 0 ) {
			add( 1, 'Long answers are not shown on Blocks checkout.' );
		}
		if ( on && has( draft, 'placeholder' ) && a === 'blocks' ) {
			add( 1, 'Blocks checkout does not show hints.' );
		}
		if ( SHARED.some( function ( k ) {
			return has( draft, k );
		} ) ) {
			// Always reviewed: the scope is tagged on the changed row, and the preview cannot show this change.
			out.review = true;
			add( 1, 'The preview shows only the checkout field, not emails or order pages.' );
			if ( a === 'blocks' ) {
				add( 1, 'The “Who sees the answers” switches control only Booster’s copy. WooCommerce can also show Blocks answers in its own emails and order pages.' );
			} else if ( a === 'unknown' ) {
				add( 1, 'If your checkout uses Blocks, WooCommerce may also show answers in its own emails and order pages. The “Who sees the answers” switches control only Booster’s copy.' );
			}
		}
		if ( on && has( draft, 'enabled' ) && ( ! f.type.stored || ! f.section.stored ) ) {
			add( 1, 'Answer type and place are saved too, because they were never saved before.' );
		}
		var words = env.checkbox_words || [];
		if ( on && current( 'type' ) === 'checkbox' && words.length ) {
			// Mirrors the server: absent checkbox wording is written with the native defaults for a checkbox that is on.
			add( 1, 'Checkbox answer wording “' + words.join( '” / “' ) + '” is saved too, because none was saved yet. Emails and order pages show it instead of 1 or 0.' );
			out.tech.push( cfg.fields.checkbox_yes.option + ', ' + cfg.fields.checkbox_no.option + ': written only if absent' );
		}
		return out;
	}
	function closeReview() {
		$( 'wcj-cq-review' ).hidden = true;
		$( 'wcj-ij-draftbar' ).classList.remove( 'is-review' );
	}
	function review() {
		var box = $( 'wcj-cq-review' );
		var ks = keys();
		var fx = facts();
		box.innerHTML = '';
		say( '' ); // Earlier save/undo messages do not describe this review.
		box.appendChild( el( 'h3', {}, 'Review ' + plural( ks.length, 'change' ) ) );
		// Exactly the drafted changes, saved now → yours, before what they mean.
		var rows = el( 'ul', { class: 'wcj-ij-rows wcj-cq-review-rows' } );
		ks.forEach( function ( k ) {
			rows.appendChild( changeRow( k, false ) );
		} );
		box.appendChild( rows );
		var ul = el( 'ul', { class: 'wcj-ij-cqs' } );
		fx.items.forEach( function ( it ) {
			ul.appendChild( el( 'li', it.level > 1 ? { class: 'wcj-ij-warn' } : {}, it.text ) );
		} );
		if ( fx.items.length ) {
			box.appendChild( ul );
		}
		var confirm = null;
		if ( fx.confirm ) {
			var lab = el( 'label', { class: 'wcj-ij-ck' } );
			confirm = el( 'input', { type: 'checkbox' } );
			lab.appendChild( confirm );
			lab.appendChild( document.createTextNode( ' Yes, make these checkout changes' ) );
			box.appendChild( lab );
		}
		var go = el( 'button', { type: 'button', class: 'button button-primary' }, 'Apply ' + plural( ks.length, 'change' ) );
		go.disabled = !! confirm;
		if ( confirm ) {
			confirm.addEventListener( 'change', function () {
				go.disabled = ! confirm.checked;
			} );
		}
		go.addEventListener( 'click', function () {
			apply( !! confirm && confirm.checked );
		} );
		var back = el( 'button', { type: 'button', class: 'button-link' }, 'Back to editing' );
		back.addEventListener( 'click', function () {
			closeReview();
			$( 'wcj-cq-primary' ).focus();
		} );
		var acts = el( 'p', { class: 'wcj-ij-ra' } );
		acts.appendChild( go );
		acts.appendChild( back );
		box.appendChild( acts );
		var det = el( 'details', { class: 'wcj-ij-tech' } );
		det.appendChild( el( 'summary', {}, 'Technical details' ) );
		var tl = el( 'ul' );
		ks.forEach( function ( k ) {
			tl.appendChild( el( 'li', {}, cfg.fields[ k ].option ) );
		} );
		fx.tech.forEach( function ( t ) {
			tl.appendChild( el( 'li', {}, t ) );
		} );
		det.appendChild( tl );
		det.appendChild( el( 'p', {}, 'Settings are written one at a time, only if nobody changed them since you loaded this page, and each is read back. Orders, collected answers and customer profiles are not changed. No email is sent.' ) );
		box.appendChild( det );
		$( 'wcj-cq-rows-box' ).hidden = true;
		$( 'wcj-ij-draftbar' ).classList.add( 'is-review' );
		box.hidden = false;
		side = 'after';
		refresh();
		renderStage();
		( confirm || go ).focus();
	}

	/* ---------- Apply, undo, receipt ---------- */
	var movedNote = '';
	function parkedText( moved ) {
		return moved.length ? 'Your unsaved ' + moved.join( ', ' ).toLowerCase() + ' is kept aside at the top of the form, not applied: ' + ( moved.length === 1 ? 'that setting is' : 'those settings are' ) + ' now managed in full settings.' : '';
	}
	function adopt( fresh ) {
		if ( fresh ) {
			saved = fresh;
			movedNote = parkedText( reconcile() );
		}
	}
	function finish() {
		busy = false;
		envNotes();
		fillInputs();
		refresh();
		showReceipt();
		renderParked();
		renderStage();
		schedule( 0 );
		if ( movedNote ) {
			var s = $( 'wcj-cq-status' );
			s.textContent = ( s.textContent ? s.textContent + ' ' : '' ) + movedNote;
			movedNote = '';
		}
	}
	function apply( confirmed ) {
		if ( busy ) {
			return;
		}
		busy = true;
		refresh();
		say( 'Saving…' );
		// Information notes are repeated after saving only when no review showed them first.
		var fx = facts();
		var info = fx.review ? [] : fx.items.filter( function ( it ) {
			return it.level === 0;
		} ).map( function ( it ) {
			return it.text;
		} );
		var base = {};
		keys().forEach( function ( k ) {
			base[ k ] = draftBase[ k ] || saved.fields[ k ].base;
		} );
		base.module = saved.env.module_base;
		post( 'apply', { changes: JSON.stringify( draft ), base: JSON.stringify( base ), confirm: confirmed ? '1' : '0' } ).then( function ( r ) {
			var d = r.data || {};
			adopt( d.fresh );
			if ( r.success && ( d.status === 'applied' || d.status === 'unchanged' ) ) {
				draft = {};
				draftBase = {};
				side = 'now';
				persist();
				closeReview();
				var ordinary = d.status === 'applied' && saved.receipt && saved.receipt.undo;
				say( ordinary ? ( info.length ? info.join( ' ' ) : 'Saved: ' + plural( saved.receipt.rows.length, 'change' ) + '. Undo is available.' ) : d.message + ( info.length ? ' ' + info.join( ' ' ) : '' ), ordinary && ! info.length ? 'receipt' : 'ok' );
			} else if ( r.success ) {
				draft = {};
				draftBase = {};
				persist();
				closeReview();
				say( d.message, 'error' );
			} else if ( d.status === 'stale' ) {
				say( d.error, 'error' );
				showStale( d.stale );
			} else {
				closeReview();
				if ( d.issues ) {
					showIssues( d.issues );
				}
				say( d.error || 'Not saved. Reload to see what is saved.', 'error' );
			}
		} ).catch( function ( e ) {
			say( e.message, 'error' );
		} ).then( finish );
	}
	function recover( options ) {
		if ( busy ) {
			return;
		}
		busy = true;
		refresh();
		say( 'Undoing…' );
		post( 'recover', { options: JSON.stringify( options ) } ).then( function ( r ) {
			var d = r.data || {};
			adopt( d.fresh );
			var skipped = d.ignored ? ' ' + d.ignored + ( d.ignored === 1 ? ' unreadable undo entry was' : ' unreadable undo entries were' ) + ' ignored; nothing was written for them.' : '';
			if ( ! r.success ) {
				say( d.error || 'Undo failed. Nothing is confirmed.', 'error' );
			} else if ( d.status === 'restored' ) {
				// The receipt may belong to another question; the page keeps showing this one.
				var where = d.slot && d.slot !== SLOT ? ' (question ' + d.slot + ')' : '';
				say( 'Undone' + where + '. Orders and collected answers were not touched.' + skipped, skipped ? 'error' : 'ok' );
			} else {
				say( 'Partly undone. Kept because they changed since your save: ' + d.conflicts.map( function ( k ) {
					return k === 'module' ? 'Checkout Custom Fields (another question is still on)' : ( cfg.fields[ k ] ? cfg.fields[ k ].label : k );
				} ).join( ', ' ) + '.' + skipped, 'error' );
			}
		} ).catch( function ( e ) {
			say( e.message, 'error' );
		} ).then( finish );
	}
	// Conflict after a stale Apply (nothing was written). Bases are rebased only by "Keep my changes",
	// after the owner has seen what is saved now for every affected change.
	function showStale( stale ) {
		Object.keys( draft ).forEach( function ( k ) {
			if ( norm( k, draft[ k ] ) === saved.fields[ k ].value ) {
				delete draft[ k ];
				delete draftBase[ k ];
			}
		} );
		persist();
		conflict = { keys: Object.keys( stale ).filter( function ( k ) {
			return k !== 'module';
		} ), module: stale.module };
		renderConflict();
		$( 'wcj-cq-review' ).focus();
	}
	function renderConflict() {
		var box = $( 'wcj-cq-review' );
		box.innerHTML = '';
		var pending = conflict.keys.filter( function ( k ) {
			return has( draft, k );
		} );
		if ( ! pending.length && conflict.module === undefined ) {
			conflict = null;
			closeReview();
			refresh();
			say( 'Using the saved values. Nothing was saved; apply your other changes when ready.' );
			return;
		}
		box.appendChild( el( 'h3', {}, 'Saved elsewhere since you started' ) );
		var ul = el( 'ul', { class: 'wcj-ij-rows' } );
		if ( conflict.module !== undefined ) {
			var mli = el( 'li' );
			mli.appendChild( el( 'span', { class: 'wcj-ij-rl' }, 'Checkout Custom Fields' ) );
			mli.appendChild( el( 'span', {}, 'saved now: ' + show( 'module', conflict.module ) ) );
			ul.appendChild( mli );
		}
		pending.forEach( function ( k ) {
			var li = el( 'li' );
			li.appendChild( el( 'span', { class: 'wcj-ij-rl' }, label( k ) ) );
			li.appendChild( el( 'span', { class: 'wcj-ij-cl' }, 'saved now' ) );
			li.appendChild( el( 'span', {}, show( k, saved.fields[ k ].value ) ) );
			li.appendChild( el( 'span', { class: 'wcj-ij-arrow', 'aria-label': 'yours' }, '→' ) );
			li.appendChild( el( 'span', { class: 'wcj-ij-cl' }, 'yours' ) );
			li.appendChild( el( 'span', {}, show( k, current( k ) ) ) );
			var use = el( 'button', { type: 'button', class: 'button-link', 'aria-label': 'Use saved ' + label( k ) }, 'Use saved' );
			use.addEventListener( 'click', function () {
				delete draft[ k ];
				delete draftBase[ k ];
				persist();
				fillInputs();
				refresh();
				renderStage();
				schedule( 0 );
				renderConflict();
			} );
			li.appendChild( use );
			ul.appendChild( li );
		} );
		box.appendChild( ul );
		box.appendChild( el( 'p', {}, 'Nothing was saved.' ) );
		var keep = el( 'button', { type: 'button', class: 'button' }, pending.length ? 'Keep my changes' : 'Continue' );
		keep.addEventListener( 'click', function () {
			// Deliberate rebase onto what the owner just compared against.
			conflict.keys.forEach( function ( k ) {
				if ( has( draftBase, k ) ) {
					draftBase[ k ] = saved.fields[ k ].base;
				}
			} );
			conflict = null;
			persist();
			closeReview();
			refresh();
			say( 'Your changes will replace what is saved now. Apply when ready.' );
			$( 'wcj-cq-primary' ).focus();
		} );
		box.appendChild( keep );
		$( 'wcj-ij-draftbar' ).classList.remove( 'is-review' );
		box.hidden = false;
		refresh();
	}
	function undoAll() {
		var rc = saved.receipt;
		var opts = rc ? rc.rows.filter( function ( row ) {
			return row.still_ours;
		} ).map( function ( row ) {
			return row.option;
		} ) : [];
		if ( opts.length ) {
			recover( opts );
		}
	}
	function showReceipt() {
		var wrap = $( 'wcj-cq-receipt' );
		var body = $( 'wcj-cq-receipt-body' );
		var rc = saved.receipt;
		body.innerHTML = '';
		if ( ! rc || ! rc.rows.length ) {
			wrap.hidden = true;
			return;
		}
		if ( rc.rows.some( function ( row ) { return ! row.readback_ok || ! row.still_ours; } ) ) { wrap.open = true; }
		var ul = el( 'ul', { class: 'wcj-ij-rows' } );
		rc.rows.forEach( function ( row ) {
			var li = el( 'li' );
			var cb = el( 'input', { type: 'checkbox', value: row.option, 'aria-label': 'Undo ' + row.label } );
			cb.disabled = ! row.still_ours;
			li.appendChild( cb );
			li.appendChild( el( 'span', { class: 'wcj-ij-rl' }, row.label ) );
			li.appendChild( el( 'span', {}, show( row.field, row.before ) ) );
			li.appendChild( el( 'span', { class: 'wcj-ij-arrow' }, '→' ) );
			li.appendChild( el( 'span', {}, show( row.field, row.after ) ) );
			li.appendChild( el( 'span', { class: 'wcj-ij-state' }, ! row.readback_ok ? 'Saved value differs' : ( row.still_ours ? 'Saved' : ( row.restored ? 'Undone' : 'Changed later, kept' ) ) ) );
			ul.appendChild( li );
		} );
		body.appendChild( el( 'p', { class: 'description' }, 'Saved ' + rc.time + ( rc.slot && rc.slot !== SLOT ? ' for question ' + rc.slot : '' ) + '. Undo only puts back settings nobody changed since.' ) );
		body.appendChild( ul );
		var btn = el( 'button', { type: 'button', class: 'button' }, 'Undo selected' );
		btn.disabled = busy;
		btn.addEventListener( 'click', function () {
			var opts = [];
			body.querySelectorAll( 'input[type=checkbox]:checked' ).forEach( function ( c ) {
				opts.push( c.value );
			} );
			if ( ! opts.length ) {
				say( 'Select a setting to undo.', 'error' );
				return;
			}
			recover( opts );
		} );
		body.appendChild( btn );
		wrap.hidden = false;
	}
	function chip( text, warn ) {
		return el( 'span', { class: 'wcj-ij-note wcj-cq-note' + ( warn ? ' is-warn' : '' ) }, text );
	}
	function envNotes() {
		var env = saved.env;
		var box = $( 'wcj-cq-env' );
		box.innerHTML = '';
		var a = arch();
		box.appendChild( chip( a === 'classic' ? 'Your checkout: Classic' : ( a === 'blocks' ? 'Your checkout: Blocks' : 'We couldn’t tell which checkout you use, so both are shown' ), a === 'unknown' ) );
		if ( ! env.module_enabled ) {
			box.appendChild( chip( 'Checkout Custom Fields is off. Customers see no Booster questions.', true ) );
		}
		if ( a !== 'classic' && env.conditioned && ! env.conditional_ok ) {
			box.appendChild( chip( 'Blocks hides this question: its cart conditions need WooCommerce 9.9 or later.', true ) );
		}
		if ( a !== 'blocks' && ! env.render_ok ) {
			box.appendChild( chip( 'The Classic field can’t be rendered here, so a sketch is shown.', true ) );
		}
		var kept = $( 'wcj-cq-kept' );
		kept.innerHTML = '';
		( env.kept || [] ).forEach( function ( t ) {
			kept.appendChild( el( 'li', {}, t ) );
		} );
		$( 'wcj-cq-kept-box' ).hidden = ! ( env.kept || [] ).length;
		var wrap = $( 'wcj-cq-slot-wrap' );
		var pick = $( 'wcj-cq-slot' );
		wrap.hidden = env.total < 2;
		pick.innerHTML = '';
		var free = false;
		env.slots.forEach( function ( s ) {
			free = free || s.free;
			var o = el( 'option', { value: String( s.slot ) }, 'Question ' + s.slot + ': ' + ( s.free ? 'unused' : ( s.label || '(no question)' ) + ( s.on ? '' : ' · off' ) ) + ( s.slot !== SLOT && hasStored( s.slot ) ? ' · unsaved changes' : '' ) );
			o.selected = s.slot === SLOT;
			pick.appendChild( o );
		} );
		if ( env.total > 1 && ! free ) {
			var full = el( 'option', { value: '', disabled: 'disabled' }, 'All ' + env.total + ' questions are in use. Add more in all checkout field settings.' );
			pick.appendChild( full );
		}
	}

	/* ---------- Events ---------- */
	function bind() {
		[ 'enabled', 'required' ].concat( SHARED ).forEach( function ( k ) {
			$( 'wcj-cq-' + k ).addEventListener( 'change', function ( e ) {
				setChange( k, e.target.checked ? 'yes' : 'no' );
			} );
		} );
		[ 'label', 'placeholder', 'options' ].forEach( function ( k ) {
			$( 'wcj-cq-' + k ).addEventListener( 'input', function ( e ) {
				setChange( k, e.target.value );
			} );
		} );
		root.querySelectorAll( '[data-set]' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				var kv = b.getAttribute( 'data-set' ).split( ':' );
				setChange( kv[ 0 ], kv[ 1 ] );
			} );
		} );
		root.querySelectorAll( '[data-side]' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				side = b.getAttribute( 'data-side' );
				refresh();
				renderStage();
			} );
		} );
		$( 'wcj-cq-count' ).addEventListener( 'click', function () {
			var box = $( 'wcj-cq-rows-box' );
			box.hidden = ! box.hidden;
			this.setAttribute( 'aria-expanded', box.hidden ? 'false' : 'true' );
		} );
		$( 'wcj-cq-primary' ).addEventListener( 'click', function () {
			if ( facts().review ) {
				review();
			} else {
				apply( false );
			}
		} );
		$( 'wcj-cq-discard' ).addEventListener( 'click', function () {
			draft = {};
			draftBase = {};
			parked = {};
			conflict = null;
			persist();
			closeReview();
			side = 'now';
			Object.keys( saved.fields ).forEach( clearError );
			fillInputs();
			refresh();
			renderParked();
			renderStage();
			schedule( 0 );
			say( 'Changes discarded. Nothing was saved.' );
		} );
		$( 'wcj-cq-undo' ).addEventListener( 'click', undoAll );
		$( 'wcj-cq-slot' ).addEventListener( 'change', function ( e ) {
			// Unsaved work stays stored for this question and comes back when you return to it.
			if ( e.target.value ) {
				window.location.href = cfg.page + '&slot=' + encodeURIComponent( e.target.value );
			}
		} );
	}

	/* ---------- Start ---------- */
	bind();
	post( 'load', {} ).then( function ( r ) {
		if ( ! r.success ) {
			throw new Error( ( r.data && r.data.error ) || 'Could not load saved settings.' );
		}
		adopt( r.data );
		var read = function ( key ) {
			try {
				var v = JSON.parse( sessionStorage.getItem( key ) || 'null' );
				return v && typeof v === 'object' ? v : null;
			} catch ( e ) {
				return null;
			}
		};
		var str = function ( v ) {
			return typeof v === 'string' ? v : '';
		};
		var s = read( STORE );
		// Candidate09 stored drafts without the site in the key. A same-tab draft found there may belong to another
		// site on this domain, so it is never restored as an active change: it is kept aside for the owner to reuse.
		var legacy = read( LEGACY );
		var setAside = [];
		var keep = function ( k, v, b ) {
			if ( KEYS.indexOf( k ) >= 0 && typeof v === 'string' && norm( k, v ) !== saved.fields[ k ].value ) {
				parked[ k ] = { value: v, base: str( b ) };
				return true;
			}
			return false;
		};
		if ( s ) {
			Object.keys( s.parked && typeof s.parked === 'object' ? s.parked : {} ).forEach( function ( k ) {
				var p = s.parked[ k ];
				if ( p && typeof p === 'object' ) {
					keep( k, p.value, p.base );
				}
			} );
			Object.keys( s.draft && typeof s.draft === 'object' ? s.draft : {} ).forEach( function ( k ) {
				var v = s.draft[ k ];
				if ( KEYS.indexOf( k ) < 0 || typeof v !== 'string' ) {
					return;
				}
				var b = s.base && typeof s.base[ k ] === 'string' ? s.base[ k ] : '';
				if ( saved.fields[ k ].locked ) {
					if ( keep( k, v, b ) ) {
						setAside.push( label( k ) );
					}
				} else if ( norm( k, v ) !== saved.fields[ k ].value ) {
					draft[ k ] = v;
					draftBase[ k ] = b || saved.fields[ k ].base;
				}
			} );
		}
		var bridged = false;
		if ( legacy ) {
			Object.keys( legacy.draft && typeof legacy.draft === 'object' ? legacy.draft : {} ).forEach( function ( k ) {
				if ( ! has( draft, k ) && ! has( parked, k ) ) {
					bridged = keep( k, legacy.draft[ k ], legacy.base && legacy.base[ k ] ) || bridged;
				}
			} );
		}
		if ( setAside.length ) {
			say( parkedText( setAside ), 'error' );
		} else if ( bridged ) {
			say( 'An earlier unsaved draft from this browser tab is kept aside at the top of the form. Use it or remove it.' );
		} else if ( keys().length ) {
			say( 'Your unsaved changes were restored.' );
		}
		side = keys().length ? 'after' : 'now';
		persist();
		if ( legacy ) {
			// Only after the values are stored under the new key.
			try {
				sessionStorage.removeItem( LEGACY );
			} catch ( e ) {}
		}
		renderParked();
		envNotes();
		fillInputs();
		refresh();
		showReceipt();
		renderStage();
		schedule( 0 );
	} ).catch( function ( e ) {
		say( e.message, 'error' );
	} );
}() );

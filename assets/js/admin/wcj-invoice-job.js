/* Branded invoice job canvas. One draft, real-engine preview, one apply, one-step undo. */
( function () {
	'use strict';
	var cfg = window.wcjInvoiceJob;
	var root = document.getElementById( 'wcj-ij' );
	if ( ! cfg || ! root ) {
		return;
	}
	var STORE = 'wcjInvoiceJobDraft:' + cfg.user;
	var NAMED = { '#cccccc': 'Light grey', '#0073aa': 'Blue', '#2f5d50': 'Forest green', '#8a3a12': 'Rust', '#1a1a2e': 'Ink' };
	var BASIC = [ [ 'Black', '#000000' ], [ 'White', '#ffffff' ], [ 'Grey', '#808080' ], [ 'Red', '#d0312d' ], [ 'Orange', '#e8772e' ], [ 'Yellow', '#e5c100' ], [ 'Green', '#2e8b57' ], [ 'Teal', '#178a8a' ], [ 'Blue', '#1f5fbf' ], [ 'Navy', '#1b2a4a' ], [ 'Purple', '#6b3fa0' ], [ 'Pink', '#d6488a' ], [ 'Brown', '#7a4a24' ] ];
	var saved = null; // Server load(): fields, env, receipt.
	var savedVer = 0; // Bumped on every fresh server state; previews from older states are out of date.
	var draft = {};
	var draftBase = {};
	var side = 'now';
	var pdfs = { now: null, after: null };
	var seqs = { now: 0, after: 0 };
	var rendering = { now: false, after: false };
	var busy = false;
	var timer = null;
	var headerLinked = true;
	var identityBase = null; // Identity-group base from the first seller/title edit; sent unchanged until rebase or discard.
	var parked = {}; // Restored draft values whose field is now locked: kept and disclosed, never submitted.
	var lineMatch = true;

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
		var s = $( 'wcj-ij-status' );
		s.className = 'wcj-ij-status' + ( kind ? ' is-' + kind : '' );
		s.textContent = msg || '';
	}
	function plural( n, word ) {
		return n + ' ' + word + ( n === 1 ? '' : 's' );
	}
	function post( op, data ) {
		var body = new FormData();
		body.append( 'action', 'wcj_invoice_job_' + op );
		body.append( 'nonce', cfg.nonces[ op ] );
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

	/* ---------- Visual values: swatches, thumbnails, plain words ---------- */
	function colorName( hex ) {
		hex = ( hex || '' ).toLowerCase();
		if ( NAMED[ hex ] ) {
			return NAMED[ hex ];
		}
		if ( ! /^#[0-9a-f]{6}$/.test( hex ) ) {
			return hex;
		}
		var rgb = function ( h ) {
			return [ 1, 3, 5 ].map( function ( i ) {
				return parseInt( h.substr( i, 2 ), 16 );
			} );
		};
		var c = rgb( hex );
		var best = BASIC[ 0 ];
		var bestD = Infinity;
		BASIC.forEach( function ( b ) {
			var o = rgb( b[ 1 ] );
			var d = Math.pow( c[ 0 ] - o[ 0 ], 2 ) + Math.pow( c[ 1 ] - o[ 1 ], 2 ) + Math.pow( c[ 2 ] - o[ 2 ], 2 );
			if ( d < bestD ) {
				bestD = d;
				best = b;
			}
		} );
		return 'Custom ' + best[ 0 ].toLowerCase();
	}
	function visual( key, value ) {
		var f = cfg.fields[ key ];
		var wrap = el( 'span', { class: 'wcj-ij-val' } );
		if ( ! f ) {
			wrap.textContent = value;
			return wrap;
		}
		if ( f.type === 'color' ) {
			var sw = el( 'span', { class: 'wcj-ij-dot', 'aria-hidden': 'true' } );
			sw.style.background = value;
			wrap.appendChild( sw );
			wrap.appendChild( document.createTextNode( colorName( value ) ) );
		} else if ( f.type === 'logo' ) {
			if ( value ) {
				wrap.appendChild( el( 'img', { class: 'wcj-ij-mini', src: value, alt: 'Logo' } ) );
			} else {
				wrap.textContent = 'No logo';
			}
		} else if ( f.type === 'create' || f.type === 'attach' ) {
			wrap.textContent = cfg.choices[ key ][ value ] || value;
		} else if ( f.type === 'yesno' ) {
			wrap.textContent = value === 'yes' ? 'Shown' : 'Hidden';
		} else if ( value === '' ) {
			wrap.textContent = key.indexOf( 'seller_' ) === 0 ? 'Not set' : 'Empty';
		} else {
			wrap.textContent = String( value ).replace( /\n/g, ', ' );
		}
		return wrap;
	}
	function current( key ) {
		return has( draft, key ) ? draft[ key ] : saved.fields[ key ].value;
	}
	// Seller fields and the title share one saved unit (identity + generated template); its base is captured once.
	// Same rule as the server's template generator: the placeholder stays only when name, address and number are all
	// empty, on a stock or canvas-managed template. Custom templates are not inspected.
	function showsPlaceholder( value ) {
		var mode = saved.env.template_mode;
		return ( mode === 'stock' || mode === 'managed' ) && [ 'seller_name', 'seller_address', 'seller_reference' ].every( function ( k ) {
			return String( value( k ) ).trim() === '';
		} );
	}
	// Effective linkage, recomputed from current server locks on every render (load, return from native edits,
	// apply/recover/stale refreshes). The stored intent (headerLinked) is kept for when the seller is editable again.
	function linked() {
		return headerLinked && ! saved.fields.seller_name.locked && ! saved.fields.header_text.locked;
	}
	function inIdentityGroup( k ) {
		return k.indexOf( 'seller_' ) === 0 || k === 'header_title_text';
	}
	function persist() {
		if ( ! keys().some( inIdentityGroup ) ) {
			identityBase = null;
		}
		try {
			sessionStorage.setItem( STORE, JSON.stringify( { draft: draft, base: draftBase, identityBase: identityBase, headerLinked: headerLinked, lineMatch: lineMatch, parked: parked } ) );
		} catch ( e ) {}
	}
	function keys() {
		return Object.keys( cfg.fields ).filter( function ( k ) {
			return has( draft, k );
		} );
	}
	function tier() {
		return keys().reduce( function ( t, k ) {
			return Math.max( t, cfg.fields[ k ].tier );
		}, 0 );
	}
	function visualDraft() {
		var v = {};
		keys().forEach( function ( k ) {
			if ( cfg.fields[ k ].tier === 0 ) {
				v[ k ] = draft[ k ];
			}
		} );
		return v;
	}
	function visualKey() {
		return JSON.stringify( visualDraft() );
	}
	function hasVisual() {
		return visualKey() !== '{}';
	}

	/* ---------- Editing ---------- */
	function setChange( key, value ) {
		if ( ! saved || saved.fields[ key ].locked ) {
			return;
		}
		if ( value === saved.fields[ key ].value ) {
			delete draft[ key ];
			delete draftBase[ key ];
		} else {
			draft[ key ] = value;
			if ( ! draftBase[ key ] ) {
				draftBase[ key ] = saved.fields[ key ].base;
			}
			if ( inIdentityGroup( key ) && ! identityBase ) {
				identityBase = saved.env.identity_base;
			}
		}
		clearError( key );
	}
	function edited( key ) {
		persist();
		closeReview();
		say( '' ); // A finished save/undo/error message describes the past, not this draft.
		if ( cfg.fields[ key ] && cfg.fields[ key ].tier === 0 ) {
			side = hasVisual() ? 'after' : 'now';
			schedule();
		}
		fillInputs();
		refresh();
	}
	function clearError( key ) {
		var e = $( 'wcj-ij-err-' + key );
		var input = $( 'wcj-ij-' + key );
		if ( e && ! e.classList.contains( 'is-note' ) ) {
			e.textContent = '';
		}
		if ( input ) {
			input.removeAttribute( 'aria-invalid' );
		}
	}
	function showIssues( issues ) {
		var first = null;
		Object.keys( issues || {} ).forEach( function ( key ) {
			var e = $( 'wcj-ij-err-' + key );
			var input = $( 'wcj-ij-' + key );
			if ( e ) {
				e.textContent = issues[ key ];
			}
			if ( input ) {
				input.setAttribute( 'aria-invalid', 'true' );
				first = first || input;
			}
		} );
		if ( first ) {
			var d = first.closest( 'details' );
			if ( d ) {
				d.open = true;
			}
			first.focus();
		}
	}
	function fillInputs() {
		Object.keys( cfg.fields ).forEach( function ( key ) {
			var input = $( 'wcj-ij-' + key );
			var info = saved.fields[ key ];
			if ( ! input ) {
				return;
			}
			var v = current( key );
			if ( input.type === 'checkbox' ) {
				input.checked = v === 'yes';
			} else if ( input.tagName === 'SELECT' ) {
				if ( ! input.options.length || input.getAttribute( 'data-filled' ) !== String( v === 'custom' ) ) {
					input.innerHTML = '';
					Object.keys( cfg.choices[ key ] ).forEach( function ( c ) {
						if ( c !== 'custom' || v === 'custom' ) {
							input.appendChild( el( 'option', { value: c }, cfg.choices[ key ][ c ] ) );
						}
					} );
					input.setAttribute( 'data-filled', String( v === 'custom' ) );
				}
				input.value = v;
			} else if ( input.type === 'color' ) {
				if ( /^#[0-9a-f]{6}$/.test( v ) ) {
					input.value = v;
				}
			} else if ( document.activeElement !== input ) {
				input.value = v;
			}
			input.disabled = !! info.locked;
			var err = $( 'wcj-ij-err-' + key );
			if ( info.locked && err && key.indexOf( 'seller_' ) !== 0 ) {
				err.textContent = info.locked;
				err.classList.add( 'is-note' );
			}
		} );
		var sellerLocked = !! saved.fields.seller_name.locked;
		$( 'wcj-ij-seller' ).hidden = sellerLocked;
		$( 'wcj-ij-seller-lock' ).hidden = ! sellerLocked;
		// Header line follows only its own server lock; a locked seller block simply means nothing to mirror.
		$( 'wcj-ij-header-link' ).checked = linked();
		$( 'wcj-ij-header-link' ).disabled = sellerLocked || !! saved.fields.header_text.locked;
		$( 'wcj-ij-header_text' ).disabled = linked() || !! saved.fields.header_text.locked;
		$( 'wcj-ij-line-match' ).checked = lineMatch;
		$( 'wcj-ij-header_line_color' ).disabled = lineMatch || !! saved.fields.header_line_color.locked;
		var logo = current( 'header_image' );
		$( 'wcj-ij-logo-img' ).hidden = ! logo;
		$( 'wcj-ij-logo-none' ).hidden = !! logo;
		if ( logo ) {
			$( 'wcj-ij-logo-img' ).src = logo;
		}
		$( 'wcj-ij-logo-pick' ).disabled = !! saved.fields.header_image.locked;
		$( 'wcj-ij-logo-clear' ).hidden = ! logo || !! saved.fields.header_image.locked;
		var color = current( 'header_text_color' );
		root.querySelectorAll( '[data-swatch]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-pressed', b.getAttribute( 'data-swatch' ) === color ? 'true' : 'false' );
			b.disabled = !! saved.fields.header_text_color.locked;
		} );
		$( 'wcj-ij-colorname' ).textContent = colorName( color );
	}
	function setColor( v ) {
		setChange( 'header_text_color', v );
		if ( lineMatch ) {
			setChange( 'header_line_color', v );
		}
		edited( 'header_text_color' );
	}
	function bind() {
		root.querySelectorAll( '[data-k]' ).forEach( function ( input ) {
			var key = input.getAttribute( 'data-k' );
			var ev = input.type === 'text' || input.type === 'number' || input.tagName === 'TEXTAREA' ? 'input' : 'change';
			input.addEventListener( ev, function () {
				var v = input.type === 'checkbox' ? ( input.checked ? 'yes' : 'no' ) : input.value;
				if ( key === 'header_text_color' ) {
					setColor( v );
					return;
				}
				setChange( key, v );
				if ( key === 'header_text' ) {
					headerLinked = false; // Typed directly (only possible while not linked): keep it independent.
				}
				if ( key === 'seller_name' && linked() ) {
					setChange( 'header_text', v.trim() === '' ? saved.fields.header_text.value : v.trim() );
				}
				edited( key );
			} );
		} );
		root.querySelectorAll( '[data-swatch]' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				setColor( b.getAttribute( 'data-swatch' ) );
			} );
		} );
		$( 'wcj-ij-header-link' ).addEventListener( 'change', function ( e ) {
			headerLinked = e.target.checked;
			if ( linked() && current( 'seller_name' ) ) {
				setChange( 'header_text', current( 'seller_name' ) );
			}
			edited( 'header_text' );
		} );
		$( 'wcj-ij-line-match' ).addEventListener( 'change', function ( e ) {
			lineMatch = e.target.checked;
			if ( lineMatch ) {
				setChange( 'header_line_color', current( 'header_text_color' ) );
			}
			edited( 'header_line_color' );
		} );
		$( 'wcj-ij-logo-pick' ).addEventListener( 'click', function () {
			if ( ! window.wp || ! wp.media ) {
				say( 'The Media Library could not open.', 'error' );
				return;
			}
			var frame = wp.media( { title: 'Choose a logo (PNG or JPEG)', library: { type: [ 'image/png', 'image/jpeg' ] }, multiple: false, button: { text: 'Use this logo' } } );
			frame.on( 'select', function () {
				var a = frame.state().get( 'selection' ).first().toJSON();
				setChange( 'header_image', a.url || '' );
				edited( 'header_image' );
				$( 'wcj-ij-logo-pick' ).focus();
			} );
			frame.open();
		} );
		$( 'wcj-ij-logo-clear' ).addEventListener( 'click', function () {
			setChange( 'header_image', '' );
			edited( 'header_image' );
			$( 'wcj-ij-logo-pick' ).focus();
		} );
		root.querySelectorAll( '[data-side]' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				side = b.getAttribute( 'data-side' );
				if ( ! fresh( side ) ) {
					schedule( 0 );
				}
				showPdf();
			} );
		} );
		$( 'wcj-ij-render' ).addEventListener( 'click', function () {
			render( side );
		} );
		$( 'wcj-ij-auto' ).addEventListener( 'change', function ( e ) {
			if ( e.target.checked ) {
				schedule( 0 );
			}
		} );
		$( 'wcj-ij-count' ).addEventListener( 'click', function () {
			var box = $( 'wcj-ij-rows-box' );
			box.hidden = ! box.hidden;
			$( 'wcj-ij-count' ).setAttribute( 'aria-expanded', box.hidden ? 'false' : 'true' );
		} );
		$( 'wcj-ij-discard' ).addEventListener( 'click', function () {
			draft = {};
			draftBase = {};
			parked = {};
			identityBase = null;
			side = 'now';
			persist();
			closeReview();
			envNotes();
			fillInputs();
			refresh();
			say( 'Draft discarded. Nothing was saved.' );
		} );
		$( 'wcj-ij-primary' ).addEventListener( 'click', function () {
			if ( tier() >= 2 ) {
				review();
			} else {
				apply( false );
			}
		} );
		$( 'wcj-ij-undo' ).addEventListener( 'click', function () {
			recover( [] );
		} );
	}

	/* ---------- Draft bar and change list ---------- */
	function identityHints() {
		[ 'seller_name', 'seller_address' ].forEach( function ( k ) {
			$( 'wcj-ij-unset-' + k ).hidden = ! showsPlaceholder( current ) || !! saved.fields[ k ].locked;
		} );
	}
	function refresh() {
		identityHints();
		var ks = keys();
		var list = $( 'wcj-ij-rows' );
		list.innerHTML = '';
		ks.forEach( function ( k ) {
			var li = el( 'li' );
			li.appendChild( el( 'span', { class: 'wcj-ij-rl' }, cfg.fields[ k ].label ) );
			li.appendChild( visual( k, saved.fields[ k ].value ) );
			li.appendChild( el( 'span', { class: 'wcj-ij-arrow', 'aria-label': 'to' }, '→' ) );
			li.appendChild( visual( k, draft[ k ] ) );
			if ( draftBase[ k ] && draftBase[ k ] !== saved.fields[ k ].base ) {
				li.appendChild( el( 'span', { class: 'wcj-ij-flag' }, 'changed elsewhere' ) );
			}
			var u = el( 'button', { type: 'button', class: 'button-link', 'aria-label': 'Undo ' + cfg.fields[ k ].label }, 'Undo' );
			u.addEventListener( 'click', function () {
				delete draft[ k ];
				delete draftBase[ k ];
				clearError( k );
				edited( k );
				if ( ! keys().length ) {
					$( 'wcj-ij-primary' ).focus();
				}
			} );
			li.appendChild( u );
			list.appendChild( li );
		} );
		var n = ks.length;
		var receiptUndo = saved && saved.receipt && saved.receipt.undo;
		$( 'wcj-ij-draftbar' ).hidden = ! n && receiptUndo && ! Object.keys( parked ).length;
		$( 'wcj-ij-savedbar' ).hidden = !! n || ! receiptUndo;
		if ( receiptUndo ) {
			$( 'wcj-ij-saved' ).textContent = (saved.receipt.rows.every( function ( row ) { return row.readback_ok && row.still_ours; } ) ? 'Saved: ' + plural( saved.receipt.rows.length, 'change' ) : 'Last change: check affected rows');
		}
		var count = $( 'wcj-ij-count' );
		count.textContent = n ? plural( n, 'change' ) + ' ▾' : ( Object.keys( parked ).length ? plural( Object.keys( parked ).length, 'kept draft value' ) : 'No changes' );
		count.disabled = ! n;
		if ( ! n ) {
			$( 'wcj-ij-rows-box' ).hidden = true;
			count.setAttribute( 'aria-expanded', 'false' );
		}
		$( 'wcj-ij-discard' ).hidden = ! n && ! Object.keys( parked ).length;
		var btn = $( 'wcj-ij-primary' );
		btn.disabled = ! n || busy;
		btn.textContent = tier() >= 2 ? 'Review ' + plural( n, 'change' ) : ( n ? 'Apply ' + plural( n, 'change' ) : 'Apply' );
		var afterBtn = root.querySelector( '[data-side="after"]' );
		afterBtn.disabled = ! hasVisual();
		afterBtn.title = hasVisual() ? '' : 'No look changes yet';
		if ( ! hasVisual() && side === 'after' ) {
			side = 'now';
		}
		showPdf();
	}

	/* ---------- Preview with honest freshness ---------- */
	function fresh( which ) {
		var p = pdfs[ which ];
		return !! p && p.ver === savedVer && ( which === 'now' || p.key === visualKey() );
	}
	function schedule( delay ) {
		if ( ! $( 'wcj-ij-auto' ).checked ) {
			showPdf();
			return;
		}
		clearTimeout( timer );
		timer = setTimeout( function () {
			if ( ! fresh( side ) ) {
				render( side );
			}
		}, delay === undefined ? 600 : delay );
	}
	function render( which ) {
		var mine = ++seqs[ which ];
		var key = visualKey();
		var ver = savedVer;
		var data = { side: which };
		if ( which === 'after' ) {
			data.changes = key;
		}
		rendering[ which ] = true;
		showPdf();
		return post( 'preview', data ).then( function ( r ) {
			if ( mine !== seqs[ which ] ) {
				return; // A newer render for this side replaced this one.
			}
			if ( ! r.success ) {
				if ( r.data && r.data.issues ) {
					showIssues( r.data.issues );
				}
				throw new Error( ( r.data && r.data.error ) || 'Preview failed. Nothing was saved.' );
			}
			var bin = atob( r.data.pdf );
			var bytes = new Uint8Array( bin.length );
			for ( var i = 0; i < bin.length; i++ ) {
				bytes[ i ] = bin.charCodeAt( i );
			}
			if ( pdfs[ which ] ) {
				URL.revokeObjectURL( pdfs[ which ].url );
			}
			pdfs[ which ] = { url: URL.createObjectURL( new Blob( [ bytes ], { type: 'application/pdf' } ) ), label: r.data.label, limits: r.data.limits, key: key, ver: ver };
		} ).catch( function ( e ) {
			say( e.message, 'error' );
		} ).then( function () {
			if ( mine === seqs[ which ] ) {
				rendering[ which ] = false;
			}
			showPdf();
			if ( which === side && ! fresh( side ) && ! rendering[ side ] && $( 'wcj-ij-auto' ).checked && pdfs[ which ] && pdfs[ which ].key !== visualKey() ) {
				schedule(); // The draft moved on while rendering.
			}
		} );
	}
	function showPdf() {
		root.querySelectorAll( '[data-side]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-pressed', b.getAttribute( 'data-side' ) === side ? 'true' : 'false' );
		} );
		var p = pdfs[ side ];
		var frame = $( 'wcj-ij-iframe' );
		var veil = $( 'wcj-ij-veil' );
		var ok = fresh( side );
		frame.hidden = ! p;
		if ( p ) {
			var src = p.url + '#toolbar=0&navpanes=0&view=FitH';
			if ( frame.getAttribute( 'src' ) !== src ) {
				frame.setAttribute( 'src', src );
			}
		}
		veil.hidden = ok;
		veil.classList.toggle( 'is-over', !! p );
		$( 'wcj-ij-veil-msg' ).textContent = rendering[ side ] ? 'Rendering…' : ( p ? 'Out of date. Render the preview to see the latest values.' : ( side === 'now' ? 'Saved invoice not rendered yet' : 'Draft not rendered yet' ) );
		$( 'wcj-ij-render' ).hidden = rendering[ side ];
		var label = side === 'now' ? 'Saved' : 'Draft';
		$( 'wcj-ij-fresh' ).textContent = ok ? label + ' · ' + p.label : ( rendering[ side ] ? 'Rendering…' : label + ' · out of date' );
		if ( side === 'now' && keys().length ) {
			$( 'wcj-ij-fresh' ).textContent += ' · Unsaved changes remain in the editor';
		}
		$( 'wcj-ij-fresh' ).className = 'wcj-ij-fresh' + ( ok ? '' : ' is-stale' );
		var lim = $( 'wcj-ij-limits' );
		lim.innerHTML = '';
		( p && p.limits ? p.limits : [] ).concat( [ 'Shows how the PDF looks. It does not prove when invoices are created or that email is delivered.' ] ).forEach( function ( l ) {
			lim.appendChild( el( 'li', {}, l ) );
		} );
	}

	/* ---------- Review: look as one line, consequences as short sentences ---------- */
	function closeReview() {
		$( 'wcj-ij-review' ).hidden = true;
		$( 'wcj-ij-draftbar' ).classList.remove( 'is-review' );
	}
	function consequences() {
		var out = [];
		var c = has( draft, 'create_on' ) ? draft.create_on : null;
		var a = has( draft, 'attach_to_emails' ) ? draft.attach_to_emails : null;
		if ( c !== null ) {
			out.push( c === 'off' ? 'No new invoices will be created. Existing invoices stay.' : ( c === 'manual' ? 'Invoices are created only when you make one on an order.' : 'Each new ' + ( c === 'processing' ? 'Processing' : 'Completed' ) + ' order gets an invoice and the next invoice number.' ) );
			if ( c !== 'off' && ! saved.env.module_enabled ) {
				out.push( 'PDF Invoicing: Off → On.' );
			}
		}
		if ( a !== null ) {
			out.push( a === 'none' ? 'Customer emails stop carrying the PDF.' : 'The PDF is attached to ' + cfg.choices.attach_to_emails[ a ] + '. Delivery is not tested here.' );
		}
		return out;
	}
	function review() {
		var box = $( 'wcj-ij-review' );
		var ks = keys();
		box.innerHTML = '';
		box.appendChild( el( 'h3', {}, 'Review ' + plural( ks.length, 'change' ) ) );
		box.appendChild( el( 'p', {}, 'Some choices update linked fields. The review groups changes; Technical details lists the native settings that would change.' ) );
		// Linked values (header line = business name, line colour = header colour) are shown once; Technical details keep every key.
		var look = ks.filter( function ( k ) {
			if ( cfg.fields[ k ].tier !== 0 ) {
				return false;
			}
			if ( k === 'header_text' && has( draft, 'seller_name' ) && draft.header_text === draft.seller_name.trim() ) {
				return false;
			}
			return ! ( k === 'header_line_color' && has( draft, 'header_text_color' ) && draft.header_line_color === draft.header_text_color );
		} );
		if ( look.length ) {
			var grid = el( 'div', { class: 'wcj-ij-look' } );
			grid.appendChild( el( 'strong', { class: 'wcj-ij-look-h' }, 'Look' ) );
			look.forEach( function ( k ) {
				var chip = el( 'span', { class: 'wcj-ij-chip' } );
				chip.appendChild( el( 'span', { class: 'wcj-ij-cl' }, cfg.fields[ k ].label ) );
				chip.appendChild( visual( k, saved.fields[ k ].value ) );
				chip.appendChild( el( 'span', { class: 'wcj-ij-arrow', 'aria-label': 'to' }, '→' ) );
				chip.appendChild( visual( k, draft[ k ] ) );
				grid.appendChild( chip );
			} );
			box.appendChild( grid );
		}
		var cq = consequences();
		if ( cq.length ) {
			box.appendChild( el( 'strong', {}, 'Invoices and email' ) );
			var ul = el( 'ul', { class: 'wcj-ij-cqs' } );
			cq.forEach( function ( s ) {
				ul.appendChild( el( 'li', {}, s ) );
			} );
			box.appendChild( ul );
			var live = has( draft, 'create_on' ) && draft.create_on !== 'off' && draft.create_on !== 'manual';
			if ( live && showsPlaceholder( current ) ) {
				box.appendChild( el( 'p', { class: 'wcj-ij-warn' }, 'The seller block still says COMPANY NAME. Add your business details first.' ) );
			}
		}
		var confirm = null;
		if ( cq.length ) {
			var lab = el( 'label', { class: 'wcj-ij-ck' } );
			confirm = el( 'input', { type: 'checkbox' } );
			lab.appendChild( confirm );
			lab.appendChild( document.createTextNode( ' Yes, make these invoice and email changes' ) );
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
			$( 'wcj-ij-primary' ).focus();
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
		if ( has( draft, 'create_on' ) && draft.create_on !== 'off' && ! saved.env.module_enabled ) {
			tl.appendChild( el( 'li', {}, 'wcj_pdf_invoicing_enabled' ) );
		}
		det.appendChild( tl );
		det.appendChild( el( 'p', {}, 'Settings are written one at a time and each is read back. Issued invoices, numbering, tax and other documents are not changed.' ) );
		box.appendChild( det );
		$( 'wcj-ij-rows-box' ).hidden = true;
		$( 'wcj-ij-draftbar' ).classList.add( 'is-review' );
		box.hidden = false;
		if ( hasVisual() ) {
			side = 'after';
			schedule( 0 );
			showPdf();
		}
		( confirm || go ).focus();
	}

	/* ---------- Apply, undo, receipt ---------- */
	function adopt( fresh ) {
		if ( fresh ) {
			saved = fresh;
			savedVer++;
		}
	}
	function finish() {
		busy = false;
		envNotes();
		fillInputs();
		refresh();
		showReceipt();
		schedule( 0 );
	}
	function apply( confirmed ) {
		if ( busy ) {
			return;
		}
		busy = true;
		refresh();
		say( 'Saving…' );
		var base = {};
		keys().forEach( function ( k ) {
			base[ k ] = draftBase[ k ] || saved.fields[ k ].base;
		} );
		base.module = saved.env.module_base;
		// One base for the whole identity group, from its first edit; never a later per-field base.
		base.identity = identityBase || saved.env.identity_base;
		post( 'apply', { changes: JSON.stringify( draft ), base: JSON.stringify( base ), confirm: confirmed ? '1' : '0' } ).then( function ( r ) {
			var d = r.data || {};
			adopt( d.fresh );
			if ( r.success && ( d.status === 'applied' || d.status === 'unchanged' ) ) {
				draft = {};
				draftBase = {};
				side = 'now';
				persist();
				closeReview();
				say( d.status === 'applied' && saved.receipt && saved.receipt.undo ? 'Saved: ' + plural( saved.receipt.rows.length, 'change' ) + '. Undo is available.' : d.message, d.status === 'applied' && saved.receipt && saved.receipt.undo ? 'receipt' : 'ok' );
			} else if ( r.success ) {
				draft = {};
				draftBase = {};
				persist();
				closeReview();
				say( d.message, 'error' );
			} else if ( d.status === 'stale' ) {
				say( d.error, 'error' );
				showStale( d.stale, d.group );
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
		say( 'Undoing…' );
		post( 'recover', { options: JSON.stringify( options ) } ).then( function ( r ) {
			var d = r.data || {};
			adopt( d.fresh );
			var skipped = d.ignored ? ' ' + d.ignored + ( d.ignored === 1 ? ' unreadable undo entry was' : ' unreadable undo entries were' ) + ' ignored; nothing was written for them.' : '';
			if ( ! r.success ) {
				say( d.error || 'Undo failed. Nothing is confirmed.', 'error' );
			} else if ( d.status === 'restored' ) {
				say( 'Undone. Issued invoices and orders were not touched.' + skipped, skipped ? 'error' : 'ok' );
			} else {
				say( 'Partly undone. Settings changed since your save were kept: ' + d.conflicts.map( function ( f ) {
					return cfg.fields[ f ] ? cfg.fields[ f ].label : ( f === 'module' ? 'PDF Invoicing' : 'Seller details and invoice body' );
				} ).join( ', ' ) + '.' + skipped, 'error' );
			}
		} ).catch( function ( e ) {
			say( e.message, 'error' );
		} ).then( finish );
	}
	// Conflict after a stale Apply (nothing was written). Bases are NOT rebased here: the owner first sees, for every
	// affected pending change, what is saved now versus theirs, then either uses the saved value per change or
	// explicitly keeps their changes. Only "Keep my changes" rebases; until then another Apply is rejected again.
	var conflict = null; // { keys: [...], group: bool, module: 'yes'|'no'|undefined }
	function showStale( stale, group ) {
		// Drafted values that now equal the saved value are no longer changes.
		Object.keys( draft ).forEach( function ( k ) {
			if ( draft[ k ] === saved.fields[ k ].value ) {
				delete draft[ k ];
				delete draftBase[ k ];
			}
		} );
		persist();
		conflict = { keys: Object.keys( stale ).filter( function ( k ) {
			return k !== 'module';
		} ), group: !! group, module: stale.module };
		renderConflict();
		$( 'wcj-ij-review' ).focus();
	}
	function renderConflict() {
		var box = $( 'wcj-ij-review' );
		box.innerHTML = '';
		var pending = conflict.keys.filter( function ( k ) {
			return has( draft, k );
		} );
		if ( ! pending.length && conflict.module === undefined ) {
			conflict = null;
			closeReview();
			say( 'Using the saved values. Nothing was saved; apply your other changes when ready.' );
			return;
		}
		box.appendChild( el( 'h3', {}, 'Saved elsewhere since you started' ) );
		if ( pending.length ) {
			box.appendChild( el( 'p', {}, 'Choose Use saved for any edit you want to drop, or Keep my changes to keep the remaining draft. Review it before applying; nothing is saved by this choice.' ) );
		}
		if ( conflict.group ) {
			box.appendChild( el( 'p', {}, 'Seller details or the title were saved somewhere else after your draft began. Compare each of your changes with what is saved now.' ) );
		}
		var ul = el( 'ul', { class: 'wcj-ij-rows' } );
		if ( conflict.module !== undefined ) {
			var mli = el( 'li' );
			mli.appendChild( el( 'span', { class: 'wcj-ij-rl' }, 'PDF Invoicing' ) );
			mli.appendChild( el( 'span', {}, 'saved now: ' + ( conflict.module === 'yes' ? 'On' : 'Off' ) ) );
			ul.appendChild( mli );
		}
		pending.forEach( function ( k ) {
			var li = el( 'li' );
			li.appendChild( el( 'span', { class: 'wcj-ij-rl' }, cfg.fields[ k ].label ) );
			li.appendChild( el( 'span', { class: 'wcj-ij-cl' }, 'saved now' ) );
			li.appendChild( visual( k, saved.fields[ k ].value ) );
			li.appendChild( el( 'span', { class: 'wcj-ij-arrow', 'aria-label': 'yours' }, '→' ) );
			li.appendChild( el( 'span', { class: 'wcj-ij-cl' }, 'yours' ) );
			li.appendChild( visual( k, draft[ k ] ) );
			var use = el( 'button', { type: 'button', class: 'button-link', 'aria-label': 'Use saved ' + cfg.fields[ k ].label }, 'Use saved' );
			use.addEventListener( 'click', function () {
				delete draft[ k ];
				delete draftBase[ k ];
				persist(); // Clears the identity base if no seller/title change remains.
				fillInputs();
				refresh();
				schedule();
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
			if ( keys().some( inIdentityGroup ) ) {
				identityBase = saved.env.identity_base;
			}
			conflict = null;
			persist();
			closeReview();
			refresh();
			say( 'Your changes will replace what is saved now. Apply when ready.' );
			$( 'wcj-ij-primary' ).focus();
		} );
		box.appendChild( keep );
		$( 'wcj-ij-draftbar' ).classList.remove( 'is-review' );
		box.hidden = false;
	}
	function showReceipt() {
		var wrap = $( 'wcj-ij-receipt' );
		var body = $( 'wcj-ij-receipt-body' );
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
			// Seller details and the generated invoice body share one checkbox; the server enforces the pairing too.
			var cb = el( 'input', { type: 'checkbox', value: ( row.options || [ row.option ] ).join( ',' ), 'aria-label': 'Restore ' + row.label } );
			cb.disabled = ! row.still_ours;
			li.appendChild( cb );
			li.appendChild( el( 'span', { class: 'wcj-ij-rl' }, row.label ) );
			li.appendChild( visual( row.field, row.before ) );
			li.appendChild( el( 'span', { class: 'wcj-ij-arrow' }, '→' ) );
			li.appendChild( visual( row.field, row.after ) );
			li.appendChild( el( 'span', { class: 'wcj-ij-state' }, ! row.readback_ok ? 'Saved value differs' : ( row.still_ours ? 'Saved' : ( row.restored ? 'Undone' : 'Changed later, kept' ) ) ) );
			ul.appendChild( li );
		} );
		body.appendChild( el( 'p', { class: 'description' }, 'Saved ' + rc.time + '. Restoring only puts back settings nobody changed since.' ) );
		body.appendChild( ul );
		var btn = el( 'button', { type: 'button', class: 'button' }, 'Restore selected' );
		btn.addEventListener( 'click', function () {
			var opts = [];
			body.querySelectorAll( 'input[type=checkbox]:checked' ).forEach( function ( c ) {
				opts = opts.concat( c.value.split( ',' ) );
			} );
			if ( ! opts.length ) {
				say( 'Select a setting to restore.', 'error' );
				return;
			}
			recover( opts );
		} );
		body.appendChild( btn );
		wrap.hidden = false;
	}
	function envNotes() {
		var box = $( 'wcj-ij-env' );
		var e = saved.env;
		var notes = [];
		box.innerHTML = '';
		if ( e.template_mode === 'custom' || e.template_mode === 'unsupported' ) {
			notes.push( 'Custom HTML template: kept as saved. Title changes the header only.' );
		}
		notes.push( e.module_enabled ? 'PDF Invoicing: On' : 'PDF Invoicing: Off' );
		if ( ! e.images_dir ) {
			notes.push( 'Logos need a different image folder (Advanced settings).' );
		}
		if ( e.temp_dir_disabled ) {
			notes.push( 'Email attachments are off in Advanced settings.' );
		}
		var savedValue = function ( k ) {
			return saved.fields[ k ].value;
		};
		var liveNow = [ 'processing', 'completed' ].indexOf( saved.fields.create_on.value ) !== -1;
		if ( liveNow && showsPlaceholder( savedValue ) ) {
			notes.push( 'Invoices being created show COMPANY NAME as seller.' );
		}
		var held = Object.keys( parked );
		if ( held.length ) {
			notes.push( 'Kept draft only, not applied: ' + held.map( function ( k ) {
				return cfg.fields[ k ].label + ': ' + parked[ k ] + '. ' + saved.fields[ k ].locked;
			} ).join( ' ' ) + ' Discard clears these draft values only; saved settings stay unchanged.' );
		}
		notes.forEach( function ( n ) {
			box.appendChild( el( 'span', { class: 'wcj-ij-note' + ( n === 'PDF Invoicing: On' ? ' is-on' : '' ) }, n ) );
		} );
	}

	/* ---------- Native notices: informational ones grouped under a visible disclosure; errors and warnings untouched ---------- */
	window.addEventListener( 'load', function () {
		var box = $( 'wcj-ij-info' );
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

	/* ---------- Start ---------- */
	bind();
	post( 'load', {} ).then( function ( r ) {
		if ( ! r.success ) {
			throw new Error( ( r.data && r.data.error ) || 'Could not load saved settings.' );
		}
		adopt( r.data );
		var f = saved.fields;
		var s = null;
		try {
			s = JSON.parse( sessionStorage.getItem( STORE ) || 'null' );
		} catch ( e ) {
			s = null;
		}
		if ( s && typeof s === 'object' ) {
			// Values for fields that are locked now are kept aside and disclosed, never submitted or silently dropped.
			var stored = Object.assign( {}, s.parked || {}, s.draft || {} );
			Object.keys( stored ).forEach( function ( k ) {
				if ( ! cfg.fields[ k ] || typeof stored[ k ] !== 'string' ) {
					return;
				}
				if ( f[ k ].locked ) {
					parked[ k ] = stored[ k ];
				} else if ( s.draft && has( s.draft, k ) && stored[ k ] !== f[ k ].value ) {
					draft[ k ] = stored[ k ];
					draftBase[ k ] = ( s.base && s.base[ k ] ) || f[ k ].base;
				}
			} );
			if ( keys().some( inIdentityGroup ) ) {
				identityBase = typeof s.identityBase === 'string' ? s.identityBase : saved.env.identity_base;
			}
		}
		// Linkage intent: the persisted choice wins; otherwise derive it from the restored draft, not saved values alone.
		headerLinked = s && typeof s.headerLinked === 'boolean' ? s.headerLinked : ( current( 'header_text' ) === current( 'seller_name' ) || ( ! has( draft, 'header_text' ) && ( f.header_text.value === 'Company Name' || f.header_text.value === '' ) ) );
		headerLinked = headerLinked && ! f.header_text.locked;
		lineMatch = s && typeof s.lineMatch === 'boolean' ? s.lineMatch : current( 'header_line_color' ) === current( 'header_text_color' );
		var heldKeys = Object.keys( parked );
		if ( heldKeys.length ) {
			say( 'Draft restored with values that cannot be applied. The kept values and current field restrictions are shown above. Discard clears this draft only.', 'error' );
		} else if ( keys().length ) {
			say( 'Your unsaved draft was restored.' );
		}
		if ( keys().length ) {
			side = hasVisual() ? 'after' : 'now';
		}
		persist();
		envNotes();
		fillInputs();
		refresh();
		showReceipt();
		schedule( 0 );
		// Editing starts only after the saved state, restored draft and native field locks are ready.
		$( 'wcj-ij-workspace' ).removeAttribute( 'inert' );
		$( 'wcj-ij-workspace' ).setAttribute( 'aria-busy', 'false' );
		$( 'wcj-ij-startup' ).remove();
	} ).catch( function ( e ) {
		$( 'wcj-ij-startup-message' ).textContent = 'Saved settings could not load. Editing remains unavailable. Nothing was saved.';
		say( e.message, 'error' );
	} );
}() );

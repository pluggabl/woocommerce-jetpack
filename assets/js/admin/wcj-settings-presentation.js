/* Progressive enhancement only. Native controls, tabs and saves remain authoritative. */
( function () {
	'use strict';
	function init() {
		if ( typeof wcjSettingsPresentation === 'undefined' ) { return; }
		var config = wcjSettingsPresentation;
		var firstTab = Object.keys( config.tabs ).map( function ( id ) { return document.getElementById( id ); } ).find( Boolean );
		var form = firstTab && firstTab.closest( 'form' );
		if ( ! form || form.hasAttribute( 'data-wcj-settings-presentation' ) || ! ( 'open' in document.createElement( 'details' ) ) || ! window.MutationObserver ) { return; }
		var byId = {};
		form.querySelectorAll( '[id]' ).forEach( function ( node ) {
			if ( ! byId[ node.id ] ) { byId[ node.id ] = []; }
			byId[ node.id ].push( node );
		} );
		function unique( id ) { return byId[ id ] && byId[ id ].length === 1 ? byId[ id ][ 0 ] : null; }
		function nativeLink( tab ) {
			return Array.from( form.querySelectorAll( '.wcj-tab-menu a[data-rel]' ) ).find( function ( link ) { return link.getAttribute( 'data-rel' ) === tab.id; } );
		}
		function describe( id, field, parent ) {
			var input = unique( id );
			var row = input && input.closest( '.wcj-tab-plugins-form-inp' );
			var nativeTab = input && input.closest( '.wcj-tab-box' );
			if ( ! row || ! nativeTab || row.parentElement !== parent || nativeTab.id !== field.tab ) { return null; }
			var expectedName = field.type === 'multiselect' ? id + '[]' : id;
			if ( input.name !== expectedName ) { return null; }
			var controls = Array.from( row.querySelectorAll( 'input,select,textarea,button' ) ).filter( function ( node ) {
				return ! ( node.matches( 'input.select2-search__field' ) && ! node.name && ! node.id );
			} );
			var control = input;
			if ( field.type === 'checkbox' ) {
				control = controls.find( function ( node ) { return node.type === 'checkbox' && node.classList.contains( 'wcj_setting_checkbox_key' ) && node.getAttribute( 'data-rel_id' ) === id; } );
				if ( input.type !== 'hidden' || controls.length !== 2 || ! control ) { return null; }
			} else if ( controls.length !== 1 || ( field.type === 'textarea' ? input.tagName !== 'TEXTAREA' : field.type === 'multiselect' || field.type === 'select' ? input.tagName !== 'SELECT' || input.multiple !== ( field.type === 'multiselect' ) : input.tagName !== 'INPUT' || input.type !== field.type ) ) { return null; }
			return { row: row, input: input, control: control, field: field };
		}
		function value( item ) {
			if ( item.field.type === 'checkbox' ) { return item.control.checked ? 'yes' : 'no'; }
			if ( item.field.type === 'multiselect' ) { return Array.from( item.input.selectedOptions ).map( function ( option ) { return option.value; } ).sort(); }
			return item.input.value;
		}
		function changed( item ) {
			var current = value( item ), baseline = item.field.default;
			if ( item.field.type === 'multiselect' ) {
				if ( Array.isArray( baseline ) && baseline.some( function ( item ) { return item !== null && typeof item === 'object'; } ) ) { return true; }
				baseline = baseline === '' ? [] : Array.isArray( baseline ) ? baseline.map( String ).sort() : [ String( baseline ) ];
				return JSON.stringify( current ) !== JSON.stringify( baseline );
			}
			return typeof baseline === 'object' || current !== String( baseline );
		}
		function invalid( item ) { return item.control.getAttribute( 'aria-invalid' ) === 'true' || ( item.control.willValidate && item.control.validity && ! item.control.validity.valid ); }
		var groups = [];
		config.groups.forEach( function ( group ) {
			var tab = unique( group.tab );
			var parent = tab && tab.querySelector( '.wcj-tab-cnt-main' );
			if ( ! parent || ! tab.classList.contains( 'wcj-tab-box' ) || ! nativeLink( tab ) ) { return; }
			var items = Object.keys( group.fields ).map( function ( id ) { return describe( id, group.fields[ id ], parent ); } );
			if ( ! items.length || items.some( function ( item, i ) { return ! item || ( i > 0 && items[ i - 1 ] && items[ i - 1 ].row.nextElementSibling !== item.row ); } ) ) { return; }
			var controller = group.controller ? describe( group.controller, group.controller_field, parent ) : null;
			if ( group.controller && ! controller ) { return; }
			var details = document.createElement( 'details' );
			details.className = 'wcj-settings-advanced';
			var summary = document.createElement( 'summary' );
			summary.textContent = group.title;
			details.appendChild( summary );
			parent.insertBefore( details, items[ 0 ].row );
			items.forEach( function ( item ) {
				// Some native conditional rows have an empty label or a label targeting the hidden checkbox value.
				var named = Array.from( item.control.labels || [] ).some( function ( label ) { return label.textContent.trim(); } );
				if ( item.field.label && ! item.control.getAttribute( 'aria-label' ) && ( ! named || item.field.type === 'checkbox' ) ) { item.control.setAttribute( 'aria-label', item.field.label ); }
				details.appendChild( item.row );
			} );
			function shouldOpen() { return items.some( function ( item ) { return changed( item ) || invalid( item ); } ) || ( controller && value( controller ) === 'yes' ); }
			details.open = Boolean( shouldOpen() );
			groups.push( { details: details, items: items, controller: controller, shouldOpen: shouldOpen } );
			// Extensions that change an enhanced subtree must never become hidden.
			new window.MutationObserver( function () { details.open = true; } ).observe( details, { childList: true, subtree: true } );
		} );
		form.setAttribute( 'data-wcj-settings-presentation', config.section );
		Object.keys( config.tabs ).forEach( function ( id ) {
			var tab = unique( id ), parent = tab && tab.querySelector( '.wcj-tab-cnt-main' );
			var link = tab && nativeLink( tab );
			if ( ! parent || ! tab.classList.contains( 'wcj-tab-box' ) || ! link ) { return; }
			var heading = document.createElement( 'h2' );
			heading.className = 'wcj-settings-heading'; heading.textContent = link.textContent.trim();
			var help = document.createElement( 'p' );
			help.className = 'wcj-settings-help';
			var label = document.createElement( 'strong' ); label.textContent = config.labels.essentials;
			help.appendChild( label ); help.appendChild( document.createTextNode( ' ' + config.tabs[ id ] ) );
			parent.insertBefore( help, parent.firstChild ); parent.insertBefore( heading, help );
		} );
		if ( config.visible_advanced ) {
			var control = unique( config.visible_advanced.id );
			var row = control && control.closest( '.wcj-tab-plugins-form-inp' );
			if ( row && row.parentElement.classList.contains( 'wcj-tab-cnt-main' ) ) {
				var checkbox = row.querySelector( '.wcj_setting_checkbox_key' );
				if ( checkbox && ! checkbox.getAttribute( 'aria-label' ) ) { checkbox.setAttribute( 'aria-label', config.visible_advanced.title ); }
				var heading = document.createElement( 'h3' ); heading.className = 'wcj-settings-heading'; heading.textContent = config.visible_advanced.title;
				var help = document.createElement( 'p' ); help.className = 'wcj-settings-help'; help.textContent = config.visible_advanced.help;
				row.parentElement.insertBefore( heading, row ); row.parentElement.insertBefore( help, row );
			}
		}
		var menu = form.querySelector( '.wcj-tab-menu' );
		var notice = document.createElement( 'div' ); notice.className = 'wcj-settings-presentation-note';
		var help = document.createElement( 'p' ); help.textContent = config.labels.help; notice.appendChild( help );
		var status = document.createElement( 'p' ); status.setAttribute( 'role', 'status' ); status.className = 'wcj-settings-view-status';
		if ( groups.length ) {
			var all = document.createElement( 'button' ); all.type = 'button'; all.className = 'button wcj-settings-show-all'; all.textContent = config.labels.showAll;
			all.addEventListener( 'click', function () {
				groups.forEach( function ( group ) { group.details.open = true; } );
				form.classList.add( 'wcj-settings-all-visible' );
				status.textContent = config.labels.allShown;
			} );
			notice.appendChild( all ); notice.appendChild( status );
		}
		if ( menu ) { menu.parentElement.insertBefore( notice, menu ); }
		form.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( '.wcj-tab-menu a[data-rel]' ) ) { form.classList.remove( 'wcj-settings-all-visible' ); status.textContent = ''; }
		}, true );
		function reveal( target ) {
			var details = target.closest( 'details' );
			while ( details ) { details.open = true; details = details.parentElement.closest( 'details' ); }
			var tab = target.closest( '.wcj-tab-box' ), link = tab && nativeLink( tab );
			if ( link && ! form.classList.contains( 'wcj-settings-all-visible' ) && ! link.classList.contains( 'active' ) ) { link.click(); }
		}
		function focusVisible( target ) {
			target.focus();
			// Native focus can align a field beneath sticky WordPress/Booster chrome.
			if ( typeof target.scrollIntoView === 'function' ) { target.scrollIntoView( { block: 'center', inline: 'nearest' } ); }
		}
		var pendingFocus = null;
		function cancelFocus() {
			if ( pendingFocus ) { window.clearTimeout( pendingFocus.timer ); pendingFocus = null; }
		}
		function focusSettled( job ) {
			if ( pendingFocus !== job ) { return; }
			var target = job.target;
			if ( ! target.isConnected || target.closest( 'form' ) !== form || target.disabled || ( job.anchor ? window.location.hash !== job.anchor : ! target.willValidate || ! target.validity || target.validity.valid ) ) { cancelFocus(); return; }
			var tab = target.closest( '.wcj-tab-box' );
			var queue = tab && typeof window.jQuery === 'function' ? window.jQuery( tab ) : null;
			var busy = queue && ( queue.is( ':animated' ) || queue.queue( 'fx' ).length );
			var visible = target.getClientRects().length && window.getComputedStyle( target ).visibility === 'visible' && ( ! tab || Number( window.getComputedStyle( tab ).opacity ) === 1 );
			var now = Date.now();
			// A late callback may still focus a settled target within the fixed stale cap.
			if ( ! busy && visible && now <= job.staleDeadline ) { cancelFocus(); focusVisible( target ); return; }
			// A stalled or foreign animation fails safely without retaining a focus job.
			if ( now >= job.deadline || now > job.staleDeadline || --job.polls <= 0 ) { cancelFocus(); return; }
			job.timer = window.setTimeout( function () { focusSettled( job ); }, 25 );
		}
		function queueFocus( target, anchor ) {
			var slow = window.jQuery && window.jQuery.fx && window.jQuery.fx.speeds ? Number( window.jQuery.fx.speeds.slow ) : 600;
			var job = { target: target, anchor: anchor, timer: null };
			pendingFocus = job;
			reveal( job.target );
			// Snapshot only this native tab's existing queue; never extend or mutate it.
			var tab = job.target.closest( '.wcj-tab-box' );
			var queued = tab && typeof window.jQuery === 'function' ? window.jQuery( tab ).queue( 'fx' ).length : 1;
			var budget = Math.min( 2000, ( slow > 0 ? slow : 600 ) * Math.min( 3, Math.max( 1, queued ) ) + 100 );
			job.deadline = Date.now() + budget;
			job.staleDeadline = job.deadline + 200;
			job.polls = Math.ceil( budget / 25 ) + 1;
			// Defer native validation/default fragment scrolling, then await this tab's fx queue.
			job.timer = window.setTimeout( function () { focusSettled( job ); }, 0 );
		}
		form.addEventListener( 'invalid', function ( event ) {
			// Native validation wins over an anchor, then retains the first invalid in its burst.
			if ( pendingFocus && pendingFocus.anchor ) { cancelFocus(); }
			if ( pendingFocus || ! event.target.matches( 'input:not([type="hidden"]),select,textarea' ) ) { return; }
			queueFocus( event.target, '' );
		}, true );
		[ 'pointerdown', 'keydown', 'click' ].forEach( function ( name ) {
			document.addEventListener( name, function ( event ) { if ( event.isTrusted ) { cancelFocus(); } }, true );
		} );
		window.addEventListener( 'hashchange', cancelFocus );
		[ 'input', 'change' ].forEach( function ( name ) {
			form.addEventListener( name, function () { groups.forEach( function ( group ) { if ( group.shouldOpen() ) { group.details.open = true; } } ); } );
		} );
		function revealAnchor() {
			var id;
			try { id = decodeURIComponent( window.location.hash.slice( 1 ) ); } catch ( error ) { return; }
			var target = id && unique( id );
			if ( ! target ) { return; }
			reveal( target );
			if ( target.type === 'hidden' ) {
				var row = target.closest( '.wcj-tab-plugins-form-inp' );
				target = row && row.querySelector( '.wcj_setting_checkbox_key' );
			}
			if ( target && target.matches( 'input:not([type="hidden"]),select,textarea,button,a' ) ) { cancelFocus(); queueFocus( target, window.location.hash ); }
		}
		window.addEventListener( 'hashchange', revealAnchor );
		revealAnchor();
	}
	// The native tab handler is installed by the earlier script's jQuery-ready
	// callback. Use that same queue before an initial field anchor clicks a tab.
	if ( typeof window.jQuery === 'function' && window.jQuery.fn && typeof window.jQuery.fn.ready === 'function' ) {
		window.jQuery( document ).ready( init );
	} else if ( document.readyState === 'loading' ) { document.addEventListener( 'DOMContentLoaded', init ); } else { init(); }
}() );

/** Dependency-free DOM doubles with exact PHP metadata. Not browser/save QA. */
'use strict';
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { execFileSync } = require( 'node:child_process' );
const fixtures = JSON.parse( execFileSync( process.env.PHP_BINARY || 'php', [ path.join( __dirname, 'codex-v10-settings-presentation.php' ), '--fixtures', ...process.argv.slice( 2 ) ], { encoding: 'utf8' } ) );
const code = fs.readFileSync( process.env.CODEX_SETTINGS_PRESENTATION_SOURCE || path.join( __dirname, '../assets/js/admin/wcj-settings-presentation.js' ), 'utf8' );

// Source contract only: browser QA still owns real long-label/RTL geometry.
const css = fs.readFileSync( process.env.CODEX_SETTINGS_PRESENTATION_CSS || path.join( __dirname, '../assets/css/admin/wcj-settings-presentation.css' ), 'utf8' );
const showAllRule = css.match( /^form\[data-wcj-settings-presentation\] \.wcj-settings-show-all \{([^}]+)\}/m );
assert.ok( showAllRule, 'long translated Show all action has a scoped reflow rule' );
for ( const declaration of [ 'max-width: 100%;', 'white-space: normal;', 'overflow-wrap: anywhere;' ] ) {
	assert.ok( showAllRule[ 1 ].includes( declaration ), 'scoped Show all reflow retains ' + declaration );
}
assert.ok( ! /overflow\s*:\s*(hidden|clip)|height\s*:/.test( showAllRule[ 1 ] ), 'reflow does not clip long text or override native automatic height' );

function makeClock() {
	const timers = new Map(); let next = 0;
	return { now: 0, timers, set( callback, delay ) { const id = ++next; timers.set( id, { at: this.now + delay, callback } ); return id; }, clear( id ) { timers.delete( id ); }, advance( delay ) {
		const end = this.now + delay; let count = 0;
		while ( true ) {
			const due = [ ...timers ].filter( ( [ , item ] ) => item.at <= end ).sort( ( a, b ) => a[ 1 ].at - b[ 1 ].at || a[ 0 ] - b[ 0 ] )[ 0 ];
			if ( ! due ) { break; }
			assert.ok( ++count < 1000, 'timer work is bounded' ); this.now = Math.max( this.now, due[ 1 ].at ); timers.delete( due[ 0 ] ); due[ 1 ].callback();
		}
		this.now = end;
	} };
}
class Element {
	constructor( tag, doc ) {
		this.tagName = tag.toUpperCase(); this.doc = doc; this.children = []; this.attributes = {}; this.handlers = {};
		this.id = ''; this.name = ''; this.type = tag === 'input' ? 'text' : ''; this.value = ''; this.textContent = ''; this.className = ''; this.style = {}; this.checked = false; this.disabled = false; this.readOnly = false; this.multiple = false; this.selectedOptions = []; this.willValidate = false; this.validity = { valid: true };
		if ( tag === 'details' ) { this.open = false; }
		this.classList = { contains: c => this.className.split( /\s+/ ).includes( c ), add: c => { if ( ! this.classList.contains( c ) ) { this.className += ' ' + c; } }, remove: c => { this.className = this.className.split( /\s+/ ).filter( v => v !== c ).join( ' ' ); } };
	}
	get parentElement() { return this.parentNode || null; }
	get isConnected() { for ( let node = this; node; node = node.parentElement ) { if ( node === this.doc.body ) { return true; } } return false; }
	getClientRects() {
		if ( ! this.isConnected || this.type === 'hidden' ) { return []; }
		for ( let node = this; node; node = node.parentElement ) {
			if ( node.tagName === 'DETAILS' && ! node.open ) { return []; }
			if ( node.style.display === 'none' && ! ( node.classList.contains( 'wcj-tab-box' ) && this.closest( 'form' )?.classList.contains( 'wcj-settings-all-visible' ) ) ) { return []; }
		}
		return [ {} ];
	}
	get firstChild() { return this.children[ 0 ] || null; }
	get nextElementSibling() { return this.parentNode && this.parentNode.children[ this.parentNode.children.indexOf( this ) + 1 ] || null; }
	appendChild( child ) { return this.insertBefore( child, null ); }
	insertBefore( child, before ) {
		if ( child.parentNode ) { child.parentNode.children.splice( child.parentNode.children.indexOf( child ), 1 ); }
		const at = before ? this.children.indexOf( before ) : this.children.length;
		this.children.splice( at, 0, child ); child.parentNode = this; return child;
	}
	setAttribute( key, value ) { this.attributes[ key ] = value; }
	getAttribute( key ) { return this.attributes[ key ] ?? null; }
	hasAttribute( key ) { return Object.hasOwn( this.attributes, key ); }
	matches( selector ) {
		return selector.split( ',' ).some( option => {
			option = option.trim();
			if ( option.includes( ' ' ) ) { const parts = option.split( /\s+/ ), last = parts.pop(); return this.matches( last ) && Boolean( this.parentElement?.closest( parts.join( ' ' ) ) ); }
			if ( option.includes( ':not' ) ) { return this.tagName === 'INPUT' && this.type !== 'hidden'; }
			const tag = option.match( /^[a-z]+/i );
			if ( tag && this.tagName !== tag[ 0 ].toUpperCase() ) { return false; }
			for ( const c of option.matchAll( /\.([\w-]+)/g ) ) { if ( ! this.classList.contains( c[ 1 ] ) ) { return false; } }
			for ( const a of option.matchAll( /\[([\w-]+)\]/g ) ) { if ( a[ 1 ] === 'id' ? ! this.id : ! this.hasAttribute( a[ 1 ] ) ) { return false; } }
			return true;
		} );
	}
	closest( selector ) { for ( let node = this; node; node = node.parentElement ) { if ( node.matches( selector ) ) { return node; } } return null; }
	querySelectorAll( selector ) {
		const parts = selector.split( /\s+/ ), last = parts.pop();
		const result = [];
		const visit = node => { for ( const child of node.children ) { if ( child.matches( last ) && ( ! parts.length || child.parentElement.closest( parts.join( ' ' ) ) ) ) { result.push( child ); } visit( child ); } };
		visit( this ); return result;
	}
	querySelector( selector ) { return this.querySelectorAll( selector )[ 0 ] || null; }
	addEventListener( name, handler, capture = false ) { ( this.handlers[ name ] ||= [] ).push( { handler, capture } ); }
	emit( name, options = {} ) {
		const chain = []; for ( let node = this; node; node = node.parentElement ) { chain.push( node ); }
		chain.push( this.doc );
		const event = { target: this, isTrusted: false, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; }, ...options };
		for ( const node of chain.toReversed() ) { for ( const listener of node.handlers[ name ] || [] ) { if ( listener.capture ) { listener.handler( event ); } } }
		for ( const node of chain ) { for ( const listener of node.handlers[ name ] || [] ) { if ( ! listener.capture ) { listener.handler( event ); } } }
		return event;
	}
	click() { return this.emit( 'click' ); }
	focus() {
		assert.ok( ! this.closest( 'details' ) || this.closest( 'details' ).open, 'disclosure revealed before focus' );
		const tab = this.closest( '.wcj-tab-box' );
		assert.ok( ! tab || tab.style.display === 'block' || this.closest( 'form' ).classList.contains( 'wcj-settings-all-visible' ), 'native tab revealed before focus' );
		assert.ok( this.getClientRects().length, 'native control is connected and rendered before focus' );
		assert.ok( ! tab || this.doc.computedStyle( tab ).opacity === '1', 'native fade completes before focus' );
		this.doc.focusCalls.push( this ); this.doc.activeElement = this;
	}
}
function build( section, edit = () => {}, hash = '', enhance = true, withConfig = true ) {
	const fixture = fixtures[ section ]; const clock = makeClock();
	const doc = { readyState: 'complete', createElement: tag => new Element( tag, doc ), createTextNode: text => { const node = new Element( 'text', doc ); node.textContent = text; return node; } };
	doc.handlers = {}; doc.addEventListener = ( name, handler, capture = false ) => { ( doc.handlers[ name ] ||= [] ).push( { handler, capture } ); };
	const form = doc.createElement( 'form' ); doc.body = doc.createElement( 'body' ); doc.body.appendChild( form ); doc.activeElement = doc.body; doc.focusCalls = [];
	doc.computedStyle = node => {
		let visibility = 'visible'; for ( let parent = node; parent; parent = parent.parentElement ) { if ( parent.style.visibility === 'hidden' ) { visibility = 'hidden'; } }
		return { visibility, opacity: clock.now < ( node.animationUntil || 0 ) ? '0.5' : '1' };
	};
	doc.getElementById = id => doc.body.querySelectorAll( '[id]' ).find( node => node.id === id ) || null;
	const menu = doc.createElement( 'div' ); menu.className = 'wcj-tab-menu'; form.appendChild( menu );
	const active = doc.createElement( 'input' ); active.name = 'wcj_setting_active_tab'; active.type = 'hidden'; form.appendChild( active );
	const tabs = {}, controls = {}, rows = {}, links = {};
	for ( const id of fixture.settings.filter( field => field.type === 'tab_start' ).map( field => field.id ) ) {
		const tab = doc.createElement( 'div' ); tab.id = id; tab.className = 'wcj-tab-box'; tab.style.display = Object.keys( tabs ).length ? 'none' : 'block';
		const inner = doc.createElement( 'div' ); inner.className = 'wcj-tab-cnt-main'; tab.appendChild( inner ); form.appendChild( tab ); tabs[ id ] = inner;
		const link = doc.createElement( 'a' ); link.textContent = id; link.setAttribute( 'data-rel', id ); link.className = active.value ? '' : 'active'; menu.appendChild( link ); links[ id ] = link;
		link.addEventListener( 'click', () => { for ( const other of Object.values( links ) ) { other.classList.remove( 'active' ); } link.classList.add( 'active' ); for ( const other of Object.values( tabs ) ) { other.parentElement.style.display = 'none'; } tab.style.display = 'block'; tab.animationUntil = clock.now + ( state.animationDelay || 0 ); tab.queueUntil = clock.now + ( state.queueDelay || state.animationDelay || 0 ); active.value = id; } );
		if ( ! active.value ) { active.value = id; }
	}
	let tab;
	for ( const field of fixture.settings ) {
		if ( field.type === 'tab_start' ) { tab = tabs[ field.id ]; }
		if ( field.type === 'tab_end' ) { tab = null; }
		if ( ! tab || ! [ 'text', 'textarea', 'select', 'multiselect', 'checkbox', 'number', 'custom_number' ].includes( field.type ) ) { continue; }
		const row = doc.createElement( 'div' ); row.className = 'wcj-tab-plugins-form-inp'; tab.appendChild( row ); rows[ field.id ] = row;
		const input = doc.createElement( field.type === 'textarea' ? 'textarea' : [ 'select', 'multiselect' ].includes( field.type ) ? 'select' : 'input' );
		input.id = field.id; input.name = field.id + ( field.type === 'multiselect' ? '[]' : '' ); input.type = field.type === 'checkbox' ? 'hidden' : field.type === 'custom_number' ? 'number' : field.type;
		input.value = String( field.default ?? '' ); input.multiple = field.type === 'multiselect'; input.readOnly = Boolean( field.custom_attributes?.readonly ); input.disabled = Boolean( field.custom_attributes?.disabled );
		row.appendChild( input ); controls[ field.id ] = input;
		if ( field.type === 'checkbox' ) {
			const checkbox = doc.createElement( 'input' ); checkbox.type = 'checkbox'; checkbox.checked = input.value === 'yes'; checkbox.disabled = input.disabled; checkbox.className = 'wcj_setting_checkbox_key'; checkbox.setAttribute( 'data-rel_id', field.id ); row.appendChild( checkbox ); input.checkbox = checkbox;
			checkbox.addEventListener( 'change', () => { input.value = checkbox.checked ? 'yes' : 'no'; } );
		}
	}
	const observers = [];
	const window = { location: { hash }, handlers: {}, addEventListener( name, handler ) { ( this.handlers[ name ] ||= [] ).push( handler ); }, dispatch( name ) { for ( const handler of this.handlers[ name ] || [] ) { handler(); } }, getComputedStyle: doc.computedStyle, setTimeout: ( callback, delay ) => clock.set( callback, delay ), clearTimeout: id => clock.clear( id ), MutationObserver: class { constructor( callback ) { observers.push( callback ); } observe() {} } };
	const queueReads = [];
	window.jQuery = tab => { queueReads.push( tab ); return { is: selector => { assert.equal( selector, ':animated' ); return clock.now < ( tab.animationUntil || 0 ); }, queue: name => { assert.equal( name, 'fx' ); return tab.stalled || clock.now < ( tab.queueUntil || 0 ) ? Array( tab.queueSlots || 1 ).fill( 'inprogress' ) : []; } }; };
	window.jQuery.fx = { speeds: { slow: 600 } };
	const state = { form, doc, controls, rows, tabs, links, active, observers, window, clock, queueReads };
	edit( state );
	state.originals = form.querySelectorAll( 'input,select,textarea' ).filter( item => item.name && item !== active );
	state.values = () => state.originals.map( node => [ node.id, node.name, node.value, node.checked, node.disabled, node.readOnly, node.multiple, JSON.stringify( node.selectedOptions ) ] );
	const before = state.values();
	const beforeActive = active.value;
	if ( enhance ) { vm.runInNewContext( code, { document: doc, window, Date: { now: () => clock.now }, ...( withConfig ? { wcjSettingsPresentation: fixture.config } : {} ) } ); }
	for ( const node of state.originals ) { assert.ok( node.closest( 'form' ) === form, 'every original node retains its original form' ); }
	if ( ! hash ) { assert.equal( active.value, beforeActive, 'enhancement does not change native active-tab value' ); }
	assert.deepEqual( state.values(), before, 'enhancement preserves every named native node, value, state and option selection' );
	state.groups = form.querySelectorAll( 'details' );
	return state;
}
for ( const [ section, expected ] of Object.entries( { add_to_cart: 5, more_button_labels: 0, price_labels: 3, related_products: 3 } ) ) {
	const state = build( section );
	assert.equal( state.groups.length, expected, section + ': exact bounded group count' );
	assert.ok( state.groups.every( group => ! group.open ), 'inactive/default groups initially collapsed' );
	assert.equal( state.form.querySelectorAll( '.wcj-settings-help' ).length, Object.keys( fixtures[ section ].config.tabs ).length + ( section === 'more_button_labels' ? 1 : 0 ) );
	assert.equal( build( section, () => {}, '', false ).groups.length, 0, 'no JavaScript remains native' );
	assert.equal( build( section, () => {}, '', true, false ).groups.length, 0, 'missing metadata remains native' );
}
const conditional = 'wcj_add_to_cart_text_on_single_sale_simple';
const unsupportedTab = build( 'add_to_cart', s => { s.tabs.add_to_cart_per_product_type_tab.parentElement.className = 'third-party-tab'; } );
assert.equal( unsupportedTab.groups.length, 0, 'missing native tab class fails open without an exception or partial grouping' );
for ( const [ id, row ] of Object.entries( unsupportedTab.rows ) ) { assert.ok( ! row.closest( 'details' ), id + ': unsupported tab rows are not reparented' ); }
const active = build( 'add_to_cart', s => { s.controls.wcj_add_to_cart_text_enabled.checkbox.checked = true; s.controls.wcj_add_to_cart_text_enabled.value = 'yes'; } );
assert.ok( active.groups.every( group => group.open ), 'enabled consequential defaults stay visible' );
assert.ok( ! active.controls.wcj_add_to_cart_per_category_enabled.closest( 'details' ), 'repeated category editor remains native' );
const blank = build( 'add_to_cart', s => { s.controls[ conditional ].value = ''; } );
assert.ok( blank.controls[ conditional ].closest( 'details' ).open, 'deliberately empty nondefault value stays visible' );
assert.equal( blank.controls[ conditional ].getAttribute( 'aria-label' ), 'Products on sale. Single product view.', 'known unlabeled conditional fields receive their existing translated native description as an accessible name' );
const html = build( 'price_labels', s => { s.controls.wcj_global_price_labels_replace_text.value = '<em>Price</em>'; } );
assert.ok( html.controls.wcj_global_price_labels_replace_text.closest( 'details' ).open, 'active HTML replacement stays visible' );
const selection = build( 'price_labels', s => { s.controls.wcj_global_price_labels_products_incl.selectedOptions = [ { value: '10' } ]; } );
assert.ok( selection.controls.wcj_global_price_labels_products_incl.closest( 'details' ).open, 'active filters stay visible' );
const related = build( 'related_products', s => { for ( const id of [ 'wcj_product_info_related_products_by_attribute_enabled', 'wcj_product_info_related_products_per_product', 'wcj_product_info_related_products_hide' ] ) { s.controls[ id ].checkbox.checked = true; s.controls[ id ].value = 'yes'; } } );
assert.ok( related.groups.every( group => group.open ), 'enabled matching/manual/hide options stay visible' );
assert.ok( ! related.controls.wcj_product_info_related_products_relate_by_category.closest( 'details' ), 'default category relationship remains visible' );
const nativeCheckbox = related.controls.wcj_product_info_related_products_per_product;
nativeCheckbox.checkbox.checked = false; nativeCheckbox.checkbox.emit( 'change' );
assert.equal( nativeCheckbox.value, 'no', 'existing hidden-checkbox synchronization still works on the original node' );
const invalid = build( 'add_to_cart' );
const initiallyInvalid = build( 'add_to_cart', s => { s.controls[ conditional ].willValidate = true; s.controls[ conditional ].validity.valid = false; } );
assert.ok( initiallyInvalid.controls[ conditional ].closest( 'details' ).open, 'existing invalid controls are initially revealed' );
invalid.controls[ conditional ].willValidate = true; invalid.controls[ conditional ].validity.valid = false;
const invalidEvent = invalid.controls[ conditional ].emit( 'invalid' );
assert.equal( invalidEvent.defaultPrevented, false, 'native invalid event is never suppressed' );
assert.ok( invalid.doc.activeElement === invalid.doc.body, 'focus waits until the native invalid event burst has finished' );
invalid.clock.advance( 0 );
assert.ok( invalid.doc.activeElement === invalid.controls[ conditional ], 'invalid event automatically focuses the revealed native control, without manual focus in the test' );
assert.equal( invalid.clock.timers.size, 0, 'success releases the timer/job' );
assert.ok( invalid.controls[ conditional ].closest( 'details' ).open );
assert.equal( invalid.active.value, 'add_to_cart_per_product_type_tab', 'invalid reveal reuses native active-tab contract' );
const anchored = build( 'add_to_cart', () => {}, '#' + conditional );
assert.ok( anchored.doc.activeElement === anchored.controls[ conditional ], 'supported anchor reveals and focuses existing field' );
const checkboxAnchor = build( 'related_products', () => {}, '#wcj_product_info_related_products_by_attribute_enabled' );
assert.ok( checkboxAnchor.doc.activeElement === checkboxAnchor.controls.wcj_product_info_related_products_by_attribute_enabled.checkbox, 'hidden-value anchor focuses its visible native checkbox' );
for ( const kind of [ 'duplicate', 'injected-row', 'injected-control' ] ) {
	const state = build( 'add_to_cart', s => {
		const foreign = s.doc.createElement( kind === 'injected-row' ? 'div' : 'input' );
		if ( kind === 'duplicate' ) { foreign.id = conditional; s.form.appendChild( foreign ); }
		else if ( kind === 'injected-row' ) { s.rows[ conditional ].parentElement.insertBefore( foreign, s.rows[ conditional ] ); }
		else { s.rows[ conditional ].appendChild( foreign ); }
	} );
	assert.equal( state.groups.length, 4, kind + ': affected group stays native' );
	assert.ok( ! state.controls[ conditional ].closest( 'details' ), 'unsupported/extension shape never hidden' );
}
const all = build( 'price_labels' );
all.controls.wcj_global_price_labels_add_after_text.value = 'Unsaved <strong>tax note</strong>';
const beforeAll = all.values(), beforeActive = all.active.value; all.form.querySelector( '.wcj-settings-show-all' ).click();
assert.ok( all.groups.every( group => group.open ) && all.form.classList.contains( 'wcj-settings-all-visible' ), 'Show all reveals all groups and native tabs in place' );
assert.deepEqual( all.values(), beforeAll, 'Show all retains unsaved controls/values and native active-tab state' );
assert.equal( all.active.value, beforeActive );
all.links.price_labels_per_product_tab.click();
assert.ok( ! all.form.classList.contains( 'wcj-settings-all-visible' ), 'native tab navigation remains usable after Show all' );
all.groups[ 0 ].open = false; all.observers[ 0 ](); assert.ok( all.groups[ 0 ].open, 'later extension subtree changes fail open' );
// Native validation can fail while its tab is hidden. These model the existing
// fadeIn('slow') queue without mutating animation, validation or form values.
const numeric = 'wcj_product_info_related_products_num';
const general = 'wcj_related_product_genral_options_tab';
const relate = 'wcj_related_product_relate_tab';
const order = 'wcj_related_product_order_tab';
function invalidField( state, id = numeric ) { const field = state.controls[ id ]; field.willValidate = true; field.validity.valid = false; return field; }
function hiddenInvalid() {
	const state = build( 'related_products' ); state.links[ relate ].click(); state.animationDelay = 600;
	const field = invalidField( state ); field.value = '1.5'; return { state, field };
}
function clean( state, message ) { assert.equal( state.clock.timers.size, 0, message + ': no pending timer' ); }
const currentTab = build( 'related_products' ); const currentField = invalidField( currentTab );
currentField.emit( 'invalid' ); currentTab.clock.advance( 0 );
assert.ok( currentTab.doc.activeElement === currentField, 'current visible tab invalid control receives deferred focus' ); clean( currentTab, 'current tab success' );
const animated = hiddenInvalid(); const valuesBeforeInvalid = animated.state.values();
animated.field.emit( 'invalid' );
assert.equal( animated.state.active.value, general, 'hidden General is revealed by the unchanged native tab click' );
animated.state.clock.advance( 599 ); assert.ok( animated.state.doc.activeElement === animated.state.doc.body, 'no focus during native slow fade' );
animated.state.clock.advance( 1 ); assert.ok( animated.state.doc.activeElement === animated.field, 'focus follows settled native slow fade' );
assert.deepEqual( animated.state.values(), valuesBeforeInvalid, 'invalid reveal/focus does not write any native setting or normalize invalid 1.5' );
assert.ok( animated.state.queueReads.every( tab => tab.id === general ), 'only the target native tab animation/queue is inspected' ); clean( animated.state, 'animated success' );
const queued = hiddenInvalid(); queued.state.animationDelay = 100; queued.state.queueDelay = 650;
queued.field.emit( 'invalid' ); queued.state.clock.advance( 625 ); assert.ok( queued.state.doc.activeElement === queued.state.doc.body, 'an idle animation does not bypass an existing fx queue' );
queued.state.clock.advance( 25 ); assert.ok( queued.state.doc.activeElement === queued.field, 'focus waits for the existing fx queue to settle' ); clean( queued.state, 'queued success' );
const multiple = hiddenInvalid();
const later = invalidField( multiple.state, 'wcj_product_info_related_products_orderby' );
multiple.field.emit( 'invalid' ); later.emit( 'invalid' );
assert.equal( multiple.state.active.value, general, 'later invalid events in the burst do not switch away from the first invalid tab' );
multiple.state.clock.advance( 600 ); assert.ok( multiple.state.doc.activeElement === multiple.field, 'first invalid field wins across native tabs' );
assert.ok( multiple.state.doc.focusCalls.length === 1 && multiple.state.doc.focusCalls[ 0 ] === multiple.field, 'multiple invalid fields produce exactly one focus' ); clean( multiple.state, 'multiple invalid success' );
// These same-tab pairs model rapid native navigation with active and queued
// slow fades. Native validation still emits both invalid events synchronously.
for ( const slots of [ 1, 2, 3 ] ) {
	const pending = hiddenInvalid(); const second = invalidField( pending.state, 'wcj_product_info_related_products_columns' ); second.value = '1.5';
	pending.state.tabs[ general ].parentElement.queueSlots = slots; pending.state.queueDelay = slots * 600;
	const before = pending.state.values();
	assert.equal( pending.field.emit( 'invalid' ).defaultPrevented, false );
	assert.equal( second.emit( 'invalid' ).defaultPrevented, false );
	assert.equal( pending.state.active.value, general, 'same-tab invalid pair reveals General without switching to a later field' );
	pending.state.clock.advance( slots * 600 - 1 );
	assert.ok( pending.state.doc.activeElement === pending.state.doc.body, slots + ' native fade(s): no premature focus' );
	pending.state.clock.advance( 1 );
	assert.ok( pending.state.doc.activeElement === pending.field, slots + ' native fade(s): same-tab invalid pair focuses the first field' );
	assert.deepEqual( pending.state.doc.focusCalls, [ pending.field ], 'exactly the first invalid receives focus' );
	assert.deepEqual( pending.state.values(), before, 'queued reveal/focus preserves both invalid values and all native controls' );
	assert.ok( pending.state.queueReads.every( tab => tab.id === general ), 'queued work inspects only the first target tab' ); clean( pending.state, 'same-tab queued pair' );
}
for ( const late of [ 750, 900 ] ) {
	const pending = hiddenInvalid(); pending.field.emit( 'invalid' );
	// Simulate a delayed event-loop callback after the soft 700ms deadline.
	pending.state.clock.now = late; pending.state.clock.advance( 0 );
	assert.ok( pending.state.doc.activeElement === pending.field, 'a settled target may focus at ' + late + 'ms within its fixed 200ms late-callback allowance' );
	assert.deepEqual( pending.state.doc.focusCalls, [ pending.field ] ); clean( pending.state, 'late settled success' );
}
for ( const scenario of [ 'late-stale', 'queued-stalled', 'queue-over-cap', 'budget-not-extended', 'max-budget-stalled', 'max-hard-cap' ] ) {
	const pending = hiddenInvalid(); const tab = pending.state.tabs[ general ].parentElement;
	if ( scenario === 'queued-stalled' ) { tab.queueSlots = 3; tab.stalled = true; }
	if ( scenario === 'queue-over-cap' ) { tab.queueSlots = 4; pending.state.queueDelay = 2500; }
	if ( scenario === 'budget-not-extended' ) { pending.state.queueDelay = 1200; }
	if ( scenario === 'max-hard-cap' || scenario === 'max-budget-stalled' ) { pending.state.window.jQuery.fx.speeds.slow = 1000; tab.queueSlots = 3; pending.state.queueDelay = 2000; }
	if ( scenario === 'max-budget-stalled' ) { tab.stalled = true; }
	pending.field.emit( 'invalid' );
	if ( scenario === 'budget-not-extended' ) { pending.state.clock.advance( 100 ); tab.queueSlots = 3; }
	if ( scenario === 'late-stale' ) { pending.state.clock.now = 901; }
	if ( scenario === 'max-hard-cap' ) { pending.state.clock.now = 2201; }
	pending.state.clock.advance( scenario === 'late-stale' || scenario === 'max-hard-cap' ? 0 : scenario === 'budget-not-extended' ? 600 : scenario === 'max-budget-stalled' ? 2000 : 1900 );
	assert.ok( pending.state.doc.activeElement === pending.state.doc.body, scenario + ': safe no-focus fallback, not success' );
	assert.ok( pending.state.queueReads.length <= 82, scenario + ': fixed maximum of 81 polls plus one queue snapshot' ); clean( pending.state, scenario + ' bounded completion' );
	tab.stalled = false; pending.state.clock.advance( 3000 );
	assert.ok( pending.state.doc.activeElement === pending.state.doc.body, scenario + ': no stale focus' ); clean( pending.state, scenario + ' no late timer' );
}
// The longer queue allowance must retain every user-cancellation and target-
// eligibility guard, rather than stealing focus when native fades finish later.
for ( const terminal of [ 'pointerdown', 'keydown', 'click', 'hashchange', 'corrected', 'disabled', 'removed', 'foreign-form', 'not-validatable' ] ) {
	const pending = hiddenInvalid(); pending.state.tabs[ general ].parentElement.queueSlots = 2; pending.state.queueDelay = 1200;
	pending.field.emit( 'invalid' ); pending.state.clock.advance( 100 );
	if ( terminal === 'hashchange' ) { pending.state.window.dispatch( terminal ); }
	else if ( [ 'pointerdown', 'keydown', 'click' ].includes( terminal ) ) { pending.state.doc.body.emit( terminal, { isTrusted: true } ); }
	else if ( terminal === 'corrected' ) { pending.field.validity.valid = true; }
	else if ( terminal === 'disabled' ) { pending.field.disabled = true; }
	else if ( terminal === 'not-validatable' ) { pending.field.willValidate = false; }
	else if ( terminal === 'removed' ) { const parent = pending.field.parentElement; parent.children.splice( parent.children.indexOf( pending.field ), 1 ); pending.field.parentNode = null; }
	else { const other = pending.state.doc.createElement( 'form' ); pending.state.doc.body.appendChild( other ); other.appendChild( pending.field ); }
	pending.state.clock.advance( 25 ); clean( pending.state, 'queued ' + terminal ); pending.state.clock.advance( 3000 );
	assert.ok( pending.state.doc.activeElement === pending.state.doc.body, 'queued ' + terminal + ': no later focus' ); clean( pending.state, 'queued ' + terminal + ' settled' );
}
for ( const action of [ 'pointerdown', 'keydown', 'click', 'hashchange' ] ) {
	const pending = hiddenInvalid(); pending.field.emit( 'invalid' ); pending.state.clock.advance( 100 );
	if ( action === 'hashchange' ) { pending.state.window.dispatch( action ); } else { pending.state.links[ order ].emit( action, { isTrusted: true } ); }
	pending.state.links[ order ].click(); const chosen = pending.state.controls.wcj_product_info_related_products_orderby;
	clean( pending.state, action + ' cancellation' ); pending.state.clock.advance( 600 ); chosen.focus(); pending.state.clock.advance( 2000 );
	assert.ok( pending.state.doc.activeElement === chosen, action + ': later native animation cannot steal manual-navigation focus' ); clean( pending.state, action + ' settled' );
	// Cancellation releases the latch as well as the timer, so a new validation
	// attempt can focus a different still-invalid control normally.
	const retry = invalidField( pending.state, 'wcj_product_info_related_products_orderby' );
	retry.emit( 'invalid' ); pending.state.clock.advance( 600 ); assert.ok( pending.state.doc.activeElement === retry, action + ': a later validation burst is not blocked' ); clean( pending.state, action + ' retry' );
}
for ( const terminal of [ 'corrected', 'disabled', 'removed', 'foreign-form' ] ) {
	const pending = hiddenInvalid(); pending.field.emit( 'invalid' ); pending.state.clock.advance( 100 );
	if ( terminal === 'corrected' ) { pending.field.validity.valid = true; }
	if ( terminal === 'disabled' ) { pending.field.disabled = true; }
	if ( terminal === 'removed' ) { const parent = pending.field.parentElement; parent.children.splice( parent.children.indexOf( pending.field ), 1 ); pending.field.parentNode = null; }
	if ( terminal === 'foreign-form' ) { const other = pending.state.doc.createElement( 'form' ); pending.state.doc.body.appendChild( other ); other.appendChild( pending.field ); }
	pending.state.clock.advance( 25 ); clean( pending.state, terminal ); pending.state.clock.advance( 2000 );
	assert.ok( pending.state.doc.activeElement === pending.state.doc.body, terminal + ': no deferred focus' ); clean( pending.state, terminal + ' settled' );
}
for ( const terminal of [ 'stalled-queue', 'hidden-control', 'invisible-control' ] ) {
	const pending = hiddenInvalid();
	if ( terminal === 'stalled-queue' ) { pending.state.tabs[ general ].parentElement.stalled = true; }
	if ( terminal === 'hidden-control' ) { pending.field.style.display = 'none'; }
	if ( terminal === 'invisible-control' ) { pending.field.style.visibility = 'hidden'; }
	pending.field.emit( 'invalid' ); pending.state.clock.advance( 700 );
	assert.ok( pending.state.doc.activeElement === pending.state.doc.body, terminal + ': bounded safe no-focus fallback, not success' ); clean( pending.state, terminal + ' deadline' );
	pending.state.tabs[ general ].parentElement.stalled = false; pending.field.style.display = 'block'; pending.field.style.visibility = 'visible'; pending.state.clock.advance( 2000 );
	assert.ok( pending.state.doc.activeElement === pending.state.doc.body, terminal + ': no late focus after timeout' ); clean( pending.state, terminal + ' settled' );
}
const noJQuery = hiddenInvalid(); delete noJQuery.state.window.jQuery;
noJQuery.field.emit( 'invalid' ); noJQuery.state.clock.advance( 599 ); assert.ok( noJQuery.state.doc.activeElement === noJQuery.state.doc.body, 'no-jQuery fallback still observes native visibility/opacity' );
noJQuery.state.clock.advance( 1 ); assert.ok( noJQuery.state.doc.activeElement === noJQuery.field, 'visible no-jQuery fallback focuses without a new dependency' ); clean( noJQuery.state, 'no-jQuery success' );
const delayed = hiddenInvalid(); delayed.field.emit( 'invalid' );
delayed.state.clock.now = 2000; delayed.state.clock.advance( 0 );
assert.ok( delayed.state.doc.activeElement === delayed.state.doc.body, 'a background-delayed timer cannot focus after its deadline' ); clean( delayed.state, 'late timer deadline' );
console.log( 'PASS: bounded invalid-focus/current-tab/native-fade/queue/first-invalid/cancellation/terminal-path fixtures; no real browser claim.' );
console.log( 'PASS: four-area metadata/controller DOM-double fixtures; no real browser, server save or commerce-output claim.' );

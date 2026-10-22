/** Dependency-free DOM doubles with exact PHP metadata. Not browser/save QA. */
'use strict';
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { execFileSync } = require( 'node:child_process' );
const fixtures = JSON.parse( execFileSync( process.env.PHP_BINARY || 'php', [ path.join( __dirname, 'codex-v10-settings-presentation.php' ), '--fixtures', ...process.argv.slice( 2 ) ], { encoding: 'utf8' } ) );
const code = fs.readFileSync( path.join( __dirname, '../assets/js/admin/wcj-settings-presentation.js' ), 'utf8' );

class Element {
	constructor( tag, doc ) {
		this.tagName = tag.toUpperCase(); this.doc = doc; this.children = []; this.attributes = {}; this.handlers = {};
		this.id = ''; this.name = ''; this.type = tag === 'input' ? 'text' : ''; this.value = ''; this.textContent = ''; this.className = ''; this.style = {}; this.checked = false; this.disabled = false; this.readOnly = false; this.multiple = false; this.selectedOptions = []; this.willValidate = false; this.validity = { valid: true };
		if ( tag === 'details' ) { this.open = false; }
		this.classList = { contains: c => this.className.split( /\s+/ ).includes( c ), add: c => { if ( ! this.classList.contains( c ) ) { this.className += ' ' + c; } }, remove: c => { this.className = this.className.split( /\s+/ ).filter( v => v !== c ).join( ' ' ); } };
	}
	get parentElement() { return this.parentNode || null; }
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
	emit( name ) {
		const chain = []; for ( let node = this; node; node = node.parentElement ) { chain.push( node ); }
		const event = { target: this, preventDefault() {} };
		for ( const node of chain.toReversed() ) { for ( const listener of node.handlers[ name ] || [] ) { if ( listener.capture ) { listener.handler( event ); } } }
		for ( const node of chain ) { for ( const listener of node.handlers[ name ] || [] ) { if ( ! listener.capture ) { listener.handler( event ); } } }
	}
	click() { this.emit( 'click' ); }
	focus() {
		assert.ok( ! this.closest( 'details' ) || this.closest( 'details' ).open, 'disclosure revealed before focus' );
		const tab = this.closest( '.wcj-tab-box' );
		assert.ok( ! tab || tab.style.display === 'block' || this.closest( 'form' ).classList.contains( 'wcj-settings-all-visible' ), 'native tab revealed before focus' );
		this.doc.activeElement = this;
	}
}
function build( section, edit = () => {}, hash = '', enhance = true, withConfig = true ) {
	const fixture = fixtures[ section ];
	const doc = { readyState: 'complete', createElement: tag => new Element( tag, doc ), createTextNode: text => { const node = new Element( 'text', doc ); node.textContent = text; return node; } };
	const form = doc.createElement( 'form' ); doc.body = doc.createElement( 'body' ); doc.body.appendChild( form );
	doc.getElementById = id => doc.body.querySelectorAll( '[id]' ).find( node => node.id === id ) || null;
	const menu = doc.createElement( 'div' ); menu.className = 'wcj-tab-menu'; form.appendChild( menu );
	const active = doc.createElement( 'input' ); active.name = 'wcj_setting_active_tab'; active.type = 'hidden'; form.appendChild( active );
	const tabs = {}, controls = {}, rows = {}, links = {};
	for ( const [ id, help ] of Object.entries( fixture.config.tabs ) ) {
		const tab = doc.createElement( 'div' ); tab.id = id; tab.className = 'wcj-tab-box'; tab.style.display = Object.keys( tabs ).length ? 'none' : 'block';
		const inner = doc.createElement( 'div' ); inner.className = 'wcj-tab-cnt-main'; tab.appendChild( inner ); form.appendChild( tab ); tabs[ id ] = inner;
		const link = doc.createElement( 'a' ); link.textContent = id; link.setAttribute( 'data-rel', id ); link.className = active.value ? '' : 'active'; menu.appendChild( link ); links[ id ] = link;
		link.addEventListener( 'click', () => { for ( const other of Object.values( links ) ) { other.classList.remove( 'active' ); } link.classList.add( 'active' ); for ( const other of Object.values( tabs ) ) { other.parentElement.style.display = 'none'; } tab.style.display = 'block'; active.value = id; } );
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
	const window = { location: { hash }, addEventListener() {}, MutationObserver: class { constructor( callback ) { observers.push( callback ); } observe() {} } };
	const state = { form, doc, controls, rows, tabs, links, active, observers };
	edit( state );
	state.originals = form.querySelectorAll( 'input,select,textarea' ).filter( item => item.name && item !== active );
	state.values = () => state.originals.map( node => [ node.id, node.name, node.value, node.checked, node.disabled, node.readOnly, node.multiple, JSON.stringify( node.selectedOptions ) ] );
	const before = state.values();
	const beforeActive = active.value;
	if ( enhance ) { vm.runInNewContext( code, { document: doc, window, ...( withConfig ? { wcjSettingsPresentation: fixture.config } : {} ) } ); }
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
invalid.controls[ conditional ].emit( 'invalid' ); invalid.controls[ conditional ].focus();
assert.ok( invalid.controls[ conditional ].closest( 'details' ).open );
assert.equal( invalid.active.value, 'add_to_cart_per_product_type_tab', 'invalid reveal reuses native active-tab contract' );
const anchored = build( 'add_to_cart', () => {}, '#' + conditional );
assert.equal( anchored.doc.activeElement, anchored.controls[ conditional ], 'supported anchor reveals and focuses existing field' );
const checkboxAnchor = build( 'related_products', () => {}, '#wcj_product_info_related_products_by_attribute_enabled' );
assert.equal( checkboxAnchor.doc.activeElement, checkboxAnchor.controls.wcj_product_info_related_products_by_attribute_enabled.checkbox, 'hidden-value anchor focuses its visible native checkbox' );
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
console.log( 'PASS: four-area metadata/controller DOM-double fixtures; no real browser, server save or commerce-output claim.' );

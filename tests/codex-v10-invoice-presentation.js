/** Deterministic controller fixture. DOM doubles are not real-browser or server QA. */
'use strict';
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );

const elements = new Map();
let focused = null;
function element( id, tag = 'div' ) {
	const handlers = {};
	const classes = new Set();
	const item = {
		id, tag, value: '', checked: false, disabled: false, hidden: false, textContent: '', children: [], attributes: {},
		classList: { toggle( name, enabled ) { if ( enabled ) { classes.add( name ); } else { classes.delete( name ); } }, contains( name ) { return classes.has( name ); } },
		addEventListener( name, callback ) { handlers[ name ] = callback; },
		async emit( name, target = item ) { if ( handlers[ name ] ) { await handlers[ name ]( { target, preventDefault() {} } ); } },
		setAttribute( name, value ) { item.attributes[ name ] = value; },
		getAttribute( name ) { return item.attributes[ name ]; },
		removeAttribute( name ) { delete item.attributes[ name ]; delete item[ name ]; },
		focus() { focused = item; },
		closest() { return null; },
		appendChild( child ) { item.children.push( child ); },
		replaceChildren() { item.children = []; },
	};
	elements.set( id, item );
	return item;
}
const root = element( 'wcj-invoice-setup' );
const form = element( 'wcj-invoice-setup-form', 'form' );
const status = element( 'wcj-setup-status' );
const panel = element( 'wcj-setup-review' );
panel.hidden = true;
const confirm = element( 'wcj-setup-confirm', 'input' );
const heading = element( 'wcj-setup-review-heading' );
const table = element( 'summary-body' );
const actions = {};
for ( const op of [ 'sample', 'rehearse', 'review', 'activate', 'undo' ] ) {
	actions[ op ] = element( op, 'button' );
	actions[ op ].attributes[ 'data-setup-action' ] = op;
	actions[ op ].closest = () => actions[ op ];
}
actions.activate.disabled = true;
const chooseLogo = element( 'wcj-setup-choose-logo', 'button' );
element( 'wcj-setup-remove-logo', 'button' );
element( 'wcj-setup-logo-name' );
element( 'wcj-setup-changes' );
for ( const id of [ 'open', 'download' ] ) { element( 'wcj-setup-' + id, 'a' ).hidden = true; }
const fields = [];
for ( const [ key, value ] of Object.entries( { business_name: 'Fixture shop', business_address: '12 Example Street', business_reference: '', logo_attachment_id: '0', accent_color: '#0073aa', footer_note: '', document_type: 'invoice', operation: 'manual', email: '' } ) ) {
	const item = element( key === 'logo_attachment_id' ? 'wcj-setup-logo' : 'wcj-setup-' + key, 'input' );
	item.name = 'draft[' + key + ']'; item.value = value; fields.push( item );
}
form.querySelectorAll = () => [ ...fields, confirm ];
form.reportValidity = () => true;
root.querySelector = () => actions.activate;
root.querySelectorAll = selector => selector === 'button' ? [ ...Object.values( actions ), chooseLogo, elements.get( 'wcj-setup-remove-logo' ) ] : [ ...Object.values( actions ), ...fields, confirm, chooseLogo, elements.get( 'wcj-setup-remove-logo' ) ];

const requests = [];
let response = {};
let ready;
let revoked = 0;
const strings = { url: '/admin-ajax.php', nonces: {}, working: 'Working', failed: 'Failed', changed: 'Draft changed', mediaUnavailable: 'Media unavailable', noLogo: 'No logo', logoSelected: 'Selected', noChanges: 'No changes' };
for ( const op of Object.keys( actions ) ) { strings.nonces[ op ] = 'nonce-' + op; }
vm.runInNewContext( fs.readFileSync( path.join( __dirname, '../assets/js/admin/wcj-invoice-setup.js' ), 'utf8' ), {
	document: { getElementById: id => elements.get( id ), addEventListener: ( name, cb ) => { ready = cb; }, querySelector: () => table, createElement: tag => element( 'generated-' + elements.size, tag ) },
	wcjInvoiceSetup: strings, window: {}, URL: { createObjectURL: () => 'blob:fixture', revokeObjectURL: () => { revoked++; } }, Blob, Uint8Array,
	atob: value => Buffer.from( value, 'base64' ).toString( 'binary' ),
	FormData: class { constructor() { this.values = Object.fromEntries( fields.filter( item => ! item.disabled ).map( item => [ item.name, item.value ] ) ); } append( key, value ) { this.values[ key ] = value; } },
	fetch: async ( url, options ) => { requests.push( { url, options } ); return { json: async () => response }; },
} );

async function click( op, data ) {
	response = { success: true, data: data || {} };
	await root.emit( 'click', actions[ op ] );
}
( async () => {
	ready();
	assert.equal( requests.length, 0, 'opening the controller does not fetch or save' );
	await click( 'activate' );
	assert.equal( requests.length, 0, 'activation guard rejects absent review/sample/rehearsal' );
	form.reportValidity = () => false;
	await click( 'sample' );
	assert.equal( requests.length, 0, 'invalid form never submits' );
	form.reportValidity = () => true;
	const outer = { open: false, parentElement: { closest: () => null } };
	const inner = { open: false, parentElement: { closest: () => outer } };
	await form.emit( 'invalid', { closest: () => inner } );
	assert.ok( inner.open && outer.open, 'native invalid fields are revealed before validation focus' );
	await chooseLogo.emit( 'click' );
	assert.equal( focused, status );
	assert.ok( status.classList.contains( 'is-error' ), 'media error is accessible and not a success' );
	response = { success: false, data: { message: 'Custom template cannot be replaced.' } };
	await root.emit( 'click', actions.review );
	assert.equal( status.textContent, 'Custom template cannot be replaced.' );
	assert.ok( panel.hidden && actions.activate.disabled, 'server negative leaves activation unavailable' );
	assert.equal( root.attributes[ 'aria-busy' ], 'false' );
	assert.equal( focused, status, 'negative response receives focus' );
	await click( 'sample', { pdf: Buffer.from( '%PDF-fixture' ).toString( 'base64' ), message: 'Sample only, no settings saved.' } );
	assert.equal( elements.get( 'wcj-setup-open' ).hidden, false );
	assert.equal( focused, elements.get( 'wcj-setup-open' ), 'successful sample focuses the newly revealed PDF link' );
	assert.equal( focused.href, 'blob:fixture', 'focused sample link points to the generated PDF' );
	assert.equal( status.textContent, 'Sample only, no settings saved.', 'sample status remains available for the live region' );
	assert.equal( status.classList.contains( 'is-error' ), false );
	await click( 'review', { fingerprint: 'review-1', changes: [ { label: '<unsafe>', before_display: 'Old', after_display: 'New' } ], gateway_restrictions: [] } );
	assert.equal( focused, heading, 'newly revealed review heading receives focus' );
	assert.equal( table.children[ 0 ].children[ 0 ].textContent, '<unsafe>', 'review values use text, not HTML' );
	confirm.checked = true; await form.emit( 'input', confirm );
	assert.ok( actions.activate.disabled, 'attachment rehearsal is still required' );
	await click( 'rehearse', { constructed: true, removed: true, message: 'Attachment built and removed. No email sent.' } );
	assert.equal( actions.activate.disabled, false );
	assert.equal( focused, status, 'other non-review success retains status focus' );
	await click( 'activate', { message: 'Server confirmed saved settings.' } );
	const saved = requests.at( -1 ).options.body.values;
	assert.equal( saved.action, 'wcj_invoice_setup_activate' );
	assert.equal( saved.fingerprint, 'review-1' );
	assert.equal( saved.nonce, 'nonce-activate' );
	assert.equal( saved[ 'draft[business_name]' ], 'Fixture shop', 'payload captured before disabling inputs' );
	assert.equal( Object.keys( saved ).length, fields.length + 3, 'no new write payload keys' );
	assert.ok( panel.hidden && actions.activate.disabled );
	assert.equal( status.textContent, 'Server confirmed saved settings.' );
	await form.emit( 'input', fields[ 0 ] );
	assert.equal( elements.get( 'wcj-setup-open' ).hidden, true );
	assert.equal( revoked, 1, 'editing invalidates and releases the sample' );
	assert.equal( status.textContent, strings.changed );
	assert.ok( fields.every( item => ! item.disabled ), 'all inputs restored after request' );
	console.log( 'PASS: invoice controller fixture; mocked DOM/server only, not browser or backend QA.' );
} )().catch( error => { console.error( error ); process.exitCode = 1; } );

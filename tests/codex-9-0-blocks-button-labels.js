/**
 * Offline real-asset Checkout label filter tests.
 * Usage: node fixture.js plugin-root
 * Public store and registry doubles are read-only. No browser/payment execution.
 */
'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const crypto = require( 'node:crypto' );
const sourcePath = path.join( process.argv[ 2 ], 'includes/js/wcj-more-button-labels-blocks.js' );
const checks = [];

function check( name, actual, expected ) {
	checks.push( { name, actual, expected, pass: Object.is( actual, expected ) } );
}

if ( ! fs.existsSync( sourcePath ) ) {
	check( 'frontend asset exists before source load', false, true );
	process.stdout.write( JSON.stringify( { assertions: checks.length, failures: 1, checks }, null, 2 ) + '\n' );
	process.exit( 1 );
}

const source = fs.readFileSync( sourcePath, 'utf8' );

function fixture( text, override = false, mutate = () => {} ) {
	if ( arguments.length === 0 ) {
		text = 'Booster label';
	}
	const state = {
		active: 'gateway_a',
		initialized: true,
		available: { unrelated_key: { name: 'gateway_a' }, b: { name: 'gateway_b' } },
		methods: {
			gateway_a: Object.freeze( { placeOrderButtonLabel: 'Gateway A' } ),
			gateway_b: Object.freeze( {} ),
		},
	};
	const calls = { selection: 0, registry: 0, registration: 0, settings: 0, subscription: 0 };
	const listeners = [];
	let callback;
	const paymentStore = Object.freeze( { name: 'wc/store/payment' } );
	const selectors = {
		getActivePaymentMethod: () => state.active,
		paymentMethodsInitialized: () => state.initialized,
		getAvailablePaymentMethods: () => state.available,
	};
	const window = {
		wc: {
			blocksCheckout: {
				registerCheckoutFilters: ( namespace, filters ) => {
					calls.registration++;
					check( 'exact native filter namespace', namespace, 'wcj-more-button-labels' );
					check( 'only label filter registered', Object.keys( filters ).join( ',' ), 'placeOrderButtonLabel' );
					callback = filters.placeOrderButtonLabel;
				},
			},
			wcSettings: {
				getSetting: ( name, defaultValue ) => {
					calls.settings++;
					check( 'exact Woo integration data key', name, 'wcj_more_button_labels_data' );
					return { text, override };
				},
			},
			wcBlocksData: { paymentStore },
			wcBlocksRegistry: {
				getPaymentMethods: () => {
					calls.registry++;
					return state.methods;
				},
			},
		},
		wp: {
			data: {
				subscribe: ( listener, store ) => {
					calls.subscription++;
					check( 'exact narrowly subscribed public payment store', store, paymentStore );
					listeners.push( listener );
					return () => {};
				},
				select: ( store ) => {
					calls.selection++;
					check( 'exact read-only public payment store', store, paymentStore );
					return selectors;
				},
			},
		},
	};
	mutate( window, state, selectors );
	vm.runInNewContext( source, { window }, { filename: sourcePath } );
	return { state, calls, window, callback, notify: () => listeners.forEach( ( listener ) => listener() ) };
}

for ( const override of [ false, true ] ) {
	for ( const text of [ '', null, undefined, false, 0, {}, [] ] ) {
		const f = fixture( text, override );
		check( 'blank/non-string keeps resolved merchant label ' + JSON.stringify( [ text, override ] ), f.callback( 'Merchant label' ), 'Merchant label' );
		check( 'blank/non-string performs no selection ' + JSON.stringify( [ text, override ] ), f.calls.selection, 0 );
		check( 'blank/non-string performs no subscription ' + JSON.stringify( [ text, override ] ), f.calls.subscription, 0 );
		check( 'blank/non-string performs no registry read ' + JSON.stringify( [ text, override ] ), f.calls.registry, 0 );
	}
	for ( const text of [ '0', ' ', 'Booster label', '支付 <script> & " ✓' ] ) {
		const f = fixture( text, override );
		check( 'nonempty labelled gateway precedence ' + JSON.stringify( [ text, override ] ), f.callback( 'Gateway A' ), override ? text : 'Gateway A' );
		f.state.active = 'gateway_b';
		check( 'nonempty unlabelled gateway replaces merchant label ' + JSON.stringify( [ text, override ] ), f.callback( 'Merchant label' ), text );
		f.state.active = 'gateway_a';
		check( 'nonempty returns to labelled gateway ' + JSON.stringify( [ text, override ] ), f.callback( 'Gateway A' ), override ? text : 'Gateway A' );
		check( 'no registered gateway label mutation ' + JSON.stringify( [ text, override ] ), f.state.methods.gateway_a.placeOrderButtonLabel, 'Gateway A' );
	}
}

for ( const [ name, mutate ] of [
	[ 'payment API missing', ( w ) => { delete w.wc.wcBlocksData; } ],
	[ 'payment store missing', ( w ) => { delete w.wc.wcBlocksData.paymentStore; } ],
	[ 'wp.data missing', ( w ) => { delete w.wp.data; } ],
	[ 'select API missing', ( w ) => { delete w.wp.data.select; } ],
	[ 'subscribe API missing', ( w ) => { delete w.wp.data.subscribe; } ],
	[ 'subscribe throws', ( w ) => { w.wp.data.subscribe = () => { throw new Error( 'unavailable' ); }; } ],
	[ 'subscribe malformed result', ( w ) => { w.wp.data.subscribe = () => undefined; } ],
	[ 'registry missing', ( w ) => { delete w.wc.wcBlocksRegistry; } ],
	[ 'getter missing', ( w ) => { delete w.wc.wcBlocksRegistry.getPaymentMethods; } ],
	[ 'uninitialized methods', ( w, s ) => { s.initialized = false; } ],
	[ 'unset initialized state', ( w, s ) => { s.initialized = undefined; } ],
	[ 'empty selection', ( w, s ) => { s.active = ''; } ],
	[ 'non-string selection', ( w, s ) => { s.active = null; } ],
	[ 'selected unavailable', ( w, s ) => { s.available = { b: { name: 'gateway_b' } }; } ],
	[ 'availability only wrong object key', ( w, s ) => { s.available = { gateway_a: { name: 'other' } }; } ],
	[ 'unknown registered method', ( w, s ) => { s.methods = {}; } ],
	[ 'missing availability', ( w, s ) => { s.available = null; } ],
	[ 'malformed availability', ( w, s ) => { s.available = 'gateway_a'; } ],
	[ 'malformed registry', ( w, s ) => { s.methods = null; } ],
	[ 'malformed gateway', ( w, s ) => { s.methods.gateway_a = null; } ],
	[ 'malformed gateway label', ( w, s ) => { s.methods.gateway_a = { placeOrderButtonLabel: 1 }; } ],
	[ 'selector absent', ( w, s, selectors ) => { delete selectors.getAvailablePaymentMethods; } ],
	[ 'selector throws', ( w ) => { w.wp.data.select = () => { throw new Error( 'unavailable' ); }; } ],
	[ 'registry getter throws', ( w ) => { w.wc.wcBlocksRegistry.getPaymentMethods = () => { throw new Error( 'unavailable' ); }; } ],
] ) {
	const f = fixture( 'Booster label', false, mutate );
	check( 'unresolved preserves incoming: ' + name, f.callback( 'Incoming resolved label' ), 'Incoming resolved label' );
	const override = fixture( 'Booster label', true, mutate );
	check( 'explicit override does not need selection: ' + name, override.callback( 'Incoming resolved label' ), 'Booster label' );
	check( 'explicit override has no payment reads: ' + name, override.calls.selection, 0 );
	check( 'explicit override has no subscriptions: ' + name, override.calls.subscription, 0 );
	check( 'explicit override has no registry reads: ' + name, override.calls.registry, 0 );
}

const dynamic = fixture();
check( 'initial labelled gateway resolved label preserved', dynamic.callback( 'A label filtered by another extension' ), 'A label filtered by another extension' );
dynamic.state.active = 'gateway_b';
check( 'dynamic active method uses fresh state', dynamic.callback( 'Merchant label' ), 'Booster label' );
dynamic.state.methods.gateway_b = Object.freeze( { placeOrderButtonLabel: 'New gateway B label' } );
check( 'dynamic registry config read without cache', dynamic.callback( 'New gateway B label' ), 'New gateway B label' );
dynamic.state.available = { a: { name: 'gateway_a' } };
check( 'dynamic loss of availability preserves incoming', dynamic.callback( 'Pending label' ), 'Pending label' );

// The real WC source, not this double, decides whether the filtered label is rendered.
const customButton = () => 'Gateway-owned component';
const boundary = fixture();
boundary.state.methods.gateway_a = Object.freeze( { placeOrderButton: customButton } );
check( 'filter does not mutate custom gateway component', boundary.state.methods.gateway_a.placeOrderButton, customButton );
check( 'saved-token native label can use configured text', boundary.callback( 'Place order' ), 'Booster label' );
check( 'custom component identity remains after filter', boundary.state.methods.gateway_a.placeOrderButton, customButton );
check( 'custom component retains its own output', customButton(), 'Gateway-owned component' );

for ( const [ name, mutate ] of [
	[ 'Woo absent', ( w ) => { delete w.wc; } ],
	[ 'checkout API absent', ( w ) => { delete w.wc.blocksCheckout; } ],
	[ 'native filter absent', ( w ) => { delete w.wc.blocksCheckout.registerCheckoutFilters; } ],
	[ 'settings absent', ( w ) => { delete w.wc.wcSettings; } ],
	[ 'settings getter absent', ( w ) => { delete w.wc.wcSettings.getSetting; } ],
] ) {
	const f = fixture( 'Booster label', false, mutate );
	check( 'absent registration API inert: ' + name, f.calls.registration, 0 );
}

// B-1: exercise Woo's actual memoizing public registry, not just callbacks.
const registryPath = path.join( process.argv[ 2 ], 'tests/fixtures/codex-9-0-wc-11-1-checkout-filter-registry.js' );
const registrySource = fs.readFileSync( registryPath, 'utf8' );
const registryStart = registrySource.indexOf( 'const J=()=>!0;let X={},ee={};' );
const registryEnd = registrySource.indexOf( ';\nglobalThis.wcjCodexCheckoutRegistry', registryStart );
const fragmentHash = crypto.createHash( 'sha256' ).update( registrySource.slice( registryStart, registryEnd ) ).digest( 'hex' );
if ( fragmentHash !== '6db0042bf33d62302530968bb708f7e83046d444bff38cd71a82ce42e8bf5063' ) {
	throw new Error( 'Pinned real checkout filter-registry fragment mismatch.' );
}
check( 'actual pinned public filter-registry fragment identity', fragmentHash, '6db0042bf33d62302530968bb708f7e83046d444bff38cd71a82ce42e8bf5063' );

function cacheFixture( text = 'Booster label', override = false, mutate = () => {} ) {
	const state = {
		initialized: false,
		active: '',
		available: { a: { name: 'unlabelled' }, b: { name: 'equal_label' }, c: { name: 'ordinary_label' } },
		methods: {
			unlabelled: Object.freeze( {} ),
			equal_label: Object.freeze( { placeOrderButtonLabel: 'Place Order' } ),
			ordinary_label: Object.freeze( { placeOrderButtonLabel: 'Pay with Gateway' } ),
		},
		selectionThrows: false,
		registryThrows: false,
	};
	const calls = { selection: 0, registry: 0, subscription: 0, registration: 0, callback: 0, dispatch: 0, dom: 0 };
	const listeners = [];
	const ordering = [];
	const events = [];
	const paymentStore = Object.freeze( { name: 'wc/store/payment' } );
	const context = {
		U: {
			isNull: ( value ) => value === null,
			isObject: ( value ) => value !== null && typeof value === 'object',
			objectHasProp: ( value, key ) => Object.prototype.hasOwnProperty.call( value, key ),
		},
		// Native placeOrderButtonLabel calls use null arg/extensions. Do not fake
		// deep comparison: any unanticipated imported comparator call fails.
		Q: () => () => { throw new Error( 'Unexpected external deep-comparison boundary.' ); },
		K: () => () => {},
		G: { __: ( value ) => value, sprintf: ( value ) => value },
		a: { CURRENT_USER_IS_ADMIN: true },
		console,
	};
	vm.createContext( context );
	vm.runInContext( registrySource, context, { filename: registryPath } );
	const register = context.wcjCodexCheckoutRegistry.registerCheckoutFilters;
	const apply = context.wcjCodexCheckoutRegistry.applyCheckoutFilter;
	register( 'before-extension', {
		placeOrderButtonLabel: ( value ) => { ordering.push( 'before' ); return value; },
		showRemoveItemLink: () => false,
	} );
	const selectors = {
		paymentMethodsInitialized: () => state.initialized,
		getActivePaymentMethod: () => state.active,
		getAvailablePaymentMethods: () => state.available,
	};
	const window = {
		wc: {
			blocksCheckout: {
				registerCheckoutFilters: ( namespace, filters ) => {
					calls.registration++;
					events.push( 'register' );
					check( 'cache sequence same Booster namespace', namespace, 'wcj-more-button-labels' );
					check( 'cache sequence only label filter registered', Object.keys( filters ).join( ',' ), 'placeOrderButtonLabel' );
					register( namespace, {
						placeOrderButtonLabel: ( value ) => {
							calls.callback++;
							ordering.push( 'booster' );
							return filters.placeOrderButtonLabel( value );
						},
					} );
				},
			},
			wcSettings: { getSetting: () => ( { text, override } ) },
			wcBlocksData: { paymentStore },
			wcBlocksRegistry: {
				getPaymentMethods: () => {
					calls.registry++;
					if ( state.registryThrows ) {
						throw new Error( 'Synthetic unknown registry.' );
					}
					return state.methods;
				},
			},
		},
		wp: {
			data: {
				select: ( store ) => {
					calls.selection++;
					check( 'cache sequence reads exact public payment store', store, paymentStore );
					if ( state.selectionThrows ) {
						throw new Error( 'Synthetic unknown payment state.' );
					}
					return selectors;
				},
				subscribe: ( listener, store ) => {
					calls.subscription++;
					events.push( 'subscribe' );
					check( 'cache sequence subscribes exact payment store only', store, paymentStore );
					listeners.push( listener );
					return () => {};
				},
				dispatch: () => { calls.dispatch++; throw new Error( 'Forbidden payment dispatch.' ); },
			},
		},
		document: new Proxy( {}, { get: () => { calls.dom++; throw new Error( 'Forbidden DOM access.' ); } } ),
	};
	mutate( window, state, selectors );
	context.window = window;
	vm.runInContext( source, context, { filename: sourcePath } );
	register( 'after-extension', { placeOrderButtonLabel: ( value ) => { ordering.push( 'after' ); return value; } } );
	return {
		state, calls, events, ordering, selectors,
		notify: () => listeners.forEach( ( listener ) => listener() ),
		apply: ( defaultValue ) => apply( { filterName: 'placeOrderButtonLabel', defaultValue } ),
		otherFilter: () => apply( { filterName: 'showRemoveItemLink', defaultValue: true } ),
	};
}

const sequence = cacheFixture();
check( 'subscription installed before initial label registration', sequence.events.join( ',' ), 'subscribe,register' );
check( 'unknown pre-init keeps incoming through real cache', sequence.apply( 'Place Order' ), 'Place Order' );
check( 'unchanged pre-init cache invokes callback once', sequence.apply( 'Place Order' ), 'Place Order' );
check( 'real registry actually cached callback result', sequence.calls.callback, 1 );
check( 'public subscription installed once', sequence.calls.subscription, 1 );
sequence.state.initialized = true;
sequence.state.active = 'unlabelled';
sequence.notify();
check( 'initialization with same default clears stale incoming', sequence.apply( 'Place Order' ), 'Booster label' );
check( 'initialization changes decision and registration once', sequence.calls.registration, 2 );
check( 'initialization reruns real cached callback', sequence.calls.callback, 2 );
sequence.notify();
check( 'same decision notification does not re-register', sequence.calls.registration, 2 );
check( 'same decision stays cached', sequence.apply( 'Place Order' ), 'Booster label' );
check( 'same decision does not reinvoke callback', sequence.calls.callback, 2 );
sequence.state.active = 'equal_label';
sequence.notify();
check( 'equal-string labelled gateway defeats cached Booster text', sequence.apply( 'Place Order' ), 'Place Order' );
check( 'equal-string switch invalidates exactly once', sequence.calls.registration, 3 );
sequence.state.active = 'unlabelled';
sequence.notify();
check( 'equal-string gateway reverse returns Booster text', sequence.apply( 'Place Order' ), 'Booster label' );
check( 'equal-string reverse invalidates exactly once', sequence.calls.registration, 4 );
sequence.state.active = 'ordinary_label';
sequence.notify();
check( 'ordinary gateway label priority', sequence.apply( 'Pay with Gateway' ), 'Pay with Gateway' );
check( 'ordinary gateway switch invalidates exactly once', sequence.calls.registration, 5 );
sequence.state.active = 'equal_label';
sequence.notify();
check( 'labelled to labelled does not re-register', sequence.calls.registration, 5 );
check( 'changed native default updates ordinary labelled output', sequence.apply( 'Place Order' ), 'Place Order' );
sequence.state.active = 'unlabelled';
sequence.notify();
check( 'unlabelled after ordinary switching', sequence.apply( 'Place Order' ), 'Booster label' );
sequence.state.available = {};
sequence.notify();
check( 'availability loss clears cached Booster with same default', sequence.apply( 'Place Order' ), 'Place Order' );
sequence.state.available = { a: { name: 'unlabelled' } };
sequence.notify();
check( 'availability recovery restores configured label', sequence.apply( 'Place Order' ), 'Booster label' );
check( 'other extension filter retained after invalidations', sequence.otherFilter(), false );
check( 'all actual callback order groups retained', sequence.ordering.every( ( value, index ) => value === [ 'before', 'booster', 'after' ][ index % 3 ] ), true );
check( 'native subscription remains singular across transitions', sequence.calls.subscription, 1 );
check( 'no payment dispatcher invoked', sequence.calls.dispatch, 0 );
check( 'no DOM access invoked', sequence.calls.dom, 0 );
check( 'equal-string gateway config untouched', sequence.state.methods.equal_label.placeOrderButtonLabel, 'Place Order' );
check( 'ordinary gateway config untouched', sequence.state.methods.ordinary_label.placeOrderButtonLabel, 'Pay with Gateway' );

for ( const [ name, breakState, restoreState ] of [
	[ 'selection throw', ( f ) => { f.state.selectionThrows = true; }, ( f ) => { f.state.selectionThrows = false; } ],
	[ 'registry throw', ( f ) => { f.state.registryThrows = true; }, ( f ) => { f.state.registryThrows = false; } ],
	[ 'malformed initialized', ( f ) => { f.state.initialized = 1; }, ( f ) => { f.state.initialized = true; } ],
	[ 'malformed available', ( f ) => { f.state.available = []; }, ( f ) => { f.state.available = { a: { name: 'unlabelled' } }; } ],
	[ 'malformed gateway label', ( f ) => { f.state.methods.unlabelled = { placeOrderButtonLabel: 1 }; }, ( f ) => { f.state.methods.unlabelled = Object.freeze( {} ); } ],
	[ 'malformed gateway array', ( f ) => { f.state.methods.unlabelled = []; }, ( f ) => { f.state.methods.unlabelled = Object.freeze( {} ); } ],
	[ 'missing selector', ( f ) => { delete f.selectors.getActivePaymentMethod; }, ( f ) => { f.selectors.getActivePaymentMethod = () => f.state.active; } ],
] ) {
	const f = cacheFixture( 'Booster label', false, ( w, s ) => { s.initialized = true; s.active = 'unlabelled'; } );
	check( 'valid initial decision before fault: ' + name, f.apply( 'Place Order' ), 'Booster label' );
	breakState( f );
	f.notify();
	check( 'fault clears cached eligibility: ' + name, f.apply( 'Place Order' ), 'Place Order' );
	check( 'fault invalidates exactly once: ' + name, f.calls.registration, 2 );
	f.notify();
	check( 'unchanged fault does not re-register: ' + name, f.calls.registration, 2 );
	restoreState( f );
	f.notify();
	check( 'valid recovery clears cached fallback: ' + name, f.apply( 'Place Order' ), 'Booster label' );
	check( 'valid recovery invalidates exactly once: ' + name, f.calls.registration, 3 );
}

for ( const [ name, mutate ] of [
	[ 'subscribe missing', ( w ) => { delete w.wp.data.subscribe; } ],
	[ 'select missing', ( w ) => { delete w.wp.data.select; } ],
	[ 'subscribe throws', ( w ) => { w.wp.data.subscribe = () => { throw new Error( 'Synthetic failed subscription.' ); }; } ],
	[ 'subscribe malformed return', ( w ) => { w.wp.data.subscribe = () => undefined; } ],
] ) {
	const f = cacheFixture( 'Booster label', false, ( w, s ) => { s.initialized = true; s.active = 'unlabelled'; mutate( w ); } );
	check( 'conditional behavior fails closed: ' + name, f.apply( 'Place Order' ), 'Place Order' );
	f.notify();
	check( 'failed subscription never re-registers: ' + name, f.calls.registration, 1 );
}

for ( const [ text, override, expected ] of [
	[ '', false, 'Place Order' ],
	[ '', true, 'Place Order' ],
	[ null, false, 'Place Order' ],
	[ 'Booster label', true, 'Booster label' ],
] ) {
	const f = cacheFixture( text, override );
	check( 'blank or explicit override cached output ' + JSON.stringify( [ text, override ] ), f.apply( 'Place Order' ), expected );
	f.state.initialized = true;
	f.state.active = 'equal_label';
	f.notify();
	check( 'blank or override makes no subscription ' + JSON.stringify( [ text, override ] ), f.calls.subscription, 0 );
	check( 'blank or override makes no selection ' + JSON.stringify( [ text, override ] ), f.calls.selection, 0 );
	check( 'blank or override makes no registry getter call ' + JSON.stringify( [ text, override ] ), f.calls.registry, 0 );
	check( 'blank or override never re-registers ' + JSON.stringify( [ text, override ] ), f.calls.registration, 1 );
}

const customComponent = () => 'Gateway-owned component';
const component = cacheFixture( 'Booster label', false, ( w, s ) => {
	s.initialized = true;
	s.active = 'unlabelled';
	s.methods.unlabelled = Object.freeze( { placeOrderButton: customComponent } );
} );
check( 'native label branch does not replace custom component', component.apply( 'Place Order' ), 'Booster label' );
component.state.available = {};
component.notify();
check( 'custom component boundary survives cache invalidation', component.state.methods.unlabelled.placeOrderButton, customComponent );
check( 'custom component output remains gateway owned', customComponent(), 'Gateway-owned component' );

const freshCallback = cacheFixture( 'Booster label', false, ( w, s ) => { s.initialized = true; s.active = 'unlabelled'; } );
check( 'fresh-reader fixture starts eligible', freshCallback.apply( 'Place Order' ), 'Booster label' );
freshCallback.state.active = 'ordinary_label';
check( 'callback rereads current selection even without notification when native key changes', freshCallback.apply( 'Pay with Gateway' ), 'Pay with Gateway' );
check( 'fresh-reader fixture still has original scalar registration count', freshCallback.calls.registration, 1 );

// Unsupported external registry mutation without a payment notification is
// explicitly a limit, not a native reactivity proof. The same cache key stays
// stale until a supported store notification changes the eligibility decision.
const registryOnly = cacheFixture( 'Booster label', false, ( w, s ) => { s.initialized = true; s.active = 'unlabelled'; } );
check( 'registry-only mutation fixture starts eligible', registryOnly.apply( 'Place Order' ), 'Booster label' );
registryOnly.state.methods.unlabelled = Object.freeze( { placeOrderButtonLabel: 'Place Order' } );
check( 'unsupported registry-only mutation leaves native cache unchanged', registryOnly.apply( 'Place Order' ), 'Booster label' );
registryOnly.notify();
check( 'public payment notification corrects registry-only cached state', registryOnly.apply( 'Place Order' ), 'Place Order' );

const failures = checks.filter( ( assertion ) => ! assertion.pass );
process.stdout.write( JSON.stringify( { assertions: checks.length, failures: failures.length, checks }, null, 2 ) + '\n' );
process.exitCode = failures.length ? 1 : 0;

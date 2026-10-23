/** Source-bound DOM doubles, not real-browser acceptance. */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
let checks = 0;
function check(value, label) { assert.ok(value, label); checks++; }
function element(attrs = {}) {
    const handlers = {};
    return { attrs, hidden: false, disabled: false, value: 'yes', name: 'native-option',
        classList: { toggle() {} },
        getAttribute(key) { return this.attrs[key]; },
        setAttribute(key, value) { this.attrs[key] = value; },
        addEventListener(key, callback) { handlers[key] = callback; },
        emit(key) { let prevented = false; handlers[key]({preventDefault() { prevented = true; }}); return prevented; }
    };
}
const all = element({'data-filter': 'all'});
const suggested = element({'data-filter': 'recommended'});
const cards = [element({'data-recommended': 'yes'}), element({'data-recommended': 'no'})];
const status = {textContent: ''};
const container = {querySelectorAll() { return [all, suggested]; }};
const document = {
    getElementById(id) { return id === 'wcj-module-filters' ? container : status; },
    querySelectorAll(selector) { assert.equal(selector, '.wcj-catalog-item[data-module]'); return cards; }
};
const source = fs.readFileSync(path.join(root, 'assets/js/wcj-module-filters.js'), 'utf8');
const start = source.indexOf('var WCJModuleFilters');
const end = source.indexOf('\n    /**', start) > 0 ? source.indexOf('\n    /**', start) : source.indexOf('$( document ).ready', start);
const scope = {document, wcj_hub_params: {count: '%d tools shown.', one: '1 tool shown.', empty: 'No tools match these filters.'}};
vm.createContext(scope);
vm.runInContext(source.slice(start, end) + '\nWCJModuleFilters.init();', scope);
check(!cards[0].hidden && !cards[1].hidden, 'All results initially visible');
check(suggested.emit('click'), 'Filter prevents submit');
check(!cards[0].hidden && cards[1].hidden, 'Complete nonrecommended card hidden');
check(cards.every(card => !card.disabled && card.value === 'yes' && card.name === 'native-option'), 'Form controls remain enabled/named/unchanged');
check(suggested.attrs['aria-pressed'] === 'true' && all.attrs['aria-pressed'] === 'false', 'Selected filter semantics');
check(status.textContent === '1 tool shown.', 'Localized status count');
cards[0].attrs['data-recommended'] = 'no';
suggested.emit('click');
check(status.textContent === 'No tools match these filters.', 'Localized empty status');
all.emit('click');
check(cards.every(card => !card.hidden), 'All results resets visibility');
const admin = fs.readFileSync(path.join(root, 'includes/js/admin-script.js'), 'utf8');
const a = admin.indexOf('function wcjSearchTools(');
const b = admin.indexOf("    $( 'body' ).on(", a);
const handlers = {};
let query = '';
let destination = 'https://fixture.invalid/wp-admin/admin.php?page=wcj-plugins&section=active&wcj-cat=labels&wcj-cat-nonce=fixture';
const window = {location: {href: destination}};
function $(selector) { return {val() { return query; }, attr() { return destination; }, on(key, callback) { handlers[selector + ':' + key] = callback; }}; }
const navigation = {window, $, URL};
vm.createContext(navigation);
vm.runInContext(admin.slice(a, b), navigation);
for (query of ['a&b+', '[] .* /', 'Café "quote"', "'", '']) {
    let prevented = false;
    handlers['#wcj_search_modules:keydown']({key: 'Enter', preventDefault() { prevented = true; }});
    const actual = new URL(window.location.href);
    check(prevented, 'Enter prevents form submit');
    check(actual.searchParams.get('wcj_search') === (query || null), 'Query encoded literally: ' + query);
    check(actual.searchParams.get('section') === 'active' && actual.searchParams.get('wcj-cat') === 'labels', 'View/category preserved');
    check(!actual.searchParams.has('apply_preset'), 'No navigation apply action');
}
query = 'click';
handlers['#wcj_search_modules_btn:click']({preventDefault() {}});
check(new URL(window.location.href).searchParams.get('wcj_search') === query, 'Click and Enter share handler');
console.log(JSON.stringify({status: 'PASS', checks, scope: 'Source-bound JS DOM doubles, no browser'}));

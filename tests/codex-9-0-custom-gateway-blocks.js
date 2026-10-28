/* Source-only public API VM, not React/browser/native checkout proof. */
'use strict';
const fs = require('fs');
const vm = require('vm');
const path = require('path');
const root = process.argv[2];
if (!root) { process.exit(2); }
const file = path.join(root, 'includes/js/wcj-custom-payment-gateways-blocks.js');
const checks = [];
const check = (name, actual, expected) => checks.push({ name, pass: JSON.stringify(actual) === JSON.stringify(expected), actual, expected });
const finish = () => {
 const failures = checks.filter(c => !c.pass).length;
 process.stdout.write(JSON.stringify({ assertions: checks.length, failures, cases: checks, limitations: ['Bounded public API/React hook stand-ins, no browser proof', 'Sanitizer call observed, native sanitizer/DOM not certified'] }, null, 2) + '\n');
 process.exitCode = failures ? 1 : 0;
};
check('actual production JS exists', fs.existsSync(file), true);
if (!fs.existsSync(file)) { finish(); } else {
 const methods = []; const sanitizations = []; let active;
 const createElement = (type, props, ...children) => ({ type, props: props || {}, children });
 const element = {
  createElement,
  RawHTML: 'RawHTML',
  useState(initial) { const i = active.cursor++; if (!(i in active.state)) active.state[i] = typeof initial === 'function' ? initial() : initial; const inst = active; return [inst.state[i], v => { inst.state[i] = typeof v === 'function' ? v(inst.state[i]) : v; }]; },
  useRef(initial) { const i = active.cursor++; if (!(i in active.refs)) active.refs[i] = { current: initial }; return active.refs[i]; },
  useEffect(callback, deps) { const i = active.cursor++; const old = active.effects[i]; if (!old || deps.some((v, j) => v !== old.deps[j])) { if (old && old.cleanup) old.cleanup(); active.effects[i] = { deps, cleanup: callback() }; } }
 };
 const field = (name, required, value = '') => ({ name, label: name, placeholder: '', default: value, className: '', required });
 const settings = {
  jetpack_custom_gateway: { profile: 1, title: 'Bank &amp; Ref', fields: [field('ref', true)], segments: ['Before', 'After'], hash: 'a'.repeat(64), supports: ['products'] },
  jetpack_custom_gateway_2: { profile: 1, title: 'Other', fields: [field('__proto__', false, 'B')], segments: ['', ''], hash: 'b'.repeat(64), supports: ['products'] },
  jetpack_custom_gateway_3: { profile: 1, title: 'Static', fields: [], segments: ['Static'], hash: 'c'.repeat(64), supports: ['products'] },
  jetpack_custom_gateway_bad: { profile: 1 },
  cod: { profile: 1 },
  jetpack_custom_gateway_4: { profile: 2 }
 };
 const window = { wc: { wcBlocksRegistry: { registerPaymentMethod: x => methods.push(x) }, wcSettings: { getSetting: (key, fallback) => key === 'paymentMethodData' ? settings : fallback }, sanitize: { sanitizeHTML: html => { sanitizations.push(html); return html; } } }, wp: { element, htmlEntities: { decodeEntities: x => x.replace(/&amp;/g, '&') }, i18n: { __: x => x } } };
 vm.runInNewContext(fs.readFileSync(file, 'utf8'), { window }, { timeout: 1000, filename: file });
 check('only eligible exact IDs registered once', methods.map(m => m.name), ['jetpack_custom_gateway', 'jetpack_custom_gateway_2', 'jetpack_custom_gateway_3']);
 check('decoded accessible title', methods[0].ariaLabel, 'Bank & Ref');
 check('existing products feature', methods[0].supports.features, ['products']);
 check('Woo owns availability', methods[0].canMakePayment(), true);
 function instance() { return { state: [], refs: [], effects: [], cursor: 0 }; }
 function render(node, props, inst) { active = inst; active.cursor = 0; return node.type({ ...node.props, ...props }); }
 function inputs(node) { if (!node || typeof node !== 'object') return []; return (node.type === 'input' ? [node] : []).concat((node.children || []).flat(Infinity).flatMap(inputs)); }
 function events() { const h = { callback: null, subscribed: 0, unsubscribed: 0 }; h.onPaymentSetup = cb => { h.callback = cb; h.subscribed++; return () => { h.callback = null; h.unsubscribed++; }; }; return h; }
 const response = { responseTypes: { SUCCESS: 'success', ERROR: 'error' } };
 const a = instance(), b = instance(), ea = events(), eb = events();
 const tree = render(methods[0].content, { eventRegistration: ea, emitResponse: response }, a);
 render(methods[1].content, { eventRegistration: eb, emitResponse: response }, b);
 check('required empty rejects', ea.callback().type, 'error');
 check('actual maxLength and text type', [inputs(tree)[0].props.maxLength, inputs(tree)[0].props.type], [512, 'text']);
 check('gateway-scoped input ID', inputs(tree)[0].props.id.includes('jetpack_custom_gateway-'), true);
 inputs(tree)[0].props.onChange({ target: { value: '0' } });
 const success = ea.callback();
 check('current value without rerender succeeds', success.type, 'success');
 check('exact ordered arrays-only payload', JSON.parse(success.meta.paymentMethodData.wcj_custom_gateway_fields), [1, 'jetpack_custom_gateway', 'a'.repeat(64), [['ref', '0']]]);
 check('no client label or required flags', Object.keys(success.meta.paymentMethodData), ['wcj_custom_gateway_fields']);
 check('other gateway state and prototype name isolated', JSON.parse(eb.callback().meta.paymentMethodData.wcj_custom_gateway_fields), [1, 'jetpack_custom_gateway_2', 'b'.repeat(64), [['__proto__', 'B']]]);
 render(methods[0].content, { eventRegistration: ea, emitResponse: response }, a);
 check('stable event registration only once', ea.subscribed, 1);
 const empty = instance(), ee = events(); render(methods[2].content, { eventRegistration: ee, emitResponse: response }, empty);
 check('zero fields exact envelope', JSON.parse(ee.callback().meta.paymentMethodData.wcj_custom_gateway_fields), [1, 'jetpack_custom_gateway_3', 'c'.repeat(64), []]);
 const edit = instance(); render(methods[0].edit, {}, edit);
 check('editor never subscribes payment event', edit.effects.filter(Boolean).length, 0);
 a.effects.filter(Boolean).forEach(e => e.cleanup && e.cleanup());
 check('unsubscribe clears only own callback', [ea.unsubscribed, ea.callback, typeof eb.callback], [1, null, 'function']);
 check('static segments pass sanitizer', sanitizations.includes('Before') && sanitizations.includes('After'), true);
 finish();
}

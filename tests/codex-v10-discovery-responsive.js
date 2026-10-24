/** Scoped CSS/source regression only. Real-browser geometry remains a separate gate. */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const cssPath = process.argv[2] || path.join(root, 'includes/css/admin-style.css');
const source = fs.readFileSync(cssPath, 'utf8');
const css = source.replace(/\/\*[\s\S]*?\*\//g, '');
let checks = 0;
function check(value, label) { assert.ok(value, label); checks++; }
const media = /@media\s*\(max-width:\s*768px\)\s*\{/g;
const match = media.exec(css);
check(match && !media.exec(css), 'One existing <=768px responsive boundary');
const start = match.index + match[0].length;
let depth = 1;
let end = start;
while (end < css.length && depth) {
    if (css[end] === '{') depth++;
    if (css[end] === '}') depth--;
    end++;
}
check(depth === 0, 'Responsive block has balanced braces');
const mobile = css.slice(start, end - 1);
const outside = css.slice(0, match.index) + css.slice(end);
function declarations(selector) {
    const needle = selector + ' {';
    const at = mobile.indexOf(needle);
    check(at >= 0 && mobile.indexOf(needle, at + needle.length) < 0, 'One mobile rule: ' + selector);
    check(!outside.includes(needle), 'No desktop/global override: ' + selector);
    const body = mobile.slice(at + needle.length, mobile.indexOf('}', at));
    check(!/!important|overflow\s*:\s*hidden/.test(body), 'No suppression/clipping: ' + selector);
    return Object.fromEntries(body.split(';').filter(x => x.trim()).map(x => {
        const colon = x.indexOf(':');
        return [x.slice(0, colon).trim(), x.slice(colon + 1).trim()];
    }));
}
const tabs = declarations('.wcj-plugins .wcj-plugins-top-part-left.plug_tab');
check(tabs.float === 'none', 'Narrow tabs leave legacy float layout');
check(tabs.width === '100%', 'Tabs use the available row width');
check(tabs['max-height'] === 'none', 'Wrapped tab labels expand without overlapping search');
const labels = declarations('.wcj-plugins .wcj-plugins-top-part-left.plug_tab ul');
check(labels['flex-wrap'] === 'wrap', 'Native links may wrap instead of clipping');
const search = declarations('.wcj-plugins .wcj-plugins-tp-search.src_plug');
check(search['box-sizing'] === 'border-box', 'Search padding stays inside its existing full width');
check(search['min-width'] === '0', 'Search form can shrink to the available width');
const input = declarations('.wcj-plugins .wcj-plugins-tp-search.src_plug .wcj-search-inp');
check(input['box-sizing'] === 'border-box', 'Search input includes padding in its width');
check(input['min-width'] === '0' && input['max-width'] === '100%', 'Search input does not force horizontal overflow');
check(/\.plug_tab\s*\{[^}]*width:\s*56%;[^}]*max-height:\s*70px;/.test(outside), 'Desktop legacy sizing is unchanged');
const markup = fs.readFileSync(path.join(root, 'includes/admin/wcj-settings-plugins.php'), 'utf8');
check(markup.includes('class="wcj-plugins-top-part-left plug_tab"'), 'Scoped selector matches actual native tabs');
check(markup.includes('class="wcj-plugins-tp-search src_plug" method="get"'), 'Scoped search retains native GET form');
check(markup.includes('id="wcj_search_modules" name="wcj_search"'), 'Existing search field identity is retained');
const paid = fs.existsSync(path.join(root, 'booster-plus-for-woocommerce.php')) || fs.existsSync(path.join(root, 'booster-elite-for-woocommerce.php'));
for (const consumer of ['includes/core/class-wcj-admin.php', 'includes/admin/class-wc-settings-jetpack.php']) {
    const php = fs.readFileSync(path.join(root, consumer), 'utf8');
    const lines = php.split('\n').filter(line => line.includes('/includes/css/admin-style.css'));
    check(lines.length === 1, 'One existing CSS consumer: ' + consumer);
    const version = paid ? "array(), WCJ()->version . '-discovery-1' )" : 'array(), time() )';
    check(lines[0].includes(version), 'Changed paid CSS has a new URL; Free keeps its existing timing: ' + consumer);
}
console.log(JSON.stringify({status: 'PASS', checks, scope: 'CSS/source only, not rendered geometry or browser acceptance'}));

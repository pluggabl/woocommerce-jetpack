"""Non-empty native report UI proof in the dedicated synthetic R2 store only.

Retains tagged synthetic entities. No checkout, payment, mail, export or real data.
This is subset-display evidence, never full reporting or accounting acceptance.
"""
from pathlib import Path
from urllib.parse import urlsplit
import hashlib
import json
import subprocess
import sys
import requests
from playwright.sync_api import sync_playwright

root = Path(sys.argv[1]).resolve()
generation = sys.argv[2]
assert generation.replace('-', '').isalnum()
identity = json.loads((root / 'runtime-r2/local-identity.json').read_text())
assert identity['namespace'] == 'booster-ai-20261112-r2'
origin = identity['origin']
assert urlsplit(origin).hostname == '127.0.0.1'
wp = identity['namespace'] + '-wp'
out = root / 'evidence/runtime' / generation
out.mkdir(parents=True, exist_ok=False)
cases = []


def php(code):
    prelude = "define('WP_ADMIN',true);$_SERVER['REQUEST_METHOD']='GET';require '/var/www/html/wp-load.php';if(DB_NAME!=='synthetic_ai'||getenv('AI_ORIGIN')!==home_url())exit(3);wp_set_current_user(get_user_by('login','store_owner')->ID);"
    result = subprocess.run(['docker', 'exec', '--user', 'www-data', wp, 'php', '-r', prelude + code], capture_output=True, timeout=90)
    assert result.returncode == 0, 'Synthetic fixture operation failed; details withheld'
    return result.stdout.decode()


def check(name, condition):
    cases.append({'case': name, 'pass': bool(condition)})
    (out / 'cases.json').write_text(json.dumps(cases, indent=2))
    assert condition, name


seed = r"""
require_once WP_PLUGIN_DIR.'/woocommerce-jetpack/includes/class-wcj-ai-report-values.php';
if (\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) exit(4);
$periods=WCJ_AI_Report_Values::completed_week_pair(time(),wp_timezone_string());
$key='wcj_ai_ui_report_fixture';$existing=get_option($key);
if ($existing) {if($existing['periods']['periods']!==$periods['periods'])exit(5);echo json_encode($existing);return;}
global $wpdb;
$wpdb->last_error='';$count=$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('shop_order','shop_order_refund')");
if ($wpdb->last_error!==''||!is_string($count)||$count!=='0') exit(6);
$older=strtotime($periods['periods'][0]['utc_start'])+3600;
$newer=strtotime($periods['periods'][1]['utc_start'])+3600;
$ids=[];
$order=static function($date,$currency,$amount,$status='completed')use(&$ids){
 $o=new WC_Order();$o->set_currency($currency);$o->set_status($status);
 $o->set_date_created($date-100);$o->set_date_paid($date);$o->set_total($amount);
 $o->add_meta_data('_wcj_ai_synthetic','c4a-report-ui',true);$o->save();$ids[]=$o->get_id();return $o;
};
$usd=$order($older,'USD','100.25');$usd->set_date_created($older-30*86400);$usd->save();
$small=$order($older,'USD','0.10');$eur=$order($newer,'EUR','70.50');
$zero=$order($newer,'USD','0.00');$excluded=$order($newer,'USD','999.99','cancelled');
$parent=$order($older-30*86400,'USD','20.00');
foreach([[$parent,$newer,'USD','5.25']]as[$p,$date,$currency,$amount]){
 $o=new WC_Order_Refund();$o->set_parent_id($p->get_id());$o->set_currency($currency);$o->set_amount($amount);
 $o->set_date_created($date);$o->add_meta_data('_wcj_ai_synthetic','c4a-report-ui',true);$o->save();$ids[]=$o->get_id();
}
$record=['periods'=>$periods,'ids'=>$ids,'scope'=>'seven retained synthetic entities, no customer identity'];
if(!add_option($key,$record,'',false)||get_option($key)!==$record)exit(7);echo json_encode($record);
"""
fixture = json.loads(php(seed))
(out / 'fixture.json').write_text(json.dumps(fixture, indent=2))
check('seven synthetic native entities retained', len(fixture['ids']) == 7)


def snapshot():
    return json.loads(php(r"""
    $r=get_option('wcj_ai_ui_report_fixture');$out=[];
    foreach($r['ids']as$id){$o=wc_get_order($id);if(!$o||$o->get_meta('_wcj_ai_synthetic')!=='c4a-report-ui')exit(8);
    $out[]=['id'=>$id,'parent'=>$o->get_parent_id('edit'),'type'=>$o->get_type(),'status'=>$o->get_status('edit'),'currency'=>$o->get_currency('edit'),
    'amount'=>$o instanceof WC_Order_Refund?$o->get_amount('edit'):$o->get_total('edit'),
    'paid'=>$o instanceof WC_Order_Refund?null:$o->get_date_paid('edit')?->getTimestamp(),'created'=>$o->get_date_created('edit')?->getTimestamp(),
    'raw_currency'=>get_post_meta($id,'_order_currency',false),
    'raw_amount'=>get_post_meta($id,$o instanceof WC_Order_Refund?'_refund_amount':'_order_total',false)];}
    echo json_encode($out);
    """))


before = snapshot()
check('persisted native amounts and currencies match fixture',
      [(x['currency'], x['amount']) for x in before] ==
      [('USD', '100.25'), ('USD', '0.10'), ('EUR', '70.50'), ('USD', '0.00'), ('USD', '999.99'), ('USD', '20.00'), ('USD', '5.25')])
older_stamp = int(__import__('datetime').datetime.fromisoformat(fixture['periods']['periods'][0]['utc_start'].replace('Z', '+00:00')).timestamp()) + 3600
newer_stamp = int(__import__('datetime').datetime.fromisoformat(fixture['periods']['periods'][1]['utc_start'].replace('Z', '+00:00')).timestamp()) + 3600
expected_dates = [(older_stamp - 30 * 86400, older_stamp), (older_stamp - 100, older_stamp), (newer_stamp - 100, newer_stamp), (newer_stamp - 100, newer_stamp), (newer_stamp - 100, newer_stamp), (older_stamp - 30 * 86400 - 100, older_stamp - 30 * 86400), (newer_stamp, None)]
check('retained native type status dates and parent revalidated',
      [(x['type'], x['status'], x['parent'], x['created'], x['paid']) for x in before] ==
      [('shop_order' if i < 6 else 'shop_order_refund', 'cancelled' if i == 4 else 'completed', 0 if i < 6 else fixture['ids'][5], *expected_dates[i]) for i in range(7)])
password = subprocess.check_output(['docker', 'exec', wp, 'cat', '/tmp/ai-owner']).decode()
session = requests.Session()
session.get(origin + '/wp-login.php', timeout=90)
session.post(origin + '/wp-login.php', data={'log': 'store_owner', 'pwd': password, 'testcookie': '1'}, allow_redirects=False, timeout=90)
check('native owner session exists', any(c.name.startswith('wordpress_logged_in_') for c in session.cookies))
with sync_playwright() as pw:
    browser = pw.chromium.launch(headless=True)
    context = browser.new_context(viewport={'width': 1280, 'height': 1100}, java_script_enabled=False)
    context.route('**/*', lambda route: route.continue_() if route.request.url.startswith(origin + '/') else route.abort())
    context.add_cookies([{'name': c.name, 'value': c.value, 'domain': c.domain, 'path': c.path, 'httpOnly': True, 'secure': False, 'sameSite': 'Lax'} for c in session.cookies])
    page = context.new_page()
    page.set_default_timeout(120000)
    page.goto(origin + '/wp-admin/admin.php?page=wcj-ai-preview', wait_until='domcontentloaded')
    form = page.locator('form').filter(has=page.locator('input[name="op"][value="report"]'))
    check('native local report form available', form.count() == 1)
    with page.expect_navigation(wait_until='domcontentloaded', timeout=120000) as navigation:
        form.get_by_role('button').click(no_wait_after=True)
    response = navigation.value
    check('native report POST succeeds with private headers', response.status == 200 and 'no-store' in response.headers.get('cache-control', '') and response.headers.get('referrer-policy') == 'no-referrer')
    check('two separate amount tables', page.locator('table').count() == 2)
    headings = ['Observed paid-cohort order totals', 'Observed recorded refund-event amounts']
    bases = ['Selected by paid date', 'Selected independently by refund creation date']
    tables = []
    for heading, basis in zip(headings, bases):
        anchor = page.get_by_role('heading', name=heading, exact=True)
        check('stream heading and basis bound: ' + heading, anchor.count() == 1 and basis in anchor.locator('xpath=following-sibling::*[1]').inner_text())
        table = anchor.locator('xpath=following-sibling::*[2]')
        check('amount table follows its own stream basis: ' + heading, table.evaluate('(e)=>e.tagName') == 'TABLE')
        tables.append(table)
    rows = [table.locator('tbody tr').evaluate_all('(rows)=>rows.map(r=>Array.from(r.children,c=>c.textContent))') for table in tables]
    older = 'Preceding seven completed days'
    newer = 'Last seven completed days'
    expected = [[[older, 'USD', '2', '100.35'], [newer, 'EUR', '1', '70.5'], [newer, 'USD', '1', '0']],
                [[newer, 'USD', '1', '5.25']]]
    check('exact paid date currency amount tuples including observed zero', sorted(rows[0]) == sorted(expected[0]))
    check('independent refund event date and currency tuples', sorted(rows[1]) == sorted(expected[1]))
    text = page.locator('body').inner_text()
    check('cancelled and out of window parent totals absent', '999.99' not in text and not any(row[-1] == '20' for table in rows for row in table))
    check('frozen timezone displayed', fixture['periods']['timezone'] in text)
    check('frozen local and UTC boundaries displayed', all(p[k] in text for p in fixture['periods']['periods'] for k in ['local_start', 'local_end', 'utc_start', 'utc_end']))
    check('incomplete membership and absent is not zero disclosed', all(s in text for s in ['Incomplete, non-atomic', 'Store membership is unknown', 'not zero', 'No cross-currency total', 'Parent orders may fall outside', 'At most 25 selected entities per stream across BOTH periods', 'Selected by paid date', 'Selected independently by refund creation date']))
    check('pending full report features disclosed', all(s in text for s in ['Net revenue;', 'product/variation breakdown;', 'return requests;', 'resumable queries;', 'HPOS.']))
    page.screenshot(path=str(out / 'report-populated.png'), full_page=True)
    context.close()
    browser.close()
check('report preserves known persisted order facts', snapshot() == before)
result = {'test_sha256': hashlib.sha256(Path(__file__).read_bytes()).hexdigest(), 'cases': cases, 'rows': rows,
          'scope': 'Actual local owner form and native legacy subset; no full accounting, HPOS, client export or completeness proof',
          'synthetic_entities_retained': True, 'screenshot_sha256': hashlib.sha256((out / 'report-populated.png').read_bytes()).hexdigest()}
(out / 'result.json').write_text(json.dumps(result, indent=2))
print(json.dumps({'passed': len(cases), 'evidence': str(out)}))

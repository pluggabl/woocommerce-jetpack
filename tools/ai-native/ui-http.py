"""Synthetic loopback-only UI checks. No credentials or full HTTP bodies in logs."""
from pathlib import Path
import base64
import hashlib
import json
import subprocess
import sys
from urllib.parse import urlsplit
import requests

from playwright.sync_api import sync_playwright

assert len(sys.argv) == 3, 'run-root and new evidence generation required'
root = Path(sys.argv[1]).resolve()
generation = sys.argv[2]
assert generation.replace('-', '').isalnum()
identity = json.loads((root / 'runtime-r2/local-identity.json').read_text())
origin = identity['origin']
assert identity['namespace'] == 'booster-ai-20261112-r2'
assert urlsplit(origin).hostname == '127.0.0.1'
out = root / 'evidence/runtime' / generation
out.mkdir(parents=True, exist_ok=False)
wp = identity['namespace'] + '-wp'
cases = []


def docker(*args):
    p = subprocess.run(['docker', *args], capture_output=True, timeout=120)
    assert p.returncode == 0, 'Synthetic Docker operation failed (details withheld)'
    return p.stdout


def php(code):
    prelude = "define('WP_ADMIN',true);$_SERVER['REQUEST_METHOD']='GET';require '/var/www/html/wp-load.php';if(DB_NAME!=='synthetic_ai'||getenv('AI_ORIGIN')!==home_url())exit(3);"
    return docker('exec', wp, 'php', '-r', prelude + code).decode()


def check(name, condition):
    cases.append({'case': name, 'pass': bool(condition)})
    (out / 'cases.json').write_text(json.dumps(cases, indent=2))
    assert condition, name


def settings():
    return json.loads(php("global $wpdb;$rows=$wpdb->get_results(\"SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'wcj_%' AND option_name NOT LIKE 'wcj_ai_invoice_plan_%'\",ARRAY_A);echo json_encode($rows);"))


password = docker('exec', wp, 'cat', '/tmp/ai-owner').decode()
page_url = origin + '/wp-admin/admin.php?page=wcj-ai-preview'
post_url = origin + '/wp-admin/admin-post.php'
before = settings()
target_colour = '#445566' if php("echo get_option('wcj_invoicing_invoice_header_text_color');") == '#334455' else '#334455'
check('fixture uses intended legacy storage', php(r"echo \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()?'hpos':'legacy';") == 'legacy')

with sync_playwright() as pw:
    browser = pw.chromium.launch(headless=True)
    context = browser.new_context(viewport={'width': 1280, 'height': 1000}, java_script_enabled=False)
    context.route('**/*', lambda route: route.continue_() if route.request.url.startswith(origin + '/') or route.request.url.startswith('data:') else route.abort())
    page = context.new_page()
    page.set_default_navigation_timeout(120000)
    page.set_default_timeout(120000)
    login = requests.Session()
    login.get(origin + '/wp-login.php', timeout=120)
    login.post(origin + '/wp-login.php', data={'log':'store_owner','pwd':password,'testcookie':'1'}, allow_redirects=False, timeout=120)
    context.add_cookies([{'name':c.name,'value':c.value,'domain':c.domain,'path':c.path,'httpOnly':True,'secure':False,'sameSite':'Lax'} for c in login.cookies])
    page.goto(page_url, wait_until='domcontentloaded')
    check('three local sections visible', all(page.get_by_role('heading', name=n).count() == 1 for n in ['1. Review an invoice colour proposal', '2. Observe native role pricing', '3. Inspect recorded amounts']))
    page.screenshot(path=str(out / 'controls.png'), full_page=True)
    check('GET controls preserve Booster settings', settings() == before)

    def form(op):
        return page.locator('form').filter(has=page.locator('input[name="op"][value="' + op + '"]'))

    def payload(op):
        return {i['name']: i['value'] for i in form(op).locator('input[type="hidden"]').evaluate_all('(xs) => xs.map(x=>({name:x.name,value:x.value}))')}

    def submit(op):
        with page.expect_navigation(wait_until='domcontentloaded', timeout=120000):
            form(op).get_by_role('button').click(no_wait_after=True)

    def create(colour):
        page.goto(page_url, wait_until='domcontentloaded')
        form('create').locator('input[name="changes[header_text_color]"]').fill(colour)
        submit('create')
        check('prepared proposal returned local review', page.get_by_role('heading', name='Saved proposal').count() == 1)

    create(target_colour)
    review_url = page.url
    check('prepare preserves Booster settings', settings() == before)
    page.screenshot(path=str(out / 'proposal.png'), full_page=True)
    stale_apply = payload('apply')
    bad = dict(stale_apply, _wpnonce='invalid', confirm='1')
    check('wrong nonce refused', context.request.post(post_url, form=bad).status == 403)
    check('missing explicit approval refused', context.request.post(post_url, form=stale_apply).status == 403)
    check('replacement changes refused', context.request.post(post_url, form=dict(stale_apply, confirm='1', **{'changes[header_text_color]':'#ff0000'})).status == 403)
    check('GET action refused', context.request.get(post_url + '?action=wcj_ai_preview&op=apply').status == 403)
    check('rejected operations preserve settings', settings() == before)

    for op in ['preview_now', 'preview_after']:
        submit(op)
        check(op + ' renders local sample PDF', page.locator('iframe[src^="data:application/pdf;base64,"]').count() == 1)
        data = page.locator('iframe').get_attribute('src').split(',', 1)[1]
        pdf = base64.b64decode(data)
        check(op + ' contains PDF bytes', pdf.startswith(b'%PDF-'))
        (out / (op + '.pdf')).write_bytes(pdf)
        page.goto(review_url, wait_until='domcontentloaded')
    check('PDF previews preserve settings', settings() == before)
    form('apply').locator('input[name="confirm"]').check()
    submit('apply')
    check('applied state shown', 'State: applied' in page.locator('.wrap').filter(has=page.get_by_role('heading', name='Booster AI', exact=False)).inner_text())
    check('exact intended value persisted', php("echo get_option('wcj_invoicing_invoice_header_text_color');") == target_colour)
    check('old revision nonce cannot replay apply', context.request.post(post_url, form=dict(stale_apply, confirm='1')).status == 403)
    page.screenshot(path=str(out / 'applied.png'), full_page=True)
    form('recover').locator('input[name="confirm"]').check()
    submit('recover')
    check('recovery restores exact prior Booster settings', settings() == before)
    check('recovered state shown', 'State: recovered' in page.locator('.wrap').filter(has=page.get_by_role('heading', name='Booster AI', exact=False)).inner_text())
    create('#556677')
    submit('cancel')
    check('cancelled state shown and settings preserved', 'State: cancelled' in page.locator('.wrap').filter(has=page.get_by_role('heading', name='Booster AI', exact=False)).inner_text() and settings() == before)
    submit('report')
    check('report discloses incomplete scope', 'Incomplete, non-atomic observation' in page.locator('body').inner_text())
    check('two amount streams rendered separately', page.get_by_role('heading', name='Observed paid-cohort order totals').count() == 1 and page.get_by_role('heading', name='Observed recorded refund-event amounts').count() == 1)
    check('empty subset not called zero', 'No rows observed; store completeness unknown.' in page.locator('body').inner_text())
    page.screenshot(path=str(out / 'report.png'), full_page=True)
    page.goto(page_url, wait_until='domcontentloaded')
    form('capture').locator('input[name="product"]').fill('10')
    submit('capture')
    capture_url = page.url
    check('native capture footer visible', page.locator('#wcj-ai-capture-title').count() == 1)
    view = json.loads(page.locator('section[aria-labelledby="wcj-ai-capture-title"] pre').inner_text())
    check('actual native role callbacks observed', len(view['events']) > 0)
    check('other modules explicitly unobserved', 'multicurrency' in view['unobserved'] and view['persistence'] == 'none')
    (out / 'capture-dto.json').write_text(json.dumps(view, indent=2))
    page.screenshot(path=str(out / 'capture.png'), full_page=True)
    for name, url in [('extra cart argument', capture_url + '&add-to-cart=10'), ('duplicate argument', capture_url + '&p=10'), ('rewritten path', capture_url.replace(origin + '/?', origin + '/wp-json/?'))]:
        check(name + ' refused', context.request.get(url).status == 403)
    check('GET body refused', context.request.fetch(capture_url, method='GET', data='{"mutation":true}', headers={'Content-Type':'application/json'}).status == 403)
    def sentinel_request(url, data=None, extra_headers=None):
        php("file_put_contents('/tmp/ai-ui-sentinel','');")
        headers = {'X-AI-Test':'sentinel', **(extra_headers or {})}
        response = context.request.post(url, form=data, headers=headers) if data is not None else context.request.get(url, headers=headers)
        return response.status, php("echo file_get_contents('/tmp/ai-ui-sentinel');")
    page.goto(page_url, wait_until='domcontentloaded')
    launch = payload('capture')
    for action in ['wcj_ai_preview', ' wcj_ai_preview ', '<b>wcj_ai_preview</b>', 'wcj_ai_preview%20']:
        status, hits = sentinel_request(post_url, dict(launch, action=action, product='10', **{'wcj-currency':'EUR','wcj-currency-nonce':'synthetic'}))
        check('action normalization denied before construction: ' + action.replace('<','[').replace('>',']'), status == 403 and hits == '')
    status, hits = sentinel_request(origin + '/wp-admin/admin.php?page=%2Fwcj-ai-preview%2F&wcj-currency=EUR')
    check('page alias denied before construction', status == 403 and hits == '')
    status, hits = sentinel_request(capture_url + '&wcj-currency=EUR')
    check('capture command denied before construction', status == 403 and hits == '')
    cookies = '; '.join(c['name']+'='+c['value'] for c in context.cookies())
    for injected in ['wcj-currency=EUR', 'p=999']:
        status, hits = sentinel_request(capture_url, extra_headers={'Cookie':cookies+'; '+injected})
        check('cookie command/conflict denied before construction: '+injected, status == 403 and hits == '')
    php("file_put_contents('/tmp/ai-ui-coupon','yes');")
    try:
        status, hits = sentinel_request(capture_url)
        check('dynamic URL coupon disabled before native wp_loaded handlers', status == 403 and 'construction_reached' in hits and 'wp_loaded_reached' not in hits)
    finally:
        php("file_put_contents('/tmp/ai-ui-coupon','no');")
    app = json.loads(php("$u=get_user_by('login','store_owner');$r=WP_Application_Passwords::create_new_application_password($u->ID,['name'=>'Synthetic UI gate']);if(is_wp_error($r))exit(5);echo json_encode(['password'=>$r[0],'uuid'=>$r[1]['uuid'],'uid'=>$u->ID]);"))
    try:
        api = pw.request.new_context(http_credentials={'username':'store_owner','password':app['password'],'send':'always'})
        check('real Application Password authenticates REST', api.get(origin+'/?rest_route=/wp/v2/users/me').status == 200)
        check('Application Password cannot enter cookie-only page (no plan or nonce involved)', api.get(page_url).status == 403)
        check('Application Password alone cannot approve', api.post(post_url, form=dict(stale_apply, confirm='1')).status == 403)
        api.dispose()
    finally:
        php("WP_Application_Passwords::delete_application_password("+str(app['uid'])+",'"+app['uuid']+"');")
    response = context.request.get(capture_url)
    check('capture privacy headers', 'no-store' in response.headers.get('cache-control','') and response.headers.get('referrer-policy') == 'no-referrer')
    anonymous = pw.request.new_context()
    check('anonymous capture refused', anonymous.get(capture_url).status == 403)
    check('anonymous action refused', anonymous.post(post_url, form=bad).status == 403)
    anonymous.dispose()
    check('capture and report preserve Booster settings', settings() == before)
    browser.close()

result = {'kind': 'synthetic_loopback_browser_and_HTTP_not_genuine_AI_client', 'origin': origin, 'cases': cases,
          'artifacts': {p.name: hashlib.sha256(p.read_bytes()).hexdigest() for p in out.iterdir() if p.is_file()},
          'limitations': ['No owner usability approval', 'No ChatGPT or Codex MCP client', 'No supported-version or tier matrix', 'Preview flag explicitly enabled']}
(out / 'result.json').write_text(json.dumps(result, indent=2))
print(json.dumps({'passed': len(cases), 'evidence': str(out)}))

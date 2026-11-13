"""Native HTTP tests for the private C4b read-only synthetic client boundary.

Secrets stay in memory or a container-local 0600 file, never evidence/arguments.
Requires the reviewed exact package, official adapter and local-only sentinels.
This is not, by itself, a genuine Codex/ChatGPT client test.
"""
from pathlib import Path
import base64
import hashlib
import json
import subprocess
import sys
import uuid
from urllib.parse import urlsplit
import requests

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
    pre = "<?php define('WP_ADMIN',true);$_SERVER['REQUEST_METHOD']='GET';require '/var/www/html/wp-load.php';if(DB_NAME!=='synthetic_ai'||getenv('AI_ORIGIN')!==home_url())exit(3);"
    p = subprocess.run(['docker', 'exec', '-i', '--user', 'www-data', wp, 'php'], input=(pre + code).encode(), capture_output=True, timeout=60)
    assert p.returncode == 0, 'Fixture PHP failed; details withheld'
    return p.stdout.decode()


def check(name, ok):
    cases.append({'case': name, 'pass': bool(ok)})
    (out / 'cases.json').write_text(json.dumps(cases, indent=2))
    assert ok, name


def encoded(value):
    return "json_decode(base64_decode('" + base64.b64encode(json.dumps(value).encode()).decode() + "'),true)"


fixture = None
client_name = 'ai_client_' + uuid.uuid4().hex
session = requests.Session()
session.trust_env = False
session.headers.update({'Accept': 'application/json, text/event-stream', 'Content-Type': 'application/json', 'X-AI-Test': 'connection-diagnostic'})
endpoint = origin + '/?rest_route=/booster-ai/v1/mcp'
counter = 0


def rpc(method, params=None, client=session):
    global counter
    counter += 1
    body = {'jsonrpc': '2.0', 'id': counter, 'method': method}
    if params is not None:
        body['params'] = params
    response = client.post(endpoint, json=body, timeout=60, allow_redirects=False, stream=True)
    (out / ('rpc-' + str(counter) + '-framing.json')).write_text(json.dumps({'status': response.status_code, 'headers': {k: v for k, v in response.headers.items() if k.lower() in ['transfer-encoding','content-length','x-booster-fixture-relay']}, 'header_names': [repr(k) for k in response.headers]}))
    response.content
    return response


def grant(value):
    php("update_option('wcj_ai_client_grant_' . " + str(fixture['uid']) + ',wp_json_encode(' + encoded(value) + '),false);')


def settings():
    return php("$o=[];foreach(WCJ_AI_Invoice_Presentation::FIELDS as $f)$o[$f]=WCJ_AI_Connection::raw('wcj_invoicing_invoice_'.$f);echo wp_json_encode($o);")


def sentinel(url, client=session, **kwargs):
    php("file_put_contents('/tmp/ai-ui-sentinel','');")
    method = kwargs.pop('method', 'GET')
    response = client.request(method, url, headers={'X-AI-Test': 'sentinel', **kwargs.pop('headers', {})}, allow_redirects=False, timeout=60, **kwargs)
    hits = php("echo file_get_contents('/tmp/ai-ui-sentinel');")
    return response, hits


success = False
try:
    fixture = json.loads(php("""
    $n='CLIENT_NAME';if(get_user_by('login',$n))exit(9);$p=bin2hex(random_bytes(24));
    $id=wp_create_user($n,$p);if(is_wp_error($id))exit(4);
    $u=new WP_User($id);$u->set_role('');$u->add_cap('read');$u->add_cap('wcj_ai_client');
    update_user_meta($id,WCJ_AI_Connection::MARKER,'1');if('1'!==get_user_meta($id,WCJ_AI_Connection::MARKER,true))exit(8);
    $a=WP_Application_Passwords::create_new_application_password($id,['name'=>'C4b synthetic private read test']);if(is_wp_error($a))exit(5);
    $g=['schema'=>1,'id'=>bin2hex(random_bytes(16)),'principal'=>$id,'owner'=>get_user_by('login','store_owner')->ID,'site'=>get_current_blog_id(),'app_uuid'=>$a[1]['uuid'],'scopes'=>['invoice:read'],'created'=>time(),'expires'=>time()+3600,'active'=>true];
    update_option('wcj_ai_client_grant_'.$id,wp_json_encode($g),false);
    echo wp_json_encode(['login'=>$n,'password'=>$p,'app_password'=>$a[0],'uuid'=>$a[1]['uuid'],'uid'=>$id,'grant'=>$g]);
    """.replace("CLIENT_NAME", client_name)))
    session.auth = (fixture['login'], fixture['app_password'])
    before = settings()
    init = rpc('initialize', {'protocolVersion': '2025-11-25', 'capabilities': {}, 'clientInfo': {'name': 'booster-native-http-test', 'version': '1'}})
    (out / 'initialize-status.json').write_text(json.dumps({'status': init.status_code, 'content_type': init.headers.get('Content-Type'), 'body_bytes': len(init.content), 'json_code': init.json().get('code') if init.headers.get('Content-Type', '').startswith('application/json') else None}))
    check('native AP initializes exact private MCP endpoint', init.status_code == 200 and 'result' in init.json())
    session.headers['MCP-Session-Id'] = init.headers['Mcp-Session-Id']
    session.headers['MCP-Protocol-Version'] = init.json()['result']['protocolVersion']
    response = session.post(endpoint, json={'jsonrpc': '2.0', 'method': 'notifications/initialized'}, timeout=60, allow_redirects=False, stream=True)
    (out / 'notification-status.json').write_text(json.dumps({'status': response.status_code, 'headers': {k: response.headers.get(k) for k in ['Transfer-Encoding', 'Content-Length', 'Content-Encoding', 'Content-Type', 'X-Booster-Fixture-Relay']}}))
    response.content  # Require complete valid transport framing, including empty notifications.
    check('initialized notification accepted', response.status_code == 202)
    listing = rpc('tools/list')
    tools = listing.json().get('result', {}).get('tools', [])
    check('exactly two private read tools listed', listing.status_code == 200 and len(tools) == 2 and {x['name'] for x in tools} == {'booster-ai-schema', 'booster-ai-context'})
    expected = json.loads(php("echo wp_json_encode(['booster-ai-schema'=>WCJ_AI_Abilities::output_schema('schema'),'booster-ai-context'=>WCJ_AI_Abilities::output_schema('context')]);"))
    check('wire output schemas equal installed closed contracts', all(x.get('outputSchema') == expected[x['name']] for x in tools))
    (out / 'tool-contracts.json').write_text(json.dumps([{'name': x['name'], 'outputSchema': x['outputSchema']} for x in tools], indent=2))
    for name in ['schema', 'context']:
        response = rpc('tools/call', {'name': 'booster-ai-' + name, 'arguments': {}})
        result = response.json().get('result', {})
        check(name + ' executes with validated structured result', response.status_code == 200 and not result.get('isError') and result.get('structuredContent', {}).get('task') == 'invoice_presentation')
        value = result['structuredContent']
        validated = php("echo true===rest_validate_value_from_schema(" + encoded(value) + ",WCJ_AI_Abilities::output_schema('" + name + "'))?'valid':'invalid';")
        check(name + ' matches full native output contract before evidence', validated == 'valid')
        (out / (name + '.json')).write_text(json.dumps(value, indent=2))
    for label, auth in [('anonymous', None), ('wrong application password', (fixture['login'], 'wrong-synthetic-value')), ('normal principal password', (fixture['login'], fixture['password']))]:
        client = requests.Session()
        client.trust_env = False
        client.headers.update(session.headers)
        client.auth = auth
        response = rpc('tools/list', client=client)
        check(label + ' cannot reuse native MCP session', response.status_code in [401, 403])
    response, hits = sentinel(endpoint, method='POST', json={'jsonrpc': '2.0', 'id': 90, 'method': 'tools/list'})
    check('canonical AP positive control reaches both native phases', response.status_code == 200 and 'construction_reached' in hits and 'wp_loaded_reached' in hits)
    anonymous = requests.Session()
    anonymous.trust_env = False
    for label, path in [('ordinary search', '/?s=booster-ai'), ('ordinary product slug', '/?name=booster-ai-product')]:
        response, hits = sentinel(origin + path, client=anonymous)
        check(label + ' not captured by endpoint quarantine', response.status_code != 403 and 'construction_reached' in hits and 'wp_loaded_reached' in hits)
    for label, path in [('extra query', '/?rest_route=/booster-ai/v1/mcp&add-to-cart=10'), ('case alias', '/?rest_route=/Booster-AI/v1/mcp'), ('space alias', '/?rest_route=%20/booster-ai/v1/mcp%20'), ('alternate WP entry', '/wp-login.php?rest_route=/booster-ai/v1/mcp'), ('unrelated REST', '/?rest_route=/wp/v2/users/me'), ('core Woo REST', '/?rest_route=/wc/v3/products'), ('normal admin', '/wp-admin/admin.php'), ('XMLRPC', '/xmlrpc.php')]:
        response, hits = sentinel(origin + path)
        check(label + ' denied before native construction', response.status_code == 403 and hits == '')
    for label, options in [('origin', {'headers': {'Origin': origin}}), ('cookie', {'headers': {'Cookie': 'probe=1'}}), ('wrong content type', {'method': 'POST', 'data': '{}', 'headers': {'Content-Type': 'text/plain'}}), ('oversize JSON', {'method': 'POST', 'data': ' ' * 16385}), ('GET body', {'data': '{}'})]:
        response, hits = sentinel(endpoint, **options)
        check(label + ' denied before native construction', response.status_code == 403 and hits == '')
    for label, user in [('padded username', ' ' * 300 + fixture['login'] + ' '), ('markup username', '<b>' + fixture['login'] + '</b>')]:
        client = requests.Session()
        client.trust_env = False
        client.auth = (user, fixture['app_password'])
        response, hits = sentinel(origin + '/?rest_route=/wp/v2/users/me', client=client)
        check(label + ' hint denied before native construction', response.status_code == 403 and hits == '')
    for label, updates in [('revoked', {'active': False}), ('expired', {'expires': fixture['grant']['created'] - 1}), ('wrong scope', {'scopes': ['invoice:write']}), ('wrong native UUID', {'app_uuid': '00000000-0000-0000-0000-000000000000'}), ('wrong site', {'site': 999}), ('wrong owner', {'owner': fixture['uid']})]:
        grant(dict(fixture['grant'], **updates))
        response = rpc('tools/list')
        check(label + ' denies existing MCP session', response.status_code in [401, 403])
        grant(fixture['grant'])
    php('$u=new WP_User(' + str(fixture['uid']) + ");$u->add_role('administrator');echo user_can($u,'manage_woocommerce')?'elevated':'confined';")
    check('role drift cannot grant business capability', php('echo user_can(' + str(fixture['uid']) + ",'manage_woocommerce')?'elevated':'confined';") == 'confined')
    check('role drift revokes connection', rpc('tools/list').status_code in [401, 403])
    php('$u=new WP_User(' + str(fixture['uid']) + ");$u->remove_role('administrator');")
    for hook in ['pre_wp_load_alloptions', 'pre_cache_alloptions', 'alloptions']:
        php("file_put_contents('/tmp/ai-c4b-policy', '" + hook + "');")
        check(hook + ' priority-zero policy uncertainty denies', rpc('tools/list').status_code == 403)
        php("file_put_contents('/tmp/ai-c4b-policy', '');")
    check('restored policy accepts same native session', rpc('tools/list').status_code == 200)
    response = rpc('tools/call', {'name': 'booster-ai-context', 'arguments': {'option': 'blogname'}})
    check('extra tool input denied', 'error' in response.json() or response.json().get('result', {}).get('isError') is True)
    response = rpc('tools/call', {'name': 'booster-ai-apply', 'arguments': {}})
    check('unexposed write tool unavailable', 'error' in response.json() or response.json().get('result', {}).get('isError') is True)
    check('four business settings byte-state unchanged', settings() == before)
    success = True
finally:
    cleanup = []
    # Each cleanup action is independent and read back. This unique generated
    # account has no pre-existing credentials; never touch an owner account.
    cleanup_code = """
$u=get_user_by('login','CLIENT_NAME');
if(!$u){echo 'absent';return;}
if('1'!==get_user_meta($u->ID,WCJ_AI_Connection::MARKER,true)){echo 'unclassified';return;}
$raw=WCJ_AI_Connection::raw('wcj_ai_client_grant_'.$u->ID);
$g=$raw&&$raw['exists']?json_decode($raw['raw'],true):null;
if(is_array($g)){$g['active']=false;update_option('wcj_ai_client_grant_'.$u->ID,wp_json_encode($g),false);}
WP_Application_Passwords::delete_all_application_passwords($u->ID);
foreach($u->roles as$r)$u->remove_role($r);
$u->add_cap('read');$u->add_cap('wcj_ai_client');
clean_user_cache($u->ID);$u=new WP_User($u->ID);
$again=WCJ_AI_Connection::raw('wcj_ai_client_grant_'.$u->ID);
$g=$again&&$again['exists']?json_decode($again['raw'],true):null;
$ok=!$u->roles&&count($u->caps)===2&&true===($u->caps['read']??null)&&true===($u->caps['wcj_ai_client']??null)&&count(WP_Application_Passwords::get_user_application_passwords($u->ID))===0&&$again&&(!$again['exists']||(is_array($g)&&false===$g['active']));
echo $ok?'verified':'failed';
""".replace('CLIENT_NAME', client_name)
    for label, code, accepted in [
        ('unique fixture grant, APs and roles', cleanup_code, ['verified', 'absent']),
        ('policy instrumentation reset', "file_put_contents('/tmp/ai-c4b-policy','');echo file_get_contents('/tmp/ai-c4b-policy')===''?'verified':'failed';", ['verified'])
    ]:
        try:
            value = php(code)
            cleanup.append({'action': label, 'verified': value in accepted})
        except Exception:
            cleanup.append({'action': label, 'verified': False})
    clean = all(x['verified'] for x in cleanup)
    (out / 'result.json').write_text(json.dumps({'kind': 'Native HTTP adapter test; not genuine client proof', 'passed': success and clean, 'cases': cases, 'test_sha256': hashlib.sha256(Path(__file__).read_bytes()).hexdigest(), 'fixture_principal': fixture['uid'] if fixture else None, 'cleanup': cleanup}, indent=2))
    if not clean:
        raise RuntimeError('Fixture cleanup not verified; inspect local synthetic state')
print(json.dumps({'passed': len(cases), 'evidence': str(out)}))

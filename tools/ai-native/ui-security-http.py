"""Bounded HTTP security continuation for the exact synthetic C4a package.

This supplements, not replaces, the positive browser flow. Never prints secrets,
nonce URLs or page bodies. The fixture-only phase sentinels must be installed.
"""
from pathlib import Path
from html.parser import HTMLParser
from urllib.parse import urlsplit
import base64
import hashlib
import json
import subprocess
import sys
import requests

root=Path(sys.argv[1]).resolve();generation=sys.argv[2]
assert generation.replace('-','').isalnum()
identity=json.loads((root/'runtime-r2/local-identity.json').read_text())
assert identity['namespace']=='booster-ai-20261112-r2'
origin=identity['origin'];assert urlsplit(origin).hostname=='127.0.0.1'
wp=identity['namespace']+'-wp';out=root/'evidence/runtime'/generation;out.mkdir(parents=True,exist_ok=False)
cases=[]
def php(code):
 pre="define('WP_ADMIN',true);$_SERVER['REQUEST_METHOD']='GET';require '/var/www/html/wp-load.php';if(DB_NAME!=='synthetic_ai'||getenv('AI_ORIGIN')!==home_url())exit(3);"
 p=subprocess.run(['docker','exec','--user','www-data',wp,'php','-r',pre+code],capture_output=True,timeout=60)
 assert p.returncode==0,'Fixture PHP failed; details withheld'
 return p.stdout.decode()
def check(name,ok):
 cases.append({'case':name,'pass':bool(ok)});(out/'cases.json').write_text(json.dumps(cases,indent=2));assert ok,name
def login(name,password):
 s=requests.Session();s.get(origin+'/wp-login.php',timeout=60)
 s.post(origin+'/wp-login.php',data={'log':name,'pwd':password,'testcookie':'1'},allow_redirects=False,timeout=60)
 check('native login sets logged-in cookie for '+name,any(c.name.startswith('wordpress_logged_in_') for c in s.cookies))
 # PHP decodes Cookie header values before populating $_COOKIE.
 # Validate the actual returned login cookie with native WP/session tokens. Woo
 # intentionally redirects low-capability users away from wp-admin/profile.php.
 cookies=[c.value for c in s.cookies if c.name.startswith('wordpress_logged_in_')]
 assert len(cookies)==1
 encoded=base64.b64encode(json.dumps({'cookie':cookies[0],'login':name}).encode()).decode()
 code="<?php define('WP_ADMIN',true);$_SERVER['REQUEST_METHOD']='GET';require '/var/www/html/wp-load.php';if(DB_NAME!=='synthetic_ai'||getenv('AI_ORIGIN')!==home_url())exit(3);$v=json_decode(base64_decode('"+encoded+"'),true);$u=get_user_by('login',$v['login']);echo ($u&&wp_validate_auth_cookie(urldecode($v['cookie']),'logged_in')===$u->ID)?'verified':'rejected';"
 verified=subprocess.run(['docker','exec','-i','--user','www-data',wp,'php'],input=code.encode(),capture_output=True,timeout=60)
 check('native cookie verifies exact identity for '+name,verified.returncode==0 and verified.stdout==b'verified')
 return s
class Forms(HTMLParser):
 def __init__(self):super().__init__();self.forms=[];self.current=None
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='form':self.current={}
  elif tag=='input' and self.current is not None and a.get('type')=='hidden':self.current[a['name']]=a.get('value','')
 def handle_endtag(self,tag):
  if tag=='form' and self.current is not None:self.forms.append(self.current);self.current=None

password=subprocess.check_output(['docker','exec',wp,'cat','/tmp/ai-owner']).decode()
s=login('store_owner',password);page_url=origin+'/wp-admin/admin.php?page=wcj-ai-preview';post_url=origin+'/wp-admin/admin-post.php'
page=s.get(page_url,timeout=60);check('owner page admitted',page.status_code==200 and 'Booster AI' in page.text)
forms=Forms();forms.feed(page.text);launch=next(x for x in forms.forms if x.get('op')=='capture')
response=s.post(post_url,data=dict(launch,product='10'),allow_redirects=False,timeout=60)
check('canonical launch returns local capture',response.status_code==303 and response.headers.get('Location','').startswith(origin+'/?'))
capture=response.headers['Location']
response=s.get(capture,timeout=60);check('canonical native capture available',response.status_code==200 and 'wcj-ai-capture-title' in response.text)
def sent(url,data=None,headers=None,body=None):
 php("file_put_contents('/tmp/ai-ui-sentinel','');")
 h={'X-AI-Test':'sentinel',**(headers or {})}
 response=s.post(url,data=data,headers=h,allow_redirects=False,timeout=60) if data is not None else s.get(url,data=body,headers=h,allow_redirects=False,timeout=60)
 return response.status_code,php("echo file_get_contents('/tmp/ai-ui-sentinel');")
cookies='; '.join(c.name+'='+c.value for c in s.cookies)
status,hits=sent(capture,headers={'Cookie':cookies})
check('exact uninjected manual Cookie header is admitted',status==200 and 'construction_reached' in hits and 'wp_loaded_reached' in hits)
for label,url in [('extra cart input',capture+'&add-to-cart=10'),('duplicate field',capture+'&p=10'),('alternate WordPress entry',capture.replace(origin+'/?',origin+'/wp-login.php?'))]:
 status,hits=sent(url);check(label+' denied before construction',status==403 and hits=='')
status,hits=sent(capture,headers={'Content-Type':'application/json'},body='{"mutation":true}')
check('GET body denied before construction',status==403 and hits=='')
for action in ['wcj_ai_preview',' wcj_ai_preview ','<b>wcj_ai_preview</b>','wcj_ai_preview%20']:
 status,hits=sent(post_url,data=dict(launch,action=action,product='10',**{'wcj-currency':'EUR','wcj-currency-nonce':'synthetic'}))
 check('action alias/commands denied '+action.replace('<','[').replace('>',']'),status==403 and hits=='')
status,hits=sent(origin+'/wp-admin/admin.php?page=%2Fwcj-ai-preview%2F&wcj-currency=EUR')
check('page alias denied before construction',status==403 and hits=='')
for injected in ['wcj-currency=EUR','p=999']:
 status,hits=sent(capture,headers={'Cookie':cookies+'; '+injected})
 check('cookie command/conflict denied '+injected,status==403 and hits=='')
php("file_put_contents('/tmp/ai-ui-coupon','yes');")
try:
 status,hits=sent(capture)
 check('dynamic coupon rejected between construction and wp_loaded handlers',status==403 and 'construction_reached' in hits and 'wp_loaded_reached' not in hits)
finally:php("file_put_contents('/tmp/ai-ui-coupon','no');")
app=json.loads(php("$u=get_user_by('login','store_owner');$r=WP_Application_Passwords::create_new_application_password($u->ID,['name'=>'Synthetic UI boundary']);if(is_wp_error($r))exit(5);echo json_encode(['password'=>$r[0],'uuid'=>$r[1]['uuid'],'uid'=>$u->ID]);"))
try:
 api=requests.Session();api.auth=('store_owner',app['password'])
 authenticated=api.get(origin+'/?rest_route=/wp/v2/users/me',timeout=60)
 check('actual Application Password REST authentication',authenticated.status_code==200 and authenticated.json().get('id')==app['uid'])
 check('AP-only cookie page rejected without plan nonce',api.get(page_url,timeout=60).status_code==403)
 check('AP-only POST rejected (corroborating layered rejection)',api.post(post_url,data=dict(launch,product='10'),timeout=60).status_code==403)
finally:php("WP_Application_Passwords::delete_application_password("+str(app['uid'])+",'"+app['uuid']+"');")
for role in ['subscriber','shop_manager']:
 user=json.loads(php("$n='ai_probe_'.bin2hex(random_bytes(4));$p=bin2hex(random_bytes(24));$id=wp_create_user($n,$p);if(is_wp_error($id))exit(4);$u=new WP_User($id);$u->set_role('"+role+"');echo json_encode(['name'=>$n,'password'=>$p]);"))
 other=login(user['name'],user['password'])
 if role=='subscriber':check('real low-capability cookie denied',other.get(page_url,timeout=60).status_code==403)
 else:
  plan=php("echo json_decode(get_option('wcj_ai_invoice_plan_index','[]'),true)[0];")
  original=s.get(page_url+'&plan='+plan,timeout=60)
  check('original owner can inspect exact selected plan',original.status_code==200 and '<h3>Saved proposal' in original.text)
  response=other.get(page_url+'&plan='+plan,timeout=60)
  check('different merchant cannot inspect owner plan',response.status_code==200 and 'Invoice proposal unavailable' in response.text and '<h3>Saved proposal' not in response.text)
anon=requests.Session();check('anonymous capture rejected',anon.get(capture,timeout=60).status_code==403)
response=s.get(capture,timeout=60)
check('positive capture retains private headers',response.status_code==200 and 'no-store' in response.headers.get('Cache-Control','') and response.headers.get('Referrer-Policy')=='no-referrer')
result={'kind':'HTTP security continuation on unchanged exact C4a runtime, not a full browser rerun','cases':cases,'test_sha256':hashlib.sha256(Path(__file__).read_bytes()).hexdigest(),'scope':'Real native HTTP; sentinels prove phase arrival, not every helper or side effect; synthetic users retained; own temporary app password deleted.'}
(out/'result.json').write_text(json.dumps(result,indent=2));print(json.dumps({'passed':len(cases),'evidence':str(out)}))

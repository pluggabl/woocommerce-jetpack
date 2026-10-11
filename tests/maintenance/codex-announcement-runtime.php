<?php
// Run only inside this release's isolated synthetic WordPress cell.
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
$root = defined('WCJ_FREE_PLUGIN_PATH') ? WCJ_FREE_PLUGIN_PATH : WCJ_PLUGIN_PATH;
require_once $root . '/includes/admin/class-wcj-admin-announcement.php';
WCJ_Admin_Announcement::init();
$GLOBALS['admin_page_hooks']['wcj-dashboard'] = 'booster';
$cases = []; $users = []; $requests = [];
$cache = WCJ_Admin_Announcement::CACHE_KEY;
$valid = ['id'=>'qa-851-one','message'=>'QA announcement & store news','url'=>'https://booster.io/','expires_at'=>gmdate('Y-m-d\TH:i:s\Z',time()+86400)];
$body = json_encode(['announcement'=>$valid]); $responseCode=200; $failNetwork=false;
$filter = function($pre,$args,$url) use (&$requests,&$body,&$responseCode,&$failNetwork) {
    $requests[]=['url'=>$url,'args'=>$args];
    if ($failNetwork) return new WP_Error('qa-timeout','Synthetic failure');
    return ['headers'=>['content-type'=>'application/json'],'body'=>$body,'response'=>['code'=>$responseCode,'message'=>'Synthetic'],'cookies'=>[],'filename'=>null];
};
add_filter('pre_http_request',$filter,999,3);
function wcj_qa_assert($id,$actual,$expected=true) {
    global $cases;
    $pass=$actual===$expected;
    $cases[]=['id'=>$id,'expected'=>$expected,'actual'=>$actual,'status'=>$pass?'PASS':'FAIL'];
    if (!$pass) throw new RuntimeException('Assertion failed: '.$id);
}
function wcj_qa_render() { ob_start(); WCJ_Admin_Announcement::render(); return ob_get_clean(); }
function wcj_qa_page($page='wcj-dashboard',$screen=null) {
    $_GET=['page'=>$page];
    set_current_screen($screen ?? get_plugin_page_hookname($page,'wcj-dashboard'));
}
$original=get_current_user_id();
try {
    $roleUsers=[];
    foreach(['administrator','shop_manager','editor','author','contributor','subscriber','customer'] as $role) {
        $id=wp_insert_user(['user_login'=>'qa851_'.$role.'_'.wp_generate_password(6,false),'user_pass'=>wp_generate_password(32,true),'role'=>$role]);
        if(is_wp_error($id))throw new RuntimeException('Synthetic user creation failed');
        $users[]=$id;$roleUsers[$role]=$id;
    }
    $second=wp_insert_user(['user_login'=>'qa851_second_'.wp_generate_password(6,false),'user_pass'=>wp_generate_password(32,true),'role'=>'administrator']);$users[]=$second;
    foreach(array_merge(['guest'=>0],$roleUsers) as $role=>$id) {
        wp_set_current_user($id);wcj_qa_page();delete_transient($cache);$requests=[];
        $html=wcj_qa_render();$allowed=current_user_can('manage_woocommerce');
        wcj_qa_assert('role-visible-'.$role,strpos($html,'wcj-admin-announcement')!==false,$allowed);
        wcj_qa_assert('role-fetch-'.$role,count($requests),$allowed?1:0);
        set_transient($cache,$valid,DAY_IN_SECONDS);
        $nonce=wp_create_nonce('wcj-dismiss-announcement-'.$valid['id']);
        wcj_qa_assert('role-direct-sink-'.$role,WCJ_Admin_Announcement::dismiss($valid['id'],$nonce),$allowed);
    }
    wp_set_current_user($second);wcj_qa_page();delete_transient($cache);$requests=[];
    wcj_qa_assert('valid-feed-visible',strpos(wcj_qa_render(),'QA announcement &amp; store news')!==false);
    wcj_qa_assert('fetch-fixed-url',$requests[0]['url'],WCJ_Admin_Announcement::ENDPOINT);
    wcj_qa_assert('fetch-timeout',$requests[0]['args']['timeout'],2);
    wcj_qa_assert('fetch-no-redirect',$requests[0]['args']['redirection'],0);
    wcj_qa_assert('fetch-size-bound',$requests[0]['args']['limit_response_size'],4097);
    wcj_qa_assert('fetch-no-site-user-agent',$requests[0]['args']['user-agent'],'Booster-Announcement-Reader/1.0');
    wcj_qa_assert('fetch-no-cookies',$requests[0]['args']['cookies'],[]);
    wcj_qa_render();wcj_qa_assert('daily-cache-one-request',count($requests),1);
    $remaining=(int)get_option('_transient_timeout_'.$cache)-time();wcj_qa_assert('daily-cache-ttl',$remaining>=86395 && $remaining<=86400);
    foreach([['page'=>'wcj-dashboard','screen'=>'edit-post'],['page'=>'unrelated','screen'=>'toplevel_page_unrelated'],['page'=>'wc-settings','screen'=>'woocommerce_page_wc-settings','tab'=>'checkout']] as $i=>$page) {
        $_GET=['page'=>$page['page']];if(isset($page['tab']))$_GET['tab']=$page['tab'];set_current_screen($page['screen']);delete_transient($cache);$requests=[];
        wcj_qa_assert('unrelated-page-'.$i,wcj_qa_render(),'');wcj_qa_assert('unrelated-no-network-'.$i,count($requests),0);
    }
    $_GET=['page'=>'wc-settings','tab'=>'jetpack'];set_current_screen('woocommerce_page_wc-settings');delete_transient($cache);
    wcj_qa_assert('booster-wc-tab-visible',strpos(wcj_qa_render(),'wcj-admin-announcement')!==false);
    foreach(['wcj-dashboard','wcj-plugins','wcj-general-settings','wcj-getting-started','wcj-tools','wcj-license'] as $page){wcj_qa_page($page);wcj_qa_assert('owned-page-'.$page,strpos(wcj_qa_render(),'wcj-admin-announcement')!==false);}
    wcj_qa_page();set_transient($cache,$valid,DAY_IN_SECONDS);
    foreach([null,'',[],str_repeat('x',33),'wrong'] as $i=>$nonce) wcj_qa_assert('invalid-nonce-'.$i,WCJ_Admin_Announcement::dismiss($valid['id'],$nonce),false);
    $nonce=wp_create_nonce('wcj-dismiss-announcement-'.$valid['id']);
    foreach([null,'',[],str_repeat('a',81),'../id','QA-BAD','inactive-id'] as $i=>$id) wcj_qa_assert('invalid-id-'.$i,WCJ_Admin_Announcement::dismiss($id,$nonce),false);
    wp_set_current_user($roleUsers['administrator']);$foreign=wp_create_nonce('wcj-dismiss-announcement-'.$valid['id']);wp_set_current_user($second);
    wcj_qa_assert('foreign-user-nonce',WCJ_Admin_Announcement::dismiss($valid['id'],$foreign),false);
    $nonce=wp_create_nonce('wcj-dismiss-announcement-'.$valid['id']);wcj_qa_assert('own-dismissal',WCJ_Admin_Announcement::dismiss($valid['id'],$nonce));
    wcj_qa_assert('dismissal-reload',wcj_qa_render(),'');
    $edited=$valid;$edited['message']='Edited message';set_transient($cache,$edited,DAY_IN_SECONDS);wcj_qa_assert('same-id-edit-stays-dismissed',wcj_qa_render(),'');
    wp_set_current_user($roleUsers['shop_manager']);$new=$valid;$new['id']='qa-851-new';set_transient($cache,$new,DAY_IN_SECONDS);wcj_qa_assert('new-id-not-dismissed',strpos(wcj_qa_render(),'wcj-admin-announcement')!==false);
    wp_set_current_user($second);wcj_qa_assert('other-id-does-not-reset-old',WCJ_Admin_Announcement::dismiss($valid['id'],$nonce),false);
    $expired=$valid;$expired['expires_at']=gmdate('Y-m-d\TH:i:s\Z',time()-1);set_transient($cache,$expired,DAY_IN_SECONDS);wcj_qa_assert('expired-cache-hidden',wcj_qa_render(),'');
    $bad=[];
    foreach(['id'=>[null,[],str_repeat('x',81),'../bad'],'message'=>[null,[],str_repeat('x',501),'<script>alert(1)</script>',"bad\ntext",'   '],'url'=>[null,[],str_repeat('x',201),'http://booster.io/','https://booster.io.evil.invalid/','https://booster.io@evil.invalid/','https://booster.io/?utm_source=test','https://booster.io:443/','https://booster.io/%2f','javascript:alert(1)'],'expires_at'=>[null,[],'2099-02-31T00:00:00Z',gmdate('Y-m-d\TH:i:s\Z',time()-1)]] as $field=>$values)foreach($values as $value){$x=$valid;$x[$field]=$value;$bad[]=$x;}
    $x=$valid;$x['code']='must not execute';$bad[]=$x;
    foreach($bad as $i=>$item){$body=json_encode(['announcement'=>$item]);delete_transient($cache);wcj_qa_assert('malformed-field-'.$i,wcj_qa_render(),'');}
    foreach(['','{bad','[]','{"announcement":null}','{"announcement":{}}',str_repeat(' ',4097),json_encode(['announcement'=>$valid,'unexpected'=>true])] as $i=>$payload){$body=$payload;delete_transient($cache);wcj_qa_assert('malformed-envelope-'.$i,wcj_qa_render(),'');}
    $body=json_encode(['announcement'=>$new]);foreach([301,404,500] as $code){$responseCode=$code;delete_transient($cache);wcj_qa_assert('http-'.$code,wcj_qa_render(),'');}$responseCode=200;
    $failNetwork=true;delete_transient($cache);$requests=[];wcj_qa_assert('network-failure-silent',wcj_qa_render(),'');wcj_qa_render();wcj_qa_assert('negative-cache-one-request',count($requests),1);$failNetwork=false;
    set_transient($cache,[],DAY_IN_SECONDS);wcj_qa_assert('empty-feed-no-notice',wcj_qa_render(),'');
    WCJ_Admin_Announcement::init();wcj_qa_assert('single-render-hook',isset($GLOBALS['wp_filter']['admin_notices']) && false!==has_action('admin_notices',['WCJ_Admin_Announcement','render']));
    echo json_encode(['result'=>'PASS','wp'=>get_bloginfo('version'),'php'=>PHP_VERSION,'wc'=>defined('WC_VERSION')?WC_VERSION:null,'cases'=>$cases],JSON_PRETTY_PRINT);
} catch(Throwable $error){echo json_encode(['result'=>'FAIL','error'=>$error->getMessage(),'cases'=>$cases],JSON_PRETTY_PRINT);throw $error;}
finally {delete_transient($cache);remove_filter('pre_http_request',$filter,999);wp_set_current_user($original);foreach($users as $id)wp_delete_user($id);}

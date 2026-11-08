<?php
/** One shared toolbar category-links setting, with exact transactional recovery. @package Booster_For_WooCommerce/admin */
defined( 'ABSPATH' ) || exit;
final class WCJ_Toolbar_Categories_Job {
 const ID = 'admin_bar';
 const OPS = array('load','save','restore');
 const FIELDS = array('categories');
 public function __construct(){foreach(self::OPS as $op){add_action('wp_ajax_wcj_toolbar_categories_'.$op,array($this,'handle'));}}
 private static function fail($m,$code=422){throw new RuntimeException($m,$code);}
 private static function authorize(){if(!WCJ_Catalog_Admin_Workspace::allowed(self::ID)){self::fail('Ask an administrator for access to toolbar settings.',403);}}
 public static function resolve($type,$field){
  if('toolbar'!==$type||'categories'!==$field){self::fail('This task only shows or hides product category links in the toolbar.',403);}
  return 'wcj_admin_bar_wc_list_cats';
 }
 public static function plain($v){return is_string($v)&&in_array($v,array('yes','no'),true);}
 public static function stored_plain($v){return self::plain($v);}
 public static function valid_state($s){return null===$s||(is_array($s)&&array_keys($s)===array('raw','autoload')&&is_string($s['raw'])&&strlen($s['raw'])<=65536&&in_array($s['autoload'],array('yes','no','on','off','auto','auto-on','auto-off'),true));}
 private static function raw($c,$k,$default=null){return isset($c['rows'][$k])?$c['rows'][$k]['raw']:$default;}
 private static function state($c,$k){return $c['rows'][$k]??null;}
 private static function hash($c,$receipt=true){if(!$receipt){unset($c['rows'][$c['receipt_key']]);}return hash_hmac('sha256',serialize($c),wp_salt('nonce'));}
 private static function receipt_key($type,$field){return 'booster_toolbar_categories_'.get_current_user_id().'_'.$type.'_'.$field;}
 private static function context($type,$field,$lock=false){
  global $wpdb;$key=self::resolve($type,$field);$user=wp_get_current_user();$receipt=self::receipt_key($type,$field);
  $keys=array($key,$receipt,'wcj_admin_bar_enabled','wcj_admin_bar_wc_enabled','wcj_modules_by_user_roles_enabled',$wpdb->prefix.'user_roles','active_plugins','blog_charset');
  foreach($user->roles as $role){$keys[]='wcj_modules_by_user_roles_incl_'.$role;$keys[]='wcj_modules_by_user_roles_excl_'.$role;}
  $keys=array_values(array_unique($keys));sort($keys);$rows=$wpdb->get_results($wpdb->prepare("SELECT option_name,option_value,autoload FROM {$wpdb->options} WHERE option_name IN (".implode(',',array_fill(0,count($keys),'%s')).") ORDER BY option_name".($lock?' FOR UPDATE':''),$keys),ARRAY_A);
  if($wpdb->last_error||!is_array($rows)){self::fail('Current toolbar settings could not be safely read.',409);}
  $c=array('rows'=>array());$bytes=0;foreach($rows as $r){$bytes+=strlen($r['option_value']);$c['rows'][$r['option_name']]=array('raw'=>(string)$r['option_value'],'autoload'=>(string)$r['autoload']);}if($bytes>1000000){self::fail('The saved context needs full settings.');}ksort($c['rows']);
  $role_rows=$wpdb->get_results($wpdb->prepare("SELECT umeta_id,meta_key,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key IN (%s,%s) ORDER BY umeta_id".($lock?' FOR UPDATE':''),get_current_user_id(),$wpdb->prefix.'capabilities',$wpdb->prefix.'user_level'),ARRAY_A);
  if($wpdb->last_error||!is_array($role_rows)){self::fail('Role storage could not be verified.',409);}
  $c+=array('key'=>$key,'receipt_key'=>$receipt,'type'=>$type,'field'=>$field,'blog'=>get_current_blog_id(),'user'=>get_current_user_id(),'edition'=>self::edition(),'keys'=>$keys,'roles'=>$user->roles,'caps'=>$user->allcaps,'role_rows'=>$role_rows,'allowlist'=>function_exists('wcj_get_enabled_module_allowlist')?wcj_get_enabled_module_allowlist():null,'platform'=>self::platform(),'hooks'=>self::hooks($key,$receipt,$keys),'observers'=>self::known_observers($key),'global_update'=>self::global_update_filter($key),'receipt_observers'=>self::known_observers($receipt),'receipt_global_update'=>self::global_update_filter($receipt));
  return $c;
 }
 private static function hooks($key,$receipt,$keys){
  $out=array();foreach($keys as $k){foreach(array('','option_','pre_option_','default_option_','pre_update_option_','sanitize_option_','woocommerce_admin_settings_sanitize_option_') as $prefix){$hook=$prefix.$k;if(has_filter($hook)&&!('option_blog_charset'===$hook&&self::core_hook($hook)['safe'])){$out[]=$hook;}}}
  foreach(array($key,$receipt) as $k){foreach(array('update_option_','add_option_','delete_option_') as $p){if(has_filter($p.$k)){$out[]=$p.$k;}}}
  $known=self::known_observers($key);foreach(array('pre_option','pre_wp_load_alloptions','pre_cache_alloptions','alloptions','woocommerce_admin_settings_sanitize_option','woocommerce_update_option_text','woocommerce_update_option','update_option','updated_option','add_option','added_option','delete_option','deleted_option','sanitize_text_field') as $hook){if(has_filter($hook)&&(!isset($known[$hook])||!$known[$hook]['safe'])){$out[]=$hook;}}sort($out);return $out;
 }
 private static function refusal($c){
  if('yes'!==self::raw($c,'wcj_admin_bar_enabled')||'yes'!==self::raw($c,'wcj_admin_bar_wc_enabled','yes')){return 'Turn on Admin Bar and its WooCommerce menu in full settings first. This task enables neither.';}
  if(!$c['platform']['safe']||!$c['global_update']['safe']||!$c['receipt_global_update']['safe']||$c['hooks']){return 'An integration or changed installation needs full settings. Your saved setting is kept unchanged.';}
  foreach(array_merge(array_values($c['observers']),array_values($c['receipt_observers'])) as $o){if(!$o['safe']){return 'A settings observer needs full settings. Nothing was changed.';}}
  if(!$c['roles']||!$c['role_rows']){return 'Current role storage could not be qualified.';}
  foreach($c['rows'] as $s){if(!self::valid_state($s)){return 'An unsupported stored value needs full settings.';}}
  $s=self::state($c,$c['key']);if(null!==$s&&!self::stored_plain($s['raw'])){return 'This saved setting is unsupported here. Use full settings; it was kept unchanged.';}
  if(!in_array(self::raw($c,'blog_charset'),array('UTF-8','utf-8','UTF8','utf8'),true)){return 'Use full settings for this site character set.';}
  $why=self::transaction_entry_reason();return $why?$why:self::storage_reason();
 }
 private static function record($c){
  $raw=self::raw($c,$c['receipt_key']);if(!is_string($raw)||strlen($raw)>20000){return null;}$r=@unserialize($raw,array('allowed_classes'=>false,'max_depth'=>5));
  if(!is_array($r)||serialize($r)!==$raw||!is_string($r['signature']??null)){return null;}$sig=$r['signature'];unset($r['signature']);if(!hash_equals(hash_hmac('sha256',serialize($r),wp_salt('nonce')),$sig)){return null;}$r['signature']=$sig;
  if(1!==($r['version']??null)||$c['blog']!==($r['blog']??null)||$c['user']!==($r['user']??null)||$c['edition']!==($r['edition']??null)||$c['key']!==($r['key']??null)||$c['type']!==($r['type']??null)||$c['field']!==($r['field']??null)||!array_key_exists('before',$r)||!array_key_exists('after',$r)||!self::valid_state($r['before'])||!self::valid_state($r['after'])||!is_string($r['after_guard']??null)||!is_string($r['operation']??null)||!preg_match('/\A[a-f0-9]{32}\z/D',$r['operation'])||!is_int($r['expires']??null)||!in_array($r['status']??null,array('saved','restored'),true)){return null;}return $r;
 }
 private static function can_restore($c,$r){return is_array($r)&&'saved'===$r['status']&&$r['expires']>=time()&&self::state($c,$c['key'])===$r['after']&&hash_equals(self::hash($c,false),$r['after_guard']);}
 private static function view($c){$s=self::state($c,$c['key']);$why=self::refusal($c);$r=self::record($c);$plain=null===$s||self::stored_plain($s['raw']);return array('type'=>$c['type'],'field'=>$c['field'],'key'=>$c['key'],'guard'=>self::hash($c),'exists'=>null!==$s,'value'=>null!==$s&&$plain?$s['raw']:'','saved'=>!$plain?'Existing value needs full settings':(null===$s?'Not set (default: Off)':('yes'===$s['raw']?'Saved: On':'Saved: Off')),'editable'=>''===$why,'reason'=>$why,'recovery_reason'=>null!==self::raw($c,$c['receipt_key'])&&!$r?'If Booster cannot check your previous save, Restore is unavailable. You can make a new save, but it will not bring back that earlier change.':'','receipt_token'=>$r?$r['signature']:'','restore'=>!$why&&self::can_restore($c,$r),'receipt_status'=>$r?$r['status']:'','receipt_current'=>$r&&self::state($c,$c['key'])===$r['after']);}
 public static function load($type,$field){self::authorize();return self::view(self::context($type,$field));}
 private static function write($c,$key,$after){
  global $wpdb;if(!in_array($key,array($c['key'],$c['receipt_key']),true)||!self::valid_state($after)){self::fail('Unexpected write target or state.',500);}$before=self::state($c,$key);if($before===$after){return;}
  if(null===$before){$n=$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,%s)",$key,$after['raw'],$after['autoload']));}
  elseif(null===$after){$n=$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND BINARY option_value=%s AND BINARY autoload=%s",$key,$before['raw'],$before['autoload']));}
  else{$n=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=%s,autoload=%s WHERE option_name=%s AND BINARY option_value=%s AND BINARY autoload=%s",$after['raw'],$after['autoload'],$key,$before['raw'],$before['autoload']));}
  if(1!==$n){self::fail('A concurrent change or storage error stopped this edit.',409);}
 }
 private static function clear_cache($keys){$core=WCJ_Job_Store::core();foreach($keys as $k){wp_cache_delete($k,'options');if($core){unset($core->options[$k]);}}wp_cache_delete('alloptions','options');wp_cache_delete('notoptions','options');}
 public static function mutate($type,$field,$guard,$value,$receipt='',$restore=false){
  global $wpdb;self::authorize();$key=self::resolve($type,$field);if(!is_string($guard)||!preg_match('/\A[a-f0-9]{64}\z/D',$guard)){self::fail('Load this field before changing it.',409);}$why=self::storage_reason();if($why){self::fail($why);}
  $why=self::transaction_entry_reason();if($why){self::fail($why,409);}$receipt_key=self::receipt_key($type,$field);$lock='wcj_toolbar_categories_'.substr(hash('sha256',$wpdb->prefix.DB_NAME),0,40);if('1'!==(string)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,5)',$lock))){self::fail('Another toolbar preference edit is running. Your draft is retained.',409);}$begun=false;
  try{
   if(false===$wpdb->query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE')){self::fail('A surrounding transaction or unsupported database prevents this task. Nothing was changed.',409);}if(false===$wpdb->query('START TRANSACTION')){self::fail('A safe transaction could not begin.',500);}$begun=true;$c=self::context($type,$field,true);self::authorize();if(!hash_equals(self::hash($c),$guard)){self::fail('Current settings changed. Your draft is retained; reload deliberately before saving.',409);}$why=self::refusal($c);if($why){self::fail($why);}$before=self::state($c,$key);
   if($restore){$r=self::record($c);if(!self::can_restore($c,$r)||!is_string($receipt)||!hash_equals($r['signature'],$receipt)){self::fail('This previous value no longer matches the latest eligible task change. The current setting was kept.',409);}$after=$r['before'];}
   else{if(!self::plain($value)){self::fail('Choose On or Off. Your saved setting was not changed.');}$after=array('raw'=>$value,'autoload'=>null===$before?'off':$before['autoload']);}
   if($before===$after){return self::view($c);}
   self::write($c,$key,$after);$expected=$c;if(null===$after){unset($expected['rows'][$key]);}else{$expected['rows'][$key]=$after;}ksort($expected['rows']);$check=self::context($type,$field,true);if(!hash_equals(self::hash($expected),self::hash($check))){self::fail('Exact readback changed. This transaction was not accepted.',409);}
   $r=array('version'=>1,'blog'=>$c['blog'],'user'=>$c['user'],'edition'=>$c['edition'],'type'=>$type,'field'=>$field,'key'=>$key,'before'=>$before,'after'=>$after,'status'=>$restore?'restored':'saved','operation'=>bin2hex(random_bytes(16)),'expires'=>time()+7*DAY_IN_SECONDS,'after_guard'=>self::hash($check,false));$r['signature']=hash_hmac('sha256',serialize($r),wp_salt('nonce'));
   $rs=array('raw'=>serialize($r),'autoload'=>null===self::state($c,$receipt_key)?'off':$c['rows'][$receipt_key]['autoload']);self::write($check,$receipt_key,$rs);$expected['rows'][$receipt_key]=$rs;ksort($expected['rows']);$verified=self::context($type,$field,true);self::authorize();if(!hash_equals(self::hash($expected),self::hash($verified))||!self::record($verified)){self::fail('Recovery receipt readback failed. This transaction was not accepted.',409);}
   $commit=$wpdb->query('COMMIT');$begun=false;self::clear_cache(array($key,$receipt_key));if(false===$commit){self::fail('Commit outcome is unknown. Reload this field before another change.',500);}$fresh=self::context($type,$field);self::authorize();if(!hash_equals(self::hash($verified),self::hash($fresh))){self::fail('The edit committed but current readback changed. Reload before another change.',409);}return self::view($fresh);
  }finally{if($begun){$ok=$wpdb->query('ROLLBACK');self::clear_cache(array($key,$receipt_key));if(false===$ok){$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));self::fail('Rollback outcome could not be verified. Reload before another change.',500);}}$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));}
 }
 public function handle(){try{
  if('POST'!==($_SERVER['REQUEST_METHOD']??'')||!empty($_FILES)){self::fail('Use the toolbar preference form.',403);}self::authorize();$action=$_POST['action']??'';$op=is_string($action)?substr($action,strlen('wcj_toolbar_categories_')):'';if(!in_array($op,self::OPS,true)||'wcj_toolbar_categories_'.$op!==$action){self::fail('Unknown operation.',403);}
  $want=array('action','nonce','type','field');if('load'!==$op){$want[]='guard';$want[]='restore'===$op?'receipt':'value';}$actual=array_keys($_POST);sort($want);sort($actual);if($want!==$actual){self::fail('Unexpected form fields.',403);}foreach($_POST as $v){if(!is_string($v)||strlen($v)>2000){self::fail('Invalid form field.',403);}}
  if(!wp_verify_nonce(wp_unslash($_POST['nonce']),'wcj_toolbar_categories_'.$op)){self::fail('This form token expired. Reload deliberately; your draft is retained.',403);}$type=wp_unslash($_POST['type']);$field=wp_unslash($_POST['field']);$r='load'===$op?self::load($type,$field):self::mutate($type,$field,wp_unslash($_POST['guard']),'save'===$op?wp_unslash($_POST['value']):'','restore'===$op?wp_unslash($_POST['receipt']):'','restore'===$op);nocache_headers();wp_send_json_success($r);
 }catch(Throwable $e){$code=in_array($e->getCode(),array(403,409,422),true)?$e->getCode():500;nocache_headers();wp_send_json_error(array('message'=>500===$code?'The outcome could not be verified. Your draft is retained. Reload deliberately before another change.':$e->getMessage()),$code);}}
 public static function edition() { return defined( 'WCJ_PLUGIN_FILE' ) && 'booster-elite-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE ) ? 'elite' : ( defined( 'WCJ_PLUGIN_FILE' ) && 'booster-plus-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE ) ? 'plus' : 'free' ); }

private static function observer_specs() { return array(
array( 'hook' => 'added_option', 'priority' => 999, 'accepted_args' => 2, 'class' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer', 'method' => 'process_added_option', 'file_sha256' => 'a431e293f882f6ed0517de243856bff419454888d65d1c0e5779ad7959abc594', 'body_sha256' => 'c4e3e8507654fbd1e9ef66f3be358443e8f60900b7d4cb2ed3826224bb097580', 'relative' => 'src/Internal/DataStores/Orders/DataSynchronizer.php', 'static' => false, 'parameters' => 2, 'declaring' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer' ),
array( 'hook' => 'added_option', 'priority' => 999, 'accepted_args' => 3, 'class' => 'Automattic\\WooCommerce\\Internal\\Features\\FeaturesController', 'method' => 'process_added_option', 'file_sha256' => '667bd335a443c93e9746d8be05309b98877cc4498888fdb91fe93a13a8b30348', 'body_sha256' => 'dcbe8f8b65af325a59eb4bbad5f45239b9e2bd20a72a05e5f29c1f1c37cb4681', 'relative' => 'src/Internal/Features/FeaturesController.php', 'static' => false, 'parameters' => 2, 'declaring' => 'Automattic\\WooCommerce\\Internal\\Features\\FeaturesController' ),
array( 'hook' => 'delete_option', 'priority' => 10, 'accepted_args' => 1, 'class' => 'Automattic\\WooCommerce\\Internal\\Admin\\Schedulers\\OrdersScheduler', 'method' => 'handle_scheduled_import_option_before_delete', 'file_sha256' => '349ed8a1a046baba4d3419335b089dbb96115b7f7de0b80718d7ca734fb0b6e6', 'body_sha256' => 'f967929b70dc4e5af314cc83769a8281c64b409ca781fb9e89c19aa108353a19', 'relative' => 'src/Internal/Admin/Schedulers/OrdersScheduler.php', 'static' => true, 'parameters' => 1, 'declaring' => 'Automattic\\WooCommerce\\Internal\\Admin\\Schedulers\\OrdersScheduler' ),
array( 'hook' => 'deleted_option', 'priority' => 999, 'accepted_args' => 1, 'class' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer', 'method' => 'process_deleted_option', 'file_sha256' => 'a431e293f882f6ed0517de243856bff419454888d65d1c0e5779ad7959abc594', 'body_sha256' => '7adc1cc398895b312649c78d625a7cbc18cb2c2da7ea0a095e3e4423681425dd', 'relative' => 'src/Internal/DataStores/Orders/DataSynchronizer.php', 'static' => false, 'parameters' => 1, 'declaring' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer' ),
array( 'hook' => 'updated_option', 'priority' => 999, 'accepted_args' => 3, 'class' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\CustomOrdersTableController', 'method' => 'process_updated_option', 'file_sha256' => 'b4d1a6772b064de9be6a80750074b0a9e371514f58131a1701cad6cd52ccb8bf', 'body_sha256' => 'a3bf19beace7a327b75aaea6f5da138ccf3b5fea29d3b752d9daca38734e761e', 'relative' => 'src/Internal/DataStores/Orders/CustomOrdersTableController.php', 'static' => false, 'parameters' => 3, 'declaring' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\CustomOrdersTableController' ),
array( 'hook' => 'updated_option', 'priority' => 999, 'accepted_args' => 3, 'class' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\CustomOrdersTableController', 'method' => 'process_updated_option_fts_index', 'file_sha256' => 'b4d1a6772b064de9be6a80750074b0a9e371514f58131a1701cad6cd52ccb8bf', 'body_sha256' => 'c8c1773ad069b3195c036ed8ff4c6fa6707fb92f27f1b324accf4677cb42585b', 'relative' => 'src/Internal/DataStores/Orders/CustomOrdersTableController.php', 'static' => false, 'parameters' => 3, 'declaring' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\CustomOrdersTableController' ),
array( 'hook' => 'updated_option', 'priority' => 999, 'accepted_args' => 3, 'class' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer', 'method' => 'process_updated_option', 'file_sha256' => 'a431e293f882f6ed0517de243856bff419454888d65d1c0e5779ad7959abc594', 'body_sha256' => 'db93170a25602ba9593d933c5d84c7fa047261521da4df584d55401afe631737', 'relative' => 'src/Internal/DataStores/Orders/DataSynchronizer.php', 'static' => false, 'parameters' => 3, 'declaring' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer' ),
array( 'hook' => 'updated_option', 'priority' => 999, 'accepted_args' => 3, 'class' => 'Automattic\\WooCommerce\\Internal\\Features\\FeaturesController', 'method' => 'process_updated_option', 'file_sha256' => '667bd335a443c93e9746d8be05309b98877cc4498888fdb91fe93a13a8b30348', 'body_sha256' => 'e24cce741c42df41d9a0b1ba6919532101adfc7b6d6e6658210b804f0f7b4f74', 'relative' => 'src/Internal/Features/FeaturesController.php', 'static' => false, 'parameters' => 3, 'declaring' => 'Automattic\\WooCommerce\\Internal\\Features\\FeaturesController' ),
array( 'hook' => 'woocommerce_admin_settings_sanitize_option', 'priority' => 9223372036854775807, 'accepted_args' => 3, 'class' => 'WCJ_Admin', 'method' => 'unclean_custom_textarea', 'file_sha256' => array( 'free' => 'aa10daca2e5e97a3ce75a001f59a0883d2a90c7a0f7a6f6827a18c3d3da55bfa', 'plus' => 'c60626022c4b66367ddf9f39b41122b5aa5bca4692fb36484faaeea10d6cf8e6', 'elite' => '85d3bdcb59405e1c8b8342c5efeb3bef88305aedb30d9675de0edbff010297c6' ), 'body_sha256' => 'ed73b9570822d3aa5a04b131184c37c45d7e8ca84df9cfa09d7a2231c47f7772', 'relative' => 'native:includes/core/class-wcj-admin.php', 'static' => false, 'parameters' => 3, 'declaring' => 'WCJ_Admin' ),
array( 'hook' => 'woocommerce_admin_settings_sanitize_option', 'priority' => 9223372036854775807, 'accepted_args' => 3, 'class' => 'WCJ_Admin', 'method' => 'maybe_unclean_field', 'file_sha256' => array( 'free' => 'aa10daca2e5e97a3ce75a001f59a0883d2a90c7a0f7a6f6827a18c3d3da55bfa', 'plus' => 'c60626022c4b66367ddf9f39b41122b5aa5bca4692fb36484faaeea10d6cf8e6', 'elite' => '85d3bdcb59405e1c8b8342c5efeb3bef88305aedb30d9675de0edbff010297c6' ), 'body_sha256' => '761ec28ed524a23788bcbdfb60d319072f090ef5230dd0121d9680bf41829b5b', 'relative' => 'native:includes/core/class-wcj-admin.php', 'static' => false, 'parameters' => 3, 'declaring' => 'WCJ_Admin' ),
 ); }

private static function observer_identity( $entry, $priority, $spec ) {
  $f = $entry['function'] ?? null;
  if ( $spec['priority'] !== (int) $priority || $spec['accepted_args'] !== ( $entry['accepted_args'] ?? null ) || ! is_array( $f ) || array_keys( $f ) !== array( 0, 1 ) || $spec['method'] !== $f[1] ) { return false; }
  if ( $spec['static'] ? ( ! is_string( $f[0] ) || $spec['class'] !== $f[0] || ! class_exists( $f[0], false ) ) : ( ! is_object( $f[0] ) || $spec['class'] !== get_class( $f[0] ) ) ) { return false; }
  $native = 0 === strpos( $spec['relative'], 'native:' );
  if ( ! $native && ! defined( 'WC_ABSPATH' ) ) { return false; }
  $file = realpath( $native ? dirname( __DIR__, 2 ) . '/' . substr( $spec['relative'], 7 ) : WC_ABSPATH . $spec['relative'] );
  $sha = is_array( $spec['file_sha256'] ) ? $spec['file_sha256'][ self::edition() ] : $spec['file_sha256'];
  if ( ! $file || ! is_readable( $file ) || hash_file( 'sha256', $file ) !== $sha ) { return false; }
  $m = new ReflectionMethod( $f[0], $f[1] );
  if ( realpath( $m->getFileName() ) !== $file || $m->getDeclaringClass()->getName() !== $spec['declaring'] || ! $m->isPublic() || $m->isStatic() !== $spec['static'] || $m->getNumberOfParameters() !== $spec['parameters'] ) { return false; }
  $body_sha = is_array( $spec['body_sha256'] ) ? $spec['body_sha256'][ self::edition() ] : $spec['body_sha256'];
  $lines = file( $file, FILE_IGNORE_NEW_LINES );
  if ( ! is_array( $lines ) || hash( 'sha256', implode( "\n", array_slice( $lines, $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1 ) ) ) !== $body_sha ) { return false; }
  return array( $spec['class'], $spec['method'], $spec['priority'], $spec['accepted_args'], $spec['relative'], $sha, $body_sha );
 }

private static function observer_constants( $class, $values, $write_keys ) {
  if ( ! class_exists( $class, false ) ) { return false; }
  $out = array();
  foreach ( $values as $name => $expected ) {
   if ( ! defined( $class . '::' . $name ) ) { return false; }
   $c = new ReflectionClassConstant( $class, $name ); $actual = $c->getValue();
   if ( $c->getDeclaringClass()->getName() !== $class || $actual !== $expected || in_array( $actual, $write_keys, true ) ) { return false; }
   $out[ $name ] = $actual;
  }
  return $out;
 }

private static function feature_observer_context( $object, $write_keys ) {
  $class = 'Automattic\WooCommerce\Internal\Features\FeaturesController';
  if ( ! is_object( $object ) || get_class( $object ) !== $class || has_filter( 'woocommerce_register_feature_definitions' ) ) { return false; }
  $state = array();
  foreach ( array( 'features', 'registered_additional_features_via_class_calls', 'registered_additional_features_via_action' ) as $name ) {
   $p = new ReflectionProperty( $class, $name );
   if ( $p->getDeclaringClass()->getName() !== $class || ! $p->isPrivate() || ! $p->isInitialized( $object ) ) { return false; }
   $state[ $name ] = $p->getValue( $object );
  }
  if ( true !== $state['registered_additional_features_via_class_calls'] || true !== $state['registered_additional_features_via_action'] || ! is_array( $state['features'] ) || ! $state['features'] || count( $state['features'] ) > 256 ) { return false; }
  $keys = array();
  foreach ( $write_keys as $key ) { if ( preg_match( '/^woocommerce_feature_([a-zA-Z0-9_]+)_enabled$/D', $key ) ) { return false; } }
  foreach ( $state['features'] as $id => $feature ) {
   if ( ! is_string( $id ) || ! preg_match( '/^[a-zA-Z0-9_-]{1,100}$/D', $id ) || ! is_array( $feature ) ) { return false; }
   $present = array_key_exists( 'option_key', $feature ); $key = $present ? $feature['option_key'] : null;
   if ( null !== $key && ( ! is_string( $key ) || strlen( $key ) > 191 || ( '' !== $key && ! preg_match( '/^[a-zA-Z0-9_-]+$/D', $key ) ) ) ) { return false; }
   if ( in_array( $key, $write_keys, true ) ) { return false; }
   $keys[ $id ] = array( 'present' => $present, 'option_key' => $key );
  }
  ksort( $keys );
  // Only this projection influences the pinned callback before it returns for our two keys.
  return array( 'class_calls_initialized' => true, 'action_initialized' => true, 'registration_hook' => 'absent', 'keys' => $keys );
 }

private static function observer_hook( $hook, $group ) {
  global $wp_filter;
  $unknown = array( 'safe' => false, 'binding' => 'unqualified' );
  if ( ! has_filter( $hook ) ) { return array( 'safe' => true, 'binding' => 'absent' ); }
  if ( ! isset( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof WP_Hook || ! is_array( $wp_filter[ $hook ]->callbacks ) ) { return $unknown; }
  $write_keys = array( $group ); $bindings = array(); $seen = array();
  try {
   foreach ( $wp_filter[ $hook ]->callbacks as $priority => $entries ) {
    if ( ! is_array( $entries ) ) { return $unknown; }
    foreach ( $entries as $entry ) {
     if ( ! is_array( $entry ) ) { return $unknown; } $matched = false;
     foreach ( self::observer_specs() as $spec ) {
      if ( $spec['hook'] !== $hook ) { continue; } $identity = self::observer_identity( $entry, $priority, $spec );
      if ( false === $identity ) { continue; }
      $id = $spec['class'] . '::' . $spec['method']; if ( isset( $seen[ $id ] ) ) { return $unknown; } $seen[ $id ] = true;
      $extra = array(); $class = $spec['class']; $method = $spec['method'];
      if ( 'Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer' === $class ) {
       $extra = self::observer_constants( $class, array( 'ORDERS_DATA_SYNC_ENABLED_OPTION' => 'woocommerce_custom_orders_table_data_sync_enabled', 'BACKGROUND_SYNC_MODE_OPTION' => 'woocommerce_custom_orders_table_background_sync_mode' ), $write_keys );
      } elseif ( 'Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' === $class ) {
       $extra = self::observer_constants( $class, array( 'HPOS_DATASTORE_CACHING_ENABLED_OPTION' => 'woocommerce_hpos_datastore_caching_enabled', 'HPOS_FTS_INDEX_OPTION' => 'woocommerce_hpos_fts_index_enabled' ), $write_keys );
       $sync = self::observer_constants( 'Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer', array( 'ORDERS_DATA_SYNC_ENABLED_OPTION' => 'woocommerce_custom_orders_table_data_sync_enabled' ), $write_keys );
       if ( false === $sync || false === $extra ) { return $unknown; } $extra['sync'] = $sync;
      } elseif ( 'Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler' === $class ) {
       $extra = self::observer_constants( $class, array( 'SCHEDULED_IMPORT_OPTION' => 'woocommerce_analytics_scheduled_import' ), $write_keys );
      } elseif ( 'Automattic\WooCommerce\Internal\Features\FeaturesController' === $class ) {
       $extra = self::feature_observer_context( $entry['function'][0], $write_keys );
      } elseif ( 'WCJ_Admin' === $class ) {
       // This task writes only its validated yes/no scalar; no native text sanitizer is invoked.
       // The signed receipt is not a Woo settings field and never enters this sanitizer.
       $extra = array( 'field' => array( 'id' => $write_keys[0], 'type' => 'checkbox', 'wcj_raw' => 'absent' ) );
      } else { return $unknown; }
      if ( false === $extra ) { return $unknown; }
      $bindings[] = array( 'identity' => $identity, 'context' => $extra, 'write_keys' => $write_keys ); $matched = true; break;
     }
     if ( ! $matched ) { return $unknown; }
    }
   }
  } catch ( Throwable $e ) { return $unknown; }
  return $bindings ? array( 'safe' => true, 'binding' => $bindings ) : $unknown;
 }

private static function known_observers( $group ) {
  $out = array(); foreach ( array( 'added_option', 'delete_option', 'deleted_option', 'updated_option', 'woocommerce_admin_settings_sanitize_option' ) as $hook ) { $out[ $hook ] = self::observer_hook( $hook, $group ); } return $out;
 }

private static function global_update_filter( $g ) {
		global $wp_filter;
		if ( ! has_filter( 'pre_update_option' ) ) { return array( 'safe' => true, 'binding' => 'absent' ); }
		$unknown = array( 'safe' => false, 'binding' => 'unqualified' );
		if ( ! isset( $wp_filter['pre_update_option'] ) || ! $wp_filter['pre_update_option'] instanceof WP_Hook || ! is_array( $wp_filter['pre_update_option']->callbacks ) || ! defined( 'WC_ABSPATH' ) ) { return $unknown; }
		$class = 'Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController';
		$sync = 'Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer';
		$bindings = array();
		try {
			foreach ( $wp_filter['pre_update_option']->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $entry ) {
					$f = $entry['function'] ?? null;
					if ( 999 !== (int) $priority || 3 !== ( $entry['accepted_args'] ?? null ) || ! is_array( $f ) || 2 !== count( $f ) || ! isset( $f[0], $f[1] ) || ! is_object( $f[0] ) || get_class( $f[0] ) !== $class || 'process_pre_update_option' !== $f[1] || ! class_exists( $sync, false ) ) { return $unknown; }
					$method = new ReflectionMethod( $f[0], $f[1] );
					$file = realpath( WC_ABSPATH . 'src/Internal/DataStores/Orders/CustomOrdersTableController.php' );
					if ( false === $file || realpath( $method->getFileName() ) !== $file || $method->getDeclaringClass()->getName() !== $class || ! $method->isPublic() || $method->isStatic() || 3 !== $method->getNumberOfParameters() || ! is_readable( $file ) ) { return $unknown; }
					$lines = file( $file, FILE_IGNORE_NEW_LINES );
					if ( ! is_array( $lines ) ) { return $unknown; }
					$body = implode( "\n", array_slice( $lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1 ) );
					$fingerprint = hash( 'sha256', $body );
					if ( '45c08141fc7a2f7977c41f668cd71923bff54bce974f4793c71fe67c4ea4235a' !== $fingerprint || ! defined( $class . '::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION' ) || ! defined( $sync . '::ORDERS_DATA_SYNC_ENABLED_OPTION' ) ) { return $unknown; }
					$usage = constant( $class . '::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION' ); $sync_option = constant( $sync . '::ORDERS_DATA_SYNC_ENABLED_OPTION' );
					if ( 'woocommerce_custom_orders_table_enabled' !== $usage || 'woocommerce_custom_orders_table_data_sync_enabled' !== $sync_option || array_intersect( array( $usage, $sync_option ), array( $g ) ) ) { return $unknown; }
					$bindings[] = array( $class, $f[1], 999, 3, $fingerprint, $usage, $sync_option );
				}
			}
		} catch ( Throwable $e ) { return $unknown; }
		return $bindings ? array( 'safe' => true, 'binding' => hash( 'sha256', serialize( $bindings ) ) ) : $unknown;
	}

private static function storage_reason() {
  global $wpdb;
  if ( PHP_INT_SIZE < 8 || wp_using_ext_object_cache() ) { return 'This task needs 64-bit PHP and nonpersistent option caches. Use full settings.'; }
  if ( is_multisite() ) { return 'Network-wide plugin and role context is outside this shortcut. Use full settings.'; }
  $schema = array( $wpdb->options => array( 'option_id' => 'bigint(20) unsigned', 'option_name' => 'varchar(191)', 'option_value' => 'longtext', 'autoload' => 'varchar(20)' ), $wpdb->usermeta => array( 'umeta_id' => 'bigint(20) unsigned', 'user_id' => 'bigint(20) unsigned', 'meta_key' => 'varchar(255)', 'meta_value' => 'longtext' ) );
  foreach ( $schema as $table => $expected ) {
   $status = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name=%s', $table ), ARRAY_A );
   if ( $wpdb->last_error || ! $status || 'InnoDB' !== $status['Engine'] ) { return 'Transactional option and role storage could not be qualified. Use full settings.'; }
   $columns = $wpdb->get_results( "SHOW FULL COLUMNS FROM {$table}", ARRAY_A ); $actual = array();
   if ( $wpdb->last_error || ! is_array( $columns ) ) { return 'The native storage schema could not be qualified. Use full settings.'; }
   foreach ( $columns as $col ) { $actual[ $col['Field'] ] = strtolower( $col['Type'] ); if ( $wpdb->options === $table && ( 'NO' !== $col['Null'] || ( in_array( $col['Field'], array( 'option_name', 'option_value', 'autoload' ), true ) && 'utf8mb4_unicode_520_ci' !== $col['Collation'] ) ) ) { return 'Custom native option storage needs full settings.'; } }
   if ( $actual !== $expected ) { return 'This storage schema differs from the qualified native schema. Use full settings.'; }
   foreach ( $columns as $col ) {
    $id = in_array( $col['Field'], array( 'option_id', 'umeta_id' ), true );
    $text = in_array( $col['Field'], array( 'option_name', 'option_value', 'autoload', 'meta_key', 'meta_value' ), true );
    $nullable = in_array( $col['Field'], array( 'meta_key', 'meta_value' ), true );
    $default = 'user_id' === $col['Field'] ? '0' : ( 'option_name' === $col['Field'] ? '' : ( 'autoload' === $col['Field'] ? 'yes' : null ) );
    if ( ( $nullable ? 'YES' : 'NO' ) !== $col['Null'] || ( $id ? 'auto_increment' : '' ) !== $col['Extra'] || $col['Default'] !== $default || ( $text && 'utf8mb4_unicode_520_ci' !== $col['Collation'] ) ) { return 'Custom native storage definitions need full settings.'; }
   }
   $triggers = $wpdb->get_results( $wpdb->prepare( 'SHOW TRIGGERS WHERE `Table`=%s', $table ), ARRAY_A );
   if ( $wpdb->last_error || ! is_array( $triggers ) || $triggers ) { return 'Storage triggers could not be safely excluded. Use full settings.'; }

   $rows = $wpdb->get_results( "SHOW INDEX FROM {$table}", ARRAY_A ); $parts = array();
   if ( $wpdb->last_error || ! is_array( $rows ) ) { return 'Storage indexes could not be verified.'; }
   foreach ( $rows as $row ) { $parts[ $row['Key_name'] ][] = $row; }
   $required = $wpdb->options === $table ? array( 'PRIMARY' => array( 'option_id', 0 ), 'option_name' => array( 'option_name', 0 ) ) : array( 'PRIMARY' => array( 'umeta_id', 0 ), 'user_id' => array( 'user_id', 1 ) );
   foreach ( $required as $name => $want ) { $r = $parts[ $name ] ?? array(); if ( 1 !== count( $r ) || $want[0] !== $r[0]['Column_name'] || $want[1] !== (int) $r[0]['Non_unique'] || 1 !== (int) $r[0]['Seq_in_index'] || ! empty( $r[0]['Sub_part'] ) ) { return 'Required native exact-key indexes are missing or custom. Use full settings.'; } }
  }
  return '';
 }

private static function core_hook( $hook ) {
  global $wp_filter;$bad=array('safe'=>false,'binding'=>'unqualified');
  if(!has_filter($hook)){return array('safe'=>true,'binding'=>'absent');}
  if(!isset($wp_filter[$hook])||!$wp_filter[$hook] instanceof WP_Hook){return $bad;}
  $specs=array(array('hook'=>'option_blog_charset','function'=>'_wp_specialchars','priority'=>10,'accepted_args'=>1,'file'=>'wp-includes/formatting.php','file_sha256'=>'350f6b32303915df970bb0e3ea360fcd54bb0b8a7ecc3d504c3d860ab9468d32','body_sha256'=>'391138e54476a3c3b6c63e9b34fe956ad34ff96d652cc6cb75b41403ed25993e','start'=>945,'end'=>993),array('hook'=>'option_blog_charset','function'=>'_canonical_charset','priority'=>10,'accepted_args'=>1,'file'=>'wp-includes/functions.php','file_sha256'=>'cac8a17e293df99f0683a88debd3ba5e5cce274183a4e9fd3c9f18375747f41c','body_sha256'=>'cb7c76532990dfb3086587762128438268e7a701c5aa6c6147f78964459b6cc0','start'=>7731,'end'=>7753),array('hook'=>'sanitize_title','function'=>'sanitize_title_with_dashes','priority'=>10,'accepted_args'=>3,'file'=>'wp-includes/formatting.php','file_sha256'=>'350f6b32303915df970bb0e3ea360fcd54bb0b8a7ecc3d504c3d860ab9468d32','body_sha256'=>'d071f20265003ca49230c19c113169351348ec9c96d6c39f600ddaab583c456e','start'=>2282,'end'=>2401));$seen=array();$bound=array();
  try{foreach($wp_filter[$hook]->callbacks as $priority=>$entries){foreach($entries as $entry){$matched=false;
   foreach($specs as $s){if($s['hook']!==$hook||$s['function']!==($entry['function']??null)||$s['priority']!==(int)$priority||$s['accepted_args']!==($entry['accepted_args']??null)){continue;}
    if(isset($seen[$s['function']])){return $bad;}$m=new ReflectionFunction($s['function']);$file=realpath(isset($s['root'])&&'plugin'===$s['root']?dirname(__DIR__,2).'/'.$s['file']:ABSPATH.$s['file']);
    if(isset($s['parameters'])&&$m->getNumberOfParameters()!==$s['parameters']){return $bad;}
    if(!$file||realpath($m->getFileName())!==$file||$m->getStartLine()!==$s['start']||$m->getEndLine()!==$s['end']||hash_file('sha256',$file)!==$s['file_sha256']){return $bad;}
    $lines=file($file,FILE_IGNORE_NEW_LINES);$body=hash('sha256',implode("\n",array_slice($lines,$s['start']-1,$s['end']-$s['start']+1)));
    if($body!==$s['body_sha256']){return $bad;}$seen[$s['function']]=true;$bound[]=$s;$matched=true;break;
   }if(!$matched){return $bad;}
  }}}catch(Throwable $e){return $bad;}
  return $bound?array('safe'=>true,'binding'=>$bound):$bad;
 }
  private static function platform() {
  if ( ! defined( 'WC_ABSPATH' ) || PHP_INT_SIZE < 8 ) { return array( 'safe' => false ); }
  $files=array('wp:wp-includes/formatting.php'=>'350f6b32303915df970bb0e3ea360fcd54bb0b8a7ecc3d504c3d860ab9468d32','wp:wp-includes/option.php'=>'fe3f0b7e115b2a50c31162ed6428abe4088c080b0f71b9678d84806168fe90d0','includes/admin/class-wc-admin-settings.php'=>'34c63bd66fa729ed1e09bca21a15061183bb12aef4455d0514817399b606eb3d','wp:wp-includes/functions.php'=>'cac8a17e293df99f0683a88debd3ba5e5cce274183a4e9fd3c9f18375747f41c','wp:wp-includes/utf8.php'=>'6115a3ffac15321e08fcb3ebe39ba0bd83121e0fecc226911f4a801fd896ec77','wp:wp-includes/load.php'=>'3efd0934bd8a454a8d7076fbbd21504ea097a753125a48e64b8a1dad380d357a','wp:wp-includes/compat-utf8.php'=>'7a922c1d15f8d1464c18d005d6e094126c4c82a85f672b1b9d8616db67f700ea','wp:wp-includes/compat.php'=>'6687dd4bf958e127a9d9df3fcc70545d8a0bea2d94410dc2a38b6ed2ee7df58b','includes/wc-core-functions.php'=>'644fcf86ccb4b58bef3c2e02bf3138ce8ccad8da3763ef472474e62fb2749923','includes/wc-formatting-functions.php'=>'a4188d2d9b6786eebed14e4a3e07fc37a2c2eec48cfb6a32199a2605c3415361','src/Internal/DataStores/Orders/CustomOrdersTableController.php'=>'b4d1a6772b064de9be6a80750074b0a9e371514f58131a1701cad6cd52ccb8bf','src/Internal/DataStores/Orders/DataSynchronizer.php'=>'a431e293f882f6ed0517de243856bff419454888d65d1c0e5779ad7959abc594','src/Internal/Features/FeaturesController.php'=>'667bd335a443c93e9746d8be05309b98877cc4498888fdb91fe93a13a8b30348','src/Internal/Admin/Schedulers/OrdersScheduler.php'=>'349ed8a1a046baba4d3419335b089dbb96115b7f7de0b80718d7ca734fb0b6e6','includes/wc-account-functions.php'=>'39bf9521c0cca33baa085f64fc03ac929e9fcc21b5806f148d0fef8cfc4c16e8','includes/class-wc-query.php'=>'21ffcfc6e4734639f2aaa2c15cc4bda637c26d6893080eb56e48747557875641'); $native=array('free'=>array('includes/settings/wcj-settings-admin-bar.php'=>'d04e1f4c3969956a79890a626795e242ac795441165b605a099483b26c094b28','includes/class-wcj-admin-bar.php'=>'15528c98a1f30e75565f201eac3492f304d33e013e23ff48623bcab9a597b004','includes/settings/wcj-settings-purchase-data.php'=>'11bf847dc76037c40c12f3b62a44af868ea10a5b45b07cddb93b5018e4e07d30','includes/class-wcj-purchase-data.php'=>'9bcf57a7040ed4d32bc1db2dec1028c5649048cc816ef1f4aacb1acd96ccc32c','includes/functions/wcj-functions-price-currency.php'=>'aa29854dad5563d6cde69dd98b0109b0caa081e8b1fb82db438fd5b9c4279e2b','includes/functions/wcj-functions-core.php'=>'b09c635f440ed758a18d7e48960904dbcc85f6c12dfd2672bebdb8e9a2344e0f','includes/functions/wcj-functions-admin.php'=>'2dae419c1a189f63af71b1a05e9e35fb062ab373697665dc527008cf34d71718','includes/functions/wcj-functions-booster-core.php'=>'407642a150cd219f8d7d17a12a0c2b169cfad1f08fea268d9cbc2f4f2c168199','includes/classes/class-wcj-module.php'=>'31bc1cec496acd7453abf585435c4eacff9362ad7978097b1df60ffe0f834232','includes/core/class-wcj-admin.php'=>'aa10daca2e5e97a3ce75a001f59a0883d2a90c7a0f7a6f6827a18c3d3da55bfa','includes/admin/class-wcj-job-store.php'=>'16a260a57e67e283dbeed0bccca3764df3e5e31248aeecaf32d8b7b40474de66'),'plus'=>array('includes/settings/wcj-settings-admin-bar.php'=>'3c40543e1bccdb2d266b1db52506a802cacca939cec4057a766093be7993ef0e','includes/class-wcj-admin-bar.php'=>'735da5a744b9c43b04786b3b504c3d9073dc55093884b4669f898dc1942eff42','includes/settings/wcj-settings-purchase-data.php'=>'ca725ccff8ae909dc10e0986b37fbe8a2e41db2a4de38bd2b838387c6e1e9e49','includes/class-wcj-purchase-data.php'=>'4118599f943988dbbd3956d9e8e96346e45be22ccfe323bd369940c4f38443d0','includes/functions/wcj-functions-price-currency.php'=>'02f8d12c33829b6e69a8b21882f843f404650b7991991b5123affa602603454e','includes/functions/wcj-functions-core.php'=>'269204584ef29993971f9ca02fca42e896d12564dae847cece4c1b27f3420ed1','includes/functions/wcj-functions-admin.php'=>'6f221eb688410e599e7d29deedb2806a40ad8088a6a915557dfcc66cf39ce7e3','includes/functions/wcj-functions-booster-core.php'=>'213d6298553459656c3343b44e1d10bdca5ed2883e584b485daa2fcd27b1d37c','includes/classes/class-wcj-module.php'=>'2c306011e2d7c8984231f03c2e9e2795640a18fda1239a42fd5f89e2c34a6022','includes/core/class-wcj-admin.php'=>'c60626022c4b66367ddf9f39b41122b5aa5bca4692fb36484faaeea10d6cf8e6','includes/admin/class-wcj-job-store.php'=>'16a260a57e67e283dbeed0bccca3764df3e5e31248aeecaf32d8b7b40474de66'),'elite'=>array('includes/settings/wcj-settings-admin-bar.php'=>'8328642c3e791fc005cbbbbe6923bdfe5a4b181e69c0909ec7caf17208989760','includes/class-wcj-admin-bar.php'=>'712dfcf4f4f3bb1abf36a45e0ef6b4bd10b68d360fe4a8e7a1af3d588eb2f530','includes/settings/wcj-settings-purchase-data.php'=>'dadda3f0ce24bb22e25e459b19bf1451eaf1a35bf0767553b1a7bcc9edb42be4','includes/class-wcj-purchase-data.php'=>'62283171803dc8dcef255e1a1e6579f175418f50b98b670413a2ad32443b1888','includes/functions/wcj-functions-price-currency.php'=>'8e5725ff3c9dd1f37dc7ef2f09347a23cdda68377e296f0746a773db46c0b40e','includes/functions/wcj-functions-core.php'=>'097f31d34847837cc8a2d9336c515c021de84b247f419eee84b7a041e1f73f99','includes/functions/wcj-functions-admin.php'=>'ec1d734592e412497818feb4e9ae4fd375c9d32de1f10ea1d10ba5eb4b9c9a75','includes/functions/wcj-functions-booster-core.php'=>'8c334071eae93a8f18b406e3e999d9586a50e12bddee0cb37e97077f9613fa6a','includes/classes/class-wcj-module.php'=>'290d825a26026a725dd43130bd28fa53222dd9cba24a856bf36d91652f1444d6','includes/core/class-wcj-admin.php'=>'85d3bdcb59405e1c8b8342c5efeb3bef88305aedb30d9675de0edbff010297c6','includes/admin/class-wcj-job-store.php'=>'16a260a57e67e283dbeed0bccca3764df3e5e31248aeecaf32d8b7b40474de66')); $bindings=array();
  foreach ( $files as $rel => $sha ) { $p = 0 === strpos( $rel, 'wp:' ) ? ABSPATH . substr( $rel, 3 ) : WC_ABSPATH . $rel; if ( ! is_readable( $p ) || hash_file( 'sha256', $p ) !== $sha ) { return array( 'safe' => false ); } $bindings[$rel] = $sha; }
  foreach ( $native[self::edition()] as $rel => $sha ) { $p = dirname(__DIR__,2) . '/' . $rel; if ( ! is_readable($p) || hash_file('sha256',$p) !== $sha ) { return array('safe'=>false); } $bindings[$rel]=$sha; }
  $bindings['task:self']=hash_file('sha256',__FILE__); $bindings['task:workspace']=hash_file('sha256',__DIR__.'/class-wcj-catalog-admin-workspace.php'); $bindings['task:access']=hash_file('sha256',__DIR__.'/class-wcj-task-finder.php');
  return array('safe'=>true,'binding'=>$bindings);
 }


 public static function transaction_entry_reason(){
  global $wpdb;$row=$wpdb->get_row('SELECT VERSION() AS version, @@version_comment AS comment, @@autocommit AS autocommit',ARRAY_A);if($wpdb->last_error||!is_array($row)||!isset($row['version'],$row['comment'],$row['autocommit'])||'1'!==(string)$row['autocommit']){return 'Automatic commits and a supported database server are required. Use full settings.';}
  $v=(string)$row['version'];$comment=(string)$row['comment'];if(!preg_match('/\A([0-9]+\.[0-9]+\.[0-9]+)/',$v,$m)){return 'This database server cannot be qualified for the guided task.';}
  $maria=false!==stripos($v,'MariaDB');$mysql=!$maria&&false!==stripos($comment,'MySQL');if($maria||$mysql){return '';}
  return 'This database server has not been qualified for the guided task. Use full settings.';
 }

 public static function cost_qualification($keys){
  $p=self::platform();if(!$p['safe']){return 'This installation differs from the supported source. Use full settings.';}
  $reason=self::storage_reason();if($reason){return $reason;}
  return self::cost_read_hooks($keys)?'A settings integration needs full settings.':'';
 }
 private static function cost_read_hooks($keys){
  // Context options are read, never updated by the cost task. Keep read and sanitizer hazards strict.
  $out=array();foreach($keys as $k){foreach(array('','option_','pre_option_','default_option_','pre_update_option_','sanitize_option_','woocommerce_admin_settings_sanitize_option_') as $prefix){$hook=$prefix.$k;if(has_filter($hook)&&!('option_blog_charset'===$hook&&self::core_hook($hook)['safe'])){$out[]=$hook;}}}
  foreach(array('pre_option','pre_wp_load_alloptions','pre_cache_alloptions','alloptions','sanitize_text_field') as $hook){if(has_filter($hook)){$out[]=$hook;}}sort($out);return $out;
 }
 public static function cost_write_qualification($key){
  // Only actual receipt/ownership writes use mutation-observer qualification, including retired markers.
  if(!is_string($key)||!preg_match('/\Abooster_missing_cost_(?:job|owner)_[1-9][0-9]*\z/D',$key)){return 'Unexpected recovery target.';}
  if(self::hooks($key,$key,array($key))||!self::global_update_filter($key)['safe']){return 'A recovery settings integration needs full settings.';}
  foreach(self::known_observers($key) as $o){if(!$o['safe']){return 'A recovery settings observer needs full settings.';}}
  return '';
 }

}
new WCJ_Toolbar_Categories_Job();

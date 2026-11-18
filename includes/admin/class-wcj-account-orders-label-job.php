<?php
/** One Orders menu label and endpoint heading. Whole-array preservation; no activation or order changes.
 * @package Booster_For_WooCommerce/admin
 */
defined( 'ABSPATH' ) || exit;
final class WCJ_Account_Orders_Label_Job {
 const ID = 'my_account';
 const KEY = 'wcj_my_account_menu_title';
 const MODULE = 'wcj_my_account_enabled';
 const RECEIPT = 'booster_account_orders_label_job_receipt';
 const OPS = array( 'load', 'save', 'undo' );
 public function __construct() {
  add_action( 'admin_menu', array( $this, 'register' ), 99 );
  foreach ( self::OPS as $op ) { add_action( 'wp_ajax_wcj_account_orders_label_job_' . $op, array( $this, 'handle' ) ); }
 }
 public static function url() { return admin_url( 'admin.php?page=wcj-account-orders-label-job' ); }
 public static function native_url() { return admin_url( wcj_admin_tab_url() . '&wcj-cat=emails_and_misc&section=my_account&active_tab=my_account_menu_endpoints_tab' ); }
 public static function edition() { return defined( 'WCJ_PLUGIN_FILE' ) && 'booster-elite-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE ) ? 'elite' : ( defined( 'WCJ_PLUGIN_FILE' ) && 'booster-plus-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE ) ? 'plus' : 'free' ); }
 private static function fail( $message, $code = 422 ) { throw new RuntimeException( $message, $code ); }
 private static function authorize() { if ( ! current_user_can( 'manage_woocommerce' ) || ! WCJ_Task_Finder::allowed( self::ID ) ) { self::fail( 'Ask an administrator for access to My Account settings.', 403 ); } }
 public function register() { if ( current_user_can( 'manage_woocommerce' ) && WCJ_Task_Finder::allowed( self::ID ) ) { add_submenu_page( 'woocommerce', 'Rename Orders in My Account', 'Orders label', 'manage_woocommerce', 'wcj-account-orders-label-job', array( $this, 'render' ) ); } }
 /** Pure bounded input and whole-array adapter. No native callback or sibling output. */
 public static function plain($v,$empty=true) {
  return is_string($v)&&strlen($v)<=64&&1===preg_match('//u',$v)&&'0'!==$v
   && !preg_match('/^(?:N;|[aObisdCrRE]:)/D',$v)
   && !preg_match('/[\p{Cc}\p{Cf}\p{Zl}\p{Zp}<>\[\]]|&(?:[A-Za-z][A-Za-z0-9]+;|\#(?:[0-9]+|[xX][0-9A-Fa-f]+);?)|%[a-fA-F0-9]{2}/u',$v)
   && (($empty&&''===$v)||1===preg_match('/[^\s\p{Z}\p{Cf}]/u',$v))
   && !preg_match('/\A[\s\p{Z}]|[\s\p{Z}]\z| {2}/u',$v);
 }
 public static function decode($raw) {
  if(!is_string($raw)||strlen($raw)>32768||!preg_match('/\Aa:[0-9]+:\{/D',$raw)){return null;}
  $a=@unserialize($raw,array('allowed_classes'=>false,'max_depth'=>2));
  if(!is_array($a)||count($a)>32||serialize($a)!==$raw){return null;}
  foreach($a as $k=>$v){if(null!==ReflectionReference::fromArrayElement($a,$k)||!is_string($k)||strlen($k)>128||1!==preg_match('//u',$k)||!is_string($v)||strlen($v)>4096||1!==preg_match('//u',$v)){return null;}}
  return $a;
 }
 public static function target_state($state) {return self::valid_state($state)&&(null===$state||null!==self::decode($state['raw']));}
 public static function patch_state($before,$value) {
  if(!self::target_state($before)||!self::plain($value,true)){self::fail('The retained labels or new wording are not supported. Nothing was changed.');}
  $a=null===$before?array():self::decode($before['raw']);
  if(array_key_exists('orders',$a)&&!self::plain($a['orders'],true)){self::fail('The saved Orders wording needs full settings. It was kept unchanged.');}
  if((''===$value&&!array_key_exists('orders',$a))||(array_key_exists('orders',$a)&&$a['orders']===$value)){return $before;}
  $a['orders']=$value;$raw=serialize($a);
  if(null===self::decode($raw)){self::fail('Adding this label would exceed the supported saved-label size. Use full settings.');}
  return array('raw'=>$raw,'autoload'=>null===$before?'off':$before['autoload']);
 }
 private static function packed($value) {
  if(!self::plain($value,true)){self::fail('Use blank for the supplied default wording, or visible plain text up to 64 UTF-8 bytes. No markup, shortcodes, entities, control characters, encoded bytes, leading or trailing spaces, or the single digit 0.');}
  if(wc_clean($value)!==$value){self::fail('The native text cleaner would change this wording. Nothing was saved. Use full settings.');}
  return $value;
 }
 private static function flags(){return array(self::MODULE,'wcj_my_account_menu_customize_enabled','wcj_my_account_menu_order_customize_enabled','wcj_my_account_menu_order_custom_items_enabled','wcj_my_account_custom_pages_enabled','wcj_modules_by_user_roles_enabled');}
 /** Read-only collision checks. Custom-page titles outside a simple ASCII slug grammar stay in full settings. */
 private static function overlap($c) {
  $endpoint=self::raw($c,'woocommerce_myaccount_orders_endpoint','orders');
  if(!is_string($endpoint)||'0'===$endpoint||!preg_match('/\A[a-zA-Z0-9_-]{1,128}\z/D',$endpoint)){return 'The WooCommerce Orders endpoint is disabled or uses an unsupported address. This task does not add a missing menu item. Check WooCommerce Advanced settings.';}
  if($endpoint!==($c['output']['orders_endpoint']??null)){return 'The current Orders address differs from the saved settings. Reload deliberately or use full settings.';}
  foreach(array('wcj_my_account_menu_order_custom_items','wcj_my_account_menu_order') as $k){$v=self::raw($c,$k,'');if(strlen($v)>8192||1!==preg_match('//u',$v)||preg_match('/^(?:a:|O:|C:)/D',$v)){return 'Custom menu choices need full settings. They were kept unchanged.';}}
  if('free'!==self::edition()&&'yes'===self::raw($c,'wcj_my_account_menu_order_custom_items_enabled','no')){
   foreach(explode(PHP_EOL,self::raw($c,'wcj_my_account_menu_order_custom_items','')) as $line){$parts=array_map('trim',explode('|',trim($line),3));if(3===count($parts)&&'orders'===$parts[0]){return 'A custom menu item also defines orders. Use full settings to choose which wording should be used before using this shortcut.';}}
  }
  if('yes'===self::raw($c,'wcj_my_account_custom_pages_enabled','no')){
   $count='free'===self::edition()?'1':self::raw($c,'wcj_my_account_custom_pages_total_number','1');
   if(!preg_match('/\A(?:0|[1-9][0-9]?)\z/D',$count)||intval($count)>32){return 'The custom-page count needs full settings before this label can be edited safely.';}
   $arrays=array();foreach(array('title','content','endpoint') as $kind){$raw=self::raw($c,'wcj_my_account_custom_pages_'.$kind);$v=null===$raw?array():self::page_array($raw);if(null===$v){return 'Saved custom pages use an unsupported structure. Use full settings; they were not changed.';}$arrays[$kind]=$v;}
   $pages=array();for($i=1;$i<=intval($count);$i++){
    $title=$arrays['title'][$i]??'';$content=$arrays['content'][$i]??'';$ep=$arrays['endpoint'][$i]??'';
    if(empty($title)||empty($content)){continue;}
    if(!preg_match('/\A[A-Za-z0-9 _-]+\z/D',$title)){return 'An active custom-page title needs full settings to check its address against Orders. Nothing was changed.';}
    // ASCII titles take remove_accents' immediate return, before locale filters. The admitted native slug callback is used for derivation, never as an admission probe.
    $pages[sanitize_title($title)]=array('endpoint'=>$ep,'title'=>$title,'content'=>$content);
   }
   if('yes'===self::raw($c,self::MODULE,'no')&&hash('sha256',serialize($pages))!==($c['output']['custom_pages_hash']??null)){return 'Current custom-page choices differ from the saved settings. Reload deliberately or use full settings.';}
   foreach($pages as $slug=>$page){$ep=$page['endpoint'];
    $final=empty($ep)?$slug:$ep;
    if('orders'===$final||(!empty($ep)&&$endpoint===$ep)){return 'An active custom page overlaps Orders. Resolve its title or address in full settings before renaming Orders here.';}
   }
  }
  return '';
 }
 private static function page_array($raw) {
  if(!is_string($raw)||strlen($raw)>65536||!preg_match('/\Aa:[0-9]+:\{/D',$raw)){return null;}
  $a=@unserialize($raw,array('allowed_classes'=>false,'max_depth'=>2));if(!is_array($a)||count($a)>32||serialize($a)!==$raw){return null;}
  foreach($a as $k=>$v){if(null!==ReflectionReference::fromArrayElement($a,$k)||!is_int($k)||$k<1||$k>32||!is_string($v)||strlen($v)>16384||1!==preg_match('//u',$v)){return null;}}return $a;
 }
 private static function raw( $c, $key, $default = null ) { return isset( $c['rows'][ $key ] ) ? $c['rows'][ $key ]['raw'] : $default; }
 private static function state( $c, $key ) { return $c['rows'][ $key ] ?? null; }
 private static function hash( $c, $receipt = true ) { if ( ! $receipt ) { unset( $c['rows'][ self::RECEIPT ] ); } return hash_hmac( 'sha256', serialize( array( get_current_blog_id(), get_current_user_id(), self::edition(), $c ) ), wp_salt( 'nonce' ) ); }

 private static function context( $lock = false ) {
  global $wpdb; $user=wp_get_current_user();
  $base=array_merge(self::flags(),array(self::KEY,self::RECEIPT,'blog_charset','woocommerce_myaccount_orders_endpoint','wcj_my_account_menu_order_custom_items','wcj_my_account_menu_order','wcj_my_account_custom_pages_total_number','wcj_my_account_custom_pages_title','wcj_my_account_custom_pages_content','wcj_my_account_custom_pages_endpoint',$wpdb->prefix.'user_roles','active_plugins'));
  foreach($user->roles as $role){$base[]='wcj_modules_by_user_roles_incl_'.$role;$base[]='wcj_modules_by_user_roles_excl_'.$role;}
  $keys=array_values(array_unique($base));sort($keys);
  $sql=$wpdb->prepare("SELECT option_name,option_value,autoload FROM {$wpdb->options} WHERE option_name IN (".implode(',',array_fill(0,count($keys),'%s')).") ORDER BY option_name".($lock?' FOR UPDATE':''),$keys);
  $rows=$wpdb->get_results($sql,ARRAY_A);
  if($wpdb->last_error||!is_array($rows)){self::fail('Account label settings could not be safely read. Use full settings.',409);}
  $c=array('rows'=>array());$bytes=0;
  foreach($rows as $row){$bytes+=strlen($row['option_value']);$c['rows'][$row['option_name']]=array('raw'=>(string)$row['option_value'],'autoload'=>(string)$row['autoload']);}
  if($bytes>1000000){self::fail('The retained settings are too large for this task. Use full settings.');}ksort($c['rows']);
  $roles=$wpdb->get_results($wpdb->prepare("SELECT umeta_id,meta_key,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key IN (%s,%s) ORDER BY umeta_id".($lock?' FOR UPDATE':''),get_current_user_id(),$wpdb->prefix.'capabilities',$wpdb->prefix.'user_level'),ARRAY_A);
  if($wpdb->last_error||!is_array($roles)){self::fail('Current role storage could not be qualified.',409);}
  // Target and key set do not depend on option existence. INSERT and Undo absence have identical context derivation.
  $c+=array('target'=>array('key'=>self::KEY,'leaf'=>'orders'),'controller'=>hash_file('sha256',__FILE__),'keys'=>$keys,'role_rows'=>$roles,'roles'=>$user->roles,'caps'=>$user->allcaps,'allowlist'=>function_exists('wcj_get_enabled_module_allowlist')?wcj_get_enabled_module_allowlist():null,'paid'=>self::paid_filter(),'observers'=>self::known_observers(self::KEY),'global_update'=>self::global_update_filter(self::KEY),'platform'=>self::platform(),'hooks'=>self::hooks(self::KEY,$keys),'escaping'=>self::escaping(),'output'=>self::output_context(),'edition'=>self::edition(),'blog'=>get_current_blog_id());
  return $c;
 }
 private static function hooks( $key, $keys ) {
  $out = array(); $known = self::known_observers( $key );
  foreach ( $keys as $k ) { foreach ( array( '', 'option_', 'pre_option_', 'default_option_', 'pre_update_option_', 'sanitize_option_', 'woocommerce_admin_settings_sanitize_option_' ) as $prefix ) { if ( has_filter( $prefix . $k ) && !( 'option_' === $prefix && 'blog_charset' === $k && self::core_hook('option_blog_charset')['safe'] ) && !( 'woocommerce_admin_settings_sanitize_option_' === $prefix && 'woocommerce_myaccount_orders_endpoint' === $k && self::endpoint_sanitizer()['safe'] ) ) { $out[] = $prefix . $k; } } }
  foreach ( array( $key, self::RECEIPT ) as $k ) { foreach ( array( 'update_option_', 'add_option_', 'delete_option_' ) as $prefix ) { if ( has_filter( $prefix . $k ) && !( 'option_' === $prefix && 'blog_charset' === $k && self::core_hook('option_blog_charset')['safe'] ) ) { $out[] = $prefix . $k; } } }
  foreach ( array( 'pre_option', 'pre_wp_load_alloptions', 'pre_cache_alloptions', 'alloptions', 'woocommerce_admin_settings_sanitize_option', 'woocommerce_update_option_text', 'woocommerce_update_option', 'update_option', 'updated_option', 'add_option', 'added_option', 'delete_option', 'deleted_option' ) as $hook ) { if ( has_filter( $hook ) && ( ! isset( $known[ $hook ] ) || ! $known[ $hook ]['safe'] ) ) { $out[] = $hook; } }
  sort( $out ); return $out;
 }
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
array( 'hook' => 'wcj_my_account_settings', 'priority' => 10, 'accepted_args' => 1, 'class' => 'WCJ_My_Account', 'declaring' => 'WCJ_Module', 'method' => 'add_settings_from_file', 'relative' => 'native:includes/classes/class-wcj-module.php', 'file_sha256' => array( 'free' => '31bc1cec496acd7453abf585435c4eacff9362ad7978097b1df60ffe0f834232', 'plus' => '2c306011e2d7c8984231f03c2e9e2795640a18fda1239a42fd5f89e2c34a6022', 'elite' => '290d825a26026a725dd43130bd28fa53222dd9cba24a856bf36d91652f1444d6' ), 'body_sha256' => array( 'free' => '23a89666372bf336121b647d306f810a560ef985c7ddeb9426e9b9e4dd3bbf75', 'plus' => 'e07bca7bb3f7f733d9f8baef023310e979a578e58dd1b2aadb6282ac67814937', 'elite' => 'e07bca7bb3f7f733d9f8baef023310e979a578e58dd1b2aadb6282ac67814937' ), 'static' => false, 'parameters' => 1 ),
array( 'hook' => 'wcj_settings_my_account', 'priority' => 100, 'accepted_args' => 1, 'class' => 'WCJ_My_Account', 'declaring' => 'WCJ_Module', 'method' => 'get_settings', 'relative' => 'native:includes/classes/class-wcj-module.php', 'file_sha256' => array( 'free' => '31bc1cec496acd7453abf585435c4eacff9362ad7978097b1df60ffe0f834232', 'plus' => '2c306011e2d7c8984231f03c2e9e2795640a18fda1239a42fd5f89e2c34a6022', 'elite' => '290d825a26026a725dd43130bd28fa53222dd9cba24a856bf36d91652f1444d6' ), 'body_sha256' => array( 'free' => 'ffd59e72735f9426b74de8cdc14c1bdbeca8f710439376ca7e9e65de2e88a2dd', 'plus' => 'ffd59e72735f9426b74de8cdc14c1bdbeca8f710439376ca7e9e65de2e88a2dd', 'elite' => '22821a332f06e2ab607f2b31f14084cc0510f9bff5d5bae7dfdcd6b278eff933' ), 'static' => false, 'parameters' => 0 )
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
  $write_keys = array( $group, self::RECEIPT ); $bindings = array(); $seen = array();
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
       // Native Orders field is text. Only its leaf enters wc_clean; siblings never enter a sanitizer.
       // The signed receipt is not a Woo settings field and never enters this sanitizer.
       $inner = self::observer_hook( 'wcj_my_account_settings', $group ); $outer = self::observer_hook( 'wcj_settings_my_account', $group );
       if ( ! $inner['safe'] || 'absent' === $inner['binding'] || ! $outer['safe'] || 'absent' === $outer['binding'] ) { return $unknown; }
       $extra = array( 'field' => array( 'id' => $write_keys[0], 'type' => 'text', 'wcj_raw' => 'absent' ), 'receipt' => 'not-a-Woo-field', 'inner' => $inner['binding'], 'outer' => $outer['binding'] );
      } elseif ( 'WCJ_My_Account' === $class ) {
       $p = new ReflectionProperty( $class, 'id' );
       if ( ! $p->isInitialized( $entry['function'][0] ) || self::ID !== $p->getValue( $entry['function'][0] ) ) { return $unknown; }
       $extra = array( 'module_id' => self::ID );
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
  $out = array(); foreach ( array( 'added_option', 'delete_option', 'deleted_option', 'updated_option', 'woocommerce_admin_settings_sanitize_option', 'wcj_my_account_settings', 'wcj_settings_my_account' ) as $hook ) { $out[ $hook ] = self::observer_hook( $hook, $group ); } return $out;
 }

 private static function paid_filter() {
  global $wp_filter;
  $unknown = array( 'safe' => false, 'binding' => 'unqualified' );
  if ( 'free' === self::edition() ) { return has_filter( 'booster_option' ) ? $unknown : array( 'safe' => true, 'binding' => 'free-defaults' ); }
  if ( ! isset( $wp_filter['booster_option'] ) || ! $wp_filter['booster_option'] instanceof WP_Hook ) { return $unknown; }
  $bindings = array();
  foreach ( $wp_filter['booster_option']->callbacks as $priority => $entries ) {
   foreach ( $entries as $entry ) {
    $f = $entry['function'] ?? null;
    if ( 101 !== (int) $priority || 2 !== ( $entry['accepted_args'] ?? null ) || ! is_array( $f ) || count( $f ) !== 2 || ! is_object( $f[0] ) || 'WCJ_Plus_Filters' !== get_class( $f[0] ) || 'booster_get_option' !== $f[1] ) { return $unknown; }
    $m = new ReflectionMethod( $f[0], $f[1] ); $file = realpath( dirname( __DIR__ ) . '/plus/class-wcj-plus-filters.php' );
    if ( ! $file || realpath( $m->getFileName() ) !== $file || ! $m->isPublic() || $m->isStatic() || $m->getNumberOfParameters() !== 2 || $m->getDeclaringClass()->getName() !== 'WCJ_Plus_Filters' ) { return $unknown; }
    $lines = file( $file, FILE_IGNORE_NEW_LINES ); $hash = hash( 'sha256', implode( "\n", array_slice( $lines, $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1 ) ) );
    if ( '544741187c4efed7ceb7bac060003631d6f2cc45a202a02d240c540404becc9f' !== $hash ) { return $unknown; }
    $bindings[] = array( $hash, 101, 2 );
   }
  }
  return count( $bindings ) === 1 ? array( 'safe' => true, 'binding' => $bindings ) : $unknown;
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
					if ( 'woocommerce_custom_orders_table_enabled' !== $usage || 'woocommerce_custom_orders_table_data_sync_enabled' !== $sync_option || array_intersect( array( $usage, $sync_option ), array( '' === $g ? '' : $g, self::RECEIPT ) ) ) { return $unknown; }
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
 private static function platform() {
  if ( ! defined( 'WC_ABSPATH' ) || PHP_INT_SIZE < 8 ) { return array( 'safe' => false ); }
  $files=array('wp:wp-includes/formatting.php'=>'350f6b32303915df970bb0e3ea360fcd54bb0b8a7ecc3d504c3d860ab9468d32','wp:wp-includes/option.php'=>'fe3f0b7e115b2a50c31162ed6428abe4088c080b0f71b9678d84806168fe90d0','includes/admin/class-wc-admin-settings.php'=>'34c63bd66fa729ed1e09bca21a15061183bb12aef4455d0514817399b606eb3d','wp:wp-includes/functions.php'=>'cac8a17e293df99f0683a88debd3ba5e5cce274183a4e9fd3c9f18375747f41c','wp:wp-includes/utf8.php'=>'6115a3ffac15321e08fcb3ebe39ba0bd83121e0fecc226911f4a801fd896ec77','wp:wp-includes/load.php'=>'3efd0934bd8a454a8d7076fbbd21504ea097a753125a48e64b8a1dad380d357a','wp:wp-includes/compat-utf8.php'=>'7a922c1d15f8d1464c18d005d6e094126c4c82a85f672b1b9d8616db67f700ea','wp:wp-includes/compat.php'=>'6687dd4bf958e127a9d9df3fcc70545d8a0bea2d94410dc2a38b6ed2ee7df58b','includes/wc-core-functions.php'=>'644fcf86ccb4b58bef3c2e02bf3138ce8ccad8da3763ef472474e62fb2749923','includes/wc-formatting-functions.php'=>'a4188d2d9b6786eebed14e4a3e07fc37a2c2eec48cfb6a32199a2605c3415361','src/Internal/DataStores/Orders/CustomOrdersTableController.php'=>'b4d1a6772b064de9be6a80750074b0a9e371514f58131a1701cad6cd52ccb8bf','src/Internal/DataStores/Orders/DataSynchronizer.php'=>'a431e293f882f6ed0517de243856bff419454888d65d1c0e5779ad7959abc594','src/Internal/Features/FeaturesController.php'=>'667bd335a443c93e9746d8be05309b98877cc4498888fdb91fe93a13a8b30348','src/Internal/Admin/Schedulers/OrdersScheduler.php'=>'349ed8a1a046baba4d3419335b089dbb96115b7f7de0b80718d7ca734fb0b6e6','includes/wc-account-functions.php'=>'39bf9521c0cca33baa085f64fc03ac929e9fcc21b5806f148d0fef8cfc4c16e8','includes/class-wc-query.php'=>'21ffcfc6e4734639f2aaa2c15cc4bda637c26d6893080eb56e48747557875641'); $native=array('free'=>array('includes/class-wcj-my-account.php'=>'b8b9f7d17e8c7d66b074ed826c598b913f93b37fee4aaeb25a3c59e7226bbcfc','includes/settings/wcj-settings-my-account.php'=>'66c2c03017ffded9449b1c42c58f5b102421194b0c8185c4a5a61900f72cfde1','includes/functions/wcj-functions-core.php'=>'b09c635f440ed758a18d7e48960904dbcc85f6c12dfd2672bebdb8e9a2344e0f','includes/functions/wcj-functions-admin.php'=>'2dae419c1a189f63af71b1a05e9e35fb062ab373697665dc527008cf34d71718','includes/functions/wcj-functions-booster-core.php'=>'407642a150cd219f8d7d17a12a0c2b169cfad1f08fea268d9cbc2f4f2c168199','includes/classes/class-wcj-module.php'=>'31bc1cec496acd7453abf585435c4eacff9362ad7978097b1df60ffe0f834232','includes/core/class-wcj-admin.php'=>'aa10daca2e5e97a3ce75a001f59a0883d2a90c7a0f7a6f6827a18c3d3da55bfa','includes/admin/class-wcj-job-store.php'=>'16a260a57e67e283dbeed0bccca3764df3e5e31248aeecaf32d8b7b40474de66'),'plus'=>array('includes/class-wcj-my-account.php'=>'ea61fe2662d83153ffe3667c4f0c8b9976d7f675900e6d1673d4dc127430d13c','includes/settings/wcj-settings-my-account.php'=>'681df3a65ab55ab0facb23667df193d3365c33a3a285520b6055da9d47e58b05','includes/functions/wcj-functions-core.php'=>'269204584ef29993971f9ca02fca42e896d12564dae847cece4c1b27f3420ed1','includes/functions/wcj-functions-admin.php'=>'6f221eb688410e599e7d29deedb2806a40ad8088a6a915557dfcc66cf39ce7e3','includes/functions/wcj-functions-booster-core.php'=>'213d6298553459656c3343b44e1d10bdca5ed2883e584b485daa2fcd27b1d37c','includes/classes/class-wcj-module.php'=>'2c306011e2d7c8984231f03c2e9e2795640a18fda1239a42fd5f89e2c34a6022','includes/core/class-wcj-admin.php'=>'c60626022c4b66367ddf9f39b41122b5aa5bca4692fb36484faaeea10d6cf8e6','includes/admin/class-wcj-job-store.php'=>'16a260a57e67e283dbeed0bccca3764df3e5e31248aeecaf32d8b7b40474de66','includes/plus/class-wcj-plus-filters.php'=>'2b754cf485663506073633bf576128fe468a7e00be3c15447b8d7263324bdfc6'),'elite'=>array('includes/class-wcj-my-account.php'=>'7f669ea28e8ad515193083c7482c768484873190dba3cce3a2b0672a4816131b','includes/settings/wcj-settings-my-account.php'=>'8423671d907286b859afb21521efd5300a831583f08da44765eff0fe09e0da10','includes/functions/wcj-functions-core.php'=>'097f31d34847837cc8a2d9336c515c021de84b247f419eee84b7a041e1f73f99','includes/functions/wcj-functions-admin.php'=>'ec1d734592e412497818feb4e9ae4fd375c9d32de1f10ea1d10ba5eb4b9c9a75','includes/functions/wcj-functions-booster-core.php'=>'8c334071eae93a8f18b406e3e999d9586a50e12bddee0cb37e97077f9613fa6a','includes/classes/class-wcj-module.php'=>'290d825a26026a725dd43130bd28fa53222dd9cba24a856bf36d91652f1444d6','includes/core/class-wcj-admin.php'=>'85d3bdcb59405e1c8b8342c5efeb3bef88305aedb30d9675de0edbff010297c6','includes/admin/class-wcj-job-store.php'=>'16a260a57e67e283dbeed0bccca3764df3e5e31248aeecaf32d8b7b40474de66','includes/plus/class-wcj-plus-filters.php'=>'773222cc68ed22c8194adcfd31549634481215b5ebccd9f3cae6212369ca0ce8')); $bindings=array();
  foreach ( $files as $rel => $sha ) { $p = 0 === strpos( $rel, 'wp:' ) ? ABSPATH . substr( $rel, 3 ) : WC_ABSPATH . $rel; if ( ! is_readable( $p ) || hash_file( 'sha256', $p ) !== $sha ) { return array( 'safe' => false ); } $bindings[$rel] = $sha; }
  foreach ( $native[self::edition()] as $rel => $sha ) { $p = dirname(__DIR__,2) . '/' . $rel; if ( ! is_readable($p) || hash_file('sha256',$p) !== $sha ) { return array('safe'=>false); } $bindings[$rel]=$sha; }
  $bindings['task:self']=hash_file('sha256',__FILE__); $bindings['task:access']=hash_file('sha256',__DIR__.'/class-wcj-task-finder.php');
  return array('safe'=>true,'binding'=>$bindings);
 }

 /** Exact native readonly-message chain, inspected but never executed. */
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
 private static function escaping() {
  $charset=self::core_hook('option_blog_charset');$slug=self::core_hook('sanitize_title');
  if(!$charset['safe']||!$slug['safe']){return array('safe'=>false);}
  foreach(array('sanitize_text_field','esc_html','sanitize_title_for_query') as $h){if(has_filter($h)){return array('safe'=>false);}}
  return array('safe'=>true,'charset'=>$charset,'slug'=>$slug);
 }
 /** The known endpoint sanitizer belongs to a different, read-only context option. Never invoke it here. */
 private static function endpoint_sanitizer() {
  global $wp_filter;$hook='woocommerce_admin_settings_sanitize_option_woocommerce_myaccount_orders_endpoint';$bad=array('safe'=>false,'binding'=>'unqualified');
  if(!has_filter($hook)){return array('safe'=>true,'binding'=>'absent');}
  if(!defined('WC_ABSPATH')||!isset($wp_filter[$hook])||!$wp_filter[$hook] instanceof WP_Hook){return $bad;}
  $bindings=array();try{foreach($wp_filter[$hook]->callbacks as $priority=>$entries){foreach($entries as $entry){
   if(10!==(int)$priority||1!==($entry['accepted_args']??null)||'wc_sanitize_endpoint_slug'!==($entry['function']??null)){return $bad;}
   $file=realpath(WC_ABSPATH.'includes/wc-formatting-functions.php');$m=new ReflectionFunction('wc_sanitize_endpoint_slug');
   if(!$file||realpath($m->getFileName())!==$file||1!==$m->getNumberOfParameters()||1643!==$m->getStartLine()||1645!==$m->getEndLine()||hash_file('sha256',$file)!=='a4188d2d9b6786eebed14e4a3e07fc37a2c2eec48cfb6a32199a2605c3415361'){return $bad;}
   $lines=file($file,FILE_IGNORE_NEW_LINES);$body=hash('sha256',implode("\n",array_slice($lines,1642,3)));
   if('625690662af33b92d6da1bc5969c4888aeaf537b6106b54d670a3858c98fabad'!==$body){return $bad;}
   $bindings[]=array('wc_sanitize_endpoint_slug',10,1,'includes/wc-formatting-functions.php','a4188d2d9b6786eebed14e4a3e07fc37a2c2eec48cfb6a32199a2605c3415361',$body);
  }}}catch(Throwable $e){return $bad;}
  return count($bindings)===1?array('safe'=>true,'binding'=>$bindings):$bad;
 }
 /** Inspect the final Woo class. Neither the feature decision nor its unrelated option is evaluated.
  * For an array input, both branches preserve orders: only the fixed order-withdrawal key can be assigned.
  */
 private static function query_vars_filter() {
  global $wp_filter;$hook='woocommerce_get_query_vars';$bad=array('safe'=>false,'binding'=>'unqualified');
  if(!has_filter($hook)){return array('safe'=>true,'binding'=>'absent');}
  if(!isset($wp_filter[$hook])||!$wp_filter[$hook] instanceof WP_Hook){return $bad;}
  $class='Automattic\\WooCommerce\\Internal\\OrderWithdrawal\\OrderWithdrawalController';
  $spec=array('hook'=>$hook,'priority'=>10,'accepted_args'=>1,'class'=>$class,'method'=>'add_query_var','declaring'=>$class,'relative'=>'src/Internal/OrderWithdrawal/OrderWithdrawalController.php','file_sha256'=>'fa1ebdeaf89a8d6f2f86614ce8c0fa4ffd95c2dea6ca056f97bc7ce006cac5fa','body_sha256'=>'db0dd67476173e2fc62a49699da35450d61960ed4f1b2078fc283b573b2e688c','static'=>false,'parameters'=>1);
  $bindings=array();try{foreach($wp_filter[$hook]->callbacks as $priority=>$entries){foreach($entries as $entry){
   $identity=self::observer_identity($entry,$priority,$spec);if(false===$identity){return $bad;}
   $reflection=new ReflectionClass($class);$method=new ReflectionMethod($class,'add_query_var');
   if(!$reflection->isFinal()||130!==$method->getStartLine()||140!==$method->getEndLine()){return $bad;}
   $constants=array();foreach(array('FEATURE_ID'=>'order_withdrawal','ENDPOINT_KEY'=>'order-withdrawal','ENDPOINT_SLUG'=>'withdraw-order','ENDPOINT_OPTION'=>'woocommerce_myaccount_order_withdrawal_endpoint') as $name=>$expected){
    $constant=$reflection->getReflectionConstant($name);if(!$constant||!$constant->isPrivate()||$constant->getDeclaringClass()->getName()!==$class||$constant->getValue()!==$expected||in_array($expected,array('orders',self::KEY,self::RECEIPT),true)){return $bad;}$constants[$name]=$expected;
   }
   $bindings[]=array('identity'=>$identity,'constants'=>$constants,'orders'=>'unchanged-in-either-branch');
  }}}catch(Throwable $e){return $bad;}
  return count($bindings)===1?array('safe'=>true,'binding'=>$bindings):$bad;
 }
 private static function output_context() {
  global $wp_filter;$bad=array('safe'=>false);$out=array();$page_hash=null;
  $query_filter=self::query_vars_filter();$endpoint_sanitizer=self::endpoint_sanitizer();
  if(!$query_filter['safe']||!$endpoint_sanitizer['safe']||has_filter('woocommerce_account_menu_item_classes')){return $bad;}
  $specs=array(array('hook'=>'woocommerce_account_menu_items','priority'=>9223372036854775807,'accepted_args'=>1,'method'=>'customize_menu','class'=>'WCJ_My_Account','declaring'=>'WCJ_My_Account','relative'=>'native:includes/class-wcj-my-account.php','file_sha256'=>array('free'=>'b8b9f7d17e8c7d66b074ed826c598b913f93b37fee4aaeb25a3c59e7226bbcfc','plus'=>'ea61fe2662d83153ffe3667c4f0c8b9976d7f675900e6d1673d4dc127430d13c','elite'=>'7f669ea28e8ad515193083c7482c768484873190dba3cce3a2b0672a4816131b'),'body_sha256'=>array('free'=>'cf0c787abd6a4b969a0db637f3be92904c1c191cf3bbf2d421005865d9a2624c','plus'=>'cf0c787abd6a4b969a0db637f3be92904c1c191cf3bbf2d421005865d9a2624c','elite'=>'cf0c787abd6a4b969a0db637f3be92904c1c191cf3bbf2d421005865d9a2624c'),'static'=>false,'parameters'=>1),array('hook'=>'woocommerce_account_menu_items','priority'=>9223372036854775807,'accepted_args'=>1,'method'=>'add_custom_page_menu_item','class'=>'WCJ_My_Account','declaring'=>'WCJ_My_Account','relative'=>'native:includes/class-wcj-my-account.php','file_sha256'=>array('free'=>'b8b9f7d17e8c7d66b074ed826c598b913f93b37fee4aaeb25a3c59e7226bbcfc','plus'=>'ea61fe2662d83153ffe3667c4f0c8b9976d7f675900e6d1673d4dc127430d13c','elite'=>'7f669ea28e8ad515193083c7482c768484873190dba3cce3a2b0672a4816131b'),'body_sha256'=>array('free'=>'64255be7582d43b4c0fac0aa7268a7c0adb3666fc176e3245009d5684ec7566a','plus'=>'64255be7582d43b4c0fac0aa7268a7c0adb3666fc176e3245009d5684ec7566a','elite'=>'64255be7582d43b4c0fac0aa7268a7c0adb3666fc176e3245009d5684ec7566a'),'static'=>false,'parameters'=>1),array('hook'=>'woocommerce_endpoint_orders_title','priority'=>9223372036854775807,'accepted_args'=>2,'method'=>'customize_endpoint_title','class'=>'WCJ_My_Account','declaring'=>'WCJ_My_Account','relative'=>'native:includes/class-wcj-my-account.php','file_sha256'=>array('free'=>'b8b9f7d17e8c7d66b074ed826c598b913f93b37fee4aaeb25a3c59e7226bbcfc','plus'=>'ea61fe2662d83153ffe3667c4f0c8b9976d7f675900e6d1673d4dc127430d13c','elite'=>'7f669ea28e8ad515193083c7482c768484873190dba3cce3a2b0672a4816131b'),'body_sha256'=>array('free'=>'36ee5ff42cdd7d4d1b505d18254729bb00b689896218016dba10e4aa19a7bdad','plus'=>'36ee5ff42cdd7d4d1b505d18254729bb00b689896218016dba10e4aa19a7bdad','elite'=>'36ee5ff42cdd7d4d1b505d18254729bb00b689896218016dba10e4aa19a7bdad'),'static'=>false,'parameters'=>2),array('hook'=>'woocommerce_get_endpoint_url','priority'=>9223372036854775807,'accepted_args'=>4,'method'=>'customize_menu_custom_endpoints','class'=>'WCJ_My_Account','declaring'=>'WCJ_My_Account','relative'=>'native:includes/class-wcj-my-account.php','file_sha256'=>array('free'=>'b8b9f7d17e8c7d66b074ed826c598b913f93b37fee4aaeb25a3c59e7226bbcfc','plus'=>'ea61fe2662d83153ffe3667c4f0c8b9976d7f675900e6d1673d4dc127430d13c','elite'=>'7f669ea28e8ad515193083c7482c768484873190dba3cce3a2b0672a4816131b'),'body_sha256'=>array('free'=>'961ffd0bc6b39855242590b5df69994511e8d73b2b83a51e0d66e5378741845e','plus'=>'961ffd0bc6b39855242590b5df69994511e8d73b2b83a51e0d66e5378741845e','elite'=>'961ffd0bc6b39855242590b5df69994511e8d73b2b83a51e0d66e5378741845e'),'static'=>false,'parameters'=>4),array('hook'=>'woocommerce_get_endpoint_url','priority'=>9223372036854775807,'accepted_args'=>4,'method'=>'set_custom_page_url','class'=>'WCJ_My_Account','declaring'=>'WCJ_My_Account','relative'=>'native:includes/class-wcj-my-account.php','file_sha256'=>array('free'=>'b8b9f7d17e8c7d66b074ed826c598b913f93b37fee4aaeb25a3c59e7226bbcfc','plus'=>'ea61fe2662d83153ffe3667c4f0c8b9976d7f675900e6d1673d4dc127430d13c','elite'=>'7f669ea28e8ad515193083c7482c768484873190dba3cce3a2b0672a4816131b'),'body_sha256'=>array('free'=>'a77200f853f0f397db270db0680a7ad3ce2780f48f782ba3389c511fd31207c9','plus'=>'a77200f853f0f397db270db0680a7ad3ce2780f48f782ba3389c511fd31207c9','elite'=>'a77200f853f0f397db270db0680a7ad3ce2780f48f782ba3389c511fd31207c9'),'static'=>false,'parameters'=>4));
  foreach(array('woocommerce_account_menu_items','woocommerce_endpoint_orders_title','woocommerce_get_endpoint_url') as $hook){
   $out[$hook]=array();if(!has_filter($hook)){continue;}
   if(!isset($wp_filter[$hook])||!$wp_filter[$hook] instanceof WP_Hook){return $bad;}
   $seen=array();foreach($wp_filter[$hook]->callbacks as $priority=>$entries){foreach($entries as $entry){$ok=false;
    foreach($specs as $spec){if($spec['hook']!==$hook){continue;}$id=self::observer_identity($entry,$priority,$spec);if(false===$id){continue;}
     if(isset($seen[$spec['method']])||self::ID!==($entry['function'][0]->id??null)){return $bad;}
     if(in_array($spec['method'],array('add_custom_page_menu_item','set_custom_page_url'),true)){
      $pages=$entry['function'][0]->custom_pages??null;if(!is_array($pages)||count($pages)>32){return $bad;}
      foreach($pages as $k=>$page){if(!is_string($k)||strlen($k)>512||!is_array($page)||array_keys($page)!==array('endpoint','title','content')){return $bad;}foreach($page as $v){if(!is_string($v)||strlen($v)>16384||1!==preg_match('//u',$v)){return $bad;}}}
      $h=hash('sha256',serialize($pages));if(null!==$page_hash&&$h!==$page_hash){return $bad;}$page_hash=$h;
     }
     $seen[$spec['method']]=true;$out[$hook][]=$id;$ok=true;break;
    }if(!$ok){return $bad;}
   }}
  }
  // Inspect, never call, the already constructed Woo query map.
  $wc=function_exists('WC')?WC():null;$q=$wc->query??null;
  if(!is_object($q)||'WC_Query'!==get_class($q)){return $bad;}
  try{$p=new ReflectionProperty('WC_Query','query_vars');$vars=$p->getValue($q);}catch(Throwable $e){return $bad;}
  if(!is_array($vars)||!is_string($vars['orders']??null)){return $bad;}
  return array('safe'=>true,'hooks'=>$out,'orders_endpoint'=>$vars['orders'],'custom_pages_hash'=>$page_hash,'query_filter'=>$query_filter,'endpoint_sanitizer'=>$endpoint_sanitizer);
 }
 private static function refusal( $c ) {
  if(!$c['platform']['safe']||!$c['paid']['safe']||!$c['global_update']['safe']||!$c['escaping']['safe']||!$c['output']['safe']||$c['hooks']){return 'Another integration or a changed installation prevents a safe label-only edit. Your settings are unchanged. Use full My Account settings.';}
  foreach($c['observers'] as $observer){if(!$observer['safe']){return 'A relevant settings observer could not be qualified. Use full settings; no change was made.';}}
  if(empty($c['roles'])||empty($c['role_rows'])){return 'Current role storage could not be qualified. Use full settings.';}
  $core=WCJ_Job_Store::core();if(!$core||!isset($core->all_modules[self::ID])){return 'The My Account module is unavailable.';}
  foreach(self::flags() as $k){if(!in_array(self::raw($c,$k,'no'),array('yes','no'),true)){return 'A saved My Account switch has an unsupported value. Full settings preserve it.';}}
  if(!in_array(self::raw($c,'blog_charset'),array('UTF-8','utf-8','UTF8','utf8'),true)){return 'This task supports a saved UTF-8 site character set. Use full settings.';}
  foreach($c['rows'] as $s){if(!self::valid_state($s)){return 'A saved storage value is not supported. Inspect it in full settings.';}}
  $raw=self::raw($c,self::KEY);$map=null===$raw?array():self::decode($raw);
  if(null===$map|| (array_key_exists('orders',$map)&&!self::plain($map['orders'],true))){return 'The saved labels contain an unsupported structure or advanced Orders wording. They were kept unchanged. Use full settings.';}
  $overlap=self::overlap($c);if($overlap){return $overlap;}
  if(null!==self::raw($c,self::RECEIPT)&&!self::record($c)){return 'The previous task receipt could not be verified. It was kept unchanged; use full settings.';}
  return self::storage_reason();
 }
 private static function record( $c ) {
  $raw=self::raw($c,self::RECEIPT); $r=is_string($raw) && strlen($raw)<=71680 && is_serialized($raw) ? @unserialize($raw,array('allowed_classes'=>false,'max_depth'=>8)) : null;
  if ( ! is_array($r) || serialize($r)!==$raw || ! is_string($r['signature']??null) ) { return null; }
  $sig=$r['signature']; unset($r['signature']); return hash_equals(hash_hmac('sha256',serialize($r),wp_salt('nonce')),$sig)?$r:null;
 }
 private static function valid_state( $s ) { return null === $s || ( is_array($s) && array_keys($s)===array('raw','autoload') && is_string($s['raw']) && in_array($s['autoload'],array('yes','no','on','off','auto','auto-on','auto-off'),true) ); }
 private static function own_record( $c,$r ) {
  return is_array($r) && 1===($r['version']??null) && get_current_blog_id()===($r['blog']??null) && get_current_user_id()===($r['user']??null) && self::edition()===($r['edition']??null) && $c['target']['key']===($r['key']??null) && self::ID===($r['task']??null) && array_key_exists('before',$r) && array_key_exists('after',$r) && self::target_state($r['before']) && self::target_state($r['after']) && is_string($r['after_guard']??null) && in_array($r['status']??null,array('applied','undone'),true);
 }
 private static function undo_allowed( $c,$r ) { return self::own_record($c,$r) && 'applied'===$r['status'] && self::state($c,$r['key'])===$r['after'] && hash_equals(self::hash($c,false),$r['after_guard']); }
 private static function view($c) {
  $r=self::record($c);$own=self::own_record($c,$r);$reason=self::refusal($c);$s=self::state($c,self::KEY);$map=null===$s?array():self::decode($s['raw']);
  $present=is_array($map)&&array_key_exists('orders',$map);$text=$present&&self::plain($map['orders'],true)?$map['orders']:'';
  $shown=null===$map||($present&&!self::plain($map['orders'],true))?'Advanced saved labels (kept unchanged)':(!$present?'No saved Orders label; the supplied wording is used.':(''===$text?'Blank saved Orders label; the supplied wording is used.':$text));
  $conditions=array('My Account module: '.('yes'===self::raw($c,self::MODULE,'no')?'On':'Off').'.','Customize Menu & Endpoints: '.('yes'===self::raw($c,'wcj_my_account_menu_customize_enabled','no')?'On':'Off').'.');
  $conditions[]='yes'!==self::raw($c,self::MODULE,'no')||'yes'!==self::raw($c,'wcj_my_account_menu_customize_enabled','no')?'Saving here changes stored wording only. Both switches must be On for Booster to use it; this task does not turn them on.':'Both saved switches are On. Check the signed-in account menu and Orders page after saving; this is not proof of the theme display.';
  $receipt='';if($own){$receipt='undone'===$r['status']?'Previous saved-label state restored and read back.':'Orders wording saved and read back. This receipt does not confirm customer-facing output.';}
  return array('guard'=>self::hash($c),'field'=>self::KEY.'[orders]','exists'=>null!==$s,'leaf_exists'=>$present,'value'=>$text,'raw'=>$present&&self::plain($map['orders'],true)?$map['orders']:'[Other labels and unsupported content are not shown]','saved'=>$shown,'editable'=>''===$reason,'reason'=>$reason,'conditions'=>$conditions,'undo'=>!$reason&&self::undo_allowed($c,$r),'receipt'=>$receipt,'receipt_current'=>$own&&self::state($c,self::KEY)===$r['after'],'history_stale'=>$own&&!self::undo_allowed($c,$r)&&'applied'===$r['status'],'edition'=>ucfirst(self::edition()));
 }
 public static function load() { self::authorize(); return self::view(self::context()); }
 public static function resume() { try { self::authorize(); $c=self::context(); $r=self::record($c); return self::own_record($c,$r) && 'applied'===$r['status'] && self::state($c,$r['key'])===$r['after'] ? self::url() : ''; } catch (Throwable $e) { return ''; } }
 private static function readable( $value ) { return is_string($value) && 1===preg_match('//u',$value) ? $value : '[Non-UTF-8 stored bytes preserved; inspect full settings]'; }
 /** Exact byte/autoload CAS, confined to the server-resolved key and task receipt, inside an owned transaction. */
 private static function write( $c,$key,$after ) {
  global $wpdb;if(!in_array($key,array($c['target']['key'],self::RECEIPT),true)){self::fail('Unexpected write target.',500);}
  $before=self::state($c,$key);if($before===$after){return;}
  if(null===$before){if(null===$after){return;}$n=$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,%s)",$key,$after['raw'],$after['autoload']));}
  elseif(null===$after){$n=$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND BINARY option_value=%s AND BINARY autoload=%s",$key,$before['raw'],$before['autoload']));}
  else{$n=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=%s,autoload=%s WHERE option_name=%s AND BINARY option_value=%s AND BINARY autoload=%s",$after['raw'],$after['autoload'],$key,$before['raw'],$before['autoload']));}
  if(1!==$n){self::fail('A concurrent change or storage error stopped the transaction. No successful save is claimed.',409);}
 }
 private static function clear_cache( $key ) {
  $core=WCJ_Job_Store::core();foreach(array($key,self::RECEIPT) as $k){if(!$k){continue;}wp_cache_delete($k,'options');if($core){unset($core->options[$k]);}}wp_cache_delete('alloptions','options');wp_cache_delete('notoptions','options');
  // No native render callback is executed. This task reports persistence, not same-request output.
 }
 public static function mutate( $guard,$value,$undo=false ) {
  global $wpdb; self::authorize();$why=self::storage_reason();if($why){self::fail($why);}
  $lock='wcj_account_label_'.substr(hash('sha256',$wpdb->prefix.DB_NAME),0,40);
  if('1'!==(string)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,5)',$lock))){self::fail('Another account label edit is running. Your draft is retained.',409);}
  $begun=false;$key='';$commit_attempted=false;
  try{
   if(false===$wpdb->query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE')||false===$wpdb->query('START TRANSACTION')){self::fail('A safe transaction could not begin.',500);}$begun=true;
   $c=self::context(true);self::authorize();$key=$c['target']['key'];
   if(!hash_equals(self::hash($c),$guard)){self::fail('Settings changed. Your draft is parked. Discard and load current values.',409);}
   $why=self::refusal($c);if($why){self::fail($why);}$before=self::state($c,$key);
   if($undo){$r=self::record($c);if(!self::undo_allowed($c,$r)){self::fail('Undo no longer matches this exact saved context. Newer settings were kept.',409);}$after=$r['before'];}
   else{$label=self::packed($value);$after=self::patch_state($before,$label);}
   if($before===$after){return self::view($c);}
   self::write($c,$key,$after);$expected=$c;if(null===$after){unset($expected['rows'][$key]);}else{$expected['rows'][$key]=$after;}ksort($expected['rows']);$check=self::context(true);
   if(!hash_equals(self::hash($expected),self::hash($check))){self::fail('Readback or context changed. The transaction was not accepted.',409);}
   $record=array('version'=>1,'blog'=>get_current_blog_id(),'user'=>get_current_user_id(),'edition'=>self::edition(),'key'=>$key,'task'=>self::ID,'before'=>$before,'after'=>$after,'status'=>$undo?'undone':'applied','time'=>time(),'after_guard'=>self::hash($check,false));
   $record['signature']=hash_hmac('sha256',serialize($record),wp_salt('nonce'));
   $rs=array('raw'=>serialize($record),'autoload'=>isset($c['rows'][self::RECEIPT])?$c['rows'][self::RECEIPT]['autoload']:'off');self::write($check,self::RECEIPT,$rs);
   $expected['rows'][self::RECEIPT]=$rs;ksort($expected['rows']);$verified=self::context(true);self::authorize();
   if(!hash_equals(self::hash($expected),self::hash($verified))||!self::own_record($verified,self::record($verified))){self::fail('Receipt or context readback failed. The transaction was not accepted.',409);}
   $commit_attempted=true;$commit=$wpdb->query('COMMIT');$begun=false;
   if(false===$commit){self::clear_cache($key);self::fail('Commit outcome is unknown. Discard and load current values before another change.',500);}
   self::clear_cache($key);self::authorize();$fresh=self::context();
   if(!hash_equals(self::hash($verified),self::hash($fresh))){self::fail('The transaction committed, but current readback changed. No current saved-state success is claimed. Discard and load current values.',409);}
   return self::view($fresh);
  } finally {
   if($begun){$rollback=$wpdb->query('ROLLBACK');self::clear_cache($key);if(false===$rollback){$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));self::fail($commit_attempted?'Commit outcome is unknown. Load current values before another change.':'Rollback could not be verified. Load current values before another change.',500);}}
   $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));
  }
 }
 public function handle() {
  try{
   if('POST'!==($_SERVER['REQUEST_METHOD']??'')||!empty($_FILES)){self::fail('Use the task form with a POST request.',403);}self::authorize();
   $action=$_POST['action']??'';$op=is_string($action)?substr($action,strlen('wcj_account_orders_label_job_')):'';
   if(!in_array($op,self::OPS,true)||'wcj_account_orders_label_job_'.$op!==$action){self::fail('Unknown task operation.',403);}
   $allowed='load'===$op?array('action','nonce'):('save'===$op?array('action','nonce','guard','value'):array('action','nonce','guard'));$actual=array_keys($_POST);sort($actual);sort($allowed);
   if($actual!==$allowed){self::fail('Unexpected or missing task fields.',403);}foreach($_POST as $v){if(!is_string($v)||strlen($v)>2000){self::fail('Task fields must be bounded strings.',403);}}
   if(!wp_verify_nonce(wp_unslash($_POST['nonce']),'wcj_account_orders_label_job_'.$op)){self::fail('This task token expired. Reload deliberately; your draft is retained.',403);}
   if('load'===$op){$result=self::load();}else{$guard=wp_unslash($_POST['guard']);if(!preg_match('/^[a-f0-9]{64}$/D',$guard)){self::fail('Load the current account labels before changing them.',409);}$result=self::mutate($guard,'save'===$op?wp_unslash($_POST['value']):'','undo'===$op);}
   nocache_headers();wp_send_json_success($result);
  }catch(Throwable $e){$code=in_array($e->getCode(),array(403,409,422),true)?$e->getCode():500;nocache_headers();wp_send_json_error(array('message'=>500===$code?'The outcome could not be verified. No successful save or rollback is claimed. Your draft is parked; discard and load current values before another change.':$e->getMessage(),'kind'=>'parked'),$code);}
 }
 public function render() {
  self::authorize();wp_enqueue_style('wcj-account-orders-label-job',wcj_plugin_url().'/assets/css/admin/wcj-account-orders-label-job.css',array(),'1.0');wp_enqueue_script('wcj-account-orders-label-job',wcj_plugin_url().'/assets/js/admin/wcj-account-orders-label-job.js',array(),'1.0',true);
  $nonces=array();foreach(self::OPS as $op){$nonces[$op]=wp_create_nonce('wcj_account_orders_label_job_'.$op);}wp_localize_script('wcj-account-orders-label-job','wcjAccountOrdersLabelJob',array('url'=>admin_url('admin-ajax.php'),'nonces'=>$nonces,'storage'=>'wcj-account-label-'.get_current_blog_id().'-'.get_current_user_id().'-'.self::edition()));
  include __DIR__.'/views/account-orders-label-job.php';
 }
}
new WCJ_Account_Orders_Label_Job();

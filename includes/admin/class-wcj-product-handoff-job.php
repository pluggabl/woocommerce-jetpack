<?php
/** Bounded, read-only product handoff. No native exporter, stored file or receipt. */
defined( 'ABSPATH' ) || exit;
final class WCJ_Product_Handoff_Refusal extends RuntimeException {}
final class WCJ_Product_Handoff_Job {
 const FIELDS = array( 'id'=>'ID', 'name'=>'Name', 'sku'=>'SKU', 'status'=>'Status', 'type'=>'Type' );
 const OPS = array( 'preview', 'download' );
 public function __construct() { foreach ( self::OPS as $op ) { add_action( 'wp_ajax_wcj_product_handoff_'.$op, array( $this, 'handle' ) ); } }
 private static function fail( $message, $code=422 ) { throw new WCJ_Product_Handoff_Refusal( $message, $code ); }
 public static function authorize() {
  if ( ! WCJ_Data_Exchange_Workspace::allowed( 'export' ) || ! function_exists( 'wcj_is_module_enabled' ) || ! wcj_is_module_enabled( 'export' ) ) { self::fail( 'Export is off or unavailable to you. Nothing was downloaded. Ask an administrator or use full settings.', 403 ); }
 }
 public static function parse_request( $ids, $fields, $nonpublic ) {
  if ( ! is_string($ids) || strlen($ids)>4096 || ! is_array($fields) || count($fields)>5 || ! in_array($nonpublic,array('0','1'),true) ) { self::fail('Use up to 20 product IDs and the fields shown in this form.'); }
  $entries=array_values(array_filter(array_map('trim',preg_split('/,|\r\n|\r|\n/',$ids)),static function($entry){return ''!==$entry;}));
  if(count($entries)>20){self::fail('There are more than 20 entries. Duplicates and invalid entries count too. Remove entries and preview again.');}
  if(!$entries){self::fail('Enter at least one product ID.');}if(1!==preg_match('//u',$ids)){self::fail('Enter product IDs using ordinary digits, commas and new lines.');}
  foreach($fields as $f){if(!is_string($f)||!array_key_exists($f,self::FIELDS)){self::fail('Choose only the five fields shown in this form.');}}
  if(count(array_unique($fields))!==count($fields)||!in_array('id',$fields,true)||!in_array('status',$fields,true)){self::fail('ID and Status are always included. Choose each optional field only once.');}
  $ordered=array();foreach(self::FIELDS as $k=>$v){if(in_array($k,$fields,true)){$ordered[]=$k;}}
  return array('entries'=>$entries,'fields'=>$ordered,'nonpublic'=>$nonpublic);
 }
 private static function id( $s ) {
  if(!is_string($s)||strlen($s)>strlen((string)PHP_INT_MAX)||!preg_match('/\A[1-9][0-9]*\z/D',$s)){return null;}
  if(strlen($s)===strlen((string)PHP_INT_MAX)&&strcmp($s,(string)PHP_INT_MAX)>0){return null;}return (int)$s;
 }
 public static function protect_cell( $value ) {
  if(!is_string($value)||strlen($value)>4096||1!==preg_match('//u',$value)||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u',$value)){self::fail('A selected product has text this handoff cannot safely represent. Use the existing editor or export tool.');}
  $protect=1===preg_match('/\A[\p{Z}\p{C}\s]*(?:[=+\-@]|\t|\r)/u',$value);
  $value=str_replace(array("\r\n","\r"),"\n",$value);
  return ($protect?"'":'').$value;
 }
 private static function preflight_product_attributes( $id ) {
  $attrs=get_post_meta($id,'_product_attributes',true);
  if(empty($attrs)){return;}
  if(!is_array($attrs)){self::fail('This product needs an update in the product editor before it can be included. Nothing was exported.');}
  foreach($attrs as $key=>$row){
   if(!is_array($row)||!is_string($row['name']??'')||(!is_scalar($row['is_variation']??0)&&null!==($row['is_variation']??0))){self::fail('This product needs an update in the product editor before it can be included. Nothing was exported.');}
   $name=$row['name']??'';
   if(!empty($row['is_variation'])&&false!==strpos($name,'/')&&sanitize_title($name)!==$key){self::fail('This product needs an update in the product editor before it can be included. Nothing was exported.');}
  }
 }
 private static function assert_csv_output_clean() {
  if(headers_sent()){self::fail('The download could not be started safely.',500);}
  foreach(ob_get_status(true) as $buffer){
   if('default output handler'!==($buffer['name']??'')||0!==($buffer['buffer_used']??-1)){self::fail('The download could not be started safely.',500);}
  }
 }
 private static function collect( $request ) {
  $rows=array();$excluded=array();$seen=array();
  $classes=array('simple'=>'WC_Product_Simple','variable'=>'WC_Product_Variable','grouped'=>'WC_Product_Grouped','external'=>'WC_Product_External');
  foreach($request['entries'] as $entry){
   $id=self::id($entry);$reason='';
   if(null===$id){$reason='Not a whole positive product ID';}
   elseif(isset($seen[$id])){$reason='Duplicate entry';}
   else{
    $seen[$id]=true;
    if(!current_user_can('edit_post',$id)){$reason='Not found or not available to you';}
    else{
     $post=get_post($id);
     if(!$post||!in_array($post->post_type,array('product','product_variation'),true)){$reason='Not found or not available to you';}
     elseif('product_variation'===$post->post_type){$reason='Variation: enter its parent product';}
     elseif('trash'===$post->post_status){$reason='In trash';}
     elseif(!in_array($post->post_status,array('publish','draft','pending','private'),true)){$reason='This product status is not supported';}
     elseif('publish'!==$post->post_status&&'1'!==$request['nonpublic']){$reason='Not published: select Include drafts, pending and private products to include it';}
     else{
      self::preflight_product_attributes($id);
      $product=wc_get_product($id);
      if(!$product||!is_a($product,'WC_Product')||(int)$product->get_id()!==$id){$reason='Not found or not available to you';}
      else{
       $type=$product->get_type();
       if(!isset($classes[$type])||get_class($product)!==$classes[$type]){$reason='This product type or custom product class is not supported';}
       elseif($product->get_status('edit')!==$post->post_status){self::fail('A product changed while it was read. Preview again.',409);}
       else{
        $values=array('id'=>(string)$id,'status'=>$post->post_status,'type'=>$type);
        if(in_array('name',$request['fields'],true)){$values['name']=$product->get_name('edit');}
        if(in_array('sku',$request['fields'],true)){$values['sku']=$product->get_sku('edit');}
        $cells=array();foreach($request['fields'] as $f){$cells[]=self::protect_cell($values[$f]);}
        if(!current_user_can('edit_post',$id)){self::fail('Product access changed. Nothing was downloaded. Preview again.',403);}
        $rows[]=$cells;
       }
      }
     }
    }
   }
   if($reason){$excluded[]=array('entry'=>$entry,'reason'=>$reason);}
  }
  return array('fields'=>$request['fields'],'rows'=>$rows,'excluded'=>$excluded,'count'=>count($rows));
 }
 private static function json( $value ) { $s=json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if(false===$s){self::fail('The selected text could not be represented safely.');}return $s; }
 private static function digest( $result ) { return hash('sha256',self::json($result)); }
 private static function session() { return hash_hmac('sha256',(string)wp_get_session_token(),wp_salt('auth')); }
 private static function encode( $data ) {return rtrim(strtr(base64_encode(self::json($data)),'+/','-_'),'=');}
 private static function sign( $data ) {$s=self::encode($data);return $s.'.'.hash_hmac('sha256',$s,wp_salt('auth'));}
 private static function decode( $token ) {
  if(!is_string($token)||strlen($token)>16000||!preg_match('/\A([A-Za-z0-9_-]+)\.([a-f0-9]{64})\z/D',$token,$m)||!hash_equals(hash_hmac('sha256',$m[1],wp_salt('auth')),$m[2])){self::fail('This preview cannot be checked. Preview again to download.',409);}
  $decoded=base64_decode(strtr($m[1],'-_','+/'),true);$p=is_string($decoded)?json_decode($decoded,true,12):null;
  if(!is_array($p)||self::encode($p)!==$m[1]||array_keys($p)!==array('version','user','blog','edition','session','issued','expires','identity','request','digest')||1!==$p['version']||get_current_user_id()!==$p['user']||get_current_blog_id()!==$p['blog']||WCJ_Data_Exchange_Workspace::edition()!==$p['edition']||!is_string($p['session'])||!hash_equals(self::session(),$p['session'])||!is_int($p['issued'])||!is_int($p['expires'])||$p['issued']>time()||$p['expires']!==$p['issued']+600||$p['expires']<=time()||!is_string($p['identity'])||!preg_match('/\A[a-f0-9]{32}\z/D',$p['identity'])||!is_string($p['digest'])||!preg_match('/\A[a-f0-9]{64}\z/D',$p['digest'])){self::fail('This preview expired or no longer belongs to this session. Preview again to download.',409);}
  $r=$p['request'];if(!is_array($r)||array_keys($r)!==array('entries','fields','nonpublic')||!is_array($r['entries'])||count($r['entries'])>20||!$r['entries']){self::fail('Preview again to download.',409);}
  foreach($r['entries'] as $entry){if(!is_string($entry)||strpbrk($entry,",\r\n")!==false){self::fail('Preview again to download.',409);}}
  if(self::parse_request(implode(',',$r['entries']),$r['fields'],$r['nonpublic'])!==$r){self::fail('Preview again to download.',409);}return $p;
 }
 public static function preview( $ids, $fields, $nonpublic ) {
  self::authorize();$request=self::parse_request($ids,$fields,$nonpublic);$result=self::collect($request);self::authorize();$now=time();$token='';
  if($result['count']){$token=self::sign(array('version'=>1,'user'=>get_current_user_id(),'blog'=>get_current_blog_id(),'edition'=>WCJ_Data_Exchange_Workspace::edition(),'session'=>self::session(),'issued'=>$now,'expires'=>$now+600,'identity'=>bin2hex(random_bytes(16)),'request'=>$request,'digest'=>self::digest($result)));}
  if(strlen($token)>16000){self::fail('This list cannot be kept as a usable preview. Shorten or remove invalid entries and preview again.');}
  return $result+array('checked_at'=>$now,'expires'=>$now+600,'token'=>$token);
 }
 public static function csv( $fields, $rows ) {
  $records=array(array_map(static function($f){return self::FIELDS[$f];},$fields));foreach($rows as $row){$records[]=$row;}$out="\xEF\xBB\xBF";
  foreach($records as $row){$out.=implode(',',array_map(static function($v){return '"'.str_replace('"','""',$v).'"';},$row))."\r\n";}return $out;
 }
 public static function download( $token ) {
  self::authorize();$p=self::decode($token);$result=self::collect($p['request']);self::authorize();
  if($p['expires']<=time()){self::fail('This preview expired. Preview again to download.',409);}if(!$result['count']||!hash_equals($p['digest'],self::digest($result))){self::fail('One or more products or access rules changed after preview, so nothing was downloaded. Preview again to see current rows.',409);}
  return self::csv($result['fields'],$result['rows']);
 }
 public function handle() {
  try{
   if('POST'!==($_SERVER['REQUEST_METHOD']??'')||!empty($_FILES)){self::fail('Use the product handoff form.',403);}self::authorize();
   $action=$_POST['action']??'';$op=is_string($action)?substr($action,strlen('wcj_product_handoff_')):'';
   if(!in_array($op,self::OPS,true)||'wcj_product_handoff_'.$op!==$action){self::fail('Unknown handoff operation.',403);}
   $keys=array_keys($_POST);$want='preview'===$op?array('action','nonce','ids','fields','nonpublic'):array('action','nonce','token');sort($keys);sort($want);
   if($keys!==$want||!is_string($_POST['nonce'])||strlen($_POST['nonce'])>100||!wp_verify_nonce(wp_unslash($_POST['nonce']),'wcj_product_handoff_'.$op)){self::fail('This form expired. Reload this page and preview again.',403);}
   if('preview'===$op){$r=self::preview(wp_unslash($_POST['ids']),wp_unslash($_POST['fields']),wp_unslash($_POST['nonpublic']));nocache_headers();wp_send_json_success($r);return;}
   $csv=self::download(wp_unslash($_POST['token']));
   self::assert_csv_output_clean();nocache_headers();self::assert_csv_output_clean();header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="booster-product-handoff.csv"');header('X-Content-Type-Options: nosniff');header('Cache-Control: no-store, private, max-age=0');header('X-WCJ-Handoff: csv-v1');header('Content-Length: '.strlen($csv));echo $csv;exit;
  }catch(Throwable $e){$code=$e instanceof WCJ_Product_Handoff_Refusal && in_array($e->getCode(),array(403,409,422),true)?$e->getCode():500;nocache_headers();wp_send_json_error(array('message'=>500===$code?'The handoff could not be prepared safely. Nothing was downloaded. Preview again or use full settings.':$e->getMessage()),$code);}
 }
}

<?php
/** Portable retained908 assertions; pinned real Woo classes, inert storage, no WP bootstrap. */
if ('cli' !== PHP_SAPI || defined('WPINC') || class_exists('WC_Data', false)) { throw new RuntimeException('OFFLINE_CLI_ONLY'); }
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
function need858($ok,$code){if(!$ok)throw new RuntimeException($code);}
function load858($r){if(isset($r['code'])){eval('?>'.$r['code']);return;}need858(filesize($r['path'])===$r['bytes']&&hash_file('sha256',$r['path'])===$r['sha256'],'BOUND_INPUT');require_once$r['path'];}
need858($argc===5,'ARGS_PLUGIN_WOO_WP_ROOTS_AND_TIER');require __DIR__.'/fixtures/payment-revision/binder.php';$tier=$argv[4];$variant='after';$b=codex_revision_bind($argv[1],$argv[2],$argv[3],$tier);need858(isset($b['views'][$tier.'-'.$variant]),'VIEW');
define('ABSPATH',__DIR__.'/');$GLOBALS['zone858']='UTC';$GLOBALS['offset858']=0;$GLOBALS['filter858']=false;$GLOBALS['hits858']=0;
function get_option($key,$default=false){return$key==='timezone_string'?$GLOBALS['zone858']:$default;}
function wc_timezone_string(){return$GLOBALS['zone858'];}
function wc_timezone_offset(){return$GLOBALS['offset858'];}
function apply_filters($name,$value){if($GLOBALS['filter858']&&$name==='woocommerce_data_get_date_modified'){++$GLOBALS['hits858'];if($value instanceof WC_DateTime){$copy=clone$value;$copy->modify('+1 day');return$copy;}}return$value;}
load858($b['wp_list_util']);load858($b['wp_list_pluck']);class WCJ_Module{}
load858($b['dependencies']['includes/abstracts/abstract-wc-data.php']);load858($b['dependencies']['includes/class-wc-datetime.php']);load858($b['dependencies']['includes/class-wc-meta-data.php']);load858($b['order_date_class']);load858($b['views'][$tier.'-'.$variant]);
class Order858 extends WC_Order_Date843 {public $fixture_items=array();public function get_items($type='line_item'){return$this->fixture_items[$type]??array();}public function set_unrelated($value){$this->set_prop('unrelated',$value);}}
class Item858 extends WC_Data {protected $id=7;protected $data=array('name'=>'Synthetic fee','total'=>'0');public function get_type(){return'fee';}}
$r=new ReflectionClass('WCJ_Payment_Gateways_Fees');$m=$r->newInstanceWithoutConstructor();$snap=$r->getMethod('get_order_fee_snapshot');$snap->setAccessible(true);$project=$r->getMethod('project_order_fee_value');$project->setAccessible(true);$reflect=$r->getMethod('reflect_order_fee_result');$reflect->setAccessible(true);$checks=array();
function eq858($name,$expected,$actual){$GLOBALS['checks'][]=array('name'=>$name,'pass'=>$expected===$actual,'expected'=>$expected,'actual'=>$actual);}
function throws858($name,$call){try{$call();eq858($name,true,false);}catch(RuntimeException$e){eq858($name,true,true);}}
function rows858($records){$o=new Order858();$o->set_meta_data($records);$o->set_object_read(true);return$o;}
function record858($id,$key,$value){return array('id'=>$id,'key'=>$key,'value'=>$value);}
function absint($n){return abs((int)$n);}
function do_action($name,...$args){}
function maybe_unserialize($value){return$value;}
class MetaStore891 {
 public $rows=array();public $next=100;public $adds=0;public $updates=0;public $deletes=0;
 public function get_internal_meta_keys(){return array();}
 public function read_meta($object){return array_map(static function($row){return(object)array('meta_id'=>$row['id'],'meta_key'=>$row['key'],'meta_value'=>$row['value']);},array_values($this->rows));}
 public function add_meta($object,$meta){$id=$this->next++;$this->rows[$id]=array('id'=>$id,'key'=>$meta->key,'value'=>$meta->value);++$this->adds;return$id;}
 public function update_meta($object,$meta){need858(isset($this->rows[$meta->id]),'UPDATE_EXISTING_ID');$this->rows[$meta->id]=array('id'=>$meta->id,'key'=>$meta->key,'value'=>$meta->value);++$this->updates;}
 public function delete_meta($object,$meta){unset($this->rows[$meta->id]);++$this->deletes;}
}
class Order891 extends Order858 {
 protected $pending891=array(99=>'retained individual deletion queue');
 public function use_store891($store){$this->data_store=$store;$this->set_id(13);}
 public function pending891(){return$this->pending891;}
}
function order891($store,$records){$o=new Order891();$o->use_store891($store);$raw=array_map(static function($row){return(object)array('meta_id'=>$row['id'],'meta_key'=>$row['key'],'meta_value'=>$row['value']);},$records);$o->init_meta_data($raw);$o->set_object_read(true);return$o;}
function revisions891($store){return array_values(array_filter($store->rows,static function($row){return$row['key']==='_wcj_gateway_fee_revision';}));}
function revision_meta891($order){return array_values(array_filter($order->get_meta_data(),static function($meta){return$meta->key==='_wcj_gateway_fee_revision';}));}

$key891='_wcj_gateway_fee_revision';
// Actual WC_Data key-only update + save_meta_data, with an inert row adapter.
// Both clean positive persisted records exist before this no-op reflection.
$one=record858(41,$key891,'same-revision');$two=record858(42,$key891,'same-revision');
$store=new MetaStore891();$store->rows=array(41=>$one,42=>$two);$c=order891($store,array($two,$one));$p=order891($store,array($one,$two));$objects=$c->get_meta_data();$initial=$store->rows;$reflect->invoke($m,$c,$p);
eq858('908-duplicate-noop-caller-record-count',2,count(revision_meta891($c)));
eq858('908-duplicate-noop-records-stay-clean',array(array(),array()),array_map(static function($meta){return$meta->get_changes();},$objects));
eq858('908-duplicate-noop-no-reflection-storage',array(0,0,0),array($store->adds,$store->updates,$store->deletes));
$c->save_meta_data();eq858('908-duplicate-noop-next-save-preserves-rows',$initial,$store->rows);eq858('908-duplicate-noop-next-save-deletes-zero',0,$store->deletes);
// A real authoritative write collapses its own duplicate through Woo's real API.
// Its retained caller may have a different record order (HPOS has no ORDER BY).
$store=new MetaStore891();$store->rows=array(41=>$one,42=>$two);$c=order891($store,array($two,$one));$p=order891($store,array($one,$two));$p->update_meta_data($key891,'changed-revision');$p->save_meta_data();eq858('908-real-write-authoritative-singleton',array(41=>record858(41,$key891,'changed-revision')),$store->rows);$reflect->invoke($m,$c,$p);
eq858('908-real-write-caller-authoritative-ID',array(41),array_map(static function($meta){return$meta->id;},revision_meta891($c)));
$save_ok=true;try{$c->save_meta_data();}catch(RuntimeException$e){$save_ok=false;}
eq858('908-real-write-next-save-no-deleted-ID-update',true,$save_ok);eq858('908-real-write-next-save-exact-row',array(41=>record858(41,$key891,'changed-revision')),$store->rows);eq858('908-real-write-no-new-insert',0,$store->adds);
if($variant==='after'){
// No revision or existing singleton is not an insertion/cleanup opportunity.
$store=new MetaStore891();$c=order891($store,array());$p=order891($store,array());$reflect->invoke($m,$c,$p);$c->save_meta_data();eq858('908-absent-noop-no-empty-insert',array(),$store->rows);
$store=new MetaStore891();$store->rows=array(41=>$one);$c=order891($store,array($one));$p=order891($store,array($one));$original=$c->get_meta_data()[0];$reflect->invoke($m,$c,$p);eq858('908-singleton-noop-object-identity',true,$original===$c->get_meta_data()[0]);eq858('908-singleton-noop-clean',array(),$original->get_changes());$c->save_meta_data();eq858('908-singleton-noop-no-write',array(0,0,0),array($store->adds,$store->updates,$store->deletes));
// First insert imports the real authoritative positive ID, never a fresh ID0.
$store=new MetaStore891();$c=order891($store,array());$p=order891($store,array());$p->update_meta_data($key891,'inserted');$p->save_meta_data();$id=revisions891($store)[0]['id'];$reflect->invoke($m,$c,$p);eq858('908-first-insert-authoritative-ID',$id,revision_meta891($c)[0]->id);$c->save_meta_data();$c->save_meta_data();eq858('908-first-insert-two-saves-singleton',array(record858($id,$key891,'inserted')),revisions891($store));eq858('908-first-insert-only-one-add',1,$store->adds);
// Unrelated dirty metadata, a deletion tombstone and a private test queue survive.
$u1=record858(30,'unrelated',array('x'=>1,0=>false));$u2=record858(31,'unrelated',array(0=>false,'x'=>'1'));$del=record858(32,'pending-delete','retain-null-delete');$store=new MetaStore891();$store->rows=array(30=>$u1,31=>$u2,32=>$del,41=>$one,42=>$two);$c=order891($store,array($u1,$two,$u2,$one,$del));$p=order891($store,array($u1,$u2,$del,$one,$two));$dirty=$c->get_meta_data()[0];$dirty->value=array('x'=>'dirty',0=>false);$changes=$dirty->get_changes();$c->delete_meta_data('pending-delete');$queue=$c->pending891();$c->set_unrelated('caller-owned-pending');$p->update_meta_data($key891,'changed-with-unrelated');$p->save_meta_data();$reflect->invoke($m,$c,$p);
eq858('908-unrelated-dirty-object',true,$dirty===$c->get_meta_data()[0]);eq858('908-unrelated-pending-values',$changes,$dirty->get_changes());eq858('908-unrelated-duplicate-nested-order-type',$u2,$c->get_meta_data()[1]->get_data());eq858('908-unrelated-item-delete-queue',$queue,$c->pending891());eq858('908-unrelated-order-pending','caller-owned-pending',$c->get_changes()['unrelated']);eq858('908-no-caller-order-save',0,$c->save_calls);$c->save_meta_data();eq858('908-unrelated-dirty-value-persists',array('x'=>'dirty',0=>false),$store->rows[30]['value']);eq858('908-unrelated-deletion-persists',false,isset($store->rows[32]));eq858('908-unrelated-duplicate-persists',$u2,$store->rows[31]);eq858('908-authoritative-revision-singleton',array(record858(41,$key891,'changed-with-unrelated')),revisions891($store));
}
$failed=array_values(array_filter($checks,static function($v){return!$v['pass'];}));echo json_encode(array('task'=>908,'tier'=>$tier,'variant'=>$variant,'assertions'=>count($checks),'failed'=>count($failed),'checks'=>$checks,'native'=>0,'actual_Woo_classes'=>array('WC_Data','WC_Meta_Data','WC_DateTime'),'scope'=>'Exact Woo metadata public update/delete/read/save with inert storage adapter; real production reflection, not whole WC_Order/DB/native.')).PHP_EOL;exit(count($failed)?1:0);

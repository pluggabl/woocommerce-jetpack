<?php
class WC_Order_Date843 extends WC_Data {
 protected $data = array('date_modified'=>null,'payment_method'=>'old','payment_method_title'=>'Old','total'=>'10','cart_tax'=>'0','shipping_total'=>'0','shipping_tax'=>'0','discount_total'=>'0','discount_tax'=>'0','unrelated'=>'keep');
 public $save_calls=0;
 public function get_items($type='line_item'){return array();}
 public function add_item($item){throw new RuntimeException('UNEXPECTED_ITEM');}
 public function remove_item($id){throw new RuntimeException('UNEXPECTED_ITEM');}
 public function save(){++$this->save_calls;throw new RuntimeException('CALLER_MUST_NOT_SAVE');}
 public function __call($name,$args){$mode=substr($name,0,4);$field=substr($name,4);if(!in_array($field,array('payment_method','payment_method_title','total','cart_tax','shipping_total','shipping_tax','discount_total','discount_tax'),true))throw new RuntimeException('UNEXPECTED_PROP');if($mode==='get_')return$this->get_prop($field,$args[0]??'view');if($mode==='set_'){$this->set_prop($field,$args[0]);return;}throw new RuntimeException('UNEXPECTED_METHOD');}
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}
}

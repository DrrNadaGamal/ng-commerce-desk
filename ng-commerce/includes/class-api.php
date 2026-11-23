<?php
if(!defined('ABSPATH'))exit;
final class NGCD_API {
 public static function allowed(){return current_user_can('manage_woocommerce')&&NGCD_Demo::enabled();}
 public static function fail($code,$message,$status=400){return new WP_Error($code,$message,array('status'=>$status));}
 public static function register(){
  foreach(array('/snapshot'=>array('GET','snapshot'),'/settings'=>array('POST','settings'),'/products/(?P<id>\d+)'=>array('PATCH','stock'),'/orders/(?P<id>\d+)'=>array('PATCH','order'),'/export'=>array('GET','export')) as $route=>$action)register_rest_route('ng-commerce/v1',$route,array('methods'=>$action[0],'callback'=>array(__CLASS__,$action[1]),'permission_callback'=>array(__CLASS__,'allowed')));
 }
 public static function snapshot(){
  $cached=get_transient('ngcd_snapshot');if($cached)return rest_ensure_response($cached);
  $products=array();foreach(wc_get_products(array('limit'=>100,'status'=>array('publish','private','draft'),'meta_key'=>NGCD_Demo::FLAG,'meta_value'=>'yes')) as $p)$products[]=array('id'=>$p->get_id(),'name'=>$p->get_name(),'sku'=>$p->get_sku(),'price'=>(float)$p->get_price(),'stock'=>(int)$p->get_stock_quantity(),'category'=>$p->get_meta('_ngcd_category'),'art'=>$p->get_meta('_ngcd_art'),'version'=>$p->get_date_modified()?$p->get_date_modified()->getTimestamp():0);
  $orders=array();foreach(wc_get_orders(array('limit'=>200,'meta_key'=>NGCD_Demo::FLAG,'meta_value'=>'yes','orderby'=>'date','order'=>'DESC')) as $o){
   $items=array();foreach($o->get_items() as $item)$items[]=array('product_id'=>$item->get_product_id(),'name'=>$item->get_name(),'quantity'=>$item->get_quantity(),'total'=>(float)$item->get_total());
   $notes=array();foreach(wc_get_order_notes(array('order_id'=>$o->get_id(),'limit'=>8,'type'=>'internal')) as $n)$notes[]=array('text'=>wp_strip_all_tags($n->content),'at'=>$n->date_created->date('Y-m-d H:i'));
   $orders[]=array('id'=>$o->get_id(),'customer'=>trim($o->get_billing_first_name().' '.$o->get_billing_last_name()),'date'=>$o->get_date_created()->date('Y-m-d'),'time'=>$o->get_date_created()->date('H:i'),'status'=>$o->get_status(),'total'=>(float)$o->get_total(),'currency'=>$o->get_currency(),'items'=>$items,'notes'=>$notes,'version'=>$o->get_meta('_ngcd_version')?:'0');
  }
  $out=array('products'=>$products,'orders'=>$orders,'log'=>get_option('ngcd_log',array()),'settings'=>array('threshold'=>(int)get_option('ngcd_threshold',5),'storeName'=>get_option('ngcd_store_name','Forma Demo Store')),'today'=>current_datetime()->format('Y-m-d'),'currency'=>'USD','timezone'=>wp_timezone_string(),'demo'=>true,'limit'=>array('products'=>100,'orders'=>200));set_transient('ngcd_snapshot',$out,30);return rest_ensure_response($out);
 }
 private static function lock($type,$id){return add_option('ngcd_lock_'.$type.'_'.$id,time(),'','no');}
 private static function unlock($type,$id){delete_option('ngcd_lock_'.$type.'_'.$id);}
 public static function stock($r){
  $id=absint($r['id']);$p=wc_get_product($id);if(!$p||$p->get_meta(NGCD_Demo::FLAG)!=='yes')return self::fail('missing','Synthetic product not found.',404);
  if(!$r->has_param('expected'))return self::fail('expected','Expected stock value required.');
  $qty=$r->get_param('stock');if(!is_numeric($qty)||(string)(int)$qty!==(string)$qty||(int)$qty<0||(int)$qty>9999)return self::fail('stock','Stock must be a whole number from 0 to 9999.');
  if(!self::lock('product',$id))return self::fail('locked','Product is being changed. Try again.',409);
  $p=wc_get_product($id);if((int)$r->get_param('expected')!==(int)$p->get_stock_quantity()){self::unlock('product',$id);return self::fail('conflict','Stock changed. Refresh before saving.',409);}
  try{$p->set_stock_quantity((int)$qty);$p->save();NGCD_Demo::log('Demo stock updated for product #'.$id.' to '.(int)$qty.'.');}finally{self::unlock('product',$id);}return array('id'=>$id,'stock'=>(int)$qty);
 }
 public static function order($r){
  $id=absint($r['id']);$o=wc_get_order($id);if(!$o||$o->get_meta(NGCD_Demo::FLAG)!=='yes')return self::fail('missing','Synthetic order not found.',404);
  if(!$r->has_param('version'))return self::fail('version','Expected order version required.');
  if(!self::lock('order',$id))return self::fail('locked','Order is being changed. Try again.',409);
  try{
   $o=wc_get_order($id);if((string)$r->get_param('version')!==(string)($o->get_meta('_ngcd_version')?:'0'))return self::fail('conflict','Order changed. Refresh before saving.',409);
   $next=sanitize_key($r->get_param('status')?:$o->get_status());$current=$o->get_status();
   $transitions=array('pending'=>array('processing','cancelled'),'on-hold'=>array('processing','cancelled'),'processing'=>array('completed','cancelled'),'completed'=>array(),'cancelled'=>array(),'failed'=>array('cancelled'),'refunded'=>array());
   if($next!==$current&&!in_array($next,$transitions[$current]??array(),true))return self::fail('transition','That status transition is not supported in this demo.',409);
   $note=sanitize_textarea_field($r->get_param('note')?:'');if(strlen($note)>1000)return self::fail('note','Notes must be under 1000 bytes.');
   if($next!==$current)$o->set_status($next);
   $o->update_meta_data('_ngcd_version',(string)((int)($o->get_meta('_ngcd_version')?:0)+1));$o->save();if($note!=='')$o->add_order_note($note,false,false);
   NGCD_Demo::log('Demo order #'.$id.' updated. No email or payment action.');return array('id'=>$id,'status'=>$next);
  }finally{self::unlock('order',$id);}
 }
 public static function settings($r){$n=sanitize_text_field($r->get_param('storeName')?:'');$threshold=$r->get_param('threshold');if(strlen($n)<2||strlen($n)>80||!is_numeric($threshold)||(string)(int)$threshold!==(string)$threshold||(int)$threshold<0||(int)$threshold>100)return self::fail('settings','Use a store name (2-80 bytes) and a whole-number threshold (0-100).');update_option('ngcd_store_name',$n,false);update_option('ngcd_threshold',(int)$threshold,false);NGCD_Demo::log('Demo display settings updated.');return array('saved'=>true);}
 public static function export($r){
  $kind=sanitize_key($r->get_param('kind'));if(!in_array($kind,array('orders','inventory'),true))return self::fail('kind','Choose orders or inventory.');
  $response=self::snapshot();$d=$response->get_data();$rows=$kind==='orders'?array_map(function($o){return array($o['id'],$o['customer'],$o['date'],$o['status'],$o['total'],$o['currency']);},$d['orders']):array_map(function($p){return array($p['id'],$p['name'],$p['sku'],$p['stock'],$p['price'],'USD');},$d['products']);
  $header=$kind==='orders'?array('Demo order ID','Fictional customer','Date','Status','Order total','Currency'):array('Demo product ID','Product','SKU','Stock','Unit price','Currency');$f=fopen('php://temp','r+');fputcsv($f,$header);foreach($rows as $row){$row=array_map(function($v){$s=(string)$v;return preg_match('/^[=+\-@\t\r]/',$s)?"'".$s:$s;},$row);fputcsv($f,$row);}rewind($f);$csv=stream_get_contents($f);fclose($f);return array('filename'=>'ng-commerce-demo-'.$kind.'.csv','csv'=>$csv,'scope'=>'All plugin-tagged synthetic '.$kind.', not filtered by the UI date range.');
 }
}

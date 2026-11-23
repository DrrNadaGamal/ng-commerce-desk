<?php
if (!defined('ABSPATH')) exit;
final class NGCD_Demo {
 const FLAG='_ngcd_synthetic';
 public static function enabled(){return get_option('ngcd_demo')==='yes';}
 public static function setup(){
  if(!class_exists('WooCommerce'))return new WP_Error('dependency','WooCommerce must be active.');
  if(self::enabled())return true;
  $existing=wc_get_orders(array('limit'=>1,'return'=>'ids'));$products=wc_get_products(array('limit'=>1,'return'=>'ids'));
  if($existing||$products)return new WP_Error('not_empty','Demo setup requires an empty isolated test store. Existing products or orders found.');
  if(!add_option('ngcd_seed_lock',time(),'','no'))return new WP_Error('locked','Setup already in progress.');
  // The mode flag is set before writes so a partial failure cannot re-seed over existing data.
  update_option('ngcd_demo','yes',false);
  $catalog=array(
   array('Orbit Desk Lamp','OR-101','Lighting',89,28,'lamp'),array('Fold Lounge Chair','FD-202','Furniture',249,7,'chair'),array('Arc Ceramic Vase','AR-303','Decor',42,3,'vase'),array('Studio Side Table','ST-404','Furniture',129,19,'table'),array('Linen Cushion','LN-505','Textiles',32,0,'cushion'),array('Terra Wall Clock','TR-606','Decor',65,14,'clock'),array('Wave Pendant Light','WV-707','Lighting',145,5,'pendant'),array('Form Bookends','FM-808','Decor',38,36,'bookends')
  );
  $ids=array();
  foreach($catalog as $i=>$item){$p=new WC_Product_Simple();$p->set_name($item[0]);$p->set_sku('NGCD-'.$item[1]);$p->set_regular_price($item[3]);$p->set_manage_stock(true);$p->set_stock_quantity($item[4]);$p->set_status('publish');$p->set_catalog_visibility('hidden');$p->set_description('Synthetic portfolio demonstration product. Not for sale.');$p->update_meta_data(self::FLAG,'yes');$p->update_meta_data('_ngcd_category',$item[2]);$p->update_meta_data('_ngcd_art',$item[5]);$p->save();$ids[]=$p->get_id();}
  $states=array('completed','completed','processing','completed','on-hold','completed','processing','cancelled','pending','completed','processing','completed');
  $names=array('Alex Demo','Nour Sample','Sam Example','Lina Demo','Omar Sample','Dana Example');
  for($i=0;$i<36;$i++){
   $o=wc_create_order(array('created_via'=>'ng-commerce-demo','status'=>'pending'));$o->update_meta_data(self::FLAG,'yes');$o->set_currency('USD');$o->set_billing_first_name(explode(' ',$names[$i%6])[0]);$o->set_billing_last_name(explode(' ',$names[$i%6])[1]);$o->set_billing_email('');
   $o->add_product(wc_get_product($ids[$i%8]),1+($i%3));if($i%3===0)$o->add_product(wc_get_product($ids[($i+2)%8]),1);
   $o->calculate_totals(false);$day=current_datetime()->modify('-'.($i%28).' days')->setTime(9+($i%7),15);
   $o->set_date_created($day->getTimestamp());$o->set_status($states[$i%12]);if($states[$i%12]==='completed'){$o->set_date_paid($day->getTimestamp());$o->set_date_completed($day->getTimestamp()+1800);}$o->save();
  }
  // Synthetic stock values are explicit, independent of demo order side effects.
  foreach($ids as $i=>$id){$p=wc_get_product($id);$p->set_stock_quantity($catalog[$i][4]);$p->save();}
  delete_option('ngcd_seed_lock');update_option('ngcd_currency','USD',false);self::log('Synthetic demo catalog and 36 orders created. No emails or payments.');return true;
 }
 public static function log($message){$log=get_option('ngcd_log',array());array_unshift($log,array('message'=>$message,'at'=>current_time('mysql'),'actor'=>get_current_user_id()));update_option('ngcd_log',array_slice($log,0,80),false);delete_transient('ngcd_snapshot');}
 public static function silence_emails(){
  // Register before setup too, then evaluate the mode at send time.
  add_filter('pre_wp_mail',function($pre){return self::enabled()?false:$pre;},999);
  $ids=array('new_order','cancelled_order','failed_order','customer_on_hold_order','customer_processing_order','customer_completed_order','customer_refunded_order','customer_invoice','customer_note','customer_reset_password','customer_new_account');
  foreach($ids as $id)add_filter('woocommerce_email_enabled_'.$id,function($enabled){return self::enabled()?false:$enabled;},999);
 }
}

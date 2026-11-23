<?php
if(!defined('ABSPATH'))exit;
final class NGCD_Plugin {
 public static function init(){
  add_action('admin_menu',array(__CLASS__,'menu'));add_action('rest_api_init',array('NGCD_API','register'));add_action('template_redirect',array(__CLASS__,'standalone'));
  add_action('admin_post_ngcd_setup',array(__CLASS__,'setup'));
  NGCD_Demo::silence_emails();
  add_filter('woocommerce_is_purchasable',function($allowed,$product){return $product->get_meta(NGCD_Demo::FLAG)==='yes'?false:$allowed;},999,2);
  foreach(array('woocommerce_can_reduce_order_stock','woocommerce_can_restore_order_stock') as $hook)add_filter($hook,function($allowed,$order=null){return $order instanceof WC_Order&&$order->get_meta(NGCD_Demo::FLAG)==='yes'?false:$allowed;},999,2);
  // This demo never submits orders through checkout or invokes payment gateways.
 }
 public static function menu(){add_menu_page(__('Commerce Desk','ng-commerce'),__('Commerce Desk','ng-commerce'),'manage_woocommerce','ng-commerce',array(__CLASS__,'admin'),'dashicons-chart-area',28);}
 public static function setup(){if(!current_user_can('manage_woocommerce'))wp_die('Access denied.','',array('response'=>403));check_admin_referer('ngcd_setup');if(empty($_POST['synthetic']))wp_die('Confirm the isolated synthetic demo setup.');$result=NGCD_Demo::setup();if(is_wp_error($result))wp_die(esc_html($result->get_error_message()));wp_safe_redirect(admin_url('admin.php?page=ng-commerce'));exit;}
 public static function admin(){
  if(!class_exists('WooCommerce')){echo '<div class="notice notice-warning"><p>Commerce Desk requires WooCommerce. Activate it on an isolated test site.</p></div>';return;}
  if(!NGCD_Demo::enabled()){
   echo '<div class="wrap"><h1>NG Commerce Desk / Synthetic demo</h1><p>Use an empty LocalWP or isolated staging store only. Setup creates eight fictional products and 36 fictional orders. No real payments or notifications. WooCommerce emails are disabled site-wide while this demo mode is active. Do not use a live store.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('ngcd_setup');echo '<input type="hidden" name="action" value="ngcd_setup"><label><input type="checkbox" name="synthetic" required> I confirm this is an empty isolated test store, not a real shop.</label><p><button class="button button-primary">Create synthetic demo workspace</button></p></form></div>';return;
  }
  echo '<p><a class="button button-primary" href="'.esc_url(home_url('/?ng_commerce=1')).'">Open full-screen Commerce Desk</a></p>';echo self::app();
 }
 public static function app(){
  wp_enqueue_style('ngcd',plugins_url('assets/app.css',NGCD_FILE),array(),'1.0.0');wp_enqueue_script('ngcd',plugins_url('assets/app.js',NGCD_FILE),array(),'1.0.0',true);
  wp_localize_script('ngcd','NGCD',array('api'=>esc_url_raw(rest_url('ng-commerce/v1/')),'nonce'=>wp_create_nonce('wp_rest'),'localeBase'=>plugins_url('assets/locales/',NGCD_FILE),'adminUrl'=>admin_url('admin.php?page=ng-commerce')));
  return '<div id="ngcd" class="cd"><div class="boot">Loading Commerce Desk / جارٍ تحميل لوحة المتجر…</div><noscript>This dashboard requires JavaScript. No changes have been made.</noscript></div>';
 }
 public static function standalone(){if(!isset($_GET['ng_commerce']))return;if(!is_user_logged_in()){auth_redirect();exit;}if(!NGCD_API::allowed()||!class_exists('WooCommerce'))wp_die('Commerce demo unavailable or access denied.','',array('response'=>403));show_admin_bar(false);remove_action('wp_head','_admin_bar_bump_cb');nocache_headers();header('X-Robots-Tag: noindex, nofollow');header('Referrer-Policy: same-origin');$app=self::app();?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>NG Commerce Desk | Synthetic demo</title><?php wp_head(); ?></head><body style="margin:0"><?php echo $app;wp_footer();?></body></html><?php exit;}
}

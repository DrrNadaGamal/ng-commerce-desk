<?php
/**
 * Plugin Name: NG Commerce Desk
 * Description: WooCommerce operations dashboard for an isolated synthetic-data demo store.
 * Version: 1.0.0
 * Author: Nada Gamal (direction), AI-assisted implementation
 * License: GPL-2.0-or-later
 * Requires PHP: 7.4
 * Requires at least: 6.4
 * Requires Plugins: woocommerce
 * Text Domain: ng-commerce
 */
if (!defined('ABSPATH')) { exit; }
define('NGCD_FILE',__FILE__);
foreach(array('demo','api','plugin') as $part) require_once __DIR__.'/includes/class-'.$part.'.php';
add_action('plugins_loaded',array('NGCD_Plugin','init'));

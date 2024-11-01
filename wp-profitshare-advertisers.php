<?php
/**
 * Plugin Name: WP Profitshare Advertisers
 * Plugin URI: https://www.profitshare.ro
 * Description: Profitshare module for wordpress woocommerce
 * Version: 1.3.6
 * Author: Conversion
 * Author URI: https://www.conversion.ro
 * License: GPL2
 */

/**
 * Return instance of Profitshare Plugin.
 *
 * @return PWA_Plugin
 */

define('PWA_VERSION', '1.3.7');

function pwa_get_plugin() {
    static $plugin;

    if (!isset($plugin)) {
        require_once('includes/controllers/class-PWA-plugin.php');
        $plugin = new PWA_Plugin(__FILE__, PWA_VERSION);
    }

    return $plugin;
}

$plugin = pwa_get_plugin();
$plugin->start();
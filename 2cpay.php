<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
Plugin Name: 2CPay
Plugin URI: https://www.zonalhost.com
Description: 2Checkout Payment Gateway Plugin for Woocommerce.
Version: 1.3
Author: ZonalHost
Author URI: https://www.zonalhost.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
// Include Gateway Class and Register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'zh_2cpay_init', 0 );
function zh_2cpay_init() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
    include_once( 'includes/gateway.php' );
    add_filter( 'woocommerce_payment_gateways', 'zh_2cpay_payment_gateway' );
    function zh_2cpay_payment_gateway( $methods ) {
        $methods[] = 'ZH_2CPay';
        return $methods;
    }
}

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'zh_2cpay_action_links' );

function zh_2cpay_action_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=zh_2cpay' ) . '">' . __( 'Settings', 'zh-2cpay' ) . '</a>',
    );
    return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_row_meta', 'zh_2cpay_row_meta', 10, 2 );

function zh_2cpay_row_meta( $links, $file ) {
    if ( strpos( $file, '2cpay.php' ) !== false ) {
	    $new_links = array(
				    '<a href="https://imojo.in/2cpay" target="_blank">Donate</a>'
			    );
	    
	    $links = array_merge( $links, $new_links );
    }
    return $links;
}

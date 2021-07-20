<?php
/**
 * WooCommerce Yoast SEO plugin.
 *
 * @package WPSEO/WooCommerce
 *
 * @wordpress-plugin
 * Plugin Name: Yoast SEO: WooCommerce
 * Version:     14.2.1
 * Plugin URI:  https://yoast.com/wordpress/plugins/yoast-woocommerce-seo/
 * Description: This extension to WooCommerce and Yoast SEO makes sure there's perfect communication between the two plugins.
 * Author:      Team Yoast
 * Author URI:  https://yoast.com
 * Depends:     Yoast SEO, WooCommerce
 * Text Domain: yoast-woo-seo
 * Domain Path: /languages/
 *
 * WC requires at least: 3.0
 * WC tested up to: 5.5
 *
 * Copyright 2014-2021 Yoast BV (email: support@yoast.com)
 */

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

define( 'WPSEO_WOO_PLUGIN_FILE', __FILE__ );
define( 'WPSEO_WOO_VERSION', '14.2.1' );

/**
 * Initializes the plugin class, to make sure all the required functionality is loaded, do this after plugins_loaded.
 *
 * @since 1.0
 *
 * @return void
 */
function initialize_yoast_woocommerce_seo() {

	load_plugin_textdomain( 'yoast-woo-seo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	global $wp_version;

	$dependency_check = new Yoast_WooCommerce_Dependencies();
	if ( $dependency_check->check_dependencies( $wp_version ) ) {
		global $yoast_woo_seo;

		// Initializes the plugin.
		$yoast_woo_seo = new Yoast_WooCommerce_SEO();
	}
}

if ( ! wp_installing() ) {
	add_action( 'plugins_loaded', 'initialize_yoast_woocommerce_seo', 20 );
}

// Activation hook.
if ( is_admin() ) {
	register_activation_hook( __FILE__, [ 'Yoast_WooCommerce_SEO', 'install' ] );
}

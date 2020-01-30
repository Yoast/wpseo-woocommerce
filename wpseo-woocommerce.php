<?php
/**
 * WooCommerce Yoast SEO plugin.
 *
 * @package Yoast\WP\Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name: Yoast SEO: WooCommerce
 * Version:     12.5-RC4
 * Plugin URI:  https://yoast.com/wordpress/plugins/yoast-woocommerce-seo/
 * Description: This extension to WooCommerce and Yoast SEO makes sure there's perfect communication between the two plugins.
 * Author:      Team Yoast
 * Author URI:  https://yoast.com
 * Depends:     Yoast SEO, WooCommerce
 * Text Domain: yoast-woo-seo
 * Domain Path: /languages/
 *
 * WC requires at least: 3.0
 * WC tested up to: 3.9
 *
 * Copyright 2014-2020 Yoast BV (email: support@yoast.com)
 */

namespace Yoast\WP\Woocommerce;

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

define( 'WPSEO_WOO_PLUGIN_FILE', __FILE__ );
define( 'WPSEO_WOO_VERSION', '12.5-RC4' );

/**
 * Initializes the plugin class, to make sure all the required functionality is loaded, do this after plugins_loaded.
 *
 * @since 1.0
 *
 * @return void
 */
function initialize() {

	load_plugin_textdomain( 'yoast-woo-seo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	global $wp_version;

	$dependency_check = new Classes\Dependencies();
	if ( $dependency_check->check_dependencies( $wp_version ) ) {
		global $yoast_woo_seo;

		// Initializes the plugin.
		$yoast_woo_seo = new Classes\Main();
	}
}

if ( ! wp_installing() ) {
	add_action( 'plugins_loaded', 'Yoast\WP\Woocommerce\initialize', 20 );
}

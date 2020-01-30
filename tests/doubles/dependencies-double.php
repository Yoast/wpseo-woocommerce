<?php

namespace Yoast\WP\Woocommerce\Tests\Doubles;

use Yoast\WP\Woocommerce\Classes\Dependencies;

/**
 * Test Helper Class.
 */
class Dependencies_Double extends Dependencies {

	/**
	 * Checks the plugin's dependencies.
	 *
	 * @param string $wp_version WordPress Version.
	 *
	 * @return bool
	 */
	public function check_dependencies( $wp_version ) {
		return parent::check_dependencies( $wp_version );
	}

	/**
	 * Gets the WordPress SEO version.
	 *
	 * @return bool|string
	 */
	public function get_yoast_seo_version() {
		return parent::get_yoast_seo_version();
	}

	/**
	 * Check if WooCommerce is installed, active and the right version.
	 *
	 * @return bool
	 */
	public function check_woocommerce_exists() {
		return parent::check_woocommerce_exists();
	}
}

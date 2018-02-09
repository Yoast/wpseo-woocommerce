<?php
/**
 * @package WPSEO\Tests
 */

/**
 * Class Yoast_WooCommerce_SEO_Double
 */
class Yoast_WooCommerce_SEO_Double extends Yoast_WooCommerce_SEO {

	/**
	 * @inheritdoc
	 */
	public function check_dependencies( $wp_version ) {
		return parent::check_dependencies( $wp_version );
	}
}

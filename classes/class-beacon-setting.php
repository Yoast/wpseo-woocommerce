<?php
/**
 * @package WPSEO/WooCommerce
 */

/**
 * Implements the helpscout beacon suggestions for wpseo WooCommerce
 */
class WPSEO_WooCommerce_Beacon_Setting implements Yoast_HelpScout_Beacon_Setting {

	/**
	 * Returns a list of helpscout hashes to show the user for a certain page.
	 *
	 * @param string $page The current admin page we are on.
	 *
	 * @return array A list of suggestions for the beacon
	 */
	public function get_suggestions( $page ) {
		switch ( $page ) {
			case 'wpseo_woo':
				return array(
					'538321ebe4b087d95176732c',
					// See: http://kb.yoast.com/article/113-configuration-guide-for-yoast-woocommerce-seo.
					'53872ddae4b06542b1a214d4',
					// See: http://kb.yoast.com/article/122-rich-snippets-in-search-results.
					'5375e110e4b0d833740d5700',
					// See: http://kb.yoast.com/article/16-my-sitemap-is-giving-a-404-error-what-should-i-do.
				);
		}

		return array();
	}

	/**
	 * Returns a product for a a certain admin page.
	 *
	 * @param string $page The current admin page we are on.
	 *
	 * @return Yoast_Product[] A product to use for sending data to helpscout
	 */
	public function get_products( $page ) {
		switch ( $page ) {
			case 'wpseo_woo':
				return array( new Yoast_Product_WPSEO_WooCommerce() );
		}

		return array();
	}

	/**
	 * Returns a list of config values for a a certain admin page.
	 *
	 * @param string $page The current admin page we are on.
	 *
	 * @return array A list with configuration for the beacon
	 */
	public function get_config( $page ) {
		return array();
	}
}

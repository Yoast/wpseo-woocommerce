<?php
/**
 * WooCommerce Yoast SEO plugin file.
 *
 * @package WPSEO/WooCommerce
 */

/**
 * Represents the product's price amount.
 */
class WPSEO_WooCommerce_Product_Price_Amount_Presenter extends WPSEO_WooCommerce_Abstract_Product_Presenter {

	/**
	 * The tag key name.
	 *
	 * @var string
	 */
	protected $key = 'product:price:amount';

	/**
	 * Gets the raw value of a presentation.
	 *
	 * @return string The raw value.
	 */
	public function get() {
		$product_type = WPSEO_WooCommerce_Utils::get_product_type( $this->product );

		// Omit the price amount for variable and grouped products.
		if ( $product_type === 'variable' || $product_type === 'grouped' ) {
			return '';
		}

		return (string) WPSEO_WooCommerce_Utils::get_product_display_price( $this->product );
	}
}

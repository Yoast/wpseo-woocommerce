<?php

namespace Yoast\WP\Woocommerce\Classes;

use WPSEO_Options;
use WPSEO_Primary_Term;
use WC_Product;

/**
 * Class Utils
 */
class Utils {

	/**
	 * Searches for the primary terms for given taxonomies and returns the first found primary term.
	 *
	 * @param array       $brand_taxonomies The taxonomies to find the primary term for.
	 * @param \WC_Product $product          The WooCommerce Product.
	 *
	 * @return string The term's name (if found). Otherwise an empty string.
	 */
	public static function search_primary_term( array $brand_taxonomies, $product ) {
		foreach ( $brand_taxonomies as $taxonomy ) {
			$primary_term       = new WPSEO_Primary_Term( $taxonomy, $product->get_id() );
			$found_primary_term = $primary_term->get_primary_term();

			if ( $found_primary_term ) {
				$term = get_term_by( 'id', $found_primary_term, $taxonomy );

				return $term->name;
			}
		}

		return '';
	}

	/**
	 * Get the product display price, using the correct decimals, and tax setting.
	 *
	 * @param WC_Product $product The product we're retrieving the price for.
	 *
	 * @return string Price ready for display.
	 */
	public static function get_product_display_price( WC_Product $product ) {
		$decimals      = wc_get_price_decimals();
		$display_price = $product->get_price();
		$quantity      = $product->get_min_purchase_quantity();

		if ( self::prices_with_tax() ) {
			$display_price = wc_get_price_including_tax(
				$product,
				[
					'qty'   => $quantity,
					'price' => $display_price,
				]
			);
		}

		return wc_format_decimal( $display_price, $decimals );
	}

	/**
	 * Determines if prices should be returned with or without tax included.
	 *
	 * @return bool True if prices should be displayed with tax added, false if not.
	 */
	public static function prices_with_tax() {
		return (
			wc_tax_enabled() &&
			! wc_prices_include_tax() &&
			get_option( 'woocommerce_tax_display_shop' ) === 'incl' &&
			WPSEO_Options::get( 'woo_schema_og_prices_with_tax' )
		);
	}
}

<?php

namespace Yoast\WP\Woocommerce\Classes;

use WPSEO_OpenGraph_Image;
use WPSEO_Options;
use WC_Product;

/**
 * Class OpenGraph
 */
class OpenGraph {

	/**
	 * OpenGraph constructor.
	 */
	public function __construct() {
		add_filter( 'language_attributes', [ $this, 'product_namespace' ], 11 );
		add_filter( 'wpseo_opengraph_type', [ $this, 'return_type_product' ] );
		add_filter( 'wpseo_opengraph_desc', [ $this, 'product_taxonomy_desc_enhancement' ] );

		add_action( 'wpseo_opengraph', [ $this, 'product_enhancement' ], 50 );
		add_action( 'wpseo_add_opengraph_additional_images', [ $this, 'set_opengraph_image' ] );

		add_action( 'Yoast\WP\Woocommerce\opengraph', [ $this, 'brand' ], 10 );
		add_action( 'Yoast\WP\Woocommerce\opengraph', [ $this, 'price' ], 20 );
		add_action( 'Yoast\WP\Woocommerce\opengraph', [ $this, 'in_stock' ], 30 );
		add_action( 'Yoast\WP\Woocommerce\opengraph', [ $this, 'retailer_item_id' ], 40 );
		add_action( 'Yoast\WP\Woocommerce\opengraph', [ $this, 'product_condition' ], 50 );
	}

	/**
	 * Return 'product' when current page is, well... a product.
	 *
	 * @param string $type Passed on without changing if not a product.
	 *
	 * @return string
	 */
	public function return_type_product( $type ) {
		if ( is_singular( 'product' ) ) {
			return 'product';
		}

		return $type;
	}

	/**
	 * Make sure the OpenGraph description is put out.
	 *
	 * @param string $desc The current description, will be overwritten if we're on a product page.
	 *
	 * @return string
	 */
	public function product_taxonomy_desc_enhancement( $desc ) {
		if ( is_product_taxonomy() ) {
			$term_desc = term_description();

			if ( ! empty( $term_desc ) ) {
				$desc = wp_strip_all_tags( $term_desc, true );
				$desc = strip_shortcodes( $desc );
			}
		}

		return $desc;
	}

	/**
	 * Adds the other product images to the OpenGraph output.
	 *
	 * @return bool False if we didn't output, true if we did.
	 */
	public function product_enhancement() {
		$product = wc_get_product( get_queried_object_id() );
		if ( ! is_object( $product ) ) {
			return false;
		}

		/**
		 * Action: 'Yoast\WP\Woocommerce\opengraph' - Allow developers to add to our OpenGraph tags.
		 *
		 * @since 12.6.0
		 *
		 * @api   WC_Product $product The WooCommerce product we're outputting for.
		 */
		do_action( 'Yoast\WP\Woocommerce\opengraph', $product );

		return true;
	}

	/**
	 * Adds the OpenGraph images.
	 *
	 * @param WPSEO_OpenGraph_Image $opengraph_image The OpenGraph image to use.
	 *
	 * @return bool True when images are added, false when they're not.
	 */
	public function set_opengraph_image( WPSEO_OpenGraph_Image $opengraph_image ) {

		if ( is_product_category() ) {
			return $this->set_opengraph_image_product_category( $opengraph_image );
		}

		$product = wc_get_product( get_queried_object_id() );
		if ( ! is_object( $product ) ) {
			return false;
		}

		return $this->set_opengraph_image_product( $opengraph_image, $product );
	}

	/**
	 * Filter for the namespace, adding the OpenGraph namespace.
	 *
	 * @link https://developers.facebook.com/docs/reference/opengraph/object-type/product/
	 *
	 * @param string $input The input namespace string.
	 *
	 * @return string
	 */
	public function product_namespace( $input ) {
		if ( is_singular( 'product' ) ) {
			$input = preg_replace( '/prefix="([^"]+)"/', 'prefix="$1 product: http://ogp.me/ns/product#"', $input );
		}

		return $input;
	}

	/**
	 * Retrieve the primary and if that doesn't exist first term for the brand taxonomy.
	 *
	 * @param string     $schema_brand The taxonomy the site uses for brands.
	 * @param WC_Product $product      The product we're finding the brand for.
	 *
	 * @return bool|string The brand name or false on failure.
	 */
	protected function get_brand_term_name( $schema_brand, $product ) {
		$primary_term = Utils::search_primary_term( [ $schema_brand ], $product );
		if ( ! empty( $primary_term ) ) {
			return $primary_term;
		}
		$terms = get_the_terms( get_the_ID(), $schema_brand );
		if ( is_array( $terms ) && count( $terms ) > 0 ) {
			$term_values = array_values( $terms );
			$term        = array_shift( $term_values );

			return $term->name;
		}

		return false;
	}

	/**
	 * Add the brand to the OpenGraph output.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 */
	public function brand( WC_Product $product ) {
		$schema_brand = WPSEO_Options::get( 'woo_schema_brand' );
		if ( $schema_brand !== '' ) {
			$brand = $this->get_brand_term_name( $schema_brand, $product );
			if ( ! empty( $brand ) ) {
				echo '<meta property="product:brand" content="' . esc_attr( $brand ) . '"/>' . "\n";
			}
		}
	}

	/**
	 * Add the price to the OpenGraph output.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 */
	public function price( WC_Product $product ) {
		/**
		 * Filter: wpseo_woocommerce_og_price - Allow developers to prevent the output of the price in the OpenGraph tags.
		 *
		 * @deprecated 12.5.0. Use the {@see 'Yoast\WP\Woocommerce\og_price'} filter instead.
		 *
		 * @api        bool unsigned Defaults to true.
		 */
		$show_price = apply_filters_deprecated(
			'wpseo_woocommerce_og_price',
			[ true ],
			'Yoast WooCommerce 12.5.0',
			'Yoast\WP\Woocommerce\og_price'
		);

		/**
		 * Filter: Yoast\WP\Woocommerce\og_price - Allow developers to prevent the output of the price in the OpenGraph tags.
		 *
		 * @since 12.5.0
		 *
		 * @api   bool unsigned Defaults to true.
		 */
		$show_price = apply_filters( 'Yoast\WP\Woocommerce\og_price', $show_price );

		if ( $show_price === true ) {
			echo '<meta property="product:price:amount" content="' . esc_attr( Utils::get_product_display_price( $product ) ) . '" />' . "\n";
			echo '<meta property="product:price:currency" content="' . esc_attr( get_woocommerce_currency() ) . '" />' . "\n";
		}
	}

	/**
	 * Add the product condition.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 */
	public function product_condition( WC_Product $product ) {
		/**
		 * Filter: Yoast\WP\Woocommerce\product_condition - Allow developers to prevent or change the output of the product condition in the OpenGraph tags.
		 *
		 * @param \WC_Product $product The product we're outputting.
		 *
		 * @api string Defaults to 'new'.
		 */
		$product_condition = apply_filters( 'Yoast\WP\Woocommerce\product_condition', 'new', $product );
		if ( ! empty( $product_condition ) ) {
			echo '<meta property="product:condition" content="' . esc_attr( $product_condition ) . '" />' . "\n";
		}
	}

	/**
	 * Add the Item ID.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 */
	public function retailer_item_id( WC_Product $product ) {
		echo '<meta property="product:retailer_item_id" content="' . esc_attr( $product->get_sku() ) . '" />' . "\n";
	}

	/**
	 * Add our product stock availability.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 */
	public function in_stock( WC_Product $product ) {
		if ( $product->is_in_stock() ) {
			echo '<meta property="product:availability" content="in stock" />' . "\n";
		}
	}

	/**
	 * Set the OpenGraph image for a product category based on the category thumbnail.
	 *
	 * @param WPSEO_OpenGraph_Image $opengraph_image The OpenGraph image class.
	 *
	 * @return bool True on success, false on failure.
	 */
	protected function set_opengraph_image_product_category( WPSEO_OpenGraph_Image $opengraph_image ) {
		$thumbnail_id = get_term_meta( get_queried_object_id(), 'thumbnail_id', true );
		if ( $thumbnail_id ) {
			$opengraph_image->add_image_by_id( $thumbnail_id );

			return true;
		}

		return false;
	}

	/**
	 * Set the OpenGraph images for a product based on its gallery image IDs.
	 *
	 * @param WPSEO_OpenGraph_Image $opengraph_image The OpenGraph image class.
	 * @param WC_Product            $product         The WooCommerce product.
	 *
	 * @return bool True on success, false on failure.
	 */
	protected function set_opengraph_image_product( WPSEO_OpenGraph_Image $opengraph_image, WC_Product $product ) {
		$img_ids = $product->get_gallery_image_ids();

		if ( is_array( $img_ids ) && $img_ids !== [] ) {
			foreach ( $img_ids as $img_id ) {
				$opengraph_image->add_image_by_id( $img_id );
			}

			return true;
		}

		return false;
	}
}

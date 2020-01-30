<?php

namespace Yoast\WP\Woocommerce\Classes;

use WPSEO_Utils;
use WPSEO_Schema_IDs;
use WPSEO_Options;
use WPSEO_Schema_Image;
use WPSEO_Primary_Term;
use WPSEO_Frontend;
use WC_Product;

/**
 * Class WPSEO_WooCommerce_Schema
 */
class Schema {

	/**
	 * The schema data we're going to output.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * WooCommerce version number.
	 *
	 * @var string
	 */
	protected $wc_version;

	/**
	 * WPSEO_WooCommerce_Schema constructor.
	 *
	 * @param string $wc_version The WooCommerce version.
	 */
	public function __construct( $wc_version = WC_VERSION ) {
		$this->wc_version = $wc_version;

		add_filter( 'woocommerce_structured_data_product', [ $this, 'change_product' ], 10, 2 );
		add_filter( 'woocommerce_structured_data_type_for_page', [ $this, 'remove_woo_breadcrumbs' ] );
		add_filter( 'wpseo_schema_webpage', [ $this, 'filter_webpage' ] );
		add_action( 'wp_footer', [ $this, 'output_schema_footer' ] );

		// Only needed for WooCommerce versions before 3.8.1.
		if ( version_compare( $this->wc_version, '3.8.1' ) < 0 ) {
			add_filter( 'woocommerce_structured_data_review', [ $this, 'change_reviewed_entity' ] );
		}
	}

	/**
	 * Should the yoast schema output be used.
	 *
	 * @return bool Whether or not the Yoast SEO schema should be output.
	 */
	public static function should_output_yoast_schema() {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- Using WPSEO hook.
		return apply_filters( 'wpseo_json_ld_output', true );
	}

	/**
	 * Outputs the Woo Schema blob in the footer.
	 *
	 * @return bool False when there's nothing to output, true when we did output something.
	 */
	public function output_schema_footer() {
		if ( empty( $this->data ) || $this->data === [] ) {
			return false;
		}

		WPSEO_Utils::schema_output( [ $this->data ], 'yoast-schema-graph yoast-schema-graph--woo yoast-schema-graph--footer' );

		return true;
	}

	/**
	 * Changes the WebPage output to point to Product as the main entity.
	 *
	 * @param array $data Product Schema data.
	 *
	 * @return array Product Schema data.
	 */
	public function filter_webpage( $data ) {
		if ( is_product() ) {
			$data['@type'] = 'ItemPage';
		}
		if ( is_checkout() || is_checkout_pay_page() ) {
			$data['@type'] = 'CheckoutPage';
		}

		return $data;
	}

	/**
	 * Changes the Review output to point to Product as the reviewed Item.
	 *
	 * @param array $data Review Schema data.
	 *
	 * @return array Review Schema data.
	 */
	public function change_reviewed_entity( $data ) {
		unset( $data['@type'] );
		unset( $data['itemReviewed'] );

		$this->data['review'][] = $data;

		return [];
	}

	/**
	 * Filter Schema Product data to work.
	 *
	 * @param array      $data    Schema Product data.
	 * @param WC_Product $product Product object.
	 *
	 * @return array Schema Product data.
	 */
	public function change_product( $data, $product ) {
		$canonical = $this->get_canonical();

		$data = $this->change_seller_in_offers( $data );
		$data = $this->filter_reviews( $data, $product );
		$data = $this->filter_offers( $data, $product );

		// This product is the main entity of this page, so we set it as such.
		$data['mainEntityOfPage'] = [
			'@id' => $canonical . WPSEO_Schema_IDs::WEBPAGE_HASH,
		];

		// Now let's add this data to our overall output.
		$this->data = $data;

		$this->add_image( $canonical );
		$this->add_brand( $product );
		$this->add_manufacturer( $product );
		$this->add_global_identifier( $product );

		return [];
	}

	/**
	 * Filters the offers array to enrich it.
	 *
	 * @param array      $data    Schema Product data.
	 * @param WC_Product $product The product.
	 *
	 * @return array Schema Product data.
	 */
	protected function filter_offers( $data, $product ) {
		$home_url = trailingslashit( get_site_url() );
		foreach ( $data['offers'] as $key => $offer ) {
			// Remove this value as it makes no sense.
			unset( $data['offers'][ $key ]['priceValidUntil'] );

			// Add an @id to the offer.
			if ( $offer['@type'] === 'Offer' ) {
				$price                           = Utils::get_product_display_price( $product );
				$data['offers'][ $key ]['@id']   = $home_url . '#/schema/offer/' . $product->get_id() . '-' . $key;
				$data['offers'][ $key ]['price'] = $price;
				$data['offers'][ $key ]['priceSpecification']['price']                 = $price;
				$data['offers'][ $key ]['priceSpecification']['priceCurrency']         = get_woocommerce_currency();
				$data['offers'][ $key ]['priceSpecification']['valueAddedTaxIncluded'] = Utils::prices_with_tax();
			}
			if ( $offer['@type'] === 'AggregateOffer' ) {
				$data['offers'][ $key ]['@id']    = $home_url . '#/schema/aggregate-offer/' . $product->get_id() . '-' . $key;
				$data['offers'][ $key ]['offers'] = $this->add_individual_offers( $product );
			}
		}

		return $data;
	}

	/**
	 * Removes the Woo Breadcrumbs from their Schema output.
	 *
	 * @param array $types Types of Schema Woo will render.
	 *
	 * @return array Types of Schema Woo will render.
	 */
	public function remove_woo_breadcrumbs( $types ) {
		foreach ( $types as $key => $type ) {
			if ( $type === 'breadcrumblist' ) {
				unset( $types[ $key ] );
			}
		}

		return $types;
	}

	/**
	 * Retrieve the global identifier type and value if we have one.
	 *
	 * @param WC_Product $product Product object.
	 *
	 * @return bool True on success, false on failure.
	 */
	protected function add_global_identifier( $product ) {
		$product_id               = $product->get_id();
		$global_identifier_values = get_post_meta( $product_id, 'wpseo_global_identifier_values', true );

		if ( ! is_array( $global_identifier_values ) || $global_identifier_values === [] ) {
			return false;
		}

		foreach ( $global_identifier_values as $type => $value ) {
			$this->data[ $type ] = $value;
			if ( $type === 'isbn' && ! empty( $value ) ) {
				$this->data['@type'] = [ 'Book', 'Product' ];
			}
		}

		return true;
	}

	/**
	 * Update the seller attribute to reference the Organization, when it is set.
	 *
	 * @param array $data Schema Product data.
	 *
	 * @return array Schema Product data.
	 */
	protected function change_seller_in_offers( $data ) {
		$company_or_person = WPSEO_Options::get( 'company_or_person', false );
		$company_name      = WPSEO_Options::get( 'company_name' );

		if ( $company_or_person !== 'company' || empty( $company_name ) ) {
			return $data;
		}

		if ( ! empty( $data['offers'] ) ) {
			foreach ( $data['offers'] as $key => $val ) {
				$data['offers'][ $key ]['seller'] = [
					'@id' => trailingslashit( WPSEO_Utils::get_home_url() ) . WPSEO_Schema_IDs::ORGANIZATION_HASH,
				];
			}
		}

		return $data;
	}

	/**
	 * Add brand to our output.
	 *
	 * @param WC_Product $product Product object.
	 */
	private function add_brand( $product ) {
		$schema_brand = WPSEO_Options::get( 'woo_schema_brand' );
		if ( ! empty( $schema_brand ) ) {
			$this->add_organization_for_attribute( 'brand', $product, $schema_brand );
		}
	}

	/**
	 * Add manufacturer to our output.
	 *
	 * @param WC_Product $product Product object.
	 */
	private function add_manufacturer( $product ) {
		$schema_manufacturer = WPSEO_Options::get( 'woo_schema_manufacturer' );
		if ( ! empty( $schema_manufacturer ) ) {
			$this->add_organization_for_attribute( 'manufacturer', $product, $schema_manufacturer );
		}
	}

	/**
	 * Adds an attribute to our Product data array with the value from a taxonomy, as an Organization,
	 *
	 * @param string     $attribute The attribute we're adding to Product.
	 * @param WC_Product $product   The WooCommerce product we're working with.
	 * @param string     $taxonomy  The taxonomy to get the attribute's value from.
	 */
	private function add_organization_for_attribute( $attribute, $product, $taxonomy ) {
		$term = $this->get_primary_term_or_first_term( $taxonomy, $product->get_id() );

		if ( $term !== null ) {
			$this->data[ $attribute ] = [
				'@type' => 'Organization',
				'name'  => $term->name,
			];
		}
	}

	/**
	 * Adds image schema.
	 *
	 * @param string $canonical The product canonical.
	 */
	private function add_image( $canonical ) {
		/**
		 * WooCommerce will set the image to false if none is available. This is incorrect schema and we should fix it
		 * for our users for now.
		 *
		 * See https://github.com/woocommerce/woocommerce/issues/24188.
		 */
		if ( isset( $this->data['image'] ) && $this->data['image'] === false ) {
			unset( $this->data['image'] );
		}

		if ( has_post_thumbnail() ) {
			$this->data['image'] = [
				'@id' => $canonical . WPSEO_Schema_IDs::PRIMARY_IMAGE_HASH,
			];

			return;
		}

		// Fallback to WooCommerce placeholder image.
		if ( function_exists( 'wc_placeholder_img_src' ) ) {
			$image_schema        = new WPSEO_Schema_Image( $canonical . '#woocommerceimageplaceholder' );
			$placeholder_img_src = wc_placeholder_img_src();
			$this->data['image'] = $image_schema->generate_from_url( $placeholder_img_src );
		}
	}

	/**
	 * Tries to get the primary term, then the first term, null if none found.
	 *
	 * @param string $taxonomy_name Taxonomy name for the term.
	 * @param int    $post_id       Post ID for the term.
	 *
	 * @return \WP_Term|null The primary term, the first term or null.
	 */
	protected function get_primary_term_or_first_term( $taxonomy_name, $post_id ) {
		$primary_term    = new WPSEO_Primary_Term( $taxonomy_name, $post_id );
		$primary_term_id = $primary_term->get_primary_term();

		if ( $primary_term_id !== false ) {
			$primary_term = get_term( $primary_term_id );
			if ( $primary_term instanceof \WP_Term ) {
				return $primary_term;
			}
		}

		$terms = get_the_terms( $post_id, $taxonomy_name );

		if ( is_array( $terms ) && count( $terms ) > 0 ) {
			return $terms[0];
		}

		return null;
	}

	/**
	 * Retrieves the canonical URL for the current page.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return string The canonical URL.
	 */
	protected function get_canonical() {
		return WPSEO_Frontend::get_instance()->canonical( false );
	}

	/**
	 * Adds the individual product variants as variants of the offer.
	 *
	 * @param WC_Product $product The WooCommerce product we're working with.
	 *
	 * @return array Schema Offers data.
	 */
	protected function add_individual_offers( $product ) {
		$variations = $product->get_available_variations();

		$site_url           = trailingslashit( get_site_url() );
		$currency           = get_woocommerce_currency();
		$prices_include_tax = Utils::prices_with_tax();
		$decimals           = wc_get_price_decimals();
		$data               = [];
		$product_id         = $product->get_id();
		$product_name       = $product->get_name();

		foreach ( $variations as $key => $variation ) {
			$variation_name = implode( ' / ', $variation['attributes'] );

			$data[] = [
				'@type'              => 'Offer',
				'@id'                => $site_url . '#/schema/offer/' . $product_id . '-' . $key,
				'name'               => $product_name . ' - ' . $variation_name,
				'price'              => wc_format_decimal( $variation['display_price'], $decimals ),
				'priceSpecification' => [
					'price'                 => wc_format_decimal( $variation['display_price'], $decimals ),
					'priceCurrency'         => $currency,
					'valueAddedTaxIncluded' => $prices_include_tax,
				],
			];
		}

		return $data;
	}

	/**
	 * Enhances the review data output by WooCommerce.
	 *
	 * @param array      $data    Review Schema data.
	 * @param WC_Product $product The WooCommerce product we're working with.
	 *
	 * @return array Review Schema data.
	 */
	protected function filter_reviews( $data, $product ) {
		if ( ! isset( $data['review'] ) || $data['review'] === [] ) {
			return $data;
		}

		$site_url = trailingslashit( get_site_url() );

		$product_id   = $product->get_id();
		$product_name = $product->get_name();

		foreach ( $data['review'] as $key => $review ) {
			$data['review'][ $key ]['@id']  = $site_url . '#/schema/review/' . $product_id . '-' . $key;
			$data['review'][ $key ]['name'] = $product_name;
		}

		return $data;
	}
}

<?php

namespace Yoast\WP\Woocommerce\Tests\Classes;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Yoast\WP\Woocommerce\Classes\Schema;
use Yoast\WP\Woocommerce\Tests\Doubles\Schema_Double;
use Yoast\WP\Woocommerce\Tests\TestCase;

/**
 * Class WooCommerce_Schema_Test.
 */
class Schema_Test extends TestCase {

	/**
	 * Test setup.
	 */
	public function setUp() {
		parent::setUp();
		if ( ! \defined( 'WC_VERSION' ) ) {
			\define( 'WC_VERSION', '3.8.1' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
		}
	}

	/**
	 * Tests the class constructor.
	 *
	 * @covers Schema::__construct
	 */
	public function test_construct() {
		$schema = new Schema( '3.9' );

		$this->assertTrue( has_filter( 'woocommerce_structured_data_product', [ $schema, 'change_product' ] ) );
		$this->assertTrue(
			has_filter(
				'woocommerce_structured_data_type_for_page',
				[
					$schema,
					'remove_woo_breadcrumbs',
				]
			)
		);
		$this->assertTrue( has_filter( 'wpseo_schema_webpage', [ $schema, 'filter_webpage' ] ) );
		$this->assertTrue( has_action( 'wp_footer', [ $schema, 'output_schema_footer' ] ) );

		$this->assertFalse( has_filter( 'woocommerce_structured_data_review', [ $schema, 'change_reviewed_entity' ] ) );
	}

	/**
	 * Tests the class constructor.
	 *
	 * @covers Schema::__construct
	 */
	public function test_construct_old_wc() {
		$schema = new Schema( '3.8' );
		$this->assertTrue( has_filter( 'woocommerce_structured_data_review', [ $schema, 'change_reviewed_entity' ] ) );
	}

	/**
	 * Test our Schema output in the footer.
	 *
	 * @covers Schema::output_schema_footer
	 */
	public function test_output_schema_footer() {
		$schema = new Schema_Double();

		$schema->data = [];
		$this->assertFalse( $schema->output_schema_footer() );

		$data = [ 'test' ];

		$utils = Mockery::mock( 'alias:WPSEO_Utils' );
		$utils->expects( 'schema_output' )->once()->andSet( 'output', $data );

		$schema->data = $data;
		$schema->output_schema_footer();
		$this->assertEquals( $data, $utils->output );
	}

	/**
	 * Test our Schema output in the footer.
	 *
	 * @covers Schema::filter_webpage
	 */
	public function test_filter_webpage() {
		Functions\stubs(
			[
				'is_product'           => false,
				'is_checkout'          => false,
				'is_checkout_pay_page' => false,
			]
		);

		$input  = [
			'@type' => 'WebPage',
		];
		$schema = new Schema();
		$this->assertEquals( $input, $schema->filter_webpage( $input ) );

		Functions\stubs(
			[
				'is_product'           => false,
				'is_checkout'          => true,
				'is_checkout_pay_page' => false,
			]
		);

		$expected = [
			'@type' => 'CheckoutPage',
		];
		$schema   = new Schema();
		$this->assertEquals( $expected, $schema->filter_webpage( $input ) );

		Functions\stubs(
			[
				'is_product'           => true,
				'is_checkout'          => false,
				'is_checkout_pay_page' => false,
			]
		);

		$expected = [
			'@type' => 'ItemPage',
		];
		$schema   = new Schema();
		$this->assertEquals( $expected, $schema->filter_webpage( $input ) );
	}

	/**
	 * Change review Schema test.
	 *
	 * @covers Schema::change_reviewed_entity
	 */
	public function test_change_reviewed_entity() {
		$schema = new Schema_Double();

		$input  = [
			'@type'         => 'Review',
			'itemReviewed'  => 'Product',
			'reviewContent' => 'is a dummy review',
		];
		$output = $schema->change_reviewed_entity( $input );

		$expected_output = [];
		$expected_data   = [
			'review' => [
				[
					'reviewContent' => 'is a dummy review',
				],
			],
		];

		$this->assertEquals( $expected_output, $output );
		$this->assertEquals( $expected_data, $schema->data );
	}

	/**
	 * Test filtering offers.
	 *
	 * @covers Schema::filter_offers
	 */
	public function test_filter_offers() {
		$schema = new Schema_Double();
		$input  = [
			'@type'       => 'Product',
			'name'        => 'Customizable responsive toolset',
			'url'         => 'https://example.com/product/customizable-responsive-toolset/',
			'description' => 'Sit debitis reprehenderit non rem natus. Corporis quidem quos et sit similique. Et ad hic exercitationem repudiandae rem laborum.\n\n\n\n\n\n\n\n\nCumque iusto cum enim ut. Et ipsum tempore dolorem ullam aspernatur autem et. Aut molestiae dolor natus. Ducimus molestias perspiciatis magni in libero deleniti ut. Rerum perspiciatis autem et maiores hic ducimus.\n\n\nAut tenetur ducimus distinctio quaerat deserunt sed. Sint ullam ut deserunt deleniti velit et. Incidunt in molestiae voluptas corrupti qui facilis quia.\n\n\n\n\n\nIste asperiores voluptas expedita id cupiditate. Sed error corrupti quibusdam dolor facere enim tenetur. Asperiores error qui commodi dolorem veritatis aspernatur.',
			'image'       => [
				'@id' => 'https://example.com/product/customizable-responsive-toolset/#primaryimage',
			],
			'sku'         => '209643',
			'offers'      => [

				[
					'@type'              => 'Offer',
					'price'              => '49.00',
					'priceValidUntil'    => '2021-12-31',
					'priceSpecification' => [
						'price'                 => '49.00',
						'priceCurrency'         => 'GBP',
						'valueAddedTaxIncluded' => false,
					],
					'priceCurrency'      => 'GBP',
					'availability'       => 'http://schema.org/InStock',
					'url'                => 'https://example.com/product/customizable-responsive-toolset/',
					'seller'             => [
						'@type' => 'Organization',
						'name'  => 'WooCommerce',
						'url'   => 'https://example.com',
					],
				],
			],
		];

		$expected_output                     = $input;
		$expected_output['offers'][0]['@id'] = 'http://example.com/#/schema/offer/209643-0';
		unset( $expected_output['offers'][0]['priceValidUntil'] );

		$base_url = 'http://example.com';
		Functions\stubs(
			[
				'get_site_url'             => $base_url,
				'wc_get_price_decimals'    => 2,
				'wc_tax_enabled'           => false,
				'wc_format_decimal'        => function ( $number ) {
					return number_format( $number, 2 );
				},
				'get_woocommerce_currency' => 'GBP',
			]
		);

		$product = Mockery::mock( 'WC_Product' );
		$product->expects( 'get_id' )->once()->andReturn( '209643' );
		$product->expects( 'get_price' )->once()->andReturn( 49 );
		$product->expects( 'get_min_purchase_quantity' )->once()->andReturn( 1 );

		$output = $schema->filter_offers( $input, $product );

		$this->assertEquals( $expected_output, $output );
	}

	/**
	 * Test filtering offers.
	 *
	 * @covers Schema::filter_offers
	 * @covers Schema::add_individual_offers
	 */
	public function test_filter_aggregate_offers() {
		$schema = new Schema_Double();
		$input  = [
			'@type'       => 'Product',
			'name'        => 'Customizable responsive toolset',
			'url'         => 'https://example.com/product/customizable-responsive-toolset/',
			'description' => 'Sit debitis reprehenderit non rem natus. Corporis quidem quos et sit similique. Et ad hic exercitationem repudiandae rem laborum.\n\n\n\n\n\n\n\n\nCumque iusto cum enim ut. Et ipsum tempore dolorem ullam aspernatur autem et. Aut molestiae dolor natus. Ducimus molestias perspiciatis magni in libero deleniti ut. Rerum perspiciatis autem et maiores hic ducimus.\n\n\nAut tenetur ducimus distinctio quaerat deserunt sed. Sint ullam ut deserunt deleniti velit et. Incidunt in molestiae voluptas corrupti qui facilis quia.\n\n\n\n\n\nIste asperiores voluptas expedita id cupiditate. Sed error corrupti quibusdam dolor facere enim tenetur. Asperiores error qui commodi dolorem veritatis aspernatur.',
			'image'       => [
				'@id' => 'https://example.com/product/customizable-responsive-toolset/#primaryimage',
			],
			'sku'         => 'sku209643',
			'offers'      => [
				[
					'@type'         => 'AggregateOffer',
					'lowPrice'      => '8.00',
					'highPrice'     => '12.00',
					'offerCount'    => 3,
					'priceCurrency' => 'GBP',
					'availability'  => 'http://schema.org/InStock',
					'url'           => 'https://example.com/product/customizable-responsive-toolset/',
					'seller'        => [
						'@type' => 'Organization',
						'name'  => 'WooCommerce',
						'url'   => 'https://example.com',
					],
				],
			],
		];

		$variants        = [
			[
				'attributes'            => [
					'attribute_pa_size' => 'l',
				],
				'availability_html'     => '',
				'backorders_allowed'    => false,
				'dimensions'            => [
					'length' => '',
					'width'  => '',
					'height' => '',
				],
				'dimensions_html'       => 'N/A',
				'display_price'         => 10,
				'display_regular_price' => 10,
				'image'                 => [
					'title'                   => 'jewelry5',
					'caption'                 => '',
					'url'                     => 'https://example.com/wp-content/uploads/2020/01/jewelry5.jpg',
					'alt'                     => '',
					'src'                     => 'https://example.com/wp-content/uploads/2020/01/jewelry5-416x312.jpg',
					'srcset'                  => 'https://example.com/wp-content/uploads/2020/01/jewelry5-416x312.jpg 416w, https://example.com/wp-content/uploads/2020/01/jewelry5-300x225.jpg 300w, https://example.com/wp-content/uploads/2020/01/jewelry5.jpg 640w',
					'sizes'                   => '(max-width: 416px) 100vw, 416px',
					'full_src'                => 'https://example.com/wp-content/uploads/2020/01/jewelry5.jpg',
					'full_src_w'              => 640,
					'full_src_h'              => 480,
					'gallery_thumbnail_src'   => 'https://example.com/wp-content/uploads/2020/01/jewelry5-100x100.jpg',
					'gallery_thumbnail_src_w' => 100,
					'gallery_thumbnail_src_h' => 100,
					'thumb_src'               => 'https://example.com/wp-content/uploads/2020/01/jewelry5-324x324.jpg',
					'thumb_src_w'             => 324,
					'thumb_src_h'             => 324,
					'src_w'                   => 416,
					'src_h'                   => 312,
				],
				'image_id'              => '17',
				'is_downloadable'       => false,
				'is_in_stock'           => true,
				'is_purchasable'        => true,
				'is_sold_individually'  => 'no',
				'is_virtual'            => false,
				'max_qty'               => '',
				'min_qty'               => 1,
				'price_html'            => '<span class=\'price\'><span class=\'woocommerce-Price-amount amount\'><span class=\'woocommerce-Price-currencySymbol\'>&pound;</span>10.00</span></span>',
				'sku'                   => '209643',
				'variation_description' => '',
				'variation_id'          => 330,
				'variation_is_active'   => true,
				'variation_is_visible'  => true,
				'weight'                => '',
				'weight_html'           => 'N/A',
			],
			[
				'attributes'            => [
					'attribute_pa_size' => 'm',
				],
				'availability_html'     => '',
				'backorders_allowed'    => false,
				'dimensions'            => [
					'length' => '',
					'width'  => '',
					'height' => '',
				],
				'dimensions_html'       => 'N/A',
				'display_price'         => 8,
				'display_regular_price' => 8,
				'image'                 => [
					'title'                   => 'jewelry5',
					'caption'                 => '',
					'url'                     => 'https://example.com/wp-content/uploads/2020/01/jewelry5.jpg',
					'alt'                     => '',
					'src'                     => 'https://example.com/wp-content/uploads/2020/01/jewelry5-416x312.jpg',
					'srcset'                  => 'https://example.com/wp-content/uploads/2020/01/jewelry5-416x312.jpg 416w, https://example.com/wp-content/uploads/2020/01/jewelry5-300x225.jpg 300w, https://example.com/wp-content/uploads/2020/01/jewelry5.jpg 640w',
					'sizes'                   => '(max-width: 416px) 100vw, 416px',
					'full_src'                => 'https://example.com/wp-content/uploads/2020/01/jewelry5.jpg',
					'full_src_w'              => 640,
					'full_src_h'              => 480,
					'gallery_thumbnail_src'   => 'https://example.com/wp-content/uploads/2020/01/jewelry5-100x100.jpg',
					'gallery_thumbnail_src_w' => 100,
					'gallery_thumbnail_src_h' => 100,
					'thumb_src'               => 'https://example.com/wp-content/uploads/2020/01/jewelry5-324x324.jpg',
					'thumb_src_w'             => 324,
					'thumb_src_h'             => 324,
					'src_w'                   => 416,
					'src_h'                   => 312,
				],
				'image_id'              => '17',
				'is_downloadable'       => false,
				'is_in_stock'           => true,
				'is_purchasable'        => true,
				'is_sold_individually'  => 'no',
				'is_virtual'            => false,
				'max_qty'               => '',
				'min_qty'               => 1,
				'price_html'            => '<span class=\'price\'><span class=\'woocommerce-Price-amount amount\'><span class=\'woocommerce-Price-currencySymbol\'>&pound;</span>8.00</span></span>',
				'sku'                   => '209643',
				'variation_description' => '',
				'variation_id'          => 331,
				'variation_is_active'   => true,
				'variation_is_visible'  => true,
				'weight'                => '',
				'weight_html'           => 'N/A',
			],
			[
				'attributes'            => [
					'attribute_pa_size' => 'xl',
				],
				'availability_html'     => '',
				'backorders_allowed'    => false,
				'dimensions'            => [
					'length' => '',
					'width'  => '',
					'height' => '',
				],
				'dimensions_html'       => 'N/A',
				'display_price'         => 12,
				'display_regular_price' => 12,
				'image'                 => [
					'title'                   => 'jewelry5',
					'caption'                 => '',
					'url'                     => 'https://example.com/wp-content/uploads/2020/01/jewelry5.jpg',
					'alt'                     => '',
					'src'                     => 'https://example.com/wp-content/uploads/2020/01/jewelry5-416x312.jpg',
					'srcset'                  => 'https://example.com/wp-content/uploads/2020/01/jewelry5-416x312.jpg 416w, https://example.com/wp-content/uploads/2020/01/jewelry5-300x225.jpg 300w, https://example.com/wp-content/uploads/2020/01/jewelry5.jpg 640w',
					'sizes'                   => '(max-width: 416px) 100vw, 416px',
					'full_src'                => 'https://example.com/wp-content/uploads/2020/01/jewelry5.jpg',
					'full_src_w'              => 640,
					'full_src_h'              => 480,
					'gallery_thumbnail_src'   => 'https://example.com/wp-content/uploads/2020/01/jewelry5-100x100.jpg',
					'gallery_thumbnail_src_w' => 100,
					'gallery_thumbnail_src_h' => 100,
					'thumb_src'               => 'https://example.com/wp-content/uploads/2020/01/jewelry5-324x324.jpg',
					'thumb_src_w'             => 324,
					'thumb_src_h'             => 324,
					'src_w'                   => 416,
					'src_h'                   => 312,
				],
				'image_id'              => '17',
				'is_downloadable'       => false,
				'is_in_stock'           => true,
				'is_purchasable'        => true,
				'is_sold_individually'  => 'no',
				'is_virtual'            => false,
				'max_qty'               => '',
				'min_qty'               => 1,
				'price_html'            => '<span class=\'price\'><span class=\'woocommerce-Price-amount amount\'><span class=\'woocommerce-Price-currencySymbol\'>&pound;</span>12.00</span></span>',
				'sku'                   => '209643',
				'variation_description' => '',
				'variation_id'          => 332,
				'variation_is_active'   => true,
				'variation_is_visible'  => true,
				'weight'                => '',
				'weight_html'           => 'N/A',
			],
		];
		$expected_output =
			[
				'@type'         => 'AggregateOffer',
				'lowPrice'      => '8.00',
				'highPrice'     => '12.00',
				'offerCount'    => 3,
				'priceCurrency' => 'GBP',
				'availability'  => 'http://schema.org/InStock',
				'url'           => 'https://example.com/product/customizable-responsive-toolset/',
				'seller'        => [
					'@type' => 'Organization',
					'name'  => 'WooCommerce',
					'url'   => 'https://example.com',
				],
				'@id'           => 'https://example.com/#/schema/aggregate-offer/209643-0',
				'offers'        => [
					[
						'@type'              => 'Offer',
						'@id'                => 'https://example.com/#/schema/offer/209643-0',
						'name'               => 'Customizable responsive toolset - l',
						'price'              => 10,
						'priceSpecification' => [
							'price'                 => '10',
							'priceCurrency'         => 'GBP',
							'valueAddedTaxIncluded' => false,
						],
					],
					[
						'@type'              => 'Offer',
						'@id'                => 'https://example.com/#/schema/offer/209643-1',
						'name'               => 'Customizable responsive toolset - m',
						'price'              => 8,
						'priceSpecification' => [
							'price'                 => '8',
							'priceCurrency'         => 'GBP',
							'valueAddedTaxIncluded' => false,
						],
					],
					[
						'@type'              => 'Offer',
						'@id'                => 'https://example.com/#/schema/offer/209643-2',
						'name'               => 'Customizable responsive toolset - xl',
						'price'              => 12,
						'priceSpecification' => [
							'price'                 => '12',
							'priceCurrency'         => 'GBP',
							'valueAddedTaxIncluded' => false,
						],
					],
				],
			];

		$base_url = 'https://example.com';
		Functions\stubs(
			[
				'get_site_url'             => $base_url,
				'get_woocommerce_currency' => 'GBP',
				'wc_prices_include_tax'    => false,
				'wc_get_price_decimals'    => 2,
				'wc_format_decimal'        => null,
				'wc_tax_enabled'           => false,
			]
		);

		$product = Mockery::mock( 'WC_Product' );
		$product->expects( 'get_id' )->twice()->andReturn( '209643' );
		$product->expects( 'get_available_variations' )->once()->andReturn( $variants );
		$product->expects( 'get_name' )->once()->andReturn( 'Customizable responsive toolset' );
		$output = $schema->filter_offers( $input, $product );

		$this->assertEquals( $expected_output, $output['offers'][0] );
	}

	/**
	 * Test that removing Woo Breadcrumbs works.
	 *
	 * @covers Schema::remove_woo_breadcrumbs
	 */
	public function test_remove_woo_breadcrumbs() {
		$input    = [ 'webpage', 'breadcrumblist' ];
		$expected = [ 'webpage' ];

		$class = new Schema();
		$this->assertEquals( $expected, $class->remove_woo_breadcrumbs( $input ) );
	}

	/**
	 * Changing the seller in offers to point to our Organization ID when there's no org.
	 *
	 * @covers Schema::change_seller_in_offers
	 */
	public function test_change_seller_in_offers_no_organization() {
		$input = [
			'@type'       => 'Product',
			'@id'         => 'https://example.com/product/customizable-responsive-toolset/#product',
			'name'        => 'Customizable responsive toolset',
			'url'         => 'https://example.com/product/customizable-responsive-toolset/',
			'description' => 'Sit debitis reprehenderit non rem natus. Corporis quidem quos et sit similique. Et ad hic exercitationem repudiandae rem laborum.\n\n\n\n\n\n\n\n\nCumque iusto cum enim ut. Et ipsum tempore dolorem ullam aspernatur autem et. Aut molestiae dolor natus. Ducimus molestias perspiciatis magni in libero deleniti ut. Rerum perspiciatis autem et maiores hic ducimus.\n\n\nAut tenetur ducimus distinctio quaerat deserunt sed. Sint ullam ut deserunt deleniti velit et. Incidunt in molestiae voluptas corrupti qui facilis quia.\n\n\n\n\n\nIste asperiores voluptas expedita id cupiditate. Sed error corrupti quibusdam dolor facere enim tenetur. Asperiores error qui commodi dolorem veritatis aspernatur.',
			'image'       => [
				'@id' => 'https://example.com/product/customizable-responsive-toolset/#primaryimage',
			],
			'sku'         => '209643',
			'offers'      => [
				[
					'@type'         => 'AggregateOffer',
					'lowPrice'      => '8.00',
					'highPrice'     => '12.00',
					'offerCount'    => 3,
					'priceCurrency' => 'GBP',
					'availability'  => 'http://schema.org/InStock',
					'url'           => 'https://example.com/product/customizable-responsive-toolset/',
					'seller'        => [
						'@type' => 'Organization',
						'name'  => 'WooCommerce',
						'url'   => 'https://example.com',
					],
					'@id'           => 'https://example.com/#/schema/aggregate-offer/24-0',

				],
			],
		];

		$options = Mockery::mock( 'alias:WPSEO_Options' );
		$options->expects( 'get' )->once()->with( 'company_or_person', false )->andReturn( false );
		$options->expects( 'get' )->once()->with( 'company_name' )->andReturn( 'Yoast BV' );

		Mockery::getConfiguration()->setConstantsMap(
			[
				'WPSEO_Schema_IDs' => [
					'ORGANIZATION_HASH'  => '#organization',
					'WEBPAGE_HASH'       => '#webpage',
					'PRIMARY_IMAGE_HASH' => '#primaryimage',
				],
			]
		);
		Mockery::mock( 'alias:WPSEO_Schema_IDs' );

		$expected = $input;
		$schema   = new Schema_Double();
		$output   = $schema->change_seller_in_offers( $input );

		$this->assertEquals( $expected, $output );
	}

	/**
	 * Changing the seller in offers to point to our Organization ID
	 *
	 * @covers Schema::change_seller_in_offers
	 */
	public function test_change_seller_in_offers() {
		$input = [
			'@type'       => 'Product',
			'@id'         => 'https://example.com/product/customizable-responsive-toolset/#product',
			'name'        => 'Customizable responsive toolset',
			'url'         => 'https://example.com/product/customizable-responsive-toolset/',
			'description' => 'Sit debitis reprehenderit non rem natus. Corporis quidem quos et sit similique. Et ad hic exercitationem repudiandae rem laborum.\n\n\n\n\n\n\n\n\nCumque iusto cum enim ut. Et ipsum tempore dolorem ullam aspernatur autem et. Aut molestiae dolor natus. Ducimus molestias perspiciatis magni in libero deleniti ut. Rerum perspiciatis autem et maiores hic ducimus.\n\n\nAut tenetur ducimus distinctio quaerat deserunt sed. Sint ullam ut deserunt deleniti velit et. Incidunt in molestiae voluptas corrupti qui facilis quia.\n\n\n\n\n\nIste asperiores voluptas expedita id cupiditate. Sed error corrupti quibusdam dolor facere enim tenetur. Asperiores error qui commodi dolorem veritatis aspernatur.',
			'image'       => [
				'@id' => 'https://example.com/product/customizable-responsive-toolset/#primaryimage',
			],
			'sku'         => '209643',
			'offers'      => [
				[
					'@type'         => 'AggregateOffer',
					'lowPrice'      => '8.00',
					'highPrice'     => '12.00',
					'offerCount'    => 3,
					'priceCurrency' => 'GBP',
					'availability'  => 'http://schema.org/InStock',
					'url'           => 'https://example.com/product/customizable-responsive-toolset/',
					'seller'        => [
						'@type' => 'Organization',
						'name'  => 'WooCommerce',
						'url'   => 'https://example.com',
					],
					'@id'           => 'https://example.com/#/schema/aggregate-offer/24-0',

				],
			],
		];

		$options = Mockery::mock( 'alias:WPSEO_Options' );
		$options->expects( 'get' )->once()->with( 'company_or_person', false )->andReturn( 'company' );
		$options->expects( 'get' )->once()->with( 'company_name' )->andReturn( 'Yoast BV' );

		$utils = Mockery::mock( 'alias:WPSEO_Utils' );
		$utils->expects( 'get_home_url' )->andReturn( 'https://example.com' );

		Mockery::getConfiguration()->setConstantsMap(
			[
				'WPSEO_Schema_IDs' => [
					'ORGANIZATION_HASH'  => '#organization',
					'WEBPAGE_HASH'       => '#webpage',
					'PRIMARY_IMAGE_HASH' => '#primaryimage',
				],
			]
		);
		Mockery::mock( 'alias:WPSEO_Schema_IDs' );

		$expected                        = $input;
		$expected['offers'][0]['seller'] = [ '@id' => 'https://example.com/#organization' ];
		$schema                          = new Schema_Double();
		$output                          = $schema->change_seller_in_offers( $input );

		$this->assertEquals( $expected, $output );
	}

	/**
	 * Test if our review filtering works and leaves empty reviews unchanged.
	 *
	 * @covers Schema::filter_reviews
	 */
	public function test_filter_reviews_empty() {
		$input   = [
			'review' => [],
		];
		$schema  = new Schema_Double();
		$product = Mockery::mock( 'WC_Product' );

		$output = $schema->filter_reviews( $input, $product );

		$this->assertEquals( $input, $output );
	}

	/**
	 * Test adding the global identifier
	 *
	 * @covers Schema::add_global_identifier
	 */
	public function test_add_global_identifier_false() {
		$product = Mockery::mock( 'WC_Product' );
		$product->expects( 'get_id' )->once()->andReturn( 123 );

		Functions\stubs(
			[
				'get_post_meta' => false,
			]
		);

		$schema = new Schema_Double();

		$this->assertFalse( $schema->add_global_identifier( $product ) );
	}

	/**
	 * Test adding the global identifier
	 *
	 * @covers Schema::add_global_identifier
	 */
	public function test_add_global_identifier_gtin() {
		$product = Mockery::mock( 'WC_Product' );
		$product->expects( 'get_id' )->once()->andReturn( 123 );

		$data = [
			'gtin8' => '123',
		];

		Functions\stubs(
			[
				'get_post_meta' => $data,
			]
		);

		$schema = new Schema_Double();
		$schema->add_global_identifier( $product );

		$this->assertEquals( $data, $schema->data );
	}

	/**
	 * Test adding an ISBN number.
	 *
	 * @covers Schema::add_global_identifier
	 */
	public function test_add_global_identifier_isbn() {
		$product = Mockery::mock( 'WC_Product' );
		$product->expects( 'get_id' )->once()->andReturn( 123 );

		$data = [
			'isbn' => '978-3-16-148410-0',
		];

		Functions\stubs(
			[
				'get_post_meta' => $data,
			]
		);

		$schema = new Schema_Double();
		$schema->add_global_identifier( $product );

		$expected = [
			'@type' => [ 'Book', 'Product' ],
			'isbn'  => '978-3-16-148410-0',
		];
		$this->assertEquals( $expected, $schema->data );
	}

	/**
	 * Test if our review filtering works.
	 *
	 * @covers Schema::filter_reviews
	 */
	public function test_filter_reviews() {
		$input = [
			'review' => [
				[
					'@type'         => 'Review',
					'reviewRating'  => [
						'@type'       => 'Rating',
						'ratingValue' => '2',
					],
					'author'        => [
						'@type' => 'Person',
						'name'  => 'Fleta Herman',
					],
					'reviewBody'    => 'Et eum odit nihil voluptas. Repudiandae expedita possimus quam quos ab dolorem rerum. Quam impedit omnis voluptatum commodi aliquid. Accusantium cumque qui sequi deleniti voluptate quia.',
					'datePublished' => '2020-01-14T14:02:48+00:00',
				],
				[
					'@type'         => 'Review',
					'reviewRating'  => [
						'@type'       => 'Rating',
						'ratingValue' => '5',
					],
					'author'        => [
						'@type' => 'Person',
						'name'  => 'Prof. Alexane Luettgen',
					],
					'reviewBody'    => 'Omnis id tempore quae dolor in. Sunt aliquam rem animi. Repellendus porro voluptatem ut id illo veritatis ullam voluptatum.',
					'datePublished' => '2020-01-14T14:02:47+00:00',
				],
			],
		];

		$base_url = 'https://example.com';
		Functions\stubs(
			[
				'get_site_url' => $base_url,
			]
		);
		$product = Mockery::mock( 'WC_Product' );
		$product->expects( 'get_id' )->once()->andReturn( 24 );
		$product->expects( 'get_name' )->once()->andReturn( 'Example product' );

		$schema = new Schema_Double();
		$output = $schema->filter_reviews( $input, $product );

		$expected = [
			'review' => [
				'0' => [
					'@type'         => 'Review',
					'reviewRating'  => [
						'@type'       => 'Rating',
						'ratingValue' => 2,
					],
					'author'        => [
						'@type' => 'Person',
						'name'  => 'Fleta Herman',
					],
					'reviewBody'    => 'Et eum odit nihil voluptas. Repudiandae expedita possimus quam quos ab dolorem rerum. Quam impedit omnis voluptatum commodi aliquid. Accusantium cumque qui sequi deleniti voluptate quia.',
					'datePublished' => '2020-01-14T14:02:48+00:00',
					'@id'           => 'https://example.com/#/schema/review/24-0',
					'name'          => 'Example product',
				],
				'1' => [
					'@type'         => 'Review',
					'reviewRating'  => [
						'@type'       => 'Rating',
						'ratingValue' => 5,
					],
					'author'        => [
						'@type' => 'Person',
						'name'  => 'Prof. Alexane Luettgen',
					],
					'reviewBody'    => 'Omnis id tempore quae dolor in. Sunt aliquam rem animi. Repellendus porro voluptatem ut id illo veritatis ullam voluptatum.',
					'datePublished' => '2020-01-14T14:02:47+00:00',
					'@id'           => 'https://example.com/#/schema/review/24-1',
					'name'          => 'Example product',
				],
			],
		];

		$this->assertEquals( $expected, $output );
	}

	/**
	 * Tests that should_output_yoast_schema returns the right value.
	 *
	 * @covers \Schema::should_output_yoast_schema
	 */
	public function test_should_output_yoast_schema() {
		Monkey\Filters\expectApplied( 'wpseo_json_ld_output' )->once()->andReturn( true );

		$actual = Schema::should_output_yoast_schema();
		$this->assertTrue( $actual );

		Monkey\Filters\expectApplied( 'wpseo_json_ld_output' )->once()->andReturn( false );

		$actual = Schema::should_output_yoast_schema();
		$this->assertFalse( $actual );
	}

	/**
	 * Tests that the schema data after change product is as expected.
	 *
	 * @covers \Schema::change_product
	 * @covers \Schema::get_canonical
	 * @covers \Schema::add_image
	 * @covers \Schema::add_brand
	 * @covers \Schema::add_manufacturer
	 * @covers \Schema::add_organization_for_attribute
	 */
	public function test_change_product() {
		$product_id   = 1;
		$product_name = 'TestProduct';
		$base_url     = 'http://local.wordpress.test';
		$canonical    = $base_url . '/product/test/';

		$utils = Mockery::mock( 'alias:WPSEO_Utils' );
		$utils->expects( 'get_home_url' )->once()->with()->andReturn( $canonical );

		$product = Mockery::mock( 'WC_Product' );
		$product->expects( 'get_id' )->times( 5 )->with()->andReturn( $product_id );
		$product->expects( 'get_name' )->once()->with()->andReturn( $product_name );
		$product->expects( 'get_price' )->once()->with()->andReturn( 1 );
		$product->expects( 'get_min_purchase_quantity' )->once()->with()->andReturn( 1 );

		Mockery::getConfiguration()->setConstantsMap(
			[
				'WPSEO_Schema_IDs' => [
					'ORGANIZATION_HASH'  => '#organization',
					'WEBPAGE_HASH'       => '#webpage',
					'PRIMARY_IMAGE_HASH' => '#primaryimage',
				],
			]
		);
		Mockery::mock( 'alias:WPSEO_Schema_IDs' );

		$mock = Mockery::mock( 'alias:WPSEO_Options' );
		$mock->expects( 'get' )->once()->with( 'woo_schema_brand' )->andReturn( 'product_cat' );
		$mock->expects( 'get' )->once()->with( 'woo_schema_manufacturer' )->andReturn( 'product_cat' );
		$mock->expects( 'get' )->once()->with( 'company_or_person', false )->andReturn( 'company' );
		$mock->expects( 'get' )->once()->with( 'company_name' )->andReturn( 'WP' );

		Functions\stubs(
			[
				'has_post_thumbnail'       => true,
				'home_url'                 => $base_url,
				'get_site_url'             => $base_url,
				'get_post_meta'            => false,
				'wc_placeholder_img_src'   => $base_url . '/example_image.jpg',
				'wc_get_price_decimals'    => 2,
				'wc_tax_enabled'           => false,
				'wc_format_decimal'        => function ( $number ) {
					return number_format( $number, 2 );
				},
				'get_woocommerce_currency' => 'GBP',
			]
		);

		$instance = Mockery::mock( Schema_Double::class )->makePartial();
		$instance->expects( 'get_canonical' )->once()->with()->andReturn( $canonical );
		$instance->expects( 'get_primary_term_or_first_term' )->twice()->with( 'product_cat', 1 )->andReturn( (object) [ 'name' => $product_name ] );

		$image_data   = [
			'url'    => $base_url . '/example_image.jpg',
			'width'  => 50,
			'height' => 50,
		];
		$schema_image = Mockery::mock( 'overload:WPSEO_Schema_Image' );
		$schema_image->expects( '__construct' )->once()->with( $canonical . '#woocommerceimageplaceholder' )->andReturnSelf();
		$schema_image->expects( 'generate_from_url' )->once()->with( $base_url . '/example_image.jpg' )->andReturn( $image_data );

		$data = [
			'@type'       => 'Product',
			'@id'         => $canonical . '#product',
			'name'        => $product_name,
			'url'         => $canonical,
			'image'       => false,
			'description' => '',
			'sku'         => 'sku1234',
			'offers'      => [
				[
					'@type'  => 'Offer',
					'price'  => '1.00',
					'url'    => $canonical,
					'seller' => [
						'@type' => 'Organization',
						'name'  => 'WP',
						'url'   => $base_url,
					],
				],
			],
			'review'      => [
				[
					'@type'         => 'Review',
					'reviewRating'  => [
						'@type'       => 'Rating',
						'ratingValue' => 5,
					],
					'author'        => [
						'@type' => 'Person',
						'name'  => 'Joost de Valk',
					],
					'reviewBody'    => 'Product review',
					'datePublished' => '2020-01-07T13:36:12+00:00',
				],
			],
		];

		$expected = [
			'@type'            => 'Product',
			'@id'              => $canonical . '#product',
			'name'             => $product_name,
			'url'              => $canonical,
			'image'            => [ '@id' => $canonical . '#primaryimage' ],
			'description'      => '',
			'sku'              => 'sku1234',
			'offers'           => [
				[
					'@type'              => 'Offer',
					'price'              => '1.00',
					'priceSpecification' => [
						'price'                 => '1.00',
						'priceCurrency'         => 'GBP',
						'valueAddedTaxIncluded' => false,
					],
					'url'                => $canonical,
					'seller'             => [
						'@id' => $canonical . '#organization',
					],
					'@id'                => $base_url . '/#/schema/offer/1-0',
				],
			],
			'review'           => [
				[
					'@type'         => 'Review',
					'reviewRating'  => [
						'@type'       => 'Rating',
						'ratingValue' => 5,
					],
					'author'        => [
						'@type' => 'Person',
						'name'  => 'Joost de Valk',
					],
					'reviewBody'    => 'Product review',
					'datePublished' => '2020-01-07T13:36:12+00:00',
					'@id'           => $base_url . '/#/schema/review/' . $product_id . '-0',
					'name'          => $product_name,
				],
			],
			'mainEntityOfPage' => [ '@id' => $canonical . '#webpage' ],
			'brand'            => [
				'@type' => 'Organization',
				'name'  => $product_name,
			],
			'manufacturer'     => [
				'@type' => 'Organization',
				'name'  => $product_name,
			],
		];

		$instance->change_product( $data, $product );
		$this->assertEquals( $expected, $instance->data );
	}

	/**
	 * Tests that the schema data after change product is as expected.
	 *
	 * @covers \Schema::change_product
	 * @covers \Schema::get_canonical
	 * @covers \Schema::add_image
	 * @covers \Schema::add_brand
	 * @covers \Schema::add_manufacturer
	 * @covers \Schema::add_organization_for_attribute
	 */
	public function test_change_product_no_thumb() {
		$product_id   = 1;
		$product_name = 'TestProduct';
		$base_url     = 'http://local.wordpress.test';
		$canonical    = $base_url . '/product/test/';

		$utils = Mockery::mock( 'alias:WPSEO_Utils' );
		$utils->expects( 'get_home_url' )->once()->with()->andReturn( $canonical );

		$product = Mockery::mock( 'WC_Product' );
		$product->expects( 'get_id' )->times( 5 )->with()->andReturn( $product_id );
		$product->expects( 'get_name' )->once()->with()->andReturn( $product_name );
		$product->expects( 'get_price' )->once()->andReturn( 1 );
		$product->expects( 'get_min_purchase_quantity' )->once()->andReturn( 1 );

		Mockery::getConfiguration()->setConstantsMap(
			[
				'WPSEO_Schema_IDs' => [
					'ORGANIZATION_HASH'  => '#organization',
					'WEBPAGE_HASH'       => '#webpage',
					'PRIMARY_IMAGE_HASH' => '#primaryimage',
				],
			]
		);
		Mockery::mock( 'alias:WPSEO_Schema_IDs' );

		$mock = Mockery::mock( 'alias:WPSEO_Options' );
		$mock->expects( 'get' )->once()->with( 'woo_schema_brand' )->andReturn( 'product_cat' );
		$mock->expects( 'get' )->once()->with( 'woo_schema_manufacturer' )->andReturn( 'product_cat' );
		$mock->expects( 'get' )->once()->with( 'company_or_person', false )->andReturn( 'company' );
		$mock->expects( 'get' )->once()->with( 'company_name' )->andReturn( 'WP' );

		Functions\stubs(
			[
				'has_post_thumbnail'       => false,
				'home_url'                 => $base_url,
				'get_site_url'             => $base_url,
				'get_post_meta'            => false,
				'wc_placeholder_img_src'   => $base_url . '/example_image.jpg',
				'wc_get_price_decimals'    => 2,
				'wc_tax_enabled'           => false,
				'wc_format_decimal'        => function ( $number ) {
					return number_format( $number, 2 );
				},
				'get_woocommerce_currency' => 'GBP',
			]
		);

		$instance = Mockery::mock( Schema_Double::class )->makePartial();
		$instance->expects( 'get_canonical' )->once()->with()->andReturn( $canonical );
		$instance->expects( 'get_primary_term_or_first_term' )->twice()->with( 'product_cat', 1 )->andReturn( (object) [ 'name' => $product_name ] );

		$image_data   = [
			'@type'  => 'ImageObject',
			'@id'    => $canonical . '#woocommerceimageplaceholder',
			'url'    => $base_url . '/example_image.jpg',
			'width'  => 50,
			'height' => 50,
		];
		$schema_image = Mockery::mock( 'overload:WPSEO_Schema_Image' );
		$schema_image->expects( '__construct' )->once()->with( $canonical . '#woocommerceimageplaceholder' )->andReturnSelf();
		$schema_image->expects( 'generate_from_url' )->once()->with( $base_url . '/example_image.jpg' )->andReturn( $image_data );

		$data = [
			'@type'       => 'Product',
			'@id'         => $canonical . '#product',
			'name'        => $product_name,
			'url'         => $canonical,
			'image'       => false,
			'description' => '',
			'sku'         => 'sku1234',
			'offers'      => [
				[
					'@type'  => 'Offer',
					'price'  => '1.00',
					'url'    => $canonical,
					'seller' => [
						'@type' => 'Organization',
						'name'  => 'WP',
						'url'   => $base_url,
					],
				],
			],
			'review'      => [
				[
					'@type'         => 'Review',
					'reviewRating'  => [
						'@type'       => 'Rating',
						'ratingValue' => 5,
					],
					'author'        => [
						'@type' => 'Person',
						'name'  => 'Joost de Valk',
					],
					'reviewBody'    => 'Product review',
					'datePublished' => '2020-01-07T13:36:12+00:00',
				],
			],
		];

		$expected = [
			'@type'            => 'Product',
			'@id'              => $canonical . '#product',
			'name'             => $product_name,
			'url'              => $canonical,
			'image'            => [
				'@id'    => $canonical . '#woocommerceimageplaceholder',
				'@type'  => 'ImageObject',
				'url'    => $base_url . '/example_image.jpg',
				'width'  => 50,
				'height' => 50,
			],
			'description'      => '',
			'sku'              => 'sku1234',
			'offers'           => [
				[
					'@type'              => 'Offer',
					'price'              => '1.00',
					'url'                => $canonical,
					'seller'             => [
						'@id' => $canonical . '#organization',
					],
					'@id'                => $base_url . '/#/schema/offer/1-0',
					'priceSpecification' => [
						'price'                 => '1.00',
						'priceCurrency'         => 'GBP',
						'valueAddedTaxIncluded' => false,
					],
				],
			],
			'review'           => [
				[
					'@type'         => 'Review',
					'reviewRating'  => [
						'@type'       => 'Rating',
						'ratingValue' => 5,
					],
					'author'        => [
						'@type' => 'Person',
						'name'  => 'Joost de Valk',
					],
					'reviewBody'    => 'Product review',
					'datePublished' => '2020-01-07T13:36:12+00:00',
					'@id'           => $base_url . '/#/schema/review/' . $product_id . '-0',
					'name'          => $product_name,
				],
			],
			'mainEntityOfPage' => [ '@id' => $canonical . '#webpage' ],
			'brand'            => [
				'@type' => 'Organization',
				'name'  => $product_name,
			],
			'manufacturer'     => [
				'@type' => 'Organization',
				'name'  => $product_name,
			],
		];

		$instance->change_product( $data, $product );
		$this->assertEquals( $expected, $instance->data );
	}

	/**
	 * Tests that get_primary_term_or_first_term returns the primary term.
	 *
	 * @covers \Schema::get_primary_term_or_first_term
	 */
	public function test_get_primary_term_or_first_term_expecting_primary_term() {
		$id            = 1;
		$taxonomy_name = 'product_cat';
		$wp_term       = Mockery::mock( 'WP_Term' );

		$primary_term = Mockery::mock( 'overload:WPSEO_Primary_Term' );
		$primary_term->expects( 'get_primary_term' )->once()->with()->andReturn( $id );

		Functions\expect( 'get_term' )->once()->with( $id )->andReturn( $wp_term );
		Functions\expect( 'get_the_terms' )->never()->withAnyArgs();

		$instance = new Schema_Double();
		$actual   = $instance->get_primary_term_or_first_term( $taxonomy_name, $id );

		$this->assertSame( $wp_term, $actual );
	}

	/**
	 * Tests that get_primary_term_or_first_term returns the first term.
	 *
	 * @covers \Schema::get_primary_term_or_first_term
	 */
	public function test_get_primary_term_or_first_term_expecting_first_term() {
		$id            = 1;
		$taxonomy_name = 'product_cat';
		$wp_term       = Mockery::mock( 'WP_Term' );

		$primary_term = Mockery::mock( 'overload:WPSEO_Primary_Term' );
		$primary_term->expects( 'get_primary_term' )->once()->with()->andReturn( false );

		Functions\expect( 'get_term' )
			->never()
			->withAnyArgs();

		Functions\expect( 'get_the_terms' )
			->once()
			->with( $id, $taxonomy_name )
			->andReturn(
				[
					$wp_term,
					'other term',
				]
			);

		$instance = new Schema_Double();
		$actual   = $instance->get_primary_term_or_first_term( $taxonomy_name, $id );

		$this->assertSame( $wp_term, $actual );
	}

	/**
	 * Tests that get_primary_term_or_first_term returns the first term.
	 *
	 * @covers \Schema::get_primary_term_or_first_term
	 */
	public function test_get_primary_term_or_first_term_without_terms() {
		$id            = 1;
		$taxonomy_name = 'product_cat';

		$primary_term = Mockery::mock( 'overload:WPSEO_Primary_Term' );
		$primary_term->expects( 'get_primary_term' )->once()->with()->andReturn( false );

		Functions\expect( 'get_term' )->never()->withAnyArgs();
		Functions\expect( 'get_the_terms' )->once()->with( $id, $taxonomy_name )->andReturn( [] );

		$instance = new Schema_Double();
		$actual   = $instance->get_primary_term_or_first_term( $taxonomy_name, $id );

		$this->assertNull( $actual );
	}
}

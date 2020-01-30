<?php

namespace Yoast\WP\Woocommerce\Tests\Classes;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Yoast\WP\Woocommerce\Classes\OpenGraph;
use Yoast\WP\Woocommerce\Tests\Doubles\OpenGraph_Double;
use Yoast\WP\Woocommerce\Tests\TestCase;

/**
 * Class WooCommerce_Schema_Test.
 */
class OpenGraph_Test extends TestCase {

	/**
	 * Test that our constructor works.
	 *
	 * @covers OpenGraph::__construct
	 */
	public function test_construct() {
		$og = new OpenGraph();

		$this->assertTrue( has_filter( 'language_attributes', [ $og, 'product_namespace' ] ) );
		$this->assertTrue( has_filter( 'wpseo_opengraph_type', [ $og, 'return_type_product' ] ) );
		$this->assertTrue( has_filter( 'wpseo_opengraph_desc', [ $og, 'product_taxonomy_desc_enhancement' ] ) );
		$this->assertTrue( has_action( 'wpseo_opengraph', [ $og, 'product_enhancement' ] ) );
		$this->assertTrue( has_action( 'wpseo_add_opengraph_additional_images', [ $og, 'set_opengraph_image' ] ) );

		$this->assertTrue( has_action( 'Yoast\WP\Woocommerce\opengraph', [ $og, 'brand' ] ) );
		$this->assertTrue( has_action( 'Yoast\WP\Woocommerce\opengraph', [ $og, 'price' ] ) );
		$this->assertTrue( has_action( 'Yoast\WP\Woocommerce\opengraph', [ $og, 'in_stock' ] ) );
		$this->assertTrue( has_action( 'Yoast\WP\Woocommerce\opengraph', [ $og, 'retailer_item_id' ] ) );
		$this->assertTrue( has_action( 'Yoast\WP\Woocommerce\opengraph', [ $og, 'product_condition' ] ) );
	}

	/**
	 * Test that our constructor works.
	 *
	 * @covers OpenGraph::return_type_product
	 */
	public function test_return_type_product() {
		Functions\stubs(
			[
				'is_singular' => true,
			]
		);

		$og = new OpenGraph();
		$this->assertEquals( 'product', $og->return_type_product( 'article' ) );

		Functions\stubs(
			[
				'is_singular' => false,
			]
		);
		$this->assertEquals( 'article', $og->return_type_product( 'article' ) );
	}

	/**
	 * Test the OpenGraph description enhancement.
	 *
	 * @covers OpenGraph::product_taxonomy_desc_enhancement
	 */
	public function test_product_taxonomy_desc_enhancement() {
		Functions\stubs(
			[
				'is_product_taxonomy' => false,
			]
		);

		$og = new OpenGraph();
		$this->assertEquals( 'example description', $og->product_taxonomy_desc_enhancement( 'example description' ) );

		$expected = 'This is our expected description';

		Functions\stubs(
			[
				'is_product_taxonomy' => true,
				'term_description'    => $expected,
				'wp_strip_all_tags'   => null,
				'strip_shortcodes'    => null,
			]
		);
		$this->assertEquals( $expected, $og->product_taxonomy_desc_enhancement( 'example description' ) );
	}

	/**
	 * Test the OpenGraph enhancement.
	 *
	 * @covers OpenGraph::product_namespace
	 */
	public function test_product_namespace() {
		$og = new OpenGraph();

		Functions\stubs(
			[
				'is_singular' => false,
			]
		);

		$input = 'prefix="fn: https://yoast.com/bla"';
		$this->assertEquals( $input, $og->product_namespace( $input ) );

		Functions\stubs(
			[
				'is_singular' => true,
			]
		);
		$this->assertEquals( 'prefix="fn: https://yoast.com/bla product: http://ogp.me/ns/product#"', $og->product_namespace( $input ) );
	}

	/**
	 * Test OpenGraph price enhancement.
	 *
	 * @covers OpenGraph::price
	 */
	public function test_price() {
		$tax_rate   = 1.1;
		$base_price = 54;

		Functions\stubs(
			[
				'apply_filters_deprecated'   => true,
				'apply_filters'              => true,
				'esc_attr'                   => null,
				'get_woocommerce_currency'   => 'USD',
				'wc_get_price_decimals'      => 2,
				'wc_tax_enabled'             => true,
				'wc_prices_include_tax'      => false,
				'wc_get_price_including_tax' => function ( $product, $args ) {
					return ( $args['price'] * 1.1 );
				},
				'wc_format_decimal'          => function ( $number ) {
					return number_format( $number, 2 );
				},
			]
		);

		Monkey\Functions\expect( 'get_option' )
			->once()
			->with( 'woocommerce_tax_display_shop' )
			->andReturn( 'incl' );

		$options = Mockery::mock( 'alias:WPSEO_Options' );
		$options->expects( 'get' )->once()->with( 'woo_schema_og_prices_with_tax' )->andReturn( true );

		$product = Mockery::mock( 'WC_Product' );
		$product->expects( 'get_price' )->once()->andReturn( $base_price );
		$product->expects( 'get_min_purchase_quantity' )->once()->andReturn( 1 );

		$og = new OpenGraph();

		ob_start();
		$og->price( $product );

		$expected = '<meta property="product:price:amount" content="' . number_format( ( $base_price * $tax_rate ), 2 ) . '" />' . "\n" . '<meta property="product:price:currency" content="USD" />' . "\n";
		$this->assertEquals( $expected, ob_get_clean() );
	}

	/**
	 * Test the OpenGraph brand enhancement.
	 *
	 * @covers OpenGraph::brand
	 * @covers OpenGraph::get_brand_term_name
	 */
	public function test_brand() {
		$product = Mockery::mock( 'WC_Product' )->makePartial();
		$product->expects( 'get_id' )->once();

		$options = Mockery::mock( 'alias:WPSEO_Options' )->makePartial();
		$options->expects( 'get' )->once()->with( 'woo_schema_brand' )->andReturn( 'brand' );

		Functions\stubs(
			[
				'wc_get_product'        => $product,
				'get_queried_object_id' => 1,
				'get_the_ID'            => 1,
				'wp_strip_all_tags'     => null,
				'strip_shortcodes'      => null,
				'get_the_terms'         => [ 'Apple' => (object) [ 'name' => 'Apple' ] ],
				'get_term_by'           => function ( $thing, $term, $taxonomy ) {
					return (object) [ 'name' => 'Apple' ];
				},
			]
		);

		$primary_term_mock = Mockery::mock( 'overload:WPSEO_Primary_Term' );
		$primary_term_mock->expects( '__construct' )->once()->with( 'brand', null )->andReturnSelf();
		$primary_term_mock->expects( 'get_primary_term' )->once()->with()->andReturn( 12345 );

		$og = new OpenGraph();
		ob_start();
		$og->brand( $product );

		$this->assertEquals( '<meta property="product:brand" content="Apple"/>' . "\n", ob_get_clean() );
	}

	/**
	 * Test the OpenGraph product condition enhancement.
	 *
	 * @covers OpenGraph::product_condition
	 */
	public function test_product_condition() {
		$product = Mockery::mock( 'WC_Product' )->makePartial();

		Functions\stubs(
			[
				'apply_filters' => 'used',
				'esc_attr'      => null,
			]
		);

		$og = new OpenGraph();
		ob_start();
		$og->product_condition( $product );

		$this->assertEquals( '<meta property="product:condition" content="used" />' . "\n", ob_get_clean() );
	}

	/**
	 * Test the OpenGraph retailer item ID enhancement.
	 *
	 * @covers OpenGraph::retailer_item_id
	 */
	public function test_retailer_item_id() {
		$product = Mockery::mock( 'WC_Product' )->makePartial();
		$product->expects( 'get_sku' )->andReturn( 'sku123' );

		Functions\stubs(
			[
				'esc_attr' => null,
			]
		);

		$og = new OpenGraph();
		ob_start();
		$og->retailer_item_id( $product );

		$this->assertEquals( '<meta property="product:retailer_item_id" content="sku123" />' . "\n", ob_get_clean() );
	}

	/**
	 * Test the OpenGraph in stock enhancement.
	 *
	 * @covers OpenGraph::in_stock
	 */
	public function test_in_stock() {
		$product = Mockery::mock( 'WC_Product' )->makePartial();
		$product->expects( 'is_in_stock' )->andReturn( true );

		$og = new OpenGraph();
		ob_start();
		$og->in_stock( $product );

		$this->assertEquals( '<meta property="product:availability" content="in stock" />' . "\n", ob_get_clean() );
	}

	/**
	 * Test setting the OpenGraph image.
	 *
	 * @covers OpenGraph::set_opengraph_image
	 * @covers OpenGraph::set_opengraph_image_product_category
	 */
	public function test_set_opengraph_image_product_category() {
		$og_image = Mockery::mock( 'alias:WPSEO_OpenGraph_Image' );
		$og_image->expects( 'add_image_by_id' )->once()->with( 123 );

		Functions\stubs(
			[
				'is_product_category'   => true,
				'get_term_meta'         => 123,
				'get_queried_object_id' => 12,
			]
		);

		$og = new OpenGraph();
		$this->assertTrue( $og->set_opengraph_image( $og_image ) );
	}

	/**
	 * Test setting the OpenGraph image.
	 *
	 * @covers OpenGraph::set_opengraph_image
	 * @covers OpenGraph::set_opengraph_image_product_category
	 */
	public function test_set_opengraph_image_product_category_no_image() {
		$og_image = Mockery::mock( 'alias:WPSEO_OpenGraph_Image' );

		Functions\stubs(
			[
				'is_product_category'   => true,
				'get_term_meta'         => false,
				'get_queried_object_id' => 12,
			]
		);

		$og = new OpenGraph();
		$this->assertFalse( $og->set_opengraph_image( $og_image ) );
	}

	/**
	 * Test setting the OpenGraph image.
	 *
	 * @covers OpenGraph::set_opengraph_image
	 * @covers OpenGraph::set_opengraph_image_product
	 */
	public function test_set_opengraph_image_product() {
		$og_image = Mockery::mock( 'alias:WPSEO_OpenGraph_Image' );
		$og_image->expects( 'add_image_by_id' )->times( 3 );

		$product = Mockery::mock( 'WC_Product' );
		$product->expects( 'get_gallery_image_ids' )->once()->andReturn( [ 1234, 1235, 1236 ] );

		Functions\stubs(
			[
				'is_product_category'   => false,
				'wc_get_product'        => $product,
				'get_queried_object_id' => 12,
			]
		);

		$og = new OpenGraph();
		$this->assertTrue( $og->set_opengraph_image( $og_image ) );
	}

	/**
	 * Test setting the OpenGraph image.
	 *
	 * @covers OpenGraph::set_opengraph_image
	 * @covers OpenGraph::set_opengraph_image_product
	 */
	public function test_set_opengraph_image_product_no_image() {
		$og_image = Mockery::mock( 'alias:WPSEO_OpenGraph_Image' );

		$product = Mockery::mock( 'WC_Product' );
		$product->expects( 'get_gallery_image_ids' )->once()->andReturn( [] );

		Functions\stubs(
			[
				'is_product_category'   => false,
				'wc_get_product'        => $product,
				'get_queried_object_id' => 12,
			]
		);

		$og = new OpenGraph();
		$this->assertFalse( $og->set_opengraph_image( $og_image ) );
	}

	/**
	 * Test setting the OpenGraph image when there is no product.
	 *
	 * @covers OpenGraph::set_opengraph_image
	 */
	public function test_set_opengraph_image_no_product() {
		$og_image = Mockery::mock( 'alias:WPSEO_OpenGraph_Image' );

		Functions\stubs(
			[
				'is_product_category'   => false,
				'wc_get_product'        => false,
				'get_queried_object_id' => 12,
			]
		);

		$og = new OpenGraph();
		$this->assertFalse( $og->set_opengraph_image( $og_image ) );
	}

	/**
	 * Test setting the OpenGraph image when there are no image.
	 *
	 * @covers OpenGraph::set_opengraph_image
	 */
	public function test_set_opengraph_image_no_image() {
		$og_image = Mockery::mock( 'alias:WPSEO_OpenGraph_Image' );

		$product = Mockery::mock( 'WC_Product' );
		$product->expects( 'get_gallery_image_ids' )->once()->andReturn( [] );

		Functions\stubs(
			[
				'is_product_category'   => false,
				'wc_get_product'        => $product,
				'get_queried_object_id' => 12,
			]
		);

		$og = new OpenGraph();
		$this->assertFalse( $og->set_opengraph_image( $og_image ) );
	}

	/**
	 * Get brand term name test.
	 *
	 * @covers OpenGraph::get_brand_term_name
	 *
	 * @runInSeparateProcess
	 */
	public function test_get_brand_term_name() {
		$product = Mockery::mock( 'WC_Product' )->makePartial();
		$product->expects( 'get_id' )->once()->andReturn( 123 );

		$taxonomy = 'brand';

		Functions\stubs(
			[
				'get_the_terms' => [ (object) [ 'name' => 'Apple' ] ],
				'get_the_ID'    => 123,
			]
		);

		$primary_term_mock = Mockery::mock( 'overload:WPSEO_Primary_Term' );
		$primary_term_mock->expects( '__construct' )->once()->with( $taxonomy, 123 )->andReturnSelf();
		$primary_term_mock->expects( 'get_primary_term' )->once()->with()->andReturn( '' );

		$og = new OpenGraph_Double();
		$this->assertEquals( 'Apple', $og->get_brand_term_name( 'brand', $product ) );
	}

	/**
	 * Get brand term name test.
	 *
	 * @covers OpenGraph::get_brand_term_name
	 */
	public function test_get_brand_term_name_none() {
		$product = Mockery::mock( 'WC_Product' )->makePartial();
		$product->expects( 'get_id' )->once()->andReturn( 123 );

		$taxonomy = 'brand';

		Functions\stubs(
			[
				'get_the_terms' => false,
				'get_the_ID'    => 123,
			]
		);

		$primary_term_mock = Mockery::mock( 'overload:WPSEO_Primary_Term' );
		$primary_term_mock->expects( '__construct' )->once()->with( $taxonomy, 123 )->andReturnSelf();
		$primary_term_mock->expects( 'get_primary_term' )->once()->with()->andReturn( '' );

		$og = new OpenGraph_Double();
		$this->assertFalse( $og->get_brand_term_name( 'brand', $product ) );
	}

	/**
	 * Tests the main product OG function.
	 *
	 * @covers OpenGraph::product_enhancement
	 */
	public function test_product_enhancement_no_product() {
		Functions\stubs(
			[
				'wc_get_product'        => false,
				'get_queried_object_id' => 123,
			]
		);

		$og = new OpenGraph();
		$this->assertFalse( $og->product_enhancement() );
	}

	/**
	 * Tests the main product OG function.
	 *
	 * @covers OpenGraph::product_enhancement
	 */
	public function test_product_enhancement() {
		$product = Mockery::mock( 'WC_Product' )->makePartial();

		Functions\stubs(
			[
				'wc_get_product'        => $product,
				'get_queried_object_id' => 123,
				'do_action'             => null,
			]
		);

		$og = new OpenGraph();
		$this->assertTrue( $og->product_enhancement() );
	}
}

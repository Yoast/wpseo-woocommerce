<?php
/**
 * @package WPSEO/WooCommerce/Tests
 */

/**
 * Unit tests.
 */
class Yoast_WooCommerce_SEO_Test extends WPSEO_WooCommerce_UnitTestCase {

	/**
	 * Tests the filtering of Yoast SEO columns.
	 *
	 * @covers Yoast_WooCommerce_SEO::column_heading()
	 */
	public function test_column_heading() {
		$woocommerce = new Yoast_WooCommerce_SEO();

		$actual   = $woocommerce->column_heading(
			array(
				'wpseo-title'    => '',
				'another-column' => '',
				'wpseo-focuskw'  => '',
			)
		);
		$expected = array( 'another-column' => '' );

		$this->assertEquals( $expected, $actual );
	}
}

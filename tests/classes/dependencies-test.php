<?php

namespace Yoast\WP\Woocommerce\Tests\Classes;

use Mockery;
use Brain\Monkey\Functions;
use Yoast\WP\Woocommerce\Tests\Doubles\Dependencies_Double;
use Yoast\WP\Woocommerce\Tests\TestCase;

/**
 * Class WooCommerce_Schema_Test.
 */
class Dependencies_Test extends TestCase {

	/**
	 * Tests check dependencies.
	 *
	 * @covers Yoast_WooCommerce_Dependencies::check_dependencies
	 * @covers Yoast_WooCommerce_Dependencies::get_yoast_seo_version
	 * @covers Yoast_WooCommerce_Dependencies::check_woocommerce_exists
	 */
	public function test_check_dependencies() {
		$valid_wp_version        = '5.2';
		$valid_yoast_seo_version = '12.6-RC0';

		$class = Mockery::mock( Dependencies_Double::class )->makePartial();

		// Invalid WordPress version.
		$actual = $class->check_dependencies( '5.0' );
		$this->assertFalse( $actual );

		// Valid WordPress version.
		$class->expects( 'check_woocommerce_exists' )->once()->andReturn( true );
		$class->expects( 'get_yoast_seo_version' )->once()->andReturn( $valid_yoast_seo_version );
		$actual = $class->check_dependencies( '5.2' );
		$this->assertTrue( $actual );

		// WooCommerce in-active.
		$class->expects( 'check_woocommerce_exists' )->once()->andReturn( false );
		$actual = $class->check_dependencies( $valid_wp_version );
		$this->assertFalse( $actual );

		// WooCommerce active.
		$class->expects( 'check_woocommerce_exists' )->once()->andReturn( true );
		$class->expects( 'get_yoast_seo_version' )->once()->andReturn( $valid_yoast_seo_version );
		$actual = $class->check_dependencies( $valid_wp_version );
		$this->assertTrue( $actual );

		// No Yoast SEO.
		$class->expects( 'check_woocommerce_exists' )->once()->andReturn( true );
		$class->expects( 'get_yoast_seo_version' )->once()->andReturn( false );
		$actual = $class->check_dependencies( $valid_wp_version );
		$this->assertFalse( $actual );

		// Wrong Yoast SEO version.
		$class->expects( 'check_woocommerce_exists' )->once()->andReturn( true );
		$class->expects( 'get_yoast_seo_version' )->once()->andReturn( '12.5' );
		$actual = $class->check_dependencies( $valid_wp_version );
		$this->assertFalse( $actual );

		// Correct Yoast SEO version.
		$class->expects( 'check_woocommerce_exists' )->once()->andReturn( true );
		$class->expects( 'get_yoast_seo_version' )->once()->andReturn( $valid_yoast_seo_version );
		$actual = $class->check_dependencies( $valid_wp_version );
		$this->assertTrue( $actual );
	}

	/**
	 * Checks whether our WooCommerce test works as expected.
	 *
	 * @covers Yoast_WooCommerce_Dependencies::check_woocommerce_exists
	 */
	public function test_check_woocommerce_exists() {
		$class = Mockery::mock( Dependencies_Double::class )->makePartial();

		$this->assertFalse( $class->check_woocommerce_exists() );

		Mockery::mock( 'woocommerce' );

		$this->assertTrue( $class->check_woocommerce_exists() );
	}

	/**
	 * Checks whether Yoast SEO version retrieval works.
	 *
	 * @covers Yoast_WooCommerce_Dependencies::get_yoast_seo_version
	 */
	public function test_get_wordpress_seo_version() {
		$class = Mockery::mock( Dependencies_Double::class )->makePartial();

		$this->assertFalse( $class->get_yoast_seo_version() );

		$wpseo_version = '14.0';
		define( 'WPSEO_VERSION', $wpseo_version );

		$this->assertEquals( $wpseo_version, $class->get_yoast_seo_version() );
	}

	/**
	 * Checks our WooCommerce not active error output.
	 *
	 * @covers Yoast_WooCommerce_Dependencies::woocommerce_missing_error
	 */
	public function test_woocommerce_missing_error() {
		$expected = '<div class="error"><p>Please <a href="plugin-install.php?tab=search&type=term&s=woocommerce&plugin-search-input=Search+Plugins">install &amp; activate WooCommerce</a> to allow the Yoast WooCommerce SEO module to work.</p></div>';

		$this->error_message_test( 'woocommerce_missing_error', $expected );
	}

	/**
	 * Checks our Yoast SEO not active error output.
	 *
	 * @covers Yoast_WooCommerce_Dependencies::yoast_seo_missing_error
	 */
	public function test_yoast_seo_missing_error() {
		$expected = '<div class="error"><p>Please <a href="plugin-install.php?tab=search&type=term&s=yoast+seo&plugin-search-input=Search+Plugins">install &amp; activate Yoast SEO</a> to allow the Yoast WooCommerce SEO module to work.</p></div>';

		$this->error_message_test( 'yoast_seo_missing_error', $expected );
	}

	/**
	 * Checks our Yoast SEO not active error output.
	 *
	 * @covers Yoast_WooCommerce_Dependencies::wordpress_upgrade_error
	 */
	public function test_wordpress_upgrade_error() {
		$expected = '<div class="error"><p>Please upgrade WordPress to the latest version to allow WordPress and the Yoast WooCommerce SEO module to work properly.</p></div>';

		$this->error_message_test( 'wordpress_upgrade_error', $expected );
	}

	/**
	 * Checks our Yoast SEO not active error output.
	 *
	 * @covers Yoast_WooCommerce_Dependencies::yoast_seo_upgrade_error
	 */
	public function test_yoast_seo_upgrade_error() {
		$expected = '<div class="error"><p>Please upgrade the Yoast SEO plugin to the latest version to allow the Yoast WooCommerce SEO module to work.</p></div>';

		$this->error_message_test( 'yoast_seo_upgrade_error', $expected );
	}

	/**
	 * Test an error message's output.
	 *
	 * @param string $function Function to test.
	 * @param string $expected Expected output.
	 */
	private function error_message_test( $function, $expected ) {
		ob_start();
		$class = Mockery::mock( Dependencies_Double::class )->makePartial();

		Functions\stubs(
			[
				'esc_url'   => null,
				'admin_url' => null,
			]
		);
		$class->$function();
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals( $expected, $output, $function );
	}
}

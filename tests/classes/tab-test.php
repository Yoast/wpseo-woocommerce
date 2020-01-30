<?php

namespace Yoast\WP\Woocommerce\Tests\Classes;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Yoast\WP\Woocommerce\Tests\Doubles\Tab_Double;
use Yoast\WP\Woocommerce\Tests\TestCase;
use Yoast\WP\Woocommerce\Classes\Tab;

/**
 * Class Tab_Test.
 */
class Tab_Test extends TestCase {

	/**
	 * Test our constructor.
	 *
	 * @covers Tab::__construct
	 */
	public function test_construct() {
		$instance = new Tab();
		$this->assertTrue( has_filter( 'woocommerce_product_data_tabs', [ $instance, 'yoast_seo_tab' ] ) );
		$this->assertTrue( has_action( 'woocommerce_product_data_panels', [ $instance, 'add_yoast_seo_fields' ] ) );
		$this->assertTrue( has_action( 'save_post', [ $instance, 'save_data' ] ) );
	}

	/**
	 * Test adding our section to the Product Data section.
	 *
	 * @covers Tab::yoast_seo_tab
	 */
	public function test_yoast_seo_tab() {
		$instance = new Tab();
		$expected = [
			'yoast_tab' => [
				'label'  => 'Yoast SEO',
				'class'  => 'yoast-seo',
				'target' => 'yoast_seo',
			],
		];
		$this->assertEquals( $expected, $instance->yoast_seo_tab( [] ) );
	}

	/**
	 * Test loading our view.
	 *
	 * @covers Tab::add_yoast_seo_fields
	 */
	public function test_add_yoast_seo_fields() {
		ob_start();

		define( 'WPSEO_WOO_PLUGIN_FILE', './wpseo-woocommerce.php' );
		Functions\stubs(
			[
				'get_the_ID'      => 123,
				'get_post_meta'   => 'gtin8',
				'plugin_dir_path' => './',
				'_e'              => null,
				'esc_attr'        => null,
				'esc_html_e'      => null,
				'wp_nonce_field'  => function ( $action, $name ) {
					return '<input type="hidden" id="" name="' . $name . '" value="' . $action . '" />';
				},
			]
		);

		$instance = new Tab();
		$instance->add_yoast_seo_fields();

		$output = ob_get_contents();
		ob_end_clean();

		$this->assertContains( 'yoast_seo[gtin8]', $output );
		$this->assertContains( '<div id="yoast_seo" class="panel woocommerce_options_panel">', $output );
	}

	/**
	 * Test whether we don't save any data when the current save is a post revision.
	 *
	 * @covers Tab::save_data
	 */
	public function test_save_data_revision() {
		Functions\stubs(
			[
				'wp_is_post_revision' => true,
			]
		);

		$instance = new Tab();
		$this->assertFalse( $instance->save_data( 123 ) );
	}

	/**
	 * Test whether we don't save any data when there is no valid nonce.
	 *
	 * @covers Tab::save_data
	 */
	public function test_save_data_wrong_nonce() {
		Functions\stubs(
			[
				'wp_is_post_revision' => false,
				'wp_verify_nonce'     => false,
			]
		);

		$instance = new Tab();
		$this->assertFalse( $instance->save_data( 123 ) );
	}

	/**
	 * Test whether we don't save any data when there is nothing to save.
	 *
	 * @covers Tab::save_data
	 */
	public function test_save_data_empty() {
		Functions\stubs(
			[
				'wp_is_post_revision' => false,
				'wp_verify_nonce'     => true,
				'update_post_meta'    => true,
				'wp_strip_all_tags'   => function ( $value ) {
					// Ignoring WPCS's warning about using `wp_strip_all_tags` because we're *doing that*.
					// @phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
					return strip_tags( $value );
				},
			]
		);

		$instance = new Tab();

		// No $_POST data, so nothing to save.
		$this->assertTrue( $instance->save_data( 123 ) );
	}

	/**
	 * Test whether we save data when we have it.
	 *
	 * @covers Tab::save_data
	 * @covers Tab::save_post_data
	 */
	public function test_save_data() {
		Functions\stubs(
			[
				'wp_is_post_revision' => false,
				'wp_verify_nonce'     => true,
				'update_post_meta'    => true,
				'wp_unslash'          => null,
				'wp_strip_all_tags'   => function ( $value ) {
					// Ignoring WPCS's warning about using `wp_strip_all_tags` because we're *doing that*.
					// @phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
					return strip_tags( $value );
				},
			]
		);

		$instance = new Tab();

		$_POST = [
			'yoast_seo' => [
				'gtin8' => '1234',
			],
		];
		$this->assertTrue( $instance->save_data( 123 ) );
	}

	/**
	 * Test our data validation.
	 *
	 * @covers Tab::validate_data
	 */
	public function test_validate_data() {
		Functions\stubs(
			[
				'wp_strip_all_tags' => function ( $value ) {
					// Ignoring WPCS's warning about using `wp_strip_all_tags` because we're *doing that*.
					// @phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
					return strip_tags( $value );
				},
			]
		);

		$instance = new Tab_Double();
		$this->assertTrue( $instance->validate_data( '12345' ) );
		$this->assertFalse( $instance->validate_data( '12345<script>' ) );
		$this->assertTrue( $instance->validate_data( '' ) );
	}

	/**
	 * Test our input fields are outputted correctly.
	 *
	 * @covers Tab::input_field_for_identifier
	 */
	public function test_input_field_for_identifier() {
		Functions\stubs(
			[
				'esc_attr' => null,
				'esc_html' => null,
			]
		);

		ob_start();
		$instance = new Tab_Double();
		$instance->input_field_for_identifier( 'gtin8', 'GTIN 8', '12345678' );
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertContains( 'gtin8', $output );
		$this->assertContains( 'GTIN 8', $output );
		$this->assertContains( '12345678', $output );
		$this->assertContains( 'yoast_identifier_gtin8', $output );
	}
}

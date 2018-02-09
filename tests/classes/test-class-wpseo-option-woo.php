<?php
/**
 * @package WPSEO/WooCommerce/Tests
 */

/**
 * Unit tests.
 */
class WPSEO_Option_Woo_Test extends WPSEO_WooCommerce_UnitTestCase {

	/** @var WPSEO_Option_Woo_Double */
	protected $option;

	/**
	 * Requires the test double.
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		require_once './tests/doubles/option-woo-double.php';
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers WPSEO_Option_Woo::__construct()
	 */
	public function test_constructor() {
		$option = new WPSEO_Option_Woo_Double();
		$this->assertEquals(
			array(
				'price' => 'Price',
				'stock' => 'Stock',
			),
			$option->valid_data_types
		);
	}

	/**
	 * Gets the data from the data provider.
	 *
	 * @dataProvider validate_option_values
	 *
	 * @param string           $field_name The field name to validate.
	 * @param string|bool      $expected   The expected value.
	 * @param string|bool|null $dirty      The value for the dirty argument
	 * @param string|bool      $clean      The value for the clean argument.
	 * @param string|bool|null $old        The value for the old argument.
	 * @param string           $short      Determines whether the short form should set or not.
	 *
	 * @covers WPSEO_Option_Woo::validate_option()
	 */
	public function test_validate_option( $field_name, $expected, $dirty, $clean, $old, $short = 'off' ) {
		$option = $this
			->getMockBuilder( 'WPSEO_Option_Woo_Double' )
			->setMethods( array( 'get_taxonomies' ) )
			->getMock();

		$option
			->expects( $this->once() )
			->method( 'get_taxonomies' )
			->will( $this->returnValue( array( 'yoast' ) ) );

		$dirty = ( $dirty !== null ) ? array( $field_name => $dirty ) : array();
		$old   = ( $old !== null ) ? array( $field_name => $old ) : array();

		$this->assertEquals(
			array( $field_name => $expected ),
			$option->validate_option(
				array_merge( array( 'short_form' => $short ), $dirty ),
				array( $field_name => $clean ),
				$old
			)
		);
	}

	/**
	 * Provider for the test_validate_option.
	 *
	 * Formatting of each record that is provided:
	 * field, expected, dirty, clean, old and short-form
	 *
	 * @return array
	 */
	public function validate_option_values() {
		return array(
			// Tests a non defined value.
			array( 'test', null, 123, null, null ),

			// Tests the validation of the dbversion option.
			array( 'dbversion', 2, 1, 3, '' ),

			// Tests the validation of the fields where the dirty value exists in the validate data types.
			array( 'data1_type', 'price', 'price', 'price', null ),
			array( 'data2_type', 'price', 'price', 'price', null ),
			array( 'schema_brand', 'yoast', 'yoast', 'yoast', null ),
			array( 'schema_manufacturer', 'yoast', 'yoast', 'yoast', null ),
			array( 'breadcrumbs', true, true, true, '' ),
			array( 'hide_columns', true, true, true, '' ),
			array( 'metabox_woo_top', true, true, true, '' ),

			// Validation where the dirty value is not in the validate data types.
			array( 'data1_type', 'foo', 'foo', 'price', null ),
			array( 'data2_type', 'foo', 'foo', 'price', null ),
			array( 'schema_brand', 'bar', 'bar', 'yoast', null ),
			array( 'schema_manufacturer', 'bar', 'bar', 'yoast', null ),
			array( 'breadcrumbs', false, null, true, '' ),
			array( 'hide_columns', false, null, true, '' ),
			array( 'metabox_woo_top', false, null, true, '' ),

			// Validation where the old value is in the validate data types with short form enabled.
			array( 'data1_type', 'price', null, 'price', 'price', 'on' ),
			array( 'data2_type', 'price', null, 'price', 'price', 'on' ),
			array( 'schema_brand', 'yoast', null, 'yoast', 'yoast,', 'on' ),
			array( 'schema_manufacturer', 'yoast', null, 'yoast', 'yoast', 'on' ),

			// Validation where the old value isn't in the validate data types with short form enabled.
			array( 'data1_type', 'foo', null, 'price', 'foo', 'on' ),
			array( 'data2_type', 'foo', null, 'price', 'foo', 'on' ),
			array( 'schema_brand', 'bar', null, 'yoast', 'bar', 'on' ),
			array( 'schema_manufacturer', 'bar', null, 'yoast', 'bar', 'on' ),

			// Validation where the old value isn't in the validate data types with short form not enabled.
			array( 'data1_type', 'price', null, 'price', 'foo', 'off' ),
			array( 'data2_type', 'price', null, 'price', 'foo', 'off' ),
			array( 'schema_brand', 'yoast', null, 'yoast', 'bar', 'off' ),
			array( 'schema_manufacturer', 'yoast', null, 'yoast', 'bar', 'off' ),

			// Validation where the boolean old value is set with short form enabled.
			array( 'breadcrumbs', true, null, true, true, 'on' ),
			array( 'hide_columns', true, null, true, true, 'on' ),
			array( 'metabox_woo_top', true, null, true, true, 'on' ),

			// Validation where the boolean old value is not set with short form enabled.
			array( 'breadcrumbs', false, null, true, null, 'on' ),
			array( 'hide_columns', false, null, true, null, 'on' ),
			array( 'metabox_woo_top', false, null, true, null, 'on' ),

			// Validation where the boolean old value is not set with short form not enabled.
			array( 'breadcrumbs', false, null, true, true, 'off' ),
			array( 'hide_columns', false, null, true, true, 'off' ),
			array( 'metabox_woo_top', false, null, true, true, 'off' ),
		);
	}
}

<?php

namespace Yoast\WP\Woocommerce\Classes;

/**
 * Class Tab
 */
class Tab {

	/**
	 * The array of allowed identifier types.
	 *
	 * @var array
	 */
	protected $global_identifier_types = [
		'gtin8'  => 'GTIN8',
		'gtin12' => 'GTIN12 / UPC',
		'gtin13' => 'GTIN13 / EAN',
		'gtin14' => 'GTIN14 / ITF-14',
		'isbn'   => 'ISBN',
		'mpn'    => 'MPN',
	];

	/**
	 * Tab constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'yoast_seo_tab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'add_yoast_seo_fields' ] );
		add_action( 'save_post', [ $this, 'save_data' ] );
	}

	/**
	 * Adds the Yoast SEO product tab.
	 *
	 * @param array $tabs The current product data tabs.
	 *
	 * @return array An array with product tabs with the Yoast SEO tab added.
	 */
	public function yoast_seo_tab( $tabs ) {
		$tabs['yoast_tab'] = [
			'label'  => 'Yoast SEO',
			'class'  => 'yoast-seo',
			'target' => 'yoast_seo',
		];

		return $tabs;
	}

	/**
	 * Outputs our tab content.
	 *
	 * @return void
	 */
	public function add_yoast_seo_fields() {
		$global_identifier_values = get_post_meta( get_the_ID(), 'wpseo_global_identifier_values', true );

		echo '<div id="yoast_seo" class="panel woocommerce_options_panel">';
		echo '<div class="options_group">';
		wp_nonce_field( 'yoast_woo_seo_identifiers', '_wpnonce_yoast_seo_woo' );

		foreach ( $this->global_identifier_types as $type => $label ) {
			$value = isset( $global_identifier_values[ $type ] ) ? $global_identifier_values[ $type ] : '';
			$this->input_field_for_identifier( $type, $label, $value );
		}
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Save the $_POST values from our tab.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return boolean Whether or not we saved data.
	 */
	public function save_data( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return false;
		}

		$nonce = filter_input( INPUT_POST, '_wpnonce_yoast_seo_woo' );
		if ( ! wp_verify_nonce( $nonce, 'yoast_woo_seo_identifiers' ) ) {
			return false;
		}

		$values = $this->save_post_data();

		if ( $values !== [] ) {
			return update_post_meta( $post_id, 'wpseo_global_identifier_values', $values );
		}

		return false;
	}

	/**
	 * Make sure the data is safe to save.
	 *
	 * @param string $value The value we're testing.
	 *
	 * @return bool True when safe and not empty, false when it's not.
	 */
	protected function validate_data( $value ) {
		if ( wp_strip_all_tags( $value ) !== $value ) {
			return false;
		}

		return true;
	}

	/**
	 * Grab the values from the $_POST data and validate them.
	 *
	 * @return array Valid save data.
	 */
	protected function save_post_data() {
		$values = [];
		foreach ( $this->global_identifier_types as $key => $label ) {
			// Ignoring nonce verification as we do that elsewhere, sanitization as we do that below.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$value = isset( $_POST['yoast_seo'][ $key ] ) ? wp_unslash( $_POST['yoast_seo'][ $key ] ) : '';
			if ( $this->validate_data( $value ) ) {
				$values[ $key ] = $value;
			}
		}

		return $values;
	}

	/**
	 * Displays an input field for an identifier.
	 *
	 * @param string $type  Type of identifier, used for input name.
	 * @param string $label Label for the identifier input.
	 * @param string $value Current value of the identifier.
	 *
	 * @return void
	 */
	protected function input_field_for_identifier( $type, $label, $value ) {
		echo '<p class="form-field">';
		echo '<label for="yoast_identifier_', esc_attr( $type ), '">', esc_html( $label ), ':</label>';
		echo '<span class="wrap">';
		echo '<input class="input-text" type="text" id="yoast_identfier_', esc_attr( $type ), '" name="yoast_seo[', esc_attr( $type ), ']" value="', esc_attr( $value ), '"/>';
		echo '</span>';
		echo '</p>';
	}
}

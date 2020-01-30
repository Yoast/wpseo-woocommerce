<?php

namespace Yoast\WP\Woocommerce\Classes;

/**
 * Class Yoast_WooCommerce_Dependencies
 */
class Dependencies {

	/**
	 * Checks the dependencies. Sets a notice when requirements aren't met.
	 *
	 * @param string $wp_version The current version of WordPress.
	 *
	 * @return bool True when the dependencies are okay.
	 */
	public function check_dependencies( $wp_version ) {
		if ( ! version_compare( $wp_version, '5.2', '>=' ) ) {
			add_action( 'all_admin_notices', [ $this, 'wordpress_upgrade_error' ] );

			return false;
		}

		// When WooCommerce is not installed.
		if ( ! $this->check_woocommerce_exists() ) {
			add_action( 'all_admin_notices', [ $this, 'woocommerce_missing_error' ] );

			return false;
		}

		// When Yoast SEO is not installed.
		$wordpress_seo_version = $this->get_yoast_seo_version();
		if ( ! $wordpress_seo_version ) {
			add_action( 'all_admin_notices', [ $this, 'yoast_seo_missing_error' ] );

			return false;
		}

		// At least 12.6, in which we've implemented the new HelpScout Beacon.
		if ( ! version_compare( $wordpress_seo_version, '12.6-RC0', '>=' ) ) {
			add_action( 'all_admin_notices', [ $this, 'yoast_seo_upgrade_error' ] );

			return false;
		}

		return true;
	}

	/**
	 * Checks whether WooCommerce is available.
	 *
	 * @return bool True if WooCommerce is active, false if not.
	 */
	protected function check_woocommerce_exists() {
		return class_exists( 'woocommerce' );
	}

	/**
	 * Returns the WordPress SEO version when set.
	 *
	 * @return bool|string The version when it is set.
	 */
	protected function get_yoast_seo_version() {
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			return false;
		}

		return WPSEO_VERSION;
	}

	/**
	 * Throw an error if WooCommerce is not active.
	 */
	public function woocommerce_missing_error() {
		echo '<div class="error"><p>';
		printf(
			/* translators: %1$s resolves to the plugin search for Yoast SEO, %2$s resolves to the closing tag, %3$s resolves to WooCommerce, %4$s resolves to Yoast WooCommerce SEO */
			esc_html__( 'Please %1$sinstall &amp; activate %3$s%2$s to allow the %4$s module to work.', 'yoast-woo-seo' ),
			'<a href="' . esc_url( admin_url( 'plugin-install.php?tab=search&type=term&s=woocommerce&plugin-search-input=Search+Plugins' ) ) . '">',
			'</a>',
			'WooCommerce',
			'Yoast WooCommerce SEO'
		);
		echo '</p></div>';
	}

	/**
	 * Throw an error if WordPress SEO is not installed.
	 */
	public function yoast_seo_missing_error() {
		echo '<div class="error"><p>';
		printf(
			/* translators: %1$s resolves to the plugin search for Yoast SEO, %2$s resolves to the closing tag, %3$s resolves to Yoast SEO, %4$s resolves to Yoast WooCommerce SEO */
			esc_html__( 'Please %1$sinstall &amp; activate %3$s%2$s to allow the %4$s module to work.', 'yoast-woo-seo' ),
			'<a href="' . esc_url( admin_url( 'plugin-install.php?tab=search&type=term&s=yoast+seo&plugin-search-input=Search+Plugins' ) ) . '">',
			'</a>',
			'Yoast SEO',
			'Yoast WooCommerce SEO'
		);
		echo '</p></div>';
	}

	/**
	 * Throw an error if WordPress is out of date.
	 */
	public function wordpress_upgrade_error() {
		echo '<div class="error"><p>';
		printf(
			/* translators: %1$s resolves to Yoast WooCommerce SEO */
			esc_html__( 'Please upgrade WordPress to the latest version to allow WordPress and the %1$s module to work properly.', 'yoast-woo-seo' ),
			'Yoast WooCommerce SEO'
		);
		echo '</p></div>';
	}

	/**
	 * Throw an error if WordPress SEO is out of date.
	 */
	public function yoast_seo_upgrade_error() {
		echo '<div class="error"><p>';
		printf(
			/* translators: %1$s resolves to Yoast SEO, %2$s resolves to Yoast WooCommerce SEO */
			esc_html__( 'Please upgrade the %1$s plugin to the latest version to allow the %2$s module to work.', 'yoast-woo-seo' ),
			'Yoast SEO',
			'Yoast WooCommerce SEO'
		);
		echo '</p></div>';
	}
}

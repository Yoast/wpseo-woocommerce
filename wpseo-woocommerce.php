<?php
/*
Plugin Name: Yoast WooCommerce SEO
Version: 1.0.1
Plugin URI: http://yoast.com/wordpress/yoast-woocommerce-seo/
Description: This extenstion to WooCommerce and WordPress SEO by Yoast makes sure there's perfect communication between the two plugins.
Author: Joost de Valk
Author URI: http://yoast.com

Copyright 2013 Joost de Valk (email: joost@yoast.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class Yoast_WooCommerce_SEO {

	/**
	 * @var array $options
	 */
	var $options = false;

	/**
	 * @var string Name of the option to store plugins setting
	 */
	var $short_name = 'wpseo_woo';

	/**
	 * @var string Name of the plugin for updating from update host
	 */
	var $plugin_name = 'WooCommerce Yoast SEO';

	/**
	 * @var string Name of the plugin author for updating from update host
	 */
	var $plugin_author = 'Joost de Valk';

	/**
	 * @var string Update host
	 */
	var $update_host = 'http://yoast.com/';

	/**
	 * @var string Version of the plugin.
	 */
	var $version = '1.0.1';

	/**
	 * @var int Database version to check whether the plugins options need updating.
	 */
	var $dbversion = 1;

	/**
	 * @var bool
	 */
	var $license_active = false;

	/**
	 * Class constructor, basically hooks all the required functionality.
	 *
	 * @since 1.0
	 */
	function __construct() {
		// Initialize the options
		$this->options = get_option( $this->short_name );

		if ( isset( $this->options['license-status'] ) && $this->options['license-status'] == 'valid' )
			$this->license_active = true;

		if ( $this->license_active ) {
			// Initialize the defaults
			if ( ! isset( $this->options['dbversion'] ) )
				$this->initialize_defaults();

			// Check if the options need updating
			if ( $this->dbversion > $this->options['dbversion'] )
				$this->upgrade();
		}

		if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			$this->initialize_update_class();

			// Admin page
			add_action( 'admin_init', array( $this, 'options_init' ) );
			add_action( 'admin_menu', array( $this, 'register_settings_page' ), 20 );
			add_action( 'admin_print_styles', array( $this, 'config_page_styles' ) );

			// Product activation
			add_action( 'add_option_' . $this->short_name, array( $this, 'activate_license' ) );
			add_action( 'update_option_' . $this->short_name, array( $this, 'activate_license' ) );

			if ( $this->license_active ) {
				// Products tab columns
				if ( isset( $this->options['hide_columns'] ) && $this->options['hide_columns'] )
					add_filter( 'manage_product_posts_columns', array( $this, 'column_heading' ), 11, 1 );

				// Move Woo box above SEO box
				if ( isset( $this->options['metabox_woo_top'] ) && $this->options['metabox_woo_top'] )
					add_action( 'admin_footer', array( $this, 'footer_js' ) );

				add_filter( 'wpseo_body_length_score', array( $this, 'change_body_length_requirements' ), 10, 2 );
				add_filter( 'wpseo_linkdex_results', array( $this, 'add_woocommerce_specific_content_analysis_tests' ), 10, 3 );
				add_filter( 'wpseo_pre_analysis_post_content', array( $this, 'add_product_images_to_content' ), 10, 2 );
			}
		}
		else if ( $this->license_active ) {
			if ( function_exists( 'get_wpseo_options' ) )
				$wpseo_options = get_wpseo_options();
			else
				return;

			// OpenGraph
			add_filter( 'wpseo_opengraph_type', array( $this, 'return_type_product' ) );
			add_filter( 'wpseo_opengraph_desc', array( $this, 'og_desc_enhancement' ) );
			add_action( 'wpseo_opengraph', array( $this, 'og_enhancement' ) );

			// Twitter
			add_filter( 'wpseo_twitter_card_type', array( $this, 'return_type_product' ) );
			add_action( 'wpseo_twitter', array( $this, 'twitter_enhancement' ) );

			add_filter( 'wpseo_sitemap_exclude_post_type', array( $this, 'xml_sitemap_post_types' ), 10, 2 );
			add_filter( 'wpseo_sitemap_exclude_taxonomy', array( $this, 'xml_sitemap_taxonomies' ), 10, 2 );

			add_filter( 'woocommerce_attribute', array( $this, 'schema_filter' ), 10, 2 );

			if ( isset( $this->options['breadcrumbs'] )
					&& $this->options['breadcrumbs']
					&& isset( $wpseo_options['breadcrumbs-enable'] )
					&& $wpseo_options['breadcrumbs-enable']
					&& class_exists( 'WPSEO_Breadcrumbs' )
			) {
				remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );
				add_action( 'woocommerce_before_main_content', array( $this, 'woo_wpseo_breadcrumbs' ) );
			}

		}

	}

	/**
	 * Changes the body copy length requirements for products.
	 *
	 * @param array $lengthReqs The length requirements currently set.
	 * @param array $job        The analysis job array.
	 *
	 * @return array
	 */
	function change_body_length_requirements( $lengthReqs, $job ) {
		if ( $job['post_type'] == 'product' ) {
			return array(
				'good' => 200,
				'ok'   => 150,
				'poor' => 100,
				'bad'  => 75
			);
		}
		return $lengthReqs;
	}

	/**
	 * Check whether the current post is a product, if so, do some WooCommerce specific testing.
	 *
	 * @param array  $results The results for the content analysis.
	 * @param array  $job     The analysis job array.
	 * @param object $post    The post object for which we're running the analysis.
	 *
	 * @return mixed
	 */
	function add_woocommerce_specific_content_analysis_tests( $results, $job, $post ) {
		if ( $post->post_type == 'product' ) {
			$results = $this->test_short_description( $results, $post );
		}
		return $results;
	}

	/**
	 * Test whether the short description is actually of decent length.
	 *
	 * @param array  $results The results for the content analysis.
	 * @param object $post    The post object for which we're running the analysis.
	 *
	 * @return array
	 */
	function test_short_description( $results, $post ) {
		global $wpseo_metabox, $statistics;

		$word_count = $statistics->word_count( strip_tags( $post->post_excerpt ) );
		if ( $word_count == 0 ) {
			$wpseo_metabox->save_score_result( $results, 1, __( 'You should write a short description for this product.', 'yoast-woo-seo' ), 'woocommerce_shortdesc' );
		}
		else if ( $word_count < 20 ) {
			$wpseo_metabox->save_score_result( $results, 5, __( 'The short description for this product too short.', 'yoast-woo-seo' ), 'woocommerce_shortdesc' );
		}
		else {
			$wpseo_metabox->save_score_result( $results, 9, __( 'Your short description has a good length.', 'yoast-woo-seo' ), 'woocommerce_shortdesc' );
		}
		return $results;
	}

	/**
	 * Make sure the product images are used in calculating the score.
	 *
	 * @param string $content The content to modify
	 * @param object $post    The post object for which we're doing the analysis.
	 *
	 * @return string
	 */
	function add_product_images_to_content( $content, $post ) {
		if ( $post->post_type == 'product' ) {
			$args = array(
				'post_type'   => 'attachment',
				'numberposts' => - 1,
				'post_status' => 'inherit',
				'post_parent' => $post->ID
			);

			$attachments = get_posts( $args );
			if ( $attachments ) {
				foreach ( $attachments as $attachment ) {
					$content .= wp_get_attachment_image( $attachment->ID, 'thumbnail' ) . ' ';
				}
			}
		}
		return $content;
	}

	/**
	 * Initialize plugin update functionality
	 */
	function initialize_update_class() {

		if ( isset( $this->options['license'] ) && ! empty( $this->options['license'] ) ) {
			// Conditionally load the custom updater
			if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) )
				include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );

			global $edd_updater;
			$edd_updater = new EDD_SL_Plugin_Updater( $this->update_host, __FILE__,
				array(
					'version'   => $this->version, // current version number
					'license'   => trim( $this->options['license'] ), // license key
					'item_name' => $this->plugin_name, // name of this plugin
					'author'    => $this->plugin_author // author of this plugin
				)
			);
		}
	}

	/**
	 * Initialize the plugin defaults.
	 */
	function initialize_defaults() {
		$options = array(
			'dbversion'       => $this->dbversion,
			'license'         => $this->options['license'],
			'license-status'  => $this->options['license-status'],
			'data1_type'      => 'price',
			'data2_type'      => 'stock',
			'breadcrumbs'     => 'on',
			'hide_columns'    => 'on',
			'metabox_woo_top' => 'on'
		);
		update_option( $this->short_name, $options );
		$this->options = $options;
	}

	/**
	 * Perform upgrade procedures to the settings
	 */
	function upgrade() {
		// Nothing yet.
	}

	/**
	 * Registers the settings page in the WP SEO menu
	 *
	 * @since 1.0
	 */
	function register_settings_page() {
		add_submenu_page( 'wpseo_dashboard', 'WooCommerce SEO', 'WooCommerce SEO', 'manage_options', $this->short_name, array( $this, 'admin_panel' ) );
	}

	/**
	 * Loads some CSS
	 *
	 * @since 1.0
	 */
	function config_page_styles() {
		global $pagenow;
		if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] == 'wpseo_woo' ) {
			wp_enqueue_style( 'yoast-admin-css', WPSEO_URL . 'css/yst_plugin_tools.css', WPSEO_VERSION );
		}
	}

	/**
	 * Registers the plugins setting for the Settings API
	 *
	 * @since 1.0
	 */
	function options_init() {
		register_setting( $this->short_name . '_options', $this->short_name );
	}

	/**
	 * See if there's a license to activate
	 *
	 * @since 1.0
	 */
	function activate_license() {
		$this->options = get_option( $this->short_name );

		if ( ! isset( $this->options['license'] ) || empty( $this->options['license'] ) ) {
			if ( defined( 'WPSEO_WOO_LICENSE' ) ) {
				$this->options['license'] = WPSEO_WOO_LICENSE;
			}
		}
		if ( ( ! isset( $this->options['license'] ) || empty( $this->options['license'] ) ) && ! defined( 'WPSEO_WOO_LICENSE' ) ) {
			unset( $this->options['license'] );
			unset( $this->options['license-status'] );
			update_option( $this->short_name, $this->options );
			return;
		}

		if ( isset ( $this->options['license-status'] ) && 'valid' == $this->options['license-status'] ) {
			return;
		}
		else if ( isset( $this->options['license'] ) || defined( 'WPSEO_WOO_LICENSE' ) ) {
			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $this->options['license'],
				'item_name'  => urlencode( $this->plugin_name )
			);

			// Call the custom API.
			$url      = add_query_arg( $api_params, $this->update_host );
			$args     = array(
				'timeout' => 25,
				'rand'    => rand( 1000, 9999 )
			);
			$response = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) ) {
				return;
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "valid" or "invalid"
			$this->options['license-status'] = $license_data->license;
			update_option( $this->short_name, $this->options );
		}
	}

	/**
	 * Builds the admin page
	 *
	 * @since 1.0
	 */
	function admin_panel() {

		if ( isset( $_GET['deactivate'] ) && 'true' == $_GET['deactivate'] ) {

			if ( wp_verify_nonce( $_GET['nonce'], 'yoast_woo_seo_deactivate_license' ) === false )
				return;

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $this->options['license'],
				'item_name'  => $this->plugin_name
			);

			// Send the remote request
			$url = add_query_arg( $api_params, $this->update_host );

			$response = wp_remote_get( $url, array( 'timeout' => 25, 'sslverify' => false ) );

			if ( ! is_wp_error( $response ) ) {
				$response = json_decode( $response['body'] );

				if ( 'deactivated' == $response->license || 'failed' == $response->license ) {
					unset( $this->options['license'] );
					$this->options['license-status'] = 'invalid';
					update_option( $this->short_name, $this->options );
				}
			}

			echo '<script type="text/javascript">document.location = "' . admin_url( 'admin.php?page=' . $this->short_name ) . '"</script>';
		}
		?>
		<div class="wrap">

		<a href="http://yoast.com/wordpress/woocommerce-yoast-seo/">
			<div id="yoast-icon"
					 style="background: url('<?php echo WPSEO_URL; ?>images/wordpress-SEO-32x32.png') no-repeat;"
					 class="icon32"><br /></div>
		</a>

		<h2 id="wpseo-title"><?php _e( 'WooCommerce SEO Settings', 'yoast-woo-seo' ); ?></h2>

		<form action="<?php echo admin_url( 'options.php' ); ?>" method="post">

		<?php

		settings_fields( $this->short_name . '_options' );

		echo '<div style="max-width: 600px;">';
		echo '<h2>' . __( 'License', 'yoast-woo-seo' ) . '</h2>';
		echo '<label class="textinput" for="license">' . __( 'License Key', 'yoast-woo-seo' ) . ':</label> '
				. '<input id="license" class="textinput" type="text" name="' . $this->short_name . '[license]" value="'
				. ( isset( $this->options['license'] ) ? $this->options['license'] : '' ) . '"/><br/>';
		echo '<p class="clear description">' . __( 'License Status', 'yoast-woo-seo' ) . ': ' . ( ( $this->license_active ) ? '<span style="color:#090; font-weight:bold">' . __( 'active', 'yoast-woo-seo' ) . '</span>' : '<span style="color:#f00; font-weight:bold">' . __( 'inactive', 'yoast-woo-seo' ) . '</span>' ) . '</p>';
		echo '<input type="hidden" name="' . $this->short_name . '[license-status]" value="' . ( ( $this->license_active ) ? 'valid' : 'invalid' ) . '"/>';

		if ( $this->license_active ) {
			echo '<input type="hidden" name="' . $this->short_name . '[dbversion]" value="' . $this->options['dbversion'] . '"/>';
			echo '<p><a href="' . admin_url( 'admin.php?page=' . $this->short_name . '&deactivate=true&nonce=' . wp_create_nonce( 'yoast_woo_seo_deactivate_license' ) ) . '" class="button">' . __( 'Deactivate License', 'yoast-woo-seo' ) . '</a></p>';

			echo '<h2>' . __( 'Twitter Product Cards', 'yoast-woo-seo' ) . '</h2>';
			echo '<p>' . __( 'Twitter allows you to display two pieces of data in the Twitter card, pick which two are shown:', 'yoast-woo-seo' ) . '</p>';

			$i = 1;
			while ( $i < 3 ) {
				echo '<label class="select" for="datatype' . $i . '" class="checkbox">' . sprintf( __( 'Data %d', 'yoast-woo-seo' ), $i ) . ':</label> ';
				echo '<select class="select" id="datatype' . $i . '" name="' . $this->short_name . '[data' . $i . '_type]">';
				foreach ( array( 'Price', 'Stock' ) as $data_type ) {
					$sel = '';
					if ( strtolower( $data_type ) == $this->options['data' . $i . '_type'] )
						$sel = ' selected';
					echo '<option value="' . strtolower( $data_type ) . '"' . $sel . '>' . $data_type . '</option>';
				}
				foreach ( get_object_taxonomies( 'product', 'objects' ) as $tax ) {
					$sel = '';
					if ( strtolower( $tax->name ) == $this->options['data' . $i . '_type'] )
						$sel = ' selected';
					echo '<option value="' . strtolower( $tax->name ) . '"' . $sel . '>' . $tax->labels->name . '</option>';
				}
				echo '</select>';
				if ( $i == 1 )
					echo '<br class="clear"/>';
				$i ++;
			}

			echo '<br class="clear"/>';
			echo '<h2>' . __( 'Schema & OpenGraph additions', 'yoast-woo-seo' ) . '</h2>';
			echo '<p>' . __( 'If you have product attributes for the following types, select them here, the plugin will make sure they\'re used for the appropriate Schema.org and OpenGraph markup.', 'yoast-woo-seo' ) . '</p>';
			echo '<label class="select" for="schema_brand" class="checkbox">' . sprintf( __( 'Brand', 'yoast-woo-seo' ), $i ) . ':</label> ';
			echo '<select class="select" id="schema_brand" name="' . $this->short_name . '[schema_brand]">';
			echo '<option value="">-</option>';
			foreach ( get_object_taxonomies( 'product', 'objects' ) as $tax ) {
				$sel = '';
				if ( strtolower( $tax->name ) == $this->options['schema_brand'] )
					$sel = ' selected';
				echo '<option value="' . strtolower( $tax->name ) . '"' . $sel . '>' . $tax->labels->name . '</option>';
			}
			echo '</select>';
			echo '<br class="clear"/>';
			echo '<label class="select" for="schema_manufacturer" class="checkbox">' . sprintf( __( 'Manufacturer', 'yoast-woo-seo' ), $i ) . ':</label> ';
			echo '<select class="select" id="schema_manufacturer" name="' . $this->short_name . '[schema_manufacturer]">';
			echo '<option value="">-</option>';
			foreach ( get_object_taxonomies( 'product', 'objects' ) as $tax ) {
				$sel = '';
				if ( strtolower( $tax->name ) == $this->options['schema_manufacturer'] )
					$sel = ' selected';
				echo '<option value="' . strtolower( $tax->name ) . '"' . $sel . '>' . $tax->labels->name . '</option>';
			}
			echo '</select>';

			$wpseo_options = get_wpseo_options();
			if ( isset( $wpseo_options['breadcrumbs-enable'] ) && $wpseo_options['breadcrumbs-enable'] ) {
				echo '<h2>' . __( 'Breadcrumbs', 'yoast-woo-seo' ) . '</h2>';
				echo '<p>' . sprintf( __( 'Both WooCommerce and WordPress SEO by Yoast have breadcrumbs functionality. The WP SEO breadcrumbs have a slightly higher chance of being picked up by search engines and you can configure them a bit more, on the %1$sInternal Links settings page%2$s. To enable them, check the box below and the WooCommerce breadcrumbs will be replaced.', 'yoast-woo-seo' ), '<a href="' . admin_url( 'admin.php?page=wpseo_internal-links' ) . '">', '</a>' ) . '</p>';
				$this->checkbox( 'breadcrumbs', __( 'Replace WooCommerce Breadcrumbs', 'yoast-woo-seo' ) );
			}

			echo '<br class="clear"/>';
			echo '<h2>' . __( 'Admin', 'yoast-woo-seo' ) . '</h2>';
			echo '<p>' . __( 'Both WooCommerce and WordPress SEO by Yoast add columns to the product page, to remove all but the SEO score column from Yoast on that page, check this box.', 'yoast-woo-seo' ) . '</p>';
			$this->checkbox( 'hide_columns', __( 'Remove WordPress SEO columns', 'yoast-woo-seo' ) );

			echo '<br class="clear"/>';
			echo '<p>' . __( 'Both WooCommerce and WordPress SEO by Yoast add metaboxes to the edit product page, if you want WooCommerce to be above WordPress SEO, check the box.', 'yoast-woo-seo' ) . '</p>';
			$this->checkbox( 'metabox_woo_top', __( 'Move WooCommerce Up', 'yoast-woo-seo' ) );
		}

		echo '<br class="clear"/>';
		echo '<div class="submit"><input type="submit" class="button-primary" name="submit" value="' . __( "Save Settings", 'yoast-woo-seo' ) . '"/></div>';
		echo '</div>';
	}

	/**
	 * Simple helper function to show a checkbox.
	 *
	 * @param string $id    The ID and optionname for the checkbox
	 * @param string $label The label for the checkbox
	 */
	function checkbox( $id, $label ) {
		echo '<input class="checkbox" type="checkbox" id="breadcrumbs" name="' . $this->short_name . '[' . $id . ']" ' . ( isset( $this->options[$id] ) ? 'checked' : '' ) . '> ';
		echo '<label for="' . $id . '" class="checkbox">' . $label . '</label> ';
	}

	/**
	 * Adds a bit of JS that moves the meta box for WP SEO below the WooCommerce box.
	 *
	 * @since 1.0
	 */
	function footer_js() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				// Show WooCommerce box before WP SEO metabox
				if ($('#woocommerce-product-data').length > 0 && $('#wpseo_meta').length > 0) {
					$('#woocommerce-product-data').insertBefore($('#wpseo_meta'));
				}
			});
		</script>
	<?php
	}

	/**
	 * Clean up the columns in the edit products page.
	 *
	 * @since 1.0
	 *
	 * @param array $columns
	 *
	 * @return mixed
	 */
	function column_heading( $columns ) {
		unset( $columns['wpseo-title'], $columns['wpseo-metadesc'], $columns['wpseo-focuskw'] );

		return $columns;
	}

	/**
	 * Output WordPress SEO crafted breadcrumbs, instead of WooCommerce ones.
	 *
	 * @since 1.0
	 */
	function woo_wpseo_breadcrumbs() {
		global $wpseo_bc;
		$wpseo_bc->breadcrumb( '<nav class="woocommerce-breadcrumb">', '</nav>' );
	}

	/**
	 * Make sure product variations and shop coupons are not included in the XML sitemap
	 *
	 * @since 1.0
	 *
	 * @param bool   $bool    Whether or not to include this post type in the XML sitemap
	 * @param string $post_type
	 *
	 * @return bool
	 */
	function xml_sitemap_post_types( $bool, $post_type ) {
		if ( $post_type == 'product_variation' || $post_type == 'shop_coupon' )
			return true;
		return $bool;
	}

	/**
	 * Make sure product attribute taxonomies are not included in the XML sitemap
	 *
	 * @since 1.0
	 *
	 * @param bool   $bool    Whether or not to include this post type in the XML sitemap
	 * @param string $taxonomy
	 *
	 * @return bool
	 */
	function xml_sitemap_taxonomies( $bool, $taxonomy ) {
		if ( $taxonomy == 'product_type' || $taxonomy == 'product_shipping_class' || $taxonomy == 'shop_order_status' )
			return true;

		if ( substr( $taxonomy, 0, 3 ) == 'pa_' )
			return true;

		return $bool;
	}

	/**
	 * Adds the other product images to the OpenGraph output
	 *
	 * @since 1.0
	 */
	function og_enhancement() {
		if ( ! is_singular( 'product' ) || ! function_exists( 'get_product' ) )
			return;

		global $wpseo_og;

		$product = get_product( get_the_ID() );
		if ( ! is_object( $product ) ) {
			return;
		}

		$img_ids = $product->get_gallery_attachment_ids();

		if ( $img_ids ) {
			foreach ( $img_ids as $img_id ) {
				$img_url = wp_get_attachment_url( $img_id );
				$wpseo_og->image_output( $img_url );
			}
		}

		if ( isset( $this->options['schema_brand'] ) && $this->options['schema_brand'] ) {
			$terms = get_the_terms( get_the_ID(), $this->options['schema_brand'] );
			if ( is_array( $terms ) && count( $terms ) > 0 ) {
				$term = array_shift( array_values( $terms ) );
				echo "<meta property='og:brand' content='" . $term->name . "'/>\n";
			}
		}
		echo "<meta property='og:price:amount' content='" . $product->get_price() . "'/>\n";
		echo "<meta property='og:price:currency' content='" . get_woocommerce_currency() . "'/>\n";
		if ( $product->is_in_stock() )
			echo "<meta property='og:price:availability' content='instock'/>\n";
	}

	/**
	 * Make sure the OpenGraph description is put out.
	 *
	 * @since 1.0
	 *
	 * @param string $desc The current description, will be overwritten if we're on a product page.
	 *
	 * @return string
	 */
	function og_desc_enhancement( $desc ) {
		if ( is_singular( 'product' ) )
			return strip_tags( get_the_excerpt() );

		if ( is_product_taxonomy() ) {
			$term_desc = term_description();
			if ( ! empty( $term_desc ) )
				return trim( strip_tags( $term_desc ) );
		}

		return $desc;
	}

	/**
	 * Output the extra data for the Twitter Card
	 *
	 * @since 1.0
	 */
	function twitter_enhancement() {
		if ( ! is_singular( 'product' ) || ! function_exists( 'get_product' ) )
			return;

		$product = get_product( get_the_ID() );

		$product_atts = array(
			'domain' => get_bloginfo( 'site_name' )
		);

		$i = 1;
		while ( $i < 3 ) {
			switch ( $this->options['data' . $i . '_type'] ) {
				case 'stock':
					$product_atts['label' . $i] = __( 'In stock', 'woocommerce' );
					$product_atts['data' . $i]  = ( $product->is_in_stock() ) ? __( 'Yes' ) : __( 'No' );
					break;
				case 'category':
					$product_atts['label' . $i] = __( 'Category', 'woocommerce' );
					$product_atts['data' . $i]  = strip_tags( get_the_term_list( get_the_ID(), 'product_cat', '', ', ' ) );
					break;
				case 'price':
					$product_atts['label' . $i] = __( 'Price', 'woocommerce' );
					$product_atts['data' . $i]  = get_woocommerce_currency_symbol() . ' ' . $product->get_price();
					break;
				default:
					$tax                        = get_taxonomy( $this->options['data' . $i . '_type'] );
					$product_atts['label' . $i] = $tax->labels->name;
					$product_atts['data' . $i]  = strip_tags( get_the_term_list( get_the_ID(), $tax->name, '', ', ' ) );
					break;
			}
			$i ++;
		}

		foreach ( $product_atts as $label => $value ) {
			echo '<meta name="twitter:' . $label . '" content="' . $value . '"/>' . "\n";
		}
	}

	/**
	 * Return 'product' when current page is, well... a product.
	 *
	 * @since 1.0
	 *
	 * @param string $type Passed on without changing if not a product.
	 *
	 * @return string
	 */
	function return_type_product( $type ) {
		if ( is_singular( 'product' ) )
			return 'product';

		return $type;
	}

	/**
	 * Filter the output of attributes and add schema.org attributes where possible
	 *
	 * @since 1.0
	 *
	 * @param string $text      The text of the attribute.
	 * @param array  $attribute The array containing the attributes.
	 *
	 * @return string
	 */
	function schema_filter( $text, $attribute ) {
		if ( 1 == $attribute['is_taxonomy'] ) {
			if ( $this->options['schema_brand'] == $attribute['name'] )
				return str_replace( '<p', '<p itemprop="brand"', $text );
			if ( $this->options['schema_manufacturer'] == $attribute['name'] )
				return str_replace( '<p', '<p itemprop="manufacturer"', $text );
		}
		return $text;
	}
}

/**
 * Throw an error if WordPress SEO is not installed.
 *
 * @since 1.0.1
 */
function yoast_wpseo_woocommerce_missing_error() {
	echo '<div class="error"><p>' . sprintf( __( 'Please %sinstall &amp; activate WordPress SEO by Yoast%s and then enable its XML sitemap functionality to allow the Video SEO module to work.' ), '<a href="' . admin_url( 'plugin-install.php?tab=search&type=term&s=wordpress+seo&plugin-search-input=Search+Plugins' ) . '">', '</a>' ) . '</p></div>';
}

/**
 * Throw an error if WordPress SEO is not installed.
 *
 * @since 1.0.1
 */
function yoast_wpseo_woocommerce_wordpress_upgrade_error() {
	echo '<div class="error"><p>' . __( 'Please upgrade WordPress to the latest version to allow WordPress and the Video SEO module to work properly.', 'yoast-video-seo' ) . '</p></div>';
}

/**
 * Throw an error if WordPress SEO is not installed.
 *
 * @since 1.0.1
 */
function yoast_wpseo_woocommerce_upgrade_error() {
	echo '<div class="error"><p>' . __( 'Please upgrade the WordPress SEO plugin to the latest version to allow the Video SEO module to work.', 'yoast-video-seo' ) . '</p></div>';
}


/**
 * Initialize the plugin class, to make sure all the required functionality is loaded, do this after plugins_loaded.
 *
 * @since 1.0
 */
function initialize_yoast_woocommerce_seo() {
	global $yoast_woo_seo;
	global $wp_version;

	if ( ! version_compare( $wp_version, '3.5', '>=' ) ) {
		add_action( 'all_admin_notices', 'yoast_wpseo_woocommerce_wordpress_upgrade_error' );
	}
	else if ( defined( 'WPSEO_VERSION' ) ) {
		if ( version_compare( WPSEO_VERSION, '1.4.15', '>=' ) ) {
			$yoast_woo_seo = new Yoast_WooCommerce_SEO();
			$yoast_woo_seo->activate_license();
		}
		else {
			add_action( 'all_admin_notices', 'yoast_wpseo_woocommerce_upgrade_error' );
		}

	}
	else {
		add_action( 'all_admin_notices', 'yoast_wpseo_woocommerce_missing_error' );
	}
}

add_action( 'plugins_loaded', 'initialize_yoast_woocommerce_seo', 20 );

<?php
/**
 * Plugin Name: Yoast WooCommerce SEO
 * Version:     3.1.1
 * Plugin URI:  https://yoast.com/wordpress/plugins/yoast-woocommerce-seo/
 * Description: This extension to WooCommerce and WordPress SEO by Yoast makes sure there's perfect communication between the two plugins.
 * Author:      Team Yoast
 * Author URI:  https://yoast.com
 * Depends:     WordPress SEO, WooCommerce
 * Text Domain: yoast-woo-seo
 * Domain Path: /languages/
 *
 * Copyright 2014 Yoast BV (email: supportyoast.com)
 */

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload_52.php' ) ) {
	require dirname( __FILE__ ) . '/vendor/autoload_52.php';
}

class Yoast_WooCommerce_SEO {

	/**
	 * @const string Version of the plugin.
	 */
	const VERSION = '3.1.1';

	/**
	 * @var object $option_instance Instance of the WooCommerce_SEO option management class
	 */
	var $option_instance;

	/**
	 * @var array $options
	 */
	var $options = array();

	/**
	 * @var string Name of the option to store plugins setting
	 */
	var $short_name;

	/**
	 * @var Yoast_Plugin_License_Manager
	 */
	private $license_manager;

	/**
	 * Return the plugin file
	 *
	 * @return string
	 */
	public static function get_plugin_file() {
		return __FILE__;
	}

	/**
	 * Class constructor, basically hooks all the required functionality.
	 *
	 * @since 1.0
	 */
	function __construct() {

		// Initialize the options
		$this->option_instance = WPSEO_Option_Woo::get_instance();
		$this->short_name      = $this->option_instance->option_name;
		$this->options         = get_option( $this->short_name );

		// Make sure the options property is always current
		add_action( 'add_option_' . $this->short_name, array( $this, 'refresh_options_property' ) );
		add_action( 'update_option_' . $this->short_name, array( $this, 'refresh_options_property' ) );

		// Load License Manager class (on admin req only)
		$this->license_manager = $this->load_license_manager();

		// Check if the options need updating
		if ( $this->option_instance->db_version > $this->options['dbversion'] ) {
			$this->upgrade();
		}

		if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			// Admin page
			add_action( 'admin_menu', array( $this, 'register_settings_page' ), 20 );
			add_action( 'admin_print_styles', array( $this, 'config_page_styles' ) );
			add_action( 'wpseo_licenses_forms', array( $this->license_manager, 'show_license_form' ) );


			// Products tab columns
			if ( $this->options['hide_columns'] === true ) {
				add_filter( 'manage_product_posts_columns', array( $this, 'column_heading' ), 11, 1 );
			}

			// Move Woo box above SEO box
			if ( $this->options['metabox_woo_top'] === true ) {
				add_action( 'admin_footer', array( $this, 'footer_js' ) );
			}
		} else {
			if ( class_exists( 'WooCommerce', false ) ) {
				$wpseo_options = WPSEO_Options::get_all();

				// Add metadescription filter
				add_filter( 'wpseo_metadesc', array( $this, 'metadesc' ) );

				// OpenGraph
				add_filter( 'wpseo_opengraph_desc', array( $this, 'og_desc_enhancement' ) );
				add_action( 'wpseo_opengraph', array( $this, 'og_enhancement' ), 50 );

				add_filter( 'wpseo_sitemap_exclude_post_type', array( $this, 'xml_sitemap_post_types' ), 10, 2 );
				add_filter( 'wpseo_sitemap_post_type_archive_link', array( $this, 'xml_sitemap_taxonomies' ), 10, 2 );

				add_filter( 'post_type_archive_link', array( $this, 'xml_post_type_archive_link' ), 10, 2 );
				add_filter( 'wpseo_sitemap_urlimages', array( $this, 'add_product_images_to_xml_sitemap' ), 10, 2 );

				add_filter( 'woocommerce_attribute', array( $this, 'schema_filter' ), 10, 2 );

				// Fix breadcrumbs
				if ( $this->options['breadcrumbs'] === true && $wpseo_options['breadcrumbs-enable'] === true ) {
					add_filter( 'woo_breadcrumbs', array( $this, 'override_woo_breadcrumbs' ) );
					add_filter( 'wpseo_breadcrumb_links', array( $this, 'add_attribute_to_breadcrumbs' ) );
				}
			}
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'admin_init', array( $this, 'init_beacon' ) );
	}


	/**
	 * Overrides the Woo breadcrumb functionality when the WP SEO breadcrumb functionality is enabled
	 *
	 * @uses  woo_breadcrumbs filter
	 *
	 * @since 1.1.3
	 *
	 * @return string
	 */
	public function override_woo_breadcrumbs() {
		return yoast_breadcrumb( '<div class="breadcrumb breadcrumbs woo-breadcrumbs"><div class="breadcrumb-trail">', '</div></div>', false );
	}

	/**
	 * Add the selected attribute to the breadcrumb
	 *
	 * @param $crumbs
	 *
	 * @return array
	 */
	public function add_attribute_to_breadcrumbs( $crumbs ) {
		global $_chosen_attributes;

		// Copy the array
		$yoast_chosen_attributes = $_chosen_attributes;

		// Check if the attribute filter is used
		if ( is_array( $yoast_chosen_attributes ) && count( $yoast_chosen_attributes ) > 0 ) {
			// Store keys
			$att_keys = array_keys( $yoast_chosen_attributes );

			// We got an attribute filter, get the first Attribute
			$att_group = array_shift( $yoast_chosen_attributes );

			if ( is_array( $att_group['terms'] ) && count( $att_group['terms'] ) > 0 ) {

				// Get the attribute ID
				$att = array_shift( $att_group['terms'] );

				// Get the term
				$term = get_term( (int) $att, array_shift( $att_keys ) );

				if ( is_object( $term ) ) {
					$crumbs[] = array( 'term' => $term );
				}
			}
		}

		return $crumbs;
	}

	/**
	 * Loads the License Manager class
	 * Takes care of remote license (de)activation and plugin updates.
	 *
	 * @return Yoast_Plugin_License_Manager
	 */
	private function load_license_manager() {

		// we only need this on admin pages
		// we don't need this in AJAX requests
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		$license_manager = new Yoast_Plugin_License_Manager(
			new Yoast_Product_WPSEO_WooCommerce()
		);

		$license_manager->setup_hooks();

		return $license_manager;
	}

	/**
	 * Refresh the options property on add/update of the option to ensure it's always current
	 */
	function refresh_options_property() {
		$this->options = get_option( $this->short_name );
	}

	/**
	 * Add the product gallery images to the XML sitemap
	 *
	 * @param array $images  The array of images for the post
	 * @param int   $post_id The ID of the post object
	 *
	 * @return array
	 */
	function add_product_images_to_xml_sitemap( $images, $post_id ) {
		if ( metadata_exists( 'post', $post_id, '_product_image_gallery' ) ) {
			$product_image_gallery = get_post_meta( $post_id, '_product_image_gallery', true );

			$attachments = array_filter( explode( ',', $product_image_gallery ) );

			foreach ( $attachments as $attachment_id ) {
				$image_src = wp_get_attachment_image_src( $attachment_id );
				$image     = array(
					'src'   => apply_filters( 'wpseo_xml_sitemap_img_src', $image_src[0], $post_id ),
					'title' => get_the_title( $attachment_id ),
					'alt'   => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				);
				$images[] = $image;

				unset( $image, $image_src );
			}
		}

		return $images;
	}

	/**
	 * Perform upgrade procedures to the settings
	 */
	function upgrade() {

		// upgrade license options
		if ( $this->license_manager && $this->license_manager->license_is_valid() == false ) {

			if ( isset( $this->options['license-status'] ) ) {
				$this->license_manager->set_license_status( $this->options['license-status'] );
			}

			if ( isset( $this->options['license'] ) ) {
				$this->license_manager->set_license_key( $this->options['license'] );
			}
		}

		// upgrade to new wp seo option class
		$this->option_instance->clean();
	}

	/**
	 * Registers the settings page in the WP SEO menu
	 *
	 * @since 1.0
	 */
	public function register_settings_page() {
		add_submenu_page( 'wpseo_dashboard', __( 'WooCommerce SEO Settings', 'yoast-woo-seo' ), __( 'WooCommerce SEO', 'yoast-woo-seo' ), 'manage_options', $this->short_name, array(
			$this,
			'admin_panel',
		) );
	}

	/**
	 * Loads some CSS
	 *
	 * @since 1.0
	 */
	function config_page_styles() {
		global $pagenow;
		if ( $pagenow == 'admin.php' && ( isset( $_GET['page'] ) && $_GET['page'] === 'wpseo_woo' ) && ( defined( 'WPSEO_PATH' ) && defined( 'WPSEO_CSSJS_SUFFIX' ) && defined( 'WPSEO_VERSION' ) ) ) {
			wp_enqueue_style( 'yoast-admin-css', plugins_url( 'css/yst_plugin_tools' . WPSEO_CSSJS_SUFFIX . '.css', WPSEO_PATH . 'dummy.txt' ), array(), WPSEO_VERSION );
		}
	}

	/**
	 * Builds the admin page
	 *
	 * @since 1.0
	 */
	function admin_panel() {

		WPSEO_WooCommerce_Wrappers::admin_header( true, $this->option_instance->group_name, $this->short_name, false );

		// @todo [JRF => whomever] change the form fields so they use the methods as defined in WPSEO_Admin_Pages

		$taxonomies = get_object_taxonomies( 'product', 'objects' );

		echo '<h2>' . __( 'Schema & OpenGraph additions', 'yoast-woo-seo' ) . '</h2>
		<p>' . __( 'If you have product attributes for the following types, select them here, the plugin will make sure they\'re used for the appropriate Schema.org and OpenGraph markup.', 'yoast-woo-seo' ) . '</p>
		<label class="select" for="schema_brand">' . __( 'Brand', 'yoast-woo-seo' ) . ':</label>
		<select class="select" id="schema_brand" name="' . esc_attr( $this->short_name . '[schema_brand]' ) . '">
			<option value="">-</option>' . "\n";
		if ( is_array( $taxonomies ) && $taxonomies !== array() ) {
			foreach ( $taxonomies as $tax ) {
				$sel = selected( strtolower( $tax->name ), $this->options['schema_brand'], false );
				echo '<option value="' . esc_attr( strtolower( $tax->name ) ) . '"' . $sel . '>' . esc_html( $tax->labels->name ) . "</option>\n";
			}
		}
		unset( $tax, $sel );
		echo '
		</select>
		<br class="clear"/>

		<label class="select" for="schema_manufacturer">' . sprintf( __( 'Manufacturer', 'yoast-woo-seo' ), $i ) . ':</label>
		<select class="select" id="schema_manufacturer" name="' . esc_attr( $this->short_name . '[schema_manufacturer]' ) . '">
			<option value="">-</option>' . "\n";
		if ( is_array( $taxonomies ) && $taxonomies !== array() ) {
			foreach ( $taxonomies as $tax ) {
				$sel = selected( strtolower( $tax->name ), $this->options['schema_manufacturer'], false );
				echo '<option value="' . esc_attr( strtolower( $tax->name ) ) . '"' . $sel . '>' . esc_html( $tax->labels->name ) . "</option>\n";
			}
		}
		unset( $tax, $sel );
		echo '
		</select>
		<br class="clear"/>';

		$wpseo_options = WPSEO_Options::get_all();
		if ( $wpseo_options['breadcrumbs-enable'] === true ) {
			echo '
		<h2>' . __( 'Breadcrumbs', 'yoast-woo-seo' ) . '</h2>
		<p>' . sprintf( __( 'Both WooCommerce and WordPress SEO by Yoast have breadcrumbs functionality. The WP SEO breadcrumbs have a slightly higher chance of being picked up by search engines and you can configure them a bit more, on the %1$sInternal Links settings page%2$s. To enable them, check the box below and the WooCommerce breadcrumbs will be replaced.', 'yoast-woo-seo' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wpseo_internal-links' ) ) . '">', '</a>' ) . "</p>\n";
			$this->checkbox( 'breadcrumbs', __( 'Replace WooCommerce Breadcrumbs', 'yoast-woo-seo' ) );
		}

		echo '
		<br class="clear"/>
		<h2>' . __( 'Admin', 'yoast-woo-seo' ) . '</h2>
		<p>' . __( 'Both WooCommerce and WordPress SEO by Yoast add columns to the product page, to remove all but the SEO score column from Yoast on that page, check this box.', 'yoast-woo-seo' ) . "</p>\n";
		$this->checkbox( 'hide_columns', __( 'Remove WordPress SEO columns', 'yoast-woo-seo' ) );

		echo '
		<br class="clear"/>
		<p>' . __( 'Both WooCommerce and WordPress SEO by Yoast add metaboxes to the edit product page, if you want WooCommerce to be above WordPress SEO, check the box.', 'yoast-woo-seo' ) . "</p>\n";
		$this->checkbox( 'metabox_woo_top', __( 'Move WooCommerce Up', 'yoast-woo-seo' ) );

		echo '<br class="clear"/>';

		// Submit button and debug info
		WPSEO_WooCommerce_Wrappers::admin_footer( true, false );
	}

	/**
	 * Simple helper function to show a checkbox.
	 *
	 * @param string $id    The ID and option name for the checkbox
	 * @param string $label The label for the checkbox
	 */
	function checkbox( $id, $label ) {
		$current = false;
		if ( isset( $this->options[ $id ] ) && $this->options[ $id ] === true ) {
			$current = 'on';
		}

		echo '<input class="checkbox" type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $this->short_name . '[' . $id . ']' ) . '" value="on" ' . checked( $current, 'on', false ) . '> ';
		echo '<label for="' . esc_attr( $id ) . '" class="checkbox">' . $label . '</label> ';
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
		yoast_breadcrumb( '<nav class="woocommerce-breadcrumb">', '</nav>' );
	}

	/**
	 * Make sure product variations and shop coupons are not included in the XML sitemap
	 *
	 * @since 1.0
	 *
	 * @param bool   $bool Whether or not to include this post type in the XML sitemap
	 * @param string $post_type
	 *
	 * @return bool
	 */
	function xml_sitemap_post_types( $bool, $post_type ) {
		if ( $post_type === 'product_variation' || $post_type === 'shop_coupon' ) {
			return true;
		}

		return $bool;
	}

	/**
	 * Make sure product attribute taxonomies are not included in the XML sitemap
	 *
	 * @since 1.0
	 *
	 * @param bool   $bool Whether or not to include this post type in the XML sitemap
	 * @param string $taxonomy
	 *
	 * @return bool
	 */
	function xml_sitemap_taxonomies( $bool, $taxonomy ) {
		if ( $taxonomy === 'product_type' || $taxonomy === 'product_shipping_class' || $taxonomy === 'shop_order_status' ) {
			return true;
		}

		if ( substr( $taxonomy, 0, 3 ) === 'pa_' ) {
			return true;
		}

		return $bool;
	}

	/**
	 * Adds the other product images to the OpenGraph output
	 *
	 * @since 1.0
	 */
	function og_enhancement() {
		global $wpseo_og;

		if ( is_product_category() || ! function_exists( 'is_product_category' ) ) {
			global $wp_query;
			$cat          = $wp_query->get_queried_object();
			$thumbnail_id = get_woocommerce_term_meta( $cat->term_id, 'thumbnail_id', true );
			$img_url      = wp_get_attachment_url( $thumbnail_id );
			if ( $img_url ) {
				$wpseo_og->image_output( $img_url );
			}
		}

		if ( ! is_singular( 'product' ) || ! function_exists( 'get_product' ) ) {
			return;
		}

		$product = get_product( get_the_ID() );
		if ( ! is_object( $product ) ) {
			return;
		}

		$img_ids = $product->get_gallery_attachment_ids();

		if ( is_array( $img_ids ) && $img_ids !== array() ) {
			foreach ( $img_ids as $img_id ) {
				$img_url = wp_get_attachment_url( $img_id );
				$wpseo_og->image_output( $img_url );
			}
		}

		if ( $this->options['schema_brand'] !== '' ) {
			$terms = get_the_terms( get_the_ID(), $this->options['schema_brand'] );
			if ( is_array( $terms ) && count( $terms ) > 0 ) {
				$term_values = array_values( $terms );
				$term        = array_shift( $term_values );
				echo '<meta property="og:brand" content="' . esc_attr( $term->name ) . "\"/>\n";
			}
		}
		/**
		 * Filter: wpseo_woocommerce_og_price - Allow developers to prevent the output of the price in the OpenGraph tags
		 *
		 * @api bool unsigned Defaults to true.
		 */
		if ( apply_filters( 'wpseo_woocommerce_og_price', true ) ) {
			echo '<meta property="og:price:amount" content="' . esc_attr( $product->get_price() ) . "\"/>\n";
			echo '<meta property="og:price:currency" content="' . esc_attr( get_woocommerce_currency() ) . "\"/>\n";
		}

		if ( $product->is_in_stock() ) {
			echo '<meta property="og:price:availability" content="instock"/>' . "\n";
		}
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

		if ( is_product_taxonomy() ) {

			$term_desc = term_description();

			if ( ! empty( $term_desc ) ) {
				$desc = trim( strip_tags( $term_desc ) );
				$desc = strip_shortcodes( $desc );
			}
		}

		return $desc;
	}

	/**
	 * If metadesc is empty check which value we should use
	 *
	 * On empty metadesc it will check for post_excerpt, otherwise it will use the full product description. If all empty
	 * just return value $metadesc
	 *
	 * @param string $metadesc
	 *
	 * @return string
	 */
	public function metadesc( $metadesc ) {

		if ( $metadesc == '' ) {
			if ( is_singular( 'product' ) && function_exists( 'get_product' ) ) {
				$product = get_product( get_the_ID() );
				if ( is_object( $product ) ) {

					if ( $product->post->post_excerpt != '' ) {
						$metadesc = $product->post->post_excerpt;
					} elseif ( $product->post->post_content != '' ) {
						$metadesc = $product->post->post_content;
					}

					if ( ! empty( $metadesc ) ) {
						$metadesc = wp_html_excerpt( $metadesc, 156 );
					}
				}
			}
		}

		return $metadesc;
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
			if ( $this->options['schema_brand'] === $attribute['name'] ) {
				return str_replace( '<p', '<p itemprop="brand"', $text );
			}
			if ( $this->options['schema_manufacturer'] === $attribute['name'] ) {
				return str_replace( '<p', '<p itemprop="manufacturer"', $text );
			}
		}

		return $text;
	}

	/**
	 * Filters the archive link on the product sitemap
	 *
	 * @param string $link
	 * @param string $post_type
	 *
	 * @return bool
	 */
	function xml_post_type_archive_link( $link, $post_type ) {

		if ( $post_type === 'product' ) {
			return false;
		} else {
			return $link;
		}
	}

	public function init_beacon() {
		$query_var = ( $page = filter_input( INPUT_GET, 'page' ) ) ? $page : '';

		// Only add the helpscout beacon on Yoast SEO pages.
		if ( substr( $query_var, 0, 5 ) === 'wpseo' ) {
			$beacon = yoast_get_helpscout_beacon( $query_var );
			$beacon->add_setting( new WPSEO_WooCommerce_Beacon_Setting() );
			$beacon->register_hooks();
		}
	}


	/********************** DEPRECATED METHODS **********************/

	/**
	 * Initialize the plugin defaults.
	 *
	 * @deprecated 1.1.0 - now auto-handled by class WPSEO_Option_Woo
	 */
	function initialize_defaults() {
		_deprecated_function( __CLASS__ . '::' . __METHOD__, 'WooCommerce SEO 1.1.0', null );
	}

	/**
	 * Registers the plugins setting for the Settings API
	 *
	 * @since      1.0
	 * @deprecated 1.1.0 - now auto-handled by class WPSEO_Option_Woo
	 */
	function options_init() {
		_deprecated_function( __CLASS__ . '::' . __METHOD__, 'WooCommerce SEO 1.1.0', null );
	}

	/**
	 * Keep old behaviour of getting the twitter domain in a different way than in WPSEO, but prevent duplicate
	 * twitter:domain meta tags
	 *
	 * @deprecated 3.1
	 * @param string $domain
	 *
	 * @return  string
	 */
	public function filter_twitter_domain( $domain ) {
		_deprecated_function( __CLASS__ . '::' . __METHOD__, 'WooCommerce SEO 3.1', null );
		return '';
	}

	/**
	 * Output the extra data for the Twitter Card
	 *
	 * @deprecated 3.1
	 * @since 1.0
	 */
	public function twitter_enhancement() {
		_deprecated_function( __CLASS__ . '::' . __METHOD__, 'WooCommerce SEO 3.1', null );
	}

	/**
	 * Return 'product' when current page is, well... a product.
	 *
	 * @deprecated 3.1
	 * @since 1.0
	 *
	 * @param string $type Passed on without changing if not a product.
	 *
	 * @return string
	 */
	public function return_type_product( $type ) {
		_deprecated_function( __CLASS__ . '::' . __METHOD__, 'WooCommerce SEO 3.1', null );
		return $type;
	}

	/**
	 * Enqueues the pluginscripts.
	 */
	public function enqueue_scripts() {
		// Only do this on product pages.
		if ( 'product' !== get_post_type( ) ) {
			return;
		}

		wp_enqueue_script( 'wp-seo-woo', plugins_url( 'js/yoastseo-woo-plugin' . WPSEO_CSSJS_SUFFIX . '.js', __FILE__ ), array(), WPSEO_VERSION, true );

		wp_localize_script( 'wp-seo-woo', 'wpseoWooL10n', $this->localize_woo_script() );
	}

	/**
	 * Localizes scripts for the wooplugin.
	 * @return array
	 */
	private function localize_woo_script() {
		return array(
			'woo_desc_none'    => __( 'You should write a short description for this product.', 'yoast-woo-seo' ),
			'woo_desc_short'   => __( 'The short description for this product is too short.', 'yoast-woo-seo' ),
			'woo_desc_good'    => __( 'Your short description has a good length.', 'yoast-woo-seo' ),
			'woo_desc_long'    => __( 'The short description for this product is too long.', 'yoast-woo-seo' ),
		);
	}
}


/**
 * Throw an error if WordPress SEO is not installed.
 *
 * @since 1.0.1
 */
function yoast_wpseo_woocommerce_missing_error() {
	echo '<div class="error"><p>' . sprintf( __( 'Please %sinstall &amp; activate WordPress SEO by Yoast%s and then enable its XML sitemap functionality to allow the WooCommerce SEO module to work.', 'yoast-woo-seo' ), '<a href="' . esc_url( admin_url( 'plugin-install.php?tab=search&type=term&s=wordpress+seo&plugin-search-input=Search+Plugins' ) ) . '">', '</a>' ) . '</p></div>';
}

/**
 * Throw an error if WordPress is out of date.
 *
 * @since 1.0.1
 */
function yoast_wpseo_woocommerce_wordpress_upgrade_error() {
	echo '<div class="error"><p>' . __( 'Please upgrade WordPress to the latest version to allow WordPress and the WooCommerce SEO module to work properly.', 'yoast-woo-seo' ) . '</p></div>';
}

/**
 * Throw an error if WordPress SEO is out of date.
 *
 * @since 1.0.1
 */
function yoast_wpseo_woocommerce_upgrade_error() {
	echo '<div class="error"><p>' . __( 'Please upgrade the WordPress SEO plugin to the latest version to allow the WooCommerce SEO module to work.', 'yoast-woo-seo' ) . '</p></div>';
}


/**
 * Initialize the plugin class, to make sure all the required functionality is loaded, do this after plugins_loaded.
 *
 * @since 1.0
 */
function initialize_yoast_woocommerce_seo() {
	global $yoast_woo_seo;
	global $wp_version;

	load_plugin_textdomain( 'yoast-woo-seo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	if ( ! version_compare( $wp_version, '3.5', '>=' ) ) {
		add_action( 'all_admin_notices', 'yoast_wpseo_woocommerce_wordpress_upgrade_error' );
	} else if ( defined( 'WPSEO_VERSION' ) ) {
		if ( version_compare( WPSEO_VERSION, '1.5', '>=' ) ) {
			$yoast_woo_seo = new Yoast_WooCommerce_SEO();
		} else {
			add_action( 'all_admin_notices', 'yoast_wpseo_woocommerce_upgrade_error' );
		}
	} else {
		add_action( 'all_admin_notices', 'yoast_wpseo_woocommerce_missing_error' );
	}
}

if ( ! function_exists( 'wp_installing' ) ) {
	/**
	 * We need to define wp_installing in WordPress versions older than 4.4
	 *
	 * @return bool
	 */
	function wp_installing() {
		return defined( 'WP_INSTALLING' );
	}
}

/**
 * Instantiate the plugin license manager for the current plugin and activate it's license.
 */
function yoast_woocommerce_seo_activate_license() {
	if ( class_exists( 'Yoast_Plugin_License_Manager' ) ) {
		if ( ! class_exists( 'Yoast_Product_WPSEO_WooCommerce' ) ) {
			require_once( dirname( __FILE__ ) . '/class-product-wpseo-woocommerce.php' );
		}

		$license_manager = new Yoast_Plugin_License_Manager( new Yoast_Product_WPSEO_WooCommerce() );
		$license_manager->activate_license();
	}
}


if ( ! wp_installing() ) {
	add_action( 'plugins_loaded', 'initialize_yoast_woocommerce_seo', 20 );


	/*
	 * When the plugin is deactivated and activated again, the license have to be activated. This is mostly the case
	 * during a update of the plugin. To solve this, we hook into the activation process by calling a method that will
	 * activate the license.
	 */
	register_activation_hook( __FILE__, 'yoast_woocommerce_seo_activate_license' );


}

class WPSEO_WooCommerce_Wrappers {

	/**
	 * Fallback for admin_header
	 *
	 * @param bool   $form
	 * @param string $option_long_name
	 * @param string $option
	 * @param bool   $contains_files
	 *
	 * @return mixed
	 */
	public static function admin_header( $form = true, $option_long_name = 'yoast_wpseo_options', $option = 'wpseo', $contains_files = false ) {

		if ( method_exists( 'Yoast_Form', 'admin_header' ) ) {
			Yoast_Form::get_instance()->admin_header( $form, $option, $contains_files, $option_long_name );

			return;
		}

		return self::admin_pages()->admin_header( true, $option_long_name, $option );
	}

	/**
	 * Fallback for admin_footer
	 *
	 * @param bool $submit
	 * @param bool $show_sidebar
	 *
	 * @return mixed
	 */
	public static function admin_footer( $submit = true, $show_sidebar = true ) {

		if ( method_exists( 'Yoast_Form', 'admin_footer' ) ) {
			Yoast_Form::get_instance()->admin_footer( $submit, $show_sidebar );

			return;
		}

		return self::admin_pages()->admin_footer( $submit, $show_sidebar );
	}

	/**
	 * Returns the wpseo_admin pages global variable
	 *
	 * @return mixed
	 */
	private static function admin_pages() {
		global $wpseo_admin_pages;

		if ( ! $wpseo_admin_pages instanceof WPSEO_Admin_Pages ) {
			$wpseo_admin_pages = new WPSEO_Admin_Pages;
		}

		return $wpseo_admin_pages;
	}

	/**
	 * Returns the result of validate bool from WPSEO_Utils if this class exists, otherwise it will return the result from
	 * validate_bool from WPSEO_Option_Woo
	 *
	 * @param $bool_to_validate
	 *
	 * @return bool
	 */
	public static function validate_bool( $bool_to_validate ) {
		if ( class_exists( 'WPSEO_Utils' ) &&  method_exists( 'WPSEO_Utils', 'validate_bool' ) ) {
			return WPSEO_Utils::validate_bool( $bool_to_validate );
		}

		return WPSEO_Option_Woo::validate_bool( $bool_to_validate );
	}
}

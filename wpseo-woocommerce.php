<?php
/**
 * WooCommerce Yoast SEO plugin.
 *
 * @package WPSEO/WooCommerce
 *
 * @wordpress-plugin
 * Plugin Name: Yoast SEO: WooCommerce
 * Version:     12.2
 * Plugin URI:  https://yoast.com/wordpress/plugins/yoast-woocommerce-seo/
 * Description: This extension to WooCommerce and Yoast SEO makes sure there's perfect communication between the two plugins.
 * Author:      Team Yoast
 * Author URI:  https://yoast.com
 * Depends:     Yoast SEO, WooCommerce
 * Text Domain: yoast-woo-seo
 * Domain Path: /languages/
 *
 * WC requires at least: 3.0
 * WC tested up to: 3.8
 *
 * Copyright 2014-2019 Yoast BV (email: support@yoast.com)
 */

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload_52.php' ) ) {
	require dirname( __FILE__ ) . '/vendor/autoload_52.php';
}

/**
 * Class Yoast_WooCommerce_SEO
 */
class Yoast_WooCommerce_SEO {

	/**
	 * Version of the plugin.
	 *
	 * @var string
	 */
	const VERSION = '12.2';

	/**
	 * Instance of the WooCommerce_SEO option management class.
	 *
	 * @var object
	 */
	public $option_instance;

	/**
	 * Cache of the current value of the WooCommerce_SEO option.
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Name of the option to store plugins setting.
	 *
	 * @var string
	 */
	public $short_name;

	/**
	 * Return the plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file() {
		return __FILE__;
	}

	/**
	 * Class constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		global $wp_version;

		if ( $this->check_dependencies( $wp_version ) ) {
			$this->initialize();
		}
	}

	/**
	 * Checks the dependencies. Sets a notice when requirements aren't met.
	 *
	 * @param string $wp_version The current version of WordPress.
	 *
	 * @return bool True whether the dependencies are okay.
	 */
	protected function check_dependencies( $wp_version ) {
		if ( ! version_compare( $wp_version, '4.9', '>=' ) ) {
			add_action( 'all_admin_notices', 'yoast_wpseo_woocommerce_wordpress_upgrade_error' );

			return false;
		}

		$wordpress_seo_version = $this->get_wordpress_seo_version();

		// When WordPress SEO is not installed.
		if ( ! $wordpress_seo_version ) {
			add_action( 'all_admin_notices', 'yoast_wpseo_woocommerce_missing_error' );

			return false;
		}

		// At least 10.2, in which we've introduced the new WPSEO_Schema_IDs functionality.
		if ( ! version_compare( $wordpress_seo_version, '10.2-rc0', '>=' ) ) {
			add_action( 'all_admin_notices', 'yoast_wpseo_woocommerce_upgrade_error' );

			return false;
		}

		return true;
	}

	/**
	 * Returns the WordPress SEO version when set.
	 *
	 * @return bool|string The version whether it is set.
	 */
	protected function get_wordpress_seo_version() {
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			return false;
		}

		return WPSEO_VERSION;
	}

	/**
	 * Initializes the plugin, basically hooks all the required functionality.
	 *
	 * @since 7.0
	 *
	 * @return void
	 */
	protected function initialize() {
		if ( $this->is_woocommerce_page( filter_input( INPUT_GET, 'page' ) ) ) {
			$this->register_i18n_promo_class();
		}

		// Initialize the options.
		$this->option_instance = WPSEO_Option_Woo::get_instance();
		$this->short_name      = $this->option_instance->option_name;
		$this->options         = get_option( $this->short_name );

		// Make sure the options property is always current.
		add_action( 'add_option_' . $this->short_name, array( $this, 'refresh_options_property' ) );
		add_action( 'update_option_' . $this->short_name, array( $this, 'refresh_options_property' ) );

		// Check if the options need updating.
		if ( $this->option_instance->db_version > $this->options['dbversion'] ) {
			$this->upgrade();
		}

		if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			// Add subitem to menu.
			add_filter( 'wpseo_submenu_pages', array( $this, 'add_submenu_pages' ) );
			add_action( 'admin_print_styles', array( $this, 'config_page_styles' ) );

			// Products tab columns.
			if ( $this->options['hide_columns'] === true ) {
				add_filter( 'manage_product_posts_columns', array( $this, 'column_heading' ), 11, 1 );
			}

			// Move Woo box above SEO box.
			if ( $this->options['metabox_woo_top'] === true ) {
				add_action( 'admin_footer', array( $this, 'footer_js' ) );
			}
		}
		else {
			$wpseo_options = WPSEO_Options::get_all();

			// Initialize schema.
			add_action( 'init', array( $this, 'initialize_schema' ) );

			// Add metadescription filter.
			add_filter( 'wpseo_metadesc', array( $this, 'metadesc' ) );

			// OpenGraph.
			add_filter( 'language_attributes', array( $this, 'og_product_namespace' ), 11 );
			add_filter( 'wpseo_opengraph_type', array( $this, 'return_type_product' ) );
			add_filter( 'wpseo_opengraph_desc', array( $this, 'og_desc_enhancement' ) );
			add_action( 'wpseo_opengraph', array( $this, 'og_enhancement' ), 50 );
			add_action( 'wpseo_register_extra_replacements', array( $this, 'register_replacements' ) );

			if ( class_exists( 'WPSEO_OpenGraph_Image' ) ) {
				add_action( 'wpseo_add_opengraph_additional_images', array( $this, 'set_opengraph_image' ) );
			}

			add_filter( 'wpseo_sitemap_exclude_post_type', array( $this, 'xml_sitemap_post_types' ), 10, 2 );
			add_filter( 'wpseo_sitemap_post_type_archive_link', array( $this, 'xml_sitemap_taxonomies' ), 10, 2 );
			add_filter( 'wpseo_sitemap_page_for_post_type_archive', array( $this, 'xml_post_type_archive_page_id' ), 10, 2 );

			add_filter( 'post_type_archive_link', array( $this, 'xml_post_type_archive_link' ), 10, 2 );
			add_filter( 'wpseo_sitemap_urlimages', array( $this, 'add_product_images_to_xml_sitemap' ), 10, 2 );

			// Fix breadcrumbs.
			if ( $this->options['breadcrumbs'] === true && $wpseo_options['breadcrumbs-enable'] === true ) {
				$this->handle_breadcrumbs_replacements();
			}
		} // End if.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Make sure the primary category will be used in the permalink.
		add_filter( 'wc_product_post_type_link_product_cat', array( $this, 'add_primary_category_permalink' ), 10, 3 );

		// Adds recommended replacevars.
		add_filter( 'wpseo_recommended_replace_vars', array( $this, 'add_recommended_replacevars' ) );

		// Only initialize beacon when the License Manager is present.
		if ( class_exists( 'Yoast_Plugin_License_Manager' ) ) {
			add_action( 'admin_init', array( $this, 'init_beacon' ) );
		}

		add_filter( 'wpseo_sitemap_entry', array( $this, 'filter_hidden_product' ), 10, 3 );
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', array( $this, 'filter_woocommerce_pages' ) );
	}

	/**
	 * Initializes the schema functionality.
	 */
	public function initialize_schema() {
		if ( WPSEO_WooCommerce_Schema::should_output_yoast_schema() ) {
			new WPSEO_WooCommerce_Schema();
		}
	}

	/**
	 * Prevents a hidden product from being added to the sitemap.
	 *
	 * @param array   $url  The url data.
	 * @param string  $type The object type.
	 * @param WP_Post $post The post object.
	 *
	 * @return bool|array False when entry is hidden.
	 */
	public function filter_hidden_product( $url, $type, $post ) {
		if ( empty( $url['loc'] ) ) {
			return $url;
		}

		if ( ! is_object( $post ) || ! property_exists( $post, 'post_type' ) ) {
			return $url;
		}

		if ( $post->post_type !== 'product' ) {
			return $url;
		}

		$excluded_from_catalog = $this->excluded_from_catalog();
		if ( in_array( $post->ID, $excluded_from_catalog, true ) ) {
			return false;
		}

		return $url;
	}

	/**
	 * Retrieves the products that are excluded from the catalog.
	 *
	 * @return array Excluded product ids.
	 */
	protected function excluded_from_catalog() {
		static $excluded_from_catalog;

		if ( $excluded_from_catalog === null ) {
			$query                 = new WP_Query(
				array(
					'fields'         => 'ids',
					'posts_per_page' => '-1',
					'post_type'      => 'product',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'tax_query'      => array(
						array(
							'taxonomy' => 'product_visibility',
							'field'    => 'name',
							'terms'    => array( 'exclude-from-catalog' ),
						),
					),
				)
			);
			$excluded_from_catalog = $query->get_posts();
		}

		return $excluded_from_catalog;
	}

	/**
	 * Adds the page ids from the WooCommerce core pages to the excluded post ids.
	 *
	 * @param array $excluded_posts_ids The excluded post ids.
	 *
	 * @return array The post ids with the added page ids.
	 */
	public function filter_woocommerce_pages( $excluded_posts_ids ) {
		$woocommerce_pages   = array();
		$woocommerce_pages[] = wc_get_page_id( 'cart' );
		$woocommerce_pages[] = wc_get_page_id( 'checkout' );
		$woocommerce_pages[] = wc_get_page_id( 'myaccount' );
		$woocommerce_pages   = array_filter( $woocommerce_pages );

		return array_merge( $excluded_posts_ids, $woocommerce_pages );
	}

	/**
	 * Adds the recommended WooCommerce replacevars to Yoast SEO.
	 *
	 * @param array $replacevars Array with replacevars.
	 *
	 * @return array Array with the added replacevars.
	 */
	public function add_recommended_replacevars( $replacevars ) {
		if ( ! class_exists( 'WooCommerce', false ) ) {
			return $replacevars;
		}

		$replacevars['product']                = array( 'sitename', 'title', 'sep', 'primary_category' );
		$replacevars['product_cat']            = array( 'sitename', 'term_title', 'sep' );
		$replacevars['product_tag']            = array( 'sitename', 'term_title', 'sep' );
		$replacevars['product_shipping_class'] = array( 'sitename', 'term_title', 'sep', 'page' );
		$replacevars['product_brand']          = array( 'sitename', 'term_title', 'sep' );
		$replacevars['pwb-brand']              = array( 'sitename', 'term_title', 'sep' );
		$replacevars['product_archive']        = array( 'sitename', 'sep', 'page', 'pt_plural' );

		return $replacevars;
	}

	/**
	 * Makes sure the primary category is used in the permalink.
	 *
	 * @param WP_Term   $term  The first found term belonging to the post.
	 * @param WP_Term[] $terms Array with all the terms belonging to the post.
	 * @param WP_Post   $post  The current open post.
	 *
	 * @return WP_Term
	 */
	public function add_primary_category_permalink( $term, $terms, $post ) {
		$primary_term    = new WPSEO_Primary_Term( 'product_cat', $post->ID );
		$primary_term_id = $primary_term->get_primary_term();

		if ( $primary_term_id ) {
			return get_term( $primary_term_id, 'product_cat' );
		}

		return $term;
	}

	/**
	 * Overrides the Woo breadcrumb functionality when the WP SEO breadcrumb functionality is enabled.
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
	 * Shows the Yoast SEO breadcrumbs.
	 *
	 * @return void
	 */
	public function show_yoast_breadcrumbs() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- We need to output HTML. If we escape this we break it.
		echo $this->override_woo_breadcrumbs();
	}

	/**
	 * Add the selected attribute to the breadcrumb.
	 *
	 * @param array $crumbs Existing breadcrumbs.
	 *
	 * @return array
	 */
	public function add_attribute_to_breadcrumbs( $crumbs ) {
		global $_chosen_attributes;

		// Copy the array.
		$yoast_chosen_attributes = $_chosen_attributes;

		// Check if the attribute filter is used.
		if ( is_array( $yoast_chosen_attributes ) && count( $yoast_chosen_attributes ) > 0 ) {
			// Store keys.
			$att_keys = array_keys( $yoast_chosen_attributes );

			// We got an attribute filter, get the first Attribute.
			$att_group = array_shift( $yoast_chosen_attributes );

			if ( is_array( $att_group['terms'] ) && count( $att_group['terms'] ) > 0 ) {

				// Get the attribute ID.
				$att = array_shift( $att_group['terms'] );

				// Get the term.
				$term = get_term( (int) $att, array_shift( $att_keys ) );

				if ( is_object( $term ) ) {
					$crumbs[] = array(
						'term' => $term,
					);
				}
			}
		}

		return $crumbs;
	}

	/**
	 * Refresh the options property on add/update of the option to ensure it's always current.
	 */
	public function refresh_options_property() {
		$this->options = get_option( $this->short_name );
	}

	/**
	 * Add the product gallery images to the XML sitemap.
	 *
	 * @param array $images  The array of images for the post.
	 * @param int   $post_id The ID of the post object.
	 *
	 * @return array
	 */
	public function add_product_images_to_xml_sitemap( $images, $post_id ) {
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
				$images[]  = $image;

				unset( $image, $image_src );
			}
		}

		return $images;
	}

	/**
	 * Perform upgrade procedures to the settings.
	 */
	public function upgrade() {
		// Upgrade to new wp seo option class.
		$this->option_instance->clean();
	}

	/**
	 * Registers the settings page in the WP SEO menu.
	 *
	 * @since 5.6
	 *
	 * @param array $submenu_pages List of current submenus.
	 *
	 * @return array All submenu pages including our own.
	 */
	public function add_submenu_pages( $submenu_pages ) {
		$submenu_pages[] = array(
			'wpseo_dashboard',
			sprintf(
				/* translators: %1$s resolves to WooCommerce SEO */
				esc_html__( '%1$s Settings', 'yoast-woo-seo' ),
				'WooCommerce SEO'
			),
			'WooCommerce SEO',
			'wpseo_manage_options',
			$this->short_name,
			array( $this, 'admin_panel' ),
		);

		return $submenu_pages;
	}

	/**
	 * Loads CSS.
	 *
	 * @since 1.0
	 */
	public function config_page_styles() {
		global $pagenow;

		$is_wpseo_woocommerce_page = ( $pagenow === 'admin.php' && filter_input( INPUT_GET, 'page' ) === 'wpseo_woo' );
		if ( ! $is_wpseo_woocommerce_page ) {
			return;
		}

		if ( ! class_exists( 'WPSEO_Admin_Asset_Manager' ) ) {
			return;
		}

		$asset_manager = new WPSEO_Admin_Asset_Manager();
		$asset_manager->enqueue_style( 'admin-css' );
	}

	/**
	 * Builds the admin page.
	 *
	 * @since 1.0
	 */
	public function admin_panel() {
		WPSEO_WooCommerce_Wrappers::admin_header( true, $this->option_instance->group_name, $this->short_name, false );

		// @todo [JRF => whomever] change the form fields so they use the methods as defined in WPSEO_Admin_Pages.
		$taxonomies = get_object_taxonomies( 'product', 'objects' );

		echo '<h2>' . esc_html__( 'Schema & OpenGraph additions', 'yoast-woo-seo' ) . '</h2>
		<p>' . esc_html__( 'If you have product attributes for the following types, select them here, the plugin will make sure they\'re used for the appropriate Schema.org and OpenGraph markup.', 'yoast-woo-seo' ) . '</p>
		<label class="select" for="schema_brand">' . esc_html__( 'Brand', 'yoast-woo-seo' ) . '</label>
		<select class="select" id="schema_brand" name="' . esc_attr( $this->short_name . '[schema_brand]' ) . '">
			<option value="">-</option>' . "\n";
		if ( is_array( $taxonomies ) && $taxonomies !== array() ) {
			foreach ( $taxonomies as $tax ) {
				echo '<option value="' . esc_attr( strtolower( $tax->name ) ) . '"'
					. selected( strtolower( $tax->name ), $this->options['schema_brand'], false ) . '>'
					. esc_html( $tax->labels->name ) . "</option>\n";
			}
		}
		unset( $tax, $sel );
		echo '
		</select>
		<br class="clear"/>

		<label class="select" for="schema_manufacturer">' . esc_html__( 'Manufacturer', 'yoast-woo-seo' ) . '</label>
		<select class="select" id="schema_manufacturer" name="' . esc_attr( $this->short_name . '[schema_manufacturer]' ) . '">
			<option value="">-</option>' . "\n";
		if ( is_array( $taxonomies ) && $taxonomies !== array() ) {
			foreach ( $taxonomies as $tax ) {
				echo '<option value="' . esc_attr( strtolower( $tax->name ) ) . '"'
					. selected( strtolower( $tax->name ), $this->options['schema_manufacturer'], false ) . '>'
					. esc_html( $tax->labels->name ) . "</option>\n";
			}
		}
		unset( $tax, $sel );
		echo '
		</select>
		<br class="clear"/>';

		$wpseo_options = WPSEO_Options::get_all();
		if ( $wpseo_options['breadcrumbs-enable'] === true ) {
			echo '<h2>' . esc_html__( 'Breadcrumbs', 'yoast-woo-seo' ) . '</h2>';
			echo '<p>';
			printf(
				/* translators: %1$s resolves to internal links options page, %2$s resolves to closing link tag, %3$s resolves to Yoast SEO, %4$s resolves to WooCommerce */
				esc_html__( 'Both %4$s and %3$s have breadcrumbs functionality. The %3$s breadcrumbs have a slightly higher chance of being picked up by search engines and you can configure them a bit more, on the %1$sBreadcrumbs settings page%2$s. To enable them, check the box below and the WooCommerce breadcrumbs will be replaced.', 'yoast-woo-seo' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=wpseo_titles#top#breadcrumbs' ) ) . '">',
				'</a>',
				'Yoast SEO',
				'WooCommerce'
			);
			echo "</p>\n";
			$this->checkbox(
				'breadcrumbs',
				sprintf(
					/* translators: %1$s resolves to WooCommerce */
					__( 'Replace %1$s Breadcrumbs', 'yoast-woo-seo' ),
					'WooCommerce'
				)
			);
		}

		echo '<br class="clear"/>';
		echo '<h2>' . esc_html__( 'Admin', 'yoast-woo-seo' ) . '</h2>';
		echo '<p>';
		printf(
			/* translators: %1$s resolves to Yoast SEO, %2$s resolves to WooCommerce */
			esc_html__( 'Both %2$s and %1$s add columns to the product page, to remove all but the SEO score column from %1$s on that page, check this box.', 'yoast-woo-seo' ),
			'Yoast SEO',
			'WooCommerce'
		);
		echo "</p>\n";
		$this->checkbox(
			'hide_columns',
			sprintf(
				/* translators: %1$s resolves to Yoast SEO */
				__( 'Remove %1$s columns', 'yoast-woo-seo' ),
				'Yoast SEO'
			)
		);

		echo '<br class="clear"/>';
		echo '<p>';
		printf(
			/* translators: %1$s resolves to Yoast SEO, %2$s resolves to WooCommerce */
			esc_html__( 'Both %2$s and %1$s add metaboxes to the edit product page, if you want %2$s to be above %1$s, check the box.', 'yoast-woo-seo' ),
			'Yoast SEO',
			'WooCommerce'
		);
		echo "</p>\n";
		$this->checkbox(
			'metabox_woo_top',
			sprintf(
				/* translators: %1$s resolves to WooCommerce */
				__( 'Move %1$s up', 'yoast-woo-seo' ),
				'WooCommerce'
			)
		);

		echo '<br class="clear"/>';

		// Submit button and debug info.
		WPSEO_WooCommerce_Wrappers::admin_footer( true, false );
	}

	/**
	 * Simple helper function to show a checkbox.
	 *
	 * @param string $id    The ID and option name for the checkbox.
	 * @param string $label The label for the checkbox.
	 */
	public function checkbox( $id, $label ) {
		$current = false;
		if ( isset( $this->options[ $id ] ) && $this->options[ $id ] === true ) {
			$current = 'on';
		}

		echo '<input class="checkbox" type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $this->short_name . '[' . $id . ']' ) . '" value="on" ' . checked( $current, 'on', false ) . '> ';
		echo '<label for="' . esc_attr( $id ) . '" class="checkbox">' . esc_html( $label ) . '</label> ';
	}

	/**
	 * Adds a bit of JS that moves the meta box for WP SEO below the WooCommerce box.
	 *
	 * @since 1.0
	 */
	public function footer_js() {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				// Show WooCommerce box before WP SEO metabox.
				if ( $( '#woocommerce-product-data' ).length > 0 && $( '#wpseo_meta' ).length > 0 ) {
					$( '#woocommerce-product-data' ).insertBefore( $( '#wpseo_meta' ) );
				}
			} );
		</script>
		<?php
	}

	/**
	 * Removes the Yoast SEO columns in the edit products page.
	 *
	 * @since 1.0
	 *
	 * @param array $columns List of registered columns.
	 *
	 * @return array Array with the filtered columns.
	 */
	public function column_heading( $columns ) {
		$keys_to_remove = array( 'wpseo-title', 'wpseo-metadesc', 'wpseo-focuskw', 'wpseo-score', 'wpseo-score-readability' );

		if ( class_exists( 'WPSEO_Link_Columns' ) ) {
			$keys_to_remove[] = 'wpseo-' . WPSEO_Link_Columns::COLUMN_LINKS;
			$keys_to_remove[] = 'wpseo-' . WPSEO_Link_Columns::COLUMN_LINKED;
		}

		foreach ( $keys_to_remove as $key_to_remove ) {
			unset( $columns[ $key_to_remove ] );
		}

		return $columns;
	}

	/**
	 * Output WordPress SEO crafted breadcrumbs, instead of WooCommerce ones.
	 *
	 * @since 1.0
	 */
	public function woo_wpseo_breadcrumbs() {
		yoast_breadcrumb( '<nav class="woocommerce-breadcrumb">', '</nav>' );
	}

	/**
	 * Make sure product variations and shop coupons are not included in the XML sitemap.
	 *
	 * @since 1.0
	 *
	 * @param bool   $bool      Whether or not to include this post type in the XML sitemap.
	 * @param string $post_type The post type of the post.
	 *
	 * @return bool
	 */
	public function xml_sitemap_post_types( $bool, $post_type ) {
		if ( $post_type === 'product_variation' || $post_type === 'shop_coupon' ) {
			return true;
		}

		return $bool;
	}

	/**
	 * Make sure product attribute taxonomies are not included in the XML sitemap.
	 *
	 * @since 1.0
	 *
	 * @param bool   $bool     Whether or not to include this post type in the XML sitemap.
	 * @param string $taxonomy The taxonomy to check against.
	 *
	 * @return bool
	 */
	public function xml_sitemap_taxonomies( $bool, $taxonomy ) {
		if ( $taxonomy === 'product_type' || $taxonomy === 'product_shipping_class' || $taxonomy === 'shop_order_status' ) {
			return true;
		}

		if ( substr( $taxonomy, 0, 3 ) === 'pa_' ) {
			return true;
		}

		return $bool;
	}

	/**
	 * Filter for the namespace, adding the OpenGraph namespace.
	 *
	 * @link https://developers.facebook.com/docs/reference/opengraph/object-type/product/
	 *
	 * @param string $input The input namespace string.
	 *
	 * @return string
	 */
	public function og_product_namespace( $input ) {
		if ( is_singular( 'product' ) ) {
			$input = preg_replace( '/prefix="([^"]+)"/', 'prefix="$1 product: http://ogp.me/ns/product#"', $input );
		}

		return $input;
	}

	/**
	 * Adds the opengraph images.
	 *
	 * @since 4.3
	 *
	 * @param WPSEO_OpenGraph_Image $opengraph_image The OpenGraph image to use.
	 */
	public function set_opengraph_image( WPSEO_OpenGraph_Image $opengraph_image ) {

		if ( ! function_exists( 'is_product_category' ) || is_product_category() ) {
			global $wp_query;
			$cat          = $wp_query->get_queried_object();
			$thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
			$img_url      = wp_get_attachment_url( $thumbnail_id );
			if ( $img_url ) {
				$opengraph_image->add_image( $img_url );
			}
		}

		$product = $this->get_product();
		if ( ! is_object( $product ) ) {
			return;
		}

		$img_ids = $this->get_image_ids( $product );

		if ( is_array( $img_ids ) && $img_ids !== array() ) {
			foreach ( $img_ids as $img_id ) {
				$img_url = wp_get_attachment_url( $img_id );
				$opengraph_image->add_image( $img_url );
			}
		}
	}

	/**
	 * Adds the other product images to the OpenGraph output.
	 *
	 * @since 1.0
	 */
	public function og_enhancement() {
		$product = $this->get_product();
		if ( ! is_object( $product ) ) {
			return;
		}

		if ( $this->options['schema_brand'] !== '' ) {
			$terms = get_the_terms( get_the_ID(), $this->options['schema_brand'] );
			if ( is_array( $terms ) && count( $terms ) > 0 ) {
				$term_values = array_values( $terms );
				$term        = array_shift( $term_values );
				echo '<meta property="product:brand" content="' . esc_attr( $term->name ) . '"/>' . "\n";
			}
		}
		/**
		 * Filter: wpseo_woocommerce_og_price - Allow developers to prevent the output of the price in the OpenGraph tags.
		 *
		 * @api bool unsigned Defaults to true.
		 */
		if ( apply_filters( 'wpseo_woocommerce_og_price', true ) ) {
			echo '<meta property="product:price:amount" content="' . esc_attr( $product->get_price() ) . '"/>' . "\n";
			echo '<meta property="product:price:currency" content="' . esc_attr( get_woocommerce_currency() ) . '"/>' . "\n";
		}

		if ( $product->is_in_stock() ) {
			echo '<meta property="product:availability" content="instock"/>' . "\n";
		}
	}

	/**
	 * Returns the product object when the current page is the product page.
	 *
	 * @since 4.3
	 *
	 * @return null|WC_Product
	 */
	private function get_product() {
		if ( ! is_singular( 'product' ) || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$product = wc_get_product( get_queried_object_id() );

		return $product;
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
	public function og_desc_enhancement( $desc ) {

		if ( is_product_taxonomy() ) {

			$term_desc = term_description();

			if ( ! empty( $term_desc ) ) {
				$desc = wp_strip_all_tags( $term_desc, true );
				$desc = strip_shortcodes( $desc );
			}
		}

		return $desc;
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
	public function return_type_product( $type ) {
		if ( is_singular( 'product' ) ) {
			return 'product';
		}

		return $type;
	}

	/**
	 * Returns the meta description. Checks which value should be used when the given meta description is empty.
	 *
	 * It will use the short_description if that one is set. Otherwise it will use the full
	 * product description limited to 156 characters. If everything is empty, it will return an empty string.
	 *
	 * @param string $meta_description The meta description to check.
	 *
	 * @return string The meta description.
	 */
	public function metadesc( $meta_description ) {

		if ( $meta_description !== '' ) {
			return $meta_description;
		}

		if ( ! is_singular( 'product' ) ) {
			return '';
		}

		$product = $this->get_product_for_id( get_the_id() );

		if ( ! is_object( $product ) ) {
			return '';
		}

		$short_description = $this->get_short_product_description( $product );
		$long_description  = $this->get_product_description( $product );

		if ( $short_description !== '' ) {
			return $short_description;
		}

		if ( $long_description !== '' ) {
			return wp_html_excerpt( $long_description, 156 );
		}

		return '';
	}

	/**
	 * Checks if product class has a short description method. Otherwise it returns the value of the post_excerpt from
	 * the post attribute.
	 *
	 * @since 4.9
	 *
	 * @param WC_Product $product The product.
	 *
	 * @return string
	 */
	protected function get_short_product_description( $product ) {
		if ( method_exists( $product, 'get_short_description' ) ) {
			return $product->get_short_description();
		}
		return $product->post->post_excerpt;
	}

	/**
	 * Checks if product class has a description method. Otherwise it returns the value of the post_content.
	 *
	 * @since 4.9
	 *
	 * @param WC_Product $product The product.
	 *
	 * @return string
	 */
	protected function get_product_description( $product ) {
		if ( method_exists( $product, 'get_description' ) ) {
			return $product->get_description();
		}

		return $product->post->post_content;
	}

	/**
	 * Checks if product class has a short description method. Otherwise it returns the value of the post_excerpt from
	 * the post attribute.
	 *
	 * @param WC_Product $product The product.
	 *
	 * @return string
	 */
	protected function get_product_short_description( $product = null ) {
		if ( is_null( $product ) ) {
			$product = $this->get_product();
		}

		if ( method_exists( $product, 'get_short_description' ) ) {
			return $product->get_short_description();
		}

		return $product->post->post_excerpt;
	}

	/**
	 * Filters the archive link on the product sitemap.
	 *
	 * @param string $link      The archive link.
	 * @param string $post_type The post type to check against.
	 *
	 * @return bool
	 */
	public function xml_post_type_archive_link( $link, $post_type ) {

		if ( 'product' !== $post_type ) {
			return $link;
		}

		if ( function_exists( 'wc_get_page_id' ) ) {
			$shop_page_id = wc_get_page_id( 'shop' );
			$home_page_id = (int) get_option( 'page_on_front' );
			if ( $home_page_id === $shop_page_id ) {
				return false;
			}
		}

		return $link;
	}

	/**
	 * Returns the ID of the WooCommerce shop page when product's archive is requested.
	 *
	 * @param int    $page_id   The page id.
	 * @param string $post_type The post type to check against.
	 *
	 * @return int
	 */
	public function xml_post_type_archive_page_id( $page_id, $post_type ) {

		if ( 'product' === $post_type && function_exists( 'wc_get_page_id' ) ) {
			$page_id = wc_get_page_id( 'shop' );
		}

		return $page_id;
	}

	/**
	 * Initialize the Yoast SEO WooCommerce helpscout beacon.
	 */
	public function init_beacon() {
		$page      = filter_input( INPUT_GET, 'page' );
		$query_var = ( ! empty( $page ) ) ? $page : '';

		// Only add the helpscout beacon on Yoast SEO pages.
		if ( $query_var === 'wpseo_woo' ) {
			$beacon = yoast_get_helpscout_beacon( $query_var );
			$beacon->add_setting( new WPSEO_WooCommerce_Beacon_Setting() );
			$beacon->register_hooks();
		}
	}

	/**
	 * Checks if the current page is a woocommerce seo plugin page.
	 *
	 * @param string $page Page to check against.
	 *
	 * @return bool
	 */
	protected function is_woocommerce_page( $page ) {
		$woo_pages = array( 'wpseo_woo' );

		return in_array( $page, $woo_pages, true );
	}

	/**
	 * Enqueues the pluginscripts.
	 */
	public function enqueue_scripts() {
		// Only do this on product pages.
		if ( 'product' !== get_post_type() ) {
			return;
		}

		$asset_manager = new WPSEO_Admin_Asset_Manager();
		$version       = $asset_manager->flatten_version( self::VERSION );

		wp_enqueue_script( 'wp-seo-woo', plugins_url( 'js/yoastseo-woo-plugin-' . $version . WPSEO_CSSJS_SUFFIX . '.js', __FILE__ ), array(), WPSEO_VERSION, true );
		wp_enqueue_script( 'wp-seo-woo-replacevars', plugins_url( 'js/yoastseo-woo-replacevars-' . $version . WPSEO_CSSJS_SUFFIX . '.js', __FILE__ ), array(), WPSEO_VERSION, true );

		wp_localize_script( 'wp-seo-woo', 'wpseoWooL10n', $this->localize_woo_script() );
		wp_localize_script( 'wp-seo-woo-replacevars', 'wpseoWooReplaceVarsL10n', $this->localize_woo_replacevars_script() );
	}

	/**
	 * Registers variable replacements for WooCommerce products.
	 */
	public function register_replacements() {
		wpseo_register_var_replacement(
			'wc_price',
			array( $this, 'get_product_var_price' ),
			'basic',
			'The product\'s price.'
		);

		wpseo_register_var_replacement(
			'wc_sku',
			array( $this, 'get_product_var_sku' ),
			'basic',
			'The product\'s SKU.'
		);

		wpseo_register_var_replacement(
			'wc_shortdesc',
			array( $this, 'get_product_var_short_description' ),
			'basic',
			'The product\'s short description.'
		);

		wpseo_register_var_replacement(
			'wc_brand',
			array( $this, 'get_product_var_brand' ),
			'basic',
			'The product\'s brand.'
		);
	}

	/**
	 * Register the promotion class for our GlotPress instance.
	 *
	 * @link https://github.com/Yoast/i18n-module
	 */
	protected function register_i18n_promo_class() {
		new Yoast_I18n_v3(
			array(
				'textdomain'     => 'yoast-woo-seo',
				'project_slug'   => 'woocommerce-seo',
				'plugin_name'    => 'Yoast WooCommerce SEO',
				'hook'           => 'wpseo_admin_promo_footer',
				'glotpress_url'  => 'http://translate.yoast.com/gp/',
				'glotpress_name' => 'Yoast Translate',
				'glotpress_logo' => 'http://translate.yoast.com/gp-templates/images/Yoast_Translate.svg',
				'register_url'   => 'http://translate.yoast.com/gp/projects#utm_source=plugin&utm_medium=promo-box&utm_campaign=wpseo-woo-i18n-promo',
			)
		);
	}

	/**
	 * Returns the set image ids for the given product.
	 *
	 * @since 4.9
	 *
	 * @param WC_Product $product The product to get the image ids for.
	 *
	 * @return array
	 */
	protected function get_image_ids( $product ) {
		if ( method_exists( $product, 'get_gallery_image_ids' ) ) {
			return $product->get_gallery_image_ids();
		}

		// Backwards compatibility.
		return $product->get_gallery_attachment_ids();
	}

	/**
	 * Returns the product for given product_id.
	 *
	 * @since 4.9
	 *
	 * @param integer $product_id The id to get the product for.
	 *
	 * @return null|WC_Product
	 */
	protected function get_product_for_id( $product_id ) {
		if ( function_exists( 'wc_get_product' ) ) {
			return wc_get_product( $product_id );
		}

		if ( function_exists( 'get_product' ) ) {
			return get_product( $product_id );
		}

		return null;
	}

	/**
	 * Retrieves the product price.
	 *
	 * @since 5.9
	 *
	 * @return string
	 */
	public function get_product_var_price() {
		$product = $this->get_product();
		if ( ! is_object( $product ) ) {
			return '';
		}

		if ( method_exists( $product, 'get_price' ) ) {
			return wp_strip_all_tags( wc_price( $product->get_price() ), true );
		}

		return '';
	}

	/**
	 * Retrieves the product short description.
	 *
	 * @since 5.9
	 *
	 * @return string
	 */
	public function get_product_var_short_description() {
		return $this->get_product_short_description();
	}

	/**
	 * Retrieves the product SKU.
	 *
	 * @since 5.9
	 *
	 * @return string
	 */
	public function get_product_var_sku() {
		$product = $this->get_product();
		if ( ! is_object( $product ) ) {
			return '';
		}

		if ( method_exists( $product, 'get_sku' ) ) {
			return $product->get_sku();
		}

		return '';
	}

	/**
	 * Retrieves the product brand.
	 *
	 * @since 5.9
	 *
	 * @return string
	 */
	public function get_product_var_brand() {
		$product = $this->get_product();
		if ( ! is_object( $product ) ) {
			return '';
		}

		$brand_taxonomies = array(
			'product_brand',
			'pwb-brand',
		);

		$brand_taxonomies = array_filter( $brand_taxonomies, 'taxonomy_exists' );

		$primary_term = $this->search_primary_term( $brand_taxonomies, $product );
		if ( $primary_term !== '' ) {
			return $primary_term;
		}

		foreach ( $brand_taxonomies as $taxonomy ) {
			$terms = get_the_terms( $product->get_id(), $taxonomy );
			if ( is_array( $terms ) ) {
				return $terms[0]->name;
			}
		}

		return '';
	}

	/**
	 * Searches for the primary terms for given taxonomies and returns the first found primary term.
	 *
	 * @param array      $brand_taxonomies The taxonomies to find the primary term for.
	 * @param WC_Product $product          The WooCommerce Product.
	 *
	 * @return string The term's name (if found). Otherwise an empty string.
	 */
	protected function search_primary_term( array $brand_taxonomies, $product ) {
		// First find the primary term.
		if ( ! class_exists( 'WPSEO_Primary_Term' ) ) {
			return '';
		}

		foreach ( $brand_taxonomies as $taxonomy ) {
			$primary_term       = new WPSEO_Primary_Term( $taxonomy, $product->get_id() );
			$found_primary_term = $primary_term->get_primary_term();

			if ( $found_primary_term ) {
				$term = get_term_by( 'id', $found_primary_term, $taxonomy );
				return $term->name;
			}
		}

		return '';
	}

	/**
	 * Localizes scripts for the WooCommerce Replacevars plugin.
	 *
	 * @return array The localized values.
	 */
	protected function localize_woo_replacevars_script() {
		return array(
			'currency'       => get_woocommerce_currency(),
			'currencySymbol' => get_woocommerce_currency_symbol(),
			'decimals'       => wc_get_price_decimals(),
			'locale'         => str_replace( '_', '-', get_locale() ),
		);
	}

	/**
	 * Localizes scripts for the wooplugin.
	 *
	 * @return array
	 */
	private function localize_woo_script() {
		$asset_manager = new WPSEO_Admin_Asset_Manager();
		$version       = $asset_manager->flatten_version( self::VERSION );

		return array(
			'script_url'     => plugins_url( 'js/yoastseo-woo-worker-' . $version . WPSEO_CSSJS_SUFFIX . '.js', self::get_plugin_file() ),
			'woo_desc_none'  => __( 'You should write a short description for this product.', 'yoast-woo-seo' ),
			'woo_desc_short' => __( 'The short description for this product is too short.', 'yoast-woo-seo' ),
			'woo_desc_good'  => __( 'Your short description has a good length.', 'yoast-woo-seo' ),
			'woo_desc_long'  => __( 'The short description for this product is too long.', 'yoast-woo-seo' ),
		);
	}

	/**
	 * Handles the WooCommerce breadcrumbs replacements.
	 *
	 * @return void
	 */
	protected function handle_breadcrumbs_replacements() {
		// Replaces the WooCommerce breadcrumbs.
		if ( has_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb' ) ) {
			remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );
			add_action( 'woocommerce_before_main_content', array( $this, 'show_yoast_breadcrumbs' ), 20, 0 );
		}

		add_filter( 'wpseo_breadcrumb_links', array( $this, 'add_attribute_to_breadcrumbs' ) );
	}
}


/**
 * Throw an error if WordPress SEO is not installed.
 *
 * @since 1.0.1
 */
function yoast_wpseo_woocommerce_missing_error() {
	echo '<div class="error"><p>';
	printf(
		/* translators: %1$s resolves to the plugin search for Yoast SEO, %2$s resolves to the closing tag, %3$s resolves to Yoast SEO, %4$s resolves to WooCommerce SEO */
		esc_html__( 'Please %1$sinstall &amp; activate %3$s%2$s and then enable its XML sitemap functionality to allow the %4$s module to work.', 'yoast-woo-seo' ),
		'<a href="' . esc_url( admin_url( 'plugin-install.php?tab=search&type=term&s=yoast+seo&plugin-search-input=Search+Plugins' ) ) . '">',
		'</a>',
		'Yoast SEO',
		'WooCommerce SEO'
	);
	echo '</p></div>';
}

/**
 * Throw an error if WordPress is out of date.
 *
 * @since 1.0.1
 */
function yoast_wpseo_woocommerce_wordpress_upgrade_error() {
	echo '<div class="error"><p>';
	printf(
		/* translators: %1$s resolves to WooCommerce SEO */
		esc_html__( 'Please upgrade WordPress to the latest version to allow WordPress and the %1$s module to work properly.', 'yoast-woo-seo' ),
		'WooCommerce SEO'
	);
	echo '</p></div>';
}

/**
 * Throw an error if WordPress SEO is out of date.
 *
 * @since 1.0.1
 */
function yoast_wpseo_woocommerce_upgrade_error() {
	echo '<div class="error"><p>';
	printf(
		/* translators: %1$s resolves to Yoast SEO, %2$s resolves to WooCommerce SEO */
		esc_html__( 'Please upgrade the %1$s plugin to the latest version to allow the %2$s module to work.', 'yoast-woo-seo' ),
		'Yoast SEO',
		'WooCommerce SEO'
	);
	echo '</p></div>';
}


/**
 * Initializes the plugin class, to make sure all the required functionality is loaded, do this after plugins_loaded.
 *
 * @since 1.0
 *
 * @return void
 */
function initialize_yoast_woocommerce_seo() {
	/**
	 * Don't initialize the plugin when WooCommerce is not active.
	 */
	if ( ! class_exists( 'WooCommerce', false ) ) {
		return;
	}

	global $yoast_woo_seo;

	load_plugin_textdomain( 'yoast-woo-seo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	// Initializes the plugin.
	$yoast_woo_seo = new Yoast_WooCommerce_SEO();
}

/**
 * Instantiate the plugin license manager for the current plugin and activate it's license.
 *
 * @codeCoverageIgnore
 *
 * @deprecated 10.1
 */
function yoast_woocommerce_seo_activate_license() {
	_deprecated_function( __FUNCTION__, '10.1' );
}

if ( ! wp_installing() ) {
	add_action( 'plugins_loaded', 'initialize_yoast_woocommerce_seo', 20 );
}

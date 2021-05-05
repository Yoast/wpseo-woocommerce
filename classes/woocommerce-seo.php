<?php
/**
 * WooCommerce Yoast SEO plugin file.
 *
 * @package WPSEO/WooCommerce
 */

/**
 * Class Yoast_WooCommerce_SEO
 */
class Yoast_WooCommerce_SEO {

	/**
	 * Version of the plugin.
	 *
	 * @var string
	 */
	const VERSION = WPSEO_WOO_VERSION;

	/**
	 * The product global identifiers.
	 *
	 * @var array
	 */
	private $global_identifiers = [];

	/**
	 * Return the plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file() {
		return WPSEO_WOO_PLUGIN_FILE;
	}

	/**
	 * Class constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->initialize();
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

		// Make sure the options property is always current.
		add_action( 'init', [ 'WPSEO_Option_Woo', 'register_option' ] );

		// Enable Yoast usage tracking.
		add_filter( 'wpseo_enable_tracking', '__return_true' );

		if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			// Add subitem to menu.
			add_filter( 'wpseo_submenu_pages', [ $this, 'add_submenu_pages' ] );
			add_action( 'admin_print_styles', [ $this, 'config_page_styles' ] );

			// Hide the Yoast SEO columns in the Product table by default, except the SEO score column.
			add_action( 'current_screen', [ $this, 'set_yoast_columns_hidden_by_default' ] );

			// Move Woo box above SEO box.
			add_action( 'admin_footer', [ $this, 'footer_js' ] );

			new WPSEO_WooCommerce_Yoast_Tab();
		}
		else {
			// Initialize schema & OpenGraph.
			add_action( 'init', [ $this, 'initialize_opengraph' ] );
			add_action( 'init', [ $this, 'initialize_schema' ] );
			add_action( 'init', [ $this, 'initialize_twitter' ] );
			add_action( 'init', [ $this, 'initialize_slack' ] );
			add_action( 'init', [ $this, 'initialize_breadcrumbs' ] );
			add_filter( 'wpseo_frontend_presenters', [ $this, 'add_frontend_presenter' ] );

			// Add metadescription filter.
			add_filter( 'wpseo_metadesc', [ $this, 'metadesc' ] );

			add_action( 'wpseo_register_extra_replacements', [ $this, 'register_replacements' ] );
			add_action( 'wp', [ $this, 'get_product_global_identifiers' ] );

			add_filter( 'wpseo_sitemap_exclude_post_type', [ $this, 'xml_sitemap_post_types' ], 10, 2 );
			add_filter( 'wpseo_sitemap_post_type_archive_link', [ $this, 'xml_sitemap_taxonomies' ], 10, 2 );
			add_filter( 'wpseo_sitemap_page_for_post_type_archive', [ $this, 'xml_post_type_archive_page_id' ], 10, 2 );

			add_filter( 'post_type_archive_link', [ $this, 'xml_post_type_archive_link' ], 10, 2 );
			add_filter( 'wpseo_sitemap_urlimages', [ $this, 'add_product_images_to_xml_sitemap' ], 10, 2 );
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Make sure the primary category will be used in the permalink.
		add_filter( 'wc_product_post_type_link_product_cat', [ $this, 'add_primary_category_permalink' ], 10, 3 );

		// Adds recommended replacevars.
		add_filter( 'wpseo_recommended_replace_vars', [ $this, 'add_recommended_replacevars' ] );

		add_filter( 'wpseo_helpscout_beacon_settings', [ $this, 'filter_helpscout_beacon' ] );

		add_filter( 'wpseo_sitemap_entry', [ $this, 'filter_hidden_product' ], 10, 3 );
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', [ $this, 'filter_woocommerce_pages' ] );
	}

	/**
	 * Initializes the schema functionality.
	 */
	public function initialize_schema() {
		if ( WPSEO_WooCommerce_Schema::should_output_yoast_schema() ) {
			new WPSEO_WooCommerce_Schema( WC_VERSION );
		}
	}

	/**
	 * Initializes the schema functionality.
	 */
	public function initialize_opengraph() {
		new WPSEO_WooCommerce_OpenGraph();
	}

	/**
	 * Initializes the twitter functionality.
	 */
	public function initialize_twitter() {
		$twitter = new WPSEO_WooCommerce_Twitter();
		$twitter->register_hooks();
	}

	/**
	 * Initializes the slack functionality.
	 */
	public function initialize_slack() {
		$slack = new WPSEO_WooCommerce_Slack();
		$slack->register_hooks();
	}

	/**
	 * Initializes the schema functionality.
	 */
	public function initialize_breadcrumbs() {
		new WPSEO_WooCommerce_Breadcrumbs();
	}

	/**
	 * Method that is executed when the plugin is activated.
	 */
	public static function install() {
		// Enable tracking.
		if ( class_exists( 'WPSEO_Options' ) && method_exists( 'WPSEO_Options', 'set' ) ) {
			WPSEO_Options::set( 'tracking', true );
		}
	}

	/**
	 * Adds the WooCommerce OpenGraph presenter.
	 *
	 * @param \Yoast\WP\SEO\Presenters\Abstract_Indexable_Presenter[] $presenters The presenter instances.
	 *
	 * @return \Yoast\WP\SEO\Presenters\Abstract_Indexable_Presenter[] The extended presenters.
	 */
	public function add_frontend_presenter( $presenters ) {
		if ( ! is_array( $presenters ) ) {
			return $presenters;
		}

		$product = $this->get_product();
		if ( ! $product instanceof WC_Product ) {
			return $presenters;
		}

		$presenters[] = new WPSEO_WooCommerce_Product_OpenGraph_Deprecation_Presenter( $product );
		$presenters[] = new WPSEO_WooCommerce_Product_Brand_Presenter( $product );

		if ( $this->should_show_price() ) {
			$presenters[] = new WPSEO_WooCommerce_Product_Price_Amount_Presenter( $product );
			$presenters[] = new WPSEO_WooCommerce_Product_Price_Currency_Presenter( $product );
		}

		$is_on_backorder = $product->is_on_backorder();
		$is_in_stock     = ( $is_on_backorder === true ) ? false : $product->is_in_stock();
		$presenters[]    = new WPSEO_WooCommerce_Pinterest_Product_Availability_Presenter( $product, $is_on_backorder, $is_in_stock );
		$presenters[]    = new WPSEO_WooCommerce_Product_Availability_Presenter( $product, $is_on_backorder, $is_in_stock );

		$presenters[] = new WPSEO_WooCommerce_Product_Retailer_Item_ID_Presenter( $product );
		$presenters[] = new WPSEO_WooCommerce_Product_Condition_Presenter( $product );

		return $presenters;
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
				[
					'fields'         => 'ids',
					'posts_per_page' => '-1',
					'post_type'      => 'product',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'tax_query'      => [
						[
							'taxonomy' => 'product_visibility',
							'field'    => 'name',
							'terms'    => [ 'exclude-from-catalog' ],
						],
					],
				]
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
		$woocommerce_pages   = [];
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

		$replacevars['product']                = [ 'sitename', 'title', 'sep', 'primary_category' ];
		$replacevars['product_cat']            = [ 'sitename', 'term_title', 'sep', 'term_hierarchy' ];
		$replacevars['product_tag']            = [ 'sitename', 'term_title', 'sep' ];
		$replacevars['product_shipping_class'] = [ 'sitename', 'term_title', 'sep', 'page' ];
		$replacevars['product_brand']          = [ 'sitename', 'term_title', 'sep' ];
		$replacevars['pwb-brand']              = [ 'sitename', 'term_title', 'sep' ];
		$replacevars['product_archive']        = [ 'sitename', 'sep', 'page', 'pt_plural' ];

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
				$image_src = wp_get_attachment_image_src( $attachment_id, 'full' );
				$image     = [
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- Using WPSEO hook.
					'src'   => apply_filters( 'wpseo_xml_sitemap_img_src', $image_src[0], $post_id ),
					'title' => get_the_title( $attachment_id ),
					'alt'   => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				];
				$images[]  = $image;

				unset( $image, $image_src );
			}
		}

		return $images;
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
		$submenu_pages[] = [
			'wpseo_dashboard',
			sprintf(
			/* translators: %1$s resolves to WooCommerce SEO */
				esc_html__( '%1$s Settings', 'yoast-woo-seo' ),
				'WooCommerce SEO'
			),
			'WooCommerce SEO',
			'wpseo_manage_options',
			'wpseo_woo',
			[ $this, 'admin_panel' ],
		];

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
		Yoast_Form::get_instance()->admin_header( true, 'wpseo_woo' );

		// Do not make the mistake of thinking this should only be public taxonomies, see https://github.com/Yoast/bugreports/issues/872.
		$object_taxonomies = get_object_taxonomies( 'product', 'objects' );
		$taxonomies        = [ '' => '-' ];
		foreach ( $object_taxonomies as $object_taxonomy ) {
			$taxonomies[ strtolower( $object_taxonomy->name ) ] = esc_html( $object_taxonomy->labels->name );
		}

		echo '<h2>' . esc_html__( 'Schema & OpenGraph additions', 'yoast-woo-seo' ) . '</h2>
		<p>' . esc_html__( 'If you have product attributes for the following types, select them here, the plugin will make sure they\'re used for the appropriate Schema.org and OpenGraph markup.', 'yoast-woo-seo' ) . '</p>';

		Yoast_Form::get_instance()->select( 'woo_schema_manufacturer', esc_html__( 'Manufacturer', 'yoast-woo-seo' ), $taxonomies );
		Yoast_Form::get_instance()->select( 'woo_schema_brand', esc_html__( 'Brand', 'yoast-woo-seo' ), $taxonomies );
		Yoast_Form::get_instance()->select( 'woo_schema_color', esc_html__( 'Color', 'yoast-woo-seo' ), $taxonomies );

		if ( WPSEO_Options::get( 'breadcrumbs-enable' ) === true ) {
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

			Yoast_Form::get_instance()->checkbox(
				'woo_breadcrumbs',
				sprintf(
				/* translators: %1$s resolves to WooCommerce */
					esc_html__( 'Replace %1$s Breadcrumbs', 'yoast-woo-seo' ),
					'WooCommerce'
				)
			);
		}

		echo '<h2>' . esc_html__( 'Admin', 'yoast-woo-seo' ) . '</h2>';
		echo '<p>';
		printf(
		/* translators: %1$s resolves to Yoast SEO, %2$s resolves to WooCommerce */
			esc_html__( 'Both %2$s and %1$s add metaboxes to the edit product page, if you want %2$s to be above %1$s, check the box.', 'yoast-woo-seo' ),
			'Yoast SEO',
			'WooCommerce'
		);
		echo "</p>\n";

		Yoast_Form::get_instance()->checkbox(
			'woo_metabox_top',
			sprintf(
			/* translators: %1$s resolves to WooCommerce */
				esc_html__( 'Move %1$s up', 'yoast-woo-seo' ),
				'WooCommerce'
			)
		);

		// Submit button and debug info.
		Yoast_Form::get_instance()->admin_footer( true, false );
	}

	/**
	 * Adds a bit of JS that moves the meta box for WP SEO below the WooCommerce box.
	 *
	 * @since 1.0
	 */
	public function footer_js() {
		if ( WPSEO_Options::get( 'woo_metabox_top' ) !== true ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				// Show WooCommerce box before WP SEO metabox.
				if ( $( "#woocommerce-product-data" ).length > 0 && $( "#wpseo_meta" ).length > 0 ) {
					$( "#woocommerce-product-data" ).insertBefore( $( "#wpseo_meta" ) );
				}
			} );
		</script>
		<?php
	}

	/**
	 * Hides the Yoast SEO columns in the Product table by default, except the SEO score one.
	 *
	 * @param WP_Screen $current_screen Current WP_Screen object.
	 *
	 * @return void
	 */
	public function set_yoast_columns_hidden_by_default( $current_screen ) {
		// Don't do anything if we're not not on the edit products page.
		if ( $current_screen->id !== 'edit-product' ) {
			return;
		}

		$yoast_hidden_columns_old_defaults = [
			'wpseo-title',
			'wpseo-metadesc',
			'wpseo-focuskw',
		];

		$user_id                   = get_current_user_id();
		$user_hidden_columns       = get_hidden_columns( $current_screen );
		$user_hidden_yoast_columns = array_filter( $user_hidden_columns, [ $this, 'filter_yoast_columns' ] );
		$is_old_default            = (
			count( $yoast_hidden_columns_old_defaults ) === count( $user_hidden_yoast_columns )
			&& count( array_diff( $yoast_hidden_columns_old_defaults, $user_hidden_yoast_columns ) ) === 0
			&& count( array_diff( $user_hidden_yoast_columns, $yoast_hidden_columns_old_defaults ) ) === 0
		);

		// Don't do anything if the Yoast hidden columns old defaults have been changed by the user.
		if ( ! $is_old_default ) {
			update_user_option( $user_id, 'wpseo_woo_columns_hidden_default', '1', true );
			return;
		}

		// Don't do anything if the new defaults have already been set.
		if ( get_user_option( 'wpseo_woo_columns_hidden_default', $user_id ) === '1' ) {
			return;
		}

		$yoast_hidden_columns = [
			'wpseo-title',
			'wpseo-metadesc',
			'wpseo-focuskw',
			'wpseo-score-readability',
		];

		if ( class_exists( 'WPSEO_Link_Columns' ) ) {
			$yoast_hidden_columns[] = 'wpseo-' . WPSEO_Link_Columns::COLUMN_LINKS;
			$yoast_hidden_columns[] = 'wpseo-' . WPSEO_Link_Columns::COLUMN_LINKED;
		}

		$hidden_columns = array_merge( $user_hidden_columns, $yoast_hidden_columns );

		update_user_option( $user_id, 'manageedit-productcolumnshidden', $hidden_columns, true );
		update_user_option( $user_id, 'wpseo_woo_columns_hidden_default', '1', true );
	}

	/**
	 * Filter the Yoast columns from the user hidden columns.
	 *
	 * @param string $column The user hidden column identifier.
	 *
	 * @return bool Whether or not the column is a Yoast column.
	 */
	private function filter_yoast_columns( $column ) {
		return strpos( $column, 'wpseo-' ) === 0;
	}

	/**
	 * Make sure product variations and shop coupons are not included in the XML sitemap.
	 *
	 * @since 1.0
	 *
	 * @param bool   $include_in_sitemap Whether or not to include this post type in the XML sitemap.
	 * @param string $post_type          The post type of the post.
	 *
	 * @return bool
	 */
	public function xml_sitemap_post_types( $include_in_sitemap, $post_type ) {
		if ( $post_type === 'product_variation' || $post_type === 'shop_coupon' ) {
			return true;
		}

		return $include_in_sitemap;
	}

	/**
	 * Make sure product attribute taxonomies are not included in the XML sitemap.
	 *
	 * @since 1.0
	 *
	 * @param bool   $include_in_sitemap Whether or not to include this taxonomy in the XML sitemap.
	 * @param string $taxonomy           The taxonomy to check against.
	 *
	 * @return bool
	 */
	public function xml_sitemap_taxonomies( $include_in_sitemap, $taxonomy ) {
		if ( $taxonomy === 'product_type' || $taxonomy === 'product_shipping_class' || $taxonomy === 'shop_order_status' ) {
			return true;
		}

		return $include_in_sitemap;
	}

	/**
	 * Returns the product object when the current page is the product page.
	 *
	 * @since 4.3
	 *
	 * @return WC_Product|null
	 */
	private function get_product() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		if ( is_admin() ) {
			return wc_get_product( get_the_ID() );
		}

		if ( ! is_singular( 'product' ) ) {
			return null;
		}

		return wc_get_product( get_queried_object_id() );
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
			return $this->clean_description( $short_description );
		}

		if ( $long_description !== '' ) {
			return wp_html_excerpt( $this->clean_description( $long_description ), 156 );
		}

		return '';
	}

	/**
	 * Make a string clear for display in meta data.
	 *
	 * @param string $text_string The input string.
	 *
	 * @return string The clean string.
	 */
	protected function clean_description( $text_string ) {
		// Strip tags.
		$text_string = wp_strip_all_tags( $text_string );

		// Replace non breaking space entities with spaces.
		$text_string = str_replace( '&nbsp;', ' ', $text_string );

		// Replace non breaking uni-code spaces with spaces. Don't ask.
		$text_string = str_replace( chr( 194 ) . chr( 160 ), ' ', $text_string );

		// Replace all double or more spaces with one space and trim our string.
		$text_string = preg_replace( '/\s+/', ' ', $text_string );
		$text_string = trim( $text_string );

		return $text_string;
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
	 * @param WC_Product|null $product The product.
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

		if ( $post_type !== 'product' ) {
			return $link;
		}

		if ( function_exists( 'wc_get_page_id' ) ) {
			$shop_page_id = wc_get_page_id( 'shop' );
			$home_page_id = (int) get_option( 'page_on_front' );
			if ( $shop_page_id === -1 || $home_page_id === $shop_page_id ) {
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

		if ( $post_type === 'product' && function_exists( 'wc_get_page_id' ) ) {
			$page_id = wc_get_page_id( 'shop' );
		}

		return $page_id;
	}

	/**
	 * Makes sure the News settings page has a HelpScout beacon.
	 *
	 * @param array $helpscout_settings The HelpScout settings.
	 *
	 * @return array The HelpScout settings with the News SEO beacon added.
	 */
	public function filter_helpscout_beacon( $helpscout_settings ) {
		$helpscout_settings['pages_ids']['wpseo_woo'] = '8535d745-4e80-48b9-b211-087880aa857d';
		$helpscout_settings['products'][]             = WPSEO_Addon_Manager::WOOCOMMERCE_SLUG;

		return $helpscout_settings;
	}

	/**
	 * Checks if the current page is a woocommerce seo plugin page.
	 *
	 * @param string $page Page to check against.
	 *
	 * @return bool
	 */
	protected function is_woocommerce_page( $page ) {
		$woo_pages = [ 'wpseo_woo' ];

		return in_array( $page, $woo_pages, true );
	}

	/**
	 * Enqueues the pluginscripts.
	 */
	public function enqueue_scripts() {
		// Only do this on product pages.
		if ( get_post_type() !== 'product' ) {
			return;
		}

		$asset_manager = new WPSEO_Admin_Asset_Manager();
		$version       = $asset_manager->flatten_version( self::VERSION );

		wp_enqueue_script( 'wp-seo-woo', plugins_url( 'js/dist/yoastseo-woo-plugin-' . $version . '.js', WPSEO_WOO_PLUGIN_FILE ), [], WPSEO_VERSION, true );
		wp_enqueue_script( 'wp-seo-woo-replacevars', plugins_url( 'js/dist/yoastseo-woo-replacevars-' . $version . '.js', WPSEO_WOO_PLUGIN_FILE ), [], WPSEO_VERSION, true );

		wp_localize_script( 'wp-seo-woo', 'wpseoWooL10n', $this->localize_woo_script() );
		wp_localize_script( 'wp-seo-woo-replacevars', 'wpseoWooReplaceVarsL10n', $this->localize_woo_replacevars_script() );
	}

	/**
	 * Registers variable replacements for WooCommerce products.
	 */
	public function register_replacements() {
		wpseo_register_var_replacement(
			'wc_price',
			[ $this, 'get_product_var_price' ],
			'basic',
			'The product\'s price.'
		);

		wpseo_register_var_replacement(
			'wc_sku',
			[ $this, 'get_product_var_sku' ],
			'basic',
			'The product\'s SKU.'
		);

		wpseo_register_var_replacement(
			'wc_shortdesc',
			[ $this, 'get_product_var_short_description' ],
			'basic',
			'The product\'s short description.'
		);

		wpseo_register_var_replacement(
			'wc_brand',
			[ $this, 'get_product_var_brand' ],
			'basic',
			'The product\'s brand.'
		);

		wpseo_register_var_replacement(
			'wc_gtin8',
			[ $this, 'get_product_var_gtin8' ],
			'basic',
			'The product\'s GTIN8 identifier.'
		);

		wpseo_register_var_replacement(
			'wc_gtin12',
			[ $this, 'get_product_var_gtin12' ],
			'basic',
			'The product\'s GTIN12 \/ UPC identifier.'
		);

		wpseo_register_var_replacement(
			'wc_gtin13',
			[ $this, 'get_product_var_gtin13' ],
			'basic',
			'The product\'s GTIN13 \/ EAN identifier.'
		);

		wpseo_register_var_replacement(
			'wc_gtin14',
			[ $this, 'get_product_var_gtin14' ],
			'basic',
			'The product\'s GTIN14 \/ ITF-14 identifier.'
		);

		wpseo_register_var_replacement(
			'wc_isbn',
			[ $this, 'get_product_var_isbn' ],
			'basic',
			'The product\'s ISBN identifier.'
		);

		wpseo_register_var_replacement(
			'wc_mpn',
			[ $this, 'get_product_var_mpn' ],
			'basic',
			'The product\'s MPN identifier.'
		);
	}

	/**
	 * Register the promotion class for our GlotPress instance.
	 *
	 * @link https://github.com/Yoast/i18n-module
	 */
	protected function register_i18n_promo_class() {
		new Yoast_I18n_v3(
			[
				'textdomain'     => 'yoast-woo-seo',
				'project_slug'   => 'woocommerce-seo',
				'plugin_name'    => 'Yoast WooCommerce SEO',
				'hook'           => 'wpseo_admin_promo_footer',
				'glotpress_url'  => 'http://translate.yoast.com/gp/',
				'glotpress_name' => 'Yoast Translate',
				'glotpress_logo' => 'http://translate.yoast.com/gp-templates/images/Yoast_Translate.svg',
				'register_url'   => 'http://translate.yoast.com/gp/projects#utm_source=plugin&utm_medium=promo-box&utm_campaign=wpseo-woo-i18n-promo',
			]
		);
	}

	/**
	 * Returns the product for given product_id.
	 *
	 * @since 4.9
	 *
	 * @param int $product_id The id to get the product for.
	 *
	 * @return WC_Product|null
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

		if ( is_object( $product ) && method_exists( $product, 'is_type' ) && method_exists( $product, 'get_price' ) ) {
			if ( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) ) {
				return $this->get_product_price_from_price_html( $product );
			}

			$price = WPSEO_WooCommerce_Utils::get_product_display_price( $product );

			// For empty prices we want to output an empty string, as wc_price() converts them to `currencySymbol + 0.00`.
			if ( $price === '' ) {
				return '';
			}

			// WooCommerce converts negative prices to 0 so we do the same here.
			if ( intval( $price ) < 0 ) {
				$price = 0;
			}

			return wp_strip_all_tags( wc_price( $price ), true );
		}

		return '';
	}

	/**
	 * Retrieves the price for a variable or grouped product.
	 *
	 * @param WC_Product $product The product.
	 *
	 * @return string The price of a variable or grouped product.
	 */
	public function get_product_price_from_price_html( $product ) {
		if ( method_exists( $product, 'get_price_html' ) && method_exists( $product, 'get_price_suffix' ) ) {
			$price_html   = $product->get_price_html();
			$price_suffix = $product->get_price_suffix();

			return wp_strip_all_tags( str_replace( $price_suffix, '', $price_html ), true );
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

		$brand_taxonomies = [
			'product_brand',
			'pwb-brand',
		];

		$brand_taxonomies = array_filter( $brand_taxonomies, 'taxonomy_exists' );

		$primary_term = WPSEO_WooCommerce_Utils::search_primary_term( $brand_taxonomies, $product );
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
	 * Retrieves the product global identifiers.
	 *
	 * @return void
	 */
	public function get_product_global_identifiers() {
		$product = $this->get_product();
		if ( ! is_object( $product ) ) {
			return;
		}

		$product_id               = $product->get_id();
		$global_identifier_values = get_post_meta( $product_id, 'wpseo_global_identifier_values', true );

		if ( ! is_array( $global_identifier_values ) ) {
			return;
		}

		$this->global_identifiers = $global_identifier_values;
	}

	/**
	 * Retrieves the product GTIN8 identifier.
	 *
	 * @return string The product GTIN8 identifier.
	 */
	public function get_product_var_gtin8() {
		return isset( $this->global_identifiers['gtin8'] ) ? $this->global_identifiers['gtin8'] : '';
	}

	/**
	 * Retrieves the product GTIN12 / UPC identifier.
	 *
	 * @return string The product GTIN12 / UPC identifier.
	 */
	public function get_product_var_gtin12() {
		return isset( $this->global_identifiers['gtin12'] ) ? $this->global_identifiers['gtin12'] : '';
	}

	/**
	 * Retrieves the product GTIN13 / EAN identifier.
	 *
	 * @return string The product GTIN13 / EAN identifier.
	 */
	public function get_product_var_gtin13() {
		return isset( $this->global_identifiers['gtin13'] ) ? $this->global_identifiers['gtin13'] : '';
	}

	/**
	 * Retrieves the product GTIN14 / ITF-14 identifier.
	 *
	 * @return string The product GTIN14 / ITF-14 identifier.
	 */
	public function get_product_var_gtin14() {
		return isset( $this->global_identifiers['gtin14'] ) ? $this->global_identifiers['gtin14'] : '';
	}

	/**
	 * Retrieves the product ISBN identifier.
	 *
	 * @return string The product ISBN identifier.
	 */
	public function get_product_var_isbn() {
		return isset( $this->global_identifiers['isbn'] ) ? $this->global_identifiers['isbn'] : '';
	}

	/**
	 * Retrieves the product MPN identifier.
	 *
	 * @return string The product MPN identifier.
	 */
	public function get_product_var_mpn() {
		return isset( $this->global_identifiers['mpn'] ) ? $this->global_identifiers['mpn'] : '';
	}

	/**
	 * Localizes scripts for the WooCommerce Replacevars plugin.
	 *
	 * @return array The localized values.
	 */
	protected function localize_woo_replacevars_script() {
		return [
			'currency'       => get_woocommerce_currency(),
			'currencySymbol' => get_woocommerce_currency_symbol(),
			'decimals'       => wc_get_price_decimals(),
			'locale'         => str_replace( '_', '-', get_locale() ),
			'price'          => $this->get_product_var_price(),
		];
	}

	/**
	 * Localizes scripts for the wooplugin.
	 *
	 * @return array
	 */
	private function localize_woo_script() {
		$asset_manager = new WPSEO_Admin_Asset_Manager();
		$version       = $asset_manager->flatten_version( self::VERSION );

		$google_preview                 = [];
		$product                        = $this->get_product();
		$google_preview['rating']       = floatval( $product->get_average_rating() );
		$google_preview['reviewCount']  = $product->get_rating_count();
		$google_preview['availability'] = str_replace( '-', ' ', $product->get_availability()['class'] );

		// Because the backorder availability value is not supported in the Google Product snippet, we output preorder in the schema, and thus the preview.
		if ( $google_preview['availability'] === 'available on backorder' ) {
			$google_preview['availability'] = 'preorder';
		}

		if ( $this->should_show_price() ) {
			$google_preview['price'] = $this->get_product_var_price();
		}

		return [
			'script_url'              => plugins_url( 'js/dist/yoastseo-woo-worker-' . $version . '.js', self::get_plugin_file() ),
			'woo_desc_none'           => __( 'You should write a short description for this product.', 'yoast-woo-seo' ),
			'woo_desc_short'          => __( 'The short description for this product is too short.', 'yoast-woo-seo' ),
			'woo_desc_good'           => __( 'Your short description has a good length.', 'yoast-woo-seo' ),
			'woo_desc_long'           => __( 'The short description for this product is too long.', 'yoast-woo-seo' ),
			'wooGooglePreviewData'    => $google_preview,
		];
	}

	/**
	 * Refresh the options property on add/update of the option to ensure it's always current.
	 *
	 * @deprecated 12.5
	 * @codeCoverageIgnore
	 */
	public function refresh_options_property() {
		_deprecated_function( __METHOD__, 'WPSEO Woo 12.5' );
	}

	/**
	 * Perform upgrade procedures to the settings.
	 *
	 * @deprecated 12.5
	 * @codeCoverageIgnore
	 */
	public function upgrade() {
		_deprecated_function( __METHOD__, 'WPSEO Woo 12.5' );
	}

	/**
	 * Simple helper function to show a checkbox.
	 *
	 * @deprecated 12.5
	 * @codeCoverageIgnore
	 */
	public function checkbox() {
		_deprecated_function( __METHOD__, 'WPSEO Woo 12.5' );
	}

	/**
	 * Determines if the price should be shown.
	 *
	 * @return bool True when the price should be shown.
	 */
	private function should_show_price() {
		/**
		 * Filter: wpseo_woocommerce_og_price - Allow developers to prevent the output of the price in the OpenGraph tags.
		 *
		 * @deprecated 12.5.0. Use the {@see 'Yoast\WP\Woocommerce\og_price'} filter instead.
		 *
		 * @api bool unsigned Defaults to true.
		 */
		$show_price = apply_filters_deprecated(
			'wpseo_woocommerce_og_price',
			[ true ],
			'Yoast WooCommerce 12.5.0',
			'Yoast\WP\Woocommerce\og_price'
		);

		/**
		 * Filter: Yoast\WP\Woocommerce\og_price - Allow developers to prevent the output of the price in the OpenGraph tags.
		 *
		 * @since 12.5.0
		 *
		 * @api bool unsigned Defaults to true.
		 */
		$show_price = apply_filters( 'Yoast\WP\Woocommerce\og_price', $show_price );

		if ( is_bool( $show_price ) ) {
			return $show_price;
		}

		return false;
	}
}

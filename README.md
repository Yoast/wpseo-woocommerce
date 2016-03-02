WooCommerce Yoast SEO
=====================
Requires at least: 4.0<br>
Tested up to: 4.4<br>
Stable tag: 3.1.1<br>

This extension to WooCommerce and WordPress SEO by Yoast makes sure there's perfect communication between the two plugins.

Description
-----------

This extension to WooCommerce and WordPress SEO by Yoast makes sure there's perfect communication between the two plugins.

Installation
------------

1. Go to Plugins -> Add New.
2. Click "Upload" right underneath "Install Plugins".
3. Upload the zip file that this readme was contained in.
4. Activate the plugin.
5. Go to SEO -> Licenses and enter your WooCommerce SEO license key.
6. Save settings, your license key will be validated. If all is well, you should now see the WooCommerce SEO settings.

Frequently Asked Questions
--------------------------

You can find the FAQ [online here](https://yoast.com/wordpress/plugins/yoast-woocommerce-seo/faq/).

Changelog
=========

### 3.1.1: March 2nd, 2016

* Bug fixes
	* Fixes a bug where the Yoast content analysis would break on product edit pages when Yoast WooCommerce SEO and Yoast SEO (Premium) 3.1+ are both active.

### 3.1: March 2nd, 2016

* Bug fixes
	* Fixes a bug where our license manager could sometimes not reach our licensing system due to problems with ssl.

* Enhancements
	* Makes sure users don't have to reactivate their license after updating or disabling/enabling the plugin.
	* Adds a support beacon on the WooCommerce SEO settings page enabling users to ask for support from the WordPress backend.
	* Makes license calls a bit faster.
	* Removed all Twitter card functionality since the Product twitter card no longer exists.

### 3.0: November 18th, 2015

* Synchronized plugin version with all other Yoast SEO plugins for WordPress.

* Bug fixes
	* Fixes deprecation warnings for filters and functions that have been removed in Yoast SEO
	* Fixes a fatal on the frontend when WooCommerce SEO is active but WooCommerce isn't.

* Enhancements
	* Makes sure WooCommerce specific content analysis checks work well with the Real Time content analysis tool in Yoast SEO 3.0.
	* Makes sure the product image galleries are still analyzed as part of the content by the Real Time content analysis tool in Yoast SEO 3.0.
	* Improves the order in which opengraph images are output. First the facebook image, then the facebook image, then the product gallery images.

### 1.1.6: November 11th, 2014
* Bugfixes
	* Fixes a bug where a Fatal error was being raised on the frontend when WooCommerce is not activated.
	* Fixes a bug where Open Graph image tags for featured images and facebook images were not included first when a product image gallery existed.
* Enhancements
	* Defaults to the short description for the meta description when no meta description is set.
	* Added 8 new languages: da_DK, en_GB, es_ES, es_MX, it_IT, nb_NO, nl_NL and tr_TR.

### 1.1.5: September 9th, 2014
* Prevent adding product archive link to XML sitemap

### 1.1.4: July 15th, 2014
* Add `wpseo_woocommerce_og_price` filter. Returning false on it prevents price from being put out in OpenGraph tags.
* Add attribute to breadcrumbs when attribute is selected.
* Removed unused breadcrumb option.
* Only initiate plugin when WP is not installing.

### 1.1.3: June 24th, 2014
* Improved how WooCommerce breadcrumbs are replaced.
* Fixed double class instantiation within same method.
* Add call to `load_plugin_textdomain()`.
* Make sure we recognize WooCommerce product gallery images in page analysis.
* Add images from product gallery to XML sitemap.
* Use product category thumbnail for og:image.
* Make sure short description length test also soft errors when short description is too long.
* Use WooCommerce price formatting functions for price in Twitter card.

### 1.1.2: March 21st, 2014
* Fixed a bug where the breadcrumb caused a fatal error.

### 1.1.1: March 21st, 2014
* Added Yoast license manager to plugin.

### 1.1: March 11th, 2014
* Compatibility update for WP SEO v1.5 including application of a number of best practices.

* Bugfixes
  * Fixed shortcodes should be removed from ogdesc.
  * Fixed duplicate twitter domain meta tag
  * Fixed error loading stylesheet (WPSEO_URL no longer defined).

* Additional enhancements
  * Change the minimum content length requirements to 200, instead of the WP SEO default of 300.
  * Add a length test for the products short description.
  * Make sure the content analysis tests use the product images as well.
  * If a product category has a description, use it for the OpenGraph description.
  * Switch to general WP SEO Licensing class

### 1.0.1: February 17th, 2014
* Add check whether WordPress and WordPress SEO by Yoast are installed and up-to-date

### 1.0: April 8th, 2013
* Initial version.
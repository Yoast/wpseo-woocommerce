WooCommerce Yoast SEO
=====================
Requires at least: 3.5
Tested up to: 3.6.1
Stable tag: 1.0.1

This extenstion to WooCommerce and WordPress SEO by Yoast makes sure there's perfect communication between the two plugins.

Description
-----------

This extenstion to WooCommerce and WordPress SEO by Yoast makes sure there's perfect communication between the two plugins.

Installation
------------

1. Go to Plugins -> Add New.
2. Click "Upload" right underneath "Install Plugins".
3. Upload the zip file that this readme was contained in.
4. Activate the plugin.
5. Go to SEO -> WooCommerce SEO and enter your license key.
6. Save settings, your license key will be validated. If all is well, you should now see the WooCommerce SEO settings.

Frequently Asked Questions
--------------------------

You can find the FAQ [online here](http://yoast.com/wordpress/woocommerce-seo/faq/).

Changelog
=========

Trunk
------
* Enhancement
	* Define 'WPSEO_WOO_LICENSE' in wp-config.php to automatically activate the license (also in WPMU install) on plugin activation

1.1
---

* Enhancements
	* Change the minimum content length requirements to 200, instead of the WP SEO default of 300.
	* Add a length test for the products short description.
	* Make sure the content analysis tests use the product images as well.
	* If a product category has a description, use it for the OpenGraph description.

1.0.1
-----

* Add check whether WordPress and WordPress SEO by Yoast are installed and up-to-date

1.0
---

* Initial version.

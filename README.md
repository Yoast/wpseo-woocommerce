WooCommerce Yoast SEO
=====================
Requires at least: 5.6
Tested up to: 5.8
Stable tag: 14.2.1
Requires PHP: 5.6.20
Depends: Yoast SEO, WooCommerce

Description
-----------

This extension to WooCommerce and Yoast SEO makes sure there's perfect communication between the two plugins.

This repository uses [the Yoast grunt tasks plugin](https://github.com/Yoast/plugin-grunt-tasks).

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

You can find the FAQ [online here](https://kb.yoast.com/kb/category/woocommerce-seo/).

Changelog
=========

### 14.2.1: July 21nd, 2021
Bugfixes:
* Fixes a bug where a fatal error was thrown on the frontend of product pages when using the `%%wc_shortdesc%%` snippet variable while running PHP 8.0.
* Fixes a bug where the `%%wc_price%%`, `%%wc_sku%%`, `%%wc_shortdesc%%` and `%%wc_brand%%` snippet variable values were not displayed on the frontend.


### 14.2: July 13th, 2021
Enhancements:
* Adds key/value pairs of all WooCommerce SEO meta tags to our REST API.

Other:
* Sets the minimum WordPress version to 5.6.
* Sets the minimum required Yoast SEO to 16.7.
* Replaces an occurrence of the deprecated jQuery `.ready` syntax with more modern syntax. Props to [kkmuffme](https://github.com/kkmuffme).

### 14.1: June 1st, 2021
Other:

* Makes WooCommerce SEO compatible with the latest version of Yoast SEO.
* Sets the minimum supported Yoast SEO version to 16.4.

### 14.0: May 18th, 2021
Enhancements:

* Add the rating, number of reviews, price and availability to the Google Preview for products to match Google's search results.
* Improves the `priceSpecification` Schema output to prevent warnings from Google Search Console.

Bugfixes:

* Fixes a bug where the Social Previews would not use Product Gallery images as a fallback for social sharing.

Other:

* Sets the WordPress tested up to version to 5.7 and minimum supported WordPress version to 5.6.
* Sets the minimum supported Yoast SEO version to 16.3.

### 13.9: January 26th, 2021
Bugfixes:

* Fixes a bug where a deprecation notice would be shown in the PHP debug log when visiting a product page.

### 13.8: December 2nd, 2020
Enhancements:

* Makes the Schema Product type filterable.

### 13.7: October 27th, 2020
Enhancements:

* Shows the price and availability of a product in its snippet when sharing the product on Slack.

### 13.6: September 29th, 2020
Bugfixes:

* Fixes a bug where no plugin icon and compatibility data would be displayed on the plugins update screen.

Other:

* We've tested with WooCommerce 4.5. Everything works as expected!

### 13.5: August 18th, 2020
Enhancements:

* Adds ‘WebPage’ as schema `@type` to Product pages and Checkout pages. This results in a `@type` array with at least 'WebPage' and 'ItemPage', or 'WebPage' and 'CheckoutPage'.

Other:

* Enables tracking when activating the plugin. This can be disabled in the Yoast SEO configuration wizard.
* Sets the minimum supported WordPress version to 5.4.

### 13.4: July 28th, 2020
Enhancements:

* Hides the Yoast SEO columns in the Products overview table by default. 

### 13.3: June 23rd, 2020
Bugfixes:

* Fixes a bug where no SKU would be output when the SKU field was left empty. It will now fall back to the product’s ID.

Enhancements:

* Outputs `PreOrder` as availability property value in the Product schema when the product is “on backorder”.

### 13.2: May 26th, 2020
Bugfixes:

* Fixes a bug where the organization schema for a primary term would error.
* Fixes a bug where a potential undefined variable notice was shown when toggling the 'prices have tax included' WooCommerce option.
* Fixes a bug where changing the product permalinks would not reset the permalinks in their respective indexables.

Enhancements:

* Omits the `product:price:amount` and `product:price:currency` og meta for variable and grouped products.
* Hides all Yoast columns in the Product overview page except the SEO Score column.

### 13.1: May 13th, 2020
Bugfixes:

* Fixes a bug where the `twitter:image` meta tag would not fall back on the first product gallery image when no main product image was set.
* Fixes a bug where all product gallery images would be output as `og:image` even though a user had set a specific image for a product.

### 13.0: April 28th, 2020
Bugfixes:

* Fixes a bug where thumbnail product gallery images were added to the XML product sitemap instead of full size images.

Enhancements:

* Adds a `Term hierarchy` snippet variable.
* Unifies the OpenGraph description fallbacks as per https://developer.yoast.com/features/meta-tags/.

Other:

* Compatibility with Yoast SEO 14.0.
* Sets the minimum supported Yoast SEO version to 14.0.
* Sets the minimum supported WordPress version to 5.3.

### 12.7: March 31st, 2020
Bugfixes:

* Fixes a bug where PHP notices and a PHP warning were thrown for `Product` schema without a price.
* Fixes a bug where the `priceValidUntil` schema property wasn't output for products on sale with an explicitly set sale end date.

Enhancements:

* Improves the Open Graph product availability for better compatibility with Facebook and Pinterest.
* Adds the product identifiers (GTIN, ISBN etc.) to the SEO title and Meta description replacement variables.
* Adds a `color` attribute to the `Product` Schema output.

### 12.6.2: March 11th, 2020
Other:

* We've tested with WooCommerce 4.0. Everything works as expected!

### 12.6.1: February 27th, 2020
Bugfixes:

* Fixes a bug where the product price and tax inclusion were not accurately reflected in the Schema and Open Graph metatags.

Enhancements:

* Removes the `valueAddedTaxIncluded` Schema property when tax rate calculations are disabled in WooCommerce.
* Removes the 'Prices in Open Graph and Schema include tax' setting in the plugin. Whether tax is included in Open Graph and Schema now solely relies on your product page settings in WooCommerce.

### 12.6: February 18th, 2020
Bugfixes:

* Fixes a bug where the internal linking and additional keyphrase functionality are missing from the product edit page.
* Fixes a bug where the short description for the meta description and Twitter and Facebook description could still contain HTML tags and redundant spaces.

Enhancements:

* Adds explanatory copy above the input fields for GTIN, ISBN etc.
* Adds an option to display the price in Schema and OpenGraph _with_ tax included.

### 12.5.1: February 5th, 2020
Bugfixes:
* Fixes a bug where not all product attributes were available in the Brands and Manufacturer dropdowns.

### 12.5: February 4th, 2020
Enhancements:

* Adds a Yoast SEO section to the WooCommerce product data metabox that allows entering a GTIN, ISBN and MPN number.
* Analyzes the content of the short description as part of the page content.
* Throws a warning when WooCommerce is not active.
* Adjusts the wording for when Yoast SEO is not active.
* Schema: 
    * Adds an `@id` attribute to every individual review.
    * Adds a `name` attribute to every individual review.
    * Adds an `@id` attribute to every offer.
    * Adds a `productID` attribute to the `Product` output.
    * Adds a `gtin8` attribute to the `Product` output.
    * Adds a `gtin12` attribute to the `Product` output.
    * Adds a `gtin13` attribute to the `Product` output.
    * Adds a `gtin14` attribute to the `Product` output.
    * Adds a `mpn` attribute to the `Product` output.
    * Adds an `isbn` attribute to the `Product` output. If an ISBN number is set, it double types the schema output to `[ Product, Book ]`. By double typing it, the Product can have the attributes of both schema.org/Book and schema.org/Product, and thus it can have an ISBN attribute and a price etc.
    * Removes the `priceValidUntil` attribute, as it's not sensible.
* OpenGraph:    
    * Adds a `product:condition` meta tag to the OpenGraph output. It defaults to `new` but can be filtered using the new `Yoast\WP\Woocommerce\product_condition` filter.
    * Adds a `product:retailer_item_id` meta tag to the OpenGraph output for Facebook Catalog usage.
* Don't try to use the WooCommerce shop page in the sitemap when it is not set in WooCommerce.

Bugfixes:

* Fixes a bug where the `product:brand` meta tag would not always be the selected primary term.
* Fixes a bug where the `product:availability` meta tag would be set to `instock` instead of the correct `in stock`.
* Fixes a bug where a PHP notice would be thrown on products with variations where a variation lacked an image.
* Fixes a bug where Organization would be used in the schema output, while no organization was set.
* Fixes a bug where non-public taxonomies could be chosen as manufacturer and brand.
* Fixes a bug where the shop page would be shown in the XML sitemap when it was set to `noindex`. Props [stodorovic](https://github.com/stodorovic).
* Fixes a bug where an empty `product:retailer_item_id` was output when there was no SKU.

Other:

* Deprecates the `wpseo_woocommerce_og_price` filter hook in favor of the `Yoast\WP\Woocommerce\og_price` hook.

### 12.4.1: December 17th, 2019
Bugfixes:
* Fixes a bug where reviews weren't output in the Product schema when WooCommerce 3.8.1 was installed.

### 12.4: November 26th, 2019
Other:
* Sets the minimum WordPress version to 5.2 and the minimum Yoast SEO version to 12.6.
* Replaces the HelpScout beacon on the WooCommerce SEO admin page.

### 12.3: November 13th, 2019
Bugfixes:
* Fixes a bug where hidden products would show in the XML sitemap.

Other:
* Sets the minimum required WordPress version to 5.2, and the minimum PHP version to 5.6.20.

### 12.2: October 15th, 2019
Enhancements:
* Shows a floating `Save changes` button on the Yoast SEO for WooCommerce admin page when the normal button is not visible in the browser window.

Other:
* Removes the colon from the form labels in the settings.
* Compatibility with Yoast SEO 12.2

### 12.1: September 17th, 2019
Other:
* Compatibility with Yoast SEO 12.1

### 12.0: September 3rd, 2019
Bugfixes:
* Fixes a bug where the primary category would not be reflected correctly in the product schema output when it was being used to set the manufacturer or brand.

Other:
* Compatibility with Yoast SEO 12.0

### 11.9: August 20th, 2019
Bugfixes:
* Fixes a bug where invalid schema would be output when a product used the placeholder image.

Other:
* Compatibility with Yoast SEO 11.9

### 11.8: August 6th, 2019
Bugfixes:
* Fixes a bug where the Yoast SEO WooCommerce schema was output when the `wpseo_json_ld_output` filter was set to false.

Other:
* Compatibility with Yoast SEO 11.8

### 11.7: July 23rd, 2019
Other:
* Compatibility with Yoast SEO 11.7

### 11.6: July 7th, 2019
Other:
* Fixes a deprecation notice when calling `get_woocommerce_term_meta`.
* Compatibility with Yoast SEO 11.6

### 11.5: June 25th, 2019
Other:
* Compatibility with Yoast SEO 11.5

### 11.4: June 12th, 2019
Other:
* Compatibility with Yoast SEO 11.4

### 11.3: May 28th, 2019
Other:
* Compatibility with Yoast SEO 11.3

### 11.2: May 15th, 2019
Other:
* Compatibility with Yoast SEO 11.2

### 11.1: April 30th, 2019
Bugfixes:
* Fixes a bug where a PHP warning would occur on a product page when a review is added.
* Fixes bug where a fatal crash would occur on the checkout page when WooCommerce is not active.

Other:
* Disables plugin when WooCommerce is not active.
* Compatibility with Yoast SEO 11.1

### 11.0: April 16th, 2019
Enhancements:
* Improves the integration of Woo & Yoast's Schema output, bringing it to a whole new level.
* Schema: Changes page type on Checkout to `CheckoutPage`.
* Schema: Changes page type on Product to `ItemPage`.

Other:
* Removes Woo breadcrumbs Schema output.
* Sets the minimum required Yoast SEO version to 11.0.

### 10.1: April 2nd, 2019
Enhancements:
* Changes `og:brand` to `product:brand` to match the OpenGraph specifications better.

Other:
* Sets the minimum required Yoast SEO version to 10.1.
* Removes an empty method that has been deprecated since version 5.6.

### 10.0: March 12th, 2019
Other:
* Compatibility with Yoast SEO 10.0

### 9.7: February 26th, 2019
Other:
* Compatibility with Yoast SEO 9.7

### 9.6.1: February 12th, 2019
Other:
* Compatibility with Yoast SEO 9.6.1

### 9.6: February 12th, 2019
Other:
* Compatibility with Yoast SEO 9.6

### 9.5: January 22nd, 2019
Other:
* Compatibility with Yoast SEO 9.5

### 9.4: January 8th, 2019
Other:
* Compatibility with Yoast SEO 9.4

### 9.3: December 18th, 2018
Enhancements:
* The short product description assessment in the SEO analysis is now updated whenever the description gets changed inside the Text-based editor (previously only in the Visual editor).

Bugfixes:
* Fixes a bug where the WooCommerce breadcrumbs were not replaced by the Yoast SEO breadcrumbs.
* Fixes a bug where switching to the visual editor with the text editor active could cause the Product editor to crash. 

Other:
* Compatibility with Yoast SEO 9.3

### 9.2: November 20th, 2018
* Compatibility with Yoast SEO 9.2

### 9.1: November 6th, 2018
Bugfixes:
* Fixes a bug where the `Yoast SEO Breadcrumbs` settings page link did not go to the correct page.
* Fixes a bug where a PHP warning would be displayed when `php_uname` has been disabled for security reasons.

Other:
* Compatibility with Yoast SEO 9.1

### 9.0: October 9th, 2018
* Compatibility with Yoast SEO 9.0

### 8.4: October 9th, 2018
* Compatibility with Yoast SEO 8.4

### 8.3: September 25th, 2018
* Compatibility with Yoast SEO 8.3

### 8.2: September 11th, 2018
* Compatibility with Yoast SEO 8.2

### 8.1: August 28th, 2018
* Compatibility with Yoast SEO 8.1

### 8.0: August 14th, 2018
* Compatibility with Yoast SEO 8.0

### 7.9.1: August 7th, 2018
* Compatibility with Yoast SEO 7.9.1

### 7.9: July 24th, 2018
* Compatibility with Yoast SEO 7.9

### 7.8: July 10th, 2018
* Excludes the WooCommerce core pages from the sitemap, as they are set to `noindex` by WooCommerce.
* Hides excluded catalog products from the products sitemap.
* Compatibility with Yoast SEO 7.8

### 7.7: June 26th, 2018
* Adds recommended templates with replacements variables for WooCommerce pages.
* Compatibility with Yoast SEO 7.7

### 7.6: June 5th, 2018
* Compatibility with Yoast SEO 7.6

### 7.5: May 15th, 2018
* Compatibility with Yoast SEO 7.5

### 7.4: May 1st, 2018
* Compatibility with Yoast SEO 7.4

### 7.3: April 17th, 2018
* Compatibility with Yoast SEO 7.3

### 7.2: April 3rd, 2018
* Compatibility with Yoast SEO 7.2

### 7.1: March 20th, 2018
* Compatibility with Yoast SEO 7.1

### 7.0: March 6th, 2018
Other:
* Removes backfill for `wp_installing()` as this was introduced in WordPress 4.4.
* Requires WordPress 4.8 or higher to be installed.
* Requires Yoast SEO 7.0 or higher to be installed.
* Security hardening.

### 6.3: February 13th, 2018
* Compatibility with Yoast SEO 6.3

### 6.2: January 23rd, 2018
Enhancements:
* Adds `%%wc_price%%`, `%%wc_sku%%`, `%%wc_shortdesc%%` and `%%wc_brand%%` replacement variables for titles and meta description.

### 6.1: January 9th, 2018
Enhancements:
* Excludes product names from being translated.

Bugfixes:
* Fixes a bug where there was a link to a settings page that no longer existed.

### 6.0: December 20th, 2017
* Compatibility with Yoast SEO 6.0

### 5.9: December 5th, 2017
Bugfixes:
* Fixes a bug where product gallery images were being placed above the featured image in the OpenGraph output. This caused an unexpected image to be used when sharing the page.
* Fixes a bug where the Yoast SEO link count columns where not removed from the product page when the setting `Remove Yoast SEO columns` is used.

Other:
* Compatibility with Yoast SEO 5.9

### 5.8: November 15th, 2017
* Compatibility with Yoast SEO 5.8

### 5.7: October 24th, 2017
* Compatibility with Yoast SEO 5.7

### 5.6: October 10th, 2017
Enhancements:
* Changes the capability on which the submenu is registered to `wpseo_manage_options`
* Changes the way the submenu is registered to use the `wpseo_submenu_pages` filter

Bugfixes:
* Fixes a bug where the short product description was cut after 156 characters in the meta description
* Fixes a bug where the license check endpoint was using an incorrect URL

### 5.5: September 26th, 2017
* Updated the internationalization module to version 3.0.

### 5.4: September 6th, 2017
* Compatibility with Yoast SEO 5.4

### 5.3: August 22nd, 2017
* Compatibility with Yoast SEO 5.3

### 5.2: August 8th, 2017
* Compatibility with Yoast SEO 5.2

### 5.1: July 25th, 2017
* Compatibility with Yoast SEO 5.1

### 5.0: July 6th, 2017
* Compatibility with Yoast SEO 5.0

### 4.9: June 7th, 2017
* Adds a filter to set the primary category in the permalink.
* Adds fallbacks for several deprecated warnings.
* Fixes a bug where not all Yoast SEO columns were hidden when the columns output by Yoast were removed.

### 4.8: May 23rd, 2017
* Compatibility with Yoast SEO 4.8

### 4.7: May 2nd, 2017
* Compatibility with Yoast SEO 4.7

### 4.6: April 11th, 2017
* Compatibility with Yoast SEO 4.6

### 4.5: March 21st, 2017
* Compatibility with Yoast SEO 4.5

### 4.4: February 28th, 2017
* Compatibility with Yoast SEO 4.4

### 4.3: February 14th, 2017
* Bugfixes
    * Fixes a bug where duplicate opengraph image tags were added to product categories.

### 4.2.1: February 3rd, 2017

* Bugfixes
	* Fixes "Fatal error: Class 'yoast_i18n' not found". 

### 4.2: January 31st, 2017

* Compatibility with Yoast SEO 4.2

### 4.1: January 17th, 2017

* Compatibility with Yoast SEO 4.1

### 4.0: December 13th, 2016

* Compatibility with Yoast SEO 4.0

### 3.9: November 29th, 2016

* Compatibility with Yoast SEO 3.9

### 3.8: November 8th, 2016

* Compatibility with Yoast SEO 3.8

### 3.7: October 11th, 2016

* Compatibility with Yoast SEO 3.7

### 3.6: September 27th, 2016

* Changes
	* Updated translations.

### 3.5: September 7th, 2016

* Compatibility with YoastSEO 3.5

### 3.4: July 19th, 2016

* Changes
	* Updated translations.

### 3.3: June 14th, 2016

* Enhancements
	* Adds the Yoast i18n module to the Yoast SEO WooCommerce settings page, which informs users the plugin isn't available in their language and what they can do about it.

* Bugfixes
    * Fixes a bug where the support beacon for Yoast SEO WooCommerce was added to all Yoast SEO settings pages.
    * Fixes a bug where updates were not working reliably when multiple paid Yoast plugins were active.

### 3.2.1: April 28th, 2016

* Bug fixes
	* Fixes a bug where the rel=next and rel=prev links were not displayed on shop archive pages 2 and up.

### 3.2: April 20th, 2016

* Bug fixes
	* Fixes a bug where clicking the 'Update now' button on the plugin page didn't update correctly.
	* Fixes a bug where product specific checks that were added to the content analysis would no longer work in combination with Yoast SEO 3.2 and higher.

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
### Earlier versions

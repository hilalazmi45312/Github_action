=== Force Default Variant for WooCommerce ===
Contributors: HappyKite, morrowmedia, philmorrow, obailey
Tags: WooCommerce, Variable product, WooCommerce variant, eCommerce
Requires at least: 4.2
Tested up to: 6.8.2
Stable tag: 1.8.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Removes the Standard WooCommerce variant default of 'Choose an Option' and replaces it with a variant.

== Description ==
<blockquote>Please Note - This plugin requires WooCommerce Version 2.4 or greater in order to properly function.</blockquote>

All WooCommerce variable products have a dropdown to select which variant the customer wants to add to cart. The standard behaviour is to not set a default and until a variant is chosen the 'Add to Cart' button is not visible.

This can cause confusion to customers coming to your site, which as a knock on affect could cause a lower than average conversion rate.

You can solve this by manually setting a default option per product, but this can be time consuming on sites with lots of products and also the user can still 'clear' the selection, causing the Add to Cart button to hide again.

This plugin aims to solve these issues by removing the blank 'Choose an Option' value from the dropdown, removing the link to clear the options and instead listing out only the actual variants, always setting a default.

This default can be configured in multiple ways; the shop owner can choose to either list the variant by Position, ID, Price Low -> High and Price High -> Low. If there are multiple variants with the same Initial Sort (Price for example), you can now assign a secondary sort function. These can be Position, ID, Stock Levels or Total Sales.

This can all be changed via the WooCommerce -> Settings -> Product -> Variant menu. If you have already set a default variant for each individual product, you can decide to keep that, or use this plugin instead.

== Installation ==
1. Upload the folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce -> Settings -> Products -> Variant to choose how to display the Variations.

== Changelog ==
= 1.8.3 - 5th September 2025 =
* UPDATE - Works with WooCommerce 10.2+
* UPDATE - Branding Changes - Comply with WC Trademark.

= 1.8.2 - 16th July 2025 =
* FIX - WooCommerce 10.2 Version check fix.
* UPDATE - Works with WordPress 6.8.2

= 1.8.1 - 28th September 2024 =
* NEW - Added Stock Count to the first Sort selection.
* UPDATE - Works with WooCommerce 9.3.3
* UPDATE - Works with WordPress 6.6.2

= 1.8 - 27th October 2023 =
* FIX - Issue with multiple attributes fixed
* NEW - Tested and declared compatibility with WC HPOS
* UPDATE - Works with WooCommerce 8.2.1
* UPDATE - Works with WordPress 6.3.2

= 1.7 - 24th March 2023 =
* NEW - Adds the ability to order variations Alphabetically
* FIX - Now requires WooCommerce to be active before activating
* UPDATE - Works with WooCommerce 7.5+
* UPDATE - Works with WordPress 6.1+
* UPDATE - Works with PHP 8.1

= 1.6.1 - 29th July 2022 =
* FIX - Issue in 1.6 that caused Out of Stock variations to appear in WooCommerce Hide Out of Stock setting is ticked.

= 1.6.0 - 1st July 2022 =
* FIX - Sort by Position fixed - This will now correctly sort by the variation order within the Variations tab on each Product page.
* FIX - Sort by Position renamed - To reflect the fix above, this has also been renamed to 'Variation Position' to make it clearer.
* NEW - Sort by Attribute Order now exists - This will sort by the Menu Order based on the variation Attributes.
* UPDATE - Works with WooCommerce 6.6+
* UPDATE - Work with WordPress 6.0+

= 1.5.2 =
* FIX - Will work properly with custom attributes
* FIX - 'Select Option' toggle works properly with WooCommerce v4+
* FIX - 'Hide out of stock items' now works properly with WooCommerce v4+
* IMPROVEMENT - If all Variants are below your specified Stock Limit, this will display the correct WooCommerce 'Out of Stock' message.
* UPDATE - Works with WooCommerce 4.1.1
* UPDATE - Works with WordPress 5.4.1

= 1.5.1 =
* FIX - Now respects the 'Hide out of stock items' option within WooCommerce.
* IMPROVEMENT - Improved the ability for external translations.
* UPDATE - Tested up to WooCommerce 4.0+

= 1.5 =
* NEW - Added in a Secondary sort function. If products have the same price you can sort them again by Stock, Sales, ID or Position
* IMPROVEMENT - Added in more Filters and hooks.
* UPDATE - Tested up to WordPress 5.3
* UPDATE - Tested up to WooCommerce 3.8

= 1.4.3 =
* NEW - Select Option will now be removed from all product dropdown by default.
* NEW - Added new setting to toggle default 'Select Option' text
* FIX - Notice in /includes/variations.php:170
* FIX - Default selected option field being blank on page load due to missing JS classes.
* UPDATE - Now works with WordPress 5.2 and above.

= 1.4.2 =
* NEW - Adding in the ability to sort by Variant Position
* NEW - Added in Filters to make it easier to add your own sorting format.
* NEW - Added filter to allow you to show/hide unavailable selections on multiple attributes.
* FIX - If a Variant is disabled it will no longer appear on the list.
* FIX - Takes note of Sale prices when reordering by Price.
* FIX - Works with multiple attributes at once.
* UPDATE - Now works with WordPress 5.1 and above.

= 1.4.1 =
* FIX - Fixed a fatal error when variation does not exist.
* FIX - Fixed an error where type became unreadable.

= 1.4 =
* NEW - The Stock Status will be checked before the variant is selected. You will no longer have an out of stock Default Variant
* NEW - Added the ability to set a minimum Stock Quantity limit if you manage stock manually.
* NEW - Added the ability to keep manually set defaults on a site wide basis.
* UPDATE - Now works with WooCommerce 3.5 and above.
* UPDATE - Now works with WordPress 5.0 and above.

= 1.3 =
* UPDATE - Now works with WordPress 4.9 and above.
* UPDATE - Now works with WooCommerce 3.3 and above.
* UPDATE - Works with PHP 7.0 and above.

= 1.2 =
* UPDATE - Now works with WordPress 4.6.2
* UPDATE - Now works with WooCommerce 2.6.4

= 1.1.1 =
* IMPROVEMENT - Removed ‘Choose an Option’ dropdown option.

= 1.1 =
* FIX - fixed Fatal Error on activation.

= 1.0 =
* UPDATE - Compatible with WordPress 4.4+
* UPDATE - Compatible with WooCommerce 2.4+
* IMPROVEMENT - Removed Templates and now using correct Functions/Filters for safer Updates

= 0.1 =
* Released Beta Version

== Frequently Asked Questions ==

= This plugin doesn't do anything =
Force Default Variant For WooCommerce is an extension of WooCommerce. Without at least WooCommerce version 2.4 installed and active then there will be no need for this plugin

= After activation I now get a Fatal Error where the Variant drop down should be? =
This is due to an outdated version of WooCommerce. If you update to the most recent version of WooCommerce your issue will be fixed.

= I can't see the options =
The options are within WooCommerce Settings. You can find it at the following location: WooCommerce -> Settings -> Products -> Variant.
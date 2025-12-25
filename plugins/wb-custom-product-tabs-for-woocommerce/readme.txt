=== Custom Product tabs for WooCommerce ===
Contributors: webbuilder143
Donate link: https://webbuilder143.com/?utm_source=wordpressorg&utm_medium=readme&utm_campaign=donate-link&utm_id=tabs-plugin&utm_content=donate
Tags: product tabs, tabs plugin, woocommerce custom tabs, woocommerce product tabs, tabs
Requires at least: 3.5.0
Tested up to: 6.9
Requires PHP: 5.6
Stable tag: 1.6.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create unlimited WooCommerce tabs and assign them in bulk by category, tag, brand, or product. Also disable WooCommerce’s default product tabs.

== Description ==

Need a customized tab for your WooCommerce products? The Custom Product Tabs for WooCommerce plugin lets you effortlessly add an unlimited number of tabs to your product pages.

You don't need to add tabs to every product manually. Instead, you can use global tabs and assign them to products based on Category, Tags, Brands, or individual products. Additionally, there's also an option to create product-specific tabs if needed.

Enhance your WooCommerce product pages today with Custom Product Tabs for WooCommerce!


== Features ==

✅ Unlimited Custom Tabs – Add as many custom tabs as needed for your WooCommerce products.
✅ Global Tabs – Create tabs that can be assigned to multiple products based on categories, tags, and WooCommerce brands.
✅ Category specific Tabs.
✅ Option to disable WooCommerce default product tabs such as the Description, Additional Information, and Reviews tabs.
✅ Product specific Tabs.
✅ Supports the default WordPress Exporter and the WordPress Importer plugin to migrate the tabs.
✅ Brand-Specific Tabs – Fully supports WooCommerce's default brand functionality and third-party brand plugins like [Perfect Brands for WooCommerce](https://wordpress.org/plugins/perfect-woocommerce-brands/).
✅ YouTube Embed Support – Easily embed YouTube videos directly within product tabs.
✅ Sortable Tabs – Organize tabs with a priority input field for better content arrangement.
✅ Multi-Language Support – Compatible with WPML and Polylang for multilingual stores.
✅ Rich Content Support – Add extra product images, detailed descriptions, videos, FAQs, and more.
✅ Shortcode Compatibility – Use WordPress shortcodes to insert dynamic content into tabs.
✅ Tab slug – SEO friendly tab slug. Create specific URL for each tab. Easy to share tab content.
✅ Developer-Friendly Hooks – Includes multiple hooks for customization and integration with third-party plugins.


== 💬 Help Us Translate! ==

Want to see this plugin in your language? [Contribute a translation](https://translate.wordpress.org/projects/wp-plugins/wb-custom-product-tabs-for-woocommerce/) and become a proud WordPress translation contributor. Your support makes a difference!


== How to Use ==

1. **Install & Activate the Plugin**  
   - Go to **WordPress Admin > Plugins > Add New**  
   - Search for **Custom Product Tabs for WooCommerce** (or upload the plugin ZIP)  
   - Click **Install Now**, then **Activate**  

2. **Create a New Product Tab**  
   - Navigate to **Products > Tabs**  
   - Click **Add New Tab**  
   - Enter a **Tab Title** and add content using the editor  

3. **Assign Tabs to Products**  
   - You can assign tabs to:  
     - **Specific Products**  
     - **Categories**  
     - **Tags**  
     - **Brands** (Compatible with WooCommerce brands and third-party brand plugins)  

   - Select the appropriate **Products, Categories, Tags, or Brands** in the assignment section  

4. **Product-Specific Tabs**  
   - When editing a product, go to the **Product Data** section  
   - Navigate to the **Custom Tabs** tab  
   - Click **Add New Tab**, enter the tab title and content  
   - These tabs will only appear on the selected product  

5. **Tab Settings**  
   - Go to **Settings > Product Tab Settings**  
   - Customize tab behavior, visibility, and default settings  
   - Configure the default order for global tabs   


== Translate tabs using Polylang ==
http://www.youtube.com/watch?v=F90dOxEjkE8


== Installation ==

= Via WordPress Dashboard =

1. Go to the Plugins menu and click Add New.
2. Search for **Custom Product Tabs for WooCommerce** (or upload the plugin ZIP)
3. Click **Install Now**, then **Activate**

= Via FTP =

1. Extract the wb-custom-product-tabs-for-woocommerce.zip file on your computer.
2. Upload the extracted wb-custom-product-tabs-for-woocommerce folder to the /wp-content/plugins/ directory on your server.
3. Go to the Plugins menu in WordPress and activate the plugin.


== Frequently Asked Questions ==

= Where can I add tabs to a product? =
To add tabs to a product, navigate to the product edit screen in WooCommerce. In the left corner of the Product Data box, you will find the "Custom Tabs" option.

= Can I disable WooCommerce default tabs? =
Absolutely. The plugin allows you to control which default WooCommerce tabs appear on product pages. You can enable or disable the Description, Additional Information, and Reviews tabs directly from the plugin settings.

= Can I add HTML content? =
Yes, you can add HTML content. The tab content section uses the standard WP editor, allowing you to include any HTML content supported by the WordPress post editor.

= How many tabs can I add to a single product? =
There is no restriction on the number of tabs you can add. You can include multiple tabs for individual products.

= What are global tabs? =
Global tabs are reusable tabs that can be assigned to multiple products based on their category, tags, brands.

= Why is my global tab not displaying on the front end? =
Ensure the global tab is correctly assigned to products via categories, tags, or brands. Additionally, you can modify the default behavior of global tabs in the plugin settings. The "Global Tab Behavior" option allows you to display global tabs on all products, even without assigning them to specific categories, tags, or brands.

= Where are the plugin settings located? =
You can find the "Product Tab Settings" menu under the "Settings" menu in the WordPress admin dashboard.

= How can I display global tabs on all products without assigning them to any categories, tags, or brands? =
To display global tabs on all products, visit the plugin settings and enable the "Global Tab Behavior" option. This will allow you to show global tabs across all products without needing to assign them to specific categories, tags, or brands.

= I am using a third-party brands plugin. Can I still assign tabs to products based on brands =
Yes, by default, our plugin supports WooCommerce's default brands. It also supports the [Perfect Brands for WooCommerce](https://wordpress.org/plugins/perfect-woocommerce-brands/) plugin. Additionally, you can add support for your custom brands plugin using the filter hook: wb_cptb_thirdparty_brand_taxonomies. If you need help adding support for your brand plugin, please reach out to us via the support forum.

= Can I arrange these tabs? =
Yes, you can arrange both product-specific tabs and global tabs according to your preference.
For a detailed guide on positioning product tabs, please visit this article: [How to Arrange WooCommerce Custom Product Tabs](https://webbuilder143.com/how-to-arrange-woocommerce-custom-product-tabs/?utm_source=wordpressorg&utm_medium=readme&utm_campaign=faq&utm_content=positioning).

= Is there any filter available to edit tabs? =
Yes, there are filters available to modify tab content and position. Developers can utilize these filters to programmatically manage the tabs.

= Is it possible to delete the tabs? =
Yes, you have the option to delete the tabs as needed.

= How can I add YouTube videos to the tab content? =
To add YouTube videos to the tab content, simply use the YouTube embed button located just above the tab content editor.

= How can I add an SEO-friendly URL slug for my tabs? =
On the tab edit screen, you have the option to manually enter a tab slug. Alternatively, you can generate a slug from the tab title.

= How do I translate tabs using Polylang? =
For a detailed guide on translating WooCommerce product tabs using Polylang, please visit this article: [How to translate tabs using Polylang](https://webbuilder143.com/how-to-translate-woocommerce-product-tabs-with-polylang/?utm_source=wordpressorg&utm_medium=readme&utm_campaign=faq&utm_content=polylang).

= How can I migrate my tabs from one site to another? =
Our plugin supports the default WordPress Export tool for exporting, and you can import using the [WordPress Importer](https://wordpress.org/plugins/wordpress-importer/) plugin.

You need to export and import Product-specific Tabs and Global Tabs separately, as they are custom post types.

Before importing Global Tabs, make sure to import Products, Categories, Tags, and Brands first. This ensures that all tab assignments are properly mapped to their corresponding items.


== Screenshots ==

1. Edit window for product specific tab
2. Global tabs and product-specific tabs on the product edit page
3. Global product tabs
4. Edit window for global product tab
5. General settings
6. Help & FAQs

== Changelog ==

= 1.6.4 =
* Tested with: WooCommerce 10.4.3

= 1.6.3 =
* [Fix] Description tab was automatically focusing on product page load. Thanks to @elkevandrunen for reporting the issue.

= 1.6.2 =
* [Fix] Custom tab not activating when changing URL hash.

= 1.6.1 =
* Tested with WP 6.9

= 1.6.0 =
* [Add] New option to disable WooCommerce default tabs.
* Tested with: WooCommerce 10.3.5

= 1.5.3 =
* [Improvement] Added compatibility with the WordPress Export tool and the WordPress Importer plugin by @wordpressdotorg.
* Tested with: WooCommerce 10.2.2

= 1.5.2 =
* [Fix] Resolved issue where WPBakery Page Builder block content was breaking inside tabs.

= 1.5.1 =
* Tested with WC 10.1.1

= 1.5.0 =
* [Improvement] Added compatibility with WPML. Thanks to @rainelement for your valuable contribution.
* [Improvement] Improved compatibility with WordPress block code.
* Tested with WC 10.0.4

= 1.4.3 =
* [Fix] Undefined variable `$tab_slug` @stefan1904 Thanks for pointing out the issue.
* Tested with WC 9.9

= 1.4.2 =
* [Fix] Global tabs assigned via categories, tags, or brands were limited to a maximum of 10. Now displaying all matching tabs. @RibbonVilla Thanks for pointing out the issue.

= 1.4.1 =
* [Fix] Product selection metabox is missing on the Global Tabs edit page.

= 1.4.0 =
* [Improvement] Compatibility with WooCommerce default brands.
* Tested with WP 6.8
* Tested with WC 9.8

= 1.3.5 =
* [Fix] Undefined array key 'slug' issue. Thanks @elasaleta for pointing out the issue.

= 1.3.4 =
* [Fix] Tab content validation failed in the editor's text view. Thanks to Richard for pointing out the issue.
* [Fix] Tab edit popup was hidden under the overlay in certain conditions. Thanks to Cheryl for helping us replicate and fix the issue.
* [Add] Option to add an SEO-friendly slug for tab URLs.

= 1.3.3 =
* [Fix] Custom tab not activating when changing URL hash.
* [Fix] Youtube embed popup position issue.

= 1.3.2 =
* Tested with WC 9.7

= 1.3.1 =
* [Tweak] Added the ability to assign global tabs to specific products.

= 1.3.0 =
* Tested with WC 9.6
* [Add] General settings page added. 
* [Add] Help and FAQ page added. 

= 1.2.5 =
* Tested with WC 9.5
* [Fix] Security vulnerability fixed.

= 1.2.4 =
* Tested with WC 9.4
* Tested with WP 6.7
* [Improvement] Polylang compatibility.
* [Compatibility] WooCommerce Brands, Plugin: Perfect Brands for WooCommerce.
* [Tweak] Tab headings are now visible by default.

= 1.2.3 =
* Tested with WC 9.3.3
* Tested with WP 6.6.2
* [Add] Added text color and background color options in the tab content editor.
* [Tweak] Displaying tab title along with tab nickname to avoid confusion on the editor screen.

= 1.2.2 =
* Tested with WC 9.3.2
* [Fix] Some CSS styles were being trimmed while saving the tabs.

= 1.2.1 =
* Tested okay with WC 9.1.2
* Tested okay with WP 6.6

= 1.2.0 =
* [Fix] The contents of the tab are cleared after editing via the text mode of the tab content editor.
* [Fix] Youtube embed width/height percentage values are converted to normal integers.
* [Add] Tab content now supports `div` tags.
* Tested okay with WC 9.0.2
* Tested okay with WP 6.5.5
* Tested okay with PHP 8.3.8

= 1.1.13 =
* Tested okay with WC 8.9
* Tested okay with WP 6.5

= 1.1.12 =
* [Fix] Tab option not available for Grouped product. @ginohanna Thanks for pointing out the issue.

= 1.1.11 =
* Tested okay with WC 8.5
* [Fix] Overriding the global post value.

= 1.1.10 =
* Tested okay with WC 8.4
* Tested okay with PHP 8.3

= 1.1.9 =
* Tested okay with WC 8.3
* Tested okay with WP 6.4
* [Tweak] New filter to exclude child category tabs in a product page. `wb_cptb_include_child_category_tabs`
* [Tweak] New filter to exclude parent category tabs in a product page. `wb_cptb_include_parent_category_tabs`

= 1.1.8 =
* Tested okay with WC 8.1
* Tested okay with WP 6.3
* Tested okay with PHP 8.2

= 1.1.7 =
* Tested okay with WC 7.9
* [Improvement] WPML compatibility improved
* [Tweak] The tab heading within the tab content will now be hidden by default. To display the tab heading, a new filter named wb_cptb_hide_heading_in_tab_content has been introduced.

= 1.1.6 =
* Tested okay with WC 7.8
* [Bug fix] Unwanted <br> tag in tab content
* [Improvement] Add new tab section

= 1.1.5 =
* Tested okay with WC 7.7
* Tested okay with WP 6.2.2
* [Improvement] YouTube embed option added
* [Bug fix] Validation for adding a tab fails when the tab content editor is in text mode
* [Bug fix] Slash issue in tab content

= 1.1.4 =
* Checked okay with WooCommerce High-Performance order storage (COT)
* Tested okay with WC 7.5
* [Bug fix] Divi layout builder is not loading. @focusaid, @wilsonorganisation Thanks for pointing out the issue.

= 1.1.3 =
* Added product category/tag columns in admin global tab listing page
* Tested okay with WC 7.3
* Tested okay with WP 6.1.1

= 1.1.2 =
* Added tab nickname option for product specific tabs
* Tested okay with WC 6.8
* Tested okay with WP 6.0.1

= 1.1.1 =
* Tested okay with WC 6.7
* Tested okay with WP 6.0

= 1.1.0 =
* Added tab nickname option for global tabs
* Help doc link added in Tab position section
* Tested okay with WC 6.3
* Tested okay with WP 5.9

= 1.0.9 =
* Tested okay with WC 5.5
* Tested okay with WP 5.8

= 1.0.8 =
* Tested okay with WC 5.4

= 1.0.7 =
* Tested okay with WC 5.2

= 1.0.6 =
* Tested okay with WC 5.1
* Tested okay with WP 5.7

= 1.0.5 =
* Tested okay with WC 4.9
* Tested okay with WP 5.6

= 1.0.4 =
* Tested okay with WC 4.5.2
* Tested okay with WP 5.5.1

= 1.0.3 =
* WooCommerce 4.4 Support added.
* WordPress 5.5 Support added.

= 1.0.2 =
* WooCommerce 4.0 Support added.
* WordPress 5.4 Support added.
* [Improvement] Global tabs added
* [Improvement] Tab position option added

= 1.0.1 =
* WooCommerce 3.9 Support added.
* [Improvement] WP editor added to tab content section

= 1.0.0 =
* Initial Version


== Upgrade Notice ==

= 1.6.4 =
* Tested with: WooCommerce 10.4.3
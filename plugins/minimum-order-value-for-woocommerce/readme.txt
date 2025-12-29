=== Minimum Order Value for WooCommerce ===
Contributors: griddeveloper7
Tags: woocommerce, minimum order, cart, checkout, minimum purchase
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Set a minimum order amount for WooCommerce with customizable cart/checkout messages and product/category exclusions.

== Description ==
Easily set a minimum order amount in WooCommerce. Display friendly, customizable cart and checkout messages that guide customers to meet your minimum order value.

This plugin helps store owners prevent low-value checkouts by enforcing a minimum order amount. It also lets you customize customer-facing messages and exclude specific products or categories from the minimum calculation.

This lightweight plugin works seamlessly with all major WooCommerce themes and builders (Elementor, Divi, Astra, Storefront). No coding required — just set your minimum spend, customize messages, and you're done. Perfect for stores that need to manage low-value orders or set minimum purchase requirements for wholesale customers.

For more information, feature requests, or the upcoming PRO version, visit our landing page: [grid-developer.vercel.app](https://grid-developer.vercel.app).

== Features ==
- **Set a global minimum order value** required to place an order
- **Dynamic cart notice** using placeholders `{minimum_amount}` and `{remaining_amount}`
- **Block checkout** until the minimum is reached
- **Exclude specific products and categories** from calculation
- **Smart select fields** with search for products and categories
- **Translation-ready**, includes built-in translations for 10+ languages (es_ES, fr_FR, de_DE, it_IT, pt_BR, nl_NL, ru_RU, ja, zh_CN, id_ID)

== Screenshots ==
1. Minimum Order settings section in WooCommerce (assets/screenshot-1.png)
2. Cart page notice showing remaining amount (assets/screenshot-2.png)
3. Checkout error message when below the minimum (assets/screenshot-3.png)
4. Product and Category exclusion pickers (assets/screenshot-1.png)

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/` or install via **Plugins → Add New**
2. Activate the plugin
3. Go to **WooCommerce → Settings → General** and find the **Minimum Order Settings** section
4. Set your minimum order value and customize messages as needed

== Frequently Asked Questions ==
= Where do I configure the minimum order value? =
Go to **WooCommerce → Settings → General** and scroll to the **Minimum Order Settings** section.

= What placeholders can I use in messages? =
Use `{minimum_amount}` for the required threshold and `{remaining_amount}` for how much more the customer needs to add.

= Does this block checkout if the order is below the minimum? =
Yes. Checkout validation runs until the cart total meets the configured minimum amount.

= Can I exclude certain products or categories? =
Yes. Use the Exclusions section to select products and/or categories that should not count toward the minimum.

= Will the minimum follow my store currency and formatting? =
Yes. The amounts displayed are formatted using your WooCommerce currency settings.

= Where can I request new features or learn about the PRO version? =
For feature requests or information about the upcoming PRO version, visit [grid-developer.vercel.app](https://grid-developer.vercel.app).

== Changelog ==
= 1.0.1 =
* Updated plugin description and FAQ with landing page link.

= 1.0.0 =
* Initial release: minimum order enforcement, customizable messages, product and category exclusions.
=== FunnelKit Automations Pro ===
Contributors: WooFunnels
Tested up to: 6.8.2
Stable tag: 3.6.5
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Change log ==

= 3.6.5 (Jul 23, 2025) =
* Fixed: Resolved an issue where plugin compatibility files were not loading correctly. (#2973)

= 3.6.4 (Jul 21, 2025) =
* Added: New action: Create WooCommerce Order. (#2644)
* Added: New merge tag 'upsell_offer_accepted_product_price' to retrieve accepted product prices in WooCommerce upsell offers. (#2931)
* Added: Gravity Forms: Spam check added for form submissions. (#2907)
* Added: New hook for handling WooCommerce order email resending, improving CRM email workflow flexibility. (#2918)
* Improved: Replaced date with date_i18n for localized date formatting in order summaries and subscription data. (#2947)
* Improved: Broadcast pause threshold increased to 20 consecutive email failures to improve campaign stability. (#2899)
* Improved: Error handling enhanced for MySQL row insertion during MySQL errors. (#2910)
* Improved: Engagement link tracking now includes noindex and nofollow headers. (#2913)
* Improved: Search functionality in Elementor improved by refining post type exclusions and focusing on post_title field. (#2901)
* Improved: Removed admin_enqueue_scripts action and refactored styling for improved layout and performance. (#2941)
* DOB field display: When the "Show if filled" setting is active, the DOB value now appears as readable text on the My Account page, even if the field is disabled. (#2962)
* Fixed: Link Triggers: "Add a note" action was not working in some cases – resolved. (#2940)
* Fixed: Corrected logic for 'is_blank' and 'is_not_blank' operators in rule evaluation. (#2921)
* Fixed: Conditional validation for disabled birthday fields refined to avoid unnecessary checks. (#2923)
* Fixed: Retained DOB field values from session and corrected discount calculation logic. (#2925)
* Fixed: Prevented blank UTM parameters from being appended to URLs. (#2937)
* Fixed: Subscription Created event now triggers correctly for FunnelKit upsell offer acceptance involving subscription products. (#2906)
* Fixed: Data insertion logic improved with format validation and enhanced error logging. (#2910)
* Fixed: Bulk Order Status Update: Resolved an issue where only the last WooCommerce order in a bulk selection was triggering the transaction email. (#2965)
* Fixed: Compatibility fixes for PHP 7.4 to ensure backward support. (#2968)

= 3.6.3 (Jun 13, 2025) =
* Improved: Admin Single Order View: Optimized FK Automation CRM contact metabox code for enhanced performance. (#2889)
* Fixed: Automation Merge Tag: Resolved issue where webhook data values were not displaying when keys were in decimal format. (#2886)
* Fixed: Broadcast Consecutive Failure Detection: Corrected errors in failure detection code logic and unpause the Broadcast. (#2891)
* Fixed: Birthday Field Translation: Fixed issue where month names on checkout page were not translatable. (#2895)

= 3.6.2 (Jun 11, 2025) =
* Fixed: Webhook Received Event: Resolved functionality issue preventing webhook received events from executing properly. (#2882)
* Fixed: Language Compatibility: Added support with the GTranslate plugin. (#2879)

= 3.6.1 (Jun 10, 2025) =
* Improved: Broadcast UX: Enhanced Broadcast experience. If consecutive failures occur during sending, the broadcast is automatically paused with an admin notification. (#2710)
* Improved: File Upload Paths: Made import/export file upload paths dynamic with site uploads path. (#2869)
* Improved: Contact Filters: Improved logic for identifying engaged audiences. (#2874)
* Fixed: Automation Logs: Webhook-received automation logs were not functioning correctly; this has been fixed. (#2870)
* Fixed: Date of Birth Field: Required check was respecting fallback setting for FB checkouts, it shouldn't, fixed. (#2868)
* Fixed: REST API: The Get all fields API endpoint now correctly returns all expected fields. (#2868)

= 3.6.0 (Jun 03, 2025) =
* Compatible upto WordPress 6.8.1
* Security: Third-Party Links: Restricted redirect links unless sent from email. (#2757)
* Security: Plugin Activation: Fixed unique key logic in FK Automation. (#2796)
* Added: Cart Export: Added a feature to export carts. (#2575)
* Added: Thrive Architect: Added compatibility with 'Thrive Architect' form integration. (#2810)
* Added: Advanced Coupons: New merge tags for 'User Total Points' & 'User Unclaimed Points'. (#2791)
* Added: DOB Field Option: New option to hide the DOB field if it’s already available for a contact. (#2743)
* Improved: General: Overall product improvements with core features restricted to specific areas. (#2794)
* Improved: Elementor Forms: Performance improvements for the 'Form submits' event schema. (#2836)
* Improved: REST API:
    - Made endpoints compatible with multisite. (#2803)
    - Optimized code and responses. (#2771, #2762)
* Improved: Duplicate Handling: Extra handling for duplicate entry insertion. (#2860)
* Improved: WooCommerce Emails: Optimized transactional emails to run later during the call. (#2858)
* Improved: Cart Block: Added tax suffix and corrected prices with taxes. (#2843)
* Improved: Importers: Improved contact import to run in sync on the progress screen. (#2846)
* Improved: Broadcast:
    - Multiple UI improvements for a better experience. (#2840)
    - Multiple code improvements for faster execution. (#2838)
* Improved: LiteSpeed Cache: Updated compatibility. (#2831)
* Improved: Engagement Meta Query: Optimized for better performance. (#2825, #2805)
* Improved: 'Add to Automation': Now shows only automation events that can be added manually. (#2821)
* Improved: API Handling: Added handling for 403 errors when getting HTML content. (#2788)
* Improved: Thrive and LearnDash: Updated various events schema for better performance. (#2783)
* Improved: Order and Cart Block: Support for item meta data added. (#2775, #2773, #2753)
* Improved: Order Status: Updated schema for the 'Pending' event. (#2750)
* Improved: Forms API: Optimized list and single form APIs for faster loading. (#2783)
* Improved: New Email Editor: Improved to reduce CORS errors. (#2748)
* Fixed: HTTP Post Action: PHP warning resolved. (#2856)
* Fixed: Date Format Issues: Fixed in order and downloadable product blocks. (#2853, #2844, #2818)
* Fixed: Conditional Step: Fixed error in date condition step. (#2830)
* Fixed: Contact Confirmation: Fixed redirection issue and updated preview data. (#2811)
* Fixed: Upsell Offer: Fixed core class checking error in upsell offer links. (#2816)
* Fixed: Subscriptions: Added subscription status check before renewal. (#2813)
* Fixed: Contact Filter: Added string support for list_any operator. (#2809)
* Fixed: PHPCS & Plugin Rank: Fixed errors and warnings. (#2801, #2798)
* Fixed: Update User Role: Fixed issue when user email changes during automation. (#2758)
* Fixed: DOB Field: Fixed validation issue. (#2746)
* Fixed: Conditional Rule: Fixed 'phone number starts with' operator issue. (#2741)
* Developer Updates: Mail Headers: Added filter hook to update mail headers. (#2769)

= 3.5.2 (Mar 06, 2025) =
* Compatible upto WordPress 6.7.2
* Compatible with PHP 8.3
* Added: Breakdance Integration: New integration added. Form submission event now available in automations and forms. (#2657)
* Improved: WooCommerce Transactional Emails: Native action hook woocommerce_email_after_order_table is now called to ensure compatibility with third-party plugins. (#2683)
* Improved: Emails: Zoom link is now excluded from tracking clicks. (#2696)
* Improved: Emails: Order summary block now displays SKU in preview content. (#2692)
* Improved: First-Time Setup: Optimized the initial code execution after plugin installation. (#2725)
* Improved: Contacts Export: Improved handling for rare cases. (#2630)
* Fixed: WooCommerce Transactional Emails: Fixed issue where the name wasn't appearing in third-party PDF invoices. (#2690)
* Fixed: SMTP Plugin Compatibility: Resolved an intermittent issue with email sending. (#2705)
* Fixed: Broadcast Test Email: Fixed UTM data issue. (#2695)
* Fixed: Emails: Fixed responsive setting issue with the product block. (#2700)
* Fixed: Broadcast Analytics: Smart sending analytics were not displaying correctly on the analytics screen, now fixed. (#2716)
* Fixed: Transactional Emails: Resent email handling improved for cases where engagement dynamic merge tags were removed. (#2719)

= 3.5.1 (Feb 11, 2025) =
* Added: Introduced the missing hook `woocommerce_email_after_order_table` in the order summary block for better customization options. (#2683)
* Fixed: Ensured UTM parameters are correctly appended to emails for accurate tracking. (#2683)

= 3.5.0 (Feb 10, 2025) =
* Compatible upto WordPress 6.7.1
* Compatible upto WooCommerce 9.7.1
* Added: New form integration: Forminator Forms. (#2642)
* Added: WooCommerce Transaction Emails: Support for failed order notifications for customers. (#2597)
* Added: New merge tag: 'Funnel Builder Conversion Data' added. (#2614)
* Added: New feature to track advanced logging, which can be activated under Tools > Advanced. (#2092)
* Improved: Action: Create FunnelKit Contact: A new setting added to update contact data if it already exists. (#2636)
* Improved: FunnelKit Form Submission Event: Created coupons are now saved in the automation data, making them accessible on the thank-you page. (#2632)
* Improved: Contact Filters: Added support for multiple values in the City filter, separated by commas. (#2639)
* Improved: Broadcast: Improved user experience with smart sending-related messages. (#2647)
* Improved: Adjusted the email pre-header spacing. (#2649)
* Improved: WooCommerce Transaction Emails: Third-party plugin attachments, like invoices, are now included in emails. (#2653)
* Improved: WooCommerce Transaction Emails: Fixed the formatting of items in the order summary block. (#2661)
* Improved: Email SMTP Providers: Added handling for soft bounces and complaints via webhook. (#2590)
* Improved: Formidable Forms: Added support for hidden fields in selection. (#2665)
* Improved: Automation Actions: Enhanced "Add Tag" and "Add List" actions to allow multiple values separated by commas. (#2677)
* Improved: Optimized database calls for better performance. (#2679)
* Improved: Product Block in New Email Visual Builder: Now excludes hidden and search-only products. (#2669)
* Fixed: WooCommerce Transactional Emails: Addressed warnings related to width in emails. (#2622)
* Fixed: WooCommerce Transactional Emails: Fixed missing order item metadata in the order summary block. (#2626)
* Fixed: WooCommerce Transaction Emails: Corrected UTM data syntax. (#2670)
* Fixed: Automation Action (Stripe Offers): Improved the "Create Offer" action to handle scenarios where the automation runs multiple times. (#2673)
* Fixed: Resolved a PHP warning that occurred during contact exports. (#2619)
* Dev: Added the filter hook bwfan_subscription_product_types to modify the product type for subscription product searches. (#2634)

= 3.4.0 (Dec 05, 2024) =
* Compatible upto WordPress 6.7.1
* Compatible upto WooCommerce 9.4.3
* Added: Stripe Recovery Offer, a new feature launched.
	Pitch Funnel Builder Offer after the buyer rejects the Bump or Upsell product during checkout.
	Send the Recovery link to any medium and allow multiple follow-ups.
* Added: WooCommerce Transactional Emails simplified.
	Design WooCommerce transactional email with the power of FunnelKit Automation's new block builder.
	Track transactional email opens and clicks.
	Allow resending emails with one click.
* Added: Email History, a new feature added. List all the emails sent from the FunnelKit Automation system (#2493)
* Added: Compatibility added with the 'Brazilian Market on WooCommerce' plugin. It has a native 'date of birth' field, which comes on checkout but wasn't updating to contact. (#2571)
* Improved: Automation Merge tag: Jetpack compatibility improved, new shipment-related merge tags added. (#2548)
* Improved: Emails: Pre-header related improvements done. (#2563)
* Improved: Automation Email: Cart Recovery Link block: Passing language of the cart to form the recovery link. (#2576)
* Improved: Bounce handling improved for Postmark ESP. Allow status change to 'soft bounce' or 'complaint.' (#2585)
* Improved: Contact Filter: FunnelKit Optin Form submitted filter code improved. (#2570)
* Fixed: Automation Merge tag: Error handling was done for the order tracking merge tag. (#2552)
* Fixed: Automation Contact Import: Error handling in case contact is not found. (#2554)
* Fixed: Automation Email: Order block, case handled for refunded data, tax data related changes, and allow 3rd part hooked in data in the email. (#2559, #2561, #2578)
* Fixed: Automation Email: New Block builder, conditional blocks with accented characters data, display issue fixed. (#2567)
* Fixed: Link Trigger: Update field action for field status wasn't working, fixed. (#2557)
* Fixed: Contact Export code optimized. Sometimes, contacts are duplicated in the exported file. Fixed. (#2573)
* Fixed: PHP error handling for birthday field update from the edit profile screen. (#2598)
* Dev: Filter hook 'bwfan_disabled_autologin_roles' was added to disallow auto login for user roles. (#2583)

= 3.3.0 (Oct 22, 2024) =
* Compatible upto WordPress 6.7.0
* Compatible upto WooCommerce 9.4.0
* Security: SQL injection improvement. (#2508)
* Added: Setting: A new setting was added to optimize the engagement tracking table. Keep engagements dynamic merge tags data of xx days in the database and optimize the rest of the records. (#2479)
* Added: Automation event: Two new events, 'Referral signup' & 'Referral approved,' were added in AffiliateWP integration. (#2532)
* Added: Compatibility added with the 'WooCommerce Orders Tracking Premium' plugin. Merge tag added in automation. (#2503)
* Added: Automation merge tag: Learndash new merge tag 'Enrolled course IDs' added. (#2523)
* Added: Automation rule: Order item SKU new rule added. (->#3433)
* Added: Contact listing: Bulk action now supports automation trigger setting. (#2501)
* Added: Weekly admin notifications added. Advanced analytics over emails.
* Added: Automation delay step: Delay with events variable options are added for subscription status change event. (#2512)
* Added: Admin app: Contact export flow optimized. Allow cancelling export. Exports listing showing. (#2483)
* Added: Admin app: View docs link added in the Rest API under Tools. (->#3232)
* Added: Pro: Validating email for missing URLs, Broken links, Email size, and Unsubscribe tag. (->#3090)
* Improved: Email: Order summary block. Supporting 3rd party plugins to append fees etc. (#2475)
* Improved: Automation rules: Funnels rules group name changed. (#2496)
* Improved: Bounce handler: Sendinblue label changed. (#2498)
* Improved: Email blocks, conditional workflow with attributes code improved. (#2518)
* Improved: Email: Modified two native hooks that is breaking the HTML structure. (#2528)
* Improved: Automation action: Made 'Cancelled product subscription' action generic, coming on all events. (#2533)
* Improved: Email: Improved support of languages in order and cart blocks. (#2530)
* Improved: Broadcast: Send to un-open query improved, disallowed further unsubscribed contacts. (#2513)
* Improved: Automation: Condition and Goal step when tag or list related options selected. Fetch the latest names. (->#3489)
* Improved: Automation rules: Order rules group heading renamed. (->#3221)
* Fixed: Rest API modal class minor fixes. (#2477)
* Fixed: AWS: Automatically marking status cancellation code has some issues, fixed. (#2487)
* Fixed: Automation rules: PHP error handling in membership plan rules. (#2510)
* Fixed: Bulk action: Restart automation action wasn't working, fixed. (->#3420)
* Fixed: Non-published products were coming in the Product block API. Fixed. (#2520)
* Fixed: Legacy automation: The fetch tags API wasn't working, so it's fixed. (#2525)
* Fixed: License activation on a multisite environment-related fix. (->#3467)
* Fixed: Email: The subscription interval wasn't showing below the order summary in the email. Fixed. (#2536)
* Fixed: Automation action: SMS-related analytics has some improvements. (#2522)
* Fixed: Admin app: Broadcast screen 2nd step, code improvements. (->#3496)
* Fixed: Automation rule: Contact gender is blank operator wasn't working, fixed. (#2540)
* Dev: A filter hook 'bwfan_exclude_reorder_status' to disallow orders with any status for reorder via reorder last order merge tag. (#2545)

= 3.2.1 (Sep 03, 2024) =
* Fixed: Automation Merge Tags: Learndash merge tag had a typo error, causing PHP error. Fixed.

= 3.2.0 (Sep 02, 2024) =
* Compatible upto WordPress 6.6.1
* Compatible upto WooCommerce 9.2.3
* Added: New Automation Action, delete contact added. (#2448)
* Added: Compatibility added with currency related plugins and a tool to reindex cart and conversions total amount. (#2418)
* Improved: Email: Cart link block CSS improvement for mobile. (#2428)
* Improved: Automation Rules: WooCommerce rules - related to order, code optimized. (#2433)
* Improved: Optimization in open click analytics queries. (#2450)
* Improved: Automation Event: Webhook received, code further optimized to take care of url encoded data. (#2460)
* Improved: Static translations made dynamic. (#2444)
* Improved: Automation Event: Subscription renewal failed, code further optimized. (#2462)
* Improved: Automation Event: Customer before card expiry, code optimized. Is subscription active checking added. (#2458)
* Improved: Automation Action: Create WP User, option to set username is added. (#2456)
* Improved: Automation Action: Cart items merge tag, images are linked with recovery link. (#2419)
* Fixed: Automation Rules: Some filters search results were not coming as per the search string, fixed. (#2426)
* Fixed: Contacts Import: Extra handling in case export file folder is not present. (#2442)
* Fixed: Email: Product block, new settings added. (->#3339, #2454)
* Fixed: Birthday field in WooCommerce 'My account' area, required checking fixed. (#2440)

= 3.1.0 (Jul 16, 2024) =
* Compatible upto WordPress 6.6.0
* Compatible upto WooCommerce 9.1.2
* Added: New Bulk action feature on Contact listing screen. Ease for admin to perform quick operations. (#3056, #2356)
* Added: New integration with the 'TI Woocommerce Wishlist' plugin. Wishlist Items on sale and Reminder events added. (#2348)
* Added: Contact two new statuses added: Soft Bounce and Complaint. Auto integration with AWS bounce handling. (#2303)
* Added: Automation Rules: WooCommerce Product attributes rules added. (->#3070)
* Added: Automation Action: 'Create coupon' action improvements: Allow selecting existing coupon to inherit its properties. (->#3077, #2316)
* Added: Automation Rules: Webhook received event, webhook data 'is empty' or 'is not empty', new operators added. (#2338)
* Added: Automation Event: FunnelKit Optin form submission, new setting added to allow A/B experiment running steps submissions. (#2320)
* Added: Contact Fields merge tags added in Broadcast, Contacts export, Bulk Action filters. (#2333)
* Added: Automation Rules: Order subtotal new rule added. (->#3061)
* Added: Automation Rules: Segments conditional rule added in WishList Member events. (#2393)
* Added: Automation Merge tag: Contact last order date merge tag added. (#2327)

New Email Editor:
* Added: Allow modifying global settings from the email builder screen. Added button padding, button border, social icon style, heading font size. (->#3102)
* Improved: Enhanced auto-apply coupon functionality with the Cart Link using the Coupon block. (->#3105)
* Improved: Made Order Summary and Cart Items block strings editable. (->#3055)
* Improved: WP image size setting in Product, Cart Items and Order Summary blocks. (#2339)
* Improved: Allow custom fonts in the editor using filter hook 'bwfan_block_editor_custom_fonts'. (->#3064)
* Improved: Subscription information in the Order Summary block. (->#3089)
* Improved: Issue found with Save layout button, extra handling added. (->#3178)
* Improved: Tax-related improvement in the order & cart items blocks. (#2363)
* Improved: Decoding merge tags code optimized. (#2373)
* Improved: Product block code is improved for faster execution. (#2359)
* Fixed: Wrong shipping label and taxes related improvement done. (#2386)

* Improved: App code optimized to increase page load speed. (#2369)
* Improved: Automation Goals: Contact subscribed, Tag and List added goals configuration pre-checked in the case already pre-occurred. (#3231, #2402)
* Improved: Automation Event: FunnelKit Optin form submission, auto-optimize phone number value for spaces, hyphen etc. (#2341)
* Improved: Automation Rules: Order-related rules code optimized. (#2337)
* Improved: Automation Rules: Marketing status rule readable text minor improvements. (->#3071)
* Improved: Automation Actions: Send email action, disallow click tracking on social media links and mobile-friendly app links. (#2380)
* Improved: Broadcast: Additional handling during immediate execution, code improved. (->#3185)
* Improved: Bulk Actions UI improvement. More user friendly experience. (->#3052)
* Improved: Automation Rules: Allow searching of membership in WC Membership rule. (#2343)
* Fixed: Broadcast admin UI improvements. (->#3040)
* Fixed: Automation Rules: The WP Forms field rule value for the checkbox field for 'contains' operator wasn't working; Fixed. (->#3225, #2392)
* Fixed: Automation Merge tag: Cart recovery link block with dynamic coupon wasn't working, fixed. (->#2401, #2306)
* Fixed: Automation Rules: The day of a week rule wasn't working for non-english sites, fixed. (#2371)
* Fixed: Broadcast: Send to unopen 2nd level broadcasts. The stats weren't showing in the listing, fixed. (#2383)
* Fixed: License issues on a multi-site setup where the license is not activated on the parent site. (->#3206)
* Fixed: Automation Rules: FunneLkit Upsell offer accepted event: The ordered product rule wasn't working; fixed. (->#3153)
* Fixed: Automation Event: Customer winback event code improved. (#2325)
* Fixed: Rest API: Assign Tag, stop hooks condition wasn't working, fixed. (#2397)

= 3.0.3 (Jun 06, 2024) =
* Improved: Translation related improvements done. Some static strings are made translatable. (#2299)
* Improved: New email editor: Order summary and cart items block code improvement. (#2304)
* Fixed: New email editor: The coupon block with the dynamic automation coupon wasn't working, so it's fixed. (#2301)
* Fixed: Bulk actions: Remove tag and lists weren't working, fixed. (#2312)

= 3.0.2 (May 22, 2024) =
* Compatible upto WordPress 6.5.3
* Compatible upto WooCommerce 8.9.1
* Added: Forms > Is transactional email, new setting added. (#2257)
* Added: Automation ended events logged. There are multiple ways to end automation. Visible in the contact journey area. (#2124)
* Improved: New Block Editor improvements. Order Summary block supports advanced data. Product block, sorting, and feed type improvements. (#2259)
* Improved: Contact CSV import > Supports creating contacts if they do not exist. (#2281)
* Improved: Translation related improvements. (#2261, #2293)
* Improved: User login code error handling in case some plugins pass the wrong arguments. (#2253)
* Improved: The contacts export file name is now more readable. (#2291)
* Improved: Broadcasts: In case of transactional email: merge tags and validation message updated. (->#2996)
* Fixed: New Block Editor, cart block image issue, fixed. (#2266)
* Fixed: Automation condition: The Optin form field rule wasn't working, and there was a string lowercase issue. Fixed. (#2273)
* Fixed: Automation action > Update custom field. Addition or subtraction in a number field, code optimized. (#2289)
* Fixed: Automation merge tag > View in browser case handled for new Block Editor. (#2287)
* Fixed: PHP warning was coming, fixed. (#2285)
* Fixed: Bulk Action: Single action selection error handling fixes. (->#2951)
* Fixed: SMS Broadcast a couple of issues fixes. (->#2971)
* Fixed: Automation > Conditional step spacing corrected. UI issue. (->#3006)
* Fixed: Broadcast > Unsubscribe link code is improved. (->#3010)
* Fixed: New Email builder > Mobile visibility issue fixed. (->#3015)
* Fixed: Bulk action admin flow improved. (->#3020)
* Fixed: The old visual email editor and merge tags modal formatting issue were fixed. (->#3030)

= 3.0.1 (Apr 24, 2024) =
* Fixed: Automation: Create coupon action, coupon type checking code corrected. (#2240)

= 3.0.0 (Apr 23, 2024) =
* Compatible upto WordPress 6.5.2
* Compatible upto WooCommerce 8.8.2
* Added: New Improved UI/ UX for the whole app.
* Added: New Intuitive Drag-and-Drop Email Builder.
    - Pre-built 40+ emails templates and layouts.
    - Global styles which can be used in all emails.
    - 10 WooCommerce blocks for emails.
    - Saving custom layouts and templates that are reusable.
    - Conditional Content in New Email Builder
* Added: Automation new step added: Split path step. Allows you to perform A/B testing in automation.
* Added: Add contact to Automation feature is added.
* Added: Automation contact activity to manage contacts in automations. One view to see all automation contacts. Failure or Skipped reasons displayed upfront.
* Added: Rest API functionality introduced. Allows to manage contacts, lists, tags and fields.
* Added: Comprehensive contact profiles.
* Added: New filter 'WooCommerce: First order days' added. Suitable to create RFM related audiences. (->#2911, #2233)
* Fixed: Automation action: 'Create coupon' - coupon type handling added. (#2224)
* Fixed: PHP warning fixed on login. (#2218)
* Fixed: Modify lists description is not working, fixed. (#2226)
* Fixed: Automation action: 'Add product in Subscription' not picking the default price if kept blank. Fixed. (#2235)

= 2.8.5 (Apr 5, 2024) =
* Compatible upto WordPress 6.5.0
* Compatible upto WooCommerce 8.8.0
* Added: Automation action: New action 'Add Product in the Subscription', 'Remove Product from the Subscription' and 'Cancel User Subscription' added for WooCommerce Subscription. (#2169, #2177)
* Added: The DOB field section wasn't showing on the WooCommerce single order page, added. (#2185)
* Added: Automation: FunnelKit Funnel Builder {{ecom_tracking_data}} merge tag. Funnel Name attribute added. (#2193)
* Improved: The unsubscribe link creation code is improved. (#2175)
* Improved: Automation admin view when many steps (over 100) are used. The code is improved. (->#2845)
* Improved: Bulk action execution code improved. Performance improvement. (->#2869)
* Improved: WooCommerce HPOS related improvements. (#2199, #2201)
* Fixed: Rare scenario where broadcast email data isn't sustained when admin moved back and forth in steps. Fixed. (->#2856)
* Fixed: Automation rule: Learndash 'Has course started' & 'Course progress' rules code is improved. (#2178, #2183)
* Dev: Filter hook 'bwfan_link_trigger_target_url' added to modify the link trigger end URL. (#2191)

= 2.8.4 (Mar 12, 2024) =
* Added: Automation rules: WP Forms and other forms, 'is empty' & 'is not empty' operators added in field value rule. (#2166)
* Fixed: Contacts: Fetching subscribed only contacts query had issues, fixed. (#2171)
* Fixed: FunnelKit optin form link on the single contact going to 404 link. (#2164)

= 2.8.3 (Mar 6, 2024) =
* Compatible upto WordPress 6.4.3
* Added: Broadcast: Contact ID merge tag support added. (#2110)
* Added: Contact filers: Tags and Lists, is empty and is not empty operators added. (#2156)
* Added: Automation Merge tag: New merge tag 'Entry ID' added in 'Fluent form submission' event. (#2130)
* Added: Automation Event: Subscriptions Status Changed - Any or specific settings added. (#2136)
* Added: Automation Condition: New rule 'Reviewed product' is added for the WooCommerce Review received event. (#2159)
* Added: Automation Merge tag: New merge tag 'Reorder last order' is added. (#2152)
* Added: Connector Support: Twilio: Allow URL shortening feature if activated at Twilio's panel. (->#2786)
* Improved: Auto appending UTM or tracking links in email, code optimized. (#2099)
* Improved: WooCommerce product search, draft status products were not coming to search for selection, added. (#2112)
* Improved: Automation Event: Elementor Popup form code improved, added logs when no form fields were fetched. Logs available in Tools > Logs. (#2107)
* Improved: Bulk actions: Tag and Lists related actions code is improved. (#2127)
* Improved: PHP 8.2 compatibility fixes. (#1928, #2146, #2149)
* Improved: Contact filters: FunnelKit Checkout page query improvement. (#2101)
* Improved: Automation Actions: Send email action code improvement during engagement template. (#2134)
* Improved: Automation Goals: Scenario when cart abandonment automation has order created goal. Contact is reaching the goal now. (->#2773)
* Fixed: Automation Condition: Contact Form 7 rule was dependent upon the WooCommerce, fixed. (->#2824)
* Fixed: Contact orders fetch call code improvements when HPOS is active. (#2138)
* Fixed: The contact status column value in the contact listing appeared wrong in case the status filter is used. (#2117)
* Fixed: Pro: Bulk Actions single page, showing the wrong created on date, fixed. (->#2791)

= 2.8.2 (Feb 1, 2024) =
* Fixed: Automation event: Thrive form submits, sometimes fields were not coming upon form selection. Data was coming in a different structure. (#2093)
* Fixed: Automation event: Elementor form submits, PHP warning was coming in a rare case, handled. (#2095)

= 2.8.1 (Jan 24, 2024) =
* Improved: Removed list-unsubscribe from unsubscribe link's query argument. (#2086)

= 2.8.0 (Jan 23, 2024) =
* Added: One-click unsubscribe feature support is added. Works with Google, Yahoo and Apple emails. (->#2669)
* Added: New Automation event 'Contact Bounced' is added. (#1991)
* Added: Compatibility added with NextMove Thank you plugin. Create NextMove Coupon code merge tag. (#2026)
* Added: Added: New merge tag 'Affiliate coupon' added. (#2022)
* Improved: Contact filters: Broadcast Send and Contact unsubscribed query optimized. (#1977)
* Improved: Contact object code optimization. (#1987)
* Improved: Automation event: Webhook received sending 200 ok response. (#1988)
* Improved: Automation action: Update user meta, keys having merge tags are not decoding, fixed. (#1980)
* Improved: Automation events: Form related events, when no forms available, showing a proper message. (#2002)
* Improved: Automation: Learndash, get group leaders by group ids function code modified. (#2023)
* Improved: Automation event: Subscription created, products fetch call code is improved, variations are coming for selection. (#2029)
* Improved: Automation merge tag: Woo shipment tracking merge tag code improved. Returning multiple tracking numbers and formatted outout. (#2017)
* Improved: Automation event: Elementor form submits event, was not working for global forms, options added. (#2052)
* Improved: Automation goals: Tag and List related goals code improved. (#2072)
* Improved: Automation event: Customer Winback event, code is improved. (#2074)
* Improved: Automation action: Update field, allowing merge tag usage on number field. (->#2736)
* Fixed: Automation run count against contact for card expiry event wasn't working, fixed. (#1975)
* Fixed: Automation action: Send email, step ID wasn't going in unsubscribe link, fixed. (#1993)
* Fixed: Broadcast: Send to unopen option contacts fetching query optimization. (#1998)
* Fixed: Contact filter: WP User role query code optimized. (#2000)
* Fixed: Contact fields: Check for array for checkbox field type only. (#2005)
* Fixed: Conflict with external plugin, blocking the redirect, fixed. (#2013)
* Fixed: Contact listing, DOB field value wasn't showing correctly, fixed. (#2012)
* Fixed: Audience exclusion query code optimized. (#2016)
* Fixed: Automation event: WooCommerce HPOS related improvements. (#2033)
* Fixed: Automation event: Fluent form submit: first name, last name & phone fields data wasn't updating, fixed. (#2065)
* Fixed: Ajax callbacks cache related improvements. (#2063)
* Fixed: Contact direct email and Forms email, excluding bounced contacts. (#2076)
* Fixed: Bulk Actions: sometimes contact selection error message was printing, code improved. (->#2688)
* Dev: Filter hook 'bwfan_exclude_click_track_urls' to disallow URL in email for click tracking. (#1996)

= 2.7.0 (Nov 7, 2023) =
* Compatible with WordPress 6.4
* Improved: Automation Event - Allow showing Divi multiple forms for selection in event or Forms. (#1932, #1948)
* Improved: HPOS related improvement. Broadcasts conversion attribution issue, Fixed. Contacts import WC Orders fixed. (#1916, #1962)
* Improved: Broadcast admin overview page, query improved. (#1965)
* Improved: Broadcast - Fetching scheduled broadcast by execution time code changed. (#1938)
* Improved: Automation action: Change affiliate rate and status actions are now open, and work globally. (#1903)
* Fixed: Contact filters - Radio field values if contains accent characters, sometimes values encoded during save. (#1875)
* Fixed: Automation Events - Events level settings, some fields marked required. (#1917)
* Fixed: Forms - Update blank field values code corrected. (#1913)
* Fixed: Contact Tags and Lists, rename code modified. (#1919)
* Fixed: Automation Event - Formidable form submission has a redirection issue, fixed. (#1940)
* Fixed: Broadcast contact name merge tags decoding issue, fixed. (#1951)
* Fixed: Forms submission code improvements. (#1956)
* Fixed: Contact Audiences - Add blank error state fixed. (->#2543)
* Fixed: Automation - Jump step > End automation option selected code corrected. (->#2540)
* Fixed: Automation - When multiple Goals used over 15, caused an issue, fixed. (->2584)
* Fixed: Visual builder email editing mode, sometimes doesn't save when saved multiple times, fixed. (->#2599)

= 2.6.0 (Sep 18, 2023) =
* Compatible with WordPress 6.3
* Compatible with WordPress 6.3.1
* PHP 8.2 compatibility related fixes. (#1831)
* WooCommerce HPOS compatibility added. (#1625)
* Added: Compatibility added with 'Yith Wishlist' plugin. Events, Rules & Merge tags. (#1810)
* Added: Automation event: WC Membership created & Subscription created - Any or specific settings added. (#1696, #1756)
* Added: Contact: New filter 'Optin form submitted' added. (#1723)
* Added: Automation action: FKA Update Contact field, allow timezone field update. (#1765)
* Added: Automation action: HTTP Request action: Allowing sending nested data. (#1844)
* Added: Learndash integration: New rules added 'Courses Completed', 'Courses Started' & 'Has Course Completed'. (#1789)
* Added: Automation rule: Order item data i.e. custom field new rule added. (->#2465)
* Added: Automation merge tag: New tag 'View In Browser' added for send email action. (#1752)
* Added: Automation delay step: Added support of order item data to choose variable for delay in product purchased event. (->#2471)
* Improved: FKA lite version handling with Pro. (#1733)
* Improved: Automation contact journey UX improved. More informative. (->#2279)
* Improved: Tag and List API call code improved, now giving results order by name in case of similar names. (#1718)
* Improved: Contact 'Total Spent' column code optimized. (->#2306)
* Improved: FunnelKit Automation admin app JS base slug updated. Now 'autonami-app'. (#1732)
* Improved: Automation event: WC Subscription before renewal code is improved. Execution timely in case subscriptions count is more. (#1717)
* Improved: Automation action: Update user display name and nickname default when a user is created by FKA. (#1707)
* Improved: Automation rule label, allow nice names in UI. (->#2316, ->#2336)
* Improved: Automation rule: New operators 'text contains', 'text does not contains' added in 'Used coupon' rule. (->#2318)
* Improved: Automation rule: WooCommerce product variation are not coming in the search input. (->#2323)
* Improved: Automation rule: 'Used coupon' new operators added. 'Text contains and does not contains'. (#1726)
* Improved: Automation rule: Contact fields, some fields doesn't have options, hence was not saving, fixed. (#1768)
* Improved: Contact single, email preview code optimized. (#1727)
* Improved: PHP notices removed. (#1730)
* Improved: On successful renewal order, do not attribute conversion. (#1770)
* Improved: Contact listing: Added handling in case selection column field deleted. (#1790)
* Improved: Auto heal table collation database error if found during contact search. (#1799)
* Improved: Contact fields: Restrict creation of fields if reserved keys name used. (#1805)
* Improved: WooCommerce active checking added in an API. (#1807)
* Improved: Broadcast campaign stats API code improved during A/B mode. (#1817)
* Improved: Automation action: Create coupon action: Limit x item setting is added. (#1824)
* Improved: Contact export query improved. (#1828)
* Improved: Contact: Send email from note: Added handling in case Email 'From Name' is not set. (#1838)
* Improved: Contact notes: Displaying note text in rich text format. (#1847)
* Improved: Automation rules: New operators added in contact fields with type radio, date & checkbox. (#1849)
* Improved: Link triggers: Redirect URL related code improvement. (#1867)
* Improved: Performance optimization, speeding up broadcast execution speed. (#1871)
* Improved: Contact single page, address field component code optimized. (->#2404)
* Improved: Automation single page UI handling in case a step if unavailable. (->#2409)
* Improved: FKA Pro: SMS Analytics: Display sms body in place of subject. (->#2441)
* Improved: Automation delay step: If skip to step doesn't exists, error handling done and letting contact proceeds further in automation. (->#2476)
* Improved: Automation event: Webhook received event, webhook data handling of more data types. (->#2481)
* Fixed: WP Mail SMTP compatibility improved. Supporting smart routing. (#1720)
* Fixed: Contacts import: WordPress users with a specific role, was importing all contacts, fixed. (#1758)
* Fixed: Contact Tags and Lists were recreating during update if same exists, fixed. (#1764)
* Fixed: Contact listing, unsubscribed contacts filter query has issues, fixed. (#1778)
* Fixed: Automation rule: Contact fields rule was not working when field is a number and value is 0, fixed. (#1792)
* Fixed: Broadcast: Send to unopen feature was showing the wrong total count, fixed. (#1820)
* Fixed: Automation rule: Elementor form field rule was not displaying the hidden fields, fixed. (#1822)
* Fixed: Contact filter: Contact created filter code improved. Site timezone was affecting the results. (#1856)
* Fixed: Contact listing: List column is used alone showing wrong output, fixed. (#1858)
* Fixed: Automation rule: WooCommerce - Total Orders count, has issue when count is 0, fixed. (#1876)
* Fixed: Forms: Merge tags are not decoding in preview text, fixed. (#1880)
* Fixed: Automation event: Webhook received event was not receiving data from thrivecart, fixed. (#1881)
* Dev: Filter hook 'bwfan_dynamic_string_chars' addedto modify characters in a dynamic coupon code. (#1747)
* Dev: Filter hook 'bwfcrm_api_pagination_limit' added for pagination limit in API search. (#1759)
* Dev: Filter hook 'bwfan_conversion_on_email_open' to disable conversion attribution on email open. (#1851)

= 2.5.0 (May 10, 2023) =
* Compatible with WordPress 6.2
* Added: New Birthday module added. (#215)
  	Birthday Reminder Automation event.
  	Contact Date of Birth Merge Tag
  	Checkout Date of birth field.
  	FunnelKit Checkout and Optin Date of birth field.
* Added: Automation: Webhook data rule added, works with Webhook received event. (#1407)
* Added: New integration added with 'Advanced Coupons' by Rymera. 2 Events, 2 Actions, merge tag and rules added. (#1461)
* Added: Contacts Import from CSV - New option is added to don't update blank values for existing contacts. (#1499, #1615)
* Added: Contacts filters - For number related filters, added 'At least' & 'At most' operators. (#1499)
* Added: Automation event - New settings 'Any or Specific' is added on 'Tag is added', 'Tag is removed', 'List is assigned' & 'List is unassigned' events. (#1513, #1666)
* Added: Automation - Subscription data i.e. meta new merge tag is added. (#1622)
* Added: Automation merge tag - All contact fields merge tags are added. (#1605)
* Added: Contact filter - FunnelKit Checkout page new filter is added. (#1569)
* Added: Automation merge tags: Group leaders' 3 merge tags added in Learndash course related events. (#1557)
* Added: Automation event - New settings 'Any product' or 'Specific products' is added on 'Order status changed' event. (#1549)
* Added: Forms: Integration added with Formidable forms. 4 merge tags added for 'form submit' event. (#1540)
* Improved: Broadcast listing stats API call code improved. Fetching stats in a batch. Optimisation done for heavy usage sites. (#1501)
* Improved: Compatibility issue found with Elementor forms, code is optimized. (#1534)
* Improved: Automation merge tags - Subscription related all merge tags code is improved. (#1530)
* Improved: Added extra handling to save date field value of any format to a required Y-m-d format. (#1522, #1603)
* Improved: Thrive form integration - Fetching forms and form fields code is improved. (#1572)
* Improved: Automation action - Update email field option is added in the 'Update fields' action. (#1571)
* Improved: Broadcasts - Validating email body on save in case there is any issue. (#1559, #1564)
* Improved: PHP 8 improvements. (#1552, #1660)
* Improved: Automation action - Update contact fields action code is improved. (#1547)
* Improved: Automation condition, 'Used coupon' rule checking code is improved. (#1656)
* Improved: Assigning tags and lists in bulk code is optimized. (#1652)
* Improved: Email conversation open click tracking code optimized. (#1640)
* Improved: Email analytics code improved, queries optimized. (#1636)
* Improved: Contacts importer code is optimized. (#1626)
* Improved: Automation - FunnelKit funnel builder 'Funnel ended' event code optimized, was not covering all the cases. (#1617, #1639)
* Improved: Broadcast listing screen code optimized, API optimized. (#1670, #1678)
* Fixed: Automation - 'User role updated' events code optimized, some cases were not covered. (#1683)
* Fixed: UTM campaign value was added on a wrong URL string, fixed. (#1680)
* Fixed: Automation conditions - Contact fields with text and textarea type, validation is not working for string containing comma. (#1662)
* Fixed: Automation - Subscription order summary merge tag is not displaying the items, fixed. (#1601)
* Fixed: Automation condition - Purchased product is none rule condition was wrong, fixed. (#1581)
* Fixed: Contact filters code is improved, database query was failing when contact fields and automation filers are used together. (#1579)
* Fixed: Fluent form submit event: Code improvement as some fields were not coming. (#1511)
* Fixed: Email field wasn't set in subscription related automation events. Hence some actions were failing, fixed. (#1508)
* Dev: Filter hook 'bwfan_amazonses_bounce_type' is added to allow different bounce types for email bounce handling in Amazon SES. (#1589)
* Dev: Filter hook 'bwfan_email_enable_pre_header_preview_only' to add blank space in email 'pre header'. (#1538)

= 2.4.3 (Dec 13, 2022) =
* Added: Automation Action: New actions added 'Add Order Custom Fields' and 'Add Subscription Custom Fields' (#1475)
* Improved: Contact listing, checking before showing if the column exists. (#1458)
* Improved: Allows contact searching with full name. (->#2044, #1481)
* Improved: Automation Event: Fluent form submits event, address field with google auto-locate merge tag wasn't working, fixed. (#1484)
* Improved: Scheduling recurring actions code improved. (#1488)
* Improved: Table creation code improved. (#1491, #1495)
* Fixed: Broadcast: Date format issue during scheduling with the latest WP version, fixed. (->#2020)
* Fixed: One notice was coming, fixed. (#1479)
* Fixed: Conditional checking added before adding language rule type, code optimized. (#1493)
* Dev: Filter hooks added add to modify links in Broadcast. (#1482)

= 2.4.2 (Nov 25, 2022) =
* Fixed: Contact: Date of Birth field not saving values before 1970, fixed. (#1452)
* Fixed: Broadcast: One notice coming in admin, fixed. (#1454)

= 2.4.1 (Nov 23, 2022) =
* Fixed: Automation: Elementor Forms rule some improvements. (#1429)
* Fixed: Forms: Elementor form is not working in case of global widget, fixed. (#1434)
* Fixed: Forms: Status not updating in case contact already exists, fixed. (#1439)
* Fixed: Automation: End Automation action: Now checking all the instances of contact in automation and ending all of them. (#1442)

= 2.4.0 (Nov 10, 2022) =
* Compatible with WordPress 6.1
* Added: Automation: Upsell Offer accepted event - Offer transaction ID merge tag added. (#1411)
* Added: Automation: Language new rule added. (#1380)
* Improved: Automation: HTTP Request action now supporting content type application JSON. (#1383)
* Improved: Automation: Subscription before renewal event: every time validating subscription if order renewed after. (#1392)
* Improved: Automation: Update Fields action: Date-related field saving issue when value is in a different format, fixed. (#1421)
* Improved: Contact > Notes: Recent notes coming at the top with Add button. (#1386)
* Improved: Translation localization-related improvements. (#1416)
* Fixed: Broadcast filters code improved. (#1408)
* Fixed: Automation: Sometimes some merge tags were not decoded in the email action, fixed. (#1389)

= 2.3.0 (Oct 18, 2022) =
* New: Re-branding related changes from WooFunnels to FunnelKit and Autonami to FunnelKit Automations.
* Added: Elastic Email: Email Bounce handling option added. (#1365)
* Improved: Disallow click tracking from the unsubscribe links. (#1355)
* Improved: Mailgun bounce handling code improved, considering failed, complaints events. (#1340)
* Improved: Thrive leads older versions are throwing a PHP error, code improved. (#1342)
* Improved: Automation: Link trigger clicked event is not working when link trigger doesn't have any actions, fixed. (#1344)
* Improved: Single Contact screen, longer tag or list overlap issue, CSS improvement. (->#1896)
* Improved: Contact filter: User role is not code improved. (#1348)
* Improved: Automation Event: WC Subscription created event wasn't triggering when a subscription is created manually. (#1351)
* Improved: Automation Event: WPForms form submit event, first name and last name field type value wasn't saving, fixed. (#1360)
* Improved: Automation Merge Tags: Contact Total Spend & WC Subscription Total merge tag now has 3 options, display raw output, formatted output & currency formatted output. (#1362)
* Fixed: Contacts: Import button wasn't working when there were no contacts, fixed. (->#1871)
* Fixed: Broadcast: Engages filter, In the period option preview was wrong, corrected. (#1898)
* Fixed: Contact fields were not updating with field types checkout and radio. Fixed. (#1329)
* Fixed: WooCommerce dependency handling improved. (#1330)
* Fixed: Broadcast: The Listing screen 'created at' column was showing the wrong value. (#1346)
* Dev: Broadcast: Filter hook to disallow business name, and unsubscribe link in the broadcast email. (#1900)
* Dev: Action hook 'bwfan_contact_email_changed' added after contact email changed. (#1324)

= 2.2.0 (Sep 23, 2022) =
* Compatible with PHP 8.0
* Added: New feature: Allow adding any columns on the contact listing page and sorting options. (#1083)
* Added: New Event: 'User role updated'. (#1078)
* Added: 'creation date' column is added on all the listing pages. (#1178)
* Added: Unschedule a broadcast, quick action added. (#1193)
* Fixed: 'Delete Coupon' action code improved to handle coupons with dynamic tags. (#1182)
* Improved: Showing decoded value in the subject on the Contact > Email page. (#1184)
* Improved: Automation triggering code optimized during 'Bulk Action'. (#1188)
* Improved: 3rd party compatibilities related to merge tags, code improved. (#1195, #1205)
* Improved: Hardened email auto open & click actions. Default time set to 5 secs. (#1213)
* Improved: Automation: Events & Actions default value is set where it was missing. (#1215, #1296)
* Improved: Contact Exports: Code improved. (#1234)
* Improved: WC Subscription related events: Email is set at the top level to help further automation flow. (#1242)
* Improved: Automation 'Debug' action, doing force logging. (#1246)
* Improved: Automation > Contacts. Bulk actions code improved. (#1248)
* Improved: Delete contact code improved, done checking for active integration related tables. (#1255)
* Improved: WC 'Before card expiry' event code is improved. (#1268)
* Improved: Added handling in forms submit related events rules, where field value contains a comma. (#1270)
* Improved: Automation webhook received event: Code optimized when nested data is passed in the webhook. (#1318)
* Fixed: Contacts export - Code added for 'Last order days' and 'AOV' columns. (#1200)
* Fixed: 'Subscription status change' event - Validation code added for the event. (#1207)
* Fixed: UTM parameters case sensitive issue fixed. (#1211)
* Fixed: Shortcodes are not executable in automation emails. Fixed. (#1218)
* Fixed: Order items table merge tag: Download URL was not coming, fixed. (#1235)
* Fixed: Contact: Bulk action filters query improved. (#1275)
* Fixed: Legacy v1 Automation: Contact tags rule code optimized. (#1279)
* Fixed: Thrive Form submit event: All forms are not coming for selection, code improved. (#1305)
* Fixed: Contact fields dynamic tags are not decoding if present inside a tag, fixed. (#1308)
* Fixed: Divi form submit event: Was not working for global forms, fixed. (#1313)
* Dev: Filter hook added before redirecting a link trigger. (#1261)

= 2.1.3 (Jul 15, 2022) =
* Added: Contact filters and automation rules, new advanced operators added like 'is blank', 'is not blank', 'starts with' etc. (#1126)
* Improved: Contacts export: when a server has a file permissions issue, displaying the error in the app and other code improvements. (->#1666, #1144)
* Fixed: Date type fields updating current date if the passed value is blank, fixed. (#1159)
* Fixed: 'From Name' & 'From Email' overriding feature wasn't working for test emails, fixed. (#1167)
* Fixed: Automation Next-gen: 'Custom winback' event settings were not passing to the event hence using the default settings, fixed. (#1172)
* Fixed: Automation Next-gen: Elementor form submits event wasn't considering global widgets, sections & posts, fixed. (#1170)
* Fixed: Link trigger wasn't saving for a particular action, fixed. (->#1680)
* Fixed: Automation: Gravity form 'Form field' rule code improved. (#1141)
* Fixed: Automation: Send Test SMS was not working, fixed. (->#1641)
* Fixed: Broadcast: Edit button in quick actions wasn't working for draft broadcasts, fixed. (->#1654)

= 2.1.2 (Jul 04, 2022) =
* Improved: Bulk actions file logging code handling in case file writing permissions are not there. (#1122)
* Improved: Engagement table column missing sometimes, more handling done. (#1124)
* Fixed: Subscription item merge tag format - `product name with quantity` wasn't working, fixed. (#1120)

= 2.1.1 (Jun 30, 2022) =
* Improved: Couple of performance enhancements optimised few duplicate queries. (#1110)
* Improved: Bulk Actions execution logic improved. (#1099)
* Fixed: Audience rule was not working for older automation, fixed. (#1101)
* Fixed: Some custom fields are not coming in contact field rules, fixed. (#1108)

= 2.1.0 (Jun 27, 2022) =
* New: Compatible with Autonami next-gen automation builder. (Autonami v2.1.0 or above)
* New: Create automation easily from built-in 32 recipes.
* New: 6 Step types: Action, Delay, Condition, Goal, Jump & Exit.
* New: Contact journey: View contact visually in the automation.
* New: In-line analytics for Email and SMS action steps.
* New: Bulk Actions a new feature introduced. Now filter contacts and perform from 9 available actions in bulk on contacts.
* Improved: Tons of performance improvements.


= 2.0.10 (May 26, 2022) =
* Added: Import contacts via CSV: Added 'creation date' as a column to create contacts with a given date. (#1039)
* Improved: Import contacts via CSV: Added more data in the error log file for better understanding. (#1033)
* Improved: Showing Error message on Broadcast Engagement Timeline (->#1535)
* Fixed: Automation rule: 'Is WordPress user' has some issues, fixed. (#1013)
* Fixed: During multiple lists/ tags assigned or unassigned, automation was not running multiple times, fixed. (#1019)
* Fixed: Forms: Elementor popup form via global widget wasn't working correctly, fixed. (#1027)
* Fixed: Forms: Auto-confirm contact setting wasn't working when contact timezone is different than store local timezone, fixed. (#1030)

= 2.0.9 (Apr 23, 2022) =
* Critical: On some servers, WordFence flag the save email body request, fixed. (#1002)
* Added: Create contact action, phone optional field added. Status field code optimized. (#982)
* Improved: Drag and Drop editor, image CSS improved. Earlier high width images overlapped. (#1007)
* Improved: Forms: Thrive form, added handling to fetch entry details from a custom field. (#1004)
* Improved: Forms: Fluent form, showing attribute name first then label then admin label of fields inside admin. (#1009)
* Fixed: Broadcast: Send test email was not working, fixed. (-> #1511)
* Fixed: Allowed mailto links in the email. (#978)
* Fixed: Forms: Gravity form checkbox field mapping has issues, resolved. (#980)
* Fixed: Forms: Add tags on confirmation field value wasn't saving, fixed. (#995)
* Fixed: Displaying errors messages during contact CSV import. (#984)

= 2.0.8 (Apr 10, 2022) =
* This release lays out foundation for upcoming Autonami Next Generation Automation Builder.
* Added: Conditions: Added 'contains', 'does not contains', 'starts with' & 'ends with' operators in all form related events, form field rule and contact related rules. (#917)
* Added: Condition: Advanced shipping rate rule added in order related events. (#939)
* Added: UTM Lead Tracker data merge tag added in order related events. (#931)
* Added: Multi currency handling done during creating conversions. (#865)
* Improved: Engaged and Unengaged filter query improved. (#943, #947)
* Improved: Condition: WooCommerce total order count rule wasn't working when no order is purchased. (#933)
* Improved: Saving email template sometimes caused 404 error on few occasions of a server, fixed. (-> #1448, -> #1490)
* Improved: WP is user rule code improved. (#925)
* Improved: Export file names more dynamic now. (#920)
* Improved: Updating contact data via API throwing email already exists error, handling done for WP Fusion plugin. (#891)
* Improved: PHP 8.1 related improvements. (#882)
* Improved: Contacts importer UI improvements. (-> #1370)
* Fixed: Total revenue not displaying in the broadcast analytics when A/B mode active. (#937)
* Fixed: Send Test SMS not decoding the merge tags, fixed. (-> #1249)
* Fixed: Contact DOB merge tag code improved. (#919)
* Dev: Added filter hook 'bwfan_skip_broadcast_tracking_url' to disable open click tracking in broadcast. (#896)
* Dev: Added filter hook 'bwfan_event_wcs_before_end_statuses' to allow any WooCommerce status on 'Subscription before end' event. (#874)

= 2.0.7 (Nov 02, 2021) =
* Added: Deep integration done with Wishlist Member plugin. Filters, Import/ Export Contacts/ Events, Actions. (#392)
* Added: New Action: Change Contact Status added. (#704)
* Added: Order related Events: Added Handl UTM Grabber plugin merge tag. (#695)
* Improved: Event: Thrive form submission: New handle found for conversion forms, added. (#720)
* Improved: Forms: Allows hidden fields for selection in Fluent Form. (#721)
* Improved: Link Trigger: Creating new hash encrypted key on cloning. (#710)
* Improved: Templates listing and Applying template code optimized. (#698, #723)
* Fixed: Event: Thrive form submission: Blank fields are showing in some cases, scenario handled. (#701)
* Fixed: Filters: User role filter wasn't working, fixed. (#729)


= 2.0.6 (Oct 07, 2021) =
* Fixed: One query was taking higher time, optimized. (#690)


= 2.0.5 (Oct 06, 2021) =
* Compatible upto Autonami 2.0.7
* Added: Link Triggers, new feature added.
* Added: Forms: Thrive Leads integration added. (#636)
* Added: New Event: Contact Unsubscribes added. (#652 -> #1036)
* Added: Filters: Engaged and Un-engaged filters added. (#675)
* Added: Automations Rule: Check if contact is in the audience, added. (#595, #610)
* Added: Automation Merge Tag: Contact total spent and contact order count merge tags added under contact profile in all events. (#641)
* Added: Automation Merge Tag: {{new_user_password}} one-time use only merge tag added that saved the user password and allows to send once. (#633)
* Added: Contacts listing screen: allow sorting by the first name. (#989)
* Added: Compatibility added with 'Advance shipment tracking pro' plugin by Zorem. (#571)
* Improved: Forms: File upload field value wasn't supported with WP Forms integration, supported now. (#580)
* Improved: Delete the conversion on WC order deletion. (#602)
* Improved: Automation Event: Webhook received code and UI improvements. (#615)
* Improved: Automation Event: Outgoing Webhook, HTTP Request: UI improved, allow adding contact basic details quickly. (#625)
* Improved: Filters: New operators 'contains', 'does not contains', 'start with' and 'end with' added in contact field filter. (#635)
* Improved: Contacts Import: Added failed entry logs, available for download after import. (#654)
* Improved: Some improvements in tables and code to speed up the performance. (#655)
* Improved: Automation Rules: Textual improvement in rules for better understanding. (#677)
* Improved: Improvement in Analytics screens when viewing on mobile devices. (#1010)
* Fixed: Purge contact reference data when contact is deleted. (#573)
* Fixed: 'Broadcast Click' filter wasn't working, fixed. (#574)
* Fixed: Compatibility updated with 'WooCommerce membership' oldest version, PHP error was coming, resolved. (#590)
* Fixed: Tracking links and conversion recording issues with SMS and Whatsapp messages, fixed. (#598)
* Fixed: Automation Event: Contact subscribes event wasn't running, fixed. (#606 -> #980)
* Fixed: Automation Event: Tag Assigned and List Assigned aren't working properly in case multiple tags/ lists are assigned. (#628)


= 2.0.4 (Aug 23, 2021) =
* Added: Sendinblue Bounce option added. (#542)
* Added: Users and Broadcast Engagement filters added. (Is user, User Role, Broadcast Send, Open and Click). Now segment contacts using broadcast enhanced filters. (#553)
* Improved: Contact update field API call improvement.
* Improved: Create user & Update fields action UI improved. (#555)
* Fixed: Contacts listing: Datetime error in a rare case, handling done.


= 2.0.3 (Aug 18, 2021) =
* Compatible upto Autonami 2.0.5
* Added: Contact filters: New filter 'Contact User Role' added. (#503)
* Added: A feature to send user password (one time) from the `create user` action to the user. (#514)
* Added: Action - Create contact, status dropdown field added. (#457)
* Added: Support for polylang for forms, now verifying the language. (#520)
* Added: Autonami Contact rules and merge tags in all the events. (#406)
* Improved: Email links as text, UTM arguments appending on the the hyper link only. (#420)
* Improved: Contacts - WC orders import, disallow importing of child orders, supported by some plugins. (#431)
* Improved: Forms now support Radio/ Checkboxes/ Dropdown fields while syncing with Autonami fields. (#442)
* Improved: Autonami contacts, code is improved, execution speed increased from an earlier time. (#450)
* Improved: Email tracking code improved. (#506)
* Improved: Contact import process improved. ($523)
* Improved: Contact export code improved. An issue was found on some servers. (#522)
* Improved: Form submission events - Fields checking code improved. (#531)
* Improved: Broadcasts scheduling now showing the time as per the store set time.
* Dev: Improved: Autonami incoming webhooks now supports nested array object data on a single key. (#425)
* Fixed: Issue occurred while adding multiple exports. (#408)
* Fixed: Forms - Funnel Builder Optin form, phone value is not coming with the country code when available, fixed. (#413)
* Fixed: Single contact - WC paid orders listing is failing in case order was deleted, fixed. (#466)
* Fixed: Event - Subscription card expiry event was running on the same contact again, another day, fixed. (#444)
* Fixed: Emails - Links with query arguments are breaking for open and click tracking, fixed. (#446)
* Fixed: Added handling in case undefined is the target link. (#500)
* Fixed: Forms - Thrive integration fetching of form fields causing issues with the latest version, fixed. (#528)


= 2.0.2 (Jul 05, 2021) =
* Added: WooCommerce Wishlist integration added. 3 events are added. (#335)
* Added: Weglot integration added. Allowing sending emails based on the buyers language. (#362)
* Added: WooCommerce Subscription: Payment count rule added. (#330)
* Added: Templates cloning feature added. (#389)
* Improved: Allowing WC Membership actions to execute on Autonami CRM events, like on Add a Tag or List. (#340)
* Improved: Auto-sync WP user profile known fields with Autonami Contact. (#341)
* Improved: Contacts import via CSV. Code is improved to parse the country by name as well. (#361)
* Improved: Allow saving blank values for contact custom fields. (#363)
* Improved: Tags and Lists fetching calls improved. (#347)
* Improved: WC Subscription failed event: validating the subscription status before executing the tasks. (#377)
* Improved: Email pre-header text code handling, hide on all email clients. (#396)
* Improved: Reports: Email: Some mysql calls improvements. (#359)
* Fixed: Forms: Email notification: Open & Click tracking wasn't working, fixed. (#384)
* Fixed: Contact custom field rule wasn't working, fixed. (#401)
* Fixed: HTTP Request action: In each scenario sending the request via GET method, fixed. (#332)
* Fixed: Fluent Form: An issue found, unable to fetch form fields in a scenario when multiple columns in a form. (#345)
* Fixed: Postmark bounce feature, found an issue, resolved. (#349)
* Dev: Added a filter hook to disable the click tracking in Automation. (#394)


= 2.0.1 (Jun 11, 2021) =
* Fixed: Some tables were not creating on some server enviroments, fixed. (fix/325)


= 2.0.0 (Jun 10, 2021) =
* Compatible with Autonami 2.0:
- > Rich contact profiles
- > Advanced import and export of contacts
- > Deep Integration with WooCommerce
- > Advanced segmenting to send targeted messages
- > Broadcast campaigns with A/B testing and smart sending
- > SMS Broadcasts via Twilio/ Bulkgate
- > Smart Automations
- > Drag and Drop email builder
- > Smart Analytics that uncover Email tracking, Engagement Metrics, ROI Analysis per campaign
- > Capture leads from your favourite form builder
- > Create WorkFlows and let different plugins interact with each other
- > Connect with your favourite services like Twilio, Slack and other services
* Compatible with PHP 8.0
* Fixed: Elementor and Fluent Form submission events some improvements.


= 1.3.0 (2021-01-12) =

* Added: New Rule: Customer Marketing status on order related events. (#224)
* Added: New Event: WooFunnels Optin form submission. (#238)
* Added: New Event: Learndash - User removed from a group. (#252)
* Added: New Event: WC Subscription note added. (#275)
* Added: New Merge tag: Subscription billing company. (#275)
* Improved: Thrive Leads: Form submit event, now includes lead groups. (#257)
* Fixed: Winback event: Validating user last purchase at the time of executing actions. (#251)
* Fixed: Elementor form submit event: A PHP notice was occurring, resolved. (#259)
* Fixed: HTTP Post action: Send test data not decoding the cart items merge tag, fixed. (#263)
* Fixed: Winback event: When a contact list is huge, sometimes based on the server won't be able to execute all the actions. Fixed. (#272)
* Fixed: Update user role action was not updating the user role, found issues with 3rd party plugin, fixed. (#281)


= 1.2.2 (2020-09-25) =

* Added: New Merge tag '{{bwf_contact_id}}' added. Will return the unique contact id of every user. Usable in case of creating coupon with dynamic value. (#241)
* Improved: Update User Role: Action now supports role assignment as well. (#243)
* Improved: Learndash: Lesson selection rule, now showing course name aside of lesson name for better understanding. (#234)
* Fixed: Fluent Forms: After form submission, it is not redirecting to the correct page, fixed. (#230)


= 1.2.1 (2020-08-26) =

* Fixed: PHP error in case WooCommerce is not active. (#220)
* Fixed: Contact has 'Active Subscription' rule, wasn't working with 'Win-back campaign' event, fixed. (#225)


= 1.2.0 (2020-08-23) =

* Compatible with WordPress 5.5
* Compatible with WooCommerce 4.3 & 4.4
* Added: Learndash integration added. Events like a user is enrolled, user completed a course or lesson or topic. Actions like enroll a user in a course, add a user to a group etc. (#153)
* Added: New event: Webhook received. Receive any data via HTTP Post and perform actions in your site. (#141)
* Added: New Action: End Automation. Stop the automation execution i.e. deletes the schedule tasks of particular automation based on contact from any automation. (#117)
* Added: Ninja Form integration added. Now execute actions after a Ninja form is submitted. (#153)
* Added: Fluent Form integration added. Now execute actions after a Fluent form is submitted. (#172)
* Added: Caldera Form integration added. Now execute actions after a Caldera form is submitted. (#179)
* Added: New merge tag: Order again URL. Ability to add all order items to the cart with a single URL. (#164)
* Added: New Rule: Subscription Failed Attempt. (#168)
* Added: New Rule: Customer Purchased Products Category added. (#150)
* Added: New rule: BWF Contact added on Gravity form, Ninja Form, Elementor Form & Thrive Leads form submission events. (#148)
* Added: New event: Order Status Pending. Run, on orders which are left in pending state and are 10 mins older. (#146)
* Added: Compatibility with 'WooCommerce Sequential Order Numbers' plugin. Merge tag {{wc_sequential_order_number}} with output (Order Number & Order number Formatted) on order related event. (#138)
* Added: Compatibility with 'WooCommerce Advanced Shipment Tracking' plugin. Merge tag {{wc_advanced_shipment_tracking}} with output (tracking_number, tracking_provider, tracking_link & date_shipped) on order related event. (#186)
* Added: Compatibility with 'Handl UTM Grabber' plugin. Merge tag {{hand_utm_grabber_data}} with multiple outputs (like utm_campaign, utm_source etc) on cart abandonment event. (#211)
* Improved: Elementor forms have multiple cases like popup form, widget form etc. All are handled. (#188, #195)
* Improved: 'Send data to Zapier' & 'HTTP Post' actions, UI improved. (#199)
* Fixed: 'Send data to Zapier' action, sometimes data contain extra slashes, fixed. (#137)
* Fixed: UpStroke offer accepted event: item id and name merge tags issue fixed. Item SKU, new merge tag added. (#184)


= 1.1.0 (2020-03-25) =

* Added: ThriveLeads integration added. Now execute actions after a thrive leads form submission. (#87)
* Added: AffiliateWP integration: New Event: Affiliate status change added. (#82)
* Added: AffiliateWP integration: New Rule: Affiliate rate added. (#82)
* Added: AffiliateWP integration: New Merge Tags: {{affwp_affiliate_rate}} and {{affwp_affiliate_status}} added. (#82)
* Added: New action: Update WordPress user role. (#92)
* Added: Compatibility with 'WooCommerce Shipment Tracking' plugin. Merge tag {{wc_shipment_tracking}} with output (tracking_number, formatted_tracking_provider, formatted_tracking_link & date_shipped) on order related event. (#95)
* Added: Compatibility with 'Jetpack' plugin shipment feature. Merge tag {{wc_jetpack_shipment}} with output (carrier_name_full, package_name, tracking_number & tracking_link) on order related event. (#105)
* Added: New action: Cancel WC order associated subscription for order status change event only. (#127)
* Added: Autonami notice to install a plugin if Autonami is not installed or active. (#133)
* Improved: HTTP Post action, showing correct response code after execution. (#104)
* Improved: Create user action, now have the first name and last name optional fields as well. (#111)
* Improved: WC Add order note action now have the option to choose between customer note and private note. (#107)
* Fixed: Compatibility to run without WooCommerce. (#80)


= 1.0.2 (2020-01-07) =

* No change


= 1.0.1 (2020-01-07) =

* Added: Affiliate first name merge tag added.
* Added: Zapier 'Send data' action: new option 'send test data' coded.
* Added: Customer winback campaign, new settings added; UI & logic improved.
* Added: New rules 'Order is a renewal for WC subscription' added.
* Added: Batch Processes: Delete option added for completed processes.
* Improved: Time sync events like 'customer winback', 'affiliate digest' etc. are now showing 'last run' date on the event UI so that store owner can see when it last ran.
* Improved: Action UI spacing, descriptions, overall UX improved.
* Improved: Custom callback action code improved.
* Improved: Delete scheduled tasks of winback campaign automation of an user after a new order is placed.
* Improved: WooCommerce subscription renewal event auto validating subscription status before executing tasks.
* Fixed: AeroCheckout page id rule wasn't working, fixed.


= 1.0.0 (2019-11-25) =

* Public Release
=== URL Media Uploader ===
Contributors: apprhyme
Tags: media uploader, URL media, upload from URL, WordPress media library, import media
Requires at least: 5.0
Tested up to: 6.7.2
Stable tag: 1.0.1
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily upload media to your library from external URLs or select files from the library by using internal ones.

== Description ==

**URL Media Uploader** allows users to seamlessly upload media files directly to their WordPress media library (or select existing ones) by pasting URLs. Skip the hassle of manually downloading files or scrolling through endless library and let the URL Media Uploader handle everything for you.

**Key Features:**

- Upload images, videos, and other media by pasting the URL.
- Automatically downloads and attaches media to your media library.
- Auto-selects the downloaded media.
- Detects internal URLs and auto-selects it in the Media Library if the file exists.
- Built-in AJAX support for a smooth user experience.
- Great for bloggers, webmasters, and developers who manage large media collections.
- Simple to use on posts, pages, and media library screens.

== Screenshots ==
1. Uploading media from an external URL
2. Selecting media by uploading internal URL
3. Compatible editors

== Installation ==

1. Upload the `url-media-uploader` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the Media Library, Posts, or Pages section where you want to upload media via URL.
4. Paste the media URL in the designated area, and the plugin will handle the rest.

== Frequently Asked Questions ==
= How is this different from "Insert from URL" core WordPress feature? =
Using "Insert from URL" links an image to external website where the image was originally stored. With URL Media Uploader you will save the image to your Media Library, reducing load time and ensuring independence from external website.

= I get an alert saying "cURL error 6: Could not resolve host". What do I do? =
We are currently looking into this, but the quickest solution is to click "Upload" button multiple times.

= What types of media can I upload using the URL Media Uploader? =
You can upload various media types like images, videos, and other supported file formats directly from a URL.

= Is there a file size limit when uploading media via URL? =
The file size is dependent on your WordPress and server configurations. Ensure that your server's upload limits are configured to handle larger files if needed.

= Can I use this plugin with any WordPress theme? =
Yes, this plugin works with any WordPress theme and integrates seamlessly into the media uploader interface.

= Does this plugin work with custom post types? =
Yes, the plugin can be used on any custom post type that supports the media uploader.

= Is URL validation built into the plugin? =
Yes, the plugin ensures that URLs are properly validated before attempting to upload the media.

== Changelog ==

= 1.0.1 = 
* Advanced validation added for downloadable URL to prevent Server-Side Request Forgery

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.1 = 
* Advanced validation added for downloadable URL to prevent Server-Side Request Forgery

== License ==

This plugin is licensed under the GPLv2 or later. For more information, see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).
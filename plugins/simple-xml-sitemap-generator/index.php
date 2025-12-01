<?php
/*
Plugin Name: Simple XML Sitemap Generator
Plugin URI: http://www.chefblogger.me
Description: XML Sitemap creates an XML for use with Google and Yahoo (and Yes! Bing too). Just install it to your wordpress installation and let the plugin do his job. <a href="options-general.php?page=QWA_sxmlsg">Administration</a>
Version: 2.4
Author: Eric-Oliver Mächler
Author URI: http://www.chefblogger.me
Requires at least: 4.0
Tested up to: 6.8
Text Domain: simple-xml-sitemap-generator
Domain Path: /languages
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

include 'conf.php';

// Mehrsprachigkeit laden
function my_plugin_initsimplexmlsitemapgenerator()
{
  load_plugin_textdomain('simple-xml-sitemap-generator', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'my_plugin_initsimplexmlsitemapgenerator');

// WordPress internen XML Sitemap Generator abschalten
add_filter('wp_sitemaps_enabled', '__return_false');

// Sitemap-Erstellung
function sg_create_sitemap()
{
  $postsForSitemap = get_posts(array(
    'numberposts' => -1,
    'orderby'     => 'modified',
    'post_type'   => array('post', 'page', 'product'),
    'order'       => 'DESC'
  ));

  $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
  $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

  foreach ($postsForSitemap as $post) {
    setup_postdata($post);
    $postdate = explode(" ", $post->post_modified);

    $xml_textid = $post->ID;
    $xml_custom_field = get_post_meta($xml_textid, 'sitemap', true);
    $xml_sitemapscore = get_post_meta($xml_textid, 'sitemapscore', true);

    $include_post = ($xml_custom_field !== 'no');
    $score_valid = is_numeric($xml_sitemapscore);

    if ($include_post) {
      $priority = $score_valid ? esc_html($xml_sitemapscore) : '0.8';

      $sitemap .= '<url>' .
        '<loc>' . esc_url(get_permalink($post->ID)) . '</loc>' .
        '<lastmod>' . esc_html($postdate[0]) . '</lastmod>' .
        '<changefreq>daily</changefreq>' .
        '<priority>' . $priority . '</priority>' .
        '</url>';
    }
  }

  // Kategorien hinzufügen
  if (get_option('sxmlsg_kategorien') === 'Ja') {
    $categories = get_categories(array(
      'orderby' => 'name',
      'order'   => 'ASC'
    ));

    $sxmlsg_kategorien_datum = gmdate("Y-m-d");

    foreach ($categories as $category) {
      $sitemap .= '<url>' .
        '<loc>' . esc_url(get_category_link($category->term_id)) . '</loc>' .
        '<lastmod>' . esc_html($sxmlsg_kategorien_datum) . '</lastmod>' .
        '<changefreq>daily</changefreq>' .
        '<priority>0.8</priority>' .
        '</url>';
    }
  }

  $sitemap .= '</urlset>';

  // WP_Filesystem verwenden
  global $wp_filesystem;

  if (empty($wp_filesystem)) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    WP_Filesystem();
  }

  if ($wp_filesystem) {
    $sitemap_file = ABSPATH . 'sitemap.xml';

    // Sicherstellen, dass das Stammverzeichnis beschreibbar ist
    if ($wp_filesystem->is_writable(ABSPATH)) {
      $wp_filesystem->put_contents($sitemap_file, $sitemap, FS_CHMOD_FILE);
    } else {
      error_log('Sitemap-Verzeichnis ist nicht beschreibbar: ' . ABSPATH);
    }
  } else {
    error_log('WP_Filesystem konnte nicht initialisiert werden.');
  }
}

add_action("publish_post", "sg_create_sitemap");
add_action("publish_page", "sg_create_sitemap");

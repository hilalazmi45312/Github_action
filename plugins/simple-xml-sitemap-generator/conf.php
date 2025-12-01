<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

 include 'form.php';
 function save_sxmlsg() {
  adminForm_sxmlsg();
 }






function QWA_sxmlsg() {
	add_options_page('Simple XML Sitemap Generator', 'Simple XML Sitemap Generator', 'manage_options', 'QWA_sxmlsg', 'save_sxmlsg');
}
add_action( 'admin_menu', 'QWA_sxmlsg' );
?>
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'VIJAGIF_WOO_FREE_GIFT_ADMIN', VIJAGIF_WOO_FREE_GIFT_DIR . "admin" . DIRECTORY_SEPARATOR );
define( 'VIJAGIF_WOO_FREE_GIFT_FRONTEND', VIJAGIF_WOO_FREE_GIFT_DIR . "frontend" . DIRECTORY_SEPARATOR );
define( 'VIJAGIF_WOO_FREE_GIFT_LANGUAGES', VIJAGIF_WOO_FREE_GIFT_DIR . "languages" . DIRECTORY_SEPARATOR );
define( 'VIJAGIF_WOO_FREE_GIFT_TEMPLATES', VIJAGIF_WOO_FREE_GIFT_DIR . "templates" . DIRECTORY_SEPARATOR );

$plugin_url = plugins_url( '', __FILE__ );
$plugin_url = str_replace( '/includes', '', $plugin_url );
define( 'VIJAGIF_WOO_FREE_GIFT_CSS', $plugin_url . "/assets/css/" );
define( 'VIJAGIF_WOO_FREE_GIFT_CSS_DIR', VIJAGIF_WOO_FREE_GIFT_DIR . "css" . DIRECTORY_SEPARATOR );
define( 'VIJAGIF_WOO_FREE_GIFT_JS', $plugin_url . "/assets/js/" );
define( 'VIJAGIF_WOO_FREE_GIFT_JS_DIR', VIJAGIF_WOO_FREE_GIFT_DIR . "js" . DIRECTORY_SEPARATOR );
//define( 'VIJAGIF_WOO_FREE_GIFT_IMAGES', $plugin_url . "/assets/images/" );

define( 'VIJAGIF_WOO_FREE_GIFT_EXTENSION_VERSION', '1.1.0' );

if ( is_file( VIJAGIF_WOO_FREE_GIFT_INCLUDES . "functions.php" ) ) {
	require_once VIJAGIF_WOO_FREE_GIFT_INCLUDES . "functions.php";
}
if ( is_file( VIJAGIF_WOO_FREE_GIFT_INCLUDES . "support.php" ) ) {
	require_once VIJAGIF_WOO_FREE_GIFT_INCLUDES . "support.php";
}
if ( is_file( VIJAGIF_WOO_FREE_GIFT_INCLUDES . "data.php" ) ) {
	require_once VIJAGIF_WOO_FREE_GIFT_INCLUDES . "data.php";
}
if ( is_file( VIJAGIF_WOO_FREE_GIFT_INCLUDES . "customize-control.php" ) ) {
	require_once VIJAGIF_WOO_FREE_GIFT_INCLUDES . "customize-control.php";
}
if ( is_file( VIJAGIF_WOO_FREE_GIFT_INCLUDES . "helper.php" ) ) {
	require_once VIJAGIF_WOO_FREE_GIFT_INCLUDES . "helper.php";
}
vi_include_folder( VIJAGIF_WOO_FREE_GIFT_ADMIN, 'VIJAGIF_WOO_FREE_GIFT_Admin_' );
vi_include_folder( VIJAGIF_WOO_FREE_GIFT_FRONTEND, 'VIJAGIF_WOO_FREE_GIFT_FRONTEND_' );


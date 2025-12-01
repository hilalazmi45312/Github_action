<?php
/**
 * Plugin Name:       Modal Builder Block
 * Description:       Build a modal with the power of WordPress' block editor. Anything you can do with the editor works inside of the modal content window or the modal trigger.
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * Version:           1.1.0
 * Author:            Rodrigo Leon
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       modal-builder-block
 *
 * @package           htr-block
 */

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/writing-your-first-block-type/
 */

function htr_block_modal_builder_block_init() {
	register_block_type( __DIR__ );
}
add_action( 'init', 'htr_block_modal_builder_block_init' );



function htr_block_modal_builder_scripts($hook) { 
	$my_js_ver  = date("ymd-Gis", filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/app.js' ));
	wp_enqueue_script( 'modal-builder', plugins_url( 'assets/js/app.js', __FILE__ ), array(), $my_js_ver, true );
}

add_action('wp_enqueue_scripts', 'htr_block_modal_builder_scripts');

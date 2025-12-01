<?php

/**
 * Compatibility - WPML plugin.
 * 
 * @since 3.8.0
 * */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('DEY_WPML_Compatibility')) {

	/**
	 * Class.
	 * 
	 * @since 3.8.0
	 * */
	class DEY_WPML_Compatibility extends DEY_Compatibility {

		/**
		 * Class Constructor.
		 * 
		 * @since 3.8.0
		 */
		public function __construct() {
			$this->id = 'wpml';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 * @since 3.8.0
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return function_exists('icl_register_string');
		}

		/**
		 * Admin action.
		 * 
		 * @since 3.8.0
		 */
		public function admin_action() {
			// Sync product delivery slots data on saving translation.
			//            add_action('wcml_before_sync_product_data', array($this, 'sync_product_delivery_slots_data'), 10);
			// Sync product delivery slots before product update.
			add_action('dey_after_product_delivery_slots_data_saved', array( $this, 'sync_product_delivery_slots_data' ), 10, 2);
		}

		/**
		 * Action.
		 * 
		 * @since 3.8.0
		 */
		public function actions() {
			// Sync product meta data to other language products after product meta data updated.
			add_action('dey_update_post_meta', array( $this, 'sync_product_meta_data' ), 10, 3);
			// Get default language product id.
			add_filter('dey_get_product_id', array( $this, 'alter_product_id' ), 10, 1);
		}

		/**
		 * Sync product delivery slots data on saving translation.
		 *
		 * @since 3.8.0
		 * 
		 * @param int $product_id product ID.
		 * @param array/boolean $data It contains to update meta values with keys
		 */
		public function sync_product_delivery_slots_data( $product_id, $data = false ) {
			global $sitepress;

			$product = wc_get_product($product_id);
			if (!is_object($sitepress) || !is_object($product)) {
				return;
			}

			$product_translated_id = $sitepress->get_element_trid($product_id, 'post_product');
			$product_translations = $sitepress->get_element_translations($product_translated_id, 'post_product');
			if (!dey_check_is_array($product_translations)) {
				return;
			}

			if (!$data) {
				$product_data_store = new DEY_Product_Data_Store($product);
				$data = $product_data_store->get_data();
			}

			if (!dey_check_is_array($data)) {
				return;
			}

			foreach ($product_translations as $product_translation) {
				if (!is_object($product_translation) || !empty($product_translation->original) || $product_id == $product_translation->element_id) {
					continue;
				}

				foreach ($data as $meta_key => $meta_value) {
					update_post_meta($product_translation->element_id, $meta_key, $meta_value);
				}
			}
		}

		/**
		 * Sync product meta data to other language products after product meta data updated.
		 * 
		 * @since 3.8.0
		 * 
		 * @global object $sitepress
		 * @param int $product_id
		 * @param string $key
		 * @param string $value
		 */
		public function sync_product_meta_data( $product_id, $key, $value ) {
			global $sitepress;
			if (!is_object($sitepress)) {
				return;
			}

			$meta_keys = DEY_Product_Data_Store::get_meta_keys();
			if (!in_array($key, $meta_keys)) {
				return;
			}

			$default_product_id = $this->get_default_product_id($product_id);
			if (!$default_product_id) {
				return;
			}

			$product_translated_id = $sitepress->get_element_trid($default_product_id, 'post_product');
			$product_translations = $sitepress->get_element_translations($default_product_id, 'post_product');
			if (!dey_check_is_array($product_translations)) {
				return;
			}

			foreach ($product_translations as $product_translation) {
				if (!is_object($product_translation)) {
					continue;
				}

				update_post_meta($product_translation->element_id, $key, $value);
			}
		}

		/**
		 * Alter product id.
		 *
		 * @since 3.8.0
		 * @param int $product_id Product ID
		 * @return int
		 * */
		public function alter_product_id( $product_id ) {
			$default_product_id = $this->get_default_product_id($product_id);

			return 0 != $default_product_id ? $default_product_id : $product_id;
		}

		/**
		 * Get the default product id for current product id.
		 * 
		 * @since 3.8.0
		 * 
		 * @global object $sitepress
		 * @param int $product_id
		 * @return int
		 */
		public function get_default_product_id( $product_id ) {
			global $sitepress;

			$default_product_id = 0;
			if (is_object($sitepress) && function_exists('wpml_object_id_filter') && method_exists($sitepress, 'get_default_language')) {
				$default_product_id = wpml_object_id_filter($product_id, 'product', false, $sitepress->get_default_language());
			}

			return $default_product_id;
		}
	}

}

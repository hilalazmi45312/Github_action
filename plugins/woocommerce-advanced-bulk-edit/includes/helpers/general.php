<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (!function_exists('wcabe_starts_with')) {
	/**
	 * Function to check if string starting
	 * with given substring
	 *
	 * @param $string string The string to search within
	 * @param $startString string The string to search for
	 *
	 * @return bool
	 */
	function wcabe_starts_with ($string, $startString)
	{
		$len = strlen($startString);
		return (substr($string, 0, $len) === $startString);
	}
}

if (!function_exists('wcabe_ends_with')) {
	/**
	 * Function to check the string if it ends
	 * with given substring or not
	 *
	 * @param $string string The string to search within
	 * @param $endString string The string to search for
	 *
	 * @return bool
	 */
	function wcabe_ends_with($string, $endString)
	{
		$len = strlen($endString);
		if ($len == 0) {
			return true;
		}
		return (substr($string, -$len) === $endString);
	}
}

if (!function_exists('wcabe_verify_ajax_nonce')) {
	/**
	 * Checks the $_POST request for existing valid nonce
	 *
	 * @return bool
	 */
	function wcabe_verify_ajax_nonce()
	{
		return wp_verify_nonce( $_POST['nonce'], 'w3ex-advbedit-nonce' );
	}
}

if (!function_exists('wcabe_verify_ajax_nonce_or_die')) {
	/**
	 * Checks the $_POST request for existing valid nonce
	 *
	 * @param string $die_message Message that will be send back to the ajax request
	 *
	 * @return void
	 */
	function wcabe_verify_ajax_nonce_or_die($die_message='no-nonce')
	{
		if (!wcabe_verify_ajax_nonce()) {
			echo json_encode( [
				'error'  => $die_message,
				'products' => []
			] );
			error_log('dying');
			die();
		}
	}
}

if (!function_exists( 'wcabe_get_current_user_roles' )) {
	/**
	 * Get an array of the current user assigned roles
	 *
	 * @return array
	 */
	function wcabe_get_current_user_roles() {
		
		if( is_user_logged_in() ) { // check if there is a logged in user
			
			$user = wp_get_current_user(); // getting & setting the current user
			$roles = ( array ) $user->roles; // obtaining the role
			
			return $roles; // return the role for the current user
			
		} else {
			
			return array(); // if there is no logged in user return empty array
			
		}
	}
}

if (!function_exists( 'wcabe_get_users_who_can_create_products' )) {
	/**
	 * Get an array of all the users that have created products before and merge them with the roles that can create
     * products. This way we have all the product creators list.
	 *
	 * @return array
	 */
	function wcabe_get_users_who_can_create_products() {
		global $wpdb;

		$product_authors = $wpdb->get_results("
			SELECT DISTINCT u.ID, u.display_name
			FROM $wpdb->posts p
			INNER JOIN $wpdb->users u ON p.post_author = u.ID
			WHERE p.post_type = 'product'
		");// 
		$admin_users = get_users(["role__in" => ["administrator", "shop_manager", 'seller', 'vendor']]);

		$combined_users = [];

		foreach ($product_authors as $author) {
			$combined_users[$author->ID] = $author;
		}

		foreach ($admin_users as $admin) {
			if (!isset($combined_users[$admin->ID])) {
				$combined_users[$admin->ID] = (object) [
					"ID" => $admin->ID,
					"display_name" => $admin->display_name
				];
			}
		}

		return array_values($combined_users);

	}
}

if (!function_exists( 'wcabe_is_current_user_shop_manager' )) {
	/**
	 * Check the current user if he has shop_manager role or shop_manager role.
	 * Updated to use capability check instead of role check for better flexibility.
	 *
	 * @return bool
	 */
	function wcabe_is_current_user_shop_manager(): bool {
        // Check if user has permission to manage WooCommerce OR has shop_manager role
        return current_user_can('manage_woocommerce') || in_array('shop_manager', wp_get_current_user()->roles);
	}
}

if (!function_exists( 'wcabe_is_current_user_admin' )) {
	/**
	 * Check the current user if he has administrator role or shop_manager role.
	 * Updated to use capability check instead of role check for better flexibility.
	 *
	 * @return bool
	 */
	function wcabe_is_current_user_admin(): bool {
		// Check for manage_woocommerce capability which both administrator and shop_manager have
		return current_user_can('administrator');
	}
}

if (!function_exists('wcabe_log')) {
	/**
	 * Logs a message in the plugin's log, located in plugin's folder.
	 *
	 * Supports channel-based logging with config-based enable/disable control.
	 * Automatically handles log rotation when file size exceeds configured limit.
	 *
	 * @param string $msg The message to log
	 * @param string $channel The log channel (e.g., 'connection', 'update_error', 'gtin', 'general')
	 *
	 * @return void
	 */
	function wcabe_log($msg, $channel = 'general'): void {
		// Check if logging is enabled globally
		if (!W3ExABulkEdit_ConfigLoader::is_logging_enabled()) {
			return;
		}

		// Check if this specific channel is enabled
		if (!W3ExABulkEdit_ConfigLoader::is_log_channel_enabled($channel)) {
			return;
		}

		$log_path = WCABE_PLUGIN_PATH . W3ExABulkEdit_ConfigLoader::get('logging.file_path', 'wcabe.log');

		// Log rotation check
		$max_size = W3ExABulkEdit_ConfigLoader::get('logging.max_file_size', 10485760); // Default 10MB
		if (file_exists($log_path) && filesize($log_path) > $max_size) {
			$backup_path = $log_path . '.' . date('Y-m-d-His');
			rename($log_path, $backup_path);
		}

		// Format log entry with timestamp and channel
		$log = date("Y-m-d H:i:s") . " [{$channel}] > " . $msg . PHP_EOL;
		file_put_contents($log_path, $log, FILE_APPEND);
	}
}

if (!function_exists('wcabe_log_read')) {
	function wcabe_log_read() {
		$log_path = WCABE_PLUGIN_PATH . 'wcabe.log';
		return file_get_contents($log_path);
	}
}

if (!function_exists('wcabe_log_read_by_channel')) {
	/**
	 * Read log entries filtered by channel
	 *
	 * @param string|null $channel The channel to filter by (null = all channels)
	 * @param int|null $limit Maximum number of lines to return (null = all lines)
	 * @return string The filtered log contents
	 */
	function wcabe_log_read_by_channel($channel = null, $limit = null) {
		$log_path = WCABE_PLUGIN_PATH . W3ExABulkEdit_ConfigLoader::get('logging.file_path', 'wcabe.log');

		if (!file_exists($log_path)) {
			return '';
		}

		$lines = file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		// Filter by channel if specified
		if ($channel !== null) {
			$lines = array_filter($lines, function($line) use ($channel) {
				return strpos($line, "[{$channel}]") !== false;
			});
		}

		// Apply limit if specified
		if ($limit !== null && $limit > 0) {
			$lines = array_slice($lines, -$limit);
		}

		return implode("\n", $lines);
	}
}

if (!function_exists('wcabe_log_get_channels')) {
	/**
	 * Get list of all channels present in the log file
	 *
	 * @return array Array of unique channel names found in the log
	 */
	function wcabe_log_get_channels() {
		$log_path = WCABE_PLUGIN_PATH . W3ExABulkEdit_ConfigLoader::get('logging.file_path', 'wcabe.log');

		if (!file_exists($log_path)) {
			return [];
		}

		$lines = file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$channels = [];

		foreach ($lines as $line) {
			if (preg_match('/\[([^\]]+)\]/', $line, $matches)) {
				$channels[$matches[1]] = true;
			}
		}

		return array_keys($channels);
	}
}

if (!function_exists('wcabe_log_update_errors')) {
	/**
	 * Log update check errors in a structured format
	 *
	 * @param array $errors Array of error messages
	 */
	function wcabe_log_update_errors($errors) {
		if (!is_array($errors) || empty($errors)) {
			return;
		}

		wcabe_log('=== Update Check Failed ===', 'update_error');
		wcabe_log('Time: ' . current_time('Y-m-d H:i:s'), 'update_error');

		foreach ($errors as $error) {
			wcabe_log($error, 'update_error');
		}

		wcabe_log('=== End of Error Report ===', 'update_error');
	}
}

if (!function_exists('wcabe_connection_log_read')) {
	function wcabe_connection_log_read(): string {
		// Use the new channel-based log reader
		$log = wcabe_log_read_by_channel('connection');

		if (empty($log)) {
			return "No log available.";
		}

		return $log;
	}
}

if (!function_exists('wcabe_connection_log_clear')) {
	function wcabe_connection_log_clear() {
		$log_file  = WCABE_PLUGIN_PATH . 'wcabe.log';
		$temp_file = WCABE_PLUGIN_PATH . 'tempwcabe.log';
		
		if ( $handle = fopen( $log_file, "r" ) ) {
			if ( $tempHandle = fopen( $temp_file, "w" ) ) {
				while ( ( $line = fgets( $handle ) ) !== false ) {
					if ( strpos( $line, '[conn-log]' ) === false ) {
						fwrite( $tempHandle, $line );
					}
				}
				fclose( $tempHandle );
			} else {
				wcabe_log( "Unable to open the temporary file for writing.", 'general' );
			}
			fclose( $handle );
			
			// Replace the original log file with the filtered content from the temp file
			rename( $temp_file, $log_file );
		} else {
		}
	}
}

if (!function_exists('version_checks_for_plugin_updates')) {
	function version_checks_for_plugin_updates($remote): array {
		$errors = [];
		if (!isset($remote->version)) {
			$errors[] = "Warning: Update info version is not set!";
		}
		if (version_compare( WCABE_VERSION, $remote->version, '>=' )) {
			$errors[] = "Plugin version: ".WCABE_VERSION.", update version: ".$remote->version." (no need to update)!";
		}
		if (version_compare( $remote->requires, get_bloginfo( 'version' ), '>' )) {
			$errors[] = "Warning: Update requires newer WP version! Current version: ".get_bloginfo( 'version' ).", update version: ".$remote->requires." (need to update WP)!";
		}
		if (version_compare( $remote->requires_php, PHP_VERSION, '>' )) {
			$errors[] = "Warning: Update requires newer PHP version! Current version: ".PHP_VERSION.", update version: ".$remote->requires_php." (need to update PHP)!";
		}
		
		return $errors;
	}
}



if (!function_exists('wcabe_check_plugin_update_connection')) {
	/**
	 * Enhanced connection testing function with better error handling
	 *
	 * @param bool $log_full_response Whether to log the full response
	 * @return array Test results including success status, message and details
	 */
	function wcabe_check_plugin_update_connection($log_full_response = false): array {
		$test_results = array(
			'success' => false,
			'message' => '',
			'details' => array(),
			'timestamp' => current_time('mysql')
		);

		// Start timing
		$start_time = microtime(true);

		try {
			// First, do a quick connectivity check
			$ping_response = wp_remote_head(WCABE_UPDATE_URL, array(
				'timeout' => 5,
				'sslverify' => true
			));

			if (is_wp_error($ping_response)) {
				throw new Exception('Connection failed: ' . $ping_response->get_error_message());
			}

			// Full update check - request raw response if logging is enabled
			$result = W3ExAdvancedBulkEditMain::request(true, $log_full_response);

			// Calculate response time
			$response_time = round((microtime(true) - $start_time) * 1000, 2);

			// Handle the response based on type
			if (is_wp_error($result)) {
				throw new Exception($result->get_error_message());
			}

			if (is_string($result)) {
				// Error message
				throw new Exception($result);
			} else if (is_array($result) && isset($result['decoded'])) {
				// We got both decoded and raw response
				$response = $result['decoded'];
				$raw_json = $result['raw'];

				$test_results['success'] = true;
				$test_results['message'] = 'Connection successful';
				$test_results['details'] = array(
					'response_time' => $response_time . 'ms',
					'server_version' => $response->version ?? 'Unknown',
					'license_status' => !empty($response->download_url) ? 'Valid' : 'Invalid',
					'download_status' => empty($response->download_msg) ? 'OK' : $response->download_msg
				);

				// Log results
				wcabe_log("*** Check START *** ", 'connection');
				wcabe_log("Connection successful!", 'connection');
				wcabe_log("Response time: " . $response_time . "ms", 'connection');
				wcabe_log("Update version: ".$response->version, 'connection');
				wcabe_log("Download url: ". (!empty($response->download_url) ? 'Valid' : 'Invalid'), 'connection');
				wcabe_log("Download status: ". (empty($response->download_msg) ? 'OK' : $response->download_msg), 'connection');

				$version_checks = version_checks_for_plugin_updates($response);
				if (!empty($version_checks) && is_array($version_checks)) {
					$test_results['details']['version_checks'] = $version_checks;
					foreach ($version_checks as $version_check_error) {
						wcabe_log($version_check_error, 'connection');
					}
				}

				// Log full raw JSON response if checkbox was checked
				if ($log_full_response && !empty($raw_json)) {
					// Parse the JSON and remove the bulky description and changelog fields
					$json_for_logging = json_decode($raw_json, true);
					if ($json_for_logging) {
						if (isset($json_for_logging['sections']['description'])) {
							unset($json_for_logging['sections']['description']);
						}
						if (isset($json_for_logging['sections']['changelog'])) {
							unset($json_for_logging['sections']['changelog']);
						}
					}

					wcabe_log("", 'connection');
					wcabe_log("=== Full Response (Raw JSON) ===", 'connection');
					// Split JSON into lines and log each line separately
					$cleaned_json = json_encode($json_for_logging, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
					$json_lines = explode("\n", $cleaned_json);
					foreach ($json_lines as $line) {
						wcabe_log($line, 'connection');
					}
					wcabe_log("=== End Full Response ===", 'connection');
				}

				wcabe_log("*** Check END *** ", 'connection');
			} else if ($result instanceof StdClass) {
				// Old format - just decoded response
				$response = $result;

				$test_results['success'] = true;
				$test_results['message'] = 'Connection successful (legacy format)';
				$test_results['details'] = array(
					'response_time' => $response_time . 'ms',
					'server_version' => $response->version ?? 'Unknown',
					'license_status' => !empty($response->download_url) ? 'Valid' : 'Invalid',
					'download_status' => empty($response->download_msg) ? 'OK' : $response->download_msg,
					'note' => 'Legacy response format detected'
				);

				wcabe_log("*** Check START *** ", 'connection');
				wcabe_log("Connection successful (legacy format)!", 'connection');
				wcabe_log("Response time: " . $response_time . "ms", 'connection');
				wcabe_log("Update version: ".$response->version, 'connection');
				wcabe_log("Download url: ". (!empty($response->download_url) ? 'Valid' : 'Invalid'), 'connection');
				wcabe_log("Download status: ". (empty($response->download_msg) ? 'OK' : $response->download_msg), 'connection');

				$version_checks = version_checks_for_plugin_updates($response);
				if (!empty($version_checks) && is_array($version_checks)) {
					$test_results['details']['version_checks'] = $version_checks;
					foreach ($version_checks as $version_check_error) {
						wcabe_log($version_check_error, 'connection');
					}
				}

				// Log full raw JSON response if checkbox was checked (won't have raw in this case)
				if ($log_full_response) {
					// Convert object to array and remove the bulky description and changelog fields
					$response_array = json_decode(json_encode($response), true);
					if ($response_array) {
						if (isset($response_array['sections']['description'])) {
							unset($response_array['sections']['description']);
						}
						if (isset($response_array['sections']['changelog'])) {
							unset($response_array['sections']['changelog']);
						}
					}

					wcabe_log("", 'connection');
					wcabe_log("=== Full Response (Raw JSON) ===", 'connection');
					// Encode the cleaned object
					$json_lines = explode("\n", json_encode($response_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
					foreach ($json_lines as $line) {
						wcabe_log($line, 'connection');
					}
					wcabe_log("=== End Full Response ===", 'connection');
				}

				wcabe_log("*** Check END *** ", 'connection');
			} else {
				// Unexpected format
				$test_results['success'] = true; // Still successful connection
				$test_results['message'] = 'Connection successful (unknown format)';
				$test_results['details']['response_time'] = $response_time . 'ms';
				$test_results['details']['note'] = 'Unknown response format detected';
				wcabe_log("Connection successful (unknown response format)", 'connection');
			}

		} catch (Exception $e) {
			$test_results['message'] = $e->getMessage();
			$test_results['details']['error_code'] = $e->getCode();
			wcabe_log("Connection failed: " . $e->getMessage(), 'connection');
		}

		// Store results
		update_option('wcabe_last_connection_test', $test_results);

		// Log final results
		wcabe_log(json_encode($test_results), 'connection');

		return $test_results;
	}
}

if (!function_exists('wcabe_fix_conflicting_plugins')) {
	/**
	 * De-registers the JS scripts which are loading improperly on WCABE admin page and are conflicting,
	 * preventing WCABE scripts from loading.
	 *
	 * @return void
	 */
	function wcabe_fix_conflicting_plugins(): void {
		$conflicting_scripts_list = [
			// Fixes for conflicting plugins
			'jquery-magnific-popup',
			'porto-builder-admin',
			'tidio-chat-admin',
			'bm-admin',
			'wbm-admin',
			'lpc_admin_notices',
			'paypal-for-woocommerce-multi-account-management',
			'soisy-pagamento-rateale',
			'my-great-script',
			'cfpa-admin.js',
			'con-apply-setting.js',
			'fma_order_on_whatsapp_admin',
			'fma_order_on_whatsapp_my_script_for_whatsapp_contact',
			'itsec-core-admin-notices',
			'validate-config-form',
			'service-selector',
			'slicewp-script',
			'cardcom-admin-script',
			'flizpay',
			'alpus-plugin-framework-admin',
			'awca_js',
			'yoast-seo-premium-metabox',
			'chosen',
			'ywpi_backend_js',
			'wallet-system-for-woocommerceadmin-notice',
		];
		
		if (defined('YITH_YWPI_ASSETS_URL')) {
			$conflicting_scripts_list[] = 'ywpi_' . YITH_YWPI_ASSETS_URL . '-js';
		}
		
		if ( class_exists( 'WC_Customer_Order_CSV_Export' ) ) {
			$conflicting_scripts_list[] = 'wc_customer_order_export_background_export_batch_handler';
			$conflicting_scripts_list[] = 'wc-customer-order-csv-export-admin';
			$conflicting_scripts_list[] = 'wc-customer-order-csv-export-jquery-timepicker';
			
			add_action( 'admin_footer', function() {
				global $wc_queued_js;
				if ( ! empty( $wc_queued_js ) ) {
					$wc_queued_js = preg_replace(
						'/window\.wc_customer_order_export_background_export_batch_handler\s*=\s*new\s+[^;]+;?\s*/i',
						'',
						$wc_queued_js
					);
				}
			}, 20 );
		}
		
		foreach ($conflicting_scripts_list as $conflicting_script) {
			if (wp_script_is($conflicting_script, 'enqueued'))
			{
				wp_deregister_script( $conflicting_script );
			}
		}

		$conflicting_styles_list = [
			// Fixes for conflicting styles
			'chosen',
		];

		foreach ($conflicting_styles_list as $conflicting_style) {
			if (wp_style_is($conflicting_style, 'enqueued'))
			{
				wp_deregister_style( $conflicting_style );
			}
		}

	}
}

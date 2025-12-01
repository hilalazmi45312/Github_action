<?php
/**
 * Plugin Name: FunnelKit Funnel Builder Basic
 * Plugin URI: https://funnelkit.com/wordpress-funnel-builder/
 * Description: Create a conversion optimised checkout and thank you page for your store within next few mins. Get started by importing one of premium templates.
 * Version: 3.13.2
 * Author: FunnelKit
 * Author URI: https://funnelkit.com
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /languages/
 *
 * Requires at least: 4.9.0
 * Tested up to: 6.8.3
 * WooFunnels: true
 *
 * FunnelKit Funnel Builder Basic is free software.
 * You can redistribute it and/or modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * FunnelKit Funnel Builder Basic is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Funnel Builder Pro. If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! function_exists( 'fk_funnel_builder_pro_check' ) ) {

	/**
	 * Function to check if funnel builder pro is active
	 * @return bool True|False
	 */
	function fk_funnel_builder_pro_check() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		$is_funnel_pro = in_array( 'funnel-builder-pro/funnel-builder-pro.php', $active_plugins, true ) || array_key_exists( 'funnel-builder-pro/funnel-builder-pro.php', $active_plugins );


		return $is_funnel_pro;
	}
}


if ( fk_funnel_builder_pro_check() ) {
	return;
}

/**
 * Defining necessary constants
 */
if ( ! defined( 'WFFN_BASIC_FILE' ) ) {
	define( 'WFFN_BASIC_FILE', __FILE__ );

}


/**
 * Adding funnel builder powerpack
 */
require_once 'funnel-builder-powerpack.php';


/**
 * once all modules files included, loading full modules
 */
add_action( 'wffn_pro_modules_loaded', function () {


	$modules = apply_filters( 'wffn_pro_modules', array(
		'checkout' => 'checkout/woofunnels-aero-checkout.php',
	) );


	try {
		if ( WFFN_Pro_Core()->is_dependency_exists ) {
			foreach ( $modules as $module ) {
				WFFN_Pro_Modules::maybe_load( $module );
			}
		}
	} catch ( Exception|Error $e ) {

		if ( strpos( $e->getMessage(), 'Failed opening required' ) !== false ) {
			wffn_basic_show_missing_file_notice();
		}
	}

} );

function wffn_basic_show_missing_file_notice() {
	add_action( 'admin_notices', function () {
		?>
        <div class="notice notice-error">
            <h3><?php echo esc_html__( 'Error: Missing Files Detected', 'funnel-builder-pro' ); ?></h3>
            <p><?php
				printf( wp_kses( /* translators: %s is a placeholder for the account link */ __( 'It appears that some critical PHP files for the Funnel Builder Pro plugin are missing. Please re-install the plugin from your <a target="_blank" href="%s">account</a> to restore full functionality.', 'funnel-builder-pro' ), array( 'a' => array( 'href' => array() ) ) // Allow only the 'a' tag with 'href' attribute
				), esc_url( 'https://myaccount.funnelkit.com' ) );
				?></p>
        </div>
		<?php
	} );


}

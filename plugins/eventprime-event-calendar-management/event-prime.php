<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://theeventprime.com
 * @since             1.0.0
 * @package           Eventprime_Event_Calendar_Management
 *
 * @wordpress-plugin
 * Plugin Name:       EventPrime – Modern Events Calendar, Bookings and Tickets
 * Plugin URI:        https://theeventprime.com
 * Description:       Beginner-friendly Events Calendar plugin to create free as well as paid Events. Includes Event Types, Event Sites & Performers too.
 * Version:           4.2.1.0
 * Author:            EventPrime Event Calendar
 * Author URI:        https://theeventprime.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       eventprime-event-calendar-management
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'EVENTPRIME_VERSION', '4.2.1.0' );
define('EM_DB_VERSION',4.0);
if( ! defined( 'EP_PLUGIN_FILE' ) ) {
    define( 'EP_PLUGIN_FILE', __FILE__ );
}

if( ! defined( 'EP_PLUGIN_BASE' ) ) {
    define( 'EP_PLUGIN_BASE', plugin_basename( __FILE__ ) );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-eventprime-event-calendar-management-activator.php
 */
function activate_eventprime_event_calendar_management() {
        add_option('emagic_db_version',EM_DB_VERSION);
	$ep_activator = new Eventprime_Event_Calendar_Management_Activator;
        $ep_activator->activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-eventprime-event-calendar-management-deactivator.php
 */
function deactivate_eventprime_event_calendar_management() {
    
        $ep_deactivator = new Eventprime_Event_Calendar_Management_Deactivator;
	$ep_deactivator->deactivate();
}

register_activation_hook( __FILE__, 'activate_eventprime_event_calendar_management' );
register_deactivation_hook( __FILE__, 'deactivate_eventprime_event_calendar_management' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-eventprime-event-calendar-management.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_eventprime_event_calendar_management() {

	$plugin = new Eventprime_Event_Calendar_Management();
	$plugin->run();

}
run_eventprime_event_calendar_management();

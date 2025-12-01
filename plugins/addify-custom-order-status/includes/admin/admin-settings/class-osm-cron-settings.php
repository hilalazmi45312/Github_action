<?php
/**
 * Settings for cron jobs
 *
 * Displays the settings for cron job
 *
 * @package Order Status Manager
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
if (!class_exists('KA_Osm_Cron_Settings')) {
	class KA_Osm_Cron_Settings {
		public function __construct() {
			add_action('admin_init', array( $this, 'osm_cron_settings' ), 99);
			add_action('admin_menu', array( $this, 'kaosm_admin_menu' ), 99);
		}
		/**
		 * Function to add submenu for cron settings
		 */
		public function kaosm_admin_menu() {
			add_submenu_page(
				'woocommerce',
				esc_html__('Order Statuses', 'addify_osm'), // Page title.
				esc_html__('Order Statuses', 'addify_osm'), // Menu title.
				'manage_options', // Capability.
				'addify_osm_cron_setting',  // Menu-slug.
				array( $this, 'kaosm_menu_callback' ),   // Function that will render its output.
				5  // Position of the menu option.
			);
		}
		/**
		 * Submenu callback function
		 */
		public function kaosm_menu_callback() {
			if (!current_user_can('manage_options')) {
				return;
			}
			if (isset($_GET['tab'])) {
				$active_tab = sanitize_text_field(wp_unslash($_GET['tab']));
			} else {
				$active_tab = 'cron';
			}
			?>         
			<div class="wrap woocommerce">
				<h2><?php echo esc_html__('Cron Job Settings', 'addify_osm'); ?></h2>
				<?php settings_errors(); ?>

			</div>
			<div class="cron-settings">
				

	   
			<form method="post" action="options.php" class="kaosm_options_form">
				<?php
				if ('cron' === $active_tab) {

					settings_fields('kaosm_cron_fields');
					do_settings_sections('kaosm_cron_section');
				}
				submit_button();
				?>
			</form>
			</div>
			<?php
		}
		/**
		 * Function to Add time settings fields
		 */
		public function osm_cron_settings() {
			add_settings_section(
				'kaosm-cron-sec',         // ID used to identify this section and with which to register options.
				'',   // Title to be displayed on the administration page.
				array( $this, 'kaosm_settings_desc' ), // Callback used to render the description of the section.
				'kaosm_cron_section'                           // Page on which to add this section of options.
			);

			add_settings_field(
				'kaosm_time_type',                      // ID used to identify the field throughout the theme.
				esc_html__('Cron Time Type', 'addify_osm'),    // The label to the left of the option interface element.
				array( $this, 'kaosm_time_type_callback' ),   // The name of the function responsible for rendering the option interface.
				'kaosm_cron_section',                          // The page on which this option will be displayed.
				'kaosm-cron-sec',         // The name of the section to which this field belongs.
				array( esc_html__('Time type to schedule the cron Job. Default type is Minutes.', 'addify_osm') )
			);

			register_setting(
				'kaosm_cron_fields',
				'kaosm_time_type'
			);

			add_settings_field(
				'kaosm_cron_time',                      // ID used to identify the field throughout the theme.
				esc_html__('Cron Job Time', 'addify_osm'),    // The label to the left of the option interface element.
				array( $this, 'kaosm_cron_time_callback' ),   // The name of the function responsible for rendering the option interface.
				'kaosm_cron_section',                          // The page on which this option will be displayed.
				'kaosm-cron-sec',         // The name of the section to which this field belongs.
				array( esc_html__('Time to schedule the cron Job. Default time is 15 Minutes.', 'addify_osm') )
			);

			register_setting(
				'kaosm_cron_fields',
				'kaosm_cron_time'
			);
		}
		public function kaosm_settings_desc() {
			$osm_cron_desc ='<p>This will use <a target="_blank" href="https://developer.wordpress.org/plugins/cron/">WordPress Crons</a> to process the order status automation rules periodically.</p>'; 
			echo wp_kses_post($osm_cron_desc);
		}

		/**
		 * Select time.
		 *
		 * @param array $args arguments.
		 */
		public function kaosm_time_type_callback( $args = array() ) {
			$value = get_option('kaosm_time_type');
			$value = empty($value) ? 'minutes' : $value;
			?>
			<select name="kaosm_time_type">
				<option value="minutes" <?php echo 'minutes' === $value ? esc_attr('selected') : ''; ?>> <?php echo esc_html__('Minutes', 'addify_osm'); ?> </option>
				<option value="hours" <?php echo 'hours' === $value ? esc_attr('selected') : ''; ?>> <?php echo esc_html__('Hours', 'addify_osm'); ?> </option>
				<option value="days" <?php echo 'days' === $value ? esc_attr('selected') : ''; ?>> <?php echo esc_html__('Days', 'addify_osm'); ?> </option>
			</select>
			<p class="description"> <?php echo wp_kses_post($args[0]); ?> </p>
			<?php
		}

		/**
		 * Select time.
		 *
		 * @param array $args arguments.
		 */
		public function kaosm_cron_time_callback( $args = array() ) {
			$value = get_option('kaosm_cron_time');
			$value = empty($value) ? 15 : $value;
			?>
			<input type="number" min="1" name="kaosm_cron_time" id="kaosm_cron_time" value="<?php echo intval($value); ?>" />
			<p class="description"> <?php echo wp_kses_post($args[0]); ?> </p>
			<?php
		}
	}
	new KA_Osm_Cron_Settings();
}

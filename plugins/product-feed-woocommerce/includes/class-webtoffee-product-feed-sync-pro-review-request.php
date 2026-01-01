<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.webtoffee.com/
 * @since      1.0.0
 *
 * @package Webtoffee_Product_Feed_Sync_Pro\includes\Review_Request
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The review seeking plugin class.
 *
 * This is used to seek for reviews after 8 days of active use.
 */
class Webtoffee_Product_Feed_Sync_Pro_Review_Request {

	/**
	 * The config options are below
	 * The unique identifier title of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	private $plugin_title               = 'WebToffee WooCommerce Product Feed & Sync Manager';
	/**
	 * The product page URL of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The URL is used to uniquely identify this plugin.
	 */
	private $review_url                 = 'https://woocommerce.com/products/import-export-suite-for-woocommerce/';
	/**
	 * The unique identifier option prefix of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	private $plugin_prefix              = 'wt_pfs'; /* must be unique name */
	/**
	 * When did the banner to show.
	 *
	 * @since    1.0.0
	 * @var      int    $days_to_show_banner    When did the banner to show.
	 */
	private $days_to_show_banner        = 45; /* when did the banner to show */
	/**
	 * Remind interval in days.
	 *
	 * @since    1.0.0
	 * @var      int    $remind_days    Remind interval in days, when did the banner to show.
	 */
	private $remind_days                = 20; /* remind interval in days */
	/**
	 * The URL of WebToffee logo.
	 *
	 * @since    1.0.0
	 * @var      string    $webtoffee_logo_url    Remind interval in days, when did the banner to show
	 */
	private $webtoffee_logo_url         = '';
	/**
	 * The banner to show count start date of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $start_date    Banner to show count start date. plugin installed date, remind me later added date
	 */
	private $start_date                 = 0; /* banner to show count start date. plugin installed date, remind me later added date */
	/**
	 * Current state of the banner.
	 * 1: active, 2: waiting to show(first after installation), 3: closed by user/not interested to review, 4: user done the review, 5:remind me later
	 *
	 * @since    1.0.0
	 * @var      int    $current_banner_state    Current state of the banner
	 */
	private $current_banner_state       = 2; /* 1: active, 2: waiting to show(first after installation), 3: closed by user/not interested to review, 4: user done the review, 5:remind me later */
	/**
	 * The WP option name to save banner state.
	 *
	 * @since    1.0.0
	 * @var      string    $banner_state_option_name    The string used to uniquely identify this plugin option.
	 */
	private $banner_state_option_name   = ''; /* WP option name to save banner state */
	/**
	 * The  WP option name to save start date
	 *
	 * @since    1.0.0
	 * @var      string    $start_date_option_name    The string used to uniquely identify this plugin option.
	 */
	private $start_date_option_name     = ''; /* WP option name to save start date */
	/**
	 * The CSS class name for Banner HTML element.
	 *
	 * @since    1.0.0
	 * @var      string    $banner_css_class    CSS class name for Banner HTML element
	 */
	private $banner_css_class           = ''; /* CSS class name for Banner HTML element. */
	/**
	 * The Banner messsage.
	 *
	 * @since    1.0.0
	 * @var      string    $banner_message    Banner message
	 */
	private $banner_message             = ''; /* Banner message. */
	/**
	 * The Remind me later button text.
	 *
	 * @since    1.0.0
	 * @var      string    $later_btn_text    Remind me later button text
	 */
	private $later_btn_text             = ''; /* Remind me later button text */
	/**
	 * The Never review button text.
	 *
	 * @since    1.0.0
	 * @var      string    $never_btn_text    Never review button text
	 */
	private $never_btn_text             = ''; /* Never review button text. */
	/**
	 * Review now button text.
	 *
	 * @since    1.0.0
	 * @var      string    $review_btn_text   Review now button text
	 */
	private $review_btn_text            = ''; /* Review now button text. */
	/**
	 * The Name of ajax action to save banner state.
	 *
	 * @since    1.0.0
	 * @var      string    $ajax_action_name    Name of ajax action to save banner state.
	 */
	private $ajax_action_name           = ''; /* Name of ajax action to save banner state. */
	/**
	 * The allowed review button actions.
	 *
	 * @since    1.0.0
	 * @var      string    $allowed_action_type_arr    Allowed actions
	 */
	private $allowed_action_type_arr    = array(
		'later', /* remind me later */
		'never', /* never */
		'review', /* review now */
		'closed', /* not interested */
	);
	/**
	 * Define the review functionality of the plugin.
	 *
	 * Set the option name and the plugin version that can be used throughout the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Set config vars.
		$this->set_vars();

		register_activation_hook( WT_PRODUCT_FEED_PRO_PLUGIN_FILENAME, array( $this, 'on_activate' ) );
		register_deactivation_hook( WT_PRODUCT_FEED_PRO_PLUGIN_FILENAME, array( $this, 'on_deactivate' ) );

		add_action( 'init', array( $this, 'wt_product_feed_pro_init_review_request' ) );

	}

	/**
	 * Initialize translations and hooks for review request functionality
	 */
	public function wt_product_feed_pro_init_review_request() {

		if ( !$this->check_condition() ) {
			return;
		}

		/*
		 * Checks the banner is active now
		 */
		$this->banner_message = sprintf(
			/* translators: 1: HTML b open. 2: HTML b close */
			__(
				'Hey, we at %1$sWebToffee%2$s would like to thank you for using our plugin. We would really appreciate if you could take a moment to drop a quick review that will inspire us to keep going.',
				'product-feed-woocommerce'
			),
			'<b>',
			'</b>'
		);

		/* button texts */
		$this->later_btn_text   = __(
			'Remind me later',
			'product-feed-woocommerce'
		);
		$this->never_btn_text   = __(
			'Not interested',
			'product-feed-woocommerce'
		);
		$this->review_btn_text  = __(
			'Review now',
			'product-feed-woocommerce'
		);

		add_action( 'admin_notices', array( $this, 'show_banner' ) ); /* show banner */
		add_action( 'admin_print_footer_scripts', array( $this, 'add_banner_scripts' ) ); /* add banner scripts */
		add_action( 'wp_ajax_' . $this->ajax_action_name, array( $this, 'process_user_action' ) ); /* process banner user action */
	}

	/**
	 *  Set config vars
	 */
	public function set_vars() {
		$this->ajax_action_name             = $this->plugin_prefix . '_process_user_review_action';
		$this->banner_state_option_name     = $this->plugin_prefix . '_review_request';
		$this->start_date_option_name       = $this->plugin_prefix . '_start_date';
		$this->banner_css_class             = $this->plugin_prefix . '_review_request';

		$this->start_date                   = absint( get_option( $this->start_date_option_name ) );
		$banner_state                       = absint( get_option( $this->banner_state_option_name ) );
		$this->current_banner_state         = ( 0 == $banner_state ? $this->current_banner_state : $banner_state );
		$this->webtoffee_logo_url           = WT_PRODUCT_FEED_PRO_PLUGIN_URL . 'images/webtoffee-logo_small.png';
	}

	/**
	 *  Actions on plugin activation
	 *  Saves activation date
	 */
	public function on_activate() {
		if ( 0 == $this->start_date ) {
			$this->reset_start_date();
		}
	}

	/**
	 *  Actions on plugin deactivation
	 *  Removes activation date
	 */
	public function on_deactivate() {
		delete_option( $this->start_date_option_name );
	}

	/**
	 *  Reset the start date.
	 */
	private function reset_start_date() {
		update_option( $this->start_date_option_name, time() );
	}

	/**
	 *  Update the banner state.
	 *
	 * @param int $val Store the current state of the banner.
	 */
	private function update_banner_state( $val ) {
		update_option( $this->banner_state_option_name, $val );
	}

	/**
	 *  Prints the banner
	 */
	public function show_banner() {
		$this->update_banner_state( 1 ); /* update banner active state */
		?>
		<div class="<?php echo esc_attr( $this->banner_css_class ); ?> notice-info notice is-dismissible">
			<?php
			if ( '' != $this->webtoffee_logo_url ) {
				?>
				<h3 style="margin: 10px 0;"><?php echo esc_html( $this->plugin_title ); ?></h3>
				<?php
			}
			?>
			<p>
				<?php echo wp_kses_post( $this->banner_message ); ?>
			</p>
			<p>
				<a class="button button-secondary" style="color:#333; border-color:#ccc; background:#efefef;" data-type="later"><?php echo esc_html( $this->later_btn_text ); ?></a>
				<a class="button button-primary" data-type="review"><?php echo esc_html( $this->review_btn_text ); ?></a>
			</p>
			<div class="wt-cli-review-footer" style="position: relative;">
			<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
			<span class="wt-cli-footer-icon" style="position: absolute;right: 0;bottom: 10px;"><img src="<?php echo esc_url( $this->webtoffee_logo_url ); ?>" style="max-width:100px;"></span>
			</div>
		</div>
		<?php
	}

	/**
	 *  Ajax hook to process user action on the banner
	 */
	public function process_user_action() {
		check_ajax_referer( $this->plugin_prefix );
		if ( isset( $_POST['wt_review_action_type'] ) ) {
			$action_type = sanitize_text_field( wp_unslash( $_POST['wt_review_action_type'] ) );

			/* current action is in allowed action list */
			if ( in_array( $action_type, $this->allowed_action_type_arr ) ) {
				if ( 'never' == $action_type || 'closed' == $action_type ) {
					$new_banner_state = 3;
				} elseif ( 'review' == $action_type ) {
					$new_banner_state = 4;
				} else {
					/* reset start date to current date */
					$this->reset_start_date();
					$new_banner_state = 5; /* remind me later */
				}
				$this->update_banner_state( $new_banner_state );
			}
		}
		exit();
	}

	/**
	 *  Add banner JS to admin footer
	 */
	public function add_banner_scripts() {
		$ajax_url = admin_url( 'admin-ajax.php' );
		$nonce = wp_create_nonce( $this->plugin_prefix );
		?>
		<script type="text/javascript">
			(function($) {
				"use strict";

				/* prepare data object */
				var data_obj = {
					_wpnonce: '<?php echo esc_html( $nonce ); ?>',
					action: '<?php echo esc_html( $this->ajax_action_name ); ?>',
					wt_review_action_type: ''
				};

				$(document).on('click', '.<?php echo esc_html( $this->banner_css_class ); ?> a.button', function(e) {
					e.preventDefault();
					var elm = $(this);
					var btn_type = elm.attr('data-type');
					if (btn_type == 'review') {
						window.open('<?php echo esc_url( $this->review_url ); ?>');
					}
					elm.parents('.<?php echo esc_html( $this->banner_css_class ); ?>').hide();

					data_obj['wt_review_action_type'] = btn_type;
					$.ajax({
						url: '<?php echo esc_url( $ajax_url ); ?>',
						data: data_obj,
						type: 'POST'
					});

				}).on('click', '.<?php echo esc_html( $this->banner_css_class ); ?> .notice-dismiss', function(e) {
					e.preventDefault();
					data_obj['wt_review_action_type'] = 'closed';
					$.ajax({
						url: '<?php echo esc_url( $ajax_url ); ?>',
						data: data_obj,
						type: 'POST',
					});

				});

			})(jQuery)
		</script>
		<?php
	}

	/**
	 *  Checks the condition to show the banner
	 */
	private function check_condition() {

		if ( 1 == $this->current_banner_state ) { /* currently showing then return true */
			return true;
		}

		if ( 2 == $this->current_banner_state || 5 == $this->current_banner_state ) { /* only waiting/remind later state */
			if ( 0 == $this->start_date ) {
				/*
				Unable to get activated date
				set current date as activation date
				 */
				$this->reset_start_date();
				return false;
			}

			$days = ( 2 == $this->current_banner_state ? $this->days_to_show_banner : $this->remind_days );

			$date_to_check = $this->start_date + ( 86400 * $days );
			if ( $date_to_check <= time() ) { /* time reached to show the banner */
				return true;
			} else {
				return false;
			}
		}

		return false;
	}
}
new Webtoffee_Product_Feed_Sync_Pro_Review_Request();

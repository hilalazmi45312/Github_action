<?php

#[AllowDynamicProperties]
class BWFAN_Pro_Admin {

	private static $ins = null;
	public $admin_path;
	public $admin_url;

	private function __construct() {
		$this->admin_path = BWFAN_PRO_PLUGIN_DIR . '/admin';
		$this->admin_url  = BWFAN_PRO_PLUGIN_URL . '/admin';

		add_action( 'bwfan_wp_sendemail_setting_html', [ $this, 'utm_fields' ] );

		add_filter( 'bwfan_automation_global_js_data', [ $this, 'language_settings' ] );

		add_action( 'admin_head', [ $this, 'spinner_gif' ] );

		add_action( 'admin_bar_menu', [ $this, 'bwfan_pro_add_menu_in_admin_bar' ], 9999, 1 );
	}

	public static function get_instance() {
		if ( is_null( self::$ins ) ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function utm_fields( $event ) {
		include __DIR__ . '/views/utm_fields.php';
	}

	/**
	 *  adding language options in case any of the language plugin like wpml, polylang,translatepress activated
	 */

	public function language_settings( $settings ) {

		return BWFAN_PRO_Common::get_language_settings( $settings );
	}

	public function spinner_gif() {
		ob_start();
		?>
        <style>
            .bwfan_btn_spin_blue {
                opacity: 1 !important;
                position: relative;
                color: rgba(0, 163, 161, .05) !important;
                pointer-events: none !important;
            }

            .bwfan_btn_spin_blue::-moz-selection {
                color: rgba(0, 163, 161, .05) !important;
            }

            .bwfan_btn_spin_blue::selection {
                color: rgba(0, 163, 161, .05) !important;
            }

            .bwfan_btn_spin_blue:after {
                animation: bwfan_spin .5s infinite linear;
                border: 2px solid #0071a1;
                border-radius: 50%;
                border-right-color: transparent !important;
                border-top-color: transparent !important;
                content: "";
                display: block;
                width: 12px;
                height: 12px;
                top: 50%;
                left: 50%;
                margin-top: -8px;
                margin-left: -8px;
                position: absolute;
            }

            a.bwfan-add-repeater-data i {
                font-size: 16px;
                width: 16px;
                height: 16px;
                color: #444;
            }
        </style>
		<?php
		echo ob_get_clean(); //phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Register admin bar autonami links.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar
	 */
	public function bwfan_pro_add_menu_in_admin_bar( \WP_Admin_Bar $wp_admin_bar ) {
		// Always add "search contact" link even at admin pages.
		?>
        <style type="text/css">
            #wp-admin-bar-bwfan_admin_bar_search_contact-default {
                width: 400px;
                position: relative;
                padding: 0 !important;
            }

            #wp-admin-bar-bwfan_admin_bar_search_contact-default li > .ab-item {
                display: none;
            }

            #wpadminbar .quicklinks .menupop ul li .bwf-search-contacts .ab-item, #wpadminbar .quicklinks .menupop.hover ul li .bwf-search-contacts .ab-item {
                height: auto;
            }

            #wp-admin-bar-bwfan_admin_bar_search_contact-default li .bwf-search-contacts {
                background: #fff;
                padding: 5px;
            }

            #wp-admin-bar-bwfan_admin_bar_search_contact-default li .bwf-search-contacts .bwf-search-input input {
                width: 100%;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                padding: 0px 10px;
                font-size: 15px;
                color: #000;
                box-sizing: border-box;
            }

            #wp-admin-bar-bwfan_admin_bar_search_contact-default li .label {
                font-size: 14px;
                display: block;
                color: #000;
                font-weight: 500;
                cursor: default;
            }

            #bwf-contact-list-suggestion-list {
                padding: 10px;
                white-space: normal;
            }

            #bwf-contact-list-suggestion-list a.bwf-contact-suggestion {
                width: fit-content;
                border-radius: 18px;
                padding: 0px 15px;
                background-color: #e5e5e5;
                border: 1px solid #9d9898;
                color: #000 !important;
                display: inline-block;
                margin: 0 0 7px 7px;
            }

            #wp-admin-bar-bwfan_admin_bar_search_contact-default li .bwf-quick-links {
                padding: 12px 20px;
                background: #f7f5f5;
            }

            #wp-admin-bar-bwfan_admin_bar_search_contact-default .bwf-quick-links ul li a.ab-item, #wp-admin-bar-bwfan_admin_bar_search_contact-default .bwf-quick-links ul a.ab-item {
                display: none;
            }

            #wp-admin-bar-bwfan_admin_bar_search_contact-default li .bwf-quick-links ul li {
                padding: 10px 0;
                margin: 0;
                display: grid;
                grid-template-columns: 50% 50%;
            }

            #wp-admin-bar-bwfan_admin_bar_search_contact-default li .bwf-quick-links ul li {
                list-style: none;
                padding: 0;
                width: 48%;
                display: inline-block;
            }

            #wpadminbar #wp-admin-bar-bwfan_admin_bar_search_contact-default li .bwf-quick-links ul li a {
                text-decoration: none;
                color: #1daafc;
            }
        </style>

		<?php
		$admin_url = admin_url( 'admin.php?page=autonami' );
		$wp_admin_bar->add_node( [
			'id'     => 'bwfan_admin_bar_search_contact',
			'title'  => __( 'Search Contact', 'wp-marketing-automations-pro' ),
			'parent' => 'top-secondary',
			'href'   => $admin_url . '&path=/contacts',
			'meta'   => [
				'target' => '_blank',
			],
		] );

		$html = '<div class="bwf-search-contacts">
                    <div class="bwf-search-input">
                        <span class="label">Enter name or email to search contact</span>
                        <input type="text" autocomplete="off" placeholder="Search Contacts" id="bwfan-admin-bar-search"/>
                        <div id="bwf-contact-list-suggestion-list">
                        </div>
                    </div>
                     <div class="bwf-quick-links">
                        <p class="label">Quick Links</p>
                        <ul>
                            <li><a href="' . esc_url( $admin_url . '&path=/automations' ) . '" target="_blank">Automations</a></li>
                            <li><a href="' . esc_url( $admin_url . '&path=/contacts' ) . '" target="_blank">Contacts</a></li>
                            <li><a href="' . esc_url( $admin_url . '&path=/analytics' ) . '" target="_blank">Analytics</a></li>
                            <li><a href="' . esc_url( $admin_url . '&path=/carts' ) . '" target="_blank">Cart</a></li>
                            <li><a href="' . esc_url( $admin_url . '&path=/settings' ) . '" target="_blank">Settings</a></li>
                        </ul>
                    </div>
                </div>';

		$wp_admin_bar->add_node( [
			'id'     => 'bwfan_admin_bar_li1',
			'title'  => $html,
			'parent' => 'bwfan_admin_bar_search_contact',
			'href'   => "#",
		] );

		wp_enqueue_script( 'bwfan-admin-bar', $this->admin_url . '/assets/js/bwfan-pro-admin-bar.js', array( 'wp-api' ), BWFAN_VERSION_DEV, true );
		wp_localize_script( 'bwfan-admin-bar', 'bwfanProObj', array(
			'siteUrl'      => site_url(),
			'apiNamespace' => BWFAN_API_NAMESPACE,
		) );
	}
}

BWFAN_Pro_Admin::get_instance();

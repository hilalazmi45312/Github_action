<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * @package
 * @version 1.0.0
 */


defined( 'ABSPATH' ) || exit; //Exit if accessed directly
if ( ! class_exists( 'WFFN_Pro_Core' ) ) {

	/**
	 * Class WFFN_Pro_Core
	 */
	#[AllowDynamicProperties]
	class WFFN_Pro_Core {

		/**
		 * @var null
		 */
		public static $_instance = null;


		/**
		 * @var WFFN_Pro_Steps
		 */
		public $steps;

		/**
		 * @var WFFN_Pro_Substeps
		 */
		public $substeps;

		/**
		 * @var WFFN_Pro_WooFunnels_Support
		 */
		public $support;

		/**
		 * @var array
		 */
		private static $_registered_entity = array(
			'active'   => array(),
			'inactive' => array(),
		);
		/**
		 * @var bool Dependency check property
		 */
		public $is_dependency_exists = true;

		/**
		 * WFFN_PRO_Core constructor.
		 */
		public function __construct() {
			/**
			 * Load important variables and constants
			 */
			$this->define_plugin_properties();


		}

		/**
		 * Defining constants
		 */
		public function define_plugin_properties() {

			define('WFFN_PRO_VERSION', '3.13.2');
			define( 'WFFN_MIN_VERSION', '1.0.0' );
			define( 'WFFN_PRO_SLUG', 'wffn_pro' );
			define( 'WFFN_PRO_FULL_NAME', __( 'FunnelKit Funnel Builder Basic', 'funnel-builder-pro' ) );
			define( 'WFFN_PRO_PLUGIN_FILE', __FILE__ );
			define( 'WFFN_PRO_PLUGIN_DIR', __DIR__ );
			define( 'WFFN_PRO_PLUGIN_URL', untrailingslashit( plugin_dir_url( WFFN_PRO_PLUGIN_FILE ) ) );
			define( 'WFFN_PRO_PLUGIN_BASENAME', defined( 'WFFN_BASIC_FILE' ) ? plugin_basename( WFFN_BASIC_FILE ) : plugin_basename( __FILE__ ) );

			add_action( 'plugins_loaded', [ $this, 'do_dependency_check' ], - 999 );
		}


		public function do_dependency_check() {
			if ( ! class_exists( 'WFFN_Core' ) ) {
				add_action( 'admin_notices', array( $this, 'wffn_lite_not_installed_notice' ) );
				$this->is_dependency_exists = false;
			}

			if ( $this->is_dependency_exists && version_compare( WFFN_VERSION, WFFN_MIN_VERSION, '<' ) ) {
				add_action( 'admin_notices', array( $this, 'wffn_lite_min_version_notice' ) );
				$this->is_dependency_exists = false;
			}
			/**
			 * Initiates and loads WooFunnels start file
			 */
			if ( true === $this->is_dependency_exists ) {
				/**
				 * Loads hooks
				 */
				$this->load_hooks();
			}

		}

		/**
		 * Load classes on plugins_loaded hook
		 */
		public function load_hooks() {
			/**
			 * Initialize Localization
			 */
			require __DIR__ . '/includes/class-wffn-pro-modules.php';
			add_action( 'init', array( $this, 'localization' ) );
			add_action( 'plugins_loaded', array( $this, 'load_classes' ), 2 );
			add_action( 'plugins_loaded', array( $this, 'register_classes' ), 3 );


			add_filter( 'wffn_conversion_tracking_persistant', '__return_true' );

			add_action( 'activated_plugin', array( $this, 'redirect_on_activation' ) );
			if ( is_admin() ) {
				add_filter( 'wffn_localized_text_admin', array( $this, 'add_license_related_code' ) );

			}
		}

		public function localization() {
			load_plugin_textdomain( 'funnel-builder-pro', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Load classes
		 */
		public function load_classes() {

			$this->load_includes();

			$this->load_commons();

			$this->load_steps();
		}


		/**
		 * Load includes folder
		 */
		public function load_includes() {
			require __DIR__ . '/includes/class-wffn-pro-woofunnels-support.php';
			require __DIR__ . '/includes/page-builders/elementor/class-wffn-pro-optin-pages-elementor.php';
			require __DIR__ . '/includes/page-builders/divi/class-wffn-pro-optin-pages-divi.php';
			require __DIR__ . '/includes/page-builders/oxygen/class-wffn-pro-optin-pages-oxygen.php';
			require __DIR__ . '/includes/page-builders/gutenberg/class-wfop-gutenberg-extension.php';

			add_action( 'wp_footer', array( $this, 'add_optin_form_footer' ) );
			add_action( 'wfopp_output_form_before', array( $this, 'maybe_optin_form_before' ), 10, 4 );
			add_action( 'wfopp_output_form_after', array( $this, 'maybe_optin_form_after' ), 10, 4 );
			add_filter( 'wfop_internal_css', array( $this, 'add_internal_css' ), 10, 2 );
			add_filter( 'wfopp_customization_default_fields', array( $this, 'add_customization_fields_defaults' ) );


		}

		public function add_optin_form_footer() {

			$optinPageId    = WFOPP_Core()->optin_pages->get_optin_id();
			$get_controller = WFOPP_Core()->form_controllers->get_integration_object( 'form' );
			if ( $optinPageId > 0 && WFOPP_Core()->optin_pages->is_wfop_page() ) {
				$get_embed_mode  = 'popover';
				$optinFields     = WFOPP_Core()->optin_pages->form_builder->get_form_fields( $optinPageId );
				$selected_design = WFOPP_Core()->optin_pages->get_page_design( $optinPageId );
				$selected_type   = isset( $selected_design['selected_type'] ) ? $selected_design['selected_type'] : '';
				if ( 'wp_editor' !== $selected_type ) {
					return;
				}

				if ( count( $optinFields ) > 0 ) {
					$customizations = WFOPP_Core()->optin_pages->form_builder->get_form_customization_option( 'all', $optinPageId );
					$font_array     = [];
					if ( 'default' !== $customizations['input_font_family'] && 'inherit' !== $customizations['input_font_family'] ) {
						$font_array[] = $customizations['input_font_family'];
					}
					if ( 'default' !== $customizations['button_font_family'] && 'inherit' !== $customizations['button_font_family'] ) {
						$font_array[] = $customizations['button_font_family'];
					}
					if ( ! empty( $font_array ) ) {
						$font_array      = array_unique( $font_array );
						$font_string     = implode( '|', $font_array );
						$google_font_url = "//fonts.googleapis.com/css?family=" . $font_string;
						wp_enqueue_style( 'wfop-google-fonts', esc_url( $google_font_url ), array(), WFFN_VERSION, 'all' );
					}

					$class = '';

					if ( $customizations['show_input_label'] === 'no' ) {
						$class = "wfop_hide_label";
					}

					$modal_effect = isset( $customizations['popup_open_animation'] ) ? $customizations['popup_open_animation'] : 'slide-down';
					/**
					 * Render popover front end popup HTML Here
					 */ ?>
                    <div class="bwf_pp_overlay bwf_pp_effect_<?php echo esc_attr( $modal_effect ) ?>">
                        <div class="bwf_pp_wrap">
                            <a class="bwf_pp_close" href="javascript:void(0)">&times;</a>
                            <div class="bwf_pp_cont">
								<?php $get_controller->frontend_render_form( $optinPageId, $get_embed_mode, $class ); ?>
                            </div>
                        </div>
                    </div>
					<?php
				}

			} else {
				return;
			}
		}

		public function maybe_optin_form_before( $optinPageId, $optin_settings, $form_mode, $customizations ) {
			if ( 'popover' === $form_mode ) {

				if ( 'enable' === $customizations['popup_bar_pp'] ) {
					$animate_class = ( isset( $customizations['popup_bar_animation'] ) && 'yes' === $customizations['popup_bar_animation'] ) ? ' bwf_pp_animate' : '';

					?>
                    <div class="pp-bar-text-wrapper <?php echo esc_attr( $customizations['popup_bar_text_wrap_classes'] ); ?>">
                        <span class="pp-bar-text above"><?php echo esc_html( $customizations['popup_bar_text'] ); ?></span>
                    </div>
                    <div class="bwf_pp_bar_wrap <?php echo esc_attr( $customizations['popup_bar_wrap_classes'] ); ?>">
                        <div class="bwf_pp_bar<?php echo esc_attr( $animate_class ); ?>" role="progressbar"
                             aria-valuenow="<?php echo esc_attr( $customizations['popup_bar_width'] ); ?>" aria-valuemin="0"
                             aria-valuemax="100">
                            <span class="pp-bar-text inside"><?php echo esc_html( $customizations['popup_bar_text'] ); ?></span>
                        </div>
                    </div>
				<?php }
				?>
                <div class="bwf_pp_opt_head"><?php echo wp_kses_post( $customizations['popup_heading'] ); ?></div>
                <div class="bwf_pp_opt_sub_head"><?php echo wp_kses_post( $customizations['popup_sub_heading'] ); ?></div>
				<?php
			}
		}

		public function maybe_optin_form_after( $optinPageId, $optin_settings, $form_mode, $customizations ) {
			if ( 'popover' === $form_mode ) { ?>
                <div class="bwf_pp_footer"><?php echo wp_kses_post( $customizations['popup_footer_text'] ); ?></div>
				<?php
			}
		}


		public function add_internal_css( $css, $customizations ) {
			$heading_color     = isset( $customizations['popup_heading_color'] ) ? "color:" . $customizations['popup_heading_color'] . ";" : "";
			$heading_font_size = isset( $customizations['popup_heading_font_size'] ) ? "font-size:" . $customizations['popup_heading_font_size'] . "px;line-height:" . ( $customizations['popup_heading_font_size'] + 8 ) . "px;" : "";

			$heading_font_family = isset( $customizations['popup_heading_font_family'] ) ? "font-family:" . $customizations['popup_heading_font_family'] . ";" : "";

			$heading_font_weight = isset( $customizations['popup_heading_font_weight'] ) ? "font-weight:" . $customizations['popup_heading_font_weight'] . ";" : "normal";

			$css['.bwf_pp_opt_head'] = $heading_color . $heading_font_size . $heading_font_family . $heading_font_weight;

			$sub_heading_color = isset( $customizations['popup_sub_heading_color'] ) ? "color:" . $customizations['popup_sub_heading_color'] . ";" : "";

			$sub_heading_font_size = isset( $customizations['popup_sub_heading_font_size'] ) ? "font-size:" . $customizations['popup_sub_heading_font_size'] . "px;line-height:" . ( $customizations['popup_sub_heading_font_size'] + 8 ) . "px;" : "";

			$sub_heading_font_family = isset( $customizations['popup_sub_heading_font_family'] ) ? "font-family:" . $customizations['popup_sub_heading_font_family'] . ";" : "";

			$sub_heading_font_weight = isset( $customizations['popup_sub_heading_font_weight'] ) ? "font-weight:" . $customizations['popup_sub_heading_font_weight'] . ";" : "normal";

			$css['body .bwf_pp_opt_sub_head'] = 'margin-bottom:20px;' . $sub_heading_color . $sub_heading_font_size . $sub_heading_font_family . $sub_heading_font_weight;

			$heading_color = ( isset( $customizations['popup_bar_pp'] ) && $customizations['popup_bar_pp'] === 'disable' ) ? "color:" . $customizations['popup_heading_color'] . ";" : "";

			$popup_bar             = ( $customizations['popup_bar_pp'] === 'disable' ) ? 'display: none;' : 'display: flex;';
			$transition            = ( ! is_admin() ) ? 'transition: all 5s ease-in-out;' : '';
			$popup_bar_width       = "width:" . $customizations['popup_bar_width'] . '%;';
			$popup_bar_height      = "height:" . $customizations['popup_bar_height'] . 'px;';
			$popup_bar_font_size   = "font-size:" . $customizations['popup_bar_font_size'] . "px;line-height:" . ( $customizations['popup_bar_font_size'] + 8 ) . "px;";
			$popup_bar_font_family = "font-family:" . $customizations['popup_bar_font_family'] . ";";
			$popup_bar_inner_gap   = "padding:" . $customizations['popup_bar_inner_gap'] . "px;";
			$popup_bar_text_color  = "color:" . $customizations['popup_bar_text_color'] . ";";
			$popup_bar_color       = "background-color:" . $customizations['popup_bar_color'] . ";";
			$popup_bar_bg_color    = "background-color:" . $customizations['popup_bar_bg_color'] . ";";

			$css['.bwf_pp_bar_wrap'] = $popup_bar . $popup_bar_bg_color . $popup_bar_height . $popup_bar_inner_gap;

			$css['.bwf_pp_bar_wrap .bwf_pp_bar'] = $popup_bar_font_size . $popup_bar_font_family . $popup_bar_text_color . $popup_bar_width . $popup_bar_color;

			$popup_footer_font_size     = "font-size:" . $customizations['popup_footer_font_size'] . "px;line-height:" . ( $customizations['popup_footer_font_size'] + 8 ) . "px;";
			$popup_footer_font_family   = "font-family:" . $customizations['popup_footer_font_family'] . ";";
			$popup_footer_text_color    = "color:" . $customizations['popup_footer_text_color'] . ";";
			$css['body .bwf_pp_footer'] = $popup_footer_font_size . $popup_footer_font_family . $popup_footer_text_color;

			$popup_width                    = isset( $customizations['popup_width'] ) ? "max-width:" . $customizations['popup_width'] . "px;" : "";
			$popup_padding                  = isset( $customizations['popup_padding'] ) ? "padding:" . $customizations['popup_padding'] . "px;" : "";
			$css['.wfop_form_preview_wrap'] = $popup_width . $popup_padding;

			$css['.wfop_form_preview_wrap'] = $popup_width . $popup_padding;
			$css['body .bwf_pp_wrap']       = $popup_width;
			$css['.bwf_pp_cont']            = $popup_padding;

			return $css;
		}

		public function add_customization_fields_defaults( $defaults ) {

			$popup = array(
				'popup_heading'             => __( 'You\'re just one step away!', 'funnel-builder-powerpack' ),
				'popup_heading_color'       => '#000000',
				'popup_heading_font_size'   => '17',
				'popup_heading_font_family' => 'inherit',
				'popup_heading_font_weight' => '400',

				'popup_sub_heading'             => __( 'Enter your details below and we\'ll get you signed up', 'funnel-builder-powerpack' ),
				'popup_sub_heading_color'       => '#000000',
				'popup_sub_heading_font_size'   => '24',
				'popup_sub_heading_font_family' => 'inherit',
				'popup_sub_heading_font_weight' => '700',

				'popup_bar_pp'                => 'enable',
				'popup_bar_animation'         => 'yes',
				'popup_bar_text'              => __( '75% Complete', 'funnel-builder-powerpack' ),
				'popup_bar_width'             => '75',
				'popup_bar_height'            => '30',
				'popup_bar_font_family'       => 'inherit',
				'popup_bar_font_size'         => '16',
				'popup_bar_inner_gap'         => '4',
				'popup_bar_text_color'        => '#ffffff',
				'popup_bar_color'             => '#338d48',
				'popup_bar_bg_color'          => '#efefef',
				'popup_bar_text_wrap_classes' => '',
				'popup_bar_wrap_classes'      => '',

				'popup_footer_text'        => __( 'Your Information is 100% Secure', 'funnel-builder-powerpack' ),
				'popup_footer_font_family' => 'inherit',
				'popup_footer_font_size'   => '16',
				'popup_footer_text_color'  => '#444444',

				'popup_width'          => '600',
				'popup_padding'        => '40',
				'popup_open_animation' => 'slide-down'
			);

			return array_merge( $defaults, $popup );

		}
		/**
		 * Include steps and sub steps
		 */
		public function load_steps() {
			require __DIR__ . '/includes/class-wffn-pro-step-base.php';
			require __DIR__ . '/includes/class-wffn-pro-step.php';
			require __DIR__ . '/includes/class-wffn-pro-steps.php';
		}

		/**
		 * Includes common functions.
		 */
		public function load_commons() {

		}

		/**
		 * @return WFFN_PRO_Core|null
		 */
		public static function get_instance() {
			if ( null === self::$_instance ) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		/**
		 * Register classes
		 */
		public function register_classes() {

			$load_classes = self::get_registered_class();
			if ( is_array( $load_classes ) && count( $load_classes ) > 0 ) {
				foreach ( $load_classes as $access_key => $class ) {

					$this->$access_key = $class::get_instance();
				}

				do_action( 'wffn_pro_loaded' );

			}
		}

		/**
		 * @return mixed
		 */
		public static function get_registered_class() {
			return self::$_registered_entity['active'];
		}

		public static function register( $short_name, $class, $overrides = null ) {

			self::$_registered_entity['active'][ $short_name ] = $class;

		}

		public function wffn_lite_not_installed_notice() {
			?>
            <div class="error">
                <p>
					<?php
					echo __( '<strong> Attention: </strong>"FunnelKit Funnel Builder" is not installed or activated. "FunnelKit Funnel Builder Basic" would only work if Funnel Builder is activated. Please install the Funnel Builder Plugin first.', 'funnel-builder-pro' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
                </p>
            </div>
			<?php
		}

		public function wffn_lite_min_version_notice() { ?>
            <div class="error">
                <p>
					<?php
					echo sprintf( __( '<strong> Attention: </strong>"FunnelKit Funnel Builder Basic" is not working because activated "FunnelKit Funnel Builder" version should be greater or equal to %s', 'funnel-builder-pro' ), WFFN_MIN_VERSION ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
                </p>
            </div>
			<?php
		}

		/**
		 * Added redirection on plugin activation
		 *
		 * @param $plugin
		 */
		public function redirect_on_activation( $plugin ) {
			if ( $plugin === plugin_basename( __FILE__ ) ) {

				wp_redirect( add_query_arg( array(
					'page'      => 'bwf_funnels',
					'activated' => 'yes',
				), admin_url( 'admin.php' ) ) );
				exit;
			}
		}


		public function add_license_related_code( $texts ) {
			$texts['license'] = array(
				'states' => array(
					1 => array(
						'notice' => array(
							'text'           => __( 'Your FunnelKit Basic license is not activated!', 'funnel-builder-pro' ),
							'primary_action' => __( 'Activate License', 'funnel-builder-pro' )
						),

					),
					2 => array(
						'notice' => array(
							'text'           => __( '<strong>FunnelKit Basic is Not Fully Activated!</strong> Please activate your license to continue using premium features without interruption.', 'funnel-builder-pro' ),
							'primary_action' => __( 'Activate License', 'funnel-builder-pro' )
						),
						'modal'  => array(
							'heading'         => __( 'FunnelKit Basic is not fully Activated', 'funnel-builder-pro' ),
							'sub_heading'     => __( 'Without an active license your checkout is not affected. However, you are missing on', 'funnel-builder-pro' ),
							'features'        => array(
								__( 'New revenue boosting features', 'funnel-builder-pro' ),
								__( 'Critical security updates', 'funnel-builder-pro' ),
								__( 'Revenue from upsells, order bumps and other premium features', 'funnel-builder-pro' ),
								__( 'Access to dedicated support', 'funnel-builder-pro' ),
							),
							'text_before_cta' => __( 'Don\'t miss out on the additional revenue. This problem is easy to fix.', 'funnel-builder-pro' ),
							'primary_action'  => __( 'Activate License', 'funnel-builder-pro' ),
						)

					),
					3 => array(
						'notice' => array(
							'text'             => __( '<strong>Your FunnelKit Basic license has expired!</strong> We\'ve extended its features until {{TIME_GRACE_EXPIRED}}, after which they\'ll be limited.', 'funnel-builder-pro' ),
							'primary_action'   => __( 'Renew Now ', 'funnel-builder-pro' ),
							'secondary_action' => __( 'I have My License Key', 'funnel-builder-pro' )
						),

					),
					4 => array(
						'notice' => array(
							'text'             => __( '<strong>Your FunnelKit Basic license has expired!</strong> Please renew your license to continue using premium features without interruption.', 'funnel-builder-pro' ),
							'primary_action'   => __( 'Renew Now ', 'funnel-builder-pro' ),
							'secondary_action' => __( 'I have My License Key', 'funnel-builder-pro' )
						),
						'modal'  => array(
							'heading'          => __( 'Your License has Expired', 'funnel-builder-pro' ),
							'sub_heading'      => __( 'Without an active license your checkout is not affected. However, you are missing on', 'funnel-builder-pro' ),
							'features'         => array(
								__( 'New revenue boosting features', 'funnel-builder-pro' ),
								__( 'Critical security updates', 'funnel-builder-pro' ),
								__( 'Revenue from upsells, order bumps and other premium features', 'funnel-builder-pro' ),
								__( 'Access to dedicated support', 'funnel-builder-pro' ),
							),
							'text_before_cta'  => __( 'Don\'t miss out on the additional revenue. This problem is easy to fix.', 'funnel-builder-pro' ),
							'primary_action'   => __( 'Renew Now ', 'funnel-builder-pro' ),
							'secondary_action' => __( 'I have My License Key', 'funnel-builder-pro' )
						)

					)
				)
			);

			$expiry = WFFN_Core()->admin->get_license_expiry();
			if ( ( ! empty( $expiry ) && ( strtotime( $expiry ) < current_time( 'timestamp', true ) ) ) || false === WFFN_Core()->admin->is_license_active() ) {
				global $wpdb;
				$checkout_total = $wpdb->get_results( $wpdb->prepare( 'SELECT SUM(`value`) as `total`,COUNT(`id`) as `orders` from ' . $wpdb->prefix . 'bwf_conversion_tracking WHERE `funnel_id`!=%d', 0 ) );

				if ( ! is_null( $checkout_total ) ) {
					$texts['totals'] = $checkout_total[0];
				}

			}


			return $texts;
		}
	}
}
if ( ! function_exists( 'WFFN_Pro_Core' ) ) {
	/**
	 * @return WFFN_PRO_Core|null
	 */
	function WFFN_Pro_Core() {  //@codingStandardsIgnoreLine
		return WFFN_Pro_Core::get_instance();
	}
}

$GLOBALS['WFFN_Pro_Core'] = WFFN_Pro_Core();

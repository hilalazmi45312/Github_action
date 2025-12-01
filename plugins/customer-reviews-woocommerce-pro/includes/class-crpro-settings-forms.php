<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CRPRO_Forms_Settings' ) ) :

	class CRPRO_Forms_Settings {

		public function __construct() {
			add_filter( 'cr_settings_forms', array( $this, 'extra_forms_settings' ) );
			add_filter( 'cr_settings_forms_sections', array( $this, 'display_section' ), 10, 2 );
			add_action( 'woocommerce_admin_field_crpro_advanced_form_template', array( $this, 'show_advanced_form_template' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
			add_action( 'admin_footer', array( $this, 'output_inline_scripts' ) );
		}

		public function extra_forms_settings( $settings ) {
			if( 'no' === get_option( 'ivole_verified_reviews', 'no' )  ) {
				// Advanced form template editor
				$settings[47] = array(
					'title' => __( 'Advanced Form Template', 'customer-reviews-woocommerce-pro' ),
					'type' => 'crpro_advanced_form_template',
					'desc' => __( 'Customize review forms with an advanced template editor.', 'customer-reviews-woocommerce-pro' ),
					'desc_tip' => true
				);
				$form_template = get_option( 'ivole_form_template', array() );
				if( isset( $form_template[ 'enabled' ] ) && $form_template[ 'enabled' ] ) {
					// hide basic settings for form templates
					unset( $settings[50] );
					unset( $settings[55] );
					unset( $settings[60] );
					unset( $settings[65] );
					unset( $settings[70] );
					unset( $settings[90] );
					unset( $settings[95] );
					unset( $settings[100] );
				}
			}

			return $settings;
		}

		public function show_advanced_form_template( $field ) {
			$form_template = get_option( 'ivole_form_template', array() );
			$setting_status = esc_html( __( 'Disabled', 'customer-reviews-woocommerce-pro' ) );
			$setting_class = ' crpro-settings-status-disabled';
			if( isset( $form_template[ 'enabled' ] ) && $form_template[ 'enabled' ] ) {
				$setting_status = esc_html( __( 'Enabled', 'customer-reviews-woocommerce-pro' ) );
				$setting_class = ' crpro-settings-status-enabled';
			}
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $field['desc'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $field['type'] ) ?>">
					<div>
						<span class="cr-settings-manage-span<?php esc_attr_e( $setting_class ); ?>"><?php echo $setting_status; ?></span>
						<a class="button cr-settings-manage-button" href="<?php echo esc_url( admin_url( 'admin.php?page=cr-reviews-settings&tab=forms&section=form_editor' ) ); ?>"><?php echo esc_html( __( 'Customize', 'customer-reviews-woocommerce-pro' ) ); ?></a>
					</div>
				</td>
			</tr>
			<?php
		}

		public function display_section( $section, $section_slug ) {
			if( 'form_editor' === $section_slug ) {

				$GLOBALS['hide_save_button'] = true;

				ob_start();

				echo '<h2>' . esc_html( __( 'Advanced Review Form Editor', 'customer-reviews-woocommerce-pro' ) );
				wc_back_link( __( 'Return to settings', 'customer-reviews-woocommerce-pro' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=forms' ) );
				echo '</h2>';
				echo '<p>' . esc_html( __( 'Advanced editor for aggregated review forms hosted locally on your server. It should be used when reviews are collected without third-party verification.', 'customer-reviews-woocommerce-pro' ) ) . '</p>';
				echo '<div class="crpro-form-editor-pace"></div>';
				echo '<input type="hidden" id="crpro_form_editor_nonce" data-nonce="' . wp_create_nonce( 'crpro-form-editor' ) . '" />';
				echo '<div id="crpro_form_editor"></div>';

				$section = ob_get_clean();
			}

			return $section;
		}

		public function enqueue_scripts_styles( $hook_suffix ) {
			// review form editor scripts and styles
			if( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], 'page=cr-reviews-settings&tab=forms&section=form_editor' ) ) {
				wp_enqueue_style( 'crpro-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', array() );
				wp_enqueue_style( 'crpro-font-google', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600&display=swap', array() );
				wp_enqueue_style( 'crpro-form-editor-vendors', plugins_url( '/dist/css/vendors.css', dirname( __FILE__ ) ), array(), CRPRO::CRPRO_VERSION  );
				wp_enqueue_style( 'crpro-form-editor-app', plugins_url( '/dist/css/app.css', dirname( __FILE__ ) ), array( 'crpro-form-editor-vendors', 'crpro-font-awesome' ), CRPRO::CRPRO_VERSION  );
				wp_enqueue_script( 'crpro-pace', plugins_url( '/dist/js/pace/pace.min.js' , dirname( __FILE__ ) ), array(), CRPRO::CRPRO_VERSION, true );
				wp_enqueue_script( 'crpro-form-editor-vendors', plugins_url( '/dist/js/vendors.js' , dirname( __FILE__ ) ), array(), CRPRO::CRPRO_VERSION, true );
				wp_enqueue_script( 'crpro-form-editor-bundle', plugins_url( '/dist/js/bundle.js' , dirname( __FILE__ ) ), array( 'crpro-form-editor-vendors' ), CRPRO::CRPRO_VERSION, true );
			}
		}

		public function output_inline_scripts() {
			if(
				isset( $_SERVER['REQUEST_URI'] ) &&
				false !== strpos( $_SERVER['REQUEST_URI'], 'page=cr-reviews-settings&tab=forms&section=form_editor' )
			) {
				?>
					<script type="text/javascript" >
						window.paceOptions = {
							restartOnPushState: false,
							ajax: false,
							elements: {
								selectors: ['body.ready']
							},
							target: '.crpro-form-editor-pace',
						}
					</script>
				<?php
			}
		}

	}

endif;

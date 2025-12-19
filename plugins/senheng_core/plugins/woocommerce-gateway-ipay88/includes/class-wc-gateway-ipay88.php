<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}

class WC_Gateway_iPay88 extends WC_Payment_Gateway {
	
	public $posted_payment_type;
	public $posted_payment_plan;
	public $posted_admin_fee;
	public $check_pass;
	public $paymenttype_options;
	public $types_mapping;
	public $paymenttype_options_ph;
	public $types_mapping_ph;
	public $image_ext;
	public $hash_amount;
	public $formatted_amount;
	public $MerchantCode;
	public $MerchantKey;
	public $use_css;
	public $paymenttype_available;
	public $paymenttype_available_ph;
	public $gateway;
	public $sandbox;
	public $url;
	
	public function __construct() {

		add_filter('woocommerce_gateway_icon', '__return_empty_string');
		
		$this->id                 = 'ipay88';
		$this->icon               = apply_filters( 'woocommerce_ipay88_icon', WC_iPay88::plugin_url() . '/assets/images/ipay88.png' );
		$this->has_fields         = false;
		$this->method_title       = __( 'iPay88', 'wc_ipay88' );
		$this->method_description = __( 'iPay88 is a payment gateway works by redirecting the customer to iPay88 server to make a payment and then returns the customer back to your "Thank you/Receipt" page. The plugin supports iPay88 Malaysia and Philippines gateways', 'wc_ipay88' );

		$this->paymenttype_options = array(
			// --- Credit Card ---
			'2'   => __( 'Credit / Debit Card', 'wc_ipay88' ),

			// --- Internet Banking ---
			'6'   => __( 'Maybank2U', 'wc_ipay88' ),
			'8'   => __( 'Alliance Online', 'wc_ipay88' ),
			'10'  => __( 'AmBank', 'wc_ipay88' ),
			'14'  => __( 'RHB Bank', 'wc_ipay88' ),
			'15'  => __( 'Hong Leong Bank', 'wc_ipay88' ),
			'20'  => __( 'CIMB Clicks', 'wc_ipay88' ),
			'31'  => __( 'Public Bank Online', 'wc_ipay88' ),
			'102' => __( 'Bank Rakyat', 'wc_ipay88' ),
			'103' => __( 'Affin Bank', 'wc_ipay88' ),
			'124' => __( 'BSN', 'wc_ipay88' ),
			'134' => __( 'Bank Islam', 'wc_ipay88' ),
			'152' => __( 'UOB Bank', 'wc_ipay88' ),
			'166' => __( 'Bank Muamalat', 'wc_ipay88' ),
			'167' => __( 'OCBC Bank', 'wc_ipay88' ),
			'168' => __( 'Standard Chartered Bank', 'wc_ipay88' ),
			'198' => __( 'HSBC Bank', 'wc_ipay88' ),

			// --- E-Wallet ---
			'210' => __( 'Boost Wallet', 'wc_ipay88' ),
			'523' => __( 'GrabPay', 'wc_ipay88' ),
			'538' => __( 'Touch n Go eWallet', 'wc_ipay88' ),
			'542' => __( 'Maybank PayQR', 'wc_ipay88' ),
			'801' => __( 'ShopeePay', 'wc_ipay88' ),

			// --- BNPL ---
			'111' => __( 'Public Bank EPP', 'wc_ipay88' ),
			'112' => __( 'Maybank EzyPay (Visa/Mastercard)', 'wc_ipay88' ),
			'115' => __( 'Maybank EzyPay (AMEX)', 'wc_ipay88' ),
			'157' => __( 'HSBC Instalment', 'wc_ipay88' ),
			'174' => __( 'CIMB Easy Pay', 'wc_ipay88' ),
			'179' => __( 'Hong Leong Bank EPP', 'wc_ipay88' ),
			'534' => __( 'RHB Instalment', 'wc_ipay88' ),
			'606' => __( 'AmBank EPP', 'wc_ipay88' ),
			'727' => __( 'Standard Chartered Instalment', 'wc_ipay88' ),
			'891' => __( 'Atome', 'wc_ipay88' ),
		);

		$this->types_mapping = array(
			'image' => array(
				// Credit Card
				'2'   => 'payment_card',

				// Internet Banking
				'6'   => 'maybank2u',
				'8'   => 'allianceonline',
				'10'  => 'ambank',
				'14'  => 'rhb',
				'15'  => 'hong_leong_connect',
				'20'  => 'cimb',
				'31'  => 'publicbank',
				'102' => 'bankrakyat',
				'103' => 'affinbank',
				'124' => 'bsn',
				'134' => 'bankislam',
				'152' => 'uobbankmy',
				'166' => 'bank_muamalat',
				'167' => 'ocbc',
				'168' => 'standard_chartered',
				'198' => 'hsbc',

				// E-Wallet
				'210' => 'boost_wallet', #teda gambar
				'523' => 'grabpay', #teda gambar
				'538' => 'tng', #teda gambar
				'542' => 'maybank_payqr', #teda gambar
				'801' => 'shopeepay', #teda gambar

				// BNPL
				'111' => 'publicbank',
				'112' => 'maybank_ezypay_visa_mc',
				'115' => 'maybank_ezypay_amex',
				'157' => 'hsbc_instalment',
				'174' => 'cimb_easy_pay',
				'179' => 'hongleong_epp',
				'534' => 'rhb_instalment',
				'606' => 'ambank_epp',
				'727' => 'standard_chartered_instalment',
				'891' => 'atome',
			),

			'id' => array(
				// Credit Card
				'2'   => '_credit_card',

				// Internet Banking
				'6'   => '_maybank2u',
				'8'   => '_alliance_online',
				'10'  => '_ambank',
				'14'  => '_rhb',
				'15'  => '_hongleong',
				'20'  => '_cimb_clicks',
				'31'  => '_publicbank',
				'102' => '_bankrakyat',
				'103' => '_affinbank',
				'124' => '_bsn',
				'134' => '_bankislam',
				'152' => '_uob',
				'166' => '_bankmuamalat',
				'167' => '_ocbc',
				'168' => '_standard_chartered',
				'198' => '_hsbc',

				// E-Wallet
				'210' => '_boost_wallet',
				'523' => '_grabpay',
				'538' => '_tng',
				'542' => '_maybank_payqr',
				'801' => '_shopeepay',

				// BNPL
				'111' => '_publicbank_epp',
				'112' => '_maybank_ezypay_vm',
				'115' => '_maybank_ezypay_amex',
				'157' => '_hsbc_instalment',
				'174' => '_cimb_easy_pay',
				'179' => '_hongleong_epp',
				'534' => '_rhb_instalment',
				'606' => '_ambank_epp',
				'727' => '_standard_chartered_instalment',
				'891' => '_atome',
			),

			'name' => array(
				// Credit Card
				'2'   => 'Credit/DebitCard',

				// Internet Banking
				'6'   => 'Maybank2U',
				'8'   => 'AllianceOnline',
				'10'  => 'Ambank',
				'14'  => 'RHB',
				'15'  => 'HongLeongConnect',
				'20'  => 'CIMB',
				'31'  => 'PublicBank',
				'102' => 'BankRakyat',
				'103' => 'AffinBank',
				'124' => 'BSN',
				'134' => 'BankIslam',
				'152' => 'UOBBank',
				'166' => 'BankMuamalat',
				'167' => 'OCBC',
				'168' => 'StandardChartered',
				'198' => 'HSBC',

				// E-Wallet
				'210' => 'BoostWallet',
				'523' => 'GrabPay',
				'538' => 'TNG',
				'542' => 'MaybankPayQR',
				'801' => 'ShopeePay',

				// BNPL
				'111' => 'PublicBankEPP',
				'112' => 'MaybankEzyPayVisaMastercard',
				'115' => 'MaybankEzyPayAMEX',
				'157' => 'HSBCInstalment',
				'174' => 'CIMBEasyPay',
				'179' => 'HongLeongEPP',
				'534' => 'RHBInstalment',
				'606' => 'AmBankEPP',
				'727' => 'StandardCharteredInstalment',
				'891' => 'Atome',
			),
		);
		
		$this->image_ext        = apply_filters( 'ipay88_image_extension', 'png' );
		$this->hash_amount      = 0;
		$this->formatted_amount = 0;
		
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables
		$this->title                    = $this->settings['title'];
		$this->description              = $this->settings['description'];
		$this->enabled                  = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'no';
		$this->MerchantCode             = $this->settings['MerchantCode'];
		$this->MerchantKey              = $this->settings['MerchantKey'];
		$this->use_css                  = isset( $this->settings['use_css'] ) ? $this->settings['use_css'] : 'no';
		$this->paymenttype_available    = isset( $this->settings['paymenttype_available'] ) ? $this->settings['paymenttype_available'] : array();
		$this->paymenttype_available_ph = isset( $this->settings['paymenttype_available_ph'] ) ? $this->settings['paymenttype_available_ph'] : array();
		$this->gateway                  = isset( $this->settings['gateway'] ) ? $this->settings['gateway'] : 'MY';
		$this->sandbox                  = isset( $this->settings['sandbox'] ) ? $this->settings['sandbox'] : 'yes';
		
		// Actions
		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array(
			$this,
			'check_status_response_ipay88'
		) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		
		// Save options
		add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		
		if ( ! $this->is_valid() ) {
			$this->enabled = 'no';
		}
		
		if ( 'MY' == $this->gateway && 'yes' == $this->use_css && ! empty ( $this->paymenttype_available ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'add_ipay88_checkout_styles' ) );
		}
		
		// Enqueue JavaScript for payment options
		add_action( 'wp_enqueue_scripts', array( $this, 'add_ipay88_checkout_scripts' ) );
	}
	
	/**
	 * Add the checkout page css to for the grouped payment options
	 */
	function add_ipay88_checkout_styles() {
		if ( is_checkout() ) {
			wp_register_style( 'ipay88-checkout-css', WC_iPay88::plugin_url() . '/assets/css/ipay88.css' );
			wp_enqueue_style( 'ipay88-checkout-css' );
		}
	}
	
	/**
	 * Add the checkout page JavaScript for payment options functionality
	 */
	function add_ipay88_checkout_scripts() {
		if ( is_checkout() ) {
			wp_register_script( 'ipay88-checkout-js', WC_iPay88::plugin_url() . '/assets/js/ipay88.js', array( 'jquery' ), '1.0.0', true );
			wp_enqueue_script( 'ipay88-checkout-js' );
			$shBnplData = PaymentMethod::getPaymentMethodPlans();
			wp_localize_script( 'ipay88-checkout-js', 'shBnplData', [
				'paymentPlans' => $shBnplData,
				'total' => WC()->cart->total,
			]);
		}
	}
	
	/**
	 * @since 1.3
	 * @return mixed
	 */
	public function get_allowed_currency() {
		if ( 'PH' == $this->gateway ) {
			$allowed_currency = array( 'PHP' );
		} else {
			$allowed_currency = array(
				'MYR',
				'USD',
				'AUD',
				'CAD',
				'EUR',
				'GBP',
				'SGD',
				'HKD',
				'IDR',
				'INR',
				'PHP',
				'THB',
				'TWD',
				'CNY',
			);
		}
		
		return apply_filters( 'wc_ipay88_allowed_currency', $allowed_currency, $this->gateway );
	}
	
	/**
	 * Check if the currency allows for gateway use
	 *
	 * @return boolean
	 **/
	function is_valid() {
		if ( ! in_array( get_woocommerce_currency(), $this->get_allowed_currency() ) ) {
			return false;
		}
		
		return apply_filters( 'wc_ipay88_is_valid', true );
	}
	
	/**
	 * Admin Panel Options
	 **/
	public function admin_options() {
		
		if ( ! $this->is_valid() ) {
			if ( 'PH' == $this->gateway ) {
				$notice = __( ' Supported currency is Philippine Pesos ( PHP )', 'wc_ipay88' );
			} else {
				$notice = sprintf( __( ' Supported currency is %s', 'wc_ipay88' ), implode( ', ', $this->get_allowed_currency() ) );
			}
			if ( $notice ) {
				?>
				<div class="error woocommerce-error"><p><?php echo esc_html( $notice ); ?></p></div>
				<?php
			}
		}
		parent::admin_options();
		
		ob_start();
		// @formatter:off
		?>
		<script>
		!function(e){e(document).ready(function(){e("#woocommerce_ipay88_gateway").change(function(){var o=e(this).val().toLowerCase();console.log(o),e(".depend_on_gateway").closest("tr").hide(),e(".show_if_gateway_"+o).closest("tr").slideDown()}).change()})}(jQuery);
		</script>
		<?php
		// @formatter:on
	} // End admin_options()
	
	/**
	 * Initialise Gateway Settings Form Fields
	 **/
	function init_form_fields() {
		
		$this->form_fields = array(
			'enabled'                  => array(
				'type'    => 'checkbox',
				'label'   => __( 'Enable iPay88', 'wc_ipay88' ),
				'default' => 'no'
			),
			'gateway'                  => array(
				'title'       => __( 'iPay88 Gateway In Use', 'wc_ipay88' ),
				'type'        => 'select',
				'description' => __( 'Which iPay88 gateway are you using? Currently supported are Malaysia and Philippines.', 'wc_ipay88' ),
				'css'         => 'min-width:350px;',
				'class'       => 'chosen_select',
				'default'     => 'MY',
				'options'     => array(
					'MY' => __( 'Malaysia', 'wc_ipay88' ),
					'PH' => __( 'Philippines', 'wc_ipay88' )
				),
			),
			'title'                    => array(
				'title'       => __( 'Method Title', 'wc_ipay88' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc_ipay88' ),
				'default'     => __( 'iPay88', 'wc_ipay88' )
			),
			'description'              => array(
				'title'       => __( 'Description', 'wc_ipay88' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'wc_ipay88' ),
				'default'     => __( "Make a payment using your Credit/Debit card.", 'wc_ipay88' )
			),
			'MerchantCode'             => array(
				'title'       => __( 'Merchant Code', 'wc_ipay88' ),
				'type'        => 'text',
				'description' => __( "The Merchant Code provided by iPay88 and used to uniquely identify the Merchant.", 'wc_ipay88' ),
				'default'     => ''
			),
			'MerchantKey'              => array(
				'title'       => __( 'Merchant Key', 'wc_ipay88' ),
				'type'        => 'password',
				'description' => __( 'Provided by iPay88 OPSG and shared between iPay88 and merchant only.', 'wc_ipay88' ),
				'default'     => ''
			),
			'paymenttype_available'    => array(
				'title'       => __( 'Available Payment Types', 'wc_ipay88' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the payment types you can offer to the customers. The Payment types will be presented to the customer to pre-select on the Checkout page. Do not choose any type to use the default selection on the iPay88 payment page.', 'wc_ipay88' ),
				'options'     => $this->paymenttype_options,
				'css'         => 'min-width:350px;',
				'default'     => '',
				'class'       => 'chosen_select depend_on_gateway show_if_gateway_my',
			),
			'paymenttype_available_ph' => array(
				'title'       => __( 'Available Payment Types', 'wc_ipay88' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the payment types you can offer to the customers. The Payment types will be presented to the customer to pre-select on the Checkout page. Do not choose any type to use the default selection on the iPay88 payment page.', 'wc_ipay88' ),
				'options'     => $this->paymenttype_options_ph,
				'css'         => 'min-width:350px;',
				'class'       => 'chosen_select depend_on_gateway show_if_gateway_ph',
				'default'     => ''
			),
			'use_css'                  => array(
				'type'        => 'checkbox',
				'label'       => __( 'Group Payment Types', 'wc_ipay88' ),
				'description' => __( 'Check if you want to use the css packed with the plugin. It will group the payment types in three columns.', 'wc_ipay88' ),
				'default'     => 'no',
				'class'       => 'depend_on_gateway show_if_gateway_my',
			),
			'sandbox'                  => array(
				'label'       => __( 'Enable Sandbox', 'wc_ipay88' ),
				'type'        => 'checkbox',
				'description' => __( 'Sandbox mode provides you with a chance to test your gateway integration with iPay88. The payment requests will be send to the iPay88 sandbox URL.<br/>Disable to start accepting Live payments.', 'wc_ipay88' ),
				'default'     => 'yes',
				'class'       => 'depend_on_gateway show_if_gateway_ph',
			),
			'debug'                    => array(
				'type'        => 'checkbox',
				'label'       => __( 'Debug Log. Recommended: Test Mode only', 'wc_ipay88' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Debug log will provide you with most of the data and events generated by the payment process. Logged inside %s.' ), '<code>' . wc_get_log_file_path( 'ipay88' ) . '</code>' ),
			)
		);
	} // End init_form_fields()
	
	/**
	 * Show Description in place of the payment fields
	 **/
	function payment_fields() {
		
		if ( $this->description ) {
			echo wpautop( wptexturize( $this->description ) );
		}
		
		if ( 'PH' == $this->gateway ) {
			$html = $this->get_ph_gateway_payment_types_html();
		} else {
			$html = $this->get_my_gateway_payment_types_html();
		}
		
		$html = apply_filters( 'wc_ipay88_payment_fields_html', $html, $this->gateway );
		
		echo $html;
	}
	
	/**
	 * Will generate the Payment Types html for iPay88 Philippines.
	 */
	function get_ph_gateway_payment_types_html() {
		
		if ( empty( $this->paymenttype_available_ph ) ) {
			return '';
		}
		ob_start();
		?>
		<p class="form-row">
			<label for="ipay88_payment_type"><?php _e( 'Payment Type', 'wc_ipay88' ); ?>
				<span class="required">*</span></label>
		</p>
		<?php
		
		echo '<div class="ipay88_ph_gateway ipay88_opt_container" >';
		foreach ( $this->paymenttype_available_ph as $number ) {
			echo '<p style="margin-bottom:5px;">';
			echo '<input type="radio" id="ipay88' . esc_attr( $this->types_mapping_ph['id'][ $number ] ) . '"';
			echo 'name="ipay88_payment_type" value="' . esc_attr( $number ) . '">';
			echo '<label for="ipay88' . esc_attr( $this->types_mapping_ph['id'][ $number ] ) . '">';
			echo '<img alt="' . esc_attr( $this->paymenttype_options_ph[ $number ] ) . '" src="' . esc_url( WC_HTTPS::force_https_url( WC_iPay88::plugin_url() ) . '/assets/images/' . $this->types_mapping_ph['image'][ $number ] . '.' . $this->image_ext ) . '">';
			echo '</label>';
			echo '</p>';
		}
		echo '</div>';
		
		return apply_filters( 'wc_ipay88_ph_payment_fields_html', ob_get_clean(), $this->paymenttype_available_ph );
	}
	
	/**
	 * Will generate the Payment Types html for iPay88 Malaysia.
	 */
	function get_my_gateway_payment_types_html() {
		
		if ( empty( $this->paymenttype_available ) ) {
			return '';
		}
		ob_start();
		?>
		<div class="ipay88-payment-container">
			<p class="form-row" style="display: none;">
				<label for="ipay88_payment_type"><?php echo esc_html( __( 'Payment Type', 'wc_ipay88' ) ); ?>
					<span class="required">*</span></label>
			</p>
		<?php

		$bnpl_options = apply_filters( 'wc_ipay88_bnpl_payment_types', array(
			'891', // Atome
			'523', // GrabPay
		) );
		
		if ( (bool) array_intersect( $bnpl_options, $this->paymenttype_available ) ) {
			echo '<div class="ipay88-payment-section bnpl-section">';
			echo '<h4 class="ipay88-section-title">';
			echo '<div class="ipay88-section-content">';
			if ( file_exists( WC_iPay88::plugin_path() . '/assets/images/bnpl.' . $this->image_ext ) ) {
				echo '<img class="ipay88-section-icon" alt="BNPL" src="' . esc_url( WC_HTTPS::force_https_url( WC_iPay88::plugin_url() ) . '/assets/images/bnpl.' . $this->image_ext ) . '">';
			}
			echo esc_html( __( 'Buy Now Pay Later', 'wc_ipay88' ) );
			echo '</div>';
			echo '</h4>';
			echo '<div class="ipay88-payment-grid bnpl-grid">';

			foreach ( $bnpl_options as $number ) {
				if ( in_array( $number, $this->paymenttype_available ) ) {
					echo '<div class="ipay88-payment-option-wrapper">';
					echo '<label class="ipay88-payment-option bnpl-option" for="ipay88' . esc_attr( $this->types_mapping['id'][ $number ] ) . '">';
					echo '<input type="radio" id="ipay88' . esc_attr( $this->types_mapping['id'][ $number ] ) . '" name="ipay88_payment_type" value="' . esc_attr( $number ) . '" data-payment-type="bnpl">';

					if ( file_exists( WC_iPay88::plugin_path() . '/assets/images/' . $this->types_mapping['image'][ $number ] . '.' . $this->image_ext ) ) {
						echo '<img class="ipay88-bank-logo" alt="' . esc_attr( $this->paymenttype_options[ $number ] ) . '" src="' . esc_url( WC_HTTPS::force_https_url( WC_iPay88::plugin_url() ) . '/assets/images/' . $this->types_mapping['image'][ $number ] . '.' . $this->image_ext ) . '">';
					}
					
					echo '<div class="ipay88-payment-label">';
					echo esc_html( $this->paymenttype_options[ $number ] );
					echo '</div>';
					echo '</label>';
					
					// Add installment options as radio buttons directly under each payment option
					echo '<input type="hidden" id="ipay88_admin_fee' . esc_attr( $number ) . '" name="ipay88_admin_fee' . esc_attr( $number ) . '" value="">';
					echo '<div class="ipay88-installment-options" id="installment-options-' . esc_attr( $number ) . '" style="display: none;">';
					echo '<div class="ipay88-installment-header">';
					echo '<h5>CHOOSE YOUR INSTALLMENT</h5>';
					echo '</div>';
					echo '<div class="ipay88-installment-list">';
					// Default installment options will be populated by JavaScript
					echo '</div>';
					echo '</div>';
					echo '</div>'; // Close wrapper
				}
			}
			
			echo '</div></div>';
		}
		
		$bank_transfer_options = apply_filters( 'wc_ipay88_bank_transfer_payment_types', array(
			'6',
			'8',
			'10',
			'14',
			'15',
			'16',
			'20',
			'31',
			'102',
			'103',
			'124',
			'134',
			'152',
			'163',
			'166',
			'167',
			'168',
			'198',
			'199',
		) );
		
		if ( (bool) array_intersect( $bank_transfer_options, $this->paymenttype_available ) ) {
			echo '<div class="ipay88-payment-section">';
			echo '<h4 class="ipay88-section-title">';
			echo '<div class="ipay88-section-content">';
			if ( file_exists( WC_iPay88::plugin_path() . '/assets/images/onlinebanking.' . $this->image_ext ) ) {
				echo '<img class="ipay88-section-icon" alt="Online Banking" src="' . esc_url( WC_HTTPS::force_https_url( WC_iPay88::plugin_url() ) . '/assets/images/onlinebanking.' . $this->image_ext ) . '">';
			}
			echo esc_html( __( 'Internet Banking (FPX)', 'wc_ipay88' ) );
			echo '</div>';
			echo '</h4>';
			echo '<div class="ipay88-payment-grid">';
			
			foreach ( $bank_transfer_options as $number ) {
				if ( in_array( $number, $this->paymenttype_available ) ) {
					echo '<label class="ipay88-payment-option" for="ipay88' . esc_attr( $this->types_mapping['id'][ $number ] ) . '">';
					echo '<input type="radio" id="ipay88' . esc_attr( $this->types_mapping['id'][ $number ] ) . '" name="ipay88_payment_type" value="' . esc_attr( $number ) . '">';
					
					if ( file_exists( WC_iPay88::plugin_path() . '/assets/images/' . $this->types_mapping['image'][ $number ] . '.' . $this->image_ext ) ) {
						echo '<img class="ipay88-bank-logo" alt="' . esc_attr( $this->paymenttype_options[ $number ] ) . '" src="' . esc_url( WC_HTTPS::force_https_url( WC_iPay88::plugin_url() ) . '/assets/images/' . $this->types_mapping['image'][ $number ] . '.' . $this->image_ext ) . '">';
					}
					
					echo '<div class="ipay88-payment-label">';
					echo esc_html( $this->paymenttype_options[ $number ] );
					echo '</div>';
					echo '</label>';
				}
			}
			echo '</div></div>';
		}

		// Credit Card Instalment (bank EPPs - excluding Atome and GrabPay)
		$cc_instalment_options = apply_filters( 'wc_ipay88_cc_instalment_payment_types', array(
			'111', // Public Bank EPP
			'112', // Maybank EzyPay (Visa/Mastercard)
			'115', // Maybank EzyPay (AMEX)
			'157', // HSBC Instalment
			'174', // CIMB Easy Pay
			'179', // Hong Leong Bank EPP
			'534', // RHB Instalment
			'606', // AmBank EPP
			'727', // Standard Chartered Instalment
		) );
		
		if ( (bool) array_intersect( $cc_instalment_options, $this->paymenttype_available ) ) {
			echo '<div class="ipay88-payment-section cc-instalment-section">';
			echo '<h4 class="ipay88-section-title">';
			echo '<div class="ipay88-section-content">';
			if ( file_exists( WC_iPay88::plugin_path() . '/assets/images/cc.' . $this->image_ext ) ) {
				echo '<img class="ipay88-section-icon" alt="Credit Card Instalment" src="' . esc_url( WC_HTTPS::force_https_url( WC_iPay88::plugin_url() ) . '/assets/images/cc.' . $this->image_ext ) . '">';
			}
			echo esc_html( __( 'Credit Card Instalment', 'wc_ipay88' ) );
			echo '</div>';
			echo '</h4>';
			echo '<div class="ipay88-payment-grid bnpl-grid">';

			foreach ( $cc_instalment_options as $number ) {
				if ( in_array( $number, $this->paymenttype_available ) ) {
					echo '<div class="ipay88-payment-option-wrapper">';
					echo '<label class="ipay88-payment-option bnpl-option" for="ipay88' . esc_attr( $this->types_mapping['id'][ $number ] ) . '">';
					echo '<input type="radio" id="ipay88' . esc_attr( $this->types_mapping['id'][ $number ] ) . '" name="ipay88_payment_type" value="' . esc_attr( $number ) . '" data-payment-type="bnpl">';

					if ( file_exists( WC_iPay88::plugin_path() . '/assets/images/' . $this->types_mapping['image'][ $number ] . '.' . $this->image_ext ) ) {
						echo '<img class="ipay88-bank-logo" alt="' . esc_attr( $this->paymenttype_options[ $number ] ) . '" src="' . esc_url( WC_HTTPS::force_https_url( WC_iPay88::plugin_url() ) . '/assets/images/' . $this->types_mapping['image'][ $number ] . '.' . $this->image_ext ) . '">';
					}
					
					echo '<div class="ipay88-payment-label">';
					echo esc_html( $this->paymenttype_options[ $number ] );
					echo '</div>';
					echo '</label>';
					
					// Add installment options as radio buttons directly under each payment option
					echo '<input type="hidden" id="ipay88_admin_fee' . esc_attr( $number ) . '" name="ipay88_admin_fee' . esc_attr( $number ) . '" value="">';
					echo '<div class="ipay88-installment-options" id="installment-options-' . esc_attr( $number ) . '" style="display: none;">';
					echo '<div class="ipay88-installment-header">';
					echo '<h5>CHOOSE YOUR INSTALLMENT</h5>';
					echo '</div>';
					echo '<div class="ipay88-installment-list">';
					// Default installment options will be populated by JavaScript
					echo '</div>';
					echo '</div>';
					echo '</div>'; // Close wrapper
				}
			}
			
			echo '</div></div>';
		}

		$credit_options = apply_filters( 'wc_ipay88_credit_payment_types', array( '2' ) );
		if ( (bool) array_intersect( $credit_options, $this->paymenttype_available ) ) {
			echo '<div class="ipay88-payment-section">';
			echo '<h4 class="ipay88-section-title">';
			echo '<div class="ipay88-section-content">';
			if ( file_exists( WC_iPay88::plugin_path() . '/assets/images/cc.' . $this->image_ext ) ) {
				echo '<img class="ipay88-section-icon" alt="Credit / Debit Card" src="' . esc_url( WC_HTTPS::force_https_url( WC_iPay88::plugin_url() ) . '/assets/images/cc.' . $this->image_ext ) . '">';
			}
			echo esc_html( __( 'Credit / Debit Card', 'wc_ipay88' ) );
			echo '</div>';
			echo '</h4>';
			echo '<div class="ipay88-payment-grid">';
			
			foreach ( $credit_options as $number ) {
				if ( in_array( $number, $this->paymenttype_available ) ) {
					echo '<label class="ipay88-payment-option" for="ipay88' . esc_attr( $this->types_mapping['id'][ $number ] ) . '">';
					echo '<input type="radio" id="ipay88' . esc_attr( $this->types_mapping['id'][ $number ] ) . '" name="ipay88_payment_type" value="' . esc_attr( $number ) . '">';
					
					if ( file_exists( WC_iPay88::plugin_path() . '/assets/images/' . $this->types_mapping['image'][ $number ] . '.' . $this->image_ext ) ) {
						echo '<img class="ipay88-bank-logo" style="width: 80px;" alt="' . esc_attr( $this->paymenttype_options[ $number ] ) . '" src="' . esc_url( WC_HTTPS::force_https_url( WC_iPay88::plugin_url() ) . '/assets/images/' . $this->types_mapping['image'][ $number ] . '.' . $this->image_ext ) . '">';
					}
					
					echo '<div class="ipay88-payment-label">';
					echo esc_html( $this->paymenttype_options[ $number ] );
					echo '</div>';
					echo '</label>';
				}
			}
			echo '</div></div>';
		}

		// E-Wallet (without GrabPay - it's now in BNPL section)
		$ewallet_options = apply_filters( 'wc_ipay88_ewallet_payment_types', array(
			'210', // Boost Wallet
			'523', // GrabPay
			'538', // Touch n Go eWallet
			'542', // Maybank PayQR
			'801', // ShopeePay
		) );
		if ( (bool) array_intersect( $ewallet_options, $this->paymenttype_available ) ) {
			echo '<div class="ipay88-payment-section">';
			echo '<h4 class="ipay88-section-title">';
			echo '<div class="ipay88-section-content">';
			if ( file_exists( WC_iPay88::plugin_path() . '/assets/images/cc.' . $this->image_ext ) ) {
				echo '<img class="ipay88-section-icon" alt="E-Wallet" src="' . esc_url( WC_HTTPS::force_https_url( WC_iPay88::plugin_url() ) . '/assets/images/cc.' . $this->image_ext ) . '">';
			}
			echo esc_html( __( 'E-Wallet', 'wc_ipay88' ) );
			echo '</div>';
			echo '</h4>';
			echo '<div class="ipay88-payment-grid">';

			foreach ( $ewallet_options as $number ) {
				if ( in_array( $number, $this->paymenttype_available ) ) {
					echo '<label class="ipay88-payment-option" for="ipay88_wallet' . esc_attr( $this->types_mapping['id'][ $number ] ) . '">';
					echo '<input type="radio" id="ipay88_wallet' . esc_attr( $this->types_mapping['id'][ $number ] ) . '" name="ipay88_payment_type" value="' . esc_attr( $number ) . '">';
					
					if ( file_exists( WC_iPay88::plugin_path() . '/assets/images/' . $this->types_mapping['image'][ $number ] . '.' . $this->image_ext ) ) {
						echo '<img class="ipay88-bank-logo" alt="' . esc_attr( $this->paymenttype_options[ $number ] ) . '" src="' . esc_url( WC_HTTPS::force_https_url( WC_IPay88::plugin_url() ) . '/assets/images/' . $this->types_mapping['image'][ $number ] . '.' . $this->image_ext ) . '">';
					}
					
					echo '<div class="ipay88-payment-label">';
					echo esc_html( $this->paymenttype_options[ $number ] );
					echo '</div>';
					echo '</label>';
				}
			}
			echo '</div></div>';
		}
		
		echo '</div>'; // Close ipay88-payment-container
		
		return apply_filters( 'wc_ipay88_my_payment_fields_html', ob_get_clean(), $this->paymenttype_available );
	}

	function bnpl_tenure()
	{
		$total = WC()->cart->total;
		$paymentPlans = PaymentMethod::getPaymentMethodPlansByAmount($total);
		foreach ($paymentPlans as $key => $plan) {
            $mechant_id = $plan->id;
            $merchant_name = $plan->name;

            if (!isset($data[$mechant_id])) {
                $data[$mechant_id] = [
                    'id' => $mechant_id,
                    'name' => $merchant_name,
                    'plans' => [],
                ];
            }

            $data[$mechant_id]['plans'][] = [
                'months' => $plan->months,
                'min_amount' => $plan->min_amount,
                'charge_rm' => $plan->charge_rm,
                'apply_admin_fee' => $plan->apply_admin_fee,
            ];
        }

		return $data;
	}
	
	/**
	 * Validate payment fields
	 **/
	function validate_fields() {
		$ptype                     = WC_iPay88::get_field( 'ipay88_payment_type', $_POST );
		$pPlan 				  = WC_iPay88::get_field( 'ipay88_payment_plan'.$ptype, $_POST );
		$adminFee                  = WC_iPay88::get_field( 'ipay88_admin_fee'.$ptype, $_POST );
		$this->posted_payment_type = null !== $ptype ? $ptype : '0';
		$this->posted_payment_plan = null !== $pPlan ? $pPlan : '0';
		$this->posted_admin_fee    = null !== $adminFee ? $adminFee : '0';
		$this->check_payment_fields( $this->posted_payment_type, $this->posted_payment_plan );
		
		//Note the credit card fields check was passed
		$this->check_pass = true;
		
		if ( ! wc_notice_count( 'error' ) ) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * @since 1.3
	 *
	 * @param $order
	 *
	 * @return string
	 */
	public function get_order_description( $order ) {
		$desc = '';

		// Loop through the order items
		if ( 0 < sizeof( $order->get_items() ) ) {
			foreach ( $order->get_items() as $item ) {
				if ( WC_Compat_iPay88::get_item_quantity( $item ) ) {
					// Get the item meta if available
					$item_meta = WC_Compat_iPay88::wc_display_item_meta( $item );

					// Get the item name
					$item_name = WC_Compat_iPay88::get_item_name( $item );
					
					// Add meta info to item name
					if ( $item_meta ) {
						$item_name .= ' (' . $item_meta . ')';
					}

					// Add the item quantity and name to the description
					$desc .= WC_Compat_iPay88::get_item_quantity( $item ) . ' x ' . $item_name . ', ';
				}
			}
			
			// Remove trailing comma and space
			$desc = rtrim( $desc, ', ' );
		}

		// If no description is generated, fallback to the order number
		if ( empty( $desc ) ) {
			$desc = 'Order #' . $order->get_order_number();
		}

		$prod_desc = wp_strip_all_tags( $desc );
		$prod_desc = html_entity_decode( $prod_desc, ENT_QUOTES, 'UTF-8' );
		$prod_desc = preg_replace('/[^A-Za-z0-9 ]/', '', $prod_desc);
		$prod_desc = substr($prod_desc, 0, 60);

		// Set the final product description
		$ipay88_args['ProdDesc'] = $prod_desc;

		return $prod_desc;
	}
	
	/**
	 * Generate iPay88 form
	 *
	 * @since 1.0.0
	 *
	 * @param $order_id
	 *
	 * @return string
	 */
	function generate_ipay88_form( $order_id ) {
		
		$order = wc_get_order( $order_id );
		
		//Debug log
		WC_iPay88::add_debug_log( 'Generating payment form for order #' . $order_id );
		
		$currency = WC_Compat_iPay88::get_order_currency( $order );
		
		// Format the order total
		$this->format_amount( $order->get_total() );
		
		$ipay88_args = array(
			'MerchantCode' => $this->MerchantCode,
			'RefNo'        => str_replace( '#', '', $order->get_order_number() ),
			'Amount'       => $this->formatted_amount,
			'Currency'     => $currency,
			'ProdDesc'     => $this->get_order_description( $order ),
			'UserName'     => WC_Compat_iPay88::get_order_billing_first_name( $order ) . ' ' . WC_Compat_iPay88::get_order_billing_last_name( $order ),
			'UserEmail'    => WC_Compat_iPay88::get_order_billing_email( $order ),
			'UserContact'  => WC_Compat_iPay88::get_order_billing_phone( $order ),
			'ResponseURL'  => WC()->api_request_url( 'WC_Gateway_iPay88' ),
			'BackendURL'   => WC_HTTPS::force_https_url( add_query_arg( 'iPay88_response', 'backend', WC()->api_request_url( 'WC_Gateway_iPay88' ) ) ),
			'Xfield1'      => '',
		);
		
		$payment_type = WC_iPay88::get_field( 'ptype', $_GET );
		if ( null != $payment_type && 0 != $payment_type ) {
			$ipay88_args['PaymentId'] = sanitize_text_field( $payment_type );
		}

		$payment_plan = WC_iPay88::get_field( 'pPlan', $_GET );
		if ( null != $payment_plan && 0 != $payment_plan ) {
			$ipay88_args['Plan'] = sanitize_text_field( $payment_plan );
			$ipay88_args['ActionType'] = '';
			$ipay88_args['TokenId'] = '';
		}
		
		//Add signature
		$ipay88_args['Signature'] = $this->generate_sha512_signature( $ipay88_args, false );
		$ipay88_args['SignatureType'] = 'HMACSHA512';
		//Debug log
		// WC_iPay88::add_debug_log( 'Order form parameters: ' . print_r( $ipay88_args, true ) );
		
		$ipay88_args = apply_filters( 'wc_ipay88_request_arguments', $ipay88_args, $order, $payment_type );
		
		$ipay88_form_array = array();
		foreach ( $ipay88_args as $key => $value ) {
			$ipay88_form_array[] = '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
		}
		
		if ( apply_filters( 'wc_ipay88_receipt_page_js_display', true ) ) {
			wc_enqueue_js(
				apply_filters( 'wc_ipay88_receipt_page_js_output',
					'jQuery("body").block({
						message: "' . apply_filters( 'wc_ipay88_redirecting_message_text', __( 'Thank you for your order. We are now redirecting you to iPay88 to make payment.', 'wc_ipay88' ) ) . '",
						overlayCSS: {
							background: "#fff",
							opacity: 0.6
						},
						css: {
							padding:        20,
							textAlign:      "center",
							color:          "#555",
							border:         "3px solid #aaa",
							backgroundColor:"#fff",
							cursor:         "wait",
							lineHeight:	"32px",
							zIndex:         "9999999"
						}
					});
					jQuery("#submit_ipay88_payment_form").click();'
				) );
		}
		
		return '<form action="' . esc_url( $this->get_form_url() ) . '" method="post" id="ipay88_payment_form" target="_top">
			' . implode( '', $ipay88_form_array ) . '
			<input type="submit" class="button-alt" id="submit_ipay88_payment_form" value="' . __( 'Pay via iPay88', 'wc_ipay88' ) . '" />'
		       . '<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'wc_ipay88' ) . '</a>
			</form>';
	}
	
	/**
	 * Return the form URL
	 *
	 * Filter: 'wc_ipay88_form_url' allows for use of different form URL
	 *
	 * @since 1.2.15
	 * @return mixed
	 */
	public function get_form_url() {
		if ( 'PH' == $this->gateway ) {
			if ( 'yes' == $this->sandbox ) {
				$this->url = 'https://payment.ipay88.com.my/epayment/entry.asp';
			} else {
				$this->url = 'https://payment.ipay88.com.my/epayment/entry.asp';
			}
		} else {
			//$this->url = 'https://payment.ipay88.com.my/epayment/entry.asp';
			$this->url = 'https://payment.ipay88.com.my/epayment/entry.asp';
		}
		
		return apply_filters( 'wc_ipay88_form_url', $this->url, $this->gateway, $this->sandbox );
	}
	
	/**
	 * Process the payment
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	function process_payment( $order_id ) {
		// Log the start
		// WC_iPay88::add_debug_log( '=== Starting process_payment for order #' . $order_id );
		// WC_iPay88::add_debug_log( 'POST data: ' . print_r( $_POST, true ) );
		
		if ( ! $this->check_pass ) {
			$ptype = WC_iPay88::get_field( 'ipay88_payment_type', $_POST );
			$pPlan = WC_iPay88::get_field( 'ipay88_payment_plan'.$ptype, $_POST );
			
			$this->posted_payment_type = null !== $ptype ? $ptype : '0';
			$this->posted_payment_plan = null !== $pPlan ? $pPlan : '0';
			$this->check_payment_fields( $this->posted_payment_type , $this->posted_payment_plan );
		}

		if ( $this->check_pass ) {
			$ptype = WC_iPay88::get_field( 'ipay88_payment_type', $_POST );
			$pPlan = WC_iPay88::get_field( 'ipay88_payment_plan'.$ptype, $_POST );
			$adminFee = WC_iPay88::get_field( 'ipay88_admin_fee'.$ptype, $_POST );
			// $adminFeeDB = PaymentMethod::getAdminFeePaymentMethods($ptype, $pPlan);
			
			$this->posted_payment_type = null !== $ptype ? $ptype : '0';
			$this->posted_payment_plan = null !== $pPlan ? $pPlan : '0';
			$this->posted_admin_fee = null !== $adminFee ? $adminFee : '0';
			$this->check_payment_fields( $this->posted_payment_type , $this->posted_payment_plan );
		}
		
		// Check for validation errors
		if ( wc_notice_count( 'error' ) ) {
			WC_iPay88::add_debug_log( 'Validation errors found' );
			return array(
				'result' => 'failure',
				'messages' => wc_print_notices( true )
			);
		}
		
		try {
			$order = wc_get_order( $order_id );
			
			if ( ! $order ) {
				WC_iPay88::add_debug_log( 'ERROR: Order not found' );
				throw new Exception( 'Order not found' );
			}

			// Store payment information
			$paymentName = isset( $this->types_mapping['name'][ $this->posted_payment_type ] ) ? $this->types_mapping['name'][ $this->posted_payment_type ] : '';
			update_post_meta( $order_id, '_ipay88_payment_type_name', sanitize_text_field( $paymentName ) );
			update_post_meta( $order_id, '_ipay88_payment_type', sanitize_text_field( $this->posted_payment_type ) );
			update_post_meta( $order_id, '_ipay88_payment_plan', sanitize_text_field( $this->posted_payment_plan ) );
			update_post_meta( $order_id, '_ipay88_admin_fee', sanitize_text_field( $this->posted_admin_fee ) );
			
			// WC_iPay88::add_debug_log( 'Generating form data...' );


			$payment_plan = (int) $this->posted_payment_plan;

			if (
				in_array( $this->posted_payment_type, ['523', '891'], true )
				&& $payment_plan > 0
			) {
				WC_iPay88::add_debug_log(
					'Using SECOND MerchantCode/MerchantKey (BNPL installment selected)'
				);

				$this->MerchantCode = SECOND_IPAY88_MERCHANT_CODE_LIVE;
				$this->MerchantKey  = SECOND_IPAY88_MERCHANT_KEY_LIVE;
			}
			
			// Generate iPay88 form data
			$ipay88_form_data = $this->get_ipay88_form_data( $order_id );
			
			// WC_iPay88::add_debug_log( 'Form data generated successfully' );
			// WC_iPay88::add_debug_log( 'Form URL: ' . $this->get_form_url() );
			
			// Return success with form data for AJAX submission
			$response = array(
				'result' => 'success',
				'redirect' => '#ipay88-redirect',
				'ipay88_form_data' => $ipay88_form_data,
				'ipay88_form_url' => $this->get_form_url()
			);
			
			WC_iPay88::add_debug_log( 'Returning response: ' . print_r( $response, true ) );
			
			return $response;
			
		} catch ( Exception $e ) {
			WC_iPay88::add_debug_log( 'ERROR: ' . $e->getMessage() );
			wc_add_notice( $e->getMessage(), 'error' );
			return array(
				'result' => 'failure',
				'messages' => wc_print_notices( true )
			);
		}
	}

	/**
	 * Get iPay88 form data without generating HTML form
	 */
	function get_ipay88_form_data( $order_id ) {
		$order = wc_get_order( $order_id );
		
		$currency = WC_Compat_iPay88::get_order_currency( $order );
		
		// Format the order total
		$this->format_amount( $order->get_total() );
		
		$ipay88_args = array(
			'MerchantCode' => $this->MerchantCode,
			'RefNo'        => str_replace( '#', '', $order->get_order_number() ),
			'Amount'       => $this->formatted_amount,
			'Currency'     => $currency,
			'ProdDesc'     => $this->get_order_description( $order ),
			'UserName'     => WC_Compat_iPay88::get_order_billing_first_name( $order ) . ' ' . WC_Compat_iPay88::get_order_billing_last_name( $order ),
			'UserEmail'    => WC_Compat_iPay88::get_order_billing_email( $order ),
			'UserContact'  => WC_Compat_iPay88::get_order_billing_phone( $order ),
			'ResponseURL'  => WC()->api_request_url( 'WC_Gateway_iPay88' ),
			'BackendURL'   => WC_HTTPS::force_https_url( add_query_arg( 'iPay88_response', 'backend', WC()->api_request_url( 'WC_Gateway_iPay88' ) ) ),
			'Xfield1'      => '',
		);
		
		$payment_type = $this->posted_payment_type;
		if ( null != $payment_type && 0 != $payment_type ) {
			$ipay88_args['PaymentId'] = sanitize_text_field( $payment_type );
		}

		$payment_plan = $this->posted_payment_plan;
		if ( null != $payment_plan && 0 != $payment_plan ) {
			$ipay88_args['Plan'] = sanitize_text_field( $payment_plan );
			$ipay88_args['ActionType'] = '';
			$ipay88_args['TokenId'] = '';
		}
		
		// Add signature
		$ipay88_args['Signature'] = $this->generate_sha512_signature( $ipay88_args, false );
		$ipay88_args['SignatureType'] = 'HMACSHA512';
		
		// Debug log
		WC_iPay88::add_debug_log( 'Order form parameters: ' . print_r( $ipay88_args, true ) );
		
		$ipay88_args = apply_filters( 'wc_ipay88_request_arguments', $ipay88_args, $order, $payment_type );
		
		return $ipay88_args;
	}
	
	/**
	 * Outputs the form in the pay page
	 *
	 * @param $order
	 */
	function receipt_page( $order ) {
		if ( apply_filters( 'wc_ipay88_display_description_before_form', true, $order ) ) {
			echo '<p>' . __( 'Thank you for your order, please click the button below to pay with iPay88.', 'wc_ipay88' ) . '</p>';
		}
		
		do_action( 'wc_ipay88_receipt_page_before_form', $order );
		
		echo $this->generate_ipay88_form( $order );
		
		do_action( 'wc_ipay88_receipt_page_after_form', $order );
	}
	
	/**
	 * Check and validate the received response
	 **/
	function validate_response() {
		
		//Debug log
		WC_iPay88::add_debug_log( 'Validating response...' );
		
		$signature = $this->generate_sha512_signature( $_POST );
		
		//Debug log
		WC_iPay88::add_debug_log( 'Generated response signature is: ' . $signature );
		WC_iPay88::add_debug_log( 'Received post signature is: ' . WC_iPay88::get_field( 'Signature', $_POST ) );
		
		if ( WC_iPay88::get_field( 'Signature', $_POST ) == $signature ) {
			
			$order_id = $this->get_order_id( WC_iPay88::get_field( 'RefNo', $_POST ) );
			
			$order = wc_get_order( (int) $order_id );

			//update order meta with payment type and plan
			if ( null !== WC_iPay88::get_field( 'PaymentId', $_POST ) ) {
				// $paymentName = isset( $this->types_mapping['name'][ WC_iPay88::get_field( 'PaymentId', $_POST ) ] ) ? $this->types_mapping['name'][ WC_iPay88::get_field( 'PaymentId', $_POST ) ] : '';
				// update_post_meta( $order_id, '_ipay88_payment_type', sanitize_text_field( WC_iPay88::get_field( 'PaymentId', $_POST ) ) );
				// update_post_meta( $order_id, '_ipay88_payment_type_name', sanitize_text_field( $paymentName ) );
				// update_post_meta( $order_id, '_ipay88_payment_plan', sanitize_text_field( WC_iPay88::get_field( 'Plan', $_POST ) ) );
			}
			
			if ( false == $order ) {
				//Debug log
				WC_iPay88::add_debug_log( 'Could not retrieve the order from the response.' );
				
				return false;
			}
			
			//Debug log
			WC_iPay88::add_debug_log( 'Signature validation passed.' );
			
			$hash_amount = WC_iPay88::get_field( 'Amount', $_POST );

			// Remove commas AND dots, keep only numbers
			$clean_received = preg_replace('/[^0-9]/', '', $hash_amount);

			// Order total formatted the same way
			$order_total = (string) intval($order->get_total() * 100);

			WC_iPay88::add_debug_log( 'Order total is: ' . $order_total );
			WC_iPay88::add_debug_log( 'Received amount is: ' . $clean_received );

			if ($order_total === $clean_received) {
				WC_iPay88::add_debug_log( 'Amount validation passed.' );
				return true;
			}
			
			//Debug log
			WC_iPay88::add_debug_log( 'Amount validation failed.' );
			
			return false;
		} else {
			
			//Debug log
			WC_iPay88::add_debug_log( 'Signature validation failed.' );
			
			return false;
		}
	}
	
	/**
	 * Check for iPay88 Payment Response.
	 * Process Payment based on the Response.
	 **/
	function check_status_response_ipay88() {
		
		$posted = stripslashes_deep( $_POST );
		
		$is_backend_notification = ( WC_iPay88::get_field( 'iPay88_response', $_GET ) == 'backend' );
		
		// $received_ok = 'PH' == $this->gateway ? 'RECEIVEOK' : 'OK';
		$received_ok = 'RECEIVEOK';
		
		//Debug log
		// Backend notification will get the OK response
		if ( $is_backend_notification ) {
			WC_iPay88::add_debug_log( 'Backend response.' );
		}
		WC_iPay88::add_debug_log( 'Payment response received. Response is: ' . print_r( $posted, true ) );
		
		if ( $this->validate_response() ) {
			
			$refno   = WC_iPay88::get_field( 'RefNo', $posted );
			$transid = WC_iPay88::get_field( 'TransId', $posted );
			$estatus = WC_iPay88::get_field( 'Status', $posted );
			$errdesc = WC_iPay88::get_field( 'ErrDesc', $posted );
			$authcode = WC_iPay88::get_field( 'AuthCode', $posted );
			$ccno = WC_iPay88::get_field( 'CCNo', $posted );
			
			$order_id = $this->get_order_id( $refno );
			
			$order = wc_get_order( (int) $order_id );

			//store in post meta the iPay88 transaction id
			update_post_meta( $order_id, '_ipay88_transaction_id', sanitize_text_field( $transid ) );
			update_post_meta( $order_id, '_ipay88_auth_code', sanitize_text_field( $authcode ) );
			update_post_meta( $order_id, '_ipay88_cc_no', sanitize_text_field( $ccno ) );
			//store in post meta the full iPay88 response
			update_post_meta( $order_id, '_ipay88_response_json', json_encode($posted) );
			
			$redirect_url = $this->get_return_url( $order );
			
			// Check if the order was already processed
			if ( 'completed' == $order->get_status() || 'processing' == $order->get_status() ) {
				
				// Debug log
				WC_iPay88::add_debug_log( 'Payment already processed. Aborting.' );
				
				// Backend notification will get the OK response
				if ( $is_backend_notification ) {
					echo $received_ok;
				} else {
					// Normal Payment notification need to be redirected to the "Thank You" page.
					wp_safe_redirect( $redirect_url );
				}
				exit;
			}
			
			switch ( $estatus ) :
				case 1 : // Successful payment
					
					// Update order
					$order->add_order_note(
						sprintf(
							__(
								'iPay88 Payment Completed.'
								. ' Transaction Reference Number: %s.', 'wc_ipay88'
							),
							$transid
						)
					);
					
					// Debug log
					WC_iPay88::add_debug_log( 'Payment completed.' );
					
					WC_Compat_iPay88::empty_cart();
					
					$order->payment_complete();
					
					break;
				case 2 : // Failed payment
				default :
					// Debug log
					WC_iPay88::add_debug_log( 'Payment failed.' );
					
					// Update order
					$order->add_order_note(
						sprintf(
							__(
								'iPay88 Payment Failed.
								   Error Description: %s
								   Transaction Reference Number: %s.', 'wc_ipay88'
							),
							$errdesc, $transid
						)
					);
					
					$order->update_status( 'failed' );
					
					// Add error to show the customer and the cancel URL
					wc_add_notice(
						__(
							'Your Payment Failed.
							Please try again or use another payment option.', 'wc_ipay88'
						), 'error'
					);
					
					break;
			endswitch;
			
			// Backend notification will get the OK response
			if ( $is_backend_notification ) {
				echo $received_ok;
			} else {
				// Normal Payment notification needs to be redirected
				if ( $estatus == 1 ) {
					// Success - redirect to thank you page
					wp_safe_redirect( $redirect_url );
				} else {
					// Failed - redirect to checkout page with error notice
					wp_safe_redirect( wc_get_checkout_url() );
				}
			}
			exit;
		}
		
		// Redirect to cart, if the validation failed
		WC_iPay88::add_debug_log( 'An error occurred while validating your iPay88 response. Please double check the plugin settings. May be you tested with the wrong purchase data or there was some error on iPay88 side.' );
		wc_add_notice( __( 'An error occurred while validating your payment notification.', 'wc_ipay88' ) );
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}
	
	/**
	 * Generate the sha1 control signature. <br/>
	 * Used in both the request and the response to validate the authenticity of the message.
	 *
	 * @param array $params      The request or response parameters
	 * @param bool  $is_response Are the parameters from the response message
	 *
	 * @return string The sha1 generated string
	 **/
	private function generate_sha1_signature( $params, $is_response = true ) {
		
		if ( $is_response ) {
			$this->format_amount( str_replace( ',', '', $params['Amount'] ) );
			$string = $params['PaymentId'] . $params['RefNo'] . $this->hash_amount . $params['Currency'] . $params['Status'];
		} else {
			$string = $params['RefNo'] . $this->hash_amount . $params['Currency'];
		}
		
		//Debug log
		WC_iPay88::add_debug_log( 'Signature string is: ' . $string );
		
		$string = apply_filters( 'wc_ipay88_sha1_signature_string', $this->MerchantKey . $this->MerchantCode . $string, $params, $is_response, $this );
		
		return apply_filters( 'wc_ipay88_sha1_signature', base64_encode( $this->hex2bin( sha1( $string ) ) ), $string, $is_response );
	}

	private function generate_sha512_signature($params, $is_response = true) {
		// Amount for signature must strip '.' and ','
		$amountForSig = preg_replace('/[.,]/', '', isset($params['Amount']) ? (string)$params['Amount'] : '');

		if ($is_response) {
			// Response signature: Key + Code + PaymentId + RefNo + Amount + Currency + Status
			$message = $this->MerchantKey
					. $this->MerchantCode
					. (isset($params['PaymentId']) ? (string)$params['PaymentId'] : '')
					. (string)$params['RefNo']
					. $amountForSig
					. (string)$params['Currency']
					. (string)$params['Status'];
		} else {
			// Request signature: Key + Code + RefNo + Amount + Currency + Xfield1
			$xfield1 = isset($params['Xfield1']) ? (string)$params['Xfield1'] : '';
			$message = $this->MerchantKey
					. $this->MerchantCode
					. (string)$params['RefNo']
					. $amountForSig
					. (string)$params['Currency']
					. $xfield1;
		}

		// HMAC-SHA512 with MerchantKey as key, HEX output (lowercase)
		$raw = hash_hmac('sha512', $message, $this->MerchantKey, true);
		$hex = strtolower(bin2hex($raw));
		return $hex;
	}
	
	function hex2bin( $hexSource ) {
		$bin = '';
		for ( $i = 0; $i < strlen( $hexSource ); $i = $i + 2 ) {
			$bin .= chr( hexdec( substr( $hexSource, $i, 2 ) ) );
		}
		
		return $bin;
	}
	
	/**
	 * Check the Payment method is submitted and is valid
	 *
	 * @param string $payment_type
	 */
	private function check_payment_fields( $payment_type = '0', $payment_plan = '0' ) {
		
		// Check only if there are available payment types
		if ( 'PH' == $this->gateway ) {
			if ( ! empty( $this->paymenttype_available_ph ) ) {
				if ( '0' == $payment_type ) {
					wc_add_notice( __( 'Payment type is required.', 'wc_ipay88' ), 'error' );
					
					return;
				}
				
				if ( ! in_array( $payment_type, $this->paymenttype_available_ph ) ) {
					wc_add_notice( __( 'Wrong payment type. Please try again.', 'wc_ipay88' ), 'error' );
					
					return;
				}
			}
		} else {
			if ( ! empty( $this->paymenttype_available ) ) {
				if ( '0' == $payment_type ) {
					wc_add_notice( __( 'Payment type is required.', 'wc_ipay88' ), 'error' );
					
					return;
				}
				
				if ( ! in_array( $payment_type, $this->paymenttype_available ) ) {
					wc_add_notice( __( 'Wrong payment type. Please try again.', 'wc_ipay88' ), 'error' );
					
					return;
				}

				$bnpl_options = apply_filters( 'wc_ipay88_bnpl_payment_types', array(
					'111',
					'112',
					'115',
					'157',
					'174',
					'179',
					'534',
					'606',
					'727',
					'891',
					'523',
				) );

				if ( in_array( $payment_type, $bnpl_options ) ) {
					if ( '0' == $payment_plan ) {
						wc_add_notice( __( 'Payment plan is required for the selected payment type.', 'wc_ipay88' ), 'error' );

						return;
					}
				}
			}
		}
	}
	
	/**
	 * Format the two amounts we need.
	 * One for hashing
	 * One for request parameter
	 *
	 * @param double $amount
	 */
	function format_amount( $amount ) {
		if ( is_numeric( $amount ) ) {
			$this->hash_amount      = number_format( $amount, 2, '', '' );
			$this->formatted_amount = number_format( $amount, 2, '.', ',' );
		}
	}
	
	/**
	 * Get the order ID. Check to see if SON and SONP is enabled and
	 *
	 * @global WC_Seq_Order_Number     $wc_seq_order_number
	 * @global WC_Seq_Order_Number_Pro $wc_seq_order_number_pro
	 *
	 * @param int                      $order_number
	 *
	 * @return int
	 */
	private function get_order_id( $order_number ) {
		
		ob_start();
		
		// Find the order ID from the custom order number, if we have SON or SONP enabled
		if ( class_exists( 'WC_Seq_Order_Number_Pro' ) ) {
			
			if ( function_exists( 'wc_seq_order_number_pro' ) ) {
				$order_id = wc_seq_order_number_pro()->find_order_by_order_number( $order_number );
			} else {
				global $wc_seq_order_number_pro;
				$order_id = $wc_seq_order_number_pro->find_order_by_order_number( $order_number );
			}
			
			if ( 0 === $order_id ) {
				$order_id = $order_number;
			}
		} elseif ( class_exists( 'WC_Seq_Order_Number' ) ) {
			
			if ( function_exists( 'wc_sequential_order_numbers' ) ) {
				$order_id = wc_sequential_order_numbers()->find_order_by_order_number( $order_number );
			} else {
				global $wc_seq_order_number;
				$order_id = $wc_seq_order_number->find_order_by_order_number( $order_number );
			}
			
			if ( 0 === $order_id ) {
				$order_id = $order_number;
			}
		} else {
			$order_id = $order_number;
		}
		
		// Remove any error notices generated during the process
		@ob_clean();
		
		return $order_id;
	}
} //end vanbodevelops ipay88 class
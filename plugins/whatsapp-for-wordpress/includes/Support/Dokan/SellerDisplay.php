<?php

namespace NTA_WhatsApp\Support\Dokan;

defined( 'ABSPATH' ) || exit;

class SellerDisplay {

	protected static $instance = null;

	private $isEnabled = false;

	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->doHooks();
		}

		return self::$instance;
	}

	private function __construct() {
	}

	private function doHooks() {
			add_action( 'init', array( $this, 'enqueue_scripts' ) );
			add_action( 'template_redirect', array( $this, 'specify_whatsapp_button_position' ) );
	}

	public function enqueue_scripts() {
		if ( class_exists( 'WeDevs_Dokan' ) && defined( 'NTA_WHATSAPP_VERSION' ) ) {
			$this->isEnabled = true;
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueueFrontendScripts' ) );
		}
	}

	public function specify_whatsapp_button_position() {
		if ( ! $this->isEnabled || ! is_single() ) {
			return;
		}

		$currentProductId = get_the_ID();

		$currentVendor = dokan_get_vendor_by_product( $currentProductId );

		if ( ! $currentVendor ) {
			return;
		}

		$waButtonPosition = get_user_meta( $currentVendor->get_id(), 'whatsapp_button_position', true );

		if ( 'before_atc' === $waButtonPosition ) {
			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'insertButtonWA' ) );
		} elseif ( 'after_atc' === $waButtonPosition ) {
			add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'insertButtonWA' ) );
		} elseif ( 'after_short_description' === $waButtonPosition ) {
			add_filter( 'woocommerce_short_description', array( $this, 'showAfterShortDescriptionWA' ) );
		} elseif ( 'after_long_description' === $waButtonPosition ) {
			add_filter( 'the_content', array( $this, 'showAfterLongDescriptionWA' ) );
		}

		add_action( 'woocommerce_after_single_product', array( $this, 'insertFloatingWidgetWA' ), 10, 0 );
		// add_action( 'dokan_after_store_tabs', array( $this, 'insertWidgetWAToStoreList' ), 10, 1 );
	}

	protected function isAccountPublished( $userWAId ) {
		$accountStatus = get_post_status( $userWAId );

		if ( $accountStatus && 'trash' === $accountStatus ) {
			return false;
		}

		return true;
	}

	public function insertButtonWA() {
		global $product;
		$id                = $product->get_id();
		$vendor            = dokan_get_vendor_by_product( $id );
		$userWAId          = get_user_meta( $vendor->get_id(), 'dokan_whatsapp_id', true );
		$userWABtnPosition = get_user_meta( $vendor->get_id(), 'whatsapp_button_position', true );
		if ( empty( $userWAId ) || ! $this->isAccountPublished( $userWAId ) ) {
			return;
		} else {
			if ( ! $userWABtnPosition ) {
				return;
			}
			echo '<div class="nta-woo-products-button">' . do_shortcode( '[njwa_button id="' . esc_attr( $userWAId ) . '"]' ) . '</div>';
		}
	}

	public function showAfterShortDescriptionWA( $post_excerpt ) {
		global $product;

		if ( ! is_single() || empty( $post_excerpt ) ) {
			return $post_excerpt;
		}

		$id                = $product->get_id();
		$vendor            = dokan_get_vendor_by_product( $id );
		$userWAId          = get_user_meta( $vendor->get_id(), 'dokan_whatsapp_id', true );
		$userWABtnPosition = get_user_meta( $vendor->get_id(), 'whatsapp_button_position', true );

		$btnContent = '<div class="nta-woo-products-button">' . do_shortcode( '[njwa_button id="' . esc_attr( $userWAId ) . '"]' ) . '</div>';

		if ( empty( $userWAId ) || ! $this->isAccountPublished( $userWAId ) ) {
			return;
		} else {
			if ( ! $userWABtnPosition ) {
				return $post_excerpt;
			}

			return $post_excerpt . $btnContent;
		}
	}

	public function showAfterLongDescriptionWA( $content ) {
		global $product;

		if ( 'product' !== get_post_type() || ! is_single() ) {
			return $content;
		}

		$id                = $product->get_id();
		$vendor            = dokan_get_vendor_by_product( $id );
		$userWAId          = get_user_meta( $vendor->get_id(), 'dokan_whatsapp_id', true );
		$userWABtnPosition = get_user_meta( $vendor->get_id(), 'whatsapp_button_position', true );

		$btnContent = '<div class="nta-woo-products-button">' . do_shortcode( '[njwa_button id="' . esc_attr( $userWAId ) . '"]' ) . '</div>';

		if ( empty( $userWAId ) || ! $this->isAccountPublished( $userWAId ) ) {
			return;
		} else {
			if ( ! $userWABtnPosition ) {
				return $content;
			}

			return $content . $btnContent;
		}
	}

	public function insertFloatingWidgetWA() {
		global $product;
		$id                          = $product->get_id();
		$vendor                      = dokan_get_vendor_by_product( $id );
		$userWAId                    = get_user_meta( $vendor->get_id(), 'dokan_whatsapp_id', true );
		$userWAFloatingWidgetDisplay = get_user_meta( $vendor->get_id(), 'whatsapp_display_floating_widget', true );
		if ( empty( $userWAId ) || ! $this->isAccountPublished( $userWAId ) ) {
			return;
		} elseif ( $userWAFloatingWidgetDisplay ) {
				$waUrl         = '';
				$waButtonLabel = '';
				$waAccountInfo = get_post_meta( $userWAId, 'nta_wa_account_info', true );
			if ( ! empty( $waAccountInfo ) ) {
				if ( ! empty( $waAccountInfo['number'] ) ) {
					$waUrl = is_numeric( $waAccountInfo['number'] ) ? 'https://api.whatsapp.com/send?phone=' . (int) $waAccountInfo['number'] : $waAccountInfo['number'];
				}
			}
				$waButtonStyles = get_post_meta( $userWAId, 'nta_wa_button_styles', true );
			if ( ! empty( $waButtonStyles ) ) {
				$waButtonLabel = ! empty( $waButtonStyles['label'] ) ? $waButtonStyles['label'] : '';
			}
				echo '
                    <div id="dokan-wa" class="dokan_wa_widget_container">
                        <div class="dokan_wa_btn_popup">
                            <a href="' . esc_url( $waUrl ) . '" target="_blank">
                                <div class="dokan_wa_btn_popup_txt">
                                    <span>' . wp_kses_post( $waButtonLabel ) . '</span>
                                </div>
                                <div class="dokan_wa_btn_popup_icon"></div>
                            </a>
                        </div>
                    </div>
                ';
		} else {
			return;
		}
	}

	public function insertWidgetWAToStoreList( $vendorId ) {
		$userWAId                    = get_user_meta( $vendorId, 'dokan_whatsapp_id', true );
		$userWAFloatingWidgetDisplay = get_user_meta( $vendorId, 'whatsapp_display_floating_widget', true );
		if ( empty( $userWAId ) ) {
			return;
		} elseif ( $userWAFloatingWidgetDisplay ) {
				$waUrl         = '';
				$waButtonLabel = '';
				$waAccountInfo = get_post_meta( $userWAId, 'nta_wa_account_info', true );
			if ( ! empty( $waAccountInfo ) ) {
				if ( ! empty( $waAccountInfo['number'] ) ) {
					$waUrl = is_numeric( $waAccountInfo['number'] ) ? 'https://api.whatsapp.com/send?phone=' . $waAccountInfo['number'] : $waAccountInfo['number'];
				}
			}
				$waButtonStyles = get_post_meta( $userWAId, 'nta_wa_button_styles', true );
			if ( ! empty( $waButtonStyles ) ) {
				$waButtonLabel = ! empty( $waButtonStyles['label'] ) ? $waButtonStyles['label'] : '';
			}
				echo '
                    <div id="dokan-wa" class="dokan_wa_widget_container">
                        <div class="dokan_wa_btn_popup">
                            <a href="' . esc_url( $waUrl ) . '" target="_blank">
                                <div class="dokan_wa_btn_popup_txt">
                                    <span>' . wp_kses_post( $waButtonLabel ) . '</span>
                                </div>
                                <div class="dokan_wa_btn_popup_icon"></div>
                            </a>
                        </div>
                    </div>
                ';
		} else {
			return;
		}
	}

	public function enqueueFrontendScripts() {
		$id   = get_the_ID();
		$post = get_post( $id );
		if ( $post && 'product' == $post->post_type ) {
			$vendor            = dokan_get_vendor_by_product( $id );
			$userWAId          = get_user_meta( $vendor->get_id(), 'dokan_whatsapp_id', true );
			$userWABtnPosition = get_user_meta( $vendor->get_id(), 'whatsapp_button_position', true );
			wp_enqueue_style( 'dokan-whatsapp-display', NTA_WHATSAPP_PLUGIN_URL . 'assets/css/dokan.css', array(), '1.0' );
			wp_enqueue_script( 'dokan_wa_display_js', NTA_WHATSAPP_PLUGIN_URL . 'assets/js/dokan.js', array(), '1.0', true );
			wp_localize_script(
				'dokan_wa_display_js',
				'displayWADokan',
				array(
					'whatsappId' => $userWAId,
					'btnDisplay' => $userWABtnPosition,
				)
			);
		} elseif ( ! empty( get_query_var( 'author' ) ) ) {
			$userWAId          = get_user_meta( get_query_var( 'author' ), 'dokan_whatsapp_id', true );
			$userWABtnPosition = get_user_meta( get_query_var( 'author' ), 'whatsapp_button_position', true );
			wp_enqueue_style( 'dokan-whatsapp-display', NTA_WHATSAPP_PLUGIN_URL . 'assets/css/dokan.css', array(), '1.0' );
			wp_enqueue_script( 'dokan_wa_display_js', NTA_WHATSAPP_PLUGIN_URL . 'assets/js/dokan.js', array(), '1.0', true );
			wp_localize_script(
				'dokan_wa_display_js',
				'displayWADokan',
				array(
					'whatsappId' => $userWAId,
					'btnDisplay' => $userWABtnPosition,
				)
			);
		}
	}
}

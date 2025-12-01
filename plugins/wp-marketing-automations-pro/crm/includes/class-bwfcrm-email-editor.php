<?php
/**
 * Email Editor Controller
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BWFCRM_Email_Editor_Controller
 */
class BWFCRM_Email_Editor_Controller {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		add_action( 'wp_ajax_nopriv_bwf_email_builder_data', array( $this, 'handle_remote_email_editor_data' ) );
		add_action( 'bwfan_action_send_email_editors', array( $this, 'add_drag_n_drop_editor' ) );
		add_action( 'bwfan_action_send_email_template', array( $this, 'add_drag_n_drop_editor_iframe' ) );

		add_action( 'admin_head', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		if ( ! isset( $_GET['page'] ) || ! isset( $_GET['edit'] ) || 'autonami-automations' !== $_GET['page'] ) {
			return;
		}

		$license       = BWFAN_Common::get_pro_license();
		$token         = get_option( 'bwfan_u_key', 0 );
		$site_url      = urlencode( home_url() );
		$automation_id = sanitize_key( $_GET['edit'] );

		$iframe_src = "//app.getautonami.com/get/$license/$token/$site_url/crm.automation.$automation_id";
		/** Remove extra slashes */
		$iframe_src = preg_replace( '/([^:])(\/{2,})/', '$1/', $iframe_src );

		$params = array(
			'license'    => $license,
			'token'      => $token,
			'site_url'   => $site_url,
			'iframe_src' => $iframe_src,
		);

		$dependencies = array( 'jquery', 'wp-api-fetch', 'wp-url' );

		if ( bwfan_is_woocommerce_active() ) {
			$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD';

			$params['currency'] = array(
				'code'              => $currency,
				'precision'         => wc_get_price_decimals(),
				'symbol'            => html_entity_decode( get_woocommerce_currency_symbol( $currency ) ),
				'symbolPosition'    => get_option( 'woocommerce_currency_pos' ),
				'decimalSeparator'  => wc_get_price_decimal_separator(),
				'thousandSeparator' => wc_get_price_thousand_separator(),
				'priceFormat'       => html_entity_decode( get_woocommerce_price_format() ),
			);

			$dependencies[] = 'wc-currency';
		}

		wp_enqueue_style( 'bwfan-automation-drag-drop-editor', BWFAN_PRO_PLUGIN_URL . '/admin/assets/css/bwfan-drag-drop-editor.css' );

		wp_enqueue_script( 'bwfan-automation-drag-drop-editor', BWFAN_PRO_PLUGIN_URL . '/admin/assets/js/bwfan-drag-drop-editor.js', $dependencies, BWFAN_VERSION_DEV, true );
		wp_localize_script( 'bwfan-automation-drag-drop-editor', 'bwfan_automation_drag_drop', $params );
	}

	public function do_request_security_check() {
		if ( ! isset( $_POST['nonce'] ) || empty( $_POST['nonce'] ) || $_POST['nonce'] !== $this->get_security_token() ) {
			wp_send_json_error( array( 'error' => 'Invalid Nonce' ) );
		}

		$license = htmlspecialchars( filter_input( INPUT_POST, 'license', FILTER_UNSAFE_RAW ) );
		if ( $license !== BWFAN_Common::get_pro_license() ) {
			wp_send_json_error( array( 'error' => 'Invalid License' ) );
		}
	}

	public function handle_remote_email_editor_data() {
		BWFAN_PRO_Common::nocache_headers();
		$this->do_request_security_check();

		$object_id = htmlspecialchars( filter_input( INPUT_POST, 'object_id', FILTER_UNSAFE_RAW ) );
		if ( false !== strpos( $object_id, 'crm.broadcast' ) ) {
			$this->handle_broadcast_request();
		}
	}

	public function handle_broadcast_request() {
		$object_id = htmlspecialchars( filter_input( INPUT_POST, 'object_id', FILTER_UNSAFE_RAW ) );
		if ( empty( $object_id ) ) {
			wp_send_json_error( array( 'error' => 'Invalid Object ID' ) );
		}

		$object_id      = explode( '.', $object_id );
		$broadcast_id   = absint( $object_id[2] );
		$content_number = absint( $object_id[4] );
		$html           = stripslashes_deep( $_POST['html'] );
		$design         = stripslashes_deep( $_POST['design'] );
		$subject        = empty( $_POST['subject'] ) ? '' : $_POST['subject'];
		$email          = empty( $_POST['email'] ) ? '' : $_POST['email'];

		$current_action = htmlspecialchars( filter_input( INPUT_POST, 'current_action', FILTER_UNSAFE_RAW ) );
		if ( empty( $current_action ) ) {
			wp_send_json_error( array( 'error' => 'Invalid/Empty action' ) );
		}

		$response = '';
		$status   = false;
		switch ( $current_action ) {
			case 'save_design':
				if ( true === BWFCRM_Core()->campaigns->save_editor_content( $broadcast_id, $content_number, $design, $html ) ) {
					$status   = true;
					$response = 'Content Saved';
				}
				break;
			case 'get_design':
				$design = BWFCRM_Core()->campaigns->get_editor_design( $broadcast_id, $content_number );
				if ( ! empty( $design ) ) {
					$status = true;
					wp_send_json( array(
						'message'     => $response,
						'status'      => $status,
						'design_data' => $design,
					) );
				} else {
					$response = 'Content empty or not found';
				}
				break;
			case 'upload_media':
				$url = $this->upload_media();
				if ( ! empty( $url ) ) {
					$status = true;
					wp_send_json( array(
						'message' => $response,
						'status'  => $status,
						'file'    => $url,
					) );
				} else {
					$response = 'Unable to upload the media';
				}
				break;
			case 'send_test_email':
				$result = BWFCRM_Core()->conversation->send_test_email( array(
					'subject' => $subject,
					'body'    => $html,
					'email'   => $email,
				) );
				if ( ! empty( $result ) ) {
					$status = true;
					wp_send_json( array(
						'message' => $response,
						'status'  => $status,
					) );
				}
				break;
		}

		if ( empty( $response ) ) {
			wp_send_json_error( array( 'error' => 'Error while completing the action' ) );
		}

		wp_send_json( array(
			'message' => $response,
			'status'  => $status,
		) );
	}

	public function get_security_token() {
		return get_option( 'bwfan_u_key', '' );
	}

	public function get_editor_localize_settings() {
		return array(
			'license'      => BWFAN_Pro_Common::encrypt_32_bit_string( BWFAN_Common::get_pro_license() ),
			'editor_nonce' => $this->get_security_token(),
			'url'          => urlencode( site_url() ),
		);
	}

	function upload_media( $image = '' ) {
		if ( ! isset( $_FILES['file'] ) && empty( $image ) ) {
			return false;
		}

		$image_to_upload = isset( $_FILES['file'] ) ? $_FILES['file'] : $image;

		require_once ABSPATH . '/wp-admin/includes/plugin.php';
		require_once ABSPATH . '/wp-admin/includes/media.php';
		require_once ABSPATH . '/wp-admin/includes/file.php';
		require_once ABSPATH . '/wp-admin/includes/image.php';

		$id = media_handle_sideload( $image_to_upload );

		/**
		 * We don't want to pass something to $id
		 * if there were upload errors.
		 * So this checks for errors
		 */
		if ( is_wp_error( $id ) ) {
			return false;
		}

		$value = wp_get_attachment_url( $id );

		return empty( $value ) ? false : $value;
	}

	public function add_drag_n_drop_editor() {
		include_once BWFAN_PRO_PLUGIN_DIR . '/admin/views/bwfan-wp-send-email-editor.php';
	}

	public function add_drag_n_drop_editor_iframe() {
		include_once BWFAN_PRO_PLUGIN_DIR . '/admin/views/bwfan-wp-send-email-editor-script.php';
	}
}

if ( class_exists( 'BWFCRM_Email_Editor_Controller' ) ) {
	BWFCRM_Core::register( 'email_editor', 'BWFCRM_Email_Editor_Controller' );
}

<?php
/**
 * Email Webhook Handler Controller Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BWFCRM_Email_Webhook_Handler
 *
 */
class BWFCRM_Email_Webhook_Handler {
	private $_webhooks = [];

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'load_webhooks' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function load_webhooks() {
		$webhook_dir = __DIR__ . '/email_webhooks';
		foreach ( glob( $webhook_dir . '/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
				continue;
			}
			require_once( $_field_filename );
		}

		do_action( 'bwfcrm_api_email_webhooks_loaded' );
	}

	public function register( $webhook_class ) {
		if ( ! class_exists( $webhook_class ) || ! method_exists( $webhook_class, 'get_instance' ) ) {
			return;
		}

		if ( empty( $webhook_class ) ) {
			return;
		}

		$slug = strtolower( $webhook_class );
		if ( false === strpos( $slug, 'bwfcrm_webhook_' ) ) {
			return;
		}

		$slug = explode( 'bwfcrm_webhook_', $slug )[1];
		/** @var BWFCRM_Email_Webhook_Base $api_obj */
		$webhook = $webhook_class::get_instance();

		$this->_webhooks[ $slug ] = $webhook;
	}

	public function register_routes() {
		register_rest_route( BWFCRM_WEBHOOK_NAMESPACE, '/emails/(?P<slug>[a-zA-Z0-9-]+)/(?P<key>[a-zA-Z0-9-]+)', [
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true'
			),
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true'
			)
		] );
	}

	public function handle_webhook( WP_REST_Request $request ) {
		BWFAN_PRO_Common::nocache_headers();
		$query_params   = $request->get_query_params();
		$query_params   = is_array( $query_params ) ? $query_params : array();
		$request_params = $request->get_params();
		$request_params = is_array( $request_params ) ? $request_params : array();
		$json_params    = $request->get_json_params();
		$json_params    = is_array( $json_params ) ? $json_params : array();
		$params         = array_replace( $query_params, $request_params );
		$params         = array_replace( $params, $json_params );

		$unique_key = get_option( 'bwfan_u_key', 0 );
		if ( ! isset( $params['key'] ) || empty( $unique_key ) || $unique_key !== $params['key'] ) {
			BWFAN_Core()->logger->log( 'Email Webhook prevented: Invalid unique key', 'crm_email_webhooks' );

			wp_send_json_error( [
				'message' => 'Invalid Key.'
			] );
		}

		if ( ! isset( $params['slug'] ) || ! isset( $this->_webhooks[ $params['slug'] ] ) ) {
			$slug = isset( $params['slug'] ) && ! empty( $params['slug'] ) ? $params['slug'] : '';
			BWFAN_Core()->logger->log( "Email Webhook prevented: Webhook with slug doesn't exists. $slug", 'crm_email_webhooks' );

			wp_send_json_error( [
				'message' => 'No Such Email Provider exists: ' . $params['slug']
			] );
		}

		$webhook = $this->_webhooks[ $params['slug'] ];
		if ( ! $webhook instanceof BWFCRM_Email_Webhook_Base ) {
			$class = get_class( $webhook );
			BWFAN_Core()->logger->log( "Email Webhook prevented: Invalid Webhook Class. $class", 'crm_email_webhooks' );
			wp_send_json_error( [
				'message' => 'Internal Email Webhook Error'
			] );

			return;
		}

		if ( 'amazonses' === $params['slug'] ) {
			$posted_data = file_get_contents( 'php://input' );

			if ( is_wp_error( $posted_data ) ) {
				BWFAN_Core()->logger->log( "No posted data for amazonses", 'crm_email_webhooks' );
				wp_send_json_error( [
					'message' => 'No data found'
				] );

				return;
			}
			$params       = json_decode( $posted_data, true );
			$message_data = is_array( $params ) && ! empty( $params['Message'] ) ? json_decode( $params['Message'], true ) : array();
			if ( ! empty( $message_data ) && is_array( $message_data ) ) {
				unset( $params['Message'] );
				$params = array_merge( $params, $message_data );
			}
		}

		/** Log webhook Request - Dev only */
		if ( ( defined( 'BWFAN_Email_Bounce_Log_Data' ) && true === BWFAN_Email_Bounce_Log_Data ) || true === apply_filters( 'bwfan_email_bounce_log_data', BWFAN_PRO_Common::is_log_enabled( 'bwfan_email_bounce_logging' ), $params ) ) {
			BWFAN_Common::log_test_data( 'Email bounce webhook data for service: ' . $params['slug'], 'fka-email-webhook-request', true );
			BWFAN_Common::log_test_data( $params, 'fka-email-webhook-request', true );
		}

		$webhook->set_data( $params );
		$webhook->handle_webhook();

		wp_send_json( [
			'message' => 'Email webhook request submitted'
		], 200 );
	}

	public function get_webhooks() {
		$webhooks   = [];
		$unique_key = get_option( 'bwfan_u_key', 0 );
		$namespace  = BWFCRM_WEBHOOK_NAMESPACE;
		/**
		 * @var string $webhook
		 * @var BWFCRM_Email_Webhook_Base $webhook_obj
		 */
		foreach ( $this->_webhooks as $webhook => $webhook_obj ) {
			$webhooks[ $webhook ] = [
				'name' => $webhook_obj->get_name(),
				'link' => add_query_arg( array(), rest_url( "$namespace/emails/$webhook/$unique_key" ) )
			];
		}

		return $webhooks;
	}
}

if ( class_exists( 'BWFCRM_Email_Webhook_Handler' ) ) {
	BWFCRM_Core::register( 'email_webhooks', 'BWFCRM_Email_Webhook_Handler' );
}
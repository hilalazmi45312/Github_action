<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[AllowDynamicProperties]
final class BWFCRM_Core {

	/**
	 * @var BWFCRM_Core
	 */
	public static $_instance = null;

	private static $_registered_entity = array(
		'active'   => array(),
		'inactive' => array(),
	);

	/**
	 * @var BWFCRM_Tag
	 */
	public $tags;

	/**
	 * @var BWFCRM_Lists
	 */
	public $lists;

	/**
	 * @var BWFCRM_API_Loader
	 */
	public $api;

	/**
	 * @var BWFCRM_Admin
	 */
	public $admin;

	/**
	 * @var BWFCRM_Importer
	 */
	public $importer;

	/**
	 * @var BWFCRM_Public
	 */
	public $public;

	/**
	 * @var BWFCRM_Campaigns
	 */
	public $campaigns;

	/**
	 * @var BWFCRM_Merge_Tags
	 */
	public $merge_tags;

	/**
	 * @var BWFCRM_Conversation
	 */
	public $conversation;

	/**
	 * @var BWFCRM_SMS
	 */
	public $sms;

	/**
	 * @var BWFCRM_Form_Controller
	 */
	public $forms;

	/**
	 * @var BWFCRM_Email_Webhook_Handler
	 */
	public $email_webhooks;

	/**
	 * @var BWFCRM_Integrations
	 */
	public $integrations;

	/**
	 * @var BWFCRM_Email_Editor_Controller
	 */
	public $email_editor;

	/**
	 * @var BWFCRM_Custom_Filters_Controller
	 */
	public $custom_filters;

	/**
	 * @var BWFCRM_Link_Trigger_Handler
	 */
	public $link_trigger_handler;

	/**
	 * @var BWFAN\Rest_API\Response_Handler
	 */
	public $response_handler;

	public $ap;
	public $exporter;
	/**
	 * @var BWFCRM_Actions_Handler
	 */
	public $actions;
	public $calls;
	public $bulk_action;

	private function __construct() {

		add_action( 'bwfan_loaded', [ $this, 'init_crm' ], 11 );
		add_action( 'bwfan_before_automations_loaded', [ $this, 'add_modules' ] );
		add_action( 'bwfan_exporters_loaded', [ $this, 'add_exporters' ] );
		// adding localized data
		add_action( 'bwfan_admin_view_localize_data', array( $this, 'add_admin_view_localize_data' ), 10, 1 );
	}

	public function init_crm() {
		$this->define_plugin_properties();
		$this->register_abstract();
		$this->core();
		$this->load_dependencies_support();
		$this->register_classes();
	}

	/**
	 * Defining constants
	 */
	public function define_plugin_properties() {
		define( 'BWFCRM_PLUGIN_FILE', __FILE__ );
		define( 'BWFCRM_PLUGIN_DIR', __DIR__ );
		define( 'BWFCRM_PLUGIN_URL', untrailingslashit( plugin_dir_url( BWFCRM_PLUGIN_FILE ) ) );
		define( 'BWFCRM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		define( 'BWFCRM_ENCODE', sha1( BWFCRM_PLUGIN_BASENAME ) );
		define( 'BWFCRM_WEBHOOK_NAMESPACE', 'autonami-webhook' );

		define( 'BWFCRM_IMPORT_DIR_BACKUP', WP_CONTENT_DIR . '/uploads/woofunnels/autonami-import' );
		define( 'BWFCRM_IMPORT_DIR', wp_upload_dir()['basedir'] . '/funnelkit/fka-import' );

		define( 'BWFCRM_EXPORT_DIR_BACKUP', WP_CONTENT_DIR . '/uploads/woofunnels-upload/autonami-export' );
		define( 'BWFCRM_EXPORT_DIR', wp_upload_dir()['basedir'] . '/funnelkit/fka-export' );

		define( 'BWFCRM_BULK_ACTION_LOG_DIR_BACKUP', WP_CONTENT_DIR . '/uploads/woofunnels-upload/bulk-action-log' );
		define( 'BWFCRM_BULK_ACTION_LOG_DIR', wp_upload_dir()['basedir'] . '/funnelkit/fka-bulk-action-log' );

		define( 'BWFCRM_EXPORT_URL_BACKUP', site_url( 'wp-content/uploads/woofunnels-upload/autonami-export/' ) );
		define( 'BWFCRM_EXPORT_URL', site_url( wp_upload_dir()['baseurl'] . '/funnelkit/fka-export/' ) );

		define( 'BWFCRM_PUBLIC_DIR', BWFCRM_PLUGIN_DIR . '/public' );
		define( 'BWFCRM_PUBLIC_URL', BWFCRM_PLUGIN_URL . '/public' );
		if ( ! defined( 'BWFAN_REST_API_NAMESPACE' ) ) {
			define( 'BWFAN_REST_API_NAMESPACE', 'funnelkit-automations' );
		}

		( ! defined( 'BWFCRM_REACT_ENVIRONMENT' ) ) ? define( 'BWFCRM_REACT_ENVIRONMENT', 1 ) : '';
		define( 'BWFCRM_REACT_PROD_URL', BWFCRM_PLUGIN_URL . '/admin/frontend/dist' );
		( 1 === BWFCRM_REACT_ENVIRONMENT ) ? define( 'BWFCRM_VERSION_DEV', BWFAN_PRO_VERSION ) : define( 'BWFCRM_VERSION_DEV', time() );
	}

	/**
	 * Setting up event Dependency Classes
	 */
	public function load_dependencies_support() {
		require BWFCRM_PLUGIN_DIR . '/includes/bwfcrm-functions.php';
		require BWFCRM_PLUGIN_DIR . '/includes/class-bwfcrm-plugin-dependency.php';
	}


	/**
	 *  register all the files
	 */
	public function add_modules() {
		$integration_dir = BWFAN_PRO_PLUGIN_DIR . '/autonami';
		foreach ( glob( $integration_dir . '/class-*.php' ) as $_field_filename ) {
			if ( strpos( $_field_filename, 'index.php' ) !== false ) {
				continue;
			}
			require_once( $_field_filename );
		}
		do_action( 'bwfan_bwfcrm_integrations_loaded', $this );
	}

	private function core() {
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-filters.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-automations.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-api-loader.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-importer.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-exporter.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-merge-tags.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-sms.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-campaigns.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-dashboards.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-reports.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-form-feed.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-forms.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-email-webhook-handler.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-integrations.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-email-editor.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-common.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-audience.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-actions.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-custom-filters.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-link-trigger-handler.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-calls.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-bulk-action-handler.php';
		require BWFAN_PRO_PLUGIN_DIR . '/public/class-bwfcrm-public.php';

		if ( ! BWFAN_PRO_Common::is_lite_3_0() ) {
			require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-contacts.php';
			require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-lists.php';
			require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-fields.php';
			require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-tag.php';
			require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-funnels.php';
			require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-group.php';
			require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-notes.php';
		}
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-templates.php';
		require BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-conversation.php';

		/** Rest APIs classes */
		include BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfan-rest-api-loader.php';
		include BWFAN_PRO_PLUGIN_DIR . '/crm/includes/controllers/class-bwfan-rest-api-response-handler.php';
		include BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfan-rest-api-common.php';

		/** Transactional mail loader */
		if( bwfan_is_woocommerce_active() ) {
			include BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-transactional-mail-loader.php';
		}
	}

	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	public function register_classes() {
		$load_classes = self::get_registered_class();
		if ( is_array( $load_classes ) && count( $load_classes ) > 0 ) {
			foreach ( $load_classes as $access_key => $class ) {
				if ( ! method_exists( $class, 'get_instance' ) ) {
					continue;
				}
				$this->{$access_key} = $class::get_instance();
			}
			do_action( 'bwfcrm_loaded' );
		}
	}

	public static function get_registered_class() {
		return self::$_registered_entity['active'];
	}

	public static function register( $short_name, $class, $overrides = null ) {
		/** Ignore classes that have been marked as inactive */
		if ( in_array( $class, self::$_registered_entity['inactive'], true ) ) {
			return;
		}

		/** Mark classes as active. Override existing active classes if they are supposed to be overridden */
		$index = array_search( $overrides, self::$_registered_entity['active'], true );
		if ( false !== $index ) {
			self::$_registered_entity['active'][ $index ] = $class;
		} else {
			self::$_registered_entity['active'][ $short_name ] = $class;
		}

		/** Mark overridden classes as inactive. */
		if ( ! empty( $overrides ) ) {
			self::$_registered_entity['inactive'][] = $overrides;
		}
	}

	/**
	 *
	 * function to register all the abstract classes
	 **/
	private function register_abstract() {
		$abstract_path = BWFAN_PRO_PLUGIN_DIR . '/crm/includes/abstracts';
		foreach ( glob( $abstract_path . '/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
				continue;
			}
			require_once( $_field_filename );
		}
	}

	/**
	 * Load exporters
	 *
	 * @return void
	 */
	public function add_exporters() {
		$exporter_dir = BWFAN_PRO_PLUGIN_DIR . '/crm/includes/exporters';
		foreach ( glob( $exporter_dir . '/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
				continue;
			}
			require_once( $_field_filename );
		}

		do_action( 'bwfcrm_exporters_loaded' );
	}

	/**
	 * adding public api active status
	 *
	 * @param BWFCRM_Base_React_Page $bwfcrm_brp_obj
	 *
	 * @return void
	 */
	public function add_admin_view_localize_data( $bwfcrm_brp_obj ) {
		$bwfcrm_brp_obj->page_data['localize_texts'] = [
			1 => [
				'notice' => [
					'text'           => __('Your FunnelKit Automation Pro license is not activated!', 'wp-marketing-automations-pro'),
					'primary_action' => __('Activate License','wp-marketing-automations-pro'),
				],
			],
			2 => [
				'notice' => [
					'text' => __('<strong>FunnelKit Automation Pro is Not Fully Activated!</strong> Please activate your license to continue using premium features without interruption.', 'wp-marketing-automations-pro'),
					'primary_action' => 'Activate License',
				],
				'modal'  => [
					'title'           => __( 'FunnelKit Automation PRO is not fully Activated', 'wp-marketing-automations-pro' ),
					'featureDesc'     => __( 'Without an active license your checkout is not affected. However, you are missing on', 'wp-marketing-automations-pro' ),
					'benefits'        => [
						0 => __( 'New revenue boosting features', 'wp-marketing-automations-pro' ),
						1 => __( 'Critical security updates', 'wp-marketing-automations-pro' ),
						2 => __( 'Access to premium features', 'wp-marketing-automations-pro' ),
						3 => __( 'Access to dedicated support', 'wp-marketing-automations-pro' ),
					],
					'text_before_cta' => __( 'Don\'t miss out on the additional revenue. This problem is easy to fix.', 'wp-marketing-automations-pro' ),
					'primary_action'  => __( 'Activate License', 'wp-marketing-automations-pro' ),
				],
			],
			3 => [
				'notice' => [
					'text'             => __( '<strong>Your FunnelKit Automation Pro license has expired!</strong> We\'ve extended its features until {{TIME_GRACE_EXPIRED}}, after which they\'ll be limited.', 'wp-marketing-automations-pro' ),
					'primary_action'   => __( 'Renew Now', 'wp-marketing-automations-pro' ),
					'secondary_action' => __( 'I have My License Key', 'wp-marketing-automations-pro' ),
				],
			],
			4 => [
				'notice' => [
					'text'             => __( '<strong>Your FunnelKit Automation Pro license has expired!</strong> Please renew your license to continue using premium features without interruption.', 'wp-marketing-automations-pro' ),
					'primary_action'   => __( 'Renew Now', 'wp-marketing-automations-pro' ),
					'secondary_action' => __( 'I have My License Key', 'wp-marketing-automations-pro' ),
				],
				'modal'  => [
					'title'            => __( 'Your License has Expired', 'wp-marketing-automations-pro' ),
					'featureDesc'      => __( 'Without an active license your checkout is not affected. However, you are missing on', 'wp-marketing-automations-pro' ),
					'benefits'         => [
						0 => __( 'New revenue boosting features', 'wp-marketing-automations-pro' ),
						1 => __( 'Critical security updates', 'wp-marketing-automations-pro' ),
						2 => __( 'Access to premium features', 'wp-marketing-automations-pro' ),
						3 => __( 'Access to dedicated support', 'wp-marketing-automations-pro' ),
					],
					'text_before_cta'  => __( 'Don\'t miss out on the additional revenue. This problem is easy to fix.', 'wp-marketing-automations-pro' ),
					'primary_action'   => __( 'Renew Now', 'wp-marketing-automations-pro' ),
					'secondary_action' => __( 'I have My License Key', 'wp-marketing-automations-pro' ),
				],
			],
		];
		$bwfcrm_brp_obj->page_data['social_icon_url'] = 'https://d241ue3yrqqwem.cloudfront.net/social-icons/';

		if ( version_compare( BWFAN_VERSION, '3.5.4', '<') ) {
			$bwfcrm_brp_obj->page_data['bwfan_nonce'] = get_option( 'bwfan_unique_secret', '' );
		}
	}

}

if ( ! function_exists( 'BWFCRM_Core' ) ) {

	/**
	 * Global Common function to load all the classes
	 * @return BWFCRM_Core
	 */
	function BWFCRM_Core() {  //@codingStandardsIgnoreLine
		return BWFCRM_Core::get_instance();
	}
}

BWFCRM_Core();

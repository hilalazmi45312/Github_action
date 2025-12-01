<?php
defined( 'ABSPATH' ) || exit; //Exit if accessed directly

/**
 * Class to initiate public interfaces & functionality
 * Class BWFCRM_Public
 */
class BWFCRM_Public {
	private static $ins = null;

	private $BWFAN_PUBLIC_VIEWS = BWFAN_PRO_PUBLIC_DIR . '/views';

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		if ( ! isset( $_GET['bwfcrm'] ) ) {
			return;
		}

		add_filter( 'template_include', [ $this, 'include_public_template' ], 9999 );
		$this->enqueue_scripts();
	}

	public function include_public_template() {
		return $this->BWFAN_PUBLIC_VIEWS . '/bwfcrm-public-app.php';
	}

	public function enqueue_scripts() {
//		$dir = ( 0 === BWFCRM_REACT_ENVIRONMENT ) ? BWFCRM_REACT_DEV_URL : BWFCRM_REACT_PROD_URL;
//		wp_enqueue_script( 'bwfcrm-public', $dir . '/public.js', array( 'wp-element', 'wp-components', 'jquery' ), '1.0.0', true );
		wp_enqueue_style( 'wp-components' );
	}
}

if ( class_exists( 'BWFCRM_Public' ) ) {
	BWFCRM_Core::register( 'public', 'BWFCRM_Public' );
}
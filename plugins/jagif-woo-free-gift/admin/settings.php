<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIJAGIF_WOO_FREE_GIFT_Admin_Settings {
	static $params;

	public function __construct() {
		add_action( 'admin_init', array( $this, 'save_meta_boxes' ), 99 );
	}

	public function save_meta_boxes() {
		$page = isset( $_REQUEST['page'] ) ? wc_clean( wp_unslash( $_REQUEST['page'] ) ) : '';
		if ( $page !== 'woo-free-gift-settings' ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['_jagif-woo-free-gift_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_jagif-woo-free-gift_nonce'] ) ), 'jagif-woo-free-gift_settings' ) ) {
			return;
		}
		global $jagif_settings;

		$args                       = array();
		$args['enable']             = ! empty( $_POST['jagif_woo_free_gift_params']['enable'] ) ? sanitize_text_field( wp_unslash( $_POST['jagif_woo_free_gift_params']['enable'] ) ) : 0;
		$args['overall_notice']     = ! empty( $_POST['jagif_woo_free_gift_params']['overall_notice'] ) ? sanitize_text_field( wp_unslash( $_POST['jagif_woo_free_gift_params']['overall_notice'] ) ) : 0;
		$args['override_type']      = ! empty( $_POST['jagif_woo_free_gift_params']['override_type'] ) ? sanitize_text_field( wp_unslash( $_POST['jagif_woo_free_gift_params']['override_type'] ) ) : 'half';
		$args['cart_notice']        = ! empty( $_POST['jagif_woo_free_gift_params']['cart_notice'] ) ? sanitize_text_field( wp_unslash( $_POST['jagif_woo_free_gift_params']['cart_notice'] ) ) : 0;
		$args['enable_link_gift']   = ! empty( $_POST['jagif_woo_free_gift_params']['enable_link_gift'] ) ? sanitize_text_field( wp_unslash( $_POST['jagif_woo_free_gift_params']['enable_link_gift'] ) ) : 0;
		if ( isset( $_POST['jagif-save-settings'] ) ) {
			$args                       = wp_parse_args( $args, get_option( 'jagif_woo_free_gift_params', $jagif_settings ) );
			$jagif_settings             = $args;
			update_option( 'jagif_woo_free_gift_params', $args );

			add_action( 'admin_notices', function () {
				?>
                <div class="updated">
                    <p><?php esc_html_e( 'Your settings have been saved!', 'jagif-woo-free-gift' ) ?></p>
                </div>
				<?php
			} );
		}
	}

	protected static function set_nonce() {
		return wp_nonce_field( 'jagif-woo-free-gift_settings', '_jagif-woo-free-gift_nonce' );
	}

	/**
	 * Set field in meta box
	 *
	 * @param      $field
	 * @param bool $multi
	 *
	 * @return string
	 */
	protected static function set_field( $field, $multi = false ) {
		if ( $field ) {
			if ( $multi ) {
				return 'jagif_woo_free_gift_params[' . $field . '][]';
			} else {
				return 'jagif_woo_free_gift_params[' . $field . ']';
			}
		} else {
			return '';
		}
	}

	/**
	 * Get Post Meta
	 *
	 * @param $field
	 *
	 * @param string $default
	 *
	 * @return bool
	 */
	public static function get_field( $field, $default = '' ) {
		global $jagif_settings;
		$params = $jagif_settings;

		if ( self::$params ) {
			$params = self::$params;
		} else {
			self::$params = $params;
		}
		if ( isset( $params[ $field ] ) && $field ) {
			return $params[ $field ];
		} else {
			return $default;
		}
	}

	public static function enqueue_style( $handles = array(), $srcs = array(), $des = array(), $type = 'enqueue' ) {
		if ( empty( $handles ) || empty( $srcs ) ) {
			return;
		}
		$action = $type === 'enqueue' ? 'wp_enqueue_style' : 'wp_register_style';
		foreach ( $handles as $i => $handle ) {
			if ( ! $handle || empty( $srcs[ $i ] ) ) {
				continue;
			}
			$action( $handle, VIJAGIF_WOO_FREE_GIFT_CSS . $srcs[ $i ], ! empty( $des[ $i ] ) ? $des[ $i ] : array(), VIJAGIF_WOO_FREE_GIFT_EXTENSION_VERSION );
		}
	}

	public static function enqueue_script( $handles = array(), $srcs = array(), $des = array(), $type = 'enqueue', $in_footer = false ) {
		if ( empty( $handles ) || empty( $srcs ) ) {
			return;
		}
		$action = $type === 'register' ? 'wp_register_script' : 'wp_enqueue_script';
		foreach ( $handles as $i => $handle ) {
			if ( ! $handle || empty( $srcs[ $i ] ) ) {
				continue;
			}
			$action( $handle, VIJAGIF_WOO_FREE_GIFT_JS . $srcs[ $i ], ! empty( $des[ $i ] ) ? $des[ $i ] : array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_EXTENSION_VERSION, $in_footer );
		}
	}

	public static function page_callback() {
		self::$params       = get_option( 'jagif_woo_free_gift_params', array() );
		$permalink          = get_permalink( wc_get_page_id( 'shop' ) );
		$link               = get_admin_url() . 'customize.php?url=' . $permalink . '&autofocus%5Bpanel%5D=jagif_design';
		$settings           = VIJAGIF_WOO_FREE_GIFT_DATA::get_instance();
		$enable             = $settings->get_params( 'enable' );
		$enable_overall_notice = $settings->get_params( 'overall_notice' );
		$enable_override_type = $settings->get_params( 'override_type' );
		$enable_cart_notice = $settings->get_params( 'cart_notice' );
		$enable_link_gift   = $settings->get_params( 'enable_link_gift' );
		?>
        <div class="wrap jagif-woo-free-gift vi-ui raised">
        <h2><?php esc_html_e( 'Jagif - Woo Free Gift Settings', 'jagif-woo-free-gift' ) ?></h2>
        <form method="post" action="" class="vi-ui form jagif-general-settings">
			<?php echo ent2ncr( wp_kses_post( self::set_nonce() ) ) ?>
            <div class="vi-ui top attached tabular menu">
                <div class="item active"
                     data-tab="general"><?php esc_html_e( 'General', 'jagif-woo-free-gift' ); ?></div>
            </div>
            <div class="vi-ui bottom attached active tab segment" data-tab="general">
                <table class="vi-ui bottom attached form-table">
                    <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label for="<?php echo esc_attr( self::set_field( 'enable' ) ) ?>">
								<?php esc_html_e( 'Enable', 'jagif-woo-free-gift' ) ?>
                            </label>
                        </th>
                        <td>
                            <div class="vi-ui toggle checkbox">
                                <input id="<?php echo esc_attr( self::set_field( 'enable' ) ); ?>"
                                       type="checkbox" <?php checked( self::get_field( 'enable', $enable ), 1 ); ?>
                                       tabindex="0" value="1"
                                       name="<?php echo esc_attr( self::set_field( 'enable' ) ); ?>"/>
                                <label></label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="woo-stcr-checkout-countdown-id-on-checkout-page"><?php esc_html_e( 'Code flow',
				                    'jagif-woo-free-gift' ) ?></label>
                        </th>
                        <td>
                            <a class="vi-ui button yellow" href="https://1.envato.market/15YMmg" target="_blank">
			                    <?php esc_html_e( 'Unlock This Feature', 'jagif-woo-free-gift' ); ?>
                            </a>
                            <p class="description">
			                    <?php esc_html_e( 'Update code flow to be compatible with some addons, bundle plugins.',
				                    'jagif-woo-free-gift' ) ?>
                            </p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="<?php echo esc_attr( self::set_field( 'enable_link_gift' ) ) ?>">
								<?php esc_html_e( 'Enable link to gift', 'jagif-woo-free-gift' ) ?>
                            </label>
                        </th>
                        <td>
                            <div class="vi-ui toggle checkbox">
                                <input id="<?php echo esc_attr( self::set_field( 'enable' ) ); ?>"
                                       type="checkbox" <?php checked( self::get_field( 'enable_link_gift', $enable_link_gift ), 1 ); ?>
                                       tabindex="0" value="1"
                                       name="<?php echo esc_attr( self::set_field( 'enable_link_gift' ) ); ?>"/>
                                <label></label>
                            </div>
                            <p class="description"><?php esc_html_e( 'Click on the gift title to see the product being gifted.', 'jagif-woo-free-gift' ) ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="<?php echo esc_attr( self::set_field( 'cart_notice' ) ) ?>">
								<?php esc_html_e( 'Enable the gift description on cart', 'jagif-woo-free-gift' ) ?>
                            </label>
                        </th>
                        <td>
                            <div class="vi-ui toggle checkbox">
                                <input id="<?php echo esc_attr( self::set_field( 'enable' ) ); ?>"
                                       type="checkbox" <?php checked( self::get_field( 'cart_notice', $enable_cart_notice ), 1 ); ?>
                                       tabindex="0" value="1"
                                       name="<?php echo esc_attr( self::set_field( 'cart_notice' ) ); ?>"/>
                                <label></label>
                            </div>
                            <p class="description"><?php esc_html_e( 'Show all available gifts.', 'jagif-woo-free-gift' ) ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="<?php echo esc_attr( self::set_field( 'override_type' ) ) ?>">
								<?php esc_html_e( 'Types of overriding rules', 'jagif-woo-free-gift' ) ?>
                            </label>
                        </th>
                        <td class="jagif-override-type-wrap">
                            <select class="jagif-override-gift vi-ui fluid dropdown" id="<?php echo esc_attr( self::set_field( 'override_type' ) ); ?>"
                                    tabindex="0" name="<?php echo esc_attr( self::set_field( 'override_type' ) ); ?>">
                                <option value="part"
                                    <?php selected( self::get_field( 'override_type', $enable_override_type ), 'part' ); ?>>
                                    <?php esc_html_e( 'Use single gift', 'jagif-woo-free-gift' ); ?></option>
                                <option value="half"
                                    <?php selected( self::get_field( 'override_type', $enable_override_type ), 'half' ); ?>>
                                    <?php esc_html_e( 'Use global rule and single gift', 'jagif-woo-free-gift' ); ?></option>
                                <option value="all"
                                    <?php selected( self::get_field( 'override_type', $enable_override_type ), 'all' ); ?>>
                                    <?php esc_html_e( 'Use global rule', 'jagif-woo-free-gift' ); ?></option>
                            </select>
                            <p class="description des-override-part<?php echo $enable_override_type == 'part' ? '' : ' jagif-hidden' ?>">
                                <?php esc_html_e( 'When product have multi gift(single product gift and rule gift) available, single gift will be selected', 'jagif-woo-free-gift' ) ?></p>
                            <p class="description des-override-half<?php echo $enable_override_type == 'half' ? '' : ' jagif-hidden' ?>">
                                <?php esc_html_e( 'When product have multi gift(single product gift and rule gift) available, single gift and gift of rule have highest priority will be selected', 'jagif-woo-free-gift' ) ?></p>
                            <p class="description des-override-all<?php echo $enable_override_type == 'all' ? '' : ' jagif-hidden' ?>">
                                <?php esc_html_e( 'When a product has multi gifts(single product gift and rule gift), gift created by rule which has the highest priority will be selected.', 'jagif-woo-free-gift' ) ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <a href="<?php echo esc_url( $link ) ?>"
                               target="_blank"><?php esc_html_e( 'Go to Customize', 'jagif-woo-free-gift' ); ?></a>
                        </th>
                    </tr>
                    </tbody>
                </table>
            </div>
            <p>
                <button class="vi-ui button labeled icon primary" name="jagif-save-settings">
                    <i class="send icon"></i> <?php esc_html_e( 'Save', 'jagif-woo-free-gift' ) ?>
                </button>
            </p>
        </form>
		<?php
		do_action( 'villatheme_support_jagif-woo-free-gift' );
	}
}
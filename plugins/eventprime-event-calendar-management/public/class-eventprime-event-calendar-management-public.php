<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://theeventprime.com
 * @since      1.0.0
 *
 * @package    Eventprime_Event_Calendar_Management
 * @subpackage Eventprime_Event_Calendar_Management/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Eventprime_Event_Calendar_Management
 * @subpackage Eventprime_Event_Calendar_Management/public
 * @author     EventPrime <support@metagauss.com>
 */
class Eventprime_Event_Calendar_Management_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
        
        private $ep_theme;
        
        
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
                

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Eventprime_Event_Calendar_Management_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Eventprime_Event_Calendar_Management_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/eventprime-event-calendar-management-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Eventprime_Event_Calendar_Management_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Eventprime_Event_Calendar_Management_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/eventprime-event-calendar-management-public.js', array( 'jquery' ), $this->version, false );
                $this->ep_enqueues();
	}
        
        public function register_shortcodes() {
        $shortcodes = array(
            'em_events' => array($this, 'load_events'),
            'em_performers' => array($this, 'load_performers'),
            'em_performer' => array($this, 'load_single_performer'),
            'em_event_organizers' => array($this, 'load_event_organizers'),
            'em_event_organizer' => array($this, 'load_single_event_organizer'),
            'em_event_types' => array($this, 'load_event_types'),
            'em_event_type' => array($this, 'load_single_event_type'),
            'em_sites' => array($this, 'load_venues'),
            'em_event_site' => array($this, 'load_single_venue'),
            'em_profile' => array($this, 'load_profile'),
            'em_login' => array($this, 'load_login'),
            'em_register' => array($this, 'load_register'),
            'em_event_submit_form' => array($this, 'load_event_submit_form'),
            'em_booking' => array($this, 'load_booking'),
            'em_booking_details' => array($this, 'load_event_booking_details'),
            'em_event' => array($this, 'load_single_event'),
            'em_gdpr_badge' => array($this, 'load_gdpr_badge'),
            /*'em_sponsors' => array($this, 'load_sponsors'),
            'em_sponsor' => array($this, 'load_single_sponsor'),*/
        );

        foreach ($shortcodes as $shortcode => $function) {
            add_shortcode(apply_filters("{$shortcode}_shortcode", $shortcode), $function);
        }
    }
    
    /**
     * Display all events
     * 
     * @param array $atts Attributes.
     * @return string
     */
    
    public function ep_enqueues() {
        wp_register_style(
			'ep-user-select2-css',
			plugin_dir_url(__FILE__) . 'css/select2.min.css',
			false, $this->version
		);
	
        wp_register_style(
            'ep-user-views-custom-css',
            plugin_dir_url(__FILE__) . 'css/ep-user-views.css',
            false, $this->version
        );

        wp_register_script(
            'ep-user-select2-js',
            plugin_dir_url(__FILE__) . 'js/select2.full.min.js',
            array( 'jquery' ), $this->version
		);
        wp_register_script(
            'ep-user-views-js',
            plugin_dir_url(__FILE__) . 'js/ep-user-custom.js',
            array( 'jquery' ), $this->version
        );
        
        wp_enqueue_style('ep-public-css',plugin_dir_url(__FILE__) . 'css/em-front-common-utility.css',false, $this->version);
        wp_enqueue_style('ep-material-fonts',plugin_dir_url(__FILE__) . 'css/ep-material-fonts-icon.css',array(), $this->version);
        wp_enqueue_style('ep-toast-css',plugin_dir_url(__FILE__) . 'css/jquery.toast.min.css',false, $this->version);
        wp_enqueue_script('ep-toast-js',plugin_dir_url(__FILE__) . 'js/jquery.toast.min.js',array('jquery'), $this->version);
        wp_enqueue_script('ep-toast-message-js',plugin_dir_url(__FILE__) . 'js/toast-message.js',array('jquery'), $this->version);

        wp_localize_script(
            'ep-toast-message-js', 
            'eventprime_toast', 
            array(
               'error'=> esc_html__( 'Error', 'eventprime-event-calendar-management' ),
               'success'=> esc_html__( 'Success', 'eventprime-event-calendar-management' ),
               'warning'=> esc_html__( 'Warning', 'eventprime-event-calendar-management' ),
            )
        );

        
        $ep_functions = new Eventprime_Basic_Functions;
        wp_enqueue_style('em-front-common-utility', plugin_dir_url( __FILE__ ) . 'css/em-front-common-utility.css', array(), $this->version, 'all' );
        wp_enqueue_script('ep-common-script', plugin_dir_url(__FILE__) . 'js/ep-common-script.js', array('jquery'), $this->version);
            // localized global settings
            $global_settings = $ep_functions->ep_get_global_settings();
            $currency_symbol = $ep_functions->ep_currency_symbol();
            $datepicker_format = $ep_functions->ep_get_datepicker_format( 2 );
            wp_localize_script(
            'ep-common-script', 
            'eventprime', 
            array(
                'global_settings'      => $global_settings,
                'currency_symbol'      => $currency_symbol,
                'ajaxurl'              => admin_url('admin-ajax.php'),
                'trans_obj'            => $ep_functions->ep_define_common_field_errors(),
                'event_wishlist_nonce' => wp_create_nonce( 'event-wishlist-action-nonce' ),
                'security_nonce_failed'=> esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ),
                'datepicker_format'    => $datepicker_format,
                'timezone' => $ep_functions->ep_get_site_timezone()
            )
        );
        wp_localize_script(
            'ep-user-views-js', 
            'ep_frontend', 
            array(
                '_nonce'                => wp_create_nonce( 'ep-frontend-nonce' ),
                'ajaxurl'               => admin_url( 'admin-ajax.php' ),
                'nonce_error'           => esc_html__( 'Please refresh the page and try again.', 'eventprime-event-calendar-management' ),
                'delete_event_confirm'  => esc_html__( 'Are you sure you want to delete this event?', 'eventprime-event-calendar-management' )
            )
        );
            
            wp_localize_script(
            'ep-common-script', 
            'eventprime_obj', 
            array(
                'global_settings'      => $global_settings,
                'currency_symbol'      => $currency_symbol,
                'ajaxurl'              => admin_url('admin-ajax.php'),
                'trans_obj'            => $ep_functions->ep_define_common_field_errors(),
                'event_wishlist_nonce' => wp_create_nonce( 'event-wishlist-action-nonce' ),
                'security_nonce_failed'=> esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ),
                'datepicker_format'    => $datepicker_format
            )
        );
            
        

        wp_register_style(
            'ep-responsive-slides-css',
             plugin_dir_url(__FILE__) . 'css/responsiveslides.css',
            false, $this->version
        );
        wp_register_script(
            'ep-responsive-slides-js',
             plugin_dir_url(__FILE__) . 'js/responsiveslides.min.js',
            array( 'jquery' ), $this->version
        );
        
//        if (! empty( $global_settings->enable_gdpr_tools ) && ! empty( $global_settings->enable_cookie_consent_banner )) 
//        {
//            wp_enqueue_script('ep-cookie-consent',plugin_dir_url(__FILE__) . 'js/ep-cookie-consent.js',array('jquery'),$this->version,true);
//            wp_localize_script( 'ep-cookie-consent', 'ep_cookie_consent_data', array(
//                'message' => ! empty( $global_settings->cookie_consent_message ) ? $global_settings->cookie_consent_message : __( 'We use cookies to ensure you get the best experience on our website.', 'eventprime-event-calendar-management' ),
//                'button' => ! empty( $global_settings->cookie_consent_button_text ) ? $global_settings->cookie_consent_button_text : __( 'Accept', 'eventprime-event-calendar-management' ),
//                'privacy_url' => ! empty( $global_settings->gdpr_privacy_policy_url )? esc_url( $global_settings->gdpr_privacy_policy_url ) : esc_url( get_privacy_policy_url() ),
//            ));
//        }
        
    }
    
    public function eventprime_get_template_html($template_name, $atts, $content = null) {
        if (!$content) {
            $content = array();
        }
        ob_start();
        
        do_action('eventprime_before_' . $template_name, $template_name, $atts);
        include 'partials/' . $template_name . '.php';
        do_action('eventprime_after_' . $template_name);
        
        $html = ob_get_contents();
        if (ob_get_length()) {
            ob_end_clean();
        }
        return $html;
    }
    
    //maybe deprecated
    public function ep_get_template_part($slug, $name = null, $data = array(), $ext_path = null) {
        $file = '';
        if (isset($name)) {
            $template = $slug . '-' . $name . '.php';
            // check file in yourtheme/eventprime
            $file = locate_template(['eventprime/' . $template], false, false);

            if (!$file) {
                if (!empty($ext_path)) {
                    $file = $ext_path . "/views/" . $template;
                } else {
                    
                    $file = plugin_dir_path(__FILE__) . "/partials/" . $template;
                }
            }
        }

        if (!$file) {
            $template = $slug . '.php';
            // check file in yourtheme/eventprime
            $file = locate_template(['eventprime/' . $template], false, false);

            if (!$file) {
                if (!empty($ext_path)) {
                    $file = $ext_path . "/views/" . $template;
                } else {
                    
                    $file = plugin_dir_path(__FILE__) . "partials/" . $template;
                }
            }
        }

        // Allow 3rd party plugins to filter template file from their plugin.
        $file = apply_filters('ep_get_template_part', $file, $slug, $name);

        if ($file) {
            load_template($file, false, $data);
        }
    }

    public function load_gdpr_badge()
    {
        include 'partials/eventprime-gdpr-badge.php';
    }
    
    public function load_single_event($atts)
    {
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
	if(isset($atts['id']))
        {
            $template = 'eventprime-event';
            return $this->eventprime_get_template_html($template, $atts);
        }
        else
        {
            ob_start();
            ?>
            <div class="ep-alert ep-alert-warning ep-mt-3">
                <?php echo esc_html_e( 'No event found.', 'eventprime-event-calendar-management' ); ?>
            </div>
            <?php
            $html = ob_get_contents();
            if (ob_get_length()) {
                ob_end_clean();
            }
            return $html;
        }
    }
    
    public function ep_load_single_template_dynamic( $content ) {
        static $in_filter = false;

        // If we re-enter because template calls apply_filters('the_content'), let core filters run.
        if ( $in_filter ) {
            return $content;
        }

        if ( is_single() && in_the_loop()) {
            $in_filter = true;
            $atts = array( 'id' => get_the_ID() );

            switch ( get_post_type() ) {
                case 'em_event':
                    $content = $this->load_single_event( $atts );
                    break;

                case 'em_performer':
                    $content = $this->load_single_performer( $atts );
                    break;

                case 'em_sponsor':
                    $ep_functions = new Eventprime_Basic_Functions;
                    $extensions = $ep_functions->ep_get_activate_extensions();
                    if ( ! empty( $extensions ) && in_array( 'Eventprime_Event_Sponsor', $extensions, true ) ) {
                        $sponsors = new Eventprime_Event_Sponsor_Public( $this->plugin_name, $this->version );
                        $content  = $sponsors->load_single_sponsor( $atts );
                    }
                    break;
            }

            $in_filter = false;
        }

        return $content;
    }

    
    public function load_events($atts)
    {
        $event = get_query_var('event');
        if(!$event){
            if(!empty(filter_input(INPUT_GET, 'event'))){
                $event = rtrim(filter_input(INPUT_GET, 'event'),'/\\');
            }
        }
        $atts = array_change_key_case((array) $atts, CASE_LOWER);
        if (isset($_GET['event']) && !empty($_GET['event']) || isset($atts['id']) && !empty($atts['id']) && !isset($atts['view'])) {
            if (!empty($atts['id'])) {
                if (strpos($atts['id'], ',') !== false) {
                    $atts['id'] = explode(',', $atts['id']);
                    
                   $template = 'eventprime-events';
                }
                else
                {
                   $event_id = absint($atts['id']);
                   $atts['id'] = $event_id;
                   $template = 'eventprime-event';
                }
                
            }
            
        }
        else 
        {
            $template = 'eventprime-events';
        }
        
        if(!empty(filter_input(INPUT_GET, 'event'))){
            $event = rtrim(filter_input(INPUT_GET, 'event'),'/\\');
            $atts['id'] = $event;
            $template = 'eventprime-event';
        }
        
        if(is_admin())
        {
            return '';
        }
        
        return $this->eventprime_get_template_html($template, $atts);
    }

    public function load_event_organizers( $atts ) {
        //print_r($_GET);die;
        $organizer = get_query_var('organizer');
        if(!$organizer){
            if(!empty(filter_input(INPUT_GET, 'organizer'))){
                $organizer = rtrim(filter_input(INPUT_GET, 'organizer'),'/\\');
            }
        }
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        if($organizer)
        {
            $ep_basic_functions = new Eventprime_Basic_Functions;
            $atts['id'] = $ep_basic_functions->ep_get_id_by_slug($organizer,'em_event_organizer');
            $template = 'eventprime-organizer';
        }
        else 
        {
            $template = 'eventprime-organizers';
        }
        return $this->eventprime_get_template_html($template, $atts);
    }
    
    public function load_venues($atts)
    {
        
        $venue = get_query_var('venue');
        if(!$venue){
            if(!empty(filter_input(INPUT_GET, 'venue'))){
                $venue = rtrim(filter_input(INPUT_GET, 'venue'),'/\\');
            }
        }
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        if($venue)
        {
            $ep_basic_functions = new Eventprime_Basic_Functions;
            $atts['id'] = $ep_basic_functions->ep_get_id_by_slug($venue,'em_venue');
            $template = 'eventprime-venue';
        }
        else 
        {
            $template = 'eventprime-venues';
        }
        return $this->eventprime_get_template_html($template, $atts);
    }
    
    public function load_single_venue($atts) {
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $template = 'eventprime-venue';
        return $this->eventprime_get_template_html($template, $atts);
    }
    
    public function load_single_event_organizer($atts)
    {
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $template = 'eventprime-organizer';
        return $this->eventprime_get_template_html($template, $atts);
    }
    
    public function load_single_performer($atts)
    {
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $template = 'eventprime-performer';
        return $this->eventprime_get_template_html($template, $atts);
    }
    
    public function load_performers($atts)
    {
        $performer = get_query_var('performer');
        if(!$performer){
            if(!empty(filter_input(INPUT_GET, 'performer'))){
                $performer = rtrim(filter_input(INPUT_GET, 'performer'),'/\\');
            }
        }
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        if($performer)
        {
            $ep_basic_functions = new Eventprime_Basic_Functions;
            $atts['id'] = $ep_basic_functions->ep_get_id_by_slug($performer,'em_performer');
            $template = 'eventprime-performer';
        }
        else 
        {
            $template = 'eventprime-performers';
        }
        
        return $this->eventprime_get_template_html($template, $atts);
    }
    
    public function load_event_types($atts)
    {
        $event_type = get_query_var('event_type');
        if(!$event_type){
            if(!empty(filter_input(INPUT_GET, 'event_type'))){
                $event_type = rtrim(filter_input(INPUT_GET, 'event_type'),'/\\');
            }
        }
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        if($event_type)
        {
            $ep_basic_functions = new Eventprime_Basic_Functions;
            $atts['id'] = $ep_basic_functions->ep_get_id_by_slug($event_type,'em_event_type');
            $template = 'eventprime-event-type';
        }
        else 
        {
            $template = 'eventprime-event-types';
        }
        return $this->eventprime_get_template_html($template, $atts);
    }
    
    public function load_single_event_type($atts)
    {
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $template = 'eventprime-event-type';
        return $this->eventprime_get_template_html($template, $atts);
    }
    
    public function load_booking($atts)
    {
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
            if( isset( $_POST['action'] ) && 'edit_booking' == sanitize_text_field( $_POST['action'] ) ) 
            {
                $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : '';
                $atts['id'] = $order_id;
                $template = 'eventprime-edit-booking';
            }
            else 
            {
                $template = 'eventprime-checkout';
            }
            return $this->eventprime_get_template_html($template, $atts);
        
        
    }
    
    public function load_event_booking_details($atts)
    {
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $template = 'eventprime-booking-detail';
        return $this->eventprime_get_template_html($template, $atts);
    }
    
    public function ep_event_add_event_booking_button( $event ) {
        $settings = new Eventprime_Basic_Functions;
        $global_options = $settings->ep_get_global_settings();

        $em_custom_link = get_post_meta( $event->id, 'em_custom_link', true );
        $url = ( $global_options->redirect_third_party == 1 && $event->em_enable_booking == 'external_bookings' )
            ? esc_url( $em_custom_link )
            : esc_url( $event->event_url );

        if ( $event && ! empty( $event->id ) ) {
            $view_details_text = $settings->ep_global_settings_button_title( 'View Details' );
            $new_tab = ! empty( $settings->ep_get_global_settings( 'open_detail_page_in_new_tab' ) );

            $target_attr = $new_tab ? ' target="_blank" rel="noopener"' : '';

            if ( $settings->check_event_has_expired( $event ) ) { ?>
                <a href="<?php echo esc_url( $event->event_url ); ?>"<?php echo $target_attr; ?>>
                    <div class="ep-btn ep-btn-dark ep-box-w-100 ep-my-0 ep-py-2">
                        <span class="ep-fw-bold ep-text-small"><?php echo esc_html( $view_details_text ); ?></span>
                    </div>
                </a>
            <?php } else {
                if ( ! empty( $event->em_enable_booking ) ) {
                    if ( $event->em_enable_booking === 'bookings_off' ) { ?>
                        <a href="<?php echo esc_url( $event->event_url ); ?>"<?php echo $target_attr; ?>>
                            <div class="ep-btn ep-btn-dark ep-box-w-100 ep-my-0 ep-py-2">
                                <span class="ep-fw-bold ep-text-small"><?php echo esc_html( $view_details_text ); ?></span>
                            </div>
                        </a>
                    <?php } elseif ( $event->em_enable_booking === 'external_bookings' ) {
                        // Respect per-event new-window toggle for external bookings
                        $ext_target = empty( $event->em_custom_link_new_browser ) ? '' : ' target="_blank" rel="noopener"'; ?>
                        <a href="<?php echo esc_url( $url ); ?>"<?php echo $ext_target; ?>>
                            <div class="ep-btn ep-btn-dark ep-box-w-100 ep-my-0 ep-py-2">
                                <span class="ep-fw-bold ep-text-small"><?php echo esc_html( $view_details_text ); ?></span>
                            </div>
                        </a>
                    <?php } else {
                        // Internal bookings: derive status + free/buy label robustly
                        if ( ! empty( $event->all_tickets_data ) ) {
                            $status = $settings->check_for_booking_status( $event->all_tickets_data, $event );
                            if ( ! empty( $status ) ) {
                                if ( $status['status'] === 'not_started' ) { ?>
                                    <div class="ep-btn ep-btn-light ep-box-w-100 ep-my-0 ep-py-2">
                                        <span class="material-icons-outlined ep-align-middle ep-text-muted ep-fs-6">history_toggle_off</span>
                                        <span class="ep-text-muted ep-text-smaller"><em><?php echo esc_html( $status['message'] ); ?></em></span>
                                    </div>
                                <?php } elseif ( $status['status'] === 'off' ) { ?>
                                    <div class="ep-btn ep-btn-light ep-box-w-100 ep-my-0 ep-py-2">
                                        <span class="material-icons-outlined ep-align-middle ep-text-muted ep-fs-6">block</span>
                                        <span class="ep-text-muted ep-text-small"><em><?php echo esc_html( $status['message'] ); ?></em></span>
                                    </div>
                                <?php } else {
                                    // status "on": compute free/buy from price (no string compare)
                                    $tpr = isset( $event->ticket_price_range ) ? $event->ticket_price_range : array();
                                    $multiple = isset( $tpr['multiple'] ) && (int) $tpr['multiple'] === 1;

                                    $min_price = null;
                                    if ( $multiple ) {
                                        if ( isset( $tpr['min'] ) && is_numeric( $tpr['min'] ) ) {
                                            $min_price = (float) $tpr['min'];
                                        }
                                        
                                        if ( isset( $tpr['max'] ) && is_numeric( $tpr['max'] ) ) {
                                            $max_price = (float) $tpr['max'];
                                            if($max_price==0.0)
                                            {
                                                $multiple = false;
                                            }
                                        }
                                        
                                        
                                        
                                    } else {
                                        if ( isset( $tpr['price'] ) && is_numeric( $tpr['price'] ) ) {
                                            $min_price = (float) $tpr['price'];
                                        }
                                    }
                                    $is_free = ( $min_price !== null && $min_price == 0.0 && !$multiple);

                                    $btn_class = $is_free ? 'ep-btn-dark' : 'ep-btn-warning';
                                    $btn_label = $is_free
                                        ? $settings->ep_global_settings_button_title( 'Free' )
                                        : $settings->ep_global_settings_button_title( 'Buy Tickets' );
                                    ?>
                                    <a href="<?php echo esc_url( $event->event_url ); ?>"<?php echo $target_attr; ?>>
                                        <div class="ep-btn <?php echo esc_attr( $btn_class ); ?> ep-box-w-100 ep-my-0 ep-p-2">
                                            <span class="ep-fw-bold ep-text-small"><?php echo $btn_label; ?></span>
                                        </div>
                                    </a>
                                <?php }
                            }
                        } else { ?>
                            <a href="<?php echo esc_url( $event->event_url ); ?>"<?php echo $target_attr; ?>>
                                <div class="ep-btn ep-btn-dark ep-box-w-100 ep-my-0 ep-py-2">
                                    <span class="ep-fw-bold ep-text-small"><?php echo esc_html( $view_details_text ); ?></span>
                                </div>
                            </a>
                        <?php }
                    }
                } else { ?>
                    <a href="<?php echo esc_url( $event->event_url ); ?>"<?php echo $target_attr; ?>>
                        <div class="ep-btn ep-btn-dark ep-box-w-100 ep-my-0 ep-py-2">
                            <span class="ep-fw-bold ep-text-small"><?php echo esc_html( $view_details_text ); ?></span>
                        </div>
                    </a>
                <?php }
            }
        }
    }

    
    public function ep_event_add_event_booking_button_old( $event ) 
    {
        $options = array();
        $settings = new Eventprime_Basic_Functions;
        $global_options = $settings->ep_get_global_settings();
        //$global_options = $options['global'];
        $em_custom_link   = get_post_meta( $event->id, 'em_custom_link', true );
        if ( $global_options->redirect_third_party == 1 && $event->em_enable_booking == 'external_bookings' ) {
            $url = esc_url( $em_custom_link );
        } else {
            $url = esc_url( $event->event_url );
        }
        if( $event && ! empty( $event->id ) ) { 
            $view_details_text = $settings->ep_global_settings_button_title('View Details');
            $new_window = ( ! empty( $settings->ep_get_global_settings( 'open_detail_page_in_new_tab' ) ) ? 'target="_blank"' : '' );
            if( $settings->check_event_has_expired( $event ) ) {
				// means event has ended. So user can only view the event detail.?>
				<a href="<?php echo esc_url( $event->event_url );?>" <?php echo esc_attr( $new_window );?>>
					<div class="ep-btn ep-btn-dark ep-box-w-100 ep-my-0 ep-py-2">
						<span class="ep-fw-bold ep-text-small">
						    <?php echo esc_html( $view_details_text ); ?>
						</span>
					</div>
				</a><?php
			} else{
				if( ! empty( $event->em_enable_booking ) ) {
					if( $event->em_enable_booking == 'bookings_off' ) {?>
                        <a href="<?php echo esc_url( $event->event_url );?>" <?php echo esc_attr( $new_window );?>>
							<div class="ep-btn ep-btn-dark ep-box-w-100 ep-my-0 ep-py-2">
								<span class="ep-fw-bold ep-text-small">
                                    <?php echo esc_html( $view_details_text ); ?>
								</span>
							</div>
						</a><?php
					} elseif( $event->em_enable_booking == 'external_bookings' ) {
						if( empty( $event->em_custom_link_new_browser ) ) {
							$new_window = '';
						}?>
						<a href="<?php echo esc_url($url) ;?>" <?php echo esc_attr( $new_window );?>>
							<div class="ep-btn ep-btn-dark ep-box-w-100 ep-my-0 ep-py-2">
								<span class="ep-fw-bold ep-text-small">
                                    <?php echo esc_html( $view_details_text ); ?>
								</span>
							</div>
						</a><?php
					} else{
						// check for booking status 
						if( ! empty( $event->all_tickets_data ) ) {
							$check_for_booking_status = $settings->check_for_booking_status( $event->all_tickets_data, $event );
							if( ! empty( $check_for_booking_status ) ) {
								if( $check_for_booking_status['status'] == 'not_started' ) {?>
									<div class="ep-btn ep-btn-light ep-box-w-100 ep-my-0 ep-py-2">
										<span class="material-icons-outlined ep-align-middle ep-text-muted ep-fs-6">history_toggle_off</span>
										<span class="ep-text-muted ep-text-smaller"><em>
                                            <?php echo esc_html( $check_for_booking_status['message'] );?>
										</em></span>
									</div><?php
								} elseif( $check_for_booking_status['status'] == 'off' ) {?>
									<div class="ep-btn ep-btn-light ep-box-w-100 ep-my-0 ep-py-2">
										<span class="material-icons-outlined ep-align-middle ep-text-muted ep-fs-6">block</span>
										<span class="ep-text-muted ep-text-small"><em>
                                            <?php echo esc_html( $check_for_booking_status['message'] );?>
										</em></span>
									</div><?php
								} else{?>
									<a href="<?php echo esc_url( $event->event_url );?>" <?php echo esc_attr( $new_window );?>><?php
										if( $check_for_booking_status['message'] == 'Free' ) {?>
											<div class="ep-btn ep-btn-dark ep-box-w-100 ep-my-0 ep-p-2"><?php
										} else{?>
											<div class="ep-btn ep-btn-warning ep-box-w-100 ep-my-0 ep-p-2"><?php
										}?>
											<span class="ep-fw-bold ep-text-small">
                                                <?php echo  esc_html__( $check_for_booking_status['message'], 'eventprime-event-calendar-management' );?>
											</span>
										</div>
									</a><?php
								}
							}
						} else{?>
                            <a href="<?php echo esc_url( $event->event_url );?>" <?php echo esc_attr( $new_window );?>>
                                <div class="ep-btn ep-btn-dark ep-box-w-100 ep-my-0 ep-py-2">
                                    <span class="ep-fw-bold ep-text-small">
                                        <?php echo esc_html( $view_details_text ); ?>
                                    </span>
                                </div>
                            </a><?php
                        }
					}
                } else{?>
                    <a href="<?php echo esc_url( $event->event_url );?>" <?php echo esc_attr( $new_window );?>>
                        <div class="ep-btn ep-btn-dark ep-box-w-100 ep-my-0 ep-py-2">
                            <span class="ep-fw-bold ep-text-small">
                                <?php echo esc_html( $view_details_text ); ?>
                            </span>
                        </div>
                    </a><?php
                }
            }
        }
    }
    
    /**
	 * Load profile template
	 */
	public function load_profile( $atts ) 
        {
            $ep_functions = new Eventprime_Basic_Functions;
            $bookings = new EventPrime_Bookings;
            $args = new stdClass();
            $args->show_register = 0;
            $args->redirect_url = ( ! empty( $ep_functions->ep_get_global_settings( 'login_redirect_after_login' ) ) ) ? get_permalink( $ep_functions->ep_get_global_settings( 'login_redirect_after_login' ) ) : get_permalink( $ep_functions->ep_get_global_settings( 'profile_page' ) );

            if( isset( $_POST['ep_register'] ) ) {
                $args->show_register = 1;
            }

            $args = $this->get_login_options( $args );

            $args = $this->get_register_options( $args );

            if ( ! is_user_logged_in() ) {
                $template = 'eventprime-login';
            }else{
                $args->current_user = wp_get_current_user();
                $args->upcoming_bookings = ( ! empty( $args->current_user->ID ) ) ? $ep_functions->get_user_wise_upcoming_bookings( $args->current_user->ID ) : array();
                $args->all_bookings      = ( ! empty( $args->current_user->ID ) ) ? $bookings->get_user_all_bookings( $args->current_user->ID,false ) : array();
                $args->wishlisted_events = ( ! empty( $args->current_user->ID ) ) ? $ep_functions->get_user_wishlisted_events( $args->current_user->ID ) : array();
                $args->submitted_events  = ( ! empty( $args->current_user->ID ) ) ? $ep_functions->get_user_submitted_events( $args->current_user->ID ) : array();
                $args->booking_events  = ( ! empty( $args->all_bookings ) ) ? $ep_functions->ep_get_bookings_events( $args->all_bookings ) : array();
                $template = 'eventprime-profile';
                //print_r($args->all_bookings);die;
            }
            return $this->eventprime_get_template_html($template, $args);
	}
        
        public function load_event_submit_form($atts)
        {
            $args         = new stdClass();
            $args->event_id = isset($_GET['event_id']) && !empty($_GET['event_id']) ? absint( sanitize_text_field($_GET['event_id']) ) : 0;
            $fes_data     = $this->get_event_submission_options( $args );
            $fes_data     = apply_filters( 'ep_filter_frontend_event_submission_options', $fes_data, $atts );
            $template = 'eventprime-frontend-submission-form';
            return $this->eventprime_get_template_html($template, $args);
        }
        
        public function get_event_submission_options( $args ) 
        {
            $ep_functions = new Eventprime_Basic_Functions;
            $args->ues_confirm_message = $ep_functions->ep_get_global_settings( 'ues_confirm_message' );
            $args->allow_submission_by_anonymous_user = $ep_functions->ep_get_global_settings('allow_submission_by_anonymous_user');
            $args->login_required = true;
            $login_page_id = $ep_functions->ep_get_global_settings('login_page');
            //$args->login_page_url = get_permalink( $login_page_id ).'?redirect='.get_permalink();
            $args->login_page_url = $ep_functions->ep_get_custom_page_url( 'login_page' );
            if( ! empty( $args->allow_submission_by_anonymous_user ) || is_user_logged_in() ) {
                $args->login_required = false;
            }
            $args->ues_login_message = $ep_functions->ep_get_global_settings('ues_login_message');
            if( ! empty( $args->allow_submission_by_anonymous_user ) ) {
               $args->ues_login_message = '';
            }
            $args->ues_default_status = $ep_functions->ep_get_global_settings('ues_default_status');
            $args->frontend_submission_roles = $ep_functions->ep_get_global_settings('frontend_submission_roles');
            $args->ues_restricted_submission_message = $ep_functions->ep_get_global_settings('ues_restricted_submission_message');

            $frontend_submission_sections = (array)$ep_functions->ep_get_global_settings('frontend_submission_sections');
            $args->fes_event_text_color = false;
            $args->fes_event_featured_image = false;
            $args->fes_event_booking = false;
            $args->fes_event_link = false;
            $args->fes_event_type = false;
            $args->fes_new_event_type = false;
            $args->fes_event_location = false;
            $args->fes_new_event_location = false;
            $args->fes_event_performer = false;
            $args->fes_new_event_performer = false;
            $args->fes_event_organizer = false;
            $args->fes_new_event_organizer = false;
            $args->fes_event_more_options = false;
            if( ! empty( $frontend_submission_sections ) ) {
                if(isset($frontend_submission_sections['fes_event_text_color'])){
                    $args->fes_event_text_color = true;
                }
                if(isset($frontend_submission_sections['fes_event_featured_image'])){
                    $args->fes_event_featured_image = true;
                }
                if(isset($frontend_submission_sections['fes_event_booking'])){
                    $args->fes_event_booking = true;
                }
                if(isset($frontend_submission_sections['fes_event_link'])){
                    $args->fes_event_link = true;
                }
                if(isset($frontend_submission_sections['fes_event_type'])){
                    $args->fes_event_type = true;
                }
                if(isset($frontend_submission_sections['fes_new_event_type'])){
                    $args->fes_new_event_type = true;
                }
                if(isset($frontend_submission_sections['fes_event_location'])){
                    $args->fes_event_location = true;
                }
                if(isset($frontend_submission_sections['fes_new_event_location'])){
                    $args->fes_new_event_location = true;
                }
                if(isset($frontend_submission_sections['fes_event_performer'])){
                    $args->fes_event_performer = true;
                }
                if(isset($frontend_submission_sections['fes_new_event_performer'])){
                    $args->fes_new_event_performer = true;
                }
                if(isset($frontend_submission_sections['fes_event_organizer'])){
                    $args->fes_event_organizer = true;
                }
                if(isset($frontend_submission_sections['fes_new_event_organizer'])){
                    $args->fes_new_event_organizer = true;
                }
                if(isset($frontend_submission_sections['fes_event_more_options'])){
                    $args->fes_event_more_options = true;
                }
            }

            //Required Section
            $frontend_submission_required = (array)$ep_functions->ep_get_global_settings('frontend_submission_required');
            $args->fes_event_description_req = false;
            $args->fes_event_booking_req = false;
            $args->fes_booking_price_req = false;
            $args->fes_event_link_req = false;
            $args->fes_event_type_req = false;
            $args->fes_event_location_req = false;
            $args->fes_event_performer_req = false;
            $args->fes_event_organizer_req = false;
            if( ! empty( $frontend_submission_required ) ) {
                if(isset($frontend_submission_required['fes_event_description'])){
                    $args->fes_event_description_req = true;
                }
                if(isset($frontend_submission_required['fes_event_booking'])){
                    $args->fes_event_booking_req = true;
                }
                if(isset($frontend_submission_required['fes_booking_price'])){
                    $args->fes_booking_price_req = true;
                }
                if(isset($frontend_submission_required['fes_event_link'])){
                    $args->fes_event_link_req = true;
                }
                if(isset($frontend_submission_required['fes_event_type'])){
                    $args->fes_event_type_req = true;
                }
                if(isset($frontend_submission_required['fes_event_location'])){
                    $args->fes_event_location_req = true;
                }
                if(isset($frontend_submission_required['fes_event_performer'])){
                    $args->fes_event_performer_req = true;
                }
                if(isset($frontend_submission_required['fes_event_organizer'])){
                    $args->fes_event_organizer_req = true;
                }
            }

            //Event Types lists
            $event_types = $ep_functions->get_event_types_data();
            $args->event_types = new stdClass();
            if(isset($event_types->terms)){
                $args->event_types = $event_types->terms;
            }

            //Event Venues lists
            $event_venues = $ep_functions->get_venues_data();
            $args->event_venues = new stdClass();
            if(isset($event_venues->terms)){
                $args->event_venues = $event_venues->terms;
            }

            //Event Performers lists
            $event_performers = $ep_functions->get_performer_all_data();;
            $args->event_performers = new stdClass();
            if(count($event_performers)){
                $args->event_performers = $event_performers;
            }

            //Event Organizers lists
            $event_organizers = $ep_functions->get_organizers_data();
            $args->event_organizers = new stdClass();
            if(isset($event_organizers->terms)){
                $args->event_organizers = $event_organizers->terms;
            }

            //Ages
            $args->ages_groups = array(
                'all'               => esc_html__( 'All', 'eventprime-event-calendar-management' ),
                'parental_guidance' => esc_html__( 'All ages but parental guidance', 'eventprime-event-calendar-management' ),
                'custom_group'      => esc_html__(' Custom Age', 'eventprime-event-calendar-management' )
            );

            $args->fes_allow_media_library = $ep_functions->ep_get_global_settings( 'fes_allow_media_library' );

            //Edit Event
           
            if( ! empty( $args->event_id ) ) {
                $args->event = $ep_functions->get_single_event( $args->event_id ); 
            }
            return $args;
    }
    

	/*
	* Load Login template
	*/
    public function load_login( $atts ) 
    {
	$ep_functions = new Eventprime_Basic_Functions;
        $args = new stdClass();
        $args->redirect_url = ( ! empty( $ep_functions->ep_get_global_settings( 'login_redirect_after_login' ) ) ) ? get_permalink( $ep_functions->ep_get_global_settings( 'login_redirect_after_login' ) ) : get_permalink( $ep_functions->ep_get_global_settings( 'profile_page' ) );
        if(isset( $atts['redirect'] ) ) {
            if( $atts['redirect'] == 'off' ) {
                $args->redirect_url = '';
            }
            if( $atts['redirect'] == 'reload' ) {
                $args->redirect_url = 'reload';
            }
        }
        //  Login Block Attributes start
        if(! empty( $atts['block_login_custom_class'] ) ){
            $args->block_login_custom_class = $atts['block_login_custom_class'];
        }
        if( ! empty( $atts['block_login_title'] ) ){
            $args->block_login_title = $atts['block_login_title'];
        }
        if( ! empty( $atts['block_login_user_detail_label'] ) ){
            $args->block_login_user_detail_label = $atts['block_login_user_detail_label'];
        }
        if( ! empty( $atts['block_login_password_label'] ) ){
            $args->block_login_password_label = $atts['block_login_password_label'];   
        }
        if( ! empty( $atts['block_login_remember_me_label'] ) ){
            $args->block_login_remember_me_label = $atts['block_login_remember_me_label'];   
        }
        if( ! empty( $atts['block_login_forget_password_label'] ) ){
            $args->block_login_forget_password_label = $atts['block_login_forget_password_label'];
        }
        if( ! empty( $atts['block_login_click_here_label'] ) ){
            $args->block_login_click_here_label = $atts['block_login_click_here_label'];
        }
        if( ! empty( $atts['block_login_button']  ) ){
            $args->block_login_button_label = $atts['block_login_button'];
        }
        if( ! empty( $atts['block_login_dont_have_account_label'] ) ){
            $args->block_login_dont_have_account_label = $atts['block_login_dont_have_account_label'];
        }
        if( ! empty( $atts['block_login_register_link_label'] ) ){
            $args->block_login_register_link_label = $atts['block_login_register_link_label'];
        }
        if( ! empty( $atts['align'] ) ){
            $args->align = $atts['align'];
        }
        if( ! empty( $atts['backgroundColor'] ) ){
            $args->backgroundColor = $atts['backgroundColor'];
        }
        if( ! empty( $atts['textColor'] ) ){
            $args->textColor = $atts['textColor'];
        }
        //  Login Block Attributes end
        
        $args = $this->get_login_options( $args );

        $args->current_user = wp_get_current_user();
        $template = 'eventprime-login';
        return $this->eventprime_get_template_html($template, $args);
    }
	
	/*
	* Load Register template
	*/
    public function load_register( $atts ) 
    {
	$ep_functions = new Eventprime_Basic_Functions;
        $args = new stdClass();
        $args->show_register = 0;
        $args->redirect_url = ( ! empty( $ep_functions->ep_get_global_settings( 'login_redirect_after_login' ) ) ) ? get_permalink( $ep_functions->ep_get_global_settings( 'login_redirect_after_login' ) ) : get_permalink( $ep_functions->ep_get_global_settings( 'profile_page' ) );

        if( isset( $_POST['ep_register'] ) ) {
            $args->show_register = 1;
        }
        //  Register Block Attributes start
        if(! empty( $atts['block_register_custom_class'] ) ){
            $args->block_register_custom_class = $atts['block_register_custom_class'];
        }
        if( ! empty( $atts['block_register_user_name_label'] ) ){
            $args->block_register_user_name_label = $atts['block_register_user_name_label'];
        }
        if( ! empty( $atts['block_register_user_email_label'] ) ){
            $args->block_register_user_email_label = $atts['block_register_user_email_label'];
        }
        if( ! empty( $atts['block_register_password_label'] ) ){
            $args->block_register_password_label = $atts['block_register_password_label'];   
        }
        if( ! empty( $atts['block_register_repeat_password_label'] ) ){
            $args->block_register_repeat_password_label = $atts['block_register_repeat_password_label'];   
        }
        if( ! empty( $atts['block_register_phone_label'] ) ){
            $args->block_register_phone_label = $atts['block_register_phone_label'];
        }
        if( ! empty( $atts['block_register_button'] ) ){
            $args->block_register_button_label = $atts['block_register_button'];
        }
        if( ! empty( $atts['block_register_already__account_label'] ) ){
            $args->block_register_already__account_label = $atts['block_register_already__account_label'];
        }
        if( ! empty( $atts['block_register_please__login_label'] ) ){
            $args->block_register_please__login_label = $atts['block_register_please__login_label'];
        }
        if( ! empty( $atts['align'] ) ){
            $args->align = $atts['align'];
        }
        if( ! empty( $atts['backgroundColor'] ) ){
            $args->backgroundColor = $atts['backgroundColor'];
        }
        if( ! empty( $atts['textColor'] ) ){
            $args->textColor = $atts['textColor'];
        }
        //  Register Block Attributes end

        $args->redirect_url = '';
        if(isset( $atts['redirect'] ) ) {
            if( $atts['redirect'] == 'reload' ) {
                $args->redirect_url = 'reload';
            }
        }

        $args = $this->get_register_options( $args );
        //print_r($args);die;
        $args->current_user = wp_get_current_user();
        $template = 'eventprime-register';
        return $this->eventprime_get_template_html($template, $args);
    }
        
    public function get_login_options( $args ) {  
        $ep_functions = new Eventprime_Basic_Functions;
        $args->login_heading_text = ( ! empty( $ep_functions->ep_get_global_settings( 'login_heading_text' ) ) ? $ep_functions->ep_get_global_settings( 'login_heading_text' ) : 'Login to Your Account' );

        // block login title update field
        if( ! empty( $args->block_login_title ) ){
            $args->login_heading_text = $args->block_login_title;
        }

        $args->login_subheading_text = ( ! empty( $ep_functions->ep_get_global_settings( 'login_subheading_text' ) ) ? $ep_functions->ep_get_global_settings( 'login_subheading_text' ) : '' );
        $args->login_username_label = esc_html__( 'Email/Username', 'eventprime-event-calendar-management' );
        $args->login_id_field = 'email_username';
        $login_id_field = $ep_functions->ep_get_global_settings( 'login_id_field' );

        if( ! empty( $login_id_field ) ) {
            $login_username_label = $ep_functions->ep_get_global_settings( 'login_id_field_label_setting' );
            if( empty( $login_username_label ) ) {
                if( $login_id_field == 'username' ) {
                    $args->login_username_label = esc_html__( 'Username', 'eventprime-event-calendar-management' );
                } elseif( $login_id_field == 'email' ) {
                    $args->login_username_label = esc_html__( 'Email', 'eventprime-event-calendar-management' );
                }
            } else{
                $args->login_username_label = $login_username_label;
            }
            $args->login_id_field = $login_id_field;
        }
        // Custom class for Login Block
        if(! empty( $args->block_login_custom_class ) ){
            $args->block_login_class = $args->block_login_custom_class;
        }
        // block user detail editable field
        if( ! empty( $args->block_login_user_detail_label ) ){
            $args->login_username_label = $args->block_login_user_detail_label;
        }

        $args->login_password_label = esc_html__( 'Password', 'eventprime-event-calendar-management' );
        $login_password_label = $ep_functions->ep_get_global_settings( 'login_password_label' );

        if( !empty( $login_password_label ) ) {
            $args->login_password_label = $login_password_label;
        }
        // block login password editable field
        if( ! empty( $args->block_login_password_label ) ){
            $args->login_password_label = $args->block_login_password_label;
        }

        $args->login_show_rememberme_label = esc_html__( 'Remember Me', 'eventprime-event-calendar-management' );
        $login_show_rememberme_label = $ep_functions->ep_get_global_settings( 'login_show_rememberme_label' );
        if( !empty( $login_show_rememberme_label ) ) {
            $args->login_show_rememberme_label = $login_show_rememberme_label;
        }
        // block login remember me editable field
        if( ! empty( $args->block_login_remember_me_label ) ){
            $args->login_show_rememberme_label = $args->block_login_remember_me_label;
        }
        
        $args->login_button_label = esc_html__( 'Log in', 'eventprime-event-calendar-management' );
        $login_button_label = $ep_functions->ep_get_global_settings( 'login_button_label' );
        if( !empty( $login_button_label ) ) {
            $args->login_button_label = $login_button_label;
        }
        // block login button editable field
        if( ! empty( $args->block_login_button_label ) ){
            $args->login_button_label = $args->block_login_button_label;
        }
        $args->login_show_forgotpassword_label = esc_html__( 'Forgot Password?', 'eventprime-event-calendar-management' );
        if( ! empty( $ep_functions->ep_get_global_settings( 'login_show_forgotpassword_label' ) ) ) {
            $args->login_show_forgotpassword_label = $ep_functions->ep_get_global_settings( 'login_show_forgotpassword_label' );
        }
        // block login forget password editable feild
        if( ! empty( $args->block_login_forget_password_label ) ){
            $args->login_show_forgotpassword_label = $args->block_login_forget_password_label;
        }
        $args->login_click_here_label = esc_html__( ' Click Here', 'eventprime-event-calendar-management' );

        // block login Click Here label
        if( ! empty( $args->block_login_click_here_label ) ){
            $args->login_click_here_label = $args->block_login_click_here_label;
        }

        $args->login_google_recaptcha = $ep_functions->ep_get_global_settings('login_google_recaptcha');
        $args->google_recaptcha_site_key = $ep_functions->ep_get_global_settings('google_recaptcha_site_key');

        $args->dont_have_account_label = esc_html__( "Don't have an account.", 'eventprime-event-calendar-management' );
        
        // block login don't have account label
        if( ! empty( $args->block_login_dont_have_account_label ) ){
            $args->dont_have_account_label = $args->block_login_dont_have_account_label;
        }

        $args->register_text = '';

        return $args;
    }

    /**
     * Get register options from global settings
     *
     * @param object $args Arguments
     * 
     * @return object $args
     */
    public function get_register_options( $args ) {
        // username settings
        $ep_functions = new Eventprime_Basic_Functions;
        $register_username = $ep_functions->ep_get_global_settings( 'register_username' );
        $args->register_username_show = 1;
        $args->register_username_label = esc_html__( 'Username', 'eventprime-event-calendar-management' );
        if( ! isset( $register_username['show'] ) || empty( $register_username['show'] ) ) {
            $args->register_username_show = 0;
        }
        
        if( isset( $register_username['label'] ) && ! empty( $register_username['label'] ) ) {
            $args->register_username_label = $register_username['label'];
        }
        // Register Block customer class
        if(! empty( $args->block_register_custom_class ) ){
            $args->block_register_class = $args->block_register_custom_class;
        }
        // Register block user name label update
        if( ! empty( $args->block_register_user_name_label ) ){
            $args->register_username_label = $args->block_register_user_name_label;
        }
        $args->register_username_mandatory = 1;
        if( ! isset( $register_username['mandatory'] ) || $register_username['mandatory'] == 0 ) {
            $args->register_username_mandatory = 0;
        }
        
        if(empty($ep_functions->ep_get_global_settings( 'login_registration_form' ))){
            $args->register_username_show = 1;
            $args->register_username_mandatory = 1;
        }
        // email settings
        $register_email = $ep_functions->ep_get_global_settings( 'register_email' );
        $args->register_email_label = esc_html__( 'Email', 'eventprime-event-calendar-management' );
        if( isset( $register_email['label'] ) && ! empty( $register_email['label'] ) ) {
            $args->register_email_label = $register_email['label'];
        }
        // Register block user email label update
        if( ! empty( $args->block_register_user_email_label ) ){
            $args->register_email_label = $args->block_register_user_email_label;
        }
        // password settings
        $register_password = $ep_functions->ep_get_global_settings( 'register_password' );
        $args->register_password_show = 1;
        $args->register_password_label = esc_html__( 'Password', 'eventprime-event-calendar-management' );
        if( ! isset( $register_password['show'] ) || $register_password['show'] == 0 ) {
            $args->register_password_show = 0;
        }
        if( isset( $register_password['label'] ) && ! empty( $register_password['label'] ) ) {
            $args->register_password_label = $register_password['label'];
        }
        $args->register_password_mandatory = 1;
        if( ! isset( $register_password['mandatory'] ) || $register_password['mandatory'] == 0 ) {
            $args->register_password_mandatory = 0;
        }
        
        if(empty($ep_functions->ep_get_global_settings( 'login_registration_form' ))){
            $args->register_password_show = 1;
        }
        // Register block password label update
        if( ! empty( $args->block_register_password_label ) ){
            $args->register_password_label = $args->block_register_password_label;
        }
        // repeat password settings
        $register_repeat_password = $ep_functions->ep_get_global_settings( 'register_repeat_password' );
        $args->register_repeat_password_show = 1;
        $args->register_repeat_password_label = esc_html__( 'Repeat Password', 'eventprime-event-calendar-management' );
        if( ! isset( $register_repeat_password['show'] ) || $register_repeat_password['show'] == 0 ) {
            $args->register_repeat_password_show = 0;
        }
        if( isset( $register_repeat_password['label'] ) && ! empty( $register_repeat_password['label'] ) ) {
            $args->register_repeat_password_label = $register_repeat_password['label'];
        }
        $args->register_repeat_password_mandatory = 1;
        if( ! isset( $register_repeat_password['mandatory'] ) || $register_repeat_password['mandatory'] == 0 ) {
            $args->register_repeat_password_mandatory = 0;
        }
        // Register block repeat password label update
        if( ! empty( $args->block_register_repeat_password_label ) ){
            $args->register_repeat_password_label = $args->block_register_repeat_password_label;
        }
        // dob settings
        $register_dob = $ep_functions->ep_get_global_settings( 'register_dob' );
        $args->register_dob_show = 1;
        $args->register_dob_label = esc_html__( 'Date of Birth', 'eventprime-event-calendar-management' );
        if( ! isset( $register_dob['show'] ) || $register_dob['show'] == 0 ) {
            $args->register_dob_show = 0; 
        }
        if( isset( $register_dob['label'] ) && ! empty( $register_dob['label'] ) ) {
                $args->register_dob_label = $register_dob['label'];
            }
        $args->register_dob_mandatory = 1;
        if( ! isset( $register_dob['mandatory'] ) || $register_dob['mandatory'] == 0 ) {
            $args->register_dob_mandatory = 0;
        }

        // phone settings
        $register_phone = $ep_functions->ep_get_global_settings( 'register_phone' );
        $args->register_phone_show = 1;
        $args->register_phone_label = esc_html__( 'Phone', 'eventprime-event-calendar-management' );
        if( ! isset( $register_phone['show'] ) || $register_phone['show'] == 0 ) {
            $args->register_phone_show = 0;
        }
        if( isset( $register_phone['label'] ) && ! empty( $register_phone['label'] ) ) {
            $args->register_phone_label = $register_phone['label'];
        }
        $args->register_phone_mandatory = 1;
        if( ! isset( $register_phone['mandatory'] ) || $register_phone['mandatory'] == 0 ) {
            $args->register_phone_mandatory = 0;
        }
        if(empty($ep_functions->ep_get_global_settings( 'login_registration_form' ))){
            $args->register_phone_show = 1;
        }
        // Register block phone label update
        if( ! empty( $args->block_register_phone_label ) ){
            $args->register_phone_label = $args->block_register_phone_label;
        }
        // timezone settings
        $register_timezone = $ep_functions->ep_get_global_settings( 'register_timezone' );
        
        $args->register_timezone_show = 1;
        $args->register_timezone_label = esc_html__( 'Timezone', 'eventprime-event-calendar-management' );
        if( ! isset( $register_timezone['show'] ) || $register_timezone['show'] == 0 ) {
            $args->register_timezone_show = 0;
        }
        if( isset( $register_timezone['label'] ) && ! empty( $register_timezone['label'] ) ) {
            $args->register_timezone_label = $register_timezone['label'];
        }
        
        $args->register_button_label = 'Register';
        // Register block button label update
        if( ! empty( $args->block_register_button_label ) ){
            $args->register_button_label = $args->block_register_button_label;
        }
        $args->register_timezone_mandatory = 1;
        if( ! isset( $register_timezone['mandatory'] ) || $register_timezone['mandatory'] == 0 ) {
            $args->register_timezone_mandatory = 0;
        }
        $args->google_recaptcha_site_key = $ep_functions->ep_get_global_settings('google_recaptcha_site_key');
        $args->register_google_recaptcha = 0;
        if( $ep_functions->ep_get_global_settings( 'register_google_recaptcha' ) ){
            $args->register_google_recaptcha = 1;
        }

        $args->already_have_account_label = esc_html__( "Already have an account?", 'eventprime-event-calendar-management' );
        
        // block register don't have account label
        if( ! empty( $args->block_register_already__account_label ) ){
            $args->already_have_account_label = $args->block_register_already__account_label;
        }
        
        $args->login_button_label = esc_html__( 'Log in', 'eventprime-event-calendar-management' );
        $login_button_label = $ep_functions->ep_get_global_settings( 'login_button_label' );
        
        if( !empty( $login_button_label ) ) {
            $args->login_button_label = $login_button_label;
        }
        $args->login_button_label = esc_html__( "Please Login", 'eventprime-event-calendar-management' );

         // block register please login label
         if( ! empty( $args->block_register_please__login_label ) ){
            $args->login_button_label = $args->block_register_please__login_label;
        }

        return $args;
    }
    
    public function ep_modify_taxonomy_archive_query($query) {
        if (!is_admin() && $query->is_main_query() && is_tax('em_venue')) {
            $query->set('posts_per_page',0 );
            $query->set('post_type', 'none');
        }
    }
    
    public function ep_load_single_template( $template ) 
    {
        if (is_tax('em_event_type') || is_tax('em_venue') || is_tax('em_event_organizer')  )
        {
            if(is_tax('em_event_type')) 
            {
                $template_file = 'event_types/single-ep-event-type';
            }
            elseif(is_tax('em_venue')) 
            {
                $template_file = 'venues/single-ep-venue';
            } 
            elseif(is_tax('em_event_organizer')) 
            {
                $template_file = 'organizers/single-ep-event-organizer';
            }
            return $this->ep_get_template_part($template_file);
        }
        // If the template file doesn't exist in the plugin, return the original template
        return $template;
    }
    
    public function ep_load_single_template_dynamic_old($content)
    {
        
        //remove_filter( 'the_content', array( $this, 'ep_load_single_template_dynamic'),1000000000 );
        if( is_single() ) 
        {
            $atts = array();
            $atts['id'] = get_the_ID();
            if( get_post_type() == 'em_event' ) {
                $content = $this->load_single_event($atts);
            } 
            elseif( get_post_type() == 'em_performer' ) {
                $content = $this->load_single_performer($atts);
            } 
            elseif(get_post_type() == 'em_sponsor' )
            {
                $ep_functions = new Eventprime_Basic_Functions;
                $extensions = $ep_functions->ep_get_activate_extensions();
                if( ! empty( $extensions ) && in_array( 'Eventprime_Event_Sponsor', $extensions ) ) 
                {
                    $sponsors = new Eventprime_Event_Sponsor_Public($this->plugin_name,$this->version);
                    $content = $sponsors->load_single_sponsor($atts);
                }
            }
        } 

           return $content;
    }
    
    public function remove_thumbnail_on_event_post_type($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (('em_event' === get_post_type($post_id) || 'em_performer' === get_post_type($post_id)) && is_single()) {
            return '';
        }
        return $html;
    }
    
    public function ep_event_add_hidden_variables( $args ) {?>
        <input type="hidden" id="ep-events-style" value="<?php echo esc_attr( $args->display_style );?>"/> <?php 
    }
    
    /**
     * Show timezone related content before event list
     */
    public function ep_show_timezone_related_message( $args ) 
    {
        $ep_functions = new Eventprime_Basic_Functions;
        $flag = false;
        $enable_event_time_to_user_timezone  = $ep_functions->ep_get_global_settings( 'enable_event_time_to_user_timezone' );
        if( ! empty( $enable_event_time_to_user_timezone ) &&  $flag) 
        { 
            $show_timezone_message_on_event_page = $ep_functions->ep_get_global_settings( 'show_timezone_message_on_event_page' );
            if( ! empty( $show_timezone_message_on_event_page ) ) 
            {
                $timezone_related_message = $ep_functions->ep_get_global_settings( 'timezone_related_message' );
                if( empty( $timezone_related_message ) ) 
                {
                    $timezone_related_message = esc_html__( 'All the event times coming as per {{$timezone}} timezone.', 'eventprime-event-calendar-management' );
                }
                if( strpos( $timezone_related_message, '{{$timezone}}' ) !== false ) 
                {
                    $current_timezone = $ep_functions->ep_get_current_user_timezone();
                    if( empty( $current_timezone ) ) {
                        $current_timezone = $ep_functions->ep_get_site_timezone();
                    }
                    // replace the variable to timezone
                    $timezone_related_message = str_replace( '{{$timezone}}', $current_timezone, $timezone_related_message );
                }?>
                <div class="ep-timezone-wrap ep-box-wrap">
                    <div class="ep-box-row ep-mb-3">
                        <div class="ep-box-col-12">
                            <?php echo esc_html( $timezone_related_message );?>
                            <span class="ep-user-profile-timezone-wrap">
                                <span class="material-icons-round ep-fs-6 ep-align-middle ep-cursor" id="ep-user-profile-timezone-edit">edit</span>&nbsp;&nbsp;
                                <span class="ep-user-profile-timezone-list" style="display: none;">
                                    <select name="ep_user_timezone" id="ep_user_profile_timezone_list" class="ep-form-input ep-input-text">
                                        <?php echo wp_timezone_choice( $current_timezone );?>
                                    </select>
                                    <button type="button" class="ep-btn ep-btn-primary ep-btn-sm" id="ep_user_profile_timezone_save"><?php esc_html_e( 'Save', 'eventprime-event-calendar-management' ); ?></button>
                                </span>
                            </span>
                        </div>
                    </div>
                </div><?php
            }
        }
    }
    
    /**
     * Add wishlist icon on the event
     * 
     * @param object $event Event data.
     * 
     * @return void
     */
    public function ep_event_add_wishlist_icon( $event, $page ) 
    {
        $ep_functions = new Eventprime_Basic_Functions;
        if( $event && ! empty( $event->id ) && empty( $ep_functions->ep_get_global_settings( 'hide_wishlist_icon' ) )) 
        {
            $add_to_wishlist_text = $ep_functions->ep_global_settings_button_title( 'Add To Wishlist' );
            $remove_from_wishlist_text = $ep_functions->ep_global_settings_button_title( 'Remove From Wishlist' );
            $wish_title = esc_html__( 'Add To Wishlist', 'eventprime-event-calendar-management' );
            if( ! empty( $add_to_wishlist_text ) ) {
                $wish_title = $add_to_wishlist_text;
            }
            if( $event->event_in_user_wishlist == true ) { 
                $wish_title = esc_html__( 'Remove From Wishlist', 'eventprime-event-calendar-management' );
                if( ! empty( $remove_from_wishlist_text ) ) {
                    $wish_title = $remove_from_wishlist_text;
                }
            }
            if( $page == 'event_detail' ) {?>
                <div class="ep-event-action ep_event_wishlist_action ep-px-2 ep-d-flex ep-align-items-center ep-rounded-tbl-right" id="ep_event_wishlist_action_<?php echo esc_attr( $event->id );?>" data-event_id="<?php echo esc_attr( $event->id );?>" title="<?php echo esc_attr($wish_title);?>">
                    <span class="material-icons-outlined ep-handle-fav ep-cursor ep-button-text-color ep-mr-3 <?php if( $event->event_in_user_wishlist == true ) { echo esc_html( 'ep-text-danger' ); }?>"><?php if( $event->event_in_user_wishlist == true ) { echo esc_html('favorite'); } else{ echo esc_html('favorite_border'); }?></span>
                </div><?php
            } else{?>
                <div class="ep-wishlist-action-wrap">
                    <div class="ep-event-action ep_event_wishlist_action ep-px-2" id="ep_event_wishlist_action_<?php echo esc_attr( $event->id );?>" data-event_id="<?php echo esc_attr( $event->id );?>" title="<?php echo esc_attr($wish_title);?>">
                        <span class="material-icons-outlined ep-handle-fav ep-cursor ep-button-text-color ep-fs-6 <?php if( $event->event_in_user_wishlist == true ) { echo esc_html( 'ep-text-danger' ); }?>"><?php if( $event->event_in_user_wishlist == true ) { echo esc_html('favorite'); } else{ echo esc_html('favorite_border'); }?></span>
                    </div>
                </div><?php
            }
        }
    }
    
    public function ep_event_add_social_sharing_icon( $event, $page ) 
    {
        $ep_functions = new Eventprime_Basic_Functions;
        if ( ! empty( $ep_functions->ep_get_global_settings( 'social_sharing' ) ) ) 
        {
            if( $event && ! empty( $event->id ) ) 
            { 
                if( $page == 'event_detail' ) 
                {
                    ?>
                    <div class="ep-sl-event-action ep-cursor ep-position-relative ep-bg-white ep-rounded-tbr-right ep-py-1 ep-d-flex ep-align-items-center">
                        <span class="material-icons-outlined ep-handle-share ep-button-text-color ep-mr-3 ep-cursor">share</span>
                        <?php
                        $social_links_url = $event->event_url;?>
                        <ul class="ep-event-share ep-m-0 ep-p-0" style="display:none;">
                            <li class="ep-event-social-icon" title="">
                                <a class="facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_url($social_links_url); ?>" onclick="javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=500,width=600'); return false;" target="_blank" title="<?php esc_html_e('Share on Facebook', 'eventprime-event-calendar-management'); ?>">
                                    <span class="ep-social-title" title="Facebook"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!-- Font Awesome Pro 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"/></svg> Facebook</span>
                                </a>
                            </li>
                            <li class="ep-event-social-icon">
                                <a class="twitter" href="https://twitter.com/share?url=<?php echo esc_url($social_links_url); ?>" onclick="javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=500'); return false;" target="_blank" title="<?php esc_html_e('Share on Twitter', 'eventprime-event-calendar-management'); ?>">
                                    <span class="ep-social-title"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!-- Font Awesome Pro 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M459.37 151.716c.325 4.548.325 9.097.325 13.645 0 138.72-105.583 298.558-298.558 298.558-59.452 0-114.68-17.219-161.137-47.106 8.447.974 16.568 1.299 25.34 1.299 49.055 0 94.213-16.568 130.274-44.832-46.132-.975-84.792-31.188-98.112-72.772 6.498.974 12.995 1.624 19.818 1.624 9.421 0 18.843-1.3 27.614-3.573-48.081-9.747-84.143-51.98-84.143-102.985v-1.299c13.969 7.797 30.214 12.67 47.431 13.319-28.264-18.843-46.781-51.005-46.781-87.391 0-19.492 5.197-37.36 14.294-52.954 51.655 63.675 129.3 105.258 216.365 109.807-1.624-7.797-2.599-15.918-2.599-24.04 0-57.828 46.782-104.934 104.934-104.934 30.213 0 57.502 12.67 76.67 33.137 23.715-4.548 46.456-13.32 66.599-25.34-7.798 24.366-24.366 44.833-46.132 57.827 21.117-2.273 41.584-8.122 60.426-16.243-14.292 20.791-32.161 39.308-52.628 54.253z"/></svg>Twitter</span>
                                </a>
                            </li>

                            <li class="ep-event-social-icon">
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo esc_url($social_links_url); ?>" onclick="javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=500'); return false;" target="_blank" title="<?php esc_html_e('Share on Linkedin', 'eventprime-event-calendar-management'); ?>">
                                    <span class="ep-social-title"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!-- Font Awesome Pro 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z"/></svg>Linkedin</span>
                                </a>
                            </li>
                            <li class="ep-event-social-icon">
                                <a href="https://api.whatsapp.com/send?text=<?php echo esc_url($social_links_url); ?>" target="_blank" title="<?php esc_html_e('Share on Whatsapp', 'eventprime-event-calendar-management'); ?>">
                                    <span class="ep-social-title"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!-- Font Awesome Pro 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>Whatsapp</span>
                                </a>
                            </li>
                        </ul>
                    </div><?php
                } 
                else
                {?>
                    <div class="ep-social-share-action-wrap">
                        <div class="ep-event-action ep-cursor ep-position-relative ep-px-2">
                            <span class="material-icons-outlined ep-handle-share ep-button-text-color ep-fs-6">share</span>
                            <?php
                            $social_links_url = $event->event_url;?>
                            <ul class="ep-event-share ep-m-0 ep-px-0" style="display:none;">
                                <li class="ep-event-social-icon" title="">
                                    <a class="facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_url($social_links_url); ?>" onclick="javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=500,width=600'); return false;" target="_blank" title="<?php esc_html_e('Share on Facebook', 'eventprime-event-calendar-management'); ?>">
                                        <span class="ep-social-title" title="Facebook"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!-- Font Awesome Pro 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"/></svg> Facebook</span>
                                    </a>
                                </li>
                                <li class="ep-event-social-icon">
                                    <a class="twitter" href="https://twitter.com/share?url=<?php echo esc_url($social_links_url); ?>" onclick="javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=500'); return false;" target="_blank" title="<?php esc_html_e('Share on Twitter', 'eventprime-event-calendar-management'); ?>">
                                        <span class="ep-social-title"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!-- Font Awesome Pro 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M459.37 151.716c.325 4.548.325 9.097.325 13.645 0 138.72-105.583 298.558-298.558 298.558-59.452 0-114.68-17.219-161.137-47.106 8.447.974 16.568 1.299 25.34 1.299 49.055 0 94.213-16.568 130.274-44.832-46.132-.975-84.792-31.188-98.112-72.772 6.498.974 12.995 1.624 19.818 1.624 9.421 0 18.843-1.3 27.614-3.573-48.081-9.747-84.143-51.98-84.143-102.985v-1.299c13.969 7.797 30.214 12.67 47.431 13.319-28.264-18.843-46.781-51.005-46.781-87.391 0-19.492 5.197-37.36 14.294-52.954 51.655 63.675 129.3 105.258 216.365 109.807-1.624-7.797-2.599-15.918-2.599-24.04 0-57.828 46.782-104.934 104.934-104.934 30.213 0 57.502 12.67 76.67 33.137 23.715-4.548 46.456-13.32 66.599-25.34-7.798 24.366-24.366 44.833-46.132 57.827 21.117-2.273 41.584-8.122 60.426-16.243-14.292 20.791-32.161 39.308-52.628 54.253z"/></svg>Twitter</span>
                                    </a>
                                </li>

                                <li class="ep-event-social-icon">
                                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo esc_url($social_links_url); ?>" onclick="javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=500'); return false;" target="_blank" title="<?php esc_html_e('Share on Linkedin', 'eventprime-event-calendar-management'); ?>">
                                        <span class="ep-social-title"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!-- Font Awesome Pro 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z"/></svg>Linkedin</span>
                                    </a>
                                </li>
                                <li class="ep-event-social-icon">
                                    <a href="https://api.whatsapp.com/send?text=<?php echo esc_url($social_links_url); ?>" target="_blank" title="<?php esc_html_e('Share on Whatsapp', 'eventprime-event-calendar-management'); ?>">
                                        <span class="ep-social-title"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!-- Font Awesome Pro 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>Whatsapp</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div><?php
                }
            }
        }
    }
    
    public function ep_event_add_event_dates( $event, $view ) {
        $ep_functions = new Eventprime_Basic_Functions;
        $event_listings_date_format = !empty($ep_functions->ep_get_global_settings('event_listings_date_format_val')) ? $ep_functions->ep_get_global_settings('event_listings_date_format_val') : 'd M';
        if( $event && ! empty( $event->id ) ) { 
            if ( ! empty( $event->em_start_date ) ) {
                // $event_date_time = $ep_functions->ep_convert_event_date_time_from_timezone( $event );
                
                $event_listings_date_time_format = $event_listings_date_format . ' h:i A'; 
                $time_format = $ep_functions->ep_get_global_settings('time_format'); 
                if ( !empty($time_format) && $time_format == 'HH:mm' ) {
                    $event_listings_date_time_format = $event_listings_date_format . ' H:i';
                } else {
                    $event_listings_date_time_format = $event_listings_date_format . ' h:i A';
                }
                $event_date_time = $ep_functions->ep_convert_event_date_time_from_timezone( $event, $event_listings_date_time_format, 0, 1 );

                $start_date = $event->em_start_date;
                if( ! empty( $event->em_end_date ) ) {
                    $end_date = $event->em_end_date;
                    if( $view == 'list' ) {
                        if( ! $ep_functions->ep_show_event_date_time( 'em_start_date', $event ) ) {
                            if( ! empty( $event->em_event_date_placeholder ) ) {
                                if( $event->em_event_date_placeholder == 'tbd' ) {
                                    $tbd_icon_file = plugin_dir_url(__FILE__). 'partials/images/tbd-icon.png';?>
                                    <span class="ep-card-event-date-start ep-text-primary">
                                        <img src="<?php echo esc_url( $tbd_icon_file );?>" width="35" />
                                    </span><?php
                                } else{
                                    if( ! empty( $event->em_event_date_placeholder_custom_note ) ) {?>
                                        <span class="ep-card-event-date-start ep-text-primary">
                                            <?php echo esc_html( $event->em_event_date_placeholder_custom_note );?>
                                        </span><?php
                                    }
                                }
                            }
                        } else{
                            if( $start_date == $end_date ) {?>
                                <span class="ep-event-date ep-fw-bold ep-text-dark"><?php
                                    // echo esc_html__( gmdate( 'D', $start_date ), 'eventprime-event-calendar-management' ) . esc_html( ', ' . $event->fstart_date );
                                    echo esc_html(wp_date( 'D', $start_date )) . ', ' . esc_html(wp_date( $event_listings_date_format, $start_date ));
                                    if( ! empty( $event->em_all_day ) || ( $ep_functions->ep_show_event_date_time( 'em_start_time', $event ) && ( ! empty( $event->em_start_time ) ) ) ) {
                                        echo ',' . '&nbsp;';
                                    }?>
                                </span><?php
                                if( ! empty( $event->em_all_day ) ) {?>
                                    <span><?php echo esc_html__( 'All Day', 'eventprime-event-calendar-management' );?></span><?php
                                }
                                else{
                                    if( $ep_functions->ep_show_event_date_time( 'em_start_time', $event ) && ( ! empty( $event->em_start_time ) ) ) {
                                        $event_change_start_time = $ep_functions->ep_convert_event_time_from_timezone( $event );
                                        if( ! empty( $event_change_start_time ) ) {?>
                                            <span><?php echo esc_html( $ep_functions->ep_convert_time_with_format( $event_change_start_time ) );?><?php
                                                if( $ep_functions->ep_show_event_date_time( 'em_end_time', $event ) && ! empty( $event->em_end_time ) ) {
                                                    $event_change_end_time = $ep_functions->ep_convert_event_time_from_timezone( $event, 1 );?>
                                                    <?php echo ' - ' . esc_html( $ep_functions->ep_convert_time_with_format( $event_change_end_time ) );?><?php
                                                }?>
                                            </span><?php
                                        } else{?>
                                            <span><?php echo esc_html( $ep_functions->ep_convert_time_with_format( $event->em_start_time ) );?><?php
                                                if( $ep_functions->ep_show_event_date_time( 'em_end_time', $event ) && ! empty( $event->em_end_time ) ) {?>
                                                    <?php echo ' - ' . esc_html( $ep_functions->ep_convert_time_with_format( $event->em_end_time ) );?><?php
                                                }?>
                                            </span><?php
                                        }
                                    }
                                }
                            } else{?>
                                <span class="ep-fw-bold ep-text-dark"><?php
                                    // echo esc_html__( wp_date( 'D', $start_date ), 'eventprime-event-calendar-management' ) . esc_html( ', ' . $event->fstart_date );
                                    echo esc_html(wp_date('D', $start_date)) . ', ' . esc_html(wp_date( $event_listings_date_format, $start_date ));
                                    if( $ep_functions->ep_show_event_date_time( 'em_end_date', $event ) && ( ! empty( $event->em_end_date ) ) ) {?>
                                        <span><?php 
                                        // echo ' - ' . esc_html( $event->fend_date );
                                        echo ' - ' . esc_html(wp_date( $event_listings_date_format, $event->em_end_date ));
                                        ?></span><?php
                                    }?>
                                </span><?php
                            }
                        }
                    } else{
                        if( ! $ep_functions->ep_show_event_date_time( 'em_start_date', $event ) ) {
                            if( ! empty( $event->em_event_date_placeholder ) ) {
                                if( $event->em_event_date_placeholder == 'tbd' ) {
                                    $tbd_icon_file = plugin_dir_url(__FILE__) .'partials/images/tbd-icon.png';?>
                                    <span class="ep-card-event-date-start ep-text-primary">
                                        <img src="<?php echo esc_url( $tbd_icon_file );?>" width="35" />
                                    </span><?php
                                } else{
                                    if( ! empty( $event->em_event_date_placeholder_custom_note ) ) {?>
                                        <span class="ep-card-event-date-start ep-text-primary">
                                            <?php echo esc_html( $event->em_event_date_placeholder_custom_note );?>
                                        </span><?php
                                    }
                                }
                            }
                        } else{
                            if( empty( $event->em_start_time ) || ! $ep_functions->ep_show_event_date_time( 'em_start_time', $event ) ) {?>
                                <span class="ep-card-event-date-start ep-text-primary hunny">
                                    <?php 
                                    // echo esc_html__( gmdate( 'D', $event->em_start_date ), 'eventprime-event-calendar-management' ) . esc_html( ', ' . $event->fstart_date );
                                    echo esc_html(wp_date( 'D', $event->em_start_date )) . ', ' . esc_html(wp_date( $event_listings_date_format, $event->em_start_date )); 
                                    ?>
                                </span><?php
                                if( ! empty( $event->em_all_day ) ) {?>
                                    <span> <?php echo ', ' . esc_html__( 'All Day', 'eventprime-event-calendar-management' );?></span><?php
                                }
                            } else{
                                if( ! empty( $event_date_time ) ) {?>
                                    <span class="ep-card-event-date-start ep-text-primary">
                                        <?php echo esc_html(wp_date('D', $start_date)) . ', ' . esc_html( $event_date_time );?>
                                    </span><?php
                                } else{?>
                                    <span class="ep-card-event-date-start ep-text-primary">
                                        <?php echo esc_html( wp_date( 'D', $event->em_start_date )) . esc_html( ', ' . $event->fstart_date );?>
                                    </span>
                                    <span class="ep-card-event-time-start ep-text-primary">
                                        <?php echo ', ' . esc_html( $ep_functions->ep_convert_time_with_format( $event->em_start_time ) );?>
                                    </span><?php
                                }
                            }
                        }
                    }
                } else{
                    if( $view == 'list' ) {
                        if( ! $ep_functions->ep_show_event_date_time( 'em_start_date', $event ) ) {
                            if( ! empty( $event->em_event_date_placeholder ) ) {
                                if( $event->em_event_date_placeholder == 'tbd' ) {
                                    $tbd_icon_file = plugin_dir_url(__FILE__) .'partials/images/tbd-icon.png';?>
                                    <span class="ep-card-event-date-start ep-text-primary">
                                        <img src="<?php echo esc_url( $tbd_icon_file );?>" width="35" />
                                    </span><?php
                                } else{
                                    if( ! empty( $event->em_event_date_placeholder_custom_note ) ) {?>
                                        <span class="ep-card-event-date-start ep-text-primary">
                                            <?php echo esc_html( $event->em_event_date_placeholder_custom_note );?>
                                        </span><?php
                                    }
                                }
                            }
                        } else{?>
                            <span class="ep-fw-bold ep-text-dark">
                                <?php 
                                // echo esc_html__( gmdate( 'D', $start_date ), 'eventprime-event-calendar-management' ) . esc_html( ', ' . $event->fstart_date );
                                echo esc_html(wp_date( 'D', $start_date )) . ', ' . esc_html(wp_date( $event_listings_date_format, $start_date ));
                                ?>
                            </span><?php
                        }
                    } else{
                        if( ! $ep_functions->ep_show_event_date_time( 'em_start_date', $event ) ) {
                            if( ! empty( $event->em_event_date_placeholder ) ) {
                                if( $event->em_event_date_placeholder == 'tbd' ) {
                                    $tbd_icon_file = plugin_dir_url(__FILE__) .'partials/images/tbd-icon.png';?>
                                    <span class="ep-card-event-date-start ep-text-primary">
                                        <img src="<?php echo esc_url( $tbd_icon_file );?>" width="35" />
                                    </span><?php
                                } else{
                                    if( ! empty( $event->em_event_date_placeholder_custom_note ) ) {?>
                                        <span class="ep-card-event-date-start ep-text-primary">
                                            <?php echo esc_html( $event->em_event_date_placeholder_custom_note );?>
                                        </span><?php
                                    }
                                }
                            }
                        } else{
                            if( empty( $event->em_start_time ) || ! $ep_functions->ep_show_event_date_time( 'em_start_time', $event ) ) {?>
                                <span class="ep-card-event-date-start ep-text-primary hb">
                                    <?php 
                                    // echo esc_html__( gmdate( 'D', $event->em_start_date ), 'eventprime-event-calendar-management' ) . esc_html( ', ' . $event->fstart_date );
                                    echo esc_html(wp_date( 'D',$event->em_start_date )) . ', ' . esc_html(wp_date( $event_listings_date_format, $start_date ));
                                    ?>
                                </span><?php
                            } else{
                                if( ! empty( $event_date_time ) ) {?>
                                    <span class="ep-card-event-date-start ep-text-primary ll">
                                        <?php echo esc_html(wp_date('D', $start_date)) . ', ' . esc_html( $event_date_time );?>
                                    </span><?php
                                } else{?>
                                    <span class="ep-card-event-date-start ep-text-primary mm">
                                        <?php 
                                         echo esc_html( wp_date( 'D', $event->em_start_date ) ) . ', ' . esc_html(wp_date( $event_listings_date_format, $start_date ));
                                        ?>

                                    </span>
                                    <span class="ep-card-event-time-start ep-text-primary">
                                        <?php echo ', ' . esc_html( $ep_functions->ep_convert_time_with_format( $event->em_start_time ) );?>
                                    </span><?php
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    public function ep_event_add_event_price( $event, $view = '' ) 
    {
        $ep_functions = new Eventprime_Basic_Functions;

        // helper: true if numeric and exactly zero
        $is_zero = static function( $v ) {
            return isset($v) && is_numeric($v) && (float)$v == 0.0;
        };

        // helper: true if numeric and > 0
        $is_positive = static function( $v ) {
            return isset($v) && is_numeric($v) && (float)$v > 0.0;
        };

        // render price label (single place to keep logic consistent)
        $render_price = function( $price ) use ( $ep_functions, $is_zero, $is_positive ) {
            if ( $is_positive( $price ) ) {
                echo esc_html( $ep_functions->ep_price_with_position( (float)$price ) );
            } else {
                // treat 0, "0", "0.0", "0.00", null, '' as Free
                $ep_functions->ep_show_free_event_price( 0 );
            }
        };

        if ( ! empty( $view ) && $view === 'card' ) { ?>
            <div class="ep-event-list-price ep-text-dark ep-di-flex ep-align-items-center ep-mt-auto">
            <?php if ( $event && ! empty( $event->id ) && $event->em_enable_booking !== 'external_bookings' ) {
                if ( ! empty( $event->ticket_price_range ) ) { ?>
                    <span class="material-icons-outlined ep-align-middle ep-fs-5 ep-mr-1">confirmation_number</span>
                    <?php
                    $tpr = $event->ticket_price_range;
                    $multiple = isset($tpr['multiple']) && (int)$tpr['multiple'] === 1;
                    $min = isset($tpr['min']) ? $tpr['min'] : null;
                    $max = isset($tpr['max']) ? $tpr['max'] : null;
                    $price = isset($tpr['price']) ? $tpr['price'] : null;

                    if ( $multiple ) {
                        if ( isset($min, $max) && (float)$min == (float)$max ) { ?>
                            <span class="ep-fw-bold ep-ml-1"> <?php $render_price( $min ); ?> </span>
                        <?php } else { ?>
                            <?php esc_html_e( 'Starting from', 'eventprime-event-calendar-management' ); ?>
                            <span class="ep-fw-bold ep-ml-1">
                                <?php echo esc_html( $ep_functions->ep_price_with_position( $min ) ); ?>
                            </span>
                        <?php }
                    } else { ?>
                        <span class="ep-fw-bold ep-ml-1"> <?php echo ' '; $render_price( $price ); ?> </span>
                    <?php }
                } else {
                    echo '&nbsp;';
                }
            } ?>
            </div>
        <?php } else {
            if ( $event && ! empty( $event->id ) && $event->em_enable_booking !== 'external_bookings' ) {
                if ( ! empty( $event->ticket_price_range ) ) { ?>
                    <div class="ep-event-list-price ep-my-2 ep-text-dark ep-align-items-center ep-d-flex ep-justify-content-end">
                        <span class="material-icons-outlined ep-align-middle ep-fs-5 ep-mr-1">confirmation_number</span>
                        <?php
                        $tpr = $event->ticket_price_range;
                        $multiple = isset($tpr['multiple']) && (int)$tpr['multiple'] === 1;
                        $min = isset($tpr['min']) ? $tpr['min'] : null;
                        $max = isset($tpr['max']) ? $tpr['max'] : null;
                        $price = isset($tpr['price']) ? $tpr['price'] : null;

                        if ( $multiple ) {
                            if ( isset($min, $max) && (float)$min == (float)$max ) { ?>
                                <span class="ep-fw-bold ep-lh-0 ep-ml-1"> <?php echo ' '; $render_price( $min ); ?> </span>
                            <?php } else { ?>
                                <?php esc_html_e( 'Starting from', 'eventprime-event-calendar-management' ); ?>
                                <span class="ep-fw-bold ep-lh-0 ep-ml-1">
                                   <?php echo esc_html( $ep_functions->ep_price_with_position( $min ) ); ?>
                                </span>
                            <?php }
                        } else { ?>
                            <span class="ep-fw-bold ep-lh-0 ep-ml-1"> <?php echo ' '; $render_price( $price ); ?> </span>
                        <?php } ?>
                    </div>
                <?php }
            }
        }
    }


    public function ep_event_add_event_price_old( $event, $view = '' ) {
        $ep_functions = new Eventprime_Basic_Functions;
        if( ! empty( $view ) && $view == 'card' ) {?>
            <div class="ep-event-list-price ep-text-dark ep-di-flex ep-align-items-center ep-mt-auto"><?php
                if( $event && ! empty( $event->id ) && $event->em_enable_booking != 'external_bookings' ) { 
                    var_dump($event->ticket_price_range);
                    if ( ! empty( $event->ticket_price_range ) ) {?>
                        <span class="material-icons-outlined ep-align-middle ep-fs-5 ep-mr-1">confirmation_number</span><?php
                        if ( isset( $event->ticket_price_range['multiple'] ) && $event->ticket_price_range['multiple'] == 1 ) { 
                            if( $event->ticket_price_range['min'] == $event->ticket_price_range['max'] ) {?>
                                <span class="ep-fw-bold ep-ml-1"><?php 
                                    echo ' ';
                                    if( ! empty( $event->ticket_price_range['min'] ) ) {
                                        echo esc_html( $ep_functions->ep_price_with_position( $event->ticket_price_range['min'] ) );
                                    } else{
                                        $ep_functions->ep_show_free_event_price( $event->ticket_price_range['min'] );
                                    }?>
                                </span><?php
                            } else{?>
                                <?php esc_html_e( 'Starting from', 'eventprime-event-calendar-management' );?>
                                <span class="ep-fw-bold ep-ml-1">
                                    <?php echo ' ' . esc_html( $ep_functions->ep_price_with_position( $event->ticket_price_range['min'] ) ); ?>
                                </span><?php
                            }
                        } else { ?>
                            <span class="ep-fw-bold ep-ml-1"><?php
                                echo ' ';
                                if( isset($event->ticket_price_range['price']) && ! empty( $event->ticket_price_range['price'] ) ){
                                    echo esc_html( $ep_functions->ep_price_with_position( $event->ticket_price_range['price'] ) );
                                } else{
                                    $ep_functions->ep_show_free_event_price(0);
                                } ?>
                            </span><?php
                        }
                    } else{
                        echo '&nbsp;';
                    }
                }?>
            </div><?php
        } else{
            if( $event && ! empty( $event->id ) && $event->em_enable_booking != 'external_bookings' ) { 
                if ( ! empty( $event->ticket_price_range ) ) {?>
                    <div class="ep-event-list-price ep-my-2 ep-text-dark ep-align-items-center ep-d-flex ep-justify-content-end">
                        <span class="material-icons-outlined ep-align-middle ep-fs-5 ep-mr-1">confirmation_number</span><?php
                        if ( isset( $event->ticket_price_range['multiple'] ) && $event->ticket_price_range['multiple'] == 1 ) { 
                            if( $event->ticket_price_range['min'] == $event->ticket_price_range['max'] ) {?>
                                <span class="ep-fw-bold ep-lh-0 ep-ml-1"><?php 
                                    echo ' ';
                                    if( ! empty( $event->ticket_price_range['min'] ) ) {
                                        echo esc_html( $ep_functions->ep_price_with_position( $event->ticket_price_range['min'] ) );
                                    } else{
                                        $ep_functions->ep_show_free_event_price( $event->ticket_price_range['min'] );
                                    }?>
                                </span><?php
                            } else{?>
                                <?php esc_html_e( 'Starting from', 'eventprime-event-calendar-management' );?>
                                <span class="ep-fw-bold ep-lh-0 ep-ml-1">
                                    <?php echo ' ' . esc_html( $ep_functions->ep_price_with_position( $event->ticket_price_range['min'] ) ); ?>
                                </span><?php
                            }
                        } else { ?>
                            <span class="ep-fw-bold ep-lh-0 ep-ml-1"><?php
                                echo ' ';
                                if( isset($event->ticket_price_range['price'] ) && ! empty( $event->ticket_price_range['price'] ) ) {
                                    echo esc_html( $ep_functions->ep_price_with_position( $event->ticket_price_range['price'] ) );
                                } else{
                                    $ep_functions->ep_show_free_event_price(0 );
                                } ?>
                            </span><?php
                        } ?>
                    </div><?php
                }
            }
        }
    }

    
    
    /**
     * Add weather widget on the event detail page
     * 
     * @param object $venue Venue data.
     * 
     * @return void
     */
    public function ep_event_detail_add_weather_widget( $venue ) {
        $ep_functions = new Eventprime_Basic_Functions;
        //print_r($venue);die;
        $weather_api_key = $ep_functions->ep_get_global_settings( 'weather_api_key' );
        if( ! empty($weather_api_key  ) && !empty($venue) && isset($venue->em_locality) && !empty($venue->em_locality) ) {
            $api_key = $weather_api_key;
$city = $venue->em_locality;
$api_url = "http://api.weatherapi.com/v1/forecast.json?key={$api_key}&q={$city}&days=7&aqi=no&alerts=no";
$temp_unit = $ep_functions->ep_get_global_settings('weather_unit_fahrenheit');
$response = file_get_contents($api_url);
$data = json_decode($response);
//print_r($data);
if(!empty($temp_unit) && $temp_unit == 1)
{
    $currenttemp = $data->current->temp_f.'°F';
}
else
{
    $currenttemp = $data->current->temp_c.'°C';
}

?>

<div class="ep-weather-widget">
    
    <div class="ep-current-weather">
        <div class="ep-location-wrap">
            <div class="ep-location-name ep-fs-4 ep-fw-bold"><?php echo esc_html($data->location->name); ?> </div>
            <div class="ep-location-title ep-fs-6"><?php esc_html_e('Weather','eventprime-event-calendar-management');?></div>
        </div>
        <img src="https:<?php echo esc_attr($data->current->condition->icon); ?>" alt="<?php esc_attr_e('Current Weather Icon','eventprime-event-calendar-management');?>">
        <div class="ep-temp-wrap">
        <div class="ep-temp ep-fs-4 ep-fw-bold"><?php echo esc_html($currenttemp); ?></div>
        <div class="ep-desc ep-fs-6"><?php echo esc_html($data->current->condition->text); ?></div>
        </div>
    </div>

    <div class="ep-weather-forecast">
        <?php foreach($data->forecast->forecastday as $day): ?>
            <div class="ep-weather-day">
                <div><?php echo esc_html(date('D', strtotime($day->date))); ?></div>
                <img src="https:<?php echo esc_attr($day->day->condition->icon); ?>" alt="">
                <?php
                
                if(!empty($temp_unit) && $temp_unit == 1){
                    $maxtemp = $day->day->maxtemp_f.'°F';
                    $mintemp = $day->day->mintemp_f.'°F';
                }
                else
                {
                    $maxtemp = $day->day->maxtemp_c.'°C';
                    $mintemp = $day->day->mintemp_c.'°C';
                }
                ?>
                <div class="max-temp"><?php echo esc_html($maxtemp); ?></div>
                <div class="min-temp"><?php echo esc_html($mintemp); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div> <?php
        }
        else
        {                     
            if( ! empty( $venue ) && ! empty( $venue->em_place_id ) ) {
                $place_url = $ep_functions->fetch_url_content( 'https://forecast7.com/api/getUrl/' . $venue->em_place_id );

                if( empty( $place_url ) ) {
                    // call autocomplete api with state and country
                    if( ! empty( $venue->em_state ) && ! empty( $venue->em_country ) ) {
                        $autocomplete_api = 'https://forecast7.com/api/autocomplete/'.$venue->em_state.'/'.$venue->em_country.'/';
                        if( empty( $autocomplete_api ) ) {
                            $autocomplete_api = 'https://forecast7.com/api/autocomplete/'.$venue->em_state.', '.$venue->em_country.'/';
                        }
                        if( $autocomplete_api ) {
                            $autocomplete_data = $ep_functions->fetch_url_content( $autocomplete_api );
                            if( ! empty( $autocomplete_data ) ) {
                                $place_data = json_decode( $autocomplete_data );
                                if( ! empty( $place_data ) && ! empty( $place_data[0] ) ) {
                                    if( ! empty( $place_data[0]->place_id ) ) {
                                        $place_url = $ep_functions->fetch_url_content( 'https://forecast7.com/api/getUrl/' . $place_data[0]->place_id );
                                    }
                                }
                            }
                        }
                    }
                }
                $temp_unit = $ep_functions->ep_get_global_settings('weather_unit_fahrenheit');
                if( ! empty( $place_url ) ) 
                {
                    if(!empty($temp_unit) && $temp_unit == 1)
                    {
                        $place_url .='/?unit=us';
                    }
                    else
                    {
                        $place_url .='/';
                    }

                //echo 'https://forecast7.com/en/'.$place_url;
                ?>
                    <a class="weatherwidget-io" href="https://forecast7.com/en/<?php echo esc_html( $place_url );?>" data-label_1="<?php echo esc_html( $venue->em_locality );?>" data-label_2="WEATHER" data-theme="pure" ><?php echo esc_html( $venue->em_locality );?> <?php esc_html_e( 'WEATHER', 'eventprime-event-calendar-management' ); ?></a>
                    <script>
                        !function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src='https://weatherwidget.io/js/widget.min.js';fjs.parentNode.insertBefore(js,fjs);}}(document,'script','weatherwidget-io-js');
                    </script>
                        <?php
                } 
                else
                {
                    ?>
                    <div class="ep-alert ep-alert-warning ep-mt-3">
                        <?php esc_html_e( 'No data found.', 'eventprime-event-calendar-management' ); ?>
                    </div>
                        <?php
                }
            } 
            else
            {
                ?>
                <div class="ep-alert ep-alert-warning ep-mt-3">
                    <?php esc_html_e( 'No data found.', 'eventprime-event-calendar-management' ); ?>
                </div>
                    <?php
            }
        }
    }
    
    public function ep_add_body_class( $classes ) {
    	$class = 'theme-' . get_template();
    	if( is_array( $classes ) ) {
    		$classes[] = $class;
    	} else{
    		$classes .= ' ' . $class . ' ';
    	}
    	return $classes;
    }
    
    /**
     * Show booking status on the event views
     */
    public function ep_event_booking_count( $event ) {
        $ep_functions = new Eventprime_Basic_Functions;
        if( ! empty( $event ) ) {
            $event_booking_status_option = $ep_functions->ep_get_global_settings( 'event_booking_status_option' );
            if( ! empty( $event_booking_status_option ) ) {?>
                <div class="ep-text-small ep-event-booking-status ep-text-dark ep-d-flex ep-align-items-center ep-mt-2 ep-mb-3"><?php
                    if( ! empty( $event->em_enable_booking ) && $event->em_enable_booking != 'external_bookings' ) {
                        if( ! $ep_functions->check_event_has_expired( $event ) && ! empty( $event->all_tickets_data ) ) {
                            $enable_status = isset( $event->em_hide_booking_status ) ? $event->em_hide_booking_status : 0;
                            if( empty( $enable_status ) ) {
                                // show bragraphs
                                if( $event_booking_status_option == 'bargraph' ) {
                                    $total_tickets = $attendee_count = $width = 0; 
                                    $total_booking = $ep_functions->get_total_booking_number_by_event_id( $event->em_id );
                                    if( ! empty( $event->all_tickets_data ) ) {
                                        foreach( $event->all_tickets_data as $tickets ) {
                                            //$check_ticket_available = EventM_Factory_Service::check_for_ticket_available_for_booking( $tickets, $event );
                                            $total_tickets = $total_tickets + $tickets->capacity;
                                        }
                                    }
                                    if( $total_tickets > 0 ) {
                                        if( $total_booking > 0  && $total_tickets > 0 ) {
                                            $width = ( $total_booking / $total_tickets ) * 100;
                                            $width = number_format( (float)$width, 2 );
                                        }?>
                                        <div class="ep-event-ticket-progress ep-box-w-100">
                                            <div class="ep-event-ticket-count">
                                                <?php echo esc_html($total_booking).'/'.esc_html($total_tickets);?>
                                            </div>
                                            <div class="ep-event-ticket-progress-bar ep-progress">
                                                <div class="ep-progress-bar" style="width:<?php echo esc_attr($width).'%';?>"></div>
                                            </div>
                                        </div><?php
                                    }
                                }
                                // show ticket left
                                if( $event_booking_status_option == 'ticket_left' ) {
                                    $available_tickets = $ep_functions->get_event_available_tickets( $event );
                                    if( ! empty( $available_tickets ) ) {?>
                                        <span class="ep-text-muted">
                                            <?php esc_html_e( 'Hurry', 'eventprime-event-calendar-management' ); ?>, 
                                            <?php echo absint( $available_tickets );?> 
                                            <?php esc_html_e( 'tickets left!', 'eventprime-event-calendar-management' ); ?>
                                        </span><?php
                                    }
                                }
                            }
                        } else{
                            echo '';
                        }
                    } else{
                        echo '';
                    }?>
                </div><?php
            }
        }
    }
    
    public function ep_bulk_post_updated_messages_filter( $bulk_messages, $bulk_counts ) {
        $bulk_messages['em_event'] = array(
            'updated'   => _n( '%s event updated.', '%s events updated.', $bulk_counts['updated'] ),
            'deleted'   => _n( '%s event permanently deleted.', '%s events permanently deleted.', $bulk_counts['deleted'] ),
            'trashed'   => _n( '%s event moved to the Trash.', '%s events moved to the Trash.', $bulk_counts['trashed'] ),
            'untrashed' => _n( '%s event restored from the Trash.', '%s events restored from the Trash.', $bulk_counts['untrashed'] ),
        );
    
        return $bulk_messages;
    }
    
    public function ep_event_detail_right_event_dates_section( $event ) {
        $ep_functions = new Eventprime_Basic_Functions;
        if( ! empty( $event ) ) {?>
            <div class="ep-btn-group ep-ticket-btn-radio" role="group" aria-label="ep-ticket-radio"><?php
                if( empty( $event->post_parent ) ) {
                    $no_load = 'no-load';
                    if( count( $event->child_events ) > 0 ) {
                        $no_load = '';
                    }?>
                    <div class="ep-btn ep-ticket-btn ep-ticket-active ep-text-small ep-fw-bold ep-py-1 ep-px-2 ep-btn-outline-secondary ep-border-2 ep-rounded-1 ep_event_ticket_date_option" id="ep_child_event_id_<?php echo esc_attr( $event->id );?>"><?php echo esc_html( $ep_functions->ep_timestamp_to_date( $event->em_start_date, 'd M', 1 ) );?></div>
                    <?php if( count( $event->child_events ) > 0 ) {
                        $ev = 2;
                        foreach( $event->child_events as $events ) {
                            $event_link = $ep_functions->ep_get_custom_page_url('events_page', $events->em_id , 'event');
                            ?>
                            <a href="<?php echo esc_url($event_link); ?>" class="ep-btn ep-ticket-btn ep-text-small ep-fw-bold ep-py-1 ep-px-2 ep-btn-outline-secondary ep-border-2 ep-rounded-1 ep_event_ticket_date_option" id="ep_child_event_id_<?php echo esc_attr( $events->em_id );?>"><?php echo esc_html( $ep_functions->ep_timestamp_to_date( $events->em_start_date, 'd M', 1 ) );?></a><?php
                            $ev++;
                        }
                    }
                } else{
                    //$parent_event_data = $event_controller->get_single_event( $event->post_parent );
                    $parent_event_id = $event->post_parent;
                    $parent_event_start_date = get_post_meta( $parent_event_id, 'em_start_date', true );
                    $all_child_event_data = $ep_functions->ep_get_child_events( $parent_event_id, array( 'fields' => 'ids' ) );
                     //$all_child_event_data = $ep_functions->get_event_child_data_by_parent_id( $parent_event_id, array( 'em_start_date' ) );
                    if( ! empty( $all_child_event_data ) && count( $all_child_event_data ) > 0 ) { 
                        $event_link = $ep_functions->ep_get_custom_page_url('events_page', $parent_event_id , 'event');
                           
                        ?>
                        <a href="<?php echo esc_url($event_link);?>" class="ep-btn ep-ticket-btn  ep-text-small ep-fw-bold ep-py-1 ep-px-2 ep-btn-outline-secondary ep-border-2 ep-rounded-1 ep_event_ticket_date_option" id="ep_child_event_id_<?php echo esc_attr( $parent_event_id );?>">
                                 <?php echo esc_html( $ep_functions->ep_timestamp_to_date( $parent_event_start_date, 'd M', 1 ) );?>
                        </a><?php
                        $ev = 2;
                        foreach( $all_child_event_data as $child_events ) {
                            $event_link = $ep_functions->ep_get_custom_page_url('events_page', $child_events , 'event');
                           
                            $checked = '';
                            $em_start_date = get_post_meta( $child_events, 'em_start_date', true );
                            if( $child_events == $event->id ) 
                            { ?>
                                <div class="ep-btn ep-ticket-btn ep-ticket-active ep-text-small ep-fw-bold ep-py-1 ep-px-2 ep-btn-outline-secondary ep-border-2 ep-rounded-1 ep_event_ticket_date_option" id="ep_child_event_id_<?php echo esc_attr( $child_events );?>"><?php echo esc_html( $ep_functions->ep_timestamp_to_date( $em_start_date, 'd M', 1 ) );?></div>
                              <?php
                            }
                            else
                            {  
                            ?>
                                <a href="<?php echo esc_url($event_link);?>" class="ep-btn ep-ticket-btn  ep-text-small ep-fw-bold ep-py-1 ep-px-2 ep-btn-outline-secondary ep-border-2 ep-rounded-1 ep_event_ticket_date_option" id="ep_child_event_id_<?php echo esc_attr( $child_events );?>">
                                    <?php echo esc_html( $ep_functions->ep_timestamp_to_date( $em_start_date, 'd M', 1 ) );?>
                                </a><?php
                            }
                                
                           
                            $ev++;
                        }
                    }
                }?>
            </div><?php
            if( empty( $no_load ) ) {?>
                <div class="ep-move-left ep-position-absolute ep-cursor"><span class="material-icons-outlined">chevron_left</span></div>
                <div class="ep-move-right ep-position-absolute ep-cursor"><span class="material-icons-outlined">chevron_right</span></div><?php
            }
        }
    }

    // dequeue already enqueues scripts
    public function ep_dequeue_event_scripts( $scripts ) {
        if( ! empty( $scripts ) ) {
            foreach( $scripts as $script ) {
                wp_deregister_script( $script );
                wp_dequeue_script( $script );
            }
        }
    }
    
    // add loader on the page
    public function ep_add_loader_section( $default = 'none' ) {
        $style = 'style=display:none;';
        if( $default == 'show' ) {
            $style = 'style=display:flex;';
        }?>
        <div class="ep-event-loader" role="alert" aria-live="polite" <?php echo esc_attr( $style );?>>
            <div class="ep-event-loader-circles-wrap">
                <svg class="ep-event-loader-circle-icon ep-event-loader-circle-icon-dot ep-event-loader-circle-dot ep-event-loader-first" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><circle cx="7.5" cy="7.5" r="7.5"></circle></svg>
                <svg class="ep-event-loader-circle-icon ep-event-loader-circle-icon-dot ep-event-loader-circle-dot ep-event-loader-second" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><circle cx="7.5" cy="7.5" r="7.5"></circle></svg>
                <svg class="ep-event-loader-circle-icon ep-event-loader-circle-icon-dot ep-event-loader-circle-dot ep-event-loader-third" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><circle cx="7.5" cy="7.5" r="7.5"></circle></svg>
            </div>
        </div><?php
    }
    
    public function ep_add_internal_loader_section($default = 'none',$class='' )
    {
        $style = 'style=display:none;';
        if( $default == 'show' ) {
            $style = 'style=display:flex;';
        }?>
                <div class="ep-loader <?php echo esc_attr($class);?>" role="alert" aria-live="polite" <?php echo esc_attr( $style );?>>
            
        </div><?php
    }
    
     /**
	 * Custom Style
	 */
	public function ep_custom_styles() {
            $ep_functions = new Eventprime_Basic_Functions;
		$custom_css = $ep_functions->ep_get_global_settings( 'custom_css' );
		if ( false !== $custom_css ) {
			echo '<style type="text/css">' . esc_attr( $custom_css ) . '</style>';
		}
	}
        
        /**
     * Update parent event status
     */
    public function ep_update_parent_event_status( $post_id, $post ) {
        if( $post->post_type == 'em_event' && $post->post_status !== 'trash') {
            if( $post->post_status !== 'publish' ) {
                $notification = new EventM_Notification_Service;
                $postData = [ 'ID' => $post->ID, 'post_status' => 'publish' ];
                wp_update_post( $postData );
                if( get_post_meta( $post_id, 'em_frontend_submission', true ) == 1 && get_post_meta( $post_id, 'em_user_submitted', true ) == 1){
                    $notification->event_approved($post_id);
                }
            }
        }
    }
    
     /**
     * Update event data after save
     */
    public function ep_update_event_data_after_save( $post_id, $post ) {
        if( $post->post_type == 'em_event' && $post->post_status !== 'trash') {
            if( $post->post_status !== 'publish' ) {
                $postData = [ 'ID' => $post->ID, 'post_status' => 'publish' ];
                wp_update_post( $postData );
                if( get_post_meta( $post_id, 'em_frontend_submission', true ) == 1 && get_post_meta( $post_id, 'em_user_submitted', true ) == 1){
                    $notification = new EventM_Notification_Service;
                    $notification->event_approved($post_id);
                }
            }
            if( $post->post_status == 'private' ) {
                $postData = [ 'ID' => $post->ID, 'post_status' => 'private' ];
                wp_update_post( $postData );
            }
        }
    }
    
    
    
    /**
     * Add calendar icon
     */
    public function ep_event_add_calendar_icon( $event, $pag ) {?>
        <div class="ep-sl-event-action ep-cursor ep-position-relative ep-event-ical-action">
            <span class="material-icons-outlined ep-handle-share ep-button-text-color ep-mr-3 ep-cursor">calendar_month</span>
            <ul class="ep-event-share ep-m-0 ep-p-0" style="display:none;">
                <li class="ep-event-social-icon">
                    <a href="#" title="<?php esc_html_e( '+ iCal Export','eventprime-event-calendar-management' ); ?>" id="ep_event_ical_export" data-event_id="<?php echo esc_attr( $event->em_id );?>">
                        <?php esc_html_e( '+ iCal Export','eventprime-event-calendar-management' ); ?>
                    </a>
                </li>
            </ul>
        </div><?php
    }
    

    public function ep_frontend_event_publish($new_status, $old_status, $post){
        $em_frontend_submission = get_post_meta($post->ID, 'em_frontend_submission', true);
        $em_user_submitted = get_post_meta($post->ID, 'em_user_submitted', true);
        $em_user = get_post_meta($post->ID, 'em_user', true);
        if(!empty($em_frontend_submission) && !empty($em_user_submitted) && !empty($em_user)){
            if($new_status == 'publish' && $old_status !='publish'){
                $notification = new EventM_Notification_Service;
                $notification->event_approved($post->ID);
            }
        }
        
    }

    public function ep_apply_slug_rules()
    {
       global $wp_rewrite;
        $ep_functions = new Eventprime_Basic_Functions;
        $enable_seo_urls = $ep_functions->ep_get_global_settings( 'enable_seo_urls' );
        $permalink = $wp_rewrite->permalink_structure;
        if( isset( $enable_seo_urls ) && ! empty( $enable_seo_urls ) && !empty($permalink)){
            /*
            // em_event post type
            $event_slug = $ep_functions->ep_get_seo_page_url('event');
            $event_page_url = basename(get_permalink($ep_functions->ep_get_global_settings('events_page')));
            add_rewrite_rule("$event_slug/([^/]+)/?$",'index.php?pagename='.$event_page_url.'&event=$matches[1]','top');
            
            //em_performer post type
            $performer_slug = $ep_functions->ep_get_seo_page_url('performer');
            $performer_page_url = basename(get_permalink($ep_functions->ep_get_global_settings('performers_page')));
            add_rewrite_rule("$performer_slug/([^/]+)/?$",'index.php?pagename='.$performer_page_url.'&performer=$matches[1]','top');
            */
            //em_event_organizer taxonomy
            $organizer_slug = $ep_functions->ep_get_seo_page_url('organizer');
            $organizer_page_url = basename(get_permalink($ep_functions->ep_get_global_settings('event_organizers')));
            add_rewrite_rule('^'.$organizer_slug.'/([^/]+)/?$','index.php?pagename='.$organizer_page_url.'&organizer=$matches[1]','top');
            
            //em_venue taxonomy
            $venue_slug = $ep_functions->ep_get_seo_page_url('venue');
            $venue_page_url = basename(get_permalink($ep_functions->ep_get_global_settings('venues_page')));
            add_rewrite_rule('^'.$venue_slug.'/([^/]+)/?$','index.php?pagename='.$venue_page_url.'&venue=$matches[1]','top');
            
            //em_venue taxonomy
            $event_type_slug = $ep_functions->ep_get_seo_page_url('event-type');
            $event_type_url = basename(get_permalink($ep_functions->ep_get_global_settings('event_types')));
            add_rewrite_rule('^'.$event_type_slug.'/([^/]+)/?$','index.php?pagename='.$event_type_url.'&event_type=$matches[1]','top');
            
        }
        //print_r($_GET);
        
    }

    public function ep_filter_query_vars($query_vars)
    {
        //$query_vars[] = 'event';
        //$query_vars[] = 'performer';
        $query_vars[] = 'organizer';
        $query_vars[] = 'event_type';
        $query_vars[] = 'venue';
        return $query_vars;
    }

    public function ep_handle_logout_redirect() {
        $ep_functions = new Eventprime_Basic_Functions(); 
        $login_page = $ep_functions->ep_get_global_settings( 'login_page' );
        return esc_url( get_permalink( $login_page ) );
    }
    
    public function eventprime_checkout_total_html_block($total_price,$total_tickets,$event_id,$extra)
    {
        $html_generator = new Eventprime_html_Generator;
        $html_generator->eventprime_checkout_total_html($total_price,$total_tickets,$event_id,$extra);   
        
    }
    
    public function ep_remove_post_navigation($output)
    {
        if (is_singular('em_event')) {
            return ''; // Remove post navigation for 'em_event'
        }
        return $output;
    }
    
    public function ep_remove_post_navigation_action()
    {
        if (is_singular('em_event')) {
            remove_action('wp_footer', 'the_post_navigation', 10);
        }
    }
    
    /**
    * Download iCal file
    */
   public function get_ical_file() {
        
           $ep_functions = new Eventprime_Basic_Functions();
           if( ! is_admin() ) {
                   if( isset( $_REQUEST['event'] ) ) {
                           $event_id = absint( $_REQUEST['event'] );
                           if( ! empty( $event_id ) ) {
                                   if( isset( $_REQUEST['download'] ) ) {
                                           $download_format = sanitize_text_field( $_REQUEST['download'] );
                                           if ($download_format === 'ical') {
                                                   $event = $ep_functions->get_single_event( $event_id );
                                                   $event_url = $event->event_url;
                                                   $event_content = preg_replace('#<a[^>]*href="((?!/)[^"]+)">[^<]+</a>#', '$0 ( $1 )', $event->description);
                                                   $event_content = str_replace("<p>", "\\n", $event_content);
                                                   if(!empty($event_content))
                                                   {
                                                        $event_content = strip_shortcodes(strip_tags($event_content));
                                                   }
                                                   $event_content = str_replace("\r\n", "\\n", $event_content);
                                                   $event_content = str_replace("\n", "\\n", $event_content);
                                                   $event_content = preg_replace('/(<script[^>]*>.+?<\/script>|<style[^>]*>.+?<\/style>)/s', '', $event_content);

                                                   $gmt_offset_seconds = $ep_functions->ep_gmt_offset_seconds( $event->em_start_date );
                                                   $time_format = ( $event->em_all_day == 1 ) ? 'Ymd' : 'Ymd\\THi00\\Z';

                                                   $crlf = "\r\n";

                                                   $ical  = "BEGIN:VCALENDAR".$crlf;
                                                   $ical .= "VERSION:2.0".$crlf;
                                                   $ical .= "METHOD:PUBLISH".$crlf;
                                                   $ical .= "CALSCALE:GREGORIAN".$crlf;
                                                   $ical .= "PRODID:-//WordPress - EPv".EVENTPRIME_VERSION."//EN".$crlf;
                                                   $ical .= "X-ORIGINAL-URL:".home_url().'/'.$crlf;
                                                   $ical .= "X-WR-CALNAME:".get_bloginfo('name').$crlf;
                                                   $ical .= "X-WR-CALDESC:".get_bloginfo('description').$crlf;
                                                   $ical .= "REFRESH-INTERVAL;VALUE=DURATION:PT1H".$crlf;
                                                   $ical .= "X-PUBLISHED-TTL:PT1H".$crlf;
                                                   $ical .= "X-MS-OLK-FORCEINSPECTOROPEN:TRUE".$crlf;

                                                   $ical .= "BEGIN:VEVENT".$crlf;
                                                   $ical .= "CLASS:PUBLIC".$crlf;
                                                   $ical .= "UID:EP-".md5( strval( $event->em_id ) )."@".$ep_functions->ep_get_site_domain().$crlf;
                                                   $ical .= "DTSTART:".gmdate( $time_format, ( $ep_functions->ep_convert_event_date_time_to_timestamp( $event, 'start' ) ) ).$crlf;
                                                   $ical .= "DTEND:".gmdate( $time_format, ( $ep_functions->ep_convert_event_date_time_to_timestamp( $event, 'end' ) ) ).$crlf;
                                                   $ical .= "DTSTAMP:".get_the_date( $time_format, $event->em_id ).$crlf;
                                                   $ical .= "CREATED:".get_the_date( 'Ymd', $event->em_id ).$crlf;
                                                   $ical .= "LAST-MODIFIED:".get_the_modified_date( 'Ymd', $event->em_id ).$crlf;
                                                   $ical .= "SUMMARY:".html_entity_decode( $event->name, ENT_NOQUOTES, 'UTF-8' ).$crlf;
                                                   $ical .= "DESCRIPTION:".html_entity_decode( $event_content, ENT_NOQUOTES, 'UTF-8' ).$crlf;
                                                   $ical .= "X-ALT-DESC;FMTTYPE=text/html:".html_entity_decode( $event_content, ENT_NOQUOTES, 'UTF-8' ).$crlf;
                                                   $ical .= "URL:".$event_url.$crlf;

                                                   if ( ! empty( $event->venue_details ) ) {
                                                           $ical .= "LOCATION:".trim(strip_tags($event->venue_details->em_address)).$crlf;
                                                   }

                                                   $cover_image_id = get_post_thumbnail_id( $event->em_id );
                                                   if ( ! empty( $cover_image_id ) && $cover_image_id > 0 ) {
                                                           $ical .= "ATTACH;FMTTYPE=".get_post_mime_type( $cover_image_id ).":".$event->image_url.$crlf;
                                                   }

                                                   $ical .= "END:VEVENT".$crlf;
                                                   $ical .= "END:VCALENDAR";

                                                   header('Content-type: application/force-download; charset=utf-8');
                                                   header('Content-Disposition: attachment; filename="ep-event-'.$event->id.'.ics"');

                                                   echo $ical;
                                                   exit;
                                           }
                                   }
                           }
                   }
           }
   }

   public function ep_gdpr_consent_checkbox($args)
   {
       $ep_functions = new Eventprime_Basic_Functions(); 
       $enable_gdpr = $ep_functions->ep_get_global_settings( 'enable_gdpr_tools' );
       $show_checkbox = $ep_functions->ep_get_global_settings('show_gdpr_consent_checkbox');
       $privacy_default  = !empty(get_privacy_policy_url()) ? get_privacy_policy_url(): site_url().'/privacy-policy';
       //var_dump($privacy_default);
       if($enable_gdpr==1 && $show_checkbox==1)
       {
           $privacy_policy = $ep_functions->ep_get_global_settings('gdpr_privacy_policy_url');
           $gdpr_text = $ep_functions->ep_get_global_settings('gdpr_consent_text') ?? esc_html__("I agree to the site's Privacy Policy.",'eventprime-event-calendar-management');
           $privacy_policy_url = !empty($privacy_policy)?$privacy_policy:$privacy_default;
           
            ?>
            <div class="ep-form-row ep-form-group ep-text-small ep-mb-3">
                <div class="ep-box-col-12">
                    <label for="ep_gdpr_consent" class="ep-form-label ep-checkbox-inline ep-d-flex ep-text-small">
                        <input type="checkbox" name="ep_gdpr_consent" id="ep_gdpr_consent" value="1" class="ep-form-input ep-input-checkbox ep-mr-1" required>
                        <?php echo esc_html(stripslashes($gdpr_text)); ?> <a href="<?php echo esc_url($privacy_policy_url);?>" target="_blank" class="ep-ml-1"><?php esc_html_e('View','eventprime-event-calendar-management');?></a>

                    </label>  
                </div>
                 <div class="ep-error-message ep-box-col-12" id="ep_gdpr_consent_error"></div>                               
            </div>
            
            <?php
       }
   }
   
   public function ep_show_gdpr_badge_on_footer()
   {
       if ( post_type_exists( 'em_event' ) ) {
           $this->load_gdpr_badge();
        }
   }
   




}

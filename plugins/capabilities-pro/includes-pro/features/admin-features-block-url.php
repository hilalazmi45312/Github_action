<?php
namespace PublishPress\Capabilities;

class AdminFeaturesBlockUrl {
    private static $instance = null;

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new AdminFeaturesBlockUrl();
        }

        return self::$instance;
    }

    function __construct() {
        //add block by url to admin features element
        add_filter('pp_capabilities_admin_features_elements', [$this, 'blockUrlElements'], 50);
        //add block by url section icon
        add_filter('pp_capabilities_admin_features_icons', [$this, 'blockUrlSectionIcon']);
        //add block by url section title
        add_filter('pp_capabilities_admin_features_titles', [$this, 'blockUrlSectionTitle']);
        //add block by url action
        add_filter('pp_capabilities_admin_features_actions', [$this, 'blockUrlSectionAction']);
        //add form element to admin features table bottom
        add_action('pp_capabilities_admin_features_blockedbyurl_before_subsection_tr', [$this, 'blockUrlAddForm']);
        //ajax handler for url block new entry submission
        add_action('wp_ajax_ppc_submit_feature_blocked_url_by_ajax', [$this, 'blockUrlNewEntryAjaxHandler']);
        //ajax handler for deleting blocked url item
        add_action('wp_ajax_ppc_delete_feature_blocked_url_item_by_ajax', [$this, 'blockUrlDeleteItemAjaxHandler']);
        //block access to url pages
        add_action('ppc_admin_feature_restriction', [$this, 'blockUrlRestrictPages']);
    }

    /**
     * Fetch admin features blocked url options.
     *
     * @return mixed
     *
     * @since 2.3.1
     */
    public static function getData()
    {
        $data = (array)get_option('ppc_admin_feature_block_url_custom_data');
        $data = array_filter($data);

        if (empty($data) && empty(get_option('ppc_admin_feature_block_url_demo_installed'))) {
            $data = [];
            //add demo data 1
            $element_id = uniqid(true) . 11;
            $data[$element_id]= ['label' => __('Block access to Plugins screen via direct link', 'capabilities-pro'), 'elements' => admin_url('plugins.php')];
            //add demo data 2
            $element_id = uniqid(true) . 22;
            $data[$element_id]= ['label' => __('Block access to Profile screen via direct link', 'capabilities-pro'), 'elements' => admin_url('profile.php')];
            //add demo data 3
            $element_id = uniqid(true) . 33;
            $data[$element_id]= ['label' => __('Block access to Media Library via direct link', 'capabilities-pro'), 'elements' => ''. admin_url('upload.php').', '. admin_url('media-new.php').''];
            //add demo data 4
            $element_id = uniqid(true) . 44;
            $data[$element_id]= ['label' => __('Block access to Themes screen via direct link', 'capabilities-pro'), 'elements' => admin_url('themes.php')];
            //add demo data 5
            $element_id = uniqid(true) . 55;
            $data[$element_id]= ['label' => __('Block access to Users and Add New User screen via direct link', 'capabilities-pro'), 'elements' => ''. admin_url('users.php').', '. admin_url('user-new.php').''];
            update_option('ppc_admin_feature_block_url_custom_data', $data);
            update_option('ppc_admin_feature_block_url_demo_installed', 1);
        }
  
        return $data;
    }


    /**
     * Admin features icon filter
     *
     * @param array $icons admin features screen elements
     *
     * @return array $icons updated icon list
     *
     * @since 2.3.1
     */
    function blockUrlSectionIcon($icons) {

        $icons['blockedbyurl']     = 'admin-links';

        return $icons;
    }


    /**
     * Admin features title filter
     *
     * @param array $titles
     *
     * @return array $titles
     *
     * @since 2.3.1
     */
    function blockUrlSectionTitle($titles) {

        $titles['Blocked by URL']     = __('Block by URL', 'capabilities-pro');

        return $titles;
    }


    /**
     * Admin features action filter
     *
     * @param array $actions
     *
     * @return array $actions
     *
     * @since 2.18.2
     */
    function blockUrlSectionAction($action) {

        $action['Blocked by URL']     = 'ppc_blocked_url';

        return $action;
    }


    /**
     * Block by url admin features element filter
     *
     * @param array $element admin features screen elements
     *
     * @return array
     *
     * @since 2.3.1
     */
    function blockUrlElements($elements) {
        $data = self::getData();
        $added_element = [];

        if (count($data) > 0) {
            foreach ($data as $name => $restrict_data) {
                $added_element[$name] = [
                    'label'          => $restrict_data['label'],
                    'action'         => 'ppc_blocked_url',
                    'elements'       => $restrict_data['elements'],
                    'custom_element' => true,
                    'button_class'   => 'ppc-custom-features-url-delete red-pointer',
                    'edit_class'     => 'ppc-custom-features-url-edit',
                    'button_data_id' => $name,
                    'element_label'  => $restrict_data['label'],
                    'element_items'  => self::cleanCustomUrl($restrict_data['elements']),
                ];
            }
        }

        $elements['Blocked by URL'] = $added_element;

        return $elements;
    }



    /**
     * Add form element to admin features table bottom
     *
     * @since 2.3.1
     */
    public function blockUrlAddForm() {
        ?>
        <tr class="ppc-menu-row child-menu blockedbyurl">
            <td colspan="2" class="form-td">
                <table class="editor-features-custom admin-features-block-url-form">
                <tr class="ppc-menu-row parent-menu ppc-add-custom-row-header">
                    <td>
                        <p class="cme-subtext"><?php esc_html_e('Enter URL to be blocked by role:', 'capabilities-pro');?>
                        </p>
                        <p class="editing-custom-item blockedbyurl">
                            <strong><?php esc_html_e('Editing:', 'capabilities-pro'); ?></strong>
                            <span class="title"></span>
                        </p>
                        </h4>
                    </td>
                </tr>

                <tr class="ppc-add-custom-row-body">
                    <td>
                        <table class="wp-list-table widefat fixed ppc-custom-features-table blockedbyurl">

                        <tr class="field-row">
                            <th scope="row">
                                <?php esc_html_e('Title', 'capabilities-pro'); ?>: <font color="red">*</font>
                            </th>
                            <td>
                                <input class="form-label ppc-feature-block-url-new-name" type="text"/><br>
                                <span class="description">
                                    <?php esc_html_e('Enter the name/label to identify the element under Blocked by URL section of this screen.', 'capabilities-pro'); ?>
                                </span>
                            </td>
                        </tr>

                        <tr class="field-row">
                            <th scope="row">
                                <?php esc_html_e('URLs', 'capabilities-pro'); ?>: <font color="red">*</font>
                            </th>
                            <td>
                                <textarea class="form-element ppc-feature-block-url-new-link"></textarea><br>
                                <span class="description">
                                <?php
                                    $sample_url_one = admin_url('plugins.php');
                                    $sample_url_two = admin_url('profile.php');
                                    printf(esc_html__('Separate multiple urls by comma. (e.g, %1$s, %2$s).', 'capabilities-pro'), esc_url_raw($sample_url_one), esc_url_raw($sample_url_two)); ?>
                                </span>
                            </td>
                        </tr>

                        <tr class="field-row">
                            <td colspan="2">
                                <input type="hidden" class="custom-edit-id" value="">
                                <input class="ppc-feature-submit-form-nonce" type="hidden"
                                    value="<?php echo esc_attr(wp_create_nonce('ppc-custom-feature-nonce')); ?>"/>
                                <div class="custom-item-submit-buttons">
                                    <div class="cancel-custom-features-item-edit button button-secondary" data-section="blockedbyurl">
                                        <?php esc_html_e('Cancel Edit', 'capabilities-pro'); ?>
                                    </div>

                                    <button type="button" class="submit-button ppc-feature-block-url-new-submit button button-secondary" data-add="<?php esc_html_e('Add New', 'capabilities-pro'); ?>" data-edit="<?php esc_html_e('Save Edit', 'capabilities-pro'); ?>">
                                        <?php esc_html_e('Add New', 'capabilities-pro'); ?>
                                    </button>
                                </div>
                                <span class="ppc-feature-post-loader spinner"></span>
                                <div class="ppc-post-features-note"></div>

                        </td></tr>
                        </table>

                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <?php
    }

    /**
     * Ajax handler for deleting blocked url item
     *
     * @since 3.3.1
     */
    public static function blockUrlNewEntryAjaxHandler()
    {
        $response['status']  = 'error';
        $response['message'] = __('An error occured!', 'capabilities-pro');
        $response['content'] = '';

        $custom_label   = isset($_POST['custom_label']) ? sanitize_text_field($_POST['custom_label']) : '';
        $custom_link    = isset($_POST['custom_link']) ? sanitize_textarea_field($_POST['custom_link']) : '';
        $security       = isset($_POST['security']) ? sanitize_key($_POST['security']) : '';
        $item_id        = isset($_POST['item_id']) ? sanitize_key($_POST['item_id']) : '';

        if (!wp_verify_nonce($security, 'ppc-custom-feature-nonce')) {
            $response['message'] = __('Invalid action. Reload this page and try again.', 'capabilities-pro');
        } elseif (empty(trim($custom_label)) || empty(trim($custom_link))) {
            $response['message'] = __('All fields are required.', 'capabilities-pro');
        } else {
            $element_id       = (!empty($item_id)) ? $item_id : uniqid(true);
            $data             = self::getData();
            $data[$element_id]= ['label' => $custom_label, 'elements' => $custom_link];
            update_option('ppc_admin_feature_block_url_custom_data', $data);

            $response['message'] = (!empty($item_id)) ? __('Custom item updated successfully', 'capabilities-pro') : __('New custom item added successfully', 'capabilities-pro');
            $response['status']  = 'success';

            $response_content    = '<tr class="ppc-menu-row child-menu ppc-menu-overlay-item blockedbyurl custom-item-'. $element_id .'">

                <td class="restrict-column ppc-menu-checkbox">
                    <input id="check-item-'. $element_id .'" class="check-item" type="checkbox" name="capsman_disabled_admin_features[]" checked value="ppc_blocked_url||' . $element_id . '">
                </td>

                <td class="menu-column ppc-menu-item custom-item-row ppc-flex">
                    <div class="ppc-flex-item">
                        <div>
                            <label for="check-item-'. $element_id .'">
                                <span class="menu-item-link">
                                <strong><i class="dashicons dashicons-arrow-right"></i> ' . $custom_label . ' </strong></span>
                            </label>
                        </div>
                        <div class="custom-item-output">
                            <div class="custom-item-display">
                                ' . self::cleanCustomUrl($custom_link) . '
                            </div>
                        </div>
                    </div>
                    <div class="ppc-flex-item">
                        <div class="button view-custom-item">' . esc_html__('View', 'capabilities-pro') .'</div>
                        <div class="button edit-features-custom-item"
                        data-section="blockedbyurl"
                        data-label="'. esc_attr($custom_label) .'"
                        data-element="'. esc_attr($custom_link) .'"
                        data-id="'. esc_attr($element_id) .'">
                        '. esc_html__('Edit', 'capabilities-pro') .'
                        </div>
                            <div
                                class="button ppc-custom-features-url-delete red-pointer feature-red"
                                data-id="'. $element_id .'">
                                '. esc_html__('Delete', 'capabilities-pro') .'
                            </div>
                        </div>
                    </div>
                </td>
            </tr>';

            $response['content'] = $response_content;
        }

        wp_send_json($response);
    }

    /**
     * Delete custom added post features item ajax callback.
     *
     * @since 2.1.1
     */
    public static function blockUrlDeleteItemAjaxHandler()
    {
        $response = [];
        $response['status']  = 'error';
        $response['message'] = __('An error occured!', 'capabilities-pro');
        $response['content'] = '';

        $delete_id     = isset($_POST['delete_id']) ? sanitize_key($_POST['delete_id']) : '';
        $security      = isset($_POST['security']) ? sanitize_key($_POST['security']) : '';

        if (!wp_verify_nonce($security, 'ppc-custom-feature-nonce')) {
            $response['message'] = __('Invalid action. Reload this page and try again.', 'capabilities-pro');
        } elseif (empty(trim($delete_id))) {
            $response['message'] = __('Invalid request!.', 'capabilities-pro');
        } else {
            $data = self::getData();
            if (array_key_exists($delete_id, $data)) {
                unset($data[$delete_id]);
                update_option('ppc_admin_feature_block_url_custom_data', $data);
            }
            $response['status']  = 'success';
            $response['message'] = __('Selected item deleted successfully', 'capabilities-pro');
        }

        wp_send_json($response);
    }

    /**
     * Block access to url pages
     *
     * @param array $disabled_elements
     *
     * @since 3.3.1
     */
    public static function blockUrlRestrictPages($disabled_elements)
    {
        if(!is_admin()){//this feature block is only restricted to admin area
            return;
        }

        //return if it's ajax request
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        //Block people from locking themselves out of "Admin Features" #278
        if (current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'pp-capabilities-admin-features') {
            return;
        }

        //get element related to block url alone
        $data_key = 'ppc_blocked_url';
        $ppc_blocked_url    = array_filter(
			$disabled_elements,
			function($value, $key) use ($data_key) {return strpos($value, $data_key) === 0;}, ARRAY_FILTER_USE_BOTH
		);

        if(count($ppc_blocked_url) > 0){
            $data = self::getData();
            $blocked_urls = [];
            foreach($ppc_blocked_url as $blocked_element){
                $blocked_element = str_replace($data_key.'||', '', $blocked_element);
                if (array_key_exists($blocked_element, $data)) {
                    $blocked_urls[] = explode (",", $data[$blocked_element]['elements']);//merge multiple url into array
                }
            }

            if ($blocked_urls) {
	            //merge all array into one
	            $blocked_urls = call_user_func_array('array_merge', $blocked_urls);

	            //trim any excess white space in the array values
	            $blocked_urls = array_map('trim', $blocked_urls);

                $page_access_forbidden = false;
                foreach($blocked_urls as $blocked_url){
                    $current_url_data = self::parsePageUrlData(self::currentPageUrl());
                    $blocked_url_data = self::parsePageUrlData($blocked_url);
                    $interceptions = array_intersect($current_url_data, $blocked_url_data);

                    //Stop users from adding characters to avoid URL blocks #279
                    if (array_values($blocked_url_data) === array_values($interceptions)) {
                        $page_access_forbidden = true;
                    }
                }

	            //block access to current page if part of
	            if ($page_access_forbidden){
	                $forbidden = esc_attr__('You do not have permission to access this page.', 'capabilities-pro');
	                wp_die(esc_html($forbidden));
                }
            }

        }
    }


    /**
     * Parse URL and return it data
     *
     * @param string  $url
     * @return array $url_data
     */
    public static function parsePageUrlData($url)
    {
        $parts = parse_url($url);
        $url_data = [];

        if (isset($parts['host'])) {
            $url_data[] = $parts['host'];
        }

        if (isset($parts['path'])) {
            $url_data[] = $parts['path'];
        }

        if (isset($parts['query'])) {
            $url_data = array_merge($url_data, explode("&", $parts['query']));
        }

        return $url_data;
    }

    /**
     * Clean custom URL by removing website link from it
     *
     * @param string $urls
     *
     * @return string
     *
     * @since 3.3.1
     */
    public static function cleanCustomUrl($urls)
    {
        $home_url = home_url();

        return str_replace($home_url, '', $urls);
    }


    /**
     * Rereive current page url
     *
     * @return string
     *
     * @since 3.3.1
     */
    public static function currentPageUrl()
    {
        if (!empty($_SERVER['HTTP_HOST']) && !empty($_SERVER['REQUEST_URI'])) {
            return esc_url_raw((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        } else {
            return admin_url('');
        }
    }

}

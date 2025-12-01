<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */



class Eventprime_Basic_Functions {
    
    //private $global_settings;
    
    public function __construct() {
        //$this->global_settings = get_option('em_global_settings');
    }

    public function eventprime_check_is_ep_dashboard_page() {
        $page = false;
        global $pagenow;
        //print_r($pagenow);die;
        $post_type = filter_input(INPUT_GET, 'post_type');
        $post = filter_input(INPUT_GET, 'post');

        /** @var type $filter */
        if (isset($post_type) && $post_type == 'em_event') {
            $page = 'events';
            $post = filter_input(INPUT_GET, 'page');
            $taxonomy = filter_input(INPUT_GET, 'taxonomy');
            if ($pagenow == 'post-new.php') {
                $page = 'event_edit';
            } elseif (isset($taxonomy) && !empty($taxonomy)) {
                $page = $taxonomy;
            } elseif (isset($post) && !empty($post)) {
                $page = $post;
            }
        } 
        elseif (isset($post_type) && $post_type == 'em_performer') {
            $page = 'performers';
            $post = filter_input(INPUT_GET, 'page');
            if ($pagenow == 'post-new.php') {
                $page = 'performer_edit';
            } elseif (isset($post) && !empty($post)) {
                $page = $post;
            }
        } elseif (isset($post_type) && $post_type == 'em_booking') {
            $page = 'bookings';
            $post = filter_input(INPUT_GET, 'page');
            if ($pagenow == 'post-new.php') {
                $page = 'booking_edit';
            } elseif (isset($post) && !empty($post)) {
                $page = $post;
            }
        } elseif ($pagenow == 'post.php' && isset($post) && !empty($post)) {
            $post_type = get_post_type($post);
            if ($post_type == 'em_event') {
                $page = 'event_edit';
            } elseif ($post_type == 'em_performer') {
                $page = 'performer_edit';
            } elseif ($post_type == 'em_booking') {
                $page = 'booking_edit';
            }
        }
        elseif(!isset($post) || empty($post))
        {
            $page = filter_input(INPUT_GET, 'page');
        }

        return $page;
    }

    public function ep_get_activate_extensions_free_paid() {
        $free = array(
            'Eventprime_Event_Import_Export',
            'Eventprime_Woocommerce_Integration',
            'Eventprime_Elementor_Integration',
            'Eventprime_Zapier_Integration',
            'Eventprime_Demo_Data'
        );
        $paid = array(
            'Eventprime_Attendees_List',
            'Eventprime_Live_Seating',
            'Eventprime_Event_Invoices',
            'Eventprime_Event_Coupons',
            'Eventprime_Guest_Booking',
            'Eventprime_Event_Sponsor',
            'Eventprime_Admin_Attendee_Booking',
            'Eventprime_List_Widgets',
            'Eventprime_Event_Tickets',
            'Eventprime_Advanced_Reports',
            'Eventprime_Advanced_Checkout_Fields',
            'Eventprime_Ratings_And_Reviews',
            'Eventprime_Event_Feedback',
            'Eventprime_RSVP',
            'Eventprime_Twilio_Text_Notification',
            'Eventprime_Event_Mailpoet',
            'Eventprime_Zoom_Meetings',
            'Eventprime_Mailchimp_Integration',
            'Eventprime_Event_Stripe',
            'Eventprime_Offline',
            'Eventprime_Woocommerce_Checkout_Integration',
            'Eventprime_Advanced_Live_Seating',
            'Eventprime_Attendee_Event_Check_In',
            'EventPrime_Waiting_List',
            'Eventprime_Honeypot_Integration',
            'Eventprime_Turnstile_Antispam',
            'Eventprime_Event_Reminder_Emails',
        );
        $free_exist = array();
        foreach ($free as $fc) {
            if (class_exists($fc)) {
                $free_exist[] = $fc;
            }
        }

        $paid_exist = array();
        foreach ($paid as $pc) {
            if (class_exists($pc)) {
                $paid_exist[] = $pc;
            }
        }

        return array(
            'free' => $free_exist,
            'paid' => $paid_exist,
        );
    }

    public function ep_get_custom_page_url($page, $id = null, $slug = null, $type = 'post', $taxonomy = '') 
    {
        global $wp_rewrite;
        $url = get_permalink($this->ep_get_global_settings($page));
        $permalink = $wp_rewrite->permalink_structure;
        if (!empty($id)) {
            if (!empty($slug)) {
                $url = add_query_arg($slug, $id, $url);
            }
            $enable_seo_urls = $this->ep_get_global_settings('enable_seo_urls');
            if (!empty($enable_seo_urls) && !empty($permalink)) {
                $url = get_permalink($id);
                if ($type == 'term') {
                    if (empty($taxonomy)) {
                        $taxonomy = get_term($id)->taxonomy;
                    }
                    $url = get_term_link($id, $taxonomy);
                }
            }
        }
        return $url;
    }
    
    

    public function ep_get_activate_extensions() {
        $extensions = array(
            'Eventprime_Event_Import_Export',
            'Eventprime_Woocommerce_Integration',
            'Eventprime_Elementor_Integration',
            'Eventprime_Attendees_List',
            'Eventprime_Live_Seating',
            'Eventprime_Event_Invoices',
            'Eventprime_Event_Coupons',
            'Eventprime_Guest_Booking',
            'Eventprime_Event_Sponsor',
            'Eventprime_Admin_Attendee_Booking',
            'Eventprime_List_Widgets',
            'Eventprime_Event_Tickets',
            'Eventprime_Advanced_Reports',
            'Eventprime_Advanced_Checkout_Fields',
            'Eventprime_Ratings_And_Reviews',
            'Eventprime_Event_Feedback',
            'Eventprime_RSVP',
            'Eventprime_Twilio_Text_Notification',
            'Eventprime_Event_Mailpoet',
            'Eventprime_Zoom_Meetings',
            'Eventprime_Zapier_Integration',
            'Eventprime_Mailchimp_Integration',
            'Eventprime_Event_Stripe',
            'Eventprime_Offline',
            'Eventprime_Woocommerce_Checkout_Integration',
            'Eventprime_Advanced_Live_Seating',
            'Eventprime_Attendee_Event_Check_In',
            'EventPrime_Waiting_List',
            'Eventprime_Honeypot_Integration',
            'Eventprime_Turnstile_Antispam',
            'Eventprime_Event_Reminder_Emails',
            'Eventprime_Demo_Data'
        );
        $activate = array();
        foreach ($extensions as $extension) {
            if (class_exists($extension)) {
                $activate[] = $extension;
            }
        }
        return $activate;
    }
    
    public function get_status(){
        $status = array(
		'completed' => 'Completed',
		'cancelled' => 'Cancelled',
		'pending'   => 'Pending',
		'draft'     => 'Draft',
		'refunded'  => 'Refunded',
		'publish'   => 'Published',
	    );
        
        return $status;
    }

    public function ep_get_booking_status() {
        $status = array(
            'completed' => esc_html__('Completed', 'eventprime-event-calendar-management'),
            'cancelled' => esc_html__('Cancelled', 'eventprime-event-calendar-management'),
            'pending'   => esc_html__('Pending', 'eventprime-event-calendar-management'),
            'refunded'  => esc_html__('Refunded', 'eventprime-event-calendar-management'),
            'failed'    => esc_html__('Failed', 'eventprime-event-calendar-management'), 
        );
        
        return $status;
    }

	/**
	 * Offline Status
	 *
	 * @var Offline_Status
	 */
	public function get_offline_status(){
            $offline_status = array(
		'Pending'   => 'Pending',
		'Received'  => 'Received',
		'Cancelled' => 'Cancelled',
            );
            return $offline_status;
        }

    public function get_currencies_cons() {
        $currencies = array(
            'USD' => 'United States Dollars',
            'EUR' => 'Euros',
            'GBP' => 'Pounds Sterling',
            'AED' => 'United Arab Emirates dirham',
            'AFN' => 'Afghan afghani',
            'ALL' => 'Albanian lek',
            'AMD' => 'Armenian dram',
            'AOA' => 'Angolan kwanza',
            'ARS' => 'Argentine peso',
            'AUD' => 'Australian Dollars',
            'AZN' => 'Azerbaijani manat',
            'BAM' => 'Bosnia and Herzegovina convertible mark',
            'BBD' => 'Barbadian dollar',
            'BDT' => 'Bangladeshi taka',
            'BGN' => 'Bulgarian lev',
            'BHD' => 'Bahraini dinar',
            'BIF' => 'Burundi Franc',
            'BND' => 'Brunei dollar',
            'BOB' => 'Bolivian boliviano',
            'BRL' => 'Brazilian Real',
            'BSD' => 'Bahamian dollar',
            'BTN' => 'Bhutanese ngultrum',
            'BWP' => 'Botswana pula',
            'BYN' => 'Belarusian ruble',
            'BZD' => 'Belize dollar',
            'CAD' => 'Canadian Dollars',
            'CDF' => 'Congolese franc',
            'CHF' => 'Swiss Franc',
            'CLP' => 'Chilean peso',
            'CNY' => 'Chinese yuan',
            'COP' => 'Colombian peso',
            'CRC' => 'Costa Rican colón',
            'CUP' => 'Cuban peso',
            'CVE' => 'Cape Verdean escudo',
            'CZK' => 'Czech Koruna',
            'DJF' => 'Djiboutian franc',
            'DKK' => 'Danish Krone',
            'DOP' => 'Dominican peso',
            'DZD' => 'Algerian dinar',
            'EGP' => 'Egyptian pound',
            'ERN' => 'Eritrean nakfa',
            'ETB' => 'Ethiopian birr',
            'FJD' => 'Fijian dollar',
            'GEL' => 'Georgian lari',
            'GHS' => 'Ghanaian cedi',
            'GMD' => 'Gambian dalasi',
            'GNF' => 'Guinean franc',
            'GTQ' => 'Guatemalan quetzal',
            'HKD' => 'Hong Kong Dollar',
            'HNL' => 'Honduran lempira',
            'HRK' => 'Croatian kuna',
            'HTG' => 'Haitian gourde',
            'HUF' => 'Hungarian Forint',
            'IDR' => 'Indonesian rupiah',
            'ILS' => 'Israeli Shekel',
            'INR' => 'Indian Rupee',
            'IQD' => 'Iraqi dinar',
            'IRR' => 'Iranian rial',
            'ISK' => 'Icelandic króna',
            'JMD' => 'Jamaican dollar',
            'JOD' => 'Jordanian dinar',
            'JPY' => 'Japanese Yen',
            'KES' => 'Kenyan shilling',
            'KGS' => 'Kyrgyzstani som',
            'KHR' => 'Cambodian riel',
            'KMF' => 'Comorian franc',
            'KPW' => 'North Korean won',
            'KRW' => 'South Korean won',
            'KWD' => 'Kuwaiti dinar',
            'KZT' => 'Kazakhstani tenge',
            'LAK' => 'Lao kip',
            'LBP' => 'Lebanese pound',
            'LKR' => 'Sri Lankan rupee',
            'LRD' => 'Liberian dollar',
            'LSL' => 'Lesotho loti',
            'LYD' => 'Libyan dinar',
            'MAD' => 'Moroccan dirham',
            'MDL' => 'Moldovan leu',
            'MGA' => 'Malagasy ariary',
            'MKD' => 'Macedonian denar',
            'MMK' => 'Burmese kyat',
            'MNT' => 'Mongolian tögrög',
            'MRU' => 'Mauritanian ouguiya',
            'MUR' => 'Mauritian rupee',
            'MVR' => 'Maldivian rufiyaa',
            'MWK' => 'Malawian kwacha',
            'MXN' => 'Mexican Peso',
            'MYR' => 'Malaysian Ringgits',
            'MZN' => 'Mozambican metical',
            'NAD' => 'Namibian dollar',
            'NGN' => 'Nigeria Naira',
            'NIO' => 'Nicaraguan córdoba',
            'NOK' => 'Norwegian Krone',
            'NPR' => 'Nepalese rupee',
            'NZD' => 'New Zealand Dollar',
            'OMR' => 'Omani rial',
            'PAB' => 'Panamanian balboa',
            'PEN' => 'Peruvian sol',
            'PGK' => 'Papua New Guinean kina',
            'PHP' => 'Philippine Pesos',
            'PKR' => 'Pakistani rupee',
            'PLN' => 'Polish Zloty',
            'PYG' => 'Paraguayan guaraní',
            'QAR' => 'Qatari riyal',
            'RIAL' => 'Iranian Rial',
            'RON' => 'Romanian leu',
            'RSD' => 'Serbian dinar',
            'RUB' => 'Russian Rubles',
            'RWF' => 'Rwandan franc',
            'SAR' => 'Saudi riyal',
            'SBD' => 'Solomon Islands dollar',
            'SCR' => 'Seychellois rupee',
            'SDG' => 'Sudanese pound',
            'SEK' => 'Swedish Krona',
            'SGD' => 'Singapore Dollar',
            'SLL' => 'Sierra Leonean leone',
            'SOS' => 'Somali shilling',
            'SRD' => 'Surinamese dollar',
            'SSP' => 'South Sudanese pound',
            'STD' => 'São Tomé and Príncipe dobra',
            'SYP' => 'Syrian pound',
            'SZL' => 'Swazi lilangeni',
            'THB' => 'Thai Baht',
            'TJS' => 'Tajikistani somoni',
            'TMT' => 'Turkmenistan manat',
            'TND' => 'Tunisian dinar',
            'TOP' => 'Tongan pa\'anga',
            'TRY' => 'Turkish Lira',
            'TTD' => 'Trinidad and Tobago dollar',
            'TWD' => 'Taiwan New Dollars',
            'TZS' => 'Tanzanian shilling',
            'UAH' => 'Ukrainian hryvnia',
            'UGX' => 'Ugandan shilling',
            'UYU' => 'Uruguayan peso',
            'UZS' => 'Uzbekistani som',
            'VEF' => 'Venezuelan bolívar',
            'VND' => 'Vietnamese dong',
            'VUV' => 'Vanuatu vatu',
            'WST' => 'Samoan tala',
            'XAF' => 'Central African CFA franc',
            'XCD' => 'East Caribbean dollar',
            'XOF' => 'West African CFA franc',
            'YER' => 'Yemeni rial',
            'ZAR' => 'South African Rand',
            'ZMW' => 'Zambian kwacha',
        );

        return $currencies;
    }

    /**
     * Method for get currency symbols
     */
    public function get_currency_symbol() {
        $currency_symbol = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'AED',
            'AFN' => 'AFN',
            'ALL' => 'L',
            'AMD' => '֏',
            'AOA' => 'Kz',
            'ARS' => '$',
            'AUD' => '$',
            'AZN' => 'AZN',
            'BAM' => 'KM',
            'BBD' => 'Bds$',
            'BDT' => '৳',
            'BGN' => 'Лв',
            'BHD' => 'BD',
            'BIF' => '₣',
            'BND' => 'B$',
            'BOB' => 'Bs',
            'BRL' => 'R$',
            'BSD' => 'B$',
            'BTN' => 'Nu',
            'BWP' => 'P',
            'BYN' => 'BYN',
            'BZD' => '$',
            'CAD' => '$',
            'CDF' => 'FC',
            'CHF' => 'CHF',
            'CLP' => '$',
            'CNY' => '¥',
            'COP' => '$',
            'CRC' => '₡',
            'CUP' => '₱',
            'CVE' => '$',
            'CZK' => 'Kč',
            'DJF' => 'Fdj',
            'DKK' => 'kr',
            'DOP' => 'RD$',
            'DZD' => 'DZD',
            'EGP' => 'E£',
            'ERN' => 'Nfk',
            'ETB' => 'Br',
            'FJD' => 'FJ$',
            'GEL' => 'GEL',
            'GHS' => 'GH₵',
            'GMD' => 'D',
            'GNF' => 'GFr',
            'GTQ' => 'Q',
            'HKD' => '$',
            'HNL' => 'L',
            'HRK' => 'kn',
            'HTG' => 'G',
            'HUF' => 'Ft',
            'IDR' => 'Rp',
            'ILS' => '₪',
            'INR' => '₹',
            'IQD' => 'IQD',
            'IRR' => 'IRR',
            'ISK' => 'kr',
            'JMD' => '$',
            'JOD' => 'JOD',
            'JPY' => '¥',
            'KES' => 'Ksh',
            'KGS' => 'Лв',
            'KHR' => '៛',
            'KMF' => 'CF',
            'KPW' => '₩',
            'KRW' => '₩',
            'KWD' => 'KD',
            'KZT' => '₸',
            'LAK' => '₭N',
            'LBP' => 'LBP',
            'LKR' => 'Rs',
            'LRD' => 'L$',
            'LSL' => 'L',
            'LYD' => 'LD',
            'MAD' => 'MAD',
            'MDL' => 'L',
            'MGA' => 'Ar',
            'MKD' => 'Ден',
            'MMK' => 'K',
            'MNT' => '₮',
            'MRU' => 'MRU',
            'MUR' => '₨',
            'MVR' => 'MVR',
            'MWK' => 'MK',
            'MXN' => '$',
            'MYR' => 'RM',
            'MZN' => 'MT',
            'NAD' => 'N$',
            'NGN' => '₦',
            'NIO' => 'C$',
            'NOK' => 'kr',
            'NPR' => 'Rs',
            'NZD' => '$',
            'OMR' => 'OMR',
            'PAB' => 'B/',
            'PEN' => 'S/',
            'PGK' => 'K',
            'PHP' => '₱',
            'PKR' => '₨',
            'PLN' => 'zł',
            'PYG' => '₲',
            'QAR' => 'QR',
            'RIAL' => 'RIAL',
            'RON' => 'lei',
            'RSD' => 'din',
            'RUB' => 'руб',
            'RWF' => 'RF',
            'SAR' => 'SR',
            'SBD' => 'Si$',
            'SCR' => 'SR',
            'SDG' => 'SDG',
            'SEK' => 'kr',
            'SGD' => '$',
            'SLL' => 'Le',
            'SOS' => 'Sh.so.',
            'SRD' => '$',
            'SSP' => '£',
            'STD' => 'Db',
            'SYP' => '£S',
            'SZL' => 'E',
            'THB' => '฿',
            'TJS' => 'ЅM',
            'TMT' => 'T',
            'TND' => 'DT',
            'TOP' => 'T$',
            'TRY' => 'TRY',
            'TTD' => 'TT$',
            'TWD' => 'NT$',
            'TZS' => 'TSh',
            'UAH' => '₴',
            'UGX' => 'USh',
            'UYU' => '$U',
            'UZS' => 'so\'m',
            'VEF' => 'Bs',
            'VND' => '₫',
            'VUV' => 'VT',
            'WST' => 'WS$',
            'XAF' => 'FCFA',
            'XCD' => '$',
            'XOF' => 'CFA',
            'YER' => 'YER',
            'ZAR' => 'R',
            'ZMW' => 'ZK',
        );
        return $currency_symbol;
    }

    public function ep_currency_symbol() {
        $all_currency_symbols = $this->get_currency_symbol();
        $selected_currency = $this->ep_get_global_settings('currency');
        if (empty($selected_currency)) {
            $selected_currency = 'USD';
        }
        $currency_symbol = $all_currency_symbols[$selected_currency];
        return $currency_symbol;
    }

    public function ep_define_common_field_errors() {
        $errors = array(
            'required' => esc_html__('This is required field', 'eventprime-event-calendar-management'),
            'invalid_url' => esc_html__('Please enter a valid url', 'eventprime-event-calendar-management'),
            'invalid_email' => esc_html__('Please enter a valid email', 'eventprime-event-calendar-management'),
            'invalid_phone' => esc_html__('Please enter a valid phone no.', 'eventprime-event-calendar-management'),
            'invalid_number' => esc_html__('Please enter a valid number', 'eventprime-event-calendar-management'),
            'invalid_date' => esc_html__('Please enter a valid date', 'eventprime-event-calendar-management'),
            'whole_number' => esc_html__('Please enter a valid whole number.', 'eventprime-event-calendar-management'),
            'invalid_price' => esc_html__('Please enter a valid price.', 'eventprime-event-calendar-management'),
        );
        return $errors;
    }

    /**
     * Method for get calendar locales
     */
    public function get_calendar_locales() {
        $calendar_locales = array(
            'en',
            'af',
            'ar-dz',
            'ar-kw',
            'ar-ly',
            'ar-ma',
            'ar-sa',
            'ar-tn',
            'ar',
            'be',
            'bg',
            'bs',
            'ca',
            'cs',
            'da',
            'de-at',
            'de-ch',
            'de',
            'el',
            'en-au',
            'en-ca',
            'en-gb',
            'en-ie',
            'en-nz',
            'es-do',
            'es-us',
            'es',
            'et',
            'eu',
            'fa',
            'fi',
            'fr-ca',
            'fr-ch',
            'fr',
            'gl',
            'he',
            'hi',
            'hr',
            'hu',
            'id',
            'is',
            'it',
            'ja',
            'ka',
            'kk',
            'ko',
            'lb',
            'lt',
            'lv',
            'mk',
            'ms-my',
            'ms',
            'nb',
            'nl-be',
            'nl',
            'nn',
            'pl',
            'pt-br',
            'pt',
            'ro',
            'ru',
            'sk',
            'sl',
            'sq',
            'sr-cyrl',
            'sr',
            'sv',
            'th',
            'tr',
            'uk',
            'vi',
            'zh-cn',
            'zh-hk',
            'zh-tw',
        );
        return $calendar_locales;
    }

    /**
     * Method for get font
     */
    public function get_fonts_cons() {
        $fonts = array('FreeSerif', 'Courier', 'Helvetica', 'Times');
        return $fonts;
    }

    /**
     * Method for get material fonts
     */
    public function get_material_icons() {
        $icons = array(
            "3d_rotation",
            "accessibility",
            "accessible",
            "account_balance",
            "account_balance_wallet",
            "account_box",
            "account_circle",
            "add_shopping_cart",
            "alarm",
            "alarm_add",
            "alarm_off",
            "alarm_on",
            "all_out",
            "android",
            "announcement",
            "aspect_ratio",
            "assessment",
            "assignment",
            "assignment_ind",
            "assignment_late",
            "assignment_return",
            "assignment_returned",
            "assignment_turned_in",
            "autorenew",
            "backup",
            "book",
            "bookmark",
            "bookmark_border",
            "bug_report",
            "build",
            "cached",
            "camera_enhance",
            "card_giftcard",
            "card_membership",
            "card_travel",
            "change_history",
            "check_circle",
            "chrome_reader_mode",
            "class",
            "code",
            "compare_arrows",
            "copyright",
            "credit_card",
            "dashboard",
            "date_range",
            "delete",
            "delete_forever",
            "description",
            "dns",
            "done",
            "done_all",
            "donut_large",
            "donut_small",
            "eject",
            "euro_symbol",
            "event",
            "event_seat",
            "exit_to_app",
            "explore",
            "extension",
            "face",
            "favorite",
            "favorite_border",
            "feedback",
            "find_in_page",
            "find_replace",
            "fingerprint",
            "flight_land",
            "flight_takeoff",
            "flip_to_back",
            "flip_to_front",
            "g_translate",
            "gavel",
            "get_app",
            "gif",
            "grade",
            "group_work",
            "help",
            "help_outline",
            "highlight_off",
            "history",
            "home",
            "hourglass_empty",
            "hourglass_full",
            "http",
            "https",
            "important_devices",
            "info",
            "info_outline",
            "input",
            "invert_colors",
            "label",
            "label_outline",
            "language",
            "launch",
            "lightbulb_outline",
            "line_style",
            "line_weight",
            "list",
            "lock",
            "lock_open",
            "lock_outline",
            "loyalty",
            "markunread_mailbox",
            "motorcycle",
            "note_add",
            "offline_pin",
            "opacity",
            "open_in_browser",
            "open_in_new",
            "open_with",
            "pageview",
            "pan_tool",
            "payment",
            "perm_camera_mic",
            "perm_contact_calendar",
            "perm_data_setting",
            "perm_device_information",
            "perm_identity",
            "perm_media",
            "perm_phone_msg",
            "perm_scan_wifi",
            "pets",
            "picture_in_picture",
            "picture_in_picture_alt",
            "play_for_work",
            "polymer",
            "power_settings_new",
            "pregnant_woman",
            "print",
            "query_builder",
            "question_answer",
            "receipt",
            "record_voice_over",
            "redeem",
            "remove_shopping_cart",
            "reorder",
            "report_problem",
            "restore",
            "restore_page",
            "room",
            "rounded_corner",
            "rowing",
            "schedule",
            "search",
            "settings",
            "settings_applications",
            "settings_backup_restore",
            "settings_bluetooth",
            "settings_brightness",
            "settings_cell",
            "settings_ethernet",
            "settings_input_antenna",
            "settings_input_component",
            "settings_input_composite",
            "settings_input_hdmi",
            "settings_input_svideo",
            "settings_overscan",
            "settings_phone",
            "settings_power",
            "settings_remote",
            "settings_voice",
            "shop",
            "shop_two",
            "shopping_basket",
            "shopping_cart",
            "speaker_notes",
            "speaker_notes_off",
            "spellcheck",
            "star",
            "star_rate",
            "stars",
            "store",
            "subject",
            "supervisor_account",
            "swap_horiz",
            "swap_vert",
            "swap_vertical_circle",
            "system_update_alt",
            "tab",
            "tab_unselected",
            "theaters",
            "thumb_down",
            "thumb_up",
            "thumbs_up_down",
            "toc",
            "today",
            "toll",
            "touch_app",
            "track_changes",
            "translate",
            "trending_down",
            "trending_flat",
            "trending_up",
            "turned_in",
            "turned_in_not",
            "update",
            "verified_user",
            "view_agenda",
            "view_array",
            "view_carousel",
            "view_column",
            "view_day",
            "view_headline",
            "view_list",
            "view_module",
            "view_quilt",
            "view_stream",
            "view_week",
            "visibility",
            "visibility_off",
            "watch_later",
            "work",
            "youtube_searched_for",
            "zoom_in",
            "zoom_out",
            "add_alert",
            "error",
            "error_outline",
            "warning",
            "add_to_queue",
            "airplay",
            "album",
            "art_track",
            "av_timer",
            "branding_watermark",
            "call_to_action",
            "closed_caption",
            "equalizer",
            "explicit",
            "fast_forward",
            "fast_rewind",
            "featured_play_list",
            "featured_video",
            "fiber_dvr",
            "fiber_manual_record",
            "fiber_new",
            "fiber_pin",
            "fiber_smart_record",
            "forward_10",
            "forward_30",
            "forward_5",
            "games",
            "hd",
            "hearing",
            "high_quality",
            "library_add",
            "library_books",
            "library_music",
            "loop",
            "mic",
            "mic_none",
            "mic_off",
            "movie",
            "music_video",
            "new_releases",
            "not_interested",
            "note",
            "pause",
            "pause_circle_filled",
            "pause_circle_outline",
            "play_arrow",
            "play_circle_filled",
            "play_circle_outline",
            "playlist_add",
            "playlist_add_check",
            "playlist_play",
            "queue",
            "queue_music",
            "queue_play_next",
            "radio",
            "recent_actors",
            "remove_from_queue",
            "repeat",
            "repeat_one",
            "replay",
            "replay_10",
            "replay_30",
            "replay_5",
            "shuffle",
            "skip_next",
            "skip_previous",
            "slow_motion_video",
            "snooze",
            "sort_by_alpha",
            "stop",
            "subscriptions",
            "subtitles",
            "surround_sound",
            "video_call",
            "video_label",
            "video_library",
            "videocam",
            "videocam_off",
            "volume_down",
            "volume_mute",
            "volume_off",
            "volume_up",
            "web",
            "web_asset",
            "business",
            "call",
            "call_end",
            "call_made",
            "call_merge",
            "call_missed",
            "call_missed_outgoing",
            "call_received",
            "call_split",
            "chat",
            "chat_bubble",
            "chat_bubble_outline",
            "clear_all",
            "comment",
            "contact_mail",
            "contact_phone",
            "contacts",
            "dialer_sip",
            "dialpad",
            "email",
            "forum",
            "import_contacts",
            "import_export",
            "invert_colors_off",
            "live_help",
            "location_off",
            "location_on",
            "mail_outline",
            "message",
            "messenger",
            "no_sim",
            "phone",
            "phonelink_erase",
            "phonelink_lock",
            "phonelink_ring",
            "phonelink_setup",
            "portable_wifi_off",
            "present_to_all",
            "ring_volume",
            "rss_feed",
            "screen_share",
            "speaker_phone",
            "stay_current_landscape",
            "stay_current_portrait",
            "stay_primary_landscape",
            "stay_primary_portrait",
            "stop_screen_share",
            "swap_calls",
            "textsms",
            "voicemail",
            "vpn_key",
            "add",
            "add_box",
            "add_circle",
            "add_circle_outline",
            "archive",
            "backspace",
            "block",
            "clear",
            "content_copy",
            "content_cut",
            "content_paste",
            "create",
            "delete_sweep",
            "drafts",
            "filter_list",
            "flag",
            "font_download",
            "forward",
            "gesture",
            "inbox",
            "link",
            "low_priority",
            "mail",
            "markunread",
            "move_to_inbox",
            "next_week",
            "redo",
            "remove",
            "remove_circle",
            "remove_circle_outline",
            "reply",
            "reply_all",
            "report",
            "save",
            "select_all",
            "send",
            "sort",
            "text_format",
            "unarchive",
            "undo",
            "weekend",
            "access_alarm",
            "access_alarms",
            "access_time",
            "add_alarm",
            "airplanemode_active",
            "airplanemode_inactive",
            "battery_alert",
            "battery_charging_full",
            "battery_full",
            "battery_std",
            "battery_unknown",
            "bluetooth",
            "bluetooth_connected",
            "bluetooth_disabled",
            "bluetooth_searching",
            "brightness_auto",
            "brightness_high",
            "brightness_low",
            "brightness_medium",
            "data_usage",
            "developer_mode",
            "devices",
            "dvr",
            "gps_fixed",
            "gps_not_fixed",
            "gps_off",
            "graphic_eq",
            "location_disabled",
            "location_searching",
            "network_cell",
            "network_wifi",
            "nfc",
            "screen_lock_landscape",
            "screen_lock_portrait",
            "screen_lock_rotation",
            "screen_rotation",
            "sd_storage",
            "settings_system_daydream",
            "signal_cellular_4_bar",
            "signal_cellular_connected_no_internet_4_bar",
            "signal_cellular_no_sim",
            "signal_cellular_null",
            "signal_cellular_off",
            "signal_wifi_4_bar",
            "signal_wifi_4_bar_lock",
            "signal_wifi_off",
            "storage",
            "usb",
            "wallpaper",
            "widgets",
            "wifi_lock",
            "attach_file",
            "attach_money",
            "border_all",
            "border_bottom",
            "border_clear",
            "border_color",
            "border_horizontal",
            "border_inner",
            "border_left",
            "border_outer",
            "border_right",
            "border_style",
            "border_top",
            "border_vertical",
            "bubble_chart",
            "drag_handle",
            "format_align_center",
            "format_align_justify",
            "format_align_left",
            "format_align_right",
            "format_bold",
            "format_clear",
            "format_color_fill",
            "format_color_reset",
            "format_color_text",
            "format_indent_decrease",
            "format_indent_increase",
            "format_italic",
            "format_line_spacing",
            "format_list_bulleted",
            "format_list_numbered",
            "format_paint",
            "format_quote",
            "format_shapes",
            "format_size",
            "format_strikethrough",
            "format_textdirection_l_to_r",
            "format_textdirection_r_to_l",
            "format_underlined",
            "functions",
            "highlight",
            "insert_chart",
            "insert_comment",
            "insert_drive_file",
            "insert_emoticon",
            "insert_invitation",
            "insert_link",
            "insert_photo",
            "linear_scale",
            "merge_type",
            "mode_comment",
            "mode_edit",
            "monetization_on",
            "money_off",
            "multiline_chart",
            "pie_chart",
            "pie_chart_outlined",
            "publish",
            "short_text",
            "show_chart",
            "space_bar",
            "strikethrough_s",
            "text_fields",
            "title",
            "vertical_align_bottom",
            "vertical_align_center",
            "vertical_align_top",
            "wrap_text",
            "attachment",
            "cloud",
            "cloud_circle",
            "cloud_done",
            "cloud_download",
            "cloud_off",
            "cloud_queue",
            "cloud_upload",
            "create_new_folder",
            "file_download",
            "file_upload",
            "folder",
            "folder_open",
            "folder_shared",
            "cast",
            "cast_connected",
            "computer",
            "desktop_mac",
            "desktop_windows",
            "developer_board",
            "device_hub",
            "devices_other",
            "dock",
            "gamepad",
            "headset",
            "headset_mic",
            "keyboard",
            "keyboard_arrow_down",
            "keyboard_arrow_left",
            "keyboard_arrow_right",
            "keyboard_arrow_up",
            "keyboard_backspace",
            "keyboard_capslock",
            "keyboard_hide",
            "keyboard_return",
            "keyboard_tab",
            "keyboard_voice",
            "laptop",
            "laptop_chromebook",
            "laptop_mac",
            "laptop_windows",
            "memory",
            "mouse",
            "phone_android",
            "phone_iphone",
            "phonelink",
            "phonelink_off",
            "power_input",
            "phonelink",
            "router",
            "scanner",
            "security",
            "sim_card",
            "smartphone",
            "speaker",
            "speaker_group",
            "tablet",
            "tablet_android",
            "tablet_mac",
            "toys",
            "tv",
            "videogame_asset",
            "watch",
            "add_a_photo",
            "add_to_photos",
            "adjust",
            "assistant",
            "assistant_photo",
            "audiotrack",
            "blur_circular",
            "blur_linear",
            "blur_off",
            "blur_on",
            "brightness_1",
            "brightness_2",
            "brightness_3",
            "brightness_4",
            "brightness_5",
            "brightness_6",
            "brightness_7",
            "broken_image",
            "brush",
            "burst_mode",
            "camera",
            "camera_alt",
            "camera_front",
            "camera_rear",
            "camera_roll",
            "center_focus_strong",
            "center_focus_weak",
            "collections",
            "collections_bookmark",
            "color_lens",
            "colorize",
            "compare",
            "control_point",
            "control_point_duplicate",
            "crop",
            "crop_16_9",
            "crop_3_2",
            "crop_5_4",
            "crop_7_5",
            "crop_din",
            "crop_free",
            "crop_landscape",
            "crop_original",
            "crop_portrait",
            "crop_square",
            "dehaze",
            "details",
            "edit",
            "exposure",
            "exposure_neg_1",
            "exposure_neg_2",
            "exposure_plus_1",
            "exposure_plus_2",
            "exposure_zero",
            "filter",
            "filter_1",
            "filter_2",
            "filter_3",
            "filter_4",
            "filter_5",
            "filter_6",
            "filter_7",
            "filter_8",
            "filter_9",
            "filter_9_plus",
            "filter_b_and_w",
            "filter_center_focus",
            "filter_drama",
            "filter_frames",
            "filter_hdr",
            "filter_none",
            "filter_tilt_shift",
            "filter_vintage",
            "flare",
            "flash_auto",
            "flash_off",
            "flash_on",
            "flip",
            "gradient",
            "grain",
            "grid_off",
            "grid_on",
            "hdr_off",
            "hdr_on",
            "hdr_strong",
            "hdr_weak",
            "healing",
            "image",
            "image_aspect_ratio",
            "iso",
            "landscape",
            "leak_add",
            "leak_remove",
            "lens",
            "linked_camera",
            "looks",
            "looks_3",
            "looks_4",
            "looks_5",
            "looks_6",
            "looks_one",
            "looks_two",
            "loupe",
            "monochrome_photos",
            "movie_creation",
            "movie_filter",
            "music_note",
            "nature",
            "nature_people",
            "navigate_before",
            "navigate_next",
            "palette",
            "panorama",
            "panorama_fish_eye",
            "panorama_horizontal",
            "panorama_vertical",
            "panorama_wide_angle",
            "photo",
            "photo_album",
            "photo_camera",
            "photo_filter",
            "photo_library",
            "photo_size_select_actual",
            "photo_size_select_large",
            "photo_size_select_small",
            "picture_as_pdf",
            "portrait",
            "remove_red_eye",
            "rotate_90_degrees_ccw",
            "rotate_left",
            "rotate_right",
            "slideshow",
            "straighten",
            "style",
            "switch_camera",
            "switch_video",
            "tag_faces",
            "texture",
            "timelapse",
            "timer_10",
            "timer",
            "timer_3",
            "timer_off",
            "tonality",
            "transform",
            "tune",
            "view_comfy",
            "view_compact",
            "vignette",
            "wb_auto",
            "wb_cloudy",
            "wb_incandescent",
            "wb_iridescent",
            "wb_sunny",
            "add_location",
            "beenhere",
            "directions",
            "directions_bike",
            "directions_boat",
            "directions_bus",
            "directions_car",
            "directions_railway",
            "directions_run",
            "directions_subway",
            "directions_transit",
            "directions_walk",
            "edit_location",
            "ev_station",
            "flight",
            "hotel",
            "layers",
            "layers_clear",
            "local_activity",
            "local_airport",
            "local_atm",
            "local_bar",
            "local_cafe",
            "local_car_wash",
            "local_convenience_store",
            "local_dining",
            "local_drink",
            "local_florist",
            "local_gas_station",
            "local_grocery_store",
            "local_hospital",
            "local_hotel",
            "local_laundry_service",
            "local_library",
            "local_mall",
            "local_movies",
            "local_offer",
            "local_parking",
            "local_pharmacy",
            "local_phone",
            "local_pizza",
            "local_play",
            "local_post_office",
            "local_printshop",
            "local_see",
            "local_shipping",
            "local_taxi",
            "map",
            "my_location",
            "navigation",
            "near_me",
            "person_pin",
            "person_pin_circle",
            "pin_drop",
            "place",
            "rate_review",
            "restaurant",
            "restaurant_menu",
            "satellite",
            "store_mall_directory",
            "streetview",
            "subway",
            "terrain",
            "traffic",
            "train",
            "tram",
            "transfer_within_a_station",
            "zoom_out_map",
            "apps",
            "arrow_back",
            "arrow_drop_down",
            "arrow_drop_down_circle",
            "arrow_drop_up",
            "arrow_downward",
            "arrow_forward",
            "arrow_upward",
            "cancel",
            "check",
            "chevron_left",
            "chevron_right",
            "close",
            "expand_less",
            "expand_more",
            "first_page",
            "fullscreen",
            "fullscreen_exit",
            "last_page",
            "menu",
            "more_horiz",
            "more_vert",
            "refresh",
            "subdirectory_arrow_left",
            "subdirectory_arrow_right",
            "unfold_less",
            "unfold_more",
            "adb",
            "airline_seat_flat",
            "airline_seat_flat_angled",
            "airline_seat_individual_suite",
            "airline_seat_legroom_extra",
            "airline_seat_legroom_normal",
            "airline_seat_legroom_reduced",
            "airline_seat_recline_extra",
            "airline_seat_recline_normal",
            "bluetooth_audio",
            "confirmation_number",
            "disc_full",
            "do_not_disturb",
            "do_not_disturb_alt",
            "do_not_disturb_off",
            "do_not_disturb_on",
            "drive_eta",
            "enhanced_encryption",
            "event_available",
            "event_busy",
            "event_note",
            "folder_special",
            "live_tv",
            "mms",
            "more",
            "network_check",
            "network_locked",
            "no_encryption",
            "ondemand_video",
            "personal_video",
            "phone_bluetooth_speaker",
            "phone_forwarded",
            "phone_in_talk",
            "phone_locked",
            "phone_missed",
            "phone_paused",
            "power",
            "priority_high",
            "sd_card",
            "sim_card_alert",
            "sms",
            "sms_failed",
            "sync",
            "sync_disabled",
            "sync_problem",
            "system_update",
            "tap_and_play",
            "time_to_leave",
            "vibration",
            "voice_chat",
            "vpn_lock",
            "wc",
            "wifi",
            "ac_unit",
            "airport_shuttle",
            "all_inclusive",
            "beach_access",
            "business_center",
            "casino",
            "child_care",
            "child_friendly",
            "fitness_center",
            "free_breakfast",
            "golf_course",
            "hot_tub",
            "kitchen",
            "pool",
            "room_service",
            "rv_hookup",
            "smoke_free",
            "smoking_rooms",
            "spa",
            "cake",
            "domain",
            "group",
            "group_add",
            "location_city",
            "mood",
            "mood_bad",
            "notifications",
            "notifications_none",
            "notifications_off",
            "notifications_active",
            "notifications_paused",
            "pages",
            "party_mode",
            "people",
            "people_outline",
            "person",
            "person_add",
            "person_outline",
            "plus_one",
            "poll",
            "public",
            "school",
            "sentiment_dissatisfied",
            "sentiment_neutral",
            "sentiment_satisfied",
            "sentiment_very_dissatisfied",
            "sentiment_very_satisfied",
            "share",
            "whatshot",
            "check_box",
            "check_box_outline_blank",
            "indeterminate_check_box",
            "radio_button_unchecked",
            "radio_button_checked",
            "star",
            "star_half",
            "star_border",
        );
        return $icons;
    }

    public function ep_get_all_pages_list() {
        $publish_pages = array();
        $args = array(
            'sort_order' => 'asc',
            'sort_column' => 'post_title',
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        $pages = get_pages($args);
        if (count($pages)) {
            foreach ($pages as $page) {
                $publish_pages[$page->ID] = $page->post_title;
            }
        }
        return $publish_pages;
    }

    /**
     * Return all user roles
     */
    public function ep_get_all_user_roles() {
        global $wp_roles;
        $userRoles = $wp_roles->roles;
        $roles = array();
        if (!empty($userRoles)) {
            foreach ($userRoles as $key => $value) {
                $roles[$key] = $value['name'];
            }
        }
        return $roles;
    }

    public function ep_get_seo_page_url($type) {
        $enable_seo_urls = $this->ep_get_global_settings('enable_seo_urls');
        if (!empty($enable_seo_urls)) {
            $seo_urls = (object) $this->ep_get_global_settings('seo_urls');
            $url = '';
            if (!empty($seo_urls)) {
                if ($type == 'event') {
                    $url = (!empty($seo_urls->event_page_type_url) ) ? $seo_urls->event_page_type_url : 'event';
                }
                if ($type == 'performer') {
                    $url = (!empty($seo_urls->performer_page_type_url) ) ? $seo_urls->performer_page_type_url : 'performer';
                }
                if ($type == 'organizer') {
                    $url = (!empty($seo_urls->organizer_page_type_url) ) ? $seo_urls->organizer_page_type_url : 'organizer';
                }
                if ($type == 'venue') {
                    $url = (!empty($seo_urls->venues_page_type_url) ) ? $seo_urls->venues_page_type_url : 'venue';
                }
                if ($type == 'event-type') {
                    $url = (!empty($seo_urls->types_page_type_url) ) ? $seo_urls->types_page_type_url : 'event-type';
                }
                if ($type == 'sponsor') {
                    $url = (!empty($seo_urls->sponsor_page_type_url) ) ? $seo_urls->sponsor_page_type_url : 'sponsor';
                }
                return $url;
            }
        }
        return $type;
    }

    public function ep_get_global_settings($meta = null) {
        // Load global setting array from options table
        if(!isset($this->global_settings) || empty($this->global_settings))
        {
            $global_options = get_option('em_global_settings');
        }
        else
        {
             $global_options = $this->global_settings;
        }
        // Check if option exists 
        if (!empty($global_options)) {
            if ($meta !== null) {
                if (isset($global_options->{$meta})) {
                    return $global_options->{$meta};
                } else {
                    // Option does not exists
                    return false;
                }
            }
            $exclude_fields = [
                "stripe_api_key",
                "ep_mailchimp_api_key",
                "zoom_api_key",
                "zoom_api_secret_key",
                "zoom_client_id",
                "zoom_client_secret",
                "admin_mobile_number",
                "twilio_auth_token",
                "twilio_service_id",
                "twilio_number",
                "zapier_api_key",
            ];
            $exclude_fields = apply_filters('ep_extend_global_exclude_fields', $exclude_fields, $global_options);
            foreach($exclude_fields as $field) {
                if ( isset($global_options->{$field}) ) {
                    $global_options->{$field} = ''; 
                }
            }
            return $global_options;
        }
        return false;
    }

    /**
     * Convert timestamp to date
     * 
     * @param int $timestamp Timestamp.
     * 
     * @param string $format Date Format.
     * 
     * @param int $strict Global Settings.
     * 
     * @return string $date Date.
     */
    public function ep_timestamp_to_date_old($timestamp, $format = 'Y-m-d', $strict = 0) {
        $date = '';
        if (!empty($timestamp)) {
            if (empty($strict)) { // Not use setting format if $strict = 1
                $format = $this->ep_get_datepicker_format();
            }
            if (!is_int($timestamp)) {
                $timestamp = (int) $timestamp;
            }
            $date = wp_date($format, $timestamp);
        }
        return $date;
    }
    
    public function ep_timestamp_to_date($timestamp, $format = 'Y-m-d', $strict = 0) {
    $date = '';
    if (!empty($timestamp)) {
        if (empty($strict)) {
            $format = $this->ep_get_datepicker_format(); // use custom format
        }
        if (!is_int($timestamp)) {
            $timestamp = (int) $timestamp;
        }
        $date = wp_date($format, $timestamp); // use wp_date instead of wp_date
    }
    return $date;
}


/**
 * Compact duration like "1d 4h 30m" from start/end UNIX timestamps.
 *
 * Expects $event->em_start_ts and $event->em_end_ts (ints, seconds).
 */
public function ep_get_event_date_time_diff( $event ) {
    if ( empty( $event->em_start_date_time ) || empty( $event->em_end_date_time ) ) {
        return '';
    }

    
    // Build DateTimes from timestamps in WP tz (no parsing of strings).
    $user_timezone = $this->ep_get_site_timezone();
    $tz = new DateTimeZone($user_timezone);
    $start = ( new DateTimeImmutable( '@' . intval( $event->em_start_date_time ) ) )->setTimezone( $tz );
    $end   = ( new DateTimeImmutable( '@' . intval( $event->em_end_date_time ) ) )->setTimezone( $tz );

    if ( $start > $end ) { [$start, $end] = [$end, $start]; }

    $d = $start->diff( $end );

    $out = '';
    if ( $d->y ) $out .= $d->y . 'y ';
    if ( $d->m ) $out .= $d->m . 'm ';
    if ( $d->d ) $out .= $d->d . 'd ';
    if ( $d->h ) $out .= $d->h . 'h ';
    if ( $d->i ) $out .= $d->i . 'm';
    return trim( $out );
}

    public function ep_get_event_date_time_diff_old($event) {
        $date_diff = '';
        if (!empty($event->em_start_date) && !empty($event->em_end_date)) {
            $start_date = $this->ep_timestamp_to_date($event->em_start_date, 'Y-m-d', 1);
            $start_time = $end_time = '';
            if (!empty($event->em_start_time)) {
                $start_time = $event->em_start_time;
            }
            $end_date = $this->ep_timestamp_to_date($event->em_end_date, 'Y-m-d', 1);
            if (!empty($event->em_end_time)) {
                $end_time = $event->em_end_time;
            }
            if (!empty($start_time)) {
                $start_date .= ' ' . $start_time;
            }
            if (!empty($end_time)) {
                $end_date .= ' ' . $end_time;
            }

            // create date
            //$start_date = DateTime::createFromFormat( 'Y-m-d H:i', $start_date );
            $start_datetime = new DateTime($start_date);
            // get difference
            //$end_date = DateTime::createFromFormat( 'Y-m-d H:i', $end_date );
            $diff = $start_datetime->diff(new DateTime($end_date));
            $date_diff = '';
            if (!empty($diff->y)) { // year
                $date_diff .= $diff->y . 'y ';
            }
            if (!empty($diff->m)) { // month
                $date_diff .= $diff->m . 'm ';
            }
            if (!empty($diff->d)) { // days
                $date_diff .= $diff->d . 'd ';
            }
            if (!empty($diff->h)) { // hour
                $date_diff .= $diff->h . 'h ';
            }
            if (!empty($diff->i)) { // minute
                $date_diff .= $diff->i . 'm';
            }
        }
        return $date_diff;
    }

    public function ep_check_time_format($timeString)
    {
        // Define the time string and expected format
        $format = $this->ep_get_global_settings('time_format');
        //$format = "H:i:s";

        // Parse the time string
        $time = DateTime::createFromFormat($format, $timeString);

        // Check if the time format is correct
        if ($time && $time->format($format) === $timeString) {
            return $timeString;
        }
        else
        {
            return '';
        }
    }
    
    public function ep_convert_event_date_time_from_timezone( $event, $format = '', $end = 0, $strict = 0 ) {
        if ( empty( $event ) ) {
            return '';
        }

        // 1) Pick a base date + time string
        $dp_format  = $this->ep_get_datepicker_format();
        if ( ! empty( $strict ) && ! empty( $format ) ) {
            $dp_format = $format; // only used when we must display exactly $format
        }

        $time_format_setting = $this->ep_get_global_settings( 'time_format' ); // 'HH:mm' or 12h
        $site_timezone       = $this->ep_get_site_timezone();

        // Determine date (string) from stored timestamp
        if ( ! empty( $end ) ) {
            $date_str  = $this->ep_timestamp_to_date( $event->em_end_date, $dp_format );
            $time_str  = ! empty( $event->em_end_time ) ? $event->em_end_time : '11:59 pm';
        } else {
            $date_str  = $this->ep_timestamp_to_date( $event->em_start_date, $dp_format );
            if ( ! empty( $event->em_start_time ) ) {
                $time_str = $event->em_start_time;
            } else {
                // Pick a sane default based on 24h/12h preference
                $time_str = ( $time_format_setting === 'HH:mm' ) ? '00:00' : '12:00 am';
            }
        }

        // 2) Build a DateTime in site timezone from date + time
        // NOTE: ep_datetime_to_timestamp returns DateTime (per your check below)
        // The parse format must match what ep_timestamp_to_date() emitted. If your datepicker format differs,
        // you likely normalize inside ep_datetime_to_timestamp already. Keep that as-is.
        $dt = $this->ep_datetime_to_timestamp( $date_str . ' ' . $time_str, 'Y-m-d h:i a', $site_timezone, 1 );

        if ( $dt && $dt instanceof DateTime ) {
            $dt->setTimeZone( new DateTimeZone( $site_timezone ) );
            $unix = $dt->getTimestamp();

            // 3) Decide the output format (use WP tokens so wp_date() can translate)
            if ( ! empty( $strict ) && is_string( $format ) && $format !== '' ) {
                // Caller provided exact format; print it localized
                return wp_date( $format, $unix );
            }

            // Default: "D, d M h:i A" (12h) or "D, d M H:i" (24h), with localized month/day names
            $out_fmt = ( $time_format_setting === 'HH:mm' )
                ? 'D, d M H:i'
                : 'D, d M h:i A';

            return wp_date( $out_fmt, $unix );
        }

        // 4) Fallback (parsing failed). Still try to localize date part if we have a raw timestamp.
        // If you can access raw timestamps directly, prefer returning wp_date() on those:
        if ( empty( $end ) && ! empty( $event->em_start_date ) ) {
            return wp_date( ( $time_format_setting === 'HH:mm' ? 'D, d M H:i' : 'D, d M h:i A' ), (int) $event->em_start_date );
        } elseif ( ! empty( $event->em_end_date ) ) {
            return wp_date( ( $time_format_setting === 'HH:mm' ? 'D, d M H:i' : 'D, d M h:i A' ), (int) $event->em_end_date );
        }

        // Last resort: return the concatenated strings (non-translated).
        return trim( $date_str . ' ' . $time_str );
    }

    public function ep_convert_event_date_time_from_timezone_old2($event, $format = '', $end = 0, $strict = 0) {
    if ($event) {
        $dp_format = $this->ep_get_datepicker_format();
        if (!empty($strict) && !empty($format)) {
            $dp_format = $format;
        }
 
        $date = $this->ep_timestamp_to_date($event->em_start_date, $dp_format);
        $time_format = $this->ep_get_global_settings('time_format');
        $start_time = $event->em_start_time;
 
        if (empty($start_time)) {
            $start_time = '12:00 am';
            if (!empty($time_format) && $time_format == 'HH:mm') {
                $start_time = '00:00 am';
            }
        }
 
        if (!empty($end)) {
            $date = $this->ep_timestamp_to_date($event->em_end_date, $dp_format);
            $start_time = $event->em_end_time;
            if (empty($start_time)) {
                $start_time = '11:59 pm';
            }
        }
 
        $site_timezone = $this->ep_get_site_timezone();
        $datetime = $date . ' ' . $start_time;
 
        $times = $this->ep_datetime_to_timestamp($datetime, 'Y-m-d h:i a', $site_timezone, 1);
 
        // ✅ Safety check
        if ($times && $times instanceof DateTime) {
            $times->setTimeZone(new DateTimeZone($site_timezone));
 
            if (!empty($strict) && !empty($format) && is_string($format)) {
                $date = $times->format($format);
            } else {
                if (!empty($time_format) && $time_format == 'HH:mm') {
                    $day_data   = $times->format('D');
                    $date_data  = $times->format('d');
                    $month_data = $times->format('M');
                    $date       = $day_data . ', ' . $date_data . ' ' . $month_data;
                    $date      .= ' ' . $times->format('H:i');
                } else {
                    $day_data   = $times->format('D');
                    $date_data  = $times->format('d');
                    $month_data = $times->format('M');
                    $time_data  = $times->format('h:i A');
                    $date       = $day_data . ', ' . $date_data . ' ' . $month_data . ', ' . $time_data;
                }
            }
            return $date;
        } else {
            // fallback if timestamp conversion fails
            return $date . ' ' . $start_time;
        }
    }
}
    
    public function ep_convert_event_date_time_from_timezone_old($event, $format = '', $end = 0, $strict = 0) {
        if ($event) {
            $dp_format = $this->ep_get_datepicker_format();
            if (!empty($strict) && !empty($format)) {
                $dp_format = $format;
            }
            $date = $this->ep_timestamp_to_date($event->em_start_date, $dp_format);
            $time_format = $this->ep_get_global_settings('time_format');
            $start_time = $event->em_start_time;
            if (empty($start_time)) {
                $start_time = '12:00 am';
                if (!empty($time_format) && $time_format == 'HH:mm') {
                    $start_time = '00:00 am';
                }
            }
            if (!empty($end)) {
                $date = $this->ep_timestamp_to_date($event->em_end_date, $dp_format);
                $start_time = $event->em_end_time;
                if (empty($start_time)) {
                    $start_time = '11:59 pm';
                }
            }
            $user_timezone = $this->ep_get_current_user_timezone();
//            if (!empty($user_timezone)) {
//                if (strpos($user_timezone, '+') !== false) {
//                    $exp_timezone = explode('+', $user_timezone)[1];
//                    $user_timezone = $this->get_site_timezone_from_offset($exp_timezone);
//                }
//                if (strpos($user_timezone, '-') !== false) {
//                    $exp_timezone = explode('-', $user_timezone)[1];
//                    $user_timezone = $this->get_site_timezone_from_offset($exp_timezone);
//                }
//            }
            $site_timezone = $this->ep_get_site_timezone();
            if (!empty($user_timezone) && $user_timezone != $site_timezone && !empty($this->ep_get_global_settings('enable_event_time_to_user_timezone'))) {
                $datetime = $date . ' ' . $start_time;
                $times = $this->ep_datetime_to_timestamp($datetime, 'Y-m-d h:i a', $site_timezone, 1);
                $times->setTimeZone(new DateTimeZone($user_timezone));
                
                if (in_array($user_timezone, DateTimeZone::listIdentifiers())) {
                    $times->setTimeZone(new DateTimeZone($user_timezone));
                } else {
                    // Handle invalid timezone
                    // e.g., fallback to UTC or site default
                    $times->setTimeZone($site_timezone);
                }
                
                
                if (!empty($strict) && !empty($format)) {
                    $date = $times->format($format);               
                } else {
                    if (!empty($time_format) && $time_format == 'HH:mm') {
                        $date = $times->format('D, d M');
                        $day_data = $times->format('D');
                        $date_data = $times->format('d');
                        $month_data = $times->format('M');
                        $date = $day_data . ', ' . $date_data . ' ' . $month_data;
                        $date .= ' ' . $times->format('H:i');
                    } else {
                        $day_data = $times->format('D');
                        $date_data = $times->format('d');
                        $month_data = $times->format('M');
                        $time_data = $times->format('h:i A');
                        $date = $day_data . ', ' . $date_data . ' ' . $month_data . ', ' . $time_data;
                    }
                }
                return $date;
            } else {
                $datetime = $date . ' ' . $start_time;
                $times = $this->ep_datetime_to_timestamp($datetime, 'Y-m-d h:i a', $site_timezone, 1);
                if (!empty($times)) {
                    $times->setTimeZone(new DateTimeZone($site_timezone));
                }
                if (!empty($strict) && !empty($format) && is_string($format)) {
                    $date = $times->format($format);
                } else {
                    if (!empty($time_format) && $time_format == 'HH:mm') {
                        $date = $times->format('D, d M');
                        $day_data = $times->format('D');
                        $date_data = $times->format('d');
                        $month_data = $times->format('M');
                        $date = $day_data . ', ' . $date_data . ' ' . $month_data;
                        $date .= ' ' . $times->format('H:i');
                    } else {
                        $day_data = $times->format('D');
                        $date_data = $times->format('d');
                        $month_data = $times->format('M');
                        $time_data = $times->format('h:i A');
                        $date = $day_data . ', ' . $date_data . ' ' . $month_data . ', ' . $time_data;
                    }
                }
                return $date;
            }
        }
    }

    // convert only time from timezone
    public function ep_convert_event_time_from_timezone($event, $end = 0) {
        if ($event) {
            if (!empty($end)) {
                $date = $this->ep_timestamp_to_date($event->em_end_date);
                $time = $event->em_end_time;
            } else {
                $date = $this->ep_timestamp_to_date($event->em_start_date);
                $time = $event->em_start_time;
            }
            if (empty($time)) {
                $time = '12:00 am';
            }
            $user_timezone = $this->ep_get_current_user_timezone();
            if (!empty($user_timezone)) {
                $site_timezone = $this->ep_get_site_timezone();
                if ($user_timezone != $site_timezone) {
                    $datetime = $date . ' ' . $time;
                    $times = $this->ep_datetime_to_timestamp($datetime, 'Y-m-d h:i A', $site_timezone, 1);
                    $times->setTimeZone(new DateTimeZone($user_timezone));
                    $date = $times->format('h:i A');
                    return $date;
                }
            }
        }
    }

    /*
     * Set get time zone
     */

    public function ep_get_site_timezone() {
        $userTimezone = wp_timezone_string();
//        if (empty($userTimezone)) {
//            $offset = (float) get_option('gmt_offset');
//            $userTimezone = $this->get_site_timezone_from_offset($offset);
//        }
//        if ($userTimezone == 'UTC') {
//            $userTimezone = date_default_timezone_get();
//        }
        return $userTimezone;
    }

    public function ep_isDate($value) {
        return strtotime($value) !== false || DateTime::createFromFormat('Y-m-d', $value) !== false;
    }

    /**
     * Convert datetime to timestamp
     */
    public function ep_datetime_to_timestamp($datetime, $format = 'Y-m-d', $timezone = '', $full_date = 0, $strict = 0) {
        if (empty($strict)) {
            $format = $this->ep_get_datepicker_format();
        }
        $timepicker_format_arr = $this->ep_get_global_settings('time_format');
        if (empty($timepicker_format_arr)) {
            $timepicker_format_arr = 'h:mmt';
        }
        if (!empty($timepicker_format_arr) && !empty($format)) {
            $timepicker_format_arr = explode(':', $timepicker_format_arr);
            $timeformat = $timepicker_format_arr[1];
            if ($timeformat == 'HH') {
                $format = $format . ' H:i A';
            } else {
                $format = $format . ' h:i A';
            }
        }

        if (!empty($timezone)) {
            $date = DateTime::createFromFormat($format, $datetime, new DateTimeZone($timezone));
        } else {
            $site_timezone = $this->ep_get_site_timezone();
            if (!empty($site_timezone)) {
                $date = DateTime::createFromFormat($format, $datetime, new DateTimeZone($site_timezone));
            } else {
                $date = DateTime::createFromFormat($format, $datetime);
            }
        }

        if (empty($date))
            return false;

        if (!empty($full_date)) {
            return $date;
        }

        //return strtotime( $date );

        return $date->getTimestamp();
    }

    
    /**
    * Convert a date/time string to a Unix timestamp (UTC-based epoch).
    *
    * @param string      $datetime   The input date/time string (must match the derived format).
    * @param string      $format     PHP date() style date part. Must be PHP tokens, not jQuery UI tokens.
    * @param string      $timezone   Optional timezone identifier; falls back to site timezone, then UTC.
    * @param int|bool    $full_date  If truthy, return DateTime object instead of timestamp.
    * @param int|bool    $strict     If falsy, $format may be replaced by site datepicker format (must be PHP-compatible).
    * @return int|DateTime|false
    */
    public function ep_datetime_to_timestamp_new($datetime, $format = 'Y-m-d', $timezone = '', $full_date = 0, $strict = 0) {
       // 1) Resolve the date format (ensure it's PHP-compatible)
       if (empty($strict)) {
           // IMPORTANT: ep_get_datepicker_format() must return a **PHP** format (e.g., 'Y-m-d').
           // If it returns a jQuery UI format, convert it before using.
           $datepicker_fmt = $this->ep_get_datepicker_format();
           if (!empty($datepicker_fmt)) {
               $format = $datepicker_fmt; // assume already PHP tokens
           }
       }

       $format = trim((string) $format);

       // 2) Determine the time pattern based on global setting
       $ui_time = $this->ep_get_global_settings('time_format');
       if (empty($ui_time)) {
           // Sensible default similar to jQuery timepicker 'h:mm tt'
           $ui_time = 'h:mm tt';
       }

       // Normalize and detect 24h vs 12h
       // Heuristics: any 'H' or 'HH' implies 24h; otherwise 12h.
       $is_24h = (strpos($ui_time, 'H') !== false || strpos($ui_time, 'HH') !== false);

       // Build PHP time format
       // - Minutes in PHP is 'i'
       // - 24h: 'H:i' (no meridiem)
       // - 12h: 'h:i A' (or 'a' if you prefer lowercase)
       $php_time = $is_24h ? 'H:i' : 'h:i A';

       // If the incoming string includes a time portion, we must include time in the format.
       // A simple heuristic: if $datetime has a colon (:), assume time is present.
       // If not, keep it date-only.
       $final_format = $format;
       if (strpos($datetime, ':') !== false) {
           $final_format = trim($format . ' ' . $php_time);
       }

       // 3) Choose timezone
       if (!empty($timezone)) {
           $tz = @new DateTimeZone($timezone);
       } else {
           $site_tz = $this->ep_get_site_timezone();
           $tz = !empty($site_tz) ? @new DateTimeZone($site_tz) : new DateTimeZone('UTC');
       }

       // 4) Parse
       $date = DateTime::createFromFormat($final_format, trim($datetime), $tz);

       // 5) If parsing failed, try a couple of common fallbacks (seconds, lowercase meridiem)
       if (!$date) {
           // Try with seconds
           if (strpos($final_format, 'H:i') !== false && strpos($datetime, ':') !== false) {
               $try_fmt = str_replace('H:i', 'H:i:s', $final_format);
               $date = DateTime::createFromFormat($try_fmt, trim($datetime), $tz);
           } elseif (strpos($final_format, 'h:i A') !== false && strpos($datetime, ':') !== false) {
               // Try lowercase meridiem
               $try_fmt = str_replace('h:i A', 'h:i a', $final_format);
               $date = DateTime::createFromFormat($try_fmt, trim($datetime), $tz);
           }
       }

       // 6) Final failure check with error details (useful while developing)
       if (!$date) {
           // Uncomment for debugging logs if needed:
           // $errors = DateTime::getLastErrors();
           // error_log('ep_datetime_to_timestamp parse failed. Format: '.$final_format.' Input: '.$datetime.' Errors: '.print_r($errors,true));
           return false;
       }

       if (!empty($full_date)) {
           return $date;
       }

       return $date->getTimestamp();
   }

    /**
     * Convert timestamp to date
     */
    public function ep_timestamp_to_datetime($timestamp, $format = 'Y-m-d h:i a', $strict = 0) {
        $datetime = '';
        if (!empty($timestamp)) {
            if (empty($strict)) {
                $format = $this->ep_get_datepicker_format();
                $time_format = $this->ep_get_global_settings( 'time_format' );
                if( ! empty( $time_format ) && $time_format == 'HH:mm' ) {
                    $timeIn24HourFormat = ' H:i';
                }
                else
                {
                    $timeIn24HourFormat = ' h:i A';
                }
                $format = $format . $timeIn24HourFormat;
            }
            $datetime = wp_date($format, $timestamp);
        }
        return $datetime;
    }

    public function ep_date_to_timestamp($date, $format = 'Y-m-d', $strict = 0, $with_time_zone = 1) {
        if (empty($strict)) {
            $format = $this->ep_get_datepicker_format();
        }
        if (!empty($with_time_zone)) {
            $site_timezone = $this->ep_get_site_timezone();
            if (!empty($site_timezone)) {
                $date = DateTime::createFromFormat($format, $date, new DateTimeZone($site_timezone));
            } else {
                $date = DateTime::createFromFormat($format, $date);
            }
        } else {
            $date = DateTime::createFromFormat($format, $date);
        }
        if (empty($date))
            return false;
        return $date->getTimestamp();
    }

    /**
     * get current time
     */
    public function ep_get_current_timestamp() {
        $site_timezone = $this->ep_get_site_timezone();
        $current_timestamp = current_time( 'timestamp',$site_timezone );

        return $current_timestamp;
    }

    // get user timezone
    public function ep_get_user_timezone() {
        $timezone_string = get_option('timezone_string');
        $gmt_offset = get_option('gmt_offset');

        if (empty($timezone_string)) {
            if (empty($gmt_offset)) {
                $timezone_string = 'UTC';
            } else {
                $timezone_string = $this->get_site_timezone_from_offset($gmt_offset);
            }
        }
       
        return $this-> ep_get_site_timezone();
    }

    /*
     * Time zone offset
     */

    public function get_site_timezone_from_offset($offset) {
        $offset = (string) $offset;
        $timezones = array(
            '-12' => 'Pacific/Auckland',
            '-11.5' => 'Pacific/Auckland', // Approx
            '-11' => 'Pacific/Apia',
            '-10.5' => 'Pacific/Apia', // Approx
            '-10' => 'Pacific/Honolulu',
            '-9.5' => 'Pacific/Honolulu', // Approx
            '-9' => 'America/Anchorage',
            '-8.5' => 'America/Anchorage', // Approx
            '-8' => 'America/Los_Angeles',
            '-7.5' => 'America/Los_Angeles', // Approx
            '-7' => 'America/Denver',
            '-6.5' => 'America/Denver', // Approx
            '-6' => 'America/Chicago',
            '-5.5' => 'America/Chicago', // Approx
            '-5' => 'America/New_York',
            '-4.5' => 'America/New_York', // Approx
            '-4' => 'America/Halifax',
            '-3.5' => 'America/Halifax', // Approx
            '-3' => 'America/Sao_Paulo',
            '-2.5' => 'America/Sao_Paulo', // Approx
            '-2' => 'America/Sao_Paulo',
            '-1.5' => 'Atlantic/Azores', // Approx
            '-1' => 'Atlantic/Azores',
            '-0.5' => 'UTC', // Approx
            '0' => 'UTC',
            '0.5' => 'UTC', // Approx
            '1' => 'Europe/Paris',
            '1.5' => 'Europe/Paris', // Approx
            '2' => 'Europe/Helsinki',
            '2.5' => 'Europe/Helsinki', // Approx
            '3' => 'Europe/Moscow',
            '3.5' => 'Europe/Moscow', // Approx
            '4' => 'Asia/Dubai',
            '4.5' => 'Asia/Tehran',
            '5' => 'Asia/Karachi',
            '5.5' => 'Asia/Kolkata',
            '5.75' => 'Asia/Katmandu',
            '6' => 'Asia/Yekaterinburg',
            '6.5' => 'Asia/Yekaterinburg', // Approx
            '7' => 'Asia/Krasnoyarsk',
            '7.5' => 'Asia/Krasnoyarsk', // Approx
            '8' => 'Asia/Shanghai',
            '8.5' => 'Asia/Shanghai', // Approx
            '8.75' => 'Asia/Tokyo', // Approx
            '9' => 'Asia/Tokyo',
            '9.5' => 'Asia/Tokyo', // Approx
            '10' => 'Australia/Melbourne',
            '10.5' => 'Australia/Adelaide',
            '11' => 'Australia/Melbourne', // Approx
            '11.5' => 'Pacific/Auckland', // Approx
            '12' => 'Pacific/Auckland',
            '12.75' => 'Pacific/Apia', // Approx
            '13' => 'Pacific/Apia',
            '13.75' => 'Pacific/Honolulu', // Approx
            '14' => 'Pacific/Honolulu',
        );
        $timezone = isset($timezones[$offset]) ? $timezones[$offset] : NULL;
        return $timezone;
    }

    // get current user timezone
    public function ep_get_current_user_timezone() {
        $user_timezone_meta = '';
        if (!empty($this->ep_get_global_settings('enable_event_time_to_user_timezone'))) {
            $user_id = get_current_user_id();
            // if user is loggedin
            /* if (!empty($user_id)) {
                // check from user meta
                $user_timezone_meta = get_user_meta($user_id, 'ep_user_timezone_meta', true);
                if (empty($user_timezone_meta)) {
                    // check if set in cookie
                    if (isset($_COOKIE['ep_user_timezone_meta'])) {
                        $user_timezone_meta = $_COOKIE['ep_user_timezone_meta'];
                        add_user_meta($user_id, 'ep_user_timezone_meta', $user_timezone_meta);
                        setcookie('ep_user_timezone_meta', '', time() - 3600);
                    }
                }
            } else {
                // for non loggedin user check if set in cookie
                if (isset($_COOKIE['ep_user_timezone_meta'])) {
                    $user_timezone_meta = $_COOKIE['ep_user_timezone_meta'];
                }
            }
             * 
             */
            // if user did not save timezone then return site timezone
            if (empty($user_timezone_meta)) {
                $user_timezone_meta = $this->ep_get_user_timezone();
            }
            //check for offset
            if (strpos($user_timezone_meta, 'UTC+') !== false) {
                $exp_meta = explode('+', $user_timezone_meta);
                if (!empty($exp_meta[1])) {
                    $exp_offset = $exp_meta[1];
                    if (!empty($exp_offset)) {
                        $user_timezone_meta = $this->get_site_timezone_from_offset($exp_offset);
                    }
                }
            }
            if (strpos($user_timezone_meta, 'UTC-') !== false) {
                $exp_meta = explode('-', $user_timezone_meta);
                if (!empty($exp_meta[1])) {
                    $exp_offset = $exp_meta[1];
                    if (!empty($exp_offset)) {
                        $user_timezone_meta = $this->get_site_timezone_from_offset($exp_offset);
                    }
                }
            }
        }
        if ($user_timezone_meta == 'UTC+0') {
            $user_timezone_meta = 'UTC';
        }
        
        return $this->ep_get_site_timezone();
    }

    public function ep_get_datepicker_format($language = 1) {
        $format = 'Y-m-d';
        if ($language == 2) {
            $format = 'yy-mm-dd';
        }
        if (!empty($this->ep_get_global_settings('datepicker_format')) && is_string($this->ep_get_global_settings('datepicker_format'))) {
            //var_dump($this->ep_get_global_settings('datepicker_format'));die;
            $datepicker_format = explode('&', $this->ep_get_global_settings('datepicker_format'));
            if (!empty($datepicker_format)) {
                $format = $datepicker_format[1];
                if ($language == 2) {
                    $format = $datepicker_format[0];
                }
            }
        }
        return $format;
    }

    public function ep_get_day_with_position($day) {
        $suffix = $day;
        if (!empty($day)) {
            if ($day < 11 || $day > 20) {
                if ($day == 10) {
                    $suffix .= 'th';
                } else if (substr((string) $day, -1) == '1') {
                    $suffix .= 'st';
                } else if (substr((string) $day, -1) == '2') {
                    $suffix .= 'nd';
                } else if (substr((string) $day, -1) == '3') {
                    $suffix .= 'rd';
                } else {
                    $suffix .= 'th';
                }
            } else {
                $suffix .= 'th';
            }
        }
        return $suffix;
    }

    /**
     * Return week day in short
     */
    public function ep_get_week_day_short() {
        $short_week_days = array(
            esc_html__('S', 'eventprime-event-calendar-management'),
            esc_html__('M', 'eventprime-event-calendar-management'),
            esc_html__('T', 'eventprime-event-calendar-management'),
            esc_html__('W', 'eventprime-event-calendar-management'),
            esc_html__('T', 'eventprime-event-calendar-management'),
            esc_html__('F', 'eventprime-event-calendar-management'),
            esc_html__('S', 'eventprime-event-calendar-management'),
        );
        return $short_week_days;
    }

    /**
     * Return week day in medium
     */
    public function ep_get_week_day_medium() {
        $medium_week_days = array(
            'mon' => esc_html__('Mon', 'eventprime-event-calendar-management'),
            'tue' => esc_html__('Tue', 'eventprime-event-calendar-management'),
            'wed' => esc_html__('Wed', 'eventprime-event-calendar-management'),
            'thu' => esc_html__('Thu', 'eventprime-event-calendar-management'),
            'fri' => esc_html__('Fri', 'eventprime-event-calendar-management'),
            'sat' => esc_html__('Sat', 'eventprime-event-calendar-management'),
            'sun' => esc_html__('Sun', 'eventprime-event-calendar-management')
        );
        return $medium_week_days;
    }

    /**
     * Return full week day
     */
    public function ep_get_week_day_full() {
        $full_week_days = array(
            esc_html__('Sunday', 'eventprime-event-calendar-management'),
            esc_html__('Monday', 'eventprime-event-calendar-management'),
            esc_html__('Tuesday', 'eventprime-event-calendar-management'),
            esc_html__('Wednesday', 'eventprime-event-calendar-management'),
            esc_html__('Thursday', 'eventprime-event-calendar-management'),
            esc_html__('Friday', 'eventprime-event-calendar-management'),
            esc_html__('Saturday', 'eventprime-event-calendar-management'),
        );
        return $full_week_days;
    }

    /**
     * Return week number
     */
    public function ep_get_week_number() {
        $week_number = array(
            '1' => esc_html__('First', 'eventprime-event-calendar-management'),
            '2' => esc_html__('Second', 'eventprime-event-calendar-management'),
            '3' => esc_html__('Third', 'eventprime-event-calendar-management'),
            '4' => esc_html__('Fourth', 'eventprime-event-calendar-management'),
            '5' => esc_html__('Last', 'eventprime-event-calendar-management'),
        );
        return $week_number;
    }

    /**
     * Return current week no.
     */
    public function ep_get_current_week_no() {
        $date = gmdate("Y-m-d");
        $first_of_month = gmdate("Y-m-01", strtotime($date));
        $current_week_no = intval(gmdate("W", strtotime($date))) - intval(gmdate("W", strtotime($first_of_month)));
        return $current_week_no;
    }

    /**
     * Return month name
     */
    public function ep_get_month_name() {
        $month_name = array(
            '1' => esc_html__('January', 'eventprime-event-calendar-management'),
            '2' => esc_html__('February', 'eventprime-event-calendar-management'),
            '3' => esc_html__('March', 'eventprime-event-calendar-management'),
            '4' => esc_html__('April', 'eventprime-event-calendar-management'),
            '5' => esc_html__('May', 'eventprime-event-calendar-management'),
            '6' => esc_html__('June', 'eventprime-event-calendar-management'),
            '7' => esc_html__('July', 'eventprime-event-calendar-management'),
            '8' => esc_html__('August', 'eventprime-event-calendar-management'),
            '9' => esc_html__('September', 'eventprime-event-calendar-management'),
            '10' => esc_html__('October', 'eventprime-event-calendar-management'),
            '11' => esc_html__('November', 'eventprime-event-calendar-management'),
            '12' => esc_html__('December', 'eventprime-event-calendar-management'),
        );
        return $month_name;
    }

    public function ep_global_settings_button_title($button_title, $text_domain = 'eventprime-event-calendar-management') {
        $button_titles = $this->ep_get_global_settings('button_titles');
        if (is_object($button_titles)) {
            $button_titles = (array) $button_titles;
        }
        if (!empty($button_titles) && isset($button_titles[$button_title]) && !empty($button_titles[$button_title])) {
            return esc_html__($button_titles[$button_title], $text_domain);
        } else {
            return esc_html__($button_title, $text_domain);
        }
    }
    
    

    public function custom_em_venue_dropdown_old($post, $box) {
        $taxonomy = 'em_venue';
        $tax = get_taxonomy($taxonomy);
        $name = esc_attr($tax->name);
        $terms = get_terms($taxonomy, array('hide_empty' => false));

        // Output the dropdown
        echo '<select name="tax_input[' . esc_attr($name) . ']">';
        echo '<option value="">Select ' . esc_attr($tax->label) . '</option>';
        foreach ($terms as $term) {
            $selected = has_term($term->term_id, $taxonomy, $post->ID) ? 'selected' : "";
            echo '<option value="' . esc_attr($term->term_id) . '" ' . !empty($selected)?esc_attr($selected):'' . '>' . esc_html($term->name) . '</option>';
        }
        echo '</select>';
    }

    public function custom_em_event_type_dropdown($post, $box) {
        $taxonomy = 'em_event_type';
        $tax = get_taxonomy($taxonomy);
        $name = esc_attr($tax->name);
        $terms = get_terms(array( 'taxonomy' => $taxonomy,'hide_empty' => false));
        //print_r($terms);die;
        // Output the dropdown
        echo '<select name="tax_input[' . esc_attr($name) . ']" class="widefat">';
        echo '<option value="">Select ' . esc_attr($tax->label) . '</option>';
        foreach ($terms as $term) {
            $selected = has_term($term->term_id, $taxonomy, $post->ID) ? 'selected' : "";
            echo '<option value="' . esc_attr($term->term_id) . '" '.esc_attr($selected).'>' . esc_html($term->name) . '</option>';
           
        }
        echo '</select>';
    }

    public function check_event_has_expired($event) {
        $expired = false;
        if ($event) {
            if (is_int($event)) {
                $event_end_date = get_post_meta($event, 'em_end_date', true);
                $event_end_time = get_post_meta($event, 'em_end_time', true);
            } else {
                if (!empty($event->em_end_date)) {
                    $event_end_date = $event->em_end_date;
                    if (!empty($event->em_end_time)) {
                        $event_end_time = $event->em_end_time;
                    }
                }
            }
            if (!empty($event_end_date)) {
                $end_date = $event_end_date;
                if (!empty($event_end_time)) {
                    $end_date = $this->ep_timestamp_to_date($event_end_date);
                    
                    $end_date .= ' ' . $event_end_time;
                   
                    $end_date = $this->ep_datetime_to_timestamp($end_date);
                    
                }
                if ($end_date < $this->ep_get_current_timestamp()) {
                    $expired = true;
                }
            }
        }

        return $expired;
    }

    public function get_existing_category_lists($post_id) {
        $dbhandler = new EP_DBhandler;
        $get_field_data = $dbhandler->get_all_result('TICKET_CATEGORIES', array('id', 'name', 'capacity'), array('event_id' => $post_id, 'status' => 1), 'results', 0, false, 'priority', false);
        return $get_field_data;
    }

    public function get_existing_individual_ticket_lists($post_id, $reset_keys = true) {
        $get_field_data = array();
        if (!empty($post_id)) {
            $dbhandler = new EP_DBhandler;
            $get_field_data = $dbhandler->get_all_result('TICKET', '*', array('event_id' => $post_id, 'category_id' => 0, 'status' => 1), 'results', 0, false, 'priority', false);
            // Format tickets for start booking and end bookings keys. This need to be update for sorting.
            if (!empty($get_field_data)) {
                $get_field_data = stripslashes_deep($get_field_data);
                if (!empty($reset_keys)) {
                    foreach ($get_field_data as $cat_key => $cat_ticket) {
                        if (!empty($cat_ticket->booking_starts)) {
                            $booking_start = json_decode(stripslashes($cat_ticket->booking_starts));
                            if (!empty($booking_start)) {
                                $updated_booking_start = array();
                                if (isset($booking_start->booking_type)) {
                                    $updated_booking_start['em_ticket_start_booking_type'] = $booking_start->booking_type;
                                }
                                if (isset($booking_start->start_date)) {
                                    $updated_booking_start['em_ticket_start_booking_date'] = $booking_start->start_date;
                                }
                                if (isset($booking_start->start_time)) {
                                    $updated_booking_start['em_ticket_start_booking_time'] = $booking_start->start_time;
                                }
                                if (isset($booking_start->event_option)) {
                                    $updated_booking_start['em_ticket_start_booking_event_option'] = $booking_start->event_option;
                                }
                                if (isset($booking_start->days)) {
                                    $updated_booking_start['em_ticket_start_booking_days'] = $booking_start->days;
                                }
                                if (isset($booking_start->days_option)) {
                                    $updated_booking_start['em_ticket_start_booking_days_option'] = $booking_start->days_option;
                                }
                                $cat_ticket->booking_starts = wp_json_encode($updated_booking_start);
                            }
                        }

                        if (!empty($cat_ticket->booking_ends)) {
                            $booking_end = json_decode(stripslashes($cat_ticket->booking_ends));
                            if (!empty($booking_end)) {
                                $updated_booking_end = array();
                                if (isset($booking_end->booking_type)) {
                                    $updated_booking_end['em_ticket_ends_booking_type'] = $booking_end->booking_type;
                                }
                                if (isset($booking_end->end_date)) {
                                    $updated_booking_end['em_ticket_ends_booking_date'] = $booking_end->end_date;
                                }
                                if (isset($booking_end->end_time)) {
                                    $updated_booking_end['em_ticket_ends_booking_time'] = $booking_end->end_time;
                                }
                                if (isset($booking_end->event_option)) {
                                    $updated_booking_end['em_ticket_ends_booking_event_option'] = $booking_end->event_option;
                                }
                                if (isset($booking_end->days)) {
                                    $updated_booking_end['em_ticket_ends_booking_days'] = $booking_end->days;
                                }
                                if (isset($booking_end->days_option)) {
                                    $updated_booking_end['em_ticket_ends_booking_days_option'] = $booking_end->days_option;
                                }
                                $cat_ticket->booking_ends = wp_json_encode($updated_booking_end);
                            }
                        }

                        $get_field_data[$cat_key] = $cat_ticket;
                    }
                }
            }
        }
        return $get_field_data;
    }

    public function get_category_tickets_capacity($cat_ticket_data) {
        $capacity = 0;
        if (!empty($cat_ticket_data)) {
            foreach ($cat_ticket_data as $ticket) {
                $capacity += $ticket->capacity;
            }
        }
        return $capacity;
    }

    public function ep_price_with_position($price, $currency_symbol = '', $format_price = true) {
        if ($format_price) {
            $price = number_format_i18n($price, 2);
        }
        if (empty($currency_symbol)) {
            $currency_symbol = $this->ep_currency_symbol();
        }
        $currency_position = $this->ep_get_global_settings('currency_position');
        $price_with_curr_pos = $currency_symbol . $price;
        if ($currency_position == 'before_space') {
            $price_with_curr_pos = $currency_symbol . ' ' . $price;
        }
        if ($currency_position == 'after') {
            $price_with_curr_pos = $price . $currency_symbol;
        }
        if ($currency_position == 'after_space') {
            $price_with_curr_pos = $price . ' ' . $currency_symbol;
        }
        return $price_with_curr_pos;
    }

    public function ep_social_sharing_fields() {
        $social_sharing_fields = apply_filters( 'ep_social_sharing_fields_key_values', array('facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'Linkedin', 'twitter' => 'Twitter', 'youtube' => 'Youtube') ); 
        return $social_sharing_fields;
    }

    public function get_existing_category_ticket_lists($post_id, $cat_id, $reset_keys = true) {
        $get_field_data = array();
        if (!empty($post_id) && !empty($cat_id)) {
            $dbhandler = new EP_DBhandler;
            $get_field_data = $dbhandler->get_all_result('TICKET', '*', array('event_id' => $post_id, 'category_id' => $cat_id, 'status' => 1), 'results', 0, false, 'priority', false);

            if (!empty($get_field_data)) {
                $get_field_data = stripslashes_deep($get_field_data);
                if (!empty($reset_keys)) {
                    foreach ($get_field_data as $cat_key => $cat_ticket) {
                        if (!empty($cat_ticket->booking_starts)) {
                            $booking_start = json_decode(stripslashes($cat_ticket->booking_starts));
                            if (!empty($booking_start)) {
                                $updated_booking_start = array();
                                if (isset($booking_start->booking_type)) {
                                    $updated_booking_start['em_ticket_start_booking_type'] = $booking_start->booking_type;
                                }
                                if (isset($booking_start->start_date)) {
                                    $updated_booking_start['em_ticket_start_booking_date'] = $booking_start->start_date;
                                }
                                if (isset($booking_start->start_time)) {
                                    $updated_booking_start['em_ticket_start_booking_time'] = $booking_start->start_time;
                                }
                                if (isset($booking_start->event_option)) {
                                    $updated_booking_start['em_ticket_start_booking_event_option'] = $booking_start->event_option;
                                }
                                if (isset($booking_start->days)) {
                                    $updated_booking_start['em_ticket_start_booking_days'] = $booking_start->days;
                                }
                                if (isset($booking_start->days_option)) {
                                    $updated_booking_start['em_ticket_start_booking_days_option'] = $booking_start->days_option;
                                }
                                $cat_ticket->booking_starts = wp_json_encode($updated_booking_start);
                            }
                        }

                        if (!empty($cat_ticket->booking_ends)) {
                            $booking_end = json_decode(stripslashes($cat_ticket->booking_ends));
                            if (!empty($booking_end)) {
                                $updated_booking_end = array();
                                if (isset($booking_end->booking_type)) {
                                    $updated_booking_end['em_ticket_ends_booking_type'] = $booking_end->booking_type;
                                }
                                if (isset($booking_end->end_date)) {
                                    $updated_booking_end['em_ticket_ends_booking_date'] = $booking_end->end_date;
                                }
                                if (isset($booking_end->end_time)) {
                                    $updated_booking_end['em_ticket_ends_booking_time'] = $booking_end->end_time;
                                }
                                if (isset($booking_end->event_option)) {
                                    $updated_booking_end['em_ticket_ends_booking_event_option'] = $booking_end->event_option;
                                }
                                if (isset($booking_end->days)) {
                                    $updated_booking_end['em_ticket_ends_booking_days'] = $booking_end->days;
                                }
                                if (isset($booking_end->days_option)) {
                                    $updated_booking_end['em_ticket_ends_booking_days_option'] = $booking_end->days_option;
                                }
                                $cat_ticket->booking_ends = wp_json_encode($updated_booking_end);
                            }
                        }

                        $get_field_data[$cat_key] = $cat_ticket;
                    }
                }
            }
        }
        return $get_field_data;
    }

    public function get_ticket_booking_event_date_options($event_id) {
        $event_date_options = array();
        if ($event_id) {
            $event_date_options['event_start'] = esc_html__('Event Start', 'eventprime-event-calendar-management');
            $event_date_options['event_ends'] = esc_html__('Event Ends', 'eventprime-event-calendar-management');
            $more_dates = get_post_meta($event_id, 'em_event_add_more_dates', true);
            if (!empty($more_dates) && count($more_dates) > 0) {
                foreach ($more_dates as $more) {
                    if (empty($more['label']))
                        continue;

                    $option_val = $more['uid'];
                    $event_date_options[$option_val] = $more['label'];
                }
            }
        }
        return $event_date_options;
    }

    public function get_event_ticket_category($event_id) {
        if (empty($event_id))
            return;
        $cat_data = array();
        $dbhandler = new EP_DBhandler;
        $get_cat_data = $dbhandler->get_all_result('TICKET_CATEGORIES', '*', array('event_id' => $event_id), 'results', 0, false, 'priority', false);
        if (!empty($get_cat_data) && count($get_cat_data) > 0) {
            foreach ($get_cat_data as $category) {
                // get tickets from category id and event id
                $get_ticket_data = $dbhandler->get_all_result('TICKET', '*', array('event_id' => $event_id, 'category_id' => $category->id), 'results', 0, false, 'priority', false);
                if (!empty($get_ticket_data) && count($get_ticket_data) > 0) {
                    $category->tickets = $get_ticket_data;
                }
                $cat_data[] = $category;
            }
        }
        return $cat_data;
    }

    public function get_event_solo_ticket($event_id) {
        $dbhandler = new EP_DBhandler;
        $ticket_data = $dbhandler->get_all_result('TICKET', '*', array('event_id' => $event_id, 'category_id' => 0), 'results', 0, false, 'priority', false);
        return $ticket_data;
    }

    public function get_ticket_price_range($event_categories, $event_tickets) {
        $tickets = $price_range = array();
        // check event categories has ticket
        if (!empty($event_categories)) {
            foreach ($event_categories as $category) {
                if (!empty($category->tickets)) {
                    $tickets = array_merge($tickets, $category->tickets);
                }
            }
        }
        // merge tickets
        if (!empty($event_tickets)) {
            $tickets = array_merge($tickets, $event_tickets);
        }
        if (!empty($tickets) && count($tickets) > 0) {
            if (count($tickets) > 1) {
                $price_range['multiple'] = 1;
                $prices = array();
                foreach ($tickets as $ticket) {
                    $prices[] = $ticket->price;
                }
                $min_price = min($prices);
                $max_price = max($prices);
                $price_range['min'] = $min_price;
                $price_range['max'] = $max_price;
            } else {
                $price_range['multiple'] = 0;
                foreach ($tickets as $ticket) {
                    $price_range['price'] = $ticket->price;
                }
            }
        }
        return $price_range;
    }

    public function get_event_all_tickets($event) {
        $all_tickets = array();
        if (!empty($event)) {
            // get tickets from category
            $ticket_categories = $event->ticket_categories;
            if (!empty($ticket_categories) && count($ticket_categories) > 0) {
                foreach ($ticket_categories as $category) {
                    if (isset($category->tickets) && !empty($category->tickets)) {
                        $all_tickets = array_merge($all_tickets, $category->tickets);
                    }
                }
            }
            // get individual tickets
            $solo_tickets = $event->solo_tickets;
            if (!empty($solo_tickets) && count($solo_tickets) > 0) {
                $all_tickets = array_merge($all_tickets, $solo_tickets);
            }
        }
        $all_tickets = apply_filters( 'ep_filter_event_all_tickets_data', $all_tickets, $event ); 
        return $all_tickets;
    }
    
    public function ep_get_term($term_id) {
        // Validate the term ID
        $term_id = $this->ep_get_filter_taxonomy_id($term_id);
        if (empty($term_id))
        {
            return;
        }
        $term_id = absint($term_id);
        if (empty($term_id)) {
            return null;
        }

        // Generate a cache key specific to the term ID
        $cache_key = "ep_term_{$term_id}";

        // Check if the name is already cached
        $cached_name = get_transient($cache_key);
        if ($cached_name !== false) {
            return $cached_name;
        }

        // Fetch the term object
        $term = get_term($term_id);
        if (is_wp_error($term) || empty($term)) {
            return null; // Return null if the term doesn't exist or there's an error
        }

        // Cache the term name for 1 hour (3600 seconds)
        set_transient($cache_key, $term, HOUR_IN_SECONDS);
        
        return $term;
    }


    public function get_single_venue($term_id, $term = null) {
        $term_id = $this->ep_get_filter_taxonomy_id($term_id);
        if (empty($term_id))
        {
            return;
        } 
        $venue = new stdClass();
        $meta = get_term_meta($term_id);
        //print_r($meta);die;
        if(!empty($meta))
        {
            foreach ($meta as $key => $val) {
                if($key=='em_type')
                {
                    $activate_extensions = $this->ep_get_activate_extensions();
                    if( !in_array( 'Eventprime_Live_Seating', $activate_extensions ) ) {
                        $val[0] = 'standings';
                    }
                }
                $venue->{$key} = maybe_unserialize($val[0]);
            }
        }
        //print_r($venue);die;
        if (empty($term)) {
            $term = get_term($term_id);
        }
        if (empty($term))
        {
            return;
        }
        
        if(!isset($term->term_id))
        {
            return;
        }
        $venue->id = $term->term_id;
        $venue->name = htmlspecialchars_decode($term->name);
        $venue->slug = $term->slug;
        $venue->description = $term->description;
        $venue->count = $term->count;
        $venue->venue_url = $this->ep_get_custom_page_url('venues_page', $term->term_id, 'venue', 'term');
        $venue->image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'admin/partials/images/dummy_image.png';
        if(empty($venue->em_address))
        {
            $venue->em_address = !empty( $term->em_address ) ? $term->em_address : '';
        }
        $venue->other_image_url = array();
        if (!empty($venue->em_gallery_images)) {
            if (count($venue->em_gallery_images) > 0) {
                $img_url = wp_get_attachment_image_src($venue->em_gallery_images[0], 'large');
                if (!empty($img_url) && isset($img_url[0])) {
                    $venue->image_url = $img_url[0];
                }
            }
            // other images
            if (count($venue->em_gallery_images) > 1) {
                for ($i = 1; $i < count($venue->em_gallery_images); $i++) {
                    $venue->other_image_url[] = wp_get_attachment_image_src($venue->em_gallery_images[$i], 'large')[0];
                }
            }
        }

        return $venue;
    }
    
    public function ep_get_filter_taxonomy_id($term_id)
    {
        $termid = '';
        if(!empty($term_id) && is_array($term_id))
        {
            foreach($term_id as $val)
            {
                if($val!==0)
                {
                    $termid = $val;
                }
            }
        }
        else
        {
            $termid = $term_id;
        }
        return $termid;
    }

    public function get_single_event_type($term_id, $term = null) {
        
        $term_id = $this->ep_get_filter_taxonomy_id($term_id);
        if (empty($term_id))
        {
            return;
        }
        //print_r($termid);die;
        $event_type = new stdClass();
        $meta = get_term_meta($term_id);
        if(!empty($meta))
        {
            foreach ($meta as $key => $val) {
                $event_type->{$key} = maybe_unserialize($val[0]);
            }
        }
        
        if (empty($term)) {
            $term = get_term($term_id);
        }
        if (empty($term))
        {
            return;
        }
        $event_type->id = $term_id;
        $event_type->name = htmlspecialchars_decode($term->name);
        $event_type->slug = $term->slug;
        $event_type->description = $term->description;
        $event_type->count = $term->count;
        $event_type->event_type_url = $this->ep_get_custom_page_url('event_types', $term_id, 'event_type', 'term');
        $event_type->image_url = $this->get_event_type_image_url($term_id);
        return $event_type;
    }

    public function get_event_type_image_url($term_id) {
        $image_url = plugin_dir_url( EP_PLUGIN_FILE ). 'admin/partials/images/dummy_image.png';
        $thumb_id = get_term_meta($term_id, 'em_image_id', true);
        if ($thumb_id) {
            $image_url = wp_get_attachment_url($thumb_id);
        }
        return $image_url;
    }

    public function get_single_organizer($term_id, $term = null) {
        if (empty($term_id))
            return;

        $organizer = new stdClass();
        $meta = get_term_meta($term_id);
        if (!empty($meta)) {
            foreach ($meta as $key => $val) {
                $organizer->{$key} = maybe_unserialize($val[0]);
            }
        }

        if (empty($term)) {
            $term = get_term($term_id);
        }

        if (empty($term))
            return;
        $organizer->id = $term->term_id;
        $organizer->name = htmlspecialchars_decode($term->name);
        $organizer->slug = $term->slug;
        $organizer->description = $term->description;
        $organizer->count = $term->count;
        $organizer->organizer_url = $this->ep_get_custom_page_url('event_organizers', $term->term_id, 'organizer', 'term');
        $organizer->image_url = $this->get_event_organizer_image_url($term->term_id);

        return $organizer;
    }

    public function get_event_organizer_image_url($term_id) {
        $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/dummy-user.png';
        $thumb_id = get_term_meta($term_id, 'em_image_id', true);
        if ($thumb_id) {
            $image_url = wp_get_attachment_url($thumb_id);
        }
        return $image_url;
    }

    public function get_single_performer($post_id, $post = null) {
        if (empty($post_id))
            return;

        $performer = new stdClass();
        $meta = get_post_meta($post_id);
        if (!empty($meta)) {
            foreach ($meta as $key => $val) {
                $performer->{$key} = maybe_unserialize($val[0]);
            }
        }
        if (empty($post)) {
            $post = get_post($post_id);
        }
        if (!empty($post)) {
            $performer->id = $post->ID;
            $performer->name = $post->post_title;
            $performer->slug = $post->post_name;
            $performer->description = $post->post_content;
            //$performer->performer_url = ep_get_custom_page_url( 'performers_page', $performer->id, 'performer' );
            $performer->performer_url = $this->get_performer_single_url($performer->id);
            $performer->image_url = $this->get_performer_image_url($performer->id);
        }

        return $performer;
    }

    public function get_performer_image_url($performer_id) {
        $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/dummy-user.png';
        if (has_post_thumbnail($performer_id)) {
            $image_url = wp_get_attachment_image_src(get_post_thumbnail_id($performer_id), 'large')[0];
        }
        return $image_url;
    }

    public function get_performer_single_url($performer_id) {
        $performer_url = $this->ep_get_custom_page_url('performers_page', $performer_id, 'performer');
        $performer_url = apply_filters('ep_perfomers_url_modify', $performer_url, $performer_id);
        return $performer_url;
    }

    public function get_event_image_url($event_id) {
        $image_url = '';
        if (has_post_thumbnail($event_id)) {
            if (is_array(wp_get_attachment_image_src(get_post_thumbnail_id($event_id), 'full'))) {
                $image_url = wp_get_attachment_image_src(get_post_thumbnail_id($event_id), 'full')[0];
            }
        }
        return $image_url;
    }

    public function ep_get_child_events($post_id, $args = array()) {
        $default = array(
            'post_parent' => $post_id,
            'post_type' => 'em_event',
            'post_status' => 'any',
            'numberposts' => -1,
            'orderby' => 'em_start_date',
            'order' => 'ASC',
        );
        $args = wp_parse_args($args, $default);
        $posts = get_posts($args);
        return $posts;
    }

    public function load_event_full_data($events) {
        $events_data = array();
        if (!empty($events) && count($events) > 0) {
            foreach ($events as $post) {
                $event = $this->get_single_event($post->ID, $post);
                if (!empty($event)) {
                    $events_data[] = $event;
                }
            }
        }
        return $events_data;
    }
    
    public function load_event_full_data_detail($events) {
        $events_data = array();
        if (!empty($events) && count($events) > 0) {
            foreach ($events as $post) {
                $event = $this->get_single_event_detail($post->ID, $post);
                if (!empty($event)) {
                    $events_data[] = $event;
                }
            }
        }
        return $events_data;
    }

    public function get_event_all_offers($event) {
        $all_offers_data = array(
            'all_offers' => array(),
            'all_show_offers' => array(),
            'show_ticket_offers' => array(),
            'ticket_offers' => array(),
            'applicable_offers' => array()
        );
        if (!empty($event)) {
            $all_tickets = $this->get_event_all_tickets($event);
            if (!empty($all_tickets) && count($all_tickets) > 0) {
                foreach ($all_tickets as $ticket) {
                    if (!empty($ticket->offers)) {
                        $all_offers_data = $this->get_event_single_offer_data($all_offers_data, $ticket, $event->em_id,0,$event);
                    }
                }
            }
        }
        return $all_offers_data;
    }

    /**
     * Update all offer data from single offer
     */
    public function get_event_single_offer_data($all_offers_data, $ticket, $event_id, $qty = 0,$event=null) {
        $ticket_offers = json_decode($ticket->offers);
        if (!empty($ticket_offers)) {
            foreach ($ticket_offers as $to) {
                $all_offers_data['all_offers'][] = $to;
                if (isset($to->em_ticket_show_offer_detail) && !empty($to->em_ticket_show_offer_detail)) {
                    $all_offers_data['all_show_offers'][$to->uid] = $to;
                    $all_offers_data['show_ticket_offers'][$ticket->id][$to->uid] = $to;
                }
                $all_offers_data['ticket_offers'][$ticket->id][$to->uid] = $to;
            }
            $offer_applied_data = $this->get_event_offer_applied_data($ticket_offers, $ticket, $event_id, $qty,$event);
            if (!empty($offer_applied_data) && count($offer_applied_data) > 0) {
                foreach ($offer_applied_data as $applied_offer_key => $ep_applied_offer) {
                    $all_offers_data['applicable_offers'][$ticket->id][$applied_offer_key] = $ep_applied_offer;
                }
            }
        }
        return $all_offers_data;
    }

    public function get_event_offer_applied_data($offers, $ticket, $event_id, $qty = 0,$event=null) {
        $offer_data = array();
        if (!empty($offers)) {
            $i = 1;
            foreach ($offers as $offer) {
                $applied_status = $this->check_event_offer_applied($offer, $ticket, $event_id, $qty,$event);
                if (!empty($applied_status)) {
                    if (is_object($applied_status)) { //offer data updated from method
                        $offer_data[$offer->uid] = $applied_status;
                    } else {
                        $offer_data[$offer->uid] = $offer;
                    }
                    // check for multiple offer handle condition
                    if (!empty($ticket->multiple_offers_option)) {
                        if ($ticket->multiple_offers_option == 'first_offer') {
                            if (count($offer_data) > 0) {
                                break;
                            }
                        }
                    }
                }
                $i++;
            }
        }
        return $offer_data;
    }

    public function check_event_offer_applied($offer, $ticket, $event_id, $qty = 0, $event = null) {
        $applied = 0;
        $dbhandler = new EP_DBhandler;
        if (!empty($offer)) {
            $current_time = $this->ep_get_current_timestamp();
            $min_date = $max_date = $current_time;
            $event_start_date = (!empty($event) && isset($event->em_start_date))? $event->em_start_date : get_post_meta($event_id, 'em_start_date', true);
            $event_start_time = (!empty($event) && isset($event->em_start_time))? $event->em_start_time : get_post_meta($event_id, 'em_start_time', true);
            $event_end_date = (!empty($event) && isset($event->em_end_date))? $event->em_end_date : get_post_meta($event_id, 'em_end_date', true);
            $event_end_time = (!empty($event) && isset($event->em_end_time))? $event->em_end_time : get_post_meta($event_id, 'em_end_time', true);
            $event_add_more_dates = (!empty($event) && isset($event->em_event_add_more_dates))? $event->em_event_add_more_dates : get_post_meta($event_id, 'em_event_add_more_dates', true);
            // offer start date
            $offer_start_booking_type = $offer->em_offer_start_booking_type;
            if ($offer_start_booking_type == 'custom_date') {
                if (isset($offer->em_offer_start_booking_date) && !empty($offer->em_offer_start_booking_date)) {
                    $offer_start_date = $offer->em_offer_start_booking_date;
                    if (isset($offer->em_offer_start_booking_time) && !empty($offer->em_offer_start_booking_time)) {
                        $offer_start_date .= ' ' . $offer->em_offer_start_booking_time;
                        $min_date = $this->ep_datetime_to_timestamp($offer_start_date);
                    } else {
                        $min_date = $this->ep_date_to_timestamp($offer_start_date);
                    }
                }
            } elseif ($offer_start_booking_type == 'relative_date') {
                $days = (!empty($offer->em_offer_start_booking_days) ? $offer->em_offer_start_booking_days : 1 );
                $days_option = (!empty($offer->em_offer_start_booking_days_option) ? $offer->em_offer_start_booking_days_option : 'before' );
                $event_option = (!empty($offer->em_offer_start_booking_event_option) ? $offer->em_offer_start_booking_event_option : 'event_start' );
                $days_string = ' days';
                if ($days == 1) {
                    $days_string = ' day';
                }
                // + or - days
                $days_icon = '- ';
                if ($days_option == 'after') {
                    $days_icon = '+ ';
                }
                if ($event_option == 'event_start') {
                    $book_end_timestamp = $event_start_date;
                    if (!empty($event_start_time)) {
                        $book_end_timestamp = $this->ep_timestamp_to_date($event_start_date);
                        $book_end_timestamp .= ' ' . $event_start_time;
                        $book_end_timestamp = $this->ep_datetime_to_timestamp($book_end_timestamp);
                    }
                    $min_date = strtotime($days_icon . $days . $days_string, $book_end_timestamp);
                } elseif ($event_option == 'event_ends') {
                    $book_end_timestamp = $event_end_date;
                    if (!empty($event_end_time)) {
                        $book_end_timestamp = $this->ep_timestamp_to_date($event_end_date);
                        $book_end_timestamp .= ' ' . $event_end_time;
                        $book_end_timestamp = $this->ep_datetime_to_timestamp($book_end_timestamp);
                    }
                    $min_date = strtotime($days_icon . $days . $days_string, $book_end_timestamp);
                } else {
                    if (!empty($event_option)) {
                        $em_event_add_more_dates = $event_add_more_dates;
                        if (!empty($em_event_add_more_dates) && count($em_event_add_more_dates) > 0) {
                            foreach ($em_event_add_more_dates as $more_dates) {
                                if ($more_dates['uid'] == $event_option) {
                                    $min_date = $more_dates['date'];
                                    if (!empty($more_dates['time'])) {
                                        $date_more = $this->ep_timestamp_to_date($more_dates['date']);
                                        $date_more .= ' ' . $more_dates['time'];
                                        $min_date = $this->ep_datetime_to_timestamp($date_more);
                                    }
                                    break;
                                }
                            }
                        }
                        $min_date = strtotime($days_icon . $days . $days_string, $min_date);
                    }
                }
            } else {
                $em_offer_start_booking_event_option = $offer->em_offer_start_booking_event_option;
                if ($em_offer_start_booking_event_option == 'event_start') {
                    $book_end_timestamp = $event_start_date;
                    if (!empty($event_start_time)) {
                        $book_end_timestamp = $this->ep_timestamp_to_date($event_start_date);
                        $book_end_timestamp .= ' ' . $event_start_time;
                        $book_end_timestamp = $this->ep_datetime_to_timestamp($book_end_timestamp);
                    }
                    $min_date = $book_end_timestamp;
                } elseif ($em_offer_start_booking_event_option == 'event_ends') {
                    $book_end_timestamp = $event_end_date;
                    if (!empty($event_end_time)) {
                        $book_end_timestamp = $this->ep_timestamp_to_date($event_end_date);
                        $book_end_timestamp .= ' ' . $event_end_time;
                        $book_end_timestamp = $this->ep_datetime_to_timestamp($book_end_timestamp);
                    }
                    $min_date = $book_end_timestamp;
                } else {
                    if (!empty($event_option)) {
                        $em_event_add_more_dates = $event_add_more_dates;
                        if (!empty($em_event_add_more_dates) && count($em_event_add_more_dates) > 0) {
                            foreach ($em_event_add_more_dates as $more_dates) {
                                if ($more_dates['uid'] == $event_option) {
                                    $min_date = $more_dates['date'];
                                    if (!empty($more_dates['time'])) {
                                        $date_more = $this->ep_timestamp_to_date($more_dates['date']);
                                        $date_more .= ' ' . $more_dates['time'];
                                        $min_date = $this->ep_datetime_to_timestamp($date_more);
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            // offer end date
            $offer_ends_booking_type = $offer->em_offer_ends_booking_type;
            if ($offer_ends_booking_type == 'custom_date') {
                if (isset($offer->em_offer_ends_booking_date) && !empty($offer->em_offer_ends_booking_date)) {
                    $offer_end_date = $offer->em_offer_ends_booking_date;
                    if (isset($offer->em_offer_ends_booking_time) && !empty($offer->em_offer_ends_booking_time)) {
                        $offer_end_date .= ' ' . $offer->em_offer_ends_booking_time;
                        $max_date = $this->ep_datetime_to_timestamp($offer_end_date);
                    } else {
                        $max_date = $this->ep_date_to_timestamp($offer_end_date);
                    }
                }
            } elseif ($offer_ends_booking_type == 'relative_date') {
                $days = (!empty($offer->em_offer_ends_booking_days) ? $offer->em_offer_ends_booking_days : 1 );
                $days_option = (!empty($offer->em_offer_ends_booking_days_option) ? $offer->em_offer_ends_booking_days_option : 'before' );
                $event_option = (!empty($offer->em_offer_ends_booking_event_option) ? $offer->em_offer_ends_booking_event_option : 'event_ends' );
                $days_string = ' days';
                if ($days == 1) {
                    $days_string = ' day';
                }
                // + or - days
                $days_icon = '- ';
                if ($days_option == 'after') {
                    $days_icon = '+ ';
                }
                if ($event_option == 'event_start') {
                    $book_end_timestamp = $event_start_date;
                    if (!empty($event_start_time)) {
                        $book_end_timestamp = $this->ep_timestamp_to_date($event_start_date);
                        $book_end_timestamp .= ' ' . $event_start_time;
                        $book_end_timestamp = $this->ep_datetime_to_timestamp($book_end_timestamp);
                    }
                    $max_date = strtotime($days_icon . $days . $days_string, $book_end_timestamp);
                } elseif ($event_option == 'event_ends') {
                    $book_end_timestamp = $event_end_date;
                    if (!empty($event_end_time)) {
                        $book_end_timestamp = $this->ep_timestamp_to_date($event_end_date);
                        $book_end_timestamp .= ' ' . $event_end_time;
                        $book_end_timestamp = $this->ep_datetime_to_timestamp($book_end_timestamp);
                    }
                    $max_date = strtotime($days_icon . $days . $days_string, $book_end_timestamp);
                } else {
                    if (!empty($event_option)) {
                        $em_event_add_more_dates = $event_add_more_dates;
                        if (!empty($em_event_add_more_dates) && count($em_event_add_more_dates) > 0) {
                            foreach ($em_event_add_more_dates as $more_dates) {
                                if ($more_dates['uid'] == $event_option) {
                                    $max_date = $more_dates['date'];
                                    if (!empty($more_dates['time'])) {
                                        $date_more = $this->ep_timestamp_to_date($more_dates['date']);
                                        $date_more .= ' ' . $more_dates['time'];
                                        $max_date = $this->ep_datetime_to_timestamp($date_more);
                                    }
                                    break;
                                }
                            }
                        }
                        $max_date = strtotime($days_icon . $days . $days_string, $max_date);
                    }
                }
            } else {
                $em_offer_ends_booking_event_option = $offer->em_offer_ends_booking_event_option;
                if ($em_offer_ends_booking_event_option == 'event_start') {
                    $book_end_timestamp = $event_start_date;
                    if (!empty($event_start_time)) {
                        $book_end_timestamp = $this->ep_timestamp_to_date($event_start_date);
                        $book_end_timestamp .= ' ' . $event_start_time;
                        $book_end_timestamp = $this->ep_datetime_to_timestamp($book_end_timestamp);
                    }
                    $max_date = $book_end_timestamp;
                } elseif ($em_offer_ends_booking_event_option == 'event_ends') {
                    $book_end_timestamp = $event_end_date;
                    if (!empty($event_end_time)) {
                        $book_end_timestamp = $this->ep_timestamp_to_date($event_end_date);
                        $book_end_timestamp .= ' ' . $event_end_time;
                        $book_end_timestamp = $this->ep_datetime_to_timestamp($book_end_timestamp);
                    }
                    $max_date = $book_end_timestamp;
                } else {
                    if (!empty($event_option)) {
                        $em_event_add_more_dates = $event_add_more_dates;
                        if (!empty($em_event_add_more_dates) && count($em_event_add_more_dates) > 0) {
                            foreach ($em_event_add_more_dates as $more_dates) {
                                if ($more_dates['uid'] == $event_option) {
                                    $max_date = $more_dates['date'];
                                    if (!empty($more_dates['time'])) {
                                        $date_more = $this->ep_timestamp_to_date($more_dates['date']);
                                        $date_more .= ' ' . $more_dates['time'];
                                        $max_date = $this->ep_datetime_to_timestamp($date_more);
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            // check for offer date time condition
            if ($current_time >= $min_date && $current_time <= $max_date) {
                // now check for offer types
                if (!empty($offer->em_ticket_offer_type)) {
                    $em_ticket_offer_type = $offer->em_ticket_offer_type;
                    if ($em_ticket_offer_type == 'seat_based') {
                        if (!empty($offer->em_ticket_offer_seat_option)) {
                            $seat_option = $offer->em_ticket_offer_seat_option;
                            if (!empty($offer->em_ticket_offer_seat_number)) {
                                $seat_number = $offer->em_ticket_offer_seat_number;
                                //$event_ticket_booking_count = $this->get_event_booking_by_ticket_id($event_id, $ticket->id);
                                if(!empty($event) && isset($event->all_bookings))
                                {
                                    $all_bookings = $event->all_bookings;
                                }
                                else
                                {
                                    $all_bookings = $dbhandler->eventprime_get_all_posts('em_booking', 'posts', array( 'pending', 'completed' ), 'ID', 0, 'ASC', -1, 'em_event', $event_id);
                                }
                                $attendees = $this->get_total_booking_number_by_event_id( $event_id, $all_bookings );
                                $event_ticket_booking_count =(isset($all_bookings) && !empty($all_bookings))?count($all_bookings):0;
                                if ($seat_option == 'first') {
                                    if ($event_ticket_booking_count < $seat_number) {
                                        $offer->em_remaining_ticket_to_offer = $seat_number - $event_ticket_booking_count;
                                        return $offer;
                                    }
                                } else {
                                    $ticket_caps = $ticket->capacity;
                                    $unbooked_tickets = $ticket_caps - $attendees;
                                    if (!empty($unbooked_tickets) && $unbooked_tickets <= $seat_number) {
                                        $offer->em_remaining_ticket_to_offer = $unbooked_tickets;
                                        return $offer;
                                    }
                                }
                            }
                        }
                    } else if ($em_ticket_offer_type == 'role_based') {
                        if (isset($offer->em_ticket_offer_user_roles) && !empty($offer->em_ticket_offer_user_roles)) {
                            $em_ticket_offer_user_roles = $offer->em_ticket_offer_user_roles;
                            $user = wp_get_current_user();
                            $roles = (array) $user->roles;
                            if (!empty($em_ticket_offer_user_roles)) {
                                $found_role = 0;
                                foreach ($em_ticket_offer_user_roles as $ur) {
                                    if (in_array($ur, $roles)) {
                                        $found_role = 1;
                                        break;
                                    }
                                }
                                if (!empty($found_role)) {
                                    $applied = 1;
                                    return $applied;
                                }
                            }
                        }
                    } else if ($em_ticket_offer_type == 'volume_based' && $qty != 0) {
                        if (!empty($offer->em_ticket_offer_volumn_count)) {
                            $volume = $offer->em_ticket_offer_volumn_count;
                            if ($qty >= $volume) {
                                $applied = 1;
                                return $applied;
                            }
                        }
                    }
                    $applied = apply_filters( "ep_check_event_ticket_applied_offers", $applied, $offer, $ticket, $event_id, $qty ); 
                }
            }
        }
        return $applied;
    }

    public function get_event_bookings_by_event_id($event_id) {
        $bookings = array();
        if (!empty($event_id)) {
            $args = array(
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
                'post_status' => 'completed',
                'meta_query' => array(
                    array(
                        'key' => 'em_event',
                        'value' => $event_id,
                        'compare' => '=',
                        'type' => 'NUMERIC,'
                    ),
                ),
                'post_type' => 'em_booking'
            );
            $bookings = get_posts($args);
        }
        return $bookings;
    }

    public function get_event_booking_by_ticket_id($event_id, $ticket_id) {
        $ticket_booking_count = 0;
        $booking_controller = new EventPrime_Bookings;
        $all_bookings = $booking_controller->get_event_bookings_by_event_id($event_id);
        if (!empty($all_bookings)) {
            foreach ($all_bookings as $booking) {
                $order_info = get_post_meta($booking->ID, 'em_order_info', true);
                if (!empty($order_info)) {
                    $tickets = $order_info['tickets'];
                    if (!empty($tickets)) {
                        foreach ($tickets as $ticket) {
                            if ($ticket->id == $ticket_id) {
                                $ticket_booking_count += $ticket->qty;
                            }
                        }
                    }
                }
            }
        }
        return $ticket_booking_count;
    }

    /**
     * Get event available offers
     * 
     * @param object $event Event Data
     * 
     * @return int available offers
     */
    public function get_event_available_offers($event) {
        $available_offers = 0;
        if (!empty($event) && !empty($event->all_tickets_data)) {
            foreach ($event->all_tickets_data as $ticket) {
                if (!empty($ticket->offers)) {
                    $ticket_offers_data = json_decode($ticket->offers);
                    if (!empty($ticket_offers_data)) {
                        foreach ($ticket_offers_data as $to) {
                            if (isset($to->em_ticket_show_offer_detail) && !empty($to->em_ticket_show_offer_detail)) {
                                $available_offers++;
                            }
                        }
                    }
                }
            }
        }
        return $available_offers;
    }

    public function get_event_qr_code($event) {
        $image_url = '';
        if (!empty($event) && isset($event->event_url) && !empty($event->event_url)) {
            $url = $event->event_url;
            $file_name = 'ep_qr_' . md5($url) . '.png';
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['basedir'] . '/ep/' . $file_name;
            if (!file_exists($file_path)) {
                if (!file_exists(dirname($file_path))) {
                    mkdir(dirname($file_path), 0755);
                }
                require_once 'lib/qrcode.php';
                $qrCode = new QRcode();
                $qrCode->png($url, $file_path, 'M', 4, 2);
            }
            $image_url = esc_url($upload_dir['baseurl'] . '/ep/' . $file_name);
        }
        return $image_url;
    }

    public function check_event_in_user_wishlist($event_id) {
        if (!empty($event_id)) {
            $user_id = get_current_user_id();
            if (!empty($user_id)) {
                $wishlist_meta = get_user_meta($user_id, 'ep_wishlist_event', true);
                if (!empty($wishlist_meta) && isset($wishlist_meta[$event_id])) {
                    return true;
                }
            }
        }
        return false;
    }

    public function get_single_event($post_id, $post = null, $restrictions = array()) {
        if (empty($post_id))
            return;
        $event = new stdClass();
        $meta = get_post_meta($post_id);
        foreach ($meta as $key => $val) {
            $event->{$key} = maybe_unserialize($val[0]);
        }
        if (empty($post)) {
            $post = get_post($post_id);
        }
        if ($post) {
            $event->id = $post->ID;
            $event->name = $post->post_title;
            $event->slug = $post->post_name;
            $event->description = wp_kses_post($post->post_content);
            $event->post_status = $post->post_status;
            $event->post_parent = $post->post_parent;
            $event->fstart_date = (!empty($event->em_start_date) ) ? $this->ep_timestamp_to_date($event->em_start_date, 'd M', 1) : '';
            $event->fend_date = (!empty($event->em_end_date) ) ? $this->ep_timestamp_to_date($event->em_end_date, 'd M', 1) : '';
            if (!empty($event->em_start_date) && !empty($this->ep_get_global_settings('enable_event_time_to_user_timezone'))) {
                $event->fstart_date = $this->ep_convert_event_date_time_from_timezone($event, 'd M', 0, 1);
                $event->fend_date = $this->ep_convert_event_date_time_from_timezone($event, 'd M', 1, 1);
            }
            
            $event->em_start_date_formated = (!empty($event->em_start_date) ) ? $this->ep_timestamp_to_date( $event->em_start_date, $this->ep_get_datepicker_format(), 1 ) : '';
            $event->em_end_date_formated = (!empty($event->em_end_date) ) ? $this->ep_timestamp_to_date( $event->em_end_date, $this->ep_get_datepicker_format(), 1 ) : '';
            $event->em_start_time_formated = (!empty($event->em_start_time) ) ? $this->ep_convert_time_with_format( $event->em_start_time ) : '';
            $event->em_end_time_formated = (!empty($event->em_end_time) ) ? $this->ep_convert_time_with_format( $event->em_end_time ) : '';
                    
                    
            $event->start_end_diff = $this->ep_get_event_date_time_diff($event);
            $event->event_url = $this->ep_get_custom_page_url('events_page', $event->id, 'event');
            $event->ticket_categories = $this->get_event_ticket_category($event->id);
            $event->solo_tickets = $this->get_event_solo_ticket($event->id);
            $event->ticket_price_range = $this->get_ticket_price_range($event->ticket_categories, $event->solo_tickets);
            $event->all_tickets_data = $this->get_event_all_tickets($event);
            // $event->venue_details = (!empty($event->em_venue) ) ? $this->get_single_venue($event->em_venue) : array();
            $event->venue_details = (!empty($event->em_venue) ) ? $this->get_single_venue($event->em_venue) : array();
            $event->event_type_details = (!empty($event->em_event_type) ) ? $this->get_single_event_type($event->em_event_type) : array();
            //print_r($event->em_organizer);die;
            $event->organizer_details = (!empty($event->em_organizer) ) ? $this->ep_get_event_organizer($event->em_organizer) : array();
            $event->performer_details = (!empty($event->em_performer) ) ? $this->get_ep_event_performer($event->em_performer) : array();
            $event->image_url = $this->get_event_image_url($event->id);
            $event->placeholder_image_url = plugin_dir_url(__FILE__) . '../admin/partials/images/dummy_image.png';
            $other_events = $this->ep_get_child_events($post->ID);
            $event->child_events = array();
            if (!empty($other_events) && count($other_events) > 0) {
                $other_event_data = $this->load_event_full_data($other_events);
                $event->child_events = $other_event_data;
            }
            $event->all_offers_data = $this->get_event_all_offers($event);
            $event->qr_code = $this->get_event_qr_code($event);
            $event->em_event_checkout_attendee_fields = $this->get_event_checkout_fields($event);
            $event->em_event_checkout_booking_fields = $this->get_event_checkout_booking_fields($event);
            $event->event_in_user_wishlist = $this->check_event_in_user_wishlist($event->id);
        }
        return $event;
    }
    
    public function get_single_event_detail($post_id, $post = null, $restrictions = array()) {
        if (empty($post_id))
            return;
        $event = new stdClass();
        $meta = get_post_meta($post_id);
        foreach ($meta as $key => $val) {
            $event->{$key} = maybe_unserialize($val[0]);
        }
        if (empty($post)) {
            $post = get_post($post_id);
        }
        if ($post) {
            $event->id = $post->ID;
            $event->name = $post->post_title;
            $event->slug = $post->post_name;
            $event->description = wp_kses_post($post->post_content);
            $event->post_status = $post->post_status;
            $event->post_parent = $post->post_parent;
            $event->fstart_date = (!empty($event->em_start_date) ) ? $this->ep_timestamp_to_date($event->em_start_date, 'd M', 1) : '';
            $event->fend_date = (!empty($event->em_end_date) ) ? $this->ep_timestamp_to_date($event->em_end_date, 'd M', 1) : '';
            if (!empty($event->em_start_date) && !empty($this->ep_get_global_settings('enable_event_time_to_user_timezone'))) {
                $event->fstart_date = $this->ep_convert_event_date_time_from_timezone($event, 'd M', 0, 1);
                $event->fend_date = $this->ep_convert_event_date_time_from_timezone($event, 'd M', 1, 1);
            }
            $event->start_end_diff = $this->ep_get_event_date_time_diff($event);
            $event->event_url = $this->ep_get_custom_page_url('events_page', $event->id, 'event');
            $event->ticket_categories = $this->get_event_ticket_category($event->id);
            $event->solo_tickets = $this->get_event_solo_ticket($event->id);
            $event->ticket_price_range = $this->get_ticket_price_range($event->ticket_categories, $event->solo_tickets);
            $event->all_tickets_data = $this->get_event_all_tickets($event);
            //$event->venue_details = (!empty($event->em_venue) ) ? $this->get_single_venue($event->em_venue) : array();
            //$event->venue_details = (!empty($event->em_venue) ) ? $this->get_single_venue($event->em_venue[0]) : array();
            //print_r($event->em_event_type);die;
            $event->event_type_details = (!empty($event->em_event_type) ) ? $this->get_single_event_type($event->em_event_type) : array();
            //print_r($event->em_organizer);die;
            $event->organizer_details = (!empty($event->em_organizer) ) ? $this->ep_get_event_organizer($event->em_organizer) : array();
            $event->performer_details = (!empty($event->em_performer) ) ? $this->get_ep_event_performer($event->em_performer) : array();
            $event->image_url = $this->get_event_image_url($event->id);
            $event->placeholder_image_url = plugin_dir_url(__FILE__) . '../admin/partials/images/dummy_image.png';
            //$other_events = $this->ep_get_child_events($post->ID);
            $event->child_events = array();
//            if (!empty($other_events) && count($other_events) > 0) {
//                $other_event_data = $this->load_event_full_data($other_events);
//                $event->child_events = $other_event_data;
//            }
            $event->all_offers_data = $this->get_event_all_offers($event);
            $event->qr_code = $this->get_event_qr_code($event);
            $event->em_event_checkout_attendee_fields = $this->get_event_checkout_fields($event);
            $event->em_event_checkout_booking_fields = $this->get_event_checkout_booking_fields($event);
            $event->event_in_user_wishlist = $this->check_event_in_user_wishlist($event->id);
            $event->all_bookings = $this->get_event_bookings_by_event_id( $event->id, true );
        }
        return $event;
    }
    
    
    
    public function get_upcoming_single_event($post_id, $post = null, $type=array())
    {
         if (empty($post_id))
            return;
        $event = new stdClass();
        $meta = get_post_meta($post_id);
        foreach ($meta as $key => $val) {
            $event->{$key} = maybe_unserialize($val[0]);
        }
        if (empty($post)) {
            $post = get_post($post_id);
        }
        if ($post) {
            $event->id = $post->ID;
            $event->name = $post->post_title;
            $event->slug = $post->post_name;
            $event->description = wp_kses_post($post->post_content);
            $event->post_status = $post->post_status;
            $event->post_parent = $post->post_parent;
            $event->fstart_date = (!empty($event->em_start_date) ) ? $this->ep_timestamp_to_date($event->em_start_date, 'd M', 1) : '';
            $event->fend_date = (!empty($event->em_end_date) ) ? $this->ep_timestamp_to_date($event->em_end_date, 'd M', 1) : '';
            if (!empty($event->em_start_date) && !empty($this->ep_get_global_settings('enable_event_time_to_user_timezone'))) {
                $event->fstart_date = $this->ep_convert_event_date_time_from_timezone($event, 'd M', 0, 1);
                $event->fend_date = $this->ep_convert_event_date_time_from_timezone($event, 'd M', 1, 1);
            }
            $event->start_end_diff = $this->ep_get_event_date_time_diff($event);
            $event->event_url = $this->ep_get_custom_page_url('events_page', $event->id, 'event');
            $event->ticket_categories = $this->get_event_ticket_category($event->id);
            $event->solo_tickets = $this->get_event_solo_ticket($event->id);
            $event->ticket_price_range = $this->get_ticket_price_range($event->ticket_categories, $event->solo_tickets);
            $event->all_tickets_data = $this->get_event_all_tickets($event);
            // $event->venue_details = (!empty($event->em_venue) ) ? $this->get_single_venue($event->em_venue) : array();
            if(!empty($type) && in_array('em_venue',$type))
            {
                $event->venue_details = (!empty($event->em_venue) ) ? $this->ep_get_term($event->em_venue) : array();
            }
            if(!empty($type) && in_array('em_event_type',$type))
            {
                $event->event_type_details = (!empty($event->em_event_type) ) ? $this->ep_get_term($event->em_event_type) : array();
            }
            //print_r($event->em_organizer);die;
            if(!empty($type) && in_array('em_organizer',$type))
            {
                $event->organizer_details = (!empty($event->em_organizer) ) ? $this->ep_get_term($event->em_organizer) : array();
            }
            if(!empty($type) && in_array('em_performer',$type))
            {
                $event->performer_details = (!empty($event->em_performer) ) ? $this->get_ep_event_performer($event->em_performer) : array();
            }
            $event->image_url = $this->get_event_image_url($event->id);
            $event->placeholder_image_url = plugin_dir_url(__FILE__) . '../admin/partials/images/dummy_image.png';
            
            $event->all_offers_data = $this->get_event_all_offers($event);
            $event->qr_code = $this->get_event_qr_code($event);
            $event->em_event_checkout_attendee_fields = $this->get_event_checkout_fields($event);
            $event->em_event_checkout_booking_fields = $this->get_event_checkout_booking_fields($event);
            $event->event_in_user_wishlist = $this->check_event_in_user_wishlist($event->id);
        }
        return $event;
    }
    
    public function ep_get_event_organizer($ids)
    {
        $organizers = array();
        if(!empty($ids))
        {
            foreach($ids as $id)
            {
                $organizer = $this->get_single_organizer( $id );
                if( ! empty( $organizer ) ) {
                    $organizers[] = $organizer;
                }
            }
        }
        
        return $organizers;
    }
    public function ep_get_checkout_fields_data() {
        $dbhandler = new EP_DBhandler();
        $get_field_data = $dbhandler->get_all_result('CHECKOUT_FIELDS','*',1,'results',0, false,'id',true,'','OBJECT_K');
        return $get_field_data;
    }
    
    public function get_all_checkout_fields() 
    {
	return $this->ep_get_checkout_fields_data();
    }

	/**
	 * Get checkout field data by id
	 * 
	 * @param int $id Checkout Field Id.
	 * 
	 * @return object
	 */
    public function get_checkout_field_by_id( $id ) {
        $dbhandler = new EP_DBhandler();
        $get_field_data = $dbhandler->get_row('CHECKOUT_FIELDS', $id, 'id');
        return $get_field_data;
    }

    public function get_event_checkout_booking_fields($event) {
        global $wpdb;
        $booking_fields = array();
        if (!empty($event->em_event_checkout_booking_fields)) {
            $checkout_table_name = $wpdb->prefix . 'eventprime_checkout_fields';
            $booking_fields = $event->em_event_checkout_booking_fields;
            if (!empty($booking_fields) && !empty($booking_fields['em_event_booking_fields_data']) && count($booking_fields['em_event_booking_fields_data']) > 0) {
                $booking_fields_data = array();
                foreach ($booking_fields['em_event_booking_fields_data'] as $fields) {
                    $get_field_data = $wpdb->get_row($wpdb->prepare("SELECT `id`, `type`, `label` FROM $checkout_table_name WHERE `id` = %d", $fields));
                    if (!empty($get_field_data)) {
                        $booking_fields_data[] = $get_field_data;
                    }
                }
                $booking_fields['em_event_booking_fields_data'] = $booking_fields_data;
            }
        }
        return $booking_fields;
    }

    /**
     * Get event booking by ticket id
     * 
     * @param $event_id Event ID.
     * 
     * @param $ticket_id Ticket ID.
     * 
     * @return int
     */
    public function get_event_checkout_fields($event) {
        global $wpdb;
        $attendee_fields = array();
        if (!empty($event->em_event_checkout_attendee_fields)) {
            $checkout_table_name = $wpdb->prefix . 'eventprime_checkout_fields';
            $attendee_fields = $event->em_event_checkout_attendee_fields;
            if (!empty($attendee_fields) && !empty($attendee_fields['em_event_checkout_fields_data']) && count($attendee_fields['em_event_checkout_fields_data']) > 0) {
                $attendee_fields_data = array();
                foreach ($attendee_fields['em_event_checkout_fields_data'] as $fields) {
                    $get_field_data = $wpdb->get_row($wpdb->prepare("SELECT `id`, `type`, `label` FROM $checkout_table_name WHERE `id` = %d", $fields));
                    if (!empty($get_field_data)) {
                        $attendee_fields_data[] = $get_field_data;
                    }
                }
                $attendee_fields['em_event_checkout_fields_data'] = $attendee_fields_data;
            }
        }
        return $attendee_fields;
    }

    public function load_booking_detail($order_id, $with_event = true, $post = array()) {
        if (empty($order_id))
            return;

        if (empty($post)) {
            $post = get_post($order_id);
            if (empty($post))
                return;
        }


        $booking = new stdClass();

        $meta = get_post_meta($order_id);
        foreach ($meta as $key => $val) {
            $booking->{$key} = maybe_unserialize($val[0]);
        }

        $detail_url = get_permalink($this->ep_get_global_settings('booking_details_page'));
        $booking->booking_detail_url = add_query_arg(array('order_id' => $order_id), $detail_url);
        $booking->post_data = $post;
        $booking->event_data = array();
        if (!empty($with_event)) {
            // load event data
            $booking->event_data = $this->get_single_event($booking->em_event);
        }

        return $booking;
    }

    public function ep_get_core_checkout_fields() {
        $field_types = array("text" => "Text", "email" => "Email", "tel" => "Tel", "date" => "Date", "number" => "Number");
        return $field_types;
    }

    public function ep_checkout_field_types() {
        $field_types = $this->ep_get_core_checkout_fields();
        return apply_filters('ep_checkout_fields_options', $field_types);
    }

    public function get_label_section_lists() {
        $labelsections = array('Event-Type', 'Event-Types', 'Venue', 'Venues', 'Performer', 'Performers', 'Organizer', 'Organizers', 'Add To Wishlist', 'Remove From Wishlist', 'Ticket', 'Tickets Left', 'Organized by');
        return apply_filters('ep_settings_language_labels', $labelsections);
    }

    public function get_button_section_lists() {
        $buttonsections = array('Buy Tickets', 'Booking closed', 'Booking start on', 'Free', 'View Details', 'Get Tickets Now', 'Checkout', 'Register', 'Add Details & Checkout', 'Submit Payment', 'Sold Out');
        return apply_filters('ep_settings_language_buttons', $buttonsections);
    }

    public function get_event_views() {
        $event_views = array(
            'square_grid' => 'Square Grid',
            'staggered_grid' => 'Staggered Grid',
            'rows' => 'Stacked Rows',
            'slider' => 'Slider',
            'month' => 'Calendar / Month',
            'week' => 'Calendar / Week - Regular',
            'listweek' => 'Calendar / Week - Agenda',
            'day' => 'Calendar Day',
        );

        return apply_filters('ep_event_views', $event_views);
    }

    public function ep_frontend_views_list_styles() {
        $listing_page_view_options = array("grid" => "Square Grid", "colored_grid" => "Colored Square Grid", "rows" => "Stacked Rows");
        return $listing_page_view_options;
    }

    public function ep_frontend_views_event_styles() {
        $upcoming_event_view_options = array("grid" => "Square Grid", "rows" => "Stacked Rows", "plain_list" => "Plain List");
        return $upcoming_event_view_options;
    }

    public function get_image_visibility_options() {
        $image_visibility_views = array(
            'none' => 'None',
            'fill' => 'Fill',
            'contain' => 'Contain',
            'cover' => 'Cover'
        );
        return $image_visibility_views;
    }

    public function ep_get_rm_forms() {
        $rm_forms = array();
        // Registration Magic Integration
        if (defined("REGMAGIC_BASIC") || defined("REGMAGIC_GOLD")) {
            $where = array("form_type" => 1);
            $data_specifier = array('%d');
            $forms = RM_DBManager::get('FORMS', $where, $data_specifier, 'results', 0, 99999, '*', $sort_by = 'created_on', $descending = true);
            //$form_dropdown_array[0] = esc_html__('Default EventPrime Form','eventprime-event-calendar-management');
            if ($forms) {
                foreach ($forms as $form) {
                    $rm_forms[$form->form_id] = $form->form_name;
                }
            }
        }
        return $rm_forms;
    }

    // list all extension
    public function ep_list_all_exts() {
        $exts = array('Live Seating', 'Events Import Export', 'Stripe Payments', 'Offline Payments', 'WooCommerce Integration', 'Event Sponsors', 'Attendees List', 'EventPrime Invoices', 'Coupon Codes', 'Guest Bookings', 'EventPrime Zoom Integration', 'Event List Widgets', 'Admin Attendee Bookings', 'EventPrime MailPoet', 'Twilio Text Notifications', 'Event Tickets', 'Zapier Integration', 'Advanced Reports', 'Advanced Checkout Fields', 'Elementor Integration', 'Mailchimp Integration', 'User Feedback', 'RSVP', 'WooCommerce Checkout', 'Ratings and Reviews','Attendee Event Check In','Waiting List','HoneyPot Security','Turnstile Antispam Security','Event Reminder Emails','Demo Data','Square Payments','hCaptcha Security');
        return $exts;
    }

    // get premium extension list
    public function ep_load_premium_extension_list() {
        $premium_ext_list = array('Live Seating', 'Stripe Payments', 'Offline Payments', 'Event Sponsors', 'Attendees List', 'EventPrime Invoices', 'Coupon Codes', 'Guest Bookings', 'EventPrime Zoom Integration', 'Event List Widgets', 'Admin Attendee Bookings', 'EventPrime MailPoet', 'Twilio Text Notifications', 'Event Tickets', 'Advanced Reports', 'Advanced Checkout Fields', 'Mailchimp Integration', 'User Feedback', 'RSVP', 'WooCommerce Checkout', 'Ratings and Reviews','Attendee Event Check In','Waiting List','HoneyPot Security','Turnstile Antispam Security','Event Reminder Emails','Square Payments','hCaptcha Security');
        return $premium_ext_list;
    }

    // load extensions data
    function em_get_more_extension_data($plugin_name) {
        $data['is_activate'] = $data['is_installed'] = $data['url'] = '';
        $data['button'] = 'Download';
        $data['class_name'] = 'ep-install-now-btn';
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $installed_plugins = get_plugins();
        $installed_plugin_file = $installed_plugin_url = array();
        if (!empty($installed_plugins)) {
            foreach ($installed_plugins as $key => $value) {
                $exp = explode('/', $key);
                $installed_plugin_file[] = end($exp);
                $installed_plugin_url[] = $key;
            }
        }
        switch ($plugin_name) {
            case 'Live Seating':
                $data['url'] = 'https://theeventprime.com/all-extensions/live-seating/';
                if (in_array('eventprime-live-seating.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-live-seating.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists('Eventprime_Live_Seating');
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=live-seating-settings');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Live Seating');
                $data['image'] = 'live_seating_icon.png';
                $data['desc'] = "Add live seat selection on your events and provide seat based tickets to your event attendees. Set a seating arrangement for all your Event Sites with specific rows, columns, and walking aisles using EventPrime's very own Event Site Seating Builder.";
                break;
            case 'Event Sponsors':
                $data['url'] = 'https://theeventprime.com/all-extensions/event-sponsors/';
                if (in_array('event-sponsor.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('event-sponsor.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Event_Sponsor");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=frontviews&sub_tab=sponsors');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Event Sponsors');
                $data['image'] = 'event_sponsors_icon.png';
                $data['desc'] = "Add Sponsor(s) to your events. Upload Sponsor logos and they will appear on the event page alongside all other details of the event.";
                break;
            case 'Stripe Payments':
                $data['url'] = 'https://theeventprime.com/all-extensions/stripe-payments/';
                if (in_array('eventprime-stripe.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-stripe.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Event_Stripe");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=payments&section=stripe');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Stripe Payments');
                $data['image'] = 'stripe_payments_icon.png';
                $data['desc'] = "Start accepting Event Booking payments using the Stripe Payment Gateway. By integrating Stripe with EventPrime, event attendees can now pay with their credit cards while you receive the payment in your Stripe account.";
                break;
            case 'Offline Payments':
                $data['url'] = 'https://theeventprime.com/all-extensions/offline-payments/';
                if (in_array('eventprime-offline.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-offline.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Offline");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=payments&section=offline');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Offline Payments');
                $data['image'] = 'offline_payments_icon.png';
                $data['desc'] = "Don't want to use any online payment gateway to collect your event booking payments? Don't worry. With the Offline Payments extension, you can accept event bookings online while you collect booking payments from attendees offline.";
                break;
            case 'Attendees List':
                $data['url'] = 'https://theeventprime.com/all-extensions/attendees-list/';
                if (in_array('eventprime-attendees-list.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-attendees-list.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Attendees_List");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=attendees-list-settings');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Attendees List');
                $data['image'] = 'attendee_list_icon.png';
                $data['desc'] = "Display names of your Event Attendees on the Event page. Or within the new Attendees List widget.";
                break;
            case 'Coupon Codes':
                $data['url'] = 'https://theeventprime.com/all-extensions/coupon-codes/';
                if (in_array('event-coupons.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('event-coupons.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Event_Coupons");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?edit.php?post_type=em_coupon');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Coupon Codes');
                $data['image'] = 'coupon_code_icon.png';
                $data['desc'] = "Create and activate coupon codes for allowing Attendees for book for events at a discount. Set discount type and limits on coupon code usage, or deactivate at will.";
                break;
            case 'Guest Bookings':
                $data['url'] = 'https://theeventprime.com/all-extensions/guest-bookings/';
                if (in_array('eventprime-guest-booking.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-guest-booking.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Guest_Booking");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=forms&section=guest_booking');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Guest Bookings');
                $data['image'] = 'guest_bookings_icon.png';
                $data['desc'] = "Allow attendees to complete their event bookings without registering or logging in.";
                break;
            case 'Event List Widgets':
                $data['url'] = 'https://theeventprime.com/all-extensions/event-list-widgets/';
                if (in_array('eventprime-list-widgets.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-list-widgets.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_List_Widgets");
                if ($data['is_activate']) {
                    $data['button'] = '';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('admin.php?page=em_global_settings');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Event List Widgets');
                $data['image'] = 'event_list_widgets_icon.png';
                $data['desc'] = "Add 3 new Event Listing widgets to your website. These are the Popular Events list, Featured Events list, and Related Events list widgets.";
                break;
            case 'Admin Attendee Bookings':
                $data['url'] = 'https://theeventprime.com/all-extensions/admin-attendee-bookings/';
                if (in_array('eventprime-admin-attendee-booking.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-admin-attendee-booking.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Admin_Attendee_Booking");
                if ($data['is_activate']) {
                    $data['button'] = '';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('admin.php?page=em_bookings');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Admin Attendee Bookings');
                $data['image'] = 'admin_attendee_booking_icon.png';
                $data['desc'] = "Admins can now create custom attendee bookings from the backend EventPrime dashboard.";
                break;
            case 'Events Import Export':
                $data['url'] = 'https://theeventprime.com/all-extensions/events-import-export/';
                if (in_array('events-import-export.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('events-import-export.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Event_Import_Export");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-import-export');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Events Import Export');
                $data['image'] = 'event_import_export_icon.png';
                $data['desc'] = "Import or export events in popular file formats like CSV, ICS, XML and JSON.";
                break;
            case 'EventPrime MailPoet':
                $data['url'] = 'https://theeventprime.com/all-extensions/mailpoet-integration/';
                if (in_array('eventprime-mailpoet.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-mailpoet.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Event_Mailpoet");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('admin.php?page=em_mailpoet');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('EventPrime MailPoet');
                $data['image'] = 'mailpoet_icon.png';
                $data['desc'] = "Connect and engage with your users by subscribing event attendees to MailPoet lists. Users can opt-in multiple newsletters during checkout and can also manage subscriptions in user account area.";
                break;
            case 'WooCommerce Integration':
                $data['url'] = 'https://theeventprime.com/all-extensions/woocommerce-integration/';
                if (in_array('woocommerce-integration.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('woocommerce-integration.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Woocommerce_Integration");
                if ($data['is_activate']) {
                    $data['button'] = '';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('admin.php?page=em_global_settings');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('WooCommerce Integration');
                $data['image'] = 'woocommerce_integration_icon.png';
                $data['desc'] = "This extension allows you to add optional and/ or mandatory products to your events. You can define quantity or let users chose it themselves. Fully integrates with EventPrime checkout experience and WooCommerce order management.";
                break;
            case 'EventPrime Zoom Integration':
                $data['url'] = 'https://theeventprime.com/all-extensions/zoom-integration/';
                if (in_array('eventprime-zoom-meetings.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-zoom-meetings.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Zoom_Meetings");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=zoom-meetings');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('EventPrime Zoom Integration');
                $data['image'] = 'zoom_integration_icon.png';
                $data['desc'] = "This extension seamlessly creates virtual events to be conducted on Zoom through the EventPrime plugin. The extension provides easy linking of your website to that of Zoom. Commence and let the attendees join the event with a single click.";
                break;
            case 'Zapier Integration':
                $data['url'] = 'https://theeventprime.com/all-extensions/zapier-integration/';
                if (in_array('event-zapier.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('event-zapier.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Zapier_Integration");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=zapier-settings');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Zapier Integration');
                $data['image'] = 'zapier_integration_icon.png';
                $data['desc'] = "Extend the power of EventPrime using Zapier's powerful automation tools! Connect with over 3000 apps by building custom templates using EventPrime triggers.";
                break;
            case 'EventPrime Invoices':
                $data['url'] = 'https://theeventprime.com/all-extensions/invoices/';
                if (in_array('event-invoices.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('event-invoices.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Event_Invoices");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=invoice');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('EventPrime Invoices');
                $data['image'] = 'event_invoices_icon.png';
                $data['desc'] = "Allows fully customizable PDF invoices, complete with your company branding, to be generated and emailed with booking details to your users.";
                break;
            case 'Twilio Text Notifications':
                $data['url'] = 'https://theeventprime.com/all-extensions/twilio-text-notifications/';
                if (in_array('sms-integration.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('sms-integration.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Twilio_Text_Notification");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=sms-settings');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Twilio Text Notifications');
                $data['image'] = 'twilio_icon.png';
                $data['desc'] = "Keep your users engaged with text/ SMS notification system. Creating Twilio account is quick and easy. With this extension installed, you will be able to configure admin and user notifications separately, with personalized content.";
                break;
            case 'Event Tickets':
                $data['url'] = 'https://theeventprime.com/all-extensions/event-tickets/';
                if (in_array('event-tickets.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('event-tickets.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Event_Tickets");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_ticket');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Event Tickets');
                $data['image'] = 'event_tickets_icon.png';
                $data['desc'] = "An EventPrime extension that generate events tickets.";
                break;
            case 'Advanced Reports':
                $data['url'] = '';
                if (in_array('advanced-reports.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('advanced-reports.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Advanced_Reports");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-events-reports');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Advanced Reports');
                $data['image'] = 'advanced-reports.png';
                $data['desc'] = "Stay updated on all the Revenue and Bookings coming your way through EventPrime. The Advanced Reports extension empowers you with data and graphs that you need to know how much your events are connecting with their audience.";
                break;
            case 'Advanced Checkout Fields':
                $data['url'] = 'https://theeventprime.com/all-extensions/advanced-checkout-fields/';
                if (in_array('eventprime-advanced-checkout-fields.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-advanced-checkout-fields.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Advanced_Checkout_Fields");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=checkoutfields');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Advanced Checkout Fields');
                $data['image'] = 'advanced-chckout-fields.png';
                $data['desc'] = "Capture additional data by adding more field types to your checkout forms, like dropdown, checkbox and radio fields.";
                break;
            case 'Elementor Integration':
                $data['url'] = 'https://theeventprime.com/all-extensions/elementor-integration-extension/';
                if (in_array('eventprime-elementor-integration.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-elementor-integration.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Elementor_Integration");
                if ($data['is_activate']) {
                    $data['button'] = '';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('admin.php?page=em_global_settings');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Elementor Integration');
                $data['image'] = 'elementor-integration.png';
                $data['desc'] = "Effortlessly create stunning and interactive event pages, calendars, and listings using Elementor’s powerful drag-and-drop interface, without the need for any coding expertise.";
                break;
            case 'Mailchimp Integration':
                $data['url'] = 'https://theeventprime.com/all-extensions/mailchimp-integration/';
                if (in_array('eventprime-mailchimp-integration.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-mailchimp-integration.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Mailchimp_Integration");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=mailchimp-integration');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Mailchimp Integration');
                $data['image'] = 'mailchimp-integration.png';
                $data['desc'] = "Elevate engagement with MailChimp Extension. Seamlessly integrate, automate emails, and connect personally for targeted subscriber interaction.";
                break;
            case 'User Feedback':
                $data['url'] = 'https://theeventprime.com/all-extensions/user-feedback/';
                if (in_array('eventprime-user-feedback.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-user-feedback.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Event_Feedback");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=feedback');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('User Feedback');
                $data['image'] = 'user-feedback.png';
                $data['desc'] = "Elevate your event experience with EventPrime's Feedback Extension. It allows attendees to share their invaluable insights through multiple submissions.";
                break;
            case 'RSVP':
                $data['url'] = 'https://theeventprime.com/all-extensions/rsvp/';
                if (in_array('eventprime-rsvp.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-rsvp.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_RSVP");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=rsvp');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('RSVP');
                $data['image'] = 'rsvp.png';
                $data['desc'] = "Create invitational events, allowing you to send individual or bulk invites, receive and track RSVPs, manage guest lists and more!";
                break;
            case 'WooCommerce Checkout':
                $data['url'] = 'https://theeventprime.com/all-extensions/woocommerce-checkout/';
                if (in_array('woocommerce-checkout.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('woocommerce-checkout.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Woocommerce_Checkout_Integration");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=wc-checkout');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('WooCommerce Checkout');
                $data['image'] = 'woocommerce checkout.png';
                $data['desc'] = "Delegate your event booking checkout process to WooCommerce, and use any compatible WooCommerce payment gateway!";
                break;
            case 'Ratings and Reviews':
                $data['url'] = 'https://theeventprime.com/all-extensions/ratings-and-reviews/';
                if (in_array('event-reviews.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('event-reviews.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Ratings_And_Reviews");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=reviews');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Ratings and Reviews');
                $data['image'] = 'review-icon.png';
                $data['desc'] = "Allow users to post reviews and rate events using star ratings. Supports multiple options including review likes and dislikes, frontend scorecard, and a robust admin area configuration!";
                break;
           case 'Attendee Event Check In':
                $data['url'] = 'https://theeventprime.com/all-extensions/attendee-event-check-in/';
                if (in_array('eventprime-attendee-event-check-in.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-attendee-event-check-in.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Attendee_Event_Check_In");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=attendee-check-in-settings');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Attendee Event Check In');
                $data['image'] = 'attendee-check-in.png';
                $data['desc'] = "Enable attendee check-in system for your events. Authorize your check-in staff to manage attendee tracking efficiently for a smooth and organized event experience.";
                break;
                
            case 'Waiting List':
                $data['url'] = 'https://theeventprime.com/all-extensions/waiting-list/';
                if (in_array('eventprime-waiting-list.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-waiting-list.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("EventPrime_Waiting_List");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=wt');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Waiting List');
                $data['image'] = 'ep-waiting-list-icon.png';
                $data['desc'] = "Allow users to join a waiting list when events are full and get notified if spots open up. Manage priorities, send alerts, and handle bookings efficiently.";
                break;
                
            case 'HoneyPot Security':
                $data['url'] = 'https://theeventprime.com/all-extensions/honeypot-security/';
                if (in_array('eventprime-honeypot-security.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-honeypot-security.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Honeypot_Integration");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=honeypot');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('HoneyPot Security');
                $data['image'] = 'honeypot-icon.png';
                $data['desc'] = "The HoneyPot Security extension for EventPrime adds an invisible anti-spam trap to your event forms, preventing bots from submitting fake data while ensuring a smooth experience for real users.";
                break;
            case 'Turnstile Antispam Security':
                $data['url'] = 'https://theeventprime.com/all-extensions/turnstile-antispam-security/';
                if (in_array('eventprime-turnstile-antispam-security.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-turnstile-antispam-security.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Turnstile_Antispam");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=turnstile-security-settings');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Turnstile Antispam Security');
                $data['image'] = 'ep-turnstile-icon.png';
                $data['desc'] = "EventPrime Turnstile Antispam Security enhances the protection of your event forms by integrating Cloudflare's advanced Turnstile CAPTCHA.";
                break;
            case 'Event Reminder Emails':
                $data['url'] = 'https://theeventprime.com/all-extensions/event-reminder-emails/';
                if (in_array('eventprime-event-reminder-emails.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-event-reminder-emails.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Event_Reminder_Emails");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=email-reminder-settings');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Event Reminder Emails');
                $data['image'] = 'ep-event-reminder-emails-icon.png';
                $data['desc'] = "The Event Reminder Emails extension for EventPrime automatically sends reminder emails to attendees before an event starts.";
                break;
            case 'Square Payments':
                $data['url'] = 'https://theeventprime.com/all-extensions/square-payments/';
                if (in_array('square-payment-integration.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('square-payment-integration.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Square_Payment_Integration");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=payments&section=square');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Square Payments');
                $data['image'] = 'ep-square-icon.png';
                $data['desc'] = "Enable secure and seamless event payments with Square. This extension integrates Square with EventPrime, providing a smooth checkout experience for your attendees.";
                break;
            case 'hCaptcha Security':
                $data['url'] = 'https://theeventprime.com/all-extensions/hcaptcha-security/';
                if (in_array('eventprime-hcaptcha-integration.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-hcaptcha-integration.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Hcaptcha_Integration");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-settings&tab=hcaptcha-security-settings');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('hCaptcha Security');
                $data['image'] = 'hcaptcha-integration.png';
                $data['desc'] = "This extension adds hCaptcha to login, registration, and event booking forms, securing them against bots and automated abuse.";
                break;
            case 'Demo Data':
                $data['url'] = 'https://theeventprime.com/all-extensions/demo-data/';
                if (in_array('eventprime-demo-data.php', $installed_plugin_file)) {
                    $data['button'] = 'Activate';
                    $data['class_name'] = 'ep-activate-now-btn';
                    $file_key = array_search('eventprime-demo-data.php', $installed_plugin_file);
                    if (!empty($file_key)) {
                        $data['is_installed'] = 1;
                    }
                    $data['url'] = $this->em_get_extension_activation_url($installed_plugin_url[$file_key]);
                }
                $data['is_activate'] = class_exists("Eventprime_Demo_Data");
                if ($data['is_activate']) {
                    $data['button'] = 'Setting';
                    $data['class_name'] = 'ep-option-now-btn';
                    $data['url'] = admin_url('edit.php?post_type=em_event&page=ep-demo-data');
                }
                $data['is_free'] = !$this->ep_check_for_premium_extension('Demo Data');
                $data['image'] = 'ep-demo-data-icon.png';
                $data['desc'] = "The purpose of this extension is to help users quickly set up their EventPrime installation with demo events to showcase the plugin’s features. The extension will allow users to generate demo events, with the option to include demo user accounts to show booking details.";
                break;
        }
        return $data;
    }

    // check if extension is premium
    function ep_check_for_premium_extension($extension) {
        $is_premium = 0;
        $premium_ext_list = $this->ep_load_premium_extension_list();
        if (in_array($extension, $premium_ext_list)) {
            $is_premium = 1;
        }
        return $is_premium;
    }

    public function ep_get_wp_editor($content = '', $editor_id = 'description') {
        ob_start();
        wp_editor($content, $editor_id);
        $temp = ob_get_clean();
        $temp .= \_WP_Editors::enqueue_scripts();
        //$temp .= print_footer_scripts();
        $temp .= \_WP_Editors::editor_js();
        return $temp;
    }
    
    public function event_data_tabs_sort($a, $b) {
        if (!isset($a['priority'], $b['priority'])) {
            return -1;
        }
        if ($a['priority'] === $b['priority']) {
            return 0;
        }
        return $a['priority'] < $b['priority'] ? -1 : 1;
    }
    
     public function ep_get_local_timestamp( $timestamp = 0 ) {
        $stamp_diff = floatval( get_option('gmt_offset') ) * 3600;
        if ( $timestamp == 0 )
            return time() + $stamp_diff;
        else
            return $timestamp + $stamp_diff;
    }
    
    // get extension activation url
    public function em_get_extension_activation_url( $path ) {
        $plugin = $path;
        if ( strpos( $path, '/' ) ) {
            $path = str_replace( '/', '%2F', $path );
        }
        $activateUrl = sprintf( admin_url( 'plugins.php?action=activate&plugin=%s' ), $path );    
        $activateUrl = wp_nonce_url( $activateUrl, 'activate-plugin_' . $plugin );
        return $activateUrl;
    }
        
    // check if phone number is valid
    public function is_valid_phone( $phone_number ) {
        $phone_pattern = '^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$^';
        return preg_match( $phone_pattern, $phone_number );
    }

    // check if site url is valid
    public function is_valid_site_url_old( $website ) {
        $url_pattern = '^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()!@:%_\+.~#?&\/\/=]*)^';
        return preg_match( $url_pattern, $website );
    }
    
    public function is_valid_site_url( $website ) {
        $website = trim( (string) $website );
        if ( $website === '' ) {
            return false;
        }

        // Try as-is
        $ok = wp_http_validate_url( $website );

        // Also allow inputs without a scheme (e.g., "example.com")
        if ( ! $ok && strpos( $website, '://' ) === false ) {
            $ok = wp_http_validate_url( 'http://' . $website );
        }

        return (bool) $ok;
    }

    
    public function ep_sanitize_phone_number( $phone ) {
	return preg_replace( '/[^\d+]/', '', $phone );
    }
    
    /**
     * Get specific data from posts
     */
    public function get_events_field_data( $fields = array(), $args = array() ) {
        $response = array();
        $default = array(
            'post_status' => 'publish',
            'order'       => 'ASC',
            'post_type'   => 'em_event',
            'numberposts' => -1,
            'offset'      => 0,
            'meta_key'    => 'em_start_date_time',
            'orderby'     => 'meta_value',
        );
        $args = wp_parse_args( $args, $default );
        $posts = get_posts( $args );
        if( empty( $posts ) ) return array();
        foreach( $posts as $post ) {
            if( empty( $post ) || empty( $post->ID ) ) continue;
            $post_data = array();
            if( ! empty( $fields ) ) {
                if( in_array( 'id', $fields, true ) ) {
                    $post_data['id'] = get_post_meta( $post->ID, 'em_id', true );
                }
                if( in_array( 'name', $fields, true ) ) {
                    $post_data['name'] = get_post_meta( $post->ID, 'em_name', true );
                }
                if( in_array( 'date', $fields, true ) || in_array( 'start_date', $fields, true ) ) {
                    $post_data['date'] = get_post_meta( $post->ID, 'em_start_date', true );
                }
                if( in_array( 'url', $fields, true ) ) {
                    $post_data['url'] = $this->ep_get_custom_page_url( 'events_page', $post->ID, 'event' );
                }
            }
            if( ! empty( $post_data ) ) {
                $response[] = $post_data;
            }
        }
        return $response;
    }
    
    /**
    * Return column size for frontend views.
    * 
    * @param number $number Number
    * 
    * @return number Column Number
    */
    public function ep_check_column_size( $number = 3 ){
        switch($number){
            case 1 : $cols = 12;
            break;
            case 2 : $cols = 6;
            break;
            case 3 : $cols = 4;
            break;
            case 4 : $cols = 3;
            break;
            case 6 : $cols = 2;
            break;
            default: $cols = 4; 
        }
        return $cols;
    }

    /**
    * Echo print die
    */
    public function epd( $val ) {
        echo "<pre>";
            print_r($val);
        echo "</pre>";
        die;
    }

    /**
    * Convert the Hex color to RGB
    * 
    * @param string $color Color Code.
    * 
    * @param int|float $opacity Opecity.
    * 
    * @return string RGBA code.
    */
    public function ep_hex2rgba( $color, $opacity = false ) {
        $default = 'rgb(0,0,0)';
        if( empty( $color ) )
            return $default; 
        if ( $color[0] == '#' ) {
            $color = substr( $color, 1 );
        }
        if ( strlen( $color ) == 6 ) {
            $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) == 3 ) {
            $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else {
            return $default;
        }
        $rgb =  array_map( 'hexdec', $hex );
        if( $opacity ){
            if( abs( $opacity ) == 1 )
                $opacity = 1.0;
            $output = 'rgba( '.implode( ",",$rgb ).','.$opacity.' )';
        } else {
            $output = 'rgb( '.implode( ",",$rgb ).' )';
        }

        return $output;
    }
    
    public function check_published_em_event() {
        // Define query arguments
        $args = [
            'post_type'      => 'em_event',
            'post_status'    => 'publish',
            'posts_per_page' => 1, // Fetch only one post to check existence
            'fields'         => 'ids', // Return only post IDs for efficiency
        ];

        // Query the posts
        $query = new WP_Query($args);

        // Check if there are any posts
        if ($query->have_posts()) {
            return true; // At least one published em_event post exists
        } else {
            return false; // No published em_event posts found
        }
    }


    /** 
     * Load common option for event views
     */
    public function load_event_common_options( $atts = array(), $load_more = 0 ) {
        $events_data     = array();
        $settings = new Eventprime_Global_Settings;
        $events_settings = $settings->ep_get_settings( 'events' );
        $events_data['calendar_view'] = 0;
        $default_cal_view = ( isset( $events_settings->default_cal_view ) ? $events_settings->default_cal_view : 'month' );
        $events_data['display_style'] = isset( $_POST['display_style'] ) ? $_POST['display_style'] : $default_cal_view;
        if( in_array( $events_data['display_style'], array_keys( $this->ep_get_event_calendar_views() ) ) ) {
            $events_data['calendar_view'] = 1;
        }
        if( isset( $atts['view'] ) && ! empty( $atts['view'] ) ){
            $events_data['display_style'] = ! empty( $_POST['display_style'] ) ? $_POST['display_style'] : $atts['view'];
        }
        $events_data['limit'] = isset( $atts['limit'] ) ? ( empty( $atts['limit'] ) ? 10 : $atts["limit"]) : ( empty( $events_settings->show_no_of_events_card ) ? 10 : $events_settings->show_no_of_events_card );
        // if custom event limit is existing and views are card, masonry and list
        if( ( $events_data['display_style'] == 'card' || $events_data['display_style'] == 'square_grid' || $events_data['display_style'] == 'masonry' || $events_data['display_style'] == 'staggered_grid' || $events_data['display_style'] == 'list' || $events_data['display_style'] == 'rows' ) && $events_settings->show_no_of_events_card == 'custom' ){
            $events_data['limit'] = $events_settings->card_view_custom_value;
        }
        if( ( $events_data['display_style'] == 'card' || $events_data['display_style'] == 'square_grid' || $events_data['display_style'] == 'masonry' || $events_data['display_style'] == 'staggered_grid' || $events_data['display_style'] == 'list'|| $events_data['display_style'] == 'rows' ) && $events_settings->show_no_of_events_card == 'all' ){
            $events_data['limit'] = -1;
        }
        // if( ( $events_data['display_style'] == 'card' || $events_data['display_style'] == 'square_grid' || $events_data['display_style'] == 'masonry' || $events_data['display_style'] == 'staggered_grid' || $events_data['display_style'] == 'list'|| $events_data['display_style'] == 'rows' ) && $events_settings->show_no_of_events_card == 'custom' ){
        //     if( ! empty( $atts['block_square_card_fetch_events'] ) ) {
        //         $events_data['limit'] = $atts['block_square_card_fetch_events'];
        //     }
        // }

        // block limit should have higher priority than $events_settings->show_no_of_events_card 
        if( isset($atts['block_square_card_fetch_events']) && !empty( $atts['block_square_card_fetch_events'] ) ) {
            $events_data['limit'] = $atts['block_square_card_fetch_events'];
        }

        if( isset( $_POST['limit'] ) && ! empty( $_POST['limit'] ) ) {
            $events_data['limit'] = $_POST['limit'];
        }
        // shortcode limit
        if( isset( $atts['show'] ) && ! empty( $atts['show'] ) ){
            $events_data['limit'] = $atts['show'];
        }
        $events_data['order'] = 'ASC';
        if(isset($atts['order']) && ! empty($atts['order'])){
           $events_data['order'] = $atts['order']; 
        }
        // limit will be -1 for calendar views
        if($events_data['display_style'] == 'slider' || $events_data['display_style'] == 'month' || $events_data['display_style'] == 'week' || $events_data['display_style'] == 'day' || $events_data['display_style'] == 'listweek' ){
        // if($events_data['display_style'] == 'month' || $events_data['display_style'] == 'week' || $events_data['display_style'] == 'day' || $events_data['display_style'] == 'listweek' ){
            $events_data['limit'] = -1;
        }
        // set query arguments
        $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
        if( $load_more ) {
            $paged = ( $_POST['paged'] ) ? $_POST['paged'] : 1;
            $paged++;
        }
        $events_data['load_more'] = !empty( $_POST['load_more'] ) ? $_POST['load_more'] : 1;
        $events_data['paged'] = $paged;
        $params = array(
            'meta_key'       => 'em_start_date_time',
            'orderby'        => 'meta_value_num',
            'posts_per_page' => $events_data['limit'],
            'offset'         => (int) ( $paged-1 ) * $events_data['limit'],
            'paged'          => $paged,
            'meta_query'     => array( 'relation' => 'AND' ),
            //'order'          => 'DESC'
        );
        if( isset( $events_data['order'] ) && ! empty( $events_data['order'] ) ) {
            $params['order'] = $events_data['order'];
        }
        // if event id is set in shortcode
        if( isset( $atts['id'] ) && ! empty( $atts['id'] ) ){
            $params['post__in'] = explode( ',', $atts['id'] );
        }
        // condition for hide upcoming events
        $hide_upcoming_events = 0;
        if( $this->ep_get_global_settings( 'shortcode_hide_upcoming_events' ) == 1 ) {
            $hide_upcoming_events = 1;
        }
        if( isset( $atts['upcoming'] ) && $atts['upcoming'] == 0 ) {
            $hide_upcoming_events = 1;
        }
        if( $hide_upcoming_events == 1 ) {
            array_push( $params['meta_query'], array(
                'key'     => 'em_start_date',
                'value'   => $this->ep_get_current_timestamp(),
                'compare' => '<='
            ) );
        }
        // condition for hide past events
        $hide_past_events = 0;
        // from settings
        if( $this->ep_get_global_settings( 'hide_past_events' ) == 1 ) {
            $hide_past_events = 1;
        }
		// from shortcode
		if( isset( $atts['upcoming'] ) && $atts['upcoming'] == 1 ) {
            $hide_past_events = 1;
        }
        if( $hide_past_events == 1 ) {
            array_push( $params['meta_query'], array(
                'key'     => 'em_end_date',
                'value'   => strtotime( 'today' ),
                'compare' => '>='
            ) );
        }
        $event_search_params = array();
        if( isset( $_POST['event_search_params'] ) && ! empty( $_POST['event_search_params'] ) ) {
            $event_search_params = json_decode( stripslashes( $_POST['event_search_params'] ), true );
            if( ! empty( $event_search_params ) && count( $event_search_params ) > 0 ) {
                $params = $this->create_filter_query($event_search_params, $params);
            }
        }
        // shortcode event types
        $type_ids = array();
        if( isset( $atts['types'] ) && ! empty( $atts['types'] ) ) {
            $type_ids = explode( ',', $atts['types'] );
        }
        if( isset( $_POST['event_types_ids'] ) && ! empty( $_POST['event_types_ids'] ) ) {
            $type_ids = explode( ',', $_POST['event_types_ids'] );
        }
        if ( ! empty( $type_ids ) ) {
            array_push( $params['meta_query'], array(
                'key'     => 'em_event_type',
                'value'   => $type_ids,
                'compare' => 'IN',
                'type'    =>'NUMERIC'
            ) );
        }
        $events_data['types_ids'] = $type_ids;
        // shortcode event venues
        $venue_ids = array();
        if( isset( $atts['sites'] ) && ! empty( $atts['sites'] ) ) {
            $venue_ids = explode( ',', $atts['sites'] );
        }
        
        if( isset( $_POST['event_venues_ids'] ) && ! empty( $_POST['event_venues_ids'] ) ) {
            $venue_ids = explode( ',', $_POST['event_venues_ids'] );
        }
//        if ( ! empty( $venue_ids ) ) {
//            $filter_venues_ids = array('relation'     => 'OR');
//            foreach ($venue_ids as $venue_id){
//                $filter_venues_ids[]= array(
//                    'key'     => 'em_venue',
//                    'value'   =>  serialize( array($venue_id) ),
//                    'compare' => '='
//                );
//            }
//            $params['meta_query'][] = $filter_venues_ids;
//        }
        
        // Filter events by Venue taxonomy term IDs
        if ( ! empty( $venue_ids ) ) {
            // Ensure it's an array of ints
            $venue_ids = array_map( 'absint', (array) $venue_ids );

            // Initialize tax_query if needed (keeps other tax filters intact)
            if ( empty( $params['tax_query'] ) ) {
                $params['tax_query'] = array( 'relation' => 'AND' );
            }

            $params['tax_query'][] = array(
                'taxonomy'         => 'em_venue',      // <-- change if your taxonomy slug differs
                'field'            => 'term_id',
                'terms'            => $venue_ids,      // match ANY of these IDs
                'operator'         => 'IN',
                'include_children' => false,           // set true if you want child venues included
            );
        }

        $events_data['venue_ids'] = $venue_ids;
        // individual events argument
        $events_data['i_events'] = '';
        if( isset( $atts['individual_events'] ) && ! empty( $atts['individual_events'] ) ){
            $events_data['i_events'] = $atts['individual_events'];
            $params['meta_query'] = $this->individual_events_shortcode_argument( $params['meta_query'],  $events_data['i_events'] );
        }
        // load more individual events
        if( isset( $_POST['i_events'] ) && ! empty( $_POST['i_events'] ) ) {
            $events_data['i_events'] = $_POST['i_events'];
            $params['meta_query'] = $this->individual_events_shortcode_argument( $params['meta_query'],  $_POST['i_events'] );
        }
        $events_data['cols'] = '';
        $show_cols = esc_attr( $this->ep_get_global_settings( 'events_no_of_columns' ) ) ;
        if( ! empty( $atts['block_square_card_columns'] ) ) {
            $show_cols = $atts['block_square_card_columns'];
        }
        if( ! empty( $_POST['event_atts'] ) ) {
            $event_atts_obj = json_decode( stripslashes( $_POST['event_atts'] ) );
            if( ! empty( $event_atts_obj->block_square_card_columns ) ){
                $show_cols = $event_atts_obj->block_square_card_columns;
            }
            $pg_gid = isset($event_atts_obj->pg_gid) ? $event_atts_obj->pg_gid : '';
            if ( $pg_gid ) {
                $atts['pg_gid'] = $pg_gid;
            }
        }
        // render atts data from extensions
        
        $params = apply_filters( 'ep_events_render_attribute_data', $params, $atts ); 
        // multi query
        if( $events_data['display_style'] == 'card' || $events_data['display_style'] == 'square_grid' || $events_data['display_style'] == 'masonry' || $events_data['display_style'] == 'staggered_grid' || $events_data['display_style'] == 'list'|| $events_data['display_style'] == 'rows' || $events_data['display_style'] == 'slider' )
        {
             $posts = $this->get_multiple_events_post_data( $params );
        }
        elseif($this->check_published_em_event())
        {
            $posts = array(1);
        }
        else
        {
            $posts = array();
        }
       
        
        $events_data['params'] = $params;
        $events_data['atts'] = $atts;
        // filter for events
        $events_data['events'] = apply_filters( 'ep_filter_front_events', $posts, $atts );
        // get event views
        $events_data['event_views'] = ( ! empty( $this->ep_get_global_settings( 'front_switch_view_option' ) ) ? $this->ep_get_global_settings( 'front_switch_view_option' ) : array() );
        // get event types
        
        //$events_data['event_types'] = $this->ep_get_event_types( array( 'id', 'name', 'em_color', 'em_type_text_color' ), 1 );
       //echo '<pre>'; print_r($events_data['event_types']);
        
       //print_r($events_data['event_types']);die;
        // get performaers
       // $events_data['performers']  = $this->ep_get_performers( array( 'id', 'name' ) );
        // get organizers
       // $events_data['organizers']  = $this->ep_get_organizers( array( 'id', 'name' ) );
        // get organizers
       // $events_data['venues']      = $this->ep_get_venues( array( 'id', 'name', 'address', 'image' ), 1 );
        // filters and filter elements condition
        $events_data['show_event_filter'] = 1;
        if( $this->ep_get_global_settings( 'disable_filter_options' ) == 1 ) {
            $events_data['show_event_filter'] = 0;
        }
        if( isset( $atts['disable_filter'] ) && $atts['disable_filter'] == 0 ) {
            $events_data['show_event_filter'] = 1;
        }
        // shortcode filters and filter elements condition
        if( isset( $atts['disable_filter'] ) && ! empty( $atts['disable_filter'] ) ){
            $events_data['show_event_filter'] = ( $atts['disable_filter'] == 1 ) ? 0 : $atts['disable_filter'];
        }
        $events_data['section_id'] = rand( 1, 1000 );
        if( ! empty( $show_cols ) ) {
            $events_data['cols'] = 12 / $show_cols;
        }
        if( isset( $atts['cols'] ) && ! empty( $atts['cols'] )){
            $events_data['cols'] = 12 / $atts['cols'];
        }
        if( ! empty( $_POST['cols'] ) ) {
            $events_data['cols'] = sanitize_text_field(wp_unslash($_POST['cols']));
        }
        // show hide filter elements
        $events_data['quick_search'] = $events_data['date_range'] = $events_data['event_type'] = $events_data['venue'] = $events_data['performer'] = $events_data['organizer'] = 1;
        if( isset( $atts['filter_elements'] ) && ! empty( $atts['filter_elements'] ) ) {
            $filter_elements_arr = explode( ',', $atts['filter_elements'] );
            if( in_array( 'quick_search', $filter_elements_arr ) ) {
                $events_data['quick_search'] = 1;
            } else{
                $events_data['quick_search'] = 0;
            }
            if( in_array( 'date_range', $filter_elements_arr ) ){
                $events_data['date_range'] = 1;
            }else{
                $events_data['date_range'] = 0;
            }
            if( in_array( 'event_type', $filter_elements_arr ) ){
                $events_data['event_type'] = 1;
            }else{
                $events_data['event_type'] = 0;
            }
            if( in_array( 'venue', $filter_elements_arr ) ){
                $events_data['venue'] = 1;
            }else{
                $events_data['venue'] = 0;
            }
            if( in_array( 'performer', $filter_elements_arr ) ){
                $events_data['performer'] = 1;
            }else{
                $events_data['performer'] = 0;
            }
            if( in_array( 'organizer', $filter_elements_arr ) ){
                $events_data['organizer'] = 1;
            }else{
                $events_data['organizer'] = 0;
            } 
        }
        $events_data['load_more_text'] = esc_html__( 'Load more', 'eventprime-event-calendar-management' );
        if ( ! empty( $atts['block_square_card_load_more_button'] ) )
        {
            $events_data['load_more_text'] = $atts['block_square_card_load_more_button'] ;
        }
        if( isset( $_POST['block_square_card_load_more_button'] ) && ! empty( $_POST['block_square_card_load_more_button'] ) ) {
            $events_data['load_more_text'] = $_POST['block_square_card_load_more_button'] ;
        }
        if ( isset ($atts['block_square_disable_load_more_button'])){
            $events_data['load_more'] = $atts['block_square_disable_load_more_button'] ;
        }
        if( isset ( $_POST['block_square_disable_load_more_button'] ) ) {
            $events_data['load_more'] = $_POST['block_square_disable_load_more_button'] ;
        }

        $events_data = apply_filters( 'ep_filter_load_event_common_options_events_data_obj', $events_data, $atts );

        return $events_data;
    }
    
    public function ep_get_terms_with_meta_on_all_events_page($taxonomy, $meta_fields = []) {
        // Validate taxonomy
        if (!taxonomy_exists($taxonomy)) {
            return new WP_Error('invalid_taxonomy', 'The provided taxonomy does not exist.');
        }
        

        // Get all terms for the taxonomy
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'number'     => 0, // Fetch all terms
        ]);

        if (is_wp_error($terms)) {
            return $terms;
        }

        $results = [];

        foreach ($terms as $term) {
            $term_array = [];
            // Retrieve all term meta
            $term_id = $term->term_id;
            $meta = get_term_meta($term_id);
            if (!empty($meta)) {
                foreach ($meta as $key => $val) {
                    $term_array[$key] = maybe_unserialize($val[0]);
                }
            }
            
            // Map term data to array
            $term_array['id'] = $term_id;
            $term_array['name'] = htmlspecialchars_decode($term->name);
            $term_array['slug'] = $term->slug;
            $term_array['description'] = $term->description;
            $term_array['count'] = $term->count;
            
            if(!empty($meta_fields))
            {
                $term_data = [];
                foreach($meta_fields as $field)
                {
                    if(isset($term_array[$field]))
                    {
                        $term_data[$field] = $term_array[$field];
                    }
                    else
                    {
                        $term_data[$field] = '';
                    }
                }
                
                $results[$term->term_id] = $term_data;
            }
            else
            {
                $results[$term->term_id] = $term_array;
            }
        }

        return $results;
    }
    
    
    public function ep_get_terms_with_meta($taxonomy, $meta_fields = []) {
        // Validate taxonomy
        if (!taxonomy_exists($taxonomy)) {
            return new WP_Error('invalid_taxonomy', 'The provided taxonomy does not exist.');
        }
        

        // Get all terms for the taxonomy
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'number'     => 0, // Fetch all terms
        ]);

        if (is_wp_error($terms)) {
            return $terms;
        }

        $results = [];

        foreach ($terms as $term) {
            $term_array = [];
            // Retrieve all term meta
            $term_id = $term->term_id;
            $meta = get_term_meta($term_id);
            if (!empty($meta)) {
                foreach ($meta as $key => $val) {
                    $term_array[$key] = maybe_unserialize($val[0]);
                }
            }
            
            // Map term data to array
            $term_array['id'] = $term_id;
            $term_array['name'] = htmlspecialchars_decode($term->name);
            $term_array['slug'] = $term->slug;
            $term_array['description'] = $term->description;
            $term_array['count'] = $term->count;
            if($taxonomy=='em_event_type')
            {
                $term_array['event_type_url'] = $this->ep_get_custom_page_url('event_types', $term_id, 'event_type', 'term',$taxonomy);
                $term_array['image_url'] = $this->get_event_type_image_url($term_id);
            }
            elseif($taxonomy=='em_venue')
            {
                $term_array['venue_url'] = $this->ep_get_custom_page_url('venues_page', $term_id, 'venue', 'term',$taxonomy);
                $term_array['image_url'] = plugin_dir_url(EP_PLUGIN_FILE) . 'admin/partials/images/dummy_image.png';
                $term_array['em_address'] = $term_array['em_address'] ?? '';
                $term_array['other_image_url'] = [];

                if (!empty($term_array['em_gallery_images'])) {
                    $gallery_images = maybe_unserialize($term_array['em_gallery_images']);
                    if (is_array($gallery_images) && count($gallery_images) > 0) {
                        $img_url = wp_get_attachment_image_src($gallery_images[0], 'large');
                        if (!empty($img_url) && isset($img_url[0])) {
                            $term_array['image_url'] = $img_url[0];
                        }

                        // Other images
                        if (count($gallery_images) > 1) {
                            for ($i = 1; $i < count($gallery_images); $i++) {
                                $term_array['other_image_url'][] = wp_get_attachment_image_src($gallery_images[$i], 'large')[0];
                            }
                        }
                    }
                }
            }
            elseif($taxonomy=='em_organizer')
            {
                $term_array['organizer_url'] = $this->ep_get_custom_page_url('event_organizers', $term_id, 'organizer', 'term',$taxonomy);
                $term_array['image_url'] = $this->get_event_organizer_image_url($term_id);
            }
             
            if(!empty($meta_fields))
            {
                $term_data = [];
                foreach($meta_fields as $field)
                {
                    if(isset($term_array[$field]))
                    {
                        $term_data[$field] = $term_array[$field];
                    }
                    else
                    {
                        $term_data[$field] = '';
                    }
                }
                
                $results[$term->term_id] = $term_data;
            }
            else
            {
                $results[$term->term_id] = $term_array;
            }
        }

        return $results;
    }
    
    
    public function create_filter_query( $event_search_params, $args ){
        foreach( $event_search_params as $params ) {
            if( $params['label'] == 'Keyword' && ! empty( $params['value'] ) ) {
                $args['s'] = sanitize_text_field( $params['value'] );
            }
            if( $params['key'] == 'days' && $params['value'] == 'next_weekend') {
                $event_start_date = strtotime('next Saturday');
                $event_end_date = strtotime('next Sunday');
                $args['meta_query'][] = array(
                    'relation'     => 'AND',
                    array(
                        'key'      => 'em_start_date',
                        'value'    => $event_start_date,
                        'compare'  => '>=',
                        'type'     => 'NUMERIC'
                    ),
                    array(
                        'key'      => 'em_end_date',
                        'value'    => $event_end_date,
                        'compare'  => '<=',
                        'type'     => 'NUMERIC'
                    )
                );
            }
            if( $params['key'] == 'days' && $params['value'] =='next_month') {
                $event_start_date = strtotime('first day of +1 month');
                $event_end_date = strtotime('last day of +1 month');
                $args['meta_query'][] = array(
                    'relation'     => 'AND',
                    array(
                        'key'      => 'em_start_date',
                        'value'    => array($event_start_date, $event_end_date),
                        'compare'  => 'BETWEEN',
                        'type'     => 'NUMERIC'
                    ),
                    /*array(
                        'key'      => 'em_end_date',
                        'value'    => $event_end_date,
                        'compare'  => '<=',
                        'type'     => 'NUMERIC'
                    )*/
                );
            }
            if( $params['key'] == 'days' && $params['value'] =='next_week') {
                $event_start_date = strtotime('monday next week');
                $event_end_date = strtotime('sunday next week');
                $args['meta_query'][] = array(
                    'relation'     => 'AND',
                    array(
                        'key'      => 'em_start_date',
                        'value'    => array($event_start_date, $event_end_date),
                        'compare'  => 'BETWEEN',
                        'type'     => 'NUMERIC'
                    ),
                    /*array(
                        'key'      => 'em_end_date',
                        'value'    => $event_end_date,
                        'compare'  => '<=',
                        'type'     => 'NUMERIC'
                    )*/
                );
            }
            if( $params['key'] == 'date_from' && !empty($params['value'])) {
                $dates_query['date_from'] = $params['value'];    
            }
            if( $params['key'] == 'date_to' && !empty($params['value'])) {
                $dates_query['date_to'] = $params['value'];
            }
            if( $params['key'] == 'days' && !empty($params['value'])) {
                $dates_query['days'] = $params['value'];
            } 
            if( $params['key'] == 'event_venues' && !empty($params['value'])) {
                $event_venue_id = $params['value'];
                $args['meta_query'][] =array(
                    array(
                        'key'      => 'em_venue',
                        'value'    => maybe_serialize(array('0',$event_venue_id)),
                        'compare'  => '='
                    )
                );
            }
            if( $params['key'] == 'event_types' && !empty($params['value'])) {
                $event_type_id = (int)$params['value'];
                $args['meta_query'][] = array(
                    array(
                        'key'      => 'em_event_type',
                        'value'    => $event_type_id,
                        'compare'  => '='
                    )
                );
            }
            if( $params['key'] == 'event_performers' && !empty($params['value'])) {
                $event_performer_ids = $params['value'];
                $filter_perfomers = array('relation'     => 'OR');
                foreach ($event_performer_ids as $performer_id){
                    $filter_perfomers[]= array(
                        'key'     => 'em_performer',
                        'value'   =>  maybe_serialize( strval ( $performer_id ) ),
                        'compare' => 'LIKE'
                    );
                }
                $args['meta_query'][] = $filter_perfomers;
                    
            }
            if( $params['key'] == 'event_organizers' && !empty($params['value'])) {
                $event_organizer_ids = $params['value'];
                $filter_organizers = array('relation' => 'OR');
                foreach ( $event_organizer_ids as $org_id ){
                    $filter_organizers[]= array(
                        'key'     => 'em_organizer',
                        'value'   =>  serialize( strval ( $org_id ) ),
                        'compare' => 'LIKE'
                    );
                }
                $args['meta_query'][] = $filter_organizers;
                    
            }
        }
        if( ! empty( $dates_query ) ) {
            $format = $this->ep_get_datepicker_format();
            if(isset($dates_query['date_from']) && isset($dates_query['date_to']) && isset($dates_query['days']) && strtolower($dates_query['days']) =='all'){
                $start_date = $this->ep_datetime_to_timestamp( $dates_query['date_from'] . ' 12:00 AM' );
                $end_date = $this->ep_datetime_to_timestamp( $dates_query['date_to'] . ' 11:59 PM' );
                $args['meta_query'][] = array(
                     'relation'     => 'OR',
                    array(
                        'key'     => 'em_start_date',
                        'value'   => array( $start_date, $end_date ),
                        'compare' => 'BETWEEN',
                        'type'    => 'NUMERIC'
                    ),
                    array(
                        'key'     => 'em_end_date',
                        'value'   => array( $start_date, $end_date ),
                        'compare' => 'BETWEEN',
                        'type'    => 'NUMERIC'
                    )
                );
            } elseif( isset( $dates_query['date_from'] ) && isset( $dates_query['date_to'] ) && isset( $dates_query['days'] ) && $dates_query['days'] != 'all' ) {
                $dates = array();
                if( strtolower( $dates_query['days'] ) == 'weekends' ) {
                    $dates = $this->getweekendDays( $dates_query['date_from'], $dates_query['date_to'] );
                } else{
                    $dates = $this->getweekDays( $dates_query['date_from'], $dates_query['date_to'] );
                }
                $date_meta_query = array( 'relation' => 'OR' );
                if( ! empty( $dates ) ) {
                    foreach( $dates as $date ) {
                        $start_date = $this->ep_datetime_to_timestamp( $dates_query['date_from'] . ' 12:00 AM' );
                        $end_date = $this->ep_datetime_to_timestamp( $dates_query['date_to'] . ' 11:59 PM' );
                        $date_meta_query[] = array(
                            'key'     => 'em_start_date',
                            'value'   => array( $start_date, $end_date ),
                            'compare' => 'BETWEEN',
                            'type'    => 'NUMERIC'
                        );
                    }
                    $args['meta_query'][]= $date_meta_query;
                }

            } elseif( isset( $dates_query['date_from'] ) ) {
                $start_date = $this->ep_datetime_to_timestamp( $dates_query['date_from'] . ' 12:00 AM' );
                $args['meta_query'][] = array(
                    'key'     => 'em_start_date',
                    'value'   => $start_date,
                    'compare' => '>=',
                    'type'    => 'NUMERIC'
                );
            } elseif( isset( $dates_query['date_to'] ) ) {
                $end_date = $this->ep_datetime_to_timestamp( $dates_query['date_to'] . ' 11:59 PM' );
                $args['meta_query'][] = array(
                    'key'     => 'em_end_date',
                    'value'   => $end_date,
                    'compare' => '<=',
                    'type'    => 'NUMERIC'
                );
            }
        }
        return $args;        
    }

     public function getweekDays($startDate, $endDate) {
        $begin = strtotime($startDate);
        $end   = strtotime($endDate);
        $dates = array();
        if ($begin > $end) {
            return $dates;
        } else {
            while ($begin <= $end) {
                $day = gmdate("N", $begin);
                if (!in_array($day, [6,7]) ){
                    $dates[]= gmdate("Y-m-d h:i a", $begin);
                }
                $begin += 86400; // +1 day
            }
            return $dates;
        }
    }
    
    public function getweekendDays($startDate, $endDate) {
        $begin = strtotime($startDate);
        $end   = strtotime($endDate);
        $dates = array();
        if ($begin > $end) {
            return $dates;
        } else {
            while ($begin <= $end) {
                $day = gmdate("N", $begin);
                if (!in_array($day, [1,2,3,4,5]) ){
                    $dates[]= gmdate("Y-m-d h:i a", $begin);
                }
                $begin += 86400; // +1 day
            }
            return $dates;
        }
    }
    
    public function individual_events_shortcode_argument( $meta_query, $individual_events = '' ) {
        if( $individual_events == 'yesterday' ) {
            $yesterday_dt = new DateTime('yesterday');
            $yesterday_dt->setTime(0, 0, 0); // Set to start of the day
            $yesterday_ts = $yesterday_dt->getTimestamp();

            $today_dt = new DateTime('today');
            $today_dt->setTime(0, 0, 0);
            $today_ts = $today_dt->getTimestamp();

            $meta_query[] = array(
                'key'     => 'em_start_date',
                'value'   => array($yesterday_ts, $today_ts),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC'
            );
        }

        if( $individual_events == 'today' ) {
            $today_dt = new DateTime('today');
            $today_dt->setTime(0, 0, 0);
            $today_ts = $today_dt->getTimestamp();

            $tomorrow_dt = new DateTime('tomorrow');
            $tomorrow_dt->setTime(0, 0, 0);
            $tomorrow_ts = $tomorrow_dt->getTimestamp();

            $meta_query[] = array(
                'key'     => 'em_start_date',
                'value'   => array($today_ts, $tomorrow_ts),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC'
            );
        }

        if( $individual_events == 'tomorrow' ) {
            $tomorrow_dt = new DateTime('tomorrow');
            $tomorrow_dt->setTime(0, 0, 0);
            $tomorrow_ts = $tomorrow_dt->getTimestamp();

            $day_after_tomorrow_dt = new DateTime('tomorrow');
            $day_after_tomorrow_dt->modify('+1 day');
            $day_after_tomorrow_dt->setTime(0, 0, 0);
            $day_after_tomorrow_ts = $day_after_tomorrow_dt->getTimestamp();

            $meta_query[] = array(
                'key'     => 'em_start_date',
                'value'   => array($tomorrow_ts, $day_after_tomorrow_ts),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC'
            );
        }

        if( $individual_events == 'this month' ) {
            $this_month_dt = new DateTime('first day of this month');
            $this_month_dt->setTime(0, 0, 0);
            $this_month_ts = $this_month_dt->getTimestamp();

            $next_month_dt = new DateTime('first day of next month');
            $next_month_dt->setTime(0, 0, 0);
            $next_month_ts = $next_month_dt->getTimestamp();

            $meta_query[] = array(
                'key'     => 'em_start_date',
                'value'   => array($this_month_ts, $next_month_ts),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC'
            );
        }

        return $meta_query;
    }

    
    public function individual_events_shortcode_argument_old( $meta_query, $individual_events = '' ){
        if( $individual_events == 'yesterday' ){
            $yesterday_dt = new DateTime('yesterday');
            $yesterday_ts = strtotime( $yesterday_dt->format('Y-m-d H:i:s') );
            array_push($meta_query,array(
                'key' => 'em_start_date',
                'value' => $yesterday_ts,
                'compare' => '>=',
                'type'=>'NUMERIC'
            ));
            $today_dt = new DateTime('today');
            $today_ts = strtotime( $today_dt->format('Y-m-d H:i:s') );
            array_push($meta_query,array(
                'key' => 'em_start_date',
                'value' => $today_ts,
                'compare' => '<',
                'type'=>'NUMERIC'
            ));
        }
        if( $individual_events == 'today' ){
            $today_dt = new DateTime('today');
            $today_ts = strtotime( $today_dt->format('Y-m-d H:i:s') );
            array_push($meta_query,array(
                'key' => 'em_start_date',
                'value' => $today_ts,
                'compare' => '>=',
                'type'=>'NUMERIC'
            ));
            $tomorrow_dt = new DateTime('tomorrow');
            $tomorrow_ts = strtotime( $tomorrow_dt->format('Y-m-d H:i:s') );
            array_push($meta_query,array(
                'key' => 'em_start_date',
                'value' => $tomorrow_ts,
                'compare' => '<',
                'type'=>'NUMERIC'
            ));
        }
        if( $individual_events == 'tomorrow' ){
            $tomorrow_dt = new DateTime('tomorrow');
            $tomorrow_ts = strtotime( $tomorrow_dt->format('Y-m-d H:i:s') );
            array_push($meta_query,array(
                'key' => 'em_start_date',
                'value' => $tomorrow_ts,
                'compare' => '>=',
                'type'=>'NUMERIC'
            ));
            $tda_tomorrow_dt = new DateTime('tomorrow');
            $tda_tomorrow_dt->modify('+1 day');
            $tda_tomorrow_ts = strtotime( $tda_tomorrow_dt->format('Y-m-d H:i:s') );
            array_push($meta_query,array(
                'key' => 'em_start_date',
                'value' => $tda_tomorrow_ts,
                'compare' => '<',
                'type'=>'NUMERIC'
            ));
        }

        if( $individual_events == 'this month' ){
            $this_month_dt = new DateTime('first day of this month');
            $this_month_ts = strtotime( $this_month_dt->format('Y-m-d 00:00:00') );
            array_push($meta_query,array(
                'key' => 'em_start_date',
                'value' => $this_month_ts,
                'compare' => '>=',
                'type'=>'NUMERIC'
            ));
            $next_month_dt = new DateTime('first day of next month');
            $next_month_ts = strtotime( $next_month_dt->format('Y-m-d 00:00:00') );
            array_push($meta_query,array(
                'key' => 'em_start_date',
                'value' => $next_month_ts,
                'compare' => '<',
                'type'=>'NUMERIC'
            ));
        }
        return $meta_query;
    }
    
    /**
     * Get multiple event data query
     */
    
     public function get_multiple_events_post_data( $args = array() ) {
        $default = array(
            'post_status' => 'publish',
            'order'       => 'ASC',
            'post_type'   => 'em_event',
            'numberposts' => -1,
            'offset'      => 0,
            'meta_key'    => 'em_start_date_time',
            'orderby'     => 'meta_value',
        );
        $args = wp_parse_args( $args, $default );
        $posts = get_posts( $args );
        if( empty( $posts ) )
           return array();
        $events = $event_ids = array();
        foreach( $posts as $post ) {
            if( empty( $post ) || empty( $post->ID ) ) continue;
            $event_ids[] = $post->ID;
            $event = new stdClass();
            // set all metas
            $meta = get_post_meta( $post->ID );
            foreach ( $meta as $key => $val ) {
                $event->{$key} = maybe_unserialize( $val[0] );
            }
            $event->id                 = $post->ID;
            $event->name               = $post->post_title;
            $event->em_name            = $post->post_title;
            $event->slug               = $post->post_name;
            $event->description        = wp_kses_post( $post->post_content );
            $event->post_status        = $post->post_status;
            $event->post_parent        = $post->post_parent;
            $event->fstart_date        = ( ! empty( $event->em_start_date ) ) ? $this->ep_timestamp_to_date( $event->em_start_date, 'd M', 1 ) : '';
            $event->fend_date          = ( ! empty( $event->em_end_date ) ) ? $this->ep_timestamp_to_date( $event->em_end_date, 'd M', 1 ) : '';
            if( ! empty( $event->em_start_date ) && ! empty( $this->ep_get_global_settings( 'enable_event_time_to_user_timezone' ) ) ){
                $event->fstart_date = $this->ep_convert_event_date_time_from_timezone( $event, 'd M', 0, 1 );
                $event->fend_date   = $this->ep_convert_event_date_time_from_timezone( $event, 'd M', 1, 1 );
            }
            $event->start_end_diff     = $this->ep_get_event_date_time_diff( $event );
            $event->event_url          = $this->ep_get_custom_page_url( 'events_page', $event->id, 'event' );
            $event->all_tickets_data   = array();
            /* $event->venue_details      = ( ! empty( $event->em_venue ) ) ? EventM_Factory_Service::ep_get_venue_by_id( $event->em_venue ) : array();
            $event->event_type_details = ( ! empty( $event->em_event_type ) ) ? EventM_Factory_Service::ep_get_event_type_by_id( $event->em_event_type ) : array();
            $event->organizer_details  = ( ! empty( $event->em_organizer ) ) ? EventM_Factory_Service::get_organizers_by_id( $event->em_organizer ) : array();
            $event->performer_details  = ( ! empty( $event->em_performer) ) ? EventM_Factory_Service::get_performers_by_id( $event->em_performer ) : array(); */
            $event->image_url          = $this->get_event_image_url( $event->id );
            $event->placeholder_image_url = plugin_dir_path(EP_PLUGIN_FILE) . 'public/partials/images/dummy_image.png';
            //$other_events            = EventM_Factory_Service::ep_get_child_events( $post->ID );
            $event->child_events       = array();
            /* if( ! empty( $other_events ) && count( $other_events ) > 0 ) {
                $other_event_data    = EventM_Factory_Service::load_event_full_data( $other_events );
                $event->child_events = $other_event_data;
            } */
            $event->qr_code          = $this->get_event_qr_code( $event );
            $event->event_in_user_wishlist = $this->check_event_in_user_wishlist( $event->id );
            $events[$post->ID] = $event;
        }
        // get all event ticket category data
        $event_ticket_categories = $this->get_multiple_events_ticket_category( $event_ids );
        if( ! empty( $event_ticket_categories ) ) {
            foreach( $event_ticket_categories as $category_data ) {
                if( ! empty( $category_data->event_id ) ) {
                    $events[$category_data->event_id]->ticket_categories[] = $category_data;
                    if( ! empty( $category_data->tickets ) ) {
                        $event_cat_tickets = $category_data->tickets;
                        if( ! empty( $event_cat_tickets ) && count( $event_cat_tickets ) > 0 ) {
                            foreach( $event_cat_tickets as $tickets_ev ) {
                                $events[$category_data->event_id]->all_tickets_data[] = $tickets_ev;
                            }
                        }
                    }
                }
            }
        }
        // get individual tickets
        $event_solo_tickets = $this->get_multiple_events_solo_ticket( $event_ids );
        if( ! empty( $event_solo_tickets ) ) {
            foreach( $event_solo_tickets as $ticket_data ) {
                if( ! empty( $ticket_data->event_id ) ) {
                    $events[$ticket_data->event_id]->solo_tickets[] = $ticket_data;
                    $events[$ticket_data->event_id]->all_tickets_data[] = $ticket_data;
                }
            }
        }
        $all_checkout_fields = $this->ep_get_checkout_fields_data();
        // various data
        foreach( $events as $event_data ) {
            // price range
            $event_data->ticket_price_range = array();
            // all offers
            $all_offers_data = array(
                'all_offers'         => array(),
                'all_show_offers'    => array(),
                'show_ticket_offers' => array(),
                'ticket_offers'      => array(),
                'applicable_offers'  => array()
            );
            $price_range = array();

            $event_data->all_tickets_data =  apply_filters( 'ep_filter_event_all_tickets_data', $event_data->all_tickets_data, $event_data );

            if( ! empty( $event_data->all_tickets_data ) && count( $event_data->all_tickets_data ) > 0 ) {
                $all_tickets = $event_data->all_tickets_data;
                if( count( $event_data->all_tickets_data ) > 1 ) {
                    $price_range['multiple'] = 1;
                    $prices = array();
                    foreach( $all_tickets as $ticket ) {
                        $prices[] = $ticket->price;
                        // event offer
                        if( ! empty( $ticket->offers ) ) {
                            $all_offers_data = $this->get_event_single_offer_data( $all_offers_data, $ticket, $event_data->em_id );
                            /* $ticket_offers = json_decode( $ticket->offers );
                            if( ! empty( $ticket_offers ) ) {
                                foreach( $ticket_offers as $to ) {
                                    $all_offers_data['all_offers'][] = $to;
                                    if( isset( $to->em_ticket_show_offer_detail ) && ! empty( $to->em_ticket_show_offer_detail ) ) {
                                        $all_offers_data['all_show_offers'][$to->uid] = $to;
                                        $all_offers_data['show_ticket_offers'][$ticket->id][$to->uid] = $to;
                                    }
                                    $all_offers_data['ticket_offers'][$ticket->id][$to->uid] = $to;
                                }
                                $offer_applied_data = EventM_Factory_Service::get_event_offer_applied_data( $ticket_offers, $ticket );
                                if( ! empty( $offer_applied_data ) && count( $offer_applied_data ) > 0 ) {
                                    $all_offers_data['applicable_offers'][$ticket->id] = $offer_applied_data;
                                }
                            } */
                        }
                    }
                    $min_price = min( $prices );
                    $max_price = max( $prices );
                    $price_range['min'] = $min_price;
                    $price_range['max'] = $max_price;
                } else{
                    $price_range['multiple'] = 0;
                    foreach( $event_data->all_tickets_data as $ticket ) {
                        $price_range['price'] = $ticket->price;
                    }
                }
            }
            $event_data->ticket_price_range = $price_range;
            $event_data->all_offers_data  = $all_offers_data;
            // checkout fields
            if( ! empty( $event_data->em_event_checkout_attendee_fields ) && ! empty( $all_checkout_fields ) ) {
                $attendee_fields = $event_data->em_event_checkout_attendee_fields;
                if( ! empty( $attendee_fields ) && ! empty( $attendee_fields['em_event_checkout_fields_data'] ) && count( $attendee_fields['em_event_checkout_fields_data'] ) > 0 ) {
                    $attendee_fields_data = array();
                    foreach( $attendee_fields['em_event_checkout_fields_data'] as $fields ) {
                        if( isset( $all_checkout_fields[ $fields ] ) && ! empty( $all_checkout_fields[ $fields ] ) ) {
	                        $attendee_fields_data[] = $all_checkout_fields[ $fields ];
						}
                    }
                    $attendee_fields['em_event_checkout_fields_data'] = $attendee_fields_data;
                }
                $event_data->em_event_checkout_attendee_fields = $attendee_fields;
            }
        }
        $wp_query = new WP_Query( $args );
        $wp_query->posts = $events;
        return $wp_query;
    }
    
    public function get_event_qr_code_duplicate( $event ) {
		$image_url = '';
		if( ! empty( $event ) && isset( $event->event_url ) && ! empty( $event->event_url ) ) {
			$url = $event->event_url;
			$file_name = 'ep_qr_'.md5($url).'.png';
			$upload_dir = wp_upload_dir();
			$file_path = $upload_dir['basedir'] . '/ep/' . $file_name;
			if( ! file_exists( $file_path ) ) {
				if( ! file_exists( dirname( $file_path ) ) ){
					mkdir( dirname( $file_path ), 0755 );
				}
				require_once plugin_dir_path(EP_PLUGIN_FILE) . 'includes/lib/qrcode.php';
				$qrCode = new QRcode();
				$qrCode->png( $url, $file_path, 'M', 4, 2 );
			}
			$image_url = esc_url( $upload_dir['baseurl'].'/ep/'.$file_name );
		}
		return $image_url;
	}
        
        public function get_multiple_events_ticket_category( $event_ids ) {
        if( empty( $event_ids ) ) return;
        if( is_array( $event_ids ) ) {
            $event_ids = implode( ',', $event_ids );
        }
        
        $dbhandler = new EP_DBhandler;
        $additional = 'event_id IN ('.$event_ids.')';
        $get_cat_data = $dbhandler->get_all_result('TICKET_CATEGORIES', '*',1, 'results', 0, false, 'priority', false,$additional,'OBJECT_K');
        if( ! empty( $get_cat_data ) ) {
                foreach( $get_cat_data as $category ) {
                    // get tickets from category id and event id
                    $get_ticket_data = $dbhandler->get_all_result('TICKET', '*', array('category_id' => $category->id), 'results', 0, false, 'priority', false);
                    if( ! empty( $get_ticket_data ) && count( $get_ticket_data ) > 0 ) {
                        $category->tickets = $get_ticket_data;
                    }
                    //$cat_data[] = $category;
                }
            }
        
        return $get_cat_data;
    }

    /** Get events tickets where category_id = 0
     * 
     * @param array $event_ids Multiple Events
     * 
     * @return array Ticket Data.
     */
    public function get_multiple_events_solo_ticket( $event_ids ) {
        if( empty( $event_ids ) ) return;
        if( is_array( $event_ids ) ) {
            $event_ids = implode( ',', $event_ids );
        }
        $dbhandler = new EP_DBhandler;
        $additional = 'category_id = 0 AND event_id IN ('.$event_ids.' )';
        $ticket_data = $dbhandler->get_all_result('TICKET', '*',1, 'results', 0, false, 'priority', false,$additional,'OBJECT_K');
        
        return $ticket_data;
    }
    
    public function ep_get_event_calendar_views() {
        $calendar_views = array(
            'month'    => 'Month',
            'week'     => 'Week',
            'day'      => 'Day',
            'listweek' => 'Listweek',
        );
        return $calendar_views;
    }

    public function ep_get_event_types( $fields, $with_id = 0 ) {
        $dbhandler = new EP_DBhandler;
        $event_type_data = array();
        if( !empty( $fields ) ) {
            $event_type_data = $dbhandler->get_event_type_field_data( $fields, $with_id );
	}
        return $event_type_data;
    }
    
   
    public function ep_get_performers( $fields ) {
        $dbhandler = new EP_DBhandler;
	$performers_data = array();
        if(!empty( $fields ) ) {
			$performers_data = $dbhandler->get_performer_field_data( $fields );
		}

		return $performers_data;
    }
    
    public function ep_get_performers_list($fields)
    {
        $dbhandler = new EP_DBhandler;
        $response = array();
        $posts = $dbhandler->get_performer_all_data();
        if (!empty($posts) && count($posts) > 0) {
            foreach ($posts as $post) {
                $post_data = array();
                if (!empty($fields)) {
                    if (in_array('id', $fields, true)) {
                        $post_data['id'] = $post->ID;
                    }
                    if (in_array('image_url', $fields, true)) {
                        $featured_img_url = get_the_post_thumbnail_url($post->ID, 'thumbnail');
                        $post_data['image_url'] = (!empty($featured_img_url) ) ? $featured_img_url : '';
                    }
                    if (in_array('name', $fields, true)) {
                        $post_data['name'] = $post->post_title;
                    }
                }
                if (!empty($post_data)) {
                    $response[] = $post_data;
                }
            }
        }
        
        return $response;
    }
    
    public function ep_get_organizers( $fields ) {
        $dbhandler = new EP_DBhandler;
        $organizers_data = array();
        if( !empty( $fields ) ) {
                $organizers_data = $dbhandler->get_event_organizer_field_data( $fields );
        }

        return $organizers_data;
    }
    
    public function ep_get_venues( $fields, $with_id = 0 ) {
        $dbhandler = new EP_DBhandler;
        $venues_data = array();
        if(!empty( $fields ) ) {
                $venues_data = $dbhandler->get_event_venues_field_data( $fields, $with_id );
        }
        return $venues_data;
    }
    
    public function ep_get_template_part($slug, $name = null, $data = array(), $ext_path = null) {
        $file = '';
        if (isset($name)) {
            $template = $slug . '-' . $name . '.php';
            // check file in yourtheme/eventprime
            $file = locate_template(['eventprime/' . $template], false, false);

            if (!$file) {
                if (!empty($ext_path)) {
                    $file = $ext_path . "/views/" . $template;
                } 
                elseif(strpos($slug,'.php'))
                {
                    $file = $slug;
                }
                else {
                    
                    $file = plugin_dir_path(EP_PLUGIN_FILE) . "/public/partials/" . $template;
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
                }
                elseif(strpos($slug,'.php'))
                {
                    $file = $slug;
                }
                else {
                    
                    $file = plugin_dir_path(EP_PLUGIN_FILE) . "public/partials/" . $template;
                }
            }
        }

        // Allow 3rd party plugins to filter template file from their plugin.
        $file = apply_filters('ep_get_template_part', $file, $slug, $name);

        if ($file) {
            load_template($file, false, $data);
        }
    }
    
    public function get_front_calendar_view_event( $events, $is_admin = false ) {
        $cal_events = array();

        if ( empty( $events ) ) {
            return $cal_events;
        }

        $new_window = ( ! empty( $this->ep_get_global_settings( 'open_detail_page_in_new_tab' ) ) ? 'target="_blank"' : '' );
        $event_listings_date_format = ! empty( $this->ep_get_global_settings( 'event_listings_date_format_val' ) )
            ? $this->ep_get_global_settings( 'event_listings_date_format_val' )
            : 'Y-m-d';

        foreach ( $events as $event ) {
            if ( empty( $event ) || empty( $event->id ) ) {
                continue;
            }

            // Base event data
            $ev = $this->get_event_data_to_views( $event );

            // Handle hide-time global and per-event settings
            $hide_time = $this->ep_get_global_settings( 'hide_time_on_front_calendar' );
            if ( isset( $event->em_hide_event_end_time ) && $event->em_hide_event_end_time == 1 ) {
                $ev['end_time'] = $ev['display_end_time'] = '';
            }
            if ( isset( $event->em_hide_event_start_time ) && $event->em_hide_event_start_time == 1 ) {
                $ev['start_time'] = $ev['display_start_time'] = '';
            }
            if ( $hide_time ) {
                $ev['start_time'] = $ev['end_time'] = '';
                $ev['display_start_time'] = $ev['display_end_time'] = '';
            }

            // Prepare start date for popup (already shifted)
            $start_date_time = $ev['display_start_date'];

            // For admin popup, add edit links
            if ( $is_admin ) {
                $ev['edit_url'] = esc_url( 'post.php?post=' . $ev['id'] . '&action=edit' );
                if ( isset( $ev['url'] ) ) {
                    unset( $ev['url'] );
                }
            }

            // ───────────────────────────────────────────────
            // Popup HTML
            // ───────────────────────────────────────────────
            $popup_html  = '<div class="ep_event_detail_popup" id="ep_calendar_popup_' . esc_attr( $ev['id'] ) . '" style="display:none">';
            $popup_html .= '<a href="' . esc_url( $ev['event_url'] ) . '" class="ep_event_popup_head" ' . esc_attr( $new_window ) . '>';
            $popup_html .= '<div class="ep_event_popup_image"><img src="' . esc_url( $ev['image'] ) . '"></div>';
            $popup_html .= '</a>';

            $popup_html .= '<div class="ep_event_popup_date_time_wrap ep-d-flex">';

            // Dates
            $popup_html .= '<div class="ep_event_popup_date ep-d-flex ep-box-direction">';
            if ( $this->ep_show_event_date_time( 'em_start_date', $event ) ) {
                $popup_html .= '<span class="ep_event_popup_start_date">' . esc_html( $start_date_time ) . '</span>';
            } else {
                if ( ! empty( $ev['date_custom_note'] ) ) {
                    if ( $ev['date_custom_note'] === 'tbd' ) {
                        $tbd_icon_file = plugin_dir_path( EP_PLUGIN_FILE ) . 'public/partials/images/tbd-icon.png';
                        $popup_html .= '<span class="ep_event_popup_start_date"><img src="' . esc_url( $tbd_icon_file ) . '" width="35" /></span>';
                    } else {
                        $popup_html .= '<span class="ep_event_popup_start_date">' . esc_html( $ev['date_custom_note'] ) . '</span>';
                    }
                }
            }

            if ( $this->ep_show_event_date_time( 'em_end_date', $event ) ) {
                $popup_html .= '<span class="ep_event_popup_end_date">' . esc_html( $ev['display_end_date'] ) . '</span>';
            }
            $popup_html .= '</div>';

            // Times
            $popup_html .= '<div class="ep_event_popup_time ep-d-flex ep-box-direction">';
            if ( $this->ep_show_event_date_time( 'em_start_time', $event ) && ! empty( $ev['display_start_time'] ) ) {
                $popup_html .= '<span class="ep_event_popup_start_time">' . esc_html( $ev['display_start_time'] ) . '</span>';
            }
            if ( $this->ep_show_event_date_time( 'em_end_time', $event ) && ! empty( $ev['display_end_time'] ) ) {
                $popup_html .= '<span class="ep_event_popup_end_time">' . esc_html( $ev['display_end_time'] ) . '</span>';
            }
            $popup_html .= '</div>'; // .ep_event_popup_time

            $popup_html .= '</div>'; // .ep_event_popup_date_time_wrap

            // Title
            $popup_html .= '<a href="' . esc_url( $ev['event_url'] ) . '" class="ep-event-modal-head" ' . esc_attr( $new_window ) . '>';
            $popup_html .= '<div class="ep_event_popup_title ep-text-break">' . esc_html( $ev['title'] ) . '</div>';
            $popup_html .= '</a>';

            // Venue and address
            if ( ! empty( $ev['venue_name'] ) ) {
                $popup_html .= '<div class="ep_event_popup_address">' . esc_html( $ev['venue_name'] ) . '</div>';
            }
            if ( ! empty( $ev['address'] ) ) {
                $popup_html .= '<div class="ep_event_popup_address">' . esc_html( $ev['address'] ) . '</div>';
            }

            // Allow filters
            $popup_html = apply_filters( 'ep_event_calendar_event_popup_html', $popup_html, $ev, $event );

            // Admin action buttons
            if ( $is_admin ) {
                $popup_html .= '<div class="ep_event_popup_action_btn ep-d-flex ep-justify-content-between ep-border-top ep-py-2 ep-px-4 ep-text-center">';
                $popup_html .= '<a href="' . esc_url( $ev['event_url'] ) . '" class="ep_event_popup_btn ep-text-decoration-none ep-box-w-100" target="__blank">';
                $popup_html .= '<div class="ep-event-action-btn ep-py-2">View Event</div>';
                $popup_html .= '</a>';
                if ( current_user_can( 'edit_em_events' ) ) {
                    $popup_html .= '<a href="' . esc_url( $ev['edit_url'] ) . '" class="ep_event_popup_btn ep-border-left ep-text-decoration-none ep-box-w-100" target="__blank">';
                    $popup_html .= '<div class="ep-event-action-btn ep-py-2">Edit Event</div>';
                    $popup_html .= '</a>';
                }
                $popup_html .= '</div>';
            }

            $popup_html .= '</div>'; // .ep_event_detail_popup

            $ev['popup_html'] = $popup_html;
            $cal_events[]     = $ev;
        }

        return $cal_events;
    }

    
     public function get_front_calendar_view_event_old( $events, $is_admin=false ) {
        $cal_events = array();
        if( ! empty( $events ) && ! empty( $events ) ) {
            $new_window = ( ! empty( $this->ep_get_global_settings( 'open_detail_page_in_new_tab' ) ) ? 'target="_blank"' : '' );
            $event_listings_date_format = !empty($this->ep_get_global_settings('event_listings_date_format_val')) ? $this->ep_get_global_settings('event_listings_date_format_val') : 'Y-m-d';
            
            foreach( $events as $event ) {
                //print_r($event);die;
                if( ! empty( $event ) && ! empty( $event->id ) ) 
                {
                $ev = $this->get_event_data_to_views( $event );
                $hide_time = $this->ep_get_global_settings( 'hide_time_on_front_calendar' );
                if(isset($event->em_hide_event_end_time) && $event->em_hide_event_end_time==1)
                {
                    $ev['end_time'] = '';
                }
                
                if(isset($event->em_hide_event_start_time) && $event->em_hide_event_start_time==1)
                {
                    $ev['start_time'] = '';
                }
                
                if($hide_time)
                {
                    $ev['start_time'] = '';
                    $ev['end_time'] = '';
                }
                $start_date_time = $ev['start'];
                if( $this->ep_show_event_date_time( 'em_start_time', $event ) ) {
                    $start_date_time = explode( ' ', $start_date_time )[0];
                }
                $start_date_time = wp_date( $event_listings_date_format, $event->em_start_date ); 
                // popup html
                if($is_admin)
                {
                    $ev['edit_url'] = esc_url('post.php?post='.$ev['id'].'&action=edit');
                    if(isset($ev['url'])){
                        unset($ev['url']);
                    }
                    $ev['event_url'] = $ev['event_url'];
                }
                $popup_html = '<div class="ep_event_detail_popup" id="ep_calendar_popup_'.esc_attr( $ev['id'] ).'" style="display:none">';
                    $popup_html .= '<a href="'.esc_url( $ev['event_url'] ).'" class="ep_event_popup_head" '.esc_attr( $new_window ).'>';
                        $popup_html .= '<div class="ep_event_popup_image">';
                            $popup_html .= '<img src="'.esc_url( $ev['image'] ).'">';
                        $popup_html .= '</div>';
                    $popup_html .= '</a>';
                    $popup_html .= '<div class="ep_event_popup_date_time_wrap ep-d-flex">';
                        $popup_html .= '<div class="ep_event_popup_date ep-d-flex ep-box-direction">';
                            if( $this->ep_show_event_date_time( 'em_start_date', $event ) ) {
                                $popup_html .= '<span class="ep_event_popup_start_date">' .esc_html( $start_date_time ) .'</span>';
                            } else{
                                if( ! empty( $ev['date_custom_note'] ) ) {
                                    if( $ev['date_custom_note'] == 'tbd' ) {
                                        $tbd_icon_file = plugin_dir_path(EP_PLUGIN_FILE) .'public/partials/images/tbd-icon.png';
                                        $popup_html .= '<span class="ep_event_popup_start_date"><img src="'. esc_url( $tbd_icon_file ) .'" width="35" /></span>';
                                    } else{
                                        $popup_html .= '<span class="ep_event_popup_start_date">' .esc_html( $ev['date_custom_note'] ) .'</span>';
                                    }
                                }
                            }
                            if( $this->ep_show_event_date_time( 'em_end_date', $event ) ) {
                                $popup_html .= '<span class="ep_event_popup_end_date">';
                                    if( isset( $ev['event_day'] ) && ! empty( $ev['event_day'] ) ) {
                                        $popup_html .= esc_html( $ev['event_day'] );
                                    } else{
                                        $event_end_dt = $ev['end'];
                                        if( ! empty( $event_end_dt ) ) {
                                            $event_end_dt = explode( ' ', $event_end_dt )[0];
                                        }
                                        $popup_html .= esc_html( $event_end_dt );
                                    }
                                    $popup_html .= '</span>';
                            }
                        $popup_html .= '</div>';
                        $popup_html .= '<div class="ep_event_popup_time ep-d-flex ep-box-direction">';
                            if( $this->ep_show_event_date_time( 'em_start_time', $event ) ) {
                                $popup_html .= '<span class="ep_event_popup_start_time">' .esc_html( $this->ep_convert_time_with_format( $ev['start_time'] ) ) .'</span>';
                            }
                            if( $this->ep_show_event_date_time( 'em_end_time', $event ) ) {
                                $popup_html .= '<span class="ep_event_popup_end_time">' .esc_html( $this->ep_convert_time_with_format( $ev['end_time'] ) ) .'</span>';
                            }
                        $popup_html .= '</div>';
                    $popup_html .= '</div>';
                    $popup_html .= '<a href="'.esc_url( $ev['event_url'] ).'" class="ep-event-modal-head" '.esc_attr( $new_window ).'>';
                        $popup_html .= '<div class="ep_event_popup_title ep-text-break">';
                            $popup_html .= esc_html( $ev['title'] );
                        $popup_html .= '</div>';
                    $popup_html .= '</a>';
                    if( ! empty( $ev['venue_name'] ) ) {
                        $popup_html .= '<div class="ep_event_popup_address">';
                            $popup_html .= esc_html( $ev['venue_name'] );
                        $popup_html .= '</div>';
                    }
                    if( ! empty( $ev['address'] ) ) {
                        $popup_html .= '<div class="ep_event_popup_address">';
                            $popup_html .= esc_html( $ev['address'] );
                        $popup_html .= '</div>';
                    }

                    $popup_html = apply_filters( 'ep_event_calendar_event_popup_html', $popup_html, $ev, $event );

                    // booking button
                    /* ob_start();
                        do_action( 'ep_event_view_event_booking_button', $event );
                        $popup_html .= ob_get_contents();
                    ob_end_clean(); */
                    
                    if($is_admin)
                    {
                        //Edit View Event
                        $popup_html .= '<div class="ep_event_popup_action_btn ep-d-flex ep-justify-content-between ep-border-top ep-py-2 ep-px-4 ep-text-center">';
                            $popup_html .= '<a href="'.esc_url( $ev['event_url'] ).'" class="ep_event_popup_btn ep-text-decoration-none ep-box-w-100" target="__blank">';
                                $popup_html .= '<div class="ep-event-action-btn ep-py-2">';
                                    $popup_html .= esc_html( 'View Event', 'eventprime-event-calendar-management' );
                                $popup_html .= '</div>';
                            $popup_html .= '</a>';
                            if( current_user_can('edit_em_events') ) {
                                $popup_html .= '<a href="'.esc_url( $ev['edit_url'] ).'" class="ep_event_popup_btn ep-border-left ep-text-decoration-none ep-box-w-100" target="__blank">';
                                    $popup_html .= '<div class="ep-event-action-btn ep-py-2">';
                                        $popup_html .= esc_html( 'Edit Event', 'eventprime-event-calendar-management' );
                                    $popup_html .= '</div>';
                                $popup_html .= '</a>';
                            }
                        $popup_html .= '</div>';
                        // End Edit View
                    }
                $popup_html .= '</div>';
                $ev['popup_html']=  $popup_html;
                $cal_events[] = $ev;
            }
            }
        }
        return $cal_events;
    }
    
    public function get_event_data_to_upcoming_views( $event ) {
        $ev = array();
        if( ! empty( $event ) && ! empty( $event->id ) ) {
            $ev['title'] = ( ! empty( $event->em_name ) ? $event->em_name : $event->name );
            $ev['id']    = $event->id;
            $ev['start'] = $ev['end'] = $ev['start_time'] = $ev['end_time'] = $ev['bg_color'] = $ev['type_text_color'] = $ev['address'] = $ev['image'] = $ev['date_custom_note'] = $ev['event_day'] = '';
            $ev['bg_color'] = 'rgb( 34,113,177 )';
            if( ! empty( $event->em_start_date ) ) {
                $start_date       = $this->ep_timestamp_to_date( $event->em_start_date, 'Y-m-d', 1 );
                $ev['start']      = $start_date;
                if( ! empty( $event->em_start_time ) && $this->ep_show_event_date_time( 'em_start_time', $event ) ) {
                    $st_time = gmdate( "H:i", strtotime( $event->em_start_time ) );
                    $st_time = explode( ' ', $st_time )[0];
                    $ev['start'] .= ' '. $st_time;
                }
                $ev['start_time'] = ( ! empty( $event->em_start_time ) ? $event->em_start_time : '' );
            }
            if( ! empty( $event->em_end_date ) ) {
                $end_date   = $this->ep_timestamp_to_date( $event->em_end_date, 'Y-m-d', 1 );
                $ev['end']  = $end_date;
                if( ! empty( $event->em_start_date ) && $event->em_start_date == $event->em_end_date ) {
                    $ev['event_day']  = gmdate( 'l', $event->em_start_date );
                }
                if( $this->ep_show_event_date_time( 'em_end_time', $event ) ) {
                    if( ! empty( $event->em_end_time ) ) {
                        $end_time = gmdate( "H:i", strtotime( $event->em_end_time ) );
                        $end_time = explode( ' ', $end_time )[0];
                        $ev['end'] .= ' '. $end_time;
                    } else{
                        if( $this->ep_is_multidate_event( $event ) ) {
                            $ev['end'] .= ' 11:59';
                        }
                    }
                } else{
                    if( empty( $event->em_hide_event_start_time ) ) {
                        if( $this->ep_is_multidate_event( $event ) ) {
                            $ev['end'] .= ' 11:59';
                        }
                    }
                    if( ! empty( $event->em_hide_event_start_time ) && ! empty( $event->em_hide_event_end_time ) ) {
                        if( $this->ep_is_multidate_event( $event ) ) {
                            if( ! empty( $event->em_end_time ) ) {
                                $end_time = gmdate( "H:i", strtotime( $event->em_end_time ) );
                                $end_time = explode( ' ', $end_time )[0];
                                $ev['end'] .= ' '. $end_time;
                            }else{
                                $ev['end'] .= ' 11:59';
                            }
                        }
                    }
                }
                if( ! empty( $event->em_end_time ) ) {
                    $ev['end_time'] = $event->em_end_time;
                }
            }
            // event type
            if( ! empty( $event->em_event_type ) ) {
                $single_et             =  $event->event_type_details;
                $ev['bg_color']        = ( ! empty( $single_et->em_color ) ) ? $this->ep_hex2rgba( $single_et->em_color ) : 'rgb( 34,113,177 )';
                $ev['type_text_color'] = ( !empty( $single_et->em_type_text_color ) ) ? $single_et->em_type_text_color : '#000000';
            }
            // venue
            if( ! empty( $event->em_venue ) ) {
                $single_venue  = $event->venue_details;
                $ev['venue_name'] = ( ! empty( $single_venue->name ) ) ? $single_venue->name : '';
                $ev['address'] = ( ! empty( $single_venue->em_address ) && ! empty( $single_venue->em_display_address_on_frontend ) ) ? $single_venue->em_address : '';
            }
            // image
            $featured_img_url = get_the_post_thumbnail_url( $event->id );
            if( ! empty( $featured_img_url ) ) {
                $ev['image'] = $featured_img_url;
            }
           
            // if hide start date then check for custom note
            if( ! $this->ep_show_event_date_time( 'em_start_date', $event ) ) {
                if( isset( $event->em_event_date_placeholder ) && $event->em_event_date_placeholder != 'custom_note' ) {
                    $ev['date_custom_note'] = $event->em_event_date_placeholder;
                } else{
                    $ev['date_custom_note'] = ( ! empty( $event->em_event_date_placeholder_custom_note) ? $event->em_event_date_placeholder_custom_note : '' );
                }
            }
            $ev['event_type'] = ( ! empty( $event->em_event_type ) ? $event->em_event_type : '' );
            $ev['venue'] = ( ! empty( $event->em_venue ) ? $event->em_venue : '' );
            $ev['performer'] = ( ! empty( $event->em_performer ) ? $event->em_performer : array() );
            $ev['organizer'] = ( ! empty( $event->em_organizer ) ? $event->em_organizer : array() );
            $ev['booking_enable'] = $event->em_enable_booking;
            /* $ev['thumbnail_id'] = get_post_thumbnail_id( $event->id );
            $ev['status'] = get_post_status( $event->id ); */
            $ev['all_day'] = ( ! empty( $event->em_all_day ) ? $event->em_all_day : 0 );
            $ev['event_end_date'] = $this->ep_timestamp_to_date( $event->em_end_date );
            $ev['event_start_date'] = $this->ep_timestamp_to_date( $event->em_start_date );
            $ev['event_id'] = $event->id;
            $ev['event_title'] = ( ! empty( $event->em_name ) ? $event->em_name : $event->name );
            // event text color
            if( ! empty( $event->em_event_text_color ) ) {
                $ev['type_text_color'] = $event->em_event_text_color;
            }
            // open event in new tab
            $ev['open_event_in_new_tab'] = absint( $this->ep_get_global_settings( 'open_detail_page_in_new_tab' ) );
        }
        return $ev;
    }
    
    
   
   // Keep time static, shift *date* to target timezone; return naive local "YYYY-MM-DDTHH:MM:SS"
    private function fc_datetime_date_shift_only( int $date_ts, string $time_str, DateTimeZone $target_tz ): string {
        $date_local = wp_date('Y-m-d', $date_ts, $target_tz);

        $time_str = trim((string)$time_str);
        if ($time_str === '') { $time_str = '00:00:00'; }

        $dt = DateTime::createFromFormat('g:i A', strtoupper($time_str))
           ?: DateTime::createFromFormat('g:i:s A', strtoupper($time_str))
           ?: DateTime::createFromFormat('H:i', $time_str)
           ?: DateTime::createFromFormat('H:i:s', $time_str);

        $norm = $dt ? $dt->format('H:i:s') : '00:00:00';
        return $date_local . 'T' . $norm;
    }

    // Display time helper (always 12h "g:i A")
    private function ep_time_display_12h( string $time_str ): string {
        $s = trim((string)$time_str);
        if ($s === '') return '';
        $dt = DateTime::createFromFormat('g:i A', strtoupper($s))
           ?: DateTime::createFromFormat('g:i:s A', strtoupper($s))
           ?: DateTime::createFromFormat('H:i', $s)
           ?: DateTime::createFromFormat('H:i:s', $s);
        return $dt ? $dt->format('g:i A') : $s;
    }


    public function get_event_data_to_views( $event ) {
        $ev = array();
        $target_tz = wp_timezone();

        if ( empty($event) || empty($event->id) ) return $ev;

        $ev['title'] = ( ! empty($event->em_name) ? $event->em_name : $event->name );
        $ev['id']    = $event->id;

        // init
        $ev['start'] = $ev['end'] = '';
        $ev['start_time'] = $ev['end_time'] = '';
        $ev['bg_color'] = 'rgb( 34,113,177 )';
        $ev['type_text_color'] = '#000000';
        $ev['address'] = $ev['image'] = $ev['date_custom_note'] = $ev['event_day'] = '';

        // >>>> NEW: display-only fields for popup
        $ev['display_start_date'] = $ev['display_end_date'] = '';
        $ev['display_start_time'] = $ev['display_end_time'] = '';

        // START
        if ( ! empty( $event->em_start_date ) ) {
            // FullCalendar machine value
            if ( ! empty($event->em_start_time) && $this->ep_show_event_date_time('em_start_time', $event) ) {
                $ev['start'] = $this->fc_datetime_date_shift_only( (int)$event->em_start_date, (string)$event->em_start_time, $target_tz );
            } else {
                // all-day start of day
                $ev['start'] = wp_date('Y-m-d', (int)$event->em_start_date, $target_tz) . 'T00:00:00';
            }

            // Visible strings
            $ev['display_start_date'] = wp_date('F j, Y', (int)$event->em_start_date, $target_tz);
            $ev['start_time']         = ( ! empty($event->em_start_time) ? (string)$event->em_start_time : '' );
            $ev['display_start_time'] = $this->ep_convert_time_with_format( $ev['start_time'] );
        }

        // END
        if ( ! empty( $event->em_end_date ) ) {
            $is_multiday = ! empty($event->em_start_date) && ((int)$event->em_start_date !== (int)$event->em_end_date);

            // FullCalendar machine value
            if ( $this->ep_show_event_date_time('em_end_time', $event) && !empty($event->em_end_time) ) {
                $ev['end'] = $this->fc_datetime_date_shift_only( (int)$event->em_end_date, (string)$event->em_end_time, $target_tz );
            } else {
                // >>>> when times are hidden, end at 23:59:59 of the (shifted) end date so the chip spans the full last day
                $end_date_local = wp_date('Y-m-d', (int)$event->em_end_date, $target_tz);
                $ev['end']      = $end_date_local . 'T23:59:59';
            }

            // Visible strings
            $ev['display_end_date'] = wp_date('F j, Y', (int)$event->em_end_date, $target_tz);
            $ev['end_time']         = ( ! empty($event->em_end_time) ? (string)$event->em_end_time : '' );
            $ev['display_end_time'] = $this->ep_convert_time_with_format( $ev['end_time'] );

            // Weekday label for single-day only
            if ( ! empty($event->em_start_date) && (int)$event->em_start_date === (int)$event->em_end_date ) {
                $ev['event_day'] = wp_date('l', (int)$event->em_start_date, $target_tz);
            }
        }

        // Event type colors
        if ( ! empty( $event->em_event_type ) ) {
            $single_et = $this->get_single_event_type( $event->em_event_type );
            $ev['bg_color']        = ( ! empty($single_et->em_color) ) ? $this->ep_hex2rgba( $single_et->em_color ) : $ev['bg_color'];
            $ev['type_text_color'] = ( ! empty($single_et->em_type_text_color) ) ? $single_et->em_type_text_color : $ev['type_text_color'];
        }

        // Venue
        if ( ! empty( $event->em_venue ) ) {
            $single_venue  = $this->get_single_venue( $event->em_venue );
            $ev['venue_name'] = ( ! empty( $single_venue->name ) ) ? $single_venue->name : '';
            $ev['address']    = ( ! empty( $single_venue->em_address ) && ! empty( $single_venue->em_display_address_on_frontend ) ) ? $single_venue->em_address : '';
        }

        // Image
        $featured_img_url = get_the_post_thumbnail_url( $event->id );
        if ( is_admin() && defined('ELEMENTOR_VERSION') ) {
            $featured_img_url = $this->get_event_image_url($event->id);
        } else {
            $thumb_id = get_post_thumbnail_id( $event->id );
            if ( ! empty($thumb_id) ) {
                $arr = wp_get_attachment_image_src( $thumb_id, 'full', false );
                if ( ! empty($arr) ) $featured_img_url = $arr[0];
            }
        }
        if ( ! empty($featured_img_url) ) {
            $ev['image'] = $featured_img_url;
        }

        // URLs
        $ev['event_url'] = $this->ep_get_custom_page_url('events_page', $event->id, 'event');
        $ev['url']       = $ev['event_url'];

        $settings          = new Eventprime_Global_Settings;
        $global_options    = $settings->ep_get_settings();
        if ( !empty($global_options->redirect_third_party) && $global_options->redirect_third_party == 1 && $event->em_enable_booking == 'external_bookings' ) {
            $em_custom_link = get_post_meta( $event->id, 'em_custom_link', true );
            if ( ! empty($em_custom_link) ) {
                $ev['event_url'] = $ev['url'] = $em_custom_link;
            }
        }

        // Date placeholder logic (unchanged)
        if ( ! $this->ep_show_event_date_time('em_start_date', $event) ) {
            if ( isset($event->em_event_date_placeholder) && $event->em_event_date_placeholder != 'custom_note' ) {
                $ev['date_custom_note'] = $event->em_event_date_placeholder;
            } else {
                $ev['date_custom_note'] = ( ! empty($event->em_event_date_placeholder_custom_note) ? $event->em_event_date_placeholder_custom_note : '' );
            }
        }

        // Misc
        $ev['event_type']      = ( ! empty($event->em_event_type) ? $event->em_event_type : '' );
        $ev['venue']           = ( ! empty($event->em_venue) ? $event->em_venue : '' );
        $ev['performer']       = ( ! empty($event->em_performer) ? $event->em_performer : array() );
        $ev['organizer']       = ( ! empty($event->em_organizer) ? $event->em_organizer : array() );
        $ev['booking_enable']  = $event->em_enable_booking;
        $ev['all_day']         = ( ! empty($event->em_all_day) ? (int)$event->em_all_day : 0 );
        $ev['event_end_date']  = $this->ep_timestamp_to_date( $event->em_end_date );
        $ev['event_start_date']= $this->ep_timestamp_to_date( $event->em_start_date );
        $ev['event_id']        = $event->id;
        $ev['event_title']     = $ev['title'];
        $ev['open_event_in_new_tab'] = absint( $this->ep_get_global_settings('open_detail_page_in_new_tab') );

        return $ev;
    }

     public function get_event_data_to_views_old( $event ) {
        $ev = array();
        $target_tz = wp_timezone();
        if( ! empty( $event ) && ! empty( $event->id ) ) {
            $ev['title'] = ( ! empty( $event->em_name ) ? $event->em_name : $event->name );
            $ev['id']    = $event->id;
            $ev['start'] = $ev['end'] = $ev['start_time'] = $ev['end_time'] = $ev['bg_color'] = $ev['type_text_color'] = $ev['address'] = $ev['image'] = $ev['date_custom_note'] = $ev['event_day'] = '';
            $ev['bg_color'] = 'rgb( 34,113,177 )';
            if( ! empty( $event->em_start_date ) ) {
                $start_date       = $this->ep_timestamp_to_date( $event->em_start_date, 'Y-m-d', 1 );
                $ev['start']      = $start_date;
               if( ! empty( $event->em_start_time ) && $this->ep_show_event_date_time( 'em_start_time', $event ) ) {
                 $ev['start'] = $this->fc_datetime_date_shift_only($event->em_start_date, $event->em_start_time,$target_tz);
                 //$ev['start'] = $this->ep_timestamp_to_datetime($event->em_start_date_time,'Y-m-d H:i', 1 );
               }
                
                $ev['start_time'] = ( ! empty( $event->em_start_time ) ? $event->em_start_time : '' );
            }
            if( ! empty( $event->em_end_date ) ) {
                $end_date   = $this->ep_timestamp_to_date( $event->em_end_date, 'Y-m-d', 1 );
                $ev['end']  = $end_date;
                
                if( ! empty( $event->em_start_date ) && $event->em_start_date == $event->em_end_date ) {
                    $ev['event_day']  = wp_date( 'l', $event->em_start_date );
                }
                if( $this->ep_show_event_date_time( 'em_end_time', $event ) ) {
                    //$ev['end'] = $this->ep_timestamp_to_datetime($event->em_end_date_time,'Y-m-d H:i', 1 );
                    $ev['end'] = $this->fc_datetime_date_shift_only($event->em_end_date, $event->em_end_time,$target_tz);
                } else{
                    if( empty( $event->em_hide_event_start_time ) ) {
                        if( $this->ep_is_multidate_event( $event ) ) {
                            $ev['end'] .= ' 11:59';
                        }
                    }
                    if( ! empty( $event->em_hide_event_start_time ) && ! empty( $event->em_hide_event_end_time ) ) {
                        if( $this->ep_is_multidate_event( $event ) ) {
                            if( ! empty( $event->em_end_time ) ) {
                                $end_time = wp_date( "H:i", strtotime( $event->em_end_time ) );
                                $end_time = explode( ' ', $end_time )[0];
                                $ev['end'] .= ' '. $end_time;
                            }else{
                                $ev['end'] .= ' 11:59';
                            }
                        }
                    }
                }
                if( ! empty( $event->em_end_time ) ) {
                    $ev['end_time'] = $event->em_end_time;
                }
            }
            // event type
            if( ! empty( $event->em_event_type ) ) {
                $single_et             = $this->get_single_event_type( $event->em_event_type );
                $ev['bg_color']        = ( ! empty( $single_et->em_color ) ) ? $this->ep_hex2rgba( $single_et->em_color ) : 'rgb( 34,113,177 )';
                $ev['type_text_color'] = ( !empty( $single_et->em_type_text_color ) ) ? $single_et->em_type_text_color : '#000000';
            }
            // venue
            if( ! empty( $event->em_venue ) ) {
                $single_venue  = $this->get_single_venue( $event->em_venue );
                $ev['venue_name'] = ( ! empty( $single_venue->name ) ) ? $single_venue->name : '';
                $ev['address'] = ( ! empty( $single_venue->em_address ) && ! empty( $single_venue->em_display_address_on_frontend ) ) ? $single_venue->em_address : '';
            }
            // image
            $featured_img_url = get_the_post_thumbnail_url( $event->id );
            if(is_admin() && defined('ELEMENTOR_VERSION'))
            {
                $featured_img_url = $this->get_event_image_url($event->id);
            }
            else
            {
                $thumb_id = get_post_thumbnail_id( $event->id );

                if( ! empty( $thumb_id ) ) {
                    $featured_img_url_array = wp_get_attachment_image_src( $thumb_id, 'full', false);
                    if(!empty($featured_img_url_array))
                    {
                        $featured_img_url = $featured_img_url_array[0];
                    }
                } 
            }
            
            if( ! empty( $featured_img_url ) ) {
                $ev['image'] = $featured_img_url;
            }
            
            
            
            
            
            // url
            $ev['event_url'] = $this->ep_get_custom_page_url( 'events_page', $event->id, 'event' );
            $ev['url'] = $this->ep_get_custom_page_url( 'events_page', $event->id, 'event' );
            $options = array();
            $settings          = new Eventprime_Global_Settings;
            $options['global'] = $settings->ep_get_settings();
            $global_options = $options['global'];
            if( $global_options->redirect_third_party == 1 && $event->em_enable_booking == 'external_bookings' ) {
                $em_custom_link   = get_post_meta( $event->id, 'em_custom_link', true );
                $ev['event_url'] = $em_custom_link;
                $ev['url'] = $em_custom_link;       
            }
            // if hide start date then check for custom note
            if( ! $this->ep_show_event_date_time( 'em_start_date', $event ) ) {
                if( isset( $event->em_event_date_placeholder ) && $event->em_event_date_placeholder != 'custom_note' ) {
                    $ev['date_custom_note'] = $event->em_event_date_placeholder;
                } else{
                    $ev['date_custom_note'] = ( ! empty( $event->em_event_date_placeholder_custom_note) ? $event->em_event_date_placeholder_custom_note : '' );
                }
            }
            $ev['event_type'] = ( ! empty( $event->em_event_type ) ? $event->em_event_type : '' );
            $ev['venue'] = ( ! empty( $event->em_venue ) ? $event->em_venue : '' );
            $ev['performer'] = ( ! empty( $event->em_performer ) ? $event->em_performer : array() );
            $ev['organizer'] = ( ! empty( $event->em_organizer ) ? $event->em_organizer : array() );
            $ev['booking_enable'] = $event->em_enable_booking;
            /* $ev['thumbnail_id'] = get_post_thumbnail_id( $event->id );
            $ev['status'] = get_post_status( $event->id ); */
            $ev['all_day'] = ( ! empty( $event->em_all_day ) ? $event->em_all_day : 0 );
            $ev['event_end_date'] = $this->ep_timestamp_to_date( $event->em_end_date );
            $ev['event_start_date'] = $this->ep_timestamp_to_date( $event->em_start_date );
            $ev['event_id'] = $event->id;
            $ev['event_title'] = ( ! empty( $event->em_name ) ? $event->em_name : $event->name );
            // event text color
            if( ! empty( $event->em_event_text_color ) ) {
                $ev['type_text_color'] = $event->em_event_text_color;
            }
            // open event in new tab
            $ev['open_event_in_new_tab'] = absint( $this->ep_get_global_settings( 'open_detail_page_in_new_tab' ) );
        }
        return $ev;
    }
    
    public function ep_is_multidate_event( $event ){
    if( is_numeric( $event->em_start_date ) && is_numeric( $event->em_end_date ) ) {
        $totalSecondsDiff = abs( $event->em_start_date - $event->em_end_date );
        $totalDaysDiff = $totalSecondsDiff/60/60/24;
        if( $totalDaysDiff > 1 ) {
            return true;
        }
    }
    return false;
}

    
   // get calendar local
public function ep_get_calendar_locale() {
    $locale = get_locale();
    $locale = ( empty( $locale ) || is_null( $locale ) ) ? 'en' : $locale;
    if( strlen( $locale ) > 5 ) {
        $locale = substr( $locale, 0, 5 );
    }
    $locale = strtolower( $locale );
    $locale = str_replace( '_', '-', $locale );
    if( in_array( $locale, $this->get_calendar_locales() ) ) {
        return $locale;
    } else {
        return substr( $locale, 0, 2 );
    }
}
   
public function ep_show_event_date_time( $value, $event ) {
    if( empty( $event->{$value} ) ) {
        return false;
    }

    if( $value == 'em_start_date' ) {
        if( ! empty( $event->em_hide_event_start_date ) ) {
            return false;
        }
    }

    if( $value == 'em_start_time' || $value == 'em_end_date' || $value == 'em_end_time' ) {
        if( $event->em_all_day == 1 ) {
            return false;
        } else{
            if( $value == 'em_start_time' && ! empty( $event->em_hide_event_start_time ) ) {
                return false;
            }
            if( $value == 'em_end_date' && ! empty( $event->em_hide_end_date ) ) {
                return false;
            }
            if( $value == 'em_end_time' && ! empty( $event->em_hide_event_end_time ) ) {
                return false;
            }
        }
    }

    return true;
}

// convert front end time to 24 hours format
public function ep_convert_time_with_format( $ep_time ) {
    $timeIn24HourFormat = $ep_time;
    $time_format = $this->ep_get_global_settings( 'time_format' );
    if( ! empty( $time_format ) && $time_format == 'HH:mm' ) {
        $dateTime = new DateTime( $ep_time );
        $timeIn24HourFormat = $dateTime->format('H:i');
    }
    return $timeIn24HourFormat;
}

public function ep_convert_event_date_time_to_timestamp( $event, $start = 'start' ) {
    $timestamp = '';
    if( $start == 'start' ) {
        if( ! empty( $event->em_start_date ) ) {
            $timestamp = $event->em_start_date;
            if( ! empty( $event->em_start_time ) ) {
                $start = $this->ep_timestamp_to_date( $event->em_start_date );
                $start .= ' '.$event->em_start_time;
                $timestamp = $this->ep_datetime_to_timestamp( $start );
            }
        }
    } else{
        if( ! empty( $event->em_end_date ) ) {
            $timestamp = $event->em_end_date;
            if( ! empty( $event->em_end_time ) ) {
                $end = $this->ep_timestamp_to_date( $event->em_end_date );
                $end .= ' '.$event->em_end_time;
                $timestamp = $this->ep_datetime_to_timestamp( $end );
            }
        }
    }
    return $timestamp;
}

public function get_ep_event_performer($ids)
{
    $performers = array();
    $dbhandler = new EP_DBhandler;
    $args = array( 'post__in' =>$ids);
    $performers = $dbhandler->get_performer_all_data($args);
    $response = array();
    if(!empty($performers))
    {
        foreach($performers as $performer)
        {
            $response[] = $this->get_single_performer($performer->ID);
        }
    }
   
    return $response;
}
    

public function ep_get_checkout_page_esential_fields() {
    $fields = array(
        'name' => array(
            'label' => esc_html__( 'Name', 'eventprime-event-calendar-management' ),
            'sub_fields' => array(
                'first_name' => array(
                    'label' => esc_html__( 'First Name', 'eventprime-event-calendar-management' ),
                    'type'  => 'text'
                ),
                'middle_name' => array(
                    'label' => esc_html__( 'Middle Name', 'eventprime-event-calendar-management' ),
                    'type'  => 'text'
                ),
                'last_name' => array(
                    'label' => esc_html__( 'Last Name', 'eventprime-event-calendar-management' ),
                    'type'  => 'text'
                )
            )
        ),
        'email' => array(
            'label' => esc_html__( 'Email', 'eventprime-event-calendar-management' ),
            'type'  => 'email'
        ),
        'phone' => array(
            'label' => esc_html__( 'Phone', 'eventprime-event-calendar-management' ),
            'type'  => 'tel'
        )
    );
    return $fields;
}

public function eventprime_check_remaining_tickets_in_event($event,$ticket_id,$qty)
{
        $total_caps = $total_bookings = 0;
        if( ! empty( $event ) && ! $this->check_event_has_expired( $event ) && ! empty( $event->all_tickets_data ) ) 
        {
                if(isset($event->all_bookings))
                {
                    $all_event_bookings = $this->get_event_booking_by_event_id( $event->id, true,$event->all_bookings );
                }
                else
                {
                    $all_event_bookings = $this->get_event_booking_by_event_id( $event->id, true );
                }
                $booked_tickets_data = $all_event_bookings['tickets'];
                foreach( $event->all_tickets_data as $ticket ) {
                        // ticket total capacity
                    if(!empty($ticket_id) && $ticket_id==$ticket->id)
                    {
                        $total_caps += $ticket->capacity;
                        // ticket booked capacity
                        $total_bookings += ( ! empty( $booked_tickets_data[$ticket->id] ) ? $booked_tickets_data[$ticket->id] : 0 );
                    }
                    else
                    {
                        $total_caps += $ticket->capacity;
                        // ticket booked capacity
                        $total_bookings += ( ! empty( $booked_tickets_data[$ticket->id] ) ? $booked_tickets_data[$ticket->id] : 0 );
                    }

                    $total_caps = apply_filters('ep_event_verify_total_capacity',$total_caps,$event, $ticket);
                    $total_bookings = apply_filters('ep_event_verify_total_bookings',$total_bookings,$event, $ticket);
                }
                
                if( $total_caps > $total_bookings ) {
                    $remaining_tickets = $total_caps - $total_bookings;
                    if($remaining_tickets >= $qty)
                    {
                        return $remaining_tickets;
                    }
                    else
                    {
                        return false;
                    }
                } 
                else
                {
                    return false;
                }
                
        }
}

public function ep_is_event_sold_out( $event ) 
{
        $total_caps = $total_bookings = 0;
        if( ! empty( $event ) && ! $this->check_event_has_expired( $event ) && ! empty( $event->all_tickets_data ) ) {
                if(isset($event->all_bookings))
                {
                    $all_event_bookings = $this->get_event_booking_by_event_id( $event->id, true,$event->all_bookings );
                }
                else
                {
                    $all_event_bookings = $this->get_event_booking_by_event_id( $event->id, true );
                }
                $booked_tickets_data = $all_event_bookings['tickets'];
                foreach( $event->all_tickets_data as $ticket ) {
                    // ticket total capacity
                    $total_caps += $ticket->capacity;
                    // ticket booked capacity
                    $total_bookings += ( ! empty( $booked_tickets_data[$ticket->id] ) ? $booked_tickets_data[$ticket->id] : 0 );
                }
        }
        if( $total_caps > $total_bookings ) {
                return false;
        } else{
                return true;
        }
}


public function get_event_booking_by_event_id( $event_id, $ticket_qty = false ,$all_bookings = null) {
		$bookings = array();
		if( ! empty( $event_id ) ) {
			$booking_controller = new EventPrime_Bookings;
                        if(empty($all_bookings))
                        {
                            $all_bookings = $booking_controller->get_event_bookings_by_event_id( $event_id );
                        }
			if( ! empty( $all_bookings ) ) {
				foreach( $all_bookings as $booking ) {
					$booking_data = $booking_controller->load_booking_detail( $booking->ID, false );
					if( ! empty( $booking_data ) ) {
						$bookings['bookings'][] = $booking_data;
					}
				}
			}
			// if need ticket quantities then calculate booked quantity for each ticket.
			if( ! empty( $ticket_qty ) ) {
				$tickets = array();
				if( ! empty( $bookings['bookings'] ) && count( $bookings['bookings'] ) > 0 ) {
					foreach( $bookings['bookings'] as $booking ) {
						if ( isset( $booking->em_status ) && $booking->em_status !== 'cancelled' ) {
							if( isset( $booking->em_order_info ) ) {
								if( isset( $booking->em_order_info['tickets'] ) && ! empty( $booking->em_order_info['tickets'] ) ) {
									$booked_tickets = $booking->em_order_info['tickets'];
									foreach( $booked_tickets as $ticket ) {
										if( ! empty( $ticket->id ) && ! empty( $ticket->qty ) ) {
											if( isset( $tickets[$ticket->id] ) ) {
												$old_qty = $tickets[ $ticket->id ];
												$old_qty += $ticket->qty;
												$tickets[ $ticket->id ] = $old_qty;
											} else{
												$tickets[ $ticket->id ] = $ticket->qty;
											}
										}
									}
								} else if( isset( $booking->em_order_info['order_item_data'] ) && ! empty( $booking->em_order_info['order_item_data'] ) ) {
									$booked_tickets = $booking->em_order_info['order_item_data'];
									foreach( $booked_tickets as $ticket ) {
										if( ! empty( $ticket->id ) && ! empty( $ticket->qty ) ) {
											if( isset( $tickets[$ticket->id] ) ) {
												$old_qty = $tickets[ $ticket->id ];
												$old_qty += $ticket->qty;
												$tickets[ $ticket->id ] = $old_qty;
											} else{
												$tickets[ $ticket->id ] = $ticket->qty;
											}
										} else if( ! empty( $ticket->variation_id ) ) {
											if( isset( $tickets[$ticket->variation_id] ) ) {
												$old_qty = $tickets[ $ticket->variation_id ];
												$old_qty += $ticket->quantity;
												$tickets[ $ticket->variation_id ] = $old_qty;
											} else{
												$tickets[ $ticket->variation_id ] = $ticket->quantity;
											}
										}
									}
								}
							}
						}
					}
				}
				$bookings['tickets'] = $tickets;
			}
		}
		return $bookings;
	}

        public function get_offer_date( $offer, $event ) {
		$offer_date = '';
		if( ! empty( $offer ) ) {
			$offer_start_timestamp = $offer_end_timestamp = $book_start_date = $book_end_date = '';
			$booking_type = $offer->em_offer_start_booking_type;
			if( $booking_type == 'custom_date' ) {
				if( ! empty( $offer->em_offer_start_booking_date ) ) {
					// offer start
					$book_start_date = $offer->em_offer_start_booking_date;
					if( ! empty( $offer->em_offer_start_booking_time ) ) {
						$book_start_date .= ' ' . $offer->em_offer_start_booking_time;
						$offer_start_timestamp = $this->ep_datetime_to_timestamp( $book_start_date );
					} else{
						$offer_start_timestamp = $this->ep_date_to_timestamp( $book_start_date );
					}
					//offer end
					if( ! empty( $offer->em_offer_ends_booking_date ) ) {
						$book_end_date = $offer->em_offer_ends_booking_date;
						if( ! empty( $offer->em_offer_ends_booking_time ) ) {
							$book_end_date .= ' ' . $offer->em_offer_ends_booking_time;
							$offer_end_timestamp = $this->ep_datetime_to_timestamp( $book_end_date );
						} else{
							$offer_end_timestamp = $this->ep_date_to_timestamp( $book_end_date );
						}
					}
				}
				// if offer start and end date is same the show the time.
				if( ! empty( $offer->em_offer_start_booking_date ) ) {
					$offer_date = esc_html__( 'Offer Date:', 'eventprime-event-calendar-management' );
					$offer_start_timestamp = $this->ep_date_to_timestamp( $offer->em_offer_start_booking_date );
					$offer_date .= ' ' . $this->ep_timestamp_to_date( $offer_start_timestamp, 'd M', 1 );
					if( ! empty( $offer->em_offer_ends_booking_date ) ) {
						if( $offer->em_offer_start_booking_date == $offer->em_offer_ends_booking_date ) {
							if( ! empty( $offer->em_offer_start_booking_time ) && ! empty( $offer->em_offer_ends_booking_time ) ) {
								$offer_date .= ' ' . $offer->em_offer_start_booking_time . ' to ' . $offer->em_offer_ends_booking_time;
							}
						} else{
							if( ! empty( $offer_end_timestamp ) ) {
								$offer_date .= ' - ' . $this->ep_timestamp_to_date( $offer_end_timestamp, 'd M', 1 );
							}
						}
					}
				}
			} elseif( $booking_type == 'event_date' ) {
				$event_option = $offer->em_offer_start_booking_event_option;
				$offer_start_timestamp = '';
				if( $event_option == 'event_start' ) {
					$offer_start_timestamp = $event->em_start_date;
					if( ! empty( $event->em_start_time ) ) {
						$offer_start_timestamp = $this->ep_timestamp_to_date( $event->em_start_date );
						$offer_start_timestamp .= ' ' . $event->em_start_time;
						$offer_start_timestamp = $this->ep_datetime_to_timestamp( $offer_start_timestamp );
					}
				} elseif( $event_option == 'event_ends' ) {
					$offer_start_timestamp = $event->em_end_date;
					if( ! empty( $event->em_end_time ) ) {
						$offer_start_timestamp = $this->ep_timestamp_to_date( $event->em_end_date );
						$offer_start_timestamp .= ' ' . $event->em_end_time;
						$offer_start_timestamp = $this->ep_datetime_to_timestamp( $offer_start_timestamp );
					}
				} else{
					if( ! empty( $event_option ) ) {
						$em_event_add_more_dates = $event->em_event_add_more_dates;
						if( ! empty( $em_event_add_more_dates ) && count( $em_event_add_more_dates ) > 0 ) {
							foreach( $em_event_add_more_dates as $more_dates ) {
								if( $more_dates['uid'] == $event_option ) {
									$offer_start_timestamp = $more_dates['date'];
									if( ! empty( $more_dates['time'] ) ) {
										$date_more = $this->ep_timestamp_to_date( $more_dates['date'] );
										$date_more .= ' ' . $more_dates['time'];
										$offer_start_timestamp = $this->ep_datetime_to_timestamp( $date_more );
									}
									break;
								}
							}
						}
					}
				}

				// offer end
				$end_event_option = $offer->em_offer_ends_booking_event_option;
				$offer_end_timestamp = '';
				if( $end_event_option == 'event_start' ) {
					$offer_end_timestamp = $event->em_start_date;
					if( ! empty( $event->em_start_time ) ) {
						$offer_end_timestamp = $this->ep_timestamp_to_date( $event->em_start_date );
						$offer_end_timestamp .= ' ' . $event->em_start_time;
						$offer_end_timestamp = $this->ep_datetime_to_timestamp( $offer_end_timestamp );
					}
				} elseif( $end_event_option == 'event_ends' ) {
					$offer_end_timestamp = $event->em_end_date;
					if( ! empty( $event->em_end_time ) ) {
						$offer_end_timestamp = $this->ep_timestamp_to_date( $event->em_end_date );
						$offer_end_timestamp .= ' ' . $event->em_end_time;
						$offer_end_timestamp = $this->ep_datetime_to_timestamp( $offer_end_timestamp );
					}
				} else{
					if( ! empty( $end_event_option ) ) {
						$em_event_add_more_dates = $event->em_event_add_more_dates;
						if( ! empty( $em_event_add_more_dates ) && count( $em_event_add_more_dates ) > 0 ) {
							foreach( $em_event_add_more_dates as $more_dates ) {
								if( $more_dates['uid'] == $end_event_option ) {
									$offer_end_timestamp = $more_dates['date'];
									if( ! empty( $more_dates['time'] ) ) {
										$date_more = $this->ep_timestamp_to_date( $more_dates['date'] );
										$date_more .= ' ' . $more_dates['time'];
										$offer_end_timestamp = $this->ep_datetime_to_timestamp( $date_more );
									}
									break;
								}
							}
						}
					}
				}
				if( ! empty( $offer_start_timestamp ) ) {
					$offer_date = esc_html__( 'Offer Date:', 'eventprime-event-calendar-management' );
					$offer_date .= ' ' . $this->ep_timestamp_to_date( $offer_start_timestamp, 'd M', 1 );
				}
				if( ! empty( $offer_end_timestamp ) ) {
					$offer_date .= ' - ' . $this->ep_timestamp_to_date( $offer_end_timestamp, 'd M', 1 );
				}
			}
			
			if( empty( $offer_date ) && ! empty( $offer_start_timestamp ) ) {
				$offer_date = esc_html__( 'Offer Date:', 'eventprime-event-calendar-management' );
				$offer_date .= ' ' . $this->ep_timestamp_to_date( $offer_start_timestamp, 'd M', 1 );

				if( ! empty( $offer_end_timestamp ) ) {
					$offer_date .= ' - ' . $this->ep_timestamp_to_date( $offer_end_timestamp, 'd M', 1 );
				}
			}
		}
		return $offer_date;
	}

        public function get_events_post_data( $args = array(), $fetch_single_event_details = true) {
        $default = array(
            'post_status' => 'publish',
            'order'       => 'ASC',
            'post_type'   => 'em_event',
            'numberposts' => -1,
            'offset'      => 0,
            'meta_key'    => 'em_start_date_time',
            'orderby'     => 'meta_value',
        );
        $args = wp_parse_args( $args, $default );
        $posts = get_posts( $args );
        if( empty( $posts ) )
            return array();
        
        $events = array();
        foreach( $posts as $post ) {
            if( empty( $post ) || empty( $post->ID ) ) continue;
            
            if($fetch_single_event_details)
            {
                $event = $this->get_single_event( $post->ID, $post );
            }
            else
            {
                $event = $post;
            }
            
            if( ! empty( $event ) ) {
                $events[] = $event;
            }
        }
        $wp_query = new WP_Query( $args );
        $wp_query->posts = $events;
        return $wp_query;
    }

    public function check_for_ticket_visibility_old( $ticket, $event ) {
		$response = array( 'status' => false, 'message' => '', 'reason' => '' );
		if( ! empty( $ticket ) && ! empty( $event ) ) {
			if( ! empty( $ticket->visibility ) ) {
				$visibility = json_decode( $ticket->visibility );
                                //print_r($visibility);die;
				$em_tickets_user_visibility = $em_ticket_for_invalid_user = $em_tickets_visibility_time_restrictions = $em_ticket_visibility_user_roles = '';
				$em_tickets_user_visibility = $visibility->em_tickets_user_visibility;
				$em_ticket_for_invalid_user = $visibility->em_ticket_for_invalid_user;
				//$em_tickets_visibility_time_restrictions = $visibility->em_tickets_visibility_time_restrictions;
				$em_tickets_visibility_time_restrictions = 'always_visible';
				// if time is always visible
				if( $em_tickets_visibility_time_restrictions == 'always_visible' ) {
					if( $em_tickets_user_visibility == 'public' ) {
						$response['status'] = true;
					} elseif( $em_tickets_user_visibility == 'all_login' ) {
						if( is_user_logged_in() ) {
							$response['status'] = true;
						} else{
							if( $em_ticket_for_invalid_user == 'disabled' ) {
								$response = array( 'status' => true, 'message' => 'disabled', 'reason' => 'user_login' );
							} else{
								$response = array( 'status' => false, 'message' => 'require_login', 'reason' => '' );
							}
						}
					} elseif( $em_tickets_user_visibility == 'user_roles' ) {
						if( is_user_logged_in() ) {
							if( isset( $visibility->em_ticket_visibility_user_roles ) && ! empty( $visibility->em_ticket_visibility_user_roles ) ) {
								$em_ticket_visibility_user_roles = $visibility->em_ticket_visibility_user_roles;
								$user = wp_get_current_user();
								$roles = ( array ) $user->roles;
								if( in_array( 'administrator', $roles ) ) {
									$response['status'] = true;
								} else{
									if( ! empty( $em_ticket_visibility_user_roles ) ) {
										$found_role = 0;
										foreach( $em_ticket_visibility_user_roles as $ur ) {
											if( in_array( $ur, $roles ) ) {
												$response['status'] = true;
												$found_role = 1;
												break;
											}
										}
										if( empty( $found_role ) ) {
											$response = array( 'status' => false, 'message' => 'role_not_found', 'reason' => '' );
                                            if( $em_ticket_for_invalid_user == 'disabled' ) {
                                                $response = array( 'status' => true, 'message' => 'disabled', 'reason' => 'user_role' );
                                            }
										} else{
											$response['status'] = true;
										}
									} else{
										$response['status'] = true;
									}
								}
							} else{
								$response['status'] = true;
							}
						} else{
							if( $em_ticket_for_invalid_user == 'disabled' ) {
								$response = array( 'status' => true, 'message' => 'disabled', 'reason' => 'user_role' );
							}
							$response = array( 'status' => false, 'message' => 'require_login', 'reason' => '' );
						}
					}
                    $response = apply_filters( "ep_check_ticket_visibility_response", $response, $ticket, $event ); 
				}
			} else{
				$response['status'] = true;
			}
		}
		return $response;
	}
       
        public function check_for_ticket_visibility($ticket, $event) 
        {
            $response = ['status' => false, 'message' => '', 'reason' => ''];

            if (empty($ticket) || empty($event)) {
                return $response;
            }

            if (empty($ticket->visibility)) {
                $response['status'] = true;
                return $response;
            }

            $visibility = json_decode($ticket->visibility);
            $user_visibility = $visibility->em_tickets_user_visibility ?? 'public';
            $invalid_user_behavior = $visibility->em_ticket_for_invalid_user ?? '';
            $role_restrictions = $visibility->em_ticket_visibility_user_roles ?? [];

            // For now, time restriction is hardcoded
            $time_restriction = 'always_visible';

            if ($time_restriction !== 'always_visible') {
                return $response; // Future support for time-based restriction can go here
            }

            // --- Public visibility
            if ($user_visibility === 'public') {
                $response['status'] = true;

            // --- All logged-in users
            } elseif ($user_visibility === 'all_login') {
                if (is_user_logged_in()) {
                    $response['status'] = true;
                } else {
                    $response = ($invalid_user_behavior === 'disabled') ?
                        ['status' => true, 'message' => 'disabled', 'reason' => 'user_login'] :
                        ['status' => false, 'message' => 'require_login', 'reason' => ''];
                }

            // --- Restricted to specific roles
            } elseif ($user_visibility === 'user_roles') {
                if (is_user_logged_in()) {
                    $user = wp_get_current_user();
                    $roles = (array) $user->roles;

                    if (in_array('administrator', $roles)) {
                        $response['status'] = true;
                    } elseif (!empty($role_restrictions)) {
                        $intersect = array_intersect($role_restrictions, $roles);
                        if (!empty($intersect)) {
                            $response['status'] = true;
                        } else {
                            $response = ($invalid_user_behavior === 'disabled') ?
                                ['status' => true, 'message' => 'disabled', 'reason' => 'user_role'] :
                                ['status' => false, 'message' => 'role_not_found', 'reason' => ''];
                        }
                    } else {
                        $response['status'] = true; // No specific roles configured
                    }
                } else {
                    $response = ($invalid_user_behavior === 'disabled') ?
                        ['status' => true, 'message' => 'disabled', 'reason' => 'user_role'] :
                        ['status' => false, 'message' => 'require_login', 'reason' => ''];
                }
            }

            return apply_filters("ep_check_ticket_visibility_response", $response, $ticket, $event);
        }

        
        public function get_ticket_category_name( $category_id, $event ) {
		$cat_name = '';
		if( ! empty( $category_id ) && ! empty( $event ) ) {
			if( ! empty( $event->ticket_categories ) ) {
				foreach( $event->ticket_categories as $category ) {
					if( $category->id == $category_id ) {
						$cat_name = $category->name;
						break;
					}
				}
			}
		}
		return $cat_name;
	}
        
        public function check_for_ticket_available_for_booking( $ticket, $event ) {
        $booking_status = '';
        if( ! empty( $ticket ) ) {
            $current_time = $this->ep_get_current_timestamp();
            $min_start = $max_end = '';
            // start date
            if( ! empty( $ticket->booking_starts ) ) {
                $starts = json_decode( $ticket->booking_starts );
                if( ! empty( $starts->booking_type ) ) {
                    $booking_type = $starts->booking_type;
                    if( $booking_type == 'custom_date' ) {
                        if( ! empty( $starts->start_date ) ){
                            $book_start_date = $starts->start_date;
                            //print_r($book_start_date);die;
                            if( ! empty( $starts->start_time ) ) {
                                $book_start_date .= ' ' . $starts->start_time;
                            }
                            else
                            {
                                $book_start_date .= ' ' . '12:00 AM';
                                
                            } 
                            $book_start_timestamp = $this->ep_datetime_to_timestamp( $book_start_date );
                            //$book_start_timestamp = $this->ep_date_to_timestamp( $book_start_date );
                            
                            
                            if( empty( $min_start ) || $book_start_timestamp < $min_start ) {
                                $min_start = $book_start_timestamp;
                            }
                        }
                    } elseif( $booking_type == 'relative_date' ) {
                        if( isset( $starts->days ) && isset( $starts->days_option ) && isset( $starts->event_option ) ) {
                            $days         = $starts->days;
                            $days_option  = $starts->days_option;
                            $event_option = $starts->event_option;
                            $days_string  = ' days';
                            if( $days == 1 ) {
                                $days_string = ' day';
                            }
                            // + or - days
                            $days_icon = '- ';
                            if( $days_option == 'after' ) {
                                $days_icon = '+ ';
                            }
                            if( $event_option == 'event_start' ) {
                                $book_start_date = $this->ep_timestamp_to_date( $event->em_start_date );
                                if( ! empty( $event->em_start_time ) ) {
                                    $book_start_date .= ' ' . $event->em_start_time;
                                }
                                $book_start_timestamp = $this->ep_datetime_to_timestamp( $book_start_date );
                                $min_start = strtotime( $days_icon . $days . $days_string, $book_start_timestamp );
                            } elseif( $event_option == 'event_ends' ) {
                                $book_start_date = $this->ep_timestamp_to_date( $event->em_end_date );
                                if( ! empty( $event->em_end_time ) ) {
                                    $book_start_date .= ' ' . $event->em_end_time;
                                }
                                $book_start_timestamp = $this->ep_datetime_to_timestamp( $book_start_date );
                                $min_start = strtotime( $days_icon . $days . $days_string, $book_start_timestamp );
                            } else{
                                if( ! empty( $event_option ) ) {
                                    $em_event_add_more_dates = $event->em_event_add_more_dates;
                                    if( ! empty( $em_event_add_more_dates ) && count( $em_event_add_more_dates ) > 0 ) {
                                        foreach( $em_event_add_more_dates as $more_dates ) {
                                            if( $more_dates['uid'] == $event_option ) {
                                                $min_start = $more_dates['date'];
                                                if( ! empty( $more_dates['time'] ) ) {
                                                    $date_more = $this->ep_timestamp_to_date( $more_dates['date'] );
                                                    $date_more .= ' ' . $more_dates['time'];
                                                    $min_start = $this->ep_datetime_to_timestamp( $date_more );
                                                }
                                                break;
                                            }
                                        }
                                    }
                                    $min_start = strtotime( $days_icon . $days . $days_string, absint( $min_start ) );
                                }
                            }
                        }
                    } else{
                        if( ! empty( $starts->event_option ) ) {
                            $event_option = $starts->event_option;
                            if( $event_option == 'event_start' ) {
                                $book_start_timestamp = $event->em_start_date;
                                if( ! empty( $event->em_start_time ) ) {
                                    $book_start_timestamp = $this->ep_timestamp_to_date( $event->em_start_date );
                                    $book_start_timestamp .= ' ' . $event->em_start_time;
                                    $book_start_timestamp = $this->ep_datetime_to_timestamp( $book_start_timestamp );
                                }
                                $min_start = $book_start_timestamp;
                            } elseif( $event_option == 'event_ends' ) {
                                $book_start_timestamp = $event->em_end_date;
                                if( ! empty( $event->em_end_time ) ) {
                                    $book_start_timestamp = $this->ep_timestamp_to_date( $event->em_end_date );
                                    $book_start_timestamp .= ' ' . $event->em_end_time;
                                    $book_start_timestamp = $this->ep_datetime_to_timestamp( $book_start_timestamp );
                                }
                                $min_start = $book_start_timestamp;
                            } else{
                                if( ! empty( $event_option ) ) {
                                    $em_event_add_more_dates = $event->em_event_add_more_dates;
                                    if( ! empty( $em_event_add_more_dates ) && count( $em_event_add_more_dates ) > 0 ) {
                                        foreach( $em_event_add_more_dates as $more_dates ) {
                                            if( $more_dates['uid'] == $event_option ) {
                                                $min_start = $more_dates['date'];
                                                if( ! empty( $more_dates['time'] ) ) {
                                                    $date_more = $this->ep_timestamp_to_date( $more_dates['date'] );
                                                    $date_more .= ' ' . $more_dates['time'];
                                                    $min_start = $this->ep_datetime_to_timestamp( $date_more );
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // end date
            if( ! empty( $ticket->booking_ends ) ) {
                $ends = json_decode( $ticket->booking_ends );
                if( ! empty( $ends->booking_type ) ) {
                    $booking_type = $ends->booking_type;
                    if( $booking_type == 'custom_date' ) {
                        if( ! empty( $ends->end_date ) ){
                            $book_end_date = $ends->end_date;
                            if( ! empty( $ends->end_time ) ) {
                                $book_end_date .= ' ' . $ends->end_time;
                            }
                            else
                            {
                                $book_end_date .= ' ' . '11:59 PM' ;
                            }
                            
                            $book_end_timestamp = $this->ep_datetime_to_timestamp( $book_end_date );
                     
                            if( empty( $max_end ) || $book_end_timestamp < $max_end ) {
                                $max_end = $book_end_timestamp;
                            }
                        }
                        else
                        {
                            $book_end_timestamp = $this->ep_timestamp_to_date( $event->em_end_date );
                            $book_end_timestamp .= ' ' . $event->em_end_time;
                            $book_end_timestamp = $this->ep_datetime_to_timestamp( $book_end_timestamp );
                            $max_end = $book_end_timestamp;
                        }
                        
                    } elseif( $booking_type == 'relative_date' ) {
                        $days         = ( ! empty( $ends->days ) ? $ends->days : 1 );
                        $days_option  = ( ! empty( $ends->days_option ) ? $ends->days_option : 'before' );
                        $event_option = ( ! empty( $ends->event_option ) ? $ends->event_option : 'event_ends' );
                        $days_string  = ' days';
                        if( $days == 1 ) {
                            $days_string = ' day';
                        }
                        // + or - days
                        $days_icon = '- ';
                        if( $days_option == 'after' ) {
                            $days_icon = '+ ';
                        }
                        if( $event_option == 'event_start' ) {
                            $book_end_timestamp = $event->em_start_date;
                            if( ! empty( $event->em_start_time ) ) {
                                $book_end_timestamp = $this->ep_timestamp_to_date( $event->em_start_date );
                                $book_end_timestamp .= ' ' . $event->em_start_time;
                                $book_end_timestamp = $this->ep_datetime_to_timestamp( $book_end_timestamp );
                            }
                            $max_end = strtotime( $days_icon . $days . $days_string, $book_end_timestamp );
                        } elseif( $event_option == 'event_ends' ) {
                            $book_end_timestamp = $event->em_end_date;
                            if( ! empty( $event->em_end_time ) ) {
                                $book_end_timestamp = $this->ep_timestamp_to_date( $event->em_end_date );
                                $book_end_timestamp .= ' ' . $event->em_end_time;
                                $book_end_timestamp = $this->ep_datetime_to_timestamp( $book_end_timestamp );
                            }
                            $max_end = strtotime( $days_icon . $days . $days_string, $book_end_timestamp );
                        } else{
                            if( ! empty( $event_option ) ) {
                                $em_event_add_more_dates = $event->em_event_add_more_dates;
                                if( ! empty( $em_event_add_more_dates ) && count( $em_event_add_more_dates ) > 0 ) {
                                    foreach( $em_event_add_more_dates as $more_dates ) {
                                        if( $more_dates['uid'] == $event_option ) {
                                            $max_end = $more_dates['date'];
                                            if( ! empty( $more_dates['time'] ) ) {
                                                $date_more = $this->ep_timestamp_to_date( $more_dates['date'] );
                                                $date_more .= ' ' . $more_dates['time'];
                                                $max_end = $this->ep_datetime_to_timestamp( $date_more );
                                            }
                                            break;
                                        }
                                    }
                                }
                                $max_end = strtotime( $days_icon . $days . $days_string, absint( $max_end ) );
                            }
                        }
                    } else{
                        if( ! empty( $ends->event_option ) ) {
                            $event_option = $ends->event_option;
                            if( $event_option == 'event_start' ) {
                                $book_end_timestamp = $event->em_start_date;
                                if( ! empty( $event->em_start_time ) ) {
                                    $book_end_timestamp = $this->ep_timestamp_to_date( $event->em_start_date );
                                    $book_end_timestamp .= ' ' . $event->em_start_time;
                                    $book_end_timestamp = $this->ep_datetime_to_timestamp( $book_end_timestamp );
                                }
                                $max_end = $book_end_timestamp;
                            } elseif( $event_option == 'event_ends' ) {
                                $book_end_timestamp = $event->em_end_date;
                                if( ! empty( $event->em_end_time ) ) {
                                    $book_end_timestamp = $this->ep_timestamp_to_date( $event->em_end_date );
                                    $book_end_timestamp .= ' ' . $event->em_end_time;
                                    $book_end_timestamp = $this->ep_datetime_to_timestamp( $book_end_timestamp );
                                }
                                $max_end = $book_end_timestamp;
                            } else{
                                if( ! empty( $event_option ) ) {
                                    $em_event_add_more_dates = $event->em_event_add_more_dates;
                                    if( ! empty( $em_event_add_more_dates ) && count( $em_event_add_more_dates ) > 0 ) {
                                        foreach( $em_event_add_more_dates as $more_dates ) {
                                            if( $more_dates['uid'] == $event_option ) {
                                                $max_end = $more_dates['date'];
                                                if( ! empty( $more_dates['time'] ) ) {
                                                    $date_more = $this->ep_timestamp_to_date( $more_dates['date'] );
                                                    $date_more .= ' ' . $more_dates['time'];
                                                    $max_end = $this->ep_datetime_to_timestamp( $date_more );
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // set booking status now
            if( empty( $min_start ) && empty( $max_end ) ) {
                $booking_status = array( 'status' => 'on', 'message' => esc_html__( 'Booking On', 'eventprime-event-calendar-management' ) );
            } elseif( ! empty( $min_start ) && empty( $max_end ) ) {
                $booking_status = array( 'status' => 'not_started', 'message' => esc_html__( 'Tickets available from', 'eventprime-event-calendar-management' ) . ' ' . $this->ep_timestamp_to_date( $min_start, 'dS M', 1 ) );
            } elseif( $min_start >  $current_time ) {
                // if time on today then modify the string
                if( $min_start < strtotime('tomorrow') ) {
                    $booking_start_string = human_time_diff( $current_time, $min_start );
                    $booking_status = array( 'status' => 'not_started', 'message' => esc_html__( 'Tickets available after', 'eventprime-event-calendar-management' ) . ' ' . $booking_start_string );
                } else{
                    if( gmdate( 'Y-m-d', $min_start ) == gmdate( 'Y-m-d', $max_end ) ) {
                        $booking_status = array( 'status' => 'not_started', 'message' => esc_html__( 'Tickets available from', 'eventprime-event-calendar-management' ) . ' ' . $this->ep_timestamp_to_date( $min_start, 'dS M H:i A', 1 ) . ' ' . esc_html__( 'to', 'eventprime-event-calendar-management' ) . ' ' . $this->ep_timestamp_to_date( $max_end, 'h:i A', 1 ) );
                    } else{
                        $booking_status = array( 'status' => 'not_started', 'message' => esc_html__( 'Tickets available from', 'eventprime-event-calendar-management' ) . ' ' . $this->ep_timestamp_to_date( $min_start, 'dS M', 1 ) . ' ' . esc_html__( 'to', 'eventprime-event-calendar-management' ) . ' ' . $this->ep_timestamp_to_date( $max_end, 'dS M', 1 ) );
                    }
                }
            } elseif( ! empty( $max_end ) && $max_end <  $current_time ) {
                $booking_status = array( 'status' => 'off', 'message' => esc_html__( 'Tickets no longer available', 'eventprime-event-calendar-management' ), 'expire' => 1 );
            } else{
                $booking_status = array( 'status' => 'on', 'message' => esc_html__( 'Booking On', 'eventprime-event-calendar-management' ) );
            }
        }
        return $booking_status;
    }
    
    public function get_upcoming_events_for_organizer_new($organizer_id, $args = array()) 
    {
        $hide_past_events = $this->ep_get_global_settings('single_organizer_hide_past_events');
        $meta_query = array(
            'relation' => 'AND',
            array(
                'key'     => 'em_organizer',
                'value'   => '%' . $organizer_id . '%',
                'compare' => 'LIKE'
            ),
        );

        if (!empty($hide_past_events)) {
            $meta_query[] = array(
                'key'     => 'em_start_date_time',
                'value'   => current_time('mysql'),
                'compare' => '>=',
                'type'    => 'DATETIME'
            );
        }

        $filter = array(
            'meta_key'    => 'em_start_date_time',
            'orderby'     => 'meta_value',
            'order'       => 'ASC',
            'meta_query'  => $meta_query,
            'post_type'   => 'em_event',
            'post_status' => array('publish', 'future'),
            'numberposts' => -1,  // Ensure we get all posts
        );

        $args = wp_parse_args($args, $filter);
        $wp_query = new WP_Query($args);
        $wp_query->organizer_id = $organizer_id;

        return $wp_query;
    }

    
    public function get_upcoming_events_for_organizer( $organizer_id, $args = array() ) {
        if(!isset($args['hide_past_events'])){
            $hide_past_events = $this->ep_get_global_settings( 'single_organizer_hide_past_events' );
        }else{
            $hide_past_events = $args['hide_past_events'];
        }
        $past_events_meta_qry = '';
        if( ! empty( $hide_past_events ) ) {
            $past_events_meta_qry = array(
                'relation' => 'OR',
                array(
                    'key'     => 'em_start_date_time',
                    'value'   => current_time( 'timestamp' ),
                    'compare' => '>=',
                ),
            );
        }
        $filter = array(
            'meta_key'    => 'em_start_date_time',
            'orderby'     => 'meta_value',
            'numberposts' => -1,
            'order'       => 'ASC',
            'meta_query'  => array( 'relation' => 'AND',
                array(
                    array(
                        'key'     => 'em_organizer',
                        'value'   =>  maybe_serialize( strval ( $organizer_id ) ),
                        'compare' => 'LIKE'
                    ),
                    $past_events_meta_qry,
                )
            ),
            'post_type' => 'em_event'
        );

        $args = wp_parse_args($args, $filter);
        $wp_query = new WP_Query( $args );
        $wp_query->organizer_id = $organizer_id;
        return $wp_query;
    }
    
    public function get_upcoming_events_for_venue( $venue_id, $args = array() ) {
        // $hide_past_events = $this->ep_get_global_settings( 'single_venue_hide_past_events' );
        if(!isset($args['hide_past_events'])){
            $hide_past_events = $this->ep_get_global_settings( 'single_venue_hide_past_events' );
        }else{
            $hide_past_events = $args['hide_past_events'];
        }
        $past_events_meta_qry = '';
        if( ! empty( $hide_past_events ) ) {
            $past_events_meta_qry = array(
                'relation' => 'OR',
                array(
                    'key'     => 'em_start_date_time',
                    'value'   => current_time( 'timestamp' ),
                    'compare' => '>=',
                ),
            );
        }
        $filter = array(
            'meta_key'    => 'em_start_date_time',
            'orderby'     => 'meta_value',
            'numberposts' => -1,
            'order'       => 'ASC',
            'meta_query'  => array( 'relation' => 'AND',
                array(
                    array(
                        'key'     => 'em_venue',
                        'value'   =>  serialize(array($venue_id)),
                        'compare' => '='
                    ),
                    $past_events_meta_qry,
                )
            ),
            'post_type' => 'em_event'
        );

        $args = wp_parse_args( $args, $filter );
        $wp_query = new WP_Query( $args );
        $wp_query->venue_id = $venue_id;
        return $wp_query;
    }


    public function check_for_booking_status( $tickets, $event ) {
        $booking_status = '';
        if( ! empty( $tickets ) ) {
            // get all event bookings
            if(isset($event->all_bookings))
            {
                $all_event_bookings = $this->get_event_booking_by_event_id( $event->em_id, true,$event->all_bookings );
            }
            else
            {
                $all_event_bookings = $this->get_event_booking_by_event_id( $event->em_id, true );
            }
            $booked_tickets_data = $all_event_bookings['tickets'];
            $min_start = $max_end = ''; $price = $start_check_off = $total_caps = $total_bookings = 0;
            $buy_ticket_text = $this->ep_global_settings_button_title('Buy Tickets');

            $buy_ticket_text = apply_filters('ep_extend_buy_ticket_text', $buy_ticket_text, $tickets, $event);

            $booking_closed_text = $this->ep_global_settings_button_title('Booking closed');
            $booking_start_on_text = $this->ep_global_settings_button_title('Booking start on');
            $free_text = $this->ep_global_settings_button_title('Free');
            $sold_out = $this->ep_global_settings_button_title('Sold Out');
            foreach( $tickets as $ticket ) {
                // start date
                if( ! empty( $ticket->booking_starts ) ) {
                    $starts = json_decode( $ticket->booking_starts );
                    if( ! empty( $starts ) && isset( $starts->booking_type ) ) {
                        $booking_type = $starts->booking_type;
                        if( $booking_type == 'custom_date' ) {
                            if( empty( $start_check_off ) ) {
                                if( ! empty( $starts->start_date ) ){
                                    $book_start_date = $starts->start_date;
                                    if( ! empty( $starts->start_time ) ) {
                                        $book_start_date .= ' ' . $starts->start_time;
                                        $book_start_timestamp = $this->ep_datetime_to_timestamp( $book_start_date );
                                    }else{ // if no time then convert only date
                                        $book_start_timestamp = $this->ep_date_to_timestamp( $book_start_date );
                                    }
                                    if( empty( $min_start ) || $book_start_timestamp < $min_start ) {
                                        $min_start = $book_start_timestamp;
                                    }
                                } else{
                                    $start_check_off = 1;
                                    $min_start = '';
                                }
                            }
                        } else if( $booking_type == 'relative_date' ) {
                            $days = 1;
                            $start_booking_days_option = 'before';
                            $event_option = 'event_start';
                            if( ! empty( $starts->em_ticket_start_booking_days ) ) {
                                $days = $starts->em_ticket_start_booking_days;
                            } elseif(isset($starts->days)){
                                $days = $starts->days;
                            }
                            if( ! empty( $starts->em_ticket_start_booking_days_option ) ) {
                                $start_booking_days_option = $starts->em_ticket_start_booking_days_option;
                            } else{
                                $start_booking_days_option = $starts->days_option;
                            }
                            if( ! empty( $starts->em_ticket_start_booking_event_option ) ) {
                                $event_option = $starts->em_ticket_start_booking_event_option;
                            } else{
                                $event_option = $starts->event_option;
                            }
                            $days_string  = ' days';
                            if( $days == 1 ) {
                                $days_string = ' day';
                            }
                            // + or - days
                            $days_icon = '- ';
                            if( $start_booking_days_option == 'after' ) {
                                $days_icon = '+ ';
                            }
                            if( $event_option == 'event_start' ) {
                                $book_start_date = $event->em_start_date;
                                if( ! empty( $event->em_start_time ) ) {
                                    $book_start_date = $this->ep_timestamp_to_date( $event->em_start_date, 'Y-m-d', 1 );
                                    $book_start_date .= ' ' . $event->em_start_time;
                                    $book_start_date = $this->ep_datetime_to_timestamp( $book_start_date );
                                }
                                $book_start_timestamp = strtotime( $days_icon . $days . $days_string, $book_start_date );
                                if( empty( $min_start ) || $book_start_timestamp < $min_start ) {
                                    $min_start = $book_start_timestamp;
                                }
                            } elseif( $event_option == 'event_ends' ) {
                                $book_start_date = $event->em_end_date;
                                if( ! empty( $event->em_end_time ) ) {
                                    $book_start_date = $this->ep_timestamp_to_date( $event->em_end_date, 'Y-m-d', 1 );
                                    $book_start_date .= ' ' . $event->em_end_time;
                                    $book_start_date = $this->ep_datetime_to_timestamp( $book_start_date );
                                }
                                $book_start_timestamp = strtotime( $days_icon . $days . $days_string, $book_start_date );
                                if( empty( $min_start ) || $book_start_timestamp < $min_start ) {
                                    $min_start = $book_start_timestamp;
                                }
                            } else{
                                if( ! empty( $event_option ) ) {
                                    $em_event_add_more_dates = $event->em_event_add_more_dates;
                                    if( ! empty( $em_event_add_more_dates ) && count( $em_event_add_more_dates ) > 0 ) {
                                        foreach( $em_event_add_more_dates as $more_dates ) {
                                            if( $more_dates['uid'] == $event_option ) {
                                                $book_start_timestamp = $more_dates['date'];
                                                if( ! empty( $more_dates['time'] ) ) {
                                                    $date_more = $this->ep_timestamp_to_date( $more_dates['date'] );
                                                    $date_more .= ' ' . $more_dates['time'];
                                                    $book_start_timestamp = $this->ep_datetime_to_timestamp( $date_more );
                                                }
                                                if( empty( $min_start ) || $book_start_timestamp < $min_start ) {
                                                    $min_start = $book_start_timestamp;
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $event_option = $starts->event_option;
                            if( $event_option == 'event_start' ) {
                                $book_start_timestamp = $event->em_start_date;
                                if( ! empty( $event->em_start_time ) ) {
                                    $book_start_timestamp = $this->ep_timestamp_to_date( $event->em_start_date, 'Y-m-d', 1 );
                                    $book_start_timestamp .= ' ' . $event->em_start_time;
                                    $book_start_timestamp = $this->ep_datetime_to_timestamp( $book_start_timestamp );
                                }
                                if( empty( $min_start ) || $book_start_timestamp < $min_start ) {
                                    $min_start = $book_start_timestamp;
                                }
                            } elseif( $event_option == 'event_ends' ) {
                                $book_start_timestamp = $event->em_end_date;
                                if( ! empty( $event->em_end_time ) ) {
                                    $book_start_timestamp = $this->ep_timestamp_to_date( $event->em_end_date, 'Y-m-d', 1 );
                                    $book_start_timestamp .= ' ' . $event->em_end_time;
                                    $book_start_timestamp = $this->ep_datetime_to_timestamp( $book_start_timestamp );
                                }
                                if( empty( $min_start ) || $book_start_timestamp < $min_start ) {
                                    $min_start = $book_start_timestamp;
                                }
                            } else{
                                if( ! empty( $event_option ) ) {
                                    $em_event_add_more_dates = $event->em_event_add_more_dates;
                                    if( ! empty( $em_event_add_more_dates ) && count( $em_event_add_more_dates ) > 0 ) {
                                        foreach( $em_event_add_more_dates as $more_dates ) {
                                            if( $more_dates['uid'] == $event_option ) {
                                                $book_start_timestamp = $more_dates['date'];
                                                if( ! empty( $more_dates['time'] ) ) {
                                                    $date_more = $this->ep_timestamp_to_date( $more_dates['date'] );
                                                    $date_more .= ' ' . $more_dates['time'];
                                                    $book_start_timestamp = $this->ep_datetime_to_timestamp( $date_more );
                                                }
                                                if( empty( $min_start ) || $book_start_timestamp < $min_start ) {
                                                    $min_start = $book_start_timestamp;
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                // end date
                if( ! empty( $ticket->booking_ends ) ) {
                    $ends = json_decode( $ticket->booking_ends );
                    if( ! empty( $ends ) && isset( $ends->booking_type ) ) {
                        $booking_type = $ends->booking_type;
                        if( $booking_type == 'custom_date' ) {
                            if( ! empty( $ends->end_date ) ) {
                                $book_end_date = $ends->end_date;
                                if( ! empty( $ends->end_time ) ) {
                                    $book_end_date .= ' ' . $ends->end_time;
                                    $book_end_timestamp = $this->ep_datetime_to_timestamp( $book_end_date );
                                } else{ // if no time then convert only date
                                    $book_end_timestamp = $this->ep_date_to_timestamp( $book_end_date );
                                }
                                if( empty( $max_end ) || $book_end_timestamp < $max_end ) {
                                    $max_end = $book_end_timestamp;
                                }
                            }
                        } else if( $booking_type == 'relative_date' ) {
                            $days = 1;
                            $end_booking_days_option = 'before';
                            $event_option = 'event_ends';
                            if( ! empty( $ends->em_ticket_end_booking_days ) ) {
                                $days = $ends->em_ticket_end_booking_days;
                            } elseif(isset($ends->days)){
                                $days = $ends->days;
                            }
                            
                            if( ! empty( $ends->em_ticket_end_booking_days_option ) ) {
                                $end_booking_days_option = $ends->em_ticket_end_booking_days_option;
                            } else if( ! empty( $ends->days_option ) ) {
                                $end_booking_days_option = $ends->days_option;
                            }
                            if( ! empty( $ends->em_ticket_end_booking_event_option ) ) {
                                $event_option = $ends->em_ticket_end_booking_event_option;
                            } else if( ! empty( $ends->event_option ) ) {
                                $event_option = $ends->event_option;
                            }
                            $days_string  = ' days';
                            if( $days == 1 ) {
                                $days_string = ' day';
                            }
                            // + or - days
                            $days_icon = '- ';
                            if( $end_booking_days_option == 'after' ) {
                                $days_icon = '+ ';
                            }
                            if( $event_option == 'event_ends' ) {
                                $book_end_timestamp = $event->em_end_date;
                                if( ! empty( $event->em_end_time ) ) {
                                    $book_end_timestamp = $this->ep_timestamp_to_date( $event->em_end_date, 'Y-m-d', 1 );
                                    $book_end_timestamp .= ' ' . $event->em_end_time;
                                    $book_end_timestamp = $this->ep_datetime_to_timestamp( $book_end_timestamp );
                                }
                                $book_end_timestamp = strtotime( $days_icon . $days . $days_string, $book_end_timestamp );
                                if( empty( $max_end ) || $book_end_timestamp < $max_end ) {
                                    $max_end = $book_end_timestamp;
                                }
                            } elseif( $event_option == 'event_ends' ) {
                                $book_end_timestamp = $event->em_end_date;
                                if( ! empty( $event->em_end_time ) ) {
                                    $book_end_timestamp = $this->ep_timestamp_to_date( $event->em_end_date, 'Y-m-d', 1 );
                                    $book_end_timestamp .= ' ' . $event->em_end_time;
                                    $book_end_timestamp = $this->ep_datetime_to_timestamp( $book_end_timestamp );
                                }
                                $book_end_timestamp = strtotime( $days_icon . $days . $days_string, $book_end_timestamp );
                                if( empty( $max_end ) || $book_end_timestamp < $max_end ) {
                                    $max_end = $book_end_timestamp;
                                }
                            } else{
                                if( ! empty( $event_option ) && ! empty( $event->em_event_add_more_dates ) ) {
                                    $em_event_add_more_dates = $event->em_event_add_more_dates;
                                    if( ! empty( $em_event_add_more_dates ) && count( $em_event_add_more_dates ) > 0 ) {
                                        foreach( $em_event_add_more_dates as $more_dates ) {
                                            if( $more_dates['uid'] == $event_option ) {
                                                $book_end_timestamp = $more_dates['date'];
                                                if( ! empty( $more_dates['time'] ) ) {
                                                    $date_more = $this->ep_timestamp_to_date( $more_dates['date'] );
                                                    $date_more .= ' ' . $more_dates['time'];
                                                    $book_end_timestamp = $this->ep_datetime_to_timestamp( $date_more );
                                                }
                                                if( empty( $max_end ) || $book_end_timestamp < $max_end ) {
                                                    $max_end = $book_end_timestamp;
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $event_option = $ends->event_option;
                            if( $event_option == 'event_start' ) {
                                $book_end_timestamp = $event->em_start_date;
                                if( ! empty( $event->em_start_time ) ) {
                                    $book_end_timestamp = $this->ep_timestamp_to_date( $event->em_start_date, 'Y-m-d', 1 );
                                    $book_end_timestamp .= ' ' . $event->em_start_time;
                                    $book_end_timestamp = $this->ep_datetime_to_timestamp( $book_end_timestamp );
                                }
                                if( empty( $max_end ) || $book_end_timestamp < $max_end ) {
                                    $max_end = $book_end_timestamp;
                                }
                            } elseif( $event_option == 'event_ends' ) {
                                $book_end_timestamp = $event->em_end_date;
                                if( ! empty( $event->em_end_time ) ) {
                                    $book_end_timestamp = $this->ep_timestamp_to_date( $event->em_end_date, 'Y-m-d', 1 );
                                    $book_end_timestamp .= ' ' . $event->em_end_time;
                                    $book_end_timestamp = $this->ep_datetime_to_timestamp( $book_end_timestamp );
                                }
                                if( empty( $max_end ) || $book_end_timestamp < $max_end ) {
                                    $max_end = $book_end_timestamp;
                                }
                            } else{
                                if( ! empty( $event_option ) ) {
                                    $em_event_add_more_dates = $event->em_event_add_more_dates;
                                    if( ! empty( $em_event_add_more_dates ) && count( $em_event_add_more_dates ) > 0 ) {
                                        foreach( $em_event_add_more_dates as $more_dates ) {
                                            if( $more_dates['uid'] == $event_option ) {
                                                $book_end_timestamp = $more_dates['date'];
                                                if( ! empty( $more_dates['time'] ) ) {
                                                    $date_more = $this->ep_timestamp_to_date( $more_dates['date'] );
                                                    $date_more .= ' ' . $more_dates['time'];
                                                    $book_end_timestamp = $this->ep_datetime_to_timestamp( $date_more );
                                                }
                                                if( empty( $max_end ) || $book_end_timestamp < $max_end ) {
                                                    $max_end = $book_end_timestamp;
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                // price
                if( ! empty( $ticket->price ) ) {
                    $price = $ticket->price;
                }
                // ticket total capacity
                $total_caps += $ticket->capacity;
                // ticket booked capacity
                $total_bookings += ( ! empty( $booked_tickets_data[$ticket->id] ) ? $booked_tickets_data[$ticket->id] : 0 );
            }
            if( $total_caps > $total_bookings ) {
                // set booking status now
                if( empty( $min_start ) && empty( $max_end ) ) {
                    if( $price > 0 ) {
                        $booking_status = array( 'status' => 'on', 'message' => $buy_ticket_text );
                    } else{
                        $booking_status = array( 'status' => 'on', 'message' => $free_text );
                    }
                } else{
                    $current_time = $this->ep_get_current_timestamp();
                    if( empty( $min_start ) && empty( $start_check_off ) ) {
                        if( $max_end <  $current_time ) {
                            $booking_status = array( 'status' => 'on', 'message' => $buy_ticket_text );
                        } else{
                            $booking_status = array( 'status' => 'off', 'message' => $booking_closed_text );
                        }
                    } elseif( $min_start <=  $current_time ) {
                        $booking_status = array( 'status' => 'on', 'message' => $buy_ticket_text );
                    } elseif( $min_start >  $current_time ) {
                        $booking_status = array( 'status' => 'not_started', 'message' => $booking_start_on_text . ' ' . $this->ep_timestamp_to_date( $min_start, 'd M', 1 ) );
                    } elseif( $max_end <  $current_time ) {
                        $booking_status = array( 'status' => 'off', 'message' => $booking_closed_text );
                    } elseif( $current_time >= $min_start && $current_time <= $max_end ) {
                        if( $price > 0 ) {
                            $booking_status = array( 'status' => 'on', 'message' => $buy_ticket_text );
                        } else{
                            $booking_status = array( 'status' => 'on', 'message' => $free_text );
                        }
                    }
                }
            } else{
                $booking_status = array( 'status' => 'off', 'message' => $sold_out );
            }
        }
        return $booking_status;
    }

    public function get_upcoming_event_by_venue_id( $venue_id, $exclude = array() ) {
		$args = $events_data = array();
		if( ! empty( $exclude ) ) {
			$args['post__not_in'] = $exclude;
			$args['numberposts']  = 5;
                        $args['post_parent'] = 0;
                        $args['posts_per_page'] = 5;
                        
		}
		$event_qry = $this->get_upcoming_events_for_venue( $venue_id, $args );
		$events = $event_qry->posts;
		if( !empty( $events ) && count( $events ) > 0 ) {
			foreach( $events as $post ) {
				$event = $this->get_single_event( $post->ID, $post );
				if( ! empty( $event ) ) {
					$events_data[] = $event;
				}
			}
		}
		return $events_data;
	}


    public function create_organizer($data = array()){
        $term_id = 0;
        if(!empty($data)){
            $organizer_name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
            $description = isset($data['description']) ? sanitize_text_field($data['description']) : '';
            $org = wp_insert_term(
                $organizer_name,
                'em_event_organizer',
                array('description'=>$description)
            );

            if( is_wp_error($org) ) {
                return $term_id;
            }

            $term_id = isset($org['term_id']) ? $org['term_id'] : 0;
            
            $em_organizer_phones = isset($data['em_organizer_phones']) && !empty($data['em_organizer_phones']) ? $data['em_organizer_phones']: array();
            $em_organizer_emails = isset($data['em_organizer_emails']) && !empty($data['em_organizer_emails']) ? $data['em_organizer_emails'] : array();
            $em_organizer_websites = isset($data['em_organizer_websites']) && !empty($data['em_organizer_websites']) ? $data['em_organizer_websites']: array();
            $em_image_id = isset($data['em_image_id']) ? $data['em_image_id']: '';
            $em_is_featured = isset($data['em_is_featured']) ? sanitize_text_field($data['em_is_featured']): 0;
            $em_social_links = isset($data['em_social_links']) ? $data['em_social_links'] : array('facebook','instagram','linkedin','twitter');
            
            update_term_meta( $term_id, 'em_organizer_phones', $em_organizer_phones );
            update_term_meta( $term_id, 'em_organizer_emails', $em_organizer_emails );
            update_term_meta( $term_id, 'em_organizer_websites', $em_organizer_websites );
            update_term_meta( $term_id, 'em_image_id', $em_image_id );
            update_term_meta( $term_id, 'em_is_featured', $em_is_featured );
            update_term_meta( $term_id, 'em_social_links', $em_social_links );
            
            if ( isset($data['em_status']) && !empty($data['em_status'])) {
                update_term_meta( $term_id, 'em_status', 1 );
            }
            do_action('em_after_organizer_created', $term_id, $data);
        }
        return $term_id;
    }

    public function insert_performer_post_data($post_data){
        $post_id = 0;
        if(!empty($post_data)){
            $title = isset($post_data['name']) ? sanitize_text_field($post_data['name']) : '';
            $description = isset($post_data['description']) ? $post_data['description'] : '';
            $status = isset($post_data['status']) ? $post_data['status'] : 'publish';
            $post_id = wp_insert_post(array (
                'post_type' => 'em_performer',
                'post_title' => $title,
                'post_content' => $description,
                'post_status' => $status
            ));
            
        }
        
        if($post_id){
            $em_type = isset($post_data['em_type']) ? $post_data['em_type'] : '';
            $em_role = isset($post_data['em_role']) ? $post_data['em_role'] : '';
            $em_display_front = isset($post_data['em_display_front']) && !empty($post_data['em_display_front']) ? 1 : 0;
            $em_is_featured = isset($post_data['em_is_featured']) && !empty($post_data['em_is_featured']) ? 1 : 0;
            $em_social_links = isset($post_data['em_social_links']) ? $post_data['em_social_links'] : array();
            $em_performer_phones = isset($post_data['em_performer_phones']) ? $post_data['em_performer_phones'] : array();
            $em_performer_emails = isset($post_data['em_performer_emails']) ? $post_data['em_performer_emails'] : array();
            $em_performer_websites = isset($post_data['em_performer_websites']) ? $post_data['em_performer_websites'] : array();
            $em_performer_gallery = isset($post_data['em_performer_gallery']) ? $post_data['em_performer_gallery'] : array();
            
            $thumbnail_id = isset( $post_data['thumbnail'] ) ? $post_data['thumbnail'] : 0;
            set_post_thumbnail($post_id, $thumbnail_id);
            
            update_post_meta( $post_id, 'em_type', $em_type );
            update_post_meta( $post_id, 'em_role', $em_role );
            update_post_meta( $post_id, 'em_display_front', $em_display_front );
            update_post_meta( $post_id, 'em_is_featured', $em_is_featured );
            update_post_meta( $post_id, 'em_social_links', $em_social_links );
            update_post_meta( $post_id, 'em_performer_phones', $em_performer_phones );
            update_post_meta( $post_id, 'em_performer_emails', $em_performer_emails );
            update_post_meta( $post_id, 'em_performer_websites', $em_performer_websites );
            update_post_meta( $post_id, 'em_performer_gallery', $em_performer_gallery );
            if ( isset($post_data['em_status']) && !empty($post_data['em_type']) ) {
                update_post_meta( $post_id, 'em_status', 1 );
            }
        }
        return $post_id;
    }
    
	/**
        * Check if any payment gateway enabled
        */
       public function em_is_payment_gateway_enabled() {
           $is_payment_enabled = false;
           $payment_processor = array( 'paypal' => $this->ep_get_global_settings( 'paypal_processor' ) );
           $payment_processor = apply_filters( 'ep_is_payment_gayway_enabled', $payment_processor );
           if( ! empty( $payment_processor ) ) {
               foreach( $payment_processor as $payment ) {
                   if( ! empty( $payment ) ) {
                       $is_payment_enabled = true;
                       break;
                   }
               }
           }
           return $is_payment_enabled;
       }

       /**
        * Get booking ticket prices
        * 
        * @param object $booking_tickets Booking Tickets.
        * 
        * @return float Tickets total price.
        */
       public function ep_get_booking_tickets_total_price( $booking_tickets ) {
           $price = 0;
           if( ! empty( $booking_tickets ) && count( $booking_tickets ) > 0 ) {
               foreach( $booking_tickets as $ticket ) {
                   $tic_price = $ticket->price;
                   $tic_qty = $ticket->qty;
                   $price += $tic_price * $tic_qty;
                   if( isset( $ticket->offer ) && ! empty( $ticket->offer ) ) {
                       //$price += $ticket->offer;
                   }
               }
           }
           return $this->ep_price_with_position( $price );
       }
       
       /**
        * Check if guest booking enabled
        */
       public function ep_enabled_guest_booking() {
           $enabled_guest_booking = 0;
           $activate_extensions = $this->ep_get_activate_extensions();
           if( in_array( 'Eventprime_Guest_Booking', $activate_extensions ) ) {
               $allow_guest_bookings = $this->ep_get_global_settings( 'allow_guest_bookings' );
               if( ! empty( $allow_guest_bookings ) ) {
                   $enabled_guest_booking = 1;
               }
           }
           return $enabled_guest_booking;
       }
       
       /**
        * Check if woocommerce integration enabled
        */
       public function ep_enabled_woocommerce_integration() {
           $enabled_woocommerce_integration = 0;
           $activate_extensions = $this->ep_get_activate_extensions();
           if( in_array( 'Eventprime_Woocommerce_Integration', $activate_extensions ) ) {
               $allow_woocommerce_integration = $this->ep_get_global_settings( 'allow_woocommerce_integration' );
               if( ! empty( $allow_woocommerce_integration ) ) {
                   $enabled_woocommerce_integration = 1;
               }
           }
           return $enabled_woocommerce_integration;
       }
       
       public function ep_enabled_reg_captcha(){
            $enabled = 0;
            if( $this->ep_enabled_guest_booking() && $this->ep_get_global_settings('checkout_reg_google_recaptcha') == 1 && ! empty( $this->ep_get_global_settings('google_recaptcha_site_key') ) ) {
                $enabled = 1;
            }
            return $enabled;
        }
        
        public function ep_enabled_woocommerce_checkout(){
            $enabled_woocommerce_checkout = 0;
            $activate_extensions = $this->ep_get_activate_extensions();
            if( in_array( 'woocommerce_checkout', $activate_extensions ) ) {
                $enabled_woocommerce_checkout = $this->ep_get_global_settings( 'enable_woocommerce_checkout' );
                if( ! empty( $enabled_woocommerce_checkout ) ) {
                    $enabled_woocommerce_checkout = 1;
                }
            }
            return $enabled_woocommerce_checkout;
        }
        
        public function ep_get_current_user_profile_name() {
            $current_user = wp_get_current_user();
            $name = '';
            if( ! empty( $current_user->user_firstname ) ) {
                $name .= $current_user->user_firstname;
            }
            if( ! empty( $current_user->user_lastname ) ) {
                $name .= ' ' . $current_user->user_lastname;
            }
            if( empty( $name ) && ! empty( $current_user->display_name ) ) {
                $name = $current_user->display_name;
            }
            return $name;
        }


    
    
        /*
     * Create new venue
     * @param (array) $data 
     * return $id
     */
    public function create_venue($data = array()){
        $term_id = 0;
        if(!empty($data)){
            $location_name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
            $venue = wp_insert_term(
                $location_name,
                'em_venue',
                array()
            );
            $term_id = isset($venue['term_id']) ? $venue['term_id'] : 0;
            
            $em_type = isset($data['em_type']) ? sanitize_text_field($data['em_type']): '';
            $em_address = isset($data['em_address']) ? sanitize_text_field($data['em_address']) : '';
            $em_lat = isset($data['em_lat']) ? sanitize_text_field($data['em_lat']): '';
            $em_lng = isset($data['em_lng']) ? sanitize_text_field($data['em_lng']): '';
            $em_locality = isset($data['em_locality']) ? sanitize_text_field($data['em_locality']): '';
            $em_state = isset($data['em_state']) ? sanitize_text_field($data['em_state']): '';
            $em_country = isset($data['em_country']) ? sanitize_text_field($data['em_country']): '';
            $em_postal_code = isset($data['em_postal_code']) ? sanitize_text_field($data['em_postal_code']): '';
            $em_zoom_level = isset($data['em_zoom_level']) ? sanitize_text_field($data['em_zoom_level']): '';
            $em_display_address_on_frontend = isset($data['em_display_address_on_frontend']) & !empty($data['em_display_address_on_frontend']) ? 1: 0;
            
            $em_established = isset($data['em_established']) ? sanitize_text_field($data['em_established']): '';
            $em_seating_organizer = isset($data['em_seating_organizer']) ? sanitize_text_field($data['em_seating_organizer']): '';
            $em_facebook_page = isset($data['em_facebook_page']) ? sanitize_text_field($data['em_facebook_page']): '';
            $em_instagram_page = isset($data['em_instagram_page']) ? sanitize_text_field($data['em_instagram_page']): '';
            
            $em_image_ids = isset($data['em_image_id']) ? $data['em_image_id']: '';
            $em_is_featured = isset($data['em_is_featured']) ? sanitize_text_field($data['em_is_featured']): '';
            
            update_term_meta( $term_id, 'em_address', $em_address );
            update_term_meta( $term_id, 'em_lat', $em_lat );
            update_term_meta( $term_id, 'em_lng', $em_lng );
            update_term_meta( $term_id, 'em_locality', $em_locality );
            update_term_meta( $term_id, 'em_state', $em_state );
            update_term_meta( $term_id, 'em_country', $em_country );
            update_term_meta( $term_id, 'em_postal_code', $em_postal_code );
            update_term_meta( $term_id, 'em_zoom_level', $em_zoom_level );
            update_term_meta( $term_id, 'em_display_address_on_frontend', $em_display_address_on_frontend );
            update_term_meta( $term_id, 'em_established', $em_established );
            update_term_meta( $term_id, 'em_type', $em_type );
            update_term_meta( $term_id, 'em_seating_organizer', $em_seating_organizer );
            update_term_meta( $term_id, 'em_facebook_page', $em_facebook_page );
            update_term_meta( $term_id, 'em_instagram_page', $em_instagram_page );
            update_term_meta( $term_id, 'em_gallery_images', array($em_image_ids) );
            update_term_meta( $term_id, 'em_is_featured', $em_is_featured );
            
            if ( isset($data['em_status']) && !empty($data['em_status'])) {
                update_term_meta( $term_id, 'em_status', 1 );
            }
        }
        return $term_id;
    }
    
        public function create_event_types($data = array()){
        $term_id = 0;
        if(!empty($data)){
            $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
            $description = isset($data['description']) ? $data['description'] : '';
            $types = wp_insert_term(
                    $name,
                    'em_event_type',
                    array('description'=>$description)
                    );
            $term_id = isset($types['term_id']) ? $types['term_id'] : 0;
            
            $em_color = isset($data['em_color']) && !empty($data['em_color']) ? sanitize_text_field($data['em_color']) : 'FF5599';
            $em_type_text_color = isset($data['em_type_text_color']) && !empty($data['em_type_text_color']) ? sanitize_text_field($data['em_type_text_color']) : '#43CDFF';
            $em_image_id = isset($data['em_image_id']) ? $data['em_image_id']: '';
            $em_is_featured = isset($data['em_is_featured']) ? sanitize_text_field($data['em_is_featured']): 0;
            $em_age_group = isset($data['em_age_group']) ? $data['em_age_group'] : 'all';
            
            update_term_meta( $term_id, 'em_color', $em_color );
	    update_term_meta( $term_id, 'em_type_text_color', $em_type_text_color );
	    update_term_meta( $term_id, 'em_image_id', $em_image_id );
	    update_term_meta( $term_id, 'em_is_featured', $em_is_featured );
            update_term_meta( $term_id, 'age_group', $em_age_group);
            if ( isset($data['em_status']) && !empty($data['em_status'])) {
                update_term_meta( $term_id, 'em_status', 1 );
            }
        }
        return $term_id;
    }
    
    public function insert_event_post_data($post_data){
        $metaboxes_controllers = new EP_DBhandler(); 
        $post_id = 0;
        if(!empty($post_data)){
            $title = isset($post_data['name']) ? sanitize_text_field($post_data['name']) : '';
            $description = isset($post_data['description']) ? $post_data['description'] : '';
            $status = isset($post_data['status']) ? $post_data['status'] : 'publish';
            $post_id = wp_insert_post(array (
                'post_type' => 'em_event',
                'post_title' => $title,
                'post_content' => $description,
                'post_status' => $status
            ));
        }
        if($post_id){
            $post = get_post($post_id);
            $em_name = isset($post_data['name']) ? sanitize_text_field($post_data['name']) : '';
            $em_start_date = isset($post_data['em_start_date']) ? $post_data['em_start_date'] : '';
            $em_end_date = isset($post_data['em_end_date']) ? $post_data['em_end_date'] : '';
            $em_start_time = isset($post_data['em_start_time']) ? $post_data['em_start_time'] : '';
            $em_end_time = isset($post_data['em_end_time']) ? $post_data['em_end_time'] : '';
            $em_all_day = isset($post_data['em_all_day']) ? $post_data['em_all_day'] : 0;
            $em_ticket_price = isset($post_data['em_ticket_price']) ? sanitize_text_field($post_data['em_ticket_price']) : '';
            $em_venue = isset($post_data['em_venue']) ? $post_data['em_venue'] : '';
            $em_performer = isset($post_data['em_performer']) ? $post_data['em_performer'] : array();
            $em_organizer = isset($post_data['em_organizer']) ? $post_data['em_organizer'] : array();
            $em_event_type = isset($post_data['em_event_type']) ? $post_data['em_event_type'] : '';
            $em_enable_booking = isset($post_data['em_enable_booking']) ? $post_data['em_enable_booking'] : 'bookings_off';
            $em_custom_link = isset($post_data['em_custom_link']) ? $post_data['em_custom_link'] : '';
            $em_custom_meta = isset($post_data['em_custom_meta']) ? $post_data['em_custom_meta'] : array();
            $em_hide_start_time = isset( $post_data['em_hide_event_start_time'] ) ? 1 : 0;
            $em_hide_event_start_date = isset( $post_data['em_hide_event_start_date'] ) ? 1 : 0;
            $em_hide_event_end_time = isset( $post_data['em_hide_event_end_time'] ) ? 1 : 0;
            $em_hide_end_date = isset( $post_data['em_hide_end_date'] ) ? 1 : 0;
            update_post_meta($post_id, 'em_id', $post_id );
            update_post_meta($post_id, 'em_name', $em_name);
            update_post_meta($post_id, 'em_start_date', $em_start_date);
            update_post_meta($post_id, 'em_end_date', $em_end_date);
            update_post_meta($post_id, 'em_end_time', $em_end_time);
            update_post_meta($post_id, 'em_start_time', $em_start_time);
            update_post_meta($post_id, 'em_all_day', $em_all_day); 
            update_post_meta($post_id, 'em_ticket_price', $em_ticket_price);
            update_post_meta($post_id, 'em_fixed_event_price', $em_ticket_price);
            update_post_meta($post_id, 'em_venue', $em_venue);
            wp_set_post_terms($post_id, $em_venue , 'em_venue', false);
            update_post_meta($post_id, 'em_event_type', $em_event_type);
            wp_set_post_terms($post_id, $em_event_type , 'em_event_type', false);
            update_post_meta($post_id, 'em_organizer', $em_organizer);
            wp_set_post_terms($post_id, $em_organizer , 'em_event_organizer', false);
            update_post_meta($post_id, 'em_performer', $em_performer);  
            wp_set_post_terms($post_id, $em_performer , 'em_performer', false);
            update_post_meta($post_id, 'em_enable_booking', $em_enable_booking);
            update_post_meta($post_id, 'em_custom_link', $em_custom_link);
            update_post_meta($post_id, 'em_custom_meta', $em_custom_meta); 
            update_post_meta( $post_id, 'em_hide_event_start_time', $em_hide_start_time );
            update_post_meta( $post_id, 'em_hide_event_start_date', $em_hide_event_start_date );
            update_post_meta( $post_id, 'em_hide_event_end_time', $em_hide_event_end_time );
            update_post_meta( $post_id, 'em_hide_end_date', $em_hide_end_date );  
            // handel recurring events request
            if( isset( $post_data['em_enable_recurrence'] ) && $post_data['em_enable_recurrence'] == 1 ) {
                update_post_meta( $post_id, 'em_enable_recurrence', 1 );
                $em_recurrence_step = (isset( $post_data['em_recurrence_step'] ) && !empty( $post_data['em_recurrence_step'] ) ) ? absint( $post_data['em_recurrence_step'] ) : 1;
                update_post_meta( $post_id, 'em_recurrence_step', $em_recurrence_step );
                if( isset( $post_data['em_recurrence_interval'] ) && ! empty( $post_data['em_recurrence_interval'] ) ) { 
                    $em_recurrence_interval = sanitize_text_field( $post_data['em_recurrence_interval'] );
                    update_post_meta( $post_id, 'em_recurrence_interval', $em_recurrence_interval );
                    // first delete old child events
                    $metaboxes_controllers->ep_delete_child_events( $post_id );
                    $em_recurrence_ends = (isset( $post_data['em_recurrence_ends'] ) && !empty( $post_data['em_recurrence_ends'] ) ) ? $post_data['em_recurrence_ends'] : 'after';
                    update_post_meta( $post_id, 'em_recurrence_ends', $em_recurrence_ends );
                    $last_date_on = $stop_after = $recurrence_limit_timestamp = $start_date_only = '';
                    if( $em_recurrence_ends == 'on' ) {
                        $last_date_on = $this->ep_date_to_timestamp( sanitize_text_field( $post_data['em_recurrence_limit'] ) );
                        update_post_meta( $post_id, 'em_recurrence_limit', $last_date_on );
                        $recurrence_limit = new DateTime( '@' . $last_date_on );
                        //$recurrence_limit->setTime( 0,0,0,0 );
                        $recurrence_limit_timestamp = $recurrence_limit->getTimestamp();
                        // update start date format
                        $start_date_only = new DateTime( '@' . $em_start_date );
                        $start_date_only->setTime( 0,0,0,0 );
                    }
                    if( $em_recurrence_ends == 'after' ) {
                        $stop_after = absint( $post_data['em_recurrence_occurrence_time'] );
                        update_post_meta( $post_id, 'em_recurrence_occurrence_time', $stop_after );
                    }
                    $data = array( 
                        'start_date' => $em_start_date,
                        'start_time' => $em_start_time,
                        'end_date' => $em_end_date,
                        'end_time' => $em_end_time,
                        'recurrence_step' => $em_recurrence_step,
                        'recurrence_interval' => $em_recurrence_interval,
                        'last_date_on' => $last_date_on,
                        'stop_after' => $stop_after,
                        'recurrence_limit_timestamp' => $recurrence_limit_timestamp,
                        'start_date_only' => $start_date_only,
                        'em_add_slug_in_event_title' => isset($post_data['em_add_slug_in_event_title']) ? $post_data['em_add_slug_in_event_title'] : '',
                        'em_event_slug_type_options' => isset($post_data['em_event_slug_type_options']) ? $post_data['em_event_slug_type_options'] : '',
                        'em_recurring_events_slug_format' => isset($post_data['em_recurring_events_slug_format']) ? $post_data['em_recurring_events_slug_format'] : '',
                        'em_selected_weekly_day' => isset($post_data['em_selected_weekly_day0']) ? $post_data['em_selected_weekly_day'] : '',
                        'em_recurrence_monthly_weekno' => isset($post_data['em_recurrence_monthly_weekno']) ? $post_data['em_recurrence_monthly_weekno'] : '',
                        'em_recurrence_monthly_fullweekday' => isset($post_data['em_recurrence_monthly_fullweekday']) ? $post_data['em_recurrence_monthly_fullweekday'] : '',
                        'em_recurrence_monthly_day' => isset($post_data['em_recurrence_monthly_day']) ? $post_data['em_recurrence_monthly_day'] : '',
                        'em_recurrence_yearly_weekno' => isset($post_data['em_recurrence_yearly_weekno']) ? $post_data['em_recurrence_yearly_weekno'] : '',
                        'em_recurrence_yearly_fullweekday' => isset($post_data['em_recurrence_yearly_fullweekday']) ? $post_data['em_recurrence_yearly_fullweekday'] : '',
                        'em_recurrence_yearly_monthday' => isset($post_data['em_recurrence_yearly_monthday']) ? $post_data['em_recurrence_yearly_monthday'] : '',
                        'em_recurrence_yearly_day' => isset($post_data['em_recurrence_yearly_day']) ? $post_data['em_recurrence_yearly_day'] : '',
                        'em_recurrence_advanced_dates' => isset($post_data['em_recurrence_advanced_dates']) ? wp_json_encode($post_data['em_recurrence_advanced_dates']) : '',
                        'em_recurrence_selected_custom_dates' => isset($post_data['em_recurrence_selected_custom_dates']) ? $post_data['em_recurrence_selected_custom_dates'] : '',
                    );                         
                    switch( $em_recurrence_interval ) {
                        case 'daily':
                            $metaboxes_controllers->ep_event_daily_recurrence( $post, $data, $post_data );
                            break;
                        case 'weekly':
                            $metaboxes_controllers->ep_event_weekly_recurrence( $post, $data, $post_data );
                            break;
                        case 'monthly':
                            $metaboxes_controllers->ep_event_monthly_recurrence( $post, $data, $post_data );
                            break;
                        case 'yearly':
                            $metaboxes_controllers->ep_event_yearly_recurrence( $post, $data, $post_data );
                            break;
                        case 'advanced':
                            $metaboxes_controllers->ep_event_advanced_recurrence( $post, $data, $post_data );
                            break;
                        case 'custom_dates':
                            $metaboxes_controllers->ep_event_custom_dates_recurrence( $post, $data, $post_data );
                            break;
                    }
                }
            }
        }
        return $post_id;
    }
    
    /**
	 * Get venue by venue id
	 * 
	 * @param int $venue_id Venue ID.
	 * 
	 * @return object Venue.
	 */
	public function ep_get_venue_by_id( $venue_id ) {
		$venue = new stdClass();
		if( ! empty( $venue_id ) ) {
			$single_venue  = $this->get_single_venue( $venue_id );
			if( ! empty( $single_venue ) ) {
				$venue = $single_venue;
			}
		}
		return $venue;
	} 
	
	
	/**
	 * Get total attendee number by booking id
	 * 
	 * @param int $booking_id Booking ID
	 * 
	 * @return int Attendee number
	 */
	public function get_total_attendee_number_by_booking_id( $booking_id ) {
		$total_attendee = 0;
		if( ! empty( $booking_id ) ) {
			$booking_controller = new EventPrime_Bookings();
			$booking_data = $booking_controller->load_booking_detail( $booking_id, false );
			if( ! empty( $booking_data ) ) {
				if( isset( $booking_data->em_order_info['tickets'] ) && ! empty( $booking_data->em_order_info['tickets'] ) ) {
					$booked_tickets = $booking_data->em_order_info['tickets'];
					foreach( $booked_tickets as $ticket ) {
						if( ! empty( $ticket->id ) && ! empty( $ticket->qty ) ) {
							$total_attendee += $ticket->qty;
						}
					}
				} else if( isset( $booking_data->em_order_info['order_item_data'] ) && ! empty( $booking_data->em_order_info['order_item_data'] ) ) {
					$booked_tickets = $booking_data->em_order_info['order_item_data'];
					foreach( $booked_tickets as $ticket ) {
						if( isset( $ticket->quantity ) ) {
							$total_attendee += $ticket->quantity;
						} else if( isset( $ticket->qty ) ) {
							$total_attendee += $ticket->qty;
						}
					}
				}
			}
		}
		return $total_attendee;
	}
	
    public function ep_set_mail_content_type_html() {
        $content_type = 'text/html';
        return $content_type;     
    }

    public function ep_set_mail_from( $original_email_address = null ) {
        $ep_admin_email_from = $this->ep_get_global_settings('ep_admin_email_from');
        if( ! empty( $ep_admin_email_from ) ){
            $original_email_address = $ep_admin_email_from;
        } else{
            $original_email_address = get_option('admin_email');
        }
        return $original_email_address;
    }
    
    public function ep_set_mail_from_name() {
        return get_option('blogname');
    }

    // get timezone offset
    public function ep_gmt_offset_seconds( $date = NULL ) {
        if( $date ) {
            $timezone = new DateTimeZone( $this->ep_get_user_timezone() );
            // Convert to Date
            if( is_numeric( $date ) ) $date = gmdate( 'Y-m-d', $date );

            $target = new DateTime( $date, $timezone );
            return $timezone->getOffset( $target );
        } else{
            $gmt_offset = get_option('gmt_offset');
            $seconds = $gmt_offset * HOUR_IN_SECONDS;

            return ( substr( $gmt_offset, 0, 1 ) == '-' ? '' : '+' ) . $seconds;
        }
    }

    /*
    * Get site Domain
    */
    public function ep_get_site_domain() {
        $url = get_site_url();

        $url = str_replace('http://', '', $url);
        $url = str_replace('https://', '', $url);
        $url = str_replace('ftp://', '', $url);
        $url = str_replace('svn://', '', $url);
        $url = str_replace('www.', '', $url);

        $ex = explode('/', $url);
        $ex2 = explode('?', $ex[0]);

        return $ex2[0];
    }
    
    /**
     * Count the total event_types
     */
    public function get_event_types_count( $args = array(), $ep_search = '', $featured = 0 , $popular = 0 ) {
        $defaults = array( 
            'hide_empty' => false ,
        );

        if( $featured == 1 ){
            $args['post_status'] = 'publish';
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                   'key'       => 'em_is_featured',
                   'value'     => 1,
                   'compare'   => '='
                ),
                array(
                   'key'       => 'em_is_featured',
                   'value'     => true,
                   'compare'   => '='
                )
            );           
        }  

        if( $popular == 1 )
        return 1;
        
        $ep_search = ( $ep_search != 'false' ) ? $ep_search : '';
        $args['name__like'] = $ep_search;

        $args  = wp_parse_args( $args, $defaults );
        $terms = get_terms( 'em_event_type', $args );
        if( ! empty( $terms ) && is_array( $terms )){
            return count($terms);
        }else{
            return 1;
        }
    }
    
    /**
     * Get event_types data
     */

    //depricated.
    public function get_event_types_data( $args = array() ) {
        $defaults = array( 
            'hide_empty' => false ,
        );
        $args        = wp_parse_args( $args, $defaults );
        $terms       = get_terms('em_event_type', $args );
        $event_types = array();
        if( empty( $terms ) || is_wp_error( $terms ) ){
           return $event_types;
        }
        foreach( $terms as $term ){
            $event_type = $this->get_single_event_type( $term->term_id, $term );
            if( ! empty( $event_type ) ) {
                $event_types[] = $event_type;
            }
        }

        $wp_query        = new WP_Term_Query( $args );
        $wp_query->terms = $event_types;
        return $wp_query;
    }
    

    
    
    
    /*
     * Ajax loadmore
     */
    public function get_event_types_loadmore(){
        $event_types_data = array();
        $settings                            = new Eventprime_Global_Settings;
        $db_handler = new EP_DBhandler;
        $ep_requests = new EP_Requests;
        $event_types_settings                = $settings->ep_get_settings( 'event_types' );

        $event_types_data['display_style']   = isset( $_POST['display_style'] ) ? $_POST["display_style"] : $event_types_settings->type_display_view;
        $event_types_data['limit']           = isset( $_POST['limit'] ) ? ( empty($_POST["limit"] ) ? 10 : $_POST["limit"]) : ( empty( $event_types_settings->type_limit ) ? 10 : $event_types_settings->type_limit );
        $event_types_data['cols']            = isset( $_POST['cols'] ) ? $this->ep_check_column_size( $_POST['cols'] ) : $this->ep_check_column_size( $event_types_settings->type_no_of_columns );
        $event_types_data['column']          = isset( $_POST["limit"] ) ? $_POST["limit"] : $event_types_settings->type_no_of_columns;
        $event_types_data['load_more']       = isset( $_POST['load_more'] ) ? $_POST['load_more'] : $event_types_settings->type_load_more;
        $event_types_data['enable_search']   = isset( $_POST['search'] ) ? $_POST['search'] : $event_types_settings->type_search;
        $event_types_data['featured']        = isset( $_POST["featured"] ) ? $_POST["featured"] : 0;
        $event_types_data['popular']         = isset( $_POST["popular"] ) ? $_POST["popular"] : 0;
        $event_types_data['orderby']         = isset( $_POST["orderby"] ) ? $_POST["orderby"] : 'term_id';
        $event_types_data['order']         = isset( $_POST["order"] ) ? $_POST["order"] : 'desc';
        $event_types_data['box_color'] = '';
        if( $event_types_data['display_style'] == 'box' || $event_types_data['display_style'] == 'colored_grid' ) {
            $event_types_data['box_color'] =  $event_types_settings->type_box_color;
        }

        // Set query arguments
        //print_r($event_types_settings->type_box_color);
        $paged = ( $_POST['paged'] ) ? (int)$_POST['paged'] : 1;
        $paged += 1;
        $event_types_data['paged'] = $paged;
        $ep_search = isset( $_POST['ep_search'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';
        $pargs = $event_types_data;
        $pargs['name__like'] = $ep_search;
                        
        if ( $event_types_data['featured'] == 1 && ( $event_types_data['popular'] == 1 ) ) {
            $pargs['meta_query'] = array(
                'relation' => 'AND',
                array(
                   'key'       => 'em_is_featured',
                   'value'     => 1,
                   'compare'   => '='
                )
            );
            $pargs['orderby'] ='count';
            $pargs['order'] ='DESC';
        }
        
        if( $event_types_data['featured'] == 1 && ( $event_types_data['popular'] == 0 || $event_types_data['popular'] == '' ) ){ 
            $pargs['meta_query'] = array(
                'relation' => 'AND',
                array(
                   'key'       => 'em_is_featured',
                   'value'     => 1,
                   'compare'   => '='
                )
                
            );
        }
        // Get popular event types
        if( $event_types_data['popular'] == 1 && ( $event_types_data['featured'] == 0 || $event_types_data['featured'] == '' ) ){
            
            $pargs['orderby'] ='count';
            $pargs['order'] ='DESC';
        }
        
        $terms_per_page = $event_types_data['limit'];
        $offset = ( $paged - 1 ) * $terms_per_page;
        $event_types_data['colorbox_start'] = absint($offset%4) + 1;
       //print_r($pargs);
        $event_types = $db_handler->ep_get_taxonomy_terms_with_pagination('em_event_type', $paged, $terms_per_page, $pargs);
        $event_types_data['event_types_count'] = $event_types['total_terms'];
        $event_types_data['event_types'] = $event_types['terms'];
       
        ob_start();
        $themepath = $ep_requests->eventprime_get_ep_theme('event-types-list-load-tpl');
        
        $this->ep_get_template_part( $themepath, null, (object)$event_types_data );
        $data['html'] = ob_get_clean();
        $data['paged'] = $paged;
        return $data;
    }
    
    public function get_event_performer_loadmore(){
        $ep_requests = new EP_Requests;
        $settings                           = new Eventprime_Global_Settings;
        $performers_settings                = $settings->ep_get_settings( 'performers' );
        $performers_data                    = array();
        $performers_data['display_style']   = isset( $_POST['display_style'] ) ? $_POST["display_style"] : $performers_settings->performer_display_view;
        $performers_data['limit']           = isset( $_POST['limit'] ) ? (empty($_POST["limit"]) ? 10 : $_POST["limit"]) : (empty($performers_settings->performer_limit) ? 10 : $performers_settings->performer_limit );
        $performers_data['cols']            = isset( $_POST['cols'] ) ? $this->ep_check_column_size( $_POST['cols'] ) : $this->ep_check_column_size( $performers_settings->performer_no_of_columns );
        $performers_data['load_more']       = isset( $_POST['load_more'] ) ? $_POST['load_more'] : $performers_settings->performer_load_more;
        $performers_data['enable_search']   = isset( $_POST['search'] ) ? $_POST['search'] : $performers_settings->performer_search;
        $performers_data['featured']        = isset( $_POST["featured"] ) ? $_POST["featured"] : 0;
        $performers_data['popular']         = isset( $_POST["popular"] ) ? $_POST["popular"] : 0;
        $performers_data['orderby']         = isset( $_POST["orderby"] ) ? $_POST["orderby"] : 'date';
        $performers_data['box_color'] = '';
        if( $performers_data['display_style'] == 'box' || $performers_data['display_style'] == 'colored_grid' ) {
            $performers_data['box_color'] = ( isset( $_POST["box_color"] ) && ! empty( $_POST["box_color"] ) ) ? explode( ',', $_POST["box_color"] ) : $performers_settings->performer_box_color;
        }
        // set query arguments
        $paged     = ( $_POST['paged'] ) ? $_POST['paged'] : 1;
        $paged++;
        $ep_search = isset( $_POST['ep_search'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';

        $offset = (int)( $paged-1 ) * (int)$performers_data['limit'];
        $pargs     = array(
            'orderby'        => $performers_data['orderby'],
            'posts_per_page' => $performers_data['limit'],
            'offset'         => $offset,
            'paged'          => $paged,
            's'              => $ep_search,
        );

        // print_r($pargs); die;

        
        // if featured enabled then get featured performers
        if( $performers_data['featured'] == 1 && $performers_data['popular'] == 0) {
            $pargs['meta_query'] = array(
                'relation'     => 'AND',
                array(
                    'key'      => 'em_display_front',
                    'value'    => 1,
                    'compare'  => '='
                    ),
                array(
                    'key'   => 'em_is_featured',
                    'value' => 1,
                    'compare'=> '='
                )
            );
        }
        $performers_data['performers'] = $this->get_performers_post_data( $pargs );

        if( $performers_data['popular'] == 1 && $performers_data['featured'] == 0) {
            $performers_data['performers'] = $this->get_popular_event_performers($performers_data['limit'], 0, $offset);
        }

        if( $performers_data['popular'] == 1 && $performers_data['featured'] == 1) {
            $performers_data['performers'] = $this->get_popular_event_performers($performers_data['limit'], $performers_data['featured'], $offset);
        }
        
        $terms_per_page = $performers_data['limit'];
        $offset = ( $paged - 1 ) * $terms_per_page;
        $performers_data['colorbox_start'] = absint($offset%4) + 1;
        ob_start();
        wp_enqueue_style(
            'ep-performer-views-css',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-views.css',
            false, EVENTPRIME_VERSION
        );
        $themepath = $ep_requests->eventprime_get_ep_theme('performers-list-load-tpl');
        
        $this->ep_get_template_part($themepath, null, (object)$performers_data );
        $data['html'] = ob_get_clean();
        $data['paged'] = $paged;
        return $data;
    }
    
    
    /**
     * Get post data
     */
    public function get_performers_post_data( $args = array() ) {
        $default = array(
            'orderby'          => 'title',
            'numberposts'      => -1,
            'offset'           => 0,     
            'order'            => 'ASC',
            'post_type'        => 'em_performer',
            'post_status'      => 'publish',
            'meta_query'       => array(    
                'relation'     => 'AND',
                array(
                  'relation'     => 'OR',
                    array(
                        'key'      => 'em_display_front',
                        'value'    => 1,
                        'compare'  => '='
                    ),
                    array(
                        'key'      => 'em_display_front',
                        'value'    => 'true',
                        'compare'  => '='
                    ),  
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key'      => 'em_status',
                        'compare'  => 'NOT EXISTS'
                    ),
                    array(
                        'key'      => 'em_status',
                        'value'    => 0,
                        'compare'  => '!='
                    ),
                ),
            )
        );
        $default = apply_filters( 'ep_performers_render_argument', $default, $args );
        $args = wp_parse_args( $args, $default );
        $posts = get_posts( $args );
        if( empty( $posts ) )
           return array();
       
        $performers = array();
        foreach( $posts as $post ) {
            $performer = $this->get_single_performer( $post->ID, $post );
            if( ! empty( $performer ) ) {
                $performers[] = $performer;
            }
        }

        $wp_query = new WP_Query( $args );
        $wp_query->posts = $performers;

        return $wp_query;
    }
    
    /**
     * Count the total venues
     */
    public function get_venues_count( $args = array() ) {
        $defaults = array( 
            'hide_empty' => false ,
            'meta_query' => array(
                'relation'=>'OR',
                array(
                  'key'     => 'em_status',
                  'value'   => 0,
                  'compare' => '!='
                ),
                array(
                  'key'     => 'em_status',
                  'compare' => 'NOT EXISTS'
                )
            )
        );
        $args  = wp_parse_args( $args, $defaults );
        $terms = get_terms( 'em_venue', $args );
        if( ! empty( $terms ) && is_array( $terms )){
            return count($terms);
        }else{
            return 1;
        }
    }
    
    /**
     * Get venues data
     */

    public function get_venues_data( $args = array() ) {
        $defaults = array( 
            'hide_empty' => false ,
            'meta_query' => array(
                'relation'=>'OR',
                array(
                  'key'     => 'em_status',
                  'value'   => 0,
                  'compare' => '!='
                ),
                array(
                  'key'     => 'em_status',
                  'compare' => 'NOT EXISTS'
                )
            )
        );
        $args        = wp_parse_args( $args, $defaults );
        $terms       = get_terms( 'em_venue', $args );
        $venues = array();
        if( empty( $terms ) || is_wp_error( $terms ) ){
           return $venues;
        }
        foreach( $terms as $term ){
            $venue = $this->get_single_venue( $term->term_id, $term );
            if( ! empty( $venue ) ) {
                $venues[] = $venue;
            }
        }

        $wp_query        = new WP_Term_Query( $args );
        $wp_query->terms = $venues;
        return $wp_query;
    }

    
     /*
     * Load more 
     */
    
    public function get_event_venue_loadmore(){
        $db_handler = new EP_DBhandler;
        $venues_data = array();
        $settings                     = new Eventprime_Global_Settings();
        $venues_settings              = $settings->ep_get_settings( 'venues' );
        
        $venues_data['display_style'] = isset( $_POST['display_style'] ) ? $_POST["display_style"] : $venues_settings->venue_display_view;
        $venues_data['limit']         = isset( $_POST['limit'] ) ? ( empty($_POST["limit"] ) ? 10 : $_POST["limit"]) : ( empty( $venues_settings->venue_limit ) ? 10 : $venues_settings->venue_limit );
        $venues_data['cols']          = isset( $_POST['cols'] ) ? $this->ep_check_column_size( $_POST['cols'] ) : $this->ep_check_column_size( $venues_settings->venue_no_of_columns );
        $venues_data['load_more']     = isset( $_POST['load_more'] ) ? $_POST['load_more'] : $venues_settings->venue_load_more;
        $venues_data['enable_search'] = isset( $_POST['search'] ) ? $_POST['search'] : $venues_settings->venue_search;
        $venues_data['featured']      = isset( $_POST["featured"] ) ? $_POST["featured"] : 0;
        $venues_data['popular']       = isset( $_POST["popular"] ) ? $_POST["popular"] : 0;
        $venues_data['orderby']         = isset( $_POST["orderby"] ) ? $_POST["orderby"] : 'term_id';
        $venues_data['order']         = isset( $_POST["order"] ) ? $_POST["order"] : 'desc';
        $venues_data['box_color'] = '';
        if( $venues_data['display_style'] == 'box' || $venues_data['display_style'] == 'colored_grid' ) {
            $venues_data['box_color'] = $venues_settings->venue_box_color;
        }

        // Set query arguments
        $paged = ( $_POST['paged'] ) ? (int)$_POST['paged'] : 1;
        $paged += 1;
        $venues_data['paged'] = $paged;
        $ep_search = isset( $_POST['ep_search'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';
        $pargs = $venues_data;
        $pargs['name__like'] = $ep_search;
        
        if ( $venues_data['featured'] == 1 && ( $venues_data['popular'] == 1 ) ) {
            $pargs['meta_query'] = array(
                'relation' => 'AND',
                array(
                   'key'       => 'em_is_featured',
                   'value'     => 1,
                   'compare'   => '='
                )
            );
            $pargs['orderby'] ='count';
            $pargs['order'] ='DESC';
        }
        
        // Get featured event venues
        if( $venues_data['featured'] == 1 && ( $venues_data['popular'] == 0 || $venues_data['popular'] == '' ) ){ 
            $pargs['meta_query'] = array(
                'relation' => 'AND',
                array(
                   'key'       => 'em_is_featured',
                   'value'     => 1,
                   'compare'   => '='
                )
                
            );
        }
        // Get popular event types
        if( $venues_data['popular'] == 1 && ( $venues_data['featured'] == 0 || $venues_data['featured'] == '' ) ){
            $pargs['orderby'] ='count';
            $pargs['order'] ='DESC';
        }
        
        $terms_per_page = $venues_data['limit'];
        $offset = ( $paged - 1 ) * $terms_per_page;
        $venues_data['colorbox_start'] = absint($offset%4) + 1;
       //print_r($pargs);
        $venues = $db_handler->ep_get_taxonomy_terms_with_pagination('em_venue', $paged, $terms_per_page, $pargs);
        $venues_data['venue_count'] = $venues['total_terms'];
        $venues_data['venues'] = $venues['terms'];
       
        ob_start();
        wp_enqueue_style(
            'ep-venue-views-css',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-views.css',
            false, EVENTPRIME_VERSION
        );
        $ep_requests = new EP_Requests;
        $themepath = $ep_requests->eventprime_get_ep_theme('venues-list-load-tpl');
        $this->ep_get_template_part( $themepath, null, (object)$venues_data );
	$data['html'] = ob_get_clean();
        $data['paged'] = $paged;
        return $data;
    }

    public function get_event_organizer_loadmore(){
        $db_handler = new EP_DBhandler;
        $organizers_data = array();
        $settings                           = new Eventprime_Global_Settings();
        $organizers_settings                = $settings->ep_get_settings( 'organizers' );
        
        $organizers_data['display_style']   = isset( $_POST['display_style'] ) ? $_POST["display_style"] : $organizers_settings->organizer_display_view;
        $organizers_data['limit']           = isset( $_POST['limit'] ) ? (empty($_POST["limit"]) ? 10 : $_POST["limit"]) : (empty($organizers_settings->organizer_limit) ? 10 : $organizers_settings->organizer_limit );
        $organizers_data['cols']            = isset( $_POST['cols'] ) ? $this->ep_check_column_size( $_POST['cols'] ) : $this->ep_check_column_size( $organizers_settings->organizer_no_of_columns );
        $organizers_data['load_more']       = isset( $_POST['load_more'] ) ? $_POST['load_more'] : $organizers_settings->organizer_load_more;
        $organizers_data['enable_search']   = isset( $_POST['search'] ) ? $_POST['search'] : $organizers_settings->organizer_search;
        $organizers_data['featured']        = isset( $_POST["featured"] ) ? $_POST["featured"] : 0;
        $organizers_data['popular']         = isset( $_POST["popular"] ) ? $_POST["popular"] : 0;
        $organizers_data['orderby']         = isset( $_POST["orderby"] ) ? $_POST["orderby"] : 'term_id';
        $organizers_data['order']         = isset( $_POST["order"] ) ? $_POST["order"] : 'desc';
        $organizers_data['box_color'] = '';
        if( $organizers_data['display_style'] == 'box' || $organizers_data['display_style'] == 'colored_grid' ) {
            $organizers_data['box_color'] = $organizers_settings->organizer_box_color;
        }
        
        $paged = ( $_POST['paged'] ) ? (int)$_POST['paged'] : 1;
        $paged += 1;
        $event_types_data['paged'] = $paged;
        $ep_search = isset( $_POST['ep_search'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';
        $pargs = $organizers_data;
        $pargs['name__like'] = $ep_search;

        if ( $organizers_data['featured'] == 1 && ( $organizers_data['popular'] == 1 ) ) {
            $pargs['meta_query'] = array(
                'relation' => 'AND',
                array(
                   'key'       => 'em_is_featured',
                   'value'     => 1,
                   'compare'   => '='
                )
            );
            $pargs['orderby'] ='count';
            $pargs['order'] ='DESC';
        }

        // get featured event organizers
        if( $organizers_data['featured'] == 1 && ( $organizers_data['popular'] == 0 || $organizers_data['popular'] == '' ) ){ 
            $pargs['meta_query'] = array(
                'relation' => 'AND',
                array(
                   'key'       => 'em_is_featured',
                   'value'     => 1,
                   'compare'   => '='
                )
            );
        }
        
        // Get popular event types
        if( $organizers_data['popular'] == 1 && ( $organizers_data['featured'] == 0 || $organizers_data['featured'] == '' ) ){
            $pargs['orderby'] ='count';
            $pargs['order'] ='DESC';
        }
                        
        $terms_per_page = $organizers_data['limit'];
        $offset = ( $paged - 1 ) * $terms_per_page;
        $organizers_data['colorbox_start'] = absint($offset%4) + 1;

        $organizers = $db_handler->ep_get_taxonomy_terms_with_pagination('em_event_organizer', $paged, $terms_per_page, $pargs);
        $organizers_data['organizers_count'] = $pargs['total_count'] = $organizers['total_terms'];
        $organizers_data['organizers'] = $organizers['terms'];

        ob_start();
        $ep_requests = new EP_Requests;
        $themepath = $ep_requests->eventprime_get_ep_theme('organizers-list-load-tpl');
        
        $this->ep_get_template_part( $themepath, null, (object)$organizers_data );
        $data['html'] = ob_get_clean();
        $data['paged'] = $paged;
        return $data;
    }
    
    /**
     * Get organizers data
     */

    public function get_organizers_data( $args = array() ) {
        $defaults = array( 
            'hide_empty' => false ,
            'meta_query' => array(
                'relation'=>'OR',
                array(
                    'key'     => 'em_status',
                    'value'   => 0,
                    'compare' => '!='
                ),
                array(
                    'key'     => 'em_status',
                    'compare' => 'NOT EXISTS'
                )
            )
        );
        $args       = wp_parse_args( $args, $defaults );
        $terms      = get_terms( 'em_event_organizer', $args );
        $organizers = array();
        if( empty( $terms ) || is_wp_error( $terms ) ){
           return $organizers;
        }
        foreach( $terms as $term ){
            $organizer = $this->get_single_organizer( $term->term_id, $term );
            if( ! empty( $organizer ) ) {
                $organizers[] = $organizer;
            }
        }

        $wp_query        = new WP_Term_Query( $args );
        $wp_query->terms = $organizers;
        return $wp_query;
    }

    /**
     * Count the total organizers
     */
    public function get_organizers_count( $args = array(), $ep_search = '', $featured = 0 , $popular = 0 ) {
        $defaults = array( 
            'hide_empty' => false ,
            'meta_query' => array(
                'relation'=>'OR',
                array(
                    'key'     => 'em_status',
                    'value'   => 0,
                    'compare' => '!='
                ),
                array(
                    'key'     => 'em_status',
                    'compare' => 'NOT EXISTS'
                )
            )
        );
        if( $featured == 1 ){
            $args['post_status'] = 'publish';
            $args['meta_query'] = array(
                'relation'=>'AND',
                array(
                    'relation' => 'OR',
                    array(
                       'key'       => 'em_is_featured',
                       'value'     => 1,
                       'compare'   => '='
                    ),
                    array(
                       'key'       => 'em_is_featured',
                       'value'     => true,
                       'compare'   => '='
                    )
                ),
                array(
                    'relation'=>'OR',
                    array(
                        'key'     => 'em_status',
                        'value'   => 0,
                        'compare' => '!='
                    ),
                    array(
                        'key'     => 'em_status',
                        'compare' => 'NOT EXISTS'
                    )
                )
            );           
        }  

        if( $popular == 1 ) return 1;
        
        $ep_search = ( $ep_search != 'false' ) ? $ep_search : '';
        $args['name__like'] = $ep_search;

        $args  = wp_parse_args( $args, $defaults );
        $terms = get_terms( 'em_event_organizer', $args );
        if( ! empty( $terms ) && is_array( $terms )){
            return count($terms);
        }else{
            return 1;
        }
    }

    /*
     * load more
     */
    public function get_events_loadmore(){
        $order = isset($_POST['order']) ? $_POST['order'] : '';
        $load_more = 1;
        $ep_requests     = new EP_Requests;
        // $events_data = $this->load_event_common_options( $atts = array('order'=>$order), $load_more );
        $atts = json_decode( sanitize_text_field( wp_unslash( $_POST['event_atts'] ) ), true );  
        $events_data = $this->load_event_common_options( $atts, $load_more );
        ob_start();
        if( $events_data['calendar_view'] == 1 ) {
            // get calendar events
            if(isset($events_data['events']->posts ))
            {
                $cal_events = $this->get_front_calendar_view_event( $events_data['events']->posts );
            }
            else
            {
                $cal_events =  array();
            }
            $cal_events =  array();
            wp_localize_script(
                'ep-front-events-js', 
                'em_front_event_object', 
                array(
                    'cal_events' => $cal_events,
                    'view' => $events_data['display_style'],
                    'local' => $this->ep_get_calendar_locale()
                )
            );
        }
        $themepath = $ep_requests->eventprime_get_ep_theme('events-list-load-tpl');

        $this->ep_get_template_part( $themepath, null, (object)$events_data );
	    $data['html'] = ob_get_clean();
        $data['paged'] = $events_data['paged'];
        return $data;
    }

    /**
     * Load detail page on click on the other dates
     */
    public function ep_load_other_date_event_detail( $event_id ) {
        if( ! empty( $event_id ) ) {
            $post                = get_post( $event_id );
            $events_data         = array();
            $events_data['post'] = $post;
            $events_data['event'] = $this->get_single_event( $post->ID );
            ob_start();
            wp_enqueue_style(
                'ep-event-owl-slider-style',
                plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/owl.carousel.min.css',
                false, EVENTPRIME_VERSION
            );
            wp_enqueue_style(
                'ep-event-owl-theme-style',
                plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/owl.theme.default.min.css',
                false, EVENTPRIME_VERSION
            );
            wp_enqueue_script(
                'ep-event-owl-slider-script',
                plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/owl.carousel.min.js',
                array( 'jquery' ), EVENTPRIME_VERSION
            );
            wp_register_script( 'em-google-map', plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/em-map.js', array( 'jquery' ), EVENTPRIME_VERSION );
            $gmap_api_key = $this->ep_get_global_settings( 'gmap_api_key' );
            if($gmap_api_key) {
                wp_enqueue_script(
                    'google_map_key', 
                    'https://maps.googleapis.com/maps/api/js?key='.$gmap_api_key.'&libraries=places,marker,drawing,geometry&callback=Function.prototype&loading=async', 
                    array(), EVENTPRIME_VERSION
                );
            }
            wp_enqueue_style( 'ep-responsive-slides-css' );
            wp_enqueue_script( 'ep-responsive-slides-js' );
            wp_enqueue_style(
                'ep-front-single-event-css',
                plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-single-event.css',
                false, EVENTPRIME_VERSION
            );
            wp_enqueue_script(
                'ep-event-single-script',
                plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/ep-frontend-single-event.js',
                array( 'jquery' ), EVENTPRIME_VERSION
            );
            // localized script array
            $get_ticket_now_text = $this->ep_global_settings_button_title('Get Tickets Now');
            $localized_script = array(
                'event'              => $events_data['event'],
                'subtotal_text'      => esc_html__( 'Subtotal', 'eventprime-event-calendar-management' ),
                'single_event_nonce' => wp_create_nonce( 'single-event-data-nonce' ),
                'event_booking_nonce'=> wp_create_nonce( 'event-booking-nonce' ),
                'starting_from_text' => esc_html__( 'Starting from', 'eventprime-event-calendar-management' ),
                'offer_applied_text' => esc_html__( 'Offers are applied in the next step.', 'eventprime-event-calendar-management' ),
                'no_offer_text'      => esc_html__( 'No offer available.', 'eventprime-event-calendar-management' ),
                'capacity_text'      => esc_html__( 'Capacity', 'eventprime-event-calendar-management' ),
                'ticket_left_text'   => esc_html__( 'tickets left!', 'eventprime-event-calendar-management' ),
                'allow_cancel_text'  => esc_html__( 'Cancellations Allowed', 'eventprime-event-calendar-management' ),
                'min_qty_text'       => esc_html__( 'Min Qnty', 'eventprime-event-calendar-management' ),
                'max_qty_text'       => esc_html__( 'Max Qnty', 'eventprime-event-calendar-management' ),
                'event_fees_text'    => esc_html__( 'Event Fees', 'eventprime-event-calendar-management' ),
                'ticket_now_btn_text'=> esc_html( $get_ticket_now_text ),
                'multi_offfer_applied'=> esc_html__( 'Offers Applied', 'eventprime-event-calendar-management' ),
                'one_offfer_applied' => esc_html__( 'Offer Applied', 'eventprime-event-calendar-management' ),
                'book_ticket_text'   => esc_html__( 'Book Tickets', 'eventprime-event-calendar-management' ),
                'max_offer_applied'  => esc_html__( 'Max Offer Applied', 'eventprime-event-calendar-management' ),
            );
            // check for child events
            if( $events_data['event']->child_events && count( $events_data['event']->child_events ) > 0 ) {
                $cal_events = $this->get_front_calendar_view_event( $events_data['event']->child_events );
                // load calendar library
                wp_enqueue_style(
                    'ep-front-event-calendar-css',
                    plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-calendar.min.css',
                    false, EVENTPRIME_VERSION
                );
                wp_enqueue_script(
                    'ep-front-event-calendar-js',
                    plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/ep-calendar.min.js',
                    false, EVENTPRIME_VERSION
                );
                wp_enqueue_script(
                    'ep-front-event-fulcalendar-local-js',
                    plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/locales-all.js',
                    array( 'jquery' ), EVENTPRIME_VERSION
                );
                $localized_script['cal_events'] = $cal_events;
                $localized_script['local'] = $this->ep_get_calendar_locale();
            }
            wp_localize_script(
                'ep-event-single-script', 
                'em_front_event_object', 
                array(
                    'em_event_data' => $localized_script,
                )
            );
            $ep_requests = new EP_Requests;
            $themepath = $ep_requests->eventprime_get_ep_theme('single-event-page-load-other-tpl');
            $this->ep_get_template_part( $themepath, null, (object)$events_data );
            return ob_get_clean();
        }
    }
    
    

    public function ep_get_ticket_data($ticket_id)
    {
        global $wpdb;
        $price_options_table = $wpdb->prefix.'em_price_options';
        $get_ticket_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $price_options_table WHERE `id` = %d", $ticket_id ) );
        return $get_ticket_data;
    }

    public function ep_recalculate_tickets_data($tickets,$offer,$event=false)
    {
        $tickets_data = json_decode($tickets);
        $newtickets = array();
        $sub_total = 0;
        $qty = 0;
        $offerdiscount = 0;
        if (is_array($tickets_data)) {
            foreach ($tickets_data as $key =>$ticket) {
                $remaining_tickets = $this->eventprime_check_remaining_tickets_in_event($event, $ticket->id,$ticket->qty);
                if($remaining_tickets==false)
                {
                    return false;
                }
                $ticket_data = $this->ep_recalculate_ticket_data($ticket,$offer); 
                $newtickets[$key] = $ticket_data[0];
                $sub_total += $ticket_data[1];
                $qty += $ticket_data[2];
                $offerdiscount +=$ticket_data[3];
            }
        }

        return array($newtickets,$sub_total,$qty,$offerdiscount);

    }

    public function ep_calculate_offer_price($price,$qty,$offer)
    {
        $discount_val = 0;
        if(!empty($offer))
        {
            foreach($offer as $off)
            {
                $discount_amount_type=  $off->em_ticket_offer_discount_type;
                $discount_amount = $off->em_ticket_offer_discount;
                if( $discount_amount_type == "percentage" ) 
                {
                    $discount = ( $discount_amount/100 ) * $price;
                    if( $discount > 0 ) {
                        $discount_val += $discount;
                    }
                }
                else
                {
                    $discount = $discount_amount;
                    if( $discount > 0 ) {
                        $discount_val += $discount;
                    }
                }
            }

        }

        return $discount_val * $qty;

    }
    public function ep_recalculate_ticket_data_old($ticket,$offer)
    {  

        $ticket_data = $this->ep_get_ticket_data($ticket->id);
        //epd($ticket_data);
        $ticket->price = (float)$ticket_data[0]->price; // Set to the desired new price
        $maximum_ticket_qty = $ticket_data[0]->max_ticket_no;
        $minimum_ticket_qty = $ticket_data[0]->min_ticket_no;
        if($ticket->qty>$maximum_ticket_qty)
        {
            $ticket->qty = $maximum_ticket_qty;
        }

        if($ticket->qty<$minimum_ticket_qty)
        {
            $ticket->qty = $minimum_ticket_qty;
        }
        $offer_applied_data = $this->get_event_offer_applied_data( $ticket_offers, $ticket_data[0], $ticket_data[0]->event_id,$ticket->qty );
    
        $offer_amount = $this->ep_calculate_offer_price($ticket_data[0]->price,$ticket->qty,$offer);
        $ticket->offer = $offer_amount; // Set to the desired new offer
        $ticket->additional_fee = json_decode($ticket_data[0]->additional_fees);
        if(empty($ticket->additional_fee))
        {
            $ticket->additional_fee = array();
        }
        $ticket->subtotal = ($ticket_data[0]->price * $ticket->qty) + $this->calculateAdditionalFees($ticket->additional_fee,$ticket->qty) - $offer_amount;

        return array($ticket,$ticket->subtotal,$ticket->qty,$ticket->offer);

    }
    public function ep_recalculate_ticket_data($ticket,$offer)
    {  

        $ticket_data = $this->ep_get_ticket_data($ticket->id);
        $ticket->price = (float)$ticket_data[0]->price; // Set to the desired new price
        $maximum_ticket_qty = $ticket_data[0]->max_ticket_no;
        $minimum_ticket_qty = $ticket_data[0]->min_ticket_no;
        
        if( !empty($maximum_ticket_qty) && $ticket->qty > $maximum_ticket_qty ) {
            $ticket->qty = $maximum_ticket_qty;
        }

        if($ticket->qty<$minimum_ticket_qty)
        {
            $ticket->qty = $minimum_ticket_qty;
        }

        $ticket_response = (object)$this->eventprime_update_cart_response($ticket->id,$ticket->qty);
//        $ticket_offers = json_decode( $ticket_data[0]->offers );
//        $offer_applied_data = $this->get_event_offer_applied_data( $ticket_offers, $ticket_data[0], $ticket_data[0]->event_id,$ticket->qty );
//
//        $offer_amount = $this->ep_calculate_offer_price($ticket_data[0]->price,$ticket->qty,$offer_applied_data);
//        $ticket->offer = $offer_amount; // Set to the desired new offer
//        $ticket->additional_fee = json_decode($ticket_data[0]->additional_fees);
//        if(empty($ticket->additional_fee))
//        {
//            $ticket->additional_fee = array();
//        }
//        $ticket->subtotal = ($ticket_data[0]->price * $ticket->qty) + $this->calculateAdditionalFees($ticket->additional_fee,$ticket->qty) - $offer_amount;
          $ticket->subtotal = $ticket_response->subtotal;
          $ticket->qty = $ticket_response->qty;
          $ticket->offer = $ticket_response->offer;
          $ticket->additional_fee = $ticket_response->additional_fee;
        return array($ticket,$ticket->subtotal,$ticket->qty,$ticket->offer);

    }
    
    public function ep_validate_woocommerce_product_data($newdata)
    {
        if(isset($newdata['woocommerce_products_variation_id']) && !empty($newdata['woocommerce_products_variation_id']) && isset($newdata['woocommerce_products_qty']))
        {
            $i = 0;
            $previous_product_total = $newdata['ep_wc_product_total'];
            $product_total = 0;
            foreach($newdata['woocommerce_products_variation_id'] as $product)
            {
                $variable_product = wc_get_product($product);
                $qty = $newdata['woocommerce_products_qty'][$i];
                $price = $variable_product->get_price() * $qty;
                $product_total += $price;
                $i++;
            }

            if($product_total!=$previous_product_total)
            {
                $data = array($product_total,$previous_product_total);
                return $data;
            }
            else
            {
                return true;
            }

        }
        else
        {
            return true;
        }
    }
    
    public function ep_recalculate_and_verify_the_cart_data($data,$offer)
    {   

        $event_id = $data['ep_event_booking_event_id'];
        $event = $this->get_single_event( $event_id );
        $event_fixed_price = ($event->em_fixed_event_price)?$event->em_fixed_event_price:0;
        $newdata = array(); 
        $total = 0;
        $qty = 0;
        $total_discount = 0;
        if(!empty($data))
        {
            foreach($data as $key=>$value)
            {
                if($key=='ep_event_booking_ticket_data')
                {
                    $newtickets_data = $this->ep_recalculate_tickets_data($value,$offer,$event);
                    if($newtickets_data==false)
                    {
                        return 'ticket_sold';
                        break;
                    }
                    $value = wp_json_encode($newtickets_data[0]);
                    $total += $newtickets_data[1];
                    $qty += $newtickets_data[2];
                    $total_discount += $newtickets_data[3];
                }
                if($key=='ep_event_booking_event_fixed_price')
                {
                    $value = $event_fixed_price;
                    $total += $event_fixed_price;
                }
                $newdata[$key] = $value;
            }

            if(isset($newdata['ep_coupon_code']) && isset($newdata['ep_coupon_discount']))
            {
                $discount = base64_decode($data['ep_coupon_discount']);
                $total = $total - $discount;
                $total_discount += $discount;
            }
            
            
            
            
            
           //print_r($data);             
           //print_r($newdata);die;
            

            $newdata['ep_event_booking_total_discount'] = $total_discount;
            if(isset($newdata['ep_event_booking_total_price']))
            {
                $newdata['ep_event_booking_total_price'] = round($total, 2);
            }
            if(isset($newdata['ep_event_booking_total_tickets']))
            {
                $newdata['ep_event_booking_total_tickets'] = $qty;
            }
            // If WooCommerce Integration enabled for the event. 
            // if ( isset($event->em_enable_product) && $event->em_enable_product == 1) { 
            //     // $newdata['ep_event_booking_total_price'] += $newdata['ep_wc_product_total']; 
            //     $newdata['ep_event_booking_total_price'] = round( $newdata['ep_event_booking_total_price'] + $newdata['ep_wc_product_total'] , 2 ); 
            // }
        }
        if(is_user_logged_in())
        {
            $user_id = get_current_user_id();
        }
        elseif(isset($data['ep_booking_guest_booking_field']) && isset($data['ep_booking_guest_booking_field']['ep_gb_email']) && !empty($data['ep_booking_guest_booking_field']['ep_gb_email']))
        {
            $user_id = $data['ep_booking_guest_booking_field']['ep_gb_email'];
        }
        $is_able_to_purchase = $this->ep_check_event_restrictions($event,$user_id);
        if($is_able_to_purchase!==true)
        {
            if($is_able_to_purchase[0]===false || $newdata['ep_event_booking_total_tickets'] > $is_able_to_purchase[0])
            {
                return false;
            }  
        }

        $newdata = apply_filters('ep_update_new_data_before_validating_cart', $newdata, $data); 

        $validate  = $this->ep_validate_cart_data($data,$newdata);
        if($validate===true)
        {
            return $newdata;
        }
        else
        {
            return $validate;
        }
    }

    public function ep_validate_cart_data($data,$newdata)
    {
        $return = true;
        if(isset($data['ep_event_booking_ticket_data']) && isset($newdata['ep_event_booking_ticket_data']))
        {
            $old = json_decode($data['ep_event_booking_ticket_data']);
            $new = json_decode($newdata['ep_event_booking_ticket_data']);
            if($old!=$new)
            {
                return false;
            }
        }

        if(isset($newdata['ep_event_booking_total_price']))
        {
            if(!isset($data['ep_event_booking_total_price']))
            {
                return false;
            }
            else if($data['ep_event_booking_total_price']!=$newdata['ep_event_booking_total_price'])
            {
                return false;
            }
        }

        if(isset($newdata['ep_event_booking_total_tickets']))
        {
            if(!isset($data['ep_event_booking_total_tickets']))
            {
                return false;
            }
            else if($data['ep_event_booking_total_tickets']!=$newdata['ep_event_booking_total_tickets'])
            {
                return false;
            }
        }

        return $return;
    }
    
    public function ep_get_booking_tickets_total_price_without_currency( $booking_tickets ) {
        $price = 0;
        if( ! empty( $booking_tickets ) && count( $booking_tickets ) > 0 ) {
            foreach( $booking_tickets as $ticket ) {
                $tic_price = $ticket->price;
                $tic_qty = $ticket->qty;
                $price += $tic_price * $tic_qty;
                if( isset( $ticket->offer ) && ! empty( $ticket->offer ) ) {
                    //$price += $ticket->offer;
                }
            }
        }
        return $price;
    }

    public function calculateAdditionalFees($additionalFees,$qty) {
        $totalAdditionalFees = 0;

        foreach ($additionalFees as $fee) {
            $totalAdditionalFees += $fee->price;
        }

        return $totalAdditionalFees * $qty;
    }


    
    public function ep_sanitize_input( $input ) 
    {
        // Initialize the new array that will hold the sanitize values
        $new_input = array();
        // Loop through the input and sanitize each of the values
        foreach ($input as $key => $val) {
            if (empty($val)) {
                $new_input[$key] = $val;
                continue;
            }
            if (is_array($val)) {
                $new_input[$key] = $this->ep_sanitize_input($val);
            } else {
                switch ($key) {
                    case 'login':
                    case 'uname':
                        $new_input[$key] = sanitize_user($val);
                        break;
                    case 'user_email':
                        $new_input[$key] = sanitize_email($val);
                        break;
                    case 'key':
                        $new_input[$key] = sanitize_text_field($val);
                        break;
                    case 'nonce':
                    case '_wpnonce':
                        $new_input[$key] = sanitize_key($val);
                        break;
                    case 'user_login':
                    case 'userdata':
                        if (is_email($val)) {
                            $new_input[$key] = sanitize_email($val);
                        } else {
                            $new_input[$key] = sanitize_user($val);
                        }
                        break;
                    default:
                        if (is_email($val)) {
                            $new_input[$key] = sanitize_email($val);
                        } else {
                            $new_input[$key] = wp_kses_post($val);
                        }

                        break;
                }
            }
        }
        return $new_input;
    }
    
    public function ep_get_booking_attendee_field_labels( $attendees ) {
        $labels = array();
        if( ! empty( $attendees ) ) {
            foreach( $attendees as $key => $attendee ) {
                if( $key == 'seat' ) continue;

                if( $key == 'name' ) {
                    if( isset( $attendee['first_name'] ) ) {
                        $labels[] = 'First Name';
                    }
                    if( isset( $attendee['middle_name'] ) ) {
                        $labels[] = 'Middle Name';
                    }
                    if( isset( $attendee['last_name'] ) ) {
                        $labels[] = 'Last Name';
                    }
                    unset( $attendees['name'] );
                }
                foreach( $attendees as $at_key => $at ) {
                    // seat column should be in end
                    if( $at_key == 'seat' ) continue;
                    if( ! empty( $at['label'] ) ) {
                        $labels[] = $at['label'];
                    }
                }
                $labels = apply_filters( 'ep_filter_booking_attendee_field_labels', $labels, $attendees );
                break;
            }
        }
        return $labels;
    }

    // generate slug from the string
    public function ep_get_slug_from_string( $string ) {
        if(empty($string) || !is_string($string))
        {
            return '';
        }
        // Strip html tags
        $text = strip_tags($string);
        // Replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '_', $text);
        // Transliterate
        //setlocale(LC_ALL, 'en_US.utf8');
        //$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        // Trim
        $text = trim($text, '-');
        // Remove duplicate -
        $text = preg_replace('~-+~', '_', $text);
        // Lowercase
        $text = strtolower($text);
        // Check if it is empty
        if (empty($text)) { return '---'; }
        // Return result
        return $text;
    }
    

    public function ep_get_converted_price_in_cent( $price, $currency ) {
        if( $this->ep_is_price_conversion_req_for_stripe( $currency ) ) {
                    $price = ( number_format( $price, 2, '.', '' ) * 100 );
            }
        return $price;
    }

    public function convert_fr( $currency, $price ) {
        if( $this->ep_is_price_conversion_req_for_stripe( $currency ) ) {
            $price = $price / 100;
        }
        return $price;
    }

    public function ep_is_price_conversion_req_for_stripe($currency){
        $currency = strtoupper( $currency );
        switch( $currency ) {
            case 'BIF':
            case 'DJF':
            case 'JPY':
            case 'KRW':
            case 'PYG':
            case 'VND':
            case 'XAF':
            case 'XPF':
            case 'CLP':
            case 'GNF':
            case 'KMF':
            case 'MGA':
            case 'RWF':
            case 'VUV':
            case 'XOF':
                return false;
            default:
                return true;
        }
        return false;
    }
    
    public function get_upcoming_events_for_performer( $performer_id, $args = array() ) {
        // $hide_past_events = $this->ep_get_global_settings( 'single_performer_hide_past_events' );
        if(!isset($args['hide_past_events'])){
            $hide_past_events = $this->ep_get_global_settings( 'single_performer_hide_past_events' );
        }else{
            $hide_past_events = $args['hide_past_events'];
        }
        $past_events_meta_qry = '';
        if( ! empty( $hide_past_events ) ) {
            $past_events_meta_qry = array(
                'relation' => 'AND',
                array(
                    'key'     => 'em_start_date_time',
                    'value'   => current_time( 'timestamp' ),
                    'compare' => '>=',
                ),
            );
        }
        
        $filter = array(
            'meta_key'    => 'em_start_date_time',
            'orderby'     => 'meta_value',
            'order'       => 'ASC',
            'meta_query'  => array( 'relation' => 'AND',
                array(
                    array(
                        'key'     => 'em_performer',
                        'value'   =>  serialize( strval ( $performer_id ) ),
                        'compare' => 'LIKE'
                    ),
                    $past_events_meta_qry,
                    
                )
            ),
            'post_type' => 'em_event'
        );
        $filter = apply_filters( 'ep_performers_render_argument', $filter, $args );
        $args = wp_parse_args($args, $filter);
        
        $wp_query = new WP_Query( $args );
        $wp_query->performer_id = $performer_id;
        return $wp_query;
    }

    public function get_upcoming_events_for_performer_new( $performer_id, $args = array() ) {
        // Fetch the global setting for hiding past events if not explicitly provided.
        $hide_past_events = isset($args['hide_past_events']) 
            ? $args['hide_past_events'] 
            : $this->ep_get_global_settings('single_performer_hide_past_events');

        // Initialize the meta query.
        $meta_query = array(
            'relation' => 'AND',
            array(
                'key'     => 'em_performer',
                'value'   => '"' . intval($performer_id) . '"', // Target specific performer IDs in serialized data.
                'compare' => 'LIKE'
            )
        );

        // Add condition to hide past events.
        if (!empty($hide_past_events)) {
            $meta_query[] = array(
                'key'     => 'em_start_date_time',
                'value'   => current_time('timestamp'),
                'compare' => '>=',
                'type'    => 'NUMERIC', // Ensure numeric comparison for timestamps.
            );
        }

        // Build the query arguments.
        $query_args = array(
            'post_status' => 'publish',
            'post_type'      => 'em_event',
            'posts_per_page' => isset($args['posts_per_page']) ? $args['posts_per_page'] : 10, // Limit the number of posts.
            'meta_key'       => 'em_start_date_time',
            'orderby'        => 'meta_value_num', // Use numeric ordering for timestamp fields.
            'order'          => 'ASC',
            'meta_query'     => $meta_query,
            'fields'         => isset($args['fields']) ? $args['fields'] : '', // Allow field-specific queries.
        );

        // Allow modifications to the query arguments via a filter.
        $query_args = apply_filters('ep_performers_render_argument', $query_args, $args);

        // Merge custom arguments provided with defaults.
        $query_args = wp_parse_args($args, $query_args);

        // Execute the query.
        $wp_query = new WP_Query($query_args);
        $wp_query->performer_id = $performer_id;

        return $wp_query;
    }

    
    public function get_eventupcoming_performer_loadmore(){
        $settings            = new Eventprime_Global_Settings;
        $performers_settings = $settings->ep_get_settings( 'performers' );
        $ep_requests = new EP_Requests;
        $event_args  = $performers_data = array();
        $event_args['event_style']   = isset( $_POST['event_style'] ) ? $_POST["event_style"] : $performers_settings->single_performer_event_display_view;
        $event_args['event_limit']   = isset( $_POST['event_limit'] ) ? (empty($_POST["event_limit"]) ? 10 : $_POST["event_limit"]) : (empty($performers_settings->single_performer_event_limit) ? 10 : $performers_settings->single_performer_event_limit );
        $event_args['event_cols']    = isset( $_POST['event_cols'] ) ? $_POST['event_cols']  : $this->ep_check_column_size( $performers_settings->single_performer_event_column );
        $event_args['load_more']     = isset( $_POST['load_more'] ) ? $_POST['load_more'] : $performers_settings->single_performer_event_load_more;
        $event_args['hide_past_events'] = isset( $_POST['hide_past_events'] ) ? $_POST['hide_past_events'] : $performers_settings->single_performer_hide_past_events;
        $event_args['post_id'] = isset( $_POST['post_id'] ) ? $_POST['post_id'] : 0;
        
        // set query arguments
        $paged     = ( $_POST['paged'] ) ? $_POST['paged'] : 1;
        $paged++;
        $pargs     = array(
            'orderby'        => 'em_start_date_time',
            'posts_per_page' => $event_args['event_limit'],
            'offset'         => (int)( $paged-1 ) * (int)$event_args['event_limit'],
            'paged'          => $paged,
            'hide_past_events' => $event_args['hide_past_events']
        );
        $performers_data['event_args']  = $event_args;
        
        $pargs['post_status'] = !empty( $event_args['hide_past_events'] ) == 1 ? 'publish' : 'any';
        $performers_data['events'] = $this->get_upcoming_events_for_performer( $event_args['post_id'] , $pargs);
        
        ob_start();
        wp_enqueue_style(
            'ep-performer-views-css',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-views.css',
            false, EVENTPRIME_VERSION
        );
        
        $themepath = $ep_requests->eventprime_get_ep_theme('upcoming-events-list-load-tpl');
        $this->ep_get_template_part( $themepath, null, (object)$performers_data );
        $data['html'] = ob_get_clean();
        $data['paged'] = $paged;
        return $data;
    }

    public function ep_add_views_path_template( $template ) {
        $pos = strpos( $template, '/' );
        if ($pos !== false) {
            $template = substr_replace($template, '/views/', $pos, strlen( '/' ));
        }
        return $template;
    }
	
    public function get_gmt_offset( $timezone = NULL ) {
        if( trim( $timezone ) != '' and $timezone != 'global'){
            $UTC = new DateTimeZone('UTC');
            $TZ = new DateTimeZone( $timezone );

            $gmt_offset_seconds = $TZ->getOffset( ( new DateTime( 'now', $UTC ) ) );
            $gmt_offset = ( $gmt_offset_seconds / HOUR_IN_SECONDS );
        }
        else $gmt_offset = get_option('gmt_offset');

        $minutes = $gmt_offset * 60;
        $hour_minutes = sprintf( "%02d", $minutes % 60 );

        // Convert the hour into two digits format
        $h = ( $minutes - $hour_minutes ) / 60;
        $hours = sprintf( "%02d", abs( $h ) );

        // Add - sign to the first of hour if it's negative
        if( $h < 0 ) $hours = '-'.$hours;

        return (substr( $hours, 0, 1 ) == '-' ? '' : '+' ).$hours.':'.( ( (int) $hour_minutes < 0 ) ? abs( $hour_minutes ) : $hour_minutes );
    }
    
    public function ep_is_registration_magic_active() {
        if ( defined( "REGMAGIC_BASIC" ) || defined( "REGMAGIC_GOLD" ) )
            return true;
        else
            return false;
    }
    
    
    
     public function get_eventupcoming_venue_loadmore(){
        $settings        = new Eventprime_Global_Settings;
        $ep_requests     = new EP_Requests;
        $venues_settings = $settings->ep_get_settings( 'venues' );
        //print_r($_POST);
        $event_args  = array();
        $venues_data                 = array();
        $event_args['event_style']   = isset( $_POST['event_style'] ) ? $_POST["event_style"] : $venues_settings->single_venue_event_display_view;
        $event_args['event_limit']   = isset( $_POST['event_limit'] ) ? (empty($_POST["event_limit"]) ? 10 : $_POST["event_limit"]) : (empty($venues_settings->single_venue_event_limit) ? 10 : $venues_settings->single_venue_event_limit );
        $event_args['event_cols']    = isset( $_POST['event_cols'] ) ? $_POST['event_cols']  : $this->ep_check_column_size( $venues_settings->single_venue_event_column );
        $event_args['load_more']     = isset( $_POST['load_more'] ) ? $_POST['load_more'] : $venues_settings->single_venue_event_load_more;
        $event_args['hide_past_events'] = isset( $_POST['hide_past_events'] ) ? $_POST['hide_past_events'] : $venues_settings->single_venue_hide_past_events;
        $event_args['post_id'] = isset( $_POST['post_id'] ) ? $_POST['post_id'] : 0;
         
        // set query arguments
        $paged     = (int)( $_POST['paged'] ) ? $_POST['paged'] : 1;
        $paged++;
        $pargs     = array(
            'orderby'        => 'em_start_date_time',
            'posts_per_page' => $event_args['event_limit'],
            'offset'         => (int)( $paged-1 ) * (int)$event_args['event_limit'],
            'paged'          => $paged,
            'hide_past_events' => $event_args['hide_past_events'],
            'post_status' => 'publish',
        );

        $venues_data['event_args']  = $event_args;
        $pargs['post_status'] = !empty( $event_args['hide_past_events'] ) == 1 ? 'publish' : 'any';
        //$venues_data['events'] = $this->get_upcoming_events_for_venue( $event_args['post_id'] , $pargs);
        $venues_data['events'] = $ep_requests->get_upcoming_events_for_taxonomy('em_venue', $event_args['post_id'], $event_args['hide_past_events'], $event_args['event_limit'], $paged, $pargs);
        ob_start();
	$themepath = $ep_requests->eventprime_get_ep_theme('upcoming-events-list-load-tpl');
        $this->ep_get_template_part( $themepath, null, (object)$venues_data );
            
        $data['html'] = ob_get_clean();
        $data['paged'] = $paged;
        return $data;
    }
    
     public function get_eventupcoming_organizer_loadmore(){
        $settings                           = new Eventprime_Global_Settings;
        $organizers_settings                = $settings->ep_get_settings( 'organizers' );
        $ep_requests = new EP_Requests;
        $event_args  = array();
        $organizer_data                  = array();
        $event_args['event_style']   = isset( $_POST['event_style'] ) ? $_POST["event_style"] : $organizers_settings->single_organizer_event_display_view;
        $event_args['event_limit']   = isset( $_POST['event_limit'] ) ? (empty($_POST["event_limit"]) ? 10 : $_POST["event_limit"]) : (empty($organizers_settings->single_organizer_event_limit) ? 10 : $organizers_settings->single_organizer_event_limit );
        $event_args['event_cols']    = isset( $_POST['event_cols'] ) ? $_POST['event_cols']  : $this->ep_check_column_size( $organizers_settings->single_organizer_event_column );
        $event_args['load_more']     = isset( $_POST['load_more'] ) ? $_POST['load_more'] : $organizers_settings->single_organizer_event_load_more;
        $event_args['hide_past_events'] = isset( $_POST['hide_past_events'] ) ? $_POST['hide_past_events'] : $organizers_settings->single_organizer_hide_past_events;
        $event_args['post_id'] = isset( $_POST['post_id'] ) ? $_POST['post_id'] : 0;
        
        // set query arguments
        //$paged = ( $_POST['paged'] ) ? $_POST['paged'] : 1;
        $paged = ( ! empty( $_POST['paged'] ) ? $_POST['paged'] : 1 );
        $paged++;
        $pargs = array(
            'orderby'        => 'em_start_date_time',
            'posts_per_page' => $event_args['event_limit'],
            'offset'         => (int)( $paged - 1 ) * (int)$event_args['event_limit'],
            'paged'          => $paged,
            'hide_past_events'=> $event_args['hide_past_events'],
            'post_status' => 'publish',
        );
        $organizer_data['event_args']  = $event_args;
        
        $pargs['post_status'] = !empty( $event_args['hide_past_events'] ) == 1 ? 'publish' : 'any';
        $organizer_data['events'] = $ep_requests->get_upcoming_events_for_taxonomy('em_event_organizer', $event_args['post_id'], $event_args['hide_past_events'], $event_args['event_limit'], $paged, $pargs);
        
        ob_start();
        wp_enqueue_style(
            'ep-organizer-views-css',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-views.css',
            false, EVENTPRIME_VERSION
        );
        $themepath = $ep_requests->eventprime_get_ep_theme('upcoming-events-list-load-tpl');

        $this->ep_get_template_part( $themepath, null, (object)$organizer_data );
        $data['html'] = ob_get_clean();
        $data['paged'] = $paged;
        return $data;
    }
    
    public function get_upcoming_events_for_event_type( $event_type_id, $args = array() ) {
        // $hide_past_events = $this->ep_get_global_settings( 'single_type_hide_past_events' );
        if(!isset($args['hide_past_events'])){
            $hide_past_events = $this->ep_get_global_settings( 'single_type_hide_past_events' );
        }else{
            $hide_past_events = $args['hide_past_events'];
        }
        $past_events_meta_qry = '';
        if( ! empty( $hide_past_events ) ) {
            $past_events_meta_qry = array(
                'relation' => 'OR',
                array(
                    'key'     => 'em_start_date_time',
                    'value'   => current_time( 'timestamp' ),
                    'compare' => '>=',
                ),
            );
        }
        $filter = array(
            'meta_key'    => 'em_start_date_time',
            'orderby'     => 'meta_value',
            'numberposts' => -1,
            'order'       => 'ASC',
            'meta_query'  => array( 'relation' => 'AND',
                array(
                    array(
                        'key'     => 'em_event_type',
                        'value'   =>  $event_type_id,
                        'compare' => '='
                    ),
                    $past_events_meta_qry,
                )
            ),
            'post_type' => 'em_event'
        );

        $args = wp_parse_args( $args, $filter );
        $wp_query = new WP_Query( $args );
        $wp_query->event_type_id = $event_type_id;
        return $wp_query;
    }

    
    public function get_eventupcoming_eventtype_loadmore(){
        $settings = new Eventprime_Global_Settings;
        $event_type_settings = $settings->ep_get_settings( 'event_types' );
        $ep_requests = new EP_Requests;
        $event_args  = array();
        $organizer_data                  = array();
        $single_type_event_orderby = $this->ep_get_global_settings( 'single_type_event_orderby' );
        $single_type_event_order = $this->ep_get_global_settings( 'single_type_event_order' );
            
        $event_args['event_style']   = isset( $_POST['event_style'] ) ? $_POST["event_style"] : $event_type_settings->single_type_event_display_view;
        $event_args['event_limit']   = isset( $_POST['event_limit'] ) ? (empty($_POST["event_limit"]) ? 10 : $_POST["event_limit"]) : (empty($event_type_settings->single_type_event_limit) ? 10 : $event_type_settings->single_type_event_limit );
        $event_args['event_cols']    = isset( $_POST['event_cols'] ) ? $_POST['event_cols']  : $this->ep_check_column_size( $event_type_settings->single_type_event_column );
        $event_args['load_more']     = isset( $_POST['load_more'] ) ? $_POST['load_more'] : $event_type_settings->single_type_event_load_more;
        $event_args['hide_past_events'] = isset( $_POST['hide_past_events'] ) ? $_POST['hide_past_events'] : $event_type_settings->single_type_hide_past_events;
        $event_args['post_id'] = isset( $_POST['post_id'] ) ? $_POST['post_id'] : 0;
        
        // set query arguments
        $paged     = ( $_POST['paged'] ) ? $_POST['paged'] : 1;
        $paged++;
        $pargs     = array(
            'orderby'        => $single_type_event_orderby,
            'order'        => $single_type_event_order,
            'posts_per_page' => $event_args['event_limit'],
            'offset'         => (int)( $paged-1 ) * (int)$event_args['event_limit'],
            'paged'          => $paged,
            'hide_past_events' => $event_args['hide_past_events'],
            'post_status' => 'publish',
        );
        $organizer_data['event_args']  = $event_args;
        
        $pargs['post_status'] = !empty( $event_args['hide_past_events'] ) == 1 ? 'publish' : 'any';
        //$organizer_data['events'] = $this->get_upcoming_events_for_event_type( $event_args['post_id'] , $pargs);
        $organizer_data['events'] = $ep_requests->get_upcoming_events_for_taxonomy('em_event_type', $event_args['post_id'], $event_args['hide_past_events'], $event_args['event_limit'], $paged, $pargs);
            
        ob_start();
        wp_enqueue_style(
            'ep-organizer-views-css',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-views.css',
            false, EVENTPRIME_VERSION
        );
        $themepath = $ep_requests->eventprime_get_ep_theme('upcoming-events-list-load-tpl');

        $this->ep_get_template_part( $themepath, null, (object)$organizer_data );
            
        $data['html'] = ob_get_clean();
        $data['paged'] = $paged;
        return $data;
    }
    
    public function get_filtered_event_content() {
        $ep_requests = new EP_Requests;
        $atts = ( ! empty( $_POST['event_atts'] ) ? (array)json_decode( stripslashes( $_POST['event_atts'] ) ) : array() );
        $events_data = $this->load_event_common_options( $atts );
        ob_start();
         $cal_events = array();
         $themepath = $ep_requests->eventprime_get_ep_theme('events-view-list-load-tpl');

        $this->ep_get_template_part( $themepath, null, (object)$events_data );
	    $data['html'] = ob_get_clean();
        $data['paged'] = $events_data['paged'];
        if( $events_data['calendar_view'] == 1 ) {
            $data['cal_events'] = $cal_events;
        }
        return $data;
    }
    
    public function get_admin_calendar_view_event( $events ) {
        $cal_events = array();
        if( ! empty( $events ) && ! empty( $events ) ) {
            $new_window = ( ! empty( $this->ep_get_global_settings( 'open_detail_page_in_new_tab' ) ) ? 'target="_blank"' : '' );
            $event_listings_date_format = !empty($this->ep_get_global_settings('event_listings_date_format_val')) ? $this->ep_get_global_settings('event_listings_date_format_val') : 'Y-m-d';
            foreach( $events as $event ) {
                $ev = $this->get_event_data_to_views( $event );
                $start_date_time = $ev['start'];
                if( $this->ep_show_event_date_time( 'em_start_time', $event ) ) {
                    $start_date_time = explode( ' ', $start_date_time )[0];
                }
                $start_date_time = wp_date( $event_listings_date_format, $event->em_start_date );
                $ev['edit_url'] = esc_url(get_edit_post_link($ev['id']));
                if(isset($ev['url'])){
                    unset($ev['url']);
                }
                $ev['event_url'] = $ev['event_url'];
                // popup html
                $popup_html = '<div class="ep_event_detail_popup" id="ep_calendar_popup_'.esc_attr( $ev['id'] ).'" style="display:none">';
                    $popup_html .= '<a href="#" class="ep_event_popup_head" '.esc_attr( $new_window ).'>';
                        $popup_html .= '<div class="ep_event_popup_image">';
                            $popup_html .= '<img src="'.esc_url( $ev['image'] ).'">';
                        $popup_html .= '</div>';
                    $popup_html .= '</a>';
                    $popup_html .= '<div class="ep_event_popup_date_time_wrap ep-d-flex">';
                        $popup_html .= '<div class="ep_event_popup_date ep-d-flex ep-box-direction">';
                            if( $this->ep_show_event_date_time( 'em_start_date', $event ) ) {
                                $popup_html .= '<span class="ep_event_popup_start_date">' .esc_html( $start_date_time ) .'</span>';
                            } else{
                                if( ! empty( $ev['date_custom_note'] ) ) {
                                    if( $ev['date_custom_note'] == 'tbd' ) {
                                        $tbd_icon_file = plugin_dir_url( EP_PLUGIN_FILE ) .'public/partials/images/tbd-icon.png';
                                        $popup_html .= '<span class="ep_event_popup_start_date"><img src="'. esc_url( $tbd_icon_file ) .'" width="35" /></span>';
                                    } else{
                                        $popup_html .= '<span class="ep_event_popup_start_date">' .esc_html( $ev['date_custom_note'] ) .'</span>';
                                    }
                                }
                            }
                            if( $this->ep_show_event_date_time( 'em_end_date', $event ) ) {
                                $popup_html .= '<span class="ep_event_popup_end_date">';
                                    if( isset( $ev['event_day'] ) && ! empty( $ev['event_day'] ) ) {
                                        $popup_html .= esc_html( $ev['event_day'] );
                                    } else{
                                        $event_end_dt = $ev['end'];
                                        if( ! empty( $event_end_dt ) ) {
                                            $event_end_dt = explode( ' ', $event_end_dt )[0];
                                        }
                                        $popup_html .= esc_html( $event_end_dt );
                                    }
                                    $popup_html .= '</span>';
                            }
                        $popup_html .= '</div>';
                        $popup_html .= '<div class="ep_event_popup_time ep-d-flex ep-box-direction">';
                            if( $this->ep_show_event_date_time( 'em_start_time', $event ) ) {
                                $popup_html .= '<span class="ep_event_popup_start_time">' .esc_html( $this->ep_convert_time_with_format( $ev['start_time'] ) ) .'</span>';
                            }
                            if( $this->ep_show_event_date_time( 'em_end_time', $event ) ) {
                                $popup_html .= '<span class="ep_event_popup_end_time">' .esc_html( $this->ep_convert_time_with_format( $ev['end_time'] ) ) .'</span>';
                            }
                        $popup_html .= '</div>';
                    $popup_html .= '</div>';
                    $popup_html .= '<a href="#" class="ep-event-modal-head">';
                        $popup_html .= '<div class="ep_event_popup_title">';
                            $popup_html .= esc_html( $ev['title'] );
                        $popup_html .= '</div>';
                    $popup_html .= '</a>';
                    if( ! empty( $ev['address'] ) ) {
                        $popup_html .= '<div class="ep_event_popup_address">';
                            $popup_html .= esc_html( $ev['address'] );
                        $popup_html .= '</div>';
                    }
                    //Edit View Event
                    $popup_html .= '<div class="ep_event_popup_action_btn ep-d-flex ep-justify-content-between ep-border-top ep-py-2 ep-px-4 ep-text-center">';
                        $popup_html .= '<a href="'.esc_url( $ev['event_url'] ).'" class="ep_event_popup_btn ep-text-decoration-none ep-box-w-100" target="__blank">';
                            $popup_html .= '<div class="ep-event-action-btn ep-py-2">';
                                $popup_html .= esc_html( 'View Event', 'eventprime-event-calendar-management' );
                            $popup_html .= '</div>';
                        $popup_html .= '</a>';
                        if( current_user_can('edit_em_events') ) {
                            $popup_html .= '<a href="'.esc_url( $ev['edit_url'] ).'" class="ep_event_popup_btn ep-border-left ep-text-decoration-none ep-box-w-100" target="__blank">';
                                $popup_html .= '<div class="ep-event-action-btn ep-py-2">';
                                    $popup_html .= esc_html( 'Edit Event', 'eventprime-event-calendar-management' );
                                $popup_html .= '</div>';
                            $popup_html .= '</a>';
                        }
                    $popup_html .= '</div>';
                    // End Edit View
                    
                $popup_html .= '</div>';
                
                $ev['popup_html']=  $popup_html;
                
                $cal_events[] = $ev;
            }
        }
        return $cal_events;
    }
    
    
    public function ep_calendar_events_create(){
        parse_str( wp_unslash( $_POST['data'] ), $data );
        $event_data = array();
        $event_data['name'] = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : 'Draft Event';
        $event_data['description'] = isset( $data['description'] ) ? sanitize_text_field( $data['description'] ) : '';
        $event_data['status'] = isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'Publish';
        $event_data['em_event_type'] = isset( $data['event_type'] ) ? sanitize_text_field( $data['event_type'] ) : '';
        $event_data['em_venue'] = isset( $data['venue'] ) ? sanitize_text_field( $data['venue'] ) : '';
        $event_data['em_organizer'] = isset( $data['organizers'] ) ? $data['organizers'] : array();
        $event_data['em_performer'] = isset( $data['performers'] ) ? $data['performers'] : array();
        $format = $this->ep_get_datepicker_format();
        $em_start_date = isset( $data['start_date'] ) ? sanitize_text_field( $data['start_date'] ) : '';
        $em_start_time = isset( $data['start_time'] ) ? sanitize_text_field( $data['start_time'] ) : '12:00 AM';
        $em_end_date = isset( $data['end_date'] ) ? sanitize_text_field( $data['end_date'] ) : '';
        $em_end_time = isset( $data['end_time'] ) ? sanitize_text_field( $data['end_time'] ) : '11:59 PM';
        $em_all_day = isset( $data['em_all_day'] ) && ! empty( $data['em_all_day'] ) ? 1 : 0 ;
        if( ! empty( $em_start_date ) ) {
            $start_date = $em_start_date;
        }
        $start_time = $end_time = '';
        if( ! empty( $em_start_time ) ) {
            $start_time = $em_start_time;
        }
        if( ! empty( $em_end_date ) ) {
            $end_date = $em_end_date;
        }
        if( ! empty( $em_end_time ) ) {
            $end_time = $em_end_time;
        }
        $allday = (int)$em_all_day;
        if( ! empty( $allday ) || ! trim( $start_date ) && ! trim( $end_date ) ) {
            $allDayDate = $start_date;
            $event_data['em_all_day'] = 1;
            $start_time = "12:00 AM";
            $end_time = "11:59 PM";
            $start_date = $allDayDate;
            $end_date = $allDayDate;
        }
        $event_data['em_start_date'] = !empty($start_date) ? $this->ep_date_to_timestamp($start_date, $format) : '';
        $event_data['em_end_date'] = !empty($end_date) ? $this->ep_date_to_timestamp($end_date ,$format): '';
        $event_data['em_start_time'] = !empty($start_time) ? $start_time : '12:00 AM';
        $event_data['em_end_time'] = !empty($end_time) ? $end_time : '11:59 PM';
        
        $event_data['em_enable_booking'] = isset($data['em_enable_booking']) && !empty($data['em_enable_booking']) ? 'bookings_on' : 'bookings_off';
        $event_data['em_ticket_price'] = 0;
        $ticket_price = isset($data['em_ticket_price']) && !empty($data['em_ticket_price']) ? sanitize_text_field($data['em_ticket_price']) : 0;
        if(isset($data['event_id']) && !empty($data['event_id'])){
            $post_id = absint($data['event_id']);
            $post_update = array(
                'ID'          => $post_id,
                'post_title'  => $event_data['name'],
                'post_status' => $event_data['status']
            );
            wp_update_post( $post_update );
            
            update_post_meta( $post_id, 'em_name', $event_data['name'] );
            update_post_meta( $post_id, 'em_start_date', $event_data['em_start_date'] );
            update_post_meta( $post_id, 'em_end_date', $event_data['em_end_date'] );
            update_post_meta( $post_id, 'em_start_time', $event_data['em_start_time'] );
            update_post_meta( $post_id, 'em_end_time', $event_data['em_end_time'] );
            update_post_meta( $post_id, 'em_all_day', $event_data['em_all_day'] );
            update_post_meta( $post_id, 'em_venue', $event_data['em_venue'] );
            update_post_meta( $post_id, 'em_event_type', $event_data['em_event_type'] );
            update_post_meta( $post_id, 'em_organizer', $event_data['em_organizer'] );
            update_post_meta( $post_id, 'em_performer', $event_data['em_performer'] );
            $thumbnail_id = isset($data['ep_featured_image_id']) && !empty($data['ep_featured_image_id']) ? sanitize_text_field($data['ep_featured_image_id']) : 0;
            if( $thumbnail_id ) {
                set_post_thumbnail( $post_id, $thumbnail_id );
            }
            $event = $this->get_single_event($post_id);
            $event_data = $this->get_admin_calendar_view_event(array($event));
            $response = array('post_id'=>$post_id,'status'=>true,'event_data'=>$event_data,'message'=>esc_html('Event Successfully Updated.','eventprime-event-calendar-management'));
        }else{
            $post_id = $this->insert_event_post_data( $event_data );
            if( ! empty( $post_id ) ) {
                
                $ep_date_time_format = 'Y-m-d';
                $start_date = $event_data['em_start_date'];
                $start_time = $event_data['em_start_time'];
                $end_date = $event_data['em_end_date'];
                $end_time = $event_data['em_end_time'];
                $merge_start_date_time = $this->ep_datetime_to_timestamp($this->ep_timestamp_to_date($start_date, 'Y-m-d', 1) . ' ' . $start_time, $ep_date_time_format, '', 0, 1);
                if (!empty($merge_start_date_time)) {
                    update_post_meta($post_id, 'em_start_date_time', $merge_start_date_time);
                }

                $merge_end_date_time = $this->ep_datetime_to_timestamp($this->ep_timestamp_to_date($end_date, 'Y-m-d', 1) . ' ' . $end_time, $ep_date_time_format, '', 0, 1);
                if (!empty($merge_end_date_time)) {
                    update_post_meta($post_id, 'em_end_date_time', $merge_end_date_time);
                }
                
                $thumbnail_id = isset($data['ep_featured_image_id']) && !empty($data['ep_featured_image_id']) ? sanitize_text_field($data['ep_featured_image_id']) : 0;
                if($thumbnail_id){
                    set_post_thumbnail( $post_id, $thumbnail_id );
                }
                if($event_data['em_enable_booking'] == 'bookings_on' && !empty($ticket_price)){
                    update_post_meta($post_id, 'em_allow_cancellations', 0);
                    $dbhandler = new EP_DBhandler;
                    $price_options_table = 'TICKET';
                    $tier_data = array();
                    $tier_data['event_id'] = $post_id;
                    $tier_data['name'] = esc_html__('Default Price', 'eventprime-event-calendar-management');
                    $tier_data['description'] = esc_html__('Default Price', 'eventprime-event-calendar-management');
                    $tier_data['start_date'] = '';
                    $tier_data['end_date'] = '';
                    $tier_data['price'] = $ticket_price;
                    $tier_data['special_price'] = '';
                    $tier_data['capacity'] = isset($data['em_ticket_capacity']) && !empty($data['em_ticket_capacity']) ? sanitize_text_field($data['em_ticket_capacity']) : 0;
                    $tier_data['is_default'] = 1;
                    $tier_data['is_event_price'] = 1;
                    $tier_data['icon'] = '';
                    $tier_data['priority'] = 1;
                    $tier_data['status'] = 1;
                    $tier_data['created_at'] = wp_date("Y-m-d H:i:s", time());
                    $dbhandler->insert_row($price_options_table, $tier_data);
                }
                $event = $this->get_single_event($post_id);
                $event_data = $this->get_admin_calendar_view_event(array($event));
                $response = array('post_id'=>$post_id,'status'=>true,'event_data'=>$event_data,'message'=>esc_html('Event Successfully created.','eventprime-event-calendar-management'));
            }else{
                $response = array('post_id'=>0,'status'=>false,'message'=>esc_html('Event not created.','eventprime-event-calendar-management'));       
            }
        }
        return $response;
    }
    
    public function get_checkout_field_label_by_id( $id ) {
        $dbhandler = new EP_DBhandler;
        $table_name = 'CHECKOUT_FIELDS';
        $get_field_label = $dbhandler->get_row($table_name, $id,'id');
	    return $get_field_label;
    }
    
    public function get_ticket_name_by_id( $id ) {
        $dbhandler = new EP_DBhandler;
        $table_name = 'TICKET';
		$name = '';
                $get_field_data = $dbhandler->get_row($table_name, $id,'id');
		if( ! empty( $get_field_data ) ) {
			$name = $get_field_data->name;
		}
        return $name;
    }
    
    public function ep_calendar_events_drag_event_date($data){
        $response = array();
        $event_id = 0;
        if(isset($data['id']) && !empty($data['id'])){
            $event_id = sanitize_text_field($data['id']);
            if(isset($data['start_date']) && !empty($data['start_date'])){
                $start_date = $this->ep_date_to_timestamp(sanitize_text_field($data['start_date']), $this->ep_get_datepicker_format());
                $end_date = isset($data['end_date']) && !empty($data['end_date']) ? $this->ep_date_to_timestamp(sanitize_text_field($data['end_date']), $this->ep_get_datepicker_format()) : $start_date;
                update_post_meta($event_id, 'em_start_date', $start_date);
                update_post_meta($event_id, 'em_end_date', $end_date);
                $event = $this->get_single_event($event_id);
                $event_data = $this->get_admin_calendar_view_event(array($event));
                $response = array('post_id'=>$event_id,'status'=>true,'event_data'=>$event_data,'message'=>esc_html('Event updated successfully..','eventprime-event-calendar-management'));
            }else{
                $response = array('post_id'=>$event_id,'status'=>false,'message'=>esc_html('Event dates missing.','eventprime-event-calendar-management'));
            }
        }else{
            $response = array('post_id'=>$event_id,'status'=>false,'message'=>esc_html('Event Id missing.','eventprime-event-calendar-management'));
        }
        return $response;
    }

    public function ep_calculate_order_total_additional_fees_v2($tickets)
    {
        $additional_fees = 0;
        if( ! empty( $tickets ) && count( $tickets ) > 0 ) {
            foreach( $tickets as $ticket ) {
                $tic_qty = $ticket->qty;
                if( ! empty( $ticket->additional_fee ) ) {
                    foreach( $ticket->additional_fee as $af ) {
                        $price = $af->price;
                        if( $price ) {
                            $additional_fees += $price;
                        }
                    }
                }
            }
        }
        return $this->ep_price_with_position( $additional_fees );
    }
    
    public function ep_calculate_order_total_additional_fees( $tickets ){
        $additional_fees = 0;
        if( ! empty( $tickets ) && count( $tickets ) > 0 ) {
            foreach( $tickets as $ticket ) {
                $tic_qty = $ticket->qty;
                if( ! empty( $ticket->additional_fee ) ) {
                    foreach( $ticket->additional_fee as $af ) {
                        $price = $af->price;
                        if( $price ) {
                            $additional_fees += $price * $tic_qty;
                        }
                    }
                }
            }
        }
        return $this->ep_price_with_position( $additional_fees );
    }

    public function ep_calculate_order_total_offer_price( $tickets ){
        $offer_fees = 0;
        if( ! empty( $tickets ) && count( $tickets ) > 0 ) {
            foreach( $tickets as $ticket ) {
                if( ! empty( $ticket->offer ) ) {
                    $offer_fees += $ticket->offer;
                }
            }
        }
        return $this->ep_price_with_position( $offer_fees );
    }

    public function get_event_ticket_name_by_id_event( $ticket_id, $event_data ) {
        $ticket_name = '';
        if( ! empty( $ticket_id ) && ! empty( $event_data ) ) {
            if( isset( $event_data->all_tickets_data ) && count( $event_data->all_tickets_data ) > 0 ) {
                $all_tickets = $event_data->all_tickets_data;
                foreach( $all_tickets as $ticket ) {
                    if( $ticket->id == $ticket_id ) {
                        $ticket_name = $ticket->name;
                        break;
                    }
                }
            }
        }
        return $ticket_name;
    }

    public function ep_show_free_event_price( $price ) {
        if( ! empty( $this->ep_get_global_settings( 'hide_0_price_from_frontend' ) ) ) {
            esc_html_e( 'Free', 'eventprime-event-calendar-management' );
        } else{
            echo esc_html( $this->ep_price_with_position( $price ) );
        }
    }

    /**
	 * Get ticket by id.
	 * 
	 * @param int $ticket_id Ticket Id.
	 * 
	 * @return object Ticket Data.
	*/
	public function get_event_ticket_by_id( $ticket_id ) {
		$ticket_data = new stdClass();
		if( ! empty( $ticket_data ) ) {
			global $wpdb;
			$ticket_table_name = $wpdb->prefix.'em_price_options';
			$ticket_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $ticket_table_name WHERE `id` = %d", $ticket_id ) );
		}
		return $ticket_data;
	}

    /** Show taxonomy dropdown for the post Metabox
     * 
     * @param object $post Post.
     * 
     * @param array $box Metabox.
     */
        
    public function custom_em_venue_dropdown($post, $box) {
        $taxonomy = 'em_venue';
        $tax = get_taxonomy($taxonomy);
        $name = esc_attr($tax->name);
        $terms = get_terms($taxonomy, array('hide_empty' => false));
        $selected = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
        
        $custom_venue_class = ( $taxonomy == 'em_venue' ) ? 'ep_event_venue_meta_box' : '';
        
        // Output the dropdown
        echo '<div id="taxonomy-'.esc_attr($taxonomy).'" class="selectdiv">';
        echo '<select name="tax_input[' . esc_attr($name) . ']" class="widefat '.esc_attr($custom_venue_class).'">';
        echo '<option value="">Select ' . esc_attr($tax->label) . '</option>';
        foreach ($terms as $term) {
            //$selected = has_term($term->term_id, $taxonomy, $post->ID) ? 'selected' : '';
            //echo '<option value="' . esc_attr($term->term_id) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
            //$venue_data = $this->ep_get_venue_by_id( $term->term_id );
            $em_type = get_term_meta( $term->term_id,'em_type',false);
            $type = (isset($em_type) && !empty($em_type) && isset($em_type[0]))?$em_type[0]:'standings';
            ?>
            <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php echo selected( $term->term_id, count( $selected ) >= 1 ? $selected[0] : '' ); ?> data-term_id="<?php echo esc_attr( $term->term_id );?>" data-type="<?php if ( isset( $type ) )  echo esc_attr( $type );?>" data-event_id="<?php echo esc_attr( $post->ID );?>"><?php echo esc_html( $term->name ); ?></option>
            <?php
        }
        echo '</select> <span class="spinner" id="ep_event_venue_spinner" style="float: none; display: none;"></span></div>';
    }    
        
    public function ep_taxonomy_select_meta_box( $post, $box ) {
        $defaults = array( 'taxonomy' => $box['args']['taxonomy'] );
    
        if ( ! isset( $box['args'] ) || !is_array( $box['args'] ) )
            $args = array();
        else
            $args = $box['args'];
    
        extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
        $tax = get_taxonomy( $taxonomy );
        $selected = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
        $hierarchical = $tax->hierarchical;
        $meta_query =  array(
            'relation'=>'OR',
            array(
                'key'     => 'em_status',
                'value'   => 0,
                'compare' => '!='
            ),
            array(
                'key'     => 'em_status',
                'compare' => 'NOT EXISTS'
            )
        );
        $custom_venue_class = ( $taxonomy == 'em_venue' ) ? 'ep_event_venue_meta_box' : '';?>
        <div id="taxonomy-<?php echo esc_attr($taxonomy); ?>" class="selectdiv"><?php 
            if ( current_user_can( $tax->cap->edit_terms ) ) {
                if( $taxonomy == 'em_venue' ) {?>
                    <select name="tax_input[<?php esc_attr_e($taxonomy);?>][]" class="widefat <?php echo esc_attr( $custom_venue_class );?>">
                        <option value="0"><?php echo esc_html__( 'Select', 'eventprime-event-calendar-management' ). " " . esc_attr($box['title']);?></option>
                        <?php foreach ( get_terms( $taxonomy, array( 'hide_empty' => false, 'meta_query'=>$meta_query ) ) as $term ){
                            $venue_data = $this->ep_get_venue_by_id( $term->term_id ); ?>
                            <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php echo selected( $term->term_id, count( $selected ) >= 1 ? $selected[0] : '' ); ?> data-term_id="<?php echo esc_attr( $term->term_id );?>" data-type="<?php if ( isset( $venue_data->em_type ) )  echo esc_attr( $venue_data->em_type );?>" data-event_id="<?php echo esc_attr( $post->ID );?>"><?php echo esc_html( $term->name ); ?></option>
                        <?php } ?>
                    </select>
                    <span class="spinner" id="ep_event_venue_spinner" style="float: none; display: none;"></span><?php
                } elseif ( $hierarchical ) {
                    wp_dropdown_categories( array(
                        'taxonomy'        => $taxonomy,
                        'class'           => 'widefat',
                        'hide_empty'      => 0, 
                        'name'            => "tax_input[$taxonomy][]",
                        'selected'        => count($selected) >= 1 ? $selected[0] : '',
                        'orderby'         => 'name', 
                        'hierarchical'    => 1, 
                        'meta_query'      => $meta_query,
                        'show_option_all' => esc_html__( 'Select', 'eventprime-event-calendar-management' ). " " . $box['title']
                    ));
                } else { ?>
                    <select name="tax_input[<?php esc_attr_e($taxonomy);?>][]" class="widefat">
                        <option value="0"><?php echo esc_html__( 'Select', 'eventprime-event-calendar-management' ). " " . esc_attr($box['title']);?></option>
                        <?php foreach ( get_terms( $taxonomy, array( 'hide_empty' => false, 'meta_query'=> $meta_query ) ) as $term): ?>
                            <option value="<?php echo esc_attr( $term->slug ); ?>" <?php echo selected($term->term_id, count($selected) >= 1 ? $selected[0] : ''); ?>><?php echo esc_html($term->name); ?></option>
                        <?php endforeach; ?>
                    </select><?php 
                }
            }?>
        </div><?php
    }
    
    // get event booking total
public function ep_get_event_booking_total( $booking ) {
    if( empty( $booking ) ) return;
    $order_info = ( ! empty( $booking->em_order_info ) ? $booking->em_order_info : array() );
    $payment_log = ( ! empty( $booking->em_payment_log ) ? $booking->em_payment_log : array() );
    if( empty( $booking->em_old_ep_booking ) ) {
        return esc_html( $this->ep_price_with_position( $order_info['booking_total'] ) );
    } else{
        if( ! empty( $order_info['booking_total'] ) ) {
            return esc_html( $this->ep_price_with_position( $order_info['booking_total'] ) );
        } else{
            if(isset($order_info['item_price']) && !empty($order_info['item_price'])){
                $after_discount_price = ($order_info['item_price'] * $order_info['quantity']) - $order_info['discount'];
                if(isset($order_info['fixed_event_price']) && !empty($order_info['fixed_event_price'])){
                    $after_discount_price += $order_info['fixed_event_price'];
                }
                $after_discount_price = apply_filters('event_magic_booking_get_final_price', $after_discount_price, $order_info);
                // coupon code section
                if(isset($order_info['coupon_discount']) && !empty($order_info['coupon_discount'])){
                    $after_discount_price = $after_discount_price - $order_info['coupon_discount'];
                }
            }
            $total_amount = (!empty($payment_log) && isset($payment_log['total_amount']) ? $payment_log['total_amount'] : (isset($order_info['subtotal']) ? $order_info['subtotal'] : '') );
            if( !empty( $payment_log ) && isset( $payment_log['payment_gateway'] ) && $payment_log['payment_gateway'] == 'none' && !isset( $payment_log['total_amount'] ) ) {
                $total_amount = 0;
            }
            return ( !empty( $total_amount ) ? $this->ep_price_with_position( $total_amount ) : $this->ep_price_with_position( $after_discount_price ) );
        }
    }
}

public function ep_get_events( $fields ) {
		$events_data = array();
                if( !empty( $fields ) ) {
			$events_data = $this->get_events_field_data( $fields );
		}

		return $events_data;
    }
    
    public function get_booking_qr_code( $booking ) {
		$image_url = '';
		if( ! empty( $booking ) ) {
			$url = get_permalink( $this->ep_get_global_settings( 'booking_details_page' ) );
			$url = add_query_arg( 'order_id', $booking->em_id, $url );
                        $url = apply_filters('ep_booking_qr_code_url', $url, $booking);
			$file_name = 'ep_qr_'.md5($url).'.png';
			$upload_dir = wp_upload_dir();
			$file_path = $upload_dir['basedir'] . '/ep/' . $file_name;
			if( ! file_exists( $file_path ) ) {
				if( ! file_exists( dirname( $file_path ) ) ){
					mkdir( dirname( $file_path ), 0755 );
				}
				require_once 'lib/qrcode.php';
				$qrCode = new QRcode();
				$qrCode->png( $url, $file_path, 'M', 4, 2 );
			}
			$image_url = esc_url( $upload_dir['baseurl'].'/ep/'.$file_name );
		}
		return $image_url;
	}


        public function get_featured_event_organizers($count = 5){
            $args = array( 
                'hide_empty' => false ,
                'number'     => $count,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'relation' => 'OR',
                        array(
                           'key'       => 'em_is_featured',
                           'value'     => 1,
                           'compare'   => '='
                        ),
                        array(
                           'key'       => 'em_is_featured',
                           'value'     => true,
                           'compare'   => '='
                        )
                    ),
                    array(
                        'relation'=>'OR',
                        array(
                            'key'     => 'em_status',
                            'value'   => 0,
                            'compare' => '!='
                        ),
                        array(
                            'key'     => 'em_status',
                            'compare' => 'NOT EXISTS'
                        )
                    )
                )
            );
            $terms      = get_terms('em_event_organizer', $args );
            $organizers = array();
            if( empty( $terms ) || is_wp_error( $terms ) ){
               return $organizers;
            }
            foreach( $terms as $term ){
                $organizer = $this->get_single_organizer( $term->term_id, $term );
                if( ! empty( $organizer ) ) {
                    $organizers[] = $organizer;
                }
            }

            $wp_query        = new WP_Term_Query( $args );
            $wp_query->terms = $organizers;
            return $wp_query;
        }
        public function get_featured_event_performers( $args = array(), $number = -1 ) {
        $default = array(
            'post_type'        => 'em_performer',
            'post_status'      => 'publish',
            'numberposts'      => $number,
            'meta_query'       => array(    
                'relation'     => 'AND',
                array(
                    'relation' => 'OR',
                    array(
                        'key'      => 'em_display_front',
                        'value'    => 1,
                        'compare'  => '='
                    ),
                    array(
                        'key'      => 'em_display_front',
                        'value'    => 'true',
                        'compare'  => '='
                    )
                ),
                array(
                    'key'      => 'em_is_featured',
                    'value'    => 1,
                    'compare'  => '='
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key'      => 'em_status',
                        'compare'  => 'NOT EXISTS'
                    ),
                    array(
                        'key'      => 'em_status',
                        'value'    => 0,
                        'compare'  => '!='
                    ),
                ),
            )
        );
        $default = apply_filters( 'ep_performers_render_argument', $default, $args );
        $args = wp_parse_args( $args, $default );
        $posts = get_posts( $args );
        if( empty( $posts ) )
           return array();
       
        $performers = array();
        foreach( $posts as $post ) {
            $performer = $this->get_single_performer( $post->ID, $post );
            if( ! empty( $performer ) ) {
                $performers[] = $performer;
            }
        }

        $wp_query = new WP_Query( $args );
        $wp_query->posts = $performers;

        return $wp_query;
    }
    
    public function get_featured_event_types( $count = 5 ){
        $args = array( 
            'hide_empty' => false ,
            'number'=>$count,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                   'key'       => 'em_is_featured',
                   'value'     => 1,
                   'compare'   => '='
                ),
                array(
                   'key'       => 'em_is_featured',
                   'value'     => true,
                   'compare'   => '='
                )
            )
        );
        $terms       = get_terms('em_event_type', $args );
        $event_types = array();
        if( empty( $terms ) || is_wp_error( $terms ) ){
           return $event_types;
        }
        foreach( $terms as $term ){
            $event_type = $this->get_single_event_type( $term->term_id, $term );
            if( ! empty( $event_type ) ) {
                $event_types[] = $event_type;
            }
        }

        $wp_query        = new WP_Term_Query( $args );
        $wp_query->terms = $event_types;
        return $wp_query;
    }

    public function get_featured_event_venues($count = 5){
        $args = array( 
            'hide_empty' => false ,
            'number'=>$count,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array(
                       'key'       => 'em_is_featured',
                       'value'     => 1,
                       'compare'   => '='
                    ),
                    array(
                       'key'       => 'em_is_featured',
                       'value'     => true,
                       'compare'   => '='
                    )
                ),
                array(
                    'relation'=>'OR',
                    array(
                      'key'     => 'em_status',
                      'value'   => 0,
                      'compare' => '!='
                    ),
                    array(
                      'key'     => 'em_status',
                      'compare' => 'NOT EXISTS'
                    )
                )
            )
        );
        $terms       = get_terms( 'em_venue', $args );
        $venues = array();
        if( empty( $terms ) || is_wp_error( $terms ) ){
           return $venues;
        }
        foreach( $terms as $term ){
            $venue = $this->get_single_venue( $term->term_id, $term );
            if( ! empty( $venue ) ) {
                $venues[] = $venue;
            }
        }

        $wp_query        = new WP_Term_Query( $args );
        $wp_query->terms = $venues;
        return $wp_query;
    }
    
    public function get_popular_event_organizers( $count = 5, $featured = 0 ){
        $args = array( 
            'hide_empty' => true ,
            'number' => $count,
            'orderby' => 'count', 
            'order' => 'DESC',
            'meta_query' => array(
                'relation'=>'OR',
                array(
                    'key'     => 'em_status',
                    'value'   => 0,
                    'compare' => '!='
                ),
                array(
                    'key'     => 'em_status',
                    'compare' => 'NOT EXISTS'
                )
            )
        );

        if( $featured == 1 ){
            $args['post_status'] = 'publish';
            $args['meta_query'] = array(
                'relation'=>'AND',
                array(
                    'relation' => 'OR',
                    array(
                       'key'       => 'em_is_featured',
                       'value'     => 1,
                       'compare'   => '='
                    ),
                    array(
                       'key'       => 'em_is_featured',
                       'value'     => true,
                       'compare'   => '='
                    )
                ),
                array(
                    'relation'=>'OR',
                    array(
                        'key'     => 'em_status',
                        'value'   => 0,
                        'compare' => '!='
                    ),
                    array(
                        'key'     => 'em_status',
                        'compare' => 'NOT EXISTS'
                    )
                )
            );           
        }  

        $terms = get_terms( 'em_event_organizer', $args );

        // check no of events in event organizers
        $events = $this->get_events_post_data();
        $event_count= array();
        if( isset( $events->posts ) && ! empty( $events->posts ) ){
            foreach( $events->posts as $event ){
                if( isset( $event->em_organizer ) && ! empty( $event->em_organizer ) ){
                    foreach( $event->em_organizer as $organizer_id ){
                        if( isset( $event_count[$organizer_id] ) ){
                            $event_count[$organizer_id] += 1;
                        }
                        else{
                            $event_count[$organizer_id]= 1;
                        }
                    }
                }
            }
        }

        $organizers = array();
        if( empty( $terms ) || is_wp_error( $terms ) ){
           return $organizers;
        }

        foreach( $terms as $term ){
            if( isset( $event_count[$term->term_id] ) ){
                $organizer = $this->get_single_organizer( $term->term_id, $term );
                $organizer->events = $event_count[$term->term_id];
                if( isset( $organizer ) && ! empty( $organizer ) ) {
                    $organizers[] = $organizer;
                }
            }   
        }
        
        $wp_query        = new WP_Term_Query( $args );
        $wp_query->terms = $organizers;
        return $wp_query;
    }
    public function get_popular_event_performers($posts_per_page = 5, $featured = 0, $offset = 0) {
        $args = array();
        if( $featured == 1 ) {
            $args = array(
                'meta_query'       => array(    
                    'relation'     => 'AND',
                    array(
                        'key'      => 'em_display_front',
                        'value'    => 1,
                        'compare'  => '='
                    ),
                    array(
                        'key'   => 'em_is_featured',
                        'value' => 1
                    ),
                    array(
                    'relation' => 'OR',
                    array(
                        'key'      => 'em_status',
                        'compare'  => 'NOT EXISTS'
                    ),
                    array(
                        'key'      => 'em_status',
                        'value'    => 0,
                        'compare'  => '!='
                    ),
                ),
                    
                )
            );
        }
        $args = apply_filters( 'ep_performers_render_argument', $args, array() );
        $performers = $this->get_performers_post_data($args);
        $events = $this->get_events_post_data(array(),false);
        $event_count = array();
        if(isset($events->posts) && !empty($events->posts)){
            foreach($events->posts as $event){
                if(isset($event->em_performer) && !empty($event->em_performer)){
                    foreach($event->em_performer as $performer_id){
                        if(isset($event_count[$performer_id])){
                            $event_count[$performer_id] +=1;
                        }
                        else{
                            $event_count[$performer_id]= 1;
                        }
                    }
                }
            }
        }
        
        $p_performers = array();
        if ( !empty($performers->posts) ) {
            foreach($performers->posts as $performer){
                if(isset($event_count[$performer->id])){
                    $performer->events= $event_count[$performer->id];
                    $p_performers[] = $performer;
                } else{
                    $performer->events=0;
                }
                
            }
        }
        
        // Sort performers by event count in descending order
        $p_performers = wp_list_sort($p_performers, 'events', 'DESC', false);

        // Apply pagination (slice the array)
        $paged_performers = array_slice($p_performers, $offset, $posts_per_page);

        // Prepare the result object
        $result = new stdClass();
        $result->posts = $paged_performers;
        $result->max_num_pages = ceil(count($p_performers) / $posts_per_page);

        return $result;
        
//        $p_performers = wp_list_sort( $p_performers , 'events', 'DESC',  false );
//        $pp = new stdClass();
//        // $pp->max_num_pages = count($p_performers);
//        if(count($p_performers) > $posts_per_page){
//            $p_performers = array_slice($p_performers, $offset, $posts_per_page);
//        }
//        $pp->posts = $p_performers;
//        $pp->max_num_pages = count($performers->posts);
//   
//        return $pp;
    }
    
    public function get_popular_event_types( $count = 5, $featured = 0 ){
        $args = array( 
            'hide_empty' => false ,
            'number'=> $count,
            'orderby' => 'count', 
            'order' => 'DESC'
        );
        if( $featured == 1 ){
            $args['post_status'] = 'publish';
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                   'key'       => 'em_is_featured',
                   'value'     => 1,
                   'compare'   => '='
                ),
                array(
                   'key'       => 'em_is_featured',
                   'value'     => true,
                   'compare'   => '='
                )
            );           
        }  

        $terms = get_terms( 'em_event_type', $args );
        // check no of events in event types
        $events = $this->get_events_post_data();
        $event_count= array();
        if( isset( $events->posts ) && ! empty( $events->posts ) ){
            foreach( $events->posts as $event ){
                if( isset( $event->em_event_type ) && ! empty( $event->em_event_type ) ){
                    $event_type = $this->ep_get_filter_taxonomy_id($event->em_event_type);
                    if( isset( $event_count[$event_type] ) ){
                        $event_count[$event_type] += 1;
                    }else{
                        $event_count[$event_type] = 1;
                    }
                }
            }
        }
       
        $event_types = array();
        if( empty( $terms ) || is_wp_error( $terms ) ){
           return $event_types;
        }
        foreach( $terms as $term ){
            if( isset( $event_count[$term->term_id] ) ){
                $event_type = $this->get_single_event_type( $term->term_id, $term );
                $event_type->events = $event_count[$term->term_id];
                if( isset( $event_type ) && ! empty( $event_type ) ) {
                    $event_types[] = $event_type;
                }
            }   
        }

        $wp_query = new WP_Term_Query( $args );
        $wp_query->terms = $event_types;
        return $wp_query;
    }
    
    public function get_popular_event_venues( $count = 5, $featured = 0 ){
        $args = array( 
            'hide_empty' => true ,
            'number'=> $count,
            'orderby' => 'count', 
            'order' => 'DESC',
            'meta_query' => array(
                'relation'=>'OR',
                array(
                  'key'     => 'em_status',
                  'value'   => 0,
                  'compare' => '!='
                ),
                array(
                  'key'     => 'em_status',
                  'compare' => 'NOT EXISTS'
                )
            )
        );
        if( $featured == 1 ){
            $args['post_status'] = 'publish';
            $args['meta_query'] = array(
                'relation'=>'AND',
                array(
                    'relation' => 'OR',
                    array(
                       'key'       => 'em_is_featured',
                       'value'     => 1,
                       'compare'   => '='
                    ),
                    array(
                       'key'       => 'em_is_featured',
                       'value'     => true,
                       'compare'   => '='
                    )
                ),
                array(
                    'relation'=>'OR',
                    array(
                      'key'     => 'em_status',
                      'value'   => 0,
                      'compare' => '!='
                    ),
                    array(
                      'key'     => 'em_status',
                      'compare' => 'NOT EXISTS'
                    )
                )
            );           
        }
        
        $terms       = get_terms( 'em_venue', $args );

        // check no of events in event venues
        $events = $this->get_events_post_data();
        $event_count= array();
        if( isset( $events->posts ) && ! empty( $events->posts ) ){
            foreach( $events->posts as $event ){
                if( isset( $event->em_venue ) && ! empty( $event->em_venue ) ){
                    $event_venue = $this->ep_get_filter_taxonomy_id($event->em_venue);
                    
                    if(!empty($event_count) && isset( $event_count[$event_venue] ) ){
                        $event_count[$event_venue] += 1;
                    }else{
                        $event_count[$event_venue] = 1;
                    }
                }
            }
        }

        $venues = array();
        if( empty( $terms ) || is_wp_error( $terms ) ){
           return $venues;
        }
        foreach( $terms as $term ){
            if( isset( $event_count[$term->term_id] ) ){
                $venue = $this->get_single_venue( $term->term_id, $term );
                $venue->events = $event_count[$term->term_id];
                if( isset( $venue ) && ! empty( $venue ) ) {
                    $venues[] = $venue;
                }
            }   
        }

        $wp_query        = new WP_Term_Query( $args );
        $wp_query->terms = $venues;
        return $wp_query;
    }
    
    // old extension data
    public function ep_old_ext_data() {
        $old_exts_lists = array( 
            'event-seating.php'               => 'Live Seating', 
            'event-analytics.php'             => 'Event Analytics',
            'event-sponser.php'               => 'Event Sponsors', 
            'event-stripe.php'                => 'Stripe Payments',
            'eventprime-offline.php'          => 'Offline Payments', 
            'eventprime-recurring-events.php' => 'Recurring Events',
            'eventprime-attendees-list.php'   => 'Attendees List',
            'event-coupons.php'               => 'Coupon Codes',
            'event-guest-booking.php'         => 'Guest Bookings',
            'eventprime-more-widgets.php'     => 'Event List Widgets', 
            'event-attendees-booking.php'     => 'Admin Attendee Bookings',
            'event-wishlist.php'              => 'Event Wishlist',
            'eventprime-event-comments.php'   => 'Event Comments',
            'automatic-discounts.php'         => 'Event Automatic Discounts',
            'google-import-export.php'        => 'Google Events Import Export',
            'events-import-export.php'        => 'Events Import Export', 
            'eventprime-mailpoet.php'         => 'EventPrime MailPoet', 
            'woocommerce-integration.php'     => 'WooCommerce Integration',
            'eventprime-zoom-meetings.php'    => 'EventPrime Zoom Integration',
            'event-zapier.php'                => 'Zapier Integration',
            'event-invoices.php'              => 'EventPrime Invoices',
            'sms-integration.php'             => 'Twilio Text Notifications'
        );

        return $old_exts_lists;
    }
    
    public function get_user_wise_upcoming_bookings( $user_id ) {
		$upcoming_bookings = array();
		if( ! empty( $user_id ) ) {
			$booking_controller = new EventPrime_Bookings;
			$upcoming_bookings = $booking_controller->get_user_upcoming_bookings( $user_id );
		}
		return $upcoming_bookings;
	}
        
        public function get_user_wishlisted_events( $user_id ) {
		$all_events = array();
		if( ! empty( $user_id ) ) {
			$wishlist_meta = get_user_meta( $user_id, 'ep_wishlist_event', true );
			if( ! empty( $wishlist_meta ) ) {
				foreach( $wishlist_meta as $event_id => $wishlist ) {
					if( ! empty( $event_id ) ) {
						$event = $this->get_single_event( $event_id );
						if( ! empty( $event ) && ! empty( $event->id ) ) {
							$booking_controller = new EventPrime_Bookings;
							$check_booking_id = $booking_controller->check_event_booking_by_user( $event_id, $user_id );
							$event_data = array( 'event' => $event, 'booking' => $check_booking_id );
							$all_events[] = $event_data;
						}
					}
				}
			}
		}
		return $all_events;
	}
        
        public function get_user_submitted_events( $user_id ) {
		$all_events = array();
		if( ! empty( $user_id ) ) {
			$args = array(
                'numberposts' => -1,
                'orderby'     => 'date',
                'order'       => 'DESC',
                'author' 	  => $user_id,
				'post_status' => 'any',
                'meta_query'  => array(
                    array(
                        'key'     => 'em_user_submitted', 
                        'value'   => 1, 
                        'compare' => '=', 
                        'type'    => 'NUMERIC,'
                    )
                ),
                'post_type'   => 'em_event'
            );
            $events = get_posts( $args );
			if( ! empty( $events ) && count( $events ) > 0 ) {
				foreach( $events as $event ) {
					$event_data = $this->get_single_event( $event->ID );
					$all_events[] = $event_data;
				}
			}
		}
		return $all_events;
	}
        
        /**
        * Get greeting text
        */
       public function ep_get_greeting_text() {
           $hour = gmdate('H');
           $greet = esc_html__( 'Good ', 'eventprime-event-calendar-management' );
           $greet .= ( $hour >= 17 ) ? esc_html__( 'Evening', 'eventprime-event-calendar-management' ) : ( ( $hour >= 12 ) ? esc_html__( 'Afternoon', 'eventprime-event-calendar-management' ) : esc_html__( 'Morning', 'eventprime-event-calendar-management' ) );
           return $greet;
       }  
        /**
        * Get venue type label
        */
       public function ep_get_venue_type_label( $type='standings' ) {
           if( $type == 'seats' ) {
               return 'Seating';
           } else{
               return 'Standing';
            }
        }
        
         public function event_submission_enqueue_style(){
        wp_enqueue_style(
			'em-admin-select2-css',
			plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/select2.min.css',
			false, EVENTPRIME_VERSION
		);
	
        wp_enqueue_style(
            'ep-user-views-css',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-views.css',
            false, EVENTPRIME_VERSION
        );
    }
    public function event_submission_enqueue_script(){
        wp_enqueue_script(
            'em-public-jscolor',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/jscolor.min.js',
            array( 'jquery' ), EVENTPRIME_VERSION
        );
        wp_enqueue_script(
            'em-admin-select2-js',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/select2.full.min.js',
            array( 'jquery' ), EVENTPRIME_VERSION
		);
        wp_enqueue_script(
		    'em-admin-timepicker-js',
		    plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/jquery.timepicker.min.js',
		    false, EVENTPRIME_VERSION
        );
        wp_enqueue_script(
            'ep-front-events-fes-js',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/ep-frontend-event-submission.js',
            array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-dialog', 'jquery-ui-accordion', 'jquery-ui-sortable', 'jquery-ui-slider' ), EVENTPRIME_VERSION
        );
        
        wp_enqueue_style(
		    'em-admin-jquery-ui',
		    plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/jquery-ui.min.css',
		    false, EVENTPRIME_VERSION
        );
        // Ui Timepicker css
	    wp_enqueue_style(
		    'em-admin-jquery-timepicker',
		    plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/jquery.timepicker.min.css',
		    false, EVENTPRIME_VERSION
        );
        wp_enqueue_script(
            'ep-moment-js',
            plugin_dir_url(EP_PLUGIN_FILE) . 'public/js/moment.min.js',
            array('jquery'), EVENTPRIME_VERSION
        );

        $required_fields = new stdClass();
        $fields = $this->ep_get_global_settings( 'frontend_submission_required' );

        // Required fields from global fes settings. 
        if(!empty($fields) && is_array($fields)){
            foreach($fields as $key => $field){
                $required_fields->$key = $field;
            }
        }

        wp_localize_script(
            'ep-front-events-fes-js', 
            'eventprime_frontend_submission',
            array(
                'datepicker_format' => $this->ep_get_global_settings( 'datepicker_format' ),
                'ajaxurl'   => admin_url('admin-ajax.php'),
            ),
        );

        wp_localize_script(
            'ep-front-events-fes-js', 
            'em_event_fes_object', 
            array(
                'before_event_scheduling' => esc_html__( 'Please choose start & end date before enable scheduling!', 'eventprime-event-calendar-management' ),
                'before_event_recurrence' => esc_html__( 'Please choose start & end date before enable recurrence!', 'eventprime-event-calendar-management' ),
                'add_schedule_btn'  	  => esc_html__( 'Add New Hourly Schedule', 'eventprime-event-calendar-management' ),
                'add_day_title_label'  	  => esc_html__( 'Title', 'eventprime-event-calendar-management' ),
                'start_time_label'  	  => esc_html__( 'Start Time', 'eventprime-event-calendar-management' ),
                'end_time_label'  	      => esc_html__( 'End Time', 'eventprime-event-calendar-management' ),
                'description_label'  	  => esc_html__( 'Description', 'eventprime-event-calendar-management' ),
                'remove_label'  	      => esc_html__( 'Remove', 'eventprime-event-calendar-management' ),
                'material_icons'          => $this->get_material_icons(),
                'icon_text'  	   	      => esc_html__( 'Icon', 'eventprime-event-calendar-management' ),
                'icon_color_text'  	      => esc_html__( 'Icon Color', 'eventprime-event-calendar-management' ),
                'additional_date_text' 	  => esc_html__( 'Date', 'eventprime-event-calendar-management' ),
                'additional_time_text' 	  => esc_html__( 'Time', 'eventprime-event-calendar-management' ),
                'optional_text' 	      => esc_html__( '(Optional)', 'eventprime-event-calendar-management' ),
                'additional_label_text'   => esc_html__( 'Label', 'eventprime-event-calendar-management' ),
                'countdown_activate_text' => esc_html__( 'Activates', 'eventprime-event-calendar-management' ),
                'countdown_activated_text'=> esc_html__( 'Activated', 'eventprime-event-calendar-management' ),
                'countdown_on_text'	      => esc_html__( 'On', 'eventprime-event-calendar-management' ),
                'countdown_ends_text'     => esc_html__( 'Ends', 'eventprime-event-calendar-management' ),
                'countdown_activates_on'  => array( 'right_away' => esc_html__( 'Right Away', 'eventprime-event-calendar-management' ), 'custom_date' => esc_html__( 'Custom Date', 'eventprime-event-calendar-management' ), 'event_date' => esc_html__( 'Event Date', 'eventprime-event-calendar-management' ), 'relative_date' => esc_html__( 'Relative Date', 'eventprime-event-calendar-management' ) ),
                'countdown_days_options'  => array( 'before' => esc_html__( 'Days Before', 'eventprime-event-calendar-management' ), 'after' => esc_html__( 'Days After', 'eventprime-event-calendar-management' ) ),
                'countdown_event_options' => array( 'event_start' => esc_html__( 'Event Start', 'eventprime-event-calendar-management' ), 'event_ends' => esc_html__( 'Event Ends', 'eventprime-event-calendar-management' ) ),
                'ticket_capacity_text'    => esc_html__( 'Capacity', 'eventprime-event-calendar-management' ),
                'add_ticket_text'    	  => esc_html__( 'Add Ticket Type', 'eventprime-event-calendar-management' ),
                'add_text'                => esc_html__( 'Add', 'eventprime-event-calendar-management' ),
                'edit_text'    	  	      => esc_html__( 'Edit', 'eventprime-event-calendar-management' ),
                'update_text'    	      => esc_html__( 'Update', 'eventprime-event-calendar-management' ),
                'add_ticket_category_text'=> esc_html__( 'Add Tickets Category', 'eventprime-event-calendar-management' ),
                'price_text'              => esc_html__( 'Fee Per Ticket', 'eventprime-event-calendar-management' ),
                'offer_text'		      => esc_html__( 'Offer', 'eventprime-event-calendar-management' ),
                'no_ticket_found_error'   => esc_html__( 'Booking will be turn off if no ticket found. Are you sure you want to continue?', 'eventprime-event-calendar-management' ),
                'max_capacity_error'      => esc_html__( 'Max allowed capacity is', 'eventprime-event-calendar-management' ),
                'max_less_then_min_error' => esc_html__( 'Maximum tickets number can\'t be less then minimum tickets number.', 'eventprime-event-calendar-management' ),
                'required_text'		      => esc_html__( 'Required', 'eventprime-event-calendar-management' ),
                'one_checkout_field_req'  => esc_html__( 'Please select atleast one attendee field.', 'eventprime-event-calendar-management' ),
                'no_name_field_option'    => esc_html__( 'Please select name field option.', 'eventprime-event-calendar-management' ),
                'some_issue_found'    	  => esc_html__( 'Some issue found. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ),
                'fixed_field_not_selected'=> esc_html__( 'Please selecte fixed field.', 'eventprime-event-calendar-management' ),
                'fixed_field_term_option_required'=> esc_html__( 'Please select one terms option.', 'eventprime-event-calendar-management' ),
                'repeat_child_event_prompt'=> esc_html__( 'This event have multiple child events. They will be deleted after update event.', 'eventprime-event-calendar-management' ),
                'empty_event_title'       => esc_html__( 'Event title is required.', 'eventprime-event-calendar-management' ),
                'empty_start_date'        => esc_html__( 'Event start date is required.', 'eventprime-event-calendar-management' ),
                'end_date_less_from_start'=> esc_html__( 'Event end date can not be less than event start date.', 'eventprime-event-calendar-management' ),
                'event_required_fields'   => $required_fields,
                'event_name_error'        => esc_html__( 'Event Name can not be empty.', 'eventprime-event-calendar-management' ),
                'event_desc_error'        => esc_html__( 'Event Description can not be empty.', 'eventprime-event-calendar-management' ),
                'event_start_date_error'  => esc_html__( 'Event start date can not be empty.', 'eventprime-event-calendar-management' ),
                'event_end_date_error'    => esc_html__( 'Event end date can not be empty.', 'eventprime-event-calendar-management' ),
                'event_custom_link_error' => esc_html__( 'Event Url can not be empty.', 'eventprime-event-calendar-management' ),
                'event_custom_link_val_error' => esc_html__( 'Please enter valid url.', 'eventprime-event-calendar-management' ),
                'event_type_error'        => esc_html__( 'Please Select Event Types.', 'eventprime-event-calendar-management' ),
                'event_type_name_error'   => esc_html__( 'Event Type name can not be empty.', 'eventprime-event-calendar-management' ),
                'event_venue_error'       => esc_html__( 'Please Select Event Venues.', 'eventprime-event-calendar-management' ),
                'event_venue_name_error'  => esc_html__( 'Event Venues name can not be empty.', 'eventprime-event-calendar-management' ),
                'event_performer_error'   => esc_html__( 'Please Select Event Performers.', 'eventprime-event-calendar-management' ),
                'event_performer_name_error' => esc_html__( 'Event Perfomer name can not be empty.', 'eventprime-event-calendar-management' ),
                'event_organizer_error'   => esc_html__( 'Please Select Event Organizers.', 'eventprime-event-calendar-management' ),
                'event_organizer_name_error' => esc_html__( 'Event Organizer name can not be empty.', 'eventprime-event-calendar-management' ),
                'fes_nonce'               => wp_create_nonce( 'ep-frontend-event-submission-nonce' ),
                'choose_image_label'      => esc_html__( 'Choose Image', 'eventprime-event-calendar-management' ),
                'use_image_label'         => esc_html__( 'Use Image', 'eventprime-event-calendar-management' ),
            )
        );
        $gmap_api_key = $this->ep_get_global_settings('gmap_api_key');
        if ($gmap_api_key):
            $gmap_uri = 'https://maps.googleapis.com/maps/api/js?key=' . $gmap_api_key . '&libraries=places';
        else:
            $gmap_uri = false;
        endif;
        
    }
    
    public function get_performer_all_data( $args = array() ) {
        $default = array(
            'orderby'          => 'title',
            'numberposts'      => -1,
            'offset'           => 0,     
            'order'            => 'ASC',
            'post_type'        => 'em_performer',
            'post_status'      => 'publish',
            'meta_query'       => array(    
                'relation'     => 'AND',
                array(
                    'relation' => 'OR',
                    array(
                        'key'      => 'em_status',
                        'compare'  => 'NOT EXISTS'
                    ),
                    array(
                        'key'      => 'em_status',
                        'value'    => 0,
                        'compare'  => '!='
                    ),
                ),
                
                array(
                    'relation'     => 'OR',
                    array(
                        'key'      => 'em_display_front',
                        'value'    => 1,
                        'compare'  => '='
                    ),
                    array(
                        'key'      => 'em_display_front',
                        'value'    => 'true',
                        'compare'  => '='
                    ),
                )
            )
        );
        $default = apply_filters( 'ep_performers_render_argument', $default, $args );
        $args = wp_parse_args( $args, $default );
        $posts = get_posts( $args );
        return $posts;
    }
    
    public function ep_form_field_generator( $fields, $required = 0 ){
        $field_types = $this->ep_get_core_checkout_fields();
        $core_field_types = array_keys( $field_types );
        $input_name = $this->ep_get_slug_from_string( $fields->label );
        if ( in_array( $fields->type, $core_field_types ) ) {?>
            <div class="ep-mb-3">
                <label for="name" class="form-label ep-text-small"><?php
                    echo esc_html( $fields->label );
                    if ($required) {?>
                        <span class="ep-form-fields-required">
                            <?php echo esc_html('*'); ?>
                        </span><?php
                    }?>
                </label>
                <input name="ep_form_fields[<?php echo esc_attr($fields->id); ?>][label]" type="hidden" value="<?php echo esc_attr($fields->label); ?>">
                <input name="ep_form_fields[<?php echo esc_attr($fields->id); ?>][<?php echo esc_attr($input_name); ?>]"
                    type="<?php echo esc_attr($fields->type); ?>"
                    class="ep-form-control"
                    id="ep_form_fields_<?php echo esc_attr($fields->id); ?>_<?php echo esc_attr($input_name); ?>"
                    placeholder="<?php echo esc_attr($fields->label); ?>"
                    <?php
                    if ($required) {
                        echo 'required="required"';
                    }?>
                >
                <div class="ep-error-message" id="ep_form_fields_<?php echo esc_attr($fields->id); ?>_<?php echo esc_attr($input_name); ?>_error"></div>
            </div><?php
        }else{
            if( class_exists('Eventprime_Advanced_Checkout_Fields' ) ){
                $adv_acf_controller = new EventM_Advanced_Checkout_Fields_Controller;
                $adv_acf_controller->ep_advcance_form_field_generator($fields, $required);
            }
        }
    }
       
       

    public function ep_get_available_tickets($event, $ticket){
        if(isset($event->all_bookings))
        {
            $all_event_bookings = $this->get_event_booking_by_event_id( $event->em_id, true,$event->all_bookings );
        }
        else
        {
            $all_event_bookings = $this->get_event_booking_by_event_id( $event->em_id, true );
        }
        $remaining_caps = $ticket->capacity;
        $booked_tickets_data = $all_event_bookings['tickets'];
        if( ! empty( $booked_tickets_data ) ) {
            if( isset( $booked_tickets_data[$ticket->id] ) && ! empty( $booked_tickets_data[$ticket->id] ) ) {
                $booked_ticket_qty = absint( $booked_tickets_data[$ticket->id] );
                if( $booked_ticket_qty > 0 ) {
                    $remaining_caps = $ticket->capacity - $booked_ticket_qty;
                    if( $remaining_caps < 1 ) {
                        $remaining_caps = 0;
                    }
                }
            }
        }
        $remaining_caps = apply_filters('ep_update_remaining_capacity', $remaining_caps, $event, $ticket);
        return $remaining_caps;
    }
    
    public function ep_validate_seating_tickets($event, $ticket_data){
        $event_seat_data = get_post_meta( $event->em_id, 'em_seat_data', true );
        $seat_availibility = array();
        if( ! empty( $event_seat_data ) ) {
        $event_seat_data = maybe_unserialize( $event_seat_data );
            foreach( $ticket_data as $tickets ) {
                if( ! empty( $tickets->seats ) ) {
                    $ticket_seats = $tickets->seats;
                    foreach( $ticket_seats as $seats_data ) {
                        $ticket_area_id = $seats_data->area_id;
                        $area_seat_data = $event_seat_data->{$ticket_area_id};
                        if( $area_seat_data ) {
                            $ticket_seat_data = $seats_data->seat_data;
                            if( ! empty( $ticket_seat_data ) ) {
                                foreach( $ticket_seat_data as $tsd ) {
                                    if( ! empty( $tsd->uid ) ) {
                                        $seat_uid = $tsd->uid;
                                        $seat_uid = explode( '-', $seat_uid );
                                        $row_index = $seat_uid[0];
                                        $col_index = $seat_uid[1];

                                        if( ! empty( $area_seat_data->seats[$row_index][$col_index] ) ) {
                                            $seat_type = $area_seat_data->seats[$row_index][$col_index]->type;
                                            $seat_availibility[] = array( 'uid' => $tsd->uid, 'seat' => $tsd->seat, 'area' => $ticket_area_id, 'status'=>$seat_type );                            
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $seat_availibility;
    }
    
    public function eventprime_get_event_booking_stats_by_event_id($event_id,$all_bookings='')
    {
        $total_booking = 0;
        $total_attendees = 0;
        if( ! empty( $event_id ) ) 
        {
            if($all_bookings=='')
            {
                $booking_controller = new EventPrime_Bookings;
                $all_bookings = $booking_controller->get_event_bookings_by_event_id( $event_id );
            }
            $total_booking = !empty($all_bookings)?count( $all_bookings ):0;
            if( ! empty( $all_bookings ) ) {
                foreach( $all_bookings as $booking ) {
                        $booking_data = get_post_meta($booking->ID,'em_order_info',true);
                        if( ! empty( $booking_data ) ) {
                                if( isset( $booking_data['tickets'] ) && ! empty( $booking_data['tickets'] ) ) {
                                        $booked_tickets = $booking_data['tickets'];
                                        foreach( $booked_tickets as $ticket ) {
                                                if( ! empty( $ticket->id ) && ! empty( $ticket->qty ) ) {
                                                        $total_attendees += $ticket->qty;
                                                }
                                        }
                                } else if( isset( $booking_data['order_item_data'] ) && ! empty( $booking_data['order_item_data'] ) ) {
                                        $booked_tickets = $booking_data['order_item_data'];
                                        foreach( $booked_tickets as $ticket ) {
                                                if( isset( $ticket->quantity ) ) {
                                                        $total_attendees += $ticket->quantity;
                                                } else if( isset( $ticket->qty ) ) {
                                                        $total_attendees += $ticket->qty;
                                                }
                                        }
                                }
                        }
                }
        }
        }
        return array('total_booking'=>$total_booking,'total_attendees'=>$total_attendees);
    }
            

    public function get_total_booking_number_by_event_id( $event_id,$all_bookings = null) {
		$total_booking = 0;
		if( ! empty( $event_id ) ) {
                        
                            $booking_controller = new EventPrime_Bookings;
                            if(empty($all_bookings))
                            {
                                $all_bookings = $booking_controller->get_event_bookings_by_event_id( $event_id );
                            }
			if( ! empty( $all_bookings ) ) {
				foreach( $all_bookings as $booking ) {
					//$booking_data = $booking_controller->load_booking_detail( $booking->ID, false );
                                        $booking_data = get_post_meta($booking->ID,'em_order_info',true);
                                        //print_r($booking_data);
					if( ! empty( $booking_data ) ) {
						if( isset( $booking_data['tickets'] ) && ! empty( $booking_data['tickets'] ) ) {
							$booked_tickets = $booking_data['tickets'];
							foreach( $booked_tickets as $ticket ) {
								if( ! empty( $ticket->id ) && ! empty( $ticket->qty ) ) {
									$total_booking += $ticket->qty;
								}
							}
						} else if( isset( $booking_data['order_item_data'] ) && ! empty( $booking_data['order_item_data'] ) ) {
							$booked_tickets = $booking_data['order_item_data'];
							foreach( $booked_tickets as $ticket ) {
								if( isset( $ticket->quantity ) ) {
									$total_booking += $ticket->quantity;
								} else if( isset( $ticket->qty ) ) {
									$total_booking += $ticket->qty;
								}
							}
						}
					}
				}
			}
		}
		return $total_booking;
	}
        
        /**
	 * Get event available tickets
	 * 
	 * @param object $event Event Data
	 * 
	 * @return int available tickets
	 */
	public function get_event_available_tickets( $event ) {
		$available_tickets = 0;
		if( ! empty( $event ) && ! $this->check_event_has_expired( $event ) && ! empty( $event->all_tickets_data ) ) {
                        if(isset($event->all_bookings))
                        {
                            $all_event_bookings = $this->get_event_booking_by_event_id( $event->id, true,$event->all_bookings );
                        }
                        else
                        {
                            $all_event_bookings = $this->get_event_booking_by_event_id( $event->id, true );
                        }
			$booked_tickets_data = $all_event_bookings['tickets'];
			foreach( $event->all_tickets_data as $ticket ) {
				$check_ticket_visibility = $this->check_for_ticket_visibility( $ticket, $event );
				if( ! empty( $check_ticket_visibility['status'] ) ) {
					$check_ticket_available = $this->check_for_ticket_available_for_booking( $ticket, $event );
					if( empty( $check_ticket_available['expire'] ) ) {
						$remaining_caps = $ticket->capacity;
						if( ! empty( $booked_tickets_data ) ) {
							if( isset( $booked_tickets_data[$ticket->id] ) && ! empty( $booked_tickets_data[$ticket->id] ) ) {
								$booked_ticket_qty = absint( $booked_tickets_data[$ticket->id] );
								if( $booked_ticket_qty > 0 ) {
									$remaining_caps = $ticket->capacity - $booked_ticket_qty;
								}
							}
						}
						$available_tickets += $remaining_caps;
					}
				}
			}
		}
		return $available_tickets;
	}

    public function ep_get_ticket_available_spaces($event, $ticket, $booked_tickets_data) {
        $available_tickets = 0;
        $check_ticket_visibility = $this->check_for_ticket_visibility( $ticket, $event );
        if( ! empty( $check_ticket_visibility['status'] ) ) {
            $check_ticket_available = $this->check_for_ticket_available_for_booking( $ticket, $event );
            if( empty( $check_ticket_available['expire'] ) ) {
                $remaining_caps = $ticket->capacity;
                if( ! empty( $booked_tickets_data ) ) {
                    if( isset( $booked_tickets_data[$ticket->id] ) && ! empty( $booked_tickets_data[$ticket->id] ) ) {
                        $booked_ticket_qty = absint( $booked_tickets_data[$ticket->id] );
                        if( $booked_ticket_qty > 0 ) {
                            $remaining_caps = $ticket->capacity - $booked_ticket_qty;
                        }
                    }
                }
                $available_tickets += $remaining_caps;
            }
        }
        return $available_tickets;
    }
        
        // depricated.
         public function get_event_child_data_by_parent_id( $parent_event_id, $fields ) {
            global $wpdb;
            $child_data = array();
            if( ! empty( $parent_event_id ) ) {
                $child_events = $this->ep_get_child_events( $parent_event_id, array( 'fields' => 'ids' ) );
                if( ! empty( $child_events ) ) {
                    $cids = implode( ', ', $child_events );
                    $child_data = $wpdb->get_results( $wpdb->prepare( "
                        SELECT p.ID, pm.meta_value as em_start_date FROM {$wpdb->postmeta} pm
                        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                        WHERE pm.meta_key = '%s' 
                        AND p.ID in($cids)", 'em_start_date' ));
                }
            }
            return $child_data;
    }
    
    public function ep_get_bookings_events($bookings)
    {
        $events = array();
        if(!empty($bookings))
        {
            foreach($bookings as $booking)
            {
                if(!isset($events[$booking->em_event]))
                {
                    $events[$booking->em_event] = $this->get_single_event( $booking->em_event );
                }
            }

        }
        return $events;
    }
    
    public function is_block_theme() 
    {
        $theme_root = get_template_directory();

        if (file_exists($theme_root . '/theme.json')) {
            return true;
        }

        $block_template_dirs = ['block-templates', 'templates', 'parts'];

        foreach ($block_template_dirs as $dir) {
            if (is_dir($theme_root . '/' . $dir)) {
                return true;
            }
        }
        return false;
    }
    
    public function get_header_block_theme() {
        $header_path = locate_template('block-templates/parts/header.html');
        if ($header_path) {
            include $header_path;
        } else {
            echo '<!-- Header template not found -->';
        }
    }

    public function get_footer_block_theme() {
        $footer_path = locate_template('block-templates/parts/footer.html');
        if ($footer_path) {
            include $footer_path;
        } else {
            echo '<!-- Footer template not found -->';
        }
    }
    
    public function ep_load_more_html($type,$args)
    {
       
        $max_num_pages = ceil( (int)$args->total_count / (int)$args->limit );
        if( $max_num_pages > 1 && isset( $args->load_more ) && $args->load_more == 1 ) {
            ?>
            <div class="<?php echo esc_attr($type); ?>-load-more ep-frontend-loadmore ep-box-w-100 ep-my-4 ep-text-center">
                <?php
                foreach($args as $key=>$value)
                {
                    ?>
                    <input type="hidden" id="<?php echo esc_attr($type).'-'. esc_attr($key); ?>" value="<?php echo esc_html($value);?>"/>
                    <?php
                }
                ?>
                    <button data-max="<?php echo esc_attr($max_num_pages);?>" id="ep-loadmore-<?php echo esc_attr($type); ?>" class="ep-btn ep-btn-outline-primary"><span class="ep-spinner ep-spinner-border-sm ep-mr-1"></span><?php esc_html_e( 'Load more', 'eventprime-event-calendar-management' );?></button>
            </div>
                <?php
        }
    }
    
    public function fetch_url_content($url) {
        $context = stream_context_create(array(
            'http' => array(
                'ignore_errors' => true
            )
        ));

        $response = file_get_contents($url, false, $context);
        $status_code = null;

        if(isset($http_response_header) && is_array($http_response_header)) {
            preg_match('/\d{3}/', $http_response_header[0], $matches);
            $status_code = $matches[0];
        }

        if($status_code == 200) {
            return $response;
        } else {
            return null;
        }
    }
    
    public function ep_get_venue_id_by_event_id($id)
        {
            $venue = get_post_meta($id, 'em_venue', true );
            if(isset($venue) && !empty($venue))
            {
                $em_venue =(is_array($venue))?$venue[0]:$venue;
            }
            else
            {
                $em_venue = '';
            }
            return $em_venue;
        }
    
    public function is_eventprime_plugin_page() 
    {
        global $pagenow;
        // Get the 'page' query parameter
        $page = filter_input(INPUT_GET, 'page');
        $post_type = filter_input(INPUT_GET, 'post_type');

        // Define the pages and post types related to your plugin
        $plugin_pages = [
            'eventprime-events',      // Example page slug for an event list page
            'eventprime-settings',    // Example page slug for the plugin settings page
            'eventprime-dashboard',   // Example page slug for the plugin dashboard
            // Add more page slugs as needed
        ];

        $plugin_post_types = [
            'em_event',
            'em_performer',
            'em_booking',
            // Add more custom post types as needed
        ];

        // Check if we are on a plugin-specific page
        if (in_array($page, $plugin_pages)) {
            return true;
        }

        // Check if we are on a plugin-specific post type page
        if (in_array($post_type, $plugin_post_types)) {
            return true;
        }

        // Additional checks for specific scenarios
        if ($pagenow == 'post-new.php' && in_array($post_type, $plugin_post_types)) {
            return true;
        }

        if ($pagenow == 'post.php') {
            $post_id = filter_input(INPUT_GET, 'post');
            if ($post_id) {
                $current_post_type = get_post_type($post_id);
                if (in_array($current_post_type, $plugin_post_types)) {
                    return true;
                }
            }
        }

        return false;
    }
    
    public function get_post_id_by_slug($slug,$post_type) {
        // Set up the arguments for the query
        $args = array(
            'name'        => $slug,
            'post_type'   => $post_type,
            'post_status' => 'publish',
            'numberposts' => 1,
        );

        // Perform the query
        $post = get_posts($args);

        // Check if the post object exists
        if ($post) {
            // Return the post ID
            return $post[0]->ID;
        } else {
            // Return null if the post is not found
            return null;
        }
    }
    
    public function get_taxonomy_id_by_slug($slug, $taxonomy) {
        $term = get_term_by('slug', $slug, $taxonomy);
        if ($term) {
            return $term->term_id;
        } else {
            return null; // Return null if the term is not found
        }
    }


    public function ep_get_id_by_slug($slug,$post_type)
    {
        $id = false;
        $posttypes = array('em_event','em_performer','em_sponsor');
        if(isset($slug) && !empty($slug))
        {
            if(!is_numeric($slug))
            {
                if(in_array($post_type, $posttypes ))
                {
                    $id = $this->get_post_id_by_slug( $slug,$post_type );
                }
                else
                {
                    $id = $this->get_taxonomy_id_by_slug( $slug,$post_type );
                }
            }
            else
            {
                $id = absint($slug);
            }
        }
        
        return $id;
    }
    
    public function ep_encrypt_decrypt_pass( $action, $string ) 
    {
            $output         = false;
            $encrypt_method = 'AES-256-CBC';
            $secret_key     = get_option( 'ep_encrypt_secret_key','This is my secret key' );
            $secret_iv      = get_option( 'ep_encrypt_secret_iv','This is my secret iv' );
            // hash
            $key = hash( 'sha256', $secret_key );
            // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
            $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );
            if ( $action == 'encrypt' ) {
                    if ( function_exists( 'openssl_encrypt' ) ) {
                            $output = openssl_encrypt( $string, $encrypt_method, $key, 0, $iv );
                            $output = base64_encode( $output );
                    } else {
                            $output = base64_encode( $string );
                    }
            } elseif ( $action == 'decrypt' ) {

                    if ( function_exists( 'openssl_decrypt' ) ) {
                            $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
                    } else {
                            $output = base64_decode( $string );
                    }
            }
            return $output;
    }
    
    public function eventprime_duplicate_post($post_id) 
    {
        // Get the original post
        $original_post = get_post($post_id);
        // Create a new post array
        $new_post = array(
            'post_title'    => $original_post->post_title . ' (Copy)',
            'post_content'  => $original_post->post_content,
            'post_status'   => 'draft',
            'post_type'     => $original_post->post_type,
            'post_author'   => $original_post->post_author,
        );
        // Insert the new post into the database
        $new_post_id = wp_insert_post($new_post);
        // Get all the post meta data from the original post
        $post_meta = get_post_meta($post_id);
        // Loop through each meta key and copy it to the new post
        foreach ($post_meta as $meta_key => $meta_values) {
            foreach ($meta_values as $meta_value) {
                add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
            }
         }
         
         // Get all the taxonomies for the post type
        $taxonomies = get_object_taxonomies($original_post->post_type);

        // Loop through each taxonomy and copy the terms to the new post
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy);
            $term_ids = wp_list_pluck($terms, 'term_id');
            wp_set_post_terms($new_post_id, $term_ids, $taxonomy);
        }

         return $new_post_id;
    }
    
    public function eventprime_get_allowed_wpkses_html()
    {
        $allowed_html = array(
            'table' => array(
                'class' => array(),
                'border' => array(),
                'cellspacing' => array(),
                'cellpadding' => array(),
                'width' => array(),
                'style' => array(),
            ),
            'tbody' => array(),
            'tr' => array(
                'style' => array(),
                'valign' => array(),
                'class' => array(),
                'id' => array(),
                'title' => array(),
            ),
            'th' => array(
                'scope' => array(),
                'class' => array(),
            ),
            'td' => array(
                'class' => array(),
                'colspan' => array(),
                'style' => array(),
                'align' => array(),
                'valign' => array(),
            ),
            'label' => array(
                'for' => array(),
                'class' => array(),
            ),
            'span' => array(
                'class' => array(),
            ),
            'input' => array(
                'type' => array(),
                'name' => array(),
                'id' => array(),
                'class' => array(),
                'value' => array(),
                'required' => array(),
                'checked' => array(), // For checkboxes
                'placeholder' => array(),
                'disabled' => array(),
                'data-*' => true, // Allowing data attributes
            ),
            'textarea' => array(
                'class' => array(),
                'rows' => array(),
                'cols' => array(),
                'name' => array(),
                'id' => array(),
                'style' => array(),
                'aria-hidden' => array(),
                'autocomplete' => array(),
                'data-*' => true, // Allowing data attributes
            ),
            'a' => array(
                'href' => array(),
                'target' => array(),
                'class' => array(),
                'data-*' => true, // Allowing data attributes
            ),
            'h1' => array(
                'style' => array(),
            ),
            'div' => array(
                'id' => array(),
                'class' => array(),
                'style' => array(),
                'aria-hidden' => array(),
                'data-*' => true, // Allowing data attributes
            ),
            'button' => array(
                'type' => array(),
                'id' => array(),
                'class' => array(),
                'aria-label' => array(),
                'aria-pressed' => array(),
                'data-*' => true, // Allowing data attributes
                'onclick' => array(), // Allowing JavaScript actions
            ),
            'strong' => array(),
            'em' => array(),
            'i' => array(
                'class' => array(),
            ),
            'p' => array(
                'class' => array(),
            ),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'iframe' => array(
                'id' => array(),
                'frameborder' => array(),
                'allowtransparency' => array(),
                'style' => array(),
                'title' => array(),
                'width' => array(),
                'height' => array(),
            ),
            'link' => array(
                'rel' => array(),
                'id' => array(),
                'href' => array(),
                'type' => array(),
                'media' => array(),
            ),
            'img' => array(
                'src' => array(),
                'alt' => array(),
                'width' => array(),
                'height' => array(),
                'style' => array(),
            ),
            'h1' => array(
                'style' => array(),
            ),
            'br' => array(), // Allowing line breaks
            'script' => array( // Allowing script elements for JavaScript
                'type' => array(),
                'src' => array(),
                'id' => array(),
            ),
            'link' => array( // Allowing <link> for stylesheets and related uses
                'rel' => array(),
                'id' => array(),
                'href' => array(),
                'type' => array(),
                'media' => array(),
            ),
            'option' => array( // For select elements
                'value' => array(),
                'selected' => array(),
            ),
            'optgroup'=>array(
                'label' => array(),
            ),
            'select' => array( // Allowing select inputs
                'name' => array(),
                'id' => array(),
                'class' => array(),
                'disabled' => array(),
            ),
        );

        return $allowed_html;
       
    }
    
    public function eventprime_update_cart_response($ticket_id,$qty)
    {
        $dbhandler = new EP_DBhandler;
        $all_offers_data = array(
            'all_offers' => array(),
            'all_show_offers' => array(),
            'show_ticket_offers' => array(),
            'ticket_offers' => array(),
            'applicable_offers' => array()
        );
        $response = array();
        $additional_fees = array();
        $additional_fee_total = 0; // Initialize this at the beginning of additional fees handling
        $applicable_offer = array();
        $ticket =  $dbhandler->get_row('TICKET', $ticket_id);
        if (empty($ticket)) {
            $response['message'] = esc_html__( 'Ticket data not found.', 'eventprime-event-calendar-management' );
        }
            
        if (isset($ticket) && !empty($ticket) && isset($ticket->offers) && !empty($ticket->offers)) 
        {
            $all_offers_data = $this->get_event_single_offer_data($all_offers_data, $ticket, $ticket->event_id,$qty);
        }
            
        if(isset($all_offers_data['applicable_offers'][$ticket_id]) && !empty($all_offers_data['applicable_offers'][$ticket_id]))
        {

            foreach($all_offers_data['applicable_offers'][$ticket_id] as $key=>$offer)
            {
                $applicable_offer[] = $offer;
            }
        }
            
        $row_ticket_price = floatval($ticket->price);
        $ticket_price_subtotal = $row_ticket_price * $qty;

        // Apply offers if applicable
        $offer_applied = $applicable_offer;
        $total_offer_discount_val = 0;
        $offer_text = '';
    
        $applied_offer_uid = array();
        $applied_offer_obj = array();
        if (!empty($offer_applied)) {
            foreach ($offer_applied as $offer) {
                //print_r($offer);
                $applied_offer_uid[] = $offer->uid;
                $applied_offer_obj[] = $offer;
                if ($offer->em_ticket_offer_discount_type === 'percentage') {
                    $discount_val = ($offer->em_ticket_offer_discount / 100) * $ticket_price_subtotal;
                } else {
                    $discount_val = $offer->em_ticket_offer_discount * $qty;
                }
                $total_offer_discount_val += $discount_val;
            }
            
            if($ticket_price_subtotal < $total_offer_discount_val)
            {
                $total_offer_discount_val = $ticket_price_subtotal;
            }
            
            $ticket_price_subtotal -= $total_offer_discount_val;
            $count = count($offer_applied);
            $offer_text = sprintf(
                /* translators: %d: Number of offers applied */
                _n('%d offer applied', '%d offers applied', $count, 'eventprime-event-calendar-management'),
                $count
            );
        }
            
        // Handle multiple offers max discount
        $max_discount_val = isset($ticket->multiple_offers_max_discount) ? floatval($ticket->multiple_offers_max_discount) : 0;
        if ($max_discount_val > 0 && $total_offer_discount_val > $max_discount_val) {
            // Apply maximum discount limit
            $total_offer_discount_val = $max_discount_val;
            $ticket_price_subtotal = ($row_ticket_price * $qty) - $max_discount_val;
            $offer_text = esc_html__( 'Max Offer Applied', 'eventprime-event-calendar-management' );
        }

        if ($ticket_price_subtotal < 0) {
            $ticket_price_subtotal = 0;
        }
            
        // Handle additional fees
        if (!empty($ticket->additional_fees)) {
            $additional_fees_data = json_decode($ticket->additional_fees, true);
            if (is_array($additional_fees_data)) {
                foreach ($additional_fees_data as $fee) {
                    $fee_amount = floatval($fee['price']) * $qty;
                    $additional_fee_total += $fee_amount;

                    $additional_fees[] = [
                        'label' => sanitize_text_field($fee['label']),
                        'price' => (float)$fee_amount,
                    ];
                }
            }
        }

        // Add additional fees to the subtotal
        $ticket_price_subtotal += $additional_fee_total;
        
        $formatted_subtotal = (float)$ticket_price_subtotal;
        $total_offer_discount_text =  (float)$total_offer_discount_val;
        
        
        // Prepare response data
        $response['id'] = $ticket_id;
        $response['category_id'] = $ticket->category_id;
        $response['name'] = $ticket->name;
        $response['price'] = (float)$row_ticket_price;
        $response['qty'] = (int)$qty;
        $response['offer'] = $total_offer_discount_text;
        $response['additional_fee'] = $additional_fees;
        $response['subtotal'] = (float)$formatted_subtotal;
        $response['offer_text'] = $offer_text;
        $response['total_offer_discount_text'] = (float)$total_offer_discount_text;
        $response['formatted_subtotal'] = (float)$formatted_subtotal;
        $response['applied_offer_uid'] = $applied_offer_uid;
        $response['applied_offer_obj'] = $applied_offer_obj;

        return $response;
    }
    
    public function ep_check_event_restrictions($event,$user_id = '')
    {
        $max_tickets_limit_per_user = isset($event->em_event_max_tickets_per_user)?(int)$event->em_event_max_tickets_per_user:0;
        $max_tickets_limit_per_order = isset($event->em_event_max_tickets_per_order)?(int)$event->em_event_max_tickets_per_order:0;
        $max_ticket_reached_message = ( isset($event->em_event_max_tickets_reached_message) && ! empty($event->em_event_max_tickets_reached_message))?$event->em_event_max_tickets_reached_message:esc_html__('You have already reached the maximum ticket limit for this event and cannot purchase additional tickets.','eventprime-event-calendar-management');
        $em_restrict_no_of_bookings_per_user = isset($event->em_restrict_no_of_bookings_per_user) ? $event->em_restrict_no_of_bookings_per_user:0;
        $return = array(true,'');

        if( !empty($em_restrict_no_of_bookings_per_user) ) {
            if(is_user_logged_in())
            {
                $user_id = get_current_user_id();
            }
            if(!empty($user_id))
            {
                $bookings = $this->eventprime_check_event_booking_by_user($event->em_id, $user_id);
                if( $bookings['total_booking'] >= $em_restrict_no_of_bookings_per_user )
                {
                    $return = array(false,sprintf( esc_html__('Booking limit reached!','eventprime-event-calendar-management'), $em_restrict_no_of_bookings_per_user ) );
                }
            }
        }

        if ( $return[0] !== false ) {
            if($max_tickets_limit_per_user > 0)
            {
                $event_id= $event->em_id;
                $bookings_by_user = 0;
                if(is_user_logged_in())
                {
                    $user_id = get_current_user_id();
                }
    
                if(!empty($user_id))
                {
                    $bookings= $this->eventprime_check_event_booking_by_user($event_id, $user_id);
                    $bookings_by_user = $bookings['total_attendees'];
                }
                if($max_tickets_limit_per_user > $bookings_by_user)
                {
                   $max_tickets = $max_tickets_limit_per_user - $bookings_by_user;
                   $message = sprintf(esc_html__('You can only purchase up to %d more tickets for this event. Please click the Cancel button to return to the event page and update your ticket selection.', 'eventprime-event-calendar-management'),$max_tickets);
                   $return = array($max_tickets,$message);
                }
                else
                {
                    $return = array(false,$max_ticket_reached_message);
                }
            }
            
            //if(!is_bool($return[0]))
            if($max_tickets_limit_per_order > 0)
            {
                if(!is_bool($return[0]))
                {
                    if($return[0] > $max_tickets_limit_per_order)
                    {
                        $max_tickets = $max_tickets_limit_per_order;
                        $message = sprintf(esc_html__('A maximum of %d tickets are allowed per order for this event.', 'eventprime-event-calendar-management'),$max_tickets);
                        $return = array($max_tickets,$message);
                    }
                    
                }
                elseif($return[0]!==false)
                {
                    $max_tickets = $max_tickets_limit_per_order;
                    $message = sprintf(esc_html__('A maximum of %d tickets are allowed per order for this event.', 'eventprime-event-calendar-management'),$max_tickets);
                    $return = array($max_tickets,$message);
                }
                
    
            }
        }
        
        return $return;
    }
    
    public function eventprime_check_event_booking_by_user_old( $event_id, $user_id ){
        $count = array();
        if( ! empty( $event_id ) && ! empty( $user_id ) ) {
            $meta_query = array(
                'relation' => 'AND',
                array(
                    'key'     => 'em_event', 
                    'value'   => $event_id, 
                    'compare' => '=', 
                    'type'    => 'NUMERIC,'
                ),
            );
            if(is_email($user_id))
            {
                $meta_query[] = array(
                    'key'     => 'em_order_info', 
                    'value'   => serialize(['ep_gb_email' => $user_id]), 
                    'compare' => 'LIKE',
                );
            }
            else
            {
                $meta_query[] = array(
                    'key'     => 'em_user', 
                    'value'   => $user_id, 
                    'compare' => '=', 
                    'type'    => 'NUMERIC,'
                );
            }
            $args = array(
                'numberposts' => -1,
                'orderby'     => 'date',
                'order'       => 'DESC',
                'post_status' => 'any',
                'meta_query'  => $meta_query,
                'post_type'   => 'em_booking'
            );
            $bookings = get_posts( $args );
            $count = $this->eventprime_get_event_booking_stats_by_event_id($event_id,$bookings);
            $count['bookings'] = $bookings;
        }
        return $count;
    }
    
    public function eventprime_check_event_booking_by_user($event_id, $user_identifier) 
    {
        if (empty($event_id) || empty($user_identifier)) {
            return []; // Return empty array if required parameters are missing
        }

        // Initialize the meta query
        $meta_query = [
            'relation' => 'AND',
            [
                'key'     => 'em_event',
                'value'   => $event_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ],
        ];

        $additional_meta_query = [];

        // Check if input is an email or user ID
        if (is_email($user_identifier)) {
            // Check if email is associated with a registered user
            $user = get_user_by('email', $user_identifier);

            if ($user) {
                // Combine user ID and guest email in the meta query
                $additional_meta_query = [
                    'relation' => 'OR',
                    [
                        'key'     => 'em_user',
                        'value'   => $user->ID,
                        'compare' => '=',
                        'type'    => 'NUMERIC',
                    ],
                    [
                        'key'     => 'em_order_info',
                        'value'   => '"ep_gb_email";s:' . strlen($user_identifier) . ':"' . $user_identifier . '"',
                        'compare' => 'LIKE',
                    ],
                ];
            } else {
                // Only match guest bookings with the email
                $additional_meta_query = [
                    [
                        'key'     => 'em_order_info',
                        'value'   => '"ep_gb_email";s:' . strlen($user_identifier) . ':"' . $user_identifier . '"',
                        'compare' => 'LIKE',
                    ],
                ];
            }
        } else {
                // If input is a user ID, match user bookings only
                $user_email = '';
                $user = get_userdata($user_identifier);
                if ($user) {
                    $user_email = $user->user_email;
                }
            // Combine user ID and guest email in the meta query
                $additional_meta_query = [
                    'relation' => 'OR',
                    [
                        'key'     => 'em_user',
                        'value'   => $user->ID,
                        'compare' => '=',
                        'type'    => 'NUMERIC',
                    ],
                    [
                        'key'     => 'em_order_info',
                        'value'   => '"ep_gb_email";s:' . strlen($user_email) . ':"' . $user_email . '"',
                        'compare' => 'LIKE',
                    ],
                ];
        }

        // Merge additional meta query
        $meta_query[] = $additional_meta_query;

        // WP_Query Arguments
        $args = [
            'numberposts' => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'post_status' => array('completed','pending'),
            'meta_query'  => $meta_query,
            'post_type'   => 'em_booking',
        ];

        // Fetch bookings
        $bookings = get_posts($args);

        // Get event booking stats
        $count = $this->eventprime_get_event_booking_stats_by_event_id($event_id, $bookings);
        return $count;
    }
    
    public function eventprime_get_pm_theme_name() {
           $dirname               = array();
           $ep_theme_path          = plugin_dir_path( EP_PLUGIN_FILE ) . 'public/partials/themes/';
           
           $wp_theme_dir           = get_stylesheet_directory();
           $override_ep_theme_path = $wp_theme_dir . '/eventprime/themes/';
           if ( file_exists( $ep_theme_path ) ) {
                   foreach ( glob( $ep_theme_path . '*', GLOB_ONLYDIR ) as $dir ) {
                             $dirname[] = basename( $dir );
                   }
           }
           
           if ( file_exists( $override_ep_theme_path ) ) {
                   foreach ( glob( $override_ep_theme_path . '*', GLOB_ONLYDIR ) as $dir2 ) {
                             $dirname[] = basename( $dir2 );
                   }
           }
           return array_unique( $dirname );
   }

   public function eventprime_get_pm_theme_path() {
           $dirname               = array();
           $ep_theme_path          =  plugin_dir_path( EP_PLUGIN_FILE ) . 'public/partials/themes/';
           $wp_theme_dir           = get_stylesheet_directory();
           $override_ep_theme_path = $wp_theme_dir . '/eventprime/themes/';
           if ( file_exists( $ep_theme_path ) ) {
                   foreach ( glob( $ep_theme_path . '*', GLOB_ONLYDIR ) as $dir ) {
                             $dirname[] = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/themes/' . basename( $dir );
                   }
           }

           if ( file_exists( $override_ep_theme_path ) ) {
                   foreach ( glob( $override_ep_theme_path . '*', GLOB_ONLYDIR ) as $dir2 ) {
                             $dirname[] = get_stylesheet_directory_uri() . '/eventprime/themes/' . basename( $dir2 );
                   }
           }
           return array_unique( $dirname );
   }

   public function ep_get_paypal_order_items($data) {
       $ep_functions = new Eventprime_Basic_Functions();
       $total_discount = 0;
       $ep_currency = $ep_functions->ep_get_global_settings('currency');

       $items = [
            'items_total' => 0,
            'items' => []
       ]; 

       // tickets 
       if( isset($data['ep_event_booking_ticket_data']) && !empty($data['ep_event_booking_ticket_data']) ) {

            $tickets = json_decode($data['ep_event_booking_ticket_data'], true);
            foreach ($tickets as $ticket) {
                $price = $ticket['price'];
                
                // Process additional fees
                if (!empty($ticket['additional_fee'])) {
                    foreach ($ticket['additional_fee'] as $add_data) {
                        $add_price = $add_data['price'];
                        if ($add_price > 0) {
                            $price = floatval($price) + ( floatval($add_price) / intval($ticket['qty']) );
                        }
                    }
                }
                
                // Calculate total discount
                if (!empty($ticket['offer'])) {
                    $total_discount += floatval($ticket['offer']);
                }
                
                // Prepare item data
                $item_data = array(
                    'name' => $ticket['name'],
                    'description' => $ticket['name'],
                    'unit_amount' => array(
                        'currency_code' => $ep_currency,
                        'value' => round( floatval($price), 2 ) 
                    ),
                    'discount' => array(
                        'currency_code' => $ep_currency,
                        'value' => round( floatval($ticket['offer'] ?? 0), 2 )
                    ),
                    'quantity' => intval($ticket['qty'])
                );
                
                $items['items_total'] += ( round( floatval($price), 2 ) * intval($ticket['qty']) );
                $items['items'][] = $item_data;
            }
        }
        
        // event additional fee 
        if( isset($data['ep_event_booking_event_fixed_price']) && !empty($data['ep_event_booking_event_fixed_price']) ) {
            $ep_event_booking_event_fixed_price = round( floatval($data['ep_event_booking_event_fixed_price']) , 2 );
            $item_data = [
                "name" => esc_html__("Event Fees", 'eventprime-event-calendar-management'),
                "description" => esc_html__("Event Fees", 'eventprime-event-calendar-management'),
                "unit_amount" => [
                    "currency_code" => $ep_currency,
                    "value" => $ep_event_booking_event_fixed_price
                ],
                "quantity" => 1
            ];
            
            $items['items_total'] += $ep_event_booking_event_fixed_price; 
            $items['items'][] = $item_data;

       }

        


        return $items;

   }
   public function ep_get_dashboard_setting_url($tab)
    {
        $nonce = wp_create_nonce('ep_settings_tab');
        $sub_tab_url = add_query_arg( 
                array(
                    'tab' => $tab,'tab_nonce'=>$nonce 
                ),admin_url('edit.php?post_type=em_event&page=ep-settings')
            );
        return $sub_tab_url;
    }
    
    public function ep_documentation_link_notice_html($text,$link)
    {
        ?>
            <div class="ep-article-notice">
                <p>
                <strong><?php echo esc_html($text);?>:</strong> 
                <a href="<?php echo esc_url($link);?>" target="_blank" rel="noopener"><?php esc_html_e('Read full guide','eventprime-event-calendar-management');?> <span class="dashicons dashicons-external"></span></a>
                
                </p>
            </div>
        <?php
    }
    
    public function ep_documentation_link_read_more_html($link,$read_more_text='')
    {
        if($read_more_text=='')
        {
            $read_more_text = esc_html__('Learn more','eventprime-event-calendar-management');
        }
         ?>
            <span class="ep-read-more">
                <a href="<?php echo esc_url($link);?>" target="_blank" rel="noopener"><?php echo esc_html($read_more_text); ?> <span class="dashicons dashicons-external"></span></a>
            </span>
        <?php
    }
    
    public function ep_privacy_export_personal_data( $email_address, $page = 1 ) {

       $user = get_user_by( 'email', $email_address );
       if ( ! $user ) {
           return array( 'data' => array(), 'done' => true );
       }

       /*---------------------------------------------
        * 1.  Collect this page of bookings
        *--------------------------------------------*/
       $per_page       = 500;                         // WP core recommendation
       $bookings_api   = new EventPrime_Bookings();
       $all_bookings   = $bookings_api->get_user_all_bookings( $user->ID, true );

       // Slice to current page
       $offset         = ( $page - 1 ) * $per_page;
       $bookings       = array_slice( $all_bookings, $offset, $per_page );

       if ( empty( $bookings ) ) {
           return array( 'data' => array(), 'done' => true );
       }

       /*---------------------------------------------
        * 2.  Build header map once
        *--------------------------------------------*/
       $labels = array(
           'id'              => __( 'Booking ID',        'eventprime-event-calendar-management' ),
           'event'           => __( 'Event',            'eventprime-event-calendar-management' ),
           'start'           => __( 'Start Date / Time','eventprime-event-calendar-management' ),
           'end'             => __( 'End Date / Time',  'eventprime-event-calendar-management' ),
           'tickets'         => __( 'Tickets',          'eventprime-event-calendar-management' ),
           'attendees'      => __( 'Attendee Details', 'eventprime-event-calendar-management' ),
           'total'           => __( 'Amount Paid',      'eventprime-event-calendar-management' ),
           'gateway'         => __( 'Payment Gateway',  'eventprime-event-calendar-management' ),
           'booking_status'  => __( 'Booking Status',   'eventprime-event-calendar-management' ),
           'payment_status'  => __( 'Payment Status',   'eventprime-event-calendar-management' ),
       );

       $out = array();

       /*---------------------------------------------
        * 3.  Transform each booking → item_data
        *--------------------------------------------*/
       foreach ( $bookings as $booking ) {
           
           // Get attendees
        $attendee_summary = '';
        $attendee_count   = 0;

        if ( ! empty( $booking->em_attendee_names ) ) {
            $attendee_names = maybe_unserialize( $booking->em_attendee_names );

            foreach ( $attendee_names as $ticket_id => $attendee_data ) {
                $labels_list = $this->ep_get_booking_attendee_field_labels( $attendee_data[1] );

                foreach ( $attendee_data as $attendee ) {
                    $attendee_count++;
                    $attendee_line = '';
                    $serialize_attendee = print_r($attendee,true);
                    foreach ( $labels_list as $label ) {
                        if($attendee_line!='')
                        {
                            $attendee_line .= "| ";
                        }
                        $slug = $this->ep_get_slug_from_string( $label );
                        $val  = isset( $attendee[ $slug ] ) && ! empty( $attendee[ $slug ] )
                            ? $attendee[ $slug ]
                            : '---';
                         if ( $val === '---' ) {
                            foreach ( $attendee as $sub_key => $sub_value ) {
                                if ( is_array( $sub_value ) && isset( $sub_value[ $slug ] ) ) {
                                    $val = $sub_value[ $slug ];
                                    break;
                                }
                            }
                        }

                        $attendee_line .= "{$label}: {$val} ";
                    }

                    $attendee_summary .= "Attendee {$attendee_count}: {$attendee_line} \n";
                }
            }
        }

           // ----- minimal data extraction (adapt as needed) -----
           $start = ! empty( $booking->event_data->em_start_date )
               ? date_i18n( 'Y-m-d H:i', $booking->event_data->em_start_date )
               : '';

           $end   = ! empty( $booking->event_data->em_end_date )
               ? date_i18n( 'Y-m-d H:i', $booking->event_data->em_end_date )
               : '';

           $item  = array(
               'id'             => $booking->em_id,
               'event'          => $booking->em_name,
               'start'          => $start,
               'end'            => $end,
               'tickets'        => count( (array) $booking->em_attendee_names ),
                'attendees'      => trim( $attendee_summary ),
               'total'          => isset( $booking->em_order_info['booking_total'] )
                                      ? $booking->em_order_info['booking_total']
                                      : '',
               'gateway'        => ucfirst( $booking->em_payment_method ?? 'N/A' ),
               'booking_status' => ucfirst( $booking->em_status          ?? 'N/A' ),
               'payment_status' => ucfirst( $booking->em_payment_log['payment_status']
                                      ?? $booking->em_payment_log['offline_status']
                                      ?? 'N/A' ),
           );

           // Convert to WP privacy format
           $item_data = array();
           foreach ( $item as $key => $value ) {
               $item_data[] = array(
                   'name'  => $labels[ $key ],
                   'value' => (string) $value,
               );
           }

           $out[] = array(
               'group_id'    => 'eventprime',
               'group_label' => __( 'EventPrime Bookings', 'eventprime-event-calendar-management' ),
               'item_id'     => 'booking-' . $booking->em_id,
               'data'        => $item_data,
           );
       }

       /*---------------------------------------------
        * 4.  Tell WP if more pages remain
        *--------------------------------------------*/
       $done = $offset + $per_page >= count( $all_bookings );

       return array(
           'data' => $out,
           'done' => $done,
       );
   }


    public function ep_privacy_delete_personal_data( $email_address, $page = 1 ) 
    {

        $user = get_user_by( 'email', $email_address );
        if ( ! $user ) {
            return array(
                'items_removed'  => 0,
                'items_retained' => 0,
                'messages'       => array(),
                'done'           => true,
            );
        }
        $bookings = new EventPrime_Bookings();
        $user_bookings      = $bookings->get_user_all_bookings( $user->ID,false );
        //print_r($bookings);die;
        $items_removed = 0;

        foreach ( $user_bookings as $booking ) {
            wp_delete_post( $booking->em_id, true );
            $items_removed++;
        }

        return array(
            'items_removed'  => $items_removed,
            'items_retained' => 0,
            'messages'       => array(),
            'done'           => true,
        );
    }

    
}

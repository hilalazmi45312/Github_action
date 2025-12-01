<?php


// add_action('wp_enqueue_scripts', 'sh_enqueue_plus_one_membership_assets');
// function sh_enqueue_plus_one_membership_assets()
// {
//     wp_enqueue_style(
//         'plus-one-membership-style',
//         SENHENG_CORE_URL . 'assets/css/plus-one-membership.css'
//     );
// }

add_action('woocommerce_account_members-area_endpoint', 'shw_members_area_content');

function shw_members_area_content()
{
    wp_enqueue_style(
        'plus-one-membership-style',
        SENHENG_CORE_URL . 'assets/css/plus-one-membership.css'
    );

    $user = wp_get_current_user();
    $id_sso = get_user_meta($user->ID, 'idsso', true);
    if (empty($id_sso)) {
        echo '<p>You have no active memberships.</p>';
        return;
    }
    $email = get_user_meta($user->ID, 'cust_email', true);
    $cards = [
        'eBSC' => [
            'title' => 'PLUSONE® E-BASIC',
            'image_url'   => SENHENG_CORE_ASSETS_URL . 'images/member_card/PlusOneBasic_1735193085.png',
            'code'        => 'eBSC',
        ],
        'C' => [
            'title' => 'PLUSONE® CLASSIC',
            'image_url'   => SENHENG_CORE_ASSETS_URL . 'images/member_card/PlusOneClassic_1735193167.png',
            'code'        => 'C',
        ],
        'L' => [
            'title' => 'PLUSONE® LADY',
            'image_url'   => SENHENG_CORE_ASSETS_URL . 'images/member_card/PlusOneLady_1735193225.png',
            'code'        => 'L',
        ],
        'G' => [
            'title' => 'PlusOne® Gold',
            'image_url'   => SENHENG_CORE_ASSETS_URL . 'images/member_card/PlusOneGold_1735193245.png',
            'code'        => 'G',
        ],
        'LG' => [
            'title' => 'PlusOne® Lady Gold',
            'image_url'   => SENHENG_CORE_ASSETS_URL . 'images/member_card/PlusOneLadyGold_1735193200.png',
            'code'        => 'LG',
        ],
        'DC' => [
            'title' => 'Dummy Card',
            'image_url'   => SENHENG_CORE_ASSETS_URL . 'images/member_card/corporate_card_1740993497.jpeg',
            'code'        => 'DC',
        ],
        'SHCT' => [
            'title' => 'PlusOne® S-Coin',
            'image_url'   => SENHENG_CORE_ASSETS_URL . 'images/member_card/PlusOneCorporatePrivilege_1739346634.png',
            'code'        => 'SHCT',
        ],
    ];

    $api = new MagentoAPI();
    $cardData = $api->getAllCardInfo($id_sso, $email);

    if ($cardData['flag'] === false || empty($cardData)) {
        echo '<p>You have no active memberships.</p>';
        return;
    }

    foreach ($cardData['card_info'] as $card) {
        $membership_name = $cards[$card['CARD_TYPE']]['title'] ?? '-';
        $membership_id   = $card['CARD_NO'] ?? '';
        $points          = $card['POINT'] ?? '0';
        $expiry_date     = $card['VALID_TO'] ?? '';
        $converted = date('d/m/Y', strtotime($expiry_date));
        $card_type       = $card['CARD_TYPE'] ?? '';
        $card_image_url  = $cards[$card_type]['image_url'] ?? '';

        echo '
        <div class="plusone-membership-card">
            <div class="card-left">
                <img src="' . esc_url($card_image_url) . '" alt="Membership Card" />
            </div>
            <div class="card-right">
                <h3>' . esc_html($membership_name) . '</h3>
                <p><strong>ID:</strong> ' . esc_html($membership_id) . '</p>
                <p><strong>PlusOne Points:</strong> ' . esc_html($points) . '</p>
                <p><strong>Expiry date:</strong> <span>' . esc_html($converted) . '</span></p>
            </div>
        </div>';
    }

    // if (function_exists('wc_memberships_get_user_active_memberships')) {
    //     $memberships = wc_memberships_get_user_active_memberships(get_current_user_id());

    //     if (! empty($memberships)) {
    //         foreach ($memberships as $membership) {
    //             $membership_plan = $membership->get_plan();
    //             $membership_name = $membership_plan->get_name();
    //             $membership_id   = $membership->get_id();
    //             $expiry_date     = $membership->get_end_date('d/m/Y'); // format as needed

    //             // Mock data – replace with real values or custom fields
    //             $points = get_user_meta(get_current_user_id(), 'plusone_points', true) ?: 0;
    //             $card_image_url = 'https://www.senheng.com.my/assets/images/1944562ef6b608f5215a058879169d62.png';

    //             echo '
    //             <div class="plusone-membership-card">
    //                 <div class="card-left">
    //                     <img src="' . esc_url($card_image_url) . '" alt="Membership Card" />
    //                 </div>
    //                 <div class="card-right">
    //                     <h3>' . esc_html($membership_name) . '</h3>
    //                     <p><strong>ID:</strong> ' . esc_html($membership_id) . '</p>
    //                     <p><strong>PlusOne Points:</strong> ' . esc_html($points) . '</p>
    //                     <p><strong>Expiry date:</strong> <span>' . esc_html($expiry_date) . '</span></p>
    //                 </div>
    //             </div>';
    //         }
    //     } else {
    //         echo '<p>You have no active memberships.</p>';
    //     }
    // }
}
